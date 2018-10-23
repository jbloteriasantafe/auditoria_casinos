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
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="css/zona-file-large.css">
<link rel="stylesheet" href="/css/perfect-scrollbar.css">
<!-- Mesaje de notificación -->
<link rel="stylesheet" href="/css/mensajeExito.css">
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
                                <div class="col-lg-3">
                                  <h5>Casino</h5>
                                  <select class="form-control" id="selectCasinos">
                                    <option value="0">- Seleccione un casino -</option>
                                     @foreach ($casinos as $casino)
                                     <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                     @endforeach
                                  </select>
                                </div>
                                <div class="col-lg-3">
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
                                <div class="col-lg-3">
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
                                <div class="col-lg-3">
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
                          <th class="col-xs-2">CASINO</th>
                          <th class="col-xs-2">FECHA</th>
                          <th class="col-xs-1">MONEDA</th>
                          <th class="col-xs-1">VALIDADO</th>
                          <th class="col-xs-1">CONT INI</th>
                          <th class="col-xs-2">RELEVAMIENTOS VISADOS</th>
                          <th class="col-xs-2">ACCIÓN</th>
                        </tr>
                      </thead>
                      <tbody style="height: 350px;">
                        @foreach($producidos as $producido)
                          <tr id="{{$producido['producido']->id_producido}}">
                            <td class="col-xs-2">{{$producido['producido']->casino->nombre}}</td>
                            <td class="col-xs-2">{{$producido['producido']->fecha}}</td>
                            <td class="tipo_moneda col-xs-1" data-tipo="{{$producido['producido']->tipo_moneda->id_tipo_moneda}}">{{$producido['producido']->tipo_moneda->descripcion}}</td>
                            <!-- si el producido esta validado -->
                            @if($producido['producido']->validado == 1)
                                <td class="col-xs-1"><i class="fa fa-fw fa-check" style="color:#66BB6A;"></td>
                            @else
                                <td class="col-xs-1"><i class="fas fa-fw fa-times" style="color:#EF5350;"></td>
                            @endif

                            <!-- iniciales cerrados -->
                            @if(empty($producido['cerrado']))
                                <td class="col-xs-1"><i class="fa fa-fw fa-check" style="color:#66BB6A;"></td>
                            @else
                                <td class="col-xs-1"><i class="fas fa-fw fa-times" style="color:#EF5350;"></td>
                            @endif

                            <!--  si los contadores finales estan validados-->
                            @if(empty($producido['validado']))
                                <td class="col-xs-2"><i class="fa fa-fw fa-check" style="color:#66BB6A;"></td>
                            @else
                                <td class="col-xs-2"><i class="fas fa-fw fa-times" style="color:#EF5350;"></td>
                            @endif
                            <td class="col-xs-2">
                              @if(empty($producido['cerrado']) && empty($producido['validado']) && $producido['producido']->validado == 0)
                                <button class="btn btn-warning carga popInfo" type="button" value="{{$producido['producido']->id_producido}}" data-trigger="hover" data-toggle="popover" data-placement="top" data-content="Ajustar"><i class="fa fa-fw fa-upload"></i></button>
                              @endif
                                <button class="btn btn-info planilla popInfo" type="button" value="{{$producido['producido']->id_producido}}" data-trigger="hover" data-toggle="popover" data-placement="top" data-content="Imprimir" ><i class="fa fa-fw fa-print"></i></button>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
                  </div>
              </div>
            </div>
            <!-- /.col-lg-12 col-xl-9 -->
            <div class="col-lg-12 col-xl-3">
              <div class="row">
                @foreach($ultimos as $p_a_validar)
                @if($p_a_validar['producido']!=null && empty($p_a_validar['validado']))
                <div class="col-lg-12">
                 <a href="" class="btn-ajustar" style="text-decoration: none;" value="{{$p_a_validar['producido']->id_casino}}" data-producido="{{$p_a_validar['producido']->id_producido}}">
                  <div class="panel panel-default panelBotonNuevo">
                      <center><img class="imgNuevo" src="/img/logos/CSV_white.png"><center>
                      <div class="backgroundNuevo"></div>
                      <div class="row">
                          <div class="col-xs-12">
                            <center>
                                <h5 class="txtLogo">+<span style="font-size:100px; position:relative; top:-8px;">{{$p_a_validar['descripcion']}}</span></h5>
                                <h4 class="txtNuevo">AJUSTAR ÚLTIMO PRODUCIDO</h4>
                            </center>
                          </div>
                      </div>
                  </div>
                 </a>
                </div>
                @endif
                @endforeach
              </div>

              <div class="row">
                <div class="col-lg-12">
                  <a href="importaciones" style="text-decoration:none;">
                      <div class="tarjetaSeccionMenor" align="center">
                        <h2 class="tituloFondoMenor">IMPORTACIONES</h2>
                        <h2 class="tituloSeccionMenor">IMPORTACIONES</h2>
                        <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/importaciones_white.png" alt="">
                      </div>
                  </a>
                  <!-- <a href="importaciones" style="text-decoration:none;">
                    <div class="tarjetaSeccionMenor">
                        <div class="imagenSeccionMenor" >
                            <img src="/img/tarjetas/resoluciones.jpg" alt="">
                        </div>
                        <div class="fondoSeccionMenor">
                            <h2 class="tituloSeccionMenor">IMPORTACIONES</h2>
                            <img width="180" class="iconoSeccionMenor" src="/img/logos/importaciones_white.png" alt="">
                        </div>
                    </div>
                  </a> -->
                </div>
              </div>

            </div>
        </div>  <!-- /#row -->


