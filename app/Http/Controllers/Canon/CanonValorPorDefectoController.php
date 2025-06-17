<?php

namespace App\Http\Controllers\Canon;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;

class CanonValorPorDefectoController extends Controller
{
  private static $instance;
  
  public static function getInstancia(){
    if(!isset(self::$instance)){
      self::$instance = new self();
    }
    return self::$instance;
  }
  
  public function valorPorDefecto($k,$dflt='{}'){
    $db = DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->where('campo',$k)
    ->first();
    
    $valoresDefecto_fallback = [
      'valores_confluir' => '{"1":{"valor_dolar":"1973.92","valor_euro":"2135.92"},"2":{"valor_dolar":"3287.21","valor_euro":"3215.91"},"3":{"valor_dolar":"2881.51","valor_euro":"2569.56"}}'
    ];
    
    $val = is_null($db)? null : preg_replace('/(\r\n|\n|\s\s+)/i','',$db->valor);
    $val = $val ?? $valoresDefecto_fallback[$k] ?? $dflt;
    
    return json_decode($val,true);
  }
    
  public function valoresPorDefecto(Request $request){
    return DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->orderBy('campo','asc')
    ->paginate($request->page_size);
  }
  
  public function valoresPorDefecto_ingresar(Request $request){    
    Validator::make($request->all(),[
      'campo' => ['required','string'],
      'valor' => ['required','string'],
    ], CanonController::$errores,[])->after(function($validator){
      if($validator->errors()->any()) return;
      json_decode($validator->getData()['valor']);
      if(json_last_error() !== JSON_ERROR_NONE){
        return $validator->errors()->add('valor','Error '.json_last_error_msg());
      }
    })->validate();
    
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $vals_viejos = DB::table('canon_valores_por_defecto')
      ->whereNull('deleted_at')
      ->where('campo',$request->campo)->get();
      foreach($vals_viejos as $v){
        $this->valoresPorDefecto_borrar_arr(['id_canon_valor_por_defecto' => $v->id_canon_valor_por_defecto],$created_at,$id_usuario);
      }
      
      DB::table('canon_valores_por_defecto')
      ->insert([
        'campo' => $request->campo,
        'valor' => $request->valor,
        'created_at' => $created_at,
        'deleted_at' => null,
        'created_id_usuario' => $id_usuario,
        'deleted_id_usuario' => null,
      ]);
      
      return 1;
    });
  }
  
  public function valoresPorDefecto_borrar(Request $request){
    return $this->valoresPorDefecto_borrar_arr($request->all());
  }
  
  private function valoresPorDefecto_borrar_arr(array $arr,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($arr,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_valores_por_defecto')
      ->whereNull('deleted_at')
      ->where('id_canon_valor_por_defecto',$arr['id_canon_valor_por_defecto'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
}
