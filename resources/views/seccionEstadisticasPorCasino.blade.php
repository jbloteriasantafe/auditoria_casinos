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

<!-- <div class="container-fluid">
  <div class="row">
    <div class="col-md-12 bannerEstadisticas">
      <h1><img width="80" src="/img/logos/tablero_general_blue.png" alt=""> ESTADÍSTICAS POR CASINO</h1>
    </div>
  </div>
</div> -->
    <!-- Tarjeta de FILTROS -->
    <div class="row">
      <div class="col-md-12">

        <div class="panel panel-default">
          <div class="panel-heading">
                <div class="row">
                    <div class="col-md-3">
                      <h4>Filtros de búsqueda</h4>
                    </div>
                  @foreach($casinos as $casino)
                    <div class="col-md-2 col-xs-4">
                      <input type="radio" name="opcion2" value="{{$casino->id_casino}}">
                      <span style="font-family: Roboto-Regular; font-size:16px;">{{$casino->nombre}}</span>
                    </div>
                  @endforeach
                </div><br>


                <div class="row">
                  <div class="col-md-3 col-md-offset-2">
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
                  <div class="col-md-2">
                      <h5 style="font-family:Roboto-Regular;font-size:14px;font-weight:700;color:#f5f5f5;">BÚSQUEDA</h4>
                      <button id="btn-generarGraficos" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> GENERAR GRÁFICOS</button>
                  </div>
                </div>

          </div> <!-- /.panel-heading -->
        </div> <!-- /.panel -->

      </div> <!-- /.col-md-12 -->
    </div> <!-- / Tarjeta FILTROS -->

    <!-- RECAUDACIÓN BRUTA TOTAL-->
    <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Recaudación Bruto Y Canon Total</h4>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-md-12">
                        <h5>MÁQUINAS TRAGAMONEDAS</h5>
                    </div>
                </div>

                <div hidden class="row informacionEstadistica">
                    <div class="col-xl-6">
                        <div id="donaMTM"></div>
                    </div>
                    <div class="col-xl-5">
                        <table class="table table-fixed" style="font-size: 14px">
                          <thead>
                              <tr>
                                  <th class="col-xs-4" style="font-weight: bold">MES</th>
                                  <th class="col-xs-4" style="background: #82B1FF; color: #FFF !important;">BRUTO</th>
                                  <th class="col-xs-4" style="background: #448AFF; color: #FFF !important;">CANON</th>
                              </tr>
                          </thead>
                          <tbody id="cuerpoTablaMTM" height="350px">

                          </tbody>
                          <tfoot id="pieTablaMTM">

                          </tfoot>
                        </table>
                    </div> <!-- /.col-md-6 -->
                </div> <!-- /.row -->

                <br><legend></legend>

                <div class="row">
                    <div class="col-md-12">
                        <h5>BINGO</h5>
                    </div>
                </div>
                <div hidden class="row informacionEstadistica">
                    <div class="col-xl-6">
                        <div id="donaBingo"></div>
                    </div>
                    <div class="col-xl-5">
                        <table id="" class="table table-fixed" style="font-size: 14px">
                              <thead>
                                  <tr>
                                      <th class="col-xs-4" style="font-weight: bold">MES</th>
                                      <th class="col-xs-4" style="background: #FFE57F; color: #FFF !important;">BRUTO</th>
                                      <th class="col-xs-4" style="background: #FFC400; color: #FFF !important";>CANON</th>
                                  </tr>
                              </thead>
                              <tbody id="cuerpoTablaBingo" height="350px">

                              </tbody>
                              <tfoot id="pieTablaBingo">

                              </tfoot>
                          </table>
                    </div> <!-- /.col-md-6 -->
                </div> <!-- /.row -->

                <br><legend></legend>

                <div class="row">
                    <div class="col-md-12">
                        <h5>MESAS</h5>
                    </div>
                </div>
                <div hidden class="row informacionEstadistica">
                    <div class="col-xl-6">
                        <div id="donaMesas"></div>
                    </div>
                    <div class="col-xl-5">
                        <table id="" class="table table-fixed" style="font-size: 14px">
                              <thead>
                                  <tr>
                                      <th class="col-xs-4" style="font-weight: bold">MES</th>
                                      <th class="col-xs-4" style="background: #B39DDB; color: #FFF !important;">BRUTO</th>
                                      <th class="col-xs-4" style="background: #9575CD; color: #FFF !important;">CANON</th>
                                  </tr>
                              </thead>
                              <tbody id="cuerpoTablaMesas" height="350px">

                              </tbody>
                              <tfoot id="pieTablaMesas">

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
                <h4>Seguimiento de Bruto Total Por Juego</h4>
            </div>
            <div class="panel-body">
              <br>
              <div class="row">
                  <div class="col-lg-12">
                      <div id="recBrutoJuego"></div>
                  </div>
              </div>
              <br>
                  <div class="row informacionEstadistica">
              <br>
                          <div class="col-lg-10 col-lg-offset-1">
                            <table id="" class="table table-fixed" style="font-size: 14px">
                                <thead>
                                    <tr>
                                        <th class="col-xs-3" style="font-weight: bold">MES</th>
                                        <th class="col-xs-2" style="background: #448AFF; color: #FFF !important;">MTM</th>
                                        <th class="col-xs-2" style="background: #9575CD; color: #FFF !important;">MESAS</th>
                                        <th class="col-xs-2" style="background: #FFC400; color: #FFF !important;">BINGO</th>
                                        <th class="col-xs-3" style="font-weight: bold">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoTablaTotalJuegos" height="350px">

                                </tbody>
                                <tfoot id="pieTablaTotalJuegos">

                                </tfoot>
                            </table>
                          </div>
                    </div>
                        <br>
              </div> <!-- /.panel-body -->
          </div> <!-- /.panel -->
        </div>
    </div>

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| ESTADÍSTICAS POR CASINO</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Estadísticas Por Casino</h5>
  <p>
    Asignado un casino en particular, se puede ver un informe detallado desde una fecha inicial/final la recaudación total de bruto
     y canon, como también un seguimiento en particular por juego.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/seccionEstadisticasPorCasino.js" charset="utf-8"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Highchart -->
<script src="js/highcharts.js"></script>
<script src="js/highcharts-3d.js"></script>
@endsection
