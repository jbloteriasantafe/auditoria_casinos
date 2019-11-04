@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php

use Illuminate\Http\Request;
use App\Http\Controllers\CasinoController;

$activas_por_fila = 23;
$total_usado = 98; //Dejo un poco de espacio en el borde.
$porcentaje_por_activa = $total_usado/$activas_por_fila;
$mitad_porcentaje = $porcentaje_por_activa / 2;
?>

@section('estilos')
<!-- <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/> -->
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel="stylesheet" href="css/lista-datos.css">
<style>
.chico {
    font-size: 95%;
}
.borde {
  border-bottom: 1px solid #ddd !important;
  border-right: 1px solid #ddd !important;
  border-top: 1px solid #ddd !important;
  border-left: 1px solid #ddd !important;
}
.input-chico {
  padding: 0px 0px !important;
  font-size: 12px !important;
  text-align: center !important;
  line-height: 1;
  border-radius: 0px;
}
.subrayado{
  border-bottom: 4px solid rgb(255,168,141);
}
.correcto{
  color: green;
}
.incorrecto{
  color: red;
}
.sombreado{
  color: grey;
}
.borde_superior {
  border-top: solid 1px black !important;
}
</style>
@endsection

                <div class="row">
                  <div class="col-lg-12 col-xl-9"> <!-- columna TABLA CASINOS -->
                    <!-- FILTROS -->
                    <div class="row">
                        <div class="col-md-12">
                            <div id="contenedorFiltros" class="panel panel-default">
                              <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                                <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                              </div>
                              <div id="collapseFiltros" class="panel-collapse collapse">
                                <div class="panel-body">
                                  <div class="row">
                                    <div class="col-md-3">
                                      <h5>Fecha</h5>
                                      <div class='input-group date' id='dtpBuscadorFecha' data-link-field="buscadorFecha" data-link-format="yyyy-mm-dd">
                                        <input id="" type='text' class="form-control" placeholder="Fecha RELEVAMIENTO"/>
                                        <span class="input-group-addon">
                                          <span class="glyphicon glyphicon-calendar"></span>
                                        </span>

                                        <input type="hidden" id="buscadorFecha" value=""/>
                                      </div>
                                   </div>
                                    <div class="col-md-3">
                                        <h5>Casino</h5>
                                        <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                            <option value="0">-Todos los Casinos-</option>
                                            @foreach ($casinos as $casino)
                                              <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <h5>Estado Relevamiento</h5>
                                        <select id="buscadorEstado" class="form-control selectSector" name="">
                                            <option value="0">-Todos los estados-</option>
                                            @foreach($estados as $estado)
                                              <option id="estado{{$estado->id_estado_relevamiento}}" value="{{$estado->id_estado_relevamiento}}">{{$estado->descripcion}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                      <h5 style="color:#f5f5f5;">boton buscar</h5>
                                      <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                    </div>

                                  </div>
                                  <br>
                                </div>
                              </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                    <div class="panel panel-default">

                      <div class="panel-heading">
                        <h4>LAYOUT TOTAL GENERADO POR EL SISTEMA</h4>
                      </div>

                      <div class="panel-body">
                        <table id="tablaLayouts" class="table table-fixed tablesorter">
                          <thead>
                            <tr>
                              <th class="col-xs-2 activa" value="layout_total.fecha" estado="desc">FECHA <i class="fas fa-sort-down"></i></th>
                              <th class="col-xs-2" value="casino.nombre" estado="">CASINO  <i class="fas fa-sort"></i></th>
                              <th class="col-xs-2" value="sector.descripcion" estado="">TURNO <i class="fas fa-sort"></i></th>
                              <th class="col-xs-3" value="estado_relevamiento.descripcion" estado="">ESTADO <i class="fas fa-sort"></i></th>
                              <th class="col-xs-3">ACCIÓN </th>
                            </tr>
                          </thead>
                          <tbody id="cuerpoTabla" style="height: 350px;">

                          </tbody>
                        </table>
                        <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                      </div>
                      </div>
                    </div>
                  </div>
                </div>
            <!-- /.col-lg-12 col-xl-9 -->
            <div class="col-lg-12 col-xl-3">
              <div class="row">
                  <div class="col-md-12">
                   <a href="" id="btn-nuevoLayoutTotal" style="text-decoration: none;">
                    <div class="panel panel-default panelBotonNuevo">
                        <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                        <div class="backgroundNuevo"></div>
                        <div class="row">
                            <div class="col-xs-12">
                              <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">GENERAR CONTROL LAYOUT</h4>
                              </center>
                            </div>
                        </div>
                    </div>
                   </a>
                  </div>
              </div>

              <div class="row">
                  <div class="col-md-12">
                   <a href="" id="btn-layoutSinSistema" style="text-decoration: none;">
                    <div class="panel panel-default panelBotonNuevo">
                        <center><img class="imgNuevo" src="/img/logos/relevamientos_sin_sistema_white.png"><center>
                        <div class="backgroundNuevo"></div>
                        <div class="row">
                            <div class="col-xs-12">
                              <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">GENERAR CONTROL LAYOUT SIN SISTEMA</h4>
                              </center>
                            </div>
                        </div>
                    </div>
                   </a>
                  </div>
              </div>

            </div>

        </div>  <!-- /#row -->

    <!-- Modal layout -->
    <div class="modal fade" id="modalLayoutTotal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| NUEVO CONTROL LAYOUT </h3>
                </div>

                <div  id="colapsado" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmLayoutTotal" name="frmLayoutTotal" class="form-horizontal" novalidate="">

                          <div class="row">
                            <div class="col-md-6">
                              <h5>FECHA</h5>
                              <!-- <input id="fechaActual" class="form-control" type="text" value=""> -->
                              <input id="fechaActual" type='text' class="form-control" readonly>
                              <input id="fechaDate" type="text" name="" hidden>
                              <br>
                            </div>
                            <div class="col-md-6">
                              <h5>CASINO</h5>
                              <select id="casino" class="form-control selectCasinos" name="">
                                @if(count($casinos) != 1)
                                  <option value="0">- Seleccione un casino -</option>
                                @endif
                                @foreach ($casinos as $casino)
                                  <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                @endforeach
                              </select>
                              <br> <span id="alertaCasino" class="alertaSpan"></span>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-6">
                              <h5>Turno</h5>
                              @if(count($casinos) != 1)
                                <input id="turno" class="form-control" type="text" name="" value="" placeholder="" readonly="true"></input>
                              @elseif(!empty(CasinoController::getInstancia()->obtenerTurno($casinos[0]->id_casino)['turno']))
                                <input id="turno" class="form-control" type="text" name="" value="{{CasinoController::getInstancia()->obtenerTurno($casinos[0]->id_casino)['turno'][0]}}" placeholder="" readonly="true"></input>
                              @else
                                <input id="turno" class="form-control" type="text" name="" value="" placeholder="No existe turno" readonly="true"></input>
                              @endif
                        		</div>
                          </div>
                  </form>

                </div>
                <div id="iconoCarga" class="sk-folding-cube">
                  <div class="sk-cube1 sk-cube"></div>
                  <div class="sk-cube2 sk-cube"></div>
                  <div class="sk-cube4 sk-cube"></div>
                  <div class="sk-cube3 sk-cube"></div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-generar" value="nuevo">GENERAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <input type="hidden" id="id_casino" name="id_casino" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal Relevamientos -->
    <div class="modal fade" id="modalLayoutSinSistema" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarSinSistema" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoSinSistema" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| NUEVO CONTROL LAYOUT SIN SISTEMA</h3>
                </div>

                <div  id="colapsadoSinSistema" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmLayoutSinSistema" name="frmLayoutSinSistema" class="form-horizontal" novalidate="">

                          <div class="row">
                            <div class="col-md-6">
                              <h5>FECHA DE CONTROL LAYOUT</h5>
                              <div class='input-group date' id='fechaControlSinSistema' data-link-field="fechaLayoutSinSistema" data-link-format="yyyy-mm-dd">
                                <input id="fecha_backup" type='text' class="form-control" placeholder="Fecha de Relevamiento"/>
                                <span class="input-group-addon">
                                  <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                                <input type="hidden" id="fechaLayoutSinSistema" value=""/>
                              </div>
                              <br>
                            </div>
                            <div class="col-md-6">
                              <h5>FECHA DE GENERACIÓN</h5>
                              <div class='input-group date' id='fechaGeneracion' data-link-field="fechaGeneracionSinSistema" data-link-format="yyyy-mm-dd">
                                <input id="fecha_generacion_backup" type='text' class="form-control" placeholder="Fecha de Generación"/>
                                <span class="input-group-addon">
                                  <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                                <input type="hidden" id="fechaGeneracionSinSistema" value=""/>
                              </div>
                              <br>
                            </div>

                          </div>

                          <div class="row">
                            <div class="col-md-6">
                              <h5>CASINO</h5>
                              <select id="casinoSinSistema" class="form-control selectCasinos" name="">
                                  <option value="">- Seleccione un casino -</option>
                                   @foreach ($casinos as $casino)
                                   <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                   @endforeach
                              </select>
                              <br> <span id="alertaCasinoSinsistema" class="alertaSpan"></span>
                            </div>
                          </div>

                  </form>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-backup" value="nuevo">USAR RELEVAMIENTO BACKUP</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <input type="hidden" id="id_casino" name="id_casino" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>


    <!-- Modal cargar layout -->
    <div class="modal fade" id="modalCargaControlLayout" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:94%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FF6E40;padding-bottom: 8px;">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <!-- <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button> -->
                 <button id="btn-minimizarCargar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">CARGAR CONTROL LAYOUT</h3>
                </div>
                <div  id="colapsadoCargar" class="collapse in">
                <div class="modal-body modalCuerpo">
                <div class="row">
                    <div class="col-lg-2 col-lg-offset-1">
                      <h5>FECHA DE LAYOUT</h5>
                      <input id="cargaFechaActual" type='text' class="form-control" readonly>

                    </div>
                    <div class="col-lg-2">
                      <h5>FECHA DE GENERACIÓN</h5>
                      <input id="cargaFechaGeneracion" type='text' class="form-control" readonly>

                    </div>
                    <div class="col-lg-2">
                      <h5>CASINO</h5>
                      <input id="cargaCasino" type='text' class="form-control" readonly>
                        <span id="alertaCasino" class="alertaSpan"></span>
                    </div>
                    <div class="col-lg-2">
                      <h5>TURNO</h5>
                      <input id="cargaTurno" type='text' class="form-control" readonly>
                        <span id="alertaTurno" class="alertaSpan"></span>
                    </div>
                  </div>
                  <br>
                  <div class="row">
                    <div class="col-xs-3 col-xs-offset-1">
                        <h5>FISCALIZADOR CARGA</h5>
                        <input id="fiscaCarga" type="text"class="form-control" readonly>
                    </div>
                    <div class="col-xs-3">
                        <h5>FISCALIZADOR TOMA</h5>
                        <!-- prueba -->
                        <input id="inputFisca" data-fisca="" class="form-control" size="100" type="text" autocomplete="off" />

                    </div>
                    <div class="col-xs-4">
                        <h5>FECHA EJECUCIÓN</h5>
                        <!-- anda -->
                            <div class='input-group date' id='dtpFecha' data-link-field="fecha_ejecucion" data-date-format="dd MM yyyy HH:ii" data-link-format="yyyy-mm-dd HH:ii">
                                <input type='text' class="form-control" placeholder="Fecha de ejecución del control" id="fecha" />
                                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                            </div>
                            <input type="hidden" id="fecha_ejecucion" value=""/>
                    </div>
                  </div>
                  <div class="row tabs" style="background-color:rgb(250,250,250);padding-bottom: 0px;">
                    <h4 class="col-lg-6 tabActivas" style="text-align: center;color:rgb(0, 103, 177);padding-bottom: 0px;margin-bottom: 0px;"><b>ACTIVAS</b></h4>
                    <h4 class="col-lg-6 tabInactivas" style="text-align: center;color:rgb(0, 103, 177);padding-bottom: 0px;margin-bottom: 0px;"><b>INACTIVAS</b></h4>
                  </div>
                  <div class="activas row" style="overflow: scroll;height: 70%;">
                  </div>
                  <div class="inactivas row" style="overflow: scroll;height: 70%;"><form id="frmCargaControlLayout" name="frmCargaLayout" class="form-horizontal" novalidate="">
                          <div class="row">
                              <div class="col-md-12">
                                  <table id="tablaCargaControlLayout" class="table">
                                      <thead>
                                          <tr>
                                              <th width="3%" ></th>
                                              <th width="17%">SECTOR</th>
                                              <th width="16%">ISLA</th>
                                              <th width="12%">N° ADMIN</th>
                                              <th width="14%">C.O <i class="fa fa-question-circle" data-toggle="popover" data-trigger="hover" data-title="CÓDIGO DE OBSERVACIÓN" data-content="Código que indica el motivo de la no toma."></i></th>
                                              <th width="14%">PROGRESIVO BLOQ</th>
                                              <th width="18%"></th>
                                          </tr>
                                      </thead>
                                      <tbody id="controlLayout">
                                      </tbody>
                                  </table>
                                  <button class="btn btn-success btn-agregarNivel" type="button"><i class="fa fa-fw fa-plus"></i> AGREGAR MÁQUINA</button>
                              </div>
                          </div>
                          <div id="encabezado_diferencia" class="row" hidden>
                              <div class="col-md-12">
                                <h5 >Máquinas con Diferencia</h5>
                                <div id="maquinas_con_diferencia">
                                </div>
                              </div>
                          </div>
                  </form></div>
                  <br>
                  <div class="row">
                      <span class="col-md-8 col-md-offset-2">
                        <h5>CANTIDAD TOTAL DE MÁQUINAS ACTIVAS:</h5>
                        <input class="total_activas" disabled>
                      </span>
                  </div>
                  <br>
                  <div class="row">
                      <div class="col-md-8 col-md-offset-2">
                        <h5>OBSERVACIONES</h5>
                        <textarea id="observacion_carga" class="form-control" style="resize:vertical;"></textarea>
                      </div>
                  </div>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-warningModificar" id="btn-guardarTemp" value="nuevo" style="position:absolute;left:20px;">GUARDAR TEMPORALMENTE</button>
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">FINALIZAR</button>
                  <button type="button" class="btn btn-default" id="btn-salir">SALIR</button>
                  <div class="mensajeSalida">
                      <br>
                      <span style="font-family:'Roboto-Black'; color:#EF5350;">CAMBIOS SIN GUARDAR</span>
                      <br>
                      <span style="font-family:'Roboto'; color:#555;">Presione SALIR nuevamente para salir sin guardar cambios.</span>
                  </div>
                  <div class="mensajeConfirmacion">
                      <br>
                      <span style="font-family:'Roboto-Black'; color:#EF5350;">Ocurrieron errores.</span>
                      <br>
                      <span style="font-family:'Roboto'; color:#555;">Si presiona finalizar nuevamente se enviará de todas formas.</span>
                  </div>
                  <input type="hidden" id="id_layout_total" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

<div class="modal fade" id="modalValidarControl" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="width:94%;">
        <div class="modal-content">
            <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#69F0AE;padding-bottom: 8px;">
                <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                <button id="btn-minimizarValidar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoValidar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title">VALIDAR CONTROL LAYOUT</h3>
            </div>
            <div id="colapsadoValidar" class="collapse in">
                <div class="modal-body modalCuerpo">
                    <div class="row">
                        <div class="col-lg-2 col-lg-offset-1">
                            <h5>FECHA DE LAYOUT</h5>
                            <input id="validarFechaActual" type='text' class="form-control" readonly>
                        </div>
                        <div class="col-lg-2">
                            <h5>FECHA DE GENERACIÓN</h5>
                            <input id="validarFechaGeneracion" type='text' class="form-control" readonly>
                        </div>
                        <div class="col-lg-2">
                            <h5>CASINO</h5>
                            <input id="validarCasino" type='text' class="form-control" readonly>
                            <span id="alertaValidarCasino" class="alertaSpan"></span>
                        </div>
                        <div class="col-lg-2">
                            <h5>TURNO</h5>
                            <input id="validarTurno" type='text' class="form-control" readonly>
                            <span id="alertaValidarTurno" class="alertaSpan"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-3 col-xs-offset-1">
                            <h5>FISCALIZADOR CARGA</h5>
                            <input id="validarFiscaCarga" type="text" class="form-control" readonly>
                        </div>
                        <div class="col-xs-3">
                            <h5>FISCALIZADOR TOMA</h5>
                            <input id="validarInputFisca" data-fisca="" class="form-control" size="100" type="text" />
                        </div>
                        <div class="col-xs-4">
                            <h5>FECHA EJECUCIÓN</h5>
                            <input type='text' class="form-control" placeholder="Fecha de ejecución del control" id="validarFechaEjecucion" readonly="true" />
                        </div>
                    </div>
                    <div class="row tabs" style="background-color:rgb(250,250,250);padding-bottom: 0px;">
                      <h4 class="col-lg-4 tabActivas" style="text-align: center;color:rgb(0, 103, 177);padding-bottom: 0px;margin-bottom: 0px;"><b>ACTIVAS</b></h4>
                      <h4 class="col-lg-4 tabInactivas" style="text-align: center;color:rgb(0, 103, 177);padding-bottom: 0px;margin-bottom: 0px;"><b>INACTIVAS</b></h4>
                      <h4 class="col-lg-4 tabDiferencias" style="text-align: center;color:rgb(0, 103, 177);padding-bottom: 0px;margin-bottom: 0px;"><b>DIFERENCIAS</b></h4>
                    </div>
                    <div class="activas row" style="overflow: scroll;height: 70%;min-height: 30%;">
                    </div>
                    <div class="inactivas row" style="overflow: scroll;height: 70%;min-height: 30%;">
                      <div class="row">
                          <div class="col-md-12">
                              <table id="tablaValidarControlLayout" class="table">
                                  <thead>
                                      <tr>
                                          <th width="3%"></th>
                                          <th width="17%" style="align:center;">SECTOR</th>
                                          <th width="16%">ISLA</th>
                                          <th width="12%">N° ADMIN</th>
                                          <th width="14%">C.O</th>
                                          <th width="14%">PROGRESIVO BLOQ</th>
                                          <th width="18%"></th>
                                      </tr>
                                  </thead>
                                  <tbody id="validarControlLayout">
                                  </tbody>
                              </table>
                          </div>
                      </div>
                    </div>
                    <div class="diferencias row" style="overflow:scroll;height: 70%;min-height: 30%;">
                    </div>
                    <div class="row">
                      <span class="col-md-8 col-md-offset-2">
                        <h5>CANTIDAD TOTAL DE MÁQUINAS ACTIVAS:</h5>
                        <input class="total_activas" disabled>
                      </span>
                    </div>
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <h5>OBSERVACIONES FISCALIZADOR</h5>
                            <textarea id="observacion_carga_validacion" class="form-control" style="resize:vertical;" disabled></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                            <h5>OBSERVACIONES</h5>
                            <textarea id="observacion_validar" class="form-control" style="resize:vertical;"></textarea>
                        </div>
                    </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-successAceptar" id="btn-finalizarValidacion" value="nuevo">VALIDAR RELEVAMIENTO</button>
                        <button type="button" class="btn btn-default" id="btn-salirValidacion" data-dismiss="modal">SALIR</button>
                        <input type="hidden" id="id_relevamiento" value="0">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Modal planilla relevamientos -->
    <div class="modal fade" id="modalPlanilla" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:80%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#42A5F5;">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">IMPRIMIR PLANILLA</h3>
                </div>

                <div  id="colapsadoCargar" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmPlanilla" name="frmPlanilla" class="form-horizontal" novalidate="">

                          <div class="row">
                              <div class="col-md-12">
                                  <!-- Carga de archivos! | Uno para el modal de nuevo y otro para modificar -->
                                  <div class="zona-file-lg">
                                      <input id="cargaArchivo" data-borrado="false" type="file" multiple>
                                  </div>

                                  <div class="alert alert-danger fade in" role="alert" id="alertaArchivo"><span></span></div>
                              </div>
                          </div>

                  </form>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-infoBuscar" id="btn-imprimirPlanilla">IMPRIMIR</button>
                  <!-- <button type="button" class="btn btn-successAceptar" id="btn-finalizar" value="nuevo">FINALIZAR RELEVAMIENTO</button> -->
                  <button type="button" class="btn btn-default" id="btn-salirPlanilla" data-dismiss="modal">SALIR</button>
                  <input type="hidden" id="id_relevamiento" value="0">
                </div>
              </div>
            </div>
          </div>
      </div>

  <!-- Modal relevamiento activas -->
  <div class="modal fade" id="modalCargarActivas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width:80%;">
            <div class="modal-content">
              <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FF6E40;">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargarCantidad" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title">CARGAR TOTALES</h3>
              </div>
              <div id="colapsadoCargarActivas" class="collapse in">
                <div class="modal-body">
                <div class="modalCuerpo" style="overflow: scroll;height: 70%;">
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-successAceptar finalizar" value="nuevo">FINALIZAR RELEVAMIENTO</button> 
                <button type="button" class="btn btn-default salir" data-dismiss="modal">SALIR</button>
              </div>
            </div>
          </div>
      </div>
  </div>



  <div id="sectorEjemplo" class="sector" hidden>
    <hr/>
    <b class="nombre">NOMBRE SECTOR</b>
    <table class="tablaIslas table table-fixed tablesorter">
      <thead class="cabezeraTabla">
        <tr>
          @for ($i=1;$i<=$activas_por_fila;$i++)
          <th style="text-align: center;width: {{$porcentaje_por_activa}}%" class="{{$i}}"></th>
          @endfor
        </tr>
      </thead>
      <tbody class="cuerpoTabla">
      </tbody>
    </table>
  </div>

  <!-- FILAS EJEMPLO -->
  <!-- Necesito crear tablas por algun motivo sino se rompe el html -->
  <div hidden>
    <table>
      <thead>
        <tr></tr>
      </thead>
      <tbody>
        <tr id="filaEjemploActivas" style="display: block;" activas_por_fila="{{$activas_por_fila}}"></tr>
        <tr id="filaEjemplo" style="display: none;">
          <td class="col-xs-2 fecha">99 Test 9999</td>
          <td class="col-xs-2 casino">CASINO99</td>
          <td class="col-xs-2 turno">99</td>
          <td class="col-xs-3">
            <i class="fas fa-fw fa-dot-circle icono_estado"></i>
            <span class="estado">ESTADO99</span>
          </td>
          <td class="col-xs-3">
            <button class="btn btn-info ver" title="VER LAYOUT TOTAL" type="button" value="-1">
              <i class="fa fa-fw fa-search-plus"></i>
            </button>
            <span></span>
            <button class="btn btn-info planilla" title="PLANILLA RELEVAMIENTO" type="button" value="-1">
              <i class="far fa-fw fa-file-alt"></i>
            </button>
            <span></span>
            <button class="btn btn-warning carga" title="CARGAR MAQUINAS NO FUNCIONANDO" type="button" value="-1">
              <i class="fa fa-fw fa-upload"></i>
            </button>
            <span></span>
            <button class="btn btn-success validar" title="VALIDAR RELEVAMIENTO" type="button" value="-1">
              <i class="fa fa-fw fa-check"></i>
            </button>
            <span></span>
            <button class="btn btn-info imprimir" title="PLANILLA COMPLETADA" type="button" value="-1">
              <i class="fa fa-fw fa-print"></i>
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div hidden>
    <table>
      <thead>
        <tr>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td id="islaEjemplo" style="width: {{$porcentaje_por_activa}}%;" class="isla borde">
            <div>
              <div style="text-align: center;" class="textoIsla">-1</div>
              <input class="form-control inputIsla input-chico"/>
            </div>
          </td>
          <td id="islaEjemploValidar" style="width: {{$porcentaje_por_activa}}%;" class="isla borde chico ">
            <div>
              <div style="text-align: center;" class="textoIsla">-1</div>
              <div style="text-align: center;">
                <span class="observado"></span>
                <span class="inactivas incorrecto"></span>
                <span>(<span class="sistema"></span>)</span>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

</div>

<div hidden>
  <table id="tablaDiferenciasEjemplo" class="table table-fixed tablesorter col-lg-12">
    <thead>
      <tr>
        <th class="col-lg-2">SECTOR</th>
        <th class="col-lg-2">ACTIVAS</th>
        <th class="col-lg-2">INACTIVAS</th>
        <th class="col-lg-2">TOTAL RELEVADO</th>
        <th class="col-lg-2">TOTAL SISTEMA</th>
        <th class="col-lg-2">DIFERENCIA</th>
      </tr>
    </thead>
    <tbody class="cuerpoTablaDiferencias">
      <tr class="diferenciasFilaEjemplo">
        <td class="diferenciasSector col-lg-2">SECTOR999</td>
        <td class="diferenciasActivas col-lg-2">ERROR</td>
        <td class="diferenciasInactivas col-lg-2">ERROR</td>
        <td class="diferenciasTotal col-lg-2">ERROR</td>
        <td class="diferenciasTotalSistema col-lg-2">ERROR</td>
        <td class="diferenciasDiferencia col-lg-2">ERROR</td>
      </tr>
    </tbody>
  </table>
</div>
    

    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| LAYOUT TOTAL</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Layout Total</h5>
      <p>
        En su generación, estarán controlados los turnos de toma, correspondientes a la hora en que se toman diariamente.
        Además, la posibilidad de generar estas planillas de forma offline, donde se genera un archivo .rar para la toma de los próximos 7 días.
      </p>
      <h5>Edición de planillas</h5>
      <p>
        Se pueden observar planillas de layout total del parque de máquinas.
        Su división es por sectores, el número observado es el n° de isla, mientras que el que se encuentra en paréntesis, es la cantidad de máquinas asociadas a dicha isla.
        Esta disposición facilita la toma del relevamiento del parque de máquinas, ya que divide el recorrido para optimizar los tiempos de toma de datos.
        En su segunda hoja de planilla generada, se observan campos de aquellas máquinas que dieron algún tipo de error o que no se pudieron tomar debido a problemas técnicos;
        los cuales, están detallados en códigos como los mas frecuentes, y se detalla con una cruz en el campo de PB (progresivo bloqueado) si éste genera un error a la hora de tomar este dato
        en ese preciso instante.
        Según su estado, se podrá cargar, validar e imprimir el archivo final de esta toma de valores.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionLayoutTotal.js" charset="utf-8"></script>
    <script src="js/paginacion.js" charset="utf-8"></script>
    <script src="js/lista-datos.js" charset="utf-8"></script>

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    <script src="js/inputSpinner.js" type="text/javascript"></script>
    @endsection
