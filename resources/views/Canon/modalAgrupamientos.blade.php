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
  <div style="width: 100%;display: flex;align-items: center;justify-content: flex-end;padding-bottom: 1em;" data-modo-mostrar='[{"modo": "VER"}]'>
    <h5 style="width: 15rem;">Version</h5>
    <select class="form-control" data-js-select-historial style="width: 15rem;">
    </select>
  </div>
  <form class="pestaña" style="display: flex;flex-direction: column;width: 100%;height: 70vh;overflow-y: scroll;" action="/canon/grupo_operador/guardar" method="POST">
    <div hidden>
      <input name="id_canon_subcanon_a_grupo" class="form-control" data-readonly='[{"modo":"*"}]'>
    </div>
    <div class="bloque_interno" style="width: 100%;height: 20vh;display: flex;flex-direction: row;">
      <div style="flex: 1;">
        <h6>Datos</h6>
        <div class="bloque_interno" style="height: 100%;display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;gap: 10px;">
          <div style="width: 25%;">
            <h5 style="padding-left: 0;">Clave</h5>
            <input name="clave" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "*"}]'>
          </div>
          <div style="width: 25%;">
            <h5 style="padding-left: 0;">Grupo Operador</h5>
            <input name="grupo_operador" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "*"}]'>
          </div>
          <div style="width: 25%;">
            <h5 style="padding-left: 0;">ID Grupo Operador</h5>
            <input name="id_grupo_operador" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "*"}]'>
          </div>
        </div>
      </div>
    </div>
    <div class="bloque_interno" style="width: 100%;height: 50vh;display: flex;">
      <div data-grafo-agrupamiento style="width: 65%;height: 100%;border: 2px solid darkblue;margin: 5px;">
      </div>
      <div style="width: 35%;height: 100%;display: flex;flex-direction: column;">
        <h6>Controles</h6>
        <div class="bloque_interno" style="height: 100%;display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;gap: 5px;">
          <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="width: 25%;" data-js-click-agregar-nodo class="btn" type="button">Agregar</button>
            <input  style="width: 50%;" data-nuevo-nodo="label" class="form-control" data-js-texto-no-formatear-numero placeholder="Nombre">
            <select data-nuevo-nodo="group" class="form-control">
              <option default selected value="superior">Nivel Superior</option>
              <option value="base:canon_variable">Subcanon - Canon Variable</option>
              <option value="base:canon_fijo_mesas">Subcanon - Canon Fijo Mesas</option>
              <option value="base:canon_fijo_mesas_adicionales">Subcanon - Canon Fijo Mesas Adicionales</option>
            </select>
          </div>
          <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="width: 25%;" data-js-click-enlazar-nodo="[data-enlazar-nodo-id]" class="btn" type="button">Enlazar</button>
            <input  style="width: 25%;" data-enlazar-nodo-id="desde" class="form-control" data-js-texto-no-formatear-numero placeholder="Desde">
            <input  style="width: 25%;" data-enlazar-nodo-id="hasta" class="form-control" data-js-texto-no-formatear-numero placeholder="Hasta">
          </div>
          <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;align-content: stretch;gap: 5px;">
            <button style="width: 25%;" data-js-click-borrar-objetos class="btn" type="button">Borrar</button>
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
