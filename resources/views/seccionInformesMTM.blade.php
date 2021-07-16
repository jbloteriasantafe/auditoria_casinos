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
<link rel="stylesheet" href="css/zona-file-large.css">
@endsection
<?php
function moneda($id_tipo_moneda){
  if($id_tipo_moneda == 1) return '$';
  if($id_tipo_moneda == 2) return 'U$S';
  return $id_tipo_moneda;
}
function mes($mes_num){
  $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  if(!array_key_exists($mes_num-1,$meses)) return $mes_num;
  return $meses[$mes_num-1];
}
function anio_mes($anio,$mes){
  return $anio.' '.mes($mes);
}
?>

        <style>
        .imgwrapper {
          width: 80%;
        }
        </style>
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
                                  <h4>Beneficios - MTM Santa fe</h4>
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
                                     @foreach($beneficios_sfe as $b)
                                      <tr>
                                        <td class="col-xs-8">{{anio_mes($b->anio,$b->mes)}}</td>
                                        <td class="col-xs-4">
                                          @if($b->estado == 1)
                                            <button data-anio="{{$b->anio}}" data-mes="{{$b->mes}}" data-casino="{{$b->casino}}" data-moneda="{{$b->id_tipo_moneda}}" class="btn btn-info planilla detalle" type="button">
                                                <i class="fa fa-fw fa-print"></i>
                                            </button>
                                          @endif
                                          @if($b->estado == 0)
                                          <a data-toggle="popover" data-trigger="hover" data-content="Beneficio no importado">
                                            <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                                          </a>
                                          @elseif($b->id_beneficio_mensual)
                                          <a data-toggle="popover" data-trigger="hover" data-content="VALIDADO">
                                            <i class="fa fa-check" style="color: green;"></i>
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
                              <center><img width="100%" src="/img/tarjetas/banner_MEL.jpg"><center>
                          </div>
                        </div>
                    </div>
                      <div class="row">
                          <div class="col-lg-12 col-xl-12">
                            <div class="panel panel-default">
                              <div class="panel-heading">
                                  <h4>Beneficios - MTM Melincué</h4>
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
                                    @foreach($beneficios_mel as $b)
                                      <tr id="">
                                        <td class="col-xs-8">{{anio_mes($b->anio,$b->mes)}}</td>
                                        <td class="col-xs-4">
                                          @if($b->estado == 1)
                                          <button data-anio="{{$b->anio}}" data-mes="{{$b->mes}}" data-casino="{{$b->casino}}" data-moneda="{{$b->id_tipo_moneda}}" class="btn btn-info planilla detalle" type="button">
                                                <i class="fa fa-fw fa-print"></i>
                                          </button>
                                          @endif
                                          @if($b->estado == 0)
                                          <a data-toggle="popover" data-trigger="hover" data-content="Beneficio no importado">
                                            <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                                          </a>
                                          @elseif($b->id_beneficio_mensual)
                                          <a data-toggle="popover" data-trigger="hover" data-content="VALIDADO">
                                            <i class="fa fa-check" style="color: green;"></i>
                                          </a>
                                          @endif
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
                                  <h4>Beneficios - MTM Rosario</h4>
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
                                    @foreach($beneficios_ros as $b)
                                      <tr>
                                        <td class="col-xs-5">{{anio_mes($b->anio,$b->mes)}}</td>
                                        <td class="col-xs-5">{{moneda($b->id_tipo_moneda)}}</td>
                                        <td class="col-xs-2">
                                          @if($b->estado == 1)
                                            <button data-anio="{{$b->anio}}" data-mes="{{$b->mes}}" data-casino="{{$b->casino}}" data-moneda="{{$b->id_tipo_moneda}}" class="btn btn-info planilla detalle" type="button">
                                                <i class="fa fa-fw fa-print"></i>
                                            </button>
                                          @endif
                                          @if($b->estado == 0)
                                          <a data-toggle="popover" data-trigger="hover" data-content="Beneficio no importado">
                                            <i class="fa fa-exclamation" style="color: #FFA726;"></i>
                                          </a>
                                          @elseif($b->id_beneficio_mensual)
                                          <a data-toggle="popover" data-trigger="hover" data-content="VALIDADO">
                                            <i class="fa fa-check" style="color: green;"></i>
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

    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| INFORMES DE TRAGAMONEDAS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Informes de Tragamonedas</h5>
      <p>
        Se presenta un informe final acerca del desempeño mensual de cada casino, teniendo en cuenta puntos como el detalle por día de la cantidad
        de máquinas presentes en cada casino, lo apostado, premios, cantidad de premios totales, el beneficio, su promedio y el % de devolución.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionMTMbeneficios.js" charset="utf-8"></script>
    <script>
      $(document).ready(function(){
          $('[data-toggle="popover"]').popover();
      });
    </script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>

    @endsection
