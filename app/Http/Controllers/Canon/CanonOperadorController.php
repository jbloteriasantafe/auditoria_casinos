<?php
namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Facades\Schema;
  
class CanonOperadorController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
  
  private $CV = null;
  private $CGO = null;
  private $CPe = null;
  private $CCu = null;
  private $mocking = true;
  public function __construct(){
    self::$instance = $this;
    $this->CV  = CanonValorPorDefectoController::getInstancia();
    $this->CGO = CanonGrupoOperadorController::getInstancia();
    $this->CPe = CanonPermisoController::getInstancia();
    $this->CCu = CanonCuentaController::getInstancia();
    $this->mocking = !Schema::hasTable('canon_operador_canon_variable');
    $this->mocking = $this->mocking || !Schema::hasTable('canon_operador_canon_fijo_mesas');
    $this->mocking = $this->mocking || !Schema::hasTable('canon_operador_canon_fijo_mesas_adicionales');
    $this->mocking = $this->mocking || !Schema::hasTable('canon_operador_cuenta');
    $this->mocking = $this->mocking || !Schema::hasTable('canon_operador');
  }
  
  public function down(){
    $this->CCu->down();
    $this->CGO->down();
    
    DB::unprepared("DROP TABLE IF EXISTS canon_operador_canon_variable");
    DB::unprepared("DROP TABLE IF EXISTS canon_operador_canon_fijo_mesas");
    DB::unprepared("DROP TABLE IF EXISTS canon_operador_canon_fijo_mesas_adicionales");
    DB::unprepared("DROP TABLE IF EXISTS canon_operador_cuenta");
    DB::unprepared("DROP TABLE IF EXISTS canon_operador");
    DB::statement("
      ALTER TABLE `canon` CHANGE `id_operador` `id_casino` INT(11) NOT NULL
    ");
    CANON_STREAM_STR('CANON_OPERADOR: DOWN');
    
    $this->CPe->down();
  }
  
  private function up(){
    DB::statement("
      ALTER TABLE `canon` CHANGE `id_casino` `id_operador` INT(11) NOT NULL
    ");
    
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_operador (
      id_canon_operador INT NOT NULL AUTO_INCREMENT,
      id_operador INT NOT NULL, -- ID asignado, el mismo operador puede borrarse pero tenemos que seguirlo teniendo
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
      -- Valores que se aplican a todo los (sub)canons (fijos)
      valor_dolar DECIMAL(20,2) NOT NULL,
      valor_euro DECIMAL(20,2) NOT NULL,
      devengado_cotizacion_dia TINYINT NOT NULL,
      devengado_cotizacion_fin_de_semana ENUM('Lunes Próximo','Viernes Anterior','Sin Movimiento'),
      determinado_cotizacion_dia TINYINT NOT NULL,
      determinado_cotizacion_fin_de_semana ENUM('Lunes Próximo','Viernes Anterior','Sin Movimiento'),
      --
      created_at DATETIME NOT NULL,
      created_by INT NOT NULL,
      deleted_at DATETIME NULL,
      deleted_by INT NULL,
      id_operador_deleted_at VARCHAR(32) GENERATED ALWAYS AS (CONCAT_WS('|',id_operador,IFNULL(deleted_at,''))) STORED NOT NULL,
      PRIMARY KEY (id_canon_operador),
      UNIQUE KEY `unq_canon_operador_id_deleted` (id_operador_deleted_at),
      KEY `fk_canon_operador_created_by` (`created_by`),
      CONSTRAINT `fk_canon_operador_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id_usuario`),
      CONSTRAINT `fk_canon_operador_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `usuario` (`id_usuario`)
    )
    ");
    
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_operador_cuenta (
      id_canon_operador_cuenta INT NOT NULL AUTO_INCREMENT,
      id_canon_operador INT NOT NULL,
      nombre VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      dia_vencimiento TINYINT NOT NULL,
      fin_de_semana ENUM('Lunes Próximo','Viernes Anterior','Sin Movimiento') NOT NULL,
      interes_diario_simple DECIMAL(7,4) NOT NULL,
      interes_mensual_compuesto DECIMAL(7,4) NOT NULL,
      PRIMARY KEY (id_canon_operador_cuenta),
      UNIQUE KEY `unq_canon_operador_cuenta` (id_canon_operador,nombre),
      CONSTRAINT `fk_canon_operador_cuenta_operador` FOREIGN KEY (`id_canon_operador`) REFERENCES `canon_operador` (`id_canon_operador`)
    )
    ");      
    
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_operador_canon_variable (
      id_canon_operador_canon_variable INT NOT NULL AUTO_INCREMENT,
      id_canon_operador INT NOT NULL,
      tipo VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      apostado_porcentaje_aplicable DECIMAL(7,4) NOT NULL,
      porcentaje_impuesto_ley DECIMAL(7,4) NOT NULL,
      alicuota DECIMAL(7,4) NOT NULL,
      devengar TINYINT NOT NULL,
      devengado_deduccion DECIMAL(20,2) NOT NULL,
      PRIMARY KEY (id_canon_operador_canon_variable),
      UNIQUE KEY `unq_canon_operador_canon_variable` (id_canon_operador,tipo),
      CONSTRAINT `fk_canon_operador_canon_variable_operador` FOREIGN KEY (`id_canon_operador`) REFERENCES `canon_operador` (`id_canon_operador`)
    )
    ");
       
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_operador_canon_fijo_mesas (
      id_canon_operador_canon_fijo_mesas INT NOT NULL AUTO_INCREMENT,
      id_canon_operador INT NOT NULL,
      tipo VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      dias_valor SMALLINT NOT NULL,
      lunes_jueves TINYINT NOT NULL, -- bool
      viernes_sabados TINYINT NOT NULL, -- bool
      domingos TINYINT NOT NULL, -- bool
      mes TINYINT NOT NULL, -- bool
      fijos TINYINT NOT NULL,
      devengar TINYINT NOT NULL,
      devengado_deduccion DECIMAL(20,2) NOT NULL,
      PRIMARY KEY (id_canon_operador_canon_fijo_mesas),
      UNIQUE KEY `unq_canon_operador_canon_fijo_mesas` (id_canon_operador,tipo),
      CONSTRAINT `fk_canon_operador_canon_fijo_mesas_operador` FOREIGN KEY (`id_canon_operador`) REFERENCES `canon_operador` (`id_canon_operador`)
    )
    ");
    
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_operador_canon_fijo_mesas_adicionales (
      id_canon_operador_canon_fijo_mesas_adicionales INT NOT NULL AUTO_INCREMENT,
      id_canon_operador INT NOT NULL,
      tipo VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
      dias_mes SMALLINT NOT NULL,
      horas_dia SMALLINT NOT NULL,
      porcentaje DECIMAL(7,4) NOT NULL,
      devengar TINYINT NOT NULL,
      devengado_deduccion DECIMAL(20,2) NOT NULL,
      PRIMARY KEY (id_canon_operador_canon_fijo_mesas_adicionales),
      UNIQUE KEY `unq_canon_operador_canon_fijo_mesas_adicionales` (id_canon_operador,tipo),
      CONSTRAINT `fk_canon_operador_canon_fijo_mesas_adicionales_operador` FOREIGN KEY (`id_canon_operador`) REFERENCES `canon_operador` (`id_canon_operador`)
    )
    ");
    CANON_STREAM_STR('CANON_OPERADOR: UP');
  }
  
  public function llenado_inicial(){
    CANON_STREAM_STR(true);
    return response()->stream(function(){   
      return DB::transaction(function(){
        $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
        
        $this->CCu->llenado_inicial($created_at,$created_by);
        $this->CGO->llenado_inicial($created_at,$created_by);
        
        $this->up();
        $operadores = $this->CV->get('operadores_iniciales');
        foreach($operadores as $o){
          $ret[] = $this->_guardar($o,$created_at,$created_by);
          CANON_STREAM_STR('OPERADOR: '.$o['id_operador']);
          $this->CGO->guardar_individual($o['id_operador'],$created_at,$created_by);
          CANON_STREAM_STR('G. OPERADOR: '.$o['id_operador']);
        }
        
        $this->CPe->llenado_inicial($created_at,$created_by);
        return $ret;
      });
    });
  }
  
  public function buscar(Request $request){
    $eliminados = $request->eliminados ?? 0;
    
    return DB::table('canon_operador as co')
    ->where(DB::raw('(deleted_at IS NOT NULL)'),$eliminados)
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_operador co2 
      WHERE co2.deleted_at IS NULL 
      AND co2.id_operador = co.id_operador 
      AND co2.id_canon_operador <> co.id_canon_operador
      LIMIT 1
    ))'),1)
    //Es el ultimo
    ->where($eliminados ? 
      DB::raw('(NOT EXISTS (
        SELECT 1 
        FROM canon_operador co2 
        WHERE co2.deleted_at IS NOT NULL 
        AND co2.id_operador = co.id_operador 
        AND (
           co2.deleted_at > co.deleted_at
           OR (
             co2.deleted_at = co.deleted_at AND co2.id_canon_operador > co.id_canon_operador
           )
        )
        AND co2.id_canon_operador <> co.id_canon_operador
        LIMIT 1
      ))')
      : DB::raw('1'),
      1
    )
    ->orderBy('id_operador','desc')
    ->paginate($request->page_size ?? 10);
  }
  
  private function mocking__obtener($id_operador){
    if($id_operador === null) return null;
    $co = \App\Casino::find($id_operador)->toArray();
    $co['cuentas'] = [];
    $co['canon_variable'] = [];
    $co['canon_fijo_mesas'] = [];
    $co['canon_fijo_mesas_adicionales'] = [];
    return $co;
  }
  
  public function _obtener($id_operador){
    if($id_operador === null) return null;
    $co = DB::table('canon_operador')
    ->where('id_operador',$id_operador)
    ->whereNull('deleted_at')
    ->first();
    
    if($co === null) return null;
    
    $co = (array) $co;
    return $this->agregar_dependencias($co);
  }
  
  public function obtener(Request $request){
    return $this->_obtener($request->id_operador ?? null);
  }
  
  public function obtenerConHistorial(Request $request){
    $ultimo = $this->_obtener($request->id_operador ?? null);
    if($ultimo === null) return ['historial' => []];
    
    $ultimo['historial'] = DB::table('canon_operador as co')
    ->select('u.user_name as usuario','co.*')
    ->where('co.id_operador',$ultimo['id_operador'])
    ->join('usuario as u','u.id_usuario','=','co.created_by')
    ->orderBy('co.created_at','desc')
    ->get()->map(function(&$co){
      $co = (array) $co;
      return $this->agregar_dependencias($co);
    });
    
    return $ultimo;
  }
  
  private function agregar_dependencias(&$co){
    if($co === null) return null;
    
    $to_array = function(\stdClass $o){return (array)$o;};
    
    $co['cuentas'] = DB::table('canon_operador_cuenta')
    ->where('id_canon_operador',$co['id_canon_operador'])
    ->get()->map($to_array)->toArray();
    
    $co['canon_variable'] = DB::table('canon_operador_canon_variable')
    ->where('id_canon_operador',$co['id_canon_operador'])
    ->get()->map($to_array)->toArray();
    
    $co['canon_fijo_mesas'] = DB::table('canon_operador_canon_fijo_mesas')
    ->where('id_canon_operador',$co['id_canon_operador'])
    ->get()->map($to_array)->toArray();
    
    $co['canon_fijo_mesas_adicionales'] = DB::table('canon_operador_canon_fijo_mesas_adicionales')
    ->where('id_canon_operador',$co['id_canon_operador'])
    ->get()->map($to_array)->toArray();
    
    return $co;
  }
  
  public function borrar(Request $request){
    $id_operador = $request->id_operador ?? null;
    if($id_operador === null) return 0;
    return DB::transaction(function() use ($id_operador){
      $deleted_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $deleted_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_operador')
      ->where('id_operador',$id_operador)
      ->whereNull('deleted_at')
      ->update(compact('deleted_at','deleted_by'));
      
      $this->CGO->borrar_individual($id_operador,$deleted_at,$deleted_by);
      
      return 1;
    });
  }
  
  public function desborrar(Request $request){
    $id_operador = $request->id_operador ?? null;
    if($id_operador === null) return 0;
    $co = DB::table('canon_operador as co')
    ->where('co.id_operador',$id_operador)
    ->whereNotNull('co.deleted_at')
    //No existe uno vivo
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_operador co2 
      WHERE co2.deleted_at IS NULL 
      AND co2.id_operador = co.id_operador 
      AND co2.id_canon_operador <> co.id_canon_operador
      LIMIT 1
    ))'),1)
    //Es el ultimo
    ->where(DB::raw('(NOT EXISTS (
      SELECT 1 
      FROM canon_operador co2 
      WHERE co2.deleted_at IS NOT NULL 
      AND co2.id_operador = co.id_operador 
      AND (
         co2.deleted_at > co.deleted_at
         OR (
           co2.deleted_at = co.deleted_at AND co2.id_canon_operador > co.id_canon_operador
         )
      )
      AND co2.id_canon_operador <> co.id_canon_operador
      LIMIT 1
    ))'),1)
    ->first();
    
    if($co === null) return 0;
    $co = (array) $co;
    $co = $this->agregar_dependencias($co);
    
    return DB::transaction(function() use ($co){
      $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      unset($co['id_canon_operador']);
      unset($co['created_at']);
      unset($co['created_by']);
      unset($co['deleted_at']);
      unset($co['deleted_by']);
      unset($co['id_operador_deleted_at']);
      
      foreach($co as $attr => &$arr){
        if(!is_array($arr)) continue;
        foreach($arr as &$obj){
          unset($obj['id_canon_operador_cuenta']);
          unset($obj['id_canon_operador_canon_variable']);
          unset($obj['id_canon_operador_canon_fijo_mesas']);
          unset($obj['id_canon_operador_canon_fijo_mesas_adicionales']);
        }
      }
      
      $ret = $this->_guardar($co,$created_at,$created_by);
      $this->CGO->desborrar_individual($co['id_operador'],$created_at,$created_by);
      
      return $ret;
    });
  }
  
  private function _guardar(array $co,$created_at,$created_by){
    unset($co['id_canon_operador']);
    
    $cuentas = $co['cuentas'] ?? [];
    unset($co['cuentas']);
    
    $canon_variable = $co['canon_variable'] ?? [];
    unset($co['canon_variable']);
    
    $canon_fijo_mesas = $co['canon_fijo_mesas'] ?? [];
    unset($co['canon_fijo_mesas']);
    
    $canon_fijo_mesas_adicionales = $co['canon_fijo_mesas_adicionales'] ?? [];
    unset($co['canon_fijo_mesas_adicionales']);
    
    $co['created_at'] = $created_at;
    $co['created_by'] = $created_by;
    $id_canon_operador = DB::table('canon_operador')
    ->insertGetId($co);
    
    DB::table('canon_operador_cuenta')
    ->insert(
      array_map(function($c) use ($id_canon_operador){
        $c['id_canon_operador'] = $id_canon_operador;
        return $c;
      },$cuentas)
    );
    
    DB::table('canon_operador_canon_variable')
    ->insert(
      array_map(function($c) use ($id_canon_operador){
        $c['id_canon_operador'] = $id_canon_operador;
        return $c;
      },$canon_variable)
    );
    
    DB::table('canon_operador_canon_fijo_mesas')
    ->insert(
      array_map(function($c) use ($id_canon_operador){
        $c['id_canon_operador'] = $id_canon_operador;
        return $c;
      },$canon_fijo_mesas)
    );
    
    DB::table('canon_operador_canon_fijo_mesas_adicionales')
    ->insert(
      array_map(function($c) use ($id_canon_operador){
        $c['id_canon_operador'] = $id_canon_operador;
        return $c;
      },$canon_fijo_mesas_adicionales)
    );
    
    return $this->_obtener($co['id_operador']);
  }
  
  public function guardar(Request $request){
    $created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    $created_by = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
    $co = null;

    $numeric_rule = function(int $digits) {
      static $cache = [];
      if($cache[$digits] ?? false) return $cache[$digits];
      $regex = '-?\d+';
      if($digits){
        $digits_regexp = implode('',array_fill(0,$digits,'\d?'));
        $regex .= '\.?'.$digits_regexp;
      }
      $cache[$digits] = 'regex:/^'.$regex.'$/';
      return $cache[$digits];
    };
    
    $movimiento_dia_fin_de_semana = 'in:Lunes Próximo,Viernes Anterior,Sin Movimiento';

    Validator::make($request->all(),[
      'id_canon_operador' => ['nullable','integer','exists:canon_operador,id_canon_operador,deleted_at,NULL'],
      'id_operador' => ['nullable','integer'],
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
      
      'valor_dolar' => ['required','string',$numeric_rule(2)],
      'valor_euro' => ['required','string',$numeric_rule(2)],
      'devengado_cotizacion_dia' => ['required','integer','min:1','max:28'],
      'determinado_cotizacion_dia' => ['required','integer','min:1','max:28'],
      'devengado_cotizacion_fin_de_semana'   => ['required','string',$movimiento_dia_fin_de_semana],
      'determinado_cotizacion_fin_de_semana' => ['required','string',$movimiento_dia_fin_de_semana],
      
      'cuentas' => ['required','array','min:1'],
      'cuentas.*.nombre' => ['required','string','max:64'],
      'cuentas.*.dia_vencimiento' => ['required','integer','min:1','max:28'],
      'cuentas.*.fin_de_semana' => ['nullable','string',$movimiento_dia_fin_de_semana],
      'cuentas.*.interes_diario_simple' => ['required','string','regex:/^\d{1,3}(\.\d{0,4})?$/'],
      'cuentas.*.interes_mensual_compuesto' => ['required','string','regex:/^\d{1,3}(\.\d{0,4})?$/'],
      
      'canon_variable' => ['nullable','array'],
      'canon_variable.*.tipo' => ['required','string','max:32'],
      'canon_variable.*.devengar' => ['required','integer','in:0,1'],
      'canon_variable.*.devengado_deduccion' => ['required','string',$numeric_rule(2)],
      'canon_variable.*.apostado_porcentaje_aplicable' => ['required','string','regex:/^\d{1,3}(\.\d{0,4})?$/'],
      'canon_variable.*.porcentaje_impuesto_ley' => ['required','string','regex:/^\d{1,3}(\.\d{0,4})?$/'],
      'canon_variable.*.alicuota' => ['required','string','regex:/^\d{1,3}(\.\d{0,4})?$/'],    
      'canon_variable.*.devengar' => ['required','integer','in:0,1'],
      'canon_variable.*.devengado_deduccion' => ['required','string',$numeric_rule(2)],
      
      'canon_fijo_mesas' => ['nullable','array'],
      'canon_fijo_mesas.*.tipo' => ['required','string','max:32'],
      'canon_fijo_mesas.*.dias_valor' => ['required','integer','min:1','max:32767'],
      'canon_fijo_mesas.*.lunes_jueves' => ['required','integer','in:0,1'],
      'canon_fijo_mesas.*.viernes_sabados' => ['required','integer','in:0,1'],
      'canon_fijo_mesas.*.domingos' => ['required','integer','in:0,1'],
      'canon_fijo_mesas.*.mes' => ['required','integer','in:0,1'],
      'canon_fijo_mesas.*.fijos' => ['required','integer','min:0','max:127'],
      'canon_fijo_mesas.*.devengar' => ['required','integer','in:0,1'],
      'canon_fijo_mesas.*.devengado_deduccion' => ['required','string',$numeric_rule(2)],
      
      'canon_fijo_mesas_adicionales' => ['nullable','array'],
      'canon_fijo_mesas_adicionales.*.tipo' => ['required','string','max:32'],
      'canon_fijo_mesas_adicionales.*.dias_mes' => ['required','integer','min:1','max:32767'],
      'canon_fijo_mesas_adicionales.*.horas_dia' => ['required','integer','min:1','max:32767'],
      'canon_fijo_mesas_adicionales.*.porcentaje' => ['required','string','regex:/^\d{1,3}(\.\d{0,4})?$/'],      
      'canon_fijo_mesas_adicionales.*.devengar' => ['required','integer','in:0,1'],
      'canon_fijo_mesas_adicionales.*.devengado_deduccion' => ['required','string',$numeric_rule(2)]
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
      $id_canon_operador = $data['id_canon_operador'] ?? null;
      $id_operador = $data['id_operador'] ?? null;
      
      if($id_canon_operador === null && $id_operador !== null){
        $ya_existe = DB::table('canon_operador')
        ->where('id_operador',$id_operador)
        ->whereNull('deleted_at')
        ->count() > 0;
        if($ya_existe){
          return $validator->errors()->add('id_operador','Ya existe, ingresar otro o modificar el operador ya existente');
        }
      }
      else if($id_canon_operador === null && $id_operador === null){
        return $validator->errors()->add('id_operador','El valor es requerido');
      }
      else if($id_canon_operador !== null && $id_operador === null){
        return $validator->errors()->add('id_operador','El valor es requerido');
      }
      else if($id_canon_operador !== null && $id_operador !== null){
        $co = DB::table('canon_operador')
        ->where('id_canon_operador',$id_canon_operador)
        ->where('id_operador',$id_operador)
        ->whereNull('deleted_at')
        ->first();
        if($co === null){
          return $validator->errors()->add('id_operador','No existe esta instancia para editar intente refrescar la pagina');
        }
      }
      else{
        return $validator->errors()->add('id_operador','UNREACHABLE');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,&$nuevo,&$co,$created_at,$created_by){
      if($co !== null){ //Estoy modificando, tengo que invalidar el viejo
        DB::table('canon_operador')
        ->where('id_canon_operador',$co->id_canon_operador)
        ->where('id_operador',$co->id_operador)
        ->whereNull('deleted_at')
        ->update([
          'deleted_at' => $created_at,
          'deleted_by' => $created_by
        ]);
      }
      
      $data = $request->all();
      unset($data['id_canon_operador']);
      
      $cuentas = $data['cuentas'] ?? [];
      unset($data['cuentas']);
      $data['cuentas'] = $cuentas;
      
      $canon_variable = $data['canon_variable'] ?? [];
      unset($data['canon_variable']);
      $data['canon_variable'] = $canon_variable;
      
      $canon_fijo_mesas = $data['canon_fijo_mesas'] ?? [];
      unset($data['canon_fijo_mesas']);
      $data['canon_fijo_mesas'] = $canon_fijo_mesas;

      $canon_fijo_mesas_adicionales = $data['canon_fijo_mesas_adicionales'] ?? [];
      unset($data['canon_fijo_mesas_adicionales']);
      $data['canon_fijo_mesas_adicionales'] = $canon_fijo_mesas_adicionales;

      $ret = $this->_guardar($data,$created_at,$created_by);
      $this->CGO->guardar_individual($data['id_operador'],$created_at,$created_by);
      return $ret;
    });
  }
  
  private $op_cache = [];
  public function obtener_operador($id_operador){
    if(!array_key_exists($id_operador,$this->op_cache)){
      $op = ($this->mocking? $this->mocking__obtener($id_operador) : $this->_obtener($id_operador)) ?? [];
      $to_array = function($o){return (array)$o;};
      $op['cuentas'] = collect($op['cuentas'] ?? [])->map($to_array)->keyBy('nombre')->toArray();
      $op['canon_variable'] = collect($op['canon_variable'] ?? [])->map($to_array)->keyBy('tipo')->toArray();
      $op['canon_fijo_mesas'] = collect($op['canon_fijo_mesas'] ?? [])->map($to_array)->keyBy('tipo')->toArray();
      $op['canon_fijo_mesas_adicionales'] = collect($op['canon_fijo_mesas_adicionales'] ?? [])->map($to_array)->keyBy('tipo')->toArray();
      $this->op_cache[$id_operador] = $op;
    }
    return $this->op_cache[$id_operador];
  }
  
  public function mover_fecha(\DateTimeImmutable $date,$movimiento = null){
    if($movimiento === null || $movimiento == 'Sin Movimiento'){
      return $date;
    }
    if($movimiento == 'Viernes Anterior'){
      $viernes_anterior = clone $date;
      $interval_1_day = \DateInterval::createFromDateString('1 day');
      for($break = 3;$break > 0 && in_array($viernes_anterior->format('w'),['0','6']);$break--){
        $viernes_anterior = $viernes_anterior->sub($interval_1_day);
      }
      if($break == 0){
        throw new \Exception('Unreachable');
      }
      return $viernes_anterior;
    }
    if($movimiento == 'Lunes Próximo'){
      $lunes_proximo = clone $date;
      $interval_1_day = \DateInterval::createFromDateString('1 day');
      for($break = 3;$break > 0 && in_array($lunes_proximo->format('w'),['0','6']);$break--){
        $lunes_proximo = $lunes_proximo->add($interval_1_day);
      }
      if($break == 0){
        throw new \Exception('Unreachable');
      }
      return $lunes_proximo;
      
    }
    throw new \Exception("No puedo mover fecha ${ISO_yyyymmdd}, Movimiento no soportado: $movimiento");
  }
  
  public function operadores($ids_operadores = null){
    $ret = DB::table('canon_operador')
    ->whereNull('deleted_at');
    if($ids_operadores !== null){
      $ret = $ret->whereIn('id_operador',$ids_operadores);
    }
    return $ret->get();
  }
}
