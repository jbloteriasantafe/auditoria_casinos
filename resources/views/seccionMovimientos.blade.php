<?php use App\Http\Controllers\UsuarioController;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
 ?>
 @extends('includes.dashboard')
 @section('headerLogo')
 <span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
 @endsection

@section('estilos')
  <link rel="stylesheet" href="css/bootstrap-datetimepicker.min.css">
  <link rel="stylesheet" href="css/paginacion.css">
  <link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
  <link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
  <link rel="stylesheet" href="/css/lista-datos.css">
@endsection

@section('contenidoVista')

<div class="row">
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
                <div class="col-lg-3">
                  <h5>Nro. de Máquina</h5>
                  <input id="busqueda_maquina" type="text" class="form-control" placeholder="Nro. de máquina">
                </div>
                <div class="col-lg-3">
                  <h5>Número de expediente</h5>
                  <div class="input-group triple-input">
                    <input id="B_nro_exp_org" style="width:30%; border-right:none;" type="text" placeholder="-----" maxlength="5" class="form-control" />
                    <input id="B_nro_exp_interno" style="width:50%;" type="text" placeholder="-------" maxlength="7" class="form-control" />
                    <input id="B_nro_exp_control" style="width:20%; border-left:none;" type="text" placeholder="-" maxlength="1" class="form-control" />
                  </div>
                </div>
                <div class="col-lg-3">
                  <h5>Tipo Movimiento</h5>
                  <select class="form-control" id="B_TipoMovimiento">
                    <option value="0" selected>- Seleccione tipo movimiento -</option>
                    @foreach ($tiposMovimientos as $tm)
                    @if(!$tm->es_intervencion_mtm && !$tm->deprecado)
                    <option value="{{$tm->id_tipo_movimiento}}">{{$tm->descripcion}}</option>
                    @endif
                    @endforeach
                    <optgroup style="color:red;" label="Fuera de uso">
                    @foreach ($tiposMovimientos as $tm)
                    @if($tm->es_intervencion_mtm || $tm->deprecado)
                    <option value="{{$tm->id_tipo_movimiento}}" style="color:red;">{{$tm->descripcion}}</option>
                    @endif
                    @endforeach
                    </optgroup>
                  </select>
                </div>
                <div class="col-lg-3">
                  <h5>Fecha</h5>
                  <div class="form-group">
                    <div class='input-group date' id='dtpFechaMov' data-link-field="fecha_movimiento" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                      <input type='text' class="form-control" placeholder="Fecha de Movimiento" id="B_fecha_mov" value=""/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="fecha_movimiento" value=""/>
                  </div>
                </div>
                <div class="col-lg-3">
                  <h5>Casino</h5>
                  <select class="form-control" id="dtpCasinoMov">
                    <option value="0" selected>- Seleccione casino -</option>
                    @foreach ($usuario['usuario']->casinos as $casino)
                    <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-3">
                  <h5>Nro. de movimiento</h5>
                  <input id="busqueda_numero" type="text" class="form-control" placeholder="Nro. de movimiento">
                </div>
              </div> <!-- row / formulario -->
              <br>
              <div class="row">
                <div class="col-md-12" style="text-align: center">
                  <button id="btn-buscarMovimiento" class="btn btn-infoBuscar" type="button" name="button">
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
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4>ÚLTIMOS MOVIMIENTOS</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultados" class="table table-fixed tablesorter">
              <thead>
                <tr>
                  <th class="col-md-1" value="casino.nombre" estado="">CASINO<i class="fa fa-sort"></i></th>
                  <th class="col-md-1" value="log_movimiento.id_log_movimiento" estado="">NÚMERO<i class="fa fa-sort"></i></th>
                  <th class="col-md-2" value="log_movimiento.fecha" estado="">FECHA<i class="fa fa-sort"></i></th>
                  <th class="col-md-1" value="expediente.nro_exp_org" estado="">EXPED.<i class="fa fa-sort"></i></th>
                  <th class="col-md-2" value="log_movimiento.islas" estado="">ISLAS<i class="fa fa-sort"></i></th>
                  <th class="col-md-2" value="tipo_movimiento.descripcion" estado="">TIPO MOVIMIENTO<i class="fa fa-sort"></i></th>
                  <th class="col-md-1" value="log_movimiento.id_estado_movimiento" estado="">ESTADO<i class="fa fa-sort"></i></th>
                  <th class="col-md-2" >ACCIÓN </th>
                </tr>
              </thead>
              <tbody id='cuerpoTabla' style="height: 380px;">
              </tbody>
            </table>
            <div id="herramientasPaginacion" class="row zonaPaginacion">
            </div>
          </div>
        </div>
      </div>
    </div> <!-- .row / TABLA -->
  </div> <!-- col-xl-9 -->
  <div class="col-xl-3">
    <!-- Botón nuevo Ingreso -->
    <div class="row">
      <div class="col-lg-12">
        <a href="" id="btn-nuevo-movimiento" style="text-decoration: none;">
          <div class="panel panel-default panelBotonNuevo">
            <img class="imgNuevo" src="/img/logos/informes_white.png" style="display: block;margin: 0 auto;">
            <div class="backgroundNuevo">
            </div>
            <div class="row">
              <div class="col-xs-12" style="text-align: center">
                <h5 class="txtLogo">+</h5>
                <h4 class="txtNuevo">NUEVO MOVIMIENTO</h4>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div> <!-- .row -->
  </div> <!-- col-xl-3 | COLUMNA DERECHA - BOTONES -->
