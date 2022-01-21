<?php

namespace App\Http\Controllers\Autoexclusion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Dompdf\Dompdf;
use View;
use Validator;
use PDF;

use App\Autoexclusion\Autoexcluido;
use App\Autoexclusion\ContactoAE;
use App\Autoexclusion\EstadoAE;
use App\Autoexclusion\Encuesta;
use App\Autoexclusion\ImportacionAE;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Autoexclusion as AE;
use App\Casino;
use App\Plataforma;

class InformesAEController extends Controller
{
    private static $atributos = [];
    private static $instance;
    public static function getInstancia($actualizar = true){
      if (!isset(self::$instance)){
          self::$instance = new InformesAEController($actualizar);
      }
      return self::$instance;
    }

    public function __construct($actualizar = true){//Actualizar estados antes de cada request
      if($actualizar) AutoexclusionController::getInstancia(false)->actualizarVencidosRenovados();
    }

    public function todo(){
      UsuarioController::getInstancia()->agregarSeccionReciente('Listado Autoexcluidos' , 'informesAutoexcluidos');
      $juegos =  DB::table('ae_juego_preferido')->get();

      return view('Autoexclusion.informesAE', ['juegos' => $juegos,
                                                              'casinos' => Casino::all(),
                                                              'plataformas' => Plataforma::all(),
                                                              'estados_autoexclusion' => AE\NombreEstadoAutoexclusion::all(),
                                                              'frecuencias' => AE\FrecuenciaAsistenciaAE::all(),
                                                              'juegos' => AE\JuegoPreferidoAE::all()
                                                              ]);
    }


