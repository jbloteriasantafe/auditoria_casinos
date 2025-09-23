<?php

namespace App\Http\Controllers\NotasCasino;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;


class NotasCasinoController extends Controller
{

    protected $USER;

    public function __construct() {
        $this->middleware(function ($request, $next) {
        $this->USER = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        return $next($request);
    });
    }

    public function index(){
        try{
            $categorias = DB::connection('gestion_notas_mysql')
            ->table('categorias')
            ->get();
            $tipos_evento = DB::connection('gestion_notas_mysql')
            ->table('tipo_eventos')
            ->get();

            $tipos_nota = array_map(function($item){
                return (object) $item;
            }, [
                ['id_tipo_nota'=>'1','tipo_nombre' => 'Comun'],
                ['id_tipo_nota'=>'2','tipo_nombre' => 'Publicidad (Bis)'],
                ['id_tipo_nota'=>'3','tipo_nombre' => 'Marketing (MKT)'],
                ['id_tipo_nota'=>'4','tipo_nombre' => 'Poker (PK)'],
            ]);

            $anio = date('Y');
        }catch(Exception $e){
            Log::error('Error al obtener los datos: '.$e->getMessage()); 

            $categorias = array_map(function($item){
                return (object) $item;
            }, [
                ['idcategoria' => 1, 'categoria' => 'Diseño'], 
                ['idcategoria' => 2, 'categoria' => 'Pautas'], 
                ['idcategoria' => 3, 'categoria' => 'Contratos'],
                ['idcategoria' => 4, 'categoria' => 'Torneo'],
                ['idcategoria' => 5, 'categoria' => 'Torneo + Gráficas']
            ]);

            $tipos_evento = array_map(function($item){
                return (object) $item;
            }, [
                ['idtipoevento'=>1,'tipo_nombre'=>'Activaciones'],
                ['idtipoevento'=>2,'tipo_nombre'=>'Medios Masivos/Tradicionales'],
                ['idtipoevento'=>3,'tipo_nombre'=>'Medios Digitales'],
                ['idtipoevento'=>4,'tipo_nombre'=>'Promociones'],
                ['idtipoevento'=>5,'tipo_nombre'=>'Torneos'],
                ['idtipoevento'=>6,'tipo_nombre'=>'Via Publica'],
                ['idtipoevento'=>7,'tipo_nombre'=>'Contratos'],
            ]);
            $tipos_nota = array_map(function($item){
                return (object) $item;
            }, [
                ['id_tipo_nota'=>'1','tipo_nombre' => 'Comun'],
                ['id_tipo_nota'=>'2','tipo_nombre' => 'Publicidad (Bis)'],
                ['id_tipo_nota'=>'3','tipo_nombre' => 'Marketing (MKT)'],
                ['id_tipo_nota'=>'4','tipo_nombre' => 'Poker (PK)'],
            ]);
            $anio = date('Y');
        }
        return view('NotasCasino.indexNotasCasino',
         compact('categorias', 'tipos_evento','tipos_nota', 'anio'));
    }

