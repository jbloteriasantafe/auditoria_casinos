@component('Components/include_guard',['nombre' => 'moldeCanonArchivo'])
<style>
  .VerCargarCanon .grid_fila_archivo {
    display: grid; 
    grid-template-columns: 20fr 20fr 1fr;
    grid-template-rows: 1fr; 
    gap: 0px 0px; 
    grid-template-areas: 
      "grid_descripcion grid_archivo grid_boton"; 
  }
  .VerCargarCanon .grid_fila_archivo > .grid_descripcion { grid-area: grid_descripcion; }
  .VerCargarCanon .grid_fila_archivo > .grid_archivo { grid-area: grid_archivo; }
  .VerCargarCanon .grid_fila_archivo > .grid_boton { grid-area: grid_boton; }
</style>
@endcomponent

@if($header ?? false)
<div class="grid_fila_archivo" style="width: 100%;">
  <div class="grid_descripcion">
    <h5>DESCRIPCIÓN</h5>
  </div>
  <div class="grid_nombre_archivo">
    <h5>NOMBRE ARCHIVO</h5>
  </div>
  <div class="grid_boton">
    <h5>&nbsp;</h5>
  </div>
</div>
@else
<div data-subcanon="canon_archivo" class="grid_fila_archivo" style="width: 100%;" data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]'>
  <div class="grid_descripcion">
    <input data-js-texto-no-formatear-numero class="form-control" placeholder="DESCRIPCIÓN" style="text-align: left;" data-descripcion>
  </div>
  <div class="grid_nombre_archivo">
    <input data-js-texto-no-formatear-numero class="form-control" type="file" style="text-align: center;" data-archivo>
  </div>
  <div class="grid_boton">
    <button class="btn" type="button" data-js-agregar-archivo data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]'><i class="fa fa-plus"></i></button>
  </div>
</div>
<?php
  $molde_str = '$adj';
  $n = function($s) use (&$id_casino,&$t,&$molde_str){
    return "canon_archivo[$molde_str][$s]";
  };
  $descripcion = $n('descripcion');
  $nombre_archivo = $n('nombre_archivo');
  $id_archivo = $n('id_archivo');
  $archivo = $n('archivo');
  $link = $n('link');
?>
<div data-subcanon="canon_archivo" data-js-molde="{{$molde_str}}" style="width: 100%;">
  <div class="grid_fila_archivo" style="width: 100%;">
    <div class="grid_descripcion">
      <input data-js-texto-no-formatear-numero style="width: 100%;text-align: left;" class="form-control" data-name="{{$descripcion}}" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"}]'>
    </div>
    <div class="grid_nombre_archivo">
      <input data-js-texto-no-formatear-numero data-js-click-abrir-val-hermano="[data-es-link]" style="width: 100%;text-align: center;cursor: pointer;" class="form-control" data-name="{{$nombre_archivo}}" data-depende="id_casino,año_mes" data-readonly='[{"modo":"*"}]'>
      <input data-js-texto-no-formatear-numero data-es-link data-name="{{$link}}" hidden>
    </div>
    <div hidden>
      <input data-js-texto-no-formatear-numero style="flex: 1;" class="form-control" data-name="{{$id_archivo}}" data-depende="id_casino,año_mes" data-readonly='[{"modo":"*"}]'>
    </div>
    <div class="grid_boton">
      <button class="btn" type="button" data-js-borrar-archivo data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'><i class="fa fa-fw fa-trash-alt"></i></button>
    </div>
  </div>
</div>
@endif
