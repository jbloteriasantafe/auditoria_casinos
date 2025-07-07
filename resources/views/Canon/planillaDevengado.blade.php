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
$columnas = count($datos);
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
  @if($tipo_presupuesto == 'devengado')
  <p style="text-align: center;"><b>VALORES DEVENGADOS ESTIMADOS CON CRITERIO CONSERVADOR POR DIFERENCIAS QUE PUDIERAN DARSE</b></p>
  @elseif($tipo_presupuesto == 'determinado')
  <p style="text-align: center;"><b>VALORES DETERMINADOS</b></p>
  @endif
  @foreach($tablas as $t)
  <h5>{{ucwords($t)}}</h5>
  <table style="width: 100%;">
    <tr>
      <th class="tablaInicio" style="text-align: center;" colspan="{{intval(ceil(($columnas+1)/2.0))}}">Mes</th>
      <th class="tablaInicio" style="text-align: center;" colspan="{{intval(floor(($columnas+1)/2.0))}}">{{$mes}}</th>
    </tr>
    <tr>
      <th class="tablaInicio" style="text-align: center;">CONCEPTO</th>
      @foreach($datos as $cas => $_)
      <th class="tablaInicio" style="text-align: center;">{{$cas == 'Total'? '' : 'Casino '}}{{$cas}}</th>
      @endforeach
    </tr>
    @foreach($conceptos as $concepto)
    <tr>
      <td class="tablaCampos" style="text-align: center;">{{$concepto}}</td>
      @foreach($datos as $cas => $datos_concepto)
      <td class="tablaCampos" style="text-align: center;">{{$datos_concepto[$concepto][$t] ?? ''}}</td>
      @endforeach
    </tr>
    @endforeach
  </table>
  <br>
  @if($tipo_presupuesto == 'devengado' && $t == '')
  <p style="text-align: left;font-style: italic;font-size: 0.70em;">La estimación elevada esta sujeta a ajustes que pudiesen corresponder al momento de producirse el ingreso real del Canon correspondiente al numeral 5.1 de los CP 9199, CP 9200, CP 9201, la Ley 14235 y el Dcto 562/24.</p>
  <br>
  @endif
  @endforeach
</body>

</html>
