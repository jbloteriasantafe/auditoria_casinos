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
$niveles = 6;
?>

@section('estilos')
<!-- <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/> -->
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel="stylesheet" href="css/lista-datos.css">
@endsection

@foreach ($casinos as $casino)
<datalist id="datalist{{$casino->id_casino}}">
  @foreach($fiscalizadores[$casino->id_casino] as $u)
  <option data-id="{{$u['id_usuario']}}">{{$u['nombre']}}</option>
  @endforeach
</datalist>
@endforeach

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
                            <h5>Sector</h5>
                            <select id="buscadorSector" class="form-control selectSector" name="">
                                <option value="0">-Todos los sectores-</option>
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
                    </div>
                    <div class="row">
                      <center>
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
          <h4>RELEVAMIENTO DE PROGRESIVOS GENERADO POR EL SISTEMA</h4>
        </div>

        <div class="panel-body">
          <table id="tablaRelevamientos" class="table table-fixed tablesorter">
            <thead>
              <tr>
                <th class="col-xs-2 activa" value="relevamiento_progresivo.fecha_generacion" estado="desc">FECHA <i class="fa fa-sort-desc"></i></th>
                <th class="col-xs-2" value="casino.nombre" estado="">CASINO  <i class="fa fa-sort"></i></th>
                <th class="col-xs-2" value="sector.descripcion" estado="">SECTOR <i class="fa fa-sort"></i></th>
                <th class="col-xs-1" value="relevamiento_progresivo.sub_control" estado="">SUB <i class="fa fa-sort"></i></th>
                <th class="col-xs-2" value="estado_relevamiento.descripcion" estado="">ESTADO <i class="fa fa-sort"></i></th>
                <th class="col-xs-3">ACCIÓN </th>
              </tr>
            </thead>
            <tbody id="cuerpoTabla" style="height: 250px;">
              <tr class='filaEjemplo' style="display: none;">
                <td class="col-xs-2 fecha">
                  01 Ene 9999
                </td>
                <td class="col-xs-2 casino">
                  EJEMPLO
                </td>
                <td class="col-xs-2 sector">
                  SECTOR999
                </td>
                <td class="col-xs-1 subcontrol">
                  99
                </td>
                <td class="col-xs-2">
                  <i class="fas fa-fw fa-dot-circle iconoEstado"></i>
                  <span class="textoEstado">EJEMPLO</span>
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
    </div>
  </div>

<!-- /.col-lg-12 col-xl-9 -->
  <div class="col-lg-12 col-xl-3">
  <div class="row">
    <div class="col-md-12">
     <a href="" id="btn-nuevo" style="text-decoration: none;">
      <div class="panel panel-default panelBotonNuevo">
          <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
          <div class="backgroundNuevo"></div>
          <div class="row">
              <div class="col-xs-12">
                <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">GENERAR RELEVAMIENTOS DE PROGRESIVOS</h4>
                </center>
              </div>
          </div>
      </div>
     </a>
    </div>
  </div>

  </div>

</div>  <!-- /#row -->

<!--MODAL CREAR RELEVAMIENTO -->
<div class="modal fade" id="modalRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarCrear" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrear" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #6dc7be;">| NUEVO RELEVAMIENTO PROGRESIVOS</h3>
            </div>

            <div  id="colapsadoCrear" class="collapse in">

            <div class="modal-body modalCuerpo">
                      <div class='input-group date' id='fechaRelevamientoDiv' data-date-format="yyyy-mm-dd HH:ii:ss" data-link-format="yyyy-mm-dd HH:ii">
                          <input type='text' class="form-control" placeholder="Fecha de ejecución del control" id="fechaRelevamientoInput" autocomplete="off" style="background-color: rgb(255,255,255);" readonly/>
                          <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                          <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <h5>CASINO</h5>
                          <select id="casino" class="form-control" name="">
                              <option value="">- Seleccione un casino -</option>
                              <?php $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario')) ?>
                               @foreach ($usuario['usuario']->casinos as $casino)
                               <option id="{{$casino->id_casino}}" value="{{$casino->codigo}}">{{$casino->nombre}}</option>
                               @endforeach
                          </select>
                          <br> <span id="alertaCasino" class="alertaSpan"></span>
                        </div>
                        <div class="col-md-6">
                          <h5>SECTOR</h5>
                          <select id="sector" class="form-control selectSector" name="">
                              <option value=""></option>
                          </select>
                          <br> <span id="alertaSector" class="alertaSpan"></span>
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

<div class="modal" id="modalConfirmacion" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content">
              <div class="modal-header modalNuevo">
                  <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
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

