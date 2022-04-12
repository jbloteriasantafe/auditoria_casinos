@if($primer_nivel)
<div class='card dropdown' style='{{$divli_style}};'>
  <a class='dropdown-toggle' data-toggle='dropdown' style='{{$link_style}};'>
    {!! $op !!}
  </a>
@else
<li class='dropdown-submenu' style='{{$divli_style}};'>
  <a class='desplegar-menu' style='{{$link_style}};'>
    @component('includes.barraMenuPrincipal_texto_con_icono',[
      'icono' => $icono,
      'op' => $op,
    ])
    @endcomponent
  </a>
@endif
<ul class='dropdown-menu'>
  @foreach($hijos as $op => $datos)
    @if(count($datos['hijos']) == 0)
      @component('includes.barraMenuPrincipal_link',[
        'primer_nivel' => false,
        'divli_style'  => $datos['divli_style'],
        'link_style'   => $datos['link_style'],
        'link'         => $datos['link'],
        'icono'        => $datos['icono'],
        'op'           => $op,
      ])
      @endcomponent
    @else
      @component('includes.barraMenuPrincipal_desplegable',[
        'primer_nivel' => false,
        'divli_style'  => $datos['divli_style'],
        'link_style'   => $datos['link_style'],
        'hijos'        => $datos['hijos'],
        'icono'        => $datos['icono'],
        'op'           => $op,
      ])
      @endcomponent
    @endif
  @endforeach
</ul>
@if($primer_nivel)
</div>
@else
</li>
@endif