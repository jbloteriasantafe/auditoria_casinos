
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
$id_usuario = $usuario['usuario']->id_usuario;
$cas = $usuario['usuario']->casinos;
?>
@extends('includes.dashboard')
@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/paginacion.css">
<link rel="stylesheet" href="/css/styleSlider.css">

<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/js/jquery-ui-1.12.1.custom/jquery-ui.css">
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="/js/jquery-timepicker-1.3.5\jquery.timepicker.min.css">

@endsection
@section('contenidoVista')

<div class="row">
    <div class="col-xl-3">
      <div class="row">
        <!-- botón de generar planilla de apertura -->
        <div class="col-md-12">
          <a href="" id="btn-generar-rel" dusk="btn-nuevo" style="text-decoration: none;">
            <div class="panel panel-default panelBotonNuevo">
              <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
                <div class="backgroundNuevo"></div>
                  <div class="row">
                    <div class="col-xs-12">
                      <center>
                          <h5 class="txtLogo">+</h5>
                          <h4 class="txtNuevo">GENERAR PLANILLA APERTURA </h4>
                      </center>
                    </div>
                    </div>
                </div>
              </a>
          </div>
        </div>

        <div class="row">
        <!-- botón de cargar apertura fiscalizada -->
          <div class="col-md-12">
            <a href="" id="btn-cargar-apertura" dusk="btn-nuevo" style="text-decoration: none;">
              <div class="panel panel-default panelBotonNuevo">
                <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
                  <div class="backgroundNuevo"></div>
                    <div class="row">
                      <div class="col-xs-12">
                        <center>
                            <h5 class="txtLogo">+</h5>
                            <h4 class="txtNuevo">CARGAR APERTURA</h4>
                        </center>
                      </div>
                      </div>
                  </div>
                </a>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <a href="" id="btn-cargar-cierre" dusk="btn-nuevo" style="text-decoration: none;">
                <div class="panel panel-default panelBotonNuevo">
                  <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
                    <div class="backgroundNuevo"></div>
                      <div class="row">
                        <div class="col-xs-12">
                          <center>
                              <h5 class="txtLogo">+</h5>
                              <h4 class="txtNuevo">NUEVO CIERRE</h4>
                          </center>
                        </div>
                        </div>
                    </div>
                  </a>
              </div>



        </div>
    </div>
    <div class="col-xl-9">
          <!-- FILTROS -->
          <div class="row">
                <div class="col-md-12">


                  <div class="panel panel-default">

                      <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                        <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                      </div>

                    <div id="collapseFiltros" class="panel-collapse collapse">

                        <div class="panel-body">

                          <div class="row">
                            <div class="col-xs-4">
                              <h5>Fecha</h5>
                              <div class="form-group">
                                <div class='input-group date' id='dtpFecha' data-link-field="fecha_filtro" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                  <input type='text' class="form-control" id="B_fecha_filtro" value=" "/>
                                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                </div>
                              </div>
                            </div>

                            <div class="col-xs-4">
                                <h5>Mesa</h5>
                                <div class="input-group lista-datos-group">
                                  <input id="filtroMesa" class="form-control" type="text" value="" autocomplete="off">
                                </div>
                            </div> <!-- fin row2 -->

                            <div class="col-xs-4">
                              <h5>Tipo de Archivo</h5>
                              <select class="select" id="tipoArchivo">
                                <option value="1">APERTURAS</option>
                                <option value="2">CIERRES</option>

                              </select>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-xs-4">
                              <h5>Casino</h5>
                              <select class="form-control" name="" id="selectCas" >
                                <option value="0" selected>- Seleccione un Casino -</option>
                                @foreach ($casinos as $cas)
                                <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                                @endforeach
                                <option value="0" >- Todos los Casinos-</option>
                              </select>
                            </div>
                            <div class="col-xs-4">
                              <h5>JUEGO</h5>
                              <select class="form-control" name="" id="selectJuego">
                                <option value="0" selected>- Seleccione un Juego -</option>
                                @foreach ($juegos as $j)
                                <option value="{{$j->id_juego_mesa}}">{{$j->nombre_juego}} - {{$j->casino->codigo}}</option>
                                @endforeach
                                <option value="0" >- Todos los Juegos-</option>
                              </select>
                            </div>
                            <br>
                                <div class="col-md-4" style="padding-top:50px;">
                                     <center>
                                       <button id="btn-buscarCyA" class="btn btn-infoBuscar" type="button" name="button">
                                         <i class="fa fa-fw fa-search"></i> BUSCAR
                                       </button>
                                     </center>
                                </div>
                          </div> <!-- row / botón buscar -->

                        </div> <!-- panel-body -->
                    </div> <!-- collapse -->



                  </div> <!-- .panel-default -->
                </div> <!-- .col-md-12 -->

          </div> <!-- .row / FILTROS -->

          <!-- TABLA -->
          <div class="row">
              <div class="col-md-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h4 id="tablaInicial">APERTURAS</h4>
                  </div>
                  <div class="panel-body">
                    <table id="tablaResultados" class="table  tablesorter " >
                      <thead>
                        <tr align="center" >
                          <th class="activa" apertura="apertura_mesa.fecha" cierre="cierre_mesa.fecha" style="font-size:14px; text-align:center !important;" estado="desc">FECHA  <i class="fas fa-sort-down"></th>
                          <th class="" apertura="mesa_de_panio.nro_mesa" cierre="mesa_de_panio.nro_mesa" style="font-size:14px; text-align:center !important;" estado="">MESA  <i class="fas fa-sort"></th>
                          <th class="" apertura="juego_mesa.siglas" cierre="juego_mesa.siglas" style="font-size:14px; text-align:center !important;" estado="">JUEGO  <i class="fas fa-sort"></th>
                          <th class="" apertura="apertura_mesa.hora" cierre="cierre_mesa.hora_inicio" style="font-size:14px; text-align:center !important;" estado="">HORA <i class="fas fa-sort"></th>
                          <th class="" apertura="moneda.siglas" cierre="moneda.siglas" style="font-size:14px; text-align:center !important;" estado="">MONEDA  <i class="fas fa-sort"></th>
                          <th class="" apertura="casino.nombre" cierre="casino.nombre" style="font-size:14px; text-align:center !important;" estado="">CASINO  <i class="fas fa-sort"></th>
                          <th class="" apertura="apertura_mesa.id_estado_cierre" cierre="cierre_mesa.id_estado_cierre" id="estado_ocultar" style="font-size:14px; text-align:center !important;">ESTADO  <i class="fas fa-sort"></th>
                          <th class="" style="font-size:14px; text-align:center !important;">ACCIÓN</th>
                        </tr>
                      </thead>
                      <tbody  id='cuerpoTablaCyA' >
                        @foreach($aperturas as $a)
                        <tr id="{{$a->id_apertura_mesa}}">

                        <td class=""  style="text-align:center !important;">{{$a->fecha}}</td>
                        <td class=""  style="text-align:center !important;">{{$a->nro_mesa}}</td>
                        <td class=""  style="text-align:center !important;">{{$a->nombre_juego}}</td>
                        <td class=""  style="text-align:center !important;">{{$a->hora}}</td>
                        <td class=""  style="text-align:center !important;">{{$a->siglas_moneda}}</td>
                        <td class=""  style="text-align:center !important;">{{$a->nombre}}</td>

                        @if($a->id_estado_cierre == 3)
                          <td class="" style="text-align:center !important">  <i class="fa fa-fw fa-check"   align="center" style="color: #4CAF50;text-align:center !important;"></i></td>
                        @else
                          <td class="" style="text-align:center !important">  <i class="fas fa-fw fa-times"  align="center" style="color: #D32F2F;text-align:center !important;"></td>
                        @endif

                        <td class="" style="text-align:center !important;">
                          @if($a->id_estado_cierre == 3)
                          <button type="button" class="btn btn-info infoCyA" value="{{$a->id_apertura_mesa}}" data-tipo="apertura">
                                  <i class="fa fa-fw fa-search-plus"></i>
                          </button>
                          @else
                          <button type="button" class="btn btn-info infoCyA" value="{{$a->id_apertura_mesa}}" data-tipo="apertura">
                                  <i class="fa fa-fw fa-search-plus"></i>
                          </button>
                          <button type="button" class="btn btn-warning modificarCyA" value="{{$a->id_apertura_mesa}}" data-tipo="apertura">
                                  <i class="fas fa-fw fa-pencil-alt"></i>
                          </button>
                          <button type="button" class="btn btn-success validarCyA" value="{{$a->id_apertura_mesa}}" data-tipo="apertura">
                                  <i class="fa fa-fw fa-check"></i>
                          </button>
                          @endif
                          <?php
                            $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
                          ?>
                          @if(($usuario['usuario']->es_superusuario))
                          <button type="button" class="btn btn-success eliminarCyA" value="{{$a->id_apertura_mesa}}" data-tipo="apertura">
                                  <i class="fa fa-fw fa-trash"></i>
                          </button>
                          @endif

                        </td>

                      </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>

                  <div class="table-responsive" style="display:none">


                  <table  class="table">
                      <tr id="moldeFilaCyA" class="filaClone" style="display:none">
                        <td class=" L_fecha"  style="text-align:center !important;"></td>
                        <td class=" L_mesa"   style="text-align:center !important;"></td>
                        <td class=" L_juego"  style="text-align:center !important;"></td>
                        <td class=" L_hora"   style="text-align:center !important;"></td>
                        <td class=" L_moneda" style="text-align:center !important;"></td>
                        <td class=" L_casino" style="text-align:center !important;"></td>
                        <td class=" L_estado" style="text-align:center !important;"></td>

                        <td class="" style="text-align:center !important;">
                          <button type="button" class="btn btn-info infoCyA" value="" data-toggle:"tooltip"
                                  data-placement:"top" title: "VER MÁS" data-delay:"{show:300, hide:100}">
                                  <i class="fa fa-fw fa-search-plus"></i>
                          </button>
                          <button type="button" class="btn btn-warning modificarCyA" value="">
                                  <i class="fas fa-fw fa-pencil-alt"></i>
                          </button>
                          <button type="button" class="btn btn-success validarCyA" value="">
                                  <i class="fa fa-fw fa-check"></i>
                          </button>
                          <?php
                             $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
                           ?>
                           @if($usuario['usuario']->elimina_cya)
                           <button type="button" class="btn btn-success eliminarCyA" value="" data-tipo="">
                                   <i class="fa fa-fw fa-trash"></i>
                           </button>
                           @endif
                        </td>
                      </tr>
                  </table>
                  </div>
                  <legend></legend>
                    <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                  </div>
                  </div>
                </div>
          </div>
    </div> <!-- .row / TABLA -->




