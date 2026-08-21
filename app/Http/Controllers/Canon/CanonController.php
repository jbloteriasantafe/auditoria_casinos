<?php

namespace App\Http\Controllers\Canon;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use View;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Archivo;
use App\Casino;
use Illuminate\Support\Facades\Schema;

require_once(app_path('BC_extendido.php'));

class CanonController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
    
  private $u = null;
  private $CVD = null;
  private $CA = null;
  private $CPe = null;
  private $CO = null;
  public function __construct(){
    self::$instance = $this;
    $this->CVD = CanonValorPorDefectoController::getInstancia();
    $this->CA = CanonAgrupamientoController::getInstancia();
    $this->CCu = CanonCuentaController::getInstancia();
    $this->CPe = CanonPermisoController::getInstancia();
    $this->CO = CanonOperadorController::getInstancia();
    $this->CGO = CanonGrupoOperadorController::getInstancia();
    $this->middleware(function ($request, $next) {
      $this->u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      return $next($request);
    });
  }

    
  private function subcanons() {
    return ['canon_variable','canon_fijo_mesas','canon_fijo_mesas_adicionales'];
  }

  public function up(){
    $scs = $this->subcanons();
    foreach($scs as $sc){
      if(!Schema::hasColumn($sc,'cuenta')){
        DB::statement("ALTER TABLE $sc 
          ADD cuenta 
            VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin 
            NOT NULL 
            DEFAULT '' 
            AFTER id_canon
        ");
        DB::statement("ALTER TABLE `bdMTM`.`$sc` ADD INDEX idx_{$sc}_canon_cuenta (id_canon,cuenta)");
      }
    }
    if(Schema::hasColumn('canon','id_casino')){
      DB::statement("
        ALTER TABLE `canon` CHANGE `id_casino` `id_operador` INT(11) NOT NULL
      ");
    }
    CANON_STREAM_STR('CANON: UP');
  }

  public function down(){
    if(Schema::hasColumn('canon','id_operador')){
      DB::statement("
        ALTER TABLE `canon` CHANGE `id_operador` `id_casino` INT(11) NOT NULL
      ");
    }
    CANON_STREAM_STR('CANON: DOWN');
  }

  public function llenado_inicial($created_at,$created_by){ 
    //NOP
  }
          
  public function index(){
    $iou = $this->CPe->ids_operadores_usuario($this->u->id_usuario);
    
    $operadores = $this->CO->operadores($iou);
    $cuentas = $this->CCu->cuentas($iou);
    $permisos_u = $this->CPe->permisos_usuario($this->u->id_usuario);
    $permisos = function($p) use (&$permisos_u){
      return array_key_exists($p,$permisos_u);
    };
    
    $subcanons = $this->subcanons();
    return View::make('Canon.index', compact('operadores','permisos','cuentas','subcanons'));
  }
  
  private static  $errores = [
    'required' => 'El valor es requerido',
    'regex'    => 'Formato incorrecto',
    'date'     => 'Tiene que ser una fecha en formato YYYY-MM-DD',
    'min'      => 'Es inferior al limite',
    'max'      => 'Supera el limite',
    'integer'  => 'Tiene que ser un número entero',
    'exists'   => 'El valor es incorrecto',
  ];
    
  private function validarCanon(array $request,array $requireds = []){
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
    $requireds_f = function(string $s) use ($requireds) {
      return in_array($s,$requireds)? 'required' : 'nullable';
    };

    Validator::make($request,[
      //canon
      'id_canon' => ['nullable','integer','exists:canon,id_canon,deleted_at,NULL'],
      'año_mes' => [$requireds_f('año_mes'),'regex:/^\d{4}\-((0\d)|(1[0-2]))\-01$/'],
      'id_operador' => [$requireds_f('id_operador'),'integer','exists:canon_operador,id_operador,deleted_at,NULL'],
      'estado' => ['nullable','string','max:32'],
      'es_antiguo' => [$requireds_f('es_antiguo'),'integer','in:1,0'],
      
      //Valores que se "difunden" a cada subcanon >:(
      'valor_dolar' => ['nullable',$numeric_rule(2)],
      'valor_euro' => ['nullable',$numeric_rule(2)],
      'devengado_fecha_cotizacion' => ['nullable','date'],
      'devengado_cotizacion_dolar' => ['nullable',$numeric_rule(2)],
      'devengado_cotizacion_euro' => ['nullable',$numeric_rule(2)],
      'determinado_fecha_cotizacion' => ['nullable','date'],
      'determinado_cotizacion_dolar' => ['nullable',$numeric_rule(2)],
      'determinado_cotizacion_euro' => ['nullable',$numeric_rule(2)],
      //subcanons
      'canon_variable' => 'array',
      'canon_variable.*.cuenta' => ['nullable','string','max:64'],
      'canon_variable.*.devengado_apostado_sistema' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.devengado_apostado_porcentaje_aplicable' => ['nullable',$numeric_rule(4)],
      'canon_variable.*.devengado_apostado_porcentaje_impuesto_ley' => ['nullable',$numeric_rule(4)],
      'canon_variable.*.devengado_bruto' => ['nullable',$numeric_rule(2)],
      //'canon_variable.*.devengado_total' => ['nullable',$numeric_rule(20)],
      'canon_variable.*.devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.determinado_impuesto' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.determinado_bruto' => ['nullable',$numeric_rule(2)],
      //'canon_variable.*.determinado_total' => ['nullable',$numeric_rule(20)],
      'canon_variable.*.determinado_ajuste' => ['nullable',$numeric_rule(22)],
      'canon_variable.*.alicuota' => ['nullable',$numeric_rule(4)],
      'canon_fijo_mesas' => 'array',
      'canon_fijo_mesas.*.cuenta' => ['nullable','string','max:64'],
      'canon_fijo_mesas.*.dias_valor' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_lunes_jueves' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_lunes_jueves' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_viernes_sabados' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_viernes_sabados' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_domingos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_domingos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_todos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_todos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_fijos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_fijos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.determinado_ajuste' => ['nullable',$numeric_rule(22)],
      'canon_fijo_mesas.*.bruto' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales' => 'array',
      'canon_fijo_mesas_adicionales.*.cuenta' => ['nullable','string','max:64'],
      'canon_fijo_mesas_adicionales.*.dias_mes' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas_dia' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.mesas' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.porcentaje' => ['nullable',$numeric_rule(4)],
      'canon_fijo_mesas_adicionales.*.devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.determinado_ajuste' => ['nullable',$numeric_rule(22)],
      'canon_archivo' => 'array',
      'canon_archivo.*.descripcion' => ['nullable','string','max:256'],
      'canon_archivo.*.id_archivo'  => ['nullable','integer','exists:archivo,id_archivo'],
      'canon_archivo.*.file'        => 'file',
    ], self::$errores,[])->after(function($validator){
      if($validator->errors()->any()) return;
    })->validate();
  }
  
  public function recalcular_req(Request $request){
    $R = $request->all();
    $this->validarCanon($R);
    return $this->recalcular($R);
  }
  
  private function recalcular(array $request){
    $R = function($s,$dflt = null) use (&$request){
      return (($request[$s] ?? null) === null || ($request[$s] === '') || ($request[$s] === []))? $dflt : $request[$s];
    };
    
    $año_mes = $R('año_mes');//@RETORNADO
    $id_operador = $R('id_operador');//@RETORNADO
    $op = $this->CO->obtener_operador($id_operador);
    
    $canon_anterior = collect([]);//@RETORNADO
    if($año_mes !== null && $id_operador !== null){
      $canon_anterior  = $this->get_prev_by_id_operador_año_mes($id_operador,$año_mes)->first();
      
      if($canon_anterior !== null){
        $canon_anterior = $this->obtener_arr(['id_canon' => $canon_anterior->id_canon]);
      }
    }
    
    $estado = $R('estado','Nuevo');//@RETORNADO
    $fecha_cotizacion = $R('fecha_cotizacion');//@RETORNADO
    $es_antiguo = $R('es_antiguo',0)? 1 : 0;//@RETORNADO
    $canon_archivo = $R('canon_archivo',[]);//@RETORNADO
    
    $devengado_bruto = '0.00';//@RETORNADO
    $devengado_deduccion = '0.00';//@RETORNADO
    $devengado = '0.00';//@RETORNADO
    $determinado_bruto = '0.00';//@RETORNADO
    $determinado_ajuste = '0.00';//@RETORNADO
    $determinado = '0.00';//@RETORNADO
    
    //Esto se hace asi porque originalmente se pensaba que las mesas tenian c/u fechas y cotizaciones distintas
    //despues me entere que eran la misma. De todos modos al guardarse en cada tabla de BD, facilita su recalculo en caso
    //de modificaciones al codigo y lo hace mas robusto, lo malo es que complica un poco el codigo
    //Entonces por ejemplo, si cambia la logica, podemos seguir recalculando cada subcanon independientemente de los demas
    $COT = [
      'valor_dolar' => $R('valor_dolar',$op['valor_dolar'] ?? '0'),
      'valor_euro'  => $R('valor_euro',$op['valor_euro'] ?? '0'),
      'devengado_fecha_cotizacion'   => $R('devengado_fecha_cotizacion',null),
      'devengado_cotizacion_dolar'   => $R('devengado_cotizacion_dolar',null),
      'devengado_cotizacion_euro'    => $R('devengado_cotizacion_euro',null),
      'determinado_fecha_cotizacion' => $R('determinado_fecha_cotizacion',null),
      'determinado_cotizacion_dolar' => $R('determinado_cotizacion_dolar',null),
      'determinado_cotizacion_euro'  => $R('determinado_cotizacion_euro',null),
    ];
    
    if($año_mes !== null && $año_mes !== '' && ($COT['devengado_fecha_cotizacion'] === null || $COT['determinado_fecha_cotizacion'] === null)){
      $f = explode('-',$año_mes);
      
      $f[0] = intval($f[0]);
      $f[1] = intval($f[1]);
      $f[2] = intval($f[2]);
      
      $f[0] = str_pad($f[1] == 12?  ($f[0]+1) :     $f[0],4,'0',STR_PAD_LEFT);
      $f[1] = str_pad($f[1] == 12?         1  : ($f[1]+1),2,'0',STR_PAD_LEFT);;
      
      if($COT['devengado_fecha_cotizacion'] === null){
        $COT['devengado_fecha_cotizacion'] = implode('-',[
          $f[0],
          $f[1],
          str_pad($op['devengado_cotizacion_dia'] ?? 1,2,'0',STR_PAD_LEFT)
        ]);
        $COT['devengado_fecha_cotizacion'] = $this->CO->mover_fecha(
          new \DateTimeImmutable($COT['devengado_fecha_cotizacion']),
          $op['devengado_cotizacion_fin_de_semana'] ?? null
        )->format('Y-m-d');
      }
      
      if($COT['determinado_fecha_cotizacion'] === null){
        $COT['determinado_fecha_cotizacion'] = implode('-',[
          $f[0],
          $f[1],
          str_pad($op['determinado_cotizacion_dia'] ?? 1,2,'0',STR_PAD_LEFT)
        ]);
        $COT['determinado_fecha_cotizacion'] = $this->CO->mover_fecha(
          new \DateTimeImmutable($COT['determinado_fecha_cotizacion']),
          $op['determinado_cotizacion_fin_de_semana'] ?? null
        )->format('Y-m-d');
      }
    }
    
    if($COT['devengado_fecha_cotizacion'] !== null){
      $COT['devengado_cotizacion_dolar'] = $COT['devengado_cotizacion_dolar'] ?? $this->cotizacion($COT['devengado_fecha_cotizacion'],2,$id_operador) ?? '0';
      $COT['devengado_cotizacion_euro']  = $COT['devengado_cotizacion_euro']  ?? $this->cotizacion($COT['devengado_fecha_cotizacion'],3,$id_operador) ?? '0';
    }
    
    if($COT['determinado_fecha_cotizacion'] !== null){
      $COT['determinado_cotizacion_dolar'] = $COT['determinado_cotizacion_dolar'] ?? $this->cotizacion($COT['determinado_fecha_cotizacion'],2,$id_operador) ?? '0';
      $COT['determinado_cotizacion_euro']  = $COT['determinado_cotizacion_euro']  ?? $this->cotizacion($COT['determinado_fecha_cotizacion'],3,$id_operador) ?? '0';
    }

    $subcanons = array_map(function($sc){ return []; },array_flip($this->subcanons()));
    
    foreach($subcanons as $subcanon => &$retsc){
      $defecto = $op[$subcanon] ?? [];
      $subcanon_canon_anterior = $canon_anterior[$subcanon] ?? [];
      $subcanon_req = $request[$subcanon] ?? [];
      
      $tipos = array_keys($request[$subcanon] ?? $defecto ?? []);
      foreach($tipos as $tipo){
        $data_tipo_req = $subcanon_req[$tipo] ?? [];
        
        $data_tipo_defecto = $defecto[$tipo] ?? [];
        $data_tipo_canon_anterior = $subcanon_canon_anterior[$tipo] ?? [];
        $retsc[$tipo] = $this->{$subcanon.'_recalcular'}(
          $año_mes,
          $id_operador,
          $es_antiguo,
          $tipo,
          $data_tipo_req,
          $data_tipo_defecto,
          $data_tipo_canon_anterior,
          $COT
        );
        
        if($retsc[$tipo]['devengar'] ?? 1){
          $devengado_deduccion = bcadd($devengado_deduccion,$retsc[$tipo]['devengado_deduccion'] ?? '0',2);
          $devengado_bruto = bcadd($devengado_bruto,$retsc[$tipo]['devengado_total'] ?? '0',22);
          $devengado = bcadd($devengado,$retsc[$tipo]['devengado'] ?? 0,22);
        }
        
        $determinado_ajuste = bcadd($determinado_ajuste,$retsc[$tipo]['determinado_ajuste'] ?? '0',22);
        $determinado_bruto = bcadd($determinado_bruto,$retsc[$tipo]['determinado_total'] ?? '0',22);
        $determinado = bcadd($determinado,$retsc[$tipo]['determinado'] ?? '0',22);
      }
    }
    
    $COT = $this->confluir_datos($subcanons,['canon_fijo_mesas','canon_fijo_mesas_adicionales'],array_keys($COT));
    
    $devengado   = bcround_ndigits($devengado,2);//@RETORNADO
    $determinado = bcround_ndigits($determinado,2);//@RETORNADO
    
    $porcentaje_seguridad = bccomp($devengado,'0.00',2) <> 0?//@RETORNADO
       bcdiv(bcmul('100',bcsub($determinado,$devengado,2),2),$devengado,19)
      : '0.00';
      
    //porcentaje_seguridad es DECIMAL(41,19)
    //da 22 digitos decimales y 19 de precision (sacando divisiones periodicas o irracionales que se truncan)
    //esto es porque
    //MAX porcentaje_seguridad = 100 * MAX num / MIN num = 100 * 9...9[18].99 / 0.01 = 100 * 9....9[20] -> 22
    //MIN porcentaje_seguridad = 100 * MIN num / MAX num = 100 * 0.01 / 9...9[18].99 > 100 * 0.01 / 10**19 = 10**(-19) -> 19
    $MAX_PORCENTAJE_SEGURIDAD = str_repeat('9',22).'.'.str_repeat('9',19);//El maximo posible es este... lo clampeo por las dudas
    $porcentaje_seguridad = bcclamp($porcentaje_seguridad,
      '-'.$MAX_PORCENTAJE_SEGURIDAD,
      $MAX_PORCENTAJE_SEGURIDAD,
      bcscale_string($MAX_PORCENTAJE_SEGURIDAD)
    );
    
    return array_merge(
      compact(
        'año_mes','id_operador','estado','es_antiguo',
        'devengado_bruto','devengado_deduccion','devengado',
        'determinado_bruto','determinado_ajuste','determinado','porcentaje_seguridad',
        'canon_archivo'
      ),
      $COT,
      $subcanons
    );
  }
  
  public function canon_variable_recalcular(
    $año_mes,
    $id_operador,
    $es_antiguo,
    $tipo,
    $data,
    $valores_defecto,
    $anterior,
    $COT
  ){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] === '') || ($data[$s] === []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] === '') || ($valores_defecto[$s] === []))? $dflt : $valores_defecto[$s];
    };
    $A = function($s,$dflt = null) use (&$anterior){
      return (($anterior[$s] ?? null) === null || ($anterior[$s] === '') || ($anterior[$s] === []))? $dflt : $anterior[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    $RAD = function($s,$dflt = null) use ($R,$A,$D){
      return $R($s,null) ?? $A($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
    $cuenta = $RD('cuenta','');
    $devengado_apostado_sistema = bcadd($R('devengado_apostado_sistema',$this->apostado($tipo,$año_mes,$id_operador)),'0',2);//@RETORNADO    
    $devengado_apostado_porcentaje_aplicable = bcadd($RD('devengado_apostado_porcentaje_aplicable','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_aplicable = bcdiv($devengado_apostado_porcentaje_aplicable,'100',6);
    
    $devengado_base_imponible = bcmul($devengado_apostado_sistema,$factor_apostado_porcentaje_aplicable,8);//2+6 @RETORNADO
    
    $devengado_apostado_porcentaje_impuesto_ley = bcadd($RD('devengado_apostado_porcentaje_impuesto_ley','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_impuesto_ley = bcdiv($devengado_apostado_porcentaje_impuesto_ley,'100',6);
    
    $devengado_impuesto   = bcmul($devengado_base_imponible,$factor_apostado_porcentaje_impuesto_ley,14);//8+6 @RETORNADO
    $determinado_impuesto =  bcadd($R('determinado_impuesto','0.00'),'0',14);//@RETORNADO
    
    $devengado_bruto   = $R('devengado_bruto',null);//@RETORNADO
    $determinado_bruto = $R('determinado_bruto',null);//@RETORNADO
    if($devengado_bruto === null || $determinado_bruto === null){
      $bruto = $this->bruto($tipo,$año_mes,$id_operador);
      $devengado_bruto   = $devengado_bruto   ?? $bruto;
      $determinado_bruto = $determinado_bruto ?? $bruto;
    }
    
    $devengado_bruto   = bcadd($devengado_bruto,'0',2);
    $determinado_bruto = bcadd($determinado_bruto,'0',2);
    
    $devengado_subtotal   = bcsub($devengado_bruto,$devengado_impuesto,14);//@RETORNADO
    $determinado_subtotal = bcsub($determinado_bruto,$determinado_impuesto,14);//@RETORNADO
    
    $alicuota = bcadd($RD('alicuota','0.0000'),'0',4);//@RETORNADO
    $factor_alicuota = bcdiv($alicuota,'100',6);
    
    $devengado_total   =  bcmul($devengado_subtotal,$factor_alicuota,20);//6+14 @RETORNADO
    $determinado_total =  bcmul($determinado_subtotal,$factor_alicuota,20);//6+14 @RETORNADO
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);
    $determinado_ajuste  = bcadd($RD('determinado_ajuste','0.00'),'0',20);
    
    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
    
    $devengado = bcsub($devengado_total,$devengado_deduccion,20);
    $determinado = bcadd($determinado_total,$determinado_ajuste,20);
    
    return compact('tipo',
      'cuenta',
      'alicuota','devengar',
      'devengado_apostado_sistema','devengado_apostado_porcentaje_aplicable','devengado_base_imponible',
      'devengado_apostado_porcentaje_impuesto_ley',
      'devengado_bruto','devengado_impuesto','devengado_subtotal','devengado_total','devengado_deduccion',
      'devengado',
      'determinado_impuesto','determinado_bruto','determinado_subtotal','determinado_total','determinado_ajuste',
      'determinado'
    );
  }
  
  public function canon_fijo_mesas_recalcular(
    $año_mes,
    $id_operador,
    $es_antiguo,
    $tipo,
    $data,
    $valores_defecto,
    $anterior,
    $COT
  ){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] === '') || ($data[$s] === []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] === '') || ($valores_defecto[$s] === []))? $dflt : $valores_defecto[$s];
    };
    $A = function($s,$dflt = null) use (&$anterior){
      return (($anterior[$s] ?? null) === null || ($anterior[$s] === '') || ($anterior[$s] === []))? $dflt : $anterior[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    $RAD = function($s,$dflt = null) use ($R,$A,$D){
      return $R($s,null) ?? $A($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
    $cuenta = $RD('cuenta','');
    $devengado_fecha_cotizacion = $COT['devengado_fecha_cotizacion'] ?? null;//@RETORNADO
    $determinado_fecha_cotizacion = $COT['determinado_fecha_cotizacion'] ?? null;//@RETORNADO
    $devengado_cotizacion_dolar = $COT['devengado_cotizacion_dolar'] ?? '0';//@RETORNADO
    $devengado_cotizacion_euro = $COT['devengado_cotizacion_euro'] ?? '0';//@RETORNADO
    $determinado_cotizacion_dolar = $COT['determinado_cotizacion_dolar'] ?? '0';//@RETORNADO
    $determinado_cotizacion_euro = $COT['determinado_cotizacion_euro'] ?? '0';//@RETORNADO
    
    $valor_dolar = $COT['valor_dolar'] ?? null;//@RETORNADO
    $valor_euro  = $COT['valor_euro']  ?? null;//@RETORNADO
    
    $dias_valor = $RD('dias_valor',0);//@RETORNADO
    $factor_dias_valor = $dias_valor != 0? bcdiv('1',$dias_valor,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    
    $devengado_valor_dolar_cotizado = bcmul($devengado_cotizacion_dolar,$valor_dolar,4);//2+2
    $devengado_valor_dolar_diario_cotizado  = '0.0000000000000000';//@RETORNADO
    $devengado_valor_euro_cotizado  = bcmul($devengado_cotizacion_euro,$valor_euro,4);//2+2
    $devengado_valor_euro_diario_cotizado   = '0.0000000000000000';//@RETORNADO
    $determinado_valor_dolar_cotizado = bcmul($determinado_cotizacion_dolar,$valor_dolar,4);//2+2
    $determinado_valor_dolar_diario_cotizado  = '0.0000000000000000';//@RETORNADO
    $determinado_valor_euro_cotizado  = bcmul($determinado_cotizacion_euro,$valor_euro,4);//2+2
    $determinado_valor_euro_diario_cotizado   = '0.0000000000000000';//@RETORNADO
    
    if($dias_valor != 0){//No entra si es =0, nulo, o falta
      $devengado_valor_dolar_diario_cotizado = bcmul($devengado_valor_dolar_cotizado,$factor_dias_valor,16);//4+12
      $devengado_valor_euro_diario_cotizado  = bcmul($devengado_valor_euro_cotizado,$factor_dias_valor,16);//4+12
      $determinado_valor_dolar_diario_cotizado = bcmul($determinado_valor_dolar_cotizado,$factor_dias_valor,16);//4+12
      $determinado_valor_euro_diario_cotizado  = bcmul($determinado_valor_euro_cotizado,$factor_dias_valor,16);//4+12
    }
    
    $dias_lunes_jueves = 0;//@RETORNADO
    $dias_viernes_sabados = 0;//@RETORNADO
    $dias_domingos = 0;//@RETORNADO
    $dias_todos = 0;//@RETORNADO
    $dias_fijos = $R('dias_fijos',$D('fijos','0'));//@RETORNADO
    
    if($año_mes !== null){     
      $rangos = [
        'lunes_jueves' => [
          'calcular' => $D('lunes_jueves',true),
          'desde' => 1,
          'hasta' => 4,
          'count' => 0
        ],
        'viernes_sabados' => [
          'calcular' => $D('viernes_sabados',true),
          'desde' => 5,
          'hasta' => 6,
          'count' => 0
        ],
        'domingos' => [
          'calcular' => $D('domingos',true),
          'desde' => 0,
          'hasta' => 0,
          'count' => 0
        ],
        'mes' => [
          'calcular' => $D('mes',true),
          'desde' => 0,
          'hasta' => 6,
          'count' => 0
        ]
      ];

      {
        $año_mes_arr = explode('-',$año_mes);
        $dias_en_el_mes = cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));
        for($d=1;$d<=$dias_en_el_mes;$d++){
          $año_mes_arr[2] = $d;
          $f = new \DateTime(implode('-',$año_mes_arr));
          $wd = $f->format('w');
          foreach($rangos as $k => &$r){
            if(!$r['calcular']) continue;
            if($wd >= $r['desde'] && $wd <= $r['hasta']){
              $r['count'] = $r['count'] + 1;
            }
          }
        }
      }
      $dias_lunes_jueves = $R('dias_lunes_jueves',$rangos['lunes_jueves']['count']);
      $dias_viernes_sabados = $R('dias_viernes_sabados',$rangos['viernes_sabados']['count']);
      $dias_domingos = $R('dias_domingos',$rangos['domingos']['count']);
      $dias_todos = $R('dias_todos',$rangos['mes']['count']);
    }
    
    $mesas_lunes_jueves      = $RD('mesas_lunes_jueves',0);//@RETORNADO
    $mesas_viernes_sabados   = $RD('mesas_viernes_sabados',0);//@RETORNADO
    $mesas_domingos          = $RD('mesas_domingos',0);//@RETORNADO
    $mesas_todos             = $RD('mesas_todos',0);//@RETORNADO
    $mesas_fijos             = $RD('mesas_fijos',0);//@RETORNADO
        
    $mesas_dias = $dias_lunes_jueves*$mesas_lunes_jueves
    +$dias_viernes_sabados*$mesas_viernes_sabados
    +$dias_domingos*$mesas_domingos
    +$dias_todos*$mesas_todos
    +$dias_fijos*$mesas_fijos;//@RETORNADO
    
    $devengado_total_dolar   = '0';//@RETORNADO
    $devengado_total_euro    = '0';//@RETORNADO
    $determinado_total_dolar = '0';//@RETORNADO
    $determinado_total_euro  = '0';//@RETORNADO
    //Lo desprendo en sumas para hacerlo mas preciso (disminuyo las divisiones)
    //$total_MONEDA = $valor_diario_MONEDA * $mesas_dias
    //$total_MONEDA = ($valor_MONEDA*$cotizacion_MONEDA/$dias_valor) * $mesas_dias
    //$total_MONEDA = $valor_MONEDA*$cotizacion_MONEDA*($mesas_dias/$dias_valor)
    //$total_MONEDA = ($valor_MONEDA*$cotizacion_MONEDA)*($mesas_dias intdiv $dias_valor + ($mesas_dias % $dias_valor)/$dias_valor)
    //$total_MONEDA = ($valor_MONEDA*$cotizacion_MONEDA)*($mesas_dias intdiv $dias_valor + ($mesas_dias % $dias_valor)*$factor_dias_valor)
    //$total_MONEDA = $valor_mensual_MONEDA*($mesas_dias intdiv $dias_valor) + $valor_diario_MONEDA*($mesas_dias % $dias_valor)
    //$total_MONEDA = $valor_mensual_MONEDA*$mesas_meses + $valor_diario_MONEDA*$mesas_dias_restantes
    $devengado_total_dolar_cotizado   = '0';
    $devengado_total_euro_cotizado    = '0';
    $determinado_total_dolar_cotizado = '0';
    $determinado_total_euro_cotizado  = '0';
    if($dias_valor > 0){
      $mesas_meses = intdiv($mesas_dias,$dias_valor);
      $mesas_dias_restantes  = $mesas_dias % $dias_valor;
      
      //Esto en teoria aumenta la precision pero puede introducir errores de +-1... prefiero desactivarlo
      /*
      //Si es menor o igual a la mitad... lo hago como esta la formula arriba
      //Si es mayor a la mitad lo hago restando desde un multiplo mas... para disminuir el error de truncamiento
      if($mesas_dias_restantes > ($dias_valor/2.0)){
        $mesas_meses += 1;
        $mesas_dias_restantes = -($dias_valor-$mesas_dias_restantes);
      }
      */
      
      $devengado_total_dolar_cotizado = bcmul($devengado_valor_dolar_cotizado,$mesas_meses,4);
      $devengado_total_euro_cotizado  = bcmul($devengado_valor_euro_cotizado,$mesas_meses,4);
      $determinado_total_dolar_cotizado = bcmul($determinado_valor_dolar_cotizado,$mesas_meses,4);
      $determinado_total_euro_cotizado  = bcmul($determinado_valor_euro_cotizado,$mesas_meses,4);
      
      $devengado_total_dolar_cotizado = bcadd($devengado_total_dolar_cotizado,bcmul($devengado_valor_dolar_diario_cotizado,$mesas_dias_restantes,16),16);
      $devengado_total_euro_cotizado  = bcadd($devengado_total_euro_cotizado,bcmul($devengado_valor_euro_diario_cotizado,$mesas_dias_restantes,16),16);
      $determinado_total_dolar_cotizado = bcadd($determinado_total_dolar_cotizado,bcmul($determinado_valor_dolar_diario_cotizado,$mesas_dias_restantes,16),16);
      $determinado_total_euro_cotizado  = bcadd($determinado_total_euro_cotizado,bcmul($determinado_valor_euro_diario_cotizado,$mesas_dias_restantes,16),16);
    }
    
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);//@RETORNADO
    $determinado_ajuste  = bcadd($RD('determinado_ajuste','0.00'),'0',16);//@RETORNADO
    $devengado_total   = bcadd($devengado_total_dolar_cotizado,$devengado_total_euro_cotizado,16);//@RETORNADO
    $determinado_total = bcadd($determinado_total_dolar_cotizado,$determinado_total_euro_cotizado,16);//@RETORNADO
    $bruto = bcadd($R('bruto',$this->bruto($tipo,$año_mes,$id_operador)),'0',2);//@RETORNADO

    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
        
    $devengado   = bcsub($devengado_total,$devengado_deduccion,16);
    $determinado = bcadd($determinado_total,$determinado_ajuste,16);
    
    return compact(
      'tipo',
      'cuenta',
      'dias_valor','factor_dias_valor','valor_dolar','valor_euro',
      'dias_lunes_jueves','mesas_lunes_jueves','dias_viernes_sabados','mesas_viernes_sabados',
      'dias_domingos','mesas_domingos','dias_todos','mesas_todos','dias_fijos','mesas_fijos',
      'mesas_dias','bruto',
      'devengar',
      'devengado_fecha_cotizacion','devengado_cotizacion_dolar','devengado_cotizacion_euro',
      'devengado_valor_dolar_cotizado','devengado_valor_euro_cotizado',
      'devengado_valor_dolar_diario_cotizado','devengado_valor_euro_diario_cotizado',
      'devengado_total_dolar_cotizado','devengado_total_euro_cotizado','devengado_total',
      'devengado_deduccion','devengado',
      
      'determinado_fecha_cotizacion','determinado_cotizacion_dolar','determinado_cotizacion_euro',
      'determinado_valor_dolar_cotizado','determinado_valor_euro_cotizado',
      'determinado_valor_dolar_diario_cotizado','determinado_valor_euro_diario_cotizado',
      'determinado_total_dolar_cotizado','determinado_total_euro_cotizado','determinado_total',
      'determinado_ajuste','determinado'
    );
  }
  
  public function canon_fijo_mesas_adicionales_recalcular(
    $año_mes,
    $id_operador,
    $es_antiguo,
    $tipo,
    $data,
    $valores_defecto,
    $anterior,
    $COT
  ){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] === '') || ($data[$s] === []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] === '') || ($valores_defecto[$s] === []))? $dflt : $valores_defecto[$s];
    };
    $A = function($s,$dflt = null) use (&$anterior){
      return (($anterior[$s] ?? null) === null || ($anterior[$s] === '') || ($anterior[$s] === []))? $dflt : $anterior[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    $RAD = function($s,$dflt = null) use ($R,$A,$D){
      return $R($s,null) ?? $A($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $dias_mes      = $RD('dias_mes',0);//@RETORNADO
    $horas_dia     = $RD('horas_dia',0);//@RETORNADO
    
    $factor_dias_mes  = ($dias_mes != 0)? bcdiv('1',$dias_mes,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    $factor_horas_mes = ($horas_dia != 0 && $dias_mes != 0)? bcdiv('1',$horas_dia*$dias_mes,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    
    $valor_dolar = $COT['valor_dolar'] ?? null;//@RETORNADO
    $valor_euro  = $COT['valor_euro']  ?? null;//@RETORNADO
    
    $horas = $R('horas',0);//@RETORNADO
    $mesas = $R('mesas',0);//@RETORNADO
    if($horas != 0) $mesas = 0;
    if($mesas != 0) $horas = 0;
    
    $porcentaje = bcadd($RD('porcentaje','0.0000'),'0',4);//@RETORNADO
    $factor_porcentaje = bcdiv($porcentaje,'100',6);
        
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
    $cuenta = $RD('cuenta','');
    $devengado_fecha_cotizacion = $COT['devengado_fecha_cotizacion'] ?? null;//@RETORNADO
    $determinado_fecha_cotizacion = $COT['determinado_fecha_cotizacion'] ?? null;//@RETORNADO
    $devengado_cotizacion_dolar = $COT['devengado_cotizacion_dolar'] ?? '0';//@RETORNADO
    $devengado_cotizacion_euro = $COT['devengado_cotizacion_euro'] ?? '0';//@$RETORNADO
    $determinado_cotizacion_dolar = $COT['determinado_cotizacion_dolar'] ?? '0';//@RETORNADO
    $determinado_cotizacion_euro = $COT['determinado_cotizacion_euro'] ?? '0';//@RETORNADO
    
    $devengado_valor_mes = bcadd(
      bcmul($valor_dolar,$devengado_cotizacion_dolar,4),//2+2
      bcmul($valor_euro,$devengado_cotizacion_euro,4),//2+2
      4
    );//@RETORNADO
    $devengado_valor_dia  = bcmul($devengado_valor_mes,$factor_dias_mes,16);//4+12 @RETORNADO
    $devengado_valor_hora = bcmul($devengado_valor_mes,$factor_horas_mes,16);//4+12 @RETORNADO
    
    $determinado_valor_mes = bcadd(
      bcmul($valor_dolar,$determinado_cotizacion_dolar,4),//2+2
      bcmul($valor_euro,$determinado_cotizacion_euro,4),//2+2
      4
    );//@RETORNADO
    $determinado_valor_dia  = bcmul($determinado_valor_mes,$factor_dias_mes,16);//4+12 @RETORNADO
    $determinado_valor_hora = bcmul($determinado_valor_mes,$factor_horas_mes,16);//4+12 @RETORNADO
    
    $devengado_total_sin_aplicar_porcentaje = '0';
    $determinado_total_sin_aplicar_porcentaje = '0';
    {//Sumo de valores mas precisos a menos precisos
      $horas_aux = $horas != 0? $horas : bcmul($mesas,$horas_dia,0);
      $horas_mes = $horas_dia*$dias_mes;
      
      $meses = intdiv($horas_aux,$horas_mes);
      $horas_dias_restantes = $horas_aux%$horas_mes;
      
      $dias = intdiv($horas_dias_restantes,$horas_dia);
      $horas_restantes = $horas_dias_restantes%$horas_dia;
      
      $devengado_total_meses = bcmul($devengado_valor_mes,$meses,4);
      $devengado_total_dias  = bcmul($devengado_valor_dia,$dias,16);
      $devengado_total_horas = bcmul($devengado_valor_hora,$horas_restantes,16);
      $determinado_total_meses = bcmul($determinado_valor_mes,$meses,4);
      $determinado_total_dias  = bcmul($determinado_valor_dia,$dias,16);
      $determinado_total_horas = bcmul($determinado_valor_hora,$horas_restantes,16);
      
      $devengado_total_sin_aplicar_porcentaje = bcadd(
        bcadd(bcadd($devengado_total_sin_aplicar_porcentaje,$devengado_total_meses,16),$devengado_total_dias,16),$devengado_total_horas,16
      );
      $determinado_total_sin_aplicar_porcentaje = bcadd(
        bcadd(bcadd($determinado_total_sin_aplicar_porcentaje,$determinado_total_meses,16),$determinado_total_dias,16),$determinado_total_horas,16
      );
    }
        
    $devengado_total = bcmul($devengado_total_sin_aplicar_porcentaje,$factor_porcentaje,22);//16+6 @RETORNADO
    $determinado_total = bcmul($determinado_total_sin_aplicar_porcentaje,$factor_porcentaje,22);//16+6 @RETORNADO
    
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);//@RETORNADO
    $determinado_ajuste = bcadd($RD('determinado_ajuste','0.00'),'0',22);//@RETORNADO
    
    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
        
    $devengado   = bcsub($devengado_total,$devengado_deduccion,22);
    $determinado = bcadd($determinado_total,$determinado_ajuste,22);
    
    return compact(
      'tipo',
      'cuenta',
      'dias_mes','horas_dia','factor_dias_mes','factor_horas_mes',
      'valor_dolar','valor_euro',
      'horas','mesas','porcentaje',
      'devengar',
      'devengado_fecha_cotizacion','devengado_cotizacion_dolar','devengado_cotizacion_euro',
      'devengado_valor_mes','devengado_valor_dia','devengado_valor_hora',
      'devengado_total','devengado_deduccion',
      'devengado',
      
      'determinado_fecha_cotizacion','determinado_cotizacion_dolar','determinado_cotizacion_euro',
      'determinado_valor_mes','determinado_valor_dia','determinado_valor_hora',
      'determinado_total','determinado_ajuste',
      'determinado'
    );
  }
  
  public function adjuntar(Request $request){
    return $this->guardar($request,false);
  }
  
  public function guardar_arr(array $datos){
    $created_at = date('Y-m-d h:i:s');
    $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
    
    $canon_anterior = ($datos['año_mes'] !== null && $datos['id_operador'] !== null)?
      DB::table('canon')//Necesito la variable para despues sacarle los archivos
      ->select('id_canon')
      ->whereNull('deleted_at')
      ->where('año_mes',$datos['año_mes'])
      ->where('id_operador',$datos['id_operador'])
      ->orderBy('created_at','desc')
      ->get()
    : [];

    $id_canon = DB::table('canon')
    ->insertGetId([
      'año_mes' => $datos['año_mes'],
      'id_operador' => $datos['id_operador'],
      'estado' => $datos['estado'],
      'devengado_bruto' => $datos['devengado_bruto'],
      'devengado_deduccion' => $datos['devengado_deduccion'],
      'devengado' => $datos['devengado'],
      'porcentaje_seguridad' => $datos['porcentaje_seguridad'],
      'determinado_bruto' => $datos['determinado_bruto'],
      'determinado_ajuste' => $datos['determinado_ajuste'],
      'determinado' => $datos['determinado'],
      //Se rellenan despues en CanonPagoController
        'saldo_anterior' => 0,
        'saldo_anterior_cerrado' => 0,
        'intereses_y_cargos' => 0,
        'motivo_intereses_y_cargos' => '',
        'principal' => 0,
        'a_pagar' => 0,
        'pago' => 0,
        'ajuste' => 0,
        'motivo_ajuste' => '',
        'diferencia' => 0,
        'saldo_posterior_cerrado' => 0,
        'saldo_posterior' => 0,
      //-----------
      'es_antiguo' => $datos['es_antiguo'],
      'created_at' => $created_at,
      'created_id_usuario' => $id_usuario,
    ]);
                
    foreach(($datos['canon_variable'] ?? []) as $tipo => $d){
      $d['id_canon'] = $id_canon;
      $d['tipo'] = $tipo;
      unset($d['id_canon_variable']);
      DB::table('canon_variable')
      ->insert($d);
    }
    
    foreach(($datos['canon_fijo_mesas'] ?? []) as $tipo => $d){
      $d['id_canon'] = $id_canon;
      $d['tipo'] = $tipo;
      unset($d['id_canon_fijo_mesas']);
      DB::table('canon_fijo_mesas')
      ->insert($d);
    }
    
    foreach(($datos['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $d){
      $d['id_canon'] = $id_canon;
      $d['tipo']     = $tipo;
      unset($d['id_canon_fijo_mesas_adicionales']);
      DB::table('canon_fijo_mesas_adicionales')
      ->insert($d);
    }
          
    {
      $archivos_existentes = count($canon_anterior) == 0? 
        collect([])
      : DB::table('canon_archivo as ca')
      ->select('ca.descripcion','ca.type','a.*')
      ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
      ->where('id_canon',$canon_anterior[0]->id_canon)
      ->get()
      ->keyBy('id_archivo');
      
      $archivos_enviados = collect($datos['canon_archivo'] ?? [])->groupBy('id_archivo');
      $archivos_resultantes = [];
      foreach($archivos_enviados as $id_archivo_e => $archivos_e){
        if($id_archivo_e !== ''){//Es "existente"
          //Se recibio un id archivo que no estaba antes
          if(!$archivos_existentes->has($id_archivo_e)) continue;
          
          $archivo_bd = $archivos_existentes[$id_archivo_e];
          
          $archivo = null;//Por si me mando varios con el mismo id_archivo, busco el que tenga mismo nombre de archivo
          foreach($archivos_e as $ae){
            if($ae['nombre_archivo'] == $archivo_bd->nombre_archivo){
              $archivo = $ae;
              break;
            }
          }
          
          if($archivo === null) continue;//No encontre, lo ignoro
                      
          //El archivo se repite para el nuevo canon pero posiblemente con otra descripcion
          $archivos_resultantes[] = [
            'id_archivo'  => $archivo_bd->id_archivo,
            'id_canon'    => $id_canon,
            'descripcion' => ($archivo['descripcion'] ?? ''),
            'type'        => $archivo_bd->type,
          ];
        }
        else{//Archivos nuevos
          foreach($archivos_e as $a){
            $file=$a['file'] ?? null;
            if($file === null) continue;
            
            $archivo_bd = new Archivo;
            $data = base64_encode(file_get_contents($file->getRealPath()));
            $nombre_archivo = $file->getClientOriginalName();
            $archivo_bd->nombre_archivo = $nombre_archivo;
            $archivo_bd->archivo = $data;
            $archivo_bd->save();
            
            $archivos_resultantes[] = [
              'id_archivo' => $archivo_bd->id_archivo,
              'id_canon' => $id_canon,
              'descripcion' => ($a['descripcion'] ?? ''),
              'type' => $file->getMimeType() ?? 'application/octet-stream'
            ];
          } 
        }
      }
      
      DB::table('canon_archivo')
      ->insert($archivos_resultantes);
    }

    //Le saco los pagos
    $canon_pagos = [];
    if(count($canon_anterior) > 0){
      foreach($this->obtener_cuentas($id_canon) as $cuenta){
        $canon_pago[$cuenta] = $this->CCu->obtener_cuenta_por_id_canon($canon_anterior[0]->id_canon,$cuenta)['canon_pago'] ?? [];
      }
    }
    foreach($canon_anterior as $c){
      $this->borrar($c->id_canon,$created_at,$id_usuario);
    }
    
    foreach($this->obtener_cuentas($id_canon) as $cuenta){
      $cc = [
        'id_canon_cuenta' => null,
        'id_canon' => $id_canon,
        'cuenta' => $cuenta,
        'id_operador' => $datos['id_operador'],
        'año_mes' => $datos['año_mes'],
        'es_antiguo' => $datos['es_antiguo'],
        'estado' => $datos['estado'],
        'canon_pago' => $canon_pagos[$cuenta] ?? []
      ];
      $cc = $this->CCu->recalcular($cc);
      $this->CCu->guardar($cc,$created_at,$id_usuario);
    }


    CANON_STREAM_STR(false);
    $this->CA->recalcular_agrupamiento($datos['año_mes']);
    $this->recalcular_saldos($datos['id_operador'],$datos['año_mes']);
    
    return $id_canon;
  }
  
  public function guardar(Request $request,$recalcular = true){
    $requeridos = $recalcular? ['año_mes','id_operador','es_antiguo'] : ['id_canon'];
    $this->validarCanon($request->all(),$requeridos);
    
    Validator::make($request->all(),[], self::$errores,[])->after(function($validator){
      if($validator->errors()->any()) return;
      $D = $validator->getData();
      if(!isset($D['id_canon'])){//Nuevo
        $ya_existe = DB::table('canon')
        ->whereNull('deleted_at')
        ->where('año_mes',$D['año_mes'])
        ->where('id_operador',$D['id_operador'])
        ->count() > 0;
        
        if($ya_existe){
          $validator->errors()->add('año_mes','Ya existe un canon para ese periodo');
          $validator->errors()->add('id_operador','Ya existe un canon para ese periodo');
          return;
        }
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$recalcular){
      $datos = null;
      if($recalcular){
        $datos = $this->recalcular($request->all());
        $datos['estado'] = 'Generado';
      }
      else{
        $datos = $this->obtener_arr(['id_canon' => $request['id_canon']]);
        $datos['canon_archivo'] = $request['canon_archivo'] ?? [];
      }
      
      $id_canon = $this->guardar_arr($datos);
      
      return $id_canon;
    });
  }
  
  public function obtener_arr(array $request,$confluir = true){
    $ret = (array) (
      (isset($request['id_canon']) && $request['id_canon'] !== null)?
        $this->get_by_id_canon($request['id_canon'],false)
      : new \stdClass()
    );
    
    $ret['canon_variable'] = DB::table('canon_variable')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
    
    $ret['canon_fijo_mesas'] = DB::table('canon_fijo_mesas')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
        
    $ret['canon_fijo_mesas_adicionales'] = DB::table('canon_fijo_mesas_adicionales')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
    
    $ret['canon_archivo'] = DB::table('canon_archivo as ca')
    ->select('ca.id_canon','ca.descripcion','a.id_archivo','a.nombre_archivo')
    ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
    ->where('ca.id_canon',$request['id_canon'])
    ->orderBy('id_archivo','asc')
    ->get()
    ->transform(function(&$adj){
      $adj->link = '/canon/archivo?id_canon='.urlencode($adj->id_canon)
      .'&nombre_archivo='.urlencode($adj->nombre_archivo);
      return $adj;
    });
    
    $ret['canon_cuenta'] = $this->CCu->obtener_cuentas($request['id_canon']);
    
    $ret = json_decode(json_encode($ret),true);
    
    if($confluir){
      $COT = [
        'valor_dolar','valor_euro',
        'devengado_fecha_cotizacion','determinado_fecha_cotizacion',
        'devengado_cotizacion_dolar','devengado_cotizacion_euro',
        'determinado_cotizacion_dolar','determinado_cotizacion_euro'
      ];
      $ret = array_merge(
        $ret,
        $this->confluir_datos($ret,['canon_fijo_mesas','canon_fijo_mesas_adicionales'],$COT)
      );
    }
    
    return !empty($ret)? $ret : $this->recalcular($ret);
  }
  
  public function confluir_datos(array $array_of_tablas,array $tablas,$attrs = null){
    $ret = [];
    
    if($attrs === null){
      $attrs = [];
      foreach($tablas as $t){
        foreach(($array_of_tablas[$t] ?? []) as $tipo => $data_tipo){
          foreach($data_tipo as $attr => $_){
            $attrs[$attr] = true;
          }
        }
      }
      $attrs = array_keys($attrs);
    }
    
    foreach($tablas as $t){
      foreach(($array_of_tablas[$t] ?? []) as $tipo => $data_tipo){
        foreach($attrs as $attr){
          if(!isset($data_tipo[$attr])) continue;
          $v = $data_tipo[$attr];
          if(!isset($ret[$attr])){
            $ret[$attr] = $v;
          }
          else if($ret[$attr] != $v){
            $ret[$attr] = null;
          }
        }
      }
    }
    return $ret;
  }
  
  public function archivo(Request $request){
    if(($request['id_canon'] ?? null) === null || ($request['nombre_archivo'] ?? null) === null)
      return null;
    
    $a = DB::table('canon_archivo as ca')
    ->select('ca.type','a.*')
    ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
    ->where('ca.id_canon',$request['id_canon'])
    ->where('a.nombre_archivo',$request['nombre_archivo'])
    ->first();
    
    if($a === null) 
      return null;
    
    return \Response::make(
      base64_decode($a->archivo), 
      200, 
      [
        'Content-Type' => $a->type,
        'Content-Disposition' => 'inline; filename="'.$a->nombre_archivo.'"'
      ]
    );
  }
  
  public function obtener(Request $request){
    return $this->obtener_arr($request->all());
  }
  
  public function obtenerConHistorial(Request $request){
    $ultimo = $this->obtener($request);
    $ultimo['historial'] = ($ultimo['id_canon'] ?? null) !== null?
      DB::table('canon')
      ->select('created_at','id_canon')->distinct()
      ->where('año_mes',$ultimo['año_mes'])
      ->where('id_operador',$ultimo['id_operador'])
      ->orderBy('created_at','desc')
      ->get()->map(function($idc,$idc_idx){
        return $this->obtener_arr(['id_canon' => $idc->id_canon]);
      })
    : collect([]);
    return $ultimo;
  }
  
  public function borrar_Req(Request $request){
    $check_estado = !$this->u->es_superusuario;
    
    Validator::make($request->all(),[
      'id_canon' => ['required','integer','exists:canon,id_canon,deleted_at,NULL']
    ], ['exists' => 'No existe Canon eliminable'],[])->after(function($validator) use ($check_estado){
      if($validator->errors()->any()) return;
      $estado_bd = DB::table('canon')
      ->where('id_canon',$validator->getData()['id_canon'])
      ->whereNull('deleted_at')
      ->select('estado')
      ->first()
      ->estado;
      if($check_estado && !in_array($estado_bd,['Generado','Pagado'])){
        return $validator->errors()->add('estado','No puede borrar un Canon en estado '.$estado_bd);
      }
    })->validate();
    
    return DB::transaction(function() use ($request){
      $deleted_at = date('Y-m-d h:i:s');
      $deleted_id_usuario = $this->u->id_usuario;
      return $this->borrar($request['id_canon'],$deleted_at,$deleted_id_usuario);
    });
  }
  
  public function borrar($id_canon,$deleted_at,$deleted_id_usuario){    
    DB::table('canon')
    ->whereNull('deleted_at')
    ->where('id_canon',$id_canon)
    ->update(compact('deleted_at','deleted_id_usuario'));

    $this->CCu->borrar_cuentas($id_canon,$deleted_at,$deleted_id_usuario);
    
    $c = DB::table('canon')->select('id_operador','año_mes')->where('id_canon',$id_canon)
    ->first();
    if($c !== null){
      CANON_STREAM_STR(false);
      $this->CA->recalcular_agrupamiento($c->año_mes);
      $this->recalcular_saldos($c->id_operador,$c->año_mes);
    }
    
    return 1;
  }
    
  public function buscar(Request $request,bool $paginar = true){
    $reglas = [];
    if(isset($request->id_operador)){
      $reglas[] = ['c.id_operador','=',$request->id_operador];
    }
    
    $desde = date('Y-m-d');
    $hasta = $desde;
    {
      $minmax = DB::table('canon')->selectRaw('MIN(año_mes) as min_año_mes,MAX(año_mes) max_año_mes')
      ->groupBy(DB::raw('"constant"'))->first();
      if($minmax !== null){
        $desde = $minmax->min_año_mes;
        $hasta = $minmax->max_año_mes;
      }
    }
    if(isset($request->año_mes)){
      $desde = isset($request->año_mes[0])? $request->año_mes[0].'-01' : $desde;
      $hasta = isset($request->año_mes[1])? $request->año_mes[1].'-01' : $hasta;
    }
    $reglas[] = ['año_mes','>=',$desde];
    $reglas[] = ['año_mes','<=',$hasta];
    
    $sort_by = [
      'columna' => 'año_mes',
      'orden' => 'desc'
    ];
    
    if(!empty($request->sort_by) && !empty($request->sort_by['columna'])){
      $sort_by['columna'] = $request->sort_by['columna'];
      if(!empty($request->sort_by['orden'])){
        $sort_by['orden'] = $request->sort_by['orden'];
      }
    }
    
    $ret = DB::table('canon as c')
    ->select('c.id_canon','c.deleted_at',
      DB::raw('IF(c.es_antiguo,"ANT","") as antiguo'),
      DB::raw('DATE_FORMAT(c.año_mes,"%Y-%m") as año_mes'),
      'co.nombre as operador','c.estado','c.devengado','c.determinado',
      DB::raw('(
        c.intereses_y_cargos
        +(
          SELECT SUM(mora_provincial)+SUM(mora_nacional)
          FROM canon_pago as cp
          WHERE cp.id_canon = c.id_canon
          GROUP BY "constant"
          LIMIT 1
        )
      ) as intereses_y_cargos'),
      DB::raw('(
        SELECT SUM(cp.pago)
        FROM canon_pago as cp
        WHERE cp.id_canon = c.id_canon
        GROUP BY "constant"
        LIMIT 1
      ) as pago'),
      DB::raw('IF(COALESCE((
        SELECT SUM(ABS(cc.saldo_posterior))
        FROM canon_cuenta as cc
        WHERE cc.id_canon = c.id_canon
        GROUP BY "constant"
        LIMIT 1
      ),0) = 0,"🗸","!") as saldo_posterior')
    )
    ->join('canon_operador as co',function($j){
      return $j->on('co.id_operador','=','c.id_operador')->whereNull('co.deleted_at');
    })
    ->whereRaw(($this->u->es_superusuario && ($request->eliminados ?? false))?
      'NOT EXISTS (
        SELECT * 
        FROM canon c2 
        WHERE c2.id_operador = c.id_operador 
        AND   c2.año_mes   = c.año_mes 
        AND   (c2.deleted_at IS NULL OR c2.created_at > c.created_at)
        LIMIT 1
       )
       AND c.deleted_at IS NOT NULL'
    : 'c.deleted_at IS NULL'
    )
    ->where($reglas)
    ->whereIn('c.id_operador',$this->CPe->ids_operadores_usuario($this->u->id_usuario))
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->orderBy('co.nombre','asc');
    
    if($paginar){
      $ret = $ret->paginate($request->page_size ?? 10);
    }
    else {
      $ret = $ret->get();
    }
    
    $ret->transform(function($c){
      $c->cuentas = $this->CCu->obtener_cuentas($c->id_canon);
      return $c;
    });
    
    return $ret;
  }
    
  private $cotizacion_DB = null;
  private function cotizacion($fecha_cotizacion,$id_tipo_moneda,$id_operador){    
    if(empty($fecha_cotizacion) || empty($id_tipo_moneda)) return '0';
    if($id_tipo_moneda == 1){
      return 1;
    }
    
    if($this->cotizacion_DB === null){//Armo cotizacion_DB
      $fecha_cotizacion_arr = explode('-',$fecha_cotizacion);
      if($fecha_cotizacion_arr[1] == '01'){
        $fecha_cotizacion_arr[0] = str_pad(intval($fecha_cotizacion_arr[0])-1,4,'0',STR_PAD_LEFT);
        $fecha_cotizacion_arr[1] = '12';
      }
      else{
        $fecha_cotizacion_arr[1] = str_pad(intval($fecha_cotizacion_arr[1])-1,2,'0',STR_PAD_LEFT);
      }
      $fecha_cotizacion_arr[2] = '01';
      
      $q_base = DB::table('canon as c')//Busco en otros canons del mismo mes
      ->whereNull('c.deleted_at')
      ->where('c.id_operador','<>',$id_operador)
      ->where('c.año_mes',implode('-',$fecha_cotizacion_arr));//Para buscar entre menos
      
      $q_cfm = (clone $q_base)
      ->leftJoin('canon_fijo_mesas as cfm','cfm.id_canon','=','c.id_canon');
      $q_cfma = (clone $q_base)
      ->leftJoin('canon_fijo_mesas_adicionales as cfma','cfma.id_canon','=','c.id_canon');
      
      $vals_db = collect([])
      ->merge(
        (clone $q_cfm)->selectRaw('
          devengado_fecha_cotizacion as dev_fecha,
          NULLIF(devengado_cotizacion_dolar,"0") as dev_moneda_2,
          NULLIF(devengado_cotizacion_euro,"0") as dev_moneda_3,
          determinado_fecha_cotizacion as det_fecha,
          NULLIF(determinado_cotizacion_dolar,"0") as det_moneda_2,
          NULLIF(determinado_cotizacion_euro,"0") as det_moneda_3
        ')->get()
      )
      ->merge(
        (clone $q_cfma)->selectRaw('
          devengado_fecha_cotizacion as dev_fecha,
          NULLIF(devengado_cotizacion_dolar,"0") as dev_moneda_2,
          NULLIF(devengado_cotizacion_euro,"0") as dev_moneda_3,
          determinado_fecha_cotizacion as det_fecha,
          NULLIF(determinado_cotizacion_dolar,"0") as det_moneda_2,
          NULLIF(determinado_cotizacion_euro,"0") as det_moneda_3
        ')->get()
      );
      
      $this->cotizacion_DB = [];
      foreach($vals_db as $v){
        $this->cotizacion_DB[$v->dev_fecha] = $this->cotizacion_DB[$v->dev_fecha] ?? [2 => [],3 => []];
        $this->cotizacion_DB[$v->dev_fecha][2][$v->dev_moneda_2] = 1;
        $this->cotizacion_DB[$v->dev_fecha][3][$v->dev_moneda_3] = 1;
        
        $this->cotizacion_DB[$v->det_fecha] = $this->cotizacion_DB[$v->det_fecha] ?? [2 => [],3 => []];
        $this->cotizacion_DB[$v->det_fecha][2][$v->det_moneda_2] = 1;
        $this->cotizacion_DB[$v->det_fecha][3][$v->det_moneda_3] = 1;
      }
      
      //Si hay una cotizacion sola para la fecha, la guardo sino pongo en nulo
      foreach($this->cotizacion_DB as $fcot => $cots){
        foreach($cots as $idtm => $valores_moneda){
          if(count($valores_moneda) > 1 || count($valores_moneda) == 0){
            $this->cotizacion_DB[$fcot][$idtm] = null;
          }
          else{
            $this->cotizacion_DB[$fcot][$idtm] = array_keys($valores_moneda)[0];
            $this->cotizacion_DB[$fcot][$idtm] = empty($this->cotizacion_DB[$fcot][$idtm])? 
            null
            : $this->cotizacion_DB[$fcot][$idtm];
          }
        }
      }
    }
    
    //Si existe una cotización comun en la DB, devuelvo esa
    $cot = ($this->cotizacion_DB[$fecha_cotizacion] ?? [])[$id_tipo_moneda] ?? null;
    if($cot !== null) return $cot;
    
    if($id_tipo_moneda == 2){//Busco en las cotizaciones de los auditores
      $cotizacion = DB::table('cotizacion as cot')
      ->where('fecha',$fecha_cotizacion)
      ->first();
      if($cotizacion !== null){
        return $cotizacion->valor;
      }
    }
    
    return '0';
  }
  
  private function apostado($tipo,$año_mes,$id_operador){
    if($año_mes === null || $tipo === null || $id_operador === null) return null;
    
    $operador = $this->CO->operadores([$id_operador])[0] ?? null;
    if($operador === null){
      return null;
    }
    
    $id_casino = Casino::where('codigo',$operador->codigo_casino)->first();
    if($id_casino === null){
      return null;
    }
    $id_casino = $id_casino->id_casino;
    
    $año_mes_arr = explode('-',$año_mes);
    switch($tipo){
      case 'Maquinas':{
        $resultado = DB::table('beneficio as b')
        ->selectRaw('SUM(b.coinin*IF(b.id_tipo_moneda = 1,1,CAST(cot.valor AS DECIMAL(20,6)))) as valor')
        ->leftJoin('cotizacion as cot',function($q){
          return $q->where('b.id_tipo_moneda',2)->on('b.fecha','=','cot.fecha');
        })
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      }break;
    }
    return null;
  }
  
  public function bruto($tipo,$año_mes,$id_operador){//@TODO: modularizar
    if($año_mes === null || $tipo === null || $id_operador === null) return null;
    
    $operador = $this->CO->operadores([$id_operador])[0] ?? null;
    if($operador === null){
      return null;
    }
    
    $id_casino = Casino::where('codigo',$operador->codigo_casino)->first();
    if($id_casino === null){
      return null;
    }
    $id_casino = $id_casino->id_casino;
    
    $año_mes_arr = explode('-',$año_mes);
    switch($tipo){
      case 'Maquinas':{
        $resultado = DB::table('beneficio as b')
        ->selectRaw('SUM(b.valor*IF(b.id_tipo_moneda = 1,1,CAST(cot.valor AS DECIMAL(20,6)))) as valor')
        ->leftJoin('cotizacion as cot',function($q){
          return $q->where('b.id_tipo_moneda',2)->on('b.fecha','=','cot.fecha');
        })
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      }break;
      case 'Bingo':{
        $resultado = DB::table('bingo_importacion as b')
        ->selectRaw('SUM(b.recaudado-b.premio_bingo-b.premio_linea) as valor')
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      };
      case 'JOL':{
        $JOL_connect_config = $this->CVD->get('JOL_connect_config') ?? null;
        $debug = $JOL_connect_config['debug'] ?? false;
        if(empty($JOL_connect_config)) return $debug? '-0.99' : null;
        if(empty($JOL_connect_config['ip_port'])) return $debug? '-0.98' : null;
        if(empty($JOL_connect_config['API-Token']))  return $debug? '-0.97' : null;
        
        set_time_limit(5);
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, "http://{$JOL_connect_config['ip_port']}/api/bruto");
        curl_setopt($ch, CURLOPT_POST, 1);
        //@TODO: cambiar JOL para que tome el id_plataforma (o el codigo plataforma)
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(compact('año_mes','id_casino')));
        //curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_PROXY, NULL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'API-Token: '.$JOL_connect_config['API-Token']
        ]);
        
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if($result === false){//Error de curl https://curl.se/libcurl/c/libcurl-errors.html
          $ret = $debug? (-curl_errno($ch)).'.99' : null;
          curl_close($ch);
          return $ret;
        }
        curl_close($ch);
        
        if($code != 200){
          return $debug? (-$code).'.98' : null;
        }
                
        return $result;
      }break;
      case 'Mesas':
      case 'Fijas':
      case 'Diarias': {
        $resultado = DB::table('importacion_diaria_mesas as idm')
        ->selectRaw('SUM(idm.utilidad*IF(idm.id_moneda = 1,1,CAST(cot.valor AS DECIMAL(20,6)))) as valor')
        ->leftJoin('cotizacion as cot',function($q){
          return $q->where('idm.id_moneda',2)->on('idm.fecha','=','cot.fecha');
        })
        ->whereNull('idm.deleted_at')
        ->where('idm.id_casino',$id_casino)
        ->whereYear('idm.fecha',$año_mes_arr[0])
        ->whereMonth('idm.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      }break;
    }
    return null;
  }
  
  public function cambiarEstado(Request $request){
    Validator::make($request->all(),[
      'id_canon' => ['required','integer','exists:canon,id_canon,deleted_at,NULL'],
      'estado' => ['required','string','in:Generado,Pagado,Cerrado'],
    ], self::$errores,[
      'in.estado' => 'El valor de estado no es correcto',
    ])->after(function($validator){
      if($validator->errors()->any()) return;
      $id_canon = $validator->getData()['id_canon'];
      $estado_db = DB::table('canon')->select('estado')->where('id_canon',$id_canon)->first()->estado;
      $estado = $validator->getData()['estado'];
      $validos = ['Generado' => ['Pagado'],'Pagado' => ['Cerrado','Generado'],'Cerrado' => ['Pagado']];
      if(!in_array($estado,$validos[$estado_db] ?? [])){
        return $validator->errors()->add('estado','Transición de estado incorrecta.');
      }
    })->validate();
    
    //@HACK: usar CoW/SoftDelete?
    return DB::transaction(function() use ($request){
      DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$request->id_canon)
      ->update(['estado' => $request->estado]);

      $this->CCu->cambiarEstadoCuentas($request->id_canon,$request->estado);
      
      return 1;
    });
  }
  
  public function desborrar(Request $request){//Se valida superusuario en el ruteo
    Validator::make($request->all(),[
      'id_canon' => ['required','integer','exists:canon,id_canon'],
    ],self::$errores,[])->validate();
    
    return DB::transaction(function() use ($request){
      DB::table('canon')
      ->where('id_canon',$request->id_canon)
      ->update(['deleted_at' => null]);
      
      $c = DB::table('canon')->select('id_operador','año_mes')->where('id_operador',$request->id_canon)
      ->first();
      if($c !== null){
        $this->CA->recalcular_agrupamiento($c->año_mes);
        $this->CPa->cambio_determinado($c->id_operador,$c->año_mes);
      }
      
      return 1;
    });
  }
    
  public function get_by_id_canon(int $id_canon,bool $active = true){    
    return DB::table('canon')
    ->select('canon.*','co.nombre as operador','usuario.user_name as usuario')
    ->join('usuario','usuario.id_usuario','=','canon.created_id_usuario')
    ->join('canon_operador as co','co.id_operador','=','canon.id_operador')
    ->where('canon.id_canon',$id_canon)
    ->where(DB::raw($active? '(canon.deleted_at IS NULL)' : '1'),'=','1')
    ->first();
  }
  public function get_by_id_operador_año_mes(int $id_operador,string $año_mes,bool $active = true){
    return DB::table('canon')
    ->select('canon.*','co.nombre as operador','usuario.user_name as usuario')
    ->join('usuario','usuario.id_usuario','=','canon.created_id_usuario')
    ->join('canon_operador as co','co.id_operador','=','canon.id_operador')
    ->where('canon.id_operador',$id_operador)
    ->where('canon.año_mes',$año_mes)
    ->where(DB::raw($active? '(canon.deleted_at IS NULL)' : '1'),'=','1');
  }
  public function get_prev_by_id_operador_año_mes(int $id_operador,string $año_mes){
    return DB::table('canon')
    ->select('canon.*','co.nombre as operador','usuario.user_name as usuario')
    ->join('usuario','usuario.id_usuario','=','canon.created_id_usuario')
    ->join('canon_operador as co','co.id_operador','=','canon.id_operador')
    ->where('canon.id_operador',$id_operador)
    ->where('canon.año_mes','<',$año_mes)
    ->whereNull('canon.deleted_at')
    ->orderBy('canon.año_mes','desc');
  }

  public function obtener_subcanons_por_cuenta(int $id_canon, string $cuenta){
    $subcanons = $this->subcanons();
    $ret = [];
    foreach($subcanons as $sc){
      $ret[$sc] = DB::table($sc)
      ->select('tipo')
      ->where('cuenta',$cuenta)
      ->where('id_canon',$id_canon)
      ->get()
      ->pluck('tipo')->toArray();
    }
    return $ret;
  }

  public function obtener_cuentas(int $id_canon){
    $subcanons = $this->subcanons();
    $ret = [];
    foreach($subcanons as $sc){
      $cuentas_sc = DB::table($sc)
      ->select('cuenta')->distinct()
      ->where('id_canon',$id_canon)
      ->get();
      foreach($cuentas_sc as $cuenta){
        $ret[$cuenta->cuenta] = true;
      }
    }
    return array_keys($ret);
  }

  //Recalcula saldos POSTERIORES a año_mes, esto es porque desde recalcular() ya se calcula el saldo de ese año_mes
  public function recalcular_saldos($id_operador,$año_mes){
    static $cuentas = null;
    $cuentas = $cuentas ?? $this->CCu->cuentas();

    foreach($cuentas as $c){
      $this->CCu->recalcular_saldos_operador_año_mes_cuenta($id_operador,$año_mes,$c);
    }

    $canons = DB::table('canon')
    ->select('id_canon')
    ->whereNull('deleted_at')
    ->where('año_mes','>',$año_mes)
    ->where('id_operador',$id_operador)
    ->get();

    foreach($canons as $c){
      $this->recalcularSaldoTotalizadoCanon($c->id_canon);
    }
  }

  public function recalcularSaldoTotalizadoCanon($id_canon){
    DB::table('canon')
    ->where('id_canon',$id_canon)
    ->update(
      $this->CCu->totalizarSaldosCuentas($id_canon)
    );
  }

  public function recalcular_saldos_Req(Request $request){
    return DB::transaction(function(){
      $operadores = $this->CO->operadores();
      foreach($operadores as $o){
        $this->recalcular_saldos($o->id_operador,'1970-01-01');
      }
      return 1;
    });
  }
}
