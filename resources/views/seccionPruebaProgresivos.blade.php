@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;
setlocale(LC_TIME, 'es_ES.UTF-8');
$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
?>

@section('estilos')
<!-- <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/> -->
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/lista-datos.css">
<link rel="stylesheet" href="css/paginacion.css">
@endsection

        <div class="row">
          <div class="col-lg-12 col-xl-9">

            <div class="row">
              <div class="col-md-12">
                <div class="panel panel-default">
                  <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                    <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                  </div>
                  <div id="collapseFiltros" class="panel-collapse collapse">
                    <div class="panel-body">
                      <div class="row">
                        <div class="col-lg-3">
                          <h5>FECHA</h5>
                          <div class="form-group">
                             <div class='input-group date' id='fecha' data-link-field="fecha_inicio" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                 <input type='text' class="form-control" placeholder="Fecha" id="B_fecha_inicio"/>
                                 <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                 <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                             </div>
                             <input class="form-control" type="hidden" id="fecha_inicio" value=""/>
                          </div>
                        </div> <!-- Primera fila -->
                        <div class="col-lg-3">
                          <h5>NOMBRE PROGRESIVO</h5>
                          <input id="n_progresivo" type="text" placeholder="Nombre de Progresivo" class="form-control" maxlength="100">
                        </div>
                        <div class="col-lg-3">
                          <h5>CASINO</h5>
                          <select class="form-control" id="selectCasinos">
                            <option value="-1" selected>- Seleccione casino -</option>
                            @foreach ($usuario['usuario']->casinos as $casino)
                            <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-lg-3">
                          <h5>TIPO</h5>
                          <select class="form-control" id="selectTipo">
                            <option value="-1" selected>- Seleccione tipo -</option>
                            <option value="1">LINKEADO</option>
                            <option value="0">INDIVIDUAL</option>
                          </select>
                        </div>
                      </div> <!-- / Primera fila -->
                      <div class="row"> <!-- Segunda fila -->
                        <div class="col-lg-6">
                            <h5 style="color:#f5f5f5">Búsqueda</h5>
                            <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                        </div>
                      </div> <!-- / Segunda fila -->
                      <br>
                    </div>
                  </div>
                </div>
            </div>
          </div> <!-- / Tarjeta FILTROS -->

              <div class="row">
                <div class="col-md-12">
                  <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 id="tituloTabla">ÚLTIMOS PEDIDOS DE PRUEBA DE PROGRESIVOS</h4><br>
                    </div>
                    <div class="panel-body">
                      <table id="tablaPrueba" class="table table-fixed tablesorter">
                        <thead>
                          <tr>
                            <th class="col-xs-2">FECHA</th>
                            <th class="col-xs-4">PROGRESIVO</th>
                            <th class="col-xs-2">TIPO</th>
                            <th class="col-xs-2">CASINO</th>
                            <th class="col-xs-2">ACCIÓN</th>
                          </tr>
                        </thead>
                        <tbody id="cuerpoTabla" style="height: 270px;">

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
                  <div class="col-xl-12 col-md-4">
                   <a href="" id="btn-nuevaPrueba" style="text-decoration: none;">
                    <div class="panel panel-default panelBotonNuevo">
                        <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                        <div class="backgroundNuevo"></div>
                        <div class="row">
                            <div class="col-xs-12">
                              <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">CREAR PRUEBA DE PROGRESIVOS</h4>
                              </center>
                            </div>
                        </div>
                    </div>
                   </a>
                  </div>

              </div>
            </div>
         <!-- /#row -->
      </div>

    <!-- Modal Relevamientos -->
    <div class="modal fade" id="modalPrueba" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| NUEVA PRUEBA DE PROGRESIVOS</h3>
                </div>

                <div  id="colapsado" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmPrueba" name="frmPrueba" class="form-horizontal" novalidate="">

                          <div class="row">
                            <div class="col-md-12">
                              <h5>FECHA</h5>
                              <!-- <input id="fechaActual" class="form-control" type="text" value=""> -->
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
                                  @foreach ($usuario['usuario']->casinos as $casino)
                                  <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                  @endforeach
                              </select>
                              <br> <span id="alertaCasino" class="alertaSpan"></span>
                            </div>
                            <div class="col-md-6">
                              <h5>SECTOR</h5>
                              <select id="sector" class="form-control" name="">
                                <option value=""></option>
                              </select>
                              <br> <span id="alertaSector" class="alertaSpan"></span>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-6">
                              <h5>MÁQUINAS</h5>
                              <input id="cantidad_maquinas" type="text" class="form-control text-center" name="" value="1" disabled>
                            </div>
                            <div class="col-md-6">
                              <h5>FISCALIZADOR</h5>
                        			<div class="input-group number-spinner">
                        				<input id="cantidad_fiscalizadores" type="text" class="form-control text-center" value="1" disabled>
                        			</div>
                        		</div>
                          </div>
                          <br><br>
                  </form>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-generar" value="nuevo">GENERAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <input type="hidden" id="existePrueba" name="id_casino" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| PRUEBA DE PROGRESIVOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Prueba de Progresivos</h5>
      <p>
        Crea una prueba de progresivos, donde la planilla generada se utiliza para la toma de datos de progresivos en el parque de máquinas.
        
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionPruebaProgresivos.js?1" charset="utf-8"></script>
    <script src="js/paginacion.js" charset="utf-8"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    <script src="js/lista-datos.js" type="text/javascript"></script>
    @endsection
