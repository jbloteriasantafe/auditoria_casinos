@component('Components/include_guard',['nombre' => 'moldeCanonPago'])
<style>
  .VerCargarCanon .grid_fila_pago {
    display: grid; 
    grid-template-columns: 0.7fr 1fr 0.7fr 0.7fr 0.7fr 0.7fr 0.7fr 0.7fr 0.1fr;
    grid-template-rows: 1fr;
    gap: 0px 0px; 
    grid-template-areas: 
      "grid_capital grid_fecha_pago grid_dias_vencidos grid_mora_provincial grid_mora_nacional grid_a_pagar grid_pago grid_diferencia grid_borrar";
  }
  .VerCargarCanon .grid_fila_pago > .grid_capital { grid-area: grid_capital; }
  .VerCargarCanon .grid_fila_pago > .grid_fecha_pago { grid-area: grid_fecha_pago; }
  .VerCargarCanon .grid_fila_pago > .grid_dias_vencidos { grid-area: grid_dias_vencidos; }
  .VerCargarCanon .grid_fila_pago > .grid_mora_provincial { grid-area: grid_mora_provincial; }
  .VerCargarCanon .grid_fila_pago > .grid_mora_nacional { grid-area: grid_mora_nacional; }
  .VerCargarCanon .grid_fila_pago > .grid_a_pagar { grid-area: grid_a_pagar; }
  .VerCargarCanon .grid_fila_pago > .grid_pago { grid-area: grid_pago; }
  .VerCargarCanon .grid_fila_pago > .grid_diferencia { grid-area: grid_diferencia; }
  .VerCargarCanon .grid_fila_pago > .grid_borrar { grid-area: grid_borrar; }
  .VerCargarCanon .grid_fila_pago > div h5 {
    padding: 0px;
  }
  .VerCargarCanon .grid_fila_pago > div input {
    padding: 1px;
  }
  .VerCargarCanon .grid_fila_pago [data-js-fecha] span {
    padding: 6px;
    font-size: 0.7em;
  }
</style>
@endcomponent

@if($header ?? false)
<div class="grid_fila_pago" style="width: 100%;">
  <div class="grid_capital">
    <h5>Capital</h5>
  </div>
  <div class="grid_fecha_pago">
    <h5>F. Pago</h5>
  </div>
  <div class="grid_dias_vencidos">
    <h5>Dias vencidos</h5>
  </div>
  <div class="grid_mora_provincial">
    <h5>Mora Provincial</h5>
  </div>
  <div class="grid_mora_nacional">
    <h5>Mora Nacional</h5>
  </div>
  <div class="grid_a_pagar">
    <h5>A PAGAR</h5>
  </div>
  <div class="grid_pago">
    <h5>PAGO</h5>
  </div>
  <div class="grid_diferencia">
    <h5>Diferencia</h5>
  </div>
  <div class="grid_borrar">
    <h5>&nbsp;</h5>
  </div>
</div>
@else
<button class="btn" type="button" data-js-agregar-pago data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]' style="display: inline-block;">
  <i class="fa fa-plus"></i>
</button>
<?php
  $molde_str = '$'.uniqid();
  foreach([
    'id_canon_pago',
    'capital','fecha_pago','dias_vencidos',
    'mora_provincial','mora_nacional',
    'a_pagar','pago','diferencia'
  ] as $varname){
    $$varname =  "canon_pago[$molde_str][$varname]";
  }
?>
<div data-subcanon="canon_pago" data-js-molde="{{$molde_str}}" style="width: 100%;">
  <div class="grid_fila_pago" style="width: 100%;">
    <input data-name="{{$id_canon_pago}}" data-modo-mostrar='[]'>
    <div class="grid_capital valor_intermedio">
      <input class="form-control" data-name="{{$capital}}" data-readonly='[{"modo":"*"}]'>
    </div>
    <div class="grid_fecha_pago">
      @component('Components.inputFecha',[
        'attrs' => "data-js-texto-no-formatear-numero data-name='$fecha_pago' data-depende='año_mes'",
        'attrs_dtp' => 'data-picker-position="top-right"',
        'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"'
      ])
      @endcomponent
    </div>
    <div class="grid_dias_vencidos valor_intermedio">
      <input class="form-control" data-name="{{$dias_vencidos}}" data-depende="fecha_vencimiento,{{$fecha_pago}}" data-readonly='[{"modo":"*"}]'>
    </div>
    <div class="grid_mora_provincial valor_intermedio">
      <input class="form-control" data-name="{{$mora_provincial}}" data-depende="{{$dias_vencidos}},tasa_provincial_diaria_simple" data-readonly='[{"modo":"*"}]'>
    </div>
    <div class="grid_mora_nacional valor_intermedio">
      <input class="form-control" data-name="{{$mora_nacional}}" data-depende="{{$dias_vencidos}},tasa_nacional_mensual_compuesta" data-readonly='[{"modo":"*"}]'>
    </div>
    <div class="grid_a_pagar">
      <input class="form-control" data-name="{{$a_pagar}}" data-readonly='[{"modo":"*"}]' data-depende="{{$mora_provincial}},{{$mora_nacional}},{{$capital}}">
    </div>
    <div class="grid_pago">
      <input class="form-control" data-name="{{$pago}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' data-depende="año_mes,id_casino">
    </div>
    <div class="grid_diferencia">
      <input class="form-control" data-name="{{$diferencia}}" data-depende="{{$a_pagar}},{{$pago}}" data-readonly='[{"modo":"*"}]'>
    </div>
    <div class="grid_borrar">
      <button class="btn" type="button" data-js-borrar-pago data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'><i class="fa fa-fw fa-trash-alt"></i></button>
    </div>
  </div>
</div>
@endif
