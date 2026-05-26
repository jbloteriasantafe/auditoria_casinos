@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
$usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
?>

@section('estilos')
<link rel="stylesheet" href="/css/paginacion.css">
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/animacionCarga.css">

<style>
  [data-visualizando="importaciones"] [data-visible]:not([data-visible="importaciones"]) {
    display: none;
  }
  [data-visualizando="registros"] [data-visible]:not([data-visible="registros"]) {
    display: none;
  }
  select[readonly] {
    pointer-events: none;
  }
  [data-js-filtro-tabla-filtro] {
    display: none;
  }
</style>
@endsection

@section('contenidoVista')

<?php 
  $idParentFiltros = uniqid();
  $idFiltroTablaImportaciones = uniqid();
  $idFiltroTablaRegistros = uniqid();
  $idModalImportar = uniqid();
  $idClearImportacion = uniqid();
  
  $id_casino_change_set   = "#{$idFiltroTablaImportaciones} [name='id_casino'],#{$idFiltroTablaRegistros} [name='id_casino'],#{$idModalImportar} [name='id_casino']";
  $informado_change_set_0 = "#{$idFiltroTablaImportaciones} [name='informado[0]'],#{$idFiltroTablaRegistros} [name='informado[0]']";
  $informado_change_set_1 = "#{$idFiltroTablaImportaciones} [name='informado[1]'],#{$idFiltroTablaRegistros} [name='informado[1]']";
  $reportado_change_set_0 = "#{$idFiltroTablaImportaciones} [name='reportado[0]'],#{$idFiltroTablaRegistros} [name='reportado[0]']";
  $reportado_change_set_1 = "#{$idFiltroTablaImportaciones} [name='reportado[1]'],#{$idFiltroTablaRegistros} [name='reportado[1]']";
  $edad_change_set_0      = "#{$idFiltroTablaImportaciones} [name='edad[0]'],#{$idFiltroTablaRegistros} [name='edad[0]']";
  $edad_change_set_1      = "#{$idFiltroTablaImportaciones} [name='edad[1]'],#{$idFiltroTablaRegistros} [name='edad[1]']";
  $filtros = "#$idFiltroTablaImportaciones,#$idFiltroTablaRegistros";
  $clearMD5 = "#$idClearImportacion,#{$idFiltroTablaImportaciones} [name='md5'],#{$idFiltroTablaRegistros} [name='md5']";
