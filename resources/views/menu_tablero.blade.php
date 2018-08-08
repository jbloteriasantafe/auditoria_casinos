@extends('includes.nuevaBarraNavegacion')

@section('contenidoVista')

<header>
  <img class="iconoSeccion" src="/img/logos/tablero_blue.png" alt="">
  <h2>TABLERO DE CONTROL</h2>
</header>


        <div class="row">

            <div class="col-lg-4">
              <a href="estadisticasGenerales" class="tarjetaMenu">
                  <div class="contenedorTarjeta" align="center">
                        <img class="imagenTarjeta" src="/img/tarjetas/img_TableroGeneral.jpg" alt="">
                        <h1 class="tituloTarjeta">GENERALES</h1>
                        <p class="detalleTarjeta">Informes comparativos de los casinos existentes en Santa Fe.</p><br>
                  </div>
              </a>
              <!-- <a href="estadisticasGenerales" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <!- Fondo de sección ->
                        <h1 class="tituloFondo">GENERALES</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/tablero_general_orange.png" alt="">
                        <!- Los que se muestran ->
                        <h1 class="tituloSeccion">GENERALES</h1>
                        <img height="68%" style="top:-580px;" class="imagenSeccion" src="/img/logos/tablero_general_white.png" alt="">
                  </div>
              </a> -->
            </div>

            <div class="col-lg-4">
              <a href="estadisticasPorCasino" class="tarjetaMenu">
                  <div class="contenedorTarjeta" align="center">
                        <img class="imagenTarjeta" src="/img/tarjetas/img_TableroPorCasino.jpg" alt="">
                        <h1 class="tituloTarjeta">POR CASINO</h1>
                        <p class="detalleTarjeta">Muestra estadísticas comparativas que se efectúan en un casino en particular.</p>
                  </div>
              </a>
              <!-- <a href="estadisticasPorCasino" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <!- Fondo de sección ->
                        <h1 class="tituloFondo">CASINO</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/tablero_casino_orange.png" alt="">
                        <!- Los que se muestran ->
                        <h1 class="tituloSeccion">POR CASINO</h1>
                        <img height="62%" style="top:-574px;" class="imagenSeccion" src="/img/logos/tablero_casino_white.png" alt="">
                  </div>
              </a> -->
            </div>

            <div class="col-lg-4">
              <a href="interanuales" class="tarjetaMenu">
                  <div class="contenedorTarjeta" align="center">
                        <img class="imagenTarjeta" src="/img/tarjetas/img_TableroInteranual.jpg" alt="">
                        <h1 class="tituloTarjeta">INTERANUALES</h1>
                        <p class="detalleTarjeta">Reportes que comparan el desempeño anual de cada casino.</p><br>
                  </div>
              </a>
                <!-- <a href="interanuales" style="text-decoration:none;">
                    <div class="tarjetaSeccion" align="center">
                          <!- Fondo de sección ->
                          <h1 class="tituloFondo">INTERANUALES</h1>
                          <img class="imagenFondo" height="130%" src="/img/logos/interanual_orange.png" alt="">
                          <!- Los que se muestran ->
                          <h1 class="tituloSeccion">INTERANUALES</h1>
                          <img height="62%" style="top:-574px;" class="imagenSeccion" src="/img/logos/interanual_white.png" alt="">
                    </div>
                </a> -->
            </div>

        </div>


@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_tablero.js" charset="utf-8"></script>

@endsection
