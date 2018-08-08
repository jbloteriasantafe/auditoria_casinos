 de@extends('includes.nuevaBarraNavegacion')
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$id_usuario = session('id_usuario');
?>

<header>
  <img class="iconoSeccion" src="/img/logos/gestion_maquinas_blue.png" alt="">
  <h2>GESTIONAR MÁQUINAS</h2>
</header>

      <div class="row"> <!-- primeraFila -->
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_maquinas'))
          <div class="col-md-4">
            <a href="maquinas" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Maquinas.jpg" alt="">
                      <h1 class="tituloTarjeta">MÁQUINAS</h1>
                      <p class="detalleTarjeta">Esta sección permite visualizar, realizar búsquedas, generar nuevas máquinas y realizar cargas masivas de máquinas.</p>
                </div>
            </a>
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_progresivos'))
          <div class="col-md-4">
            <a href="progresivos" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Progresivos.jpg" alt="">
                      <h1 class="tituloTarjeta">PROGRESIVOS</h1>
                      <p class="detalleTarjeta">Se podrán cargar los distintos tipos de progresivos asociados a sus respectivas tragamonedas.</p>
                </div>
            </a>
          </div>
          @endif
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_islas'))
          <div class="col-md-4">
            <a href="islas" class="tarjetaMenu">
                <div class="contenedorTarjeta" align="center">
                      <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                      <img class="imagenTarjeta" src="/img/tarjetas/img_Islas.jpg" alt="">
                      <h1 class="tituloTarjeta">ISLAS</h1>
                      <p class="detalleTarjeta">Definir, modificar o cambiar islas en cada sector del casino, pudiendo asociar MTM en ellas.</p>
                </div>
            </a>
          </div>
          @endif

      </div> <!-- ./primeraFila -->

      <div class="row">
          <div class="col-xl-3 col-md-6">
              <a href="formulas" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">FÓRMULAS</h2>
                    <h2 class="tituloSeccionMenor">FÓRMULAS</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/formulas_white.png" alt="">
                  </div>
              </a>
          </div>
          <div class="col-xl-3 col-md-6">
            <a href="juegos" style="text-decoration:none;">
                <div class="tarjetaSeccionMenor" align="center">
                  <h2 class="tituloFondoMenor">JUEGOS</h2>
                  <h2 class="tituloSeccionMenor">JUEGOS</h2>
                  <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/juegos_white.png" alt="">
                </div>
            </a>
          </div>
          <div class="col-xl-3 col-md-6">
              <a href="certificadoSoft" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">GLISOFT</h2>
                    <h2 class="tituloSeccionMenor">GLI SOFTWARE</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/software_white.png" alt="">
                  </div>
              </a>
          </div>
          <div class="col-xl-3 col-md-6">
              <a href="certificadoHard" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">GLIHARD</h2>
                    <h2 class="tituloSeccionMenor">GLI HARDWARE</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/hardware_white.png" alt="">
                  </div>
              </a>
          </div>
      </div>



@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_gestionarMTM.js" charset="utf-8"></script>
@endsection
