<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
$id_usuario = $usuario['usuario']->id_usuario;
$cas = $usuario['usuario']->casinos;
?>

@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<!-- <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/> -->
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.css">
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/zona-file-large.css">
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/paginacion.css">
@endsection

<!-- FILTROS -->
<div class="row">
  <div class="col-md-10">
    <div class="panel panel-default">

      <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
        <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
      </div>

      <div id="collapseFiltros" class="panel-collapse collapse">

        <div class="panel-body">

          <div class="row">
            <div class="col-lg-4">
              <h5>Casino</h5>
              <select class="form-control" id="B_Casino">
                <option value="" selected>Todos</option>
                @foreach(UsuarioController::getInstancia()->quienSoy()['usuario']->casinos as $c)
                <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
                @endforeach
              </select>
            </div>
            <div class="col-lg-4">
              <h5>Tipo Movimiento</h5>
              <select class="form-control" id="B_TipoMovimientoRel">
                <option value="" selected>Todos</option>
                @foreach ($tipos_movimientos as $t_mov)
                @if(!$t_mov->deprecado && !$t_mov->es_intervencion_mtm)
                <option value="{{$t_mov->id_tipo_movimiento}}">{{$t_mov->descripcion}}</option>
                @endif
                @endforeach
                <optgroup style="color:red;" label="Fuera de uso">
                @foreach ($tipos_movimientos as $t_mov)
                @if($t_mov->deprecado || $t_mov->es_intervencion_mtm)
                <option value="{{$t_mov->id_tipo_movimiento}}">{{$t_mov->descripcion}}</option>
                @endif
                @endforeach
                </optgroup>
              </select>
            </div>
            <div class="col-lg-4">
              <h5>Fecha</h5>
              <div class="form-group">
                <div class='input-group date' id='dtpFechaRM' data-link-field="fechaRelMov" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" id="B_fecha_rel"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
                <input class="form-control" type="hidden" id="fechaRelMov" value=""/>
              </div>
            </div>
            <div class="col-lg-4">
              <h5>Nro. de Movimiento</h5>
              <input id="busqueda_numero_movimiento" type="text" class="form-control" placeholder="Nro. de movimiento">
            </div>
            <div class="col-lg-4">
              <h5>Nro. de Máquina</h5>
              <input id="busqueda_maquina" type="text" class="form-control" placeholder="Nro. de máquina">
            </div>
          </div> <!-- row / formulario -->

          <br>

          <div class="row">
            <div class="col-md-12" style="text-align: center">
                <button id="btn-buscarRelMov" class="btn btn-infoBuscar" type="button" name="button">
                  <i class="fa fa-fw fa-search"></i> BUSCAR
                </button>
            </div>
          </div> <!-- row / botón buscar -->

        </div> <!-- panel-body -->
      </div> <!-- collapse -->

    </div> <!-- .panel-default -->
  </div> <!-- .col-md-12 -->
</div> <!-- .row / FILTROS -->
<div class="row">
  <div class="col-md-10">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4>ÚLTIMOS RELEVAMIENTOS DE MOVIMIENTOS</h4>
      </div>
      <div class="panel-body">
        <table id="tablaRelevamientosMovimientos" class="table table-fixed tablesorter">
          <thead>
            <th class="col-xs-1" value="casino.nombre">CASINO<i class="fa fa-sort"></i></th>
            <th class="col-xs-1" value="log_movimiento.id_log_movimiento">MOV<i class="fa fa-sort"></i></th>
            <th class="col-xs-2" value="fiscalizacion_movimiento.fecha_envio_fiscalizar">FECHA<i class="fa fa-sort"></i></th>
            <th class="col-xs-1" value="fiscalizacion_movimiento.identificacion_nota">NOTA<i class="fa fa-sort"></i></th> 
            <th class="col-xs-2" value="maquinas">MAQUINAS<i class="fa fa-sort"></i></th>
            <th class="col-xs-2" value="tipo_movimiento.descripcion">TIPO DE MOVIMIENTO<i class="fa fa-sort"></i></th>
            <th class="col-xs-1" value="estado_relevamiento.descripcion">ESTADO<i class="fa fa-sort"></i></th>
            <th class="col-xs-2">ACCIÓN</th>
          </thead>
          <tbody id="cuerpoTablaRel" style="height: 380px;">
          </tbody>
        </table>
        <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
      </div>
    </div>
  </div>
</div>

<table hidden>
  <tr id="filaEjemploRelevamiento">
    <td class="col-xs-1 casino">CASINO</td>
    <td class="col-xs-1 movimiento">9999999</td>
    <td class="col-xs-2 fecha">99-99-999</td>
    <td class="col-xs-1 nota">99999</td>
    <td class="col-xs-2 maquinas">9999,999,9,9,99,,99</td>
    <td class="col-xs-2 tipo">TIPO</td>
    <td class="col-xs-1 estado">
        <i class="fas fa-fw fa-exclamation" style="color: rgb(255,255,0);align: center;"></i>
    </td>
    <td class="col-xs-2 accion">
      <button class="btn btn-success btn-verRelMov" title="VER">
        <i class="fa fa-fw fa-search"></i>
      </button>
      <button class="btn btn-success btn-cargarRelMov" title="CARGAR">
        <i class="fa fa-fw fa-upload"></i>
      </button>
      <button class="btn btn-success btn-imprimirRelMov" title="IMPRIMIR">
        <i class="fas fa-fw fa-print"></i>
      </button>
      <button class="btn btn-default btn-eliminarFiscal" title="ELIMINAR">
        <i class="fas fa-fw fa-trash"></i>
      </button>
    </td>
  </tr>
</table>


<!--********************* Modal para CARGAR RELEVAMIENTO*****************************-->

<div class="modal fade" id="modalCargarRelMov" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 90%">
    <div class="modal-content">
      <div class="modal-header" style="background: #4FC3F7">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">CARGAR RELEVAMIENTOS</h3>
      </div> <!-- modal header -->

      <div  id="colapsado" class="collapse in">
        <div class="modal-body">
          @include('divRelevamientoMovimiento')
        </div>  <!-- modal body -->

        <div class="modal-footer">
          <!-- INPUTS QUE ME SIRVEN PARA ENVIAR JSON EN EL POST DE VALIDAR -->
          <button id="datosUltimoEgresoTemporal" type="button" class="btn btn-informacion" value="" title="CARGAR DATOS ULTIMO EGRESO TEMPORAL">
            <i class="fas fa-file-alt"></i>
            <i class="fas fa-undo-alt"></i>
          </button>
          <button id="guardarRel" type="button" class="btn btn-success guardarRelMov" value="" >GUARDAR</button>
          <button type="button" class="btn btn-default cancelar" data-dismiss="modal" aria-label="Close">SALIR</button>
        </div> <!-- modal footer -->
      </div> <!-- modal colapsado -->
    </div> <!-- modal content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->

@endsection
@section('scripts')

<script src="/js/paginacion.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->
<script src="/js/divRelevamientoMovimiento.js" charset="utf-8"></script>
<script src="/js/seccionRelevamientosMovimientos.js" charset="utf-8"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Custom input Bootstrap -->
<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<script src="/js/inputSpinner.js" type="text/javascript"></script>
<script src="/js/lista-datos.js" type="text/javascript"></script>
<script src="/js/utils.js" type="text/javascript"></script>
@endsection
