@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
@section('contenidoVista')

@section('estilos')
<!-- <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/> -->
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/lista-datos.css">
@endsection

<div class="col-md-9">
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

                <div class="col-lg-4">
                  <h5>Tipo Movimiento</h5>
                  <select class="form-control" id="B_TipoMovEventualidad">
                    <option value="" selected>- Seleccione tipo intervención -</option>
                    @foreach ($tiposEventualidadesMTM as $t_ev)
                    <option value="{{$t_ev->id_tipo_movimiento}}">{{$t_ev->descripcion}}</option>
                    @endforeach
                    <!-- <option value=" " >- Todos los movimientos -</option> -->
                  </select>

                </div>
                <div class="col-lg-4">
                  <h5>Fecha</h5>
                  <div class="form-group">
                    <div class='input-group date' id='dtpFechaEv' data-link-field="fecha_eventualidad" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                      <input type='text' class="form-control" placeholder="Fecha de Eventualidad" id="B_fecha_ev"/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="fecha_eventualidad" value=""/>
                  </div>
                </div>

                <div class="col-lg-4">
                  <h5>Casino</h5>
                  <select class="form-control" id="B_CasinoEv">
                    <option value="" selected>- Seleccione casino -</option>
                    @foreach ($casinos as $casino)
                    <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                    @endforeach
                    <!-- <option value=" " >- Todos los movimientos -</option> -->
                  </select>

                </div>


              </div> <!-- row / formulario -->

              <br>

              <div class="row">
                <div class="col-md-12">
                  <center>
                    <button id="btn-buscarEventualidadMTM" class="btn btn-infoBuscar" type="button" name="button">
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
              <h4 id="tituloTablaEvMTM"></h4>
            </div>
            <div class="panel-body">
              <table id="tablaResultadosEvMTM" class="table table-fixed tablesorter">
                <thead>
                  <tr>
                    <th class="col-xs-2 " value="eventualidades.fecha_toma" estado="" >FECHA <i class="fa fa-sort"></i></th>
                    <th class="col-xs-2"  estado="">TIPO</th>
                    <th class="col-xs-1">ESTADO</th>
                    <th class="col-xs-2"  estado="">CASINO</th>
                    <th class="col-xs-2"  estado="">ISLA</th>
                    <th class="col-xs-3"  estado="">ACCIÓN</th>
                  </tr>
                </thead>
                <tbody id="cuerpoTablaEvMTM" style="max-height: 356px;">
                  @foreach($eventualidades as $eventualidad)
                  <tr id="{{$eventualidad->id_log_movimiento}}">
                    <td class="col-xs-2 fecha_eventualidad">{{$eventualidad->fecha}}</td>
                    <td class="col-xs-2">{{$eventualidad->descripcion}}</td>

                    @if($eventualidad->id_estado_movimiento == 4)
                    <td class="col-xs-1" text-align="center"><i class="fa fa-fw fa-check" style="color:#4CAF50"></i></td>
                    @else
                    <td class="col-xs-1" text-align="center"><i class="fas fa-fw fa-times" style="color:#EF5350"></i></td>
                    @endif

                    <td class="col-xs-2">{{$eventualidad->nombre}}</td>
                    <td class="col-xs-2">{{$eventualidad->islas}}</td>

                    <td class="col-xs-3">

                      <button class="btn  btn_imprimirEvmtm btn-info" type="button" value="{{$eventualidad->id_log_movimiento}}" ><i class="fa fa-fw fa-print"></i></button>

                      @if($eventualidad->id_estado_relevamiento == 6 && $esControlador == 0)
                      <button class="btn btn_cargarEvmtm btn-success" type="button" value="{{$eventualidad->id_log_movimiento}}" data-casino="{{$eventualidad->id_casino}}"><i class="fa fa-fw fa-upload"></i></button>
                      <button class="btn  btn_borrarEvmtm btn-danger" type="button" value="{{$eventualidad->id_log_movimiento}}"><i class="fa fa-fw fa-trash"></i></button>
                      @endif

                      @if($eventualidad->id_estado_relevamiento == 1  && $esControlador == 1)
                      <button class="btn  btn_validarEvmtm btn-success" type="button" value="{{$eventualidad->id_log_movimiento}}"><i class="fa fa-fw fa-check"></i></button>
                      @endif

                      @if(($eventualidad->id_estado_relevamiento == 6) && ($esControlador == 1))
                      <button class="btn  btn_borrarEvmtm btn-danger" type="button" value="{{$eventualidad->id_log_movimiento}}"><i class="fa fa-fw fa-trash"></i></button>
                      @endif

                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div> <!-- .row / TABLA -->
