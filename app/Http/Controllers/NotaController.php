<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Expediente;
use App\Disposicion;
use App\Casino;
use App\Nota;
use Validator;

class NotaController extends Controller
{
  private static $atributos = [
    'id_expediente' => 'Expediente',
    'id_tipo_movimiento' => 'Tipo de Movimiento',
    'id_casino' => 'Casino',
    'fecha' => 'Fecha de creación de nota',
    'disposiciones' => 'Disposiciones',
    'disposiciones.*.nro_disposicion' => 'Nro Disposición',
    'disposiciones.*.nro_disposicion_anio' => 'Nro Disposición Año'
  ];
  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new NotaController();
      }
      return self::$instance;
  }

  public function buscarTodoNotas(){

      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = array();
      foreach($usuario->casinos as $casino){
        $casinos[] = $casino->id_casino;
      }
      $notas=DB::table('nota')
                      ->join('expediente' , 'expediente.id_expediente' ,'=' , 'nota.id_expediente')
                      ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
                      ->join('casino', 'expediente_tiene_casino.id_casino', '=', 'casino.id_casino')
                      ->leftJoin('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','nota.id_tipo_movimiento')
                      ->whereIn('casino.id_casino' ,$casinos)
                      ->where('es_disposicion','=',0)
                      ->orderBy('nota.identificacion','asc')
                      ->take(30)
                      ->get();

      $casinos=Casino::all();
      return view('seccionNotasExpediente' , ['notas' => $notas , 'casinos' => $casinos]);
  }

  public function guardarNota($request, $id_expediente, $id_casino)// se usa desde expedienteController
  {
    $nota = new Nota;
    $nota->expediente()->associate($id_expediente);
    $nota->casino()->associate($id_casino); //asumiendo que los expedientes anuales son uno por casino copio el id_casino del expediente
    //$nota->tipo_movimiento()->associate($request['id_tipo_movimiento']);
    $nota->es_disposicion = 0;
    $nota->fecha = $request['fecha'];
    $nota->detalle = $request['detalle'];
    $nota->identificacion = $request['identificacion'];
    $nota->save();

    if(!empty($request['id_tipo_movimiento']) || $request['id_tipo_movimiento']!= 0 ){
      if($request['id_tipo_movimiento'] != 3){//3=REINGRESO
          $nota->tipo_movimiento()->associate($request['id_tipo_movimiento']);
          $nota->save();
      }else{//es REINGRESO
          $nota->tipo_movimiento()->associate($request['id_tipo_movimiento']);
          $nota->save();
      }
    }
    $nota->save();
  }

  ///asociar nota con movimiento existente! no crearlos
  public function guardarNotaConMovimiento($request, $id_expediente, $id_casino)// se usa desde expedienteController
  {
    $log_id = intval($request['id_log_movimiento']);
    $nota = new Nota;

    $nota->fecha = $request['fecha'];
    $nota->detalle = $request['detalle'];
    $nota->identificacion = $request['identificacion'];
    $nota->es_disposicion = 0;
    $nota->save();
    $nota->log_movimiento()->associate($log_id);
    $nota->expediente()->associate($id_expediente);
    $nota->casino()->associate($id_casino); //asumiendo que los expedientes anuales son uno por casino copio el id_casino del expediente

    $logMov =LogMovimientoController::getInstancia()->asociarExpediente($log_id, $id_expediente);
    $nota->tipo_movimiento()->associate($logMov->id_tipo_movimiento);
    $nota->save();
  }

  //para no impactar en los movimientos-> se crea la disposicion pero en realidad
  //el movimiento esta asociado a una nota
  public function guardarNotaParaDisposicionConMov($id_expediente, $id_casino,$nro_disposicion,$id_tipo_movimiento)// se usa desde expedienteController
  {
    $nota = new Nota;
    $nota->expediente()->associate($id_expediente);
    $nota->casino()->associate($id_casino); //asumiendo que los expedientes anuales son uno por casino copio el id_casino del expediente
    $nota->tipo_movimiento()->associate($id_tipo_movimiento);
    $nota->fecha = date('Y-m-d');
    $nota->detalle = $nro_disposicion;
    $nota->identificacion = 'Disposición Nro '.$nro_disposicion;
    $nota->es_disposicion = 1;
    $nota->save();
    return $nota->id_nota;
  }

  //nunca se usa ja
  public function guardarNotaNueva($request)
  {
    Validator::make($request->all(), [
        'id_expediente' => 'required|exists:expediente,id_expediente',
        'id_casino' => 'required|exists:casino,id_casino',
        'id_tipo_movimiento' => 'required|exists:tipo_movimiento,id_tipo_movimiento',
        'fecha' => 'required|date',
        'disposiciones' => 'nullable',
        'disposiciones.*.nro_disposicion' => ['required','regex:/^\d\d\d$/'],
        'disposiciones.*.nro_disposicion_anio' => ['required','regex:/^\d\d$/']
    ], array(), self::$atributos)->after(function($validator){
      //ver que mensaje largar
      //$validator->errors()->add('detalles.['.$index.'].producido_calculado_relevado','El Producido Calculado Relevado debe estar presente si el estado es 3.');
    })->validate();

    $nota = new Nota;
    $nota->expediente()->associate($request['id_expediente']);
    $nota->casino()->associate($request['id_casino']); //asumiendo que los expedientes anuales son uno por casino copio el id_casino del expediente
    $nota->tipo_movimiento()->associate($request['id_tipo_movimiento']);
    $nota->fecha = $request['fecha'];
    $nota->save();
    if(!empty($request['disposiciones'])){
      foreach ($request['disposiciones'] as $disp){
        DisposicionController::getInstancia()->guardarDisposicionNota($disp,$nota->id_nota);
      }
    }
  }

  public function eliminarNota($id)
  {
    $nota = Nota::find($id);
    $disposiciones = $nota->disposiciones;
    if(!empty($disposiciones)){
      foreach($disposiciones as $disposicion){
        DisposicionController::getInstancia()->eliminarDisposicion($disposicion->id_disposicion);
      }
    }
    if(!is_null($nota->log_movimiento) && !is_null($nota->expediente)){
      LogMovimientoController::getInstancia()->disasociarExpediente($nota->log_movimiento->id_log_movimiento,$nota->expediente->id_expediente);
    }

    $nota->expediente()->dissociate();
    $nota->maquinas()->detach();
    $nota = Nota::destroy($id);
    return ['nota' => $nota];
  }

  public function consultaMovimientosNota($id_nota){
    $nota = Nota::findOrFail($id_nota);
    if($nota->id_tipo_movimiento != null){
      if(count($nota->log_movimiento->relevamientos_movimientos) > 0){
        return ['eliminable' => 0, 'nota' => $nota];
      }
    }
    return ['eliminable' => 1, 'nota' => $nota];
  }

  public function buscarNotas(Request $request){
    $reglas = array();
    if(!empty($request->nro_exp_org)){
      $reglas[]=['expediente.nro_exp_org' , 'like' ,'%' . $request->nro_exp_org . '%'];
    }
    if(!empty($request->nro_exp_interno)){
      $reglas[]=['expediente.nro_exp_interno', 'like' , '%' . $request->nro_exp_interno . '%'];
    }
    if(!empty($request->nro_exp_control)){
      $reglas[]=['expediente.nro_exp_control', 'like' ,'%' . $request->nro_exp_control .'%'];
    }
    if($request->casino!= 0){
      $reglas[]=['expediente.id_casino', '=' ,  $request->casino ];
    }

    if(!empty($request->identificacion)){
      $reglas[]=['nota.identificacion', 'like' ,  '%' . $request->identificacion.'%'];
    }


    if(empty($request->fecha)){
        $resultados=DB::table('expediente')
        ->join('nota', 'nota.id_expediente', '=', 'expediente.id_expediente')
        ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
        ->join('casino', 'expediente_tiene_casino.id_casino', '=', 'casino.id_casino')
        ->leftJoin('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','nota.id_tipo_movimiento')
        ->where('es_disposicion','=',0)
        ->orderBy('nota.identificacion','asc')
        ->where($reglas)
        ->take(50)
        ->get();
    }else{
        $fecha=explode("-", $request->fecha);
        $resultados=DB::table('expediente')
        ->join('nota', 'nota.id_expediente', '=', 'expediente.id_expediente')
        ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
        ->join('casino', 'expediente_tiene_casino.id_casino', '=', 'casino.id_casino')
        ->leftJoin('tipo_movimiento','tipo_movimiento.id_tipo_movimiento','=','nota.id_tipo_movimiento')
        ->where('es_disposicion','=',0)
        ->orderBy('nota.identificacion','asc')
        ->where($reglas)
        ->whereYear('fecha_iniciacion' , '=' ,$fecha[0])
        ->whereMonth('fecha_iniciacion','=', $fecha[1])

        ->take(50)
        ->get();
      }
        return ['resultados' => $resultados];
  }

  public function eliminarNotaCompleta($id_nota){
    $logMovsController = new LogMovimientoController();
    $nota = Nota::find($id_nota);
    if($nota->id_tipo_movimiento != null){
      $nota->tipo_movimiento()->dissociate();
      $log = $nota->log_movimiento();
      $nota->log_movimiento()->dissociate();
      $logMovsController->eliminarMovimientoExpediente($log->id_log_movimiento);
    }
    $nota->expediente()->dissociate();
    return 1;
  }

}
