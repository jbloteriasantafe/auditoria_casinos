@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
setlocale(LC_TIME, 'es_ES.UTF-8');
$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
?>

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="/css/paginacion.css">
<link rel="stylesheet" href="css/lista-datos.css">
@endsection

<div class="row">
  <div class="col-xl-3">
    @if($usuario->tienePermiso('relevamiento_cargar'))
    <div class="row">
      <div class="col-xl-12 col-md-4">
        <a href="" id="btn-nuevoRelevamiento" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
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
      <div class="col-xl-12 col-md-4">
        <a href="" id="btn-relevamientoSinSistema" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/relevamientos_sin_sistema_white.png"><center>
            <div class="backgroundNuevo"></div>
            <div class="row">
              <div class="col-xs-12">
                <center>
                  <h5 class="txtLogo">+</h5>
                  <h4 class="txtNuevo">RELEVAMIENTOS SIN SISTEMA</h4>
                </center>
              </div>
            </div>
          </div>
        </a>
      </div>
      @endif
      @if($usuario->tienePermiso('relevamiento_selec_maquinas_por_relevamiento'))
      <div class="col-xl-12 col-md-4">
        <a href="" id="btn-maquinasPorRelevamiento" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <center><img class="imgNuevo" src="/img/logos/tragaperras_white.png"><center>
            <div class="backgroundNuevo"></div>
            <div class="row">
              <div class="col-xs-12">
                <center>
                  <h5 class="txtLogo">+</h5>
                  <h4 class="txtNuevo">MÁQUINAS POR RELEVAMIENTO</h4>
                </center>
              </div>
            </div>
          </div>
        </a>
      </div>
      @endif
    </div>
  </div>
  <div class="col-xl-9"> <!-- columna TABLA CASINOS -->
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
                  <h5>Fecha de Relevamiento</h5>
                  <div class="form-group">
                    <div class='input-group date' id='dtpBuscadorFecha' data-link-field="buscadorFecha" data-link-format="yyyy-mm-dd">
                     <input type='text' class="form-control" placeholder="Fecha de relevamiento" id="B_fecharelevamiento"/>
                     <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                     <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="buscadorFecha" value=""/>
                  </div>
                </div>
                <div class="col-md-3">
                  <h5>Casino</h5>
                  <select id="buscadorCasino" class="form-control selectCasinos">
                    <option value="0">-Todos los Casinos-</option>
                    @foreach($casinos as $casino)
                    <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-3">
                  <h5>Sector</h5>
                  <select id="buscadorSector" class="form-control selectSector">
                    <option value="0">-Todos los sectores-</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <h5>Estado Relevamiento</h5>
                  <select id="buscadorEstado" class="form-control selectSector">
                    <option value="0">-Todos los estados-</option>
                    @foreach($estados as $estado)
                    <option value="{{$estado->id_estado_relevamiento}}">{{$estado->descripcion}}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="row">
                <center>
                  <h5 style="color:#f5f5f5;">boton buscar</h5>
                  <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                </center>
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
            <h4>Últimos relevamientos</h4>
          </div>
          <div class="panel-body">
            <table id="tablaRelevamientos" class="table table-fixed tablesorter">
              <thead>
                <tr>
                  <th class="col-xs-2 activa" value="relevamiento.fecha" estado="desc">FECHA REL.  <i class="fas fa-sort-down"></i></th>
                  <th class="col-xs-2" value="casino.nombre" estado="">CASINO  <i class="fas fa-sort"></i></th>
                  <th class="col-xs-2" value="sector.descripcion" estado="">SECTOR  <i class="fas fa-sort"></i></th>
                  <th class="col-xs-1" value="relevamiento.subrelevamiento" estado="">SUB  <i class="fas fa-sort"></i></th>
                  <th class="col-xs-2" value="estado_relevamiento.descripcion" estado="">ESTADO  <i class="fas fa-sort"></i></th>
                  <th class="col-xs-3" value="" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody style="height:275px;">
              </tbody>
            </table>
            <legend></legend>
            <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div><!-- /.col-lg-12 col-xl-9 -->
</div>  <!-- /#row -->

