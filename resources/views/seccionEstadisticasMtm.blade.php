@extends('includes.nuevaBarraNavegacion')
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
?>
@section('estilos')
  <link rel="stylesheet" href="css/bootstrap-datetimepicker.min.css">
  <link rel="stylesheet" href="css/paginacion.css">
@endsection

@section('contenidoVista')

        <header>
          <img class="iconoSeccion" src="/img/logos/estadisticas_blue.png" alt="">
          <h2>ESTADÍSTICAS RELEVAMIENTOS</h2>
        </header>

                <div class="row"> <!-- row principal -->

                    <div class="col-lg-3">
                        <div class="row"> <!-- BUSCAR MÁQUINA -->
                          <div class="col-md-12">

                              <div id="seccionBusquedaPorMaquina" class="panel panel-default">

                                  <div class="panel-heading">
                                    <h4>BUSCAR MÁQUINA</h4>
                                  </div>

                                  <div class="panel-body">
                                    <div class="row">
                                      <div class="col-lg-12">
                                        <h5>CASINO</h5>
                                        <select id="b_casinoMaquina" class="form-control">
                                            <option value="">Todos los casinos</option>
                                            <?php $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario')) ?>
                                             @foreach ($usuario['usuario']->casinos as $casino)
                                             <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                             @endforeach
                                        </select>
                                      </div>
                                    </div><br>

                                    <div class="row">
                                      <div class="col-lg-12">
                                        <h5>NÚMERO ADMIN</h5>
                                          <input id="b_adminMaquina" type="text" class="form-control" value="" placeholder="Nro. admin">
                                        </div>
                                    </div><br>

                                    <div class="row">
                                      <div class="col-lg-12">
                                          <h5>CANTIDAD DE RELEVAMIENTOS</h5>
                                          <input id="b_cantidad_relevamientos" type="text" class="form-control" value="" placeholder="Cantidad de relevamientos">
                                      </div>
                                    </div><br><br>

                                    <div class="row">
                                      <div class="col-md-12">
                                        <button id="btn-buscarMaquina" class="btn btn-infoBuscar" type="button" name="button" data-content='Debe completar <strong>todos</strong> los campos.' data-trigger="manual" data-toggle="popover" data-placement="right" >
                                          <i class="fa fa-fw fa-search" style="margin-right:10px;"></i> BUSCAR MÁQUINA
                                        </button>
                                      </div>
                                    </div><br>

                                  </div> <!-- panel-body -->

                              </div> <!-- panel -->

                          </div>
                        </div> <!-- Tarjeta FILTROS | row -->
                    </div> <!-- columna izquierda | col-lg-3 -->

                    <div class="col-lg-9">
                      <div class="row"> <!-- Tarjeta de FILTROS -->
                        <div class="col-md-12">

                            <div class="panel panel-default">
                              <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                                <h4>BÚSQUEDA DE MÁQUINAS SIN RELEVAMIENTO <i class="fa fa-fw fa-angle-down"></i></h4>
                              </div>
                              <div id="collapseFiltros" class="panel-collapse collapse">
                                <div class="panel-body">

                                  <div class="row">
                                    <div class="col-lg-4">
                                      <h5>Fecha Desde</h5>

                                      <div class="form-group">
                                         <div class='input-group date' id='b_fecha_desde' data-link-field="fecha_desde_date" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                             <input type='text' class="form-control" placeholder="Fecha de Inicio" id="fecha_desde"/>
                                             <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                             <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                         </div>
                                         <input class="form-control" type="hidden" id="fecha_desde_date" value=""/>
                                      </div>
                                      <!--
                                      <div class='input-group date' id='b_fecha_desde' data-link-field="fecha_desde_date" data-link-format="yyyy-mm-dd">
                                        <input id="fecha_desde" type='text' class="form-control" placeholder="Fecha de Inicio" data-content='Este campo es <strong>requerido</strong>.' data-trigger="manual" data-toggle="popover" data-placement="top"/>
                                        <span class="input-group-addon">
                                          <span class="glyphicon glyphicon-calendar"></span>
                                        </span>
                                        <input type="hidden" id="fecha_desde_date" value=""/>
                                      </div> -->
                                    </div>
                                    <div class="col-lg-4">
                                      <h5>Fecha Hasta</h5>

                                      <div class="form-group">
                                         <div class='input-group date' id='b_fecha_hasta' data-link-field="fecha_hasta_date" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                             <input type='text' class="form-control" placeholder="Fecha de Fin" id="fecha_hasta"/>
                                             <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                             <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                         </div>
                                         <input class="form-control" type="hidden" id="fecha_hasta_date" value=""/>
                                      </div>

                                      <!-- <div class='input-group date' id='b_fecha_hasta' data-link-field="fecha_hasta_date" data-link-format="yyyy-mm-dd">
                                        <input id="" type='text' class="form-control" placeholder="Fecha de Inicio"/>
                                        <span class="input-group-addon">
                                          <span class="glyphicon glyphicon-calendar"></span>
                                        </span>
                                        <input type="hidden" id="fecha_hasta_date" value=""/>
                                      </div> -->
                                    </div>
                                    <div class="col-lg-4">
                                      <h5>ISLA</h5>
                                      <input id="b_isla" type="text" class="form-control" value="" placeholder="Isla">
                                    </div>
                                  </div>

                                  <div class="row">
                                    <div class="col-lg-4">
                                      <h5>CASINO</h5>
                                      <select id="b_casino" class="form-control" name="">
                                          <option value="">- Seleccione un casino -</option>
                                          <?php $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario')) ?>
                                           @foreach ($usuario['usuario']->casinos as $casino)
                                           <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                           @endforeach
                                      </select>
                                    </div>
                                    <div class="col-lg-4">
                                      <h5>SECTOR</h5>
                                      <select id="b_sector" class="form-control" name="">
                                        <option value=""></option>
                                      </select>
                                    </div>

                                    <div class="col-lg-4">
                                      <h5 style="color:#f5f5f5;">buqueda</h5>
                                      <!-- <input id="b_isla" type="text" class="form-control" value=""> -->
                                        <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button">
                                          <i class="fa fa-fw fa-search" style="margin-right:10px;"></i> BUSCAR MÁQUINAS
                                        </button>
                                    </div>

                                  </div>

                                  <br>

                                  <!-- <div class="row">
                                    <div class="col-md-12">
                                      <center><button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button></center>
                                    </div>
                                  </div> -->

                                </div>
                              </div>

                            </div>

                        </div>
                      </div> <!-- / Tarjeta FILTROS -->

                      <div class="row"> <!-- Tarjeta TABLA resultados -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                              <h4 id="tituloTabla"></h4>
                            </div>
                            <div class="panel-body">
                              <table id="tablaResultados" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-3" value="casino" estado="">CASINO <i class="fa fa-sort fa-fw"></i></th>
                                    <th class="col-xs-2" value="sector" estado="">SECTOR <i class="fa fa-sort fa-fw"></i></th>
                                    <th class="col-xs-2" value="isla" estado="">ISLA <i class="fa fa-sort fa-fw"></i></th>
                                    <th class="col-xs-2" value="maquina" estado="">NRO ADMIN <i class="fa fa-sort fa-fw"></i></th>
                                    <th class="col-xs-3">ACCIÓN</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTabla">
                                </tbody>
                              </table>
                              <!--Comienzo indices paginacion-->
                              <div id="indicesPaginacion">
                              </div>
                            </div>
                          </div>
                        </div>
                      </div> <!-- / Tarjeta TABLA -->

                    </div> <!-- col-lg-9 -->
                </div>
                <!-- /.row principal -->

    <!-- Modal de pedido de máquina -->
    <div class="modal fade" id="modalPedido" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header" style="font-family: Roboto-Black; background-color: #FFB74D; color: white;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" id="myModalLabel">PEDIR MÁQUINA</h3>
                 </div> <!-- modal-header -->

              <div  id="colapsado" class="collapse in">

                  <div class="modal-body" style="font-family: Roboto; color: #aaa;">
                    <form id="frmPedido" name="frmPedido" class="form-horizontal" novalidate="">

                        <div class="row">
                            <div class="col-md-6">
                                <h5>NÚMERO ADMIN</h5>
                                <input id="nro_admin_pedido" data-maquina="" class="form-control" type="text" name="" value="">
                            </div>
                            <div class="col-md-6">
                                <h5>CASINO</h5>
                                <input id="casino_pedido" data-casino="" class="form-control" type="text" name="" value="">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                              <h5>Fecha de inicio</h5>
                              <div class='input-group date' id='dtpFechaInicio_m' data-link-field="fecha_inicio_m" data-link-format="yyyy-mm-dd">
                                <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio_m" data-content='Valor de campo <strong>incorrecto</strong>.' data-trigger="manual" data-toggle="popover" data-placement="top"/>
                                <span class="input-group-addon">
                                  <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                                <input type="hidden" id="fecha_inicio_m" value=""/>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <h5>Fecha de finalización</h5>
                              <div class='input-group date' id='dtpFechaFin_m' data-link-field="fecha_fin_m" data-link-format="yyyy-mm-dd">
                                <input type='text' class="form-control" placeholder="Fecha de Fin" id="B_fecha_fin_m"/>
                                <span class="input-group-addon">
                                  <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                                <input type="hidden" id="fecha_fin_m" value=""/>
                              </div>
                            </div>
                        </div>
                        <br>

                        <div class="row">
                            <div class="col-md-12">
                                <table id="fechasPedido" class="table">
                                    <thead>
                                      <tr>
                                        <th>FECHAS A PEDIDO DE LA MÁQUINA</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </form>
                  </div> <!-- modal-body -->

                  <div class="modal-footer">
                    <button id="btn-pedido" type="button" class="btn btn-warningModificar">PEDIR</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
                    <input id="id_maquina" hidden type="text" name="" value="0">
                  </div> <!-- modal-footer -->

              </div> <!-- modal colapsado -->
            </div> <!-- modal-content -->
          </div> <!-- modal-dialog -->
    </div> <!-- modal -->

    <!-- Modal detalle de máquina -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:90%;">
             <div class="modal-content">
                 <div class="modal-header" style="font-family: Roboto-Black; background-color: #3D5AFE; color: white;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" id="myModalLabel">DETALLE DE MÁQUINA</h3>
                 </div> <!-- modal-header -->

              <div  id="colapsado" class="collapse in">

                  <div class="modal-body" style="font-family: Roboto; color: #aaa;">
                    <form id="frmDetalle" name="frmDetalle" class="form-horizontal" novalidate="">

                        <div class="row">
                            <div class="col-md-2 col-md-offset-2">
                              <h5>NÚMERO DE ADMIN</h5>
                              <input id="adminDetalle" type="text" class="form-control" value="" readonly>
                            </div>
                            <div class="col-md-2">
                              <h5>CASINO</h5>
                              <input id="casinoDetalle" type="text" class="form-control" value="" readonly>
                            </div>
                            <div class="col-md-2">
                              <h5>SECTOR</h5>
                              <input id="sectorDetalle" type="text" class="form-control" value="" readonly>
                            </div>
                            <div class="col-md-2">
                              <h5>ISLA</h5>
                              <input id="islaDetalle" type="text" class="form-control" value="" readonly>
                            </div>
                        </div>
                        <br>

                        <div class="row">
                          <div class="col-md-12">
                            <table id="tablaRelevamientos" class="table">
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


                    </form>
                  </div> <!-- modal-body -->

                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
                    <input id="id_maquina" hidden type="text" name="" value="0">
                  </div> <!-- modal-footer -->

              </div> <!-- modal colapsado -->
            </div> <!-- modal-content -->
          </div> <!-- modal-dialog -->
    </div> <!-- modal -->

    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="js/seccionEstadisticasRelevamientos.js?2" charset="utf-8"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    @endsection
