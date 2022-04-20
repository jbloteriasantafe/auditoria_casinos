@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoInformes">@svg('informes','iconoInformes')</span>
@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/js/jquery-ui-1.12.1.custom/jquery-ui.css">

@endsection
@section('contenidoVista')

<!-- FILTROS -->
<div class="row">
  <!-- FILTROS -->
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default" >
        <div class="panel-body">
          <div class="row">
            <div class="col-xs-3">
              <h5>Año</h5>
              <div class="form-group">
                <div class='input-group date' id='dtpFecha' data-link-field="fecha_filtro" data-date-format="yyyy" data-link-format="yyyy">
                  <input type='text' class="form-control" id="B_fecha_filtro" value=""/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <div class="col-xs-3">
              <h5>Casino</h5>
              <select class="form-control" name="" id="CasInformeA" >
                @foreach($casinos as $c)
                <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
                @endforeach
              </select>
            </div>
            <div class="col-xs-3">
              <h5>Moneda</h5>
              <select class="form-control" name="" id="MonInformeA" >
                <option value="1" selected>PESOS</option>
                <option value="2">DOLARES</option>
              </select>
            </div>
            <div class="col-xs-3" >
              <button id="buscar-informes-anuales" style="margin-top:30px" class="btn btn-infoBuscar" type="button" name="button">
                <i class="fa fa-fw fa-search"></i> BUSCAR
              </button>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-3 col-xs-offset-3">
              <h5>Casino</h5>
              <select class="form-control" name="" id="CasComparar" >
                <option value="" selected>---</option>
                @foreach($casinos as $c)
                <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
                @endforeach
              </select>
            </div>
            <div class="col-xs-3">
              <h5>Casino</h5>
              <select class="form-control" name="" id="MonComparar" >
                <option value="" selected>---</option>
                <option value="1">PESOS</option>
                <option value="2">DOLARES</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div id="mensajeErrorFiltros" hidden>
              <br>
              <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
              <br>
              <span id="mensajeF" style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">No se han encontrado datos para generar Gráfico</span>
            </div>
          </div>
        </div> <!-- panel-body -->
      </div> <!-- collapse -->
    </div>
  </div>
  <div class="panel-footer">
    <div class="row" id="speedChartDiv">
      <div class="col-xs-12">
        <div id="speedChart">
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">Mesas - Informes Anuales Contables</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <p>
  En esta seccion puede obtener un grafico totalizado al año de lo importado en "Mesas - Importaciones Diarias".
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript personalizado -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<script src="js/inputSpinner.js" type="text/javascript"></script>
<script src="/js/lista-datos.js" type="text/javascript"></script>

<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>
<script src="/js/jquery-ui.js" type="text/javascript"></script>

<!-- JS paginacion -->
<script src="/js/paginacion.js" charset="utf-8"></script>

<script src="/js/highcharts.js"  type="text/javascript"></script>
<script src="/js/highcharts-3d.js"  type="text/javascript"></script>
<script src = "/js/highcharts/code/modules/exporting.js"> </script>
<script src="/js/highcharts/code/highcharts-more.js"  type="text/javascript"></script>


<script type="text/javascript" src="js/Informes/seccionInformesAnuales.js?2"></script>

@endsection
