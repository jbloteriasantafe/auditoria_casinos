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
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
      $notas = array();
      foreach($usuario['usuario']->casinos as $casino){
        $auxiliar=DB::table('nota')
                      ->join('expediente' , 'expediente.id_expediente' ,'=' , 'nota.id_expediente')
                      ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
                      ->join('casino', 'expediente_tiene_casino.id_casino', '=', 'casino.id_casino')
                      ->where('casino.id_casino' , '=' ,$casino->id_casino)
                      ->get()
                      ->toArray();
          $notas=array_merge($notas,$auxiliar);
      }
      $casinos=Casino::all();
      return view('seccionNotas' , ['notas' => $notas , 'casinos' => $casinos]);
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

    //crear log movimiento unicamente si no es reingreso

    if(!empty($request['id_tipo_movimiento']) || $request['id_tipo_movimiento']!= 0 ){
      if($request['id_tipo_movimiento'] != 3){//3=REINGRESO
          $log = LogMovimientoController::getInstancia()->guardarLogMovimientoExpediente($id_expediente,$request['id_tipo_movimiento']);
      }else{//es REINGRESO
          $log = LogMovimientoController::getInstancia()->generarReingreso($id_expediente);
      }
      $nota->log_movimiento()->associate($log->id_log_movimiento);
    }
    $nota->save();
  }

  public function guardarNotaConMovimiento($request, $id_expediente, $id_casino)// se usa desde expedienteController
  {

    $nota = new Nota;
    $nota->expediente()->associate($id_expediente);
    $nota->casino()->associate($id_casino); //asumiendo que los expedientes anuales son uno por casino copio el id_casino del expediente
    //$nota->tipo_movimiento()->associate($request['id_tipo_movimiento']);
    $nota->fecha = $request['fecha'];
    $nota->detalle = $request['detalle'];
    $nota->identificacion = $request['identificacion'];
    $nota->es_disposicion = 0;
    $nota->save();

    $nota->log_movimiento()->associate(intval($request['id_log_movimiento']));
    if($request['id_tipo_movimiento'] != 3){//3=REINGRESO
        $log = LogMovimientoController::getInstancia()->guardarLogMovimientoExpediente($id_expediente,$request['id_tipo_movimiento']);
    }else{//es REINGRESO
        $log = LogMovimientoController::getInstancia()->generarReingreso($id_expediente);
    }
    $nota->log_movimiento()->associate($log->id_log_movimiento);
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



    $idl = LogMovimientoController::getInstancia()->guardarLogMovimientoExpediente($id_expediente,$id_tipo_movimiento);
    $nota->log_movimiento()->associate(intval($idl->id_log_movimiento));
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

/*

    $controlador=DB::table('casino')
                  ->join('usuario_tiene_casino' , 'casino.id_casino', '=', 'usuario_tiene_casino.id_casino')
                  ->join('usuario', 'usuario.id_usuario','=', 'usuario_tiene_casino.id_usuario')
                  ->join('usuario_tiene_rol', 'usuario_tiene_rol.id_usuario', '=', 'usuario.id_usuario' )
                  ->join('rol', 'rol.id_rol','=', 'usuario_tiene_rol.id_rol')
                  ->where('casino.id_casino' ,'=', $request['id_casino'])
                  ->where('rol.descripcion','LIKE','CONTROL')
                  ->get()
                  ->first();*/

    //crear log movimiento unicamente si no es reingreso
    // if($request['id_tipo_movimiento'] != 3){//3=REINGRESO
    //     LogMovimientoController::getInstancia()->guardarLogMovimientoNota($nota->id_nota );
    // }else{//es REINGRESO
    //     LogMovimientoController::getInstancia()->generarReingreso($request['id_expediente'], $nota->id_nota);
    // }

    return ['nota' => $nota ];
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
    $nota->expediente()->dissociate();
    $nota->maquinas()->detach();
    $nota = Nota::destroy($id);
    return ['nota' => $nota];

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
      $reglas[]=['nota.identificacion', '=' ,  $request->identificacion ];
    }


    if(empty($request->fecha)){
        $resultados=DB::table('expediente')
        ->select('nota.*','casino.*')
        ->join('nota', 'nota.id_expediente', '=', 'expediente.id_expediente')
        ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
        ->join('casino', 'expediente_tiene_casino.id_casino', '=', 'casino.id_casino')
        ->where($reglas)
        ->get();
    }else{
        $fecha=explode("-", $request->fecha);
        $resultados=DB::table('expediente')
        ->select('nota.*','casino.*')
        ->join('nota', 'nota.id_expediente', '=', 'expediente.id_expediente')
        ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
        ->join('casino', 'expediente_tiene_casino.id_casino', '=', 'casino.id_casino')
        ->where($reglas)
        ->whereYear('fecha_iniciacion' , '=' ,$fecha[0])
        ->whereMonth('fecha_iniciacion','=', $fecha[1])
        ->get();
      }
        return ['resultados' => $resultados];
  }

}
