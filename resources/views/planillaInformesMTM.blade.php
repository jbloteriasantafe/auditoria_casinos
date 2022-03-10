<!DOCTYPE html>

<html>
  <style>
  table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 98%;
  }

  td, th {
    border: 1px solid #dddddd;
    padding: 3px;
  }
  th {
    text-align: center;
  }
  td {
    text-align: right;
  }
  .centrar {
    text-align: center;
  }

  tr:nth-child(even) {
    background-color: #dddddd;
  }
  </style>
  <?php 
  $calculado = !is_null($desde_hasta);
  $mostrar_total_apostadoypremios = true;
  foreach($beneficios as $b){
    if($b->apostado == '' || $b->premios == ''){
      $mostrar_total_apostadoypremios = false;
      break;
    }
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
    <div class="encabezadoImg">
      <img src="img/logos/banner_nuevo2_landscape.png" width="900">
      <h2><span>MTM | Informe de beneficios ({{$sum->tipoMoneda}})</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></span></div>
    <div class="primerEncabezado">Se han realizado los procedimientos de control correspondientes
    al mes de <b>{{$sum->mes}}</b> del <b>Casino de {{$sum->casino}}</b>.<br>Teniendo en cuenta lo anterior, se informa que para <b>Máquinas Tragamonedas</b>
    se obtuvo un beneficio de <b>{{$sum->tipoMoneda}} {{$sum->totalBeneficio}}</b>, detallando a continuación el producido diario. 
    <span style="font-size: 60%;">{{$calculado? 'MAQUINAS ('.$desde_hasta.')' : ''}}</span> </div>
    <br>
    <table>
      <tr>
        <th class="tablaInicio">FECHA</th>
        <th class="tablaInicio">MAQUINAS</th>
        <th class="tablaInicio">APOSTADO</th>
        <th class="tablaInicio">PREMIOS</th>
        @if ($sum->casino != 'Rosario' && !$calculado)
        <th class="tablaInicio">P.MAYORES</th>
        @endif
        @if ($mostrar_pdev)
        <th class="tablaInicio">%dev</th>
        @endif
        @if ($sum->tipoMoneda == 'US$')
        <th class="tablaInicio">BENEFICIO (US$) {{$calculado? '(CALCULADO)' : ''}}</th>
        <th class="tablaInicio">COTIZACIÓN<sup>*</sup></th>
        <th class="tablaInicio">BENEFICIO ($)</th>
        @elseif($sum->tipoMoneda == '$')
        <th class="tablaInicio">BENEFICIO {{$calculado? '(CALCULADO)' : ''}}</th>
        @endif
      </tr>
      @foreach ($beneficios as $b)
      <tr>
        <td class="tablaCampos centrar">{{$b->fecha}}</td>
        <td class="tablaCampos centrar">{{$b->cantidad_maquinas}}</td>
        <td class="tablaCampos">{{$b->apostado}}</td>
        <td class="tablaCampos">{{$b->premios}}</td>
        @if ($sum->casino != 'Rosario' && !$calculado)
        <td class="tablaCampos">{{$b->pmayores}}</td>
        @endif
        @if ($mostrar_pdev)
        <td class="tablaCampos">{{$b->pdev}}</td>
        @endif
        @if ($sum->tipoMoneda == 'US$')
        <td class="tablaCampos">{{$b->beneficio}}</td>
        <td class="tablaCampos">{{$b->cotizacion}}</td>
        <td class="tablaCampos">{{$b->beneficioPesos}}</td>
        @elseif($sum->tipoMoneda == '$')
        <td class="tablaCampos">{{$b->beneficio}}</td>
        @endif
      </tr>
      @endforeach
    </table>
    <br><br>
    <table>
      <tr>
        <th class="tablaInicio">MAQUINAS</th>
        <th class="tablaInicio">APOSTADO</th>
        <th class="tablaInicio">PREMIOS</th>
        @if ($sum->casino != 'Rosario' && !$calculado)
        <th class="tablaInicio">P.MAYORES</th>
        @endif
        @if ($mostrar_pdev)
        <th class="tablaInicio">%dev</th>
        @endif
        @if ($sum->tipoMoneda == 'US$')
        <th class="tablaInicio">BENEFICIO (US$) {{$calculado? '(CALCULADO)' : ''}}</th>
        <th class="tablaInicio">BENEFICIO ($)</th>
        @elseif($sum->tipoMoneda == '$')
        <th class="tablaInicio">BENEFICIO {{$calculado? '(CALCULADO)' : ''}}</th>
        @endif
      </tr>
      <tr>
        <td class="tablaCampos centrar">{{$sum->cantidad_maquinas}}</td>
        <td class="tablaCampos centrar">{{$mostrar_total_apostadoypremios? $sum->totalApostado : ''}}</td>
        <td class="tablaCampos centrar">{{$mostrar_total_apostadoypremios? $sum->totalPremios  : ''}}</td>
        @if ($sum->casino != 'Rosario' && !$calculado)
        <td class="tablaCampos centrar">{{$sum->totalPmayores}}</td>
        @endif
        @if ($mostrar_pdev)
        <td class="tablaCampos">{{$sum->totalPdev}}</td>
        @endif
        @if ($sum->tipoMoneda == 'US$')
        <td class="tablaCampos centrar">{{$sum->totalBeneficio}}</td>
        <td class="tablaCampos centrar">{{$sum->totalBeneficioPesos}}</td>
        @elseif($sum->tipoMoneda == '$')
        <td class="tablaCampos centrar">{{$sum->totalBeneficio}}</td>
        @endif
      </tr>
    </table>
    @if ($sum->tipoMoneda == 'US$')
    <div>
      <p> 
        <FONT SIZE=1> <strong>* </strong>Cotización establecida por la Dirección General de Casinos y Bingos (Nota N° 277/16) <br>
        <i> "... se utilizará como tipo de cambio para efectuar la conversión a pesos, el valor del dólar 
          oficial tipo comprador (información suministrada por el Banco de la Nación Argentina) correspondiente a la fecha de producción
            de las MTM. Para el caso de los días Sábados, Domingos y Feriados, se utilizará como tipo de cambio, el del último día hábil disponible.."
          </FONT>
        </i>
      </p>
    </div>
    @endif
  </body>
</html>
