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
<link rel="stylesheet" href="/css/paginacion.css">

@endsection

@section('contenidoVista')

<div class="row">
  <div class="col-xl-3">
    <div class="row">
      <div class="col-md-12">
        <a href="" id="btn-generar" dusk="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
              <div class="backgroundNuevo"></div>
                <div class="row">
                  <div class="col-xs-12">
                    <center>
                        <h5 class="txtLogo">+</h5>
                        <h4 class="txtNuevo">GENERAR RELEVAMIENTO</h4>
                    </center>
                  </div>
                </div>
            </div>
          </a>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <a href="" id="btn-backUp" dusk="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
              <div class="backgroundNuevo"></div>
                <div class="row">
                  <div class="col-xs-12">
                    <center>
                        <h5 class="txtLogo">+</h5>
                        <h4 class="txtNuevo">CARGAR RELEVAMIENTO SIN SISTEMA</h4>
                    </center>
                  </div>
                </div>
            </div>
          </a>
      </div>
    </div>
    @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_abm_apuesta_minima'))
    <div class="row">
      <div class="col-md-12">
        <a href="" id="btn-minimo" dusk="btn-nuevo" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/procedimientos.png"><center>
              <div class="backgroundNuevo"></div>
                <div class="row">
                  <div class="col-xs-12">
                    <center>
                        <h5 class="txtLogo" style="font-size:80px !important;margin-top:60px !important"> <i class="fas fa-fw fa-pencil-alt"></i> </h5>
                        <br>
                        <br>
                        <h4 class="txtNuevo">MODIFICAR MÍNIMO DE APUESTAS REQUERIDO</h4>
                    </center>
                  </div>
                </div>
            </div>
          </a>
      </div>
    </div>
    @endif
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
                  <div class="col-xs-3">
                    <h5>Fecha de Producción</h5>
                    <div class="form-group">
                      <div class='input-group date' id='dtpFecha' data-link-field="fecha_filtro" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                        <input type='text' class="form-control" id="B_fecha_filtro" value=""/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                      </div>
                    </div>
                  </div>

                  <div class="col-xs-3">
                    <h5>Casino</h5>
                    <select class="form-control" name="" id="filtroCasino" >
                      <option value="0" selected>- Todos los Casinos -</option>
                      @foreach ($casinos as $cas)
                      <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-xs-3">
                    <h5>TURNO</h5>
                    <select class="form-control" name="" id="filtroTurno">
                      <option value="0" selected>- Todos los Turnos -</option>
                      @foreach ($turnos as $t)
                      <option value="{{$t->id_turno}}">#{{$t->nro_turno}} -{{$t->entrada}} a {{$t->salida}}- {{$t->casino->codigo}}</option>
                      @endforeach
                    </select>
                  </div>
                  <br>
                  <div class="col-md-3" style="padding-top:20px;">
                    <button id="btn-buscar-apuestas" class="btn btn-infoBuscar" type="button" name="button" style="margin-top:10px">
                      <i class="fa fa-fw fa-search"></i> BUSCAR
                    </button>
                  </div>
                </div> <!-- row / botón buscar -->

              </div> <!-- panel-body -->
            </div> <!-- collapse -->
          </div> <!-- .panel-default -->
        </div> <!-- .col-md-12 -->
      </div> <!-- .row / FILTROS -->

          <!-- TABLA -->
          <div class="row">
            <div class="col-xs-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h4>RELEVAMIENTOS</h4>
                  </div>
                  <div class="panel-body">
                    <div class="table-responsive">


                    <table id="tablaResultadosApuestas" class="table tablesorter" >
                      <thead>
                        <tr align="center" >
                          <th class="activa" value="fecha" style="font-size:14px; text-align:center !important;" estado="desc">FECHA PRODUCCIÓN <i class="fas fa-sort-down"></th>
                          <th class="" value="nro_turno" style="font-size:14px; text-align:center !important;" estado="">TURNO  <i class="fas fa-sort"></th>
                          <th class="" value="nombre" style="font-size:14px; text-align:center !important;" estado="">CASINO  <i class="fas fa-sort"></th>
                          <th class="" value="id_estado_relevamiento" style="font-size:14px; text-align:center !important;">ESTADO  <i class="fas fa-sort"></th>
                          <th class="" style="font-size:14px; text-align:center !important;">ACCIÓN</th>
                        </tr>
                      </thead>
                      <tbody  id='cuerpoTablaApuestas' style="height:380px !important">

                      </tbody>
                    </table>
                    </div>
                    <div class="table-responsive" style="display:none">


                    <table  class="table">
                        <tr id="moldeApuesta" class="filaClone" style="display:none">
                          <td class="col-xs-2 L_fecha"  style="text-align:center !important;"></td>
                          <td class="col-xs-2 L_turno"   style="text-align:center !important;"></td>
                          <td class="col-xs-2 L_casino"  style="text-align:center !important;"></td>
                          <td class="col-xs-3 L_estado"   style="text-align:center !important;"></td>

                          <td class="col-xs-3" style="text-align:center !important;">
                            <button type="button" class="btn btn-successAceptar cargarApuesta" value="">
                                    <i class="fas fa-fw fa-upload"></i>
                            </button>
                            <button type="button" class="btn btn-info imprimirApuesta" value="">
                                    <i class="fa fa-fw fa-print"></i>
                            </button>
                            <button type="button" class="btn btn-warning modificarApuesta" value="">
                                    <i class="fas fa-fw fa-pencil-alt"></i>
                            </button>
                            <button type="button" class="btn btn-success validarApuesta" value="">
                                    <i class="fa fa-fw fa-check"></i>
                            </button>
                            @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_validar_eliminar_relevamientos_apuestas'))
                            <button type="button" class="btn btn-success eliminarApuesta" value="">
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
    </div> <!-- .row / TABLA -->
  </div> <!-- fin col-xl-9 -->
