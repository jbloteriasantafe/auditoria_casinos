@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/lista-datos.css">
@endsection

<div class="col-xl-9">
    <!-- FILTROS -->
    <div class="row">
      <div class="col-md-12">


        <div class="panel panel-default">

          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
            <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
          </div>

          <div id="collapseFiltros" class="panel-collapse collapse">

            <div class="panel-body">

              <div class="row">

                <div class="col-lg-3">
                  <h5>Tipo intervención</h5>
                  <select class="form-control" id="B_TipoEventualidad">
                    <option value="0" selected>- Seleccione tipo intervención -</option>
                    @foreach ($tiposEventualidades as $t_ev)
                    <option value="{{$t_ev->id_tipo_eventualidad}}">{{$t_ev->descripcion}}</option>
                    @endforeach
                    <!-- <option value=" " >- Todos los movimientos -</option> -->
                  </select>

                </div>
                <div class="col-lg-3">
                  <h5>Fecha</h5>
                  <div class="form-group">
                    <div class='input-group date' id='dtpFechaEv' data-link-field="fecha_eventualidad" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                      <input type='text' class="form-control" placeholder="Fecha de Eventualidad" id="B_fecha_ev"/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="fechaEventualidad" value="0"/>
                  </div>
                </div>

                <div class="col-lg-3">
                  <h5>Casino</h5>
                  <select class="form-control" id="B_CasinoEv">
                    <option value="">Todos los casinos</option>
                    @foreach ($casinos as $idx => $casino)
                    <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                    @endforeach
                  </select>

                </div>

                <div class="col-lg-3">
                  <h5>Turno</h5>
                  <input class="form-control" id="B_TurnoEventualidad">
                </div>
              </div> <!-- row / formulario -->

              <br>

              <div class="row">
                <div class="col-md-12">
                  <center>
                    <button id="btn-buscarEventualidad" class="btn btn-infoBuscar" type="button" name="button">
                      <i class="fa fa-fw fa-search"></i> BUSCAR
                    </button>
                  </center>
                </div>
              </div> <!-- row / botón buscar -->




            </div> <!-- panel-body -->
          </div> <!-- collapse -->



        </div> <!-- .panel-default -->
      </div> <!-- .col-md-12 -->

    </div> <!-- .row / FILTROS -->

    <!-- TABLA -->
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>ÚLTIMAS INTERVENCIONES TÉCNICAS INGRESADAS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosEv" class="table table-fixed tablesorter">
              <thead>
                <tr>
                  <th class="col-xs-2 "  estado="">FECHA <i class="fa fa-sort"></i></th>
                  <th class="col-xs-1 "  estado="">HORA <i class="fa fa-sort"></i></th>
                  <th class="col-xs-2"  estado="">TIPO</th>
                  <th class="col-xs-1">ESTADO</th>
                  <th class="col-xs-2"  estado="">TURNO <i class="fa fa-sort"></i></th>
                  <th class="col-xs-2"  estado="">CASINO</th>
                  <th class="col-xs-2"  estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaEv" style="max-height: 356px;">
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div> <!-- .row / TABLA -->


  </div> <!-- .col-xl-9  | COLUMNA IZQUIERDA - FILTRO Y TABLA -->



<div class="col-xl-3">
    <!-- Botón nueva eventualidad -->
  <div class="row">
    <div class="col-lg-12">
      <a id="btn-nueva-eventualidad" style="text-decoration: none;">
        <div class="panel panel-default panelBotonNuevo">
          <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
            <div class="backgroundNuevo"></div>
              <div class="row">
                <div class="col-xs-12">
                  <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">NUEVA INTERVENCIÓN TÉCNICA</h4>
                  </center>
                </div>
              </div>
        </div>
      </a>
    </div>
  </div> <!-- .row -->

</div>



  <!-- *****************Modal Eliminar ****************************************** -->
  <div class="modal fade" id="modalEliminarEventualidad" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
              <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
            </div>

            <div class="modal-body franjaRojaModal">
              <form id="frmEliminar" name="frmCasino" class="form-horizontal" novalidate="">
                  <div class="form-group error ">
                    <div class="col-xs-12">
                        <strong id="titulo-modal-eliminar">¿Seguro desea eliminar la INTERVENCIÓN?</strong>
                    </div>
                  </div>
              </form>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarEvent" value="0">ELIMINAR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
            </div>
        </div>
      </div>
  </div>