<!-- Modal Relevamientos -->
<div class="modal fade" id="modalMaquinasPorRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarMRelevamientos" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoMRelevamientos" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| MÁQUINAS POR RELEVAMIENTOS</h3>
      </div> <!-- /.modal-header -->
      <div id="colapsadoMRelevamientos" class="collapse in">
        <div class="modal-body modalCuerpo">
          <form id="frmMaquinasPorRelevamiento" name="frmMaquinasPorRelevamiento" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-md-4">
                <h5>CASINO</h5>
                <select id="casino" class="form-control" name="">
                  <option value="">- Seleccione un casino -</option>
                  @foreach ($usuario->casinos as $casino)
                  <option id="{{$casino->id_casino}}" value="{{$casino->codigo}}">{{$casino->nombre}}</option>
                  @endforeach
                </select>
                <br>
                <span id="alertaCasino" class="alertaSpan"></span>
              </div>
              <div class="col-md-4">
                <h5>SECTOR</h5>
                <select id="sector" class="form-control" name="">
                  <option value=""></option>
                </select>
                <br>
                <span id="alertaSector" class="alertaSpan"></span>
              </div>
              <div class="col-md-4">
                <h5>TIPO</h5>
                <select id="tipo_cantidad" class="form-control" name="">
                  <option value="">- Seleccione el tipo -</option>
                  @foreach($tipos_cantidad as $tipo_cantidad)
                  <option id="{{$tipo_cantidad->id_tipo_cantidad_maquinas_por_relevamiento}}">
                    {{$tipo_cantidad->descripcion}}
                  </option>
                  @endforeach
                </select>
                <br>
                <span id="alertaTipoCantidad" class="alertaSpan"></span>
              </div>
            </div>
            <div class="row">
              <div class="col-md-4">
                <h5>FECHA DESDE</h5>
                <div class='input-group date' id='dtpFechaDesde' data-link-field="fecha_desde" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
                <input class="form-control" type="hidden" id="fecha_desde" value=""/>
              </div>
              <div class="col-md-4">
                <h5>FECHA HASTA</h5>
                <div class='input-group date' id='dtpFechaHasta' data-link-field="fecha_hasta" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="Fecha Hasta" id="B_fecha_inicio"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
                <input class="form-control" type="hidden" id="fecha_hasta" value=""/>
              </div>
              <div class="col-md-4">
                <h5>MÁQUINAS</h5>
                <div class="input-group number-spinner">
                  <span class="input-group-btn">
                    <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="dwn">-</button>
                  </span>
                  <input id="cantidad_maquinas_por_relevamiento" type="text" class="form-control text-center" value="1">
                  <span class="input-group-btn">
                    <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="up">+</button>
                  </span>
                </div>
              </div>
            </div>
            <br>
            <br>
            <!-- DETALLES DE LAS MÁQUINAS POR DEFAULT Y TEMPORALES -->
            <div id="detalles" class="row">
              <div class="col-md-12">
                <h5 style="color:#333 !important;font-family:Roboto-Condensed;font-size:20px;font-weight:700;">DETALLES PARA EL SECTOR SELECCIONADO</h5>
                <br>
                <div class="row">
                  <div class="col-xs-6 col-md-4">
                    <h5>MÁQUINAS POR DEFECTO</h5>
                  </div>
                  <div class="col-xs-6 col-md-4">
                    <span id="maquinas_defecto" class="badge" style="background-color: #6dc7be;font-family:Roboto-Regular;font-size:18px;margin-top:5px;">15</span>
                  </div>
                </div> <!-- /.row -->
                <br>
                <div class="row">
                  <div class="col-md-12" style="height: 350px; overflow-y: scroll;">
                    <table id="maquinas_temporales" class="table">
                      <thead>
                        <th>DESDE FECHA</th>
                        <th>HASTA FECHA</th>
                        <th>CANTIDAD DE MÁQUINAS</th>
                        <th>ACCIÓN</th>
                      </thead>
                      <tbody>
                        <tr>
                          <td>12 Agosto 2017</td>
                          <td>18 Agosto 2017</td>
                          <td><span style="background-color: #6dc7be;font-family:Roboto-Regular;font-size:18px;" class="badge">21</span></td>
                          <td><i class="fa fa-trash"></i></td>
                        </tr>
                        <tr>
                          <td>25 Septiembre 2017</td>
                          <td>27 Septiembre 2017</td>
                          <td><span style="background-color: #6dc7be;font-family:Roboto-Regular;font-size:18px;" class="badge">12</span></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div> <!-- /.row -->
              </div> <!-- /.col-md-12 -->
            </div> <!-- /#detalles -->
          </form>
        </div> <!-- ./modal-body -->
        <!-- </div> -->
        <div class="modal-footer">
          <!-- Mensaje y botones para validación de carga temporal -->
          <p style="color:red;" id="mensajeTemporal" hidden >LAS FECHAS ELEGIDAS PISAN A OTRAS TEMPORALES DEFINIDAS ANTERIORMENTE</p>
          <button type="button" class="btn btn-successAceptar" id="btn-generarDeTodasFormas" hidden >GENERAR IGUAL</button>
          <button type="button" class="btn btn-default" id="btn-cancelarTemporal" hidden>CANCELAR CARGA TEMPORAL</button>
          <button type="button" class="btn btn-successAceptar" id="btn-generarMaquinasPorRelevamiento" value="nuevo">GENERAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          <input type="hidden" id="id_casino" name="id_casino" value="0">
        </div> <!-- /.modal-footer -->
      </div> <!-- /#collapsado -->
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div> <!-- /.modal -->

