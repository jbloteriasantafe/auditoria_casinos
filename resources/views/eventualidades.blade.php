@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoExpedientes">@svg('expedientes','iconoExpedientes')</span>
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('contenidoVista')

@section('estilos')
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/paginacion.css"/>
<link rel="stylesheet" href="css/lista-datos.css">

<style>
    #modalEliminarObservacion.modal {
      z-index: 2000 !important;
    }
    #modalEliminarObservacion + .modal-backdrop {
      z-index: 1999 !important;
    }
    #tablaResultadosEv th {
      cursor: default !important;
    }
    #tablaResultadosEv th:after {
      display: none !important;
    }
    #tablaResultadosEv th:hover {
      background-color: transparent !important;
    }
  </style>

@endsection

    <!-- Botón nueva eventualidad -->
      <div class="row">
        <div class="col-lg-6">
          <a href="#modalCargarEventualidad" data-toggle="modal" style="text-decoration: none;">
            <div class="panel panel-default panelBotonNuevo">
              <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"></center>
              <div class="backgroundNuevo"></div>
              <div class="row">
                <div class="col-xs-12">
                  <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">GENERAR EVENTUALIDAD</h4>
                  </center>
                </div>
              </div>
            </div>
          </a>
        </div>
        <div class="col-lg-6">
          <a href="#modalSubirEventualidad" data-toggle="modal" style="text-decoration: none;">
            <div class="panel panel-default panelBotonNuevo">
              <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"></center>
              <div class="backgroundNuevo"></div>
              <div class="row">
                <div class="col-xs-12">
                  <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">SUBIR EVENTUALIDAD FIRMADA</h4>
                  </center>
                </div>
              </div>
            </div>
          </a>
        </div>

  </div>  <!-- /#row -->



    <!-- FILTROS -->
    <div class="row">
      <div class="col-md-12">


        <!-- FILTROS -->
        <div class="panel panel-default">
          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
            <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
          </div>
          <div id="collapseFiltros" class="panel-collapse collapse">
            <div class="panel-body">
              <div class="row">
                <div class="col-lg-2">
                  <h5>Fecha desde</h5>
                  <div class="input-group date" id="dtpFechaEv">
                    <input type="text" class="form-control" placeholder="yyyy-mm-dd" id="B_fecha_ev" readonly/>
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
                <div class="col-lg-2">
                  <h5>Fecha hasta</h5>
                  <div class="input-group date" id="dtpFechaEvHasta">
                    <input type="text" class="form-control" placeholder="yyyy-mm-dd" id="B_fecha_evhasta" readonly/>
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
                <div class="col-lg-2">
                  <h5>Casino</h5>
                  <select class="form-control" id="B_CasinoEv">
                    <option value="">Todos los casinos</option>
                    @foreach($casinos as $c)
                      <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-2">
                  <h5>Turno</h5>
                  <select class="form-control" id="B_TurnoEventualidad">
                    <option value="">Todos los turnos</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                  </select>
                </div>
                <div class="col-lg-2">
                  <h5>Estado</h5>
                  <select class="form-control" id="B_Estado">
                    <option value="">Todos los estados</option>
                    <option value="1">Generado</option>
                    <option value="2">Firmado</option>
                    <option value="3">Visado</option>
                  </select>
                </div>
                <div class="col-lg-2">
                  <h5>Con observaciones</h5>
                  <input type="checkbox" id="B_Observado" value="1">
                </div>
              </div>
            </br>
              <!--<div class="row">-->

              <!--</div>-->

              <div class="row">
                <div class="col-md-12 text-center">
                  <button id="btn-buscarEventualidades" class="btn btn-infoBuscar">
                    <i class="fa fa-search"></i> BUSCAR
                  </button>
                </div>
              </div>
            </div>
          </div>

      </div> <!-- .col-md-12 -->

    </div> <!-- .row / FILTROS -->

    <!-- TABLA -->
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>ÚLTIMAS EVENTUALIDADES INGRESADAS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosEv" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-2" estado="">FECHA</th>
                  <th class="col-xs-2" estado="">CASINO</th>
                  <th class="col-xs-2" estado="">TURNO</th>
                  <th class="col-xs-2" estado="">HORA</th>
                  <th class="col-xs-2" estado="">ESTADO</th>
                  <th class="col-xs-2" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaEv" style="max-height: 356px;">
              </tbody>
            </table>
            <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div> <!-- .row / TABLA -->





  <!-- *****************Modal Eliminar eventualidad****************************************** -->
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
                        <strong id="titulo-modal-eliminar">¿Seguro desea eliminar la EVENTUALIDAD?</strong>
                    </div>
                  </div>
              </form>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarEventualidad" value="0">ELIMINAR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
            </div>
        </div>
      </div>
  </div>

  <!--**************************** MODAL ELIMINAR OBSERVACION ****************** -->
  <div class="modal fade" id="modalEliminarObservacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
              <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
            </div>

            <div class="modal-body franjaRojaModal">
              <form id="frmEliminarObs" name="frmCasino" class="form-horizontal" novalidate="">
                  <div class="form-group error ">
                    <div class="col-xs-12">
                        <strong id="titulo-modal-eliminar">¿Seguro desea eliminar la OBSERVACIÓN?</strong>
                    </div>
                  </div>
              </form>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarObservacion" value="0">ELIMINAR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
            </div>
        </div>
      </div>
  </div>
