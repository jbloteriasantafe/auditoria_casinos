@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
?>
@section('estilos')
  <link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
  <link rel="stylesheet" href="css/paginacion.css">
@endsection

@section('contenidoVista')

              <div class="row"> <!-- Tarjeta de FILTROS -->
                <div class="col-lg-12 col-xl-9">

              <div class="row">
                <div class="col-md-12">
                  <div class="panel panel-default">
                    <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                      <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                    </div>
                    <div id="collapseFiltros" class="panel-collapse collapse">
                      <div class="panel-body">
                        <div class="row"> <!-- Primera fila -->
                          <div class="col-lg-3">
                            <h5>NRO ADMIN</h5>
                            <input id="nro_admin" type="text" placeholder="Número de Admin" class="form-control" maxlength="100">
                          </div>
                          <div class="col-lg-3">
                            <h5>Casino</h5>
                            <select class="form-control" id="selectCasinos">
                              <option value="0">- Seleccione un casino -</option>
                               @foreach ($casinos as $casino)
                               <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                               @endforeach

                            </select>
                          </div>
                          <div class="col-lg-3">
                            <h5>Sector</h5>
                            <select class="form-control" id="selectSector">

                            </select>
                          </div>
                          <div class="col-lg-3">
                            <h5>Isla</h5>
                            <input id="nro_isla" type="text" placeholder="Número de Isla" class="form-control" maxlength="45">
                          </div>
                        </div> <!-- / Primera fila -->
                        <br>
                        <div class="row"> <!-- Segunda fila -->
                          <div class="col-lg-3">
                            <h5>Fecha de inicio</h5>

                            <div class="form-group">
                               <div class='input-group date' id='dtpFechaInicio' data-link-field="fecha_inicio" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                   <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio"/>
                                   <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                   <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                               </div>
                               <input class="form-control" type="hidden" id="fecha_inicio" value=""/>
                            </div>

                            <!-- viejo -->
                            <!-- <div class='input-group date' id='dtpFechaInicio' data-link-field="fecha_inicio" data-link-format="yyyy-mm-dd">
                              <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio"/>
                              <span class="input-group-addon">
                                <span class="glyphicon glyphicon-calendar"></span>
                              </span>
                              <input type="hidden" id="fecha_inicio" value=""/>
                            </div>-->
                          </div>
                          <div class="col-lg-3">
                            <h5>Fecha de finalización</h5>

                            <div class="form-group">
                               <div class='input-group date' id='dtpFechaFin' data-link-field="fecha_fin" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                   <input type='text' class="form-control" placeholder="Fecha de Fin" id="B_fecha_fin"/>
                                   <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                   <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                               </div>
                               <input class="form-control" type="hidden" id="fecha_fin" value=""/>
                            </div>

                            <!-- <div class='input-group date' id='dtpFechaFin' data-link-field="fecha_fin" data-link-format="yyyy-mm-dd">
                              <input type='text' class="form-control" placeholder="Fecha de Fin" id="B_fecha_fin"/>
                              <span class="input-group-addon">
                                <span class="glyphicon glyphicon-calendar"></span>
                              </span>
                              <input type="hidden" id="fecha_fin" value=""/>
                            </div> -->
                          </div>

                          <div class="col-lg-3">
                              <h5 style="color:#f5f5f5">Búsqueda</h5>
                              <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                          </div>
                        </div> <!-- / Segunda fila -->
                        <br>

                      </div>
                    </div>
                  </div>
              </div>
            </div> <!-- / Tarjeta FILTROS -->

            <div class="row"> <!-- Tarjeta TABLA registros MTM -->
              <div class="col-md-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h4>ÚLTIMOS REGISTROS DE MÁQUINAS A PEDIDO</h4>
                  </div>
                  <div class="panel-body">
                    <table id="tablaResultados" class="table table-fixed tablesorter">
                      <thead>
                        <tr>
                          <th class="col-xs-2" value="maquina.nro_admin" estado="">NRO ADMIN  <i class="fa fa-sort"></i></th>
                          <th class="col-xs-2" value="maquina_a_pedido.fecha" estado="">FECHA  <i class="fa fa-sort"></i></th>
                          <th class="col-xs-2" value="casino.nombre" estado="">CASINO  <i class="fa fa-sort"></i></th>
                          <th class="col-xs-2" value="sector.descripcion" estado="">SECTOR  <i class="fa fa-sort"></i></th>
                          <th class="col-xs-2" value="isla.nro_isla" estado="">ISLA  <i class="fa fa-sort"></i></th>
                          <th class="col-xs-2">ACCIONES</th>
                        </tr>
                      </thead>
                      <!-- <tbody id="cuerpoTabla" style="height: 420px;"> -->
                        <tbody id="cuerpoTabla" style="height: 260px;">


                        </tbody>
                    </table>
                    <!--Comienzo indices paginacion-->
                    <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div> <!-- / Tarjeta TABLA -->

          <div class="col-lg-12 col-xl-3">
          <!-- columna AGREGAR juego-->
          <div class="row">
            <div class="col-lg-12">
             <a href="" id="btn-nuevo" style="text-decoration: none;">
              <div class="panel panel-default panelBotonNuevo">
                  <center><img class="imgNuevo" src="/img/logos/maquinas_a_pedido_white.png"><center>
                  <div class="backgroundNuevo"></div>
                  <div class="row">
                      <div class="col-xs-12">
                        <center>
                            <h5 class="txtLogo">+</h5>
                            <h4 class="txtNuevo">NUEVO REGISTRO DE MTM A PEDIR</h4>
                        </center>
                      </div>
                  </div>
              </div>
             </a>
            </div>
          </div>
          </div>
        </div>
        <!-- /#row -->

    <!-- Modal Casino -->
    <div class="modal fade" id="modalMTM_a_pedir" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| NUEVO PEDIDO A MTM</h3>
                </div>

                <div  id="colapsado" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmMTM_a_pedido" name="frmMTM_a_pedido" class="form-horizontal" novalidate="">
                          <div class="row">
                            <div class="col-md-6">
                              <h5>NRO ADMIN</h5>
                              <input id="nro_admin_m" data-fisca="" class="form-control" size="100" type="text" name="nro_admin_m"  placeholder="Número Admin"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" />
                              <!-- <input type="text" class="form-control" id="nro_admin_m" name="nro_admin_m" placeholder="Número Admin" value=""> -->
                              <br><span id="alertaNro_Admin" class="alertaSpan"></span>
                            </div>
                            <div class="col-md-6">
                              <h5>CASINO</h5>

                              <select class="form-control" id="selectCasinos_m" data-content='Valor del campo <strong>incorrecto</strong>' data-trigger="manual" data-toggle="popover" data-placement="top">
                                @if($casinos->count() == 1)
                                <option id="{{$casinos[0]->id_casino}}" value="{{$casinos[0]->id_casino}}">{{$casinos[0]->nombre}}</option>
                                @else
                                <option value="0">- Seleccione un casino -</option>
                                 @foreach ($casinos as $casino)
                                 <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                 @endforeach
                                 @endif
                                </select>
                              <br> <span id="alertaCasino" class="alertaSpan"></span>
                            </div>
                            <div class="col-md-6">
                              <h5>Fecha de inicio</h5>
                              <div class='input-group date' id='dtpFechaInicio_m' data-link-field="fecha_inicio_m" data-link-format="yyyy-mm">
                                <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio_m" data-content='Valor del campo <strong>incorrecto</strong>' data-trigger="manual" data-toggle="popover" data-placement="top"/>
                                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                <input type="hidden" id="fecha_inicio_m" value=""/>
                              </div>
                            </div>

                            <!-- <div class="form-group">
                               <div class='input-group date' id='dtpFechaFin' data-link-field="fecha_fin" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                   <input type='text' class="form-control" placeholder="Fecha de Fin" id="B_fecha_fin"/>
                                   <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                   <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                               </div>
                               <input class="form-control" type="hidden" id="fecha_fin" value=""/>
                            </div> -->


                            <div class="col-md-6">
                              <h5>Fecha de finalización</h5>
                              <div class='input-group date' id='dtpFechaFin_m' data-link-field="fecha_fin_m" data-link-format="yyyy-mm">
                                <input type='text' class="form-control" placeholder="Fecha de Fin" id="B_fecha_fin_m"  data-content='No debe ser <strong>menor</strong> a fecha inicio' data-trigger="manual" data-toggle="popover" data-placement="top"/>
                                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                <input type="hidden" id="fecha_fin_m" value=""/>
                              </div>
                            </div>
                          </div>
                  </form>
                </div>
                <br>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <input type="hidden" id="id_registro_MTM" name="id_registro_MTM" value="0">
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
                  <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
                </div>

                <div class="modal-body franjaRojaModal">
                  <form id="frmEliminar" name="frmMTM_a_pedido" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Seguro desea eliminar el Pedido?</strong>
                          </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarModal" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| MTM A PEDIDO</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Mtm a pedido</h5>
      <p>
        Sección que propone máquinas a relevar, aquellas que no fueron asignadas aleatoriamente dentro del mes
        y fueron identificadas para su obtención de datos.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="js/seccionMtmAPedido.js?1" charset="utf-8"></script>

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    @endsection
