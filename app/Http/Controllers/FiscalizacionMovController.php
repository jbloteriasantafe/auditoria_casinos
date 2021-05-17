<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Response;
use App\Archivo;
use App\RelevamientoMovimiento;
use App\FiscalizacionMov;
use App\TipoMovimiento;
use App\TomaRelevamientoMovimiento;
use App\LogMovimiento;
use App\Nota;
use App\Evento;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TomaRelevamientoMovimientoController;
use Validator;

class FiscalizacionMovController extends Controller
{
  private static $atributos = [

  ];

  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new FiscalizacionMovController();
      }
      return self::$instance;
  }

  public function buscarTodo()
  {
    return $fiscalizaciones = FiscalizacionMov::all();
  }

  public function crearFiscalizacion($id_log_movimiento,$es_reingreso, $fecha){
    $fiscalizacion = new FiscalizacionMov;
    if($es_reingreso == "true"){
      $fiscalizacion->es_reingreso = 1;
    }else{
      $fiscalizacion->es_reingreso = 0;
    }
    if($fecha == null || empty($fecha)) $fecha = date("Y-m-d");
    $fiscalizacion->fecha_envio_fiscalizar = $fecha; // fecha de hoy_ que seria la misma que la fecha_envio_fiscalizar_X del relevamiento
    $fiscalizacion->save();
    $fiscalizacion->log_movimiento()->associate($id_log_movimiento);
    $nota =  Nota::where('id_log_movimiento','=', $id_log_movimiento)->get()->first();
    if($nota != null){
      $fiscalizacion->identificacion_nota = $nota->identificacion;
    }else{
      $fiscalizacion->identificacion_nota = '---';
    }
    $fiscalizacion->estado_relevamiento()->associate(1);//generado
    $fiscalizacion->save();
    return $fiscalizacion;
  }

  public function buscarTomaEgreso($id_fiscalizacion_movimiento,$id_log_movimiento,$id_maquina){
    $relevamientos = RelevamientoMovimiento::where([['id_maquina','=',$id_maquina],['id_log_movimiento','=',$id_log_movimiento]])->get();
    $toma1=null;
    $id_relev=null;
    foreach ($relevamientos as $relev) {
      if($id_fiscalizacion_movimiento != $relev->id_fiscalizacion_movimiento){
        $id_relev =$relev->id_relev_mov;
        break;
      }
    }
    $toma1 = TomaRelevamientoMovimiento::where('id_relevamiento_movimiento','=',$id_relev)->get()->first();

    $toma = DB::table('toma_relev_mov')
                   ->select('toma_relev_mov.*','juego.nombre_juego')
                   ->join('juego','toma_relev_mov.juego','=', 'juego.id_juego')
                   ->where('toma_relev_mov.id_toma_relev_mov', '=', $toma1->id_toma_relev_mov)
                   ->get()
                   ->first();
    //ver como hacer para enviar todos los datos para la pantalla, nombre juego etc

    return $toma;
  }

  public function buscarFiscalizaciones(Request $request){
    $casinos= array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $es_controlador = $usuario->es_controlador;
    if(empty($request->id_casino)){
      foreach($usuario->casinos as $casino){
        $casinos[] = $casino->id_casino;
      }
    }
    else if($usuario->usuarioTieneCasino($request->id_casino)){
      $casinos[] = $request->id_casino;
    }

    $reglas=array();

    if(isset($request->id_tipo_movimiento)){
      $reglas[]=['tipo_movimiento.id_tipo_movimiento','=', $request->id_tipo_movimiento];
    }

    $sort_by = ['columna' => 'fiscalizacion_movimiento.id_fiscalizacion_movimiento', 'orden' => 'DESC'];
    if(!empty($request->sort_by) && !empty($request->sort_by['orden']) && !empty($request->sort_by['columna'])){
      $sort_by = $request->sort_by;
    }
    $page_size = 10;
    if(!empty($request->page_size)){
      $page_size = $request->page_size;
    }

    $resultados = DB::table('fiscalizacion_movimiento')
    ->select('fiscalizacion_movimiento.*','tipo_movimiento.*','casino.nombre','estado_relevamiento.descripcion as estado_descripcion')
    ->selectRaw("GROUP_CONCAT(DISTINCT(maquina.nro_admin) ORDER BY maquina.nro_admin ASC SEPARATOR ', ') as maquinas")
    ->join('log_movimiento','log_movimiento.id_log_movimiento','=', 'fiscalizacion_movimiento.id_log_movimiento')
    ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
    ->join('logmov_tipomov','log_movimiento.id_log_movimiento','=','logmov_tipomov.id_log_movimiento')
    ->join('tipo_movimiento','logmov_tipomov.id_tipo_movimiento','=', 'tipo_movimiento.id_tipo_movimiento')
    ->join('relevamiento_movimiento','relevamiento_movimiento.id_fiscalizacion_movimiento','=','fiscalizacion_movimiento.id_fiscalizacion_movimiento')
    ->join('maquina','maquina.id_maquina','=','relevamiento_movimiento.id_maquina')
    ->leftJoin('estado_relevamiento','estado_relevamiento.id_estado_relevamiento','=','fiscalizacion_movimiento.id_estado_relevamiento')
    ->whereIn('log_movimiento.id_casino',$casinos)
    ->whereNotNull('log_movimiento.id_expediente')
    ->where($reglas);

    if(isset($request->nro_admin) && $request->nro_admin != ""){
      $resultados = $resultados->whereRaw("CAST(maquina.nro_admin as CHAR) regexp ?",'^'.$request->nro_admin);
    }

    if(isset($request->fecha)){
      $fecha=explode("-", $request->fecha);
      $resultados = $resultados->whereYear('fiscalizacion_movimiento.fecha_envio_fiscalizar' , '=', $fecha[0])
                               ->whereMonth('fiscalizacion_movimiento.fecha_envio_fiscalizar','=', $fecha[1]);
    }

    if(isset($request->id_log_movimiento)){
      $resultados = $resultados->whereRaw("CAST(log_movimiento.id_log_movimiento as CHAR) regexp ?",'^'.$request->id_log_movimiento);
    }
    
    $resultados = $resultados->groupBy('fiscalizacion_movimiento.id_fiscalizacion_movimiento','casino.id_casino','tipo_movimiento.id_tipo_movimiento')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->paginate($request->page_size);

    $tiposMovimientos = TipoMovimiento::all();
    return ['fiscalizaciones' => $resultados ,'tipos_movimientos' => $tiposMovimientos, 'es_controlador' => $es_controlador];
  }

  public function eliminarFiscalizacion($id){
    $fiscalizacion = FiscalizacionMov::find($id);
    if(isset($fiscalizacion->relevamientos_movimientos)){
      foreach ($fiscalizacion->relevamientos_movimientos as $rel) {
        if(isset($rel->toma_relevamiento_movimiento)){
        $rel->toma_relevamiento_movimiento()->delete();
        }
        $rel->delete();
    }}
    if(isset($fiscalizacion->cargador)){
      $fiscalizacion->cargador()->dissociate();
      $fiscalizacion->fiscalizador()->dissociate();
    }
    if(isset($fiscalizacion->log_movimiento)){
      $fiscalizacion->log_movimiento()->dissociate();
    }
    $fiscalizacion->estado_relevamiento()->dissociate();
    if(isset($fiscalizacion->nota)){
      $fiscalizacion->nota()->dissociate();
    }
    $fiscalizacion->destroy();
    return 1;
  }
  public function eliminarFiscalizacionParcial($id){
    $fiscalizacion = FiscalizacionMov::find($id);
    DB::transaction(function() use ($fiscalizacion){
      if(isset($fiscalizacion->relevamientos_movimientos)){
        foreach($fiscalizacion->relevamientos_movimientos as $rel){
          $rel->id_fiscalizacion_movimiento = null;
          $rel->estado_relevamiento()->associate(1);
          $rel->save();
          foreach($rel->toma_relevamiento_movimiento as $toma){
            TomaRelevamientoMovimientoController::getInstancia()->limpiarToma($toma->id_toma_relev_mov);
          }
        }
      }
      Evento::where('id_fiscalizacion_movimiento',$fiscalizacion->id_fiscalizacion_movimiento)->delete();
      $log = $fiscalizacion->log_movimiento;
      $fiscalizacion->delete();
      if(!is_null($log)){//Nunca deberia pasar que sea null, pero lo agrego por las dudas
        //Si tiene fiscalizaciones FISCALIZANDO, si no MTM CARGADAS
        $estado = 7;//MTM cargadas
        if($log->fiscalizaciones()->count() > 0) $estado = 2;//FISCALIZANDO
        else if(!is_null($log->cant_maquinas) && $log->cant_maquinas > 0) $estado = 8;//CARGANDO
        $log->estado_movimiento()->associate($estado);
        $log->save();
      }
    });
    return 1;
  }

}
