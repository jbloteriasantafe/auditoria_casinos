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

    p {
      border-top: 1px solid #000;
    }
    .break{
      word-wrap: break-word;
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
    <div class="encabezadoImg">
        <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
        <h2><span>PVAR03 | Procedimiento de Control Ambiental</span></h2>
    </div>

    <div class="camposTab titulo" style="right:250px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:261px;"></span><?php print_r(date('j-m-y / h:i')); ?></div>

    <!-- Tabla de datos del relevamiento de control ambiental -->
    <table>
      <tr>
        <th class="tablaInicio" style="background-color: #e6e6e6">CASINO</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">N° RELEVAMIENTO</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">FECHA PRODUCCIÓN</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">FECHA AUDITORÍA</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">FISCALIZADOR</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">ESTADO</th>
      </tr>

      <tr>
        <td class="tablaInicio" style="background-color: white">{{$otros_datos['casino']}}</td>
        <td class="tablaInicio" style="background-color: white">{{$relevamiento_ambiental->nro_relevamiento_ambiental}}</td>
        <td class="tablaInicio" style="background-color: white">{{$relevamiento_ambiental->fecha_generacion}}</td>
        <td class="tablaInicio" style="background-color: white">{{$relevamiento_ambiental->fecha_ejecucion}}</td>
        <td class="tablaInicio" style="background-color: white">{{$otros_datos['fiscalizador']}}</td>
        <td class="tablaInicio" style="background-color: white">{{$otros_datos['estado']}}</td>
      </tr>
    </table>

    <br>

    <?php 
    $turnos_size = sizeof($relevamiento_ambiental->casino->turnos);
    function clearnull($val,$default = ''){
      return is_null($val)? $default : $val;
    }
    function clearzero($val,$default = ''){
      return $val == 0? $default : $val;
    }
    ?>

    <table>
      <thead>
        <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" rowspan="2" width="120px">GENERALIDADES</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" colspan="{{sizeof($relevamiento_ambiental->casino->turnos)}}">TURNOS</th>
        </tr>
        <tr>
          @foreach ($relevamiento_ambiental->casino->turnos as $turno)
          <th class="tablaInicio" style="background-color: #e6e6e6">{{$turno->nro_turno}}</th>
          @endforeach
        </tr>
      </thead>
      <tbody style="line-height: 150%;">
        @foreach ($generalidades as $g)
        <tr>
          <td class="tablaInicio" style="background-color: #e6e6e6" width="120px"><b>{{$g['tipo_generalidad']}}</b></td>
          @for($i=1;$i<=$turnos_size;$i++)
          <td class="tablaAmbiental" style="background-color: white">{{clearnull($g['turno'.$i])}}</td>
          @endfor
        </tr>
        @endforeach
      </tbody>
    </table>

    <br>
    <table style="table-layout:fixed;">
        <thead><tr>
          <th class="tablaInicio" style="background-color: #e6e6e6">OBSERVACIONES GENERALES</th>
        </tr></thead>
        <tbody><tr>
          <td class="tablaAmbiental" style="background-color: #white">
            <div class="break" style="line-height: 300%;">
            {{clearnull($relevamiento_ambiental->observacion_carga,str_repeat('.',624))}}
            </div>
          </td>
        </tr></tbody>
    </table>

    @if ($relevamiento_ambiental->observacion_validacion != NULL)
      <br>
      <table style="table-layout:fixed;">
        <thead><tr>
          <th class="tablaInicio" style="background-color: #e6e6e6">OBSERVACIONES VALIDACIÓN</th>
        </tr></thead>
        <tbody><tr>
          <td class="tablaAmbiental" style="background-color: #white">
            <div class="break">
            {{$relevamiento_ambiental->observacion_validacion}}
            </div>
          </td>
        </tr></tbody>
      </table>
    @endif

    <?php
    $id_casino = $relevamiento_ambiental->casino->id_casino;
    $nombre_columna = $id_casino == 3? 'ISLOTES' : 'ISLAS';
    $atributo = $id_casino == 3? 'nro_islote' : 'nro_isla';

    $nombres_sectores = [];
    $det_x_sectores = [];
    foreach($relevamiento_ambiental->casino->sectores as $sector){
      $nombres_sectores[$sector->id_sector] = $sector->descripcion;
      $det_x_sectores[$sector->id_sector] = [];
    }
    foreach($detalles as $detalle){
      $det_x_sectores[$detalle['id_sector']][] = $detalle;
    }

    $cols_x_pag = 4;
    $ancho_tabla = (100.0/$cols_x_pag);
    $filas_por_col = 24.0;
    $cols_x_sector = [];
    foreach($nombres_sectores as $id_sector => $nsector){
      $cols_x_sector[$id_sector] = ceil(count($det_x_sectores[$id_sector]) / $filas_por_col);
    }
    $posicion = [
     0 =>  'position: absolute;top: 10px;left: -3%;',
     1 =>  'position: absolute;top: 10px;left: 23%;',
     2 =>  'position: absolute;top: 10px;left: 49%;',
     3 =>  'position: absolute;top: 10px;left: 75%;'
    ];
    ?>

    @foreach($nombres_sectores as $id_sector => $nsector)
    <?php 
      $total_turno = [];
      for($i=1;$i<=$turnos_size;$i++) $total_turno[$i] = 0;
    ?>
    <div style="page-break-after:always;"></div>
    <div class="primerEncabezado" style="font-size: 103%;">Sector de control ambiental: {{$nsector}}</div>
    @for($col=0;$col<$cols_x_sector[$id_sector];$col++)
    @if ($col%$cols_x_pag == 0 && $col != 0)
    <div style="page-break-after:always;"></div>
    @endif
    <table style="table-layout:fixed;width: {{$ancho_tabla}}%;{{$posicion[$col%$cols_x_pag]}}">
      <thead>
        <tr>
          <th class="tablaInicio" style="background-color: #e6e6e6;font-size: 0.5em;text-align: center;" rowspan="2" width="5%">{{$nombre_columna}}</th>
          <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" colspan="{{$turnos_size}}">TURNOS</th>
        </tr>
        <tr>
        @foreach ($relevamiento_ambiental->casino->turnos as $turno)
          <th class="tablaInicio" style="background-color: #e6e6e6" width="5%">{{$turno->nro_turno}}</th>
        @endforeach
        </tr>
      </thead>
      <tbody>
        @for($j=$col*$filas_por_col;$j<min(($col+1)*$filas_por_col,count($det_x_sectores[$id_sector]));$j++)
        <?php $detalle = $det_x_sectores[$id_sector][$j];?>
        <tr>
          <td class="tablaAmbiental" style="background-color: white;border-right: 1px solid grey;border-left: 1px solid grey;">{{$detalle[$atributo]}} </td>
          @for($i=1;$i<=$turnos_size;$i++)
            <?php $total_turno[$i] += $detalle['turno'.$i];?>
            <td class="tablaAmbiental" style="background-color: white">{{clearnull($detalle['turno'.$i])}}</td>
          @endfor
        </tr>
        @endfor
        <tr>
          @if(($col+1) == $cols_x_sector[$id_sector])
            <td class="tablaAmbiental" style="background-color: #e6e6e6;border-top: 1.5px solid grey;font-size: 0.5em;text-align: center;"><b>TOTAL<b></td>
            @for($i=1;$i<=$turnos_size;$i++)
            <td class="tablaAmbiental" style="background-color: white;border-top: 1.5px solid grey;">{{clearzero($total_turno[$i])}}</td>
            @endfor
          @else
            <td class="tablaAmbiental" style="background-color: #e6e6e6;border-top: 1.5px solid grey;"></td>
            @for($i=1;$i<=$turnos_size;$i++)
            <td class="tablaAmbiental" style="background-color: white;border-top: 1.5px solid grey;"></td>
            @endfor
          @endif
        </tr>
      </tbody>
    </table>
    @endfor
    @endforeach

    <div style="page-break-after:always;"></div>
    <table>
      <thead>
        <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="100px">TURNOS</th>
            @foreach ($relevamiento_ambiental->casino->turnos as $turno)
            <th class="tablaInicio" style="background-color: #e6e6e6">{{$turno->nro_turno}}</th>
            @endforeach
        </tr>
      </thead>
      <tbody>
        <tr style="line-height: 600%;">
          <td class="tablaInicio" style="background-color: #e6e6e6;text-align: center;padding-bottom: 30px;" width="100px"><b>Firma y sello</b></td>
          @for($i=1;$i<=$turnos_size;$i++)
          <td class="tablaAmbiental" style="background-color: white"></td>
          @endfor
        </tr>
      </tbody>
    </table>
  </body>
</html>
