<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Expediente;
use App\Resolucion;
use App\Disposicion;
use App\Casino;

class ResolucionController extends Controller
{
  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new ResolucionController();
      }
      return self::$instance;
  }

  public function buscarTodoResoluciones(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
    $resoluciones=array();
    foreach($usuario['usuario']->casinos as $casino){
      $auxiliar=DB::table('resolucion')->join('expediente' , 'expediente.id_expediente' ,'=' , 'resolucion.id_expediente')->join('casino', 'casino.id_casino', '=' , 'expediente.id_casino')->where('casino.id_casino' , '=' ,$casino->id_casino)->get()->toArray();
        $resoluciones=array_merge($resoluciones,$auxiliar);
    }
    $casinos=Casino::all();
    UsuarioController::getInstancia()->agregarSeccionReciente('Resoluciones' , 'resoluciones');

    return view('seccionResoluciones' , ['resoluciones' => $resoluciones , 'casinos' => $casinos]);
  }

  public function buscarResolucion(Request $request){
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
      $reglas[]=['expediente.id_casino', '=' , $request->casino ];
    }
    if(!empty($request->nro_resolucion)){
      $reglas[]=['resolucion.nro_resolucion', 'like' ,'%' . $request->nro_resolucion . '%'];
    }
    if(!empty($request->nro_resolucion_anio)){
      $reglas[]=['resolucion.nro_resolucion_anio', 'like' , '%' . $request->nro_resolucion_anio . '%'];
    }

      $resultados=DB::table('expediente')
      ->join('resolucion', 'resolucion.id_expediente' , '=' , 'expediente.id_expediente')
      ->join('casino', 'casino.id_casino' , '=' , 'expediente.id_casino')
      ->where($reglas)
      ->get();
        return ['resultados' => $resultados , 'dato' => $request->nro_exp_org];
  }

  public function guardarResolucion($res,$id_expediente){
    $resolucion = new Resolucion;
    $resolucion->nro_resolucion = $res['nro_resolucion'];
    $resolucion->nro_resolucion_anio = $res['nro_resolucion_anio'];
    $resolucion->expediente()->associate($id_expediente);
    $resolucion->save();
  }

  public function updateResolucion($res,$id_expediente){
    //primero sincronizo con los id
    $id_res=array();
    foreach($res as $r){
      if($r->id_resolucion!="-1"){
        array_push($id_res,$r->id_resolucion);
      }
 //TODO terminar




    }
  }


  public function eliminarResolucion($id){
    $resolucion = Resolucion::destroy($id);
    return ['resolucion' => $resolucion];
  }

}
