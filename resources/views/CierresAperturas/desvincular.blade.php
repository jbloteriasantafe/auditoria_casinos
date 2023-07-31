@component('CierresAperturas/modal',[
  'clases_modal' => 'desvincularApertura',
  'attrs_modal' => 'data-js-desvincular',
  'estilo_cabecera' => 'background-color:#0D47A1;'
])
  @slot('titulo')
    ALERTA
  @endslot
  @slot('cuerpo')
    <h6>Esta Apertura fue vinculada a un Cierre determinado mediante la validación,
        puede observarse en los detalles de la misma.</h6>
    <h6>¿Desea deshacer esta validación y desvincular el Cierre?</h6>
  @endslot
  @slot('pie')
    <button type="button" class="btn btn-info" data-js-desvincular-boton>DESVINCULAR</button>
  @endslot
@endcomponent
