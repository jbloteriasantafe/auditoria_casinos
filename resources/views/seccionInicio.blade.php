@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoInicio">@svg('home','iconoHome')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<link rel='stylesheet' href='/css/fullcalendar.min.css'/>
@endsection
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
$id_usuario = $usuario['usuario']->id_usuario;
?>

@section('contenidoVista')

                <div class="row">
                    <div class="col-md-7">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="panel panel-default">

                          <div class="panel-heading">
                            <h4>BIENVENIDO <?php echo $usuario['usuario']->nombre; ?></h4>
                          </div>

                          <div class="panel-body">
                            <div class="row">
                              <div class="col-lg-12">
                                <h5 style="display:inline-block">ROL Y PERMISO </h5><span style="margin-top:8px; margin-left: 15px;">
                                  <?php echo ' - ';
                                  foreach($usuario['usuario']->roles as $roles){
                                    echo $roles->descripcion.' - ';
                                    } ?></span>
                                <br>
                                <h5 style="display:inline-block">CASINO(S) ASOCIADO(S) </h5><span style="margin-top:6px; margin-left: 15px;">
                                  <?php echo ' - ';
                                  foreach($usuario['usuario']->casinos as $casinos){
                                    echo $casinos->nombre.' - ' ;
                                    } ?> </span>

                                  <!-- <input id="b_adminMaquina" type="text" class="form-control" value="" placeholder="Nro. admin"> -->
                                <br><br><br>
                              </div>
                            </div>
                          </div> <!-- panel-body -->

                      </div> <!-- panel -->
                    </div>

                      <div class="col-md-6">
                        <div class="panel panel-default">

                            <div class="panel-heading">
                              <h4><?php

                                    $week_days = array ("Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado");
                                    $months = array ("", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
                                    $year_now = date ("Y");
                                    $month_now = date ("n");
                                    $day_now = date ("j");
                                    $week_day_now = date ("w");
                                    $date = $week_days[$week_day_now] . ", " . $day_now . " de " . $months[$month_now] . " de " . $year_now;
                                    echo $date;

                                ?></h4>
                            </div>

                            <div class="panel-body">

                              <div class="row">
                                <div class="col-lg-12">

                                  <span style="margin-top:6px;"><div id="cont_1b69ec28a0054c43833bebaf9168811f"><script type="text/javascript" async src="https://www.meteored.com.ar/wid_loader/1b69ec28a0054c43833bebaf9168811f"></script></div></span>
                                </div>
                              </div>
                            </div> <!-- panel-body -->

                        </div> <!-- panel -->
                      </div>

                    <div class="col-md-12">
                      <div class="panel panel-default">

                          <div class="panel-heading">
                            <h4>ÚLTIMAS SECCIONES VISITADAS</h4>
                          </div>

                          <style media="screen">
                              .seccionVisitada {
                                  text-align: center;
                                  height: 200px;
                              }
                              .seccionVisitada a {
                                  text-decoration: none;
                              }
                              .seccionVisitada:hover i {
                                  opacity: 1;
                              }
                              .seccionVisitada:hover .icon {
                                  transform: scale(1.3);
                                  top: 0px;
                              }
                              .seccionVisitada i {
                                  color: #aaa;
                                  display: block;
                                  opacity: 0;
                                  /*transition: opacity 100ms;*/
                              }

                              .seccionVisitada .icon {
                                  stroke: #aaa;
                                  position: relative;
                                  top: -15px;
                              }

                              .seccionVisitada h6 {
                                font-family: Roboto-Condensed;
                                font-size: 18px;
                              }
                              /* ICONOS */
                              .seccionVisitada .iconoMaquinas {
                                width: 80px; height: 80px;
                              }
                              .seccionVisitada .iconoUsuarios {
                                width: 52px; height: 52px;
                                margin: 14px 0px 14px 0px;
                              }
                              .seccionVisitada .iconoExpedientes {
                                width: 62px; height: 62px;
                                margin: 9px 0px 9px 0px;
                              }
                          </style>

                          <div class="panel-body">
                              <div class="row">
                                @foreach($ultimas_visitadas as $visitada)
                                  <div class="col-md-3 seccionVisitada">
                                          @if($visitada->ruta == 'casinos')
                                            <a href="casinos"><i class="fa fa-share fa-2x"></i>
                                            @svg('casinos','iconoCasinosModif')</a>
                                          @elseif($visitada->ruta == 'usuarios')
                                            <a href="usuarios"><i class="fa fa-share fa-2x"></i>@svg('usuario','iconoUsuarios')</a>
                                          @elseif($visitada->ruta == 'roles')
                                            <a href="usuarios"><i class="fa fa-share fa-2x"></i>@svg('usuario','iconoUsuarios')</a>
                                          @elseif($visitada->ruta == 'configCuenta')
                                          <a href="usuarios"><i class="fa fa-share fa-2x"></i>@svg('usuario','iconoUsuarios')</a>
                                          @elseif($visitada->ruta == 'logActividades')
                                            <a href="usuarios"><i class="fa fa-share fa-2x"></i>@svg('usuario','iconoUsuarios')</a>
                                          @elseif($visitada->ruta == 'expedientes')
                                            <a href="expedientes"><i class="fa fa-share fa-2x"></i>@svg('expedientes','iconoExpedientes')</a>
                                          @elseif($visitada->ruta == 'resoluciones')
                                            <a href="expedientes"><i class="fa fa-share fa-2x"></i>@svg('expedientes','iconoExpedientes')</a>
                                          @elseif($visitada->ruta == 'disposiciones')
                                            <a href="expedientes"><i class="fa fa-share fa-2x"></i>@svg('expedientes','iconoExpedientes')</a>
                                          @elseif($visitada->ruta == 'informeEstadoParque')
                                            <a href="informeEstadoParque"><i class="fa fa-share fa-2x"></i>@svg('informes','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'estadisticas_relevamientos')
                                            <a href="estadisticas_relevamientos"><i class="fa fa-share fa-2x"></i>@svg('informes','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'informeContableMTM')
                                            <a href="informeContableMTM"><i class="fa fa-share fa-2x"></i>@svg('informes','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'informeSector')
                                            <a href="informeSector"><i class="fa fa-share fa-2x"></i>@svg('informes','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'relevamientosProgresivo')
                                            <a href="relevamientosProgresivo"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'maquinas')
                                            <a href="maquinas"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'progresivos')
                                            <a href="progresivos"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'islas')
                                            <a href="islas"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'formulas')
                                            <a href="formulas"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'juegos')
                                            <a href="juegos"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'certificadoSoft')
                                            <a href="certificadoSoft"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'certificadoHard')
                                            <a href="certificadoHard"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'sectores')
                                            <a href="sectores"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'importaciones')
                                            <a href="importaciones"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'relevamientos')
                                            <a href="relevamientos"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'mtm_a_pedido')
                                            <a href="mtm_a_pedido"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'producidos')
                                            <a href="producidos"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'beneficios')
                                            <a href="beneficios"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'layout_total')
                                            <a href="layout_total"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'layout_parcial')
                                            <a href="layout_parcial"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'prueba_juego')
                                            <a href="prueba_juegos"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'prueba_progresivos')
                                            <a href="prueba_progresivos"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'movimientos')
                                            <a href="movimientos"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'relevamientos_movimientos')
                                            <a href="relevamientos_movimientos"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'eventualidades')
                                            <a href="eventualidades"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'eventualidadesMTM')
                                            <a href="eventualidadesMTM"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'estadisticasGenerales')
                                            <a href="estadisticasGenerales"><i class="fa fa-share fa-2x"></i>@svg('tablero_modif','iconoTableroModif')</a>
                                          @elseif($visitada->ruta == 'estadisticasPorCasino')
                                            <a href="estadisticasPorCasino"><i class="fa fa-share fa-2x"></i>@svg('tablero_modif','iconoTableroModif')</a>
                                          @elseif($visitada->ruta == 'interanuales')
                                            <a href="interanuales"><i class="fa fa-share fa-2x"></i>@svg('tablero_control','iconoTableroModif')</a>
                                          @elseif($visitada->ruta == 'informesMTM')
                                            <a href="informesMTM"><i class="fa fa-share fa-2x"></i>@svg('informes','iconoInformesModif')</a>
                                          @elseif($visitada->ruta == 'bingo')
                                            <a href="bingo"><i class="fa fa-share fa-2x"></i>@svg('bingos','iconoInformesModif')</a>
                                          @elseif($visitada->ruta == 'diferencia-bingo')
                                            <a href="bingo/reportesDiferencia"><i class="fa fa-share fa-2x"></i>@svg('bingos','iconoInformesModif')</a>
                                          @elseif($visitada->ruta == 'estado-bingo')
                                            <a href="bingo/reportesEstado"><i class="fa fa-share fa-2x"></i>@svg('bingos','iconoInformesModif')</a>
                                          @elseif($visitada->ruta == 'importacion-bingo')
                                            <a href="bingo/importarRelevamiento"><i class="fa fa-share fa-2x"></i>@svg('bingos','iconoInformesModif')</a>
                                          @elseif($visitada->ruta == 'informe-bingo')
                                            <a href="bingo/informe"><i class="fa fa-share fa-2x"></i>@svg('bingos','iconoInformesModif')</a>  
                                          @elseif($visitada->ruta == 'relevamientosControlAmbiental')
                                            <a href="relevamientosControlAmbiental"><i class="fa fa-share fa-2x"></i>@svg('maquinas','iconoMaquinas')</a>
                                          @elseif($visitada->ruta == 'autoexclusion')
                                            <a href="autoexclusion"><i class="fa fa-share fa-2x"></i>@svg('usuario','iconoUsuarios')</a>
                                          @elseif($visitada->ruta == 'galeriaAE')
                                            <a href="galeriaImagenesAutoexcluidos"><i class="fa fa-share fa-2x"></i>@svg('usuario','iconoUsuarios')</a>
                                          @elseif($visitada->ruta == 'informesAutoexcluidos')
                                            <a href="informesAutoexcluidos"><i class="fa fa-share fa-2x"></i>@svg('informes','iconoInformesModif')</a>
                                          @endif
                                          <h6>{{$visitada->seccion}}</h6>
                                  </div>
                                @endforeach
                              </div>
                          </div>
                      </div> <!-- panel -->
                    </div>

                    <!-- <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                              <h4>ÚLTIMAS SECCIONES VISITADAS</h4>
                            </div>
                            <div class="panel-body">

                              <style media="screen">
                                  .visitada {
                                    min-height: 200px;
                                    margin: 5px 0px 20px 0px;
                                    background-color: #FFAB91;
                                  }
                                  .visitada a .icon {
                                    fill: #aaa;
                                  }
                                  .visitada h6 {

                                  }
                              </style>
                              <div class="row">
                                  <div class="col-md-3">
                                      <div class="visitada">
                                          <a href="islas"></a>
                                          <h6>MÁQUINAS</h6>
                                      </div>
                                  </div>
                                  <div class="col-md-3">
                                      <div class="visitada">

                                      </div>
                                  </div>
                                  <div class="col-md-3">
                                      <div class="visitada">

                                      </div>
                                  </div>
                                  <div class="col-md-3">
                                      <div class="visitada">

                                      </div>
                                  </div>
                              </div>
                            </div>
                        </div>
                    </div> -->

                </div>
              </div>
              <div class="col-md-5">
                <div class="panel panel-default">

                    <div class="panel-heading">
                      <h4>CALENDARIO</h4>
                    </div>

                    <div class="panel-body">
                      <div class="row">
                        <div class="col-lg-12">
                          <div id="calendarioInicio"></div>
                        </div>
                      </div>

                    </div> <!-- panel-body -->

                </div> <!-- panel -->
              </div>
            </div>

            @if ($id_usuario == 00)

            <div id="modal_javi" class="modal fade" role="dialog">
              <div class="modal-dialog modal-lg">

                <!-- Modal content-->
                <div class="modal-content">
                  <div class="modal-body" style="text-align:center;">
                    <img src="/img/alvaro_rivera.png" alt="" style="display:inline; margin-top: 20px; margin-bottom:20px;">
                  </div>
                </div>

              </div>
            </div>

            @endif


            <meta name="_token" content="{!! csrf_token() !!}" />
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA INICIO</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjetas de Inicio</h5>
  <p>En esta sección se podrá ver una ayuda rápida al calendario con actividades previstas y cargadas en el sistema, incluyendo feriados y días no hábiles.
  Además de la situación actual del clima en Santa Fe y los últimos accesos a los cuales cada usuario visitó por última vez.</p>
</div>

@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
    <!-- token -->
    <script src='/js/moment.min.js'></script>
    <script src='/js/fullcalendar.min.js'></script>
    <script src='/js/locale-all.js'></script>
    <script src="/js/gcal.min.js" charset="utf-8"></script>

    <script src="js/seccionInicio.js"></script>

    @if ($id_usuario == 5)
    <script type="text/javascript">
        $('#modal_javi').modal('show');
        console.log('Anda');
    </script>
    @endif
@endsection
