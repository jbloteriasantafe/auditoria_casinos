  @extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use App\Bingo\PremioBingo;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];;
$casinos = $usuario->casinos;
//Supongo que el $casinos[0] esta porque se trae el unico casino que le corresponde a una fisca. Para un superusuario,
//trae los premios de Melincue porque es el primero de los 3 casinos.
$premios = PremioBingo::where('id_casino','=',$casinos[0]->id_casino)->get()->all();
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
                                       <div class='input-group date' id='dtpBuscadorFecha' data-link-field="buscadorFecha" data-link-format="yyyy-mm-dd">
                                           <input type='text' class="form-control" placeholder="Fecha de relevamiento" id="B_fecharelevamiento"/>
                                           <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                           <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                       </div>
                                       <input class="form-control" type="hidden" id="buscadorFecha" value=""/>
                                    <!-- </div> -->
                                  </div>

                                  <!-- <div class="col-md-3">
                                    <h5>Fecha de Sesión</h5>
                                    <input type="date" id="buscadorFecha" class="form-control" style="padding: 0px!important;">
                                  </div> -->
                                  <div class="col-md-3">
                                    <h5>Estado de la Sesión</h5>
                                    <select id="buscadorEstado" class="form-control" name="">
                                        <option value="0">-Todos los Estados-</option>
                                        <option value="1">ABIERTA</option>
                                        <option value="2">CERRADA</option>
                                    </select>
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Casino</h5>
                                    <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                        <option value="0">-Todos los Casinos-</option>
                                        @foreach($casinos as $casino)
                                        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                        @endforeach
                                    </select>
                                  </div>
                                  <div class="col-md-3">
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
                                <h4>TODAS LAS SESIONES</h4>
                            </div>
                            <div class="panel-body modal-cuerpo">
                              <table id="tablaResultados" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-2" value="fecha_inicio" estado="">FECHA  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" value="hora_inicio" estado="">HORA INICIO <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" value="id_casino" estado="">CASINO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="id_usuario_inicio" estado="">INICIÓ USUARIO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" value="hora_fin" estado="">HORA FINAL  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="id_usuario_fin" estado="">CERRÓ USUARIO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" value="id_estado" estado="">ESTADO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2">ACCIONES</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTabla" style="height: 370px;">


                                </tbody>
                              </table>
                              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                              </div>
                            </div>
                          </div>



                        </div> <!-- Fin del col de los filtros -->

                      </div> <!-- Fin del row de la tabla -->

                      <div class="col-lg-4 col-xl-3"><!-- BOTÓN NUEVA SESIÓN -->
                        <div class="row">
                          <div class="col-md-12">
                            <a href="" id="btn-nuevo" style="text-decoration: none;">
                                <div class="panel panel-default panelBotonNuevo">
                                  <center><img class="imgNuevo" src="/img/logos/informes_bingo_white.png"><center>
                                    <div class="backgroundNuevo"></div>
                                      <div class="row">
                                        <div class="col-xs-12">
                                          <center>
                                            <h5 class="txtLogo">+</h5>
                                            <h4 class="txtNuevo">NUEVA SESIÓN</h4>
                                          </center>
                                        </div>
                                  </div>
                              </div>
                            </a>
                           </div>
                          </div>
                      </div>


                      <div class="col-lg-4 col-xl-3"><!-- BOTÓN GENERAR FORMULARIO SESIÓN -->
                        <div class="row">
                          <div class="col-md-12">
                            <a href="" id="btn-planilla-sesion" style="text-decoration: none;">
                                <div class="panel panel-default panelBotonNuevo">
                                  <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                                    <div class="backgroundNuevo"></div>
                                      <div class="row">
                                        <div class="col-xs-12">
                                          <center>
                                            <h5 class="txtLogo">+</h5>
                                            <h4 class="txtNuevo">Formulario Sesión</h4>
                                          </center>
                                        </div>
                                  </div>
                              </div>
                            </a>
                           </div>
                          </div>
                      </div>


                      <div class="col-lg-4 col-xl-3"><!-- BOTÓN GENERAR FORMULARIO RELEVAMIENTO -->
                        <div class="row">
                          <div class="col-md-12">
                            <a href="" id="btn-planilla-relevamiento" style="text-decoration: none;">
                                <div class="panel panel-default panelBotonNuevo">
                                  <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                                    <div class="backgroundNuevo"></div>
                                      <div class="row">
                                        <div class="col-xs-12">
                                          <center>
                                            <h5 class="txtLogo">+</h5>
                                            <h4 class="txtNuevo">Formulario Relevamiento</h4>
                                          </center>
                                        </div>
                                  </div>
                              </div>
                            </a>
                           </div>
                          </div>
                      </div>


            </div> <!--/columna row -->


    <!-- Modal NUEVA SESIÓN -->
    <div class="modal fade" id="modalFormula" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| NUEVA SESIÓN</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmFormula" name="frmFormula" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                          <div class="col-lg-12">
                            <div id="columna" class="row">
                              <div id="terminoFormula" class="row" style="margin-bottom: 15px;">

                                <div class="col-lg-4">
                                  <h5>FECHA INICIO</h5>
                                  <div class='input-group date' id='dtpFechaSesion' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                                      <input type='text' class="form-control" placeholder="Fecha de sesión" id="fechaInicioNueva" autocomplete="off" style="background-color: rgb(255,255,255);" />
                                      <span id="input-times" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                      <span id="input-calendar" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                  </div>

                                  <!-- <input type="date" id="fechaInicioNueva" class="form-control" style="padding: 0px!important;"> -->
                                </div>

                                <div class="col-lg-4">
                                  <h5>HORA INICIO</h5>
                                  <div class='input-group date' id='dtpHoraSesion' data-date-format="HH:ii:ss" data-link-format="HH:ii">
                                      <input type='text' class="form-control" placeholder="Hora de sesión" id="horaInicioNueva" autocomplete="off" style="background-color: rgb(255,255,255);" />
                                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                  </div>


                                  <!-- <input id="horaInicioNueva" name="horaInicioNueva" type="time" class="form-control"  style="padding: 0!important;" placeholder="" value="" required> -->
                                </div>

                                <div class="col-lg-4">
                                  <h5>CASINO</h5>
                                  <select id="casino_nueva" class="form-control selectCasinos" name="">
                                      @foreach($casinos as $casino)
                                      <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                      @endforeach
                                  </select>
                                </div>

                                <div class="col-lg-6">
                                  <h5>POZO DOTACIÓN INICIAL</h5>
                                  <input id="pozo_dotacion_inicial" name="pozo_dotacion_inicial" type="text" class="form-control"  placeholder="" value="" required>
                                </div>

                                <div class="col-lg-6">
                                  <h5>POZO EXTRA INICIAL</h5>
                                  <input id="pozo_extra_inicial" name="pozo_extra_inicial" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>VALOR DEL CARTON</h5>
                                  <input id="valor_carton" name="valor_carton" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>SERIE INICIAL</h5>
                                  <input id="serie_inicial" name="serie_inicial" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>CARTON INICIAL</h5>
                                  <input id="carton_inicial" name="carton_inicial" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3 text-center">
                                 <h5>-</h5>
                                 <button id="btn-agregarTermino" class="btn btn-success btn-xs" type="button"><i class="fa fa-fw fa-plus"></i> Mas filas</button>
                               </div>

                              </div>

                          </div>
                          <span id="alerta_sesion" class="alertaSpan"></span>


                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_sesion" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal CERRAR SESIÓN -->
    <div class="modal fade" id="modalCierreSesion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| CERRAR SESIÓN</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmCierreSesion" name="frmCierreSesion" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                          <div class="col-lg-12">
                            <div id="columna2" class="row">
                              <div id="terminoCierreSesion" class="row" style="margin-bottom: 15px;">

                                <div class="col-lg-4">
                                  <h5>FECHA CIERRE</h5>
                                  <div class='input-group date' id='dtpFechaCierreSesion' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                                      <input type='text' class="form-control" placeholder="Fecha de sesión" id="fechaCierreSesion" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                  </div>

                                  <!-- <input type="date" id="fechaCierreSesion" class="form-control" style="padding: 0px!important;"> -->
                                </div>

                                <div class="col-lg-4">
                                  <h5>HORA CIERRE</h5>
                                  <div class='input-group date' id='dtpHoraCierreSesion' data-date-format="HH:ii:ss" data-link-format="HH:ii">
                                      <input type='text' class="form-control" placeholder="Hora de sesión" id="horaCierreSesion" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                  </div>

                                  <!-- <input id="horaCierreSesion" name="horaInicioNueva" type="time" class="form-control"  style="padding: 0!important;" placeholder="" value="" required> -->
                                </div>

                                <div class="col-lg-4">
                                  <h5>CASINO</h5>
                                  <select id="casino_cierre" class="form-control selectCasinos" name="" disabled="">
                                      @foreach($casinos as $casino)
                                      <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                      @endforeach
                                  </select>
                                </div>


                                <div class="col-lg-6">
                                  <h5>POZO DOTACIÓN FINAL</h5>
                                  <input id="pozo_dotacion_final" name="pozo_dotacion_final" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-6">
                                  <h5>POZO EXTRA FINAL</h5>
                                  <input id="pozo_extra_final" name="pozo_extra_final" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>VALOR DEL CARTON</h5>
                                  <input id="valor_carton_f" name="valor_carton_f" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>SERIE FINAL</h5>
                                  <input id="serie_final" name="serie_final" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>CARTON FINAL</h5>
                                  <input id="carton_final" name="carton_final" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3 text-center">
                                 <h5>-</h5>
                                 <button id="btn-agregarTerminoFinal" class="btn btn-success btn-xs" type="button"><i class="fa fa-fw fa-plus"></i> Mas filas</button>
                               </div>

                              </div>

                          </div>
                          <span id="alerta_sesion" class="alertaSpan"></span>


                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar-cierre">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_sesion" value="0">
                  <input type="hidden" id="cantidad_detalles" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal CARGAR PARTIDA/RELEVAMIENTO -->
    <div class="modal fade" id="modalRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| CARGAR RELEVAMIENTO</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmRelevamiento" name="frmCierreSesion" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                          <div class="col-lg-12">
                            <div id="columnaRelevamiento" class="row">
                              <div id="terminoRelevamiento" class="row" style="margin-bottom: 15px;">


                                <div class="col-lg-4">
                                  <h5>NRO. DE PARTIDA</h5>
                                  <input id="nro_partida" name="nro_partida" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>HORA DE JUGADA</h5>
                                  <div class='input-group date' id='dtpHoraJugada' data-date-format="HH:ii" data-link-format="HH:ii">
                                      <input type='text' class="form-control" placeholder="Hora de partida" id="hora_jugada" autocomplete="off" style="background-color: rgb(255,255,255);" />
                                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                  </div>
                                  <!-- <input id="hora_jugada" name="hora_jugada" type="time" class="form-control"  placeholder="" value=""> -->
                                </div>

                                <div class="col-lg-4">
                                  <h5>VALOR DEL CARTON</h5>
                                  <input id="valor_carton_rel" name="valor_carton_rel" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>SERIE INICIAL</h5>
                                  <input id="serie_inicio" name="serie_inicio" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>CARTON INICIAL</h5>
                                  <input id="carton_inicio_i" name="carton_inicio_i" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>CARTON FINAL</h5>
                                  <input id="carton_fin_i" name="carton_fin_i" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>SERIE FINAL</h5>
                                  <input id="serie_fin" name="serie_fin" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>CARTON INICIAL</h5>
                                  <input id="carton_inicio_f" name="carton_inicio_f" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>CARTON FINAL</h5>
                                  <input id="carton_fin_f" name="carton_fin_f" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="row">
                                <div class="col-lg-4">
                                  <h5>CARTONES VENDIDOS</h5>
                                  <input id="cartones_vendidos" name="cartones_vendidos" type="text" class="form-control"  placeholder="" value="">
                                </div>
                              </div>

                                <div class="row">
                                  <div class="col-lg-4">
                                    <h5>PREMIO LINEA</h5>
                                    <input id="premio_linea" name="premio_linea" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                  <div class="col-lg-4">
                                    <h5>PREMIO BINGO</h5>
                                    <input id="premio_bingo" name="premio_bingo" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                </div>

                                <div class="row">
                                  <div class="col-lg-4">
                                    <h5>MAXI LINEA</h5>
                                    <input id="maxi_linea" name="maxi_linea" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                  <div class="col-lg-4">
                                    <h5>MAXI BINGO</h5>
                                    <input id="maxi_bingo" name="maxi_bingo" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                </div>

                                <div class="row">
                                  <div class="col-lg-4">
                                    <h5>POSICIÓN BOLA LINEA</h5>
                                    <input id="pos_bola_linea" name="pos_bola_linea" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                  <div class="col-lg-4">
                                    <h5>POSICIÓN BOLA BINGO</h5>
                                    <input id="pos_bola_bingo" name="pos_bola_bingo" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                </div>

                                <div class="row">
                                  <div class="col-lg-4">
                                    <h5>NOMBRE DEL PREMIO</h5>
                                    <select class="form-control" id="nombre_premio">
                                      <option value="" selected="">Seleccionar Valor</option>
                                      @foreach ($premios as $premio)
                                              <option value="{{$premio->id_premio}}">{{$premio->nombre_premio}}</option>

                                     @endforeach
                                    </select>
                                  </div>
                                  <div class="col-lg-4">
                                    <h5>NÚMERO CARTÓN GANADOR</h5>
                                    <input id="carton_ganador" name="carton_ganador" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                  <div class="col-lg-4 text-center">
                                   <h5>-</h5>
                                   <button id="btn-agregarTerminoRelevamiento" class="btn btn-success btn-xs" type="button"><i class="fa fa-fw fa-plus"></i> Mas filas</button>
                                 </div>

                          </div>



                              </div>

                          </div>
                          <span id="alerta_sesion" class="alertaSpan"></span>


                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar-relevamiento">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_sesion" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal DETALLES + RELEVAMIENTOS -->
    <div class="modal fade" id="modalDetallesRel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" style="min-width:85%;">
             <div class="modal-content">
                <div class="modal-header pbzero">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title pbtitle" id="myModalLabel">| DETALLES SESIÓN</h3>

                  <ul class="nav nav-tabs bbnav">
                    <li class="active"><a data-toggle="tab" href="#detalles">DETALLES</a></li>
                    <li><a data-toggle="tab" href="#historialCambios">HISTORIAL DE CAMBIOS</a></li>
                  </ul>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmDetallesRel" name="frmDetallesRel" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                        <div class="tab-content">
                          <div class="col-lg-12 tab-pane fade in active" id="detalles">
                            <div id="columnaDetallesRel" class="row">
                              <div id="terminoDetallesRel" class="row" style="margin-bottom: 15px;">
                                <div class="col-lg-12">
                                <h6>DATOS DE LA SESIÓN</h6>
                                </div>

                                <div class="col-lg-3">
                                  <h5>POZO DOTACIÓN INICIAL</h5>
                                  <input id="pozo_dotacion_inicial_d" name="pozo_dotacion_inicial_d" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>POZO EXTRA FINAL</h5>
                                  <input id="pozo_extra_inicial_d" name="pozo_extra_inicial_d" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>POZO DOTACIÓN FINAL</h5>
                                  <input id="pozo_dotacion_final_d" name="pozo_dotacion_final_d" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>POZO EXTRA FINAL</h5>
                                  <input id="pozo_extra_final_d" name="pozo_extra_final_d" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <!-- datos de detalles -->
                              <div class="row">
                                <div class="col-lg-12">
                                <h6>DETALLES DE LA SESIÓN</h6>
                                </div>
                                <div class="col-lg-2">
                                  <h5>VALOR CARTON</h5>
                                </div>
                                <div class="col-lg-2">
                                  <h5>SERIE INICIAL</h5>
                                </div>
                                <div class="col-lg-2">
                                  <h5>CARTON INICIAL</h5>
                                </div>
                                <div class="col-lg-2">
                                  <h5>SERIE FINAL</h5>
                                </div>
                                <div class="col-lg-2">
                                  <h5>CARTON FINAL</h5>
                                </div>
                              </div>

                              <div id="terminoDatos2" class="row" style="margin-bottom: 15px;">
                              </div>
                              <!-- fin datos de detalles -->
                                </div>
                                <div class="col-lg-12">
                                <h6>RELEVAMIENTOS CARGADOS</h6>
                              </div>
                          </div>
                          <span id="alerta_sesion" class="alertaSpan"></span>

                          <div class="panel-body modal-cuerpo">
                            <table id="tablaResultadosRel" class="table table-striped">
                              <thead>
                                <tr>
                                  <th class="col" value="nro_partida_r">Nro. PARTIDA</th>
                                  <th class="col" value="hora_sesion_r">HORA SESION</th>
                                  <th class="col" value="serie_inicial_r">SERIE INICIAL</th>
                                  <th class="col" value="carton_inicial_r">CARTON INICIAL</th>
                                  <th class="col" value="carton_final_r">CARTON FINAL</th>
                                  <th class="col" value="serie_final_r">SERIE FINAL</th>
                                  <th class="col" value="carton_inicial_rr">CARTON INICIAL</th>
                                  <th class="col" value="carton_final_rr">CARTON FINAL</th>
                                  <th class="col" value="cartones_vendidos_r">CARTONES VENDIDOS</th>
                                  <th class="col" value="valor_carton_r">VALOR CARTON</th>
                                  <th class="col" value="bola_linea_r">BOLA LÍNEA</th>
                                  <th class="col" value="bola_bingo_r">BOLA BINGO</th>
                                  <th class="col" value="premio_bingo_r">PREMIO LÍNEA</th>
                                  <th class="col" value="premio_bingo_r">PREMIO BINGO</th>
                                  <th class="col" value="pozo_dot_r">POZO DOT.</th>
                                  <th class="col" value="pozo_extra_r">POZO EXTRA</th>
                                  <th class="col" value="usuario_r">USUARIO</th>
                                  <th class="col" id="accionesResultadoRel">ACCIONES</th>
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
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeEliminar"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarSesion" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="cantidad_partidas" value="0">
                </div>
            </div>
          </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminarPartida" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleEliminarPartida" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeEliminarPartida"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarPartida" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>

    <!-- Modal reAbrirSesion -->
    <!-- <div class="modal fade" id="modalAbrirSesion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleAbrirSesion" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeAbrirSesion"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerAbrirSesion" id="btn-abrirSesion" value="0">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                </div>
            </div>
          </div>
    </div> -->


    <!-- Modal reabrir sesión -->
    <div class="modal fade" id="modalAbrirSesion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">

        <div class="modal-dialog">
           <div class="modal-content">

             <div class="modal-header" style="background: #d9534f; color: #E53935;">
               <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                 <h3 class="modal-title" style="color:#000000;">ADVERTENCIA</h3>
             </div>

                  <div class="modal-body" style="color:#fff; background-color:#FFFFF;">
              <form id="frmMotivos">

                      <h6 style="color:#000000 !important; font-size:14px;"></h6>
                      <br>
                      <h6 id="mensajeAbrirSesion" style="color:#000000"></h6>
                      <div id="campo-valor">
                        <input placeholder="" id="motivo-reapertura" type="text" class="form-control">
                      </div>
              </form>
                    </div>
            <br>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" id="btn-abrirSesion">ACEPTAR</button>
              <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal">CANCELAR</button>
              <!-- <button type="button" class="btn btn-dangerAbrirSesion" id="btn-abrirSesion" value="0">ACEPTAR</button>
              <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button> -->
            </div>
        </div>
      </div>
</div>

    <!-- Modal ERRORES / ADVERTENCIAS -->
    <div class="modal fade" id="modalCorrecta" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-title-correcta" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeCorrecta"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">ACEPTAR</button>
                </div>
            </div>
          </div>
    </div>


    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title2" style="color: #fff;">| SESIONES</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Sesiones</h5>
      <p>
        Apertura de nuevas sesiones de bingo, cierre y reapertura. Carga de relevamientos de partida para una sesión de bingo.
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="/js/Bingo/sesion.js" charset="utf-8"></script>
    <script src="/js/Bingo/lista-datos.js" type="text/javascript"></script>


    <!-- Custom input Bootstrap -->
    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>


    @endsection
