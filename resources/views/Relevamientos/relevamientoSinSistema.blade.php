<!-- Modal Relevamientos -->
@component('Components/modal',[
  'clases_modal' => 'modalRelevamientoSinSistema',
  'attrs_modal' => 'data-js-modal-relevamiento-sin-sistema',
  'estilo_cabecera' => 'background-color: #6dc7be;',
])

@slot('titulo')
| RELEVAMIENTO SIN SISTEMA
@endslot

@slot('cuerpo')
<form id="frmRelSinSistema" name="frmRelSinSistema" class="form-horizontal" novalidate="">
  <div class="row">
    <div class="col-md-12">
      <div class="col-md-6">
        <h5>FECHA DE RELEVAMIENTO</h5>
        @component('Components/inputFecha',['attrs' => 'name="fecha"'])
        @endcomponent
      </div>
      <div class="col-md-6">
        <h5>FECHA DE GENERACIÓN</h5>
        @component('Components/inputFecha',['attrs' => 'name="fecha_generacion"'])
        @endcomponent
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="col-md-6">
        <h5>CASINO</h5>
        <select class="form-control" name="id_casino" data-js-cambio-casino-select-sectores="[data-js-modal-relevamiento-sin-sistema] [data-js-poner-sectores]">
          <option value="">- Seleccione un casino -</option>
          @foreach ($casinos as $casino)
          <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-6">
        <h5>SECTOR</h5>
        <select class="form-control" name="id_sector" data-js-poner-sectores>
        </select>
      </div>
    </div>
  </div>
</form>
@endslot

@slot('pie')
<button type="button" class="btn btn-successAceptar" data-js-usar-relevamiento-backup value="nuevo">USAR RELEVAMIENTO BACKUP</button>
@endslot

@endcomponent
