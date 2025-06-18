<?php

namespace App\Http\Controllers\Canon;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use PDF;
use Dompdf\Dompdf;
use View;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Plataforma;
use App\Casino;

require_once(app_path('BC_extendido.php'));

class CanonController extends Controller
{
  private static $instance;
  
  private $subcanons = [];
  private $canon_pago = null;
  private $canon_archivo = null;
  public function __construct($subcanons = null,$canon_pago = null,$canon_archivo = null){
    $this->canon_pago = $canon_pago ?? CanonPagoController::getInstancia();
    $this->canon_archivo = $canon_archivo ?? CanonArchivoController::getInstancia();
    $subcanons = $subcanons ?? [
      CanonVariableController::getInstancia(),
      CanonFijoMesasController::getInstancia(),
      CanonFijoMesasAdicionalesController::getInstancia()
    ];
    foreach($subcanons as $sc){
      $this->subcanons[$sc->table] = $sc;
    }
  }
  
  public static function getInstancia(){
    if(!isset(self::$instance)){
      self::$instance = new self();
    }
    return self::$instance;
  }
        
  public function index(){
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $casinos = $u->casinos;     
    $es_superusuario = $u->es_superusuario;
    $puede_cargar = $es_superusuario || $u->tienePermiso('m_a_pagos');
    return View::make('Canon.index', compact('casinos','plataformas','es_superusuario','puede_cargar'));
  }
  
  public static  $errores = [
    'required' => 'El valor es requerido',
    'regex'    => 'Formato incorrecto',
    'date'     => 'Tiene que ser una fecha en formato YYYY-MM-DD',
    'min'      => 'Es inferior al limite',
    'max'      => 'Supera el limite',
    'integer'  => 'Tiene que ser un número entero',
    'exists'   => 'El valor es incorrecto',
  ];
    
  private function validarCanon(array $request,array $requireds = []){
    $requireds_f = function(string $s) use ($requireds) {
      return in_array($s,$requireds)? 'required' : 'nullable';
    };

    $validar_arr = [
      'id_canon' => ['nullable','integer','exists:canon,id_canon,deleted_at,NULL'],
      'año_mes' => [$requireds_f('año_mes'),'regex:/^\d{4}\-((0\d)|(1[0-2]))\-01$/'],
      'id_casino' => [$requireds_f('id_casino'),'integer','exists:casino,id_casino,deleted_at,NULL'],
      'estado' => ['nullable','string','max:32'],
      'es_antiguo' => [$requireds_f('es_antiguo'),'integer','in:1,0'],
      'intereses_y_cargos' => ['nullable',AUX::numeric_rule(2)],
      'motivo_intereses_y_cargos' => ['nullable','string','max:128'],
      'ajuste' => ['nullable',AUX::numeric_rule(2)],
      'motivo_ajuste' => ['nullable','string','max:128'],
      //Valores que se "difunden" a cada subcanon >:(
      'valor_dolar' => ['nullable',AUX::numeric_rule(2)],
      'valor_euro' => ['nullable',AUX::numeric_rule(2)],
      'devengado_fecha_cotizacion' => ['nullable','date'],
      'devengado_cotizacion_dolar' => ['nullable',AUX::numeric_rule(2)],
      'devengado_cotizacion_euro' => ['nullable',AUX::numeric_rule(2)],
      'determinado_fecha_cotizacion' => ['nullable','date'],
      'determinado_cotizacion_dolar' => ['nullable',AUX::numeric_rule(2)],
      'determinado_cotizacion_euro' => ['nullable',AUX::numeric_rule(2)]
    ];
    
    foreach($this->subcanons as $sc){
      $validar_arr = array_merge($validar_arr,$sc->validar());
    }
    
    $validar_arr = array_merge($validar_arr,$this->canon_pago->validar());
    
    Validator::make($request,$validar_arr,self::$errores,[])->after(function($validator){
      if($validator->errors()->any()) return;
    })->validate();
  }
  
  public function recalcular_req(Request $request){
    $R = $request->all();
    $this->validarCanon($R);
    return $this->recalcular($R);
  }
  
