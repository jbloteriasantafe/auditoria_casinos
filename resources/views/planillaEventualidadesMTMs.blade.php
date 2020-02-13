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
  font-size: 70%;
}
</style>

  <head>
    <meta charset="utf-8">
    <title></title>

    <!-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> -->

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- <link href="css/bootstrap.min.css" rel="stylesheet"> -->

    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>

    @foreach ($rels as $relevamiento)
    @if ($relevamiento->nro != 0)
      <div style="page-break-after:always;"></div>
      @endif
        <div class="encabezadoImg">
              <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span>RMTM 01-02 | Control de intervenciones de MTMs.</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>
              <table>
              <tr>
                <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">TIPO DE MOVIMIENTO</th>
                <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">SENTIDO</th>
              </tr>
              <td class="tablaInicio"style="background-color: #dddddd; border-color: gray;">{{$relevamiento->tipo_movimiento}}</td>
              <td class="tablaInicio"style="background-color: #dddddd; border-color: gray;">{{$relevamiento->sentido}}</td>
              </table>
              <br>
              <table>
                <tr>
                  <th class="tablaInicio" style="width: 100px; background-color: #dddddd; border-color: gray;">N° ADMIN</th>
                  <td class="tablaInicio" style="width: 100px; padding-left: 36px; background-color: #dddddd; border-color: gray;">{{$relevamiento->nro_admin}}</td>
                  <th class="tablaInicio" style="width: 100px; background-color: #dddddd; border-color: gray;">N° ISLA</th>
                  <td class="tablaInicio" style="width: 100px; padding-left: 36px; background-color: #dddddd; border-color: gray;">{{$relevamiento->nro_isla}}</td>
                  <th class="tablaInicio" style="width: 100px;background-color: #dddddd; border-color: gray;">N° SERIE</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;"> {{$relevamiento->nro_serie}}</td>
                </tr>

              </table>
              <table>
                <tr>
                  <th class="tablaInicio" style="width: 100px; background-color: #dddddd; border-color: gray;">MARCA</th>
                  <td class="tablaInicio" style="width: 100px; padding-left: 36px; background-color: #dddddd; border-color: gray;">{{$relevamiento->marca}}</td>
                  <th class="tablaInicio" style="width: 100px;background-color: #dddddd; border-color: gray;">MODELO</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;">{{$relevamiento->modelo}}</td>
                </tr>

              </table>
              <br>
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">FECHA Y HORA TOMA</th>
                  <td class="tablaInicio"  style="border-color: gray;padding-left: 36px;">
                  @if($relevamiento->fecha_relev_sala != null) 
                  {{$relevamiento->fecha_relev_sala}} 
                  @else 
                  _____/_____/_____, _____:_____ 
                  @endif
                  </td>
                </tr>
              </table>
              <br>

              <table>
                <tr>
                  <th class="tablaInicio" style="width: 60px; border-color: gray; background-color: #dddddd;">SECTOR</th>
                  <td class="tablaInicio" style="padding-left: 36px; border-color: gray;">@if($relevamiento->toma1_descripcion_sector_relevado != null){{$relevamiento->toma1_descripcion_sector_relevado}} @endif</td>
                  <th class="tablaInicio" style="width: 50px; border-color: gray; background-color: #dddddd;">ISLA</th>
                  <td class="tablaInicio" style="padding-left: 36px; border-color: gray;">@if($relevamiento->toma1_nro_isla_relevada != null){{$relevamiento->toma1_nro_isla_relevada}} @endif</td>
                  <th class="tablaInicio" style="width: 30px; border-color: gray; background-color: #dddddd;">MAC</th>
                  <td class="tablaInicio" style="padding-left: 36px; border-color: gray;">@if($relevamiento->toma1_mac != null){{$relevamiento->toma1_mac}} @endif</td>
                </tr>

              </table>
              <br>
              <table>
                <!-- Tabla COIN IN -->
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">CONTADORES</th>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">TOMA </th>
                </tr>
                  <tr class="filaContadores">

                    <th class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->nom_cont1}}</th>
                    <td class="tablaInicio" style="background-color: #fff; padding-right: 150px; border-color: gray;">@if($relevamiento->toma1_cont1!=null){{$relevamiento->toma1_cont1}} @endif </td>
                  </tr>
                  <!-- FIN TABLA COIN IN -->
                  <!-- TABLA COIN OUT -->
                  <tr class="filaContadores">

                    <th class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->nom_cont2}}</th>
                    <td class="tablaInicio" style="background-color: #fff; border-color: gray;"> @if($relevamiento->toma1_cont2!=null){{$relevamiento->toma1_cont2}} @endif</td>
                  </tr>
                  <!-- TABLA ANEXA COIN OUT -->
                  <tr class="filaContadores">
                    <th class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->nom_cont3}}</th>
                    <td class="tablaInicio" style="background-color: #fff; border-color: gray;"> @if($relevamiento->toma1_cont3!=null){{$relevamiento->toma1_cont3}} @endif</td>
                  </tr>
                  <!-- FIN TABLA ANEXA COIN OUT-->
                  <!-- FIN TABLA COIN OUT -->
                  <!-- TABLA JACKPOT -->
                  <tr class="filaContadores">
                    <th class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->nom_cont4}}</th>
                    <td class="tablaInicio" style="background-color: #fff; border-color: gray;"> @if($relevamiento->toma1_cont4!=null){{$relevamiento->toma1_cont4}} @endif</td>
                  </tr>
                  <!-- TABLA ANEXA JACKPOTS -->
                  <tr class="filaContadores">
                    <th class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->nom_cont5}}</th>
                    <td class="tablaInicio" style="background-color: #fff; border-color: gray;"> @if($relevamiento->toma1_cont5!=null){{$relevamiento->toma1_cont5}} @endif </td>
                  </tr>
                  <tr class="filaContadores">
                    <th class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->nom_cont6}}</th>
                    <td class="tablaInicio" style="background-color: #fff; border-color: gray;"> @if($relevamiento->toma1_cont6!=null){{$relevamiento->toma1_cont6}} @endif </td>
                  </tr>
                </table>

              <br>
              <table>
                <tr>
                  <th class="tablaInicio" style="border-right: 0px; background-color: #dddddd; border-color: gray;">DATOS</th>
                  <th class="tablaInicio" style="border-left: 0px; background-color: #dddddd; border-color: gray;">TOMA</th>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>JUEGO</b></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px; border-color: gray;">@if($relevamiento->toma1_juego != null){{$relevamiento->toma1_juego}} @endif</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>APUESTA MÁX</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if($relevamiento->toma1_apuesta_max != null){{$relevamiento->toma1_apuesta_max}} @endif</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>CANT LÍNEAS</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if($relevamiento->toma1_cant_lineas != null){{$relevamiento->toma1_cant_lineas}} @endif</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>%DEVOLUCIÓN</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"> @if($relevamiento->toma1_porcentaje_devolucion != null){{$relevamiento->toma1_porcentaje_devolucion}} @endif</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>DENOMINACIÓN</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if($relevamiento->toma1_denominacion != null){{$relevamiento->toma1_denominacion}} @endif</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>CANT CRÉDITOS</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if($relevamiento->toma1_cant_creditos != null){{$relevamiento->toma1_cant_creditos}} @endif</td>

                </tr>

              </table>
              <br>

              @if(count($relevamiento->progresivos) > 0)
              <table style="table-layout:fixed;">
                <thead>
                  <tr>
                    <th class="tablaInicio cabezera" style="font-size: 60%;" width="10.5%">PROGRESIVO</th>
                    <th class="tablaInicio cabezera" style="font-size: 60%;">NIVEL 1</th>
                    <th class="tablaInicio cabezera" style="font-size: 60%;">NIVEL 2</th>
                    <th class="tablaInicio cabezera" style="font-size: 60%;">NIVEL 3</th>
                    <th class="tablaInicio cabezera" style="font-size: 60%;">NIVEL 4</th>
                    <th class="tablaInicio cabezera" style="font-size: 60%;">NIVEL 5</th>
                    <th class="tablaInicio cabezera" style="font-size: 60%;">NIVEL 6</th>
                    <th class="tablaInicio cabezera" style="font-size: 60%;" width="10.5%">CAUSA NO TOMA</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($relevamiento->progresivos as $p)
                  <tr>
                    @if($p->es_individual)
                      <td class="tablaProgresivos fila" style="font-size: 60%;"><div class="break">INDIVIDUAL</div></td>
                    @else
                      <td class="tablaProgresivos fila" style="font-size: 60%;"><div class="break">{{$p->progresivo}}{{$p->pozo_unico? ' ' : ' ('.$p->pozo.')'}}</div></td>
                    @endif
                    @for($i=1;$i<7;$i++)
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
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">OBSERVACIONES GENERALES</th>
                </tr>
                <tr>
                  @if($relevamiento->toma1_observ != null)
                  <td class="tablaInicio" style="height:auto; background-color: #fff; border-color: gray;">
                    {{$relevamiento->toma1_observ}}
                  </td>
                 @else
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">
                  <div style="color: #dddddd;">
                  @for($i = 0; $i<648; $i++)
                  .
                  @endfor
                  </p>
                  </div>
                  </td>
                @endif
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
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Personal del Concesionario en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Fiscalizador en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Personal del Concesionario en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Fiscalizador en Sala de Juegos</center></td>
                </tr>
              </table>
              @endforeach
      </body>
</html>