<!-- Modal Relevamientos -->
<div class="modal fade" id="modalRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
       <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
       <button id="btn-minimizarNuevo" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoNuevo" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
       <h3 class="modal-title">| NUEVO RELEVAMIENTO</h3>
      </div>
      <div id="colapsadoNuevo" class="collapse in">
        <div class="modal-body modalCuerpo">
          <form id="frmRelevamiento" name="frmRelevamiento" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-md-12">
                <h5>FECHA DE RELEVAMIENTO</h5>
                <input id="fechaActual" type='text' class="form-control" disabled style="text-align:center;">
                <input id="fechaDate" type="text" name="" hidden>
                <br>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <h5>CASINO</h5>
                <select id="casino" class="form-control" name="">
                  <option value="">- Seleccione un casino -</option>
                   @foreach ($usuario->casinos as $casino)
                   <option id="{{$casino->id_casino}}" value="{{$casino->codigo}}">{{$casino->nombre}}</option>
                   @endforeach
                </select>
                <br>
                <span id="alertaCasino" class="alertaSpan"></span>
              </div>
              <div class="col-md-6">
                <h5>SECTOR</h5>
                <select id="sector" class="form-control" name="">
                  <option value=""></option>
                </select>
                <br>
                <span id="alertaSector" class="alertaSpan"></span>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <h5>MÁQUINAS</h5>
                <input id="cantidad_maquinas" type="text" class="form-control" name="" value="" disabled>
              </div
              <div class="col-md-6">
                <h5>FISCALIZADORES</h5>
                <div class="input-group number-spinner">
                  <span class="input-group-btn">
                    <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="dwn">-</button>
                  </span>
                  <input id="cantidad_fiscalizadores" type="text" class="form-control text-center" value="1">
                  <span class="input-group-btn">
                    <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="up">+</button>
                  </span>
                </div>
              </div>
            </div>
            @if($usuario->es_superusuario)
            <div class="row">
              <div class="col-md-6">
                <h5>SEMILLA</h5>
                <input id="seed" type="number" class="form-control">
              </div>
            </div>
            @endif
            <br>
            <br>
            <div id="maquinas_pedido" class="row">
              <div class="col-md-12">
                <h5>MÁQUINAS A PEDIDO</h5>
                <span style="font-family:Roboto-Regular;font-size:16px;">El sector elegido tiene N máquinas a pedido</span>
              </div>
            </div>
          </form>
          <div id="iconoCarga" class="sk-folding-cube">
            <div class="sk-cube1 sk-cube"></div>
            <div class="sk-cube2 sk-cube"></div>
            <div class="sk-cube4 sk-cube"></div>
            <div class="sk-cube3 sk-cube"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-generar" value="nuevo">GENERAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          <input type="hidden" id="existeRelevamiento" name="id_casino" value="0">
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="confirmacionGenerarRelevamiento" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modalNuevo">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <h3 class="modal-title"> NUEVO RELEVAMIENTO</h3>
      </div>
      <div class="modal-body">
        <h5 style="padding:10px;font-family:Roboto-Condensed;color:#FF1744 !important;font-size:24px;">ATENCIÓN</h5>
        <h5 style="padding:0px;font-family:Roboto-Condensed;color:#444 !important;font-size:20px;">YA EXISTE RELEVAMIENTO PARA EL SECTOR SELECCIONADO</h5>
        <p style="font-family:Roboto-Regular;font-size:16px;margin:20px 0px;">
          Si vuelve a generar el relevamiento se sobreescribirán los datos anteriores y se perderán las planillas de relevamiento generadas anteriormente.
        </p>
        <p style="font-family:Roboto-Regular;font-size:16px;margin-bottom:20px;">
          ¿Desea generar el relevamiento de todas formas?
        </p>
      </div>
      <div class="modal-footer">
        <button id="btn-generarIgual" type="button" class="btn btn-successAceptar" value="nuevo">GENERAR DE TODAS FORMAS</button>
        <button id="btn-cancelarConfirmacion" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="imposibleGenerarRelevamiento" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modalNuevo">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <h3 class="modal-title"> NUEVO RELEVAMIENTO</h3>
      </div>
      <div class="modal-body" style="text-align:center;">
        <h5 style="padding:0px;font-family:Roboto-Condensed;color:#444 !important;font-size:20px;">NO SE PUEDE GENERAR EL RELEVAMIENTO</h5>
        <p style="font-family:Roboto-Regular;font-size:16px;margin:20px 0px;">
          El sector seleccionado ya se está relevando.
        </p>
      </div>
      <div class="modal-footer">
        <button id="btn-volver" type="button" class="btn btn-successAceptar" data-dismiss="modal">VOLVER</button>
        <button id="btn-cancelarImposible" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Relevamientos -->
