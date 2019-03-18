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

<div class="col-lg-12 tab_content" id="gestionInfoMes" hidden="true">

  <div class="col-xl-12">
    <div class="row">
      <div class="col-xl-12">

        <div class="panel-body">
          <div class="row">
            <div class="col-xs-4">
              <h5 style="font-family:Roboto-Regular !important;font-size:12px;font-weight:bold;color:#aaa">FECHA</h5>
              <div class="form-group">
                <div class='input-group date' id='dtpFecha' data-link-field="fecha_filtro" data-date-format="yyyy-MM" data-link-format="yyyy-MM">
                  <input type='text' class="form-control" id="fechaInformeMensual" value=""/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <div class="col-xs-4">
              <h5 style="font-family:Roboto-Regular !important;font-size:12px;font-weight:bold;color:#aaa">CASINO</h5>
              <select class="form-control" name="" id="CasInforme" >
                <option value="" selected>- Todos los Casinos -</option>
                @foreach($casinos as $c)
                <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
                @endforeach
              </select>
            </div>

            <div class="col-xs-4" >
              <button id="buscar-informes-mensuales" style="margin-top:30px" class="btn btn-infoBuscar" type="button" name="button">
                <i class="fa fa-fw fa-search"></i> BUSCAR
              </button>
            </div>
          </div>

        </div> <!-- panel-body -->
      </div> <!-- collapse -->
    </div>
  <br>
    <div class="row">
      <div class="panel panel-default" >

        <div class="panel-body">
          <div class="table table-responsive">

          <table id="tablaInformesMensuales" class="table table-responsive tablesorter">
            <thead>
              <tr align="center" >
                <th class="col-xs-4" value="fecha" estado="desc" style="font-size:14px;text-align:center !important">MES<i class="fas fa-sort"></i></th>
                <th class="col-xs-4" value="nombre" estado="" style="font-size:14px;text-align:center !important">CASINO<i class="fas fa-sort"></i></th>
                <th class="col-xs-4"  style="font-size:14px;text-align:center !important;text-align:center !important">ACCIÓN</th>
              </tr>
            </thead>
            <tbody>

            </tbody>
          </table>
          <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
          </div>

        </div>

      </div>
    </div>
  </div>

</div>

<!-- sector de gráficos -->
<div class="col-lg-12 tab_content" id="graficosMes" hidden="true">

  <div class="row col-xl-12">
    <div class="panel-footer" style="height:550px !important">
      <div class="row">
        <div class="col-xs-4">
          <h6 style="font-family:Roboto-Regular !important;font-size:12px; font-weight:bold;color:#212121; margin-left:10px">AÑO Y MES</h6>
          <div class="form-group">
            <div class='input-group date' id='dtpFechaMyA' data-link-field="fecha_filtro_mes" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
              <input type='text' class="form-control" id="B_MyA_filtro" value=""/>
              <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
              <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
        </div>
        <div class="col-xs-4">
          <h6 style="font-family:Roboto-Regular !important;font-size:12px; font-weight:bold;color:#212121; margin-left:10px">CASINO</h6>
          <select class="form-control" name="" id="casinoFMes" >
            @foreach($casinos as $c)
              <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-xs-4">
          <button type="button" class="btn btn-infoBuscar" id="generarGraficos" style="margin-top:30px" name="button">GENERAR GRÁFICO/S</button>
        </div>
    </div>
    <br>
    <div class="row">
      <div class="col-xs-6" style="width:50% ; height: 10%" display="inline">
        <!-- grafico de linea -->
        <div id="graficoPesos" style="width: 550px; height: 400px; margin: 0px; margin-left:10px; float:left !important; display:inline-block" ></div>
        <h6 class="mensajeErrorGrafico" style="font-family:Roboto-Regular;font-size:15px;text-align:left !important" hidden="true"></h6>

      </div>
      <div class="col-xs-6" style="width:50% ; height: 10%; float:right; display:inline">
        <div id="graficoDolares" style="width: 500px; height: 400px; margin: 0px; margin-right:0px !important; margin-left:10px; float:center !important"></div>
      </div>

    </div>
  </div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<script src="js/inputSpinner.js" type="text/javascript"></script>
<script src="/js/lista-datos.js" type="text/javascript"></script>
<script src="/js/paginacion.js" charset="utf-8"></script>

<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<script src="/js/highcharts.js"  type="text/javascript"></script>
<script src="/js/highcharts-3d.js"  type="text/javascript"></script>
<script src = "/js/highcharts/code/modules/exporting.js"> </script>
<script src="/js/highcharts/code/highcharts-more.js"  type="text/javascript"></script>

<script type="text/javascript" src="js/Informes/seccionInformesMensuales.js"></script>


@endsection
