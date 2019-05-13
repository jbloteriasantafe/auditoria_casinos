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
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<link href="css/bootstrap.min.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/js/jquery-ui-1.12.1.custom/jquery-ui.css">
<link rel="stylesheet" href="/css/paginacion.css">
@endsection

@section('contenidoVista')

<div class="col-lg-12 tab_content" id="pant_aperturas" hidden="true">

<div class="row">
  <div class="col-md-3">
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
  </div>

  <div class="col-md-9">
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
                      <input type='text' class="form-control" id="B_fecha_filtro" value="" placeholder="aaaa-mm-dd"/>
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
                  </select>
                </div>
                <div class="col-xs-4">
                  <h5>JUEGO</h5>
                  <select class="form-control" name="" id="selectJuego">
                    <option value="0" selected>- Seleccione un Juego -</option>
                    @foreach ($juegos as $j)
                    <option value="{{$j->id_juego_mesa}}">{{$j->nombre_juego}} - {{$j->casino->codigo}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4" style="padding-top:50px;">
                  <button id="btn-buscarCyA" class="btn btn-infoBuscar" type="button" name="button" style="margin-top:30px">
                    <i class="fa fa-fw fa-search"></i> BUSCAR
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
          <!-- TABLA -->
    <div class="row">
      <div class="col-xs-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 id="tablaInicial">APERTURAS</h4>
          </div>
          <div class="panel-body">
            <div class="table-responsive">
              <table id="tablaResultados" class="table  tablesorter " >
                <thead>
                  <tr align="center" >
                    <th class="activa" apertura="apertura_mesa.fecha" cierre="cierre_mesa.fecha" style="font-size:14px; text-align:center !important;" estado="desc">FECHA  <i class="fas fa-sort-down"></th>
                    <th class="" apertura="mesa_de_panio.nro_mesa" cierre="mesa_de_panio.nro_mesa" style="font-size:14px; text-align:center !important;" estado="">MESA  <i class="fas fa-sort"></th>
                    <th class="" apertura="juego_mesa.siglas" cierre="juego_mesa.siglas" style="font-size:14px; text-align:center !important;" estado="">JUEGO  <i class="fas fa-sort"></th>
                    <th class="" apertura="apertura_mesa.hora" cierre="cierre_mesa.hora_inicio" style="font-size:14px; text-align:center !important;" estado="">HORA <i class="fas fa-sort"></th>
                    <th class="" apertura="moneda.silgas" cierre="moneda.siglas" style="font-size:14px; text-align:center !important;" estado="">MONEDA  <i class="fas fa-sort"></th>
                    <th class="" apertura="casino.nombre" cierre="casino.nombre" style="font-size:14px; text-align:center !important;" estado="">CASINO  <i class="fas fa-sort"></th>
                    <th class="" apertura="apertura_mesa.id_estado_cierre" cierre="cierre_mesa.id_estado_cierre" style="font-size:14px; text-align:center !important;">ESTADO  <i class="fas fa-sort"></th>
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
                      <td class="" style="text-align:center !important"> <button type="button" name="button"> <i class="fa fa-fw fa-check"   align="center"  style="color: #4CAF50;text-align:center !important;"></i></button></td>
                    @endif
                    @if($a->id_estado_cierre == 2)
                    <td class="" style="text-align:center !important"> <button type="button" name="button"></button> <i class="fas fa-fw fa-exclamation" align="center" style="color: #FFC107;text-align:center !important;"></i></td>
                    @endif
                    @if($a->id_estado_cierre == 1)
                      <td class="" style="text-align:center !important">  <i class="fas fa-fw fa-times"  align="center" style="color: #D32F2F;text-align:center !important;"></td>
                    @endif

                    <td class="" style="text-align:center !important;">
                      @if($a->id_estado_cierre == 3)
                        <button type="button" class="btn btn-info infoCyA" value="{{$a->id_apertura_mesa}}">
                          <i class="fa fa-fw fa-search-plus"></i>
                        </button>
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_validar_aperturas'))
                        <button type="button" class="btn btn-info desvincular" value="{{$a->id_apertura_mesa}}">
                          <i class="fas fa-fw fa-unlink"></i>
                        </button>
                        @endif
                        @else
                        <button type="button" class="btn btn-info infoCyA" value="{{$a->id_apertura_mesa}}" >
                          <i class="fa fa-fw fa-search-plus"></i>
                        </button>
                        <button type="button" class="btn btn-warning modificarCyA" value="{{$a->id_apertura_mesa}}">
                          <i class="fas fa-fw fa-pencil-alt"></i>
                        </button>
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_validar_aperturas'))
                        <button type="button" class="btn btn-success validarCyA" value="{{$a->id_apertura_mesa}}">
                          <i class="fa fa-fw fa-check"></i>
                        </button>
                        @endif
                      @endif
                      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_eliminar_cierres_aperturas'))
                        <button type="button" class="btn btn-success eliminarCyA" value="{{$a->id_apertura_mesa}}">
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
                    <button type="button" class="btn btn-info infoCyA" value="">
                      <i class="fa fa-fw fa-search-plus"></i>
                    </button>
                    @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_validar_aperturas'))
                    <button type="button" class="btn btn-info desvincular" value="">
                      <i class="fas fa-fw fa-unlink"></i>
                    </button>
                    @endif
                    <button type="button" class="btn btn-warning modificarCyA" value="">
                      <i class="fas fa-fw fa-pencil-alt"></i>
                    </button>
                    @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_validar_aperturas'))
                    <button type="button" class="btn btn-success validarCyA" value="">
                      <i class="fa fa-fw fa-check"></i>
                    </button>
                    @endif
                    @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_eliminar_cierres_aperturas'))
                    <button type="button" class="btn btn-success eliminarCyA" value="" >
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
  </div>
</div>

</div> <!-- fin de la pestaña -->

<div class="col-lg-12 tab_content" id="pant_cierres" hidden="true">

  <div class="row">
    <div class="col-xl-3">

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
      <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros2" style="cursor: pointer">
              <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
            </div>

            <div id="collapseFiltros2" class="panel-collapse collapse">
              <div class="panel-body">
                <div class="row">
                  <div class="col-xs-4">
                    <h5>Fecha</h5>
                    <div class="form-group">
                      <div class='input-group date' id='dtpFechaCierreFiltro' data-link-field="fecha_filtro" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                        <input type='text' class="form-control" id="B_fecha_filtro_cierre" value=" " placeholder="aaaa-mm-dd"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                      </div>
                    </div>
                  </div>
                  <div class="col-xs-4">
                    <h5>Mesa</h5>
                    <div class="input-group lista-datos-group">
                      <input id="filtroMesaCierre" class="form-control" type="text" value="" autocomplete="off">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-xs-4">
                    <h5>Casino</h5>
                    <select class="form-control" name="" id="selectCasCierre" >
                      <option value="0" selected>- Seleccione un Casino -</option>
                      @foreach ($casinos as $cas)
                      <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                      @endforeach
                    </select>
                  </div>

                  <br>
                  <div class="col-md-4" style="padding-top:50px;">
                    <button id="btn-buscar-cierre" class="btn btn-infoBuscar" type="button" name="button" style="margin-top:30px">
                      <i class="fa fa-fw fa-search"></i> BUSCAR
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

            <!-- TABLA -->
      <div class="row">
        <div class="col-xs-12">
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4 id="tablaInicial">CIERRES</h4>
            </div>
            <div class="panel-body">
              <div class="table-responsive">
                <table id="tablaResultadosCierres" class="table  tablesorter " >
                  <thead>
                    <tr align="center" >
                      <th class="activa"  cierre="cierre_mesa.fecha" style="font-size:14px; text-align:center !important;" estado="desc">FECHA  <i class="fas fa-sort-down"></th>
                      <th class=""  cierre="mesa_de_panio.nro_mesa" style="font-size:14px; text-align:center !important;" estado="">MESA  <i class="fas fa-sort"></th>
                      <th class=""  cierre="juego_mesa.siglas" style="font-size:14px; text-align:center !important;" estado="">JUEGO  <i class="fas fa-sort"></th>
                      <th class=""  cierre="cierre_mesa.hora_inicio" style="font-size:14px; text-align:center !important;" estado="">HORA <i class="fas fa-sort"></th>
                      <th class=""  cierre="moneda.siglas" style="font-size:14px; text-align:center !important;" estado="">MONEDA  <i class="fas fa-sort"></th>
                      <th class="" cierre="casino.nombre" style="font-size:14px; text-align:center !important;" estado="">CASINO  <i class="fas fa-sort"></th>
                      <th class="" style="font-size:14px; text-align:center !important;">ACCIÓN</th>
                    </tr>
                  </thead>
                  <tbody  id='cuerpoTablaCierre' >

                  </tbody>
                </table>
              </div>
              <div class="table-responsive" style="display:none">
                <table  class="table">
                  <tr id="moldeFilaCierre" class="filaClone" style="display:none">
                    <td class=" cierre_fecha"  style="text-align:center !important;"></td>
                    <td class=" cierre_mesa"   style="text-align:center !important;"></td>
                    <td class=" cierre_juego"  style="text-align:center !important;"></td>
                    <td class=" cierre_hora"   style="text-align:center !important;"></td>
                    <td class=" cierre_moneda" style="text-align:center !important;"></td>
                    <td class=" cierre_casino" style="text-align:center !important;"></td>

                    <td class="" style="text-align:center !important;">
                      <button type="button" class="btn btn-info infoCierre" value="">
                        <i class="fa fa-fw fa-search-plus"></i>
                      </button>
                      <button type="button" class="btn btn-warning modificarCierre" value="">
                        <i class="fas fa-fw fa-pencil-alt"></i>
                      </button>
                      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_validar_cierres'))
                      <button type="button" class="btn btn-success validarCierre" value="">
                        <i class="fa fa-fw fa-check"></i>
                      </button>
                      @endif
                      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_eliminar_cierres_aperturas'))
                      <button type="button" class="btn btn-success eliminarCierre" value="" >
                        <i class="fa fa-fw fa-trash"></i>
                      </button>
                      @endif
                    </td>
                  </tr>
                </table>
              </div>
              <legend></legend>
              <div id="herramientasPaginacion2" class="row zonaPaginacion"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div> <!-- fin pestaña cierre -->

<!-- modal relevamiento- generando -->
<div class="modal fade" id="modalRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content">
           <div class="modal-header" style="background-color:#1DE9B6;">
             <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
             <h3 class="modal-title">| GENERANDO RELEVAMIENTO</h3>
            </div>

            <div  id="colapsadoNuevo" class="collapse in">

            <div class="modal-body modalCuerpo" >

            </div>
          </div>
        </div>
      </div>
</div>

<div class="modal fade" id="modalErrorRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content" style="border-radius:5px !important">
           <div class="modal-header" style="font-family: Roboto-Black; background-color:#0D47A1">
             <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
             <h3 class="modal-title">| AVISO</h3>
            </div>
            <div class="modal-body">
              <div class="row">
                <h6 style="text-align:center !important">'Por favor reintente en 15 minutos...'</h6>
                <h6 style="text-align:center !important">GRACIAS</h6>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">ACEPTAR</button>
            </div>
          </div>
        </div>
</div>

<!-- modal alerta desvinculación -->
<div class="modal fade" id="modalDesvinculacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content" style="border-radius:5px !important">
           <div class="modal-header" style="background-color:#0D47A1;">
             <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
             <h3 class="modal-title">| ALERTA</h3>
            </div>

            <div  id="colapsadoNuevo" class="collapse in">
              <div class="modal-body modalCuerpo">
                <h6>Esta Apertura fue vinculada a un Cierre determinado mediante la validación,
                    puede observarse en los detalles de la misma.</h6>
                <h6>¿Desea deshacer esta validación y desvincular el Cierre?</h6>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-info" id="btn-desvincular" value="">DESVINCULAR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
            </div>
          </div>
        </div>
</div>

<!-- MODAL CARGA cierre -->
<div class="modal fade" id="modalCargaCierre" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" >
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| CARGA CIERRE </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row" style="border-bottom:2px solid #ccc;">
            <div class="col-md-4">
              <h6>FECHA PRODUCCIÓN</h6>
              <div class="form-group">
                <div class='input-group date' id='dtpfechaCierre' data-link-field="fecha_cierre" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="aaaa-mm-dd" id="B_fecha_cie" value=" "/>
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
                  <span class="input-group-btn" style="display:block;">
                    <button id="agregarMesaCierre" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <h6>FISCALIZADOR DE CARGA</h6>
              <input id="fiscalizadorCierre" class="form-control" type="text" value=""  size="100" readonly="true" >
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
                          <input type="time" name="hora_cierre" class="form-control" value="" format="hh:mm" id="horario_ini_c">
                          <br>
                        </div>

                        <div class="col-md-4">
                          <h6>HORA CIERRE</h6>
                          <input type="time" name="hora_CC" class="form-control" value="" id="horarioCie">

                          <br>
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
                                <td><input type="text" value="" style="text-align:right" readonly="true" class="col-xs-6 form-control fichaValCC"></td>
                                <td><input type="text" style="text-align:right" class="col-xs-6 form-control inputCie" id="input" val=""></td>
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
<div class="modal fade" id="modalDetalleCierre" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" >
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#0D47A1;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| DETALLES DE CIERRE </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-xs-12">
              <div class="col-xs-4" align="center" style=" border-right:1px solid #ccc;">
                <h6>MESA</h6>
                <div class="col-xs-2 col-xs-offset-1 iconoMesa">
                    <i class="fas fa-clipboard-check fa-2x" style="position:relative; left:-1px;"></i>
                </div>
                <div class="col-xs-6" align="center">
                  <h5 class="mesa_det_cierre" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
                </div>
              </div>
              <div class="col-xs-4" align="center" style=" border-right:1px solid #ccc;">
                <h6>JUEGO</h6>
                <div class="col-xs-2 col-xs-offset-1 iconoJuego">
                    <i class="fas fa-dice fa-2x" style="position:relative; left:-1px;"></i>
                </div>
                <div class="col-xs-6" align="center">
                  <h5 class="juego_det_cierre" style="color: #000 !important; font-size: 14px;"></h5>
                </div>
              </div>
              <div class="col-xs-4" align="center">
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
            <div class="col-xs-12" style="border-top:2px solid #ccc; border-bottom:2px solid #ccc;">
                <div class="col-xs-4" align="center" style="border-right:1px solid #ccc;">
                  <h6>HORA DE APERTURA</h6>
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
                <div class="col-xs-4" align="center" style="border-right:1px solid #ccc;">
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
                <div class="col-xs-4" align="center" >
                  <h6>FECHA</h6>
                  <div class="row">
                    <div class="col-xs-2 col-xs-offset-1 iconoCalendarr">
                        <i class="far fa-calendar-alt fa-2x" style="position:relative; left:-1px;"></i>
                    </div>
                    <div class="col-xs-6" align="center">
                        <h5 class="fecha_detalle_cierre" style="color: #000 !important; font-size: 14px;">10:20 H</h5>
                    </div>
                  </div>
                </div>
            </div>
          </div>
          <br>
          <div class="row" style="border-bottom:1px solid #ccc; text-align:center;">
            <h3 align="center" style="padding-bottom:20px;display:inline" >DATOS GENERALES</h3><i class="fas fa-info-circle" style="font-size:30px;"></i>
            <br>
            <br>
          </div>
          <div class="row " style="border-bottom:1px solid #ccc; ">
            <div class="col-xs-6">
              <h6 align="center">FICHAS</h6>
            <table  id="fichasdetallesC"style="border-collapse: collapse;" align="center" class="table table-striped">
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


            <div class="col-xs-6" id="vertical-bar">
              <div class="row" style="text-align:center !important">
                <div class="col-xs-12">
                  <h6>TOTAL</h6>
                  <input type="text" id="total_detalle" value="" readonly="true">
                </div>
                <div class="col-xs-12">
                  <h6>TOTAL ANTICIPOS</h6>
                  <input type="text" id="anticipos_detalle" value="" readonly="true">
                </div>
              </div>
            </div>
          </div>
          <div class="row" style="border-bottom:1px solid #ccc; text-align:center;">
            <br>
              <h3 align="center" style="position:relative;top:-2px;display:inline;border-bottom:1px solid #ccc;" align="center">DATOS APERTURA</h3> <i class="fas fa-clipboard-check" style="font-size:30px;"></i>
              <br>
          </div>
          <br>
          <div class="row aperturaVinculada" style="border-top: 1px solid: #ccc;">
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
                <h6>TOTAL APERTURA</h6>
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

<!-- MODAL DE VALIDAR DE CIERRE -->
<div class="modal fade" id="modalValidarCierre" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" >
  <div class="modal-dialog modal-lg" style="width:70%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| VALIDAR DE CIERRE </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-xs-12">
              <div class="row" style="border-bottom:2px solid #ccc">
                  <h3 style="text-align:center">DATOS GENERALES</h3>
              </div>
              <div class="row" style="background-color:#BDBDBD;">
                <div class="col-xs-6">
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:40px;" class="mesa_validar_c"></h6>
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:40px;" class="juego_validar_c"></h6>
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:40px;" class="cargador_validar_c"></h6>
                </div>
                <div class="col-xs-6">
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:60px;" class="inicio_validar_c"></h6>
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:60px;" class="hora_cierre_validar_c"></h6>
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:60px;" class="fecha_validar_c"></h6>
                </div>

              </div>
            </div>
          </div>
          </div>
          <br>

          <div class="row " >
            <div class="col-xs-6">
              <h6 align="center">FICHAS</h6>
            <table  id="fichasdetallesCValidar"style="border-collapse: separate;" align="center" class="table table-striped">
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
              <tbody id="datosCierreFichasValidar" align="center" style="border-spacing: 7px 7px;">
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


            <div class="col-xs-6" id="vertical-bar">
              <div class="row">
                <div class="col-xs-6">
                  <h6>TOTAL</h6>
                  <input type="text" id="total_validar_c" value="" readonly="true">
                </div>
                <div class="col-xs-6">
                  <h6>TOTAL ANTICIPOS</h6>
                  <input type="text" id="anticipos_validar_c" value="" readonly="true">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-8">
                <h6 style="padding-left:15px !important">OBSERVACIONES</h6>
                <textarea name="name" id="obsValidacionCierre" style="margin-left:15px !important" rows="4" width="100%"  class="estilotextarea4"></textarea>
              </div>
            </div>
          </div>
          <br>
          <div class="modal-footer">
            <button id="validarCierre" type="button" value="" class="btn btn-successAceptar" >VALIDAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL MODIFICAR CIERRE -->
<div class="modal fade" id="modalModificarCierre" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
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
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">FECHA</h5>
                <h5 class="list-group-item" style="text-align:center !important; margin-top:0px !important;color:black !important; font-size:14px !important" id="f_cierre"></h5>
                <br>
              </div>
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">MESA</h5>
                <h5 class="list-group-item nro_cierre" style="text-align:center !important;color:black !important; margin-top:0px !important; font-size:14px !important"></h5>
                <br>
              </div>
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">JUEGO</h5>
                <h5 class="list-group-item j_cierre" style="text-align:center !important;color:black !important; margin-top:0px !important; font-size:14px !important"></h5>
                <br>
              </div>
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">CASINO</h5>
                <h5 class="list-group-item cas_cierre" style="text-align:center !important;color:black !important; margin-top:0px !important; font-size:14px !important"></h5>
                <br>
              </div>
            </div>
            <div class="row" >
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">HS APERTURA</h5>
                <input class="list-group-item cas_cierre form-control" style="height:40px !important" id="hs_inicio_cierre" type="time"></input>
                <br>
              </div>
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">HS CIERRE</h5>
                <input class="list-group-item form-control" style="height:40px !important" id="hs_cierre_cierre" type="time"></input>
                <br>
              </div>
              <div class="col-xs-4">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">FISCALIZADOR CARGA</h5>
                <input class="list-group-item form-control" style="height:40px !important" id="fis_cierre" type="text"></input>
                <br>
              </div>

              <div class="col-xs-2" style="float:left !important">
                <h5  class="mon_apertura linea" style="font-size:14px !important;">Moneda: </h5>
                <br>
                @foreach($monedas as $moneda)
                  <input type="radio" name="monedaModCie" style="margin-left:5px;" value="{{$moneda->id_moneda}}"><span style="font-family: Roboto-Regular; padding-left:10px;">{{$moneda->descripcion}}</span> <br>
                @endforeach
              </div>
            </div>

            <br>
            <div class="row">
              <div class="col-xs-6">
                <h6 style="text-align:center !important">FICHAS: </h6>
                <table style="margin-left:2px" align="center" class="table table-bordered">
                    <thead>
                    <tr>
                      <th class="col-xs-3" style="border-bottom:1px solid #ccc;border-right:1px solid #ccc"><h5 align="center">Valor</h5></th>
                      <th class="col-xs-3" style="border-bottom:1px solid #ccc;"><h5 align="center">Monto</h5></th>
                    </tr>
                  </thead>
                  <tbody id="modificarFichasCie">
                  </tbody>
                </table>
              </div>
              <br>
              <div class="col-xs-6" align="center">
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
          <button type="button" class="btn btn-successAceptar" id="modificar_cierre" value="nuevo" hidden="true">GUARDAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          <input id="id_cierre" type="text" name="" value="" hidden="true">
        </div>
        <div id="errorModificarCierre" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique los valores cargados.</span>
        </div> <!-- mensaje -->
        <div id="errorModificarCierre2" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">La moneda elegida no es correcta.</span>
        </div> <!-- mensaje -->
    </div>
  </div>
</div>

<!-- MODAL CARGA APERTURA -->
<div class="modal fade" id="modalCargaApertura" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true"
                data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title">| CARGA APERTURA</h3>
              </div>
          <div  id="colapsado" class="collapse in">
                <div class="modal-body" style="font-family: Roboto;">
                  <div class="row" style="border-bottom:2px solid #ccc;">
                    <div class="col-xs-4">
                      <h6>FECHA APERTURA</h6>
                      <div class="form-group">
                        <div class='input-group date' id='dtpFechaApert' data-link-field="fecha_apertura" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                          <input type='text' class="form-control" placeholder="aaaa-mm-dd" id="B_fecha_apert" value=" "/>
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
                        <span class="help-block" style="color: #0D47A1 !important;margin-top:-5px !important; font-size:12px !important;padding-left:5px !important"><i>*Presione '+' para agregar la Mesa.</i></span>
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
                                    <h6>MONEDA</h5>
                                    @foreach($monedas as $moneda)
                                      <input type="radio" name="monedaApertura" style="margin-left:15px !important" value="{{$moneda->id_moneda}}"><span style="font-family: Roboto-Regular; padding-left:10px;">{{$moneda->descripcion}}</span> <br>
                                    @endforeach
                                    </div>
                                  <div class="col-xs-4">
                                    <h5>HORA DE APERTURA</h5>
                                    <input type="time" name="hora_apertura" class="form-control" value="" id="horarioAp">
                                    <br>
                                  </div>
                                  <div class="col-xs-5">
                                    <h5>FISCALIZADOR DE TOMA </h5>
                                      <input id="fiscalizApertura" class="form-control" type="text" value=""  size="100" autocomplete="off">
                                    <br>
                                  </div>
                                </div>
                                <br>
                                <div class="row">
                                    <h5 align="center">FICHAS</h5>
                                    <div class="row">
                                      <div class=" col-xs-6">
                                    <table id="tablaCargaApertura">
                                      <thead >
                                        <tr class="col-xs-6">
                                          <th><h5 class="col-xs-6" style="padding-left:30px">VALOR</h5></th>
                                          <th><h5 class="col-xs-6" style="padding-left:70px">CANTIDAD</h5></th>
                                        </tr>
                                      </thead>
                                      <tbody id="bodyCApertura">

                                      </tbody>
                                    </table>
                                    <table>
                                      <tbody>
                                        <tr id="filaFichasClon" style="display:none">
                                          <td><input type="text" value="" style="text-align:right" readonly="true" class="col-xs-6 form-control fichaVal"></td>
                                          <td><input type="text" style="text-align:right" class="col-xs-6 form-control inputApe" id="input" val="" pattern="[[^0-9]*"></td>
                                        </tr>
                                      </tbody>
                                    </table>

                                    </div>

                                <div class="col-xs-4">
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
                          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique los datos ingresados.</span>
                        </div> <!-- mensaje -->
                        <div id="mensajeErrorCargaApFichas" hidden>
                          <br>
                          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
                          <br>
                          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique que los datos ingresados para cada ficha sean correctos.</span>
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

      <div class="modal-header" style="background-color:#0D47A1;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true"
                data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| DETALLE APERTURA</h3>
      </div>

      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">

          <div class="row" style="border-bottom:1px solid #ccc;border-top:1px solid #ccc">
            <div class="col-xs-4" align="center">
              <h6>MESA</h6>
              <div class="col-xs-2 col-xs-offset-1 iconoMESAAp">
                  <i class="fas fa-clipboard-check fa-2x" style=xs"position:relative; left:-1px;"></i>
              </div>
              <h5 class="mesa_det_apertura" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
              <br>
            </div>
            <div class="col-xs-4" align="center" style="border-left:1px solid #ccc">
              <h6>FECHA</h6>
              <div class="col-xs-2 col-xs-offset-1 iconoFechaAp">
                  <i class="far fa-calendar-alt fa-2x" style="position:relative; left:-1px;"></i>
              </div>
              <h5 class="fecha_det_apertura" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
            </div>
            <div class="col-xs-4" align="center" style="border-left:1px solid #ccc">
              <h6>JUEGO</h6>
              <div class="col-xs-2 col-xs-offset-1 iconoJuegoAp">
                  <i class="fas fa-dice fa-2x" style="position:relative; left:-1px;"></i>
              </div>
              <h5 class="juego_det_apertura" style="color: #000 !important; font-size: 14px;">nro mesa</h5>
            </div>
          </div>
          <br>
          <div class="row" style="border-bottom:1px solid #ccc">
            <div class="col-xs-4" align="center">
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
            <div class="col-xs-4" align="center" style="border-left:1px solid #ccc">
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

            <div class="row"align="center">
              <div class="col-xs-8" >
                <h6 style="text-align:center">FICHAS</h6>
                <table class="table table-striped">
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

                  <tbody id="bodyFichasDetApert" align="right">
                  </tbody>
                </table>
              </div>
            <div class="col-xs-4" >
              <br>
              <h6>TOTAL</h6><input id="totalAperturaDet" type="text" class="form-control" value="" readonly>
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
<div class="modal fade" id="modalModificarApertura" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
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
            <div class="row" >
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">FECHA</h5>
                <h5 class="list-group-item f_apertura" style="text-align:center !important; margin-top:0px !important;color:black !important; font-size:14px !important"></h5>
                <br>
              </div>
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">CASINO</h5>
                <h5 class="list-group-item cas_apertura" style="text-align:center !important; margin-top:0px !important;color:black !important; font-size:14px !important"></h5>
                <br>
              </div>
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">MESA-JUEGO</h5>
                <h5 class="list-group-item nro_apertura" style="text-align:center !important; margin-top:0px !important;color:black !important; font-size:14px !important"></h5>
                <br>
              </div>
              <div class="col-xs-3">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">fiscalizador CARGA</h5>
                <h5 class="list-group-item car_apertura" style="text-align:center !important; margin-top:0px !important;color:black !important; font-size:14px !important" ></h5>
                <br>
              </div>
            </div>
            <div class="row" >
              <div class="col-xs-4" style="margin-left:2px">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">HS DE APERTURA</h5>
                <input class="list-group-item form-control" style="height:40px !important; text-align:center !important" id="hs_apertura" type="time"></input>                <br>
              </div>
              <div class="col-xs-4">
                <h5 class="list-group-item"  style="font-size:14px !important; text-align:center !important; background-color:#aaa; color:white !important;">Fiscalizador de Toma</h5>
                <input class="list-group-item cas_cierre form-control" style="height:40px !important" id="fis_apertura" type="text" value=""  size="100" autocomplete="off"></input>
                <br>
              </div>
              <div class="col-xs-3" style="float:left !important">
                <br>
                <h5  class="mon_apertura linea" style="font-size:14px !important;">Moneda: </h5>
                <br>
                @foreach($monedas as $moneda)
                  <input type="radio"  name="monedaModApe"  id="monedaModApe" style="margin-left:40px;" value="{{$moneda->id_moneda}}"><span style="font-family: Roboto-Regular; padding-left:10px;">{{$moneda->descripcion}}</span> <br>
                @endforeach
              </div>

            </div>
            <br>
            <br>
            <div class="row" align="center" >
              <div class="col-xs-6">

                <h6>FICHAS: </h6>
                <table style="margin-left:2px" align="center" class="table table-bordered">
                  <thead>
                    <tr>
                      <th class="col-xs-3" style="border-bottom:1px solid #ccc;border-right:1px solid #ccc"><h5 align="center">Valor</h5></th>
                      <th class="col-xs-3" style="border-bottom:1px solid #ccc;"><h5 align="center">Cantidad</h5></th>
                    </tr>
                  </thead>
                  <tbody id="modificarFichasAp">
                  </tbody>
                </table>
              </div>
              <br>
              <div class="col-xs-6" align="center">
                <div class="row">
                  <h6><b>TOTAL: </b></h6>
                  <input id="totalModifApe" type="text" class="form-control" style="width:auto !important" value="" readonly="true">
                </div>
              </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="modificar_apertura" value="nuevo" hidden="true">GUARDAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          <input id="id_apertura" type="text" name="" value="" hidden="true">
        </div>
        <div id="errorModificar" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique los valores cargados.</span>
        </div> <!-- mensaje -->
        <div id="errorModificar2" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">La moneda elegida no es correcta.</span>
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


<!-- modal de validar -->
<div class="modal fade" id="modalValidarApertura2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width:70%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
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
              <h6 display="inline-block" style="font-size:19px !important; padding:0px;margin:0px !important;">Seleccione un Cierre para validar esta Apertura:</h6>
            </div>
            <div class="col-xs-4" >
              <select class="form-control" display="inline-block" style="padding-right:40px;margin:0px !important;padding-left:0px;" name="selFecha" id="fechaCierreVal">
                <option value="0" selected class="defecto">- Seleccione una Fecha -</option>
              </select>
            </div>
            <div class="col-xs-3" style="padding-left:20px;padding-bottom:10px;align:center">
              <button type="button" style="width:120px; height:40px; padding-top:0.5px;" class="btn btn-success comparar" > <h6>COMPARAR</h6> </button>
            </div>
            <br>
            <br>
          </div>

          <!-- datos de la apertura -->
          <div class="row">
            <div class="col-xs-6" align="center" style=" border-bottom:2px solid #ccc;">
              <div class="row" style="border-bottom:2px solid #ccc">
                <h6>APERTURA</h6>
              </div>
            <div class="row" style="background-color:#BDBDBD;">
              <div class="col-md-12" style="border-right: 3px solid #aaa">
                <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="hs_validar_aper"></h6>
                <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="fechaAp_validar_aper"></h6>
                <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="fis_validar_aper"></h6>
                <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="car_validar_aper"></h6>
                <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="tipo_validar_aper"></h6>
                <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="mon_validar_aper"></h6>
              </div>
            </div>
          </div>

          <!-- datos del cierre -->
          <div class="col-xs-6" id="div_cierre" style="border-bottom:2px solid #ccc;" hidden>
                <h6 style="text-align:center !important">CIERRE</h6>
              <div class="row" style="background-color:#BDBDBD;">
                <div class="col-xs-12">
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="nro_validar"></h6>
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="j_validar"></h6>
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="cas_validar"></h6>
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="hs_inicio_validar"></h6>
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="hs_cierre_validar"></h6>
                  <h6 style="font-size:17px !important;text-align:left !important;margin-left:15px;" class="f_validar"></h6>
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
            <table  style="border-collapse: separate;"  class="table table-bordered" align="center" id="tablaValidar">
              <thead>
                <tr>
                  <th class="col-xs-3" style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;border-bottom:1px solid #ccc;">
                    <h5 style="font-size: 15px !important;color:#aaa !important;color:#aaa !important; text-align: center !important ">VALOR</h5>
                  </th>
                  <th class="col-xs-3" style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;border-right:1px solid #ccc; border-bottom:1px solid #ccc;">
                    <h5 style="font-size: 15px !important;color:#aaa !important;color:#aaa !important;text-align: center !important ">CANTIDAD CIERRE</h5>
                  </th>
                  <th class="col-xs-3" style="padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;border-right:1px solid #ccc; border-bottom:1px solid #ccc;">
                    <h5 style="font-size: 15px !important;color:#aaa !important;color:#aaa !important; text-align: center !important ">CANTIDAD APERTURA</h5>
                  </th>
                  <th class="col-xs-3" style="padding-bottom:8px;padding-top:8px;color:#aaa !important;padding-left:8px;padding-right:8px; border-bottom:1px solid #ccc;">
                    <h5 style="font-size: 15px !important;color:#aaa !important; text-align: center !important ">DIFERENCIAS</h5>  </th>
                </tr>
              </thead>
              <tbody id="validarFichas" align="center" style="border-spacing: 7px 7px;">

              </tbody>
            </table>
            <div class="table table-responsive" id="mostrarTablaValidar"  style="display:none">
              <table class="table" style="padding:0px !important">
                <tbody >
                  <tr class"filaClone" id="clonarTFichasV" style="display:none; padding:0px !important;">
                    <td class="valor_validar" style="padding:1px !important;text-align:right !important;"></td>
                    <td class="cant_cierre_validar" style="padding:1px !important;text-align:right !important;font-weight: bold"></td>
                    <td class="cant_apertura_validar" style="padding:1px !important;text-align:right !important;"></td>
                    <td class="diferencias_validar" style="padding:1px !important;text-align:right !important; "></td>
                  </tr>
                </tbody>
              </table>
            </div>
        </div>

           <div class="row">
                <div class="col-md-4">
                  <h6>TOTAL CIERRE</h6>
                  <input type="text" id="total_cierre_validar" class="form-control" value="" readonly="true">
                </div>
                <div class="col-md-4" >
                  <h6>TOTAL APERTURA</h6>
                  <input type="text" id="total_aper_validar" class="form-control" value="" readonly="true">
                </div>
                <div class="col-md-4" >
                  <h6>TOTAL ANTICIPOS</h6>
                  <input type="text" id="anticipos_validar" class="form-control" value="" readonly="true">
                </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-8">
              <h6>OBSERVACIONES</h6>
              <textarea name="name" id="obsValidacion" rows="4" width="100%"  class="estilotextarea4"></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button id="validar" type="button" value="" class="btn btn-successAceptar" data-dismiss="modal" hidden="true">VALIDAR</button>
            <button id="validar-diferencia" type="button" value="" class="btn btn-successAceptar" data-dismiss="modal" hidden="true">VALIDAR CON DIFERENCIA</button>
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


<!-- modal preg elim -->
<div class="modal fade" id="modalAlertaBaja" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-70%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color:#D50000">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| ALERTA</h3>
      </div>

      <div id="colapsado" class="collapse in">
        <div class="modal-body">
          <h6 style="color:#000000; font-size: 18px !important; text-align:center !important" id="msjAlertaBaja"></h6>
          <div class="row">

          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dangerEliminar" id="btn-baja" value="">ELIMINAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>

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
  <script src="js/CierresAperturas/CierresAperturas.js" type="text/javascript" charset="utf-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <script src="js/inputSpinner.js" type="text/javascript"></script>
  <script src="/js/lista-datos.js" type="text/javascript"></script>

  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <script src="/js/jquery-ui.js" type="text/javascript"></script>

  <script src="js/math.min.js" type="text/javascript"></script>
  <script src="js/bootstrap.min.js" type="text/javascript"></script>

  <!-- JS paginacion -->
  <script src="/js/paginacion.js" charset="utf-8"></script>
@endsection
