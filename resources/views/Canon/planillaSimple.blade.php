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
  font-size: 0.45em !important;
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
  <table class="borde_cada_dos_columnas" style="width: 100%;">
    <?php
      $maxrows = 0;
      foreach($datos as $tabla => $datos_tabla){
        foreach($datos_tabla as $tipo => $datos_tipo){
          $maxrows = max($maxrows,count($datos_tipo));
        }
      }
      $maxcols = count($datos);    
      $header_width = 100/$maxcols;
      $cell_width = 100/$maxcols/2;
    ?>
    <thead>
      <tr>
        @foreach($datos as $tabla => $_)
        <th colspan="2" class="tablaInicio" style="text-align: center;" width="{{$header_width}}%">{{ucwords_espacios($tabla)}}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @for($row=0;$row<$maxrows;$row++)
      <tr>
        @foreach($datos as $tabla => $datos_tabla)
                   
          <?php $r = 0; ?>
          @foreach($datos_tabla as $tipo => $datos_tipo)
          @foreach($datos_tipo as $k => $v)
          @if($r == $row)
          <th class="tablaInicio" style="text-align: left;" width="{{$cell_width}}%">{{ucwords_espacios($k)}}</th>
          <td class="tablaCampos" style="text-align: right;" width="{{$cell_width}}%">{{$v}}</td>
          <?php $r = -1 ?>
          @break(2)
          @endif
          <?php $r++; ?>
          @endforeach
          @endforeach
          
          @if($r != -1)
          <th class="tablaInicio" style="text-align: left;" width="{{$cell_width}}%">&nbsp;</th>
          <td class="tablaCampos" style="text-align: right;" width="{{$cell_width}}%">&nbsp;</td>
          @endif
          
        @endforeach
      </tr>
      @endfor
    </tbody>
  </table>
</body>
</html>
