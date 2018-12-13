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
use Illuminate\Support\Facades\DB;

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
    foreach($usuario->casinos as $casino){
          $casinos [] = $casino->id_casino;
    }

    $reglas=array();

    if(isset($request->id_tipo_movimiento)){
      $reglas[]=['log_movimiento.id_tipo_movimiento','=', $request->id_tipo_movimiento];
    }

    if(isset($request->nro_admin) && $request->nro_admin != ""){
      $reglas[]=['relevamiento_movimiento.nro_admin','like', $request->nro_admin.'%'];
    }

    if(!isset($request->fecha)){
      $resultados = DB::table('fiscalizacion_movimiento')
                        ->select('fiscalizacion_movimiento.*','tipo_movimiento.*','casino.nombre')//,'estado_relevamiento.*'
                        ->join('log_movimiento','log_movimiento.id_log_movimiento','=', 'fiscalizacion_movimiento.id_log_movimiento')
                        ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
                        ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=', 'log_movimiento.id_tipo_movimiento')
                        ->join('relevamiento_movimiento','relevamiento_movimiento.id_fiscalizacion_movimiento','=','fiscalizacion_movimiento.id_fiscalizacion_movimiento')
                        ->whereIn('log_movimiento.id_casino',$casinos)
                        ->where('log_movimiento.id_expediente','<>','null')
                        ->where($reglas)
                        ->distinct('fiscalizacion_movimiento.id_fiscalizacion_movimiento','casino.id_casino','tipo_movimiento.id_tipo_movimiento')
                        ->orderBy('fiscalizacion_movimiento.fecha_envio_fiscalizar','desc')
                        ->take(25)
                        ->get();
    }else{
      $fecha=explode("-", $request->fecha);

      $resultados = DB::table('fiscalizacion_movimiento')
                        ->select('fiscalizacion_movimiento.*','tipo_movimiento.*','casino.nombre')//,'estado_relevamiento.*'
                        ->join('log_movimiento','log_movimiento.id_log_movimiento','=', 'fiscalizacion_movimiento.id_log_movimiento')
                        ->join('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=', 'log_movimiento.id_tipo_movimiento')
                        ->join('relevamiento_movimiento','relevamiento_movimiento.id_fiscalizacion_movimiento','=','fiscalizacion_movimiento.id_fiscalizacion_movimiento')
                        ->join('casino','casino.id_casino','=','log_movimiento.id_casino')
                        ->where('log_movimiento.id_expediente','<>','null')
                        ->whereIn('log_movimiento.id_casino',$casinos)
                        ->where($reglas)
                        ->whereYear('fiscalizacion_movimiento.fecha_envio_fiscalizar' , '=', $fecha[0])
                        ->whereMonth('fiscalizacion_movimiento.fecha_envio_fiscalizar','=', $fecha[1])
                        ->distinct('fiscalizacion_movimiento.id_fiscalizacion_movimiento','casino.id_casino','tipo_movimiento.id_tipo_movimiento')
                        ->orderBy('fiscalizacion_movimiento.fecha_envio_fiscalizar','desc')
                        ->take(25)
                        ->get();
    }

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
      $fiscalizacion->nota()->dissociate;
    }
    $fiscalizacion->destroy;
    return 1;
  }

}
