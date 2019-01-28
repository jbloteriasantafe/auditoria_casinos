<?php use App\Http\Controllers\UsuarioController;

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
                      <option value="" selected>- Seleccione tipo movimiento -</option>
                      @foreach ($tiposMovimientos as $tipoMovimiento)
                      <option value="{{$tipoMovimiento->id_tipo_movimiento}}">{{$tipoMovimiento->descripcion}}</option>
                      @endforeach
                      <option value="0" >- Todos los movimientos -</option>
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
                      @foreach ($casinos as $casino)
                      <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                      @endforeach
                    </select>
                  </div>

                </div> <!-- row / formulario -->
                <br>
                <div class="row">
                  <div class="col-md-12">
                    <center>
                      <button id="btn-buscarMovimiento" class="btn btn-infoBuscar" type="button" name="button">
                        <i class="fa fa-fw fa-search"></i> BUSCAR
                      </button>
                    </center>
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
                            <th class="col-md-2" value="log_movimiento.fecha" estado="">FECHA <i class="fa fa-sort"></i></th>
                            <th class="col-md-2" value="expediente.nro_exp_org" estado="">EXPEDIENTE <i class="fa fa-sort"></i></th>
                            <th class="col-md-2" value="log_movimiento.islas" estado="">ISLAS<i class="fa fa-sort"></i></th>
                            <th class="col-md-2" value="tipo_movimiento" estado="">TIPO MOVIMIENTO <i class="fa fa-sort"></i></th>
                            <th class="col-md-1" value="validado" estado="">VALIDADO</th>
                            <th class="col-md-3" >ACCIÓN </th>

                          </tr>
                        </thead>
                          <tbody  id='cuerpoTabla' style="height: 380px;">
                        </tbody>
                      </table>
                      <!--Comienzo indices paginacion-->
                      <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                      </div>
                    </div>
                  </div>
            </div> <!-- .row / TABLA -->
          </div>
      <div class="col-xl-3">

              <!-- Botón nuevo Ingreso -->
          <div class="row">
              <div class="col-lg-12">
                 <a href="" id="btn-nuevo-movimiento" style="text-decoration: none;">
                  <div class="panel panel-default panelBotonNuevo">
                      <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
                      <div class="backgroundNuevo"></div>
                      <div class="row">
                          <div class="col-xs-12">
                            <center>
                                <h5 class="txtLogo">+</h5>
                                <h4 class="txtNuevo">NUEVO MOVIMIENTO</h4>
                            </center>
                          </div>
                      </div>
                  </div>
                 </a>
              </div>
          </div> <!-- .row -->

      </div> <!-- col-xl-3 | COLUMNA DERECHA - BOTONES -->



   <!-- ********************* MODALES ************************************ -->

<!-- **************Modal para ingresos*****************************-->
<div class="modal fade" id="modalLogMovimiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                        <div class="col-md-6">
                              <center>
                                <h5>CARGA MANUAL</h5>
                                <input id="tipoManual" type="radio" name="carga" value="1">
                              </center>
                        </div>
                        <div class="col-md-6">
                              <center>
                                <h5>CARGA MASIVA</h5>
                                <input id="tipoCargaSel" type="radio" name="carga" value="2" >
                              </center>
                        </div>
                    </div>
                      <br>
                      <div id="cantMaqCargar" class="row">
                            <div class="col-md-8 col-md-offset-2">
                                <center>
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
                                </center>
                            </div>
                      </div>

                      <br><br>



                      <br><br>
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
                           <option class="default1" value="3">- Seleccione el casino -</option>
                         </select>
                      </div>
                      <div class="col-xs-6">
                        <h5>TIPO MOVIMIENTO</h5>
                        <select id="tipo_movimiento_nuevo" class="form-control" name="">
                          <option class="default2" value="7">- Seleccione el Tipo -</option>
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


