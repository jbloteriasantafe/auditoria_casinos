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
<datalist id="datalist_fiscalizadores">
  @foreach ($casinos as $casino)
  @foreach($fiscalizadores[$casino->id_casino] as $u)
  <option data-id="{{$u['id_usuario']}}" data-id-casino="{{$casino->id_casino}}">{{$u['nombre']}}</option>
  @endforeach
  @endforeach
</datalist>

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
                        <h4>RELEVAMIENTO DE PROGRESIVOS GENERADO POR EL SISTEMA</h4>
                      </div>

                      <div class="panel-body">
                        <table id="tablaRelevamientos" class="table table-fixed tablesorter">
                          <thead>
                            <tr>
                              <th class="col-xs-2 activa" value="relevamiento_progresivo.fecha" estado="desc">FECHA <i class="fa fa-sort-desc"></i></th>
                              <th class="col-xs-2" value="casino.nombre" estado="">CASINO  <i class="fa fa-sort"></i></th>
                              <th class="col-xs-2" value="sector.descripcion" estado="">SECTOR <i class="fa fa-sort"></i></th>
                              <th class="col-xs-1" value="relevamiento_progresivo.sub_control" estado="">SUB <i class="fa fa-sort"></i></th>
                              <th class="col-xs-2" value="estado_relevamiento.descripcion" estado="">ESTADO <i class="fa fa-sort"></i></th>
                              <th class="col-xs-3">ACCIÓN </th>
                            </tr>
                          </thead>
                          <tbody id="cuerpoTabla" style="height: 250px;">
                            <tr class='filaEjemplo' style="">
                              <td class="col-xs-2 fecha">
                                01 Ene 9999
                              </td>
                              <td class="col-xs-2 casino">
                                EJEMPLO
                              </td>
                              <td class="col-xs-2 sector">
                                SECTOR999
                              </td>
                              <td class="col-xs-1 subcontrol">
                                99
                              </td>
                              <td class="col-xs-2">
                                <i class="fas fa-fw fa-dot-circle iconoEstado"></i>
                                <span class="textoEstado">EJEMPLO</span>
                              </td>
                              <td class="col-xs-3 acciones">
                                <button class="btn btn-info planilla" type="button">
                                  <i class="far  fa-fw fa-file-alt"></i></button>
                                <span></span>
                                <button class="btn btn-warning carga" type="button">
                                  <i class="fa fa-fw fa-upload"></i></button>
                                <span></span>
                                <button class="btn btn-success validar" type="button">
                                  <i class="fa fa-fw fa-check"></i></button>
                                <span></span>
                                <button class="btn btn-info imprimir" type="button">
                                  <i class="fa fa-fw fa-print"></i></button>
                              </td>

                            </tr>

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
                   <a href="" id="btn-nuevo" style="text-decoration: none;">
                    <div class="panel panel-default panelBotonNuevo">
                        <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                        <div class="backgroundNuevo"></div>
                        <div class="row">
                            <div class="col-xs-12">
                              <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">GENERAR RELEVAMIENTOS DE PROGRESIVOS</h4>
                              </center>
                            </div>
                        </div>
                    </div>
                   </a>
                  </div>
              </div>

              <div class="row">
                  <div class="col-md-12">
                   <a href="" id="btn-relevamientoSinSistema" style="text-decoration: none;">
                    <div class="panel panel-default panelBotonNuevo">
                        <center><img class="imgNuevo" src="/img/logos/relevamientos_sin_sistema_white.png"><center>
                        <div class="backgroundNuevo"></div>
                        <div class="row">
                            <div class="col-xs-12">
                              <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">GENERAR RELEVAMIENTOS DE PROGRESIVOS SIN SISTEMA</h4>
                              </center>
                            </div>
                        </div>
                    </div>
                   </a>
                  </div>
              </div>

            </div>

        </div>  <!-- /#row -->

    <!--MODAL CREAR RELEVAMIENTO -->
    <div class="modal fade" id="modalRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarCrear" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrear" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title" style="background-color: #6dc7be;">| NUEVO RELEVAMIENTO PROGRESIVOS</h3>
                </div>

                <div  id="colapsadoCrear" class="collapse in">

                <div class="modal-body modalCuerpo">
                          <div class="row">
                            <div class="col-md-8 col-md-offset-2">
                              <h5>FECHA</h5>
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
                                      <option value="{{$sector->id_sector}}">{{$sector->descripcion}}</option>
                                    @endforeach
                                  @else
                                  <option value="">-Seleccione un casino-</option>
                                  @endif
                              </select>
                              <br> <span id="alertaSector" class="alertaSpan"></span>
                            </div>
                          </div>

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
              </div>
            </div>
      </div>
    <!-- Modal Relevamientos -->
    <div class="modal fade" id="modalRelevamientoSinSistema" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarSinSistema" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoSinSistema" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title" style="background-color: #6dc7be;">| NUEVO CONTROL LAYOUT SIN SISTEMA</h3>
                </div>

                <div  id="colapsadoSinSistema" class="collapse in">

                <div class="modal-body modalCuerpo">
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
    <div class="modal fade" id="modalCargaRelevamientoProgresivos" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:95%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FF6E40;">
                 <button id="btn-minimizarCargar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title">CARGAR CONTROL LAYOUT</h3>
                </div>

                <div  id="colapsadoCargar" class="collapse in">

                <div class="modal-body modalCuerpo">
                          <div class="row">
                            <div class="col-lg-2">
                              <h5>FECHA DE GENERACIÓN</h5>
                              <input id="cargaFechaGeneracion" type='text' class="form-control" readonly>
                            </div>
                            <div class="col-lg-2">
                              <h5>CASINO</h5>
                              <input id="cargaCasino" type='text' class="form-control" readonly>
                            </div>
                            <div class="col-lg-2">
                              <h5>SECTOR</h5>
                              <input id="cargaSector" type='text' class="form-control" readonly>
                            </div>
                            <div class="col-lg-2">
                              <h5>SUB RELEVAMIENTO</h5>
                              <input id="cargaSubrelevamiento" type='text' class="form-control" readonly>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-2 col-md-offset-1">
                                <h5>FISCALIZADOR CARGA</h5>
                                <input id="fiscaCarga" type="text"class="form-control" readonly>
                            </div>
                            <div class="col-md-2">
                                <h5>FISCALIZADOR TOMA</h5>
                                <input id="inputFisca" class="form-control" type="text" autocomplete="off">
                            </div>
                            <div class="col-md-2">
                                <h5>TÉCNICO</h5>
                                <input id="tecnico"  type="text"class="form-control">
                            </div>
                            <div class="col-md-3">
                                <h5>FECHA EJECUCIÓN</h5>
                                   <div class='input-group date' id='dtpFecha' data-link-field="fecha_ejecucion" data-date-format="dd MM yyyy HH:ii" data-link-format="yyyy-mm-dd HH:ii">
                                       <input type='text' class="form-control" placeholder="Fecha de ejecución del control" id="fecha" autocomplete="off"/>
                                       <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                       <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                   </div>
                                   <input type="hidden" id="fecha_ejecucion" value=""/>
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
                          <div class="row">
                              <div class="col-xs-2 col-xs-offset-1">
                                  <h4>Nro Isla</h4>
                              </div>
                              <div class="col-xs-2">
                                <h4>Nombre Progresivo</h4>
                              </div>
                              <div class="col-xs-2">
                                <h4>Nombre Nivel</h4>
                              </div>
                              <div class="col-xs-2">
                                <h4>Base</h4>
                              </div>
                              <div class="col-xs-2">
                                <h4>Valor Actual</h4>
                              </div>

                          </div>
                          <div class="row">
                            <div id="contenedor_progresivos" class="col-md-12">


                            </div>
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
                  <button type="button" class="btn btn-warningModificar" id="btn-finalizar" value="nuevo">FINALIZAR RELEVAMIENTO</button>
                  <button type="button" class="btn btn-default" id="btn-salir">SALIR</button>
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal validar layout -->
    <div class="modal fade" id="modalValidarRelevamientoProgresivos" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:95%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#69F0AE;">
                 <button id="btn-minimizarValidar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargarValidar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title">| VALIDAR RELEVAMIENTO DE PROGRESIVOS </h3>
                </div>

                <div  id="colapsadoCargarValidar" class="collapse in">

                <div class="modal-body modalCuerpo">
                          <div class="row">
                            <div class="col-lg-2 col-lg-offset-1">
                              <h5>FECHA DE CONTROL LAYOUT</h5>
                              <input id="validacionFechaActual" type='text' class="form-control" readonly>
                            </div>
                            <div class="col-lg-2">
                              <h5>FECHA DE GENERACIÓN</h5>
                              <input id="validacionFechaGeneracion" type='text' class="form-control" readonly>
                            </div>
                            <div class="col-lg-2">
                              <h5>CASINO</h5>
                              <input id="validacionCasino" type='text' class="form-control" readonly>
                            </div>
                            <div class="col-lg-2">
                              <h5>SECTOR</h5>
                              <input id="validacionSector" type='text' class="form-control" readonly>
                            </div>
                            <div class="col-lg-2">
                              <h5>SUB RELEVAMIENTO</h5>
                              <input id="validacionSubrelevamiento" type='text' class="form-control" readonly>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-2 col-md-offset-1">
                                <h5>FISCALIZADOR CARGA</h5>
                                <input id="validacionFiscaCarga" type="text"class="form-control" readonly>
                            </div>
                            <div class="col-md-2">
                                <h5>FISCALIZADOR TOMA</h5>
                                <input id="validacionInputFisca" class="form-control" type="text" autocomplete="off">
                            </div>
                            <div class="col-md-2">
                                <h5>TÉCNICO</h5>
                                <input id="validacionTecnico"  type="text"class="form-control">
                            </div>
                            <div class="col-md-3">
                                <h5>FECHA EJECUCIÓN</h5>
                                <input id="validacionFechaEjecucion" type="text"class="form-control" readonly>
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
                          <div class="row">
                            <div id="validacion_contenedor_progresivos" class="col-md-12">

                            </div>
                          </div>
                          <br>
                          <div class="row">
                              <div class="col-md-8 col-md-offset-2">
                                <h5>OBSERVACIONES</h5>
                                <textarea id="observacion_validacion" class="form-control" style="resize:vertical;"></textarea>
                              </div>
                          </div>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-warningModificar" id="btn-finalizarValidacion" value="nuevo">FINALIZAR RELEVAMIENTO</button>
                  <button type="button" class="btn btn-default" id="btn-salir" data-dismiss="modal">SALIR</button>
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
                 <button id="btn-minimizarPlanilla" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoPlanilla" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">IMPRIMIR PLANILLA</h3>
                </div>

                <div  id="colapsadoPlanilla" class="collapse in">

                <div class="modal-body modalCuerpo">
                          <div class="row">
                              <div class="col-md-12">
                                  <!-- Carga de archivos! | Uno para el modal de nuevo y otro para modificar -->
                                  <div class="zona-file-lg">
                                      <input id="cargaArchivo" data-borrado="false" type="file" multiple>
                                  </div>

                                  <div class="alert alert-danger fade in" role="alert" id="alertaArchivo"><span></span></div>
                              </div>
                          </div>


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
    <h3 class="modal-title" style="color: #fff;">| RELEVAMIENTO DE PROGRESIVOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Relevamiento de progresivos</h5>
      <p>
        Genera relevamientos aleatorios de progresivos dentro del casino, donde se deberán completar
        las planillas de informes, y luego, cargarlas según correspondan dichos datos.
        También se encuentra la opción de generar estos relevamientos sin sistema, donde se descargará
        un archivo .zip posterior a 7 días para relevar.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->


    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionRelevamientosProgresivos.js" charset="utf-8"></script>
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
