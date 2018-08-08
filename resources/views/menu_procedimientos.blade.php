@extends('includes.nuevaBarraNavegacion')
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$id_usuario = session('id_usuario');
?>

<header>
  <img class="iconoSeccion" src="/img/logos/procedimientos_blue.png" alt="">
  <h2>PROCEDIMIENTOS</h2>
</header>

      @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,
                                   ['ver_seccion_importaciones','ver_seccion_relevamientos','ver_seccion_mtm_a_pedido',
                                    'ver_seccion_producidos','ver_seccion_beneficios','ver_seccion_estadisticas_relevamientos']))
      <div class="row">
          <div class="col-md-4">
            <a href="menu_contadores" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <img class="imagenTarjeta" src="/img/tarjetas/img_CoinIn.jpg" alt="">
                      <h1 class="tituloTarjeta">CONTADORES</h1>
                      <p class="detalleTarjeta">Otorga configuraciones que cumplen con el objetivo de fiscalizar las distintas MTM presentes en cada casino.</p>
                </div>
            </a>
          </div>
              <!-- <a href="menu_contadores" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <!- Fondo de sección ->
                        <h1 class="tituloFondo">CONTADORES</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/contadores_orange.png" alt="">
                        <!- Los que se muestran ->
                        <h1 class="tituloSeccion">CONTADORES</h1>
                        <img height="68%" style="top:-580px;" class="imagenSeccion" src="/img/logos/contadores.png" alt="">
                  </div>
              </a> -->


          <div class="col-md-4">
            <a href="menu_layout" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Layout.jpg" alt="">
                      <h1 class="tituloTarjeta">LAYOUT</h1>
                      <p class="detalleTarjeta">Determina distintas estrategias donde el relevamiento de MTM puede ser en un sector parcial o total de un casino.</p>
                </div>
            </a>
              <!-- <a href="menu_layout" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <!- Fondo de sección ->
                        <h1 class="tituloFondo">LAYOUT</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/contadores_orange.png" alt="">
                        <!- Los que se muestran ->
                        <h1 class="tituloSeccion">LAYOUT</h1>
                        <img height="68%" style="top:-580px;" class="imagenSeccion" src="/img/logos/sectores_white.png" alt="">
                  </div>
              </a> -->
          </div>
          <div class="col-md-4">
            <a href="relevamientos_progresivos" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Progresivos_Relevamientos.jpg" alt="">
                      <h1 class="tituloTarjeta">RELEVAMIENTO DE PROGRESIVOS</h1>
                      <p class="detalleTarjeta">Se realizan fiscalizaciones de los distintos tipos de progresivos existentes.</p>
                </div>
            </a>
              <!-- <a href="menu_layout" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <!- Fondo de sección ->
                        <h1 class="tituloFondo">LAYOUT</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/contadores_orange.png" alt="">
                        <!- Los que se muestran ->
                        <h1 class="tituloSeccion">LAYOUT</h1>
                        <img height="68%" style="top:-580px;" class="imagenSeccion" src="/img/logos/sectores_white.png" alt="">
                  </div>
              </a> -->
          </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <a href="#" class="tarjetaMenu">
              <div class="contenedorTarjeta" align="center">
                    <img class="imagenTarjeta" src="/img/tarjetas/img_Movimientos.jpg" alt="">
                    <h1 class="tituloTarjeta">MOVIMIENTOS</h1>
                    <p class="detalleTarjeta">Detalle de movimientos dentro y fuera del sistema, que controla configuraciones y/o estados emergentes que se presenten.</p>
              </div>
          </a>
        </div>
        <div class="col-md-6">
          <a href="#" class="tarjetaMenu">
              <div class="contenedorTarjeta" align="center">
                    <img class="imagenTarjeta" src="/img/tarjetas/img_Pagos_Manuales.jpg" alt="">
                    <h1 class="tituloTarjeta">PAGOS MANUALES</h1>
                    <p class="detalleTarjeta">Expone aquellos pagos que superaron el límite de pago automático por máquina.</p>
              </div>
          </a>
        </div>
      </div>
      @endif


@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_procedimientos.js" charset="utf-8"></script>
@endsection
