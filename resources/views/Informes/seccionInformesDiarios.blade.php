@extends('includes.dashboard')
@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<link href="css/bootstrap.min.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/paginacion.css">


@endsection
@section('contenidoVista')


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
              <div class="col-xs-3">
                <h5>Fecha</h5>
                <div class="form-group">
                  <div class='input-group date' id='dtpFechaInfD' data-link-field="fecha_filtro" data-date-format="MM yyyy" data-link-format="yyyy-mm-dd">
                    <input type='text' class="form-control" id="B_fecha_diario" value=""/>
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-xs-3">
                <h5>Casino</h5>
                <select class="form-control" name="" id="select_casino_diario" >
                  <option value="0" selected>- Todos los Casinos -</option>
                  @foreach($casinos as $c)
                  <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-xs-3" >
                <button id="buscar-info-diarios" style="margin-top:30px" class="btn btn-infoBuscar" type="button" name="button">
                    <i class="fa fa-fw fa-search"></i> BUSCAR
                </button>
              </div>
            </div>
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
            <h4>INFORMES DIARIOS</h4>
          </div>
          <div class="panel-body">
            <div class="table table-responsive"  >
              <table id="tablaInfoDiarios" class="table table-responsive tablesorter " width="100%">
                <thead>
                  <tr align="center" >
                    <th value="fecha" class="col-xs-4 activa" estado="desc" style="font-size:14px;text-align:center !important">FECHA DE PRODUCCIÓN<i class="fas fa-sort"></th>
                    <th value="nombre" class="col-xs-4" estado="" style="font-size:14px;text-align:center !important">CASINO<i class="fas fa-sort"></th>
                    <th class="col-xs-4"  style="font-size:14px;text-align:center !important;">ACCIÓN</th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
              </table>
          </div>
            <div class="table table-responsive" id="molde2" style="display:none" >

              <table class="table table-sorter">
                <tr id="moldeInfoDia" class="filaClone" style="display:none">
                  <td class="col-md-4 diario_fecha" style="text-aling:center !important"></td>
                  <td class="col-md-4 diario_casino" style="text-aling:center !important"></td>
                  <td class="col-md-4 diario_accion" style="text-align:center !important;">
                    <button type="button" class="btn btn-info imprimirInfoDiario" value="">
                      <i class="fa fa-fw fa-print"></i>
                    </button>
                  </td>
                </tr>
              </table>
              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
            </div>

          </div>

        </div>
      </div>
    </div> <!-- .row / TABLA -->

</div> <!-- col-xl-3 | COLUMNA DERECHA - BOTONES -->


