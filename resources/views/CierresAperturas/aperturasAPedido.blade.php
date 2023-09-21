@component('Components/modal',[
  'clases_modal' => 'aperturaAPedido',
  'attrs_modal' => 'data-js-apertura-a-pedido',
  'estilo_cabecera' => 'background-color:#4AA89F;',
  'grande' => 80,
])
  @slot('titulo')
  APERTURAS A PEDIDO
  @endslot
  @slot('cuerpo')
  <div class="row">
    <div class="col-xs-2">
      <h5>JUEGO</h5>
      <select class="form-control" data-js-juego>
        @foreach ($juegos as $j)
        <option value="{{$j->id_juego_mesa}}" data-siglas="{{$j->siglas}}" data-casino="{{$j->casino->nombre}}">
          {{$j->nombre_juego}} - {{$j->casino->codigo}}
        </option>
        @endforeach
      </select>
    </div>
    <div class="col-xs-2">
      <h5>MESA</h5>
      <input class="form-control" data-js-mesa name="id_mesa_de_panio" data-js-formdata-attr="data-elemento-seleccionado" placeholder="Número de mesa"/>
    </div>
    <div class="col-xs-2">
      <h5>F. INICIO</h5>
      <div class="form-group">
        @component('Components/inputFecha',['attrs' => 'name="fecha_inicio"','placeholder' => 'Fecha inicio'])
        @endcomponent
      </div>
    </div>
    <div class="col-xs-2">
      <h5>F. FIN</h5>
      <div class="form-group">
        @component('Components/inputFecha',['attrs' => 'name="fecha_fin"','placeholder' => 'Fecha fin'])
        @endcomponent
      </div>
    </div>
    <div class="col-xs-2">
      <h5>&nbsp;</h5>
      <button data-js-agregar class="btn btn-success" type="button">
        <i class="fa fa-plus"></i>
      </button>
    </div>
  </div>
  <hr>
  <div class="row" style="max-height: 450px;overflow-y: scroll;">
    <table class="table" data-js-tabla>
      <thead>
        <tr>
          <th class="col-md-2" style="text-align:center;">CASINO</th>
          <th class="col-md-2" style="text-align:center;">MONEDA</th>
          <th class="col-md-2" style="text-align:center;">JUEGO</th>
          <th class="col-md-2" style="text-align:center;">MESA</th>
          <th class="col-md-2" style="text-align:center;">FECHA INICIO</th>
          <th class="col-md-2" style="text-align:center;">FECHA FIN</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
    <table hidden>
      <tr data-js-molde>
        <td class="casino" style="text-align:center;">CASINO</td>
        <td class="moneda" style="text-align:center;">ARS/USD/MULTIMONEDA</td>
        <td class="juego" style="text-align:center;">RA/CR/MJ/ETC</td>
        <td class="nro_mesa" style="text-align:center;">1234</td>
        <td class="fecha_inicio" style="text-align:center;">9999-99-99</td>
        <td class="fecha_fin" style="text-align:center;">9999-99-99</td>
        <td style="text-align:center;">
          <button type="button" class="btn btn-success" data-js-eliminar-aap>
            <i class="fa fa-fw fa-trash"></i>
          </button>
        </td>
      </tr>
    </table>
  </div>
  @endslot
@endcomponent
