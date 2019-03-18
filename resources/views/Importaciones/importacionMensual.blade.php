@extends('includes.dashboard')
@extends('Importaciones.importacionDiaria')

@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/js/jquery-ui-1.12.1.custom/jquery-ui.css">
<link rel="stylesheet" href="/css/paginacion.css">
@endsection
@section('contenidoVista')

<!-- Modal Importacion Mensual-->
<div class="modal fade" id="modalImportacionMensual" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width: 60%">
         <div class="modal-content">
           <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be;">
             <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title">| IMPORTACIÓN INFORME MENSUAL DE MESAS</h3>
            </div>

            <div  id="colapsado" class="collapse in">

            <div class="modal-body modalCuerpo">
                    <!-- Estilos del mansaje de información -->
                    <style media="screen">
                      #mensajeInvalido i {
                        color: #FF5252;
                        position: relative;
                        top: -3px;
                        left: -10px;
                        transform: scale(2);
                      }
                      #mensajeInvalido h6 {
                        margin-left: 6px !important;
                        color: #FF1744;
                        display:inline;
                        font-size: 20px;
                        font-weight:bold !important; font-family:Roboto-Condensed !important
                      }
                      #mensajeInvalido p {
                        /*color: black;*/
                        display:inline;
                        font-size: 16px;
                        /*font-weight:bold !important; */
                        font-family:Roboto-Regular !important
                      }

                      #iconoMoneda {
                        transform: scale(1.2);
                      }
                  </style>

              <form id="frmImportacion"  class="form-horizontal" novalidate="">
                <div class="col-xs-4 rowFecha">
                  <h5>FECHA</h5>
                  <div class="form-group">
                    <div class='input-group date' id='dtpFechaImp' data-link-field="fecha_importacion" data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                      <input type='text' class="form-control" placeholder="Fecha de Importación" id="B_fecha_imp" value=""/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="fecha_importacion" value=""/>
                  </div>
                </div>
                <div class="col-xs-4 rowCasino">
                  <h5>CASINO</h5>
                  <select class="form-control" id="casinoSel">
                    <option value="0" selected>- Seleccione un Casino -</option>
                    <option value="3">ROSARIO</option>
                    <option value="2">SANTA FE</option>
                    <option value="1">MELINCUÉ</option>
                  </select>
                </div>
                <div class="col-xs-4 rowMoneda">
                  <h5>MONEDA</h5>
                  <select class="form-control" id="monedaSel">
                    <option value="0" selected>- Seleccione Moneda -</option>
                    <option value="1">PESOS</option>
                    <option value="2">DÓLARES</option>

                  </select>
                </div>
              </form>

              <div id="rowArchivo" class="row" style="">
                      <div class="col-xs-12">
                        <div class="zona-file">
                          <h5>ARCHIVO</h5>
                            <input id="archivoMes" data-borrado="false" type="file" name="" >
                            <br> <span id="alertaArchivo" class="alertaSpan"></span>
                        </div>
                      </div>
              </div>



              <div id="mensajeError" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                      <div class="col-md-12">
                          <h6>SE PRODUJO UN ERROR DE CONEXIÓN</h6>
                          <button id="btn-reintentarContador" class="btn btn-info" type="button" name="button">REINTENTAR IMPORTACIÓN</button>
                      </div>
                  </div>



              <div id="mensajeInvalido" class="row" style="margin-bottom:20px !important; margin-top: 20px !important;">
                        <div class="col-xs-12" align="center">
                            <i class="fa fa-fw fa-exclamation-triangle"></i>
                            <h6> ARCHIVO INCORRECTO</h6>
                        </div>
                        <br>
                        <br>
                        <div class="col-xs-12" align="center">
                            <p>Solo se aceptan archivos con extensión .csv o .txt</p>
                        </div>
                  </div>

                  <div class="loading" id="iconoCarga" style="text-align: center" hidden="true">
                    <img src="/img/ajax-loader(1).gif" alt="loading" />
                    <br>Un momento, por favor...
                  </div>

              <!-- <div id="iconoCarga" class="sk-folding-cube">
                  <div class="windows8">
                	<div class="wBall" id="wBall_1">
                		<div class="wInnerBall"></div>
                	</div>
                	<div class="wBall" id="wBall_2">
                		<div class="wInnerBall"></div>
                	</div>
                	<div class="wBall" id="wBall_3">
                		<div class="wInnerBall"></div>
                	</div>
                	<div class="wBall" id="wBall_4">
                		<div class="wInnerBall"></div>
                	</div>
                	<div class="wBall" id="wBall_5">
                		<div class="wInnerBall"></div>
                	</div>
                </div>
              </div> -->

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-successAceptar" id="btn-guardarMensual" value="nuevo"> SUBIR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal"> CANCELAR</button>
            </div>
          </div>
        </div>
      </div>
</div>