<!-- ***************Modal de ENVIAR A FISCALIZAR INGRESOS***************-->
<div class="modal fade" id="modalEnviarFiscalizarIngreso" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 70%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel"></h3>
      </div>

      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">

          <div class="row">
            <div class="row">

              <div class="col-md-12" align="center">
                <h6>FECHA: </h6>
                <div class="form-group" style="padding-left:80px;padding-right:80px">
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
          <input id="id_log_movimiento" type="text" name="" value="" hidden>
          <button id="btn-enviar-ingreso" type="button" class="btn btn-guardar btn-successAceptar" >ENVIAR A FISCALIZAR</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>

      </div> <!-- collapsado -->
    </div> <!-- modal-content -->
  </div> <!-- modal-dialog -->
</div> <!-- modal -->


  <!-- *************Modal para otros movimientos: egreso ***********************************-->
<div class="modal fade" id="modalLogMovimiento2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 70%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">CARGAR MÁQUINAS</h3>
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
            <div class="col-md-6" >
              <h6>Fecha: </h6>
              <div class="form-group">
                <div class='input-group date' id='dtpFechaEgreso' data-link-field="fecha_cierre" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" placeholder="Fecha a fiscalizar" id="B_fecha_egreso" value=""/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <br>
          </div> <!-- FIN ROW 1 -->
          <br>
          <div class="row"> <!-- ROW 2 -->
            <div class="col-md=6">
              <h6>MÁQUINAS SELECCIONADAS</h6>
              <br>
              <table id="tablaMaquinasSeleccionadas" class="table">
                <thead>
                  <tr>
                    <th width="10%">NÚMERO</th>
                    <th width="20%">MARCA</th>
                    <th width="10%">MODELO</th>
                    <th id="isla_layout" width="10%">ISLA</th>
                    <th width="30%">JUEGO</th>
                    <th width="10%">SERIE</th>
                    <th width="10%">ACCIÓN</th>
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
        <input id="tipo_movi" type="text" name="" value="" hidden="">
        <input id="mov" type="text" name="" value="" hidden="">
        <button id="btn-pausar" type="button" class="btn btn-default" data-dismiss="modal">PAUSAR CARGA</button>
        <button id="boton-cancelar" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        <button id="btn-enviar-egreso" type="button" class="btn btn-default" value="" >ENVIAR A FISCALIZAR</button>

        <button id="btn-enviar-toma2" type="button" class="btn btn-default" value="" >ENVIAR A FISCALIZAR</button>

        <div id="mensajeFiscalizacionError" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">No fue posible enviar a fiscalizar las máquinas cargadas.</span>
        </div> <!-- mensaje -->

      </div> <!-- modal footer -->
    </div> <!-- modal-content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->


