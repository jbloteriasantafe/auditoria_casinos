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
  <div class="encabezadoImg">
    <img src="img/logos/banner_2024_landscape.png" width="900">
  </div>
  <div class="camposTab titulo" style="top: 22px; left: 22px;">Canon ({{ucwords_espacios($tipo)}})</div>
  <div class="camposTab titulo" style="top: -15px; right:-15px;">FECHA PLANILLA</div>
  <div class="camposInfo" style="top: 0px; right:0px;"></span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></div>
  
  <p>El presente informe se emite a ráis del pago del CANON del mes de {{$mes ?? '--MES--'}} de {{$año ?? '--AÑO--'}}, efectuado por el consecionario Casino de {{$casino ?? '--CASINO--'}}, el cual vencio el {{$dia_semanal_vencimiento ?? '--DIA-VENCIMIENTO--'}} {{$fecha_vencimiento ?? '--FECHA-VENCIMIENTO--'}}</p>
</body>

</html>
