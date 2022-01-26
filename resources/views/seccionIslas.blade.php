@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="/css/paginacion.css">
  <link rel="stylesheet" href="/css/lista-datos.css">

@endsection

@section('contenidoVista')

        <div class="row">
            <div class="col-lg-12 col-xl-9">
                <!-- FILTROS -->
                <div class="row">
                    <div class="col-md-12">
                        <div id="contenedorFiltros" class="panel panel-default">
                          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                            <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                          </div>
                          <div id="collapseFiltros" class="panel-collapse collapse">
                            <div class="panel-body">
                              <div class="row">
                                <div class="col-md-2">
                                  <h5>Nro de isla</h5>
                                  <input id="buscadorNroIsla" class="form-control" placeholder="Nro. de isla">
                                </div>
                                <div class="col-md-2">
                                  <h5>Cant. de máquinas</h5>
                                  <input id="buscadorCantMaquinas" class="form-control" placeholder="Cantidad de máquinas">
                                </div>
                                <div class="col-md-3">
                                    <h5>Casino</h5>
                                    <select id="buscadorCasino" class="form-control" name="">
                                        <option value="0">-Seleccione un Casino-</option>
                                         @foreach ($casinos as $casino)
                                         <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                         @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <h5>Sector</h5>
                                    <select id="buscadorSector" class="form-control" name="">
                                        <option value="0">-Todos los sectores-</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                  <h5 style="color:#ffffff;">boton buscar</h5>
                                  <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                </div>
                              </div>
                              <br>
                            </div>
                          </div>
                        </div>
                    </div>
                </div>

                <!-- TABLA -->
                <div class="row">
                    <div class="col-md-12">
                      <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>CANTIDAD TOTAL DE ISLAS</h4>
                        </div>
                        <div class="panel-body">
                          <table id="tablaResultados" class="table table-fixed tablesorter">
                            <thead>
                              <tr>
                                <th class="col-xs-1 activa" value="isla.nro_isla" estado="asc">N° ISLA  <i class="fas fa-sort-up"></i></th>
                                <th class="col-xs-2" value="isla.codigo" estado="">CÓD SUBISLA  <i class="fas fa-sort"></i></th>
                                <th class="col-xs-2" value="casino.descripcion" estado="">CASINO  <i class="fas fa-sort"></i></th>
                                <th class="col-xs-2" value="sector.descripcion" estado="">SECTOR  <i class="fas fa-sort"></i></th>
                                <th class="col-xs-2" value="cantidad_maquinas" estado="">MÁQUINAS <i class="fas fa-sort"></i></th>
                                <th class="col-xs-3" value="" estado="">ACCIONES</th>
                              </tr>

                            </thead>
                            <tbody id="cuerpoTabla" style="height: 350px;">
                            </tbody>
                          </table>
                          <!--Comienzo indices paginacion-->
                          <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                          </div>
                        </div>
                      </div>
                    </div> <!-- ./TABLA -->
                </div>

            <div class="col-lg-12 col-xl-3">
             <div class="row">
              <div class="col-lg-12">
               <a href="" id="btn-nuevo" style="text-decoration: none;">
                <div class="panel panel-default panelBotonNuevo">
                    <center><img class="imgNuevo" src="/img/logos/islas_white.png"><center>
                    <div class="backgroundNuevo"></div>
                    <div class="row">
                        <div class="col-xs-12">
                          <center>
                              <h5 class="txtLogo">+</h5>
                              <h4 class="txtNuevo">NUEVA ISLA</h4>
                          </center>
                        </div>
                    </div>
                </div>
               </a>
              </div> <!-- fin fila NUEVO USUARIO -->
             </div>
            </div>

        </div> <!-- columna de FILTROS Y TABLA -->