</div> <!-- col-xl-3 | COLUMNA DERECHA - BOTONES -->

<!-- Modal Relevamientos -->
<div class="modal fade" id="modalRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content">
           <div class="modal-header" style="background-color:#1DE9B6;">
             <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
             <h3 class="modal-title">| GENERANDO RELEVAMIENTO</h3>
            </div>

            <div  id="colapsadoNuevo" class="collapse in">

            <div class="modal-body modalCuerpo" >


              <div id="iconoCarga" class="sk-folding-cube">
                <div class="sk-cube1 sk-cube"></div>
                <div class="sk-cube2 sk-cube"></div>
                <div class="sk-cube4 sk-cube"></div>
                <div class="sk-cube3 sk-cube"></div>
              </div>

            </div>
          </div>
        </div>
      </div>
</div>


<!-- MODAL CARGA cierre -->
<div class="modal fade" id="modalCargaCierre" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" >
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1DE9B6;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| CARGA CIERRE </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row" style="border-bottom:2px solid #ccc;">
            <div class="col-md-4">
              <h6>FECHA</h6>
              <div class="form-group">
                <div class='input-group date' id='dtpfechaCierre' data-link-field="fecha_cierre" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="Fecha de Cierre" id="B_fecha_cie" value=" "/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <div class="col-xs-4">
              <h6>CASINO</h6>
              <select class="form-control" name="" id="casinoCierre" >
                <option value="0" selected>- Seleccione un Casino -</option>
                @foreach ($casinos as $cas)
                <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                @endforeach
              </select>
            </div>
            <br>
            <br>

            <div class="col-xs-4">
              <button type="button" id="confirmarCierre" class="btn btn-infoBuscar" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;">SIGUIENTE</button>
            </div>
            <br>
            <br>
          </div>

          <br>
          <br>

          <div class="row desplegable" hidden>
            <br>
          <div class="row">
            <div class="col-md-6" id=inputAgregarMesaC>
              <h6 id="agregamesac">Agregar Mesa</h6>
              <div class="row">
                <div class="input-group ">
                  <input id="inputMesaCierre" class="form-control" type="text" value="" autocomplete="off" placeholder="Nro. de Mesa" >
                  <span class="input-group-btn">
                    <button id="agregarMesaCierre" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <h6>FISCALIZADOR DE CARGA</h6>
              <input id="fiscalizadorCierre" class="form-control" type="text" value=""  size="100" readonly="true">
            </div>
          </div>

          <div class="row">
            <div class="col-xs-3 listMes"  hidden="true">
              <h6><b>MESAS</b></h6>
              <table id="listaMesasCierres" class="table">
                <thead>
                  <tr>
                    <th class="col-xs-4"  style=" border-right:2px solid #ccc;">NRO</th>
                    <th class="col-xs-2"> </th>
                    <th class="col-xs-2"> </th>
                  </tr>

                </thead>
                <tbody>
                </tbody>
              </table>
            </div> <!-- tablafechas -->
            <div id="mensajeExitoCargaCie" class="col-xs-8" hidden>
              <br>
              <span style="font-family:'Roboto-Black'; font-size:16px; color:#4CAF50;">EXITO</span>
              <br>
              <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">El cierre ha sido guardado correctamente. </span>
            </div> <!-- mensaje -->
                <div id="columnaDetalleCie" class="col-xs-9" style="border-left:2px solid #ccc; border-right:2px solid #ccc;" hidden="true" >
                  <h6 style="border-bottom:1px solid #ccc"><b>DETALLES</b></h6>
                  <br>
                  <div class="detalleMesaCie">
                    <form id="frmCargaCierres" name="frmCargaCierres" class="form-horizontal" novalidate="">
                      <div class="row">
                        <div class="col-md-4">
                          <h6>MONEDA</h6>
                          @foreach($monedas as $moneda)
                            <input type="radio" name="moneda" style="margin-left:15px !important" value="{{$moneda->id_moneda}}"><span style="font-family: Roboto-Regular; padding-left:10px;">{{$moneda->descripcion}}</span> <br>
                          @endforeach
                        </div>
                        <div class="col-md-4">
                          <h6>HORA DE APERTURA</h6>
                          <input type="time" name="hora_cierre" class="form-control" style="padding-top:0px" value="" format="hh:mm" id="horario_ini_c">
                          <br>
                        </div>

                        <div class="col-md-4">
                          <h6>HORA CIERRE</h6>
                          <input type="time" name="hora_CC" class="form-control" value="" format="hh:mm" style="padding-top:0px" id="horarioCie">

                          <br>
                        </div>
                        <div class="col-md-4">
                          <h6>JUEGO</h6>
                          <div class="row">
                            <div class="input-group lista-datos-group">
                              <input id="juegoCierre" class="form-control" type="text" value="" size="100" autocomplete="off" >
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <br>
                    <h6 align="center">FICHAS</h6>
                      <div class="row">
                        <div class="col-xs-6" >
                          <table text-align="center" class="table" id="tablaCargaCierreF">
                            <thead>
                              <tr class="col-xs-12">
                                <th class="col-xs-6" align="center"><h6>VALOR</h6></th>
                                <th class="col-xs-6" align="center" style="padding-left:70px"><h6>MONTO</h6></th>
                              </tr>
                            </thead>

                            <tbody id="bodyFichasCierre" >
                            </tbody>

                          </table>
                          <table>
                            <tbody>
                              <tr id="clonCierre" style="display:none">
                                <td><input type="text" value="" readonly="true" class="col-xs-6 form-control fichaValCC"></td>
                                <td><input type="text" class="col-xs-6 form-control inputCie" id="input" val=""></td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                        <br>
                        <br>
                        <div class="col-xs-6">
                          <h6><b>TOTAL: </b></h6>
                          <input id="totalCierre" type="text" value="" readonly="true" display="inline">
                          <button id="recalcular" type="button" name="button"><i class="fas fa-redo-alt"></i></button>

                          <h6><b>TOTAL ANTICIPOS ($): </b></h6>
                          <input id="totalAnticipoCierre" type="text" value="">

                        </div>
                      </div>
                        <br>
                    </div>
                    </div>
                  </form>
                </div>
              </div>


          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="btn-guardar-cierre" value="nuevo" hidden="true">GUARDAR</button>
            <button type="button" class="btn btn-default" id="btn-finalizar-cierre" hidden="true">FINALIZAR</button>
          </div>
          <input type="text" id="id_mesa_panio" name="" value="" hidden>
          <div id="mensajeCargaConError" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Debe completar todos los campos.</span>
          </div> <!-- mensaje -->
          <div id="mensajeErrorMoneda" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">La moneda no se corresponde con la mesa.</span>
          </div> <!-- mensaje -->
          <div id="mensajeFichasError2" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique los valores ingresados para cada ficha.</span>
          </div> <!-- mensaje -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DE DETALLES DE CIERRE -->
