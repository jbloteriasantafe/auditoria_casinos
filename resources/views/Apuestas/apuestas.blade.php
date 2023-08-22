<?php
use Illuminate\Http\Request;
$puede_validar_eliminar = $usuario->tienePermiso('m_validar_eliminar_relevamientos_apuestas');
$puede_modificar_minimo = $usuario->tienePermiso('m_abm_apuesta_minima');
?>

@extends('includes.dashboard')
@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<link href="css/bootstrap.min.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/paginacion.css">

@endsection

@section('contenidoVista')

<div class="row">
  <div class="col-md-3">
    <div class="row">
      <div class="col-md-12">
        <a href="" id="btn-generar" dusk="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
              <div class="backgroundNuevo"></div>
                <div class="row">
                  <div class="col-xs-12">
                    <center>
                        <h5 class="txtLogo">+</h5>
                        <h4 class="txtNuevo">IMPRIMIR PLANILLAS DE RELEVAMIENTO </h4>
                    </center>
                  </div>
                </div>
            </div>
          </a>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <a href="" id="btn-backUp" dusk="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
              <div class="backgroundNuevo"></div>
                <div class="row">
                  <div class="col-xs-12">
                    <center>
                        <h5 class="txtLogo">+</h5>
                        <h4 class="txtNuevo">CARGAR RELEVAMIENTO SIN SISTEMA</h4>
                    </center>
                  </div>
                </div>
            </div>
          </a>
      </div>
    </div>
    @if($puede_modificar_minimo)
    <div class="row">
      <div class="col-md-12">
        <a href="" id="btn-minimo" dusk="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/procedimientos.png"><center>
              <div class="backgroundNuevo"></div>
                <div class="row">
                  <div class="col-xs-12">
                    <center>
                        <h5 class="txtLogo" style="font-size:80px !important;margin-top:60px !important"> <i class="fas fa-fw fa-pencil-alt"></i> </h5>
                        <br>
                        <br>
                        <h4 class="txtNuevo">MODIFICAR MÍNIMO DE APUESTAS REQUERIDO</h4>
                    </center>
                  </div>
                </div>
            </div>
          </a>
      </div>
    </div>
    @endif
  </div>

  <div class="col-md-9">
    @component('CierresAperturas/FiltroTabla')
      @slot('titulo')
      RELEVAMIENTOS GENERADOS
      @endslot
      
      @slot('target_buscar')
      /apuestas/buscarRelevamientosApuestas
      @endslot
      
      @slot('filtros')
      <div class="col-md-2">
        <h5>Fecha</h5>
        @component('CierresAperturas/inputFecha',['attrs' => 'name="fecha"'])
        @endcomponent
      </div>
      <div class="col-md-3">
        <h5>Casino</h5>
        <select name="id_casino" class="form-control">
          <option value="" selected>- Seleccione un Casino -</option>
          @foreach ($casinos as $cas)
          <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <h5>TURNO</h5>
        <select class="form-control" name="id_turno">
          <option value="0" selected>- Todos los Turnos -</option>
          @foreach ($turnos as $t)
          <option value="{{$t->id_turno}}">#{{$t->nro_turno}} -{{$t->nombre_dia_desde}} a {{$t->nombre_dia_hasta}}- {{$t->casino->codigo}}</option>
          @endforeach
        </select>
      </div>
      @endslot
      
      @slot('cabecera')
      <tr>
        <th data-js-sortable="fecha" style="text-align: center;">FECHA PRODUCCIÓN</th>
        <th data-js-sortable="nro_turno" style="text-align: center;">TURNO</th>
        <th data-js-sortable="nombre" style="text-align: center;">CASINO</th>
        <th data-js-sortable="id_estado_relevamiento" style="text-align: center;">ESTADO</th>
        <th style="text-align: center;">ACCION</th>
      </tr>
      @endslot
      
      @slot('molde')
      <tr>
        <td class="fecha" style="text-align: center;">FECHA PRODUCCIÓN</td>
        <td class="nro_turno" style="text-align: center;">TURNO</td>
        <td class="casino" style="text-align: center;">CASINO</td>
        <td class="estado" style="text-align: center;">
          <i class="fas fa-fw fa-times" style="color: rgb(211, 47, 47);" data-estados="1,2,3,5,6,7"></i>
          <i class="fas fa-fw fa-check-circle" style="color: rgb(76, 175, 80);" data-estados="4"></i>
        </td>
        <td style="text-align:center;">
          @if($puede_validar_eliminar)
          <button type="button" class="btn" data-js-ver-apuesta data-estados="1,2,3,4,5,6,7">
            <i class="fa fa-fw fa-search-plus"></i>
          </button>
          @endif
          <button type="button" class="btn" data-js-cargar-apuesta data-estados="1">
            <i class="fas fa-fw fa-upload"></i>
          </button>
          <button type="button" class="btn" data-js-modificar-apuesta data-estados="3">
            <i class="fas fa-fw fa-pencil-alt"></i>
          </button>
          <button type="button" class="btn" data-estados="1,2,3,4,5,6,7" data-js-nueva-pestaña="/apuestas/imprimirPlanilla">
            <i class="far fa-fw fa-file-alt"></i>
          </button>
          @if($puede_validar_eliminar)
          <button type="button" class="btn" data-estados="1,2,3,4,5,6,7" data-js-nueva-pestaña="/apuestas/imprimir" >
            <i class="fa fa-fw fa-print"></i>
          </button>
          <button type="button" class="btn" data-js-validar-apuesta data-estados="3">
            <i class="fa fa-fw fa-check"></i>
          </button>
          <button type="button" class="btn" data-js-eliminar-apuesta data-estados="1,3">
            <i class="fa fa-fw fa-trash"></i>
          </button>
          @endif
        </td>
      </tr>
      @endslot
    @endcomponent
  </div>
</div>

@component('Apuestas/cargarModificarApuestas',[
  'monedas' => $monedas,'estados_mesa' => $estados_mesa,
  'puede_validar' => $puede_validar_eliminar,
])
@endcomponent

@component('Apuestas/eliminar')
@endcomponent

@component('Apuestas/modificarMinimo',compact('casinos','monedas'))
@endcomponent

@component('Apuestas/regenerarBackup',compact('casinos'))
@endcomponent

@component('Apuestas/generar')
@endcomponent

@endsection

@section('scripts')
    <script src="js/inputSpinner.js" type="text/javascript"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>
    <script src="js/Apuestas/apuestas.js?5" type="module" charset="utf-8"></script>
@endsection
