 <?php
use App\Http\Controllers\UsuarioController;
?>
@extends('includes.dashboard')

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">

<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<link href="css/bootstrap.min.css" rel="stylesheet"/>
@endsection

@section('contenidoVista')

<div class="col-xl-3">
  <div class="row">
    <div class="col-md-12">
      @if(UsuarioController::getInstancia()->quienSoy()['usuario']->es_superusuario)
      <a href="" id="btn-nuevo" dusk="btn-nuevo" style="text-decoration: none;">
        <div class="panel panel-default panelBotonNuevo">
          <center><img class="imgNuevo" src="/img/logos/casinos_white.png"><center>
            <div class="backgroundNuevo"></div>
              <div class="row">
                <div class="col-xs-12">
                  <center>
                      <h5 class="txtLogo">+</h5>
                      <h4 class="txtNuevo">NUEVO CASINO</h4>
                  </center>
                </div>
              </div>
          </div>
        </a>
        @endif
    </div>
  </div>
</div>

<div class="col-xl-9">
  <div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>CASINOS CARGADOS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <div class="table-responsive">
              <table id="tablaCasinos" class="table tablesorter" >
                <thead>
                  <tr>
                    <th class="col-xs-3" style="font-size:14px; text-align:center !important;">NOMBRE <i class="fa fa-sort"></i></th>
                    <th class="col-xs-3" style="font-size:14px; text-align:center !important;">CÓDIGO <i class="fa fa-sort"></i></th>
                    <th class="col-xs-3" style="font-size:14px; text-align:center !important;">FECHA CREACIÓN <i class="fa fa-sort"></i></th>
                    <th class="col-xs-3 accionesTH" style="font-size:14px; text-align:center !important;">ACCIÓN</th>
                  </tr>
                </thead>
                <tbody style="height:380px !important">
                  @foreach ($casinos as $casino)
                  <tr id="{{$casino->id_casino}}">
                    <td class="col-xs-3" style="text-align:center !important;">{{$casino->nombre}}</td>
                    <td class="col-xs-3" style="text-align:center !important;">{{$casino->codigo}}</td>
                    <td class="col-xs-3" style="text-align:center !important;">{{$casino->fecha_inicio}}</td>
                   <td class="col-xs-3" style="text-align:center !important;">
                     <button class="btn btn-warningModificar modificarCasino" value="{{$casino->id_casino}}"><i class="fas fa-fw fa-pencil-alt"></i></button>
                   </td>
                 </tr>
                 @endforeach
                </tbody>
              </table>
              </div>
              <div class="table-responsive"  id="dd" style="display:none">
                <table  class="table">
                    <tr id="moldeFilaCasino" class="filaClone" style="display:none">
                      <td class="col-xs-3 NCasino"  style="text-align:center !important;"></td>
                      <td class="col-xs-3 CodCasino"   style="text-align:center !important;"></td>
                      <td class="col-xs-3 fInicioCasino"  style="text-align:center !important;"></td>

                      <td class="col-xs-3" style="text-align:center !important;">
                        <button class="btn btn-warning modificarCasino" value="">
                            <i class="fas fa-fw fa-pencil-alt"></i>
                      </td>
                    </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

