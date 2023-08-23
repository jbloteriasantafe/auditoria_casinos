@component('../CierresAperturas/include_guard',['nombre' => 'generarRelevamiento'])
<style>
  .generarRelevamiento .form-control {
    text-align: center;
  }
</style>
@endcomponent

@component('CierresAperturas/modal',[
  'clases_modal' => 'generarRelevamiento',
  'attrs_modal' => 'data-js-generar-relevamiento',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 30,
])
  @slot('titulo')
  NUEVO RELEVAMIENTO
  @endslot
  @slot('cuerpo')
  <div class="row">
    <div class="col-md-12">
      <div class="col-md-6">
        <h5>FECHA</h5>
        @component('CierresAperturas/inputFecha',[
          'attrs' => 'name="fecha"',
          'attrs_dtp' => 'data-date-format="DD dd - MM - yyyy"',
        ])
        @endcomponent
      </div>
      <div class="col-md-6">
        <h5>CASINO</h5>
        <select name="id_casino" class="form-control">
          @if(count($casinos) == 1)
          <option value="{{$casinos[0]->id_casino}}">{{$casinos[0]->nombre}}</option>
          @else
          <option value="">— SELECCIONE —</option>
          @foreach ($casinos as $cas)
          <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
          @endforeach
          @endif
        </select>
      </div>
    </div>
  </div>
  @endslot
  @slot('pie')
  <button data-js-generar type="button" class="btn btn-successAceptar">GENERAR</button>
  @endslot
@endcomponent