</div>

<!-- **************Modal para ingresos*****************************-->
<div class="modal fade" id="modalIngresoElegirTipo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header" >
              <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
              <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
              <h3 class="modal-title modalVerMas" id="myModalLabel">MÁQUINAS A INGRESAR</h3>
          </div>

          <div  id="colapsado" class="collapse in">

            <div class="modal-body" style="font-family: Roboto;">
                <form id="frmLog" name="frmLog" class="form-horizontal" novalidate="">
                    <div class="row">
                          <div class="col-md-12">
                            <br>
                              <h6 align="center" >CONCEPTO DEL EXPEDIENTE</h6>
                              <br>
                              <textarea class="form-control" id="conceptoExpediente" readonly style="resize:none; height:100px;">Agregar 30 máquinas al sector 2 del CASINO Santa Fe.</textarea>
                          </div>
                    </div>
                      <br>
                    <div  class="row">
                        <div class="col-md-6" style="text-align: center">
                          <h5>CARGA MANUAL</h5>
                          <input id="tipoManual" type="radio" name="carga" value="1">
                        </div>
                        <div class="col-md-6" style="text-align: center">
                          <h5>CARGA MASIVA</h5>
                          <input id="tipoCargaSel" type="radio" name="carga" value="2">
                        </div>
                    </div>
                      <br>
                      <div id="cantMaqCargar" class="row">
                            <div class="col-md-8 col-md-offset-2" style="text-align: center">
                              <h5>Cantidad de máquinas a ingresar</h5>
                              <div class="input-group number-spinner">
                                <span class="input-group-btn">
                                  <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="dwn">-</button>
                                </span>
                                <input id="cant_maq" type="text" class="form-control text-center" value="1">
                                <span class="input-group-btn">
                                  <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="up">+</button>
                                </span>
                              </div>
                            </div>
                      </div>
                  </form>
                </div>
                <div class="modal-footer">
                  <input id="id_log_movimiento" type="text" name="" value="" hidden=true>
                  <button id="btn-aceptar-ingreso" type="button" class="btn btn-guardar btn-successAceptar" >ACEPTAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
                <div id="mensajeErrorCarga" hidden>
                    <br>
                    <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
                    <br>
                    <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;"></span>
                </div> <!-- mensaje -->

            </div> <!-- collapsado -->
        </div> <!-- modal-content -->
    </div> <!-- modal-dialog -->
</div> <!-- modal -->


<!--****** MODAL PARA PREGUNTAR EL CASINOy tipo de mov QUE SE DESEA CREAR ****-->
<div class="modal fade" id="modalCas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
         <div class="modal-content">
            <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be;">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
              <h3 class="modal-titleCas">NUEVO MOVIMIENTO</h3>
            </div>

            <div class="modal-body" style="font-family: Roboto;">
              <form id="frmElegir" name="frmCasino" class="form-horizontal" novalidate="">
                  <div class="form-group error ">
                      <div class="col-xs-6">
                        <h5>Casino</h5>
                        <select class="form-control" id="selectCasinoIngreso">
                           <option class="default1" value="">- Seleccione el casino -</option>
                         </select>
                      </div>
                      <div class="col-xs-6">
                        <h5>TIPO MOVIMIENTO</h5>
                        <select id="tipo_movimiento_nuevo" class="form-control" name="">
                          <option class="default2" value="">- Seleccione el Tipo -</option>
                        </select>
                        <br>
                      </div>
                  </div>
              </form>
            </div>

          <div class="modal-footer">
              <button id="aceptarCasinoIng" type="button" class="btn btn-aceptarCas btn-success" >ACEPTAR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
      </div>
    </div>
