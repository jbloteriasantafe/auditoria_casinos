@extends('includes.nuevaBarraNavegacion')
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$id_usuario = session('id_usuario');
?>

<header>
  <img class="iconoSeccion" src="/img/logos/layout_blue.png" alt="">
  <h2>MOVIMIENTOS</h2>
</header>

<div class="row"> <!-- primeraFila -->
  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_gestionar_movimientos'))
    <div class="col-md-6">
      <a href="movimientos" class="tarjetaMenu">
          <div class="contenedorTarjeta" align="center">
                <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                <img class="imagenTarjeta" src="/img/tarjetas/img_Maquinas.jpg" alt="">
                <h1 class="tituloTarjeta">Asignación de Movimientos a Relevar</h1>
                <p class="detalleTarjeta">Asignación de cambios a maquinas y envío a fiscalizar de movimientos.</p>
          </div>
      </a>
    </div>
    @endif
    @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_relevamientos_movimientos'))
    <div class="col-md-6">
        <a href="relevamientos_movimientos" class="tarjetaMenu">
          <div class="contenedorTarjeta" align="center">
                <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                <img class="imagenTarjeta" src="/img/tarjetas/img_Progresivos.jpg" alt="">
                <h1 class="tituloTarjeta">RELEVAMIENTOS</h1>
                <p class="detalleTarjeta">Detalle de los relevamientos de movimientos a fiscalizar.</p>
          </div>
      </a>
    </div>
    @endif
    @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_eventualidades'))
    <div class="col-md-6">
        <a href="eventualidades" class="tarjetaMenu">
          <div class="contenedorTarjeta" align="center">
                <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                <img class="imagenTarjeta" src="/img/tarjetas/img_Islas.jpg" alt="">
                <h1 class="tituloTarjeta">INTERVENCIONES TÉCNICAS</h1>
                <p class="detalleTarjeta">Detalle de las intervenciones técnicas y ambientales que afectan a determinadas máquinas.</p>
          </div>
      </a>
    </div>
    @endif
    @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_eventualidades_MTM'))
    <div class="col-md-6">
        <a href="eventualidadesMTM" class="tarjetaMenu">
          <div class="contenedorTarjeta" align="center">
                <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                <img class="imagenTarjeta" src="/img/tarjetas/img_GestionMaquinas.jpg" alt="">
                <h1 class="tituloTarjeta">INTERVENCIONES MTM</h1>
                <p class="detalleTarjeta">Detalle de las intervenciones sobre máquinas, que corresponden a movimientos que requieren expedientes y aún no lo tienen.</p>
          </div>
      </a>
    </div>
    @endif
  </div>

@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_movimientos.js" charset="utf-8"></script>
@endsection
