<?php

namespace App\Http\Controllers\Eventualidades;

use App\Eventualidades\Eventualidad;
use App\Eventualidades\ObservacionResumenDiario;
use App\Eventualidades\ResumenDiario;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResumenDiarioController extends Controller
{
    private static $instance;
    public static function getInstancia()
    {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    /**
     * Página "Resumen Diario" (sólo admin/super vía middleware tiene_permiso:visar_resumen_diario).
     * El reporte por casino se carga por AJAX desde el filtro de la propia vista.
     */
    public function index()
    {
        $usuario = Usuario::find(session('id_usuario'));
        $casinos = $usuario->casinos;
        UsuarioController::getInstancia()->agregarSeccionReciente('Resumen Diario', 'resumen_diario');
        return view('Eventualidades.resumen_diario', compact('casinos', 'usuario'));
    }

    /**
     * Devuelve el resumen para (casino, fecha). Lazy-create si no existe.
     */
    private function obtenerOcrear($idCasino, $fecha)
    {
        $resumen = ResumenDiario::where('id_casino', $idCasino)
            ->whereDate('fecha', $fecha)
            ->first();
        if (!$resumen) {
            $resumen = new ResumenDiario();
            $resumen->id_casino = $idCasino;
            $resumen->fecha     = $fecha;
            $resumen->estado    = 'no_visado';
            $resumen->save();
        }
        return $resumen;
    }

    private function chequearAccesoCasino($idCasino)
    {
        $u = Usuario::find(session('id_usuario'));
        $allowed = $u->casinos->pluck('id_casino')->toArray();
        return in_array((int)$idCasino, $allowed);
    }

    public function visar(Request $request)
    {
        $this->validate($request, [
            'id_casino' => 'required|exists:casino,id_casino',
            'fecha'     => 'required|date',
        ]);

        if (!$this->chequearAccesoCasino($request->id_casino)) {
            return response()->json(['success' => false, 'error' => 'Acceso denegado al casino'], 403);
        }

        try {
            DB::beginTransaction();
            $r = $this->obtenerOcrear($request->id_casino, $request->fecha);
            $r->estado             = 'visado';
            $r->id_usuario_visador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
            $r->fecha_visado       = date('Y-m-d H:i:s');
            $r->save();

            // Cascada: al visar el resumen, se visan las eventualidades FIRMADAS (estado 2)
            // de ese (casino, fecha). Las que aún no estén firmadas (estado 1) quedan generadas
            // y se visarán automáticamente al firmarse (ver subirEventualidad).
            // El cambio de estado va DENTRO de la transacción; el movimiento de archivos va
            // DESPUÉS del commit (Storage::move no es transaccional: un rollback no lo desharía).
            $firmadas = Eventualidad::where('id_casino', $request->id_casino)
                ->whereDate('fecha_toma', $request->fecha)
                ->where('estado_eventualidad', 2)
                ->get();
            foreach ($firmadas as $ev) {
                $ev->estado_eventualidad = 3; // visada
                $ev->save();
            }

            DB::commit();

            // Movimiento de PDFs AFirmar->Firmadas fuera de la transacción (FS, idempotente).
            foreach ($firmadas as $ev) {
                try {
                    EventualidadesController::getInstancia()->moverArchivoAFirmadas($ev);
                } catch (\Throwable $e) {
                    \Log::error('Mover PDF a Firmadas falló (ev '.$ev->id_eventualidades.'): '.$e->getMessage());
                }
            }

            return response()->json(['success' => true, 'id_resumen_diario' => $r->id_resumen_diario]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function desvisar(Request $request)
    {
        $this->validate($request, [
            'id_casino' => 'required|exists:casino,id_casino',
            'fecha'     => 'required|date',
        ]);

        if (!$this->chequearAccesoCasino($request->id_casino)) {
            return response()->json(['success' => false, 'error' => 'Acceso denegado al casino'], 403);
        }

        try {
            DB::beginTransaction();
            $r = $this->obtenerOcrear($request->id_casino, $request->fecha);
            $r->estado             = 'no_visado';
            $r->id_usuario_visador = null;
            $r->fecha_visado       = null;
            $r->save();

            // Cascada inversa: al quitar el visado del resumen, las eventualidades visadas
            // (estado 3) de ese (casino, fecha) vuelven a firmadas (estado 2).
            // Estado dentro de la transacción; archivos después del commit (igual que en visar()).
            $visadas = Eventualidad::where('id_casino', $request->id_casino)
                ->whereDate('fecha_toma', $request->fecha)
                ->where('estado_eventualidad', 3)
                ->get();
            foreach ($visadas as $ev) {
                $ev->estado_eventualidad = 2; // vuelve a firmada
                $ev->save();
            }

            DB::commit();

            // Movimiento de PDFs Firmadas->AFirmar fuera de la transacción (FS, idempotente).
            foreach ($visadas as $ev) {
                try {
                    EventualidadesController::getInstancia()->moverArchivoAAFirmar($ev);
                } catch (\Throwable $e) {
                    \Log::error('Mover PDF a AFirmar falló (ev '.$ev->id_eventualidades.'): '.$e->getMessage());
                }
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function listarObservaciones($idCasino, $fecha)
    {
        if (!$this->chequearAccesoCasino($idCasino)) {
            return response()->json(['error' => 'Acceso denegado al casino'], 403);
        }

        $resumen = ResumenDiario::where('id_casino', $idCasino)
            ->whereDate('fecha', $fecha)
            ->first();

        $obs = collect();
        if ($resumen) {
            $obs = DB::table('observacion_resumen_diario as o')
                ->leftJoin('usuario as u', 'u.id_usuario', '=', 'o.id_usuario_generador')
                ->where('o.id_resumen_diario', $resumen->id_resumen_diario)
                ->orderBy('o.created_at', 'desc')
                ->get([
                    'o.id_observacion_resumen_diario as id',
                    'o.observacion',
                    'o.id_archivo',
                    'o.created_at',
                    'u.imagen as user_imagen',
                    DB::raw('COALESCE(u.nombre, u.user_name, "—") as usuario'),
                ]);

            $obsIds = $obs->pluck('id')->all();
            $archivosPorObs = [];
            if (!empty($obsIds)) {
                $rows = DB::table('archivo_observacion_resumen')
                    ->whereIn('id_observacion_resumen_diario', $obsIds)
                    ->orderBy('id_archivo_observacion_resumen', 'asc')
                    ->get(['id_observacion_resumen_diario', 'filename']);
                foreach ($rows as $r) {
                    $archivosPorObs[$r->id_observacion_resumen_diario][] = [
                        'filename' => $r->filename,
                        'url'      => url('/eventualidades/resumen-diario/archivo/' . $r->filename),
                    ];
                }
            }

            $obs->transform(function ($o) use ($archivosPorObs) {
                $files = $archivosPorObs[$o->id] ?? [];
                // Legacy: id_archivo viejo, si existe, va primero
                if ($o->id_archivo) {
                    array_unshift($files, [
                        'filename' => $o->id_archivo,
                        'url'      => url('/eventualidades/resumen-diario/archivo/' . $o->id_archivo),
                    ]);
                }
                $o->files = $files;
                unset($o->id_archivo);
                return $o;
            });
        }

        $usuario = Usuario::find(session('id_usuario'));
        $esControlador = $usuario->roles->pluck('descripcion')
            ->intersect(['ADMINISTRADOR','SUPERUSUARIO'])
            ->isNotEmpty() ? 1 : 0;

        return response()->json([
            'obs'         => $obs,
            'controlador' => $esControlador,
        ]);
    }

    /**
     * Lista de eventualidades de un (casino, fecha) — usado por el reporte
     * para expandir la fila y mostrar los partes del día.
     */
    public function eventualidadesDelDia($idCasino, $fecha)
    {
        if (!$this->chequearAccesoCasino($idCasino)) {
            return response()->json(['error' => 'Acceso denegado al casino'], 403);
        }

        $eventualidades = DB::table('eventualidades as ev')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'ev.id_usuario_generador')
            ->leftJoin('turno as t',  't.id_turno',  '=', 'ev.id_turno')
            ->where('ev.id_casino', $idCasino)
            ->where('ev.estado_eventualidad', '>', 0) // excluye borradores ("sin terminar")
            ->whereDate('ev.fecha_toma', $fecha)
            ->orderBy('ev.id_turno', 'asc')
            ->orderBy('ev.id_eventualidades', 'asc')
            ->get([
                'ev.id_eventualidades as id',
                'ev.estado_eventualidad as estado',
                DB::raw('t.nro_turno as turno'),
                DB::raw('COALESCE(u.nombre, u.user_name, "—") as creador'),
            ]);

        return response()->json(['eventualidades' => $eventualidades]);
    }

    /**
     * Endpoint unificado: 1 observación con texto + N archivos. Reemplaza
     * la combinación guardarObservacion + subirObservacion.
     */
    public function agregarObservacion(Request $request)
    {
        $this->validate($request, [
            'id_casino'   => 'required|exists:casino,id_casino',
            'fecha'       => 'required|date',
            'observacion' => 'nullable|string|max:5000',
            'files'       => 'nullable|array',
            'files.*'     => 'file|max:20480',
        ]);

        if (!$this->chequearAccesoCasino($request->id_casino)) {
            return response()->json(['success' => false, 'error' => 'Acceso denegado al casino'], 403);
        }

        $texto    = trim($request->input('observacion', ''));
        $archivos = $request->file('files', []);
        if ($texto === '' && empty($archivos)) {
            return response()->json([
                'success' => false,
                'error'   => 'Tenés que escribir una observación o adjuntar al menos un archivo.'
            ], 422);
        }

        try {
            DB::beginTransaction();
            $resumen = $this->obtenerOcrear($request->id_casino, $request->fecha);

            $o = new ObservacionResumenDiario();
            $o->id_resumen_diario    = $resumen->id_resumen_diario;
            $o->id_usuario_generador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
            $o->observacion          = $texto !== '' ? $texto : null;
            $o->save();

            if (!empty($archivos)) {
                $dir = storage_path('app/public/EventualidadesFirmadas/ObservacionesResumen');
                if (!file_exists($dir)) mkdir($dir, 0755, true);
                foreach ($archivos as $file) {
                    $filename = time() . '_' . mt_rand(1000, 9999) . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                    $file->storeAs('public/EventualidadesFirmadas/ObservacionesResumen', $filename);
                    \App\Eventualidades\ArchivoObservacionResumen::create([
                        'id_observacion_resumen_diario' => $o->id_observacion_resumen_diario,
                        'filename'                      => $filename,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'id' => $o->id_observacion_resumen_diario]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function guardarObservacion(Request $request)
    {
        $this->validate($request, [
            'id_casino'   => 'required|exists:casino,id_casino',
            'fecha'       => 'required|date',
            'observacion' => 'required|string|max:5000',
        ]);

        if (!$this->chequearAccesoCasino($request->id_casino)) {
            return response()->json(['success' => false, 'error' => 'Acceso denegado al casino'], 403);
        }

        try {
            DB::beginTransaction();
            $resumen = $this->obtenerOcrear($request->id_casino, $request->fecha);

            $o = new ObservacionResumenDiario();
            $o->id_resumen_diario     = $resumen->id_resumen_diario;
            $o->id_usuario_generador  = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
            $o->observacion           = $request->observacion;
            $o->save();

            DB::commit();
            return response()->json(['success' => true, 'id' => $o->id_observacion_resumen_diario]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function subirObservacion(Request $request)
    {
        $this->validate($request, [
            'id_casino' => 'required|exists:casino,id_casino',
            'fecha'     => 'required|date',
            'uploadObs' => 'required|file|max:20480',
        ]);

        if (!$this->chequearAccesoCasino($request->id_casino)) {
            return response()->json(['success' => false, 'error' => 'Acceso denegado al casino'], 403);
        }

        $resumen = $this->obtenerOcrear($request->id_casino, $request->fecha);

        $file = $request->file('uploadObs');
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

        $dir = storage_path('app/public/EventualidadesFirmadas/ObservacionesResumen');
        if (!file_exists($dir)) mkdir($dir, 0755, true);

        $file->storeAs('public/EventualidadesFirmadas/ObservacionesResumen', $filename);

        $o = new ObservacionResumenDiario();
        $o->id_resumen_diario    = $resumen->id_resumen_diario;
        $o->id_usuario_generador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $o->id_archivo           = $filename;
        $o->save();

        return response()->json(['success' => true, 'id' => $o->id_observacion_resumen_diario]);
    }

    public function verArchivo($filename)
    {
        $path = storage_path('app/public/EventualidadesFirmadas/ObservacionesResumen/' . $filename);
        if (!file_exists($path)) abort(404);
        // Sólo PDF e imágenes se muestran inline (preview); el resto (CSV, Excel, etc.) se descarga
        // (si no, el navegador muestra el CSV como texto plano).
        $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $disp = in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp']) ? 'inline' : 'attachment';
        return response()->file($path, [
            'Content-Type'        => mime_content_type($path),
            'Content-Disposition' => $disp . '; filename="' . $filename . '"',
        ]);
    }

    public function eliminarObservacion($id)
    {
        $u = Usuario::find(session('id_usuario'));
        $esControlador = $u->roles->pluck('descripcion')
            ->intersect(['ADMINISTRADOR','SUPERUSUARIO'])
            ->isNotEmpty();
        if (!$esControlador) {
            return response()->json(['success' => false, 'error' => 'Sin permiso para eliminar observaciones'], 403);
        }

        try {
            $o = ObservacionResumenDiario::findOrFail($id);
            // Legacy
            if ($o->id_archivo) {
                Storage::disk('public')->delete('EventualidadesFirmadas/ObservacionesResumen/' . $o->id_archivo);
            }
            // Archivos nuevos
            $archivos = DB::table('archivo_observacion_resumen')
                ->where('id_observacion_resumen_diario', $id)
                ->pluck('filename');
            foreach ($archivos as $fn) {
                Storage::disk('public')->delete('EventualidadesFirmadas/ObservacionesResumen/' . $fn);
            }
            // Las filas del bridge se borran en cascada.
            $o->delete();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
