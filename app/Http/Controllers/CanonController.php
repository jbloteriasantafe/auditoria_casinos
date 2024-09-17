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
  
  private function getValorPorDefecto($k){
    $db = DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->where('campo',$k)
    ->first();
        
    $val = is_null($db)? '{}' : preg_replace('/(\r\n|\n|\s\s+)/i','',$db->valor);
    
    return json_decode($val,true);
  }
    
  public function index(){
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos;
    $plataformas = Plataforma::all();
    
    $casino_tipo_mesas_adicionales = $this->getValorPorDefecto('casino_tipo_mesas_adicionales');
    $casino_mesas = $this->getValorPorDefecto('casino_mesas');
                 
    return View::make('Canon.ncanon', compact('casinos','plataformas','casino_tipo_mesas_adicionales','casino_mesas','primary_keys'));
  }
  
  public function recalcular(Request $request){
    $ret = [];
    $ret['año_mes'] = $request['año_mes'] ?? null;
    $ret['id_casino'] = $request['id_casino'] ?? null;
    $ret['estado'] = 'Generado';
    $ret['fecha_cotizacion'] = $request['fecha_cotizacion'] ?? null;
    $ret['fecha_vencimiento'] = $request['fecha_vencimiento'] ?? null;
    $ret['fecha_pago'] = $request['fecha_pago'] ?? null;  
    
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
    
    $canon_variable_vals_defecto = ($this->getValorPorDefecto('canon_variable') ?? [])[$ret['id_casino']] ?? [];
    $ret['canon_variable']  = [];
    $ret['bruto_devengado'] = 0.0;
    $ret['bruto_pagar'] = 0.0;
    foreach(($request['canon_variable'] ?? $canon_variable_vals_defecto ?? []) as $cv => $_){
      $datos_defecto = $canon_variable_vals_defecto[$cv] ?? [];
      $ret['canon_variable'][$cv] = $this->canon_variable_recalcular(
        $datos_defecto,
        $request['canon_variable'][$cv] ?? []
      );
      $ret['bruto_devengado'] += $ret['canon_variable'][$cv]['total_devengado'] ?? 0.0;
      $ret['bruto_pagar'] += $ret['canon_variable'][$cv]['total_pagar'] ?? 0.0;
    }
    
    $ret['canon_fijo'] = [];
    $ret['canon_fijo']['mesas'] = $this->mesas_recalcular_v2($ret['año_mes'],$ret['id_casino'],$ret['fecha_cotizacion'],$request['canon_fijo']['mesas'] ?? []);
    $ret['bruto_devengado'] += $ret['canon_fijo']['mesas']['total_devengado'];
    $ret['bruto_pagar'] += $ret['canon_fijo']['mesas']['total_pagar'];
    
    $ret['canon_fijo']['mesas_adicionales'] = [];
    $tipo_mesas_adicionales = $this->getValorPorDefecto('casino_tipo_mesas_adicionales')[$ret['id_casino']] ?? [];
    foreach(($request['canon_fijo']['mesas_adicionales'] ?? $tipo_mesas_adicionales ?? []) as $tipo_ma => $_){
      $ret['canon_fijo']['mesas_adicionales'][$tipo_ma] = $this->mesasAdicionales_recalcular(
        $tipo_ma,
        $tipo_mesas_adicionales[$tipo_ma] ?? [],
        ($request['canon_fijo']['mesas_adicionales'] ?? [])[$tipo_ma] ?? []
      );
      $ret['bruto_devengado'] += $ret['canon_fijo']['mesas_adicionales'][$tipo_ma]['total_devengado'];
      $ret['bruto_pagar'] += $ret['canon_fijo']['mesas_adicionales'][$tipo_ma]['total_pagar'];
    }
    
    $ret['bruto_devengado'] = $ret['bruto_devengado'] ?? 0.0;
    $ret['bruto_pagar'] = $ret['bruto_pagar'] ?? 0.0;
    $ret['deduccion'] = $request['deduccion'] ?? null;
    $ret['devengado'] = $ret['bruto_devengado'] - ($ret['deduccion'] ?? 0.0);
    
    $ret['porcentaje_seguridad'] = null;
    if($ret['bruto_devengado'] != null){
      $ret['porcentaje_seguridad'] = 100.0*($ret['bruto_devengado']-$ret['devengado'])/$ret['bruto_devengado'];
    }
    
    $ret['interes_mora'] = $request['interes_mora'] ?? null;
    $ret['pago'] = $request['pago'] ?? null;
    $ret['mora'] = $request['mora'] ?? null;
    
    if($ret['fecha_vencimiento'] && $ret['fecha_pago']){
      $timestamp_venc = \DateTimeImmutable::createFromFormat('Y-m-d', $ret['fecha_vencimiento']);
      $timestamp_pago = \DateTimeImmutable::createFromFormat('Y-m-d', $ret['fecha_pago']);
      $date_interval  = $timestamp_pago->diff($timestamp_venc);
      $cantidad_dias = intval($date_interval->format('%d'));
      if($cantidad_dias < 0){}
      else if(!is_null($ret['interes_mora'])){//Si envio el interes, calculo el pago
        $ret['pago'] = $ret['bruto_devengado']*pow(1+$ret['interes_mora']/100.0,$cantidad_dias);
        $ret['mora'] = $ret['pago'] - $ret['bruto_devengado'];
      }
      else if(!is_null($ret['pago'])){//Si envio el pago, calculo el interes
        $coeff = log($ret['pago']/$ret['bruto_devengado'])/($cantidad_dias == 0? 1 : $cantidad_dias);
        $ret['interes_mora'] = (exp($coeff)-1)*100;
        $ret['mora'] = $ret['pago'] - $ret['bruto_devengado'];
      }
      else if(!is_null($ret['mora'])){
        $ret['pago'] = $ret['bruto_devengado']+$ret['mora'];
        $coeff = log($ret['pago']/$ret['bruto_devengado'])/($cantidad_dias == 0? 1 : $cantidad_dias);
        $ret['interes_mora'] = (exp($coeff)-1)*100;
      }
      else {
        $ret['pago'] = $ret['bruto_devengado'];
        $ret['interes_mora'] = 0;
        $ret['mora'] = 0;
      }
    }
    
    return $ret;
  }
  
  public function canon_variable_recalcular($valores_defecto,$data){
    $apostado_sistema = $data['apostado_sistema'] ?? null;
    $apostado_informado = $data['apostado_informado'] ?? null;
    
    $apostado_porcentaje_aplicable = $data['apostado_porcentaje_aplicable'] ?? $valores_defecto['apostado_porcentaje_aplicable'] ?? 0.0;
    $base_imponible_devengado = ($apostado_sistema ?? 0.0)*$apostado_porcentaje_aplicable/100.0;
    $base_imponible_pagar     = ($apostado_informado ?? 0.0)*$apostado_porcentaje_aplicable/100.0;
    
    $apostado_porcentaje_impuesto_ley = $data['apostado_porcentaje_impuesto_ley'] ?? $valores_defecto['apostado_porcentaje_impuesto_ley'] ?? 0.0;
    $impuesto_devengado = $base_imponible_devengado*$apostado_porcentaje_impuesto_ley/100.0;
    $impuesto_pagar = $base_imponible_pagar*$apostado_porcentaje_impuesto_ley/100.0;
    
    $bruto = $data['bruto'] ?? null;
    $subtotal_devengado = ($bruto ?? 0.0) - $impuesto_devengado;
    $subtotal_pagar     = ($bruto ?? 0.0) - $impuesto_pagar;
    
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
  
  public function mesas_recalcular_v2($año_mes,$id_casino,$fecha_cotizacion,$data){
    $ret = [
      'año_mes' => $año_mes,
      'fecha_cotizacion' => $fecha_cotizacion,
      'cotizacion_dolar' => null,
      'cotizacion_euro' => null,
      'valor_dolar' => null,
      'valor_euro' => null,
      'dias_valor' => 30,
      'valor_diario_dolar' => null,
      'valor_diario_euro' => null,
      'dias_lunes_jueves' => null,
      'mesas_lunes_jueves' => null,
      'dias_viernes_sabados' => null,
      'mesas_viernes_sabados' => null,
      'dias_domingos' => null,
      'mesas_domingos' => null,
      'dias_todos' => null,
      'mesas_todos' => null,
      'total_dolar' => null,
      'total_euro'  => null,
      'total' => null,
    ];
    
    foreach($data as $k => $v){
      $ret[$k] = $data[$k] ?? $ret[$k] ?? null;
    }
        
    if($ret['fecha_cotizacion'] !== null){
      $ret['cotizacion_dolar'] = $ret['cotizacion_dolar'] ?? $this->mesas_cotizacion($ret['fecha_cotizacion'],2);
      $ret['cotizacion_euro']  = $ret['cotizacion_euro']  ?? $this->mesas_cotizacion($ret['fecha_cotizacion'],3);
    }
        
    if($id_casino !== null){
      $casino_mesas = $this->getValorPorDefecto('casino_mesas');
      $data_cas = $casino_mesas[$id_casino] ?? [];
      $ret['valor_dolar'] = $ret['valor_dolar'] ?? ($data_cas['valor_dolar'] ?? null);
      $ret['valor_euro']  = $ret['valor_euro']  ?? ($data_cas['valor_euro'] ?? null);
    }
    
    if($ret['dias_valor']){
      if($ret['cotizacion_dolar'] !== null && $ret['valor_dolar'] !== null){
        $ret['valor_diario_dolar'] = floatval($ret['cotizacion_dolar'])*floatval($ret['valor_dolar'])/intval($ret['dias_valor']);
      }
      if($ret['cotizacion_euro'] !== null && $ret['valor_euro'] !== null){
        $ret['valor_diario_euro'] = floatval($ret['cotizacion_euro'])*floatval($ret['valor_euro'])/intval($ret['dias_valor']);
      }
    }
    
    if($ret['año_mes'] !== null){
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
        $año_mes_arr = explode('-',$ret['año_mes']);
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
  
  public function mesasAdicionales_recalcular($tipo_torneo,$data_tipo_torneo,$data){
    $valor_mensual = $data['valor_mensual'] ?? $data_tipo_torneo['valor_mensual'] ?? null;
    $dias_mes = $data['dias_mes'] ?? $data_tipo_torneo['dias_mes'] ?? null;
    $horas_dia = $data['horas_dia'] ?? $data_tipo_torneo['horas_dia'] ?? null;
    $porcentaje = $data['porcentaje'] ?? $data_tipo_torneo['porcentaje'] ?? null;
    
    $valor_diario = null;
    if($dias_mes != null){
      $valor_diario = $data['valor_diario'] ?? ($valor_mensual/$dias_mes) ?? null;
    }
    
    $valor_hora = null;
    if($horas_dia != null){
      $valor_hora = $data['valor_hora'] ?? ($valor_diario/$horas_dia) ?? null;
    }
       
    $horas = $data['horas'] ?? null;
    $mesas = $data['mesas'] ?? null;
    $total_devengado = ($horas ?? 0)*($valor_hora ?? 0)*($mesas ?? 0)*(($porcentaje ?? 0.0)/100.0);
    $total_pagar = $total_devengado;
    
    return compact('tipo_torneo','valor_mensual','dias_mes','valor_diario','horas_dia','valor_hora','horas','mesas','porcentaje','total_devengado','total_pagar');
  }
  
  public function total(){
    return [
      'current_page' => 1,
      'per_page'     => 10,
      'from'         => 1,
      'to'           => 10,
      'data'         => [],
      'total'        => 0,
      'last_page'    => ceil(1/10)
    ];
  }
  
  public function total_fisico(){
    return [
      'current_page' => 1,
      'per_page'     => 10,
      'from'         => 1,
      'to'           => 10,
      'data'         => [],
      'total'        => 0,
      'last_page'    => ceil(1/10)
    ];
  }
  
  public function maquinas(Request $request){
    return DB::table('canon_maquinas')
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->orderBy('casino','desc')
    ->paginate($request->page_size);
  }
  
  public function maquinas_ingresar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $data = [
        'año_mes' => null,
        'casino'  => null,
        'bruto'   => null,
        'alicuota' => null,
        'total'   => null,
        'created_at' => null,
        'deleted_at' => null,
        'created_id_usuario' => null,
        'deleted_id_usuario' => null,
        'estado' => null,
      ];
      
      foreach($data as $k => $dflt){
        $data[$k] = $request[$k] ?? $dflt;
      }
      
      $data['total'] = $data['bruto']*(100.0-$data['alicuota'])/100.0;
      
      $a_borrar = DB::table('canon_maquinas')
      ->where('casino',$data['casino'])
      ->where('año_mes',$data['año_mes'])
      ->whereNull('deleted_at')->get();
      
      foreach($a_borrar as $b){
        $this->bingo_borrar_arr(['id' => $b->id_canon_maquinas],$created_at,$id_usuario);
      }
      
      $data['estado'] = 'Generado';
      $data['created_at'] = $created_at;
      $data['deleted_at'] = null;
      $data['created_id_usuario'] = $id_usuario;
      $data['deleted_id_usuario'] = null;
      
      DB::table('canon_maquinas')
      ->insert($data);
      
      return 1;
    });
  }
  
  public function maquinas_borrar(Request $request){
    return $this->maquinas_borrar_arr($request->all());
  }
  
  private function maquinas_borrar_arr($request,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($request,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_maquinas')
      ->whereNull('deleted_at')
      ->where('id_canon_maquinas',$request['id'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
  
  public function jol(Request $request){
    return DB::table('canon_jol')
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->orderBy('plataforma','desc')
    ->paginate($request->page_size);
  }
  
  public function jol_ingresar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $data = [
        'año_mes' => null,
        'plataforma'  => null,
        'bruto'   => null,
        'alicuota' => null,
        'total'   => null,
        'created_at' => null,
        'deleted_at' => null,
        'created_id_usuario' => null,
        'deleted_id_usuario' => null,
        'estado' => null,
      ];
      
      foreach($data as $k => $dflt){
        $data[$k] = $request[$k] ?? $dflt;
      }
      
      $data['total'] = $data['bruto']*(100.0-$data['alicuota'])/100.0;
      
      $a_borrar = DB::table('canon_jol')
      ->where('plataforma',$data['plataforma'])
      ->where('año_mes',$data['año_mes'])
      ->whereNull('deleted_at')->get();
      
      foreach($a_borrar as $b){
        $this->bingo_borrar_arr(['id' => $b->id_canon_jol],$created_at,$id_usuario);
      }
      
      $data['estado'] = 'Generado';
      $data['created_at'] = $created_at;
      $data['deleted_at'] = null;
      $data['created_id_usuario'] = $id_usuario;
      $data['deleted_id_usuario'] = null;
      
      DB::table('canon_jol')
      ->insert($data);
      
      return 1;
    });
  }
  
  public function jol_borrar(Request $request){
    return $this->jol_borrar_arr($request->all());
  }
  
  private function jol_borrar_arr($request,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($request,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_jol')
      ->whereNull('deleted_at')
      ->where('id_canon_jol',$request['id'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
  
  public function bingo(Request $request){
    return DB::table('canon_bingo')
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->orderBy('casino','desc')
    ->paginate($request->page_size);
  }
  
  public function bingo_ingresar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $data = [
        'año_mes' => null,
        'casino'  => null,
        'bruto'   => null,
        'alicuota' => null,
        'total'   => null,
        'created_at' => null,
        'deleted_at' => null,
        'created_id_usuario' => null,
        'deleted_id_usuario' => null,
        'estado' => null,
      ];
      
      foreach($data as $k => $dflt){
        $data[$k] = $request[$k] ?? $dflt;
      }
      
      $data['total'] = $data['bruto']*(100.0-$data['alicuota'])/100.0;
      
      $a_borrar = DB::table('canon_bingo')
      ->where('casino',$data['casino'])
      ->where('año_mes',$data['año_mes'])
      ->whereNull('deleted_at')->get();
      
      foreach($a_borrar as $b){
        $this->bingo_borrar_arr(['id' => $b->id_canon_bingo],$created_at,$id_usuario);
      }
      
      $data['estado'] = 'Generado';
      $data['created_at'] = $created_at;
      $data['deleted_at'] = null;
      $data['created_id_usuario'] = $id_usuario;
      $data['deleted_id_usuario'] = null;
      
      DB::table('canon_bingo')
      ->insert($data);
      
      return 1;
    });
  }
  
  public function bingo_borrar(Request $request){
    return $this->bingo_borrar_arr($request->all());
  }
  
  private function bingo_borrar_arr($request,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($request,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_bingo')
      ->whereNull('deleted_at')
      ->where('id_canon_bingo',$request['id'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
  
  public function mesas(Request $request){
    return DB::table('canon_mesas_v2')
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->orderBy('casino','desc')
    ->paginate($request->page_size);
  }
  
  public function mesas_ingresar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $data = [
        'año_mes' => null,
        'casino'  => null,
        'fecha_cotizacion' => null,
        'dias_valor' => 30,
        'cotizacion_dolar' => null,
        'valor_dolar' => null,
        'cotizacion_euro' => null,
        'valor_euro' => null,
        'dias_lunes_jueves' => null,
        'mesas_lunes_jueves' => null,
        'dias_viernes_sabados' => null,
        'mesas_viernes_sabados' => null,
        'dias_domingos' => null,
        'mesas_domingos' => null,
        'dias_todos' => null,
        'mesas_todos' => null
      ];
      
      foreach($data as $k => $dflt){
        $data[$k] = $request[$k] ?? $dflt;
      }
      
      $a_borrar = DB::table('canon_mesas_v2')
      ->where('casino',$data['casino'])
      ->where('año_mes',$data['año_mes'])
      ->whereNull('deleted_at')->get();
      
      foreach($a_borrar as $b){
        $this->mesas_borrar_arr(['id' => $b->id_canon_mesas_v2],$created_at,$id_usuario);
      }
      
      $data = $this->mesas_recalcular_arr($data);
      $data['estado'] = 'Generado';
      $data['created_at'] = $created_at;
      $data['deleted_at'] = null;
      $data['created_id_usuario'] = $id_usuario;
      $data['deleted_id_usuario'] = null;
      
      DB::table('canon_mesas_v2')
      ->insert($data);
      
      return 1;
    });
  }
  
  public function mesas_borrar(Request $request,$deleted_at = null,$deleted_id_usuario = null){
    return $this->mesas_borrar_arr($request,$deleted_at,$deleted_id_usuario);
  }
  
  private function mesas_borrar_arr($request,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($request,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_mesas_v2')
      ->whereNull('deleted_at')
      ->where('id_canon_mesas_v2',$request['id'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
  
  public function mesas_cotizacion($fecha_cotizacion,$id_tipo_moneda){
    if(empty($fecha_cotizacion) || empty($id_tipo_moneda)) return null;
    return rand()%2000 + 1;
  }
  
  public function mesas_recalcular(Request $request){
    return $this->mesas_recalcular_arr($request);
  }
  public function mesas_recalcular_arr($request){
    $ret = [
      'año_mes' => null,
      'casino'  => null,
      'fecha_cotizacion' => null,
      'dias_valor' => 30,
      'cotizacion_dolar' => null,
      'valor_dolar' => null,
      'cotizacion_euro' => null,
      'valor_euro' => null,
      'valor_diario_dolar' => null,
      'valor_diario_euro' => null,
      'dias_lunes_jueves' => null,
      'mesas_lunes_jueves' => null,
      'dias_viernes_sabados' => null,
      'mesas_viernes_sabados' => null,
      'dias_domingos' => null,
      'mesas_domingos' => null,
      'dias_todos' => null,
      'mesas_todos' => null,
      'total_dolar' => null,
      'total_euro' => null,
      'total' => null,
    ];
        
    foreach($ret as $attr => $dflt){
      $ret[$attr] = $request[$attr] ?? $dflt;
    }
    
    if($ret['año_mes'] !== null){
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
        $año_mes_arr = explode('-',$ret['año_mes']);
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
            
    if($ret['fecha_cotizacion'] !== null){
      $ret['cotizacion_dolar'] = $ret['cotizacion_dolar'] ?? $this->mesas_cotizacion($ret['fecha_cotizacion'],2);
      $ret['cotizacion_euro']  = $ret['cotizacion_euro']  ?? $this->mesas_cotizacion($ret['fecha_cotizacion'],3);
    }
    
    if($ret['casino'] !== null){
      $casino_mesas = $this->getValorPorDefecto('casino_mesas');
      $data_cas = $casino_mesas[$ret['casino']] ?? [];
      $ret['valor_dolar'] = $ret['valor_dolar'] ?? ($data_cas['valor_dolar'] ?? null);
      $ret['valor_euro']  = $ret['valor_euro']  ?? ($data_cas['valor_euro'] ?? null);
    }
    
    if($ret['dias_valor']){
      if($ret['cotizacion_dolar'] !== null && $ret['valor_dolar'] !== null){
        $ret['valor_diario_dolar'] = floatval($ret['cotizacion_dolar'])*floatval($ret['valor_dolar'])/intval($ret['dias_valor']);
      }
      if($ret['cotizacion_euro'] !== null && $ret['valor_euro'] !== null){
        $ret['valor_diario_euro'] = floatval($ret['cotizacion_euro'])*floatval($ret['valor_euro'])/intval($ret['dias_valor']);
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
    
    $ret['total'] = ($ret['total_dolar'] ?? 0) + ($ret['total_euro'] ?? 0);
    
    return $ret;
  }
  
  public function mesasAdicionales(Request $request){
    return DB::table('canon_mesas_adicionales')
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->orderBy('casino','desc')
    ->paginate($request->page_size);
  }
  
  public function mesasAdicionales_ingresar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
            
      $tipo       = $request->tipo ?? '';
      $valor_hora = $request->valor_hora ?? 0;
      $horas      = $request->horas ?? 0;
      $mesas      = $request->mesas ?? 0;
      $porcentaje = $request->porcentaje ?? 0;
      $total = $valor_hora*$horas*$mesas*($porcentaje/100.0);
      
      DB::table('canon_mesas_adicionales')
      ->insert([
        'año_mes' => $request->año_mes ?? null,
        'casino'  => $request->casino ?? null,
        'tipo'    => $tipo,
        'valor_hora' => $valor_hora,
        'horas'      => $horas,
        'mesas'      => $mesas,
        'porcentaje' => $porcentaje,
        'total'      => $total,
        'estado'     => 'Generado',
        'created_at' => $created_at,
        'deleted_at' => null,
        'created_id_usuario' => $id_usuario,
        'deleted_id_usuario' => null,
      ]);
      
      return 1;
    });
  }
  
  public function mesasAdicionales_borrar(Request $request,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($request,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_mesas_adicionales')
      ->whereNull('deleted_at')
      ->where('id_canon_mesas_adicionales',$request->id ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
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
        $this->valoresPorDefecto_borrar_arr(['id' => $v->id_canon_valor_por_defecto],$created_at,$id_usuario);
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
      ->where('id_canon_valor_por_defecto',$arr['id'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
  
  public function antiguo(Request $request){
    return DB::table('canon_antiguo')
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->orderBy('casino','desc')
    ->paginate($request->page_size);
  }
  
  public function antiguo_ingresar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      {
        $viejos = DB::table('canon_antiguo')
        ->where('casino',$request['casino'])
        ->where('año_mes',$request['año_mes'])
        ->whereNull('deleted_at')->get();
        foreach($viejos as $v){
          $this->antiguo_borrar_arr(['id' => $v->id_canon_antiguo],$created_at,$id_usuario);
        }
      }
      
      $this->antiguo_borrar($request,$created_at,$id_usuario);
      
      $devengado = $request->devengado ?? 0;
      $bruto     = $request->bruto ?? 0;
      $pagado    = $request->pagado ?? 0;
      $mora      = $pagado - $bruto;
      $deduccion = $bruto  - $devengado;
      
      $porcentaje_seguridad = $bruto-$devengado;
      if($bruto < 0.01){
        $porcentaje_seguridad = null;
      }
      else if($porcentaje_seguridad < 0.01){
        $porcentaje_seguridad = 0;
      }
      else{
        $porcentaje_seguridad = 100*$porcentaje_seguridad/$bruto;
      }
      
      $fecha_vencimiento = $request->fecha_vencimiento ?? null;
      $fecha_pago        = $request->fecha_pago ?? null;
      $cantidad_dias;{
        $timestamp_venc = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha_vencimiento);
        $timestamp_pago = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha_pago);
        $date_interval  = $timestamp_pago->diff($timestamp_venc);
        if(empty($date_interval)){
          throw new \Exception('Faltan las fechas');
        }
        $cantidad_dias = intval($date_interval->format('%d'));
      }
      
      $interes_mora = null;
      if($cantidad_dias > 0){
        if($bruto >= 0.01){
          if($pagado >= 0.01){
            $coeff = log($pagado/$bruto,M_E)/$cantidad_dias;
            $interes_mora = (exp($coeff)-1)*100;
          }
          else{
            $interes_mora = null;
          }
        }
        else{
          $interes_mora = 0;
        }
      }
      
      DB::table('canon_antiguo')
      ->insert([
        'año_mes' => $request->año_mes ?? null,
        'casino'  => $request->casino ?? null,
        'fecha_vencimiento' => $request->fecha_vencimiento ?? null,
        'fecha_pago'        => $request->fecha_pago ?? null,
        'bruto'        => $bruto,
        'devengado'    => $devengado,
        'porcentaje_seguridad' => $porcentaje_seguridad,//@TODO: pasar a columna calculada ??
        'pagado'       => $pagado,
        'deduccion'    => $deduccion,
        'interes_mora' => $interes_mora,//@TODO: pasar a columna calculada ??
        'mora'         => $mora,
        'created_at' => $created_at,
        'deleted_at' => null,
        'created_id_usuario' => $id_usuario,
        'deleted_id_usuario' => null,
      ]);
      
      return 1;
    });
  }
  
  public function antiguo_borrar(Request $request,$deleted_at = null,$deleted_id_usuario = null){
    return $this->antiguo_borrar_arr($request,$deleted_at,$deleted_id_usuario);
  }
  
  public function antiguo_borrar_arr($request,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($request,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_antiguo')
      ->whereNull('deleted_at')
      ->where('id_canon_antiguo',$request['id'])
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
}