</div>

<div class="modal fade" id="modalEnviarFiscalizar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 70%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">SELECCIÓN DE MTMs PARA ENVÍO A FISCALIZAR</h3>
      </div>

      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">

          <div class="row">
            <div class="row">
              <div class="col-md-1">
                <h5 class="" style="">FECHA</h5>
              </div>
              <div class="col-md-6">
                <div class="form-group" style="padding-left:0px;padding-right:80px;">
                  <div class='input-group date' id='dtpFechaIngreso' data-link-field="fecha_cierre" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                    <input type='text' class="form-control" placeholder="Fecha a fiscalizar" id="B_fecha_ingreso" value=""/>
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
              <br><br>

            </div>
            <div class="col-md-12">
              <table id="tablaMaquinas" class="table">
                <thead>
                  <tr>
                    <th class="col-xs-3">  </th>
                    <th class="col-xs-9">Máquinas a fiscalizar</th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
              </table>
            </div>

          </div>
        </div> <!-- fin del body -->

        <div class="modal-footer">
          <button id="btn-enviar-ingreso" type="button" class="btn btn-guardar btn-successAceptar" >ENVIAR A FISCALIZAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>

      </div> <!-- collapsado -->
    </div> <!-- modal-content -->
  </div> <!-- modal-dialog -->
</div> <!-- modal -->
 
<div class="modal fade" id="modalEgresoElegirMaquinas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 70%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">CARGAR MÁQUINAS A EGRESAR</h3>
      </div>

      <div class="modal-body" style="">
        <div  id="colapsado" class="collapse in">
          <div class="row"> <!-- ROW 1 -->
            <div class="col-md-6">
              <h6>Agregar Máquina</h6>
              <div class="row">
                <div class="input-group lista-datos-group">
                  <input id="inputMaq" class="form-control" type="text" value="" autocomplete="off" placeholder="Ingrese el número de la máquina" >
                  <span class="input-group-btn">
                    <button id="agregarMaq" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                  </span>
                </div>
              </div>
            </div>
            <br>
          </div> <!-- FIN ROW 1 -->
          <br>
          <div class="row"> <!-- ROW 2 -->
            <div class="col-md-12">
              <h6>MÁQUINAS SELECCIONADAS</h6>
              <br>
              <table id="tablaMaquinasSeleccionadas" class="table">
                <thead>
                  <tr>
                    <th class="col-md-1">NÚMERO</th>
                    <th class="col-md-1">MARCA</th>
                    <th class="col-md-2">MODELO</th>
                    <th class="col-md-1">ISLA</th>
                    <th class="col-md-3">JUEGO</th>
                    <th class="col-md-3">SERIE</th>
                    <th class="col-md-1">ACCIÓN</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          </div> <!-- FIN ROW 2 -->
        </div> <!-- colapsado -->
      </div> <!-- modal body -->

      <div class="modal-footer">
        <button id="boton-cancelar" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        <button id="btn-enviar-egreso" type="button" class="btn btn-default" value="" >CARGAR MÁQUINAS</button>
      </div> <!-- modal footer -->
    </div> <!-- modal-content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->

  <!--********************* Modal para VALIDACIÓN *****************************-->
<div class="modal fade" id="modalValidacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 90%">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">Validar Máquinas</h3>
      </div>

      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">

          <div class="row" >
            <div class="col-md-2" >
              <h6><b>FECHA</b></h6>
              <table id="tablaFechasFiscalizacion" class="table">
                <thead>
                  <tr>
                    <th> </th>
                    <th> </th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div> <!-- tablafechas -->
            <div class="col-md-10">
            @include('divRelevamientoMovimiento')
            </div>
          </div>  <!-- fin row inicial -->
          <br/>
          <div class="modal-footer">
            <button type="button" class="btn btn-default cancelar" data-dismiss="modal" aria-label="Close">SALIR</button>    
          </div>
          </div> <!-- modal body -->
      </div> <!--  modal colap-->
    </div>  <!-- modal content -->
  </div> <!--  modal dialog -->
