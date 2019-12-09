@extends('includes.dashboard')
@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/css/tab_style.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
@endsection
@section('contenidoVista')


<div class="col-lg-12 tab_content" id="pant_juegos" hidden="true">

    <div class="row">
      <div class="col-xl-12">
          <a href="" id="btn-nuevo-juego" dusk="btn-nuevo" style="text-decoration: none;">
            <div class="panel panel-default panelBotonNuevo">
              <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
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
      <div class="row"> <!-- fila de FILTROS -->
        <div class="col-md-12">
          <div id="contenedorFiltros" class="panel panel-default">
            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
            </div>
            <div id="collapseFiltros" class="panel-collapse collapse">
              <div class="panel-body">
                <div class="row">
                  <div class="col-md-6">
                    <h5>NOMBRE</h5>
                    <input id="FiltroNombre" type="text" name="" class="form-control" value=" " placeholder="Nombre de Juego">
                  </div>
                  <div class="col-md-6">
                    <h5>MESA</h5>
                    <input id="FiltroMesa" type="text" name="" class="form-control" value="0" placeholder="Número de Mesa">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <h5>CASINO</h5>
                    <select class="form-control" id="FiltroCasino">
                      @foreach ($casinos as $cas)
                      <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                      @endforeach
                      <option value="0" selected>- Todos los casinos-</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <h5>TIPO</h5>
                    <select class="form-control" id="FiltroTipo">
                      <option value="" selected>- Seleccione el tipo-</option>
                      @foreach ($tipos_mesas as $t)
                      <option value="{{$t->id_tipo_mesa}}">{{$t->descripcion}}</option>
                      @endforeach
                      <option value="0" >- Todos los tipos-</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <h5 style="color:#FAFAFA">boton buscar</h5>
                    <button id="btn-buscarJuegos" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                  </div>
                </div>
                <br>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- Fin de la fila de FILTROS -->

      <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4 id="tituloBusquedaJuegos">Juegos cargados en el Sistema</h4>
            </div>
            <div class="panel-body">
              <table id="tablaJuegos" class="table table-striped">
                <thead>
                  <tr>
                    <th class="col-xs-3" style="font-size:14px; text-align:center !important;">NOMBRE </th>
                    <th class="col-xs-3" style="font-size:14px; text-align:center !important;" >SIGLAS </th>
                    <th class="col-xs-3" style="font-size:14px; text-align:center !important;" >CASINO </th>
                    <th class="col-xs-3" style="font-size:14px; text-align:center !important;" >ACCIONES</th>
                  </tr>
                </thead>
                <tbody id="cuerpoTablaJuegos" >
                  @foreach($juegos as $juego)
                  <tr>
                    <td style=" text-align:center !important;">{{$juego->nombre_juego}}</td>
                    <td style="text-align:center !important;">{{$juego->siglas}}</td>
                    <td style="text-align:center !important;">{{$juego->casino->nombre}} </td>
                    <td style="text-align:center !important;">

                      <button type="button" class="btn btn-warning modificarJuego" value="{{$juego->id_juego_mesa}}">
                        <i class="fas fa-fw fa-pencil-alt"></i>
                      </button>
                      <button type="button" class="btn btn-danger eliminarJuego" value="{{$juego->id_juego_mesa}}">
                        <i class="fa fa-fw fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                  @endforeach


                    </tbody>
                  </table>

                </div>
            </div>
          </div>
      </div>  <!--/fila TABLA -->

</div>


  <!-- MODAL NUEVO JUEGO -->
