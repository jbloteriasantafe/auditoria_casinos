<!-- Modal Relevamientos -->
@component('Components/modal',[
  'clases_modal' => 'modalRelevamiento',
  'attrs_modal' => 'data-js-modal-generar-relevamiento',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'salir' => '',
])

@slot('titulo')
| NUEVO RELEVAMIENTO
@endslot

@slot('cuerpo')
<form data-existe-relevamiento="-1,0,1,2" data-paso="0" class="form-horizontal" novalidate=""  hidden>
  <div class="row">
    <div class="col-md-12">
      <h5>FECHA DE RELEVAMIENTO</h5>
      <input type='text' class="form-control" disabled style="text-align:center;" value="{{strftime('%A, %d de %B de %Y')}}">
      <input data-js-fecha-hoy type="text" value="{{date('Y-m-d')}}" hidden>
      <br>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <h5>CASINO</h5>
      <select name="id_casino" class="form-control" data-js-cambio-casino-select-sectores="[data-js-modal-generar-relevamiento] [data-js-poner-sectores]">
        <option value="">- Seleccione un casino -</option>
         @foreach ($casinos as $casino)
         <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
         @endforeach
      </select>
    </div>
    <div class="col-md-6">
      <h5>SECTOR</h5>
      <select name="id_sector" class="form-control" data-js-poner-sectores data-js-cambio-sector>
      </select>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-md-6">
      <h5>MÁQUINAS</h5>
      <input name="cantidad_maquinas" type="text" class="form-control" value="" disabled>
    </div>
    <div class="col-md-6">
      <h5>FISCALIZADORES</h5>
      <div class="input-group number-spinner">
        <span class="input-group-btn">
          <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="dwn">-</button>
        </span>
        <input name="cantidad_fiscalizadores" type="text" class="form-control text-center" value="1">
        <span class="input-group-btn">
          <button style="border: 1px solid #ccc;" class="btn btn-default" data-dir="up">+</button>
        </span>
      </div>
    </div>
  </div>
  @if($es_superusuario)
  <br>
  <div class="row">
    <div class="col-md-6">
      <h5>SEMILLA</h5>
      <input name="seed" type="number" class="form-control">
    </div>
  </div>
  @endif
  <br>
  <div data-js-maquinas-a-pedido class="row">
    <div class="col-md-12">
      <h5>MÁQUINAS A PEDIDO</h5>
      <span style="font-family:Roboto-Regular;font-size:16px;" data-js-maquinas-a-pedido-cantidad>El sector elegido tiene N máquinas a pedido</span>
    </div>
  </div>
</form>

<div data-existe-relevamiento="0" data-paso="1" class="sk-folding-cube" hidden>
  <div class="sk-cube1 sk-cube"></div>
  <div class="sk-cube2 sk-cube"></div>
  <div class="sk-cube4 sk-cube"></div>
  <div class="sk-cube3 sk-cube"></div>
</div>

<div data-existe-relevamiento="1" data-paso="1" hidden>
  <p style="font-family:Roboto-Regular;font-size:16px;margin:20px 0px;">
    Si vuelve a generar el relevamiento se sobreescribirán los datos anteriores y se perderán las planillas de relevamiento generadas anteriormente.
  </p>
  <p style="font-family:Roboto-Regular;font-size:16px;margin-bottom:20px;">
    ¿Desea generar el relevamiento de todas formas?
  </p>
</div>
<div data-existe-relevamiento="1" data-paso="2" class="sk-folding-cube" hidden>
  <div class="sk-cube1 sk-cube"></div>
  <div class="sk-cube2 sk-cube"></div>
  <div class="sk-cube4 sk-cube"></div>
  <div class="sk-cube3 sk-cube"></div>
</div>

<div data-existe-relevamiento="2" data-paso="1" hidden>
  <h5 style="padding:0px;font-family:Roboto-Condensed;color:#444 !important;font-size:20px;">NO SE PUEDE GENERAR EL RELEVAMIENTO</h5>
  <p style="font-family:Roboto-Regular;font-size:16px;margin:20px 0px;">
    El sector seleccionado ya se está relevando.
  </p>
</div>
@endslot

@slot('pie')


{{-- Para mostrar errores si no manda nada --}}
<div data-existe-relevamiento="-1" data-paso="0" hidden> 
  <button type="button" class="btn btn-successAceptar" data-js-generar-posta>GENERAR</button>
  <button type="button" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
</div>

<div data-existe-relevamiento="0" data-paso="0" hidden>
  <button type="button" class="btn btn-successAceptar" data-js-generar-posta>GENERAR</button>
  <button type="button" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
</div>
<div data-existe-relevamiento="0" data-paso="1" hidden>
</div>

<div data-existe-relevamiento="1,2" data-paso="0" hidden>
  <button type="button" class="btn btn-successAceptar" data-js-pasar-paso>GENERAR</button>
  <button type="button" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
</div>

<div data-existe-relevamiento="1" data-paso="1" hidden>
  <button type="button" class="btn btn-warningModificar" data-js-generar-posta>REGENERAR</button>
  <button type="button" class="btn btn-successAceptar" data-js-cancelar>CANCELAR</button>
  <button type="button" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
</div>

<div data-existe-relevamiento="2" data-paso="1" hidden>
  <button type="button" class="btn btn-successAceptar" data-js-cancelar>CANCELAR</button>
  <button type="button" class="btn btn-default btn-salir" data-js-salir data-dismiss="modal">SALIR</button>
</div>
@endslot

@endcomponent
