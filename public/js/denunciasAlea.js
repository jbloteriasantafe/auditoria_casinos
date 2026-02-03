$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
});
$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Denuncias Alea');



});

$(document)
  .on('show.bs.modal', '.modal', function () {
    var z = 1040 + (10 * $('.modal:visible').length);
    $(this).css('z-index', z);
    setTimeout(function () {
      $('.modal-backdrop').not('.modal-stack')
        .css('z-index', z - 1)
        .addClass('modal-stack');
    }, 0);
  })
  .on('hidden.bs.modal', '.modal', function () {
    if ($('.modal:visible').length) {
      $('body').addClass('modal-open');
    }
  });

  function boolLabel(n) {
    return (String(n) === '1' || n === true) ? 'Sí' : 'No';
  }

function attachYYYYMMDDFormatter(sel){
  $(document).on('input', sel, function () {
    let v = this.value.replace(/\D/g, '').slice(0, 8);
    if (v.length > 4) v = v.slice(0,4) + '-' + v.slice(4);
    if (v.length > 7) v = v.slice(0,7) + '-' + v.slice(7);
    this.value = v;
  });

  $(document).on('blur', sel, function () {
    const m = this.value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (m) {
      const y  = +m[1];
      let mm   = Math.min(12, Math.max(1, +m[2]));
      const md = new Date(y, mm, 0).getDate();
      let dd   = Math.min(md, Math.max(1, +m[3]));
      this.value = String(y).padStart(4,'0') + '-' +
                   String(mm).padStart(2,'0') + '-' +
                   String(dd).padStart(2,'0');
    }
  });
}

function attachYYYYMMFormatter(sel){
  $(document).on('input', sel, function(){
    let v = this.value.replace(/\D/g,'').slice(0,6);
    this.value = v.length>4 ? v.slice(0,4)+'-'+v.slice(4) : v;
  });
  $(document).on('blur', sel, function(){
    const m = this.value.match(/^(\d{4})-(\d{1,2})$/);
    if(m){
      let mm = Math.min(12, Math.max(1, +m[2]));
      this.value = m[1] + '-' + String(mm).padStart(2,'0');
    }
  });
}

function convertirMesAno(input) {
  if (!input) return null;

  const meses = [
    '', 'ENE', 'FEB', 'MAR', 'ABR', 'MAY',
    'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'
  ];


  const [fechaPart] = input.split(' ');
  const [year, month] = fechaPart.split('-');

  const mesAbrev = meses[parseInt(month, 10)];

  return `${mesAbrev} ${year}`;
}



function validarCampo(selector, contexto, mensaje, valid) {
  var $input = $(selector);
  var valor = $input.val().trim();
  if (!valor){
    $input.closest(contexto)
          .addClass('has-error')
          .append('<span class="help-block js-error">' + mensaje + '</span>');
    return 0;
  }
  if(!valid) return 0;
return 1;
}


$(document).on('change', '#denuncia_alea_chk', function () {
  $('#denuncia_alea_hidden').val(this.checked ? '1' : '0');
});

