<?php

namespace App\Http\Controllers\Eventualidades;

use App\Eventualidades\Eventualidad;
use App\Eventualidades\Observacion;
use App\Eventualidades\ResumenDiario;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\TipoEventualidad;
use App\Turno;
use App\Usuario;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Response;
use Validator;
use View;

class EventualidadesController extends Controller
{
    private static $instance;
    public static function getInstancia()
    {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    public function buscarTodoDesdeFiscalizador()
    {
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        foreach ($usuario->casinos as $casino) {
            $casinos[] = $casino->id_casino;
        }
        $eventualidades = DB::unprepared(DB::raw("CREATE TEMPORARY TABLE eventualidades_temp
                                                        AS (
                                                            SELECT eventualidad.*,DATE(eventualidad.fecha_generacion) as fecha, TIME(eventualidad.fecha_generacion) as hora,tipo_eventualidad.descripcion,casino.nombre
                                                            FROM eventualidad inner join casino on eventualidad.id_casino = casino.id_casino
                                                                 inner join tipo_eventualidad on tipo_eventualidad.id_tipo_eventualidad = eventualidad.id_tipo_eventualidad
                                                            );
                                         "
        )
        );
        $eventualidades = DB::table('eventualidades_temp')
            ->whereIn('id_casino', $casinos)
            ->orderBy('fecha', 'DES')
            ->orderBy('hora', 'DES')
            ->take(25)
            ->get();
        $query1 = DB::statement(DB::raw("
                                       DROP TABLE eventualidades_temp
                                   "));

        $sectores = DB::table('sector')
        ->whereIn('sector.id_casino',$casinos)->get();
        $islas = DB::table('isla')
        ->join('sector','isla.id_sector','=','sector.id_sector')
        ->whereIn('sector.id_casino',$casinos)->get();

        $turnos = Turno::all();
        $tiposEventualidades = TipoEventualidad::all();
        $casinos = $usuario->casinos;
        $esControlador = 0;
        $usuario = Usuario::find(session('id_usuario'));
        foreach ($usuario->roles as $rol) {
            if ($rol->descripcion == "CONTROL" || $rol->descripcion == "ADMINISTRADOR" || $rol->descripcion == "SUPERUSUARIO") {
                $esControlador = 1;
            }
        }
        UsuarioController::getInstancia()->agregarSeccionReciente('Eventualidades', 'eventualidades');

        return view('Eventualidades.index', ['eventualidades' => $eventualidades,
            'esControlador' => $esControlador,
            'turnos' => $turnos,
            'tiposEventualidades' => $tiposEventualidades,
            'casinos' => $casinos,
            'sectores' => $sectores,
            'islas' => $islas,
            'usuario' => $usuario]);
    }

    public function procedimientosPorCasino($id_casino)
    {
        $procs = \App\Procedimiento::join('casino_tiene_procedimiento as ctp',
                    'ctp.id_procedimiento', '=', 'procedimiento.id_procedimiento')
            ->where('procedimiento.activo', 1)
            ->where('ctp.activo', 1)
            ->where('ctp.id_casino', $id_casino)
            ->orderBy('procedimiento.orden')
            ->get(['procedimiento.id_procedimiento', 'procedimiento.nombre']);
        return response()->json(['procedimientos' => $procs]);
    }

    public function guardarEventualidad(Request $request)
    {
        // borrador = "guardado temporal" → estado 0 (sin terminar), validación relajada.
        $borrador   = (int) $request->input('borrador') === 1;
        $idBorrador = $request->input('id_borrador') ?: null;

        // El encabezado (casino, turno, fecha) es obligatorio siempre (columnas NOT NULL).
        // El resto: relajado en borrador, completo en el guardado final.
        $reglas = [
            'fecha_toma'                   => 'required|date',
            'turno'                        => 'required|integer',
            'horario'                      => 'nullable|string|max:50',
            'id_casino'                    => 'required|exists:casino,id_casino',
            'procedimientos.*.estado'      => 'nullable|in:✔,*',
            'procedimientos.*.observacion' => 'nullable|string|max:1000',
            'boletin_adjunto'              => 'nullable|string|max:300',
            'observaciones'                => 'nullable|string|max:4000',
        ];
        if ($borrador) {
            $reglas['procedimientos']       = 'nullable|array';
            $reglas['menores']              = 'nullable|in:Si,Sí,No';
            $reglas['fumadores']            = 'nullable|in:Si,Sí,No';
            $reglas['otros_fiscalizadores'] = 'nullable|string|max:300';
        } else {
            $reglas['procedimientos']       = 'required|array|min:1';
            $reglas['procedimientos.*.estado'] = 'required|in:✔,*';
            $reglas['menores']              = 'required|in:Si,Sí,No';
            $reglas['fumadores']            = 'required|in:Si,Sí,No';
            $reglas['otros_fiscalizadores'] = 'required|string|max:300';
        }
        $this->validate($request, $reglas);

        try {
            DB::beginTransaction();

            $userId = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];

            // Validación cruzada de procedimientos: los enviados deben pertenecer al casino;
            // en el guardado FINAL además deben venir TODOS los del casino (en borrador, parcial OK).
            $idsValidos  = \App\Procedimiento::join('casino_tiene_procedimiento as ctp',
                    'ctp.id_procedimiento', '=', 'procedimiento.id_procedimiento')
                ->where('procedimiento.activo', 1)
                ->where('ctp.activo', 1)
                ->where('ctp.id_casino', $request->id_casino)
                ->pluck('procedimiento.id_procedimiento')->toArray();
            $idsEnviados = array_keys((array) $request->input('procedimientos', []));
            if (array_diff($idsEnviados, $idsValidos)) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Procedimientos no asignados al casino'], 422);
            }
            if (!$borrador && array_diff($idsValidos, $idsEnviados)) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Falta responder algún procedimiento del casino'], 422);
            }

            // Crear nueva, o continuar un borrador existente (sólo propio y en estado 0).
            if ($idBorrador) {
                $ev = Eventualidad::find($idBorrador);
                if (!$ev || (int)$ev->estado_eventualidad !== 0 || $ev->id_usuario_generador != $userId) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'error' => 'Borrador inválido o sin permiso'], 422);
                }
            } else {
                $ev = new Eventualidad();
                $ev->fecha_carga          = date('Y-m-d H:i:s');
                $ev->id_usuario_generador = $userId;
            }

            $ev->fecha_toma           = $request->fecha_toma;
            $ev->id_turno             = $request->turno;
            $ev->horario              = $request->horario;
            $ev->id_casino            = $request->id_casino;
            $ev->menores              = $request->menores;
            $ev->fumadores            = $request->fumadores;
            $ev->boletin_adjunto      = $request->boletin_adjunto;
            $ev->observaciones        = $request->observaciones;
            $ev->otros_fiscalizadores = $request->otros_fiscalizadores;
            $ev->estado_eventualidad  = $borrador ? 0 : 1; // 0 = sin terminar (borrador), 1 = generado
            $ev->save();

            $mapaEstado = ['✔' => 'realizado', '*' => 'no_realizado'];
            $sync = [];
            foreach ((array)$request->input('procedimientos', []) as $idProc => $p) {
                $estado      = $mapaEstado[$p['estado'] ?? ''] ?? null;
                $observacion = isset($p['observacion']) ? trim($p['observacion']) : null;
                if ($observacion === '') $observacion = null;
                // Saltar filas vacías (sin respuesta y sin observación)
                if ($estado === null && $observacion === null) continue;
                $sync[(int)$idProc] = [
                    'estado'      => $estado,
                    'observacion' => $observacion,
                ];
            }
            $ev->procedimientosRealizados()->sync($sync);

            DB::commit();
            return response()->json(['success' => true, 'id' => $ev->id_eventualidades, 'borrador' => $borrador]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Devuelve los datos de un borrador (estado 0) para precargar el form al "Continuar".
     * Sólo el propio creador puede recuperarlo.
     */
    public function obtenerBorrador($id)
    {
        $userId = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $ev = Eventualidad::with('procedimientosRealizados')->find($id);
        if (!$ev || (int)$ev->estado_eventualidad !== 0 || $ev->id_usuario_generador != $userId) {
            return response()->json(['error' => 'Borrador no encontrado'], 404);
        }
        $procs = [];
        foreach ($ev->procedimientosRealizados as $p) {
            $procs[$p->id_procedimiento] = [
                'estado'      => $p->pivot->estado,        // 'realizado' | 'no_realizado' | null
                'observacion' => $p->pivot->observacion,
            ];
        }
        return response()->json([
            'id'                   => $ev->id_eventualidades,
            'id_casino'            => $ev->id_casino,
            'id_turno'             => $ev->id_turno,
            'fecha_toma'           => $ev->fecha_toma,
            'horario'              => $ev->horario,
            'menores'              => $ev->menores,
            'fumadores'            => $ev->fumadores,
            'boletin_adjunto'      => $ev->boletin_adjunto,
            'observaciones'        => $ev->observaciones,
            'otros_fiscalizadores' => $ev->otros_fiscalizadores,
            'procedimientos'       => $procs,
        ]);
    }


public function PDF($id) {
  $eventualidad = Eventualidad::with(['casino','turno','procedimientosRealizados'])->findOrFail($id);
  $eventualidad->fecha_toma = substr($eventualidad->fecha_toma, 0, 16);
  $eventualidad->horario = substr($eventualidad->horario,0, 16);
  $horaIn = substr($eventualidad->horario,0,5);
  $horaFin = substr($eventualidad->horario,11,16);
  $eventualidad->horario = $horaIn . ' a '.$horaFin;


  $view = View::make('Eventualidades.pdf', compact('eventualidad'));
  $dompdf = new Dompdf();
  $dompdf->set_paper('A4', 'portrait');
  $dompdf->loadHtml($view);
  $dompdf->render();

  $canvas = $dompdf->getCanvas();
    $cpdf   = $canvas->get_cpdf();
    $cpdf->addInfo('Subject','event_loteria_pdf');
    $cpdf->addInfo('Title', $id);
    // Marcador redundante para detectar a qué eventualidad pertenece al subir la firmada,
    // aunque se modifique el PDF, se renombre o se reenvíe (ver subirEventualidad).
    $cpdf->addInfo('Keywords', 'eventualidad_id_'.$id);

    $output   = $dompdf->output();

    $dir = storage_path('app/public/EventualidadesCrudas');
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    $hoy = date('Y_m_d',strtotime($eventualidad->fecha_toma));
    $filename = "eventualidades{$eventualidad->id_eventualidades}_{$hoy}_{$eventualidad->casino->nombre}_turno_{$eventualidad->turno->nro_turno}.pdf";
    $path     = "{$dir}/{$filename}";

    file_put_contents($path, $output);

    return response()->file($path, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$filename.'"',
    ]);
}
public function obtenerTurnos($id_casino)
{
    $dia = date('N');                      // 1..7
    if (request()->query('useYesterday')) {
        $dia = $dia - 1 ?: 7;              // si baja de 1 lo llevas a 7
    }

    $turnos = Turno::where('id_casino', $id_casino)
        ->where('dia_desde', '<=', $dia)
        ->where('dia_hasta', '>=', $dia)
        ->orderBy('nro_turno')
        ->get(['id_turno','nro_turno','entrada','salida']);

    return response()->json(['turnos' => $turnos]);
}



