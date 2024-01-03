<!DOCTYPE html>
<html>
  <style>
  table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
  }
  td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 3px;
  }
  .cell_fg{
    position:absolute; 
    z-index:1;
    text-align: right;
    word-wrap: break-word;
    width:100%; 
    height:100%; 
  }
  .cell_bg{
    position:relative; 
    z-index:0; 
    text-align: right;
    word-wrap: break-word;
    color: rgb(120,120,120);
    font-size: 50%;
    top: 10px;
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
    @section('encabezado')
    <div class="encabezadoImg">
        <img src="img/logos/banner_2024_landscape.png" width="900">
        <h2><span>RMTM06 | Procedimiento de Control de valores de Pozos Progresivos de MTM</span></h2>
    </div>
    <div class="camposTab titulo" style="right:250px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:261px;"></span><?php print_r(date('j-m-y / h:i')); ?></div>
    @endsection
    @yield('encabezado')

    <!-- Tabla de datos del relevamiento de progresivos -->
    <table>
      <thead>
        <tr>
          <th class="tablaInicio" style="background-color: #dddddd">CASINO</th>
          <th class="tablaInicio" style="background-color: #dddddd">SECTOR</th>
          <th class="tablaInicio" style="background-color: #dddddd">N° RELEV.</th>
          <th class="tablaInicio" style="background-color: #dddddd">FECHA PRODUCCIÓN</th>
          <th class="tablaInicio" style="background-color: #dddddd">FECHA AUDITORÍA</th>
          <th class="tablaInicio" style="background-color: #dddddd">FISCALIZADOR</th>
          <th class="tablaInicio" style="background-color: #dddddd">ESTADO</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="tablaInicio">{{$rel['casino']->nombre}}</td>
          <td class="tablaInicio">{{$rel['sector']->descripcion}}</td>
          <td class="tablaInicio">{{$rel['relevamiento']->nro_relevamiento_progresivo}}</td>
          <td class="tablaInicio">{{$rel['relevamiento']->fecha_generacion}}</td>
          <td class="tablaInicio">{{$rel['relevamiento']->fecha_ejecucion}}</td>
          <td class="tablaInicio">{{$rel['usuario_fiscalizador']? $rel['usuario_fiscalizador']->nombre : '' }}</td>
          <td class="tablaInicio">{{$rel['relevamiento']->estado_relevamiento->descripcion}}</td>
        </tr>
      </tbody>
    </table>
    <br>
    
    @foreach(['linkeados' => $rel['detalles_linkeados'],'individuales' => $rel['detalles_individuales']] as $tipo => $dets)
    
    @continue(count($dets)==0)
    
    @if(!$loop->first)
    <div style="page-break-after:always;"></div>
    @yield('encabezado')
    @endif
    
    <div class="primerEncabezado">Listado de progresivos {{$tipo}}:</div>
    <table style="table-layout:fixed">
      <thead>
        <tr>
          <th class="tablaInicio" style="background-color: #dddddd" width="7%">ISLA/S</th>
          @if($tipo == 'individuales')
          <th class="tablaInicio" style="background-color: #dddddd">MAQUINA</th>
          @endif
          <th class="tablaInicio" style="background-color: #dddddd" width="10.5%">PROGRESIVO</th>
          @for($i=1;$i<=$rel['MAX_LVL'];$i++)
          <th class="tablaInicio" style="background-color: #dddddd">NIVEL {{$i}}</th>
          @endfor
          <th class="tablaInicio" style="background-color: #dddddd;" width="10.5%">CAUSA NO TOMA</th>
        </tr>
      </thead>
      <tbody>
        @foreach($dets as $d)
        <tr>
          <td class="tablaProgresivos break">{{$d->nro_islas}}</td>
          
          @if($tipo == 'individuales')
          <td class="tablaProgresivos break">{{$d->nro_admins}}</td>
          @endif
          
          @if ($d->pozo_unico)
          <td class="tablaProgresivos break">{{$d->nombre_progresivo}}</td>
          @else
          <td class="tablaProgresivos break">{{$d->nombre_progresivo}} ( {{$d->descripcion_pozo}} )</td>
          @endif
          
          {{-- Asume que niveles esta ordenado por nro_nivel ASCENDENTE --}}
          @php
            $last_level = 0;
          @endphp
          
          @foreach($d->niveles as $idx => $n)   
            {{-- Si salto de 1 a 4 por ejemplo, hace una columna vacia para 2 y 3 --}}
            @for($i=$last_level+1;$i<$n->nro_nivel;$i++)
            <td class="tablaProgresivos" style="background-color: #f5f5f5"></td>            
            @endfor
            
            {{-- El nivel per-se --}}
            @if ($d->causa_no_toma_progresivo)
            <td class="tablaProgresivos" style="text-align: center;"> — </td>
            @else
            <td class="tablaProgresivos">
              <div class="cell_fg">{{ $n->valor }}</div>
              <div class="cell_bg">{{ $n->nombre_nivel }}</div>
            </td>
            @endif
            
            @php
              $last_level = $n->nro_nivel;
            @endphp
          @endforeach
          {{-- Completo los que falten despues del ultimo --}}
          @for($i=$last_level+1;$i<=$rel['MAX_LVL'];$i++)
          <td class="tablaProgresivos" style="background-color: #f5f5f5"></td>            
          @endfor
          
          <td class="tablaProgresivos break">{{$d->causa_no_toma_progresivo}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endforeach
    <br><br>
    @if ($rel['relevamiento']->observacion_carga)
    <div class="primerEncabezado">Observaciones de carga:</div>
    <div style="color: #9c9c9c;">{{$rel['relevamiento']->observacion_carga}}</div>
    <br><br>
    @endif
    @if ($rel['relevamiento']->observacion_validacion)
    <div class="primerEncabezado">Observaciones de validacion:</div>
    <div style="color: #9c9c9c;">{{$rel['relevamiento']->observacion_validacion}}</div>
    <br><br>
    @endif
    <br>
    <div class="primerEncabezado" style="padding-left: 720px;">
      <p style="width: 250px; padding-left: 60px;border-top: 1px solid #000;">Firma y aclaración/s responsable/s.</p>
    </div>
  </body>
</html>
