<?php
namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class CanonOperarioController extends Controller
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
    DB::unprepared("DROP TABLE IF EXISTS canon_operario_cuenta");
    DB::unprepared("DROP TABLE IF EXISTS canon_operario");
  }
  
  public function up(){
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_operario (
      id_canon_operario INT NOT NULL AUTO_INCREMENT,
      id_operario INT NOT NULL, -- ID asignado, el mismo operario puede borrarse pero tenemos que seguirlo teniendo
      nombre_legal VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, -- Razon social
      nombre VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, -- El COLLATE es para que sea mas detallista en la igualdad, 'a' <> 'A' y 'á' <> 'a'
      codigo VARCHAR(16) NOT NULL,
      abbr   VARCHAR(16) NOT NULL, -- algunos informes usan otro código ('CME' en vez de 'MEL')
      cuit   VARCHAR(16) NOT NULL,
      color  VARCHAR(16) NOT NULL, -- #RRGGBBAA , para mantener consistencia entre informes
      codigo_casino VARCHAR(16) NULL, -- Casino vinculado si tiene, para buscar datos de beneficio
      codigo_plataforma VARCHAR(16) NULL, -- Plataforma vinculada si tiene, para buscar datos de beneficio
      codigo_apuestas_deportivas VARCHAR(16) NULL, -- Plataforma vinculada si tiene, para buscar datos de beneficio
      inicio_actividad DATE NOT NULL,
      created_at DATETIME NOT NULL,
      created_by INT NOT NULL,
      deleted_at DATETIME NULL,
      deleted_by INT NULL,
      id_operario_deleted_at VARCHAR(32) GENERATED ALWAYS AS (CONCAT_WS('|',id_operario,IFNULL(deleted_at,''))) STORED NOT NULL,
      PRIMARY KEY (id_canon_operario),
      UNIQUE KEY `unq_canon_operario_id_deleted` (id_operario_deleted_at),
      KEY `fk_canon_operario_created_by` (`created_by`),
      CONSTRAINT `fk_canon_operario_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id_usuario`),
      CONSTRAINT `fk_canon_operario_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `usuario` (`id_usuario`)
    )
    ");
    
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_operario_cuenta (
      id_canon_operario_cuenta INT NOT NULL AUTO_INCREMENT,
      id_canon_operario INT NOT NULL,
      nombre VARCHAR(64) NOT NULL,
      dia_vencimiento TINYINT NOT NULL,
      fin_de_semana ENUM('Lunes Próximo','Viernes Anterior','Sin Movimiento') NOT NULL,
      interes_diario_simple DECIMAL(7,4) NOT NULL,
      interes_mensual_compuesto DECIMAL(7,4) NOT NULL,
      PRIMARY KEY (id_canon_operario_cuenta),
      UNIQUE KEY `unq_canon_operario_cuenta` (id_canon_operario,nombre),
      CONSTRAINT `fk_canon_operario_cuenta_operario` FOREIGN KEY (`id_canon_operario`) REFERENCES `canon_operario` (`id_canon_operario`)
    )
    ");
  }
  
  public function llenado_inicial(){
    return DB::transaction(function(){
      $this->up();
      $operarios = $this->CV->get('operarios_iniciales');
      $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      $ret = [];
      foreach($operarios as $o){
        $ret[] = $this->_guardar($o,$created_at,$created_by);
      }
      return $ret;
    });
  }
  
  public function buscar(Request $request){
    $eliminados = $request->eliminados ?? 0;
    
    return DB::table('canon_operario as co')
    ->where(DB::raw('(deleted_at IS NOT NULL)'),$eliminados)
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_operario co2 
      WHERE co2.deleted_at IS NULL 
      AND co2.id_operario = co.id_operario 
      AND co2.id_canon_operario <> co.id_canon_operario
      LIMIT 1
    ))'),1)
    //Es el ultimo
    ->where($eliminados ? 
      DB::raw('(NOT EXISTS (
        SELECT 1 
        FROM canon_operario co2 
        WHERE co2.deleted_at IS NOT NULL 
        AND co2.id_operario = co.id_operario 
        AND (
           co2.deleted_at > co.deleted_at
           OR (
             co2.deleted_at = co.deleted_at AND co2.id_canon_operario > co.id_canon_operario
           )
        )
        AND co2.id_canon_operario <> co.id_canon_operario
        LIMIT 1
      ))')
      : DB::raw('1'),
      1
    )
    ->orderBy('id_operario','desc')
    ->paginate($request->page_size ?? 10);
  }
  
  public function _obtener($id_operario){
    if($id_operario === null) return null;
    $co = DB::table('canon_operario')
    ->where('id_operario',$id_operario)
    ->whereNull('deleted_at')
    ->first();
    
    if($co === null) return null;
    
    $co = (array) $co;
    return $this->agregar_cuentas($co);
  }
  
  public function obtener(Request $request){
    return $this->_obtener($request->id_operario ?? null);
  }
  
  public function obtenerConHistorial(Request $request){
    $ultimo = $this->_obtener($request->id_operario ?? null);
    if($ultimo === null) return ['historial' => []];
    
    $ultimo['historial'] = DB::table('canon_operario as co')
    ->select('u.user_name as usuario','co.*')
    ->where('co.id_operario',$ultimo['id_operario'])
    ->join('usuario as u','u.id_usuario','=','co.created_by')
    ->orderBy('co.created_at','desc')
    ->get()->map(function(&$co){
      $co = (array) $co;
      return $this->agregar_cuentas($co);
    });
    
    return $ultimo;
  }
  
  private function agregar_cuentas(&$co){
    if($co === null) return null;
    
    $co['cuentas'] = DB::table('canon_operario_cuenta')
    ->where('id_canon_operario',$co['id_canon_operario'])
    ->get()->toArray();
    return $co;
  }
  
  public function borrar(Request $request){
    $id_operario = $request->id_operario ?? null;
    if($id_operario === null) return 0;
    return DB::transaction(function() use ($id_operario){
      $deleted_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $deleted_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_operario')
      ->where('id_operario',$id_operario)
      ->whereNull('deleted_at')
      ->update(compact('deleted_at','deleted_by'));
      
      return 1;
    });
  }
  
  public function desborrar(Request $request){
    $id_operario = $request->id_operario ?? null;
    if($id_operario === null) return 0;
    $co = DB::table('canon_operario as co')
    ->where('co.id_operario',$id_operario)
    ->whereNotNull('co.deleted_at')
    //No existe uno vivo
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_operario co2 
      WHERE co2.deleted_at IS NULL 
      AND co2.id_operario = co.id_operario 
      AND co2.id_canon_operario <> co.id_canon_operario
      LIMIT 1
    ))'),1)
    //Es el ultimo
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_operario co2 
      WHERE co2.deleted_at IS NOT NULL 
      AND co2.id_operario = co.id_operario 
      AND (
         co2.deleted_at > co.deleted_at
         OR (
           co2.deleted_at = co.deleted_at AND co2.id_canon_operario > co.id_canon_operario
         )
      )
      AND co2.id_canon_operario <> co.id_canon_operario
      LIMIT 1
    ))'),1)
    ->first();
    
    if($co === null) return 0;
    $co = (array) $co;
    $co = $this->agregar_cuentas($co);
    
    return DB::transaction(function() use ($co){
      $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      unset($co['id_canon_operario']);
      unset($co['created_at']);
      unset($co['created_by']);
      unset($co['deleted_at']);
      unset($co['deleted_by']);
      unset($co['id_operario_deleted_at']);
      
      return $this->_guardar((array) $co,$created_at,$created_by);
    });
  }
  
  private function _guardar(array $co,$created_at,$created_by){
    unset($co['id_canon_operario']);
    
    $cuentas = $co['cuentas'] ?? [];
    unset($co['cuentas']);
    
    $co['created_at'] = $created_at;
    $co['created_by'] = $created_by;
    $id_canon_operario = DB::table('canon_operario')
    ->insertGetId($co);
    
    DB::table('canon_operario_cuenta')
    ->insert(
      array_map(function($c) use ($id_canon_operario){
        $c['id_canon_operario'] = $id_canon_operario;
        return $c;
      },$cuentas)
    );
      
    return $this->_obtener($co['id_operario']);
  }
  
  public function guardar(Request $request){
    $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
    $co = null;
      
    Validator::make($request->all(),[
      'id_canon_operario' => ['nullable','integer','exists:canon_operario,id_canon_operario,deleted_at,NULL'],
      'id_operario' => ['nullable','integer'],
      'nombre_legal' => ['required','string','max:64'],
      'nombre' => ['required','string','max:64'],
      'codigo' => ['required','string','max:16'],
      'cuit' => ['required','string','max:16'],
      'inicio_actividad' => ['required','date'],
      'abbr' => ['required','string','max:16'],
      'color' => ['required','string','max:16'],
      'codigo_casino' => ['nullable','string','max:16'],
      'codigo_plataforma' => ['nullable','string','max:16'],
      'codigo_apuestas_deportivas' => ['nullable','string','max:16'],
      'cuentas' => ['required','array','min:1'],
      'cuentas.*.nombre' => ['required','string','max:64'],
      'cuentas.*.dia_vencimiento' => ['required','integer','min:1','max:28'],
      'cuentas.*.fin_de_semana' => ['nullable','string','in:Lunes Próximo,Viernes Anterior,Sin Movimiento'],
      'cuentas.*.interes_diario_simple' => ['required','string','regex:/^\d{1,3}(\.\d{0,4})?$/'],
      'cuentas.*.interes_mensual_compuesto' => ['required','string','regex:/^\d{1,3}(\.\d{0,4})?$/']
    ], [
      'required' => 'El valor es requerido',
      'integer' => 'Tiene que ser un número',
      'min' => 'Fuera de rango',
      'max' => 'Supera el limite de tamaño',
      'array' => 'Tiene que ser una lista',
      'exists' => 'No existe el valór para referenciar',
      'string' => 'Se requiere una cadena',
      'in' => 'No es un valor valido',
      'regex' => 'Formato incorrecto'
    ],[])->after(function($validator) use (&$co){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $id_canon_operario = $data['id_canon_operario'] ?? null;
      $id_operario = $data['id_operario'] ?? null;
      
      if($id_canon_operario === null && $id_operario !== null){
        $ya_existe = DB::table('canon_operario')
        ->where('id_operario',$id_operario)
        ->whereNull('deleted_at')
        ->count() > 0;
        if($ya_existe){
          return $validator->errors()->add('id_operario','Ya existe, ingresar otro o modificar el operario ya existente');
        }
      }
      else if($id_canon_operario === null && $id_operario === null){
        return $validator->errors()->add('id_operario','El valor es requerido');
      }
      else if($id_canon_operario !== null && $id_operario === null){
        return $validator->errors()->add('id_operario','El valor es requerido');
      }
      else if($id_canon_operario !== null && $id_operario !== null){
        $co = DB::table('canon_operario')
        ->where('id_canon_operario',$id_canon_operario)
        ->where('id_operario',$id_operario)
        ->whereNull('deleted_at')
        ->first();
        if($co === null){
          return $validator->errors()->add('id_operario','No existe esta instancia para editar intente refrescar la pagina');
        }
      }
      else{
        return $validator->errors()->add('id_operario','UNREACHABLE');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,&$nuevo,&$co,$created_at,$created_by){
      if($co !== null){ //Estoy modificando, tengo que invalidar el viejo
        DB::table('canon_operario')
        ->where('id_canon_operario',$co->id_canon_operario)
        ->where('id_operario',$co->id_operario)
        ->whereNull('deleted_at')
        ->update([
          'deleted_at' => $created_at,
          'deleted_by' => $created_by
        ]);
      }
      
      $data = $request->all();
      
      $cuentas = $data['cuentas'] ?? [];
      unset($data['cuentas']);
      unset($data['id_canon_operario']);
      $data['cuentas'] = $cuentas;
            
      return $this->_guardar($data,$created_at,$created_by);
    });
  }
}
