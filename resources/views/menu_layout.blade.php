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
  <h2>LAYOUT</h2>
</header>

<div class="row"> <!-- primeraFila -->
  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_layout_total'))
    <div class="col-md-6">
      <a href="layout_total" class="tarjetaMenu">
          <div class="contenedorTarjeta" align="center">
                <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                <img class="imagenTarjeta" src="/img/tarjetas/img_Maquinas.jpg" alt="">
                <h1 class="tituloTarjeta">LAYOUT TOTAL</h1>
                <p class="detalleTarjeta">Informe de todos los sectores con sus respectivas islas totales por casino.</p>
          </div>
      </a>
    </div>
    @endif
    @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_layout_parcial'))
    <div class="col-md-6">
        <a href="layout_parcial" class="tarjetaMenu">
          <div class="contenedorTarjeta" align="center">
                <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                <img class="imagenTarjeta" src="/img/tarjetas/img_Progresivos.jpg" alt="">
                <h1 class="tituloTarjeta">LAYOUT PARCIAL</h1>
                <p class="detalleTarjeta">Detalle parcial de auditoria del funcionamiento de MTM por sector.</p>
          </div>
      </a>
    </div>
    @endif
  </div>

@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_layout.js" charset="utf-8"></script>
@endsection
