@component('Components/include_guard',['nombre' => 'sortearOusarBackup'])
<style>
</style>
@endcomponent

@component('Components/modal',[
  'clases_modal' => 'sortearOusarBackup',
  'attrs_modal' => 'data-js-sortear-usar-backup',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 40,
])
  @slot('titulo')
  <span data-mostrar="SORTEAR">SORTEAR APERTURAS</span>
  <span data-mostrar="BACKUP">USAR PLANILLA BACKUP</span>
  @endslot
  @slot('cuerpo')
  <div class="row">
    <div class="col-md-4">
      <h5>CASINO</h5>
      <select name="id_casino" class="form-control" disabled>
        @foreach ($casinos as $cas)
        <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <h5>FECHA</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_backup"','disabled' => 1])
      @endcomponent
    </div>
    <div class="col-md-4" data-mostrar="BACKUP">
      <h5>FECHA GENERACIÓN</h5>
      @component('Components/inputFecha',['attrs' => 'name="created_at"'])
      @endcomponent
    </div>
  </div>
  @endslot
  @slot('pie')
  <button data-js-sortear type="button" class="btn btn-successAceptar" data-mostrar="SORTEAR">SORTEAR</button>
  <button data-js-usar-backup type="button" class="btn btn-successAceptar" data-mostrar="BACKUP">USAR BACKUP</button>
  @endslot
@endcomponent