<!--Modal nuevo para ajustes-->

<div class="modal fade" id="modalCargaProducidos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 60%;">
    <div class="modal-content" >
      <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#FFB74D;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">VALIDAR AJUSTES</h3>
      </div>

      <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">

          <div class="row" >
            <div class="col-md-3">
              <h6><b>MÁQUINAS</b></h6>
              <table id="tablaMaquinas" class="table" style="display: block;">
                <thead style="display: block;position: relative;">
                  <tr >
                    <th class="col-xs-2">Nº ADMIN</th>
                    <th class="col-xs-2"></th>
                  </tr>
                </thead>
                <tbody id="cuerpoTabla"  style="display: block;overflow: auto;height: 700px;">
                </tbody>
              </table>
              <table>
              <tbody id="filaClon" style="display:none" class="filaCl" >
                  <td class="col-md-3 nroAdm" value=""> nro admin</td>
                  <td class="col-md-2 idMaqTabla" value=""> <button type="button" class="btn btn-info infoMaq" value="">
                    <i class="fa fa-fw fa-eye"></i>
                  </button></td>
              </tbody>
              </table>
            </div> <!-- tablafechas -->

            <div id="columnaDetalle" class="col-md-9" style="border-right:2px solid #ccc;" hidden>
              <h6 id="detallesEs"><b>DETALLES</b></h6>
              <br>
              <br>
              <div class="detalleMaq" >
                <form id="frmCargaProducidos" name="frmCargaProducidos" class="form-horizontal" novalidate="">

                  <div class="row" style="border-top: 1px solid #ccc;  border-left:1px solid #ccc;border-right:1px solid #ccc;border-bottom:1px solid #ccc; padding-top:30px; padding-bottom:30px;" >
                    <div class="col-lg-3">
                      <h5>COININ. INICIAL</h5>
                      <input id="coininIni" type="text" class="form-control">
                      <br>
                    </div> <!-- nro admin -->
                    <div class="col-lg-3">
                      <h5>COINOUT INI.</h5>
                      <input id="coinoutIni" type="text" class="form-control" >
                      <br>
                    </div> <!-- Fisca toma -->

                    <div class="col-lg-3">
                      <h5>JACKPOT INI.</h5>
                      <input id="jackIni" type="text" class="form-control">
                      <br>
                    </div> <!-- fisca carga-->
                    <div class="col-lg-3">
                      <h5>PROG. INICIAL</h5>
                      <input id="progIni" type="text" class="form-control" >
                      <br>
                    </div> <!-- nro admin -->

                  </div>

                  <div class="row" style="border-left:1px solid #ccc;border-right:1px solid #ccc;">

                    <br>

                    <div class="col-lg-3">
                      <h5>COININ FINAL</h5>
                      <input id="coininFin" type="text" class="form-control">
                      <br>
                    </div>
                    <div class="col-lg-3">
                      <h5>COINOUT FINAL</h5>
                      <input id="coinoutFin" type="text" class="form-control">
                      <br>
                    </div>
                    <div class="col-lg-3">
                      <h5>JACKPOT FINAL</h5>
                      <input id="jackFin" type="text" class="form-control" >
                      <br>
                    </div>
                    <div class="col-lg-3">
                      <h5>PROG. FINAL</h5>
                      <input id="progFin" type="text" class="form-control" >
                      <br>
                    </div>
                  </div>

                  <div class="row" style=" border-top: 1px solid #ccc; border-left:1px solid #ccc;border-right:1px solid #ccc;border-bottom:1px solid #ccc; padding-top:30px; padding-bottom:30px;">

                    <div class="col-lg-3">
                      <h5>PRODUC.CALC.</h5>
                      <input id="prodCalc" type="text" class="form-control" readonly="readonly">
                      <br>
                    </div>
                    <div class="col-lg-3">
                      <h5>PRODUCIDO SIST.</h5>
                      <input id="prodSist" type="text" class="form-control" >
                      <br>
                    </div>

                    <div class="col-lg-3">
                        <h5>DIFERENCIAS</h5>
                        <h6 id="diferencias" style="font-size:20px;font-family: Roboto-Regular; color:#000000;  padding-left:  15px;"></h6>
                    </div>
                    <div class="col-lg-3">
                        <h5>OBSERVACIONES</h5>
                        <select class="form-control" id="observacionesAjuste">
                           <option class="default1" value="0">-Tipo Ajuste-</option>
                        </select>
                    </div>
                  </div>
                  <div class="row" hidden>
                    <div class="col-lg-2">
                      <input id="data-denominacion" type="text" class="form-control" >
                      <br>
                    </div>
                    <div class="col-lg-2">
                      <input id="data-contador-final" type="text" class="form-control" >
                      <br>
                    </div>
                    <div class="col-lg-2">
                      <input id="data-contador-inicial" type="text" class="form-control" >
                      <br>
                    </div>
                    <div class="col-lg-2">
                      <input id="data-producido" type="text" class="form-control" >
                      <br>
                    </div>
                    <div class="col-lg-2">
                      <input id="data-detalle-inicial" type="text" class="form-control" >
                      <br>
                    </div>
                    <div class="col-lg-2">
                      <input id="data-detalle-final" type="text" class="form-control" >
                      <br>
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
        <button type="button" class="btn btn-warningModificar" id="btn-guardar" value="nuevo">GUARDAR TEMPORALMENTE</button>
        <button type="button" class="btn btn-warningModificar" id="btn-finalizar" value="nuevo">FINALIZAR AJUSTES</button>
        <button type="button" class="btn btn-default" id="btn-salir" >SALIR</button>
        <div class="mensajeSalida">
            <br>
            <span style="font-family:'Roboto-Black'; color:#EF5350;">CAMBIOS SIN GUARDAR</span>
            <br>
            <span style="font-family:'Roboto'; color:#555;">Presione SALIR nuevamente para salir sin guardar cambios.</span>
            <span style="font-family:'Roboto'; color:#555;">Presione GUARDAR TEMPORALMENTE para guardando los cambios y luego SALIR.</span>
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





    <!-- Modal planilla relevamientos -->
    <div class="modal fade" id="modalPlanilla" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog" style="width:80%;">
             <div class="modal-content">
               <div class="modal-header" style="font-family:'Roboto-Black';color:white;background-color:#42A5F5;">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCargar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">IMPRIMIR PLANILLA</h3>
                </div>

                <div  id="colapsadoCargar" class="collapse in">

                  <div class="modal-body modalCuerpo">

                    <form id="frmPlanilla" name="frmPlanilla" class="form-horizontal" novalidate="">
                            <div class="row">
                                <div class="col-md-12">
                                    <!-- Carga de archivos! | Uno para el modal de nuevo y otro para modificar -->
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
                    <input type="hidden" id="id_producido" value="0">
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
    <script src="js/seccionProducidos.js" charset="utf-8"></script>

    <script src="/js/perfect-scrollbar.js" charset="utf-8"></script>



    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    <script type="text/javascript">
    var ps = new PerfectScrollbar('.opcionesMenu');
    </script>

    @endsection
