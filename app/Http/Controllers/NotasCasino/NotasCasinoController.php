<?php

namespace App\Http\Controllers\NotasCasino;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

//TODO: REFACTORIZAR EL LLEVAR ARCHIVOS PARA LEVANTAR LOS ARCHIVOS DE LA VM Y GENERAR LOS PDFS
//TODO: REFACTORIZAR EL GUARDADO DE ARCHIVOS PARA QUE SE GUARDEN EN LA VM 
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
            'adjuntoPautas' => 'nullable|file|mimes:pdf,zip,rar|max:153600',
            'adjuntoDisenio' => 'nullable|file|mimes:pdf,zip,rar|max:153600',
            'basesyCondiciones' => 'nullable|file|mimes:pdf,zip,rar|max:153600',
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

        // obtnego los datos de la request
        $evento = $request->input('nombreEvento');
        $tipoEvento = $request->input('tipoEvento');
        $fechaInicio = $request->input('fechaInicio');
        $fechaFinalizacion = $request->input('fechaFinalizacion');
        $categoria = $request->input('categoria');
        $responsable = 4; //! HAY QUE CREAR 3 USUARIOS 1 PARA CADA CASINO Y SETEAR ESTE VALOR SEGUN EL USUARIO
        $idEstado = 9; //carga inicial
        $adjuntoPautasPath = null;
        $adjuntoDisenioPath = null;
        $basesyCondicionesPath = null;
        $fechaReferencia = null;

        if($request->has('fechaReferencia')) {
            $fechaReferencia = $request->input('fechaReferencia');
        }

        //? GUARDAR EN LA TABLA EVENTOS,FORMATO:
        /* 
            idevento, -> autoincremental  //! AGREGAR
            responsable, -> null
            nronota_ev, -> si va//! AGREGAR
            origen, -> si va//! AGREGAR -> HARCODDEADO
            evento(nombre), -> si va //! AGREGAR -> NOMBRE EVENTO
            fecha_nota_recep, -> null 
            tipo_evento, -> si va //! AGREGAR
            fecha_evento(inicio),si va //! AGREGAR
            fecha_finalizacion,si va //! AGREGAR
            estado_fecha, null
            fecha_referencia_evento,si va //! AGREGAR
            mes_referencia_evento,null
            anio,null
            adjunto_pautas, si existe va el path | NULL //! AGREGAR
            adjunto_diseño, si existe va el path | NULL //! AGREGAR
            adjunto_basesycond, si existe va el path | NULL //! AGREGAR
            adjunto_inf_tecnico, NULL y sacar del form
            idestado, ESTADO 9 CARGA INICIAL //! AGREGAR -> HARCODEADO
            idest_seg, null
            observaciones, null 
            obs_segim,null
            fecha_orden, null
            material_entrega, null
            fecha_hora_reg, null
            fecha_hora_modif, null
            notas_relacionadas, null
            id_categoria, si va //! AGREGAR
            dircarpeta, null
        */
        
            //este guardado es momentaneo ( la idea es que via api se guarden en la otra pc mientras desarrollo los otros modulos )
            //cuando termine el desarrollo se implementara la logica para guardar local
            if ($request->hasFile('adjuntoPautas')) {
                $adjuntoPautasPath = $request->file('adjuntoPautas')->store('adjuntos');
            }

            if ($request->hasFile('adjuntoDisenio')) {
                $adjuntoDisenioPath = $request->file('adjuntoDisenio')->store('adjuntos');
            }

            if ($request->hasFile('basesyCondiciones')) {
                $basesyCondicionesPath = $request->file('basesyCondiciones')->store('adjuntos');
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
                'adjunto_pautas' => $adjuntoPautasPath,
                'adjunto_diseño' => $adjuntoDisenioPath,
                'adjunto_basesycond' => $basesyCondicionesPath,
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
        Log::info($casino->id_casino);
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

            return response()->json([
                'current_page' => $notasActuales->currentPage(),
                'per_page' => $notasActuales->perPage(),
                'total' => $notasActuales->total(),
                'data' => $notasActuales->items()
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['success' => false, 'error' => $e->getMessage()],500);
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