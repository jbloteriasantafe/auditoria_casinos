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
    text-align: left;
    padding: 3px;
  }

  tr:nth-child(even) {
    background-color: #dddddd;
  }

  footer {
      margin-top:50px;
      width:200%;
      height:300px;
  }
  .text_field {
    text-align: center;
    padding: 0px;
    font-size:9px !important;
    border-color: gray;
  }
  .number_field {
    text-align: right;
    padding: 0px;
    font-size:9px !important;
    border-color: gray;
  }
  </style>
  <?php 
  $string_number = function ($s,$n = 2){
    if(is_null($s) || $s === "" || $s === "--") return $s;
    $s = str_replace(",","",$s);
    $coma = str_replace(".",",",$s);
    $poscero = strpos($coma,",");
    if($poscero == false) return $coma.",".str_repeat("0",$n);
    $ceros = strlen($coma)-$poscero-1;
    $n -= $ceros;
    if($n < 0) return $coma;
    return $coma.str_repeat("0",$n);
  }
  ?>

  <head>
    <meta charset="utf-8">
    <title></title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
    <link href="/css/importacionFuentes.css" rel="stylesheet">
    <link rel="stylesheet" href="/web-fonts-with-css/css/fontawesome-all.css">

    <script src="/js/jquery.js"></script>
    <script src="/js/bootstrap.js"></script>
    <script src="/js/ajaxError.js"></script>
  </head>

  <body>
    <div class="encabezadoImg">
      <img src="img/logos/banner_nuevo2_landscape.png" width="900">
      <br>
      <h2><span>RMES02 | Informe Diario de Mesas de Paño.</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA INFORME</div>
    <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i'); print_r($hoy); ?></div>
    <h4 style="font-family:Roboto-Condesed !important;top:-10px;bottom:-30px!important;padding-top:-40px !important;">
      <i>RESULTADOS EN {{$importacion->moneda->siglas}} AL {{$importacion->fecha}}<i>
    </h4>
    <table style="table-layout: fixed;">
      <thead>
        <tr>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;width: 7%;">JUEGO</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;width: 5%;">MESA</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">DROP</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">REPOS.</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">RETIROS</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">UTIL.</th>
          @if($importacion->moneda->siglas != 'ARS')
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">COTIZA CIÓN</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">CONVER SIÓN</th>
          @endif
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;width: 6%;">HOLD</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">SALDO EN FICHAS</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">PROPINA</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">SALDO EN FICHAS (Rel.)</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">DIF.</th>
          <th class="tablaInicio text_field" style="background-color: #c0c0c0;">AJUSTE</th>
        </tr>
      </thead>
      <tbody>
        <?php $con_observacion_individual = false; ?>
        @foreach($det_importacion as $d)
        <?php $con_observacion_individual = $con_observacion_individual || !empty($d->observacion); ?>
        <tr>
          <td class="tablaCampos text_field">{{$d->siglas_juego}}</td>
          <td class="tablaCampos text_field">{{$d->nro_mesa}}</td>
          <td class="tablaCampos number_field">{{$string_number($d->droop)}}</td>
          <td class="tablaCampos number_field">{{$string_number($d->reposiciones)}}</td>
          <td class="tablaCampos number_field">{{$string_number($d->retiros)}}</td>
          <td class="tablaCampos number_field">{{$string_number($d->utilidad)}}</td>
          @if($importacion->moneda->siglas != 'ARS')
          <td class="tablaCampos number_field">{{$d->cotizacion_diaria? number_format($d->cotizacion_diaria,3,",","") : ""}}</td>
          <td class="tablaCampos number_field">{{$d->cotizacion_diaria? number_format($d->conversion,2,",","") : ""}}</td>
          @endif
          <td class="tablaCampos number_field">{{$d->hold != "--"? number_format($d->hold,2,",","")  : ""}}</td>
          <td class="tablaCampos number_field">{{$string_number($d->saldo_fichas)}}</td>
          <td class="tablaCampos number_field">{{$string_number($d->propina)}}</td>
          <td class="tablaCampos number_field">{{$string_number($d->saldo_fichas_relevado)}}</td>
          <td class="tablaCampos number_field">{{$string_number($d->diferencia_saldo_fichas)}}</td>
          <td class="tablaCampos number_field">{{number_format($d->ajuste_fichas,2,",","")}}</td>
        </tr>
        @endforeach
        <!-- fila totalizadora -->
        <tr>
          <th class="text_field">TOTALES</th>
          <th class="text_field">--</th>
          <th class="number_field">{{$string_number($importacion->droop)}}</th>
          <th class="number_field">{{$string_number($importacion->reposiciones)}}</th>
          <th class="number_field">{{$string_number($importacion->retiros)}}</th>
          <th class="number_field">{{$string_number($importacion->utilidad)}}</th>
          @if($importacion->moneda->siglas != 'ARS')
          <th class="number_field">{{$importacion->cotizacion_diaria? number_format($importacion->cotizacion_diaria,3,",","") : ""}}</th>
          <th class="number_field">{{$importacion->cotizacion_diaria? number_format($importacion->conversion_total,3,",","") : ""}}</th>
          @endif
          <th class="number_field">{{$importacion->hold != "--"? number_format($importacion->hold,2,",","")  : ""}}</th>
          <th class="number_field">{{$string_number($importacion->saldo_fichas)}}</th>
          <td class="number_field">{{$string_number($importacion->propina)}}</td>
          <th class="number_field">{{$string_number($importacion->saldo_fichas_relevado)}}</th>
          <th class="number_field">{{number_format($importacion->diferencia_saldo_fichas,2,",","")}}</th>
          <th class="number_field">{{number_format($importacion->ajuste_fichas,"2",",","")}}</th>
        </tr>
      </tbody>
    </table>
    
    @if($con_observacion_individual || !empty($importacion->observacion))
    <h5>Observaciones</h5>

    @if($con_observacion_individual)
    <table style="table-layout: fixed;">
      @foreach($det_importacion as $d)
      @if($d->observacion)
      <tr>
        <td class="tablaCampos" width="10%">{{$d->siglas_juego.$d->nro_mesa}}</td>
        <td class="tablaCampos" >{{$d->observacion}}</td>
      </tr>
      @endif
      @endforeach
    </table>
    @endif

    @if(!empty($importacion->observacion))
    <div class="tablaCampos" style="border: 1px solid gray;">
    {{$importacion->observacion}}
    </div>
    @endif

    @endif
  </body>
</html>
