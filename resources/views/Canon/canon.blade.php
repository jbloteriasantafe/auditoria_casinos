@extends('includes.dashboard')
@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/js/jquery-ui-1.12.1.custom/jquery-ui.css">
<link rel="stylesheet" href="/css/paginacion.css">
<style>
.headerGrisVB{
  text-align:center !important;
  font-weight:bold;
  font-size:13px !important;
  background-color:#E0E0E0;
  border-right: 1px solid #aaa !important;
}
.headerEuroVB{
  text-align:center !important;
  font-weight:bold;
  font-size:13px !important;
  background-color:#81B0C7;
  border-right: 1px solid #aaa !important;
}
.headerDolarVB{
  text-align:center !important;
  font-weight:bold;
  font-size:13px !important;
  background-color:#81C784;
  border-right: 1px solid #aaa !important;;
}
</style>
@endsection
@section('contenidoVista')

<div class="col-lg-12 tab_content" id="pant_canon_pagos" hidden="true">

<div class="row">
  <div class="col-md-12">
    <a href="" id="pagoCanon" dusk="btn-nuevo" style="text-decoration: none;">
      <div class="panel panel-default panelBotonNuevo">
        <center><img class="imgNuevo" src="/img/logos/informes_white.png"><center>
          <div class="backgroundNuevo"></div>
          <div class="row">
            <div class="col-xs-12">
              <center>
                <h5 class="txtLogo">+</h5>
                <h4 class="txtNuevo">REGISTRAR NUEVO PAGO<h4>
              </center>
            </div>
          </div>
        </div>
      </a>
  </div>
</div>

<div class="row" style="text-align:center">
  <div class="col-md-12">

  <div class="panel panel-default">
    <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros2" style="cursor: pointer; background-color:#ccc !important;">
      <h4>VALORES BASE ORIGINALES<i class="fa fa-fw fa-angle-down"></i></h4>
    </div>
    <div id="collapseFiltros2" class="panel-collapse collapse">
      <div class="panel-body">
        <div class="col-xs-6" style="text-align:center !important">
          <h5>Casino</h5>
          <select class="form-control" name="" id="verDatosCanon" >
            <option value="0" selected>- Seleccione -</option>
            @foreach ($casinos as $cas)
            <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-xs-6">
          <button type="button" class="btn btn-infoBuscar" style="margin-top:30px !important;" id="buscarDatos" name="button">BUSCAR</button>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

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
              <h5>Fecha de Pago</h5>
              <div class="form-group">
                <div class='input-group date' id='dtpFechaFiltro' data-link-field="fecha_filtro" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                  <input type='text' class="form-control" id="B_fecha_filtro" value="" placeholder="aaaa-mm-dd"/>
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
              <h5>Mes</h5>
              <select class="form-control" name="" id="mesFiltro">
                <option value="0" selected class="default">Todos los Meses</option>
              </select>
            </div>
            <br>
            <div class="col-xs-3" style="padding-top:20px;">
              <button id="btn-buscar-pagos" class="btn btn-infoBuscar" type="button" name="button" style="margin-top:10px">
                <i class="fa fa-fw fa-search"></i> BUSCAR
              </button>
            </div>

          </div> <!-- row / botón buscar -->
          <span class="help-block" style="color: #0D47A1 !important;float:left; font-size:12px !important;padding-left:5px !important"><i>*Debe seleccionar un Casino para que se carguen los Meses de Pago</i></span>

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
        <h4>Pagos de canon registrados</h4>
      </div>
      <div class="panel-body">
        <table id="tablaInicial" class="table table-fixed tablesorter ">
          <thead>
            <tr align="center" >
              <th class="col-xs-2" value="DIFM.anio" style="text-align:center !important;">AÑO<i class="fas fa-fw fa-sort"></i></th>
              <th class="col-xs-2 " value="DIFM.mes" style="14px;text-align:center !important;">MES<i class="fas fa-fw fa-sort"></th>
              <th class="col-xs-2" value="casino.nombre" style="text-align:center !important;">CASINO<i class="fas fa-fw fa-sort"></th>
              <th class="col-xs-2" style="text-align:center !important;">Rdo Bruto</th>
              <th class="col-xs-1" style="text-align:center !important;">$/EUR</th>
              <th class="col-xs-1" style="text-align:center !important;">$/USD</th>
              <th class="col-xs-2" style="text-align:center !important;"></th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
        <div class="table table-responsive" id="tablaInicio"  style="display:none">
          <table class="table" >
            <tbody >
              <tr id="clonartinicial" class="filaClone"  style="display:none">
                <td class="col-xs-2 anioInicio" style="text-align:center !important"></td>
                <td class="col-xs-2 mesInicio" style="text-align:center !important"></td>
                <td class="col-xs-2 casinoInicio" style="text-align:center !important"></td>
                <td class="col-xs-2 montoInicio" style="text-align:center !important"></td>
                <td class="col-xs-1 euroInicio" style="text-align:center !important"></td>
                <td class="col-xs-1 dolarInicio" style="text-align:center !important"></td>
                <td class="col-xs-2" style="text-align:center !important">
                  <button type="button" name="button" class="btn btn-success modificarPago"><i class="fas fa-fw fa-pencil-alt "></i> </button>
                  <button type="button" name="button" class="btn btn-success eliminarPago"><i class="fas fa-fw fa-trash-alt "></i> </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
      </div>
    </div>
    </div>
  </div>
