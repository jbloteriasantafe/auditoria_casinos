@component('Components/include_guard',['nombre' => 'Canon_toggleSubCanon'])
<style>
  [data-subcanon-toggle-estado="esconder"] [data-subcanon-toggle-visible="mostrar"] {
    display: none !important;
  }
  [data-subcanon-toggle-estado="mostrar"] [data-subcanon-toggle-visible="esconder"] {
    display: none !important;
  }
</style>
@endcomponent
<button type="button" class="btn" data-js-click-subcanon-toggle-set="mostrar" data-subcanon-toggle-visible="esconder" hidden>
  MOSTRAR
</button>
<button type="button" class="btn" data-js-click-subcanon-toggle-set="esconder" data-subcanon-toggle-visible="mostrar" hidden>
  ESCONDER
</button>
