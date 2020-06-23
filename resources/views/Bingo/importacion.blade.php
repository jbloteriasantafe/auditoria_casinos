  @extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];;
$casinos = $usuario->casinos;
?>

@section('estilos')
  <link rel="stylesheet" href="/css/paginacion.css">
  <link rel="stylesheet" href="/css/lista-datos.css">
  <link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
  <link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
  <link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
  <link rel="stylesheet" href="/css/animacionCarga.css">

@endsection

@section('contenidoVista')

                <div class="row">
                  <div class="col-lg-12 col-xl-9">

                     <div class="row"> <!-- fila de FILTROS -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltros" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">
                                  <div class="col-md-3">
                                    <h5>Fecha de la sesión</h5>
                                    <!-- <div class="form-group"> -->
                                       <div class='input-group date' id='dtpBuscadorFecha' data-link-field="buscadorFecha" data-link-format="yyyy-mm">
                                           <input type='text' class="form-control" placeholder="Mes de sesión" id="B_fecharelevamiento"/>
                                           <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                           <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                       </div>
                                       <input class="form-control" type="hidden" id="buscadorFecha" value=""/>
                                    <!-- </div> -->
                                  </div>
                                  <div class="col-md-3">
                                    <h5>CASINO</h5>
                                    <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                        <option value="0">-Todos los Casinos-</option>
                                        @foreach($casinos as $casino)
                                        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                        @endforeach
                                    </select>
                                  </div>

                                  <div class="col-md-6 text-right">
                                    <h5 style="color:#f5f5f5;">boton buscar</h5>
                                    <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div><br>
                              </div> <!-- /.panel-body -->
                            </div>
                          </div> <!-- /.panel -->
                        </div> <!-- /.col-md-12 -->
                    </div> <!-- Fin de la fila de FILTROS -->


                      <div class="row"><!-- RESULTADOS BÚSQUEDA -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>LISTA DE IMPORTACIONES</h4>
                            </div>
                            <div class="panel-body modal-cuerpo">
                              <table id="tablaResultados" class="table table-striped tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col" value="fecha">FECHA SESIÓN <i class="fa fa-sort"></i></th>
                                    <th class="col" value="id_casino">CASINO <i class="fa fa-sort"></i></th>
                                    <th class="col" value="id_usuario">USUARIO <i class="fa fa-sort"></i></th>
                                    <th class="col">ACCIÓN </th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTabla">


                                </tbody>
                              </table>
                              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                              </div>
                            </div>
                          </div>



                        </div> <!-- Fin del col de los filtros -->

                      </div> <!-- Fin del row de la tabla -->


                      <div class="col-lg-12 col-xl-3"><!-- BOTÓN NUEVA IMPORTACIÓN -->
                        <div class="row">
                          <div class="col-md-12">
                            <a href="" id="btn-nuevo" style="text-decoration: none;">
                                <div class="panel panel-default panelBotonNuevo">
                                  <center><img class="imgNuevo" src="/img/logos/CSV_white.png"><center>
                                    <div class="backgroundNuevo"></div>
                                      <div class="row">
                                        <div class="col-xs-12">
                                          <center>
                                            <h5 class="txtLogo">+</h5>
                                            <h4 class="txtNuevo">IMPORTAR RELEVAMIENTO</h4>
                                          </center>
                                        </div>
                                  </div>
                              </div>
                            </a>
                           </div>
                          </div>
                      </div>

            </div> <!--/columna row -->


            <!-- Modal Importacion -->
            <div class="modal fade" id="modalImportacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                     <div class="modal-content">
                       <div class="modal-header modalNuevo">
                         <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                         <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                         <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                         <h3 class="modal-title">| IMPORTAR SESIÓN</h3>
                        </div>

                        <div  id="colapsado" class="collapse in">

                        <div class="modal-body modalCuerpo">
                                <!-- Estilos del mansaje de información -->
                                <style media="screen">
                                  #mensajeInvalido i {
                                    color: #FF5252;
                                    position: relative;
                                    top: -3px;
                                    left: -10px;
                                    transform: scale(2);
                                  }
                                  #mensajeInvalido h6 {
                                    margin-left: 6px !important;
                                    color: #FF1744;
                                    display:inline;
                                    font-size: 20px;
                                    font-weight:bold !important; font-family:Roboto-Condensed !important
                                  }
                                  #mensajeInvalido p {
                                    /*color: black;*/
                                    display:inline;
                                    font-size: 16px;
                                    /*font-weight:bold !important; */
                                    font-family:Roboto-Regular !important
                                  }

                                  #mensajeInformacion h6 {
                                      margin-left: 10px;
                                      display:inline;
                                      font-size: 20px;
                                      font-weight:bold !important; font-family:Roboto-Condensed !important;
                                  }

                                  #mensajeInformacion i {
                                      position: relative;
                                      top: -3px;
                                      /*transform: scale(0.7);*/
                                      color: #6DC7BE;
                                  }

                                  #mensajeInformacion i.corrido {
                                      margin-left: 10px;
                                  }

                                  #iconoMoneda {
                                    transform: scale(1.2);
                                  }
                              </style>

                          <div id="rowArchivo" class="row" style="">
                                  <div class="col-xs-12">
                                      <h5>ARCHIVO</h5>
                                      <div class="zona-file">
                                        <input id="archivo" data-borrado="false" type="file" name="" >
                                        <br> <span id="alertaArchivo" class="alertaSpan"></span>
                                      </div>
                                  </div>
                          </div>

                          <div id="mensajeError" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                                  <div class="col-md-12">
                                      <h6 id="msjeError">SE PRODUJO UN ERROR DE CONEXIÓN</h6>
                                      <button id="btn-reintentar" class="btn btn-info" type="button" name="button">REINTENTAR IMPORTACIÓN</button>
                                  </div>
                              </div>

                          <div id="mensajeInvalido" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                                    <div class="col-xs-12" align="center">
                                        <i class="fa fa-fw fa-exclamation-triangle"></i>
                                        <h6> ARCHIVO INCORRECTO</h6>
                                    </div>
                                    <br>
                                    <br>
                                    <div class="col-xs-12" align="center">
                                        <p>Solo se aceptan archivos con extensión .csv o .txt</p>
                                    </div>
                              </div>

                          <div id="casinoSelect" class="row" style="margin-bottom:20px !important; margin-top: 50px !important;">
                                          <div class="col-xs-12" align="center">
                                            <select id="id_casino" class="form-control selectCasinos" name="">
                                                @foreach($casinos as $casino)

                                                <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>

                                                @endforeach
                                            </select>
                                          </div>
                            </div>


                              <div id="iconoCarga" class="sk-folding-cube">
                                <div class="sk-cube1 sk-cube"></div>
                                <div class="sk-cube2 sk-cube"></div>
                                <div class="sk-cube4 sk-cube"></div>
                                <div class="sk-cube3 sk-cube"></div>
                              </div>

                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-successAceptar" id="btn-guardar" hidden value="nuevo"> SUBIR</button>
                          <button type="button" class="btn btn-default" data-dismiss="modal"> CANCELAR</button>
                          <input type="hidden" id="guarda_igual" value="0">
                          <input type="hidden" id="i_eliminar" value="0">
                        </div>
                      </div>
                    </div>
                  </div>
            </div>

    <!-- Modal Eliminar Imprtación-->
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-title" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeEliminar"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-danger" id="btn-eliminar" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>

    <!-- Modal importación ya cargada-->
    <div class="modal" id="modalImportacionCargada" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                  <div class="modal-header modalNuevo">
                      <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                      <h3 class="modal-title"> IMPORTAR SESIÓN</h3>
                  </div>
                  <div class="modal-body">
                      <form id="frmMotivos">
                        <h5 style="padding:10px;font-family:Roboto-Condensed;color:#FF1744 !important;font-size:24px;">ATENCIÓN</h5>
                        <h5 style="padding:0px;font-family:Roboto-Condensed;color:#444 !important;font-size:20px;">YA EXISTE IMPORTACIÓN PARA EL DÍA Y CASINO SELECCIONADO.</h5>

                      <p style="font-family:Roboto-Regular;font-size:16px;margin:20px 0px;">
                        Si vuelve a importar la sesión se sobreescribirán los datos anteriores.
                      </p>

                      <p style="font-family:Roboto-Regular;font-size:16px;margin-bottom:20px;">
                        ¿Desea importar la sesión de todas formas? Por favor ingrese el motivo.
                      </p>
                      <div id="campo-valor">
                        <input placeholder="" id="motivo-reimportacion" type="text" class="form-control">
                      </div>
                      </form>
                  </div>
                  <div class="modal-footer">
                    <button id="btn-guardarIgual" type="button" class="btn btn-successAceptar" value="nuevo">IMPORTAR DE TODAS FORMAS</button>
                    <button id="btn-cancelarConfirmacion" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  </div>
              </div>

          </div>
    </div>

    <!-- Modal DETALLES -->
    <div class="modal fade" id="modalDetallesRel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" style="min-width:85%;">
             <div class="modal-content">
                <div class="modal-header pbzero">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title pbtitle" id="myModalLabel">| DETALLES IMORTACIÓN</h3>

                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmDetallesRel" name="frmDetallesRel" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                        <div class="tab-content">
                          <div class="col-lg-12 tab-pane fade in active" id="detalles">

                          <span id="alerta_sesion" class="alertaSpan"></span>


                          <div class="panel-body modal-cuerpo">
                            <table id="tablaResultadosRel" class="table table-striped">
                              <thead>
                                <tr>
                                  <!-- <th class="col" value="fecha">FECHA SESIÓN <i class="fa fa-sort"></i></th> -->
                                  <th class="col" value="num_partida">Nro. PARTIDA <i class="fa fa-sort"></i></th>
                                  <th class="col" value="hora_inicio">HORA <i class="fa fa-sort"></i></th>
                                  <th class="col" value="serieA">SERIE INICIAL <i class="fa fa-sort"></i></th>
                                  <th class="col" value="carton_inicio_A">CARTON INICIAL <i class="fa fa-sort"></i></th>
                                  <th class="col" value="carton_fin_A">CARTON FINAL <i class="fa fa-sort"></i></th>
                                  <th class="col" value="serieB">SERIE FINAL <i class="fa fa-sort"></i></th>
                                  <th class="col" value="carton_incio_B">CARTON INICIAL <i class="fa fa-sort"></i></th>
                                  <th class="col" value="carton_fin_B">CARTON FINAL <i class="fa fa-sort"></i></th>
                                  <th class="col" value="cartones_vendidos">CARTONES VENDIDOS <i class="fa fa-sort"></i></th>
                                  <th class="col" value="valor_carton">VALOR CARTON <i class="fa fa-sort"></i></th>
                                  <th class="col" value="cant_bola">CANT. BOLA <i class="fa fa-sort"></i></th>
                                  <th class="col" value="recaudado">RECAUDADO <i class="fa fa-sort"></i></th>
                                  <th class="col" value="premio_linea">PREMIO LÍNEA <i class="fa fa-sort"></i></th>
                                  <th class="col" value="premio_bingo">PREMIO BINGO <i class="fa fa-sort"></i></th>
                                  <th class="col" value="pozo_dot">POZO DOT. <i class="fa fa-sort"></i></th>
                                  <th class="col" value="pozo_extra">POZO EXTRA <i class="fa fa-sort"></i></th>
                                </tr>
                              </thead>
                              <tbody id="cuerpoTablaRel">


                              </tbody>
                            </table>
                            </div>


                          </div>
                                <div class="col-lg-12 tab-pane fade" id="historialCambios">
                                <div class="panel-body modal-cuerpo">
                                  <table id="tablaResultadosHis" class="table table-striped">
                                    <thead>
                                      <tr>
                                        <th class="col" value="fecha_h">FECHA</th>
                                        <th class="col" value="usuario_incio_h">USUARIO INICIO</th>
                                        <th class="col" value="fecha_inicio_h">FECHA INICIO</th>
                                        <th class="col" value="hora_inicio_h">HORA INICIO</th>
                                        <th class="col" value="pozo_dot_inicial_h">POZO DOT. INICIAL</th>
                                        <th class="col" value="pozo_extra_inicial_h">POZO EXTRA INICIAL</th>
                                        <th class="col" value="usuario_fin_h">USUARIO FIN</th>
                                        <th class="col" value="fecha_fin_h">FECHA FIN</th>
                                        <th class="col" value="hora_fin_h">HORA FIN</th>
                                        <th class="col" value="pozo_dot_final_h">POZO DOT. FINAL</th>
                                        <th class="col" value="pozo_extra_final_h">POZO EXTRA FINAL</th>
                                      </tr>
                                    </thead>
                                    <tbody id="cuerpoTablaHis">
                                    </tbody>
                                  </table>
                                  </div>

                                </div>
                        </div>
                      </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">

                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_sesion" value="0">
                </div>
              </div>
            </div>
          </div>

    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title2" style="color: #fff;">| IMPORTAR RELVAMIENTOS DE PARTIDAS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Importar Relevamientos</h5>
      <p>
        Visualiza e importa relevamientos de partidas de bingo.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="/js/Bingo/importacion.js" charset="utf-8"></script>
    <script src="/js/Bingo/lista-datos.js" type="text/javascript"></script>
    <!-- Custom input Bootstrap -->
    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
    @endsection