<!-- Modal cargar layout -->
<div class="modal fade" id="modalRelevamientoProgresivos" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:95%;">
         <div class="modal-content">
           <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FF6E40;">
             <button id="btn-minimizarCargar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title">CARGAR CONTROL LAYOUT</h3>
            </div>

            <div  id="colapsadoCargar" class="collapse in">

            <div class="modal-body modalCuerpo">
                      <div class="row">
                        <div class="col-lg-3">
                          <h5>FECHA DE GENERACIÓN</h5>
                          <input id="cargaFechaGeneracion" type='text' class="form-control" readonly>
                        </div>
                        <div class="col-lg-3">
                          <h5>CASINO</h5>
                          <input id="cargaCasino" type='text' class="form-control" readonly>
                        </div>
                        <div class="col-lg-3">
                          <h5>SECTOR</h5>
                          <input id="cargaSector" type='text' class="form-control" readonly>
                        </div>
                        <div class="col-lg-3">
                          <h5>SUB RELEVAMIENTO</h5>
                          <input id="cargaSubrelevamiento" type='text' class="form-control" readonly>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-4">
                            <h5>FISCALIZADOR CARGA</h5>
                            <input id="usuario_cargador" type="text"class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <h5>FISCALIZADOR TOMA</h5>
                            <input id="usuario_fiscalizador" class="form-control" type="text" autocomplete="off" list="">
                        </div>
                        <div class="col-md-4">
                            <h5>FECHA EJECUCIÓN</h5>
                               <div class='input-group date' id='dtpFecha' data-link-field="fecha_ejecucion" data-date-format="yyyy-mm-dd HH:ii:ss" data-link-format="yyyy-mm-dd HH:ii">
                                   <input type='text' class="form-control" placeholder="Fecha de ejecución del control" id="fecha" autocomplete="off" style="background-color: rgb(255,255,255);" readonly/>
                                   <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                   <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                               </div>
                        </div>
                      </div>

                      <br><br>

                      <table class="table table-fixed" style="margin-bottom: 0px;">
                        <thead class="cabeceraTablaPozos">
                          <th class="col-xs-2 sortable" data-id="nombreProgresivo">Progresivo</th>
                          <th class="col-xs-2 sortable" data-id="nombrePozo">Pozo</th>
                          <th class="col-xs-1 sortable" data-id="isla">Isla</th>
                          @for ($i=1;$i<=$niveles;$i++)
                          <th class="col-xs-1" data-id="nivel{{$i}}">Nivel {{$i}}</th>
                          @endfor
                          <th class="col-xs-1" data-id="causaNoToma">Causa no toma</th>
                        </thead>
                        <tbody></tbody>
                      </table>
                      <div class="" style="overflow: scroll;height: 500px;">
                      <table class="table table-fixed tablaPozos">
                        <tbody class="cuerpoTablaPozos">
                          <tr class="filaEjemplo" style="display: none">
                            <td class="col-xs-2 nombreProgresivo">PROGRESIVO99</td>
                            <td class="col-xs-2 nombrePozo">POZO99</td>
                            <td class="col-xs-1 isla">ISLA1/ISLA2/...</td>
                            @for ($i=1;$i<=$niveles;$i++)
                            <td class="col-xs-1">
                              <input class="nivel{{$i}} form-control" type="number" min="0" data-toggle="tooltip" data-placement="down" title="nivel{{$i}}"></input>
                            </td>
                            @endfor
                            <td class="col-xs-1">
                              <select class="causaNoToma form-control">
                                <option value="-1"></option>
                                @foreach($causasNoToma as $causa)
                                <option value="{{$causa->id_tipo_causa_no_toma_progresivo}}">{{$causa->descripcion}}</option>
                                @endforeach
                              </select>
                            </td>
                          </tr>
                          <tr class="filaEjemplo validacion" style="display: none">
                            <td class="col-xs-2 nombreProgresivo">PROGRESIVO99</td>
                            <td class="col-xs-2 nombrePozo">POZO99</td>
                            <td class="col-xs-1 isla">ISLA1/ISLA2/...</td>
                            @for ($i=1;$i<=6;$i++)
                            <td class="col-xs-1">
                              <input disabled="disabled" class="nivel{{$i}} form-control" type="number" min="0" data-toggle="tooltip" data-placement="down" title="nivel{{$i}}"></input>
                            </td>
                            @endfor
                            <td class="col-xs-1">
                              <select disabled="disabled" class="causaNoToma form-control">
                                <option value="-1"></option>
                                @foreach($causasNoToma as $causa)
                                <option value="{{$causa->id_tipo_causa_no_toma_progresivo}}">{{$causa->descripcion}}</option>
                                @endforeach
                              </select>
                            </td>
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
              <button type="button" class="btn btn-successAceptar" id="btn-finalizar" value="nuevo">FINALIZAR RELEVAMIENTO</button>
              <button type="button" class="btn btn-dangerElimina" id="btn-salir">SALIR</button>
            </div>
          </div>
        </div>
      </div>
</div>

<meta name="_token" content="{!! csrf_token() !!}" />

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| RELEVAMIENTO DE PROGRESIVOS</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Relevamiento de progresivos</h5>
  <p>
    Genera relevamientos aleatorios de progresivos dentro del casino, donde se deberán completar
    las planillas de informes, y luego, cargarlas según correspondan dichos datos.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->


@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/seccionRelevamientosProgresivos.js" charset="utf-8"></script>
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
