@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php

use Illuminate\Http\Request;
use App\Http\Controllers\CasinoController;

$observadas_por_fila = 23;
$total_usado = 98; //Dejo un poco de espacio en el borde.
$porcentaje_por_observada = $total_usado/$observadas_por_fila;
?>

@section('estilos')
<!-- <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/> -->
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel="stylesheet" href="css/lista-datos.css">
<style>
.chico {
    font-size: 95%;
}
.borde {
  border-bottom: 1px solid #ddd !important;
  border-right: 1px solid #ddd !important;
  border-top: 1px solid #ddd !important;
  border-left: 1px solid #ddd !important;
}
.input-chico {
  padding: 0px 0px !important;
  font-size: 12px !important;
  text-align: center !important;
  line-height: 1;
  border-radius: 0px;
}
.subrayado{
  border-bottom: 2px solid rgb(255,168,141) !important;
  color: rgb(238, 108, 68) !important;
  background-color: unset !important;
}
.correcto{
  color: green;
}
.incorrecto{
  color: red;
}
.sombreado{
  color: grey;
}
.borde_superior {
  border-top: solid 1px black !important;
}
.tabTitle{
  text-align: center;
  color: #bbb;
  padding-bottom: 5px;
  padding-top: 5px;
  margin-bottom: 0px;
  border-top: 1px solid #ccc;
  border-left: 1px solid #ccc;
  border-right: 1px solid #ccc;
  border-top-left-radius: 10px;
  border-top-right-radius: 10px;
  min-width: 33%;
  background-color: #f3f3f3;
}
.tabs{
  background-color:rgb(250,250,250);
  padding-bottom: 0px;
  border-bottom: 1px solid #ccc;
  display: flex;
	flex-direction: row;
	flex-wrap: nowrap;
	justify-content: space-between;
	align-items: stretch;
	align-content: stretch;
}
.left{
  text-align: left;
}
</style>
@endsection

                <div class="row">
                  <div class="col-lg-12 col-xl-9"> <!-- columna TABLA CASINOS -->
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
                                    <div class="col-md-3">
                                      <h5>Fecha</h5>
                                      <div class='input-group date' id='dtpBuscadorFecha' data-link-field="buscadorFecha" data-link-format="yyyy-mm-dd">
                                        <input id="" type='text' class="form-control" placeholder="Fecha RELEVAMIENTO"/>
                                        <span class="input-group-addon">
                                          <span class="glyphicon glyphicon-calendar"></span>
                                        </span>

                                        <input type="hidden" id="buscadorFecha" value=""/>
                                      </div>
                                   </div>
                                    <div class="col-md-3">
                                        <h5>Casino</h5>
                                        <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                            <option value="">-Todos los Casinos-</option>
                                            @foreach ($casinos as $casino)
                                              <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <h5>Estado Relevamiento</h5>
                                        <select id="buscadorEstado" class="form-control" name="">
                                            <option value="0">-Todos los estados-</option>
                                            @foreach($estados as $estado)
                                              <option id="estado{{$estado->id_estado_relevamiento}}" value="{{$estado->id_estado_relevamiento}}">{{$estado->descripcion}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                      <h5 style="color:#f5f5f5;">boton buscar</h5>
                                      <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                    </div>
                                  </div>
                                  <br>
                                </div>
                              </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                      <div class="col-md-12">
                    <div class="panel panel-default">

                      <div class="panel-heading">
                        <h4>LAYOUT TOTAL GENERADO POR EL SISTEMA</h4>
                      </div>

                      <div class="panel-body">
                        <table id="tablaLayouts" class="table table-fixed tablesorter">
                          <thead>
                            <tr>
                              <th class="left col-xs-2 activa" value="layout_total.fecha" estado="desc">FECHA <i class="fas fa-sort-down"></i></th>
                              <th class="left col-xs-2" value="casino.nombre" estado="">CASINO  <i class="fas fa-sort"></i></th>
                              <th class="left col-xs-2" value="sector.descripcion" estado="">SECTOR  <i class="fas fa-sort"></i></th>
                              <th class="col-xs-1" value="layout_total.turno" estado="">TURNO <i class="fas fa-sort"></i></th>
                              <th class="left col-xs-2" value="estado_relevamiento.descripcion" estado="">ESTADO <i class="fas fa-sort"></i></th>
                              <th class="left col-xs-3">ACCIÓN </th>
                            </tr>
                          </thead>
                          <tbody id="cuerpoTabla" style="height: 350px;">

                          </tbody>
                        </table>
                        <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                      </div>
                      </div>
                    </div>
                  </div>
                </div>
            <!-- /.col-lg-12 col-xl-9 -->
            <div class="col-lg-12 col-xl-3">
              <div class="row">
                  <div class="col-md-12">
                   <a href="" id="btn-nuevoLayoutTotal" style="text-decoration: none;">
                    <div class="panel panel-default panelBotonNuevo">
                        <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                        <div class="backgroundNuevo"></div>
                        <div class="row">
                            <div class="col-xs-12">
                              <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">GENERAR CONTROL LAYOUT</h4>
                              </center>
                            </div>
                        </div>
                    </div>
                   </a>
                  </div>
              </div>

              <div class="row">
                  <div class="col-md-12">
                   <a href="" id="btn-layoutSinSistema" style="text-decoration: none;">
                    <div class="panel panel-default panelBotonNuevo">
                        <center><img class="imgNuevo" src="/img/logos/relevamientos_sin_sistema_white.png"><center>
                        <div class="backgroundNuevo"></div>
                        <div class="row">
                            <div class="col-xs-12">
                              <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">GENERAR CONTROL LAYOUT SIN SISTEMA</h4>
                              </center>
                            </div>
                        </div>
                    </div>
                   </a>
                  </div>
              </div>

            </div>

        </div>  <!-- /#row -->

    <!-- Modal layout -->
    <div class="modal fade" id="modalNuevoLayoutTotal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button type="button" class="close minimizar" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| NUEVO CONTROL LAYOUT </h3>
                </div>

                <div  id="colapsado" class="collapse in">

                <div class="modal-body modalCuerpo">
                  <form id="frmLayoutTotal" name="frmLayoutTotal" class="form-horizontal" novalidate="">
                          <div class="row">
                            <div class="col-md-6">
                              <h5>FECHA</h5>
                              <input id="fechaActual" type='text' class="form-control" readonly>
                              <input id="fechaDate" type="text" name="" hidden>
                              <br>
                            </div>
                            <div class="col-md-6">
                              <h5>CASINO</h5>
                              <select id="casino" class="form-control selectCasinos" name="">
                                <option value="">- Seleccione un casino -</option>
                                @foreach ($casinos as $casino)
                                <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-6">
                              <h5>SECTOR</h5>
                              <select class="form-control selectSector">
                              </select>
                            </div>
                            <div class="col-md-6">
                              <h5>Turno</h5>
                              @if(count($casinos) != 1)
                                <input id="turno" class="form-control" type="text" name="" value="" placeholder="" readonly="true"></input>
                              @elseif(!empty(CasinoController::getInstancia()->obtenerTurno($casinos[0]->id_casino)['turno']))
                                <input id="turno" class="form-control" type="text" name="" value="{{CasinoController::getInstancia()->obtenerTurno($casinos[0]->id_casino)['turno'][0]}}" placeholder="" readonly="true"></input>
                              @else
                                <input id="turno" class="form-control" type="text" name="" value="" placeholder="No existe turno" readonly="true"></input>
                              @endif
                        		</div>
                          </div>
                  </form>

                </div>
                <div id="iconoCarga" class="sk-folding-cube">
                  <div class="sk-cube1 sk-cube"></div>
                  <div class="sk-cube2 sk-cube"></div>
                  <div class="sk-cube4 sk-cube"></div>
                  <div class="sk-cube3 sk-cube"></div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-generar" value="nuevo">GENERAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <input type="hidden" id="id_casino" name="id_casino" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

<div class="modal fade" id="modalLayoutSinSistema" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
          <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
          <button type="button" class="close minimizar" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoSinSistema" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
          <h3 class="modal-title">| NUEVO CONTROL LAYOUT SIN SISTEMA</h3>
        </div>
        <div  id="colapsadoSinSistema" class="collapse in">
        <div class="modal-body modalCuerpo">
          <form id="frmLayoutSinSistema" name="frmLayoutSinSistema" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-md-6">
                <h5>FECHA DE CONTROL LAYOUT</h5>
                <div class='input-group date' id='fechaControlSinSistema' data-link-field="fechaLayoutSinSistema" data-link-format="yyyy-mm-dd">
                  <input id="fecha_backup" type='text' class="form-control" placeholder="Fecha de Relevamiento"/>
                  <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span>
                  </span>
                  <input type="hidden" id="fechaLayoutSinSistema" value=""/>
                </div>
                <br>
              </div>
              <div class="col-md-6">
                <h5>FECHA DE GENERACIÓN</h5>
                <div class='input-group date' id='fechaGeneracion' data-link-field="fechaGeneracionSinSistema" data-link-format="yyyy-mm-dd">
                  <input id="fecha_generacion_backup" type='text' class="form-control" placeholder="Fecha de Generación"/>
                  <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span>
                  </span>
                  <input type="hidden" id="fechaGeneracionSinSistema" value=""/>
                </div>
                <br>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <h5>CASINO</h5>
                <select id="casinoSinSistema" class="form-control selectCasinos" name="">
                  <option value="">- Seleccione un casino -</option>
                  @foreach ($casinos as $casino)
                  <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <h5>TURNO</h5>
                <input id="turnoSinSistema" type="number" class="form-control" name=""/>
              </div>
              <div class="col-md-4">
                <h5>SECTOR</h5>
                <select class="form-control selectSector">
                </select>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-backup" value="nuevo">USAR RELEVAMIENTO BACKUP</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          <input type="hidden" id="id_casino" name="id_casino" value="0">
        </div>
      </div>
    </div>
  </div>
</div>

<table hidden>
  <tr id="filaEjemploInactivasLayout" class="NivelLayout" id_nivel_layout="0">
    <td>
      <select type="text" class="form-control sector">
      </select>
    </td>
    <td>
      <input type="text" placeholder="Isla" class="form-control nro_isla"/>
    </td>
    <td>
      <input type="text" placeholder="N° ADMIN" class="form-control nro_admin"/>
    </td>
    <td>
      <select type="text" class="form-control co">
        <option value="">- C.O. -</option>
        @foreach($codigos as $c)
        <option value="{{$c->codigo}}" title="{{$c->descripcion}}">{{$c->codigo}} -> {{$c->descripcion}}</option>
        @endforeach
      </select>
    </td>
    <td>
      <input type="checkbox" class="form-control pb" style="text-align: center;"/>
    </td>
    <td>
      <button class="btn btn-dancer borrarFila borrarNivelLayout" type="button">
        <i class="fa fa-fw fa-trash"></i>
      </button>
      <a class="btn btn-success pop gestion_maquina" type="button" target="_blank" 
          data-placement="top" data-trigger="hover" title="GESTIONAR MÁQUINA" data-content="Ir a sección máquina">
        <i class="fa fa-fw fa-wrench"></i>
      </a>
    </td>
  </tr>
</table>

<div class="modal fade" id="modalLayoutTotal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:94%;">
    <div class="modal-content">
      <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:red;padding-bottom: 8px;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button type="button" class="close minimizar" data-toggle="collapse" data-minimizar="true" data-target="#modalLayoutTotalColapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">ABCDEFGH</h3>
      </div>
      <div id="modalLayoutTotalColapsado" class="collapse in">
        <div class="modal-body modalCuerpo">
          <div class="row">
            <div class="col-lg-2 col-lg-offset-1">
              <h5>FECHA DE LAYOUT</h5>
              <input id="fecha_layout" type='text' class="form-control" readonly>
            </div>
            <div class="col-lg-2">
              <h5>FECHA DE GENERACIÓN</h5>
              <input id="fecha_generacion_layout" type='text' class="form-control" readonly>
            </div>
            <div class="col-lg-2">
              <h5>CASINO</h5>
              <input id="casino_layout" type='text' class="form-control" readonly>
            </div>
            <div class="col-lg-2">
              <h5>TURNO</h5>
              <input id="turno_layout" type='text' class="form-control" readonly>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-3 col-xs-offset-1">
              <h5>FISCALIZADOR CARGA</h5>
              <input id="fiscalizador_carga_layout" type="text" class="form-control" readonly>
            </div>
            <div class="col-xs-3">
              <h5>FISCALIZADOR TOMA</h5>
              <input id="fiscalizador_toma_layout" data-fisca="" class="form-control" size="100" type="text"/>
            </div>
            <div class="col-xs-4">
              <h5>FECHA EJECUCIÓN</h5>
              <div class='input-group date' id='dtpFechaEjecucionLayout' data-link-field="fecha_ejecucion_layout_hidden" data-date-format="dd MM yyyy HH:ii" data-link-format="yyyy-mm-dd HH:ii">
                  <input type='text' class="form-control" placeholder="Fecha de ejecución del control" id="fecha_ejecucion_layout" />
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
              <input type="hidden" id="fecha_ejecucion_layout_hidden" value=""/>
            </div>
          </div>
          <div class="row tabs">
            <h4 class="tabTitle" tabDiv="#observadas_layout" id="tabObservadas"><b>OBSERVADAS</b></h4>
            <h4 class="tabTitle" tabDiv="#inactivas_layout"><b>INACTIVAS</b></h4>
            <h4 class="tabTitle" tabDiv="#diferencias_layout" id="tabDiferencias"><b>DIFERENCIAS</b></h4>
          </div>
          <div id="observadas_layout" class="row tabDiv" style="overflow-y: scroll;height: 450px;">
          </div>
          <div id="inactivas_layout" class="row tabDiv" style="overflow-y: scroll;height: 450px;">
            <div class="row">
              <div class="col-md-12">
                <table class="table">
                  <thead>
                    <tr>
                      <th width="20%" style="text-align: center;">SECTOR</th>
                      <th width="16%" style="text-align: center;">ISLA</th>
                      <th width="12%" style="text-align: center;">N° ADMIN</th>
                      <th width="22%" style="text-align: center;">C.O</th>
                      <th width="14%" style="text-align: center;">PROGRESIVO BLOQ</th>
                      <th width="10%"></th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
                <button id="btn_agregar_inactiva_layout" class="btn btn-success" type="button"><i class="fa fa-fw fa-plus"></i> AGREGAR MÁQUINA</button>
              </div>
            </div>
          </div>
          <div id="diferencias_layout" class="row tabDiv" style="overflow-y: scroll;height: 450px;">
          </div>
          <div class="row" style="border-top: 1px solid #ddd">
            <div id="total_observadas_layout" class="col-md-8 col-md-offset-2">
              <h5>CANTIDAD TOTAL DE MÁQUINAS OBSERVADAS:</h5>
              <input disabled>
            </div>
          </div>
          <div class="row">
            <div class="col-md-8 col-md-offset-2">
              <h5>OBSERVACIONES FISCALIZADOR</h5>
              <textarea id="observaciones_fisca_layout" class="form-control" style="resize:vertical;"></textarea>
            </div>
          </div>
          <div class="row">
            <div class="col-md-8 col-md-offset-2">
              <h5>OBSERVACIONES</h5>
              <textarea id="observaciones_adm_layout" class="form-control" style="resize:vertical;"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warningModificar" id="btn_guardartemp_layout" style="position:absolute;left:20px;">GUARDAR TEMPORALMENTE</button>
          <button type="button" class="btn btn-successAceptar" id="btn_validar_layout">VALIDAR</button>
          <button type="button" class="btn btn-successAceptar" id="btn_finalizar_layout">FINALIZAR</button>
          <button type="button" class="btn btn-default" id="btn_salir_layout" data-dismiss="modal">SALIR</button>
          <div id="mensaje_cambios_layout">
            <br>
            <span style="font-family:'Roboto-Black'; color:#EF5350;">CAMBIOS SIN GUARDAR</span>
            <br>
            <span style="font-family:'Roboto'; color:#555;">Presione SALIR nuevamente para salir sin guardar cambios.</span>
          </div>
          <div id="mensaje_confirmar_layout">
            <br>
            <span style="font-family:'Roboto-Black'; color:#EF5350;">Ocurrieron errores.</span>
            <br>
            <span style="font-family:'Roboto'; color:#555;">Si presiona finalizar nuevamente se enviará de todas formas.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

  <div id="sectorEjemplo" class="sector" hidden>
    <hr/>
    <b class="nombre">NOMBRE SECTOR</b>
    <table class="tablaIslas table table-fixed tablesorter">
      <thead class="cabezeraTabla">
        <tr>
          @for ($i=1;$i<=$observadas_por_fila;$i++)
          <th style="text-align: center;width: {{$porcentaje_por_observada}}%" class="{{$i}}"></th>
          @endfor
        </tr>
      </thead>
      <tbody class="cuerpoTabla">
      </tbody>
    </table>
  </div>

  <!-- FILAS EJEMPLO -->
  <!-- Necesito crear tablas por algun motivo sino se rompe el html -->
<table hidden>
  <tr id="filaEjemploObservadas" style="display: block;" observadas_por_fila="{{$observadas_por_fila}}"></tr>
  <tr id="filaEjemplo" style="display: none;">
    <td class="left col-xs-2 fecha">99 Test 9999</td>
    <td class="left col-xs-2 casino">CASINO99</td>
    <td class="left col-xs-2 sector">SECTOR99</td>
    <td class="col-xs-1 turno" style="text-align: center;">99</td>
    <td class="col-xs-2" style="text-align: left;">
      <i class="fas fa-fw fa-dot-circle icono_estado"></i>
      <span class="estado">ESTADO99</span>
    </td>
    <td class="left col-xs-3 acciones">
      @if($usuario->tienePermiso('validar_layout_total'))
      <button class="btn btn-info ver" title="VER LAYOUT TOTAL" type="button" value="-1">
        <i class="fa fa-fw fa-search-plus"></i>
      </button>
      @endif
      <button class="btn btn-info planilla" title="PLANILLA RELEVAMIENTO" type="button" value="-1">
        <i class="far fa-fw fa-file-alt"></i>
      </button>
      @if($usuario->tienePermiso('carga_layout_total'))
      <button class="btn btn-warning carga" title="CARGAR LAYOUT TOTAL" type="button" value="-1">
        <i class="fa fa-fw fa-upload"></i>
      </button>
      @endif
      @if($usuario->tienePermiso('validar_layout_total'))
      <button class="btn btn-success validar" title="VALIDAR RELEVAMIENTO" type="button" value="-1">
        <i class="fa fa-fw fa-check"></i>
      </button>
      <button class="btn btn-info imprimir" title="PLANILLA COMPLETADA" type="button" value="-1">
        <i class="fa fa-fw fa-print"></i>
      </button>
      <button class="btn btn-info eliminar" title="ELIMINAR LAYOUT TOTAL" type="button" value="-1">
        <i class="fa fa-fw fa-trash"></i>
      </button>
      @endif
    </td>
  </tr>
</table>

<table hidden>
  <tr>
    <td id="islaEjemplo" style="width: {{$porcentaje_por_observada}}%;" class="isla borde">
      <div>
        <div style="text-align: center;" class="textoIsla">-1</div>
        <input class="form-control inputIsla input-chico"/>
      </div>
    </td>
    <td id="islaEjemploValidar" style="width: {{$porcentaje_por_observada}}%;" class="isla borde chico ">
      <div>
        <div style="text-align: center;" class="textoIsla">-1</div>
        <div style="text-align: center;">
          <span class="observado"></span>
          <span class="inactivas incorrecto"></span>
          <span>(<span class="sistema"></span>)</span>
        </div>
      </div>
    </td>
  </tr>
</table>

<div hidden>
  <table id="tablaDiferenciasEjemplo" class="table table-fixed table-bordered tablesorter col-lg-12">
    <thead>
      <tr>
        <th class="col-lg-2" style="text-align: center;">SECTOR</th>
        <th class="col-lg-2" style="text-align: center;">INACTIVAS</th>
        <th class="col-lg-3" style="text-align: center;">OBSERVADAS</th>
        <th class="col-lg-3" style="text-align: center;">TOTAL SISTEMA</th>
        <th class="col-lg-2" style="text-align: center;">DIFERENCIA</th>
      </tr>
    </thead>
    <tbody class="cuerpoTablaDiferencias">
      <tr class="diferenciasFilaEjemplo">
        <td class="diferenciasSector col-lg-2" style="text-align: center;">SECTOR999</td>
        <td class="diferenciasInactivas col-lg-2" style="text-align: right;">ERROR</td>
        <td class="diferenciasObservadas col-lg-3" style="text-align: right;">ERROR</td>
        <td class="diferenciasTotalSistema col-lg-3" style="text-align: right;">ERROR</td>
        <td class="diferenciasDiferencia col-lg-2" style="text-align: right;">ERROR</td>
      </tr>
    </tbody>
  </table>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
      </div>
      <div class="modal-body" style="color:#fff; background-color:#EF5350;">
        <form id="frmEliminar" name="frmEliminarLayout" class="form-horizontal" novalidate="">
          <div class="form-group error ">
            <div class="col-xs-12">
              <strong>¿Seguro desea eliminar el LAYOUT?</strong>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarModal" value="-1" data-dismiss="modal">ELIMINAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
      </div>
    </div>
  </div>
</div>
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| LAYOUT TOTAL</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Layout Total</h5>
      <p>
        En su generación, estarán controlados los turnos de toma, correspondientes a la hora en que se toman diariamente.
        Además, la posibilidad de generar estas planillas de forma offline, donde se genera un archivo .rar para la toma de los próximos 7 días.
      </p>
      <h5>Edición de planillas</h5>
      <p>
        Se pueden observar planillas de layout total del parque de máquinas.
        Su división es por sectores, el número observado es el n° de isla, mientras que el que se encuentra en paréntesis, es la cantidad de máquinas asociadas a dicha isla.
        Esta disposición facilita la toma del relevamiento del parque de máquinas, ya que divide el recorrido para optimizar los tiempos de toma de datos.
        En su segunda hoja de planilla generada, se observan campos de aquellas máquinas que dieron algún tipo de error o que no se pudieron tomar debido a problemas técnicos;
        los cuales, están detallados en códigos como los mas frecuentes, y se detalla con una cruz en el campo de PB (progresivo bloqueado) si éste genera un error a la hora de tomar este dato
        en ese preciso instante.
        Según su estado, se podrá cargar, validar e imprimir el archivo final de esta toma de valores.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionLayoutTotal.js" charset="utf-8"></script>
    <script src="js/paginacion.js" charset="utf-8"></script>
    <script src="js/lista-datos.js" charset="utf-8"></script>

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    <script src="js/inputSpinner.js" type="text/javascript"></script>
    @endsection