<div class="modal fade" id="modalRelSinSistema" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarSinSistema" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoSinSistema" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| RELEVAMIENTO SIN SISTEMA</h3>
      </div>
      <div id="colapsadoSinSistema" class="collapse in">
        <div class="modal-body modalCuerpo">
          <form id="frmRelSinSistema" name="frmRelSinSistema" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-md-6">
                <h5>FECHA DE RELEVAMIENTO</h5>
                <div class='input-group date' id='fechaRelSinSistema' data-link-field="fechaRelSinSistema_date" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="Fecha de Inicio"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
                <input type="hidden" id="fechaRelSinSistema_date" value=""/>
                <br>
              </div>
              <div class="col-md-6">
                <h5>FECHA DE GENERACIÓN</h5>
                <div class='input-group date' id='fechaGeneracion' data-link-field="fechaGeneracion_date" data-date-format="dd MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="Fecha de Inicio"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
                <input type="hidden" id="fechaGeneracion_date" value=""/>
                <br>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <h5>CASINO</h5>
                <select id="casinoSinSistema" class="form-control" name="">
                  <option value="">- Seleccione un casino -</option>
                  @foreach ($usuario->casinos as $casino)
                  <option id="{{$casino->id_casino}}" value="{{$casino->codigo}}">{{$casino->nombre}}</option>
                  @endforeach
                </select>
                <br>
                <span id="alertaCasinoSinsistema" class="alertaSpan"></span>
              </div>
              <div class="col-md-6">
                <h5>SECTOR</h5>
                <select id="sectorSinSistema" class="form-control" name="">
                  <option value=""></option>
                </select>
                <br>
                <span id="alertaSectorSinSistema" class="alertaSpan"></span>
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

