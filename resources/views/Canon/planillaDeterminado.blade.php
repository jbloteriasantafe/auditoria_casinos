<!DOCTYPE html>

<html>

<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  table-layout: fixed;
  word-wrap: break-word !important;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
}

tr:nth-child(even) {
  background-color: #dddddd;
}
</style>

<?php 
function ucwords_espacios($s){
  return ucwords(str_replace('_',' ',$s));
}
?>

<head>
  <meta charset="utf-8">
  <title></title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
</head>
<body>
  <?php 
    $filas = ['MESAS','MESAS AD.','CANON FIJO','MTM','BINGO','TOTAL FISICO','JOL','CANON VARIABLE','TOTAL'];
    $columnas = ["BENEFICIO","DEV. BRUTO","DEV. DEDUCCIÓN","DEVENGADO","DETERMINADO"];
    $casinos = ['Melincué','Santa Fe','Rosario','TOTAL'];
    $redondeos = [
      'Melincué' => [
        'MESAS' => [
          'DEVENGADO' => 1,
        ],
        'MESAS AD.' => [
          'DEVENGADO' => 1,
        ],
        'MTM' => [
          'DEVENGADO' => 1,
        ],
        'BINGO' => [
          'DEVENGADO' => 1,
        ],
        'JOL' => [
          'DEVENGADO' => 1,
        ],
        'CANON FIJO' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 2,
          'DETERMINADO' => 1,
        ],
        'TOTAL FISICO' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 2,
          'DETERMINADO' => 1,
        ],
        'CANON VARIABLE' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 2,
          'DETERMINADO' => 1,
        ],
        'TOTAL' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 4,
          'DETERMINADO' => 1,
        ]
      ],
      'Santa Fe' => [
        'MESAS' => [
          'DEVENGADO' => 1,
        ],
        'MESAS AD.' => [
          'DEVENGADO' => 1,
        ],
        'MTM' => [
          'DEVENGADO' => 1,
        ],
        'BINGO' => [
          'DEVENGADO' => 1,
        ],
        'JOL' => [
          'DEVENGADO' => 1,
        ],
        'CANON FIJO' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 2,
          'DETERMINADO' => 1,
        ],
        'TOTAL FISICO' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 2,
          'DETERMINADO' => 1,
        ],
        'CANON VARIABLE' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 2,
          'DETERMINADO' => 1,
        ],
        'TOTAL' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 4,
          'DETERMINADO' => 1,
        ]
      ],
      'Rosario' => [
        'MESAS' => [
          'DEVENGADO' => 1,
        ],
        'MESAS AD.' => [
          'DEVENGADO' => 1,
        ],
        'MTM' => [
          'DEVENGADO' => 1,
        ],
        'BINGO' => [
          'DEVENGADO' => 1,
        ],
        'JOL' => [
          'DEVENGADO' => 1,
        ],
        'CANON FIJO' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 2,
          'DETERMINADO' => 1,
        ],
        'TOTAL FISICO' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 2,
          'DETERMINADO' => 1,
        ],
        'CANON VARIABLE' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 2,
          'DETERMINADO' => 1,
        ],
        'TOTAL' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO"' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 4,
          'DETERMINADO' => 1,
        ]
      ],
      'TOTAL' => [
        'MESAS' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 3,
          'DETERMINADO' => 1,
        ],
        'MESAS AD.' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 3,
          'DETERMINADO' => 1,
        ],
        'MTM' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 3,
          'DETERMINADO' => 1,
        ],
        'BINGO' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 3,
          'DETERMINADO' => 1,
        ],
        'JOL' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 3,
          'DETERMINADO' => 1,
        ],
        'CANON FIJO' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 3,
          'DETERMINADO' => 1,
        ],
        'TOTAL FISICO' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 3,
          'DETERMINADO' => 1,
        ],
        'CANON VARIABLE' => [
          'BENEFICIO' => 1,
          'DEV. BRUTO' => 1,
          'DEV. DEDUCCIÓN' => 1,
          'DEVENGADO' => 3,
          'DETERMINADO' => 1,
        ],
        'TOTAL' => [
          'BENEFICIO' => 3,
          'DEV. BRUTO' => 3,
          'DEV. DEDUCCIÓN' => 3,
          'DEVENGADO' => 4,
          'DETERMINADO' => 3,
        ]
      ]
    ];//@TODO: cambiar a que se chequee dinamicamente segun el campo
    $r = function($CAS,$TIPO,$COL) use ($redondeos){
      return (($redondeos[$CAS] ?? [])[$TIPO] ?? [])[$COL] ?? 0;
    }
  ?>
  <style>
    .tablaValores th{
      text-align: center;
      font-size: 0.65em;
    }
    .tablaValores td{
      text-align: right;
      font-size: 0.7em;
    }
    .tablaValores tr.borde_abajo td,
    .tablaValores tr.borde_abajo th {
      border-bottom: 1px solid black;
    }
    .tablaValores tr.borde td,
    .tablaValores tr.borde th {
      border: 1px solid black;
    }
  </style>
  <table class="tablaValores" style="width: 100%;">
    @foreach($casinos as $cas)
    <tr class="borde">
      <th colspan="{{count($columnas)+1}}" style="background: white;">{{$cas}}</th>
    </tr>
    <tr class="borde">
      <th style="background: white;">Ajuste</th>
      <td style="background: white;">99.999.999.999,99</td>
      <th style="background: white;">Saldo Anterior</th>
      <td style="background: white;">99.999.999.999,99</td>
      <th style="background: white;">Cargos Adic.</th>
      <td style="background: white;">99.999.999.999,99</td>
    </tr>
    <tr class="borde_abajo">
      <th>TIPO</th>
      @foreach($columnas as $col)
      <th>{{$col}}</th>
      @endforeach
    </tr>
    @foreach($filas as $t)
    <tr class="{{($loop->last && !$loop->parent->last)? 'borde_abajo' : ''}}">
      <th>{!! $t !!}</th>
      @foreach($columnas as $col)
      <td>
        <div style="text-align: left;word-wrap: normal;width: 20%;position: absolute;padding: 0;margin: 0;">
          {!! implode('&nbsp;',array_fill(0,$r($cas,$t,$col),'<span>1¢</span>')) !!}
        </div>
        <div style="text-align: right;word-wrap: break-word;font-size: 140%;width: 100%;f">99.999.999.999,99</div>
      </td>
      @endforeach
    </tr>
    @endforeach
    @endforeach
  </table>
  <div style="break-after:page"></div>
  <div style="font-size: 10.5pt;line-height: 12pt;font-family: 'Liberation Serif';font-variant: small-caps;">
    <p style="text-align: right;">
      SANTA FE, “Cuna de la Constitución Nacional”, {{$fecha_planilla[3]}} {{$fecha_planilla[2]}} de {{$fecha_planilla[1]}} de {{$fecha_planilla[0]}}.
    </p>
    
    <div style="padding-left: 63.63%;">
      <span                           ><b>Ref: </b></span>Expte. Nro. XXXXX-XXXXXXX-X<br>
      <span style="visibility: hidden"><b>Ref: </b></span>Canon  {{$año_mes[1]}} {{$año_mes[0]}}<br>
      <span style="visibility: hidden"><b>Ref: </b></span>Casino de {{$casino->nombre}} S.A.
    </div>
    
    <div style="font-weight: bold;font-size: 13pt;">
      <?php
        $espacios  = str_repeat('&nbsp;',21);
        $espacios .= '/'.$espacios;
        $datos_cas = $datos[$casino->nombre] ?? [];
        $datos_cas = array_map(function(&$v){return $v[''] ?? '0';},$datos_cas);
        $importe = function($k) use ($datos_cas){ return $datos_cas[$k] ?? '!ERROR!'; };
        $ch_enum = function($offset){ return chr(ord('a')+$offset); };
      ?>
      <p>
        Sr.<br>
        Gustavo Rivera<br>
        Director General<br>
        de Casinos y Bingos<br>
        <span><u>S{!! $espacios !!}D</u></span>
      </p>
    </div>
    
    <p style="text-indent: 27.27%;">
      El presente informe se emite a raíz del pago del CANON del mes de Agosto de 2024, efectuado por el concesionario Casino de Rosario S.A., el cual venció el Martes 10-09-24.<br>
    </p>
    <p style="text-indent: 27.27%;">
      En ese marco y en función del análisis efectuado se informa:
    </p>
    <ul>
      <li>
        <div style="font-weight: bold;">
          <u>CANON FIJO:</u>
          <br>
          <p>Cotización Monedas de Cambio Comprador del día {{date_format(date_create($COT['determinado_fecha_cotizacion']),"d/m/y")}}:</p>
          <p style="padding-left: 6.06%;">
            Dólar: $ {{$COT['determinado_cotizacion_dolar']}}<br>
            Euro: $ {{$COT['determinado_cotizacion_euro']}}
          </p>
        </div>
        
        @foreach($canon['canon_fijo_mesas'] as $cfmidx => $cfm)
        <p style="padding-left: 6.06%;">
          {{$ch_enum($cfmidx)}}) Canon Fijo Mesas de Paño:
        </p>
        <p>
          <?php
            $tipos_dias = [
              '_viernes_sabados' => 'viernes y sábados',
              '_domingos' => 'Domingos',
              '_lunes_jueves' => 'de lunes a jueves',
              '_todos' => 'en el mes',
              '_fijos' => 'fijas'
            ];
            $tipos_dias = array_filter($tipos_dias,function($v,$k) use ($cfm){
              return $cfm["mesas{$k}"] > 0;
            },ARRAY_FILTER_USE_BOTH);
          ?>
          @foreach($tipos_dias as $k => $str)
          <u>Cantidad de Días/Mesas Habilitadas {{$str}}:</u><b> {{$cfm["dias{$k}"]}} días / {{$cfm["mesas{$k}"]}} mesas.-</b>
          <br>
          @endforeach
          <br>
          <?php
          $a_ingles = function(string $numero){
            return str_replace(',','.',str_replace('.','',$numero));
          };
          $vd_dolar = $a_ingles($cfm['determinado_valor_dolar_diario_cotizado']);
          $vd_euro  = $a_ingles($cfm['determinado_valor_euro_diario_cotizado']);
          $valor_diario = bcadd($vd_dolar,$vd_euro,max(bcscale_string($vd_dolar),bcscale_string($vd_euro)));
          $valor_diario = bcround_ndigits($valor_diario,2);
          $valor_diario = App\Http\Controllers\CanonController::formatear_decimal($valor_diario);
          ?>
          
          <b>Valor  diario/mesa 
          <br>
          = (U$S {{$cfm['valor_dolar']}} * tipo de cambio comprador al día anterior al pago +  € {{$cfm['valor_euro']}} * tipo de cambio comprador al día anterior al pago) / 30</b>
          <br>
          = (U$S {{$cfm['valor_dolar']}} * ${{$COT['determinado_cotizacion_dolar']}} + € {{$cfm['valor_euro']}} * ${{$COT['determinado_cotizacion_dolar']}}) / 30 = $ {{$valor_diario}}
          <br><br>
          
          <b>Total canon mensual
          <br>
          = 
          @foreach($tipos_dias as $k => $str)
          {{$loop->first? '' : '+'}} diario/mesa *  cant. Días * {{$cfm["mesas$k"]}} mesas
          @endforeach
          </b>
          <br>
          = 
          @foreach($tipos_dias as $k => $str)
          {{$loop->first? '' : '+'}} ${{$valor_diario}} * {{$cfm["dias{$k}"]}} * {{$cfm["mesas{$k}"]}}
          @endforeach
        </p>
        <br>
        <p style="font-weight: bold;text-align: right;">
          Valor Canon Fijo Mesas habilitadas - $ XXXXXXXXXXXXX.-
        </p>
        @endforeach
        
        <?php $rel_cfma_idx = 0; ?>
        @foreach($canon['canon_fijo_mesas_adicionales'] as $cfmaidx => $cfma)
        @continue($cfma['horas'] == '0')
        <p style="padding-left: 6.06%;">
          {{$ch_enum(count($canon['canon_fijo_mesas'])+($rel_cfma_idx++))}}) Canon Fijo {{$cfma['tipo']}}:
        </p>
        <p>
          <b>Total horas/Mesas Torneos 
          <br>
          = Valor Diario por mesa / {{$cfma['horas_dia']}} Horas  * Horas/Mesas Utilizadas</b>
          <br>
          = $ {{$cfma['determinado_valor_dia']}} / {{$cfma['horas_dia']}} * {{$cfma['horas']}} horas/mesas
        </p>
        <br>
        <p style="font-weight: bold;text-align: right;">
          Valor Canon Fijo Torneos de Póker - $ XXXXXXXXXXX
        </p>
        @endforeach
        
        <br>
        <p style="font-weight: bold;text-align: right;">
          TOTAL CANON FIJO MESAS HABILITADAS - $ {{$importe('Paños')}}.-
        </p>
      </li>
      <li>
        <div style="font-weight: bold;">
          <u>CANON VARIABLE:</u>
        </div>
        <?php
          $CVnombres = [
            'maquinas' => 'Máquinas Tragamonedas',
            'JOL' => 'Juegos On Line',
            'bingo' => 'Bingo'
          ];
          $CVnombres2 = [
            'maquinas' => 'MTM',
            'JOL' => 'ON LINE',
          ];
          $tipo_a_total = [
            'Maquinas' => 'MTM',
          ];
        ?>
        @foreach($canon['canon_variable'] as $cvidx => $cv)
        <?php
          $tipo = $cv['tipo'] ?? '!ERROR!';
          $n1 = $CVnombres[$tipo] ?? $tipo;
          $n2 = strtoupper($CVnombres2[$tipo] ?? $tipo);
          $t = $tipo_a_total[$tipo] ?? $tipo;
        ?>
        <p style="padding-left: 6.06%;">
          {{$ch_enum($cvidx)}}) {{$n1}}:
        </p>
        <p>
          <b>Valor Canon Variable {{$n2}}<br>
          = [(Valor Resultado Bruto En Pesos + Valor Resultado Bruto En Dólares convertido a pesos) – (1º y 2º quincena Impuesto s/ Ley 27.346 aplicación RG 4036-E de AFIP)]  * Alícuota Aplicable</b><br>
          = $ 5.556.935.935,50 ($.5.464.460.653,16+ $.92.475.282,34) - $ 173.504.144,35 ($82.191.860,56 + $91.312.283,79) * 20,56 % = $ 5.383.431.791,15 * 20,56 %
        </p>
        <br>
        <p style="font-weight: bold;text-align: right;">
          Valor Canon Variable {{$n2}} – $ {{$importe($t)}}
        </p>
        @endforeach
        
        <br>
        <p style="font-weight: bold;text-align: right;">
          VALOR TOTAL CANON VARIABLE - $ 1.627.524.542,57
        </p>
      </li>
    </ul>
    
    <br>
    <p style="font-weight: bold;text-align: right;">
      TOTAL CANON DETERMINADO MES DE AGOSTO 2024 - $ 1.876.459.737,09
    </p>
    
    <p>
      Por lo expuesto en el presente informe, se debería imputar como pago del canon del mes de Agosto 2024 al Casino Rosario S.A. las siguientes sumas:<br>
      * $  1.360.040.713,03 como pago del canon casino físico.<br>
      * $  516.419.024,06 como pago del canon casino on line.<br>
      <br>
      A los fines que estime corresponder, elévese.-	
    </p>
  </div>
</body>

</html>