<div class="modal fade" id="modalAltaJuego" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" style="width:70%">
      <div class="modal-content">
        <div class="modal-header" style="background-color:#1DE9B6;">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
          <h3 class="modal-title">| NUEVO JUEGO</h3>
        </div>

        <div id="colapsado" class="collapse in">
          <div class="modal-body">
            <form id="frmAltaMesa" name="frmAltaMesa" class="form-horizontal" novalidate="">
              <div class="row">
                <div class="col-md-6">
                  <h5>Nombre</h5>
                  <input type="text" class="form-control" id="nombre_juego" placeholder="Nombre Juego" name="user_name">
                  <br>
                </div>
                <div class="col-md-6">
                  <h5>Siglas</h5>
                  <input type="text" class="form-control" id="siglas_juego" name="name" placeholder="Siglas del juego" value="">
                  <br>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <h5>Casino</h5>
                  <select class="form-control" id="casino_juego">
                    <option value="0" selected class="default">- Seleccione un Casino-</option>
                    @foreach ($casinos as $cas)
                    <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                    @endforeach
                  </select>
                  <br>
                </div>
                <div class="col-md-4">
                  <h5>Tipo Mesa</h5>
                  <select class="form-control" id="tipo_mesa_juego">
                    <option value="0" selected class="default2">- Seleccione un Tipo de Mesa -</option>
                    @foreach ($tipos_mesas as $t)
                    <option value="{{$t->id_tipo_mesa}}">{{$t->descripcion}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <h5>N° de Posiciones</h5>
                  <input type="text" class="form-control" value="" id="posicionesJuego" placeholder="Posiciones por Juego">
                </div>
              </div>
            </form>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="btn-guardar-juego" value="nuevo">ACEPTAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>

          <div id="mensajeErrorAltaJuego" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Debe completar todos los campos.</span>
          </div> <!-- mensaje -->

        </div>
      </div>
    </div>
</div>


    <!-- MODAL MODIFICAR JUEGO -->
<div class="modal fade" id="modalModificarJuego" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
           <div class="modal-content">
              <div class="modal-header" style="background-color:#FFA726;">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title">| MODIFICAR JUEGO</h3>
              </div>

        <div id="colapsado" class="collapse in">
              <div class="modal-body">
                <form id="frmModificarJuego" name="frmAltaMesa" class="form-horizontal" novalidate="">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>NOMBRE: </h6>
                            <input type="text" class="form-control" id="modif_nom" value="">
                            <br>
                        </div>
                        <div class="col-md-6">
                            <h6>SIGLAS: </h6>
                            <input type="text" class="form-control" id="modif_siglas" value="">
                            <br>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-md-6">
                            <h6>CASINO: </h6>
                            <input type="text" class="form-control" id="modif_cas" value="" readonly="true">
                            <br>
                        </div>
                        <div class="col-md-6">
                            <h6>TIPO: </h6>
                            <input type="text" class="form-control" id="modif_tipo" value="" readonly="true">
                            <br>
                        </div>

                      </div>
                      <div class="row">
                        <div class="col-md-6">
                            <h6>MESAS VINCULADAS: </h6>
                            <table class="table table-striped">
                              <thead>
                                <tr>
                                  <td style="text-align:center !important;"><h5>NRO.MESA</h5></td>
                                  <td style="text-align:center !important;"><h5>SECTOR</h5></td>
                                </tr>
                              </thead>
                              <tbody id="mesasAsignadas" style="text-align:center !important" >

                                </tr>
                              </tbody>
                            </table>
                            <br>
                        </div>
                        <div class="col-md-6">
                          <h6>N° POSICIONES: </h6>
                          <input type="text" class="form-control" id="modif_pos" value="" >
                          <br>
                        </div>

                      </div>
                    <br>
                  </form>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-successAceptar" id="btn-modificar-juego" value="">GUARDAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
              </div>
              <div id="mensajeErrorModificacion" hidden>
                <br>
                <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
                <br>
                <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Debe completar todos los campos.</span>
              </div> <!-- mensaje -->

            </div>
          </div>
        </div>
</div>


  <!-- MODAL ELIMINAR -->

  <div class="modal fade" id="modalAlertaEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content">

           <div class="modal-header" style="background: #d9534f; color: #E53935;">
             <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
             <h3 class="modal-titleEliminar" style="color:#000000;">| ALERTA</h3>
           </div>

          <div class="modal-body" style="color:#fff; background-color:#FFFFF;">

                <h6 style="color:#000000 !important; font-size:14px;">¿ESTA SEGURO QUE DESEA ELIMINAR ESTE JUEGO?</h6>
                <br>
                <h6 id="msjeliminarJuego" style="color:#000000"></h6>

          </div>
          <br>
          <div class="modal-footer">
            <button type="button" class="btn btn-dangerEliminar" id="btn-eliminar-juego" value="" data-dismiss="modal">ELIMINAR</button>
          </div>
      </div>
    </div>
  </div>



  <!-- SECTORES MESAS -->
<div class="col-lg-12 tab_content" id="pant_sectores" hidden="true">
  <div class=" col-xl-9"> <!-- columna de FILTROS y TABLA -->
    <div class="row">
      <div class="col-xl-12">
        <a href="" id="btn-nuevo-sector" dusk="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
              <div class="backgroundNuevo"></div>
              <div class="row">
                <div class="col-xs-12">
                  <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">NUEVO SECTOR</h4>
                  </center>
                </div>
              </div>
            </div>
          </a>
        </div>
    </div>
    <div class="row"> <!-- fila de FILTROS -->
      <div class="col-xl-12">
        <div id="contenedorFiltrosSectores" class="panel panel-default">
          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltrosSec" style="cursor: pointer; ">
            <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
          </div>
          <div id="collapseFiltrosSec" class="panel-collapse collapse">
            <div class="panel-body">
              <div class="row">
                <div class="col-md-3">
                  <h5>NOMBRE SECTOR</h5>
                  <input id="s_descr" type="text" name="" class="form-control" value=" " placeholder="Nombre de Sector">
                </div>
                <div class="col-md-3">
                  <h5>MESA</h5>
                  <input id="s_mesa" type="text" name="" class="form-control" value="0" placeholder="Número de Mesa">
                </div>
              <div class="col-md-3">
                  <h5>CASINO</h5>
                  <select class="form-control" id="s_casino">
                    <option value="" selected>- Seleccione un Casino-</option>
                    @foreach ($casinos as $cas)
                    <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                    @endforeach
                    <option value="0" >- Todos los casinos-</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <h5 style="color:#FAFAFA">boton buscar</h5>
                  <button id="btn-buscarSectores" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                </div>
              </div>
              <br>
            </div>
          </div>
        </div>
      </div>
    </div> <!-- Fin de la fila de FILTROS -->

    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 id="tituloBusquedaSectores">Sectores cargados en el Sistema</h4>
          </div>
          <div class="panel-body">
            <table id="tablaSectores" class="table table-striped">
              <thead>
                <tr>
                  <th class="col-xs-3" style="font-size:14px; text-align:center !important;">NOMBRE </th>
                  <th class="col-xs-3" style="font-size:14px; text-align:center !important;" >CASINO </th>
                  <th class="col-xs-3" style="font-size:14px; text-align:center !important;" >MESAS </th>
                  <th class="col-xs-3" style="font-size:14px; text-align:center !important;" >ACCIONES</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaSectores" >

              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>  <!--/fila TABLA -->
  </div> <!-- Fin de la columna FILTROS y TABLA -->

</div>

<!-- MODAL NUEVO SECTOR -->
<div class="modal fade" id="modalAltaSector" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-70%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1DE9B6;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| NUEVO SECTOR</h3>
      </div>

      <div id="colapsado" class="collapse in">
        <div class="modal-body">
          <form id="frmAltaMesa" name="frmAltaMesa" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-md-6">
                <h5>Descripción</h5>
                <input type="text" class="form-control" id="nombre_sector" placeholder="Nombre de Sector">
                <br>
              </div>
              <div class="col-md-6">
                <h5>Casino</h5>
                <select class="form-control" id="casino_sector">
                  <option value="0" selected class="default">- Seleccione un Casino-</option>
                  @foreach ($casinos as $cas)
                  <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                  @endforeach
                </select>
                <br>
              </div>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-guardar-sector" value="nuevo">ACEPTAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>


      </div>
    </div>
  </div>
</div>

<!-- MODAL ELIMINAR SECTOR-->
<div class="modal fade" id="modalAlertaSector" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-70%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color:#D50000">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| ALERTA</h3>
      </div>

      <div id="colapsado" class="collapse in">
        <div class="modal-body">
          <h6 style="color:#000000; font-size: 18px !important; text-align:center !important"> ¿ESTA SEGURO QUE DESEA ELIMINAR ESTE SECTOR?</h6>
          <div class="row">

          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dangerEliminar" id="btn-baja-sector" value="">ELIMINAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>

      </div>
    </div>
  </div>
</div>


@endsection

  <!-- Comienza modal de ayuda -->
  @section('tituloDeAyuda')
  <h3 class="modal-title" style="color: #fff;">| AYUDA GESTIONAR MESAS</h3>
  @endsection
  @section('contenidoAyuda')
  <div class="col-md-12">
    <h5>Tarjetas de gestionar juegos</h5>
    <p>
    ver
    </p>
  </div>

  @endsection
  <!-- Termina modal de ayuda -->

  @section('scripts')

  <!-- JavaScript personalizado -->
  <script src="js/inputSpinner.js" type="text/javascript"></script>
  <script src="/js/lista-datos.js" type="text/javascript"></script>

  <!-- Custom input Bootstrap -->
  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>

  <script src="js/Juegos/gestionJuegos.js"></script>


  @endsection
