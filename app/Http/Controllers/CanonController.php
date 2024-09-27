<?php

namespace App\Http\Controllers;



use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use PDF;
use Dompdf\Dompdf;
use View;
use Zipper;
use File;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Plataforma;

class CanonController extends Controller
{
  private static $atributos = [];
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
    
  public function index(){
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos;
    $plataformas = Plataforma::all();
    
    $casino_tipo_mesas_adicionales = $this->valorPorDefecto('casino_tipo_mesas_adicionales');
    $casino_mesas = $this->valorPorDefecto('casino_mesas');
                 
    return View::make('Canon.ncanon', compact('casinos','plataformas','casino_tipo_mesas_adicionales','casino_mesas','primary_keys'));
  }
  
  public function recalcular(Request $request){
    $ret = [];
    $ret['año_mes'] = $request['año_mes'] ?? null;
    $ret['id_casino'] = $request['id_casino'] ?? null;
    $ret['estado'] = $request['estado'] ?? 'Nuevo';
    $ret['fecha_cotizacion'] = $request['fecha_cotizacion'] ?? null;
    $ret['fecha_vencimiento'] = $request['fecha_vencimiento'] ?? null;
    $ret['fecha_pago'] = $request['fecha_pago'] ?? null;  
    $ret['es_antiguo'] = $request['es_antiguo'] ?? 0;
    
    if(!empty($ret['año_mes'])){
      $f = explode('-',$ret['año_mes']);
      $f[2] = '10';
      $f = implode('-',$f);
      $f = new \DateTimeImmutable($f);
      $viernes_anterior = clone $f;
      $proximo_lunes = clone $f;
      for($break = 9;$break > 0 && in_array($viernes_anterior->format('w'),['0','6']);$break--){
        $viernes_anterior = $viernes_anterior->sub(\DateInterval::createFromDateString('1 day'));
      }
      for($break = 9;$break > 0 && in_array($proximo_lunes->format('w'),['0','6']);$break--){
        $proximo_lunes = $proximo_lunes->add(\DateInterval::createFromDateString('1 day'));
      }
      $ret['fecha_cotizacion'] = $ret['fecha_cotizacion'] ?? $viernes_anterior->format('Y-m-d');
      $ret['fecha_vencimiento'] = $ret['fecha_vencimiento'] ?? $proximo_lunes->format('Y-m-d');
      $ret['fecha_pago'] = $ret['fecha_pago'] ?? $ret['fecha_vencimiento'];
    }
    
    
    
    $ret['canon_variable'] = [];
    $ret['canon_fijo_mesas'] = [];
    $ret['canon_fijo_mesas_adicionales'] = [];
    
    if($ret['es_antiguo'] ?? false){
      $ret['bruto_devengado'] = $request['bruto_devengado'] ?? 0.0;
      $ret['bruto_pagar'] = $request['bruto_devengado'] ?? 0.0;
    }
    else{
      $ret['bruto_devengado'] = 0.0;
      $ret['bruto_pagar'] = 0.0;
      {//Varios tipos (JOL, Bingo, Maquinas)
        $tipo_cv = ($this->valorPorDefecto('canon_variable') ?? [])[$ret['id_casino']] ?? [];
        foreach(($request['canon_variable'] ?? $tipo_cv ?? []) as $cv => $_){
          $datos_defecto = $tipo_cv[$cv] ?? [];
          $ret['canon_variable'][$cv] = $this->canon_variable_recalcular(
            $datos_defecto,
            $request['canon_variable'][$cv] ?? []
          );
          $ret['bruto_devengado'] += $ret['canon_variable'][$cv]['total_devengado'] ?? 0.0;
          $ret['bruto_pagar'] += $ret['canon_variable'][$cv]['total_pagar'] ?? 0.0;
        }
      }
      {//Se hace asi para que quede homogoneo, en realidad solo puede ser tipo Mesas
        $ret['canon_fijo_mesas'] = [];
        $tipo_mesas = empty($request['id_casino'])? [] : ['Mesas' => null];
        foreach(($tipo_mesas ?? []) as $tipo_m => $_){
          $ret['canon_fijo_mesas'][$tipo_m] = $this->mesas_recalcular(
            $ret['año_mes'],
            $ret['id_casino'],
            $ret['fecha_cotizacion'],
            ($request['canon_fijo_mesas'] ?? [])[$tipo_m] ?? []
          );
          $ret['bruto_devengado'] += $ret['canon_fijo_mesas'][$tipo_m]['total_devengado'] ?? 0.0;
          $ret['bruto_pagar'] += $ret['canon_fijo_mesas'][$tipo_m]['total_pagar'] ?? 0.0; 
        }
      }
      {//Las mesas adicionales pueden ser varios tipos (Torneo Truco, Torneo Poker, etc)
        $ret['canon_fijo_mesas_adicionales'] = [];
        $tipo_mesas_adicionales = $this->valorPorDefecto('casino_tipo_mesas_adicionales')[$ret['id_casino']] ?? [];
        foreach(($request['canon_fijo_mesas_adicionales'] ?? $tipo_mesas_adicionales ?? []) as $tipo_ma => $_){
          $ret['canon_fijo_mesas_adicionales'][$tipo_ma] = $this->mesasAdicionales_recalcular(
            $tipo_ma,
            $tipo_mesas_adicionales[$tipo_ma] ?? [],
            ($RCF['mesas_adicionales'] ?? [])[$tipo_ma] ?? []
          );
          $ret['bruto_devengado'] += $ret['canon_fijo_mesas_adicionales'][$tipo_ma]['total_devengado'] ?? 0.0;
          $ret['bruto_pagar'] += $ret['canon_fijo_mesas_adicionales'][$tipo_ma]['total_pagar'] ?? 0.0;
        }
      }
    }
    
    $ret['deduccion'] = $request['deduccion'] ?? 0.0;
    $ret['devengado'] = $ret['bruto_devengado'] - $ret['deduccion'];
    
    $ret['porcentaje_seguridad'] = $ret['bruto_devengado'] != 0.0?
       100.0*($ret['bruto_devengado']-$ret['devengado'])/$ret['bruto_devengado']
      : null;
    
    $ret['interes_mora'] = $request['interes_mora'] ?? 0.0;
    $ret['a_pagar'] = $request['a_pagar'] ?? 0.0;
    $ret['mora'] = $request['mora'] ?? 0.0;
    
    if($ret['fecha_vencimiento'] && $ret['fecha_pago']){
      $timestamp_venc = \DateTimeImmutable::createFromFormat('Y-m-d', $ret['fecha_vencimiento']);
      $timestamp_pago = \DateTimeImmutable::createFromFormat('Y-m-d', $ret['fecha_pago']);
      $date_interval  = $timestamp_pago->diff($timestamp_venc);
      $cantidad_dias = intval($date_interval->format('%d'));
      if($cantidad_dias < 0){}
      else if(!is_null($ret['interes_mora'])){//Si envio el interes, calculo el pago
        $ret['a_pagar'] = $ret['bruto_pagar']*pow(1+$ret['interes_mora']/100.0,$cantidad_dias);
        $ret['mora'] = $ret['a_pagar'] - $ret['bruto_pagar'];
      }
      else if(!is_null($ret['a_pagar'])){//Si envio el pago, calculo el interes
        $coeff = log($ret['a_pagar']/$ret['bruto_pagar'])/($cantidad_dias == 0? 1 : $cantidad_dias);
        $ret['interes_mora'] = (exp($coeff)-1)*100;
        $ret['mora'] = $ret['a_pagar'] - $ret['bruto_pagar'];
      }
      else if(!is_null($ret['mora'])){
        $ret['a_pagar'] = $ret['bruto_pagar']+$ret['mora'];
        $coeff = log($ret['a_pagar']/$ret['bruto_pagar'])/($cantidad_dias == 0? 1 : $cantidad_dias);
        $ret['interes_mora'] = (exp($coeff)-1)*100;
      }
      else {
        $ret['a_pagar'] = $ret['bruto_pagar'];
        $ret['interes_mora'] = 0;
        $ret['mora'] = 0;
      }
    }
    
    $ret['pago'] = $request['pago'] ?? 0.0;
    $ret['diferencia'] = ($ret['pago'] ?? 0) - $ret['a_pagar'];
    $ret['saldo_anterior'] = (!is_null($ret['año_mes']) && !is_null($ret['id_casino']))? 
      $this->calcular_saldo_hasta($ret['año_mes'],$ret['id_casino'])
    : 0;
    
    $ret['saldo_posterior'] = $ret['saldo_anterior'] + $ret['diferencia'];
    
    return $ret;
  }
  
