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
  </style>

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
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">JUEGO</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">MESA</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray; ">DROP</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray; ">REPOS.</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray; ">RETIROS</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">UTIL.</th>
          @if($importacion->moneda->siglas != 'ARS')
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">COTIZA CIÓN</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">CONVER SIÓN</th>
          @endif
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">HOLD</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">SALDO EN FICHAS</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">SALDO EN FICHAS (Rel.)</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">DIF.</th>
          <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">AJUSTE</th>
        </tr>
      </thead>
      <tbody>
        @foreach($det_importacion as $d)
        <tr>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->siglas_juego}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->nro_mesa}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->droop}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->reposiciones}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->retiros}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->utilidad}}</td>
          @if($importacion->moneda->siglas != 'ARS')
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->cotizacion_diaria}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->conversion}}</td>
          @endif
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->hold}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->saldo_fichas}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->saldo_fichas_relevado}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->diferencia_saldo_fichas}}</td>
          <td class="tablaCampos" style="font-size:10px; border-color: gray;">{{$d->ajuste_fichas}}</td>
        </tr>
        @endforeach
        <!-- fila totalizadora -->
        <tr>
          <th style="font-size:10px; border-color: gray;">TOTALES</th>
          <th style="font-size:10px; border-color: gray;">--</th>
          <th style="font-size:10px; border-color: gray;">{{$importacion->droop}}</th>
          <th style="font-size:10px; border-color: gray;">{{$importacion->reposiciones}}</th>
          <th style="font-size:10px; border-color: gray;">{{$importacion->retiros}}</th>
          <th style="font-size:10px; border-color: gray;">{{$importacion->utilidad}}</th>
          @if($importacion->moneda->siglas != 'ARS')
          <th style="font-size:10px; border-color: gray;">{{$importacion->cotizacion_diaria}}</th>
          <th style="font-size:10px; border-color: gray;">{{$importacion->conversion_total}}</th>
          @endif
          <th style="font-size:10px; border-color: gray;">{{$importacion->hold}}</th>
          <th style="font-size:10px; border-color: gray;">{{$importacion->saldo_fichas}}</th>
          <th style="font-size:10px; border-color: gray;">{{$importacion->saldo_fichas_relevado}}</th>
          <th style="font-size:10px; border-color: gray;">{{$importacion->diferencia_saldo_fichas}}</th>
          <th style="font-size:10px; border-color: gray;">{{$importacion->ajuste_fichas}}</th>
        </tr>
      </tbody>
    </table>
    <h5>Observaciones</h5>
    <div class="tablaCampos">
    {{$importacion->observacion}}
    </div>
    <br>
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
  </body>
</html>
