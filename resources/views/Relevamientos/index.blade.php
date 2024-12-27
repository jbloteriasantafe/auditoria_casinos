@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;
setlocale(LC_TIME, 'es_ES.UTF-8');
$usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
$puede_ver = AuthenticationController::getInstancia()->usuarioTieneAlgunRol(
  $usuario->id_usuario,
  ['SUPERUSUARIO','ADMINISTRADOR','AUDITORIA_CALIDAD']
);
$CONTADORES_VISIBLES = min(6,$CONTADORES);
?>

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="/css/paginacion.css">
<link rel="stylesheet" href="css/lista-datos.css">
@endsection

<div class="row">
  <div class="col-xl-3">
    @if($usuario->tienePermiso('relevamiento_cargar'))
    <div class="row">
      <div class="col-xl-12 col-md-4">
        <a data-js-mostrar-modal="[data-js-modal-generar-relevamiento]" href="" id="btn-nuevoRelevamiento" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
            <div class="backgroundNuevo"></div>
            <div class="row">
              <div class="col-xs-12">
                <center>
                  <h5 class="txtLogo">+</h5>
                  <h4 class="txtNuevo">GENERAR RELEVAMIENTO</h4>
                </center>
              </div>
            </div>
          </div>
        </a>
      </div>
      <div class="col-xl-12 col-md-4">
        <a data-js-mostrar-modal="[data-js-modal-relevamiento-sin-sistema]" href="" id="btn-relevamientoSinSistema" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/relevamientos_sin_sistema_white.png"><center>
            <div class="backgroundNuevo"></div>
            <div class="row">
              <div class="col-xs-12">
                <center>
                  <h5 class="txtLogo">+</h5>
                  <h4 class="txtNuevo">RELEVAMIENTOS SIN SISTEMA</h4>
                </center>
              </div>
            </div>
          </div>
        </a>
      </div>
      @endif
      @if($usuario->tienePermiso('relevamiento_selec_maquinas_por_relevamiento'))
      <div data-js-mostrar-modal="[data-js-modal-maquinas-por-relevamiento]" class="col-xl-12 col-md-4">
        <a href="" id="btn-maquinasPorRelevamiento" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/tragaperras_white.png"><center>
            <div class="backgroundNuevo"></div>
            <div class="row">
              <div class="col-xs-12">
                <center>
                  <h5 class="txtLogo">+</h5>
                  <h4 class="txtNuevo">MÁQUINAS POR RELEVAMIENTO</h4>
                </center>
              </div>
            </div>
          </div>
        </a>
      </div>
      @endif
    </div>
  </div>
  <div class="col-xl-9"> <!-- columna TABLA CASINOS -->
    @component('Components/FiltroTabla')
    
    @slot('titulo')
    RELEVAMIENTOS
    @endslot
    
    @slot('target_buscar')
    /relevamientos/buscarRelevamientos
    @endslot
    
    @slot('filtros')
    <div class="col-md-3">
      <h5>Fecha de Relevamiento</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha"' ,'attrs_dtp' => 'data-start-view="2" data-min-view="2"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Casino</h5>
      <select name="casino" class="form-control" data-js-cambio-casino-select-sectores="#sectoresBusqueda">
        <option value="">-Todos los Casinos-</option>
        @foreach($casinos as $casino)
        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <h5>Sector</h5>
      <select id="sectoresBusqueda" name="sector" class="form-control">
        <option value="" data-js-cambio-casino-mantener>-Todos los sectores-</option>
      </select>
    </div>
    <div class="col-md-3">
      <h5>Estado Relevamiento</h5>
      <select name="estadoRelevamiento" class="form-control">
        <option value="">-Todos los estados-</option>
        @foreach($estados as $estado)
        <option value="{{$estado->id_estado_relevamiento}}">{{$estado->descripcion}}</option>
        @endforeach
      </select>
    </div>
    @endslot
    
    @slot('cabecera')
    <tr>
      <th data-js-sortable="relevamiento.fecha" data-js-state="desc" style="text-align: center;">FECHA</th>
      <th data-js-sortable="casino.nombre" style="text-align: center;">CASINO</th>
      <th data-js-sortable="sector.descripcion" style="text-align: center;">SECTOR</th>
      <th data-js-sortable="relevamiento.subrelevamiento" style="text-align: center;">SUB</th>
      <th data-js-sortable="estado_relevamiento.descripcion" style="text-align: center;">ESTADO</th>
      <th style="text-align: center;">ACCION</th>
    </tr>
    <style>
      tr.filaBusqueda td {
        text-align: center;
      }
    </style>
    @endslot
    
    @slot('molde')
    <tr class="filaBusqueda">
      <td class="col-xs-2 fecha">99 MES 99999</td>
      <td class="col-xs-2 casino">CASINO</td>
      <td class="col-xs-2 sector">SECTOR</td>
      <td class="col-xs-1 subrelevamiento">SUB</td>
      <td class="col-xs-2 estado" style="text-align: left;">
        <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faGenerado" data-id-estado-relevamiento="1" style="display: none;"></i>
        <span data-id-estado-relevamiento="1" hidden>Generado</span>
        <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faCargando" data-id-estado-relevamiento="2" style="display: none;"></i>
        <span data-id-estado-relevamiento="2" hidden>Cargando</span>
        <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faFinalizado" data-id-estado-relevamiento="3" style="display: none;"></i>
        <span data-id-estado-relevamiento="3" hidden>Finalizado</span>
        <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faVisado" data-id-estado-relevamiento="4" style="display: none;"></i>
        <span data-id-estado-relevamiento="4" hidden>Visado</span>
        <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faValidado" data-id-estado-relevamiento="7" style="display: none;"></i>
        <span data-id-estado-relevamiento="7" hidden>Rel. Visado</span>
      </td>
      <td class="col-xs-3 acciones" style="text-align: left;">
        @if($puede_ver)
        <button data-js-mostrar-modal-carga="Ver"     class="btn btn-success verDetalle" type="button" title="VER RELEVAMIENTO" data-id-estado-relevamiento="1,2,3,4,7" style="display: none;">
          <i class="fa fa-fw fa-search-plus"></i>
        </button>
        @endif
        @if($usuario->tienePermiso('relevamiento_cargar'))
        <button data-js-click-id-link="relevamientos/generarPlanilla/"  class="btn btn-info planilla" type="button" title="VER PLANILLA" data-id-estado-relevamiento="1" style="display: none;">
          <i class="far fa-fw fa-file-alt"></i>
        </button>
        <button data-js-mostrar-modal-carga="Cargar"  class="btn btn-warning carga" type="button" title="CARGAR RELEVAMIENTO" data-id-estado-relevamiento="1,2" style="display: none;">
          <i class="fa fa-fw fa-upload"></i>
        </button>
        @endif
        @if($usuario->tienePermiso('relevamiento_validar'))
        <button data-js-mostrar-modal-carga="Validar" class="btn btn-success validar" type="button" title="VISAR RELEVAMIENTO" data-id-estado-relevamiento="3" style="display: none;">
          <i class="fa fa-fw fa-check"></i>
        </button>
        @endif
        <button data-js-click-id-link="relevamientos/generarPlanilla/" class="btn btn-info imprimir" type="button" title="IMPRIMIR PLANILLA" data-id-estado-relevamiento="2,3,4,7" style="display: none;">
          <i class="fa fa-fw fa-print"></i>
        </button>
        <button data-js-click-id-link="relevamientos/generarPlanillaValidado/" class="btn btn-success validado" type="button" title="IMPRIMIR VISADO" data-id-estado-relevamiento="7" style="display: none;">
          <i class="fa fa-fw fa-bookmark"></i>
        </button>
      </td>
    </tr>
    @endslot
    
    @endcomponent
  </div><!-- /.col-lg-12 col-xl-9 -->