</div>
<div class="col-md-3">

      <!--Botón nueva eventualidad de máquina-->
  <div class="row">
      <div class="col-lg-12">
            <a href="" id="btn-nueva-evmaquina" style="text-decoration: none;">
              <div class="panel panel-default panelBotonNuevo">
                  <center><img class="imgNuevo" src="/img/logos/tragaperras_white.png"><center>
                  <div class="backgroundNuevo"></div>
                  <div class="row">
                      <div class="col-xs-12">
                        <center>
                            <h5 class="txtLogo">+</h5>
                            <h4 class="txtNuevo">NUEVA INTERVENCIÓN DE MÁQUINA</h4>
                          </center>
                      </div>
                  </div>
              </div>
            </a>
          </div>
      </div> <!-- .row -->
</div>


<!-- ************************modal para cargar maq afectadas ******************************-->
<div class="modal fade" id="modalNuevaEvMTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 70%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color:#D50000">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">CARGA DE MÁQUINAS PARA INTERVENCIÓN MTM</h3>
      </div>

      <div class="modal-body" style="">
        <div  id="colapsado" class="collapse in">

          <div class="row"> <!-- ROW 1 -->
            <div class="col-md-8">
              <h5>Agregar Máquina</h5>

              <div class="row">
                <div class="input-group lista-datos-group">
                  <input id="inputMTM" class="form-control" type="text" value="" autocomplete="off">
                  <span class="input-group-btn">
                    <button id="agregarMTMEv" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <h5>Tipo Movimiento</h5>
              <select class="form-control" id="tipoMov">
                <option value="9" selected>- Seleccione Tipo Movimiento -</option>
              </select>
            </div>
            <br>
          </div> <!-- FIN ROW 1 -->

          <div class="row"> <!-- ROW 2 -->
            <div class="col-md-12">
              <h6>MÁQUINAS SELECCIONADAS</h6>
              <table id="tablaMTM" class="table">
                <thead>
                  <tr>
                    <th width="20%">NÚMERO</th>
                    <th width="20%">ACCIÓN</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          </div> <!-- FIN ROW 2 -->

        </div> <!-- colapsado -->
      </div> <!-- modal body -->

      <div class="modal-footer">
        <button id="btn-impr" type="button" class="btn btn-default" value="" >IMPRIMIR</button>
        <button id="boton-cancelar-evMTM" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>

        <div id="mensajeCargarMTM" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Debe seleccionar las máquinas que pertenecen a esta intervención</span>
        </div> <!-- mensaje -->

      </div> <!-- modal footer -->

    </div> <!-- modal content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->


<!-- *****************Modal Eliminar ****************************************** -->
<div class="modal fade" id="modalEliminarEventualidadMTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                      <strong id="titulo-modal-eliminar">¿Seguro desea eliminar la INTERVENCIÓN MTM?</strong>
                  </div>
                </div>
            </form>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarEventMTM" value="0">ELIMINAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
      </div>
    </div>
</div>


<!--********************* Modal para CARGAR MTM*****************************-->