<!-- Modal ver detalles importados -->
<div class="modal fade" id="modalInfoMensual" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width:60%">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #4FC3F7;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| DETALLE IMPORTACIÓN DIARIA VALIDADA</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-xs-4">
              <h5>MES</h5>
              <input id="fechaImpM" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-4">
              <h5>CASINO</h5>
              <input id="casinoImpM" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-4">
              <h5>MONEDA</h5>
              <input id="monedaImpM" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">
            </div>

        </div>
        <br>
        <br>
        <div class="row">

            <div class="col-xs-12" >

              <table  style="border-collapse: collapse; table-layout:auto" align="center" class="table table-bordered" >
                  <thead>
                    <tr>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5  style="font-size: 15px; color:#000;text-align:center !important;">FECHA</h5>
                      </th>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 15px; color:#000;text-align:center !important;">DROP</h5>
                      </th>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 15px; color:#000;text-align:center !important;">UTILIDAD</h5>
                      </th>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;">
                        <h5 style="font-size: 15px; color:#000;text-align:center !important;">HOLD %</h5>
                      </th>
                    </tr>
                  </thead>
                  <tbody id="datosMensuales" >

                  </tbody>
                </table>
                <div class="table table-responsive" id="mostrarTablaVerMensual"  style="display:none;">

                  <table class="table" style="padding:0px !important">
                    <tr id="moldeInfoMensual" class="filaClone" >
                      <td class="col-xs-3 ver_fecha" style="padding:2px;text-align:center !important;"></td>
                      <td class="col-xs-3 ver_drop" style="padding:2px;text-align:center !important;"></td>
                      <td class="col-xs-3 ver_utilidad" style="padding:2px;text-align:center !important;"></td>
                      <td class="col-xs-3 ver_hold" style="padding:2px;text-align:center !important;"></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              </div>

          </div>
          <div class="row">
            <div class="col-md-12">
              <h5>OBSERVACIONES</h5>
              <textarea name="name" id="observacionesImpM" class="form-control col-xs-12"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          </div>
          <div id="mensajeErrorGral" hidden>
              <br>
              <div class="col-xs-12" style="display: inline-block;">
                  <i class="fa fa-fw fa-exclamation-triangle" style="color:#C62828;display: inline-block;" ></i>
                  <h6 style="display: inline-block;"> ARCHIVO INCORRECTO</h6>
              </div>
              <br>
              <p id="span" style="font-family:'Roboto-Regular'; font-size:16px; color:#555;"></p>
          </div> <!-- mensaje -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- modal Validar -->
<div class="modal fade" id="modalValidarMensual" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width:60%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">|  VALIDACIÓN IMPORTACIÓN MENSUAL</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <div class="col-xs-4">
              <h5>MES</h5>
              <input id="fechaValidar" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-4">
              <h5>CASINO</h5>
              <input id="casinoValidar" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">

            </div>
            <div class="col-xs-4">
              <h5>MONEDA</h5>
              <input id="monedaValidar" class="form-control" type="text" value=""  size="100" autocomplete="off" readonly="true">
            </div>

        </div>
        <br>
        <br>
        <div class="row">

            <div class="col-xs-12" >

                <table   style="border-collapse: collapse; table-layout:auto; overflow:scroll;" align="center" class=" table table-fixed"  >
                  <thead>
                    <tr>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5  style="font-size: 15px; color:#000;text-align:center !important;">FECHA</h5>
                      </th>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 15px; color:#000;text-align:center !important;">DROP</h5>
                      </th>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;  border-right:1px solid #ccc;">
                        <h5 style="font-size: 15px; color:#000;text-align:center !important;">UTILIDAD</h5>
                      </th>
                      <th class="col-xs-3" style="text-align:center !important;padding-bottom:8px;padding-top:8px;padding-left:8px;padding-right:8px;">
                        <h5 style="font-size: 15px; color:#000;text-align:center !important;">HOLD %</h5>
                      </th>
                    </tr>
                  </thead>
                  <tbody id="datosMensualesValidar" >

                  </tbody>
                </table>
                <table style="table-layout:auto" class="table table-fixed">
                  <tbody>
                    <tr id="moldeValidarMensual" class="filaClone" style="display:none">
                      <td class="col-xs-3 validar_fecha" style="text-align:center !important;"></td>
                      <td class="col-xs-3 validar_drop" style="text-align:center !important;"></td>
                      <td class="col-xs-3 validar_utilidad" style="text-align:center !important;"></td>
                      <td class="col-xs-3 validar_hold" style="text-align:center !important;"></td>
                    </tr>
                  </tbody>
                </table>
              </div>

          </div>
          <div class="row">
            <div class="col-md-12">
              <h5>OBSERVACIONES</h5>
              <textarea name="name" id="observacionesValidar" class="form-control col-xs-12"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="validarMes" value="" hidden="true">GUARDAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<meta name="_token" content="{!! csrf_token() !!}" />

@endsection


@section('scripts')

  <!-- JavaScript personalizado -->
  <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <script src="js/inputSpinner.js" type="text/javascript"></script>
  <script src="/js/lista-datos.js" type="text/javascript"></script>

  <script src="js/fileinput.min.js" type="text/javascript"></script>

  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>

  <script src="js/Importaciones/importacionMensual.js" charset="utf-8"></script>

  <script src="/js/paginacion.js" charset="utf-8"></script>
@endsection
