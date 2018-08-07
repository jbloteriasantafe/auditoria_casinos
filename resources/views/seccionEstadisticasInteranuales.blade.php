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
      <h1><img width="80" src="/img/logos/tablero_general_blue.png" alt=""> ESTADÍSTICAS INTERANUALES</h1>
    </div>
  </div>
</div> -->
    <!-- Tarjeta de FILTROS -->
    <div class="row">
      <div class="col-md-12">

        <div class="panel panel-default">
          <div class="panel-heading">
            <div class="row">
                <div class="row">
                  <div class="col-md-3">
                      <h4>Filtros de búsqueda</h4>
                  </div>
                  @foreach($casinos as $casino)
                    <div class="col-md-2 ">
                      <input type="radio" name="opcion2" value="{{$casino->id_casino}}"><h5>{{$casino->nombre}}</h5>
                    </div>
                  @endforeach


                </div>


                <div class="row">
                  <div class="col-md-3 col-md-offset-2">
                      <h5 style="font-family:Roboto-Regular;font-size:14px;font-weight:700;color:#aaa;">SELECCIONE UN AÑO</h4>
                      <div class="form-group">
                         <div class='input-group date' id='dtpFechaDesde' data-link-field="fecha_desde" data-date-format="yyyy" data-link-format="yyyy">
                             <input type='text' class="form-control" placeholder="Fecha Desde"/>
                             <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                             <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                         </div>
                         <input class="form-control" type="hidden" id="fecha_desde" value=""/>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <h5 style="font-family:Roboto-Regular;font-size:14px;font-weight:700;color:#aaa;">A COMPARAR CON EL AÑO</h4>
                      <div class="form-group">
                         <div class='input-group date' id='dtpFechaHasta' data-link-field="fecha_hasta" data-date-format="yyyy" data-link-format="yyyy">
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
                <h4>REPORTE INTERANUAL DE CASINO</h4>
            </div>
            <div class="panel-body">
                <div hidden class="row informacionEstadistica">
                    <div class="col-md-12">
                        <h5>BRUTO</h5>
                        <div id="seguimientoBruto"></div>
                    </div>

                </div> <!-- /.row -->
                <br>
                <div hidden class="row informacionEstadistica">
                    <div class="col-md-12">
                        <h5>CANON</h5>
                        <div id="seguimientoCanon"></div>
                    </div>

                </div> <!-- /.row -->
                <div hidden class="row informacionEstadistica">
                    <div class="col-md-12">
                        <table class="table table-fixed" style="font-size: 14px">
                            <thead id="cabeceraTablaXY">
                              <tr class="row">
                                 <th class="col-xs-2 col-xs-offset-1 cabeceraCasino" style="text-transform:uppercase;color: #fff !important;"></th>
                                 <th class="col-xs-4 cabeceraX"></th>
                                 <th class="col-xs-4 cabeceraY"></th>
                              </tr>
                              <tr class="row">
                                 <th class="col-xs-2 col-xs-offset-1" style="font-weight: bold">MES</th>
                                 <th class="col-xs-2" style="">BRUTO</th>
                                 <th class="col-xs-2" style="">CANON</th>
                                 <th class="col-xs-2" style="">BRUTO</th>
                                 <th class="col-xs-2" style="">CANON</th>
                              </tr>

                            </thead>
                            <tbody id="cuerpoTablaXY" style:"height: 500px;">

                            </tbody>
                            <tfoot id="pieTablaXY">

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
                <h4>REPORTE INTERANUAL DE CASINO POR JUEGO</h4>
            </div>
            <div class="panel-body">
                <div id="seguimientoCanonJuego"></div>
            </div>
            <div class="row informacionEstadistica">
                          <div class="col-md-12">
                            <table id="" class="table table-fixed" style="font-size: 14px">
                                <thead>
                                  <tr>
                                        <th class="col-xs-11 col-xs-offset-1 cabeceraX"></th>
                                  </tr>
                                    <tr>
                                        <th class="col-xs-2 col-xs-offset-1" style="font-weight: bold">MES</th>
                                        <th class="col-xs-2" style="background: #448AFF; color: #FFF !important;">MTM</th>
                                        <th class="col-xs-2" style="background: #9575CD; color: #FFF !important;">MESAS</th>
                                        <th class="col-xs-2" style="background: #FFC400; color: #FFF !important;">BINGO</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoTablaX" style:"height: 500px;">

                                </tbody>
                                <tfoot id="pieTablaX">

                                </tfoot>
                            </table>
                          </div>
              </div>
              <div class="row informacionEstadistica">
                                      <div class="col-md-12">
                                        <table id="" class="table table-fixed" style="font-size: 14px">
                                            <thead>
                                                <tr>

                                                  <th class="col-xs-11 col-xs-offset-1 cabeceraY"></th>
                                                </tr>
                                                <tr>
                                                    <th class="col-xs-2 col-xs-offset-1" style="font-weight: bold">MES</th>
                                                    <th class="col-xs-2" style="background: #448AFF; color: #FFF !important;">MTM</th>
                                                    <th class="col-xs-2" style="background: #9575CD; color: #FFF !important;">MESAS</th>
                                                    <th class="col-xs-2" style="background: #FFC400; color: #FFF !important;">BINGO</th>

                                                </tr>
                                            </thead>
                                            <tbody id="cuerpoTablaY" style:"height: 500px;">

                                            </tbody>
                                            <tfoot id="pieTablaY">

                                            </tfoot>
                                        </table>
                                      </div>
              </div>
              <br>
          </div>
        </div>
    </div>

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| ESTADÍSTICAS INTERANUALES</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Estadísticas Interanuales</h5>
  <p>
    Informes gráficos dónde intervienen los reportes interanuales de casino en específico, que compara su bruto y canon anual; también sus datos y estadísticas
    comparativas en cuanto a sus tipos de juegos, donde compara la recaudación total de éstas en comparación en su año elegido.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/seccionEstadisticasInteranuales.js" charset="utf-8"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Highchart -->
<script src="js/highcharts.js"></script>
<script src="js/highcharts-3d.js"></script>
@endsection
