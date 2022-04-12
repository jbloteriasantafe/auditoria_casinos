@if($primer_nivel)
<div class='card' style='{{$divli_style}};'>
@else
<li style='{{$divli_style}};'>
@endif
  <a class="enlace" tabindex='-1' href='{{$link}}' style='{{$link_style}};'>
    @component('includes.barraMenuPrincipal_texto_con_icono',[
      'icono' => $icono,
      'op' => $op,
    ])
    @endcomponent
  </a>
@if($primer_nivel)
</div>
@else
</li>
@endif