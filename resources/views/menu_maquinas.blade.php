@extends('includes.nuevaBarraNavegacion')
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$id_usuario = session('id_usuario');
?>

<header>
  <img class="iconoSeccion" src="/img/logos/tragaperras_blue.png" alt="">
  <h2>MÁQUINAS</h2>
</header>

      <div class="row">
          @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,
                                           ['ver_seccion_maquinas','ver_seccion_progresivos','ver_seccion_islas','ver_seccion_formulas',
                                            'ver_seccion_juegos','ver_seccion_glisoft','ver_seccion_glihard','ver_seccion_sectores']))
          <div class="col-md-6">
            <a href="menu_gestionarMTM" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <img class="imagenTarjeta" src="/img/tarjetas/img_GestionMaquinas.jpg" alt="">
                      <h1 class="tituloTarjeta">GESTIONAR MÁQUINAS</h1>
                      <p class="detalleTarjeta">Sección que permite definir y gestionar configuraciones vinculadas a máquinas tragamonedas.</p><br>
                </div>
            </a>
            <!-- <a href="menu_gestionarMTM" style="text-decoration:none;">
                <div class="" align="center" style="overflow:hidden; margin: 0px 8px 15px 8px; height:350px; background-color: #F5F5F5;">
                      <h1 class="" style="color: #555; font-family: Roboto-Condensed; padding: 30px 10px 20px 10px; margin: 0px;">GESTIONAR</h1>
                      <img style="padding-top:20px;" width="95%" class="" src="/img/logos/casinos_blue.png" alt="">
                </div>
            </a> -->
              <!-- <a href="menu_gestionarMTM" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <h1 class="tituloFondo">GESTIONAR</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/tragaperras_orange.png" alt="">
                        <h1 class="tituloSeccion">GESTIONAR MÁQUINAS</h1>
                        <img height="68%" style="top:-570px;" class="imagenSeccion" src="/img/logos/gestion_maquinas.png" alt="">
                  </div>
              </a> -->
            <!-- <a href="menu_gestionarMTM" style="text-decoration:none;">
              <div class="tarjetaSeccion">
                  <div class="imagenSeccion" >
                      <img src="/img/tarjetas/gestionar_maquinas.jpg" alt="">
                  </div>
                  <div class="mascaraSeccion"></div>
                  <div class="fondoSeccion">
                      <h2 class="tituloSeccion">GESTIONAR MÁQUINAS</h2>
                      <img width="160" class="iconoSeccion" src="/img/logos/tragaperras_white.png" alt="">
                  </div>
              </div>
            </a> -->
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,
                                             ['ver_seccion_importaciones','ver_seccion_relevamientos','ver_seccion_mtm_a_pedido',
                                              'ver_seccion_producidos','ver_seccion_beneficios','ver_seccion_estadisticas_relevamientos']))
          <div class="col-md-6">
            <a href="menu_procedimientos" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Procedimientos.jpg" alt="">
                      <h1 class="tituloTarjeta">PROCEDIMIENTOS</h1>
                      <p class="detalleTarjeta">Refiere a la dinámica del funcionamiento, generación de resultados y fiscalización de los procesos que se lleven a cabo.</p>
                </div>
            </a>
              <!-- <a href="menu_procedimientos" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <h1 class="tituloFondo">PROCEDIMIENTOS</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/procedimientos_orange.png" alt="">
                        <h1 class="tituloSeccion">PROCEDIMIENTOS</h1>
                        <img height="60%" style="top:-570px;" class="imagenSeccion" src="/img/logos/procedimientos.png" alt="">
                  </div>
              </a> -->

              <!-- <a href="menu_procedimientos" style="text-decoration:none;">
                  <div class="" align="center" style="overflow:hidden; margin: 0px 8px 15px 8px; height:350px; background-color: #F5F5F5;">
                        <h1 class="" style="color: #555; font-family: Roboto-Condensed; padding: 30px 10px 20px 10px; margin: 0px;">PROCEDIMIENTOS</h1>
                        <img style="padding-top:20px;" width="95%" class="" src="/img/logos/casinos_blue.png" alt="">
                  </div>
              </a> -->
            <!-- <a href="menu_procedimientos" style="text-decoration:none;">
              <div class="tarjetaSeccion">
                  <div class="imagenSeccion" >
                      <img src="/img/tarjetas/progresivos.jpg" alt="">
                  </div>
                  <div class="mascaraSeccion"></div>
                  <div class="fondoSeccion">
                      <h2 class="tituloSeccion">PROCEDIMIENTOS</h2>
                      <img width="160" class="iconoSeccion" src="/img/logos/formulas_white.png" alt="">
                  </div>
              </div>
            </a> -->
          </div>
          @endif


      </div>


@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_maquinas.js" charset="utf-8"></script>
@endsection
