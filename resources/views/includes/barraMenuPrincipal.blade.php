<style>
#barraMenuPrincipal {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: space-between;
  padding: 0px;
  margin: 0px;
  font-family: Roboto-Regular;
}
#barraMenuPrincipal .card {
  height: inherit;
  flex: 1;
  display: flex;/*Esto es para que si hay varias entradas en el card se organizen una despues de la otra (icono de ayuda)*/
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  align-content: center;
}
#barraMenuPrincipal .card.open > ul {
  animation-name: mostrarse;
  animation-duration: 0.05s;
  animation-timing-function: ease-out
}
@keyframes mostrarse {
  from {
    clip-path: polygon(0% 0%, 100% 0%, 100% 0%, 0% 0%);
  }
  to {
    clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%);
  }
}
#barraMenuPrincipal a,#btn-ayuda{
  color: white;
  border-right:  1px solid rgba(255,255,255,0.15);
  border-bottom: 1px solid rgba(255,255,255,0.15);
  text-decoration: none;
  width: 100%;/*Estos hacen que ocupen todo el div*/
  height: 100%;
  display: flex;/*Estos centran verticalmente*/
  flex-direction: column;
  justify-content: center;
  text-align: center;
  background-color: {{$fondo}};
}
#barraMenuPrincipal div:hover,#barraMenuPrincipal a:hover,
#barraMenuPrincipal div:focus,#barraMenuPrincipal a:focus {
  background-color: #185891 !important;
  cursor: pointer;
}
#barraMenuPrincipal img {
  width: 1em;
}
#barraMenuPrincipal svg {
  max-height: 2em;
  fill: white;
  stroke: white;
}
#barraMenuPrincipal .dropdown-submenu {
  position: relative;
}
#barraMenuPrincipal .dropdown-menu {
  padding: 0px;
  margin: 0px;
  border: 0px;
  width: 100%;
}
#barraMenuPrincipal .dropdown-submenu .dropdown-menu {
  width: 16em !important;
  border: 1px solid #aaa;
  box-shadow: 1px 1px black;
}
#barraMenuPrincipal .dropdown-submenu .dropdown-menu.derecha {/*Posición del submenu*/
  top: 10%;
  left: 90%;
}
#barraMenuPrincipal .dropdown-submenu .dropdown-menu.izquierda {/*Posición del submenu*/
  top: 10%;
  left: -90%;
}
#barraMenuPrincipal .enlace,#barraMenuPrincipal .desplegar-menu {
  padding: 0px;
  line-height: 2em;
}
#barraMenuPrincipal .enlace{
  font-weight: bold;
}
#barraMenuPrincipal .desplegar-menu {
  font-style: oblique 11deg;
  background: linear-gradient(45deg, {{$fondo}} 0%, white 1500%);
  font-weight: 50;
}
#barraMenuPrincipal .texto_con_icono{
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: left;
  align-items: center;
  align-content: center;
}
#barraMenuPrincipal .texto_con_icono svg{
  width: unset;
  height: unset;
}
#barraMenuPrincipal .texto_con_icono i{
  font-size: 125%;
  line-height: 2em;
}
#barraMenuPrincipal .texto_con_icono span{
  word-wrap: break-all;
  white-space: normal;
}
#barraMenuPrincipal div,#barraMenuPrincipal li{
  /*Deshabilitar selección para que la apariencia sea mas de botones*/
  -webkit-touch-callout: none; /* iOS Safari */
  -webkit-user-select: none; /* Safari */
    -khtml-user-select: none; /* Konqueror HTML */
      -moz-user-select: none; /* Old versions of Firefox */
      -ms-user-select: none; /* Internet Explorer/Edge */
          user-select: none; /* Non-prefixed version, currently
                                supported by Chrome, Edge, Opera and Firefox */
}
</style>