<div class="modal fade" id="modalCargarMaqEv" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 90%">
    <div class="modal-content">
      <div class="modal-header">
        <button id="btn-closeCargar" type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">CARGAR MTM</h3>
      </div> <!-- modal header -->

      <div  id="colapsado" class="collapse in">
        <div class="row"> <!-- PRIMER FILA-->
          <div class="col-md-3">
            <h5>Fiscalizador Toma: </h5>
            <div class="row"> <!-- row 2 -->
              <div class="input-group lista-datos-group">
                <input id="fiscalizadorEv" class="form-control" type="text" value="" autocomplete="off">
              </div>
              <!-- <input id="inputMaq" data-maquina="" class="form-control" type="text" autocomplete="off" placeholder="Buscar máquinas"/> -->
            </div> <!-- fin row2 -->
          </div>

          <div class="col-md-3">
            <h5>Tipo Movimiento</h5>
            <div class="input-group lista-datos-group">
              <input id="inputTipoMov" class="form-control" type="text" value="" autocomplete="off" readonly="">
            </div>
            <br>
          </div>

          <div class="col-md-3">
            <h5>Fiscalizador Carga: </h5>
            <div class="row"> <!-- row 3 -->
              <input id="fiscaCargaEv" type="text"class="form-control">
            </div> <!-- fin row 3 -->
          </div>

          <div class="col-md-3">
            <h5>Fecha Ejecución: </h5>
            <div class='input-group date' id='evFecha' data-link-field="fecha_ejecucionEv" data-date-format="dd MM yyyy HH:ii" data-link-format="yyyy-mm-dd HH:ii">
              <input type='text' class="form-control" placeholder="Fecha de ejecución del relevamiento" id="fechaEv"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" />
              <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
              <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
            <input type="hidden" id="fecha_ejecucionEv" value=""/>
          </div>

          <br>

        </div> <!-- FIN PRIMER FILA-->

        <div class="modal-body" style="font-family: Roboto;">
          <div class="row"> <!-- row inicial -->

            <div class="col-md-3">
              <h5>Máquinas</h5>
              <table id="tablaCargarMTM" class="table">
                <thead>
                  <tr>
                    <th> </th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
              </table>
            </div> <!-- maquinas -->

            <div  id="detallesMTM" class="col-md-9"  hidden="">
              <h6>DETALLES</h6>
              <form id="form1" class="" action="index.html" method="post">

                <div class="row" > <!-- PRIMER ROW DE DETALLE -->

                  <div class="col-lg-4">
                    <h5>N° Admin</h5>
                    <div class="row"> <!-- row 3 -->
                      <input id="inputAdmin" type="text"class="form-control">
                    </div> <!-- fin row 3 -->
                  </div>

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
                  <div class="col-lg-4">
                    <h5>Marca</h5>
                    <input id="marcaEv" type="text" class="form-control" readonly="readonly">
                    <br>
                  </div> <!-- marca -->

                  <div class="col-lg-4">
                    <h5>Modelo</h5>
                    <input id="modeloEv" type="text" class="form-control" readonly="readonly">
                    <br>
                  </div> <!-- modelo -->
                  <div class="col-lg-4">
                    <h5>MAC</h5>
                    <input id="macEv" type="text" class="form-control">
                    <br>
                    <span id="alerta_macEv" class="alertaSpan"></span>
                  </div>

                </div> <!-- segundo row -->

                <div class="row">
                  <div class="col-lg-4">
                    <h5>ISLA</h5>
                    <input id="islaRelevadaEv" type="text" value="" class="form-control">
                    <br>
                  </div>

                  <div class="col-lg-4">
                    <h5>SECTOR</h5>
                    <input id="sectorRelevadoEv" type="text" value="" class="form-control">
                    <br>
                  </div> <!-- SECTOR -->
                </div>

                <div class="row"> <!-- TERCER ROW DE DETALLE -->

                  <div id="" class="table-editable">

                    <table id="tablaCargarContadores" class="table">
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
          <input id="id_maq" type="text" name="" value="" hidden="">
          <input id="id_fiscaliz_carga" type="text" name="" value="" hidden="">
          <input id=id_mov type="text" name="" value="" hidden="">
          <button id="guardarEv" type="button" class="btn btn-success guardarRelMov" value="" >GUARDAR</button>
          <button type="button" class="btn btn-default cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>


          <div id="mensajeExitoCarga" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#4CAF50;">EXITO</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Los datos se han guardado correctamente</span>
          </div> <!-- mensaje -->
          <div id="mensajeErrorCargaEv" hidden>
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



