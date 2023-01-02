<?php
use App\Http\Controllers\UsuarioController;
?>
@if(UsuarioController::getInstancia()->quienSoy()['usuario']->tienePermiso('cotizar_dolar_peso')) 
<div class="col-md-12">
  <a id="btn-cotizacion" href="" style="text-decoration:none;">
    <div class="tarjetaSeccionMenor" align="center">
      <h2 class="tituloFondoMenor"> COTIZACIÓN</h2>
      <h2 class="tituloSeccionMenor">COTIZACIÓN </h2>
      <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/peso-dollar.svg" alt="">
    </div>
  </a>
</div>
@endif
