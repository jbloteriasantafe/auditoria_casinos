@extends('includes.nuevaBarraNavegacion')
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$id_usuario = session('id_usuario');
?>
                      <style media="screen">

                            .contenedorTarea {
                              margin: 10px 0px;
                              position: relative;
                              /*background-color: yellow;*/
                            }

                            /* CIRCULO */
                            .circuloTarea {
                              background-color: #999;
                              width: 38px;
                              height: 38px;
                              border-radius: 50%;
                            }
                            /* ICONO */
                            .checkTarea {
                              transform: scale(1.4);
                              color: white;
                              position: relative;
                              top: 8px;
                            }
                            /* Si no está completada se oculta el check */
                            .checkTarea.fa-times {
                              display: inline;
                            }
                            .checkTarea.fa-check {
                              display: none;
                            }
                            .infoTarea {
                              font-family: Roboto-BoldCondensed;
                              font-size: 16px;
                              color: white;
                              /*z-index: 300;*/
                              position: relative;
                              top: 8px;
                              left: 0px;
                            }


                            /* FECHA */
                            .circuloFecha {
                              background-color: #999;
                              max-width: 120px;
                              min-width: 105px;
                              height: 38px;
                              border-radius: 20px;
                              z-index: 200 !important;
                            }

                            .contenedorTarea .calendarTarea {
                              /*transform: scale(1.4);*/
                              color: white;
                              position: relative;
                              top: 5px;
                            }
                            .fechaTarea {
                              font-family: Roboto-BoldCondensed;
                              font-size: 16px;
                              color: white;
                              /*z-index: 300;*/
                              position: relative;
                              top: 7px;
                              margin-left: 6px;
                            }


                            /* LINEAS IZQUIERDAS Y DERECHAS */
                            .contenedorTarea .izq-lineaTarea {
                              position: absolute;
                              top:18px;
                              left: 0px;
                              height: 3px;
                              width: 50%;
                              background-color: #999;
                              margin: 0px -15px;
                            }
                            .contenedorTarea .der-lineaTarea {
                              position: absolute;
                              top:18px;
                              right: 0px;
                              height: 3px;
                              width: 50%;
                              float: right;
                              background-color: #999;
                              margin: 0px -15px;
                            }



                            /* TAREA PARCIAL */
                            .contenedorTarea.parcial .izq-lineaTarea,
                            .contenedorTarea.parcial .der-lineaTarea,
                            .contenedorTarea.parcial .circuloTarea,
                            .contenedorTarea.parcial .circuloFecha {
                              background-color: #FF6E40;
                            }



                            /* TAREA COMPLETADA */
                            .contenedorTarea.completada .izq-lineaTarea,
                            .contenedorTarea.completada .der-lineaTarea,
                            .contenedorTarea.completada .circuloTarea,
                            .contenedorTarea.completada .circuloFecha {
                              background-color: #8BC34A;
                            }
                            /* Si está completada se oculta el times */
                            .completada .checkTarea.fa-check {
                              display: inline;
                            }
                            .completada .checkTarea.fa-times {
                              display: none;
                            }


                      </style>

<header>
  <img class="iconoSeccion" src="/img/logos/contadores_blue.png" alt="">
  <h2>CONTADORES</h2>
