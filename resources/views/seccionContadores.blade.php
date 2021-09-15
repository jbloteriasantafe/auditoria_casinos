@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel='stylesheet' href='/css/fullcalendar.min.css'/>
<style>
#tablaContadores > thead > tr > th {
  text-align: center;
}
#tablaContadores > tbody > tr > td {
  text-align: center;
}
#maquinasModal > tbody > tr > td {
  text-align: center;
}
</style>
@endsection

        <div class="row">

            <div class="col-xl-9">
                <div class="row"> <!-- FILTROS -->
                      <div class="col-md-12">
                        <div class="panel panel-default">
                          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                            <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                          </div>
                          <div id="collapseFiltros" class="panel-collapse collapse">
                            <div class="panel-body">
                              <div class="row"> <!-- Primera fila -->
                                <div class="col-lg-2">
                                  <h5>Casino</h5>
                                  <select class="form-control" id="selectCasinos">
                                    @if($casinos->count() == 1)
                                    <option id="{{$casinos[0]->id_casino}}" value="{{$casinos[0]->id_casino}}">{{$casinos[0]->nombre}}</option>
                                    @else
                                    <option value="">- TODOS -</option>
                                     @foreach ($casinos as $casino)
                                     <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                     @endforeach
                                    @endif
                                  </select>
                                </div>
                                <div class="col-lg-2">
                                  <h5>Moneda</h5>
                                  <select class="form-control" id="selectTipoMoneda">
                                    <option value="">- TODAS -</option>
                                     @foreach ($tipo_monedas as $tipo_moneda)
                                     <option id="{{$tipo_moneda->id_tipo_moneda}}" value="{{$tipo_moneda->id_tipo_moneda}}">{{$tipo_moneda->descripcion}}</option>
                                     @endforeach
                                  </select>
                                </div>
                                <div class="col-lg-3">
                                    <h5>Fecha Desde</h5>
                                    <div class="form-group">
                                       <div class='input-group date' id='dtpFechaDesde' data-link-field="fecha_desde" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                                           <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio"/>
                                           <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                           <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                       </div>
                                       <input class="form-control" type="hidden" id="fecha_desde" value=""/>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                  <h5>Fecha Hasta</h5>
                                  <div class="form-group">
                                     <div class='input-group date' id='dtpFechaHasta' data-link-field="fecha_hasta" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                                         <input type='text' class="form-control" placeholder="Fecha Hasta" id="B_fecha_inicio"/>
                                         <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                         <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                     </div>
                                     <input class="form-control" type="hidden" id="fecha_hasta" value=""/>
                                  </div>
                                </div>
                                <div class="col-lg-2">
                                  <h5>Validado</h5>
                                  <select class="form-control" id="selectValidado">
                                    <option value="">- TODOS -</option>
                                    <option value="0">NO</option>
                                    <option value="1">SI</option>
                                  </select>
                                </div>
                              </div>
                              <div class="row">
                                  <div class="col-md-12">
                                    <center><button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button></center>
                                  </div>
                              </div>
                              <br>
                            </div> <!-- /.panel-body -->
                          </div>
                        </div> <!-- /.panel -->
                      </div>
                </div>

                <div class="row"> <!-- TABLA -->
                      <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>ÚLTIMOS CONTADORES</h4>
                            </div>
                            <div class="panel-body">
                              <table id="tablaContadores" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-3">FECHA</th>
                                    <th class="col-xs-2">CASINO</th>
                                    <th class="col-xs-2">MONEDA</th>
                                    <th class="col-xs-2">VALIDADO</th>
                                    <th class="col-xs-3">ACCIÓN</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTablaResultados" style="height: 350px;">
                                </tbody>
                              </table>
                              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                              </div>
                            </div>
                          </div>
                      </div>
                </div>
            <table hidden>
              <tr id="filaEjemploResultado">
                <td class="col-xs-3 fecha">9999-99-99</td>
                <td class="col-xs-2 casino">CAS</td>
                <td class="col-xs-2 moneda">MON</td>
                <td class="col-xs-2 validado">
                  <i class="fa fa-check" style="color: green;"></i>
                  <i class="fa fa-times" style="color: red;"></i>
                </td>
                <td class="col-xs-3 accion">
                  <button class="btn btn-info ver" title="VER">
                    <i class="fa fa-fw fa-search-plus"></i>
                  </button>
                  <button class="btn btn-info validar" title="VALIDAR">
                    <i class="fa fa-fw fa-check"></i>
                  </button>
                  <button class="btn btn-info imprimir" title="IMPRIMIR">
                    <i class="fa fa-fw fa-print"></i>
                  </button>
                </td>
              </tr>
            </table>

            <div class="col-xl-3">
              <div class="row">
                <div class="col-md-12">
                  <a href="importaciones" style="text-decoration:none;">
                    <div class="tarjetaSeccionMenor" align="center">
                      <h2 class="tituloFondoMenor">IMPORTACIONES</h2>
                      <h2 class="tituloSeccionMenor">IMPORTACIONES</h2>
                      <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/importaciones_white.png" alt="">
                    </div>
                  </a>
                </div>
              </div>
            </div> <!-- /.col-md-3 -->

        </div> <!-- /.row -->

    <div class="modal fade" id="modalContadores" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:70%;">
        <div class="modal-content">
          <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FFB74D;">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
            <h3 class="modal-title">CONTADORES</h3>
          </div>
          <div  id="colapsadoCargar" class="collapse in">
            <div class="modal-body modalCuerpo">
              <div class="row">
                <div class="col-md-3">
                  <h5>Casino</h5>
                  <input type="text" readonly="true" id="casinoModal" disabled class="form-control">
                </div>
                <div class="col-md-3">
                  <h5>Moneda</h5>
                  <input type="text" readonly="true" id="monedaModal" disabled class="form-control">
                </div>
                <div class="col-md-3">
                  <h5>Fecha</h5>
                  <input type="text" readonly="true" id="fechaModal" disabled class="form-control">
                </div>
                <div class="col-md-3">
                  <h5>Alertas</h5>
                  <input type="text" readonly="true" id="alertasModal" disabled class="form-control">
                </div>
              </div>
              <hr>
              <div class="row">
                <div class="col-md-3" style="border-right: 1px solid #ddd;height: 700px;">
                  <div class="row">
                    <div class="col-md-12">
                      <input type="checkbox" id="verSoloAlertasModal" class="form-check-input">
                      <label class="form-check-label" for="verSoloAlertasModal">VER SOLO ALERTAS</label>
                    </div>
                  </div>
                  <div class="row" style="overflow-y: scroll;max-height: 95%;">
                    <h5 style="text-align: center;">MÁQUINAS</h5>
                    <table id="maquinasModal" class="col-md-12 table">
                    </table>
                    <table hidden>
                      <tr id="filaEjemploMaquina">
                        <td class="nro_admin">9999</td>
                        <td>
                          <button class="btn btn-info verContadores" title="VER CONTADORES">
                            <i class="fa fa-fw fa-search-plus"></i>
                          </button>
                        </td>
                      </tr>
                    </table>
                  </div>
                </div>
                <div class="col-md-9" id="detalleModal">
                  <div class="row">
                    <div class="col-md-4">
                      <h5>MÁQUINA</h5>
                      <input id="maquinaModal" readonly disabled class="form-control"/>
                    </div>
                    <div class="col-md-4">
                      <h5>ESTADO</h5>
                      <input id="estadoModal" readonly disabled class="form-control"/>
                    </div>
                  </div>
                  <div class="row">
                    <h5>CONTADORES</h5>
                    <div class="col-md-12" style="height: 200px;overflow-y: scroll;border: 1px solid #ddd">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>HORA</th>
                            <th>ISLA</th>
                            <th>COININ</th>
                            <th>COINOUT</th>
                            <th>JACKPOT</th>
                            <th>PROGRESIVO</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td>HORA</td>
                            <td>ISLA</td>
                            <td>COININ</td>
                            <td>COINOUT</td>
                            <td>JACKPOT</td>
                            <td>PROGRESIVO</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="row">
                    <h5>ALERTAS</h5>
                    <div class="col-md-12" style="height: 200px;overflow-y: scroll;border: 1px solid #ddd">
                      <table class="table">
                        <thead>
                          <tr>
                            <th>HORA</th>
                            <th>DESCRIPCION</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td><a>HORA</a></td>
                            <td>DESCRIPCION</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-10 col-md-offset-1">
                      <h5>OBSERVACIONES</h5>
                      <textarea class="form-control" style="height: 125px;" id="observacionesModal"></textarea>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-1 col-md-offset-5">
                      <button type="button" class="btn btn-success" id="btn-validar">VALIDAR</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" id="btn-salir" data-dismiss="modal">SALIR</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| CONTADORES</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <p>
        En esta sección se obtienen alertas sobre la evolución de los contadores individuales para cada maquina.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>

    
    <script src='/js/moment.min.js'></script>
    <script src='/js/fullcalendar.min.js'></script>
    <script src='/js/locale-all.js'></script>

    <!-- JavaScript personalizado -->
    <script src="js/seccionContadores.js" charset="utf-8"></script>-
    
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    @endsection
