@component('Components.include_guard',['nombre' => 'Canon_toggleMensualDiario'])
<style>
  [data-subcanon-toggle-mensual-diario-estado="mensual"] [data-mensual-diario-toggle-visible="diario"] {
    display: none !important;
  }
  [data-subcanon-toggle-mensual-diario-estado="diario"] [data-mensual-diario-toggle-visible="mensual"] {
    display: none !important;
  }
</style>
@endcomponent
<button type="button" class="btn" data-js-click-subcanon-mensual-diario-toggle-set="mensual" data-mensual-diario-toggle-visible="diario">
  MENSUAL
</button>
<button type="button" class="btn" data-js-click-subcanon-mensual-diario-toggle-set="diario" data-mensual-diario-toggle-visible="mensual">
  DIARIO
</button>