</div>


<!-- MODAL CARGA RELEVAMIENTO -->
<div class="modal fade" id="modalCarga" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"  data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width: 70%;">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1DE9B6;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| CARGA RELEVAMIENTO DE VALORES DE APUESTAS </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row" style="border-bottom:2px solid #ccc;">
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">FECHA</h6>
              <div class="form-group">
                <div class='input-group date' id='dtpFechaCarga' data-link-field="fecha_cierre" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="Fecha de relevamiento" id="B_fecha_carga" value=" "/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">HORA PROPUESTA</h6>
              <input type="time" name="horarioRel" class="form-control" style="padding-top:0px;" id="hora_prop_carga" value="">
            </div>
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">HORA EJECUCIÓN</h6>
              <input type="time" name="horarioRel" class="form-control" style="padding-top:0px;" id="hora_ejec_carga" value="">
            </div>
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">TURNO</h6>
                <input type="text" class="form-control" id="turnoRelevado" name="" value="" readonly="true">
            </div>
          </div>
          <div class="row" style="border-bottom:2px solid #ccc;">
            <div class="col-md-4">
              <h6 style="font-size:15px !important;">FISCALIZADOR DE TOMA</h6>
              <div class="input-group ">
                <input id="fiscalizadorCarga" class="form-control" type="text" value="" autocomplete="off" placeholder="Nombre Fiscalizador" >
                <span class="input-group-btn" style="display:block;">
                  <button id="agregarFisca" class="btn btn-default btn-lista-datos" data-carga="normal" type="button"><i class="fa fa-plus"></i></button>
                </span>
              </div>
            </div>
            <div class="col-md-5" style="border-right:1px solid #ccc">
              <table class="table" id="fiscalizadoresPart">
                <tbody>
                </tbody>
              </table>
            </div>

            <div class="col-md-3">
              <h5 style="font-size:15px !important">A: Abierta</h5>
              <h5 style="font-size:15px !important">C: Cerrada</h5>
              <h5 style="font-size:15px !important">T: En Torneo</h5>
            </div>
          </div>
          <br>
          <div class="row">
            <table id="tablaCarga"class="table table-fixed table-striped ">
              <thead style="height:40px">
                <th class="col-xs-2"><h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">JUEGO</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold ">NRO MESA</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">POSICIONES</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">ESTADO (A|C|T)</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">MÍNIMA</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">MÁXIMA</h6> </th>

              </thead>

              <tbody style=" height:420px; width:100%">

              </tbody>
            </table>
            <div class="table table-responsive" id="ff" style="display:none;">

            <table class="table table-striped " >
                <tr id="moldeCarga" class="filaClone" style="display:none">
                  <td class="col-xs-2 juego_carga" rowspan="1" nowrap  style="text-align:center !important;"></td>
                  <td class="col-xs-2 nro_mesa" rowspan="1" nowrap style="text-align:center !important;"></td>
                  <td class="col-xs-2 pos_carga" rowspan="1" nowrap style="text-align:center !important;"></td>
                  <td class="col-xs-2">
                    <select class=" form-control estado_carga" id="" rowspan="1" style="text-align:center !important;">
                    </select>
                  </td>
                  <td class="col-xs-2">
                    <input type="text" style="text-align:center !important;" id="" rowspan="1" class=" form-control min_carga" name="" value="">
                  </td>
                  <td class="col-xs-2">
                    <input type="text"  style="text-align:center !important;" id="" rowspan="1" class=" form-control max_carga" name="" value="">

                  </td>
                </tr>

            </table>
            </div>
          </div>
          <div class="row">
            <h6 style="font-size:16px;font-weight:bold">OBSERVACIONES:</h6>
            <textarea name="name" id="obsCarga" rows="2" style="resize:none;display:block;width:80% !important;margin-left:10px;align:center" wrap="off" class="estilotextarea4"></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo" hidden="true">GUARDAR</button>
        </div>

        <div id="mensajeErrorCarga" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;" id="cambiar">Deben completarse todos los datos solicitados.</span>
        </div> <!-- mensaje -->
      </div>
    </div>
  </div>
