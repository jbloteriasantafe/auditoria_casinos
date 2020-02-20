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
<link rel="stylesheet" href="/css/paginacion.css">
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

                <div class="row">
                <div class="col-lg-4">
                  <h5>Tipo Movimiento</h5>
                  <select class="form-control" id="B_TipoMovEventualidad">
                    <option value="" selected>- Seleccione tipo intervención -</option>
                    @foreach ($tiposEventualidadesMTM as $t_ev)
                    @if($t_ev->es_intervencion_mtm && !$t_ev->deprecado)
                    <option value="{{$t_ev->id_tipo_movimiento}}">{{$t_ev->descripcion}}</option>
                    @endif
                    @endforeach
                    <optgroup style="color:red;" label="Fuera de uso">
                    @foreach ($tiposEventualidadesMTM as $t_ev)
                    @if($t_ev->deprecado)
                    <option value="{{$t_ev->id_tipo_movimiento}}" style="color:red;">{{$t_ev->descripcion}}</option>
                    @endif
                    @endforeach
                    </optgroup>
                  </select>
                </div>
                <div class="col-lg-4">
                  <h5>SENTIDO</h5>
                  <select class="form-control" id="B_SentidoEventualidad">
                    <option value="" selected>- Seleccione el sentido -</option>
                    <option value="EGRESO TEMPORAL">EGRESO TEMPORAL</option>
                    <option value="REINGRESO">REINGRESO</option>
                    <option value="---">---</option>
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
              </div>
              <div class="row">
                <div class="col-lg-4">
                    <h5>Casino</h5>
                    <select class="form-control" id="B_CasinoEv">
                      <option value="" selected>- Seleccione casino -</option>
                      @foreach ($casinos as $casino)
                      <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-lg-4">
                    <h5>Nro. de Máquina</h5>
                    <input id="B_mtmEv" type="text" class="form-control" placeholder="Nro. de máquina">
                  </div>

                  <div class="col-lg-4">
                    <h5>Nro. de Isla</h5>
                    <input id="B_islaEv" type="text" class="form-control" placeholder="Nro. Isla">
                  </div>
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
              <h4 id="tituloTablaEvMTM">Movimientos Registrados en el Sistema</h4>
            </div>
            <div class="panel-body">
              <table id="tablaResultadosEvMTM" class="table table-fixed tablesorter">
                <thead>
                  <tr>
                    <th class="col-xs-2" value="log_movimiento.fecha" estado="" >FECHA <i class="fa fa-sort"></i></th>
                    <th class="col-xs-2" value="tipo_movimiento.descripcion" estado="">TIPO <i class="fa fa-sort"></i></th>
                    <th class="col-xs-2" value="log_movimiento.sentido" estado="">SENTIDO <i class="fa fa-sort"></i></th>
                    <th class="col-xs-1" value="estado_movimiento.descripcion" estado="">ESTADO <i class="fa fa-sort"></i></th>
                    <th class="col-xs-1" value="casino.nombre" estado="">CASINO <i class="fa fa-sort"></i></th>
                    <th class="col-xs-1" value="log_movimiento.islas" estado="">ISLAS <i class="fa fa-sort"></i></th>
                    <th class="col-xs-3" estado="">ACCIÓN</th>
                  </tr>
                </thead>
                <tbody id="cuerpoTablaEvMTM" style="max-height: 356px;">
                  {{-- @foreach($eventualidades as $eventualidad)
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

                      @if($eventualidad->id_estado_movimiento == 6 && ($esControlador == 0 || $esSuperUsuario))
                      <button class="btn btn_cargarEvmtm btn-success" type="button" value="{{$eventualidad->id_log_movimiento}}" data-casino="{{$eventualidad->id_casino}}"><i class="fa fa-fw fa-upload"></i></button>
                      <button class="btn  btn_borrarEvmtm btn-danger" type="button" value="{{$eventualidad->id_log_movimiento}}"><i class="fa fa-fw fa-trash"></i></button>
                      @endif

                      @if($eventualidad->id_estado_movimiento == 1  && ($esControlador == 1 || $esSuperUsuario))
                      <button class="btn  btn_validarEvmtm btn-success" type="button" value="{{$eventualidad->id_log_movimiento}}"><i class="fa fa-fw fa-check"></i></button>
                      @endif

                      @if(($eventualidad->id_estado_movimiento == 6) && ($esControlador == 1 || $esSuperUsuario)))
                      <button class="btn  btn_borrarEvmtm btn-danger" type="button" value="{{$eventualidad->id_log_movimiento}}"><i class="fa fa-fw fa-trash"></i></button>
                      @endif

                    </td>
                  </tr>
                  @endforeach --}}
                </tbody>
              </table>
              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- .row / TABLA -->
      
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
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">CARGA DE MÁQUINAS PARA INTERVENCIÓN MTM</h3>
      </div>

      <div class="modal-body" style="">
        <div  id="colapsado" class="collapse in">

          <div class="row"> <!-- ROW 1 -->
            <div class="col-md-4">
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
              </select>
            </div>
            <div class="col-md-4">
              <h5>SENTIDO</h5>
              <select class="form-control" id="sentidoMov">
                <option value="EGRESO TEMPORAL">EGRESO TEMPORAL</option>
                <option value="REINGRESO">REINGRESO</option>
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
      </div> <!-- modal footer -->
    </div> <!-- modal content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->