<!-- Modal Isla -->
<div class="modal fade" id="modalIsla" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
         <div class="modal-content">
            <div class="modal-header" style="background: #5cb85c;font-family: Roboto-Black;">
              <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
              <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
              <h3 class="modal-title" style="color: #fff;">| NUEVA ISLA</h3>
            </div>

            <div  id="colapsado" class="collapse in">
              <div class="modal-body modal-cuerpo">

                <form id="frmIsla" name="frmIsla" class="form-horizontal" novalidate="">

                  <div class="row">
                    <div class="col-md-2">
                      <h5>N° Isla</h5>
                      <input id="nro_isla" type="text" class="form-control" placeholder="Nro de Isla">
                      <br>
                      <span id="alerta-nro_isla" class="alertaSpan"></span>
                    </div>
                    <div class="col-md-2">
                      <h5>Código</h5>
                      <input id="ncodigo" type="text" class="form-control" placeholder="Subisla">
                      <br>
                      <span id="alerta-codigo" class="alertaSpan"></span>
                    </div>
                    <div class="col-md-4">
                        <h5>Casino</h5>
                        <select id="casino" class="form-control" name="">
                            <option value="0">-Seleccione un Casino-</option>
                             @foreach ($casinos as $casino)
                             <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                             @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                      <h5>Sector</h5>
                      <select id="sector" class="form-control" name="">
                          <option value="0">-Sector del Casino-</option>
                      </select>
                      <br>
                      <span id="alerta-sector" class="alertaSpan"></span>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-2">
                      <h5>N° Islote</h5>
                      <input id="nro_islote" type="text" class="form-control" placeholder="Nro de Islote"/>
                    </div>
                    <div class="col-md-2">
                      <h5>Orden</h5>
                      <input id="orden" type="text" class="form-control" placeholder="Orden"/>
                    </div>
                  </div>

                  <div class="row">

                      <div class="col-md-6">
                          <h5>Buscar Máquinas <i class="fa fa-fw fa-search"></i></h5>
                              <div class="input-group">
                                  <input id="buscadorMaquina" class="form-control" type="text" value="" autocomplete="off">
                                  <span class="input-group-btn">
                                    <button class="btn btn-default btn-lista-datos agregarMaquina" type="button"><i class="fa fa-plus"></i></button>
                                  </span>
                              </div>

                          <br>

                          <!-- Lista de maquinas agregadas -->
                          <h5>Máquinas agregadas en la isla</h5>
                          <div class="row">
                              <div class="col-xs-2">
                                    <h5>NÚMERO</h5>
                              </div>
                              <div class="col-xs-4">
                                  <h5>MARCA</h5>
                              </div>
                              <div class="col-xs-4">
                                  <h5>MODELO</h5>
                              </div>
                              <div class="col-xs-2">
                              </div>
                              <ul style="margin-left: 15px;" class="col-xs-12" id="listaMaquinas">
                              </ul>
                          </div>
                      </div>
                      <div class="col-md-6 movimientos">
                        <h5>Historial de Movimientos</h5>
                        <br>
                        <div class="columnaMovimientos" style="position:relative;"></div>
                      </div>
                  </div>

                </form>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                <input type="hidden" id="id_isla" value="0">
              </div>

            </div> <!-- /Fin panel minimizable -->

        </div>
      </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header" style="font-family: Roboto-Black; color: #EF5350">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
              <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
            </div>

            <div class="modal-body" style="color:#fff; background-color:#EF5350;">
              <form id="frmEliminar" name="frmProgresivo" class="form-horizontal" novalidate="">
                  <div class="form-group error ">
                      <div class="col-xs-12">
                        <strong>¿Seguro desea eliminar la ISLA?</strong>
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

