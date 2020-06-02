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
          <div class="col-md-12">
              <div id="contenedorFiltros" class="panel panel-default">
                <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                  <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                </div>
                <div id="collapseFiltros" class="panel-collapse collapse">
                  <div class="panel-body">
                    <div class="row">
                      <div class="col-md-3">
                          <h5>Apellido</h5>
                          <input class="form-control" id="buscadorApellido" value=""/>
                      </div>
                      <div class="col-md-3">
                          <h5>DNI</h5>
                          <input class="form-control" id="buscadorDni" value=""/>
                      </div>
                      <div class="col-md-3">
                          <h5>Casino</h5>
                          <select id="buscadorCasino" class="form-control selectCasinos" name="">
                              <option value="0">-Todos los Casinos-</option>
                              @foreach ($casinos as $casino)
                                <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                              @endforeach
                          </select>
                      </div>
                      <div class="col-md-3">
                        <h5>Fecha ae</h5>
                        <div class="input-group date" id="dtpFechaAutoexclusion" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                            <input type="text" class="form-control" placeholder="Fecha de autoexclusion" id="buscadorFechaAutoexclusion" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                            <span id="input-times-autoexclusion" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                            <span id="input-calendar-autoexclusion" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                      </div>
                    </div><br>

                    <div class="row">
                      <div class="col-md-3">
                        <h5>Fecha vencimiento</h5>
                        <div class="input-group date" id="dtpFechaVencimiento" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                            <input type="text" class="form-control" placeholder="Fecha de vencimiento" id="buscadorFechaVencimiento" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                            <span id="input-times-vencimiento" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                            <span id="input-calendar-vencimiento" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                      </div>
                    </div>

                    <br>
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
          <div class="col-md-12">
            <div class="panel panel-default">
              <div class="panel-heading">
                <h4>LISTADO DE AUTOEXCLUIDOS REVOCABLES</h4>
              </div>

              <div class="panel-body">
                <table id="tablaAutoexcluidosRevocables" class="table table-fixed tablesorter">
                  <thead>
                    <tr>
                      <th class="col-xs-2 activa">DNI<i class="fa fa-sort-desc"></i></th>
                      <th class="col-xs-2">APELLIDO<i class="fa fa-sort"></i></th>
                      <th class="col-xs-2">NOMBRES<i class="fa fa-sort"></i></th>
                      <th class="col-xs-2">FECHA AE<i class="fa fa-sort"></i></th>
                      <th class="col-xs-2">VENC. 1° PERÍODO<i class="fa fa-sort"></i></th>
                      <th class="col-xs-2">ACCIONES<i class="fa fa-sort"></i></th>
                    </tr>
                  </thead>
                  <tbody id="cuerpoTabla" style="height: 350px;">
                    <tr class="filaTabla" style="display: none">
                      <td class="col-sm-2 dni"></td>
                      <td class="col-xs-2 apellido"></td>
                      <td class="col-xs-2 nombres"></td>
                      <td class="col-xs-2 fecha_ae"></td>
                      <td class="col-xs-2 fecha_vencimiento_primer_periodo"></td>
                      <td class="col-xs-2 acciones">
                        <button id="btnImprimirFormularioFinalizacionAE" class="btn btn-info planilla" type="button">
                          <i class="far  fa-fw fa-file-alt"></i></button>
                        <button id="btnFinalizarAE" class="btn btn-warning carga" type="button">
                          <i class="fa fa-fw fa-check"></i></button>
                        <span></span>
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
  <script src="/js/Autoexclusion/vencimientos.js" charset="utf-8"></script>
  <!-- Custom input Bootstrap -->
  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <!-- DateTimePicker JavaScript -->
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
  @endsection
