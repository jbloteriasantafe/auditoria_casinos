@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoTablero">@svg('tablero_control','iconoTableroControl')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
@endsection

    <!-- Tarjeta de FILTROS -->
    <div class="row">
      <div class="col-md-12">

        <div class="panel panel-default">
          <div class="panel-heading">
            <div class="row">
                <div class="col-md-2">
                    <h4>Filtros de búsqueda</h4>
                </div>
                <div class="col-md-3">
                    <h5 style="font-family:Roboto-Regular;font-size:14px;font-weight:700;color:#aaa;">FECHA DESDE</h4>
                    <div class="form-group">
                       <div class='input-group date' id='dtpFechaDesde' data-link-field="fecha_desde" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                           <input type='text' class="form-control" placeholder="Fecha Desde"/>
                           <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                           <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                       </div>
                       <input class="form-control" type="hidden" id="fecha_desde" value=""/>
                    </div>
                </div>
                <div class="col-md-3">
                    <h5 style="font-family:Roboto-Regular;font-size:14px;font-weight:700;color:#aaa;">FECHA HASTA</h4>
                    <div class="form-group">
                       <div class='input-group date' id='dtpFechaHasta' data-link-field="fecha_hasta" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                           <input type='text' class="form-control" placeholder="Fecha Hasta"/>
                           <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                           <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                       </div>
                       <input class="form-control" type="hidden" id="fecha_hasta" value=""/>
                    </div>
                </div>
                <div class="col-md-3">
                    <h5 style="font-family:Roboto-Regular;font-size:14px;font-weight:700;color:#f5f5f5;">BÚSQUEDA</h4>
                    <button id="btn-generarGraficos" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> GENERAR GRÁFICOS</button>
                </div>
            </div> <!-- /.row -->

          </div> <!-- /.panel-heading -->
        </div> <!-- /.panel -->

      </div> <!-- /.col-md-12 -->
    </div> <!-- / Tarjeta FILTROS -->

    <!-- RECAUDACIÓN BRUTA TOTAL-->
    <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Recaudación Bruta Total de Casinos</h4>
            </div>
            <div class="panel-body">

                <div class="row informacionEstadistica">
                    <div class="col-xl-6">
                        <div id="tortaBruto"></div>
                    </div>
                    <div class="col-xl-5">
                        <table id="tablaBruto" class="table table-fixed" style="font-size: 14px">
                          <thead>
                            <tr>
                              <th class="col-xs-3" style="font-weight: bold">MES</th>
                              <th class="col-xs-3" style="background: #ef3e42; color: #FFF !important;">CSF</th>
                              <th class="col-xs-3" style="background: #00b259; color: #FFF !important;">CME</th>
                              <th class="col-xs-3" style="background: #f58426; color: #FFF !important;">CRO</th>
                            </tr>
                          </thead>
                          <tbody id="cuerpoTablaBruto" height="350">

                          </tbody>
                          <tfoot id="pieTablaBruto">

                          </tfoot>
                        </table>
                    </div> <!-- /.col-md-6 -->
                </div> <!-- /.row -->
                <br>
            </div> <!-- /.panel-body -->
          </div> <!-- /.panel -->
        </div> <!-- /.col-md-12 -->
    </div> <!-- /.row -->

    <!-- RECAUDACIÓN CANON-->
    <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Recaudación neta (canon) de Casinos</h4>
            </div>
            <div class="panel-body">


              <div hidden class="row informacionEstadistica">
                  <div class="col-xl-6">
                      <div id="tortaCanon"></div>
                  </div>
                  <div class="col-xl-5">
                      <table id="tablaCanon" class="table table-fixed" style="font-size: 14px">
                        <thead>
                          <tr>
                            <th class="col-xs-3" style="font-weight: bold">MES</th>
                            <th class="col-xs-3" style="background: #ef3e42; color: #FFF !important;">CSF</th>
                            <th class="col-xs-3" style="background: #00b259; color: #FFF !important;">CME</th>
                            <th class="col-xs-3" style="background: #f58426; color: #FFF !important;">CRO</th>
                          </tr>
                        </thead>
                        <tbody id="cuerpoTablaCanon" height="350">

                        </tbody>
                        <tfoot id="pieTablaCanon">

                        </tfoot>
                      </table>
                  </div> <!-- /.col-md-6 -->
              </div> <!-- /.row -->

              <br>

            </div> <!-- /.panel-body -->
          </div> <!-- /.panel -->
        </div> <!-- /.col-md-12 -->
    </div> <!-- /.row -->


    <!-- SEGUIMIENTO BRUTO -->
    <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Seguimiento de Bruto Total de Casinos</h4>
            </div>
            <div class="panel-body">
              <br>
                  <div class="row">
                    <div class="col-xl-10 col-xl-offset-1">
                      <div id="seguimientoBruto"></div>
                    </div>
                  </div>
              <br>
            </div>
          </div>
        </div>


    </div>

    <!-- SEGUIMIENTO BARRAS -->
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
              <h4>Comparación Canon y Bruto de Casinos</h4>
          </div>
          <div class="panel-body">
            <br>
              <div class="row">
                  <div class="col-xl-10 col-xl-offset-1 informacionEstadistica">
                      <h5>SANTA FE</h5>
                      <div id="recaudacionBarraSF"></div>
                  </div>
              </div>

              <br><legend></legend><br>

              <div class="row">
                  <div class="col-xl-10 col-xl-offset-1 informacionEstadistica">
                      <h5>ROSARIO</h5>
                      <div id="recaudacionBarraR"></div>
                  </div>
              </div>

              <br><legend></legend><br>

              <div class="row">
                  <div class="col-xl-10 col-xl-offset-1 informacionEstadistica">
                      <h5>MELINCUÉ</h5>
                      <div id="recaudacionBarraM"></div>
                  </div>
              </div>
              <br>
          </div> <!-- /.panel-body -->
        </div> <!-- /.panel -->
      </div> <!-- /.col-md-12 -->
    </div> <!-- /.row -->



@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| ESTADÍSTICAS GENERALES</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Estadísticas Generales</h5>
  <p>
    Se muestran las estadísticas representativas generales de casinos en los que se compara su recaudación bruta, neta, canon y bruto.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/seccionEstadisticasGenerales.js" charset="utf-8"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Highchart -->
<script src="js/highcharts.js"></script>
<script src="js/highcharts-3d.js"></script>
@endsection
