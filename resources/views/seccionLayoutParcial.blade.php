@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php

use Illuminate\Http\Request;

?>

@section('estilos')
<!-- <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/> -->
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel="stylesheet" href="css/lista-datos.css">
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
                                        <div class="form-group">
                                           <div class='input-group date' id='dtpBuscadorFecha' data-link-field="buscadorFecha" data-link-format="yyyy-mm-dd">
                                               <input type='text' class="form-control" placeholder="Fecha de relevamiento" id="B_fecharelevamiento"/>
                                               <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                               <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                           </div>
                                           <input class="form-control" type="hidden" id="buscadorFecha" value=""/>
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
                                          <h5>Sector</h5>
                                          <select id="buscadorSector" class="form-control selectSector" name="">
                                              <option value="0">-Todos los sectores-</option>

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
                                  </div>
                                  <div class="row">
                                    <center>
                                      <h5 style="color:#f5f5f5;">boton buscar</h5>
                                      <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                    </center>
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
                        <h4>LAYOUT PARCIAL GENERADO POR EL SISTEMA</h4>
                      </div>

                      <div class="panel-body">
                        <table id="tablaLayouts" class="table table-fixed tablesorter">
                          <thead>
                            <tr>
                              <th class="col-xs-2 activa" value="layout_parcial.fecha" estado="desc">FECHA <i class="fas fa-sort-down"></i></th>
                              <th class="col-xs-2" value="casino.nombre" estado="">CASINO  <i class="fas fa-sort"></i></th>
                              <th class="col-xs-2" value="sector.descripcion" estado="">SECTOR <i class="fas fa-sort"></i></th>
                              <th class="col-xs-1" value="layout_parcial.sub_control" estado="">SUB <i class="fas fa-sort"></i></th>
                              <th class="col-xs-2" value="estado_relevamiento.descripcion" estado="">ESTADO <i class="fas fa-sort"></i></th>
                              <th class="col-xs-3">ACCIÓN </th>
                            </tr>
                          </thead>
                          <tbody id="cuerpoTabla" style="height: 250px;">

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
                   <a href="" id="btn-nuevoLayoutParcial" style="text-decoration: none;">
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
    <div class="modal fade" id="modalLayoutParcial" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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

                  <form id="frmLayoutParcial" name="frmLayoutParcial" class="form-horizontal" novalidate="">

                          <div class="row">
                            <div class="col-md-8 col-md-offset-2">
                              <h5>FECHA</h5>
                              <!-- <input id="fechaActual" class="form-control" type="text" value=""> -->
                              <input id="fechaActual" style="text-align:center" type='text' class="form-control" readonly>
                              <input id="fechaDate" type="text" name="" hidden>
                              <br>
                            </div>

                          </div>

                          <div class="row">
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
                            <div class="col-md-6">
                              <h5>SECTOR</h5>
                              <select id="sector" class="form-control selectSector" name="">
                                  @if(count($casinos) == 1)
                                    @foreach($casinos[0]->sectores as $sector)
                                      <<option value="{{$sector->id_sector}}">{{$sector->descripcion}}</option>
                                    @endforeach
                                  @else
                                  <option value="">-Seleccione un casino-</option>
                                  @endif
                              </select>
                              <br> <span id="alertaSector" class="alertaSpan"></span>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-6">
                              <h5>MÁQUINAS</h5>
                              <!-- <input id="cantidad_maquinas" type="text" class="form-control" name="" value=""> -->
                              <div class="input-group number-spinner">
                                <span class="input-group-btn">
                                  <!-- <button class="btn btn-default" data-dir="dwn"><span class="glyphicon glyphicon-minus"></span></button> -->
                                  <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="dwn">-</button>
                                </span>
                                <input id="cantidad_maquinas" type="text" class="form-control text-center" value="10">
                                <span class="input-group-btn">
                                  <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="up">+</button>
                                </span>
                              </div>
                            </div>

                            <div class="col-md-6">
                              <h5>FISCALIZADORES</h5>

                              <div class="input-group number-spinner">
                                <span class="input-group-btn">
                                  <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="dwn">-</button>
                                </span>
                                <input id="cantidad_fiscalizadores" type="text" class="form-control text-center" value="1">
                                <span class="input-group-btn">
                                  <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="up">+</button>
                                </span>
                              </div>

                            </div>
                          </div>

                  </form>

                  <div id="iconoCarga" class="sk-folding-cube">
                    <div class="sk-cube1 sk-cube"></div>
                    <div class="sk-cube2 sk-cube"></div>
                    <div class="sk-cube4 sk-cube"></div>
                    <div class="sk-cube3 sk-cube"></div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-generar" value="nuevo">GENERAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <input type="hidden" id="existeLayoutParcial" name="id_casino" value="0">
                  <input type="hidden" id="id_casino" name="id_casino" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>
    <div class="modal" id="modalConfirmacion" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                  <div class="modal-header modalNuevo">
                      <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                      <h3 class="modal-title"> NUEVO RELEVAMIENTO</h3>
                  </div>
                  <!-- esto funcinaba en el momento que se dejaba generar de todas formas el relevamiento, se cambio para evitar que el usuario puede regenerarlo
                  <div class="modal-body">
                        <h5 style="padding:10px;font-family:Roboto-Condensed;color:#FF1744 !important;font-size:24px;">ATENCIÓN</h5>
                        <h5 style="padding:0px;font-family:Roboto-Condensed;color:#444 !important;font-size:20px;">YA EXISTE RELEVAMIENTO PARA EL SECTOR SELECCIONADO</h5>

                      <p style="font-family:Roboto-Regular;font-size:16px;margin:20px 0px;">
                        Si vuelve a generar el relevamiento se sobreescribirán los datos anteriores y se perderán las planillas de relevamiento generadas anteriormente.
                      </p>
                       
                      <p style="font-family:Roboto-Regular;font-size:16px;margin-bottom:20px;">
                        ¿Desea generar el relevamiento de todas formas?
                      </p>
                  </div>
                  <div class="modal-footer">
                    <button id="btn-generarIgual" type="button" class="btn btn-successAceptar" value="nuevo">GENERAR DE TODAS FORMAS</button>
                    <button id="btn-cancelarConfirmacion" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  </div>

                   Modal Relevamientos -->
                  <!-- para reutilizar el modal anterior se cambio el nombre del boton cancelar a aceptar, donde se comporta como el cancelar antiguo -->
                   <div class="modal-body">
                    <h5 style="padding:10px;font-family:Roboto-Condensed;color:#FF1744 !important;font-size:24px;">ATENCIÓN</h5>
                    <h5 style="padding:0px;font-family:Roboto-Condensed;color:#444 !important;font-size:20px;">YA EXISTE RELEVAMIENTO PARA EL SECTOR SELECCIONADO</h5>

                  <p style="font-family:Roboto-Regular;font-size:16px;margin:20px 0px;">
                    Deberá finalizar el relevamiento existente para poder generar uno nuevo.
                  </p>
              </div>
              <div class="modal-footer">
                <button id="btn-cancelarConfirmacion" type="button" class="btn btn-successAceptar" data-dismiss="modal">ACEPTAR</button>
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
                            </div>
                            <div class="col-md-6">
                              <h5>SECTOR</h5>
                              <select id="sectorSinSistema" class="form-control select" name="">
                                <option value=""></option>
                              </select>
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
          <div class="modal-dialog" style="width:95%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FF6E40;">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <!-- <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button> -->
                 <button id="btn-minimizarCargar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">CARGAR CONTROL LAYOUT</h3>
                </div>

                <div  id="colapsadoCargar" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmCargaControlLayout" name="frmCargaLayout" class="form-horizontal" novalidate="">

                          <div class="row">
                            <div class="col-lg-2 col-lg-offset-1">
                              <h5>FECHA DE CONTROL LAYOUT</h5>
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
                              <h5>SECTOR</h5>
                              <input id="cargaSector" type='text' class="form-control" readonly>
                               <span id="alertaSector" class="alertaSpan"></span>
                            </div>
                            <div class="col-lg-2">
                              <h5>SUB RELEVAMIENTO</h5>
                              <input id="cargaSubrelevamiento" type='text' class="form-control" readonly>
                               <span id="alertaSubrelevamiento" class="alertaSpan"></span>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-2 col-md-offset-1">
                                <h5>FISCALIZADOR CARGA</h5>
                                <input id="fiscaCarga" type="text"class="form-control" readonly>
                            </div>
                            <div class="col-md-2">
                                <h5>FISCALIZADOR TOMA</h5>

                                <!-- amda -->
                                <!-- <div class = "input-group">
                                   <input id="inputFisca" data-fisca="" class="form-control" size="100" type="text" list="datalistFisca" autocomplete="off"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" />
                                   <span class = "input-group-btn">
                                      <button id="cancelarFisca" class="btn btn-danger" type="button" style="border:1px solid #ccc;"><i class="fa fa-times"></i></button>
                                   </span>
                                </div> -->

                                <!-- prueba -->
                                <input id="inputFisca" class="form-control" type="text" autocomplete="off">
                                <!-- <div class="input-group">
                                   <input id="inputFisca" data-fisca="" class="form-control" size="100" type="text" list="datalistFisca" autocomplete="off"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" />
                                   <span id="cancelarFisca" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-times"></i></span>
                                </div> -->

                                <datalist id="datalistFisca"></datalist>
                            </div>
                            <div class="col-md-2">
                                <h5>TÉCNICO</h5>
                                <input id="tecnico"  type="text"class="form-control">
                            </div>
                            <div class="col-md-3">
                                <h5>FECHA EJECUCIÓN</h5>


                                <!-- anda -->
                                   <div class='input-group date' id='dtpFecha' data-link-field="fecha_ejecucion" data-date-format="dd MM yyyy HH:ii" data-link-format="yyyy-mm-dd HH:ii">
                                       <input type='text' class="form-control" placeholder="Fecha de ejecución del control" id="fecha"  />
                                       <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                       <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                   </div>
                                   <input type="hidden" id="fecha_ejecucion" value=""/>

                                <!-- <div class='input-group date' id='dtpFecha' data-link-field="fecha_ejecucion" data-date-format="dd MM yyyy HH:ii" data-link-format="yyyy-mm-dd HH:ii">
                                  <input type='text' class="form-control" placeholder="Fecha ejecución" id="fecha"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" />
                                  <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                  </span>
                                  <input type="hidden" id="fecha_ejecucion" value=""/>
                                </div> -->
                            </div>
                          </div>

                          <br><br>

                          <div class="row">
                              <div class="col-md-12">
                                  <p style="font-family:'Roboto-Regular';font-size:16px;margin-left:20px;">
                                     <i class="fa fa-fw fa-exclamation" style="color:#2196F3"></i> Haga doble click sobre los campos para entrar y salir del modo edición.
                                  </p>
                              </div>
                          </div>

                          <br>

                          <!-- MODELO DE FILA PROGRESIVO -->
                          <div hidden class="row rowProgresivo" style="margin: 0px 0px 10px 0px !important;">
                              <div class="col-md-12">
                                  <center><h6 style="font-family:'Roboto-Condensed'; font-size:16px; color:black;">PROGRESIVO</h6></center>
                              </div>
                              <div class="col-md-2 col-md-offset-2">
                                  <h5>NOMBRE DE PROGRESIVO</h5>
                                  <input class="form-control nombre_progresivo inputLayout" type="text" value="Nombre de progresivo 1" readonly="true">
                              </div>
                              <div class="col-md-2">
                                  <h5>TIPO</h5>
                                  <input class="form-control tipo_progresivo inputLayout" type="text" value="750000" readonly="true">
                              </div>
                              <div class="col-md-2">
                                  <h5>MÁXIMO</h5>
                                  <input class="form-control maximo_progresivo inputLayout" type="text" value="750000" readonly="true">
                              </div>
                              <div class="col-md-2">
                                  <h5>% RECUPERACIÓN</h5>
                                  <input class="form-control recuperacion_progresivo inputLayout" type="text" value="10" readonly="true">
                              </div>
                          </div>

                          <!-- MODELO DE FILA NIVELES -->
                          <div hidden class="row rowNivelProgresivo" style="margin: 15px 0px !important;">
                              <div class="col-md-12">
                                  <center><h6 style="font-family:'Roboto-Condensed'; font-size:16px; color:black;">NIVELES</h6></center>
                              </div>
                              <div class="col-md-8 col-md-offset-2">
                                <table class="table tablaNivelProgresivo">
                                    <thead>
                                        <tr>
                                          <th>N° NIVEL</th>
                                          <th>NOMBRE DE NIVEL</th>
                                          <th>BASE</th>
                                          <th>% OCULTO</th>
                                          <th>% VISIBLE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr hidden class="filaNivel">
                                          <td><input class="form-control nro_nivel inputLayout" type="text" value="1" readonly="true"></td>
                                          <td><input class="form-control nombre_nivel inputLayout" type="text" value="Nivel 1" readonly="true"></td>
                                          <td><input class="form-control base_nivel inputLayout" type="text" value="1012304" readonly="true"></td>
                                          <td><input class="form-control porc_oculto inputLayout" type="text" value="14" readonly="true"></td>
                                          <td><input class="form-control porc_visible inputLayout" type="text" value="23" readonly="true"></td>
                                        </tr>
                                    </tbody>
                                </table>
                              </div>
                          </div>


                          <div class="row">
                                <div class="col-md-12">
                                    <table id="tablaMaquinasLayouts" class="table">
                                        <thead>
                                          <tr>
                                              <th width="6%">MTM</th>
                                              <th width="6%">ISLA</th>
                                              <th width="10%">FABRICANTE</th>
                                              <th width="10%">PAQUETE-JUEGO</th>
                                              <th width="25%">JUEGO</th>
                                              <th width="15%">N° SERIE</th>
                                              <th width="4%">NT</th>
                                              <th width="9%">D. SALA</th>
                                              <th width="9%">% DEV</th>
                                              <th width="6%">PROG</th>
                                          </tr>
                                        </thead>
                                        <tbody>

                                          <style>
                                              .inputLayout {
                                                  /*padding-left: 22px;*/
                                                  padding: 6px;
                                                  text-align: center;
                                              }

                                              .noSelect {
                                                -moz-user-select: none;
                                                -khtml-user-select: none;
                                                -webkit-user-select: none;
                                                -ms-user-select: none;
                                                user-select: none;
                                              }

                                              .checkLayout {
                                                  display: inline !important;
                                                  position: relative !important;
                                                  top: -30px !important;
                                                  left: 2px !important;
                                              }

                                              .checkboxLayout {
                                                  position: relative;
                                                  left: 2px !important; top: 0px !important;
                                              }

                                              #tablaMaquinasLayouts th {
                                                text-align: center;
                                              }

                                              #tablaMaquinasLayouts td {
                                                text-align: center;
                                                  padding: 10px 4px !important;
                                              }
                                          </style>


                                          <tr>
                                              <td>
                                                <a class="pop modificado" title="VALOR DEL SISTEMA" data-placement="top" data-trigger="hover" data-content="2345">
                                                  <input class="form-control inputLayout" data-original="2345" type="text" value="2345" readonly="true">
                                                </a>
                                              </td>
                                              <td><input class="form-control inputLayout" data-original="18" type="text" value="18" readonly="true"></td>
                                              <td><input class="form-control inputLayout" data-original="ARISTOCRAT" type="text" value="ARISTOCRAT" readonly="true"></td>
                                              <td><input class="form-control inputLayout" data-original="Golden Knight 40 Lines" type="text" value="Golden Knight 40 Lines" readonly="true"></td>
                                              <td><input class="form-control inputLayout" data-original="MAV2002311" type="text" value="MAV2002311" readonly="true"></td>
                                              <td><input class="checkboxLayout" type="checkbox" value=""></td>
                                              <td><input class="form-control" type="text" value=""></td>
                                              <td><input class="form-control" type="text" value=""></td>
                                          </tr>
                                        </tbody>
                                    </table>
                                </div>
                          </div>


                          <div id="contenedorMaquinas">

                          </div> <!-- ./contenedorMaquinas -->

                          <br>
                          <div class="row">
                              <div class="col-md-8 col-md-offset-2">
                                <h5>OBSERVACIONES</h5>
                                <textarea id="observacion_carga" class="form-control" style="resize:vertical;"></textarea>
                              </div>
                          </div>


                  </form>

                </div>
                <div class="modal-footer">
                  <!-- <div class="mensajeSalida">
                      <span style="font-family:'Roboto-Black'; color:#EF5350;">CAMBIOS SIN GUARDAR</span>
                      <br>
                      <span style="font-family:'Roboto-Regular'; color:#555;">Presione SALIR nuevamente para salir sin guardar cambios.</span>
                      <br>
                      <span style="font-family:'Roboto-Regular'; color:#555;">Presione GUARDAR TEMPORALMENTE para guardar los cambios.</span>
                  </div>
                  <br> -->
                  <button type="button" class="btn btn-warningModificar" id="btn-finalizar" value="nuevo">FINALIZAR RELEVAMIENTO</button>
                  <button type="button" class="btn btn-default" id="btn-salir">SALIR</button>
                  <input type="hidden" id="id_layout_parcial" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal validar relevamientos -->
    <div class="modal fade" id="modalValidarControl" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:94%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#69F0AE;">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarValidar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoValidar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">VALIDAR CONTROL LAYOUT</h3>
                </div>

                <div  id="colapsadoValidar" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmValidarControlLayout" name="frmValidarControlLayout" class="form-horizontal" novalidate="">

                          <div class="row">
                            <div class="col-lg-2 col-lg-offset-2">
                              <h5>FECHA</h5>
                              <input id="validarFechaActual" type='text' class="form-control" readonly>
                              <br>
                            </div>
                            <div class="col-lg-2">
                              <h5>CASINO</h5>
                              <input id="validarCasino" type='text' class="form-control" readonly>
                              <br> <span id="alertaCasino" class="alertaSpan"></span>
                            </div>
                            <div class="col-lg-2">
                              <h5>SECTOR</h5>
                              <input id="validarSector" type='text' class="form-control" readonly>
                              <br> <span id="alertaSector" class="alertaSpan"></span>
                            </div>
                            <div class="col-lg-2">
                                <h5>SUB</h5>
                                <input id="validarSub" class="form-control" type="text" readonly>
                            </div>

                          </div>

                          <div class="row">
                              <div class="col-lg-2 col-lg-offset-2">
                                  <h5>FISCALIZADOR CARGA</h5>
                                  <input id="validarFiscaCarga" type="text"class="form-control" readonly>
                              </div>
                              <div class="col-lg-2">
                                  <h5>FISCALIZADOR TOMA</h5>
                                  <input id="validarFiscaToma" type="text"class="form-control" readonly>
                              </div>
                              <div class="col-lg-2">
                                  <h5>TÉCNICO</h5>
                                  <input id="validarTecnico" type="text"class="form-control" readonly>
                              </div>
                              <div class="col-lg-2">
                                <h5>FECHA EJECUCIÓN</h5>
                                <input id="validarFechaEjecucion" type='text' class="form-control" readonly>
                                <br>
                              </div>
                          </div>

                          <br><br>


                          <!-- MODELO DE FILA PROGRESIVO -->
                          <div hidden class="row rowProgresivo" style="margin: 0px 0px 10px 0px !important;">
                              <div class="col-md-12">
                                  <center><h6 style="font-family:'Roboto-Condensed'; font-size:16px; color:black;">PROGRESIVO</h6></center>
                              </div>
                              <div class="col-md-2 col-md-offset-2">
                                  <h5>NOMBRE DE PROGRESIVO</h5>
                                  <input class="form-control nombre_progresivo" type="text" value="Nombre de progresivo 1" readonly="true">
                              </div>
                              <div class="col-md-2">
                                  <h5>TIPO</h5>
                                  <input class="form-control tipo_progresivo" type="text" value="750000" readonly="true">
                              </div>
                              <div class="col-md-2">
                                  <h5>MÁXIMO</h5>
                                  <input class="form-control maximo_progresivo" type="text" value="750000" readonly="true">
                              </div>
                              <div class="col-md-2">
                                  <h5>% RECUPERACIÓN</h5>
                                  <input class="form-control recuperacion_progresivo" type="text" value="10" readonly="true">
                              </div>
                          </div>

                          <!-- MODELO DE FILA NIVELES -->
                          <div hidden class="row rowNivelProgresivo" style="margin: 15px 0px !important;">
                              <div class="col-md-12">
                                  <center><h6 style="font-family:'Roboto-Condensed'; font-size:16px; color:black;">NIVELES</h6></center>
                              </div>
                              <div class="col-md-8 col-md-offset-2">
                                <table class="table tablaNivelProgresivo">
                                    <thead>
                                        <tr>
                                          <th>N° NIVEL</th>
                                          <th>NOMBRE DE NIVEL</th>
                                          <th>BASE</th>
                                          <th>% OCULTO</th>
                                          <th>% VISIBLE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr hidden class="filaNivel">
                                          <td><input class="form-control nro_nivel" type="text" value="1" readonly="true"></td>
                                          <td><input class="form-control nombre_nivel" type="text" value="Nivel 1" readonly="true"></td>
                                          <td><input class="form-control base_nivel" type="text" value="1012304" readonly="true"></td>
                                          <td><input class="form-control porc_oculto" type="text" value="14" readonly="true"></td>
                                          <td><input class="form-control porc_visible" type="text" value="23" readonly="true"></td>
                                        </tr>
                                    </tbody>
                                </table>
                              </div>
                          </div>




                          <div class="row">
                                <div class="col-md-12">
                                    <table id="tablaMaquinasLayouts" class="table">
                                        <thead>
                                          <tr>
                                              <th width="6%">MTM</th>
                                              <th width="6%">ISLA</th>
                                              <th width="10%">FABRICANTE</th>
                                              <th width="10%">PAQUETE-JUEGO</th>
                                              <th width="25%">JUEGO</th>
                                              <th width="15%">N° SERIE</th>
                                              <th width="4%">NO</th>
                                              <th width="9%">D. SALA</th>
                                              <th width="9%">% DEV</th>
                                              <th width="6%">PROG</th>
                                          </tr>
                                        </thead>
                                        <tbody>

                                          <style>
                                              .inputLayout {
                                                  /*padding-left: 22px;*/
                                                  padding: 6px;
                                                  text-align: center;
                                              }

                                              .noSelect {
                                                -moz-user-select: none;
                                                -khtml-user-select: none;
                                                -webkit-user-select: none;
                                                -ms-user-select: none;
                                                user-select: none;
                                              }

                                              .checkLayout {
                                                  display: inline !important;
                                                  position: relative !important;
                                                  top: -30px !important;
                                                  left: 2px !important;
                                              }

                                              .checkboxLayout {
                                                  position: relative;
                                                  left: 2px !important; top: 0px !important;
                                              }

                                              #tablaMaquinasLayouts th {
                                                text-align: center;
                                              }

                                              #tablaMaquinasLayouts td {
                                                text-align: center;
                                                  padding: 10px 4px !important;
                                              }
                                          </style>


                                          <tr>
                                              <td>
                                                <a class="pop modificado" title="VALOR DEL SISTEMA" data-placement="top" data-trigger="hover" data-content="2345">
                                                  <input class="form-control inputLayout" data-original="2345" type="text" value="2345" readonly="true">
                                                </a>
                                              </td>
                                              <td><input class="form-control inputLayout" data-original="18" type="text" value="18" readonly="true"></td>
                                              <td><input class="form-control inputLayout" data-original="ARISTOCRAT" type="text" value="ARISTOCRAT" readonly="true"></td>
                                              <td><input class="form-control inputLayout" data-original="Golden Knight 40 Lines" type="text" value="Golden Knight 40 Lines" readonly="true"></td>
                                              <td><input class="form-control inputLayout" data-original="MAV2002311" type="text" value="MAV2002311" readonly="true"></td>
                                              <td><input class="checkboxLayout" type="checkbox" value=""></td>
                                              <td><input class="form-control" type="text" value=""></td>
                                              <td><input class="form-control" type="text" value=""></td>
                                          </tr>
                                        </tbody>
                                    </table>
                                </div>
                          </div>

                          <div id="contenedorMaquinas">

                          </div> <!-- ./contenedorMaquinas -->
                          <br>
                          <div class="row">
                              <div class="col-md-8 col-md-offset-2">
                                <h5>OBSERVACIONES FISCALIZACIÓN</h5>
                                <textarea id="observacion_fiscalizacion" class="form-control" style="resize:vertical;" disabled></textarea>
                              </div>
                          </div>
                          <br>
                          <div class="row">
                              <div class="col-md-8 col-md-offset-2">
                                <h5>OBSERVACIONES</h5>
                                <textarea id="observacion_validacion" class="form-control" style="resize:vertical;"></textarea>
                              </div>
                          </div>
                  </form>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-validarRelevamiento" value="nuevo">VALIDAR RELEVAMIENTO</button>
                  <button type="button" class="btn btn-default" id="btn-salirValidacion" data-dismiss="modal">SALIR</button>
                  <input type="hidden" id="id_layout_parcial" value="0">
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




    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| LAYOUT PARCIAL</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Layout Parcial</h5>
      <p>
        Puede crearse un layout parcial por sector, con una cantidad definida de máquinas y fiscalizadores, donde salen a relevar esta información con las planillas generadas
        por el sistema. Además de tener la posibilidad de trabajar sin sistema, donde se producen planillas para los relevamientos de los próximos 7 días.
      </p>
      <h5>Edición de planillas</h5>
      <p>
        De manera aleatoria, se generan las cantidades de máquinas designadas para obtener su información, detallados el sector, n° admin, su isla y el juego asociado.
        Luego, en la tabla siguiente, podrán describirse los errores posibles que se obtengan en su toma de valores de dichas máquinas.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionLayoutParcial.js?5" charset="utf-8"></script>
    <script src="js/paginacion.js" charset="utf-8"></script>

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    <script src="js/inputSpinner.js" type="text/javascript"></script>
    <script src="js/lista-datos.js" type="text/javascript"></script>
    @endsection
