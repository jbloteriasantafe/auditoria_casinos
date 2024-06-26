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
    padding: 1px;
  }

  tr:nth-child(even) {
    background-color: #dddddd;
  }

  p {
        border-top: 1px solid #000;
  }

  footer
  {
      margin-top:50px;
      width:200%;
      height:300px;
  }
  #tablaDias thead th {
    font-size:14px;
    background-color: #dddddd;
    border-color: gray;
    text-align:center !important
  }
  #tablaDias tbody tr td {
    font-size: 11px;
    text-align: right;
  }
  #tablaDias tbody tr:last-child td {
    font-size: 11px;
    font-weight: bold;
    text-align: right;
  }
</style>
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
    $fecha_planilla = date('j-m-y / h:i');
    $fmt = function($s,$digits = 2){
      if(!is_numeric($s)) return $s;
      return number_format($s,$digits,",",".");
    }
    ?>
    <div class="encabezadoImg">
      <img src="img/logos/banner_2024_landscape.png" width="900">
      <br>
      <h2><span>RMES02 | Informe mensual por casinos de MESAS DE PAÑO.</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;">{{$fecha_planilla}}</div>
    <h4 style="top:-10px;bottom:-30px!important;padding-top:-40px !important;text-align: center;">
      Resultados del mes {{$mes}} para {{$casino->nombre}}
    </h4>

    <table style="border-collapse: collapse;" id="tablaDias">
      <thead>
        <tr align="center" >
          <th class="col-xl-2 tablaInicio" rowspan=2>FECHA</th>
          @foreach($monedas as $m)
          <?php $cotizar = $m->id_moneda != 1; ?>
          <th class="tablaInicio" colspan={{$cotizar? 4 : 2}}>{{$m->siglas}}</th>
          @endforeach
          <th class="col-xl-2 tablaInicio" rowspan=2>TOTAL</th>
        </tr>
        <tr align="center">
          @foreach($monedas as $m)
          <?php $cotizar = $m->id_moneda != 1; ?>
          <th class="tablaInicio">MESAS</th>
          <th class="tablaInicio">UTILIDAD</th>
          @if($cotizar)
          <th class="tablaInicio">COTIZACION</th>
          <th class="tablaInicio">UTILIDAD COTIZADA</th>
          @endif
          @endforeach
        </tr>
      </thead>
      <tbody>
        <?php
          $val = function($d,$moneda,$k,$dflt='--'){
            return ($d[$moneda->siglas] ?? [])[$k] ?? $dflt;
          }
        ?>
        @foreach($datos as $fecha => $d)
        <tr>
          <td class="tablaCampos" style="text-align: center">{{$fecha}}</td>
          <?php $utilidad_fila = 0 ?>
          @foreach($monedas as $m)
          <?php 
            $cotizar = $m->id_moneda != 1;
            $utilidad_fila += $val($d,$m,$cotizar? 'conversion_total' : 'utilidad',0);
           ?>
          <td class="tablaCampos" style="text-align: center">{{$val($d,$m,'mesas')}}</td>
          <td class="tablaCampos">{{$fmt($val($d,$m,'utilidad'))}}</td>
          @if($cotizar)
          <td class="tablaCampos">{{$fmt($val($d,$m,'cotizacion_diaria'))}}</td>
          <td class="tablaCampos">{{$fmt($val($d,$m,'conversion_total'))}}</td>
          @endif
          @endforeach
          <td class="tablaCampos">{{$fmt($utilidad_fila)}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </body>
</html>
