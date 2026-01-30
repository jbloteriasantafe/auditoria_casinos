@extends('includes.nuevaBarraNavegacion')
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$id_usuario = session('id_usuario');
?>

<header>
  <img class="iconoSeccion" src="/img/logos/expedientes_blue.png" alt="">
  <h2>EXPEDIENTES</h2>
</header>

<!-- <div id="page-wrapper">

  <div class="container-fluid"> -->
      <div class="row">
          <div class="col-md-4">
            <a href="/notas-unificadas" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Expedientes.jpg" alt="">
                      <h1 class="tituloTarjeta">NOTAS UNIFICADAS</h1>
                      <p class="detalleTarjeta">Gestión unificada de Notas, Fiscalización y Marketing.</p>
                </div>
            </a>
          </div>

         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_expedientes'))
          <div class="col-md-4">
            <a href="expedientes" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Expedientes.jpg" alt="">
                      <h1 class="tituloTarjeta">EXPEDIENTES</h1>
                      <p class="detalleTarjeta">Permite crear, modificar y eliminar expedientes en el sistema.</p><br>
                </div>
            </a>
            <!-- <a href="expedientes" style="text-decoration:none;">
                <div class="tarjetaSeccion" align="center">
                      <!- Fondo de sección ->
                      <h1 class="tituloFondo">EXPEDIENTES</h1>
                      <img class="imagenFondo" height="130%" src="/img/logos/expedientes_orange.png" alt="">
                      <!- Los que se muestran ->
                      <h1 class="tituloSeccion">GESTIÓN EXPEDIENTES</h1>
                      <img height="70%" style="top:-580px;" class="imagenSeccion" src="/img/logos/gestion_expedientes.png" alt="">
                </div>
            </a> -->
            <!-- <a href="expedientes" style="text-decoration:none;">
                <div class="tarjetaSeccionMenor" align="center">
                  <h2 class="tituloFondoMenor">EXPEDIENTES</h2>
                  <h2 class="tituloSeccionMenor">GESTIÓN EXPEDIENTES</h2>
                  <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/expedientes_white.png" alt="">
                </div>
            </a> -->
            <!-- <a href="expedientes">
              <div id="tarjetaGestionExpedientes" class="tarjeta">
                <div class="mascara"><div class="cuadro"></div></div>
                <h2 class="titulo">GESTIÓN EXPEDIENTES</h2>
                <p class="descripcion">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqa.</p>
              </div>
            </a> -->
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_resoluciones'))
          <div class="col-md-4">
            <a href="disposiciones" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Disposiciones.jpg" alt="">
                      <h1 class="tituloTarjeta">DISPOSICIONES</h1>
                      <p class="detalleTarjeta">Permite garantizar los distintos movimientos y/o actividades de los concesionarios a fiscalizar</p>
                </div>
            </a>
            <!-- <a href="resoluciones" style="text-decoration:none;">
                <div class="tarjetaSeccion" align="center">
                      <!- Fondo de sección ->
                      <h1 class="tituloFondo">RESOLUCIONES</h1>
                      <img class="imagenFondo" height="130%" src="/img/logos/resoluciones_orange.png" alt="">
                      <!- Los que se muestran ->
                      <h1 class="tituloSeccion">RESOLUCIONES</h1>
                      <img height="62%" style="top:-580px;" class="imagenSeccion" src="/img/logos/resoluciones_white.png" alt="">
                </div>
            </a> -->
            <!-- <a href="resoluciones">
              <div id="tarjetaResoluciones" class="tarjeta">
                <div class="mascara"><div class="cuadro"></div></div>
                <h2 class="titulo">RESOLUCIONES</h2>
                <p class="descripcion">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqa.</p>
              </div>
            </a> -->
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_disposiciones'))
          <div class="col-md-4">
            <a href="resoluciones" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Resoluciones.jpg" alt="">
                      <h1 class="tituloTarjeta">RESOLUCIONES</h1>
                      <p class="detalleTarjeta">Instrumentos legales de rango superior que avalan pedidos realizados por los concesionarios.</p>
                </div>
            </a>
            <!-- <a href="disposiciones" style="text-decoration:none;">
                <div class="tarjetaSeccion" align="center">
                      <!- Fondo de sección ->
                      <h1 class="tituloFondo">DISPOSICIONES</h1>
                      <img class="imagenFondo" height="130%" src="/img/logos/disposiciones_orange.png" alt="">
                      <!- Los que se muestran ->
                      <h1 class="tituloSeccion">DISPOSICIONES</h1>
                      <img height="62%" style="top:-580px;" class="imagenSeccion" src="/img/logos/disposiciones_white.png" alt="">
                </div>
            </a> -->
            <!-- <a href="disposiciones">
              <div id="tarjetaDisposiciones" class="tarjeta">
                <div class="mascara"><div class="cuadro"></div></div>
                <h2 class="titulo">DISPOSICIONES</h2>
                <p class="descripcion">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqa.</p>
              </div>
            </a> -->
          </div>
          @endif
          

      </div>
  <!-- </div>

</div> -->


@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_expedientes.js" charset="utf-8"></script>
@endsection
