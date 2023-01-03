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
                          @if($nro_admin == null)
                            <select id="selectCasino" class="form-control" name="">
                                  <option value="0">- Seleccione el casino -</option>
                                      @foreach($casinos as $casino)
                                        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                      @endforeach
                            </select>
                            <br>
                            <input id="inputMaquina" class="form-control" type="text" placeholder="N° de administración" disabled>
                            @endif
                            @if($nro_admin != null)
                            <select id="selectCasino" class="form-control" name="">
                                  <option value="{{$casino}}" selected> {{$nombre}} </option>
                                      @foreach($casinos as $casino)
                                        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                      @endforeach
                            </select>
                            <br>
                            <input id="inputMaquina" class="form-control" type="text" placeholder="{{$nro_admin}}" disabled>

                            @endif
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

                    <div class="row">
                        <div class="col-md-4" >
                            <div class="row" style="text-align:center;">
                                <div class="col-md-12">
                                  <span id="nro_admin" class="infoResaltada"></span>
                                  <img src="/img/logos/tragaperras_blue.png" alt="" width="140px;" style="position:relative;left:10px;top:-14px;">
                                  <span id="marca" class="infoResaltada"></span>
                                </div>
                              
                                {{-- <div class="col-md-12">
                                  <h5>JUEGO ACTIVO</h5>
                                  <span id="juego" class="infoResaltada"></span>
                                </div> --}}
                                {{-- <div class="col-md-12" style="border-top:1px solid #ccc;">
                                  <h5>CASINO</h5>
                                  <span id="casino" class="infoResaltada"></span>
                                </div>
                                <br>
                                <div class="col-md-6">
                                  <h5>SECTOR</h5>
                                  <span id="sector" class="infoResaltada"></span>
                                </div>
                                <div class="col-md-6">
                                  <h5>ISLA</h5>
                                  <span id="isla" class="infoResaltada"></span>
                                </div>
                                <div class="col-md-6" style="border-top:1px solid #ccc;">
                                  <h5>DENOMINACIÓN</h5>
                                  <span id="denominacion" class="infoResaltada"></span>
                                </div>
                                <div class="col-md-6" style="border-top:1px solid #ccc;">
                                  <h5>% DEVOLUCIÓN</h5>
                                  <span id="devolucion" class="infoResaltada"></span>
                                </div> --}}
                            </div>
                        </div>

                        <div class="col-md-4" style="border-right:1px solid #ccc; border-left:1px solid #ccc;">
                          <div class="row" style="text-align:center;">

                            <div class="col-md-12" style="border-top:1px solid #ccc;">
                              <h5>CASINO</h5>
                              <span id="casino" class="infoResaltada"></span>
                            </div>
                            <br>
                            <div class="col-md-6">
                              <h5>SECTOR</h5>
                              <span id="sector" class="infoResaltada"></span>
                            </div>
                            <div class="col-md-6">
                              <h5>ISLA</h5>
                              <span id="isla" class="infoResaltada"></span>
                            </div>
                            <div class="col-md-12" style="border-top:1px solid #ccc;">
                              <h5>DENOMINACIÓN CONTABLE BASE</h5>
                              <span id="denominacion" class="infoResaltada"></span>
                            </div>
                            <div class="col-md-12">
                                <h5>JUEGO ACTIVO</h5>
                                <span id="juego" class="infoResaltada"></span>
                              </div>
                            {{-- <div class="col-md-6" style="border-top:1px solid #ccc;">
                              <h5>% DEVOLUCIÓN</h5>
                              <span id="devolucion" class="infoResaltada"></span>
                            </div> --}}

                          </div>
                        </div>
                        <div class="col-md-4">
                            <h5>HISTORIAL DE NO TOMAS</h5>
                            <br>
                            <div style="height: 350px;overflow: auto;">
                              <table id="tablaNoToma" class="table">
                                <thead>
                                  <tr>
                                    <th>DÍAS</th>
                                    <th>MOTIVO</th>
                                  </tr>
                                </thead>
                                <tbody>
                                </tbody>
                              </table>
                            </div>
                        </div>
                    </div>

                  </div>
                  <div class="row" style="border-top: 1px solid #ddd;padding:10px 0px 15px 0px;">
                      <div class="col-md-12">
                          <h6> Últimos 5 relevamientos</h6>
                          <br>
                        <table id="tablaRelevamientosDesdeNoToma" class="table">
                          <thead>
                            <tr>
                              <th>FECHA</th>
                              <th>CONT 1</th>
                              <th>CONT 2</th>
                              <th>CONT 3</th>
                              <th>CONT 4</th>
                              <th>CONT 5</th>
                              <th>CONT 6</th>
                              <th>CONT 7</th>
                              <th>CONT 8</th>
                              <th>COIN IN</th>
                              <th>COIN OUT</th>
                              <th>JACKPOT</th>
                              <th>PROGRESIVO</th>
                              <th>PROD CALCULADO</th>
                              <th>PROD IMPORTADO</th>
                              <th>DIFERENCIA</th> <!-- 15 columnas -->
                            </tr>
                          </thead>
                          <tbody style="color:black;">

                          </tbody>
                        </table>
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
<script src="/js/informe_no_toma.js?2" charset="utf-8"></script>

<script src="/js/lista-datos.js" type="text/javascript"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Highchart -->
<script src="/js/highcharts.js"></script>
<script src="/js/highcharts-3d.js"></script>

@endsection
