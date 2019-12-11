@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('contenidoVista')

<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
$user = UsuarioController::getInstancia()->quienSoy()['usuario'];
$puede_fiscalizar = $user->es_fiscalizador || $user->es_superusuario;
$puede_validar = $user->es_administrador || $user->es_superusuario;
$puede_eliminar = $user->es_administrador || $user->es_superusuario;
$puede_modificar_valores = $user->es_administrador || $user->es_superusuario;
$cant_turnos = sizeof($casinos[0]->turnos);
?>

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel="stylesheet" href="css/lista-datos.css">

<style>
  .fondoBlanco {
    background-color: rgb(255,255,255) !important;
  }
</style>
@endsection

@foreach ($casinos as $casino)
  <datalist id="datalist{{$casino->id_casino}}">
    @foreach($fiscalizadores[$casino->id_casino] as $u)
      <option data-id="{{$u['id_usuario']}}">{{$u['nombre']}}</option>
    @endforeach
  </datalist>
@endforeach

<div class="row">
  <div class="col-xl-3">
      <div class="row">
        <div class="col-xl-12 col-md-4">
              <a href="" id="btn-nuevo" style="text-decoration: none;">
                <div class="panel panel-default panelBotonNuevo">
                    <center>
                      <img class="imgNuevo" src="/img/logos/relevamientos_white.png">
                    </center>
                    <div class="backgroundNuevo"></div>
                    <div class="row">
                        <div class="col-xs-12">
                          <center>
                            <h5 class="txtLogo">+</h5>
                            <h4 class="txtNuevo">GENERAR RELEVAMIENTO DE CONTROL AMBIENTAL EN MESAS</h4>
                          </center>
                        </div>
                    </div>
                </div>
              </a>
        </div>

      </div>
    </div><!-- row botones -->

  <div class="col-xl-9">
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
                            <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                <option value="0">-Todos los Casinos-</option>
                                @foreach ($casinos as $casino)
                                  <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <h5>Estado Relevamiento</h5>
                            <select id="buscadorEstado" class="form-control selectSector" name="">
                                <option value="0">-Todos los estados-</option>
                                @foreach($estados as $estado)
                                  <option id="estado{{$estado->id_estado_relevamiento}}" value="{{$estado->id_estado_relevamiento}}">{{$estado->descripcion}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <h5>Búsqueda</h5>
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
              <h4>RELEVAMIENTOS DE CONTROL AMBIENTAL CREADOS POR EL SISTEMA</h4>
            </div>

            <div class="panel-body">
              <table id="tablaRelevamientos" class="table table-fixed tablesorter">
                <thead>
                  <tr>
                    <th class="col-xs-3 activa" value="relevamiento_ambiental.fecha_generacion" estado="desc">FECHA <i class="fa fa-sort-desc"></i></th>
                    <th class="col-xs-3" value="casino.nombre" estado="">CASINO  <i class="fa fa-sort"></i></th>
                    <th class="col-xs-3" value="estado_relevamiento.descripcion" estado="">ESTADO <i class="fa fa-sort"></i></th>
                    <th class="col-xs-3">ACCIÓN </th>
                  </tr>
                </thead>
                <tbody id="cuerpoTabla" style="height: 350px;">
                  <tr class='filaEjemplo' style="display: none;">
                    <td class="col-xs-3 fecha"></td>
                    <td class="col-xs-3 casino"></td>
                    <td class="col-xs-3">
                      <i class="fas fa-fw fa-dot-circle iconoEstado"></i>
                      <span class="textoEstado"></span>
                    </td>
                    <td class="col-xs-3 acciones">
                      <button class="btn btn-info planilla" type="button">
                        <i class="far  fa-fw fa-file-alt"></i></button>
                      <span></span>
                      @if($puede_fiscalizar)
                      <button class="btn btn-warning carga" type="button">
                        <i class="fa fa-fw fa-upload"></i></button>
                      <span></span>
                      @endif
                      @if($puede_validar)
                      <button class="btn btn-success validar" type="button">
                        <i class="fa fa-fw fa-check"></i></button>
                      <span></span>
                      @endif
                      @if($puede_eliminar)
                      <button class="btn btn-success eliminar" type="button">
                        <i class="fa fa-fw fa-trash"></i></button>
                      <span></span>
                      @endif
                      <button class="btn btn-info imprimir" type="button">
                        <i class="fa fa-fw fa-print"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
            </div>
        </div>
      </div>
    </div>  <!-- row tabla -->
  </div> <!-- row principal -->


  <!--MODAL CREAR RELEVAMIENTO -->
  <div class="modal fade" id="modalRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width:38%">
           <div class="modal-content">
             <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
               <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
               <button id="btn-minimizarCrear" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrear" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title" style="background-color: #6dc7be;">| NUEVO RELEVAMIENTO DE CONTROL AMBIENTAL DE MESAS</h3>
              </div>

              <div  id="colapsadoCrear" class="collapse in">

              <div class="modal-body modalCuerpo">
                    <div class="row">
                      <div class="col-xs-6">
                        <h5>FECHA DE RELEVAMIENTO</h5>
                        <div class='input-group date' id='fechaRelevamientoDiv' data-date-format="yyyy-mm-dd HH:ii:ss" data-link-format="yyyy-mm-dd HH:ii">
                            <input type='text' class="form-control" placeholder="Fecha de ejecución del control" id="fechaRelevamientoInput" autocomplete="off" style="background-color: rgb(255,255,255);" readonly/>
                            <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                            <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                        <br>
                      </div>

                      <div class="col-xs-6">
                        <h5>CASINO</h5>
                        <select id="casino" class="form-control" name="" style="float:right !important">
                            <option value="">- Seleccione un casino -</option>
                            <?php $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))?>
                             @foreach ($usuario['usuario']->casinos as $casino)
                             <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                             @endforeach
                        </select>
                        <br> <span id="alertaCasino" class="alertaSpan"></span>
                      </div>
                    </div>

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
                <input type="hidden" id="existeLayoutParcial" name="id_casino" value="0">
                <input type="hidden" id="id_casino" name="id_casino" value="0">
              </div>
            </div>
          </div>
        </div>
  </div>


  <!-- MODAL RELEVAMIENTOS SIN SISTEMA -->
  <div class="modal fade" id="modalRelSinSistema" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
           <div class="modal-content">
             <div class="modal-header modalNuevo">
               <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
               <button id="btn-minimizarSinSistema" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoSinSistema" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title">RELEVAMIENTO SIN SISTEMA</h3>
              </div>

              <div  id="colapsadoSinSistema" class="collapse in">

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
                                <?php $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario')) ?>
                                 @foreach ($usuario['usuario']->casinos as $casino)
                                 <option id="{{$casino->id_casino}}" value="{{$casino->codigo}}">{{$casino->nombre}}</option>
                                 @endforeach
                            </select>
                            <br> <span id="alertaCasinoSinsistema" class="alertaSpan"></span>
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

  <!-- MODAL CARGAR RELEVAMIENTO -->
  <div class="modal fade" id="modalRelevamientoAmbiental" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="width:45%;">
      <div class="modal-content">
         <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FF6E40;">
           <button id="btn-minimizarCargar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title">CARGAR CONTROL LAYOUT</h3>
          </div>

          <div  id="colapsadoCargar" class="collapse in">

          <div class="modal-body modalCuerpo">
                    <div class="row">
                      <div class="col-md-4">
                        <h5>FECHA DE GENERACIÓN</h5>
                        <input id="cargaFechaGeneracion" type='text' class="form-control" readonly>
                      </div>
                      <div class="col-md-4">
                        <h5>CASINO</h5>
                        <input id="cargaCasino" type='text' class="form-control" readonly>
                      </div>
                      <div class="col-md-4">
                          <h5>FISCALIZADOR CARGA</h5>
                          <input id="usuario_cargador" type="text"class="form-control" readonly>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-4">
                          <h5>TIPO DE CONTROL AMBIENTAL</h5>
                          <input id="tipo_control_ambiental" type="text"class="form-control" readonly>
                      </div>
                      <div class="col-md-4">
                          <h5>FISCALIZADOR TOMA</h5>
                          <input id="usuario_fiscalizador" class="form-control" type="text" autocomplete="off" list="">
                      </div>
                      <div class="col-md-4" >
                          <h5>FECHA EJECUCIÓN</h5>
                             <div class='input-group date' id='dtpFecha' data-link-field="fecha_ejecucion" data-date-format="yyyy-mm-dd HH:ii:ss" data-link-format="yyyy-mm-dd HH:ii">
                                 <input type='text' class="form-control fondoBlanco" placeholder="Fecha de ejecución del control" id="fecha" autocomplete="off" readonly/>
                                 <span class="input-group-addon usables" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                 <span class="input-group-addon usables" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                 <span class="input-group-addon nousables" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                 <span class="input-group-addon nousables" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                             </div>
                      </div>
                    </div>
                    <br><br>

                    <table class="table" id="tablaPersonas" style="margin-bottom: 0px; border-bottom: 0px">
                      <thead class="cabeceraTablaPersonas">

                      </thead>
                      <tbody></tbody>
                    </table>

                    <div class="" style="overflow: scroll; height: 500px;">
                    <table class="table tablaPozos">
                      <tbody class="cuerpoTablaPersonas">
                        <tr class="filaEjemplo" style="display: none">
                          <td class="col-xs-2 mesa" style="width:110px; height:52px: display: inline-block">X</td>

                        </tr>
                      </tbody>
                    </table>
                  </div>
                    <br>

                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                          <h5>OBSERVACIONES</h5>
                          <textarea id="observacion_carga" class="form-control" style="resize:vertical;"></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">
                          <h5>OBSERVACIONES AL VISAR</h5>
                          <textarea id="observacion_validacion" class="form-control" style="resize:vertical;"></textarea>
                        </div>
                    </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-warningModificar" id="btn-guardar">GUARDAR TEMPORALMENTE</button>
            <button type="button" class="btn btn-successAceptar" id="btn-finalizar" value="nuevo">FINALIZAR</button>
            <button type="button" class="btn btn-dangerElimina" id="btn-salir">SALIR</button>
          </div>
        </div>
      </div>
    </div>
  </div>


  <div class="modal" id="mensajeAlerta" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#D50000;">
                  <h2 class="modal-title"><b>ATENCIÓN</b></h2>
              </div>
              <div class="modal-body textoMensaje">
                  <h4><b>ESTA POR ELIMINAR UN RELEVAMIENTO</b></h4>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-danger confirmar">CONFIRMAR</button>
                <button type="button" class="btn btn-secondary cancelar" data-dismiss="modal">CANCELAR</button>
              </div>
          </div>
      </div>
  </div>


</div>


<meta name="_token" content="{!! csrf_token() !!}" />

@endsection


<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| RELEVAMIENTO DE CONTROL AMBIENTAL</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Relevamiento de control ambiental</h5>
  <p>
    Genera relevamientos de control ambiental dentro del casino, donde se deberán completar
    las planillas de informes, y luego, cargarlas según correspondan dichos datos.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->


@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/seccionRelevamientosAmbientalMesas.js" charset="utf-8"></script>
<script src="js/paginacion.js" charset="utf-8"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Custom input Bootstrap -->
<script src="js/fileinput.min.js" type="text/javascript"></script>
<script src="js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<script src="js/inputSpinner.js" type="text/javascript"></script>
<script src="js/lista-datos.js" type="text/javascript"></script>
@endsection
