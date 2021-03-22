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

    @foreach($rta as $diaria)
      @if($loop->first)
      @else
      <div style="page-break-after:always;"></div>
      <div class="encabezadoImg">
        <img src="img/logos/banner_nuevo2_landscape.png" width="900">
        <h2><span>RMES02 | Informe mensual por casinos de MESAS DE PAÑO.</span></h2>
      </div>
      <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
      <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i'); print_r($hoy); ?></div>
      @endif
      <h4 style="font-family:Roboto-Condesed !important;top:-10px;bottom:-30px!important;padding-top:-40px !important;"><i>RESULTADOS EN {{$diaria['importacion']->moneda->siglas}} AL {{$diaria['importacion']->fecha}}<i></h4>

      <table style="border-collapse: collapse;">
        <thead>
          <tr>
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">MESA</th>
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;" width="120px">JUEGO</th>
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">SALDO EN FICHAS</th>
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray; ">DROP</th>
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray; ">RET.</th>
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray; ">REP.</th>
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">UTILIDAD</th>
            @if($diaria['importacion']->moneda->siglas != 'ARS')
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">COTIZACIÓN</th>
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">CONVERSIÓN</th>
            @endif
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">DIF.</th>
            <th class=" tablaInicio" style="background-color: #c0c0c0; border-color: gray;">HOLD</th>
          </tr>
        </thead>
        <tbody>
          @foreach($diaria['detalles'] as $mesa)
          <tr>
            <td class="tablaCampos" style=" font-size:12px; border-color: gray;">{{$mesa->nro_mesa}}</td>
            <td class="tablaCampos" style="font-size:12px; border-color: gray;" width="120px">{{$mesa->nombre_juego}}</td>
            <td class="tablaCampos" style="font-size:12px; border-color: gray;">{{$mesa->saldo_fichas}}</td>
            <td class="tablaCampos" style="font-size:12px; border-color: gray;">{{$mesa->droop}}</td>
            <td class="tablaCampos" style="font-size:12px; border-color: gray;">{{$mesa->retiros}}</td>
            <td class="tablaCampos" style="font-size:12px; border-color: gray;">{{$mesa->reposiciones}}</td>
            <td class="tablaCampos" style="font-size:12px; border-color: gray;">{{$mesa->utilidad}}</td>
            @if($diaria['importacion']->moneda->siglas != 'ARS')
            <td class="tablaCampos" style="font-size:12px; border-color: gray;">{{$mesa->cotizacion}}</td>
            <td class="tablaCampos" style="font-size:12px; border-color: gray;">{{$mesa->conversion}}</td>
            @endif
            <td class="tablaCampos" style="font-size:12px; border-color: gray;">{{$mesa->diferencia_cierre}}</td>
            <td class="tablaCampos" style="font-size:12px; border-color: gray;">{{$mesa->hold}}</td>
          </tr>
          @endforeach
          <!-- fila totalizadora -->
          <tr>
            <th style=" font-size:12px; border-color: gray;">TOTALES</th>
            <th style="font-size:12px; border-color: gray;">--</th>
            <th style="font-size:12px; border-color: gray;">{{$diaria['importacion']->saldo_diario_fichas}}</th>
            <th style="font-size:12px; border-color: gray;">{{$diaria['importacion']->total_diario}}</th>
            <th style="font-size:12px; border-color: gray;">{{$diaria['importacion']->utilidad_diaria_total}}</th>
            <th style="font-size:12px; border-color: gray;">{{$diaria['importacion']->total_diario_retiros}}</th>
            <th style="font-size:12px; border-color: gray;">{{$diaria['importacion']->total_diario_reposiciones}}</th>
            @if($diaria['importacion']->moneda->siglas != 'ARS')
            <th style="font-size:12px; border-color: gray;">{{$diaria['importacion']->cotizacion}}</th>
            <th style="font-size:12px; border-color: gray;">{{$diaria['importacion']->conversion_total}}</th>
            @endif
            <th style="font-size:12px; border-color: gray;">{{$diaria['importacion']->diferencias}}</th>
            <th style="font-size:12px; border-color: gray;">{{$diaria['importacion']->hold_diario}}</th>
          </tr>
        </tbody>
      </table>
    @endforeach

    @if(!isset($rta2))
    <h4>MODIFICACIONES REALIZADAS SOBRE CIERRES E IMPORTACIONES VINCULADOS</h4>
    <table>
      <thead>
        <tr>
          <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">MESA</th>
          <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">JUEGO</th>
          <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">CAMPO MODIFICADO</th>
          <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">ENTIDAD</th>
          <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">VALOR ANTERIOR</th>
          <th class=" tablaInicio" style="background-color: #dddddd; border-color: gray;">VALOR NUEVO</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rta2 as $m)
        <tr>
          <td class="tablaCampos" style="border-color: gray;">{{$m['mesa']}}</td>
          <td class="tablaCampos" style="border-color: gray;">{{$m['juego']}}</td>
          <td class="tablaCampos" style="border-color: gray;">{{$m['campo_modificado']}}</td>
          <td class="tablaCampos" style="border-color: gray;">{{$m['entidad']}}</td>
          <td class="tablaCampos" style="border-color: gray;">{{$m['valor_anterior']}}</td>
          <td class="tablaCampos" style="border-color: gray;">{{$m['valor_nuevo']}}</td>
        </tr>
        @endforeach
        @else
        <br>
        <h4 class="tablaCampos" style="font-weight:bold !important">NO SE HAN REALIZADO MODIFICACIONES EN LOS DATOS CARGADOS </h4>
        @endif
      </tbody>
    </table>
  </body>

</html>
