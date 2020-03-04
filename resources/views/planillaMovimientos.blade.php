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
  .filaContadores{
    height: 10px !important;
  }
  footer
  {
      margin-top:50px;
      width:200%;
      height:300px;
  }
  .break{
      word-wrap: break-word;
  }
  .cabezera{
    background-color: #dddddd; 
    border-color: gray;
  }
  .fila{
    background-color: #fff;
    border-color: gray;
  }
  .cell_fg{
    position:absolute; 
    width:100%; 
    height:100%; 
    z-index:1;
  }

  .cell_bg_1{
    position:relative; 
    z-index:0; 
    color: rgb(120,120,120);
    font-size: 50%;
    top: 10px;
    text-align: right;
  }
  .cell_bg_2{
    position:absolute; 
    width:100%; 
    height:100%; 
    z-index:0; 
    color: rgb(180,180,180);
    text-align:right;
    font-size: 60%;
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
    @foreach ($relevamientos as $idx_rel => $relevamiento)
    @foreach ($relevamiento->tomas as $idx_toma => $toma)
    @if ($idx_rel > 0 || $idx_toma > 0)
    <div style="page-break-after:always;"></div>
    @endif
    <div class="encabezadoImg">
      <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
      <h2><span>RMTM 01-02 | Control de {{$tipo_planilla}} de MTMs.</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><?php $hoy = date('j-m-y / h:i');print_r($hoy); ?></div>
    <table>
      <th class="tablaInicio cabezera" style="width: 20%;">TIPO DE MOVIMIENTO</th>
      <td class="tablaInicio cabezera" style="padding-left:36px;">{{$relevamiento->tipo_movimiento}}</td>
      @if(isset($relevamiento->sentido))
      <th class="tablaInicio cabezera" style="width: 10%">SENTIDO</th>
      <td class="tablaInicio cabezera" style="padding-left:36px;">{{$relevamiento->sentido}}</td>
      @endif
    </table>
    <br>
    <table>
      <tr>
        <th class="tablaInicio cabezera" style="width: 10%;">N° ADMIN</th>
        <td class="tablaInicio cabezera" style="padding-left:36px;">{{$relevamiento->nro_admin}}</td>
        <th class="tablaInicio cabezera" style="width: 10%;">N° ISLA</th>
        <td class="tablaInicio cabezera" style="padding-left:36px;">{{$relevamiento->nro_isla}}</td>
        <th class="tablaInicio cabezera" style="width: 10%;">N° SERIE</th>
        <td class="tablaInicio cabezera" style="padding-left:36px;"> {{$relevamiento->nro_serie}}</td>
      </tr>
    </table>
    <table>
      <tr>
        <th class="tablaInicio cabezera" style="width: 10%;">MARCA</th>
        <td class="tablaInicio cabezera" style="padding-left:36px;">{{$relevamiento->marca}}</td>
        <th class="tablaInicio cabezera" style="width: 10%;">MODELO</th>
        <td class="tablaInicio cabezera" style="padding-left:36px;">{{$relevamiento->modelo}}</td>
      </tr>
    </table>
    <br>
    <table>
      <tr>
        <th class="tablaInicio cabezera" style="width: 15%;">FECHA Y HORA</th>
        <td class="tablaInicio fila" style="padding-left: 36px;">
        @if($toma->fecha_relev_sala != null)
        {{$toma->fecha_relev_sala}}
        @else
        ____/____/____, ____:____
        @endif
        </td>
      </tr>
    </table>
    <table>
      <tr>
        <th class="tablaInicio cabezera" style="width: 10%;">SECTOR</th>
        <td class="tablaInicio fila" style="padding-left: 36px;">
        @if($toma->descripcion_sector_relevado!=null)
          {{$toma->descripcion_sector_relevado}}
        @endif
        </td>
        <th class="tablaInicio cabezera" style="width: 10%;">ISLA</th>
        <td class="tablaInicio fila" style="padding-left: 36px;">
        @if($toma->nro_isla_relevada!=null)
          {{$toma->nro_isla_relevada}}
        @endif
        </td>
      </tr>
    </table>
    <br>
    <table>
      <!-- Tabla COIN IN -->
      <tr>
        <th class="tablaInicio cabezera" style="width: 30%;">CONTADORES</th>
        <th class="tablaInicio cabezera">TOMA</th>
      </tr>
      @for($i=1;$i<=6;$i++)
      <tr class="filaContadores">
        <td class="tablaInicio fila">
          <div class="break">
            <b>
            @if(isset($relevamiento->nom_conts[$i])) {{$relevamiento->nom_conts[$i]}} @endif
            </b>
          </div>
        </td>
        <td class="tablaInicio fila">@if(isset($toma->conts[$i])) {{$toma->conts[$i]}} @endif</td>
      </tr>
      @endfor
    </table>
    <br>
    <table>
      <tr>
        <th class="tablaInicio cabezera" style="border-right: 0px;">TOMA</th>
        <th class="tablaInicio cabezera" style="border-left: 0px;"></th>
      </tr>
      <tr>
        <td class="tablaInicio fila" style="width: 30%;"><b>JUEGO</b></td>
        <td class="tablaInicio fila" style="padding-right: 150px;">{{$toma->juego}}</td>
      </tr>
      <tr>
        <td class="tablaInicio fila" style="width: 30%;"><b>APUESTA MÁX</b></td>
        <td class="tablaInicio fila">{{$toma->apuesta_max}}</td>
      </tr>
      <tr>
        <td class="tablaInicio fila" style="width: 30%;"><b>CANT LÍNEAS</b></td>
        <td class="tablaInicio fila">{{$toma->cant_lineas}}</td>
      </tr>
      <tr>
        <td class="tablaInicio fila" style="width: 30%;"><b>% DEVOLUCIÓN</b></td>
        <td class="tablaInicio fila">{{$toma->porcentaje_devolucion}}</td>
      </tr>
      <tr>
        <td class="tablaInicio fila" style="width: 30%;"><b>DENOMINACIÓN</b></td>
        <td class="tablaInicio fila">{{$toma->denominacion}}</td>
      </tr>
      <tr>
        <td class="tablaInicio fila" style="width: 30%;"><b>CANT CRÉDITOS</b></td>
        <td class="tablaInicio fila">{{$toma->cant_creditos}}</td>
      </tr>
      <tr>
        <td class="tablaInicio fila" style="width: 30%;"><b>MAC</b></td>
        <td class="tablaInicio fila">{{$toma->mac}}</td>
      </tr>
    </table>
    <br>
    @if(count($toma->progresivos) > 0)
    <table style="table-layout:fixed;">
      <thead>
        <tr>
          <th class="tablaInicio cabezera" style="font-size: 60%;" width="10.5%">PROGRESIVO</th>
          @for($i=1;$i<=$toma->max_lvl_prog;$i++)
          <th class="tablaInicio cabezera" style="font-size: 60%;">NIVEL {{$i}}</th>
          @endfor
          <th class="tablaInicio cabezera" style="font-size: 60%;" width="10.5%">CAUSA NO TOMA</th>
        </tr>
      </thead>
      <tbody>
        @foreach($toma->progresivos as $p)
        <tr>
          @if($p->es_individual)
            <td class="tablaProgresivos fila" style="font-size: 60%;"><div class="break">INDIVIDUAL</div></td>
          @else
            <td class="tablaProgresivos fila" style="font-size: 60%;"><div class="break">{{$p->progresivo}}{{$p->pozo_unico? ' ' : ' ('.$p->pozo.')'}}</div></td>
          @endif
          @for($i=1;$i<=$toma->max_lvl_prog;$i++)
            @if(array_key_exists($i,$p->niveles) && empty($p->tipo_causa_no_toma_progresivo))
              <td class="tablaProgresivos fila" style="font-size: 60%;">
                <div class="cell_fg break">
                {{empty($p->tipo_causa_no_toma_progresivo)? $p->valores_niveles[$i] : '-'}}
                </div>
                <div class="cell_bg_1 break">
                {{$p->es_individual? '' : $p->niveles[$i]}}
                </div>
              </td>
            @elseif(!empty($p->tipo_causa_no_toma_progresivo))
              <td class="tablaProgresivos fila" style="text-align: center;">—</td>
            @else
              <td class="tablaProgresivos cabezera"></td>
            @endif
          @endfor
          <td class="tablaProgresivos fila" style="font-size: 60%;">
            <div class="break">
            {{$p->tipo_causa_no_toma_progresivo}}
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <br>
    @endif
    <table>
      <tr>
        <th class="tablaInicio cabezera">OBSERVACIONES GENERALES</th>
      </tr>
      <tr>
        <td class="tablaInicio fila" style="height:auto; ">
        @if(count($relevamiento->tomas) > 1)
          <i>TOMA {{$idx_toma + 1}}:</i>
          <br>
        @endif
        @if($toma->observ != null)
          {{$toma->observ}}
        @else
          <div style="color: #dddddd;">
          @for($i = 0; $i<648; $i++)
          .
          @endfor
          </div>
        @endif
        </td>
      </tr>
    </table>
    <br>
    <table>
      <tr>
        <th class="tablaInicio" style="padding-top: 50px; border-right: 0px; border-color: gray;"></th>
        <th class="tablaInicio" style="border-left: 0px; border-color: gray;"></th>
        <th class="tablaInicio" style="border-right: 0px; border-color: gray;"></th>
        <th class="tablaInicio" style="border-left: 0px; border-color: gray;"></th>
      </tr>
      <tr>
        <td class="tablaInicio cabezera"><center>Personal del Concesionario en Sala de Juegos</center></td>
        <td class="tablaInicio cabezera"><center>Fiscalizador en Sala de Juegos</center></td>
        <td class="tablaInicio cabezera"><center>Personal del Concesionario en Sala de Juegos</center></td>
        <td class="tablaInicio cabezera"><center>Fiscalizador en Sala de Juegos</center></td>
      </tr>
    </table>
    @endforeach
    @endforeach
  </body>
</html>