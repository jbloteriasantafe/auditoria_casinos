@component('Components.include_guard',['nombre' => 'Canon_toggle_'.$tipo])
<style>
  [{!! $parentAttr !!}="{!! $estado1 !!}"] [{!! $childAttr !!}="{!! $estado2 !!}"] {
    display: none !important;
  }
  [{!! $parentAttr !!}="{!! $estado2 !!}"] [{!! $childAttr !!}="{!! $estado1 !!}"] {
    display: none !important;
  }
</style>
@endcomponent
<button type="button" class="btn" 
  {!! $extraAttrs ?? '' !!}
  data-js-click-toggle="{{ e(json_encode(['parentSelector' => $parentSelector,'parentAttr' => $parentAttr,'nuevoEstado' => $estado1])) }}" 
  {!! $childAttr !!}="{!! $estado2 !!}">
  {!! $htmlEstado1 ?? $estado1 !!}
</button>
<button type="button" class="btn" 
  {!! $extraAttrs ?? '' !!}
  data-js-click-toggle="{{ e(json_encode(['parentSelector' => $parentSelector,'parentAttr' => $parentAttr,'nuevoEstado' => $estado2])) }}" 
  {!! $childAttr !!}="{!! $estado1 !!}">
  {!! $htmlEstado2 ?? $estado2 !!}
</button>