<!-- *****************Modal Eliminar ****************************************** -->
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header" style="background-color:#ef3e42;color:white;">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3>ADVERTENCIA</h3>
              </div>
              <div  id="colapsado" class="collapse in">
              <div class="modal-body">
                <form id="frmEliminar" name="frmJuego" class="form-horizontal" novalidate="">
                    <div class="form-group error ">
                        <div id="mensajeEliminar" class="col-xs-12">
                          <strong>¿Seguro desea eliminar la INTERVENCIÓN MTM?</strong>
                        </div>
                    </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-dangerEliminar confirmar" id="btn-eliminarModal" value="0">ELIMINAR</button>
                <button type="button" class="btn btn-default cancelar" data-dismiss="modal">CANCELAR</button>
              </div>
            </div>
          </div>
        </div>
  </div>

<!--********************* Modal para CARGAR MTM*****************************-->

<div class="modal fade" id="modalCargarRelMov" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 90%">
    <div class="modal-content">
      <div class="modal-header" style="background: #6dc7be;">
        <button id="btn-closeCargar" type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">CARGAR MTM</h3>
      </div> <!-- modal header -->

      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          @include('divRelevamientoMovimiento')
        </div>  <!-- modal body -->

        <div class="modal-footer">
          <button id="guardarRel" type="button" class="btn btn-success guardarRelMov" value="" >GUARDAR</button>
          <button type="button" class="btn btn-default cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
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
                          <h5>SECTOR</h5>
                          <input id="sectorRelevadoVal" type="text" value="" class="form-control">
                          <br>
                        </div> <!-- SECTOR -->

                        <div class="col-lg-4">
                          <h5>ISLA</h5>
                          <input id="islaRelevadaVal" type="text" value="" class="form-control">
                          <br>
                        </div><!-- ISLA -->

                      </div>
                        <div class="row">
                          <table id="tablaValidarContadores" class="table">
                            <thead>
                              <tr>
                                <th class="col-xs-6"><h6><b>CONTADORES</b></h6></th>
                                <th class="col-xs-6"><h6><b>TOMA</b></h6></th>
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

                                    @if($esControlador == 1 || $esSuperUsuario)
                                      <div class="col-lg-12">
                                        <h6>OBSERVACIONES ADMIN:</h6>
                                        <textarea id="observacionesAdmin" class="form-control"  maxlength="200" style="resize:vertical;"></textarea>
                                      </div>
                                    @endif

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
              <button id="errorValidacionEv" type="button" class="btn btn-default errorEv"  data-dismiss="modal" >CANCELAR</button>
              <button id="enviarValidarEv" type="button" class="btn btn-default validarEv" value="" > VISAR </button>
            </div>
           </div>
      </div>
    </div>
  </div>
</div>

<table hidden>
  <thead></thead>
  <tbody>
    <tr id="filaEjemploTablaEventualidades">
      <td class="col-xs-2 fecha">99 DIC 9999</td>
      <td class="col-xs-2 tipo">***</td>
      <td class="col-xs-2 sentido">***</td>
      <td class="col-xs-1 estado">
        <i class="fas fa-fw fa-exclamation" style="color: rgb(255,255,0);align: center;"></i>
      </td>
      <td class="col-xs-1 casino">INVALIDO</td>
      <td class="col-xs-1 isla">999999</td>
      <td class="col-xs-3 accion">
        <button class="btn btn-info btn_imprimirEvmtm" title="IMPRIMIR">
          <i class="fa fa-fw fa-print"></i>
        </button>
        <button class="btn btn-info btn_cargarEvmtm" title="CARGAR">
          <i class="fa fa-fw fa-upload"></i>
        </button>
        <button class="btn btn-info btn_validarEvmtm" title="VALIDAR">
          <i class="fa fa-fw fa-check"></i>
        </button>
        <button class="btn btn-info btn_borrarEvmtm" title="BORRAR">
          <i class="fa fa-fw fa-trash"></i>
        </button>
      </td>
    </tr>
  <tbody>
</table>

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
