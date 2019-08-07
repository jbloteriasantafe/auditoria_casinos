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
    <div id="contenedorFiltros" class="row"> <!-- Tarjeta de FILTROS -->
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
            <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
          </div>
          <div id="collapseFiltros" class="panel-collapse collapse">
            <div class="panel-body">
              <div class="row"> <!-- Primera fila -->
                <div class="col-lg-6">
                  <h5>Nombre Progresivo</h5>
                  <input id="B_nombre_progresivo" type="text" class="form-control" placeholder="Nombre progresivo">
                </div>
                <div class="col-lg-6">
                  <h5 style="color:#f5f5f5">Búsqueda</h5>
                  <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                </div>
              </div> <!-- / Primera fila -->
            </div>
          </div>
        </div>
      </div>
    </div> <!-- / Tarjeta FILTROS -->

    <div class="row"> <!-- Tarjeta TABLA Progresivos -->
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>ÚLTIMOS PROGRESIVOS</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultados" class="table table-fixed tablesorter">
              <thead>
                <tr>
                  <th class="col-xs-6" value="progresivo.nombre" estado="">NOMBRE PROGRESIVO  <i class="fa fa-sort"></i></th>
                  <th class="col-xs-6">ACCIONES</th>
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
    </div>
  </div> <!-- / Tarjeta TABLA -->

  {{-- <div class="col-lg-12 col-xl-3">
    <div class="row">
      <div class="col-lg-12">
        <a href="" id="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"><center>
              <div class="backgroundNuevo"></div>
              <div class="row">
                <div class="col-xs-12">
                  <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">NUEVO PROGRESIVO</h4>
                  </center>
                </div>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div> --}}

    <div class="col-lg-12 col-xl-3">
      <div class="row">
        <div class="col-lg-12">
          <a href="" id="btn-nuevo-ind" style="text-decoration: none;">
            <div class="panel panel-default panelBotonNuevo">
              <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"><center>
                <div class="backgroundNuevo"></div>
                <div class="row">
                  <div class="col-xs-12">
                    <center>
                      <h5 class="txtLogo">+</h5>
                      <h4 class="txtNuevo">NUEVO PROGRESIVO INDIVIDUAL</h4>
                    </center>
                  </div>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>

      <div class="col-lg-12 col-xl-3">
        <div class="row">
          <div class="col-lg-12">
            <a href="" id="btn-nuevo-link" style="text-decoration: none;">
              <div class="panel panel-default panelBotonNuevo">
                <center><img class="imgNuevo" src="/img/logos/progresivos_white.png"><center>
                  <div class="backgroundNuevo"></div>
                  <div class="row">
                    <div class="col-xs-12">
                      <center>
                        <h5 class="txtLogo">+</h5>
                        <h4 class="txtNuevo">NUEVO PROGRESIVO LINKEADO</h4>
                      </center>
                    </div>
                  </div>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
            <!-- /.row -->

    <!-- Modal Progresivo -->
    <div class="modal fade" id="modalProgresivo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header" style="background: #5cb85c;">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" style="color: #fff;">| NUEVO PROGRESIVO</h3>
                </div>
                <div  id="colapsado" class="collapse in">
                  <div class="modal-body modal-Cuerpo">
                      <div class="row">
                        <div class="col-md-6 col-lg-6">
                          <h5 class='row'>Nombre Progresivo</h5>
                          <input id="nombre_progresivo" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
                          <!-- <div id="alerta-nombre_progresivo" class="alert alert-danger"><span></span></div> -->
                          <span id="alerta-nombre-progresivo" class="alertaSpan"></span>
                        </div>
                      </div>
                    <div class="navModal">

                      <div class=''>
                         <h3 class=''>Pozos</h3>
                         <div class="row">
                           <h5>Nuevo Pozo:
                             <button id="btn-agregarPozo" class="btn btn-success  " type="button">
                               <i class="fa fa-fw fa-plus"></i>
                               <b>Agregar</b>
                             </button>
                           </h5>
                         </div>
                         <div id="contenedorPozos" class="row" style="overflow-y: auto;overflow-x: hidden;height: 400px;"></div>
                     </div>
                     <div class=''>
                       <h3 class=''>Maquinas</h3>
                       <div class="row">
                         <h5>Enlazar maquina:
                           <button id='btn-agregarMaquina' class="btn btn-success">
                             <i class="fa fa-fw fa-plus"></i>
                             <b>Enlazar</b>
                           </button>
                         </h5>
                       </div>
                       <div id="contenedorMaquinas" class="row" style="overflow-y: auto;overflow-x: hidden;height: 400px;"></div>
                     </div>

                  </div> <!-- /Fin panel minimizable -->

                  <div class="modal-footer">
                    <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                    <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                    <input type="hidden" id="id_progresivo" value="0">
                  </div>
                </div> <!-- Fin modal-header -->
            </div>
          </div>
    </div>

    <!-- Modal Progresivo Individual -->

    <div class="modal fade" id="modalProgInd" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header" style="background: #5cb85c;">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" style="color: #fff;">| NUEVO PROGRESIVO INDIVIDUAL</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                  <div class="modal-body modal-Cuerpo">
                      <div class="row">
                        <div class="col-md-6 col-lg-6">
                          <h5>Nombre Progresivo</h5>
                          <input id="nombre_progresivo_ind" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
                          <br>
                          <!-- <div id="alerta-nombre_progresivo" class="alert alert-danger"><span></span></div> -->
                          <span id="alerta-nombre-progresivo" class="alertaSpan"></span>
                        </div>
                        <div class="col-xs-6 col-md-6 col-lg-6">
                          <h5>Valor Máximo</h5>
                          <input id="maximo_ind" type="text" class="form-control" placeholder="Valor Máximo">
                        </div>
                      </div>
                      <br>
                      <div id="cuerpo_individual">
                        <div class="row">
                          <div class="col-xs-6 col-md-6 col-lg-6">


                            <h5>Buscador Islas <i class="fa fa-fw fa-search"></i></h5>
                            <div class="row">
                              <div class="input-group lista-datos-group">
                                              <input id="inputIslaInd" class="form-control" type="text" value="" autocomplete="off" >
                                              <span class="input-group-btn">
                                                <button class="btn btn-default btn-lista-datos agregarIsla" type="button"><i class="fa fa-plus"></i></button>
                                              </span>
                              </div>

                            </div>
                            <br>
                            <h5>Buscador Maquinas <i class="fa fa-fw fa-search"></i></h5>
                            <div class="row">
                              <div class="input-group lista-datos-group">
                                              <input id="inputMtmInd" class="form-control" type="text" value="" autocomplete="off" >
                                              <span class="input-group-btn">
                                                <button class="btn btn-default btn-lista-datos agregarMaquina" type="button"><i class="fa fa-plus"></i></button>
                                              </span>
                              </div>
                            </div>

                          </div>

                          <div id="" class="col-md-6 col-lg-6">
                            <h5>Maquinas Seleccionadas:</h5>
                            <ul class="listaMaquinas">
                            </ul>
                          </div>
                        </div>
                        <br>
                        <div class="row" id="niveles_ind">
                            <div class="col-lg-12">
                                 <button class="btn btn-success btn-agregarNivelProgresivo" type="button"><i class="fa fa-fw fa-plus"></i> Nuevo Nivel de Progresivo</button>
                                <div class="columna" style="padding-top: 15px;">
                                </div>
                            </div>
                        </div>

                      </div>

                  </div> <!-- /Fin panel minimizable -->

                  <div class="modal-footer">
                    <button type="button" class="btn btn-successAceptar" id="btn-guardar-ind" value="nuevo">ACEPTAR</button>
                    <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                    <input type="hidden" id="id_progresivo_ind" value="0">
                  </div>

                </div> <!-- Fin modal-header -->

            </div>
          </div>
    </div>
    <!-- Fin modal Progresivo Individual -->


    <!-- Modal Progresivo Linkeado -->

    <div class="modal fade" id="modalProgLink" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-keyboard="false" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header" style="background: #5cb85c;">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" style="color: #fff;">| NUEVO PROGRESIVO LINKEADO</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                  <div class="modal-body modal-Cuerpo">
                      <div class="row">
                        <div class="col-md-6 col-lg-6">
                          <h5>Nombre Progresivo</h5>
                          <input id="nombre_progresivo_link" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
                          <br>
                          <!-- <div id="alerta-nombre_progresivo" class="alert alert-danger"><span></span></div> -->
                          <span id="alerta-nombre-progresivo" class="alertaSpan"></span>
                        </div>
                        <div class="col-xs-6 col-md-6 col-lg-6">
                          <h5>Valor Máximo</h5>
                          <input id="maximo_link" type="text" class="form-control" placeholder="Valor Máximo">
                        </div>
                      </div>

                      <div id="cuerpo_individual">


                        <div class="row" id="niveles_link">
                            <div class="col-lg-12">
                              <button class="btn btn-success btn-agregarNivelProgresivo" type="button"><i class="fa fa-fw fa-plus"></i> Nuevo Nivel de Progresivo</button>
                              <div class="columna" style="padding-top: 15px;">
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row" style="border-top: 4px solid #a0968b; padding-top: 15px;"></div>

                        <div id="cuerpo_linkeado">
                          <!-- comienzo seccdio invididual -->
                          <div class="row">

                              <div class="row">
                                  <div class="col-xs-6 col-md-6 col-lg-6">


                                    <h5>Buscador Islas <i class="fa fa-fw fa-search"></i></h5>
                                    <div class="row">
                                      <div class="input-group lista-datos-group">
                                                      <input id="inputIslaLink" class="form-control" type="text" value="" autocomplete="off" >
                                                      <span class="input-group-btn">
                                                        <button class="btn btn-default btn-lista-datos agregarIsla" type="button"><i class="fa fa-plus"></i></button>
                                                      </span>
                                      </div>

                                    </div>
                                    <br>
                                    <h5>Buscador Maquinas <i class="fa fa-fw fa-search"></i></h5>
                                    <div class="row">
                                      <div class="input-group lista-datos-group">
                                                      <input id="inputMtmLink" class="form-control" type="text" value="" autocomplete="off" >
                                                      <span class="input-group-btn">
                                                        <button class="btn btn-default btn-lista-datos agregarMaquina" type="button"><i class="fa fa-plus"></i></button>
                                                      </span>
                                      </div>
                                    </div>

                                  </div>

                                  <div id="" class="col-md-6 col-lg-6">
                                    <h5>Maquinas Seleccionadas:</h5>
                                    <ul class="listaMaquinas">
                                    </ul>
                                  </div>
                                </div>

                            <div class="col-lg-12">
                               <button id="btn-agregarPozo-link" class="btn btn-success  " type="button"><i class="fa fa-fw fa-plus"></i> Nuevo Pozo</button>
                            </div>
                          </div>
                          <br>



                         </div>
                          <!-- fin despaleable pozos-->
                        </div>




                      </div>




                  </div> <!-- /Fin panel minimizable -->

                  <div class="modal-footer">
                    <button type="button" class="btn btn-successAceptar" id="btn-guardar-link" value="nuevo">ACEPTAR</button>
                    <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                    <input type="hidden" id="id_progresivo_link" value="0">
                  </div>

                </div> <!-- Fin modal-header -->

            </div>
          </div>
    </div>




    <!-- Fin Modal Progresivo Linkeado -->




    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                  <form id="frmEliminar" name="frmProgresivo" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Seguro desea eliminar el PROGRESIVO?</strong>
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


    </div> <!-- / Tarjeta TABLA -->

    <!-- Tabla del pozo de ejemplo -->

    <div class="row top-buffer tablaPozoDiv ejemplo" style="display: none">
      <div class="col-md-14">
        <div>
          <button class="btn btn-link abrirPozo"></i>
            <i class="fa fa-fw fa-angle-up"></i>
          </button>
          <b class="nombrePozo">Pozo</b>
          <button class="btn btn-info editarPozo">
            <i class="fa fa-fw fa-pencil-alt"></i>
          </button>
          <button class="btn btn-info eliminarPozo"></i>
            <i class="fa fa-fw fa-trash-alt"></i>
          </button>

          <div class="panel-body collapse">
            <table class="table table-condensed tablesorter tablaPozo" style="padding: 1px;">
              <thead>
                <tr>
                  <th class="col-xs-1" value="nivel_progresivo.nro_nivel" estado="">#<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="nivel_progresivo.nombre_nivel" estado="">Nombre nivel progresivo<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="nivel_progresivo.base" estado="">Base<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="nivel_progresivo.maximo" estado="">Máximo<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="nivel_progresivo.porc_visible" estado="">% Visible<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="nivel_progresivo.porc_oculto" estado="">% Oculto<i class="fa fa-sort"></i></th>
                  <th class="col-xs-3">ACCIONES</th>
                </tr>
              </thead>
              <tbody class="cuerpoTablaPozo">
                <tr class="filaEjemplo" style="display: none;">
                  <td class="col-xs-1 cuerpoTablaPozoNumero">999</td>
                  <td class="col-xs-2 cuerpoTablaPozoNombre">NOMBRE EJEMPLO</td>
                  <td class="col-xs-2 cuerpoTablaPozoBase">999999999</td>
                  <td class="col-xs-2 cuerpoTablaPozoMaximo">999999999</td>
                  <td class="col-xs-1 cuerpoTablaPorcVisible">12.34</td>
                  <td class="col-xs-1 cuerpoTablaPorcOculto">12.34</td>
                  <td class="col-xs-3 cuerpoTablaPozoAcciones">
                    <span> </span>
                    <button class="btn btn-info editar">
                      <i class="fa fa-fw fa-pencil-alt"></i>
                    </button>
                    <span> </span>
                    <button class="btn btn-info borrar">
                      <i class="fa fa-fw fa-trash-alt"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
            <button class="btn btn-info agregar">
              <i class="fa fa-fw fa-plus"></i>
              <b>Agregar nivel</b>
            </button>
          </div>
        </div>
      </div>

    </div>

    <div class="row top-buffer tablaMaquinasDiv ejemplo"  style="display: none;">
      <div class="col-md-10">
        <div>
          <div class="panel-body">
            <table class="table table-condensed tablesorter tablaMaquinas">
              <thead>
                <tr>
                  <th class="col-xs-1" value="maquina.nro_admin" estado="">#<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="maquina.sector" estado="">Sector<i class="fa fa-sort"></i></th>
                  <th class="col-xs-1" value="maquina.isla" estado="">Isla<i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" value="maquina.marca_juego" estado="">Marca juego<i class="fa fa-sort"></i></th>
                  <th class="col-xs-4">ACCIONES</th>
                </tr>
              </thead>
              <tbody class="cuerpoTabla" style="overflow-y: auto;overflow-x: hidden;">
                <tr class="filaEjemplo">
                  <td class="col-xs-1 cuerpoTablaNroAdmin">999</td>
                  <td class="col-xs-2 cuerpoTablaSector">SECTOR999</td>
                  <td class="col-xs-1 cuerpoTablaIsla">999</td>
                  <td class="col-xs-4 cuerpoTablaMarcaJuego">SIN MARCA</td>
                  <td class="col-xs-4 cuerpoTablaAcciones">
                    <button class="btn btn-info">
                      <i class="fas fa-unlink"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| PROGRESIVOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Progresivos</h5>
      <p>
        Se podrán cargar los distintos tipos de progresivos asociados a sus respectivas tragamonedas.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>
    <!-- JavaScript personalizado -->
    <script src="js/seccionProgresivos.js" charset="utf-8"></script>
    @endsection
