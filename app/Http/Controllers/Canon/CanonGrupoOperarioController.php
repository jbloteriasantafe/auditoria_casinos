<?php
namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class CanonGrupoOperarioController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
  
  private $CV = null;
  public function __construct(){
    self::$instance = $this;
    $this->CV = CanonValorPorDefectoController::getInstancia();
  }
  
  public function down(){
    DB::unprepared("DROP TABLE IF EXISTS canon_grupo_operario_operario");
    DB::unprepared("DROP TABLE IF EXISTS canon_grupo_operario");
  }
  
  public function up(){
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_grupo_operario (
      id_canon_grupo_operario INT NOT NULL AUTO_INCREMENT,
      id_grupo_operario INT NOT NULL,
      nombre VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, -- El COLLATE es para que sea mas detallista en la igualdad, 'a' <> 'A' y 'á' <> 'a'
      codigo VARCHAR(16) NOT NULL,
      abbr   VARCHAR(16) NOT NULL,
      color  VARCHAR(16) NOT NULL,
      created_at DATETIME NOT NULL,
      created_by INT NOT NULL,
      deleted_at DATETIME NULL,
      deleted_by INT NULL,
      id_grupo_operario_deleted_at VARCHAR(32) GENERATED ALWAYS AS (CONCAT_WS('|',id_grupo_operario,IFNULL(deleted_at,''))) STORED NOT NULL,
      PRIMARY KEY (id_canon_grupo_operario),
      UNIQUE KEY `unq_canon_grupo_operario_id_deleted` (id_grupo_operario_deleted_at),
      KEY `fk_canon_grupo_operario_created_by` (`created_by`),
      CONSTRAINT `fk_canon_grupo_operario_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id_usuario`),
      CONSTRAINT `fk_canon_grupo_operario_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `usuario` (`id_usuario`)
    )
    ");
    
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_grupo_operario_operario (
      id_canon_grupo_operario_operario INT NOT NULL AUTO_INCREMENT,
      id_canon_grupo_operario INT NOT NULL,
      id_grupo_operario INT NOT NULL,
      id_operario INT NOT NULL,
      PRIMARY KEY (id_canon_grupo_operario_operario),
      UNIQUE KEY `unq_canon_grupo_operario_operario` (id_canon_grupo_operario,id_operario),
      CONSTRAINT `fk_canon_grupo_operario_operario` FOREIGN KEY (`id_canon_grupo_operario`) REFERENCES `canon_grupo_operario` (`id_canon_grupo_operario`)
    )
    ");
  }
  
  public function generar_grupo_para_operario($id_operario){
    $o = CanonOperarioController::getInstancia()->_obtener($id_operario);
    return [
      'id_grupo_operario' => $id_operario,
      'nombre' => $o['nombre'],
      'codigo' => $o['codigo'],
      'abbr' => $o['abbr'],
      'color' => $o['color'],
      'operarios' => [
        [
          'id_operario' => $id_operario,
          'id_grupo_operario' => $id_operario
        ]
      ]
    ];
  }
    
  public function llenado_inicial(){
    return DB::transaction(function(){
      $this->up();
      $grupos_operarios = $this->CV->get('grupos_operarios_iniciales');
      $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      $ret = [];
      foreach($grupos_operarios as $g){
        $gg = array_key_exists('id_operario',$g)?
          $this->generar_grupo_para_operario($g['id_operario'])
        : $g;
        $ret[] = $this->_guardar($gg,$created_at,$created_by);
      }
      return $ret;
    });
  }
  
  public function buscar(Request $request){
    $eliminados = $request->eliminados ?? 0;
    
    $operarios = '(
      SELECT GROUP_CONCAT(COALESCE(co.codigo,cgoo.id_operario) ORDER BY cgoo.id_operario asc  SEPARATOR ", " ) 
      FROM canon_grupo_operario_operario as cgoo 
      LEFT JOIN canon_operario as co ON co.id_operario = cgoo.id_operario AND co.deleted_at IS NULL
      WHERE cgoo.id_canon_grupo_operario = cgo.id_canon_grupo_operario GROUP BY "constant"
    ) as operarios';
    return DB::table('canon_grupo_operario as cgo')
    ->select('cgo.*',DB::raw($operarios))
    ->where(DB::raw('(deleted_at IS NOT NULL)'),$eliminados)
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_grupo_operario cgo2 
      WHERE cgo2.deleted_at IS NULL 
      AND cgo2.id_grupo_operario = cgo.id_grupo_operario 
      AND cgo2.id_canon_grupo_operario <> cgo.id_canon_grupo_operario
      LIMIT 1
    ))'),1)
    //Es el ultimo
    ->where($eliminados ? 
      DB::raw('(NOT EXISTS (
        SELECT 1 
        FROM canon_grupo_operario cgo2 
        WHERE cgo2.deleted_at IS NOT NULL 
        AND cgo2.id_grupo_operario = cgo.id_grupo_operario 
        AND (
           cgo2.deleted_at > cgo.deleted_at
           OR (
             cgo2.deleted_at = cgo.deleted_at AND cgo2.id_canon_grupo_operario > cgo.id_canon_grupo_operario
           )
        )
        AND cgo2.id_canon_grupo_operario <> cgo.id_canon_grupo_operario
        LIMIT 1
      ))')
      : DB::raw('1'),
      1
    )
    ->orderBy('id_grupo_operario','desc')
    ->paginate($request->page_size ?? 10);
  }
  
  private function _obtener($id_grupo_operario){
    if($id_grupo_operario === null) return null;
    $cgo = DB::table('canon_grupo_operario')
    ->where('id_grupo_operario',$id_grupo_operario)
    ->whereNull('deleted_at')
    ->first();
    
    if($cgo === null) return null;
    
    $cgo = (array) $cgo;
    return $this->agregar_operarios($cgo);
  }
  
  public function obtener(Request $request){
    return $this->_obtener($request->id_grupo_operario ?? null);
  }
  
  public function obtenerConHistorial(Request $request){
    $ultimo = $this->_obtener($request->id_grupo_operario ?? null);
    if($ultimo === null) return ['historial' => []];
    
    $ultimo['historial'] = DB::table('canon_grupo_operario as cgo')
    ->select('u.user_name as usuario','cgo.*')
    ->where('cgo.id_grupo_operario',$ultimo['id_grupo_operario'])
    ->join('usuario as u','u.id_usuario','=','cgo.created_by')
    ->orderBy('cgo.created_at','desc')
    ->get()->map(function(&$cgo){
      $cgo = (array) $cgo;
      return $this->agregar_operarios($cgo);
    });
    
    return $ultimo;
  }
  
  private function agregar_operarios(&$cgo){
    if($cgo === null) return null;
    
    $cgo['operarios'] = DB::table('canon_grupo_operario_operario')
    ->where('id_canon_grupo_operario',$cgo['id_canon_grupo_operario'])
    ->get()->map(function($o){return (array)$o;})->toArray();
    return $cgo;
  }
  
  public function borrar(Request $request){
    $id_grupo_operario = $request->id_grupo_operario ?? null;
    if($id_grupo_operario === null) return 0;
    return DB::transaction(function() use ($id_grupo_operario){
      $deleted_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $deleted_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_grupo_operario')
      ->where('id_grupo_operario',$id_grupo_operario)
      ->whereNull('deleted_at')
      ->update(compact('deleted_at','deleted_by'));
      
      return 1;
    });
  }
  
  public function desborrar(Request $request){
    $id_grupo_operario = $request->id_grupo_operario ?? null;
    if($id_grupo_operario === null) return 0;
    $cgo = DB::table('canon_grupo_operario as cgo')
    ->where('cgo.id_grupo_operario',$id_grupo_operario)
    ->whereNotNull('cgo.deleted_at')
    //No existe uno vivo
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_grupo_operario cgo2 
      WHERE cgo2.deleted_at IS NULL 
      AND cgo2.id_grupo_operario = cgo.id_grupo_operario 
      AND cgo2.id_canon_grupo_operario <> cgo.id_canon_grupo_operario
      LIMIT 1
    ))'),1)
    //Es el ultimo
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_grupo_operario cgo2 
      WHERE cgo2.deleted_at IS NOT NULL 
      AND cgo2.id_grupo_operario = cgo.id_grupo_operario 
      AND (
         cgo2.deleted_at > cgo.deleted_at
         OR (
           cgo2.deleted_at = cgo.deleted_at AND cgo2.id_canon_grupo_operario > cgo.id_canon_grupo_operario
         )
      )
      AND cgo2.id_canon_grupo_operario <> cgo.id_canon_grupo_operario
      LIMIT 1
    ))'),1)
    ->first();
    
    if($cgo === null) return 0;
    $cgo = (array) $cgo;
    $cgo = $this->agregar_operarios($cgo);
    
    return DB::transaction(function() use ($cgo){
      $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      unset($cgo['id_canon_grupo_operario']);
      unset($cgo['created_at']);
      unset($cgo['created_by']);
      unset($cgo['deleted_at']);
      unset($cgo['deleted_by']);
      unset($cgo['id_grupo_operario_deleted_at']);
      foreach(($cgo['operarios'] ?? []) as $oidx => &$o){
        unset($cgo['operarios'][$oidx]['id_canon_grupo_operario_operario']);
      }
      
      return $this->_guardar((array) $cgo,$created_at,$created_by);
    });
  }
  
  private function _guardar(array $cgo,$created_at,$created_by){
    unset($cgo['id_canon_grupo_operario']);
    
    $operarios = $cgo['operarios'] ?? [];
    unset($cgo['operarios']);
        
    $cgo['created_at'] = $created_at;
    $cgo['created_by'] = $created_by;
    $id_canon_grupo_operario = DB::table('canon_grupo_operario')
    ->insertGetId($cgo);
    
    DB::table('canon_grupo_operario_operario')
    ->insert(
      array_map(function($o) use ($id_canon_grupo_operario,$cgo){
        $o = (array) $o;
        $o['id_canon_grupo_operario'] = $id_canon_grupo_operario;
        $o['id_grupo_operario'] = $cgo['id_grupo_operario'];
        return $o;
      },$operarios)
    );
          
    return $this->_obtener($cgo['id_grupo_operario']);
  }
  
  public function guardar(Request $request){
    $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
    $cgo = null;
    
    Validator::make($request->all(),[
      'id_canon_grupo_operario' => ['nullable','integer','exists:canon_grupo_operario,id_canon_grupo_operario,deleted_at,NULL'],
      'id_grupo_operario' => ['nullable','integer'],
      'nombre' => ['required','string','max:64'],
      'codigo' => ['required','string','max:16'],
      'abbr' => ['required','string','max:16'],
      'color' => ['required','string','max:16'],
      'operarios' => ['required','array','min:1'],
      'operarios.*.id_operario' => ['required','integer'] //@TODO: verificar existencia?
    ], [
      'required' => 'El valor es requerido',
      'integer' => 'Tiene que ser un número',
      'min' => 'Fuera de rango',
      'max' => 'Supera el limite de tamaño',
      'array' => 'Tiene que ser una lista',
      'exists' => 'No existe el valór para referenciar',
      'string' => 'Se requiere una cadena',
      'in' => 'No es un valor valido',
    ],[])->after(function($validator) use (&$cgo){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $id_canon_grupo_operario = $data['id_canon_grupo_operario'] ?? null;
      $id_grupo_operario = $data['id_grupo_operario'] ?? null;
      
      if($id_canon_grupo_operario === null && $id_grupo_operario !== null){
        $ya_existe = DB::table('canon_grupo_operario')
        ->where('id_grupo_operario',$id_grupo_operario)
        ->whereNull('deleted_at')
        ->count() > 0;
        if($ya_existe){
          return $validator->errors()->add('id_grupo_operario','Ya existe, ingresar otro o modificar el operario ya existente');
        }
      }
      else if($id_canon_grupo_operario === null && $id_grupo_operario === null){
        return $validator->errors()->add('id_grupo_operario','El valor es requerido');
      }
      else if($id_canon_grupo_operario !== null && $id_grupo_operario === null){
        return $validator->errors()->add('id_grupo_operario','El valor es requerido');
      }
      else if($id_canon_grupo_operario !== null && $id_grupo_operario !== null){
        $cgo = DB::table('canon_grupo_operario')
        ->where('id_canon_grupo_operario',$id_canon_grupo_operario)
        ->where('id_grupo_operario',$id_grupo_operario)
        ->whereNull('deleted_at')
        ->first();
        if($cgo === null){
          return $validator->errors()->add('id_grupo_operario','No existe esta instancia para editar intente refrescar la pagina');
        }
      }
      else{
        return $validator->errors()->add('id_grupo_operario','UNREACHABLE');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,&$nuevo,&$cgo,$created_at,$created_by){
      if($cgo !== null){ //Estoy modificando, tengo que invalidar el viejo
        DB::table('canon_grupo_operario')
        ->where('id_canon_grupo_operario',$cgo->id_canon_grupo_operario)
        ->where('id_grupo_operario',$cgo->id_grupo_operario)
        ->whereNull('deleted_at')
        ->update([
          'deleted_at' => $created_at,
          'deleted_by' => $created_by
        ]);
      }
      
      $data = $request->all();            
      return $this->_guardar($data,$created_at,$created_by);
    });
  }
}