<!-- ***************MODAL VISAR EVENTUALIDAD ***********************-->
  <div class="modal fade" id="modalVisarEventualidad" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
              <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
              <button id="btn-minimizarVisar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVisar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
              <h3 class="modal-title" style="background-color: #6dc7be;">| VISAR EVENTUALIDAD</h3>
             </div>

             <div  id="colapsadoVisar" class="collapse in">

            <div class="modal-body franjaRojaModal">
              <form id="frmVisar" name="frmCasino" class="form-horizontal" novalidate="">
                  <div class="form-group error ">
                    <div class="col-xs-12">
                        <strong id="titulo-modal-visar">¿Seguro desea VISAR la EVENTUALIDAD?</strong>
                    </div>
                  </div>
              </form>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-successAceptar" id="btn-visarEventualidad" value="0">VISAR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
            </div>
        </div>
      </div>
  </div>
</div>

<!--****************************MODAL PARA AGREGAR OBSERVACION******************** -->

<div class="modal fade" id="modalObservacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            <button id="btn-minimizarObservacion" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoObservacion" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
            <h3 class="modal-title" style="background-color: #6dc7be;">| ESCRIBIR O SUBÍR OBSERVACIÓN</h3>
           </div>

           <div  id="colapsadoObservacion" class="collapse in">

          <div class="modal-body franjaRojaModal">
            <form id="formNuevaObservacion" class="form-horizontal" novalidate="" autocomplete="off">
              <input type="hidden" name="id_eventualidades" id="obs_event_id">
                <div class="form-group error ">
                  <div class="col-md-12">
                    <div>
                      <h5>Escribir observación</h5>
                      <textarea maxlength="3999" class="form-control" name="observacion" rows="3"></textarea>
                    </div>
                  </div>
                </div>
            </form>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="guardarObs" value="0">GENERAR OBSERVACIÓN</button>
          </div>

          <div class="col-md-12">
          <h5>Subir archivo</h5>
          <br/>
        </div>
        <form id="formSubirObservacion" class="form-horizontal" novalidate autocomplete="off">
    <input type="hidden" name="id_eventualidades" id="obs_event_id_file">

    <div class="form-group">
      <div class="col-sm-offset-1">
        <div class="input-group col-sm-10">
          <span class="input-group-btn">
            <button class="btn btn-primary" type="button" onclick="$('#uploadObs').click()">
              <i class="fa fa-folder-open"></i> Examinar…
            </button>
          </span>
          <input type="text"
                 id="fileNameObs"
                 class="form-control"
                 placeholder="No se ha seleccionado ningún archivo"
                 readonly>
          <input type="file"
                 id="uploadObs"
                 name="uploadObs"
                 style="display:none;">
        </div>
      </div>
    </div>
  </form>


          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="subirObs" value="0">SUBIR ARCHIVO OBSERVACIÓN</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>

      </div>
    </div>