<div class="modal fade" id="modalDetalleCierre" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#4FC3F7;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| DETALLES DE CIERRE </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-md-12">
              <div class="col-md-4" align="center" style=" border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                <h6>MESA</h6>
                <div class="col-xs-2 col-xs-offset-1 iconoMesa">
                    <i class="fas fa-clipboard-check fa-2x" style="position:relative; left:-1px;"></i>
                </div>
                <div class="col-xs-6" align="center">
                  <h5 class="mesa_det_cierre" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
                </div>
              </div>
              <div class="col-md-4" align="center" style=" border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                <h6>JUEGO</h6>
                <div class="col-xs-2 col-xs-offset-1 iconoJuego">
                    <i class="fas fa-dice fa-2x" style="position:relative; left:-1px;"></i>
                </div>
                <div class="col-xs-6" align="center">
                  <h5 class="juego_det_cierre" style="color: #000 !important; font-size: 14px;"></h5>
                </div>
              </div>
              <div class="col-md-4" align="center" style=" border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                <h6>FISCALIZADOR DE CARGA</h6>
                <div class="col-xs-2 col-xs-offset-1 iconoCargadorCi">
                    <i class="far fa-user fa-2x" style="position:relative; left:-1px;"></i>
                </div>
                <div class="col-xs-6" align="center">
                  <h5 class="cargador_det_cierre" style="color: #000 !important; font-size: 14px;"></h5>
                </div>
              </div>

            </div>
          </div>
          <div class="row">
            <div class="col-md-12" style="border-top:2px solid #ccc; border-bottom:2px solid #ccc;">
                <div class="col-md-4" align="center" style="border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                  <h6>HORA APERTURA</h6>
                  <div class="row ">

                  <div class="col-xs-2 col-xs-offset-1 iconoFecha">
                      <i class="far fa-clock fa-2x" style="position:relative; left:-1px;"></i>
                  </div>
                  <div class="col-xs-6" align="center">
                      <h5 class="inicio_cierre_det" style="color: #000 !important; font-size: 14px;" >10:20 H</h5>
                  </div>
                  </div>
                  <br>
                </div>
                <div class="col-md-4" align="center" style="border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                  <h6>HORA CIERRE</h6>
                  <div class="row">
                  <div class="col-xs-2 col-xs-offset-1 iconoFecha">
                      <i class="far fa-clock fa-2x" style="position:relative; left:-1px;"></i>
                  </div>
                  <div class="col-xs-6" align="center">
                      <h5 class=" hora_cierre_det" style="color: #000 !important; font-size: 14px;">10:20 H</h5>
                  </div>
                </div>
                  <br>
                </div>
                <div class="col-md-4" align="center" style="padding-top:30px; padding-bottom:30px;">
                  <h6>FECHA DE PRODUCCIÓN</h6>
                  <div class="row">
                    <div class="col-xs-2 col-xs-offset-1 iconoCalendarr">
                        <i class="far fa-calendar-alt fa-2x" style="position:relative; left:-1px;"></i>
                    </div>
                    <div class="col-xs-6" align="center">
                        <h5 class=" hora_cierre_det" style="color: #000 !important; font-size: 14px;">10:20 H</h5>
                    </div>
                  </div>
                </div>
            </div>
          </div>
          <br>
          <div class="row" style="border-bottom:1px solid #ccc; text-align:center;">
            <h3 align="center" style="padding-bottom:20px; display:inline;position:relative;top:-2px;">DATOS GENERALES</h3><i class="fas fa-info-circle" style="font-size:30px;"></i>
            <br>
            <br>
          </div>
          <div class="row" style="border-bottom:1px solid #ccc; ">
            <div class="col-md-6">
              <h6 align="center">FICHAS</h6>
            <table  style="border-collapse: separate;" align="center" class="table table-striped">
              <thead>
                <tr>
                  <th style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;border-bottom:1px solid #ccc;">
                    <h5 align="center" style="font-size: 15px; color:#000;">Valor</h5>
                  </th>
                  <th style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px; border-bottom:1px solid #ccc;">
                    <h5 align="center" style="font-size: 15px; color:#000;">Monto</h5>
                  </th>
                </tr>
              </thead>
              <tbody id="datosCierreFichas" align="center" style="border-spacing: 7px 7px;">
              </tbody>
            </table>

          </div>
            <style>
                #vertical-bar {
                                border-left: 1px solid #ccc;
                                width:1px;
                                height:300px;
                              }
                h8 {
                  color: black;
                  font-family: Roboto-Regular;
                  text-transform: uppercase;
                  font-size: 16px;
                  padding-top: 3px;
                  border-bottom: 1px solid: #ccc;
                }
           </style>


            <div class="col-md-6" id="vertical-bar">
              <div class="row">
                <div class="col-md-6">
                  <h6>TOTAL ($):</h6>
                  <input type="text" id="total_detalle" value="" readonly="true">
                </div>
                <div class="col-md-6">
                  <h6>TOTAL ANTICIPOS ($):</h6>
                  <input type="text" id="anticipos_detalle" value="" readonly="true">
                </div>
              </div>
            </div>
          </div>
          <div class="row" style="border-bottom:1px solid #ccc; text-align:center;">
            <br>
              <h3 align="center" style="display:inline;position:relative;top:-2px;" align="center">DATOS APERTURA</h3> <i class="fas fa-clipboard-check" style="font-size:30px;"></i>
              <br>
              <br>
          </div>
          <br>
          <div class="row" style="border-top: 1px solid: #ccc;">
            <div class="col-md-8" align="center">
              <table style="border-collapse: separate;" class="table table-striped">
                <thead>
                  <tr>
                    <th style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;border-bottom:1px solid #ccc;">
                      <h5 align="center" style="font-size: 15px; color:#000;">Valor</h5>
                    </th>
                    <th style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px; border-bottom:1px solid #ccc;">
                      <h5 align="center" style="font-size: 15px; color:#000;">Cantidad</h5>
                    </th>
                  </tr>
                </thead>
                <tbody id="datosCierreFichasApertura" style="border-spacing: 7px 7px;" align="center">

                </tbody>

              </table>
            </div>
            <br>
            <br>
            <div class="col-md-4">
              <div class="row" >
                <br>
                <h6>TOTAL APERTURA ($):</h6>
                <input type="text" id="totalA_det_cierre" value="" readonly="true">
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL MODIFICAR CIERRE -->
<div class="modal fade" id="modalModificarCierre" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width: 80%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#FFA726;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true"
                data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title">| MODIFICAR CIERRE</h3>
              </div>
          <div  id="colapsado" class="collapse in">
            <br>
            <div class="row" style="padding-bottom: 10px">

                <div class="col-xs-3" style:"display:inline">
                  <h6 class="linea">Casino:</h6>
                  <input type="text" name="" class="form-control linea cas_cierre" value="" readonly="true">
                </div>
                <div class="col-xs-3">
                  <h6 class="linea">Fecha: </h6>
                  <input type="text" name="" class="form-control linea f_cierre" value="" readonly="true">
                </div>
                <div class="col-xs-2" style:"display:inline">
                  <h6 class="linea">Mesa:</h6>
                  <input type="text" name="" class="form-control linea nro_cierre" value="" readonly="true">
                </div>
                <div class="col-xs-4">
                  <h6 class="linea">Juego:</h6>
                  <input type="text" name="" class="form-control linea j_cierre" value="" readonly="true">
                </div>
                <br>
                <br>
            </div>
            <br>
            <div class="row" >
              <div class="col-xs-6">
                <h6 text-align="center">Hora de Apertura: </h6>
                <input type="time" name="hora_In_cierre_modif" format="hh:mm" style="padding-top:0px" class="form-control" id="hs_inicio_cierre" value="">

                <br>
              </div>
              <div class="col-xs-6">
                <h6 text-align="center">Hora Cierre: </h6>
                <input type="time" name="hora_cierre_modif" class="form-control" format="hh:mm" style="padding-top:0px" id="hs_cierre_cierre" value="">
                <br>
              </div>
              <br>
            </div>
            <div class="row">

              <div class="col-xs-6">
                <h6 text-align="center" class="linea">Fiscalizador de Carga: </h6>
                <br>
                <div class="">
                  <input class="linea form-control" id="fis_cierre" type="text" value=""  size="100" autocomplete="off">
                </div>
              </div>
              <div class="col-xs-6" style="margin-top:-5px">
                <h6 class="mon_apertura">Moneda: </h6>
                @foreach($monedas as $moneda)
                <input type="radio" name="monedaModCie" style="margin-left:15px !important" value="{{$moneda->id_moneda}}"><span style="font-family: Roboto-Regular; padding-left:10px;">{{$moneda->descripcion}}</span> <br>
                @endforeach
              </div>
            </div>
            <br>
            <br>
            <div class="row">
              <div class="col-xs-6">

                <h6 text-align="center">FICHAS: </h6>
                <table align="center" class="table">
                  <thead>
                    <tr>
                      <th class="col-md-3" style="border-bottom:1px solid #ccc;border-right:1px solid #ccc"><h5 align="center">Valor</h5></th>
                      <th class="col-md-3" style="border-bottom:1px solid #ccc;"><h5 align="center">Monto</h5></th>
                    </tr>
                  </thead>
                  <tbody id="modificarFichasCie">
                  </tbody>
                </table>
              </div>
              <br>
              <div class="col-md-6" align="center">
                <div class="row">
                  <h6><b>TOTAL: </b></h6>
                  <input id="totalModifCie" type="text" value="" readonly="true">
                </div>
                <div class="row">
                  <h6><b>TOTAL ANTICIPO: </b></h6>
                  <input id="totalAnticipoModif" type="text" value="">
                </div>
                <br><br>
              </div>

            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="modificar_cierre" value="nuevo" hidden="true">GUARDAR CAMBIOS</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          <input id="id_cierre" type="text" name="" value="" hidden="true">
        </div>
        <div id="errorModificarCierre" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique los valores cargados.</span>
        </div> <!-- mensaje -->
    </div>
  </div>
