@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoInformes">@svg('informes','iconoInformes')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
setlocale(LC_TIME, 'es_ES.UTF-8');
?>

@section('estilos')
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
@endsection

        <!-- <div class="container-fluid">
          <div class="row">
            <div class="col-md-12 bannerImportaciones">
              <h1><img width="80" src="/img/logos/informes_juegos_blue.png" alt=""> INFORMES DE JUEGOS</h1>
            </div>
          </div>
        </div> -->

                <div class="row">

                  <div class="col-md-4">
                      <div class="row">
                          <div class="col-lg-12">
                            <div class="panel">
                                <center><img width="100%" src="/img/tarjetas/banner_CSF.jpg"></center>
                            </div>
                          </div>
                      </div>
                      <div class="row">
                          <div class="col-lg-12 col-xl-12">
                            <div class="panel panel-default">
                              <div class="panel-heading">
                                  <h4>Beneficios - Juegos Santa fe</h4>
                              </div>
                              <div class="panel-body">
                                <table id="" class="table table-fixed tablesorter">
                                  <thead>
                                      <tr>
                                        <th class="col-xs-7">FECHA</th>
                                        <th class="col-xs-5">ACCIÓN</th>
                                      </tr>
                                  </thead>
                                  <tbody style="height: 356px;">
                                      <tr>
                                        <td class="col-xs-8"></td>
                                        <td class="col-xs-4">
                                            <button class="btn btn-info planilla detalle" type="button">
                                                <i class="fa fa-fw fa-print"></i>
                                            </button>
                                          <a data-toggle="popover" data-trigger="hover" data-content="Beneficio no importado">
                                            <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                                          </a>
                                        </td>
                                      </tr>
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          </div>
                      </div> <!-- row -->
                  </div>

                  <div class="col-md-4">
                    <div class="row">
                        <div class="col-lg-12">
                          <div class="panel">
                            <center><img width="100%" src="/img/tarjetas/banner_MEL.jpg"></center>
                          </div>
                        </div>
                    </div>
                      <div class="row">
                          <div class="col-lg-12 col-xl-12">
                            <div class="panel panel-default">
                              <div class="panel-heading">
                                  <h4>Beneficios - Juegos Melincué</h4>
                              </div>
                              <div class="panel-body">
                                <table id="" class="table table-fixed tablesorter">
                                  <thead>
                                      <tr>
                                        <th class="col-xs-7">FECHA</th>
                                        <th class="col-xs-5">ACCIÓN</th>
                                      </tr>
                                  </thead>
                                  <tbody style="height: 356px;">
                                      <tr id="">
                                        <td class="col-xs-8"></td>
                                        <td class="col-xs-4">
                                          <button  class="btn btn-info planilla detalle" type="button">
                                                <i class="fa fa-fw fa-print"></i>
                                          </button>
                                          <a data-toggle="popover" data-trigger="hover" data-content="Beneficio no importado">
                                            <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                                          </a>
                                      </tr>
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          </div>
                      </div> <!-- row -->
                  </div>

                  <div class="col-md-4">
                    <div class="row">
                        <div class="col-lg-12">
                          <div class="panel">
                            <center><img width="100%" src="/img/tarjetas/banner_ROS.jpg"></center>
                          </div>
                        </div>
                    </div>
                      <div class="row">
                          <div class="col-lg-12 col-xl-12">
                            <div class="panel panel-default">
                              <div class="panel-heading">
                                  <h4>Beneficios - Juegos Rosario</h4>
                              </div>
                              <div class="panel-body">
                                <table id="" class="table table-fixed tablesorter">
                                  <thead>
                                      <tr>
                                        <th class="col-xs-4">FECHA</th>
                                        <th class="col-xs-5">MONEDA</th>
                                        <th class="col-xs-3">ACCIÓN</th>
                                      </tr>
                                  </thead>
                                  <tbody style="height: 356px;">
                                      <tr>
                                        <td class="col-xs-5"></td>
                                        <td class="col-xs-5"></td>
                                        <td class="col-xs-2">
                                            <button class="btn btn-info planilla detalle" type="button">
                                                <i class="fa fa-fw fa-print"></i>
                                            </button>
                                          <a data-toggle="popover" data-trigger="hover" data-content="Beneficio no importado">
                                            <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                                          </a>
                                        </td>
                                      </tr>
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          </div>
                      </div> <!-- row -->
                  </div>


            </div>
            <!-- row -->

        <!-- Modal planilla relevamientos -->
        <div class="modal fade" id="modalPlanilla" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog" style="width:80%;">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo">
                     <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
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
                      <input type="hidden" id="id_informe" value="0">
                    </div>
                  </div>
                </div>
              </div>
        </div>


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection
    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionJuegosBeneficios.js" type="text/javascript">
    </script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    @endsection
