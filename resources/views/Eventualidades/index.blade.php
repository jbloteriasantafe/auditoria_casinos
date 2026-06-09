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
    #tablaResultadosEv th {
      cursor: default !important;
    }
    #tablaResultadosEv th:after {
      display: none !important;
    }
    #tablaResultadosEv th:hover {
      background-color: transparent !important;
    }
    /* Botones de respuesta del checklist de procedimientos */
    label.btn-respuesta-si.active,
    label.btn-respuesta-si.active:hover,
    label.btn-respuesta-si.active:focus {
      background-color: #5cb85c !important;
      color: #fff !important;
      border-color: #4cae4c !important;
    }
    label.btn-respuesta-no.active,
    label.btn-respuesta-no.active:hover,
    label.btn-respuesta-no.active:focus {
      background-color: #d9534f !important;
      color: #fff !important;
      border-color: #d43f3a !important;
    }
    .btn-respuesta-si i, .btn-respuesta-no i { margin-right: 4px; }
    /* Radio de borde fijo: el hover NO debe redondear el botón (queda recto como el resto) */
    label.btn-respuesta-si,
    label.btn-respuesta-si:hover,
    label.btn-respuesta-si:focus,
    label.btn-respuesta-si.active,
    label.btn-respuesta-no,
    label.btn-respuesta-no:hover,
    label.btn-respuesta-no:focus,
    label.btn-respuesta-no.active {
      border-radius: 4px !important;
    }
    /* Esconder los radios reales (sólo el label/btn es interactivo) */
    #tbodyProcedimientos input[type=radio] {
      position: absolute; opacity: 0; pointer-events: none;
    }
    /* Fila con error de validación de procedimientos */
    #tbodyProcedimientos > tr.danger > td { background-color: #f2dede !important; }
    #tablaProcedimientosForm td { vertical-align: middle; }
    /* Fila del reporte diario (sin table-fixed flotando, render normal) */
    #tablaReporteDiario td { vertical-align: middle; }
    /* (Legacy) Badges de estado del resumen — reemplazados por el círculo .estado-resumen +
       i.faVisado/i.faNoVisado. Se dejan comentados por si se reusa el estilo pill en el futuro.
    .badge-estado {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: bold;
      letter-spacing: 0.4px;
    }
    .badge-estado-visado   { background: #dff0d8; color: #3c763d; border: 1px solid #3c763d; }
    .badge-estado-noVisado { background: #f5f5f5; color: #777;    border: 1px solid #aaa;    }
    .badge-clickable       { cursor: pointer; transition: all .15s; }
    .badge-clickable:hover { transform: scale(1.05); box-shadow: 0 1px 4px rgba(0,0,0,0.2); }
    */
    /* Círculo de estado del resumen diario — mismo lenguaje visual que la tabla de eventualidades.
       Los colores i.faVisado/i.faNoVisado viven en estiloDashboard.css (junto a faGenerado/faValidado)
       para que la tabla de eventualidades y el reporte diario compartan una única fuente de verdad. */
    .estado-resumen { font-weight: normal; white-space: nowrap; }
    /* En el reporte el círculo va pegado a su texto: anulamos el nudge -9px que sí usa la
       tabla de eventualidades (allí el círculo arranca la celda; acá quedaría descolgado). */
    .estado-resumen i.faVisado,
    .estado-resumen i.faNoVisado { position: static; left: auto; margin-right: 4px; }
    /* Botón "GUARDAR TEMPORAL" en amarillo (el theme resetea .btn a gris). */
    #guardarTemporal {
      background-color: #f0ad4e !important;
      border-color: #eea236 !important;
      color: #fff !important;
      font-weight: bold;
    }
    #guardarTemporal:hover,
    #guardarTemporal:focus {
      background-color: #ec971f !important;
      border-color: #d58512 !important;
      color: #fff !important;
    }
    #guardarTemporal i { margin-right: 4px; }
    /* Tabla reporte un poco más densa */
    #tablaReporteDiario.table-condensed > thead > tr > th,
    #tablaReporteDiario.table-condensed > tbody > tr > td { padding: 6px 8px; font-size: 13px; }
    /* Badge de cantidad de observaciones sobre el icono */
    .btn-obsResumen { position: relative; }
    .btn-obsResumen .obs-count {
      position: absolute;
      top: -6px; right: -6px;
      background: #d9534f; color: #fff;
      border-radius: 50%;
      min-width: 18px; height: 18px; line-height: 18px;
      font-size: 10px; font-weight: bold;
      padding: 0 4px;
    }
    /* Badge "+18" al lado de "¿Menores en sala?" (mismo estilo que el de la tabla) */
    .badge-menores18 {
      display: inline-block;
      position: relative;
      width: 26px;
      height: 26px;
      border: 2px solid #d9534f;
      border-radius: 50%;
      text-align: center;
      line-height: 22px;
      color: #000;
      font-weight: bold;
      font-family: Arial, sans-serif;
      font-size: 11px;
      background-color: #fff;
      margin-left: 6px;
      vertical-align: middle;
    }
    .badge-menores18::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 22px;
      height: 2px;
      background-color: #d9534f;
      transform: translate(-50%, -50%) rotate(45deg);
    }
    /* Ícono de cigarrillo al lado de "¿Fumadores?" */
    .icon-fumadores {
      color: #8a6d3b;
      margin-left: 6px;
      font-size: 16px;
      vertical-align: middle;
    }
    /* Botón PDF del listado de eventualidades del día (override del .btn { background:none } global) */
    .btn-pdfEv {
      background-color: #0067b1 !important;
      color: #fff !important;
      border: 1px solid #005a99 !important;
      padding: 2px 8px !important;
      font-weight: bold;
    }
    .btn-pdfEv:hover, .btn-pdfEv:focus { background-color: #005a99 !important; color: #fff !important; text-decoration: none; }
    .btn-pdfEv i { margin-right: 3px; }
    /* Botón "cantidad de eventualidades" del reporte (toggle expand) */
    .btn-toggleEvs {
      background-color: #e7f1fa !important;
      color: #0067b1 !important;
      border: 1px solid #0067b1 !important;
      font-weight: bold;
      padding: 2px 10px !important;
      min-width: 50px;
    }
    .btn-toggleEvs:hover, .btn-toggleEvs:focus { background-color: #0067b1 !important; color: #fff !important; }
    .btn-toggleEvs .caret { margin-left: 4px; transition: transform .15s; }
    /* Botón "Gestionar procedimientos" en el panel del reporte */
    .btn-gestionarProc {
      font-family: Roboto-Condensed;
      font-weight: bold;
      font-size: 12px;
      letter-spacing: 0.5px;
      color: #0067b1;
      border-color: #0067b1;
      background-color: #fff;
      padding: 5px 12px;
    }
    .btn-gestionarProc:hover, .btn-gestionarProc:focus {
      background-color: #0067b1 !important;
      color: #fff !important;
      border-color: #0067b1 !important;
    }
    .btn-gestionarProc i { margin-right: 4px; }
  </style>

@endsection

    <!-- Botón nueva eventualidad -->
      <div class="row">
        <div class="col-lg-6">
          <a href="#modalCargarEventualidad" id="btnNuevaEventualidad" data-toggle="modal" style="text-decoration: none;">
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
                    <option value="0">Sin terminar</option>
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

      <!-- REPORTE DIARIO: movido a su propia página /eventualidades/resumen-diario
           (vista Eventualidades/resumen_diario.blade.php, sólo admin/super). -->

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
<!-- El modal de confirmación de borrado de observación (#modalEliminarObservacion) ahora vive en
     el partial compartido _modal_obs_eventualidad.blade.php (incluido más abajo). -->
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

<!-- (Modal de visado del resumen movido a Eventualidades/resumen_diario.blade.php) -->

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

@include('Eventualidades.partials._modal_obs_eventualidad')


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
            <h5>Procedimientos Realizados <small class="text-muted">(marcá Realizado o No realizado en cada uno)</small></h5>

            <div class="table-responsive">
              <table class="table table-bordered table-sm" id="tablaProcedimientosForm">
                <thead class="thead-light">
                  <tr>
                    <th>Procedimiento</th>
                    <th class="text-center" style="width: 320px;">Respuesta</th>
                    <th>Observación</th>
                  </tr>
                </thead>
                <tbody id="tbodyProcedimientos">
                  <tr><td colspan="3" class="text-center text-muted">Seleccione un casino para ver los procedimientos.</td></tr>
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
              <h5>
                ¿Menores en sala?
                <span class="badge-menores18" title="Indicador de menores en sala">+18</span>
              </h5>
              <select class="form-control" name="menores">
                <option value="No">No</option>
                <option value="Sí">Sí</option>
              </select>
            </div>
            <div class="col-md-3">
              <h5>
                ¿Fumadores?
                <span class="icon-fumadores" title="Indicador de fumadores en sala">🚬</span>
              </h5>
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

          <input type="hidden" name="id_borrador" id="id_borrador" value="">
          <button id="guardarTemporal" type="button" class="btn btn-warning pull-left" title="Guardar como borrador para seguir después">
            <i class="fa fa-clock-o"></i> GUARDADO TEMPORAL
          </button>
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

{{-- ABM de procedimientos: movido a la página Resumen Diario (resumen_diario.blade.php) --}}

  @endsection
  @section('scripts')
  <!-- JavaScript paginacion -->
  <script src="/js/paginacion.js" charset="utf-8"></script>

  <script>
    window.PERMISOS_EVENTUALIDADES = {
      visar_resumen_diario: {{ (isset($usuario) && $usuario->tienePermiso('visar_resumen_diario')) ? 'true' : 'false' }},
      eliminar_observacion: {{ (isset($usuario) && ($usuario->es_administrador || $usuario->es_superusuario)) ? 'true' : 'false' }}
    };
  </script>

  <!-- JavaScript personalizado -->
  <script src="/js/eventualidades/observaciones_eventualidad.js?v=7" charset="utf-8"></script>
  <script src="/js/eventualidades/index.js?v=3" charset="utf-8"></script>

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
