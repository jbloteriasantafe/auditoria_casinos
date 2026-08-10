<?php
namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Validator;
use App\Usuario;
use App\Casino;

class CanonPermisoController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
  
  private $mocking = true;
  public function __construct(){
    self::$instance = $this;
    $this->mocking = !(Schema::hasTable('canon_permiso') && Schema::hasTable('canon_permiso_usuario'));
  }
  
  public function down(){
    DB::unprepared("DROP TABLE IF EXISTS canon_permiso_usuario");
    DB::unprepared("DROP TABLE IF EXISTS canon_permiso");
    CANON_STREAM_STR('CANON_PERMISO: DOWN');
  }
  
  public function up(){
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_permiso (
      id_canon_permiso INT NOT NULL AUTO_INCREMENT,
      descripcion VARCHAR(64) NOT NULL,
      PRIMARY KEY (id_canon_permiso),
      UNIQUE KEY unq_canon_permiso (descripcion)
    )
    ");
    
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_permiso_usuario (
      id_canon_permiso_usuario INT NOT NULL AUTO_INCREMENT,
      id_canon_permiso INT NOT NULL,
      id_usuario INT NOT NULL,
      id_operador INT NOT NULL,
      created_at DATETIME NOT NULL,
      created_by INT NOT NULL,
      deleted_at DATETIME NULL,
      deleted_by INT NULL,
      PRIMARY KEY (id_canon_permiso_usuario),
      UNIQUE KEY unq_canon_permiso_usuario (id_canon_permiso,id_usuario,id_operador,deleted_at),
      CONSTRAINT `fk_canon_permiso_usuario_canon_permiso` FOREIGN KEY (`id_canon_permiso`) REFERENCES `canon_permiso` (`id_canon_permiso`),
      CONSTRAINT `fk_canon_permiso_usuario_id_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`),
      CONSTRAINT `fk_canon_permiso_usuario_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id_usuario`),
      CONSTRAINT `fk_canon_permiso_usuario_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `usuario` (`id_usuario`)
    )
    ");
    CANON_STREAM_STR('CANON_PERMISO: UP');
  }
  
  private function map_permisos(){
    return [
      'canon_ver' => [
        'rol' => ['SUPERUSUARIO','CONTABLE','CONTABLE_DOCUMENTOS'],
        'permiso' => ['m_b_pagos']
      ],
      'canon_adjuntar' => [
        'rol' => ['SUPERUSUARIO','CONTABLE','CONTABLE_DOCUMENTOS'],
        'permiso' => ['m_b_pagos']
      ],
      'canon_cargar' => [
        'rol' => ['SUPERUSUARIO','CONTABLE'],
        'permiso' => ['m_a_pagos']
      ],
      'canon_eliminar' => [
        'rol' => ['SUPERUSUARIO','CONTABLE'],
        'permiso' => ['m_a_pagos']
      ],
      'canon_deseliminar' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_cuenta_ver' => [
        'rol' => ['SUPERUSUARIO','CONTABLE','CONTABLE_DOCUMENTOS'],
        'permiso' => ['m_b_pagos']
      ],
      'canon_cuenta_cargar' => [
        'rol' => ['SUPERUSUARIO','CONTABLE'],
        'permiso' => ['m_a_pagos']
      ],
      'canon_operador_ver' => [
        'rol' => ['SUPERUSUARIO','CONTABLE_DOCUMENTOS'],
        'permiso' => ['m_b_pagos']
      ],
      'canon_operador_cargar' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_operador_eliminar' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_operador_deseliminar' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_agrupamiento_ver' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_agrupamiento_cargar' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_agrupamiento_eliminar' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_agrupamiento_deseliminar' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_permiso_ver' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_permiso_cargar' => [
        'rol' => ['SUPERUSUARIO']
      ],
      'canon_permiso_eliminar' => [
        'rol' => ['SUPERUSUARIO']
      ]
    ];
  }
  
  public function llenado_inicial($created_at,$created_by){
    $this->up();
    $permisos = $this->map_permisos();
    
    {
      $i = ['descripcion' => null];
      foreach($permisos as $p => $_){
        $i['descripcion'] = $p;
        $permisos[$p]['id'] = DB::table('canon_permiso')->insertGetId($i);
      }
    }
    
    $usuarios_bd = \App\Usuario::all();
    $casinos_bd = \App\Casino::all()->pluck('id_casino');
    $operadores_bd = DB::table('canon_operador')->select('id_operador')->distinct()
    ->whereNull('deleted_at')->get()->pluck('id_operador');
    
    $i = [
      'id_canon_permiso' => null,
      'id_usuario'  => null,
      'id_operador' => null,
      'created_at' => $created_at,
      'created_by' => $created_by,
      'deleted_at' => null,
      'deleted_by' => null
    ];
    
    foreach($usuarios_bd as $uidx => $u){
      CANON_STREAM_STR('CANON_PERMISO: Usuario '.($uidx+1).'/'.count($usuarios_bd));
      $ps = $this->mocking_permisosIntersect($u->id_usuario,array_keys($permisos));
      $i['id_usuario'] = $u->id_usuario;
      
      foreach($ps as $p => $ids_operadores){
        $ops;
        if(count($ids_operadores) < $casinos_bd->count()){
          $ops = $ids_operadores;
        }
        else{//Si tiene todos los casinos le asigno todos los operadores
          $ops = $operadores_bd;
        }
        
        $i['id_canon_permiso'] = $permisos[$p]['id'];
        
        foreach($ops as $id_operador){
          $i['id_operador'] = $id_operador;
          DB::table('canon_permiso_usuario')
          ->insert($i);
        }
      }
    }
  }
  
  public function tienePermiso($id_usuario,$permiso){
    return count($this->permisosIntersect($id_usuario,[$permiso])) == 1;
  }
  public function tieneAlgunPermiso($id_usuario,array $permisos){
    return count($this->permisosIntersect($id_usuario,$permisos)) > 0;
  }
  public function tieneTodosPermisos($id_usuario,array $permisos){
    return count($this->permisosIntersect($id_usuario,$permisos)) == count($permisos);
  }
  
  private function mocking_permisosIntersect($id_usuario,array $permisos){
    $map_permisos = $this->map_permisos();
    
    $u = Usuario::find($id_usuario);
    $casinos_bd = \App\Casino::all();
    $casinos_u = $u->casinos->keyBy('id_casino');
    
    $ret = collect([]);
    foreach($permisos as $p){
      $pdata = $map_permisos[$p] ?? [];
      
      foreach($casinos_bd as $c){        
        $entry = new \stdClass();
        $entry->permiso = $p;
        $entry->id_operador = $c->id_casino;
        
        if($u->tieneRol('SUPERUSUARIO')){
          $ret->push($entry);
          continue;
        }
        
        if(!$casinos_u->has($c->id_casino)){
          continue;
        }
        
        foreach(($pdata['rol'] ?? []) as $urol){
          if($u->tieneRol($urol)){
            $ret->push($entry);
            continue 2;
          }
        }
        
        foreach(($pdata['permiso'] ?? []) as $upermiso){
          if($u->tienePermiso($upermiso)){
            $ret->push($entry);
            continue 2;
          }
        }
      }
    }
    
    return $ret->groupBy('permiso')->map(function($ops){
      return $ops->pluck('id_operador');
    });
  }
  
  public function permisosIntersect($id_usuario,array $permisos){
    if($this->mocking){
      return $this->mocking_permisosIntersect($id_usuario,$permisos);
    }
    else{
      if(Usuario::find($id_usuario)->tieneRol('SUPERUSUARIO')){
        return DB::table('canon_permiso as cp')
        ->select('cp.descripcion as permiso','co.id_operador')
        ->crossJoin('canon_operador as co')
        ->whereIn('cp.descripcion',$permisos)
        ->whereNull('co.deleted_at')->get()->groupBy('permiso')->map(function($ops){
          return $ops->pluck('id_operador')->toArray();
        })->toArray();
      }
      else{
        return DB::table('canon_permiso as cp')
        ->select('cp.descripcion as permiso','cpu.id_operador')
        ->join('canon_permiso_usuario as cpu','cpu.id_canon_permiso','=','cp.id_canon_permiso')
        ->join('canon_operador as co','co.id_operador','=','cpu.id_operador')
        ->whereIn('cp.descripcion',$permisos)
        ->where('cpu.id_usuario',$id_usuario)
        ->whereNull('cpu.deleted_at')
        ->whereNull('co.deleted_at')
        ->get()->groupBy('permiso')->map(function($ops){
          return $ops->pluck('id_operador')->toArray();
        })->toArray();
      }
    }
  }
  
  public function buscar($paginate = true){
    $ret = collect([]);
    if($this->mocking){
      $ret = collect(array_keys($this->map_permisos()))->map(function($p){
        return (object)['id_canon_permiso' => null,'permiso' => $p];
      });
    }
    else{
      $ret = DB::table('canon_permiso as cp')
      ->select('cp.id_canon_permiso','cp.descripcion as permiso')
      ->get();
    }
    
    $permisos = $ret->count();
    
    if($paginate) {
      $page_size = request()->page_size ?? 10;
      $page = request()->page ?? 1;
            
      return [
        'current_page' => $page,
        'data' => $ret->slice(($page-1) * $page_size, $page_size)->values(),
        'from' => (($page-1)*$page_size+1),
        'to' => min($permisos,$page*$page_size),
        'last_page' => (intdiv($permisos,$page_size)+1),
        'next_page_url' => null,
        'path' => null,
        'prev_page_url' => null,
        'per_page' => $page_size,
        'total' => $permisos
      ];
    }
    else{
      return $ret;
    }
  }
  
  public function buscar_con_usuario_operador($paginate = true){
    if($this->mocking){
      return [];
    }

    $ret = DB::table('canon_permiso as cp')
    ->select('cpu.id_canon_permiso_usuario','cp.descripcion as permiso','cpu.id_operador','u.user_name')
    ->join('canon_permiso_usuario as cpu',function($j){
      return $j->on('cp.id_canon_permiso','=','cpu.id_canon_permiso')
      ->whereNull('cpu.deleted_at');
    })
    ->join('usuario as u',function($j){
      return $j->on('u.id_usuario','=','cpu.id_usuario')
      ->whereNull('u.deleted_at');
    });
    
    $reglas = [];
    if(isset(request()->permiso)){
      $reglas[] = ['cp.descripcion','LIKE',request()->permiso.'%'];
    }
    if(isset(request()->user_name)){
      $reglas[] = ['u.user_name','LIKE',request()->user_name.'%'];
    }
    if(isset(request()->id_operador)){
      $reglas[] = ['cpu.id_operador','=',request()->id_operador];
    }
    
    $ret = $ret->where($reglas);
    
    $totales = (clone $ret)->count();
    
    if($paginate) {
      $page_size = request()->page_size ?? 10;
      $page = request()->page ?? 1;
            
      return [
        'current_page' => $page,
        'data' => $ret->offset(($page-1) * $page_size)->limit($page_size)->get(),
        'from' => (($page-1)*$page_size+1),
        'to' => min($totales,$page*$page_size),
        'last_page' => (intdiv($totales,$page_size)+1),
        'next_page_url' => null,
        'path' => null,
        'prev_page_url' => null,
        'per_page' => $page_size,
        'total' => $totales
      ];
    }
    else{
      return $ret->get();
    }
  }
  
  public function ingresar(){
    if($this->mocking){
      return response()->json(['mensaje' => 'Mocking, realizar migración'],422);
    }
    
    
    if(empty(request()->permiso)){
      return response()->json(['permiso' => ['Valor Vacio']],422);
    }
    if(empty(request()->user_name)){
      return response()->json(['user_name' => ['Valor Vacio']],422);
    }
    if(empty(request()->id_operador)){
      return response()->json(['id_operador' => ['Valor Vacio']],422);
    }
    
    DB::beginTransaction();
    try{
      $canon_permiso = DB::table('canon_permiso')
      ->where('descripcion',request()->permiso)->first();
      if($canon_permiso === null){
        $id_canon_permiso = DB::table('canon_permiso')
        ->insertGetId(['descripcion' => request()->permiso]);
        $canon_permiso = DB::table('canon_permiso')
        ->where('id_canon_permiso',$id_canon_permiso)->first();
      }
      
      $user = DB::table('usuario')
      ->where('user_name',request()->user_name)
      ->whereNull('deleted_at')
      ->first();
      if($user === null){
        throw new \Exception('Usuario inexistente');
      }
      
      $created_at = date('Y-m-d h:i:s');
      $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $ret = DB::table('canon_permiso_usuario')
      ->insertGetId([
        'id_canon_permiso' => $canon_permiso->id_canon_permiso,
        'id_usuario' => $user->id_usuario,
        'id_operador' => request()->id_operador,
        'created_at' => $created_at,
        'created_by' => $created_by
      ]);
      DB::commit();
      return $ret;
    }
    catch(\Exception $e){
      DB::rollBack();
      return response()->json(['mensaje' => $e->getMessage()],422);
    }
  }
  
  public function borrar(){
    if(empty(request()->id_canon_permiso_usuario)){
      return response()->json(['mensaje' => 'Permiso Vacio'],422);
    }
    if($this->mocking){
      return response()->json(['mensaje' => 'Mocking, realizar migración'],422);
    }
    
    try{
      $deleted_at = date('Y-m-d h:i:s');
      $deleted_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_permiso_usuario')
      ->where('id_canon_permiso_usuario',request()->id_canon_permiso_usuario)
      ->update([
        'deleted_at' => $deleted_at,
        'deleted_by' => $deleted_by
      ]);
      
      return 1;
    }
    catch(\Exception $e){
      return response()->json(['mensaje' => $e->getMessage()],422);
    }
  }
  
  private $permisos_cache = null;
  public function permisos(){
    if($this->permisos_cache === null){
      if($this->mocking){
        $this->permisos_cache = array_keys($this->map_permisos());
      }
      else{
        $this->permisos_cache = DB::table('canon_permiso')
        ->select('descripcion')->distinct()
        ->get()->pluck('descripcion')->toArray();
      }
    }
    return $this->permisos_cache;
  }
  
  private $permisos_usuario_cache = [];
  public function permisos_usuario($id_usuario){
    if(!array_key_exists($id_usuario,$this->permisos_usuario_cache)){
      $permisos = $this->permisos();
      if($id_usuario === null){
        $permisos = array_map(function($p){return [];},array_flip($permisos));
      }
      else{
        $permisos = $this->permisosIntersect($id_usuario,$permisos);
      }
      $permisos = array_filter($permisos,function($ido){
        return !empty($ido);
      });
      $this->permisos_usuario_cache[$id_usuario] = $permisos;
    }
    return $this->permisos_usuario_cache[$id_usuario];
  }
  
  private $ids_operadores_usuario_cache = [];
  public function ids_operadores_usuario($id_usuario = null){
    if($id_usuario === null){
      return [];
    }  
    if(!array_key_exists($id_usuario,$this->ids_operadores_usuario_cache)){
      $ops = [];
      foreach($this->permisos_usuario($id_usuario) as $p => $idops){
        foreach($idops as $ido){
          $ops[$ido] = true;
        }
      }
      $this->ids_operadores_usuario_cache[$id_usuario] = array_keys($ops);
    }
    return $this->ids_operadores_usuario_cache[$id_usuario];
  }
}