</div>  <!-- /#row -->

@component('Relevamientos/maquinasPorRelevamientos',['casinos' => $usuario->casinos,'tipos_cantidad' => $tipos_cantidad])
@endcomponent

@component('Relevamientos/generarRelevamiento',['casinos' => $usuario->casinos, 'es_superusuario' => $usuario->es_superusuario])
@endcomponent

@component('Relevamientos/relevamientoSinSistema',['casinos' => $usuario->casinos])
@endcomponent

@component('Relevamientos/cargarRelevamiento',compact('CONTADORES','CONTADORES_VISIBLES','tipos_causa_no_toma'))
@endcomponent

<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| RELEVAMIENTOS</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Relevamientos</h5>
  <p>
    Se observan los últimos relevamientos generados en el sistema, con sus respectivos estados que son detallados en la vista.
    Se podrán cargar, editar, imprimir estas planillas, dependiendo en el estado en que se encuentre el relevamiento deseado.
    Además, se podrán generar nuevos relevamientos, implementándose la opción de que el sistema esté fuera de servicio.
    Produce un archivo con formato .zip conteniendo en ellos relevamientos para 7 días, los cuales se cargarán cuando el sistema vuelva a estar en línea.
    Y también se podrán seleccionar máquinas por relevamiento, considerando el casino, la fecha de inicio/final, su tipo y la cantidad de máquinas que requiera.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JS paginacion -->
<script src="/js/paginacion.js" charset="utf-8"></script>

<!-- JavaScript personalizado -->
<!-- ?version para forzar que se recarge el script en el navegador del cliente -->
<script src="/js/Relevamientos/index.js?1" type="module" charset="utf-8"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Custom input Bootstrap -->
<script src="js/fileinput.min.js" type="text/javascript"></script>
<script src="js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<script src="js/inputSpinner.js" type="text/javascript"></script>
<script src="js/lista-datos.js" type="text/javascript"></script>
<script src="js/math.min.js" type="text/javascript"></script>
@endsection
