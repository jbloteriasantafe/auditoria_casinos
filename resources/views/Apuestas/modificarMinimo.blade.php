@component('Components/include_guard',['nombre' => 'modificarMinimo'])
<style>
  
</style>
@endcomponent

@component('Components/modal',[
  'clases_modal' => 'modificarMinimo',
  'attrs_modal' => 'data-js-modificar-minimo',
  'estilo_cabecera' => 'background-color:#FFA726;',
  'grande' => 40,
])
  @slot('titulo')
  REQUERIMIENTOS VALOR MÍNIMO DE APUESTAS
  @endslot
  @slot('cuerpo')
  <div class="row">
    <div class="col-xs-6">
      <h5>CASINO</h5>
      <select data-js-cambio-casino name="id_casino" class="form-control">
        @foreach ($casinos as $cas)
        <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-xs-6">
      <h5>MONEDA</h5>
      <select data-js-cambio-moneda name="id_moneda" class="form-control">
        @foreach ($monedas as $m)
        <option value="{{$m->id_moneda}}">{{$m->siglas}}</option>
        @endforeach
      </select>
    </div>
  </div>
  <br>
  <div class="row valores">
    <h6 style="margin-left: 50px;font-size:17px;text-align:center !important;" id="req">MODIFICACIONES:</h6>
    <div class="row">
      <div class="col-xs-12">
        <div class="row">
          <div class="col-xs-4">
            <h6 style="font-size:16px">Juego:</h6>
          </div>
          <div class="col-xs-8">
            <select data-js-cambio-juego name="id_juego_mesa" class="form-control">
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-xs-4">
            <h6 style="font-size:16px">Apuesta Mínima:</h6>
          </div>
          <div class="col-xs-8">
            <input name="apuesta_minima" type="text" class="form-control">
          </div>
        </div>
        <div class="row">
          <div class="col-xs-4">
            <h6 style="font-size:16px">Cantidad de Mesas Abiertas:</h6>
          </div>
          <div class="col-xs-8">
            <input name="cantidad_requerida" type="text" class="form-control">
          </div>
        </div>
      </div>
    </div>
  </div>
  @endslot
  @slot('pie')
  <span style="font-family:sans-serif;float:left !important;font-size:12px; text-align:left; color:#0D47A1"> Pista: si desea modificar más de una apuesta mínima, guarde cada cambio de forma <br>individual. Caso contrario, solo será considerado el último cambio introducido.</span>
  <button data-js-guardar type="button" class="btn btn-warningModificar">GUARDAR</button>
  @endslot
@endcomponent
