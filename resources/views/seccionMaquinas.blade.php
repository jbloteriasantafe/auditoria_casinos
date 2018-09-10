@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario')) ?>

@section('estilos')
  <link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
  <link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
  <link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
  <link rel="stylesheet" href="/css/paginacion.css">
  <link rel="stylesheet" href="/css/lista-datos.css">
  <link rel="stylesheet" href="/css/styleSlider.css">
@endsection

@section('contenidoVista')
              <div class="row">
                <div class="col-lg-12 col-xl-12">

                  <div class="row"> <!-- Tarjeta de FILTROS -->
                    <div class="col-md-12">

                        <div id="contenedorFiltros" class="panel panel-default">
                          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                            <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                          </div>
                          <div id="collapseFiltros" class="panel-collapse collapse">
                            <div class="panel-body">
                              <div class="row"> <!-- Primera fila -->
                                <!-- 5 / 7 / 1 -->
                                <div class="col-lg-3">
                                  <h5>Nro. de Máquina</h5>
                                  <input id="busqueda_maquina" type="text" class="form-control" placeholder="Nro. de máquina">
                                </div>
                                <div class="col-lg-3">
                                  <h5>Marca</h5>
                                  <input id="busqueda_marca" type="text" class="form-control" placeholder="Marca">
                                </div>
                                <div class="col-lg-3">
                                  <h5>Juego </h5>
                                  <input id="busqueda_juego" type="text" class="form-control" placeholder="Nombre de Juego">
                                </div>
                                <div class="col-lg-3">
                                  <h5>Denominacion</h5>
                                  <input id="busqueda_denominacion" type="text" class="form-control" placeholder="Denominacion">
                                </div>
                              </div> <!-- / Primera fila -->

                              <br>

                              <div class="row"> <!-- Segunda fila -->
                                <div class="col-lg-3">
                                  <h5>Casino</h5>
                                  <select class="form-control" id="busqueda_casino">
                                    <option value="0">Todos los casinos</option>
                                    <!-- prueba de que solo vea los casinos a los que esta asigando -->
                                    @foreach ($usuario['usuario']->casinos as $casino)
                                    <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                    @endforeach
                                  </select>
                                </div>
                                <div class="col-lg-3">
                                  <h5>Sector</h5>
                                  <select class="form-control" id="busqueda_sector">
                                    <option value="0">Todos los sectores</option>

                                  </select>
                                </div>
                                <div class="col-lg-3">
                                  <h5>Nro Isla</h5>
                                  <input id="busqueda_isla" type="text" class="form-control" placeholder="Nro. isla">
                                </div>
                                <div class="col-lg-3">
                                  <h5 style="color:#f5f5f5">Búsqueda</h5>
                                  <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR </button>
                                </div>
                              </div> <!-- / Segunda fila -->

                              <br>
                            </div>
                          </div>

                        </div>

                    </div>
                  </div> <!-- / Tarjeta FILTROS -->

                  <div class="row"> <!-- Tarjeta TABLA Maquina -->
                    <div class="col-md-12">
                      <div class="panel panel-default">
                        <div class="panel-heading">
                          <h4>TODAS LAS MÁQUINAS</h4>
                        </div>
                        <div class="panel-body">
                          <table id="tablaMaquinas" class="table table-fixed tablesorter">
                            <thead>
                              <tr>
                                <th class="col-xs-1 activa" value="maquina.nro_admin" estado="asc">NRO <i class="fas fa-sort-up"></i></th>
                                <th class="col-xs-1" value="isla.nro_isla" estado="" >ISLA <i class="fas fa-sort"></i></th>
                                <th class="col-xs-3" value="sector.descripcion" estado="" >CASINO - SECTOR <i class="fas fa-sort"></i></th>
                                <th class="col-xs-2" value="maquina.marca" estado="">MARCA  <i class="fas fa-sort"></i></th>
                                <th class="col-xs-2" value="juego.nombre_juego" estado="">JUEGO  <i class="fas fa-sort"></i></th>
                                <th class="col-xs-1" value="denominacion" estado="">DEN.  <i class="fas fa-sort"></i></th>
                                <th class="col-xs-2">ACCIONES</th>
                              </tr>
                            </thead>
                            <tbody id="cuerpoTabla" style="height: 250px;">
                            </tbody>
                          </table>
                          <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div> <!-- / Tarjeta TABLA -->
                </div>

              </div>
              <!-- /.row -->

      <!-- Modal Maquina -->
    <div class="modal fade" id="modalMaquina" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header" style="padding-bottom: 0px !important;">

                  <!-- <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button> -->
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>

                  <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                  <h3 class="modal-title" style="color: #fff; text-align:center">NUEVA MÁQUINA TRAGAMONEDAS</h3>

                  <style media="screen">
                      .navModal > div {
                          display: inline-block;
                          margin: 0px 15px 0px 15px;
                      }

                      .navModal > div a{
                          outline: none;
                          text-decoration: none;
                          margin-bottom: 0px !important;
                      }

                      .navModal h4 {
                          font-family: Roboto-BoldCondensed;
                          padding-bottom: 20px;
                          margin-bottom: 0px !important;
                      }

                      .navModal.nuevo h4 {
                        color: #009688;;
                      }

                      .navModal.nav_modificar h4 {
                        color: #E65100;
                      }

                      .navModal.detalle h4 {
                        color: #448AFF;
                      }

                      .navModal a.navModalActivo h4 {
                          color: white;
                          font-size: 20px;
                          border-bottom: 5px solid #fff;
                      }
                  </style>

                  <div class="navModal" style="position:relative; bottom:-15px; text-align:center; font-family: Roboto-Regular; font-size: 20px; color: #999;">

                        <div width="10%">
                              <i id="error_nav_maquina" class="fa fa-times" style="color:red;"></i>
                              <a href="" id="navMaquina"><h4>MÁQUINA</h4></a>
                        </div>
                        <div width="10%">
                              <i id="error_nav_isla" class="fa fa-times" style="color:red;"></i>
                              <a href="" id="navIsla" hidden><h4>ISLA</h4></a>
                        </div>
                        <div width="10%">
                              <i id="error_nav_juego" class="fa fa-times" style="color:#F44336;"></i>
                              <a href="" id="navJuego"><h4>JUEGOS</h4></a>
                        </div>
                        <div width="10%">
                              <i id="error_nav_progresivo" class="fa fa-times" style="color:#F44336;"></i>
                              <a href="" id="navProgresivo"><h4>PROGRESIVO</h4></a>
                        </div>
                        <div width="10%">
                              <i id="error_nav_soft" class="fa fa-times" style="color:#F44336;"></i>
                              <a href="" id="navSoft"><h4>GLI SOFT</h4></a>
                        </div>
                        <div width="10%">
                              <i id="error_nav_hard" class="fa fa-times" style="color:red;"></i>
                              <a href="" id="navHard"><h4>GLI HARD</h4></a>
                        </div>
                        <div width="10%">
                              <i id="error_nav_formula" class="fa fa-times" style="color:red;"></i>
                              <a href="" id="navFormula"><h4>FÓRMULA</h4></a>
                        </div>

                  </div>

                </div>

                <div class="modal-body">

                  <!-- Panel que se minimiza -->
                  <div  id="colapsado" class="collapse in">

                      <!-- PASO 1 | MÁQUINA -->
                      <div class="seccion" id="secMaquina">
                        <!-- <form id="frmMaquina" name="frmMaquina" class="form-horizontal" novalidate=""> -->
                          <div class="row">
                              <div class="col-md-12">
                                <h6>DETALLES DE LA MÁQUINA</h6>
                              </div>
                          </div>
                          <br>

                          <div class="row">
                            <div class="col-lg-4">
                              <h5>Nro Administración</h5>
                              <input id="nro_admin" type="text" class="form-control" placeholder="Nro. administración">
                              <br>
                              <span id="alerta_nro_admin" class="alertaSpan"></span>
                            </div>
                            <div class="col-lg-4">
                              <h5>Marca</h5>
                              <input id="marca" type="text" class="form-control" placeholder="Marca" autocomplete="off">
                              <br>
                              <span id="alerta_marca" class="alertaSpan"></span>
                            </div>
                            <div class="col-lg-4">
                              <h5>Modelo</h5>
                              <input id="modelo" type="text" class="form-control" placeholder="Modelo">
                              <br>
                              <span id="alerta_modelo" class="alertaSpan"></span>
                            </div>

                          </div>

                          <div class="row">

                            <div class="col-lg-4">
                              <h5>Unidad de Medida</h5>
                              <select class="form-control" id="unidad_medida">
                                @foreach ($unidades_medida as $unidad)
                                <option value="{{$unidad->id_unidad_medida}}">{{$unidad->descripcion}}</option>
                                @endforeach
                              </select>                              <br>
                              <span id="alerta_unidad_medida" class="alertaSpan"></span>
                            </div>
                            <div class="col-lg-4">
                              <h5>Número de Serie</h5>
                              <input id="nro_serie" type="text" class="form-control" placeholder="Nro. de serie">
                              <br>
                              <span id="alerta_nro_serie" class="alertaSpan"></span>
                            </div>
                            <div class="col-lg-4">
                              <h5>MAC</h5>
                              <input id="mac" type="text" class="form-control" placeholder="MAC">
                              <br>
                              <span id="alerta_mac" class="alertaSpan"></span>
                            </div>
                          </div>

                          <div class="row">

                            <div class="col-lg-4">
                              <h5>Marca - Juego <i class="fa fa-question-circle" data-toggle="popover" data-trigger="hover" data-content="Si deja este campo en blanco la abreviación será generada automáticamente"></i></h5>
                              <input id="marca_juego" type="text" class="form-control" placeholder="Descripción marca">
                              <br>
                              <span id="alerta_desc_marca" class="alertaSpan"></span>
                            </div>
                            <div class="col-lg-4">
                              <h5>Gabinete</h5>
                              <select class="form-control" id="tipo_gabinete">
                                <option value="0">-Tipo de Gabinete-</option>
                                @foreach ($gabinetes as $gabinete)
                                <option value="{{$gabinete->id_tipo_gabinete}}">{{$gabinete->descripcion}}</option>
                                @endforeach
                              </select>
                              <br>
                            </div>
                            <div class="col-lg-4">
                              <h5>Tipo de Máquina</h5>
                              <select class="form-control" id="tipo_maquina">
                                <option value="0">-Tipo de Máquina-</option>
                                @foreach ($tipos as $tipo)
                                <option value="{{$tipo->id_tipo_maquina}}">{{$tipo->descripcion}}</option>
                                @endforeach
                              </select>
                              <br>
                              <span id="alerta_tipo" class="alertaSpan"></span>
                            </div>
                          </div>

                          <div class="row">
                              <div class="col-lg-4">
                                  <h5>Denominación Base</h5>
                                  <input id="denominacion" type="text" class="form-control" placeholder="Denominación">
                                  <br>
                              </div>
                              <div class="col-lg-4">
                                  <h5>% Devolución</h5>
                                  <input id="porcentaje_devolucion" type="text" class="form-control" placeholder="Porcentaje Devolución">
                                  <br>
                                  <span id="alerta_devolucion" class="alertaSpan"></span>
                              </div>
                              <div class="col-lg-4">
                                  <h5>Estado</h5>
                                  <select class="form-control" id="estado">
                                      <option value="0">-Estado Máquina-</option>
                                      @foreach ($estados as $estado)
                                      <option value="{{$estado->id_estado_maquina}}">{{$estado->descripcion}}</option>
                                      @endforeach
                                  </select><br>

                              </div>

                          </div>
                          <div class="row">
                            <div class="col-lg-4">
                              <h5>Moneda</h5>
                              <select class="form-control" id="tipo_moneda">
                                @foreach ($monedas as $moneda)
                                <option value="{{$moneda->id_tipo_moneda}}">{{$moneda->descripcion}}</option>
                                @endforeach
                              </select><br>
                            </div>

                          </div>

                          <div class="row">
                            <div class="col-lg-12">
                              <h5>Buscar Expedientes <i class="fa fa-search"></i></h5>
                              <div class="row">
                                 <div class="input-group lista-datos-group">
                                                  <input id="buscadorExpediente" class="form-control " type="text" value="" autocomplete="off" placeholder="- - - - -/ - - - - - - - / -">
                                                  <span class="input-group-btn">
                                                    <button class="btn btn-default btn-lista-datos agregarExpediente" type="button"><i class="fa fa-plus"></i></button>
                                                  </span>
                                </div>
                              </div>
                              <br>

                              <div class="row">
                                <div class="col-xs-12">
                                  <h5>Expedientes del GLI SOFT</h5>
                                  <ul id="listaExpedientes">
                                    <li class="row">
                                      <div class="col-xs-7">
                                          asd
                                      </div>
                                      <div class="col-xs-5">
                                          <button type="button" name="button">asd</button>
                                      </div>
                                    </li>
                                  </ul>
                                </div>
                              </div>
                            </div>
                                 <!-- fin de la columna  -->
                          </div>
                        <!-- </form> -->
                      </div> <!-- / PASO 1 | MÁQUINA -->

                      <!-- PASO 2 | ISLA-->
                      <div class="seccion" id="secIsla">

                        <div id="" data-agregado="false" style="padding: 5px 0px 30px 0px;">
                            <div class="row">
                                <div class="col-md-12">
                                  <h6>ISLA ACTIVA</h6>

                                  <table id="tablaIslaActiva" class="table" hidden style="margin-top:30px; margin-bottom:20px;">
                                    <thead>
                                      <tr>
                                          <th width="15%">ISLA</th>
                                          <th width="15%">SUBISLA</th>
                                          <th width="15%">MÁQUINAS</th>
                                          <th width="20%">CASINO</th>
                                          <th width="20%">SECTOR</th>
                                          <th width="15%">ACCIÓN</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr id="activa_datos" data-isla="" data-casino="" data-sector="">
                                          <td id="activa_nro_isla">
                                            <span class="badge" style="background-color: #6dc7be;font-family:Roboto-Regular;font-size:18px;margin-top:-3px;">123</span>
                                          </td>
                                          <td id="activa_sub_isla">2</td>
                                          <td id="activa_cantidad_maquinas">8</td>
                                          <td id="activa_casino">Santa Fe</td>
                                          <td id="activa_zona">Zona 1</td>
                                          <td>
                                            <button id="editarIslaActiva" class="btn btn-warning" type="button">
                                              <i class="fas fa-fw fa-pencil-alt"></i>
                                            </button>
                                            <button id="borrarIslaActiva" class="btn btn-danger" type="button">
                                              <i class="fas fa-fw fa-trash"></i>
                                            </button>
                                          </td>
                                      </tr>
                                    </tbody>
                                  </table>

                                  <p id="noexiste_isla" style="display:block;margin-top:30px; margin-bottom:20px;"><i class="fa fa-times aviso"></i> La máquina no tiene una isla asociada.</p>
                                </div>
                            </div>

                        </div>

                        <!-- CREAR O BUSCAR Isla-->
                        <div id="agregarIsla" style="cursor:pointer;" data-toggle="collapse" data-target="#islaPlegado">
                            <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                                <div class="col-md-12">
                                    <h6 id="tituloAgregar">AGREGAR ISLA<i class="fa fa-fw fa-angle-down"></i></h6>
                                </div>
                            </div>
                        </div>

                        <div id="islaPlegado" class="collapse">
                            <div class="row" style="padding-bottom: 15px;">
                              <br>
                                <div class="row">
                                    <div class="col-md-4">
                                        <h5>Casino</h5>
                                        <select class="form-control" id="selectCasino">
                                         <option value="0">- Seleccione el casino -</option>
                                         <?php $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario')) ?>
                                         @foreach ($usuario['usuario']->casinos as $casino)
                                         <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                         @endforeach
                                         </select>
                                         <br>
                                        <span id="alerta_casinos" class="alertaSpan"></span>
                                    </div>
                                    <div class="col-md-4">
                                      <h5>Sector</h5>
                                      <select class="form-control" id="sector" >
                                        <option value="0">- Sectores del Casino -</option>
                                      </select>
                                      <span id="alerta_sector" class="alertaSpan"></span>
                                    </div>

                                </div>

                                <br>

                                <div class="row">
                                    <div class="col-md-3">
                                      <h5>Número de isla</h5>
                                      <input id="nro_isla"  class="form-control" type="text"  placeholder="Número de isla" autocomplete="off">
                                      <!-- <input id="inputSoft" data-soft="" class="form-control" type="text" list="soft" autocomplete="off" placeholder="Código de certificado" />
                                      <datalist id="soft"> </datalist> -->

                                      <br>
                                      <span id="alerta_nro_isla" class="alertaSpan"></span>
                                    </div>

                                    <div class="col-md-3">
                                      <h5>Sub isla</h5>
                                      <input id="sub_isla" type="text" class="form-control" placeholder="Número de isla">
                                      <br>
                                      <span id="alerta_nro_isla" class="alertaSpan"></span>
                                    </div>

                                    <div class="col-md-6">
                                      <h5>Agregar Máquina</h5>
                                      <div class="row">
                                        <div class="col-xs-9">
                                          <input id="inputMaquina" data-maquina=""  class="form-control" type="text" autocomplete="off" placeholder="Buscar máquinas"/>
                                        </div>
                                        <div class="col-xs-3">
                                            <button id="cancelarMaquina" class="btn btn-danger borrarFila borrarInputIsla" type="button"><i class="fa fa-fw fa-times"></i></button>
                                            <button id="agregarMaquina" class="btn btn-success borrarFila agregarInputIsla" type="button"><i class="fa fa-fw fa-plus"></i></button>
                                        </div>
                                      </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div id="infoCambioSector" hidden class="col-md-12">
                                        <i class="fa fa-exclamation" style="margin-left:10px; margin-right:10px;color:#FF9100;margin-top:10px;"></i>
                                        <p style="color:#FF9100;margin-top:10px;">Está cambiando la ISLA a otro SECTOR</p>
                                    </div>
                                </div>

                                <br><br>

                                <div id="maquinasEnIsla" class="row">
                                    <div class="col-xs-12">
                                      <h6>MÁQUINAS EN LA ISLA</h6>
                                      <br>
                                        <table id="tablaMaquinasDeIsla" class="table">
                                            <thead>
                                                <tr>
                                                  <th width="15%"></th>
                                                  <th width="15%">NÚMERO</th>
                                                  <th width="25%">MARCA</th>
                                                  <th width="30%">MODELO</th>
                                                  <th width="15%">ACCIÓN</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="0" class="actual">
                                                  <td>
                                                    <i class="fa fa-star" style="color:#FB8C00;position:relative;left:-1px;"></i>
                                                    <span style="color:#aaa; margin-left:10px;">Actual</span>
                                                  </td>
                                                  <td>-</td>
                                                  <td>-</td>
                                                  <td>-</td>
                                                  <td></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- <div class="col-md-4 col-md-offset-1">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5>Casino</h5>
                                            <input id="inputSoft" data-soft="" class="form-control" type="text" list="soft" autocomplete="off" placeholder="Código de certificado" />
                                            <datalist id="soft"> </datalist>
                                            <span id="alerta_codigo_soft" class="alertaSpan"></span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5>Observaciones</h5>
                                            <textarea id="observaciones" class="form-control" rows="10" style="resize:none; height:80px;" placeholder="Observaciones"></textarea>
                                            <span id="alerta_observaciones" class="alertaSpan"></span>
                                        </div>
                                    </div>
                                </div> -->

                            </div>

                            <style media="screen">
                                  .modal-body .btn-success{
                                      border: none;
                                      font-family: Roboto-Condensed;
                                      font-size: 15px;
                                      font-weight: bold;
                                  }
                                  .modal-body .btn-danger{
                                      border: none;
                                      font-family: Roboto-Condensed;
                                      font-size: 15px;
                                      font-weight: bold;
                                  }


                            </style>

                            <div class="row">
                                <div class="col-md-12">
                                    <button id="btn-cancelarIsla" class="btn btn-danger" type="button" name="button" style="display: none;">
                                        <i class="fa fa-fw fa-times" style="position:relative; left:-1px; top:-1px;"></i> LIMPIAR CAMPOS
                                    </button>
                                    <button id="btn-crearIsla" class="btn btn-success" type="button" name="button" style="display: none;">
                                        <i class="fa fa-fw fa-plus" style="position:relative; left:-1px; top:-1px;"></i> CREAR ISLA
                                    </button>
                                    <button id="btn-agregarIsla" class="btn btn-success" type="button" name="button" style="display: none;">
                                        <i class="fa fa-fw fa-arrow-up" style="position:relative; left:-1px; top:-1px;"></i> AGREGAR ISLA
                                    </button>
                                </div>
                            </div>
                        </div>
                      </div> <!-- / PASO 2 | ISLA -->

                      <!-- PASO 2 | JUEGO -->
                      <div class="seccion" id="secJuego">

                        <div id="listaJuegosMaquina" data-agregado="false" style="padding: 5px 0px 30px 0px;">
                            <div class="row">
                                <div class="col-md-12">
                                  <h6>JUEGOS ACTIVOS</h6>

                                  <table id="tablaJuegosActivos" class="table" style="margin-top:30px; margin-bottom:20px;">
                                    <thead>
                                      <tr>
                                          <th width="10%">ACTIVO</th>
                                          <th width="25%">NOMBRE</th>
                                          <th width="20%">DENOMINACIÓN</th>
                                          <th width="15%">% DEV</th>
                                          <th width="20%">TABLAS DE PAGO</th>
                                          <th width="10%">ACCIÓN</th>
                                      </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                  </table>

                                  <p id="" style="display:block;margin-top:30px; margin-bottom:20px;"><i class="fa fa-times aviso"></i> La máquina no tiene juegos asociados.</p>
                                </div>
                            </div>

                        </div>


                          <!-- CREAR O BUSCAR JUEGO-->
                          <div id="agregarJuego" style="cursor:pointer;" data-toggle="collapse" data-target="#juegoPlegado">
                              <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                                  <div class="col-md-12">
                                      <h6>AGREGAR JUEGO<i class="fa fa-fw fa-angle-down"></i></h6>
                                  </div>
                              </div>
                          </div>

                          <div id="juegoPlegado" class="collapse">
                              <div class="row" style="padding-bottom: 15px;">
                                  <div class="col-md-4">
                                      <h5>Nombre Juego</h5>
                                        <!-- <input id="nro_isla"  class="form-control" type="text"  placeholder="Número de isla" autocomplete="off"> -->
                                      <input id="inputJuego" class="form-control" type="text" autocomplete="off" placeholder="Nombre juego" />
                                      <!-- <datalist id="juego"> </datalist> -->
                                  </div>
                                  <div class="col-md-3">
                                      <h5>Código de identificación</h5>
                                      <input id="inputCodigo" data-codigo="" class="form-control" type="text" autocomplete="off" placeholder="Código de identificación"/>
                                  </div>
                                  <div class="col-md-3">
                                      <h5>Den. de Sala</h5>
                                      <input id="den_sala" class="form-control" type="text" name="" value="" placeholder="ej: 0.1/0.5/1">
                                  </div>
                                  <div class="col-md-2">
                                      <h5>% Dev</h5>
                                      <input id="porcentaje_devolucion_juego" class="form-control" type="text" name="" value="" placeholder="ej: 95.21">
                                  </div>
                              </div>

                              <div class="row" style="padding-bottom: 15px;">
                                  <div id="tablas_de_pago" class="col-md-12">
                                      <h5 style="display:inline; margin-right:10px;">Tablas de pago</h5>
                                      <button style="display:inline;" id="btn-agregarTablaDePago" class="btn btn-success borrarFila" type="button">
                                        <i class="fa fa-fw fa-plus"></i>
                                      </button>
                                      <div id="tablas_pago" style="margin-top:15px;">
                                      </div>
                                  </div>
                              </div>

                              <div class="row">
                                  <div class="col-md-12">
                                      <button id="btn-cancelarJuego" class="btn btn-danger" type="button" name="button">
                                          <i class="fa fa-fw fa-times"></i> LIMPIAR CAMPOS
                                      </button>
                                      <button id="btn-crearJuego" class="btn btn-successAceptar" type="button" name="button">
                                          <i class="fa fa-fw fa-plus"></i> CREAR JUEGO
                                      </button>
                                      <button id="btn-agregarJuegoLista" class="btn btn-successAceptar" type="button" name="button">
                                          <i class="fa fa-fw fa-arrow-up"></i> AGREGAR JUEGO
                                      </button>
                                  </div>
                              </div>
                          </div>
                      </div> <!-- / PASO 2 | JUEGO -->

                      <!-- PASO 3 | PROGRESIVO -->
                      <div class="seccion" id="secProgresivo">
                          <div class="row">
                            <div class="col-lg-12">
                              <h6>PROGRESIVO</h6>
                              <div id="tablaProgresivoSeleccionado" class="row" style="margin-bottom: 15px;" hidden="true">
                                  <div class="col-xs-2 col-xs-offset-1">
                                      <h5>Progresivo</h5>
                                      <p id="progresivoSeleccionado"></p>
                                  </div>
                                  <div class="col-xs-3">
                                      <h5>Tipo de Progresivo</h5>
                                      <p id="tipoSeleccionado"></p>

                                  </div>
                                  <div class="col-xs-2">
                                      <h5>Máximo</h5>
                                      <p id="maximoSeleccionado"></p>

                                  </div>
                                  <div class="col-xs-2">
                                      <h5>% Recup.</h5>
                                      <p id="porc_recuperacionSeleccionado"></p>
                                  </div>
                                  <div class="col-xs-2">
                                      <h5>Acción </h5>
                                      <button id="editarProgresivoSeleccionado" class="btn btn-warning" type="button">
                                        <i class="fas fa-fw fa-pencil-alt"></i>
                                      </button>
                                      <button id="borrarProgresivoSeleccionado" class="btn btn-danger" type="button">
                                        <i class="fas fa-fw fa-trash"></i>
                                      </button>
                                  </div>
                              </div>
                              <div class="row" id="noexiste_progresivo">
                                <p  style="display:block;margin-top:25px; margin-bottom:20px;"><i class="fa fa-times aviso"></i> No existe ningún progresivo activo.</p>
                              </div>
                              <div class="row">
                                <div class="col-lg-10 col-lg-offset-1">
                                  <table id="tablaNivelesSeleccionados" class="" hidden="true">
                                      <thead>
                                        <th class="col-lg-2">Nro Nivel</th>
                                        <th class="col-lg-2">Nombre nivel</th>
                                        <th class="col-lg-2">Base</th>
                                        <th class="col-lg-2">% Visible</th>
                                        <th class="col-lg-2">% Oculto</th>
                                        <th class="col-lg-2"></th>
                                      </thead>
                                      <tbody id="nivelesSeleccionados">

                                      </tbody >
                                    </table>
                                </div>
                              </div>
                          <br>
                          <div id="seccionAgregarProgresivo" style="cursor:pointer;" data-toggle="collapse" data-target="#collapseAgregarProgresivo">
                              <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                                  <div class="col-md-12">
                                      <h6>AGREGAR PROGRESIVO<i class="fa fa-fw fa-angle-down"></i></h6>
                                  </div>
                              </div>
                          </div>
                          <div id="collapseAgregarProgresivo" class="collapse">
                            <div class="row">
                              <div class="col-md-6 col-lg-6">
                                <h5>Nombre Progresivo</h5>
                                <input id="nombre_progresivo" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
                                <br>
                                <!-- <div id="alerta-nombre_progresivo" class="alert alert-danger"><span></span></div> -->
                                <span id="alerta-nombre-progresivo" class="alertaSpan"></span>
                              </div>
                              <div class="col-md-6 col-lg-6">
                                <h5>Tipo Progresivo</h5>
                                <select class="form-control" id="selectTipoProgresivos">
                                  <option value="0">-Seleccione un tipo-</option>
                                  @foreach ($tipo_progresivos as $tipo_progresivo)
                                  <option value="{{$tipo_progresivo}}">{{$tipo_progresivo}}</option>
                                  @endforeach
                                </select>
                              </div>
                            </div>
                            <div class="row">
                              <div class="col-xs-6 col-md-6 col-lg-6">
                                <h5>Porcentaje Recuperación</h5>
                                <input id="porcentaje_recuperacion" type="text" class="form-control" placeholder="Porcentaje recuperación">
                              </div>
                              <div class="col-xs-6 col-md-6 col-lg-6">
                                <h5>Valor Máximo</h5>
                                <input id="maximo" type="text" class="form-control" placeholder="Valor Máximo">
                              </div>

                            </div>
                            <br>
                            <div hidden="true" id="cuerpo_individual">
                              <div class="row">
                                <div class="col-xs-6 col-md-6 col-lg-6">

                                  <h5>Buscador Islas <i class="fa fa-fw fa-search"></i></h5>
                                  <div class="row">

                                    <div class="input-group">
                                        <input id="input-datos-grupo" class="form-control buscadorIsla" type="text" value="" autocomplete="off">
                                        <span class="input-group-btn">
                                          <button class="btn btn-default btn-lista-datos agregarIsla" type="button"><i class="fa fa-plus"></i></button>
                                        </span>
                                    </div>

                                  </div>
                                  <br>
                                  <h5>Buscador Maquinas <i class="fa fa-fw fa-search"></i></h5>
                                  <div class="row">
                                    <div class="input-group">
                                        <input id="input-datos-grupo" class="form-control buscadorMaquina" type="text" value="" autocomplete="off">
                                        <span class="input-group-btn">
                                          <button class="btn btn-default btn-lista-datos agregarMaquina" type="button"><i class="fa fa-plus"></i></button>
                                        </span>
                                    </div>
                                  </div>

                                </div>

                                <div id="" class="col-md-6 col-lg-6">
                                  <div class="row">
                                    <div class="col-md-8 col-lg-8">
                                      <h5>Maquinas Seleccionadas:</h5>
                                    </div>
                                    <div class="col-md-4 col-lg-4 errorVacio">

                                    </div>
                                  </div>
                                  <ul class="listaMaquinas">
                                  </ul>
                                </div>
                              </div>
                              <br>
                              <div class="row">
                                  <div class="col-lg-12">
                                      <h5>Niveles Progresivo <button class="btn btn-success btn-agregarNivelProgresivo" type="button"><i class="fa fa-fw fa-plus"></i> Agregar</button></h5>
                                      <div class="columna">
                                      </div>
                                  </div>
                              </div>

                            </div>
                            <div hidden="true" id="cuerpo_linkeado">
                              <!-- comienzo seccdio invididual -->
                              <div class="row">
                                <h5>Nuevo Pozo:  <button id="btn-agregarPozo" class="btn btn-success  " type="button"><i class="fa fa-fw fa-plus"></i> Agregar</button></h5>
                              </div>

                             <div id="contenedorPozos" class="">

                             </div>
                              <!-- fin despaleable pozos-->
                            </div>
                            <button id="btn-cancelarProgresivo" class="btn btn-danger" type="button" name="button">
                                <i class="fa fa-fw fa-times"></i> LIMPIAR CAMPOS
                            </button>
                            <button id="btn-agregarProgresivo" class="btn btn-successAceptar" type="button" name="button">
                                <i class="fa fa-fw fa-arrow-up"></i> AGREGAR PROGRESIVO
                            </button>
                            <button id="btn-crearProgresivo" class="btn btn-successAceptar" type="button" name="button">
                              <i class="fa fa-fw fa-plus"></i> CREAR PROGRESIVO
                            </button>

                            </div>
                          </div>
                        </div>
                      </div> <!-- / PASO 3 | Progresivo -->

                      <!-- / PASO 4 | SOFT -->
                      <div class="seccion" id="secSoft">

                        <div id="listaSoftMaquina" data-agregado="false" style="padding: 5px 0px 30px 0px;">
                            <div class="row">
                                <div class="col-md-12">
                                  <h6>GLI SOFTWARE ACTIVO</h6>

                                  <!-- Tabla de todos los gli soft en la máquina -->
                                  <table id="tablaSoftActivo" class="table" hidden style="margin-top:30px; margin-bottom:20px;">
                                    <thead>
                                      <tr>
                                        <th width="30%">CÓDIGO DE CERTIFICADO</th>
                                        <th width="40%">ARCHIVO</th>
                                        <th width="30%">ACCIÓN</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr id="datosGLISoft" data-id="" data-codigo="" data-observaciones="">
                                        <td>
                                          <span id="nro_certificado_activo" class="badge" style="background-color: #6dc7be;font-family:Roboto-Regular;font-size:18px;margin-top:-3px;">123</span>
                                        </td>
                                        <td id="nombre_archivo_activo"></td>
                                        <td>
                                          <button type="button" class="btn btn-danger borrarSoft" name="button">
                                            <i class="fa fa-fw fa-trash"></i>
                                          </button>
                                        </td>
                                      </tr>
                                    </tbody>
                                  </table>

                                  <div class="zona-file" hidden>
                                    <!-- <input id="muestraArchivoSoft" type="file" name="" value=""> -->
                                  </div>

                                  <p id="noexiste_soft" style="display:block;margin-top:30px; margin-bottom:20px;"><i class="fa fa-times aviso"></i> La máquina no contiene certificado de GLI Software.</p>
                                </div>
                            </div>

                        </div>


                        <!-- CREAR O BUSCAR GLI soft-->
                        <div id="agregarSoft" style="cursor:pointer;" data-toggle="collapse" data-target="#softPlegado">
                            <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                                <div class="col-md-12">
                                    <h6>AGREGAR GLI SOFTWARE<i class="fa fa-fw fa-angle-down"></i></h6>
                                </div>
                            </div>
                        </div>

                        <div id="softPlegado" class="collapse">
                            <br>
                            <div class="row" style="padding-bottom: 15px;">
                                <div class="col-md-4 col-md-offset-1">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5>Código de Certificado</h5>
                                            <input id="inputSoft" data-software=""  class="form-control" type="text" autocomplete="off" placeholder="Buscar GLI Software"/>
                                            <!-- <input id="inputSoft" data-soft="" class="form-control" type="text" list="soft" autocomplete="off" placeholder="Código de certificado" /> -->
                                            <!-- <datalist id="soft"> </datalist> -->
                                            <!-- <span id="alerta_codigo_soft" class="alertaSpan"></span> -->
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5>Observaciones</h5>
                                            <textarea id="observaciones" class="form-control" rows="10" style="resize:none; height:80px;" placeholder="Observaciones"></textarea>
                                            <span id="alerta_observaciones" class="alertaSpan"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Archivo</h5>
                                    <div class="zona-file">
                                        <input id="cargaArchivoSoft" data-borrado="false" type="file">
                                    </div>
                                    <span id="alerta_archivoSoft" class="alertaSpan"></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <button id="btn-cancelarSoft" class="btn btn-danger" type="button" name="button">
                                        <i class="fa fa-fw fa-times"></i> LIMPIAR CAMPOS
                                    </button>
                                    <button id="btn-crearSoft" class="btn btn-successAceptar" type="button" name="button">
                                        <i class="fa fa-fw fa-plus"></i> CREAR GLI SOFTWARE
                                    </button>
                                    <button id="btn-agregarSoftLista" class="btn btn-successAceptar" type="button" name="button">
                                        <i class="fa fa-fw fa-arrow-up"></i> AGREGAR GLI SOFTWARE
                                    </button>
                                </div>
                            </div>
                        </div>
                      </div> <!-- / PASO 4 | soft -->



                      <!-- PASO 5 | HARD -->
                      <div class="seccion" id="secHard">
                        <div id="listaHardMaquina" data-agregado="false" style="padding: 5px 0px 30px 0px;">
                            <div class="row">
                                <div class="col-md-12">
                                  <h6>GLI HARDWARE ACTIVO</h6>

                                  <!-- Tabla de todos los gli hard en la máquina -->
                                  <table id="tablaHardActivo" class="table" hidden style="margin-top:30px; margin-bottom:20px;">
                                    <thead>
                                      <tr>
                                        <th width="30%">CÓDIGO DE CERTIFICADO</th>
                                        <th width="40%">ARCHIVO</th>
                                        <th width="30%">ACCIÓN</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                        <td>
                                          <span id="nro_certificado_hard_activo" class="badge" style="background-color: #6dc7be;font-family:Roboto-Regular;font-size:18px;margin-top:-3px;">123</span>
                                        </td>
                                        <td id="nombre_archivo_hard_activo"></td>
                                        <td>
                                          <button type="button" class="btn btn-danger borrarHard" name="button">
                                            <i class="fa fa-fw fa-trash"></i>
                                          </button>
                                        </td>
                                      </tr>

                                    </tbody>
                                  </table>

                                  <div class="zona-file" hidden>
                                    <!-- <input id="muestraArchivoSoft" type="file" name="" value=""> -->
                                  </div>

                                  <p id="noexiste_hard" style="display:block;margin-top:30px; margin-bottom:20px;">
                                    <i class="fa fa-times aviso"></i> La máquina no contiene certificado de GLI Hardware.</p>
                                </div>
                            </div>

                        </div>

                        <!-- CREAR O BUSCAR GLI HARD-->
                        <div id="agregarHard" style="cursor:pointer;" data-toggle="collapse" data-target="#hardPlegado">
                            <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                                <div class="col-md-12">
                                    <h6>AGREGAR GLI HARDWARE<i class="fa fa-fw fa-angle-down"></i></h6>
                                </div>
                            </div>
                        </div>

                        <div id="hardPlegado" class="collapse">

                            <div class="row" style="padding-bottom: 15px;">
                                <div class="col-md-4 col-md-offset-1">
                                    <h5>Código de Certificado</h5>
                                    <!-- <input id="inputHard" data-hard="" class="form-control" type="text" list="hard" autocomplete="off" placeholder="Código de certificado"/> -->
                                    <input id="inputHard" class="form-control" type="text" autocomplete="off" placeholder="Buscar GLI Hardware"/>
                                </div>
                                <div class="col-md-6">
                                    <h5>Archivo</h5>
                                    <div class="zona-file">
                                        <input id="cargaArchivoHard" data-borrado="false" type="file">
                                    </div>
                                    <span id="alerta_archivoHard" class="alertaSpan"></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <button id="btn-cancelarHard" class="btn btn-danger" type="button" name="button">
                                        <i class="fa fa-fw fa-times"></i> LIMPIAR CAMPOS
                                    </button>
                                    <button id="btn-crearHard" class="btn btn-successAceptar" type="button" name="button">
                                        <i class="fa fa-fw fa-plus"></i> CREAR GLI HARDWARE
                                    </button>
                                    <button id="btn-agregarHardLista" class="btn btn-successAceptar" type="button" name="button">
                                        <i class="fa fa-fw fa-arrow-up"></i> AGREGAR GLI HARDWARE
                                    </button>
                                </div>
                            </div>
                        </div>

                      </div> <!-- / PASO 4 | HARD -->

                      <!-- PASO 6 | FORMULA -->

                      <div class="seccion" id="secFormula">
                        <form id="frmFormula" name="frmFormula" class="form-horizontal" novalidate="">
                          <div class="row">
                            <div class="col-lg-12">
                              <h6>FÓRMULA </h6>
                              <br>
                              <div class="row">
                                  <div class="col-xs-6 col-xs-offset-1">
                                      <h5>Formula seleccionada</h5>
                                      <p id="formulaSeleccionada">No existe formula seleccionada.</p>

                                  </div>
                                  <div class="col-xs-5">
                                      <h5>Acción</h5>
                                      <button id="borrarFormulaSeleccionada" type="button" class="btn btn-danger borrarFila"  name="button" ><i class="fa fa-trash"></i></button>
                                  </div>
                              </div>
                              <br>
                              <div id="seccionAgregarFormula" style="cursor:pointer;" data-toggle="collapse" data-target="#collapseAgregarFormula">
                                  <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                                      <div class="col-md-12">
                                          <h6>AGREGAR FÓRMULA<i class="fa fa-fw fa-angle-down"></i></h6>
                                      </div>
                                  </div>
                              </div>
                              <div id="collapseAgregarFormula" class="collapse">
                              <div class="row">
                                <h5>Buscar Formula <i class="fa fa-search"></i></h5>
                                  <div class="input-group lista-datos-group">
                                                  <input id="inputFormula" class="form-control " type="text" value="" autocomplete="off" >
                                                  <span class="input-group-btn">
                                                    <button class="btn btn-default btn-lista-datos agregarFormula" type="button"><i class="fa fa-plus"></i></button>
                                                  </span>
                                </div>
                              </div>
                              <br>
                              <span id="alerta_formula" class="alertaSpan"></span>
                            </div>
                          </div>
                      </div>
                    </form>
                    </div><!-- / PASO 5 | FORMULA -->

                  </div> <!-- /Fin panel minimizable -->
                </div> <!-- Fin modal-header -->

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">Crear MÁQUINA</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_maquina" value="0">
                </div>
            </div>
          </div>
    </div>

    <!-- Modal Carga Masiva -->
    <div class="modal fade" id="modalCargaMasiva" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header modalNuevo">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" style="color: #fff;">| CARGA MASIVA</h3>
                </div>

          <div id="colapsado" class="collapse in">
                <div class="modal-body">
                  <form id="frmCargaMasiva" name="frmCargaMasiva" class="form-horizontal" novalidate="">
                      <div class="row">
                        <div class="col-md-4">
                            <h5>Seleccionar Casino:</h5>
                            <select class="form-control" id="contenedorCargaMasiva">
                              @foreach ($usuario['usuario']->casinos as $casino)
                              <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                              @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <h5>Buscar CSV</h5>
                            <input id="cargaMasiva" type="file" name="Archivo de Máquinas" accept="">
                        </div>
                              <!-- <button type="button" class="btn btn-success" id="btn-carga-masiva" value=""> SIGUIENTE</button> -->
                      </div><br>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-carga-masiva" value="nuevo">ACEPTAR</button>
                  <button id='boton-cancelar' type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <button id='boton-salir' type="button" class="btn btn-default" data-dismiss="modal" style="display: none;">SALIR</button>
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
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                  <form id="frmEliminar" name="frmCasino" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Seguro desea eliminar la MTM?</strong>
                          </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarModal" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| GESTIONAR MÁQUINAS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Gestionar máquinas</h5>
      <p>
        Sección que permite definir y gestionar configuraciones vinculadas a máquinas tragamonedas.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>

    <!-- JavaScript personalizado -->
    <script src="/js/seccionMaquinas-Formula.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-JuegoNuevo.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-GliSoftNuevo.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-GliHardNuevo.js" charset="utf-8"></script>
    <script src="/js/seccionMaquina-IslaNuevo.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-Progresivo.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-Modal.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas.js" charset="utf-8"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- Custom input Bootstrap -->
    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    @endsection
