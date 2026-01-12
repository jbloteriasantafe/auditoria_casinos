@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
?>

@section('estilos')
<link rel="stylesheet" href="/css/paginacion.css"/>
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="/css/perfect-scrollbar.css">
<!-- Mesaje de notificación -->
<link rel="stylesheet" href="/css/mensajeExito.css?1">
<link rel="stylesheet" href="/css/mensajeError.css">
@endsection

      <div class="row">
            <div class="col-lg-12 col-xl-9"> <!-- columna TABLA CASINOS -->
              <div class="row">
                  <div class="col-md-12">
                      <div class="panel panel-default">
                        <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                          <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                        </div>
                        <div id="collapseFiltros" class="panel-collapse collapse">
                          <div class="panel-body">
                              <div class="row"> <!-- Primera fila -->
                                <div class="col-lg-4">
                                  <h5>Casino</h5>
                                  <select class="form-control" id="selectCasino">
                                    <option value="" selected>- Todos los casinos -</option>
                                     @foreach ($casinos as $c)
                                     <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
                                     @endforeach
                                  </select>
                                </div>
                                <div class="col-lg-4">
                                  <h5>Fecha de inicio</h5>
                                  <div class="form-group">
                                     <div class='input-group date' id='dtpFechaInicio' data-link-field="fecha_inicio" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                         <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio"/>
                                         <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fas fa-times"></i></span>
                                         <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                                     </div>
                                     <input class="form-control" type="hidden" id="fecha_inicio" value=""/>
                                  </div>
                                </div>
                                <div class="col-lg-4">
                                  <h5>Fecha de finalización</h5>
                                  <div class="form-group">
                                     <div class='input-group date' id='dtpFechaFin' data-link-field="fecha_fin" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                                         <input type='text' class="form-control" placeholder="Fecha de Fin" id="B_fecha_fin"/>
                                         <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                         <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                                     </div>
                                     <input class="form-control" type="hidden" id="fecha_fin" value=""/>
                                  </div>
                                </div>
                                <div class="col-lg-4">
                                  <h5>MONEDA</h5>
                                  <select class="form-control" id="selectMoneda">
                                    <option value="" selected>- Todas las monedas -</option>
                                    @foreach($monedas as $m)
                                    <option value="{{$m->id_tipo_moneda}}">{{$m->descripcion}}</option>
                                    @endforeach
                                  </select>
                                </div>
                                <div class="col-lg-4">
                                  <h5>Validado</h5>
                                  <select class="form-control" id="selectValidado">
                                    <option value="-">-</option>
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                  </select>
                                </div>
                              </div>
                              <br>
                              <div class="row">
                                <div class="col-md-12">
                                  <center><button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button></center>
                                </div>
                              </div>
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
                      <h4>Últimos Producidos</h4>
                  </div>
                  <div class="panel-body">
                    <table id="tablaImportacionesProducidos" class="table table-fixed tablesorter">
                      <thead>
                        <tr>
                          <th class="col-xs-1">CASINO</th>
                          <th class="col-xs-2" style="text-align: center;" value="fecha" estado="">FECHA<i class="fa fa-sort"></i></th>
                          <th class="col-xs-1" style="text-align: center;">MONEDA</th>
                          <th class="col-xs-2" style="text-align: center;">VALIDADO</th>
                          <th class="col-xs-2" style="text-align: center;">CONT INI</th>
                          <th class="col-xs-2" style="text-align: center;">RELEVAMIENTOS VISADOS</th>
                          <th class="col-xs-2">ACCIÓN</th>
                        </tr>
                      </thead>
                      <tbody style="height: 350px;">
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
                <div class="col-lg-12">
                  <a href="importaciones" style="text-decoration:none;">
                    <div class="tarjetaSeccionMenor" align="center">
                      <h2 class="tituloFondoMenor">IMPORTACIONES</h2>
                      <h2 class="tituloSeccionMenor">IMPORTACIONES</h2>
                      <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/importaciones_white.png" alt="">
                    </div>
                  </a>
                </div>
              </div>
            </div>
        </div>  <!-- /#row -->

