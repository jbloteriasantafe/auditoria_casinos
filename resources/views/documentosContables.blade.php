@extends('includes.dashboard')

  @section('headerLogo')
  <span class="etiquetaLogoExpedientes">@svg('expedientes','iconoExpedientes')</span>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @endsection
  @section('contenidoVista')

  @section('estilos')
  <link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
  <link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
  <link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
  <link rel="stylesheet" href="css/paginacion.css?v=2.0"/>
  <link rel="stylesheet" href="css/lista-datos.css">

  <style>
    #mensajeExito {
      animation: salida 1.5s forwards;
    }
    #mensajeError {
      animation: salida 2s forwards;
    }
    .tabs {
      --fondo: white;
      --gradiente: rgb(235,235,235);
      --gradiente-fondo-inicio: rgba(180,180,180,1);
      --gradiente-fondo-fin: rgba(180,180,180,0);
      --borde-tab: rgb(221, 221, 221);
      --borde-tab-seleccionado: orange;
      --texto-tab-seleccionado: #555;
      width: 100%;
      display: flex;
      flex-direction: column; 
      overflow-y: auto; 
      max-height: 75vh;
      display: block;

      margin-bottom: 10px;
      padding: 0;
    }

    .icono-validado {
      color: #4CAF50 !important;
      font-size: 1.3em !important;
    }
    .icono-no-validado {
      color: #F44336 !important;
      font-size: 1.3em !important;
    }


    .tabs > div {
      flex: none;
      margin: 0;
      padding: 0;
    }
    
    div[id^="pant_"] > .row > .col-md-12 {
      width: 100%; 
      float: none; 
      margin: 0; 
      padding: 0;
    }
    .tabs a {
      padding: 15px 10px;
      font-family:Roboto-condensed;
      font-size:20px;
      background: white;
      display: inline-block;
      width: 100%;
      height: 100%;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
      border: 1px solid var(--borde-tab);
      border-top-left-radius: 2em;
      border-top-right-radius: 2em;
    }
    .tabs a.active {
      color: var(--texto-tab-seleccionado);
      cursor: default;
      border-color: var(--borde-tab-seleccionado);
      border-bottom: none;
    }
    .tabs a:not(.active):not(:hover) {
      background-image:  linear-gradient(135deg, var(--gradiente) 25%, transparent 25%), linear-gradient(225deg, var(--gradiente) 25%, transparent 25%), linear-gradient(45deg, var(--gradiente) 25%, transparent 25%), linear-gradient(315deg, var(--gradiente) 25%, #ffffff 25%);
      background-position:  3px 0, 3px 0, 0 0, 0 0;
      background-size: 3px 3px;
      background-repeat: repeat;
      background-color: var(--fondo);
    }
  </style>
  @endsection

  <div class="row">
    <!-- FILTROS GLOBALES -->
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4>Filtros de Búsqueda Globales</h4>
        </div>
        <div class="panel-body">
          <div class="row">
            <div class="col-lg-4">
              <h5>Casino</h5>
              <select class="form-control" id="filtro_global_casino">
                <option value="">Todos los casinos</option>
                @foreach($casinos as $c)
                  <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-lg-4">
              <h5>Fecha mes desde</h5>
              <div name="FFechaDesde" class='input-group date' id='filtro_global_desde' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                  <input name="fecha_desde" type='text' class="form-control" placeholder="yyyy-mm" id="filtro_global_desde_input" style="background-color: rgb(255,255,255);"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
            <div class="col-lg-4">
              <h5>Fecha mes hasta</h5>
              <div name="FFechaHasta" class='input-group date' id='filtro_global_hasta' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                  <input name="fecha_hasta" type='text' class="form-control" placeholder="yyyy-mm" id="filtro_global_hasta_input" style="background-color: rgb(255,255,255);"/>
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
          </div>
          <br/>
          <div class="row" style="margin-top: 15px;">
            <div class="col-md-3">
              <button id="btn-eliminar-filtros" class="btn btn-dangerEliminar" style="width:100%">
                <i class="fa fa-trash"></i> ELIMINAR FILTROS
              </button>
            </div>
            <div class="col-md-3">
              <button id="btn-buscar-global" class="btn btn-infoBuscar" style="width:100%">
                <i class="fa fa-search"></i> BUSCAR
              </button>
            </div>
            <div class="col-md-3">
              <button id="btn-ver-validados" class="btn btn-infoBuscar" style="width:100%" disabled>
                <i class="fa fa-check-square-o"></i> VER DOCUMENTOS VALIDADOS
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- SIDEBAR TABS -->
    <div class="col-md-2">
      <div class="tabs" data-js-tabs="" style="flex-direction: column; overflow-y: auto; height: 100%; display: block;">
              <div><a data-js-tab="#pant_estado_contable" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">ESTADO CONTABLE</a></div>
  
      <div><a data-js-tab="#pant_iva" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">IVA</a></div>
        <div><a data-js-tab="#pant_iibb" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">IIBB</a></div>
        <div><a data-js-tab="#pant_drei" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">DREI</a></div>
        <div><a data-js-tab="#pant_inmobiliario" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">IMP. INMOBILIARIO</a></div>
        <div><a data-js-tab="#pant_tgi" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">TGI</a></div>
        <div><a data-js-tab="#pant_imp_ap_mtm" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">IMP. APUESTAS MTM</a></div>
        <div><a data-js-tab="#pant_imp_ap_ol" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">IMP. APUESTAS ONLINE</a></div>
        <div><a data-js-tab="#pant_ganancias" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">GANANCIAS</a></div>
        <div><a data-js-tab="#pant_patentes" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">PATENTES</a></div>
        @if($rosario)
          <div><a data-js-tab="#pant_contrib_ente" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">CONTRIB. ENTE (ROS)</a></div>
          <div><a data-js-tab="#pant_derecho" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">DER. ACCESO (ROS)</a></div>
        @endif
        <div><a data-js-tab="#pant_deuda" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">DEUDA ESTADO</a></div>
        <div><a data-js-tab="#pant_direct" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">AUT. DIRECTORES</a></div>
        <div><a data-js-tab="#pant_premios_mtm" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">REGISTROS CONTABLES Y PREMIOS</a></div>
        <!--
        <div><a data-js-tab="#pant_promo_tickets" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">PROMO TICKETS</a></div>
        <div><a data-js-tab="#pant_pozos_acumulados" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">POZOS ACUMULADOS</a></div>
        <div><a data-js-tab="#pant_jackpots_pagados" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">JACKPOTS PAGADOS</a></div>
        <div><a data-js-tab="#pant_premios_pagados" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">PREMIOS PAGADOS</a></div>
        <div><a data-js-tab="#pant_pagos_mayores_mesas" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">PAGOS MESAS DE PAÑO</a></div>
        <div><a data-js-tab="#pant_registros" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">REGISTROS CONTABLES</a></div>
        -->
        <div><a data-js-tab="#pant_aportes" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">APORTES PATRONALES</a></div>
        <div><a data-js-tab="#pant_rrhh" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">RRHH</a></div>
        <div><a data-js-tab="#pant_oper" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">REPORTE LAVADO</a></div>
        <div><a data-js-tab="#pant_seguros" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">SEGUROS</a></div>
        <div id="div_tab_validados" hidden><a data-js-tab="#pant_validados" style="border-radius: 0; border-bottom: 1px solid var(--borde-tab);">VALIDADOS</a></div>
      </div>
    </div>

    <!-- CONTENT AREA -->
    <div class="col-md-10">
      <div id="pant_iva" hidden>
        <div class="row">
          <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="iva_nuevo">NUEVO PAGO DE IVA</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE IVA EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosIVA" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-2" style="text-align: left !important;" value="fecha_iva" estado="">MES <i class="fa fa-sort"></i></th>
                  <th class="col-xs-3" style="text-align: left !important;" value="fecha_pres_iva" estado="">FECHA PRESENTACIÓN <i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" style="text-align: left !important;" value="casino.nombre" estado="">CASINO <i class="fa fa-sort"></i></th>
                  <th class="col-xs-2" style="text-align: left !important;" value="iva.saldo" estado="">SALDO <i class="fa fa-sort"></i></th>
                  <th class="col-xs-3" style="text-align: left !important;">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaIVA" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarIvaExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarIvaCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionIVA" class="row zonaPaginacion"></div>
          </div>

        </div>
      </div>
    </div>




  </div>

  <div id="pant_estado_contable" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="estado_contable_nuevo">NUEVO REGISTRO</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE ESTADO CONTABLE EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosEstadoContable" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-4" value="fecha_EstadoContable" estado="">FECHA <i class="fa"></i></th>
                  <th class="col-xs-4" value="casino.nombre" estado="">CASINO <i class="fa"></i></th>
                  <th class="col-xs-4">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaEstadoContable" style="max-height: 356px;">
              </tbody>
            </table>
            <div id="herramientasPaginacionEstadoContable" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>


    <!-- MODALES ESTADO CONTABLE -->
    <div class="modal fade" id="modalCargarEstadoContable" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width: 70%;">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarEstadoContable" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoEstadoContable" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title" style="background-color: #6dc7be;">| NUEVO ESTADO CONTABLE</h3>
                </div>
                <!-- TODO: Add missing custom inputs based on specific schema request -->
                <div id="colapsadoEstadoContable" class="collapse in">
                  <div class="modal-body modalCuerpo">
                    <form id="frmEstadoContable" name="frmEstadoContable" class="form-horizontal" novalidate="">
                            <div class="row">
                              <div class="col-md-6">
                                <h5>CASINO</h5>
                                <select class="form-control" id="casinoEstadoContable" name="casinoEstadoContable">
                                  <option value="0" selected="-">- Seleccione Casino -</option>
                                  @foreach ($casinos as $cas)
                                    <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                                  @endforeach
                                </select>
                              </div>
                              <div class="col-md-6">
                                <h5>FECHA</h5>
                                <!-- Formato de MES-AÑO -->
                                <div class='input-group date' id='dtpFechaEstadoContable' data-link-field="fecha_EstadoContable" data-date-format="yyyy-MM" data-link-format="yyyy-mm-dd">
                                  <input type='text' class="form-control" id="inputFechaEstadoContable" value=""/>
                                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                </div>
                                <input type="hidden" id="fecha_EstadoContable" value=""/>
                              </div>
                            </div>
                            
                            <br>
                            <hr>
                            <h4 style="color:#000;">DATOS DEL REPORTE</h4>
                            <style>
                              .table-ec { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                              .table-ec th, .table-ec td { border: 1px solid #ccc; padding: 6px 8px; vertical-align: middle; }
                              .table-ec th { text-align: center; background-color: #f9f9f9; }
                              .table-ec .total-row { font-weight: bold; background-color: #f5f5f5; }
                              .table-ec input { width: 100%; text-align: right; }
                              .table-ec .bg-tot-activo { background-color: #e1f5fe; }
                              .table-ec .bg-tot-pasivo { background-color: #ffcdd2; }
                              .table-ec .bg-patrimonio { background-color: #c8e6c9; }
                              .table-ec .bg-gb { background-color: #f5f5f5; font-weight: bold; }
                              .table-ec .bg-ge { background-color: #e1bee7; }
                              .table-ec .bg-gere { background-color: #ffcdd2; }
                            </style>
                            <table class="table-ec">
                              <thead>
                                <tr>
                                  <th colspan="2" style="border:none;"></th>
                                  <th class="ec-anio-actual" style="font-size: 1.1em; border: 2px dashed #000; border-bottom: none; width: 25%;">202X</th>
                                  <th class="ec-anio-anterior" style="font-size: 1.1em; border: 2px dashed #000; border-bottom: none; border-left: none; width: 25%;">202X Reexpresado</th>
                                </tr>
                              </thead>
                              <tbody>
                                <!-- Estado de Situacion Patrimonial -->
                                <tr>
                                  <td rowspan="7" style="text-align: center; font-weight: bold; width: 15%;">Estado de<br>Situacion<br>Patrimonial</td>
                                  <td style="width: 35%;">Activo Corriente:</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="activo_corriente" name="activo_corriente" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="activo_corriente_reexpresado" name="activo_corriente_reexpresado" value=""></td>
                                </tr>
                                <tr>
                                  <td>Activo No Corriente:</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="activo_nocorriente" name="activo_nocorriente" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="activo_nocorriente_reexpresado" name="activo_nocorriente_reexpresado" value=""></td>
                                </tr>
                                <tr class="total-row">
                                  <td>Total Activo:</td>
                                  <td class="bg-tot-activo" style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="total_activo" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                  <td class="bg-tot-activo" style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="total_activo_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                </tr>
                                <tr>
                                  <td>Pasivo Corriente:</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="pasivo_corriente" name="pasivo_corriente" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="pasivo_corriente_reexpresado" name="pasivo_corriente_reexpresado" value=""></td>
                                </tr>
                                <tr>
                                  <td>Pasivo No Corriente:</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="pasivo_nocorriente" name="pasivo_nocorriente" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="pasivo_nocorriente_reexpresado" name="pasivo_nocorriente_reexpresado" value=""></td>
                                </tr>
                                <tr class="total-row">
                                  <td>Total Pasivo:</td>
                                  <td class="bg-tot-pasivo" style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="total_pasivo" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                  <td class="bg-tot-pasivo" style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="total_pasivo_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                </tr>
                                <tr class="total-row">
                                  <td>Patrimonio Neto:</td>
                                  <td class="bg-patrimonio" style="border-left: 2px dashed #000; border-bottom: 2px dashed #000;"><input type="text" class="form-control" id="patrimonio_neto" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                  <td class="bg-patrimonio" style="border-right: 2px dashed #000; border-bottom: 2px dashed #000;"><input type="text" class="form-control" id="patrimonio_neto_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                </tr>
                                <!-- Estado de Resultado -->
                                <tr>
                                  <td rowspan="9" style="text-align: center; font-weight: bold;">Estado de<br>Resultado</td>
                                  <td>Ingresos</td>
                                  <td style="border-left: 2px dashed #000; border-top: 2px dashed #000;"><input type="text" class="form-control" id="ingresos" name="ingresos" value=""></td>
                                  <td style="border-right: 2px dashed #000; border-top: 2px dashed #000;"><input type="text" class="form-control" id="ingresos_reexpresado" name="ingresos_reexpresado" value=""></td>
                                </tr>
                                <tr>
                                  <td>Costos</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="costos" name="costos" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="costos_reexpresado" name="costos_reexpresado" value=""></td>
                                </tr>
                                <tr class="total-row">
                                  <td>Ganancia Bruta</td>
                                  <td class="bg-gb" style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="ganancia_bruta" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                  <td class="bg-gb" style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="ganancia_bruta_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                </tr>
                                <tr>
                                  <td>Gastos comercialización</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="gastos_comercio" name="gastos_comercio" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="gastos_comercio_reexpresado" name="gastos_comercio_reexpresado" value=""></td>
                                </tr>
                                <tr>
                                  <td>Gastos Administración</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="gastos_adm" name="gastos_adm" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="gastos_adm_reexpresado" name="gastos_adm_reexpresado" value=""></td>
                                </tr>
                                <tr>
                                  <td>RECPAM</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="recpam" name="recpam" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="recpam_reexpresado" name="recpam_reexpresado" value=""></td>
                                </tr>
                                <tr>
                                  <td>Otros</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="otros" name="otros" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="otros_reexpresado" name="otros_reexpresado" value=""></td>
                                </tr>
                                <tr>
                                  <td>Impuesto a las ganancias</td>
                                  <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="imp_ganancias" name="imp_ganancias" value=""></td>
                                  <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="imp_ganancias_reexpresado" name="imp_ganancias_reexpresado" value=""></td>
                                </tr>
                                <tr class="total-row">
                                  <td>Ganancia del Ejercicio:</td>
                                  <td class="bg-ge" style="border-left: 2px dashed #000; border-bottom: 2px dashed #000;"><input type="text" class="form-control" id="ganancia_ejercicio" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                  <td class="bg-gere" style="border-right: 2px dashed #000; border-bottom: 2px dashed #000;"><input type="text" class="form-control" id="ganancia_ejercicio_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                                </tr>
                              </tbody>
                            </table>
                            
                            <br>
                            <h4 style="color:#000; text-transform: uppercase;">Variaciones Porcentuales</h4>
                            <style>
                              .table-vp { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9em; }
                              .table-vp th, .table-vp td { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
                              .table-vp th { text-align: center; font-weight: bold; background-color: #f8dbdb; border: 2px solid #000; font-size: 1.1em;}
                              .table-vp .cat-header { color: red; text-align: center; }
                              .table-vp .desc-col { color: red; text-align: center; font-weight: bold; }
                              .table-vp input { width: 100%; text-align: right; border: none; background: transparent; }
                            </style>
                            <table class="table-vp">
                              <thead>
                                <tr>
                                  <th colspan="2" style="background-color: #f28b82; color: #000;">VARIACIONES PORCENTUALES</th>
                                  <th class="ec-anio-actual" style="width: 15%; background-color: #fff;">202X</th>
                                  <th class="ec-anio-anterior" style="width: 15%; background-color: #fff;">202X Aj.</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr>
                                  <td class="cat-header">Ratio de liquidez</td>
                                  <td class="desc-col">Indice de liquidez corriente (solvencia) act corriente/pasivo corriente (margen positivo entre 1,5 y 2)
                                    <i class="fa fa-exclamation-triangle text-danger"
                                      data-toggle="popover"
                                      data-html="true"
                                      data-placement="right"
                                      data-content="Es el índice que indica cómo una empresa puede hacer frente a sus deudas de corto plazo.
                                        Evalúa  la  capacidad  de  la  empresa  para  cumplir  en  término  con  sus  compromisos financieros, deudas y pasivos de corto plazo. 
                                        Si es menor al 100% quiere decir que la empresa no llega a cubrir las deudas de un periodo con sus activos del mismo periodo, es decir que tiene baja liquidez.">
                                    </i>
                                  </td>
                                  <td><input type="text" id="vp_liquidez_corr" readonly></td>
                                  <td><input type="text" id="vp_liquidez_corr_reexpresado" readonly style="color: red;"></td>
                                </tr>
                                <tr>
                                  <td class="cat-header">Ratio de solvencia</td>
                                  <td class="desc-col">Indice de solvencia (activo/pasivo) - (mayor a 1)</td>
                                  <td><input type="text" id="vp_solvencia" readonly></td>
                                  <td><input type="text" id="vp_solvencia_reexpresado" readonly style="color: red;"></td>
                                </tr>
                                <tr>
                                  <td rowspan="4" class="cat-header">Ratio de Endeudamiento</td>
                                  <td class="desc-col">Indice de endeudamiento (Pasivo/PN) - (aprox, 0,5)</td>
                                  <td><input type="text" id="vp_endeudamiento" readonly></td>
                                  <td><input type="text" id="vp_endeudamiento_reexpresado" readonly style="color: red;"></td>
                                </tr>
                                <tr>
                                  <td class="desc-col">Relación porcentual entre Activo/Patrimonio Neto</td>
                                  <td><input type="text" id="vp_activo_pn" readonly></td>
                                  <td><input type="text" id="vp_activo_pn_reexpresado" readonly style="color: red;"></td>
                                </tr>
                                <tr>
                                  <td class="desc-col">Relación porcentual entre Resultados/Patrimonio Neto</td>
                                  <td><input type="text" id="vp_resultado_pn" readonly></td>
                                  <td><input type="text" id="vp_resultado_pn_reexpresado" readonly style="color: red;"></td>
                                </tr>
                                <tr>
                                  <td class="desc-col">Relacion porcentual entre Resultados/Pasivo</td>
                                  <td><input type="text" id="vp_resultado_pasivo" readonly></td>
                                  <td><input type="text" id="vp_resultado_pasivo_reexpresado" readonly style="color: red;"></td>
                                </tr>
                                <tr>
                                  <td rowspan="3" class="cat-header">Ratios de Rentabilidad</td>
                                  <td class="desc-col">Margen de Utilidad (Ut./Ventas)</td>
                                  <td><input type="text" id="vp_margen_utilidad" readonly></td>
                                  <td><input type="text" id="vp_margen_utilidad_reexpresado" readonly></td>
                                </tr>
                                <tr>
                                  <td class="desc-col">ROE (Rentabilidad sobre patrimonio) - (Ut./PN)</td>
                                  <td><input type="text" id="vp_roe" readonly></td>
                                  <td><input type="text" id="vp_roe_reexpresado" readonly></td>
                                </tr>
                                <tr>
                                  <td class="desc-col">ROA (Rentabilidad sobre activos) - (Ut./A)</td>
                                  <td><input type="text" id="vp_roa" readonly></td>
                                  <td><input type="text" id="vp_roa_reexpresado" readonly></td>
                                </tr>
                              </tbody>
                            </table>

                            <br>
                            <style>
                              .table-va { width: 50%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9em; border: 2px solid #000; }
                              .table-va th, .table-va td { border: 1px solid #000; padding: 4px 6px; font-weight: bold;}
                              .table-va input { width: 100%; text-align: right; border: none; background: transparent; }
                            </style>
                            <table class="table-va">
                              <tbody>
                                <tr>
                                  <td>Variacion anual del Activo</td>
                                  <td><input type="text" id="va_activo" readonly></td>
                                </tr>
                                <tr>
                                  <td>Variacion anual del Pasivo</td>
                                  <td><input type="text" id="va_pasivo" readonly></td>
                                </tr>
                                <tr>
                                  <td>Variacion anual del Patrimonio Neto</td>
                                  <td><input type="text" id="va_patrimonio" readonly style="color: red;"></td>
                                </tr>
                                <tr>
                                  <td>Variacion anual del Resultado</td>
                                  <td><input type="text" id="va_resultado" readonly style="color: red;"></td>
                                </tr>
                              </tbody>
                            </table>

                            <br>
                            <div class="row">
                              <div class="col-md-3">
                                <h5>Archivo</h5>
                              </div>
                            </div>
                            <div class="row">
                                <div class="form-group">
                                <div class="input-group col-md-8">
                                  <span class="input-group-btn">
                                    <button class="btn btn-primary" type="button" id="btnPickEstadoContable">
                                      <i class="fa fa-folder-open"></i> Examinar…
                                    </button>
                                  </span>
                                  <input type="text" id="fileNameEstadoContable" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                                  <input type="file" id="uploadEstadoContable" name="uploadEstadoContable[]" multiple style="display:none;">
                                </div>

                                <div class="table-responsive" id="uploadsEstadoContableWrap" style="margin-top:8px; display:none;">
                                  <table class="table table-striped table-bordered table-condensed" id="uploadsEstadoContableTable">
                                    <thead>
                                      <tr>
                                        <th style="width:48px;">#</th>
                                        <th>Archivo</th>
                                        <th style="width:200px;">Tamaño</th>
                                        <th style="width:70px;">Acción</th>
                                      </tr>
                                    </thead>
                                    <tbody></tbody>
                                  </table>
                                </div>
                                <div id="uploadsEstadoContableContainer" style="display:none;"></div>
                              </div>
                            </div>

                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-successAceptar" id="btn-guardarEstadoContable" value="nuevo">ACEPTAR</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                    <input type="hidden" id="id_estado_contable" value="0">
                  </div>
                </div>
              </div>
            </div>
      </div>

    <div class="modal fade" id="modalVerEstadoContable" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <h3 class="modal-title" style="background-color: #6dc7be;">| EL ESTADO CONTABLE</h3>
            </div>
            <div class="modal-body modalCuerpo">
              <div class="row">
                <div class="col-md-6">
                  <h5>CASINO</h5>
                  <input type="text" class="form-control" id="v_casinoEstadoContable" readonly>
                </div>
                <div class="col-md-6">
                  <h5>FECHA</h5>
                  <input type='text' class="form-control" id="v_fechaEstadoContable" readonly/>
                </div>
              </div>
              
              <br>
              <h4 style="color:#000;">DATOS DEL REPORTE</h4>
              <table class="table-ec">
                <thead>
                  <tr>
                    <th colspan="2" style="border:none;"></th>
                    <th class="ec-anio-actual" style="font-size: 1.1em; border: 2px dashed #000; border-bottom: none; width:25%;">202X</th>
                    <th class="ec-anio-anterior" style="font-size: 1.1em; border: 2px dashed #000; border-bottom: none; border-left: none; width:25%;">202X Reexpresado</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Estado de Situacion Patrimonial -->
                  <tr>
                    <td rowspan="7" style="text-align: center; font-weight: bold; width: 15%;">Estado de<br>Situacion<br>Patrimonial</td>
                    <td style="width: 35%;">Activo Corriente:</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_activo_corriente" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_activo_corriente_reexpresado" readonly></td>
                  </tr>
                  <tr>
                    <td>Activo No Corriente:</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_activo_nocorriente" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_activo_nocorriente_reexpresado" readonly></td>
                  </tr>
                  <tr class="total-row">
                    <td>Total Activo:</td>
                    <td class="bg-tot-activo" style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_total_activo" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                    <td class="bg-tot-activo" style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_total_activo_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                  </tr>
                  <tr>
                    <td>Pasivo Corriente:</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_pasivo_corriente" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_pasivo_corriente_reexpresado" readonly></td>
                  </tr>
                  <tr>
                    <td>Pasivo No Corriente:</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_pasivo_nocorriente" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_pasivo_nocorriente_reexpresado" readonly></td>
                  </tr>
                  <tr class="total-row">
                    <td>Total Pasivo:</td>
                    <td class="bg-tot-pasivo" style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_total_pasivo" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                    <td class="bg-tot-pasivo" style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_total_pasivo_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                  </tr>
                  <tr class="total-row">
                    <td>Patrimonio Neto:</td>
                    <td class="bg-patrimonio" style="border-left: 2px dashed #000; border-bottom: 2px dashed #000;"><input type="text" class="form-control" id="v_patrimonio_neto" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                    <td class="bg-patrimonio" style="border-right: 2px dashed #000; border-bottom: 2px dashed #000;"><input type="text" class="form-control" id="v_patrimonio_neto_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                  </tr>
                  <!-- Estado de Resultado -->
                  <tr>
                    <td rowspan="9" style="text-align: center; font-weight: bold;">Estado de<br>Resultado</td>
                    <td>Ingresos</td>
                    <td style="border-left: 2px dashed #000; border-top: 2px dashed #000;"><input type="text" class="form-control" id="v_ingresos" readonly></td>
                    <td style="border-right: 2px dashed #000; border-top: 2px dashed #000;"><input type="text" class="form-control" id="v_ingresos_reexpresado" readonly></td>
                  </tr>
                  <tr>
                    <td>Costos</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_costos" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_costos_reexpresado" readonly></td>
                  </tr>
                  <tr class="total-row">
                    <td>Ganancia Bruta</td>
                    <td class="bg-gb" style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_ganancia_bruta" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                    <td class="bg-gb" style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_ganancia_bruta_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                  </tr>
                  <tr>
                    <td>Gastos comercialización</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_gastos_comercio" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_gastos_comercio_reexpresado" readonly></td>
                  </tr>
                  <tr>
                    <td>Gastos Administración</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_gastos_adm" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_gastos_adm_reexpresado" readonly></td>
                  </tr>
                  <tr>
                    <td>RECPAM</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_recpam" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_recpam_reexpresado" readonly></td>
                  </tr>
                  <tr>
                    <td>Otros</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_otros" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_otros_reexpresado" readonly></td>
                  </tr>
                  <tr>
                    <td>Impuesto a las ganancias</td>
                    <td style="border-left: 2px dashed #000;"><input type="text" class="form-control" id="v_imp_ganancias" readonly></td>
                    <td style="border-right: 2px dashed #000;"><input type="text" class="form-control" id="v_imp_ganancias_reexpresado" readonly></td>
                  </tr>
                  <tr class="total-row">
                    <td>Ganancia del Ejercicio:</td>
                    <td class="bg-ge" style="border-left: 2px dashed #000; border-bottom: 2px dashed #000;"><input type="text" class="form-control" id="v_ganancia_ejercicio" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                    <td class="bg-gere" style="border-right: 2px dashed #000; border-bottom: 2px dashed #000;"><input type="text" class="form-control" id="v_ganancia_ejercicio_reexpresado" readonly style="background-color: transparent; border: none; font-weight: bold; color: #000;"></td>
                  </tr>
                </tbody>
              </table>

              <br>
              <h4 style="color:#000; text-transform: uppercase;">Variaciones Porcentuales</h4>
              <table class="table-vp">
                <thead>
                  <tr>
                    <th colspan="2" style="background-color: #f28b82; color: #000;">VARIACIONES PORCENTUALES</th>
                    <th class="ec-anio-actual" style="width: 15%; background-color: #fff;">202X</th>
                    <th class="ec-anio-anterior" style="width: 15%; background-color: #fff;">202X Aj.</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="cat-header">Ratio de liquidez</td>
                    <td class="desc-col">Indice de liquidez corriente (solvencia) act corriente/pasivo corriente (margen positivo entre 1,5 y 2)</td>
                    <td><input type="text" id="v_vp_liquidez_corr" readonly></td>
                    <td><input type="text" id="v_vp_liquidez_corr_reexpresado" readonly style="color: red;"></td>
                  </tr>
                  <tr>
                    <td class="cat-header">Ratio de solvencia</td>
                    <td class="desc-col">Indice de solvencia (activo/pasivo) - (mayor a 1)</td>
                    <td><input type="text" id="v_vp_solvencia" readonly></td>
                    <td><input type="text" id="v_vp_solvencia_reexpresado" readonly style="color: red;"></td>
                  </tr>
                  <tr>
                    <td rowspan="4" class="cat-header">Ratio de Endeudamiento</td>
                    <td class="desc-col">Indice de endeudamiento (Pasivo/PN) - (aprox, 0,5)</td>
                    <td><input type="text" id="v_vp_endeudamiento" readonly></td>
                    <td><input type="text" id="v_vp_endeudamiento_reexpresado" readonly style="color: red;"></td>
                  </tr>
                  <tr>
                    <td class="desc-col">Relación porcentual entre Activo/Patrimonio Neto</td>
                    <td><input type="text" id="v_vp_activo_pn" readonly></td>
                    <td><input type="text" id="v_vp_activo_pn_reexpresado" readonly style="color: red;"></td>
                  </tr>
                  <tr>
                    <td class="desc-col">Relación porcentual entre Resultados/Patrimonio Neto</td>
                    <td><input type="text" id="v_vp_resultado_pn" readonly></td>
                    <td><input type="text" id="v_vp_resultado_pn_reexpresado" readonly style="color: red;"></td>
                  </tr>
                  <tr>
                    <td class="desc-col">Relacion porcentual entre Resultados/Pasivo</td>
                    <td><input type="text" id="v_vp_resultado_pasivo" readonly></td>
                    <td><input type="text" id="v_vp_resultado_pasivo_reexpresado" readonly style="color: red;"></td>
                  </tr>
                  <tr>
                    <td rowspan="3" class="cat-header">Ratios de Rentabilidad</td>
                    <td class="desc-col">Margen de Utilidad (Ut./Ventas)</td>
                    <td><input type="text" id="v_vp_margen_utilidad" readonly></td>
                    <td><input type="text" id="v_vp_margen_utilidad_reexpresado" readonly></td>
                  </tr>
                  <tr>
                    <td class="desc-col">ROE (Rentabilidad sobre patrimonio) - (Ut./PN)</td>
                    <td><input type="text" id="v_vp_roe" readonly></td>
                    <td><input type="text" id="v_vp_roe_reexpresado" readonly></td>
                  </tr>
                  <tr>
                    <td class="desc-col">ROA (Rentabilidad sobre activos) - (Ut./A)</td>
                    <td><input type="text" id="v_vp_roa" readonly></td>
                    <td><input type="text" id="v_vp_roa_reexpresado" readonly></td>
                  </tr>
                </tbody>
              </table>

              <br>
              <table class="table-va">
                <tbody>
                  <tr>
                    <td>Variacion anual del Activo</td>
                    <td><input type="text" id="v_va_activo" readonly></td>
                  </tr>
                  <tr>
                    <td>Variacion anual del Pasivo</td>
                    <td><input type="text" id="v_va_pasivo" readonly></td>
                  </tr>
                  <tr>
                    <td>Variacion anual del Patrimonio Neto</td>
                    <td><input type="text" id="v_va_patrimonio" readonly style="color: red;"></td>
                  </tr>
                  <tr>
                    <td>Variacion anual del Resultado</td>
                    <td><input type="text" id="v_va_resultado" readonly style="color: red;"></td>
                  </tr>
                </tbody>
              </table>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">CERRAR</button>
            </div>
          </div>
        </div>
    </div>


    <!-- Modal Eliminar ESTADO CONTABLE-->
    <div class="modal fade" id="modalEliminarEstadoContable" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
              </div>

              <div class="modal-body franjaRojaModal">
                <form id="frmEliminarEstadoContable" name="frmEstadoContable" class="form-horizontal" novalidate="">
                    <div class="form-group error ">
                      <div class="col-xs-12">
                          <strong id="titulo-modal-eliminarEstadoContable">¿Seguro desea eliminar el ESTADO CONTABLE?</strong>
                      </div>
                    </div>
                </form>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarEstadoContable" value="0">ELIMINAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
              </div>
          </div>
        </div>
    </div>

  </div>


    <!-- PANE VALIDADOS -->
    <div id="pant_validados" hidden>
      <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4>DOCUMENTOS VALIDADOS EN EL SISTEMA</h4>
            </div>
            <div class="panel-body">
              <table id="tablaResultadosValidados" class="table table-fixed">
                <thead>
                  <tr>
                    <th class="col-xs-4" style="text-align: left !important;" estado="">DOCUMENTO</th>
                    <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                    <th class="col-xs-3" style="text-align: left !important;" estado="">AÑO</th>
                    <th class="col-xs-2" style="text-align: left !important;" estado="">VALIDADO</th>
                  </tr>
                </thead>
                <tbody id="cuerpoTablaValidados" style="max-height: 500px;">
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>


  <div id="pant_iibb" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="iibb_nuevo">NUEVO PAGO DE IIBB</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE IIBB EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosiibb" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">FECHA PRESENTACIÓN</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaiibb" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargariibbExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargariibbCsvRegistros" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv (Datos de los registros)
                </button>
                <button id="btn-descargariibbCsvActividades" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv (Datos de las actividades de cada registro)
                </button>
              </div>
            </div>
            <div id="herramientasPaginacioniibb" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="pant_drei" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="DREI_nuevo">NUEVO PAGO DE DREI</button>
            <!-- <button class="btn" type="button" id="DREI_nueva_partida">NUEVA PARTIDA</button> -->
            <!-- <button class="btn" type="button" id="DREI_partida_gestionar">GESTIONAR PARTIDAS</button> -->
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE DReI EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosDREI" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">FECHA PRESENTACIÓN</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaDREI" style="max-height: 356px;">
              </tbody>
            </table>
            <script>
              // AGGRESSIVE OVERRIDE
              setInterval(function() {
                $('#tablaResultadosiibb th, #tablaResultadosDREI th').css({
                  'text-align': 'left',
                  'padding-left': '0px'
                });
              }, 1000);
            </script>
            <div class="col-md-12 text-center">
              <button id="btn-descargarDREIExcel" class="btn btn-infoBuscar">
                <i class="fa fa-download"></i> .xlsx
              </button>
              <button id="btn-descargarDREICsv" class="btn btn-infoBuscar">
                <i class="fa fa-download"></i> .csv
              </button>

            </div>
            <div id="herramientasPaginacionDREI" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="pant_tgi" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="TGI_nuevo">NUEVO PAGO DE TGI</button>
            <button class="btn" type="button" id="TGI_nueva_partida">NUEVA PARTIDA</button>
            <button class="btn" type="button" id="TGI_partida_gestionar">GESTIONAR PARTIDAS</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE TGI EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosTGI" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-5" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaTGI" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarTGIExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarTGICsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionTGI" class="row zonaPaginacion"></div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="pant_imp_ap_ol" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="IMP_AP_OL_nuevo">NUEVO PAGO DE IMP A APUESTAS ONLINE</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE IMPUESTOS A LAS APUESTAS ONLINE EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosIMP_AP_OL" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">FECHA PRESENTACIÓN</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">QNA</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaIMP_AP_OL" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarIMP_AP_OLExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarIMP_AP_OLCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionIMP_AP_OL" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="pant_imp_ap_mtm" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="IMP_AP_MTM_nuevo">NUEVO PAGO DE IMP A APUESTAS MTM</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE IMPUESTOS A LAS APUESTAS MTM EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosIMP_AP_MTM" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">FECHA PRESENTACIÓN</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">QNA</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaIMP_AP_MTM" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarIMP_AP_MTMExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarIMP_AP_MTMCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionIMP_AP_MTM" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_deuda" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="DeudaEstado_nuevo">NUEVO PAGO DE DEUDA </button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE DEUDA CONSOLIDADA CON EL ESTADO EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosDeudaEstado" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">FECHA DE CONSULTA</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">INCUMPLIMIENTO</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaDeudaEstado" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarDeudaEstadoExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarDeudaEstadoCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionDeudaEstado" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_pagos_mesas" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="PagosMayoresMesas_nuevo">NUEVO PAGO DE MAYOR A MESA DE PAÑO</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE PAGOS MAYORES DE MESAS DE PAÑO EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosPagosMayoresMesas" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-5" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaPagosMayoresMesas" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarPagosMayoresMesasExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarPagosMayoresMesasCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionPagosMayoresMesas" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_oper" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="ReporteYLavado_nuevo">NUEVO PAGO DE REPORTE DE LAVADO</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE REPORTES DE OPERACIONES Y LAVADO DE ACTIVOS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosReporteYLavado" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">REPORTE SISTEMATICO</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">REPORTE DE OPERACIONES</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaReporteYLavado" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarReporteYLavadoExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarReporteYLavadoCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionReporteYLavado" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_registros" hidden>
    
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="RegistrosContables_nuevo">NUEVO REGISTRO CONTABLE</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE REGISTROS CONTABLES EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosRegistrosContables" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-2" estado="">MES</th>
                  <th class="col-xs-2" estado="">CASINO</th>
                  <th class="col-xs-3" estado="">TOTAL PESOS</th>
                  <th class="col-xs-3" estado="">TOTAL DÓLARES</th>
                  <th class="col-xs-4" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaRegistrosContables" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarRegistrosContablesExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarRegistrosContablesCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionRegistrosContables" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_aportes" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="AportesPatronales_nuevo">NUEVO APORTE PATRONAL</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE APORTES PATRONALES EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosAportesPatronales" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-5" estado="">MES</th>
                  <th class="col-xs-5" estado="">CASINO</th>
                  <th class="col-xs-2" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaAportesPatronales" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarAportesPatronalesExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarAportesPatronalesCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionAportesPatronales" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_promoticket" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="PromoTickets_nuevo">NUEVO PROMO TICKET</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE PROMO TICKETS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosPromoTickets" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">CANTIDAD</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">IMPORTE</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaPromoTickets" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarPromoTicketsExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarPromoTicketsCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionPromoTickets" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_pozos_acumulados" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="PozosAcumuladosLinkeados_nuevo">NUEVO POZO ACUMULADO</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE POZOS ACUMULADOS LINKEADOS E INDIVIDUALES EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosPozosAcumuladosLinkeados" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">IMPORTE AL ULT. DIA DE CADA MES</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaPozosAcumuladosLinkeados" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarPozosAcumuladosLinkeadosExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarPozosAcumuladosLinkeadosCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionPozosAcumuladosLinkeados" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="pant_contrib_ente" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="ContribEnteTuristico_nuevo">NUEVO CONTRIBUCIÓN ENTE TURÍSTICO</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE CONTRIBUCIÓN ENTE TURÍSTICO EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosContribEnteTuristico" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">FECHA PRESENTACIÓN</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MONTO PAGADO</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaContribEnteTuristico" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarContribEnteTuristicoExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarContribEnteTuristicoCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionContribEnteTuristico" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_rrhh" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="RRHH_nuevo">NUEVO RECURSOS HUMANOS</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE RECURSOS HUMANOS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosRRHH" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">TOTAL PERSONAL</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">PORCENTAJE VIVIENDO EN SANTA FE</th>

                  <th class="col-xs-3" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaRRHH" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarRRHHExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarRRHHCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionRRHH" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_ganancias" hidden>
    <div class="row">
      <div class="col-md-6">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="Ganancias_nuevo">NUEVO ANTICIPO</button>

            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE GANANCIAS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosGanancias" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" estado="">PERÍODO</th>
                  <th class="col-xs-3" estado="">ANTICIPO</th>

                  <th class="col-xs-3" estado="">CASINO</th>
                  <th class="col-xs-4" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaGanancias" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarGananciasExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx (anticipo y período)
                </button>
                <button id="btn-descargarGananciasCsvAnticipos" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>

              </div>
            </div>
            <div id="herramientasPaginacionGanancias" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="Ganancias_periodo_nuevo">NUEVO PERÍODO</button>

            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE PERÍODOS FISCALES EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosGanancias_periodo" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">PERÍODO</th>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaGanancias_periodo" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarGananciasCsvPeriodos" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionGanancias_periodo" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_jackpots_pagados" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="JackpotsPagados_nuevo">NUEVO JACKPOT PAGADO</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE JACKPOTS PAGADOS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosJackpotsPagados" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">IMPORTE</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaJackpotsPagados" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarJackpotsPagadosExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarJackpotsPagadosCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionJackpotsPagados" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_premios_pagados" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="PremiosPagados_nuevo">NUEVO PREMIO PAGADO</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE REGISTROS CONTABLES Y PREMIOS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosPremiosPagados" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-2" estado="">MES</th>
                  <th class="col-xs-2" estado="">CASINO</th>
                  <th class="col-xs-3" estado="">CANTIDAD</th>
                  <th class="col-xs-3" estado="">IMPORTE</th>
                  <th class="col-xs-4" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaPremiosPagados" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarPremiosPagadosExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarPremiosPagadosCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionPremiosPagados" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="pant_premios_mtm" hidden>
    
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="PremiosMTM_nuevo">NUEVO REGISTRO</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE REGISTROS CONTABLES Y PREMIOS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosPremiosMTM" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-5" style="text-align: left !important;">MES</th>
                  <th class="col-xs-5" style="text-align: left !important;">CASINO</th>
                  <th class="col-xs-3" style="text-align: left !important;">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaPremiosMTM" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarPremiosMTMExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarPremiosMTMCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionPremiosMTM" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_direct" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="AutDirectores_nuevo_autorizacion">NUEVO AUTÓNOMO</button>
            <button class="btn" type="button" id="AutDirectores_nuevo_director">NUEVO DIRECTOR</button>
            <button class="btn" type="button" id="AutDirectores_gestionar_directores">GESTIONAR DIRECTORES</button>


            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE AUTÓNOMOS DE DIRECTORES EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosAutDirectores" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-4" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaAutDirectores" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarAutDirectoresExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarAutDirectoresCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionAutDirectores" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="pant_seguros" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="Seguros_nuevo">NUEVO PAGO DE SEGURO</button>
            <button class="btn" type="button" id="Seguros_nuevo_tipo">NUEVO TIPO DE SEGURO</button>
            <button class="btn" type="button" id="Seguros_tipo_gestionar">GESTIONAR TIPOS DE SEGURO</button>


            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE SEGUROS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosSeguros" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">PERIODO DESDE</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">PERIODO HASTA</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">TIPO DE SEGURO</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">CASINO</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">ESTADO</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaSeguros" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarSegurosExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarSegurosCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionSeguros" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_derecho" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="DerechoAcceso_nuevo">NUEVO DERECHO DE ACCESO</button>
            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE DERECHOS DE ACCESO</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosDerechoAcceso" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">MES</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">SEMANA</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">FECHA VENC.</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">MONTO</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">OBSERVACIONES</th>
                  <th class="col-xs-2" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaDerechoAcceso" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarDerechoAccesoExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarDerechoAccesoCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionDerechoAcceso" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div id="pant_patentes" hidden>
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="Patentes_nuevo">NUEVO PAGO DE PATENTES</button>
            <button class="btn" type="button" id="Patentes_nuevo_patenteDe">NUEVA PATENTE</button>
            <button class="btn" type="button" id="Patentes_patenteDe_gestionar">GESTIONAR PATENTES</button>


            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE PATENTES EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosPatentes" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-6" estado="">PERÍODO</th>
                  <th class="col-xs-4" estado="">CASINO</th>
                  <th class="col-xs-4" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaPatentes" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarPatentesExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarPatentesCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionPatentes" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="pant_inmobiliario" hidden>
    
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <button class="btn" type="button" id="ImpInmobiliario_nuevo">NUEVO PAGO DE IMPUESTO INMOBILIARIO</button>
            <button class="btn" type="button" id="ImpInmobiliario_nueva_partida">NUEVA PARTIDA</button>
            <button class="btn" type="button" id="ImpInmobiliario_partida_gestionar">GESTIONAR PARTIDAS</button>


            <br/><br/>
            <h4>ÚLTIMOS REGISTROS DE IMPUESTOS INMOBILIARIOS EN EL SISTEMA</h4>
          </div>
          <div class="panel-body">
            <table id="tablaResultadosImpInmobiliario" class="table table-fixed">
              <thead>
                <tr>
                  <th class="col-xs-4" estado="">PERÍODO</th>
                  <th class="col-xs-4" estado="">CASINO</th>
                  <th class="col-xs-3" style="text-align: left !important;" estado="">ACCIÓN</th>
                </tr>
              </thead>
              <tbody id="cuerpoTablaImpInmobiliario" style="max-height: 356px;">
              </tbody>
            </table>
            <div class="row">
              <div class="col-md-12 text-center">
                <button id="btn-descargarImpInmobiliarioExcel" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .xlsx
                </button>
                <button id="btn-descargarImpInmobiliarioCsv" class="btn btn-infoBuscar">
                  <i class="fa fa-download"></i> .csv
                </button>
              </div>
            </div>
            <div id="herramientasPaginacionImpInmobiliario" class="row zonaPaginacion"></div>
          </div>
        </div>
      </div>
    </div>

  </div> <!-- close pant_inmobiliario -->

    </div> <!-- Close col-md-10 -->
  </div> <!-- Close row sidebar+content -->

  <!--*************MODALES ********************-->

<!-- MODAL VER ARCHIVOS ASOCIADOS AL REGISTRO -->

<div class="modal fade" id="modalArchivosAsociados" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" style="width:70%">
    <div class="modal-content">
      <div class="modal-header" style="background:#6dc7be">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <h4 class="modal-title" id="tituloArchivos">Archivos</h4>
      </div>
      <div class="modal-body">
        <div id="listaArchivos" class="list-group"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

 <!-- MODAL  ELIMINAR ARCHIVO -->

 <div class="modal fade" id="modalEliminarArchivo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                       <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el archivo?</strong>
                   </div>
                 </div>
             </form>
           </div>

           <div class="modal-footer">
             <button type="button"  id="btn-eliminarArchivo" class="btn btn-dangerEliminar"> ELIMINAR  </button>
             <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
           </div>
       </div>
     </div>
 </div>

<!-- MODAL CARGAR IVA -->

<div class="modal fade" id="modalCargarIva" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:71%">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #00695c;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarCrearIva" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearIva" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE IVA</h3>
            </div>

            <div  id="colapsadoCrearIva" class="collapse in">

    <form id="formNuevoRegistroIva" novalidate="" method="POST" autocomplete="off">

      <input type="hidden" id="iva_modo" name="iva_modo" value="create">
      <input type="hidden" id="id_registroIva" name="id_registroIva" value="">

      <div class="modal-body">
        <div class="row">
          <div class="col-md-4">
            <h5>MES</h5>
            <div class='input-group date' id='fechaIva' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                <input name="fecha_iva" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_iva" style="background-color: rgb(255,255,255);"/>
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
          <div class="col-md-5">
            <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
            <div class='input-group date' id='fechaIvaPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                <input name="fecha_ivaPres" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_ivaPres" style="background-color: rgb(255,255,255);"/>
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
        </div>
        <br/>

        <div class="row">
          <div class="col-md-4" >
            <h5>Casino</h5>
            <select name="casinoIva" class="form-control" id="casinoIva">
              <option value="">Elige un casino</option>
              @foreach($casinos as $c)
                <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <h5>Saldo a favor ARCA/Contribuyente</h5>
            <input type="text" class="form-control" name="saldoIva" id="saldoIva">
          </div>
          <div class="col-md-4">
            <h5>Observaciones</h5>
            <textarea class="form-control" name="obsiva" maxlength="3999" rows="1"></textarea>
          </div>
        </div>

      </br>

        <div class="row">
          <div class="col-md-3">
            <h5>Archivo</h5>
            </div>
          </div>
          <div class="row">
              <div class="form-group">
              <div class="input-group col-md-8">
                <span class="input-group-btn">
                  <button class="btn btn-primary" type="button" id="btnPickIva">
                    <i class="fa fa-folder-open"></i> Examinar…
                  </button>
                </span>
                <input type="text" id="fileNameIva" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                <input type="file" id="uploadIva" name="uploadIva[]" multiple style="display:none;">
              </div>

              <div class="table-responsive" id="uploadsIvaWrap" style="margin-top:8px; display:none;">
                <table class="table table-striped table-bordered table-condensed" id="uploadsIvaTable">
                  <thead>
                    <tr>
                      <th style="width:48px;">#</th>
                      <th>Archivo</th>
                      <th style="width:200px;">Tamaño</th>
                      <th style="width:70px;">Acción</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>

              <div id="uploadsIvaContainer" style="display:none;"></div>
              </div>
          </div>
        </div>
      <div class="modal-footer">

        <button id ="guardarRegistroIva" type="button" class="btn btn-successAceptar">GENERAR</button>
        <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

      </div>
        </form>
      </div> <!-- modal content -->
    </div> <!-- modal dialog -->
  </div> <!-- modal fade -->
  </div>
</div>

<!-- MODAL VER OBSERVACION IVA-->

<div class="modal fade" id="modalObsIva" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:50%">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #00695c;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarObsIva" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoObsIva" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #00695c;">| OBSERVACIÓN DEL REGISTRO DE IVA</h3>
            </div>

            <div  id="colapsadoObsIva" class="collapse in">

              <div class="modal-body">
                <p id="obsIvaContent"></p>
              </div>



              <div class="modal-footer">

                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>

            </div>

          </div> <!-- modal content -->
    </div> <!-- modal dialog -->
  </div> <!-- modal fade -->

<!-- MODAL ELIMINAR IVA -->

<div class="modal fade" id="modalEliminarIva" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                      <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro IVA?</strong>
                  </div>
                </div>
            </form>
          </div>

          <div class="modal-footer">
            <button type="button"  id="btn-eliminarIva" class="btn btn-dangerEliminar"> ELIMINAR  </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
      </div>
    </div>
</div>

<!-- MODAL VER IVA -->
<div class="modal fade" id="modalVerIva" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:50%">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="background-color: #00695c;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarVerIva" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerIva" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO IVA</h3>
      </div>

      <div id="colapsadoVerIva" class="collapse in">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <h5>MES</h5>
              <input type="text" class="form-control" id="ver_fecha_iva" readonly>
            </div>
            <div class="col-md-4">
              <h5>FECHA DE PRESENTACIÓN</h5>
              <input type="text" class="form-control" id="ver_fecha_pres_iva" readonly>
            </div>
            <div class="col-md-4">
              <h5>CASINO</h5>
              <input type="text" class="form-control" id="ver_casino_iva" readonly>
            </div>
          </div>
          <br/>
          <div class="row">
            <div class="col-md-6">
              <h5>SALDO A FAVOR ARCA/CONTRIBUYENTE</h5>
              <input type="text" class="form-control" id="ver_saldo_iva" readonly>
            </div>
            <div class="col-md-6">
              <h5>OBSERVACIONES</h5>
              <textarea class="form-control" id="ver_obs_iva" readonly></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
        </div>
      </div>
    </div> <!-- modal content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->

<!-- MODAL CARGAR IIBB -->
<div class="modal fade" id="modalCargariibb" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:71%">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #00695c;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarCreariibb" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCreariibb" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE IIBB</h3>
            </div>

            <div  id="colapsadoCreariibb" class="collapse in">

    <form id="formNuevoRegistroiibb" novalidate="" method="POST" autocomplete="off">


          <input type="hidden" id="iibb_modo" name="iibb_modo" value="create">
          <input type="hidden" id="id_registroiibb" name="id_registroiibb" value="">

      <div class="modal-body">
        <div class="row">
          <div class="col-md-4">
            <h5>Mes</h5>
            <div class='input-group date' id='fechaiibb' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                <input name="fecha_iibb" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_iibb" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
          <div class="col-md-4">
            <h5>PRESENTACIÓN DDJJ/FECHA DE PAGO</h5>
            <div class='input-group date' id='fechaiibbPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                <input name="fecha_iibbPres" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_iibbPres" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
          <div class="col-md-4" >
            <h5>Casino</h5>
            <select name="casinoiibb" class="form-control" id="casinoiibb">
              <option value="">Elija un casino</option>
              @foreach($casinos as $c)
                <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <br/>

        <div id="contenedor-inputs-iibb-cargar">

        </div>
    </br>

      <div class="row">
          <div class="text-center">
            <button type="button" id="agregar-bloque-iibb-cargar" class="mt-3 btn btn-success">+</button>
          </div>
        </div>
        <br/>
        <div class="row">
          <div class="col-md-4">
            <h5>Diferencia minimo </h5>
            <input type="text" class="form-control" name="dif_miniibb" id="dif_miniibb">
          </div>
          <div class="col-md-4">
            <h5>Deducciones</h5>
            <input type="text" class="form-control" name="deduccionesiibb" id="deduccionesiibb">
          </div>
          <div class="col-md-4">
            <h5>Observaciones</h5>
            <textarea class="form-control" name="obsiibb" maxlength="3999" rows="1"></textarea>
          </div>
        </div>

      </br>

      <div class="row">
        <div class="col-md-6">
          <h5>IMpuesto Total</h5>
          <input type="text" id="total_impuesto_iibb" class="form-control" name="total_impuesto_iibb">
        </div>
        <div class="col-md-6">
          <h5>Saldo a favor API/Contribuyente</h5>
          <input type="text" id="saldo_iibb" class="form-control" name="saldo_iibb">

        </div>
      </div>

        <div class="row">
          <div class="col-md-3">
            <h5>Archivo</h5>
            </div>
          </div>
          <div class="row">
              <div class="form-group">
              <div class="input-group col-md-8">
                <span class="input-group-btn">
                  <button class="btn btn-primary" type="button" id="btnPickiibb">
                    <i class="fa fa-folder-open"></i> Examinar…
                  </button>
                </span>
                <input type="text" id="fileNameiibb" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                <input type="file" id="uploadiibb" name="uploadiibb[]" multiple style="display:none;">
              </div>

              <div class="table-responsive" id="uploadsiibbWrap" style="margin-top:8px; display:none;">
                <table class="table table-striped table-bordered table-condensed" id="uploadsiibbTable">
                  <thead>
                    <tr>
                      <th style="width:48px;">#</th>
                      <th>Archivo</th>
                      <th style="width:200px;">Tamaño</th>
                      <th style="width:70px;">Acción</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>

              <div id="uploadsiibbContainer" style="display:none;"></div>
              </div>
      </div>

    </div>
      <div class="modal-footer">

        <button id ="guardarRegistroiibb" type="button" class="btn btn-successAceptar">GENERAR</button>
        <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

      </div>
    </form>
  </div> <!-- modal content -->
  </div> <!-- modal dialog -->
  </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR IIBB -->
<div class="modal fade" id="modalEliminariibb" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                     <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro IIBB?</strong>
                 </div>
               </div>
           </form>
         </div>

         <div class="modal-footer">
           <button type="button"  id="btn-eliminariibb" class="btn btn-dangerEliminar"> ELIMINAR  </button>
           <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
         </div>
     </div>
   </div>
</div>

<!-- MODAL VER IIBB-->
<div class="modal fade" id="modalVeriibb" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
       <div class="modal-dialog" style="width:71%">
          <div class="modal-content">
            <div class="modal-header modalNuevo" style="background-color: #00695c;">
              <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
              <button id="btn-minimizarVeriibb" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVeriibb" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
              <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO IIBB</h3>
             </div>

             <div  id="colapsadoVeriibb" class="collapse in">
               <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>Fecha IIBB</h5>
                    <input type="text" class="form-control" id="ver_fecha_iibb" readonly>
                  </div>
                  <div class="col-md-4">
                    <h5>Presentación DDJJ/Fecha de pago</h5>
                    <input type="text" class="form-control" id="ver_fecha_pres_iibb" readonly>
                  </div>
                  <div class="col-md-4">
                    <h5>Casino</h5>
                    <input type="text" class="form-control" id="ver_casino_iibb" readonly>
                  </div>
                </div>
              </br>

              <div class="row">
                <div class="col-md-4">
                  <h4>Actividad/Observaciones</h4>
                </div>
                <div class="col-md-3">
                  <h4>Montos</h4>
                </div>
                <div class="col-md-3">
                  <h4>Alicuotas (%)</h4>
                </div>
                <div class="col-md-2">
                  <h4>Impuesto Total</h4>
                </div>
              </div>
              <div id="contenedor-bases-ver-iibb"></div>


                <div class="row">
                  <div class="col-md-4">
                    <h5>Impuesto total determinado</h5>
                    <input type="text" class="form-control" id="ver_impuestoTotal_iibb" readonly>
                  </div>
                  <div class="col-md-4">
                    <h5>Diferencia mínimo</h5>
                    <input type="text" class="form-control" id="ver_diferencia_iibb" readonly>
                  </div>
                  <div class="col-md-4">
                    <h5>Deducciones</h5>
                    <input type="text" class="form-control" id="ver_deduccion_iibb" readonly>
                  </div>
                </div>
              </br>
                <div class="row">
                  <div class="col-md-6">
                    <h5>Saldo a favor API/Contribuyente</h5>
                    <input type="text" class="form-control" id="ver_saldo_iibb" readonly>
                  </div>
                  <div class="col-md-6">
                    <h5>Observaciones</h5>
                    <textarea class="form-control" id="ver_obs_iibb" readonly></textarea>
                  </div>
                </div>
              </div>

               <div class="modal-footer">

                 <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

               </div>
             </div> <!-- modal content -->
           </div> <!-- modal dialog -->
         </div> <!-- modal fade -->
 </div>

<!-- MODAL CARGAR DREI -->
<div class="modal fade" id="modalCargarDREI" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:71%">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #00695c;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarCrearDREI" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearDREI" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE DReI</h3>
            </div>

            <div  id="colapsadoCrearDREI" class="collapse in">

    <form id="formNuevoRegistroDREI" novalidate="" method="POST" autocomplete="off">

      <input type="hidden" id="DREI_modo" name="DREI_modo" value="create">
      <input type="hidden" id="id_registroDREI" name="id_registroDREI" value="">

      <div class="modal-body">
        <div class="row">
          <div class="col-md-4">
            <h5>Mes</h5>
            <div class='input-group date' id='fechaDREI' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                <input name="fecha_DREI" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_DREI" autocomplete="off" style="background-color: rgb(255,255,255);" />
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
          <div class="col-md-4">
            <h5>FECHA DE PRESENTACIÓN Y PAGO</h5>
            <div class='input-group date' id='fechaDREIPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                <input name="fecha_DREIPres" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_DREIPres" autocomplete="off" style="background-color: rgb(255,255,255);" />
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
            </div>
          </div>
          <div class="col-md-4" >
            <h5>Casino</h5>
            <select name="casinoDREI" class="form-control" id="casinoDREI">
              <option value="">Elija un casino</option>
              @foreach($casinos as $c)
                <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <br/>
        <div id="formularioCSF" class="formulario-DREI" style="display: none;">

          <div class="row">
            <div class="col-md-12">
              <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Comercio</h4>
            </div>
          </div>
          <div class="row">

            <div class="col-md-4">
              <h5>Base imponible</h5>
              <input type="text" class="form-control" name="base_imponible_comDREI" id="base_imponible_comDREI">
            </div>
            <div class="col-md-4">
              <h5>Alicuota (%)</h5>
              <input type="text" class="form-control" name="alicuota_comDREI" id="alicuota_comDREI">
            </div>
            <div class="col-md-4">
              <h5>Impuesto determinado</h5>
              <input type="text" class="form-control" name="imp_det_comDREI" id="imp_det_comDREI" >
            </div>
          </div>
          <br/>
          <div class="row">
            <div class="col-md-12">
              <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Gastronomía</h4>
            </div>
          </div>
          <div class="row">

            <div class="col-md-4">
              <h5>Base imponible</h5>
              <input type="text" class="form-control" name="base_imponible_gasDREI" id="base_imponible_gasDREI">
            </div>
            <div class="col-md-4">
              <h5>Alicuota (%)</h5>
              <input type="text" class="form-control" name="alicuota_gasDREI" id="alicuota_gasDREI">
            </div>
            <div class="col-md-4">
              <h5>Impuesto determinado</h5>
              <input type="text" class="form-control" name="imp_det_gasDREI" id="imp_det_gasDREI" >
            </div>
          </div>
          <br/>
          <div class="row">
            <div class="col-md-12">
              <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Explotación Casinos y Bingos</h4>
            </div>
          </div>
          <div class="row">

            <div class="col-md-4">
              <h5>Base imponible</h5>
              <input type="text" class="form-control" name="base_imponible_explDREI" id="base_imponible_explDREI">
            </div>
            <div class="col-md-4">
              <h5>Alicuota (%)</h5>
              <input type="text" class="form-control" name="alicuota_explDREI" id="alicuota_explDREI">
            </div>
            <div class="col-md-4">
              <h5>Impuesto determinado</h5>
              <input type="text" class="form-control" name="imp_det_explDREI" id="imp_det_explDREI" >
            </div>
          </div>
          <br/>
          <div class="row">
            <div class="col-md-12">
              <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Apuestas y Juegos de Azar por plataformas</h4>
            </div>
          </div>
          <div class="row">

            <div class="col-md-4">
              <h5>Base imponible</h5>
              <input type="text" class="form-control" name="base_imponible_apyjDREI" id="base_imponible_apyjDREI">
            </div>
            <div class="col-md-4">
              <h5>Alicuota (%)</h5>
              <input type="text" class="form-control" name="alicuota_apyjDREI" id="alicuota_apyjDREI">
            </div>
            <div class="col-md-4">
              <h5>Impuesto determinado</h5>
              <input type="text" class="form-control" name="imp_det_apyjDREI" id="imp_det_apyjDREI" >
            </div>
          </div>



          </br>
            <div class="row">
              <div class="col-md-4">
                <h5>Intereses</h5>
                <input type="text" class="form-control" name="interesesDREI" id="interesesDREI">
              </div>
              <div class="col-md-4">
                <h5>Deducciones</h5>
                <input type="text" class="form-control" name="deduccionesDREI" id="deduccionesDREI">
              </div>
              <div class="col-md-4">
                <h5>Bromatología</h5>
                <input type="text" class="form-control" name="bromatologiaDREI" id="bromatologiaDREI">
              </div>


          </div>
        </br>
        <div class="row">
          <div class="col-md-4">
            <h5> Impuesto total determinado</h5>
            <input type="text" class="form-control" name="imp_tot_csfDREI" id="imp_tot_csfDREI">
          </div>
          <div class="col-md-4">
            <h5>Saldo a favor</h5>
            <input type="text" class="form-control" name="saldoDREI" id="saldoDREI">
          </div>
        </div>
      </div>
        <div id="formularioMEL" class="formulario-DREI" style="display: none;">

            <div class="row">
              <div class="col-md-6">
                <h5>Monto Pagado</h5>
                <input type="text" class="form-control" name="monto_pagado_melDREI" id="monto_pagado_melDREI">
              </div>
            </div>
          </br>
            <div class="row">
              <div class="col-md-12">
              <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Base Imponible Juegos</h4>
            </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <h5>Base imponible</h5>
                <input type="text" class="form-control" name="base_imponible_melDREI" id="base_imponible_melDREI">
              </div>
              <div class="col-md-4">
                <h5>Alicuota (%)</h5>
                <input type="text" class="form-control" name="alicuota_melDREI" id="alicuota_melDREI">
              </div>
              <div class="col-md-4">
                <h5>Impuesto determinado</h5>
                <input type="text" class="form-control" name="imp_det_melDREI" id="imp_det_melDREI" >
              </div>
            </div>
            </br>

            <div class="row">
              <div class="col-md-12">
                <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Base Imponible Otras Actividades</h4>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <h5>Base imponible</h5>
                <input type="text" class="form-control" name="base_imponibleO_melDREI" id="base_imponibleO_melDREI">
              </div>
              <div class="col-md-4">
                <h5>Alicuota (%)</h5>
                <input type="text" class="form-control" name="alicuotaO_melDREI" id="alicuotaO_melDREI">
              </div>
              <div class="col-md-4">
                <h5>Impuesto determinado</h5>
                <input type="text" class="form-control" name="imp_det0_melDREI" id="imp_det0_melDREI">
              </div>
            </div>
          </br>
          <div class="row">
            <div class="col-md-4">
              <h5> Saldo</h5>
              <input type="text" class="form-control" name="saldo_melDREI" id="saldo_melDREI">
            </div>
          </div>
        </div>

        <div id="formularioRO" class="formulario-DREI" style="display: none;">

              <div class="row">

                <div class="col-md-6">
                  <h5>Vencimiento Previsto</h5>
                  <div class='input-group date' id='fechaDREIVenc' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                      <input name="fecha_DREIVenc" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_DREIVenc" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
            </br>

            <div class="row">
              <div class="col-md-6">
                <h5>Monto Pagado Total</h5>
                <input type="text" class="form-control" name="monto_pagado_roDREI" id="monto_pagado_roDREI">
              </div>
              <div class="col-md-6">
                <h5>Saldo a favor municipal/Contribuyente</h5>
                <input type="text" class="form-control" name="total_roDREI" id="total_roDREI">
              </div>
            </div>

        </div>


      </br>
        <div class="row">
          <div class="col-md-4">
            <h5>Observaciones</h5>
            <textarea class="form-control" name="obsDREI" id="obsDREI" maxlength="3999" rows="2"></textarea>
          </div>
        </div>

      </br>

      <div class="row">
        <div class="col-md-3">
          <h5>Archivo</h5>
          </div>
        </div>
        <div class="row">
            <div class="form-group">
            <div class="input-group col-md-8">
              <span class="input-group-btn">
                <button class="btn btn-primary" type="button" id="btnPickDREI">
                  <i class="fa fa-folder-open"></i> Examinar…
                </button>
              </span>
              <input type="text" id="fileNameDREI" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
              <input type="file" id="uploadDREI" name="uploadDREI[]" multiple style="display:none;">
            </div>

            <div class="table-responsive" id="uploadsDREIWrap" style="margin-top:8px; display:none;">
              <table class="table table-striped table-bordered table-condensed" id="uploadsDREITable">
                <thead>
                  <tr>
                    <th style="width:48px;">#</th>
                    <th>Archivo</th>
                    <th style="width:200px;">Tamaño</th>
                    <th style="width:70px;">Acción</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <div id="uploadsDREIContainer" style="display:none;"></div>
            </div>
    </div>

      </div>
      </br>
      <div class="modal-footer">

        <button id ="guardarRegistroDREI" type="button" class="btn btn-successAceptar">GENERAR</button>
        <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

      </div>
    </form>
  </div> <!-- modal content -->
  </div> <!-- modal dialog -->
  </div> <!-- modal fade -->
</div>

<!-- MODAL VER DREI CSF-->
<div class="modal fade" id="modalVerCSFDREI" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:71%">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #00695c;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarVerDREI" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerDREI" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO DREI</h3>
            </div>

            <div  id="colapsadoVerDREI" class="collapse in">
              <div class="modal-body">
               <div class="row">
                 <div class="col-md-4">
                   <h5>Fecha DREI</h5>
                   <input type="text" class="form-control" id="ver_fecha_csfDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Presentación DDJJ/Fecha de pago</h5>
                   <input type="text" class="form-control" id="ver_fecha_pres_csfDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Casino</h5>
                   <input type="text" class="form-control" id="ver_casino_csfDREI" readonly>
                 </div>
               </div>
             </br>
               <div class="row">
                 <div class="col-md-6">
                   <h4>Comercio</h4>
                 </div>
               </div>
               <div class="row">
                 <div class="col-md-4">
                   <h5>Base imponible</h5>
                   <input type="text" class="form-control" id="ver_com_base_csfDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Alicuota (%)</h5>
                   <input type="text" class="form-control" id="ver_com_ali_csfDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Subtotal impuesto determinado</h5>
                   <input type="text" class="form-control" id="ver_com_imp_csfDREI" readonly>
                 </div>
               </div>
             </br>
             <div class="row">
               <div class="col-md-6">
                 <h4>Gastronomía</h4>
               </div>
             </div>
             <div class="row">
               <div class="col-md-4">
                 <h5>Base imponible</h5>
                 <input type="text" class="form-control" id="ver_gas_base_csfDREI" readonly>
               </div>
               <div class="col-md-4">
                 <h5>Alicuota (%)</h5>
                 <input type="text" class="form-control" id="ver_gas_ali_csfDREI" readonly>
               </div>
               <div class="col-md-4">
                 <h5>Impuesto determinado</h5>
                 <input type="text" class="form-control" id="ver_gas_imp_csfDREI" readonly>
               </div>
             </div>
             </br>
             <div class="row">
               <div class="col-md-6">
                 <h4>Explotación casinos y bingos</h4>
               </div>
             </div>
             <div class="row">
               <div class="col-md-4">
                 <h5>Base imponible</h5>
                 <input type="text" class="form-control" id="ver_expl_base_csfDREI" readonly>
               </div>
               <div class="col-md-4">
                 <h5>Alicuota (%)</h5>
                 <input type="text" class="form-control" id="ver_expl_ali_csfDREI" readonly>
               </div>
               <div class="col-md-4">
                 <h5>Impuesto determinado</h5>
                 <input type="text" class="form-control" id="ver_expl_imp_csfDREI" readonly>
             </div>
            </div>
          </br>
          <div class="row">
            <div class="col-md-6">
              <h4>Apuestas y juegos de azar por plataformas</h4>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <h5>Base imponible</h5>
              <input type="text" class="form-control" id="ver_apyju_base_csfDREI" readonly>
            </div>
            <div class="col-md-4">
              <h5>Alicuota (%)</h5>
              <input type="text" class="form-control" id="ver_apyju_ali_csfDREI" readonly>
            </div>
            <div class="col-md-4">
              <h5>Impuesto determinado</h5>
              <input type="text" class="form-control" id="ver_apyju_imp_csfDREI" readonly>
          </div>
         </div>
       </br>
         <div class="row">
           <div class="col-md-4">
             <h5>Adicional de Bromatología</h5>
             <input type="text" class="form-control" id="ver_bromatologia_csfDREI" readonly>
           </div>
           <div class="col-md-4">
             <h5>Deducciones</h5>
             <input type="text" class="form-control" id="ver_deducciones_csfDREI" readonly>
           </div>
           <div class="col-md-4">
             <h5>Total impuesto determinado</h5>
             <input type="text" class="form-control" id="ver_total_imp_csfDREI" readonly>
           </div>
         </div>
       </br>
         <div class="row">
           <div class="col-md-4">
             <h5>Intereses</h5>
             <input type="text" class="form-control" id="ver_intereses_csfDREI" readonly>
           </div>
           <div class="col-md-4">
             <h5>Saldo a favor</h5>
             <input type="text" class="form-control" id="ver_saldo_csfDREI" readonly>
           </div>

         </div>
       </br>
         <div class="row">
           <div class="col-md-5">
             <h5>Observaciones</h5>
             <textarea class="form-control" id="ver_obs_csfDREI" readonly></textarea>
           </div>

         </div>

         </div>

              <div class="modal-footer">

                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </div> <!-- modal content -->
          </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL VER DREI MEL -->
<div class="modal fade" id="modalVerMELDREI" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:71%">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #00695c;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarVermelDREI" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVermelDREI" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO DREI</h3>
            </div>

            <div  id="colapsadoVermelDREI" class="collapse in">
              <div class="modal-body">
               <div class="row">
                 <div class="col-md-4">
                   <h5>Fecha DREI</h5>
                   <input type="text" class="form-control" id="ver_fecha_melDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Presentación DDJJ/Fecha de pago</h5>
                   <input type="text" class="form-control" id="ver_fecha_pres_melDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Casino</h5>
                   <input type="text" class="form-control" id="ver_casino_melDREI" readonly>
                 </div>
               </div>
             </br>

              <div class="row">
                <div class="col-md-4">
                  <h5>Monto Pagado</h5>
                  <input type="text" class="form-control" id="ver_monto_melDREI" readonly>
                </div>
              </div>

               <div class="row">
                 <div class="col-md-6">
                   <h4>Base Imponible Juegos</h4>
                 </div>
               </div>
               <div class="row">
                 <div class="col-md-4">
                   <h5>Base imponible</h5>
                   <input type="text" class="form-control" id="ver_com_base_melDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Alicuota (%)</h5>
                   <input type="text" class="form-control" id="ver_com_ali_melDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Impuesto Determinado</h5>
                   <input type="text" class="form-control" id="ver_com_imp_melDREI" readonly>
                 </div>
               </div>
             </br>
             <div class="row">
               <div class="col-md-6">
                 <h4>Base Imponible Otras Actividades</h4>
               </div>
             </div>
             <div class="row">
               <div class="col-md-4">
                 <h5>Base imponible</h5>
                 <input type="text" class="form-control" id="ver_gas_base_melDREI" readonly>
               </div>
               <div class="col-md-4">
                 <h5>Alicuota (%)</h5>
                 <input type="text" class="form-control" id="ver_gas_ali_melDREI" readonly>
               </div>
               <div class="col-md-4">
                 <h5>Impuesto Determinado</h5>
                 <input type="text" class="form-control" id="ver_gas_imp_melDREI" readonly>
               </div>
             </div>


       </br>
         <div class="row">

           <div class="col-md-4">
             <h5>Saldo</h5>
             <input type="text" class="form-control" id="ver_saldo_melDREI" readonly>
           </div>
           <div class="col-md-4">
             <h5>Observaciones</h5>
             <textarea class="form-control" id="ver_obs_melDREI" readonly></textarea>
           </div>
         </div>

         </div>

              <div class="modal-footer">

                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </div> <!-- modal content -->
          </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL VER DREI RO -->
<div class="modal fade" id="modalVerRODREI" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:71%">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #00695c;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarVerroDREI" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerroDREI" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO DREI</h3>
            </div>

            <div  id="colapsadoVerroDREI" class="collapse in">
              <div class="modal-body">
               <div class="row">
                 <div class="col-md-4">
                   <h5>Fecha DREI</h5>
                   <input type="text" class="form-control" id="ver_fecha_roDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Presentación DDJJ/Fecha de pago</h5>
                   <input type="text" class="form-control" id="ver_fecha_pres_roDREI" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Casino</h5>
                   <input type="text" class="form-control" id="ver_casino_roDREI" readonly>
                 </div>
               </div>
             </br>

              <div class="row">
                <div class="col-md-6">
                  <h5>Monto Pagado Total</h5>
                  <input type="text" class="form-control" id="ver_monto_roDREI" readonly>
                </div>
                <div class="col-md-6">
                  <h5>Saldo a favor municipal/Contribuyente</h5>
                  <input type="text" class="form-control" id="ver_saldo_roDREI" readonly>
                </div>
              </div>


   </br>
         <div class="row">


           <div class="col-md-4">
             <h5>Observaciones</h5>
             <textarea class="form-control" id="ver_obs_roDREI" readonly></textarea>
           </div>
         </div>

         </div>

              <div class="modal-footer">

                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </div> <!-- modal content -->
          </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR DREI -->
<div class="modal fade" id="modalEliminarDREI" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                      <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro DREI?</strong>
                  </div>
                </div>
            </form>
          </div>

          <div class="modal-footer">
            <button type="button"  id="btn-eliminarDREI" class="btn btn-dangerEliminar"> ELIMINAR  </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
      </div>
    </div>
</div>

<!-- MODAL CARGAR TGI - PARTIDA -->

<div class="modal fade" id="modalCargarTGI_partida" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog" style="width:71%">
                   <div class="modal-content">
                     <div class="modal-header modalNuevo" style="background-color: #00695c;">
                       <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                       <button id="btn-minimizarCrearTGI_partida" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearTGI_partida" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                       <h3 class="modal-title" style="background-color: #00695c;">| NUEVA PARTIDA</h3>
                      </div>

                      <div  id="colapsadoCrearTGI_partida" class="collapse in">

              <form id="formNuevoRegistroTGI_partida" novalidate="" method="POST" autocomplete="off">

                <div class="modal-body">
                  <div class="row">
                    <div class="col-md-3">
                      <h5>Casino</h5>
                      <select class="form-control" name ="CasinoTGI_partida" id="CasinoTGI_partida">
                        <option value="">Seleccione un casino</option>
                        @foreach($casinos as $c)
                          <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-9">
                      <h5>Nombre de la partida</h5>
                      <input type="text" rows="3" class="form-control" name="nombre_TGI_partida" id="nombre_TGI_partida" >
                    </div>
                  </div>
                  <br/>

                </div>

                <div class="modal-footer">

                  <button id ="guardarRegistroTGI_partida" type="button" class="btn btn-successAceptar">GENERAR</button>
                  <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                </div>
              </form>
            </div> <!-- modal content -->
          </div> <!-- modal dialog -->
          </div> <!-- modal fade -->
  </div>

<!-- MODAL GESTIONAR TGI - PARTIDA -->

<div class="modal fade" id="modalTGI_partida_gestionar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerpatenteDe_gestionar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerpatenteDe_gestionar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| PARTIDAS </h3>
                  </div>

                  <div  id="colapsadoVerpatenteDe_gestionar" class="collapse in">
                    <div class="modal-body">

                      <div class="modal-body p-0">
                         <div id="dir-list-loading_TGI_partida" class="p-3">Cargando...</div>
                         <div class="table-responsive" style="display:none">
                           <table class="table table-fixed" id="tabla-TGI_partida">
                             <thead class="thead-light">
                               <tr>
                                 <th class="col-md-8">Nombre de la partida</th>
                                 <th class="col-md-1">Casino</th>
                                 <th class="col-md-2">Estado</th>
                                 <th class="text-left col-md-1">Acciones</th>
                               </tr>
                             </thead>
                             <tbody></tbody>
                           </table>
                         </div>
                       </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR TGI - PARTIDA -->

<div class="modal fade" id="modalEliminarTGI_partida" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar la partida?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarTGI_partida" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL MODIFICAR TGI - PARTIDA-->

<div class="modal fade" id="modalModificarTGI_partida" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarModificarTGI_partida" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoModificarTGI_partida" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| MODIFICAR PARTIDA</h3>
                    </div>

                    <div  id="colapsadoModificarTGI_partida" class="collapse in">

            <form id="formModificarRegistroTGI_partida" novalidate="" method="POST" autocomplete="off">

              <div class="modal-body">
                  <input type="hidden" name="ModifId_TGI_partida" id="ModifId_TGI_partida">

                <div class="row">
                  <div class="col-md-10">
                    <h5>Partida</h5>
                    <input type="text" class="form-control" name="ModifTGI_partida_partida" id="ModifTGI_partida_partida" >
                  </div>
                  <div class="col-md-2">
                    <h5>Estado</h5>
                    <select name="ModifEstadoTGI_partida" class="form-control" id="ModifEstadoTGI_partida">
                      <option value="1">Habilitado</option>
                      <option value="0">Deshabilitado</option>
                    </select>
                  </div>
                </div>
                <br/>

              </div>

              <div class="modal-footer">

                <button id ="guardarModifRegistroTGI_partida" type="button" class="btn btn-successAceptar">GUARDAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">CANCELAR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR TGI -->

<div class="modal fade" id="modalCargarTGI" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width:95%">
           <div class="modal-content">
             <div class="modal-header modalNuevo" style="background-color: #00695c;">
               <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
               <button id="btn-minimizarCrearTGI" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearTGI" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title" style="background-color: #00695c;">| NUEVO PAGO DE TGI</h3>
              </div>

              <div  id="colapsadoCrearTGI" class="collapse in">

      <form id="formNuevoRegistroTGI" novalidate="" method="POST" autocomplete="off">

        <input type="hidden" id="TGI_modo" name="TGI_modo" value="create">
          <input type="hidden" id="id_registroTGI" name="id_registroTGI" value="">


        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <h5>MES</h5>
              <div class='input-group date' id='fechaTGI' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                  <input name="fecha_TGI" type='text' class="form-control" placeholder="Fecha de ejecución" id="fecha_TGI" autocomplete="off" style="background-color: rgb(255,255,255);" />
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
            </div>
            <div class="col-md-4" >
              <h5>Casino</h5>
              <select name="casinoTGI" class="form-control" id="casinoTGI">
                <option value="">Elige un casino</option>
                @foreach($casinos as $c)
                  <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                @endforeach
              </select>
            </div>

          </div>
          <br>


<div class="row">
  <div class="col-md-12">
    <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Pagos por Partida</h4>

    <div id="pagosTGIContainer"></div>
  </div>
</div>




    </br>


      <div class="row">
          <div class="col-md-3">
            <h5>Archivo</h5>
            </div>
          </div>
          <div class="row">
              <div class="form-group">
              <div class="input-group col-md-8">
                <span class="input-group-btn">
                  <button class="btn btn-primary" type="button" id="btnPickTGI">
                    <i class="fa fa-folder-open"></i> Examinar…
                  </button>
                </span>
                <input type="text" id="fileNameTGI" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                <input type="file" id="uploadTGI" name="uploadTGI[]" multiple style="display:none;">
              </div>

              <div class="table-responsive" id="uploadsTGIWrap" style="margin-top:8px; display:none;">
                <table class="table table-striped table-bordered table-condensed" id="uploadsTGITable">
                  <thead>
                    <tr>
                      <th style="width:48px;">#</th>
                      <th>Archivo</th>
                      <th style="width:200px;">Tamaño</th>
                      <th style="width:70px;">Acción</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>

              <div id="uploadsTGIContainer" style="display:none;"></div>
              </div>
      </div>

        </div>

        <div class="modal-footer">

          <button id ="guardarRegistroTGI" type="button" class="btn btn-successAceptar">GENERAR</button>
          <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

        </div>
      </form>
    </div> <!-- modal content -->
  </div> <!-- modal dialog -->
  </div> <!-- modal fade -->
  </div>

<!-- MODAL ELIMINAR TGI -->

<div class="modal fade" id="modalEliminarTGI" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                        <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro TGI?</strong>
                    </div>
                  </div>
              </form>
            </div>

            <div class="modal-footer">
              <button type="button"  id="btn-eliminarTGI" class="btn btn-dangerEliminar"> ELIMINAR  </button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
            </div>
        </div>
      </div>
  </div>

<!-- MODAL VER TGI CSF -->

<div class="modal fade" id="modalVerTGICSF" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width:95%">
           <div class="modal-content">
             <div class="modal-header modalNuevo" style="background-color: #00695c;">
               <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
               <button id="btn-minimizarVerTGICSF" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerTGICSF" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title" style="bac kground-color: #6dc7be;">| REGISTRO TGI</h3>
              </div>

              <div id="colapsadoVerTGICSF" class="collapse in">
                <div class="modal-body">
                  <div class="row">
                    <div class="col-md-4">
                      <h5>MES</h5>
                      <input type="text" class="form-control" id="ver_fecha_TGI" readonly>
                    </div>
                    <div class="col-md-4">
                      <h5>Casino</h5>
                      <input type="text" class="form-control" id="ver_casino_TGI" readonly>
                    </div>
                    <div class="col-md-4" id="wrap_ver_cuotas_TGI" style="display:none;">
                      <h5>Cuota</h5>
                      <input type="text" class="form-control" id="ver_cuotas_TGI" readonly>
                    </div>
                  </div>

                  <br/>

                  <div class="row">
                    <div class="col-md-12">
                      <h4>Pagos por Partida</h4>
                      <div id="ver_pagosTGIContainer"></div>
                    </div>
                  </div>


                </div>
              </div>

                <div class="modal-footer">

                  <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                </div>
              </div> <!-- modal content -->
            </div> <!-- modal dialog -->
          </div> <!-- modal fade -->
  </div>

<!-- MODAL CARGAR IMPUESTO A APUESTAS ONLINE IMP AP OL -->

<div class="modal fade" id="modalCargarIMP_AP_OL" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:71%">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="background-color: #00695c;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarCrearIMP_AP_OL" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearIMP_AP_OL" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE IMP_AP_OL</h3>
                </div>

                <div  id="colapsadoCrearIMP_AP_OL" class="collapse in">

        <form id="formNuevoRegistroIMP_AP_OL" novalidate="" method="POST" autocomplete="off">
          <input type="hidden" id="IMP_AP_OL_modo" name="IMP_AP_OL_modo" value="create">
          <input type="hidden" id="id_registroIMP_AP_OL" name="id_registroIMP_AP_OL" value="">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4">
                <h5>MES</h5>
                <div class='input-group date' id='fechaIMP_AP_OL' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                    <input name="fecha_IMP_AP_OL" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_IMP_AP_OL" autocomplete="off" style="background-color: rgb(255,255,255);" />
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-5">
                <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
                <div class='input-group date' id='fechaIMP_AP_OLPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                    <input name="fecha_IMP_AP_OLPres" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_IMP_AP_OLPres" autocomplete="off" style="background-color: rgb(255,255,255);" />
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-3" >
                <h5>Casino</h5>
                <select name="casinoIMP_AP_OL" class="form-control" id="casinoIMP_AP_OL">
                  <option value="">Elige un casino</option>
                  @foreach($casinos as $c)
                    <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <br/>

            <div class="row">
              <div class="col-md-4" >
                <h5>QNA</h5>
                <select name="qnaIMP_AP_OL" class="form-control" id="qnaIMP_AP_OL">
                  <option value="1">1°</option>
                  <option value="2">2°</option>
                </select>
              </div>
              <div class="col-md-5">
                <h5>FECHA DE PAGO</h5>
                <div class='input-group date' id='fecha_pagoIMP_AP_OL' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                    <input name="fecha_pago_IMP_AP_OL" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_pago_IMP_AP_OL" autocomplete="off" style="background-color: rgb(255,255,255);" />
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
        </br>
          <div class="row">
            <div class="col-md-4">
              <h5>Monto Pagado</h5>
              <input type="text" class="form-control" name="monto_pagadoIMP_AP_OL" id="monto_pagadoIMP_AP_OL">
            </div>
            <div class="col-md-4">
               <h5>Monto Apuestas</h5>
               <input type="text" class="form-control" name="monto_apuestasIMP_AP_OL" id="monto_apuestasIMP_AP_OL">
           </div>
          <div class="col-md-4">
            <h5>Alicuota (%)</h5>
            <input type="text" class="form-control" name="alicuotaIMP_AP_OL" id="alicuotaIMP_AP_OL">
          </div>
        </div>
      </br>
        <div class="row">
          <div class="col-md-4">
            <h5>IMPUESTO DETERMINADO</h5>
            <input type="text" class="form-control" name="impuesto_determinadoIMP_AP_OL" id="impuesto_determinadoIMP_AP_OL">
         </div>
       </div>

          </br>


                  <div class="row">
                          <div class="col-md-3">
                            <h5>Archivo</h5>
                            </div>
                          </div>
                          <div class="row">
                              <div class="form-group">
                              <div class="input-group col-md-8">
                                <span class="input-group-btn">
                                  <button class="btn btn-primary" type="button" id="btnPickIMP_AP_OL">
                                    <i class="fa fa-folder-open"></i> Examinar…
                                  </button>
                                </span>
                                <input type="text" id="fileNameIMP_AP_OL" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                                <input type="file" id="uploadIMP_AP_OL" name="uploadIMP_AP_OL[]" multiple style="display:none;">
                              </div>

                              <div class="table-responsive" id="uploadsIMP_AP_OLWrap" style="margin-top:8px; display:none;">
                                <table class="table table-striped table-bordered table-condensed" id="uploadsIMP_AP_OLTable">
                                  <thead>
                                    <tr>
                                      <th style="width:48px;">#</th>
                                      <th>Archivo</th>
                                      <th style="width:200px;">Tamaño</th>
                                      <th style="width:70px;">Acción</th>
                                    </tr>
                                  </thead>
                                  <tbody></tbody>
                                </table>
                              </div>

                              <div id="uploadsIMP_AP_OLContainer" style="display:none;"></div>
                              </div>
                      </div>
          </div>

          <div class="modal-footer">

            <button id ="guardarRegistroIMP_AP_OL" type="button" class="btn btn-successAceptar">GENERAR</button>
            <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

          </div>
        </form>
      </div> <!-- modal content -->
    </div> <!-- modal dialog -->
    </div> <!-- modal fade -->
  </div>

<!-- MODAL ELIMINAR IMPUESTO A APUESTAS ONLINE -->

<div class="modal fade" id="modalEliminarIMP_AP_OL" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                        <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro sobre el impuesto a las apuestas online?</strong>
                    </div>
                  </div>
              </form>
            </div>

            <div class="modal-footer">
              <button type="button"  id="btn-eliminarIMP_AP_OL" class="btn btn-dangerEliminar"> ELIMINAR  </button>
              <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
            </div>
        </div>
      </div>
  </div>

<!-- MODAL VER IMPUESTO A APUESTAS ONLINE -->

<div class="modal fade" id="modalVerIMP_AP_OL" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width:71%">
           <div class="modal-content">
             <div class="modal-header modalNuevo" style="background-color: #00695c;">
               <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
               <button id="btn-minimizarVerIMP_AP_OL" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerIMP_AP_OL" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO IMPUESTO A APUESTAS ONLINE</h3>
              </div>

              <div  id="colapsadoVerIMP_AP_OL" class="collapse in">
                <div class="modal-body">
                 <div class="row">
                   <div class="col-md-4">
                     <h5>MES</h5>
                     <input type="text" class="form-control" id="ver_fecha_IMP_AP_OL" readonly>
                   </div>
                   <div class="col-md-4">
                     <h5>Presentación DDJJ/Fecha de pago</h5>
                     <input type="text" class="form-control" id="ver_fecha_pres_IMP_AP_OL" readonly>
                   </div>
                   <div class="col-md-4">
                     <h5>Casino</h5>
                     <input type="text" class="form-control" id="ver_casino_IMP_AP_OL" readonly>
                   </div>
                 </div>
               </br>
                 <div class="row">
                   <div class="col-md-4">
                     <h5>QNA</h5>
                     <input type="text" class="form-control" id="ver_qna_IMP_AP_OL" readonly>
                   </div>
                   <div class="col-md-4">
                     <h5>FECHA DE PAGO</h5>
                     <input type="text" class="form-control" id="ver_fecha_pago_IMP_AP_OL" readonly>
                   </div>

                 </div>
               </br>
                 <div class="row">
                   <div class="col-md-4">
                     <h5>MONTO PAGADO</h5>
                     <input type="text" class="form-control" id="ver_monto_pagado_IMP_AP_OL" readonly>
                   </div>
                   <div class="col-md-4">
                     <h5>MONTO APUESTAS</h5>
                     <input type="text" class="form-control" id="ver_monto_apuestas_IMP_AP_OL" readonly>
                   </div>
                   <div class="col-md-4">
                     <h5>ALICUOTA (%)</h5>
                     <input type="text" class="form-control" id="ver_alicuota_IMP_AP_OL" readonly>
                   </div>
                 </div>
               </br>
                 <div class="row">
                   <div class="col-md-4">
                     <h5>IMPUESTO DETERMINADO</h5>
                     <input type="text" class="form-control" id="ver_impuesto_determinado_IMP_AP_OL" readonly>
                   </div>

                 </div>


               </div>

                <div class="modal-footer">

                  <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                </div>
              </div> <!-- modal content -->
            </div> <!-- modal dialog -->
          </div> <!-- modal fade -->
  </div>

<!-- MODAL CARGAR IMPUESTO A APUESTAS MTM IMP AP MTM -->

<div class="modal fade" id="modalCargarIMP_AP_MTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarCrearIMP_AP_MTM" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearIMP_AP_MTM" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE IMP_AP_MTM</h3>
                  </div>

                  <div  id="colapsadoCrearIMP_AP_MTM" class="collapse in">

          <form id="formNuevoRegistroIMP_AP_MTM" novalidate="" method="POST" autocomplete="off">

                <input type="hidden" id="IMP_AP_MTM_modo" name="IMP_AP_MTM_modo" value="create">
                <input type="hidden" id="id_registroIMP_AP_MTM" name="id_registroIMP_AP_MTM" value="">
            <div class="modal-body">
              <div class="row">
                <div class="col-md-4">
                  <h5>MES</h5>
                  <div class='input-group date' id='fechaIMP_AP_MTM' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                      <input name="fecha_IMP_AP_MTM" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_IMP_AP_MTM" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
                <div class="col-md-5">
                  <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
                  <div class='input-group date' id='fechaIMP_AP_MTMPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                      <input name="fecha_IMP_AP_MTMPres" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_IMP_AP_MTMPres" autocomplete="off" style="background-color: rgb(255,255,255);" />
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
                <div class="col-md-3" >
                  <h5>Casino</h5>
                  <select name="casinoIMP_AP_MTM" class="form-control" id="casinoIMP_AP_MTM">
                    <option value="">Elige un casino</option>
                    @foreach($casinos as $c)
                      <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <br/>

              <div class="row">
                <div class="col-md-4" >
                  <h5>QNA</h5>
                  <select name="qnaIMP_AP_MTM" class="form-control" id="qnaIMP_AP_MTM">
                    <option value="1">1°</option>
                    <option value="2">2°</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <h5>FECHA DE PAGO</h5>
                  <div class='input-group date' id='fecha_pagoIMP_AP_MTM' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                      <input name="fecha_pago_IMP_AP_MTM" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_pago_IMP_AP_MTM" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
                <div class="col-md-4">
                  <h5>Cantidad de MTM</h5>
                  <input type="text" class="form-control" name="cantMTM_IMP_AP_MTM" id="cant_MTM_IMP_AP_MTM">
              </div>
            </div>
          </br>

            <div class="row">
              <div class="col-md-4">
                <h5>Monto Pagado</h5>
                <input type="text" class="form-control" name="monto_pagadoIMP_AP_MTM" id="monto_pagadoIMP_AP_MTM">
              </div>
              <div class="col-md-4">
                 <h5>Monto Apuestas</h5>
                 <input type="text" class="form-control" name="monto_apuestasIMP_AP_MTM" id="monto_apuestasIMP_AP_MTM">
             </div>
            <div class="col-md-4">
              <h5>Alicuota (%)</h5>
              <input type="text" class="form-control" name="alicuotaIMP_AP_MTM" id="alicuotaIMP_AP_MTM">
            </div>
          </div>
        </br>
          <div class="row">
            <div class="col-md-4">
              <h5>IMPUESTO DETERMINADO</h5>
              <input type="text" class="form-control" name="impuesto_determinadoIMP_AP_MTM" id="impuesto_determinadoIMP_AP_MTM">
           </div>
         </div>

            </br>


        <div class="row">
                <div class="col-md-3">
                  <h5>Archivo</h5>
                  </div>
                </div>
                <div class="row">
                    <div class="form-group">
                    <div class="input-group col-md-8">
                      <span class="input-group-btn">
                        <button class="btn btn-primary" type="button" id="btnPickIMP_AP_MTM">
                          <i class="fa fa-folder-open"></i> Examinar…
                        </button>
                      </span>
                      <input type="text" id="fileNameIMP_AP_MTM" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                      <input type="file" id="uploadIMP_AP_MTM" name="uploadIMP_AP_MTM[]" multiple style="display:none;">
                    </div>

                    <div class="table-responsive" id="uploadsIMP_AP_MTMWrap" style="margin-top:8px; display:none;">
                      <table class="table table-striped table-bordered table-condensed" id="uploadsIMP_AP_MTMTable">
                        <thead>
                          <tr>
                            <th style="width:48px;">#</th>
                            <th>Archivo</th>
                            <th style="width:200px;">Tamaño</th>
                            <th style="width:70px;">Acción</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>

                    <div id="uploadsIMP_AP_MTMContainer" style="display:none;"></div>
                    </div>
            </div>
            </div>

            <div class="modal-footer">

              <button id ="guardarRegistroIMP_AP_MTM" type="button" class="btn btn-successAceptar">GENERAR</button>
              <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

            </div>
          </form>
        </div> <!-- modal content -->
      </div> <!-- modal dialog -->
      </div> <!-- modal fade -->
  </div>

<!-- MODAL ELIMINAR IMPUESTO A APUESTAS MTM -->

<div class="modal fade" id="modalEliminarIMP_AP_MTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                          <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro sobre el impuesto a las apuestas MTM?</strong>
                      </div>
                    </div>
                </form>
              </div>

              <div class="modal-footer">
                <button type="button"  id="btn-eliminarIMP_AP_MTM" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
              </div>
          </div>
        </div>
    </div>

<!-- MODAL VER IMPUESTO A APUESTAS MTM -->

<div class="modal fade" id="modalVerIMP_AP_MTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:71%">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="background-color: #00695c;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarVerIMP_AP_MTM" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerIMP_AP_MTM" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO IMPUESTO A APUESTAS ONLINE</h3>
                </div>

                <div  id="colapsadoVerIMP_AP_MTM" class="collapse in">
                  <div class="modal-body">
                   <div class="row">
                     <div class="col-md-4">
                       <h5>MES</h5>
                       <input type="text" class="form-control" id="ver_fecha_IMP_AP_MTM" readonly>
                     </div>
                     <div class="col-md-4">
                       <h5>Presentación DDJJ/Fecha de pago</h5>
                       <input type="text" class="form-control" id="ver_fecha_pres_IMP_AP_MTM" readonly>
                     </div>
                     <div class="col-md-4">
                       <h5>Casino</h5>
                       <input type="text" class="form-control" id="ver_casino_IMP_AP_MTM" readonly>
                     </div>
                   </div>
                 </br>
                   <div class="row">
                     <div class="col-md-4">
                       <h5>QNA</h5>
                       <input type="text" class="form-control" id="ver_qna_IMP_AP_MTM" readonly>
                     </div>
                     <div class="col-md-4">
                       <h5>FECHA DE PAGO</h5>
                       <input type="text" class="form-control" id="ver_fecha_pago_IMP_AP_MTM" readonly>
                     </div>
                     <div class="col-md-4">
                       <h5>Cantidad de MTM</h5>
                       <input type="text" class="form-control" id="ver_cant_mtm_IMP_AP_MTM" readonly>
                     </div>

                   </div>
                 </br>
                   <div class="row">
                     <div class="col-md-4">
                       <h5>MONTO PAGADO</h5>
                       <input type="text" class="form-control" id="ver_monto_pagado_IMP_AP_MTM" readonly>
                     </div>
                     <div class="col-md-4">
                       <h5>MONTO APUESTAS</h5>
                       <input type="text" class="form-control" id="ver_monto_apuestas_IMP_AP_MTM" readonly>
                     </div>
                     <div class="col-md-4">
                       <h5>ALICUOTA (%)</h5>
                       <input type="text" class="form-control" id="ver_alicuota_IMP_AP_MTM" readonly>
                     </div>
                   </div>
                 </br>
                   <div class="row">
                     <div class="col-md-4">
                       <h5>IMPUESTO DETERMINADO</h5>
                       <input type="text" class="form-control" id="ver_impuesto_determinado_IMP_AP_MTM" readonly>
                     </div>

                   </div>


                 </div>

                  <div class="modal-footer">

                    <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                  </div>
                </div> <!-- modal content -->
              </div> <!-- modal dialog -->
            </div> <!-- modal fade -->
  </div>

<!-- MODAL CARGAR DEUDA ESTADO -->

<div class="modal fade" id="modalCargarDeudaEstado" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarCrearDeudaEstado" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearDeudaEstado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE DEUDA CONSOLIDADA CON EL ESTADO</h3>
                  </div>

                  <div  id="colapsadoCrearDeudaEstado" class="collapse in">

          <form id="formNuevoRegistroDeudaEstado" novalidate="" method="POST" autocomplete="off">
            <input type="hidden" id="DeudaEstado_modo" name="DeudaEstado_modo" value="create">
               <input type="hidden" id="id_registroDeudaEstado" name="id_registroDeudaEstado" value="">
            <div class="modal-body">
              <div class="row">
                <div class="col-md-4">
                  <h5>MES</h5>
                  <div class='input-group date' id='fechaDeudaEstado' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                      <input name="fecha_DeudaEstado" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_DeudaEstado" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
                <div class="col-md-5">
                  <h5>FECHA DE CONSULTA</h5>
                  <div class='input-group date' id='fechaDeudaEstadoPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                      <input name="fecha_DeudaEstadoPres" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_DeudaEstadoPres" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
                <div class="col-md-3" >
                  <h5>Casino</h5>
                  <select name="casinoDeudaEstado" class="form-control" id="casinoDeudaEstado">
                    <option value="">Elige un casino</option>
                    @foreach($casinos as $c)
                      <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <br/>

              <div class="row">
                <div class="col-md-4" >
                  <h5>¿Registra incumplimientos?</h5>
                  <select name="regIncumDeudaEstado" class="form-control" id="regIncumDeudaEstado">
                    <option value="2">NO</option>
                    <option value="1">SI</option>

                  </select>
                </div>
            </div>
          </br>
          <div id="formularioIncumDeudaEstado" class="formulario-DeudaEstado" style="display: none;">
            <div class="row">
              <div class="col-md-12">
                <h5>Incumplimiento</h5>
                <textarea class="form-control" name="incumDeudaEstado" id="incumDeudaEstado" maxlength="4000" rows="5"></textarea>
            </div>
          </div>
          </br>
        </div>



            <div class="row">
                <div class="col-md-3">
                  <h5>Archivo</h5>
                  </div>
                </div>
                <div class="row">
                    <div class="form-group">
                    <div class="input-group col-md-8">
                      <span class="input-group-btn">
                        <button class="btn btn-primary" type="button" id="btnPickDeudaEstado">
                          <i class="fa fa-folder-open"></i> Examinar…
                        </button>
                      </span>
                      <input type="text" id="fileNameDeudaEstado" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                      <input type="file" id="uploadDeudaEstado" name="uploadDeudaEstado[]" multiple style="display:none;">
                    </div>

                    <div class="table-responsive" id="uploadsDeudaEstadoWrap" style="margin-top:8px; display:none;">
                      <table class="table table-striped table-bordered table-condensed" id="uploadsDeudaEstadoTable">
                        <thead>
                          <tr>
                            <th style="width:48px;">#</th>
                            <th>Archivo</th>
                            <th style="width:200px;">Tamaño</th>
                            <th style="width:70px;">Acción</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>

                    <div id="uploadsDeudaEstadoContainer" style="display:none;"></div>
                    </div>
            </div>

            </div>

            <div class="modal-footer">

              <button id ="guardarRegistroDeudaEstado" type="button" class="btn btn-successAceptar">GENERAR</button>
              <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

            </div>
          </form>
        </div> <!-- modal content -->
      </div> <!-- modal dialog -->
      </div> <!-- modal fade -->
  </div>

<!-- MODAL ELIMINAR DEUDA ESTADO -->

<div class="modal fade" id="modalEliminarDeudaEstado" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                          <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro sobre la deuda consolidada con el estado?</strong>
                      </div>
                    </div>
                </form>
              </div>

              <div class="modal-footer">
                <button type="button"  id="btn-eliminarDeudaEstado" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
              </div>
          </div>
        </div>
    </div>

<!-- MODAL VER DEUDA ESTADO -->

<div class="modal fade" id="modalVerDeudaEstado" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:71%">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="background-color: #00695c;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizarVerDeudaEstado" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerDeudaEstado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title" style="background-color: #00695c;">| INCUMPLIMIENTO A LA DEUDA CONSOLIDADA CON EL ESTADO</h3>
                </div>

                <div  id="colapsadoVerDeudaEstado" class="collapse in">
                  <div class="modal-body">
                   <div class="row">

                     <div class="col-md-12">
                       <h4>Incumplimiento:</h4>
                       <textarea id="ver_incumplimiento_DeudaEstado" class="form-control" readonly rows="10"></textarea>
                     </div>
                   </div>


                 </div>

                  <div class="modal-footer">

                    <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                  </div>
                </div> <!-- modal content -->
              </div> <!-- modal dialog -->
            </div> <!-- modal fade -->
  </div>

<!-- MODAL CARGAR PAGOS MAYORES MESAS DE PAÑO -->

<div class="modal fade" id="modalCargarPagosMayoresMesas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearPagosMayoresMesas" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearPagosMayoresMesas" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE PAGO MAYOR DE MESA DE PAÑO</h3>
                    </div>

                    <div  id="colapsadoCrearPagosMayoresMesas" class="collapse in">

            <form id="formNuevoRegistroPagosMayoresMesas" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="PagosMayoresMesas_modo" name="PagosMayoresMesas_modo" value="create">
                  <input type="hidden" id="id_registroPagosMayoresMesas" name="id_registroPagosMayoresMesas" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaPagosMayoresMesas' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_PagosMayoresMesas" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_PagosMayoresMesas" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3" >
                    <h5>Casino</h5>
                    <select name="casinoPagosMayoresMesas" class="form-control" id="casinoPagosMayoresMesas">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <br/>

                <div class="row">
                  <div class="col-md-4" >
                    <h5>Cantidad de pagos</h5>
                    <textarea class="form-control" name="cant_pagos_PagosMayoresMesas" id="cant_pagos_PagosMayoresMesas"></textarea>
                  </div>
                  <div class="col-md-4">
                    <h5>Importe en pesos </h5>
                    <input type="text" class="form-control" name="importe_pesos_PagosMayoresMesas" id="importe_pesos_PagosMayoresMesas" placeholder="ARS">
                  </div>
                  <div class="col-md-4">
                    <h5>Importe en Dolares</h5>
                    <input type="text" class="form-control" name="importe_dolares_PagosMayoresMesas" id="importe_dolares_PagosMayoresMesas" placeholder="USD">
                </div>
              </div>

              </br>


<div class="row">
        <div class="col-md-3">
          <h5>Archivo</h5>
          </div>
        </div>
        <div class="row">
            <div class="form-group">
            <div class="input-group col-md-8">
              <span class="input-group-btn">
                <button class="btn btn-primary" type="button" id="btnPickPagosMayoresMesas">
                  <i class="fa fa-folder-open"></i> Examinar…
                </button>
              </span>
              <input type="text" id="fileNamePagosMayoresMesas" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
              <input type="file" id="uploadPagosMayoresMesas" name="uploadPagosMayoresMesas[]" multiple style="display:none;">
            </div>

            <div class="table-responsive" id="uploadsPagosMayoresMesasWrap" style="margin-top:8px; display:none;">
              <table class="table table-striped table-bordered table-condensed" id="uploadsPagosMayoresMesasTable">
                <thead>
                  <tr>
                    <th style="width:48px;">#</th>
                    <th>Archivo</th>
                    <th style="width:200px;">Tamaño</th>
                    <th style="width:70px;">Acción</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <div id="uploadsPagosMayoresMesasContainer" style="display:none;"></div>
            </div>
    </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroPagosMayoresMesas" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR PAGOS MAYORES MESAS DE PAÑO -->

<div class="modal fade" id="modalEliminarPagosMayoresMesas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro sobre el pago mayor de mesa de paño?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarPagosMayoresMesas" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER PAGOS MAYORES MESAS DE PAÑO -->

<div class="modal fade" id="modalVerPagosMayoresMesas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerPagosMayoresMesas" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerPagosMayoresMesas" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO PAGO MAYOR DE MESA DE PAÑO</h3>
                  </div>

                  <div  id="colapsadoVerPagosMayoresMesas" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_PagosMayoresMesas" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_PagosMayoresMesas" readonly>
                       </div>
                     </div>
                   </br>
                     <div class="row">
                       <div class="col-md-4">
                         <h5>Cantidad de pagos</h5>
                         <textarea class="form-control" id="ver_cant_pagos_PagosMayoresMesas" readonly></textarea>
                       </div>
                       <div class="col-md-4">
                         <h5>Importe en pesos</h5>
                         <input type="text" class="form-control" id="ver_importe_pesos_PagosMayoresMesas" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Importe en dolares</h5>
                         <input type="text" class="form-control" id="ver_importe_usd_PagosMayoresMesas" readonly>
                       </div>

                     </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR REPORTE DE OPERACIONES Y LAVADO DE ACTIVOS -->

<div class="modal fade" id="modalCargarReporteYLavado" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearReporteYLavado" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearReporteYLavado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE REPORTE DE OPERACION Y LAVADO DE ACTIVOS</h3>
                    </div>

                    <div  id="colapsadoCrearReporteYLavado" class="collapse in">

            <form id="formNuevoRegistroReporteYLavado" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="ReporteYLavado_modo" name="ReporteYLavado_modo" value="create">
                  <input type="hidden" id="id_registroReporteYLavado" name="id_registroReporteYLavado" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaReporteYLavado' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_ReporteYLavado" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_ReporteYLavado" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3" >
                    <h5>Casino</h5>
                    <select name="casinoReporteYLavado" class="form-control" id="casinoReporteYLavado">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <br/>

                <div class="row">
                  <div class="col-md-5" >
                    <h5>Reporte Sistematico de Operaciones</h5>
                    <input type="text" class="form-control" name="reporte_sistematico_ReporteYLavado" id="reporte_sistematico_ReporteYLavado" >

                  </div>
                  <div class="col-md-7">
                    <h5>Reporte de Operaciones Sospechosas y Financiamiento del Terrorismo</h5>
                    <input type="text" class="form-control" name="reporte_operaciones_ReporteYLavado" id="reporte_operaciones_ReporteYLavado" >
                  </div>
              </div>

              </br>


<div class="row">
        <div class="col-md-3">
          <h5>Archivo</h5>
          </div>
        </div>
        <div class="row">
            <div class="form-group">
            <div class="input-group col-md-8">
              <span class="input-group-btn">
                <button class="btn btn-primary" type="button" id="btnPickReporteYLavado">
                  <i class="fa fa-folder-open"></i> Examinar…
                </button>
              </span>
              <input type="text" id="fileNameReporteYLavado" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
              <input type="file" id="uploadReporteYLavado" name="uploadReporteYLavado[]" multiple style="display:none;">
            </div>

            <div class="table-responsive" id="uploadsReporteYLavadoWrap" style="margin-top:8px; display:none;">
              <table class="table table-striped table-bordered table-condensed" id="uploadsReporteYLavadoTable">
                <thead>
                  <tr>
                    <th style="width:48px;">#</th>
                    <th>Archivo</th>
                    <th style="width:200px;">Tamaño</th>
                    <th style="width:70px;">Acción</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <div id="uploadsReporteYLavadoContainer" style="display:none;"></div>
            </div>
    </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroReporteYLavado" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR REPORTE DE OPERACIONES Y LAVADO DE ACTIVOS -->

<div class="modal fade" id="modalEliminarReporteYLavado" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro de reportes de operaciones y lavado de activos?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarReporteYLavado" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER OPERACIONES Y LAVADO DE ACTIVOS no se usa-->

<div class="modal fade" id="modalVerReporteYLavado" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerReporteYLavado" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerReporteYLavado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO PAGO MAYOR DE MESA DE PAÑO</h3>
                  </div>

                  <div  id="colapsadoVerReporteYLavado" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_ReporteYLavado" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_ReporteYLavado" readonly>
                       </div>
                     </div>
                   </br>
                     <div class="row">
                       <div class="col-md-4">
                         <h5>Cantidad de pagos</h5>
                         <textarea class="form-control" id="ver_cant_pagos_ReporteYLavado" readonly></textarea>
                       </div>
                       <div class="col-md-4">
                         <h5>Importe en pesos</h5>
                         <input type="text" class="form-control" id="ver_importe_pesos_ReporteYLavado" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Importe en dolares</h5>
                         <input type="text" class="form-control" id="ver_importe_usd_ReporteYLavado" readonly>
                       </div>

                     </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR REGISTROS CONTABLES -->

<div class="modal fade" id="modalCargarRegistrosContables" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearRegistrosContables" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearRegistrosContables" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE REGISTROS CONTABLES</h3>
                    </div>

                    <div  id="colapsadoCrearRegistrosContables" class="collapse in">

            <form id="formNuevoRegistroRegistrosContables" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="RegistrosContables_modo" name="RegistrosContables_modo" value="create">
                 <input type="hidden" id="id_registroRegistrosContables" name="id_registroRegistrosContables" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaRegistrosContables' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_RegistrosContables" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_RegistrosContables" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3" >
                    <h5>Casino</h5>
                    <select name="casinoRegistrosContables" class="form-control" id="casinoRegistrosContables">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <br/>

                <div class="row">
                  <div class="col-md-12">
                    <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">MTM</h4>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-5" >
                    <h5>En pesos</h5>
                    <input type="text" class="form-control" name="mtm_pesos_RegistrosContables" id="mtm_pesos_RegistrosContables" >

                  </div>
                  <div class="col-md-5">
                    <h5>En Dólares</h5>
                    <input type="text" class="form-control" name="mtm_usd_RegistrosContables" id="mtm_usd_RegistrosContables" >
                  </div>
              </div>
            </br>
              <div class="row">
                <div class="col-md-12">
                  <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">MP</h4>
                </div>
              </div>
              <div class="row">
                <div class="col-md-5" >
                  <h5>En pesos</h5>
                  <input type="text" class="form-control" name="mp_pesos_RegistrosContables" id="mp_pesos_RegistrosContables" >

                </div>
                <div class="col-md-5">
                  <h5>En Dólares</h5>
                  <input type="text" class="form-control" name="mp_usd_RegistrosContables" id="mp_usd_RegistrosContables" >
                </div>
            </div>
          </br>
            <div class="row">
              <div class="col-md-12">
                <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Bingo</h4>
              </div>
            </div>
            <div class="row">
              <div class="col-md-5" >
                <h5>En pesos</h5>
                <input type="text" class="form-control" name="bingo_RegistrosContables" id="bingo_RegistrosContables" >

              </div>
          </div>
        </br>
          <div class="row">
            <div class="col-md-12">
              <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Juego OnLine</h4>
            </div>
          </div>
          <div class="row">
            <div class="col-md-5" >
              <h5>En pesos</h5>
              <input type="text" class="form-control" name="jol_RegistrosContables" id="jol_RegistrosContables" >

            </div>
        </div>

        <div class="row">
          <div class="col-md-12">
            <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Totales</h4>
          </div>
        </div>
        <div class="row">
          <div class="col-md-5" >
            <h5>En Pesos</h5>
            <input type="text" class="form-control" name="total_RegistrosContables" id="total_RegistrosContables" >

          </div>
          <div class="col-md-5" >
            <h5>En Dólares</h5>
            <input type="text" class="form-control" name ="total_usd_RegistrosContables" id="total_usd_RegistrosContables" >

          </div>
      </div>

              </br>


<div class="row">
          <div class="col-md-3">
            <h5>Archivo</h5>
            </div>
          </div>
          <div class="row">
              <div class="form-group">
              <div class="input-group col-md-8">
                <span class="input-group-btn">
                  <button class="btn btn-primary" type="button" id="btnPickRegistrosContables">
                    <i class="fa fa-folder-open"></i> Examinar…
                  </button>
                </span>
                <input type="text" id="fileNameRegistrosContables" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                <input type="file" id="uploadRegistrosContables" name="uploadRegistrosContables[]" multiple style="display:none;">
              </div>

              <div class="table-responsive" id="uploadsRegistrosContablesWrap" style="margin-top:8px; display:none;">
                <table class="table table-striped table-bordered table-condensed" id="uploadsRegistrosContablesTable">
                  <thead>
                    <tr>
                      <th style="width:48px;">#</th>
                      <th>Archivo</th>
                      <th style="width:200px;">Tamaño</th>
                      <th style="width:70px;">Acción</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>

              <div id="uploadsRegistrosContablesContainer" style="display:none;"></div>
              </div>
      </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroRegistrosContables" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR REGISTRO CONTABLE -->

<div class="modal fade" id="modalEliminarRegistrosContables" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro contable?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarRegistrosContables" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER REGISTRO CONTABLE -->

<div class="modal fade" id="modalVerRegistrosContables" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerRegistrosContables" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerRegistrosContables" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO CONTABLE</h3>
                  </div>

                  <div  id="colapsadoVerRegistrosContables" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_RegistrosContables" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_RegistrosContables" readonly>
                       </div>
                     </div>
                   </br>
                   <div class="row">
                     <div class="col-md-10">
                       <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">MTM</h4>
                     </div>
                   </div>
                   <div class="row">
                     <div class="col-md-5" >
                       <h5>En pesos</h5>
                       <input type="text" class="form-control" id="ver_mtm_RegistrosContables" readonly >

                     </div>
                     <div class="col-md-5">
                       <h5>En Dólares</h5>
                       <input type="text" class="form-control" id="ver_mtm_usd_RegistrosContables" readonly>
                     </div>
                 </div>
   </br>
                 <div class="row">
                   <div class="col-md-10">
                     <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">MP</h4>
                   </div>
                 </div>
                 <div class="row">
                   <div class="col-md-5" >
                     <h5>En pesos</h5>
                     <input type="text" class="form-control" id="ver_mp_RegistrosContables" readonly>

                   </div>
                   <div class="col-md-5">
                     <h5>En Dólares</h5>
                     <input type="text" class="form-control" id="ver_mp_usd_RegistrosContables" readonly>
                   </div>
               </div>
   </br>
               <div class="row">
                 <div class="col-md-10">
                   <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Bingo</h4>
                 </div>
               </div>
               <div class="row">
                 <div class="col-md-5" >
                   <h5>En pesos</h5>
                   <input type="text" class="form-control" id="ver_bingo_RegistrosContables" readonly>

                 </div>
             </div>
           </br>
             <div class="row">
               <div class="col-md-10">
                 <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Juego OnLine</h4>
               </div>
             </div>
             <div class="row">
               <div class="col-md-5" >
                 <h5>En pesos</h5>
                 <input type="text" class="form-control" id="ver_jol_RegistrosContables" readonly>

               </div>
           </div>
         </br>
         <div class="row">
           <div class="col-md-10">
             <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Totales</h4>
           </div>
         </div>
         <div class="row">
           <div class="col-md-5" >
             <h5>En Pesos</h5>
             <input type="text" class="form-control" id="ver_total_RegistrosContables" readonly>

           </div>
           <div class="col-md-5" >
             <h5>En Dólares</h5>
             <input type="text" class="form-control" id="ver_total_usd_RegistrosContables" readonly>

           </div>
       </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR APORTES PATRONALES -->

<div class="modal fade" id="modalCargarAportesPatronales" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearAportesPatronales" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearAportesPatronales" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE APORTE PATRONAL</h3>
                    </div>

                    <div  id="colapsadoCrearAportesPatronales" class="collapse in">

            <form id="formNuevoRegistroAportesPatronales" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="AportesPatronales_modo" name="AportesPatronales_modo" value="create">
                  <input type="hidden" id="id_registroAportesPatronales" name="id_registroAportesPatronales" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaAportesPatronales' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_AportesPatronales" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_AportesPatronales" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3" >
                    <h5>Casino</h5>
                    <select name="casinoAportesPatronales" class="form-control" id="casinoAportesPatronales">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-5">
                    <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
                    <div class='input-group date' id='fechaAportesPatronalesPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                        <input name="fecha_AportesPatronalesPres" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_AportesPatronalesPres" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                </div>
                <br/>


                <div class="row">
                  <div class="col-md-4">
                    <h5>FECHA DE PAGO</h5>
                    <div class='input-group date' id='fecha_pagoAportesPatronales' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                        <input name="fecha_pago_AportesPatronales" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_pago_AportesPatronales" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <h5>Cantidad de Empleados</h5>
                    <input type="text" class="form-control" name="cant_empleados_AportesPatronales" id="cant_empleados_AportesPatronales" >
                  </div>
                  <div class="col-md-4">
                    <h5>Monto Pagado</h5>
                    <input type="text" class="form-control" name="monto_pagado_AportesPatronales" id="monto_pagado_AportesPatronales" >
                  </div>
              </div>

              </br>
              <div class="row">
                <div class="col-md-6">
                  <h5>Observaciones</h5>
                  <textarea class="form-control" maxlength="4000" name="obs_AportesPatronales" id="obs_AportesPatronales" rows="3"></textarea>
                </div>
              </div>
            </br>

<div class="row">
      <div class="col-md-3">
        <h5>Archivo</h5>
        </div>
      </div>
      <div class="row">
          <div class="form-group">
          <div class="input-group col-md-8">
            <span class="input-group-btn">
              <button class="btn btn-primary" type="button" id="btnPickAportesPatronales">
                <i class="fa fa-folder-open"></i> Examinar…
              </button>
            </span>
            <input type="text" id="fileNameAportesPatronales" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
            <input type="file" id="uploadAportesPatronales" name="uploadAportesPatronales[]" multiple style="display:none;">
          </div>

          <div class="table-responsive" id="uploadsAportesPatronalesWrap" style="margin-top:8px; display:none;">
            <table class="table table-striped table-bordered table-condensed" id="uploadsAportesPatronalesTable">
              <thead>
                <tr>
                  <th style="width:48px;">#</th>
                  <th>Archivo</th>
                  <th style="width:200px;">Tamaño</th>
                  <th style="width:70px;">Acción</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div id="uploadsAportesPatronalesContainer" style="display:none;"></div>
          </div>
  </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroAportesPatronales" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR APORTE PATRONAL -->

<div class="modal fade" id="modalEliminarAportesPatronales" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el aporte patronal?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarAportesPatronales" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER APORTE PATRONAL -->

<div class="modal fade" id="modalVerAportesPatronales" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerAportesPatronales" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerAportesPatronales" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO APORTE PATRONAL</h3>
                  </div>

                  <div  id="colapsadoVerAportesPatronales" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_AportesPatronales" readonly>
                       </div>
                       <div class="col-md-3">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_AportesPatronales" readonly>
                       </div>
                       <div class="col-md-5">
                         <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
                         <input type='text' class="form-control" id="ver_fecha_AportesPatronalesPres" readonly/>
                       </div>
                     </div>
                     <br/>

                     <div class="row">
                       <div class="col-md-4">
                         <h5>FECHA DE PAGO</h5>
                             <input type='text' class="form-control" id="ver_fecha_pago_AportesPatronales" readonly/>
                       </div>
                       <div class="col-md-4">
                         <h5>Cantidad de Empleados</h5>
                         <input type="text" class="form-control" id="ver_cant_empleados_AportesPatronales" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Monto Pagado</h5>
                         <input type="text" class="form-control" id="ver_monto_pagado_AportesPatronales" readonly>
                       </div>
                   </div>

                   </br>
                   <div class="row">
                     <div class="col-md-6">
                       <h5>Observaciones</h5>
                       <textarea class="form-control" id="ver_obs_AportesPatronales" readonly rows="3"></textarea>
                     </div>
                   </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR PROMO TICKETS -->

<div class="modal fade" id="modalCargarPromoTickets" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearPromoTickets" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearPromoTickets" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE PROMO TICKET</h3>
                    </div>

                    <div  id="colapsadoCrearPromoTickets" class="collapse in">

            <form id="formNuevoRegistroPromoTickets" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="PromoTickets_modo" name="PromoTickets_modo" value="create">
                  <input type="hidden" id="id_registroPromoTickets" name="id_registroPromoTickets" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaPromoTickets' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_PromoTickets" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_PromoTickets" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3" >
                    <h5>Casino</h5>
                    <select name="casinoPromoTickets" class="form-control" id="casinoPromoTickets">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <br/>


                <div class="row">
                  <div class="col-md-5">
                    <h5>Cantidad</h5>
                    <input type="text" class="form-control" name="cant_PromoTickets" id="cant_PromoTickets" >
                  </div>
                  <div class="col-md-5">
                    <h5>Importe</h5>
                    <input type="text" class="form-control" name="importe_PromoTickets" id="importe_PromoTickets" >
                  </div>
              </div>

              </br>

<div class="row">
        <div class="col-md-3">
          <h5>Archivo</h5>
          </div>
        </div>
        <div class="row">
            <div class="form-group">
            <div class="input-group col-md-8">
              <span class="input-group-btn">
                <button class="btn btn-primary" type="button" id="btnPickPromoTickets">
                  <i class="fa fa-folder-open"></i> Examinar…
                </button>
              </span>
              <input type="text" id="fileNamePromoTickets" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
              <input type="file" id="uploadPromoTickets" name="uploadPromoTickets[]" multiple style="display:none;">
            </div>

            <div class="table-responsive" id="uploadsPromoTicketsWrap" style="margin-top:8px; display:none;">
              <table class="table table-striped table-bordered table-condensed" id="uploadsPromoTicketsTable">
                <thead>
                  <tr>
                    <th style="width:48px;">#</th>
                    <th>Archivo</th>
                    <th style="width:200px;">Tamaño</th>
                    <th style="width:70px;">Acción</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <div id="uploadsPromoTicketsContainer" style="display:none;"></div>
            </div>
    </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroPromoTickets" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR PROMO TICKETS -->

<div class="modal fade" id="modalEliminarPromoTickets" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el promoticket?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarPromoTickets" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER PROMO TICKETS -->

<div class="modal fade" id="modalVerPromoTickets" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerPromoTickets" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerPromoTickets" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO APORTE PATRONAL</h3>
                  </div>

                  <div  id="colapsadoVerPromoTickets" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_PromoTickets" readonly>
                       </div>
                       <div class="col-md-3">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_PromoTickets" readonly>
                       </div>
                       <div class="col-md-5">
                         <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
                         <input type='text' class="form-control" id="ver_fecha_PromoTicketsPres" readonly/>
                       </div>
                     </div>
                     <br/>

                     <div class="row">

                       <div class="col-md-4">
                         <h5>Cantidad de Empleados</h5>
                         <input type="text" class="form-control" id="ver_cant_empleados_PromoTickets" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Monto Pagado</h5>
                         <input type="text" class="form-control" id="ver_monto_pagado_PromoTickets" readonly>
                       </div>
                   </div>

                   </br>
                   <div class="row">
                     <div class="col-md-6">
                       <h5>Observaciones</h5>
                       <textarea class="form-control" id="ver_obs_PromoTickets" readonly rows="3"></textarea>
                     </div>
                   </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR POZO ACUMULADO LINKEADO E INDIVIDUAL -->

<div class="modal fade" id="modalCargarPozosAcumuladosLinkeados" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearPozosAcumuladosLinkeados" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearPozosAcumuladosLinkeados" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE POZO ACUMULADO LINKEADO E INDIVIDUAL</h3>
                    </div>

                    <div  id="colapsadoCrearPozosAcumuladosLinkeados" class="collapse in">

            <form id="formNuevoRegistroPozosAcumuladosLinkeados" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="PozosAcumuladosLinkeados_modo" name="PozosAcumuladosLinkeados_modo" value="create">
                  <input type="hidden" id="id_registroPozosAcumuladosLinkeados" name="id_registroPozosAcumuladosLinkeados" value="">

              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaPozosAcumuladosLinkeados' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_PozosAcumuladosLinkeados" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_PozosAcumuladosLinkeados" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3" >
                    <h5>Casino</h5>
                    <select name="casinoPozosAcumuladosLinkeados" class="form-control" id="casinoPozosAcumuladosLinkeados">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>

                </div>
                <br/>


                <div class="row">

                  <div class="col-md-6">
                    <h5>IMPORTE AL ÚLTIMO DÍA DE CADA MES</h5>
                    <input type="text" class="form-control" name="importe_PozosAcumuladosLinkeados" id="importe_PozosAcumuladosLinkeados" >
                  </div>

              </div>

            </br>

<div class="row">
      <div class="col-md-3">
        <h5>Archivo</h5>
        </div>
      </div>
      <div class="row">
          <div class="form-group">
          <div class="input-group col-md-8">
            <span class="input-group-btn">
              <button class="btn btn-primary" type="button" id="btnPickPozosAcumuladosLinkeados">
                <i class="fa fa-folder-open"></i> Examinar…
              </button>
            </span>
            <input type="text" id="fileNamePozosAcumuladosLinkeados" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
            <input type="file" id="uploadPozosAcumuladosLinkeados" name="uploadPozosAcumuladosLinkeados[]" multiple style="display:none;">
          </div>

          <div class="table-responsive" id="uploadsPozosAcumuladosLinkeadosWrap" style="margin-top:8px; display:none;">
            <table class="table table-striped table-bordered table-condensed" id="uploadsPozosAcumuladosLinkeadosTable">
              <thead>
                <tr>
                  <th style="width:48px;">#</th>
                  <th>Archivo</th>
                  <th style="width:200px;">Tamaño</th>
                  <th style="width:70px;">Acción</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div id="uploadsPozosAcumuladosLinkeadosContainer" style="display:none;"></div>
          </div>
  </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroPozosAcumuladosLinkeados" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR POZO ACUMULADO LINKEADO E INDIVIDUAL-->

<div class="modal fade" id="modalEliminarPozosAcumuladosLinkeados" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el ...?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarPozosAcumuladosLinkeados" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER POZOS ACUMULADOS LINKEADOS-->

<div class="modal fade" id="modalVerPozosAcumuladosLinkeados" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerPozosAcumuladosLinkeados" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerPozosAcumuladosLinkeados" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO APORTE PATRONAL</h3>
                  </div>

                  <div  id="colapsadoVerPozosAcumuladosLinkeados" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_PozosAcumuladosLinkeados" readonly>
                       </div>
                       <div class="col-md-3">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_PozosAcumuladosLinkeados" readonly>
                       </div>
                       <div class="col-md-5">
                         <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
                         <input type='text' class="form-control" id="ver_fecha_PozosAcumuladosLinkeadosPres" readonly/>
                       </div>
                     </div>
                     <br/>

                     <div class="row">
                       <div class="col-md-4">
                         <h5>FECHA DE PAGO</h5>
                             <input type='text' class="form-control" id="ver_fecha_pago_PozosAcumuladosLinkeados" readonly/>
                       </div>
                       <div class="col-md-4">
                         <h5>Cantidad de Empleados</h5>
                         <input type="text" class="form-control" id="ver_cant_empleados_PozosAcumuladosLinkeados" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Monto Pagado</h5>
                         <input type="text" class="form-control" id="ver_monto_pagado_PozosAcumuladosLinkeados" readonly>
                       </div>
                   </div>

                   </br>
                   <div class="row">
                     <div class="col-md-6">
                       <h5>Observaciones</h5>
                       <textarea class="form-control" id="ver_obs_PozosAcumuladosLinkeados" readonly rows="3"></textarea>
                     </div>
                   </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR CONTRIBUCION ENTE TURISTICO -->

<div class="modal fade" id="modalCargarContribEnteTuristico" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearContribEnteTuristico" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearContribEnteTuristico" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE CONTRIBUCION ENTE TURISTICO</h3>
                    </div>

                    <div  id="colapsadoCrearContribEnteTuristico" class="collapse in">

            <form id="formNuevoRegistroContribEnteTuristico" novalidate="" method="POST" autocomplete="off">

                  <input type="hidden" id="ContribEnteTuristico_modo" name="ContribEnteTuristico_modo" value="create">
                  <input type="hidden" id="id_registroContribEnteTuristico" name="id_registroContribEnteTuristico" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-3">
                    <h5>Casino</h5>
                    <select name="casinoContribEnteTuristico" class="form-control" id="casinoContribEnteTuristico" readonly>
                      <option value="3">Rosario</option>
                    </select>
                  </div>
              </div>
            </br>
                <div class="row">
                  <div class="col-md-3">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaContribEnteTuristico' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_ContribEnteTuristico" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_ContribEnteTuristico" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA Y PAGO</h5>
                    <div class='input-group date' id='fechaContribEnteTuristicoPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                        <input name="fecha_ContribEnteTuristicoPres" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_ContribEnteTuristicoPres" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3">
                    <h5>FECHA DE VENCIMIENTO</h5>
                    <div class='input-group date' id='fecha_vencContribEnteTuristico' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                        <input name="fecha_venc_ContribEnteTuristico" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_venc_ContribEnteTuristico" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                </div>
                <br/>


                <div class="row">

                  <div class="col-md-4">
                    <h5>Base Imponible</h5>
                    <input type="text" class="form-control" name="base_imponible_ContribEnteTuristico" id="base_imponible_ContribEnteTuristico" >
                  </div>
                  <div class="col-md-4">
                    <h5>Alicuota (%)</h5>
                    <input type="text" class="form-control" name="alicuota_ContribEnteTuristico" id="alicuota_ContribEnteTuristico" >
                  </div>
                  <div class="col-md-4">
                    <h5>Impuesto determinado</h5>
                    <input type="text" class="form-control" name="impuesto_determinado_ContribEnteTuristico" id="impuesto_determinado_ContribEnteTuristico">
                  </div>
              </div>

              </br>
              <div class="row">
                <div class="col-md-6">
                  <h5>Observaciones</h5>
                  <textarea class="form-control" maxlength="4000" name="obs_ContribEnteTuristico" id="obs_ContribEnteTuristico" rows="3"></textarea>
                </div>
                <div class="col-md-6">
                  <h5>MONTO PAGADO</h5>
                  <input type="text" class="form-control" name="monto_pagado_ContribEnteTuristico" id="monto_pagado_ContribEnteTuristico">
                </div>
              </div>
            </br>

<div class="row">
      <div class="col-md-3">
        <h5>Archivo</h5>
        </div>
      </div>
      <div class="row">
          <div class="form-group">
          <div class="input-group col-md-8">
            <span class="input-group-btn">
              <button class="btn btn-primary" type="button" id="btnPickContribEnteTuristico">
                <i class="fa fa-folder-open"></i> Examinar…
              </button>
            </span>
            <input type="text" id="fileNameContribEnteTuristico" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
            <input type="file" id="uploadContribEnteTuristico" name="uploadContribEnteTuristico[]" multiple style="display:none;">
          </div>

          <div class="table-responsive" id="uploadsContribEnteTuristicoWrap" style="margin-top:8px; display:none;">
            <table class="table table-striped table-bordered table-condensed" id="uploadsContribEnteTuristicoTable">
              <thead>
                <tr>
                  <th style="width:48px;">#</th>
                  <th>Archivo</th>
                  <th style="width:200px;">Tamaño</th>
                  <th style="width:70px;">Acción</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div id="uploadsContribEnteTuristicoContainer" style="display:none;"></div>
          </div>
  </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroContribEnteTuristico" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR CONTRIBUCION ENTE TURISTICO -->

<div class="modal fade" id="modalEliminarContribEnteTuristico" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro sobre la contribución al ente turistico?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarContribEnteTuristico" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER CONTRIBUCION ENTE TURISTICO -->

<div class="modal fade" id="modalVerContribEnteTuristico" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerContribEnteTuristico" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerContribEnteTuristico" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO CONTRIBUCIÓN ENTE TURISTICO</h3>
                  </div>

                  <div  id="colapsadoVerContribEnteTuristico" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_ContribEnteTuristico" readonly>
                       </div>
                       <div class="col-md-3">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_ContribEnteTuristico" readonly>
                       </div>

                     </div>
                     <br/>

                     <div class="row">
                       <div class="col-md-6">
                         <h5>FECHA DE VENCIMIENTO</h5>
                             <input type='text' class="form-control" id="ver_fecha_venc_ContribEnteTuristico" readonly/>
                       </div>
                       <div class="col-md-6">
                         <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA Y PAGO</h5>
                         <input type='text' class="form-control" id="ver_fecha_pres_ContribEnteTuristicoPres" readonly/>
                       </div>
                   </div>

                 </br>

                   <div class="row">
                     <div class="col-md-4">
                       <h5>Base Imponible</h5>
                       <input type="text" class="form-control" id="ver_base_imponible_ContribEnteTuristico" readonly>
                     </div>
                     <div class="col-md-4">
                       <h5>Alicuota (%)</h5>
                       <input type="text" class="form-control" id="ver_alicuota_ContribEnteTuristico" readonly>
                     </div>
                     <div class="col-md-4">
                       <h5>Impuesto Determinado</h5>
                       <input type="text" class="form-control" id="ver_impuesto_determinado_ContribEnteTuristico" readonly>
                   </div>
                 </div>
                   </br>
                   <div class="row">
                     <div class="col-md-6">
                       <h5>Observaciones</h5>
                       <textarea class="form-control" id="ver_obs_ContribEnteTuristico" readonly rows="3"></textarea>
                     </div>
                     <div class="col-md-6">
                       <h5>Monto Pagado</h5>
                       <input type="text" class="form-control" id="ver_monto_pagado_ContribEnteTuristico" readonly>
                   </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
            </div>
</div>

<!-- MODAL CARGAR RRHH -->

<div class="modal fade" id="modalCargarRRHH" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearRRHH" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearRRHH" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE RRHH</h3>
                    </div>

                    <div  id="colapsadoCrearRRHH" class="collapse in">

            <form id="formNuevoRegistroRRHH" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="RRHH_modo" name="RRHH_modo" value="create">
                  <input type="hidden" id="id_registroRRHH" name="id_registroRRHH" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaRRHH' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_RRHH" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_RRHH" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3" >
                    <h5>Casino</h5>
                    <select name="casinoRRHH" class="form-control" id="casinoRRHH">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <br/>


                <div class="row">

                  <div class="col-md-4">
                    <h5>Personal al Inicio</h5>
                    <input type="text" class="form-control" name="personal_inicio_RRHH" id="personal_inicio_RRHH" >
                  </div>
                </div>

              </br>
              <div class="row">

                <div class="col-md-4">
                  <h5>Altas del mes</h5>
                  <input type="text" class="form-control" name="altas_RRHH" id="altas_RRHH" >
                </div>
                <div class="col-md-4">
                  <h5>Bajas del mes</h5>
                  <input type="text" class="form-control" name="bajas_RRHH" id="bajas_RRHH" >
                </div>
            </div>
          </br>

            <div class="row">
                    <div class="col-md-4">
                      <h5>Personal al Final</h5>
                      <input type="text" class="form-control" name="personal_final_RRHH" id="personal_final_RRHH" >
                  </div>
            </div>
            </br>
          
            <div class="row">

              <div class="col-md-4">
                <h5>Personal según la nómina</h5>
                <input type="text" class="form-control" name="personal_nomina_RRHH" id="personal_nomina_RRHH" >
              </div>
              <div class="col-md-4">
                <h5>Diferencia</h5>
                <input type="text" class="form-control" name="diferencia_RRHH" id="diferencia_RRHH" >
              </div>
          </div>
        </br>
          <div class="row">

            <div class="col-md-4">
              <h5>Tercerizados</h5>
              <input type="text" class="form-control" name="tercerizados_RRHH" id="tercerizados_RRHH" >
            </div>
            <div class="col-md-4">
              <h5>Total</h5>
              <input type="text" class="form-control" name="total_personal_RRHH" id="total_personal_RRHH" >
            </div>
            <div class="col-md-4">
              <h5>Ofertado/Adjudicado
                <i class="fa fa-exclamation-triangle text-danger"
                   data-toggle="popover"
                   data-html="true"
                   data-placement="right"
                   title="¡Atención!"
                   data-content="Cantidad mínima de empleados por oferta/pliego.">
                </i>
              </h5>
              <input type="text" class="form-control" name="ofertado_adjudicado_RRHH" id="ofertado_adjudicado_RRHH" readonly>
            </div>
        </div>
        </br>
        <div class="row">

          <div class="col-md-4">
            <h5>Cantidad de personal Ludico</h5>
            <input type="text" class="form-control" name="ludicos_RRHH" id="ludicos_RRHH" >
          </div>
          <div class="col-md-4">
            <h5>Cantidad de personal no ludico</h5>
            <input type="text" class="form-control" name="no_ludicos_RRHH" id="no_ludicos_RRHH" >
          </div>
          <div class="col-md-4">
            <h5>Total de personal</h5>
            <input type="text" class="form-control" name="total_ludicos_RRHH" id="total_ludicos_RRHH" >
          </div>
      </div>
      </br>
      <div class="row">

        <div class="col-md-4">
          <h5>Porcentaje de personal Ludico</h5>
          <input type="text" class="form-control" name="porcentaje_ludicos_RRHH" id="porcentaje_ludicos_RRHH" >
        </div>
        <div class="col-md-4">
          <h5>Porcentaje de personal no ludico</h5>
          <input type="text" class="form-control" name="porcentaje_no_ludicos_RRHH" id="porcentaje_no_ludicos_RRHH" >
        </div>
        <div class="col-md-4">
          <h5>Porcentaje total de personal</h5>
          <input type="text" class="form-control" name="total_porcentaje_ludicos_RRHH" id="total_porcentaje_ludicos_RRHH" >
        </div>
    </div>
    </br>
        <div class="row">

          <div class="col-md-4">
            <h5>Cantidad de personal Ludico viviendo en Santa Fe</h5>
            <input type="text" class="form-control" name="ludicos_vivivendo_RRHH" id="ludicos_vivivendo_RRHH" >
          </div>
          <div class="col-md-4">
            <h5>Cantidad de personal no ludico viviendo en Santa Fe</h5>
            <input type="text" class="form-control" name="no_ludicos_viviendo_RRHH" id="no_ludicos_viviendo_RRHH" >
          </div>
          <div class="col-md-4">
            <h5>Total de personal viviendo en Santa Fe</h5>
            <input type="text" class="form-control" name="total_ludicos_viviendo_RRHH" id="total_ludicos_viviendo_RRHH" >
          </div>
      </div>
    </br>
        <div class="row">

          <div class="col-md-4">
            <h5>Porcentaje de personal Ludico domiciliado en Santa FE</h5>
            <input type="text" class="form-control" name="porcentaje_ludicos_sf_RRHH" id="porcentaje_ludicos_sf_RRHH" >
          </div>
          <div class="col-md-4">
            <h5>Porcentaje de personal no ludico domiciliado en Santa FE</h5>
            <input type="text" class="form-control" name="porcentaje_no_ludicos_sf_RRHH" id="porcentaje_no_ludicos_sf_RRHH" >
          </div>
          <div class="col-md-4">
            <h5>Porcentaje total de personal viviendo en santa Fe
              <i class="fa fa-exclamation-triangle text-danger"
                 data-toggle="popover"
                 data-html="true"
                 data-placement="right"
                 title="¡Atención!"
                 data-content="Cap. 18 del pliego: 80% del personal lúdico debe ser nativo o residente por mínimo 1 año.">
              </i>
            </h5>
            <input type="text" class="form-control" name="porcentaje_total_sf_RRHH" id="porcentaje_total_sf_RRHH" >
          </div>
        </div>
  </br>
          <div class="row">

            <div class="col-md-4">
              <h5>Diferencia entre nómina y DDJJ por tipo de personal</h5>
              <input type="text" class="form-control" name="dif_nomina_RRHH" id="dif_nomina_RRHH" >
            </div>

          </div>
          </br>
          <div class="row">
    <div class="col-md-3">
      <h5>Archivo</h5>
      </div>
    </div>
    <div class="row">
        <div class="form-group">
        <div class="input-group col-md-8">
          <span class="input-group-btn">
            <button class="btn btn-primary" type="button" id="btnPickRRHH">
              <i class="fa fa-folder-open"></i> Examinar…
            </button>
          </span>
          <input type="text" id="fileNameRRHH" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
          <input type="file" id="uploadRRHH" name="uploadRRHH[]" multiple style="display:none;">
        </div>

        <div class="table-responsive" id="uploadsRRHHWrap" style="margin-top:8px; display:none;">
          <table class="table table-striped table-bordered table-condensed" id="uploadsRRHHTable">
            <thead>
              <tr>
                <th style="width:48px;">#</th>
                <th>Archivo</th>
                <th style="width:200px;">Tamaño</th>
                <th style="width:70px;">Acción</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <div id="uploadsRRHHContainer" style="display:none;"></div>
        </div>
</div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroRRHH" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR RRHH -->

<div class="modal fade" id="modalEliminarRRHH" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro de RRHH?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarRRHH" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>


<!-- MODAL PELIGRO ADJUDICADOS RRHH -->

<div class="modal fade" id="modalAdjudicadosRRHH" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="personal-adjudicados-RRHH"></strong>
                          </br>
                            <strong id="viviendo-adjudicados-RRHH"></strong>

                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" data-dismiss="modal">CERRAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER  RRHH -->

<div class="modal fade" id="modalVerRRHH" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerRRHH" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerRRHH" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO APORTE PATRONAL</h3>
                  </div>

                  <div  id="colapsadoVerRRHH" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_RRHH" readonly>
                       </div>
                       <div class="col-md-3">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_RRHH" readonly>
                       </div>
                     </div>
                     <br/>

                     <div class="row">

                       <div class="col-md-4">
                         <h5>Personal al Inicio</h5>
                         <input type="text" class="form-control" name="personal_inicio_RRHH" id="ver_personal_inicio_RRHH" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Personal al Final</h5>
                         <input type="text" class="form-control" name="personal_final_RRHH" id="ver_personal_final_RRHH" readonly>
                       </div>
                   </div>

                   </br>
                   <div class="row">

                     <div class="col-md-4">
                       <h5>Altas del mes</h5>
                       <input type="text" class="form-control" name="altas_RRHH" id="ver_altas_RRHH" readonly>
                     </div>
                     <div class="col-md-4">
                       <h5>Bajas del mes</h5>
                       <input type="text" class="form-control" name="bajas_RRHH" id="ver_bajas_RRHH" readonly>
                     </div>
                 </div>
               </br>
                 <div class="row">

                   <div class="col-md-4">
                     <h5>Personal según la nómina</h5>
                     <input type="text" class="form-control" name="personal_nomina_RRHH" id="ver_personal_nomina_RRHH" readonly>
                   </div>
                   <div class="col-md-4">
                     <h5>Diferencia</h5>
                     <input type="text" class="form-control" name="diferencia_RRHH" id="ver_diferencia_RRHH" readonly>
                   </div>
               </div>
             </br>
               <div class="row">

                 <div class="col-md-4">
                   <h5>Tercerizados</h5>
                   <input type="text" class="form-control" name="tercerizados_RRHH" id="ver_tercerizados_RRHH" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Total</h5>
                   <input type="text" class="form-control" name="total_personal_RRHH" id="ver_total_personal_RRHH" readonly>
                 </div>
                 <div class="col-md-4">
                   <h5>Ofertado/Adjudicado</h5>
                   <input type="text" class="form-control" name="ofertado_adjudicado_RRHH" id="ver_ofertado_adjudicado_RRHH" readonly>
                 </div>
             </div>
             </br>
             <div class="row">

               <div class="col-md-4">
                 <h5>Cantidad de personal Ludico</h5>
                 <input type="text" class="form-control" name="ludicos_RRHH" id="ver_ludicos_RRHH" readonly>
               </div>
               <div class="col-md-4">
                 <h5>Cantidad de personal no ludico</h5>
                 <input type="text" class="form-control" name="no_ludicos_RRHH" id="ver_no_ludicos_RRHH" readonly>
               </div>
               <div class="col-md-4">
                 <h5>Total de personal</h5>
                 <input type="text" class="form-control" name="total_ludicos_RRHH" id="ver_total_ludicos_RRHH" readonly>
               </div>
           </div>
           </br>
           <div class="row">

             <div class="col-md-4">
               <h5>Porcentaje de personal Ludico</h5>
               <input type="text" class="form-control" name="porcentaje_ludicos_RRHH" id="ver_porcentaje_ludicos_RRHH" readonly>
             </div>
             <div class="col-md-4">
               <h5>Porcentaje de personal no ludico</h5>
               <input type="text" class="form-control" name="porcentaje_no_ludicos_RRHH" id="ver_porcentaje_no_ludicos_RRHH" readonly>
             </div>
             <div class="col-md-4">
               <h5>Porcentaje total de personal</h5>
               <input type="text" class="form-control" name="total_porcentaje_ludicos_RRHH" id="ver_total_porcentaje_ludicos_RRHH" readonly>
             </div>
         </div>
         </br>

          <div class="row">
            <div class="col-md-4">
              <h5>Cantidad de personal ludico viviendo en santa fe</h5>
              <input type="text" class="form-control" name="ver_ludicos_viviendo_RRHH" id="ver_ludicos_viviendo_RRHH" readonly>
            </div>
            <div class="col-md-4">
              <h5>Cantidad de personal no ludico viviendo en santa fe</h5>
              <input type="text" class="form-control" name="ver_no_ludicos_viviendo_RRHH" id="ver_no_ludicos_viviendo_RRHH"  readonly>
            </div>
            <div class="col-md-4">
              <h5>TOTAL de personal viviendo en santa fe</h5>
              <input type="text" class="form-control" name="ver_total_viviendo_RRHH" id="ver_total_viviendo_RRHH" readonly>
            </div>
          </div>
        </br>
             <div class="row">

               <div class="col-md-4">
                 <h5>Porcentaje de personal Ludico domiciliado en Santa FE</h5>
                 <input type="text" class="form-control" name="porcentaje_ludicos_sf_RRHH" id="ver_porcentaje_ludicos_sf_RRHH" readonly>
               </div>
               <div class="col-md-4">
                 <h5>Porcentaje de personal no ludico domiciliado en Santa FE</h5>
                 <input type="text" class="form-control" name="porcentaje_no_ludicos_sf_RRHH" id="ver_porcentaje_no_ludicos_sf_RRHH" readonly>
               </div>
               <div class="col-md-4">
                 <!-- emoji invisible para que el total este alineado con los otros input-->
                 <h5>Porcentaje Total de personal viviendo en Santa Fe</h5>

                 <input type="text" class="form-control" name="ver_porcentaje_total_sf_RRHH" id="ver_porcentaje_total_sf_RRHH" readonly>
               </div>
             </div>
       </br>
               <div class="row">

                 <div class="col-md-4">
                   <h5>Diferencia entre nómina y DDJJ por tipo de personal</h5>
                   <input type="text" class="form-control" name="dif_nomina_RRHH" id="ver_dif_nomina_RRHH" readonly>
                 </div>

               </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR ANTICIPO GANANCIAS -->

<div class="modal fade" id="modalCargarGanancias" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearGanancias" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearGanancias" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE GANANCIAS</h3>
                    </div>

                    <div  id="colapsadoCrearGanancias" class="collapse in">

            <form id="formNuevoRegistroGanancias" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="Ganancias_modo" name="Ganancias_modo" value="create">
                 <input type="hidden" id="id_registroGanancias" name="id_registroGanancias" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4" >
                    <h5>Casino</h5>
                    <select name="casinoGanancias" class="form-control" id="casinoGanancias">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4">
                    <h5>Período al que pertenece</h5>
                    <div class='input-group date' id='fechaGananciasPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                        <input maxlength="4" name="fecha_GananciasPres" type='text' class="form-control" placeholder="yyyy" id="fecha_GananciasPres" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                </div>
                <br/>


                <div class="row">
                  <div class="col-md-4">
                    <h5>Número de Anticipo</h5>
                    <input type="text" class="form-control" name="nro_anticipo_Ganancias" id="nro_anticipo_Ganancias" >

                  </div>
                  <div class="col-md-4">
                    <h5>Anticipo</h5>
                    <input type="text" class="form-control" name="anticipo_Ganancias" id="anticipo_Ganancias" >
                  </div>

                </div>
              </br>
                <div class="row">
                  <div class="col-md-4">
                    <h5>Abonado</h5>
                    <input type="text" class="form-control" name="abonado_Ganancias" id="abonado_Ganancias" >
                  </div>
                  <div class="col-md-4">
                    <h5>Computa contra impuestos</h5>
                    <input type="text" class="form-control" name="computa_Ganancias" id="computa_Ganancias" >
                  </div>
                  <div class="col-md-4">
                    <h5>DIferencia con anticipo</h5>
                    <input type="text" class="form-control" name="diferencia_Ganancias" id="diferencia_Ganancias" >
                  </div>
              </div>

              </br>
              <div class="row">
                <div class="col-md-6">
                  <h5>FECHA DE PAGO</h5>
                  <div class='input-group date' id='fecha_pagoGanancias' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                      <input name="fecha_pago_Ganancias" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_pago_Ganancias" autocomplete="off" style="background-color: rgb(255,255,255);" />
                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
              </div>
                <div class="col-md-6">
                  <h5>Observaciones</h5>
                  <textarea class="form-control" maxlength="4000" name="obs_Ganancias" id="obs_Ganancias" rows="3"></textarea>
                </div>
              </div>
            </br>

            <div class="row">
              <div class="col-md-3">
                <h5>Archivo</h5>
                </div>
              </div>
              <div class="row">
                  <div class="form-group">
                  <div class="input-group col-md-8">
                    <span class="input-group-btn">
                      <button class="btn btn-primary" type="button" id="btnPickGanancias">
                        <i class="fa fa-folder-open"></i> Examinar…
                      </button>
                    </span>
                    <input type="text" id="fileNameGanancias" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                    <input type="file" id="uploadGanancias" name="uploadGanancias[]" multiple style="display:none;">
                  </div>

                  <div class="table-responsive" id="uploadsGananciasWrap" style="margin-top:8px; display:none;">
                    <table class="table table-striped table-bordered table-condensed" id="uploadsGananciasTable">
                      <thead>
                        <tr>
                          <th style="width:48px;">#</th>
                          <th>Archivo</th>
                          <th style="width:200px;">Tamaño</th>
                          <th style="width:70px;">Acción</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                  </div>

                  <div id="uploadsGananciasContainer" style="display:none;"></div>
                  </div>
                </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroGanancias" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR ANTICIPO GANANCIAS -->

<div class="modal fade" id="modalEliminarGanancias" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el anticipo de Ganancias?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarGanancias" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER ANTICIPO GANANCIAS -->

<div class="modal fade" id="modalVerGanancias" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerGanancias" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerGanancias" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO GANANCIAS</h3>
                  </div>

                  <div  id="colapsadoVerGanancias" class="collapse in">
                    <div class="modal-body">


                     <div class="row">
                       <div class="col-md-4" >
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_Ganancias" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Período al que pertenece</h5>
                           <input name="fecha_GananciasPres" type='text' class="form-control" id="ver_fecha_GananciasPres" readonly/>
                       </div>
                     </div>
                   </br>
                     <div class="row">
                       <div class="col-md-4">
                         <h5>Número de Anticipo</h5>
                         <input type="text" class="form-control" name="nro_anticipo_Ganancias" readonly id="ver_nro_anticipo_Ganancias" >

                       </div>
                       <div class="col-md-4">
                         <h5>Anticipo</h5>
                         <input type="text" class="form-control" name="anticipo_Ganancias" readonly id="ver_anticipo_Ganancias" >
                       </div>
                     </div>
                     <br/>

                     <div class="row">

                       <div class="col-md-4">
                         <h5>Abonado</h5>
                         <input type="text" class="form-control" name="abonado_Ganancias" readonly id="ver_abonado_Ganancias" >
                       </div>
                       <div class="col-md-4">
                         <h5>Computa contra impuestos</h5>
                         <input type="text" class="form-control" name="computa_Ganancias" readonly id="ver_computa_Ganancias" >
                       </div>
                       <div class="col-md-4">
                         <h5>Diferencia</h5>
                         <input type="text" class="form-control" name="diferencia_Ganancias" readonly id="ver_diferencia_Ganancias" >
                       </div>
                   </div>

                   </br>
                   <div class="row">
                     <div class="col-md-6">
                       <h5>FECHA DE PAGO</h5>
                       <input name="fecha_pago_Ganancias" type='text' class="form-control" id="ver_fecha_pago_Ganancias" readonly/>

                   </div>
                     <div class="col-md-6">
                       <h5>Observaciones</h5>
                       <textarea class="form-control" maxlength="4000" name="obs_Ganancias" id="ver_obs_Ganancias" readonly rows="3"></textarea>
                     </div>
                   </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR PERIODO GANANCIAS -->

<div class="modal fade" id="modalCargarGanancias_periodo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearGanancias_periodo" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearGanancias_periodo" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE PERÍODO FISCAL - GANANCIAS</h3>
                    </div>

                    <div  id="colapsadoCrearGanancias_periodo" class="collapse in">

            <form id="formNuevoRegistroGanancias_periodo" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="Ganancias_periodo_modo" name="Ganancias_periodo_modo" value="create">
                 <input type="hidden" id="id_registroGanancias_periodo" name="id_registroGanancias_periodo" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-6" >
                    <h5>Casino</h5>
                    <select name="casinoGanancias_periodo" class="form-control" id="casinoGanancias_periodo">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6">
                    <h5>Período al que pertenece</h5>
                    <div class='input-group date' id='fechaGanancias_periodoPres' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                        <input maxlength="4" name="fecha_Ganancias_periodoPres" type='text' class="form-control" placeholder="yyyy" id="fecha_Ganancias_periodoPres" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                </div>
                <br/>

                <div class="row">
                  <div class="col-md-6">
                    <h5>Impuesto a pagar/saldo a favor</h5>
                    <input type="text" class="form-control" name="saldo_Ganancias_periodo" id="saldo_Ganancias_periodo">
                  </div>
                  <div class="col-md-6">
                    <h5>FECHA DE PRESENTACIÓN</h5>
                    <div class='input-group date' id='fecha_pres_Ganancias_periodo' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                        <input name="fecha_pres_Ganancias_periodo" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_pago_Ganancias_periodo" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                </div>

                </div>


              </br>
              <div class="row">
                <div class="col-md-6">
                  <h5>Forma de Pago</h5>
                  <textarea class="form-control" maxlength="4000" name="forma_pago_Ganancias_periodo" id="forma_pago_Ganancias_periodo" rows="3"></textarea>
                </div>
                <div class="col-md-6">
                  <h5>Observaciones</h5>
                  <textarea class="form-control" maxlength="4000" name="obs_Ganancias_periodo" id="obs_Ganancias_periodo" rows="3"></textarea>
                </div>
              </div>
            </br>


            <div class="row">
          <div class="col-md-3">
            <h5>Archivo</h5>
            </div>
          </div>
          <div class="row">
              <div class="form-group">
              <div class="input-group col-md-8">
                <span class="input-group-btn">
                  <button class="btn btn-primary" type="button" id="btnPickGanancias_periodo">
                    <i class="fa fa-folder-open"></i> Examinar…
                  </button>
                </span>
                <input type="text" id="fileNameGanancias_periodo" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                <input type="file" id="uploadGanancias_periodo" name="uploadGanancias_periodo[]" multiple style="display:none;">
              </div>

              <div class="table-responsive" id="uploadsGanancias_periodoWrap" style="margin-top:8px; display:none;">
                <table class="table table-striped table-bordered table-condensed" id="uploadsGanancias_periodoTable">
                  <thead>
                    <tr>
                      <th style="width:48px;">#</th>
                      <th>Archivo</th>
                      <th style="width:200px;">Tamaño</th>
                      <th style="width:70px;">Acción</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>

              <div id="uploadsGanancias_periodoContainer" style="display:none;"></div>
              </div>
      </div>

              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroGanancias_periodo" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR PERÍODO GANANCIAS -->

<div class="modal fade" id="modalEliminarGanancias_periodo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el Período fiscal de Ganancias?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarGanancias_periodo" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER PERÍODO GANANCIAS -->

<div class="modal fade" id="modalVerGanancias_periodo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerGanancias_periodo" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerGanancias_periodo" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO PERÍODO FISCAL</h3>
                  </div>

                  <div  id="colapsadoVerGanancias_periodo" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-6">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_Ganancias_periodo" readonly>
                       </div>

                       <div class="col-md-6">
                         <h5>Período al que pertenece</h5>
                           <input name="fecha_GananciasPres" type='text' class="form-control" id="ver_periodo_Ganancias_periodo" readonly/>
                       </div>
                     </div>
                     <br/>

                     <div class="row">
                       <div class="col-md-6">
                         <h5>Impuesto a pagar/saldo a favor</h5>
                             <input type='text' class="form-control" id="ver_saldo_Ganancias_periodo" readonly/>
                       </div>
                       <div class="col-md-6">
                         <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
                         <input type='text' class="form-control" id="ver_fecha_pres_Ganancias_periodo" readonly/>
                       </div>

                   </div>


                   </br>
                   <div class="row">
                     <div class="col-md-6">
                       <h5>Forma de Pago</h5>
                       <textarea class="form-control" id="ver_forma_pago_Ganancias_periodo" readonly rows="3"></textarea>
                     </div>
                     <div class="col-md-6">
                       <h5>Observaciones</h5>
                       <textarea class="form-control" id="ver_obs_Ganancias_periodo" readonly rows="3"></textarea>
                     </div>
                   </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR Jackpots Cargados -->

<div class="modal fade" id="modalCargarJackpotsPagados" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearJackpotsPagados" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearJackpotsPagados" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE PROMO TICKET</h3>
                    </div>

                    <div  id="colapsadoCrearJackpotsPagados" class="collapse in">

            <form id="formNuevoRegistroJackpotsPagados" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="JackpotsPagados_modo" name="JackpotsPagados_modo" value="create">
                  <input type="hidden" id="id_registroJackpotsPagados" name="id_registroJackpotsPagados" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaJackpotsPagados' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_JackpotsPagados" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_JackpotsPagados" autocomplete="off" style="background-color: rgb(255,255,255);" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3" >
                    <h5>Casino</h5>
                    <select name="casinoJackpotsPagados" class="form-control" id="casinoJackpotsPagados">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <br/>


                <div class="row">
                  <div class="col-md-5">
                    <h5>Importe</h5>
                    <input type="text" class="form-control" name="importe_JackpotsPagados" id="importe_JackpotsPagados" >
                  </div>
              </div>

              </br>

              <div class="row">
        <div class="col-md-3">
          <h5>Archivo</h5>
          </div>
        </div>
        <div class="row">
            <div class="form-group">
            <div class="input-group col-md-8">
              <span class="input-group-btn">
                <button class="btn btn-primary" type="button" id="btnPickJackpotsPagados">
                  <i class="fa fa-folder-open"></i> Examinar…
                </button>
              </span>
              <input type="text" id="fileNameJackpotsPagados" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
              <input type="file" id="uploadJackpotsPagados" name="uploadJackpotsPagados[]" multiple style="display:none;">
            </div>

            <div class="table-responsive" id="uploadsJackpotsPagadosWrap" style="margin-top:8px; display:none;">
              <table class="table table-striped table-bordered table-condensed" id="uploadsJackpotsPagadosTable">
                <thead>
                  <tr>
                    <th style="width:48px;">#</th>
                    <th>Archivo</th>
                    <th style="width:200px;">Tamaño</th>
                    <th style="width:70px;">Acción</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <div id="uploadsJackpotsPagadosContainer" style="display:none;"></div>
            </div>
    </div>

              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroJackpotsPagados" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR Jackpots Pagados -->

<div class="modal fade" id="modalEliminarJackpotsPagados" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro del Jackpot Pagado?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarJackpotsPagados" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER Jackpots Pagados -->

<div class="modal fade" id="modalVerJackpotsPagados" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerJackpotsPagados" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerJackpotsPagados" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO APORTE PATRONAL</h3>
                  </div>

                  <div  id="colapsadoVerJackpotsPagados" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_JackpotsPagados" readonly>
                       </div>
                       <div class="col-md-3">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_JackpotsPagados" readonly>
                       </div>
                       <div class="col-md-5">
                         <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
                         <input type='text' class="form-control" id="ver_fecha_JackpotsPagadosPres" readonly/>
                       </div>
                     </div>
                     <br/>

                     <div class="row">

                       <div class="col-md-4">
                         <h5>Cantidad de Empleados</h5>
                         <input type="text" class="form-control" id="ver_cant_empleados_JackpotsPagados" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Monto Pagado</h5>
                         <input type="text" class="form-control" id="ver_monto_pagado_JackpotsPagados" readonly>
                       </div>
                   </div>

                   </br>
                   <div class="row">
                     <div class="col-md-6">
                       <h5>Observaciones</h5>
                       <textarea class="form-control" id="ver_obs_JackpotsPagados" readonly rows="3"></textarea>
                     </div>
                   </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR PREMIOS PAGADOS -->

<div class="modal fade" id="modalCargarPremiosPagados" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearPremiosPagados" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearPremiosPagados" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE PROMO TICKET</h3>
                    </div>

                    <div  id="colapsadoCrearPremiosPagados" class="collapse in">

            <form id="formNuevoRegistroPremiosPagados" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="PremiosPagados_modo" name="PremiosPagados_modo" value="create">
                  <input type="hidden" id="id_registroPremiosPagados" name="id_registroPremiosPagados" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaPremiosPagados' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_PremiosPagados" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_PremiosPagados" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-3" >
                    <h5>Casino</h5>
                    <select name="casinoPremiosPagados" class="form-control" id="casinoPremiosPagados">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <br/>


                <div class="row">
                  <div class="col-md-5">
                    <h5>Cantidad</h5>
                    <input type="text" class="form-control" name="cant_PremiosPagados" id="cant_PremiosPagados" >
                  </div>
                  <div class="col-md-5">
                    <h5>Importe</h5>
                    <input type="text" class="form-control" name="importe_PremiosPagados" id="importe_PremiosPagados" >
                  </div>
              </div>

              </br>

<div class="row">
        <div class="col-md-3">
          <h5>Archivo</h5>
          </div>
        </div>
        <div class="row">
            <div class="form-group">
            <div class="input-group col-md-8">
              <span class="input-group-btn">
                <button class="btn btn-primary" type="button" id="btnPickPremiosPagados">
                  <i class="fa fa-folder-open"></i> Examinar…
                </button>
              </span>
              <input type="text" id="fileNamePremiosPagados" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
              <input type="file" id="uploadPremiosPagados" name="uploadPremiosPagados[]" multiple style="display:none;">
            </div>

            <div class="table-responsive" id="uploadsPremiosPagadosWrap" style="margin-top:8px; display:none;">
              <table class="table table-striped table-bordered table-condensed" id="uploadsPremiosPagadosTable">
                <thead>
                  <tr>
                    <th style="width:48px;">#</th>
                    <th>Archivo</th>
                    <th style="width:200px;">Tamaño</th>
                    <th style="width:70px;">Acción</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <div id="uploadsPremiosPagadosContainer" style="display:none;"></div>
            </div>
    </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroPremiosPagados" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR PREMIOS PAGADOS -->

<div class="modal fade" id="modalEliminarPremiosPagados" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro del premio pagado?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarPremiosPagados" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER PREMIO PAGADO -->

<div class="modal fade" id="modalVerPremiosPagados" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerPremiosPagados" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerPremiosPagados" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO APORTE PATRONAL</h3>
                  </div>

                  <div  id="colapsadoVerPremiosPagados" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_PremiosPagados" readonly>
                       </div>
                       <div class="col-md-3">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_PremiosPagados" readonly>
                       </div>
                       <div class="col-md-5">
                         <h5>FECHA DE PRESENTACIÓN DE LA DECLARACIÓN JURADA</h5>
                         <input type='text' class="form-control" id="ver_fecha_PremiosPagadosPres" readonly/>
                       </div>
                     </div>
                     <br/>

                     <div class="row">

                       <div class="col-md-4">
                         <h5>Cantidad de Empleados</h5>
                         <input type="text" class="form-control" id="ver_cant_empleados_PremiosPagados" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Monto Pagado</h5>
                         <input type="text" class="form-control" id="ver_monto_pagado_PremiosPagados" readonly>
                       </div>
                   </div>

                   </br>
                   <div class="row">
                     <div class="col-md-6">
                       <h5>Observaciones</h5>
                       <textarea class="form-control" id="ver_obs_PremiosPagados" readonly rows="3"></textarea>
                     </div>
                   </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR PREMIOS MTM -->

<div class="modal fade" id="modalCargarPremiosMTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="width:80%">
        <div class="modal-content">
            <div class="modal-header modalNuevo" style="background-color: #00695c;">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                <button id="btn-minimizarCrearPremiosMTM" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearPremiosMTM" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                <h3 class="modal-title" id="modalCargarPremiosMTM_titulo" style="background-color: #00695c;">| NUEVO REGISTRO UNIFICADO</h3>
            </div>

            <div id="colapsadoCrearPremiosMTM" class="collapse in">
                <form id="formNuevoRegistroPremiosMTM_Unificado" novalidate="" method="POST" autocomplete="off">
                    <input type="hidden" id="PremiosMTM_Unificado_modo" name="PremiosMTM_Unificado_modo" value="create">
                    <!-- Hidden IDs for all sections -->
                    <input type="hidden" id="id_registroPremiosMTM_Unified" name="id_registroPremiosMTM" value="">
                    <input type="hidden" id="id_registroPromoTickets_Unified" name="id_registroPromoTickets" value="">
                    <input type="hidden" id="id_registroPozos_Unified" name="id_registroPozos" value="">
                    <input type="hidden" id="id_registroJackpots_Unified" name="id_registroJackpots" value="">
                    <input type="hidden" id="id_registroPremiosPagados_Unified" name="id_registroPremiosPagados" value="">
                    <input type="hidden" id="id_registroPagosMesas_Unified" name="id_registroPagosMesas" value="">
                    <input type="hidden" id="id_registroRegistrosContables_Unified" name="id_registroRegistrosContables" value="">
                    <div class="modal-body">
                        <!-- DATOS GENERALES -->
                        <div class="row">
                            <div class="col-md-4">
                                <h5>MES</h5>
                                <div class='input-group date' id='fechaPremiosMTM_Unificado' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                                    <input name="fecha_Unificado" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_Unificado" autocomplete="off" style="background-color: rgb(255,255,255);" />
                                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h5>Casino</h5>
                                <select name="casino_Unificado" class="form-control" id="casino_Unificado">
                                    <option value="">Elige un casino</option>
                                    @foreach($casinos as $c)
                                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        </br>
                        <!-- SECCIONES DINÁMICAS -->
                        <div id="unified_sections">

                            <!-- PREMIOS MTM -->
                            <div class="prize-section" id="sec_PremiosMTM" style="display: none;">
                                <div style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #333;">PREMIOS MTM</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"><h5>Cancel</h5><input type="text" class="form-control" name="PremiosMTM[cancel]" id="cancel_PremiosMTM_Unified"></div>
                                    <div class="col-md-3"><h5>Cancel USD</h5><input type="text" class="form-control" name="PremiosMTM[cancel_usd]" id="cancel_usd_PremiosMTM_Unified"></div>
                                    <div class="col-md-3"><h5>Progresivos</h5><input type="text" class="form-control" name="PremiosMTM[progresivos]" id="progre_PremiosMTM_Unified"></div>
                                    <div class="col-md-3"><h5>Progresivos USD</h5><input type="text" class="form-control" name="PremiosMTM[progresivos_usd]" id="progre_usd_PremiosMTM_Unified"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"><h5>Jackpots</h5><input type="text" class="form-control" name="PremiosMTM[jackpots]" id="jack_PremiosMTM_Unified"></div>
                                    <div class="col-md-3"><h5>Jackpots USD</h5><input type="text" class="form-control" name="PremiosMTM[jackpots_usd]" id="jack_usd_PremiosMTM_Unified"></div>
                                    <div class="col-md-3"><h5>Total</h5><input type="text" class="form-control" name="PremiosMTM[total]" id="total_PremiosMTM_Unified"></div>
                                    <div class="col-md-3"><h5>Total USD</h5><input type="text" class="form-control" name="PremiosMTM[total_usd]" id="total_usd_PremiosMTM_Unified"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-11">
                                        <h5>Archivo</h5>
                                        <div class="input-group file-group-unified">
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary btnPickUnified" type="button" data-module="PremiosMTM">
                                                    <i class="fa fa-folder-open"></i> Examinar…
                                                </button>
                                            </span>
                                            <input type="text" class="form-control displayUnified" id="display_PremiosMTM" placeholder="No se ha seleccionado ningún archivo" readonly>
                                            <input type="file" class="inputUnified" id="upload_PremiosMTM" name="uploadPremiosMTM[]" multiple style="display:none;">
                                        </div>
                                        <button type="button" class="btn btn-infoBuscar btn-ver-archivos-unified" data-type="PremiosMTM" style="display:none;"><i class="fa fa-file"></i> VER ARCHIVOS</button>
                                        <div class="table-responsive wrapUnified" id="wrap_PremiosMTM" style="margin-top:8px; display:none;">
                                            <table class="table table-striped table-bordered table-condensed tableUnified" id="table_PremiosMTM">
                                                <thead><tr><th style="width:48px;">#</th><th>Archivo</th><th style="width:120px;">Tamaño</th><th style="width:70px;">Acción</th></tr></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="containerUnified" id="container_PremiosMTM" style="display:none;"></div>
                                    </div>
                                </div>
                                <br/>
                            </div>

                            <!-- PROMO TICKETS -->
                            <div class="prize-section" id="sec_PromoTickets" style="display: none;">
                                <div style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #333;">PROMO TICKETS</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><h5>Cantidad</h5><input type="text" class="form-control" name="PromoTickets[cantidad]" id="cant_PromoTickets_Unified"></div>
                                    <div class="col-md-6"><h5>Importe</h5><input type="text" class="form-control" name="PromoTickets[importe]" id="importe_PromoTickets_Unified"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-11">
                                        <h5>Archivo</h5>
                                        <div class="input-group file-group-unified">
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary btnPickUnified" type="button" data-module="PromoTickets">
                                                    <i class="fa fa-folder-open"></i> Examinar…
                                                </button>
                                            </span>
                                            <input type="text" class="form-control displayUnified" id="display_PromoTickets" placeholder="No se ha seleccionado ningún archivo" readonly>
                                            <input type="file" class="inputUnified" id="upload_PromoTickets" name="uploadPromoTickets[]" multiple style="display:none;">
                                        </div>
                                        <button type="button" class="btn btn-infoBuscar btn-ver-archivos-unified" data-type="PromoTickets" style="display:none;"><i class="fa fa-file"></i> VER ARCHIVOS</button>
                                        <div class="table-responsive wrapUnified" id="wrap_PromoTickets" style="margin-top:8px; display:none;">
                                            <table class="table table-striped table-bordered table-condensed tableUnified" id="table_PromoTickets">
                                                <thead><tr><th style="width:48px;">#</th><th>Archivo</th><th style="width:120px;">Tamaño</th><th style="width:70px;">Acción</th></tr></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="containerUnified" id="container_PromoTickets" style="display:none;"></div>
                                    </div>
                                </div>
                                <br/>
                            </div>

                            <!-- POZOS -->
                            <div class="prize-section" id="sec_Pozos" style="display: none;">
                                <div style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #333;">POZOS ACUMULADOS</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><h5>Importe</h5><input type="text" class="form-control" name="Pozos[importe]" id="importe_Pozos_Unified"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-11">
                                        <h5>Archivo</h5>
                                        <div class="input-group file-group-unified">
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary btnPickUnified" type="button" data-module="Pozos">
                                                    <i class="fa fa-folder-open"></i> Examinar…
                                                </button>
                                            </span>
                                            <input type="text" class="form-control displayUnified" id="display_Pozos" placeholder="No se ha seleccionado ningún archivo" readonly>
                                            <input type="file" class="inputUnified" id="upload_Pozos" name="uploadPozos[]" multiple style="display:none;">
                                        </div>
                                        <button type="button" class="btn btn-infoBuscar btn-ver-archivos-unified" data-type="Pozos" style="display:none;"><i class="fa fa-file"></i> VER ARCHIVOS</button>
                                        <div class="table-responsive wrapUnified" id="wrap_Pozos" style="margin-top:8px; display:none;">
                                            <table class="table table-striped table-bordered table-condensed tableUnified" id="table_Pozos">
                                                <thead><tr><th style="width:48px;">#</th><th>Archivo</th><th style="width:120px;">Tamaño</th><th style="width:70px;">Acción</th></tr></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="containerUnified" id="container_Pozos" style="display:none;"></div>
                                    </div>
                                </div>
                                <br/>
                            </div>

                            <!-- JACKPOTS -->
                            <div class="prize-section" id="sec_Jackpots" style="display: none;">
                                <div style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #333;">JACKPOTS</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><h5>Importe</h5><input type="text" class="form-control" name="Jackpots[importe]" id="importe_Jackpots_Unified"></div>
                                    <div class="col-md-4"><h5>Importe USD</h5><input type="text" class="form-control" name="Jackpots[importe_usd]" id="importe_usd_Jackpots_Unified"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-11">
                                        <h5>Archivo</h5>
                                        <div class="input-group file-group-unified">
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary btnPickUnified" type="button" data-module="Jackpots">
                                                    <i class="fa fa-folder-open"></i> Examinar…
                                                </button>
                                            </span>
                                            <input type="text" class="form-control displayUnified" id="display_Jackpots" placeholder="No se ha seleccionado ningún archivo" readonly>
                                            <input type="file" class="inputUnified" id="upload_Jackpots" name="uploadJackpots[]" multiple style="display:none;">
                                        </div>
                                        <button type="button" class="btn btn-infoBuscar btn-ver-archivos-unified" data-type="Jackpots" style="display:none;"><i class="fa fa-file"></i> VER ARCHIVOS</button>
                                        <div class="table-responsive wrapUnified" id="wrap_Jackpots" style="margin-top:8px; display:none;">
                                            <table class="table table-striped table-bordered table-condensed tableUnified" id="table_Jackpots">
                                                <thead><tr><th style="width:48px;">#</th><th>Archivo</th><th style="width:120px;">Tamaño</th><th style="width:70px;">Acción</th></tr></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="containerUnified" id="container_Jackpots" style="display:none;"></div>
                                    </div>
                                </div>
                                <br/>
                            </div>

                            <!-- PREMIOS PAGADOS -->
                            <div class="prize-section" id="sec_PremiosPagados" style="display: none;">
                                <div style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #333;">PREMIOS MAYORES MTM</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><h5>Cantidad</h5><input type="text" class="form-control" name="PremiosPagados[cantidad]" id="cant_PremiosPagados_Unified"></div>
                                    <div class="col-md-4"><h5>Importe</h5><input type="text" class="form-control" name="PremiosPagados[importe]" id="importe_PremiosPagados_Unified"></div>
                                    <div class="col-md-4"><h5>Importe USD</h5><input type="text" class="form-control" name="PremiosPagados[importe_usd]" id="importe_usd_PremiosPagados_Unified"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-11">
                                        <h5>Archivo</h5>
                                        <div class="input-group file-group-unified">
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary btnPickUnified" type="button" data-module="PremiosPagados">
                                                    <i class="fa fa-folder-open"></i> Examinar…
                                                </button>
                                            </span>
                                            <input type="text" class="form-control displayUnified" id="display_PremiosPagados" placeholder="No se ha seleccionado ningún archivo" readonly>
                                            <input type="file" class="inputUnified" id="upload_PremiosPagados" name="uploadPremiosPagados[]" multiple style="display:none;">
                                        </div>
                                        <button type="button" class="btn btn-infoBuscar btn-ver-archivos-unified" data-type="PremiosPagados" style="display:none;"><i class="fa fa-file"></i> VER ARCHIVOS</button>
                                        <div class="table-responsive wrapUnified" id="wrap_PremiosPagados" style="margin-top:8px; display:none;">
                                            <table class="table table-striped table-bordered table-condensed tableUnified" id="table_PremiosPagados">
                                                <thead><tr><th style="width:48px;">#</th><th>Archivo</th><th style="width:120px;">Tamaño</th><th style="width:70px;">Acción</th></tr></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="containerUnified" id="container_PremiosPagados" style="display:none;"></div>
                                    </div>
                                </div>
                                <br/>
                            </div>

                            <!-- PAGOS MESAS -->
                            <div class="prize-section" id="sec_PagosMesas" style="display: none;">
                                <div style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #333;">PREMIOS MAYORES MESAS DE PAÑO</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><h5>Cantidad</h5><input type="text" class="form-control" name="PagosMesas[cantidad]" id="cant_PagosMesas_Unified"></div>
                                    <div class="col-md-4"><h5>Importe</h5><input type="text" class="form-control" name="PagosMesas[importe]" id="importe_PagosMesas_Unified"></div>
                                    <div class="col-md-4"><h5>Importe USD</h5><input type="text" class="form-control" name="PagosMesas[importe_usd]" id="importe_usd_PagosMesas_Unified"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-11">
                                        <h5>Archivo</h5>
                                        <div class="input-group file-group-unified">
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary btnPickUnified" type="button" data-module="PagosMesas">
                                                    <i class="fa fa-folder-open"></i> Examinar…
                                                </button>
                                            </span>
                                            <input type="text" class="form-control displayUnified" id="display_PagosMesas" placeholder="No se ha seleccionado ningún archivo" readonly>
                                            <input type="file" class="inputUnified" id="upload_PagosMesas" name="uploadPagosMesas[]" multiple style="display:none;">
                                        </div>
                                        <button type="button" class="btn btn-infoBuscar btn-ver-archivos-unified" data-type="PagosMesas" style="display:none;"><i class="fa fa-file"></i> VER ARCHIVOS</button>
                                        <div class="table-responsive wrapUnified" id="wrap_PagosMesas" style="margin-top:8px; display:none;">
                                            <table class="table table-striped table-bordered table-condensed tableUnified" id="table_PagosMesas">
                                                <thead><tr><th style="width:48px;">#</th><th>Archivo</th><th style="width:120px;">Tamaño</th><th style="width:70px;">Acción</th></tr></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="containerUnified" id="container_PagosMesas" style="display:none;"></div>
                                    </div>
                                </div>
                                <br/>
                            </div>

                            <!-- REGISTROS CONTABLES -->
                            <div class="prize-section" id="sec_RegistrosContables" style="display: none;">
                                <div style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px;">
                                    <h4 style="margin: 0; color: #333;">REGISTROS CONTABLES (MTM, MP, BINGO, JOL)</h4>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"><h5>MTM Pesos</h5><input type="text" class="form-control" name="RegistrosContables[mtm_pesos]" id="mtm_pesos_RegistrosContables_Unified"></div>
                                    <div class="col-md-3"><h5>MTM Dólares</h5><input type="text" class="form-control" name="RegistrosContables[mtm_dolares]" id="mtm_usd_RegistrosContables_Unified"></div>
                                    <div class="col-md-3"><h5>MP Pesos</h5><input type="text" class="form-control" name="RegistrosContables[mp_pesos]" id="mp_pesos_RegistrosContables_Unified"></div>
                                    <div class="col-md-3"><h5>MP Dólares</h5><input type="text" class="form-control" name="RegistrosContables[mp_dolares]" id="mp_usd_RegistrosContables_Unified"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"><h5>Bingo</h5><input type="text" class="form-control" name="RegistrosContables[bingo]" id="bingo_RegistrosContables_Unified"></div>
                                    <div class="col-md-3"><h5>Juego OnLine</h5><input type="text" class="form-control" name="RegistrosContables[juego_online]" id="jol_RegistrosContables_Unified"></div>
                                    <div class="col-md-3"><h5>Total</h5><input type="text" class="form-control" name="RegistrosContables[total]" id="total_RegistrosContables_Unified"></div>
                                    <div class="col-md-3"><h5>Total USD</h5><input type="text" class="form-control" name="RegistrosContables[total_usd]" id="total_usd_RegistrosContables_Unified"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-11">
                                        <h5>Archivo</h5>
                                        <div class="input-group file-group-unified">
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary btnPickUnified" type="button" data-module="RegistrosContables">
                                                    <i class="fa fa-folder-open"></i> Examinar…
                                                </button>
                                            </span>
                                            <input type="text" class="form-control displayUnified" id="display_RegistrosContables" placeholder="No se ha seleccionado ningún archivo" readonly>
                                            <input type="file" class="inputUnified" id="upload_RegistrosContables" name="uploadRegistrosContables[]" multiple style="display:none;">
                                        </div>
                                        <button type="button" class="btn btn-infoBuscar btn-ver-archivos-unified" data-type="RegistrosContables" style="display:none;"><i class="fa fa-file"></i> VER ARCHIVOS</button>
                                        <div class="table-responsive wrapUnified" id="wrap_RegistrosContables" style="margin-top:8px; display:none;">
                                            <table class="table table-striped table-bordered table-condensed tableUnified" id="table_RegistrosContables">
                                                <thead><tr><th style="width:48px;">#</th><th>Archivo</th><th style="width:120px;">Tamaño</th><th style="width:70px;">Acción</th></tr></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                        <div class="containerUnified" id="container_RegistrosContables" style="display:none;"></div>
                                    </div>
                                </div>
                                <br/>
                            </div>

                        </div> <!-- /modal-body -->
                        <div class="modal-footer">
                            <button id="btnVerArchivosUnificado" type="button" class="btn btn-info" style="display:none;">VER ARCHIVOS</button>
                            <button id="guardarRegistroPremiosMTM_Unificado" type="button" class="btn btn-successAceptar">GENERAR</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</div>

<!-- MODAL VER ARCHIVOS UNIFICADO -->
<div class="modal fade" id="modalVerArchivosUnified" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="width: 60%;">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #00695c; color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color:white;"><i class="fa fa-times"></i></button>
                <h3 class="modal-title">| ARCHIVOS RESPALDATORIOS</h3>
            </div>
            <div class="modal-body">
                <div id="contentVerArchivosUnified"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">CERRAR</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ELIMINAR PREMIOS MTM -->

<div class="modal fade" id="modalEliminarPremiosMTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro del premio mtm?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarPremiosMTM" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER PREMIOS MTM -->

<div class="modal fade" id="modalVerPremiosMTM" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerPremiosMTM" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerPremiosMTM" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO PREMIO MTM</h3>
                  </div>

                  <div  id="colapsadoVerPremiosMTM" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_PremiosMTM" readonly>
                       </div>
                       <div class="col-md-3">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_PremiosMTM" readonly>
                       </div>
                     </div>
                     <br/>

                     <div class="row">

                       <div class="col-md-4">
                         <h5>Cancel Credits</h5>
                         <input type="text" class="form-control" id="ver_cancel_PremiosMTM" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Cancel Credits USD</h5>
                         <input type="text" class="form-control" id="ver_cancel_usd_PremiosMTM" readonly>
                       </div>
                   </div>
                   <br/>

                     <div class="row">

                       <div class="col-md-4">
                         <h5>Jackpots</h5>
                         <input type="text" class="form-control" id="ver_jackpots_PremiosMTM" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Jackpots USD</h5>
                         <input type="text" class="form-control" id="ver_jackpots_usd_PremiosMTM" readonly>
                       </div>
                 </div>
                 <br/>

                   <div class="row">

                       <div class="col-md-4">
                         <h5>Progresivos</h5>
                         <input type="text" class="form-control" id="ver_progre_PremiosMTM" readonly>
                       </div>
                       <div class="col-md-4">
                         <h5>Progresivos USD</h5>
                         <input type="text" class="form-control" id="ver_progre_usd_PremiosMTM" readonly>
                       </div>
                     </div>
                     <br/>

                 <div class="row">

                   <div class="col-md-4">
                     <h5>Total</h5>
                     <input type="text" class="form-control" id="ver_total_PremiosMTM" readonly>
                   </div>
                   <div class="col-md-4">
                     <h5>Total USD</h5>
                     <input type="text" class="form-control" id="ver_total_usd_PremiosMTM" readonly>
                   </div>
               </div>


                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR AUT DIRECTORES - DIRECTOR -->

<div class="modal fade" id="modalCargarAutDirectores_director" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearAutDirectores_director" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearAutDirectores_director" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO DIRECTOR</h3>
                    </div>

                    <div  id="colapsadoCrearAutDirectores_director" class="collapse in">

            <form id="formNuevoRegistroAutDirectores_director" novalidate="" method="POST" autocomplete="off">

              <div class="modal-body">
                <div class="row">
                  <div class="col-md-4" >
                    <h5>Casino</h5>
                    <select name="casinoAutDirectores_director" class="form-control" id="casinoAutDirectores_director">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4">
                    <h5>Nombre y apellido</h5>
                    <input type="text" class="form-control" name="nombre_AutDirectores_director" id="cancel_AutDirectores_director" >
                  </div>
                  <div class="col-md-4">
                    <h5>C.U.I.T.</h5>
                    <input type="text" class="form-control" name="cuit_AutDirectores_director" id="cancel_AutDirectores_director" >
                  </div>
                </div>
                <br/>

              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroAutDirectores_director" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL MODIFICAR AUT DIRECTORES - DIRECTOR  -->


<div class="modal fade" id="modalModificarAutDirectores_director" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarModificarAutDirectores_director" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoModificarAutDirectores_director" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| MODIFICAR DIRECTOR</h3>
                    </div>

                    <div  id="colapsadoModificarAutDirectores_director" class="collapse in">

            <form id="formModificarRegistroAutDirectores_director" novalidate="" method="POST" autocomplete="off">

              <div class="modal-body">
                  <input type="hidden" name="ModifId_AutDirectores_director" id="ModifId_AutDirectores_director">

                <div class="row">
                  <div class="col-md-4">
                    <h5>Nombre</h5>
                    <input type="text" class="form-control" name="ModifAutDirectores_director_nombre" id="ModifAutDirectores_director_nombre" >
                  </div>
                  <div class="col-md-4">
                    <h5>Cuit</h5>
                    <input type="text" class="form-control" name="ModifAutDirectores_director_cuit" id="ModifAutDirectores_director_cuit" >
                  </div>
                  <div class="col-md-4">
                    <h5>Estado</h5>
                    <select name="ModifAutDirectores_director_estado" class="form-control" id="ModifAutDirectores_director_estado" name="ModifAutDirectores_director_estado">
                      <option value="1">Habilitado</option>
                      <option value="0">Deshabilitado</option>

                    </select>
                  </div>

                </div>
                <br/>

              </div>

              <div class="modal-footer">

                <button id ="guardarModifRegistroAutDirectores_director" type="button" class="btn btn-successAceptar">GUARDAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">CANCELAR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>


<!-- MODAL ELIMINAR AUT DIRECTORES - AUTORIZACIÓN -->

<div class="modal fade" id="modalEliminarAutDirectores_autorizacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro del autónomo de los directores?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarAutDirectores_autorizacion" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL GESTIONAR AUT. DIRECTORES  DIRECTORES -->

<div class="modal fade" id="modalAutDirectores_gestionar_directores" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarAutDirectores_gestionar_directores" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoAutDirectores_gestionar_directores" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| DIRECTORES</h3>
                  </div>

                  <div  id="colapsadoVerAutDirectores_gestionar_directores" class="collapse in">
                    <div class="modal-body">

                      <div class="modal-body p-0">
                         <div id="dir-list-loading" class="p-3">Cargando...</div>
                         <div class="table-responsive" style="display:none">
                           <table class="table table-fixed" id="tabla-directores-AutDirectores">
                             <thead class="thead-light">
                               <tr>
                                 <th class="col-md-4">Nombre</th>
                                 <th class="col-md-4">CUIT</th>
                                 <th class="col-md-1">Casino</th>
                                 <th class="text-center col-md-2">Estado</th>
                                 <th class="text-left col-md-1">Acciones</th>
                               </tr>
                             </thead>
                             <tbody></tbody>
                           </table>
                         </div>
                       </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR AUT DIRECTORES - DIRECTOR-->

<div class="modal fade" id="modalEliminarAutDirectores_director" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el director?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarAutDirectores_director" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL CARGAR AUT. DIRECTORES - AUTORIZACION -->

<div class="modal fade" id="modalCargarAutDirectores_autorizacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearAutDirectores_autorizacion" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearAutDirectores_autorizacion" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE AUTÓNOMO DE DIRECTORES</h3>
                    </div>

                    <div  id="colapsadoCrearAutDirectores_autorizacion" class="collapse in">

            <form id="formNuevoRegistroAutDirectores_autorizacion" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="AutDirectores_modo" name="AutDirectores_modo" value="create">
                  <input type="hidden" id="id_registroAutDirectores" name="id_registroAutDirectores" value="">

              <div class="modal-body">
                <div class="row">
                  <div class="col-md-6">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaAutDirectores_autorizacion' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_AutDirectores_autorizacion" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_AutDirectores_autorizacion" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-6" >
                    <h5>Casino</h5>
                    <select name="casinoAutDirectores_autorizacion" class="form-control" id="casinoAutDirectores_autorizacion">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <br/>

                <div id="zona-directores" class="row">

                </div>

              </br>

<div class="row">
        <div class="col-md-3">
          <h5>Archivo</h5>
          </div>
        </div>
        <div class="row">
            <div class="form-group">
            <div class="input-group col-md-8">
              <span class="input-group-btn">
                <button class="btn btn-primary" type="button" id="btnPickAutDirectores">
                  <i class="fa fa-folder-open"></i> Examinar…
                </button>
              </span>
              <input type="text" id="fileNameAutDirectores" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
              <input type="file" id="uploadAutDirectores" name="uploadAutDirectores[]" multiple style="display:none;">
            </div>

            <div class="table-responsive" id="uploadsAutDirectoresWrap" style="margin-top:8px; display:none;">
              <table class="table table-striped table-bordered table-condensed" id="uploadsAutDirectoresTable">
                <thead>
                  <tr>
                    <th style="width:48px;">#</th>
                    <th>Archivo</th>
                    <th style="width:200px;">Tamaño</th>
                    <th style="width:70px;">Acción</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <div id="uploadsAutDirectoresContainer" style="display:none;"></div>
            </div>
    </div>
              </div>

              <div class="modal-footer">

                <button id ="guardarRegistroAutDirectores_autorizacion" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL VER AUT DIRECTORES - AUTORIZACIÓN -->

<div class="modal fade" id="modalVerAutDirectores_autorizacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerAutDirectores_autorizacion" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerAutDirectores_autorizacion" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO AUT. DIRECTORES</h3>
                  </div>

                  <div  id="colapsadoVerAutDirectores_autorizacion" class="collapse in">
                    <div class="modal-body">
                     <div class="row">
                       <div class="col-md-4">
                         <h5>MES</h5>
                         <input type="text" class="form-control" id="ver_fecha_AutDirectores" readonly>
                       </div>
                       <div class="col-md-3">
                         <h5>Casino</h5>
                         <input type="text" class="form-control" id="ver_casino_AutDirectores" readonly>
                       </div>
                     </div>
                     <br/>

                     <div class="row">
                        <div class="col-md-12">
                          <div class="table-responsive">
                            <table class="table table-sm" id="tabla-detalle-AutDirectores">
                              <thead>
                                <tr>
                                  <th>Director</th>
                                  <th>CUIT</th>
                                  <th class="text-center">Autoriza</th>
                                  <th>Observación</th>
                                </tr>
                              </thead>
                              <tbody id="detalle-AutDirectores-body"></tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                   <br/>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR SEGUROS - TIPO -->

<div class="modal fade" id="modalCargarSeguros_tipo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearSeguros_tipo" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearSeguros_tipo" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO TIPO DE SEGURO</h3>
                    </div>

                    <div  id="colapsadoCrearSeguros_tipo" class="collapse in">

            <form id="formNuevoRegistroSeguros_tipo" novalidate="" method="POST" autocomplete="off">

              <div class="modal-body">
                <div class="row">
                  <div class="col-md-12">
                    <h5>Nombre del tipo de seguro</h5>
                    <input type="text" rows="3" class="form-control" name="tipo_Seguros_tipo" id="tipo_Seguros_tipo" >
                  </div>
                </div>
                <br/>

              </div>

              <div class="modal-footer">
                <button id="guardarRegistroSeguros_tipo" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
              </div>
            </form>
          </div> <!-- collapse -->
        </div> <!-- modal content -->
      </div> <!-- modal dialog -->
    </div> <!-- modal fade -->
</div>

<!-- MODAL GESTIONAR SEGUROS - TIPO -->

<div class="modal fade" id="modalSeguros_tipo_gestionar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarSeguros_tipo_gestionar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoSeguros_tipo_gestionar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| TIPO DE SEGURO</h3>
                  </div>

                  <div  id="colapsadoVerSeguros_tipo_gestionar" class="collapse in">
                    <div class="modal-body">

                      <div class="modal-body p-0">
                         <div id="dir-list-loading_seguros" class="p-3">Cargando...</div>
                         <div class="table-responsive" style="display:none">
                           <table class="table table-fixed" id="tabla-Seguros_tipo">
                             <thead class="thead-light">
                               <tr>
                                 <th class="col-md-9">Tipo de Seguro</th>
                                 <th class="text-left col-md-3">Acciones</th>
                               </tr>
                             </thead>
                             <tbody></tbody>
                           </table>
                         </div>
                       </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR SEGUROS - TIPO -->

<div class="modal fade" id="modalEliminarSeguros_tipo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el tipo de seguro?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarSeguros_tipo" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL MODIFICAR SEGUROS - TIPO -->

<div class="modal fade" id="modalModificarSeguros_tipo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarModificarSeguros_tipo" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoModificarSeguros_tipo" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| MODIFICAR TIPO DE SEGURO</h3>
                    </div>

                    <div  id="colapsadoModificarSeguros_tipo" class="collapse in">

            <form id="formModificarRegistroSeguros_tipo" novalidate="" method="POST" autocomplete="off">

              <div class="modal-body">
                  <input type="hidden" name="id_registroSeguros_tipo" id="ModifId_Seguros_tipo">

                <div class="row">
                  <div class="col-md-12">
                    <h5>Nombre del tipo de seguro</h5>
                    <input type="text" rows="3" class="form-control" name="ModifTipo_Seguros_tipo" id="ModifTipo_Seguros_tipo" >
                  </div>
                </div>
                <br/>

              </div>

              <div class="modal-footer">
                <button id="guardarModifRegistroSeguros_tipo" type="button" class="btn btn-successAceptar">GUARDAR</button>
                <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">CANCELAR</button>
              </div>
            </form>
          </div> <!-- collapse -->
        </div> <!-- modal content -->
      </div> <!-- modal dialog -->
    </div> <!-- modal fade -->

<!-- MODAL CARGAR SEGURO -->

<div class="modal fade" id="modalCargarSeguros" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearSeguros" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearSeguros" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE PAGO DE SEGUROS</h3>
                    </div>

                    <div  id="colapsadoCrearSeguros" class="collapse in">

            <form id="formNuevoRegistroSeguros" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="Seguros_modo" name="Seguros_modo" value="create">
                  <input type="hidden" id="id_registroSeguros" name="id_registroSeguros" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-6">
                    <h5>PERIODO DE VIGENCIA DESDE</h5>
                    <div class='input-group date' id='fechaSegurosDes' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_SegurosDes" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_SegurosDes" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <h5>HASTA</h5>
                    <div class='input-group date' id='fechaSegurosHas' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_SegurosHas" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_SegurosHas" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6" >
                    <h5>Casino</h5>
                    <select name="casinoSeguros" class="form-control" id="casinoSeguros">
                      <option value="">Elige un casino</option>
                      @foreach($casinos as $c)
                        <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6" >
                    <h5>Tipo de seguro</h5>
                    <select name="tipo_Seguros" class="form-control" id="tipo_Seguros">
                      <option value="">Elige un tipo de seguro</option>
                    </select>
                  </div>
                </div>
                <br/>

                <div class="row">
                  <div class="col-md-4">
                    <h5>Compañia</h5>
                    <input type="text" class="form-control" id="comp_Seguros" name="comp_Seguros">
                  </div>
                  <div class="col-md-4">
                    <h5>Número de póliza</h5>
                    <input type="text" class="form-control" id="poliza_Seguros" name="poliza_Seguros">
                  </div>
                  <div class="col-md-4">
                    <h5>Monto Asegurado</h5>
                    <input type="text" class="form-control" id="monto_Seguros" name="monto_Seguros">
                  </div>
              </div>

            </br>
            <div class="row">
              <div class="col-md-4">
                <h5>CUENTA PAGA / PAGO TOTAL</h5>
                <input type="text" class="form-control" id="cta_paga_Seguros" name="cta_paga_Seguros">
              </div>
              <div class="col-md-4">
                <h5>Art. CP 9201</h5>
                <input type="text" class="form-control" id="art_Seguros" name="art_Seguros">
              </div>
              <div class="col-md-4">
                <h5>Requerimento Anual</h5>
                <input type="text" class="form-control" id="requerimento_Seguros" name="requerimento_Seguros">
              </div>
          </div>

              </br>
              <div class="row">
                  <div class="col-md-6">
                    <h5>Observaciones</h5>
                    <textarea class="form-control" maxlength="4000" name="obs_Seguros" id="obs_Seguros" rows="3"></textarea>
                  </div>
                <div class="col-md-6" >
                  <h5>Estado</h5>
                  <select name="estado_Seguros" class="form-control" id="estado_Seguros">
                    <option value="0">VENCIDO</option>
                    <option value="1">VIGENTE</option>

                  </select>
                </div>


              </div>
            </br>


<div class="row">
        <div class="col-md-3">
          <h5>Archivo</h5>
          </div>
        </div>
        <div class="row">
            <div class="form-group">
            <div class="input-group col-md-8">
              <span class="input-group-btn">
                <button class="btn btn-primary" type="button" id="btnPickSeguros">
                  <i class="fa fa-folder-open"></i> Examinar…
                </button>
              </span>
              <input type="text" id="fileNameSeguros" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
              <input type="file" id="uploadSeguros" name="uploadSeguros[]" multiple style="display:none;">
            </div>

            <div class="table-responsive" id="uploadsSegurosWrap" style="margin-top:8px; display:none;">
              <table class="table table-striped table-bordered table-condensed" id="uploadsSegurosTable">
                <thead>
                  <tr>
                    <th style="width:48px;">#</th>
                    <th>Archivo</th>
                    <th style="width:200px;">Tamaño</th>
                    <th style="width:70px;">Acción</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <div id="uploadsSegurosContainer" style="display:none;"></div>
            </div>
    </div>
              </div>

              <div class="modal-footer">
                <button id="guardarRegistroSeguros" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
              </div>
            </form>
          </div> <!-- collapse -->
        </div> <!-- modal content -->
      </div> <!-- modal dialog -->
    </div> <!-- modal fade -->

<!-- MODAL ELIMINAR SEGURO -->

<div class="modal fade" id="modalEliminarSeguros" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro del seguro?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarSeguros" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER SEGURO -->

<div class="modal fade" id="modalVerSeguros" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarSeguros" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerSeguros" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO SEGUROS</h3>
                  </div>

                  <div  id="colapsadoVerSeguros" class="collapse in">
                    <div class="modal-body">
                      <div class="row">
                        <div class="col-md-6">
                          <h5>PERIODO DE VIGENCIA DESDE</h5>
                              <input type='text' class="form-control" id="ver_fecha_SegurosDes" readonly/>
                        </div>
                        <div class="col-md-6">
                          <h5>HASTA</h5>
                              <input type='text' class="form-control" id="ver_fecha_SegurosHas" readonly/>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6" >
                          <h5>Casino</h5>
                          <input type="text" class="form-control"  id="ver_casino_Seguros" readonly>

                        </div>
                        <div class="col-md-6" >
                          <h5>Tipo de seguro</h5>
                          <input type="text" class="form-control" id="ver_tipo_Seguros" readonly>
                        </div>
                      </div>
                      <br/>

                      <div class="row">
                        <div class="col-md-4">
                          <h5>Compañia</h5>
                          <input type="text" class="form-control" id="ver_compañia_Seguros" readonly>
                        </div>
                        <div class="col-md-4">
                          <h5>Número de póliza</h5>
                          <input type="text" class="form-control" id="ver_poliza_Seguros" readonly>
                        </div>
                        <div class="col-md-4">
                          <h5>Monto Asegurado</h5>
                          <input type="text" class="form-control" id="ver_monto_Seguros" readonly>
                        </div>
                    </div>

                  </br>
                  <div class="row">
                    <div class="col-md-4">
                      <h5>CUENTA PAGA / PAGO TOTAL</h5>
                      <input type="text" class="form-control" id="ver_cta_paga_Seguros" readonly>
                    </div>
                    <div class="col-md-4">
                      <h5>Art. CP 9201</h5>
                      <input type="text" class="form-control" id="ver_art_Seguros" readonly>
                    </div>
                    <div class="col-md-4">
                      <h5>Requerimento Anual</h5>
                      <input type="text" class="form-control" id="ver_requerimento_Seguros" readonly>
                    </div>
                </div>

                    </br>
                    <div class="row">
                        <div class="col-md-6">
                          <h5>Observaciones</h5>
                          <textarea class="form-control" maxlength="4000" id="ver_obs_Seguros" readonly rows="3"></textarea>
                        </div>
                      <div class="col-md-6" >
                        <h5>Estado</h5>
                        <input type="text" class="form-control" id="ver_estado_Seguros" readonly>
                      </div>


                    </div>
                   <br/>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR DERECHO DE ACCESO -->

<div class="modal fade" id="modalCargarDerechoAcceso" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarCrearDerechoAcceso" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearDerechoAcceso" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| NUEVO REGISTRO DE CONTRIBUCION ENTE TURISTICO</h3>
                    </div>

                    <div  id="colapsadoCrearDerechoAcceso" class="collapse in">

            <form id="formNuevoRegistroDerechoAcceso" novalidate="" method="POST" autocomplete="off">
              <input type="hidden" id="DerechoAcceso_modo" name="DerechoAcceso_modo" value="create">
                  <input type="hidden" id="id_registroDerechoAcceso" name="id_registroDerechoAcceso" value="">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-6" >
                    <h5>Casino</h5>
                    <select name="casinoDerechoAcceso" class="form-control" id="casinoDerechoAcceso" readonly>
                      <option value="3">Rosario</option>
                    </select>
                  </div>
                <div class="col-md-6" >
                  <h5>Semana N°</h5>
                  <select name="semanaDerechoAcceso" class="form-control" id="semanaDerechoAcceso" >
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>

                  </select>
                </div>
              </div>

            </br>
                <div class="row">
                  <div class="col-md-6">
                    <h5>MES</h5>
                    <div class='input-group date' id='fechaDerechoAcceso' data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                        <input name="fecha_DerechoAcceso" type='text' class="form-control" placeholder="yyyy-mm" id="fecha_DerechoAcceso" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <h5>FECHA DE VENCIMIENTO</h5>
                    <div class='input-group date' id='fecha_vencDerechoAcceso' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm">
                        <input name="fecha_venc_DerechoAcceso" type='text' class="form-control" placeholder="yyyy-mm-dd" id="fecha_venc_DerechoAcceso" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                    </div>
                  </div>

                </div>
                <br/>

                <div class="row">

                  <div class="col-md-4">
                    <h5>Monto pagado</h5>
                    <input type="text" class="form-control" name="monto_DerechoAcceso" id="monto_DerechoAcceso" >
                  </div>
                  <div class="col-md-8">
                    <h5>Observaciones</h5>
                    <textarea class="form-control" maxlength="4000" name="obs_DerechoAcceso" id="obs_DerechoAcceso" rows="3"></textarea>
                  </div>
              </div>

            </br>

            <div class="row">
      <div class="col-md-3">
        <h5>Archivo</h5>
        </div>
      </div>
      <div class="row">
          <div class="form-group">
          <div class="input-group col-md-8">
            <span class="input-group-btn">
              <button class="btn btn-primary" type="button" id="btnPickDerechoAcceso">
                <i class="fa fa-folder-open"></i> Examinar…
              </button>
            </span>
            <input type="text" id="fileNameDerechoAcceso" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
            <input type="file" id="uploadDerechoAcceso" name="uploadDerechoAcceso[]" multiple style="display:none;">
          </div>

          <div class="table-responsive" id="uploadsDerechoAccesoWrap" style="margin-top:8px; display:none;">
            <table class="table table-striped table-bordered table-condensed" id="uploadsDerechoAccesoTable">
              <thead>
                <tr>
                  <th style="width:48px;">#</th>
                  <th>Archivo</th>
                  <th style="width:200px;">Tamaño</th>
                  <th style="width:70px;">Acción</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div id="uploadsDerechoAccesoContainer" style="display:none;"></div>
          </div>
  </div>
              </div>

              <div class="modal-footer">
                <button id="guardarRegistroDerechoAcceso" type="button" class="btn btn-successAceptar">GENERAR</button>
                <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
              </div>
            </form>
          </div> <!-- collapse -->
        </div> <!-- modal content -->
      </div> <!-- modal dialog -->
    </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR DERECHO DE ACCESO -->

<div class="modal fade" id="modalEliminarDerechoAcceso" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro sobre derecho de acceso?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarDerechoAcceso" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER OBSERVACION DERECHO DE ACCESO-->

<div class="modal fade" id="modalVerDerechoAcceso" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width:50%">
         <div class="modal-content">
           <div class="modal-header modalNuevo" style="background-color: #00695c;">
             <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
             <button id="btn-minimizarVerDerechoAcceso" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerDerechoAcceso" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
             <h3 class="modal-title" style="background-color: #00695c;">| OBSERVACIÓN DEL DERECHO DE ACCESO</h3>
            </div>

            <div  id="colapsadoVerDerechoAcceso" class="collapse in">

              <div class="modal-body">
                <p id="obsDerechoAcceso"></p>
              <div class="modal-footer">
                <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
              </div>

            </div> <!-- colapsado -->

          </div> <!-- modal content -->
    </div> <!-- modal dialog -->
  </div> <!-- modal fade -->

<!-- MODAL CARGAR PATENTES - ELEMENTO PATENTABLE -->

<div class="modal fade" id="modalCargarPatentes_patenteDe" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog" style="width:71%">
                   <div class="modal-content">
                     <div class="modal-header modalNuevo" style="background-color: #00695c;">
                       <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                       <button id="btn-minimizarCrearPatentes_patenteDe" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearPatentes_patenteDe" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                       <h3 class="modal-title" style="background-color: #00695c;">| NUEVA PATENTE</h3>
                      </div>

                      <div  id="colapsadoCrearPatentes_patentesDe" class="collapse in">

              <form id="formNuevoRegistroPatentes_patenteDe" novalidate="" method="POST" autocomplete="off">

                <div class="modal-body">
                  <div class="row">
                    <div class="col-md-3">
                      <h5>Casino</h5>
                      <select class="form-control" name="CasinoPatentes_patenteDe" id="CasinoPatentes_patenteDe">
                        <option value="">Seleccione un casino</option>
                        @foreach($casinos as $c)
                          <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-9">
                      <h5>Nombre del elemento patentable</h5>
                      <input type="text" class="form-control" name="nombre_Patentes_patenteDe" id="nombre_Patentes_patenteDe" >
                    </div>
                  </div>
                  <br/>

                </div>

                <div class="modal-footer">

                  <button id ="guardarRegistroPatentes_patenteDe" type="button" class="btn btn-successAceptar">GENERAR</button>
                  <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                </div>
              </form>
            </div> <!-- modal content -->
          </div> <!-- modal dialog -->
          </div> <!-- modal fade -->
  </div>

<!-- MODAL GESTIONAR PATENTES - ELEMENTO PATENTABLE -->

<div class="modal fade" id="modalPatentes_patenteDe_gestionar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerpatenteDe_gestionar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerpatenteDe_gestionar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| ELEMENTOS PATENTADO</h3>
                  </div>

                  <div  id="colapsadoVerpatenteDe_gestionar" class="collapse in">
                    <div class="modal-body">

                      <div class="modal-body p-0">
                         <div id="dir-list-loading_Patentes_patenteDe" class="p-3">Cargando...</div>
                         <div class="table-responsive" style="display:none">
                           <table class="table table-fixed" id="tabla-Patentes_patenteDe">
                             <thead class="thead-light">
                               <tr>
                                 <th class="col-md-6">Nombre del Elemento Patentado</th>
                                 <th class="col-md-1">Casino</th>
                                 <th class="col-md-2">Estado</th>
                                 <th class="text-left col-md-3">Acciones</th>
                               </tr>
                             </thead>
                             <tbody></tbody>
                           </table>
                         </div>
                       </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR PATENTES - PATENTES DE -->

<div class="modal fade" id="modalEliminarPatentes_patenteDe" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el elemento patentado?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarPatentes_patenteDe" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL MODIFICAR PATENTES - PATENTES DE-->

<div class="modal fade" id="modalModificarPatentes_patentesDe" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarModificarPatentes_patenteDe" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoModificarPatentes_patenteDe" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| MODIFICAR ELEMENTO PATENTADO</h3>
                    </div>

                    <div  id="colapsadoModificarPatentes_patenteDe" class="collapse in">

            <form id="formModificarRegistroPatentes_patenteDe" novalidate="" method="POST" autocomplete="off">

              <div class="modal-body">
                  <input type="hidden" name="ModifId_Patentes_patenteDe" id="ModifId_Patentes_patenteDe">

                <div class="row">
                  <div class="col-md-6">
                    <h5>Nombre del elemento patentado</h5>
                    <input type="text" class="form-control" name="ModifPatentes_patenteDe_nombre" id="ModifPatentes_patenteDe_nombre" >
                  </div>
                  <div class="col-md-6">
                    <h5>Estado</h5>
                    <select class="form-control" name ="ModifPatentes_patenteDe_estado" id="ModifPatentes_patenteDe_estado">
                    </select>
                  </div>
                </div>
                <br/>

              </div>

              <div class="modal-footer">

                <button id ="guardarModifRegistroPatentes_patenteDe" type="button" class="btn btn-successAceptar">GUARDAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">CANCELAR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>

<!-- MODAL CARGAR PATENTES -->
<div class="modal fade" id="modalCargarPatentes" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:95%">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarCrearPatentes" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearPatentes" style="position:relative; right:20px; top:5px">
          <i class="fa fa-minus"></i>
        </button>
        <h3 class="modal-title" style="background-color:#6dc7be;">| NUEVO PAGO DE PATENTES</h3>
      </div>

      <div id="colapsadoCrearPatentes" class="collapse in">
        <form id="formNuevoRegistroPatentes" novalidate method="POST" autocomplete="off">
          <input type="hidden" id="Patentes_modo" name="Patentes_modo" value="create">
          <input type="hidden" id="id_registroPatentes" name="id_registroPatentes" value="">

          <div class="modal-body">
            <div class="row">
              <div class="col-md-4">
                <h5>MES</h5>
                <div class="input-group date" id="fechaPatentes" data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                  <input name="fecha_Patentes" type="text" class="form-control" placeholder="Fecha de ejecución" id="fecha_Patentes" autocomplete="off" style="background-color:#fff;" />
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>

              <div class="col-md-4">
                <h5>Casino</h5>
                <select name="casinoPatentes" class="form-control" id="casinoPatentes">
                  <option value="">Seleccione un casino</option>
                  @foreach($casinos as $c)
                    <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <br/>

            <div class="row">
              <div class="col-md-12">
                <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Pagos por Patente</h4>
                <div id="pagosPatentesContainer"></div>
              </div>
            </div>

            <br/>

            <div class="row">
              <div class="col-md-3">
                <h5>Archivo</h5>
              </div>
            </div>

            <div class="row">
              <div class="form-group">
                <div class="input-group col-md-8">
                  <span class="input-group-btn">
                    <button class="btn btn-primary" type="button" id="btnPickPatentes">
                      <i class="fa fa-folder-open"></i> Examinar…
                    </button>
                  </span>
                  <input type="text" id="fileNamePatentes" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                  <input type="file" id="uploadPatentes" name="uploadPatentes[]" multiple style="display:none;">
                </div>

                <div class="table-responsive" id="uploadsPatentesWrap" style="margin-top:8px; display:none;">
                  <table class="table table-striped table-bordered table-condensed" id="uploadsPatentesTable">
                    <thead>
                      <tr>
                        <th style="width:48px;">#</th>
                        <th>Archivo</th>
                        <th style="width:200px;">Tamaño</th>
                        <th style="width:70px;">Acción</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>

                <div id="uploadsPatentesContainer" style="display:none;"></div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button id="guardarRegistroPatentes" type="button" class="btn btn-successAceptar">GENERAR</button>
            <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- MODAL ELIMINAR PATENTE -->

<div class="modal fade" id="modalEliminarPatentes" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro de la Patente?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarPatentes" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER PATENTES -->
<div class="modal fade" id="modalVerPatentes" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:95%">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarPatentes" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerPatentes" style="position:relative; right:20px; top:5px">
          <i class="fa fa-minus"></i>
        </button>
        <h3 class="modal-title" style="background-color:#6dc7be;">| REGISTRO PATENTES</h3>
      </div>

      <div id="colapsadoVerPatentes" class="collapse in">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <h5>Periodo</h5>
              <input type="text" class="form-control" id="ver_fecha_Patentes" readonly/>
            </div>
            <div class="col-md-4">
              <h5>Casino</h5>
              <input type="text" class="form-control" id="ver_casino_Patentes" readonly>
            </div>
          </div>

          <br/>

          <div class="row">
            <div class="col-md-12">
              <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Pagos por Patente</h4>
              <div id="ver_pagosPatentesContainer"></div>
            </div>
          </div>

          <br/>

        </div>

        <div class="modal-footer">
          <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CARGAR IMPUESTO INMOBILIARIO - PARTIDA -->

<div class="modal fade" id="modalCargarImpInmobiliario_partida" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog" style="width:71%">
                   <div class="modal-content">
                     <div class="modal-header modalNuevo" style="background-color: #00695c;">
                       <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                       <button id="btn-minimizarCrearImpInmobiliario_partida" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearImpInmobiliario_partida" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                       <h3 class="modal-title" style="background-color: #00695c;">| NUEVA PARTIDA</h3>
                      </div>

                      <div  id="colapsadoCrearPatentes_patentesDe" class="collapse in">

              <form id="formNuevoRegistroImpInmobiliario_partida" novalidate="" method="POST" autocomplete="off">

                <div class="modal-body">
                  <div class="row">
                    <div class="col-md-3">
                      <h5>Casino</h5>
                      <select class="form-control" name ="CasinoImpInmobiliario_partida" id="CasinoImpInmobiliario_partida">
                        <option value="">Seleccione un casino</option>
                        @foreach($casinos as $c)
                          <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-9">
                      <h5>Nombre de la partida</h5>
                      <input type="text" rows="3" class="form-control" name="nombre_ImpInmobiliario_partida" id="nombre_ImpInmobiliario_partida" >
                    </div>
                  </div>
                  <br/>

                </div>

                <div class="modal-footer">

                  <button id ="guardarRegistroImpInmobiliario_partida" type="button" class="btn btn-successAceptar">GENERAR</button>
                  <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                </div>
              </form>
            </div> <!-- modal content -->
          </div> <!-- modal dialog -->
          </div> <!-- modal fade -->
  </div>

<!-- MODAL GESTIONAR IMPUESTO INMOBILIARIO - PARTIDA -->

<div class="modal fade" id="modalImpInmobiliario_partida_gestionar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #00695c;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarVerpatenteDe_gestionar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerpatenteDe_gestionar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #00695c;">| PARTIDAS </h3>
                  </div>

                  <div  id="colapsadoVerpatenteDe_gestionar" class="collapse in">
                    <div class="modal-body">

                      <div class="modal-body p-0">
                         <div id="dir-list-loading_ImpInmobiliario_partida" class="p-3">Cargando...</div>
                         <div class="table-responsive" style="display:none">
                           <table class="table table-fixed" id="tabla-ImpInmobiliario_partida">
                             <thead class="thead-light">
                               <tr>
                                 <th class="col-md-8">Nombre de la partida</th>
                                 <th class="col-md-1">Casino</th>
                                 <th class="col-md-2">Estado</th>

                                 <th class="col-md-1">Acciones</th>
                               </tr>
                             </thead>
                             <tbody></tbody>
                           </table>
                         </div>
                       </div>

                   </div>

                    <div class="modal-footer">

                      <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>

                    </div>
                  </div> <!-- modal content -->
                </div> <!-- modal dialog -->
              </div> <!-- modal fade -->
</div>

<!-- MODAL ELIMINAR IMPUESTO INMOBILIARIO - PARTIDA -->

<div class="modal fade" id="modalEliminarImpInmobiliario_partida" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar la partida?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarImpInmobiliario_partida" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL MODIFICAR IMPUESTO INMOBILIARIO - PARTIDA-->

<div class="modal fade" id="modalModificarImpInmobiliario_partida" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width:71%">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #00695c;">
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizarModificarImpInmobiliario_partida" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoModificarImpInmobiliario_partida" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     <h3 class="modal-title" style="background-color: #00695c;">| MODIFICAR PARTIDA</h3>
                    </div>

                    <div  id="colapsadoModificarImpInmobiliario_partida" class="collapse in">

            <form id="formModificarRegistroImpInmobiliario_partida" novalidate="" method="POST" autocomplete="off">

              <div class="modal-body">
                  <input type="hidden" name="ModifId_ImpInmobiliario_partida" id="ModifId_ImpInmobiliario_partida">

                <div class="row">
                  <div class="col-md-10">
                    <h5>Partida</h5>
                    <input type="text" class="form-control" name="ModifImpInmobiliario_partida_partida" id="ModifImpInmobiliario_partida_partida" >
                  </div>
                  <div class="col-md-2" >
                    <h5>Estado</h5>
                    <select name="ModifImpInmobiliario_partida_estado" class="form-control" id="ModifImpInmobiliario_partida_estado">
                      <option value="1">Habilitado</option>
                      <option value="0">Deshabilitado</option>


                    </select>
                  </div>
                </div>
                <br/>

              </div>

              <div class="modal-footer">

                <button id ="guardarModifRegistroImpInmobiliario_partida" type="button" class="btn btn-successAceptar">GUARDAR</button>
                <button type="button" id ="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">CANCELAR</button>

              </div>
            </form>
          </div> <!-- modal content -->
        </div> <!-- modal dialog -->
        </div> <!-- modal fade -->
</div>


<!-- MODAL CARGAR IMPUESTO INMOBILIARIO -->
<div class="modal fade" id="modalCargarImpInmobiliario" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:95%">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="background-color: #00695c;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarCrearImpInmobiliario" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrearImpInmobiliario" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" style="background-color: #00695c;">| NUEVO PAGO DE IMPUESTO INMOBILIARIO</h3>
      </div>

      <div id="colapsadoCrearImpInmobiliario" class="collapse in">
        <form id="formNuevoRegistroImpInmobiliario" novalidate method="POST" autocomplete="off">

          <input type="hidden" id="ImpInmobiliario_modo" name="ImpInmobiliario_modo" value="create">
          <input type="hidden" id="id_registroImpInmobiliario" name="id_registroImpInmobiliario" value="">

          <div class="modal-body">
            <div class="row">
              <div class="col-md-4">
                <h5>MES</h5>
                <div class="input-group date" id="fechaImpInmobiliario" data-date-format="yyyy-mm" data-link-format="yyyy-mm">
                  <input name="fecha_ImpInmobiliario" type="text" class="form-control" placeholder="Fecha de ejecución" id="fecha_ImpInmobiliario" autocomplete="off" style="background-color:#fff;" />
                  <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-4">
                <h5>Casino</h5>
                <select name="casinoImpInmobiliario" class="form-control" id="casinoImpInmobiliario">
                  <option value="">Elige un casino</option>
                  @foreach($casinos as $c)
                    <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <br/>

            <div class="row">
              <div class="col-md-12">
                <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Pagos por Partida</h4>
                <div id="pagosImpInmobiliarioContainer"></div>
              </div>
            </div>

            <br/>

            <div class="row">
              <div class="col-md-3">
                <h5>Archivo</h5>
              </div>
            </div>
            <div class="row">
              <div class="form-group">
                <div class="input-group col-md-8">
                  <span class="input-group-btn">
                    <button class="btn btn-primary" type="button" id="btnPickImpInmobiliario">
                      <i class="fa fa-folder-open"></i> Examinar…
                    </button>
                  </span>
                  <input type="text" id="fileNameImpInmobiliario" class="form-control" placeholder="No se ha seleccionado ningún archivo" readonly>
                  <input type="file" id="uploadImpInmobiliario" name="uploadImpInmobiliario[]" multiple style="display:none;">
                </div>

                <div class="table-responsive" id="uploadsImpInmobiliarioWrap" style="margin-top:8px; display:none;">
                  <table class="table table-striped table-bordered table-condensed" id="uploadsImpInmobiliarioTable">
                    <thead>
                      <tr>
                        <th style="width:48px;">#</th>
                        <th>Archivo</th>
                        <th style="width:200px;">Tamaño</th>
                        <th style="width:70px;">Acción</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>

                <div id="uploadsImpInmobiliarioContainer" style="display:none;"></div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button id="guardarRegistroImpInmobiliario" type="button" class="btn btn-successAceptar">GENERAR</button>
            <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<!-- MODAL ELIMINAR IMPUESTO INMOBILIARIO -->

<div class="modal fade" id="modalEliminarImpInmobiliario" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
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
                            <strong id="titulo-modal-eliminar">¿Seguro desea eliminar el registro del impuesto inmobiliario?</strong>
                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button"  id="btn-eliminarImpInmobiliario" class="btn btn-dangerEliminar"> ELIMINAR  </button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
</div>

<!-- MODAL VER IMPUESTO INMOBILIARIO -->
<div class="modal fade" id="modalVerImpInmobiliario" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width:95%">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="background-color: #00695c;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarImpInmobiliario" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoVerImpInmobiliario" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" style="background-color: #00695c;">| REGISTRO IMPUESTO INMOBILIARIO</h3>
      </div>

      <div id="colapsadoVerImpInmobiliario" class="collapse in">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4">
              <h5>Periodo</h5>
              <input type="text" class="form-control" id="ver_fecha_ImpInmobiliario" readonly/>
            </div>
            <div class="col-md-4">
              <h5>Casino</h5>
              <input type="text" class="form-control" id="ver_casino_ImpInmobiliario" readonly>
            </div>
          </div>

          <br/>

          <div class="row">
            <div class="col-md-12">
              <h4 style="background: #eee; padding: 10px; border-left: 5px solid #ccc; margin-bottom: 10px; color: #333;">Pagos por Partida</h4>
              <div id="ver_pagosImpInmobiliarioContainer"></div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" id="salir" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
        </div>
      </div>
    </div>
  </div>
</div>






@endsection
@section('scripts')
  <!-- JavaScript paginacion -->
  <script src="/js/paginaciondocumentosContables.js?v={{ time() }}" charset="utf-8"></script>

  <!-- JavaScript personalizado -->
  <script src="js/documentosContables.js?v={{ time() }}" charset="utf-8"></script>

  <!-- DateTimePicker JavaScript -->
  <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <!-- Custom input Bootstrap -->
  <script src="js/fileinput.min.js" type="text/javascript"></script>
  <script src="js/locales/es.js" type="text/javascript"></script>


  <script src="/themes/explorer/theme.js" type="text/javascript"></script>

  <script src="js/inputSpinner.js" type="text/javascript"></script>
  <script src="js/lista-datos.js" type="text/javascript"></script>



<!-- Modal Validation Confirmation -->
<div class="modal fade" id="modalValidarDocumento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
          </div>

          <div class="modal-body franjaRojaModal">
            <form id="frmValidar" name="frmValidar" class="form-horizontal" novalidate="">
                <div class="form-group error ">
                  <div class="col-xs-12">
                      <strong id="titulo-modal-validar">¿Seguro desea confirmar la validación del documento?</strong>
                  </div>
                </div>
            </form>
          </div>

          <div class="modal-footer">
            <button type="button"  id="btn-confirmar-validacion" class="btn btn-dangerEliminar"> CONFIRMAR  </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
      </div>
    </div>
</div>
@endsection
