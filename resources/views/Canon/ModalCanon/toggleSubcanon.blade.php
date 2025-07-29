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
@endcomponent
