@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="css/bootstrap-datetimepicker.min.css">
  <link rel="stylesheet" href="css/paginacion.css">
  <style>
  .no_tomado{
    background-color: rgb(238,238,238);
  }
  </style>
@endsection

@section('contenidoVista')

<div data-listas-maquinas data-listas-maquinas-sacar-id_casino="#id_casino" data-listas-maquinas-sacar-str="#nro_admin" data-listas-maquinas-setear-id_maquina="#id_maquina" hidden>
  <!-- Tiene TODAS las maquinas de todos los casino -->
  <datalist data-lista-maquina-todas>
    <?php $codigos_casinos = $casinos->keyBy('id_casino'); ?>
    @foreach($maquinas as $m)
    <option data-id_casino="{{$m->id_casino}}" data-codigo-casino="{{$codigos_casinos[$m->id_casino]->codigo}}" data-id_maquina="{{$m->id_maquina}}" data-nro_admin="{{$m->nro_admin}}"></option>
    @endforeach
  </datalist>
  <!-- Las maquinas del casino elegido -->
  <datalist data-lista-maquina-cas></datalist>
  <!-- Tiene las que va buscando dinamicamente -->
  <datalist data-lista-maquina-str id="listasMaquinasStr"></datalist>
</div>


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
    /estadisticas_relevamientos/obtenerUltimosRelevamientosPorMaquina
    @endslot
    
    @slot('filtros')
    <div class="row">
      <div class="col-lg-12">
        <h5>CASINO</h5>
        <select name="id_casino" class="form-control" id="id_casino">
          @if(count($casinos) != 1)
          <option value="">Todos los casinos</option>
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
        <input name="id_maquina" id="id_maquina" hidden>
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
    <tr data-js-buscar-relevamientos>
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

<div class="modal fade" id="modalPedido" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #FFB74D; color: white;">
       <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
       <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoPedido" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
       <h3 class="modal-title" id="myModalLabel">PEDIR MÁQUINA</h3>
      </div> <!-- modal-header -->
      <div id="colapsadoPedido" class="collapse in">
        <div class="modal-body" style="font-family: Roboto; color: #aaa;">
          <form id="frmPedido" name="frmPedido" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-md-6">
                <h5>NÚMERO ADMIN</h5>
                <input id="nro_admin_pedido" data-maquina="" class="form-control" type="text" name="" value="">
              </div>
              <div class="col-md-6">
                <h5>CASINO</h5>
                <input id="casino_pedido" data-casino="" class="form-control" type="text" name="" value="">
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <h5>Fecha de inicio</h5>
                <input type='text' class="form-control" placeholder="Fecha de inicio" id="B_fecha_inicio_m" autocomplete="off" readonly/>
              </div>
              <div class="col-md-6">
                <h5>Fecha de finalización</h5>
                <div class='input-group date' id='dtpFechaFin_m' data-date-format="yyyy/mm/dd" data-link-format="yyyy/mm/dd">
                  <input type='text' class="form-control" placeholder="Fecha de finalización" id="B_fecha_fin_m" autocomplete="off"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-12">
                <table id="fechasPedido" class="table">
                  <thead>
                    <tr>
                      <th>FECHAS A PEDIDO DE LA MÁQUINA</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
          </form>
        </div> <!-- modal-body -->
        <div class="modal-footer">
          <button id="btn-pedido" type="button" class="btn btn-warningModificar">PEDIR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          <input id="id_maquina" hidden type="text" name="" value="0">
        </div> <!-- modal-footer -->
      </div> <!-- modal colapsado -->
    </div> <!-- modal-content -->
  </div> <!-- modal-dialog -->
</div> <!-- modal -->

<div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:90%;">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #3D5AFE; color: white;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarDetalle" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoDetalle" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" id="myModalLabel">DETALLE DE MÁQUINA</h3>
      </div> <!-- modal-header -->
      <div id="colapsadoDetalle" class="collapse in">
        <div class="modal-body" style="font-family: Roboto; color: #aaa;">
          <form id="frmDetalle" name="frmDetalle" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-md-2 col-md-offset-2">
                <h5>NÚMERO DE ADMIN</h5>
                <input id="adminDetalle" type="text" class="form-control" value="" readonly>
              </div>
              <div class="col-md-2">
                <h5>CASINO</h5>
                <input id="casinoDetalle" type="text" class="form-control" value="" readonly>
              </div>
              <div class="col-md-2">
                <h5>SECTOR</h5>
                <input id="sectorDetalle" type="text" class="form-control" value="" readonly>
              </div>
              <div class="col-md-2">
                <h5>ISLA</h5>
                <input id="islaDetalle" type="text" class="form-control" value="" readonly>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-12">
                <table id="tablaRelevamientos" class="table">
                  <thead>
                    <tr>
                      <th>FECHA</th>
                      <th>CONT 1</th>
                      <th>CONT 2</th>
                      <th>CONT 3</th>
                      <th>CONT 4</th>
                      <th>CONT 5</th>
                      <th>CONT 6</th>
                      <th>CONT 7</th>
                      <th>CONT 8</th>
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
          </form>
        </div> <!-- modal-body -->
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          <input id="id_maquina" hidden type="text" name="" value="0">
        </div> <!-- modal-footer -->
      </div> <!-- modal colapsado -->
    </div> <!-- modal-content -->
  </div> <!-- modal-dialog -->
</div> <!-- modal -->

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
<script src="js/paginacion.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->
<script src="js/seccionEstadisticasRelevamientos.js?3" charset="utf-8" type="module"></script>
<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
@endsection
