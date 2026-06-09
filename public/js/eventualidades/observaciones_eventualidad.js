/* Observaciones de eventualidades — modal #modalVerObservaciones.
   Compartido por la tabla de eventualidades (index) y el reporte diario (resumen_diario). */

// Default global: TODOS los tooltips de esta sección se anclan al <body>. Si el tooltip se inserta
// dentro de una tabla/lista con overflow, agranda el área scrolleable y descuadra el layout (el
// "title" desplazaba el botón). Esto se carga antes que index.js / resumen_diario.js / abm, así que
// cubre cada tooltip, lo haya inicializado quien lo haya inicializado.
if ($.fn.tooltip && $.fn.tooltip.Constructor && $.fn.tooltip.Constructor.DEFAULTS) {
  $.fn.tooltip.Constructor.DEFAULTS.container = 'body';
}

// ---------------------------------------------------------------------
// Modal de AVISO / ERROR reutilizable. Reemplaza al alert() nativo del
// navegador en toda la sección Eventualidades (este archivo se carga antes
// que index.js / resumen_diario.js / abm_procedimientos.js, así que la
// función window.avisoEv() queda disponible para todos).
//   avisoEv('mensaje');                          // título por defecto "Aviso"
//   avisoEv('mensaje', { titulo: 'Error' });     // título custom
// ---------------------------------------------------------------------
(function () {
  if (window.avisoEv) return;
  function ensureModal() {
    if (document.getElementById('modalAvisoEv')) return;
    $('body').append(
      '<div class="modal fade" id="modalAvisoEv" tabindex="-1" role="dialog" aria-hidden="true" style="z-index:1080;">' +
        '<div class="modal-dialog" role="document">' +
          '<div class="modal-content">' +
            '<div class="modal-header">' +
              '<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>' +
              '<h4 class="modal-title" id="modalAvisoEvTitulo">Aviso</h4>' +
            '</div>' +
            '<div class="modal-body"><p id="modalAvisoEvMsg" style="margin:0; white-space:pre-line;"></p></div>' +
            '<div class="modal-footer">' +
              '<button type="button" class="btn btn-primary" data-dismiss="modal">ENTENDIDO</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }
  window.avisoEv = function (mensaje, opts) {
    opts = opts || {};
    ensureModal();
    $('#modalAvisoEvTitulo').text(opts.titulo || 'Aviso');
    $('#modalAvisoEvMsg').text(mensaje == null ? '' : String(mensaje));
    // z-index alto para que quede por encima de un modal de formulario ya abierto.
    $('#modalAvisoEv').css('z-index', 1080).modal('show');
  };
})();

function htmlEscapeE(s){ return $('<div>').text(s == null ? '' : s).html(); }

// Avatar del autor: la imagen va guardada como base64 en usuario.imagen (mismo método que
// los comentarios de Notas Unificadas). Si no tiene foto, círculo gris con ícono.
function avatarObsHtml(userImagen, size){
  size = size || 38;
  if (userImagen) {
    return '<img src="data:image/jpeg;base64,' + userImagen + '" style="width:' + size + 'px; height:' + size + 'px; border-radius:50%; object-fit:cover; flex-shrink:0;">';
  }
  return '<div style="width:' + size + 'px; height:' + size + 'px; border-radius:50%; background:#bdc3c7; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fa fa-user" style="color:white; font-size:' + (size * 0.45) + 'px;"></i></div>';
}

function renderArchivosListaEv(files){
  if (!files || !files.length) return '';
  var items = files.map(function(f){
    return '<a href="' + f.url + '" target="_blank" class="text-primary">' +
             '<i class="fa fa-paperclip"></i> ' + htmlEscapeE(f.filename) +
           '</a>';
  });
  return '<div class="obs-archivos" style="margin-top:4px;">' + items.join('<br>') + '</div>';
}

// Confirmación de borrado de observación con modal consistente (#modalEliminarObservacion, en el
// partial). Compartido por las obs de eventualidad y de resumen (este archivo se carga en ambas
// páginas). Reemplaza al confirm() nativo del navegador.
var _onConfirmEliminarObs = null;
function pedirEliminarObservacion(mensaje, onConfirm){
  _onConfirmEliminarObs = (typeof onConfirm === 'function') ? onConfirm : null;
  if (mensaje) $('#titulo-modal-eliminar-obs').text(mensaje);
  $('#modalEliminarObservacion').modal('show');
}
$(document).on('click', '#btn-eliminarObservacion', function(){
  $('#modalEliminarObservacion').modal('hide');
  var cb = _onConfirmEliminarObs; _onConfirmEliminarObs = null;
  if (cb) cb();
});
// Fix Bootstrap 3 (modal anidado): al cerrar el modal de confirmación que está por encima del
// modal de observaciones, el body pierde .modal-open y el de atrás queda sin scroll. Lo restauramos.
$(document).on('hidden.bs.modal', '.modal', function(){
  if ($('.modal.in').length) { $('body').addClass('modal-open'); }
});

function cargarObservacionesEv(){
  var evId = $('#listaObsEv').data('ev-id');
  if (!evId) return;
  var $ul = $('#listaObsEv').empty().append('<li class="list-group-item text-muted">Cargando…</li>');
  $.getJSON('/eventualidades/' + evId + '/observaciones')
    .done(function(data){
      var obs = data.obs || [];
      var esControlador = data.controlador === 1;
      // Título: Casino — día — Turno N — Creador
      if (data.meta) {
        var m = data.meta;
        var dia = m.fecha_toma ? (m.fecha_toma + '').substring(0, 10) : '—';
        var turnoStr = m.turno ? 'Turno ' + m.turno : 'Turno —';
        var partes = [m.casino || '—', dia, turnoStr, (m.creador || '—')];
        $('#obsEvCabecera').text(partes.join(' · '));
      } else {
        $('#obsEvCabecera').text('#' + evId);
      }
      $ul.empty();
      if (!obs.length) {
        $ul.append('<li class="list-group-item text-muted"><em>Sin observaciones</em></li>');
        return;
      }
      obs.forEach(function(o){
        var when  = (o.created_at || '').replace('T',' ').replace(/\..+$/,'');
        var files = o.files || [];
        var del = esControlador
          ? '<button class="btn btn-danger btn-xs pull-right btn-elimObsEv" data-id="' + o.id + '" data-toggle="tooltip" data-placement="bottom" title="ELIMINAR"><i class="fa fa-trash"></i></button>'
          : '';
        var txt = '';
        if (o.observacion) {
          txt = '<div style="margin-top:4px;">' + htmlEscapeE(o.observacion) + '</div>';
        }
        var fechaHtml = when ? ' <small class="text-muted">' + htmlEscapeE(when) + '</small>' : '';
        $ul.append(
          '<li class="list-group-item" style="display:flex; gap:10px; align-items:flex-start;">' +
            avatarObsHtml(o.user_imagen, 38) +
            '<div style="flex:1; min-width:0;">' +
              del +
              '<strong>' + htmlEscapeE(o.usuario || '—') + '</strong>' + fechaHtml +
              txt +
              renderArchivosListaEv(files) +
            '</div>' +
          '</li>'
        );
      });
      // container:'body' → el tooltip no se inserta dentro de la lista con overflow:auto
      // (si no, agranda el scroll y desplaza el botón).
      $ul.find('[data-toggle="tooltip"]').tooltip({ container: 'body' });
    })
    .fail(function(){
      $ul.empty().append('<li class="list-group-item text-danger">Error al cargar.</li>');
    });
}

$(document).on('click', '.btn-verObs', function(){
  const evId = $(this).data('id');
  $('#listaObsEv').data('ev-id', evId);
  $('#obsEvCabecera').text('Cargando…');
  $('#obsEvTexto').val('');
  $('#obsEvFile').val('');
  $('#obsEvFileName').val('');
  cargarObservacionesEv();
  $('#modalVerObservaciones').modal('show');
});

$(document).on('change', '#obsEvFile', function(){
  var fs = this.files || [];
  if (!fs.length) { $('#obsEvFileName').val(''); return; }
  if (fs.length === 1) { $('#obsEvFileName').val(fs[0].name); return; }
  $('#obsEvFileName').val(fs.length + ' archivos seleccionados');
});

$(document).on('click', '#btnGuardarObsEv', function(){
  var evId  = $('#listaObsEv').data('ev-id');
  var texto = ($('#obsEvTexto').val() || '').trim();
  var files = $('#obsEvFile')[0].files;
  if (!texto && (!files || !files.length)) {
    avisoEv('Escribí una observación o adjuntá un archivo.');
    return;
  }

  var fd = new FormData();
  fd.append('id_eventualidades', evId);
  fd.append('observacion',       texto);
  for (var i = 0; i < files.length; i++) fd.append('files[]', files[i]);
  fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

  $.ajax({
    url: '/eventualidades/agregarObservacion',
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false
  })
  .done(function(){
    $('#obsEvTexto').val('');
    $('#obsEvFile').val('');
    $('#obsEvFileName').val('');
    cargarObservacionesEv();
    // Refresca según la página: la tabla de eventualidades (index) o el reporte (resumen diario).
    if ($('#cuerpoTablaEv').length) {
      var paginaActual = $('#herramientasPaginacion').getCurrentPage();
      $('#btn-buscarEventualidades').trigger('click', [{ page: paginaActual }]);
    }
    $(document).trigger('reporteDiario:refresh');
  })
  .fail(function(xhr){
    var msg = (xhr.responseJSON && xhr.responseJSON.error) || 'No se pudo guardar.';
    avisoEv(msg);
  });
});

// Eliminar observación (sólo admin/super). El endpoint legacy responde con `1` si OK.
$(document).on('click', '.btn-elimObsEv', function(){
  var id = $(this).data('id');
  pedirEliminarObservacion('¿Seguro que querés eliminar esta observación?', function(){
    $.ajax({
      url: '/eventualidades/observacion/' + id,
      method: 'GET'
    })
    .done(function(res){
      if (res == 1) {
        cargarObservacionesEv();
      } else {
        avisoEv('No se pudo eliminar.');
      }
    })
    .fail(function(){ avisoEv('No se pudo eliminar.'); });
  });
});