</div>

<!-- MODAL MODIFICAR -->
<div class="modal fade" id="modalModificar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"  data-backdrop="static" data-keyboard="false">
<div class="modal-dialog modal-lg" style="width: 80%" >
  <div class="modal-content">
    <div class="modal-header" style="background-color:#FFA726;">
      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
      <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
      <h3 class="modal-title">| MODIFICACIÓN DE RELEVAMIENTO DE VALORES DE APUESTAS </h3>
    </div>
    <div  id="colapsado" class="collapse in">
      <div class="modal-body" style="font-family: Roboto;">
        <div class="row" style="border-bottom:2px solid #ccc;">
          <div class="col-md-3">
            <h6 style="font-size:16px !important;">FECHA</h6>
            <div class="form-group">
              <div class='input-group date' id='dtpFechaModificar' data-link-field="fecha_cierre" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                <input type='text' class="form-control" placeholder="Fecha de relevamiento" id="B_fecha_modificar" value=" "/>
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <h6 style="font-size:16px !important;">HORA PROPUESTA</h6>
            <input type="time" name="horarioRel" class="form-control" style="padding-top:0px;" id="hora_prop_mod" value="">
          </div>
          <div class="col-md-3">
            <h6 style="font-size:16px !important;">HORA EJECUCIÓN</h6>
            <input type="time" name="horarioRel" class="form-control" style="padding-top:0px;" id="hora_ejec_mod" value="">
          </div>
          <div class="col-md-3">
            <h6 style="font-size:16px !important;">TURNO</h6>
              <input type="text" class="form-control" id="turnoRelevadoMod" name="" value="" readonly="true">
          </div>
        </div>
        <div class="row" style="border-bottom:2px solid #ccc;">
          <div class="col-md-4">
            <h6 style="font-size:16px !important;">FISCALIZADOR DE TOMA</h6>
            <div class="input-group ">
              <input id="fiscalizadorMod" class="form-control" type="text" value="" autocomplete="off" placeholder="Nombre Fiscalizador" >
              <span class="input-group-btn" style="display:block;">
                <button id="agregarFiscaMod" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
              </span>
            </div>
          </div>
          <div class="col-md-5" style="border-right:1px solid #ccc">
            <table class="table" id="fiscalizadoresPartModif">
              <tbody>
              </tbody>
            </table>
          </div>

          <div class="col-md-3">
            <h5 style="font-size:15px !important">A: Abierta</h5>
            <h5 style="font-size:15px !important">C: Cerrada</h5>
            <h5 style="font-size:15px !important">T: En Torneo</h5>
          </div>

        </div>
        <br><br>
        <div class="row">
          <table id="tablaModificar"class="table table-fixed table-striped ">
            <thead style="height:40px">
              <th class="col-xs-2"><h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">JUEGO</h6> </th>
              <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold ">NRO MESA</h6> </th>
              <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">POSICIONES</h6> </th>
              <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">ESTADO (A|C|T)</h6> </th>
              <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">MÍNIMA</h6> </th>
              <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">MÁXIMA</h6> </th>

            </thead>

            <tbody style=" height:380px; width:100%">

            </tbody>
          </table>
          <div class="table table-responsive" id="dd" style="display:none;">

          <table class="table table-striped " >
              <tr id="moldeModificacion" class="filaClone" style="display:none">
                <td class="col-xs-2 juego_mod" rowspan="1" nowrap  style="text-align:center !important;"></td>
                <td class="col-xs-2 nro_mesa_mod" rowspan="1" nowrap style="text-align:center !important;"></td>
                <td class="col-xs-2 pos_mod" rowspan="1" nowrap style="text-align:center !important;"></td>
                <td class="col-xs-2">
                  <select class=" form-control estado_mod" id="" rowspan="1" style="text-align:center !important;">
                  </select>
                </td>
                <td class="col-xs-2">
                  <input type="text" style="text-align:center !important;" id="" rowspan="1" class=" form-control min_mod" name="" value="">
                </td>
                <td class="col-xs-2">
                  <input type="text"  style="text-align:center !important;" id="" rowspan="1" class=" form-control max_mod" name="" value="">

                </td>
              </tr>

          </table>
          </div>
        </div>
        <div class="row">
          <h6>OBSERVACIONES:</h6>
          <textarea name="name" id="obsModificacion" rows="2" style="resize:none;display:block;width:80% !important;margin-left:10px;align:center" wrap="off" class="estilotextarea4"></textarea>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-successAceptar" id="btn-guardar-modif" value="nuevo" hidden="true">GUARDAR</button>
      </div>
      <div id="mensajeErrorModificar" hidden>
        <br>
        <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
        <br>
        <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Deben completarse todos los datos solicitados.</span>
      </div> <!-- mensaje -->
    </div>
  </div>
