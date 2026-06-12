@extends('includes.dashboard')

@section('headerLogo')
  <span class="etiquetaLogoExpedientes">@svg('expedientes', 'iconoExpedientes')</span>
  <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('contenidoVista')

  @section('estilos')
    <link href="/css/bootstrap-datetimepicker.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/css/bootstrap-datetimepicker.css">
    <link rel="stylesheet" href="/css/paginacion.css" />
    <style>
      #tablaReporteDiario td {
        vertical-align: middle;
      }

      /* Círculo de estado del resumen — los colores i.faVisado/i.faNoVisado viven en estiloDashboard.css. */
      .estado-resumen {
        font-weight: normal;
        white-space: nowrap;
      }

      .estado-resumen i.faVisado,
      .estado-resumen i.faNoVisado {
        position: static;
        left: auto;
        margin-right: 4px;
      }

      #tablaReporteDiario.table-condensed>thead>tr>th,
      #tablaReporteDiario.table-condensed>tbody>tr>td {
        padding: 6px 8px;
        font-size: 13px;
      }

      /* Badge de cantidad de observaciones sobre el icono */
      .btn-obsResumen {
        position: relative;
      }

      .btn-obsResumen .obs-count {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #d9534f;
        color: #fff;
        border-radius: 50%;
        min-width: 18px;
        height: 18px;
        line-height: 18px;
        font-size: 10px;
        font-weight: bold;
        padding: 0 4px;
      }

      /* Botón PDF del listado de eventualidades del día (override del .btn { background:none } global) */
      .btn-pdfEv {
        background-color: #0067b1 !important;
        color: #fff !important;
        border: 1px solid #005a99 !important;
        padding: 2px 8px !important;
        font-weight: bold;
      }

      .btn-pdfEv:hover,
      .btn-pdfEv:focus {
        background-color: #005a99 !important;
        color: #fff !important;
        text-decoration: none;
      }

      .btn-pdfEv i {
        margin-right: 3px;
      }

      /* Botón "cantidad de eventualidades" del reporte (toggle expand) */
      .btn-toggleEvs {
        background-color: #e7f1fa !important;
        color: #0067b1 !important;
        border: 1px solid #0067b1 !important;
        font-weight: bold;
        padding: 2px 10px !important;
        min-width: 50px;
      }

      .btn-toggleEvs:hover,
      .btn-toggleEvs:focus {
        background-color: #0067b1 !important;
        color: #fff !important;
      }

      .btn-toggleEvs .caret {
        margin-left: 4px;
        transition: transform .15s;
      }

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

      .btn-gestionarProc:hover,
      .btn-gestionarProc:focus {
        background-color: #0067b1 !important;
        color: #fff !important;
        border-color: #0067b1 !important;
      }

      .btn-gestionarProc i {
        margin-right: 4px;
      }
    </style>
  @endsection

  <!-- FILTROS -->
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
          <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
        </div>
        <div id="collapseFiltros" class="panel-collapse collapse in">
          <div class="panel-body">
            <div class="row">
              <div class="col-lg-3">
                <h5>Fecha desde</h5>
                <div class="input-group date" id="dtpFechaEv">
                  <input type="text" class="form-control" placeholder="yyyy-mm-dd" id="B_fecha_ev" readonly />
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i
                      class="fa fa-times"></i></span>
                  <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-lg-3">
                <h5>Fecha hasta</h5>
                <div class="input-group date" id="dtpFechaEvHasta">
                  <input type="text" class="form-control" placeholder="yyyy-mm-dd" id="B_fecha_evhasta" readonly />
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i
                      class="fa fa-times"></i></span>
                  <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-lg-3">
                <h5>Casino</h5>
                <select class="form-control" id="B_CasinoEv">
                  <option value="">Todos los casinos</option>
                  @foreach($casinos as $c)
                    <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-lg-3">
                <h5>Con observaciones</h5>
                <input type="checkbox" id="B_ObservadoResumen" value="1">
              </div>
            </div>
            <div class="row" style="margin-top:18px;">
              <div class="col-md-12 text-center">
                </br>
                <button id="btn-buscarResumen" class="btn btn-infoBuscar">
                  <i class="fa fa-search"></i> BUSCAR
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- REPORTE DIARIO POR CASINO -->
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading" data-toggle="collapse" href="#collapseReporteDiario"
          style="cursor:pointer; overflow:hidden;">
          @if(isset($usuario) && $usuario->tienePermiso('abm_procedimientos'))
            <button type="button" id="btnAbrirABMProcedimientos" class="btn btn-default btn-sm btn-gestionarProc pull-right"
              title="Gestionar procedimientos del sistema">
              <i class="fa fa-cog"></i> GESTIONAR PROCEDIMIENTOS
            </button>
          @endif
          <h4 style="margin:0; line-height:30px;">REPORTE DIARIO POR CASINO — COBERTURA DE PROCEDIMIENTOS <i
              class="fa fa-fw fa-angle-down"></i></h4>
        </div>
        <div id="collapseReporteDiario" class="panel-collapse collapse in">
          <div class="panel-body">
            <table id="tablaReporteDiario" class="table table-condensed">
              <thead>
                <tr>
                  <th style="width:10%;">FECHA</th>
                  <th style="width:15%;">CASINO</th>
                  <th style="width:22%; white-space:nowrap;" class="text-center">CANT. DE EVENTUALIDADES</th>
                  <th style="width:24%;">COBERTURA</th>
                  <th style="width:14%;" class="text-center">ESTADO</th>
                  <th style="width:15%;" class="text-center">ACCIONES</th>
                </tr>
              </thead>
              <tbody id="cuerpoReporteDiario">
                <tr>
                  <td colspan="6" class="text-center text-muted">Cargando…</td>
                </tr>
              </tbody>
            </table>
            <div id="herramientasPaginacionResumen" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detalle Cobertura -->
  <div class="modal fade" id="modalDetalleCobertura" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header modalNuevo">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <h3 class="modal-title">| DETALLE DE COBERTURA <small id="detalleCabecera" class="text-white"></small></h3>
        </div>
        <div class="modal-body modalCuerpo">
          <h5 style="color:#3c763d;"><i class="fa fa-check"></i> Realizados</h5>
          <ul id="listaRealizados" class="list-unstyled" style="padding-left:18px;"></ul>
          <hr>
          <h5 style="color:#a94442;"><i class="fa fa-times"></i> No realizados</h5>
          <ul id="listaNoRealizados" class="list-unstyled" style="padding-left:18px;"></ul>
          <hr>
          <h5><i class="fa fa-file-text-o"></i> Eventualidades del día</h5>
          <div id="cuerpoEventualidadesDia"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">CERRAR</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Observaciones del Resumen Diario -->
  <div class="modal fade" id="modalObsResumen" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header modalNuevo">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <h3 class="modal-title">| OBSERVACIONES DEL RESUMEN <small id="obsResumenCabecera" class="text-white"></small>
          </h3>
        </div>
        <div class="modal-body modalCuerpo">
          <ul id="listaObsResumen" class="list-group" style="max-height:300px; overflow:auto;"></ul>
          <hr>
          <h5>Agregar observación</h5>
          <textarea id="obsResumenTexto" class="form-control" rows="3" maxlength="5000"
            placeholder="Escribí la observación (opcional si vas a subir un archivo)..."></textarea>
          <div class="input-group" style="margin-top:8px;">
            <span class="input-group-btn">
              <button class="btn" type="button" onclick="$('#obsResumenFile').click()"
                style="background:#0067b1; color:#fff; border:1px solid #005a99;">
                <i class="fa fa-paperclip"></i> Adjuntar archivo
              </button>
            </span>
            <input type="text" id="obsResumenFileName" class="form-control" placeholder="Sin archivos" readonly>
            <input type="file" id="obsResumenFile" multiple style="display:none;">
          </div>
          <div class="text-right" style="margin-top:8px;">
            <button type="button" id="btnGuardarObsResumen" class="btn btn-infoBuscar">
              <i class="fa fa-save"></i> GUARDAR OBSERVACIÓN
            </button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">CERRAR</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Visar / Quitar visado del resumen diario -->
  <div class="modal fade" id="modalVisarResumen" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <h3 class="modal-title" id="tituloModalVisarResumen" style="background-color: #6dc7be;">| VISAR RESUMEN DIARIO
          </h3>
        </div>
        <div class="modal-body franjaRojaModal">
          <div class="form-group">
            <div class="col-xs-12">
              <strong id="textoModalVisarResumen">¿Seguro desea VISAR el resumen diario?</strong>
              <p class="text-muted" id="detalleModalVisarResumen" style="margin-top:8px; margin-bottom:0;"></p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-confirmarVisarResumen">VISAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>
      </div>
    </div>
  </div>

  @include('Eventualidades.partials._modal_obs_eventualidad')

  @if(isset($usuario) && $usuario->tienePermiso('abm_procedimientos'))
    @include('Eventualidades.abm_procedimientos')
  @endif

