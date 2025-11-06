@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
  <link rel="stylesheet" href="/css/paginacion.css">
  <style>
  .no_tomado{
    background-color: rgb(238,238,238);
  }
  </style>
@endsection

@section('contenidoVista')

@component('Components/listasAutocompletar',[
  'data' => $maquinas,
  'selector_id_casino' => "#id_casino",
  'selector_str'       => "#nro_admin",
  'get_id_casino'    => function($m){return $m->id_casino;},
  'get_id'           => function($m){return $m->id_maquina;},
  'get_str'          => function($m){return $m->nro_admin;},
  'outputStrListId'  => 'listasMaquinasStr',
])
@endcomponent

<div class="row"> <!-- row principal -->
  <div class="col-lg-3">
    <style>
      #filtrosRelevamientos [data-js-filtro-tabla-tabla] {
        display: none;
      }
    </style>
    @component('Components/FiltroTabla',['id' => 'filtrosRelevamientos', 'titulo_filtro' =>  'Buscar Relevamientos'])
    
    @slot('titulo')
    BUSCAR RELEVAMIENTOS DE MÁQUINA
    @endslot
    
    @slot('target_buscar')
    /estadisticas_relevamientos/obtenerUltimosRelevamientosPorMaquinaNroAdmin
    @endslot
    
    @slot('filtros')
    <div class="row">
      <div class="col-lg-12">
        <h5>CASINO</h5>
        <select name="id_casino" class="form-control" id="id_casino">
          @if(count($casinos) != 1)
          <option value="">- Seleccione un casino -</option>
          @endif
          @foreach($casinos as $c)
          <option value="{{$c->id_casino}}" {{count($casinos) == 1? 'selected' : ''}}>{{$c->nombre}}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <h5>NÚMERO ADMIN</h5>
        <input name="nro_admin" class="form-control" id="nro_admin">
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <h5>CANTIDAD DE RELEVAMIENTOS</h5>
        <input name="cantidad_relevamientos" class="form-control">
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <h5>TOMADO</h5>
        <select name="tomado" class="form-control">
          <option value="">TODOS</option>
          <option value="SI">SI</option>
          <option value="NO">NO</option>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <h5>TOMADO</h5>
        <select name="diferencia" class="form-control">
          <option value="">TODOS</option>
          <option value="SI">SI</option>
          <option value="NO">NO</option>
        </select>
      </div>
    </div>
    @endslot
    
    @slot('cabecera')
    @endslot
    
    @slot('molde')
    <tr>
      <td class="fecha">-</td>
      @for($c=1;$c<=$contadores;$c++)
      <td class="cont{{$c}}">-</td>
      @endfor
      <td class="coinin">-</td>
      <td class="coinout">-</td>
      <td class="jackpot">-</td>
      <td class="progresivo">-</td>
      <td class="producido_calculado_relevado">.</td>
      <td class="producido_importado">-</td>
      <td class="diferencia">-</td>
    </tr>
    @endslot
    
    @endcomponent
  </div>
  <div class="col-xl-9"> <!-- columna TABLA CASINOS -->
    @component('Components/FiltroTabla')
    
    @slot('titulo')
    BÚSQUEDA DE MÁQUINAS SIN RELEVAMIENTO 
    @endslot
    
    @slot('target_buscar')
    /estadisticas_relevamientos/buscarMaquinasSinRelevamientos
    @endslot
    
    @slot('filtros')
    <div class="row">
      <div class="col-md-4">
        <h5>CASINO</h5>
        <select name="id_casino" class="form-control" data-js-cambio-casino-select-sectores="#destinoSectores">
          @if(count($casinos) != 1)
          <option value="">- Seleccione un casino -</option>
          @endif
          @foreach($casinos as $c)
          <option value="{{$c->id_casino}}" {{count($casinos) == 1? 'selected' : ''}}>{{$c->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Sector</h5>
        <select name="id_sector"  class="form-control" id="destinoSectores">
          <option value="" data-js-cambio-casino-mantener>- Seleccione -</option>
        </select>
      </div>
      <div class="col-md-4">
        <h5>Nro isla</h5>
        <input name="nro_isla" class="form-control">
      </div>
    </div>
    <div class="col-md-4">
      <h5>Fecha desde</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_desde"'])
      @endcomponent
    </div>
    <div class="col-md-4">
      <h5>Fecha hasta</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_hasta"'])
      @endcomponent
    </div>
    @endslot
    
    @slot('cabecera')
    <tr>
      <th data-js-sortable="casino" style="text-align: center;">CASINO</th>
      <th data-js-sortable="sector" style="text-align: center;">SECTOR</th>
      <th data-js-sortable="isla" style="text-align: center;">ISLA</th>
      <th data-js-sortable="nro_admin"  style="text-align: center;">NRO ADMIN</th>
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
      <td class="casino">CASINO</td>
      <td class="sector">SECTOR</td>
      <td class="nro_isla">ISLA</td>
      <td class="nro_admin">NRO ADMIN</td>
      <td>
        <button data-js-pedir class="btn btn-danger" title="PEDIR"><i class="fa fa-tag"></i></button>
      </td>
    </tr>
    @endslot
    
    @endcomponent
  </div>
</div><!-- /.row principal -->

@component('Relevamientos/modalMtmAPedido',['casinos' => $casinos])
  @slot('append_body')
  <table data-js-tabla-fechas-pedidas>
    <thead>
      <tr><td>FECHAS PEDIDAS</td></tr>
    </thead>
    <tbody>
    </tbody>
  </table>
  @endslot
@endcomponent

@component('Components/modal',[
  'attrs_modal' => 'data-js-modal-detalle-maquina',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 90,
])
  @slot('titulo')
  DETALLE DE MÁQUINA
  @endslot
  @slot('cuerpo')
  <div class="row">
    <div class="col-md-2 col-md-offset-2">
      <h5>NÚMERO DE ADMIN</h5>
      <input name="nro_admin" type="text" class="form-control" value="" readonly>
    </div>
    <div class="col-md-2">
      <h5>CASINO</h5>
      <input name="casino" type="text" class="form-control" value="" readonly>
    </div>
    <div class="col-md-2">
      <h5>SECTOR</h5>
      <input name="sector" type="text" class="form-control" value="" readonly>
    </div>
    <div class="col-md-2">
      <h5>ISLA</h5>
      <input name="nro_isla" type="text" class="form-control" value="" readonly>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-md-12">
      <table data-js-modal-detalle-maquina-tabla-relevamientos class="table">
        <thead>
          <tr>
            <th>FECHA</th>
            @for($c=1;$c<=$contadores;$c++)
            <th>CONT {{$c}}</th>
            @endfor
            <th>COIN IN</th>
            <th>COIN OUT</th>
            <th>JACKPOT</th>
            <th>PROGRESIVO</th>
            <th>PROD CALCULADO</th>
            <th>PROD IMPORTADO</th>
            <th>DIFERENCIA</th> <!-- 15 columnas -->
          </tr>
        </thead>
        <tbody style="color:black;">
        </tbody>
      </table>
    </div>
  </div>
  @endslot
@endcomponent

<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| ESTADÍSTICAS DE RELEVAMIENTOS</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <p>
    Sección donde permite encontrar estadísticas de máquinas tragamonedas, con respecto a las últimas
    evaluaciones de sus relevamientos diarios. Aquellas donde no se encuentren toma de datos,
    tienen la posibilidad de pedir manualmente para el próximo relevamiento.
  </p>
</div>
@endsection

@section('scripts')
<!-- JavaScript paginacion -->
<script src="/js/paginacion.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->
<script>
  const CONTADORES = {{$contadores}};
</script>
<script src="/js/seccionEstadisticasRelevamientos.js?4" charset="utf-8" type="module"></script>
<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
@endsection
