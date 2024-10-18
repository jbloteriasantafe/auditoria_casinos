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
  padding: 1px;
  font-size: 0.5em !important;
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
  <div class="camposTab titulo" style="top: 22px; left: 22px;">REPORTE DE CANON</div>
  <div class="camposTab titulo" style="top: -15px; right:-15px;">FECHA PLANILLA</div>
  <div class="camposInfo" style="top: 0px; right:0px;"></span><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></div>
  <?php
  $max_col_size = 0;
  foreach($datos as $datos_tabla){
    $max_col_size = max($max_col_size,count($datos_tabla[0] ?? []));
  }
  $main_col_width = min(100/count($datos),25);
  $table_width = $main_col_width*count($datos);
  ?>
  
  @if(count($datos) > 0)
  <table style="width: {{$table_width}}%;">
    <?php
      $maxrows = 0;
    ?>
    <tr>
      @foreach($datos as $titulo_tabla => $datos_tabla)
      <?php
        foreach($datos_tabla as $d){
          $maxrows = max($maxrows,count(array_keys($d)));
        }
      ?>
      <th colspan="{{1+count($datos_tabla)}}" class="tablaInicio" style="text-align: center;" width="{{$main_col_width}}%">{{ucwords_espacios($titulo_tabla)}}</th>
      @endforeach
    </tr>
    
    @for($kidx=0;$kidx<$maxrows;$kidx++)
    <tr>
      @foreach($datos as $titulo_tabla => $datos_tabla)
      <?php 
        $k = array_keys($datos_tabla[0] ?? [])[$kidx] ?? null ;
        $col_width = $main_col_width/(1+count($datos_tabla));
      ?>
      
      <th class="tablaInicio" style="text-align: left;" width="{{$col_width}}%">{{$k !== null? ucwords_espacios($k) : '&nbsp;'}}</th>
      @foreach($datos_tabla as $didx => $d)
      <td class="tablaCampos" style="text-align: right;" width="{{$col_width}}%">{{$k !== null? $d[$k] : '&nbsp;'}}</td>
      @endforeach
      
      @endforeach
    </tr>
    @endfor
  </table>
  @endif
  
</body>

</html>
