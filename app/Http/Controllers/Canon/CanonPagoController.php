<?php

namespace App\Http\Controllers\Canon;

use Illuminate\Http\Request;
use Validator;
use App\Casino;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;

require_once(app_path('BC_extendido.php'));

class CanonPagoController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
  
  private $u = null;
  private $CC = null;
  private $CV = null;
  private $CO = null;
  public function __construct(){
    self::$instance = $this;
    $this->CC = CanonController::getInstancia();
    $this->CV = CanonValorPorDefectoController::getInstancia();
    $this->CO = CanonOperarioController::getInstancia();
    $this->middleware(function ($request, $next) {
      $this->u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      return $next($request);
    });
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
      'año_mes' => ['required','regex:/^\d{4}\-((0\d)|(1[0-2]))\-01$/'],
      'id_casino' => ['required','integer','exists:casino,id_casino,deleted_at,NULL'],
      'estado' => ['nullable','string','max:32'],
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
  
  private function recalcular(array $request){
    $R = function($s,$dflt = null) use (&$request){
      return (($request[$s] ?? null) === null || ($request[$s] === '') || ($request[$s] === []))? $dflt : $request[$s];
    };
    
    $año_mes = $R('año_mes');//@RETORNADO
    $id_casino = $R('id_casino');//@RETORNADO
    $estado = $R('estado');//@RETORNADO
    
    $co = $this->CO->obtener_operario($id_casino);
    $cuenta = [];
    foreach($co['cuentas'] ?? [] as $c){
      $cuenta = $c;
      break;//@TODO: cambiar para cuando se haga pago en multiples cuentas
    }
    //@TODO: Obtener de agrupamientos
    $determinado = $R('determinado','0.00');//@RETORNADO
    
    //PRINCIPAL
    $c_ant = DB::table('canon')
    ->where('año_mes','<',$año_mes)
    ->where('id_casino','=',$id_casino)
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->first();
        
    $saldo_anterior = ($c_ant !== null)? $c_ant->saldo_posterior : '0';//@RETORNADO
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
      'interes_provincial_diario_simple' => $R(
        'interes_provincial_diario_simple',
        $cuenta['interes_provincial_diario_simple'] ?? '0'
      ),
      'interes_nacional_mensual_compuesto' => $R(
        'interes_nacional_mensual_compuesto',
        $cuenta['interes_nacional_mensual_compuesto'] ?? '0'
      ),
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
        str_pad(   $cuenta['dia_vencimiento'] ?? 10,2,'0',STR_PAD_LEFT)
      ]);
      
      $PAG['fecha_vencimiento'] = $this->CO->mover_fecha(
        new \DateTimeImmutable($PAG['fecha_vencimiento']),
        $cuenta['fin_de_semana']
      )->format('Y-m-d');
    }
    
    $timestamp_venc = $PAG['fecha_vencimiento']?
      \DateTimeImmutable::createFromFormat('Y-m-d', $PAG['fecha_vencimiento'])
    : null;
    $factor_interes_provincial_diario_simple = bcdiv($PAG['interes_provincial_diario_simple'],'100',6);
    $factor_interes_nacional_mensual_compuesto = bcdiv($PAG['interes_nacional_mensual_compuesto'],'100',6);
    
    $restante = $principal;
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
    }
    
    $PAG = $this->confluir_datos_pago(compact('canon_pago'));
    $interes_provincial_diario_simple   = $PAG['interes_provincial_diario_simple'] ?? null;//@RETORNADO
    $interes_nacional_mensual_compuesto = $PAG['interes_nacional_mensual_compuesto'] ?? null;//@RETORNADO
    $fecha_vencimiento                  = $PAG['fecha_vencimiento'] ?? null;//@RETORNADO
    
    $ajuste = bcadd($R('ajuste','0.00'),'0',2);//@RETORNADO
    $motivo_ajuste = $R('motivo_ajuste','');//@RETORNADO
    $diferencia = bcadd(bcsub($a_pagar,$pago,2),$ajuste,2);//@RETORNADO
    $saldo_posterior = bcsub('0',$diferencia,2);//@RETORNADO @HACK: Lo mismo que diferencia? el saldo ya esta en el a_pagar
    $saldo_posterior_cerrado = $saldo_posterior;//@RETORNADO
    
    return compact(
      'año_mes','id_casino','estado','determinado',
      'saldo_anterior','saldo_anterior_cerrado',
      'intereses_y_cargos','motivo_intereses_y_cargos','principal',
      //Confluidos
      'fecha_vencimiento','interes_provincial_diario_simple','interes_nacional_mensual_compuesto',
      //Pagos
      'canon_pago',
      'a_pagar','pago','ajuste','motivo_ajuste','diferencia',
      'saldo_posterior','saldo_posterior_cerrado'
    );
  }
  
  public function guardar_arr(array $datos){    
    DB::table('canon')
    ->where('id_canon',$datos['id_canon'])
    ->update([
      'saldo_anterior' => $datos['saldo_anterior'],
      'saldo_anterior_cerrado' => $datos['saldo_anterior_cerrado'],
      'intereses_y_cargos' => $datos['intereses_y_cargos'],
      'motivo_intereses_y_cargos' => $datos['motivo_intereses_y_cargos'] ?? '',
      'principal' => $datos['principal'],
      'a_pagar' => $datos['a_pagar'],
      'pago' => $datos['pago'],
      'ajuste' => $datos['ajuste'],
      'motivo_ajuste' => $datos['motivo_ajuste'] ?? '',
      'diferencia' => $datos['diferencia'],
      'saldo_posterior_cerrado' => $datos['saldo_posterior_cerrado'],
      'saldo_posterior' => $datos['saldo_posterior']
    ]);
    
    foreach(($datos['canon_pago'] ?? []) as $idx => $d){
      DB::table('canon_pago')
      ->insert([
        'id_canon' => $datos['id_canon'] ?? $d['id_canon'],
        'capital' => $d['capital'],
        'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? $d['fecha_vencimiento'],
        'fecha_pago' => $d['fecha_pago'],
        'dias_vencidos' => $d['dias_vencidos'],
        'interes_provincial_diario_simple' => $datos['interes_provincial_diario_simple'] ?? $d['interes_provincial_diario_simple'],
        'interes_nacional_mensual_compuesto' => $datos['interes_nacional_mensual_compuesto'] ?? $d['interes_nacional_mensual_compuesto'],
        'mora_provincial' => $d['mora_provincial'],
        'mora_nacional' => $d['mora_nacional'],
        'a_pagar' => $d['a_pagar'],
        'pago' => $d['pago'],
        'diferencia' => $d['diferencia'],
      ]);
    }
    
    $this->cambio_determinado($datos['id_casino'],$datos['año_mes']);
    
    return 1;
  }
  
  public function guardar(Request $request){
    $this->validar($request->all());
    return DB::transaction(function() use ($request){
      //Genero un nuevo canon
      $canon = $this->CC->obtener_arr(['id_canon' => $request['id_canon']]);
      $canon['canon_pago'] = [];
      $nuevo_id_canon = $this->CC->guardar_arr($canon);
      //Le inserta un pago por defecto asi que lo borro
      DB::table('canon_pago')->where('id_canon',$nuevo_id_canon)->delete();
      $request_arr = $request->all();
      $request_arr['id_canon'] = $nuevo_id_canon;//Le parcheo el nuevo id
      $this->guardar_arr($request_arr);//Guardo este nuevo canon
      return $nuevo_id_canon;
    });
  }
  
  //@HACK: usar CoW/SoftDelete?
  private function recalcular_saldos($saldo_posterior_prev,$año_mes,$id_casino){
    $canons = DB::table('canon')
    ->whereNull('deleted_at')
    ->where('año_mes','>',$año_mes)
    ->where('id_casino','=',$id_casino)
    ->orderBy('año_mes','asc')->get();
    
    if(count($canons) <= 0) return;
        
    foreach($canons as $c){
      if(defined('DUMP_CANONS')){
        dump($c);
      }
      //Si esta cerrado, solo actualizo los saldos "no cerrados" y que se use en un canon proximo
      if(in_array(strtoupper($c->estado),['PAGADO','CERRADO'])){
        $c->saldo_anterior = $saldo_posterior_prev;
        $diffsaldos = bcsub($c->saldo_anterior,$c->saldo_anterior_cerrado,2);
        $c->saldo_posterior = bcadd($c->saldo_posterior_cerrado,$diffsaldos,2);
        
        DB::table('canon')
        ->where('id_canon',$c->id_canon)
        ->update([
          'saldo_anterior' => $c->saldo_anterior,
          'saldo_posterior' => $c->saldo_posterior
        ]);
        
        $saldo_posterior_prev = $c->saldo_posterior;
        
        if(defined('DUMP_CANONS')){
          dump([
            'saldo_anterior' => $c->saldo_anterior,
            'saldo_posterior' => $c->saldo_posterior
          ]);
        }
      }
      else{//El saldo influye en el principal y por ende en todos los calculos de pagos
        $c_para_recalcular = $this->obtener_arr(['id_canon' => $c->id_canon]);
        $c_para_recalcular['saldo_anterior'] = $saldo_posterior_prev;
        $c_para_recalcular['saldo_anterior_cerrado'] = $saldo_posterior_prev;
                
        $datos = $this->recalcular($c_para_recalcular);
        
        DB::table('canon')
        ->where('id_canon',$c->id_canon)
        ->update([
          'saldo_anterior' => $datos['saldo_anterior'],
          'saldo_anterior_cerrado' => $datos['saldo_anterior_cerrado'],
          'principal' => $datos['principal'],
          'a_pagar' => $datos['a_pagar'],
          'diferencia' => $datos['diferencia'],
          'saldo_posterior_cerrado' => $datos['saldo_posterior_cerrado'],
          'saldo_posterior' => $datos['saldo_posterior'],
        ]);
        
        $pagos_bd = DB::table('canon_pago')
        ->where('id_canon',$c->id_canon)
        ->get()->keyBy('id_canon_pago');
        
        $pagos_actualizados = collect($datos['canon_pago'])
        ->keyBy('id_canon_pago');
        
        assert($pagos_bd->keys()->sort() == $pagos_actualizados->keys()->sort());
        
        foreach($pagos_bd as $id_canon_pago => $pbd){
          $pact = $pagos_actualizados[$id_canon_pago];
          
          DB::table('canon_pago')
          ->where('id_canon_pago',$id_canon_pago)
          ->update([
            'capital' => $pact['capital'],
            'mora_provincial' => $pact['mora_provincial'],
            'mora_nacional' => $pact['mora_nacional'],
            'a_pagar' => $pact['a_pagar'],
            'diferencia' => $pact['diferencia']
          ]);
        }
        
        $saldo_posterior_prev = $datos['saldo_posterior'];
        
        if(defined('DUMP_CANONS')){
          dump($datos);
        }
      }
    }
  }
  
  public function recalcular_saldos_Req(Request $request){
    DB::transaction(function(){
      define('DUMP_CANONS',1);
      foreach(Casino::all() as $c){
        $this->recalcular_saldos('0','1970-01-01',$c->id_casino);
      }
      return 1;
    });
  }
  
  public function obtener_arr(array $request,$confluir = true){
    $ret = (array) DB::table('canon as c')
    ->select('cas.nombre as casino','c.*','u.user_name as usuario')
    ->join('usuario as u','u.id_usuario','=','c.created_id_usuario')
    ->join('casino as cas','cas.id_casino','=','c.id_casino')
    ->where('id_canon',$request['id_canon'])
    ->first();
    
    $ret['canon_pago'] = DB::table('canon_pago')
    ->where('id_canon',$request['id_canon'])
    ->orderBy('fecha_pago','asc')
    ->get();
        
    $ret = json_decode(json_encode($ret),true);
    
    if($confluir){
      $PAG = $this->confluir_datos_pago($ret);
      foreach($PAG as $k => $v) $ret[$k] = $v;
    }
    
    return $ret;
  }
  
  private function confluir_datos(array $canon,array $tablas,array $atributos){
    $ret = [];
    foreach($tablas as $tabla){
      foreach($atributos as $attr){
        foreach($canon[$tabla] as $tipo => $data_tabla){
          if(!isset($data_tabla[$attr])) continue;
          $val = $data_tabla[$attr];
          if(isset($ret[$attr])){//Si es distinto, hay conflicto y pongo en nulo
            $ret[$attr] = $val != $ret[$attr]? null : $val;
          }
          else{
            $ret[$attr] = $val;
          }
        }
      }
    }
    return $ret;
  }
  
  private function confluir_datos_pago(array $canon){
    return $this->confluir_datos(
      $canon,
      ['canon_pago'],
      ['fecha_vencimiento','interes_provincial_diario_simple','interes_nacional_mensual_compuesto']
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
      ->where('id_casino',$ultimo['id_casino'])
      ->orderBy('created_at','desc')
      ->get()->map(function($idc,$idc_idx){
        return $this->obtener_arr(['id_canon' => $idc->id_canon]);
      })
    : collect([]);
    return $ultimo;
  }
  
  public function cambio_determinado($id_casino,$año_mes){
    $cprev = $this->CC->get_prev_by_id_casino_año_mes($id_casino,$año_mes)->first();
    if($cprev){
      $this->recalcular_saldos($cprev->saldo_posterior,$cprev->año_mes,$id_casino);
    }
    else{
      $this->recalcular_saldos('0','1970-01-01',$id_casino);
    }
  }
  
  public function traspasar_pagos($cviejo,$c){
    if($cviejo === null){
      $this->asegurar_que_tenga_pago($c);
      return;
    }
    
    $cviejo_arr = $this->obtener_arr(['id_canon' => $cviejo->id_canon]);
    if(empty($cviejo_arr['canon_pago'] ?? [])){
      $this->asegurar_que_tenga_pago($c);
      return;
    }
    
    $carr = $this->obtener_arr(['id_canon' => $c->id_canon]);    
    $carr['canon_pago'] = $cviejo_arr['canon_pago'];
    foreach($carr['canon_pago'] as $cpidx => $cp){
      $carr['canon_pago'][$cpidx]['id_canon'] = $c->id_canon;
    }
    
    $this->guardar_arr($carr);
  }
  
  private function asegurar_que_tenga_pago($c){
    if($c === null) return;
    
    //Esto le genera un pago si no tiene      
    $datos = $this->recalcular($this->obtener_arr(['id_canon' => $c->id_canon]));
    if(count($datos['canon_pago'] ?? []) == 0){
      throw new \Exception('No puede haber canon sin pago asociado');
    }
    foreach($datos['canon_pago'] as $idx => $d){
      DB::table('canon_pago')
      ->insert([
        'id_canon' => $c->id_canon,
        'capital' => $d['capital'],
        'fecha_vencimiento' => $d['fecha_vencimiento'],
        'fecha_pago' => $d['fecha_pago'],
        'dias_vencidos' => $d['dias_vencidos'],
        'interes_provincial_diario_simple' => $d['interes_provincial_diario_simple'],
        'interes_nacional_mensual_compuesto' => $d['interes_nacional_mensual_compuesto'],
        'mora_provincial' => $d['mora_provincial'],
        'mora_nacional' => $d['mora_nacional'],
        'a_pagar' => $d['a_pagar'],
        'pago' => $d['pago'],
        'diferencia' => $d['diferencia']
      ]);
    }
  }
}
