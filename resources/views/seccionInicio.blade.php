@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoInicio">@svg('home','iconoHome')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<link rel='stylesheet' href='/css/fullcalendar.min.css'/>
@endsection
<?php
$usuario = \App\Http\Controllers\UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
?>

@section('contenidoVista')

                <div class="row">
                    <div class="col-md-7">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="panel panel-default">

                          <div class="panel-heading">
                            <h4>BIENVENIDO {{$usuario['usuario']->nombre}}</h4>
                          </div>

                          <div class="panel-body">
                            <div class="row">
                              <div class="col-lg-12">
                                <h5 style="display:inline-block">ROL Y PERMISO </h5>
                                <span style="margin-top:8px; margin-left: 15px;">
                                  - {{implode(' - ',$usuario['usuario']->roles->pluck('descripcion')->toArray())}} -
                                </span>
                                <br>
                                <h5 style="display:inline-block">CASINO(S) ASOCIADO(S) </h5>
                                <span style="margin-top:6px; margin-left: 15px;">
                                  - {{implode(' - ',$usuario['usuario']->casinos->pluck('nombre')->toArray())}} -
                                </span>
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
                                    $date = $week_days[$week_day_now] . ", " . $day_now . " de " . $months[$month_now] . " del " . $year_now;
                                    echo $date;

                                ?></h4>
                            </div>
git 
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
<?php
$iconos_por_ruta = [
  'casinos' => [null,'casinos','iconoCasinosModif'],
  'usuarios' => [null,'usuario','iconoUsuarios'],
  'roles' => [null,'usuario','iconoUsuarios'],
  'configCuenta' => [null,'usuario','iconoUsuarios'],
  'logActividades' => [null,'usuario','iconoUsuarios'],
  'expedientes' => [null,'expedientes','iconoExpedientes'],
  'resoluciones' => [null,'expedientes','iconoExpedientes'],
  'disposiciones' => [null,'expedientes','iconoExpedientes'],
  'informeEstadoParque' => [null,'informes','iconoMaquinas'],
  'estadisticas_relevamientos'  => [null,'informes','iconoMaquinas'],
  'informeContableMTM' => [null,'informes','iconoMaquinas'],
  'informeSector' => [null,'informes','iconoMaquinas'],
  'informeDiarioBasico' => [null,'informes','iconoMaquinas'],
  'relevamientosProgresivo' => [null,'maquinas','iconoMaquinas'],
  'maquinas' => [null,'maquinas','iconoMaquinas'],
  'progresivos' => [null,'maquinas','iconoMaquinas'],
  'islas' => [null,'maquinas','iconoMaquinas'],
  'formulas' => [null,'maquinas','iconoMaquinas'],
  'juegos' => [null,'maquinas','iconoMaquinas'],
  'certificadoSoft' => [null,'maquinas','iconoMaquinas'],
  'certificadoHard' => [null,'maquinas','iconoMaquinas'],
  'sectores' => [null,'maquinas','iconoMaquinas'],
  'importaciones' => [null,'maquinas','iconoMaquinas'],
  'relevamientos' => [null,'maquinas','iconoMaquinas'],
  'mtm_a_pedido' => [null,'maquinas','iconoMaquinas'],
  'producidos' => [null,'maquinas','iconoMaquinas'],
  'beneficios' => [null,'maquinas','iconoMaquinas'],
  'layout_total' => [null,'maquinas','iconoMaquinas'],
  'layout_parcial' => [null,'maquinas','iconoMaquinas'],
  'prueba_juego' => [null,'maquinas','iconoMaquinas'],
  'prueba_progresivos' => [null,'maquinas','iconoMaquinas'],
  'relevamientos_movimientos' => [null,'maquinas','iconoMaquinas'],
  'eventualidades' => [null,'maquinas','iconoMaquinas'],
  'eventualidadesMTM' => [null,'maquinas','iconoMaquinas'],
  'estadisticasGenerales' => [null,'tablero_modif','iconoTableroModif'],
  'estadisticasPorCasino' => [null,'tablero_modif','iconoTableroModif'],
  'interanuales' => [null,'tablero_control','iconoTableroModif'],
  'informesMTM' => [null,'informes','iconoInformesModif'],
  'bingo' => [null,'bingos','iconoInformesModif'],
  'diferencia-bingo' => ['bingo/reportesDiferencia','bingos','iconoInformesModif'],
  'estado-bingo' => ['bingo/reportesEstado','bingos','iconoInformesModif'],
  'importacion-bingo' => ['bingo/importarRelevamiento','bingos','iconoInformesModif'],
  'informe-bingo' => ['bingo/informe','bingos','iconoInformesModif'],
  'relevamientosControlAmbiental' => [null,'maquinas','iconoMaquinas'],
  'autoexclusion' => [null,'usuario','iconoUsuarios'],
  'galeriaAE' => ['galeriaAE','usuario','iconoUsuarios'],
  'informesAutoexcluidos' => [null,'informes','iconoInformesModif'],
];
?>
                          <div class="panel-body">
                              <div class="row">
                                @foreach($ultimas_visitadas as $visitada)
                                  <div class="col-md-3 seccionVisitada">
                                    <?php 
                                      $val = $iconos_por_ruta[$visitada->ruta] ?? [$visitada->ruta,null,null];
                                    ?>
                                    <a href="{{$val[0] ?? $visitada->ruta}}" style="color: rgb(68,68,68);">
                                      <i class="fa fa-share fa-2x"></i>
                                      @if($val[1] || $val[2])
                                      @svg($val[1],$val[2])
                                      @endif
                                      <h6>{{$visitada->seccion}}</h6>
                                    </a>
                                  </div>
                                @endforeach
                              </div>
                          </div>
                      </div> <!-- panel -->
                    </div>
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

            @if ($usuario['usuario']->id_usuario == 00)

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

    @if ($usuario['usuario']->id_usuario == 5)
    <script type="text/javascript">
        $('#modal_javi').modal('show');
        console.log('Anda');
    </script>
    @endif
@endsection
