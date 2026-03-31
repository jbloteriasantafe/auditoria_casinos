// SCRIPT PARA MOSTRAR VERSIONES EN COMPARACIÓN
console.log('Script de versiones V2 cargado (con onion skin)');

// Variables globales para onion skin
var onionPdf = null;
var currentOnionPage = 1;
var onionVersionId = null;
var onionAnotacionesCache = null;

window.modoComparacion = false;

window.poblarSelectComparacion = function(nota) {
    var idNota = null;
    if (nota && nota.id) idNota = nota.id;
    else if (nota && nota.id_nota_ingreso) idNota = nota.id_nota_ingreso;
    else if (typeof currentIdNota !== 'undefined' && currentIdNota) idNota = currentIdNota;

    var tipo = (typeof currentTipoArchivo !== 'undefined') ? currentTipoArchivo : null;

    if(!idNota || !tipo) {
        console.error('ERROR: No se pudo determinar ID de nota o tipo de archivo para versiones');
        return;
    }

    // Cargar versiones disponibles en el select de comparación
    function cargarVersionesComparacion() {
        var select = $('#select-comparar');
        select.empty().append('<option value="">Seleccionar versión...</option>');

        $.ajax({
            url: '/notas-unificadas/historial-versiones/' + idNota + '/' + tipo,
            method: 'GET',
            success: function(response) {
                if(response.success && response.versiones && response.versiones.length > 0) {
                    var versiones = response.versiones;
                    if(versiones.length <= 1) {
                        select.append('<option disabled>No hay versiones anteriores disponibles</option>');
                    } else {
                        versiones.forEach(function(v, index) {
                            select.append('<option value="version-' + v.id + '" data-id="' + v.id + '">v' + v.version + ' — ' + v.created_at + '</option>');
                        });
                    }
                } else {
                    select.append('<option disabled>No hay historial de versiones</option>');
                }
            }
        });
    }

    // Checkbox: activar modo comparación
    $('#check-comparar').off('change').on('change', function() {
        if($(this).is(':checked')) {
            cargarVersionesComparacion();
            // Actualizar label de versión actual
            var textoVersionActual = $('#selectVersion option:selected').text() || '—';
            $('#labelVersionActual').text(textoVersionActual);
            // Mostrar layout comparación
            $('#layoutSinComparar').hide();
            $('#layoutComparando').css('display', 'flex');
        } else {
            salirComparacion();
        }
    });

    // Botón cancelar comparación
    $('#btnCancelarComparar').off('click').on('click', function() {
        salirComparacion();
    });

    // Cambio en select de versión a comparar
    $('#select-comparar').off('change').on('change', function() {
        var valor = $(this).val();
        if(!valor) {
            limpiarOnionSkin();
            window.modoComparacion = false;
            _desbloquearHerramientas();
            if(typeof window.mostrarComentariosComparacion === 'function') window.mostrarComentariosComparacion([]);
            return;
        }
        if(valor.startsWith('version-')) {
            var idVersion = $(this).find(':selected').data('id');
            if(!idVersion) idVersion = valor.replace('version-', '');
            activarOnionSkin(idVersion);
            // Bloquear herramientas de dibujo durante comparación
            window.modoComparacion = true;
            _bloquearHerramientas();
        }
    });

    // Slider opacidad: 0% = solo versión actual, 100% = solo versión comparada
    $('#slider-opacidad').off('input change').on('input change', function() {
        var pct = parseInt($(this).val(), 10);
        $('.onion-layer').css('opacity', pct / 100);
        $('#slider-opacidad-label').text(pct + '%');
    });

    // Estado inicial
    $('#layoutComparando').hide();
    $('#layoutSinComparar').show();
    $('#check-comparar').prop('checked', false);
    limpiarOnionSkin();
};

function salirComparacion() {
    $('#check-comparar').prop('checked', false);
    $('#layoutComparando').hide();
    $('#layoutSinComparar').show();
    $('#select-comparar').val('');
    limpiarOnionSkin();
    window.modoComparacion = false;
    _desbloquearHerramientas();
    // Limpiar comentarios de comparación
    if(typeof window.mostrarComentariosComparacion === 'function') window.mostrarComentariosComparacion([]);
}

function _bloquearHerramientas() {
    $('#grupoHerramientas .btn-tool').prop('disabled', true).css('opacity', 0.4);
    $('#btnComment').prop('disabled', true).css('opacity', 0.4);
    $('#btnDeleteSelected').prop('disabled', true).css('opacity', 0.4);
}

function _desbloquearHerramientas() {
    $('#grupoHerramientas .btn-tool').prop('disabled', false).css('opacity', 1);
    $('#btnComment').prop('disabled', false).css('opacity', 1);
    $('#btnDeleteSelected').prop('disabled', false).css('opacity', 1);
}