<!-- Modal cargar relevamientos -->
<div class="modal fade" id="modalCargaRelevamiento" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:90%;">
    <div class="modal-content">
      <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FF6E40;">
       <button id="btn-minimizarCargar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
       <h3 class="modal-title">CARGAR RELEVAMIENTO</h3>
      </div>
      <div id="colapsadoCargar" class="collapse in">
        <div class="modal-body modalCuerpo">
          <form id="frmCargaRelevamiento" name="frmCargaRelevamiento" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-lg-2 col-xl-offset-1">
                <h5>FECHA DE RELEVAMIENTO</h5>
                <input id="cargaFechaActual" type='text' class="form-control" readonly>
              </div>
              <div class="col-lg-2">
                <h5>FECHA DE GENERACIÓN</h5>
                <input id="cargaFechaGeneracion" type='text' class="form-control" readonly>
              </div>
              <div class="col-lg-2">
                <h5>CASINO</h5>
                <input id="cargaCasino" type='text' class="form-control" readonly>
                <span id="alertaCasino" class="alertaSpan"></span>
              </div>
              <div class="col-lg-2">
                <h5>SECTOR</h5>
                <input id="cargaSector" type='text' class="form-control" readonly>
                <span id="alertaSector" class="alertaSpan"></span>
              </div>
              <div class="col-lg-2">
                <h5>SUB RELEVAMIENTO</h5>
                <input id="cargaSubrelevamiento" type='text' class="form-control" readonly>
                <span id="alertaSubrelevamiento" class="alertaSpan"></span>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2 col-xl-offset-1">
                <h5>FISCALIZADOR CARGA</h5>
                <input id="fiscaCarga" type="text"class="form-control" readonly>
              </div>
              <div class="col-md-2">
                <h5>FISCALIZADOR TOMA</h5>
                <input id="inputFisca" class="form-control" type="text" autocomplete="off">
              </div>
              <div class="col-md-2">
                <h5>TÉCNICO</h5>
                <input id="tecnico" type="text"class="form-control">
              </div>
              <div class="col-md-3">
                <h5>HORA EJECUCIÓN</h5>
                 <div class='input-group date' id='dtpFecha' data-link-field="fecha_ejecucion" data-date-format="HH:ii" data-link-format="HH:ii">
                   <input type='text' class="form-control" placeholder="Fecha de ejecución del relevamiento" id="fecha"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" autocomplete="off" />
                   <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                   <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                 </div>
                 <input type="hidden" id="fecha_ejecucion" value=""/>
              </div>
            </div>
            <br>
            <br>
            <div class="row">
              <div class="col-md-12">
                <table id="tablaCargaRelevamiento" class="table">
                  <thead>
                    <tr>
                      <th width="3%">MTM</th>
                      <th>CONTADOR 1</th>
                      <th>CONTADOR 2</th>
                      <th>CONTADOR 3</th>
                      <th>CONTADOR 4</th>
                      <th>CONTADOR 5</th>
                      <th>CONTADOR 6</th>
                      <th width="2%">DIF</th>
                      <th width="12%">CAUSA DE NO TOMA</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-8 col-md-offset-2">
                <h5>OBSERVACIONES</h5>
                <textarea id="observacion_carga" class="form-control" style="resize:vertical;"></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warningModificar" id="btn-finalizar" value="nuevo" style="position:absolute;left:20px;">FINALIZAR RELEVAMIENTO</button>
          <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">GUARDAR TEMPORALMENTE</button>
          <button type="button" class="btn btn-default" id="btn-salir">SALIR</button>
          <div class="mensajeSalida">
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">CAMBIOS SIN GUARDAR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Presione SALIR nuevamente para salir sin guardar cambios.</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Presione GUARDAR TEMPORALMENTE para guardar los cambios.</span>
          </div>
          <input type="hidden" id="id_relevamiento" value="0">
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal validar relevamientos -->
<div class="modal fade" id="modalValidarRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:94%;">
    <div class="modal-content">
      <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#69F0AE;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarValidar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoValidar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| DETALLES RELEVAMIENTO</h3>
      </div>
      <div id="colapsadoValidar" class="collapse in">
        <div class="modal-body modalCuerpo">
          <form id="frmValidarRelevamiento" name="frmValidarRelevamiento" class="form-horizontal" novalidate="">
            <div class="row">
              <div class="col-lg-2">
                <h5>FECHA</h5>
                <input id="validarFechaActual" type='text' class="form-control" readonly>
                <br>
              </div>
              <div class="col-lg-2">
                <h5>CASINO</h5>
                <input id="validarCasino" type='text' class="form-control" readonly>
                <br>
                <span id="alertaCasino" class="alertaSpan"></span>
              </div>
              <div class="col-lg-2">
                <h5>SECTOR</h5>
                <input id="validarSector" type='text' class="form-control" readonly>
                <br>
                <span id="alertaSector" class="alertaSpan"></span>
              </div>
              <div class="col-md-2">
                <h5>FISCALIZADOR CARGA</h5>
                <input id="validarFiscaCarga" type="text"class="form-control" readonly>
              </div>
              <div class="col-md-2">
                <h5>FISCALIZADOR TOMA</h5>
                <input id="validarFiscaToma" type="text"class="form-control" readonly>
              </div>
              <div class="col-md-2">
                <h5>TÉCNICO</h5>
                <input id="validarTecnico" type="text"class="form-control" readonly>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-12">
                <table id="tablaValidarRelevamiento" class="table">
                  <thead>
                    <tr>
                      <th width="3%">MÁQ</th>
                      <th>CONTADOR 1</th>
                      <th>CONTADOR 2</th>
                      <th>CONTADOR 3</th>
                      <th>CONTADOR 4</th>
                      <th>CONTADOR 5</th>
                      <th>CONTADOR 6</th>
                      <!-- <th>CONTADOR 7</th> -->
                      <!-- <th>CONTADOR 8</th> -->
                      <th>P. CALCULADO ($)</th>
                      <th>P. IMPORTADO ($)</th>
                      <th>DIFERENCIA</th>
                      <th></th>
                      <th>TIPO NO TOMA</th>
                      <th>DEN</th>
                      <th>-</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-8 col-md-offset-2">
                <h5>OBSERVACIONES FISCALIZADOR</h5>
                <textarea id="observacion_fisca_validacion" class="form-control" style="resize:vertical;" readonly="true"></textarea>
              </div>
            </div>
            <br>
            <div class="row">
              <div class="col-md-8 col-md-offset-2">
                <h5>OBSERVACIONES DE VISADO</h5>
                <textarea id="observacion_validacion" class="form-control" style="resize:vertical;"></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-finalizarValidacion" value="nuevo">VISAR RELEVAMIENTO</button>
          <button type="button" class="btn btn-default" id="btn-salirValidacion" data-dismiss="modal">SALIR</button>
          <input type="hidden" id="id_relevamiento" value="0">
          <div id="mensajeValidacion" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">NO SE PUEDE VISAR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">No se importaron los contadores para dicha fecha.</span>
          </div>
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
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
      </div>
      <div class="modal-body franjaRojaModal">
        <form id="frmEliminar" name="frmCasino" class="form-horizontal" novalidate="">
          <div class="form-group error ">
            <div class="col-xs-12">
              <strong>¿Seguro desea eliminar el CASINO? Podría ocasionar errores serios en el sistema.</strong>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="btn-eliminarModal" value="0">ELIMINAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
      </div>
    </div>
  </div>