</div>
</div>


<!-- MODAL VALIDAR -->
<div class="modal fade" id="modalValidar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"  data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width: 80%" >
    <div class="modal-content">
      <div class="modal-header" style="background-color:#4FC3F7;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| VALIDACIÓN DE RELEVAMIENTO DE VALORES DE APUESTAS </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">FECHA</h6>
                <input type='text' class="form-control"  id="B_fecha_val" value=" " readonly="true">
            </div>
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">HS PROPUESTA</h6>
              <input type="time" name="horarioRel" class="form-control" style="padding-top:0px;" id="hora_prop_val" value="" readonly="true">
            </div>
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">HS EJECUCIÓN</h6>
              <input type="time" name="horarioRel" class="form-control" style="padding-top:0px;" id="hora_ejec_val" value="" readonly="true">
            </div>
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">TURNO</h6>
                <input type="text" class="form-control" id="turnoRelevadoVal" name="" value="" readonly="true">
            </div>
          </div>

          <br>
          <br>
          <div class="row" style="border-collapse: collapse;position:relative">
            <div class="col-xs-5" style="border-right:1px solid #ccc">
              <table  style="width:100%;"  class="table table-responsive">
                <h6 style="text-align:center !important;font-size:16px; border-bottom:1px solid #ccc; padding-bottom: 10px;">MESAS ABIERTAS EN ESTA FECHA: </h6>
                <thead >
                  <th style="border-right:2px solid #ccc; padding-bottom:3px !important"> <h5 style="font-size:14px !important; text-align:center !important; ">JUEGO</h5> </th>
                  <th style="padding-bottom:3px !important"> <h5 style="font-size:14px !important; text-align:center !important">CANTIDAD</h5> </th>

                </thead>
                <tbody id="mesasPorJuego">

                </tbody>
              </table>
            </div>
            <div class="col-xs-5" style="border-right:1px solid #ccc">
              <table id="fiscalizadoresPartVal" style="width:100%;position:relative" class="table table-responsive" >
                <h6 style="font-size:16px !important;">FISCALIZADOR/ES DE TOMA</h6>

                <tbody>
                </tbody>
              </table>
            </div>
            <div class="col-xs-2">
              <table id="cumplio_min" style="width:100%;position:relative" class="table table-responsive" >
                <h6 style="font-size:16px !important;border-bottom:1px solid #ccc; padding-bottom: 10px;">CUMPLIÓ MÍNIMO:</h6>

                <tbody>
                </tbody>
              </table>

            </div>
          </div>
          <br>
          <br>
          <!-- <div class="row">
            <h6 class="cumpleMin" style="font-size:16px !important;">CUMPLIÓ MÍNIMO REQUERIDO:</h6>

          </div> -->
          <br>
          <div class="row">
            <table id="tablaValidar"class="table table-fixed table-striped " style="width:100%">
              <thead style="height:40px">
                <th class="col-xs-2"><h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">JUEGO</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold ">NRO MESA</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">POSICIONES</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">ESTADO (A|C|T)</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">MÍNIMA</h6> </th>
                <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">MÁXIMA</h6> </th>

              </thead>

              <tbody style=" height:380px; width:100%">

              </tbody>
            </table>
            <div class="table table-responsive" id="dd" style="display:none;">

            <table class="table table-fixed table-striped " style="width:100%">
                <tr id="moldeValidar" class="filaClone" style="display:none">
                  <td class="col-xs-2 juego_val" rowspan="1" nowrap  style="text-align:center !important;"></td>
                  <td class="col-xs-2 nro_mesa_val" rowspan="1" nowrap style="text-align:center !important;"></td>
                  <td class="col-xs-2 pos_val" rowspan="1" nowrap style="text-align:center !important;"></td>
                  <td class="col-xs-2">
                    <select class="col-xs-2 form-control estado_val" id="" rowspan="1" nowrap style="align:center !important;margin-left:7px">
                    </select>
                  </td>
                  <td class="col-xs-2">
                    <input type="text" style="align:center !important;margin-left:7px" id="" rowspan="1" nowrap class="col-xs-2 form-control min_val" name="" value="">
                  </td>
                  <td class="col-xs-2">
                    <input type="text"  style="align:center !important;margin-left:7px" id="" rowspan="1"  nowrap class="col-xs-2 form-control max_val" name="" value="">

                  </td>
                </tr>

            </table>
            </div>
          </div>

          <div class="row" style="position:relative">
            <h6 style="font-size:16px">OBSERVACIONES FISCALIZADOR:</h6>
            <textarea name="name" id="obsFiscalizador" rows="2" style="resize:none;display:block;width:80% !important;margin-left:10px;align:center" wrap="off" class="estilotextarea4" readonly="true"></textarea>
          </div>
          <div class="row" style="position:relative">
            <h6 style="font-size:16px">OBSERVACIONES FINALES:</h6>
            <textarea name="name" id="obsValidacion" rows="auto" style="resize:none;display:block;width:80% !important;margin-left:10px;align:center" wrap="off" class="estilotextarea4"></textarea>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-validar" value="nuevo" hidden="true">VALIDAR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- modal para modificar el minimo solicitado -->
