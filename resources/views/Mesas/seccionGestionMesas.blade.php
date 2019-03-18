@extends('includes.dashboard')
@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel="stylesheet" href="/css/styleSlider.css">


@endsection
@section('contenidoVista')


<div class="row">
  <div class="col-lg-12 col-xl-9">
    <a href="" id="btn-nueva-mesa" dusk="btn-nuevo" style="text-decoration: none;">
      <div class="panel panel-default panelBotonNuevo">
        <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
          <div class="backgroundNuevo"></div>
            <div class="row">
              <div class="col-xs-12">
                <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">NUEVA MESA</h4>
                </center>
              </div>
          </div>
      </div>
    </a>
  </div>

</div>
  <div class="row">
    <div class="col-lg-12 col-xl-9"> <!-- columna de FILTROS y TABLA -->
      <div class="row"> <!-- fila de FILTROS -->
        <div class="col-md-12">
          <div id="contenedorFiltros" class="panel panel-default">
            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
            </div>
            <div id="collapseFiltros" class="panel-collapse collapse">
              <div class="panel-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>Número</h5>
                    <input id="F_Nro" class="form-control" placeholder="Número" val="0">
                  </div>
                  <div class="col-md-4">
                    <h5>Juego</h5>
                    <select class="form-control" id="F_Juego">
                      <option value="" selected>- Todos los juegos -</option>
                      @foreach ($juegos as $juego)
                      <option value="{{$juego->id_juego_mesa}}">{{$juego->nombre_juego}}  - {{$juego->casino->codigo}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4">
                    <h5>Sector</h5>
                    <select class="form-control" id="F_Sector">
                      <option value="" selected>- Seleccione un Sector-</option>
                      @foreach ($sectores as $sector)
                      <option value="{{$sector->id_sector_mesas}}">{{$sector->descripcion}} - {{$sector->casino->codigo}}</option>
                      @endforeach
                      <option value="0" >-Todos los sectores-</option>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <h5>Casino</h5>
                    <select class="form-control" id="F_Casino">
                      <option value="" selected>- Seleccione un Casino-</option>
                      @foreach ($casinos as $cas)
                      <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                      @endforeach
                      <option value="0" >-Todos los casinos-</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <h5>Tipo Mesa</h5>
                    <select class="form-control" id="F_Tipo">
                      <option value="" selected>- Seleccione un Tipo de Mesa -</option>
                      @foreach ($tipo_mesa as $tipo)
                      <option value="{{$tipo->id_tipo_mesa}}">{{$tipo->descripcion}}</option>
                      @endforeach
                      <option value="0" >-Todos los tipos-</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <h5 style="color:#FAFAFA">boton buscar</h5>
                    <button id="btn-buscarMesas" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                  </div>
                </div>
                <br>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- Fin de la fila de FILTROS -->

      <div class="row">
        <div class="col-xs-12">
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4 id="tituloBusquedaMesas">Mesas cargadas en el Sistema</h4>
            </div>
            <div class="panel-body">
              <table id="tablaMesas" class="table table-fixed tablesorter">
                <thead>
                  <tr>
                    <th class="col-xs-2" value="nro_mesa" style="font-size:14px; text-align:center !important;">NÚMERO <i class="fas fa-sort"></i></th>
                    <th class="col-xs-3" value="nombre_juego" style="font-size:14px; text-align:center !important;">JUEGO <i class="fas fa-sort"></i></th>
                    <th class="col-xs-2" value="nombre_sector" style="font-size:14px; text-align:center !important;">SECTOR <i class="fas fa-sort"></i></th>
                    <th class="col-xs-2" value="casino.nombre" style="font-size:14px; text-align:center !important;">CASINO <i class="fas fa-sort"></i></th>
                    <th class="col-xs-3" style="font-size:14px; text-align:center !important;">ACCIONES</th>
                  </tr>
                </thead>
                <tbody id="cuerpoTablaMesas" style="height: 450px;">



                </tbody>
              </table>
              <table>
                <tr id="moldeFilaMesa" class="mesaClone" style="display:none">
                        <td class="col-xs-2 nroMesa" style=" text-align:center !important;"></td>
                        <td class="col-xs-3 juegoMesa" style=" text-align:center !important;"></td>
                        <td class="col-xs-2 sectorMesa" style=" text-align:center !important;"></td>
                        <td class="col-xs-2 casinoMesa" style="text-align:center !important;"></td>
                        <td class="col-xs-3" style=" text-align:center !important;">
                          <button type="button" class="btn btn-info infoMesa" value="" data-toggle:"tooltip"
                            data-placement:"top" title: "VER MÁS" data-delay:"{'show':'300', 'hide':'100'}">
                            <i class="fa fa-fw fa-search-plus"></i>
                          </button>
                          <button type="button" class="btn btn-warning modificarMesa" value="">
                            <i class="fas fa-fw fa-pencil-alt"></i>
                          </button>
                          <button type="button" class="btn btn-danger eliminarMesa" value="">
                            <i class="fa fa-fw fa-trash"></i>
                          </button>

                        </td>
                </tr>
            </table>
                  <!--Comienzo indices paginacion-->
                  <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                </div>
              </div>
            </div>
          </div>  <!--/fila TABLA -->
        </div> <!-- Fin de la columna FILTROS y TABLA -->



  </div>

  <!-- MODAL NUEVA MESA -->
      <div class="modal fade" id="modalAltaMesa" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
               <div class="modal-content">
                  <div class="modal-header" style="background-color:#1DE9B6;">
                    <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                    <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                    <h3 class="modal-title">| NUEVA MESA</h3>
                  </div>

                  <div id="colapsado" class="collapse in">
                    <div class="modal-body">
                      <form id="frmAltaMesa" name="frmAltaMesa" class="form-horizontal" novalidate="">
                          <div class="row">
                              <div class="col-md-4">
                                  <h5>Número de Mesa</h5>
                                  <input type="text" class="form-control" id="nro_mesa" placeholder="Número de Mesa" name="user_name">
                                  <br>
                              </div>
                              <div class="col-md-4">
                                  <h5>Número Administrativo</h5>
                                  <input type="text" class="form-control" id="nro_adm_mesa" placeholder="Número Adm." name="user_name">
                                  <span class="help-block" style="color: #0D47A1 !important;margin-top:0px !important; font-size:11px !important;padding-left:5px !important"><i>*Nro de manejo interno de casinos</i></span>
                                  <br>
                              </div>
                              <div class="col-md-4">
                                  <h5>Nombre</h5>
                                  <input type="text" class="form-control" id="nombre_mesa" name="name" placeholder="Nombre Mesa" value="">
                                  <br>
                              </div>
                            </div>
                            <div class="row">
                              <div class="col-md-6">
                                  <h5>Descripción</h5>
                                  <input type="text" class="form-control" id="descripcion_mesa" name="descripcion" placeholder="Descripción" value="">
                                  <br>
                              </div>
                              <div class="col-md-6">
                                  <h5>Casino</h5>
                                  <select class="form-control" id="casino_mesa">
                                    <option value="0" selected class="default">- Seleccione un Casino-</option>

                                  </select>
                                  <span class="help-block" style="margin-top:0px !important; font-size:11px !important;padding-left:5px !important; color: #0D47A1 !important"><i>*Seleccione para habilitar el resto de los campos</i></span>
                                  <br>
                              </div>
                            </div>
                          <br>
                          <div class="row">
                              <div class="col-md-4">
                                <h5>Sector</h5>
                                <select class="form-control" id="sector_mesa">
                                  <option value="0" selected class="default1">- Seleccione un Sector-</option>

                                </select>
                              </div>
                              <div class="col-md-4">
                                <h5>Juego</h5>
                                <select class="form-control" id="juego_mesa">
                                  <option value="0" selected class="default2">- Seleccione un Juego -</option>

                                </select>
                              </div>
                              <div class="col-md-4">
                                  <h5>Tipo Moneda</h5>
                                  <select class="form-control" id="moneda_mesa">
                                    <option value="0" selected class="default3">- MULTI-MONEDA -</option>

                                  </select>
                                  </div>
                              </div>
                          <!-- /nuevas secciones -->

                      </form>
                    </div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-successAceptar" id="btn-guardar-mesa" value="nuevo">ACEPTAR</button>
                      <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                    </div>

                    <div id="mensajeErrorAlta" hidden>
                      <br>
                      <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
                      <br>
                      <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Debe completar todos los campos.</span>
                    </div> <!-- mensaje -->
                  </div>
              </div>
          </div>
      </div>

      <!-- FIN MODAL GUIA -->
    <style media="screen">

      .contenedorFecha p {
        font-family: Roboto-Regular;
        font-size: 18px;
      }

      .iconoMoneda {
        text-align: center;
        padding: 0px !important;
      }
      .iconoMesa {
        text-align: center;
        padding: 0px !important;
      }
      .tmoneda .fa-usd-square {
        color: #E91E63;
      }
      .tmesa .fa-pencil-alt {
        color: #FFC107;
      }
      </style>
  <!-- MODAL DETALLE -->
  <div class="modal fade" id="modalDetalleMesa" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header"  style="background-color:#4FC3F7;">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
            <h3 class="modal-title">| DETALLES MESA</h3>
          </div>

          <div id="colapsado" class="collapse in">
            <div class="modal-body">
                <div class="row" style=" border-bottom:2px solid #ccc; padding-top:30px; padding-bottom:30px;">
                  <div class="col-xs-6" style="text-align:center;position:relative; left:40px;">
                    <br>
                    <h6 class="detalle_nombre" style="font-family: Roboto-BoldCondensed; font-size: 24px;display:inline">mesa</h6>
                    <h6 class="detalle_descripcion" style="font-family: Roboto-Regular;font-size:18px; display:inline">Nro</h6>
                    <br>
                  </div>
                    <div class="col-xs-6 offset-xs-4 iconoInfo">
                        <i class="fas fa-info-circle fa-4x" ></i>
                    </div>
                </div>

                <div class="row" style=" border-bottom:2px solid #ccc; ">

                  <div class="col-md-6" style="text-align:center; border-right: 2px solid #ccc;">
                    <h6 style="font-size:20px;font-weight:bold;padding-top:30px;">DESCRIPCIÓN:</h6>
                    <div class="row desc" style="padding-bottom:15px;">
                      <div class="col-xs-3 offset-xs-1 iconoDesc" style="display:inline;position:relative;left:20px;">
                        <i class="fas fa-align-justify fa-3x" ></i>
                      </div>
                      <h5 class="detalle_descripcion" style="color: #000; font-family: Roboto-Regular;font-size:18px; position:relative;right:45px;"></h5>
                    </div>
                  </div>

                  <div class="col-md-6"  style="text-align:center; ">
                    <h6 style="font-size:20px;font-weight:bold; padding-top:30px;">NÚMERO DE MESA:</h6>
                    <div class="row nro" >
                      <div class="col-xs-3 offset-xs-1 iconoNro" style="display:inline;position:relative;left:25px;">
                        <i class="fas fa-clipboard-check fa-3x" ></i>
                      </div>
                      <h5 class="detalle_nro" style="color:#000;font-family: Roboto-Regular;font-size:18px;position:relative;right:55px;"></h5>
                    </div>
                  </div>

                </div>

                <div class="row" style=" border-bottom:2px solid #ccc;">

                    <div class="col-md-6" style="text-align:center; border-right: 2px solid #ccc; ">
                        <h6 style="font-size:20px;font-weight:bold;padding-top:30px;">CASINO:</h6>
                      <div class="row cas" style="padding-bottom:15px;">
                      <div class="col-xs-3 offset-xs-1 iconoCasino" style="display:inline;position:relative;left:20px;top:0px;">
                        <i class="far fa-building fa-3x" ></i>
                      </div>
                        <h5 class="detalle_casino" style="color:#000;font-family: Roboto-Regular;font-size:18px;position:relative;right:45px;"></h5>
                      </div>
                  </div>

                  <div class="col-md-6"  style="text-align:center;">
                    <h6 style="font-size:20px;font-weight:bold; padding-top:30px;">SECTOR:</h6>
                    <div class="row sector" style="padding-bottom:15px;">
                      <div class="col-xs-3 offset-xs-1 iconoSector" style="display:inline;position:relative;left:25px; ">
                        <i class="fab fa-buromobelexperte fa-3x" ></i>
                      </div>
                      <h5 class="detalle_sector" style="color:#000;font-family: Roboto-Regular;font-size:18px;position:relative;right:55px;"></h5>
                    </div>
                  </div>

                </div>

                  <div class="row" >

                    <div class="col-md-6" style="text-align:center; border-right: 2px solid #ccc;">
                      <center>
                        <h6  style="font-size:20px;font-weight:bold;text-align:center !important; padding-top:30px;">TIPO MONEDA: </h6>
                      </center>
                      <div class="row tmoneda" style="padding-bottom:15px;">
                        <div class="col-xs-2 col-xs-offset-1 iconoMoneda" style="display:inline;position:relative;left:10px;">
                          <i class="fas fa-hand-holding-usd fa-3x" ></i>
                        </div>
                          <h5 class="detalle_moneda" style="color:#000;font-family: Roboto-Regular;font-size:18px;position:relative;right:50px;"></h5>
                      </div>
                    </div>

                    <div class="col-md-6" style="text-align:center; border-right: 2px solid #ccc">
                      <center>
                        <h6 style="font-size:20px;font-weight:bold;text-align:center !important; padding-top:30px; ">TIPO MESA: </h6>
                      </center>
                      <div class="row tmesa" style="padding-bottom:15px;">
                        <div class="col-xs-3 offset-xs-1 iconoMesa" style="display:inline;position:relative;left:30px;">
                          <i class="fas fa-dice fa-3x" ></i>
                        </div>
                        <h5 class="detalle_tipo" style="color:#000;font-family: Roboto-Regular;font-size:18px;position:relative;right:30px;"></h5>
                      </div>
                    </div>

                  </div>
                  <br>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"> SALIR</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  <!-- MODAL MODIFICAR MESA -->
  <div class="modal fade" id="modalModificarMesa" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"  data-backdrop="static" data-keyboard="false">
      <div class="modal-dialog modal-lg">
           <div class="modal-content">
              <div class="modal-header" style="background-color:#FFA726">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title">| MODIFICAR MESA</h3>
              </div>

            <div id="colapsado" class="collapse in">
                  <div class="modal-body" style="font-family: Roboto;">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Número de Mesa</h5>
                                <input type="text" class="form-control" id="numeroM" placeholder="Número de Mesa" name="user_name">
                                <br>
                            </div>
                            <div class="col-md-4">
                                <h5>Número Adminitrativo</h5>
                                <input type="text" class="form-control" id="numeroAdmM" placeholder="Número de Mesa" name="user_name">
                                <br>
                            </div>
                            <div class="col-md-4">
                                <h5>Nombre</h5>
                                <input type="text" class="form-control" id="nombreM" name="name" placeholder="Nombre Mesa" value="">
                                <br>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Descripción</h5>
                                <input type="text" class="form-control" id="descripcionM" name="descripcion" placeholder="Descripción" value="">
                                <br>
                            </div>
                            <div class="col-md-6">
                                <h5>Casino</h5>
                                <select class="form-control" id="casinoM" readonly="true">
                                  <option value="0" selected>- Seleccione un Casino-</option>
                                  @foreach ($casinos as $cas)
                                  <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                                  @endforeach
                                </select>
                                <br>
                            </div>
                          </div>
                        <br>
                        <div class="row">
                            <div class="col-md-4">
                              <h5>Sector</h5>
                              <select class="form-control" id="sectorM">
                                <option value="0" selected>- Seleccione un Sector-</option>
                                @foreach ($sectores as $sector)
                                <option value="{{$sector->id_sector_mesas}}">{{$sector->descripcion}}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-md-4">
                              <h5>Juego</h5>
                              <select class="form-control" id="juegoM">
                                <option value="0" selected>- Seleccione un Juego -</option>
                                @foreach ($juegos as $juego)
                                <option value="{{$juego->id_juego_mesa}}">{{$juego->nombre_juego}}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-md-4">
                                <h5>Tipo Moneda</h5>
                                <select class="form-control" id="monedaM">
                                  <option value="0" selected class="default3">- MULTI-MONEDA -</option>
                                  @foreach ($monedas as $moneda)
                                  <option value="{{$moneda->id_moneda}}">{{$moneda->descripcion}}</option>
                                  @endforeach
                                </select>
                            </div>
                          </div>
                  </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-successAceptar" id="btn-modificar-mesa" value="">ACEPTAR</button>
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
             <h3 class="modal-titleEliminar" style="color:#000000;">CONFIMAR ELIMINACIÓN</h3>
           </div>

          <div class="modal-body" style="color:#fff; background-color:#FFFFF;">

                  <!-- Si no anda falta el <fieldset> -->

                <h4 style="color:#000000">¿Esta seguro que desea eliminar esta Mesa?</h4>

          </div>
          <br>
          <div class="modal-footer">
            <button type="button" id="btn-eliminar-mesa" value="" class="btn btn-default" data-dismiss="modal">ACEPTAR</button>
            <input id="id_casino" type="text" name="" value="" hidden=true>

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
    <h5>Tarjetas de gestionar mesas</h5>
    <p>
      Gestiona el ingreso o baja de mesas existentes en el sistema. Permite asociarlas a diferentes casinos y modificar sus correspondientes datos.
    </p>
  </div>

  @endsection
  <!-- Termina modal de ayuda -->

  @section('scripts')

  <!-- JavaScript paginacion -->
  <script src="/js/paginacion.js" charset="utf-8"></script>
  <script src="/js/lista-datos.js" type="text/javascript"></script>

  <!-- DateTimePicker JavaScript -->
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <!-- Custom input Bootstrap -->
  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>

  <script src="js/Mesas/seccionGestionMesas.js"></script>



  @endsection