<!-- ***********MODAL PARA CARGAR EVENTUALIDAD ************************-->
<div class="modal fade" id="modalCargarEventualidad" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 90%">
    <div class="modal-content">
      <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
          <h3 class="modal-title modalVerMas" id="myModalLabel"></h3>
      </div>

        <div class="modal-body" style="">
          <div  id="colapsado" class="collapse in">
            <div class="row">
              <div class="col-md-4">
                <h5>Fiscalizador Toma: </h5>
                <div class="row"> <!-- row 2 -->
                  <div class="input-group lista-datos-group">
                    <input id="fiscaToma" class="form-control" type="text" value="" autocomplete="off">
                  </div>
                  <!-- <input id="inputMaq" data-maquina="" class="form-control" type="text" autocomplete="off" placeholder="Buscar máquinas"/> -->
                </div> <!-- fin row2 -->
              </div>
              <div class="col-md-4">
                <h5>Fecha Ejecución: </h5>
                <div class='input-group date' id='evFecha' data-link-field="fecha_ejecucionEv" data-date-format="dd MM yyyy HH:ii" data-link-format="yyyy-mm-dd HH:ii">
                  <input type='text' class="form-control" placeholder="Fecha de ejecución de la eventualidad" id="fechaEv"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" />
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
                <input type="hidden" id="fecha_ejecucionEv" value=""/>
              </div>
              <div class="col-md-4">
                <h5>Tipo de Intervención</h5>
                <select class="form-control" id="tipoEventualidad">
                  <option value=" " selected>- Seleccione tipo eventualidad -</option>
                  <option value="1">FALLA TÉCNICA</option>
                  <option value="2">AMBIENTAL</option>
                </select>
              </div>
            </div>

            <div class="row">

              <div id="seleccion" class="col-xs-4">
                <h5>Seleccione</h5>
                <select class="form-control" id="select_event">
                  <option value="3"> - </option>
                  <option value="0">ISLAS</option>
                  <option value="1">SECTORES</option>
                  <option value="2">MAQUINAS</option>
                </select>
                <br>
              </div>

              <div class="col-xs-7">
              <h5 style="color: white !important"> dfgdfg</h5>
              <div id="inputMaquinaEv" class="input-group">
                <input id="inputMaqui" class="form-control" type="text" value="" autocomplete="off" placeholder="Buscar">
                <span class="input-group-btn">
                  <button id="agregarMaqEv" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                </span>
              </div>
            </div>

              <div class="col-xs-7">
              <div id="inputSectorEv" class="input-group">
                <input id="inputSec" class="form-control" type="text" value="" autocomplete="off" placeholder="Buscar">
                <span class="input-group-btn">
                  <button id="agregarSecEv" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                </span>
              </div>
            </div>

              <div class="col-xs-7">
              <div id="inputIslaEv" class="input-group">
                <input id="inputIs" class="form-control" type="text" value="" autocomplete="off" placeholder="Buscar">
                <span class="input-group-btn">
                  <button id="agregarIsEv" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                </span>
              </div>
            </div>

            </div> <!-- fin row -->
            <div class="row">

              <div class="col-xs-12">
                <table id="tablaCargaEvent" class="table">
                  <thead>
                    <tr>
                      <th id="filainicial" class="col-xs-3"> </th>
                      <th id="segunda_columna" class="col-xs-5" text-align="center"></th>

                    </tr>
                  </thead>
                  <tbody>

                  </tbody>
                </table>
              </div>
              <div class="col-xs-12">
                <div class="pre-scrollable">
                  <table id="tablaCargaCompleta"  class="table table-fixed tablesorter">
                    <thead>
                      <tr>
                        <th class="col-xs-4" text-align="center">Nro Admin </th>
                        <th class="col-xs-5" text-align="center">Sector</th>
                        <th class="col-xs-3" text-align="center">Isla</th>
                      </tr>
                    </thead>
                    <tbody>

                    </tbody style="height: 250px;">
                  </table>
                </div>
              </div>
            </div> <!-- fin row -->
            <div class="row">
              <div class="col-md-12">
                <h5>Observaciones</h5>
                <textarea id="observacionesEv" class="form-control" rows="10" style="resize:none; height:80px;" placeholder="Observaciones"></textarea>
                <span id="alerta_observaciones" class="alertaSpan"></span>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-xs-12">
                <h5>Archivo</h5>
                <!-- Carga de archivos! | Uno para el modal de nuevo y otro para modificar -->
                <div class="zona-file">
                  <input id="cargaInforme" data-borrado="false" type="file">
                </div>
                <div class="alert alert-danger fade in" role="alert" id="alertaArchivo"><span></span></div>
              </div>
            </div>

          </div> <!-- colapsado -->

        </div> <!-- modal body -->

        <div class="modal-footer">
          <input id="id_casino" type="text" name="" value="" hidden="">
          <input id="tipo" type="text" name="" value="" hidden="">
          <input id="id_event" type="text" name="" value="" hidden="">
          <button id="btn-aceptar-carga" type="button" class="btn btn-default" value="" >ACEPTAR</button>
          <button id="btn-aceptar-visado" type="button" class="btn btn-default" value="" hidden="true">VISAR</button>
          <button id="btn-cancelar-carga" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div> <!-- modal footer -->

    </div> <!-- modal content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->



  <!--********************* Modal para CARGAR EVENTUALIDAD DE MÁQUINA*****************************-->
