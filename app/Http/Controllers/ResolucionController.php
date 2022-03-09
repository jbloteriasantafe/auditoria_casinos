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
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    UsuarioController::getInstancia()->agregarSeccionReciente('Resoluciones' , 'resoluciones');
    return view('seccionResoluciones' , ['casinos' => $usuario->casinos]);
  }

  public function buscarResolucion(Request $request){
    $usuario =  UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = [];
    foreach($usuario->casinos as $c) $cas[] = $c->id_casino;

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
      $reglas[]=['casino.id_casino', '=' , $request->casino];
    }
    if(!empty($request->nro_resolucion)){
      $reglas[]=['resolucion.nro_resolucion', 'like' ,'%' . $request->nro_resolucion . '%'];
    }
    if(!empty($request->nro_resolucion_anio)){
      $reglas[]=['resolucion.nro_resolucion_anio', 'like' , '%' . $request->nro_resolucion_anio . '%'];
    }

    $sort_by = $request->sort_by;
    $resultados=DB::table('expediente')
    ->join('resolucion', 'resolucion.id_expediente' , '=' , 'expediente.id_expediente')
    ->join('expediente_tiene_casino','expediente_tiene_casino.id_expediente','=','expediente.id_expediente')
    ->join('casino','casino.id_casino','=','expediente_tiene_casino.id_casino')
    ->whereIn('casino.id_casino',$cas)
    ->where($reglas)
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })->paginate($request->page_size);

    return ['resultados' => $resultados];
  }

  public function guardarResolucion($res,$id_expediente){
    $resolucion = new Resolucion;
    $resolucion->nro_resolucion      = $res['nro_resolucion'];
    $resolucion->nro_resolucion_anio = $res['nro_resolucion_anio'];
    $resolucion->expediente()->associate($id_expediente);
    $resolucion->save();
  }

  public function updateResolucion($res,$id_expediente){
    $res = $res ?? [];
    $actuales = [];
    $crear    = [];
    foreach($res as $r){
      if(!empty($r['id_resolucion'])){
        array_push($actuales,$r['id_resolucion']);
      }else{
        array_push($crear,$r);
      }
    }

    Resolucion::where('id_expediente',$id_expediente)
    ->whereNotIn('id_resolucion',$actuales)->delete();
    
    foreach($crear as $r){
      $this->guardarResolucion($r,$id_expediente);
    }
  }

  public function eliminarResolucion($id){
    $resolucion = Resolucion::destroy($id);
    return ['resolucion' => $resolucion];
  }
}
