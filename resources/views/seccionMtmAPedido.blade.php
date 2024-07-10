@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link rel="stylesheet" href="css/paginacion.css">
@endsection

@section('contenidoVista')

<div class="row">
  <div class="col-xl-9"> <!-- columna TABLA CASINOS -->
    @component('Components/FiltroTabla')
    
    @slot('titulo')
    MTMs a pedido
    @endslot
    
    @slot('target_buscar')
    /mtm_a_pedido/buscarMTMaPedido
    @endslot
    
    @slot('filtros')
    <div class="col-md-3">
      <h5>CASINO</h5>
      <select name="id_casino" class="form-control" data-js-cambio-casino-select-sectores="#destinoSectores">
        @if(count($casinos) == 0 || count($casinos) > 1)
        <option value="">- Seleccione un casino -</option>
        @endif
        @foreach($casinos as $c)
        <option value="{{$c->id_casino}}" {{count($casinos) == 1? 'selected' : ''}}>{{$c->nombre}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <h5>Sector</h5>
      <select name="id_sector"  class="form-control" id="destinoSectores">
        <option value="" data-js-cambio-casino-mantener>- Seleccione -</option>
      </select>
    </div>
    <div class="col-md-3">
      <h5>ISLA</h5>
      <input name="nro_isla" class="form-control">
    </div>
    <div class="col-md-3">
      <h5>NRO ADMIN</h5>
      <input name="nro_admin" class="form-control">
    </div>
    <div class="col-md-3">
      <h5>Fecha de inicio</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_inicio"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha de finalización</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_fin"'])
      @endcomponent
    </div>
    @endslot
    
    @slot('cabecera')
    <tr>
      <th data-js-sortable="maquina.nro_admin"  style="text-align: center;">NRO ADMIN</th>
      <th data-js-sortable="maquina_a_pedido.fecha" data-js-state="desc" style="text-align: center;">FECHA</th>
      <th data-js-sortable="casino.nombre" style="text-align: center;">CASINO</th>
      <th data-js-sortable="sector.descripcion" style="text-align: center;">SECTOR</th>
      <th data-js-sortable="isla.nro_isla" style="text-align: center;">ISLA</th>
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
      <td class="nro_admin">NRO ADMIN</td>
      <td class="fecha">FECHA</td>
      <td class="casino">CASINO</td>
      <td class="sector">SECTOR</td>
      <td class="nro_isla">ISLA</td>
      <td>
        <button data-js-eliminar-mtm-a-p class="btn btn-danger" title="ELIMINAR"><i class="fa fa-fw fa-trash-alt"></i></button>
      </td>
    </tr>
    @endslot
    
    @endcomponent
  </div>
  <div class="col-xl-3">
    <div class="row">
      <div class="col-lg-12">
        <a href="" id="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/maquinas_a_pedido_white.png"><center>
            <div class="backgroundNuevo"></div>
            <div class="row">
              <div class="col-xs-12">
                <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">NUEVO REGISTRO DE MTM A PEDIR</h4>
                </center>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</div>

@component('Components/modal',[
  'clases_modal' => 'modalMTMaP',
  'attrs_modal' => 'data-js-modal-mtm-a-p',
  'estilo_cabecera' => 'background-color: #6dc7be;',
])

@slot('titulo')
| NUEVO PEDIDO A MTM
@endslot

@slot('cuerpo')
<form class="row" novalidate="">
  <div class="col-md-6">
    <h5>CASINO</h5>
    <select name="id_casino" class="form-control">
      @if(count($casinos) == 0 || count($casinos) > 1)
      <option value="">- Seleccione un casino -</option>
      @endif
      @foreach($casinos as $c)
      <option value="{{$c->id_casino}}" {{count($casinos) == 1? 'selected' : ''}}>{{$c->nombre}}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-6">
    <h5>NRO ADMIN</h5>
    <input name="nro_admin" class="form-control">
  </div>
  <div class="col-md-6">
    <h5>FECHA DE INICIO</h5>
    @component('Components/inputFecha',['attrs' => 'name="fecha_inicio"'])
    @endcomponent
  </div>
  <div class="col-md-6">
    <h5>FECHA DE FIN</h5>
    @component('Components/inputFecha',['attrs' => 'name="fecha_fin"'])
    @endcomponent
  </div>
</form>
@endslot

@slot('pie')
<button type="button" class="btn btn-successAceptar" data-js-aceptar>ACEPTAR</button>
@endslot

@endcomponent



@component('Components/modalEliminar')
@endcomponent

<meta name="_token" content="{!! csrf_token() !!}" />

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| MTM A PEDIDO</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Mtm a pedido</h5>
  <p>
    Sección que propone máquinas a relevar, aquellas que no fueron asignadas aleatoriamente dentro del mes
    y fueron identificadas para su obtención de datos.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript paginacion -->
<script src="js/paginacion.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->
<script src="/js/seccionMtmAPedido.js?2" type="module" charset="utf-8"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

@endsection
