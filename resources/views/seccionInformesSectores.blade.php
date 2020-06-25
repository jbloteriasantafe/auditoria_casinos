@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('estilos')
  <link rel="stylesheet" href="/css/lista-datos.css">
  <style>
  .borrada {
    text-decoration: line-through;
  }
  </style>
@endsection
<?php
    function get(&$var, $default="") {
        return isset($var) ? $var : $default;
    }
    $rojo = 'rgb(219,100,100)';
    $amarillo = 'rgb(244,160,0)';
    $colores = [3 => $rojo ,4 => $rojo,5 => $rojo,6=> $amarillo];
?>
@section('contenidoVista')
<datalist id="dataCasinos">
</datalist>
<datalist id="dataSectores">
</datalist>
<datalist id="dataIslas">
</datalist>
<datalist id="dataMaquinas">
</datalist>

<div class="row">
    <div class="col-lg-12">
        <div class="col-lg-3">
            <div class="row">
                <div class="panel panel-default">
                    <div class="panel-heading"><h4>Casinos</h4></div>
                    <div class="panel-body">
                        <!-- height 100% para forzar mostrar en resolucines bajas. -->
                        <select id="sel_casinos" class="form-control" list="dataCasinos" size=3 style="height: 100%">
                        </select>
                        <button type="button" class="btn btn-secondary" id="btn_refrescar" title="Refrescar MTMs">
                            <i class="fa fa-sync" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="row">
                <div class="panel panel-default">
                    <div class="panel-heading"><h4>Sectores</h4></div>
                    <div class="panel-body">
                        <select id="sel_sectores" class="form-control" size=10 style="height: 100%">
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="row">
                <div class="panel panel-default">
                    <div class="panel-heading"><h4>Islas</h4></div>
                    <div class="panel-body">
                        <select id="sel_islas" class="form-control" size=20 style="height: 100%">
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="row">
                <div class="panel panel-default">
                    <div class="panel-heading"><h4>Maquinas</h4></div>
                    <div class="panel-body">
                        <select id="sel_maquinas" class="form-control" size=20 style="height: 100%">
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($es_admin)
<div class="row panel panel-default">
    <div class="panel-heading"><h4>Transacciones</h4></div>
    <hr>
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-4" style="border-right: 1px solid gray;">
                <select id="sel_encoladas" class="form-control" size=20 style="height: 100%;width: 100%">
                </select>
                <button type="button" class="btn btn-secondary" id="btn_ordenar">Ordenar</button>
                <button type="button" class="btn btn-secondary" id="btn_limpiar">Limpiar</button>
            </div>
            <div class="col-lg-2">
                <h5>Cambiar a estado</h5>
                <select id="sel_estado" class="form-control" size=1>
                    @foreach($estados as $e)
                    <option value="{{$e->id_estado_maquina}}">{{$e->descripcion}}</option>
                    @endforeach
                </select>
                <br>
                <button type="button" class="btn btn-warning" id="btn_estado">CAMBIAR</button>
            </div>
        </div>
    </div>
</div>
@endif
<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| INFORMES SECTORES</h3>
@endsection
@section('contenidoAyuda')
    <div class="col-md-12">
        <p>
        Permite la busqueda de maquinas por casinos, sectores e islas
        </p>
    </div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionInformesSectores.js" charset="utf-8"></script>
@endsection