<div class="modal fade" id="modalMinimo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"  data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width: 80%" >
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1DE9B6;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| REQUERIMIENTO VALOR MÍNIMO DE APUESTAS </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row" style="border-bottom:2px solid #ccc;">
            <h6 style="margin-left: 10px;font-size:17px;text-align:center !important;font-weight:bold" id="req">REQUERIMIENTOS ACTUALES</h6>
            <br>
            <div class="row">
              <div class="col-xs-6">
                <h6 style="font-size:16px; text-align:center !important">PESOS</h6>
                <h6 style="font-size:16px; margin-left:20px;border-bottom:1px solid #ccc" id="juegoMinimo">Juego: </h6>
                <br>
                <h6 style="font-size:16px; margin-left:20px;border-bottom:1px solid #ccc" id="apuestaMinimo">Apuesta Mínima: </h6>
                <br>
                <h6 style="font-size:16px; margin-left:20px;border-bottom:1px solid #ccc" id="cantMinimo">Cantidad de mesas abiertas: </h6>
                <br>
              </div>
              <div class="col-xs-6" style="border-left:1px solid #ccc">
                <h6 style="font-size:16px;text-align:center !important">DÓLARES</h6>
                <h6 style="font-size:16px; margin-left:20px;border-bottom:1px solid #ccc" id="juegoMinimoDol">Juego: </h6>
                <br>
                <h6 style="font-size:16px; margin-left:20px;border-bottom:1px solid #ccc" id="apuestaMinimoDol">Apuesta Mínima: </h6>
                <br>
                <h6 style="font-size:16px; margin-left:20px;border-bottom:1px solid #ccc" id="cantMinimoDol">Cantidad de mesas abiertas: </h6>
                <br>
              </div>
            </div>


          </div>
          <div class="row">
            <h6 style="margin-left: 10px;font-size:17px;text-align:center !important; font-weight:bold">MODIFICACIONES:</h6>
            <br>
            <div class="row">
              <div class="col-xs-6" style="border-right:1px solid #ccc">
                <h6 style="font-size:16px; text-align:center !important;border-bottom:1px solid #ccc">PESOS</h6>
                <br>
                <div class="col-xs-3">
                  <h6 style="font-size:16px">Juego:</h6>
                  <input type="text" class="form-control" id="juegoNuevo" name="" value="">
                </div>
                <div class="col-xs-4">
                  <h6 style="font-size:16px">Apuesta Min.:</h6>
                  <input type="text" class="form-control" id="apuestaNueva" name="" value="">
                </div>
                <div class="col-xs-5">
                  <h6 style="font-size:16px">Cant. de Mesas Abiertas:</h6>
                  <input type="text" class="form-control" id="cantidadNueva" name="" value="">
                </div>
              </div>
              <div class="col-xs-6">
                <h6 style="font-size:16px;text-align:center !important;border-bottom:1px solid #ccc">DÓLARES</h6>
                <br>
                <div class="col-xs-3">
                  <h6 style="font-size:16px">Juego:</h6>
                  <input type="text" class="form-control" id="juegoNuevoDol" name="" value="">
                </div>
                <div class="col-xs-4">
                  <h6 style="font-size:16px">Apuesta Min.:</h6>
                  <input type="text" class="form-control" id="apuestaNuevaDol" name="" value="">
                </div>
                <div class="col-xs-5">
                  <h6 style="font-size:16px">Cant. de Mesas Abiertas:</h6>
                  <input type="text" class="form-control" id="cantidadNuevaDol" name="" value="">
                </div>
              </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-guardar-minimo" value="nuevo" hidden="true">MODIFICAR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL BACKUP -->
