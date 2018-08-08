@extends('includes.nuevaBarraNavegacion')

@section('contenidoVista')

<!-- <div class="container-fluid">
  <div class="row">
    <div class="col-md-12 bannerMUsuarios">
      <h1><img width="80" src="/img/logos/usuarios_blue.png" alt=""> USUARIOS</h1>
    </div>
  </div>
</div> -->

<header>
  <img class="iconoSeccion" src="/img/logos/usuarios_blue.png" alt="">
  <h2>USUARIOS</h2>
</header>


<!-- <div id="page-wrapper"> -->
    <!-- <div class="container-fluid"> -->
        <div class="row">

            <div class="col-md-4">
              <!-- <a href="usuarios" style="text-decoration:none;">
                  <div class="tarjetaSeccion" align="center">
                        <h1 class="tituloFondo">USUARIOS</h1>
                        <img class="imagenFondo" height="130%" src="/img/logos/gestion_usuarios_orange.png" alt="">
                        <h1 class="tituloSeccion">GESTIÓN USUARIOS</h1>
                        <img height="68%" style="top:-580px;" class="imagenSeccion" src="/img/logos/gestion_usuarios.png" alt="">
                  </div>
              </a> -->

              <!-- <a href="usuarios" style="text-decoration:none;">
                  <div class="" align="center" style="margin: 0px 8px 15px 8px; height:650px; background-color: #F5F5F5;">
                        <h1 class="" style="color: #555; font-family: Roboto-Condensed; padding: 50px 10px 20px 10px; margin: 0px;">GESTIÓN USUARIOS</h1>
                        <img style="padding-top:100px;" width="90%" class="" src="/img/logos/usuarios_blue.png" alt="">
                  </div>
              </a> -->


              <!-- <a href="usuarios" style="text-decoration: none;">
                  <div class="tarjetaMenu">
                      <img src="/img/tarjetas/img_2.jpg" alt="">
                      <h1>GESTIÓN USUARIOS</h1>
                  </div>
              </a> -->
              <!-- <a href="usuarios" style="text-decoration: none;">
                  <div class="tarjetaaa">
                      <img src="/img/tarjetas/img_1.jpg" alt="">
                      <h1>GESTIÓN USUARIOS</h1>
                  </div>
              </a> -->

              <a href="usuarios" class="tarjetaMenu">
                  <div class="contenedorTarjeta" align="center">
                        <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                        <img class="imagenTarjeta" src="/img/tarjetas/img_GestionUsuarios.jpg" alt="">
                        <h1 class="tituloTarjeta">GESTIONAR USUARIOS</h1>
                        <p class="detalleTarjeta">Esta sección permite crear, modificar y eliminar usuarios, además de poder filtrar los usuarios de la lista.</p>
                  </div>
              </a>

            </div>
              <div class="col-md-4">
                <!-- <a href="logActividades" style="text-decoration:none;">
                    <div class="tarjetaSeccion" align="center">
                          <h1 class="tituloFondo">ACTIVIDADES</h1>
                          <img class="imagenFondo" height="130%" src="/img/logos/log_actividades_orange.png" alt="">
                          <h1 class="tituloSeccion">LOG DE ACTIVIDADES</h1>
                          <img height="62%" style="top:-574px;" class="imagenSeccion" src="/img/logos/log_actividades.png" alt="">
                    </div>
                </a> -->
                <!-- <a href="usuarios" style="text-decoration: none;">
                    <div class="tarjetaMenu">
                        <img src="/img/tarjetas/img_2.jpg" alt="">
                        <h1>LOG DE ACTIVIDADES</h1>
                    </div>
                </a> -->
                <!-- <a href="logActividades" style="text-decoration:none;">
                    <div class="" align="center" style="margin: 0px 8px 15px 8px; height:650px; background-color: #F5F5F5;">
                          <h1 class="" style="color: #555; font-family: Roboto-Condensed; padding: 50px 10px 20px 10px; margin: 0px;">LOG DE ACTIVIDADES</h1>
                          <img style="padding-top:135px;" width="80%" class="" src="/img/logos/log_actividades_blue.png" alt="">
                    </div>
                </a> -->

                <a href="roles" class="tarjetaMenu">
                    <div class="contenedorTarjeta" align="center">
                          <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                          <img class="imagenTarjeta" src="/img/tarjetas/img_RolesPermisos.jpg" alt="">
                          <h1 class="tituloTarjeta">ROLES Y PERMISOS</h1>
                          <p class="detalleTarjeta">Ingrese aquí para ver la sección de manejo de permisos/roles a usuarios.</p>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
              <a href="logActividades" class="tarjetaMenu">
                  <div class="contenedorTarjeta" align="center">
                        <!-- <img class="imagenTarjeta" src="/img/tarjetas/img_1.jpg" alt=""> -->
                        <img class="imagenTarjeta" src="/img/tarjetas/img_LogActividad.jpg" alt="">
                        <h1 class="tituloTarjeta">LOG DE ACTIVIDADES</h1>
                        <p class="detalleTarjeta">Muestra movimientos internos de acciones en el sistema.</p><br>
                  </div>
              </a>
            </div>

        </div>

    <!-- </div> -->

<!-- </div> -->


@endsection
@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/menu_usuarios.js" charset="utf-8"></script>

@endsection
