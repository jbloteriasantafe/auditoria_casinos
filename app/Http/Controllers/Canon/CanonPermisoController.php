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
    DB::unprepared("DROP TABLE IF EXISTS canon_permiso");
    DB::unprepared("DROP TABLE IF EXISTS canon_permiso_usuario");
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
      CONSTRAINT `fk_canon_permiso_usuario_id_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`),
      CONSTRAINT `fk_canon_permiso_usuario_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id_usuario`),
      CONSTRAINT `fk_canon_permiso_usuario_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `usuario` (`id_usuario`)
    )
    ");
  }
  
  private function map_permisos(){
    return [
      'canon_ver' => [
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
      ]
    ];
  }
  
  public function llenado_inicial($created_at,$created_by){
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
    
    foreach($usuarios_bd as $u){
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
    
    return DB::table('canon_permiso as cp')
    ->select('cp.descripcion as permiso','cpu.id_operador')
    ->join('canon_permiso_usuario as cpu','cpu.id_canon_permiso','=','cp.id_canon_permiso')
    ->whereIn('cp.descripcion',$permisos)
    ->where('cpu.id_usuario',$id_usuario)
    ->whereNull('cpu.deleted_at')->get()->groupBy('permiso')->map(function($ops){
      return $ops->pluck('id_operador');
    });
  }
}