</div>
</div>

<!-- ************MODAL PARA VER OBSERVACIONES *********************-->

<div class="modal fade" id="modalVerObservaciones" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarVerObservacion" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerObservacion" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" style="background-color: #6dc7be;">| OBSERVACIONES</h3>
       </div>
      <div class="modal-body">
        <ul id="listaPdfs" class="list-unstyled" data-ev-id="">
          <!-- Aquí inyectaremos: <li><a href="..." target="_blank">archivo.pdf</a></li> -->
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
      </div>
    </div>
  </div>
</div>


<!-- ***********MODAL PARA CARGAR EVENTUALIDAD ************************-->


  <div class="modal fade" id="modalCargarEventualidad" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width:71%">
           <div class="modal-content">
             <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
               <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
               <button id="btn-minimizarCrear" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrear" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title" style="background-color: #6dc7be;">| NUEVA EVENTUALIDAD</h3>
              </div>

              <div  id="colapsadoCrear" class="collapse in">

      <form id="formNuevaEventualidad" novalidate="" method="POST" autocomplete="off">

        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <h5>Casino</h5>
              <select class="form-control" name="id_casino">
                <option value="">- Seleccione un casino -</option>
                @foreach ($casinos as $casino)
                  <option value="{{ $casino->id_casino }}">{{ $casino->nombre }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <h5>Turno</h5>
              <select name="turno" class="form-control">
                <option value="">- Seleccione un turno -</option>
              </select>
            </div>
            <div class="col-md-4">
              <h5>Horario</h5>
              <input type="text" name="horario" class="form-control">
            </div>
          </div>

          <br>

          <div class="row">
          <!--  <div class="col-md-4">
              <h5>Fecha</h5>
              <input type="text" class="form-control" value="{{strftime('%A, %d de %B de %Y')}}">
              <input  data-js-fecha-hoy name="fecha" type="hidden" value="{{date('Y-m-d')}}">
            </div>
          -->
            <div class="col-md-4" >
              <h5>FECHA DE LA EVENTUALIDAD</h5>
              <div class='input-group date' id='evFecha' data-date-format="yyyy-mm-dd HH:ii:ss" data-link-format="yyyy-mm-dd HH:ii">
                  <input name="fecha_toma" type='text' class="form-control" placeholder="Fecha de ejecución del control" id="fecha_toma" autocomplete="off" style="background-color: rgb(255,255,255);" readonly/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
            <div class="col-md-8">
              <h5>Fiscalizadores en sala (COMPLETAR CON TODOS LOS FISCALIZADORES)<h5>
              <input type="text" maxlength="299" class="form-control" name="otros_fiscalizadores" value="{{$usuario->nombre}}">
            </div>

        </div>

          <br>

          <br>
            <h5>Procedimientos Realizados</h5>

            <div class="table-responsive">
              <table class="table table-bordered table-sm">
                <thead class="thead-light">
                  <tr>
                    <th>Procedimiento</th>
                    <th class="text-center" style="width: 50px;">✔</th>
                    <th class="text-center" style="width: 50px;">*</th>
                    <th>Observaciones</th>
                  </tr>
                </thead>
                <tbody>
                  @php
                    $procedimientos = [
                      'Toma de Contadores',
                      'Contadores a Pedido',
                      'Toma de Progresivos',
                      'Control Ambiental',
                      'Control de Layout Total',
                      'Control de Layout Parcial',
                      'Egreso y Reingreso de MTM',
                      'Informes de Turnos Extras',
                      'Relevamiento Torneo de Poker',
                      'Bingo Tradicional',
                      'Solicitudes de Reemplazo / Licencia',
                      'Solicitud de Autoexclusión',
                      'Aperturas de Mesas de Paño',
                      'Valores de Apuesta de Mesas de Paño',
                    ];
                  @endphp

                  @foreach ($procedimientos as $i => $nombre)
                    <tr>
                      <td>{{ $nombre }}</td>
                      <td class="text-center">
                        <input type="radio" name="procedimientos[{{ $i }}][estado]" value="✔">
                      </td>
                      <td class="text-center">
                        <input type="radio" name="procedimientos[{{ $i }}][estado]" value="*">
                      </td>
                      <td>
                        <input type="text" class="form-control form-control-sm" name="procedimientos[{{ $i }}][observacion]" placeholder="Observaciones">
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>


          <div class="row">
            <div class="col-md-12">
              <h5>Observaciones</h5>
              <textarea class="form-control" name="observaciones" maxlength="3999" rows="3"></textarea>
            </div>
          </div>

          <br>

          <div class="row">
            <div class="col-md-3">
              <h5>¿Menores en sala?</h5>
              <select class="form-control" name="menores">
                <option value="No">No</option>
                <option value="Sí">Sí</option>
              </select>
            </div>
            <div class="col-md-3">
              <h5>¿Fumadores?</h5>
              <select class="form-control" name="fumadores">
                <option value="No">No</option>
                <option value="Sí">Sí</option>
              </select>
            </div>
            <div class="col-md-6">
              <h5>¿Boletín adjunto?</h5>
              <input type="text" maxlength="299" class="form-control" name="boletin_adjunto" placeholder="Observaciones">
            </div>
          </div>

        </div>

        <div class="modal-footer">

          <button id ="guardarEv" type="button" class="btn btn-successAceptar" data-js-generar-posta-descargar="crearEventualidad">GENERAR</button>
          <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>



        </div>
      </form>
    </div> <!-- modal content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->
</div>

<!-- **********************MODAL SUBIR EVENTUALIDAD FIRMADA**********************-->



<div class="modal fade" id="modalSubirEventualidad" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:45%">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarSubir" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoSubir" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #6dc7be;">| SUBIR EVENTUALIDAD FIRMADA</h3>
            </div>

            <div  id="colapsadoSubir" class="collapse in">

              <form id="formSubirEventualidad" novalidate="" enctype="multipart/form-data" method="POST" autocomplete="off">

                <div class="col-md-9">
                  <br/>
                  <h5>Subir eventualidad firmada</h5>
                  <div class="input-group">
                    <span class="input-group-btn">
                      <button class="btn btn-primary" type="button" onclick="$('#upload').click()">
                        <i class="fa fa-folder-open"></i> Examinar…
                      </button>
                    </span>
                    <input type="text"
                           id="fileNameEv"
                           class="form-control"
                           placeholder="No se ha seleccionado ningún archivo"
                           readonly>
                    <input type="file"
                           id="upload"
                           name="upload"
                           style="display:none;"
                           onchange="document.getElementById('fileNameEv').value = this.files[0]?.name || ''">
                  </div>
                </div>

                <div class="modal-footer">

<br/><br/><br/><br/><br/>

                  <button id ="subirEv" type="button" class="btn btn-successAceptar" data-js-generar-posta-descargar="subirEventualidad">SUBIR</button>
                  <button type="button" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>


                </div>

              </form>
              </div>
            </div> <!-- modal content -->
          </div> <!-- modal dialog -->
        </div> <!-- modal fade -->



  @endsection
  @section('scripts')
  <!-- JavaScript paginacion -->
  <script src="/js/paginacion.js" charset="utf-8"></script>

  <!-- JavaScript personalizado -->
  <script src="js/nuevasEventualidades.js" charset="utf-8"></script>

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

  <!-- Comienza modal de ayuda -->
  @section('tituloDeAyuda')
  <h3 class="modal-title" style="color: #fff;">| Eventualidades</h3>
  @endsection
  @section('contenidoAyuda')
  <div class="col-md-12">
    <h5>Tarjeta de Eventualidades</h5>
    <p>
      Eventualidades sobre novedades y eventos que alteren el normal funcionamiento.
    </p>
  </div>
  @endsection
  <!-- Termina modal de ayuda -->
