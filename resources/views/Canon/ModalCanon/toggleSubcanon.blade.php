@component(
  'Canon.ModalCanon.toggle',
  [
    'tipo' => 'Subcanon',
    'parentSelector' => '[data-subcanon-toggle-estado]',
    'parentAttr' => 'data-subcanon-toggle-estado',
    'childAttr'  => 'data-subcanon-toggle-visible',
    'estado1' => 'mostrar_subcanon',
    'estado2' => 'esconder_subcanon',
    'extraAttrs' => 'data-js-subcanon-mostrar-esconder-siblings'
  ]
)
  @slot('htmlEstado1')
  <i class="fa fa-dot-circle" style="font-weight: 200;"></i>
  @endslot
  @slot('htmlEstado2')
  <i class="fa fa-circle" style="font-weight: 200;"></i>
  @endslot
@endcomponent
