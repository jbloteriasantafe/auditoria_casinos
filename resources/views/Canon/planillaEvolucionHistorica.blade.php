<!DOCTYPE html>

<html>

<style>
* {
  padding: 0;margin: 0;
}
table {
  font-family: mono;
  border-collapse: collapse;
  table-layout: fixed;
  word-wrap: break-word !important;
  font-size: 0.75em;
}

th {
  text-align: center;
  text-shadow: white 1px 1px 4px;
}
td {
  text-align: right;
}

td, th {
  border: 1px solid #dddddd;
}

tr:nth-last-child(1) td,
tr:nth-last-child(1) th {
  border-bottom: 1px solid black;
}

tr:nth-last-child(2) td,
tr:nth-last-child(2) th {
  border-bottom: 1px solid black;
}

tr th:nth-last-child(1),
tr td:nth-last-child(1) {
  border-right: 1px solid black;
}

th.santa_fe {
  background: #EA4335;
}
th.melincué {
  background: #34A853;
}
th.rosario {
  background: #FBBC04;
}

thead th {
  border-bottom: 1px solid black;
}

.negativo {
  color: red;
  font-weight: bold;
}
</style>

<head>
  <meta charset="utf-8">
  <title></title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <?php
    $meses = [null,'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    unset($meses[0]);
    $snakecase = function($s){
      return trim(strtolower(preg_replace('/\s/','_',$s)));
    }
  ?>
  
  @foreach($datos as $año => $datos_año)
  @if(!$loop->first)
  <br>
  @endif
  <table style="width: 100%;">
    <thead>
      <tr>
        <th style="width: 10%;border: 1px solid black;">{{$año}}</th>
        @foreach($casinos as $cas)
        <th class="{{$snakecase($cas)}}" colspan="5" style="width: 30%;border-top: 1px solid black;border-right: 1px solid black;border-bottom: 1px solid black;">{{$cas}}</th>
        @endforeach
      </tr>
      
      <tr>
        <th style="border: 1px solid black;">Meses</th>
        @foreach($casinos as $cas)
        <?php $scas = $snakecase($cas); ?>
        <th class="{{$scas}}">Devengado</th>
        <th class="{{$scas}}">Var. Devengado</th>
        <th class="{{$scas}}">Canon</th>
        <th class="{{$scas}}">Diferencia</th>
        <th class="{{$scas}}" style="border-right: 1px solid black;">Var. Devengado Sobre Canon</th>
        @endforeach
      </tr>
    </thead>
    
    <tbody>
      <?php 
        $valor_vacio = '-';
        $negativo = function($attr){return ($attr[0] ?? null) == '-'? 'negativo' : '';};
        $formatear_porcentaje = function($attr) use ($valor_vacio){return $attr === null? $valor_vacio : ($attr.'%');};
      ?>
      @foreach($datos_año as $mes => $datos_año_mes)
      <tr>
        <th style="border-left: 1px solid black;border-right: 1px solid black;">{{$meses[$mes] ?? $mes}}</th>
        @foreach($casinos as $cas)
        <?php $d = $datos_año_mes[$cas] ?? (new \stdClass()); ?>
        <td>{{ $d->devengado ?? $valor_vacio }}</td>
        <td class="{{$negativo($d->variacion_devengado ?? null)}}">{{$formatear_porcentaje($d->variacion_devengado ?? null)}}</td>
        <td>{{ $d->canon ?? $valor_vacio }}</td>
        <td>{{ $d->diferencia ?? $valor_vacio }}</td>
        <td class="{{$negativo($d->variacion_sobre_devengado ?? null)}}" style="border-right: 1px solid black;">{{$formatear_porcentaje($d->variacion_sobre_devengado ?? null)}}</td>
        @endforeach
      </tr>
      @endforeach
      
      <tr class="total">
        <th style="border-left: 1px solid black;border-right: 1px solid black;">TOTAL</th>
        @foreach($casinos as $cas)
        <?php $d = $datos_anuales[$año][$cas] ?? (new \stdClass()); ?>
        <td>{{ $d->devengado ?? $valor_vacio }}</td>
        <td class="{{$negativo($d->variacion_devengado ?? null)}}">{{$formatear_porcentaje($d->variacion_devengado ?? null)}}</td>
        <td>{{ $d->canon ?? $valor_vacio }}</td>
        <td>{{ $d->diferencia ?? $valor_vacio }}</td>
        <td class="{{$negativo($d->variacion_sobre_devengado ?? null)}}" style="border-right: 1px solid black;">{{$formatear_porcentaje($d->variacion_sobre_devengado ?? null)}}</td>
        @endforeach
      </tr>
    </tbody>
  </table>
  @endforeach
</body>

</html>