<table hidden>
  <tr id="filaEjemploProducidos">
    <td class="col-xs-1 casino">CASINO</td>
    <td class="col-xs-2 fecha" style="text-align: center;">9999-99-99</td>
    <td class="col-xs-1 moneda" style="text-align: center;">MONEDA</td>
    <td class="col-xs-2 producido_valido" style="text-align: center;">
      <i class="fa fa-fw fa-check  valido"   style="color: #66BB6A"></i>
      <i class="fas fa-fw fa-times invalido" style="color: #EF5350"></i>
    </td>
    <td class="col-xs-2 contador_inicial_cerrado" style="text-align: center;">
      <i class="fa fa-fw fa-check  valido"   style="color: #66BB6A"></i>
      <i class="fas fa-fw fa-times invalido" style="color: #EF5350"></i>
    </td>
    <td class="col-xs-2 relevamiento_valido" style="text-align: center;">
      <i class="fa fa-fw fa-check  valido"   style="color: #66BB6A"></i>
      <i class="fas fa-fw fa-times invalido" style="color: #EF5350"></i>
    </td>
    <td class="col-xs-2 acciones">
      <button class="btn btn-warning carga" title="VALIDAR PRODUCIDO"><i class="fa fa-fw fa-upload"></i></button>
      <button class="btn btn-info planilla" title="DIFERENCIAS"><i class="fa fa-fw fa-print"></i></button>
      <button class="btn btn-info producido" title="VER PRODUCIDO"><i class="fa fa-fw fa-search-plus"></i></button>
    </td>
  </tr>
</table>

<!--Modal nuevo para ajustes-->
<div class="modal fade" id="modalCargaProducidos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 60%;">
    <div class="modal-content" >
      <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FFB74D;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">VALIDAR AJUSTES</h3>
      </div>
      <div id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row" >
            <h6 style="padding-left:15px" id="descripcion_validacion"></h6>
            <h6 style="padding-left:15px">Máquinas con diferencias: <span id="maquinas_con_diferencias">---</span></h6>
            @if($es_superusuario)
            <div style="padding-left:15px; margin-bottom:10px;">
              <button id="btn-ver-tabla" class="btn btn-info" style="margin-right:10px;">
                <i class="fa fa-table"></i> VISTA TABLA
              </button>
              <span id="resultado-ajuste-masivo" style="font-weight:bold;"></span>
            </div>
            @endif
          </div>
          <div class="row" >
            <div class="col-md-3">
              <h6><b>MÁQUINAS</b></h6>
              <table id="tablaMaquinas" class="table" style="display: block;">
                <thead style="display: block;position: relative;">
                  <tr>
                    <th class="col-xs-2">Nº ADMIN</th>
                    <th class="col-xs-2"></th>
                  </tr>
                </thead>
                <tbody id="cuerpoTabla"  style="display: block;overflow: auto;max-height: 600px;">
                </tbody>
              </table>
              <table hidden>
                <tr id="filaClon">
                  <td class="col-md-3 nroAdm" value=""> nro admin</td>
                  <td class="col-md-2 idMaqTabla" value="">
                    <button type="button" class="btn btn-info infoMaq" value="" title="Ver detalles"><i class="fa fa-fw fa-eye"></i></button>
                  </td>
                </tr>
              </table>
            </div>

            <div id="columnaDetalle" class="col-md-9" style="border-right:2px solid #ccc;" hidden>
              <h6 id="detallesEs"><b>DETALLES</b></h6>
              <br>
              <br>
              <div class="detalleMaq" >
                <h5 id="info-denominacion"></h5>
                <form id="frmCargaProducidos" name="frmCargaProducidos" class="form-horizontal" novalidate="">
                  <div class="row" style="padding-bottom: 5px;">
                    <div class="col-lg-6 col-lg-offset-3">
                      <select class="form-control" id="tipoAjuste" style="text-align: center;">
                        <option class="default1" value="0" >-Tipo Ajuste-</option>
                      </select>
                    </div>
                  </div>
                  <style>
                    .bordear-separar {
                      border: 1px solid #ccc;
                      padding-top: 15px;
                      padding-bottom: 15px;
                    }
                    .listar-horizontal {
                      display: flex;
                      justify-content: center;
                    }
                    .listar-horizontal > * {
                      flex: 1;
                      text-align: center;
                    }
                  </style>
                  <div class="row bordear-separar listar-horizontal cont_iniciales">
                    <div>
                      <h5>COININ. INICIAL</h5>
                      <input id="coininIni" type="text" class="form-control">
                    </div>
                    <div>
                      <h5>COINOUT INI.</h5>
                      <input id="coinoutIni" type="text" class="form-control">
                    </div>
                    <div>
                      <h5>JACKPOT INI.</h5>
                      <input id="jackIni" type="text" class="form-control">
                    </div>
                    <div>
                      <h5>PROG. INICIAL</h5>
                      <input id="progIni" type="text" class="form-control">
                    </div>
                    <div style="flex: 0.7;">
                      <h5>DEN. INICIAL</h5>
                      <input id="denIni" type="number"  step="0.01" min="0" class="form-control">
                    </div>
                  </div>
                  <div class="row bordear-separar listar-horizontal cont_finales">
                    <div>
                      <h5>COININ FINAL</h5>
                      <input id="coininFin" type="text" class="form-control">
                    </div>
                    <div>
                      <h5>COINOUT FINAL</h5>
                      <input id="coinoutFin" type="text" class="form-control">
                    </div>
                    <div>
                      <h5>JACKPOT FINAL</h5>
                      <input id="jackFin" type="text" class="form-control">
                    </div>
                    <div>
                      <h5>PROG. FINAL</h5>
                      <input id="progFin" type="text" class="form-control">
                    </div>
                    <div style="flex: 0.7;">
                      <h5>DEN. FINAL</h5>
                      <input id="denFin" type="number" step="0.01" min="0" class="form-control">
                    </div>
                  </div>
                  <div class="row bordear-separar listar-horizontal">
                    <div>
                      <h5>PRODUC.CALC.</h5>
                      <input id="prodCalc" type="text" class="form-control" readonly="readonly">
                    </div>
                    <div>
                      <h5>PRODUCIDO SIST.</h5>
                      <input id="prodSist" type="text" class="form-control" >
                    </div>
                    <div>
                      <h5>DIFERENCIAS</h5>
                      <h6 id="diferencias" style="font-size:20px;font-family: Roboto-Regular; color:#000000;  padding-left:  15px;"></h6>
                    </div>
                  </div>
                  <div class="row bordear-separar">
                    <div class="row">
                      <div class="col-lg-12">
                        <h5>OBSERVACIONES</h5>
                        <textarea id="prodObservaciones" class="form-control" style="resize:vertical;"></textarea>
                      </div>
                    </div>
                    <br>
                    <div class="row">
                      <div class="col-lg-1 col-lg-offset-10">
                        <button id="crearTicket" type="button" class="btn btn-default" title="CREAR TICKET">
                          <i class="far fa-envelope"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="row" hidden>
                    <div class="col-lg-2">
                      <input id="data-denominacion" type="text" class="form-control" >
                    </div>
                    <div class="col-lg-2">
                      <input id="data-producido" type="text" class="form-control" >
                    </div>
                    <div class="col-lg-2">
                      <input id="data-detalle-inicial" type="text" class="form-control" >
                    </div>
                    <div class="col-lg-2">
                      <input id="data-detalle-final" type="text" class="form-control" >
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>  <!-- fin row inicial -->
          <div class="row" align="right" style="margin-right:20px; font-weight:bold">
          <h4 id="textoExito" hidden>Se arreglaron: 0 máquinas</h4>
      </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warningModificar" id="btn-finalizar" value="nuevo">FINALIZAR AJUSTES</button>
        <button type="button" class="btn btn-default" id="btn-salir" >SALIR</button>
        <button type="button" class="btn btn-info success" id="btn-salir-validado" hidden="true">VALIDAR</button>
        <div class="mensajeSalida">
            <br>
            <span style="font-family:'Roboto-Black'; color:#EF5350;">CAMBIOS REALIZADOS</span>
            <br>
            <span style="font-family:'Roboto'; color:#555;">Presione SALIR nuevamente para salir.</span>
        </div>
        <div class="mensajeFin" hidden>
          <br>
          <span style="font-family:'Roboto-Black'; color:#66BB6A; font-size:16px;">Los ajustes se han guardado correctamente.</span>
          <br>
        </div>
        <input type="hidden" id="id_producido" value="0">
      </div> <!-- modal body -->
    </div> <!--  modal colap-->
  </div>  <!-- modal content -->