  private function recalcular(array $request){
    $make_accessor = function(&$arr){
      return function($k,$dflt = null) use (&$arr){
        return (!isset($arr[$k]) || $arr[$k] === '' || $arr[$k] === null || $arr[$k] === [])?
          $dflt
        : $arr[$k];
      };
    };
    
    $R = $make_accessor($request);
    
    $año_mes = $R('año_mes');//@RETORNADO
    $id_casino = $R('id_casino');//@RETORNADO
    
    $canon_anterior = collect([]);//@RETORNADO
    if($año_mes !== null && $id_casino !== null){
      $canon_anterior = DB::table('canon')
      ->select('id_canon')
      ->whereNull('deleted_at')
      ->where('id_casino',$id_casino)
      ->where('año_mes','<',$año_mes)
      ->orderBy('año_mes','desc')
      ->first();
      
      if($canon_anterior !== null){
        $canon_anterior = $this->obtener_arr_confluido(['id_canon' => $canon_anterior->id_canon]);
      }
    }
    
    $estado = $R('estado','Nuevo');//@RETORNADO
    $fecha_cotizacion = $R('fecha_cotizacion');//@RETORNADO
    $es_antiguo = $R('es_antiguo',0)? 1 : 0;//@RETORNADO
    
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
    $COT_D = (CanonValorPorDefectoController::getInstancia()->valorPorDefecto('valores_confluir') ?? [])[$id_casino] ?? [];
    $COT = [
      'valor_dolar' => bcadd($R('valor_dolar',$COT_D['valor_dolar'] ?? null),'0',2),
      'valor_euro'  => bcadd($R('valor_euro',$COT_D['valor_euro'] ?? null),'0',2),
      'devengado_fecha_cotizacion'   => $R('devengado_fecha_cotizacion',null),
      'devengado_cotizacion_dolar'   => $R('devengado_cotizacion_dolar',null),
      'devengado_cotizacion_euro'    => $R('devengado_cotizacion_euro',null),
      'determinado_fecha_cotizacion' => $R('determinado_fecha_cotizacion',null),
      'determinado_cotizacion_dolar' => $R('determinado_cotizacion_dolar',null),
      'determinado_cotizacion_euro'  => $R('determinado_cotizacion_euro',null),
    ];
    
    if($año_mes !== null && $año_mes !== '' && ($COT['devengado_fecha_cotizacion'] === null || $COT['determinado_fecha_cotizacion'] === null)){
      $f = explode('-',$año_mes);
      
      $f[0] = $f[1] == '12'? intval($f[0])+1 : $f[0];
      $f[1] = $f[1] == '12'? '01' : str_pad(intval($f[1])+1,2,'0',STR_PAD_LEFT);
      
      if($COT['devengado_fecha_cotizacion'] === null){
        $COT['devengado_fecha_cotizacion'] = implode('-',$f);
      }
      
      if($COT['determinado_fecha_cotizacion'] === null){
        $f[2] = '09';
        $f = implode('-',$f);
        $f = new \DateTimeImmutable($f);
        $viernes_anterior = clone $f;
        for($break = 9;$break > 0 && in_array($viernes_anterior->format('w'),['0','6']);$break--){
          $viernes_anterior = $viernes_anterior->sub(\DateInterval::createFromDateString('1 day'));
        }
        $COT['determinado_fecha_cotizacion'] = $viernes_anterior->format('Y-m-d');//@RETORNADO
      }
    }
    
    if($COT['devengado_fecha_cotizacion'] !== null){
      $COT['devengado_cotizacion_dolar'] = $COT['devengado_cotizacion_dolar'] ?? $this->cotizacion($COT['devengado_fecha_cotizacion'],2,$id_casino) ?? '0';
      $COT['devengado_cotizacion_euro']  = $COT['devengado_cotizacion_euro']  ?? $this->cotizacion($COT['devengado_fecha_cotizacion'],3,$id_casino) ?? '0';
    }
    
    if($COT['determinado_fecha_cotizacion'] !== null){
      $COT['determinado_cotizacion_dolar'] = $COT['determinado_cotizacion_dolar'] ?? $this->cotizacion($COT['determinado_fecha_cotizacion'],2,$id_casino) ?? '0';
      $COT['determinado_cotizacion_euro']  = $COT['determinado_cotizacion_euro']  ?? $this->cotizacion($COT['determinado_fecha_cotizacion'],3,$id_casino) ?? '0';
    }
    
    $subcanons = [];
    
    $make_multiple_accessors = function(&$r,&$d,&$a,&$cot) use ($make_accessor){
      $R = $make_accessor($r);
      $D = $make_accessor($d);
      $A = $make_accessor($a);
      $COT = $make_accessor($cot);
      $RD = function($s,$dflt = null) use ($R,$D){
        return $R($s,null) ?? $D($s,null) ?? $dflt;
      };
      $RAD = function($s,$dflt = null) use ($R,$A,$D){
        return $R($s,null) ?? $A($s,null) ?? $D($s,null) ?? $dflt;
      };
      return compact('R','D','A','RD','RAD','COT');
    };
    
    foreach($this->subcanons as $subcanon => $scobj){        
      $valorPorDefecto = (CanonValorPorDefectoController::getInstancia()->valorPorDefecto($subcanon,$scobj->valorPorDefecto) ?? [])[$id_casino] ?? [];
      $data_request = $request[$subcanon] ?? [];
      $subcanon_anterior = $canon_anterior[$subcanon] ?? [];
      $retsc = [];
      
      foreach(($request[$subcanon] ?? $defecto ?? []) as $tipo => $_){
        $data_request_tipo = $data_request[$tipo] ?? [];
        $defecto_tipo = $defecto[$tipo] ?? [];
        $anterior_tipo = $subcanon_anterior[$tipo] ?? [];
                
        $retsc[$tipo] = $scobj->recalcular(
          $año_mes,
          $id_casino,
          $es_antiguo,
          $tipo,
          $make_multiple_accessors($data_request_tipo,$defecto_tipo,$anterior_tipo,$COT)
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
      
      $subcanons[$subcanon] = $retsc;
    }
    
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
        
    $ret = [];
    $ret = array_merge($ret,$subcanons);
    $ret = array_merge($ret,$this->canon_pago->recalcular($id_casino,$año_mes,$principal,$R));
    $ret = array_merge($ret,$this->canon_archivo->recalcular($id_casino,$año_mes,$principal,$R));
    $ret = array_merge($ret,$this->confluir($ret));
    
    $ajuste = bcadd($R('ajuste','0.00'),'0',2);//@RETORNADO
    $motivo_ajuste = $R('motivo_ajuste','');//@RETORNADO
    $diferencia = bcadd(bcsub($ret['a_pagar'],$ret['pago'],2),$ajuste,2);//@RETORNADO
    $saldo_posterior = bcsub('0',$diferencia,2);//@RETORNADO @HACK: Lo mismo que diferencia? el saldo ya esta en el a_pagar
    $saldo_posterior_cerrado = $saldo_posterior;//@RETORNADO
    
    return array_merge($ret,compact(
      'canon_anterior',
      'año_mes','id_casino','estado','es_antiguo',
      'devengado_bruto','devengado_deduccion','devengado',
      'determinado_bruto','determinado_ajuste','determinado','porcentaje_seguridad',
      'saldo_anterior','saldo_anterior_cerrado',
      'intereses_y_cargos','motivo_intereses_y_cargos','principal',
      'ajuste','motivo_ajuste','diferencia',
      'saldo_posterior','saldo_posterior_cerrado'
    ));
  }
  
  public function adjuntar(Request $request){
    return $this->guardar($request,false);
  }
  
  public function guardar(Request $request,$recalcular = true){
    $requeridos = $recalcular? ['año_mes','id_casino','es_antiguo'] : ['id_canon'];
    $this->validarCanon($request->all(),$requeridos);
    
    Validator::make($request->all(),[], self::$errores,[])->after(function($validator){
      if($validator->errors()->any()) return;
      $D = $validator->getData();
      if(!isset($D['id_canon'])){//Nuevo
        $ya_existe = DB::table('canon')
        ->whereNull('deleted_at')
        ->where('año_mes',$D['año_mes'])
        ->where('id_casino',$D['id_casino'])
        ->count() > 0;
        
        if($ya_existe){
          $validator->errors()->add('año_mes','Ya existe un canon para ese periodo');
          $validator->errors()->add('id_casino','Ya existe un canon para ese periodo');
          return;
        }
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$recalcular){
      $datos;
      if($recalcular){
        $datos = $this->recalcular($request->all());
        $datos['estado'] = 'Generado';
      }
      else{
        $datos = $this->obtener_arr_confluido(['id_canon' => $request['id_canon']]);
        $datos['canon_archivo'] = $request['canon_archivo'] ?? [];
      }
      
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $canon_anterior = ($datos['año_mes'] !== null && $datos['id_casino'] !== null)?
        DB::table('canon')
        ->select('id_canon')
        ->whereNull('deleted_at')
        ->where('año_mes',$datos['año_mes'])
        ->where('id_casino',$datos['id_casino'])
        ->orderBy('created_at','desc')
        ->get()
      : [];
      
      foreach($canon_anterior as $c){
        $this->borrar_arr(['id_canon' => $c->id_canon],$created_at,$id_usuario);
      }
            
      $id_canon = DB::table('canon')
      ->insertGetId([
        'año_mes' => $datos['año_mes'],
        'id_casino' => $datos['id_casino'],
        'estado' => $datos['estado'],
        'devengado_bruto' => $datos['devengado_bruto'],
        'devengado_deduccion' => $datos['devengado_deduccion'],
        'devengado' => $datos['devengado'],
        'porcentaje_seguridad' => $datos['porcentaje_seguridad'],
        'determinado_bruto' => $datos['determinado_bruto'],
        'determinado_ajuste' => $datos['determinado_ajuste'],
        'determinado' => $datos['determinado'],
        'saldo_anterior' => $datos['saldo_anterior'],
        'saldo_anterior_cerrado' => $datos['saldo_anterior_cerrado'],
        'intereses_y_cargos' => $datos['intereses_y_cargos'],
        'motivo_intereses_y_cargos' => $datos['motivo_intereses_y_cargos'],
        'principal' => $datos['principal'],
        'a_pagar' => $datos['a_pagar'],
        'pago' => $datos['pago'],
        'ajuste' => $datos['ajuste'],
        'motivo_ajuste' => $datos['motivo_ajuste'],
        'diferencia' => $datos['diferencia'],
        'saldo_posterior_cerrado' => $datos['saldo_posterior_cerrado'],
        'saldo_posterior' => $datos['saldo_posterior'],
        'es_antiguo' => $datos['es_antiguo'],
        'created_at' => $created_at,
        'created_id_usuario' => $id_usuario,
      ]);
      
      $id_canon_anterior = count($canon_anterior)? $canon_anterior[0]->id_canon : null;
      
      $this->canon_pago->guardar($id_canon,$id_canon_anterior,$datos);
      
      foreach($this->subcanons as $scobj){
        $scobj->guardar($id_canon,$id_canon_anterior,$datos);
      }
      
      $this->canon_archivo->guardar($id_canon,$id_canon_anterior,$datos);
      
      if($recalcular){
        $this->cambio_canon_recalcular_saldos($id_canon);
      }
      
      return 1;
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
      }
      else{//El saldo influye en el principal y por ende en todos los calculos de pagos
        $c_para_recalcular = $this->obtener_arr_confluido(['id_canon' => $c->id_canon]);
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
      }
    }
  }
  
  public function recalcular_saldos_Req(Request $request){
    DB::transaction(function(){
      foreach(Casino::all() as $c){
        $this->recalcular_saldos('0','1970-01-01',$c->id_casino);
      }
      return 1;
    });
  }
  
  public function obtener_arr(array $request){
    $ret = (array) DB::table('canon as c')
    ->select('cas.nombre as casino','c.*','u.user_name as usuario')
    ->join('usuario as u','u.id_usuario','=','c.created_id_usuario')
    ->join('casino as cas','cas.id_casino','=','c.id_casino')
    ->where('id_canon',$request['id_canon'])
    ->first();
    
    $ret = array_merge($ret,$this->canon_pago->obtener($request['id_canon']));
    foreach($this->subcanons as $scobj){
      $ret = array_merge($ret,$scobj->obtener($request['id_canon']));
    }
    $ret = array_merge($ret,$this->canon_archivo->obtener($request['id_canon']));
    
    $ret = json_decode(json_encode($ret),true);
            
    return !empty($ret)? $ret : $this->recalcular($ret);
  }
  
  public function obtener_arr_confluido(array $request){
    $ret = $this->obtener_arr($request);
    return array_merge($ret,$this->confluir($ret));
  }
  
  private function confluir(array $canon){    
    $aux = [];
    
    $aux[] = [$this->canon_pago->confluir($canon)];
    foreach($this->subcanons as $sc => $scobj){
      $aux[] = [$scobj->confluir($canon)];
    }
    
    $attrs = array_keys(array_reduce($aux,function($carry,$conf){
      foreach($conf as $cnf)
      foreach($cnf as $k => $_){
        $carry[$k] = 1;
      }
      return $carry;
    },[]));
    
    return AUX::confluir_datos(
      $aux,
      array_keys($aux),
      $attrs
    );
  }
    
  public function obtener(Request $request){
    return $this->obtener_arr_confluido($request->all());
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
        return $this->obtener_arr_confluido(['id_canon' => $idc->id_canon]);
      })
    : collect([]);
    return $ultimo;
  }
  
  public function borrar(Request $request){
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $check_estado = !$u->es_superusuario;
    
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
    
    return $this->borrar_arr($request->all());
  }
  
  public function borrar_arr(array $arr,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($arr,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      $id_canon = $arr['id_canon'];
      
      DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$id_canon)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      $this->cambio_canon_recalcular_saldos($id_canon);
      
      return 1;
    });
  }
  
  private function cambio_canon_recalcular_saldos($id_canon){
    $c = DB::table('canon')
    ->where('id_canon',$id_canon)
    ->first();
    
    $cprev = DB::table('canon')
    ->where('año_mes','<',$c->año_mes)
    ->where('id_casino','=',$c->id_casino)
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->first();
    
    if($cprev === null){
      $this->recalcular_saldos('0','1970-01-01',$c->id_casino);
    }
    else{
      $this->recalcular_saldos($cprev->saldo_posterior,$cprev->año_mes,$c->id_casino);
    }
  }
  
  public function buscar(Request $request,bool $paginar = true){
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $reglas = [];
    if(isset($request->id_casino)){
      $reglas[] = ['c.id_casino','=',$request->id_casino];
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
      'cas.nombre as casino','c.estado','c.devengado','c.determinado',
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
      'c.pago','c.saldo_posterior'
    )
    ->join('casino as cas','cas.id_casino','=','c.id_casino')
    ->whereRaw(($u->es_superusuario && ($request->eliminados ?? false))?
      'NOT EXISTS (
        SELECT * 
        FROM canon c2 
        WHERE c2.id_casino = c.id_casino 
        AND   c2.año_mes   = c.año_mes 
        AND   (c2.deleted_at IS NULL OR c2.created_at > c.created_at)
        LIMIT 1
       )
       AND c.deleted_at IS NOT NULL'
    : 'c.deleted_at IS NULL'
    )
    ->where($reglas)
    ->whereIn('c.id_casino',$u->casinos->pluck('id_casino'))
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->orderBy('cas.nombre','asc');
    
    if($paginar){
      $ret = $ret->paginate($request->page_size ?? 10);
    }
    else {
      $ret = $ret->get();
    }
    
    return $ret;
  }
    
  private $cotizacion_DB = null;
  private function cotizacion($fecha_cotizacion,$id_tipo_moneda,$id_casino){
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
      ->where('c.id_casino','<>',$id_casino)
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
      
      $c = DB::table('canon')
      ->where('id_canon',$request->id_canon)
      ->first();
      
      $this->recalcular_saldos($c->saldo_posterior,$c->año_mes,$c->id_casino);
      
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
            
      $this->cambio_canon_recalcular_saldos($request->id_canon);
      
      return 1;
    });
  }
  
  private function obtener_para_salida($id_canon){
    $data = $this->obtener_arr(compact('id_canon'));
    $ret = [];
    
    $ret['canon'] = [];
    foreach(['id_canon','id_casino','created_at','created_id_usuario','deleted_at','deleted_id_usuario','usuario','es_antiguo'] as $k){
      unset($data[$k]);
    }
    foreach($data as $k => $v){
      if(!is_array($v)){
        $ret['canon'][$k] = $v;
      }
    }
    $ret['canon'] = [$ret['canon']];
    
    $ret = array_merge($ret,$this->canon_pago->procesar_para_salida($data));
    
    foreach($this->subcanons as $scobj){
      $ret = array_merge($ret,$scobj->procesar_para_salida($data));
    }
    
    $ret = array_merge($ret,$this->canon_archivo->procesar_para_salida($data));
    
    foreach($ret as $k => $d){
      if(count($d) == 0) unset($ret[$k]);
    }
    
    return AUX::formatear_datos($ret);
  }
  
  public function planilla(Request $request){
    $ret = $this->obtener_para_salida($request->id_canon);
    $dir_path = storage_path("canon_{$request->id_canon}");
    
    $rmdir = function($dir) use(&$rmdir){//Borra recursivamente... cuidado con que se lo llama
      assert(substr($dir,0,strlen(storage_path())) == storage_path());//Chequea que no se llame con un path raro
      if(is_dir($dir) === false) return false;
      $files = array_diff(scandir($dir), ['.', '..']); 
      
      foreach($files as $f){
        $fpath = $dir.'/'.$f;
        if(is_dir($fpath)){
          $rmdir($fpath);
        }
        else{
          unlink($fpath);
        }
      }
      
      return rmdir($dir);
    };
    
    $rmdir($dir_path);
    mkdir($dir_path);
    
    $filenames = [];
    foreach($ret as $tipo => $arreglo){
      $lineas = [];
            
      foreach($arreglo as $v){
        $lineas[] = AUX::csvstr(array_keys($v));
        break;
      }
      
      foreach($arreglo as $v){
        $lineas[] = AUX::csvstr($v);
      }
      
      $lineas[] = '';
      
      $fname = $dir_path."/{$tipo}";
      $fhandler = fopen($fname,"w");
      fwrite($fhandler,implode("\r\n",$lineas));
      fclose($fhandler);
      
      $filenames[] = $fname;
    }
    
    $año_mes = $ret['canon'][0]['año_mes'];
    $casino  = $ret['canon'][0]['casino'];
    $outfile = "Canon-$año_mes-$casino.xlsx";
    $abs_outfile = storage_path($outfile);
    
    $log_file = storage_path(uniqid().'.log');
    $err_file = storage_path(uniqid().'.err');
    
    //Uso {$dir_path}/* para evitar tener que escapar espacios y cosas asi
    $cmd = 'python3 '.escapeshellarg(base_path('xlsxmaker.py')).' '.escapeshellarg($abs_outfile).' '.escapeshellarg($dir_path).'/* > '.escapeshellarg($log_file).' 2> '.escapeshellarg($err_file);
    exec($cmd);
    $rmdir($dir_path);
    
    if(is_file($abs_outfile) === false){
      echo '<p>','ERROR','</p>';
      echo '<p>',$cmd,'</p>';
      echo '<p>',"====================================",'</p>';
      echo '<p>',htmlspecialchars($log_file),'</p>';
      echo '<p>',htmlspecialchars(file_get_contents($log_file)),'</p>';
      echo '<p>',"====================================",'</p>';
      echo '<p>',htmlspecialchars($err_file),'</p>';
      echo '<p>',htmlspecialchars(file_get_contents($err_file)),'</p>';          
      unlink($log_file);
      unlink($err_file);
      return;
    }
    
    unlink($log_file);
    unlink($err_file);
    return response()->download($abs_outfile)->deleteFileAfterSend(true);
  }
  
  public function planillaPDF(Request $request){
    $datos = $this->obtener_para_salida($request->id_canon);
    $view = View::make('Canon.planillaSimple', compact('datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    //$dompdf->getCanvas()->page_text(20, 815, $codigo_casino."/".$fecha, $font, 10, array(0,0,0));
    //$dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    $año_mes = $datos['canon'][0]['año_mes'];
    $casino  = $datos['canon'][0]['casino'];
    $filename = "Canon-$año_mes-$casino.csv";
    return $dompdf->stream($filename, Array('Attachment'=>0));
  }
  
  
  public function totales($id_canon){
    return DB::table('canon')
    ->select(
      DB::raw('NULL as beneficio'),
      DB::raw('SUM(devengado_deduccion+devengado) as bruto'),
      DB::raw('SUM(devengado_deduccion) as deduccion'),
      DB::raw('SUM(devengado) as devengado'),
      DB::raw('SUM(determinado) as determinado')
    )
    ->where('id_canon',$id_canon)
    ->first();
  }
  
  public function totalesCanon($año,$mes){//Usado en backoffice tambien
    $cs = DB::table('canon as c')
    ->select('c.*','cas.nombre as casino')->distinct()
    ->join('casino as cas','cas.id_casino','=','c.id_casino')
    ->whereNull('c.deleted_at')
    ->whereYear('c.año_mes',$año)
    ->whereMonth('c.año_mes',$mes)
    ->get();
        
    $empty_val = ['beneficio' => null,'bruto' => null,'devengado' => null,'deduccion' => null,'determinado' => null];    
    $conceptos = ['Mesa' => $empty_val,'Maquina' => $empty_val,'Bingo' => $empty_val,'Físico' => $empty_val,'Online' => $empty_val,'' => $empty_val];    
    $datos = ['' => $conceptos];
    
    $sub_f;$add_f;{
      $apply_func_f = function($func)  use ($empty_val){
        return function($ta,$tb) use ($func,$empty_val){
          $ret = $empty_val;
          foreach($ret as $k => $_){
            $ret[$k] = $func($ta[$k] ?? '0',$tb[$k] ??  '0');
          }
          return $ret;
        };
      };
      $sub_f = $apply_func_f('bcsub_precise');
      $add_f = $apply_func_f('bcadd_precise');
    }
    
    foreach($cs as $canon){
      if(empty($canon)) continue;
      
      $total = $this->totales($canon->id_canon);
      $totales = $conceptos;
      
      foreach($this->subcanons as $scstr => $sc){
        $T = $sc->totales($canon->id_canon);
        foreach($totales as $concepto => $tot){          
          foreach($T as $tipo => $tot_tipo){
            if($sc->es($tipo,$concepto)){
              $totales[$concepto][] = (array) $tot_tipo;
            }
          }
        }
      }
      
      $totalizados = array_map(function($tot) use ($empty_val){
        $totalizar_func = function($carry,$t){
          foreach($carry as $ck => $cv){
            $carry[$ck] = $cv === null && $t[$ck] === null?
              null
            : bcadd_precise($cv ?? '0',$t[$ck] ?? '0');
          }
          return $carry;
        };
        return array_reduce($tot,$totalizar_func,$empty_val);
      },$totales);
      
      foreach($totalizados as $concepto => $T){
        $totalizados[$concepto] = array_map(function($n){
          return $n === null? null: bcround_ndigits($n ?? '0',2);
        },$T);
      }
      
      $totalizados[''] = $empty_val;
      foreach($totalizados as $concepto => $T){
        if($concepto != '' && $concepto != 'Físico'){
          $totalizados[''] = $add_f($totalizados[''],$T);
        }
      }
      $error_redondeo = $sub_f((array) $total,$totalizados['']);
      $totalizados['Mesa']  = $add_f($totalizados['Mesa'],$error_redondeo);//Le meto la diferencia de redondeo a mesas
      $totalizados['Físico'] = $add_f($totalizados['Físico'],$error_redondeo);//Ergo tambien al total fisico
      $totalizados[''] = $add_f($totalizados[''],$error_redondeo);//Ergo tambien al total
      
      $datos[$canon->casino] = $totalizados;
      foreach($totalizados as $concepto => $t){
        $datos[''][$concepto] = $add_f($datos[''][$concepto],$t);
      }
    }
    
    //Muevo la columna total al final... array_shift la saca del principio y despues la vuelvo a asignar para que se ponga al final
    $datos[''] = array_shift($datos);
    
    //Formateo a español los numeros
    foreach($datos as $id_canon => &$dcas){
      foreach($dcas as $tipo => &$vals){
        foreach($vals as $tval => &$v){
          $v = $v === null? null : AUX::formatear_decimal($v);
        }
      }
    }
    
    return $datos;
  }
  
  public function planillaDevengado(Request $request,$tipo_presupuesto = 'devengado'){
    if(!isset($request->id_canon)) return;
    
    $c_año_mes = DB::table('canon')
    ->select('año_mes')
    ->whereNull('deleted_at')
    ->where('id_canon',$request->id_canon)
    ->first();
    
    if(empty($c_año_mes)) return;
    
    $año_mes = explode('-',$c_año_mes->año_mes);
    $mes = $año_mes[1].'/'.substr($año_mes[0],2);
    
    $datos = $this->totalesCanon($año_mes[0],$año_mes[1]);
    
    $tablas = [];
    if($tipo_presupuesto == 'devengado'){
      $tablas = ['','deduccion','bruto'];
      foreach($datos as $cas => &$t){
        foreach($t as $nombre_sc => &$subcanon){
          $subcanon[''] = $subcanon['devengado'];
          unset($subcanon['beneficio']);
          unset($subcanon['devengado']);
          unset($subcanon['determinado']);
        }
      }
    }
    else if($tipo_presupuesto == 'determinado'){
      $tablas = [''];
      foreach($datos as &$t){
        foreach($t as &$subcanon){
          $subcanon[''] = $subcanon['determinado'];
          unset($subcanon['beneficio']);
          unset($subcanon['devengado']);
          unset($subcanon['bruto']);
          unset($subcanon['deduccion']);
          unset($subcanon['determinado']);
        }
      }
    }
    
    $conceptos = array_keys(array_reduce($datos,function($carry,$item){
      return array_merge($carry,$item);
    },[]));
        
    $view = View::make('Canon.planillaDevengado', compact('tipo_presupuesto','tablas','conceptos','mes','datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    return $dompdf->stream("Devengado-Canon-$mes.pdf", Array('Attachment'=>0));
  }
  
  public function planillaDeterminado(Request $request){
    return $this->planillaDevengado($request,'determinado');
  }
  
  private function planillaInforme(string $planilla,string $tipo,string $sacar,int $id_canon){
    $datos = $this->obtener_para_salida($id_canon);
    $sacable = function($s) use ($sacar){
      return substr($s,0,strlen($sacar)) == $sacar;
    };
    $simplificable = function($s) use ($tipo){
      if(substr($s,0,strlen($tipo)) == $tipo){
        return substr($s,strlen($tipo)+1);//+1 por el guion bajo
      }
      return false;
    };
        
    foreach($datos as $c => $datos_c){
      foreach($datos_c as $tc => $datos_tc){
        unset($datos[$c][$tc]['tipo']);
        foreach($datos_tc as $k => $v){
          if($sacable($k)){
            unset($datos[$c][$tc][$k]);
          }
          $s = $simplificable($k);
          if($s !== false){
            unset($datos[$c][$tc][$k]);
            $datos[$c][$tc][$s] = $v;
          }
        }
      }
    }
    
    $view = View::make($planilla, compact('tipo','datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    //$dompdf->getCanvas()->page_text(20, 815, $codigo_casino."/".$fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    $año_mes = $datos['canon'][0]['año_mes'];
    $casino  = $datos['canon'][0]['casino'];
    $filename = "Canon-$año_mes-$casino.pdf";
    return $dompdf->stream($filename, Array('Attachment'=>0));
  }
  
  public function descargar(Request $request){
    $data = $this->buscar($request,false);
    
    $conceptos = [
      'Maquina','Bingo','Online','Mesa'
    ];
    
    $tipo_valores = [
      'beneficio','bruto','deduccion','devengado','determinado'
    ];
    
    $arreglo_a_csv = [];
    $totales_cache = [];//Si busco para un periodo me devuelve todos los casinos por eso lo cacheo
    foreach($data as $d){
      $año_mes = explode('-',$d->año_mes);
      
      $t = null;
      if(!array_key_exists($d->año_mes,$totales_cache)){
        $totales_cache[$d->año_mes] = $this->totalesCanon(intval($año_mes[0]),intval($año_mes[1]));
      }
      $t = $totales_cache[$d->año_mes][$d->casino];//Deberia existir porque buscar() lo devolvio
      
      $fila = [
        'año_mes' => $d->año_mes,
        'casino'  => $d->casino,
      ];
      foreach($tipo_valores as $tval){
        foreach($conceptos as $cncpt){
          $fila[$tval.'_'.$cncpt] = ($t[$cncpt] ?? [])[$tval] ?? '0';
        }
        $fila[$tval] = ($t['Total'] ?? [])[$tval] ?? '0';
      }
      $fila['intereses_y_cargos'] = AUX::formatear_decimal($d->intereses_y_cargos);
      $fila['pago']      = AUX::formatear_decimal($d->pago);
      $fila['saldo_posterior'] = AUX::formatear_decimal($d->saldo_posterior);
      $arreglo_a_csv[] = $fila;
    }
    
    $header = array_keys($arreglo_a_csv[0] ?? []);
    
    $f = fopen('php://memory', 'r+');//https://stackoverflow.com/questions/13108157/php-array-to-csv
    fputcsv($f, $header,',','"',"\\");
    foreach ($arreglo_a_csv as $fila) {
      fputcsv($f, array_values($fila),',','"',"\\");
    }
    rewind($f);
        
    return stream_get_contents($f);
  }
    
  public function descargarPlanillas(Request $request){
    $primer_año = null;
    $ultimo_año = null;
    $primer_mes = null;
    $ultimo_mes = null;
    {
      $primer_año_mes = DB::table('canon')
      ->whereNull('deleted_at')
      ->select('año_mes');
      $ultimo_año_mes = (clone $primer_año_mes)->orderBy('año_mes','desc')->first();
      $primer_año_mes = $primer_año_mes->orderBy('año_mes','asc')->first();

      if($primer_año_mes !== null && $ultimo_año_mes !== null){
        $primer_año_mes = explode('-',$primer_año_mes->año_mes);
        $ultimo_año_mes = explode('-',$ultimo_año_mes->año_mes);
        
        $primer_año = intval($primer_año_mes[0]);
        $ultimo_año = intval($ultimo_año_mes[0]);
        $primer_mes = intval($primer_año_mes[1]);
        $ultimo_mes = intval($ultimo_año_mes[1]);
      }
    }
    
    $años = [];
    if($primer_año !== null && $ultimo_año !== null){
      $años = collect(array_reverse(range($primer_año,$ultimo_año,1)));
    }
    $año  = $request->año ?? null;
    $año  = $año === null? null : intval($año);
    $año_anterior = $año === null? null : ($año-1);
    
    $meses = collect([]);
    $meses_elegibles = ['Resumen'];//@HACK hasta que agregue un canon diario... despues solo usar $meses
    $meses_calendario = collect([null,'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']);
    unset($meses_calendario[0]);
    
    $meses = collect(['Resumen','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']);
    if($primer_mes !== null && $ultimo_mes !== null){      
      if($año == $primer_año){
        foreach($meses as $mnum => $m){
          if($mnum == 0) continue;
          if($mnum == $primer_mes){
            break;
          }
          unset($meses[$mnum]);
        }
      }
      else if($año == $ultimo_año){
        $unsetting = false;
        foreach($meses as $mnum => $m){
          if($mnum == 0) continue;
          if($mnum == $ultimo_mes){
            $unsetting = true;
          }
          else if($unsetting){
            unset($meses[$mnum]);
          }
        }
      }
    }
    
    $mes  = $request->mes ?? null;
    $num_mes = $mes !== null? $meses->search(function($m) use ($mes){
      return $m == $mes;
    }) : false;
    $num_mes = $num_mes === false? null: $num_mes;
    
    $mes = $num_mes === null? null : $mes;//Si el mes no es uno valido lo saco
    
    $planilla  = $request->planilla ?? null;
    
    $SFE_MEL = 'Santa Fe - Melincué';
    $unir_sfe_mel = in_array($planilla,['participacion','jol']);
    $casinos = Casino::select('id_casino','nombre')
    ->orderBy('id_casino','asc')
    ->get()
    ->groupBy(function($c) use ($SFE_MEL,$unir_sfe_mel){
      if($unir_sfe_mel && in_array($c->nombre,['Melincué','Santa Fe'])){
        return $SFE_MEL;
      }
      return $c->nombre;
    })
    ->map(function($cs){
      return $cs->pluck('id_casino')->toArray();
    });
    $casinos['Total'] = Casino::select('id_casino')->get()->pluck('id_casino')->toArray();
        
    $plataformas = Plataforma::orderBy('id_plataforma','asc')->get();
    $relacion_plat_cas = ['CCO' => 'Rosario','BPLAY' => $SFE_MEL];
    
    $planillas = [
      'evolucion_historica' => 'Evolución Historica',
      'canon_total' => 'Canon Total',
      'canon_fisico_online' => 'Canon Físico-On Line',
      'participacion' => 'Particip. % Resultado CF-JOL',
      //'mtm' => 'Maquinas',
      //'mesas' => 'Mesas de Paño',
      //'bingos' => 'Bingos',
      //'jol' => 'Juegos On Line'
    ];
    $planillas_anuales = ['canon_total','canon_fisico_online','participacion','bingos'];
    $planillas_mensuales = ['mtm','mesas','bingos','jol'];    
    $es_anual = isset($planilla) && $planilla !== null && in_array($planilla,$planillas_anuales);
    $es_mensual = isset($planilla) && $planilla !== null && in_array($planilla,$planillas_mensuales);
    
    $data = collect([]);
    $data_anual = collect([]);
    if(($es_anual && $año !== null) || ($es_mensual && $año !== null && $mes == 'Resumen') || $planilla == 'evolucion_historica'){    
      $tipos_variables_fisicos = ['Maquinas','Bingo'];
      $tipos_variables_online = ['JOL'];
      $tipos_fijos_mesas = DB::table('canon_fijo_mesas')
      ->select('tipo')->distinct()->get()->pluck('tipo')->values()->toArray();
      $tipos_fijos_mesas_adicionales = DB::table('canon_fijo_mesas_adicionales')
      ->select('tipo')->distinct()->get()->pluck('tipo')->values()->toArray();
      
      $ranged_sql = function($begin,$end){
        $ret = "( SELECT $begin as val ";
        for($i=$begin+1;$i<=$end;$i++){
          $ret.= 'UNION ALL SELECT '.$i.' ';
        }
        return $ret.')';
      };
      
      $meses_sql = $ranged_sql(1,12);
      $años_sql = $año === null?
        $ranged_sql($primer_año,$ultimo_año)
      : $ranged_sql($año-1,$año);
      
      $casinos_select = $unir_sfe_mel?
        'IF(cas.nombre IN ("Santa Fe","Melincué"),"Santa Fe - Melincué",cas.nombre)'
      : 'cas.nombre';
      
      $q = DB::table(DB::raw($meses_sql.' as mes'))
      ->crossJoin(DB::raw($años_sql.' as año'))
      ->crossJoin(DB::raw('(
        SELECT cas.nombre as nombre,cas.id_casino
        FROM casino as cas
        WHERE cas.deleted_at IS NULL
      ) as cas'))
      ->leftJoin('canon as c',function($j){
        return $j->whereNull('c.deleted_at')
        ->on('c.id_casino','=','cas.id_casino')
        ->on(DB::raw('YEAR(c.año_mes)'),'=','año.val')
        ->on(DB::raw('MONTH(c.año_mes)'),'=','mes.val');
      })
      ->leftJoin('canon as c_yoy',function($j){
        return $j->whereNull('c_yoy.deleted_at')
        ->on('c_yoy.id_casino','=','cas.id_casino')
        ->on(DB::raw('YEAR(c_yoy.año_mes)'),'=',DB::raw('(año.val-1)'))
        ->on(DB::raw('MONTH(c_yoy.año_mes)'),'=','mes.val');
      })
      ->leftJoin('canon as c_mom',function($j){
        return $j->whereNull('c_mom.deleted_at')
        ->on('c_mom.id_casino','=','cas.id_casino')
        ->on(DB::raw('YEAR(c_mom.año_mes)'),'=', DB::raw('IF(
          mes.val<>1,
          año.val,
          año.val-1
        )'))
        ->on(DB::raw('MONTH(c_mom.año_mes)'),'=',DB::raw('IF(
          mes.val<>1,
          mes.val-1,
          12
        )'));
      });
      
      $fisicos = [];
      $online  = [];
      $variables = [];
      $fijos = [];
      $fijos_adicionales = [];
      foreach($tipos_variables_fisicos as $tidx => $t){
        $alias = 'c_fis_v'.$tidx;
        $q = $q->leftJoin('canon_variable as '.$alias,function($j) use ($alias,$t){
          return $j->on($alias.'.id_canon','=','c.id_canon')
          ->where($alias.'.tipo','=',$t);
        });
        $fisicos[] = $alias;
        $variables[] = $alias;
      }
      
      foreach($tipos_variables_online as $tidx => $t){
        $alias = 'c_ol_v'.$tidx;
        $q = $q->leftJoin('canon_variable as '.$alias,function($j) use ($alias,$t){
          return $j->on($alias.'.id_canon','=','c.id_canon')
          ->where($alias.'.tipo','=',$t);
        });
        $online[] = $alias;
        $variables[] = $alias;
      }
      
      foreach($tipos_fijos_mesas as $tidx => $t){
        $alias = 'c_fis_mf'.$tidx;
        $q = $q->leftJoin('canon_fijo_mesas as '.$alias,function($j) use ($alias,$t){
          return $j->on($alias.'.id_canon','=','c.id_canon')
          ->where($alias.'.tipo','=',$t);
        });
        $fisicos[] = $alias;
        $fijos[] = $alias;
      }
      
      foreach($tipos_fijos_mesas_adicionales as $tidx => $t){
        $alias = 'c_fis_mfa'.$tidx;
        $q = $q->leftJoin('canon_fijo_mesas_adicionales as '.$alias,function($j) use ($alias,$t){
          return $j->on($alias.'.id_canon','=','c.id_canon')
          ->where($alias.'.tipo','=',$t);
        });
        $fisicos[] = $alias;
        $fijos_adicionales[] = $alias;
      }
      
      $canon_fisico = 'ROUND('.implode('+',array_map(function($t){
        return "IFNULL(SUM($t.determinado),0)";
      },$fisicos)).',2)';
      
      $canon_online = 'ROUND('.implode('+',array_map(function($t){
        return "IFNULL(SUM($t.determinado),0)";
      },$online)).',2)';
            
      if($planilla == 'evolucion_historica'){
        $sel_aggr = 'SUM(c.devengado) as devengado,
        SUM(c.determinado) as canon,
        SUM(c_yoy.devengado) as yoy_devengado,
        SUM(c_yoy.determinado) as yoy_canon,
        SUM(c_mom.devengado) as mom_devengado,
        SUM(c_mom.determinado) as mom_canon,
        ROUND(100*(SUM(c.devengado)/NULLIF(SUM(c_yoy.devengado),0)-1),2) as variacion_anual_devengado,
        ROUND(100*(SUM(c.devengado)/NULLIF(SUM(c_mom.devengado),0)-1),2) as variacion_mensual_devengado,
        ROUND(100*(SUM(c.determinado)/NULLIF(SUM(c_yoy.determinado),0)-1),2) as variacion_anual_canon,
        ROUND(100*(SUM(c.determinado)/NULLIF(SUM(c_mom.determinado),0)-1),2) as variacion_mensual_canon,
        (SUM(c.determinado)-SUM(c.devengado)) as diferencia,
        ROUND(100*(1-SUM(c.devengado)/NULLIF(SUM(c.determinado),0)),2) as variacion_sobre_devengado';
      }
      else if($planilla == 'canon_total'){
        $sel_aggr = 'SUM(c.determinado) as canon_total,
        100*(SUM(c.determinado)/NULLIF(SUM(c_yoy.determinado),0)-1) as variacion_anual,
        100*(SUM(c.determinado)/NULLIF(SUM(c_mom.determinado),0)-1) as variacion_mensual';
      }
      else if($planilla == 'canon_fisico_online'){
        $sel_aggr = $canon_fisico.' as canon_fisico,
        '.$canon_online.' as canon_online,
        SUM(c.determinado) as canon_total,
        100*(SUM(c.determinado)/NULLIF(SUM(c_yoy.determinado),0)-1) as variacion_anual,
        100*(SUM(c.determinado)/NULLIF(SUM(c_mom.determinado),0)-1) as variacion_mensual';
      }
      else if($planilla == 'participacion'){
        $ganancia_total_variable = '('.implode('+',array_map(function($t){
          return "IFNULL(SUM($t.determinado_subtotal),0)";//Con el impuesto sacado
        },$variables)).')';
        
        $ganancia_online_variable = '('.implode('+',array_map(function($t){
          return "IFNULL(SUM($t.determinado_subtotal),0)";//Con el impuesto sacado
        },$online)).')';
              
        $ganancia_fisico_fijo = '('.implode('+',array_map(function($t){
          return "IFNULL(SUM($t.bruto),0)";//Con el impuesto sacado
        },$fijos)).')';
              
        $ganancia_online = "($ganancia_online_variable)";
        $ganancia_total  = "($ganancia_total_variable+$ganancia_fisico_fijo)";
        $ganancia_fisico = "($ganancia_total-$ganancia_online)";
        
        $porcentaje_fisico = "ROUND(100*$ganancia_fisico/NULLIF($ganancia_total,0),2)";
        $porcentaje_online = "ROUND(100*$ganancia_online/NULLIF($ganancia_total,0),2)";
      
        $ganancia_cco = '('.implode('+',array_map(function($t){
          return "IFNULL(SUM(IF(cas.nombre = 'Rosario',$t.determinado_subtotal,0)),0)";
        },$online)).')';
        
        $ganancia_bplay = '('.implode('+',array_map(function($t){
          return "IFNULL(SUM(IF(cas.nombre IN ('Santa Fe','Melincué'),$t.determinado_subtotal,0)),0)";
        },$online)).')';
        
        $porcentaje_CCO = "ROUND(100*$ganancia_cco/NULLIF($ganancia_online,0),2)";
        $porcentaje_BPLAY = "ROUND(100*$ganancia_bplay/NULLIF($ganancia_online,0),2)";
        //Como son dos porcentajes el rounding uno siempre compensa tal que sea 100 la suma
        $sel_aggr = "$canon_online as canon_online,
        $porcentaje_fisico as porcentaje_fisico,
        $porcentaje_online as porcentaje_online,
        $porcentaje_CCO as porcentaje_CCO,
        $porcentaje_BPLAY as porcentaje_BPLAY";
      }
      else if($planilla == 'mtm'){
        $mtmalias = $variables[array_search('Maquinas',$tipos_variables_fisicos)];
        $sel_aggr = 
        "NULL as bruto,
         NULL as bruto_usd,
         NULL as bruto_convertido,
         SUM($mtmalias.determinado_bruto) as bruto_total,
         SUM($mtmalias.determinado) as canon_total,
         NULL as variacion_anual";
      }
      else if($planilla == 'bingos'){
        $binalias = $variables[array_search('Bingo',$tipos_variables_fisicos)];
        $sel_aggr = 
        "NULL as bruto,
        SUM($binalias.determinado_bruto) as bruto_total,
        SUM($binalias.determinado) as canon_total,
        NULL as variacion_anual";
      }
      else if($planilla == 'jol'){
        $jolalias = $variables[count($tipos_variables_fisicos)+array_search('JOL',$tipos_variables_online)];
        $sel_aggr = 
        "NULL as usuarios_online,
        NULL as resultado_online,
        NULL as canon_online,
        NULL as usuarios_poker,
        NULL as utilidad_poker,
        NULL as canon_poker,
        NULL as resultado,
        SUM($jolalias.determinado) as canon_total,
        NULL as variacion_anual";
      }
      else if($planilla == 'mesas'){
        $sel_aggr = 
        "NULL as bruto,
        SUM($jolalias.determinado_bruto) as bruto_total,
        SUM($jolalias.determinado) as canon_total,
        NULL as variacion_anual";
      }
      else{
        throw new \Exception($planilla.' sin implementar');
      }
      
      $años_planilla;
      if($es_anual || $es_mensual){
        $años_planilla = [$año_anterior,$año];
      }
      else{
        $años_planilla = $años->toArray();
      }
      
      $data = (clone $q)
      ->selectRaw(
        $casinos_select.' as casino,
        año.val as año,
        mes.val as mes,
        '.$sel_aggr
      )
      ->groupBy(DB::raw($casinos_select.',año.val,mes.val'))
      ->orderBy('año','asc')
      ->orderBy('mes','asc')
      ->get()
      ->merge(//Por año por mes
        (clone $q)
        ->selectRaw('
          "Total" as casino,
          año.val as año,
          mes.val as mes,
          '.$sel_aggr
        )
        ->groupBy('año.val','mes.val')
        ->orderBy('año','asc')
        ->orderBy('mes','asc')
        ->get()
      )
      ->merge(//Por casino por mes
        (clone $q)
        ->selectRaw(
          $casinos_select.' as casino,
          0 as año,
          mes.val as mes,
          '.$sel_aggr
        )
        ->groupBy(DB::raw($casinos_select.',mes.val'))
        ->orderBy('mes','asc')
        ->get()
      )
      ->merge(//Por casino por año
        (clone $q)
        ->selectRaw(
          $casinos_select.' as casino,
          año.val as año,
          0 as mes,
          '.$sel_aggr
        )
        ->groupBy(DB::raw($casinos_select.',año.val'))
        ->orderBy('año','asc')
        ->get()
      )
      ->merge(//Por casino
        (clone $q)
        ->selectRaw(
          $casinos_select.' as casino,
          0 as año,
          0 as mes,
          '.$sel_aggr
        )
        ->groupBy(DB::raw($casinos_select))
        ->get()
      )
      ->merge(//Por año
        (clone $q)
        ->selectRaw('
          "Total" as casino,
          año.val as año,
          0 as mes,
          '.$sel_aggr
        )
        ->groupBy('año.val')
        ->orderBy('año','asc')
        ->get()
      )
      ->merge(//Por mes
        (clone $q)
        ->selectRaw('
          "Total" as casino,
          0 as año,
          mes.val as mes,
          '.$sel_aggr
        )
        ->groupBy('mes.val')
        ->orderBy('mes','asc')
        ->get()
      )
      ->merge(//Por nada
        (clone $q)
        ->selectRaw('
          "Total" as casino,
          0 as año,
          0 as mes,
          '.$sel_aggr
        )
        ->groupBy(DB::raw('"constant"'))
        ->get()
      )
      ->groupBy('casino')
      ->map(function($d_cas){
        return $d_cas->groupBy('año')
        ->map(function($d_cas_año){
          return $d_cas_año->keyBy('mes');
        });
      });
    }
    
    $casinos = collect($casinos->keys());
    $abbr_casinos = $casinos->map(function($cas) {
      switch($cas){
        case 'Melincué': return 'CME';
        case 'Santa Fe': return 'CSF';
        case 'Santa Fe - Melincué': return 'CSF-CME';
        case 'Rosario':  return 'CRO';
        case 'Total':    return 'TOTAL';
      }
      return $cas;
    });
    
    return View::make('Canon.planillaPlanillas',compact('data','data_plataformas','años_planilla','años','año','año_anterior','meses','meses_calendario','meses_elegibles','mes','num_mes','planillas','planilla','es_anual','es_mensual','casinos','abbr_casinos','plataformas','relacion_plat_cas'));
  }
    
  public function diario(Request $request){
    $tabla = $request->tabla ?? '';
    $id = $request->id ?? null;
    $año_mes = $request->año_mes ?? null;
    
    Validator::make($request->all(),[
      'tabla' => ['required','in:canon_variable,canon_fijo_mesas,canon_fijo_mesas_adicionales'],
      'id' => ['nullable','exists:'.$tabla.',id_'.$tabla],
      'año_mes' => ($id !== null?
          ['nullable']
        : ['required','regex:/^\d{4}\-((0\d)|(1[0-2]))\-01$/']
      )
    ], self::$errores,[])->after(function($validator){})->validate();
    
    $scobj = $this->subcanons[$tabla] ?? null;
    if($scobj === null) return [];
    return $scobj->diario($id,$año_mes);
  }
}