</div>


<!-- MODAL CARGA APERTURA -->
<div class="modal fade" id="modalCargaApertura" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1DE9B6;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true"
                data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title">| CARGA APERTURA</h3>
              </div>
          <div  id="colapsado" class="collapse in">
                <div class="modal-body" style="font-family: Roboto;">
                  <div class="row" style="border-bottom:2px solid #ccc;">
                    <div class="col-xs-4">
                      <h6>FECHA</h6>
                      <div class="form-group">
                        <div class='input-group date' id='dtpFechaApert' data-link-field="fecha_apertura" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                          <input type='text' class="form-control" placeholder="Fecha de Apertura" id="B_fecha_apert" value=" "/>
                          <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                          <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                      </div>
                    </div>
                    <div class="col-xs-4">
                      <h6>CASINO</h6>
                      <select class="form-control" name="" id="casinoApertura" >
                        <option value="0" selected>- Seleccione un Casino -</option>
                        @foreach ($casinos as $cas)
                        <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                        @endforeach
                      </select>
                    </div>
                    <br>
                    <br>
                    <div class="col-xs-4">
                      <button type="button" id="confirmar" class="btn btn-infoBuscar" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;">SIGUIENTE</button>
                    </div>

                  </div>
                  <br>
                  <div class="row detallesCargaAp" hidden="true">
                    <div class="row">
                      <div class="col-md-6" id=inputAgregarMesa>
                        <h6 id="agregamesa">Agregar Mesa</h6>
                        <div class="row">
                          <div class="input-group ">
                            <input id="inputMesaApertura" class="form-control" type="text" value="" autocomplete="off" placeholder="Nro. de Mesa" >
                            <span class="input-group-btn">
                              <button id="agregarMesa" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                            </span>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <h6>Fiscalizador de Carga</h6>
                        <input type="text" id="cargador" class="form-control" value="" data-cargador="" readonly="true">
                      </div>

                    </div>
                    <div class="row" >
                      <div class="col-xs-4">
                        <h6><b>MESAS</b></h6>
                        <table id="tablaMesasApert" class="table">
                          <thead>
                            <tr>
                              <th class="col-xs-4"  style=" border-right:2px solid #ccc;">NRO</th>
                              <th class="col-xs-4">JUEGO</th>
                              <th class="col-xs-2"> </th>
                              <th class="col-xs-2"> </th>
                            </tr>

                          </thead>
                          <tbody id="bodyMesas" >
                          </tbody>
                        </table>
                      </div> <!-- tablafechas -->
                      <div id="mensajeExitoCargaAp" class="col-xs-8" hidden>
                        <br>
                        <span style="font-family:'Roboto-Black'; font-size:16px; color:#4CAF50;">EXITO</span>
                        <br>
                        <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">La apertura ha sido guardada correctamente. </span>
                      </div> <!-- mensaje -->
                          <div id="columnaDetalle" class="col-xs-8" style="border-left:2px solid #ccc; border-right:2px solid #ccc;" hidden="true" >
                            <h6><b>DETALLES</b></h6>
                            <div class="detalleMesa">
                              <form id="frmCargaProducidos" name="frmCargaProducidos" class="form-horizontal" novalidate="">
                                <div class="row">
                                  <div class="col-xs-3">
                                    <h6>MONEDA</h6>
                                    @foreach($monedas as $moneda)
                                      <input type="radio" name="monedaApertura" style="margin-left:15px !important" value="{{$moneda->id_moneda}}"><span style="font-family: Roboto-Regular; padding-left:10px;">{{$moneda->descripcion}}</span> <br>
                                    @endforeach
                                    </div>
                                  <div class="col-xs-4">
                                    <h6>HORA DE APERTURA</h6>
                                    <input type="time" name="hora_apertura" class="form-control" format="hh:mm" style="padding-top:0px" value="" id="horarioAp">
                                    <br>
                                  </div>
                                  <div class="col-xs-5">
                                    <h6>FISCALIZADOR DE TOMA </h6>
                                      <input id="fiscalizApertura" class="form-control" type="text" value=""  size="100" autocomplete="off">
                                    <br>
                                  </div>
                                </div>
                                <br>
                                <div class="row">
                                    <h6 align="center">FICHAS</h6>
                                    <div class="row">
                                      <div class=" col-xs-6">
                                    <table id="tablaCargaApertura">
                                      <thead >
                                        <tr class="col-xs-6">
                                          <th><h6 class="col-xs-6" style="padding-left:30px">VALOR</h6></th>
                                          <th><h6 class="col-xs-6" style="padding-left:70px">CANTIDAD</h65></th>
                                        </tr>
                                      </thead>
                                      <tbody id="bodyCApertura">

                                      </tbody>
                                    </table>
                                    <table>
                                      <tbody>
                                        <tr id="filaFichasClon" style="display:none">
                                          <td><input type="text" value="" readonly="true" class="col-xs-6 form-control fichaVal"></td>
                                          <td><input type="text" class="col-xs-6 form-control inputApe" id="input" val="" pattern="[[^0-9]*"></td>
                                        </tr>
                                      </tbody>
                                    </table>
                                    </div>


                                <div class="col-xs-4" >
                                  <br>
                                  <h6 align="center">TOTAL:</h6><input id="totalApertura" type="text" class="form-control" value="" style="display:inline-block !important;" readonly>
                                  <button id="recalcularApert" type="button" name="button"><i class="fas fa-redo-alt"></i></button>
                                  <br>
                                </div>
                              </div>

                              </div>

                              </div>
                            </form>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-successAceptar" id="btn-guardar-apertura" value="nuevo" hidden="true">GUARDAR</button>
                          <button type="button" class="btn btn-default" id="btn-finalizar-apertura" hidden>FINALIZAR</button>
                          <input type="text" id="id_mesa_ap" name="" value="" hidden>
                        </div>

                        <div id="mensajeErrorCargaAp" hidden>
                          <br>
                          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
                          <br>
                          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Debe completar todos los campos.</span>
                        </div> <!-- mensaje -->


                      </div>

                    </div>
          </div>
    </div>
  </div>
