<div class="texto_con_icono">
  @if(isset($icono) && $icono != '')
  <span class="texto_con_icono_icono" style="flex: 1;">
  {!! $icono !!}
  </span>
  @endif
  @if(isset($op) && $op != '')
  <span class="texto_con_icono_texto" style="flex: 4;">
  {!! $op !!}
  </span>
  @endif
</div>