  private function calcular_saldo_hasta($año_mes,$id_casino){
    $saldo_anterior = DB::table('canon')
    ->selectRaw('SUM(diferencia) as saldo')
    ->where('id_casino',$id_casino)
    ->where('año_mes','<',$año_mes)
    ->groupBy(DB::raw('"constant"'))
    ->first();
    return $saldo_anterior === null? 0 : $saldo_anterior->saldo;
  }
  
  public function canon_variable_recalcular($valores_defecto,$data){
    $apostado_sistema = $data['apostado_sistema'] ?? 0.0;
    $apostado_informado = $data['apostado_informado'] ?? 0.0;
    
    $apostado_porcentaje_aplicable = $data['apostado_porcentaje_aplicable'] ?? $valores_defecto['apostado_porcentaje_aplicable'] ?? 0.0;
    $base_imponible_devengado = $apostado_sistema*$apostado_porcentaje_aplicable/100.0;
    $base_imponible_pagar     = $apostado_informado*$apostado_porcentaje_aplicable/100.0;
    
    $apostado_porcentaje_impuesto_ley = $data['apostado_porcentaje_impuesto_ley'] ?? $valores_defecto['apostado_porcentaje_impuesto_ley'] ?? 0.0;
    $impuesto_devengado = $base_imponible_devengado*$apostado_porcentaje_impuesto_ley/100.0;
    $impuesto_pagar = $base_imponible_pagar*$apostado_porcentaje_impuesto_ley/100.0;
    
    $bruto = $data['bruto'] ?? 0.0;
    $subtotal_devengado = $bruto - $impuesto_devengado;
    $subtotal_pagar     = $bruto - $impuesto_pagar;
    
    $alicuota = $data['alicuota'] ?? $valores_defecto['alicuota'] ?? 0.0;
    $total_devengado = $subtotal_devengado*$alicuota/100.0;
    $total_pagar = $subtotal_pagar*$alicuota/100.0;
    
    return compact(
      'apostado_sistema','apostado_informado',
      'apostado_porcentaje_aplicable','base_imponible_devengado','base_imponible_pagar',
      'apostado_porcentaje_impuesto_ley','impuesto_devengado','impuesto_pagar',
      'bruto','subtotal_devengado','subtotal_pagar',
      'alicuota','total_devengado','total_pagar'
    );
  }
  
