<?php $uid = uniqid(); ?>
<div id="{{$uid}}" data-js-generar-plantilla>
  @component('CierresAperturas/modal',[
    'clases_modal' => 'generarAperturas',
    'attrs_modal' => 'data-js-generar-plantilla-modal',
    'estilo_cabecera' => 'background-color:#1DE9B6;'
  ])
    @slot('titulo')
      GENERAR RELEVAMIENTO
    @endslot
    @slot('cuerpo')
    <div class="row">
      <div class="col-md-6">
        <h5>Seleccione el casino</h5>
        <select name="id_casino" class="form-control">
          <option value="">- Seleccione un casino -</option>
          @foreach($casinos as $c)
          <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-6">
        <h5>&nbsp;</h5>
        <button data-js-generar-aperturas type="button" class="btn btn-success" style="font-family: Roboto-Condensed;">GENERAR</button>
      </div>
    </div>
    <br>
    <div class="row" data-js-generar-spinner style="text-align: center;" hidden>
      <i class="fa fa-spinner fa-spin" style="font-size:4em;" alt="Cargando"></i>
      <br>
      <h6>Un momento, por favor...</h6>
    </div>
    @endslot
  @endcomponent
  
  @component('CierresAperturas/modal',[
    'clases_modal' => 'reintenteAperturas',
    'attrs_modal' => 'data-js-reintente',
    'estilo_cabecera' => 'background-color:#0D47A1;'
  ])
    @slot('titulo')
      AVISO
    @endslot
    @slot('cuerpo')
    <div class="row" style="text-align: center;">
      <h6 class="mensaje">Por favor reintente en 15 minutos...</h6>
    </div>
    @endslot
  @endcomponent
</div>

