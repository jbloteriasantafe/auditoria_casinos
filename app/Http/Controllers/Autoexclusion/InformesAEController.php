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
use App\Autoexclusion as AE;
use App\Casino;

class InformesAEController extends Controller
{
    private static $atributos = [
    ];

    public function todo(){
      UsuarioController::getInstancia()->agregarSeccionReciente('Listado Autoexcluidos' , 'informesAutoexcluidos');
      $juegos =  DB::table('ae_juego_preferido')->get();

      return view('Autoexclusion.informesAE', ['juegos' => $juegos,
                                                              'casinos' => Casino::all(),
                                                              'estados_autoexclusion' => AE\NombreEstadoAutoexclusion::all(),
                                                              'frecuencias' => AE\FrecuenciaAsistenciaAE::all(),
                                                              'juegos' => AE\JuegoPreferidoAE::all()
                                                              ]);
    }


    public function buscarAutoexcluidos(Request $request){
      AutoexclusionController::getInstancia()->actualizarVencidosRenovados();
      $reglas = Array();
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      if(!empty($request->casino)){
        $reglas[]=['ae_estado.id_casino','=',$request->casino];
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

      if($request->sexo != null){
        $reglas[]=['ae_datos.id_sexo','=',$request->sexo];
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
        ->select('ae_datos.*', 'ae_estado.*', 'casino.codigo as casino', 'ae_nombre_estado.descripcion as estado')
        ->join('ae_estado' , 'ae_datos.id_autoexcluido' , '=', 'ae_estado.id_autoexcluido')
        ->join('casino', 'ae_estado.id_casino', '=', 'casino.id_casino')
        ->join('ae_nombre_estado', 'ae_estado.id_nombre_estado', '=', 'ae_nombre_estado.id_nombre_estado')
        ->leftJoin('ae_encuesta', 'ae_datos.id_autoexcluido', '=', 'ae_encuesta.id_autoexcluido')
        ->when($sort_by,function($query) use ($sort_by){
                        return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                    })
        ->where($reglas);

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
        if($request->hace_encuesta === '1'){
          $resultados = $resultados->whereNotNull('ae_encuesta.id_autoexcluido');
        }
        else if($request->hace_encuesta === '0'){
          $resultados = $resultados->whereNull('ae_encuesta.id_autoexcluido');
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
