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
    @foreach($casinos as $c)
    <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
    @endforeach
</datalist>
<datalist id="dataSectores">
    @foreach($sectores as $s)
    <option value="{{$s->id_sector}}" data-id-casino="{{$s->id_casino}}">{{$s->descripcion}}</option>
    @endforeach
</datalist>
<datalist id="dataIslas">
    @foreach($islas as $i)
    <option value="{{$i->id_isla}}" data-id-sector="{{$i->id_sector}}" data-id-casino="{{$i->id_casino}}">{{$i->nro_isla}}</option>
    @endforeach
</datalist>
<datalist id="dataMaquinas">
    @foreach($maquinas as $m)
    <option value="{{$m->id_maquina}}" 
    data-id-isla="{{$m->id_isla}}" 
    data-id-sector="{{$m->id_sector}}" 
    data-id-casino="{{$m->id_casino}}"
    data-id-estado-maquina="{{$m->id_estado_maquina}}"
    @if ($m->borrada == 1)
    class="borrada"
    @endif
    style='background: {{get($colores[$m->id_estado_maquina])}}'
    ><b>{{$m->nro_admin}}</b> <small>{{$m->estado_descripcion}}</small></option>
    @endforeach
</datalist>

<div class="row">
    <div class="col-lg-12">
        <div class="col-lg-3">
            <div class="row">
                <div class="panel panel-default">
                    <div class="panel-heading"><h4>Casinos</h4></div>
                    <div class="panel-body">
                    <!-- height 100% para forzar mostrar en resolucines bajas. -->
                        <select id="sel_casinos" class="form-control" list="dataCasinos" size={{count($casinos)}} style="height: 100%">
                        @foreach($casinos as $c)
                        <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
                        @endforeach
                        </select>
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
