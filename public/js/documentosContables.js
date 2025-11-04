$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
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

function _normAli(num){
  if (num == null ||   !isFinite(num)) return 0;
  return (Math.abs(num) > 1.000001) ? (num / 100) : num;
}

/**
 * Calcula % en vivo: porcentaje = (parte / base) * 100
 * opts = {
 *   base:    '#idBase',        // denominador
 *   parte:   '#idParte',       // numerador
 *   target:  '#idPorcentaje',  // salida (ej: alícuota)
 *   decimales: 2,              // cantidad de decimales a mostrar
 *   blankIfZeroBase: true      // si base = 0, deja vacío el target
 * }
 */
function instalarAutoPorcentajeAR(opts){
  var o = $.extend({
    base: null,
    parte: null,
    target: null,
    decimales: 2,
    blankIfZeroBase: true
  }, opts || {});
  if (!o.base || !o.parte || !o.target) return;

  function leerNum(sel){
    var $el = $(sel);
    var n = $el.data('num');
    if (n == null){
      var p = parseNumeroFlexible($el.val());
      n = p ? p.num : null;
    }
    return n;
  }

  function recalc(){
    var base  = leerNum(o.base);
    var parte = leerNum(o.parte);

    var pct = null;
    if (base != null && Math.abs(base) > 0){
      pct = (Number(parte || 0) / Number(base)) * 100;
    }

    if (pct == null && o.blankIfZeroBase){
      $(o.target).val('').data('num', null).trigger('num:changed', [null]);
    } else {
      $(o.target)
        .val(formatoAR(pct || 0, o.decimales))
        .data('num', pct || 0)
        .trigger('num:changed', [pct || 0]);
    }
  }

  $(document).on('input change num:changed', [o.base, o.parte].join(','), recalc);

  recalc();
}


 /**
  * Calcula impuesto (= base * alícuota)
  * options = {
  *   base: '#idBase',
  *   alicuota: '#idAlicuota',
  *   impuesto: '#idImpuesto',
  *   total: '#idTotal' (opcional),
  *   decBase: 2, decImp: 2, decTotal: 2,
  *   aliEsPorcentaje: true   // ⬅️ NUEVO: 6 -> 6%, 0,95 -> 0,95%
  * }
  */
 function instalarAutoImpuestoAR(options){
   var o = $.extend({
     base: null, alicuota: null, impuesto: null, total: null,
     decBase: 2, decImp: 2, decTotal: 2,
     aliEsPorcentaje: true
   }, options || {});
   if (!o.base || !o.alicuota || !o.impuesto) return;

   function leerNum(sel){
     var $el = $(sel);
     var n = $el.data('num');
     if (n == null) {
       var p = parseNumeroFlexible($el.val());
       n = p ? p.num : null;
     }
     return n;
   }

   function recalc(){
     var base   = leerNum(o.base) || 0;
     var aliRaw = leerNum(o.alicuota) || 0;
     var ali    = o.aliEsPorcentaje ? (aliRaw / 100) : aliRaw;
     var imp    = base * ali;
     var tot    = base + imp;

     $(o.impuesto).val(formatoAR(imp, o.decImp)).data('num', imp).trigger('num:changed', [imp]);
     if (o.total){
       $(o.total).val(formatoAR(tot, o.decTotal)).data('num', tot).trigger('num:changed', [tot]);
     }
   }

   $(document).on('input change num:changed', [o.base, o.alicuota].join(','), recalc);
   recalc();
 }


 function _parseUltimoPuntoDecimal(str){
   if (str == null) return null;
   var s = String(str);

   s = s.replace(/[^\d.\-]/g, '');
   s = s.replace(/(?!^)-/g, '');
   if (!s) return null;

   var lastDot = s.lastIndexOf('.');
   var numStr;
   if (lastDot >= 0){
     var left  = s.slice(0, lastDot).replace(/\./g, '');
     var right = s.slice(lastDot + 1).replace(/\./g, '');
     numStr = left + (right ? '.' + right : '');
   } else {
     numStr = s.replace(/\./g, '');
   }

   var n = Number(numStr);
   return isFinite(n) ? n : null;
 }

 function _smartParseNum(v){
   if (v == null) return null;
   var s = String(v).trim();
   if (!s) return null;

   if (s.indexOf(',') !== -1){
     var p = parseNumeroFlexible(s);
     return p ? p.num : null;
   }
   return _parseUltimoPuntoDecimal(s);
 }
 function instalarNumeroFlexibleAR(selector, opts){
   var o = $.extend({ decimales: 2, permitirNegativos: false }, opts||{});

   $(document).off('input.numflex blur.numflex paste.numflex focus.numflex', selector);

   $(document).on('focus.numflex', selector, function(){
     $(this).removeData('normalized');
   });

   $(document).on('input.numflex', selector, function(){
     $(this).removeData('normalized');

     var n = _smartParseNum(this.value);
     if (n != null && !o.permitirNegativos && n < 0) n = Math.abs(n);
     $(this).data('num', n).trigger('num:changed', [n]);
   });

   $(document).on('paste.numflex', selector, function(){
     var el=this; setTimeout(function(){ $(el).trigger('input'); }, 0);
   });

   $(document).on('blur.numflex', selector, function(){
     var $el = $(this);

     if ($el.data('normalized') === true){
       var nCanon = _smartParseNum(this.value);
       if (nCanon != null && !o.permitirNegativos && nCanon < 0) nCanon = Math.abs(nCanon);
       $el.data('num', nCanon).trigger('num:changed', [nCanon]);
       return;
     }

     var n = _smartParseNum(this.value);
     if (n == null){
       $el.data('num', null).trigger('num:changed', [null]);
       return;
     }
     if (!o.permitirNegativos && n < 0) n = Math.abs(n);

     var formatted = formatoAR(n, o.decimales);
     if (this.value !== formatted){
       this.value = formatted;
     }
     $el.data('num', n).trigger('num:changed', [n]);
   });
 }


function instalarAutoSumaAR(opts){
  var o = $.extend({ sources: [], target: null, decimales: 2 }, opts||{});
  if (!o.target || !o.sources.length) return;

  function leerNum($el){
    var n = $el.data('num');
    if (n == null) {
      var p = parseNumeroFlexible($el.val());
      n = p ? p.num : 0;
    }
    return Number(n) || 0;
  }

  function signoDe(sel){
    return /^\s*-/.test(sel) ? -1 : 1;
  }
  function limpio(sel){
    return String(sel).replace(/^\s*[+-]\s*/, '');
  }

  function recalc(){
    var total = 0;
    o.sources.forEach(function(s){
      var sgn = signoDe(s);
      var sel = limpio(s);
      var $els = $(sel);
      if (!$els.length) return;
      $els.each(function(){ total += sgn * leerNum($(this)); });
    });
    $(o.target).val( formatoAR(total, o.decimales) )
               .data('num', total)
               .trigger('num:changed', [total]);
  }

  var triggers = Array.from(new Set(o.sources.map(limpio))).join(',');
  if (triggers) $(document).on('input change num:changed', triggers, recalc);

  recalc();
}


function parseNumeroFlexible(input) {
  if (input == null) return null;
  let s = String(input).trim();
  if (!s) return null;

  const neg = /^\s*-/.test(s);
  s = s.replace(/[^\d.,]/g, '');

  s = s.replace(/\./g, '');
  const lastComma = s.lastIndexOf(',');
  if (lastComma !== -1) {
    s = s.slice(0, lastComma).replace(/,/g, '') + '.' + s.slice(lastComma + 1).replace(/,/g, '');
  }

  const num = Number((neg ? '-' : '') + s);
  if (!isFinite(num)) return null;

  return { num, str: String(num).includes('.') ? String(num) : String(Math.trunc(num)) };
}

function formatoAR(value, decs = null) {
  if (value === null || value === undefined || value === '') return '';
  const n = Number(value);
  if (!isFinite(n)) return '';
  const frac = (decs === null)
    ? ((String(value).split('.')[1] || '').length)
    : decs;
  return n.toLocaleString('es-AR', {
    minimumFractionDigits: frac,
    maximumFractionDigits: frac
  });
}


function setDateSmart($group, val){
  var v = (val||'').trim();
  var $inp = $group.find('input');
  if(!v){
    $inp.val('').trigger('input').trigger('change');
    var dp = $group.data('datepicker');
    if(dp && $group.datepicker) $group.datepicker('clearDates');
    var dtp = $group.data('DateTimePicker');
    if(dtp && dtp.clear) dtp.clear();
    return;
  }
  $inp.val(v).trigger('input').trigger('change');

  var dtp = $group.data('DateTimePicker');
  if(dtp && dtp.date){
    var fmt = (v.length===7?'YYYY-MM':'YYYY-MM-DD');
    var m = window.moment ? moment(v.length===7 ? (v+'-01') : v, 'YYYY-MM-DD') : null;
    if(m) dtp.date(m);
  }

  var dp = $group.data('datepicker');
  if(dp && $group.datepicker){
    var d = new Date(v.length===7 ? (v+'-01') : v);
    $group.datepicker('setDate', d);
    $group.datepicker('update');
  }
}

function iconoExt(nombre){
  var n = (nombre||'').toLowerCase();
  if(/\.(pdf)$/.test(n)) return '📄';
  if(/\.(xlsx?|csv)$/.test(n)) return '📊';
  if(/\.(docx?|rtf|txt)$/.test(n)) return '📝';
  if(/\.(png|jpe?g|gif|bmp|webp|svg)$/.test(n)) return '🖼️';
  if(/\.(zip|rar|7z|tar|gz)$/.test(n)) return '🗜️';
  return '📎';
}

function buildArchivoRow(item, registro){
  var fid    = item.id || item.id_registro_archivo || item.pk || item.file_id;
  var nombre = item.nombre || item.archivo || (item.path ? String(item.path).split('/').pop() : 'archivo');

  var href = '/documentosContables/visualizarArchivo/'+ registro +'/' + encodeURIComponent(nombre);

  var $row  = $('<div class="list-group-item">');

  var $icon = $('<i>')
                .attr('class', faIconClassByExt(nombre))
                .css({marginRight:'8px', fontSize:'16px', lineHeight:'1'});

  var $link = $('<a target="_blank">')
                .attr('href', href)
                .text(nombre);

  var $del  = $('<button type="button" class="btn btn-xs btn-danger btn-del-archivo" title="Quitar">')
                .attr('data-id', fid)
                .css({float:'right'})
                .append('<i class="fa fa-trash"></i>');

  $row.append($icon).append($link).append($del);
  return $row;
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

function populateSelect($select, items, valueKey, textKey, placeholder){
  var html = '<option value="">' + (placeholder || 'Seleccioná...') + '</option>';
  for(var i=0;i<items.length;i++){
    var it = items[i];
    html += '<option value="'+ it[valueKey] +'">'+ (it[textKey] || '') +'</option>';
  }
  $select.html(html);
}

function validarCampoNumSiHayValor($input, contexto, mensaje, valid){
  if (!$input || !$input.length) return valid ? 1 : 0;
  var v = $input.val();
  if (v == null || String(v).trim() === '') return valid ? 1 : 0;
  return validarCampoNum($input, contexto, mensaje, valid);
}


function validarCampoNum(selector, contexto, mensaje, valid) {
  var $input = $(selector);
  var valor = ($input.val() || '').trim();

  var $ctx = $input.closest(contexto);
  $ctx.removeClass('has-error').find('.help-block.js-error').remove();

  if (!valor) {
    $ctx.addClass('has-error')
        .append('<span class="help-block js-error">' + mensaje + '</span>');
    return 0;
  }

  var hasComma = valor.indexOf(',') !== -1;
  var hasDot   = valor.indexOf('.') !== -1;
  var forceNormalize = hasComma && hasDot;

  if ($input.data('normalized') === true && !forceNormalize) {
    var n = Number(($input.val() || '').trim());
    if (!isFinite(n)) {
      $ctx.addClass('has-error')
          .append('<span class="help-block js-error">Introduce un número válido</span>');
      return 0;
    }
    return valid ? 1 : 0;
  }

  var parsed = parseNumeroFlexible(valor);
  if (!parsed) {
    $ctx.addClass('has-error')
        .append('<span class="help-block js-error">Introduce un número válido</span>');
    return 0;
  }

  $input.val(parsed.str);
  $input.data('normalized', true);

  if (!valid) return 0;
  return 1;
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


$(document).on('click', '#btn-eliminarArchivo', function(){
  const $modal = $('#modalEliminarArchivo');

  const fileId = $modal.data('id');
  const regId  = $modal.data('regId');
  const scope  = $modal.data('scope');


  $.getJSON('/documentosContables/eliminarArchivo', { id: fileId })
    .done(function(r){
      if (r && r.success) {
        const reloaders = {
          Iva:  (id) => cargarArchivosIvaLista(id),
          iibb: (id) => cargarArchivosiibbLista(id),
          DREI: (id) => cargarArchivosDREILista(id),
          TGI: (id) => cargarArchivosTGILista(id),
          IMP_AP_MTM: (id) => cargarArchivosIMP_AP_MTMLista(id),
          IMP_AP_OL: (id) => cargarArchivosIMP_AP_OLLista(id),
          Ganancias: (id) => cargarArchivosGananciasLista(id),
          Ganancias_periodo: (id) => cargarArchivosGanancias_periodoLista(id),
          Patentes: (id) => cargarArchivosPatentesLista(id),
          ImpInmobiliario: (id) => cargarArchivosImpInmobiliarioLista(id),
          ContribEnteTuristico: (id) => cargarArchivosContribEnteTuristicoLista(id),
          DerechoAcceso: (id) => cargarArchivosDerechoAccesoLista(id),
          DeudaEstado: (id) => cargarArchivosDeudaEstadoLista(id),
          AutDirectores: (id) => cargarArchivosAutDirectoresLista(id),
          PremiosMTM: (id) => cargarArchivosPremiosMTMLista(id),
          PromoTickets: (id) => cargarArchivosPromoTicketsLista(id),
          PozosAcumuladosLinkeados: (id) => cargarArchivosPozosAcumuladosLinkeadosLista(id),
          JackpotsPagados: (id) => cargarArchivosJackpotsPagadosLista(id),
          PremiosPagados: (id) => cargarArchivosPremiosPagadosLista(id),
          PagosMayoresMesas: (id) => cargarArchivosPagosMayoresMesasLista(id),
          RegistrosContables: (id) => cargarArchivosRegistrosContablesLista(id),
          AportesPatronales: (id) => cargarArchivosAportesPatronalesLista(id),
          RRHH: (id) => cargarArchivosRRHHLista(id),
          ReporteYLavado: (id) => cargarArchivosReporteYLavadoLista(id),
          Seguros: (id) => cargarArchivosSegurosLista(id),

        };

        if (reloaders[scope]) {
          reloaders[scope](regId);
        } else {
          $(`[data-archivo-id="${fileId}"]`).remove();
        }

        $('#modalEliminarArchivo').modal('hide').removeData('id regId scope');
      } else {
        alert('No se pudo eliminar');
      }
    })
    .fail(function(){
      alert('Error al eliminar');
    });
});



$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Documentos Contables');



});




//IIBB



$(function () {
  instalarNumeroFlexibleAR([
    '.js-num-ar',
    '#dif_miniibb',
    '#deduccionesiibb',
    '#saldo_iibb',
    '#total_impuesto_iibb',
  ].join(', '), { decimales: 2 });

  instalarAutoSumaAR({
    sources: ['.iibb-imp'],
    target:  '#total_impuesto_iibb',
    decimales: 2
  });

  instalarAutoSumaAR({
    sources: ['#total_impuesto_iibb','#dif_miniibb','-#deduccionesiibb'],
    target:  '#saldo_iibb',
    decimales: 2
  });
});


function cargarArchivosiibbLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('iibbId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosiibb/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/iibb/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="iibb"  class="btn btn-sm btn-danger btn-del-archivo-iibb" title="Quitar">')
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

$(document).on('click', '.btn-archivos-iibb', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro IIBB');
  cargarArchivosiibbLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-iibb', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});


function appendIibbFilaEditar(b, index, container){
  container = container || '#contenedor-inputs-iibb-cargar';

  var id   = (b && b.id != null) ? b.id : '';
  var obs  = (b && b.obs) ? b.obs : '';

  var montoNum = (b && b.monto != null)    ? (parseNumeroFlexible(b.monto)    ? parseNumeroFlexible(b.monto).num    : null) : null;
  var aliNum   = (b && b.alicuota != null) ? (parseNumeroFlexible(b.alicuota) ? parseNumeroFlexible(b.alicuota).num : null) : null;
  var impNum   = (b && b.imp != null)      ? (parseNumeroFlexible(b.imp)      ? parseNumeroFlexible(b.imp).num      : null) : null;

  if (impNum == null && montoNum != null && aliNum != null){
    impNum = montoNum * (aliNum / 100);
  }

  var montoFmt = (montoNum != null) ? formatoAR(montoNum, 2) : '';
  var aliFmt   = (aliNum   != null) ? formatoAR(aliNum,   2) : '';
  var impFmt   = (impNum   != null) ? formatoAR(impNum,   2) : '';

  var montoId = 'monto_iibb_'    + index;
  var aliId   = 'alicuota_iibb_' + index;
  var impId   = 'imp_iibb_'      + index;

  var html = ''
    + '<div class="bases-iibb-cargar" data-id="'+ index +'" data-base-id="'+ id +'">'
    + '  <div class="row">'
    + '    <div class="col-md-3"><h5>Base imponible...</h5></div>'
    + '    <div class="col-md-3"><h5>Monto</h5></div>'
    + '    <div class="col-md-2"><h5>Alicuota (%)</h5></div>'
    + '    <div class="col-md-3"><h5>Impuesto Determinado</h5></div>'
    + '    <div class="col-md-1"></div>'
    + '  </div>'
    + '  <div class="row">'
    + '    <div class="col-md-3">'
    + '      <textarea name="base[]" class="form-control" rows="2" maxlength="4000"'
    + '        placeholder="Completar con el concepto">'+ obs +'</textarea>'
    + '    </div>'
    + '    <div class="col-md-3">'
    + '      <input type="text" id="'+ montoId +'" name="monto[]" class="form-control js-num-ar iibb-monto"'
    + '             placeholder="$" inputmode="decimal" value="'+ montoFmt +'">'
    + '    </div>'
    + '    <div class="col-md-2">'
    + '      <input type="text" id="'+ aliId +'" name="alicuota[]" class="form-control js-num-ar iibb-ali"'
    + '             placeholder="%" inputmode="decimal" value="'+ aliFmt +'">'
    + '    </div>'
    + '    <div class="col-md-3">'
    + '      <input type="text" id="'+ impId +'" name="impuesto[]" class="form-control js-num-ar iibb-imp"'
    + '             inputmode="decimal" value="'+ impFmt +'" >'
    + '    </div>'
    + '    <div class="col-md-1">'
    + '      <button type="button" class="btn btn-danger btn-sm eliminar-bloque-iibb-cargar">X</button>'
    + '    </div>'
    + '  </div>'
    + '</div>';

  $(container).append(html);

  instalarNumeroFlexibleAR('#'+montoId+', #'+aliId+', #'+impId, { decimales: 2 });
  instalarAutoImpuestoAR({
    base: '#'+montoId,
    alicuota: '#'+aliId,
    impuesto: '#'+impId,
    aliEsPorcentaje: true,
    decImp: 2
  });

  $('#'+montoId).trigger('input');
}


function abrirModaliibbEditar(id){
  resetFormiibb();
  $('#iibb_modo').val('edit');
  $('#id_registroiibb').val(id);
  $('#modalCargariibb .modal-title').text('| EDITAR REGISTRO DE IIBB');
  $('#guardarRegistroiibb').text('ACTUALIZAR');
  $('#modalCargariibb').modal('show');

  $.getJSON('/documentosContables/llenariibbEdit/'+id, function(d){
    const ym  = String(d.fecha || '').slice(0,7);
    const ymd = String(d.fecha_pres || '').slice(0,10);

    $('#fechaiibb input[name="fecha_iibb"]').val(ym).trigger('change');
    $('#fechaiibbPres input[name="fecha_iibbPres"]').val(ymd).trigger('change');
    $('#casinoiibb').val(d.casino).trigger('change');

    $('#dif_miniibb').val(formatoAR(d.diferencia ?? ''));
    $('#deduccionesiibb').val(formatoAR(d.deducciones ?? ''));
    $('#saldo_iibb').val(formatoAR(d.saldo));
    $('#total_impuesto_iibb').val(formatoAR(d.impuesto_total));

    $('#obsiibb').val(d.obs || '');

    const $cont = $('#contenedor-inputs-iibb-cargar').empty();
    const bases = Array.isArray(d.bases) ? d.bases : [];
    let idx = 0;
    if (bases.length) {
      bases.forEach(b => appendIibbFilaEditar(b, ++idx, '#contenedor-inputs-iibb-cargar'));
    } else {
      appendIibbFilaEditar({}, ++idx, '#contenedor-inputs-iibb-cargar');
    }

    $('.iibb-imp').first().trigger('input');

    $(document).trigger('iibb:set-contador', [idx]);
  })
  .fail(function(xhr){
    console.error('[iibb editar] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro de IIBB.');
  });
}


function resetFormiibb(){
  var $f = $('#formNuevoRegistroiibb');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameiibb').val('No se ha seleccionado ningún archivo');
  $('#fileListiibb').empty();
  $('#uploadiibb').val('');
  $('#uploadsiibbContainer').empty();
    $('#uploadsiibbTable tbody').empty();
    $('#uploadsiibbWrap').hide();
    $('#fileNameiibb').val('No se ha seleccionado ningún archivo');
  }

function abrirModaliibbCrear(){
  $('#iibb_modo').val('create');
  $('#id_registroiibb').val('');
  $('#modalCargariibb .modal-title').text('| NUEVO REGISTRO DE IIBB');
  $('#guardarRegistroiibb').text('GENERAR');
  $('#modalCargariibb').modal('show');
}


$(document).on('click','#iibb_nuevo',function(){
  abrirModaliibbCrear();
});

$(document).on('click','.btn-edit-iibb',function(){
  var id = $(this).data('id');
  abrirModaliibbEditar(id);
});


$(document).on('click','#guardarRegistroiibb',function(e){
  var $form = $('#formNuevoRegistroiibb');
  var modo = $('#iibb_modo').val() || 'create';
  var id   = $('#id_registroiibb').val() || '';
  let valid = true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();


  valid = validarCampo('select[name="casinoiibb"]','.col-md-4','El casino es requerido.',valid);

  if ($('#contenedor-inputs-iibb-cargar .bases-iibb-cargar').length === 0) {
    $('#agregar-bloque-iibb-cargar').trigger('click');
    valid = 0;
  }


$('#contenedor-inputs-iibb-cargar .bases-iibb-cargar').each(function () {
      const $fila = $(this);
      const $base = $fila.find('textarea[name="base[]"]');
      const $monto = $fila.find('input[name="monto[]"]');
      const $alicuota = $fila.find('input[name="alicuota[]"]');
      const $imp = $fila.find('input[name="impuesto[]"]');

      valid = validarCampo($base,'.col-md-3','La base es requerida.',valid);
      valid = validarCampoNum($monto,'.col-md-3','El monto es requerido',valid);
      valid = validarCampoNum($alicuota,'.col-md-2','La alicuota es requerida.',valid);
      valid = validarCampoNum($imp,'.col-md-3','El impuesto determinado es requerido', valid);
  });

  valid = validarCampoNum('input[name="deduccionesiibb"]','.col-md-4','La deducción es requerida.',valid);

  valid = validarCampoNum('input[name="dif_miniibb"]','.col-md-4','La diferencia es requerida',valid);

  valid = validarCampo('input[name="fecha_iibb"]','.col-md-4','La fecha es requerida',valid);
  valid = validarCampo('input[name="fecha_iibbPres"]','.col-md-4','La fecha es requerida',valid);
  valid = validarCampoNum('input[name="saldo_iibb"]','.col-md-6','El saldo es requerido.',valid);

  valid = validarCampoNum('input[name="total_impuesto_iibb"]','.col-md-6','El saldo es requerido.',valid);



  if(!valid) return 0;

    var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsiibbContainer input[type="file"][name="uploadiibb[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadiibb[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadiibb');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadiibb[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizariibb/'+id)
      : '/documentosContables/guardariibb';

    $.ajax({
      url: url,
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
      success: function(){
      cargariibb({
        page:     1,
        perPage:  $('#herramientasPaginacioniibb').getPageSize(),
        casino:   $('#FCasinoiibb').val(),
        desde: $('#fecha_iibbDesde').val(),
        hasta: $('#fecha_iibbHasta').val()
      });
      setTimeout(() => $('#modalCargariibb').modal('hide'), 1000);
      resetFormiibb();
    },
    error: function(xhr){
      $('#salir').next('.help-block.js-error').remove();
      $('#salir')
        .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
    }
  });
});


function cargariibb({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasiibb',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaiibb').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaiibb').append(generarFilaiibb(item));
      });

      $('#herramientasPaginacioniibb').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceiibb
      );
      $('#herramientasPaginacioniibb').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceiibb
      );

    },
    error(err) {
      console.error('Error cargando IIBB:', err);
    }
  });
}


function clickIndiceiibb(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargariibb({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoiibb').val(),
    desde: $('#fecha_iibbDesde').val(),
    hasta: $('#fecha_iibbHasta').val()
  });
}

function generarFilaiibb(iibb,controlador) {
  const fila = $('<tr>').attr('id', iibb.id_registroiibb);

  const fecha = convertirMesAno(iibb.fecha_iibb) || '-';
  const pres = iibb.fecha_presentacion || '-';
  const casino= iibb.casino || '-';

  fila
    .append($('<td>').addClass('col-xs-3').html(fecha))
    .append($('<td>').addClass('col-xs-3').html(pres))
    .append($('<td>').addClass('col-xs-3').text(casino))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-3 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegiibb')
    .attr('id',iibb.id_registroiibb)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO IIBB')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

  if (iibb.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-iibb')
    .attr('type','button')
    .attr('data-id', iibb.id_registroiibb)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-iibb')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', iibb.id_registroiibb)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);

  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteiibb')
  .attr('id',iibb.id_registroiibb)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO IIBB')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteiibb', function(){
  const id = $(this).attr('id');
  $('#btn-eliminariibb').attr('data-id', id);
  $('#modalEliminariibb').modal('show');
});

$('#btn-eliminariibb').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminariibb/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminariibb').modal('hide');
      cargariibb({
        page:     $('#herramientasPaginacioniibb').getCurrentPage(),
        perPage:  $('#herramientasPaginacioniibb').getPageSize(),
        casino:   $('#FCasinoiibb').val(),
        desde: $('#fecha_iibbDesde').val(),
        hasta: $('#fecha_iibbHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$(document).on('click', '.btn-verRegiibb', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenariibb/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_iibb').val(fecha);
    $('#ver_fecha_pres_iibb').val(data.fecha_pres);
    $('#ver_casino_iibb').val(data.casino);

    $('#contenedor-bases-ver-iibb').empty();
        data.base.forEach(function(base){
          const base_imponible = formatoAR(base.base);
          const alicuota = formatoAR(base.alicuota);
          const impuesto = formatoAR(base.impuesto_determinado);
          const bloque = `
            <div class="row">
              <div class="col-md-4">
                <textarea class="form-control" rows="1" readonly>${base.observacion}</textarea>
              </div>
              <div class="col-md-3">
                <input type="text" class="form-control" value="$ `+base_imponible+`" readonly>
              </div>
              <div class="col-md-3">
                <input type="text" class="form-control" value="`+alicuota+` %" readonly>
              </div>
              <div class="col-md-2">
                <input type="text" class="form-control" value="$ `+impuesto+`" readonly>
              </div>
            </div>
            <br/>
          `;
          $('#contenedor-bases-ver-iibb').append(bloque);
        });
    $('#ver_deduccion_iibb').val('$ ' + formatoAR(data.deducciones));
    $('#ver_impuestoTotal_iibb').val('$ ' + formatoAR(data.impuesto_total));
    $('#ver_diferencia_iibb').val('$ ' + formatoAR(data.diferencia));

    $('#ver_saldo_iibb').val('$ ' + formatoAR(data.saldo));
    $('#ver_obs_iibb').val(data.obs);


    $('#modalVeriibb').modal('show');
  });
});

$('#btn-buscariibb').on('click', function(e){
  e.preventDefault();
  cargariibb({
    page:    1,
    perPage: $('#herramientasPaginacioniibb').getPageSize(),
    casino:  $('#FCasinoiibb').val(),
    desde: $('#fecha_iibbDesde').val(),
    hasta: $('#fecha_iibbHasta').val()
  });
});

$('#btn-descargariibbCsvRegistros').on('click', function () {
  const casino = $('#FCasinoiibb').val() ? $('#FCasinoiibb').val() : 4;
  const desde = $('#fecha_iibbDesde').val();
  const hasta = $('#fecha_iibbHasta').val();
  let valid = true;
  $('#collapseDescargariibb .has-error').removeClass('has-error');
  $('#collapseDescargariibb .js-error').remove();

  if (!casino) {
    $('#DCasinoiibb').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_iibbDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargariibbCsvRegistros?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$('#btn-descargariibbCsvActividades').on('click', function () {
  const casino = $('#FCasinoiibb').val() ? $('#FCasinoiibb').val() : 4;
  const desde = $('#fecha_iibbDesde').val();
  const hasta = $('#fecha_iibbHasta').val();
  let valid = true;
  $('#collapseDescargariibb .has-error').removeClass('has-error');
  $('#collapseDescargariibb .js-error').remove();

  if (!casino) {
    $('#DCasinoiibb').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_iibbDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargariibbCsvActividades?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$('#btn-descargariibbExcel').on('click',function(e){

  $('#collapseDescargariibb .has-error').removeClass('has-error');
  $('#collapseDescargariibb .js-error').remove();

  const casino = $('#FCasinoiibb').val() ? $('#FCasinoiibb').val() : 4;
  const desde = $('#fecha_iibbDesde').val();
  const hasta = $('#fecha_iibbHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoiibb').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_iibbDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargariibbXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargariibbXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

// DREI


$(function(){
  instalarNumeroFlexibleAR([
    '#garageDREI',
    '#bromatologiaDREI',
    '#base_imponible_comDREI',
    '#base_imponible_gasDREI',
    '#base_imponible_explDREI',
    '#base_imponible_apyjDREI',
    '#monto_pagado_melDREI',
    '#base_imponible_melDREI', '#alicuota_melDREI',
    '#base_imponibleO_melDREI', '#alicuotaO_melDREI',
    '#monto_pagado_roDREI',
    '#base_imponible_roDREI', '#alicuota_roDREI',
    '#base_imponibleO_roDREI', '#alicuotaO_roDREI', '#total_roDREI',
    '#publicidadDREI', '#retDREI', '#minDREI', '#rect1DREI', '#rect2DREI','#interesesDREI','#deduccionesDREI'
  ].join(', '), { decimales: 2 });
  instalarNumeroFlexibleAR([

    '#alicuota_comDREI',
    '#alicuota_gasDREI',
    '#alicuota_explDREI',
    '#alicuota_apyjDREI',

  ].join(', '), { decimales: 3 });


    instalarAutoImpuestoAR({
    base:     '#base_imponible_comDREI',
    alicuota: '#alicuota_comDREI',
    impuesto: '#imp_det_comDREI',
    decImp:   2,
    aliEsPorcentaje: false,
  });
    instalarAutoImpuestoAR({
    base:     '#base_imponible_gasDREI',
    alicuota: '#alicuota_gasDREI',
    impuesto: '#imp_det_gasDREI',
    decImp:   2,
    aliEsPorcentaje: false,

  });
    instalarAutoImpuestoAR({
    base:     '#base_imponible_apyjDREI',
    alicuota: '#alicuota_apyjDREI',
    impuesto: '#imp_det_apyjDREI',
    decImp:   2,
    aliEsPorcentaje: false,

  });
    instalarAutoImpuestoAR({
    base:     '#base_imponible_explDREI',
    alicuota: '#alicuota_explDREI',
    impuesto: '#imp_det_explDREI',
    decImp:   2,
    aliEsPorcentaje: false,

  });
    instalarAutoImpuestoAR({
    base:     '#base_imponible_melDREI',
    alicuota: '#alicuota_melDREI',
    impuesto: '#imp_det_melDREI',
    decImp:   2,
    aliEsPorcentaje: true,

  });
    instalarAutoImpuestoAR({
    base:     '#base_imponibleO_melDREI',
    alicuota: '#alicuotaO_melDREI',
    impuesto: '#imp_det0_melDREI',
    decImp:   2,
    aliEsPorcentaje: true,

  });
    instalarAutoImpuestoAR({
    base:     '#base_imponible_roDREI',
    alicuota: '#alicuota_roDREI',
    impuesto: '#imp_det_roDREI',
    decImp:   2,
    aliEsPorcentaje: false,

  });

  });
    instalarAutoSumaAR({
    sources: ['#imp_det_comDREI','#imp_det_gasDREI','#imp_det_explDREI','#imp_det_apyjDREI','#garageDREI','-#deduccionesDREI'],
    target:  '#imp_tot_csfDREI',
    decimales: 2
  });
    instalarAutoSumaAR({
    sources: ['#imp_tot_csfDREI','#bromatologiaDREI','-#deduccionesDREI','#interesesDREI'],
    target:  '#saldoDREI',
    decimales: 2
  });
    instalarAutoSumaAR({
    sources: ['#imp_det0_melDREI','#imp_det_melDREI','-#monto_pagado_melDREI'],
    target:  '#saldo_melDREI',
    decimales: 2
  });


function cargarArchivosDREILista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('DREIId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosDREI/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/drei/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="DREI"  class="btn btn-sm btn-danger btn-del-archivo-DREI" title="Quitar">')
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

$(document).on('click', '.btn-archivos-DREI', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro DREI');
  cargarArchivosDREILista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-DREI', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalDREIEditar(id){
  resetFormDREI();
  $('#DREI_modo').val('edit');
  $('#id_registroDREI').val(id);
  $('#modalCargarDREI .modal-title').text('| EDITAR REGISTRO DE DREI');
  $('#guardarRegistroDREI').text('ACTUALIZAR');
  $('#modalCargarDREI').modal('show');

  $.getJSON('/documentosContables/llenarDREIEdit/'+id, function(d){
    var ym  = String(d.fecha || d.fecha_drei || d.fecha_DREI || '').slice(0,7);
    var ymd = String(d.fecha_pres || d.fecha_presentacion || d.fecha_DREIPres || d['fecha pres'] || '').slice(0,10);

    $('#fechaDREI input[name="fecha_DREI"]').val(ym).trigger('input').trigger('change');
    $('#fechaDREIPres input[name="fecha_DREIPres"]').val(ymd).trigger('input').trigger('change');

    var dp1 = $('#fechaDREI').data('datepicker');
    if (dp1 && $('#fechaDREI').datepicker)  {
      $('#fechaDREI').datepicker('setDate', new Date((ym || '1970-01')+'-01'));
      $('#fechaDREI').datepicker('update');
    }
    var dp2 = $('#fechaDREIPres').data('datepicker');
    if (dp2 && $('#fechaDREIPres').datepicker){
      $('#fechaDREIPres').datepicker('setDate', new Date(ymd || '1970-01-01'));
      $('#fechaDREIPres').datepicker('update');
    }
    var dtp1 = $('#fechaDREI').data('DateTimePicker');
    if (dtp1 && dtp1.date) dtp1.date(window.moment ? moment((ym || '1970-01')+'-01','YYYY-MM-DD') : new Date((ym || '1970-01')+'-01'));
    var dtp2 = $('#fechaDREIPres').data('DateTimePicker');
    if (dtp2 && dtp2.date) dtp2.date(window.moment ? moment(ymd || '1970-01-01','YYYY-MM-DD') : new Date(ymd || '1970-01-01'));

    $('#casinoDREI').val(d.casino || d.id_casino).trigger('change');

    $('#obsDREI').val(d.obs || d.observacion || '');

    var tipo = String(d.casino || '');
    if (tipo === '2') {
      // CSF
      $('#bromatologiaDREI').val('$' + formatoAR(d.bromatologia ?? ''));
      $('#interesesDREI').val('$' + formatoAR(d.intereses ?? ''));
      $('#deduccionesDREI').val('$' + formatoAR(d.deducciones ?? ''));
      $('#saldoDREI').val('$' + formatoAR(d.saldo ?? ''));

      $('#base_imponible_comDREI').val('$' + formatoAR(d.com_base_imponible ?? ''));
      $('#alicuota_comDREI').val(formatoAR(d.com_alicuota ?? '')+' %');
      $('#imp_det_comDREI').val('$ '+ formatoAR(d.com_subt_imp_det ?? ''));

      $('#base_imponible_gasDREI').val('$' + formatoAR(d.gas_base_imponible ?? ''));
      $('#alicuota_gasDREI').val(formatoAR(d.gas_alicuota ?? '')+' %');
      $('#imp_det_gasDREI').val('$ '+ formatoAR(d.gas_imp_det ?? ''));

      $('#base_imponible_explDREI').val('$' + formatoAR(d.expl_base_imponible ?? ''));
      $('#alicuota_explDREI').val(formatoAR(d.expl_alicuota ?? '')+' %');
      $('#imp_det_explDREI').val('$ '+ formatoAR(d.expl_imp_det ?? ''));

      $('#base_imponible_apyjDREI').val('$' + formatoAR(d.apyju_base_imponible ?? ''));
      $('#alicuota_apyjDREI').val(formatoAR(d.apyju_alicuota ?? '')+' %');
      $('#imp_det_apyjDREI').val('$ '+ formatoAR(d.apyju_imp_det ?? ''));

      $('#imp_tot_csfDREI').val('$ ' + formatoAR(d.imp_tot_csfDREI));

    } else if (tipo === '1') {
      // MEL
      $('#monto_pagado_melDREI').val('$' + formatoAR(d.monto_pagado ?? ''));

      $('#base_imponible_melDREI').val('$' + formatoAR(d.com_base_imponible ?? ''));
      $('#alicuota_melDREI').val(formatoAR(d.com_alicuota ?? '')+' %');
      $('#imp_det_melDREI').val('$ '+ formatoAR(d.com_subt_imp_det ?? ''));


      $('#base_imponibleO_melDREI').val('$' + formatoAR(d.gas_base_imponible ?? ''));
      $('#alicuotaO_melDREI').val(formatoAR(d.gas_alicuota ?? '')+' %');
      $('#imp_det0_melDREI').val('$ '+ formatoAR(d.gas_imp_det ?? ''));

      $('#saldo_melDREI').val('$ ' + formatoAR(d.saldo));


    } else if (tipo === '3') {
      // RO
      $('#monto_pagado_roDREI').val('$' + formatoAR(d.monto_pagado ?? ''));

      $('#fechaDREIVenc input[name="fecha_DREIVenc"]')
        .val(String(d.vencimiento_previsto || '').slice(0,10))
        .trigger('input').trigger('change');

      $('#base_imponible_roDREI').val('$' + formatoAR(d.com_base_imponible ?? ''));
      $('#alicuota_roDREI').val(formatoAR(d.com_alicuota ?? '') + ' % y m2');
      $('#imp_det_roDREI').val('$ '+ formatoAR(d.com_subt_imp_det ?? ''));


      $('#rect1DREI').val('$' + formatoAR(d.rectificativa_1 ?? ''));

      $('#total_roDREI').val('$ ' + formatoAR(d.saldo));

    }
  });
}



function resetFormDREI(){
  var $f = $('#formNuevoRegistroDREI');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameDREI').val('No se ha seleccionado ningún archivo');
  $('#fileListDREI').empty();
  $('#uploadDREI').val('');
  $('#uploadsDREIContainer').empty();
    $('#uploadsDREITable tbody').empty();
    $('#uploadsDREIWrap').hide();
    $('#fileNameDREI').val('No se ha seleccionado ningún archivo');
  }

function abrirModalDREICrear(){
  $('#DREI_modo').val('create');
  $('#id_registroDREI').val('');
  $('#modalCargarDREI .modal-title').text('| NUEVO REGISTRO DE DREI');
  $('#guardarRegistroDREI').text('GENERAR');
  $('#modalCargarDREI').modal('show');
}


$(document).on('click','#DREI_nuevo',function(){
  abrirModalDREICrear();
});

$(document).on('click','.btn-edit-DREI',function(){
  var id = $(this).data('id');
  abrirModalDREIEditar(id);
});


$('#btn-descargarDREICsv').on('click', function () {
  const casino = $('#FCasinoDREI').val() ? $('#FCasinoDREI').val() : 4;

  const desde = $('#fecha_DREIDesde').val();
  const hasta = $('#fecha_DREIHasta').val();
  let valid = true;

  $('#collapseDescargarDREI .has-error').removeClass('has-error');
  $('#collapseDescargarDREI .js-error').remove();

  if (!casino) {
    $('#DCasinoDREI').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_DREIDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarDREICsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$('#btn-descargarDREIExcel').on('click',function(e){

  $('#collapseDescargarDREI .has-error').removeClass('has-error');
  $('#collapseDescargarDREI .js-error').remove();

  const casino = $('#FCasinoDREI').val()? $('#FCasinoDREI').val() : 4;
  const desde = $('#fecha_DREIDesde').val();
  const hasta = $('#fecha_DREIHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoDREI').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_DREIDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarDREIXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarDREIXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});


$(document).on('click', '.btn-deleteDREI', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarDREI').attr('data-id', id);
  $('#modalEliminarDREI').modal('show');
});

$('#btn-eliminarDREI').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarDREI/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarDREI').modal('hide');
      cargarDREI({
        page:     $('#herramientasPaginacionDREI').getCurrentPage(),
        perPage:  $('#herramientasPaginacionDREI').getPageSize(),
        casino:   $('#FCasinoDREI').val(),
        desde: $('#fecha_DREIDesde').val(),
        hasta: $('#fecha_DREIHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$('#btn-buscarDREI').on('click', function(e){
  e.preventDefault();
  cargarDREI({
    page:    1,
    perPage: $('#herramientasPaginacionDREI').getPageSize(),
    casino:  $('#FCasinoDREI').val(),
    desde: $('#fecha_DREIDesde').val(),
    hasta: $('#fecha_DREIHasta').val()
  });
});

function clickIndiceDREI(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarDREI({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoDREI').val(),
    desde: $('#fecha_DREIDesde').val(),
    hasta: $('#fecha_DREIHasta').val()
  });
}


function cargarDREI({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasDREI',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaDREI').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaDREI').append(generarFilaDREI(item));
      });

      $('#herramientasPaginacionDREI').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDREI
      );
      $('#herramientasPaginacionDREI').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDREI
      );

    },
    error(err) {
      console.error('Error cargando DREI:', err);
    }
  });
}

function generarFilaDREI(drei,controlador) {
  const fila = $('<tr>').attr('id', drei.id_registroDREI);

  const fecha = convertirMesAno(drei.fecha_drei) || '-';
  const pres = drei.fecha_presentacion || '-';
  const casino= drei.casino || '-';
  fila
    .append($('<td>').addClass('col-xs-3').html(fecha))
    .append($('<td>').addClass('col-xs-3').html(pres))
    .append($('<td>').addClass('col-xs-3').text(casino))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-3 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegDREI')
    .attr('id',drei.id_registroDREI)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO DREI')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

  if (drei.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-DREI')
    .attr('type','button')
    .attr('data-id', drei.id_registroDREI)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-DREI')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', drei.id_registroDREI)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteDREI')
  .attr('id',drei.id_registroDREI)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO DREI')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-verRegDREI', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarDREI/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }
    fecha = convertirMesAno(data.fecha);

    if(data.casino==='Santa Fe'){


      $('#ver_fecha_csfDREI').val(fecha);
      $('#ver_fecha_pres_csfDREI').val(data.fecha_pres);
      $('#ver_casino_csfDREI').val(data.casino);

      $('#ver_com_base_csfDREI').val('$ ' + formatoAR(data.com_base));
      $('#ver_com_ali_csfDREI').val(formatoAR(data.com_alicuota));
      $('#ver_com_imp_csfDREI').val('$ ' + formatoAR(data.com_subt));

      $('#ver_gas_base_csfDREI').val('$ ' + formatoAR(data.gas_base));
      $('#ver_gas_ali_csfDREI').val(formatoAR(data.gas_alicuota) );
      $('#ver_gas_imp_csfDREI').val('$ ' + formatoAR(data.gas_imp));

      $('#ver_expl_base_csfDREI').val('$ ' + formatoAR(data.expl_base));
      $('#ver_expl_ali_csfDREI').val(formatoAR(data.expl_alicuota) );
      $('#ver_expl_imp_csfDREI').val('$ ' + formatoAR(data.expl_imp));

      $('#ver_apyju_base_csfDREI').val('$ ' + formatoAR(data.apyju_base));
      $('#ver_apyju_ali_csfDREI').val(formatoAR(data.apyju_alicuota) );
      $('#ver_apyju_imp_csfDREI').val('$ ' + formatoAR(data.apyju_imp));

      $('#ver_bromatologia_csfDREI').val('$ ' + formatoAR(data.bromatologia));
      $('#ver_deducciones_csfDREI').val('$ ' + formatoAR(data.deducciones));
      $('#ver_total_imp_csfDREI').val('$ ' + formatoAR(data.total_imp_det));

      $('#ver_intereses_csfDREI').val('$ ' + formatoAR(data.intereses));
      $('#ver_saldo_csfDREI').val('$ ' + formatoAR(data.saldo));
      $('#ver_obs_csfDREI').val(data.obs);


      $('#modalVerCSFDREI').modal('show');

    }else if(data.casino==='Melincué'){

      $('#ver_fecha_melDREI').val(fecha);
      $('#ver_fecha_pres_melDREI').val(data.fecha_pres);
      $('#ver_casino_melDREI').val(data.casino);
      $('#ver_monto_melDREI').val('$ ' + formatoAR(data.monto_pagado));

      $('#ver_com_base_melDREI').val('$ ' + formatoAR(data.com_base));
      $('#ver_com_ali_melDREI').val(formatoAR(data.com_alicuota) + ' %');
      $('#ver_com_imp_melDREI').val('$ ' + formatoAR(data.com_subt));

      $('#ver_gas_base_melDREI').val('$ ' + formatoAR(data.gas_base));
      $('#ver_gas_ali_melDREI').val(formatoAR(data.gas_alicuota) + ' %');
      $('#ver_gas_imp_melDREI').val('$ ' + formatoAR(data.gas_imp));

      $('#ver_saldo_melDREI').val('$ ' + formatoAR(data.saldo));
      $('#ver_obs_melDREI').val(data.obs);

      $('#modalVerMELDREI').modal('show');
    }else{

      $('#ver_fecha_roDREI').val(fecha);
      $('#ver_fecha_pres_roDREI').val(data.fecha_pres);
      $('#ver_casino_roDREI').val(data.casino);
      $('#ver_monto_roDREI').val('$ ' + formatoAR(data.monto_pagado));



      $('#ver_saldo_roDREI').val('$ ' + formatoAR(data.saldo));
      $('#ver_obs_roDREI').val(data.obs);

      $('#modalVerRODREI').modal('show');
    }
  });
});

$(document).on('click','#guardarRegistroDREI',function(e){
  var $form = $('#formNuevoRegistroDREI');
  var modo = $('#DREI_modo').val() || 'create';
  var id   = $('#id_registroDREI').val() || '';

  let valid=true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();


  valid = validarCampo('select[name="casinoDREI"]','.col-md-4','El casino es requerido.',valid);
  valid = validarCampo('input[name="fecha_DREI"]','.col-md-4','La fecha es requerida',valid);
  valid = validarCampo('input[name="fecha_DREIPres"]','.col-md-4','La fecha es requerida.',valid);


  const visibleFormId = $('.formulario-DREI:visible').attr('id');

  if (visibleFormId === 'formularioCSF') {

    valid = validarCampoNum('input[name="bromatologiaDREI"]:visible', '.col-md-5', 'La bromatología es requerida.', valid);

    valid = validarCampoNum('input[name="base_imponible_comDREI"]:visible', '.col-md-4', 'La base es requerida.', valid);

    valid = validarCampoNum('input[name="alicuota_comDREI"]:visible', '.col-md-4', 'La alicuota es requerida.', valid);

    valid = validarCampoNum('input[name="base_imponible_gasDREI"]:visible', '.col-md-4', 'La base es requerida.', valid);

    valid = validarCampoNum('input[name="alicuota_gasDREI"]:visible', '.col-md-4', 'La alicuota es requerida.', valid);

    valid = validarCampoNum('input[name="base_imponible_explDREI"]:visible', '.col-md-4', 'La base es requerida.', valid);

    valid = validarCampoNum('input[name="alicuota_explDREI"]:visible', '.col-md-4', 'La alicuota es requerida.', valid);

    valid = validarCampoNum('input[name="base_imponible_apyjDREI"]:visible', '.col-md-4', 'La base es requerida.', valid);

    valid = validarCampoNum('input[name="alicuota_apyjDREI"]:visible', '.col-md-4', 'La alicuota es requerida.', valid);

    valid = validarCampoNum('input[name="interesesDREI"]:visible', '.col-md-4', 'Los intereses son requeridos.', valid);

    valid = validarCampoNum('input[name="deduccionesDREI"]:visible', '.col-md-4', 'La deducción es requerida.', valid);

    valid = validarCampoNum('input[name="imp_det_comDREI"]:visible','.col-md-4','El impuesto es requerido.', valid);
    valid = validarCampoNum('input[name="imp_det_gasDREI"]:visible','.col-md-4','El impuesto es requerido.', valid);
    valid = validarCampoNum('input[name="imp_det_explDREI"]:visible','.col-md-4','El impuesto es requerido.', valid);
    valid = validarCampoNum('input[name="imp_det_apyjDREI"]:visible','.col-md-4','El impuesto es requerido.', valid);
    valid = validarCampoNum('input[name="imp_tot_csfDREI"]:visible','.col-md-4','El impuesto es requerido.', valid);

    valid = validarCampoNum('input[name="saldoDREI"]:visible', '.col-md-4', 'El saldo es requerido.', valid);

  } else if (visibleFormId === 'formularioMEL') {
    valid = validarCampoNum('input[name="monto_pagado_melDREI"]:visible', '.col-md-6', 'El monto pagado es requerido.', valid);

    valid = validarCampoNum('input[name="base_imponible_melDREI"]:visible', '.col-md-6', 'La base es requerida.', valid);

    valid = validarCampoNum('input[name="alicuota_melDREI"]:visible', '.col-md-6', 'La alicuota es requerida.', valid);

    valid = validarCampoNum('input[name="base_imponibleO_melDREI"]:visible', '.col-md-6', 'La base es requerida.', valid);

    valid = validarCampoNum('input[name="alicuotaO_melDREI"]:visible', '.col-md-6', 'La alicuota es requerida.', valid);

    valid = validarCampoNum('input[name="imp_det_melDREI"]:visible','.col-md-4','El impuesto es requerido.', valid);
    valid = validarCampoNum('input[name="imp_det0_melDREI"]:visible','.col-md-4','El impuesto es requerido.', valid);
    valid = validarCampoNum('input[name="saldo_melDREI"]:visible', '.col-md-4', 'El saldo es requerido.', valid);

  } else if (visibleFormId === 'formularioRO') {
    valid = validarCampoNumSiHayValor($('input[name="monto_pagado_roDREI"]:visible'), '.col-md-6', 'El monto pagado es requerido.', valid);

    valid = validarCampoNumSiHayValor($('input[name="total_roDREI"]:visible'), '.col-md-6', 'El total es requerido.', valid);

    const $fechavenc = $form.find('input[name="fecha_DREIVenc"]');
    if (!$fechavenc.val()) {
      $fechavenc.closest('.col-md-6')
                 .addClass('has-error')
                 .append('<span class="help-block js-error">La fecha es requerida.</span>');
      valid = false;
    }

  }

if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);

var fd = new FormData();

  $form.serializeArray().forEach(function(p){
    fd.append(p.name, p.value);
  });

  $('#uploadsDREIContainer input[type="file"][name="uploadDREI[]"]').each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append('uploadDREI[]', files[i]);
    }
  });

  var cur = document.getElementById('uploadDREI');
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append('uploadDREI[]', cur.files[j]);
    }
  }

  var url = (modo === 'edit')
    ? ('/documentosContables/actualizarDREI/'+id)
    : '/documentosContables/guardarDREI';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarDREI({
      page:     1,
      perPage:  $('#herramientasPaginacionDREI').getPageSize(),
      casino:   $('#FCasinoDREI').val()
    });
    setTimeout(() => $('#modalCargarDREI').modal('hide'), 1000);
    resetFormDREI();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});



//IVA
function cargarArchivosIvaLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('ivaId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosIva/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/iva/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="Iva" class="btn btn-sm btn-danger btn-del-archivo-iva" title="Quitar">')
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

$(document).on('click', '.btn-archivos-iva', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro IVA');
  cargarArchivosIvaLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-iva', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});

$(document).on('shown.bs.modal', '#modalCargarPremiosIVA', function(){
  $('#saldoIva').trigger('input');
});

instalarNumeroFlexibleAR('#saldoIva');


function clickIndiceIva(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarIva({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoIva').val(),
    desde: $('#fecha_ivaDesde').val(),
    hasta: $('#fecha_ivaHasta').val()
  });
}

function resetFormIva(){
  var $f = $('#formNuevoRegistroIva');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameIva').val('No se ha seleccionado ningún archivo');
  $('#fileListIva').empty();
  $('#uploadIva').val('');
  $('#uploadsIvaContainer').empty();
    $('#uploadsIvaTable tbody').empty();
    $('#uploadsIvaWrap').hide();
    $('#fileNameIva').val('No se ha seleccionado ningún archivo');
  }

function abrirModalIvaCrear(){

  $('#iva_modo').val('create');
  $('#id_registroIva').val('');
  $('#modalCargarIva .modal-title').text('| NUEVO REGISTRO DE IVA');
  $('#guardarRegistroIva').text('GENERAR');
  $('#modalCargarIva').modal('show');
}

function abrirModalIvaEditar(id){
  resetFormIva();
  $('#iva_modo').val('edit');
  $('#id_registroIva').val(id);
  $('#modalCargarIva .modal-title').text('| EDITAR REGISTRO DE IVA');
  $('#guardarRegistroIva').text('ACTUALIZAR');
  $('#modalCargarIva').modal('show');

  $.getJSON('/documentosContables/llenarIva/'+id, function(d){
    var ym  = String(d.fecha || d.fecha_iva || '').slice(0,7);
    var ymd = String(d.fecha_pres || d.fecha_presentacion || d.fecha_ivaPres || d['fecha pres'] || '').slice(0,10);

    $('#casinoIva').val(d.casino || d.id_casino).trigger('change');
    $('#saldoIva').val(formatoAR(d.saldo));
    $('[name="obsiva"]').val(d.obs || d.observacion || '');

    $('#fechaIva input[name="fecha_iva"]').val(ym).trigger('input').trigger('change');
    $('#fechaIvaPres input[name="fecha_ivaPres"]').val(ymd).trigger('input').trigger('change');

    var dp1 = $('#fechaIva').data('datepicker');
    if(dp1 && $('#fechaIva').datepicker){
      $('#fechaIva').datepicker('setDate', new Date(ym+'-01'));
      $('#fechaIva').datepicker('update');
    }
    var dp2 = $('#fechaIvaPres').data('datepicker');
    if(dp2 && $('#fechaIvaPres').datepicker){
      $('#fechaIvaPres').datepicker('setDate', new Date(ymd));
      $('#fechaIvaPres').datepicker('update');
    }
    var dtp1 = $('#fechaIva').data('DateTimePicker');
    if(dtp1 && dtp1.date) dtp1.date(window.moment ? moment(ym+'-01','YYYY-MM-DD') : new Date(ym+'-01'));
    var dtp2 = $('#fechaIvaPres').data('DateTimePicker');
    if(dtp2 && dtp2.date) dtp2.date(window.moment ? moment(ymd,'YYYY-MM-DD') : new Date(ymd));
  })
  .fail(function(xhr){
    console.error('[IVA] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro de IVA.');
  });
}






$(document).on('click','#iva_nuevo',function(){
  abrirModalIvaCrear();
});

$(document).on('click','.btn-edit-iva',function(){
  var id = $(this).data('id');
  abrirModalIvaEditar(id);
});
$(document).on('click','#guardarRegistroIva',function(){

  var $form = $('#formNuevoRegistroIva');
  var modo = $('#iva_modo').val() || 'create';
  var id   = $('#id_registroIva').val() || '';
  var valid = true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();


  valid = validarCampo("select[name='casinoIva']",'.col-md-4','El casino es requerido.', valid);

  valid = validarCampoNum("input[name='saldoIva']",'.col-md-4','El saldo es requerido.',valid);

  valid = validarCampo("input[name='fecha_iva']",'.col-md-4','La fecha es requerida.',valid);
  valid = validarCampo("input[name='fecha_ivaPres']",'.col-md-5','La fecha es requerida.',valid);

  if(!valid) return;

  var fd = new FormData();

  $form.serializeArray().forEach(function(p){
    fd.append(p.name, p.value);
  });

  $('#uploadsIvaContainer input[type="file"][name="uploadIva[]"]').each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append('uploadIva[]', files[i]);
    }
  });

  var cur = document.getElementById('uploadIva');
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append('uploadIva[]', cur.files[j]);
    }
  }

  var url = (modo === 'edit')
    ? ('/documentosContables/actualizarIva/'+id)
    : '/documentosContables/guardarIva';

  $.ajax({
    url: url,
    method: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
    success: function(){
      cargarIva({
        page: 1,
        perPage: $('#herramientasPaginacionIVA').getPageSize(),
        casino: $('#FCasinoIva').val(),
        desde:  $('#fecha_ivaDesde').val(),
        hasta:  $('#fecha_ivaHasta').val()
      });
      $('#modalCargarIva').modal('hide');
      resetFormIva();
    },
    error: function(){
      $('#salir').next('.help-block.js-error').remove();
      $('#salir').after('<span class="help-block js-error text-danger">Ocurrió un error.</span>');
    }
  });
});

function cargarIva({ page = 1, perPage = 10, casino, desde, hasta}) {
  $.ajax({
    url: '/documentosContables/ultimasIva',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaIVA').empty();
      res.registros.forEach(item => {
        $('#cuerpoTablaIVA').append(generarFilaIva(item));
      });

      $('#herramientasPaginacionIVA').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIva
      );
      $('#herramientasPaginacionIVA').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIva
      );
    },
    error(err) {
      console.error('Error cargando IVA:', err);
    }
  });
}

function generarFilaIva(iva,controlador) {
  const fila = $('<tr>').attr('id', iva.id_registroIva);

  const fecha = convertirMesAno(iva.fecha_iva) || '-';
  const pres = iva.fecha_presentacion || '-';
  const casino= iva.casino || '-';
  const saldo = '$ '+ formatoAR(iva.saldo);
  const obs  = iva.observacion || '-';

  fila
    .append($('<td>').addClass('col-xs-1').html(fecha))
    .append($('<td>').addClass('col-xs-2').html(pres))
    .append($('<td>').addClass('col-xs-1').text(casino))
    .append($('<td>').addClass('col-xs-3').text(saldo))
    .append($('<td>').addClass('col-xs-3').text(obs));

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  if(iva.observacion && iva.observacion.length > 31){
    const btnView = $('<a>')
      .addClass('btn btn-success btn-sm mr-1')
      .attr('id','btn-ObsIva')
      .attr('target', '_blank')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-obs', iva.observacion)
      .attr('title', 'VER OBSERVACIÓN')
      .append($('<i>').addClass('fa fa-fw fa-eye'));
    tdAcc.append(btnView);
  }
  if (iva.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-iva')
    .attr('type','button')
    .attr('data-id', iva.id_registroIva)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-iva')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', iva.id_registroIva)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);
  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteIva')
  .attr('id',iva.id_registroIva)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO IVA')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteIva', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarIva').attr('data-id', id);
  $('#modalEliminarIva').modal('show');
});

$('#btn-eliminarIva').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarIva/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarIva').modal('hide');
      cargarIva({
        page:     $('#herramientasPaginacionIVA').getCurrentPage(),
        perPage:  $('#herramientasPaginacionIVA').getPageSize(),
        casino:   $('#FCasinoIva').val(),
        desde: $('#fecha_ivaDesde').val(),
        hasta: $('#fecha_ivaHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$(document).on('click','#btn-ObsIva',function(e){

  const texto = $(this).data('obs');
    $('#obsIvaContent').text(texto);

    $('#modalObsIva').modal('show');

});

$('#btn-buscarIva').on('click', function(e){
  e.preventDefault();

  cargarIva({
    page:    1,
    perPage: $('#herramientasPaginacionIVA').getPageSize(),
    casino:  $('#FCasinoIva').val(),
    desde: $('#fecha_ivaDesde').val(),
    hasta: $('#fecha_ivaHasta').val()


  });
});

$('#btn-descargarIvaCsv').on('click', function () {
  const casino = $('#FCasinoIva').val() ? $('#FCasinoIva').val() : 4;
  const desde = $('#fecha_ivaDesde').val();
  const hasta = $('#fecha_ivaHasta').val();
  let valid = true;
  $('#collapseDescargarIva .has-error').removeClass('has-error');
  $('#collapseDescargarIva .js-error').remove();

  if (!casino) {
    $('#DCasinoIva').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_ivaDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarIvaCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$('#btn-descargarIvaExcel').on('click',function(e){

  $('#collapseDescargarIva .has-error').removeClass('has-error');
  $('#collapseDescargarIva .js-error').remove();
  const casino = $('#FCasinoIva').val() ? $('#FCasinoIva').val() : 4;
  const desde   = $('#fecha_ivaDesde').val();
  const hasta =  $('#fecha_ivaHasta').val();
  let   valid  = true;


  if (!casino) {
    $('#DCasinoIva').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_ivaDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  if(casino!=4){
      window.location.href = `/documentosContables/descargarIvaXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    } else{
      window.location.href = `/documentosContables/descargarIvaXlsxTodos?desde=${desde}&hasta=${hasta}`;

}
});

//TGI
let TGI_PARTIDAS_REQ_ID = 0;

var TGI_SUPPRESS_CASINO_CHANGE = false;

$(function(){
  instalarNumeroFlexibleAR([
    '#Cocheras_importeTGI','#Hotel_importeTGI','#Cocheras_importeTGI',
  ].join(', '), { decimales: 2 });
});

function cargarArchivosTGILista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('TGIId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosTGI/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/TGI/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="TGI"  class="btn btn-sm btn-danger btn-del-archivo-TGI" title="Quitar">')
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

$(document).on('click', '.btn-archivos-TGI', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro TGI');
  cargarArchivosTGILista(id);
  $('#modalArchivosAsociados').modal('show');
});


function cargarPartidasTGI(casinoId, selectedId){
  const reqId = ++TGI_PARTIDAS_REQ_ID;
  const $sel = $('#partida_TGI');

  if (selectedId != null) $sel.data('selected-id', String(selectedId));

  $sel.prop('disabled', true).html('<option value="">Cargando…</option>');

  if (!casinoId){
    $sel.html('<option value="">Elegí un casino</option>').prop('disabled', false);
    $sel.removeData('selected-id');
    return;
  }

  $.getJSON('/documentosContables/getTGI_partidaPorCasino', { casino: casinoId })
    .done(function(data){
      if (reqId !== TGI_PARTIDAS_REQ_ID) return;
      populateSelect($sel, data, 'id', 'partida', 'Elegí una partida');
      const sid = $sel.data('selected-id');
      if (sid != null) $sel.val(sid);
    })
    .fail(function(){
      if (reqId !== TGI_PARTIDAS_REQ_ID) return;
      $sel.html('<option value="">Error al cargar</option>');
    })
    .always(function(){
      if (reqId !== TGI_PARTIDAS_REQ_ID) return;
      $sel.prop('disabled', false).removeData('selected-id');
    });
}



$(document).on('click', '.btn-del-archivo-TGI', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});

$('#modalCargarTGI')
  .off('shown.bs.modal.tgi')
  .on('shown.bs.modal.tgi', function(){
    if ($('#TGI_modo').val() === 'edit') return;
    cargarPartidasTGI($('#casinoTGI').val());
  });

  function abrirModalTGIEditar(id){
    resetFormTGI();
    $('#TGI_modo').val('edit');
    $('#id_registroTGI').val(id);
    $('#modalCargarTGI .modal-title').text('| EDITAR REGISTRO DE TGI');
    $('#guardarRegistroTGI').text('ACTUALIZAR');
    $('#modalCargarTGI').modal('show');

    TGI_SUPPRESS_CASINO_CHANGE = true;

    $.getJSON('/documentosContables/llenarTGIEdit/'+id, function(d){
      var ym = String(d.fecha || '').slice(0,7);

      $('#fechaTGI input[name="fecha_TGI"]').val(ym);
      $('#casinoTGI').val(d.casino);


      const $cont = $('#pagosTGIContainer').empty();
      TGI_PAGOS_IDX = 0;

      var seen = {};
      if (Array.isArray(d.pagos) && d.pagos.length){
        d.pagos.forEach(function(p){
          var key = String(p.partida_id||'')+'|'+String(p.fecha_vencimiento||'')+'|'+String(p.fecha_pago||'')+'|'+String(p.importe||'');
          if (seen[key]) return; seen[key]=1;

          var $row = renderFilaPagoTGI({
            id: (p.partida_id || ''),
            partida: (p.partida || '')
          });
          $row.find('input.pago-cuota').val(p.cuota || '');
          $row.find('textarea.pago-observacion').val(p.observacion || '');
          $row.find('input.pago-importe').val(p.importe || '').trigger('blur');
          $row.find('input.pago-vto').val(p.fecha_vencimiento || '').trigger('change');
          $row.find('input.pago-pago').val(p.fecha_pago || '').trigger('change');
        });
      }
    })
    .fail(function(xhr){
      console.error('[TGI editar] GET FAIL', xhr.status, xhr.responseText);
      alert('No se pudo cargar el TGI.');
    })
    .always(function(){
      TGI_SUPPRESS_CASINO_CHANGE = false;
    });
  }



  function resetFormTGI(){
    const $f = $('#formNuevoRegistroTGI');
    if ($f[0]) $f[0].reset();

    $f.find('.has-error').removeClass('has-error');
    $f.find('.help-block.js-error').remove();

    $('.formulario-TGI').hide()
      .find('input,textarea,select').val('').trigger('change');

    $('#partida_TGI')
      .removeData('selected-id')
      .html('<option value="">Elegí una partida</option>');

    $('#uploadsTGITable tbody').empty();
    $('#uploadsTGIWrap').hide();
    $('#uploadsTGIContainer').empty();
    $('#fileNameTGI').val('No se ha seleccionado ningún archivo');

    TGI_PARTIDAS_REQ_ID++;
  }


function abrirModalTGICrear(){
  $('#TGI_modo').val('create');
  $('#id_registroTGI').val('');
  $('#modalCargarTGI .modal-title').text('| NUEVO REGISTRO DE TGI');
  $('#guardarRegistroTGI').text('GENERAR');
  $('#modalCargarTGI').modal('show');
}


$(document).on('click','#TGI_nuevo',function(){
  abrirModalTGICrear();
});

$(document).on('click','.btn-edit-TGI',function(){
  var id = $(this).data('id');
  abrirModalTGIEditar(id);
});


$(document).on('click','#TGI_nueva_partida',function(e){

    $('#modalCargarTGI_partida').modal('show');

});



function cargarTGI_partidas(selectedId){


  $.getJSON('/documentosContables/getTGI_partidaPorCasino', function(data){
    $.each(data, function(_, t){
      var id  = t.id;
      var txt = t.nombre || '';
      $sel.append('<option value="'+ id +'">'+ txt +'</option>');
    });

    if (selectedId) $sel.val(String(selectedId));
  })
  .fail(function(xhr){
    console.error(xhr.responseText || xhr);
    $sel.empty().append('<option value="">Error al cargar</option>');
  })
  .always(function(){
    $sel.prop('disabled', false);
  });
}

$(document).on('click','#TGI_partida_gestionar',function(){
  $('#modalTGI_partida_gestionar').modal('show');
  cargarTGI_partidaGestion();
});

function renderFilaTGI_partida(d){
  var estado = d.estado
  ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
  : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';
  return ''+
  '<tr data-estado="'+d.estado+'" data-id="'+d.id+'" data-casino"'+escapeAttr(d.casino_id || '')+'"  data-partida="'+escapeAttr(d.partida || '')+'">'+
    '<td class="col-md-8">'+escapeHtml(d.partida || '')+'</td>'+
    '<td class="col-md-1">'+escapeHtml(d.casino_nombre|| '')+'</td>'+
    '<td class="col-md-2">'+estado+'</td>'+
    '<td class="col-md-1">'+
      '<button type="button" class="btn btn-sm btn-primary btn-editTGI_partida" title="MODIFICAR PARTIDA">'+
        '<i class="fa fa-edit"></i>'+
      '</button> '+
    '</td>'+
  '</tr>';
}

let TGI_PAGOS_IDX = 0;

function renderFilaPagoTGI(p){
  const idx = ++TGI_PAGOS_IDX;

  const $row = $(
    '<div class="row pago-row" data-idx="'+idx+'" style="margin-bottom:10px;">\
      <input type="hidden" name="pago_partida[]" value="'+ (p.id||'') +'">\
      <div class="col-md-2">\
        <h5>Partida</h5>\
        <div class="form-control" style="height:auto; min-height:34px; padding-top:6px; font-weight:600;">'+ (p.partida || '') +'</div>\
      </div>\
      <div class="col-md-1">\
        <h5>Cuota</h5>\
        <input type="text" class="form-control pago-cuota" id="pago_cuota_'+idx+'" name="pago_cuota[]" >\
      </div>\
      <div class="col-md-2">\
        <h5>Importe</h5>\
        <input type="text" class="form-control pago-importe" id="pago_importe_'+idx+'" name="pago_importe[]" placeholder="$">\
      </div>\
      <div class="col-md-2">\
        <h5>Vencimiento</h5>\
        <div class="input-group date" id="pago_vto_'+idx+'">\
          <input type="text" class="form-control pago-vto" name="pago_vencimiento[]" placeholder="yyyy-mm-dd" autocomplete="off">\
          <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>\
          <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>\
        </div>\
      </div>\
      <div class="col-md-2">\
        <h5>Fecha pago</h5>\
        <div class="input-group date" id="pago_pago_'+idx+'">\
          <input type="text" class="form-control pago-pago" name="pago_pago[]" placeholder="yyyy-mm-dd" autocomplete="off">\
          <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>\
          <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>\
        </div>\
      </div>\
      <div class="col-md-3">\
        <h5>Observaciones</h5>\
        <textarea class="form-control pago-observacion" id="pago_observacion_'+idx+'" name="pago_observacion[]" ></textarea>\
      </div>\
    </div>'
  );

  $('#pagosTGIContainer').append($row);
  initPagoRow(idx);

  return $row;
}

function initPagoRow(idx){
  const $modal = $('#modalCargarTGI');
  const $vto   = $('#pago_vto_'+idx);
  const $pago  = $('#pago_pago_'+idx);

  if (typeof instalarNumeroFlexibleAR === 'function') {
    instalarNumeroFlexibleAR('#pago_importe_' + idx, { decimales: 2 });
  }
  if (typeof attachYYYYMMDDFormatter === 'function') {
    attachYYYYMMDDFormatter('#pago_vto_'  + idx + ' input');
    attachYYYYMMDDFormatter('#pago_pago_' + idx + ' input');
  }

  if (!$.fn.datetimepicker) { console.warn('[TGI] Falta $.fn.datetimepicker'); return; }

  try { $vto.datetimepicker('remove'); } catch(e){}
  try { $pago.datetimepicker('remove'); } catch(e){}

  const opts = {
    language: 'es',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    format: 'yyyy-mm-dd',
    startView: 2,
    minView: 2,
    maxView: 4,
    forceParse: false
  };

  $vto.datetimepicker(opts);
  $pago.datetimepicker(opts);

  [$vto, $pago].forEach(function($wrap){
    $wrap.off('show.dp.reloc').on('show.dp.reloc', function(){
      const inst = $wrap.data('datetimepicker');
      const $widget = inst && inst.picker ? inst.picker : $('.datetimepicker:visible').last();
      if ($widget.length && !$modal.has($widget).length) {
        $modal.append($widget);
        try { inst.place(); } catch(e){}
      }
      $widget.find('.timepicker, .datetimepicker-hours, .datetimepicker-minutes').hide();
      $widget.find('.datepicker, .datetimepicker-days').show();
    });
  });

  $vto.off('changeDate.dpHide').on('changeDate.dpHide', function(){ try { $vto.datetimepicker('hide'); } catch(e){} });
  $pago.off('changeDate.dpHide').on('changeDate.dpHide', function(){ try { $pago.datetimepicker('hide'); } catch(e){} });

  $modal
    .off('click.dpOpen', '#pago_vto_'+idx+' .input-group-addon:last-child, #pago_pago_'+idx+' .input-group-addon:last-child')
    .on('click.dpOpen',  '#pago_vto_'+idx+' .input-group-addon:last-child, #pago_pago_'+idx+' .input-group-addon:last-child', function(){
      $(this).closest('.input-group.date').datetimepicker('show');
    })
    .off('click.dpClear', '#pago_vto_'+idx+' .input-group-addon:first-child, #pago_pago_'+idx+' .input-group-addon:first-child')
    .on('click.dpClear',  '#pago_vto_'+idx+' .input-group-addon:first-child, #pago_pago_'+idx+' .input-group-addon:first-child', function(){
      $(this).closest('.input-group.date').find('input').val('').trigger('input').trigger('change');
      try { $(this).closest('.input-group.date').datetimepicker('hide'); } catch(e){}
    });
}

(function injectPickerFixes(){
  if (document.getElementById('dtp-fixes-css')) return;
  const css = `
    #modalCargarTGI .datetimepicker{ z-index: 20000 !important; }
    #modalCargarTGI .datetimepicker .timepicker,
    #modalCargarTGI .datetimepicker .datetimepicker-hours,
    #modalCargarTGI .datetimepicker .datetimepicker-minutes{ display:none !important; }
    #modalCargarTGI .datetimepicker .datepicker,
    #modalCargarTGI .datetimepicker .datetimepicker-days{ display:block !important; }
  `;
  const style = document.createElement('style');
  style.id = 'dtp-fixes-css';
  style.type = 'text/css';
  style.appendChild(document.createTextNode(css));
  document.head.appendChild(style);
})();



function cargarPagosPorCasino(casinoId){
  const $cont = $('#pagosTGIContainer').empty();
  TGI_PAGOS_IDX = 0;
  if(!casinoId) return;

  $.getJSON('/documentosContables/getTGI_partidaPorCasino', { casino: casinoId })
    .done(function(data){
      (data || []).forEach(function(p){
        $cont.append( renderFilaPagoTGI(p) );
      });
    })
    .fail(function(){
      $cont.append('<div class="alert alert-danger">Error al cargar partidas.</div>');
    });
}

$(document)
  .off('change.tgiPagos','#casinoTGI')
  .on('change.tgiPagos','#casinoTGI', function(){
    if (TGI_SUPPRESS_CASINO_CHANGE) return;
    cargarPagosPorCasino($(this).val());
  });

$(document).on('click','.btn-del-pago',function(){
  $(this).closest('.pago-row').remove();
});

$(document).on('click','.btn-del-pago',function(){
  $(this).closest('.pago-row').remove();
});



function cargarTGI_partidaGestion(casinoId){
  $('#dir-list-loading_TGI_partida').show();
  $('#tabla-TGI_partida').closest('.table-responsive').hide();
  $('#tabla-TGI_partida tbody').empty();

  var url = '/documentosContables/getTGI_partida';

  $.getJSON(url, function(data){
    var rows = '';
    for (var i=0; i<data.length; i++){
      var d = data[i];
      rows += renderFilaTGI_partida(d);
    }
    $('#tabla-TGI_partida tbody').html(rows);
    $('#dir-list-loading_TGI_partida').hide();
    $('#tabla-TGI_partida').closest('.table-responsive').show();
  }).fail(function(xhr){
    $('#dir-list-loading_TGI_partida').text('Error cargando elementos patentables.');
    console.error(xhr.responseText);
  });
}

$(document).on('click', '.btn-elimTGI_partida', function(){
  const id = $(this).closest('tr').data('id');

  $('#btn-eliminarTGI_partida').attr('data-id', id);
  $('#modalEliminarTGI_partida').modal('show');
});

$(document).on('click', '.btn-editTGI_partida', function(){
  const $tr  = $(this).closest('tr');
  const id = $(this).closest('tr').data('id');
  const partida = $(this).closest('tr').data('partida');
  const estado = $(this).closest('tr').data('estado');
  var $sel = $('#ModifTGI_partida_estado');

  $('#ModifId_TGI_partida').val(id);
  $('#ModifEstadoTGI_partida').val(estado);
  $('#ModifTGI_partida_partida').val(partida);

  $('#modalModificarTGI_partida').modal('show');
});

$(document).on('click', '#guardarModifRegistroTGI_partida', function(){
  const $btn  = $(this).prop('disabled', true);
  const $form = $('#formModificarRegistroTGI_partida');

  valid = true;
  valid = validarCampo("input[name='ModifTGI_partida_partida']",'.col-md-12','El tipo es requerido.', valid);

  if(!valid) return 0;

  $.ajax({
    url: '/documentosContables/modificarTGI_partida',
    method: 'POST',
    data: $form.serialize(),
    success: function(res){
      cargarTGI_partidaGestion();
      $('#modalModificarTGI_partida').modal('hide');
    },
    error: function(xhr){
      console.error(xhr.responseText || xhr);
    },
    complete: function(){
      $btn.prop('disabled', false);
    }
  });
});



$(document).on('click', '#btn-eliminarTGI_partida', function () {
  const id  = $(this).attr('data-id');
  const $tr = $('#tabla-TGI_partida tr[data-id="'+id+'"]');

  $.get('/documentosContables/TGIEliminarPartida/' + id, function (res) {
    if (res.ok) {
      $tr.remove();
      $('#modalEliminarTGI_partida').modal('hide')
    } else {
    }
  }).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
  });
});

$(document).on('click','#guardarRegistroTGI_partida',function(e){
  var $form = $('#formNuevoRegistroTGI_partida');
  let valid=true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  valid = validarCampo("select[name='CasinoTGI_partida']",".col-md-3",'El casino es requerido.',valid);
  valid = validarCampo("input[name='nombre_TGI_partida']",'.col-md-9','El nombre de la partida es requerido.', valid);

  if(!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: '/documentosContables/guardarRegistroTGI_partida',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(res){
      setTimeout(() => $('#modalCargarTGI_partida').modal('hide'), 1000);
    },
    error: function(xhr){

      $('#salir').next('.help-block.js-error').remove();
      $('#salir')
        .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
    }


  });
});


$(document).on('click','#guardarRegistroTGI',function(e){
  var $form = $('#formNuevoRegistroTGI');
  let valid=true;
  var id   = $('#id_registroTGI').val() || '';
  var modo = $('#TGI_modo').val() || 'create';

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  var $casino = $form.find('select[name="casinoTGI"]');
  if (!$casino.val()) {
    $casino.closest('.col-md-4')
           .addClass('has-error')
           .append('<span class="help-block js-error">El casino es requerido.</span>');
    valid = false;
  }

  valid = validarCampo('input[name="fecha_TGI"]','.col-md-4','La fecha es requerida.', valid);


  const visibleFormId = $('.formulario-TGI:visible').attr('id');

  var $obs = $form.find('textarea[name="obsTGI"]');
  var obsText = $obs.val() ? $obs.val().trim() : '';
  if (obsText.length > 4000) {
    $obs.closest('.col-md-4')
      .find('.help-block.js-error').remove();

    $obs.closest('.col-md-4')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El máximo de caracteres es de 4000.</span>');
    valid = false;
  }

  $('#pagosTGIContainer .pago-row').each(function(){
    var $row   = $(this);
    var $imp   = $row.find('input.pago-importe');
    var $vto   = $row.find('input.pago-vto');
    var $pago  = $row.find('input.pago-pago');

    var vImp  = ($imp.val()  || '').trim();
    var vVto  = ($vto.val()  || '').trim();
    var vPago = ($pago.val() || '').trim();

    if (!vImp && !vVto && !vPago) return;

    valid = validarCampoNumSiHayValor($imp,  '.col-md-2', 'El importe es requerido.', valid);
    valid = validarCampo($vto,  '.col-md-3', 'El vencimiento es requerido.', valid);
    valid = validarCampo($pago, '.col-md-3', 'La fecha de pago es requerida.', valid);
  });



if (!valid) return;


  let formElem = $form[0];
  let formData = new FormData(formElem);


    var fd = new FormData();

      $form.serializeArray().forEach(function(p){
        fd.append(p.name, p.value);
      });

      $('#uploadsTGIContainer input[type="file"][name="uploadTGI[]"]').each(function () {
        var files = this.files || [];
        for (var i = 0; i < files.length; i++) {
          fd.append('uploadTGI[]', files[i]);
        }
      });

      var cur = document.getElementById('uploadTGI');
      if (cur && cur.files && cur.files.length) {
        for (var j = 0; j < cur.files.length; j++) {
          fd.append('uploadTGI[]', cur.files[j]);
        }
      }

      var url = (modo === 'edit')
        ? ('/documentosContables/actualizarTGI/'+id)
        : '/documentosContables/guardarTGI';


  $.ajax({
    url: url,
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(res){
       cargarTGI({
        page:     1,
        perPage:  $('#herramientasPaginacionTGI').getPageSize(),
        casino:   $('#FCasinoTGI').val(),
        desde: $('#fecha_TGIDesde').val(),
        hasta: $('#fecha_TGIHasta').val()
      });
      resetFormTGI();

      setTimeout(() => $('#modalCargarTGI').modal('hide'), 1000);
    },
    error: function(xhr){

      $('#salir').next('.help-block.js-error').remove();
      $('#salir')
        .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
    }


  });
});

$('#btn-buscarTGI').on('click', function(e){
  e.preventDefault();
  cargarTGI({
    page:    1,
    perPage: $('#herramientasPaginacionTGI').getPageSize(),
    casino:  $('#FCasinoTGI').val(),
    desde: $('#fecha_TGIDesde').val(),
    hasta: $('#fecha_TGIHasta').val()
  });
});

function clickIndiceTGI(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarTGI({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoTGI').val(),
    desde: $('#fecha_TGIDesde').val(),
    hasta: $('#fecha_TGIHasta').val()
  });
}

function cargarTGI({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasTGI',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaTGI').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaTGI').append(generarFilaTGI(item));
      });

      $('#herramientasPaginacionTGI').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceTGI
      );
      $('#herramientasPaginacionTGI').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceTGI
      );

    },
    error(err) {
      console.error('Error cargando TGI:', err);
    }
  });
}

function generarFilaTGI(TGI,controlador) {
  const fila = $('<tr>').attr('id', TGI.id_registroTGI);
  const fecha = convertirMesAno(TGI.fecha_TGI) || '-';
  const fechaPres = TGI.fechaPres || '-';
  const casino= TGI.casino || '-';
  const file= TGI.archivo;

  fila
    .append($('<td>').addClass('col-xs-4').html(fecha))
    .append($('<td>').addClass('col-xs-4').text(casino))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-4 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegTGI')
    .attr('id',TGI.id_registroTGI)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO TGI')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

if (TGI.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-TGI')
    .attr('type','button')
    .attr('data-id', TGI.id_registroTGI)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-TGI')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', TGI.id_registroTGI)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);



  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteTGI')
  .attr('id',TGI.id_registroTGI)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO TGI')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteTGI', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarTGI').attr('data-id', id);
  $('#modalEliminarTGI').modal('show');
});

$('#btn-eliminarTGI').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarTGI/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarTGI').modal('hide');
      cargarTGI({
        page:     $('#herramientasPaginacionTGI').getCurrentPage(),
        perPage:  $('#herramientasPaginacionTGI').getPageSize(),
        casino:   $('#FCasinoTGI').val(),
        desde: $('#fecha_TGIDesde').val(),
        hasta: $('#fecha_TGIHasta').val()
      });
    } else {
    }
  }).fail(() => {
    console.log('error');
  });
});
$(document)
  .off('click', '.btn-verRegTGI')
  .on('click', '.btn-verRegTGI', function () {
    const id = $(this).attr('id');

    $.getJSON(`/documentosContables/llenarTGI/${id}`, function (data) {
      if (!data) { alert('No se encontraron datos'); return; }

      $('#ver_fecha_TGI').val(convertirMesAno(data.fecha));
      $('#ver_casino_TGI').val(data.casino || '');



      renderVerPagosTGI('#ver_pagosTGIContainer', data.pagos || []);

      $('#modalVerTGICSF').modal('show');
    }).fail(function (xhr) {
      console.error('Error llenarTGI:', xhr?.responseText || xhr.statusText);
      alert('No se pudo obtener el registro TGI.');
    });
  });

  let VER_TGI_PAGOS_IDX = 0;

  function renderFilaPagoTGIReadOnly(p) {
    const idx = ++VER_TGI_PAGOS_IDX;

    const $row = $(
      '<div class="row pago-row" data-idx="'+idx+'">\
        <div class="col-md-2">\
          <h5>Partida</h5>\
          <div class="form-control">'+ (p.partida || '') +'</div>\
        </div>\
        <div class="col-md-1">\
          <h5>Cuota</h5>\
          <input type="text" class="form-control pago-cuota" id="ver_pago_cuota_'+idx+'" readonly>\
        </div>\
        <div class="col-md-2">\
          <h5>Importe</h5>\
          <input type="text" class="form-control pago-importe" id="ver_pago_importe_'+idx+'" placeholder="$" readonly>\
        </div>\
        <div class="col-md-2">\
          <h5>Vencimiento</h5>\
          <div class="input-group date" id="ver_pago_vto_'+idx+'">\
            <input type="text" class="form-control pago-vto" placeholder="yyyy-mm-dd" autocomplete="off" readonly>\
          </div>\
        </div>\
        <div class="col-md-2">\
          <h5>Fecha pago</h5>\
          <div class="input-group date" id="ver_pago_pago_'+idx+'">\
            <input type="text" class="form-control pago-pago" placeholder="yyyy-mm-dd" autocomplete="off" readonly>\
          </div>\
        </div>\
        <div class="col-md-3">\
          <h5>Observacion</h5>\
          <textarea class="form-control pago-observacion" id="ver_pago_observacion_'+idx+'" placeholder="$" readonly></textarea>\
        </div>\
      </div>'
    );

    $row.find('.pago-cuota').val(p.cuota || '');
    $row.find('.pago-importe').val(p.importe != null ? '$ ' + formatoAR(p.importe) : '');
    $row.find('.pago-vto').val(p.fecha_vencimiento || '');
    $row.find('.pago-pago').val(p.fecha_pago || '');
    $row.find('.pago-observacion').val(p.observacion || '');

    return $row;
  }

  function renderVerPagosTGI(containerSelector, pagos) {
    const $container = $(containerSelector);
    $container.empty();
    VER_TGI_PAGOS_IDX = 0;

    if (!Array.isArray(pagos) || pagos.length === 0) {
      $container.append('<div class="alert alert-info" role="alert">No hay pagos registrados para este TGI.</div>');
      return;
    }

    pagos.forEach(p => $container.append(renderFilaPagoTGIReadOnly(p)));
  }






$('#btn-descargarTGICsv').on('click', function () {
  const casino = $('#FCasinoTGI').val() ? $('#FCasinoTGI').val() : 4;
  const desde = $('#fecha_TGIDesde').val();
  const hasta = $('#fecha_TGIHasta').val();
  let valid = true;
  $('#collapseDescargarTGI .has-error').removeClass('has-error');
  $('#collapseDescargarTGI .js-error').remove();

  if (!casino) {
    $('#DCasinoTGI').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_TGIDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarTGICsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$('#btn-descargarTGIExcel').on('click',function(e){

  $('#collapseDescargarTGI .has-error').removeClass('has-error');
  $('#collapseDescargarTGI .js-error').remove();

  const casino = $('#FCasinoTGI').val() ? $('#FCasinoTGI').val() : 4;
  const desde = $('#fecha_TGIDesde').val();
  const hasta = $('#fecha_TGIHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoTGI').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_TGIDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarTGIXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarTGIXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

//IMPUESTOS A APUESTAS ONLINE IMP_AP_OL

$(function(){
  instalarNumeroFlexibleAR([
    '#monto_pagadoIMP_AP_OL','#monto_apuestasIMP_AP_OL','#alicuotaIMP_AP_OL','#impuesto_determinadoIMP_AP_OL'
  ].join(', '), { decimales: 2 });

    instalarAutoImpuestoAR({
    base:     '#monto_apuestasIMP_AP_OL',
    alicuota: '#alicuotaIMP_AP_OL',
    impuesto: '#impuesto_determinadoIMP_AP_OL',
    decImp:   2,
    aliEsPorcentaje: 1
  });
});

function cargarArchivosIMP_AP_OLLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('IMP_AP_OLId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosIMP_AP_OL/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/IMP_AP_OL/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="IMP_AP_OL"  class="btn btn-sm btn-danger btn-del-archivo-IMP_AP_OL" title="Quitar">')
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

$(document).on('click', '.btn-archivos-IMP_AP_OL', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro IMP_AP_OL');
  cargarArchivosIMP_AP_OLLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-IMP_AP_OL', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});


function abrirModalIMP_AP_OLEditar(id){
  resetFormIMP_AP_OL();
  $('#IMP_AP_OL_modo').val('edit');
  $('#id_registroIMP_AP_OL').val(id);
  $('#modalCargarIMP_AP_OL .modal-title').text('| EDITAR REGISTRO DE IMPUESTO A APUESTAS ONLINE');
  $('#guardarRegistroIMP_AP_OL').text('ACTUALIZAR');
  $('#modalCargarIMP_AP_OL').modal('show');

  $.getJSON('/documentosContables/llenarIMP_AP_OLEdit/'+id, function(d){
    var ym  = String(d.fecha || '').slice(0,7);
    var ymd = String(d.fecha_pres || '').slice(0,10);
    var ymdp= String(d.fecha_pago || '').slice(0,10);

    $('#fechaIMP_AP_OL input[name="fecha_IMP_AP_OL"]').val(ym).trigger('input').trigger('change');
    $('#fechaIMP_AP_OLPres input[name="fecha_IMP_AP_OLPres"]').val(ymd).trigger('input').trigger('change');
    $('input[name="fecha_pago_IMP_AP_OL"]').val(ymdp).trigger('input').trigger('change');

    $('#casinoIMP_AP_OL').val(d.casino).trigger('change');

    $('select[name="qnaIMP_AP_OL"]').val(d.qna ?? '').trigger('change');
    $('input[name="monto_pagadoIMP_AP_OL"]').val(formatoAR(d.monto_pagado ?? ''));
    $('input[name="monto_apuestasIMP_AP_OL"]').val(formatoAR(d.monto_apuestas ?? ''));
    $('input[name="alicuotaIMP_AP_OL"]').val(formatoAR(d.alicuota ?? ''));
    $('input[name="impuesto_determinadoIMP_AP_OL"]').val(formatoAR(d.impuesto_determinado ?? ''));
    $('[name="obsIMP_AP_OL"]').val(d.obs || '');
  })
  .fail(function(xhr){
    console.error('[IMP_AP_OL] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}

function resetFormIMP_AP_OL(){
  var $f = $('#formNuevoRegistroIMP_AP_OL');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameIMP_AP_OL').val('No se ha seleccionado ningún archivo');
  $('#fileListIMP_AP_OL').empty();
  $('#uploadIMP_AP_OL').val('');
  $('#uploadsIMP_AP_OLContainer').empty();
    $('#uploadsIMP_AP_OLTable tbody').empty();
    $('#uploadsIMP_AP_OLWrap').hide();
    $('#fileNameIMP_AP_OL').val('No se ha seleccionado ningún archivo');
  }

function abrirModalIMP_AP_OLCrear(){
  $('#IMP_AP_OL_modo').val('create');
  $('#id_registroIMP_AP_OL').val('');
  $('#modalCargarIMP_AP_OL .modal-title').text('| NUEVO REGISTRO DE IMPUESTO A APUESTAS ONLINE');
  $('#guardarRegistroIMP_AP_OL').text('GENERAR');
  $('#modalCargarIMP_AP_OL').modal('show');
}


$(document).on('click','#IMP_AP_OL_nuevo',function(){
  abrirModalIMP_AP_OLCrear();
});

$(document).on('click','.btn-edit-IMP_AP_OL',function(){
  var id = $(this).data('id');
  abrirModalIMP_AP_OLEditar(id);
});

$(document).on('click','#guardarRegistroIMP_AP_OL',function(e){
  var $form = $('#formNuevoRegistroIMP_AP_OL');
  let valid=true;
  var id   = $('#id_registroIMP_AP_OL').val() || '';
  var modo = $('#IMP_AP_OL_modo').val() || 'create';

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  valid = validarCampo('select[name="casinoIMP_AP_OL"]','.col-md-3','El casino es requerido',valid);
  valid = validarCampoNum('input[name="alicuotaIMP_AP_OL"]','.col-md-4','La alicuota es requerida.',valid);
  valid = validarCampoNum('input[name="monto_pagadoIMP_AP_OL"]','.col-md-4','El monto es requerido.',valid);
  valid = validarCampoNum('input[name="monto_apuestasIMP_AP_OL"]','.col-md-4','El monto es requerido',valid);
  valid = validarCampoNum('input[name="impuesto_determinadoIMP_AP_OL"]','.col-md-4','El monto es requerido.',valid);

  valid = validarCampo('input[name="fecha_IMP_AP_OL"]','.col-md-4','La fecha es requerida',valid);
  valid = validarCampo('input[name="fecha_pago_IMP_AP_OL','.col-md-4','La fecha es requerida',valid);
  valid = validarCampo('input[name="fecha_IMP_AP_OLPres"]','.col-md-5','La fecha es requerida', valid);


if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsIMP_AP_OLContainer input[type="file"][name="uploadIMP_AP_OL[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadIMP_AP_OL[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadIMP_AP_OL');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadIMP_AP_OL[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarIMP_AP_OL/'+id)
      : '/documentosContables/guardarIMP_AP_OL';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarIMP_AP_OL({
      page:     1,
      perPage:  $('#herramientasPaginacionIMP_AP_OL').getPageSize(),
      casino:   $('#FCasinoIMP_AP_OL').val(),
      desde: $('#fecha_IMP_AP_OLDesde').val(),
      hasta: $('#fecha_IMP_AP_OLHasta').val()
    });
    setTimeout(() => $('#modalCargarIMP_AP_OL').modal('hide'), 1000);
    resetFormIMP_AP_OL();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarIMP_AP_OL').on('click', function(e){
  e.preventDefault();
  cargarIMP_AP_OL({
    page:    1,
    perPage: $('#herramientasPaginacionIMP_AP_OL').getPageSize(),
    casino:  $('#FCasinoIMP_AP_OL').val(),
    desde: $('#fecha_IMP_AP_OLDesde').val(),
    hasta: $('#fecha_IMP_AP_OLHasta').val()
  });
});

function clickIndiceIMP_AP_OL(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarIMP_AP_OL({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoIMP_AP_OL').val(),
    desde: $('#fecha_IMP_AP_OLDesde').val(),
    hasta: $('#fecha_IMP_AP_OLHasta').val()
  });
}

function cargarIMP_AP_OL({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasIMP_AP_OL',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaIMP_AP_OL').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaIMP_AP_OL').append(generarFilaIMP_AP_OL(item));
      });

      $('#herramientasPaginacionIMP_AP_OL').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIMP_AP_OL
      );
      $('#herramientasPaginacionIMP_AP_OL').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIMP_AP_OL
      );

    },
    error(err) {
      console.error('Error cargando IMP_AP_OL:', err);
    }
  });
}

function generarFilaIMP_AP_OL(IMP_AP_OL,controlador) {
  const fila = $('<tr>').attr('id', IMP_AP_OL.id_registroIMP_AP_OL);

  const fecha = convertirMesAno(IMP_AP_OL.fecha_IMP_AP_OL) || '-';
  const pres = IMP_AP_OL.fecha_presentacion || '-';
  const casino= IMP_AP_OL.casino || '-';
  const qna = IMP_AP_OL.qna + '°' || '-';

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-3').html(pres))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-2').html(qna))


  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegIMP_AP_OL')
    .attr('id',IMP_AP_OL.id_registroIMP_AP_OL)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO IMPUESTO A APUESTAS ONLINE')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);
  if (IMP_AP_OL.tiene_archivos) {
    const btnFiles = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-archivos-IMP_AP_OL')
      .attr('type','button')
      .attr('data-id', IMP_AP_OL.id_registroIMP_AP_OL)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER ARCHIVOS ASOCIADOS')
      .append($('<i>').addClass('fa fa-file'));
    tdAcc.append(btnFiles);
  }
    var btnEdit = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-edit-IMP_AP_OL')
      .attr('type','button')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-id', IMP_AP_OL.id_registroIMP_AP_OL)
      .attr('title','EDITAR')
      .append($('<i>').addClass('fa fa-edit'));
    tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteIMP_AP_OL')
  .attr('id',IMP_AP_OL.id_registroIMP_AP_OL)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO IMPUESTO A APUESTAS ONLINE')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteIMP_AP_OL', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarIMP_AP_OL').attr('data-id', id);
  $('#modalEliminarIMP_AP_OL').modal('show');
});

$('#btn-eliminarIMP_AP_OL').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarIMP_AP_OL/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarIMP_AP_OL').modal('hide');
      cargarIMP_AP_OL({
        page:     $('#herramientasPaginacionIMP_AP_OL').getCurrentPage(),
        perPage:  $('#herramientasPaginacionIMP_AP_OL').getPageSize(),
        casino:   $('#FCasinoIMP_AP_OL').val(),
        desde: $('#fecha_IMP_AP_OLDesde').val(),
        hasta: $('#fecha_IMP_AP_OLHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});


$(document).on('click', '.btn-verRegIMP_AP_OL', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarIMP_AP_OL/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_IMP_AP_OL').val(fecha);
    $('#ver_fecha_pres_IMP_AP_OL').val(data.fecha_pres);
    $('#ver_casino_IMP_AP_OL').val(data.casino);

    $('#ver_qna_IMP_AP_OL').val(data.qna + '°');
    $('#ver_fecha_pago_IMP_AP_OL').val(data.fecha_pago);
    $('#ver_monto_pagado_IMP_AP_OL').val('$ ' + formatoAR(data.monto_pagado));
    $('#ver_monto_apuestas_IMP_AP_OL').val('$ ' + formatoAR(data.monto_apuestas));

    $('#ver_alicuota_IMP_AP_OL').val(formatoAR(data.alicuota) + ' %');
    $('#ver_impuesto_determinado_IMP_AP_OL').val('$ ' + formatoAR(data.impuesto_determinado));



    $('#modalVerIMP_AP_OL').modal('show');
  });
});

$('#btn-descargarIMP_AP_OLExcel').on('click',function(e){

  $('#collapseDescargarIMP_AP_OL .has-error').removeClass('has-error');
  $('#collapseDescargarIMP_AP_OL .js-error').remove();

  const casino = $('#FCasinoIMP_AP_OL').val() ? $('#FCasinoIMP_AP_OL').val() : 4;
  const desde = $('#fecha_IMP_AP_OLDesde').val();
  const hasta = $('#fecha_IMP_AP_OLHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoIMP_AP_OL').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_IMP_AP_OLDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarIMP_AP_OLXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarIMP_AP_OLXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});


$('#btn-descargarIMP_AP_OLCsv').on('click', function () {
  const casino = $('#FCasinoIMP_AP_OL').val() ? $('#FCasinoIMP_AP_OL').val() : 4;
  const desde = $('#fecha_IMP_AP_OLDesde').val();
  const hasta = $('#fecha_IMP_AP_OLHasta').val();
  let valid = true;
  $('#collapseDescargarIMP_AP_OL .has-error').removeClass('has-error');
  $('#collapseDescargarIMP_AP_OL .js-error').remove();

  if (!casino) {
    $('#DCasinoIMP_AP_OL').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_IMP_AP_OLDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarIMP_AP_OLCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


//IMPUESTOS A APUESTAS ONLINE IMP_AP_MTM

$(function(){
  instalarNumeroFlexibleAR([
    '#monto_pagadoIMP_AP_MTM','#monto_apuestasIMP_AP_MTM','#alicuotaIMP_AP_MTM','#impuesto_determinadoIMP_AP_MTM'
  ].join(', '), { decimales: 2 });

    instalarAutoImpuestoAR({
    base:     '#monto_apuestasIMP_AP_MTM',
    alicuota: '#alicuotaIMP_AP_MTM',
    impuesto: '#impuesto_determinadoIMP_AP_MTM',
    decImp:   2,
    aliEsPorcentaje: 1

  });
});

function cargarArchivosIMP_AP_MTMLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('IMP_AP_MTMId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosIMP_AP_MTM/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/IMP_AP_MTM/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="IMP_AP_MTM"  class="btn btn-sm btn-danger btn-del-archivo-IMP_AP_MTM" title="Quitar">')
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

$(document).on('click', '.btn-archivos-IMP_AP_MTM', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro IMP_AP_MTM');
  cargarArchivosIMP_AP_MTMLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-IMP_AP_MTM', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});


function abrirModalIMP_AP_MTMEditar(id){
  resetFormIMP_AP_MTM();
  $('#IMP_AP_MTM_modo').val('edit');
  $('#id_registroIMP_AP_MTM').val(id);
  $('#modalCargarIMP_AP_MTM .modal-title').text('| EDITAR REGISTRO DE IMPUESTO A APUESTAS MTM');
  $('#guardarRegistroIMP_AP_MTM').text('ACTUALIZAR');
  $('#modalCargarIMP_AP_MTM').modal('show');

  $.getJSON('/documentosContables/llenarIMP_AP_MTMEdit/'+id, function(d){
    var ym  = String(d.fecha || '').slice(0,7);
    var ymd = String(d.fecha_pres || '').slice(0,10);
    var ymdp= String(d.fecha_pago || '').slice(0,10);

    $('#fechaIMP_AP_MTM input[name="fecha_IMP_AP_MTM"]').val(ym).trigger('input').trigger('change');
    $('#fechaIMP_AP_MTMPres input[name="fecha_IMP_AP_MTMPres"]').val(ymd).trigger('input').trigger('change');
    $('input[name="fecha_pago_IMP_AP_MTM"]').val(ymdp).trigger('input').trigger('change');

    $('#casinoIMP_AP_MTM').val(d.casino).trigger('change');

    $('select[name="qnaIMP_AP_MTM"]').val(d.qna ?? '').trigger('change');
    $('input[name="monto_pagadoIMP_AP_MTM"]').val(formatoAR(d.monto_pagado ?? ''));
    $('input[name="monto_apuestasIMP_AP_MTM"]').val(formatoAR(d.monto_apuestas ?? ''));
    $('input[name="alicuotaIMP_AP_MTM"]').val(formatoAR(d.alicuota ?? ''));
    $('input[name="impuesto_determinadoIMP_AP_MTM"]').val(formatoAR(d.impuesto_determinado ?? ''));
    $('input[name="cantMTM_IMP_AP_MTM"]').val(formatoAR(d.cant_mtm ?? ''));
    $('[name="obsIMP_AP_MTM"]').val(d.obs || '');
  })
  .fail(function(xhr){
    console.error('[IMP_AP_MTM] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}

function resetFormIMP_AP_MTM(){
  var $f = $('#formNuevoRegistroIMP_AP_MTM');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameIMP_AP_MTM').val('No se ha seleccionado ningún archivo');
  $('#fileListIMP_AP_MTM').empty();
  $('#uploadIMP_AP_MTM').val('');
  $('#uploadsIMP_AP_MTMContainer').empty();
    $('#uploadsIMP_AP_MTMTable tbody').empty();
    $('#uploadsIMP_AP_MTMWrap').hide();
    $('#fileNameIMP_AP_MTM').val('No se ha seleccionado ningún archivo');
  }

function abrirModalIMP_AP_MTMCrear(){
  $('#IMP_AP_MTM_modo').val('create');
  $('#id_registroIMP_AP_MTM').val('');
  $('#modalCargarIMP_AP_MTM .modal-title').text('| NUEVO REGISTRO DE IMPUESTO APUESTAS MTM');
  $('#guardarRegistroIMP_AP_MTM').text('GENERAR');
  $('#modalCargarIMP_AP_MTM').modal('show');
}


$(document).on('click','#IMP_AP_MTM_nuevo',function(){
  abrirModalIMP_AP_MTMCrear();
});

$(document).on('click','.btn-edit-IMP_AP_MTM',function(){
  var id = $(this).data('id');
  abrirModalIMP_AP_MTMEditar(id);
});

$(document).on('click','#guardarRegistroIMP_AP_MTM',function(e){
  var $form = $('#formNuevoRegistroIMP_AP_MTM');
  let valid=true;
  var id   = $('#id_registroIMP_AP_MTM').val() || '';
  var modo = $('#IMP_AP_MTM_modo').val() || 'create';

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  valid = validarCampo('select[name="casinoIMP_AP_MTM"]','.col-md-3','El casino es requerido',valid);
  valid = validarCampoNum('input[name="alicuotaIMP_AP_MTM"]','.col-md-4','La alicuota es requerida.',valid);
  valid = validarCampoNum('input[name="monto_pagadoIMP_AP_MTM"]','.col-md-4','El monto es requerido.',valid);
  valid = validarCampoNum('input[name="cantMTM_IMP_AP_MTM"]','.col-md-4','El monto es requerido',valid);
  valid = validarCampoNum('input[name="monto_apuestasIMP_AP_MTM"]','.col-md-4','El monto es requerido',valid);
  valid = validarCampoNum('input[name="impuesto_determinadoIMP_AP_MTM"]','.col-md-4','El monto es requerido.',valid);

  valid = validarCampo('input[name="fecha_IMP_AP_MTM"]','.col-md-4','La fecha es requerida',valid);
  valid = validarCampo('input[name="fecha_pago_IMP_AP_MTM','.col-md-4','La fecha es requerida',valid);
  valid = validarCampo('input[name="fecha_IMP_AP_MTMPres"]','.col-md-5','La fecha es requerida', valid);


if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsIMP_AP_MTMContainer input[type="file"][name="uploadIMP_AP_MTM[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadIMP_AP_MTM[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadIMP_AP_MTM');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadIMP_AP_MTM[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarIMP_AP_MTM/'+id)
      : '/documentosContables/guardarIMP_AP_MTM';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarIMP_AP_MTM({
      page:     1,
      perPage:  $('#herramientasPaginacionIMP_AP_MTM').getPageSize(),
      casino:   $('#FCasinoIMP_AP_MTM').val(),
      desde: $('#fecha_IMP_AP_MTMDesde').val(),
      hasta: $('#fecha_IMP_AP_MTMHasta').val()
    });
    setTimeout(() => $('#modalCargarIMP_AP_MTM').modal('hide'), 1000);
    resetFormIMP_AP_MTM();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarIMP_AP_MTM').on('click', function(e){
  e.preventDefault();
  cargarIMP_AP_MTM({
    page:    1,
    perPage: $('#herramientasPaginacionIMP_AP_MTM').getPageSize(),
    casino:  $('#FCasinoIMP_AP_MTM').val(),
    desde: $('#fecha_IMP_AP_MTMDesde').val(),
    hasta: $('#fecha_IMP_AP_MTMHasta').val()
  });
});

function clickIndiceIMP_AP_MTM(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarIMP_AP_MTM({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoIMP_AP_MTM').val(),
    desde: $('#fecha_IMP_AP_MTMDesde').val(),
    hasta: $('#fecha_IMP_AP_MTMHasta').val()
  });
}

function cargarIMP_AP_MTM({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasIMP_AP_MTM',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaIMP_AP_MTM').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaIMP_AP_MTM').append(generarFilaIMP_AP_MTM(item));
      });

      $('#herramientasPaginacionIMP_AP_MTM').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIMP_AP_MTM
      );
      $('#herramientasPaginacionIMP_AP_MTM').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIMP_AP_MTM
      );

    },
    error(err) {
      console.error('Error cargando IMP_AP_MTM:', err);
    }
  });
}

function generarFilaIMP_AP_MTM(IMP_AP_MTM,controlador) {
  const fila = $('<tr>').attr('id', IMP_AP_MTM.id_registroIMP_AP_MTM);

  const fecha = convertirMesAno(IMP_AP_MTM.fecha_IMP_AP_MTM) || '-';
  const pres = IMP_AP_MTM.fecha_presentacion || '-';
  const casino= IMP_AP_MTM.casino || '-';
  const qna = IMP_AP_MTM.qna + '°' || '-';

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-3').html(pres))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-2').html(qna))


  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegIMP_AP_MTM')
    .attr('id',IMP_AP_MTM.id_registroIMP_AP_MTM)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO IMPUESTO A APUESTAS MTM')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);
  if (IMP_AP_MTM.tiene_archivos) {
    const btnFiles = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-archivos-IMP_AP_MTM')
      .attr('type','button')
      .attr('data-id', IMP_AP_MTM.id_registroIMP_AP_MTM)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER ARCHIVOS ASOCIADOS')
      .append($('<i>').addClass('fa fa-file'));
    tdAcc.append(btnFiles);
  }
    var btnEdit = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-edit-IMP_AP_MTM')
      .attr('type','button')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-id', IMP_AP_MTM.id_registroIMP_AP_MTM)
      .attr('title','EDITAR')
      .append($('<i>').addClass('fa fa-edit'));
    tdAcc.append(btnEdit);



  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteIMP_AP_MTM')
  .attr('id',IMP_AP_MTM.id_registroIMP_AP_MTM)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO IMPUESTO A APUESTAS MTM')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteIMP_AP_MTM', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarIMP_AP_MTM').attr('data-id', id);
  $('#modalEliminarIMP_AP_MTM').modal('show');
});

$('#btn-eliminarIMP_AP_MTM').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarIMP_AP_MTM/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarIMP_AP_MTM').modal('hide');
      cargarIMP_AP_MTM({
        page:     $('#herramientasPaginacionIMP_AP_MTM').getCurrentPage(),
        perPage:  $('#herramientasPaginacionIMP_AP_MTM').getPageSize(),
        casino:   $('#FCasinoIMP_AP_MTM').val(),
        desde: $('#fecha_IMP_AP_MTMDesde').val(),
        hasta: $('#fecha_IMP_AP_MTMHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});


$(document).on('click', '.btn-verRegIMP_AP_MTM', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarIMP_AP_MTM/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_IMP_AP_MTM').val(fecha);
    $('#ver_fecha_pres_IMP_AP_MTM').val(data.fecha_pres);
    $('#ver_casino_IMP_AP_MTM').val(data.casino);

    $('#ver_qna_IMP_AP_MTM').val(data.qna + '°');
    $('#ver_fecha_pago_IMP_AP_MTM').val(data.fecha_pago);
    $('#ver_monto_pagado_IMP_AP_MTM').val('$ ' + formatoAR(data.monto_pagado));
    $('#ver_monto_apuestas_IMP_AP_MTM').val('$ ' + formatoAR(data.monto_apuestas));

    $('#ver_cant_mtm_IMP_AP_MTM').val(data.cant_mtm);


    $('#ver_alicuota_IMP_AP_MTM').val(formatoAR(data.alicuota) + ' %');
    $('#ver_impuesto_determinado_IMP_AP_MTM').val('$ ' + formatoAR(data.impuesto_determinado));



    $('#modalVerIMP_AP_MTM').modal('show');
  });
});

$('#btn-descargarIMP_AP_MTMExcel').on('click',function(e){

  $('#collapseDescargarIMP_AP_MTM .has-error').removeClass('has-error');
  $('#collapseDescargarIMP_AP_MTM .js-error').remove();

  const casino = $('#FCasinoIMP_AP_MTM').val() ? $('#FCasinoIMP_AP_MTM').val() : 4;
  const desde = $('#fecha_IMP_AP_MTMDesde').val();
  const hasta = $('#fecha_IMP_AP_MTMHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoIMP_AP_MTM').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_IMP_AP_MTMDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarIMP_AP_MTMXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarIMP_AP_MTMXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});


$('#btn-descargarIMP_AP_MTMCsv').on('click', function () {
  const casino = $('#FCasinoIMP_AP_MTM').val() ? $('#FCasinoIMP_AP_MTM').val() : 4;
  const desde = $('#fecha_IMP_AP_MTMDesde').val();
  const hasta = $('#fecha_IMP_AP_MTMHasta').val();
  let valid = true;
  $('#collapseDescargarIMP_AP_MTM .has-error').removeClass('has-error');
  $('#collapseDescargarIMP_AP_MTM .js-error').remove();

  if (!casino) {
    $('#DCasinoIMP_AP_MTM').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_IMP_AP_MTMDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarIMP_AP_MTMCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});



//IMPUESTOS A PAGOS MAYORES DE MESAS DE PAÑO

instalarNumeroFlexibleAR('#importe_pesos_PagosMayoresMesas');
instalarNumeroFlexibleAR('#importe_dolares_PagosMayoresMesas');

function cargarArchivosPagosMayoresMesasLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('PagosMayoresMesasId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosPagosMayoresMesas/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/PagosMayoresMesas/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="PagosMayoresMesas"  class="btn btn-sm btn-danger btn-del-archivo-PagosMayoresMesas" title="Quitar">')
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

$(document).on('click', '.btn-archivos-PagosMayoresMesas', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro PagosMayoresMesas');
  cargarArchivosPagosMayoresMesasLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-PagosMayoresMesas', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalPagosMayoresMesasEditar(id){
  resetFormPagosMayoresMesas();
  $('#PagosMayoresMesas_modo').val('edit');
  $('#id_registroPagosMayoresMesas').val(id);
  $('#modalCargarPagosMayoresMesas .modal-title').text('| EDITAR REGISTRO DE PagosMayoresMesas');
  $('#guardarRegistroPagosMayoresMesas').text('ACTUALIZAR');
  $('#modalCargarPagosMayoresMesas').modal('show');

  $.getJSON('/documentosContables/llenarPagosMayoresMesasEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);
    $('#fechaPagosMayoresMesas input[name="fecha_PagosMayoresMesas"]').val(ym).trigger('input').trigger('change');
    $('#casinoPagosMayoresMesas').val(d.casino).trigger('change');
    $('input[name="cant_pagos_PagosMayoresMesas"]').val(d.cant_pagos ?? '');
    $('input[name="importe_pesos_PagosMayoresMesas"]').val(formatoAR(d.importe_pesos ?? ''));
    $('input[name="importe_dolares_PagosMayoresMesas"]').val(formatoAR(d.importe_usd ?? ''));
  })
  .fail(function(xhr){
    console.error('[PagosMayoresMesas] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}



function resetFormPagosMayoresMesas(){
  var $f = $('#formNuevoRegistroPagosMayoresMesas');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNamePagosMayoresMesas').val('No se ha seleccionado ningún archivo');
  $('#fileListPagosMayoresMesas').empty();
  $('#uploadPagosMayoresMesas').val('');
  $('#uploadsPagosMayoresMesasContainer').empty();
    $('#uploadsPagosMayoresMesasTable tbody').empty();
    $('#uploadsPagosMayoresMesasWrap').hide();
    $('#fileNamePagosMayoresMesas').val('No se ha seleccionado ningún archivo');
  }

function abrirModalPagosMayoresMesasCrear(){
  $('#PagosMayoresMesas_modo').val('create');
  $('#id_registroPagosMayoresMesas').val('');
  $('#modalCargarPagosMayoresMesas .modal-title').text('| NUEVO REGISTRO DE PagosMayoresMesas');
  $('#guardarRegistroPagosMayoresMesas').text('GENERAR');
  $('#modalCargarPagosMayoresMesas').modal('show');
}


$(document).on('click','#PagosMayoresMesas_nuevo',function(){
  abrirModalPagosMayoresMesasCrear();
});

$(document).on('click','.btn-edit-PagosMayoresMesas',function(){
  var id = $(this).data('id');
  abrirModalPagosMayoresMesasEditar(id);
});

$(document).on('click','#guardarRegistroPagosMayoresMesas',function(e){
  var $form = $('#formNuevoRegistroPagosMayoresMesas');
  let valid=true;
  var id   = $('#id_registroPagosMayoresMesas').val() || '';
   var modo = $('#PagosMayoresMesas_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoPagosMayoresMesas']",'.col-md-3','El casino es requerido.', valid);

valid = validarCampo("input[name='fecha_PagosMayoresMesas']",'.col-md-4','La fecha es requerida.', valid);

valid = validarCampoNum("input[name='importe_pesos_PagosMayoresMesas']",'.col-md-4','El importe en pesos es requerido.', valid);

valid = validarCampoNum("input[name='importe_dolares_PagosMayoresMesas']",'.col-md-4','El importe en dolares es requerido.', valid);



if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

   $form.serializeArray().forEach(function(p){
     fd.append(p.name, p.value);
   });

   $('#uploadsPagosMayoresMesasContainer input[type="file"][name="uploadPagosMayoresMesas[]"]').each(function () {
     var files = this.files || [];
     for (var i = 0; i < files.length; i++) {
       fd.append('uploadPagosMayoresMesas[]', files[i]);
     }
   });

   var cur = document.getElementById('uploadPagosMayoresMesas');
   if (cur && cur.files && cur.files.length) {
     for (var j = 0; j < cur.files.length; j++) {
       fd.append('uploadPagosMayoresMesas[]', cur.files[j]);
     }
   }

   var url = (modo === 'edit')
     ? ('/documentosContables/actualizarPagosMayoresMesas/'+id)
     : '/documentosContables/guardarPagosMayoresMesas';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarPagosMayoresMesas({
      page:     1,
      perPage:  $('#herramientasPaginacionPagosMayoresMesas').getPageSize(),
      casino:   $('#FCasinoPagosMayoresMesas').val(),
      desde: $('#fecha_PagosMayoresMesasDesde').val(),
      hasta: $('#fecha_PagosMayoresMesasHasta').val()
    });
    setTimeout(() => $('#modalCargarPagosMayoresMesas').modal('hide'), 1000);
        resetFormPagosMayoresMesas();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarPagosMayoresMesas').on('click', function(e){
  e.preventDefault();
  cargarPagosMayoresMesas({
    page:    1,
    perPage: $('#herramientasPaginacionPagosMayoresMesas').getPageSize(),
    casino:  $('#FCasinoPagosMayoresMesas').val(),
    desde: $('#fecha_PagosMayoresMesasDesde').val(),
    hasta: $('#fecha_PagosMayoresMesasHasta').val()
  });
});

function clickIndicePagosMayoresMesas(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPagosMayoresMesas({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoPagosMayoresMesas').val(),
    desde: $('#fecha_PagosMayoresMesasDesde').val(),
    hasta: $('#fecha_PagosMayoresMesasHasta').val()
  });
}

function cargarPagosMayoresMesas({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasPagosMayoresMesas',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaPagosMayoresMesas').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaPagosMayoresMesas').append(generarFilaPagosMayoresMesas(item));
      });

      $('#herramientasPaginacionPagosMayoresMesas').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePagosMayoresMesas
      );
      $('#herramientasPaginacionPagosMayoresMesas').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePagosMayoresMesas
      );

    },
    error(err) {
      console.error('Error cargando PagosMayoresMesas:', err);
    }
  });
}

function generarFilaPagosMayoresMesas(PagosMayoresMesas,controlador) {
  const fila = $('<tr>').attr('id', PagosMayoresMesas.id_registroPagosMayoresMesas);
  const fecha = convertirMesAno(PagosMayoresMesas.fecha_PagosMayoresMesas) || '-';
  const casino= PagosMayoresMesas.casino || '-';

  fila
    .append($('<td>').addClass('col-xs-5').html(fecha))
    .append($('<td>').addClass('col-xs-5').text(casino))


  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-1 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegPagosMayoresMesas')
    .attr('id',PagosMayoresMesas.id_registroPagosMayoresMesas)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER PAGO MAYOR DE MESA DE PAÑO')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

if (PagosMayoresMesas.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-PagosMayoresMesas')
    .attr('type','button')
    .attr('data-id', PagosMayoresMesas.id_registroPagosMayoresMesas)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-PagosMayoresMesas')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', PagosMayoresMesas.id_registroPagosMayoresMesas)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);



  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deletePagosMayoresMesas')
  .attr('id',PagosMayoresMesas.id_registroPagosMayoresMesas)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR PAGO MAYOR DE MESA DE PAÑO')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deletePagosMayoresMesas', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarPagosMayoresMesas').attr('data-id', id);
  $('#modalEliminarPagosMayoresMesas').modal('show');
});

$('#btn-eliminarPagosMayoresMesas').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarPagosMayoresMesas/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarPagosMayoresMesas').modal('hide');
      cargarPagosMayoresMesas({
        page:     $('#herramientasPaginacionPAgosMayoresMesas').getCurrentPage(),
        perPage:  $('#herramientasPaginacionPagosMayoresMesas').getPageSize(),
        casino:   $('#FCasinoPagosMayoresMesas').val(),
        desde: $('#fecha_PagosMayoresMesasDesde').val(),
        hasta: $('#fecha_PagosMayoresMesasHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});


$(document).on('click', '.btn-verRegPagosMayoresMesas', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarPagosMayoresMesas/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_PagosMayoresMesas').val(fecha);
    $('#ver_casino_PagosMayoresMesas').val(data.casino);

    $('#ver_cant_pagos_PagosMayoresMesas').val(data.cant_pagos);
    $('#ver_importe_pesos_PagosMayoresMesas').val('$ ' + formatoAR(data.importe_pesos));
    $('#ver_importe_usd_PagosMayoresMesas').val('USD ' + formatoAR(data.importe_usd));



    $('#modalVerPagosMayoresMesas').modal('show');
  });
});

$('#btn-descargarPagosMayoresMesasExcel').on('click',function(e){

  $('#collapseDescargarPagosMayoresMesas .has-error').removeClass('has-error');
  $('#collapseDescargarPagosMayoresMesas .js-error').remove();

  const casino = $('#FCasinoPagosMayoresMesas').val() ? $('#FCasinoPagosMayoresMesas').val() : 4;
  const desde = $('#fecha_PagosMayoresMesasDesde').val();
  const hasta = $('#fecha_PagosMayoresMesasHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoPagosMayoresMesas').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_PagosMayoresMesasDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarPagosMayoresMesasXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarPagosMayoresMesasXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});


$('#btn-descargarPagosMayoresMesasCsv').on('click', function () {
  const casino = $('#FCasinoPagosMayoresMesas').val() ? $('#FCasinoPagosMayoresMesas').val() : 4;
  const desde = $('#fecha_PagosMayoresMesasDesde').val();
  const hasta = $('#fecha_PagosMayoresMesasHasta').val();
  let valid = true;
  $('#collapseDescargarPagosMayoresMesas .has-error').removeClass('has-error');
  $('#collapseDescargarPagosMayoresMesas .js-error').remove();

  if (!casino) {
    $('#DCasinoPagosMayoresMesas').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_PagosMayoresMesasDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPagosMayoresMesasCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//IMPUESTOS A DEUDA ESTADO


function cargarArchivosDeudaEstadoLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('DeudaEstadoId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosDeudaEstado/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/DeudaEstado/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="DeudaEstado"  class="btn btn-sm btn-danger btn-del-archivo-DeudaEstado" title="Quitar">')
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

$(document).on('click', '.btn-archivos-DeudaEstado', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro DeudaEstado');
  cargarArchivosDeudaEstadoLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-DeudaEstado', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});

function abrirModalDeudaEstadoEditar(id){
  resetFormDeudaEstado();
  $('#DeudaEstado_modo').val('edit');
  $('#id_registroDeudaEstado').val(id);
  $('#modalCargarDeudaEstado .modal-title').text('| EDITAR REGISTRO DE DEUDA CON EL ESTADO');
  $('#guardarRegistroDeudaEstado').text('ACTUALIZAR');
  $('#modalCargarDeudaEstado').modal('show');

  $.getJSON('/documentosContables/llenarDeudaEstadoEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);

    $('#fechaDeudaEstado input[name="fecha_DeudaEstado"]')
      .val(ym).trigger('input').trigger('change');

    $('#casinoDeudaEstado').val(d.casino).trigger('change');

    $('#regIncumDeudaEstado')
      .val(String(d.registra_incumplimiento ?? ''))
      .trigger('change');

    $('[name="incumDeudaEstado"]').val(d.incumplimiento ?? '');

    $('#fechaDeudaEstadoPres input[name="fecha_DeudaEstadoPres"]')
      .val(d.fecha_consulta || '')
      .trigger('input').trigger('change');

    $('[name="obsDeudaEstado"]').val(d.obs || '');
  })
  .fail(function(xhr){
    console.error('[DeudaEstado] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}



function resetFormDeudaEstado(){
  var $f = $('#formNuevoRegistroDeudaEstado');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameDeudaEstado').val('No se ha seleccionado ningún archivo');
  $('#fileListDeudaEstado').empty();
  $('#uploadDeudaEstado').val('');
  $('#uploadsDeudaEstadoContainer').empty();
    $('#uploadsDeudaEstadoTable tbody').empty();
    $('#uploadsDeudaEstadoWrap').hide();
    $('#fileNameDeudaEstado').val('No se ha seleccionado ningún archivo');
  }

function abrirModalDeudaEstadoCrear(){
  $('#DeudaEstado_modo').val('create');
  $('#id_registroDeudaEstado').val('');
  $('#modalCargarDeudaEstado .modal-title').text('| NUEVO REGISTRO DE DeudaEstado');
  $('#guardarRegistroDeudaEstado').text('GENERAR');
  $('#modalCargarDeudaEstado').modal('show');
}


$(document).on('click','#DeudaEstado_nuevo',function(){
  abrirModalDeudaEstadoCrear();
});

$(document).on('click','.btn-edit-DeudaEstado',function(){
  var id = $(this).data('id');
  abrirModalDeudaEstadoEditar(id);
});

$(document).on('click','#guardarRegistroDeudaEstado',function(e){
  var $form = $('#formNuevoRegistroDeudaEstado');
  let valid=true;
  var id   = $('#id_registroDeudaEstado').val() || '';
  var modo = $('#DeudaEstado_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();


  valid = validarCampo('select[name="casinoDeudaEstado"]','.col-md-3','El  es requerido.', valid);

  valid = validarCampo('input[name="fecha_DeudaEstadoPres"]','.col-md-5','La  es requerida.', valid);

  valid = validarCampo('input[name="fecha_DeudaEstado"]','.col-md-4','La fecha es requerida.', valid);



if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsDeudaEstadoContainer input[type="file"][name="uploadDeudaEstado[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadDeudaEstado[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadDeudaEstado');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadDeudaEstado[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarDeudaEstado/'+id)
      : '/documentosContables/guardarDeudaEstado';
$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarDeudaEstado({
      page:     1,
      perPage:  $('#herramientasPaginacionDeudaEstado').getPageSize(),
      casino:   $('#FCasinoDeudaEstado').val(),
      desde: $('#fecha_DeudaEstadoDesde').val(),
      hasta: $('#fecha_DeudaEstadoHasta').val()
    });
    setTimeout(() => $('#modalCargarDeudaEstado').modal('hide'), 1000);
    resetFormDeudaEstado();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarDeudaEstado').on('click', function(e){
  e.preventDefault();
  cargarDeudaEstado({
    page:    1,
    perPage: $('#herramientasPaginacionDeudaEstado').getPageSize(),
    casino:  $('#FCasinoDeudaEstado').val(),
    desde: $('#fecha_DeudaEstadoDesde').val(),
    hasta: $('#fecha_DeudaEstadoHasta').val()
  });
});

function clickIndiceDeudaEstado(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarDeudaEstado({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoDeudaEstado').val(),
    desde: $('#fecha_DeudaEstadoDesde').val(),
    hasta: $('#fecha_DeudaEstadoHasta').val()
  });
}

function cargarDeudaEstado({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasDeudaEstado',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaDeudaEstado').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaDeudaEstado').append(generarFilaDeudaEstado(item));
      });

      $('#herramientasPaginacionDeudaEstado').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDeudaEstado
      );
      $('#herramientasPaginacionDeudaEstado').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDeudaEstado
      );

    },
    error(err) {
      console.error('Error cargando DeudaEstado:', err);
    }
  });
}

function generarFilaDeudaEstado(DeudaEstado,controlador) {
  const fila = $('<tr>').attr('id', DeudaEstado.id_registroDeudaEstado);

  const fecha = convertirMesAno(DeudaEstado.fecha_DeudaEstado) || '-';
  const pres = DeudaEstado.fecha_presentacion || '-';
  const casino= DeudaEstado.casino || '-';
  const incum = DeudaEstado.incumplimiento==1 ? 'Si' : 'No';

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-3').html(pres))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-2').html(incum))


  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

 if(incum=='Si'){
  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegDeudaEstado')
    .attr('id',DeudaEstado.id_registroDeudaEstado)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER INCUMPLIMIENTO')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);
}
if (DeudaEstado.tiene_archivos) {
const btnFiles = $('<button>')
  .addClass('btn btn-info btn-sm mr-1 btn-archivos-DeudaEstado')
  .attr('type','button')
  .attr('data-id', DeudaEstado.id_registroDeudaEstado)
  .attr('data-toggle', 'tooltip')
  .attr('data-placement','bottom')
  .attr('title', 'VER ARCHIVOS ASOCIADOS')
  .append($('<i>').addClass('fa fa-file'));
tdAcc.append(btnFiles);
}
var btnEdit = $('<button>')
  .addClass('btn btn-info btn-sm mr-1 btn-edit-DeudaEstado')
  .attr('type','button')
  .attr('data-toggle', 'tooltip')
  .attr('data-placement','bottom')
  .attr('data-id', DeudaEstado.id_registroDeudaEstado)
  .attr('title','EDITAR')
  .append($('<i>').addClass('fa fa-edit'));
tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteDeudaEstado')
  .attr('id',DeudaEstado.id_registroDeudaEstado)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR DEUDA CONSOLIDADA CON EL ESTADO')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteDeudaEstado', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarDeudaEstado').attr('data-id', id);
  $('#modalEliminarDeudaEstado').modal('show');
});

$('#btn-eliminarDeudaEstado').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarDeudaEstado/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarDeudaEstado').modal('hide');
      cargarDeudaEstado({
        page:     $('#herramientasPaginacionDeudaEstado').getCurrentPage(),
        perPage:  $('#herramientasPaginacionDeudaEstado').getPageSize(),
        casino:   $('#FCasinoDeudaEstado').val(),
        desde: $('#fecha_DeudaEstadoDesde').val(),
        hasta: $('#fecha_DeudaEstadoHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});


$(document).on('click', '.btn-verRegDeudaEstado', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarDeudaEstado/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    $('#ver_incumplimiento_DeudaEstado').val(data.incumplimiento);

    $('#modalVerDeudaEstado').modal('show');
  });
});

$('#btn-descargarDeudaEstadoExcel').on('click',function(e){

  $('#collapseDescargarDeudaEstado .has-error').removeClass('has-error');
  $('#collapseDescargarDeudaEstado .js-error').remove();

  const casino = $('#FCasinoDeudaEstado').val() ? $('#FCasinoDeudaEstado').val() : 4;
  const desde = $('#fecha_DeudaEstadoDesde').val();
  const hasta = $('#fecha_DeudaEstadoHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoDeudaEstado').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_DeudaEstadoDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarDeudaEstadoXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarDeudaEstadoXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});


$('#btn-descargarDeudaEstadoCsv').on('click', function () {
  const casino = $('#FCasinoDeudaEstado').val() ? $('#FCasinoDeudaEstado').val() : 4;
  const desde = $('#fecha_DeudaEstadoDesde').val();
  const hasta = $('#fecha_DeudaEstadoHasta').val();
  let valid = true;
  $('#collapseDescargarDeudaEstado .has-error').removeClass('has-error');
  $('#collapseDescargarDeudaEstado .js-error').remove();

  if (!casino) {
    $('#DCasinoDeudaEstado').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_DeudaEstadoDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarDeudaEstadoCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//REPORTE DE OPERACIONES Y LAVADO DE ACTIVOS

function cargarArchivosReporteYLavadoLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('ReporteYLavadoId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosReporteYLavado/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/ReporteYLavado/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="ReporteYLavado"  class="btn btn-sm btn-danger btn-del-archivo-ReporteYLavado" title="Quitar">')
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

$(document).on('click', '.btn-archivos-ReporteYLavado', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro ReporteYLavado');
  cargarArchivosReporteYLavadoLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-ReporteYLavado', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});

function abrirModalReporteYLavadoEditar(id){
  resetFormReporteYLavado();
  $('#ReporteYLavado_modo').val('edit');
  $('#id_registroReporteYLavado').val(id);
  $('#modalCargarReporteYLavado .modal-title').text('| EDITAR REGISTRO DE REPORTES DE OPERACIONES - LAVADO DE ACTIVOS');
  $('#guardarRegistroReporteYLavado').text('ACTUALIZAR');
  $('#modalCargarReporteYLavado').modal('show');

  $.getJSON('/documentosContables/llenarReporteYLavadoEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);
    $('#fechaReporteYLavado input[name="fecha_ReporteYLavado"]').val(ym).trigger('input').trigger('change');
    $('#casinoReporteYLavado').val(d.casino).trigger('change');
    $('input[name="reporte_sistematico_ReporteYLavado"]').val(d.reporte_sistematico ?? '');
    $('input[name="reporte_operaciones_ReporteYLavado"]').val(d.reporte_operaciones ?? '');
  })
  .fail(function(xhr){
    console.error('[ReporteYLavado] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro Reporte y Lavado.');
  });
}



function resetFormReporteYLavado(){
  var $f = $('#formNuevoRegistroReporteYLavado');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameReporteYLavado').val('No se ha seleccionado ningún archivo');
  $('#fileListReporteYLavado').empty();
  $('#uploadReporteYLavado').val('');
  $('#uploadsReporteYLavadoContainer').empty();
    $('#uploadsReporteYLavadoTable tbody').empty();
    $('#uploadsReporteYLavadoWrap').hide();
    $('#fileNameReporteYLavado').val('No se ha seleccionado ningún archivo');
  }

function abrirModalReporteYLavadoCrear(){
  $('#ReporteYLavado_modo').val('create');
  $('#id_registroReporteYLavado').val('');
  $('#modalCargarReporteYLavado .modal-title').text('| NUEVO REGISTRO DE REPORTE DE OPERACIONES - LAVADO ACTIVOS');
  $('#guardarRegistroReporteYLavado').text('GENERAR');
  $('#modalCargarReporteYLavado').modal('show');
}


$(document).on('click','#ReporteYLavado_nuevo',function(){
  abrirModalReporteYLavadoCrear();
});

$(document).on('click','.btn-edit-ReporteYLavado',function(){
  var id = $(this).data('id');
  abrirModalReporteYLavadoEditar(id);
});

$(document).on('click','#guardarRegistroReporteYLavado',function(e){
  var $form = $('#formNuevoRegistroReporteYLavado');
  let valid=true;
  var id   = $('#id_registroReporteYLavado').val() || '';
    var modo = $('#ReporteYLavado_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoReporteYLavado']",'.col-md-3','El casino es requerido.', valid);

valid = validarCampo("input[name='fecha_ReporteYLavado']",'.col-md-4','La fecha es requerida.', valid);

valid = validarCampoNum("input[name='reporte_sistematico_ReporteYLavado']",'.col-md-5','La cantidad es requerida.', valid);

valid = validarCampoNum("input[name='reporte_operaciones_ReporteYLavado']",'.col-md-7','La cantidad es requerida.', valid);



if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsReporteYLavadoContainer input[type="file"][name="uploadReporteYLavado[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadReporteYLavado[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadReporteYLavado');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadReporteYLavado[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarReporteYLavado/'+id)
      : '/documentosContables/guardarReporteYLavado';
$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarReporteYLavado({
      page:     1,
      perPage:  $('#herramientasPaginacionReporteYLavado').getPageSize(),
      casino:   $('#FCasinoReporteYLavado').val(),
      desde: $('#fecha_ReporteYLavadoDesde').val(),
      hasta: $('#fecha_ReporteYLavadoHasta').val()
    });
    setTimeout(() => $('#modalCargarReporteYLavado').modal('hide'), 1000);
        resetFormReporteYLavado();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarReporteYLavado').on('click', function(e){
  e.preventDefault();
  cargarReporteYLavado({
    page:    1,
    perPage: $('#herramientasPaginacionReporteYLavado').getPageSize(),
    casino:  $('#FCasinoReporteYLavado').val(),
    desde: $('#fecha_ReporteYLavadoDesde').val(),
    hasta: $('#fecha_ReporteYLavadoHasta').val()
  });
});

function clickIndiceReporteYLavado(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarReporteYLavado({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoReporteYLavado').val(),
    desde: $('#fecha_ReporteYLavadoDesde').val(),
    hasta: $('#fecha_ReporteYLavadoHasta').val()
  });
}

function cargarReporteYLavado({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasReporteYLavado',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaReporteYLavado').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaReporteYLavado').append(generarFilaReporteYLavado(item));
      });

      $('#herramientasPaginacionReporteYLavado').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceReporteYLavado
      );
      $('#herramientasPaginacionReporteYLavado').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceReporteYLavado
      );

    },
    error(err) {
      console.error('Error cargando ReporteYLavado:', err);
    }
  });
}

function generarFilaReporteYLavado(ReporteYLavado,controlador) {
  const fila = $('<tr>').attr('id', ReporteYLavado.id_registroReporteYLavado);
  const fecha = convertirMesAno(ReporteYLavado.fecha_ReporteYLavado) || '-';
  const casino= ReporteYLavado.casino || '-';
  const sistematico = ReporteYLavado.reporte_sistematico || '-';
  const operaciones = ReporteYLavado.reporte_operaciones || '-';

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-3').text(sistematico))
    .append($('<td>').addClass('col-xs-3').text(operaciones))
  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-1 d-flex flex-wrap');


if (ReporteYLavado.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-ReporteYLavado')
    .attr('type','button')
    .attr('data-id', ReporteYLavado.id_registroReporteYLavado)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-ReporteYLavado')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', ReporteYLavado.id_registroReporteYLavado)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteReporteYLavado')
  .attr('id',ReporteYLavado.id_registroReporteYLavado)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO DE OPERACIONES Y LAVADO DE ACTIVOS')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteReporteYLavado', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarReporteYLavado').attr('data-id', id);
  $('#modalEliminarReporteYLavado').modal('show');
});

$('#btn-eliminarReporteYLavado').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarReporteYLavado/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarReporteYLavado').modal('hide');
      cargarReporteYLavado({
        page:     $('#herramientasPaginacionReporteYLavado').getCurrentPage(),
        perPage:  $('#herramientasPaginacionReporteYLavado').getPageSize(),
        casino:   $('#FCasinoReporteYLavado').val(),
        desde: $('#fecha_ReporteYLavadoDesde').val(),
        hasta: $('#fecha_ReporteYLavadoHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

//no se usa
$(document).on('click', '.btn-verRegReporteYLavado', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarReporteYLavado/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_ReporteYLavado').val(fecha);
    $('#ver_casino_ReporteYLavado').val(data.casino);


    $('#modalVerReporteYLavado').modal('show');
  });
});


$('#btn-descargarReporteYLavadoCsv').on('click', function () {
  const casino = $('#FCasinoReporteYLavado').val() ? $('#FCasinoReporteYLavado').val() : 4;
  const desde = $('#fecha_RegistrosReporteYLavadoDesde').val();
  const hasta = $('#fecha_RegistrosReporteYLavadoHasta').val();
  let valid = true;
  $('#collapseDescargarReporteYLavado .has-error').removeClass('has-error');
  $('#collapseDescargarReporteYLavado .js-error').remove();

  if (!casino) {
    $('#DCasinoReporteYLavado').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_ReporteYLavadoDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarReporteYLavadoCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


$('#btn-descargarReporteYLavadoExcel').on('click',function(e){

  $('#collapseDescargarReporteYLavado .has-error').removeClass('has-error');
  $('#collapseDescargarReporteYLavado .js-error').remove();

  const casino = $('#FCasinoReporteYLavado').val() ? $('#FCasinoReporteYLavado').val() : 4;
  const desde = $('#fecha_RegistrosReporteYLavadoDesde').val();
  const hasta = $('#fecha_RegistrosReporteYLavadoHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoReporteYLavado').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_ReporteYLavadoDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarReporteYLavadoXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarReporteYLavadoXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

//REGISTROS CONTABLES


$(function(){
  instalarNumeroFlexibleAR('#mtm_pesos_RegistrosContables, #mtm_usd_RegistrosContables, #mp_pesos_RegistrosContables');
  instalarNumeroFlexibleAR('#mp_usd_RegistrosContables, #bingo_RegistrosContables, #jol_RegistrosContables');
  instalarNumeroFlexibleAR('#total_RegistrosContables, #total_usd_RegistrosContables');

  instalarAutoSumaAR({
    sources: ['#mtm_pesos_RegistrosContables','#mp_pesos_RegistrosContables','#bingo_RegistrosContables','#jol_RegistrosContables'],
    target:  '#total_RegistrosContables',
    decimales: 2
  });
  instalarAutoSumaAR({
    sources: ['#mtm_usd_RegistrosContables','#mp_usd_RegistrosContables'],
    target:  '#total_usd_RegistrosContables',
    decimales: 2
  });
});



function cargarArchivosRegistrosContablesLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('RegistrosContablesId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosRegistrosContables/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/RegistrosContables/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="RegistrosContables"  class="btn btn-sm btn-danger btn-del-archivo-RegistrosContables" title="Quitar">')
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

$(document).on('click', '.btn-archivos-RegistrosContables', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro RegistrosContables');
  cargarArchivosRegistrosContablesLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-RegistrosContables', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalRegistrosContablesEditar(id){
  resetFormRegistrosContables();
  $('#RegistrosContables_modo').val('edit');
  $('#id_registroRegistrosContables').val(id);
  $('#modalCargarRegistrosContables .modal-title').text('| EDITAR REGISTRO DE REGISTROS CONTABLES');
  $('#guardarRegistroRegistrosContables').text('ACTUALIZAR');
  $('#modalCargarRegistrosContables').modal('show');

  $.getJSON('/documentosContables/llenarRegistrosContablesEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);
    $('#fechaRegistrosContables input[name="fecha_RegistrosContables"]').val(ym).trigger('input').trigger('change');
    $('#casinoRegistrosContables').val(d.casino).trigger('change');

    $('input[name="mtm_pesos_RegistrosContables"]').val(formatoAR(d.mtm ?? ''));
    $('input[name="mtm_usd_RegistrosContables"]').val(formatoAR(d.mtm_usd ?? ''));
    $('input[name="mp_pesos_RegistrosContables"]').val(formatoAR(d.mp ?? ''));
    $('input[name="mp_usd_RegistrosContables"]').val(formatoAR(d.mp_usd ?? ''));
    $('input[name="bingo_RegistrosContables"]').val(formatoAR(d.bingo ?? ''));
    $('input[name="jol_RegistrosContables"]').val(formatoAR(d.jol ?? ''));
    $('input[name="total_RegistrosContables"]').val(formatoAR(d.total ?? ''));
    $('input[name="total_usd_RegistrosContables"]').val(formatoAR(d.total_usd ?? ''));

  })
  .fail(function(xhr){
    console.error('[RegistrosContables] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}


function resetFormRegistrosContables(){
  var $f = $('#formNuevoRegistroRegistrosContables');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameRegistrosContables').val('No se ha seleccionado ningún archivo');
  $('#fileListRegistrosContables').empty();
  $('#uploadRegistrosContables').val('');
  $('#uploadsRegistrosContablesContainer').empty();
    $('#uploadsRegistrosContablesTable tbody').empty();
    $('#uploadsRegistrosContablesWrap').hide();
    $('#fileNameRegistrosContables').val('No se ha seleccionado ningún archivo');
  }

function abrirModalRegistrosContablesCrear(){
  $('#RegistrosContables_modo').val('create');
  $('#id_registroRegistrosContables').val('');
  $('#modalCargarRegistrosContables .modal-title').text('| NUEVO REGISTRO DE REGISTROS CONTABLES');
  $('#guardarRegistroRegistrosContables').text('GENERAR');
  $('#modalCargarRegistrosContables').modal('show');
}


$(document).on('click','#RegistrosContables_nuevo',function(){
  abrirModalRegistrosContablesCrear();
});

$(document).on('click','.btn-edit-RegistrosContables',function(){
  var id = $(this).data('id');
  abrirModalRegistrosContablesEditar(id);
});
$(document).on('click','#guardarRegistroRegistrosContables',function(e){
  var $form = $('#formNuevoRegistroRegistrosContables');
  let valid=true;
  var id   = $('#id_registroRegistrosContables').val() || '';
  var modo = $('#RegistrosContables_modo').val() || 'create';


  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoRegistrosContables']",'.col-md-3','El casino es requerido.', valid);

valid = validarCampo("input[name='fecha_RegistrosContables']",'.col-md-4','La fecha es requerida.', valid);

valid = validarCampoNumSiHayValor($("input[name='mp_pesos_RegistrosContables']"),'.col-md-5','La cantidad es requerida.', valid);

valid = validarCampoNumSiHayValor($("input[name='mp_usd_RegistrosContables']"),'.col-md-5','La cantidad es requerida.', valid);


valid = validarCampoNumSiHayValor($("input[name='mtm_pesos_RegistrosContables']"),'.col-md-5','La cantidad es requerida.', valid);

valid = validarCampoNumSiHayValor($("input[name='mtm_usd_RegistrosContables']"),'.col-md-5','La cantidad es requerida.', valid);


valid = validarCampoNumSiHayValor($("input[name='bingo_RegistrosContables']"),'.col-md-5','La cantidad es requerida.', valid);

valid = validarCampoNumSiHayValor($("input[name='jol_RegistrosContables']"),'.col-md-5','La cantidad es requerida.', valid);

valid = validarCampoNumSiHayValor($("input[name='total_usd_RegistrosContables']"),'.col-md-5','El total es necesario', valid);

valid = validarCampoNumSiHayValor($("input[name='total_RegistrosContables']"),'.col-md-5','El total es necesario', valid);


if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsRegistrosContablesContainer input[type="file"][name="uploadRegistrosContables[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadRegistrosContables[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadRegistrosContables');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadRegistrosContables[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarRegistrosContables/'+id)
      : '/documentosContables/guardarRegistrosContables';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarRegistrosContables({
      page:     1,
      perPage:  $('#herramientasPaginacionRegistrosContables').getPageSize(),
      casino:   $('#FCasinoRegistrosContables').val(),
      desde: $('#fecha_RegistrosContablesDesde').val(),
      hasta: $('#fecha_RegistrosContablesHasta').val()
    });
    setTimeout(() => $('#modalCargarRegistrosContables').modal('hide'), 1000);

    resetFormRegistrosContables();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarRegistrosContables').on('click', function(e){
  e.preventDefault();
  cargarRegistrosContables({
    page:    1,
    perPage: $('#herramientasPaginacionRegistrosContables').getPageSize(),
    casino:  $('#FCasinoRegistrosContables').val(),
    desde: $('#fecha_RegistrosContablesDesde').val(),
    hasta: $('#fecha_RegistrosContablesHasta').val()
  });
});

function clickIndiceRegistrosContables(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarRegistrosContables({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoRegistrosContables').val(),
    desde: $('#fecha_RegistrosContablesDesde').val(),
    hasta: $('#fecha_RegistrosContablesHasta').val()
  });
}

function cargarRegistrosContables({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasRegistrosContables',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaRegistrosContables').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaRegistrosContables').append(generarFilaRegistrosContables(item));
      });

      $('#herramientasPaginacionRegistrosContables').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceRegistrosContables
      );
      $('#herramientasPaginacionRegistrosContables').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceRegistrosContables
      );

    },
    error(err) {
      console.error('Error cargando RegistrosContables:', err);
    }
  });
}

function generarFilaRegistrosContables(RegistrosContables,controlador) {
  const fila = $('<tr>').attr('id', RegistrosContables.id_registroRegistrosContables);
  const fecha = convertirMesAno(RegistrosContables.fecha_RegistrosContables) || '-';
  const casino= RegistrosContables.casino || '-';
  const total = '$ '+ formatoAR(RegistrosContables.total) || '-';
  const total_usd = 'USD '+ formatoAR(RegistrosContables.total_usd) || '-';

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-3').text(total))
    .append($('<td>').addClass('col-xs-3').text(total_usd))
  const tdAcc = $('<td>').addClass('col-xs-1 d-flex flex-wrap');

  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegRegistrosContables')
    .attr('id',RegistrosContables.id_registroRegistrosContables)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO CONTABLE')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);


if (RegistrosContables.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-RegistrosContables')
    .attr('type','button')
    .attr('data-id', RegistrosContables.id_registroRegistrosContables)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-RegistrosContables')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', RegistrosContables.id_registroRegistrosContables)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteRegistrosContables')
  .attr('id',RegistrosContables.id_registroRegistrosContables)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO CONTABLE')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteRegistrosContables', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarRegistrosContables').attr('data-id', id);
  $('#modalEliminarRegistrosContables').modal('show');
});

$('#btn-eliminarRegistrosContables').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarRegistrosContables/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarRegistrosContables').modal('hide');
      cargarRegistrosContables({
        page:     $('#herramientasPaginacionRegistrosContables').getCurrentPage(),
        perPage:  $('#herramientasPaginacionRegistrosContables').getPageSize(),
        casino:   $('#FCasinoRegistrosContables').val(),
        desde: $('#fecha_RegistrosContablesDesde').val(),
        hasta: $('#fecha_RegistrosContablesHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

//no se usa
$(document).on('click', '.btn-verRegRegistrosContables', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarRegistrosContables/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_RegistrosContables').val(fecha);
    $('#ver_casino_RegistrosContables').val(data.casino);

    $('#ver_mtm_RegistrosContables').val('$ '+ formatoAR(data.mtm));
    $('#ver_mtm_usd_RegistrosContables').val('USD '+ formatoAR(data.mtm_usd));

    $('#ver_mp_RegistrosContables').val('$ '+ formatoAR(data.mp));
    $('#ver_mp_usd_RegistrosContables').val('USD '+ formatoAR(data.mp_usd));

    $('#ver_bingo_RegistrosContables').val('$ '+ formatoAR(data.bingo));
    $('#ver_jol_RegistrosContables').val('$ '+ formatoAR(data.jol));

    $('#ver_total_RegistrosContables').val('$ '+ formatoAR(data.total));
    $('#ver_total_usd_RegistrosContables').val('USD '+ formatoAR(data.total_usd));

    $('#modalVerRegistrosContables').modal('show');
  });
});

$('#btn-descargarRegistrosContablesExcel').on('click',function(e){

  $('#collapseDescargarRegistrosContables .has-error').removeClass('has-error');
  $('#collapseDescargarRegistrosContables .js-error').remove();

  const casino = $('#FCasinoRegistrosContables').val() ? $('#FCasinoRegistrosContables').val() : 4 ;
  const desde = $('#fecha_RegistrosContablesDesde').val();
  const hasta = $('#fecha_RegistrosContablesHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoRegistrosContables').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_RegistrosContablesDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarRegistrosContablesXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarRegistrosContablesXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarRegistrosContablesCsv').on('click', function () {
  const casino = $('#FCasinoRegistrosContables').val() ? $('#FCasinoRegistrosContables').val() : 4 ;
  const desde = $('#fecha_RegistrosContablesDesde').val();
  const hasta = $('#fecha_RegistrosContablesHasta').val();
  let valid = true;
  $('#collapseDescargarRegistrosContables .has-error').removeClass('has-error');
  $('#collapseDescargarRegistrosContables .js-error').remove();

  if (!casino) {
    $('#DCasinoRegistrosContables').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_RegistrosContablesDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarRegistrosContablesCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


// APORTES PATRONALES


instalarNumeroFlexibleAR('#monto_pagado_AportesPatronales');

function cargarArchivosAportesPatronalesLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('AportesPatronalesId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosAportesPatronales/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/AportesPatronales/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="AportesPatronales"  class="btn btn-sm btn-danger btn-del-archivo-AportesPatronales" title="Quitar">')
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

$(document).on('click', '.btn-archivos-AportesPatronales', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro AportesPatronales');
  cargarArchivosAportesPatronalesLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-AportesPatronales', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalAportesPatronalesEditar(id){
  resetFormAportesPatronales();
  $('#AportesPatronales_modo').val('edit');
  $('#id_registroAportesPatronales').val(id);
  $('#modalCargarAportesPatronales .modal-title').text('| EDITAR REGISTRO DE APORTES PATRONALES');
  $('#guardarRegistroAportesPatronales').text('ACTUALIZAR');
  $('#modalCargarAportesPatronales').modal('show');

  $.getJSON('/documentosContables/llenarAportesPatronalesEdit/'+id, function(d){
    var ym  = String(d.fecha || '').slice(0,7);
    var ymd = String(d.fecha_pres || '').slice(0,10);
    var ymdp= String(d.fecha_pago || '').slice(0,10);

    $('#fechaAportesPatronales input[name="fecha_AportesPatronales"]').val(ym).trigger('input').trigger('change');
    $('#fechaAportesPatronalesPres input[name="fecha_AportesPatronalesPres"]').val(ymd).trigger('input').trigger('change');
    $('#fecha_pagoAportesPatronales input[name="fecha_pago_AportesPatronales"]').val(ymdp).trigger('input').trigger('change');

    $('#casinoAportesPatronales').val(d.casino).trigger('change');
    $('input[name="cant_empleados_AportesPatronales"]').val(d.cant_empleados ?? '');
    $('input[name="monto_pagado_AportesPatronales"]').val(formatoAR(d.monto_pagado ?? ''));
    $('[name="obs_AportesPatronales"]').val(d.obs || '');
  })
  .fail(function(xhr){
    console.error('[AportesPatronales] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}



function resetFormAportesPatronales(){
  var $f = $('#formNuevoRegistroAportesPatronales');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameAportesPatronales').val('No se ha seleccionado ningún archivo');
  $('#fileListAportesPatronales').empty();
  $('#uploadAportesPatronales').val('');
  $('#uploadsAportesPatronalesContainer').empty();
    $('#uploadsAportesPatronalesTable tbody').empty();
    $('#uploadsAportesPatronalesWrap').hide();
    $('#fileNameAportesPatronales').val('No se ha seleccionado ningún archivo');
  }

function abrirModalAportesPatronalesCrear(){
  $('#AportesPatronales_modo').val('create');
  $('#id_registroAportesPatronales').val('');
  $('#modalCargarAportesPatronales .modal-title').text('| NUEVO REGISTRO DE APORTES PATRONALES');
  $('#guardarRegistroAportesPatronales').text('GENERAR');
  $('#modalCargarAportesPatronales').modal('show');
}


$(document).on('click','#AportesPatronales_nuevo',function(){
  abrirModalAportesPatronalesCrear();
});

$(document).on('click','.btn-edit-AportesPatronales',function(){
  var id = $(this).data('id');
  abrirModalAportesPatronalesEditar(id);
});

$(document).on('click','#guardarRegistroAportesPatronales',function(e){
  var $form = $('#formNuevoRegistroAportesPatronales');
  let valid=true;
  var id   = $('#id_registroAportesPatronales').val() || '';
   var modo = $('#AportesPatronales_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoAportesPatronales']",'.col-md-3','El casino es requerido.', valid);

valid = validarCampo("input[name='fecha_AportesPatronales']",'.col-md-4','La fecha es requerida.', valid);

valid = validarCampo("input[name='fecha_AportesPatronalesPres']",'.col-md-5','La fecha es requerida.', valid);

valid = validarCampo("input[name='fecha_pago_AportesPatronales']",'.col-md-4','La fecha es requerida.', valid);


valid = validarCampoNum("input[name='cant_empleados_AportesPatronales']",'.col-md-4','La cantidad es requerida.', valid);


valid = validarCampoNum("input[name='monto_pagado_AportesPatronales']",'.col-md-4','La cantidad es requerida.', valid);
if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsAportesPatronalesContainer input[type="file"][name="uploadAportesPatronales[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadAportesPatronales[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadAportesPatronales');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadAportesPatronales[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarAportesPatronales/'+id)
      : '/documentosContables/guardarAportesPatronales';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarAportesPatronales({
      page:     1,
      perPage:  $('#herramientasPaginacionAportesPatronales').getPageSize(),
      casino:   $('#FCasinoAportesPatronales').val(),
      desde: $('#fecha_AportesPatronalesDesde').val(),
      hasta: $('#fecha_AportesPatronalesHasta').val()
    });
    setTimeout(() => $('#modalCargarAportesPatronales').modal('hide'), 1000);
        resetFormAportesPatronales();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarAportesPatronales').on('click', function(e){
  e.preventDefault();
  cargarAportesPatronales({
    page:    1,
    perPage: $('#herramientasPaginacionAportesPatronales').getPageSize(),
    casino:  $('#FCasinoAportesPatronales').val(),
    desde: $('#fecha_AportesPatronalesDesde').val(),
    hasta: $('#fecha_AportesPatronalesHasta').val()
  });
});

function clickIndiceAportesPatronales(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarAportesPatronales({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoAportesPatronales').val(),
    desde: $('#fecha_AportesPatronalesDesde').val(),
    hasta: $('#fecha_AportesPatronalesHasta').val()
  });
}

function cargarAportesPatronales({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasAportesPatronales',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaAportesPatronales').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaAportesPatronales').append(generarFilaAportesPatronales(item));
      });

      $('#herramientasPaginacionAportesPatronales').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceAportesPatronales
      );
      $('#herramientasPaginacionAportesPatronales').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceAportesPatronales
      );

    },
    error(err) {
      console.error('Error cargando AportesPatronales:', err);
    }
  });
}

function generarFilaAportesPatronales(AportesPatronales,controlador) {
  const fila = $('<tr>').attr('id', AportesPatronales.id_registroAportesPatronales);
  const fecha = convertirMesAno(AportesPatronales.fecha_AportesPatronales) || '-';
  const casino= AportesPatronales.casino || '-';

  fila
    .append($('<td>').addClass('col-xs-5').html(fecha))
    .append($('<td>').addClass('col-xs-5').text(casino))
  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegAportesPatronales')
    .attr('id',AportesPatronales.id_registroAportesPatronales)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER APORTE PATRONAL')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);


if (AportesPatronales.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-AportesPatronales')
    .attr('type','button')
    .attr('data-id', AportesPatronales.id_registroAportesPatronales)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-AportesPatronales')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', AportesPatronales.id_registroAportesPatronales)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteAportesPatronales')
  .attr('id',AportesPatronales.id_registroAportesPatronales)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR APORTE PATRONAL')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteAportesPatronales', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarAportesPatronales').attr('data-id', id);
  $('#modalEliminarAportesPatronales').modal('show');
});

$('#btn-eliminarAportesPatronales').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarAportesPatronales/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarAportesPatronales').modal('hide');
      cargarAportesPatronales({
        page:     $('#herramientasPaginacionAportesPatronales').getCurrentPage(),
        perPage:  $('#herramientasPaginacionAportesPatronales').getPageSize(),
        casino:   $('#FCasinoAportesPatronales').val(),
        desde: $('#fecha_AportesPatronalesDesde').val(),
        hasta: $('#fecha_AportesPatronalesHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

//no se usa
$(document).on('click', '.btn-verRegAportesPatronales', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarAportesPatronales/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_AportesPatronales').val(fecha);
    $('#ver_casino_AportesPatronales').val(data.casino);

    $('#ver_fecha_pago_AportesPatronales').val(data.fecha_pago);
    $('#ver_fecha_AportesPatronalesPres').val(data.fecha_pres);


    $('#ver_cant_empleados_AportesPatronales').val(data.cant_empleados);
    $('#ver_monto_pagado_AportesPatronales').val('$ ' + formatoAR(data.monto_pagado));
    $('#ver_obs_AportesPatronales').val(data.obs_AportesPatronales);



    $('#modalVerAportesPatronales').modal('show');
  });
});

$('#btn-descargarAportesPatronalesExcel').on('click',function(e){

  $('#collapseDescargarAportesPatronales .has-error').removeClass('has-error');
  $('#collapseDescargarAportesPatronales .js-error').remove();

  const casino = $('#FCasinoAportesPatronales').val() ? $('#FCasinoAportesPatronales').val() : 4;
  const desde = $('#fecha_AportesPatronalesDesde').val();
  const hasta = $('#fecha_AportesPatronalesHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoAportesPatronales').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_AportesPatronalesDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarAportesPatronalesXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarAportesPatronalesXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarAportesPatronalesCsv').on('click', function () {
  const casino = $('#FCasinoAportesPatronales').val() ? $('#FCasinoAportesPatronales').val() : 4;
  const desde = $('#fecha_AportesPatronalesDesde').val();
  const hasta = $('#fecha_AportesPatronalesHasta').val();
  let valid = true;
  $('#collapseDescargarAportesPatronales .has-error').removeClass('has-error');
  $('#collapseDescargarAportesPatronales .js-error').remove();

  if (!casino) {
    $('#DCasinoAportesPatronales').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_AportesPatronalesDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarAportesPatronalesCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


//PROMO TICKETS

instalarNumeroFlexibleAR('#importe_PromoTickets');

function cargarArchivosPromoTicketsLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('PromoTicketsId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosPromoTickets/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/PromoTickets/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="PromoTickets"  class="btn btn-sm btn-danger btn-del-archivo-PromoTickets" title="Quitar">')
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

$(document).on('click', '.btn-archivos-PromoTickets', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro PromoTickets');
  cargarArchivosPromoTicketsLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-PromoTickets', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalPromoTicketsEditar(id){
  resetFormPromoTickets();
  $('#PromoTickets_modo').val('edit');
  $('#id_registroPromoTickets').val(id);
  $('#modalCargarPromoTickets .modal-title').text('| EDITAR REGISTRO DE PROMOTICKETS');
  $('#guardarRegistroPromoTickets').text('ACTUALIZAR');
  $('#modalCargarPromoTickets').modal('show');

  $.getJSON('/documentosContables/llenarPromoTicketsEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);

    $('#fechaPromoTickets input[name="fecha_PromoTickets"]').val(ym).trigger('input').trigger('change');
    $('#casinoPromoTickets').val(d.casino).trigger('change');

    $('input[name="cant_PromoTickets"]').val(d.cantidad ?? '');
    $('input[name="importe_PromoTickets"]').val(formatoAR(d.importe ?? ''));
  })
  .fail(function(xhr){
    console.error('[PromoTickets] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro PromoTickets.');
  });
}



function resetFormPromoTickets(){
  var $f = $('#formNuevoRegistroPromoTickets');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNamePromoTickets').val('No se ha seleccionado ningún archivo');
  $('#fileListPromoTickets').empty();
  $('#uploadPromoTickets').val('');
  $('#uploadsPromoTicketsContainer').empty();
    $('#uploadsPromoTicketsTable tbody').empty();
    $('#uploadsPromoTicketsWrap').hide();
    $('#fileNamePromoTickets').val('No se ha seleccionado ningún archivo');
  }

function abrirModalPromoTicketsCrear(){
  $('#PromoTickets_modo').val('create');
  $('#id_registroPromoTickets').val('');
  $('#modalCargarPromoTickets .modal-title').text('| NUEVO REGISTRO DE PROMOTICKETS');
  $('#guardarRegistroPromoTickets').text('GENERAR');
  $('#modalCargarPromoTickets').modal('show');
}


$(document).on('click','#PromoTickets_nuevo',function(){
  abrirModalPromoTicketsCrear();
});

$(document).on('click','.btn-edit-PromoTickets',function(){
  var id = $(this).data('id');
  abrirModalPromoTicketsEditar(id);
});

$(document).on('click','#guardarRegistroPromoTickets',function(e){
  var $form = $('#formNuevoRegistroPromoTickets');
  let valid=true;
  var id   = $('#id_registroPromoTickets').val() || '';
   var modo = $('#PromoTickets_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoPromoTickets']",'.col-md-3','El casino es requerido.', valid);

valid = validarCampo("input[name='fecha_PromoTickets']",'.col-md-4','La fecha es requerida.', valid);


valid = validarCampoNum("input[name='cant_PromoTickets']",'.col-md-5','La cantidad es requerida.', valid);


valid = validarCampoNum("input[name='importe_PromoTickets']",'.col-md-5','El importe es requerido.', valid);

if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsPromoTicketsContainer input[type="file"][name="uploadPromoTickets[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadPromoTickets[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadPromoTickets');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadPromoTickets[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarPromoTickets/'+id)
      : '/documentosContables/guardarPromoTickets';
$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarPromoTickets({
      page:     1,
      perPage:  $('#herramientasPaginacionPromoTickets').getPageSize(),
      casino:   $('#FCasinoPromoTickets').val(),
      desde: $('#fecha_PromoTicketsDesde').val(),
      hasta: $('#fecha_PromoTicketsHasta').val()
    });
    setTimeout(() => $('#modalCargarPromoTickets').modal('hide'), 1000);
        resetFormPromoTickets();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarPromoTickets').on('click', function(e){
  e.preventDefault();
  cargarPromoTickets({
    page:    1,
    perPage: $('#herramientasPaginacionPromoTickets').getPageSize(),
    casino:  $('#FCasinoPromoTickets').val(),
    desde: $('#fecha_PromoTicketsDesde').val(),
    hasta: $('#fecha_PromoTicketsHasta').val()
  });
});

function clickIndicePromoTickets(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPromoTickets({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoPromoTickets').val(),
    desde: $('#fecha_PromoTicketsDesde').val(),
    hasta: $('#fecha_PromoTicketsHasta').val()
  });
}

function cargarPromoTickets({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasPromoTickets',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaPromoTickets').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaPromoTickets').append(generarFilaPromoTickets(item));
      });

      $('#herramientasPaginacionPromoTickets').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePromoTickets
      );
      $('#herramientasPaginacionPromoTickets').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePromoTickets
      );

    },
    error(err) {
      console.error('Error cargando PromoTickets:', err);
    }
  });
}

function generarFilaPromoTickets(PromoTickets,controlador) {
  const fila = $('<tr>').attr('id', PromoTickets.id_registroPromoTickets);
  const fecha = convertirMesAno(PromoTickets.fecha_PromoTickets) || '-';
  const casino= PromoTickets.casino || '-';
  const importe = '$ '+ formatoAR(PromoTickets.importe) || '-';
  const cantidad = PromoTickets.cantidad || '-';

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-3').text(cantidad))
    .append($('<td>').addClass('col-xs-3').text(importe))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  if (PromoTickets.tiene_archivos) {
    const btnFiles = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-archivos-PromoTickets')
      .attr('type','button')
      .attr('data-id', PromoTickets.id_registroPromoTickets)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER ARCHIVOS ASOCIADOS')
      .append($('<i>').addClass('fa fa-file'));
    tdAcc.append(btnFiles);
  }
    var btnEdit = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-edit-PromoTickets')
      .attr('type','button')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-id', PromoTickets.id_registroPromoTickets)
      .attr('title','EDITAR')
      .append($('<i>').addClass('fa fa-edit'));
    tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deletePromoTickets')
  .attr('id',PromoTickets.id_registroPromoTickets)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR PROMO TICKET')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deletePromoTickets', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarPromoTickets').attr('data-id', id);
  $('#modalEliminarPromoTickets').modal('show');
});

$('#btn-eliminarPromoTickets').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarPromoTickets/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarPromoTickets').modal('hide');
      cargarPromoTickets({
        page:     $('#herramientasPaginacionPromoTickets').getCurrentPage(),
        perPage:  $('#herramientasPaginacionPromoTickets').getPageSize(),
        casino:   $('#FCasinoPromoTickets').val(),
        desde: $('#fecha_PromoTicketsDesde').val(),
        hasta: $('#fecha_PromoTicketsHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

//no se usa
$(document).on('click', '.btn-verRegPromoTickets', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarPromoTickets/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_PromoTickets').val(fecha);
    $('#ver_casino_PromoTickets').val(data.casino);

    $('#ver_fecha_pago_PromoTickets').val(data.fecha_pago);
    $('#ver_fecha_PromoTicketsPres').val(data.fecha_pres);


    $('#ver_cant_empleados_PromoTickets').val(data.cant_empleados);
    $('#ver_monto_pagado_PromoTickets').val(data.monto_pagado);
    $('#ver_obs_PromoTickets').val(data.obs_PromoTickets);



    $('#modalVerPromoTickets').modal('show');
  });
});

$('#btn-descargarPromoTicketsExcel').on('click',function(e){

  $('#collapseDescargarPromoTickets .has-error').removeClass('has-error');
  $('#collapseDescargarPromoTickets .js-error').remove();

  const casino = $('#FCasinoPromoTickets').val() ? $('#FCasinoPromoTickets').val() : 4;
  const desde = $('#fecha_PromoTicketsDesde').val();
  const hasta = $('#fecha_PromoTicketsHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoPromoTickets').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_PromoTicketsDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarPromoTicketsXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarPromoTicketsXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarPromoTicketsCsv').on('click', function () {
  const casino = $('#FCasinoPromoTickets').val() ? $('#FCasinoPromoTickets').val() : 4;
  const desde = $('#fecha_PromoTicketsDesde').val();
  const hasta = $('#fecha_PromoTicketsHasta').val();
  let valid = true;
  $('#collapseDescargarPromoTickets .has-error').removeClass('has-error');
  $('#collapseDescargarPromoTickets .js-error').remove();

  if (!casino) {
    $('#DCasinoPromoTickets').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_PromoTicketsDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPromoTicketsCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


//POZOS ACUMULADOS LINKEADOS E INDIVIDUALES

instalarNumeroFlexibleAR('#importe_PozosAcumuladosLinkeados');


function cargarArchivosPozosAcumuladosLinkeadosLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('PozosAcumuladosLinkeadosId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosPozosAcumuladosLinkeados/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/PozosAcumuladosLinkeados/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="PozosAcumuladosLinkeados"  class="btn btn-sm btn-danger btn-del-archivo-PozosAcumuladosLinkeados" title="Quitar">')
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

$(document).on('click', '.btn-archivos-PozosAcumuladosLinkeados', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro PozosAcumuladosLinkeados');
  cargarArchivosPozosAcumuladosLinkeadosLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-PozosAcumuladosLinkeados', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalPozosAcumuladosLinkeadosEditar(id){
  resetFormPozosAcumuladosLinkeados();
  $('#PozosAcumuladosLinkeados_modo').val('edit');
  $('#id_registroPozosAcumuladosLinkeados').val(id);
  $('#modalCargarPozosAcumuladosLinkeados .modal-title').text('| EDITAR REGISTRO DE POZOS ACUMULADOS LINKEADOS E INDIVIDUALES');
  $('#guardarRegistroPozosAcumuladosLinkeados').text('ACTUALIZAR');
  $('#modalCargarPozosAcumuladosLinkeados').modal('show');

  $.getJSON('/documentosContables/llenarPozosAcumuladosLinkeadosEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);

    $('#fechaPozosAcumuladosLinkeados input[name="fecha_PozosAcumuladosLinkeados"]').val(ym).trigger('input').trigger('change');
    $('#casinoPozosAcumuladosLinkeados').val(d.casino).trigger('change');
    $('input[name="importe_PozosAcumuladosLinkeados"]').val(formatoAR(d.importe ?? ''));
  })
  .fail(function(xhr){
    console.error('[PozosAcumuladosLinkeados] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}



function resetFormPozosAcumuladosLinkeados(){
  var $f = $('#formNuevoRegistroPozosAcumuladosLinkeados');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNamePozosAcumuladosLinkeados').val('No se ha seleccionado ningún archivo');
  $('#fileListPozosAcumuladosLinkeados').empty();
  $('#uploadPozosAcumuladosLinkeados').val('');
  $('#uploadsPozosAcumuladosLinkeadosContainer').empty();
    $('#uploadsPozosAcumuladosLinkeadosTable tbody').empty();
    $('#uploadsPozosAcumuladosLinkeadosWrap').hide();
    $('#fileNamePozosAcumuladosLinkeados').val('No se ha seleccionado ningún archivo');
  }

function abrirModalPozosAcumuladosLinkeadosCrear(){
  $('#PozosAcumuladosLinkeados_modo').val('create');
  $('#id_registroPozosAcumuladosLinkeados').val('');
  $('#modalCargarPozosAcumuladosLinkeados .modal-title').text('| NUEVO REGISTRO DE POZOS ACUMULADOS LINKEADOS E INDIVIDUALES');
  $('#guardarRegistroPozosAcumuladosLinkeados').text('GENERAR');
  $('#modalCargarPozosAcumuladosLinkeados').modal('show');
}


$(document).on('click','#PozosAcumuladosLinkeados_nuevo',function(){
  abrirModalPozosAcumuladosLinkeadosCrear();
});

$(document).on('click','.btn-edit-PozosAcumuladosLinkeados',function(){
  var id = $(this).data('id');
  abrirModalPozosAcumuladosLinkeadosEditar(id);
});


$(document).on('click','#guardarRegistroPozosAcumuladosLinkeados',function(e){
  var $form = $('#formNuevoRegistroPozosAcumuladosLinkeados');
  let valid=true;
  var id   = $('#id_registroPozosAcumuladosLinkeados').val() || '';
   var modo = $('#PozosAcumuladosLinkeados_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoPozosAcumuladosLinkeados']",'.col-md-3','El casino es requerido.', valid);

valid = validarCampo("input[name='fecha_PozosAcumuladosLinkeados']",'.col-md-4','La fecha es requerida.', valid);

valid = validarCampoNum("input[name='importe_PozosAcumuladosLinkeados']",'.col-md-6','El importe es requerido.', valid);
if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsPozosAcumuladosLinkeadosContainer input[type="file"][name="uploadPozosAcumuladosLinkeados[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadPozosAcumuladosLinkeados[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadPozosAcumuladosLinkeados');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadPozosAcumuladosLinkeados[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarPozosAcumuladosLinkeados/'+id)
      : '/documentosContables/guardarPozosAcumuladosLinkeados';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarPozosAcumuladosLinkeados({
      page:     1,
      perPage:  $('#herramientasPaginacionPozosAcumuladosLinkeados').getPageSize(),
      casino:   $('#FCasinoPozosAcumuladosLinkeados').val(),
      desde: $('#fecha_PozosAcumuladosLinkeadosDesde').val(),
      hasta: $('#fecha_PozosAcumuladosLinkeadosHasta').val()
    });
    setTimeout(() => $('#modalCargarPozosAcumuladosLinkeados').modal('hide'), 1000);
        resetFormPozosAcumuladosLinkeados();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarPozosAcumuladosLinkeados').on('click', function(e){
  e.preventDefault();
  cargarPozosAcumuladosLinkeados({
    page:    1,
    perPage: $('#herramientasPaginacionPozosAcumuladosLinkeados').getPageSize(),
    casino:  $('#FCasinoPozosAcumuladosLinkeados').val(),
    desde: $('#fecha_PozosAcumuladosLinkeadosDesde').val(),
    hasta: $('#fecha_PozosAcumuladosLinkeadosHasta').val()
  });
});

function clickIndicePozosAcumuladosLinkeados(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPozosAcumuladosLinkeados({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoPozosAcumuladosLinkeados').val(),
    desde: $('#fecha_PozosAcumuladosLinkeadosDesde').val(),
    hasta: $('#fecha_PozosAcumuladosLinkeadosHasta').val()
  });
}

function cargarPozosAcumuladosLinkeados({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasPozosAcumuladosLinkeados',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaPozosAcumuladosLinkeados').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaPozosAcumuladosLinkeados').append(generarFilaPozosAcumuladosLinkeados(item));
      });

      $('#herramientasPaginacionPozosAcumuladosLinkeados').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePozosAcumuladosLinkeados
      );
      $('#herramientasPaginacionPozosAcumuladosLinkeados').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePozosAcumuladosLinkeados
      );

    },
    error(err) {
      console.error('Error cargando PozosAcumuladosLinkeados:', err);
    }
  });
}

function generarFilaPozosAcumuladosLinkeados(PozosAcumuladosLinkeados,controlador) {
  const fila = $('<tr>').attr('id', PozosAcumuladosLinkeados.id_registroPozosAcumuladosLinkeados);
  const fecha = convertirMesAno(PozosAcumuladosLinkeados.fecha_PozosAcumuladosLinkeados) || '-';
  const casino= PozosAcumuladosLinkeados.casino || '-';
  const importe = '$ '+ formatoAR(PozosAcumuladosLinkeados.importe) || '-';

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-5').text(importe))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  if (PozosAcumuladosLinkeados.tiene_archivos) {
    const btnFiles = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-archivos-PozosAcumuladosLinkeados')
      .attr('type','button')
      .attr('data-id', PozosAcumuladosLinkeados.id_registroPozosAcumuladosLinkeados)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER ARCHIVOS ASOCIADOS')
      .append($('<i>').addClass('fa fa-file'));
    tdAcc.append(btnFiles);
  }
    var btnEdit = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-edit-PozosAcumuladosLinkeados')
      .attr('type','button')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-id', PozosAcumuladosLinkeados.id_registroPozosAcumuladosLinkeados)
      .attr('title','EDITAR')
      .append($('<i>').addClass('fa fa-edit'));
    tdAcc.append(btnEdit);



  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deletePozosAcumuladosLinkeados')
  .attr('id',PozosAcumuladosLinkeados.id_registroPozosAcumuladosLinkeados)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR POZO ACUMULADO LINKEADO')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deletePozosAcumuladosLinkeados', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarPozosAcumuladosLinkeados').attr('data-id', id);
  $('#modalEliminarPozosAcumuladosLinkeados').modal('show');
});

$('#btn-eliminarPozosAcumuladosLinkeados').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarPozosAcumuladosLinkeados/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarPozosAcumuladosLinkeados').modal('hide');
      cargarPozosAcumuladosLinkeados({
        page:     $('#herramientasPaginacionPozosAcumuladosLinkeados').getCurrentPage(),
        perPage:  $('#herramientasPaginacionPozosAcumuladosLinkeados').getPageSize(),
        casino:   $('#FCasinoPozosAcumuladosLinkeados').val(),
        desde: $('#fecha_PozosAcumuladosLinkeadosDesde').val(),
        hasta: $('#fecha_PozosAcumuladosLinkeadosHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

//no se usa
$(document).on('click', '.btn-verRegPozosAcumuladosLinkeados', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarPozosAcumuladosLinkeados/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_PozosAcumuladosLinkeados').val(fecha);
    $('#ver_casino_PozosAcumuladosLinkeados').val(data.casino);

    $('#ver_fecha_pago_PozosAcumuladosLinkeados').val(data.fecha_pago);
    $('#ver_fecha_PozosAcumuladosLinkeadosPres').val(data.fecha_pres);


    $('#ver_cant_empleados_PozosAcumuladosLinkeados').val(data.cant_empleados);
    $('#ver_monto_pagado_PozosAcumuladosLinkeados').val(data.monto_pagado);
    $('#ver_obs_PozosAcumuladosLinkeados').val(data.obs_PozosAcumuladosLinkeados);



    $('#modalVerPozosAcumuladosLinkeados').modal('show');
  });
});

$('#btn-descargarPozosAcumuladosLinkeadosExcel').on('click',function(e){

  $('#collapseDescargarPozosAcumuladosLinkeados .has-error').removeClass('has-error');
  $('#collapseDescargarPozosAcumuladosLinkeados .js-error').remove();

  const casino = $('#FCasinoPozosAcumuladosLinkeados').val() ? $('#FCasinoPozosAcumuladosLinkeados').val() : 4;
  const desde = $('#fecha_PozosAcumuladosLinkeadosDesde').val();
  const hasta = $('#fecha_PozosAcumuladosLinkeadosHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoPozosAcumuladosLinkeados').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_PozosAcumuladosLinkeadosDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarPozosAcumuladosLinkeadosXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarPozosAcumuladosLinkeadosXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarPozosAcumuladosLinkeadosCsv').on('click', function () {
  const casino = $('#FCasinoPozosAcumuladosLinkeados').val() ? $('#FCasinoPozosAcumuladosLinkeados').val() : 4;
  const desde = $('#fecha_PozosAcumuladosLinkeadosDesde').val();
  const hasta = $('#fecha_PozosAcumuladosLinkeadosHasta').val();
  let valid = true;
  $('#collapseDescargarPozosAcumuladosLinkeados .has-error').removeClass('has-error');
  $('#collapseDescargarPozosAcumuladosLinkeados .js-error').remove();

  if (!casino) {
    $('#DCasinoPozosAcumuladosLinkeados').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_PozosAcumuladosLinkeadosDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPozosAcumuladosLinkeadosCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//CONTRIBUCION ENTE TURISTICO

$(function(){
  instalarNumeroFlexibleAR([
    '#base_imponible_ContribEnteTuristico','#alicuota_ContribEnteTuristico','#impuesto_determinado_ContribEnteTuristico','#monto_pagado_ContribEnteTuristico'
  ].join(', '), { decimales: 2 });

    instalarAutoImpuestoAR({
    base:     '#base_imponible_ContribEnteTuristico',
    alicuota: '#alicuota_ContribEnteTuristico',
    impuesto: '#impuesto_determinado_ContribEnteTuristico',
    decImp:   2,
  });
});

function cargarArchivosContribEnteTuristicoLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('ContribEnteTuristicoId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosContribEnteTuristico/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/ContribEnteTuristico/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="ContribEnteTuristico"  class="btn btn-sm btn-danger btn-del-archivo-ContribEnteTuristico" title="Quitar">')
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

$(document).on('click', '.btn-archivos-ContribEnteTuristico', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro ContribEnteTuristico');
  cargarArchivosContribEnteTuristicoLista(id);
  $('#modalArchivosAsociados').modal('show');
});


function abrirModalContribEnteTuristicoEditar(id){
  resetFormContribEnteTuristico();
  $('#ContribEnteTuristico_modo').val('edit');
  $('#id_registroContribEnteTuristico').val(id);
  $('#modalCargarContribEnteTuristico .modal-title').text('| EDITAR REGISTRO DE CONTRIB. ENTE TURÍSTICO');
  $('#guardarRegistroContribEnteTuristico').text('ACTUALIZAR');
  $('#modalCargarContribEnteTuristico').modal('show');

  $.getJSON('/documentosContables/llenarContribEnteTuristicoEdit/'+id, function(d){
    var ym   = String(d.fecha || '').slice(0,7);
    var ymd  = String(d.fecha_pres || '').slice(0,10);
    var ymdV = String(d.fecha_venc || '').slice(0,10);

    $('#fechaContribEnteTuristico input[name="fecha_ContribEnteTuristico"]').val(ym).trigger('input').trigger('change');
    $('#fechaContribEnteTuristicoPres input[name="fecha_ContribEnteTuristicoPres"]').val(ymd).trigger('input').trigger('change');
    $('input[name="fecha_venc_ContribEnteTuristico"]').val(ymdV).trigger('input').trigger('change');

    $('input[name="base_imponible_ContribEnteTuristico"]').val(formatoAR(d.base_imponible ?? ''));
    $('input[name="alicuota_ContribEnteTuristico"]').val(formatoAR(d.alicuota ?? ''));
    $('input[name="impuesto_determinado_ContribEnteTuristico"]').val(formatoAR(d.impuesto_determinado ?? ''));
    $('input[name="monto_pagado_ContribEnteTuristico"]').val(formatoAR(d.monto_pagado ?? ''));
    $('[name="obs_ContribEnteTuristico"]').val(d.obs || '');

    var $cas = $('#casinoContribEnteTuristico');
    $cas.val(3).trigger('change');
    $cas.closest('.col-md-4, .col-md-6, .col-md-12').show();
  })
  .fail(function(xhr){
    console.error('[ContribEnteTuristico] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}



function resetFormContribEnteTuristico(){
  var $f = $('#formNuevoRegistroContribEnteTuristico');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameContribEnteTuristico').val('No se ha seleccionado ningún archivo');
  $('#fileListContribEnteTuristico').empty();
  $('#uploadContribEnteTuristico').val('');
  $('#uploadsContribEnteTuristicoContainer').empty();
    $('#uploadsContribEnteTuristicoTable tbody').empty();
    $('#uploadsContribEnteTuristicoWrap').hide();
    $('#fileNameContribEnteTuristico').val('No se ha seleccionado ningún archivo');
  }

function abrirModalContribEnteTuristicoCrear(){
  $('#ContribEnteTuristico_modo').val('create');
  $('#id_registroContribEnteTuristico').val('');
  $('#modalCargarContribEnteTuristico .modal-title').text('| NUEVO REGISTRO DE CONTRIB. ENTE TURISTICO');
  $('#guardarRegistroContribEnteTuristico').text('GENERAR');
  $('#modalCargarContribEnteTuristico').modal('show');
}


$(document).on('click','#ContribEnteTuristico_nuevo',function(){
  abrirModalContribEnteTuristicoCrear();
});

$(document).on('click','.btn-edit-ContribEnteTuristico',function(){
  var id = $(this).data('id');
  abrirModalContribEnteTuristicoEditar(id);
});

$(document).on('click','#guardarRegistroContribEnteTuristico',function(e){
  var $form = $('#formNuevoRegistroContribEnteTuristico');
  let valid=true;
  var id   = $('#id_registroContribEnteTuristico').val() || '';
    var modo = $('#ContribEnteTuristico_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();


valid = validarCampo("input[name='fecha_ContribEnteTuristico']",'.col-md-3','La fecha es requerida.', valid);

valid = validarCampo("input[name='fecha_ContribEnteTuristicoPres']",'.col-md-6 ','La fecha es requerida.', valid);

valid = validarCampo("input[name='fecha_venc_ContribEnteTuristico']",'.col-md-6','La fecha es requerida.', valid);



valid = validarCampoNum("input[name='alicuota_ContribEnteTuristico']",'.col-md-4','El importe es requerido.', valid);
valid = validarCampoNum("input[name='impuesto_determinado_ContribEnteTuristico']",'.col-md-4','El importe es requerido.', valid);
valid = validarCampoNum("input[name='base_imponible_ContribEnteTuristico']",'.col-md-4','El importe es requerido.', valid);
valid = validarCampoNum("input[name='monto_pagado_ContribEnteTuristico']",'.col-md-6','El importe es requerido.', valid);

if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);

  var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsContribEnteTuristicoContainer input[type="file"][name="uploadContribEnteTuristico[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadContribEnteTuristico[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadContribEnteTuristico');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadContribEnteTuristico[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarContribEnteTuristico/'+id)
      : '/documentosContables/guardarContribEnteTuristico';


$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarContribEnteTuristico({
      page:     1,
      perPage:  $('#herramientasPaginacionContribEnteTuristico').getPageSize(),
      casino:   $('#FCasinoContribEnteTuristico').val(),
      desde: $('#fecha_ContribEnteTuristicoDesde').val(),
      hasta: $('#fecha_ContribEnteTuristicoHasta').val()
    });
    setTimeout(() => $('#modalCargarContribEnteTuristico').modal('hide'), 1000);
    resetFormContribEnteTuristico();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarContribEnteTuristico').on('click', function(e){
  e.preventDefault();
  cargarContribEnteTuristico({
    page:    1,
    perPage: $('#herramientasPaginacionContribEnteTuristico').getPageSize(),
    casino:  $('#FCasinoContribEnteTuristico').val(),
    desde: $('#fecha_ContribEnteTuristicoDesde').val(),
    hasta: $('#fecha_ContribEnteTuristicoHasta').val()
  });
});

function clickIndiceContribEnteTuristico(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarContribEnteTuristico({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoContribEnteTuristico').val(),
    desde: $('#fecha_ContribEnteTuristicoDesde').val(),
    hasta: $('#fecha_ContribEnteTuristicoHasta').val()
  });
}

function cargarContribEnteTuristico({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasContribEnteTuristico',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaContribEnteTuristico').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaContribEnteTuristico').append(generarFilaContribEnteTuristico(item));
      });

      $('#herramientasPaginacionContribEnteTuristico').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceContribEnteTuristico
      );
      $('#herramientasPaginacionContribEnteTuristico').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceContribEnteTuristico
      );

    },
    error(err) {
      console.error('Error cargando ContribEnteTuristico:', err);
    }
  });
}

function generarFilaContribEnteTuristico(ContribEnteTuristico,controlador) {
  const fila = $('<tr>').attr('id', ContribEnteTuristico.id_registroContribEnteTuristico);
  const fecha = convertirMesAno(ContribEnteTuristico.fecha_ContribEnteTuristico) || '-';
  const fecha_pres = ContribEnteTuristico.fecha_pres || '-';
  const casino= ContribEnteTuristico.casino || '-';
  const monto_pagado = '$ '+ContribEnteTuristico.monto_pagado || '-';

  fila
    .append($('<td>').addClass('col-xs-3').html(fecha))
    .append($('<td>').addClass('col-xs-3').text(fecha_pres))
    .append($('<td>').addClass('col-xs-3').text(monto_pagado))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegContribEnteTuristico')
    .attr('id',ContribEnteTuristico.id_registroContribEnteTuristico)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER CONTRIBUCIÓN ENTE TURISTICO')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

  if (ContribEnteTuristico.tiene_archivos) {
    const btnFiles = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-archivos-ContribEnteTuristico')
      .attr('type','button')
      .attr('data-id', ContribEnteTuristico.id_registroContribEnteTuristico)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER ARCHIVOS ASOCIADOS')
      .append($('<i>').addClass('fa fa-file'));
    tdAcc.append(btnFiles);
  }
    var btnEdit = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-edit-ContribEnteTuristico')
      .attr('type','button')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-id', ContribEnteTuristico.id_registroContribEnteTuristico)
      .attr('title','EDITAR')
      .append($('<i>').addClass('fa fa-edit'));
    tdAcc.append(btnEdit);



  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteContribEnteTuristico')
  .attr('id',ContribEnteTuristico.id_registroContribEnteTuristico)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR CONTRIBUCIÓN ENTE TURISTICO')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteContribEnteTuristico', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarContribEnteTuristico').attr('data-id', id);
  $('#modalEliminarContribEnteTuristico').modal('show');
});

$('#btn-eliminarContribEnteTuristico').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarContribEnteTuristico/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarContribEnteTuristico').modal('hide');
      cargarContribEnteTuristico({
        page:     $('#herramientasPaginacionContribEnteTuristico').getCurrentPage(),
        perPage:  $('#herramientasPaginacionContribEnteTuristico').getPageSize(),
        casino:   $('#FCasinoContribEnteTuristico').val(),
        desde: $('#fecha_ContribEnteTuristicoDesde').val(),
        hasta: $('#fecha_ContribEnteTuristicoHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$(document).on('click', '.btn-verRegContribEnteTuristico', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarContribEnteTuristico/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_ContribEnteTuristico').val(fecha);
    $('#ver_casino_ContribEnteTuristico').val(data.casino);

    $('#ver_fecha_venc_ContribEnteTuristico').val(data.fecha_venc);
    $('#ver_fecha_pres_ContribEnteTuristicoPres').val(data.fecha_pres);


    $('#ver_base_imponible_ContribEnteTuristico').val('$ '+formatoAR(data.base_imponible));
    $('#ver_alicuota_ContribEnteTuristico').val(formatoAR(data.alicuota) + ' %');
    $('#ver_impuesto_determinado_ContribEnteTuristico').val('$ '+formatoAR(data.impuesto_determinado));
    $('#ver_monto_pagado_ContribEnteTuristico').val('$ '+formatoAR(data.monto_pagado));
    $('#ver_obs_ContribEnteTuristico').val(data.obs);



    $('#modalVerContribEnteTuristico').modal('show');
  });
});

$('#btn-descargarContribEnteTuristicoExcel').on('click',function(e){

  $('#collapseDescargarContribEnteTuristico .has-error').removeClass('has-error');
  $('#collapseDescargarContribEnteTuristico .js-error').remove();

  const desde = $('#fecha_ContribEnteTuristicoDescDesde').val();
  const hasta = $('#fecha_ContribEnteTuristicoDescHasta').val();
  let   valid  = true;


  if(desde>hasta && hasta){
    $('#fecha_ContribEnteTuristicoDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;
  window.location.href = `/documentosContables/descargarContribEnteTuristicoXlsx?casino=3&desde=${desde}&hasta=${hasta}`;


});

$('#btn-descargarContribEnteTuristicoCsv').on('click', function () {
  const desde = $('#fecha_ContribEnteTuristicoDescDesde').val();
  const hasta = $('#fecha_ContribEnteTuristicoDescHasta').val();
  let valid = true;
  $('#collapseDescargarContribEnteTuristico .has-error').removeClass('has-error');
  $('#collapseDescargarContribEnteTuristico .js-error').remove();


  if(desde>hasta && hasta){
    $('#fecha_ContribEnteTuristicoDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarContribEnteTuristicoCsv?casino=3`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


//RR HH

instalarAutoSumaAR({
  sources: ['#personal_inicio_RRHH','#altas_RRHH','-#bajas_RRHH'],
  target:  '#personal_final_RRHH',
  decimales: 0
});

instalarAutoSumaAR({
  sources: ['#personal_final_RRHH','-#personal_nomina_RRHH'],
  target:  '#diferencia_RRHH',
  decimales: 0
});

instalarAutoSumaAR({
  sources: ['#tercerizados_RRHH','#personal_nomina_RRHH'],
  target:  '#total_personal_RRHH',
  decimales: 0
});

instalarAutoPorcentajeAR({
    base:   '#total_ludicos_RRHH',
    parte:  '#no_ludicos_RRHH',
    target: '#porcentaje_no_ludicos_RRHH',
    decimales: 2,
    blankIfZeroBase: true
  });

  instalarAutoPorcentajeAR({
      base:   '#total_ludicos_RRHH',
      parte:  '#ludicos_RRHH',
      target: '#porcentaje_ludicos_RRHH',
      decimales: 2,
      blankIfZeroBase: true
    });
    instalarAutoSumaAR({
      sources: ['#porcentaje_ludicos_RRHH','#porcentaje_no_ludicos_RRHH'],
      target:  '#total_porcentaje_ludicos_RRHH',
      decimales: 2
    });

    instalarAutoSumaAR({
      sources: ['#personal_nomina_RRHH','-#total_ludicos_RRHH'],
      target:  '#dif_nomina_RRHH',
      decimales: 0
    });
    instalarAutoSumaAR({
      sources: ['#ludicos_vivivendo_RRHH','#no_ludicos_viviendo_RRHH'],
      target:  '#total_ludicos_viviendo_RRHH',
      decimales: 0
    });
    instalarAutoSumaAR({
      sources: ['#ludicos_RRHH','#no_ludicos_RRHH'],
      target:  '#total_ludicos_RRHH',
      decimales: 0
    });
    instalarAutoPorcentajeAR({
        base:   '#total_ludicos_RRHH',
        parte:  '#ludicos_vivivendo_RRHH',
        target: '#porcentaje_ludicos_sf_RRHH',
        decimales: 2,
        blankIfZeroBase: true
      });
      instalarAutoPorcentajeAR({
          base:   '#total_ludicos_RRHH',
          parte:  '#no_ludicos_viviendo_RRHH',
          target: '#porcentaje_no_ludicos_sf_RRHH',
          decimales: 2,
          blankIfZeroBase: true
        });
        instalarAutoPorcentajeAR({
            base:   '#total_ludicos_RRHH',
            parte:  '#total_ludicos_viviendo_RRHH',
            target: '#porcentaje_total_sf_RRHH',
            decimales: 2,
            blankIfZeroBase: true
          });


function cargarArchivosRRHHLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('RRHHId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosRRHH/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/RRHH/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="RRHH"  class="btn btn-sm btn-danger btn-del-archivo-RRHH" title="Quitar">')
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

$(document).on('click', '.btn-archivos-RRHH', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro RRHH');
  cargarArchivosRRHHLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-RRHH', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});

function abrirModalRRHHEditar(id){
  resetFormRRHH();
  $('#RRHH_modo').val('edit');
  $('#id_registroRRHH').val(id);
  $('#modalCargarRRHH .modal-title').text('| EDITAR REGISTRO DE RRHH');
  $('#guardarRegistroRRHH').text('ACTUALIZAR');
  $('#modalCargarRRHH').modal('show');

  $.getJSON('/documentosContables/llenarRRHHEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);
    $('#fechaRRHH input[name="fecha_RRHH"]').val(ym).trigger('input').trigger('change');
    $('#casinoRRHH').val(d.casino).trigger('change');

    $('input[name="personal_inicio_RRHH"]').val(d.personal_inicio ?? '');
    $('input[name="personal_final_RRHH"]').val(d.personal_final ?? '');
    $('input[name="altas_RRHH"]').val(d.altas_mes ?? '');
    $('input[name="bajas_RRHH"]').val(d.bajas ?? '');
    $('input[name="personal_nomina_RRHH"]').val(d.personal_nomina ?? '');
    $('input[name="diferencia_RRHH"]').val(d.diferencia ?? '');
    $('input[name="tercerizados_RRHH"]').val(d.tercerizados ?? '');
    $('input[name="total_personal_RRHH"]').val(d.total_personal ?? '');
    $('input[name="ofertado_adjudicado_RRHH"]').val(d.ofertado_adjudicado ?? '');
    $('input[name="ludicos_RRHH"]').val(d.ludico ?? '');
    $('input[name="no_ludicos_RRHH"]').val(d.no_ludico ?? '');
    $('input[name="total_ludicos_RRHH"]').val(d.total_tipo ?? '');
    $('input[name="porcentaje_ludicos_RRHH"]').val(formatoAR(d.porcentaje_ludico ?? ''));
    $('input[name="porcentaje_no_ludicos_RRHH"]').val(formatoAR(d.porcentaje_no_ludico ?? ''));
    $('input[name="total_porcentaje_ludicos_RRHH"]').val(formatoAR(d.porcentaje_total ?? ''));
    $('input[name="porcentaje_ludicos_sf_RRHH"]').val(formatoAR(d.porcentaje_ludico_viviendo ?? ''));
    $('input[name="porcentaje_no_ludicos_sf_RRHH"]').val(formatoAR(d.porcentaje_no_ludico_viviendo ?? ''));
    $('input[name="porcentaje_total_sf_RRHH"]').val(formatoAR(d.porcentaje_total_viviendo ?? ''));
    $('input[name="dif_nomina_RRHH"]').val(d.diferencia_nomina_ddjj ?? '');
    $('input[name="ludicos_vivivendo_RRHH"]').val(d.ludico_viviendo ?? '');
    $('input[name="no_ludicos_viviendo_RRHH"]').val(d.no_ludico_viviendo ?? '');
    $('input[name="total_ludicos_viviendo_RRHH"]').val(d.total_ludico_viviendo ?? '');

})

  .fail(function(xhr){
    console.error('[RRHH] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro RRHH.');
  });
}



$(document).on('hidden.bs.modal', '#modalCargarRRHH', function(){
  var $m = $('#modalCargarRRHH');
  $m.find('.has-error').removeClass('has-error');
  $m.find('input').removeClass('is-invalid text-danger').removeAttr('title data-original-title');
  $m.data('warn-shown', false);
});




function resetFormRRHH(){
  var $f = $('#formNuevoRegistroRRHH');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameRRHH').val('No se ha seleccionado ningún archivo');
  $('#fileListRRHH').empty();
  $('#uploadRRHH').val('');
  $('#uploadsRRHHContainer').empty();
    $('#uploadsRRHHTable tbody').empty();
    $('#uploadsRRHHWrap').hide();
    $('#fileNameRRHH').val('No se ha seleccionado ningún archivo');
  }

function abrirModalRRHHCrear(){
  $('#RRHH_modo').val('create');
  $('#id_registroRRHH').val('');
  $('#modalCargarRRHH .modal-title').text('| NUEVO REGISTRO DE RRHH');
  $('#guardarRegistroRRHH').text('GENERAR');
  $('#modalCargarRRHH').modal('show');
}


$(document).on('click','#RRHH_nuevo',function(){
  abrirModalRRHHCrear();
});


$(document).on('click','.btn-edit-RRHH',function(){
  var id = $(this).data('id');
  abrirModalRRHHEditar(id);
});
$(document).off('click.rrhh','#guardarRegistroRRHH').on('click.rrhh','#guardarRegistroRRHH', function(){
  var $form = $('#formNuevoRegistroRRHH');
  var id   = $('#id_registroRRHH').val() || '';
  var modo = $('#RRHH_modo').val() || 'create';

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  let valid=true;

  valid = validarCampo("select[name='casinoRRHH']",'.col-md-3','El casino es requerido.', valid);
  valid = validarCampo("input[name='fecha_RRHH']",'.col-md-4','La fecha es requerida.', valid);

  valid = validarCampoNum("input[name='personal_inicio_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='personal_final_RRHH']",'.col-md-4','La cantidad es requerida.', valid);

  valid = validarCampoNum("input[name='altas_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='bajas_RRHH']",'.col-md-4','La cantidad es requerida.', valid);

  valid = validarCampoNum("input[name='personal_nomina_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='diferencia_RRHH']",'.col-md-4','La cantidad es requerida.', valid);

  valid = validarCampoNum("input[name='tercerizados_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='total_personal_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='ofertado_adjudicado_RRHH']",'.col-md-4','La cantidad es requerida.', valid);

  valid = validarCampoNum("input[name='ludicos_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='no_ludicos_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='total_ludicos_RRHH']",'.col-md-4','La cantidad es requerida.', valid);

  valid = validarCampoNum("input[name='porcentaje_ludicos_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='porcentaje_no_ludicos_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='total_porcentaje_ludicos_RRHH']",'.col-md-4','La cantidad es requerida.', valid);

  valid = validarCampoNum("input[name='ludicos_vivivendo_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='no_ludicos_viviendo_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='total_ludicos_viviendo_RRHH']",'.col-md-4','La cantidad es requerida.', valid);

  valid = validarCampoNum("input[name='porcentaje_ludicos_sf_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='porcentaje_no_ludicos_sf_RRHH']",'.col-md-4','La cantidad es requerida.', valid);
  valid = validarCampoNum("input[name='porcentaje_total_sf_RRHH']",'.col-md-4','La cantidad es requerida.', valid);


  valid = validarCampoNum("input[name='dif_nomina_RRHH']",'.col-md-4','La cantidad es requerida.', valid);


  if(!valid){
    return;
  };
  var total      = ($('#total_personal_RRHH').val());
  var adjudicado = ($('#ofertado_adjudicado_RRHH').val());
  var pctSF      = ($('#porcentaje_total_sf_RRHH').val());
  var umbralSF   = 80;

  var warnPersonal = (total < adjudicado);
  var warnSF       = (pctSF < umbralSF);

  var yaAdvirtio = $form.data('warn-shown') === true;

  if ((warnPersonal || warnSF) && !yaAdvirtio) {
    $('#personal-adjudicados-RRHH').toggle(warnPersonal)
      .text('El total de personal ('+ total +') es menor al adjudicado ('+ adjudicado +').');
    $('#viviendo-adjudicados-RRHH').toggle(warnSF)
      .text('El % de personal viviendo en Santa Fe ('+ pctSF +'%) es menor al umbral ('+ umbralSF +'%).');

    $form.data('warn-shown', true);
    $('#modalAdjudicadosRRHH').modal({
      backdrop: 'static',
      keyboard: false,
      show: true
    });

    return;
  }

  var formData = new FormData($form[0]);
  var url = ($('#RRHH_modo').val()==='edit')
    ? ('/documentosContables/actualizarRRHH/'+($('#id_registroRRHH').val()||''))
    : '/documentosContables/guardarRRHH';

  $.ajax({
    url: url,
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(){
      cargarRRHH({
        page:     1,
        perPage:  $('#herramientasPaginacionRRHH').getPageSize(),
        casino:   $('#FCasinoRRHH').val(),
        desde: $('#fecha_RRHHDesde').val(),
        hasta: $('#fecha_RRHHHasta').val()
      });
      setTimeout(() => $('#modalCargarRRHH').modal('hide'), 1000);
      resetFormRRHH();
      $form.removeData('warn-shown');
    },
    error: function(xhr){
      $('#salir').next('.help-block.js-error').remove();
      $('#salir').after('<span class="help-block js-error text-danger" style="color:red;">Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
    }
  });
});

$(document).on('shown.bs.modal', '#modalCargarRRHH', function(){
  $('#formNuevoRegistroRRHH').removeData('warn-shown');
});

$('#btn-buscarRRHH').on('click', function(e){
  e.preventDefault();
  cargarRRHH({
    page:    1,
    perPage: $('#herramientasPaginacionRRHH').getPageSize(),
    casino:  $('#FCasinoRRHH').val(),
    desde: $('#fecha_RRHHDesde').val(),
    hasta: $('#fecha_RRHHHasta').val()
  });
});

function clickIndiceRRHH(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarRRHH({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoRRHH').val(),
    desde: $('#fecha_RRHHDesde').val(),
    hasta: $('#fecha_RRHHHasta').val()
  });
}

function cargarRRHH({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasRRHH',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaRRHH').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaRRHH').append(generarFilaRRHH(item));
      });

      $('#herramientasPaginacionRRHH').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceRRHH
      );
      $('#herramientasPaginacionRRHH').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceRRHH
      );

    },
    error(err) {
      console.error('Error cargando RRHH:', err);
    }
  });
}

function generarFilaRRHH(RRHH,controlador) {
  const fila = $('<tr>').attr('id', RRHH.id_registroRRHH);
  const fecha = convertirMesAno(RRHH.fecha_RRHH) || '-'
  const total = formatoAR(RRHH.total || '-');
  const casino= RRHH.casino || '-';
  const porcentaje_viviendo = formatoAR(RRHH.porcentaje || '-');

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-2').text(total))
    .append($('<td>').addClass('col-xs-3').text(porcentaje_viviendo + ' %'));


  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegRRHH')
    .attr('id',RRHH.id_registroRRHH)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO RRHH')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);


if (RRHH.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-RRHH')
    .attr('type','button')
    .attr('data-id', RRHH.id_registroRRHH)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-RRHH')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', RRHH.id_registroRRHH)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);




  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteRRHH')
  .attr('id',RRHH.id_registroRRHH)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO RRHH')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteRRHH', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarRRHH').attr('data-id', id);
  $('#modalEliminarRRHH').modal('show');
});

$('#btn-eliminarRRHH').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarRRHH/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarRRHH').modal('hide');
      cargarRRHH({
        page:     $('#herramientasPaginacionRRHH').getCurrentPage(),
        perPage:  $('#herramientasPaginacionRRHH').getPageSize(),
        casino:   $('#FCasinoRRHH').val(),
        desde: $('#fecha_RRHHDesde').val(),
        hasta: $('#fecha_RRHHHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

function marcarErrorVer($inp, on){
  var $wrap = $inp.closest('.col-md-4, .col-md-3');
  if(on){
    $wrap.addClass('has-error');
    $inp.addClass('text-danger');
  }else{
    $wrap.removeClass('has-error');
    $inp.removeClass('text-danger');
  }
}

$(document).on('hidden.bs.modal', '#modalVerRRHH', function(){
  $('#modalVerRRHH .col-md-4, #modalVerRRHH .col-md-3').removeClass('has-error');
  $('#modalVerRRHH input').removeClass('text-danger is-invalid').removeAttr('title data-original-title');
});

$(document).on('click', '.btn-verRegRRHH', function(){
  const id = $(this).attr('id');

  $.get('/documentosContables/llenarRRHH/'+id, function(data){
    if (!data) { alert('No se encontraron datos'); return; }

    var fecha = convertirMesAno(data.fecha);
    $('#ver_fecha_RRHH').val(fecha);
    $('#ver_casino_RRHH').val(data.casino);

    $('#ver_personal_inicio_RRHH').val(data.personal_inicio);
    $('#ver_altas_RRHH').val(data.altas_mes);
    $('#ver_bajas_RRHH').val(data.bajas);
    $('#ver_personal_final_RRHH').val(data.personal_final);
    $('#ver_diferencia_RRHH').val(data.diferencia);
    $('#ver_personal_nomina_RRHH').val(data.personal_nomina);

    $('#ver_tercerizados_RRHH').val(data.tercerizados);
    $('#ver_total_personal_RRHH').val(data.total_personal);
    $('#ver_ofertado_adjudicado_RRHH').val(data.ofertado_adjudicado);

    $('#ver_ludicos_RRHH').val(data.ludico);
    $('#ver_no_ludicos_RRHH').val(data.no_ludico);
    $('#ver_total_ludicos_RRHH').val(data.total_tipo);

    $('#ver_porcentaje_ludicos_RRHH').val((data.porcentaje_ludico||0) + ' %');
    $('#ver_porcentaje_no_ludicos_RRHH').val((data.porcentaje_no_ludico||0)+ ' %');
    $('#ver_total_porcentaje_ludicos_RRHH').val((data.porcentaje_total||0) + ' %');

    $('#ver_porcentaje_ludicos_sf_RRHH').val((data.porcentaje_ludico_viviendo||0)+ ' %');
    $('#ver_porcentaje_no_ludicos_sf_RRHH').val((data.porcentaje_no_ludico_viviendo||0)+ ' %');
    $('#ver_porcentaje_total_sf_RRHH').val((data.porcentaje_total_viviendo||0)+ ' %');

    $('#ver_ludicos_viviendo_RRHH').val(data.ludico_viviendo);
    $('#ver_no_ludicos_viviendo_RRHH').val(data.no_ludico_viviendo);
    $('#ver_total_viviendo_RRHH').val(data.total_viviendo);

    $('#ver_dif_nomina_RRHH').val(data.diferencia_nomina);

    var total   = Number(data.total_personal);
    var adj     = Number(data.ofertado_adjudicado);
    var pctSF   = Number(data.porcentaje_total_viviendo);
    var umbral  = 80;

    $('#modalVerRRHH .col-md-4, #modalVerRRHH .col-md-3').removeClass('has-error');
    $('#modalVerRRHH input').removeClass('text-danger');

    var badPersonal = isFinite(total) && isFinite(adj) && total < adj;
    marcarErrorVer($('#ver_total_personal_RRHH'), badPersonal);
    marcarErrorVer($('#ver_ofertado_adjudicado_RRHH'), badPersonal);

    var badSF = isFinite(pctSF) && pctSF < umbral;
    marcarErrorVer($('#ver_porcentaje_total_sf_RRHH'), badSF);

    $('#modalVerRRHH').modal('show');
  });
});

$('#btn-descargarRRHHExcel').on('click',function(e){

  $('#collapseDescargarRRHH .has-error').removeClass('has-error');
  $('#collapseDescargarRRHH .js-error').remove();

  const casino = $('#FCasinoRRHH').val() ? $('#FCasinoRRHH').val() : 4;
  const desde = $('#fecha_RRHHDesde').val();
  const hasta = $('#fecha_RRHHHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoRRHH').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_RRHHDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarRRHHXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarRRHHXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarRRHHCsv').on('click', function () {
  const casino = $('#FCasinoRRHH').val() ? $('#FCasinoRRHH').val() : 4;
  const desde = $('#fecha_RRHHDesde').val();
  const hasta = $('#fecha_RRHHHasta').val();
  let valid = true;
  $('#collapseDescargarRRHH .has-error').removeClass('has-error');
  $('#collapseDescargarRRHH .js-error').remove();

  if (!casino) {
    $('#DCasinoRRHH').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_RRHHDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarRRHHCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


//GANANCIAS



$(function(){
  instalarNumeroFlexibleAR('#computa_Ganancias, #abonado_Ganancias, #anticipo_Ganancias');

  instalarAutoSumaAR({
    sources: ['#anticipo_Ganancias','-#abonado_Ganancias','-#computa_Ganancias'],
    target:  '#diferencia_Ganancias',
    decimales: 2
  });


});



function cargarArchivosGananciasLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('GananciasId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosGanancias/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/Ganancias/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="Ganancias"  class="btn btn-sm btn-danger btn-del-archivo-Ganancias" title="Quitar">')
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

$(document).on('click', '.btn-archivos-Ganancias', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro Ganancias');
  cargarArchivosGananciasLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-Ganancias', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});

function abrirModalGananciasEditar(id){
  resetFormGanancias();
  $('#Ganancias_modo').val('edit');
  $('#id_registroGanancias').val(id);
  $('#modalCargarGanancias .modal-title').text('| EDITAR REGISTRO DE ANTICIPO DE GANANCIAS');
  $('#guardarRegistroGanancias').text('ACTUALIZAR');
  $('#modalCargarGanancias').modal('show');

  $.getJSON('/documentosContables/llenarGananciasEdit/'+id, function(d){
    var y   = String(d.fecha || '').slice(0,4);
    var ymdp = String(d.fecha_pago || '').slice(0,10);

    $('#casinoGanancias').val(d.casino).trigger('change');

    $('input[name="fecha_GananciasPres"]').val(y);
    $('input[name="fecha_pago_Ganancias"]').val(ymdp).trigger('input').trigger('change');

    $('input[name="nro_anticipo_Ganancias"]').val(formatoAR(d.nro_anticipo ?? ''));
    $('input[name="anticipo_Ganancias"]').val(formatoAR(d.anticipo ?? ''));
    $('input[name="diferencia_Ganancias"]').val(formatoAR(d.diferencia));
    $('input[name="abonado_Ganancias"]').val(formatoAR(d.abonado ?? ''));

    var qv = formatoAR(d.computa == null ? '' : String(d.computa));
    $('[name="computa_Ganancias"]').val(qv).trigger('change');

    $('[name="obs_Ganancias"]').val(d.obs || '');
  })
  .fail(function(xhr){
    console.error('[Ganancias] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro de Ganancias.');
  });
}


function resetFormGanancias(){
  var $f = $('#formNuevoRegistroGanancias');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameGanancias').val('No se ha seleccionado ningún archivo');
  $('#fileListGanancias').empty();
  $('#uploadGanancias').val('');
  $('#uploadsGananciasContainer').empty();
    $('#uploadsGananciasTable tbody').empty();
    $('#uploadsGananciasWrap').hide();
    $('#fileNameGanancias').val('No se ha seleccionado ningún archivo');
  }

function abrirModalGananciasCrear(){
  $('#Ganancias_modo').val('create');
  $('#id_registroGanancias').val('');
  $('#modalCargarGanancias .modal-title').text('| NUEVO REGISTRO DE GANANCIAS');
  $('#guardarRegistroGanancias').text('GENERAR');
  $('#modalCargarGanancias').modal('show');
}


$(document).on('click','#Ganancias_nuevo',function(){
  abrirModalGananciasCrear();
});

$(document).on('click','.btn-edit-Ganancias',function(){
  var id = $(this).data('id');
  abrirModalGananciasEditar(id);
});


$(document).on('click','#Ganancias_periodo_nuevo',function(e){

  $('#modalCargarGanancias_periodo').modal('show');

});

$(document).on('click','#guardarRegistroGanancias',function(e){
  var $form = $('#formNuevoRegistroGanancias');
  let valid = true;
  var id   = $('#id_registroGanancias').val() || '';
    var modo = $('#Ganancias_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  valid = validarCampo("select[name='casinoGanancias']",'.col-md-4','El casino es requerido.', valid);
  valid = validarCampo("input[name='fecha_GananciasPres']",'.col-md-4','La fecha es requerida.', valid);
  valid = validarCampoNum("input[name='nro_anticipo_Ganancias']",'.col-md-4','El número es requerido.', valid);

  valid = validarCampoNum("input[name='anticipo_Ganancias']",'.col-md-4','El número es requerido.', valid);
  valid = validarCampoNum("input[name='computa_Ganancias']",'.col-md-4','El número es requerido.', valid);
  valid = validarCampoNum("input[name='abonado_Ganancias']",'.col-md-4','El número es requerido.', valid);
  valid = validarCampoNum("input[name='diferencia_Ganancias']",'.col-md-4','El numero es requerido.', valid);

  valid = validarCampo("input[name='fecha_pago_Ganancias']",'.col-md-6','La fecha es requerida.', valid);

  if(!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsGananciasContainer input[type="file"][name="uploadGanancias[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadGanancias[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadGanancias');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadGanancias[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarGanancias/'+id)
      : '/documentosContables/guardarGanancias';

  $.ajax({
    url: url,
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(res){
      cargarGanancias({
        page:     1,
        perPage:  $('#herramientasPaginacionGanancias').getPageSize(),
        casino:   $('#FCasinoGanancias').val(),
        desde: $('#fecha_GananciasDesde').val(),
        hasta: $('#fecha_GananciasHasta').val()
      });
      setTimeout(() => $('#modalCargarGanancias').modal('hide'), 1000);
      resetFormGanancias();
    },
    error: function(xhr){
      $('#salir').next('.help-block.js-error').remove();
      $('#salir')
        .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
    }
  });
});


function cargarArchivosGanancias_periodoLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('Ganancias_periodoId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosGanancias_periodo/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/Ganancias/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="Ganancias_periodo"  class="btn btn-sm btn-danger btn-del-archivo-Ganancias_periodo" title="Quitar">')
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

$(document).on('click', '.btn-archivos-Ganancias_periodo', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro Ganancias_periodo');
  cargarArchivosGanancias_periodoLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-Ganancias_periodo', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalGanancias_periodoEditar(id){
  resetFormGanancias_periodo();
  $('#Ganancias_periodo_modo').val('edit');
  $('#id_registroGanancias_periodo').val(id);
  $('#modalCargarGanancias_periodo .modal-title').text('| EDITAR REGISTRO DE PERÍODO FISCAL - GANANCIAS');
  $('#guardarRegistroGanancias_periodo').text('ACTUALIZAR');
  $('#modalCargarGanancias_periodo').modal('show');

  $.getJSON('/documentosContables/llenarGanancias_periodoEdit/'+id, function(d){
    $('#casinoGanancias_periodo').val(d.casino).trigger('change');

    var year = String(d.periodo || '').slice(0,4);
    $('#fechaGanancias_periodoPres input[name="fecha_Ganancias_periodoPres"]').val(year);

    var ymd = String(d.fecha_pres || '').slice(0,10);
    $('#fecha_pres_Ganancias_periodo input[name="fecha_pres_Ganancias_periodo"]')
      .val(ymd).trigger('input').trigger('change');

    $('input[name="saldo_Ganancias_periodo"]').val(d.saldo ?? '');
    $('[name="forma_pago_Ganancias_periodo"]').val(d.forma_pago ?? '');
    $('[name="obs_Ganancias_periodo"]').val(d.obs || '');
  })
  .fail(function(xhr){
    console.error('[Ganancias_periodo] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el período de Ganancias.');
  });
}


function resetFormGanancias_periodo(){
  var $f = $('#formNuevoRegistroGanancias_periodo');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameGanancias_periodo').val('No se ha seleccionado ningún archivo');
  $('#fileListGanancias_periodo').empty();
  $('#uploadGanancias_periodo').val('');
  $('#uploadsGanancias_periodoContainer').empty();
    $('#uploadsGanancias_periodoTable tbody').empty();
    $('#uploadsGanancias_periodoWrap').hide();
    $('#fileNameGanancias_periodo').val('No se ha seleccionado ningún archivo');
  }

function abrirModalGanancias_periodoCrear(){
  $('#Ganancias_periodo_modo').val('create');
  $('#id_registroGanancias_periodo').val('');
  $('#modalCargarGanancias_periodo .modal-title').text('| NUEVO REGISTRO DE PERÍODO DE GANANCIAS');
  $('#guardarRegistroGanancias_periodo').text('GENERAR');
  $('#modalCargarGanancias_periodo').modal('show');
}


$(document).on('click','#Ganancias_periodo_nuevo',function(){
  abrirModalGanancias_periodoCrear();
});

$(document).on('click','.btn-edit-Ganancias_periodo',function(){
  var id = $(this).data('id');
  abrirModalGanancias_periodoEditar(id);
});


$(document).on('click','#guardarRegistroGanancias_periodo',function(e){
  var $form = $('#formNuevoRegistroGanancias_periodo');
  let valid = true;
  var id   = $('#id_registroGanancias_periodo').val() || '';
  var modo = $('#Ganancias_periodo_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  valid = validarCampo("select[name='casinoGanancias_periodo']",'.col-md-6','El casino es requerido.', valid);

  valid = validarCampo("input[name='fecha_Ganancias_periodoPres']",'.col-md-6','La fecha es requerida.', valid);


  valid = validarCampoNum("input[name='saldo_Ganancias_periodo']",'.col-md-6','El saldo es requerido.', valid);


  valid = validarCampo("input[name='fecha_pres_Ganancias_periodo']",'.col-md-6','La fecha es requerida.', valid);

  if(!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

      $form.serializeArray().forEach(function(p){
        fd.append(p.name, p.value);
      });

      $('#uploadsGanancias_periodoContainer input[type="file"][name="uploadGanancias_periodo[]"]').each(function () {
        var files = this.files || [];
        for (var i = 0; i < files.length; i++) {
          fd.append('uploadGanancias_periodo[]', files[i]);
        }
      });

      var cur = document.getElementById('uploadGanancias_periodo');
      if (cur && cur.files && cur.files.length) {
        for (var j = 0; j < cur.files.length; j++) {
          fd.append('uploadGanancias_periodo[]', cur.files[j]);
        }
      }

      var url = (modo === 'edit')
        ? ('/documentosContables/actualizarGanancias_periodo/'+id)
        : '/documentosContables/guardarGanancias_periodo';
  $.ajax({
    url: url,
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(res){
      cargarGanancias_periodo({
        page:     1,
        perPage:  $('#herramientasPaginacionGanancias_periodo').getPageSize(),
        casino:   $('#FCasinoGanancias_periodo').val(),
        desde: $('#fecha_Ganancias_periodoDesde').val(),
        hasta: $('#fecha_Ganancias_periodoHasta').val()
      });
      setTimeout(() => $('#modalCargarGanancias_periodo').modal('hide'), 1000);
       resetFormGanancias_periodo();
    },
    error: function(xhr){
      $('#salir').next('.help-block.js-error').remove();
      $('#salir')
        .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
    }
  });
});

function cargarGanancias({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasGanancias',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaGanancias').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaGanancias').append(generarFilaGanancias(item));
      });

      $('#herramientasPaginacionGanancias').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceGanancias
      );
      $('#herramientasPaginacionGanancias').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceGanancias
      );

    },
    error(err) {
      console.error('Error cargando Ganancias:', err);
    }
  });
}
function cargarGanancias_periodo({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasGanancias_periodo',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaGanancias_periodo').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaGanancias_periodo').append(generarFilaGanancias_periodo(item));
      });

      $('#herramientasPaginacionGanancias_periodo').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceGanancias_periodo
      );
      $('#herramientasPaginacionGanancias_periodo').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceGanancias_periodo
      );

    },
    error(err) {
      console.error('Error cargando Ganancias_periodo:', err);
    }
  });
}

function clickIndiceGanancias(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarGanancias({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoGanancias').val(),
    desde: $('#fecha_GananciasDesde').val(),
    hasta: $('#fecha_GananciasHasta').val()
  });
}

function clickIndiceGanancias_periodo(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarGanancias_periodo({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoGanancias').val(),
    desde: $('#fecha_GananciasDesde').val(),
    hasta: $('#fecha_GananciasHasta').val()
  });
}
function generarFilaGanancias(Ganancias,controlador) {
  const fila = $('<tr>').attr('id', Ganancias.id_registroGanancias);

  const pres = Ganancias.periodo || '-';
  const casino= Ganancias.casino || '-';
  const anticipo = Ganancias.anticipo || '-';

  fila
    .append($('<td>').addClass('col-xs-3').text(pres))
    .append($('<td>').addClass('col-xs-3').text(anticipo))

    .append($('<td>').addClass('col-xs-3').text(casino))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-3 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegGanancias')
    .attr('id',Ganancias.id_registroGanancias)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO GANANCIAS')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);
  if (Ganancias.tiene_archivos) {
    const btnFiles = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-archivos-Ganancias')
      .attr('type','button')
      .attr('data-id', Ganancias.id_registroGanancias)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER ARCHIVOS ASOCIADOS')
      .append($('<i>').addClass('fa fa-file'));
    tdAcc.append(btnFiles);
  }
    var btnEdit = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-edit-Ganancias')
      .attr('type','button')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-id', Ganancias.id_registroGanancias)
      .attr('title','EDITAR')
      .append($('<i>').addClass('fa fa-edit'));
    tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteGanancias')
  .attr('id',Ganancias.id_registroGanancias)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO GANANCIAS')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteGanancias', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarGanancias').attr('data-id', id);
  $('#modalEliminarGanancias').modal('show');
});

$('#btn-eliminarGanancias').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarGanancias/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarGanancias').modal('hide');
      cargarGanancias({
        page:     $('#herramientasPaginacionGanancias').getCurrentPage(),
        perPage:  $('#herramientasPaginacionGanancias').getPageSize(),
        casino:   $('#FCasinoGanancias').val(),
        desde: $('#fecha_GananciasDesde').val(),
        hasta: $('#fecha_GananciasHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

function generarFilaGanancias_periodo(Ganancias_periodo,controlador) {
  const fila = $('<tr>').attr('id', Ganancias_periodo.id_registroGanancias_periodo);

  const pres = Ganancias_periodo.periodo || '-';
  const casino= Ganancias_periodo.casino || '-';
  const file= Ganancias_periodo.archivo;

  fila
    .append($('<td>').addClass('col-xs-4').text(pres))

    .append($('<td>').addClass('col-xs-4').text(casino))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-4 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegGanancias_periodo')
    .attr('id',Ganancias_periodo.id_registroGanancias_periodo)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO PERIODO FISCAL')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

  if (Ganancias_periodo.tiene_archivos) {
    const btnFiles = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-archivos-Ganancias_periodo')
      .attr('type','button')
      .attr('data-id', Ganancias_periodo.id_registroGanancias_periodo)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER ARCHIVOS ASOCIADOS')
      .append($('<i>').addClass('fa fa-file'));
    tdAcc.append(btnFiles);
  }
    var btnEdit = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-edit-Ganancias_periodo')
      .attr('type','button')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-id', Ganancias_periodo.id_registroGanancias_periodo)
      .attr('title','EDITAR')
      .append($('<i>').addClass('fa fa-edit'));
    tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteGanancias_periodo')
  .attr('id',Ganancias_periodo.id_registroGanancias_periodo)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO PERIODO FISCAL')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteGanancias_periodo', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarGanancias_periodo').attr('data-id', id);
  $('#modalEliminarGanancias_periodo').modal('show');
});

$('#btn-eliminarGanancias_periodo').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarGanancias_periodo/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarGanancias_periodo').modal('hide');
      cargarGanancias_periodo({
        page:     $('#herramientasPaginacionGanancias_periodo').getCurrentPage(),
        perPage:  $('#herramientasPaginacionGanancias_periodo').getPageSize(),
        casino:   $('#FCasinoGanancias').val(),
        desde: $('#fecha_GananciasDesde').val(),
        hasta: $('#fecha_GananciasHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$(document).on('click', '.btn-verRegGanancias', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarGanancias/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    $('#ver_casino_Ganancias').val(data.casino);
    $('#ver_fecha_GananciasPres').val(data.periodo);
    $('#ver_nro_anticipo_Ganancias').val(data.nro_anticipo);

    $('#ver_anticipo_Ganancias').val(formatoAR(data.anticipo));
    $('#ver_abonado_Ganancias').val(formatoAR(data.abonado));
    $('#ver_computa_Ganancias').val(formatoAR(data.computo));
    $('#ver_diferencia_Ganancias').val(formatoAR(data.diferencia));

    $('#ver_fecha_pago_Ganancias').val(data.fecha_pago);
    $('#ver_obs_Ganancias').val(data.obs);

    $('#modalVerGanancias').modal('show');
  });
});

$(document).on('click', '.btn-verRegGanancias_periodo', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarGanancias_periodo/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    $('#ver_casino_Ganancias_periodo').val(data.casino);
    $('#ver_periodo_Ganancias_periodo').val(data.periodo);
    $('#ver_fecha_pres_Ganancias_periodo').val(data.fecha_presentacion);

    $('#ver_forma_pago_Ganancias_periodo').val(data.forma_pago);
    $('#ver_saldo_Ganancias_periodo').val(data.saldo);

    $('#ver_obs_Ganancias_periodo').val(data.obs);

    $('#modalVerGanancias_periodo').modal('show');
  });
});

$('#btn-buscarGanancias').on('click', function(e){
  e.preventDefault();
  cargarGanancias({
    page:    1,
    perPage: $('#herramientasPaginacionGanancias').getPageSize(),
    casino:  $('#FCasinoGanancias').val(),
    desde: $('#fecha_GananciasDesde').val(),
    hasta: $('#fecha_GananciasHasta').val()
  });
  cargarGanancias_periodo({
    page:    1,
    perPage: $('#herramientasPaginacionGanancias').getPageSize(),
    casino:  $('#FCasinoGanancias').val(),
    desde: $('#fecha_GananciasDesde').val(),
    hasta: $('#fecha_GananciasHasta').val()
  });
});

$('#btn-descargarGananciasCsvAnticipos').on('click', function () {
  const casino = $('#FCasinoGanancias').val() ? $('#FCasinoGanancias').val() : 4;
  const desde = $('#fecha_GananciasDesde').val();
  const hasta = $('#fecha_GananciasHasta').val();
  let valid = true;
  $('#collapseDescargarGanancias .has-error').removeClass('has-error');
  $('#collapseDescargarGanancias .js-error').remove();

  if (!casino) {
    $('#DCasinoGanancias').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_GananciasDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarGananciasCsvAnticipos?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$('#btn-descargarGananciasCsvPeriodos').on('click', function () {
  const casino = $('#FCasinoGanancias').val() ? $('#FCasinoGanancias').val() : 4;
  const desde = $('#fecha_GananciasDesde').val();
  const hasta = $('#fecha_GananciasHasta').val();
  let valid = true;
  $('#collapseDescargarGanancias .has-error').removeClass('has-error');
  $('#collapseDescargarGanancias .js-error').remove();

  if (!casino) {
    $('#DCasinoGanancias').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_GananciasDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarGananciasCsvPeriodos?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$('#btn-descargarGananciasExcel').on('click',function(e){

  $('#collapseDescargarGanancias .has-error').removeClass('has-error');
  $('#collapseDescargarGanancias .js-error').remove();

  const casino = $('#FCasinoGanancias').val() ? $('#FCasinoGanancias').val() : 4;
  const desde = $('#fecha_GananciasDesde').val();
  const hasta = $('#fecha_GananciasHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoGanancias').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_GananciasDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarGananciasXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarGananciasXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});


//Jackpots PAGADOS

instalarNumeroFlexibleAR('#importe_JackpotsPagados');

function cargarArchivosJackpotsPagadosLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('JackpotsPagadosId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosJackpotsPagados/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/JackpotsPagados/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="JackpotsPagados"  class="btn btn-sm btn-danger btn-del-archivo-JackpotsPagados" title="Quitar">')
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

$(document).on('click', '.btn-archivos-JackpotsPagados', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro JackpotsPagados');
  cargarArchivosJackpotsPagadosLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-JackpotsPagados', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalJackpotsPagadosEditar(id){
  resetFormJackpotsPagados();
  $('#JackpotsPagados_modo').val('edit');
  $('#id_registroJackpotsPagados').val(id);
  $('#modalCargarJackpotsPagados .modal-title').text('| EDITAR REGISTRO DE JACKPOTS PAGADOS');
  $('#guardarRegistroJackpotsPagados').text('ACTUALIZAR');
  $('#modalCargarJackpotsPagados').modal('show');

  $.getJSON('/documentosContables/llenarJackpotsPagadosEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);
    $('#fechaJackpotsPagados input[name="fecha_JackpotsPagados"]').val(ym).trigger('input').trigger('change');
    $('#casinoJackpotsPagados').val(d.casino).trigger('change');
    $('input[name="importe_JackpotsPagados"]').val(formatoAR(d.importe ?? ''));
  })
  .fail(function(xhr){
    console.error('[JackpotsPagados] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}


function resetFormJackpotsPagados(){
  var $f = $('#formNuevoRegistroJackpotsPagados');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameJackpotsPagados').val('No se ha seleccionado ningún archivo');
  $('#fileListJackpotsPagados').empty();
  $('#uploadJackpotsPagados').val('');
  $('#uploadsJackpotsPagadosContainer').empty();
    $('#uploadsJackpotsPagadosTable tbody').empty();
    $('#uploadsJackpotsPagadosWrap').hide();
    $('#fileNameJackpotsPagados').val('No se ha seleccionado ningún archivo');
  }

function abrirModalJackpotsPagadosCrear(){
  $('#JackpotsPagados_modo').val('create');
  $('#id_registroJackpotsPagados').val('');
  $('#modalCargarJackpotsPagados .modal-title').text('| NUEVO REGISTRO DE JACKPOTS PAGADOS');
  $('#guardarRegistroJackpotsPagados').text('GENERAR');
  $('#modalCargarJackpotsPagados').modal('show');
}


$(document).on('click','#JackpotsPagados_nuevo',function(){
  abrirModalJackpotsPagadosCrear();
});

$(document).on('click','.btn-edit-JackpotsPagados',function(){
  var id = $(this).data('id');
  abrirModalJackpotsPagadosEditar(id);
});

$(document).on('click','#guardarRegistroJackpotsPagados',function(e){
  var $form = $('#formNuevoRegistroJackpotsPagados');
  let valid=true;
  var id   = $('#id_registroJackpotsPagados').val() || '';
    var modo = $('#JackpotsPagados_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoJackpotsPagados']",'.col-md-3','El casino es requerido.', valid);

valid = validarCampo("input[name='fecha_JackpotsPagados']",'.col-md-4','La fecha es requerida.', valid);

valid = validarCampoNum("input[name='importe_JackpotsPagados']",'.col-md-5','El importe es requerido.', valid);

if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsJackpotsPagadosContainer input[type="file"][name="uploadJackpotsPagados[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadJackpotsPagados[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadJackpotsPagados');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadJackpotsPagados[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarJackpotsPagados/'+id)
      : '/documentosContables/guardarJackpotsPagados';
$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarJackpotsPagados({
      page:     1,
      perPage:  $('#herramientasPaginacionJackpotsPagados').getPageSize(),
      casino:   $('#FCasinoJackpotsPagados').val(),
      desde: $('#fecha_JackpotsPagadosDesde').val(),
      hasta: $('#fecha_JackpotsPagadosHasta').val()
    });
    setTimeout(() => $('#modalCargarJackpotsPagados').modal('hide'), 1000);
    resetFormJackpotsPagados();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarJackpotsPagados').on('click', function(e){
  e.preventDefault();
  cargarJackpotsPagados({
    page:    1,
    perPage: $('#herramientasPaginacionJackpotsPagados').getPageSize(),
    casino:  $('#FCasinoJackpotsPagados').val(),
    desde: $('#fecha_JackpotsPagadosDesde').val(),
    hasta: $('#fecha_JackpotsPagadosHasta').val()
  });
});

function clickIndiceJackpotsPagados(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarJackpotsPagados({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoJackpotsPagados').val(),
    desde: $('#fecha_JackpotsPagadosDesde').val(),
    hasta: $('#fecha_JackpotsPagadosHasta').val()
  });
}

function cargarJackpotsPagados({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasJackpotsPagados',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaJackpotsPagados').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaJackpotsPagados').append(generarFilaJackpotsPagados(item));
      });

      $('#herramientasPaginacionJackpotsPagados').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceJackpotsPagados
      );
      $('#herramientasPaginacionJackpotsPagados').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceJackpotsPagados
      );

    },
    error(err) {
      console.error('Error cargando JackpotsPagados:', err);
    }
  });
}

function generarFilaJackpotsPagados(JackpotsPagados,controlador) {
  const fila = $('<tr>').attr('id', JackpotsPagados.id_registroJackpotsPagados);
  const fecha = convertirMesAno(JackpotsPagados.fecha_JackpotsPagados) || '-';
  const casino= JackpotsPagados.casino || '-';
  const importe = '$ '+ formatoAR(JackpotsPagados.importe) || '-';

  fila
    .append($('<td>').addClass('col-xs-3').html(fecha))
    .append($('<td>').addClass('col-xs-3').text(casino))
    .append($('<td>').addClass('col-xs-3').text(importe))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-3 d-flex flex-wrap');

  if (JackpotsPagados.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-JackpotsPagados')
    .attr('type','button')
    .attr('data-id', JackpotsPagados.id_registroJackpotsPagados)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-JackpotsPagados')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', JackpotsPagados.id_registroJackpotsPagados)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteJackpotsPagados')
  .attr('id',JackpotsPagados.id_registroJackpotsPagados)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR JACKPOT PAGADO')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteJackpotsPagados', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarJackpotsPagados').attr('data-id', id);
  $('#modalEliminarJackpotsPagados').modal('show');
});

$('#btn-eliminarJackpotsPagados').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarJackpotsPagados/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarJackpotsPagados').modal('hide');
      cargarJackpotsPagados({
        page:     $('#herramientasPaginacionJackpotsPagados').getCurrentPage(),
        perPage:  $('#herramientasPaginacionJackpotsPagados').getPageSize(),
        casino:   $('#FCasinoJackpotsPagados').val(),
        desde: $('#fecha_JackpotsPagadosDesde').val(),
        hasta: $('#fecha_JackpotsPagadosHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

//no se usa
$(document).on('click', '.btn-verRegJackpotsPagados', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarJackpotsPagados/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_JackpotsPagados').val(fecha);
    $('#ver_casino_JackpotsPagados').val(data.casino);

    $('#ver_fecha_pago_JackpotsPagados').val(data.fecha_pago);
    $('#ver_fecha_JackpotsPagadosPres').val(data.fecha_pres);


    $('#ver_cant_empleados_JackpotsPagados').val(data.cant_empleados);
    $('#ver_monto_pagado_JackpotsPagados').val(data.monto_pagado);
    $('#ver_obs_JackpotsPagados').val(data.obs_JackpotsPagados);



    $('#modalVerJackpotsPagados').modal('show');
  });
});

$('#btn-descargarJackpotsPagadosExcel').on('click',function(e){

  $('#collapseDescargarJackpotsPagados .has-error').removeClass('has-error');
  $('#collapseDescargarJackpotsPagados .js-error').remove();

  const casino = $('#FCasinoJackpotsPagados').val() ? $('#FCasinoJackpotsPagados').val() : 4;
  const desde = $('#fecha_JackpotsPagadosDesde').val();
  const hasta = $('#fecha_JackpotsPagadosHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoJackpotsPagados').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_JackpotsPagadosDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarJackpotsPagadosXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarJackpotsPagadosXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarJackpotsPagadosCsv').on('click', function () {
  const casino = $('#FCasinoJackpotsPagados').val() ? $('#FCasinoJackpotsPagados').val() : 4;
  const desde = $('#fecha_JackpotsPagadosDesde').val();
  const hasta = $('#fecha_JackpotsPagadosHasta').val();
  let valid = true;
  $('#collapseDescargarJackpotsPagados .has-error').removeClass('has-error');
  $('#collapseDescargarJackpotsPagados .js-error').remove();

  if (!casino) {
    $('#DCasinoJackpotsPagados').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_JackpotsPagadosDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarJackpotsPagadosCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


//PREMIOS PAGADOS

instalarNumeroFlexibleAR('#importe_PremiosPagados');

function cargarArchivosPremiosPagadosLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('PremiosPagadosId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosPremiosPagados/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/PremiosPagados/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="PremiosPagados"  class="btn btn-sm btn-danger btn-del-archivo-PremiosPagados" title="Quitar">')
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

$(document).on('click', '.btn-archivos-PremiosPagados', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro PremiosPagados');
  cargarArchivosPremiosPagadosLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-PremiosPagados', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});

function abrirModalPremiosPagadosEditar(id){
  resetFormPremiosPagados();
  $('#PremiosPagados_modo').val('edit');
  $('#id_registroPremiosPagados').val(id);
  $('#modalCargarPremiosPagados .modal-title').text('| EDITAR REGISTRO DE PREMIOS PAGADOS');
  $('#guardarRegistroPremiosPagados').text('ACTUALIZAR');
  $('#modalCargarPremiosPagados').modal('show');

  $.getJSON('/documentosContables/llenarPremiosPagadosEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);
    $('#fechaPremiosPagados input[name="fecha_PremiosPagados"]').val(ym).trigger('input').trigger('change');
    $('#casinoPremiosPagados').val(d.casino).trigger('change');
    $('input[name="cant_PremiosPagados"]').val(d.cantidad ?? '');
    $('input[name="importe_PremiosPagados"]').val(formatoAR(d.importe ?? ''));
  })
  .fail(function(xhr){
    console.error('[PremiosPagados] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}



function resetFormPremiosPagados(){
  var $f = $('#formNuevoRegistroPremiosPagados');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNamePremiosPagados').val('No se ha seleccionado ningún archivo');
  $('#fileListPremiosPagados').empty();
  $('#uploadPremiosPagados').val('');
  $('#uploadsPremiosPagadosContainer').empty();
    $('#uploadsPremiosPagadosTable tbody').empty();
    $('#uploadsPremiosPagadosWrap').hide();
    $('#fileNamePremiosPagados').val('No se ha seleccionado ningún archivo');
  }

function abrirModalPremiosPagadosCrear(){
  $('#PremiosPagados_modo').val('create');
  $('#id_registroPremiosPagados').val('');
  $('#modalCargarPremiosPagados .modal-title').text('| NUEVO REGISTRO DE PREMIOS PAGADOS');
  $('#guardarRegistroPremiosPagados').text('GENERAR');
  $('#modalCargarPremiosPagados').modal('show');
}


$(document).on('click','#PremiosPagados_nuevo',function(){
  abrirModalPremiosPagadosCrear();
});

$(document).on('click','.btn-edit-PremiosPagados',function(){
  var id = $(this).data('id');
  abrirModalPremiosPagadosEditar(id);
});

$(document).on('click','#guardarRegistroPremiosPagados',function(e){
  var $form = $('#formNuevoRegistroPremiosPagados');
  let valid=true;
  var id   = $('#id_registroPremiosPagados').val() || '';
    var modo = $('#PremiosPagados_modo').val() || 'create';

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoPremiosPagados']",'.col-md-3','El casino es requerido.', valid);

valid = validarCampo("input[name='fecha_PremiosPagados']",'.col-md-4','La fecha es requerida.', valid);


valid = validarCampoNum("input[name='cant_PremiosPagados']",'.col-md-5','La cantidad es requerida.', valid);


valid = validarCampoNum("input[name='importe_PremiosPagados']",'.col-md-5','El importe es requerido.', valid);

if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsPremiosPagadosContainer input[type="file"][name="uploadPremiosPagados[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadPremiosPagados[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadPremiosPagados');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadPremiosPagados[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarPremiosPagados/'+id)
      : '/documentosContables/guardarPremiosPagados';
$.ajax({
  url:url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarPremiosPagados({
      page:     1,
      perPage:  $('#herramientasPaginacionPremiosPagados').getPageSize(),
      casino:   $('#FCasinoPremiosPagados').val(),
      desde: $('#fecha_PremiosPagadosDesde').val(),
      hasta: $('#fecha_PremiosPagadosHasta').val()
    });
    setTimeout(() => $('#modalCargarPremiosPagados').modal('hide'), 1000);
        resetFormPremiosPagados();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarPremiosPagados').on('click', function(e){
  e.preventDefault();
  cargarPremiosPagados({
    page:    1,
    perPage: $('#herramientasPaginacionPremiosPagados').getPageSize(),
    casino:  $('#FCasinoPremiosPagados').val(),
    desde: $('#fecha_PremiosPagadosDesde').val(),
    hasta: $('#fecha_PremiosPagadosHasta').val()
  });
});

function clickIndicePremiosPagados(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPremiosPagados({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoPremiosPagados').val(),
    desde: $('#fecha_PremiosPagadosDesde').val(),
    hasta: $('#fecha_PremiosPagadosHasta').val()
  });
}

function cargarPremiosPagados({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasPremiosPagados',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaPremiosPagados').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaPremiosPagados').append(generarFilaPremiosPagados(item));
      });

      $('#herramientasPaginacionPremiosPagados').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePremiosPagados
      );
      $('#herramientasPaginacionPremiosPagados').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePremiosPagados
      );

    },
    error(err) {
      console.error('Error cargando PremiosPagados:', err);
    }
  });
}

function generarFilaPremiosPagados(PremiosPagados,controlador) {
  const fila = $('<tr>').attr('id', PremiosPagados.id_registroPremiosPagados);
  const fecha = convertirMesAno(PremiosPagados.fecha_PremiosPagados) || '-';
  const casino= PremiosPagados.casino || '-';
  const importe = '$ '+ formatoAR(PremiosPagados.importe) || '-';
  const cantidad = PremiosPagados.cantidad || '-';

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-3').text(cantidad))
    .append($('<td>').addClass('col-xs-3').text(importe))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');


if (PremiosPagados.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-PremiosPagados')
    .attr('type','button')
    .attr('data-id', PremiosPagados.id_registroPremiosPagados)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-PremiosPagados')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', PremiosPagados.id_registroPremiosPagados)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deletePremiosPagados')
  .attr('id',PremiosPagados.id_registroPremiosPagados)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR PREMIO PAGADO')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deletePremiosPagados', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarPremiosPagados').attr('data-id', id);
  $('#modalEliminarPremiosPagados').modal('show');
});

$('#btn-eliminarPremiosPagados').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarPremiosPagados/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarPremiosPagados').modal('hide');
      cargarPremiosPagados({
        page:     $('#herramientasPaginacionPremiosPagados').getCurrentPage(),
        perPage:  $('#herramientasPaginacionPremiosPagados').getPageSize(),
        casino:   $('#FCasinoPremiosPagados').val(),
        desde: $('#fecha_PremiosPagadosDesde').val(),
        hasta: $('#fecha_PremiosPagadosHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

//no se usa
$(document).on('click', '.btn-verRegPremiosPagados', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarPremiosPagados/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_PremiosPagados').val(fecha);
    $('#ver_casino_PremiosPagados').val(data.casino);

    $('#ver_fecha_pago_PremiosPagados').val(data.fecha_pago);
    $('#ver_fecha_PremiosPagadosPres').val(data.fecha_pres);


    $('#ver_cant_empleados_PremiosPagados').val(data.cant_empleados);
    $('#ver_monto_pagado_PremiosPagados').val(data.monto_pagado);
    $('#ver_obs_PremiosPagados').val(data.obs_PremiosPagados);



    $('#modalVerPremiosPagados').modal('show');
  });
});

$('#btn-descargarPremiosPagadosExcel').on('click',function(e){

  $('#collapseDescargarPremiosPagados .has-error').removeClass('has-error');
  $('#collapseDescargarPremiosPagados .js-error').remove();

  const casino = $('#FCasinoPremiosPagados').val() ? $('#FCasinoPremiosPagados').val() : 4;
  const desde = $('#fecha_PremiosPagadosDesde').val();
  const hasta = $('#fecha_PremiosPagadosHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoPremiosPagados').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_PremiosPagadosDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarPremiosPagadosXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarPremiosPagadosXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarPremiosPagadosCsv').on('click', function () {
  const casino = $('#FCasinoPremiosPagados').val() ? $('#FCasinoPremiosPagados').val() : 4;
  const desde = $('#fecha_PremiosPagadosDesde').val();
  const hasta = $('#fecha_PremiosPagadosHasta').val();
  let valid = true;
  $('#collapseDescargarPremiosPagados .has-error').removeClass('has-error');
  $('#collapseDescargarPremiosPagados .js-error').remove();

  if (!casino) {
    $('#DCasinoPremiosPagados').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_PremiosPagadosDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPremiosPagadosCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//PREMIOS MTM

$(function(){
  instalarNumeroFlexibleAR('#cancel_PremiosMTM, #jack_PremiosMTM, #progre_PremiosMTM');
  instalarNumeroFlexibleAR('#cancel_usd_PremiosMTM, #jack_usd_PremiosMTM, #progre_usd_PremiosMTM');
  instalarNumeroFlexibleAR('#total_PremiosMTM, #total_usd_PremiosMTM');

  instalarAutoSumaAR({
    sources: ['#cancel_PremiosMTM','#jack_PremiosMTM','#progre_PremiosMTM'],
    target:  '#total_PremiosMTM',
    decimales: 2
  });
  instalarAutoSumaAR({
    sources: ['#cancel_usd_PremiosMTM','#jack_usd_PremiosMTM','#progre_usd_PremiosMTM'],
    target:  '#total_usd_PremiosMTM',
    decimales: 2
  });

});

$(document).on('shown.bs.modal', '#modalCargarPremiosMTM', function(){
  $('#cancel_PremiosMTM, #jack_PremiosMTM, #progre_PremiosMTM, ' +
    '#cancel_usd_PremiosMTM, #jack_usd_PremiosMTM, #progre_usd_PremiosMTM'
  ).trigger('input');
});

function cargarArchivosPremiosMTMLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('PremiosMTMId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosPremiosMTM/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/PremiosMTM/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="PremiosMTM"  class="btn btn-sm btn-danger btn-del-archivo-PremiosMTM" title="Quitar">')
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

$(document).on('click', '.btn-archivos-PremiosMTM', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro PremiosMTM');
  cargarArchivosPremiosMTMLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-PremiosMTM', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalPremiosMTMEditar(id){
  resetFormPremiosMTM();
  $('#PremiosMTM_modo').val('edit');
  $('#id_registroPremiosMTM').val(id);
  $('#modalCargarPremiosMTM .modal-title').text('| EDITAR REGISTRO DE PREMIOS MTM');
  $('#guardarRegistroPremiosMTM').text('ACTUALIZAR');
  $('#modalCargarPremiosMTM').modal('show');

  $.getJSON('/documentosContables/llenarPremiosMTMEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);

    $('#fechaPremiosMTM input[name="fecha_PremiosMTM"]').val(ym).trigger('input').trigger('change');
    $('#casinoPremiosMTM').val(d.casino).trigger('change');

    $('input[name="cancel_PremiosMTM"]').val('$ ' + formatoAR(d.cancel));
    $('input[name="cancel_usd_PremiosMTM"]').val('USD ' + formatoAR(d.cancel_usd));

    $('input[name="progre_PremiosMTM"]').val('$ ' + formatoAR(d.progre));
    $('input[name="progre_usd_PremiosMTM"]').val('USD ' + formatoAR(d.progre_usd));

    $('input[name="jack_PremiosMTM"]').val('$ ' + formatoAR(d.jack));
    $('input[name="jack_usd_PremiosMTM"]').val('USD ' + formatoAR(d.jack_usd));

    $('input[name="total_PremiosMTM"]').val('$ ' + formatoAR(d.total));
    $('input[name="total_usd_PremiosMTM"]').val('USD ' + formatoAR(d.total_usd));
  })
  .fail(function(xhr){
    console.error('[PremiosMTM] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}



function resetFormPremiosMTM(){
  var $f = $('#formNuevoRegistroPremiosMTM');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNamePremiosMTM').val('No se ha seleccionado ningún archivo');
  $('#fileListPremiosMTM').empty();
  $('#uploadPremiosMTM').val('');
  $('#uploadsPremiosMTMContainer').empty();
    $('#uploadsPremiosMTMTable tbody').empty();
    $('#uploadsPremiosMTMWrap').hide();
    $('#fileNamePremiosMTM').val('No se ha seleccionado ningún archivo');
  }

function abrirModalPremiosMTMCrear(){
  $('#PremiosMTM_modo').val('create');
  $('#id_registroPremiosMTM').val('');
  $('#modalCargarPremiosMTM .modal-title').text('| NUEVO REGISTRO DE PREMIOS MTM');
  $('#guardarRegistroPremiosMTM').text('GENERAR');
  $('#modalCargarPremiosMTM').modal('show');
}


$(document).on('click','#PremiosMTM_nuevo',function(){
  abrirModalPremiosMTMCrear();
});

$(document).on('click','.btn-edit-PremiosMTM',function(){
  var id = $(this).data('id');
  abrirModalPremiosMTMEditar(id);
});

$(document).on('click','#guardarRegistroPremiosMTM',function(e){
  var $form = $('#formNuevoRegistroPremiosMTM');
  let valid=true;
  var id   = $('#id_registroPremiosMTM').val() || '';
  var modo = $('#PremiosMTM_modo').val() || 'create';

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoPremiosMTM']",'.col-md-3','El casino es requerido.', valid);

valid = validarCampo("input[name='fecha_PremiosMTM']",'.col-md-4','La fecha es requerida.', valid);


valid = validarCampoNumSiHayValor($("input[name='cancel_PremiosMTM']"),'.col-md-5','La cantidad es requerida.', valid);
valid = validarCampoNumSiHayValor($("input[name='cancel_usd_PremiosMTM']"),'.col-md-5','El importe es requerido.', valid);


valid = validarCampoNumSiHayValor($("input[name='jack_PremiosMTM']"),'.col-md-5','La cantidad es requerida.', valid);
valid = validarCampoNumSiHayValor($("input[name='jack_usd_PremiosMTM']"),'.col-md-5','El importe es requerido.', valid);


valid = validarCampoNumSiHayValor($("input[name='progre_PremiosMTM']"),'.col-md-5','La cantidad es requerida.', valid);
valid = validarCampoNumSiHayValor($("input[name='progre_usd_PremiosMTM']"),'.col-md-5','El importe es requerido.', valid);


valid = validarCampoNumSiHayValor($("input[name='total_PremiosMTM']"),'.col-md-5','La cantidad es requerida.', valid);
valid = validarCampoNumSiHayValor($("input[name='total_usd_PremiosMTM']"),'.col-md-5','El importe es requerido.', valid);

if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsPremiosMTMContainer input[type="file"][name="uploadPremiosMTM[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadPremiosMTM[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadPremiosMTM');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadPremiosMTM[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarPremiosMTM/'+id)
      : '/documentosContables/guardarPremiosMTM';
$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarPremiosMTM({
      page:     1,
      perPage:  $('#herramientasPaginacionPremiosMTM').getPageSize(),
      casino:   $('#FCasinoPremiosMTM').val(),
      desde: $('#fecha_PremiosMTMDesde').val(),
      hasta: $('#fecha_PremiosMTMHasta').val()
    });
    setTimeout(() => $('#modalCargarPremiosMTM').modal('hide'), 1000);
    resetFormPremiosMTM();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarPremiosMTM').on('click', function(e){
  e.preventDefault();
  cargarPremiosMTM({
    page:    1,
    perPage: $('#herramientasPaginacionPremiosMTM').getPageSize(),
    casino:  $('#FCasinoPremiosMTM').val(),
    desde: $('#fecha_PremiosMTMDesde').val(),
    hasta: $('#fecha_PremiosMTMHasta').val()
  });
});

function clickIndicePremiosMTM(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPremiosMTM({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoPremiosMTM').val(),
    desde: $('#fecha_PremiosMTMDesde').val(),
    hasta: $('#fecha_PremiosMTMHasta').val()
  });
}

function cargarPremiosMTM({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasPremiosMTM',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaPremiosMTM').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaPremiosMTM').append(generarFilaPremiosMTM(item));
      });

      $('#herramientasPaginacionPremiosMTM').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePremiosMTM
      );
      $('#herramientasPaginacionPremiosMTM').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePremiosMTM
      );

    },
    error(err) {
      console.error('Error cargando PremiosMTM:', err);
    }
  });
}

function generarFilaPremiosMTM(PremiosMTM,controlador) {
  const fila = $('<tr>').attr('id', PremiosMTM.id_registroPremiosMTM);
  const fecha = convertirMesAno(PremiosMTM.fecha_PremiosMTM) || '-';
  const casino= PremiosMTM.casino || '-';
  const total = '$ '+formatoAR(PremiosMTM.total);
  const total_usd = 'USD '+formatoAR(PremiosMTM.total_usd);

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-3').text(total))
    .append($('<td>').addClass('col-xs-3').text(total_usd))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegPremiosMTM')
    .attr('id',PremiosMTM.id_registroPremiosMTM)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER PREMIO MTM')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

  if (PremiosMTM.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-PremiosMTM')
    .attr('type','button')
    .attr('data-id', PremiosMTM.id_registroPremiosMTM)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-PremiosMTM')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', PremiosMTM.id_registroPremiosMTM)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deletePremiosMTM')
  .attr('id',PremiosMTM.id_registroPremiosMTM)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR PREMIO MTM')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deletePremiosMTM', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarPremiosMTM').attr('data-id', id);
  $('#modalEliminarPremiosMTM').modal('show');
});

$('#btn-eliminarPremiosMTM').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarPremiosMTM/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarPremiosMTM').modal('hide');
      cargarPremiosMTM({
        page:     $('#herramientasPaginacionPremiosMTM').getCurrentPage(),
        perPage:  $('#herramientasPaginacionPremiosMTM').getPageSize(),
        casino:   $('#FCasinoPremiosMTM').val(),
        desde: $('#fecha_PremiosMTMDesde').val(),
        hasta: $('#fecha_PremiosMTMHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$(document).on('click', '.btn-verRegPremiosMTM', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarPremiosMTM/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_PremiosMTM').val(fecha);
    $('#ver_casino_PremiosMTM').val(data.casino);

    $('#ver_cancel_PremiosMTM').val('$ '+ formatoAR(data.cancel));
    $('#ver_cancel_usd_PremiosMTM').val('USD ' + formatoAR(data.cancel_usd));

    $('#ver_progre_PremiosMTM').val('$ '+ formatoAR(data.progresivos));
    $('#ver_progre_usd_PremiosMTM').val('USD ' + formatoAR(data.progresivos_usd));


    $('#ver_jackpots_PremiosMTM').val('$ '+ formatoAR(data.jackpots));
    $('#ver_jackpots_usd_PremiosMTM').val('USD '+ formatoAR(data.jackpots_usd));


    $('#ver_total_PremiosMTM').val('$ '+ formatoAR(data.total));
    $('#ver_total_usd_PremiosMTM').val('USD '+ formatoAR(data.total_usd));


    $('#modalVerPremiosMTM').modal('show');
  });
});

$('#btn-descargarPremiosMTMExcel').on('click',function(e){

  $('#collapseDescargarPremiosMTM .has-error').removeClass('has-error');
  $('#collapseDescargarPremiosMTM .js-error').remove();

  const casino = $('#FCasinoPremiosMTM').val() ?  $('#FCasinoPremiosMTM').val() : 4 ;
  const desde = $('#fecha_PremiosMTMDesde').val();
  const hasta = $('#fecha_PremiosMTMHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoPremiosMTM').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_PremiosMTMDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarPremiosMTMXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarPremiosMTMXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarPremiosMTMCsv').on('click', function () {
  const casino = $('#FCasinoPremiosMTM').val() ?  $('#FCasinoPremiosMTM').val() : 4 ;
  const desde = $('#fecha_PremiosMTMDesde').val();
  const hasta = $('#fecha_PremiosMTMHasta').val();
  let valid = true;
  $('#collapseDescargarPremiosMTM .has-error').removeClass('has-error');
  $('#collapseDescargarPremiosMTM .js-error').remove();

  if (!casino) {
    $('#DCasinoPremiosMTM').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_PremiosMTMDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPremiosMTMCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//AUT. DIRECTORES
$(document).on('click','#AutDirectores_nuevo_director',function(e){

    $('#modalCargarAutDirectores_director').modal('show');

});


function cargarArchivosAutDirectoresLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('AutDirectoresId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosAutDirectores/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/AutDirectores/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="AutDirectores"  class="btn btn-sm btn-danger btn-del-archivo-AutDirectores" title="Quitar">')
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

$(document).on('click', '.btn-archivos-AutDirectores', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro AutDirectores');
  cargarArchivosAutDirectoresLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-AutDirectores', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});

function abrirModalAutDirectoresEditar(id){
  resetFormAutDirectores_autorizacion();
  $('#AutDirectores_modo').val('edit');
  $('#id_registroAutDirectores').val(id);
  $('#modalCargarAutDirectores_autorizacion .modal-title').text('| EDITAR REGISTRO DE AUT. DIRECTORES');
  $('#guardarRegistroAutDirectores').text('ACTUALIZAR');
  $('#modalCargarAutDirectores_autorizacion').modal('show');

  $.getJSON('/documentosContables/llenarAutDirectoresEdit/'+id, function(d){
    var ym = String(d.fecha || '').slice(0,7);
    $('input[name="fecha_AutDirectores_autorizacion"]').val(ym).trigger('input');
    $('#casinoAutDirectores_autorizacion').val(d.casino);

    var html = '';
    (d.directores || []).forEach(function(x){
      html += '<div class="col-md-7 d-flex align-items-center mb-2">';
      html += '  <span class="me-2" style="white-space:nowrap;">'+x.nombre+' C.U.I.T.: '+(x.cuit||'')+'</span>';
      html += '  <input type="hidden" name="directores['+x.id+']" value="0">';
      html += '  <input type="checkbox" name="directores['+x.id+']" value="1" '+(parseInt(x.autoriza)?'checked':'')+' style="margin-left:6px;">';
      html += '</div>';
      html += '<div class="col-md-5 mb-2">';
      html += '  <textarea name="observacion['+x.id+']" class="form-control form-control-sm" rows="1" placeholder="observacion">'+(x.observacion||'')+'</textarea>';
      html += '</div>';
    });
    $('#zona-directores').html(html || '<div class="col-md-12 text-muted">Sin directores asociados.</div>');
  }).fail(function(xhr){
    console.error(xhr.responseText || xhr);
    alert('No se pudo cargar la autorización.');
  });
}





function resetFormAutDirectores_autorizacion(){
  var $f = $('#formNuevoRegistroAutDirectores_autorizacion');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameAutDirectores').val('No se ha seleccionado ningún archivo');
  $('#fileListAutDirectores').empty();
  $('#zona-directores')
    .empty()
    .html('<div class="col-md-12 text-muted">Elegí un casino para ver los directores.</div>');
  $('#uploadAutDirectores').val('');
  $('#uploadsAutDirectoresContainer').empty();
    $('#uploadsAutDirectoresTable tbody').empty();
    $('#uploadsAutDirectoresWrap').hide();
    $('#fileNameAutDirectores').val('No se ha seleccionado ningún archivo');
  }

function abrirModalAutDirectoresCrear(){
  resetFormAutDirectores_autorizacion();
  $('#AutDirectores_modo').val('create');
  $('#id_registroAutDirectores').val('');
  $('#modalCargarAutDirectores .modal-title').text('| NUEVO REGISTRO DE AUT. DIRECTORES');
  $('#guardarRegistroAutDirectores').text('GENERAR');
  $('#modalCargarAutDirectores_autorizacion').modal('show');
}


$(document).on('click','#AutDirectores_nuevo_autorizacion',function(){
  abrirModalAutDirectoresCrear();
});

$(document).on('click','.btn-edit-AutDirectores',function(){
  var id = $(this).data('id');
  abrirModalAutDirectoresEditar(id);
});

$(document).on('click','#AutDirectores_gestionar_directores',function(){
  $('#modalAutDirectores_gestionar_directores').modal('show');
  cargarAutDirectoresGestion();
});

function escapeHtml(s){
  return (s||'').toString().replace(/[&<>"']/g,function(m){
    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]);
  });
}

function renderFilaDirector(d){
  var estado = d.habilitado
  ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
  : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';

  return ''+
  '<tr data-nombre="'+d.nombre+'" data-cuit="'+d.cuit+'" data-estado="'+d.habilitado+'" data-id="'+d.id+'">'+
    '<td class="col-md-4">'+escapeHtml(d.nombre || '')+'</td>'+
    '<td class="col-md-4">'+escapeHtml(d.cuit || '-')+'</td>'+
    '<td class="col-md-1">'+escapeHtml(d.casino || '')+'</td>'+
    '<td class="text-center col-md-2 estado">'+estado+'</td>'+
    '<td class="col-md-1">'+
      '<button type="button" class="btn btn-sm btn-primary btn-editAutDirectores_director" title="MODIFICAR DIRECTOR">'+
        '<i class="fa fa-edit"></i>'+
      '</button> '+
    '</td>'+
  '</tr>';
}

$(document).on('click', '#guardarModifRegistroAutDirectores_director', function () {
  var $btn  = $(this).prop('disabled', true);
  var $form = $('#formModificarRegistroAutDirectores_director');
  var id    = $('#ModifId_AutDirectores_director').val();

  var valid = true;
  valid = validarCampo("input[name='ModifAutDirectores_director_nombre']", '.col-md-4', 'El nombre es requerido.', valid);
  valid = validarCampo("input[name='ModifAutDirectores_director_cuit']", '.col-md-4', 'El CUIT es requerido.', valid);

  if (!valid) { $btn.prop('disabled', false); return 0; }

  $.ajax({
    url: '/documentosContables/actualizarAutDirectores_director/' + id,
    method: 'POST',
    data: $form.serialize(),
    success: function (res) {
      if (res && res.success) {
          cargarAutDirectoresGestion();

        $('#modalModificarAutDirectores_director').modal('hide');
      } else {
        alert('No se pudo guardar la modificación.');
      }
    },
    error: function (xhr) {
      console.error(xhr.responseText || xhr);
    },
    complete: function () {
      $btn.prop('disabled', false);
    }
  });
});

$(document).on('click', '.btn-editAutDirectores_director', function(){
  const $tr  = $(this).closest('tr');
  const id = $(this).closest('tr').data('id');
  const nombre = $(this).closest('tr').data('nombre');
  const cuit = $(this).closest('tr').data('cuit');

  const estado = $(this).closest('tr').data('estado');
  var $sel = $('#ModifTGI_partida_estado');

  $('#ModifId_AutDirectores_director').val(id);
  $('#ModifAutDirectores_director_nombre').val(nombre);
  $('#ModifAutDirectores_director_cuit').val(cuit);
  $('#ModifAutDirectores_director_estado').val(estado);

  $('#modalModificarAutDirectores_director').modal('show');
});

function cargarAutDirectoresGestion(casinoId){
  $('#dir-list-loading').show();
  $('#tabla-directores-AutDirectores').closest('.table-responsive').hide();
  $('#tabla-directores-AutDirectores tbody').empty();

  var url = '/documentosContables/getAutDirectores';

  $.getJSON(url, function(data){
    var rows = '';
    for (var i=0; i<data.length; i++){
      var d = data[i];
      rows += renderFilaDirector(d);
    }
    $('#tabla-directores-AutDirectores tbody').html(rows);
    $('#dir-list-loading').hide();
    $('#tabla-directores-AutDirectores').closest('.table-responsive').show();
  }).fail(function(xhr){
    $('#dir-list-loading').text('Error cargando directores.');
    console.error(xhr.responseText);
  });
}


$(document).on('click', '#AutDirectoresHabilitar', function () {
  var $btn = $(this);
  var $tr  = $btn.closest('tr');
  var id   = $tr.data('id');

  $.get('/documentosContables/AutDirectoresHabilitarDirector/' + id, function (res) {
    var habilitado = Number(res.habilitado) === 1;

    var estadoHtml = habilitado
    ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
    : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';

    $tr.find('.estado').html(estadoHtml);
    $btn
      .attr('title', habilitado ? 'DESHABILITAR' : 'HABILITAR')
      .attr('target', '_blank')
      .attr('data-toggle', 'tooltip')
      .toggleClass('btn-success btn-danger')
      .html(habilitado ? '<i class="fa fa-times"></i>' : '<i class="fa fa-check"></i>');
  });
});

$(document).on('click', '.btn-elimAutDirectores_director', function(){
  const id = $(this).closest('tr').data('id');
  $('#btn-eliminarAutDirectores_director').attr('data-id', id);
  $('#modalEliminarAutDirectores_director').modal('show');
});


$(document).on('click', '#btn-eliminarAutDirectores_director', function () {
  const id  = $(this).attr('data-id');
  const $tr = $('#tabla-directores-AutDirectores tr[data-id="'+id+'"]');

  $.get('/documentosContables/AutDirectoresEliminarDirector/' + id, function (res) {
    if (res.ok) {
      $tr.remove();
      $('#modalEliminarAutDirectores_director').modal('hide')
    } else {
    }
  }).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
  });
});

$(document).on('click','#guardarRegistroAutDirectores_director',function(e){
  var $form = $('#formNuevoRegistroAutDirectores_director');
  let valid=true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoAutDirectores_director']",'.col-md-5','El casino es requerido.', valid);

valid = validarCampo("input[name='nombre_AutDirectores_director']",'.col-md-5','El nombre es requerido.', valid);
valid = validarCampo("input[name='cuit_AutDirectores_director']",'.col-md-5','El C.U.I.T. es requerido.', valid);



if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);

$.ajax({
  url: '/documentosContables/guardarAutDirectores_director',
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarAutDirectores({
      page:     1,
      perPage:  $('#herramientasPaginacionAutDirectores').getPageSize(),
      casino:   $('#FCasinoAutDirectores').val(),
      desde: $('#fecha_AutDirectoresDesde').val(),
      hasta: $('#fecha_AutDirectoresHasta').val()
    });
    setTimeout(() => $('#modalCargarAutDirectores_director').modal('hide'), 1000);
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$(document).on('click','#guardarRegistroAutDirectores_autorizacion',function(e){
  var $form = $('#formNuevoRegistroAutDirectores_autorizacion');
  let valid=true;
  var id   = $('#id_registroAutDirectores').val() || '';
    var modo = $('#AutDirectores_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoAutDirectores_autorizacion']",'.col-md-6','El casino es requerido.', valid);
valid = validarCampo("input[name='fecha_AutDirectores_autorizacion']",'.col-md-6','La fecha es requerida.', valid);


if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsAutDirectoresContainer input[type="file"][name="uploadAutDirectores[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadAutDirectores[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadAutDirectores');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadAutDirectores[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarAutDirectores/'+id)
      : '/documentosContables/guardarAutDirectores_autorizacion';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarAutDirectores({
      page:     1,
      perPage:  $('#herramientasPaginacionAutDirectores').getPageSize(),
      casino:   $('#FCasinoAutDirectores').val(),
      desde: $('#fecha_AutDirectoresDesde').val(),
      hasta: $('#fecha_AutDirectoresHasta').val()
    });
    setTimeout(() => $('#modalCargarAutDirectores_autorizacion').modal('hide'), 1000);
    resetFormAutDirectores_autorizacion();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarAutDirectores').on('click', function(e){
  e.preventDefault();
  cargarAutDirectores({
    page:    1,
    perPage: $('#herramientasPaginacionAutDirectores').getPageSize(),
    casino:  $('#FCasinoAutDirectores').val(),
    desde: $('#fecha_AutDirectoresDesde').val(),
    hasta: $('#fecha_AutDirectoresHasta').val()
  });
});

function clickIndiceAutDirectores(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarAutDirectores({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoAutDirectores').val(),
    desde: $('#fecha_AutDirectoresDesde').val(),
    hasta: $('#fecha_AutDirectoresHasta').val()
  });
}

function cargarAutDirectores({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasAutDirectores',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaAutDirectores').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaAutDirectores').append(generarFilaAutDirectores(item));
      });

      $('#herramientasPaginacionAutDirectores').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceAutDirectores
      );
      $('#herramientasPaginacionAutDirectores').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceAutDirectores
      );

    },
    error(err) {
      console.error('Error cargando AutDirectores:', err);
    }
  });
}

function generarFilaAutDirectores(AutDirectores,controlador) {
  const fila = $('<tr>').attr('id', AutDirectores.id_registroAutDirectores);
  const fecha = convertirMesAno(AutDirectores.fecha_AutDirectores) || '-';
  const casino= AutDirectores.casino || '-';

  fila
    .append($('<td>').addClass('col-xs-4').html(fecha))
    .append($('<td>').addClass('col-xs-4').text(casino))

  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-4 d-flex flex-wrap');


  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegAutDirectores_autorizacion')
    .attr('id',AutDirectores.id_registroAutDirectores)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER AUTORIZACIONES DE DIRECTORES')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

  if (AutDirectores.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-AutDirectores')
    .attr('type','button')
    .attr('data-id', AutDirectores.id_registroAutDirectores)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-AutDirectores')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', AutDirectores.id_registroAutDirectores)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);


  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteAutDirectores')
  .attr('id',AutDirectores.id_registroAutDirectores)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR AUTORIZACIONES DE DIRECTORES')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteAutDirectores', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarAutDirectores_autorizacion').attr('data-id', id);
  $('#modalEliminarAutDirectores_autorizacion').modal('show');
});

$('#btn-eliminarAutDirectores_autorizacion').on('click', function(){
  const id = $(this).attr('data-id');

  $.ajax({
    url: `/documentosContables/eliminarAutDirectores/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarAutDirectores_autorizacion').modal('hide');
      cargarAutDirectores({
        page:     $('#herramientasPaginacionAutDirectores').getCurrentPage(),
        perPage:  $('#herramientasPaginacionAutDirectores').getPageSize(),
        casino:   $('#FCasinoAutDirectores').val(),
        desde: $('#fecha_AutDirectoresDesde').val(),
        hasta: $('#fecha_AutDirectoresHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$(document).on('click', '.btn-verRegAutDirectores_autorizacion', function(){
  const id = $(this).attr('id');

  $('#detalle-AutDirectores-body').html('');

  $.get(`/documentosContables/llenarAutDirectores/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    var autorizaciones = data.autorizaciones;

    fecha = convertirMesAno(data.fecha);

    $('#ver_fecha_AutDirectores').val(fecha);
    $('#ver_casino_AutDirectores').val(data.casino);

    var rows= '';

    autorizaciones.forEach(function(a){
      var dir   = a.director;
      var nombre = dir.nombre || '';
      var cuit   = dir.cuit   || '';
      var ok     = a.autoriza;

      var icono = ok
        ? '<span class="text-success"><i class="fa fa-check"></i></span>'
        : '<span class="text-danger"><i class="fa fa-times"></i></span>';

      rows += '<tr>'
            +   '<td>' + escapeHtml(nombre) + '</td>'
            +   '<td>' + escapeHtml(cuit) + '</td>'
            +   '<td class="text-center">' + icono + '</td>'
            +   '<td>' + escapeHtml(a.observaciones || '') + '</td>'
            + '</tr>';
    });
    if(rows === '') rows = '<tr><td colspan="4" class="text-muted">Sin autorizaciones.</td></tr>';
    $('#detalle-AutDirectores-body').html(rows);

    $('#modalVerAutDirectores_autorizacion').modal('show');
  });
});
$('#btn-descargarAutDirectoresExcel').on('click',function(e){

  $('#collapseDescargarAutDirectores .has-error').removeClass('has-error');
  $('#collapseDescargarAutDirectores .js-error').remove();

  const casino = $('#FCasinoAutDirectores').val() ? $('#FCasinoAutDirectores').val() : 4;
  const desde = $('#fecha_AutDirectoresDesde').val();
  const hasta = $('#fecha_AutDirectoresHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoAutDirectores').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_AutDirectoresDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarAutDirectoresXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarAutDirectoresXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarAutDirectoresCsv').on('click', function () {
  const casino = $('#FCasinoAutDirectores').val() ? $('#FCasinoAutDirectores').val() : 4;
  const desde = $('#fecha_AutDirectoresDesde').val();
  const hasta = $('#fecha_AutDirectoresHasta').val();
  let valid = true;
  $('#collapseDescargarAutDirectores .has-error').removeClass('has-error');
  $('#collapseDescargarAutDirectores .js-error').remove();

  if (!casino) {
    $('#DCasinoAutDirectores').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_AutDirectoresDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarAutDirectoresCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

// SEGUROS

instalarNumeroFlexibleAR('#monto_Seguros , #cta_paga_Seguros');

$(document).on('click','#Seguros_nuevo_tipo',function(e){

    $('#modalCargarSeguros_tipo').modal('show');

});

function cargarArchivosSegurosLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('SegurosId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosSeguros/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/Seguros/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="Seguros"  class="btn btn-sm btn-danger btn-del-archivo-Seguros" title="Quitar">')
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

$(document).on('click', '.btn-archivos-Seguros', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro Seguros');
  cargarArchivosSegurosLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-Seguros', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});

function abrirModalSegurosEditar(id){
  resetFormSeguros();
  $('#Seguros_modo').val('edit');
  $('#id_registroSeguros').val(id);
  $('#modalCargarSeguros .modal-title').text('| EDITAR REGISTRO DE SEGUROS');
  $('#guardarRegistroSeguros').text('ACTUALIZAR');
  $('#modalCargarSeguros').modal('show');

  $.getJSON('/documentosContables/llenarSegurosEdit/'+id, function(d){
    $('#fecha_SegurosDes').val(String(d.desde || '').slice(0,10)).trigger('input').trigger('change');
    $('#fecha_SegurosHas').val(String(d.hasta || '').slice(0,10)).trigger('input').trigger('change');
    $('#casinoSeguros').val(d.casino).trigger('change');
    $('#tipo_Seguros').val(d.tipo_id).trigger('change');
    $('#art_Seguros').val(d.art ?? '');
    $('#comp_Seguros').val(d.compania || '');
    $('#poliza_Seguros').val(d.poliza || '');
    $('#monto_Seguros').val(formatoAR(d.monto ?? ''));
    $('#cta_paga_Seguros').val(formatoAR(d.cta_paga_total ?? ''));
    $('#estado_Seguros').val(d.estado ?? '');
    $('#requerimento_Seguros').val(d.requerimiento_anual ?? '');
    $('[name="obs_Seguros"]').val(d.obs || '');
  })
  .fail(function(xhr){
    console.error('[SEGUROS] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro de Seguros.');
  });
}

function resetFormSeguros(){
  var $f = $('#formNuevoRegistroSeguros');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameSeguros').val('No se ha seleccionado ningún archivo');
  $('#fileListSeguros').empty();
  $('#uploadSeguros').val('');
  $('#uploadsSegurosContainer').empty();
    $('#uploadsSegurosTable tbody').empty();
    $('#uploadsSegurosWrap').hide();
    $('#fileNameSeguros').val('No se ha seleccionado ningún archivo');
  }

function abrirModalSegurosCrear(){
  $('#Seguros_modo').val('create');
  $('#id_registroSeguros').val('');
  $('#modalCargarSeguros .modal-title').text('| NUEVO REGISTRO DE SEGUROS');
  $('#guardarRegistroSeguros').text('GENERAR');
  $('#modalCargarSeguros').modal('show');
}


$(document).on('click','#Seguros_nuevo',function(){
  abrirModalSegurosCrear();
});

$(document).on('click','.btn-edit-Seguros',function(){
  var id = $(this).data('id');
  abrirModalSegurosEditar(id);
});

function cargarTiposSeguro(selectedId){
  var $sel = $('#tipo_Seguros');

  $sel.empty();


  $.getJSON('/documentosContables/getSeguros_tipo', function(data){
    $.each(data, function(_, t){
      var id  = t.id;
      var txt = t.tipo || '';
      $sel.append('<option value="'+ id +'">'+ txt +'</option>');
    });

    if (selectedId) $sel.val(String(selectedId));
  })
  .fail(function(xhr){
    console.error(xhr.responseText || xhr);
    $sel.empty().append('<option value="">Error al cargar</option>');
  })
  .always(function(){
    $sel.prop('disabled', false);
  });
}

$(document).on('click','#Seguros_tipo_gestionar',function(){
  $('#modalSeguros_tipo_gestionar').modal('show');
  cargarSeguros_tipoGestion();
});

function escapeAttr(s){
  return (s||'').toString()
    .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function renderFilaSeguros_tipo(d){
  return ''+
  '<tr data-id="'+d.id_registroSeguros_tipo+'" data-tipo="'+escapeAttr(d.tipo || '')+'">'+
    '<td class="col-md-9">'+escapeHtml(d.tipo || '')+'</td>'+
    '<td class="text-left col-md-3">'+
      '<button type="button" class="btn btn-sm btn-primary btn-editSeguros_tipo" title="MODIFICAR TIPO DE SEGURO">'+
        '<i class="fa fa-edit"></i>'+
      '</button> '+
    '</td>'+
  '</tr>';
}


function cargarSeguros_tipoGestion(casinoId){
  $('#dir-list-loading_seguros').show();
  $('#tabla-Seguros_tipo').closest('.table-responsive').hide();
  $('#tabla-Seguros_tipo tbody').empty();

  var url = '/documentosContables/getSeguros_tipo';

  $.getJSON(url, function(data){
    var rows = '';
    for (var i=0; i<data.length; i++){
      var d = data[i];
      rows += renderFilaSeguros_tipo(d);
    }
    $('#tabla-Seguros_tipo tbody').html(rows);
    $('#dir-list-loading_seguros').hide();
    $('#tabla-Seguros_tipo').closest('.table-responsive').show();
  }).fail(function(xhr){
    $('#dir-list-loading_seguros').text('Error cargando tipos de seguro.');
    console.error(xhr.responseText);
  });
}

$(document).on('click', '.btn-elimSeguros_tipo', function(){
  const id = $(this).closest('tr').data('id');
  $('#btn-eliminarSeguros_tipo').attr('data-id', id);
  $('#modalEliminarSeguros_tipo').modal('show');
});


$(document).on('click', '.btn-editSeguros_tipo', function(){
  const id = $(this).closest('tr').data('id');
  const tipo = $(this).closest('tr').data('tipo');

  $('#guardarModifRegistroSeguros_tipo').attr('data-id', id);
  $('#ModifTipo_Seguros_tipo').val(tipo);
  $('#modalModificarSeguros_tipo').modal('show');
});

$(document).on('click', '.btn-editSeguros_tipo', function(){
  const $tr  = $(this).closest('tr');
  const id   = $tr.data('id');
  const tipo = $tr.find('td:first').text().trim();

  $('#ModifId_Seguros_tipo').val(id);
  $('#ModifTipo_Seguros_tipo').val(tipo);

  $('#modalModificarSeguros_tipo').modal('show');
});

$(document).on('click', '#guardarModifRegistroSeguros_tipo', function(){
  const $btn  = $(this).prop('disabled', true);
  const $form = $('#formModificarRegistroSeguros_tipo');

  valid = true;
  valid = validarCampo("input[name='ModifTipo_Seguros_tipo']",'.col-md-12','El tipo es requerido.', valid);

  if(!valid) return 0;

  $.ajax({
    url: '/documentosContables/modificarSeguros_tipo',
    method: 'POST',
    data: $form.serialize(),
    success: function(res){
      cargarSeguros_tipoGestion();
      cargarTiposSeguro();
      $('#modalModificarSeguros_tipo').modal('hide');
    },
    error: function(xhr){
      console.error(xhr.responseText || xhr);
    },
    complete: function(){
      $btn.prop('disabled', false);
    }
  });
});



$(document).on('click', '#btn-eliminarSeguros_tipo', function () {
  const id  = $(this).attr('data-id');
  const $tr = $('#tabla-Seguros_tipo tr[data-id="'+id+'"]');

  $.get('/documentosContables/SegurosEliminarTipo/' + id, function (res) {
    if (res.ok) {
      $tr.remove();
      $('#modalEliminarSeguros_tipo').modal('hide')
    } else {
    }
  }).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
  });
});

$(document).on('click','#guardarRegistroSeguros_tipo',function(e){
  var $form = $('#formNuevoRegistroSeguros_tipo');
  let valid=true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("input[name='tipo_Seguros_tipo']",'.col-md-12','El tipo es requerido.', valid);

if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);

$.ajax({
  url: '/documentosContables/guardarSeguros_tipo',
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    setTimeout(() => $('#modalCargarSeguros_tipo').modal('hide'), 1000);
    cargarTiposSeguro();

  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$(document).on('click','#guardarRegistroSeguros',function(e){
  var $form = $('#formNuevoRegistroSeguros');
  let valid=true;
  var id   = $('#id_registroSeguros').val() || '';
  var modo = $('#Seguros_modo').val() || 'create';
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoSeguros']",'.col-md-6','El casino es requerido.', valid);
valid = validarCampo("select[name='tipo_Seguros']",'.col-md-6','El tipo es requerido.', valid);


valid = validarCampo("input[name='fecha_SegurosDes']",'.col-md-6','La fecha es requerida.', valid);
valid = validarCampo("input[name='fecha_SegurosHas']",'.col-md-6','La fecha es requerida.', valid);

const $cta_paga = $("input[name='cta_paga_Seguros']");
valid = validarCampoNumSiHayValor($cta_paga, '.col-md-4', 'El monto es requerido.', valid);



valid = validarCampoNum("input[name='poliza_Seguros']",'.col-md-4','La Póliza es requerida', valid);
valid = validarCampoNum("input[name='monto_Seguros']",'.col-md-4','El monto es requerido.', valid);
valid = validarCampo("input[name='comp_Seguros']",'.col-md-4','La compañía es requerida.', valid);


if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsSegurosContainer input[type="file"][name="uploadSeguros[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadSeguros[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadSeguros');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadSeguros[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarSeguros/'+id)
      : '/documentosContables/guardarSeguros';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarSeguros({
      page:     1,
      perPage:  $('#herramientasPaginacionSeguros').getPageSize(),
      casino:   $('#FCasinoSeguros').val(),
      desde: $('#fecha_SegurosDesde').val(),
      hasta: $('#fecha_SegurosHasta').val()
    });
    setTimeout(() => $('#modalCargarSeguros').modal('hide'), 1000);
        resetFormSeguros();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarSeguros').on('click', function(e){
  e.preventDefault();
  cargarSeguros({
    page:    1,
    perPage: $('#herramientasPaginacionSeguros').getPageSize(),
    casino:  $('#FCasinoSeguros').val(),
    desde: $('#fecha_SegurosDesde').val(),
    hasta: $('#fecha_SegurosHasta').val()
  });
});

function clickIndiceSeguros(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarSeguros({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoSeguros').val(),
    desde: $('#fecha_SegurosDesde').val(),
    hasta: $('#fecha_SegurosHasta').val()
  });
}

function cargarSeguros({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasSeguros',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaSeguros').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaSeguros').append(generarFilaSeguros(item));
      });

      $('#herramientasPaginacionSeguros').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceSeguros
      );
      $('#herramientasPaginacionSeguros').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceSeguros
      );

    },
    error(err) {
      console.error('Error cargando Seguros:', err);
    }
  });
}

function generarFilaSeguros(Seguros,controlador) {
  const fila = $('<tr>').attr('id', Seguros.id_registroSeguros);
  const periodoIn = Seguros.periodoIn || '-';
  const periodoFin = Seguros.periodoFin || '-';
  const tipo = Seguros.tipo || '-';
  const casino= Seguros.casino || '-';
  const estado = Seguros.estado==1 ? 'VIGENTE' : 'VENCIDO' ;

  fila
    .append($('<td>').addClass('col-xs-2').text(periodoIn))
    .append($('<td>').addClass('col-xs-2').text(periodoFin))
    .append($('<td>').addClass('col-xs-4').text(tipo))
    .append($('<td>').addClass('col-xs-1').text(casino))
    .append($('<td>').addClass('col-xs-1').text(estado))


  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegSeguros')
    .attr('id',Seguros.id_registroSeguros)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO DE SEGURO')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

  if (Seguros.estado == 1 ){
  var btnCheck = $('<a>')
          .addClass('btn btn-success btn-sm btn-estadoRegSeguros')
          .attr('id', Seguros.id_registroSeguros)
          .attr('data-toggle', 'tooltip')
          .attr('data-placement','bottom')
          .attr('title', 'MARCAR COMO VENCIDO')
          .append($('<i>').addClass('fa fa-fw fa-check'));
    tdAcc.append(btnCheck);
      }



if (Seguros.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-Seguros')
    .attr('type','button')
    .attr('data-id', Seguros.id_registroSeguros)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-Seguros')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', Seguros.id_registroSeguros)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);



  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteSeguros')
  .attr('id',Seguros.id_registroSeguros)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO DE SEGURO')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteSeguros', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarSeguros').attr('data-id', id);
  $('#modalEliminarSeguros').modal('show');
});

$('#btn-eliminarSeguros').on('click', function(){
  const id = $(this).attr('data-id');

  $.ajax({
    url: `/documentosContables/eliminarSeguros/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarSeguros').modal('hide');
      cargarSeguros({
        page:     $('#herramientasPaginacionSeguros').getCurrentPage(),
        perPage:  $('#herramientasPaginacionSeguros').getPageSize(),
        casino:   $('#FCasinoSeguros').val(),
        desde: $('#fecha_SegurosDesde').val(),
        hasta: $('#fecha_SegurosHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$(document).on('click', '.btn-estadoRegSeguros', function(e){
    e.preventDefault();
  const id = $(this).attr('id');

  $.ajax({
    url: `/documentosContables/estadoSeguros/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      cargarSeguros({
        page:     $('#herramientasPaginacionSeguros').getCurrentPage(),
        perPage:  $('#herramientasPaginacionSeguros').getPageSize(),
        casino:   $('#FCasinoSeguros').val(),
        desde: $('#fecha_SegurosDesde').val(),
        hasta: $('#fecha_SegurosHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$(document).on('click', '.btn-verRegSeguros', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarSeguros/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }


    $('#ver_monto_Seguros').val(formatoAR(data.monto));

    $('#ver_casino_Seguros').val(data.casino);
    $('#ver_fecha_SegurosDes').val(data.periodo_inicio);
    $('#ver_fecha_SegurosHas').val(data.periodo_fin);
    $('#ver_tipo_Seguros').val(data.tipoSeguro);
    $('#ver_compañia_Seguros').val(data.compañia);
    $('#ver_poliza_Seguros').val(data.nro_poliza);
    $('#ver_cta_paga_Seguros').val(formatoAR(data.cta_paga_total));
    $('#ver_art_Seguros').val(data.art);
    $('#ver_requerimento_Seguros').val(data.requerimento_anual);
    $('#ver_obs_Seguros').val(data.observaciones);
    $('#ver_estado_Seguros').val(data.estado);



    $('#modalVerSeguros').modal('show');
  });
});


$('#btn-descargarSegurosExcel').on('click',function(e){

  $('#collapseDescargarSeguros .has-error').removeClass('has-error');
  $('#collapseDescargarSeguros .js-error').remove();

  const casino = $('#FCasinoSeguros').val() ? $('#FCasinoSeguros').val() : 4;
  const desde = $('#fecha_SegurosDesde').val();
  const hasta = $('#fecha_SegurosHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoSeguros').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }
  if(desde>hasta && hasta){
    $('#fecha_SegurosDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

    if(casino!=4) {
      window.location.href = `/documentosContables/descargarSegurosXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
    }else{
      window.location.href = `/documentosContables/descargarSegurosXlsxTodos?desde=${desde}&hasta=${hasta}`;

    }
});

$('#btn-descargarSegurosCsv').on('click', function () {
  const casino = $('#FCasinoSeguros').val() ? $('#FCasinoSeguros').val() : 4;
  const desde = $('#fecha_SegurosDesde').val();
  const hasta = $('#fecha_SegurosHasta').val();
  let valid = true;
  $('#collapseDescargarSeguros .has-error').removeClass('has-error');
  $('#collapseDescargarSeguros .js-error').remove();

  if (!casino) {
    $('#DCasinoSeguros').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_SegurosDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarSegurosCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


//DERECHO DE ACCESO

instalarNumeroFlexibleAR('#monto_DerechoAcceso');

function cargarArchivosDerechoAccesoLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('DerechoAccesoId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosDerechoAcceso/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/DerechoAcceso/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="DerechoAcceso"  class="btn btn-sm btn-danger btn-del-archivo-DerechoAcceso" title="Quitar">')
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

$(document).on('click', '.btn-archivos-DerechoAcceso', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro DerechoAcceso');
  cargarArchivosDerechoAccesoLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-DerechoAcceso', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});
function abrirModalDerechoAccesoEditar(id){
  resetFormDerechoAcceso();
  $('#DerechoAcceso_modo').val('edit');
  $('#id_registroDerechoAcceso').val(id);
  $('#modalCargarDerechoAcceso .modal-title').text('| EDITAR REGISTRO DE DERECHO DE ACCESO');
  $('#guardarRegistroDerechoAcceso').text('ACTUALIZAR');
  $('#modalCargarDerechoAcceso').modal('show');

  $.getJSON('/documentosContables/llenarDerechoAccesoEdit/'+id, function(d){
    var ym  = String(d.fecha || '').slice(0,7);


    $('#fechaDerechoAcceso input[name="fecha_DerechoAcceso"]')
      .val(ym).trigger('input').trigger('change');

    $('#casinoDerechoAcceso').val(d.casino).trigger('change');

    $('[name="semanaDerechoAcceso"]').val(d.semana ?? '').trigger('change');

    $('[name="fecha_venc_DerechoAcceso"]')
      .val(d.fecha_venc || '').trigger('input').trigger('change');

    $('[name="monto_DerechoAcceso"]').val(formatoAR(d.monto ?? ''));
    $('[name="obs_DerechoAcceso"]').val(d.obs || '');
  })
  .fail(function(xhr){
    console.error('[DerechoAcceso] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro.');
  });
}



function resetFormDerechoAcceso(){
  var $f = $('#formNuevoRegistroDerechoAcceso');
  $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();
  $('#fileNameDerechoAcceso').val('No se ha seleccionado ningún archivo');
  $('#fileListDerechoAcceso').empty();
  $('#uploadDerechoAcceso').val('');
  $('#uploadsDerechoAccesoContainer').empty();
    $('#uploadsDerechoAccesoTable tbody').empty();
    $('#uploadsDerechoAccesoWrap').hide();
    $('#fileNameDerechoAcceso').val('No se ha seleccionado ningún archivo');
  }

function abrirModalDerechoAccesoCrear(){
  $('#DerechoAcceso_modo').val('create');
  $('#id_registroDerechoAcceso').val('');
  $('#modalCargarDerechoAcceso .modal-title').text('| NUEVO REGISTRO DE DERECHO DE ACCESO');
  $('#guardarRegistroDerechoAcceso').text('GENERAR');
  $('#modalCargarDerechoAcceso').modal('show');
}


$(document).on('click','#DerechoAcceso_nuevo',function(){
  abrirModalDerechoAccesoCrear();
});

$(document).on('click','.btn-edit-DerechoAcceso',function(){
  var id = $(this).data('id');
  abrirModalDerechoAccesoEditar(id);
});

$(document).on('click','#guardarRegistroDerechoAcceso',function(e){
  var $form = $('#formNuevoRegistroDerechoAcceso');
  let valid=true;
  var id   = $('#id_registroDerechoAcceso').val() || '';
  var modo = $('#DerechoAcceso_modo').val() || 'create';

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();


valid = validarCampo("input[name='fecha_DerechoAcceso']",'.col-md-6','La fecha es requerida.', valid);

valid = validarCampo("input[name='fecha_venc_DerechoAcceso']",'.col-md-6','La fecha es requerida.', valid);

valid = validarCampoNum("input[name='monto_DerechoAcceso']",'.col-md-4','El importe es requerido.', valid);

if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);
var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsDerechoAccesoContainer input[type="file"][name="uploadDerechoAcceso[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadDerechoAcceso[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadDerechoAcceso');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadDerechoAcceso[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarDerechoAcceso/'+id)
      : '/documentosContables/guardarDerechoAcceso';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarDerechoAcceso({
      page:     1,
      perPage:  $('#herramientasPaginacionDerechoAcceso').getPageSize(),
      casino:   $('#FCasinoDerechoAcceso').val(),
      desde: $('#fecha_DerechoAccesoDesde').val(),
      hasta: $('#fecha_DerechoAccesoHasta').val()
    });
    setTimeout(() => $('#modalCargarDerechoAcceso').modal('hide'), 1000);
    resetFormDerechoAcceso();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarDerechoAcceso').on('click', function(e){
  e.preventDefault();
  cargarDerechoAcceso({
    page:    1,
    perPage: $('#herramientasPaginacionDerechoAcceso').getPageSize(),
    casino:  $('#FCasinoDerechoAcceso').val(),
    desde: $('#fecha_DerechoAccesoDesde').val(),
    hasta: $('#fecha_DerechoAccesoHasta').val()
  });
});

function clickIndiceDerechoAcceso(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarDerechoAcceso({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoDerechoAcceso').val(),
    desde: $('#fecha_DerechoAccesoDesde').val(),
    hasta: $('#fecha_DerechoAccesoHasta').val()
  });
}

function cargarDerechoAcceso({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasDerechoAcceso',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaDerechoAcceso').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaDerechoAcceso').append(generarFilaDerechoAcceso(item));
      });

      $('#herramientasPaginacionDerechoAcceso').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDerechoAcceso
      );
      $('#herramientasPaginacionDerechoAcceso').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDerechoAcceso
      );

    },
    error(err) {
      console.error('Error cargando DerechoAcceso:', err);
    }
  });
}

function generarFilaDerechoAcceso(DerechoAcceso,controlador) {
  const fila = $('<tr>').attr('id', DerechoAcceso.id_registroDerechoAcceso);
  const fecha = convertirMesAno(DerechoAcceso.fecha_DerechoAcceso) || '-';
  const fecha_venc = DerechoAcceso.fecha_venc || '-';
  const casino= DerechoAcceso.casino || '-';
  const monto = '$ '+ formatoAR(DerechoAcceso.monto) || '-';
  const obs = DerechoAcceso.obs || '-';
  const semana = DerechoAcceso.semana || '-';

  fila
    .append($('<td>').addClass('col-xs-1').html(fecha))
    .append($('<td>').addClass('col-xs-1').text(semana))
    .append($('<td>').addClass('col-xs-2').text(fecha_venc))
    .append($('<td>').addClass('col-xs-2').text(monto))
    .append($('<td>').addClass('col-xs-4').text(obs))


  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  if (obs.length>65){
    const btnView = $('<a>')
      .addClass('btn btn-success btn-sm btn-verRegDerechoAcceso')
      .attr('id',DerechoAcceso.id_registroDerechoAcceso)
      .attr('target', '_blank')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER OBSERVACION')
      .append($('<i>').addClass('fa fa-fw fa-eye'));
    tdAcc.append(btnView);
  }
  if (DerechoAcceso.tiene_archivos) {
  const btnFiles = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-archivos-DerechoAcceso')
    .attr('type','button')
    .attr('data-id', DerechoAcceso.id_registroDerechoAcceso)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER ARCHIVOS ASOCIADOS')
    .append($('<i>').addClass('fa fa-file'));
  tdAcc.append(btnFiles);
}
  var btnEdit = $('<button>')
    .addClass('btn btn-info btn-sm mr-1 btn-edit-DerechoAcceso')
    .attr('type','button')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('data-id', DerechoAcceso.id_registroDerechoAcceso)
    .attr('title','EDITAR')
    .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);

  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteDerechoAcceso')
  .attr('id',DerechoAcceso.id_registroDerechoAcceso)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR DERECHO DE ACCESO')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteDerechoAcceso', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarDerechoAcceso').attr('data-id', id);
  $('#modalEliminarDerechoAcceso').modal('show');
});

$('#btn-eliminarDerechoAcceso').on('click', function(){
  const id = $(this).attr('data-id');
  $.ajax({
    url: `/documentosContables/eliminarDerechoAcceso/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarDerechoAcceso').modal('hide');
      cargarDerechoAcceso({
        page:     $('#herramientasPaginacionDerechoAcceso').getCurrentPage(),
        perPage:  $('#herramientasPaginacionDerechoAcceso').getPageSize(),
        casino:   $('#FCasinoDerechoAcceso').val(),
        desde: $('#fecha_DerechoAccesoDesde').val(),
        hasta: $('#fecha_DerechoAccesoHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});

$(document).on('click', '.btn-verRegDerechoAcceso', function(){
  const id = $(this).attr('id');

  $.get(`/documentosContables/llenarDerechoAcceso/${id}`, function(data){
    if (!data) {
      alert('No se encontraron datos');
      return;
    }

    $('#obsDerechoAcceso').text(data.obs);

    $('#modalVerDerechoAcceso').modal('show');
  });
});

$('#btn-descargarDerechoAccesoExcel').on('click',function(e){

  $('#collapseDescargarDerechoAcceso .has-error').removeClass('has-error');
  $('#collapseDescargarDerechoAcceso .js-error').remove();

  const desde = $('#fecha_DerechoAccesoDesde').val();
  const hasta = $('#fecha_DerechoAccesoDescHasta').val();
  let   valid  = true;


  if(desde>hasta && hasta){
    $('#fecha_DerechoAccesoDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;
  window.location.href = `/documentosContables/descargarDerechoAccesoXlsx?casino=3&desde=${desde}&hasta=${hasta}`;


});

$('#btn-descargarDerechoAccesoCsv').on('click', function () {
  const desde = $('#fecha_DerechoAccesoDesde').val();
  const hasta = $('#fecha_DerechoAccesoHasta').val();
  let valid = true;
  $('#collapseDescargarDerechoAcceso .has-error').removeClass('has-error');
  $('#collapseDescargarDerechoAcceso .js-error').remove();


  if(desde>hasta && hasta){
    $('#fecha_DerechoAccesoDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarDerechoAccesoCsv?casino=3`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});



//PATENTES

var PAT_PAGOS_IDX = 0;

function renderFilaPagoPatentes(p){
  PAT_PAGOS_IDX++;
  var idx = PAT_PAGOS_IDX;

  var $row = $(
    '<div class="row pago-row" data-idx="'+ idx +'" style="margin-bottom:10px;">' +
      '<input type="hidden" name="pago_patenteDe[]" value="'+ (p.id || '') +'">' +

      '<div class="col-md-2">' +
        '<h5>Patente</h5>' +
        '<div class="form-control" style="height:auto; min-height:34px; padding-top:6px; font-weight:600;">'+ (p.patenteDe || p.nombre || '') +'</div>' +
      '</div>' +

      '<div class="col-md-1">' +
        '<h5>Cuota</h5>' +
        '<input type="text" class="form-control pago-cuota" id="pat_pago_cuota_'+ idx +'" name="pago_cuota[]">' +
      '</div>' +

      '<div class="col-md-2">' +
        '<h5>Importe</h5>' +
        '<input type="text" class="form-control pago-importe" id="pat_pago_importe_'+ idx +'" name="pago_importe[]" placeholder="$">' +
      '</div>' +

      '<div class="col-md-2">' +
        '<h5>Fecha</h5>' +
        '<div class="input-group date" id="pat_pago_fecha_'+ idx +'">' +
          '<input type="text" class="form-control pago-fecha" name="pago_fecha_pres[]" placeholder="yyyy-mm-dd" autocomplete="off">' +
          '<span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>' +
          '<span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>' +
        '</div>' +
      '</div>' +

      '<div class="col-md-5">' +
        '<h5>Observación</h5>' +
        '<textarea class="form-control pago-observacion" id="pat_pago_obs_'+ idx +'" name="pago_observacion[]" rows="1"></textarea>' +
      '</div>' +
    '</div>'
  );

  $row.find('.pago-cuota').val(p.cuota || '');
  if (p.importe != null && p.importe !== '') $row.find('.pago-importe').val(p.importe);
  $row.find('.pago-fecha').val(p.fecha_pres || p.fecha || '');
  $row.find('.pago-observacion').val(p.observacion || p.obs || '');

  $('#pagosPatentesContainer').append($row);
  initPagoRowPatentes(idx);
  return $row;
}

function initPagoRowPatentes(idx){
  var $modal = $('#modalCargarPatentes');
  var $fecha = $('#pat_pago_fecha_' + idx);

  if (typeof instalarNumeroFlexibleAR === 'function') {
    instalarNumeroFlexibleAR('#pat_pago_importe_' + idx, { decimales: 2 });
  }
  if (typeof attachYYYYMMDDFormatter === 'function') {
    attachYYYYMMDDFormatter('#pat_pago_fecha_' + idx + ' input');
  }

  if (!$.fn.datetimepicker) { console.warn('[Patentes] Falta $.fn.datetimepicker'); return; }
  try { $fecha.datetimepicker('remove'); } catch(e){}

  var opts = {
    language: 'es',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    format: 'yyyy-mm-dd',
    startView: 2,
    minView: 2,
    maxView: 4,
    forceParse: false
  };

  $fecha.datetimepicker(opts);

  $fecha.off('show.dp.reloc').on('show.dp.reloc', function(){
    var inst = $fecha.data('datetimepicker');
    var $widget = inst && inst.picker ? inst.picker : $('.datetimepicker:visible').last();
    if ($widget.length && !$modal.has($widget).length) {
      $modal.append($widget);
      try { inst.place(); } catch(e){}
    }
    $widget.find('.timepicker, .datetimepicker-hours, .datetimepicker-minutes').hide();
    $widget.find('.datepicker, .datetimepicker-days').show();
  });

  $('#pat_pago_fecha_'+idx+' .input-group-addon:last-child').off('click').on('click', function(){
    $(this).closest('.input-group.date').datetimepicker('show');
  });
  $('#pat_pago_fecha_'+idx+' .input-group-addon:first-child').off('click').on('click', function(){
    $(this).closest('.input-group.date').find('input').val('').trigger('input').trigger('change');
    try { $(this).closest('.input-group.date').datetimepicker('hide'); } catch(e){}
  });

  $fecha.off('changeDate.dpHide').on('changeDate.dpHide', function(){
    try { $fecha.datetimepicker('hide'); } catch(e){}
  });
}

(function injectPickerFixesPat(){
  if (document.getElementById('dtp-fixes-pat-css')) return;
  var css =
    '#modalCargarPatentes .datetimepicker{ z-index: 20000 !important; }' +
    '#modalCargarPatentes .datetimepicker .timepicker,' +
    '#modalCargarPatentes .datetimepicker .datetimepicker-hours,' +
    '#modalCargarPatentes .datetimepicker .datetimepicker-minutes{ display:none !important; }' +
    '#modalCargarPatentes .datetimepicker .datepicker,' +
    '#modalCargarPatentes .datetimepicker .datetimepicker-days{ display:block !important; }';
  var style = document.createElement('style');
  style.id = 'dtp-fixes-pat-css';
  style.type = 'text/css';
  style.appendChild(document.createTextNode(css));
  document.head.appendChild(style);
})();

function cargarPagosPorCasinoPatentes(casinoId){
  var $cont = $('#pagosPatentesContainer').empty();
  PAT_PAGOS_IDX = 0;
  if(!casinoId) return;

  $.getJSON('/documentosContables/getPatentes_patenteDeHabilitadosPorCasino/'+casinoId)
    .done(function(data){
      (data || []).forEach(function(p){
        renderFilaPagoPatentes({
          id: (p.id || p.id_registroPatentes_patenteDe || ''),
          patenteDe: p.nombre || ''
        });
      });
    })
    .fail(function(){
      $cont.append('<div class="alert alert-danger">Error al cargar elementos patentables.</div>');
    });
}

$(document)
  .off('change.patPagos','#casinoPatentes')
  .on('change.patPagos','#casinoPatentes', function(){
    if (window.PAT_SUPPRESS_CASINO_CHANGE) return;
    cargarPagosPorCasinoPatentes($(this).val());
  });

  var VER_PAT_PAGOS_IDX = 0;

  function renderFilaPagoPatentesReadOnly(p){
    VER_PAT_PAGOS_IDX++;
    var idx = VER_PAT_PAGOS_IDX;

var $row = $(
  '<div class="row pago-row" data-idx="'+ idx +'">' +
    '<div class="col-md-2"><h5>Patente</h5><div class="form-control fc-wrap"></div></div>' +
    '<div class="col-md-1"><h5>Cuota</h5><input type="text" class="form-control" readonly></div>' +
    '<div class="col-md-2"><h5>Importe</h5><input type="text" class="form-control" readonly></div>' +
    '<div class="col-md-2"><h5>Fecha</h5><div class="input-group date"><input type="text" class="form-control" readonly></div></div>' +
    '<div class="col-md-5"><h5>Observación</h5><textarea class="form-control" rows="1" readonly></textarea></div>' +
  '</div>'
);

(function injectWrapCss(){
  if (document.getElementById('patente-wrap-css')) return;
  var css = `
    .fc-wrap{
      min-height:34px;
      height:auto;
      padding-top:6px;
      overflow:hidden;
      white-space:normal;           /* permite múltiples líneas */
      word-break:break-word;        /* corta palabras largas */
      overflow-wrap:anywhere;       /* fuerza corte si es necesario */
    }
  `;
  var s=document.createElement('style'); s.id='patente-wrap-css'; s.textContent=css;
  document.head.appendChild(s);
})();


    var importeFmt = (p.importe != null && p.importe !== '')
      ? (typeof formatoAR === 'function' ? '$ ' + formatoAR(p.importe) : p.importe)
      : '';

    $row.find('.col-md-2 .form-control').first().text(p.patenteDe || p.nombre || '');
    $row.find('.col-md-1 input').val(p.cuota || '');
    $row.find('.col-md-2 input.form-control').first().val(importeFmt);
    $row.find('.col-md-2 .input-group.date input').val(p.fecha_pres || p.fecha || '');
    $row.find('textarea').val(p.observacion || p.obs || '');

    return $row;
  }

  function renderVerPagosPatentes(containerSelector, pagos){
    var $c = $(containerSelector);
    $c.empty();
    VER_PAT_PAGOS_IDX = 0;

    if (!Array.isArray(pagos) || !pagos.length){
      $c.append('<div class="alert alert-info" role="alert">No hay pagos registrados.</div>');
      return;
    }
    pagos.forEach(function(p){ $c.append( renderFilaPagoPatentesReadOnly(p) ); });
  }

  $(document).off('click', '.btn-verRegPatentes').on('click', '.btn-verRegPatentes', function(){
    const id = $(this).attr('id');
    $.getJSON('/documentosContables/llenarPatentes/'+id, function(data){
      if (!data) { alert('No se encontraron datos'); return; }

      const fecha = convertirMesAno(data.fecha_Patentes || data.fecha);
      $('#ver_fecha_Patentes').val(fecha || '');
      $('#ver_casino_Patentes').val(data.casino || '');
      $('#ver_obs_Patentes').val(data.observaciones || data.obs || '');

      renderVerPagosPatentes('#ver_pagosPatentesContainer', data.pagos || []);

      $('#modalVerPatentes').modal('show');
    }).fail(function(xhr){
      console.error('llenarPatentes fail', xhr.responseText || xhr.statusText);
      alert('No se pudo obtener el registro.');
    });
  });


instalarNumeroFlexibleAR('#total_Patentes');

$(document).on('click','#Patentes_nuevo_patenteDe',function(e){

    $('#modalCargarPatentes_patenteDe').modal('show');

});

function cargarArchivosPatentesLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('PatentesId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosPatentes/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/Patentes/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="Patentes"  class="btn btn-sm btn-danger btn-del-archivo-Patentes" title="Quitar">')
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

$(document).on('click', '.btn-archivos-Patentes', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro Patentes');
  cargarArchivosPatentesLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-Patentes', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});


function resetFormPatentes(){
  var $f = $('#formNuevoRegistroPatentes');
  if ($f[0]) $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();

  $('#uploadsPatentesContainer').empty();
  $('#uploadsPatentesTable tbody').empty();
  $('#uploadsPatentesWrap').hide();
  $('#fileNamePatentes').val('No se ha seleccionado ningún archivo');
  $('#uploadPatentes').val('');

  $('#pagosPatentesContainer').empty();
  PAT_PAGOS_IDX = 0;
}

function abrirModalPatentesCrear(){
  $('#Patentes_modo').val('create');
  $('#id_registroPatentes').val('');
  $('#modalCargarPatentes .modal-title').text('| NUEVO REGISTRO DE PATENTES');
  $('#guardarRegistroPatentes').text('GENERAR');
  $('#modalCargarPatentes').modal('show');
}


$(document).on('click','#Patentes_nuevo',function(){
  abrirModalPatentesCrear();
});

$(document).on('click','.btn-edit-Patentes',function(){
  var id = $(this).data('id');
  abrirModalPatentesEditar(id);
});

function abrirModalPatentesEditar(id){
  resetFormPatentes();
  $('#Patentes_modo').val('edit');
  $('#id_registroPatentes').val(id);
  $('#modalCargarPatentes .modal-title').text('| EDITAR REGISTRO DE PATENTES');
  $('#guardarRegistroPatentes').text('ACTUALIZAR');
  $('#modalCargarPatentes').modal('show');

  window.PAT_SUPPRESS_CASINO_CHANGE = true;

  $.getJSON('/documentosContables/llenarPatentesEdit/'+id, function(d){
    var ym = String(d.fecha || d.fecha_Patentes || '').slice(0,7);
    $('#fechaPatentes input[name="fecha_Patentes"]').val(ym);

    $('#casinoPatentes').val(d.casino || '');

    var $cont = $('#pagosPatentesContainer').empty();
    PAT_PAGOS_IDX = 0;

    if (Array.isArray(d.pagos) && d.pagos.length) {
      var seenIds = new Set();
      d.pagos.forEach(function(p){
        var pid = (p.id != null && p.id !== '') ? String(p.id) : null;
        if (pid && seenIds.has(pid)) return;
        if (pid) seenIds.add(pid);

        renderFilaPagoPatentes({
          id:          p.patenteDe_id || p.patenteDe || '',
          patenteDe:   p.patenteDe_nombre || p.patenteDe || p.nombre || '',
          cuota:       p.cuota ?? '',
          importe:     p.importe ?? '',
          fecha_pres:  p.fecha_pres ?? '',
          observacion: p.observacion ?? p.obs ?? ''
        });
      });
    } else {
      if (d.casino) cargarPagosPorCasinoPatentes(d.casino);
    }
  })
  .fail(function(xhr){
    console.error('[Patentes editar] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro de Patentes.');
  })
  .always(function(){
    window.PAT_SUPPRESS_CASINO_CHANGE = false;
  });
}


$(document).on('change','#casinoPatentes',function(){
  var sid = $('#elementoPatentable_Patentes').data('selected-id');
  cargarElementosPatentables($(this).val(), sid);
});

function cargarElementosPatentables(casinoId, selectedId){
  var $sel = $('#elementoPatentable_Patentes');
  $sel.prop('disabled', true).empty().append('<option value="">Cargando...</option>');

  if(!casinoId){
    $sel.html('<option value="">Elige un elemento patentable</option>').prop('disabled', false);
    $sel.removeData('selected-id');
    return;
  }

  $.getJSON('/documentosContables/getPatentes_patenteDeHabilitadosPorCasino/'+casinoId, function(data){
    var opts = '<option value="">Elige un elemento patentable</option>';
    $.each(data || [], function(_, t){
      opts += '<option value="'+ t.id +'">'+ (t.nombre || '') +'</option>';
    });
    $sel.html(opts);

    var sid = selectedId != null && selectedId !== '' ? String(selectedId) : String($sel.data('selected-id') || '');
    if (sid) $sel.val(sid);
    $sel.removeData('selected-id');
  })
  .fail(function(){
    $sel.html('<option value="">Error al cargar</option>');
  })
  .always(function(){
    $sel.prop('disabled', false);
  });
}



$(document).on('click','#Patentes_patenteDe_gestionar',function(){
  $('#modalPatentes_patenteDe_gestionar').modal('show');
  cargarPatentes_patenteDeGestion();
});

function renderFilaPatentes_patenteDe(d){
  var estado = d.estado
  ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
  : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';
  return ''+
  '<tr data-id="'+d.id+'" data-estado="'+Number(d.estado)+'" data-nombre="'+escapeAttr(d.nombre || '')+'">'+
    '<td class="col-md-6">'+escapeHtml(d.nombre || '')+'</td>'+
    '<td class="col-md-1">'+escapeHtml(d.casino || '')+'</td>'+
    '<td class="col-md-2">'+estado+ '</td>'+
    '<td class="text-left col-md-3">'+
      '<button type="button" class="btn btn-sm btn-primary btn-editPatentes_patenteDe" title="MODIFICAR ELEMENTO PATENTABLE">'+
        '<i class="fa fa-edit"></i>'+
      '</button> '+
    '</td>'+
  '</tr>';
}


function cargarPatentes_patenteDeGestion(casinoId){
  $('#dir-list-loading_Patentes_patenteDe').show();
  $('#tabla-Patentes_patenteDe').closest('.table-responsive').hide();
  $('#tabla-Patentes_patenteDe tbody').empty();

  var url = '/documentosContables/getPatentes_patenteDe';

  $.getJSON(url, function(data){
    var rows = '';
    for (var i=0; i<data.length; i++){
      var d = data[i];
      rows += renderFilaPatentes_patenteDe(d);
    }
    $('#tabla-Patentes_patenteDe tbody').html(rows);
    $('#dir-list-loading_Patentes_patenteDe').hide();
    $('#tabla-Patentes_patenteDe').closest('.table-responsive').show();
  }).fail(function(xhr){
    $('#dir-list-loading_Patentes_patenteDe').text('Error cargando elementos patentables.');
    console.error(xhr.responseText);
  });
}

$(document).on('click', '.btn-elimPatentes_patenteDe', function(){
  const id = $(this).closest('tr').data('id');

  $('#btn-eliminarPatentes_patenteDe').attr('data-id', id);
  $('#modalEliminarPatentes_patenteDe').modal('show');
});

$(document).on('click', '.btn-editPatentes_patenteDe', function(){
  const $tr  = $(this).closest('tr');
  const id = $(this).closest('tr').data('id');
  const nombre = $(this).closest('tr').data('nombre');
  const estado = $(this).closest('tr').data('estado');
  var $sel = $('#ModifPatentes_patenteDe_estado');
  $('#ModifId_Patentes_patenteDe').val(id);
  $('#ModifPatentes_patenteDe_nombre').val(nombre);
  if(Number(estado)===1){
    $sel.empty().append('<option value="1">Habilitado</option><option value="0">Deshabilitado</option>');
  }else{
    $sel.empty().append('<option value="0">Deshabilitado</option><option value="1">Habilitado</option>');
  }


  $('#modalModificarPatentes_patentesDe').modal('show');
});

$(document).on('click', '#guardarModifRegistroPatentes_patenteDe', function(){
  const $btn  = $(this).prop('disabled', true);
  const $form = $('#formModificarRegistroPatentes_patenteDe');

  valid = true;
  valid = validarCampo("input[name='ModifPatentes_patenteDe_nombre']",'.col-md-6','El tipo es requerido.', valid);

  if(!valid) return 0;

  $.ajax({
    url: '/documentosContables/modificarPatentes_patenteDe',
    method: 'POST',
    data: $form.serialize(),
    success: function(res){
      cargarPatentes_patenteDeGestion();
      $('#modalModificarPatentes_patentesDe').modal('hide');
    },
    error: function(xhr){
      console.error(xhr.responseText || xhr);
    },
    complete: function(){
      $btn.prop('disabled', false);
    }
  });
});



$(document).on('click', '#btn-eliminarPatentes_patenteDe', function () {
  const id  = $(this).attr('data-id');
  const $tr = $('#tabla-Patentes_patenteDe tr[data-id="'+id+'"]');

  $.get('/documentosContables/PatentesEliminarpatenteDe/' + id, function (res) {
    if (res.ok) {
      $tr.remove();
      $('#modalEliminarPatentes_patenteDe').modal('hide')
    } else {
    }
  }).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
  });
});

$(document).on('click','#guardarRegistroPatentes_patenteDe',function(e){
  var $form = $('#formNuevoRegistroPatentes_patenteDe');
  let valid=true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  valid = validarCampo("select[name='CasinoPatentes_patenteDe']",'.col-md-3','El casino es requerido.', valid);
  valid = validarCampo("input[name='nombre_Patentes_patenteDe']",'.col-md-9','El tipo es requerido.', valid);

  if(!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: '/documentosContables/guardarRegistroPatentes_patenteDe',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(res){
      setTimeout(() => $('#modalCargarPatentes_patenteDe').modal('hide'), 1000);
    },
    error: function(xhr){

      $('#salir').next('.help-block.js-error').remove();
      $('#salir')
        .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
    }


  });
});

$(document).off('click','#guardarRegistroPatentes').on('click','#guardarRegistroPatentes',function(e){
  var $form = $('#formNuevoRegistroPatentes');
  let valid=true;
  var id   = $('#id_registroPatentes').val() || '';
  var modo = $('#Patentes_modo').val() || 'create';

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  valid = validarCampo("select[name='casinoPatentes']",'.col-md-4','El casino es requerido.', valid);
  valid = validarCampo("input[name='fecha_Patentes']",'.col-md-4','La fecha es requerida.', valid);

  $('#pagosPatentesContainer .pago-row').each(function(){
    var $row = $(this);
    var $cuo = $row.find('input.pago-cuota');
    var $imp = $row.find('input.pago-importe');
    var $fec = $row.find('input.pago-fecha');

    var vCuo = ($cuo.val() || '').trim();
    var vImp = ($imp.val() || '').trim();
    var vFec = ($fec.val() || '').trim();

    if (!vCuo && !vImp && !vFec) return;

    valid = validarCampoNum($cuo, '.col-md-1', 'La cuota es requerida.', valid);
    valid = validarCampoNum($imp, '.col-md-2', 'El importe es requerido.', valid);
    valid = validarCampo($fec,  '.col-md-2', 'La fecha es requerida.',   valid);
  });

  if(!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $('#uploadsPatentesContainer input[type="file"][name="uploadPatentes[]"]').each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      formData.append('uploadPatentes[]', files[i]);
    }
  });
  var cur = document.getElementById('uploadPatentes');
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      formData.append('uploadPatentes[]', cur.files[j]);
    }
  }

  var url = (modo === 'edit')
      ? ('/documentosContables/actualizarPatentes/'+id)
      : '/documentosContables/guardarPatentes';

  $.ajax({
    url:url,
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(res){
      cargarPatentes({
        page:     1,
        perPage:  $('#herramientasPaginacionPatentes').getPageSize(),
        casino:   $('#FCasinoPatentes').val(),
        desde: $('#fecha_PatentesDesde').val(),
        hasta: $('#fecha_PatentesHasta').val()
      });
      setTimeout(() => $('#modalCargarPatentes').modal('hide'), 1000);
      resetFormPatentes();
    },
    error: function(xhr){
      $('#salir').next('.help-block.js-error').remove();
      $('#salir').after('<span class="help-block js-error text-danger" style="color:red;">Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
    }
  });
});


$('#btn-buscarPatentes').on('click', function(e){
  e.preventDefault();
  cargarPatentes({
    page:    1,
    perPage: $('#herramientasPaginacionPatentes').getPageSize(),
    casino:  $('#FCasinoPatentes').val(),
    desde: $('#fecha_PatentesDesde').val(),
    hasta: $('#fecha_PatentesHasta').val()
  });
});

function clickIndicePatentes(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPatentes({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoPatentes').val(),
    desde: $('#fecha_PatentesDesde').val(),
    hasta: $('#fecha_PatentesHasta').val()
  });
}

function cargarPatentes({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasPatentes',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaPatentes').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaPatentes').append(generarFilaPatentes(item));
      });

      $('#herramientasPaginacionPatentes').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePatentes
      );
      $('#herramientasPaginacionPatentes').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePatentes
      );

    },
    error(err) {
      console.error('Error cargando Patentes:', err);
    }
  });
}
function generarFilaPatentes(Patentes){
  const fila  = $('<tr>').attr('id', Patentes.id_registroPatentes);
  const fecha = convertirMesAno(Patentes.fecha_Patentes) || '-';
  const casino = Patentes.casino || '-';

  fila
    .append($('<td>').addClass('col-xs-6').html(fecha))
    .append($('<td>').addClass('col-xs-4').text(casino));

  // Acciones
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegPatentes')
    .attr('id',Patentes.id_registroPatentes)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO DE PATENTES')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

  if (Patentes.tiene_archivos) {
    const btnFiles = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-archivos-Patentes')
      .attr('type','button')
      .attr('data-id', Patentes.id_registroPatentes)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER ARCHIVOS ASOCIADOS')
      .append($('<i>').addClass('fa fa-file'));
    tdAcc.append(btnFiles);
  }

  const btnEdit = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-edit-Patentes')
      .attr('type','button')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-id', Patentes.id_registroPatentes)
      .attr('title','EDITAR')
      .append($('<i>').addClass('fa fa-edit'));
  tdAcc.append(btnEdit);

  const btnDelete = $('<button>')
    .addClass('btn btn-danger btn-sm btn-deletePatentes')
    .attr('id',Patentes.id_registroPatentes)
    .attr('data-toggle','tooltip')
    .attr('data-placement','bottom')
    .attr('title','ELIMINAR REGISTRO DE PATENTES')
    .append($('<i>').addClass('fa fa-trash'));
  tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}


$(document).on('click', '.btn-deletePatentes', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarPatentes').attr('data-id', id);
  $('#modalEliminarPatentes').modal('show');
});

$('#btn-eliminarPatentes').on('click', function(){
  const id = $(this).attr('data-id');

  $.ajax({
    url: `/documentosContables/eliminarPatentes/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarPatentes').modal('hide');
      cargarPatentes({
        page:     $('#herramientasPaginacionPatentes').getCurrentPage(),
        perPage:  $('#herramientasPaginacionPatentes').getPageSize(),
        casino:   $('#FCasinoPatentes').val(),
        desde: $('#fecha_PatentesDesde').val(),
        hasta: $('#fecha_PatentesHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});




$('#btn-descargarPatentesExcel').on('click',function(e){

  $('#collapseDescargarPatentes .has-error').removeClass('has-error');
  $('#collapseDescargarPatentes .js-error').remove();

  const casino = $('#FCasinoPatentes').val() ? $('#FCasinoPatentes').val() : 4;
  const desde = $('#fecha_PatentesDesde').val();
  const hasta = $('#fecha_PatentesHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoPatentes').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_PatentesDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  if(casino!=4) {
    window.location.href = `/documentosContables/descargarPatentesXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  }else{
    window.location.href = `/documentosContables/descargarPatentesXlsxTodos?desde=${desde}&hasta=${hasta}`;

  }

});

$('#btn-descargarPatentesCsv').on('click', function () {
  const casino = $('#FCasinoPatentes').val() ? $('#FCasinoPatentes').val() : 4;
  const desde = $('#fecha_PatentesDesde').val();
  const hasta = $('#fecha_PatentesHasta').val();
  let valid = true;
  $('#collapseDescargarPatentes .has-error').removeClass('has-error');
  $('#collapseDescargarPatentes .js-error').remove();

  if (!casino) {
    $('#DCasinoPatentes').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_PatentesDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPatentesCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});


// IMPUESTO INMOBILIARIO

var IMP_PAGOS_IDX = 0;

function renderFilaPagoImpInmobiliario(p){
  IMP_PAGOS_IDX++;
  var idx = IMP_PAGOS_IDX;

  var $row = $(
    '<div class="row pago-row" data-idx="'+ idx +'" style="margin-bottom:10px;">' +
      '<input type="hidden" name="pago_partida[]" value="'+ (p.id || '') +'">' +
      '<div class="col-md-2">' +
        '<h5>Partida</h5>' +
        '<div class="form-control" style="height:auto; min-height:34px; padding-top:6px; font-weight:600;">'+ (p.partida || '') +'</div>' +
      '</div>' +
      '<div class="col-md-1">' +
        '<h5>Cuota</h5>' +
        '<input type="text" class="form-control pago-cuota" id="imp_pago_cuota_'+ idx +'" name="pago_cuota[]">' +
      '</div>' +
      '<div class="col-md-2">' +
        '<h5>Importe</h5>' +
        '<input type="text" class="form-control pago-importe" id="imp_pago_importe_'+ idx +'" name="pago_importe[]" placeholder="$">' +
      '</div>' +
      '<div class="col-md-2">' +
        '<h5>Fecha</h5>' +
        '<div class="input-group date" id="imp_pago_fecha_'+ idx +'">' +
          '<input type="text" class="form-control pago-fecha" name="pago_fecha_pres[]" placeholder="yyyy-mm-dd" autocomplete="off">' +
          '<span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>' +
          '<span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>' +
        '</div>' +
      '</div>' +
      '<div class="col-md-5">' +
        '<h5>Observación</h5>' +
        '<textarea class="form-control pago-observacion" id="imp_pago_obs_'+ idx +'" name="pago_observacion[]" rows="1"></textarea>' +
      '</div>' +
    '</div>'
  );

  $row.find('.pago-cuota').val(p.cuota || '');
  if (p.importe != null && p.importe !== '') $row.find('.pago-importe').val(p.importe);
  $row.find('.pago-fecha').val(p.fecha_pres || p.fecha || '');
  $row.find('.pago-observacion').val(p.observacion || p.obs || '');

  $('#pagosImpInmobiliarioContainer').append($row);
  initPagoRowImpInmobiliario(idx);
  return $row;
}


function initPagoRowImpInmobiliario(idx){
  var $modal = $('#modalCargarImpInmobiliario');
  var $fecha = $('#imp_pago_fecha_' + idx);

  if (typeof instalarNumeroFlexibleAR === 'function') {
    instalarNumeroFlexibleAR('#imp_pago_importe_' + idx, { decimales: 2 });
  }
  if (typeof attachYYYYMMDDFormatter === 'function') {
    attachYYYYMMDDFormatter('#imp_pago_fecha_' + idx + ' input');
  }

  if (!$.fn.datetimepicker) { console.warn('[ImpInmobiliario] Falta $.fn.datetimepicker'); return; }
  try { $fecha.datetimepicker('remove'); } catch(e){}

  var opts = {
    language: 'es',
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    format: 'yyyy-mm-dd',
    startView: 2,
    minView: 2,
    maxView: 4,
    forceParse: false
  };

  $fecha.datetimepicker(opts);

  $fecha.off('show.dp.reloc').on('show.dp.reloc', function(){
    var inst = $fecha.data('datetimepicker');
    var $widget = inst && inst.picker ? inst.picker : $('.datetimepicker:visible').last();
    if ($widget.length && !$modal.has($widget).length) {
      $modal.append($widget);
      try { inst.place(); } catch(e){}
    }
    $widget.find('.timepicker, .datetimepicker-hours, .datetimepicker-minutes').hide();
    $widget.find('.datepicker, .datetimepicker-days').show();
  });

  $('#imp_pago_fecha_'+idx+' .input-group-addon:last-child').off('click').on('click', function(){
    $(this).closest('.input-group.date').datetimepicker('show');
  });
  $('#imp_pago_fecha_'+idx+' .input-group-addon:first-child').off('click').on('click', function(){
    $(this).closest('.input-group.date').find('input').val('').trigger('input').trigger('change');
    try { $(this).closest('.input-group.date').datetimepicker('hide'); } catch(e){}
  });

  $fecha.off('changeDate.dpHide').on('changeDate.dpHide', function(){
    try { $fecha.datetimepicker('hide'); } catch(e){}
  });
}


(function injectPickerFixesImp(){
  if (document.getElementById('dtp-fixes-imp-css')) return;
  var css =
    '#modalCargarImpInmobiliario .datetimepicker{ z-index: 20000 !important; }' +
    '#modalCargarImpInmobiliario .datetimepicker .timepicker,' +
    '#modalCargarImpInmobiliario .datetimepicker .datetimepicker-hours,' +
    '#modalCargarImpInmobiliario .datetimepicker .datetimepicker-minutes{ display:none !important; }' +
    '#modalCargarImpInmobiliario .datetimepicker .datepicker,' +
    '#modalCargarImpInmobiliario .datetimepicker .datetimepicker-days{ display:block !important; }';
  var style = document.createElement('style');
  style.id = 'dtp-fixes-imp-css';
  style.type = 'text/css';
  style.appendChild(document.createTextNode(css));
  document.head.appendChild(style);
})();


function cargarPagosPorCasinoImpInmobiliario(casinoId){
  var $cont = $('#pagosImpInmobiliarioContainer').empty();
  IMP_PAGOS_IDX = 0;
  if(!casinoId) return;

  $.getJSON('/documentosContables/getImpInmobiliario_partidaPorCasino', { casino: casinoId })
    .done(function(data){
      (data || []).forEach(function(p){
        renderFilaPagoImpInmobiliario(p);
      });
    })
    .fail(function(){
      $cont.append('<div class="alert alert-danger">Error al cargar partidas.</div>');
    });
}

$(document)
  .off('change.impPagos','#casinoImpInmobiliario')
  .on('change.impPagos','#casinoImpInmobiliario', function(){
    if (IMP_SUPPRESS_CASINO_CHANGE) return;
    cargarPagosPorCasinoImpInmobiliario($(this).val());
  });


var VER_IMP_PAGOS_IDX = 0;

function renderFilaPagoImpInmobiliarioReadOnly(p){
  VER_IMP_PAGOS_IDX++;
  var idx = VER_IMP_PAGOS_IDX;

  var $row = $(
  '<div class="row pago-row" data-idx="'+ idx +'">' +
    '<div class="col-md-2"><h5>Partida</h5><div class="form-control partida-box fc-wrap"></div></div>' +
    '<div class="col-md-1"><h5>Cuota</h5><input type="text" class="form-control" readonly></div>' +
    '<div class="col-md-2"><h5>Importe</h5><input type="text" class="form-control" readonly></div>' +
    '<div class="col-md-2"><h5>Fecha</h5><div class="input-group date"><input type="text" class="form-control" readonly></div></div>' +
    '<div class="col-md-5"><h5>Observación</h5><textarea class="form-control" rows="1" readonly></textarea></div>' +
  '</div>'
);

var txtPartida = (p.partida || p.partida_nombre || p.nombre || '').toString();
$row.find('.partida-box').text(txtPartida);

(function injectWrapCssPartida(){
  if (document.getElementById('partida-wrap-css')) return;
  var css = `
    .fc-wrap{
      min-height:34px;
      height:auto;
      padding-top:6px;
      overflow:hidden;
      white-space:normal;
      word-break:break-word;
      overflow-wrap:anywhere;
    }
  `;
  var s = document.createElement('style');
  s.id = 'partida-wrap-css';
  s.textContent = css;
  document.head.appendChild(s);
})();


  var importeFmt = (p.importe != null && p.importe !== '')
    ? (typeof formatoAR === 'function' ? '$ ' + formatoAR(p.importe) : p.importe)
    : '';

  $row.find('.col-md-2 .form-control').first().text(p.partida || '');
  $row.find('.col-md-1 input').val(p.cuota || '');
  $row.find('.col-md-2 input.form-control').first().val(importeFmt);
  $row.find('.col-md-2 .input-group.date input').val(p.fecha_pres || p.fecha || '');
  $row.find('textarea').val(p.observacion || p.obs || '');

  return $row;
}

function renderVerPagosImpInmobiliario(containerSelector, pagos){
  var $c = $(containerSelector);
  $c.empty();
  VER_IMP_PAGOS_IDX = 0;

  if (!Array.isArray(pagos) || !pagos.length){
    $c.append('<div class="alert alert-info" role="alert">No hay pagos registrados.</div>');
    return;
  }
  for (var i=0;i<pagos.length;i++){
    $c.append( renderFilaPagoImpInmobiliarioReadOnly(pagos[i]) );
  }
}

$(document).off('click', '.btn-verRegImpInmobiliario').on('click', '.btn-verRegImpInmobiliario', function(){
  var id = $(this).attr('id');

  $.getJSON('/documentosContables/llenarImpInmobiliario/'+id, function(data){
    if (!data) { alert('No se encontraron datos'); return; }

    const fecha = convertirMesAno(data.fecha);
    console.log(fecha);
    $('#ver_fecha_ImpInmobiliario').val(fecha);
    $('#ver_casino_ImpInmobiliario').val(data.casino || '');

    renderVerPagosImpInmobiliario('#ver_pagosImpInmobiliarioContainer', data.pagos || []);

    $('#modalVerImpInmobiliario').modal('show');
  }).fail(function(xhr){
    console.error('llenarImpInmobiliario fail', xhr.responseText || xhr.statusText);
    alert('No se pudo obtener el registro.');
  });
});




let IMP_PARTIDAS_REQ_ID = 0;
let IMP_SUPPRESS_CASINO_CHANGE = false;

$(document)
  .off('change.imp','#casinoImpInmobiliario')
  .on('change.imp','#casinoImpInmobiliario', function(){
    if (IMP_SUPPRESS_CASINO_CHANGE) return;
    const sid = $('#partida_ImpInmobiliario').data('selected-id');
    cargarPartidasImpInmobiliario($(this).val(), sid ?? null);
  });


instalarNumeroFlexibleAR('#total_ImpInmobiliario');

$(document).on('click','#ImpInmobiliario_nuevo',function(e){

    $('#modalCargarImpInmobiliario').modal('show');
});

$(document).on('click','#ImpInmobiliario_nueva_partida',function(e){

    $('#modalCargarImpInmobiliario_partida').modal('show');

});

function cargarPartidasImpInmobiliario(casinoId, selectedId){
  const reqId = ++IMP_PARTIDAS_REQ_ID;
  const $sel  = $('#partida_ImpInmobiliario');

  if (selectedId != null) $sel.data('selected-id', String(selectedId));
  $sel.prop('disabled', true).html('<option value="">Cargando…</option>');

  if (!casinoId){
    $sel.html('<option value="">Elegí un casino</option>').prop('disabled', false);
    $sel.removeData('selected-id');
    return;
  }

  $.getJSON('/documentosContables/getImpInmobiliario_partidaPorCasino', { casino: casinoId })
    .done(function(data){
      if (reqId !== IMP_PARTIDAS_REQ_ID) return;
      populateSelect($sel, data, 'id', 'partida', 'Elegí una partida');
      const sid = $sel.data('selected-id');
      if (sid != null) $sel.val(sid);
    })
    .fail(function(){
      if (reqId !== IMP_PARTIDAS_REQ_ID) return;
      $sel.html('<option value="">Error al cargar</option>');
    })
    .always(function(){
      if (reqId !== IMP_PARTIDAS_REQ_ID) return;
      $sel.prop('disabled', false).removeData('selected-id');
    });
}

function abrirModalImpInmobiliarioEditar(id){
  resetFormImpInmobiliario();
  $('#ImpInmobiliario_modo').val('edit');
  $('#id_registroImpInmobiliario').val(id);
  $('#modalCargarImpInmobiliario .modal-title').text('| EDITAR REGISTRO DE IMPUESTO INMOBILIARIO');
  $('#guardarRegistroImpInmobiliario').text('ACTUALIZAR');
  $('#modalCargarImpInmobiliario').modal('show');

  IMP_SUPPRESS_CASINO_CHANGE = true;

  $.getJSON('/documentosContables/llenarImpInmobiliarioEdit/'+id, function(d){
    var ym = String(d.fecha || d.fecha_ImpInmobiliario || '').slice(0,7);
    $('#fechaImpInmobiliario input[name="fecha_ImpInmobiliario"]').val(ym);

    $('#casinoImpInmobiliario').val(d.casino); // NO dispares change acá

    if (d.partida_id != null && d.partida_id !== '') {
      $('#partida_ImpInmobiliario').data('selected-id', String(d.partida_id));
    }
    cargarPartidasImpInmobiliario(d.casino, d.partida_id);

var $cont = $('#pagosImpInmobiliarioContainer').empty();
IMP_PAGOS_IDX = 0;

if (Array.isArray(d.pagos) && d.pagos.length) {
  var seen = {};
  d.pagos.forEach(function(p){
    var key = String(p.partida_id||'')+'|'+String(p.cuota||'')+'|'+String(p.importe||'')+'|'+String(p.fecha_pres||'');
    if (seen[key]) return; seen[key] = 1;

    var $row = renderFilaPagoImpInmobiliario({
      id:          p.partida_id || '',
      partida:     p.partida    || '',
      cuota:       p.cuota      || '',
      importe:     p.importe    || '',
      fecha_pres:  p.fecha_pres || '',
      observacion: p.observacion|| ''
    });

    $row.find('input.pago-cuota').val(p.cuota || '');
    if (p.importe != null && p.importe !== '') {
      $row.find('input.pago-importe').val(p.importe);
    }
    $row.find('input.pago-fecha').val(p.fecha_pres || '');
    $row.find('textarea.pago-observacion').val(p.observacion || '');
  });


    }
  })
  .fail(function(xhr){
    console.error('[ImpInmobiliario editar] GET FAIL', xhr.status, xhr.responseText);
    alert('No se pudo cargar el registro de Impuesto Inmobiliario.');
  })
  .always(function(){
    IMP_SUPPRESS_CASINO_CHANGE = false;
  });
}




$(document).on('click','#ImpInmobiliario_partida_gestionar',function(){
  $('#modalImpInmobiliario_partida_gestionar').modal('show');
  cargarImpInmobiliario_partidaGestion();
});

function renderFilaImpInmobiliario_partida(d){
  var estado = d.estado
  ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
  : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';
  return ''+
  '<tr data-estado="'+d.estado+'" data-id="'+d.id+'" data-casino"'+escapeAttr(d.casino_id || '')+'"  data-partida="'+escapeAttr(d.partida || '')+'">'+
    '<td class="col-md-8">'+escapeHtml(d.partida || '')+'</td>'+
    '<td class="col-md-1">'+escapeHtml(d.casino_nombre|| '')+'</td>'+
    '<td class="col-md-2">'+estado+'</td>'+
    '<td class="col-md-1">'+
      '<button type="button" class="btn btn-sm btn-primary btn-editImpInmobiliario_partida" title="MODIFICAR PARTIDA">'+
        '<i class="fa fa-edit"></i>'+
      '</button> '+
    '</td>'+
  '</tr>';
}


function cargarImpInmobiliario_partidaGestion(casinoId){
  $('#dir-list-loading_ImpInmobiliario_partida').show();
  $('#tabla-ImpInmobiliario_partida').closest('.table-responsive').hide();
  $('#tabla-ImpInmobiliario_partida tbody').empty();

  var url = '/documentosContables/getImpInmobiliario_partida';

  $.getJSON(url, function(data){
    var rows = '';
    for (var i=0; i<data.length; i++){
      var d = data[i];
      rows += renderFilaImpInmobiliario_partida(d);
    }
    $('#tabla-ImpInmobiliario_partida tbody').html(rows);
    $('#dir-list-loading_ImpInmobiliario_partida').hide();
    $('#tabla-ImpInmobiliario_partida').closest('.table-responsive').show();
  }).fail(function(xhr){
    $('#dir-list-loading_ImpInmobiliario_partida').text('Error cargando elementos patentables.');
    console.error(xhr.responseText);
  });
}

$(document).on('click', '.btn-elimImpInmobiliario_partida', function(){
  const id = $(this).closest('tr').data('id');

  $('#btn-eliminarImpInmobiliario_partida').attr('data-id', id);
  $('#modalEliminarImpInmobiliario_partida').modal('show');
});

$(document).on('click', '.btn-editImpInmobiliario_partida', function(){
  const $tr  = $(this).closest('tr');
  const id = $(this).closest('tr').data('id');
  const partida = $(this).closest('tr').data('partida');
  var $sel = $('#ModifImpInmobiliario_partida_estado');

  $('#ModifId_ImpInmobiliario_partida').val(id);
  $('#ModifImpInmobiliario_partida_partida').val(partida);

  $('#modalModificarImpInmobiliario_partida').modal('show');
});

$(document).on('click', '#guardarModifRegistroImpInmobiliario_partida', function(){
  const $btn  = $(this).prop('disabled', true);
  const $form = $('#formModificarRegistroImpInmobiliario_partida');

  valid = true;
  valid = validarCampo("input[name='ModifImpInmobiliario_partida_partida']",'.col-md-12','El tipo es requerido.', valid);

  if(!valid) return 0;

  $.ajax({
    url: '/documentosContables/modificarImpInmobiliario_partida',
    method: 'POST',
    data: $form.serialize(),
    success: function(res){
      cargarImpInmobiliario_partidaGestion();
      $('#modalModificarImpInmobiliario_partida').modal('hide');
    },
    error: function(xhr){
      console.error(xhr.responseText || xhr);
    },
    complete: function(){
      $btn.prop('disabled', false);
    }
  });
});



$(document).on('click', '#btn-eliminarImpInmobiliario_partida', function () {
  const id  = $(this).attr('data-id');
  const $tr = $('#tabla-ImpInmobiliario_partida tr[data-id="'+id+'"]');

  $.get('/documentosContables/ImpInmobiliarioEliminarPartida/' + id, function (res) {
    if (res.ok) {
      $tr.remove();
      $('#modalEliminarImpInmobiliario_partida').modal('hide')
    } else {
    }
  }).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
  });
});

$(document).on('click','#guardarRegistroImpInmobiliario_partida',function(e){
  var $form = $('#formNuevoRegistroImpInmobiliario_partida');
  let valid=true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  valid = validarCampo("select[name='CasinoImpInmobiliario_partida']",".col-md-3",'El casino es requerido.',valid);
  valid = validarCampo("input[name='nombre_ImpInmobiliario_partida']",'.col-md-9','El nombre de la partida es requerido.', valid);

  if(!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: '/documentosContables/guardarRegistroImpInmobiliario_partida',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(res){
      setTimeout(() => $('#modalCargarImpInmobiliario_partida').modal('hide'), 1000);
    },
    error: function(xhr){

      $('#salir').next('.help-block.js-error').remove();
      $('#salir')
        .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
    }


  });
});



function cargarArchivosImpInmobiliarioLista(id){
  var $m = $('#modalArchivosAsociados');
  $m.data('ImpInmobiliarioId', id);
  var $list = $('#listaArchivos').empty().append('<div class="list-group-item">Cargando...</div>');

  $.getJSON('/documentosContables/archivosImpInmobiliario/'+id)
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
        var href   = '/documentosContables/visualizarArchivo/ImpInmobiliario/' + encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a   = $('<a target="_blank">').attr('href', href).text(iconoExt(nombre)+' '+nombre);
        var $del = $('<button type="button" data-id="'+fid+'" data-reg-id="'+id+'" data-scope="ImpInmobiliario"  class="btn btn-sm btn-danger btn-del-archivo-ImpInmobiliario" title="Quitar">')
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

$(document).on('click', '.btn-archivos-ImpInmobiliario', function(){
  var id = $(this).data('id');
  $('#tituloArchivos').text('Archivos del registro ImpInmobiliario');
  cargarArchivosImpInmobiliarioLista(id);
  $('#modalArchivosAsociados').modal('show');
});


$(document).on('click', '.btn-del-archivo-ImpInmobiliario', function(){
  const id = $(this).data('id');
  const regId = $(this).data('regId');
  const scope = $(this).data('scope');
  $('#btn-eliminarArchivo').attr('data-id', id);
  $('#modalEliminarArchivo').data({ id, regId, scope }).modal('show');
});


function resetFormImpInmobiliario(){
  const $f = $('#formNuevoRegistroImpInmobiliario');
  if ($f[0]) $f[0].reset();
  $f.find('.has-error').removeClass('has-error');
  $f.find('.help-block.js-error').remove();

  $('#partida_ImpInmobiliario')
    .removeData('selected-id')
    .html('<option value="">Elegí una partida</option>');

  $('#uploadsImpInmobiliarioContainer').empty();
  $('#uploadsImpInmobiliarioTable tbody').empty();
  $('#uploadsImpInmobiliarioWrap').hide();
  $('#fileNameImpInmobiliario').val('No se ha seleccionado ningún archivo');

  IMP_PARTIDAS_REQ_ID++;
}


function abrirModalImpInmobiliarioCrear(){
  $('#ImpInmobiliario_modo').val('create');
  $('#id_registroImpInmobiliario').val('');
  $('#modalCargarImpInmobiliario .modal-title').text('| NUEVO REGISTRO DE IMPUESTO INMOBILIARIO');
  $('#guardarRegistroImpInmobiliario').text('GENERAR');
  $('#modalCargarImpInmobiliario').modal('show');
}


$(document).on('click','#ImpInmobiliario_nuevo',function(){
  abrirModalImpInmobiliarioCrear();
});

$(document).on('click','.btn-edit-ImpInmobiliario',function(){
  var id = $(this).data('id');
  abrirModalImpInmobiliarioEditar(id);
});


$(document).on('click','#guardarRegistroImpInmobiliario',function(e){
  var $form = $('#formNuevoRegistroImpInmobiliario');
  let valid=true;
  var id   = $('#id_registroImpInmobiliario').val() || '';
  var modo = $('#ImpInmobiliario_modo').val() || 'create';

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

valid = validarCampo("select[name='casinoImpInmobiliario']",'.col-md-6','El casino es requerido.', valid);
valid = validarCampo("input[name='fecha_ImpInmobiliario']",'.col-md-6','La fecha es requerida.', valid);


$('#pagosImpInmobiliarioContainer .pago-row').each(function(){
  var $row = $(this);
  var $cuo = $row.find('input.pago-cuota');
  var $imp = $row.find('input.pago-importe');
  var $fec = $row.find('input.pago-fecha');

  var vCuo = ($cuo.val() || '').trim();
  var vImp = ($imp.val() || '').trim();
  var vFec = ($fec.val() || '').trim();

  if (!vCuo && !vImp && !vFec) return;

  valid = validarCampoNum($cuo, '.col-md-1', 'La cuota es requerida.', valid);
  valid = validarCampoNum($imp, '.col-md-2', 'El importe es requerido.', valid);
  valid = validarCampo($fec,  '.col-md-2', 'La fecha es requerida.',   valid);
});


if(!valid) return 0;

let formElem = $form[0];
let formData = new FormData(formElem);

  var fd = new FormData();

    $form.serializeArray().forEach(function(p){
      fd.append(p.name, p.value);
    });

    $('#uploadsImpInmobiliarioContainer input[type="file"][name="uploadImpInmobiliario[]"]').each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append('uploadImpInmobiliario[]', files[i]);
      }
    });

    var cur = document.getElementById('uploadImpInmobiliario');
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append('uploadImpInmobiliario[]', cur.files[j]);
      }
    }

    var url = (modo === 'edit')
      ? ('/documentosContables/actualizarImpInmobiliario/'+id)
      : '/documentosContables/guardarImpInmobiliario';

$.ajax({
  url: url,
  method: 'POST',
  data: formData,
  processData: false,
  contentType: false,
  success: function(res){
    cargarImpInmobiliario({
      page:     1,
      perPage:  $('#herramientasPaginacionImpInmobiliario').getPageSize(),
      casino:   $('#FCasinoImpInmobiliario').val(),
      desde: $('#fecha_ImpInmobiliarioDesde').val(),
      hasta: $('#fecha_ImpInmobiliarioHasta').val()
    });
    setTimeout(() => $('#modalCargarImpInmobiliario').modal('hide'), 1000);
    resetFormImpInmobiliario();
  },
  error: function(xhr){

    $('#salir').next('.help-block.js-error').remove();
    $('#salir')
      .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
    console.error("Error al guardar:", xhr);
  }


});
});

$('#btn-buscarImpInmobiliario').on('click', function(e){
  e.preventDefault();
  cargarImpInmobiliario({
    page:    1,
    perPage: $('#herramientasPaginacionImpInmobiliario').getPageSize(),
    casino:  $('#FCasinoImpInmobiliario').val(),
    desde: $('#fecha_ImpInmobiliarioDesde').val(),
    hasta: $('#fecha_ImpInmobiliarioHasta').val()
  });
});

function clickIndiceImpInmobiliario(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarImpInmobiliario({
    page:    pageNumber,
    perPage: pageSize,
    casino:  $('#FCasinoImpInmobiliario').val(),
    desde: $('#fecha_ImpInmobiliarioDesde').val(),
    hasta: $('#fecha_ImpInmobiliarioHasta').val()
  });
}

function cargarImpInmobiliario({ page = 1, perPage = 10, casino,desde,hasta }) {

  $.ajax({
    url: '/documentosContables/ultimasImpInmobiliario',
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta
    },
    dataType: 'json',
    success(res) {
      $('#cuerpoTablaImpInmobiliario').empty();

      res.registros.forEach(item => {
        $('#cuerpoTablaImpInmobiliario').append(generarFilaImpInmobiliario(item));
      });

      $('#herramientasPaginacionImpInmobiliario').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceImpInmobiliario
      );
      $('#herramientasPaginacionImpInmobiliario').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceImpInmobiliario
      );

    },
    error(err) {
      console.error('Error cargando ImpInmobiliario:', err);
    }
  });
}

function generarFilaImpInmobiliario(ImpInmobiliario,controlador) {
  const fila = $('<tr>').attr('id', ImpInmobiliario.id_registroImpInmobiliario);
  const fecha = convertirMesAno(ImpInmobiliario.fecha_ImpInmobiliario) || '-';
  const casino = ImpInmobiliario.casino || '-';

  fila
    .append($('<td>').addClass('col-xs-4').html(fecha))
    .append($('<td>').addClass('col-xs-4').text(casino))



  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-4 d-flex flex-wrap');

  const btnView = $('<a>')
    .addClass('btn btn-success btn-sm btn-verRegImpInmobiliario')
    .attr('id',ImpInmobiliario.id_registroImpInmobiliario)
    .attr('target', '_blank')
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'VER REGISTRO DE IMPUESTO INMOBILIARIO')
    .append($('<i>').addClass('fa fa-fw fa-eye'));
  tdAcc.append(btnView);

  if (ImpInmobiliario.tiene_archivos) {
    const btnFiles = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-archivos-ImpInmobiliario')
      .attr('type','button')
      .attr('data-id', ImpInmobiliario.id_registroImpInmobiliario)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER ARCHIVOS ASOCIADOS')
      .append($('<i>').addClass('fa fa-file'));
    tdAcc.append(btnFiles);
  }
    var btnEdit = $('<button>')
      .addClass('btn btn-info btn-sm mr-1 btn-edit-ImpInmobiliario')
      .attr('type','button')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('data-id', ImpInmobiliario.id_registroImpInmobiliario)
      .attr('title','EDITAR')
      .append($('<i>').addClass('fa fa-edit'));
    tdAcc.append(btnEdit);



  const btnDelete = $('<button>')
  .addClass('btn btn-danger btn-sm btn-deleteImpInmobiliario')
  .attr('id',ImpInmobiliario.id_registroImpInmobiliario)
  .attr('data-toggle','tooltip')
  .attr('data-placement','bottom')
  .attr('title','ELIMINAR REGISTRO DE IMPUESTO INMOBILIARIO')
  .append($('<i>').addClass('fa fa-trash'));
tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '.btn-deleteImpInmobiliario', function(){
  const id = $(this).attr('id');
  $('#btn-eliminarImpInmobiliario').attr('data-id', id);
  $('#modalEliminarImpInmobiliario').modal('show');
});

$('#btn-eliminarImpInmobiliario').on('click', function(){
  const id = $(this).attr('data-id');

  $.ajax({
    url: `/documentosContables/eliminarImpInmobiliario/${id}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarImpInmobiliario').modal('hide');
      cargarImpInmobiliario({
        page:     $('#herramientasPaginacionImpInmobiliario').getCurrentPage(),
        perPage:  $('#herramientasPaginacionImpInmobiliario').getPageSize(),
        casino:   $('#FCasinoImpInmobiliario').val(),
        desde: $('#fecha_ImpInmobiliarioDesde').val(),
        hasta: $('#fecha_ImpInmobiliarioHasta').val()
      });
    } else {
    }
  }).fail(() => {
  });
});



$('#btn-descargarImpInmobiliarioExcel').on('click',function(e){

  $('#collapseDescargarImpInmobiliario .has-error').removeClass('has-error');
  $('#collapseDescargarImpInmobiliario .js-error').remove();

  const casino = $('#FCasinoImpInmobiliario').val() ? $('#FCasinoImpInmobiliario').val() : 4;
  const desde = $('#fecha_ImpInmobiliarioDesde').val();
  const hasta = $('#fecha_ImpInmobiliarioHasta').val();
  let   valid  = true;

  if (!casino) {
    $('#DCasinoImpInmobiliario').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_ImpInmobiliarioDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  if(casino!=4) {
    window.location.href = `/documentosContables/descargarImpInmobiliarioXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  }else{
    window.location.href = `/documentosContables/descargarImpInmobiliarioXlsxTodos?desde=${desde}&hasta=${hasta}`;

  }

});

$('#btn-descargarImpInmobiliarioCsv').on('click', function () {
  const casino = $('#FCasinoImpInmobiliario').val() ? $('#FCasinoImpInmobiliario').val() : 4;
  const desde = $('#fecha_ImpInmobiliarioDesde').val();
  const hasta = $('#fecha_ImpInmobiliarioHasta').val();
  let valid = true;
  $('#collapseDescargarImpInmobiliario .has-error').removeClass('has-error');
  $('#collapseDescargarImpInmobiliario .js-error').remove();

  if (!casino) {
    $('#DCasinoImpInmobiliario').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El casino es requerido.</span>');
    valid = false;
  }

  if(desde>hasta && hasta){
    $('#fecha_ImpInmobiliarioDescDesde').closest('.col-lg-3')
      .addClass('has-error')
      .append('<span class="help-block js-error text-danger">El intérvalo de inicio no puede ser posterior al de fin.</span>');
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarImpInmobiliarioCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

// GENERALESSSSSS


$(function(){
  $('[data-js-tabs] a').on('click', function(e){
    e.preventDefault();

    const selector = $(this).data('js-tab');

    $('[data-js-tabs] a').removeClass('active');
    $(this).addClass('active');

    $('[id^="pant_"]').hide();
    $(selector).show();

    if (selector === '#pant_iva') {
      cargarIva({ page: 1, perPage: 10});

      $(document).on('click','#btnPickIva',function(){
          $('#uploadIva').click();
        });

        $(document).on('change','#uploadIva',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsIvaContainer');
          renderUploadsIva();
          var $new = $('<input type="file" id="uploadIva" name="uploadIva[]" multiple style="display:none;">');
          $('#btnPickIva').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsIva(){
          var $tbody = $('#uploadsIvaTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsIvaContainer input[type=file][name="uploadIva[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsIvaWrap').css('display', total ? '' : 'none');
          $('#fileNameIva').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsIvaTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsIvaContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsIva();
        });


              attachYYYYMMFormatter('#fecha_ivaDesde');
              attachYYYYMMFormatter('#fecha_ivaHasta');
              attachYYYYMMFormatter('#fecha_iva');
              attachYYYYMMDDFormatter('#fecha_ivaPres');

              $('#fechaIva').datetimepicker({
                language:  'es',
                todayBtn:  1,
                autoclose: 1,
                todayHighlight: 1,
                format: 'yyyy-mm',
                pickerPosition: "bottom-left",
                startView: 3,
                minView: 3,
                ignoreReadonly: true,
                timePicker: false,
                container:$('main section')
              });
              $('#fechaIvaPres').datetimepicker({
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
              $('#fechaIvaDescDesde').datetimepicker({
                language:  'es',
                todayBtn:  1,
                autoclose: 1,
                todayHighlight: 1,
                format: 'yyyy-mm',
                pickerPosition: "top-left",
                startView: 3,
                minView: 3,
                ignoreReadonly: true,
                timePicker: false,
                container:$('main section')
              });
              $('#fechaIvaDescHasta').datetimepicker({
                language:  'es',
                todayBtn:  1,
                autoclose: 1,
                todayHighlight: 1,
                format: 'yyyy-mm',
                pickerPosition: "top-left",
                startView: 3,
                minView: 3,
                ignoreReadonly: true,
                timePicker: false,
                container:$('main section')
              });
              $('#fechaIvaDesde').datetimepicker({
                language:  'es',
                todayBtn:  1,
                autoclose: 1,
                todayHighlight: 1,
                format: 'yyyy-mm',
                pickerPosition: "top-left",
                startView: 3,
                minView: 3,
                ignoreReadonly: true,
                timePicker: false,
                container:$('main section')
              });
              $('#fechaIvaHasta').datetimepicker({
                language:  'es',
                todayBtn:  1,
                autoclose: 1,
                todayHighlight: 1,
                format: 'yyyy-mm',
                pickerPosition: "top-left",
                startView: 3,
                minView: 3,
                ignoreReadonly: true,
                timePicker: false,
                container:$('main section')
              });
        }
    else if(selector === "#pant_iibb"){
      cargariibb({ page: 1, perPage: 10});
      $(document).on('click','#btnPickiibb',function(){
          $('#uploadiibb').click();
        });

        $(document).on('change','#uploadiibb',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsiibbContainer');
          renderUploadsiibb();
          var $new = $('<input type="file" id="uploadiibb" name="uploadiibb[]" multiple style="display:none;">');
          $('#btnPickiibb').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsiibb(){
          var $tbody = $('#uploadsiibbTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsiibbContainer input[type=file][name="uploadiibb[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsiibbWrap').css('display', total ? '' : 'none');
          $('#fileNameiibb').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsiibbTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsiibbContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsiibb();
        });

      attachYYYYMMFormatter('#fecha_iibbDesde');
      attachYYYYMMFormatter('#fecha_iibbHasta');
      attachYYYYMMFormatter('#fecha_iibb');
      attachYYYYMMDDFormatter('#fecha_iibbPres');

      $('#fechaiibbDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaiibbDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaiibbDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaiibbHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaiibb').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaiibbPres').datetimepicker({
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
      (function () {
        let contador = 0;
        $(document).on('iibb:set-contador', function(e, n){ contador = Number(n)||0; });
        $(document)
          .off('click.iibb', '#agregar-bloque-iibb-cargar')
          .on('click.iibb', '#agregar-bloque-iibb-cargar', function (e) {
            e.preventDefault();
            contador++;

            const montoId = `monto_iibb_${contador}`;
            const aliId   = `alicuota_iibb_${contador}`;
            const impId   = `imp_iibb_${contador}`;

            const bloque = `
              <div class="bases-iibb-cargar" data-id="${contador}">
                <div class="row">
                  <div class="col-md-3"><h5>Base imponible...</h5></div>
                  <div class="col-md-3"><h5>Monto</h5></div>
                  <div class="col-md-2"><h5>Alicuota (%)</h5></div>
                  <div class="col-md-3"><h5>Impuesto Determinado</h5></div>
                  <div class="col-md-1"></div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <textarea name="base[]" class="form-control" rows="2" placeholder="Completar con el concepto" maxlength="4000"></textarea>
                  </div>
                  <div class="col-md-3">
                    <input type="text" id="${montoId}" name="monto[]" class="form-control js-num-ar iibb-monto" placeholder="$" inputmode="decimal">
                  </div>
                  <div class="col-md-2">
                    <input type="text" id="${aliId}" name="alicuota[]" class="form-control js-num-ar iibb-ali" placeholder="%" inputmode="decimal">
                  </div>
                  <div class="col-md-3">
                    <input type="text" id="${impId}" name="impuesto[]" class="form-control js-num-ar iibb-imp" inputmode="decimal" readonly>
                  </div>
                  <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm eliminar-bloque-iibb-cargar">X</button>
                  </div>
                </div>
              </div>`;

            $('#contenedor-inputs-iibb-cargar').append(bloque);

            instalarNumeroFlexibleAR(`#${montoId}, #${aliId}, #${impId}`, { decimales: 2 });

            instalarAutoImpuestoAR({
              base:     `#${montoId}`,
              alicuota: `#${aliId}`,
              impuesto: `#${impId}`,
              decImp:   2,
              aliEsPorcentaje: true
            });

            $(`#${montoId}`).trigger('input');
          });

        $(document)
          .off('click.iibb', '.eliminar-bloque-iibb-cargar')
          .on('click.iibb', '.eliminar-bloque-iibb-cargar', function () {
            $(this).closest('.bases-iibb-cargar').remove();
            const $firstImp = $('#contenedor-inputs-iibb-cargar .iibb-imp').first();
            if ($firstImp.length) $firstImp.trigger('input');
            else $('#total_impuesto_iibb').val(formatoAR(0,2)).data('num',0).trigger('num:changed',[0]);
          });

        $('#modalCargariibb').on('hidden.bs.modal', function () {
          $('#contenedor-inputs-iibb-cargar').empty();
          contador = 0;
          const f = document.getElementById('formNuevoRegistroiibb');
          if (f) f.reset();
          $('#total_impuesto_iibb').val(formatoAR(0,2)).data('num',0);
        });
      })();



      }
    else if(selector === "#pant_drei"){
      cargarDREI({ page: 1, perPage: 10});
      $(document).on('click','#btnPickDREI',function(){
          $('#uploadDREI').click();
        });

        $(document).on('change','#uploadDREI',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsDREIContainer');
          renderUploadsDREI();
          var $new = $('<input type="file" id="uploadDREI" name="uploadDREI[]" multiple style="display:none;">');
          $('#btnPickDREI').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsDREI(){
          var $tbody = $('#uploadsDREITable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsDREIContainer input[type=file][name="uploadDREI[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsDREIWrap').css('display', total ? '' : 'none');
          $('#fileNameDREI').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsDREITable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsDREIContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsDREI();
        });

      $(document).ready(function () {
        $('#casinoDREI').on('change', function () {
            var tipo = $(this).val();

            $('#formNuevoRegistroDREI .has-error').removeClass('has-error');
            $('#formNuevoRegistroDREI .help-block.js-error').remove();

            $('.formulario-DREI').hide();

            if (tipo == '1') {
                $('#formularioMEL').show();
            } else if (tipo == '2') {
                $('#formularioCSF').show();
            } else if (tipo == '3') {
                $('#formularioRO').show();
            }
        });
      });


      attachYYYYMMFormatter('#fecha_DREIDesde');
      attachYYYYMMFormatter('#fecha_DREIHasta');
      attachYYYYMMDDFormatter('#fecha_DREIPres');
      attachYYYYMMFormatter('#fecha_DREI');
      attachYYYYMMDDFormatter('#fecha_DREIVenc');

      $('#fechaDREI').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDREIPres').datetimepicker({
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
      $('#fechaDREIVenc').datetimepicker({
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
      $('#fechaDREIDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDREIHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDREIDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDREIDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_tgi"){
      cargarTGI({ page: 1, perPage: 10});
      $(document).on('click','#btnPickTGI',function(){
          $('#uploadTGI').click();
        });

        $(document).on('change','#uploadTGI',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsTGIContainer');
          renderUploadsTGI();
          var $new = $('<input type="file" id="uploadTGI" name="uploadTGI[]" multiple style="display:none;">');
          $('#btnPickTGI').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsTGI(){
          var $tbody = $('#uploadsTGITable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsTGIContainer input[type=file][name="uploadTGI[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsTGIWrap').css('display', total ? '' : 'none');
          $('#fileNameTGI').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsTGITable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsTGIContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsTGI();
        });


      $(document).on('change', '#casinoTGI', function(){
        var sid = $('#partida_TGI').data('selected-id');
        if ($('#TGI_modo').val() === 'edit' && sid != null) return;
        cargarPartidasTGI($(this).val());
      });




      attachYYYYMMFormatter('#fecha_TGI');

      attachYYYYMMFormatter('#fecha_TGIDesde');
      attachYYYYMMFormatter('#fecha_TGIHasta');

      $('#fechaTGI').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaTGIDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaTGIHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_imp_ap_ol"){
      cargarIMP_AP_OL({ page: 1, perPage: 10});
      $(document).on('click','#btnPickIMP_AP_OL',function(){
          $('#uploadIMP_AP_OL').click();
        });

        $(document).on('change','#uploadIMP_AP_OL',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsIMP_AP_OLContainer');
          renderUploadsIMP_AP_OL();
          var $new = $('<input type="file" id="uploadIMP_AP_OL" name="uploadIMP_AP_OL[]" multiple style="display:none;">');
          $('#btnPickIMP_AP_OL').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsIMP_AP_OL(){
          var $tbody = $('#uploadsIMP_AP_OLTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsIMP_AP_OLContainer input[type=file][name="uploadIMP_AP_OL[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsIMP_AP_OLWrap').css('display', total ? '' : 'none');
          $('#fileNameIMP_AP_OL').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsIMP_AP_OLTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsIMP_AP_OLContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsIMP_AP_OL();
        });


      attachYYYYMMFormatter('#fecha_IMP_AP_OLDesde');
      attachYYYYMMFormatter('#fecha_IMP_AP_OLHasta');
      attachYYYYMMFormatter('#fecha_IMP_AP_OL');
      attachYYYYMMDDFormatter('#fecha_IMP_AP_OLPres')
      attachYYYYMMDDFormatter('#fecha_pago_IMP_AP_OL')


      $('#fechaIMP_AP_OL').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaIMP_AP_OLPres').datetimepicker({
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
      $('#fecha_pagoIMP_AP_OL').datetimepicker({
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
      $('#fechaIMP_AP_OLDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaIMP_AP_OLHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaIMP_AP_OLDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaIMP_AP_OLDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_imp_ap_mtm"){
      cargarIMP_AP_MTM({ page: 1, perPage: 10});
      $(document).on('click','#btnPickIMP_AP_MTM',function(){
          $('#uploadIMP_AP_MTM').click();
        });

        $(document).on('change','#uploadIMP_AP_MTM',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsIMP_AP_MTMContainer');
          renderUploadsIMP_AP_MTM();
          var $new = $('<input type="file" id="uploadIMP_AP_MTM" name="uploadIMP_AP_MTM[]" multiple style="display:none;">');
          $('#btnPickIMP_AP_MTM').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsIMP_AP_MTM(){
          var $tbody = $('#uploadsIMP_AP_MTMTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsIMP_AP_MTMContainer input[type=file][name="uploadIMP_AP_MTM[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsIMP_AP_MTMWrap').css('display', total ? '' : 'none');
          $('#fileNameIMP_AP_MTM').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsIMP_AP_MTMTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsIMP_AP_MTMContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsIMP_AP_MTM();
        });


      attachYYYYMMFormatter('#fecha_IMP_AP_MTMDesde');
      attachYYYYMMFormatter('#fecha_IMP_AP_MTMHasta');
      attachYYYYMMFormatter('#fecha_IMP_AP_MTM');
      attachYYYYMMDDFormatter('#fecha_IMP_AP_MTMPres')
      attachYYYYMMDDFormatter('#fecha_pago_IMP_AP_MTM')


      $('#fechaIMP_AP_MTM').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaIMP_AP_MTMPres').datetimepicker({
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
      $('#fecha_pagoIMP_AP_MTM').datetimepicker({
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
      $('#fechaIMP_AP_MTMDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaIMP_AP_MTMHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaIMP_AP_MTMDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaIMP_AP_MTMDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_deuda"){
      cargarDeudaEstado({ page: 1, perPage: 10});

$(document).on('click','#btnPickDeudaEstado',function(){
          $('#uploadDeudaEstado').click();
        });

        $(document).on('change','#uploadDeudaEstado',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsDeudaEstadoContainer');
          renderUploadsDeudaEstado();
          var $new = $('<input type="file" id="uploadDeudaEstado" name="uploadDeudaEstado[]" multiple style="display:none;">');
          $('#btnPickDeudaEstado').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsDeudaEstado(){
          var $tbody = $('#uploadsDeudaEstadoTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsDeudaEstadoContainer input[type=file][name="uploadDeudaEstado[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsDeudaEstadoWrap').css('display', total ? '' : 'none');
          $('#fileNameDeudaEstado').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsDeudaEstadoTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsDeudaEstadoContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsDeudaEstado();
        });


      $(document).ready(function () {
        $('#regIncumDeudaEstado').on('change', function () {
            var tipo = $(this).val();

            $('.formulario-DeudaEstado').hide();

            if (tipo == '1') {
                $('#formularioIncumDeudaEstado').show();
            }
        });
      });

      attachYYYYMMFormatter('#fecha_DeudaEstadoDesde');
      attachYYYYMMFormatter('#fecha_DeudaEstadoHasta');
      attachYYYYMMFormatter('#fecha_DeudaEstado');
      attachYYYYMMDDFormatter('#fecha_DeudaEstadoPres');



      $('#fechaDeudaEstado').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDeudaEstadoPres').datetimepicker({
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
      $('#fecha_pagoDeudaEstado').datetimepicker({
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
      $('#fechaDeudaEstadoDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDeudaEstadoHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDeudaEstadoDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDeudaEstadoDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_pagos_mesas"){
      cargarPagosMayoresMesas({ page: 1, perPage: 10});

$(document).on('click','#btnPickPagosMayoresMesas',function(){
          $('#uploadPagosMayoresMesas').click();
        });

        $(document).on('change','#uploadPagosMayoresMesas',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsPagosMayoresMesasContainer');
          renderUploadsPagosMayoresMesas();
          var $new = $('<input type="file" id="uploadPagosMayoresMesas" name="uploadPagosMayoresMesas[]" multiple style="display:none;">');
          $('#btnPickPagosMayoresMesas').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsPagosMayoresMesas(){
          var $tbody = $('#uploadsPagosMayoresMesasTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsPagosMayoresMesasContainer input[type=file][name="uploadPagosMayoresMesas[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsPagosMayoresMesasWrap').css('display', total ? '' : 'none');
          $('#fileNamePagosMayoresMesas').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsPagosMayoresMesasTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsPagosMayoresMesasContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsPagosMayoresMesas();
        });
        attachYYYYMMFormatter('#fecha_PagosMayoresMesasDesde');
        attachYYYYMMFormatter('#fecha_PagosMayoresMesasHasta');
        attachYYYYMMFormatter('#fecha_PagosMayoresMesas');

      $('#fechaPagosMayoresMesas').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPagosMayoresMesasPres').datetimepicker({
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
      $('#fecha_pagoPagosMayoresMesas').datetimepicker({
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
      $('#fechaPagosMayoresMesasDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPagosMayoresMesasHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPagosMayoresMesasDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPagosMayoresMesasDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_oper"){
      cargarReporteYLavado({ page: 1, perPage: 10});
      $(document).on('click','#btnPickReporteYLavado',function(){
          $('#uploadReporteYLavado').click();
        });

        $(document).on('change','#uploadReporteYLavado',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsReporteYLavadoContainer');
          renderUploadsReporteYLavado();
          var $new = $('<input type="file" id="uploadReporteYLavado" name="uploadReporteYLavado[]" multiple style="display:none;">');
          $('#btnPickReporteYLavado').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsReporteYLavado(){
          var $tbody = $('#uploadsReporteYLavadoTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsReporteYLavadoContainer input[type=file][name="uploadReporteYLavado[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsReporteYLavadoWrap').css('display', total ? '' : 'none');
          $('#fileNameReporteYLavado').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsReporteYLavadoTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsReporteYLavadoContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsReporteYLavado();
        });

        attachYYYYMMFormatter('#fecha_ReporteYLavado');
        attachYYYYMMFormatter('#fecha_ReporteYLavadoDesde');
        attachYYYYMMFormatter('#fecha_ReporteYLavadoHasta');

      $('#fechaReporteYLavado').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaReporteYLavadoPres').datetimepicker({
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
      $('#fecha_pagoReporteYLavado').datetimepicker({
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
      $('#fechaReporteYLavadoDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaReporteYLavadoHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaReporteYLavadoDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaReporteYLavadoDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_registros"){
      cargarRegistrosContables({ page: 1, perPage: 10});
      $(document).on('click','#btnPickRegistrosContables',function(){
            $('#uploadRegistrosContables').click();
          });

          $(document).on('change','#uploadRegistrosContables',function(){
            if(!this.files || !this.files.length) return;
            var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
            $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsRegistrosContablesContainer');
            renderUploadsRegistrosContables();
            var $new = $('<input type="file" id="uploadRegistrosContables" name="uploadRegistrosContables[]" multiple style="display:none;">');
            $('#btnPickRegistrosContables').closest('.input-group').append($new);
          });

          function humanSize(n){
            if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
            if(n >= 1024) return (n/1024).toFixed(0)+' KB';
            return n+' B';
          }

          function renderUploadsRegistrosContables(){
            var $tbody = $('#uploadsRegistrosContablesTable tbody').empty();
            var total = 0, row = 1;

            $('#uploadsRegistrosContablesContainer input[type=file][name="uploadRegistrosContables[]"]').each(function(ix){
              var $inp = $(this);
              var gid = $inp.attr('data-group');
              var files = this.files || [];
              for(var i=0;i<files.length;i++){
                var f = files[i];
                total++;
                var $tr = $('<tr></tr>');
                $tr.append('<td>'+ (row++) +'</td>');
                $tr.append('<td>'+ f.name +'</td>');
                $tr.append('<td>'+ humanSize(f.size) +'</td>');
                var $btn = $(
                              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                                '<i class="fa fa-trash"></i>' +
                              '</button>'
                            ).attr('data-group', gid).attr('data-idx', i);
                $tr.append($('<td></td>').append($btn));
                $tbody.append($tr);
              }
            });

            $('#uploadsRegistrosContablesWrap').css('display', total ? '' : 'none');
            $('#fileNameRegistrosContables').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
          }

          $(document).on('click','#uploadsRegistrosContablesTable .btn-danger',function(){
            var gid = $(this).attr('data-group');
            var idx = parseInt($(this).attr('data-idx'),10);
            var $inp = $('#uploadsRegistrosContablesContainer input[type=file][data-group="'+gid+'"]');
            if(!$inp.length) return;

            var dt = new DataTransfer();
            var files = $inp[0].files;
            for(var i=0;i<files.length;i++){
              if(i !== idx) dt.items.add(files[i]);
            }
            $inp[0].files = dt.files;
            if($inp[0].files.length === 0) $inp.remove();
            renderUploadsRegistrosContables();
          });

          attachYYYYMMFormatter('#fecha_RegistrosContables');
          attachYYYYMMFormatter('#fecha_RegistrosContablesDesde');
          attachYYYYMMFormatter('#fecha_RegistrosContablesHasta');

      $('#fechaRegistrosContables').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaRegistrosContablesPres').datetimepicker({
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
      $('#fecha_pagoRegistrosContables').datetimepicker({
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
      $('#fechaRegistrosContablesDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaRegistrosContablesHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaRegistrosContablesDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaRegistrosContablesDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_aportes"){
      cargarAportesPatronales({ page: 1, perPage: 10});

$(document).on('click','#btnPickAportesPatronales',function(){
          $('#uploadAportesPatronales').click();
        });

        $(document).on('change','#uploadAportesPatronales',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsAportesPatronalesContainer');
          renderUploadsAportesPatronales();
          var $new = $('<input type="file" id="uploadAportesPatronales" name="uploadAportesPatronales[]" multiple style="display:none;">');
          $('#btnPickAportesPatronales').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsAportesPatronales(){
          var $tbody = $('#uploadsAportesPatronalesTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsAportesPatronalesContainer input[type=file][name="uploadAportesPatronales[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsAportesPatronalesWrap').css('display', total ? '' : 'none');
          $('#fileNameAportesPatronales').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsAportesPatronalesTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsAportesPatronalesContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsAportesPatronales();
        });

        attachYYYYMMFormatter('#fecha_AportesPatronalesDesde');
        attachYYYYMMFormatter('#fecha_AportesPatronalesHasta');
        attachYYYYMMFormatter('#fecha_AportesPatronales');
        attachYYYYMMDDFormatter('#fecha_AportesPatronalesPres');
        attachYYYYMMDDFormatter('#fecha_pago_AportesPatronales');

      $('#fechaAportesPatronales').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaAportesPatronalesPres').datetimepicker({
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
      $('#fecha_pagoAportesPatronales').datetimepicker({
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
      $('#fechaAportesPatronalesDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaAportesPatronalesHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaAportesPatronalesDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaAportesPatronalesDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_promoticket"){
      cargarPromoTickets({ page: 1, perPage: 10});

$(document).on('click','#btnPickPromoTickets',function(){
          $('#uploadPromoTickets').click();
        });

        $(document).on('change','#uploadPromoTickets',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsPromoTicketsContainer');
          renderUploadsPromoTickets();
          var $new = $('<input type="file" id="uploadPromoTickets" name="uploadPromoTickets[]" multiple style="display:none;">');
          $('#btnPickPromoTickets').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsPromoTickets(){
          var $tbody = $('#uploadsPromoTicketsTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsPromoTicketsContainer input[type=file][name="uploadPromoTickets[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsPromoTicketsWrap').css('display', total ? '' : 'none');
          $('#fileNamePromoTickets').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsPromoTicketsTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsPromoTicketsContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsPromoTickets();
        });

        attachYYYYMMFormatter('#fecha_PromoTicketsDesde');
        attachYYYYMMFormatter('#fecha_PromoTicketsHasta');
        attachYYYYMMFormatter('#fecha_PromoTickets');

      $('#fechaPromoTickets').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPromoTicketsPres').datetimepicker({
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
      $('#fecha_pagoPromoTickets').datetimepicker({
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
      $('#fechaPromoTicketsDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPromoTicketsHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPromoTicketsDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPromoTicketsDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_pozos_acumulados"){
      cargarPozosAcumuladosLinkeados({ page: 1, perPage: 10});
      $(document).on('click','#btnPickPozosAcumuladosLinkeados',function(){
          $('#uploadPozosAcumuladosLinkeados').click();
        });

        $(document).on('change','#uploadPozosAcumuladosLinkeados',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsPozosAcumuladosLinkeadosContainer');
          renderUploadsPozosAcumuladosLinkeados();
          var $new = $('<input type="file" id="uploadPozosAcumuladosLinkeados" name="uploadPozosAcumuladosLinkeados[]" multiple style="display:none;">');
          $('#btnPickPozosAcumuladosLinkeados').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsPozosAcumuladosLinkeados(){
          var $tbody = $('#uploadsPozosAcumuladosLinkeadosTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsPozosAcumuladosLinkeadosContainer input[type=file][name="uploadPozosAcumuladosLinkeados[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsPozosAcumuladosLinkeadosWrap').css('display', total ? '' : 'none');
          $('#fileNamePozosAcumuladosLinkeados').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsPozosAcumuladosLinkeadosTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsPozosAcumuladosLinkeadosContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsPozosAcumuladosLinkeados();
        });

        attachYYYYMMFormatter('#fecha_PozosAcumuladosLinkeados');
        attachYYYYMMFormatter('#fecha_PozosAcumuladosLinkeadosHasta');
        attachYYYYMMFormatter('#fecha_PozosAcumuladosLinkeadosDesde');

      $('#fechaPozosAcumuladosLinkeados').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPozosAcumuladosLinkeadosPres').datetimepicker({
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
      $('#fecha_pagoPozosAcumuladosLinkeados').datetimepicker({
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
      $('#fechaPozosAcumuladosLinkeadosDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPozosAcumuladosLinkeadosHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPozosAcumuladosLinkeadosDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPozosAcumuladosLinkeadosDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_contrib_ente"){
      cargarContribEnteTuristico({ page: 1, perPage: 10});
      $(document).on('click','#btnPickContribEnteTuristico',function(){
          $('#uploadContribEnteTuristico').click();
        });

        $(document).on('change','#uploadContribEnteTuristico',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsContribEnteTuristicoContainer');
          renderUploadsContribEnteTuristico();
          var $new = $('<input type="file" id="uploadContribEnteTuristico" name="uploadContribEnteTuristico[]" multiple style="display:none;">');
          $('#btnPickContribEnteTuristico').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsContribEnteTuristico(){
          var $tbody = $('#uploadsContribEnteTuristicoTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsContribEnteTuristicoContainer input[type=file][name="uploadContribEnteTuristico[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsContribEnteTuristicoWrap').css('display', total ? '' : 'none');
          $('#fileNameContribEnteTuristico').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsContribEnteTuristicoTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsContribEnteTuristicoContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsContribEnteTuristico();
        });

        attachYYYYMMFormatter('#fecha_ContribEnteTuristicoDesde');
        attachYYYYMMFormatter('#fecha_ContribEnteTuristicoHasta');
        attachYYYYMMFormatter('#fecha_ContribEnteTuristico');

        attachYYYYMMDDFormatter('#fecha_venc_ContribEnteTuristico');
        attachYYYYMMDDFormatter('#fecha_ContribEnteTuristicoPres');

      $('#fechaContribEnteTuristico').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaContribEnteTuristicoPres').datetimepicker({
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
      $('#fecha_vencContribEnteTuristico').datetimepicker({
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
      $('#fechaContribEnteTuristicoDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaContribEnteTuristicoHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaContribEnteTuristicoDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaContribEnteTuristicoDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_rrhh"){
      cargarRRHH({ page: 1, perPage: 10});
      $(document).on('click','#btnPickRRHH',function(){
          $('#uploadRRHH').click();
        });

        $(document).on('change','#uploadRRHH',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsRRHHContainer');
          renderUploadsRRHH();
          var $new = $('<input type="file" id="uploadRRHH" name="uploadRRHH[]" multiple style="display:none;">');
          $('#btnPickRRHH').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsRRHH(){
          var $tbody = $('#uploadsRRHHTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsRRHHContainer input[type=file][name="uploadRRHH[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsRRHHWrap').css('display', total ? '' : 'none');
          $('#fileNameRRHH').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsRRHHTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsRRHHContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsRRHH();
        });

        $(document).ready(function () {
          function setOfertadoPorCasino(tipo){
            if (tipo == '1') {
              $('#ofertado_adjudicado_RRHH').val(194);
            } else if (tipo == '2') {
              $('#ofertado_adjudicado_RRHH').val(495);
            } else if (tipo == '3') {
              $('#ofertado_adjudicado_RRHH').val(1389);
            } else {
              $('#ofertado_adjudicado_RRHH').val('');
            }
          }

          function cargarPersonalInicio(casinoId){
            if(!casinoId){
              $('#personal_inicio_RRHH').val('');
              return;
            }
            $.get('/documentosContables/ultimosPersonalRRHH/' + casinoId, function(data){
              var val = null;

              if (data == null) {
                val = '';
              } else if (typeof data === 'object') {
                if ('personal_inicio' in data) val = data.personal_inicio;
                else if ('value' in data)     val = data.value;
                else                          val = '';
              } else {
                val = String(data).trim();
              }

              $('#personal_inicio_RRHH').val(val);

              $('#personal_inicio_RRHH').trigger('input');
            }).fail(function(){
              $('#personal_inicio_RRHH').val('');
            });
          }

          $('#casinoRRHH').on('change', function () {
            var tipo = $(this).val();

            $('#formNuevoRegistroRRHH .has-error').removeClass('has-error');
            $('#formNuevoRegistroRRHH .help-block.js-error').remove();

            setOfertadoPorCasino(tipo);

            cargarPersonalInicio(tipo);
          });

          $(document).on('shown.bs.modal', '#modalCargarRRHH', function(){
            $('#casinoRRHH').trigger('change');
          });
        });
        $(document).on('shown.bs.modal', '#modalCargarRRHH', function () {
          $(this).find('[data-toggle="popover"]').popover({ container: 'body', trigger: 'hover focus' });
        });
        attachYYYYMMFormatter('#fecha_RRHH');
        attachYYYYMMFormatter('#fecha_RRHHDesde');
        attachYYYYMMFormatter('#fecha_RRHHHasta');

      $('#fechaRRHH').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaRRHHPres').datetimepicker({
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
      $('#fecha_pagoRRHH').datetimepicker({
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
      $('#fechaRRHHDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaRRHHHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaRRHHDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaRRHHDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_ganancias"){
      cargarGanancias({ page: 1, perPage: 10});
      cargarGanancias_periodo({ page: 1, perPage: 10});


      $(document).on('click','#btnPickGanancias',function(){
          $('#uploadGanancias').click();
        });

        $(document).on('change','#uploadGanancias',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsGananciasContainer');
          renderUploadsGanancias();
          var $new = $('<input type="file" id="uploadGanancias" name="uploadGanancias[]" multiple style="display:none;">');
          $('#btnPickGanancias').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsGanancias(){
          var $tbody = $('#uploadsGananciasTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsGananciasContainer input[type=file][name="uploadGanancias[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsGananciasWrap').css('display', total ? '' : 'none');
          $('#fileNameGanancias').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsGananciasTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsGananciasContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsGanancias();
        });


$(document).on('click','#btnPickGanancias_periodo',function(){
          $('#uploadGanancias_periodo').click();
        });

        $(document).on('change','#uploadGanancias_periodo',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsGanancias_periodoContainer');
          renderUploadsGanancias_periodo();
          var $new = $('<input type="file" id="uploadGanancias_periodo" name="uploadGanancias_periodo[]" multiple style="display:none;">');
          $('#btnPickGanancias_periodo').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsGanancias_periodo(){
          var $tbody = $('#uploadsGanancias_periodoTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsGanancias_periodoContainer input[type=file][name="uploadGanancias_periodo[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsGanancias_periodoWrap').css('display', total ? '' : 'none');
          $('#fileNameGanancias_periodo').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsGanancias_periodoTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsGanancias_periodoContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsGanancias_periodo();
        });

        attachYYYYMMDDFormatter('#fecha_pago_Ganancias');
        attachYYYYMMDDFormatter('#fecha_pago_Ganancias_periodo');

      $('#fechaGanancias_periodoPres').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 4,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaGananciasPres').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 4,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fecha_pres_Ganancias_periodo').datetimepicker({
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
      $('#fecha_pagoGanancias').datetimepicker({
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
      $('#fechaGananciasDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy',
        pickerPosition: "top-left",
        startView: 4,
        minView: 4,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaGananciasHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy',
        pickerPosition: "top-left",
        startView: 4,
        minView: 4,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaGananciasDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy',
        pickerPosition: "top-left",
        startView: 4,
        minView: 4,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaGananciasDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy',
        pickerPosition: "top-left",
        startView: 4,
        minView: 4,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_jackpots_pagados"){
      cargarJackpotsPagados({ page: 1, perPage: 10});
      $(document).on('click','#btnPickJackpotsPagados',function(){
          $('#uploadJackpotsPagados').click();
        });

        $(document).on('change','#uploadJackpotsPagados',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsJackpotsPagadosContainer');
          renderUploadsJackpotsPagados();
          var $new = $('<input type="file" id="uploadJackpotsPagados" name="uploadJackpotsPagados[]" multiple style="display:none;">');
          $('#btnPickJackpotsPagados').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsJackpotsPagados(){
          var $tbody = $('#uploadsJackpotsPagadosTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsJackpotsPagadosContainer input[type=file][name="uploadJackpotsPagados[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsJackpotsPagadosWrap').css('display', total ? '' : 'none');
          $('#fileNameJackpotsPagados').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsJackpotsPagadosTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsJackpotsPagadosContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsJackpotsPagados();
        });

        attachYYYYMMFormatter('#fecha_JackpotsPagadosDesde');
        attachYYYYMMFormatter('#fecha_JackpotsPagadosHasta');
        attachYYYYMMFormatter('#fecha_JackpotsPagados');

      $('#fechaJackpotsPagados').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaJackpotsPagadosPres').datetimepicker({
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
      $('#fecha_pagoJackpotsPagados').datetimepicker({
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
      $('#fechaJackpotsPagadosDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaJackpotsPagadosHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaJackpotsPagadosDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaJackpotsPagadosDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_premios_pagados"){
      cargarPremiosPagados({ page: 1, perPage: 10});
      $(document).on('click','#btnPickPremiosPagados',function(){
          $('#uploadPremiosPagados').click();
        });

        $(document).on('change','#uploadPremiosPagados',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsPremiosPagadosContainer');
          renderUploadsPremiosPagados();
          var $new = $('<input type="file" id="uploadPremiosPagados" name="uploadPremiosPagados[]" multiple style="display:none;">');
          $('#btnPickPremiosPagados').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsPremiosPagados(){
          var $tbody = $('#uploadsPremiosPagadosTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsPremiosPagadosContainer input[type=file][name="uploadPremiosPagados[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsPremiosPagadosWrap').css('display', total ? '' : 'none');
          $('#fileNamePremiosPagados').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsPremiosPagadosTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsPremiosPagadosContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsPremiosPagados();
        });
        attachYYYYMMFormatter('#fecha_PremiosPagados');
        attachYYYYMMFormatter('#fecha_PremiosPagadosDesde');
        attachYYYYMMFormatter('#fecha_PremiosPagadosHasta');


      $('#fechaPremiosPagados').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPremiosPagadosPres').datetimepicker({
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
      $('#fecha_pagoPremiosPagados').datetimepicker({
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
      $('#fechaPremiosPagadosDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPremiosPagadosHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPremiosPagadosDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPremiosPagadosDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_premios_mtm"){
      cargarPremiosMTM({ page: 1, perPage: 10});

  $(document).on('click','#btnPickPremiosMTM',function(){
            $('#uploadPremiosMTM').click();
          });

          $(document).on('change','#uploadPremiosMTM',function(){
            if(!this.files || !this.files.length) return;
            var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
            $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsPremiosMTMContainer');
            renderUploadsPremiosMTM();
            var $new = $('<input type="file" id="uploadPremiosMTM" name="uploadPremiosMTM[]" multiple style="display:none;">');
            $('#btnPickPremiosMTM').closest('.input-group').append($new);
          });

          function humanSize(n){
            if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
            if(n >= 1024) return (n/1024).toFixed(0)+' KB';
            return n+' B';
          }

          function renderUploadsPremiosMTM(){
            var $tbody = $('#uploadsPremiosMTMTable tbody').empty();
            var total = 0, row = 1;

            $('#uploadsPremiosMTMContainer input[type=file][name="uploadPremiosMTM[]"]').each(function(ix){
              var $inp = $(this);
              var gid = $inp.attr('data-group');
              var files = this.files || [];
              for(var i=0;i<files.length;i++){
                var f = files[i];
                total++;
                var $tr = $('<tr></tr>');
                $tr.append('<td>'+ (row++) +'</td>');
                $tr.append('<td>'+ f.name +'</td>');
                $tr.append('<td>'+ humanSize(f.size) +'</td>');
                var $btn = $(
                              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                                '<i class="fa fa-trash"></i>' +
                              '</button>'
                            ).attr('data-group', gid).attr('data-idx', i);
                $tr.append($('<td></td>').append($btn));
                $tbody.append($tr);
              }
            });

            $('#uploadsPremiosMTMWrap').css('display', total ? '' : 'none');
            $('#fileNamePremiosMTM').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
          }

          $(document).on('click','#uploadsPremiosMTMTable .btn-danger',function(){
            var gid = $(this).attr('data-group');
            var idx = parseInt($(this).attr('data-idx'),10);
            var $inp = $('#uploadsPremiosMTMContainer input[type=file][data-group="'+gid+'"]');
            if(!$inp.length) return;

            var dt = new DataTransfer();
            var files = $inp[0].files;
            for(var i=0;i<files.length;i++){
              if(i !== idx) dt.items.add(files[i]);
            }
            $inp[0].files = dt.files;
            if($inp[0].files.length === 0) $inp.remove();
            renderUploadsPremiosMTM();
          });

          attachYYYYMMFormatter('#fecha_PremiosMTMDesde');
          attachYYYYMMFormatter('#fecha_PremiosMTMHasta');
          attachYYYYMMFormatter('#fecha_PremiosMTM');


      $('#fechaPremiosMTM').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPremiosMTMPres').datetimepicker({
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
      $('#fecha_pagoPremiosMTM').datetimepicker({
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
      $('#fechaPremiosMTMDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPremiosMTMHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPremiosMTMDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPremiosMTMDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_direct"){
      cargarAutDirectores({ page: 1, perPage: 10});

      $(document).on('click','#btnPickAutDirectores',function(){
          $('#uploadAutDirectores').click();
        });

        $(document).on('change','#uploadAutDirectores',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsAutDirectoresContainer');
          renderUploadsAutDirectores();
          var $new = $('<input type="file" id="uploadAutDirectores" name="uploadAutDirectores[]" multiple style="display:none;">');
          $('#btnPickAutDirectores').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsAutDirectores(){
          var $tbody = $('#uploadsAutDirectoresTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsAutDirectoresContainer input[type=file][name="uploadAutDirectores[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsAutDirectoresWrap').css('display', total ? '' : 'none');
          $('#fileNameAutDirectores').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsAutDirectoresTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsAutDirectoresContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsAutDirectores();
        });

        attachYYYYMMFormatter('#fecha_AutDirectoresDesde');
        attachYYYYMMFormatter('#fecha_AutDirectoresHasta');
        attachYYYYMMFormatter('#fecha_AutDirectores_autorizacion');

        $('#casinoAutDirectores_autorizacion').off('change.autdir').on('change.autdir', function(){
          if ($('#AutDirectores_modo').val() === 'edit') return;
          var id = $(this).val();
          $('#zona-directores').html('');
          if(!id){ return; }
          $.getJSON('/documentosContables/AutDirectoresHabilitadosPorCasino/'+id, function(data){
            if(!data || !data.length){
              $('#zona-directores').html('<div class="col-md-12 text-muted">Sin directores habilitados para este casino.</div>');
              return;
            }
            var html = '';
            for (var i=0;i<data.length;i++){
              var d = data[i];
              html += '<div class="col-md-7 d-flex align-items-center mb-2">';
              html += '  <span class="me-2" style="white-space:nowrap;">'+d.nombre+' C.U.I.T.: '+(d.cuit||'')+'</span>';
              html += '  <input type="hidden" name="directores['+d.id_registroAutDirectores_director+']" value="0">';
              html += '  <input type="checkbox" name="directores['+d.id_registroAutDirectores_director+']" value="1" style="margin-left:6px;">';
              html += '</div>';
              html += '<div class="col-md-5 mb-2">';
              html += '  <textarea name="observacion['+d.id_registroAutDirectores_director+']" class="form-control form-control-sm" rows="1" placeholder="observacion"></textarea>';
              html += '</div>';
            }
            $('#zona-directores').html(html);
          });
        });



      $('#fechaAutDirectores_autorizacion').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaAutDirectoresPres').datetimepicker({
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
      $('#fecha_pagoAutDirectores').datetimepicker({
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
      $('#fechaAutDirectoresDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaAutDirectoresHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaAutDirectoresDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaAutDirectoresDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_seguros"){
      cargarSeguros({ page: 1, perPage: 10});
      $(document).on('click','#btnPickSeguros',function(){
          $('#uploadSeguros').click();
        });

        $(document).on('change','#uploadSeguros',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsSegurosContainer');
          renderUploadsSeguros();
          var $new = $('<input type="file" id="uploadSeguros" name="uploadSeguros[]" multiple style="display:none;">');
          $('#btnPickSeguros').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsSeguros(){
          var $tbody = $('#uploadsSegurosTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsSegurosContainer input[type=file][name="uploadSeguros[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsSegurosWrap').css('display', total ? '' : 'none');
          $('#fileNameSeguros').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsSegurosTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsSegurosContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsSeguros();
        });

        attachYYYYMMFormatter('#fecha_SegurosDesde');
        attachYYYYMMFormatter('#fecha_SegurosHasta');
        attachYYYYMMDDFormatter('#fecha_SegurosDes');
        attachYYYYMMDDFormatter('#fecha_SegurosHas');
      cargarTiposSeguro();

      $('#fechaSegurosDes').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaSegurosHas').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaSegurosPres').datetimepicker({
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
      $('#fecha_pagoSeguros').datetimepicker({
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
      $('#fechaSegurosDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "top-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaSegurosHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "top-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaSegurosDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "top-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaSegurosDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "top-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_derecho"){
      cargarDerechoAcceso({ page: 1, perPage: 10});

$(document).on('click','#btnPickDerechoAcceso',function(){
          $('#uploadDerechoAcceso').click();
        });

        $(document).on('change','#uploadDerechoAcceso',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsDerechoAccesoContainer');
          renderUploadsDerechoAcceso();
          var $new = $('<input type="file" id="uploadDerechoAcceso" name="uploadDerechoAcceso[]" multiple style="display:none;">');
          $('#btnPickDerechoAcceso').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsDerechoAcceso(){
          var $tbody = $('#uploadsDerechoAccesoTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsDerechoAccesoContainer input[type=file][name="uploadDerechoAcceso[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsDerechoAccesoWrap').css('display', total ? '' : 'none');
          $('#fileNameDerechoAcceso').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsDerechoAccesoTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsDerechoAccesoContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsDerechoAcceso();
        });


        attachYYYYMMFormatter('#fecha_DerechoAccesoDesde');
        attachYYYYMMFormatter('#fecha_DerechoAccesoHasta');
        attachYYYYMMFormatter('#fecha_DerechoAcceso');
        attachYYYYMMDDFormatter('#fecha_venc_DerechoAcceso');


      $('#fechaDerechoAcceso').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDerechoAccesoPres').datetimepicker({
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
      $('#fecha_vencDerechoAcceso').datetimepicker({
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
      $('#fechaDerechoAccesoDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDerechoAccesoHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDerechoAccesoDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaDerechoAccesoDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_patentes"){
      cargarPatentes({ page: 1, perPage: 10});

$(document).on('click','#btnPickPatentes',function(){
          $('#uploadPatentes').click();
        });

        $(document).on('change','#uploadPatentes',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsPatentesContainer');
          renderUploadsPatentes();
          var $new = $('<input type="file" id="uploadPatentes" name="uploadPatentes[]" multiple style="display:none;">');
          $('#btnPickPatentes').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsPatentes(){
          var $tbody = $('#uploadsPatentesTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsPatentesContainer input[type=file][name="uploadPatentes[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsPatentesWrap').css('display', total ? '' : 'none');
          $('#fileNamePatentes').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsPatentesTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsPatentesContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsPatentes();
        });

      attachYYYYMMFormatter('#fecha_PatentesDesde');
      attachYYYYMMFormatter('#fecha_PatentesHasta');
      attachYYYYMMDDFormatter('#fecha_PatentesPres');
      attachYYYYMMFormatter('#fecha_Patentes');



      $('#fechaPatentes').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaPatentesPres').datetimepicker({
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
      $('#fecha_vencPatentes').datetimepicker({
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
      $('#fechaPatentesDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPatentesHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPatentesDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaPatentesDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
    else if(selector === "#pant_inmobiliario"){
        cargarImpInmobiliario({ page: 1, perPage: 10});

$(document).on('click','#btnPickImpInmobiliario',function(){
          $('#uploadImpInmobiliario').click();
        });

        $(document).on('change','#uploadImpInmobiliario',function(){
          if(!this.files || !this.files.length) return;
          var gid = 'lote_'+(Date.now().toString(36)+Math.random().toString(36).slice(2,6));
          $(this).attr('data-group', gid).removeAttr('id').addClass('archived').appendTo('#uploadsImpInmobiliarioContainer');
          renderUploadsImpInmobiliario();
          var $new = $('<input type="file" id="uploadImpInmobiliario" name="uploadImpInmobiliario[]" multiple style="display:none;">');
          $('#btnPickImpInmobiliario').closest('.input-group').append($new);
        });

        function humanSize(n){
          if(n >= 1024*1024) return (n/1048576).toFixed(1)+' MB';
          if(n >= 1024) return (n/1024).toFixed(0)+' KB';
          return n+' B';
        }

        function renderUploadsImpInmobiliario(){
          var $tbody = $('#uploadsImpInmobiliarioTable tbody').empty();
          var total = 0, row = 1;

          $('#uploadsImpInmobiliarioContainer input[type=file][name="uploadImpInmobiliario[]"]').each(function(ix){
            var $inp = $(this);
            var gid = $inp.attr('data-group');
            var files = this.files || [];
            for(var i=0;i<files.length;i++){
              var f = files[i];
              total++;
              var $tr = $('<tr></tr>');
              $tr.append('<td>'+ (row++) +'</td>');
              $tr.append('<td>'+ f.name +'</td>');
              $tr.append('<td>'+ humanSize(f.size) +'</td>');
              var $btn = $(
                            '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                              '<i class="fa fa-trash"></i>' +
                            '</button>'
                          ).attr('data-group', gid).attr('data-idx', i);
              $tr.append($('<td></td>').append($btn));
              $tbody.append($tr);
            }
          });

          $('#uploadsImpInmobiliarioWrap').css('display', total ? '' : 'none');
          $('#fileNameImpInmobiliario').val(total ? (total+' archivo(s) seleccionado(s)') : 'No se ha seleccionado ningún archivo');
        }

        $(document).on('click','#uploadsImpInmobiliarioTable .btn-danger',function(){
          var gid = $(this).attr('data-group');
          var idx = parseInt($(this).attr('data-idx'),10);
          var $inp = $('#uploadsImpInmobiliarioContainer input[type=file][data-group="'+gid+'"]');
          if(!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for(var i=0;i<files.length;i++){
            if(i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if($inp[0].files.length === 0) $inp.remove();
          renderUploadsImpInmobiliario();
        });



        attachYYYYMMFormatter('#fecha_ImpInmobiliario');
        attachYYYYMMDDFormatter('#fecha_ImpInmobiliarioPres');
        attachYYYYMMFormatter('#fecha_ImpInmobiliarioDesde');
        attachYYYYMMFormatter('#fecha_ImpInmobiliarioHasta');

      $('#fechaImpInmobiliario').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,
        timePicker: false,
        container:$('main section')
      });
      $('#fechaImpInmobiliarioPres').datetimepicker({
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
      $('#fecha_vencImpInmobiliario').datetimepicker({
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
      $('#fechaImpInmobiliarioDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaImpInmobiliarioHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaImpInmobiliarioDescDesde').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
      $('#fechaImpInmobiliarioDescHasta').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm',
        pickerPosition: "top-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container:$('main section')
      });
    }
  });
  $('[data-js-tabs] a').first().trigger('click');
});
