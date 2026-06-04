<!-- ************MODAL PARA VER / AGREGAR OBSERVACIONES DE EVENTUALIDAD *********************
     Compartido entre la tabla de eventualidades (index) y el reporte diario (resumen_diario).
     Lo maneja public/js/eventualidades/observaciones_eventualidad.js -->
<div class="modal fade" id="modalVerObservaciones" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modalNuevo">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <h3 class="modal-title">| OBSERVACIONES DE EVENTUALIDAD <small id="obsEvCabecera" class="text-white"></small></h3>
      </div>
      <div class="modal-body modalCuerpo">
        <ul id="listaObsEv" class="list-group" style="max-height:300px; overflow:auto;" data-ev-id=""></ul>
        <hr>
        <h5>Agregar observación</h5>
        <textarea id="obsEvTexto" class="form-control" rows="3" maxlength="3999" placeholder="Escribí la observación (opcional si vas a subir un archivo)..."></textarea>
        <div class="input-group" style="margin-top:8px;">
          <span class="input-group-btn">
            <button class="btn" type="button" onclick="$('#obsEvFile').click()"
              style="background:#0067b1; color:#fff; border:1px solid #005a99;">
              <i class="fa fa-paperclip"></i> Adjuntar archivo
            </button>
          </span>
          <input type="text" id="obsEvFileName" class="form-control" placeholder="Sin archivos" readonly>
          <input type="file" id="obsEvFile" multiple style="display:none;">
        </div>
        <div class="text-right" style="margin-top:8px;">
          <button type="button" id="btnGuardarObsEv" class="btn btn-infoBuscar">
            <i class="fa fa-save"></i> GUARDAR OBSERVACIÓN
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">CERRAR</button>
      </div>
    </div>
  </div>
</div>

{{-- Modal de confirmación de borrado de observación (compartido: obs de eventualidad y de resumen).
     Se abre por ENCIMA del modal de observaciones, de ahí el z-index alto. Lo dispara
     observaciones_eventualidad.js (pedirEliminarObservacion). --}}
<style>
  #modalEliminarObservacion.modal { z-index: 2050 !important; }
  #modalEliminarObservacion + .modal-backdrop { z-index: 2040 !important; }
</style>
<div class="modal fade" id="modalEliminarObservacion" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
      </div>
      <div class="modal-body franjaRojaModal">
        <div class="form-group error">
          <div class="col-xs-12">
            <strong id="titulo-modal-eliminar-obs">¿Seguro que querés eliminar esta observación?</strong>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarObservacion">ELIMINAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
      </div>
    </div>
  </div>
</div>