</div>



<!--MODAL DE DETALLES DE APERTURA -->
<div class="modal fade" id="modalDetalleApertura" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header" style="background-color:#4FC3F7;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true"
                data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| DETALLE APERTURA</h3>
      </div>

      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">

          <div class="row" style="border-bottom:1px solid #ccc;border-top:1px solid #ccc">
            <div class="col-md-4" align="center">
              <h6>MESA</h6>
              <div class="col-xs-2 col-xs-offset-1 iconoMESAAp">
                  <i class="fas fa-clipboard-check fa-2x" style="position:relative; left:-1px;"></i>
              </div>
              <h5 class="mesa_det_apertura" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
              <br>
            </div>
            <div class="col-md-4" align="center" style="border-left:1px solid #ccc">
              <h6>FECHA PRODUCCIÓN</h6>
              <div class="col-xs-2 col-xs-offset-1 iconoFechaAp">
                  <i class="far fa-calendar-alt fa-2x" style="position:relative; left:-1px;"></i>
              </div>
              <h5 class="fecha_det_apertura" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
            </div>
            <div class="col-md-4" align="center" style="border-left:1px solid #ccc">
              <h6>JUEGO</h6>
              <div class="col-xs-2 col-xs-offset-1 iconoJuegoAp">
                  <i class="fas fa-dice fa-2x" style="position:relative; left:-1px;"></i>
              </div>
              <h5 class="juego_det_apertura" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
            </div>
          </div>
          <br>
          <div class="row" style="border-bottom:1px solid #ccc">
            <div class="col-md-4" align="center">
              <h6>HORA</h6>
              <div class="row ">
                <div class="col-xs-2 col-xs-offset-1 iconoHoraAp">
                  <i class="far fa-clock fa-2x" style="position:relative; left:-1px;"></i>
                </div>
                <div class="col-xs-9">
                  <h5 class="hora_apertura_det" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
                </div>
              </div>
              <br>
            </div>
            <div class="col-md-4" align="center" style="border-left:1px solid #ccc">
              <h6>FISCALIZADOR DE TOMA</h6>
              <div class="row ">
              <div class="col-xs-2 col-xs-offset-1 icoFiscaDetA">
                  <i class="far fa-user fa-2x" style="position:relative; left:-1px;"></i>
              </div>
              <div class="col-xs-6" align="center">
                  <h5 class="fisca_det_apertura" style="color: #000 !important; font-size: 14px;" >10:20 H</h5>
              </div>
              </div>
              <br>
            </div>
            <div class="col-md-4" align="center" style="border-left:1px solid #ccc">
              <h6>FISCALIZADOR DE CARGA</h6>
              <div class="row ">
              <div class="col-xs-2 col-xs-offset-1 iconoCargadorDetA">
                  <i class="far fa-user fa-2x" style="position:relative; left:-1px;"></i>
              </div>
              <div class="col-xs-6" align="center">
                  <h5 class="cargador_det_apertura" style="color: #000 !important; font-size: 14px;" >10:20 H</h5>
              </div>
              </div>
            </div>
            <br>
          </div>
          <div class="row" style="border-bottom:1px solid #ccc; text-align:center;">
            <br>
            <h3 align="center" style="padding-bottom:20px; display:inline;position:relative;top:-2px;">DATOS GENERALES</h3><i class="fas fa-info-circle" style="font-size:30px;"></i>
            <br>
            <br>
          </div>
          <div class="row" align="center">
              <h6>FICHAS</h6>
              <br><br>
          </div>
            <div class="row"align="center">
              <div class="col-xs-8" >
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;border-bottom:1px solid #ccc;">
                        <h5 align="center" style="font-size: 15px; color:#000;">Valor</h5>
                      </th>
                      <th style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px; border-bottom:1px solid #ccc;">
                        <h5 align="center" style="font-size: 15px; color:#000;">Cantidad</h5>
                      </th>
                    </tr>
                  </thead>

                  <tbody id="bodyFichasDetApert" align="center">
                  </tbody>
                </table>
              </div>
            <div class="col-xs-4" >
              <br>
              <h6>TOTAL ($):</h6><input id="totalAperturaDet" type="text" class="form-control" value="" readonly>
              <br>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
      </div>

    </div>
  </div>