</div> <!--  modal dialog -->
</div> <!-- modal fade -->

<!-- Modal de Reporte de Ajustes Automáticos -->
<div class="modal fade" id="modalReporteAjustes" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" style="width: 80%;">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#4DB6AC; color:white;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <h4 class="modal-title"><i class="fa fa-file-text-o"></i> REPORTE DE AJUSTES AUTOMÁTICOS</h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <h5 style="color:#66BB6A;"><i class="fa fa-check"></i> AJUSTADAS: <span id="reporte-ajustadas-count">0</span></h5>
            <div style="max-height:400px; overflow-y:auto;">
              <table class="table table-condensed table-striped" id="tabla-ajustadas">
                <thead>
                  <tr style="background-color:#E8F5E9;">
                    <th>Nº Admin</th>
                    <th>Diferencia</th>
                    <th>Ajuste (Créditos)</th>
                    <th>COINOUT INI Antes</th>
                    <th>COINOUT INI Después</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
          <div class="col-md-6">
            <h5 style="color:#EF5350;"><i class="fa fa-times"></i> NO AJUSTADAS: <span id="reporte-fallidas-count">0</span></h5>
            <div style="max-height:400px; overflow-y:auto;">
              <table class="table table-condensed table-striped" id="tabla-fallidas">
                <thead>
                  <tr style="background-color:#FFEBEE;">
                    <th>Nº Admin</th>
                    <th>Diferencia</th>
                    <th>Razón</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btn-imprimir-reporte"><i class="fa fa-print"></i> Imprimir</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Vista de Tabla Completa -->
