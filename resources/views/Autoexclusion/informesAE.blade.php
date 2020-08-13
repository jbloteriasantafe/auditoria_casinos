@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use\App\http\Controllers\RelevamientoAmbientalController;
?>

@section('estilos')
<link rel="stylesheet" href="/css/paginacion.css">
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/animacionCarga.css">

<style>
.page {
  display: none;
}
.active {
  display: inherit;
}
.easy-autocomplete{
width:initial!important
}

/* Make circles that indicate the steps of the form: */
.step {
height: 15px;
width: 15px;
margin: 0 2px;
background-color: #bbbbbb;
border: none;
border-radius: 50%;
display: inline-block;
opacity: 0.5;
}

/* Mark the active step: */
.step.actived {
opacity: 1;
}

/* Mark the steps that are finished and valid: */
.step.finish {
background-color: #4CAF50;
}

</style>
@endsection

@section('contenidoVista')

    <div class="col-xl-9">

      <!-- FILTROS DE BÚSQUEDA -->
      <div class="row">
          <div>
              <div id="contenedorFiltros" class="panel panel-default" style="width: 100%">
                <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                  <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                </div>
                <div id="collapseFiltros" class="panel-collapse collapse">
                  <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Casino</h5>
                            <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                <option value="0">-Todos los Casinos-</option>
                                @foreach ($casinos as $casino)
                                  <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <h5>Estado</h5>
                            <select id="buscadorEstado" class="form-control selectEstado" name="">
                              <option selected="" value="">- Todos los estados -</option>
                              @foreach ($estados_autoexclusion as $estado)
                                <option id="{{$estado->id_nombre_estado}}" value="{{$estado->id_nombre_estado}}">{{$estado->descripcion}}</option>
                              @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <h5>Apellido</h5>
                            <input class="form-control" id="buscadorApellido" value=""/>
                        </div>


                    </div><br>

                    <div class="row">
                      <div class="col-md-3">
                          <h5>DNI</h5>
                          <input class="form-control" id="buscadorDni" value=""/>
                      </div>
                      <div class="col-md-3">
                          <h5>Sexo</h5>
                          <select id="buscadorSexo" class="form-control selectSexo" name="">
                            <option selected="" value="">- Todos -</option>
                            <option id="0" value="0">Masculino</option>
                            <option id="1" value="1">Femenino</option>
                            <option id="-1" value="-1">Otro</option>
                          </select>
                      </div>
                      <div class="col-md-3">
                          <h5>Localidad</h5>
                          <input class="form-control" id="buscadorLocalidad" value=""/>
                      </div>
                      <div class="col-md-3">
                          <h5>Provincia</h5>
                          <input class="form-control" id="buscadorProvincia" value=""/>
                      </div>
                    </div><br>

                    <div class="row">
                        <div class="col-md-3">
                          <h5>Fecha de autoexclusión - Desde</h5>
                          <div class="input-group date" id="dtpFechaAutoexclusionD">
                              <input type="text" class="form-control" placeholder="Fecha de autoexclusión (desde)" id="buscadorFechaAutoexclusionD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-autoexclusionD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-autoexclusionD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de autoexclusión- Hasta</h5>
                          <div class="input-group date" id="dtpFechaAutoexclusionH">
                              <input type="text" class="form-control" placeholder="Fecha de autoexclusión (hasta)" id="buscadorFechaAutoexclusionH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-autoexclusionH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-autoexclusionH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de vencimiento - Desde</h5>
                          <div class="input-group date" id="dtpFechaVencimientoD">
                              <input type="text" class="form-control" placeholder="Fecha de vencimiento (desde)" id="buscadorFechaVencimientoD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-vencimientoD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-vencimientoD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de vencimiento - Hasta</h5>
                          <div class="input-group date" id="dtpFechaVencimientoH">
                              <input type="text" class="form-control" placeholder="Fecha de vencimiento (hasta)" id="buscadorFechaVencimientoH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-vencimientoH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-vencimientoH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                    </div><br>

                    <div class="row">
                        <div class="col-md-3">
                          <h5>Fecha de revocación - Desde</h5>
                          <div class="input-group date" id="dtpFechaRevocacionD">
                              <input type="text" class="form-control" placeholder="Fecha de revocación (desde)" id="buscadorFechaRevocacionD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-revocacionD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-revocacionD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de revocación - Hasta</h5>
                          <div class="input-group date" id="dtpFechaRevocacionH">
                              <input type="text" class="form-control" placeholder="Fecha de revocación (hasta)" id="buscadorFechaRevocacionH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-revocacionH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-revocacionH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de cierre autoexclusión - Desde</h5>
                          <div class="input-group date" id="dtpFechaCierreD">
                              <input type="text" class="form-control" placeholder="Fecha de cierre AE (desde)" id="buscadorFechaCierreD" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-cierreD" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-cierreD" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                        <div class="col-md-3">
                          <h5>Fecha de cierre autoexclusión - Hasta</h5>
                          <div class="input-group date" id="dtpFechaCierreH">
                              <input type="text" class="form-control" placeholder="Fecha de cierre AE (hasta)" id="buscadorFechaCierreH" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-cierreH" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-cierreH" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                        </div>
                  </div><br>

                    <div class="row">
                      <center>
                        <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                      </center>
                    </div>
                    <br>
                  </div>
                </div>
              </div>
          </div>
      </div>


      <div class="row">
        <div>
          <div class="panel panel-default" style="width: 100%;">
            <div class="panel-heading">
              <h4>LISTADO DE AE</h4>
            </div>

            <div class="panel-body">
              <table id="tablaInformesAE" class="table table-fixed tablesorter">
                <thead>
                  <tr>
                    <th style="width: 5%;" value="ae_estado.id_casino" estado="">CAS<i class="fa fa-sort"></i></th>
                    <th style="width: 13%;" value="ae_datos.nro_dni" estado="">DNI<i class="fa fa-sort"></i></th>
                    <th style="width: 14%;" value="estado" estado="">ESTADO<i class="fa fa-sort"></i></th>
                    <th style="width: 9%;" value="ae_datos.apellido" estado="">APELLIDO<i class="fa fa-sort"></i></th>
                    <th style="width: 9%;" value="ae_datos.nombres" estado="">NOMBRES<i class="fa fa-sort"></i></th>
                    <th style="width: 9%;" value="ae_datos.nombre_localidad" estado="">LOCALIDAD<i class="fa fa-sort"></i></th>
                    <th style="width: 9%;" value="ae_datos.nombre_provincia" estado="">PROVINCIA<i class="fa fa-sort"></i></th>
                    <th style="width: 8%;" value="ae_estado.fecha_ae" estado="">F. AE<i class="fa fa-sort"></i></th>
                    <th style="width: 8%;" value="ae_estado.fecha_vencimiento" estado="">F. VENC<i class="fa fa-sort"></i></th> 
                    <th style="width: 8%;" value="ae_estado.fecha_revocacion_ae" estado="">F. REVOC<i class="fa fa-sort"></i></th>
                    <th style="width: 8%;" value="ae_estado.fecha_cierre_ae" estado="">F. CIERRE<i class="fa fa-sort"></i></th>
                  </tr>
                </thead>
                <tbody id="cuerpoTabla" style="height: 350px;">
                  <tr class="filaTabla" style="display: none">
                    <td style="width: 5%;" class="casino"></td>
                    <td style="width: 13%;" class="dni" >
                      <a target="_blank" class="link" title="VER AUTOEXCLUSIÓN">9999</a>
                      <a target="_blank" class="btnVerFoto btn btn-info planilla" type="button" data-toggle="tooltip" data-placement="top" title="VER FOTO" data-delay="{'show':'300', 'hide':'100'}">
                        <i class="far  fa-fw fa-images"></i></a>
                      <span></span>
                    </td>
                    <td style="width: 14%;" class="estado"></td>
                    <td style="width: 9%;" class="apellido"></td>
                    <td style="width: 9%;" class="nombres"></td>
                    <td style="width: 9%;" class="localidad"></td>
                    <td style="width: 9%;" class="provincia" ></td>
                    <td style="width: 8%;" class="fecha_ae" ></td>
                    <td style="width: 8%;" class="fecha_vencimiento_primer_periodo" ></td>
                    <td style="width: 8%;" class="fecha_finalizacion" ></td>
                    <td style="width: 8%;" class="fecha_cierre_ae" ></td>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
            </div>
        </div>
      </div>
    </div>  <!-- row tabla -->


      <!--MODAL FINALIZAR AE -->
      <div class="modal fade" id="modalFinalizarAE" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog" style="width: 37%">
               <div class="modal-content">
                 <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <button id="btn-minimizarCrear" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrear" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                   <h3 class="modal-title" style="background-color: #6dc7be;">| FINALIZAR AUTOEXCLUSIÓN</h3>
                  </div>

                  <div  id="colapsadoCrear" class="collapse in">
                  <div class="modal-body modalCuerpo">
                      <div class="row">
                        <div class="col-md-6">
                          <h5>FECHA DE FINALIZACIÓN DE AE</h5>
                          <div class="input-group date" id="dtpFechaFinalizacionAE" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                              <input type="text" class="form-control" placeholder="Fecha de finalización de AE" id="buscadorFechaFinalizacionAE" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                              <span id="input-times-finalizacion" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                              <span id="input-calendar-finalizacion" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                          </div>
                          <br>
                        </div>
                        <div class="col-md-6">
                          <h5>FORMULARIO DE FINALIZACIÓN DE AE</h5>
                          <input id="formularioFinalizacionAE" type="file" name="formularioFinalizacionAE">
                          <br>
                        </div>
                      </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-successAceptar" id="btn-finalizar-ae" value="nuevo">FINALIZAR AE</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  </div>
                </div>
              </div>
            </div>
      </div>

    </div> <!-- row principal -->


  <!-- token -->
  <meta name="_token" content="{!! csrf_token() !!}" />
  @endsection


  <!-- Comienza modal de ayuda -->
  @section('tituloDeAyuda')
  <h3 class="modal-title2" style="color: #fff;">| SESIONES</h3>
  @endsection
  @section('contenidoAyuda')
  <div class="col-md-12">
    <h5>Tarjeta de Sesiones</h5>
    <p>
      Agregar nuevos autoexluidos, revocar autoexclusiones, ver listado y estados.
  </div>
  @endsection
  <!-- Termina modal de ayuda -->


  @section('scripts')
  <!-- JavaScript paginacion -->
  <script src="js/paginacion.js" charset="utf-8"></script>
  <!-- JavaScript personalizado -->
  <script src="/js/Autoexclusion/informesAE.js" charset="utf-8"></script>
  <!-- Custom input Bootstrap -->
  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <!-- DateTimePicker JavaScript -->
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
  @endsection