public function ultimasIntervenciones(Request $request)
{
    $user = Usuario::find(session('id_usuario'));
    // Suponiendo que la relación se llama "casinos" y cada Casino tiene id_casino
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $page    = max(1, (int)$request->query('page', 1));
    $perPage = max(1, (int)$request->query('page_size', 20));

    $query = Eventualidad::with(['casino','turno'])
              // aquí filtramos por los casinos del usuario
              ->whereIn('id_casino', $allowedCasinoIds)
              ->orderBy('fecha_toma','desc');

    // Los borradores ("sin terminar", estado 0) sólo los ve su creador.
    $query->where(function ($q) use ($user) {
        $q->where('estado_eventualidad', '>', 0)
          ->orWhere(function ($q2) use ($user) {
              $q2->where('estado_eventualidad', 0)
                 ->where('id_usuario_generador', $user->id_usuario);
          });
    });

    if ($f_casino = $request->query('id_casino')) {
      if (in_array($f_casino, $allowedCasinoIds)) {
        $query->where('id_casino', $f_casino);
      }
    }
    // OJO: estado 0 ("sin terminar") es falsy → comparo explícitamente contra null/''.
    $f_estado = $request->query('estado_eventualidad');
    if ($f_estado !== null && $f_estado !== '') {
      $query->where('estado_eventualidad', $f_estado);
    }
    if ($f_fecha = $request->query('fecha')) {
      $query->where('fecha_toma',">=", $f_fecha);
    }
    if ($f_hasta = $request->query('hasta')){
      $f_hasta++;
      $query->where('fecha_toma',"<=",$f_hasta);
    }
    if ($f_turno = $request->query('nro_turno')) {
      $query->whereHas('turno', function($q) use ($f_turno) {
        $q->where('nro_turno', $f_turno);
      });
    }
    if ($request->observados == 1) {
    $query->whereHas('observaciones', function($q){
        $q->whereNotNull('id_archivo');
      });
    }

    $total = $query->count();
    $evs   = $query
               ->skip(($page-1)*$perPage)
               ->take($perPage)
               ->get();

    $esControlador = $user->roles
        ->pluck('descripcion')
        ->intersect(['ADMINISTRADOR','SUPERUSUARIO'])
        ->isNotEmpty() ? 1 : 0;

    return response()->json([
      'intervenciones' => $evs,
      'controlador'    => $esControlador,
      'pagination'     => [
         'current_page' => $page,
         'per_page'     => $perPage,
         'total'        => $total,
      ],
    ]);
}


