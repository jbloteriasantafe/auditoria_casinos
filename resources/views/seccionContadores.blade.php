@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="css/paginacion.css">
<link rel='stylesheet' href='/css/fullcalendar.min.css'/>
<style>
#tablaContadores > thead > tr > th {
  text-align: center;
}
#tablaContadores > tbody > tr > td {
  text-align: center;
}
</style>
@endsection

        <div class="row">

            <div class="col-xl-9">
                <div class="row"> <!-- FILTROS -->
                      <div class="col-md-12">
                        <div class="panel panel-default">
                          <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                            <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                          </div>
                          <div id="collapseFiltros" class="panel-collapse collapse">
                            <div class="panel-body">
                              <div class="row"> <!-- Primera fila -->
                                <div class="col-lg-2">
                                  <h5>Casino</h5>
                                  <select class="form-control" id="selectCasinos">
                                    @if($casinos->count() == 1)
                                    <option id="{{$casinos[0]->id_casino}}" value="{{$casinos[0]->id_casino}}">{{$casinos[0]->nombre}}</option>
                                    @else
                                    <option value="">- Casino -</option>
                                     @foreach ($casinos as $casino)
                                     <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                     @endforeach
                                    @endif
                                  </select>
                                </div>
                                <div class="col-lg-2">
                                  <h5>Moneda</h5>
                                  <select class="form-control" id="selectTipoMoneda">
                                    <option value="">- Moneda -</option>
                                     @foreach ($tipo_monedas as $tipo_moneda)
                                     <option id="{{$tipo_moneda->id_tipo_moneda}}" value="{{$tipo_moneda->id_tipo_moneda}}">{{$tipo_moneda->descripcion}}</option>
                                     @endforeach
                                  </select>
                                </div>
                                <div class="col-lg-3">
                                    <h5>Fecha Desde</h5>
                                    <div class="form-group">
                                       <div class='input-group date' id='dtpFechaDesde' data-link-field="fecha_desde" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                                           <input type='text' class="form-control" placeholder="Fecha de Inicio" id="B_fecha_inicio"/>
                                           <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                           <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                       </div>
                                       <input class="form-control" type="hidden" id="fecha_desde" value=""/>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                  <h5>Fecha Hasta</h5>
                                  <div class="form-group">
                                     <div class='input-group date' id='dtpFechaHasta' data-link-field="fecha_hasta" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                                         <input type='text' class="form-control" placeholder="Fecha Hasta" id="B_fecha_inicio"/>
                                         <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                         <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                     </div>
                                     <input class="form-control" type="hidden" id="fecha_hasta" value=""/>
                                  </div>
                                </div>
                                <div class="col-lg-2">
                                  <h5>Validado</h5>
                                  <select class="form-control" id="selectValidado">
                                    <option value="">- TODOS -</option>
                                    <option value="0">NO</option>
                                    <option value="1">SI</option>
                                  </select>
                                </div>
                              </div>
                              <div class="row">
                                  <div class="col-md-12">
                                    <center><button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button></center>
                                  </div>
                              </div>
                              <br>
                            </div> <!-- /.panel-body -->
                          </div>
                        </div> <!-- /.panel -->
                      </div>
                </div>

                <div class="row"> <!-- TABLA -->
                      <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>ÚLTIMOS CONTADORES</h4>
                            </div>
                            <div class="panel-body">
                              <table id="tablaContadores" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-3">FECHA</th>
                                    <th class="col-xs-2">CASINO</th>
                                    <th class="col-xs-2">MONEDA</th>
                                    <th class="col-xs-2">VALIDADO</th>
                                    <th class="col-xs-3">ACCIÓN</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTablaResultados" style="height: 350px;">
                                </tbody>
                              </table>
                              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                              </div>
                            </div>
                          </div>
                      </div>
                </div>
            <table hidden>
              <tr id="filaEjemploResultado">
                <td class="col-xs-3 fecha">9999-99-99</td>
                <td class="col-xs-2 casino">CAS</td>
                <td class="col-xs-2 moneda">MON</td>
                <td class="col-xs-2 validado">
                  <i class="fa fa-check" style="color: green;"></i>
                  <i class="fa fa-times" style="color: red;"></i>
                </td>
                <td class="col-xs-3 accion">
                  <button class="btn btn-info ver" title="VER">
                    <i class="fa fa-fw fa-search-plus"></i>
                  </button>
                  <button class="btn btn-info validar" title="VALIDAR">
                    <i class="fa fa-fw fa-check"></i>
                  </button>
                  <button class="btn btn-info imprimir" title="IMPRIMIR">
                    <i class="fa fa-fw fa-print"></i>
                  </button>
                </td>
              </tr>
            </table>

            <div class="col-xl-3">
              <div class="row">
                <div class="col-md-12">
                  <a href="importaciones" style="text-decoration:none;">
                    <div class="tarjetaSeccionMenor" align="center">
                      <h2 class="tituloFondoMenor">IMPORTACIONES</h2>
                      <h2 class="tituloSeccionMenor">IMPORTACIONES</h2>
                      <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/importaciones_white.png" alt="">
                    </div>
                  </a>
                </div>
              </div>
            </div> <!-- /.col-md-3 -->

        </div> <!-- /.row -->

    <div class="modal fade" id="modalValidarBeneficio" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:70%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FFB74D;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">VALIDAR BENEFICIOS</h3>
                </div>

                <div  id="colapsadoCargar" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmValidarBeneficio" name="frmValidarBeneficio" class="form-horizontal" novalidate="">
                          <div class="row">
                            <div class="col-md-3">
                              <h5>Casino</h5>
                              <input type="text" readonly="true" id="casinoModal" disabled class="form-control">
                            </div>
                            <div class="col-md-3">
                              <h5>Tipo Moneda</h5>
                              <input type="text" readonly="true" id="tipoMonedaModal" disabled class="form-control">
                            </div>
                            <div class="col-md-3">
                              <h5>Año</h5>
                              <input type="text" readonly="true" id="anioModal" disabled class="form-control">
                            </div>
                            <div class="col-md-3">
                              <h5>Mes</h5>
                              <input type="text" readonly="true" id="mesModal" disabled class="form-control">
                            </div>
                          </div>
                          <br>
                          <div class="row">
                              <div class="col-md-12">
                                  <table id="tablaModal" class="table">
                                      <thead>
                                        <tr>
                                            <th width="10%">FECHA</th>
                                            <th width="12%">CALCULADO</th>
                                            <th width="12%">BENEFICIO</th>
                                            <th width="12%">DIFERENCIA</th>
                                            <th width="3%"></th>
                                            <th width="49%">OBSERVACIÓN</th>
                                            <th width="3%"></th>
                                        </tr>
                                      </thead>
                                      <tbody id="cuerpoTabla" style="color:black;">


                                      </tbody>
                                  </table>
                              </div>
                          </div>

                          <br>
                  </form>
                  <div class="row" align="left" style="margin-right:10px; font-weight:bold">
                      <h4 id="textoExito"></h4>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-warningModificar" id="btn-validar-si" value="nuevo">SI</button>
                  <button type="button" class="btn btn-warningModificar" id="btn-validar" value="nuevo">VALIDAR BENEFICIOS</button>
                  <button type="button" class="btn btn-default" id="btn-salir" data-dismiss="modal">SALIR</button>
                  <input type="hidden" id="id_beneficio" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal planilla relevamientos -->
    <div class="modal fade" id="modalPlanilla" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:80%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#42A5F5;">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">IMPRIMIR PLANILLA</h3>
                </div>

                <div  id="colapsadoCargar" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmPlanilla" name="frmPlanilla" class="form-horizontal" novalidate="">

                          <div class="row">
                              <div class="col-md-12">
                                  <div class="zona-file-lg">
                                      <input id="cargaArchivo" data-borrado="false" type="file" multiple>
                                  </div>
                                  <div class="alert alert-danger fade in" role="alert" id="alertaArchivo"><span></span></div>
                              </div>
                          </div>

                  </form>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-imprimirPlanilla">IMPRIMIR</button>
                  <button type="button" class="btn btn-default" id="btn-salirPlanilla" data-dismiss="modal">SALIR</button>
                  <input type="hidden" id="id_relevamiento" value="0">
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
                  <h3 class="modal-title">ADVERTENCIA</h3>
                </div>

                <div class="modal-body franjaRojaModal">
                  <form id="frmEliminar" name="frmCasino" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Seguro desea eliminar Producido? Podría ocasionar errores serios en el sistema.</strong>
                          </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-danger" id="btn-eliminarModal" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>

    
    <!-- Modal cotizacion -->
      <div class="modal fade" id="modal-cotizacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg"  >
              <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title">| COTIZACIÓN DÓLAR->PESO</h3>
                </div>

                <div class="modal-body" style="background-color: white;">

                    <div class="row" style="padding-bottom: 15px;">
                        <div class="col-md-12">
                            <div id="calendarioInicioBeneficio"></div>
                      </div>

                </div>

                <div class="modal-footer">
                  <label id="labelCotizacion" for="number"> </label>
                  <input id="valorCotizacion" type="number" step="0.001" min="25" max="200" placeholder="xx,xxx">
                  <button type="button" class="btn btn-successAceptar" id="guardarCotizacion">GUARDAR</button>
                </div> 
            </div>
          </div>
      </div>
    


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| CONTADORES</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <p>
        En esta sección se obtienen alertas sobre la evolución de los contadores individuales para cada maquina.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>

    
    <script src='/js/moment.min.js'></script>
    <script src='/js/fullcalendar.min.js'></script>
    <script src='/js/locale-all.js'></script>

    <!-- JavaScript personalizado -->
    <script src="js/seccionContadores.js" charset="utf-8"></script>-
    
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    @endsection