</div>

<!-- ELIMINA LOS SALTOS DE LINEA  -->
<style media="screen">
.linea  {
  padding: 10px;
  display: inline;
  }
</style>

<!-- MODAL MODIFICAR APERTURA -->
<div class="modal fade" id="modalModificarApertura" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width: 75%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#FFA726;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true"
                data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title">| MODIFICAR APERTURA</h3>
              </div>
          <div  id="colapsado" class="collapse in">
            <br>
            <div class="row">
                <div class="col-xs-4">
                  <h6 class="linea">Fecha: </h6>
                  <input type="text" name="" class="form-control linea f_apertura" value="" readonly="true">
                </div>

                <div class="col-xs-4" style:"display:inline">
                  <h6 class="linea">Casino: </h6>
                  <input type="text" name="" class="form-control linea cas_apertura" value="" readonly="true">
                </div>

                <div class="col-xs-4" style:"display:inline">
                  <h6 class="linea">Mesa: </h6>
                  <input type="text" name="" class="form-control linea nro_apertura" value="" readonly="true">

                </div>
            </div>
            <br>
            <br>
            <div class="row" >
                <div class="col-xs-4">
                  <h6 class="linea">Juego: </h6>
                  <input type="text" name="" class="form-control linea j_apertura" value="" readonly="true">
                </div>
                <div class="col-xs-4">
                  <h6 class="linea">Fiscalizador de Carga: </h6>
                  <input type="text" name="" class="form-control linea car_apertura" value="" readonly="true">
                </div>
            </div>
            <br>
            <div class="row">
              <div class="col-xs-4">
                <h6 text-align="center">Hora de Apertura: </h6>
                <input type="time" class="form-control" name="hora_apertura_modif" format="hh:mm" style="padding-top:0px" id="hs_apertura" value="">
              </div>
              <br>
              <div class="col-xs-4">
                <h6 text-align="center" class="linea">Fiscalizador de Toma: </h6>
                <input class="linea form-control" id="fis_apertura" type="text" value=""  size="100" autocomplete="off">
              </div>
              <div class="col-xs-4" style="margin-top:-10px">
                <h6 class="mon_apertura">Moneda: </h6>
                @foreach($monedas as $moneda)
                <input type="radio" name="monedaModApe" style="margin-left:15px !important" value="{{$moneda->id_moneda}}"><span style="font-family: Roboto-Regular; padding-left:10px;">{{$moneda->descripcion}}</span> <br>
                @endforeach
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-xs-6">

                <h6>FICHAS: </h6>
                <table align="center" class="table">
                  <thead>
                    <tr>
                      <th class="col-md-3" style="border-bottom:1px solid #ccc;border-right:1px solid #ccc"><h5 align="center">Valor</h5></th>
                      <th class="col-md-3" style="border-bottom:1px solid #ccc;"><h5 align="center">Cantidad</h5></th>
                    </tr>
                  </thead>
                  <tbody id="modificarFichasAp">
                  </tbody>
                </table>
              </div>
              <br>
              <div class="col-md-6" align="center">
                <div class="row">
                  <h6><b>TOTAL: </b></h6>
                  <input id="totalModifApe" type="text" value="" readonly="true">
                </div>
              </div>

            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="modificar_apertura" value="nuevo" hidden="true">GUARDAR CAMBIOS</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          <input id="id_apertura" type="text" name="" value="" hidden="true">
        </div>
        <div id="errorModificar" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique los valores cargados.</span>
        </div> <!-- mensaje -->
    </div>
  </div>
</div>

