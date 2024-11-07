@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<!-- <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/> -->
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel="stylesheet" href="css/lista-datos.css">
@endsection

@component('Components/listasAutocompletar',[
  'data' => $fiscalizadores,
  'selector_id_casino' => "[data-js-modal-ver-cargar-validar-layout-parcial] [name='id_casino']",
  'selector_str'       => "[data-js-modal-ver-cargar-validar-layout-parcial] [name='fiscalizador_toma']",
  'selector_output_id' => "[data-js-modal-ver-cargar-validar-layout-parcial] [name='id_fiscalizador_toma']",
  'get_id_casino'    => function($f){return $f->id_casino;},
  'get_id'           => function($f){return $f->id_usuario;},
  'get_str'          => function($f){return $f->nombre;},
  'outputCasListId'  => 'listasFiscalizadoresCas',
])
@endcomponent

<style>
  tr.filaCabeceraFiltro th,
  tr.filaCuerpoFiltro td {
    text-align: center;
  }
</style>

<div class="row">
  <div class="col-xl-9"> <!-- columna TABLA CASINOS -->
    @component('Components/FiltroTabla')
    
    @slot('titulo')
    LAYOUT PARCIAL GENERADO POR EL SISTEMA
    @endslot
    
    @slot('target_buscar')
    /layout_parcial/buscarLayoutsParciales
    @endslot
    
    @slot('filtros')
    <div class="col-md-3">
      <h5>Fecha</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Casino</h5>
      <select class="form-control" name="id_casino"  data-js-cambio-casino-select-sectores="#destinoSectoresFiltro">
        @if(count($casinos) != 1)
        <option value="">-Todos los Casinos-</option>
        @endif
        @foreach ($casinos as $c)
        <option value="{{$c->id_casino}}" {{ count($casinos) == 1? 'selected' : '' }}>{{$c->nombre}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <h5>Sector</h5>
      <select class="form-control" name="id_sector" id="destinoSectoresFiltro">
        <option value="" data-js-cambio-casino-mantener>-Todos los sectores-</option>
      </select>
    </div>
    <div class="col-md-3">
      <h5>Estado Relevamiento</h5>
      <select class="form-control" name="id_estado_relevamiento">
        <option value="">-Todos los estados-</option>
        @foreach($estados as $e)
        <option value="{{$e->id_estado_relevamiento}}">{{$e->descripcion}}</option>
        @endforeach
      </select>
    </div>
    @endslot
    
    @slot('cabecera')
    <tr class="filaCabeceraFiltro">
      <th class="col-xs-2" data-js-sortable="layout_parcial.fecha" data-js-state="desc">FECHA</th>
      <th class="col-xs-2" data-js-sortable="casino.nombre">CASINO</th>
      <th class="col-xs-2" data-js-sortable="sector.descripcion">SECTOR</th>
      <th class="col-xs-1" data-js-sortable="layout_parcial.sub_control">SUB</th>
      <th class="col-xs-2" data-js-sortable="estado_relevamiento.descripcion">ESTADO</th>
      <th class="col-xs-3">ACCIÓN </th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr class="filaCuerpoFiltro">
      <td class="fecha">FECHA</td>
      <td class="casino">CASINO</td>
      <td class="sector">SECTOR</td>
      <td class="subrelevamiento">SUBRELEVAMIETNO</td>
      <td>
        <?php 
          $est = $estados->mapWithKeys(function($e){
            return [$e['descripcion'] => $e['id_estado_relevamiento']];
          });;
        ?>
        <span data-id_estado_relevamiento="{{$est['Generado']}}">
          <i class="fas fa-fw fa-dot-circle faGenerado" title="Generado"></i>Generado
        </span>
        <span data-id_estado_relevamiento="{{$est['Cargando']}}">
          <i class="fas fa-fw fa-dot-circle faCargando" title="Cargando"></i>Cargando
        </span>
        <span data-id_estado_relevamiento="{{$est['Finalizado']}}">
          <i class="fas fa-fw fa-dot-circle faFinalizado" title="Finalizado"></i>Finalizado
        </span>
        <span data-id_estado_relevamiento="{{$est['Visado']}}">
          <i class="fas fa-fw fa-dot-circle" title="Visado"></i>Visado
        </span>
      </td>
      <td class="col-xs-3">
        @if($carga_layout_parcial)
        <button class="btn btn-warning carga" type="button"   data-js-abrir-modal="CARGAR"  data-id_estado_relevamiento="{{$est['Generado']}},{{$est['Cargando']}}">
          <i class="fa fa-fw fa-upload"></i>
        </button>
        @endif
        @if($validar_layout_parcial)
        <button class="btn btn-success validar" type="button" data-js-abrir-modal="VALIDAR" data-id_estado_relevamiento="{{$est['Finalizado']}}">
          <i class="fa fa-fw fa-check"></i>
        </button>
        <button class="btn btn-success ver" type="button"     data-js-abrir-modal="VER"     data-id_estado_relevamiento="{{$est['Visado']}}">
          <i class="fa fa-fw fa-search-plus"></i>
        </button>
        @endif
        @if($ver_planilla_layout_parcial)
        <button class="btn btn-info planilla" type="button"   data-js-planilla="/layout_parcial/generarPlanillaLayoutParcial">
          <i class="far fa-fw fa-file-alt"></i>
        </button>
        @endif
        <button class="btn btn-info imprimir" type="button"   data-js-planilla="/layout_parcial/generarPlanillaLayoutParcialCargado" data-id_estado_relevamiento="{{$est['Finalizado']}},{{$est['Visado']}}">
          <i class="fa fa-fw fa-print"></i>
        </button>
      </td>
    </tr>
    @endslot
    
    @endcomponent
  </div>
  <div class="col-xl-3">
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

@component('Components/modal',[
  'clases_modal' => 'modalLayoutParcial',
  'attrs_modal' => 'data-js-modal-layout-parcial',
  'estilo_cabecera' => 'background-color: #6dc7be;',
])

@slot('titulo')
| NUEVO CONTROL LAYOUT 
@endslot

@slot('cuerpo')
<form class="form-horizontal" novalidate="">
  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <h5>FECHA</h5>
      <?php setlocale(LC_TIME, 'es_ES.UTF-8'); ?>
      <input style="text-align:center" type='text' class="form-control" readonly value="{{ucwords(strftime('%A, %d %B %Y'))}}">
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-md-6">
      <h5>CASINO</h5>
      <select class="form-control" name="id_casino" data-js-cambio-casino-select-sectores="#destinoSectores">
        @if(count($casinos) != 1)
        <option value="">- Seleccione un casino -</option>
        @endif
        @foreach ($casinos as $c)
        <option value="{{$c->id_casino}}" {{count($casinos) == 1? 'selected' : ''}}>{{$c->nombre}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-6">
      <h5>SECTOR</h5>
      <select id="destinoSectores" class="form-control" name="id_sector" >
      </select>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-md-6">
      <h5>MÁQUINAS</h5>
      <div class="input-group number-spinner">
        <span class="input-group-btn">
          <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="dwn">-</button>
        </span>
        <input name="cantidad_maquinas" type="text" class="form-control text-center" value="10" data-default="10">
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
        <input name="cantidad_fiscalizadores" type="text" class="form-control text-center" value="1" data-default="1">
        <span class="input-group-btn">
          <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="up">+</button>
        </span>
      </div>
    </div>
  </div>
</form>
<div data-js-icono-carga class="sk-folding-cube" hidden>
  <div class="sk-cube1 sk-cube"></div>
  <div class="sk-cube2 sk-cube"></div>
  <div class="sk-cube4 sk-cube"></div>
  <div class="sk-cube3 sk-cube"></div>
</div>
@endslot

@slot('pie')
<button type="button" class="btn btn-successAceptar" data-js-generar>GENERAR</button>
@endslot

@endcomponent

@component('Components/modal',[
  'clases_modal' => 'modalLayoutParcialSinSistema',
  'attrs_modal' => 'data-js-modal-layout-parcial-sin-sistema',
  'estilo_cabecera' => 'background-color: #6dc7be;',
])

@slot('titulo')
| NUEVO CONTROL LAYOUT SIN SISTEMA
@endslot

@slot('cuerpo')
<form class="form-horizontal row" novalidate="">
  <div><!-- Nose porque tengo que ponerlo dentro de otro div para que se alinee -->
    <div class="col-md-6">
      <h5>FECHA DE CONTROL LAYOUT</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha"'])
      @endcomponent
    </div>
    <div class="col-md-6">
      <h5>FECHA DE GENERACIÓN</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_generacion"'])
      @endcomponent
    </div>
  </div>
  <div class="col-md-6">
    <h5>CASINO</h5>
    <select class="form-control" name="id_casino"  data-js-cambio-casino-select-sectores="#destinoSectoresSinSistema">
      <option value="">- Seleccione un casino -</option>
      @foreach ($casinos as $casino)
      <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-6">
    <h5>SECTOR</h5>
    <select class="form-control" name="id_sector" id="destinoSectoresSinSistema">
    </select>
  </div>
</form>
<div data-js-icono-carga class="sk-folding-cube" hidden>
  <div class="sk-cube1 sk-cube"></div>
  <div class="sk-cube2 sk-cube"></div>
  <div class="sk-cube4 sk-cube"></div>
  <div class="sk-cube3 sk-cube"></div>
</div>
@endslot

@slot('pie')
<button type="button" class="btn btn-successAceptar" data-js-usar-relevamiento-backup>USAR RELEVAMIENTO BACKUP</button>
@endslot

@endcomponent


<style>
  .modalVerCargarValidarLayoutParcial[data-css-modo="VER"] .modal-header {
    background-color: red;
  }
  .modalVerCargarValidarLayoutParcial[data-css-modo="CARGAR"] .modal-header {
    background-color: green;
  }
  .modalVerCargarValidarLayoutParcial[data-css-modo="VALIDAR"] .modal-header {
    background-color: blue;
  }
  .modalVerCargarValidarLayoutParcial .tablaRelevado th {
    text-align: center;
  }
  .modalVerCargarValidarLayoutParcial [data-css-seleccion-correcta="1"] {
    box-shadow: green 0em 0em 0.3em;
  }
  .modalVerCargarValidarLayoutParcial [data-css-seleccion-correcta="0"] {
    box-shadow: red 0em 0em 0.3em;
  }
</style>

<style>
  
</style>

@component('Components/modal',[
  'clases_modal' => 'modalVerCargarValidarLayoutParcial',
  'attrs_modal' => 'data-js-modal-ver-cargar-validar-layout-parcial',
  'grande' => 90,
])

@slot('titulo')
| LAYOUT PARCIAL
@endslot

@slot('cuerpo')
<form class="form-horizontal row" novalidate="">
  <input name="id_casino" hidden>
  <input name="id_layout_parcial" hidden>
  <div class="row">
    <div class="col-lg-2 col-lg-offset-1">
      <h5>FECHA DE CONTROL LAYOUT</h5>
      <input name="fecha" type='text' class="form-control" data-js-modo-habilitar="">
    </div>
    <div class="col-lg-2">
      <h5>FECHA DE GENERACIÓN</h5>
      <input name="fecha_generacion" type='text' class="form-control" data-js-modo-habilitar="">
    </div>
    <div class="col-lg-2">
      <h5>CASINO</h5>
      <input name="casino" type='text' class="form-control" data-js-modo-habilitar="">
    </div>
    <div class="col-lg-2">
      <h5>SECTOR</h5>
      <input name="sector" type='text' class="form-control" data-js-modo-habilitar="">
    </div>
    <div class="col-lg-2">
      <h5>SUB RELEVAMIENTO</h5>
      <input name="subrelevamiento" type='text' class="form-control" data-js-modo-habilitar="">
    </div>
  </div>
  <div class="row">
    <div class="col-md-2 col-md-offset-1">
      <h5>FISCALIZADOR CARGA</h5>
      <input name="fiscalizador_carga" value="{{$nombre_usuario}}" type="text"class="form-control" data-js-modo-habilitar="" data-js-modo-ver="CARGAR">
      <input name="fiscalizador_carga_recibido" type="text"class="form-control" data-js-modo-habilitar="" data-js-modo-ver="VER,VALIDAR">
    </div>
    <div class="col-md-2">
      <h5>FISCALIZADOR TOMA</h5>
      <input name="fiscalizador_toma" list="listasFiscalizadoresCas" class="form-control" type="text" autocomplete="off" data-js-modo-habilitar="CARGAR">
      <input name="id_fiscalizador_toma" data-js-selecciono-id-fiscalizador="[name='fiscalizador_toma']" hidden>
    </div>
    <div class="col-md-2">
      <h5>TÉCNICO</h5>
      <input name="tecnico" type="text"class="form-control" data-js-modo-habilitar="CARGAR">
    </div>
    <div class="col-md-3">
      <h5>FECHA EJECUCIÓN</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_ejecucion"', 'attrs_dtp' => 'data-date-format="yyyy-mm-dd HH:ii" data-js-modo-habilitar="CARGAR"'])
      @endcomponent
    </div>
  </div>
  <br>
  <br>
  <div class="row" data-js-modo-ver="CARGAR">
    <div class="col-md-12">
      <p style="font-family:'Roboto-Regular';font-size:16px;margin-left:20px;">
        <i class="fa fa-fw fa-exclamation" style="color:#2196F3"></i> Haga doble click sobre los campos para entrar y salir del modo edición.
      </p>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
      <table data-js-tabla-relevado class="table tablaRelevado">
        <thead>
          <tr>
            <th>MTM</th>
            <th>ISLA</th>
            <th>FABRICANTE</th>
            <th>JUEGO</th>
            <th>N° SERIE</th>
            <th>NT</th>
            <th>D. SALA</th>
            <th>% DEV</th>
            @if($ver_seccion_maquinas)
            <th>MAQ</th>
            @endif
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <div class="row" data-js-modo-ver="VER,CARGAR,VALIDAR">
    <div class="col-md-8 col-md-offset-2">
      <h5>OBSERVACIONES FISCALIZACIÓN</h5>
      <textarea name="observacion_fiscalizacion" class="form-control" style="resize:vertical;" data-js-modo-habilitar="CARGAR"></textarea>
    </div>
  </div>
  <div class="row" data-js-modo-ver="VER,VALIDAR">
    <div class="col-md-8 col-md-offset-2">
      <h5>OBSERVACIONES</h5>
      <textarea name="observacion_validacion" class="form-control" style="resize:vertical;" data-js-modo-habilitar="VALIDAR"></textarea>
    </div>
  </div>
</form>
<table hidden>
  <tr data-js-molde-relevado>
    <td>
      <input class="form-control" data-dyn-name="nro_admin" type="text" readonly>
    </td>
    <td>
      <input class="form-control" data-dyn-name="nro_isla" type="text" data-js-editable-original="" data-js-modo-habilitar="CARGAR">
    </td>
    <td>
      <input class="form-control" data-dyn-name="marca" type="text" data-js-editable-original="" data-js-modo-habilitar="CARGAR">
    </td>
    <td>
      <input class="form-control" data-dyn-name="juego" type="text" data-js-editable-original="" data-js-modo-habilitar="CARGAR">
    </td>
    <td>
      <input class="form-control" data-dyn-name="nro_serie" type="text" data-js-editable-original="" data-js-modo-habilitar="CARGAR">
    </td>
    <td>
      <input class="checkboxLayout" type="checkbox" 
        data-js-cambio-limpiar="[data-dyn-name='denominacion'],[data-dyn-name='pdev']" 
        data-js-cambio-asignar-val="[data-dyn-name='no_toma']"
        data-js-modo-habilitar="CARGAR">
      <input data-dyn-name="no_toma" hidden>
    </td>
    <td>
      <input class="form-control" type="text" data-dyn-name="denominacion" data-js-modo-habilitar="CARGAR">
    </td>
    <td>
      <input class="form-control" type="text" data-dyn-name="pdev" data-js-modo-habilitar="CARGAR">
    </td>
    @if($ver_seccion_maquinas)
    <td>
      <a class="btn btn-success pop link_maquinas" type="button" href="http://10.1.121.30:8000/maquinas" target="_blank" data-placement="top" data-trigger="hover" title="GESTIONAR MÁQUINA" data-content="Ir a sección máquina" style="">
        <i class="fa fa-fw fa-wrench"></i>
      </a>
    </td>
    @endif
  </tr>
</table>
@endslot

@slot('pie')
<button type="button" class="btn btn-warningModificar" data-js-enviar-form="layout_parcial/guardarLayoutParcial"   data-js-modo-ver="CARGAR" style="float: left;">GUARDAR RELEVAMIENTO</button>
<button type="button" class="btn btn-successAceptar"   data-js-enviar-form="layout_parcial/finalizarLayoutParcial" data-js-modo-ver="CARGAR">FINALIZAR RELEVAMIENTO</button>
<button type="button" class="btn btn-successAceptar"   data-js-enviar-form="layout_parcial/validarLayoutParcial"   data-js-modo-ver="VALIDAR">VALIDAR RELEVAMIENTO</button>
@endslot

@endcomponent

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
<script src="js/seccionLayoutParcial.js?6" type="module" charset="utf-8"></script>
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