@endsection

@section('scripts')
  <script>
    window.PERMISOS_EVENTUALIDADES = {
      visar_resumen_diario: {{ (isset($usuario) && $usuario->tienePermiso('visar_resumen_diario')) ? 'true' : 'false' }},
      eliminar_observacion: {{ (isset($usuario) && ($usuario->es_administrador || $usuario->es_superusuario)) ? 'true' : 'false' }}
              };
  </script>

  <!-- Paginación (mismo plugin que la tabla de eventualidades) -->
  <script src="/js/paginacion.js" charset="utf-8"></script>

  <!-- DateTimePicker -->
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <!-- JavaScript del módulo (?v= = cache-bust: forzar al navegador a bajar la versión nueva) -->
  <script src="/js/eventualidades/observaciones_eventualidad.js?v=8" charset="utf-8"></script>
  <script src="/js/eventualidades/resumen_diario.js?v=7" charset="utf-8"></script>
  @if(isset($usuario) && $usuario->tienePermiso('abm_procedimientos'))
    <script src="/js/eventualidades/abm_procedimientos.js?v=5" charset="utf-8"></script>
  @endif
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
  <h3 class="modal-title" style="color: #fff;">| Resumen diario de eventualidades</h3>
@endsection
@section('contenidoAyuda')
  <div class="col-md-12">
    <h5>Tarjeta de Resumen diario de eventualidades</h5>
    <p>
      Resumen diario de eventualidades sobre novedades y eventos que alteren el normal funcionamiento.
    </p>
  </div>
@endsection
<!-- Termina modal de ayuda -->