@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
  <link rel="stylesheet" href="css/paginacion.css">
  <link rel="stylesheet" href="/css/lista-datos.css">
@endsection

@section('contenidoVista')

              <div class="row">
                  <div class="col-lg-12 col-xl-9">
                    <div class="row"> <!-- fila de FILTROS -->
                        <div class="col-md-12">
                          <div id="contenedorFiltros" class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltros" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">
                                  <div class="col-md-3">
                                    <h5>Nombre del juego</h5>
                                    <input id="buscadorNombre" class="form-control" placeholder="Nombre del juego">
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Código del Juego</h5>
                                    <input id="buscadorCodigoJuego" class="form-control" placeholder="Código del Juego">
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Código de certificado</h5>
                                    <input id="buscadorCodigo" class="form-control" placeholder="Código de identificación">
                                  </div>
                                  <div class="col-md-3">
                                    <h5 style="color:#ffffff;">Buscar</h5>
                                    <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div> <!-- Fin de la fila de FILTROS -->


                      <div class="row">
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>TODOS LOS JUEGOS</h4>
                            </div>
                            <div class="panel-body">
                              <table id="tablaResultados" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-3" value="juego.nombre_juego" estado="">NOMBRE DEL JUEGO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-3" value="juego.cod_identificacion" estado="">CÓDIGO DEL JUEGO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-3" value="juego.cod_identificacion" estado="">CÓDIGO DEL CERTIFICADO  <i class="fa fa-sort"></i></th>
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
                        </div> <!-- Fin del col de los filtros -->

                      </div> <!-- Fin del row de la tabla -->


                <div class="col-lg-12 col-xl-3">
                  <div class="row">
                    <div class="col-lg-12">
                     <a href="" id="btn-nuevo" style="text-decoration: none;">
                      <div class="panel panel-default panelBotonNuevo">
                          <center><img class="imgNuevo" src="/img/logos/juegos_white.png"><center>
                          <div class="backgroundNuevo"></div>
                          <div class="row">
                              <div class="col-xs-12">
                                <center>
                                    <h5 class="txtLogo">+</h5>
                                    <h4 class="txtNuevo">NUEVO JUEGO</h4>
                                </center>
                              </div>
                          </div>
                      </div>
                     </a>
                    </div>
                  </div>
                </div>

          </div> <!--/columna TABLA -->


    <!-- Modal Juego -->
    <div class="modal fade" id="modalJuego" tabindex="-1" data-backdrop="static" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header modalNuevo">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>

                  <h3 class="modal-title"> | NUEVO JUEGO</h3>

                </div>

          <!-- Panel que se minimiza -->
          <div id="colapsado" class="collapse in">

          <div class="modal-body">
            <div id="juegoPlegado" class="row">
                <div class="row">
                  <div class="row" style="padding-bottom: 15px;">
                      <div class="col-md-4">
                          <h5>Nombre Juego</h5>
                          <input id="inputJuego" class="form-control" type="text" autocomplete="off" placeholder="Nombre juego"/>
                      </div>
                      <div class="col-md-4">
                        <h5>Código Juego</h5>
                        <input id="inputCodigoJuego" class="form-control" type="text" autocomplete="off" placeholder="Código Juego" />
                    </div>
                      <div class="col-md-4" id="cod_inp">
                          <h5>Código de Certificado</h5>
                          <input id="inputCodigo" data-codigo="" class="form-control" type="text" readonly="true" />
                      </div>
                  </div>
                  <br>
                  <div class="row" style="padding-bottom: 15px;">
                      <div id="tablas_de_pago" class="col-md-12">
                          <h5 style="display:inline; margin-right:5px;">Tablas de pago</h5>
                          <button style="display:inline;" id="btn-agregarTablaDePago" class="btn btn-success borrarFila" type="button">
                            <i class="fa fa-fw fa-plus"></i>
                          </button>
                          <div id="tablas_pago" style="margin-top:15px;">
                          </div>
                      </div>
                  </div>
                  <div class="row">
                    <div class="col-md-12">
                      <h5>Vincular Máquinas  <button style="display:inline;" id="btn-agregarMaquina" class="btn btn-success borrarFila" type="button">
                        <i class="fa fa-fw fa-link"></i>
                      </button></h5>
                        <div id="listaMaquinas" class="">
                          <style media="screen">
                              .my-group .form-control{
                                  width:25%;
                              }
                              .my-group{
                                margin-bottom: 10px !important;
                              }
                          </style>

                          <div id="maquina_mod" class="row">
                            <div class="col-md-10">
                              <div class="input-group my-group">
                                <select class="selectCasinos selectpicker form-control" name="">
                                  @foreach($casinos as $casino)
                                  <option value="{{$casino->id_casino}}">{{$casino->codigo}}</option>
                                  @endforeach
                                </select>

                                <input type="text" class="form-control nro_admin" name="snpid" placeholder="Nro Admin">
                                <input type="text" class="form-control denominacion" name="snpid" placeholder="Denominación">
                                <input type="text" class="form-control porcentaje" name="snpid" placeholder="% Devolución">
                              </div>
                            </div>
                            <div class="col-md-2">
                              <button class="btn btn-danger borrarFila borrarJuego"><i class="fa fa-fw fa-trash"></i></button>
                            </div>
                          </div>
                        </div>
                    </div><!-- col-md-12 -->

                  </div>
                </div>
              </div>
          </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                  <button id='boton-cancelar' type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <button id='boton-salir' type="button" class="btn btn-default" data-dismiss="modal" style="display: none;">SALIR</button>
                  <input type="hidden" id="id_juego" name="id_juego" value="0">
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
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>

               <div  id="colapsado" class="collapse in">
                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                  <form id="frmEliminar" name="frmJuego" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div id="mensajeEliminar" class="col-xs-12">
                            <strong>¿Seguro desea eliminar el JUEGO?</strong>
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
    </div>

    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| JUEGOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Juegos</h5>
      <p>
        Define juegos con sus respectivas tablas de pago. Luego, se podrán vincular a máquinas con su n° de admin,
        denominación y % de devolución.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')

    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <script src="/js/lista-datos.js" charset="utf-8"></script>

    <!-- JavaScript personalizado -->
    <script src="js/seccionJuegos.js" charset="utf-8"></script>
    @endsection
