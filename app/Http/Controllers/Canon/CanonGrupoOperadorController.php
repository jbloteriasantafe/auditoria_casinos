<?php
namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Facades\Schema;

class CanonGrupoOperadorController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
  
  private $CV = null;
  private $CAgg = null;
  private $mocking = true;
  public function __construct(){
    self::$instance = $this;
    $this->CV = CanonValorPorDefectoController::getInstancia();
    $this->CAgg = CanonAgrupamientoController::getInstancia();
    $this->mocking = !Schema::hasTable('canon_grupo_operador_operador');
    $this->mocking = $this->mocking || !Schema::hasTable('canon_grupo_operador_operador');
  }
  
  public function down(){
    DB::unprepared("DROP TABLE IF EXISTS canon_grupo_operador_operador");
    DB::unprepared("DROP TABLE IF EXISTS canon_grupo_operador");
    CANON_STREAM_STR('CANON_GRUPO_OPERADOR: DOWN');
    
    $this->CAgg->down();
  }
  
  public function up(){
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_grupo_operador (
      id_canon_grupo_operador INT NOT NULL AUTO_INCREMENT,
      id_grupo_operador INT NOT NULL,
      nombre VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, -- El COLLATE es para que sea mas detallista en la igualdad, 'a' <> 'A' y 'á' <> 'a'
      codigo VARCHAR(16) NOT NULL,
      abbr   VARCHAR(16) NOT NULL,
      color  VARCHAR(16) NOT NULL,
      created_at DATETIME NOT NULL,
      created_by INT NOT NULL,
      deleted_at DATETIME NULL,
      deleted_by INT NULL,
      es_individual TINYINT NOT NULL DEFAULT 0,
      id_grupo_operador_deleted_at VARCHAR(32) GENERATED ALWAYS AS (CONCAT_WS('|',id_grupo_operador,IFNULL(deleted_at,''))) STORED NOT NULL,
      PRIMARY KEY (id_canon_grupo_operador),
      UNIQUE KEY `unq_canon_grupo_operador_id_deleted` (id_grupo_operador_deleted_at),
      KEY `fk_canon_grupo_operador_created_by` (`created_by`),
      CONSTRAINT `fk_canon_grupo_operador_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id_usuario`),
      CONSTRAINT `fk_canon_grupo_operador_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `usuario` (`id_usuario`)
    )
    ");
    
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_grupo_operador_operador (
      id_canon_grupo_operador_operador INT NOT NULL AUTO_INCREMENT,
      id_canon_grupo_operador INT NOT NULL,
      id_grupo_operador INT NOT NULL,
      id_operador INT NOT NULL,
      PRIMARY KEY (id_canon_grupo_operador_operador),
      UNIQUE KEY `unq_canon_grupo_operador_operador` (id_canon_grupo_operador,id_operador),
      CONSTRAINT `fk_canon_grupo_operador_operador` FOREIGN KEY (`id_canon_grupo_operador`) REFERENCES `canon_grupo_operador` (`id_canon_grupo_operador`)
    )
    ");
    CANON_STREAM_STR('CANON_GRUPO_OPERADOR: UP');
    
  }
    
  public function llenado_inicial($created_at,$created_by){//Sin transaccion porque se llama desde CanonOperadorController con transaccion
    $this->up();
    $grupos_operadores = $this->CV->get('grupos_operadores_iniciales');
    $ret = [];
    foreach($grupos_operadores as $g){
      $ret[] = $this->_guardar($g,$created_at,$created_by);
      CANON_STREAM_STR('GRUPO OPERADOR: '.$g['id_grupo_operador']);
    }
    $this->CAgg->llenado_inicial($created_at,$created_by);
    return $ret;
  }
  
  public function buscar(Request $request){
    $eliminados = $request->eliminados ?? 0;
    
    $operadores = '(
      SELECT GROUP_CONCAT(COALESCE(co.codigo,cgoo.id_operador) ORDER BY cgoo.id_operador asc  SEPARATOR ", " ) 
      FROM canon_grupo_operador_operador as cgoo 
      LEFT JOIN canon_operador as co ON co.id_operador = cgoo.id_operador AND co.deleted_at IS NULL
      WHERE cgoo.id_canon_grupo_operador = cgo.id_canon_grupo_operador GROUP BY "constant"
    ) as operadores';
    return DB::table('canon_grupo_operador as cgo')
    ->select('cgo.*',DB::raw($operadores))
    ->where(DB::raw('(deleted_at IS NOT NULL)'),$eliminados)
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_grupo_operador cgo2 
      WHERE cgo2.deleted_at IS NULL 
      AND cgo2.id_grupo_operador = cgo.id_grupo_operador 
      AND cgo2.id_canon_grupo_operador <> cgo.id_canon_grupo_operador
      LIMIT 1
    ))'),1)
    //Es el ultimo
    ->where($eliminados ? 
      DB::raw('(NOT EXISTS (
        SELECT 1 
        FROM canon_grupo_operador cgo2 
        WHERE cgo2.deleted_at IS NOT NULL 
        AND cgo2.id_grupo_operador = cgo.id_grupo_operador 
        AND (
           cgo2.deleted_at > cgo.deleted_at
           OR (
             cgo2.deleted_at = cgo.deleted_at AND cgo2.id_canon_grupo_operador > cgo.id_canon_grupo_operador
           )
        )
        AND cgo2.id_canon_grupo_operador <> cgo.id_canon_grupo_operador
        LIMIT 1
      ))')
      : DB::raw('1'),
      1
    )
    ->orderBy('id_grupo_operador','desc')
    ->paginate($request->page_size ?? 10);
  }
  
  private function _obtener($id_grupo_operador){
    if($this->mocking || $id_grupo_operador === null) return null;
    $cgo = DB::table('canon_grupo_operador')
    ->where('id_grupo_operador',$id_grupo_operador)
    ->whereNull('deleted_at')
    ->first();
    
    if($cgo === null) return null;
    
    $cgo = (array) $cgo;
    return $this->agregar_operadores($cgo);
  }
  
  public function obtener(Request $request){
    return $this->_obtener($request->id_grupo_operador ?? null);
  }
  
  public function obtenerConHistorial(Request $request){
    $ultimo = $this->_obtener($request->id_grupo_operador ?? null);
    if($ultimo === null) return ['historial' => []];
    
    $ultimo['historial'] = DB::table('canon_grupo_operador as cgo')
    ->select('u.user_name as usuario','cgo.*')
    ->where('cgo.id_grupo_operador',$ultimo['id_grupo_operador'])
    ->join('usuario as u','u.id_usuario','=','cgo.created_by')
    ->orderBy('cgo.created_at','desc')
    ->get()->map(function(&$cgo){
      $cgo = (array) $cgo;
      return $this->agregar_operadores($cgo);
    });
    
    return $ultimo;
  }
  
  private function agregar_operadores(&$cgo){
    if($cgo === null) return null;
    
    $cgo['operadores'] = DB::table('canon_grupo_operador_operador')
    ->where('id_canon_grupo_operador',$cgo['id_canon_grupo_operador'])
    ->get()->map(function($o){return (array)$o;})->toArray();
    return $cgo;
  }
  
  public function borrar(Request $request){
    $id_grupo_operador = $request->id_grupo_operador ?? null;
    if($id_grupo_operador === null) return 0;
    return DB::transaction(function() use ($id_grupo_operador){
      $deleted_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $deleted_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_grupo_operador')
      ->where('id_grupo_operador',$id_grupo_operador)
      ->whereNull('deleted_at')
      ->update(compact('deleted_at','deleted_by'));
      
      return 1;
    });
  }
  
  public function desborrar(Request $request){
    $id_grupo_operador = $request->id_grupo_operador ?? null;
    if($id_grupo_operador === null) return 0;
    $cgo = DB::table('canon_grupo_operador as cgo')
    ->where('cgo.id_grupo_operador',$id_grupo_operador)
    ->whereNotNull('cgo.deleted_at')
    //No existe uno vivo
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_grupo_operador cgo2 
      WHERE cgo2.deleted_at IS NULL 
      AND cgo2.id_grupo_operador = cgo.id_grupo_operador 
      AND cgo2.id_canon_grupo_operador <> cgo.id_canon_grupo_operador
      LIMIT 1
    ))'),1)
    //Es el ultimo
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_grupo_operador cgo2 
      WHERE cgo2.deleted_at IS NOT NULL 
      AND cgo2.id_grupo_operador = cgo.id_grupo_operador 
      AND (
         cgo2.deleted_at > cgo.deleted_at
         OR (
           cgo2.deleted_at = cgo.deleted_at AND cgo2.id_canon_grupo_operador > cgo.id_canon_grupo_operador
         )
      )
      AND cgo2.id_canon_grupo_operador <> cgo.id_canon_grupo_operador
      LIMIT 1
    ))'),1)
    ->first();
    
    if($cgo === null) return 0;
    $cgo = (array) $cgo;
    $cgo = $this->agregar_operadores($cgo);
    
    return DB::transaction(function() use ($cgo){
      $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      unset($cgo['id_canon_grupo_operador']);
      unset($cgo['created_at']);
      unset($cgo['created_by']);
      unset($cgo['deleted_at']);
      unset($cgo['deleted_by']);
      unset($cgo['id_grupo_operador_deleted_at']);
      foreach(($cgo['operadores'] ?? []) as $oidx => &$o){
        unset($cgo['operadores'][$oidx]['id_canon_grupo_operador_operador']);
      }
      
      return $this->_guardar((array) $cgo,$created_at,$created_by);
    });
  }
  
  public function guardar_individual($id_operador,$created_at,$created_by){
    $o = CanonOperadorController::getInstancia()->_obtener($id_operador);
    $cgo = [
      'id_grupo_operador' => $id_operador,
      'nombre' => $o['nombre'],
      'codigo' => $o['codigo'],
      'abbr' => $o['abbr'],
      'color' => $o['color'],
      'es_individual' => 1,
      'operadores' => [
        [
          'id_operador' => $id_operador,
          'id_grupo_operador' => $id_operador
        ]
      ]
    ];
    
    $ret = $this->_guardar($cgo,$created_at,$created_by);
    
    return $ret;
  }
  
  public function borrar_individual($id_operador,$deleted_at,$deleted_by){
    return DB::table('canon_grupo_operador')
    ->where('id_grupo_operador',$id_operador)
    ->where('es_individual',1)
    ->whereNull('deleted_at')
    ->update(compact('deleted_at','deleted_by'));
  }
  
  public function desborrar_individual($id_operador,$created_at,$created_by){
    return $this->guardar_individual($id_operador,$created_at,$created_by);
  }
  
  private function _guardar(array $cgo,$created_at,$created_by){
    $this->invalidar_grupo($cgo['id_grupo_operador'],$created_at,$created_by);
    
    unset($cgo['id_canon_grupo_operador']);
    
    $operadores = $cgo['operadores'] ?? [];
    unset($cgo['operadores']);
        
    $cgo['created_at'] = $created_at;
    $cgo['created_by'] = $created_by;
    
    $id_canon_grupo_operador = DB::table('canon_grupo_operador')
    ->insertGetId($cgo);
    
    DB::table('canon_grupo_operador_operador')
    ->insert(
      array_map(function($o) use ($id_canon_grupo_operador,$cgo){
        $o = (array) $o;
        $o['id_canon_grupo_operador'] = $id_canon_grupo_operador;
        $o['id_grupo_operador'] = $cgo['id_grupo_operador'];
        return $o;
      },$operadores)
    );
          
    return $this->_obtener($cgo['id_grupo_operador']);
  }
  
  public function guardar(Request $request){
    $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
        
    Validator::make($request->all(),[
      'id_canon_grupo_operador' => ['nullable','integer','exists:canon_grupo_operador,id_canon_grupo_operador,deleted_at,NULL'],
      'id_grupo_operador' => ['nullable','integer'],
      'es_individual' => ['required','integer','in:0'],
      'nombre' => ['required','string','max:64'],
      'codigo' => ['required','string','max:16'],
      'abbr' => ['required','string','max:16'],
      'color' => ['required','string','max:16'],
      'operadores' => ['required','array','min:1'],
      'operadores.*.id_operador' => ['required','integer'] //@TODO: verificar existencia?
    ], [
      'required' => 'El valor es requerido',
      'integer' => 'Tiene que ser un número',
      'min' => 'Fuera de rango',
      'max' => 'Supera el limite de tamaño',
      'array' => 'Tiene que ser una lista',
      'exists' => 'No existe el valór para referenciar',
      'string' => 'Se requiere una cadena',
      'in' => 'No es un valor valido',
    ],[])->after(function($validator){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $id_canon_grupo_operador = $data['id_canon_grupo_operador'] ?? null;
      $id_grupo_operador = $data['id_grupo_operador'] ?? null;
      
      if($id_canon_grupo_operador === null && $id_grupo_operador !== null){
        $ya_existe = DB::table('canon_grupo_operador')
        ->where('id_grupo_operador',$id_grupo_operador)
        ->whereNull('deleted_at')
        ->count() > 0;
        if($ya_existe){
          return $validator->errors()->add('id_grupo_operador','Ya existe, ingresar otro o modificar el operador ya existente');
        }
      }
      else if($id_canon_grupo_operador === null && $id_grupo_operador === null){
        return $validator->errors()->add('id_grupo_operador','El valor es requerido');
      }
      else if($id_canon_grupo_operador !== null && $id_grupo_operador === null){
        return $validator->errors()->add('id_grupo_operador','El valor es requerido');
      }
      else if($id_canon_grupo_operador !== null && $id_grupo_operador !== null){
        $cgo = DB::table('canon_grupo_operador')
        ->where('id_canon_grupo_operador',$id_canon_grupo_operador)
        ->where('id_grupo_operador',$id_grupo_operador)
        ->whereNull('deleted_at')
        ->first();
        if($cgo === null){
          return $validator->errors()->add('id_grupo_operador','No existe esta instancia para editar intente refrescar la pagina');
        }
      }
      else{
        return $validator->errors()->add('id_grupo_operador','UNREACHABLE');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,&$nuevo,&$cgo,$created_at,$created_by){
      $data = $request->all();            
      return $this->_guardar($data,$created_at,$created_by);
    });
  }
  
  private function invalidar_grupo($id_grupo_operador,$deleted_at,$deleted_by){
    return DB::table('canon_grupo_operador')
    ->where('id_grupo_operador',$id_grupo_operador)
    ->whereNull('deleted_at')
    ->update([
      'deleted_at' => $deleted_at,
      'deleted_by' => $deleted_by
    ]);
  }
}
