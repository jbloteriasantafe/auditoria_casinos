<!DOCTYPE html>
<?php
  $valor_vacio = '-';
  $FD = \App\Http\Controllers\CanonController::class;
  $formatear_decimal = function($attr) use ($valor_vacio,$FD){
    return $attr === null? $valor_vacio : $FD::formatear_decimal($attr);
  };
  $N = function($attr){
    return (($attr[0] ?? null) == '-' && strlen($attr) > 1)? 'negativo' : '';
  };
  $formatear_porcentaje = function($attr) use ($formatear_decimal,$valor_vacio){
    return $attr === null? $valor_vacio : ($formatear_decimal($attr).'%');
  };
  
  $abbr_año = isset($año)? str_pad(substr($año,2),2,'0',STR_PAD_LEFT) : null;
  
  $abbr_mes = function($num_mes) use ($meses){
    return strtoupper(substr($meses[$num_mes] ?? '',0,3));
  };
  $abbr_meses = $meses_calendario->map(function($_,$_nmes) use ($abbr_mes){return $abbr_mes($_nmes) ?? '';})
  ->filter(function($_abbr){return strlen($_abbr);});
  
  $abbr_num_meses = $meses_calendario->map(function($_,$_nmes){return str_pad($_nmes,2,'0',STR_PAD_LEFT);});
  
  $dataf = function($c,$a,$m) use ($data){
    return (($data[$c] ?? [])[$a] ?? [])[$m] ?? (new \stdClass());
  };
    
  $data_arr = compact(
    'data','data_plataformas','años','año','año_anterior','meses',
    'mes','meses_calendario','num_mes','planillas','planilla',
    'es_anual','casinos','abbr_casinos',
    'plataformas','relacion_plat_cas','valor_vacio','formatear_decimal','N',
    'formatear_porcentaje','abbr_año','meses_calendario',
    'abbr_mes','abbr_meses','abbr_num_meses','dataf','parametros',
    'fecha_inicio',
    'primer_fecha','ultima_fecha',
    'primer_año','ultimo_año',
    'primer_mes','ultimo_mes'
  );
?>
<html>

<head>
  <meta charset="utf-8">
  <title></title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/css/Canon/estiloPlanilla.css">
</head>
<body>
  <style>
    .link_planilla {
      all: unset;
      padding: 0.1em;
      background: black;
      color: white;
      border: 1px solid grey;
      cursor: pointer;
    }
    .link_planilla:hover {
      background: darkgrey;
    }
    .link_planillla_usada {
      padding: 0.1em;
      background: white;
      color: black;
      border: 1px solid grey;
      cursor: pointer;
    }
  </style>
  <div style="padding: 0.5em;border-bottom: 4px groove black;background: lightcyan;">
    <?php
      $url = request()->url();
    ?>
    @foreach($botones as $param => $param_vals)
    <?php
      $url.= ($loop->first? '?' : '&').urlencode($param).'=';
    ?>
    
    <div style="padding: 0.5em;">
      @foreach($param_vals as $pk_pv)
      
      @if(($parametros[$param] ?? null) == $pk_pv[0])
      <span class="link_planillla_usada">{{$pk_pv[1] ?? ''}}</span>
      @else
      <a class="link_planilla" href="{{$url.urlencode($pk_pv[0] ?? '')}}">{{$pk_pv[1] ?? ''}}</a>
      @endif
            
      @endforeach
      
      @if($loop->first)
      <button data-js-click-descargar-tabla="[data-target-descargar-tabla]" style="float: right;">Descargar tabla</button>
      @endif
    </div>
    <?php
      $url.= urlencode($parametros[$param] ?? '');
    ?>
    @endforeach
  </div>
  <div data-target-descargar-tabla>
    <style>
      tr th {
        border-right: 1px solid black;
      }
      tr td {
        border-right: 1px solid black;
      }
    </style>
    @if($botones_elegidos)
    @if($planilla == 'evolucion_historica')
      @component('Canon.Planillas.evolucion_historica',$data_arr)
      @endcomponent
    @endif
    @if($planilla == 'actualizacion_valores')
      @component('Canon.Planillas.actualizacion_valores',$data_arr)
      @endcomponent
    @endif
    @if($planilla == 'evolucion_cotizacion')
      @component('Canon.Planillas.evolucion_cotizacion',$data_arr)
      @endcomponent
    @endif
    @if($planilla == 'canon_total')
      @component('Canon.Planillas.canon_total',$data_arr)
      @endcomponent
    @endif
    @if($planilla == 'canon_fisico_online')
      @component('Canon.Planillas.canon_fisico_online',$data_arr)
      @endcomponent
    @endif
    @if($planilla == 'participacion')
      @component('Canon.Planillas.participacion',$data_arr)
      @endcomponent
    @endif
    @endif
  </div>
</body>

<script>
  const data = {!! (isset($data) && count($data) > 0)? json_encode($data) : '{}' !!};
  const parametros = {!! (isset($parametros) && count($parametros) > 0)? json_encode($parametros) : '{}' !!};
  const casinos = {!! (isset($casinos) && count($casinos) > 0)? json_encode($casinos) : '[]' !!};
  const meses = {!! (isset($meses_calendario) && count($meses_calendario) > 0)? json_encode($meses_calendario) : '[]' !!};
  const colors = [
    @foreach($abbr_casinos as $_cas)
    @continue($_cas == 'TOTAL')
    window.getComputedStyle(document.body).getPropertyValue('--color-{!! $_cas !!}'),
    @endforeach
  ];
  
  const planilla = "{!! isset($planilla)? $planilla : '' !!}";
  const año = "{!! isset($año)? $año : '' !!}";
  const mes = "{!! isset($mes)? $mes : '' !!}";
  const fecha_planilla = "{!! date('Ymdhi') !!}";
  const nombre_archivo = "{!! implode('_',$parametros) !!}";
</script>
<script src="/js/Canon/planillas.js?1" charset="utf-8" type="module"></script>

</html>