<ul id="barraMenuPrincipal">
  <div class="card" style="flex:unset;width: 8vw;">
    <?php $fondoOL = '/img/tarjetas/banner_OL'.(rand(0,1) + 1).'.jpg'; ?>
    <a tabindex="-1" href="/inicio">
      <span><img src="/img/logos/logo_nuevo2_bn.png" style="width: 8vw;"></span>
    </a>
  </div>
  <div class="card" style="flex:unset;width: 8vw;">
    <a tabindex="-1" href="/configCuenta">
      <?php
      $img_user = $tiene_imagen ? '/configCuenta/imagen' : '/img/img_user.jpg';
      ?>
      <span>
        <img src='{{$img_user}}' class='img-circle' style="width: 2vw;">
      </span>
      {{$usuario->nombre}} 
      {{'@'.$usuario->user_name}}
    </a>
  </div>
  <div id="btn-ayuda" class="card" style="background-color: rgb(61, 106, 41);">
    @section('headerLogo')
    @show
    <span class="tituloSeccionPantalla" style="text-align: center;">---</span>
  </div>
  @foreach($opciones as $op => $datos)
    @if(count($datos['hijos']) == 0)
      @component('includes.barraMenuPrincipal_link',[
        'primer_nivel' => true,
        'divli_style'  => $datos['divli_style'],
        'link_style'   => $datos['link_style'],
        'link'         => $datos['link'],
        'icono'        => $datos['icono'],
        'op'           => $op,
      ])
      @endcomponent
    @else
      @component('includes.barraMenuPrincipal_desplegable',[
        'primer_nivel' => true,
        'divli_style'  => $datos['divli_style'],
        'link_style'   => $datos['link_style'],
        'hijos'        => $datos['hijos'],
        'icono'        => $datos['icono'],
        'op'           => $op,
      ])
      @endcomponent
    @endif
  @endforeach
  <div class="card dropdown" style="flex:unset;width: 5vw;" onclick="markNotificationAsRead('{{count($usuario->unreadNotifications)}}')">
    <a class="dropdown-toggle no_abrir_en_mouseenter" type="button" data-toggle="dropdown">
      @component('includes.barraMenuPrincipal_texto_con_icono',[
        'icono' => '<i class="far fa-bell" style="font-size: 100%;"></i>
                    <span class="badge" style="background: white;color: black;text-align: center;">'.
                    count($usuario->unreadNotifications).
                    '</span>',
      ])
      @endcomponent
    </a>
    <ul class="dropdown-menu" style="max-height: 300px; overflow-y:auto; width:350px;">
      @forelse ($usuario->unreadNotifications as $notif)
      <div style="background: #E6E6E6;">
          @include('includes.notifications.'.snake_case(class_basename($notif->type)))
      </div>
      @empty
      @forelse($usuario->lastNotifications() as $notif)
          @include('includes.notifications.'.snake_case(class_basename($notif->type)))
      @empty
      <a href="#" style="display: inline-block;width: 100%;">No hay nuevas Notificaciones</a>
      @endforelse
      @endforelse
    </ul>
  </div>
  @if($usuario->es_superusuario || $usuario->es_auditor)
  <div class="card" style="flex:unset;width: 5vw;">
    <a id="ticket" tabindex="-1" href="#">
      @component('includes.barraMenuPrincipal_texto_con_icono',[
        'icono' => '<i id="ticket" class="far fa-envelope"></i>',
      ])
      @endcomponent
    </a>
  </div>
  @endif
  <div class="card" style="flex:unset;width: 5vw;">
    <a id="calendario" tabindex="-1" href="/calendario_eventos">
      @component('includes.barraMenuPrincipal_texto_con_icono',[
        'icono' => '<i  class="far fa-fw fa-calendar-alt"></i>',
      ])
      @endcomponent
    </a>
  </div>
  <div class="card" style="flex:unset;width: 5vw;">
    <a class="etiquetaLogoSalida" tabindex="-1" href="#">
      @component('includes.barraMenuPrincipal_texto_con_icono',[
        'icono' => '<img src="/img/logos/salida.png">',
      ])
      @endcomponent
    </a>
  </div>
</ul>

<script type="module">
$(document).ready(function(){
  /* Deshabilito desplegar el menu principal en hover
  $('#barraMenuPrincipal > .dropdown > .dropdown-toggle:not(.no_abrir_en_mouseenter)').mouseenter(function(e){
    $(this).click();
  });*/
  function toggleSubmenu(e){//@TODO: ver porque se mueve el fondo si se desplegan muchos submenues
    e.preventDefault();
    e.stopPropagation();
    $(this).closest('ul.dropdown-menu')//voy para el menu de arriba
    .find('ul.dropdown-menu')
    .hide().removeClass('izquierda derecha');

    const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    const submenu = $(this).next('ul');
    if(submenu.length > 0){
      submenu.addClass('derecha');
      submenu.toggle();//Toggleo el submenu
      const relativo = submenu[0].getBoundingClientRect().right/vw;
      if(relativo > 1.){
        submenu.removeClass('derecha').addClass('izquierda');
      }
    }
    //$(this).blur();
  }
  $('#barraMenuPrincipal > .card > * a.desplegar-menu').mouseenter(toggleSubmenu).click(function(e){
    //Bloqueo todas las acciones al clickear
    e.preventDefault();
    e.stopPropagation();
  });
  $('#barraMenuPrincipal > .card > * a.enlace').mouseenter(toggleSubmenu);
  $(document).on('hidden.bs.dropdown','.dropdown',function(e){
    //Escondo todos los submenues cuando se esconde un menu de 1er nivel
    $(this).find('li.dropdown-submenu').find('ul.dropdown-menu').hide();
    $('#barraMenuPrincipal').find('.izquierda,.derecha').removeClass('izquierda derecha');
  });
});
</script>
