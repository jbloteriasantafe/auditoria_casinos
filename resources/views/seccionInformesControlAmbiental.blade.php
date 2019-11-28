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

<div class="col-xl-9">
  <!-- FILTROS -->
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
          <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
        </div>

        <div id="collapseFiltros" class="panel-collapse collapse">
          <div class="panel-body">
            <div class="row">
              <div class="col-xs-3">
                <h5>Fecha</h5>
                <div class="form-group">
                  <div class='input-group date' id='dtpFechaInfD' data-link-field="fecha_filtro" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                    <input type='text' class="form-control" id="B_fecha_diario" value=""/>
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-xs-3">
                <h5>Casino</h5>
                <select class="form-control" name="" id="select_casino_diario" >
                  <option value="0" selected>- Todos los Casinos -</option>
                  @foreach($casinos as $c)
                  <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-xs-3" >
                <button id="buscar-info-diarios" style="margin-top:30px" class="btn btn-infoBuscar" type="button" name="button">
                    <i class="fa fa-fw fa-search"></i> BUSCAR
                </button>
              </div>
            </div>
          </div> <!-- panel-body -->
        </div> <!-- collapse -->
      </div> <!-- .panel-default -->
    </div> <!-- .col-md-12 -->
  </div> <!-- .row / FILTROS -->

  <!-- TABLA PRINCIPAL-->
  <div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>INFORMES DIARIOS</h4>
          </div>
          <div class="panel-body">
            <div class="table table-responsive"  >
              <table id="tablaInfoDiarios" class="table table-responsive tablesorter " width="100%">
                <thead>
                  <tr align="center" >
                    <th value="fecha" class="col-xs-4 activa" estado="desc" style="font-size:14px;text-align:center !important">FECHA DE PRODUCCIÓN<i class="fas fa-sort"></th>
                    <th value="nombre" class="col-xs-4" estado="" style="font-size:14px;text-align:center !important">CASINO<i class="fas fa-sort"></th>
                    <th class="col-xs-4"  style="font-size:14px;text-align:center !important;">ACCIÓN</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
          </div>
            <div class="table table-responsive" id="molde2" style="display:none" >

              <table class="table table-sorter">
                <tr id="moldeInfoDia" class="filaClone" style="display:none">
                  <td class="col-md-4 diario_fecha" style="text-aling:center !important"></td>
                  <td class="col-md-4 diario_casino" style="text-aling:center !important"></td>
                  <td class="col-md-4 diario_accion" style="text-align:center !important;">
                    <button type="button" class="btn btn-info imprimirInfoDiario" value="">
                      <i class="fa fa-fw fa-print"></i>
                    </button>
                  </td>
                </tr>
              </table>
              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
            </div>

          </div>

        </div>
      </div>
    </div> <!-- .row / TABLA -->

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
<script type="text/javascript" src="/js/seccionInformesControlAmbiental.js"></script>
@endsection