</div>

<div hidden>

  <div align='left'>
    <input type='radio' name='medida' value='credito' checked >
    <i style='margin-left:5px;position:relative;top:-3px;' class='fa fa-fw fa-life-ring'></i>
    <span style='position:relative;top:-3px;'> Cŕedito</span><br>
    <input type='radio' name='medida' value='pesos'>
    <i style='margin-left:5px;position:relative;top:-3px;' class='fas fa-dollar-sign'></i>
    <span style='position:relative;top:-3px;'> Pesos</span> <br><br>
    <button id='1' class='btn btn-deAccion btn-successAccion ajustar' type='button' style='margin-right:8px;'>AJUSTAR</button>
    <button class='btn btn-deAccion btn-defaultAccion cancelarAjuste' type='button'>CANCELAR</button>
  </div>
</div>

<table id="moldesFilas" hidden>
  <tr class="moldeBusqueda">
    <td class="col-xs-2 fecha">99 MES 99999</td>
    <td class="col-xs-2 casino">CASINO</td>
    <td class="col-xs-2 sector">SECTOR</td>
    <td class="col-xs-1 subrelevamiento">SUB</td>
    <td class="col-xs-2 estado">
      <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faGenerado" data-id-estado-relevamiento="1" style="display: none;"></i>
      <span data-id-estado-relevamiento="1" hidden>Generado</span>
      <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faCargando" data-id-estado-relevamiento="2" style="display: none;"></i>
      <span data-id-estado-relevamiento="2" hidden>Cargando</span>
      <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faFinalizado" data-id-estado-relevamiento="3" style="display: none;"></i>
      <span data-id-estado-relevamiento="3" hidden>Finalizado</span>
      <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faVisado" data-id-estado-relevamiento="4" style="display: none;"></i>
      <span data-id-estado-relevamiento="4" hidden>Visado</span>
      <i class="iconoEstadoRelevamiento fas fa-fw fa-dot-circle faValidado" data-id-estado-relevamiento="7" style="display: none;"></i>
      <span data-id-estado-relevamiento="7" hidden>Rel. Visado</span>
    </td>
    <td class="col-xs-3 acciones">
      @if($usuario->tienePermiso('relevamiento_cargar'))
      <button class="btn btn-info planilla" type="button" title="VER PLANILLA" data-id-estado-relevamiento="1" style="display: none;">
        <i class="far fa-fw fa-file-alt"></i>
      </button>
      <button class="btn btn-warning carga" type="button" title="CARGAR RELEVAMIENTO" data-id-estado-relevamiento="1,2" style="display: none;">
        <i class="fa fa-fw fa-upload"></i>
      </button>
      @endif
      @if($usuario->tienePermiso('relevamiento_validar'))
      <button class="btn btn-success validar" type="button" title="VISAR RELEVAMIENTO" data-id-estado-relevamiento="3" style="display: none;">
        <i class="fa fa-fw fa-check"></i>
      </button>
      @endif
      @if($usuario->es_administrador || $usuario->es_superusuario)
      <button class="btn btn-success verDetalle" type="button" title="VER RELEVAMIENTO" data-id-estado-relevamiento="4,7" style="display: none;">
        <i class="fa fa-fw fa-search-plus"></i>
      </button>
      @endif
      <button class="btn btn-info imprimir" type="button" title="IMPRIMIR PLANILLA" data-id-estado-relevamiento="2,3,4,7" style="display: none;">
        <i class="fa fa-fw fa-print"></i>
      </button>
      <button class="btn btn-success validado" type="button" title="IMPRIMIR VISADO" data-id-estado-relevamiento="7" style="display: none;">
        <i class="fa fa-fw fa-bookmark"></i>
      </button>
    </td>
  </tr>
  <tr class="moldeCarga" data-medida="" data-denominacion="">
    <td class="maquina">2272</td>
    @for($c=1;$c<=8;$c++)
    <td {{$c<=6? '' : 'hidden'}}><input class="contador cont{{$c}} form-control"></td>
    <input class="formulaCont{{$c}}" hidden>
    <input class="formulaOper{{$c}}" hidden>
    @endfor
    <td hidden><input class="producidoCalculado form-control" style="text-align: right; border: 2px solid rgb(109, 199, 190); color: rgb(109, 199, 190);"></td>
    <td hidden><input class="producido form-control" style="text-align: right; border: 2px solid rgb(109, 199, 190); color: rgb(109, 199, 190);"></td>
    <td hidden><input class="diferencia form-control" style="text-align: right;"></td>
    <td style="text-align: center;" class="estado_diferencia">
      <i class="fa fa-times icono_estado icono_incorrecto" style="color: rgb(239, 83, 80);" hidden></i>
      <i class="fa fa-check icono_estado icono_correcto" style="color: rgb(102, 187, 106);" hidden></i>
      <i class="fa fa-ban icono_estado icono_no_toma" style="color: rgb(30, 144, 255);" hidden></i>
      <a class="pop icono_estado icono_truncado" data-content="Contadores importados truncados" data-placement="top" rel="popover" data-trigger="hover" hidden>
        <i class="pop fa fa-exclamation" style="color: rgb(255, 167, 38); display: inline-block;"></i>
      </a>
      <a class="pop icono_estado icono_no_importado" data-content="No se importaron contadores" data-placement="top" rel="popover" data-trigger="hover" hidden>
        <i class="pop fa fa-question" style="color: rgb(66, 165, 245); display: inline-block;"></i>
      </a>
    </td>
    <td>
      <select class="tipo_causa_no_toma form-control">
        <option value=""></option>
        <option value="1">NO FUNCIONA TOUCH</option>
        <option value="2">MÁQUINA OCUPADA</option>
        <option value="3">MÁQUINA APAGADA</option>
        <option value="4">FALTA LLAVE</option>
        <option value="5" disabled="disabled">DATOS ERRONEOS</option>
        <option value="6">OTRO</option>
        <option value="7">MÁQUINA FUERA DE SERVICIO</option>
        <option value="8">MTM NO COMUNICA</option>
        <option value="9">CERRADURA ROTA</option>
        <option value="10">BOTONERA ROTA</option>
        <option value="11">NO FUNCIONA LLAVE</option>
        <option value="12">NO FUNCIONA BOTONERA</option>
        <option value="13">ERROR DE FÓRMULA</option>
      </select>
    </td>
    <td hidden>
      <?php
      $popup = function($select){
        $checked1 = $select == 1? 'checked' : '';
        $checked2 = $select == 2? 'checked' : '';
        return '<div align="left">
          <input type="radio" name="medida" value="credito" '.$checked1.'>
          <i style="margin-left:5px;position:relative;top:-3px;" class="fa fa-fw fa-life-ring"></i>
          <span style="position:relative;top:-3px;"> Cŕedito</span><br>
          <input type="radio" name="medida" value="pesos" '.$checked2.'>
          <i style="margin-left:5px;position:relative;top:-3px;" class="fas fa-dollar-sign"></i>
          <span style="position:relative;top:-3px;"> Pesos</span> <br><br>
          <button id="1" class="btn btn-deAccion btn-successAccion ajustar" type="button" style="margin-right:8px;">AJUSTAR</button>
          <button class="btn btn-deAccion btn-defaultAccion cancelarAjuste" type="button">CANCELAR</button>
        </div>';
      };
      ?>
      <button data-medida="1" class="btn btn-warning pop medida" title="AJUSTE" data-trigger="manual" data-toggle="popover" data-placement="left" data-html="true" type="button" class="btn btn-warning pop medida"
       data-content="{{$popup(1)}}">
        <i class="fa fa-fw fa-life-ring"></i>
      </button>
      <button data-medida="2" class="btn btn-warning pop medida" title="AJUSTE" data-trigger="manual" data-toggle="popover" data-placement="left" data-html="true" type="button" class="btn btn-warning pop medida"
       data-content="{{$popup(2)}}">
        <i class="fas fa-dollar-sign"></i>
      </button>
    </td>
    <td hidden>
      <select class="a_pedido form-control acciones_validacion">
        <option value="0" selected>NO</option>
        <option value="1">1 día</option>
        <option value="5">5 días</option>
        <option value="10">10 días</option>
        <option value="15">15 días</option>
      </select>
    </td>
    <td hidden>
      <button class="btn btn-success estadisticas_no_toma acciones_validacion" type="button">
        <i class="fas fa-fw fa-external-link-square-alt"></i>
      </button>
    </td>
  </tr>