  public function mesas_recalcular($año_mes,$id_casino,$fecha_cotizacion,$data){
    $ret = [
      'fecha_cotizacion' => $fecha_cotizacion,
      'cotizacion_dolar' => 0.0,
      'cotizacion_euro' => 0.0,
      'valor_dolar' => null,
      'valor_euro' => null,
      'dias_valor' => 30,
      'valor_diario_dolar' => 0.0,
      'valor_diario_euro' => 0.0,
      'dias_lunes_jueves' => null,
      'mesas_lunes_jueves' => 0,
      'dias_viernes_sabados' => null,
      'mesas_viernes_sabados' => 0,
      'dias_domingos' => null,
      'mesas_domingos' => 0,
      'dias_todos' => null,
      'mesas_todos' => 0,
      'total_dolar' => 0.0,
      'total_euro'  => 0.0,
      'total_devengado' => 0.0,
      'total_pagar' => 0.0,
    ];
    
    foreach($data as $k => $v){
      $ret[$k] = $data[$k] ?? $ret[$k] ?? null;
    }
        
    if($ret['fecha_cotizacion'] !== null){
      $ret['cotizacion_dolar'] = $ret['cotizacion_dolar'] ?? $this->cotizacion($ret['fecha_cotizacion'],2) ?? 0.0;
      $ret['cotizacion_euro']  = $ret['cotizacion_euro']  ?? $this->cotizacion($ret['fecha_cotizacion'],3) ?? 0.0;
    }
        
    if($id_casino !== null){
      $casino_mesas = $this->valorPorDefecto('casino_mesas');
      $data_cas = $casino_mesas[$id_casino] ?? [];
      $ret['valor_dolar'] = $ret['valor_dolar'] ?? $data_cas['valor_dolar'] ?? 0.0;
      $ret['valor_euro']  = $ret['valor_euro']  ?? $data_cas['valor_euro']  ?? 0.0;
    }
    
    if($ret['dias_valor']){
      if($ret['cotizacion_dolar'] !== null && $ret['valor_dolar'] !== null){
        $ret['valor_diario_dolar'] = floatval($ret['cotizacion_dolar'])*floatval($ret['valor_dolar'])/intval($ret['dias_valor']);
      }
      if($ret['cotizacion_euro'] !== null && $ret['valor_euro'] !== null){
        $ret['valor_diario_euro'] = floatval($ret['cotizacion_euro'])*floatval($ret['valor_euro'])/intval($ret['dias_valor']);
      }
    }
    
    if($año_mes !== null){
      if($ret['fecha_cotizacion'] === null){
        $año_mes_arr = explode('-',$ret['año_mes']);
        if($año_mes_arr[1] < 12){
          $año_mes_arr[1] = str_pad(intval($año_mes_arr[1])+1,2,'0',STR_PAD_LEFT);
        }
        else{
          $año_mes_arr[0] = intval($año_mes_arr[0])+1;
          $año_mes_arr[1] = '01';
        }
        $ret['fecha_cotizacion'] = implode('-',$año_mes_arr);
      }
      
      $keys = ['dias_lunes_jueves' => [1,4,0],'dias_viernes_sabados' => [5,6,0],'dias_domingos' => [0,0,0],'dias_todos' => [0,6,0]];
      if($ret['dias_lunes_jueves'] === null || $ret['dias_viernes_sabados'] === null || $ret['dias_domingos'] === null || $ret['dias_todos'] === null){
        $año_mes_arr = explode('-',$año_mes);
        $dias_en_el_mes = cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));
        for($d=1;$d<=$dias_en_el_mes;$d++){
          $año_mes_arr[2] = $d;
          $f = new \DateTime(implode('-',$año_mes_arr));
          $wd = $f->format('w');
          foreach($keys as $k => &$wdmin_wdmax_count){
            if($wd >= $wdmin_wdmax_count[0] && $wd <= $wdmin_wdmax_count[1]){
              $wdmin_wdmax_count[2] = $wdmin_wdmax_count[2] + 1;
            }
          }
        }
        foreach($keys as $k => &$wdmin_wdmax_count){
          $ret[$k] = $ret[$k] ?? $wdmin_wdmax_count[2];
        }
      }
    }
    
    $mesasdias = $ret['dias_lunes_jueves']*$ret['mesas_lunes_jueves']
    +$ret['dias_viernes_sabados']*$ret['mesas_viernes_sabados']
    +$ret['dias_domingos']*$ret['mesas_domingos']
    +$ret['dias_todos']*$ret['mesas_todos'];
    
    if($ret['valor_diario_dolar'] !== null){
      $ret['total_dolar'] = $ret['valor_diario_dolar']*($mesasdias ?? 0);
    }
    if($ret['valor_diario_euro'] !== null){
      $ret['total_euro'] = $ret['valor_diario_euro']*($mesasdias ?? 0);
    }
    
    $ret['total_devengado'] = ($ret['total_dolar'] ?? 0) + ($ret['total_euro'] ?? 0);
    $ret['total_pagar'] = $ret['total_devengado'];
    
    return $ret;
  }
  
  public function mesasAdicionales_recalcular($tipo,$data_tipo,$data){
    $valor_mensual = $data['valor_mensual'] ?? $data_tipo['valor_mensual'] ?? 0.0;
    $dias_mes = $data['dias_mes'] ?? $data_tipo['dias_mes'] ?? 0;
    $horas_dia = $data['horas_dia'] ?? $data_tipo['horas_dia'] ?? 0;
    $porcentaje = $data['porcentaje'] ?? $data_tipo['porcentaje'] ?? 0.0;
    
    $valor_diario = 0.0;
    if($dias_mes != null){
      $valor_diario = $data['valor_diario'] ?? ($valor_mensual/$dias_mes) ?? 0.0;
    }
    
    $valor_hora = 0.0;
    if($horas_dia != null){
      $valor_hora = $data['valor_hora'] ?? ($valor_diario/$horas_dia) ?? 0.0;
    }
       
    $horas = $data['horas'] ?? 0;
    $mesas = $data['mesas'] ?? 0;
    $total_devengado = $horas*$valor_hora*$mesas*($porcentaje/100.0);
    $total_pagar = $total_devengado;
    
    return compact('tipo','valor_mensual','dias_mes','valor_diario','horas_dia','valor_hora','horas','mesas','porcentaje','total_devengado','total_pagar');
  }
  
  public function guardar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      {
        $canon_viejos = DB::table('canon')
        ->whereNull('deleted_at')
        ->where('año_mes',$request->año_mes ?? null)
        ->where('id_casino',$request->id_casino ?? null)
        ->get();
        
        foreach($canon_viejos as $cv){
          $this->borrar_arr(['id_canon' => $cv->id_canon],$created_at,$id_usuario);
        }
      }
      
      $datos = $this->recalcular($request);
      
      DB::table('canon')
      ->insert([
        'año_mes' => $datos['año_mes'] ?? null,
        'id_casino' => $datos['id_casino'] ?? null,
        'estado' => $datos['estado'] ?? 'ERROR',
        'bruto_devengado' => $datos['bruto_devengado'] ?? 0,
        'deduccion' => $datos['deduccion'] ?? 0,
        'devengado' => $datos['devengado'] ?? 0,
        'porcentaje_seguridad' => $datos['porcentaje_seguridad'] ?? 0, 
        'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
        'fecha_pago' => $datos['fecha_pago'] ?? null,
        'bruto_pagar' => $datos['bruto_pagar'] ?? 0,
        'interes_mora' => $datos['interes_mora'] ?? 0,
        'mora' => $datos['mora'] ?? 0,
        'a_pagar' => $datos['a_pagar'] ?? 0,
        'pago' => $datos['pago'] ?? 0,
        'diferencia' => $datos['diferencia'] ?? 0,
        'es_antiguo' => ($datos['es_antiguo'] ?? false)? 1 : 0,
        'created_at' => $created_at,
        'created_id_usuario' => $id_usuario,
      ]);
      
      $canon = DB::table('canon')
      ->where('año_mes',$request->año_mes ?? null)
      ->where('id_casino',$request->id_casino ?? null)
      ->whereNull('deleted_at')
      ->first();
      
      foreach(($datos['canon_variable'] ?? []) as $tipo => $datos_cv){
        $datos_cv['tipo'] = $tipo;
        $datos_cv['id_canon'] = $canon->id_canon;
        DB::table('canon_variable')
        ->insert($datos_cv);
      }
      
      foreach(($datos['canon_fijo_mesas'] ?? []) as $tipo => $datos_cfm){
        $datos_cfm['id_canon'] = $canon->id_canon;//Solo hay 1 tipo
        DB::table('canon_fijo_mesas')
        ->insert($datos_cfm);
      }
      
      foreach(($datos['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $datos_cfma){
        $datos_cfma['id_canon'] = $canon->id_canon;
        $datos_cfma['tipo']     = $tipo;
        DB::table('canon_fijo_mesas_adicionales')
        ->insert($datos_cfma);
      }
      
      return 1;
    });
  }
  
  public function obtener_arr(array $request){
    $ret = (array) DB::table('canon as c')
    ->select('c.*','u.user_name as usuario')
    ->join('usuario as u','u.id_usuario','=','c.created_id_usuario')
    ->where('id_canon',$request['id_canon'])
    ->first();
    $ret = $ret ?? [];
        
    $ret['canon_variable'] = DB::table('canon_variable')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
    
    $ret['canon_fijo_mesas'] = [];
    $ret['canon_fijo_mesas']['Mesas'] = collect(DB::table('canon_fijo_mesas')->where('id_canon',$request['id_canon'])->first());
    if($ret['canon_fijo_mesas']['Mesas']->count() == 0){
      unset($ret['canon_fijo_mesas']['Mesas']);
    }
    
    $ret['canon_fijo_mesas_adicionales'] = DB::table('canon_fijo_mesas_adicionales')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
    
    return $ret;
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
  
  public function borrar(Request $request,$deleted_at = null,$deleted_id_usuario = null){
    return $this->borrar_arr($request,$deleted_at,$deleted_id_usuario);
  }
  
  public function borrar_arr($arr,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($arr,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$arr['id_canon'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
  
  public function buscar(){
    $ret = DB::table('canon')
    ->select('canon.*','casino.nombre as casino')
    ->join('casino','casino.id_casino','=','canon.id_casino')
    ->whereNull('canon.deleted_at')
    ->orderBy('id_casino','desc')
    ->orderBy('año_mes','desc')
    ->paginate($request->page_size ?? 10);
    //Necesito transformar la data paginada pero si llamo transform() elimina toda la data de paginado
    $ret2 = $ret->toArray();
    
    //@HACK: asume que esta ordenado por año_mes descendiente
    //cambiar el algoritmo si se da la posibilidiad de reordenar
    $saldo_anterior = [];
    $ret2['data'] = $ret->reverse()->transform(function(&$c) use (&$saldo_anterior){
      if(($saldo_anterior[$c->id_casino] ?? null) === null){
        $saldo_anterior[$c->id_casino] = $this->calcular_saldo_hasta($c->año_mes,$c->id_casino);
      }
      $c->saldo_posterior = $saldo_anterior[$c->id_casino]+$c->diferencia;
      $saldo_anterior[$c->id_casino] = $c->saldo_posterior;
      return $c;
    })->reverse();
    
    return $ret2;
  }
  
  public function cotizacion($fecha_cotizacion,$id_tipo_moneda){
    if(empty($fecha_cotizacion) || empty($id_tipo_moneda)) return null;
    return null;//@TODO
  }
  
  private function valorPorDefecto($k){
    $db = DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->where('campo',$k)
    ->first();
        
    $val = is_null($db)? '{}' : preg_replace('/(\r\n|\n|\s\s+)/i','',$db->valor);
    
    return json_decode($val,true);
  }
    
  public function valoresPorDefecto(Request $request){
    return DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->orderBy('campo','asc')
    ->paginate($request->page_size);
  }
  
  public function valoresPorDefecto_ingresar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $vals_viejos = DB::table('canon_valores_por_defecto')
      ->whereNull('deleted_at')
      ->where('campo',$request->campo ?? '')->get();
      foreach($vals_viejos as $v){
        $this->valoresPorDefecto_borrar_arr(['id_canon_valor_por_defecto' => $v->id_canon_valor_por_defecto],$created_at,$id_usuario);
      }
      
      DB::table('canon_valores_por_defecto')
      ->insert([
        'campo' => $request->campo ?? '',
        'valor' => $request->valor ?? '',
        'created_at' => $created_at,
        'deleted_at' => null,
        'created_id_usuario' => $id_usuario,
        'deleted_id_usuario' => null,
      ]);
      
      return 1;
    });
  }
  
  public function valoresPorDefecto_borrar(Request $request,$deleted_at = null,$deleted_id_usuario = null){
    return $this->valoresPorDefecto_borrar_arr($request,$deleted_at,$deleted_id_usuario);
  }
  
  public function valoresPorDefecto_borrar_arr($arr,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($arr,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_valores_por_defecto')
      ->whereNull('deleted_at')
      ->where('id_canon_valor_por_defecto',$arr['id_canon_valor_por_defecto'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
}
