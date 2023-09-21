@component('Components/modal',[
  'clases_modal' => 'alertaBaja',
  'attrs_modal' => 'data-js-alerta-baja',
  'estilo_cabecera' => 'font-family: Roboto-Black; background-color:#D50000'
])
  @slot('titulo')
    ALERTA
  @endslot
  @slot('cuerpo')
    <div data-js-eliminar-apertura hidden></div>
    <div data-js-eliminar-cierre   hidden></div>
    <h6 class="mensaje" style="color:#000000; font-size: 18px !important; text-align:center !important"></h6>
  @endslot
  @slot('pie')
    <button type="button" class="btn btn-dangerEliminar" data-js-eliminar>ELIMINAR</button>
  @endslot
@endcomponent