<!-- MODAL modificar INFORME DIARIO -->
<div class="modal fade" id="modalModificar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width:60%">
    <div class="modal-content">
      <div class="modal-header" style="background-color:#FFA726;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| INFORME DIARIO </h3>
      </div>
    <div  id="colapsado" class="collapse in">
        <div class="modal-body" style="font-family: Roboto;">
          <div class="row">
            <h6 id="fecha_modificar">INFORME DEL DÍA:</h6>
          </div>
          <div class="row">
            <div class="col-xs-12">
              <h6 class="list-group-item"  style="font-size:16px !important; text-align:center !important; background-color:#aaa; color:white;">¿QUÉ DESEA MODIFICAR?</h6>
              <br>
            </div>
            <div class="row">
              <div class="col-xs-12">
                <h6 class="msjSinDiferencias">No hay cierres ni importaciones con diferencias para modificar.</h6>
                <div class="desplegarTablaCI">

                  <table class="table" id="tablaCierresAModificar">
                    <thead>
                      <th style="text-align:center !important">MESA</th>
                      <th style="text-align:center !important">JUEGO</th>
                      <th style="text-align:center !important">CIERRE</th>
                      <th style="text-align:center !important">DETALLE IMPORTACION</th>
                      <th style="text-align:center !important">MONEDA</th>
                    </thead>
                    <tbody>
                    </tbody>
                  </table>
                </div>
                <div class="table-responsive" id="dd" style="display:none">
                  <table class="table">
                    <tr id="moldeModif" style="display:none">
                      <td style="text-align:center !important" class="nro_modificar"></td>
                      <td style="text-align:center !important" class="juego_modificar"></td>
                      <td style="text-align:center !important"> <button type="button" name="button" class="btn btn-info" id="cierre_modificar"  value=""> <i class="fas fa-fw fa-pencil-alt"></i> </button> </td>
                      <td style="text-align:center !important"> <button type="button" name="button" class="btn btn-info" id="id_imp_modificar" value=""> <i class="fas fa-fw fa-pencil-alt"></i> </button> </td>
                      <td style="text-align:center !important" class="moneda_modificar"></td>

                    </tr>
                  </table>
                </div>
              </div>

            </div>
          </div>
          <div class="row desplegarModificarCierre" hidden="true">
            <div class="col-xs-8">
              <h6 text-align="center">FICHAS: </h6>
              <table align="center" class="table-responsive">
                <thead>
                  <tr>
                    <th class="col-xs-4" style="border-bottom:1px solid #ccc;border-right:1px solid #ccc"><h5 align="center">Valor</h5></th>
                    <th class="col-xs-4" style="border-bottom:1px solid #ccc;"><h5 align="center">Monto</h5></th>
                  </tr>
                </thead>
                <tbody id="fichasModif" align="center">
                </tbody>
              </table>
          </div>
          <br>
          <br>
          <br>
          <div class="col-xs-4" align="center">
            <div class="row">
              <h6><b>TOTAL: </b></h6>
              <input id="totalModif" type="text"  style="text-align: center !important"  readonly="true">
            </div>
            <br><br>
          </div>
          </div>


          <div id="mensajeExitoCargaCierre" class="col-xs-8" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#4CAF50;">EXITO</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">El Cierre ha sido modificado correctamente. </span>
          </div> <!-- mensaje -->
          <div class="row desplegarModificarImp" hidden="true">
            <div class="col-xs-12">
                <table  style="border-collapse: collapse; table-layout:auto" align="center" class="table table-bordered" >
                  <thead>
                    <tr  >
                      <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                        <h5  style="font-size: 14px; color:#000;text-align:center !important;">JUEGO</h5>
                      </th>
                      <th  class="col-xs-1" style="text-align:center !important;padding:0px;">
                        <h5  style="font-size: 14px; color:#000;text-align:center !important;">NRO MESA</h5>
                      </th>
                      <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                        <h5 style="font-size: 14px; color:#000;text-align:center !important;">DROP</h5>
                      </th>
                      <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                        <h5 style="font-size: 14px; color:#000;text-align:center !important;">UTILIDAD</h5>
                      </th>
                      <th  class="col-xs-2" style="text-align:center !important;padding:0px;">
                        <h5 style="font-size: 14px; color:#000;text-align:center !important;">RETIROS</h5>
                      </th>
                      <th  class="col-xs-2" style="text-align:center !important;padding:0px;padding:0px;">
                        <h5 style="font-size: 14px; color:#000;text-align:center !important;">REPOSICIONES</h5>
                      </th>
                      <th  class="col-xs-1" style="text-align:center !important;padding:0px;">
                        <h5 style="font-size: 14px; color:#000;text-align:center !important;">HOLD %</h5>
                      </th>
                      <th  class="col-xs-1" style="text-align:center !important;padding:0px;">
                        <h5 style="font-size: 14px; color:#000;text-align:center !important;" id="columnaCot">COTIZACIÓN</h5>
                      </th>
                    </tr>
                  </thead>
                  <tbody id="datosImpModifPesos" >

                  </tbody>
                </table>
                <div class="table table-responsive" id="mostrarTabla"  style="display:none;">

                  <table class="table" style="padding:0px !important">
                    <tbody>
                    <tr id="moldeModifImp" class="filaClone"  style="display:none">
                        <td class="col-xs-2 v_juego" style="text-align:center !important;padding:2px !important;"></td>
                        <td class="col-xs-1 v_mesa" style="text-align:center !important;padding:2px !important;"></td>
                        <td class="col-xs-2" style="text-align:center !important;padding:2px !important;">
                          <input type="text" name="" class="form-control v_drop" value="">
                        </td>
                        <td class="col-xs-2" style="text-align:center !important;padding:2px !important;">
                          <input type="text" name="" class="form-control v_utilidad" value="">
                        </td>
                        <td class="col-xs-2" style="text-align:center !important;padding:2px !important;">
                          <input type="text" name="" class="form-control v_retiros" value="">
                        </td>
                        <td class="col-xs-2" style="text-align:center !important;padding:2px !important;">
                          <input type="text" name="" class="form-control v_reposiciones" value="">
                        </td>
                        <td class="col-xs-1 v_hold" style="text-align:center !important;padding:2px !important;"></td>
                        <td class="col-xs-2" style="text-align:center !important;padding:2px !important;">
                          <input type="text" name="" class="form-control v_cotizacion" value="">
                        </td>
                    </tr>
                </table>
              </div>
            </div>
        </div>
          <div id="mensajeExitoCargaImp" class="col-xs-8" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#4CAF50;">EXITO</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">La Importación ha sido modificada correctamente. </span>
          </div> <!-- mensaje -->

          <div class="modal-footer">
            <button type="button" class="btn btn-warningModificar" id="btn-guardar-importacion" value="nuevo" hidden="true">GUARDAR</button>
            <button type="button" class="btn btn-warningModificar" id="btn-guardar-cierre" value="nuevo" hidden="true">GUARDAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">FINALIZAR</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>




@endsection

@section('scripts')

<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<script src="js/inputSpinner.js" type="text/javascript"></script>
<script src="/js/lista-datos.js" type="text/javascript"></script>
<script src="/js/paginacion.js" charset="utf-8"></script>

<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>
<script type="text/javascript" src="/js/Informes/seccionInformesDiarios.js"></script>

@endsection