<!-- nuevo casino -->
<div class="modal fade" id="modalAlta" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false" style="font-style:normal;">
  <div class="modal-dialog modal-lg" style="width: 70%;">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1DE9B6;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| ALTA CASINO </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-xs-3">
              <h5>NOMBRE *</h5>
              <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del casino" value="">
            </div>
            <div class="col-xs-3">
              <h5>CÓDIGO *</h5>
              <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Código del casino" value="">
            </div>
            <div class="col-xs-3">
              <h5>% SORTEO MESAS *</h5>
              <input type="text" class="form-control" id="porcentaje_sorteo_mesas" name="porcentaje" placeholder="Porcentaje" value="">
              <span class="help-block" style="color: #0D47A1 !important;margin-top:5px !important; font-size:12px !important;padding-left:5px !important">
                <i>*Solicitud de imágenes Búnker.</i></span>
            </div>
            <div class="col-xs-3">
              <h5>FECHA INICIO *</h5>
              <div class="form-group">
                <div class='input-group date' id='dtpFecha' data-link-field="fecha_inicio_new" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" id="fecha_inicio" value="" placeholder="aaaa-mm-dd"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
          </div>
          <br>

          <div class="row pestaniasAlta">
            <ul class="nav nav-tabs nav-justified pestaniasTF" id="pestaniasTF" style=" width:80%;">
              <li id="p_turnos" ><a href="#fturnos"  style="font-family:Roboto-condensed;font-size:20px; "> <h6 style="font-size:16px !important">TURNOS *</h6> </a></li>
              <li id="p_pesos"><a href="#fpesos"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important">FICHAS PESOS *</h6></a></li>
              <li id="p_dolares"><a href="#fdolares"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important">FICHAS DÓLARES</h6></a></li>
            </ul>

            <div class="pestaniaTurnos" hidden="true">
                <div class="col-xs-6">
                  <h6 style="font-size:16px;margin-left:12px;">TURNOS</h6>
                  <div class="row">
                    <div class="col-md-6">
                      <h5>Cantidad de Turnos:</h5>
                      <div class="col-md-6">
                        <input type="text" class="form-control cant_turno"  name="" value="" display="inline">
                      </div>
                      <div class="col-md-4">
                        <button type="button" name="button" class="btn btn-infoBuscar okTurnos" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;">SIGUIENTE</button>
                      </div>
                    </div>
                  </div>
                </div>
                <br>
                <br>

              <table class="table table-sorter" id="tablaTurnos" hidden="true" >
                <thead>
                  <th class="col-md-1"> <h6 style="font-size:16px; text-align:center !important;color:#212121"> Número:</h6></th>
                  <th class="col-md-2"><h6 style="font-size:16px; text-align:center !important;color:#212121">Desde:</h6></th>
                  <th class="col-md-2"><h6 style="font-size:16px; text-align:center !important;color:#212121">Hasta:</h6></th>
                  <th class="col-md-2"><h6 style="font-size:16px; text-align:center !important;color:#212121">Entrada:</h6></th>
                  <th class="col-md-2"><h6 style="font-size:16px; text-align:center !important;color:#212121">Salida:</h6></th>
                </thead>
                <tbody>

                </tbody>
              </table>
            </div>
            <div class="pestaniaPesos" hidden="true">
              <div class="row">
                <div class="col-xs-6" style="margin-top:50px !important; text-align:center !important">
                  <h6 style="font-size:12px !important">Debe seleccionar fichas de pesos para su nuevo Casino. Puede crear nuevas desde el siguiente botón.</h6>
                  <button type="button" name="button" class="btn btn-infoBuscar agregarFPesos" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;">AGREGAR FICHA</button>
                </div>
              <div class="col-xs-6">
                <br>
                <table class="table table-bordered" id="tablaFichas" >
                  <thead>
                    <th class="col-xs-6"> <h6 style="font-size:16px; text-align:center !important;color:#212121"> VALOR</h6></th>
                    <th class="col-xs-4"><h6 style="font-size:16px; text-align:center !important;color:#212121">UTILIZAR</h6></th>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
                <div class="table table-responsive" id="dd" style="display:none">
                  <table class="table">
                    <tr class="filaClone" id="moldeFicha" style="display:none">
                      <td class="col-xs-6 valorF" style=" text-align:center !important; padding:1px !important" value=""></td>
                      <td class="col-xs-4" style="text-align:center !important;padding:1px !important">
                        <input type="checkbox" class="utilizar"  value="" >
                      </td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
            </div>
            <div class="pestaniaDolares" hidden="true">
              <div class="row">
                <div class="col-xs-6" style="margin-top:50px !important;text-align:center !important">
                  <button type="button" name="button" class="btn btn-infoBuscar agregarFDolares" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;">AGREGAR FICHA</button>
                </div>
              <div class="col-xs-6">
                <br>
                <table class="table table-bordered" id="tablaFichasDol" >
                  <thead>
                    <th class="col-xs-6"> <h6 style="font-size:16px; text-align:center !important;color:#212121"> VALOR</h6></th>
                    <th class="col-xs-4"><h6 style="font-size:16px; text-align:center !important;color:#212121">UTILIZAR</h6></th>
                  </thead>
                  <tbody>

                  </tbody>
                </table>
                <div class="table table-responsive" id="tt" style="display:none">
                  <table class="table">
                    <tr class="filaClone" id="moldeFichaDol" style="display:none">
                      <td class="col-xs-6 valorDol" style=" text-align:center !important; padding:1px !important" value=""></td>
                      <td class="col-xs-4" style="text-align:center !important; padding:1px !important">
                        <input type="checkbox" class="utilizarDol"  value="" >
                      </td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="alert alert-info" role="alert" id="alertaCanon" hidden="true">
          <h4 class="alert-heading">IMPORTANTE!</h4>
          <p>Al finalizar la creación de este casino, usted deberá ingresar datos referidos al CANON.</p>
          <hr>
          <p class="mb-0" style="font-size:11px !important"><i>Para esto debe ingresar a la sección CANON y modificar los datos correspondientes al nuevo Casino.</i></p>
        </div>
        <br>
        <div class="modal-footer">
          <span style="font-family:sans-serif;float:left !important;font-size:12px;color:#0D47A1"> * Campos Obligatorios</span>
          <button type="button" class="btn btn-successAceptar " id="btn-guardar" value="" hidden="true">GUARDAR</button>
          <button type="button" class="btn btn-successAceptar " id="btn-continuar" value="">FINALIZAR</button>
          <button type="button" class="btn btn-default" id="btn-cancelar-alta" data-dismiss="modal">CANCELAR</button>
          <input type="hidden" id="id_casino" name="id_casino" value="0">
        </div>
        <div id="mensajeErrorTurnosCarga" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique que todos los campos Obligatorios esten completos.</span>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<style media="screen">
  .block-scroll {overflow:hidden;}