<!-- **************modal Para DENOMINACION ************************-->
<div class="modal fade" id="modalDenominacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 70%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #ff9d2d;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel"></h3>
      </div>

      <div class="modal-body" style="">
         <div  id="colapsado" class="collapse in">

           <div class="row"> <!-- ROW 1 -->
             <div class="col-xl-12">
               <div class="row">

                   <div class="col-md-4">
                      <h6>Agregar Máquina</h6>
                      <div class="row">

                         <div class="input-group">
                           <input id="inputMaq2" class="form-control" type="text" value="" autocomplete="off" placeholder="Nro. Admin">
                           <span class="input-group-btn">
                             <button id="agregarMaq2" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                           </span>
                         </div>
                       </div>
                     </div>

                   <div class="col-md-4" id="busqIsla">
                     <h6>Seleccionar Isla</h6>
                     <div class="row">

                       <div class="input-group">
                         <input id="inputIslaDen" class="form-control" type="text" value="" autocomplete="off" placeholder="Nro Isla">
                         <span class="input-group-btn">
                           <button id="agregarIslaDen" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                         </span>
                       </div>

                    </div>
                   </div>

                   <div class="col-md-4" id="busqSector">
                     <h6>Seleccionar Sector</h6>
                     <div class="row">

                       <div class="input-group">
                         <input id="inputSectorDen" class="form-control" type="text" value="" autocomplete="off" placeholder="Nro Sector">
                         <span class="input-group-btn">
                           <button id="agregarSectorDen" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                         </span>
                       </div>

                     </div>
                   </div>
                   <div class="col-md-4" >
                     <h6>Fecha: </h6>
                     <div class="form-group">
                       <div class='input-group date' id='dtpFechaMDenom' data-link-field="fecha_cierre" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                         <input type='text' class="form-control" placeholder="Fecha a fiscalizar" id="B_fecha_denom" value=""/>
                         <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                         <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                       </div>
                     </div>
                   </div>
               </div> <!-- row -->

             </div>
           </div>


          <div class="row">

            <div class="row">
              <br>

              <div class="col-md-4">
                <h5 id="nuevaDen" hidden="" >Nueva Denominación: </h5> <input class="form-control" type="text" name="" value="" id="denom_comun" hidden>
              </div>
              <div class="col-md-4">
                  <h5 id="nuevaUni" hidden="">Unidad de Medida: </h5>
                  <select class="form-control" name="" id="unidad_comun">
                     <option value="0">- Seleccione unidad -</option>
                     <option value="1">CRED</option>
                     <option value="2">PESOS</option>
                   </select>
              </div>
              <div class="col-md-4">
                <h5 id="aplicar" style="color:white !important;"  hidden>fgfdgfhgfh</h5>
                <button id="todosDen" class="btn btn-default" type="button" name="button" hidden="">
                  <i class="fa fa-fw fa-check"></i>Aplicar a todas
                </button>

              </div>

            </div>

            <div class="row">

              <div class="col-md-4">
              <h5 id="nuevaDev" hidden="">Nueva Devolución: </h5> <input type="text" name="" value="" id="devol_comun" hidden>
              </div>

              <div class="col-md-4">
                  <h5 id="aplicar1" style="color:white !important;">fgfdgfhgfh </h5>
                  <button id="todosDev" class="btn btn-default" type="button" name="button" hidden="">
                    <i class="fa fa-fw fa-check"></i> Aplicar a todas
                  </button>
              </div>

            </div>

          </div>
          <br>

          <style media="screen">
              #btn-borrarTodo {
                  background-color: #ccc !important;
                  color:white;
                  font-family: Roboto-Condensed;
                  font-weight: bold;
              }
              #btn-borrarTodo:hover {
                  background-color: #EF5350 !important;
              }

              #tablaDenominacion tbody tr td:nth-child(4){
                text-align: center;
              }

          </style>

          <div class="row">
            <div class="col-xs-12">
              <table id="tablaDenominacion" class="table">
                <thead>
                  <tr>
                    <th class="col-xs-3" >MÁQUINAS A MODIFICAR</th>
                    <th id="segunda_columna" class="col-xs-4" text-align="center"></th>
                    <th id="tercer_columna" class="col-xs-4">UNIDAD DE MEDIDA</th>
                    <th class="col-xs-1">
                      <button id="btn-borrarTodo" type="button" name="button" class="btn btn-default" style="border-radius:0px;">
                          BORRAR TODOS
                      </button>

                    </th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
              </table>
            </div>
          </div> <!-- fin row -->

        </div> <!-- colapsado -->

      </div> <!-- modal body -->

        <div class="modal-footer">
          <input id="id_t_mov" type="text" name="" value="" hidden="">
          <input id="id_mov_denominacion" type="text" name="" value="" hidden="">
          <button id="btn-pausar-denom" type="button" class="btn btn-default" data-dismiss="modal">PAUSAR CARGA</button>
          <button id="btn-enviar-denom" type="button" class="btn btn-default" value="" >ENVIAR A FISCALIZAR</button>
          <button id="boton-cancelar-denom" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>

          <div id="mensajeFiscalizacionError2" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">No fue posible enviar a fiscalizar las máquinas cargadas.</span>
          </div> <!-- mensaje -->
        </div> <!-- modal footer -->

      </div> <!-- modal content -->
    </div> <!-- modal dialog -->
</div> <!-- modal fade -->


