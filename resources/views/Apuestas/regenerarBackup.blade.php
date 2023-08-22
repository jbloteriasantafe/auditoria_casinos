@component('CierresAperturas/include_guard',['nombre' => 'regenerarBackup'])
<style>
</style>
@endcomponent

@component('CierresAperturas/modal',[
  'clases_modal' => 'regenerarBackup',
  'attrs_modal' => 'data-js-regenerar-backup',
  'estilo_cabecera' => 'background-color:#FFA726;',
  'grande' => 40,
])
  @slot('titulo')
  REGENERAR BACKUP
  @endslot
  @slot('cuerpo')
  <div class="row">
    <div class="col-md-4">
      <h5>CASINO</h5>
      <select name="id_casino" class="form-control">
        @foreach ($casinos as $cas)
        <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <h5>FECHA GENERACIÓN</h5>
      @component('CierresAperturas/inputFecha',['attrs' => 'name="fecha_generacion"'])
      @endcomponent
    </div>
    <div class="col-md-4">
      <h5>FECHA</h5>
      @component('CierresAperturas/inputFecha',['attrs' => 'name="fecha"'])
      @endcomponent
    </div>
  </div>
  @endslot
  @slot('pie')
  <button data-js-regenerar type="button" class="btn btn-warningModificar">REGENERAR</button>
  @endslot
@endcomponent
