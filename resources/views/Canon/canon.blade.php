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
      <h4 >VER DATOS ACTUALES <i class="fa fa-fw fa-angle-down"></i></h4>
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
              <th class="col-xs-2 activa" value="DIFM.fecha_cobro" estado="desc" style="font-size:14px;text-align:center !important;">FECHA DE PAGO<i class="fas fa-fw fa-sort-down"></i></th>
              <th class="col-xs-1 " value="mes_casino.nro_mes" estado="desc" style="font-size:14px;text-align:center !important;">MES<i class="fas fa-fw fa-sort"></th>
              <th class="col-xs-2" value="casino.nombre" estado="desc" style="font-size:14px;text-align:center !important;">CASINO<i class="fas fa-fw fa-sort"></th>
              <th class="col-xs-2" style="font-size:14px; text-align:center !important;">COTIZACIÓN DOLAR</th>
              <th class="col-xs-2" style="font-size:14px; text-align:center !important;">COTIZACIÓN EURO</th>
              <th class="col-xs-2" style="font-size:14px; text-align:center !important;">IMPUESTOS</th>
              <th class="col-xs-1" style="font-size:14px; text-align:center !important;"></th>

            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
        <div class="table table-responsive" id="dd"  style="display:none">
          <table class="table" >
            <tbody >
              <tr id="clonartinicial" class="filaClone"  style="display:none">
                <td class="col-xs-2 fechaInicio" style="text-align:center !important"></td>
                <td class="col-xs-1 mesInicio" style="text-align:center !important"></td>
                <td class="col-xs-2 casinoInicio" style="text-align:center !important"></td>
                <td class="col-xs-2 dolarInicio" style="text-align:center !important"></td>
                <td class="col-xs-2 euroInicio" style="text-align:center !important"></td>
                <td class="col-xs-2 impInicio" style="text-align:center !important"></td>
                <td class="col-xs-1" style="text-align:center !important">
                  <button type="button" name="button" class="btn btn-success modificarPago"><i class="fas fa-fw fa-pencil-alt "></i> </button>
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
        <h4 >SELECCIONE FILTROS PARA MOSTRAR INFORMES CORRESPONDIENTES <i class="fa fa-fw fa-angle-down"></i></h4>
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

  <div class="row datosReg" hidden="true">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4>DATOS REGISTRADOS AÑOS INDICADOS</h4>
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

        <div class="row desplegarActualizar" hidden="true">
          <div class="row">
            <table class="col-xs-12 table table-bordered" id="anio1" style="padding:0px !important">
              <thead>
                  <th class="casinoInformeFinal" colspan="8"> <h6 style="font-weight:bold;text-align:center !important;font-size:17px !important;color:#000;">CASINO 2017/2018</h6> </th>
              </thead>
              <tbody width="auto">
                  <tr style="" class="default1">
                    <td  style="text-align:center !important;font-weight:bold;font-size:13px !important;background-color:#E0E0E0;border-right: 1px solid #aaa !important;border-left: 1px solid #aaa !important;">Meses</td>
                    <td class="rdo1" style="text-align:center !important;font-weight:bold;font-size:13px !important;background-color:#E0E0E0;border-right: 1px solid #aaa !important;">Rdo. Bruto</td>
                    <td class="rdo2" style="text-align:center !important;font-weight:bold;font-size:13px !important;background-color:#E0E0E0;border-right: 1px solid #aaa !important;">Rdo. Bruto</td>
                    <td class="cotizacion1" style="text-align:center !important;font-weight:bold;background-color:#81C784; font-size:13px !important;border-right: 1px solid #aaa !important;">Cot. Euro</td>
                    <td class="cotizacion2" style="text-align:center !important;font-weight:bold;background-color:#81C784; font-size:13px !important;border-right: 1px solid #aaa !important;">Cot. Euro</td>
                    <td class="valor1" style="text-align:center !important;font-weight:bold;background-color:#81C784;font-size:13px !important;border-right: 1px solid #aaa !important;">Euros</td>
                    <td class="valor2" style="text-align:center !important;font-weight:bold;background-color:#81C784;font-size:13px !important;border-right: 1px solid #aaa !important;">Euros</td>
                    <td style="text-align:center !important;font-weight:bold;background-color:#81C784;font-size:13px !important;border-right: 1px solid #aaa !important;">Variación Euro en %</td>
              </tbody>
            </table>
          </div>
          <div class="table table-responsive" id="mostrarTabla1"  style="display:none">
            <table class="table" style="padding:0px !important">
              <tbody >
                <tr class"filaClone" id="clonarT1" style="display:none; padding:0px !important;">
                  <td class="mest1" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="rdo1t1" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="rdo2t1" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="cotEuroT1" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="cotEuro2T1" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="euroT1" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="euro2T1" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="variacionET1" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="row">
            <table class="col-xs-12 table table-bordered" id="anio2" style="padding:0px !important">
              <thead style="border-color:#aaa !important">
                <th class=" casinoInformeFinal2" colspan="8"> <h6 style="font-weith:bold;text-align:center;font-size:17px !important;color:#000">CASINO DÓLARES</h6> </th>
              </thead>
              <tbody width="auto">
                <tr style="text-align:center !important" class="default2">
                  <td  style="font-weight:bold;font-size:13px !important;background-color:#E0E0E0;border-right: 1px solid #aaa !important;border-left: 1px solid #aaa !important;">Meses</td>
                  <td class="rdo1" style="font-weight:bold;font-size:13px !important;background-color:#E0E0E0;border-right: 1px solid #aaa !important;" >Rdo. Bruto</td>
                  <td class="rdo2" style="font-weight:bold;font-size:13px !important;background-color:#E0E0E0;border-right: 1px solid #aaa !important;" >Rdo. Bruto</td>
                  <td class="cotizacion1" style="font-weight:bold;background-color:#FFD54F; font-size:13px !important;border-right: 1px solid #aaa !important;">Cot. Dólar</td>
                  <td class="cotizacion2" style="font-weight:bold;background-color:#FFD54F; font-size:13px !important;border-right: 1px solid #aaa !important;">Cot. Dólar</td>
                  <td class="valor1" style="font-weight:bold;background-color:#FFD54F; font-size:13px !important;border-right: 1px solid #aaa !important;">Dólares</td>
                  <td class="valor2" style="font-weight:bold;background-color:#FFD54F; font-size:13px !important;border-right: 1px solid #aaa !important;">Dólares</td>
                  <td style="font-weight:bold;background-color:#FFD54F; font-size:13px !important;border-right: 1px solid #aaa !important;">Variación Dólar en %</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="row" style="float:right">
            <button type="button" id="actualizarCanon" style="font-weight:bold" class="btn btn-successAceptar" hidden="true">ACTUALIZAR CANON</button>
          </div>
          <div class="table table-responsive" id="mostrarTabla2"  style="display:none;">
            <table class="table" style="padding:0px !important">
              <tbody >
                <tr class"filaClone" id="clonarT2" style="display:none; padding:0px !important">
                  <td class="mesT2" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="rdo1T2" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="rdo2T2" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="cotDolar1T2" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="cotDolar2T2" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="dolar1T2" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="dolar2T2" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                  <td class="variacionDT2" style="padding:1px !important;text-align:center !important; font-size:12px !important"></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


  <div class="row datosActualizacion" hidden="true">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4>DATOS REGISTRADOS AÑOS INDICADOS</h4>
        </div>
        <div class="panel-body">
          <div class="row">
            <div id="mensajeErrorActualizacion" hidden>
              <br>
              <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
              <br>
              <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;" class="msj"></span>
            </div> <!-- mensaje -->
          </div>
          <div class="row ">
            <table class="table table-bordered" id="tablaActualizacion" style="border:1px solid #aaa !important">
              <thead style="background-color:#E0E0E0;font-weight:bold;font-size:16px !important;">
                <th style="background-color: #aaa;border-right: 1px solid #000 !important;border-left: 1px solid #000 !important;border-bottom: 1px solid #000 !important;border-top: 1px solid #000 !important;">
                  <h6 style="color:white !important;text-align:center !important;font-size:16px !important;font-weight:bold">
                    Moneda
                  </h6>
                </th>
                <th style="background-color: #aaa;border-right: 1px solid #000 !important;border-left: 1px solid #000 !important;border-bottom: 1px solid #000 !important;border-top: 1px solid #000 !important;">
                  <h6 id="t_valor_ant" style="color:white !important;text-align:center !important;font-size:16px !important;font-weight:bold">
                    Valores 2017/2018
                  </h6>
                </th>
                <th style="background-color: #aaa;border-right: 1px solid #000 !important;border-left: 1px solid #000 !important;border-bottom: 1px solid #000 !important;border-top: 1px solid #000 !important;">
                  <h6 id="t_monto_ant" style="color:white !important;text-align:center !important;font-size:16px !important;font-weight:bold">
                    Montos 2016/2017
                  </h6>
                </th>
                <th style="background-color: #aaa;border-right: 1px solid #000 !important;border-left: 1px solid #000 !important;border-bottom: 1px solid #000 !important;border-top: 1px solid #000 !important;">
                  <h6 id="t_monto_act" style="color:white !important;text-align:center !important;font-size:16px !important;font-weight:bold">
                    Montos 2017/2018
                  </h6>
                </th>
                <th style="background-color: #aaa;border-right: 1px solid #000 !important;border-left: 1px solid #000 !important;border-bottom: 1px solid #000 !important;border-top: 1px solid #000 !important;">
                  <h6 style="color:white !important;text-align:center !important;font-size:16px !important;font-weight:bold">
                    % de Variación
                  </h6>
                </th>
                <th style="background-color: #aaa;border-right: 1px solid #000 !important;border-left: 1px solid #000 !important;border-bottom: 1px solid #000 !important;border-top: 1px solid #000 !important;">
                  <h6 id="t4_valor_base_ant" style="color:white !important;text-align:center !important;font-size:16px !important;font-weight:bold">
                    Valor Base
                  </h6>
                </th>
                <th style="background-color: #aaa;border-right: 1px solid #000 !important;border-left: 1px solid #000 !important;border-bottom: 1px solid #000 !important;border-top: 1px solid #000 !important;">
                  <h6 id="t_valor_base_act" style="color:white !important;text-align:center !important;font-size:16px !important;font-weight:bold">
                    Valor Base 2018/2019
                  </h6>
                </th>
                <th style="background-color: #aaa;border-right: 1px solid #000 !important;border-left: 1px solid #000 !important;border-bottom: 1px solid #000 !important;border-top: 1px solid #000 !important;">
                  <h6 id="t_valor_base_nuevo" style="color:white !important;text-align:center !important;font-size:16px !important;font-weight:bold">
                    Valores 2018/2019
                  </h6>
                </th>
              </thead>
              <tbody>

              </tbody>
            </table>
          </div>
          <div class="table table-responsive" id="mostrarTablaAct"  style="display:none">
            <table class="table" >
              <tbody >
                <tr class"filaClone" id="clonarTA" style="display:none">
                  <td class="col-xs-1 monedaActualizacion" style="border-right: 1px solid #000 !important;border-bottom: 1px solid #000 !important;border-left: 1px solid #000 !important; padding:1px !important;text-align:center !important; font-size:14px !important"></td>
                  <td class="col-xs-1 valoresActualizacion" style="border-right: 1px solid #000 !important;border-bottom: 1px solid #000 !important; border-left: 1px solid #000 !important; padding:1px !important;text-align:center !important; font-size:14px !important"></td>
                  <td class="col-xs-2 pagos1Actualizacion" style="border-right: 1px solid #000 !important;border-bottom: 1px solid #000 !important; border-left: 1px solid #000 !important; padding:1px !important;text-align:center !important; font-size:14px !important"></td>
                  <td class="col-xs-2 pagos2Actualizacion" style="border-right: 1px solid #000 !important;border-bottom: 1px solid #000 !important; border-left: 1px solid #000 !important; padding:1px !important;text-align:center !important; font-size:14px !important"></td>
                  <td class="col-xs-2 variacionActualizacion" style="border-right: 1px solid #000 !important;border-bottom: 1px solid #000 !important; border-left: 1px solid #000 !important; padding:1px !important;text-align:center !important; font-size:14px !important"></td>
                  <td class="col-xs-1 vBaseActualizacion" style="border-right: 1px solid #000 !important;border-bottom: 1px solid #000 !important; border-left: 1px solid #000 !important; padding:1px !important;text-align:center !important; font-size:14px !important"></td>
                  <td class="col-xs-2 vBaseNuevoActualizacion" style="border-right: 1px solid #000 !important;border-bottom: 1px solid #000 !important; border-left: 1px solid #000 !important; padding:1px !important;text-align:center !important; font-size:14px !important"></td>
                  <td class="col-xs-2 vFinalesActualizacion" style="border-right: 1px solid #000 !important;border-bottom: 1px solid #000 !important; border-left: 1px solid #000 !important; padding:1px !important;text-align:center !important; font-size:14px !important"></td>
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
        <h3 class="modal-title">| DATOS DEL CANON </h3>
      </div>
      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row" style="border-bottom:2px solid #ccc;">
            <h6 style="margin-left: 10px;font-size:17px;text-align:center !important;font-weight:bold" id="casinoDatos" ></h6>
            <br>
            <div class="row">
              <div class="col-xs-7" >
                <h6 style="font-size:16px; margin-left:20px;border-bottom:1px solid #ccc;padding:3px !important" id="valorBaseD">VALOR BASE DÓLAR: </h6>
                <h6 style="font-size:16px; margin-left:20px;border-bottom:1px solid #ccc;padding:3px !important" id="valorBaseE">VALOR BASE EURO: </h6>
                <h6 style="font-size:16px; margin-left:20px;border-bottom:1px solid #ccc;padding:3px !important" id="periodoValido">PERIODO DE VALIDEZ: </h6>
                <br>
              </div>
              <button  type="button" class="btn btn-warning modificarCanon" style="margin-bottom:10px !important;font-family:Roboto-Regular !important; font-weight: bold !important;margin-top:120px !important;float:right;margin-right: 20px !important;" name="button">MODIFICAR</button>
            </div>

          </div>
          <div class="row modificacion" hidden="true">
            <h6 style="margin-left: 10px;font-size:17px;text-align:center !important; font-weight:bold">MODIFICACIONES:</h6>
            <br>
            <div class="row " >
              <div class="col-xs-12" style="border-right:1px solid #ccc" id="datosCasinoModif">
                <h6 style="font-size:16px; text-align:center !important;border-bottom:1px solid #ccc"></h6>
                <br>
                <div class="col-xs-6">
                  <h6 style="font-size:16px">VALOR BASE DÓLAR:</h6>
                  <input type="text" class="form-control" id="baseNuevoDolar" name="" value="">
                </div>
                <div class="col-xs-6">
                  <h6 style="font-size:16px">VALOR BASE EURO:</h6>
                  <input type="text" class="form-control" id="baseNuevoEuro" name="" value="">
                </div>

              </div>

              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warningModificar" id="guardarModificacion" value="nuevo" hidden="true">GUARDAR</button>
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
        <h3 class="modal-title">| CARGA DE PAGO </h3>
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
                  <h6 style="font-size:16px !important;">MES A PAGAR</h6>
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
                <div class="col-xs-3">
                  <h6 style="font-size:16px !important;">COTIZACIÓN EURO</h6>
                  <input type="text" name="" value="" id="cotEuroPago" class="form-control">
                </div>
                <div class="col-xs-3">
                  <h6 style="font-size:16px !important;">COTIZACIÓN DOLAR</h6>
                  <input type="text" name="" value="" id="cotDolarPago" class="form-control">
                </div>
                <div class="col-xs-3">
                  <h6 style="font-size:16px !important;">MONTO ABONADO</h6>
                  <input type="text" name="" value="" id="montoPago" class="form-control">
                </div>
                <div class="col-xs-3">
                  <h6 style="font-size:16px !important;">INTERESES</h6>
                  <input type="text" name="" value="" id="impuestosPago" class="form-control">
                </div>
              </div>
              <br>
              <div class="row">
                <div class="col-xs-12">
                  <h6 style="font-size:16px !important;">OBSERVACIONES</h6>
                  <textarea name="name" rows="4"  class="form-control" cols="auto" id="obsPago" style="resize: vertical"></textarea>
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

<div class="modal fade" id="modalAlertaActualizacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
         <div class="modal-content" style="border-radius:5px !important">
           <div class="modal-header" style="background-color:#0D47A1;">
             <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
             <h3 class="modal-title">| AVISO</h3>
            </div>

            <div  id="colapsadoNuevo" class="collapse in">
              <div class="modal-body ">
                <div class="col-xs-12">
                  <br>
                  <h6>Esta actualización se realizará por única vez, y luego sólo podrá visualizar los datos.</h6>
                  <h6>¿Desea continuar?</h6>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" id="aceptarActualizacion" class="btn btn-default">ACTUALIZAR</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
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