<style media="screen">
  .estilotextarea4 {
    background-color: transparent;
    border: 1px solid #000000;
    height: 100%;
    width: 100%;
    scrollbar-arrow-color: #000066;
    scrollbar-base-color: #000033;
    scrollbar-dark-shadow-color: #336699;
    scrollbar-track-color: #666633;
    scrollbar-face-color: #cc9933;
    scrollbar-shadow-color: #DDDDDD;
    scrollbar-highlight-color: #CCCCCC;}
</style>

<!-- MODAL VALIDAR APERTURA -->
<div class="modal fade" id="modalValidarApertura" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width:50%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1DE9B6;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| VALIDAR APERTURA </h3>
      </div>
      <!-- colapsado -->
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">

          <!-- row de seleccion de fecha -->
          <div class="row" style=" border-bottom:2px solid #ccc; padding-bottom:20px">
            <div class="col-xs-5">
              <h6 display="inline-block">Seleccione un Cierre para validar esta Apertura:</h6>
            </div>
            <div class="col-xs-4" >
              <select class="form-control" display="inline-block" style="padding-right:40px" name="selFecha" id="fechaCierreVal">
                <option value="0" selected class="defecto">- Seleccione una Fecha -</option>
              </select>
            </div>
            <div class="col-xs-3" style="padding-left:20px;padding-bottom:10px;align:center">
              <button type="button" style="width:120px; height:40px; padding-top:0.5px;" class="btn btn-success comparar" > <h6>COMPARAR</h6> </button>
            </div>
            <br>
            <br>
          </div>

          <!-- datos del cierre -->
          <div class="row" id="div_cierre" style="border-bottom:2px solid #ccc;" hidden>
            <div class="col-md-1" align="center" >
              <h1 style="padding-top:110px;padding-bottom:134px;font-family:'Roboto-Black';">C</h1>
            </div>
            <div class="col-md-11">
              <div class="row">
                <div class="col-md-12">
                  <div class="col-md-4" align="center" style=" border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                    <h6>MESA</h6>
                    <div class="col-xs-2 col-xs-offset-1 iconoMesa">
                        <i class="fas fa-clipboard-check fa-2x" style="position:relative; left:-1px;"></i>
                    </div>
                    <div class="col-xs-6" align="center">
                      <h5 class="nro_validar" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
                    </div>
                  </div>
                  <div class="col-md-4" align="center" style=" border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                    <h6>JUEGO</h6>
                    <div class="col-xs-2 col-xs-offset-0 iconoJuego">
                        <i class="fas fa-dice fa-2x" style="position:relative; left:-1px;"></i>
                    </div>
                    <div class="col-xs-6" align="center">
                      <h5 class="j_validar" style="color: #000 !important; font-size: 14px;"></h5>
                    </div>
                  </div>
                  <div class="col-md-4" align="center" style="padding-top:30px; padding-bottom:30px;">
                    <h6>CASINO</h6>
                    <div class="row">
                      <div class="col-xs-2 col-xs-offset-1 iconoFecha">
                        <span class="icono" style="padding-bottom: 56px; position:relative; left:-1px; size:2px">
                          @svg('casinos','iconoCasinos')
                        </span>
                      </div>
                      <div class="col-xs-6" align="center">
                          <h5 class="cas_validar" style="color: #000 !important; font-size: 14px;">10:20 H</h5>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
              <div class="row">
                <div class="col-md-12" style="border-top:2px solid #ccc; ">
                    <div class="col-md-4" align="center" style="border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                      <h6>HORA APERTURA</h6>
                      <div class="row ">

                      <div class="col-xs-2 col-xs-offset-1 iconoFecha">
                          <i class="far fa-clock fa-2x" style="position:relative; left:-1px;"></i>
                      </div>
                      <div class="col-xs-6" align="center">
                          <h5 class="hs_inicio_validar" style="color: #000 !important; font-size: 14px;" >10:20 H</h5>
                      </div>
                      </div>
                      <br>
                    </div>
                    <div class="col-md-4" align="center" style="border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                      <h6>HORA CIERRE</h6>
                      <div class="row">
                      <div class="col-xs-2 col-xs-offset-1 iconoFecha">
                          <i class="far fa-clock fa-2x" style="position:relative; left:-1px;"></i>
                      </div>
                      <div class="col-xs-6" align="center">
                          <h5 class=" hs_cierre_validar" style="color: #000 !important; font-size: 14px;">10:20 H</h5>
                      </div>
                    </div>
                      <br>
                    </div>
                    <div class="col-md-4" align="center" style="padding-top:30px; padding-bottom:30px;">
                      <h6>FECHA CIERRE</h6>
                      <div class="row">
                        <div class="col-xs-2 col-xs-offset-1 iconoCalendarr">
                            <i class="far fa-calendar-alt fa-2x" style="position:relative; left:-1px;"></i>
                        </div>
                        <div class="col-xs-6" align="center">
                            <h5 class="f_validar" style="color: #000 !important; font-size: 14px;">10:20 H</h5>
                        </div>
                      </div>
                    </div>
                </div>
              </div>
            </div>
          </div>

          <!-- datos de la apertura -->
          <div class="row">
            <div class="col-md-1" align="center" style=" border-bottom:2px solid #ccc;">
              <h1 style="padding-top:110px;padding-bottom:134px;font-family:'Roboto-Black';">A</h1>
            </div>
            <div class="col-md-11">
              <div class="row">
                <div class="col-md-12">
                  <div class="col-md-4" align="center" style=" border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                    <h6>HORA APERTURA</h6>
                    <div class="col-xs-2 col-xs-offset-1 iconoMesa">
                        <i class="fas fa-clipboard-check fa-2x" style="position:relative; left:-1px;"></i>
                    </div>
                    <div class="col-xs-6" align="center">
                      <h5 class="hs_validar_aper" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
                    </div>
                  </div>
                  <div class="col-md-4" align="center" style=" border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                    <h6>FECHA APERTURA</h6>
                    <div class="col-xs-2 col-xs-offset-0 iconoJuego">
                        <i class="fas fa-dice fa-2x" style="position:relative; left:-1px;"></i>
                    </div>
                    <div class="col-xs-6" align="center">
                      <h5 class="fechaAp_validar_aper" style="color: #000 !important; font-size: 14px;"></h5>
                    </div>
                  </div>
                  <div class="col-md-4" align="center" style="padding-top:30px; padding-bottom:30px;">
                    <h6>FISCALIZADOR DE TOMA</h6>
                    <div class="col-xs-2 col-xs-offset-1 iconoFiscalizador">
                        <i class="far fa-user fa-2x" style="position:relative; left:-1px;"></i>
                    </div>
                    <div class="col-xs-6" align="center">
                      <h5 class="fis_validar_aper" style="color: #000 !important; font-size: 14px;"></h5>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12" style="border-top:2px solid #ccc; border-bottom:2px solid #ccc;">
                    <div class="col-md-4" align="center" style="border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                      <h6>FISCALIZADOR DE CARGA</h6>
                      <div class="row ">

                      <div class="col-xs-2 col-xs-offset-1 iconoCargador">
                          <i class="far fa-user fa-2x" style="position:relative; left:-1px;"></i>
                      </div>
                      <div class="col-xs-6" align="center">
                          <h5 class="car_validar_aper" style="color: #000 !important; font-size: 14px;" >10:20 H</h5>
                      </div>
                      </div>
                      <br>
                    </div>
                    <div class="col-md-4" align="center" style="border-right:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                      <h6>TIPO MESA</h6>
                      <div class="row">
                      <div class="col-xs-2 col-xs-offset-1 iconoTMesa">
                          <i class="fas fa-info-circle fa-2x" style="position:relative; left:-1px;"></i>
                      </div>
                      <div class="col-xs-6" align="center">
                          <h5 class="tipo_validar_aper" style="color: #000 !important; font-size: 14px;">10:20 H</h5>
                      </div>
                    </div>
                      <br>
                    </div>
                    <div class="col-md-4" align="center" style="padding-top:30px; padding-bottom:30px;">
                      <h6>TIPO MONEDA </h6>
                      <div class="row">
                        <div class="col-xs-2 col-xs-offset-1 iconoMonedaV">
                            <i class="fas fa-hand-holding-usd fa-2x" style="position:relative; left:-1px;"></i>
                        </div>
                        <div class="col-xs-6" align="center">
                            <h5 class="mon_validar_aper" style="color: #000 !important; font-size: 14px;">10:20 H</h5>
                        </div>
                      </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
          <br>
          <div class="row" style="border-bottom:1px solid #ccc; text-align:center;">
            <h3 align="center" style="padding-bottom:20px; display:inline;position:relative;top:-2px;">DATOS GENERALES</h3><i class="fas fa-info-circle" style="font-size:30px;"></i>
            <br>
            <br>
          </div>
          <div class="row" style="border-bottom:1px solid #ccc;">
              <h6 align="center">FICHAS</h6>
            <table  style="border-collapse: separate;"  class="table table-striped" align="center" id="tablaValidar">
              <thead>
                <tr>
                  <th class="col-xs-3" style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;border-bottom:1px solid #ccc;">
                    <h5 style="font-size: 15px; text-align: center !important ">VALOR</h5>
                  </th>
                  <th class="col-xs-3" style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;border-right:1px solid #ccc; border-bottom:1px solid #ccc;">
                    <h5 style="font-size: 15px;text-align: center !important ">MONTO CIERRE</h5>
                  </th>
                  <th class="col-xs-3" style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;border-right:1px solid #ccc; border-bottom:1px solid #ccc;">
                    <h5 style="font-size: 15px; text-align: center !important ">MONTO APERTURA</h5>
                  </th>
                  <th class="col-xs-3" style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px; border-bottom:1px solid #ccc;">
                    <h5 style="font-size: 15px; text-align: center !important ">DIFERENCIAS</h5>  </th>
                </tr>
              </thead>
              <tbody id="validarFichas" align="center" style="border-spacing: 7px 7px;">

              </tbody>
            </table>

        </div>
           <br>
           <br>
           <div class="row">
                <div class="col-md-4">
                  <h6>TOTAL CIERRE ($):</h6>
                  <input type="text" id="total_cierre_validar" class="form-control" value="" readonly="true">
                </div>
                <div class="col-md-4" >
                  <h6>TOTAL APERTURA ($):</h6>
                  <input type="text" id="total_aper_validar" class="form-control" value="" readonly="true">
                </div>
                <div class="col-md-4" >
                  <h6>TOTAL ANTICIPOS ($):</h6>
                  <input type="text" id="anticipos_validar" class="form-control" value="" readonly="true">
                </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-8">
              <h6>OBSERVACIONES:</h6>
              <textarea name="name" id="obsValidacion" rows="4" width="100%"  class="estilotextarea4"></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button id="validar" type="button" value="" class="btn btn-success" data-dismiss="modal" hidden="true">VALIDAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          </div>

          <div id="mensajeErrorValApertura" hidden>
              <br>
              <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
              <br>
              <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Debe comparar la Apertura con algún Cierre del listado presentado.</span>
          </div> <!-- mensaje -->

        </div>
      </div>
    </div>
  </div>
</div>

<meta name="_token" content="{!! csrf_token() !!}" />

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h6>GESTIÓN DE CIERRES Y APERTURAS</h6>
  <p>
    Desde esta sección se podrán visualizar los cierres y aperturas cargados, ordenados por fecha,
    y generar las planillas de Relevamiento de Aperturas.
    Los datos cargados pueden filtrarse, cargar y editar. Sólo las aperturas se validan, seleccionando
    el cierre con el que se desea realizar dicha acción, para luego poder comparar datos de cada mesa.
    <br><br>

    <h6>CIERRES</h6>
    Desde el botón "Nuevo Cierre", podrán cargarse simultaneamente los Cierres correspondientes a una fecha de producción
    especificada y a un casino especificados en la ventana de carga, de las diferentes mesas que abrieron. Para guardar
    la información cargada para cada mesa, se debe presionar el botón "Guardar", y esta aparecerá con un tilde en el listado
    de mesas a cargar. Una vez que se hayan cargado todos los datos de cierre de cada mesa, se presiona el botón "Finalizar"
    para cerrar la ventana de carga.
    Luego podrán visualizarse en el listado principal, los Cierres cargados hasta el momento, ordenados por fecha y paginados.
    Estos pueden filtrarse por mesa, fecha, juego y casino, desplazando la barra de "FILTROS".
    Además se puede acceder a los detalles de cada cierre, modificarse y eliminar, según los roles y permisos de cada usuario.
    <br><br>
    <h6>APERTURAS</h6>
    Desde el botón "Generar Planilla Apertura", se genera un archivo con cinco planillas en las que se detallan las mesas que
    han sido seleccionadas por sorteo para relevar su apertura.
    Desde el botón "Cargar Apertura, podrán cargarse simultaneamente las Aperturas correspondientes a una fecha de producción
    especificada y a un casino especificados en la ventana de carga, de las mesas relevadas. Para guardar la información
    cargada para cada mesa, se debe presionar el botón "Guardar", y esta aparecerá con un tilde en el listado de mesas a cargar.
    Una vez que se hayan cargado todos los datos de apertura de cada mesa, se presiona el botón "Finalizar" para cerrar la
    ventana de carga.
    Luego podrán visualizarse en el listado principal, las Aperturas cargadas hasta el momento, ordenadas por fecha y paginadas.
    Estas pueden filtrarse por mesa, fecha, juego y casino, desplazando la barra de "FILTROS".
    Además se puede acceder a los detalles de cada Apertura, modificarse, eliminarse y validarse, según los roles y permisos de
    cada usuario.
    Para la validación se debe seleccionar el Cierre que se corresponda con la Apertura a validar, en la selección se detalla
    la hora, la moneda y fecha del cierre.  En caso de haber diferencias, podrá validarse con Observación.  Una vez validada,
    esta apertura aparecerá en el listado principal con una tilde verde en la columna "Estado".
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')

  <!-- JavaScript personalizado -->

  <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <script src="js/inputSpinner.js" type="text/javascript"></script>
  <script src="js/lista-datos.js" type="text/javascript"></script>
  <script src="/js/paginacion.js" charset="utf-8"></script>

  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <script src="/js/jquery-ui.js" type="text/javascript"></script>


  <script src="js/math.min.js" type="text/javascript"></script>
  <script src="js/CierresAperturas/CierresAperturas.js" charset="utf-8"></script>

@endsection
