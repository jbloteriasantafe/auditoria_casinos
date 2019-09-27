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
              <h1><img width="80" src="/img/logos/informes_bingo_blue.png" alt=""> INFORMES BINGOS</h1>
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
                                  <h4>Beneficios - Bingo Santa fe</h4>
                              </div>
                              <div class="panel-body">
                                <table id="" class="table table-fixed tablesorter">
                                  <thead>
                                      <tr>
                                        <th class="col-xs-7">FECHA</th>
                                        <th class="col-xs-5 text-right">ACCIÓN</th>
                                      </tr>
                                  </thead>
                                  <tbody style="height: 356px;">
                                    @foreach($informe_sfe as $informe)
                                      <tr>
                                        <td class="col-xs-7">{{ $informe->fecha_informe}}</td>
                                        <td class="col-xs-5 text-right">

                                            <button data-fecha="{{$informe->fecha_completa}}"  data-casino="{{$informe->id_casino}}" class="btn btn-info planilla detalle" type="button">
                                                <i class="fa fa-fw fa-print"></i>
                                            </button>

                                          @if($informe->estado == 0)
                                          <a data-toggle="popover" data-trigger="hover" data-content="Hay sesiones sin validar">
                                            <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                                          </a>
                                          @endif
                                        </td>
                                      </tr>
                                      @endforeach
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
                                  <h4>Beneficios - Bingo Melincué</h4>
                              </div>
                              <div class="panel-body">
                                <table id="" class="table table-fixed tablesorter">
                                  <thead>
                                      <tr>
                                        <th class="col-xs-7">FECHA</th>
                                        <th class="col-xs-5 text-right">ACCIÓN</th>
                                      </tr>
                                  </thead>
                                  <tbody style="height: 356px;">
                                    @foreach($informe_mel as $informe)
                                      <tr>
                                        <td class="col-xs-7">{{ $informe->fecha_informe}}</td>
                                        <td class="col-xs-5 text-right">

                                            <button data-fecha="{{$informe->fecha_completa}}"  data-casino="{{$informe->id_casino}}" class="btn btn-info planilla detalle" type="button">
                                                <i class="fa fa-fw fa-print"></i>
                                            </button>

                                          @if($informe->estado == 0)
                                          <a data-toggle="popover" data-trigger="hover" data-content="Hay sesiones sin validar">
                                            <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                                          </a>
                                          @endif
                                        </td>
                                      </tr>
                                      @endforeach
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
                                  <h4>Beneficios - Bingo Rosario</h4>
                              </div>
                              <div class="panel-body">
                                <table id="" class="table table-fixed tablesorter">
                                  <thead>
                                      <tr>
                                        <th class="col-xs-7">FECHA</th>
                                        <th class="col-xs-5 text-right">ACCIÓN</th>
                                      </tr>
                                  </thead>
                                  <tbody style="height: 356px;">
                                    @foreach($informe_ros as $informe)
                                      <tr>
                                        <td class="col-xs-7">{{ $informe->fecha_informe}}</td>
                                        <td class="col-xs-5 text-right">

                                            <button data-fecha="{{$informe->fecha_completa}}"  data-casino="{{$informe->id_casino}}" class="btn btn-info planilla detalle" type="button">
                                                <i class="fa fa-fw fa-print"></i>
                                            </button>

                                            @if($informe->estado == 0)
                                            <a data-toggle="popover" data-trigger="hover" data-content="Hay sesiones sin validar">
                                              <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                                            </a>
                                            @endif
                                        </td>
                                      </tr>
                                      @endforeach
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



          <!-- Modal PREGUNTA OBSERVACIONES -->
          <div class="modal fade" id="modalPregunta" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">

              <div class="modal-dialog">
                 <div class="modal-content">

                   <div class="modal-header" style="background: #d9534f; color: #E53935;">
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                     <h3 class="modal-title-pregunta" style="color:#000000;"></h3>
                   </div>

                        <div class="modal-body" style="color:#fff; background-color:#FFFFF;">
                    <form id="frmObservaciones">

                            <h6 style="color:#000000 !important; font-size:14px;"></h6>
                            <br>
                            <h6 id="mensajePregunta" style="color:#000000"></h6>
                            <div id="campo-valor">
                            </div>
                    </form>
                          </div>
                  <br>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" id="btn-con-observacion">INFORME CON OBSERVACIÓN</button>
                    <button type="button" class="btn btn-default" id="btn-sin-observacion" data-dismiss="modal">INFORME SIN OBSERVACIÓN</button>
                    <button type="button" class="btn btn-default" id="btn-generar-con-observacion" data-dismiss="modal">GENERAR</button>
                  </div>
              </div>
            </div>
</div>

    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection
    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="../js/Bingo/informe.js" type="text/javascript">

    </script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    @endsection
