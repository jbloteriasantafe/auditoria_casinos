@component('CierresAperturas/modal',[
  'clases_modal' => 'modalEliminar',
  'attrs_modal' => 'data-js-eliminar',
  'estilo_cabecera' => 'font-family: Roboto-Black; background-color:#D50000'
])
  @slot('titulo')
    ALERTA
  @endslot
  @slot('cuerpo')
    <h6 style="color:#000000 !important; font-size:17px !important;">¿ESTA SEGURO QUE DESEA ELIMINAR ESTE RELEVAMIENTO DE APUESTAS?</h6>
    <br>
  @endslot
  @slot('pie')
    <button type="button" class="btn btn-dangerEliminar"  data-js-click-eliminar>ELIMINAR</button>
  @endslot
@endcomponent