<!-- ************************modal eliminar mtm ******************************-->
<div class="modal fade" id="modalBajaMTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 70%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color:#D50000">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">MOVIMIENTOS</h3>
      </div>

      <div class="modal-body" style="">
        <div  id="colapsado" class="collapse in">

          <div class="row"> <!-- ROW 1 -->
            <div class="col-md-8">
              <h5>Agregar Máquina</h5>

              <div class="row">
                <div class="input-group lista-datos-group">
                  <input id="inputMaq3" class="form-control" type="text" value="" autocomplete="off">
                  <span class="input-group-btn">
                    <button id="agregarMaqBaja" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                  </span>
                </div>
              </div>
            </div>
            <br>
          </div> <!-- FIN ROW 1 -->

          <div class="row"> <!-- ROW 2 -->
            <div class="col-md=6">
              <h6>MÁQUINAS SELECCIONADAS</h6>
              <table id="tablaBajaMTM" class="table">
                <thead>
                  <tr>
                    <th width="20%">NÚMERO</th>
                    <th width="30%">MARCA</th>
                    <th width="30%">MODELO</th>
                    <th width="20%">ACCIÓN</th>
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
        <input id="movimId" type="text" name="" value="" hidden="">
        <input id="tipoMovBaja" type="text" name="" value="" hidden="">
        <button id="btn-baja" type="button" class="btn btn-default" value="" >ELIMINAR</button>
        <button id="boton-cancelar-baja" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>

        <div id="mensajeBaja" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
          <br>
          <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">No fue posible enviar a fiscalizar las máquinas cargadas.</span>
        </div> <!-- mensaje -->

      </div> <!-- modal footer -->

    </div> <!-- modal content -->
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

            <div id="columnaMaq" class="col-md-2" style="border-left:2px solid #4FC3F7; border-right:2px solid #4FC3F7;" hidden>
              <h6><b>MÁQUINAS</b></h6>
              <table id="tablaMaquinasFiscalizacion" class="table">
                <thead>
                  <tr>
                    <th> </th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div> <!-- maquinas -->

            <div id="columnaDetalle" class="col-md-8" hidden>
              <h6><b>DETALLES</b></h6>
              <div class="detalleMaq" >

                <div class="row" >

                  <div class="col-lg-3">
                    <h5>Fiscalizador Toma</h5>
                    <input id="f_tomaMov" type="text" class="form-control" readonly="readonly">
                    <br>
                  </div> <!-- Fisca toma -->

                  <div class="col-lg-3">
                    <h5>Fiscalizador Carga</h5>
                    <input id="f_cargaMov" type="text" class="form-control" readonly="readonly">
                    <br>
                  </div> <!-- fisca carga-->
                </div>

                <div class="row" >
                  <div class="col-lg-4">
                    <h5>Nro Admin.</h5>
                    <input id="nro_adminMov" type="text" class="form-control" readonly="readonly">
                    <br>
                  </div> <!-- nro admin -->
                  <div class="col-lg-4">
                    <h5>N° Isla</h5>
                    <input id="nro_islaMov" type="text" class="form-control" readonly="readonly" >
                    <br>
                  </div> <!-- nro_isla -->
                  <div class="col-lg-4">
                    <h5>N° Serie</h5>
                    <input id="nro_serieMov" type="text" class="form-control" readonly="readonly" >
                    <br>
                  </div> <!-- nro_serie -->
                </div> <!-- primer row -->

                <div class="row">
                  <div class="col-lg-6">
                    <h5>Marca</h5>
                    <input id="marcaMov" type="text" class="form-control" readonly="readonly">
                    <br>
                  </div> <!-- marca -->
                  <div class="col-lg-6">
                    <h5>Modelo</h5>
                    <input id="modeloMov" type="text" class="form-control" readonly="readonly">
                    <br>
                  </div> <!-- modelo -->
                  <div class="col-lg-6">
                    <h5>MAC</h5>
                    <input id="macMov" type="text" class="form-control" readonly="readonly">
                    <br>
                  </div> <!-- mac -->
                  <div class="col-lg-4">
                    <h5>ISLA</h5>
                    <input id="islaRelevadaMov" type="text" value="" class="form-control">
                    <br>
                  </div>

                  <div class="col-lg-4">
                    <h5>SECTOR</h5>
                    <input id="sectorRelevadoMov" type="text" value="" class="form-control">
                    <br>
                  </div> <!-- SECTOR -->
                </div> <!-- segundo row -->

                <div class="row">
                  <table id="tablaValidarIngreso" class="table">
                    <thead>
                      <tr>
                        <th class="col-xs-6"><h6><b>CONTADORES</b></h6></th>
                        <th id="toma_anterior" class="col-xs-3" hidden=""><h6>TOMA ANTERIOR</h6></th>
                        <th id="toma_nueva" class="col-xs-3" hidden=""><h6>TOMA ACTUAL</h6></th>
                        <th id="toma_check" class="col-xs-2" hidden=""><h6>CHECK</h6></th>
                        <th class="col-xs-2"></th>
                      </tr>
                    </thead>
                    <tbody>
                    </tbody>
                  </table>
                </div>  <!-- tercer row -->

                <div id="toma2">
                  <br>
                  <h6>TOMA ANTERIOR</h6>

                  <div class="row">
                    <div class="col-lg-4">
                      <h5>JUEGO</h5>
                      <input id="juego1" type="text" class="form-control" readonly="readonly">
                      <br>
                    </div>
                    <div class="col-lg-4">
                      <h5>APUESTA MÁX</h5>
                      <input id="apuesta1" type="text" class="form-control" readonly="readonly">
                    </div>
                    <div class="col-lg-4">
                      <h5>CANT LÍNEAS</h5>
                      <input id="cant_lineas1" type="text" class="form-control" readonly="readonly">
                    </div>
                  </div>  <!-- sexto row -->

                  <div class="row">
                    <div class="col-lg-4">
                      <h5>% DEVOLUCIÓN</h5>
                      <input id="devolucion1" type="text" class="form-control" readonly="readonly">
                    </div>
                    <div class="col-lg-4">
                      <h5>DENOMINACIÓN</h5>
                      <input id="denominacion1" type="text" class="form-control" readonly="readonly">
                    </div>
                    <div class="col-lg-4">
                      <h5>CANT CRÉDITOS</h5>
                      <input id="creditos1" type="text" class="form-control" readonly="readonly">
                    </div>
                  </div>  <!-- septimo row -->

                </div>

                <br>
                <h6>TOMA ACTUAL</h6>
                <div class="row">
                  <div class="col-lg-4">
                    <h5>JUEGO</h5>
                    <input id="juego" type="text" class="form-control" readonly="readonly">
                    <br>
                  </div>
                  <div class="col-lg-4">
                    <h5>APUESTA MÁX</h5>
                    <input id="apuesta" type="text" class="form-control" readonly="readonly">
                  </div>
                  <div class="col-lg-4">
                    <h5>CANT LÍNEAS</h5>
                    <input id="cant_lineas" type="text" class="form-control" readonly="readonly">
                  </div>
                </div> <!-- cuarto row -->

                <div class="row">
                  <div class="col-lg-4">
                    <h5>% DEVOLUCIÓN</h5>
                    <input id="devolucion" type="text" class="form-control" readonly="readonly">
                  </div>
                  <div class="col-lg-4">
                    <h5>DENOMINACIÓN</h5>
                    <input id="denominacion" type="text" class="form-control" readonly="readonly">
                  </div>
                  <div class="col-lg-4">
                    <h5>CANT CRÉDITOS</h5>
                    <input id="creditos" type="text" class="form-control" readonly="readonly">
                  </div>
                </div> <!-- quinto row -->


                <div class="row">
                  <div class="col-lg-12">
                    <h6>OBSERVACIONES:</h6>
                    <textarea id="observacionesToma" class="form-control" readonly="readonly" style="resize:vertical;"></textarea>
                  </div>
                </div>
              </div>
            </div> <!-- fin row de detalle -->
          </div>  <!-- fin row inicial -->

          <div class="modal-footer">
              <!-- INPUTS QUE ME SIRVEN PARA ENVIAR JSON EN EL POST DE VALIDAR -->
              <input id="id_log_movimiento" type="text" name="" value="" hidden>
              <input id="fecha_fiscalizacion" type="text" name="" value="" hidden>
              <input id="relevamiento" type="text" name="" value="" hidden>
              <input id="maquina" type="text" name="" value="" hidden>
              <button id="errorValidacion" type="button" class="btn btn-default error" >ERROR</button>
              <button id="enviarValidar" type="button" class="btn btn-default validar" value=""> VALIDAR </button>
              <button id="finalizarValidar" type="button" class="btn btn-default" value="" data-fiscalizacion="" hidden=> FINALIZAR VALIDACIÓN </button>


              <div id="mensajeErrorVal" hidden>
                <br>
                <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
                <br>
                <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;"></span>
              </div> <!-- mensaje -->
              <div id="mensajeExitoValidacion" hidden>
                <br>
                <span style="font-family:'Roboto-Black'; font-size:16px; color:#4CAF50 ;">EXITO</span>
                <br>
                <span  style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">La VALIDACIÓN se ha realizado correctamente</span>
              </div> <!-- mensaje -->
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
                <!-- <div class="row">
            <div class="col-md-8 col-md-offset-2">

                   <div class="row">
                        <div class="col-md-12">
                            <h4 id="mensaje3" style="color:#000000"></h4>
                            <ul id="lista2" style="color:#000000">

                            </ul>
                        </div>
                    </div> -->

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

              <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->

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
                          <i id="error_nav_progresivo" class="fa fa-times" style="color:#F44336;"></i>
                          <a href="" id="navProgresivo"><h4>PROGRESIVO</h4></a>
                    </div>
                    <div width="10%">
                          <i id="error_nav_soft" class="fa fa-times" style="color:#F44336;"></i>
                          <a href="" id="navSoft"><h4>GLI SOFT</h4></a>
                    </div>
                    <div width="10%">
                          <i id="error_nav_hard" class="fa fa-times" style="color:red; display:none;"></i>
                          <a href="" id="navHard"><h4>GLI HARD</h4></a>
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
                          <span id="alerta_gabinete" class="alertaSpan"></span>
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
                          <select class="form-control" id="juega_progresivo">
                            @foreach ($monedas as $moneda)
                            <option value="{{$moneda->id_tipo_moneda}}">{{$moneda->descripcion}}</option>
                            @endforeach
                          </select>
                        </div>

                      </div>
                      <br>
                      <div class="row">

                        <div class="col-lg-4">

                          <h5>Expedientes del GLI SOFT</h5>

                          <!-- <h5>Número de expediente</h5> -->
                          <div id="M_expediente" class="input-group triple-input">
                              <input id="M_nro_exp_org" style="width:30%; border-right:none;" type="text" placeholder="-----" maxlength="5" class="form-control" />
                              <input id="M_nro_exp_interno" style="width:50%;" type="text" placeholder="-------" maxlength="7" class="form-control" />
                              <input id="M_nro_exp_control" style="width:20%; border-left:none;" type="text" placeholder="-" maxlength="1" class="form-control" />
                          </div>
                          <!-- <ul id="listaExpedientes"></ul> -->

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
                        <!-- <div class="row">
                            <div class="col-md-12">
                              <h4>Isla Activa</h4>
                              <p id="noexiste_isla">La máquina no tiene un isla asociada.</p>
                            </div>
                        </div> -->

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
                                <h5>Sub Isla</h5>
                            </div>
                            <div class="col-xs-2">
                                <h5>Cant. Máquinas</h5>
                            </div>
                            <div class="col-xs-2">
                                <h5>Acciones</h5>
                            </div>
                        </div>
                        <!-- Informacion de isla seleccionadoa -->
                        <div id="islaSeleccionada" class="row">
                            <div class="col-xs-2">
                              <p id="casinoSeleccionado"></p>
                            </div>
                            <div class="col-xs-2">
                              <p id="sectorSeleccionado"></p>
                            </div>
                            <div class="col-xs-2">
                              <p id="nro_islaSeleccionado"></p>
                            </div>
                            <div class="col-xs-2">
                              <p id="codigoSeleccionado"></p>
                            </div>
                            <div class="col-xs-2">
                              <p id="cantidad_maquinasSeleccionado"></p>
                            </div>
                            <div id="accionesIslaSeleccionada" class="col-xs-2">
                            </div>
                        </div>
                    </div>

                    <!-- CREAR O BUSCAR Isla-->
                    <div id="agregarIsla" style="cursor:pointer;" data-toggle="collapse" data-target="#islaPlegado">
                        <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                            <div class="col-md-12" >
                                <h6 >AGREGAR ISLA<i class="fa fa-fw fa-angle-down"></i></h6>
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
                                              <th width="5%"></th>
                                              <th width="20%">NÚMERO</th>
                                              <th width="30%">MARCA</th>
                                              <th width="30%">MODELO</th>
                                              <th width="15%">ACCIÓN</th>
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
                                        <i class="fa fa-fw fa-arrow-up"></i> ASOCIAR JUEGO
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- / PASO 2 | JUEGO -->


                  <!-- PASO 3 | PROGRESIVO -->
                  <div class="seccion" id="secProgresivo">
                    <!-- <form id="frmProgresivo" name="frmProgresivo" class="form-horizontal" novalidate=""> -->
                      <div class="row">
                        <div class="col-lg-12">
                          <h6>PROGRESIVO ACTIVO</h6>
                          <div id="tablaProgresivoSeleccionado" class="row" style="margin-bottom: 15px;" hidden="true">
                              <div class="col-xs-2 col-xs-offset-1">
                                  <h5>Progresivo seleccionada</h5>
                                  <p id="progresivoSeleccionado">No existe progresivo seleccionada.</p>
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
                                  <h5>Acción</h5>
                                  <button id="borrarProgresivoSeleccionado" type="button" class="btn btn-danger borrarFila"  name="button"><i class="fa fa-trash"></i></button>
                              </div>
                          </div>
                          <div class="row" id="noexiste_progresivo">
                            <p  style="display:block;margin-top:25px; margin-bottom:20px;"><i class="fas fa-times aviso"></i> No existe ningún progresivo activo.</p>
                          </div>
                          <div class="row">
                            <div class="col-lg-10 col-lg-offset-1">
                              <table id="tablaNivelesSeleccionados" class="" hidden="true">
                                  <thead>
                                    <th class="col-lg-2">Nro Nivel</th>
                                    <th class="col-lg-2">Nombre nivel</th>
                                    <th class="col-lg-2">% Oculto</th>
                                    <th class="col-lg-2">% Visible</th>
                                    <th class="col-lg-2">Base</th>
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
                                      <h6>AGREGAR PROGRESIVO<i class="fas fa-fw fa-angle-down"></i></h6>
                                  </div>
                              </div>
                          </div>
                          <div id="collapseAgregarProgresivo" class="collapse">
                            <div class="row">
                              <div class="col-md-6 col-lg-6">
                                <h5>Nombre Progresivo</h5>
                                <input id="nombre_progresivo" type="text" class="form-control" placeholder="Nombre Progresivo" autocomplete="off">
                                <br>
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

                                  <h5>Buscador Islas <i class="fas fa-fw fa-search"></i></h5>
                                  <div class="row">
                                    <div class='input-group' id='groupIsla'>
                                        <input class="form-control buscadorIsla" type="text" list="datalist_islas" autocomplete="off" placeholder="Nro Isla" id=""  />
                                        <datalist id="datalist_islas"> </datalist>
                                        <span class="input-group-addon cancelarIsla" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                        <span class="input-group-addon agregarIsla" style="cursor:pointer;"><i class="fa fa-plus"></i></span>
                                    </div>

                                  </div>
                                  <br>
                                  <h5>Buscador Maquinas <i class="fas fa-fw fa-search"></i></h5>
                                  <div class="row">
                                    <div class='input-group' id='groupMaquina'>
                                        <input data-maquina=""  class="form-control buscadorMaquina" type="text" list="datalist_maquinas" autocomplete="off" placeholder="Nro Admin" id=""/>
                                        <datalist id="datalist_maquinas"> </datalist>
                                        <span class="input-group-addon cancelarMaquina" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                        <span class="input-group-addon agregarMaquina" style="cursor:pointer;"><i class="fa fa-plus"></i></span>
                                    </div>
                                  </div>

                                </div>

                                <div id="" class="col-md-6 col-lg-6">
                                  <h5>Maquinas Seleccionadas:</h5>
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
                                <i class="fas fa-fw fa-times"></i> LIMPIAR CAMPOS
                            </button>
                            <button id="btn-agregarProgresivo" class="btn btn-successAceptar" type="button" name="button">
                                <i class="fas fa-fw fa-arrow-up"></i> AGREGAR PROGRESIVO
                            </button>
                            <button id="btn-crearProgresivo" class="btn btn-successAceptar" type="button" name="button">
                                <i class="fas fa-fw fa-plus"></i> CREAR PROGRESIVO
                            </button>

                            </div>
                          </div>
                      </div>
                    <!-- </form> -->
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
                                        <i class="fas fa-fw fa-trash"></i>
                                      </button>
                                    </td>
                                  </tr>

                                </tbody>
                              </table>

                              <div class="zona-file" hidden>
                                <!-- <input id="muestraArchivoSoft" type="file" name="" value=""> -->
                              </div>

                              <p id="noexiste_soft" style="display:block;margin-top:30px; margin-bottom:20px;"><i class="fas fa-times aviso"></i> La máquina no contiene certificado de GLI Software.</p>
                            </div>
                        </div>

                    </div>


                    <!-- CREAR O BUSCAR GLI soft-->
                    <div id="agregarSoft" style="cursor:pointer;" data-toggle="collapse" data-target="#softPlegado">
                        <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                            <div class="col-md-12">
                                <h6>AGREGAR GLI SOFTWARE<i class="fas fa-fw fa-angle-down"></i></h6>
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
                                    <i class="fas fa-fw fa-times"></i> LIMPIAR CAMPOS
                                </button>
                                <button id="btn-crearSoft" class="btn btn-successAceptar" type="button" name="button">
                                    <i class="fas fa-fw fa-plus"></i> CREAR GLI SOFTWARE
                                </button>
                                <button id="btn-agregarSoftLista" class="btn btn-successAceptar" type="button" name="button">
                                    <i class="fas fa-fw fa-arrow-up"></i> AGREGAR GLI SOFTWARE
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
                                        <i class="fas fa-fw fa-trash"></i>
                                      </button>
                                    </td>
                                  </tr>

                                </tbody>
                              </table>

                              <div class="zona-file" hidden>
                                <!-- <input id="muestraArchivoSoft" type="file" name="" value=""> -->
                              </div>

                              <p id="noexiste_hard" style="display:block;margin-top:30px; margin-bottom:20px;">
                                <i class="fas fa-times aviso"></i> La máquina no contiene certificado de GLI Hardware.</p>
                            </div>
                        </div>

                    </div>

                    <!-- CREAR O BUSCAR GLI HARD-->
                    <div id="agregarHard" style="cursor:pointer;" data-toggle="collapse" data-target="#hardPlegado">
                        <div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">
                            <div class="col-md-12">
                                <h6>AGREGAR GLI HARDWARE<i class="fas fa-fw fa-angle-down"></i></h6>
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
                                    <i class="fas fa-fw fa-times"></i> LIMPIAR CAMPOS
                                </button>
                                <button id="btn-crearHard" class="btn btn-successAceptar" type="button" name="button">
                                    <i class="fas fa-fw fa-plus"></i> CREAR GLI HARDWARE
                                </button>
                                <button id="btn-agregarHardLista" class="btn btn-successAceptar" type="button" name="button">
                                    <i class="fas fa-fw fa-arrow-up"></i> AGREGAR GLI HARDWARE
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

                          <!-- <input id="cargaMasiva" type="file" name="Archivo de Máquinas" accept=""> -->
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


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="/js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="/js/seccionMovimientosVista.js" charset="utf-8"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- JavaScript personalizado -->
    <script src="/js/seccionMaquinas-Formula.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-JuegoNuevo.js" charset="utf-8"></script>
    <!-- <script src="/js/seccionMaquinas-GliSoft.js" charset="utf-8"></script> -->
    <script src="/js/seccionMaquinas-GliSoftNuevo.js" charset="utf-8"></script>
    <script src="/js/seccionMaquina-IslaNuevo.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-GliHardNuevo.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-Progresivo.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas-Modal.js" charset="utf-8"></script>
    <script src="/js/seccionMaquinas.js" charset="utf-8"></script>

    <script src="js/inputSpinner.js" type="text/javascript"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>

    <!-- Custom input Bootstrap -->
    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    @endsection