</div> <!-- modal fade -->

<!-- MODAL ELIMINAR -->

<div class="modal fade" id="modalAlerta" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
       <div class="modal-content">

         <div class="modal-header" style="background: #d9534f; color: #E53935;">
           <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
           <h3 class="modal-titleEliminar" id="myModalLabel" style="color:#000000;">ERROR!</h3>
         </div>

        <div class="modal-body" style="color:#fff; background-color:#FFFFF;">
                <!-- Si no anda falta el <fieldset> -->
              <h4 style="color:#000000">Este movimiento no posee máquinas en proceso de fiscalización.</h4>
        </div>
        <br>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">ACEPTAR</button>
        </div>
    </div>
  </div>
</div>

  <!-- *********************MODAL MÁQUINA **************************************** -->
  <div class="modal fade" id="modalMaquina" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
         <div class="modal-content">
            <div class="modal-header" style="padding-bottom: 0px !important;">

              <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
              <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>

              <center>
                <h3 class="modal-title" style="margin-left:30px; color: #fff; text-align:center; display:inline;">NUEVA MÁQUINA TRAGAMONEDAS</h3>
              </center>

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
                      color: #009688;
                      padding-bottom: 20px;
                      margin-bottom: 0px !important;
                  }

                  .navModal a.navModalActivo h4 {
                      color: white;
                      font-size: 20px;
                      border-bottom: 5px solid #fff;
                  }

              </style>

              <div class="navModal" style="position:relative; bottom:-15px; text-align:center; font-family: Roboto-Regular; font-size: 20px; color: #999;">

                    <div width="10%">
                          <i id="error_nav_maquina" class="fa fa-times" style="color:red; display:none;"></i>
                          <a href="" id="navMaquina"><h4>MÁQUINA</h4></a>
                    </div>
                    <div width="10%">
                          <i id="error_nav_isla" class="fa fa-times" style="color:red; display:none;"></i>
                          <a href="" id="navIsla"><h4>ISLA</h4></a>
                    </div>
                    <div width="10%">
                          <i id="error_nav_juego" class="fa fa-times" style="color:#F44336;"></i>
                          <a href="" id="navJuego"><h4>JUEGOS</h4></a>
                    </div>
                    <div width="10%">
                          <i id="error_nav_formula" class="fa fa-times" style="color:red; display:none;"></i>
                          <a href="" id="navFormula"><h4>FÓRMULA</h4></a>
                    </div>

              </div>

            </div>

            <div class="modal-body">

              <!-- Panel que se minimiza -->
              <div  id="colapsado" class="collapse in">

                  <!-- PASO 1 | MÁQUINA -->
                  <div class="seccion" id="secMaquina">
                    <form id="frmMaquina" name="frmMaquina" class="form-horizontal" novalidate="">

                      <div class="row">
                        <div class="col-md-12">
                          <h6>DETALLES DE LA MÁQUINA</h6>
                        </div>
                      </div>

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
                          </select>
                           <br>
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
                          <h5>Marca - Juego <input class="form-check-input" type="checkbox" value="" id="marca_juego_check" title="Por defecto se genera solo segun la marca y el juego activo, desmarcarlo si se desea cambiarlo a mano."></h5>
                          <input id="marca_juego" type="text" class="form-control" placeholder="Descripción marca">
                          <br>
                          <span id="alerta_desc_marca" class="alertaSpan"></span>
                        </div>
                        <div class="col-lg-4">
                          <h5>Gabinete</h5>
                          <select class="form-control" id="tipo_gabinete">
                            <option value="">-Tipo de Gabinete-</option>
                            @foreach ($gabinetes as $gabinete)
                            <option value="{{$gabinete->id_tipo_gabinete}}">{{$gabinete->descripcion}}</option>
                            @endforeach
                          </select>
                          <br>
                          <span id="alerta_gabinete" class="alertaSpan"></span>
                        </div>
                        <div class="col-lg-4">
                          <h5>Tipo de Máquina</h5>
                          <select class="form-control" id="tipo_maquina">
                            <option value="">-Tipo de Máquina-</option>
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
                        </div>

                        <div class="col-lg-4">
                          <h5>Estado</h5>
                          <select class="form-control" id="estado">
                            <option value="0">-Estado Máquina-</option>
                            @foreach ($estados as $estado)
                            <option value="{{$estado->id_estado_maquina}}">{{$estado->descripcion}}</option>
                            @endforeach
                          </select>
                        </div>
                        {{-- <div class="col-lg-4">
                          <h5>% Devolución</h5>
                          <input id="porcentaje_devolucion" type="text" class="form-control" placeholder="Porcentaje Devolución">
                        </div> --}}
                        <div class="col-lg-4">
                          <h5>Progresivo</h5>
                          <select class="form-control" id="juega_progresivo">
                            <option value="0">NO</option>
                            <option value="1">SI</option>
                          </select>
                        </div>

                      </div>
                      <br>
                      <div class="row">


                        <div class="col-lg-4">
                          <h5>Moneda</h5>
                          <select class="form-control" id="tipo_moneda">
                            @foreach ($monedas as $moneda)
                            <option value="{{$moneda->id_tipo_moneda}}">{{$moneda->descripcion}}</option>
                            @endforeach
                          </select>
                        </div>

                      </div>
                      <br>
                      <div class="row">

                        <div class="col-lg-4">

                          <h5>Expedientes</h5>

                          <div id="M_expediente" class="input-group triple-input">
                              <input id="M_nro_exp_org" style="width:30%; border-right:none;" type="text" placeholder="-----" maxlength="5" class="form-control" />
                              <input id="M_nro_exp_interno" style="width:50%;" type="text" placeholder="-------" maxlength="7" class="form-control" />
                              <input id="M_nro_exp_control" style="width:20%; border-left:none;" type="text" placeholder="-" maxlength="1" class="form-control" />
                          </div>

                        </div>
                             <!-- fin de la columna  -->
                      </div>

                    </form>
                  </div> <!-- / PASO 1 | MÁQUINA -->

                  <!-- PASO 2 | ISLA-->
                  <div class="seccion" id="secIsla">
                    <div id="listaSoftMaquina" data-agregado="false" style="padding: 0px 0px 30px 0px;">
                        <div class="row">
                            <div class="col-md-12">
                              <h6>ISLA ACTIVA</h6>

                              <table id="tablaIslaActiva" class="table" hidden style="margin-top:30px; margin-bottom:20px;">
                                <thead>
                                  <tr>
                                      <th width="15%">ISLA</th>
                                      <th width="15%">MÁQUINAS</th>
                                      <th width="20%">CASINO</th>
                                      <th width="20%">SECTOR</th>
                                      <th width="15%">ACCIÓN</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <tr id="activa_datos" data-isla="" data-casino="" data-sector="">
                                      <td id="activa_nro_isla">
                                        <span class="badge" style="background-color: #6dc7be;font-family:Roboto-Regular;font-size:18px;margin-top:-3px;">999999</span>
                                      </td>
                                      <td id="activa_cantidad_maquinas">99999999999</td>
                                      <td id="activa_casino">INVALIDO</td>
                                      <td id="activa_zona">INVALIDO</td>
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

                        <div id="headListaIsla" class="row" hidden="true">
                            <div class="col-xs-2">
                                <h5>Casino</h5>
                            </div>
                            <div class="col-xs-2">
                                <h5>Sector</h5>
                            </div>
                            <div class="col-xs-2">
                                <h5>Nro Isla</h5>
                            </div>
                            <div class="col-xs-2">
                                <h5>Cant. Máquinas</h5>
                            </div>
                            <div class="col-xs-2">
                                <h5>Acciones</h5>
                            </div>
                        </div>
                    </div>

                    <!-- BUSCAR Isla-->
                    <div id="asociarIsla" style="cursor:pointer;" data-toggle="collapse" data-target="#islaPlegado">
                        <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                            <div class="col-md-12" >
                                <h6 >ASOCIAR ISLA<i class="fa fa-fw fa-angle-down"></i></h6>
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
                                  <br>
                                  <span id="alerta_nro_isla" class="alertaSpan"></span>
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
                                              <th width="5%"></th>
                                              <th width="25%">NÚMERO</th>
                                              <th width="35%">MARCA</th>
                                              <th width="35%">MODELO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="0" class="actual">
                                              <td><i class="fa fa-star" style="color:#FB8C00;"></i></td>
                                              <td>-</td>
                                              <td>-</td>
                                              <td>-</td>
                                              <td></td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <br><br>
                                    <p style="color:#aaa;">
                                      <i class="fa fa-fw fa-star" style="color:#FB8C00;"></i> Máquina actual
                                      <i class="fa fa-fw fa-plus" style="color:#00C853; margin-left:20px;"></i> Máquina agregada
                                    </p><br><br>
                                </div>
                            </div>

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
                                <button id="btn-asociarIsla" class="btn btn-success" type="button" name="button" style="display: none;">
                                    <i class="fa fa-fw fa-arrow-up" style="position:relative; left:-1px; top:-1px;"></i> ASOCIAR ISLA
                                </button>
                            </div>
                        </div>
                    </div>
                  </div> <!-- / PASO 2 | ISLA -->

                  <!-- PASO 3 | JUEGO -->
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
                                        <th width="10%">DENOMINACIÓN</th>
                                        <th width="10%">% DEV</th>
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
                                    <h6>ASOCIAR JUEGO<i class="fa fa-fw fa-angle-down"></i></h6>
                                </div>
                            </div>
                        </div>

                        <div id="juegoPlegado" class="collapse">
                            <div class="row" >
                                <div class="col-md-4">
                                    <h5>Nombre Juego</h5>
                                      <!-- <input id="nro_isla"  class="form-control" type="text"  placeholder="Número de isla" autocomplete="off"> -->
                                    <input id="inputJuego" class="form-control" type="text" autocomplete="off" placeholder="Nombre juego" />
                                    <!-- <datalist id="juego"> </datalist> -->
                                </div>
                                <div class="col-md-4">
                                    <h5>Código de Juego</h5>
                                    <input id="inputCodigo" disabled data-codigo="" class="form-control" type="text" autocomplete="off" placeholder="Código de juego"/>
                                </div>

                            </div>

                            <div class="row" style="padding-bottom: 15px;">
                              <div class="col-md-4">
                                <h5>Den. de Sala</h5>
                                <input id="den_sala" class="form-control" type="text" name="" value="" placeholder="ej: 0.1/0.5/1">
                            </div>
                            <div class="col-md-4">
                                <h5>% Dev</h5>
                                <input id="porcentaje_devolucion_juego" class="form-control" type="text" name="" value="" placeholder="ej: 95.21">
                            </div>
                            </div>

                            <div class="row" style="padding-bottom: 15px;border-top: 1px solid #eee; padding-top: 15px;">
                                <div id="tablas_de_pago" class="col-md-12">
                                    <h5 style="display:inline; margin-right:10px;">Tablas de pago</h5>
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
                                        <i class="fa fa-fw fa-arrow-up"></i> ASOCIAR JUEGO
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- / PASO 2 | JUEGO -->

                  <!-- PASO 4 | FORMULA -->
                  <div class="seccion" id="secFormula">
                    <form id="frmFormula" name="frmFormula" class="form-horizontal" novalidate="">
                      <div class="row">
                        <div class="col-lg-12">
                          <h6>FÓRMULA ACTIVA</h6>
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
                                      <h6>AGREGAR FÓRMULA<i class="fas fa-fw fa-angle-down"></i></h6>
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
                        </div>
                      </div>
                    </div>
                    </form>
                  </div><!-- / PASO 5 | FORMULA -->

              </div> <!-- /Fin panel minimizable -->
            </div> <!-- Fin modal-header -->

            <div class="modal-footer">
              <i class="fa fa-fw fa-paperclip fa-2x" style="display:inline-block;color:#1DE9B6;position:relative; top:5px;"></i>
              <p id="maquinas_pendientes" style="display:inline-block; font-family:Roboto-Regular; font-size:18px; margin-right:20px;position:relative; top:1px;"> 3 PENDIENTES</p>
              <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">Crear MÁQUINA</button>
              <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
              <input id="id_movimiento" type="text" name="" value="" hidden="">
              <input type="hidden" id="id_maquina" value="0">
            </div>

        </div>
      </div>
  </div>


  <!-- ********************** Modal Carga Masiva ************************************* -->
  <div class="modal fade" id="modalCargaMasiva" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
           <div class="modal-content">
              <div class="modal-header" stylE="background: #6DC7BE','color: #000;">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title" style="color: #fff;">| NUEVA CARGA MASIVA</h3>
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
                          <!-- <h5>Buscar CSV</h5> -->
                          <h5>Archivo</h5>
                          <div class="zona-file">
                              <input id="cargaMasiva" data-borrado="false" type="file" accept="">
                          </div>
                      </div>
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
              <div class="modal-header" style="background-color:#ef3e42;color:white;">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3>ADVERTENCIA</h3>
              </div>

              <div  id="colapsado" class="collapse in">
              <div class="modal-body">
                <form id="frmEliminar" name="frmJuego" class="form-horizontal" novalidate="">
                    <div class="form-group error ">
                        <div id="mensajeEliminar" class="col-xs-12">
                          <strong>¿Seguro desea eliminar el MOVIMIENTO?</strong>
                        </div>
                    </div>
                </form>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-dangerEliminar confirmar" id="btn-eliminarModal" value="0">ELIMINAR</button>
                <button type="button" class="btn btn-default cancelar" data-dismiss="modal">CANCELAR</button>
              </div>
            </div>
          </div>
        </div>
  </div>