<div id="modalDividirIsla" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header" style="font-family: Roboto-Black; background: #ff9d2d;">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
              <h3 class="modal-title">DIVIDIR ISLA</h3>
            </div>

            <div class="modal-body">
              <div class="row">
                  <div class="col-md-3">
                      <h5>N° ISLA</h5>
                      <input id="d_nro_isla" class="form-control" type="text" value="" readonly>
                  </div>
                  <div class="col-md-6">
                      <h5>CASINO</h5>
                      <input id="d_casino" class="form-control" type="text" value="" readonly>
                  </div>
                  <div class="col-md-3">
                      <h5>MÁQUINAS</h5>
                      <input id="d_maquinas" class="form-control" type="text" value="" readonly>
                  </div>
              </div>

              <br>

              <div class="row">
                  <div class="col-md-2 col-md-offset-2">
                      <h5>SUBISLA</h5>
                      <select id="selectSubisla" class="form-control" name="">
                      </select>
                  </div>
                  <div class="col-md-6">
                      <h5>AGREGAR MÁQUINA</h5>
                      <div class="input-group lista-datos-group">
                           <input id="inputMaquina" class="form-control " type="text" value="" autocomplete="off" placeholder="Buscar máquina">
                           <span class="input-group-btn">
                             <button id="btn-agregarMaquina" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                           </span>
                     </div>
                  </div>
              </div>

              <br>

              <style media="screen">
                  .btn.mover i {
                      position: relative;
                      top:-1px;
                  }

                  .contenedorSI {
                    border: 3px solid #333;
                    border-radius: 10px;
                    padding: 10px;
                    margin: 10px 0px;
                  }

                  .contenedorSI span {
                    font-family: Roboto-Condensed;
                    font-size: 20px;
                  }

                  .subisla h5 {
                    margin-top: 10px;
                    padding-left: 0px;
                  }

                  .maquinaSI .tdBorrar button {
                    display: none;
                  }

                  .maquinaSI:hover .tdBorrar button {
                    display: inline-block;
                  }
              </style>

              <div class="row">
                  <div class="col-md-12">
                      <i class="fa fa-exclamation" style="margin-left:20px; color:#E65100;"></i>
                      <p style="margin-left:20px;display:inline-block;font-family:Roboto-Regular;font-size:16px;color:#bbb;">Las subislas sin máquinas serán borradas</p>
                  </div>
              </div>

              <div id="subislas" class="row">

                    <div id="moldeSubisla" class="subisla" data-sub="1" hidden>
                      <div class="contenedorSI">
                        <div class="col-md-2" style="margin-bottom:30px;">
                            <span></span>
                        </div>
                        <div class="col-md-4" style="margin-bottom:30px;">
                            <h5>CÓDIGO SUBISLA</h5>
                            <input class="form-control codigo_subisla" type="text" name="">
                        </div>
                        <div class="col-md-6" style="margin-bottom:30px;">
                            <h5>SECTOR</h5>
                            <select class="selectSector form-control" name="">
                            </select>
                        </div>

                        <table class="table" style="margin-top:20px;">
                          <thead>
                              <tr>
                                <th class="col-xs-1"></th>
                                <th class="col-xs-3">N° ADMIN</th>
                                <th class="col-xs-6">MARCA</th>
                                <th class="col-xs-2" style="text-align:center;">MOVER</th>
                              </tr>
                          </thead>
                          <tbody>
                              <tr id="moldeMaquinaSI" class="maquinaSI" data-maquina="0" hidden>
                                <td class="tdBorrar">
                                  <button class="btn btn-danger borrarMaquinaSI" type="button" name="button" value="1">
                                      <i class="fa fa-fw fa-trash"></i>
                                  </button>
                                </td>
                                <td class="nro_admin"></td>
                                <td class="marca_juego">MÁQUINA 1</td>
                                <td style="text-align:center;">
                                    <button class="btn btn-warning mover_izquierda mover" type="button" name="button" value="1">
                                        <i class="fa fa-fw fa-arrow-up"></i>
                                    </button>
                                    <button class="btn btn-warning mover_derecha mover" type="button" name="button" value="1">
                                        <i class="fa fa-fw fa-arrow-down"></i>
                                    </button>
                                </td>
                              </tr>
                          </tbody>
                        </table>

                      </div> <!-- contenedor -->
                    </div> <!-- sub -->
              </div>
            </div>

            <div class="modal-footer">
              <button id="btn-aceptarDividir" type="button" class="btn btn-success btn-warningModificar" value="0">ACEPTAR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
              <input id="cantidad_subislas" type="text" name="" value="0" hidden>
            </div>
        </div>
    </div>
</div>

@endsection

  <!-- Comienza modal de ayuda -->
  @section('tituloDeAyuda')
  <h3 class="modal-title" style="color: #fff;">ISLAS</h3>
  @endsection
  @section('contenidoAyuda')
  <div class="col-md-12">
    <p>
      Definir, modificar o cambiar islas en cada sector del casino, pudiendo asociar MTM en ellas.
    </p>
  </div>
  @endsection
  <!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript paginacion -->
<script src="/js/paginacion.js" charset="utf-8"></script>
<script src="/js/lista-datos.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->
<script src="/js/seccionIslas.js" charset="utf-8"></script>
@endsection
