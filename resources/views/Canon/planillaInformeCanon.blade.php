<!DOCTYPE html>
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
    &nbsp;
    <button data-js-click-descargar-tabla="[data-target-descargar-tabla]" style="float: right;">Descargar tabla</button>
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
    <div style="width: 100%;font-size: 1.5em;">
      <style>
          tr > td.bleft, tr > th.bleft {
            border-left: 1px solid black !important;
          }
          tr > td.bright, tr > th.bright {
            border-right: 1px solid black !important;
          }
          tr > td.btop, tr > th.btop {
            border-top: 1px solid black !important;
          }
          tr > td.bbottom, tr > th.bbottom {
            border-bottom: 1px solid black !important;
          }
          tr > td.b, tr > th.b {
            border: 1px solid black !important;
          }
          tr > td.nob, tr > th.nob {
            border: 0 !important;
          }
          tr > td.celda_vacia,
          tr > th.celda_vacia {
            background: lightgrey;
          }
          html, body {
            background: lightgrey;
          }
          tr, th, td {
            background: white;
          }
      </style>
      <table style="width: 100%;table-layout: fixed">     
        <?php 
          $colspan = ['width: 5em;','','','','']; $C = count($colspan); 
          $DEC = function($val){
            return $val === null? '-' : \App\Http\Controllers\CanonController::formatear_decimal($val);
          };
          $PJE = function($val){
            return $val === null? '-' : \App\Http\Controllers\CanonController::formatear_decimal($val).'%';
          };
        ?>
        <colgroup>
          @foreach($colspan as $colstyle)
          <col style="{{$colstyle}}">
          @endforeach
        </colgroup>
        <thead>
          <tr>
            <th class="{{$abbr_casinos[$casino]}}" style="border: 1px solid black" colspan="{{$C}}">{{$casino}} {{$año}}-{{$mes}}</th>
          </tr>
        </thead>
        @section('separator')
        <tr>
          <td class="celda_vacia nob" colspan="{{$C}}" style="font-size: 0.8em;">&nbsp;</td>
        </tr>
        @endsection
                
        @yield('separator')
        <thead>
          <tr>
            <th style="text-align: left;" colspan="{{$C}}">Canon Variable</th>
          </tr>
        </thead>
        <thead>
          <tr>
            <th class="celda_vacia nob">&nbsp;</th>
            <th class="bleft">Detalle</th>
            <th>Beneficio</th>
            <th>Alícuota</th>
            <th>Determinado</th>
          </tr>
        </thead>
        <tbody>
          @foreach($data['Variable'] as $tvar => $dvar)
          @if($tvar != 'Total')
          <tr>
            @if($loop->first)
            <td class="celda_vacia nob" rowspan="{{$loop->count}}">&nbsp;</td>
            @endif
            <td class="bleft">{{$tvar}}</td>
            <td>{{$DEC($dvar['beneficio'] ?? null)}}</td>
            <td>{{$PJE($dvar['alicuota'] ?? null)}}</td>
            <td>{{$DEC($dvar['determinado'] ?? null)}}</td>
          </tr>
          @else
          <tr>
            <td class="celda_vacia nob">&nbsp;</th>
            <td class="bleft">{{$DEC($dvar['beneficio'] ?? null)}}</td>
            <td class="celda_vacia nob">&nbsp;</td>
            <td class="bleft">{{$DEC($dvar['determinado'] ?? null)}}</td>
          </tr>
          @endif
          
          @endforeach
        </tbody>
        
        @yield('separator')
        <thead>
          <tr>
            <th style="text-align: left;" colspan="{{$C}}">Canon Fijo</th>
          </tr>
        </thead>
        <thead>
          <tr>
            <th>Moneda</th>
            <th>Valor Mesa</th>
            <th>Tipo de cambio</th>
            <th>Valor Mesa (Pesos)</th>
            <th>Determinado</th>
          </tr>
        </thead>
        <tbody>
          @foreach($data['Fijo']['Monedas'] as $mon => $datamon)
          <tr>
            <td class="{{$loop->last? 'bbottom' : ''}}">{{$mon}}</td>
            <td class="{{$loop->last? 'bbottom' : ''}}">{{$DEC($datamon['valor'] ?? null)}}</td>
            <td class="{{$loop->last? 'bbottom' : ''}}">{{$DEC($datamon['cotizacion'] ?? null)}}</td>
            <td class="{{$loop->last? 'bbottom' : ''}}">{{$DEC($datamon['pesos'] ?? null)}}</td>
            @if($loop->first)
            <td class="bbottom">{{$DEC($data['Fijo']['determinado'] ?? null)}}</td>
            @else
            <td class="celda_vacia nob">&nbsp;</td>
            @endif
          </tr>
          @endforeach
          <tr>
            <?php $valores = 1+count($data['Valores']['dia'])+count($data['Valores']['hora']); ?>
            <th class="celda_vacia nob" colspan="2" rowspan="{{$valores}}">&nbsp;</th>
            <th class="b" style="text-align: right;">Valor Mensual</th>
            <td class="b">{{$DEC($data['Valores']['mes'] ?? null)}}</td>
            <th class="celda_vacia nob" rowspan="{{$valores}}">&nbsp;</th>
          </tr>
          @foreach($data['Valores']['dia'] as $div => $val)
          <tr>
            <th class="b" style="text-align: right;">Valor Diario {{$loop->count > 1? ('('.$div.')') : ''}}</th>
            <td class="b">{{$DEC($val ?? null)}}</td>
          </tr>
          @endforeach
          @foreach($data['Valores']['hora'] as $div => $val)
          <tr>
            <th class="b" style="text-align: right;">Valor Hora {{$loop->count > 1? ('('.$div.')') : ''}}</th>
            <td class="b">{{$DEC($val ?? null)}}</td>
          </tr>
          @endforeach
        </tbody>
        
        @yield('separator')
        <thead>
          <tr>
            <th class="celda_vacia nob" rowspan="2">&nbsp;</th>
            <th style="border-left: 1px solid black;" colspan="4">Mesas</th>
          </tr>
          <tr>
            <th>Día semana</th>
            <th>Días</th>
            <th>Mesas</th>
            <th>Determinado</th>
          </tr>
        </thead>
        <tbody>
          <?php $mesas = $data['Fijo']['Mesas']; ?>
          <tr>
            <td class="celda_vacia nob" rowspan="6">&nbsp;</td>
            <td class="bleft">Lunes-Jueves</td>
            <td>{{$DEC($mesas['Lunes-Jueves']['dias'] ?? null)}}</td>
            <td>{{$DEC($mesas['Lunes-Jueves']['mesas'] ?? null)}}</td>
            <td class="bbottom">{{$DEC($mesas['Total']['determinado'] ?? null)}}</td>
          </tr>
          <tr>
            <td>Viernes-Sábados</td>
            <td>{{$DEC($mesas['Viernes-Sábados']['dias'] ?? null)}}</td>
            <td>{{$DEC($mesas['Viernes-Sábados']['mesas'] ?? null)}}</td>
            <td class="celda_vacia nob" rowspan="5">&nbsp;</td>
          </tr>
          <tr>
            <td>Domingos</td>
            <td>{{$DEC($mesas['Domingos']['dias'] ?? null)}}</td>
            <td>{{$DEC($mesas['Domingos']['mesas'] ?? null)}}</td>
          </tr>
          <tr>
            <td>Todos</td>
            <td>{{$DEC($mesas['Todos']['dias'] ?? null)}}</td>
            <td>{{$DEC($mesas['Todos']['mesas'] ?? null)}}</td>
          </tr>
          <tr>
            <td>Fijos</td>
            <td>{{$DEC($mesas['Fijos']['dias'] ?? null)}}</td>
            <td>{{$DEC($mesas['Fijos']['mesas'] ?? null)}}</td>
          </tr>
          <tr>
            <td class="celda_vacia nob" colspan="2">&nbsp;</td>
            <td class="bleft">{{$DEC($mesas['Total']['mesas'] ?? null)}}</td>
          </tr>
        </tbody>
          
        @yield('separator')
        <thead>
          <tr>
            <th class="celda_vacia nob">&nbsp;</th>
            <th class="bleft" colspan="4">Adicionales</th>
          </tr>
          <tr>
            <th class="celda_vacia nob">&nbsp;</th>
            <th class="bleft">Detalle</th>
            <th>Horas</th>
            <th>Mesas</th>
            <th>Determinado</th>
          </tr>
        </thead>
        <tbody>
          @foreach($data['Fijo']['Adicionales'] as $tfa => $data_tfa)
          @if($tfa != 'Total')
          <tr>
            <td class="celda_vacia nob">&nbsp;</td>
            <td class="bleft">{{$tfa}}</td>
            <td>{{$DEC($data_tfa['horas'] ?? null)}}</td>
            <td>{{$DEC($data_tfa['mesas'] ?? null)}}</td>
            <td>{{$DEC($data_tfa['determinado'] ?? null)}}</td>
          </tr>
          @else
          <tr>
            <td class="celda_vacia nob" colspan="2">&nbsp;</td>
            <td class="bleft">{{$DEC($data_tfa['horas'] ?? null)}}</td>
            <td>{{$DEC($data_tfa['mesas'] ?? null)}}</td>
            <td>{{$DEC($data_tfa['determinado'] ?? null)}}</td>
          </tr>
          @endif
          @endforeach
        </tbody>
        
        @yield('separator')
        @yield('separator')
        <thead>
          <tr>
            <th colspan="{{$C}}">Totales</th>
          </tr>
          <tr>
            <th class="celda_vacia nob" colspan="2" rowspan="3">&nbsp;</th>
            <th class="bleft">Canon Físico</th>
            <th>{{$DEC($data['Canon']['Físico'] ?? null)}}</th>
            <th class="celda_vacia nob" rowspan="3">&nbsp;</th>
          </tr>
          <tr>
            <th>Canon Online</th>
            <th>{{$DEC($data['Canon']['Online'] ?? null)}}</th>
          </tr>
          <tr>
            <th>Canon</th>
            <th>{{$DEC($data['Canon']['Total'] ?? null)}}</th>
          </tr>
        </thead>
        @yield('separator')
      </table>
    </div>
  </div>
</body>

<script>
  const parametros = {
    'planilla': 'canon_mensual',
    'casino': '{{$casino}}',
    'año': '{{$año}}',
    'mes': '{{$mes}}'
  };
  const nombre_archivo = Object.values(parametros).join('_');
  const planilla = 'canon_mensual';
  const año = "{!! isset($año)? $año : '' !!}";
  const mes = "{!! isset($mes)? $mes : '' !!}";
  const fecha_planilla = "{!! date('Ymdhi') !!}";
  
  const colors = [
    @foreach($abbr_casinos as $_cas)
    @continue($_cas == 'TOTAL')
    window.getComputedStyle(document.body).getPropertyValue('--color-{!! $_cas !!}'),
    @endforeach
  ];
</script>
<script src="/js/Canon/planillas.js?1" charset="utf-8" type="module"></script>

</html>
