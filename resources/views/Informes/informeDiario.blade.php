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

footer
{
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

    <script src="/js/jquery.js"></script>
    <script src="/js/bootstrap.js"></script>
    <script src="/js/ajaxError.js"></script>

  </head>
  <body>

  <div class="encabezadoImg">
        <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
        <br>
        <h2><span>RMES02 | Informe Diario de Mesas de Paño.</span></h2>
  </div>
  <div class="camposTab titulo" style="right:-15px;">FECHA INFORME</div>
  <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
        print_r($hoy); ?></div>


  @foreach($rta as $diaria)
    @if($loop->first)
    @else
    <div style="page-break-after:always;"></div>
    <div class="encabezadoImg">
          <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
          <h2><span>RMES02 | Informe mensual por casinos de MESAS DE PAÑO.</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
          print_r($hoy); ?></div>
    @endif
    <h4 style="font-family:Roboto-Regular;top:-10px;bottom:-30px!important;padding-top:-40px !important;"><i>RESULTADOS EN {{$diaria['importacion']->moneda->siglas}}<i></h4>

    <table style="border-collapse: collapse;">
      <thead>
        <tr align="center" >
          <th class=" tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">MESA</th>
          <th class=" tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">JUEGO</th>
          <th class=" tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">SALDO EN FICHAS</th>
          <th class=" tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">DROP</th>
          <th class=" tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">UTILIDAD</th>
          <th class=" tablaInicio"  style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important;">HOLD</th>
          @if($diaria['importacion']->moneda->siglas != 'ARS')
          <th class=" tablaInicio"  style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important;">COTIZACIÓN</th>
          <th class=" tablaInicio"  style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important;">CONVERSIÓN</th>
          @endif
          <th class=" tablaInicio"  style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important;">DIFERENCIAS</th>
        </tr>
      </thead>
      <tbody>
        @foreach($diaria['detalles'] as $mesa)
        <tr>
          <th style=" font-size:12px; border-color: gray;text-align:center !important;">{{$mesa->nro_mesa}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$mesa->nombre_juego}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$mesa->saldo_fichas}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$mesa->droop}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$mesa->utilidad}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$mesa->hold}}</th>
          @if($diaria['importacion']->moneda->siglas != 'ARS')
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$mesa->cotizacion}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$mesa->conversion}}</th>
          @endif
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$mesa->diferencia_cierre}}</th>
        </tr>
        @endforeach
        <!-- fila totalizadora -->
        <tr>
          <th style=" font-size:12px; border-color: gray;text-align:center !important;">TOTALES</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">--</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$diaria['importacion']->saldo_diario_fichas}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$diaria['importacion']->total_diario}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$diaria['importacion']->utilidad_diaria_total}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$diaria['importacion']->hold_diario}}</th>
          @if($diaria['importacion']->moneda->siglas != 'ARS')
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$diaria['importacion']->cotizacion}}</th>
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$diaria['importacion']->conversion_total}}</th>
          @endif
          <th style="font-size:12px; border-color: gray;text-align:center !important;">{{$diaria['importacion']->diferencia_cierre}}</th>
        </tr>
      </tbody>
    </table>
  @endforeach


</html>
