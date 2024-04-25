@component('Components/modal',[
  'clases_modal' => 'modalEliminar',
  'attrs_modal' => 'data-js-modal-eliminar',
  'estilo_cabecera' => 'font-family: Roboto-Black; background-color:#D50000'
])
  @slot('titulo')
    ALERTA
  @endslot
  @slot('cuerpo')
    <h6 class="mensaje" style="color:#000000; font-size: 18px !important; text-align:center !important"></h6>
  @endslot
  @slot('pie')
    <button type="button" class="btn btn-dangerEliminar" data-js-modal-eliminar-click-eliminar>ELIMINAR</button>
  @endslot
@endcomponent