</header>
      <div class="row">
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_relevamientos'))
          <div class="col-xl-6 col-md-6">
              <a href="relevamientos" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">RELEVAMIENTOS</h2>
                    <h2 class="tituloSeccionMenor">RELEVAMIENTOS</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/relevamientos_white.png" alt="">
                  </div>
              </a>
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_importaciones'))
          <div class="col-xl-6 col-md-6">
              <a href="importaciones" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">IMPORTACIONES</h2>
                    <h2 class="tituloSeccionMenor">IMPORTACIONES</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/importaciones_white.png" alt="">
                  </div>
              </a>
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_mtm_a_pedido'))
          <div class="col-xl-3 col-md-6">
              <a href="mtm_a_pedido" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">MTMAPEDIDO</h2>
                    <h2 class="tituloSeccionMenor">MTM A PEDIDO</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/maquinas_a_pedido_white.png" alt="">
                  </div>
              </a>
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_producidos'))
          <div class="col-xl-3 col-md-6">
              <a href="producidos" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">PRODUCIDOS</h2>
                    <h2 class="tituloSeccionMenor">PRODUCIDOS</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/producidos_white.png" alt="">
                  </div>
              </a>
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_beneficios'))
          <div class="col-xl-3 col-md-6">
              <a href="beneficios" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">BENEFICIOS</h2>
                    <h2 class="tituloSeccionMenor">BENEFICIOS</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/beneficios_white.png" alt="">
                  </div>
              </a>
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_estadisticas_relevamientos'))
          <div class="col-xl-3 col-md-6">
              <a href="estadisticas_relevamientos" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">ESTADÍSTICAS</h2>
                    <h2 class="tituloSeccionMenor">ESTADÍSTICAS</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/informes_white.png" alt="">
                  </div>
              </a>
          </div>
          @endif
      </div>

      @foreach($ajustes_finales as $ajuste_casino)
      <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>CASINO {{$ajuste_casino['nombre']}}</h4>
                </div>
                <div class="panel-body">
                      <!-- Lista de tareas -->
                      <div class="row" style="text-align:center;">
                          <div class="col-xs-3">
                              <h5>FECHA</h5>
                          </div>
                          <div class="col-xs-3">
                              <h5>CONTADORES</h5>
                          </div>
                          <div class="col-xs-3">
                              <h5>RELEVAMIENTOS</h5>
                          </div>
                          <div class="col-xs-3">
                              <h5>VALIDADO</h5>
                          </div>
                      </div>
                     @foreach($ajuste_casino['detalles'] as $ajuste)
                      <!-- Tarea -->
                      <div class="row tareaContador">
                          <div class="contenedorTarea completada col-xs-3">
                            <center>
                                <div class="graficoTarea">
                                    <!-- <div class="izq-lineaTarea"></div> -->
                                    <div class="der-lineaTarea"></div>
                                    <div class="circuloFecha">
                                      <i class="calendarTarea fa fa-calendar"></i>
                                      <span class="fechaTarea">{{$ajuste->fecha}}</span>
                                    </div>
                                </div>
                            </center>
                          </div>
                          @if($ajuste->importado)
                          <div class="contenedorTarea completada col-xs-3">
                          @else
                          <div class="contenedorTarea col-xs-3">
                          @endif
                            <center>
                                <div class="graficoTarea">
                                    <div class="izq-lineaTarea"></div>
                                    <div class="der-lineaTarea"></div>
                                    <div class="circuloTarea">
                                      <i class="checkTarea fa fa-times"></i>
                                      <i class="checkTarea fa fa-check"></i>
                                    </div>
                                </div>
                            </center>
                          </div>
                          @if($ajuste->cantidad_sectores_relevados == $ajuste->cantidad_sectores)
                            <div class="contenedorTarea completada col-xs-3">
                          @endif
                          @if($ajuste->cantidad_sectores_relevados > 0 && $ajuste->cantidad_sectores_relevados < $ajuste->cantidad_sectores)
                          <div class="contenedorTarea parcial col-xs-3">
                          @endif
                          @if($ajuste->cantidad_sectores_relevados == 0)
                          <div class="contenedorTarea col-xs-3">
                          @endif
                            <center>
                                <div class="graficoTarea"  >
                                    <div class="izq-lineaTarea"></div>
                                    <div class="der-lineaTarea"></div>
                                    <div class="circuloFecha" data-content='Se han finalizado {{$ajuste->cantidad_sectores_relevados}} relevamientos de {{$ajuste->cantidad_sectores}}.' data-trigger="hover" data-toggle="popover" data-placement="top">
                                      <span class="infoTarea">{{$ajuste->cantidad_sectores_relevados}}/{{$ajuste->cantidad_sectores}}</span>
                                    </div>
                                </div>
                            </center>
                          </div>
                          @if($ajuste->cantidad_sectores_validados == $ajuste->cantidad_sectores && $ajuste->finalizado)
                          <div class="contenedorTarea completada col-xs-3">
                          @else
                          <div class="contenedorTarea  col-xs-3">
                          @endif
                            <center>
                              <div class="graficoTarea"  data-content='Se han validado {{$ajuste->cantidad_sectores_validados}} relevamientos de {{$ajuste->cantidad_sectores}}.' data-trigger="hover" data-toggle="popover" data-placement="top">
                                  <div class="izq-lineaTarea"></div>
                                  <div class="circuloFecha">
                                    <span class="infoTarea">{{$ajuste->cantidad_sectores_validados}}/{{$ajuste->cantidad_sectores}}</span>
                                  </div>
                              </div>
                            </center>
                          </div>
                      </div>
                      <br>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
            @endforeach



@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_contadores.js" charset="utf-8"></script>
@endsection
