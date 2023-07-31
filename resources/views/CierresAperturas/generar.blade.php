<?php $uid = uniqid(); ?>
<div id="{{$uid}}" data-js-generar-plantilla>
  @component('CierresAperturas/modal',[
    'attrs_modal' => 'data-js-generar-plantilla-modal',
    'estilo_cabecera' => 'background-color:#1DE9B6;'
  ])
    @slot('titulo')
      GENERANDO RELEVAMIENTO
    @endslot
    @slot('cuerpo')
    <div class="loading" style="text-align: center;">
      <i class="fa fa-spinner fa-spin" style="font-size:4em;" alt="Cargando"></i>
      <br>
      <h6>Un momento, por favor...</h6>
    </div>
    @endslot
    @slot('salir')
    @endslot
  @endcomponent
  
  @component('CierresAperturas/modal',[
    'attrs_modal' => 'data-js-reintente',
    'estilo_cabecera' => 'background-color:#0D47A1;'
  ])
    @slot('titulo')
      AVISO
    @endslot
    @slot('cuerpo')
    <div class="row" style="text-align: center;">
      <h6>'Por favor reintente en 15 minutos...'</h6>
      <h6>GRACIAS</h6>
    </div>
    @endslot
  @endcomponent
</div>