?>
<div class="row" data-visualizando="importaciones" id="{{$idParentFiltros}}">
  <div  class="col-md-12">
    <div class="row panel panel-default">
      <div class="panel-body">
        <div class="row">
          <div class="col-md-2">
            <h5>Visualizar</h5>
            <select class="form-control" data-js-change-visualizando="#{{$idParentFiltros}}" value="importaciones">
              <option value="importaciones">Importaciones</option>
              <option value="registros">Registros</option>
            </select>
          </div>
          <div class="col-md-2">
            <h5>Casino</h5>
            <select data-js-change-trigger-buscar="{!! $filtros !!}" data-js-change-clear="{{$clearMD5}}" data-js-change-set="{!! $id_casino_change_set !!}" class="form-control" value="{{ count($casinos)? $casinos[0]->id_casino : '' }}">
              @foreach(($casinos ?? []) as $c)
              <option value='{{$c->id_casino}}'>{{$c->nombre}}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2" data-visible="registros">          
            <h5>Importación</h5>
            <div style="display: flex;">
              <input id="{{$idClearImportacion}}" class="form-control" name="md5" readonly style="width: 20em;" placeholder="IMPORTACIÓN">
              <button class="btn" type="button" title="ver" data-js-click-asignar-md5="importaciones">
                <i class="fa fa-times"></i>
                <span hidden data-key="md5"></span>
              </button>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3">
            <h5>Informado</h5>
            <div style="display: flex;">
              @component('Components/inputFecha',[
                'attrs' => 'data-js-change-trigger-buscar="'.$filtros.'" data-js-change-set="'.$informado_change_set_0.'" data-js-change-clear="'.$clearMD5.'"',
                'attrs_dtp' => 'data-date-format="yyyy-mm-dd" data-start-view="year" data-min-view="month"',
                'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
                'placeholder' => 'DESDE'
              ])
              @endcomponent
              @component('Components/inputFecha',[
                'attrs' => 'data-js-change-trigger-buscar="'.$filtros.'" data-js-change-set="'.$informado_change_set_1.'" data-js-change-clear="'.$clearMD5.'"',
                'attrs_dtp' => 'data-date-format="yyyy-mm-dd" data-start-view="year" data-min-view="month"',
                'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
                'placeholder' => 'HASTA'
              ])
              @endcomponent
            </div>
          </div>
          <div class="col-md-3">
            <h5>Reportado</h5>
            <div style="display: flex;">
              @component('Components/inputFecha',[
                'attrs' => 'data-js-change-trigger-buscar="'.$filtros.'" data-js-change-set="'.$reportado_change_set_0.'"',
                'attrs_dtp' => 'data-date-format="yyyy-mm-dd" data-start-view="year" data-min-view="month"',
                'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
                'placeholder' => 'DESDE'
              ])
              @endcomponent
              @component('Components/inputFecha',[
                'attrs' => 'data-js-change-trigger-buscar="'.$filtros.'" data-js-change-set="'.$reportado_change_set_1.'"',
                'attrs_dtp' => 'data-date-format="yyyy-mm-dd" data-start-view="year" data-min-view="month"',
                'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
                'placeholder' => 'HASTA'
              ])
              @endcomponent
            </div>
          </div>
          <div class="col-md-3">
            <h5>Edad (a fecha de reporte)</h5>
            <div style="display: flex;">
              <div style="padding: 0 !important;flex: 1;">
                <input class="form-control" data-js-change-trigger-buscar="{!! $filtros !!}" data-js-change-set="{!! $edad_change_set_0 !!}">
              </div>
              <div style="padding: 0 !important;flex: 1;">
                <input class="form-control" data-js-change-trigger-buscar="{!! $filtros !!}" data-js-change-set="{!! $edad_change_set_1 !!}">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div data-visible="importaciones" class="col-md-12">
    @component('Components/FiltroTabla',['id' => $idFiltroTablaImportaciones])
      @slot('titulo')
      Importaciones Registros DNI
      <button class="btn" type="button" data-js-click-mostrar-modal="#{{$idModalImportar}}">IMPORTAR</button>
      @endslot
      
      @slot('target_buscar')
      /registrosDNI/buscar/importaciones
      @endslot
      
      @slot('filtros')
      <div class="col-md-12">
        @section('filtrosComunes')
        <div class="col-md-3" hidden>
          <h5>Casino</h5>
          <select readonly class="form-control" name="id_casino" value="{{ count($casinos)? $casinos[0]->id_casino : '' }}">
            @foreach(($casinos ?? []) as $c)
            <option value='{{$c->id_casino}}'>{{$c->nombre}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3" hidden>
          <h5>Informado</h5>
          <div style="display: flex;">
            @component('Components/inputFecha',[
              'attrs' => 'name="informado[0]"',
              'attrs_dtp' => 'data-date-format="yyyy-mm-dd" data-start-view="year" data-min-view="month"',
              'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
              'placeholder' => 'DESDE',
              'readonly' => 1
            ])
            @endcomponent
            @component('Components/inputFecha',[
              'attrs' => 'name="informado[1]"',
              'attrs_dtp' => 'data-date-format="yyyy-mm-dd" data-start-view="year" data-min-view="month"',
              'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
              'placeholder' => 'HASTA',
              'readonly' => 1
            ])
            @endcomponent
          </div>
        </div>
        <div class="col-md-3" hidden>
          <h5>Reportado</h5>
          <div style="display: flex;">
            @component('Components/inputFecha',[
              'attrs' => 'name="reportado[0]"',
              'attrs_dtp' => 'data-date-format="yyyy-mm-dd" data-start-view="year" data-min-view="month"',
              'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
              'placeholder' => 'DESDE',
              'readonly' => 1
            ])
            @endcomponent
            @component('Components/inputFecha',[
              'attrs' => 'name="reportado[1]"',
              'attrs_dtp' => 'data-date-format="yyyy-mm-dd" data-start-view="year" data-min-view="month"',
              'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
              'placeholder' => 'HASTA',
              'readonly' => 1
            ])
            @endcomponent
          </div>
        </div>
        <div class="col-md-3" hidden>
          <h5>Edad (a fecha de reporte)</h5>
          <div style="display: flex;">
            <div style="padding: 0 !important;flex: 1;">
              <input class="form-control" name="edad[0]" readonly>
            </div>
            <div style="padding: 0 !important;flex: 1;">
              <input class="form-control" name="edad[1]" readonly>
            </div>
          </div>
        </div>
        @endsection
        @yield('filtrosComunes')
      </div>
      @endslot
      
      @slot('cabecera')
      <tr>
        <th data-js-sortable="fecha_informado">F. INFORMADO</th>
        <th>ARCHIVO</th>
        <th>MD5</th>
        <th data-js-sortable="desde">F. REGISTROS (DESDE)</th>
        <th data-js-sortable="hasta">F. REGISTROS (HASTA)</th>
        <th data-js-sortable="cantidad_registros">CANT. REGISTROS</th>
        <th data-js-sortable="cantidad_menores">CANT. MENORES</th>
        <th>ACCION</th>
      </tr>
      @endslot
      
      @slot('molde')
      <tr>
        <td data-key="fecha_informado">F. INFORMADO</td>
        <td><small data-key="nombre_archivo">nombre_archivo.xls</small></td>
        <td><small data-key="md5">md5</small></td>
        <td data-key="desde">F. REGISTROS (DESDE)</td>
        <td data-key="hasta">F. REGISTROS (HASTA)</td>
        <td data-key="cantidad_registros">CANT. REGISTROS</td>
        <td data-key="cantidad_menores">CANT. MENORES</td>
        <td>
          <button class="btn" type="button" title="ver" data-js-click-asignar-md5="registros">
            <i class="fa fa-fw fa-search-plus"></i>
            <span hidden data-key="md5"></span>
          </button>
          <button class="btn" type="button" data-js-click-borrar="/registrosDNI/borrar" title="borrar"><i class="fa fa-fw fa-trash-alt"></i></button>
        </td>
      </tr>
      @endslot
    @endcomponent
  </div>
  <div data-visible="registros" class="col-md-12">
    @component('Components/FiltroTabla',['id' => $idFiltroTablaRegistros])
      @slot('titulo')
      Registros DNI
      <button class="btn" type="button" data-js-descargar="/registrosDNI/descargar">DESCARGAR</button>
      @endslot
      
      @slot('target_buscar')
      /registrosDNI/buscar/registros
      @endslot
      
      @slot('filtros')
      <div class="col-md-12">
        @yield('filtrosComunes')
        <div class="col-md-3">
          <h5>Importación</h5>
          <input class="form-control" name="md5" readonly>
        </div>
      </div>
      @endslot
      
      @slot('cabecera')
      <tr>
        <th data-js-sortable="timestamp">F. REPORTE</th>
        <th data-js-sortable="fecha_nacimiento">F. NAC</th>
        <th data-js-sortable="edad">EDAD</th>
        <th data-js-sortable="fecha_informado">F. INFORMADO</th>
      </tr>
      @endslot
      
      @slot('molde')
      <tr>
        <td data-key="timestamp">F. REPORTE</td>
        <td data-key="fecha_nacimiento">F. NAC</td>
        <td data-key="edad">EDAD</td>
        <td data-key="fecha_informado">F. INFORMADO</td>
      </tr>
      @endslot
    @endcomponent
  </div>
</div>



@component('Components/modal',[
  'id' => $idModalImportar,
  'clases_modal' => 'modalImportarRegistrosDNI',
  'attrs_modal' => 'data-js-modal-importar-registros-dni',
  'estilo_cabecera' => 'font-family: Roboto-Black; background-color: #6dc7be;'
])
  @slot('titulo')
    Importar Registros DNI
  @endslot
  @slot('cuerpo')
    <?php $formId = uniqid(); ?>
    <!-- Ajax params necesarios para enviar archivos -->
    <form id="{{$formId}}" action="/registrosDNI/importar" method="POST" class="row" data-ajax-params='{"processData": false,"contentType": false,"cache": false}'>
      <div class="row">
        <div class="col-md-6">
          <h5>Casino</h5>
          <select name="id_casino" class="form-control" readonly>
            <option value="">- SELECCIONAR -</option>
            @foreach(($casinos ?? []) as $c)
            <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <h5>Fecha Informado</h5>
          @component('Components/inputFecha',[
            'attrs' => 'name="fecha_informado"',
            'attrs_dtp' => 'data-date-format="yyyy-mm-dd" data-start-view="year" data-min-view="month"',
            'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
            'placeholder' => 'Fecha Informado'
          ])
          @endcomponent
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <h5>Archivo</h5>
          <input id="archivo" type="file" name="archivo" style="max-width:100%; hite-space: normal;word-wrap: break-word; ">
        </div>
      </div>
      @include('includes.md5hash')
    </form>
  @endslot
  @slot('pie')
  <button type="button" class="btn btn-successAceptar" data-js-click-submit-form="#{{$formId}}">IMPORTAR</button>
  @endslot
@endcomponent

@component('Components/modalEliminar')
@endcomponent



@component('Components/modal',[
  'clases_modal' => 'modalCargando',
  'attrs_modal' => 'data-js-modal-cargando',
  'estilo_cabecera' => 'display: none;',
  'estilo_pie' => 'display: none;'
])
  @slot('cuerpo')
  <div style="width: 100%;height: 100%;font-size: 2rem;text-align: center;">
    <p>CARGANDO</p>
    <p>
      <i class="fa fa-spinner fa-spin"></i>
    </p>
  </div>
  @endslot
@endcomponent

<!-- token -->
<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title2" style="color: #fff;">Registros DNI</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <p>Registros de DNIs solicitados por los casinos</p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript paginacion -->
<script src="js/paginacion.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->
<script src="/js/seccionRegistrosDNI.js?8" type="module" charset="utf-8"></script>
<script src="/js/lib/spark-md5.js" charset="utf-8"></script><!-- Dependencia de md5.js -->
<script src="/js/md5.js?3" charset="utf-8"></script>
<!-- Custom input Bootstrap -->
<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>
<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
@endsection
