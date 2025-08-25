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
  
  $dias_mes = ($num_mes !== null && $num_mes !== 0 && $año !== null)? 
    cal_days_in_month(CAL_GREGORIAN,$num_mes,$año) 
  : 0;
  
  
  $dataf = function($c,$a,$m) use ($data){
    return (($data[$c] ?? [])[$a] ?? [])[$m] ?? (new \stdClass());
  };
    
  $data_arr = compact(
    'data','data_plataformas','años_planilla','años','año','año_anterior','meses',
    'meses_elegibles','mes','meses_calendario','num_mes','planillas','planilla',
    'es_anual','es_mensual','casinos','abbr_casinos',
    'plataformas','relacion_plat_cas','valor_vacio','formatear_decimal','N',
    'formatear_porcentaje','abbr_año','meses_calendario',
    'abbr_mes','abbr_meses','abbr_num_meses','dias_mes','dataf','parametros',
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
    @if($planilla == 'evolucion_historica')
      @component('Canon.Planillas.evolucion_historica',$data_arr)
      @endcomponent
    @endif
    @if($planilla == 'actualizacion_valores')
      @component('Canon.Planillas.actualizacion_valores',$data_arr)
      @endcomponent
    @endif
    @if($es_anual && isset($año))
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
    @if($es_mensual && isset($año) && isset($mes))
    
    @if($planilla == 'mtm')
    @if($mes == 'Resumen')
    <table style="width: 100%;table-layout: fixed">
      <colgroup>
        <col class="mes">
        @foreach($casinos as $_casino)
        <col class="bruto">
        <col class="bruto_usd">
        <col class="bruto_convertido">
        <col class="bruto_total">
        <col class="canon_total">
        <col class="variacion_anual">
        @endforeach          
      </colgroup>
      <thead>
        <tr>
          <th class="mes" style="border-right: 1px solid black;" rowspan="2">MESES</th>
          @foreach($abbr_casinos as $_cas)
          <th class="{{$_cas}}" colspan="6">{{$_cas}}</th>
          @endforeach
        </tr>
        <tr>
          @foreach($abbr_casinos as $_cas)
          <th class="bruto {{$_cas}}">R.B. ($)</th>
          <th class="bruto_usd {{$_cas}}">R.B. (U$s)</th>
          <th class="bruto_convertido {{$_cas}}">R.B. Convertido</th>
          <th class="bruto_total {{$_cas}}">R.B. TOTAL</th>
          <th class="canon_total {{$_cas}}">CANON</th>
          <th class="variacion_anual {{$_cas}}">Var. %</th>
          @endforeach
        </tr>
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año_anterior}}</th>
          @foreach($abbr_casinos as $_cidx => $_cas)
          <?php
            $total = $casinos[$_cidx] ?? null;
            $total = $total !== null? ($data_anual[$total] ?? null) : null;
            $total = $total !== null? ($total[$año_anterior] ?? null) : null;
            $bruto = $formatear_decimal($total? $total->bruto : null);
            $bruto_usd = $formatear_decimal($total? $total->bruto_usd : null);
            $bruto_convertido = $formatear_decimal($total? $total->bruto_convertido : null);
            $bruto_total = $formatear_decimal($total? $total->bruto_total : null);
            $canon_total = $formatear_decimal($total? $total->canon_total : null);
            $variacion_anual = $formatear_porcentaje($total? $total->variacion_anual : null);
          ?>
          <th class="bruto" style="text-align: right;">{{$bruto}}</th>
          <th class="bruto_usd" style="text-align: right;">{{$bruto_usd}}</th>
          <th class="bruto_convertido" style="text-align: right;">{{$bruto_convertido}}</th>
          <th class="bruto_total" style="text-align: right;">{{$bruto_total}}</th>
          <th class="canon_total" style="text-align: right;">{{$canon_total}}</th>
          <th class="variacion_anual" style="text-align: right;">{{$valor_vacio}}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($abbr_meses as $_nmes => $_mes)
        <tr>
          <th class="mes" style="border-right: 1px solid black">{{$_mes}}</th>
          @foreach($abbr_casinos as $_cidx => $_cas)
          <?php
            $_casino = $casinos[$_cidx];
            $canon = $data[$_casino] ?? null; 
            if($canon !== null){
              $canon = $canon[$_nmes] ?? null;
            }
            $bruto = $formatear_decimal($canon? $canon->bruto : null);
            $bruto_usd = $formatear_decimal($canon? $canon->bruto_usd : null);
            $bruto_convertido = $formatear_decimal($canon? $canon->bruto_convertido : null);
            $bruto_total = $formatear_decimal($canon? $canon->bruto_total : null);
            $canon_total = $formatear_decimal($canon? $canon->canon_total : null);
            $variacion_anual = $formatear_porcentaje($canon? $canon->variacion_anual : null);
          ?>
          <td class="bruto">{{$bruto}}</td>
          <td class="bruto_usd">{{$bruto_usd}}</td>
          <td class="bruto_convertido">{{$bruto_convertido}}</td>
          <td class="bruto_total">{{$bruto_total}}</td>
          <td class="canon_total">{{$canon_total}}</td>
          <td class="variacion_anual">{{$variacion_anual}}</td>
          @endforeach
        </tr>
        @endforeach
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año}}</th>
          @foreach($abbr_casinos as $_cidx => $_cas)
          <?php
            $total = $casinos[$_cidx] ?? null;
            $total = $total !== null? ($data_anual[$total] ?? null) : null;
            $total = $total !== null? ($total[$año] ?? null) : null;
            $bruto = $formatear_decimal($total? $total->bruto : null);
            $bruto_usd = $formatear_decimal($total? $total->bruto_usd : null);
            $bruto_convertido = $formatear_decimal($total? $total->bruto_convertido : null);
            $bruto_total = $formatear_decimal($total? $total->bruto_total : null);
            $canon_total = $formatear_decimal($total? $total->canon_total : null);
            $variacion_anual = $formatear_porcentaje($total? $total->variacion_anual : null);
          ?>
          <th class="bruto" style="text-align: right;">{{$bruto}}</th>
          <th class="bruto_usd" style="text-align: right;">{{$bruto_usd}}</th>
          <th class="bruto_convertido" style="text-align: right;">{{$bruto_convertido}}</th>
          <th class="bruto_total" style="text-align: right;">{{$bruto_total}}</th>
          <th class="canon_total" style="text-align: right;">{{$canon_total}}</th>
          <th class="variacion_anual" style="text-align: right;">{{$variacion_anual}}</th>
          @endforeach
        </tr>
      </tbody>
    </table>
    <div>
      GRAFICO EVOLUCION CANON POR MES POR CASINO
    </div>
    @endif
    @if($mes !== 'Resumen')
    <table style="width: 100%;table-layout: fixed">
      <colgroup>
        <col class="dia">
        @foreach($casinos as $_casino)
        <col class="cantidad_maquinas">
        <col class="bruto">
        <col class="cantidad_maquinas_usd">
        <col class="bruto_usd">
        <col class="cotizacion">
        <col class="bruto_convertido">
        <col class="bruto_total">
        <col class="canon">
        <col class="variacion">
        @endforeach          
      </colgroup>
      <thead>
        <tr>
          <th class="dia" style="border-right: 1px solid black;width: 4%;" rowspan="2">DÍA</th>
          @foreach($abbr_casinos as $_cas)
          <th class="{{$_cas}}" colspan="8" style="width: {{96/count($casinos)}}%";>{{$_cas}}</th>
          @endforeach
        </tr>
        <tr>
          @foreach($abbr_casinos as $_cas)
          <th class="cantidad_maquinas {{$_cas}}">CANT. MAQ. $</th>
          <th class="bruto {{$_cas}}">R.B. ($)</th>
          <th class="cantidad_maquinas {{$_cas}}">CANT. MAQ. U$s</th>
          <th class="bruto_usd {{$_cas}}">R.B. (U$s)</th>
          <th class="cotizacion {{$_cas}}">TC</th>
          <th class="bruto_convertido {{$_cas}}">R.B. Convertido</th>
          <th class="bruto_total {{$_cas}}">R.B. TOTAL</th>
          <th class="canon {{$_cas}}">CANON</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @for($d=1;$d<=$dias_mes;$d++)
        <tr>
          <td class="dia" style="border-right: 1px solid black">{{$d}}</td>
          @foreach($casinos as $_casino)
          <td class="cantidad_maquinas">1111</td>
          <td class="bruto">9999</td>
          <td class="cantidad_maquinas">1111</td>
          <td class="bruto_usd">9999</td>
          <td class="cotizacion">12,34</td>
          <td class="bruto_convertido">9999</td>
          <td class="bruto_total">9999</td>
          <td class="canon">9999</td>
          @endforeach
        </tr>
        @endfor
        <tr>
          <th class="dia celda_especial" style="border-right: 1px solid black;">TOTAL</th>
          @foreach($casinos as $_casino)
          <th class="cantidad_maquinas" style="text-align: right;">1111</th>
          <th class="bruto" style="text-align: right;">9999</th>
          <th class="cantidad_maquinas">1111</th>
          <th class="bruto_usd" style="text-align: right;">9999</th>
          <th class="cotizacion"  style="text-align: right;">12,34</th>
          <th class="bruto_convertido" style="text-align: right;">9999</th>
          <th class="bruto_total" style="text-align: right;">9999</th>
          <th class="canon" style="text-align: right;">9999</th>
          @endforeach
        </tr>
      </tbody>
    </table>
    @endif
    @endif
    
    @if($planilla == 'mesas')
    @if($mes == 'Resumen')
    <table style="width: 100%;table-layout: fixed">
      <colgroup>
        <col class="mes">
        @foreach($casinos as $_casino)
        <col class="utilidad">
        <col class="utilidad_usd">
        <col class="utilidad_total">
        <col class="canon_total">
        <col class="variacion_anual">
        @endforeach          
      </colgroup>
      <thead>
        <tr>
          <th class="mes" style="border-right: 1px solid black;" rowspan="2">MESES</th>
          @foreach($abbr_casinos as $_cas)
          <th class="{{$_cas}}" colspan="5">{{$_cas}}</th>
          @endforeach
        </tr>
        <tr>
          @foreach($abbr_casinos as $_cas)
          <th class="utilidad {{$_cas}}">Utilidad ($)</th>
          <th class="utilidad_usd {{$_cas}}">Utilidad (U$s)</th>
          <th class="utilidad_total {{$_cas}}">Utilidad (Total)</th>
          <th class="canon_total {{$_cas}}">Canon</th>
          <th class="variacion_anual {{$_cas}}">Var. %</th>
          @endforeach
        </tr>
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año_anterior}}</th>
          @foreach($abbr_casinos as $_cas)
          <th class="utilidad" style="text-align: right;">9999</th>
          <th class="utilidad_usd" style="text-align: right;">9999</th>
          <th class="utilidad_total" style="text-align: right;">9999</th>
          <th class="canon_total" style="text-align: right;">9999</th>
          <th class="variacion_anual" style="text-align: right;">12,34%</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($abbr_meses as $_mes)
        <tr>
          <th class="mes" style="border-right: 1px solid black">{{$_mes}}</th>
          @foreach($abbr_casinos as $_cas)
          <td class="utilidad">9999</td>
          <td class="utilidad_usd">9999</td>
          <td class="utilidad_total">9999</td>
          <td class="canon_total">9999</td>
          <td class="variacion_anual">12,34%</td>
          @endforeach
        </tr>
        @endforeach
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año}}</th>
          @foreach($abbr_casinos as $_cas)
          <th class="utilidad" style="text-align: right;">9999</th>
          <th class="utilidad_usd" style="text-align: right;">9999</th>
          <th class="utilidad_total" style="text-align: right;">9999</th>
          <th class="canon_total" style="text-align: right;">9999</th>
          <th class="variacion_anual" style="text-align: right;">12,34%</th>
          @endforeach
        </tr>
      </tbody>
    </table>
    <div>
      GRAFICO EVOLUCION CANON POR MES POR CASINO
    </div>
    @endif
    @if($mes !== 'Resumen')
    <table style="width: 100%;table-layout: fixed">
      <colgroup>
        <col class="dia">
        @foreach($casinos as $_casino)
        <col class="cantidad_mesas_mh">
        <col class="cantidad_mesas_mu">
        <col class="utilidad">
        <col class="utilidad_usd">
        <col class="cotizacion">
        <col class="utilidad_cotizada">
        <col class="utilidad_total">
        @endforeach            
      </colgroup>
      <thead>
        <tr>
          <th class="dia" style="border-right: 1px solid black;width: 4%;" rowspan="3">DÍA</th>
          @foreach($abbr_casinos as $_cas)
          <th class="{{$_cas}}" colspan="7" style="width: {{96/count($casinos)}}%";>{{$_cas}}</th>
          @endforeach
        </tr>
        <tr>
          @foreach($abbr_casinos as $_cas)
          <th class="cantidad_mesas {{$_cas}}" colspan="2">Cant. Mesas</th>
          <th class="utilidad {{$_cas}}" rowspan="2">Utilidad ($)</th>
          <th class="utilidad_usd {{$_cas}}" rowspan="2">Utilidad (U$s)</th>
          <th class="cotizacion {{$_cas}}" rowspan="2">TC</th>
          <th class="utilidad_cotizada {{$_cas}}" rowspan="2">Utilidad (Convertida)</th>
          <th class="utilidad_total {{$_cas}}" rowspan="2">Utilidad (Total)</th>
          @endforeach
        </tr>
        <tr>
          @foreach($abbr_casinos as $_cas)
          <th class="cantidad_mesas_mh {{$_cas}}">MH</th>
          <th class="cantidad_mesas_mu {{$_cas}}">MU</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @for($d=1;$d<=$dias_mes;$d++)
        <tr>
          <td class="dia" style="border-right: 1px solid black">{{$d}}</td>
          @foreach($casinos as $_casino)
          <td class="cantidad_mesas_mh">1111</td>
          <td class="cantidad_mesas_mu">1111</td>
          <td class="utilidad">9999</td>
          <td class="utilidad_usd">9999</td>
          <td class="cotizacion">12,34</td>
          <td class="utilidad_cotizada">9999</td>
          <td class="utilidad_total">9999</td>
          @endforeach
        </tr>
        @endfor
        <tr>
          <th class="dia celda_especial" style="border-right: 1px solid black;">TOTAL</th>
          @foreach($casinos as $_casino)
          <th class="cantidad_mesas_mh" style="text-align: right;">1111</th>
          <th class="cantidad_mesas_mu" style="text-align: right;">9999</th>
          <th class="utilidad" style="text-align: right;">1111</th>
          <th class="utilidad_usd" style="text-align: right;">9999</th>
          <th class="cotizacion" style="text-align: right;">12,34</th>
          <th class="utilidad_cotizada" style="text-align: right;">9999</th>
          <th class="utilidad_total" style="text-align: right;">9999</th>
          @endforeach
        </tr>
      </tbody>
    </table>
    @endif
    @endif
    
    @if($planilla == 'bingos')
    @if($mes == 'Resumen')
    <table style="width: 100%;table-layout: fixed">
      <colgroup>
        <col class="mes">
        @foreach($casinos as $_casino)
        <col class="bruto_total">
        <col class="canon_total">
        <col class="variacion_anual">
        @endforeach          
      </colgroup>
      <thead>
        <tr>
          <th class="mes" style="border-right: 1px solid black;" rowspan="2">MESES</th>
          @foreach($abbr_casinos as $_cas)
          <th class="{{$_cas}}" colspan="3">{{$_cas}}</th>
          @endforeach
        </tr>
        <tr>
          @foreach($abbr_casinos as $_cas)
          <th class="bruto {{$_cas}}">R.B. ($)</th>
          <th class="canon {{$_cas}}">CANON</th>
          <th class="variacion {{$_cas}}">Var. %</th>
          @endforeach
        </tr>
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año_anterior}}</th>
          @foreach($abbr_casinos as $_cas)
          <th class="bruto" style="text-align: right;">9999</th>
          <th class="canon" style="text-align: right;">9999</th>
          <th class="variacion" style="text-align: right;">12,34%</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($abbr_meses as $_mes)
        <tr>
          <th class="mes" style="border-right: 1px solid black">{{$_mes}}</th>
          @foreach($abbr_casinos as $_cas)
          <td class="bruto">9999</td>
          <td class="canon">9999</td>
          <td class="variacion">12,34%</td>
          @endforeach
        </tr>
        @endforeach
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año}}</th>
          @foreach($abbr_casinos as $_cas)
          <th class="bruto" style="text-align: right;">9999</th>
          <th class="canon" style="text-align: right;">9999</th>
          <th class="variacion" style="text-align: right;">12,34%</th>
          @endforeach
        </tr>
      </tbody>
    </table>
    <div>
      GRAFICO EVOLUCION CANON POR MES POR CASINO
    </div>
    @endif
    @if($mes !== 'Resumen')
    <table style="width: 100%;table-layout: fixed">
      <colgroup>
        <col class="dia">
        @foreach($casinos as $_casino)
        <col class="bruto">
        <col class="canon">
        @endforeach          
      </colgroup>
      <thead>
        <tr>
          <th class="dia" style="border-right: 1px solid black;width: 4%;" rowspan="2">DÍA</th>
          @foreach($abbr_casinos as $_cas)
          <th class="{{$_cas}}" colspan="2" style="width: {{96/count($casinos)}}%";>{{$_cas}}</th>
          @endforeach
        </tr>
        <tr>
          @foreach($abbr_casinos as $_cas)
          <th class="bruto {{$_cas}}">R.B. ($)</th>
          <th class="canon {{$_cas}}">CANON</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @for($d=1;$d<=$dias_mes;$d++)
        <tr>
          <td class="dia" style="border-right: 1px solid black">{{$d}}</td>
          @foreach($casinos as $_casino)
          <td class="bruto">9999</td>
          <td class="canon">9999</td>
          @endforeach
        </tr>
        @endfor
        <tr>
          <th class="dia celda_especial" style="border-right: 1px solid black;">TOTAL</th>
          @foreach($casinos as $_casino)
          <th class="bruto" style="text-align: right;">9999</th>
          <th class="canon" style="text-align: right;">9999</th>
          @endforeach
        </tr>
      </tbody>
    </table>
    @endif
    @endif
    
    @if($planilla == 'jol')
    @if($mes == 'Resumen')
    <table style="width: 100%;table-layout: fixed">
      <colgroup>
        <col class="mes">
        @foreach($plataformas as $_plat)
        <col class="usuarios_online">
        <col class="resultado_online">
        <col class="canon_online">
        <col class="usuarios_poker">
        <col class="utilidad_poker">
        <col class="canon_poker">
        <col class="resultado">
        <col class="canon_total">
        <col class="variacion_anual">
        @endforeach          
      </colgroup>
      <thead>
        <tr>
          <th class="mes" style="border-right: 1px solid black;width: 4%;" rowspan="3">MESES</th>
          @foreach($plataformas as $_plat)
          <?php $_cas = $abbr_casinos[$casinos->search($relacion_plat_cas[$_plat->codigo])]; ?>
          <th class="plataforma {{$_cas}}" colspan="9">{{$_cas}}</th>
          @endforeach
          <th class="plataforma TOTAL" colspan="3" rowspan="2">TOTAL</th>
        </tr>
        <tr>
          @foreach($plataformas as $_plat)
          <?php $_cas = $abbr_casinos[$casinos->search($relacion_plat_cas[$_plat->codigo])]; ?>
          <th class="plataforma {{$_cas}}" colspan="3">{{$_plat->nombre}}</th>
          <th class="plataforma {{$_cas}}" colspan="3">Poker</th>
          <th class="plataforma {{$_cas}}" colspan="3">Total</th>
          @endforeach
        </tr>
        <tr>
          @foreach($plataformas as $_plat)
          <th class="usuarios_online">Usuarios</th>
          <th class="resultado_online">Resultado</th>
          <th class="canon_online">Canon</th>
          <th class="usuarios_poker">Usuarios</th>
          <th class="utilidad_poker">Utilidad</th>
          <th class="canon_poker">Canon</th>
          <th class="resultado">Resultado</th>
          <th class="canon_total">Canon</th>
          <th class="variacion_anual">Var. %</th>
          @endforeach
          <th class="utilidad_poker">Utilidad</th>
          <th class="canon_total">Canon</th>
          <th class="variacion_anual">Var. %</th>
        </tr>
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año_anterior}}</th>
          @foreach($plataformas as $_pidx => $_plat)
          <?php
            $_cas = $relacion_plat_cas[$_plat->codigo] ?? $_plat->codigo;
            $total = $data_anual[$_cas] ?? null;
            $total = $total !== null? ($total[$año_anterior] ?? null) : null;
            $usuarios_online = $formatear_decimal($total? $total->usuarios_online : null);
            $resultado_online = $formatear_decimal($total? $total->resultado_online : null);
            $canon_online = $formatear_decimal($total? $total->canon_online : null);
            $usuarios_poker = $formatear_decimal($total? $total->usuarios_poker : null);
            $utilidad_poker = $formatear_decimal($total? $total->utilidad_poker : null);
            $canon_poker = $formatear_decimal($total? $total->canon_poker : null);
            $resultado = $formatear_decimal($total? $total->resultado : null);
            $canon_total = $formatear_decimal($total? $total->canon_total : null);
            $variacion_anual = $formatear_porcentaje($total? $total->variacion_anual : null);
          ?>
          <th class="usuarios_online" style="text-align: right;">{{$usuarios_online}}</th>
          <th class="resultado_online" style="text-align: right;">{{$resultado_online}}</th>
          <th class="canon_online" style="text-align: right;">{{$canon_online}}</th>
          <th class="usuarios_poker" style="text-align: right;">{{$usuarios_poker}}</th>
          <th class="utilidad_poker" style="text-align: right;">{{$utilidad_poker}}</th>
          <th class="canon_poker" style="text-align: right;">{{$canon_poker}}</th>
          <th class="resultado" style="text-align: right;">{{$resultado}}</th>
          <th class="canon_total" style="text-align: right;">{{$canon_total}}</th>
          <th class="variacion_anual" style="text-align: right;">{{$variacion_anual}}</th>
          @endforeach
          <?php
            $total = $data_anual['Total'] ?? null;
            $total = $total !== null? ($total[$año_anterior] ?? null) : null;
            $utilidad_poker = $formatear_decimal($total? $total->utilidad_poker : null);
            $canon_total = $formatear_decimal($total? $total->canon_total : null);
            $variacion_anual = $formatear_porcentaje($total? $total->variacion_anual : null);
          ?>
          <th class="utilidad_poker" style="text-align: right;">{{$utilidad_poker}}</th>
          <th class="canon_total" style="text-align: right;">{{$canon_total}}</th>
          <th class="variacion_anual" style="text-align: right;">{{$variacion_anual}}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($abbr_meses as $_nmes => $_mes)
        <tr>
          <th class="mes" style="border-right: 1px solid black">{{$_mes}}</th>
          @foreach($plataformas as $_plat)
          <?php
            $_cas = $relacion_plat_cas[$_plat->codigo] ?? $_plat->codigo;
            $canon = $data[$_cas] ?? null;
            $canon = $canon !== null? ($canon[$_nmes] ?? null) : null;
            $usuarios_online = $formatear_decimal($canon? $canon->usuarios_online : null);
            $resultado_online = $formatear_decimal($canon? $canon->resultado_online : null);
            $canon_online = $formatear_decimal($canon? $canon->canon_online : null);
            $usuarios_poker = $formatear_decimal($canon? $canon->usuarios_poker : null);
            $utilidad_poker = $formatear_decimal($canon? $canon->utilidad_poker : null);
            $canon_poker = $formatear_decimal($canon? $canon->canon_poker : null);
            $resultado = $formatear_decimal($canon? $canon->resultado : null);
            $canon_total = $formatear_decimal($canon? $canon->canon_total : null);
            $variacion_anual = $formatear_porcentaje($canon? $canon->variacion_anual : null);
          ?>
          <td class="usuarios_online">{{$usuarios_online}}</td>
          <td class="resultado_online">{{$resultado_online}}</td>
          <td class="canon_online">{{$canon_online}}</td>
          <td class="usuarios_poker">{{$usuarios_poker}}</td>
          <td class="utilidad_poker">{{$utilidad_poker}}</td>
          <td class="canon_poker">{{$canon_poker}}</td>
          <td class="resultado">{{$resultado}}</td>
          <td class="canon_total">{{$canon_total}}</td>
          <td class="variacion_anual">{{$variacion_anual}}</td>
          @endforeach
          <?php
            $canon = $data['Total'] ?? null;
            $canon = $canon !== null? ($canon[$_nmes] ?? null) : null;
            $utilidad_poker = $formatear_decimal($canon? $canon->utilidad_poker : null);
            $canon_total = $formatear_decimal($canon? $canon->canon_total : null);
            $variacion_anual = $formatear_porcentaje($canon? $canon->variacion_anual : null);
          ?>
          <th class="utilidad_poker" style="text-align: right;">{{$utilidad_poker}}</th>
          <th class="canon_total" style="text-align: right;">{{$canon_total}}</th>
          <th class="variacion_anual" style="text-align: right;">{{$variacion_anual}}</th>
        </tr>
        @endforeach
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año}}</th>
          @foreach($plataformas as $_pidx => $_plat)
          <?php
            $_cas = $relacion_plat_cas[$_plat->codigo] ?? $_plat->codigo;
            $total = $data_anual[$_cas] ?? null;
            $total = $total !== null? ($total[$año] ?? null) : null;
            $usuarios_online = $formatear_decimal($total? $total->usuarios_online : null);
            $resultado_online = $formatear_decimal($total? $total->resultado_online : null);
            $canon_online = $formatear_decimal($total? $total->canon_online : null);
            $usuarios_poker = $formatear_decimal($total? $total->usuarios_poker : null);
            $utilidad_poker = $formatear_decimal($total? $total->utilidad_poker : null);
            $canon_poker = $formatear_decimal($total? $total->canon_poker : null);
            $resultado = $formatear_decimal($total? $total->resultado : null);
            $canon_total = $formatear_decimal($total? $total->canon_total : null);
            $variacion_anual = $formatear_porcentaje($total? $total->variacion_anual : null);
          ?>
          <th class="usuarios_online" style="text-align: right;">{{$usuarios_online}}</th>
          <th class="resultado_online" style="text-align: right;">{{$resultado_online}}</th>
          <th class="canon_online" style="text-align: right;">{{$canon_online}}</th>
          <th class="usuarios_poker" style="text-align: right;">{{$usuarios_poker}}</th>
          <th class="utilidad_poker" style="text-align: right;">{{$utilidad_poker}}</th>
          <th class="canon_poker" style="text-align: right;">{{$canon_poker}}</th>
          <th class="resultado" style="text-align: right;">{{$resultado}}</th>
          <th class="canon_total" style="text-align: right;">{{$canon_total}}</th>
          <th class="variacion_anual" style="text-align: right;">{{$variacion_anual}}</th>
          @endforeach
          <?php
            $total = $data_anual['Total'] ?? null;
            $total = $total !== null? ($total[$año] ?? null) : null;
            $utilidad_poker = $formatear_decimal($total? $total->utilidad_poker : null);
            $canon_total = $formatear_decimal($total? $total->canon_total : null);
            $variacion_anual = $formatear_porcentaje($total? $total->variacion_anual : null);
          ?>
          <th class="utilidad_poker" style="text-align: right;">{{$utilidad_poker}}</th>
          <th class="canon_total" style="text-align: right;">{{$canon_total}}</th>
          <th class="variacion_anual" style="text-align: right;">{{$variacion_anual}}</th>
        </tr>
      </tbody>
    </table>
    <div>
      GRAFICO EVOLUCION CANON POR MES POR CASINO
    </div>
    @endif
    @if($mes !== 'Resumen')
    <table style="width: 100%;table-layout: fixed">
      <colgroup>
        <col class="dia">
        @foreach($plataformas as $_plat)
        <col class="usuarios_online">
        <col class="resultado_online">
        <col class="canon_online">
        <col class="usuarios_poker">
        <col class="utilidad_poker">
        <col class="canon_poker">
        <col class="resultado">
        <col class="canon">
        <col class="variacion">
        @endforeach          
      </colgroup>
      <thead>
        <tr>
          <th class="mes" style="border-right: 1px solid black;width: 4%;" rowspan="3">DÍA</th>
          @foreach($plataformas as $_plat)
          <?php $_cas = $abbr_casinos[$casinos->search($relacion_plat_cas[$_plat->codigo])]; ?>
          <th class="plataforma {{$_cas}}" colspan="9">{{$_cas}}</th>
          @endforeach
          <th class="plataforma TOTAL" colspan="3" rowspan="2">TOTAL</th>
        </tr>
        <tr>
          @foreach($plataformas as $_plat)
          <?php $_cas = $abbr_casinos[$casinos->search($relacion_plat_cas[$_plat->codigo])]; ?>
          <th class="plataforma {{$_cas}}" colspan="3">{{$_plat->nombre}}</th>
          <th class="plataforma {{$_cas}}" colspan="3">Poker</th>
          <th class="plataforma {{$_cas}}" colspan="3">Total</th>
          @endforeach
        </tr>
        <tr>
          @foreach($plataformas as $_plat)
          <th class="usuarios_online">Usuarios</th>
          <th class="resultado_online">Resultado</th>
          <th class="canon_online">Canon</th>
          <th class="usuarios_poker">Usuarios</th>
          <th class="utilidad_poker">Utilidad</th>
          <th class="canon_poker">Canon</th>
          <th class="resultado">Resultado</th>
          <th class="canon">Canon</th>
          <th class="variacion">Var. %</th>
          @endforeach
          <th class="utilidad_poker">Utilidad</th>
          <th class="canon">Canon</th>
          <th class="canon_poker">Var. %</th>
        </tr>
      </thead>
      <tbody>
        @for($d=1;$d<=$dias_mes;$d++)
        <tr>
          <th class="mes" style="border-right: 1px solid black">{{$d}}</th>
          @foreach($plataformas as $_plat)
          <td class="usuarios_online">1111</td>
          <td class="resultado_online">9999</td>
          <td class="canon_online">9999</td>
          <td class="usuarios_poker">1111</td>
          <td class="utilidad_poker">9999</td>
          <td class="canon_poker">9999</td>
          <td class="resultado">9999</td>
          <td class="canon">9999</td>
          <td class="variacion">12,34%</td>
          @endforeach
          <td class="usuarios_poker">1111</td>
          <td class="canon">9999</td>
          <td class="variacion">12,34%</td>
        </tr>
        @endfor
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">TOTAL</th>
          @foreach($plataformas as $_plat)
          <th class="usuarios_online" style="text-align: right;">1111</th>
          <th class="resultado_online" style="text-align: right;">9999</th>
          <th class="canon_online" style="text-align: right;">9999</th>
          <th class="usuarios_poker" style="text-align: right;">1111</th>
          <th class="utilidad_poker" style="text-align: right;">9999</th>
          <th class="canon_poker" style="text-align: right;">9999</th>
          <th class="resultado" style="text-align: right;">9999</th>
          <th class="canon" style="text-align: right;">9999</th>
          <th class="variacion" style="text-align: right;">12,34%</th>
          @endforeach
          <th class="utilidad_poker" style="text-align: right;">9999</th>
          <th class="canon" style="text-align: right;">9999</th>
          <th class="variacion" style="text-align: right;">12,34%</th>
        </tr>
      </tbody>
    </table>
    @endif
    @endif
    @endif
  </div>
</body>

<script>
  const data = {!! (isset($data) && count($data) > 0)? json_encode($data) : '{}' !!};
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
</script>
<script src="/js/Canon/planillas.js" charset="utf-8" type="module"></script>

</html>
