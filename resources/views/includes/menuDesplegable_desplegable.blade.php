<li class="menu_con_opciones">
  <span>{{$op}}</span>
  <ul>
    @foreach($hijos as $op => $datos)
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
</li>