    public function buscarAutoexcluidos(Request $request){
      $reglas = Array();
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      if(!empty($request->casino)){
        $reglas[]=['ae_estado.id_casino','=',$request->casino];
      }
      if(!empty($request->plataforma)){
        $reglas[]=['ae_estado.id_plataforma','=',$request->plataforma];
      }

      if(!empty($request->estado)){
        $reglas[]=['ae_estado.id_nombre_estado','=',$request->estado];
      }

      if(!empty($request->apellido)){
        $reglas[]=['ae_datos.apellido','LIKE', '%' . $request->apellido . '%'];
      }

      if(!empty($request->dni)){
        $reglas[]=['ae_datos.nro_dni','=',$request->dni];
      }

      if(!is_null($request->genero)){
        $reglas[]=['ae_datos.id_sexo','=',$request->genero];
      }

      if(!empty($request->localidad)){
        $reglas[]=['ae_datos.nombre_localidad','LIKE', '%' . $request->localidad . '%'];
      }

      if(!empty($request->provincia)){
        $reglas[]=['ae_datos.nombre_provincia','LIKE', '%' . $request->provincia . '%'];
      }

      if(!empty($request->fecha_autoexclusion_desde)){
        $reglas[]=['ae_estado.fecha_ae','>=',$request->fecha_autoexclusion_desde];
      }

      if(!empty($request->fecha_autoexclusion_hasta)){
        $reglas[]=['ae_estado.fecha_ae','<=',$request->fecha_autoexclusion_hasta];
      }

      if(!empty($request->fecha_vencimiento_desde)){
        $reglas[]=['ae_estado.fecha_vencimiento','>=',$request->fecha_vencimiento_desde];
      }

      if(!empty($request->fecha_vencimiento_hasta)){
        $reglas[]=['ae_estado.fecha_vencimiento','<=',$request->fecha_vencimiento_hasta];
      }

      if(!empty($request->fecha_revocacion_desde)){
        $reglas[]=['ae_estado.fecha_revocacion_ae','>=',$request->fecha_revocacion_desde];
      }

      if(!empty($request->fecha_revocacion_hasta)){
        $reglas[]=['ae_estado.fecha_revocacion_ae','<=',$request->fecha_revocacion_hasta];
      }

      if(!empty($request->fecha_cierre_desde)){
        $reglas[]=['ae_estado.fecha_cierre_ae','>=',$request->fecha_cierre_desde];
      }

      if(!empty($request->fecha_cierre_hasta)){
        $reglas[]=['ae_estado.fecha_cierre_ae','<=',$request->fecha_cierre_hasta];
      }

      $sort_by = ['columna' => 'ae_datos.id_autoexcluido', 'orden' => 'desc'];
      if(!empty($request->sort_by)){
        $sort_by = $request->sort_by;
      }

      $resultados = DB::table('ae_datos')
        ->selectRaw('ae_datos.id_autoexcluido, ae_datos.nro_dni, ae_datos.apellido, 
                     ae_datos.nombres,ae_datos.nombre_localidad,ae_datos.nombre_provincia,
                     ae_estado.fecha_ae, ae_estado.fecha_renovacion, ae_estado.fecha_vencimiento,
                     ae_estado.fecha_revocacion_ae,ae_estado.fecha_cierre_ae,ae_estado.id_nombre_estado,
                     ae_estado.id_casino,ae_estado.id_plataforma,ae_nombre_estado.descripcion as estado,
                     IFNULL(casino.nombre,plataforma.nombre) as casino_plataforma')
        ->join('ae_estado' , 'ae_datos.id_autoexcluido' , '=', 'ae_estado.id_autoexcluido')
        ->leftjoin('casino','ae_estado.id_casino','=','casino.id_casino')
        ->leftjoin('plataforma','ae_estado.id_plataforma','=','plataforma.id_plataforma')
        ->join('ae_nombre_estado', 'ae_estado.id_nombre_estado', '=', 'ae_nombre_estado.id_nombre_estado')
        ->leftJoin('ae_encuesta', 'ae_datos.id_autoexcluido', '=', 'ae_encuesta.id_autoexcluido')
        ->when($sort_by,function($query) use ($sort_by){
                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                    })
        ->where($reglas)
        ->whereNull('ae_datos.deleted_at')->whereNull('ae_estado.deleted_at')->whereNull('ae_encuesta.deleted_at');

      if(!is_null($request->finalizo)){
          if($request->finalizo) $resultados = $resultados->whereNotNull('ae_estado.fecha_revocacion_ae');
          else                   $resultados = $resultados->whereNull('ae_estado.fecha_revocacion_ae');
      }

      if(!empty($request->dia_semanal)){
        $resultados = $resultados->whereRaw('WEEKDAY(ae_estado.fecha_ae) = ?',$request->dia_semanal - 1);
      }
      
      if(!empty($request->edad_desde) || !empty($request->edad_hasta)){
        $resultados = $resultados->whereNotNull('ae_datos.fecha_nacimiento');
      }
      if(!empty($request->edad_desde)){
        $resultados = $resultados->whereRaw('TIMESTAMPDIFF(YEAR, ae_datos.fecha_nacimiento, CURDATE()) >= ?',$request->edad_desde);
      }
      if(!empty($request->edad_hasta)){
        $resultados = $resultados->whereRaw('TIMESTAMPDIFF(YEAR, ae_datos.fecha_nacimiento, CURDATE()) <= ?',$request->edad_hasta);
      }

      $buscar_encuesta = true;
      if(count($request->hace_encuesta) > 0){
        //@HACK, hay algunos que "tienen" encuesta con todos los campos vacios... para eso hago este chequeo
        $columnas = array_filter(
          Schema::getColumnListing('ae_encuesta'),
          function($c){ return !in_array($c,['id_encuesta','id_autoexcluido','created_at','updated_at','deleted_at']); }
        );
        //Tiene encuesta y alguno de los campos no es nulo
        $where = 'ae_encuesta.id_encuesta IS NOT NULL and
                 (ae_encuesta.'.implode(' IS NOT NULL OR ae_encuesta.',$columnas).' IS NOT NULL)';
        if($request->hace_encuesta === '1'){
          $resultados = $resultados->whereRaw($where);
        }
        else if($request->hace_encuesta === '0'){
          //No tiene encuesta o tiene pero todos los campos son nulos
          $resultados = $resultados->whereRaw('NOT ('.$where.')');
          $buscar_encuesta = false;
        }
      }

      if($buscar_encuesta){
        if($request->frecuencia == 4)
          $resultados = $resultados->where(function ($q){
            return $q->whereNull('ae_encuesta.id_frecuencia_asistencia')->orWhere('ae_encuesta.id_frecuencia_asistencia','=',4);
          });
        else if(isset($request->frecuencia))
          $resultados = $resultados->where('ae_encuesta.id_frecuencia_asistencia','=',$request->frecuencia);

        if($request->juego == 8)
          $resultados = $resultados->where(function ($q){
            return $q->whereNull('ae_encuesta.id_juego_preferido')->orWhere('ae_encuesta.id_juego_preferido','=',8);
          });
        else if(isset($request->juego))
          $resultados = $resultados->where('ae_encuesta.id_juego_preferido','=',$request->juego);

        if(!is_array($request->veces)) $resultados = $resultados->whereNull('ae_encuesta.veces');
        else{
          if(!is_null($request->veces[0])) $resultados = $resultados->where('ae_encuesta.veces','>=',$request->veces[0]);
          if(!is_null($request->veces[1])) $resultados = $resultados->where('ae_encuesta.veces','<=',$request->veces[1]);
        }

        if(!is_array($request->horas)) $resultados->whereNull('ae_encuesta.tiempo_jugado');
        else{
          if(!is_null($request->horas[0])) $resultados = $resultados->where('ae_encuesta.tiempo_jugado','>=',$request->horas[0]);
          if(!is_null($request->horas[1])) $resultados = $resultados->where('ae_encuesta.tiempo_jugado','<=',$request->horas[1]);
        }

        if($request->compania == -1)
          $resultados = $resultados->whereNull('ae_encuesta.como_asiste');
        else if(isset($request->compania))
          $resultados = $resultados->where('ae_encuesta.como_asiste','=',$request->compania);

        if($request->juego_responsable == -1)
          $resultados = $resultados->whereNull('ae_encuesta.juego_responsable');
        else if(isset($request->juego_responsable))
          $resultados = $resultados->where('ae_encuesta.juego_responsable','=',$request->juego_responsable);

        if($request->club == -1)
          $resultados = $resultados->whereNull('ae_encuesta.club_jugadores');
        else if(isset($request->club))
          $resultados = $resultados->where('ae_encuesta.club_jugadores','=',$request->club);

        if($request->autocontrol == -1)
          $resultados = $resultados->whereNull('ae_encuesta.autocontrol_juego');
        else if(isset($request->autocontrol))
          $resultados = $resultados->where('ae_encuesta.autocontrol_juego','=',$request->autocontrol);
        
        if($request->recibir_info == -1)
          $resultados = $resultados->whereNull('ae_encuesta.recibir_informacion');
        else if(isset($request->recibir_info))
          $resultados = $resultados->where('ae_encuesta.recibir_informacion','=',$request->recibir_info);

        if($request->medio == -1)
          $resultados = $resultados->where(function ($q){
            return $q->whereNull('ae_encuesta.medio_recibir_informacion')
              ->orWhere('ae_encuesta.medio_recibir_informacion','LIKE','NO %')
              ->orWhere('ae_encuesta.medio_recibir_informacion','=','-')
              ->orWhere('ae_encuesta.medio_recibir_informacion','=','--')
              ->orWhere('ae_encuesta.medio_recibir_informacion','=','---');
          });
        else if($request->medio == 'TELEFONO')
          $resultados = $resultados->where(function ($q){
            return $q->where('ae_encuesta.medio_recibir_informacion','LIKE','tel%')
              ->orWhere('ae_encuesta.medio_recibir_informacion','LIKE','%telé%')
              ->orWhereRaw('ae_encuesta.medio_recibir_informacion REGEXP \'[0-9]+\' AND ae_encuesta.medio_recibir_informacion NOT LIKE \'%@%\'');
        });
        else if($request->medio == 'CORREO')
        $resultados = $resultados->where(function ($q){
          return $q->where('ae_encuesta.medio_recibir_informacion','LIKE','%correo%')
            ->orWhere('ae_encuesta.medio_recibir_informacion','LIKE','%mail%')
            ->orWhere('ae_encuesta.medio_recibir_informacion','LIKE','%@%');
        });
        else if($request->medio == 'OTRO')
          $resultados = $resultados->where(function ($q){
            return $q->whereNotNull('ae_encuesta.medio_recibir_informacion')
              ->where('ae_encuesta.medio_recibir_informacion','NOT LIKE','NO %')
              ->where('ae_encuesta.medio_recibir_informacion','<>','-')
              ->where('ae_encuesta.medio_recibir_informacion','<>','--')
              ->where('ae_encuesta.medio_recibir_informacion','<>','--')
              ->where('ae_encuesta.medio_recibir_informacion','NOT LIKE','tel%')
              ->where('ae_encuesta.medio_recibir_informacion','NOT LIKE','%telé%')
              ->where('ae_encuesta.medio_recibir_informacion','NOT LIKE','%correo%')
              ->where('ae_encuesta.medio_recibir_informacion','NOT LIKE','%mail%')
              ->where('ae_encuesta.medio_recibir_informacion','NOT LIKE','%@%')
              ->whereRaw('ae_encuesta.medio_recibir_informacion NOT REGEXP \'[0-9]+\'');
          });
      }

      $resultados = $resultados->paginate($request->page_size);

      $resultados->getCollection()->transform(function ($row){
        $ae = AE\Autoexcluido::find($row->id_autoexcluido);
        $row->puede = AE\NombreEstadoAutoexclusion::find($ae->estado_transicionable)->descripcion;
        $row->es_primer_ae = $ae->es_primer_ae;
        return $row;
      });
      return $resultados;
    }
}