</table>

<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| RELEVAMIENTOS</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Relevamientos</h5>
  <p>
    Se observan los últimos relevamientos generados en el sistema, con sus respectivos estados que son detallados en la vista.
    Se podrán cargar, editar, imprimir estas planillas, dependiendo en el estado en que se encuentre el relevamiento deseado.
    Además, se podrán generar nuevos relevamientos, implementándose la opción de que el sistema esté fuera de servicio.
    Produce un archivo con formato .zip conteniendo en ellos relevamientos para 7 días, los cuales se cargarán cuando el sistema vuelva a estar en línea.
    Y también se podrán seleccionar máquinas por relevamiento, considerando el casino, la fecha de inicio/final, su tipo y la cantidad de máquinas que requiera.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JS paginacion -->
<script src="/js/paginacion.js" charset="utf-8"></script>

<!-- JavaScript personalizado -->
<!-- ?version para forzar que se recarge el script en el navegador del cliente -->
<script src="js/seccionRelevamientos.js?7" charset="utf-8"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Custom input Bootstrap -->
<script src="js/fileinput.min.js" type="text/javascript"></script>
<script src="js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<script src="js/inputSpinner.js" type="text/javascript"></script>
<script src="js/lista-datos.js" type="text/javascript"></script>
<script src="js/math.min.js" type="text/javascript"></script>
@endsection
