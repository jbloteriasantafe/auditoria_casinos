@component('Components/modal',[
  'clases_modal' => 'modalMaquinasPorRelevamiento',
  'attrs_modal' => 'data-js-modal-maquinas-por-relevamiento',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 60,
])

@slot('titulo')
| MÁQUINAS POR RELEVAMIENTOS
@endslot

@slot('cuerpo')
<form class="row">
  <div class="col-md-4">
    <h5>CASINO</h5>
    <select class="form-control" data-js-cambio-casino-select-sectores="[data-js-modal-maquinas-por-relevamiento] [data-js-poner-sectores]">
      <option value="">- Seleccione un casino -</option>
      @foreach ($casinos as $casino)
      <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4">
    <h5>SECTOR</h5>
    <select name="id_sector" class="form-control" data-js-poner-sectores data-js-cambio-sector>
    </select>
  </div>
  <div class="col-md-4">
    <h5>TIPO</h5>
    <select name="id_tipo_cantidad_maquinas_por_relevamiento" class="form-control" data-js-cambio-tipo>
      <option value="">- Seleccione el tipo -</option>
      @foreach($tipos_cantidad as $tipo_cantidad)
      <option value="{{$tipo_cantidad->id_tipo_cantidad_maquinas_por_relevamiento}}">
        {{$tipo_cantidad->descripcion}}
      </option>
      @endforeach
    </select>
  </div>
</div>
<div class="row">
  <div class="col-md-4">
    <h5>FECHA DESDE</h5>
    @component('Components/inputFecha',['attrs' => 'name="fecha_desde" data-js-cambio-eliminar-forzar'])
    @endcomponent
  </div>
  <div class="col-md-4">
    <h5>FECHA HASTA</h5>
    @component('Components/inputFecha',['attrs' => 'name="fecha_hasta" data-js-cambio-eliminar-forzar'])
    @endcomponent
  </div>
  <div class="col-md-4">
    <h5>MÁQUINAS</h5>
    <div class="input-group number-spinner">
      <span class="input-group-btn">
        <button data-js-deshabilitar-sin-tipo style="border: 1px solid #ccc;" class="btn btn-default" data-dir="dwn">-</button>
      </span>
      <input data-js-deshabilitar-sin-tipo name="cantidad_maquinas" type="text" class="form-control text-center" value="1">
      <span class="input-group-btn">
        <button data-js-deshabilitar-sin-tipo style="border: 1px solid #ccc;" class="btn btn-default" data-dir="up">+</button>
      </span>
    </div>
  </div>
</div>
<br>
<br>
<!-- DETALLES DE LAS MÁQUINAS POR DEFAULT Y TEMPORALES -->
<div data-js-detalles-sector class="row">
  <div class="col-md-12">
    <h5 style="color:#333 !important;font-family:Roboto-Condensed;font-size:20px;font-weight:700;">DETALLES PARA EL SECTOR SELECCIONADO</h5>
    <br>
    <div class="row">
      <div class="col-xs-6 col-md-4">
        <h5>MÁQUINAS POR DEFECTO</h5>
      </div>
      <div class="col-xs-6 col-md-4">
        <span data-js-maquinas-por-defecto class="badge" style="background-color: #6dc7be;font-family:Roboto-Regular;font-size:18px;margin-top:5px;">15</span>
      </div>
    </div> <!-- /.row -->
    <br>
    <div data-js-maquinas-temporales class="row">
      <div class="col-md-12" style="height: 350px; overflow-y: scroll;">
        <table class="table">
          <thead>
            <th>DESDE FECHA</th>
            <th>HASTA FECHA</th>
            <th>CANTIDAD DE MÁQUINAS</th>
            <th>ACCIÓN</th>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
    <table hidden>
      <tr data-js-molde-maquinas-por-relevamiento>
        <td class="fecha_desde">99 Mes 1999</td>
        <td class="fecha_hasta">99 Mes 2999</td>
        <td>
          <span class="badge cantidad" style="background-color: rgb(109, 199, 190); font-family: Roboto-Regular; font-size: 18px;">
            999
          </span>
        </td>
        <td>
          <button type="button" class="btn btn-danger" data-js-click-borrar-fila>
            <i class="fa fa-fw fa-trash"></i>
          </button>
        </td>
      </tr>
    </table>
  </div>
</form>
@endslot

@slot('pie')
<p style="color:red;" id="mensajeTemporal" data-js-forzando-carga="1">LAS FECHAS ELEGIDAS PISAN A OTRAS TEMPORALES DEFINIDAS ANTERIORMENTE</p>
<button type="button" class="btn btn-successAceptar" data-js-generar='forzar' data-js-forzando-carga="1">GENERAR IGUAL</button>
<button type="button" class="btn btn-default" data-js-cancelar data-js-forzando-carga="1">CANCELAR CARGA TEMPORAL</button>
<button type="button" class="btn btn-successAceptar" data-js-generar='no forzar' data-js-forzando-carga="0">GENERAR</button>
@endslot

@endcomponent
