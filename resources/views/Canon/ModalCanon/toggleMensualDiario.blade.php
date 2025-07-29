@component(
  'Canon.ModalCanon.toggle',
  [
    'tipo' => 'MensualDiario',
    'parentSelector' => '[data-subcanon-toggle-mensual-diario-estado]',
    'parentAttr' => 'data-subcanon-toggle-mensual-diario-estado',
    'childAttr'  => 'data-mensual-diario-toggle-visible',
    'estado1' => 'mensual',
    'estado2' => 'diario',
  ]
)
  @slot('htmlEstado1')
  <i class="fa fa-circle" style="font-weight: 200;"></i>
  @endslot
  @slot('htmlEstado2')
  <i class="fa fa-dot-circle" style="font-weight: 200;"></i>
  @endslot
@endcomponent