<table hidden>
  <tr id="filaEjemploMovimiento">
     <td class="col-xs-1 casino">CASINO</td>
     <td class="col-xs-1">
      <span class="nro_mov">9999999</span>
      <button type="button" class="btn btn-link ver_fiscalizaciones" title="VER FISCALIZACIONES" style="font-size: 74%;background-color: transparent !important;">
        <i class="fa fa-fw fa-search"></i>
      </button>
     </td>
     <td class="col-xs-2 fecha_mov" style="font-size: 100%">99 DIC 9999</td>
     <td class="col-xs-1 nro_exp_mov">9999-999-9</td>
     <td class="col-xs-2 islas_mov">9999-9999-...</td>
     <td class="col-xs-2 tipo_mov">TIPO9999</td>
     <td class="col-xs-1 icono_mov" style="text-align: center;">
       <i class="fas fa-fw fa-times" style="color: #EF5350;"></i>
     </td>
     <td class="col-xs-2 botones_mov">
        <button type="button" class="btn boton_nuevo" title="NUEVO">
          <i class="far fa-fw fa-file-alt"></i>
        </button>
        <button type="button" class="btn btn-success boton_cargar" title="CARGAR">
          <i class="fa fa-fw fa-plus"></i>
        </button>
        <button type="button" class="btn btn-success boton_fiscalizar" title="ENVIAR A FISCALIZAR">
          <i class="fa fa-fw fa-paper-plane"></i>
        </button>
        <button type="button" class="btn btn-success boton_validar" title="VALIDAR">
          <i class="fa fa-fw fa-check"></i>
        </button>
        <button type="button" class="btn print_mov" title="IMPRIMIR MOV">
          <i class="fas fa-fw fa-print"></i>
        </button>
        <button type="button" class="btn btn-danger baja_mov" title="BAJA MOV">
          <i class="fas fa-fw fa-trash"></i>
        </button>
     </td>
  </tr>