    public function subirNota (Request $request){

        $validator = Validator::make($request->all(),[
            'nroNota' => 'required|integer',
            'tipoNota' => 'required|integer',
            'anioNota' => 'required|integer',
            'nombreEvento' => 'required|string|max:1000',
            'tipoEvento' => 'required|integer',
            'categoria' => 'required|integer',
            'adjuntoPautas' => 'nullable|file|mimes:pdf,zip|max:153600',
            'adjuntoDisenio' => 'nullable|file|mimes:pdf,zip|max:153600',
            'basesyCondiciones' => 'nullable|file|mimes:pdf,zip,doc,docx|max:153600',
            'fechaInicio' => 'required|date',
            'fechaFinalizacion' => 'required|date',
            'fechaReferencia' => 'nullable|string|max:500',
        ]);

        if($validator->fails()) {
            Log::info('Validación fallida', $validator->errors()->toArray());
            return response()->json($validator->errors(), 422);
        }

        // Armo la nota segun su tipo
        $nroNota = $request->input('nroNota');
        $tipoNota = $request->input('tipoNota');
        $anioNota = $request->input('anioNota');
        $resto = $anioNota % 1000;
        
        $formatos = [
            1 => "{$nroNota}-{$resto}",
            2 => "{$nroNota}-{$resto} Bis",
            3 => "MKT-{$nroNota}-{$resto}",
            4 => "PK-{$nroNota}-{$resto}"
        ];

        if (isset($formatos[$tipoNota])) {
            $nota = $formatos[$tipoNota];
        } else {
            $nota = '';
        }

        try{
        //verifico si el numero de nota es unico
        $existeNota = DB::connection('gestion_notas_mysql')->table('eventos')
            ->where('nronota_ev', $nota)
            ->exists();

        if($existeNota) {
            return response()->json(['success' => false, 'error' => 'El número de nota ya existe'], 422);
        }
        $casino = $this->USER->casinos->first();
        $origen = $this->obtenerCasino($casino);

        $responsable = null;
        switch($origen){
            case 1://CSF
                $responsable = 28;//santa fe
                break;
            case 2://CME
                $responsable = 29; //melincue
                break;
            case 3://CRO
                $responsable = 27; //rosario
                break;
            default:
                $responsable = 4; //usuario de Mecha
                break;
        }

        // obtnego los datos de la request
        $evento = $request->input('nombreEvento');
        $tipoEvento = $request->input('tipoEvento');
        $fechaInicio = $request->input('fechaInicio');
        $fechaFinalizacion = $request->input('fechaFinalizacion');
        $categoria = $request->input('categoria');
        $idEstado = 9; //carga inicial
        $fechaReferencia = null;

        if($request->has('fechaReferencia')) {
            $fechaReferencia = $request->input('fechaReferencia');
        }
        $archivos = [
            'adjuntoPautas' => 'Eventos_Pautas',
            'adjuntoDisenio' => 'Eventos_Diseño',
            'basesyCondiciones' => 'Eventos_byc',
        ];
        $pathsGuardados = [];
        foreach ($archivos as $input => $subcarpeta) {
            if ($request->hasFile($input)) {
                $archivo = $request->file($input);
                $nombreArchivo = $archivo->getClientOriginalName();
                // Guardar en el disco notas_casinos dentro de la subcarpeta correspondiente
                $rutaGuardada = Storage::disk('notas_casinos')->putFileAs(
                $subcarpeta,        // subcarpeta dentro del disco
                $archivo,           // archivo a guardar
                $nombreArchivo      // conservar el nombre original
                );

                $pathsGuardados[$input] = basename($rutaGuardada);
            }
        }

        DB::connection('gestion_notas_mysql')->table('eventos')->insert([
            'responsable' => $responsable,
            'nronota_ev' => $nota,
            'origen' => $origen,
            'evento' => $evento,
            'fecha_nota_recep' => null,
            'tipo_evento' => $tipoEvento,
            'fecha_evento' => $fechaInicio,
            'fecha_finalizacion' => $fechaFinalizacion,
            'estado_fecha' => null,
            'fecha_referencia_evento' => $fechaReferencia,
            'mes_referencia_evento' => null,
            'anio' => null,
            'adjunto_pautas' => isset($pathsGuardados['adjuntoPautas']) ? $pathsGuardados['adjuntoPautas'] : null,
            'adjunto_diseño' => isset($pathsGuardados['adjuntoDisenio']) ? $pathsGuardados['adjuntoDisenio'] : null,
            'adjunto_basesycond' => isset($pathsGuardados['basesyCondiciones']) ? $pathsGuardados['basesyCondiciones'] : null,
            'adjunto_inf_tecnico' => null,
            'idestado' => $idEstado,
            'idest_seg' => null,
            'observaciones' => null,
            'obs_seguim' => null,
            'fecha_orden' => null,
            'material_entrega' => null,
            'fecha_hora_reg' => null,
            'fecha_hora_modif' => null,
            'notas_relacionadas' => null,
            'idcategoria' => $categoria,
            'dircarpeta' => null
        ]);
    return response()->json(['success' => true],200);
        } catch (Exception $e) {
        Log::error($e);
        return response()->json(['success' => false, 'error' => $e->getMessage()],500);
        }
    }