<!-- **************MODAL DE VALIDACIÓN **********************-->
<div class="modal fade" id="modalValidacionEventualidadMTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width: 90%">
         <div class="modal-content">
           <div class="modal-header">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title modalVerMas" id="myModalLabel">VISAR MTMs</h3>
            </div>

          <div  id="colapsado" class="collapse in">

            <div class="modal-body" style="font-family: Roboto;">
              <div class="row">

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

                <div class="col-md-9">
                  <div class="detalleMaqVal">
                      <h5>DETALLES</h5>
                    <div class="row">

                      <div class="col-lg-3">
                        <h5>Tipo Mov.</h5>
                        <input id="tipo_movVal" type="text" class="form-control" readonly="readonly">
                        <br>
                      </div> <!--tipo de movimiento -->

                      <div class="col-lg-3">
                        <h5>Fecha</h5>
                        <input id="fecha_Val" type="text" class="form-control" readonly="readonly">
                        <br>
                      </div> <!--fecha_sala -->

                      <div class="col-lg-3">
                        <h5>Fiscalizador Toma</h5>
                        <input id="f_tomaVal" type="text" class="form-control" readonly="readonly">
                        <br>
                      </div> <!-- Fisca toma -->

                      <div class="col-lg-3">
                        <h5>Fiscalizador Carga</h5>
                        <input id="f_cargaVal" type="text" class="form-control" readonly="readonly">
                        <br>
                      </div> <!-- fisca carga-->

                    </div>
                    <div class="row" >

                      <div class="col-lg-4">
                        <h5>Nro Admin.</h5>
                        <input id="nro_adminVal" type="text" class="form-control" readonly="readonly">
                        <br>
                      </div> <!-- nro admin -->
                      <div class="col-lg-4">
                        <h5>N° Isla</h5>
                        <input id="nro_islaVal" type="text" class="form-control" readonly="readonly" >
                        <br>
                      </div> <!-- nro_isla -->
                      <div class="col-lg-4">
                        <h5>N° Serie</h5>
                        <input id="nro_serieVal" type="text" class="form-control" readonly="readonly" >
                        <br>
                      </div> <!-- nro_serie -->
                    </div> <!-- primer row -->
                    <div class="row">
                        <div class="col-lg-4">
                          <h5>Marca</h5>
                          <input id="marcaVal" type="text" class="form-control" readonly="readonly">
                          <br>
                        </div> <!-- marca -->

                        <div class="col-lg-4">
                          <h5>Modelo</h5>
                          <input id="modeloVal" type="text" class="form-control" readonly="readonly">
                          <br>
                        </div> <!-- modelo -->
                        <div class="col-lg-4">
                          <h5>MAC</h5>
                          <input id="macVal" type="text" class="form-control">
                          <br>
                          <span id="alerta_macVal" class="alertaSpan"></span>
                        </div>
                      </div> <!-- segundo row -->
                      <div class="row">
                        <div class="col-lg-4">
                          <h5>ISLA</h5>
                          <input id="islaRelevadaVal" type="text" value="" class="form-control">
                          <br>
                        </div>

                        <div class="col-lg-4">
                          <h5>SECTOR</h5>
                          <input id="sectorRelevadoVal" type="text" value="" class="form-control">
                          <br>
                        </div> <!-- SECTOR -->
                      </div>
                        <div class="row">
                          <table id="tablaValidarContadores" class="table">
                            <thead>
                              <tr>
                                <th class="col-xs-6"><h6><b>CONTADORES</b></h6></th>
                                <th class="col-xs-3"><h6><b>TOMA</b></h6></th>
                              </tr>
                            </thead>
                            <tbody>

                            </tbody>
                          </table>
                        </div>  <!-- tercer row -->

                              <h6>TOMA</h6>

                              <div class="row">
                                <div class="col-lg-4">
                                  <h5>JUEGO</h5>
                                  <input id="juego" type="text" class="form-control" readonly="readonly">
                                  <br>
                                </div>

                                <div class="col-lg-4">
                                  <h5>APUESTA MÁX</h5>
                                  <input id="apuesta" type="text" class="form-control" readonly="readonly">
                                  </div>

                                <div class="col-lg-4">
                                  <h5>CANT LÍNEAS</h5>
                                  <input id="cant_lineas" type="text" class="form-control" readonly="readonly">
                                  </div>
                                </div> <!-- cuarto row -->
                              <div class="row">

                                <div class="col-lg-4">
                                  <h5>% DEVOLUCIÓN</h5>
                                  <input id="devolucion" type="text" class="form-control" readonly="readonly">
                                  </div>

                                <div class="col-lg-4">
                                  <h5>DENOMINACIÓN</h5>
                                  <input id="denominacion" type="text" class="form-control" readonly="readonly">
                                  </div>

                                <div class="col-lg-4">
                                  <h5>CANT CRÉDITOS</h5>
                                  <input id="creditos" type="text" class="form-control" readonly="readonly">
                                  </div>
                                </div> <!-- quinto row -->


                                <div id="toma2">

                                  <h6>TOMA 1</h6>

                                    <div class="row">
                                    <div class="col-lg-4">
                                      <h5>JUEGO</h5>
                                      <input id="juego1" type="text" class="form-control" readonly="readonly">
                                      <br>
                                    </div>

                                    <div class="col-lg-4">
                                      <h5>APUESTA MÁX</h5>
                                      <input id="apuesta1" type="text" class="form-control" readonly="readonly">
                                      </div>

                                    <div class="col-lg-4">
                                      <h5>CANT LÍNEAS</h5>
                                      <input id="cant_lineas1" type="text" class="form-control" readonly="readonly">
                                      </div>
                                    </div>  <!-- sexto row -->
                                    <div class="row">

                                    <div class="col-lg-4">
                                      <h5>% DEVOLUCIÓN</h5>
                                      <input id="devolucion1" type="text" class="form-control" readonly="readonly">
                                      </div>


                                    <div class="col-lg-4">
                                      <h5>DENOMINACIÓN</h5>
                                      <input id="denominacion1" type="text" class="form-control" readonly="readonly">
                                      </div>

                                    <div class="col-lg-4">
                                      <h5>CANT CRÉDITOS</h5>
                                      <input id="creditos1" type="text" class="form-control" readonly="readonly">
                                      </div>
                                    </div>  <!-- septimo row -->
                                  </div>


                                    <div class="row">
                                      <div class="col-lg-12">
                                        <h6>OBSERVACIONES:</h6>
                                      <textarea id="observacionesToma" class="form-control" readonly="readonly" style="resize:vertical;"></textarea>
                                    </div>
                                  </div>
                                  </div>
                                </div> <!-- fin row de detalle -->
                                </div>  <!-- fin row inicial -->
            <div class="modal-footer">
              <!-- INPUTS QUE ME SIRVEN PARA ENVIAR JSON EN EL POST DE VALIDAR -->
            <input id="id_log_movimiento" type="text" name="" value="" hidden>
            <input id="fecha_fiscalizacion" type="text" name="" value="" hidden>
            <input id="relevamiento" type="text" name="" value="" hidden>
            <input id="maquina" type="text" name="" value="" hidden>
              <button id="errorValidacionEv" type="button" class="btn btn-default errorEv"  data-dismiss="modal" >ERROR</button>
              <button id="enviarValidarEv" type="button" class="btn btn-default validarEv" value="" > VISAR </button>

              <div id="mensajeErrorVal" hidden>
                  <br>
                  <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
                  <br>
                  <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;"></span>
              </div> <!-- mensaje -->
              <div id="mensajeExitoValidacion" hidden>
                  <br>
                  <span style="font-family:'Roboto-Black'; font-size:16px; color:#4CAF50 ;">EXITO</span>
                  <br>
                  <span  style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">La acción se ha realizado correctamente</span>
              </div> <!-- mensaje -->
            </div>

           </div>
      </div>
    </div>
  </div>
</div>






    @endsection
    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>

    <!-- JavaScript personalizado -->
    <script src="js/eventualidadesMTM.js" charset="utf-8"></script>

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