</style>
<!-- MODAL MODIFICAR -->
<div class="modal fade" style="overflow-y: auto;" id="modalModificarCasino" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="font-style:normal;">
  <div class="modal-dialog modal-lg" style="width: 70%;" >
    <div class="modal-content">
      <div class="modal-header" style="background-color:#FFA726;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button  type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| MODIFICAR DATOS CASINO </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-xs-3">
              <h5>NOMBRE *</h5>
              <input type="text" class="form-control" id="nombreModif" name="nombre" placeholder="Nombre del casino" value="">
            </div>
            <div class="col-xs-3">
              @if(UsuarioController::getInstancia()->quienSoy()['usuario']->es_superusuario)
              <h5>CÓDIGO *</h5>
              <input type="text" class="form-control" id="codigoModif" name="codigo" placeholder="Código del casino" value="">
              @else
              <input type="text" class="form-control" id="codigoModif" name="codigo" placeholder="Código del casino" value="" disabled="" style="display: none;">
              @endif
            </div>
            <div class="col-xs-3">
              @if(UsuarioController::getInstancia()->quienSoy()['usuario']->es_superusuario)
              <h5>% SORTEO MESAS *</h5>
              <input type="text" class="form-control" id="porcentajeModif" name="sorteo" placeholder="Porcentaje del casino" value=""/>
              <span class="help-block" style="color: #0D47A1 !important;margin-top:5px !important; font-size:12px !important;padding-left:5px !important">
                <i>*Solicitud de imágenes Búnker.</i></span>
              @else
              <input type="text" class="form-control" id="porcentajeModif" name="sorteo" placeholder="Porcentaje del casino" value="" disabled="" style="display: none;"/>
              @endif
            </div>
            <div class="col-xs-3">
              @if(UsuarioController::getInstancia()->quienSoy()['usuario']->es_superusuario)
              <h5>FECHA INICIO *</h5>
              <div class="form-group">
                <div class='input-group date' id='dtpFechaIni' data-link-field="fecha_inicio" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd" >
                  <input type='text' class="form-control" id="finicioModif" placeholder="aaaa-mm-dd" value=" "/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              @else
              <div class="form-group" disabled="" style="display: none;">
                <div class='input-group date' id='dtpFechaIni' data-link-field="fecha_inicio" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd" >
                  <input type='text' class="form-control" id="finicioModif" placeholder="aaaa-mm-dd" value=" " disabled=""/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              @endif
              </div>
            </div>
          <br>
          <div class="row pestaniasModif">
            <ul class="nav nav-tabs nav-justified pestaniasTFM" id="pestaniasTFM" style=" width:80%;">
              <li id="p_turnos_modif" ><a href="#fturnosModif"  style="font-family:Roboto-condensed;font-size:20px; "> <h6 style="font-size:16px !important">TURNOS *</h6> </a></li>
              <li id="p_pesos_modif"><a href="#fpesosModif"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important">FICHAS PESOS *</h6></a></li>
              <li id="p_dolares_modif"><a href="#fdolaresModif"  style="font-family:Roboto-condensed;font-size:20px;"><h6 style="font-size:16px !important">FICHAS DÓLARES</h6></a></li>
            </ul>

            <div class="pestaniaTurnosModif" hidden="true">
              <div class="row">
                <h5>TURNOS</h5>
                <table class="table table-sorter" id="tablaTurnosModif">
                  <thead>
                    <th class="col-xs-2"> <h6 style="font-size:16px; text-align:center !important;color:#212121"> Número:</h6></th>
                    <th class="col-xs-2"><h6 style="font-size:16px; text-align:center !important;color:#212121">Desde:</h6></th>
                    <th class="col-xs-2"><h6 style="font-size:16px; text-align:center !important;color:#212121">Hasta:</h6></th>
                    <th class="col-xs-2"><h6 style="font-size:16px; text-align:center !important;color:#212121">Entrada:</h6></th>
                    <th class="col-xs-2"><h6 style="font-size:16px; text-align:center !important;color:#212121">Salida:</h6></th>
                    <th class="col-xs-2"></th>
                  </thead>
                  <tbody height="auto" width="100%">
                  </tbody>
                </table>
              </div>
              <div class="row">
                <button type="button" name="button" class="btn btn-infoBuscar masTurnos" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;">AGREGAR NUEVO TURNO</button>
              </div>
            </div>
            <div class="pestaniaPesosModif" hidden="true">
              <div class="row">
                <div class="col-xs-6" style="margin-top:50px !important; text-align:center !important">
                  <button type="button" name="button" class="btn btn-infoBuscar agregarFPesosModif" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;">AGREGAR FICHA</button>
                </div>
                <div class="col-xs-6">
                  <table class="table table-bordered" id="tablaFichasModif" >
                    <thead>
                      <th class="col-xs-6"> <h6 style="font-size:16px; text-align:center !important;color:#212121"> VALOR</h6></th>
                      <th class="col-xs-4"><h6 style="font-size:16px; text-align:center !important;color:#212121">UTILIZAR</h6></th>
                    </thead>
                    <tbody >
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="pestaniaDolaresModif" hidden="true">
              <div class="row">
                <div class="col-xs-6" style="margin-top:50px !important;text-align:center !important">
                  <button type="button" name="button" class="btn btn-infoBuscar agregarFDolaresModif" style="font-family:Roboto-Condensed;font-weight: bold;font-size: 15px;">AGREGAR FICHA</button>
                </div>
                <div class="col-xs-6">
                  <table class="table table-bordered" id="tablaFichasDolModif" >
                    <thead>
                      <th class="col-xs-6"> <h6 style="font-size:16px; text-align:center !important;color:#212121"> VALOR</h6></th>
                      <th class="col-xs-4"><h6 style="font-size:16px; text-align:center !important;color:#212121">UTILIZAR</h6></th>
                    </thead>
                    <tbody>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <span style="font-family:sans-serif;float:left !important;font-size:12px;color:#0D47A1"> * Campos Obligatorios</span>
          <button type="button" class="btn btn-warningModificar" id="btn-modificarCas" value="">MODIFICAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>
        <div id="mensajeErrorTurnos" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique que todos los campos Obligatorios esten completos.</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPreModificar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="font-style:normal;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style='font-family: Roboto-Black;  background-color:#FFA726;'>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-title" style="color:#F5F5F5;">ADVERTENCIA</h3>
      </div>
      <div class="modal-body">
        <form id="frmPreModificar" name="" class="form-horizontal" novalidate="">
          <div class="form-group error ">
            <div class="col-xs-12">
              <h6>¿Seguro desea modificar el CASINO? Podría ocasionar errores serios en el sistema.</h6>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" id="btn-preModificar" value="0">CONTINUAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<!-- JavaScript personalizado -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<script src="js/inputSpinner.js" type="text/javascript"></script>

<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<script>
var override_mostrar_dolares = false;
var estilo_viejo = $('#p_dolares_modif').css('display');

@if(UsuarioController::getInstancia()->quienSoy()['usuario']->es_superusuario)
override_mostrar_dolares = true;
@endif

</script>
<script src="js/Casinos/casinos.js" charset="utf-8"></script>



@endsection