    public function paginarNotas (Request $request){
        //me faltaria agregar los filtros para el order by
        $validator = Validator::make($request->all(),[
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:5|max:50',
            'nroNota' => 'nullable|string|max:50',
            'nombreEvento' => 'nullable|string|max:1000',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date',
        ]);
        if($validator->fails()){
            Log::info($validator->errors());
            return response()->json(['success' => false, 'error' => $validator->errors()],400);
        }
        
        $pagina = $request->input('page',1);
        $porPagina = $request->input('perPage',5);
        $nroNota = $request->input('nroNota');
        $nombreEvento = $request->input('nombreEvento');
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');
        $casino = $this->USER->casinos->first();
        $origen = $this->obtenerCasino($casino);
        try {
            $query = DB::connection('gestion_notas_mysql')
            ->table('eventos')
            ->join('estados', 'eventos.idestado', '=', 'estados.idestado')
            ->select(
                'eventos.idevento',
                'eventos.nronota_ev',
                'eventos.evento',
                'eventos.adjunto_pautas',
                'eventos.adjunto_diseño',
                'eventos.adjunto_basesycond',
                'eventos.fecha_evento',
                'eventos.fecha_finalizacion',
                'estados.estado',
                'eventos.notas_relacionadas'
            )
            ->where('eventos.origen', $origen);

            if($nroNota) {
                $query->where('eventos.nronota_ev', 'like', "%$nroNota%");
            }

            if($nombreEvento) {
                $query->where('eventos.evento', 'like', "%$nombreEvento%");
            }

            if ($fechaInicio && $fechaFin) {
                $query->whereBetween('eventos.fecha_evento', [$fechaInicio, $fechaFin]);
            } elseif ($fechaInicio) {
                $query->whereDate('eventos.fecha_evento', '>=', $fechaInicio);
            } elseif ($fechaFin) {
                $query->whereDate('eventos.fecha_evento', '<=', $fechaFin);
            }

            $notasActuales = $query
            ->orderBy('eventos.idevento', 'desc')
            ->paginate($porPagina, ['*'], 'page', $pagina);

            //encrypto
            $data = collect($notasActuales->items())->map(function ($nota) {
                $nota->idevento_enc = Crypt::encryptString($nota->idevento);
                return $nota;
            });


            return response()->json([
                'current_page' => $notasActuales->currentPage(),
                'per_page' => $notasActuales->perPage(),
                'total' => $notasActuales->total(),
                'data' => $data
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['success' => false, 'error' => $e->getMessage()],500);
        }
    }

    public function descargarArchivo ($id, $tipo){
         try {
            $idReal = Crypt::decryptString($id);
        } catch (Exception $e) {
            abort(404, 'ID inválido');
        }

        $validator = Validator::make([
            'id' => $idReal,
            'tipo' => $tipo
        ], [
            'id' => 'required|integer',
            'tipo' => 'required|string|in:pautas,disenio,basesycond'
        ]);

        if ($validator->fails()) {
            abort(404);
        }

        try {
            $nota = DB::connection('gestion_notas_mysql')
                ->table('eventos')
                ->where('idevento', $idReal)
                ->first();

            if (!$nota) {
                abort(404);
            }
            $rutaArchivo = null;
            $nombreArchivo = null;
            switch ($tipo) {
                case 'pautas':
                    $rutaArchivo = 'Eventos_Pautas/'.$nota->adjunto_pautas;
                    $nombreArchivo = $nota->adjunto_pautas;
                    break;
                case 'disenio':
                    $rutaArchivo = 'Eventos_Diseño/'.$nota->adjunto_diseño;
                    $nombreArchivo = $nota->adjunto_diseño;
                    break;
                case 'basesycond':
                    $rutaArchivo =  'Eventos_byc/'.$nota->adjunto_basesycond;
                    $nombreArchivo = $nota->adjunto_basesycond;
                    break;
                default:
                    abort(404);
            }

            if (empty($nombreArchivo)) {
                abort(404, 'El archivo no está cargado.');
            }

            if(!Storage::disk('notas_casinos')->exists($rutaArchivo)) {
            abort(404);
            }

            $rutaCompleta = Storage::disk('notas_casinos')->path($rutaArchivo);
            $mime = mime_content_type($rutaCompleta);
        
            return response()->download($rutaCompleta, $nombreArchivo, [
                'Content-Type' => $mime,
            ]);
        } catch (Exception $th) {
            abort(404);
        }
    }

    private function obtenerCasino ($casino) {
        $idCasinos = [ 'SANTA-FE' => 1, 'MELINCUE' => 2, 'ROSARIO' => 3, ];
        $id = null; 
        if($casino->id_casino == 1){ $id = $idCasinos['MELINCUE']; } 
        if($casino->id_casino == 2){ $id = $idCasinos['SANTA-FE']; } 
        if($casino->id_casino == 3){ $id = $idCasinos['ROSARIO']; }
        return $id;
    }
}