public function reporteDiario(Request $request)
{
    $user = \App\Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();

    $desde = $request->query('desde') ?: null;   // sin cota inferior por defecto (se cargan TODOS)
    $hasta = $request->query('hasta') ?: null;   // sin cota superior por defecto
    $idCasinoFiltro = $request->query('id_casino');

    $page    = max(1, (int) $request->query('page', 1));
    $perPage = (int) $request->query('page_size', 10);
    if ($perPage < 1) $perPage = 10;

    $casinos = $allowedCasinoIds;
    if ($idCasinoFiltro && in_array((int)$idCasinoFiltro, $allowedCasinoIds)) {
        $casinos = [(int)$idCasinoFiltro];
    }
    if (empty($casinos)) {
        return response()->json(['rows' => [], 'pagination' => ['current_page' => 1, 'per_page' => $perPage, 'total' => 0]]);
    }

    // Total de grupos (casino, fecha) en el rango — para la paginación.
    $totalRow = DB::table('eventualidades as ev')
        ->whereIn('ev.id_casino', $casinos)
        ->where('ev.estado_eventualidad', '>', 0) // excluye borradores ("sin terminar")
        ->when($desde, function ($q) use ($desde) { return $q->whereDate('ev.fecha_toma', '>=', $desde); })
        ->when($hasta, function ($q) use ($hasta) { return $q->whereDate('ev.fecha_toma', '<=', $hasta); })
        ->selectRaw('COUNT(DISTINCT ev.id_casino, DATE(ev.fecha_toma)) as total')
        ->first();
    $total = $totalRow ? (int) $totalRow->total : 0;

    // NOTA: los "esperados" se calculan POR DÍA dentro de la query de abajo (no con la asignación
    // actual del casino). Así los resúmenes históricos no cambian si después se agrega/saca/desactiva
    // un procedimiento. esperados = procedimientos respondidos ese día (realizado + no_realizado).

    // Cubiertos por (casino, fecha) + estado del resumen (LEFT JOIN compatible con Laravel 5.4).
    $data = DB::table('eventualidades as ev')
        ->join('casino as c','c.id_casino','=','ev.id_casino')
        ->leftJoin('eventualidad_tiene_procedimiento as etp',
            'etp.id_eventualidades','=','ev.id_eventualidades')
        ->leftJoin('resumen_diario as rd', function ($j) {
            $j->on('rd.id_casino', '=', 'ev.id_casino')
              ->whereRaw('rd.fecha = DATE(ev.fecha_toma)');
        })
        ->whereIn('ev.id_casino', $casinos)
        ->where('ev.estado_eventualidad', '>', 0) // excluye borradores ("sin terminar")
        ->when($desde, function ($q) use ($desde) {
            return $q->whereDate('ev.fecha_toma','>=',$desde);
        })
        ->when($hasta, function ($q) use ($hasta) {
            return $q->whereDate('ev.fecha_toma','<=',$hasta);
        })
        ->selectRaw('
            ev.id_casino,
            c.nombre as casino,
            DATE(ev.fecha_toma) as fecha,
            COUNT(DISTINCT ev.id_eventualidades) as eventualidades,
            COUNT(DISTINCT CASE WHEN etp.estado = \'realizado\' THEN etp.id_procedimiento END) as cubiertos,
            COUNT(DISTINCT CASE WHEN etp.estado IS NOT NULL THEN etp.id_procedimiento END) as esperados,
            COALESCE(MAX(rd.estado), \'no_visado\') as estado,
            MAX(rd.id_resumen_diario) as id_resumen_diario
        ')
        ->groupBy('ev.id_casino','c.nombre',DB::raw('DATE(ev.fecha_toma)'))
        ->orderBy(DB::raw('DATE(ev.fecha_toma)'),'desc')
        ->orderBy('c.nombre','asc')
        ->limit($perPage)->offset(($page - 1) * $perPage)
        ->get();

    // Count de observaciones por resumen — segunda query, mergeada en PHP.
    $resumenIds = $data->pluck('id_resumen_diario')->filter()->all();
    $obsCount = [];
    if (!empty($resumenIds)) {
        $obsCount = DB::table('observacion_resumen_diario')
            ->whereIn('id_resumen_diario', $resumenIds)
            ->select('id_resumen_diario', DB::raw('COUNT(*) as c'))
            ->groupBy('id_resumen_diario')
            ->pluck('c', 'id_resumen_diario')
            ->toArray();
    }

    $rows = $data->map(function($r) use ($obsCount) {
        $r->esperados           = (int)$r->esperados;
        $r->cubiertos           = (int)$r->cubiertos;
        $r->eventualidades      = (int)$r->eventualidades;
        $r->observaciones_count = isset($obsCount[$r->id_resumen_diario]) ? (int)$obsCount[$r->id_resumen_diario] : 0;
        $r->coverage_pct        = $r->esperados ? (int)round(100*$r->cubiertos/$r->esperados) : 0;
        return $r;
    });

    return response()->json([
        'rows' => $rows,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
        ],
    ]);
}


public function reporteDiarioDetalle(Request $request)
{
    $idCasino = (int)$request->query('id_casino');
    $fecha    = $request->query('fecha');
    if (!$idCasino || !$fecha) {
        return response()->json(['error'=>'Falta id_casino o fecha'], 422);
    }

    // Permission check: el casino debe estar entre los del usuario.
    $user = \App\Usuario::find(session('id_usuario'));
    $allowedCasinoIds = $user->casinos->pluck('id_casino')->toArray();
    if (!in_array($idCasino, $allowedCasinoIds)) {
        return response()->json(['error'=>'Acceso denegado al casino'], 403);
    }

    // Por cada procedimiento: estado + usuario que lo generó + turno.
    $rows = DB::table('eventualidad_tiene_procedimiento as etp')
        ->join('eventualidades as ev','ev.id_eventualidades','=','etp.id_eventualidades')
        ->leftJoin('usuario as u','u.id_usuario','=','ev.id_usuario_generador')
        ->leftJoin('turno as t','t.id_turno','=','ev.id_turno')
        ->where('ev.id_casino', $idCasino)
        ->where('ev.estado_eventualidad', '>', 0) // excluye borradores ("sin terminar")
        ->whereDate('ev.fecha_toma', $fecha)
        ->whereNotNull('etp.estado')
        ->get(['etp.id_procedimiento','etp.estado','u.nombre','u.user_name','t.nro_turno']);

    $porProc = $rows->groupBy('id_procedimiento');

    // Esperados = los procedimientos que estuvieron EN JUEGO ESE DÍA (no la asignación actual del
    // casino), así el detalle histórico no cambia si después se agrega/saca un procedimiento.
    $esperados = \App\Procedimiento::whereIn('id_procedimiento', $porProc->keys()->all() ?: [-1])
        ->orderBy('orden')
        ->pluck('nombre','id_procedimiento');

    $entriesDe = function ($entries, $estado) {
        return $entries->where('estado', $estado)
            ->map(function ($r) {
                return [
                    'usuario' => $r->nombre ?: ($r->user_name ?: '—'),
                    'turno'   => $r->nro_turno,
                ];
            })
            ->unique(function ($e) { return $e['usuario'].'|'.$e['turno']; })
            ->values()->toArray();
    };

    $realizados = []; $noRealizados = []; $sinRespuesta = [];
    foreach ($esperados as $id => $nombre) {
        $entries = $porProc->get($id);
        if (!$entries) {
            $sinRespuesta[] = $nombre;
            continue;
        }
        $estados = $entries->pluck('estado')->unique()->toArray();
        // Prioridad: si alguien dijo "realizado", cuenta como realizado.
        if (in_array('realizado', $estados, true)) {
            $realizados[] = [
                'nombre'  => $nombre,
                'entries' => $entriesDe($entries, 'realizado'),
            ];
        } elseif (in_array('no_realizado', $estados, true)) {
            $noRealizados[] = [
                'nombre'  => $nombre,
                'entries' => $entriesDe($entries, 'no_realizado'),
            ];
        } else {
            $sinRespuesta[] = $nombre;
        }
    }

    return response()->json([
        'fecha'         => $fecha,
        'realizados'    => $realizados,
        'no_realizados' => $noRealizados,
        'sin_respuesta' => $sinRespuesta,
    ]);
}


public function subirEventualidad(Request $request)
{

    //valido, subo archivo

    $this->validate($request,[
            'upload' => 'required|file|mimes:pdf',
        ], [
            'upload.required' => 'Debés seleccionar un archivo.',
            'upload.mimes'    => 'Solo se permiten archivos PDF.',
        ]);

    $file = $request->file('upload');
    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

    $dir = storage_path('app/public/EventualidadesAFirmar');
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    $path = $file->storeAs('public/EventualidadesAFirmar', $filename);

    $fullPath = storage_path("app/{$path}");
    // Detectamos a qué eventualidad pertenece SIN exigir que el PDF sea "original":
    // se puede modificar, mandar por WhatsApp, etc. Buscamos el id embebido en TODO el archivo:
    // primero el marcador redundante (PDFs nuevos) y, si no, el metadato /Title de respaldo.
    $content = file_get_contents($fullPath);
    // dompdf escribe los metadatos (/Keywords, /Title) en UTF-16BE con BOM: cada caracter
    // ASCII queda intercalado con un \x00 (p.ej. "e\x00v\x00e\x00n\x00t..."). Por eso el
    // marcador 'eventualidad_id_5' NO aparece como bytes contiguos. Buscamos en una copia
    // sin bytes nulos para que el marcador (y el /Title) matcheen vengan como vengan.
    $contentSinNulos = str_replace("\x00", '', $content);
    $id = null;
    if (preg_match('/eventualidad_id_(\d+)/i', $contentSinNulos, $m)) {
        $id = (int) $m[1];
    } elseif (preg_match('/\/Title\s*\(([^)]*)\)/', $contentSinNulos, $m2)) {
        $digits = preg_replace('/\D+/', '', $m2[1]);
        if ($digits !== '') $id = (int) $digits;
    }
    if (!$id) {
        Storage::disk('public')->delete("EventualidadesAFirmar/{$filename}");
        return response()->json([
            'success' => false,
            'cod'     => 3,
            'error'   => 'No se pudo detectar a qué eventualidad pertenece el PDF.'
        ], 422);
    }

    //modifico el objeto en la base de datos
        $ev = Eventualidad::find($id);
        if (! $ev) {
          // Borramos el PDF recién subido para no dejar archivos huérfanos en disco
          // (las demás ramas de error ya lo hacen).
          Storage::disk('public')->delete("EventualidadesAFirmar/{$filename}");
          return response()->json([
            'success' => false,
            'cod'     => 2,
            'error'   => "No existe eventualidad con ID $id"
          ], 404);
        }
        if($ev->estado_eventualidad==3) {
          Storage::disk('public')->delete("EventualidadesAFirmar/{$filename}");
          return response()->json([
                'success' => false,
                'error'   => "Esta eventualidad ya esta visada.",
                'cod'     => 3
                ]);
        }
        if ($ev->id_archivo) Storage::disk('public')->delete("EventualidadesAFirmar/{$ev->id_archivo}");

        $ev->id_archivo = $filename;
        $ev->estado_eventualidad = 2; // marcado como firmado
        $ev->save();

        // Si el resumen diario de ese (casino, fecha) YA está visado, esta eventualidad
        // recién firmada pasa automáticamente a visada (estado 3).
        $autoVisada = false;
        $fechaResumen = substr($ev->fecha_toma, 0, 10);
        $resumenVisado = ResumenDiario::where('id_casino', $ev->id_casino)
            ->whereDate('fecha', $fechaResumen)
            ->where('estado', 'visado')
            ->exists();
        if ($resumenVisado) {
          try {
            $autoVisada = $this->visarUnaFirmada($ev);
          } catch (\Throwable $e) {
            \Log::error('Auto-visado tras firmar falló (ev '.$ev->id_eventualidades.'): '.$e->getMessage());
          }
        }

    // 4) Responder JSON
    return response()->json([
      'success'     => true,
      'path'        => $path,
      'url'         => Storage::url($path), //
      'auto_visada' => $autoVisada,
    ]);
}

public function visarEventualidad($id){

  $ev = Eventualidad::find($id);
  if (! $ev) {
    return response()->json([
      'success' => false,
      'error'   => "Ocurrió un error al intentar visar la eventualidad con id $id"
    ], 404);
  }

  $this->visarUnaFirmada($ev); // mueve AFirmar->Firmadas y deja estado_eventualidad=3

  return 1;
}

/**
 * SOLO filesystem: mueve el PDF de una eventualidad de EventualidadesAFirmar a
 * EventualidadesFirmadas. Idempotente (si ya está en Firmadas o no hay archivo, no hace nada).
 * No toca la BD — por eso es seguro llamarla DESPUÉS de DB::commit() en la cascada del resumen.
 */
public function moverArchivoAFirmadas(Eventualidad $ev)
{
  if (!$ev->id_archivo) return;
  $disk = 'public';
  $dir  = storage_path('app/public/EventualidadesFirmadas');
  if (!file_exists($dir)) {
      mkdir($dir, 0755, true);
  }

  $source = 'EventualidadesAFirmar/'  . $ev->id_archivo;
  $target = 'EventualidadesFirmadas/' . $ev->id_archivo;
  if (!Storage::disk($disk)->exists($target) && Storage::disk($disk)->exists($source)) {
    Storage::disk($disk)->move($source, $target);
  }
  // Limpieza best-effort del PDF crudo.
  Storage::disk($disk)->delete("EventualidadesCrudas/eventualidad_{$ev->id_eventualidades}.pdf");
}

/**
 * SOLO filesystem: mueve el PDF de vuelta de EventualidadesFirmadas a EventualidadesAFirmar.
 * Idempotente. No toca la BD.
 */
public function moverArchivoAAFirmar(Eventualidad $ev)
{
  if (!$ev->id_archivo) return;
  $disk = 'public';
  $dir  = storage_path('app/public/EventualidadesAFirmar');
  if (!file_exists($dir)) {
      mkdir($dir, 0755, true);
  }

  $source = 'EventualidadesFirmadas/' . $ev->id_archivo;
  $target = 'EventualidadesAFirmar/'  . $ev->id_archivo;
  if (!Storage::disk($disk)->exists($target) && Storage::disk($disk)->exists($source)) {
    Storage::disk($disk)->move($source, $target);
  }
}

/**
 * Pasa UNA eventualidad firmada (estado 2) a visada (estado 3): mueve el PDF y guarda el estado.
 * Para los flujos de UNA sola eventualidad (visado individual y auto-visado al firmar), donde
 * no hay transacción batch. El estado SIEMPRE se setea (la BD es la fuente de verdad del flujo);
 * el movimiento de archivo es best-effort. Devuelve true.
 */
public function visarUnaFirmada(Eventualidad $ev)
{
  $this->moverArchivoAFirmadas($ev);
  $ev->estado_eventualidad = 3; // marcado como visado
  $ev->save();
  return true;
}

public function eliminarEventualidad($id){
  $ev = Eventualidad::findOrFail($id);

  if ($ev->id_archivo) {
      Storage::disk('public')->delete("EventualidadesCrudas/eventualidad_{$ev->id_eventualidades}.pdf");
      Storage::disk('public')->delete("EventualidadesAFirmar/{$ev->id_archivo}");
      Storage::disk('public')->delete("EventualidadesFirmadas/{$ev->id_archivo}");
  }


  if($ev===null) return 0;
  $ev->casino()->dissociate();
  $ev->estado_eventualidad = null;
  $ev->save();

  Eventualidad::destroy($id);


  return 1;
}

public function guardarObservacion(Request $request)
{
    DB::beginTransaction();
    try {
        $observacion = new Observacion();
        $observacion->id_eventualidades    = $request->id_eventualidades;
        $observacion->observacion          = $request->observacion;
        $observacion->id_usuario_generador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $observacion->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'id'      => $observacion->id_observacion_eventualidades,
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

/**
 * Endpoint unificado: crea UNA observación con texto + N archivos adjuntos.
 * Reemplaza la combinación guardarObservacion + subirObservacion.
 */
public function agregarObservacion(Request $request)
{
    $this->validate($request, [
        'id_eventualidades' => 'required|exists:eventualidades,id_eventualidades',
        'observacion'       => 'nullable|string|max:5000',
        'files'             => 'nullable|array',
        'files.*'           => 'file|max:20480',
    ]);

    $texto   = trim($request->input('observacion', ''));
    $archivos = $request->file('files', []);
    if ($texto === '' && empty($archivos)) {
        return response()->json([
            'success' => false,
            'error'   => 'Tenés que escribir una observación o adjuntar al menos un archivo.'
        ], 422);
    }

    DB::beginTransaction();
    try {
        $observacion = new Observacion();
        $observacion->id_eventualidades    = $request->id_eventualidades;
        $observacion->observacion          = $texto !== '' ? $texto : null;
        $observacion->id_usuario_generador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
        $observacion->save();

        if (!empty($archivos)) {
            $dir = storage_path('app/public/EventualidadesFirmadas/ObservacionesFirmadas');
            if (!file_exists($dir)) mkdir($dir, 0755, true);
            foreach ($archivos as $file) {
                $filename = time() . '_' . mt_rand(1000, 9999) . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->storeAs('public/EventualidadesFirmadas/ObservacionesFirmadas', $filename);
                \App\Eventualidades\ArchivoObservacionEventualidad::create([
                    'id_observacion_eventualidades' => $observacion->id_observacion_eventualidades,
                    'filename'                      => $filename,
                ]);
            }
        }

        DB::commit();
        return response()->json([
            'success' => true,
            'id'      => $observacion->id_observacion_eventualidades,
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}




public function subirObservacion(Request $request)
{

    $file = $request->file('uploadObs');
    $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

    $dir = storage_path('app/public/EventualidadesFirmadas/ObservacionesFirmadas');
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    $path = $file->storeAs('public/EventualidadesFirmadas/ObservacionesFirmadas', $filename);

    $fullPath = storage_path("app/{$path}");

    $id = $request->input('id_eventualidades');

    $ob = new Observacion();
    $ob->id_eventualidades=$id;
    $ob->id_usuario_generador = UsuarioController::getInstancia()->quienSoy()['usuario']['id_usuario'];
    $ob->id_archivo=$filename;
    $ob->save();


    return response()->json([
      'success' => true,
      'path'    => $path,
      'url'     => Storage::url($path),
    ]);
  }


  public function getObservaciones($id)
  {
      // Metadatos de la eventualidad para mostrar en el título del modal
      $meta = DB::table('eventualidades as ev')
          ->leftJoin('casino as c',  'c.id_casino', '=', 'ev.id_casino')
          ->leftJoin('turno as t',   't.id_turno',  '=', 'ev.id_turno')
          ->leftJoin('usuario as u', 'u.id_usuario','=', 'ev.id_usuario_generador')
          ->where('ev.id_eventualidades', $id)
          ->first([
              'ev.id_eventualidades',
              'ev.fecha_toma',
              DB::raw('c.nombre as casino'),
              DB::raw('t.nro_turno as turno'),
              DB::raw('COALESCE(u.nombre, u.user_name, "—") as creador'),
          ]);

      $obs = DB::table('observacion_eventualidades as o')
          ->leftJoin('usuario as u', 'u.id_usuario', '=', 'o.id_usuario_generador')
          ->where('o.id_eventualidades', $id)
          ->orderBy('o.created_at', 'desc')
          ->get([
              'o.id_observacion_eventualidades as id',
              'o.observacion',
              'o.id_archivo',
              'o.created_at',
              'u.imagen as user_imagen',
              DB::raw('COALESCE(u.nombre, u.user_name, "—") as usuario'),
          ]);

      // Cargar archivos de la nueva tabla agrupados por id de observación.
      $obsIds = $obs->pluck('id')->all();
      $archivosPorObs = [];
      if (!empty($obsIds)) {
          $rows = DB::table('archivo_observacion_eventualidad')
              ->whereIn('id_observacion_eventualidades', $obsIds)
              ->orderBy('id_archivo_observacion_eventualidad', 'asc')
              ->get(['id_observacion_eventualidades', 'filename']);
          foreach ($rows as $r) {
              $archivosPorObs[$r->id_observacion_eventualidades][] = [
                  'filename' => $r->filename,
                  'url'      => url('/eventualidades/visualizarArchivo/observaciones/'.$r->filename),
              ];
          }
      }

      $obs->transform(function ($o) use ($archivosPorObs) {
          $files = $archivosPorObs[$o->id] ?? [];
          // Legacy: si la observación vieja tenía un id_archivo, lo incluyo como primer archivo.
          if ($o->id_archivo) {
              array_unshift($files, [
                  'filename' => $o->id_archivo,
                  'url'      => url('/eventualidades/visualizarArchivo/observaciones/'.$o->id_archivo),
              ]);
          }
          $o->files = $files;
          unset($o->id_archivo);
          return $o;
      });

      $usuario = Usuario::find(session('id_usuario'));
      $esControlador = $usuario->roles
          ->pluck('descripcion')
          ->intersect(['ADMINISTRADOR','SUPERUSUARIO'])
          ->isNotEmpty() ? 1 : 0;

      return response()->json([
          'obs'         => $obs,
          'controlador' => $esControlador,
          'meta'        => $meta,
      ]);
  }

  public function verObservacionPdf($id)
{
    $observacion = Observacion::with(['eventualidad.turno','eventualidad.casino'])
        ->findOrFail($id);

    $html = \View::make('Eventualidades.observacion_pdf', compact('observacion'))->render();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($html);
    $dompdf->render();

    $pdf = $dompdf->output();
    $filename = sprintf(
        'observacion_de_eventualidad_%s_%s.pdf',
        $observacion->id_eventualidades,
        $observacion->id_observacion_eventualidades
    );

    return response($pdf, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$filename.'"',
        'Cache-Control'       => 'private, no-store, max-age=0, must-revalidate',
    ]);
}

  public function eliminarObservacion($id){
    // Sólo admin/superusuario puede borrar (defensa en profundidad: la UI ya oculta el botón a
    // los demás, pero la ruta es alcanzable a mano). Mismo guard que ResumenDiarioController.
    $u = Usuario::find(session('id_usuario'));
    $esControlador = $u && $u->roles->pluck('descripcion')
        ->intersect(['ADMINISTRADOR','SUPERUSUARIO'])->isNotEmpty();
    if (!$esControlador) {
        return response()->json(['success' => false, 'error' => 'Sin permiso para eliminar observaciones'], 403);
    }

    $ob = Observacion::findOrFail($id);

    // Borrar archivo legacy (columna id_archivo) si existe
    if ($ob->id_archivo) {
        Storage::disk('public')->delete("EventualidadesFirmadas/ObservacionesFirmadas/{$ob->id_archivo}");
        Storage::disk('public')->delete("EventualidadesFirmadas/Observaciones/observacion_de_eventualidad_{$ob->id_eventualidades}_{$ob->id_observacion_eventualidades}");
    }

    // Borrar archivos nuevos (tabla archivo_observacion_eventualidad) de disco
    $archivos = DB::table('archivo_observacion_eventualidad')
        ->where('id_observacion_eventualidades', $id)
        ->pluck('filename');
    foreach ($archivos as $fn) {
        Storage::disk('public')->delete("EventualidadesFirmadas/ObservacionesFirmadas/{$fn}");
    }
    // Los rows en archivo_observacion_eventualidad se borran solos por FK CASCADE.

    if($ob===null) return 0;
    $ob->eventualidad()->dissociate();
    $ob->save();

    Observacion::destroy($id);

    return 1;
  }

  public function visualizarArchivo($estado,$id_archivo){
    //@TODO Validar que el id_archivo exista y que el usuario tenga acceso
    $path = [
      'firmado' => 'app/public/EventualidadesAFirmar',
      'visado' => 'app/public/EventualidadesFirmadas',
      'observaciones' => 'app/public/EventualidadesFirmadas/ObservacionesFirmadas'
    ];

    if(!array_key_exists($estado,$path)){
      throw new \Exception('Estado '.$estado.' invalido');
    }

    $abs_file = storage_path($path[$estado].'/'.$id_archivo);
    if (!file_exists($abs_file)) abort(404);   // 404 limpio en vez de 500 si falta el archivo (igual que verArchivo)
    // Sólo PDF e imágenes inline (preview); el resto (CSV, Excel, etc.) se descarga
    // (si no, el navegador abre el CSV como texto plano).
    $ext  = strtolower(pathinfo($id_archivo, PATHINFO_EXTENSION));
    $disp = in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp']) ? 'inline' : 'attachment';
    return response()->stream(function () use ($abs_file) {
        readfile($abs_file);
      }, 200, [
      'Content-Type' => mime_content_type($abs_file),
      'Content-Disposition' => "$disp; filename=\"$id_archivo\""
    ]);
  }

}
