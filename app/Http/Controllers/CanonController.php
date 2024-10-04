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
use App\Archivo;

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
    return View::make('Canon.ncanon', compact('casinos','plataformas'));
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
    $ret['adjuntos'] = $request['adjuntos'] ?? [];
    
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
        $defecto = ($this->valorPorDefecto('canon_variable') ?? [])[$ret['id_casino']] ?? [];
        foreach(($request['canon_variable'] ?? $defecto ?? []) as $tipo => $_){
          $ret['canon_variable'][$tipo] = $this->canon_variable_recalcular(
            $tipo,
            $defecto[$tipo] ?? [],
            ($request['canon_variable'] ?? [])[$tipo] ?? []
          );
          $ret['bruto_devengado'] += $ret['canon_variable'][$tipo]['total_devengado'] ?? 0.0;
          $ret['bruto_pagar'] += $ret['canon_variable'][$tipo]['total_pagar'] ?? 0.0;
        }
      }
      {//Dos tipos muy parecidos (Fijas y Diarias), se hace asi mas que nada para que sea homogeneo
        $defecto = $this->valorPorDefecto('canon_fijo_mesas')[$ret['id_casino']] ?? [];
        foreach(($request['canon_fijo_mesas'] ?? $defecto ?? []) as $tipo => $_){
          $ret['canon_fijo_mesas'][$tipo] = $this->mesas_recalcular(
            $ret['año_mes'],
            $ret['id_casino'],
            $ret['fecha_cotizacion'],
            $tipo,
            $defecto[$tipo] ?? [],
            ($request['canon_fijo_mesas'] ?? [])[$tipo] ?? []
          );
          $ret['bruto_devengado'] += $ret['canon_fijo_mesas'][$tipo]['total_devengado'] ?? 0.0;
          $ret['bruto_pagar'] += $ret['canon_fijo_mesas'][$tipo]['total_pagar'] ?? 0.0; 
        }
      }
      {//Las mesas adicionales pueden ser varios tipos (Torneo Truco, Torneo Poker, etc)
        $defecto = $this->valorPorDefecto('canon_fijo_mesas_adicionales')[$ret['id_casino']] ?? [];
        foreach(($request['canon_fijo_mesas_adicionales'] ?? $defecto ?? []) as $tipo => $_){
          $ret['canon_fijo_mesas_adicionales'][$tipo] = $this->mesasAdicionales_recalcular(
            $tipo,
            $defecto[$tipo] ?? [],
            ($request['mesas_adicionales'] ?? [])[$tipo] ?? []
          );
          $ret['bruto_devengado'] += $ret['canon_fijo_mesas_adicionales'][$tipo]['total_devengado'] ?? 0.0;
          $ret['bruto_pagar'] += $ret['canon_fijo_mesas_adicionales'][$tipo]['total_pagar'] ?? 0.0;
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
  
  public function canon_variable_recalcular($tipo,$valores_defecto,$data){
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
    
    return compact('tipo',
      'apostado_sistema','apostado_informado',
      'apostado_porcentaje_aplicable','base_imponible_devengado','base_imponible_pagar',
      'apostado_porcentaje_impuesto_ley','impuesto_devengado','impuesto_pagar',
      'bruto','subtotal_devengado','subtotal_pagar',
      'alicuota','total_devengado','total_pagar'
    );
  }
  
  public function mesas_recalcular(
      $año_mes,$id_casino,
      $fecha_cotizacion,//@RETORNADO
      $tipo,//@RETORNADO
      $datos_defecto,$data
  ){
    
    $cotizacion_dolar = 0.0;//@RETORNADO
    $cotizacion_euro  = 0.0;//@RETORNADO
    if($fecha_cotizacion !== null){
      $cotizacion_dolar = $data['cotizacion_dolar'] ?? $this->cotizacion($fecha_cotizacion,2) ?? 0.0;
      $cotizacion_euro  = $data['cotizacion_euro']  ?? $this->cotizacion($fecha_cotizacion,3) ?? 0.0;
    }

    $valor_dolar = 0.0;//@RETORNADO
    $valor_euro  = 0.0;//@RETORNADO
    if($id_casino !== null){
      $valor_dolar = $data['valor_dolar'] ?? $datos_defecto['valor_dolar'] ?? 0.0;
      $valor_euro  = $data['valor_euro']  ?? $datos_defecto['valor_euro']  ?? 0.0;
    }
    
    $dias_valor = $data['dias_valor'] ?? $datos_defecto['dias_valor'] ?? 0;//@RETORNADO
    $valor_diario_dolar = 0.0;//@RETORNADO
    $valor_diario_euro  = 0.0;//@RETORNADO
    if($dias_valor != 0){//No entra si es =0, nulo, o falta
      $valor_diario_dolar = floatval($cotizacion_dolar)*floatval($valor_dolar)/$dias_valor;
      $valor_diario_euro  = floatval($cotizacion_euro) *floatval($valor_euro) /$dias_valor;
    }
    
    $dias_lunes_jueves = 0;//@RETORNADO
    $dias_viernes_sabados = 0;//@RETORNADO
    $dias_domingos = 0;//@RETORNADO
    $dias_todos = 0;//@RETORNADO
    $dias_fijos = $data['dias_fijos'] ?? $datos_defecto['dias_fijos'] ?? 0;//@RETORNADO
    if($año_mes !== null){
      if($fecha_cotizacion === null){
        $año_mes_arr = explode('-',$ret['año_mes']);
        if($año_mes_arr[1] < 12){
          $año_mes_arr[1] = str_pad(intval($año_mes_arr[1])+1,2,'0',STR_PAD_LEFT);
        }
        else{
          $año_mes_arr[0] = intval($año_mes_arr[0])+1;
          $año_mes_arr[1] = '01';
        }
        $fecha_cotizacion = implode('-',$año_mes_arr);
      }
      
      $dias = [
        'dias_lunes_jueves'    => $data['dias_lunes_jueves'] ?? null,
        'dias_viernes_sabados' => $data['dias_viernes_sabados'] ?? null,
        'dias_domingos'        => $data['dias_domingos'] ?? null,
        'dias_todos'           => $data['dias_todos'] ?? null
      ];
      
      $calcular_alguno = array_reduce($dias,function($carry,$item){
        return $carry || ($item === null);//Si alguno esta vacio, hay que calcular
      },false);
      
      $wdmin_wdmax_count_arr = [
        'dias_lunes_jueves'    => [1,4,0],
        'dias_viernes_sabados' => [5,6,0],
        'dias_domingos'        => [0,0,0],
        'dias_todos'           => [0,6,0],
      ];
      
      if($calcular_alguno){
        $año_mes_arr = explode('-',$año_mes);
        $dias_en_el_mes = cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));
        for($d=1;$d<=$dias_en_el_mes;$d++){
          $año_mes_arr[2] = $d;
          $f = new \DateTime(implode('-',$año_mes_arr));
          $wd = $f->format('w');
          foreach($wdmin_wdmax_count_arr as $k => &$wdmin_wdmax_count){
            if($dias[$k] !== null) continue;//No calcularlo si viene puesto
            if($wd >= $wdmin_wdmax_count[0] && $wd <= $wdmin_wdmax_count[1]){
              $wdmin_wdmax_count[2] = $wdmin_wdmax_count[2] + 1;
            }
          }
        }
      }
      
      $dias_lunes_jueves    = (($datos_defecto['calcular_dias_lunes_jueves'] ?? true) || ($dias['dias_lunes_jueves'] !== null))? 
        ($dias['dias_lunes_jueves'] ?? $wdmin_wdmax_count_arr['dias_lunes_jueves'][2])
      : 0;
      $dias_viernes_sabados = (($datos_defecto['calcular_dias_viernes_sabados'] ?? true) || ($dias['dias_viernes_sabados'] !== null))? 
        ($dias['dias_viernes_sabados'] ?? $wdmin_wdmax_count_arr['dias_viernes_sabados'][2])
      : 0;
      $dias_domingos    = (($datos_defecto['calcular_dias_domingos'] ?? true) || ($dias['dias_domingos'] !== null))? 
        ($dias['dias_domingos'] ?? $wdmin_wdmax_count_arr['dias_domingos'][2])
      : 0;
      $dias_todos    = (($datos_defecto['calcular_dias_todos'] ?? true) || ($dias['dias_todos'] !== null))? 
        ($dias['dias_todos'] ?? $wdmin_wdmax_count_arr['dias_todos'][2])
      : 0;
    }
    
    $mesas_lunes_jueves      = $data['mesas_lunes_jueves'] ?? 0;//@RETORNADO
    $mesas_viernes_sabados   = $data['mesas_viernes_sabados'] ?? 0;//@RETORNADO
    $mesas_domingos          = $data['mesas_domingos'] ?? 0;//@RETORNADO
    $mesas_todos             = $data['mesas_todos'] ?? 0;//@RETORNADO
    $mesas_fijos             = $data['mesas_fijos'] ?? 0;//@RETORNADO
        
    $mesasdias = $dias_lunes_jueves*$mesas_lunes_jueves
    +$dias_viernes_sabados*$mesas_viernes_sabados
    +$dias_domingos*$mesas_domingos
    +$dias_todos*$mesas_todos
    +$dias_fijos*$mesas_fijos;
    
    $total_dolar = $valor_diario_dolar*$mesasdias;//@RETORNADO
    $total_euro  = $valor_diario_euro*$mesasdias;//@RETORNADO
    $total_devengado = $total_dolar+$total_euro;//@RETORNADO
    $total_pagar = $total_devengado;//@RETORNADO
    
    return compact(
      'tipo','fecha_cotizacion',
      'dias_valor','valor_dolar','valor_euro','cotizacion_dolar','cotizacion_euro','valor_diario_dolar','valor_diario_euro',
      'dias_lunes_jueves','mesas_lunes_jueves','dias_viernes_sabados','mesas_viernes_sabados',
      'dias_domingos','mesas_domingos','dias_todos','mesas_todos','dias_fijos','mesas_fijos',
      'total_dolar','total_euro','total_devengado','total_pagar'
    );
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
      
      $id_canon_anterior = null;
      {
        $canon_viejos = DB::table('canon')
        ->whereNull('deleted_at')
        ->where('año_mes',$request->año_mes ?? null)
        ->where('id_casino',$request->id_casino ?? null)
        ->orderBy('created_at','desc')
        ->get();
        
        foreach($canon_viejos as $idx => $cv){
          if($idx == 0){//Saco todos los id_archivos para pasarselos a la version de canon nueva
            $id_canon_anterior = $cv->id_canon;
          }
          $this->borrar_arr(['id_canon' => $cv->id_canon],$created_at,$id_usuario);
        }
      }
      
      $datos = $this->recalcular($request);
      
      DB::table('canon')
      ->insert([
        'año_mes' => $datos['año_mes'] ?? null,
        'id_casino' => $datos['id_casino'] ?? null,
        'estado' => 'Generado',
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
        $datos_cv['id_canon'] = $canon->id_canon;
        $datos_cv['tipo'] = $tipo;
        DB::table('canon_variable')
        ->insert($datos_cv);
      }
      
      foreach(($datos['canon_fijo_mesas'] ?? []) as $tipo => $datos_cfm){
        $datos_cfm['id_canon'] = $canon->id_canon;
        $datos_cfm['tipo'] = $tipo;
        DB::table('canon_fijo_mesas')
        ->insert($datos_cfm);
      }
      
      foreach(($datos['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $datos_cfma){
        $datos_cfma['id_canon'] = $canon->id_canon;
        $datos_cfma['tipo']     = $tipo;
        DB::table('canon_fijo_mesas_adicionales')
        ->insert($datos_cfma);
      }
      
      {
        $archivos_existentes = $id_canon_anterior === null? 
          collect([])
        : DB::table('canon_archivo as ca')
        ->select('ca.descripcion','ca.type','a.*')
        ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
        ->where('id_canon',$id_canon_anterior)
        ->get()
        ->keyBy('id_archivo');
        
        $archivos_enviados = collect($datos['adjuntos'] ?? [])->groupBy('id_archivo');
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
              'id_canon'    => $canon->id_canon,
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
                'id_canon' => $canon->id_canon,
                'descripcion' => ($a['descripcion'] ?? ''),
                'type' => $file->getMimeType() ?? 'application/octet-stream'
              ];
            } 
          }
        }
        
        DB::table('canon_archivo')
        ->insert($archivos_resultantes);
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
    
    $ret['canon_fijo_mesas'] = DB::table('canon_fijo_mesas')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
        
    $ret['canon_fijo_mesas_adicionales'] = DB::table('canon_fijo_mesas_adicionales')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
    
    $ret['adjuntos'] = DB::table('canon_archivo as ca')
    ->select('ca.id_canon','ca.descripcion','a.id_archivo','a.nombre_archivo')
    ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
    ->where('ca.id_canon',$request['id_canon'])
    ->orderBy('id_archivo','asc')
    ->get()
    ->transform(function(&$adj){
      $adj->link = '/Ncanon/archivo?id_canon='.urlencode($adj->id_canon)
      .'&nombre_archivo='.urlencode($adj->nombre_archivo);
      return $adj;
    });
    
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
  
  public function cambiarEstado(Request $request){
    return DB::transaction(function() use ($request){
      $updateado = DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$request->id_canon)
      ->update(['estado' => $request->estado]) == 1;
      
      $estado = 200;
      $ret = ['id_canon' => $request->id_canon,'estado' => $request->estado,'mensaje' => ''];
      if($updateado != 1){
        $estado = 422;
        $ret['mensaje'] = 'Error, canon no encontrado';
      }
      return $ret;
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
