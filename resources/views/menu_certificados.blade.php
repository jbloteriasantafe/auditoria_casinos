@extends('includes.barraNavegacion')
@section('contenidoVista')

<div class="container-fluid">
  <div class="row">
    <div class="col-md-12 bannerMCertificados">
      <h1> | CERTIFICADOS</h1><br>
    </div>
  </div>
</div>

<div id="page-wrapper">

  <div class="container-fluid">
      <div class="row">
          <div class="col-lg-6">
            <a href="certificadoSoft">
              <div id="tarjetaSoft" class="tarjeta">
                <div class="mascara"><div class="cuadro"></div></div>
                <h2 class="titulo">CERTIFICADOS GLI SOFTWARE</h2>
                <p class="descripcion">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqa.</p>
              </div>
            </a>
          </div>
          <div class="col-lg-6">
            <a href="certificadoHard">
              <div id="tarjetaHard" class="tarjeta">
                <div class="mascara"><div class="cuadro"></div></div>
                <h2 class="titulo">CERTIFICADOS GLI HARDWARE</h2>
                <p class="descripcion">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqa.</p>
              </div>
            </a>
          </div>
      </div>
  </div>

</div>


@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_certificados.js" charset="utf-8"></script>
@endsection
