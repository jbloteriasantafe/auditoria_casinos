<?php

namespace App\Http\Controllers\Canon;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Schema;

require_once(app_path('BC_extendido.php'));

class CanonCuentaController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
  
  private $CC = null;
  private $CO = null;
  private $CGO = null;
  private $CAgg = null;
  public function __construct(){
    self::$instance = $this;
    $this->CC = CanonController::getInstancia();
    $this->CO = CanonOperadorController::getInstancia();
    $this->CGO = CanonGrupoOperadorController::getInstancia();
    $this->CAgg = CanonAgrupamientoController::getInstancia();
    $this->middleware(function ($request, $next) {
      $this->u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      return $next($request);
    });
  }
  
  public function down(){
    DB::unprepared("DROP TABLE IF EXISTS canon_cuenta");
    if(Schema::hasColumn('canon_pago', 'id_canon_cuenta')){
      DB::unprepared("ALTER TABLE `canon_pago` DROP INDEX idx_canon_pago_canon_id_cuenta");
      DB::unprepared("ALTER TABLE `canon_pago` DROP `id_canon_cuenta`");
    }
    //La cuenta y los timestamps no se limpian para poder revincular en remigraciones (down-up)
    CANON_STREAM_STR('CANON_PAGO: DOWN');
  }
  
  public function up(){
    if(!Schema::hasColumn('canon_pago', 'cuenta')){
      DB::statement("ALTER TABLE canon_pago 
        ADD cuenta 
          VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin 
          NOT NULL 
          DEFAULT '' 
          AFTER id_canon
      ");
      DB::statement("ALTER TABLE `bdMTM`.`canon_pago` ADD INDEX idx_canon_pago_canon_cuenta (id_canon,cuenta)");
    }

    if(!Schema::hasColumn('canon_pago', 'id_canon_cuenta')){
      DB::statement("ALTER TABLE canon_pago 
        ADD id_canon_cuenta INT NULL AFTER id_canon
      ");
      DB::statement("ALTER TABLE `bdMTM`.`canon_pago` ADD INDEX idx_canon_pago_canon_id_cuenta (id_canon,id_canon_cuenta)");
    }

    if(!Schema::hasColumn('canon_pago', 'created_at')){
      DB::statement("ALTER TABLE canon_pago 
        ADD created_at TIMESTAMP NULL AFTER diferencia,
        ADD created_by INT NULL AFTER created_at,
        ADD deleted_at TIMESTAMP NULL AFTER created_by,
        ADD deleted_by INT NULL AFTER deleted_at
      ");
    }
    
    DB::statement("
    CREATE TABLE IF NOT EXISTS canon_cuenta (
      id_canon_cuenta INT NOT NULL AUTO_INCREMENT,
      id_canon INT NOT NULL,
      
      -- Valores del canon(id_canon) para facilitar la busqueda/indexacion/implementación en frontend
      id_operador INT NOT NULL,
      año_mes DATE NOT NULL,
      estado varchar(32) NOT NULL,
      es_antiguo tinyint(1) NOT NULL,
      
      -- Instanciado a partir de canon_operador_cuenta
      -- Estos valores se inundan a los pagos (porque quedo asi de legacy)
      cuenta VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
      fecha_vencimiento DATE NOT NULL,                          
      interes_provincial_diario_simple DECIMAL(7,4) NOT NULL,   
      interes_nacional_mensual_compuesto DECIMAL(7,4) NOT NULL, 
      
      -- determinado obtenido de CanonAgrupamiento (de subcanons) usando la clave 'cuenta' y valor <cuenta>
      determinado DECIMAL(20,2) NOT NULL, 
      saldo_anterior DECIMAL(20,2) NOT NULL, 
      saldo_anterior_cerrado DECIMAL(20,2) NOT NULL, -- Si estado=Cerrado los cambios se congela aqui y cualquier modificación que cambie saldo_anterior se pasa a saldo_posterior
      intereses_y_cargos DECIMAL(20,2) NOT NULL,
      motivo_intereses_y_cargos VARCHAR(128) NOT NULL,
      
      -- Valor principal al que se le van descontar los pagos
      principal DECIMAL(20,2) NOT NULL,     
      
      -- Valores totalizados de los pagos
      mora_provincial DECIMAL(20,2) NOT NULL,
      mora_nacional DECIMAL(20,2) NOT NULL,
      a_pagar DECIMAL(20,2) NOT NULL,
      pago DECIMAL(20,2) NOT NULL,
      
      -- Valores introducidos
      ajuste DECIMAL(20,2) NOT NULL,
      motivo_ajuste VARCHAR(128) NOT NULL,
      
      -- Lo que queda en cuenta
      diferencia DECIMAL(20,2) NOT NULL, 
      saldo_posterior_cerrado DECIMAL(20,2) NOT NULL,
      saldo_posterior DECIMAL(20,2) NOT NULL,
      
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      deleted_at TIMESTAMP NULL DEFAULT NULL,
      created_by INT NOT NULL,
      deleted_by INT DEFAULT NULL,
      
      PRIMARY KEY (id_canon_cuenta),
      
      INDEX `unq_canon_cuenta` (`id_canon`,`cuenta`,`deleted_at`),
      INDEX `unq_canon_cuenta2` (`id_operador`,`año_mes`,`cuenta`,`deleted_at`,`id_canon`),
      
      KEY `fk_canon_cuenta_created_by` (`created_by`),
      KEY `fk_canon_cuenta_deleted_by` (`deleted_by`),
      CONSTRAINT `fk_canon_cuenta_canon` FOREIGN KEY (`id_canon`) REFERENCES `canon` (`id_canon`),
      CONSTRAINT `fk_canon_cuenta_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `usuario` (`id_usuario`),
      CONSTRAINT `fk_canon_cuenta_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id_usuario`)
    )
    ");

    DB::statement("
      UPDATE canon_pago as cp
      JOIN canon as c ON c.id_canon = cp.id_canon
      SET
        cp.created_at = c.created_at,
        cp.deleted_at = c.deleted_at,
        cp.created_by = c.created_id_usuario,
        cp.deleted_by = c.deleted_id_usuario
      WHERE cp.created_at IS NULL
    ");

    CANON_STREAM_STR('CANON_PAGO: UP');    
  }
  
  public function llenado_inicial($created_at,$created_by){//argumentos no usados porque se usan los valores ya cargados
    DB::unprepared("INSERT INTO canon_cuenta
    (
      id_canon,
      cuenta,
      id_canon_cuenta,
      id_operador,año_mes,estado,es_antiguo,
      fecha_vencimiento,
      interes_provincial_diario_simple,
      interes_nacional_mensual_compuesto,
      determinado,
      saldo_anterior,saldo_anterior_cerrado,
      intereses_y_cargos,motivo_intereses_y_cargos,
      principal,
      mora_provincial,mora_nacional,
      a_pagar,pago,ajuste,motivo_ajuste,
      diferencia,
      saldo_posterior_cerrado,saldo_posterior,
      created_at,
      deleted_at,
      created_by,
      deleted_by
    )
    SELECT
      c.id_canon,
      cp.cuenta,
      cp.id_canon_cuenta, -- Null en la primera llamada, al insertarse se le genera un número
      c.id_operador,c.año_mes,c.estado,c.es_antiguo,
      IF(
        MIN(cp.fecha_vencimiento) = MAX(cp.fecha_vencimiento),
        MIN(cp.fecha_vencimiento),
        c.año_mes
      ) as fecha_vencimiento,
      IF(
        MIN(cp.interes_provincial_diario_simple) = MAX(cp.interes_provincial_diario_simple),
        MIN(cp.interes_provincial_diario_simple),
        0
      ) as interes_provincial_diario_simple,
      IF(
        MIN(cp.interes_nacional_mensual_compuesto) = MAX(cp.interes_nacional_mensual_compuesto),
        MIN(cp.interes_nacional_mensual_compuesto),
        0
      ) as interes_nacional_mensual_compuesto,
      c.determinado,
      c.saldo_anterior, c.saldo_anterior_cerrado,
      c.intereses_y_cargos, c.motivo_intereses_y_cargos,
      c.principal,
      SUM(cp.mora_provincial) as mora_provincial,
      SUM(cp.mora_nacional) as mora_nacional,
      c.a_pagar,c.pago,c.ajuste,c.motivo_ajuste,
      c.diferencia,
      c.saldo_posterior_cerrado,c.saldo_posterior,
      c.created_at,
      c.deleted_at,
      c.created_id_usuario as created_by,
      c.deleted_id_usuario as created_by
      
      FROM canon as c
      JOIN canon_pago as cp ON cp.id_canon = c.id_canon
      GROUP BY c.id_canon,cp.cuenta,cp.id_canon_cuenta
    ");

    //Tengo que hacer esto para re-vincularlos si hago un down/up
    DB::unprepared("
      UPDATE canon_pago as cp 
      JOIN canon_cuenta as cc ON cc.id_canon = cp.id_canon
        AND cc.created_at = cp.created_at
        AND COALESCE(cc.deleted_at,'') = COALESCE(cp.deleted_at,'')
        AND cc.created_by = cp.created_by
        AND COALESCE(cc.deleted_by,'') = COALESCE(cp.deleted_by,'')
      SET 
        cp.id_canon_cuenta = cc.id_canon_cuenta
      WHERE cp.id_canon_cuenta IS NULL
    ");
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
    
  private function validar(array $request){
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

    Validator::make($request,[
      //canon
      'id_canon' => ['required','integer','exists:canon,id_canon,deleted_at,NULL'],
      'id_canon_cuenta' => ['required','integer','exists:canon_cuenta,id_canon_cuenta,deleted_at,NULL'],
      'año_mes' => ['required','regex:/^\d{4}\-((0\d)|(1[0-2]))\-01$/'],
      'id_operador' => ['required','integer','exists:canon_operador,id_operador,deleted_at,NULL'],
      'estado' => ['nullable','string','max:32'],
      'cuenta' => ['nullable','string','max:64'],
      'es_antiguo' => ['required','integer','in:1,0'],
      'intereses_y_cargos' => ['nullable',$numeric_rule(2)],
      'motivo_intereses_y_cargos' => ['nullable','string','max:128'],
      'fecha_vencimiento' => ['nullable','date'],
      'interes_provincial_diario_simple' => ['nullable',$numeric_rule(4)],
      'interes_nacional_mensual_compuesto' => ['nullable',$numeric_rule(4)],
      'canon_pago.*.fecha_pago' => ['nullable','date'],
      'canon_pago.*.pago' => ['nullable',$numeric_rule(2)],
      'ajuste' => ['nullable',$numeric_rule(2)],
      'motivo_ajuste' => ['nullable','string','max:128']
    ], self::$errores,[])->after(function($validator){
      if($validator->errors()->any()) return;
    })->validate();
  }
  
  public function recalcular_req(Request $request){
    $R = $request->all();
    $this->validar($R);
    return $this->recalcular($R);
  }
  
  public function recalcular(array $request){
    $R = function($s,$dflt = null) use (&$request){
      return (($request[$s] ?? null) === null || ($request[$s] === '') || ($request[$s] === []))? $dflt : $request[$s];
    };

    $año_mes = $R('año_mes','');//@RETORNADO
    if(empty($año_mes)){
      throw new \Exception('año_mes es requerido');
    }

    $id_operador = $R('id_operador','');//@RETORNADO
    if(empty($id_operador)){
      throw new \Exception('id_operador es requerido');
    }

    $op = $this->CO->obtener_operador($id_operador);
    $cuenta = $R('cuenta','');//@RETORNADO
    $cuenta_dflt = $op['cuentas'][$cuenta] ?? [];

    $D = function($s,$dflt = null) use (&$cuenta_dflt){
      return (($cuenta_dflt[$s] ?? null) === null || ($cuenta_dflt[$s] === '') || ($cuenta_dflt[$s] === []))? $dflt : $cuenta_dflt[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };

    $id_canon = $R('id_canon',null);//@RETORNADO
    $id_canon_cuenta = $R('id_canon_cuenta',null);//@RETORNADO
    $estado = $R('estado','');//@RETORNADO
    
    $es_antiguo = $R('es_antiguo',0);//@RETORNADO
    
    $determinado = '0.00';
    {
      /*
      Creo un agrupamiento temporal para poder calcular el determinado de la cuenta
      {
        "Cuenta $id_canon_cuenta": {
          "base": {
            "Total": {
              $igo: {
                "$sc": [$sc[0],$sc[1],...]
                .
                .
                .
              }
            }
          }
        }
      }
      */
      DB::transaction(function() use ($id_operador,$id_canon,$id_canon_cuenta,$cuenta,$año_mes,&$determinado){
        $igo = $this->CGO->obtener_igo_individual($id_operador);
        $agrupamiento = [];
        $Total = [];
        $subcanons = $this->CC->obtener_subcanons_por_cuenta($id_canon,$cuenta);
        foreach($subcanons as $sc => $tipos){
          $Total[$igo][$sc] = $tipos;
        }
        $clave = 'Cuenta '.($id_canon_cuenta ?? uniqid());
        $agrupamiento = [
          'base' => ['Total' => $Total],
        ];
        $agrupamientos = [];
        $agrupamientos[$clave] = $agrupamiento;

        $this->CAgg->guardarAgrupamientos($agrupamientos);
        $this->CAgg->recalcular_clave_año_mes($clave,$año_mes);
        $agg = $this->CAgg->obtenerCalculadoNivel($año_mes,$igo,$clave,0);
        $determinado = $agg['Total']->{'determinado¬pos_red'} ?? '0.00';
        $this->CAgg->borrarAgrupamiento($clave);
      });
    }

    $c_cuenta_ant = $this->get_prev_by_id_operador_año_mes_cuenta($id_operador,$año_mes,$cuenta)->first();
        
    $saldo_anterior = ($c_cuenta_ant !== null)? $c_cuenta_ant->saldo_posterior : '0';//@RETORNADO
    $saldo_anterior_cerrado = $saldo_anterior;//@RETORNADO
    
    $intereses_y_cargos = bcadd($R('intereses_y_cargos','0'),'0',2);//@RETORNADO
    $motivo_intereses_y_cargos = $R('motivo_intereses_y_cargos','');//@RETORNADO
    $principal = bcsub(bcadd($determinado,$intereses_y_cargos,2),$saldo_anterior_cerrado,2);//@RETORNADO
    
    //PAGOS
    $canon_pago = $request['canon_pago'] ?? [];
    $canon_pago = empty($canon_pago)? [[]] : $canon_pago;//Si no tiene pagos le agrego uno vacio.
    {//Manteno las keys y el orden de las keys... importante para el front cuando se borra/cambia fecha etc
      $ordenado_por_fecha = json_decode(json_encode($canon_pago),true);
      usort($ordenado_por_fecha,function($a,$b){//Lo ordeno por fecha de pago
        $fa = $a['fecha_pago'] ?? null;
        $fb = $b['fecha_pago'] ?? null;
        
        if(!empty($fa) &&  empty($fb)) return -1;
        if( empty($fa) && !empty($fb)) return  1;
        if( empty($fa) &&  empty($fb)){
          return 0;
        }
        return $fa <= $fb? -1 : 1;
      });
      $keys = array_keys($canon_pago);
      foreach($ordenado_por_fecha as $idx => $v){
        $canon_pago[$keys[$idx]] = $v;
      }
    }
    
    $a_pagar = $principal;//@RETORNADO
    $pago = '0';//@RETORNADO    
    $PAG = [
      'interes_provincial_diario_simple' => $RD('interes_provincial_diario_simple','0'),
      'interes_nacional_mensual_compuesto' => $RD('interes_nacional_mensual_compuesto','0'),
      'fecha_vencimiento' => $R('fecha_vencimiento',null),
    ];
    if($año_mes !== null && $año_mes !== '' && $PAG['fecha_vencimiento'] === null){
      $f = explode('-',$año_mes);
      
      $f[0] = intval($f[0]);
      $f[1] = intval($f[1]);
      $f[2] = intval($f[2]);
      
      $f[0] = str_pad($f[1] == 12?  ($f[0]+1) :     $f[0],4,'0',STR_PAD_LEFT);
      $f[1] = str_pad($f[1] == 12?         1  : ($f[1]+1),2,'0',STR_PAD_LEFT);
      
      $PAG['fecha_vencimiento'] = implode('-',[
        $f[0],
        $f[1],
        str_pad(   $cuenta_dflt['dia_vencimiento'] ?? 10,2,'0',STR_PAD_LEFT)
      ]);
      
      $PAG['fecha_vencimiento'] = $this->CO->mover_fecha(
        new \DateTimeImmutable($PAG['fecha_vencimiento']),
        $D('fin_de_semana','Sin Movimiento')
      )->format('Y-m-d');
    }
    
    $timestamp_venc = $PAG['fecha_vencimiento']?
      \DateTimeImmutable::createFromFormat('Y-m-d', $PAG['fecha_vencimiento'])
    : null;
    $factor_interes_provincial_diario_simple = bcdiv($PAG['interes_provincial_diario_simple'],'100',6);
    $factor_interes_nacional_mensual_compuesto = bcdiv($PAG['interes_nacional_mensual_compuesto'],'100',6);
    
    $restante = $principal;
    $mora_provincial = '0';
    $mora_nacional   = '0';
    foreach($canon_pago as $idx => &$p){
      $p['capital'] = $restante;
      $p['fecha_pago'] = $p['fecha_pago'] ?? $PAG['fecha_vencimiento'] ?? null;
      $p['fecha_vencimiento'] = $PAG['fecha_vencimiento'] ?? null;
      $p['interes_provincial_diario_simple'] = $PAG['interes_provincial_diario_simple'] ?? null;
      $p['interes_nacional_mensual_compuesto'] = $PAG['interes_nacional_mensual_compuesto'] ?? null;
      
      if($timestamp_venc && $p['fecha_pago'] != null){
        $timestamp_pago = \DateTimeImmutable::createFromFormat('Y-m-d', $p['fecha_pago']);
        $date_interval  = $timestamp_pago->diff($timestamp_venc);
        $p['dias_vencidos'] = ($timestamp_pago <= $timestamp_venc)? 0
        : intval($date_interval->days);
      }
      else{
        $p['dias_vencidos'] = 0;
      }
      
      $p['mora_provincial'] = bcmul($p['dias_vencidos'],bcmul($p['capital'],$factor_interes_provincial_diario_simple,8),8);
      $p['mora_provincial'] = bcround_ndigits($p['mora_provincial'],2);
      $p['mora_provincial'] = bcmax($p['mora_provincial'],'0',2);
      
      $p['mora_nacional'] = '0';
      {
        $capitalizaciones = intdiv($p['dias_vencidos'],30);
        $capital_final    = $p['capital'];
        $interes_nacional = bcadd('1',$factor_interes_nacional_mensual_compuesto,6);
        $digitos_capital_final = 2;
        for($c=0;$c<$capitalizaciones;$c++){
          $capital_final = bcmul($capital_final,$interes_nacional,$digitos_capital_final+6);
        }
        $p['mora_nacional'] = bcsub($capital_final,$p['capital'],$digitos_capital_final);
        $p['mora_nacional'] = bcround_ndigits($p['mora_nacional'],2);
      }
      $p['mora_nacional'] = bcmax($p['mora_nacional'],'0',2);
      
      $p['a_pagar'] = bcadd($p['capital'],$p['mora_provincial'],2);
      $p['a_pagar'] = bcadd($p['a_pagar'],$p['mora_nacional'],2);
      $p['pago'] = $p['pago'] ?? '0';
      $p['diferencia'] = bcsub($p['a_pagar'],$p['pago'],2);
      
      $a_pagar = bcadd($a_pagar,$p['mora_provincial'],2);
      $a_pagar = bcadd($a_pagar,$p['mora_nacional'],2);
      $pago = bcadd($pago,$p['pago'],2);
      $restante = $p['diferencia'];

      $mora_provincial = bcadd_precise($mora_provincial,$p['mora_provincial']);
      $mora_nacional = bcadd_precise($mora_nacional,$p['mora_nacional']);
    }
    
    $PAG = $this->CC->confluir_datos(compact('canon_pago'),['canon_pago'],array_keys($PAG));
    
    $ajuste = bcadd($R('ajuste','0.00'),'0',2);//@RETORNADO
    $motivo_ajuste = $R('motivo_ajuste','');//@RETORNADO
    $diferencia = bcadd(bcsub($a_pagar,$pago,2),$ajuste,2);//@RETORNADO
    $saldo_posterior = bcsub('0',$diferencia,2);//@RETORNADO @HACK: Lo mismo que diferencia? el saldo ya esta en el a_pagar
    $saldo_posterior_cerrado = $saldo_posterior;//@RETORNADO
    
    return array_merge(
      compact(
        'id_canon','id_canon_cuenta',
        'año_mes','id_operador','estado','cuenta','es_antiguo',
        'determinado',
        'saldo_anterior','saldo_anterior_cerrado',
        'intereses_y_cargos','motivo_intereses_y_cargos','principal',
        //Pagos
        'canon_pago',
        'mora_provincial',
        'mora_nacional',
        'a_pagar','pago','ajuste','motivo_ajuste','diferencia',
        'saldo_posterior','saldo_posterior_cerrado'
      ),
      $PAG
    );
  }

  private function get_prev_by_id_operador_año_mes_cuenta($id_operador,$año_mes,$cuenta){
    return DB::table('canon_cuenta')
    ->select('canon_cuenta.*','co.nombre as operador','usuario.user_name as usuario')
    ->join('usuario','usuario.id_usuario','=','canon_cuenta.created_by')
    ->join('canon_operador as co','co.id_operador','=','canon_cuenta.id_operador')
    ->where('canon_cuenta.id_operador',$id_operador)
    ->where('canon_cuenta.año_mes','<',$año_mes)
    ->where('canon_cuenta.cuenta','=',$cuenta)
    ->whereNull('canon_cuenta.deleted_at')
    ->orderBy('canon_cuenta.año_mes','desc');
  }

  
  public function guardar(array $datos,$timestamp,$id_usuario){    
    $cc_anteriores = ($datos['año_mes'] !== null && $datos['id_operador'] !== null && $datos['cuenta'] !== null)?
      DB::table('canon_cuenta')
      ->select('id_canon_cuenta')
      ->whereNull('deleted_at')
      ->where('año_mes',$datos['año_mes'])
      ->where('id_operador',$datos['id_operador'])
      ->where('cuenta',$datos['cuenta'])
      ->orderBy('created_at','desc')
      ->get()
    : [];
    
    foreach($cc_anteriores as $cc){
      $this->borrar($cc->id_canon_cuenta,$timestamp,$id_usuario);
    }

    $id_canon_cuenta = DB::table('canon_cuenta')
    ->insertGetId([
      'id_canon' => $datos['id_canon'],
      'id_operador' => $datos['id_operador'],
      'año_mes' => $datos['año_mes'],
      'estado' => $datos['estado'],
      'es_antiguo' => $datos['es_antiguo'],
      'cuenta' => $datos['cuenta'],
      'fecha_vencimiento' => $datos['fecha_vencimiento'],
      'interes_provincial_diario_simple' => $datos['interes_provincial_diario_simple'],
      'interes_nacional_mensual_compuesto' => $datos['interes_nacional_mensual_compuesto'],
      'determinado' => $datos['determinado'],
      'saldo_anterior' => $datos['saldo_anterior'],
      'saldo_anterior_cerrado' => $datos['saldo_anterior_cerrado'],
      'intereses_y_cargos' => $datos['intereses_y_cargos'],
      'motivo_intereses_y_cargos' => $datos['motivo_intereses_y_cargos'],
      'principal' => $datos['principal'],
      'mora_provincial' => $datos['mora_provincial'],
      'mora_nacional' => $datos['mora_nacional'],
      'a_pagar' => $datos['a_pagar'],
      'pago' => $datos['pago'],
      'ajuste' => $datos['ajuste'],
      'motivo_ajuste' => $datos['motivo_ajuste'],
      'diferencia' => $datos['diferencia'],
      'saldo_posterior' => $datos['saldo_posterior'],
      'saldo_posterior_cerrado' => $datos['saldo_posterior_cerrado'],
      'created_at' => $timestamp,
      'created_by' => $id_usuario,
      'deleted_at' => null,
      'deleted_by' => null
    ]);

    foreach(($datos['canon_pago'] ?? []) as $d){
      DB::table('canon_pago')
      ->insert([
        'id_canon_cuenta' => $id_canon_cuenta,
        'id_canon' => $datos['id_canon'],
        'cuenta' => $datos['cuenta'],
        'capital' => $d['capital'],
        'fecha_vencimiento' => $datos['fecha_vencimiento'],
        'fecha_pago' => $d['fecha_pago'],
        'dias_vencidos' => $d['dias_vencidos'],
        'interes_provincial_diario_simple' => $datos['interes_provincial_diario_simple'],
        'interes_nacional_mensual_compuesto' => $datos['interes_nacional_mensual_compuesto'],
        'mora_provincial' => $d['mora_provincial'],
        'mora_nacional' => $d['mora_nacional'],
        'a_pagar' => $d['a_pagar'],
        'pago' => $d['pago'],
        'diferencia' => $d['diferencia'],
        'created_at' => $timestamp,
        'created_by' => $id_usuario,
        'deleted_at' => null,
        'deleted_by' => null
      ]);
    }

    return 1;
  }
  
  public function guardar_req(Request $request){
    $this->validar($request->all());
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;

      $datos = $this->recalcular($request->all());
      $id_canon_cuenta = $this->guardar($datos,$created_at,$id_usuario);
      $this->CC->recalcular_saldos($datos['id_operador'],$datos['año_mes']);

      return $id_canon_cuenta;
    });
  }

  public function actualizar_determinado($id_canon_cuenta,$created_at,$id_usuario){
    $cc = $this->obtener($id_canon_cuenta);
    $cc = $this->recalcular($cc);
    return $this->guardar($cc,$created_at,$id_usuario);
  }

  public function borrar($id_canon_cuenta,$deleted_at,$deleted_by){
    DB::table('canon_cuenta')
    ->whereNull('deleted_at')
    ->where('id_canon_cuenta',$id_canon_cuenta)
    ->update(compact('deleted_at','deleted_by'));

    DB::table('canon_pago')
    ->whereNull('deleted_at')
    ->where('id_canon_cuenta',$id_canon_cuenta)
    ->update(compact('deleted_at','deleted_by'));
    return 1;
  }

  public function borrar_cuentas($id_canon,$deleted_at,$deleted_by){
    $cuentas = $this->obtener_cuentas($id_canon);
    foreach($cuentas as $cc){
      $this->borrar($cc['id_canon_cuenta'],$deleted_at,$deleted_by);
    }
    return 1;
  }
    
  public function obtener_req(Request $request){
    return $this->obtener($request['id_canon_cuenta'] ?? '');
  }
  
  public function obtenerConHistorial_req(Request $request){
    $ultimo = $this->obtener($request['id_canon_cuenta']);
    $ultimo['historial'] = ($ultimo['id_canon_cuenta'] ?? null) !== null?
      DB::table('canon_cuenta')
      ->select('created_at','id_canon_cuenta')->distinct()
      ->where('año_mes',$ultimo['año_mes'])
      ->where('id_operador',$ultimo['id_operador'])
      ->where('cuenta',$ultimo['cuenta'])
      ->orderBy('created_at','desc')
      ->get()->map(function($idc,$idc_idx){
        return $this->obtener($idc->id_canon_cuenta);
      })
    : collect([]);
    return $ultimo;
  }

  public function obtener($id_canon_cuenta){
    $ret = DB::table('canon_cuenta as cc')
    ->select('cc.*','co.nombre as operador','u.user_name as usuario')
    ->where('cc.id_canon_cuenta',$id_canon_cuenta)
    ->join('usuario as u','u.id_usuario','=','cc.created_by')
    ->join('canon_operador as co','co.id_operador','=','cc.id_operador')
    ->first();

    $ret = $ret ?? (new \stdClass());
    $ret->canon_pago = isset($ret->id_canon_cuenta)?
      DB::table('canon_pago')
      ->where('id_canon_cuenta',$ret->id_canon_cuenta)
      ->orderBy('fecha_pago','asc')
      ->get()
    : collect([]);

    $ret = json_decode(json_encode($ret),true);
        
    return $ret;
  }

  public function obtener_cuenta_por_id_canon($id_canon,$cuenta){
    $ret = DB::table('canon_cuenta as cc')
    ->select('cc.*','co.nombre as operador','u.user_name as usuario')
    ->where('cc.id_canon',$id_canon)
    ->where('cc.cuenta',$cuenta)
    ->whereNull('cc.deleted_at')
    ->join('usuario as u','u.id_usuario','=','cc.created_by')
    ->join('canon_operador as co','co.id_operador','=','cc.id_operador')
    ->first();

    $ret = $ret ?? (new \stdClass());
    $ret->canon_pago = isset($ret->id_canon_cuenta)?
      DB::table('canon_pago')
      ->where('id_canon_cuenta',$ret->id_canon_cuenta)
      ->orderBy('fecha_pago','asc')
      ->get()
    : collect([]);

    $ret = json_decode(json_encode($ret),true);
        
    return $ret;
  }
  
  public function obtener_cuentas($id_canon){
    return DB::table('canon_cuenta')
    ->where('id_canon',$id_canon)
    ->whereNull('deleted_at')->get()
    ->map(function($cuenta){
      $cuenta->pagos = DB::table('canon_pago')->where('id_canon_cuenta',$cuenta->id_canon_cuenta)
      ->where('cuenta',$cuenta->cuenta)->whereNull('deleted_at')->get()->toArray();
      return (array) $cuenta;
    })->toArray();
  }
  
  public function cuentas($ids_operadores = null){
    $ret = DB::table('canon_cuenta')
    ->select('cuenta')->distinct()
    ->whereNull('deleted_at');
    
    if($ids_operadores !== null){
      $ret = $ret->whereIn('id_operador',$ids_operadores);
    }
    
    return $ret->orderBy('cuenta','asc')
    ->get()->pluck('cuenta')->toArray();
  }
  
  //Recalcula saldos POSTERIORES a año_mes, esto es porque desde recalcular() ya se calcula el saldo de ese año_mes
  public function recalcular_saldos_operador_año_mes_cuenta($id_operador,$año_mes,$cuenta){
    $canon_cuenta_anterior = DB::table('canon_cuenta')
    ->select('saldo_posterior')
    ->whereNull('deleted_at')
    ->where('id_operador',$id_operador)
    ->where('cuenta',$cuenta)
    ->where('año_mes','<=',$año_mes)
    ->orderBy('año_mes','desc')
    ->first();

    $saldo_posterior_prev = $canon_cuenta_anterior? $canon_cuenta_anterior->saldo_posterior : '0';

    $canon_cuentas = DB::table('canon_cuenta')
    ->select('id_canon_cuenta','estado','saldo_anterior_cerrado','saldo_posterior_cerrado')
    ->whereNull('deleted_at')
    ->where('id_operador',$id_operador)
    ->where('cuenta',$cuenta)
    ->where('año_mes','>',$año_mes)
    ->orderBy('año_mes','asc')
    ->get();

    foreach($canon_cuentas as $cc){
      if(in_array(strtoupper($cc->estado),['PAGADO','CERRADO'])){
        $saldo_anterior = $saldo_posterior_prev;
        $diferencia_saldos = bcsub($saldo_anterior,$cc->saldo_anterior_cerrado,2);
        $saldo_posterior = bcadd($cc->saldo_posterior_cerrado,$diferencia_saldos,2);

        DB::table('canon_cuenta')
        ->where('id_canon_cuenta',$cc->id_canon_cuenta)
        ->update(compact('saldo_anterior','saldo_posterior'));

        $saldo_posterior_prev = $saldo_posterior;
        continue;
      }

      $cuenta_para_recalcular = $this->obtener($cc->id_canon_cuenta);
      $cuenta_para_recalcular['saldo_anterior'] = $saldo_posterior_prev;
      $cuenta_para_recalcular['saldo_anterior_cerrado'] = $saldo_posterior_prev;

      $datos = $this->recalcular($cuenta_para_recalcular);

      DB::table('canon_cuenta')
      ->where('id_canon_cuenta',$cc->id_canon_cuenta)
      ->update([
        'saldo_anterior' => $datos['saldo_anterior'],
        'saldo_anterior_cerrado' => $datos['saldo_anterior_cerrado'],
        'principal' => $datos['principal'],
        'a_pagar' => $datos['a_pagar'],
        'diferencia' => $datos['diferencia'],
        'saldo_posterior_cerrado' => $datos['saldo_posterior_cerrado'],
        'saldo_posterior' => $datos['saldo_posterior'],
      ]);

      $pagos_actualizados = collect($datos['canon_pago'] ?? [])
      ->keyBy('id_canon_pago');

      $pagos_bd = DB::table('canon_pago')
      ->where('id_canon_cuenta',$cc->id_canon_cuenta)
      ->get();
      foreach($pagos_bd as $pago){
        $pago_actualizado = $pagos_actualizados->get($pago->id_canon_pago);
        if($pago_actualizado === null) continue;

        DB::table('canon_pago')
        ->where('id_canon_pago',$pago->id_canon_pago)
        ->update([
          'capital' => $pago_actualizado['capital'],
          'mora_provincial' => $pago_actualizado['mora_provincial'],
          'mora_nacional' => $pago_actualizado['mora_nacional'],
          'a_pagar' => $pago_actualizado['a_pagar'],
          'diferencia' => $pago_actualizado['diferencia']
        ]);
      }

      $saldo_posterior_prev = $datos['saldo_posterior'];
    }

    return 1;
  }

  public function totalizarSaldosCuentas($id_canon){
    //@LEGACY: el canon antes tenia los saldos y demases, ahora son por cuenta, por ahora lo calculo simplemente sumando
    //Suma absolutos para que las diferencias no se cancelen y se pueda mostrar en el front si hay saldos abiertos
    $ret = DB::table('canon_cuenta')
    ->selectRaw('
      SUM(ABS(saldo_anterior)) as saldo_anterior,
      SUM(ABS(saldo_anterior_cerrado)) as saldo_anterior_cerrado,
      SUM(ABS(intereses_y_cargos)) as intereses_y_cargos,
      "" as motivo_intereses_y_cargos,
      SUM(ABS(principal)) as principal,
      SUM(ABS(a_pagar)) as a_pagar,
      SUM(ABS(pago)) as pago,
      SUM(ABS(ajuste)) as ajuste,
      "" as motivo_ajuste,
      SUM(ABS(diferencia)) as diferencia,
      SUM(ABS(saldo_posterior_cerrado)) as saldo_posterior_cerrado,
      SUM(ABS(saldo_posterior)) as saldo_posterior
    ')
    ->where('id_canon',$id_canon)
    ->whereNull('deleted_at')
    ->groupBy('id_canon')
    ->first();
    return $ret === null? [] : ((array)$ret);
  }

  public function cambiarEstadoCuentas($id_canon,$estado){
    return DB::table('canon_cuenta')
    ->whereNull('deleted_at')
    ->where('id_canon',$id_canon)
    ->update(['estado' => $estado]);
  }
}
