<style>
  .EditarAgrupamiento select[name="id_grupo_operador"] {
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
  }
  
  .EditarAgrupamiento select[name="id_grupo_operador"] option[data-css-nodos="0"]:checked {
    color: grey !important;
  }
</style>

@component('Components/modal',[
  'clases_modal' => 'VerCargarCanon EditarAgrupamiento',
  'attrs_modal' => 'data-js-modal-canon-comportamiento-comun data-js-modal-editar-agrupamiento data-modo=\'NUEVO\' ',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 70,
])
  @slot('titulo')
  AGRUPAMIENTO
  @endslot
  @slot('cuerpo')
  <!-- <div style="width: 100%;display: flex;align-items: center;justify-content: flex-end;padding-bottom: 1em;" data-modo-mostrar='[{"modo": "VER"}]'>
    <h5 style="width: 15rem;">Version</h5>
    <select class="form-control" data-js-select-historial style="width: 15rem;">
    </select>
  </div> -->
  <form class="pestaña" style="display: flex;flex-direction: column;width: 100%;height: 70vh;overflow-y: scroll;" action="/canon/grupo_operador/guardar" method="POST">
    <div class="bloque_interno" style="width: 100%;display: flex;flex-direction: row;">
      <div style="flex: 1;">
        <div class="bloque_interno" style="height: 100%;display: flex;flex-direction: row;justify-content: center;align-items: center;align-content: stretch;gap: 10px;">
          <h5>Clave</h5>
          <input style="width: 20em;" name="clave" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "*"}]'>
          <h5>Grupo Operador</h5>
          <div data-contenedor-grupos-operadores style="display: flex;flex-wrap: wrap;align-items: center;align-content: flex-start;">
          </div>
        </div>
      </div>
    </div>
    <div hidden>
      <label style="width: 15em;" data-molde-grupo-operador>
        <input type="radio" data-radio-grupo-operador="">
        <span style="font-weight: normal;" data-radio-grupo-operador-label>99999999999</span>
      </label>
    </div>
    <div class="bloque_interno" style="width: 100%;height: 50vh;display: flex;">
      <div data-grafo-agrupamiento style="flex: 0.65;flex-grow: 1;height: 100%;border: 2px solid darkblue;margin: 5px;">
      </div>
      <div style="flex: 0.35;height: 100%;display: flex;flex-direction: column;" data-modo-mostrar='[{"modo": "NUEVO","modo": "EDITAR"}]'>
        <h6>Controles</h6>
        <div class="bloque_interno" style="height: 100%;display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;gap: 5px;">
          <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="flex: 1;padding: 0;" data-js-click-centrar class="btn" type="button">Centrar</button>
          </div>
          <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="flex: 1;padding: 0;" data-js-click-organizar class="btn" type="button">Organizar</button>
          </div>
          
          <div data-agregar-nodo style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="flex: 0.25;padding: 0;" data-js-click-agregar-grupo-operador class="btn" type="button">Agregar</button>
            <input  style="flex: 0.75;padding: 0;" data-nuevo-grupo-operador class="form-control" data-js-texto-no-formatear-numero data-js-keypress-solo-numeros placeholder="Grupo Operador">
          </div>
          <div data-agregar-nodo style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="flex: 0.25;padding: 0;"  data-js-click-agregar-nodo class="btn" type="button">Agregar</button>
            <input  style="flex: 0.75;padding: 0;" data-nuevo-nodo="label" class="form-control" data-js-texto-no-formatear-numero placeholder="Superior">
            <input style="width: 50%;" data-nuevo-nodo="group" value="superior" class="form-control" data-js-texto-no-formatear-numero data-modo-mostrar='[{"modo": "NUNCA!"}]'>
          </div>
          @foreach($subcanons as $sc)
          <div data-agregar-nodo style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="flex: 0.25;padding: 0;"  data-js-click-agregar-nodo class="btn" type="button">Agregar</button>
            <input style="flex: 0.75;padding: 0;"  data-nuevo-nodo="label" class="form-control" data-js-texto-no-formatear-numero placeholder="{{$sc}}">
            <input style="width: 50%;" data-nuevo-nodo="group" value="base:{{$sc}}" class="form-control" data-js-texto-no-formatear-numero data-modo-mostrar='[{"modo": "NUNCA!"}]'>
          </div>
          @endforeach
          <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="flex: 0.25;padding: 0;" data-js-click-enlazar-nodo="[data-enlazar-nodo-id]" class="btn" type="button">Enlazar</button>
            <input  style="flex: 0.375;padding: 0;" data-enlazar-nodo-id="desde" class="form-control" data-js-texto-no-formatear-numero placeholder="Desde">
            <input  style="flex: 0.375;padding: 0;" data-enlazar-nodo-id="hasta" class="form-control" data-js-texto-no-formatear-numero placeholder="Hasta">
          </div>
          <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="flex: 0.25;padding: 0;" data-js-click-borrar-objetos class="btn" type="button">Borrar</button>
          </div>
          <br>
        </div>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
  <button class="btn btn-successAceptar" type="button" data-js-click-submit-form="form" data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>GUARDAR</button>
  @endslot
@endcomponent
