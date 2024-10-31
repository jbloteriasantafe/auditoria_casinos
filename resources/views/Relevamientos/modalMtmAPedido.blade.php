@component('Components/modal',[
  'clases_modal' => 'modalMTMaP',
  'attrs_modal' => 'data-js-modal-mtm-a-p',
  'estilo_cabecera' => 'background-color: #6dc7be;',
])

@slot('titulo')
| NUEVO PEDIDO A MTM
@endslot

@slot('cuerpo')
<form class="row" novalidate="">
  <div class="col-md-6">
    <h5>CASINO</h5>
    <select name="id_casino" class="form-control">
      @if(count($casinos) != 1)
      <option value="">- Seleccione un casino -</option>
      @endif
      @foreach($casinos as $c)
      <option value="{{$c->id_casino}}" {{count($casinos) == 1? 'selected' : ''}}>{{$c->nombre}}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-6">
    <h5>NRO ADMIN</h5>
    <input name="nro_admin" class="form-control">
  </div>
  <div class="col-md-6">
    <h5>FECHA DE INICIO</h5>
    @component('Components/inputFecha',['attrs' => 'name="fecha_inicio"'])
    @endcomponent
  </div>
  <div class="col-md-6">
    <h5>FECHA DE FIN</h5>
    @component('Components/inputFecha',['attrs' => 'name="fecha_fin"'])
    @endcomponent
  </div>
</form>
{!! $append_body ?? '' !!}
@endslot

@slot('pie')
<button type="button" class="btn btn-successAceptar" data-js-aceptar>ACEPTAR</button>
@endslot

@endcomponent
