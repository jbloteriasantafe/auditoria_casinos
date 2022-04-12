<style>
#menuDesplegable {
  color: #fff;
  font-family: Roboto-Regular;
  font-size: 1.25em;
  background-color: {{$fondo}};
}
#menuDesplegable.visible {
  animation-name: mostrarse_horizontal;
  animation-duration: 0.03s;
  animation-timing-function: ease-in
}
@keyframes  mostrarse_horizontal {
  from {
    clip-path: polygon(0% 0%, 0% 100%, 0% 100%, 0% 0%);
  }
  to {
    clip-path: polygon(0% 0%, 0% 100%, 100% 100%, 100% 0%);
  }
}
#menuDesplegable ul {
  box-shadow: inset 0 0 0 100vw rgba(255,255,255,0.04);
  padding-left:    0px;
  margin-left:     10px;
  margin-bottom:   10px;
  border-left:   3px solid #185891db;
  border-right:  3px solid #00000000;
  border-top:    2px solid #00000000;
  border-bottom: 2px solid #00000000;
  list-style-type: none;
}
#menuDesplegable > ul{/*Lo saco al borde izq para el primer nivel*/
  border-left:   3px solid #00000000;
}
#menuDesplegable li {
  text-align: left;
  padding: 2px;
}
#menuDesplegable .enlace {
  text-decoration: none;
  color: rgba(190,190,255,0.85);
  border-bottom: 1px solid rgba(255,255,255,0.15);
  text-align: center;
}
#menuDesplegable .enlace > a, #menuDesplegable .menu_con_opciones > span, #menuDesplegable .menu_con_opciones_desplegado > span,#menuDesplegable .desactivado > span{
  display: block;
  width: 100%;
}
#menuDesplegable .desactivado {
  text-align: center;
  color: rgba(255,255,255,0.7);
  background: repeating-linear-gradient(45deg,
      rgba(  0,  0,  0,0.05),
      rgba(255,255,255,0.05) 5px,
      rgba(  0,  0,  0,0.05) 5px,
      rgba(255,255,255,0.05) 5px
  );
  font-style: italic;
}
#menuDesplegable .menu_con_opciones > span{
  text-align: center;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  font-weight: bold;
}
#menuDesplegable .menu_con_opciones > ul{
  display: none;
}
#menuDesplegable .menu_con_opciones_desplegado > span{
  border-bottom: 3px solid #185891db;
}
#menuDesplegable a {
  color: white;
  text-decoration: none;
  background: repeating-linear-gradient(45deg,
    rgba(  0,  0,  0,0.05),
    rgba(24, 88, 145,0.5)  5px,
    rgba(  0,  0,  0,0.05) 5px,
    rgba(24, 88, 145,0.5)  5px
  );
}
#menuDesplegable .opcion_actual a{
  background: repeating-linear-gradient(45deg,
    rgba(  0,  0,  0,0.05),
    rgba(61, 106, 41,0.5) 5px,
    rgba(  0,  0,  0,0.05) 5px,
    rgba(61, 106, 41,0.5) 5px
  );
}
#menuDesplegable .enlace > a:hover, #menuDesplegable .menu_con_opciones > span:hover, #menuDesplegable .menu_con_opciones_desplegado > span:hover,#menuDesplegable .desactivado > span:hover{
  cursor: pointer;
  background-color: #185891;
}

#menuDesplegable .menu_con_opciones_desplegado > ul {
  animation-name: mostrarse;
  animation-duration: 0.11s;
  animation-timing-function: ease-out;
}
#menuDesplegable .menu_con_opciones_desplegado > span {
  text-align: left;
  animation-name: mover;
  animation-duration: 0.08s;
  animation-timing-function: linear;
}
@keyframes mostrarse {
  from {
    clip-path: polygon(0% 0%, 100% 0%, 100% 0%, 0% 0%);
  }
  to {
    clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%);
  }
}
@keyframes mover {/*Bastante @HACK, si el texto overflowea en varias lineas seguro se ve horrible, pero para una linea funciona*/
  from {
    transform: translate(30%);
  }
  to {
    transform: translate(0%);
  }
}
#botonMenuDesplegable {
  color: #fff;
  border-color: rgb(0,0,0,0.5);
  background-color: {{$fondo}};
}
</style>

<div style="width:100%;position: absolute;z-index: 3;">
  <aside id="menuDesplegable" style="height: 100vh;width: 15%;float: left;overflow-y: scroll;" hidden>
    <ul class="menu_con_opciones_desplegado" style="margin-top: 5%;">
    @if(!is_null($tarjeta_css))
    <div style="{{$tarjeta_css}};">
      <a>&nbsp;</a>
    </div>
    @endif
    @foreach($opciones as $op => $datos)
      @if(count($datos['hijos']) == 0)
        @component('includes.menuDesplegable_link',[
          'link'         => $datos['link'],
          'op'           => $op,
        ])
        @endcomponent
      @else
        @component('includes.menuDesplegable_desplegable',[
          'op'           => $op,
          'hijos'        => $datos['hijos'],
        ])
        @endcomponent
      @endif
    @endforeach
    </ul>
  </aside>
  <div style="float: left;">
    <button id="botonMenuDesplegable" type="button" class="btn" 
      data-toggle="#menuDesplegable,#oscurecerContenido,#botonDerecha,#botonIzquierda" 
      style="z-index: 4;position: absolute;">
      <i id="botonDerecha" class="fa fa-fw fa-solid fa-arrow-right"></i>
      <i id="botonIzquierda" class="fa fa-fw fa-solid fa-arrow-left" style="display: none;"></i>
    </button>
  </div>
  <div id="oscurecerContenido" style="position:absolute;z-index: 3;height: 100%;left: 15%;width: 100%;float:left;background: rgba(0,0,0,0.2);" hidden>
    &nbsp;
  </div>
</div>

<script type="module">
$(document).ready(function(){
  $('#menuDesplegable .menu_con_opciones > span,#menuDesplegable .menu_con_opciones_desplegado > span').click(function(e){
    if($(this).parent().hasClass('menu_con_opciones_desplegado')){//Si esta desplegado solo escondo todo lo por debajo
      //Submenues
      $(this).parent().find('.menu_con_opciones_desplegado').removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
      //Padre
      $(this).parent().removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
      return;
    }
    //Si hizo click en otro menu, escondo todo y desplego el arbol hasta ahi
    //Escondo todo
    $('#menuDesplegable .menu_con_opciones_desplegado').removeClass('menu_con_opciones_desplegado').addClass('menu_con_opciones');
    //Abro todos los padres
    $(this).parents('.menu_con_opciones').removeClass('menu_con_opciones').addClass('menu_con_opciones_desplegado');
  });
  $('#botonMenuDesplegable').click(function(e){
    //Busco la opcion basado en la URL y la diferencio
    const opcion_actual = $('#menuDesplegable a').filter(function(){
      return $(this).attr('href') == ("/"+window.location.pathname.split("/")[1]);
    });
    //Lo marco como que es la opción actual mostrandose
    opcion_actual.parent().toggleClass('opcion_actual');
    //Desplego la opcion
    opcion_actual.closest('.menu_con_opciones').children('span').click();
    //Muestro el menu
    $($(this).attr('data-toggle')).toggle().toggleClass('visible');
  });
})
</script>