<div class="modal fade" id="modalTablaCompleta" tabindex="-1" role="dialog">
  <div class="modal-dialog" style="width: 98%;">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#2196F3; color:white;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <h4 class="modal-title"><i class="fa fa-table"></i> VISTA DE TABLA - <span id="titulo-fecha-producido"></span></h4>
      </div>
      <div class="modal-body">
        <!-- Excel Import -->
        <div style="margin-bottom:10px; padding:8px; background-color:#FFF8E1; border-radius:4px; display:flex; align-items:center; gap:10px;">
          <label class="btn btn-sm btn-warning" style="margin:0; cursor:pointer;" title="Cargar CSV/Excel de Casino Rosario para comparar contadores">
            <i class="fa fa-file-text-o"></i> Cargar CSV
            <input type="file" id="input-excel-tabla" accept=".xls,.xlsx,.csv" style="display:none;">
          </label>
          <span id="excel-tabla-status" style="font-size:12px; color:#5D4037;"></span>
          <button type="button" id="btn-aplicar-excel" class="btn btn-sm btn-success" style="display:none;" title="Aplicar valores del Excel a las filas con valores en 0">
            <i class="fa fa-check"></i> Aplicar a filas vacías
          </button>
        </div>

        <div style="max-height:500px; overflow-y:auto;">
          <table class="table table-condensed table-striped table-bordered" id="tabla-diferencias-completa">
            <thead style="background-color:#E3F2FD; position:sticky; top:0; z-index:10;">
              <tr>
                <th style="width:4%; background-color:#E3F2FD;" title="Número Administrativo de la máquina en el sistema del casino">Nº</th>
                <th style="width:6%; background-color:#E3F2FD;" title="Diferencia entre el Producido Calculado y el Producido Reportado. Debe ser 0 para guardar.">Diferencia</th>
                <th style="width:4%; background-color:#E3F2FD;" title="Denominación Inicial: Factor de conversión de créditos a pesos al inicio del día">Den INI</th>
                <th style="width:7%; background-color:#E3F2FD;" title="CoinIn Inicial: Total de créditos apostados acumulados al inicio del día">CoinIn INI</th>
                <th style="width:7%; background-color:#E3F2FD;" title="CoinOut Inicial: Total de créditos pagados acumulados al inicio del día">CoinOut INI</th>
                <th style="width:7%; background-color:#E3F2FD;" title="Jackpot Inicial: Acumulado de jackpots pagados al inicio del día">Jack INI</th>
                <th style="width:7%; background-color:#E3F2FD;" title="Progresivo Inicial: Acumulado de premios progresivos al inicio del día">Prog INI</th>
                <th style="width:4%; background-color:#E3F2FD;" title="Denominación Final: Factor de conversión de créditos a pesos al final del día">Den FIN</th>
                <th style="width:7%; background-color:#E3F2FD;" title="CoinIn Final: Total de créditos apostados acumulados al final del día">CoinIn FIN</th>
                <th style="width:7%; background-color:#E3F2FD;" title="CoinOut Final: Total de créditos pagados acumulados al final del día">CoinOut FIN</th>
                <th style="width:7%; background-color:#E3F2FD;" title="Jackpot Final: Acumulado de jackpots pagados al final del día">Jack FIN</th>
                <th style="width:7%; background-color:#E3F2FD;" title="Progresivo Final: Acumulado de premios progresivos al final del día">Prog FIN</th>
                <th style="width:10%; background-color:#E3F2FD;" title="Tipo de Ajuste: Define qué valores se usan para calcular. Múltiples Ajustes usa todos los del formulario.">Tipo Ajuste</th>
                <th style="width:5%; background-color:#E3F2FD;" title="Guardar: Solo funciona cuando la diferencia es 0">Acción</th>
              </tr>
            </thead>
            <tbody id="tbody-tabla-diferencias"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <span id="tabla-total-info" style="float:left; margin-top:7px;"></span>
        <button type="button" id="btn-validar-producido-tabla" class="btn btn-success" style="margin-right:10px;" title="Guarda todas las filas con diferencia 0 y valida el producido si todas las máquinas están ajustadas">
          <i class="fa fa-check-circle"></i> FINALIZAR Y VALIDAR PRODUCIDO
        </button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

      </div>
    </div>
  </div>
</div>

<input type="file" id="input-excel" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" style="display:none;">

<meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| PRODUCIDOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Producidos</h5>
      <p>
        Se presenta la información obtenida de producidos por día, según sus estados de validación, de inicio (contador inicial) y final (contador final).
        Se generan planillas con los datos obtenidos, aportando las diferencias con sus respectivos ajustes si los hubiere.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="/js/paginacion.js" charset="utf-8"></script>
    <script src="js/seccionProducidos.js?1" charset="utf-8"></script>
    <script src="/js/perfect-scrollbar.js" charset="utf-8"></script>

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    @endsection