function activarOnionSkin(idVersion) {
    onionVersionId = idVersion;
    onionAnotacionesCache = null;
    var pdfRenderedPage = null;

    // Inicializar slider al 50%
    $('#slider-opacidad').val(50);
    $('#slider-opacidad-label').text('50%');
    $('.onion-layer').css('opacity', 0.5);

    var idNota = (typeof currentIdNota !== 'undefined') ? currentIdNota : null;
    var tipo   = (typeof currentTipoArchivo !== 'undefined') ? currentTipoArchivo : null;

    if(idNota && tipo) {
        $.get('/notas-unificadas/pdf-anotaciones/datos/' + idNota + '/' + tipo + '?version_id=' + idVersion, function(data) {
            onionAnotacionesCache = data.anotaciones || [];
            // Mostrar comentarios de la versión comparada (solo lectura)
            var compLabel = $('#select-comparar option:selected').text().match(/^(v\d+)/);
            compLabel = compLabel ? compLabel[1] : '';
            if(typeof window.mostrarComentariosComparacion === 'function') {
                window.mostrarComentariosComparacion(data.comentarios || [], compLabel);
            }
            if(pdfRenderedPage !== null) {
                dibujarAnotacionesOnion(pdfRenderedPage);
            }
        });
    }

    cargarPdfOnion('/notas-unificadas/visualizar-version/' + idVersion, function(pageNum) {
        pdfRenderedPage = pageNum;
        if(onionAnotacionesCache !== null) {
            dibujarAnotacionesOnion(pageNum);
        }
    });
}

function cargarPdfOnion(url, onRendered) {
    pdfjsLib.getDocument(url).promise.then(function(pdf) {
        onionPdf = pdf;
        currentOnionPage = (typeof currentPage !== 'undefined' ? currentPage : 1) || 1;
        renderOnionLayer(currentOnionPage, onRendered);
    });
}

function renderOnionLayer(pageNum, onRendered) {
    if(!onionPdf) return;

    onionPdf.getPage(pageNum).then(function(page) {
        var scale = 1.5;
        var viewport = page.getViewport({scale: scale});

        var onionEl = document.getElementById('onionCanvas');
        if(!onionEl) return;

        var ctx = onionEl.getContext('2d');
        onionEl.width  = viewport.width;
        onionEl.height = viewport.height;
        $(onionEl).css({ width: viewport.width + 'px', height: viewport.height + 'px' });
        ctx.clearRect(0, 0, onionEl.width, onionEl.height);

        page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function() {
            $('.onion-layer').show();
            if(typeof onRendered === 'function') onRendered(pageNum);
        });
    });
}

function dibujarAnotacionesOnion(pageNum) {
    if(!onionAnotacionesCache) return;

    var paginaData = onionAnotacionesCache.find(function(a) { return a.pagina == pageNum; });
    if(!paginaData || !paginaData.anotaciones_json) return;

    var onionEl = document.getElementById('onionCanvas');
    if(!onionEl) return;

    var json;
    try { json = JSON.parse(paginaData.anotaciones_json); } catch(e) { return; }
    if(!json || !json.objects || json.objects.length === 0) return;

    var tempId = 'onionFabricTemp_' + Date.now();
    var tempEl = document.createElement('canvas');
    tempEl.id = tempId;
    tempEl.width  = onionEl.width;
    tempEl.height = onionEl.height;
    tempEl.style.cssText = 'position:absolute; left:-9999px; top:-9999px;';
    document.body.appendChild(tempEl);

    var tempFabric = new fabric.StaticCanvas(tempId, {
        width:  onionEl.width,
        height: onionEl.height
    });

    tempFabric.loadFromJSON(json, function() {
        tempFabric.renderAll();
        var ctx = onionEl.getContext('2d');
        ctx.drawImage(tempFabric.lowerCanvasEl, 0, 0);
        tempFabric.dispose();
        // StaticCanvas NO crea wrapper, tempEl sigue siendo hijo directo de document.body
        if(document.body.contains(tempEl)) {
            document.body.removeChild(tempEl);
        }
    });
}

function limpiarOnionSkin() {
    var onionEl = document.getElementById('onionCanvas');
    if(onionEl) {
        var ctx = onionEl.getContext('2d');
        ctx.clearRect(0, 0, onionEl.width, onionEl.height);
    }
    $('.onion-layer').hide();
    onionPdf = null;
    onionVersionId = null;
    onionAnotacionesCache = null;
}

// Llamar al cambiar de página en el editor principal
window.actualizarOnionPagina = function(pageNum) {
    if(onionPdf) {
        renderOnionLayer(pageNum, function(p) {
            if(onionAnotacionesCache !== null) dibujarAnotacionesOnion(p);
        });
    }
};