</div>

<!-- pestaña de actualización -->
<div class="col-lg-12 tab_content" id="pant_canon_valores" hidden="true">
  <div class="panel panel-default">
    <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros3" style="cursor: pointer; background-color:#ccc !important;">
      <h4>SELECCIONE EL CASINO Y EL PERIODO<i class="fa fa-fw fa-angle-down"></i></h4>
    </div>
    <div id="collapseFiltros3" class="panel-collapse collapse">
      <div class="panel-body">
        <div class="row">
          <div class="col-xs-3" style="text-align:center !important">
            <h5>Casino</h5>
            <select class="form-control" name="" id="selectActualizacion" >
              <option value="0" selected>- Seleccione -</option>
              @foreach ($casinos as $cas)
              <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
              @endforeach
            </select>
          </div>
          <div class="col-xs-3">
            <h5>PERÍODO</h5>
            <select class="form-control" name="" id="periodo">
            </select>
          </div>
          <div class="col-xs-3">
            <button type="button" class="btn btn-infoBuscar" style="margin-top:30px !important;" id="buscarActualizar" name="button">BUSCAR</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="divBaseCanon" class="row" hidden="true">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4>DATOS REGISTRADOS Y DERIVADOS</h4>
        </div>
          <div class="panel-body">
          <div class="row">
            <div id="mensajeErrorInforme" hidden>
              <br>
              <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
              <br>
              <span class="msjtext" style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Aún no hay Informes generados para las fechas y el casino ingresados.</span>
            </div> <!-- mensaje -->
          </div>
          <div id="tablasMontosVB" class="row" hidden="true">
            <div id="tablaBaseCanon" class="row">
              <table class="col-xs-12 table table-bordered" id="tablaVB" style="padding:0px !important">
                <thead>
                  <tr>
                    <td class="headerGrisVB">MONEDA</td>
                    <td class="headerGrisVB">VALOR BASE ANTERIOR</td>
                    <td class="headerGrisVB">VARIACION</td>
                    <td class="headerGrisVB">VALOR BASE NUEVO</td>
                    <td class="headerGrisVB">CANON ANTERIOR</td>
                    <td class="headerGrisVB">CANON NUEVO</td>
                  </tr>
                </thead>
                <tbody>
                  <tr id="filaEuroBaseCanon">
                    <td class="headerEuroVB">EURO</td>
                    <td class="base" style="text-align:center !important;">213</td>
                    <td class="variacion" style="text-align:center !important;">XX%</td>
                    <td class="baseNuevo" style="text-align:center !important;">213</td>
                    <td class="canon" style="text-align:center !important;">213</td>
                    <td class="canonNuevo" style="text-align:center !important;">213</td>
                  </tr>
                  <tr id="filaEuroBaseCanon">
                    <td class="headerDolarVB">DOLAR</td>
                    <td class="base" style="text-align:center !important;">213</td>
                    <td class="variacion" style="text-align:center !important;">XX%</td>
                    <td class="baseNuevo" style="text-align:center !important;">213</td>
                    <td class="canon" style="text-align:center !important;">213</td>
                    <td class="canonNuevo" style="text-align:center !important;">213</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="row">
              <table class="col-xs-12 table table-bordered" id="tablaEuro" style="padding:0px !important">
                <thead>
                  <tr>
                    <th class="casinoInformeFinal" colspan="8" style="text-align: center;color: darkblue !important;">EURO</th>
                  </tr>
                  <tr>
                    <td class="headerGrisVB">Meses</td>
                    <td class="rdo1 headerGrisVB">Rdo. Bruto</td>
                    <td class="rdo2 headerGrisVB">Rdo. Bruto</td>
                    <td class="cotizacion1 headerEuroVB">Cot. Euro</td>
                    <td class="cotizacion2 headerEuroVB">Cot. Euro</td>
                    <td class="valor2 headerEuroVB">Euros</td>
                    <td class="valor2 headerEuroVB">Euros</td>
                    <td class="headerEuroVB">Variación %</td>
                  </tr>
                </thead>
                <tbody width="auto">
                </tbody>
              </table>
            </div>
            <div class="row">
              <table class="col-xs-12 table table-bordered" id="tablaDolar" style="padding:0px !important">
                <thead style="border-color:#aaa !important">
                  <tr>
                    <th class="casinoInformeFinal2" colspan="8" style="text-align: center;color: green !important;">DÓLAR</th>
                  </tr>
                  <tr>
                    <td class="headerGrisVB">Meses</td>
                    <td class="rdo1 headerGrisVB" >Rdo. Bruto</td>
                    <td class="rdo2 headerGrisVB" >Rdo. Bruto</td>
                    <td class="cotizacion1 headerDolarVB">Cot. Dólar</td>
                    <td class="cotizacion2 headerDolarVB">Cot. Dólar</td>
                    <td class="valor1 headerDolarVB">Dólares</td>
                    <td class="valor2 headerDolarVB">Dólares</td>
                    <td class="headerDolarVB">Variación %</td>
                  </tr>
                </thead>
                <tbody width="auto">
                </tbody>
              </table>
            </div>
            <table hidden>
              <tbody>
                <tr class="filaClone" id="clonarT" style="display:none; padding:0px !important;">
                  <td class="mesT" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="rdo1T" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="rdo2T" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="cot1T" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="cot2T" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="monto1T" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="monto2T" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="variacionT" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- modal para modificar DATOS DE CANON -->
