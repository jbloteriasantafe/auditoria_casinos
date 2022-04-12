<div class="texto_con_icono">
  @if(isset($icono) && $icono != '')
  <span style="flex: 1;">
  {!! $icono !!}
  </span>
  @endif
  @if(isset($op) && $op != '')
  <span style="flex: 4;">
  {!! $op !!}
  </span>
  @endif
</div>