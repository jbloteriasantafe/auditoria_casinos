@extends('includes.nuevaBarraNavegacion')

@section('contenidoVista')


<!-- <div class="container-fluid">
  <div class="row">
    <div class="col-md-12 bannerEstadisticas">
      <h1><img width="80" src="/img/logos/informes_blue.png" alt=""> MENU INFORMES</h1>
    </div>
  </div>
</div> -->

<header>
  <img class="iconoSeccion" src="/img/logos/informes_blue.png" alt="">
  <h2>MENÚ INFORMES</h2>
</header>

        <div class="row">

            <div class="col-lg-4">
              <a href="informesMTM" class="tarjetaMenu">
                  <div class="contenedorTarjeta" align="center">
                        <img class="imagenTarjeta" src="/img/tarjetas/img_InformesMTM.jpg" alt="">
                        <h1 class="tituloTarjeta">MTM</h1>
                        <p class="detalleTarjeta">Aquí se pueden ver los beneficios anuales de MTM.</p>
                  </div>
              </a>
              <!-- <a href="informesMTM" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <!- Fondo de sección ->
                        <h1 class="tituloFondo">MTM</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/informes_mtm_orange.png" alt="">
                        <!- Los que se muestran ->
                        <h1 class="tituloSeccion">MTM</h1>
                        <img height="68%" style="top:-580px;" class="imagenSeccion" src="/img/logos/informes_mtm_white.png" alt="">
                  </div>
              </a> -->
            </div>

            <div class="col-lg-4">
              <a href="informesBingo" class="tarjetaMenu">
                  <div class="contenedorTarjeta" align="center">
                        <img class="imagenTarjeta" src="/img/tarjetas/img_InformesBingo.jpg" alt="">
                        <h1 class="tituloTarjeta">BINGOS</h1>
                        <p class="detalleTarjeta">Aquí se pueden ver los beneficios anuales de Bingo.</p>
                  </div>
              </a>
                <!-- <a href="informesBingo" style="text-decoration:none;">
                    <div class="tarjetaSeccion" align="center">
                          <!- Fondo de sección ->
                          <h1 class="tituloFondo">BINGOS</h1>
                          <img class="imagenFondo" height="130%" src="/img/logos/informes_bingo_orange.png" alt="">
                          <!- Los que se muestran ->
                          <h1 class="tituloSeccion">BINGOS</h1>
                          <img height="62%" style="top:-574px;" class="imagenSeccion" src="/img/logos/informes_bingo_white.png" alt="">
                    </div>
                </a> -->
            </div>

            <div class="col-lg-4">
              <a href="informesJuegos" class="tarjetaMenu">
                  <div class="contenedorTarjeta" align="center">
                        <img class="imagenTarjeta" src="/img/tarjetas/img_InformesJuegos.jpg" alt="">
                        <h1 class="tituloTarjeta">MESAS DE PAÑO</h1>
                        <p class="detalleTarjeta">Aquí se pueden ver los beneficios anuales de Mesas de paños.</p>
                  </div>
              </a>
              <!-- <a href="informesJuegos" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <!- Fondo de sección ->
                        <h1 class="tituloFondo">JUEGOS</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/informes_juegos_orange.png" alt="">
                        <!- Los que se muestran ->
                        <h1 class="tituloSeccion">JUEGOS</h1>
                        <img height="62%" style="top:-574px;" class="imagenSeccion" src="/img/logos/informes_juegos_white.png" alt="">
                  </div>
              </a> -->
            </div>

        </div>


@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_informes.js" charset="utf-8"></script>

@endsection
