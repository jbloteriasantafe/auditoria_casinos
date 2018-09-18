@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="/css/lista-datos.css">
  <link rel="stylesheet" href="/css/estilosDetallesContables.css">
@endsection

@section('contenidoVista')

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default" style="height:650px; padding-top:100px;">
                <div class="panel-heading" style="text-align:center;">
                    <h4>¿QUÉ MÁQUINA DESEA VER?</h4>
                </div>
                <div class="panel-body" style="text-align:center;">
                    <img src="/img/logos/tragaperras.png" alt="" width="250px" style="margin-bottom:40px; margin-top:20px;">

                    <div class="row">
                        <div class="col-md-4 col-md-offset-4">
                            <select id="selectCasino" class="form-control" name="">
                                  <option value="0">- Seleccione el casino -</option>
                                @foreach($casinos as $casino)
                                  <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                @endforeach
                            </select>
                            <br>
                            <input id="inputMaquina" class="form-control" type="text" placeholder="N° de administración" disabled>
                            <br>
                            <button id="btn-buscarMTM" class="btn btn-infoBuscar" type="button" style="width:100%;">VER DETALLES</button>
                        </div>
                    </div>
                    <br>
                </div>
            </div>
        </div> <!-- col-md-4 -->
    </div>



    <div class="modal fade" id="modalMaquinaContable" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:90%;">
             <div class="modal-content">
                  <div class="modal-header" style="background:#304FFE;">
                      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                      <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                      <h3 class="modal-title" style="color: #fff; text-align:center">DETALLES CONTABLES DE LA MÁQUINA</h3>
                  </div>
                <div id="colapsado" class="collapse in">
                  <div class="modal-body">

                    <!-- CONFIGURACIÓN -->
                    <div class="row" style="padding-bottom:12px;">
                        <div class="col-md-3" style="text-align:center; border-right: 1px solid #ccc;">
                            <!-- <h5>N° ADMIN - MARCA</h5> -->
                            <span id="nro_admin" class="infoResaltada">2010</span>
                            <img src="/img/logos/tragaperras_blue.png" alt="" width="140px;" style="position:relative;left:10px;top:-14px;">
                            <span id="marca" class="infoResaltada">ARISTOCRAT</span>
                        </div>
                        <div class="col-md-9" style="text-align:center;">
                            <div class="row" style="padding-top:10px;padding-bottom:10px;border-bottom: 1px solid #ccc;">
                                <div class="col-md-4">
                                  <h5>CASINO</h5>
                                  <span id="casino" class="infoResaltada">ROSARIO</span>
                                </div>
                                <div class="col-md-4">
                                  <h5>SECTOR</h5>
                                  <span id="sector" class="infoResaltada">SUBSUELO</span>
                                </div>
                                <div class="col-md-4">
                                  <h5>ISLA</h5>
                                  <span id="isla" class="infoResaltada">23</span>
                                </div>
                            </div>
                            <div class="row" style="padding-top:20px;">
                                <div class="col-md-4">
                                    <h5>JUEGO ACTIVO</h5>
                                    <span id="juego" class="infoResaltada">LUCKY LARRYS LOBSTERMANIA</span>
                                </div>
                                <div class="col-md-4">
                                    <h5>DENOMINACIÓN</h5>
                                    <span id="denominacion" class="infoResaltada">0.1</span>
                                </div>
                                <div class="col-md-4">
                                    <h5>% DEVOLUCIÓN</h5>
                                    <span id="devolucion" class="infoResaltada">83 %</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONTADORES -->
                    <div class="row" style="border-top: 1px solid #ddd;padding:10px 0px 15px 0px;">
                        <div class="col-md-9" style="text-align:center; border-right: 1px solid #ccc;">
                          <div class="row">
                            <h5>PRODUCIDO</h5>
                              <div class="col-md-12">
                                  <span class="infoResaltada" style="display:inline;position:relative;top:-8px;font-size:20px;">$</span>
                                  <span id="producido" class="infoResaltada">123445</span>
                              </div>
                              <div class="col-md-12">
                                  <div id="graficoSeguimientoContadores"></div>
                              </div>

                              <style media="screen">
                                  .infoImportacion {
                                    font-family: Roboto-Condensed;
                                    font-size: 26px;
                                  }
                                  .iconInfo {
                                    display: inline;
                                    margin-left: 18px;
                                    position: relative; top:-4px;
                                  }
                                  .iconInfo.fa-check {
                                    color: #00E676;
                                  }
                                  .iconInfo.fa-times {
                                    color: #FF1744;
                                  }
                              </style>


                          </div>

                          <div class="row detalleEstados" style="padding:30px 0px;" hidden>
                              <div class="col-md-12">
                                  <span id="fechaEstado" style="font-family:Roboto-Light;font-size:28px;">01 Oct 17</span>
                                  <span id="producidoEstado" style="font-family:Roboto-Condensed;font-size:28px;margin-left:30px;">$ 1647</span>
                              </div>
                          </div>
                          <div class="row detalleEstados" hidden>
                              <div class="col-md-4">
                                  <h5>ESTADO CONTADORES</h5>
                                  <br>
                                  <span class="infoImportacion">Cerrado</span><i class="fa fa-check iconInfo contador_cerrado"></i><i class="fa fa-times iconInfo contador_cerrado"></i>
                                  <br>
                                  <span class="infoImportacion">Importado</span><i class="fa fa-check iconInfo contador_importado"></i><i class="fa fa-times iconInfo contador_importado"></i>
                              </div>
                              <div class="col-md-4">
                                  <h5>ESTADO RELEVAMIENTO</h5>
                                  <br>
                                  <span class="infoImportacion">Relevado</span><i class="fa fa-check iconInfo relevamiento_relevado"></i><i class="fa fa-times iconInfo relevamiento_relevado"></i>
                                  <br>
                                  <span class="infoImportacion">Validado</span><i class="fa fa-check iconInfo relevamiento_validado"></i><i class="fa fa-times iconInfo relevamiento_validado"></i>
                              </div>
                              <div class="col-md-4">
                                  <h5>ESTADO PRODUCIDO</h5>
                                  <br>
                                  <span class="infoImportacion">Importado</span><i class="fa fa-check iconInfo producido_importado"></i><i class="fa fa-times iconInfo producido_importado"></i>
                                  <br>
                                  <span class="infoImportacion">Validado</span><i class="fa fa-check iconInfo producido_validado"></i><i class="fa fa-times iconInfo producido_validado"></i>
                              </div>
                          </div>
                        </div>
                        <div id="listaMovimientos" class="col-md-3" style="text-align:left;">
                            <h5>ÚLTIMOS MOVIMIENTOS</h5>

                            <!-- <div class="lineaTiempo"></div> -->
                            <br><br>

                            <div id="mov" class="filaMovimiento" hidden>
                                <div class="circuloTiempo"></div>
                                <span class="fecha">12-MAR-2018</span><i class="fa fa-share link"></i>
                                <span class="razon infoResaltada">CAMBIO DE SECTOR</span>
                            </div>

                            <div id="mensajeMovimiento" style="padding:15px;">
                                <span style="font-family:Roboto-Regular;font-size:18px;">No existen movimientos para esta máquina</span>
                            </div>

                        </div>
                    </div>








                  </div>
                  <div class="modal-footer">

                  </div>
                </div>
             </div>
          </div>
    </div>
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA INFORME DE MTM</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Informe de MTM</h5>
  <p>
      Detalle de información contable de una máquina tragamoneda. Son visibles los datos de su sector,
      isla, denominación, % de evolución, su condición de ingreso en el juego y sus últimos movimientos.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<script src="/js/contable_mtm.js" charset="utf-8"></script>

<script src="/js/lista-datos.js" type="text/javascript"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Highchart -->
<script src="js/highcharts.js"></script>
<script src="js/highcharts-3d.js"></script>

@endsection