//denunciasAlea_paginas
function cargarArchivosdenunciasAlea_paginasLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('denunciasAlea_paginasId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/denunciasAlea/archivosdenunciasAlea_paginas/'+id)
    .done(function(res){
      var files = Array.isArray(res) ? res : (res.data || res.archivos || res.items || []);
      $list.empty();
      if(!files.length){
        $list.append('<div class="list-group-item">Sin archivos asociados.</div>');
        return;
      }
      files.forEach(function(f){
        var fid    = f.id || f.id_registro_archivo;
        var nombre = f.nombre || f.archivo || (f.path ? String(f.path).split('/').pop() : 'archivo');
        var href   = '/denunciasAlea/visualizarArchivo/denunciasAlea_paginas/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="denunciasAlea_paginas" class="btn btn-sm btn-danger btn-del-archivo-denunciasAlea_paginas" title="Quitar">')
                    .attr('data-toggle','tooltip')
                    .attr('data-placement','bottom')
                    .attr('title','ELIMINAR ARCHIVO')
                    .css('float','right').append($('<i>').addClass('fa fa-trash'));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function(){
      $list.empty().append('<div class="list-group-item text-danger">Error al cargar archivos.</div>');
    });
}

var gSortBy  = 'fecha';
var gSortDir = 'desc';

function getFiltrosDenuncias(){
  return {
    // Mes (legacy)
    desde:   $('#fecha_denunciasAlea_paginasDesde').val(),
    hasta:   $('#fecha_denunciasAlea_paginasHasta').val(),

    // Día (nuevo)
    fecha_desde: $('#FDesdeDia').val(),
    fecha_hasta: $('#FHastaDia').val(),

    // Otros filtros
    user_pag:     $('#FUserPag').val(),
    plataforma:   $('#FPlataforma').val(),
    link_pagina:  $('#FLink').val(),
    denunciada:   $('#FDenunciada').val(),
    cant_min:     $('#FCantMin').val(),
    cant_max:     $('#FCantMax').val(),
    estado_id:    $('#FEstado').val(),
    lugar_id:     $('#FLugar').val(),

    // Orden
    sort_by:  gSortBy,
    sort_dir: gSortDir
  };
}

function cargardenunciasAlea_paginas({ page = 1, perPage = 10, desde, hasta } = {}) {
  const fx = getFiltrosDenuncias();

  $.ajax({
    url: '/denunciasAlea/ultimasDenuncias',
    data: {
      page,
      page_size: perPage,

      // Mes (yyyy-mm)
      desde: (typeof desde !== 'undefined' ? desde : fx.desde),
      hasta: (typeof hasta !== 'undefined' ? hasta : fx.hasta),

      // Día (yyyy-mm-dd) si lo estás usando en la UI
      fecha_desde: fx.fecha_desde,
      fecha_hasta: fx.fecha_hasta,

      // Otros filtros
      user_pag:   fx.user_pag,
      plataforma: fx.plataforma,
      link_pagina: fx.link_pagina,
      denunciada: fx.denunciada,
      cant_min:   fx.cant_min,
      cant_max:   fx.cant_max,
      estado_id:  fx.estado_id,
      lugar_id:   fx.lugar_id,

      // Orden
      sort_by:  fx.sort_by,
      sort_dir: fx.sort_dir
    },
    dataType: 'json',
    success: function (res) {
      const $tb = $('#cuerpoTabladenunciasAlea_paginas').empty();
      const items = Array.isArray(res && res.registros) ? res.registros : [];

      if (!items.length) {
        $tb.append(
          $('<tr>').append(
            $('<td>', { colspan: 10, class: 'text-center text-muted' }).text('Sin resultados.')
          )
        );
      } else {
        items.forEach(function (item) {
          $tb.append(generarFiladenunciasAlea_paginas(item));
        });
      }

      const pag = res && res.pagination
        ? res.pagination
        : { current_page: page, per_page: perPage, total: items.length };

      $('#herramientasPaginaciondenunciasAlea_paginas').generarTitulo(
        pag.current_page,
        pag.per_page,
        pag.total,
        clickIndicedenunciasAlea_paginas
      );
      $('#herramientasPaginaciondenunciasAlea_paginas').generarIndices(
        pag.current_page,
        pag.per_page,
        pag.total,
        clickIndicedenunciasAlea_paginas
      );
    },
    error: function (err) {
      console.error('Error cargando denunciasAlea_paginas:', err);
    }
  });
}




function clickIndicedenunciasAlea_paginas(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargardenunciasAlea_paginas({
    page:    pageNumber,
    perPage: pageSize,
    desde:   $('#fecha_denunciasAlea_paginasDesde').val(),
    hasta:   $('#fecha_denunciasAlea_paginasHasta').val()
  });
}


function resetFormdenunciasAlea_paginas(){
  var $f = $('#formNuevoRegistrodenunciasAlea_paginas');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNamedenunciasAlea_paginas').val('No se ha seleccionado ningún archivo');
  $('#fileListdenunciasAlea_paginas').empty();
  $('#uploaddenunciasAlea_paginas').val('');
  $('#uploadsdenunciasAlea_paginasContainer').empty();
    $('#uploadsdenunciasAlea_paginasTable tbody').empty();
    $('#uploadsdenunciasAlea_paginasWrap').hide();
    $('#fileNamedenunciasAlea_paginas').val('No se ha seleccionado ningún archivo');
  }

  function abrirModaldenunciasAlea_paginasCrear(){
    resetFormdenunciasAlea_paginas();

    $('#denunciasAlea_paginas_modo').val('create');
    $('#id_registrodenunciasAlea_paginas').val('');
    $('#guardarRegistrodenunciasAlea_paginas').text('GENERAR');

    // Estado por defecto: 0 / destildado
    $('#denuncia_alea_hidden').val('0');
    $('#denuncia_alea_chk').prop('checked', false).trigger('change');

    // Fecha por defecto
    const d = new Date();
    const currentDate = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    $('#fecha_denunciasAlea_paginasPres').val(currentDate);

    $('#modalCargardenunciasAlea_paginas').modal('show');
  }
  function abrirModaldenunciasAlea_paginasEditar(id){
    resetFormdenunciasAlea_paginas();
    $('#denunciasAlea_paginas_modo').val('edit');
    $('#id_registrodenunciasAlea_paginas').val(id);
    $('#modalCargardenunciasAlea_paginas .modal-title').text('| EDITAR REGISTRO DE denunciasAlea_paginas');
    $('#guardarRegistrodenunciasAlea_paginas').text('ACTUALIZAR');
    $('#modalCargardenunciasAlea_paginas').modal('show');

    $.getJSON('/denunciasAlea/llenardenunciasAlea_paginas/' + id, function(d){
      // fechas
      var ymd = String(d.fecha_pres || d.fecha || '').slice(0,10);
      $('#fechadenunciasAlea_paginasPres input[name="fecha_denunciasAlea_paginasPres"]')
        .val(ymd).trigger('input').trigger('change');

      // campos simples
      $('[name="usuariodenunciasAlea_paginas"]').val(d.usuariodenunciasAlea_paginas);
      $('[name="plataformadenunciasAlea_paginas"]').val(d.plataformadenunciasAlea_paginas).trigger('change');
      $('[name="linkPaginadenunciasAlea_paginas"]').val(d.linkPaginadenunciasAlea_paginas);
      $('[name="CantdenunciasAlea_paginas"]').val(d.CantdenunciasAlea_paginas);
      $('[name="estadodenunciasAlea_paginas"]').val(d.estadodenunciasAlea_paginas).trigger('change');
      $('[name="lugardenunciasAlea_paginas"]').val(d.lugardenunciasAlea_paginas).trigger('change');

      // switch (usar IDs nuevos)
      var on = (String(d.denuncia_alea) === '1');
      $('#denuncia_alea_chk').prop('checked', on).trigger('change'); // <-- esto actualiza el hidden
    })
    .fail(function(xhr){
      console.error('[denunciasAlea_paginas] GET FAIL', xhr.status, xhr.responseText);
      alert('No se pudo cargar el registro de denunciasAlea_paginas.');
    });
  }







$(document).on('click','#denunciasAlea_paginas_nuevo',function(){
  abrirModaldenunciasAlea_paginasCrear();
});

$(document).on('click','.btn-edit-denunciasAlea_paginas',function(){
  var id = $(this).data('id');
  abrirModaldenunciasAlea_paginasEditar(id);
});

let DEN_ESTADOS = null;
let DEN_ESTADOS_PROM = null;
let ESTADO_OPTIONS_HTML = null;

function loadEstados() {
  // Si ya está en memoria, devolvé un "promise" jQuery resuelto
  if (DEN_ESTADOS) {
    return $.Deferred(function(dfd){ dfd.resolve(DEN_ESTADOS); }).promise();
  }
  // Si hay una request en vuelo, reutilizála
  if (DEN_ESTADOS_PROM) return DEN_ESTADOS_PROM;

  // Pedí al server (jqXHR)
  DEN_ESTADOS_PROM = $.getJSON('/denunciasAlea/estados')
    .done(function(list){
      DEN_ESTADOS = list;
      ESTADO_OPTIONS_HTML = ['<option value="">(sin estado)</option>']
        .concat(list.map(function(e){ return '<option value="'+e.id+'">'+e.nombre+'</option>'; }))
        .join('');
    })
    .fail(function(err){
      // permitir reintentar si falló
      DEN_ESTADOS_PROM = null;
      console.error('[estados] fallo', err);
    })
    .always(function(){
      // limpiar "en vuelo" (si tuvo éxito igual queda cacheado en DEN_ESTADOS)
      DEN_ESTADOS_PROM = null;
    });

  return DEN_ESTADOS_PROM; // jqXHR (tiene .then/.done/.fail)
}

// Prefetch al cargar
$(document).ready(function(){ loadEstados(); });

function buildEstadoSelect(id, selectedId) {
  const $sel = $('<select class="form-control input-sm sel-estado-denuncia" disabled>')
                 .attr('data-id', id)
                 .css('min-width', '120px');

  if (ESTADO_OPTIONS_HTML) {
    $sel.html(ESTADO_OPTIONS_HTML);
    if (selectedId != null && selectedId !== '') $sel.val(String(selectedId));
    $sel.prop('disabled', false);
    return $sel;
  }

  $sel.append('<option>Cargando…</option>');
  loadEstados().done(function(){
    $sel.html(ESTADO_OPTIONS_HTML);
    if (selectedId != null && selectedId !== '') $sel.val(String(selectedId));
    $sel.prop('disabled', false);
  });
  return $sel;
}



$(document).on('click','#guardarRegistrodenunciasAlea_paginas',function(){

  var $form = $('#formNuevoRegistrodenunciasAlea_paginas');
  var modo = $('#denunciasAlea_paginas_modo').val() || 'create';
  var id   = $('#id_registrodenunciasAlea_paginas').val() || '';
  var valid = true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();


  valid = validarCampo("input[name='fecha_denunciasAlea_paginasPres']",'.col-md-5','La fecha es requerida.',valid);

  if(!valid) return;

  var fd = new FormData();

  $form.serializeArray().forEach(function(p){
    fd.append(p.name, p.value);
  });


  var url = (modo === 'edit')
    ? ('/denunciasAlea/actualizarPagina/'+id)
    : '/denunciasAlea/guardarPagina';

  $.ajax({
    url: url,
    method: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
    success: function(){
      cargardenunciasAlea_paginas({
        page: 1,
        perPage: $('#herramientasPaginaciondenunciasAlea_paginas').getPageSize(),
        desde:  $('#fecha_denunciasAlea_paginasDesde').val(),
        hasta:  $('#fecha_denunciasAlea_paginasHasta').val()
      });
      $('#modalCargardenunciasAlea_paginas').modal('hide');
      resetFormdenunciasAlea_paginas();
    },
    error: function(){
      $('#salir').next('.help-block.js-error').remove();
      $('#salir').after('<span class="help-block js-error text-danger">Ocurrió un error.</span>');
    }
  });
});

function buildSwitchDenuncia(id, isOn){
  var $label  = $('<label class="switch-alea switch-alea--table">');
  var $chk    = $('<input type="checkbox" class="denuncia-alea-toggle">').attr('data-id', id);
  if (isOn) $chk.prop('checked', true);
  var $slider = $('<span class="slider-alea">')
                  .append('<span class="slider-text off">No</span>')
                  .append('<span class="slider-text on">Sí</span>');
  $label.append($chk, $slider);
  return $label;
}

function generarFiladenunciasAlea_paginas(item, controlador) {
  var id = item.id_denunciasAlea_paginas;

  var fila = $('<tr>').attr('id', 'row-' + id).attr('data-id', id);

  // (1) selección
  fila.append(
    $('<td>').addClass('text-center')
      .append($('<input>', { type:'checkbox', class:'sel-denuncia', 'data-id': id }))
  );

  // (2) acciones
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm btn-edit-denunciasAlea_paginas')
    .attr({type:'button','data-toggle':'tooltip','data-placement':'bottom','data-id':id,})
    .append($('<i>').addClass('fa fa-edit'));

  var btnDelete = $('<button>')
    .addClass('btn btn-danger btn-sm btn-deletedenunciasAlea_paginas')
    .attr({type:'button','data-toggle':'tooltip','data-placement':'bottom','data-id':id})
    .append($('<i>').addClass('fa fa-trash'));

  fila.append(
    $('<td>').addClass('text-center')
      .append($('<div>').css({display:'inline-flex',gap:'6px'}).append(btnEdit, btnDelete))
  );

  // datos
  var fecha         = item.fecha || '-';
  var userPag       = item.user_pag || '-';
  var plataforma    = item.plataforma || '-';
  var link          = item.link_pagina || '-';
  var denunciadaTxt = (String(item.denunciada) === '1' || item.denunciada === true) ? 'Sí' : 'No';
  var cantDen       = (item.cant_denuncias != null) ? item.cant_denuncias : '-';
  var estadoActual  = item.estado_denuncia || '-';
  var denunciadoEn  = item.denunciado_en || '-';

  var linkEl = (link === '-')
    ? document.createTextNode('-')
    : $('<a>')
        .attr({href:link,target:'_blank',rel:'noopener',title:link})
        .css({display:'inline-block',maxWidth:'100%',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'})
        .text(link);

        var on = (String(item.denunciada ?? item.denuncia_alea) === '1') || (item.denunciada === true) || (item.denuncia_alea === true);

      fila
        .append($('<td>').text(fecha))
        .append($('<td>').css({whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}).text(userPag))
        .append($('<td>').text(plataforma))
        .append($('<td>').append(linkEl))

        // ⬇️ aquí va el switch en vez del texto
        .append($('<td class="text-center">').append(buildSwitchDenuncia(id, on)))

        // Cantidad editable
        .append($('<td>').addClass('editable-cant text-center').attr('data-id', id).text(cantDen))
         .append(
          $('<td class="text-center">').append(
             (function(){
                const $sel = buildEstadoSelect(
                  id,
                  item.estado_id ?? item.id_estado ?? null
                );
                // Si tu API todavía NO manda el id y solo manda el nombre,
                // mapeamos por texto.
                if ((item.estado_id == null) && item.estado_denuncia){
                  loadEstados().then(list=>{
                    const found = list.find(e =>
                      String(e.nombre).toLowerCase() === String(item.estado_denuncia).toLowerCase()
                    );
                   if (found) $sel.val(String(found.id));
                  });
                }
                return $sel;
             })()
           )
         )

        .append($('<td>').text(denunciadoEn));

  fila.find('[data-toggle="tooltip"]').tooltip({container:'body'});
  return fila;
}

// Guardamos el valor anterior para poder revertir si falla
$(document).on('focusin', '.sel-estado-denuncia', function(){
  $(this).data('prev', $(this).val());
});

$(document).on('change', '.sel-estado-denuncia', function(){
  const $sel = $(this);
  const prev = $sel.data('prev');
  const id   = $sel.data('id');
  const estado_id = $sel.val() || '';

  $sel.prop('disabled', true).addClass('is-loading');

  $.ajax({
    // Ajustá la ruta al nombre que uses en Laravel
    url: '/denunciasAlea/set-estado/' + encodeURIComponent(id),
    method: 'POST',
    data: { estado_id: estado_id }
  })
  .done(function(resp){
    // opcional: mostrar toast o actualizar algo más
  })
  .fail(function(xhr){
    alert('No se pudo actualizar el estado.');
    $sel.val(prev || '');
  })
  .always(function(){
    $sel.prop('disabled', false).removeClass('is-loading');
  });
});


$(document).on('change', '.denuncia-alea-toggle', function(){
  const $chk = $(this);
  const id   = $chk.data('id');
  const val  = $chk.is(':checked') ? 1 : 0;

  const $td = $chk.closest('td').addClass('td-saving');
  $chk.prop('disabled', true);

  $.ajax({
    url: '/denunciasAlea/cambiarEstadoDenuncia/' + encodeURIComponent(id) + '/set',
    method: 'POST',
    data: { denuncia_alea: val } // booleano 0/1
  })
  .done(function(resp){
    // opcional: podrías refrescar solo el estado del registro si el back
    // te devuelve el valor normalizado (0/1) u otros campos derivados.
  })
  .fail(function(xhr){
    // revertir UI si falla
    $chk.prop('checked', !val);
    alert('No se pudo actualizar la denuncia. Intentá nuevamente.');
  })
  .always(function(){
    $chk.prop('disabled', false);
    $td.removeClass('td-saving');
  });
});

$(document).on('change', '#checkAllDenuncias', function(){
  var on = $(this).is(':checked');
  $('.sel-denuncia').prop('checked', on);
});

/* Eliminar */
$(document).on('click', '.btn-deletedenunciasAlea_paginas', function(){
  var id = $(this).data('id');               // ✅ tomamos el data-id
  $('#btn-eliminardenunciasAlea_paginas').attr('data-id', id);
  $('#modalEliminardenunciasAlea_paginas').modal('show');
});

$('#btn-eliminardenunciasAlea_paginas').on('click', function(){
  var id = $(this).attr('data-id');
  if (!id) return;
  $.ajax({
    url: '/denunciasAlea/eliminarDenuncia/' + encodeURIComponent(id),
    method: 'GET' // si tu ruta soporta DELETE, cámbialo y agrega CSRF
  }).done(function(res){
    if (res == 1 || (res && res.success)) {
      $('#modalEliminardenunciasAlea_paginas').modal('hide');
      cargardenunciasAlea_paginas({
        page:    $('#herramientasPaginaciondenunciasAlea_paginas').getCurrentPage(),
        perPage: $('#herramientasPaginaciondenunciasAlea_paginas').getPageSize(),
        desde:   $('#fecha_denunciasAlea_paginasDesde').val(),
        hasta:   $('#fecha_denunciasAlea_paginasHasta').val()
      });
    }
  });
});

function getSeleccionDenuncias() {
  var ids = $('.sel-denuncia:checked').map(function(){
    var v = $(this).data('id');
    return (v !== undefined && v !== null && v !== '') ? String(v) : null;
  }).get().filter(Boolean);
  console.log('[selección] total=', $('.sel-denuncia').length, 'checked=', ids.length, ids);
  return ids;
}

function postDownload(url, data){
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = url;
  form.target = '_blank';

  var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  var _t = document.createElement('input');
  _t.type = 'hidden'; _t.name = '_token'; _t.value = token;
  form.appendChild(_t);

  Object.keys(data).forEach(function(k){
    var val = data[k];
    if (Array.isArray(val)) {
      // si es array => múltiples inputs con nombre k[]
      val.forEach(function(v){
        var i = document.createElement('input');
        i.type = 'hidden'; i.name = k + '[]'; i.value = v;
        form.appendChild(i);
      });
    } else {
      var i = document.createElement('input');
      i.type = 'hidden'; i.name = k; i.value = val;
      form.appendChild(i);
    }
  });

  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}

$(document)
.off('click', '#btn-descargardenunciasAlea_paginasExcel')
.on('click', '#btn-descargardenunciasAlea_paginasExcel', function(e){
  e.preventDefault();
  var ids = getSeleccionDenuncias();
  if(!ids.length){ alert('Seleccioná al menos una fila.'); return; }
  postDownload('/denunciasAlea/export-seleccion', { format:'xlsx', ids: ids.join(',') });
});

$(document)
.off('click', '#btn-descargardenunciasAlea_paginasPDF')
.on('click', '#btn-descargardenunciasAlea_paginasPDF', function(e){
  e.preventDefault();
  var ids = getSeleccionDenuncias();
  if(!ids.length){ alert('Seleccioná al menos una fila.'); return; }
  postDownload('/denunciasAlea/export-seleccion', { format:'pdf', ids: ids.join(',') });
});

$('#btn-buscardenunciasAlea_paginas').on('click', function(e){
  e.preventDefault();
  cargardenunciasAlea_paginas({
    page: 1,
    perPage: $('#herramientasPaginaciondenunciasAlea_paginas').getPageSize()
  });
});

$('#btn-limpiardenunciasAlea_paginas').on('click', function(){
  $('#FUserPag,#FLink,#FCantMin,#FCantMax').val('');
  $('#FPlataforma,#FDenunciada,#FEstado,#FLugar').val('');
  // Día (nuevo)
  $('#FDesdeDia,#FHastaDia').val('');
  // (si querés, también limpiar los de mes)
  // $('#fecha_denunciasAlea_paginasDesde,#fecha_denunciasAlea_paginasHasta').val('');

  cargardenunciasAlea_paginas({
    page: 1,
    perPage: $('#herramientasPaginaciondenunciasAlea_paginas').getPageSize()
  });
});


$(document).on('click', '.th-sort', function(){
  const by = $(this).data('sort');
  if (gSortBy === by) {
    gSortDir = (gSortDir === 'asc') ? 'desc' : 'asc';
  } else {
    gSortBy  = by;
    gSortDir = 'asc';
  }

  // UI (iconos)
  $('.th-sort').removeClass('active asc desc');
  $(this).addClass('active').addClass(gSortDir);

  cargardenunciasAlea_paginas({
    page: 1,
    perPage: $('#herramientasPaginaciondenunciasAlea_paginas').getPageSize()
  });
});


// =======================
// Pestaña 2 (Obs & Den)
// =======================
var gODSortBy  = 'anio';
var gODSortDir = 'desc';

function formatMiles(n){
  var v = parseInt(n, 10);
  if (isNaN(v)) v = 0;
  return v.toLocaleString('es-AR');
}

function getFiltrosObsYDen(){
  return {
    desde: $('#inp_obsYDenDesde').val() || '',
    hasta: $('#inp_obsYDenHasta').val() || '',

    total_identificadas_min: $('#FTotalIdentMin').val() || '',
    total_identificadas_max: $('#FTotalIdentMax').val() || '',
    realizadas_min:          $('#FRealizadasMin').val() || '',
    realizadas_max:          $('#FRealizadasMax').val() || '',
    no_realizadas_min:       $('#FNoRealizadasMin').val() || '',
    no_realizadas_max:       $('#FNoRealizadasMax').val() || '',
    activas_min:             $('#FActivasMin').val() || '',
    activas_max:             $('#FActivasMax').val() || '',
    bajas_min:               $('#FBajasMin').val() || '',
    bajas_max:               $('#FBajasMax').val() || '',
    den_sf_min:              $('#FDenSFMin').val() || '',
    den_sf_max:              $('#FDenSFMax').val() || '',
    den_ros_min:             $('#FDenRosMin').val() || '',
    den_ros_max:             $('#FDenRosMax').val() || '',

    sort_by:  gODSortBy,
    sort_dir: gODSortDir
  };
}

function cargarObsYDen(params){
  params = params || {};
  var page    = params.page    || 1;
  var perPage = params.perPage || 10;

  var fx = getFiltrosObsYDen();
  $.ajax({
    url: '/denunciasAlea/totales-mensuales',
    data: {
  page: page,
  page_size: perPage,

  desde: fx.desde,
  hasta: fx.hasta,

  total_identificadas_min: fx.total_identificadas_min,
  total_identificadas_max: fx.total_identificadas_max,
  realizadas_min:          fx.realizadas_min,
  realizadas_max:          fx.realizadas_max,
  no_realizadas_min:       fx.no_realizadas_min,
  no_realizadas_max:       fx.no_realizadas_max,
  activas_min:             fx.activas_min,
  activas_max:             fx.activas_max,
  bajas_min:               fx.bajas_min,
  bajas_max:               fx.bajas_max,
  den_sf_min:              fx.den_sf_min,
  den_sf_max:              fx.den_sf_max,
  den_ros_min:             fx.den_ros_min,
  den_ros_max:             fx.den_ros_max,

  sort_by: fx.sort_by,
  sort_dir: fx.sort_dir
},

    dataType: 'json'
  }).done(function(res){
    var $tb = $('#cuerpoTablaObsYDen').empty();
    (res.registros || []).forEach(function(r){
      $tb.append(generarFilaObsYDen(r));
    });

    $('#herramientasPaginacionObsYDen').generarTitulo(
      res.pagination.current_page,
      res.pagination.per_page,
      res.pagination.total,
      clickIndiceObsYDen
    );
    $('#herramientasPaginacionObsYDen').generarIndices(
      res.pagination.current_page,
      res.pagination.per_page,
      res.pagination.total,
      clickIndiceObsYDen
    );
  });
}

function generarFilaObsYDen(r){
  var mid   = String(r.anio)+'-'+String(r.mes).padStart(2,'0');
  var $tr   = $('<tr>').attr('data-key', mid);

  $tr.append(
    $('<td class="text-center">').append(
      $('<input>', {type:'checkbox', class:'sel-obsden', 'data-key': mid})
    )
  );
  $tr.append($('<td>').text(r.anio));
  $tr.append($('<td>').text(r.mes));

  $tr.append($('<td>').text(formatMiles(r.total_identificadas)));
  $tr.append($('<td>').text(formatMiles(r.realizadas)));
  $tr.append($('<td>').text(formatMiles(r.no_realizadas)));
  $tr.append($('<td>').text(formatMiles(r.activas)));
  $tr.append($('<td>').text(formatMiles(r.bajas)));
  $tr.append($('<td>').text(formatMiles(r.den_sf)));
  $tr.append($('<td>').text(formatMiles(r.den_ros)));

  return $tr;
}

function clickIndiceObsYDen(e, pageNumber, pageSize){
  if (e) e.preventDefault();
  cargarObsYDen({ page: pageNumber, perPage: pageSize });
}

// Orden por header
$(document).on('click', '.th-sort-od', function(){
  var by = $(this).data('sort');
  if (gODSortBy === by){
    gODSortDir = (gODSortDir === 'asc') ? 'desc' : 'asc';
  } else {
    gODSortBy  = by;
    gODSortDir = 'asc';
  }
  $('.th-sort-od').removeClass('active asc desc');
  $(this).addClass('active').addClass(gODSortDir);

  cargarObsYDen({
    page: 1,
    perPage: $('#herramientasPaginacionObsYDen').getPageSize ? $('#herramientasPaginacionObsYDen').getPageSize() : 10
  });
});

// Seleccionar todos
$(document).on('change', '#checkAllObsYDen', function(){
  var on = $(this).is(':checked');
  $('.sel-obsden').prop('checked', on);
});

// Buscar / Limpiar
$('#btn-buscarObsYDen').on('click', function(e){
  e.preventDefault();
  cargarObsYDen({ page: 1, perPage: 10 });
});
$('#btn-limpiarObsYDen').on('click', function(){
  $('#inp_obsYDenDesde,#inp_obsYDenHasta').val('');

  $('#FTotalIdentMin,#FTotalIdentMax,#FRealizadasMin,#FRealizadasMax,#FNoRealizadasMin,#FNoRealizadasMax,#FActivasMin,#FActivasMax,#FBajasMin,#FBajasMax,#FDenSFMin,#FDenSFMax,#FDenRosMin,#FDenRosMax').val('');

  cargarObsYDen({ page: 1, perPage: 10 });
});

function getSeleccionObsYDen(){
  return $('.sel-obsden:checked').map(function(){
    // el valor está en data-key del checkbox o del <tr>
    var key = $(this).data('key') || $(this).closest('tr').data('key');
    return key ? String(key) : null; // p.ej. "2025-07"
  }).get().filter(Boolean);
}
// Exports
$(document).on('click', '#btn-descargarObsYDenExcel', function(e){
  e.preventDefault();
  var sel = getSeleccionObsYDen();           // ["2025-07","2025-08",...]
  if (!sel.length){ alert('Seleccioná al menos un mes.'); return; }

  // 🔹 Sin filtros: sólo lo seleccionado
  postDownload('/denunciasAlea/export-totales', {
    format: 'xlsx',
    seleccion: sel            // se envía como seleccion[]
  });
});

$(document).on('click', '#btn-descargarObsYDenPDF', function(e){
  e.preventDefault();
  var fx  = getFiltrosObsYDen();
  var sel = getSeleccionObsYDen();
  if (!sel.length){ alert('Seleccioná al menos un mes.'); return; }

  postDownload('/denunciasAlea/export-totales', Object.assign({
    format: 'pdf',
    seleccion: sel
  }, fx));
});






// Estadisticas


// ... (otros códigos JS previos) ...

// Estadisticas
;(function () {
  // Tema global minimalista
  Highcharts.setOptions({
    chart: {
      backgroundColor: 'transparent',
      style: { fontFamily: "-apple-system, system-ui, Segoe UI, Roboto, 'Helvetica Neue', Arial" },
      spacing: [8, 12, 8, 12]
    },
    lang: {
      contextButtonTitle: 'Exportar',
      downloadPNG: 'PNG',
      downloadJPEG: 'JPEG',
      downloadPDF: 'PDF',
      downloadSVG: 'SVG',
      viewFullscreen: 'Pantalla completa',
      thousandsSep: '.',
      decimalPoint: ','
    },
    colors: ['#16a34a', '#ef4444'], // Activas / Bajas
    title: { style: { color: '#111827', fontWeight: 600, fontSize: '16px' } },
    subtitle: { style: { color: '#6b7280', fontSize: '12px' } },
    xAxis: {
      lineColor: '#e5e7eb',
      tickColor: '#e5e7eb',
      labels: { style: { color: '#374151' } },
      gridLineWidth: 0
    },
    yAxis: {
      gridLineColor: '#f1f5f9',
      labels: { style: { color: '#374151' } },
      title: { style: { color: '#111827', fontWeight: 600 } }
    },
    legend: {
      itemStyle: { color: '#111827', fontWeight: 600 },
      itemHoverStyle: { color: '#111827' }
    },
    tooltip: {
      backgroundColor: 'rgba(255,255,255,0.95)',
      borderColor: '#e5e7eb',
      borderRadius: 8,
      style: { color: '#111827' }
    },
    credits: { enabled: false }
  });

  let chartAB = null;

  function renderChartActivasBajas(categories, dataActivas, dataBajas, dataDenuncias) {
    if (chartAB) { chartAB.destroy(); chartAB = null; }

    chartAB = Highcharts.chart('chartActivasBajas', {
      chart: { type: 'column' },
      title: { text: 'Denuncias y Perfiles por mes en el año actual' },
      subtitle: { text: 'Pilar 1: Denuncias | Pilar 2: Bajas + Activas (apilado)' },
      xAxis: { categories, crosshair: { width: 1, color: '#e5e7eb' } },
      yAxis: {
        min: 0,
        title: { text: 'Cantidad' },
        stackLabels: {
          enabled: true,
          style: { fontWeight: '600', color: '#111827' },
          formatter: function () {
            return Highcharts.numberFormat(this.total, 0, ',', '.');
          }
        }
      },
      legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom' },
      plotOptions: {
        column: {
          // 🔑 agrupado por pila: cada "stack" es una columna distinta por categoría
          stacking: 'normal',
          grouping: true,
          borderWidth: 0,
          borderRadius: 5,
          pointPadding: 0.1,
          groupPadding: 0.12,
          dataLabels: {
            enabled: true,
            formatter() {
              const v = this.y || 0;
              return v >= 1 ? Highcharts.numberFormat(v, 0, ',', '.') : '';
            },
            style: { textOutline: 'none', fontSize: '11px', color: '#111827' }
          },
          states: { inactive: { opacity: 0.85 } }
        }
      },
      tooltip: {
        shared: true,
        useHTML: true,
        formatter: function () {
          const fmt  = n => Highcharts.numberFormat(n || 0, 0, ',', '.');
          const pts  = this.points || [];
          const denP = pts.find(p => p.series.userOptions.stack === 'den');
          const perf = pts.filter(p => p.series.userOptions.stack === 'perf');
          const sumPerf = perf.reduce((s, p) => s + (p.y || 0), 0);

          let html = `<div style="font-weight:600;margin-bottom:4px">${this.x}</div>`;
          if (denP) html += `<div>Denuncias: <b>${fmt(denP.y)}</b></div>`;
          perf.forEach(p => { html += `<div>${p.series.name}: <b>${fmt(p.y)}</b></div>`; });
          html += `<hr style="border:none;border-top:1px solid #e5e7eb;margin:6px 0" />`;
          html += `<div>Total perfiles (Bajas + Activas): <b>${fmt(sumPerf)}</b></div>`;
          return html;
        }
      },
      series: [
        // Pilar A (stack 'den'): solo denuncias
        { name: 'Denuncias', data: dataDenuncias, color: '#0ea5e9', stack: 'den' },

        // Pilar B (stack 'perf'): bajas (abajo) + activas (arriba)
        { name: 'Bajas',     data: dataBajas,     color: '#ef4444', stack: 'perf' },
        { name: 'Activas',   data: dataActivas,   color: '#16a34a', stack: 'perf' }
      ],
      exporting: {
        enabled: true,
        buttons: {
          contextButton: { menuItems: ['viewFullscreen','separator','downloadPNG','downloadJPEG','downloadPDF','downloadSVG'] }
        }
      },
      credits: { enabled: false }
    });
  }

  function cargarChartActivasBajas(opts) {
    opts = opts || {};
    var chartContainer = $('#chartActivasBajas');

    const MESES = ['', 'ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];

    const toNum = (v) => {
        if (v == null) return 0;
        if (typeof v === 'number') return v;
        const s = String(v).replace(/\./g, '').replace(',', '.');
        const n = Number(s);
        return Number.isFinite(n) ? n : 0;
    };

    // --- LÓGICA NUEVA: ÚLTIMOS 12 MESES ---
    const now = new Date();

    // 1. Fecha "Hasta" (Mes actual)
    const yHasta = now.getFullYear();
    const mHasta = now.getMonth() + 1; // getMonth es 0-11
    const hastaStr = `${yHasta}-${String(mHasta).padStart(2,'0')}`;

    // 2. Fecha "Desde" (11 meses atrás para completar el año móvil)
    const past = new Date(now.getFullYear(), now.getMonth() - 11, 1);
    const yDesde = past.getFullYear();
    const mDesde = past.getMonth() + 1;
    const desdeStr = `${yDesde}-${String(mDesde).padStart(2,'0')}`;

    chartContainer.html('<div style="text-align:center;padding:40px;color:#6b7280">Cargando últimos 12 meses...</div>');

    $.ajax({
      url: '/denunciasAlea/totales-mensuales',
      dataType: 'json',
      data: {
        page: 1,
        page_size: 50, // Solo necesitamos los últimos 12, pedimos 50 por seguridad
        desde: desdeStr,
        hasta: hastaStr,
        sort_by: 'anio', // Importante: Que el back intente ordenar
        sort_dir: 'asc'
      }
    })
    .done(function (res) {
      let rows = Array.isArray(res && res.registros) ? res.registros.slice() : [];

      // Ordenamos manualmente por Año y luego Mes para asegurar que
      // Dic-2024 aparezca antes que Ene-2025
      rows.sort((a, b) => (toNum(a.anio) - toNum(b.anio)) || (toNum(a.mes) - toNum(b.mes)));

      // Si por alguna razón vienen más de 12, cortamos los últimos 12
      if (rows.length > 12) {
          rows = rows.slice(rows.length - 12);
      }

      const categories = rows.map(r => `${MESES[toNum(r.mes)]} ${toNum(r.anio)}`);
      const activas    = rows.map(r => toNum(r.activas));
      const bajas      = rows.map(r => toNum(r.bajas));
      const denuncias  = rows.map(r => toNum(r.realizadas));

      renderChartActivasBajas(categories, activas, bajas, denuncias);

      // Título dinámico
      if(chartAB) {
          chartAB.setTitle({ text: `Evolución últimos 12 meses (${categories[0]} - ${categories[categories.length-1]})` });
      }
    })
    .fail(function () {
      chartContainer.html('<div style="text-align:center;padding:40px;color:#ef4444;font-weight:600">Error al cargar datos.</div>');
    });
  }

  // Exponer al global para poder llamarla desde otras partes (pestañas)
  window.cargarChartActivasBajas = cargarChartActivasBajas;
})(); // ← cerrar el IIFE

;(function () {
  // --- Normaliza "plataforma" a 3 buckets ---
  function normPlataforma(v) {
    const s = (v || '')
      .toString()
      .toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // sin acentos
      .replace(/[^a-z0-9]+/g, ' ');

    if (/\binsta/.test(s) || /\big\b/.test(s)) return 'Instagram';
    if (/\bface/.test(s)  || /\bfb\b/.test(s)) return 'Facebook';
    // vacío o null => "Sin plataforma"
    if (!s.trim()) return 'Sin plataforma';
    // cualquier otra cosa la ignoramos en este gráfico
    return 'OTROS';
  }

  // --- Render del Pie ---
  function renderPiePlataformas(stats) {
    const total = stats.instagram + stats.facebook + stats.sin;
    if (!total) {
      $('#chartPiePlataformas').html(
        '<div style="text-align:center;padding:40px;color:#6b7280">Sin datos para graficar.</div>'
      );
      return;
    }

    Highcharts.chart('chartPiePlataformas', {
      chart: { type: 'pie' },
      title: { text: 'Distribución por plataforma' },
      subtitle: { text: 'Porcentaje y cantidad' },
      tooltip: {
        pointFormatter: function () {
          const pct = Highcharts.numberFormat(this.percentage || 0, 1, ',', '.');
          const cnt = Highcharts.numberFormat(this.y || 0, 0, ',', '.');
          return `<span style="color:${this.color}">\u25CF</span> ${this.name}: <b>${pct}%</b> (${cnt})<br/>`;
        }
      },
      plotOptions: {
        pie: {
          allowPointSelect: true,
          cursor: 'pointer',
          dataLabels: {
            enabled: true,
            formatter: function () {
              const pct = Highcharts.numberFormat(this.percentage || 0, 1, ',', '.');
              const cnt = Highcharts.numberFormat(this.y || 0, 0, ',', '.');
              return `${this.point.label}: ${pct}% (${cnt})`;
            },
            style: { textOutline: 'none', color: '#111827', fontSize: '12px' }
          },
          showInLegend: true
        }
      },
      series: [{
        name: 'Plataformas',
        colorByPoint: true,
        colors: ['#0ea5e9', '#3b82f6', '#9ca3af'], // Instagram / Facebook / Sin plataforma
        data: [
          { name: 'Instagram',       y: stats.instagram, label: 'Instagram' },
          { name: 'Facebook',        y: stats.facebook,  label: 'Facebook'  },
          { name: 'Sin plataforma',  y: stats.sin,       label: 'Sin plataforma' }
        ]
      }],
      credits: { enabled: false }
    });
  }

  // --- Descarga todas las filas aplicando los filtros actuales de tu UI ---
  function fetchAllDenuncias(params) {
    const pageSize = 500; // ajustá si tu API permite más
    let page = 1, acc = [];

    const base = Object.assign({}, params, { page_size: pageSize });

    function step() {
      return $.ajax({
        url: '/denunciasAlea/ultimasDenuncias',
        dataType: 'json',
        data: Object.assign({}, base, { page })
      }).then(function (res) {
        const items = Array.isArray(res && res.registros) ? res.registros : [];
        acc = acc.concat(items);

        const pag = res && res.pagination;
        const total    = pag ? pag.total        : acc.length;
        const per_page = pag ? pag.per_page     : items.length;
        const current  = pag ? pag.current_page : page;

        if (acc.length < total && items.length === per_page) {
          page = current + 1;
          return step();
        }
      });
    }
    return step().then(() => acc);
  }

  // --- Función pública: arma el pie con los filtros de tu pantalla ---
  function cargarPiePlataformas() {
    // Reutilizamos los mismos filtros que tu tabla
    const fx = (typeof getFiltrosDenuncias === 'function') ? getFiltrosDenuncias() : {};

    $('#chartPiePlataformas').html('<div style="text-align:center;padding:40px;color:#6b7280">Cargando…</div>');

    const params = {
      page: 1,
      // filtros mes
      desde:        fx.desde        || '',
      hasta:        fx.hasta        || '',
      // filtros día
      fecha_desde:  fx.fecha_desde  || '',
      fecha_hasta:  fx.fecha_hasta  || '',
      // otros
      user_pag:     fx.user_pag     || '',
      plataforma:   fx.plataforma   || '',
      link_pagina:  fx.link_pagina  || '',
      denunciada:   fx.denunciada   || '',
      cant_min:     fx.cant_min     || '',
      cant_max:     fx.cant_max     || '',
      estado_id:    fx.estado_id    || '',
      lugar_id:     fx.lugar_id     || '',
      sort_by:      fx.sort_by      || 'fecha',
      sort_dir:     fx.sort_dir     || 'desc'
    };

    fetchAllDenuncias(params)
      .then(function (rows) {
        let instagram = 0, facebook = 0, sin = 0;
        rows.forEach(function (r) {
          const raw = r.plataforma || r.plataformadenunciasAlea_paginas || '';
          const bucket = normPlataforma(raw);
          if (bucket === 'Instagram')      instagram++;
          else if (bucket === 'Facebook')  facebook++;
          else if (bucket === 'Sin plataforma') sin++;
          // "OTROS" se ignora para este gráfico a pedido.
        });
        renderPiePlataformas({ instagram, facebook, sin });
      })
      .fail(function () {
        $('#chartPiePlataformas').html('<div style="text-align:center;padding:40px;color:#ef4444;font-weight:600">Error al cargar datos.</div>');
      });
  }

  // Exponer al global
  window.cargarPiePlataformas = cargarPiePlataformas;

  ;(function () {
  // Variables globales del módulo para destruir instancias si se recarga
  let chartOrigen = null;
  let chartEfect = null;

  // Render: Origen (SF vs Rosario)
  function renderChartOrigen(categories, dataSF, dataRos) {
    if (chartOrigen) { chartOrigen.destroy(); chartOrigen = null; }

    chartOrigen = Highcharts.chart('chartOrigenDenuncias', {
      chart: { type: 'column' },
      title: { text: 'Origen de Denuncias' },
      subtitle: { text: 'Santa Fe vs Rosario' },
      xAxis: { categories: categories, crosshair: true },
      yAxis: { min: 0, title: { text: 'Cantidad' } },
      tooltip: { shared: true },
      plotOptions: {
        column: {
          pointPadding: 0.2,
          borderWidth: 0,
          borderRadius: 3
        }
      },
      colors: ['#8b5cf6', '#f97316'], // Violeta y Naranja
      series: [
        { name: 'Santa Fe', data: dataSF },
        { name: 'Rosario', data: dataRos }
      ],
      credits: { enabled: false }
    });
  }

  // Render: Efectividad (Realizadas vs No Realizadas)
  function renderChartEfectividad(categories, dataReal, dataNoReal) {
    if (chartEfect) { chartEfect.destroy(); chartEfect = null; }

    chartEfect = Highcharts.chart('chartEfectividad', {
      chart: { type: 'areaspline' }, // Área suave
      title: { text: 'Gestión de Denuncias' },
      subtitle: { text: 'Realizadas vs No Realizadas' },
      xAxis: { categories: categories, crosshair: true },
      yAxis: { title: { text: 'Total' } },
      tooltip: { shared: true },
      plotOptions: {
        areaspline: {
          fillOpacity: 0.3
        }
      },
      colors: ['#0ea5e9', '#64748b'], // Azul claro y Gris oscuro
      series: [
        { name: 'Realizadas', data: dataReal },
        { name: 'No Realizadas', data: dataNoReal }
      ],
      credits: { enabled: false }
    });
  }

  // Función Principal que pide los datos y llama a los renders
  function cargarGraficosSecundarios() {
    const container1 = $('#chartOrigenDenuncias');
    const container2 = $('#chartEfectividad');

    // Loading state visual
    const loadingHtml = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#9ca3af">Cargando datos...</div>';
    container1.html(loadingHtml);
    container2.html(loadingHtml);

    const MESES = ['', 'ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
    const toNum = (v) => {
        if (v == null) return 0;
        if (typeof v === 'number') return v;
        const s = String(v).replace(/\./g, '').replace(',', '.');
        const n = Number(s);
        return Number.isFinite(n) ? n : 0;
    };

    // Filtro: Año actual (Igual que tu gráfico principal)
    const now = new Date();
    const year = now.getFullYear();
    const desde = `${year}-01`;
    const hasta = `${year}-12`;

    // Reutilizamos el endpoint que ya tienes
    $.ajax({
      url: '/denunciasAlea/totales-mensuales',
      dataType: 'json',
      data: {
        page: 1,
        page_size: 500, // Traer todo el año
        desde: desde,
        hasta: hasta,
        sort_by: 'anio',
        sort_dir: 'asc'
      }
    })
    .done(function (res) {
      let rows = Array.isArray(res && res.registros) ? res.registros.slice() : [];

      // Ordenar y filtrar por año actual JS
      rows = rows
        .filter(r => toNum(r.anio) === year)
        .sort((a, b) => (toNum(a.anio) - toNum(b.anio)) || (toNum(a.mes) - toNum(b.mes)));

      // Preparar Arrays para Highcharts
      const categories = rows.map(r => `${MESES[toNum(r.mes)]}`);

      // Datos Gráfico Origen
      const dataSF  = rows.map(r => toNum(r.den_sf));
      const dataRos = rows.map(r => toNum(r.den_ros));

      // Datos Gráfico Efectividad
      const dataReal   = rows.map(r => toNum(r.realizadas));
      const dataNoReal = rows.map(r => toNum(r.no_realizadas));

      // Renderizar
      renderChartOrigen(categories, dataSF, dataRos);
      renderChartEfectividad(categories, dataReal, dataNoReal);
    })
    .fail(function () {
      const errHtml = '<div style="text-align:center;padding-top:40%;color:#ef4444">Error de carga</div>';
      container1.html(errHtml);
      container2.html(errHtml);
    });
  }

  // Exponer la función al scope global para llamarla desde el click del tab
  window.cargarGraficosSecundarios = cargarGraficosSecundarios;

})();
;(function () {

  function renderTopPerfiles(data) {
    Highcharts.chart('chartTopPerfiles', {
      chart: { type: 'bar' },
      title: { text: 'Top 5 Perfiles con más Denuncias' },
      subtitle: { text: 'Volumen acumulado de denuncias recibidas' },
      xAxis: {
        type: 'category',
        title: { text: null },
        labels: { style: { fontSize: '13px', fontWeight: 'bold' } }
      },
      yAxis: {
        min: 0,
        title: { text: 'Total de Denuncias', align: 'high' },
        labels: { overflow: 'justify' },
        allowDecimals: false
      },
      tooltip: {
        pointFormat: '<b>{point.y}</b> denuncias acumuladas'
      },
      plotOptions: {
        bar: {
          dataLabels: { enabled: true, style: { fontWeight: 'bold' } },
          colorByPoint: true
        }
      },
      colors: ['#7f1d1d', '#991b1b', '#b91c1c', '#dc2626', '#ef4444'], // Degradado rojo oscuro a claro
      legend: { enabled: false },
      series: [{
        name: 'Denuncias',
        data: data
      }],
      credits: { enabled: false }
    });
  }

  function cargarTopPerfiles() {
    const container = $('#chartTopPerfiles');
    container.html('<div style="text-align:center;padding:40px;color:#6b7280">Calculando ranking...</div>');

    const fx = (typeof getFiltrosDenuncias === 'function') ? getFiltrosDenuncias() : {};

    const params = {
      page: 1,
      desde:        fx.desde        || '',
      hasta:        fx.hasta        || '',
      fecha_desde:  fx.fecha_desde  || '',
      fecha_hasta:  fx.fecha_hasta  || '',
      user_pag:     fx.user_pag     || '',
      plataforma:   fx.plataforma   || '',
      cant_min:     fx.cant_min     || '', // Importante: respetar filtros de cantidad si los usan
      cant_max:     fx.cant_max     || '',
      sort_by:      'fecha',
      sort_dir:     'desc'
    };

    if (typeof fetchAllDenuncias !== 'function') {
        container.html('Error: función auxiliar fetchAllDenuncias no encontrada.');
        return;
    }

    fetchAllDenuncias(params)
      .then(function (rows) {

        let conteo = {};

        rows.forEach(function(r) {
          // 1. Identificar usuario
          let usuario = r.user_pag || r.usuariodenunciasAlea_paginas || 'Anónimo';
          usuario = usuario.trim();
          if (!usuario || usuario === '-') return;

          // 2. Obtener la CANTIDAD acumulada del registro
          // Probamos 'cant_denuncias' (nombre usual en listas) o 'CantdenunciasAlea_paginas' (nombre en forms)
          let cantidad = r.cant_denuncias || r.CantdenunciasAlea_paginas;

          // 3. Convertir a entero (si viene nulo o vacío, asumimos 0)
          cantidad = parseInt(cantidad, 10);
          if (isNaN(cantidad)) cantidad = 0;

          // 4. Sumar al acumulador
          if (!conteo[usuario]) {
            conteo[usuario] = 0;
          }
          conteo[usuario] += cantidad;
        });

        // Convertir objeto a array para Highcharts
        let ranking = [];
        for (var key in conteo) {
          if (conteo.hasOwnProperty(key)) {
            ranking.push({ name: key, y: conteo[key] });
          }
        }

        // Ordenar por cantidad (mayor a menor)
        ranking.sort(function(a, b) {
          return b.y - a.y;
        });

        // Top 5
        let top5 = ranking.slice(0, 5);

        if (top5.length === 0) {
            container.html('<div style="text-align:center;padding:40px;">No hay datos suficientes.</div>');
        } else {
            renderTopPerfiles(top5);
        }

      })
      .fail(function () {
        container.html('<div style="text-align:center;padding:40px;color:#ef4444">Error al cargar el ranking.</div>');
      });
  }

  window.cargarTopPerfiles = cargarTopPerfiles;

})();

;(function () {

  function renderChartEstadosPie(data, total) {
    if ($('#chartEstadosPie').length === 0) return;

    // Formateamos el total con puntos de miles
    const totalFmt = Highcharts.numberFormat(total, 0, ',', '.');

    Highcharts.chart('chartEstadosPie', {
      chart: { type: 'pie' },
      title: { text: 'Distribución por Estado' },

      // --- AQUÍ AGREGAMOS EL TOTAL ---
      subtitle: {
          text: `Total: <b>${totalFmt}</b> denuncias<br><span style="font-size:10px">Porcentaje y cantidad</span>`,
          style: { fontSize: '14px', color: '#374151' }
      },
      // -------------------------------

      tooltip: {
        pointFormatter: function () {
          const pct = Highcharts.numberFormat(this.percentage || 0, 1, ',', '.');
          const cnt = Highcharts.numberFormat(this.y || 0, 0, ',', '.');
          return `<span style="color:${this.color}">\u25CF</span> ${this.name}: <b>${pct}%</b> (${cnt})<br/>`;
        }
      },
      plotOptions: {
        pie: {
          allowPointSelect: true,
          cursor: 'pointer',
          dataLabels: {
            enabled: true,
            style: { textOutline: 'none', color: '#111827', fontSize: '12px' },
            formatter: function () {
              const pct = Highcharts.numberFormat(this.percentage || 0, 1, ',', '.');
              const cnt = Highcharts.numberFormat(this.y || 0, 0, ',', '.');
              return `${this.point.name}: ${pct}% (${cnt})`;
            }
          },
          showInLegend: true
        }
      },
      colors: ['#f59e0b', '#10b981', '#ef4444', '#3b82f6', '#6b7280', '#8b5cf6'],
      series: [{
        name: 'Estados',
        colorByPoint: true,
        data: data
      }],
      credits: { enabled: false }
    });
  }

  function cargarDistribucionEstados() {
    const container = $('#chartEstadosPie');
    container.html('<div style="text-align:center;padding:40px;color:#6b7280">Cargando estados...</div>');

    const fx = (typeof getFiltrosDenuncias === 'function') ? getFiltrosDenuncias() : {};

    const params = {
      page: 1,
      desde:        fx.desde        || '',
      hasta:        fx.hasta        || '',
      fecha_desde:  fx.fecha_desde  || '',
      fecha_hasta:  fx.fecha_hasta  || '',
      user_pag:     fx.user_pag     || '',
      plataforma:   fx.plataforma   || '',
      cant_min:     fx.cant_min     || '',
      cant_max:     fx.cant_max     || '',
      estado_id:    fx.estado_id    || '',
      lugar_id:     fx.lugar_id     || '',
      sort_by:      'fecha',
      sort_dir:     'desc'
    };

    if (typeof fetchAllDenuncias !== 'function') {
        container.html('Error: función auxiliar no encontrada.');
        return;
    }

    fetchAllDenuncias(params)
      .then(function (rows) {

        let conteo = {};
        let total = 0; // <--- Ya calculamos esto aquí

        rows.forEach(function(r) {
            let estadoNombre = r.estado_denuncia;
            if (!estadoNombre) {
                estadoNombre = 'SIN ESTADO';
            } else {
                estadoNombre = estadoNombre.charAt(0).toUpperCase() + estadoNombre.slice(1).toLowerCase();
            }

            if (!conteo[estadoNombre]) {
                conteo[estadoNombre] = 0;
            }
            conteo[estadoNombre]++;
            total++; // Sumamos cada fila
        });

        if (total === 0) {
            container.html('<div style="text-align:center;padding:40px;">No hay datos para graficar.</div>');
            return;
        }

        let dataSerie = [];
        for (let key in conteo) {
            dataSerie.push({ name: key, y: conteo[key] });
        }
        dataSerie.sort((a, b) => b.y - a.y);

        // --- CAMBIO AQUÍ: Pasamos 'total' como segundo argumento ---
        renderChartEstadosPie(dataSerie, total);
      })
      .fail(function () {
        container.html('<div style="text-align:center;padding:40px;color:#ef4444">Error al cargar gráfico de estados.</div>');
      });
  }

  // Exponer la función
  window.cargarDistribucionEstados = cargarDistribucionEstados;

})();
;(function () {

  let chartAnual = null;

  function renderChartAnual(categories, dataActivas, dataBajas, dataDenuncias) {
    if (chartAnual) { chartAnual.destroy(); chartAnual = null; }

    if ($('#chartEvolucionAnual').length === 0) return;

    chartAnual = Highcharts.chart('chartEvolucionAnual', {
      chart: { type: 'column' },
      title: { text: 'Evolución Anual Histórica' },
      subtitle: { text: 'Comparativa acumulada por año' },
      xAxis: {
        categories: categories,
        crosshair: { width: 1, color: '#e5e7eb' }, // Estilo igual al mensual
        labels: { style: { color: '#374151', fontWeight: 'bold' } }
      },
      yAxis: {
        min: 0,
        title: { text: 'Cantidad' },
        stackLabels: {
          enabled: true,
          style: { fontWeight: 'bold', color: '#111827' }, // Texto oscuro igual al mensual
          formatter: function() {
             // Formato de miles
             return Highcharts.numberFormat(this.total, 0, ',', '.');
          }
        }
      },
      tooltip: {
        shared: true,
        useHTML: true, // Tooltip HTML igual al mensual para que se vea lindo
        formatter: function () {
             const fmt  = n => Highcharts.numberFormat(n || 0, 0, ',', '.');
             const pts  = this.points || [];

             // Buscamos los stacks
             const denP = pts.find(p => p.series.userOptions.stack === 'den');
             const perf = pts.filter(p => p.series.userOptions.stack === 'perf');
             const sumPerf = perf.reduce((s, p) => s + (p.y || 0), 0);

             let html = `<div style="font-weight:600;margin-bottom:4px;border-bottom:1px solid #ddd;padding-bottom:4px;">${this.x}</div>`;

             // Fila Denuncias
             if (denP) {
                 html += `<div><span style="color:${denP.series.color}">\u25CF</span> Denuncias: <b>${fmt(denP.y)}</b></div>`;
             }

             // Filas Perfiles
             perf.forEach(p => {
                 html += `<div><span style="color:${p.series.color}">\u25CF</span> ${p.series.name}: <b>${fmt(p.y)}</b></div>`;
             });

             // Total Perfiles (suma de stack)
             html += `<div style="margin-top:4px;font-size:11px;color:#666">Total Perfiles: <b>${fmt(sumPerf)}</b></div>`;
             return html;
        }
      },
      plotOptions: {
        column: {
          stacking: 'normal',
          grouping: true,
          borderWidth: 0,
          borderRadius: 5,     // <--- ¡Clave! Bordes redondeados igual al mensual
          pointPadding: 0.15,  // Un poquito más de aire que el mensual (0.1) para que no se peguen los años
          groupPadding: 0.12,
          dataLabels: {
            enabled: true,
            // Usamos el mismo estilo que el mensual (oscuro sin borde), se lee más limpio
            style: { textOutline: 'none', fontSize: '10px', color: '#111827', fontWeight: 'bold' },
            formatter: function() {
                // 1. Si es 0, no mostrar
                if (this.y === 0) return null;

                // 2. Si la barra es muy finita (menos de 15px de alto), no mostrar para que no se encime
                if (this.point.shapeArgs && this.point.shapeArgs.height < 15) return null;

                return Highcharts.numberFormat(this.y, 0, ',', '.');
            }
          },
          states: { inactive: { opacity: 0.85 } }
        }
      },
      colors: ['#0ea5e9', '#ef4444', '#16a34a'], // Azul, Rojo, Verde
      series: [
        // PILAR 1: Denuncias
        {
            name: 'Denuncias',
            data: dataDenuncias,
            stack: 'den',
            color: '#0ea5e9'
        },
        // PILAR 2: Perfiles (Bajas + Activas)
        {
            name: 'Bajas',
            data: dataBajas,
            stack: 'perf',
            color: '#ef4444'
        },
        {
            name: 'Activas',
            data: dataActivas,
            stack: 'perf',
            color: '#16a34a'
        }
      ],
      credits: { enabled: false }
    });
  }

  function cargarEvolucionAnual() {
    const container = $('#chartEvolucionAnual');
    container.html('<div style="text-align:center;padding:40px;color:#6b7280">Procesando historia...</div>');

    // Helpers
    const toNum = (v) => {
        if (v == null) return 0;
        const s = String(v).replace(/\./g, '').replace(',', '.');
        const n = Number(s);
        return Number.isFinite(n) ? n : 0;
    };

    // Pedimos un rango de fechas muy amplio para traer TODO el historial
    // Ajustá '2020' si tenés datos más viejos.
    const anioActual = new Date().getFullYear();
    const desde = '2020-01';
    const hasta = (anioActual + 1) + '-12';

    $.ajax({
      url: '/denunciasAlea/totales-mensuales',
      dataType: 'json',
      data: {
        page: 1,
        page_size: 2000, // Un número grande para asegurar traer todos los meses de todos los años
        desde: desde,
        hasta: hasta,
        sort_by: 'anio',
        sort_dir: 'asc'
      }
    })
    .done(function (res) {
      const rows = Array.isArray(res && res.registros) ? res.registros : [];

      if(!rows.length) {
         container.html('<div style="text-align:center;padding:40px;">Sin datos históricos.</div>');
         return;
      }

      // --- LÓGICA DE AGREGACIÓN POR AÑO ---
      let acumulador = {};

      rows.forEach(r => {
          let anio = r.anio; // Ej: 2023

          if (!acumulador[anio]) {
              acumulador[anio] = { activas: 0, bajas: 0, denuncias: 0 };
          }

          // Sumamos los valores del mes al total del año
          acumulador[anio].activas   += toNum(r.activas);
          acumulador[anio].bajas     += toNum(r.bajas);
          // Usamos 'realizadas' como métrica de denuncias (ajustalo si preferís sumar 'realizadas' + 'no_realizadas')
          acumulador[anio].denuncias += toNum(r.realizadas);
      });

      // Preparamos los arrays para el gráfico
      // Obtenemos los años ordenados (keys del objeto)
      let anios = Object.keys(acumulador).sort();

      let seriesActivas = [];
      let seriesBajas = [];
      let seriesDenuncias = [];

      anios.forEach(anio => {
          seriesActivas.push(acumulador[anio].activas);
          seriesBajas.push(acumulador[anio].bajas);
          seriesDenuncias.push(acumulador[anio].denuncias);
      });

      renderChartAnual(anios, seriesActivas, seriesBajas, seriesDenuncias);
    })
    .fail(function () {
      container.html('<div style="text-align:center;padding:40px;color:#ef4444">Error al cargar histórico.</div>');
    });
  }

  window.cargarEvolucionAnual = cargarEvolucionAnual;

})();

;(function () {

  let chartImpacto = null;

  function renderChartImpacto(fechas, acumuladoBajas, acumuladoDetectadas) {
    if (chartImpacto) { chartImpacto.destroy(); chartImpacto = null; }

    if ($('#chartImpactoAcumulado').length === 0) return;

    chartImpacto = Highcharts.chart('chartImpactoAcumulado', {
      chart: { type: 'area' }, // Área para que se vea el "volumen"
      title: { text: 'Curva de Erradicación Acumulada' },
      subtitle: { text: 'Crecimiento histórico de perfiles eliminados' },
      xAxis: {
        categories: fechas,
        tickmarkPlacement: 'on',
        title: { enabled: false },
        labels: {
            step: 2, // Muestra etiqueta mes por medio para no saturar
            style: { fontWeight: 'bold' }
        }
      },
      yAxis: {
        title: { text: 'Total Acumulado' },
        labels: {
          formatter: function () { return Highcharts.numberFormat(this.value, 0, ',', '.'); }
        }
      },
      tooltip: {
        shared: true,
        split: false,
        valueDecimals: 0,
        formatter: function () {
            const fmt = n => Highcharts.numberFormat(n, 0, ',', '.');
            let s = `<b>${this.x}</b><br/>`;
            this.points.forEach(p => {
                s += `<span style="color:${p.series.color}">\u25CF</span> ${p.series.name}: <b>${fmt(p.y)}</b><br/>`;
            });
            return s;
        }
      },
      plotOptions: {
        area: {
          stacking: null, // No apilado, comparativo
          lineColor: '#ffffff',
          lineWidth: 2,
          marker: { enabled: false, symbol: 'circle', radius: 2, states: { hover: { enabled: true } } },
          fillOpacity: 0.2
        }
      },
      colors: ['#16a34a', '#3b82f6'], // Verde (Bajas), Azul (Detectadas)
      series: [
        {
          name: 'Bajas Acumuladas (Éxito)',
          data: acumuladoBajas,
          color: '#16a34a',
          fillColor: {
            linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
            stops: [
                [0, 'rgba(22, 163, 74, 0.6)'], // Verde arriba
                [1, 'rgba(22, 163, 74, 0.05)'] // Transparente abajo
            ]
          }
        },
        {
          name: 'Total Detectadas',
          data: acumuladoDetectadas,
          type: 'line', // Línea simple para referencia
          dashStyle: 'ShortDash',
          color: '#3b82f6',
          lineWidth: 1.5
        }
      ],
      credits: { enabled: false }
    });
  }

  function cargarTableroExito() {
    // 1. Indicadores visuales de carga
    $('#kpiTotalDetectadas, #kpiTotalBajas, #kpiEfectividad').text('...');
    $('#chartImpactoAcumulado').html('<div style="text-align:center;padding:50px;color:#aaa">Calculando impacto...</div>');

    // 2. Pedir TODO el historial (rango amplio)
    const anioActual = new Date().getFullYear();
    // Podés ajustar '2022-01' a la fecha de inicio real del sistema
    const desde = '2022-01';
    const hasta = (anioActual + 1) + '-12';

    // Helper numérico
    const toNum = (v) => {
        const n = parseFloat(String(v).replace(/\./g, '').replace(',', '.'));
        return isNaN(n) ? 0 : n;
    };

    // Helper formato miles
    const fmt = (n) => n.toLocaleString('es-AR');

    $.ajax({
      url: '/denunciasAlea/totales-mensuales',
      dataType: 'json',
      data: {
        page: 1,
        page_size: 5000, // Traer todo
        desde: desde,
        hasta: hasta,
        sort_by: 'anio', // Orden cronológico fundamental para acumular
        sort_dir: 'asc'
      }
    })
    .done(function (res) {
      let rows = Array.isArray(res && res.registros) ? res.registros : [];

      // Ordenar cronológicamente (Año -> Mes)
      rows.sort((a, b) => (toNum(a.anio) - toNum(b.anio)) || (toNum(a.mes) - toNum(b.mes)));

      // --- LÓGICA DE ACUMULADOS ---
      let totalDetectadas = 0;
      let totalBajas = 0;

      let serieFechas = [];
      let serieBajasAcum = [];
      let serieDetectadasAcum = [];

      rows.forEach(r => {
          // Sumar lo del mes actual
          let identMes = toNum(r.total_identificadas);

          // OJO: Si 'total_identificadas' es un snapshot del mes (cuantas había ese mes)
          // y no "nuevas", esta lógica cambia. Asumo que 'total_identificadas' son las
          // que se cargaron/detectaron EN ese mes.
          // Si no tenés ese dato, podrías usar (bajas + activas + realizadas) como proxy.

          // Alternativa si total_identificadas no es incremental:
          // identMes = toNum(r.bajas) + toNum(r.activas);

          let bajasMes = toNum(r.bajas);

          totalDetectadas += identMes;
          totalBajas      += bajasMes;

          // Guardar puntos para el gráfico
          // Label mes/año corto (ej: 04/24)
          let label = String(r.mes).padStart(2,'0') + '/' + String(r.anio).slice(-2);

          serieFechas.push(label);
          serieBajasAcum.push(totalBajas);
          serieDetectadasAcum.push(totalDetectadas);
      });

      // --- ACTUALIZAR KPIs (Tarjetas) ---
      $('#kpiTotalDetectadas').text(fmt(totalDetectadas));
      $('#kpiTotalBajas').text(fmt(totalBajas));

      // Cálculo de efectividad
      let efectividad = 0;
      if (totalDetectadas > 0) {
          efectividad = (totalBajas / totalDetectadas) * 100;
      }
      // Si por alguna razón las bajas superan detectadas (por datos viejos), topear en 100
      if (efectividad > 100) efectividad = 100;

      $('#kpiEfectividad').text(efectividad.toFixed(1) + '%');

      // Color dinámico para la efectividad
      if (efectividad > 80) $('#kpiEfectividad').css('color', '#16a34a'); // Verde
      else if (efectividad > 50) $('#kpiEfectividad').css('color', '#f59e0b'); // Amarillo
      else $('#kpiEfectividad').css('color', '#ef4444'); // Rojo

      // --- RENDERIZAR GRÁFICO ---
      renderChartImpacto(serieFechas, serieBajasAcum, serieDetectadasAcum);

    })
    .fail(function () {
       $('#chartImpactoAcumulado').html('Error cargando datos.');
    });
  }

  window.cargarTableroExito = cargarTableroExito;

})();
})();




// GENERALESSSSSS


$(function(){
  $('[data-js-tabs] a').on('click', function(e){
    e.preventDefault();

    const selector = $(this).data('js-tab');

    $('[data-js-tabs] a').removeClass('active');
    $(this).addClass('active');

    $('[id^="pant_"]').hide();
    $(selector).show();

    if (selector === '#pant_denunciasAlea_paginas') {
      cargardenunciasAlea_paginas({ page: 1, perPage: 10});

              attachYYYYMMDDFormatter('#fecha_denunciasAlea_paginasPres');

              attachYYYYMMDDFormatter('#FDesdeDia');
              attachYYYYMMDDFormatter('#FHastaDia');

              $('#fecha_denunciasAlea_paginasDesdeDia').datetimepicker({
                language:  'es',
                todayBtn:  1,
                autoclose: 1,
                todayHighlight: 1,
                format: 'yyyy-mm-dd',
                pickerPosition: "bottom-left",
                startView: 2,
                minView: 2,
                ignoreReadonly: true,
                timePicker: false,
                container:$('main section')
              });

              $('#fecha_denunciasAlea_paginasHastaDia').datetimepicker({
                language:  'es',
                todayBtn:  1,
                autoclose: 1,
                todayHighlight: 1,
                format: 'yyyy-mm-dd',
                pickerPosition: "bottom-left",
                startView: 2,
                minView: 2,
                ignoreReadonly: true,
                timePicker: false,
                container:$('main section')
              });

                $('#fechadenunciasAlea_paginasPres').datetimepicker({
                language:  'es',
                todayBtn:  1,
                autoclose: 1,
                todayHighlight: 1,
                format: 'yyyy-mm-dd',
                pickerPosition: "bottom-left",
                startView: 2,
                minView: 2,
                ignoreReadonly: true,
                timePicker: false,
                container:$('main section')
              });


        }
    else if(selector === "#pant_denunciasAlea_obsYDen"){
      // Hook al cambiar de pestaña
        // Formateadores yyyy-mm
        attachYYYYMMFormatter('#inp_obsYDenDesde');
        attachYYYYMMFormatter('#inp_obsYDenHasta');

        // DateTimePickers (mismo plugin que ya usás)
        $('#fecha_obsYDenDesde,#fecha_obsYDenHasta').datetimepicker({
          language: 'es',
          todayBtn: 1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'yyyy-mm',
          startView: 3, minView: 3,
          ignoreReadonly: true,
          timePicker: false,
          container: $('main section')
        });
      cargarObsYDen({ page: 1, perPage: 10 });
}
    else if(selector === "#pant_pagActivas"){
      cargarPagActivas({ page: 1, perPage: 'all' });
    }
    else if(selector === "#pant_estadisticas"){
      if(window.cargarTableroExito) {
        window.cargarTableroExito();
    }
      cargarChartActivasBajas();   //
        cargarPiePlataformas(); // ← agregá esta línea
        if(window.cargarGraficosSecundarios) {
           window.cargarGraficosSecundarios();
       }
       if(window.cargarTopPerfiles) {
       window.cargarTopPerfiles();
   }
   if(window.cargarDistribucionEstados) {
    window.cargarDistribucionEstados();
}
if(window.cargarEvolucionAnual) {
    window.cargarEvolucionAnual();
}
    }

  });
// Inline Editing Cantidad Denuncias
$(document).on('dblclick', '.editable-cant', function() {
    var $cell = $(this);
    if ($cell.find('input').length > 0) return; // Ya en edición
    
    var currentText = $cell.text().trim();
    var currentVal = (currentText === '-' || currentText === '') ? 0 : currentText;
    
    // Convertir a input
    var $input = $('<input>', {
        type: 'number',
        value: currentVal,
        class: 'form-control input-sm',
        style: 'width: 100%; text-align: center;'
    });
    
    $cell.data('original', currentText);
    $cell.empty().append($input);
    $input.focus().select();
});

$(document).on('blur', '.editable-cant input', function() {
    saveCantDenuncia($(this));
});

$(document).on('keypress', '.editable-cant input', function(e) {
    if (e.which === 13) { // Enter
        $(this).blur();
    }
});

function saveCantDenuncia($input) {
    var $cell = $input.closest('td');
    var id    = $cell.data('id');
    var newVal = $input.val();
    
    // Validación básica
    if (newVal === '') { newVal = 0; }
    
    $.ajax({
        url: '/denunciasAlea/modificar-cantidad/' + id,
        type: 'POST',
        data: { cantidad: newVal },
        success: function(res) {
            if (res.success) {
                $cell.text(newVal);
            } else {
                $cell.text($cell.data('original'));
                alert('Error al guardar: ' + (res.message || 'Desconocido'));
            }
        },
        error: function(err) {
            console.error(err);
            $cell.text($cell.data('original'));
            alert('Error de conexión al guardar.');
        }
    });
}

  $('[data-js-tabs] a').first().trigger('click');
});

// -----------------------------------------------------------------------------
// SECCIÓN PÁGINAS ACTIVAS (IMPORTADOR CSV)
// -----------------------------------------------------------------------------

var gPaginasActivasData = [];

// Mostrar nombre de archivo seleccionado
$(document).on('change', '#importarPaginasActivasInput', function() {
    var fileName = $(this).val().split('\\').pop();
    $('#nombreArchivoSel').text(fileName || 'Ningún archivo seleccionado');
});

$(document).on('click', '#btn-importar-pagActivas', function(e){
  e.preventDefault();
  var input = $('#importarPaginasActivasInput')[0];
  if (!input.files || !input.files[0]) {
      alert('Por favor, selecciona un archivo CSV.');
      return;
  }

  var formData = new FormData();
  formData.append('archivo', input.files[0]);

  $('#cuerpoTablaPagActivas').html('<tr><td colspan="6" class="text-center">Importando y procesando...</td></tr>');
  $('#mensajeImportacion').text('');
  
  // Deshabilitar botones de acciones
  $('#btn-baja-inactivas, #btn-descargarPagActivasExcel, #btn-descargarPagActivasPDF').prop('disabled', true);

  $.ajax({
      url: '/denunciasAlea/importar-csv-paginas-activas',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(res) {
          gPaginasActivasData = res.registros || [];
          renderTablaPagActivas(gPaginasActivasData);
          $('#mensajeImportacion').text('Se importaron ' + gPaginasActivasData.length + ' registros.');
          
          if (gPaginasActivasData.length > 0) {
               $('#btn-baja-inactivas, #btn-descargarPagActivasExcel, #btn-descargarPagActivasPDF').prop('disabled', false);
          }
      },
      error: function() {
          $('#cuerpoTablaPagActivas').html('<tr><td colspan="6" class="text-center text-danger">Error al importar el archivo.</td></tr>');
      }
  });
});

function renderTablaPagActivas(data) {
  var $tbody = $('#cuerpoTablaPagActivas').empty();
  
  if (!data || data.length === 0) {
      $tbody.html('<tr><td colspan="6" class="text-center">No hay datos.</td></tr>');
      return;
  }

  data.forEach(function(item) {
      // item: {id_temp, usuario, url, estado, detalle, plataforma}
      var isInactive = String(item.estado || '').toLowerCase().includes('inactiv');
      var cssClass = isInactive ? 'warning' : ''; 
      
      var $tr = $('<tr>').addClass(cssClass).attr('data-id-temp', item.id_temp);
      
      $tr.append($('<td class="text-center">').append(
          $('<input>', {type: 'checkbox', class: 'check-pag-activa', 'data-id-temp': item.id_temp})
      ));
      $tr.append($('<td>').text(item.fecha || '-'));
      $tr.append($('<td>').text(item.usuario || '-'));
      
      var urlDisplay = item.url ? $('<a>', {href: item.url, target:'_blank'}).text(item.url) : '-';
      $tr.append($('<td>').html(urlDisplay));
      
      $tr.append($('<td>').text(item.estado || '-'));
      $tr.append($('<td>').text(item.detalle || '-'));
      $tr.append($('<td>').text(item.plataforma || '-'));
      
      $tbody.append($tr);
  });
}

$(document).on('change', '#checkAllPagActivas', function() {
    $('.check-pag-activa').prop('checked', $(this).is(':checked'));
});

// Botón "Dar de baja a paginas inactivas" (Modo AUTOMÁTICO con confirmación en Modal)
var gItemsBaja = [];

$(document).on('click', '#btn-baja-inactivas', function(e) {
    e.preventDefault();
    
    // Filtrar automáticamente las Inactivas
    gItemsBaja = gPaginasActivasData.filter(function(item) {
        return String(item.estado || '').toLowerCase().includes('inactiv');
    });
    
    if (gItemsBaja.length === 0) {
        alert('No se encontraron páginas con estado "Inactivo" en la lista importada.');
        return;
    }
    
    // Poblar modal
    $('#cant_baja').text(gItemsBaja.length);
    var $ul = $('#lista_baja').empty();
    
    gItemsBaja.forEach(function(item) {
        var txt = (item.usuario || 'S/Usr') + ' - ' + (item.url || 'S/Url');
        $ul.append($('<li>').text(txt));
    });
    
    // Mostrar modal
    $('#modalBajaPaginas').modal('show');
});

// Confirmación en Modal
$(document).on('click', '#btn-confirmar-baja', function(e) {
    if (gItemsBaja.length === 0) return;
    
    // Deshabilitar para evitar doble click
    var $btn = $(this).prop('disabled', true).text('Procesando...');
    
    $.ajax({
        url: '/denunciasAlea/dar-baja-paginas-inactivas',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ items: gItemsBaja }),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(res) {
            $btn.prop('disabled', false).text('Confirmar Baja');
            $('#modalBajaPaginas').modal('hide');
            
            if (res.success) {
                alert('Proceso finalizado. Se han dado de baja ' + res.updated_count + ' registros.');
                
                // Opcional: Remover los ítems procesados de la tabla visualmente
                // O marcarlos como "Procesado"
                // Vamos a eliminarlos de la lista visual para que no se puedan volver a procesar
                var probIds = gItemsBaja.map(function(i){ return i.id_temp; });
                gPaginasActivasData = gPaginasActivasData.filter(function(item){
                    return !probIds.includes(item.id_temp);
                });
                renderTablaPagActivas(gPaginasActivasData);
                $('#mensajeImportacion').text('Quedan ' + gPaginasActivasData.length + ' registros sin procesar.');
                
            } else {
                alert('Ocurrió un error: ' + (res.message || 'Error desconocido'));
            }
        },
        error: function(xhr) {
            $btn.prop('disabled', false).text('Confirmar Baja');
            alert('Error al procesar la solicitud.');
            console.error(xhr);
        }
    });
});


// Botones Exportar
$(document).on('click', '#btn-descargarPagActivasExcel', function(e) {
    e.preventDefault();
    exportarPaginasActivas('xlsx');
});

$(document).on('click', '#btn-descargarPagActivasPDF', function(e) {
    e.preventDefault();
    exportarPaginasActivas('pdf');
});

function exportarPaginasActivas(format) {
    var selectedIds = $('.check-pag-activa:checked').map(function() {
        return $(this).data('id-temp'); // esto devuelve number o string del data-attr
    }).get();

    var dataToExport = [];
    if (selectedIds.length > 0) {
        dataToExport = gPaginasActivasData.filter(function(item) {
            return selectedIds.includes(item.id_temp) || selectedIds.includes(String(item.id_temp));
        });
    } else {
        alert('Por favor, selecciona los registros que deseas exportar.');
        return;
    }
    
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/denunciasAlea/exportar-listado-temporal';
    form.target = '_blank';

    var token = $('meta[name="csrf-token"]').attr('content');
    
    var inputToken = document.createElement('input');
    inputToken.type = 'hidden';
    inputToken.name = '_token';
    inputToken.value = token;
    form.appendChild(inputToken);
    
    var inputFormat = document.createElement('input');
    inputFormat.type = 'hidden';
    inputFormat.name = 'format';
    inputFormat.value = format;
    form.appendChild(inputFormat);
    
    dataToExport.forEach(function(item, index) {
        Object.keys(item).forEach(function(key) {
           var i = document.createElement('input');
           i.type = 'hidden';
           name = 'data[' + index + '][' + key + ']'; // name must be defined properly
           i.name = name;
           i.value = item[key];
           form.appendChild(i);
        });
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
