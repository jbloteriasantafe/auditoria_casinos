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

tr:nth-child(even) {
  background-color: #dddddd;
}

p {
      border-top: 1px solid #000;
}

.recuadroLista{
  border-left: 6px solid black;
  background-color: lightgrey;
}

/* DivTable.com */
.divTable{
	display: table;
	width: 100%;
}
.divTableRow {
	display: table-row;
}
.divTableHeading {
	background-color: #EEE;
	display: table-header-group;
}
.divTableCell, .divTableHead {
	border: 1px solid #999999;
	display: table-cell;
	padding: 0px;
}
.divTableCell2, .divTableHead2 {
	border: 1px solid #999999;
	display: table-cell;
  padding: 3px 6px;
}
.divTableHeading {
	background-color: #EEE;
	display: table-header-group;
	font-weight: bold;
}
.divTableFoot {
	background-color: #EEE;
	display: table-footer-group;
	font-weight: bold;
}
.divTableBody {
	display: table-row-group;
}
.chico {
    font-size: 80%;
}

</style>

  <head>
    <meta charset="utf-8">
    <title></title>

    <!-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> -->

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="css/estiloPlanillaLandscape.css" rel="stylesheet">
  </head>
  <body>
  <?php 
  $hoy = date('j-m-y / H:i');
  if(!is_null($rel->fecha_ejecucion)) $ejec = date('j-m-y / H:i',strtotime($rel->fecha_ejecucion));
  else $ejec = '&nbsp; -&nbsp; &nbsp; -&nbsp; &nbsp;  / &nbsp; &nbsp; :&nbsp; &nbsp;';
  ?>


        <div class="encabezadoImg">
              <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span>RMTM07 | Control de Estado de Máquinas Tragamonedas</span></h2>
        </div>

        <div class="camposTab titulo" style="top: -16px; right:386px;">TURNO</div>
        <div class="camposInfo" style="top: -16px; right:255px;"></span>{{$rel->turno}}</div>
        <div class="camposTab titulo" style="top: 0px; right:325px;">FECHA PLANILLA</div>
        <div class="camposInfo" style="top: 0px; right:201px;"></span>{{$hoy}}</div>
        @if($mostrar_maquinas)
        <div class="camposTab titulo" style="top: 16px; right:294px;">FECHA DE EJECUCIÓN</div>
        <div class="camposInfo" style="top: 16px; right: 201px;">{{$ejec}}</div>
        <div class="camposTab titulo" style="top: 32px; right:202px;">CANT. TOTAL DE MÁQ. OBSERVADAS:</div>
        <div class="camposInfo" style="top: 32px;right: 170px;">{{$rel->total_activas}}</div>
        @else
        <div class="camposTab titulo" style="top: 16px; right:194px;">FECHA DE EJECUCIÓN &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; : &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </div>
        <div class="camposTab titulo" style="top: 32px; right:170px;">CANT. TOTAL DE MÁQ. OBSERVADAS: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>
        @endif

        <div class="primerEncabezado"><b>Lista de control de Islas verificadas </b><i>(Ingresar total de máquinas observadas por isla)</i>:</div>
        <br>

        @foreach($detalles as $detalle)
        <div class="recuadroLista" style="padding-left: 10px;"> {{$detalle->descripcion}}</div>
        <?php $pos = 0; ?>
        <div class="divTable">
        <div class="divTableBody">
          @if($mostrar_maquinas)
              <div class="divTableRow">
                @foreach($detalle->islas as $isla)
                @if($isla->cantidad_maquinas_y_int_tecnica!=0)
                @if($pos != 23)
                <?php $pos += 1; ?>
                <div class="divTableCell" style="width: 4.26%;text-align: center;">
                  <div>{{$isla->nro_isla}}</div> 
                  <div class="chico">{{is_null($isla->pivot->maquinas_observadas)? '_' : $isla->pivot->maquinas_observadas}} ({{$isla->cantidad_maquinas}})</div>
                </div>
                @else
                <?php $pos = 1; ?>
              </div>
              <div class="divTableRow">
                <div class="divTableCell" style="width: 4.26%;text-align: center;">
                  <div>{{$isla->nro_isla}}</div> 
                  <div class="chico">{{is_null($isla->pivot->maquinas_observadas)? '_' : $isla->pivot->maquinas_observadas}} ({{$isla->cantidad_maquinas}})</div>
                </div>
                @endif
                @endif
                @endforeach
              </div>

              @else
              <div class="divTableRow" style="margin: 0px;">
                  @foreach($detalle->islas as $isla)
                  @if($isla->cantidad_maquinas_y_int_tecnica!=0)
                  @if($pos != 23)
                  <?php $pos += 1; ?>
                  <div class="divTableCell" style="width: 4.26%;text-align: center;">
                    <div>{{$isla->nro_isla}}</div>
                    <div>___</div>
                  </div>
                  @else
                  <?php $pos = 1; ?>
                </div>
                <div class="divTableRow">
                  <div class="divTableCell" style="width: 4.26%;text-align: center;">
                    <div>{{$isla->nro_isla}}</div>
                    <div>___</div>
                  </div>
                  @endif
                  @endif
                  @endforeach
                </div>

            @endif

        </div>
      </div><br>
        @endforeach

        <!-- ENLACE HOJA 2 -->
        <div style="page-break-after:always;"></div>

        <!-- HOJA 2 -->
        <div class="encabezadoImg">
              <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span>RMTM07 | Control de Estado de Máquinas Tragamonedas </span></h2>
        </div>

        <div class="camposTab titulo" style="top: -16px; right:386px;">TURNO</div>
        <div class="camposInfo" style="top: -16px; right:255px;"></span>{{$rel->turno}}</div>
        <div class="camposTab titulo" style="top: 0px; right:325px;">FECHA PLANILLA</div>
        <div class="camposInfo" style="top: 0px; right:201px;"></span>{{$hoy}}</div>
        @if($mostrar_maquinas)
        <div class="camposTab titulo" style="top: 16px; right:294px;">FECHA DE EJECUCIÓN</div>
        <div class="camposInfo" style="top: 16px; right: 201px;">{{$ejec}}</div>
        <div class="camposTab titulo" style="top: 32px; right:202px;">CANT. TOTAL DE MÁQ. OBSERVADAS:</div>
        <div class="camposInfo" style="top: 32px;right: 170px;">{{$rel->total_activas}}</div>
        @else
        <div class="camposTab titulo" style="top: 16px; right:194px;">FECHA DE EJECUCIÓN &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; : &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </div>
        <div class="camposTab titulo" style="top: 32px; right:170px;">CANT. TOTAL DE MÁQ. OBSERVADAS: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>
        @endif


              <div class="divTable">
                <div class="divTableBody">
                  <div class="divTableRow">
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      SECTOR
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      ISLA
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      N° ADMIN
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      C.O(*)
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      P.B(**)
                    </div>
                    <div class="divTableCell2" style="border: none;">
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      SECTOR
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      ISLA
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      N° ADMIN
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      C.O(*)
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      P.B(**)
                    </div>
                    <div class="divTableCell2" style="border: none;">
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      SECTOR
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      ISLA
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      N° ADMIN
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      C.O(*)
                    </div>
                    <div class="divTableCell2" style="background-color: #dddddd;">
                      P.B(**)
                    </div>
                  </div>
                  <?php $j=0 ?>
                  @for($k=0; $k<ceil(count($maquinas_apagadas)/ 3) ; $k++)
                  <div class="divTableRow">
                    @if(isset($maquinas_apagadas[$k]))
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k]->descripcion_sector}}
                    </div>
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k]->nro_isla}}
                    </div>
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k]->nro_admin}}
                    </div>
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k]->co}}
                    </div>
                    <div class="divTableCell2">
                      <?php echo $maquinas_apagadas[$k]->pb == 0 ? '' : 'X';  ?>
                    </div>
                    <div class="divTableCell2" style="border: none;">
                    </div>
                    @else
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2" style="border: none;">
                    </div>
                    @endif

                    @if(isset($maquinas_apagadas[$k+1]))
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k+1]->descripcion_sector}}
                    </div>
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k+1]->nro_isla}}
                    </div>
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k+1]->nro_admin}}
                    </div>
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k+1]->co}}
                    </div>
                    <div class="divTableCell2">
                      <?php echo $maquinas_apagadas[$k+1]->pb == 0 ? '' : 'X';  ?>
                    </div>
                    <div class="divTableCell2" style="border: none;">
                    </div>
                    @else

                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2" style="border: none;">
                    </div>
                    @endif

                    @if(isset($maquinas_apagadas[$k+2]))
                    <div class="divTableCell2" style="padding-top: 18px;">
                      {{$maquinas_apagadas[$k+2]->descripcion_sector}}
                    </div>
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k+2]->nro_isla}}
                    </div>
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k+2]->nro_admin}}
                    </div>
                    <div class="divTableCell2">
                      {{$maquinas_apagadas[$k+2]->co}}
                    </div>
                    <div class="divTableCell">
                      <?php echo $maquinas_apagadas[$k+2]->pb == 0 ? '' : 'X';?>
                    </div>
                    @else
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    @endif
                  </div>
                  @endfor


                  @for($i=0; $i<(17 -$k) ; $i++)
                  <div class="divTableRow">
                    <div class="divTableCell2" style="padding-top: 18px;">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2" style="border: none;">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2" style="border: none;">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                    <div class="divTableCell2">
                    </div>
                  </div>
                  @endfor
                </div>
              </div>
              <br>
              <div>
                <div style="float: left;width: 20%;">
                  <div style="font-size: 11px; text-decoration: underline;"> Códigos:</div>
                  <div style="font-size: 11px;"><strong>FDS</strong> -> Fuera de servicio.</div>
                  <div style="font-size: 11px;"><strong>MT</strong> -> Módulo tildado.</div>
                  <div style="font-size: 11px;"><strong>FDST</strong> -> Fuera de servicio temporal.</div>
                  <div style="font-size: 11px;"><strong>TM</strong> -> Tareas de mantenimiento.</div>
                  <div style="font-size: 11px;"><strong>FDS</strong> -> No funciona la botonera.</div>
                  <div style="font-size: 11px;"><strong>MA</strong> -> Máquinia apagada.</div>
                  <div style="font-size: 11px;"><strong>TP</strong> -> Trabajo en el plato.</div>
                </div>
                <div style="float: left;width: 45%;">
                  @if(!is_null($observacion))
                  <div style="text-decoration: underline;">Observaciones:</div>
                  <div style="color: #636363; word-wrap: break-word;font-size: 15px;">
                  {{$observacion}}
                  </div>
                  @endif
                </div>
                <div style="float: right;width: 30%;">
                  @if($mostrar_maquinas)
                  <br>
                  <br>
                  <br>
                  <br>
                  <p>
                    Firma y aclaración/s responsable/s.
                  </p>
                  @endif
                </div>
              </div>
  </body>
</html>