<div class="modal fade" id="modalCargarEvMaquina" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 90%">
    <div class="modal-content">
      <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
          <h3 class="modal-title modalVerMas" id="myModalLabel">Cargar Intervención de Máquina</h3>
      </div> <!-- modal header -->

      <div  id="colapsado" class="collapse in">
        <div class="row"> <!-- PRIMER FILA-->
          <div class="col-md-3">
            <h5>Fiscalizador Toma: </h5>
            <div class="row"> <!-- row 2 -->
              <div class="input-group lista-datos-group">
                <input id="fiscaToma" class="form-control" type="text" value="" autocomplete="off">
              </div>
                <!-- <input id="inputMaq" data-maquina="" class="form-control" type="text" autocomplete="off" placeholder="Buscar máquinas"/> -->
            </div> <!-- fin row2 -->
          </div>

          <div class="col-md-3">
            <h5>Fiscalizador Carga: </h5>
            <div class="row"> <!-- row 3 -->
              <input id="fiscaCarga" type="text"class="form-control">
            </div> <!-- fin row 3 -->
          </div>

          <div class="col-md-3">
            <h5>Tipo Movimiento </h5>
            <select id="tipoMov" class="form-control" name="">
                <option value="9"> - </option>
                <option value="1"> INGRESO </option>
                <option value="2"> EGRESO </option>
                <option value="3"> REINGRESO </option>
                <option value="4"> CAMBIO LAYOUT </option>
                <option value="5"> DENOMINACIÓN </option>
                <option value="6"> % DEVOLUCIÓN </option>
                <option value="7"> JUEGO </option>
                <option value="8"> EGRESO/REINGRESO </option>

            </select>
          </div>

          <div class="col-md-3">
            <h5>Fecha Ejecución: </h5>
            <div class='input-group date' id='relFecha' data-link-field="fecha_ejecucionRel" data-date-format="dd MM yyyy HH:ii" data-link-format="yyyy-mm-dd HH:ii">
                <input type='text' class="form-control" placeholder="Fecha de ejecución del relevamiento" id="fechaRel"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" />
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
            <input type="hidden" id="fecha_ejecucionRel" value=""/>
          </div>
            <br>

          </div> <!-- FIN PRIMER FILA-->

          <div class="modal-body" style="font-family: Roboto; color: #aaa;">
            <div class="row"> <!-- row inicial -->

              <div class="col-md-3">
                <h5>Máquinas</h5>
                <table id="tablaMaquinasFiscalizacion" class="table">
                  <thead>
                    <tr>
                      <th> </th>
                    </tr>
                  </thead>
                  <tbody>

                  </tbody>
                </table>
              </div> <!-- maquinas -->

              <div  id="detalless" class="col-md-9">

                <h6>DETALLES</h6>

                <form id="form1" class="" action="index.html" method="post">


                  <div class="row" > <!-- PRIMER ROW DE DETALLE -->

                    <div class="col-lg-4">
                      <h5>Nro Admin.</h5>
                      <input id="nro_adminEv" type="text"   class="form-control" readonly="readonly">
                      <br>
                    </div> <!-- nro admin -->

                    <div class="col-lg-4">
                      <h5>N° Isla</h5>
                      <input id="nro_islaEv" type="text" class="form-control" readonly="readonly" >
                      <br>
                    </div> <!-- nro_isla -->

                    <div class="col-lg-4">
                      <h5>N° Serie</h5>
                      <input id="nro_serieEv" type="text" class="form-control" readonly="readonly" >
                      <br>
                    </div> <!-- nro_serie -->

                  </div> <!-- primer row DE DETALLE -->

                  <div class="row"> <!-- SEGUNDO ROW DE DETALLE -->
                    <div class="col-lg-6">
                      <h5>Marca</h5>
                      <input id="marcaEv" type="text" class="form-control" readonly="readonly">
                      <br>
                    </div> <!-- marca -->

                    <div class="col-lg-6">
                      <h5>Modelo</h5>
                      <input id="modeloEv" type="text" class="form-control" readonly="readonly">
                      <br>
                    </div> <!-- modelo -->

                  </div> <!-- segundo row -->

                  <div class="row"> <!-- TERCER ROW DE DETALLE -->

                    <div id="" class="table-editable">

                      <table id="tablaCargarEvent" class="table">
                        <thead>
                          <tr>
                            <th class="col-xs-6"><h6><b>CONTADORES</b></h6></th>
                            <th class="col-xs-3"><h6><b>TOMA</b></h6></th>
                          </tr>
                        </thead>

                        <tbody>

                        </tbody>
                      </table>
                    </div> <!-- FIN TABLA -->

                  </div>  <!-- FIN TERCER ROW DE DETALLE -->

                  <h6>TOMA</h6>

                  <div class="row"> <!-- PRIMER ROW DE TOMA -->
                    <div class="col-lg-4">
                      <h5>JUEGO</h5>
                      <select id="juegoEv" class="form-control" name="">
                        <option value=""></option>
                      </select>
                      <br>
                    </div>
                    <div class="col-lg-4">
                      <h5>APUESTA MÁX</h5>
                      <input id="apuestaEv" type="text" value="" class="form-control">
                    </div>
                    <div class="col-lg-4">
                      <h5>CANT LÍNEAS</h5>
                      <input id="cant_lineasEv" type="text" value="" class="form-control">
                    </div>
                  </div> <!-- FIN PRIMER ROW DE TOMA -->

                  <div class="row"> <!-- SEGUNDO ROW DE TOMA -->

                    <div class="col-lg-4">
                      <h5>% DEVOLUCIÓN</h5>
                      <input id="devolucionEv" type="text" value="" class="form-control">
                    </div>

                    <div class="col-lg-4">
                      <h5>DENOMINACIÓN</h5>
                      <input id="denominacionEv" type="text" value="" class="form-control">
                    </div>

                    <div class="col-lg-4">
                      <h5>CANT CRÉDITOS</h5>
                      <input id="creditosEv" type="text" value="" class="form-control">
                    </div>

                  </div> <!-- FIN SEGUNDO ROW DE TOMA -->

                  <div class="row">
                    <div class="col-lg-12">
                      <h6>OBSERVACIONES:</h6>
                      <textarea id="observacionesTomaEv" value="" class="form-control" style="resize:vertical;"></textarea>
                    </div>
                  </div> <!-- FIN ULTIMO row -->
                </form>
              </div> <!-- fin detalle -->
            </div> <!-- fin ROW INICIAL -->

          </div>  <!-- modal body -->

          <div class="modal-footer">

            <!-- INPUTS QUE ME SIRVEN PARA ENVIAR JSON EN EL POST DE VALIDAR -->
            <input id="id_log_movimiento" type="text" name="" value="" hidden>
            <input id="casinoId" type="text" name="" value="" hidden="">
            <input id="fecha_fiscalizacion" type="text" name="" value="" hidden>
            <input id="id_fiscalizac" type="text" name="" value="" hidden="">
            <input id="relevamiento" type="text" name="" value="" hidden="">
            <input id="maquina" type="text" name="" value="" hidden>
            <input id="fiscalizador" type="text" name="" value="" hidden="">
            <button id="guardarRel" type="button" class="btn btn-success guardarRelMov" value="" >GUARDAR</button>
            <button type="button" class="btn btn-default cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>


            <div id="mensajeExitoCarga" hidden>
              <br>
              <span style="font-family:'Roboto-Black'; font-size:16px; color:#4CAF50;">EXITO</span>
              <br>
              <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Los datos se han guardado correctamente</span>
            </div> <!-- mensaje -->
            <div id="mensajeErrorCarga" hidden>
              <br>
              <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
              <br>
              <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">No se han cargado todos los contadores.</span>
            </div> <!-- mensaje -->

        </div> <!-- modal footer -->
      </div> <!-- modal colapsado -->
    </div> <!-- modal content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->


  @endsection
  @section('scripts')
  <!-- JavaScript paginacion -->
  <script src="/js/paginacion.js" charset="utf-8"></script>

  <!-- JavaScript personalizado -->
  <script src="js/eventualidades.js" charset="utf-8"></script>

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
