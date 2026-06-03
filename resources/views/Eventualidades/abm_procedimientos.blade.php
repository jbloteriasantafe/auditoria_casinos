{{-- Partial: incluir en una vista que ya extienda dashboard. Renderiza 3 modales:
     #modalGestionProcedimientos (lista), #modalProcedimiento (form), #modalEliminarProcedimiento.
     La data se carga por AJAX al abrir el modal principal. --}}

<style>
  /* Botón "nuevo" con la identidad teal del sistema, pero apto para modal (no el tile grande). */
  .btn-nuevoProc {
    background: #fff;
    border: 2px solid #4DB6AC;
    color: #4DB6AC;
    border-radius: 0;
    font-family: "Roboto-Black";
    font-size: 13px;
    padding: 6px 14px;
    cursor: pointer;
  }
  .btn-nuevoProc:hover, .btn-nuevoProc:focus {
    background: #4DB6AC;
    color: #fff;
  }
  .btn-nuevoProc i { margin-right: 5px; }

  /* Fila de casino con su switch de activo en el form. */
  .abm-casino-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 4px;
    border-bottom: 1px solid #eee;
  }
  .abm-casino-row .abm-casino-nombre { font-family: Roboto-Regular; }
  .abm-casino-estado { font-size: 12px; color: #999; margin-right: 8px; }
</style>

<!-- Modal principal: lista de procedimientos -->
<div class="modal fade" id="modalGestionProcedimientos" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modalNuevo">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <h3 class="modal-title">| GESTIÓN DE PROCEDIMIENTOS</h3>
      </div>
      <div class="modal-body modalCuerpo">
        <div class="row" style="margin-bottom:10px;">
          <div class="col-md-12 text-right">
            <button type="button" class="btn-nuevoProc" id="btnNuevoProcedimiento">
              <i class="fa fa-plus"></i> NUEVO PROCEDIMIENTO
            </button>
          </div>
        </div>
        <table class="table table-bordered table-sm" id="tablaABMProcedimientos">
          <thead class="thead-light">
            <tr>
              <th>NOMBRE</th>
              <th style="width:70px;">ORDEN</th>
              <th style="width:150px;">ACTIVO EN</th>
              <th style="width:110px;">ACCIÓN</th>
            </tr>
          </thead>
          <tbody id="tbodyABMProcedimientos">
            <tr><td colspan="4" class="text-center text-muted">Cargando…</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">CERRAR</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Procedimiento (form nuevo/modificar) -->
<div class="modal fade" id="modalProcedimiento" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modalNuevo">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <h3 class="modal-title" id="tituloModalProcedimiento">| NUEVO PROCEDIMIENTO</h3>
      </div>
      <div class="modal-body modalCuerpo">
        <form id="frmProcedimiento" novalidate>
          <div class="row">
            <div class="col-md-12">
              <h5>NOMBRE</h5>
              <input type="text" class="form-control" id="abmNombre" name="nombre" maxlength="150" placeholder="Nombre del procedimiento">
              <br><span id="abmAlertaNombre" class="alertaSpan"></span>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <h5>UBICACIÓN EN LA LISTA</h5>
              <div style="display:flex; gap:8px; align-items:center; flex-wrap:nowrap;">
                <select class="form-control" id="abmPosicion" style="width:auto; min-width:130px;">
                  <option value="despues">Después de</option>
                  <option value="antes">Antes de</option>
                </select>
                <select class="form-control" id="abmRef" style="flex:1;"></select>
              </div>
              <small class="text-muted">Elegí dónde ubicarlo respecto de otro procedimiento (sin pensar en números).</small>
            </div>
          </div>
          <div class="row" style="margin-top:6px;">
            <div class="col-md-12">
              <h5>ACTIVO POR CASINO</h5>
              <div id="abmListaCasinos">
                <div class="text-muted">Cargando casinos…</div>
              </div>
              <small class="text-muted">Encendé el switch en los casinos donde este procedimiento debe estar activo.</small>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-successAceptar" id="btnGuardarProcedimiento" value="nuevo">ACEPTAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        <input type="hidden" id="abmIdProcedimiento" value="0">
      </div>
    </div>
  </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminarProcedimiento" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
        <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
      </div>
      <div class="modal-body franjaRojaModal">
        <h5>¿Seguro desea eliminar el <strong>PROCEDIMIENTO</strong>? Si tiene eventualidades asociadas no se va a poder borrar; en ese caso desactivalo por casino en lugar de borrarlo.</h5>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dangerEliminar" id="btnEliminarProcedimientoModal" value="0">ELIMINAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
      </div>
    </div>
  </div>
</div>
