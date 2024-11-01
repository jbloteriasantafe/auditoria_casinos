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
  <p style="text-align: center;"><b>VALORES DEVENGADOS ESTIMADOS CON CRITERIO CONSERVADOR POR DIFERENCIAS QUE PUDIERAN DARSE</b></p>
  <table style="width: 100%;">
    <tr>
      <th class="tablaInicio" style="text-align: center;" colspan="{{intval(ceil(($columnas+1)/2.0))}}">Mes</th>
      <th class="tablaInicio" style="text-align: center;" colspan="{{intval(floor(($columnas+1)/2.0))}}">{{$mes}}</th>
    </tr>
    <tr>
      <th class="tablaInicio" style="text-align: center;">CONCEPTO</th>
      @foreach($datos as $casino => $_)
      <th class="tablaInicio" style="text-align: center;">{{$casino == 'Total'? '' : 'Casino '}}{{$casino}}</th>
      @endforeach
    </tr>
    @foreach($conceptos as $concepto)
    <tr>
      <td class="tablaCampos" style="text-align: center;">{{$concepto}}</td>
      @foreach($datos as $casino => $datos_concepto)
      <td class="tablaCampos" style="text-align: center;">{{$datos_concepto[$concepto] ?? ''}}</td>
      @endforeach
    </tr>
    @endforeach
  </table>
</body>

</html>
