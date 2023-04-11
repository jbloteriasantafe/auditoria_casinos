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

@endsection

@section('contenidoVista')

                <div class="row">
                  <div class="col-lg-12 col-xl-5">

                     <div class="row"> <!-- fila de FILTROS -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltros" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">
                                  <div class="col-md-6">
                                    <h5>Casino</h5>
                                    <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                        <option value="0">-Todos los Casinos-</option>
                                        @foreach($casinos as $casino)
                                        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                        @endforeach
                                    </select>
                                  </div>

                                  <div class="col-md-6 text-right">
                                    <h5 style="color:#f5f5f5;">boton buscar</h5>
                                    <button id="btn-buscar-premio" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div><br>
                              </div> <!-- /.panel-body -->
                            </div>
                          </div> <!-- /.panel -->
                        </div> <!-- /.col-md-12 -->
                    </div> <!-- Fin de la fila de FILTROS -->


                      <div class="row"><!-- RESULTADOS BÚSQUEDA -->
                        <div class="col-md-12" style="width: 1300px">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>LISTA DE PREMIOS</h4>
                            </div>
                            <div class="panel-body modal-cuerpo">
                              <table id="tablaResultadosPremios" class="table table-fixed tablesorter" >
                                <thead style="width: 1200px">
                                  <tr>
                                    <th class="col-xs-2" value="nombre_premio" estado="">NOMBRE DE PREMIO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="porcentaje" estado="">PORCENTAJE <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="bola_tope" estado="">BOLA TOPE  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="tipo_premio" estado="">TIPO PREMIO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="casino_p" estado="">CASINO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2">ACCIONES</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTablaPremios" style="height: 370px; width: 1200px">


                                </tbody>
                              </table>
                              <!-- <div id="herramientasPaginacion" class="row zonaPaginacion"></div> -->
                              </div>
                            </div>
                          </div>



                        </div> <!-- Fin del col de los filtros -->

                      </div> <!-- Fin del row de la tabla -->


                  <div class="col-lg-12 col-xl-5">

                    <div class="row" style="display: none;"> <!-- fila de FILTROS -->
                        <div class="col-md-12" style="width:1300px">
                          <div class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltros" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">
                                  <div class="col-md-3">
                                    <h5>Fecha de Sesión</h5>
                                    <input type="date" id="buscadorFecha" class="form-control" style="padding: 0px!important;">
                                  </div>
                                  <div class="col-md-3">
                                    <h5 style="color:#f5f5f5;">boton buscar</h5>
                                    <button id="btn-buscar-canon" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div><br>
                              </div> <!-- /.panel-body -->
                            </div>
                          </div> <!-- /.panel -->
                        </div> <!-- /.col-md-12 -->
                    </div> <!-- Fin de la fila de FILTROS -->


                      <div class="row"><!-- RESULTADOS BÚSQUEDA -->

                        <!--
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>LISTA DE CANON</h4>
                            </div>
                            <div class="panel-body modal-cuerpo">
                              <table id="tablaResultadosCanon" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-4" value="fecha_inicio" estado="">FECHA INICIO <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="canon" estado="">% CANON <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-4" value="canon" estado="">% CASINO <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2">ACCIONES</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTablaCanon" style="height: 370px;">


                                </tbody>
                              </table>
                              </div>
                            </div>
                          </div>
                          -->


                        </div> <!-- Fin del col de los filtros -->

                      </div> <!-- Fin del row de la tabla -->

                      <div class="col-lg-6 col-xl-2"><!-- BOTÓN NUEVO PREMIO -->
                        <div class="row">
                          <div class="col-md-12">
                            <a href="" id="btn-nuevo-premio" style="text-decoration: none;">
                                <div class="panel panel-default panelBotonNuevo">
                                  <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                                    <div class="backgroundNuevo"></div>
                                      <div class="row">
                                        <div class="col-xs-12">
                                          <center>
                                            <h5 class="txtLogo">+</h5>
                                            <h4 class="txtNuevo">AGREGAR NUEVO PREMIO</h4>
                                          </center>
                                        </div>
                                  </div>
                              </div>
                            </a>
                           </div>
                          </div>
                      </div>


                      <!-- BOTÓN AGREGAR CANON -->
                      <!--
                      <div class="col-lg-6 col-xl-2">
                        <div class="row">
                          <div class="col-md-12">
                            <a href="" id="btn-nuevo-canon" style="text-decoration: none;">
                                <div class="panel panel-default panelBotonNuevo">
                                  <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                                    <div class="backgroundNuevo"></div>
                                      <div class="row">
                                        <div class="col-xs-12">
                                          <center>
                                            <h5 class="txtLogo">+</h5>
                                            <h4 class="txtNuevo">AGREGAR CANON</h4>
                                          </center>
                                        </div>
                                  </div>
                              </div>
                            </a>
                           </div>
                          </div>
                      </div>
                      -->


            </div> <!--/columna row -->


    <!-- Modal AGREGAR/editar PREMIO -->
    <div class="modal fade" id="modalNuevoPremio" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog ">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| AGREGAR NUEVO PREMIO</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmNuevoPremio" name="frmNuevoPremio" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                          <div class="col-lg-12">
                            <div id="columna" class="row">
                              <div id="terminoNuevoPremio" class="row" style="margin-bottom: 15px;">

                                <div class="col-lg-12">
                                  <h5>NOMBRE DEL PREMIO</h5>
                                  <input type="text" id="nombre_premio" name="nombre_premio" class="form-control" style="padding: 0px!important;">
                                </div>

                                <div class="col-lg-12">
                                  <h5>PORCENTAJE</h5>
                                  <input id="porcentaje_premio" name="porcentaje_premio" type="text" class="form-control"  style="padding: 0!important;" placeholder="" value="" required>
                                </div>
                              <div class="col-lg-12">
                                  <h5>BOLA TOPE</h5>
                                  <input id="bola_tope" name="bola_tope" type="text" class="form-control"  placeholder="" value="" required>
                                </div>

                                <div class="col-lg-12">
                                  <h5>CASINO</h5>
                                  <select id="casino_premio" class="form-control selectCasinos" name="">
                                      @foreach($casinos as $casino)
                                      <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                      @endforeach
                                  </select>
                                </div>
                                <div class="col-lg-12">
                                  <h5>TIPO PREMIO</h5>
                                  <select id="tipo_premio" class="form-control selectCasinos" name="">
                                      <option value="">Seleccione Valor</option>
                                      <option value="1">Normal</option>
                                      <option value="2">Especial</option>
                                  </select>
                                </div>
                              </div>

                          </div>
                          <span id="alerta_sesion" class="alertaSpan"></span>


                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar-premio" value="nuevo">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_premio" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal AGREGAR/editar CANON -->
    <div class="modal fade" id="modalCanon" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog ">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| AGREGAR CANON</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmCanon" name="frmCanon" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                          <div class="col-lg-12">
                            <div id="columna2" class="row">
                              <div id="terminoCanon" class="row" style="margin-bottom: 15px;">

                                <div class="col-lg-12">
                                  <h5>FECHA DE INICIO</h5>
                                  <input type="date" id="fecha_inicio" name="nombre_premio" class="form-control" style="padding: 0px!important;">
                                </div>

                                <div class="col-lg-12">
                                  <h5>PORCENTAJE</h5>
                                  <input id="porcentaje_canon" name="porcentaje_canon" type="text" class="form-control"  style="padding: 0!important;" placeholder="" value="" required>
                                </div>
                                <div class="col-lg-12">
                                  <h5>CASINO</h5>
                                  <select id="casino_canon" class="form-control selectCasinos" name="">
                                      @foreach($casinos as $casino)
                                      <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                      @endforeach
                                  </select>
                                </div>
                              </div>

                          </div>
                          <span id="alerta_sesion" class="alertaSpan"></span>


                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar-canon" value="nuevo">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_canon" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>
    <!-- Modal Eliminar Premio-->
    <div class="modal fade" id="modalEliminarPremio" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-title" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeEliminarPremio"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-danger" id="btn-eliminar-premio" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>

    <!-- Modal Eliminar canon-->
    <div class="modal fade" id="modalEliminarCanon" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-title" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeEliminarCanon"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-danger" id="btn-eliminar-canon" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>



    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title2" style="color: #fff;">| GESTION DE SESIONES</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Gestion de Sesiones</h5>
      <p>
        Creación de nuevos premios y canones de bingo.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="/js/Bingo/gestion.js?1" charset="utf-8"></script>
    <script src="/js/Bingo/lista-datos.js" type="text/javascript"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
    @endsection