<div class="modal fade" id="modalVerYModificar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"  data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width: 80%" >
    <div class="modal-content">
      <div class="modal-header" style="background-color:#0D47A1;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| VALOR BASE ORIGINAL</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <h6 style="font-size:17px;text-align:center !important;font-weight:bold;" id="casinoDatos"></h6>
            <h6 style="font-size:16px;text-align:center !important;" id="periodoValido">
              PERIODO DE VALIDEZ: <p style="color: rgb(13, 71, 161) !important; display: inline;">0</p>
            </h6>
          </div>
          <hr>
          <div class="row" id="nuevosValoresBaseCasino">
            <div class="row" >
              <div class="col-xs-12">
                <div class="col-xs-6">
                  <h6 style="font-size:16px">VALOR BASE EURO:</h6>
                  <input type="text" class="form-control" id="baseNuevoEuro" name="" value="">
                </div>
                <div class="col-xs-6">
                  <h6 style="font-size:16px">VALOR BASE DÓLAR:</h6>
                  <input type="text" class="form-control" id="baseNuevoDolar" name="" value="">
                </div>
              </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warningModificar" id="guardarModificacion" value="nuevo">GUARDAR</button>
        </div>
      </div>
    </div>
  </div>

<!-- modal para cargar pago -->
<div class="modal fade" id="modalPago" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"  data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg" style="width: 70% !important" >
    <div class="modal-content">
      <div class="modal-header" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-carga-cierre" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| CARGA DE PAGO</h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
            <div class="row" >
              <div class="col-xs-6">
              <h6 style="font-size:18px !important">CASINO</h6>
              <select class="form-control" name="" id="selectCasinoPago">
                <option value="" class="default1">- Seleccione un Casino -</option>
                @foreach ($casinos as $cas)
                <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                @endforeach
              </select>
            </div>
            </div>
            <br>
            <br>
            <div class="row desplegarPago" hidden="true" >
              <div class="row">
                <div class="col-xs-4">
                  <h5>Año inicio período</h5>
                  <div class="form-group">
                    <div class='input-group date' id='dtpFechaAnioInicio' data-link-field="fecha_filtro" data-date-format="yyyy" data-link-format="yyyy">
                      <input type='text' class="form-control" id="fechaAnioInicio" placeholder="aaaa" value=""/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                </div>
                <div class="col-xs-4">
                  <h6 style="font-size:16px !important;">MES</h6>
                  <select class="form-control" name="" id="selectMesPago">
                  </select>
                </div>
                <div class="col-xs-4">
                  <h6 style="font-size:16px !important;">FECHA DE PAGO</h6>
                  <div class="form-group">
                    <div class='input-group date' id='dtpFechaPago' data-link-field="fecha_pago" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                      <input type='text' class="form-control" id="fechaPago" value=""/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-xs-4">
                  <h6 style="font-size:16px !important;">COTIZACIÓN EURO</h6>
                  <input type="text" name="" value="" id="cotEuroPago" class="form-control">
                </div>
                <div class="col-xs-4">
                  <h6 style="font-size:16px !important;">COTIZACIÓN DOLAR</h6>
                  <input type="text" name="" value="" id="cotDolarPago" class="form-control">
                </div>
                <div class="col-xs-4">
                  <h6 style="font-size:16px !important;">MONTO EN PESOS</h6>
                  <input type="text" name="" value="" id="montoPago" class="form-control">
                </div>
              </div>
            </div>
        </div>
      </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="guardarPago" value="nuevo" hidden="true">GUARDAR</button>
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
  <script src="/js/paginacion.js" charset="utf-8"></script>

  <script src="js/Canon/canon.js" type="text/javascript" charset="utf-8"></script>

@endsection