<div class="modal fade" id="modalCargaBackUp" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width: 80%" >
    <div class="modal-content">
      <div class="modal-header" style="background-color:#1DE9B6;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| CARGA DE RELEVAMIENTO DE VALORES DE APUESTAS </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row" style="border-bottom:1px solid #ccc">
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">FECHA GENERACIÓN </h6>
              <div class="form-group">
                <div class='input-group date' id='dtpFechaBUp'  data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="Fecha de relevamiento" id="B_fecha_bup" value=" "/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <h6 style="font-size:16px !important;">FECHA PRODUCCIÓN</h6>
              <div class="form-group">
                <div class='input-group date' id='dtpFechaBUpEjecucion'  data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="Fecha de relevamiento" id="B_fecha_bupEj" value=" "/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
                <h6 style="font-size:16px !important;">TURNO</h6>
                <input type="text" class="form-control" id="turnoRelevadoBUp" name="" value="" >
            </div>
            <div class="col-md-2">
              <button type="button" id="buscarBackUp" class="button btn-infoBuscar" style="margin-top:32px" name="button">BUSCAR</button>
              </div>
            </div>

            <div class="row desplegarCarga">

              <div class="row ">
                <div class="row">
                  <div class="col-md-2">
                    <h6 style="font-size:16px !important;">HS PROPUESTA</h6>
                  <input type="time" name="horarioRel" class="form-control" style="padding-top:0px;" id="hora_prop_BUp" value="">
                  </div>
                  <div class="col-md-2">
                    <h6 style="font-size:16px !important;">HS EJECUCIÓN</h6>
                    <input type="time" name="horarioRel" class="form-control" style="padding-top:0px;" id="hora_ejec_BUp" value="">
                  </div>
                  <div class="col-md-3">
                    <h6 style="font-size:15px !important;">FISCALIZADOR DE TOMA</h6>
                    <div class="input-group ">
                      <input id="fiscalizadorBUp" class="form-control" type="text" value="" autocomplete="off" placeholder="Nombre Fiscalizador" >
                      <span class="input-group-btn" style="display:block;">
                        <button id="agregarFiscaBUp" class="btn btn-default btn-lista-datos" data-carga="backup" type="button"><i class="fa fa-plus"></i></button>
                      </span>
                    </div>
                  </div>
                  <div class="col-md-4" style="margin-left:25px !important">
                    <table class="table" id="fiscalizadoresPartBUp">
                      <tbody >
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <br>
              <div class="row">
                <table id="tablaCargaBUp"class="table table-fixed table-striped ">
                  <thead style="height:40px">
                    <th class="col-xs-2"><h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">JUEGO</h6> </th>
                    <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold ">NRO MESA</h6> </th>
                    <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">POSICIONES</h6> </th>
                    <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">ESTADO (A|C|T)</h6> </th>
                    <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">MÍNIMA</h6> </th>
                    <th class="col-xs-2"> <h6 style="text-align:center !important;color:#212121 !important;font-size:17px;font-weight:bold">MÁXIMA</h6> </th>

                  </thead>

                  <tbody style=" height:420px; width:100%">

                  </tbody>
                </table>
                <div class="table table-responsive" id="pp" style="display:none;">

                <table class="table table-striped " >
                    <tr id="moldeBUp" class="filaClone" style="display:none">
                      <td class="col-xs-2 juego_up" rowspan="1" nowrap  style="text-align:center !important;"></td>
                      <td class="col-xs-2 nro_mesa_up" rowspan="1" nowrap style="text-align:center !important;"></td>
                      <td class="col-xs-2 pos_up" rowspan="1" nowrap style="text-align:center !important;"></td>
                      <td class="col-xs-2">
                        <select class=" form-control estado_up" id="" rowspan="1" style="text-align:center !important;">
                        </select>
                      </td>
                      <td class="col-xs-2">
                        <input type="text" style="text-align:center !important;" id="" rowspan="1" class=" form-control min_up" name="" value="">
                      </td>
                      <td class="col-xs-2">
                        <input type="text"  style="text-align:center !important;" id="" rowspan="1" class=" form-control max_up" name="" value="">

                      </td>
                    </tr>

                </table>
                </div>
              </div>
              <div class="row">
                <h6 style="font-size:16px;font-weight:bold">OBSERVACIONES:</h6>
                <textarea name="name" id="obsBUp" rows="2" style="resize:none;display:block;width:80% !important;margin-left:10px;align:center" wrap="off" class="estilotextarea4"></textarea>
              </div>
            </div>
        </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="btn-guardar-backUp" value="nuevo" hidden="true">GUARDAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>

          </div>

          <div id="mensajeErrorCargaBUp" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Deben completarse todos los datos solicitados.</span>
          </div> <!-- mensaje -->
          <div id="mensajeErrorBuscarBUp" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">No se ha encontrado Relevamientos que coincidan con los datos ingresados.</span>
          </div> <!-- mensaje -->
        </div>

      </div>
    </div>
  </div>
