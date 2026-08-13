<!DOCTYPE html>

<html>

<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  table-layout: fixed;
  word-wrap: break-word !important;
  font-size: 0.53em !important;
}

th {
  background-color: #dddddd;
}

td {
  padding-right: 0.5em;
  border-width: 0.99px; /* @HACK para que Dompdf tome el override del color */
  border-style: solid;
  border-color: rgb(230,230,230);
}
td.sin-valor {  
  background-color: rgb(230, 230, 230) !important;
  text-align: center !important;
  padding-right: 0;
}
td.bleft , th.bleft {
  border-left-color: rgb(180,180,180) !important;
  border-left-width: 2px;
}
td.bright , th.bright {
  border-right-color: rgb(180,180,180) !important;
  border-right-width: 2px;
}
td.btop , th.btop {
  border-top-color: rgb(180,180,180) !important;
  border-top-width: 2px;
}
td.bbottom , th.bbottom {
  border-bottom-color: rgb(180,180,180) !important;
  border-bottom-width: 2px;
}
</style>

<?php 
$columnas = count($grupos_operadores);
$D = function($n){
  return bc_formatear_decimal($n);
};
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
      <th class="tablaInicio sin" style="text-align: center;" colspan="{{intval(ceil(($columnas+1)/2.0))}}">Mes</th>
      <th class="tablaInicio" style="text-align: left;" colspan="{{intval(floor(($columnas+1)/2.0))}}">{{$mes}}</th>
    </tr>
    <tr>
      <th class="tablaInicio btop bright" style="text-align: center;">OPERADOR</th>
      @foreach($grupos_operadores as $gop)
      <?php $bleft = $gop == 'Total'? 'bleft' : ''; ?>
      <th class="tablaInicio btop {{$bleft}}" style="text-align: center;">{{$gop}}</th>
      @endforeach
    </tr>
    @foreach($conceptos as $concepto)
    <tr>
      <?php $tot = $concepto == 'Total' || $concepto == 'Total Físico'; ?>
      <?php $btop = $tot? 'btop' : ''; ?>
      <?php $bbottom = $concepto == 'Total Físico' ? 'bbottom' : ''; ?>
      <td class="tablaCampos {{$btop}} {{$bbottom}} bright" style="text-align: center;"><b>{{$concepto}}</b></td>
      @foreach($datos as $gop => $datos_concepto)
      <?php 
        $v = $datos_concepto[$concepto][$t]['pos_red'];
        $r = $datos_concepto[$concepto][$t]['err_red'];
        $sin_valor = $v === null? 'sin-valor' : '';
        $bleft = $gop == 'Total'? 'bleft' : ''; 
        $v = $v !== null? $D($v) : '—';
        $r = 
          (($gop == 'Total' || $tot) && bccomp_precise($r ?? '0','0') != 0)? 
          ('('.$D(bcmul_precise($r,100)).'¢)')
        : '';
      ?>
      <td class="tablaCampos {{$sin_valor}} {{$bleft}} {{$btop}} {{$bbottom}}" style="text-align: right;">
        @if(empty($sin_valor))
        <div style="float: left;width: 30%;text-align: left;color: #646363;">{{$r}}</div>
        <div style="float: right;width: 80%;text-align: right;">{{$v}}</div>
        <div style="clear: both;"></div>
        @else
        {{$v}}
        @endif
      </td>
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