</table>


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="/js/divRelevamientoMovimiento.js?2" charset="utf-8"></script>
    <script src="/js/seccionMovimientosVista.js?1" charset="utf-8"></script>
    <script src="/js/seccionMovimientos-Egreso.js?1" charset="utf-8"></script>
    <script src="/js/seccionMovimientos-Ingreso.js" charset="utf-8"></script>
    <script src="/js/seccionMovimientos-Validar.js" charset="utf-8"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- JavaScript personalizado -->
    <script>
    //Stubs para seccionMaquinas-Modal, porque no se usan/envian cuando se hace el alta estos datos
    function limpiarModalGliSoft(){};
    function limpiarModalGliHard(){};
    function habilitarControlesGliSoft(){};
    function habilitarControlesGliHard(){};
    function ocultarAlertasGliSoft(){};
    function ocultarAlertasGliHard(){};
    function obtenerDatosGliHard(){
      return {id_gli_hard : null,nro_certificado: null,file: null,nombre_archivo: null};
    };
    </script>
    <script src="/js/seccionMaquinas-Formula.js?1" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-JuegoNuevo.js?3" charset="utf-8"></script>
    <script src="/js/seccionMaquina-IslaNuevo.js?1" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-Modal.js?1" charset="utf-8"></script>
    <script src="/js/seccionMaquinas.js?2" charset="utf-8"></script>
    <script src="/js/utils.js" type="text/javascript"></script>

    <script src="js/inputSpinner.js" type="text/javascript"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>

    <!-- Custom input Bootstrap -->
    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    @endsection