</div>


<!-- MODAL PARA GENERAR PLANILLAS  DE BACK UP -->
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





  @endsection


  <!-- Comienza modal de ayuda -->
  @section('tituloDeAyuda')
  <h3 class="modal-title" style="color: #fff;">| AYUDA</h3>
  @endsection
  @section('contenidoAyuda')
  <div class="col-md-12">
    <h6>GESTIÓN DE CIERRES Y APERTURAS</h6>
    <p>
      Desde esta sección se podrán visualizar los relevamientos que fueron generados por el sistema.
      Estos relevamientos son uno por cada turno existente.
      <br><br>

      <h6>DESCARGA DE PLANILLAS</h6>
      Desde el botón "GENERAR RELEVAMIENTO", podrán descargar las planillas para el día y 4 más (relevamientos SIN SISTEMA).
      Estos se encuentran en un archivo ".zip", el cual a su vez contiene tantos archivos ".zip" como turnos distintos haya,
       con sus respectivas planillas.
      <br><br>
      <h6>CARGA DE RELEVAMIENTOS</h6>
      En el listado de relevamientos, figuran los que se crean cada día (fecha de generación del día y fecha de producción del día).
      A estos últimos, se los debe buscar en el listado, utilizando los filtros de ser necesario. En la fila posee un botón para realizar
      la carga del relevamiento.
      Si se desea cargar un relevamiento que haya sido generado en una fecha anterior a la fecha de producción, se deberá hacer
      click en el botón "CARGAR RELEVAMIENTO SIN SISTEMA", se lo buscará con los datos solicitados y se procederá a realizar su carga.
      (Deberá ingresar un único valor en los campos "mínimo" y "máximo" del relevamiento de cada mesa).
    </p>
    <h6>VALIDACIÓN</h6>
    Una vez realizada la carga, se habilita la validación, con la posibilidad de realizar comentarios sobre lo relevado.
    <h6>VISUALIZACIÓN</h6>
    Finalmente, los relevamientos se pueden visualizar en una planilla.
  </div>
  @endsection

  @section('scripts')

    <!-- JavaScript personalizado -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <script src="js/inputSpinner.js" type="text/javascript"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>

    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    <script src="/js/jquery-ui.js" type="text/javascript"></script>

    <script src="js/math.min.js" type="text/javascript"></script>

    <!-- JS paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>

    <script src="js/Apuestas/apuestas.js" type="text/javascript" charset="utf-8"></script>

  @endsection
