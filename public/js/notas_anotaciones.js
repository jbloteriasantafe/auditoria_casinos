/**
 * Sistema de Anotaciones de PDFs para Notas Unificadas
 * Adaptado de test_v2.js
 */

// Variables globales
var pdfDoc = null;
var currentPage = 1;
var totalPages = 0;
var canvas = null;
var currentIdNota = null;
var currentTipoArchivo = null;
var currentVersionId = null;
var currentVersionLabel = '';
var currentNroNota = '';
var comentariosDB = [];
var nextComRef = 1;

// ============================================
// CARGA DIFERIDA DE LIBRERÍAS
// ============================================
var _libsAnotacionesCargadas = false;

function cargarLibsAnotaciones(callback) {
    if (_libsAnotacionesCargadas) { callback(); return; }

    var pdfScript = document.createElement('script');
    pdfScript.src = '/js/lib/pdf.min.js';
    pdfScript.onload = function() {
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/lib/pdf.worker.min.js';
        var fabricScript = document.createElement('script');
        fabricScript.src = '/js/lib/fabric.min.js';
        fabricScript.onload = function() {
            _libsAnotacionesCargadas = true;
            callback();
        };
        document.head.appendChild(fabricScript);
    };
    document.head.appendChild(pdfScript);
}

// ============================================
// INICIALIZACIÓN
// ============================================

$(document).on('click', '.btn-agregar-observaciones', function() {
    var idNota = $(this).data('id');
    var nroNota = $(this).data('nro-nota');

    currentIdNota = idNota;
    currentNroNota = nroNota;

    // Cargar librerías solo si no están cargadas aún
    cargarLibsAnotaciones(function() {
        // Cargar lista de PDFs disponibles
        $.get(`/notas-unificadas/pdf-anotaciones/listar/${idNota}`, function(pdfs) {
            if(pdfs.length === 0) {
                notificacion('warning', 'Esta nota no tiene PDFs adjuntos para anotar.');
                return;
            }
            mostrarSelectorPdfs(pdfs);
        }).fail(function() {
            notificacion('error', 'Error al cargar los PDFs de esta nota.');
        });
    });
});

function mostrarSelectorPdfs(pdfs) {
    var html = '<div class="list-group" style="max-height: 400px; overflow-y: auto;">';

    pdfs.forEach(function(pdf) {
        var icon = pdf.nombre.includes('Solicitud') ? 'file-pdf-o' :
                   pdf.nombre.includes('Dise') ? 'image' :
                   pdf.nombre.includes('Bases') ? 'file-text-o' :
                   pdf.nombre.includes('Varios') ? 'archive' :
                   'file-pdf-o';

        html += '<a href="javascript:void(0)" class="list-group-item pdf-selector-item"' +
                ' data-tipo="' + pdf.tipo + '"' +
                ' data-version-id="' + (pdf.version_id || '') + '"' +
                ' style="cursor: pointer; transition: all 0.2s;">' +
                '<i class="fa fa-' + icon + ' fa-2x pull-left" style="margin-right: 15px; color: #e74c3c;"></i>' +
                '<h4 class="list-group-item-heading">' + pdf.nombre + '</h4>' +
                '<p class="list-group-item-text text-muted">' + pdf.archivo + '</p>' +
                '</a>';
    });

    html += '</div>';

    $('#listaPdfsDisponibles').html(html);
    $('#modalSelectorPdfs').modal('show');
}

$(document).on('click', '.pdf-selector-item', function() {
    var tipo = $(this).data('tipo');
    var versionId = $(this).data('version-id') || null;
    $('#modalSelectorPdfs').modal('hide');
    abrirEditorPdf(currentIdNota, tipo, versionId);
});

function abrirEditorPdf(idNota, tipo, versionId) {
    currentTipoArchivo = tipo;
    currentVersionId = versionId || null;

    $('#modalEditorAnotaciones').modal('show');

    // Limpiar overlay previo si quedó colgado
    $('#pdfLoadingOverlay').remove();
    var loadingOverlay = $('<div id="pdfLoadingOverlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(52, 73, 94, 0.95); z-index: 10000; display: flex; align-items: center; justify-content: center;"><div class="text-center" style="color: white;"><i class="fa fa-spinner fa-spin fa-3x"></i><p style="margin-top: 20px; font-size: 16px;">Cargando PDF...</p></div></div>');
    $('#editorContent').append(loadingOverlay);

    var ajaxUrl = '/notas-unificadas/pdf-anotaciones/datos/' + idNota + '/' + tipo;
    if(currentVersionId) ajaxUrl += '?version_id=' + currentVersionId;

    $.get(ajaxUrl, function(data) {
        window.notaActual = data.nota;
        $('#pdfLoadingOverlay').remove();
        inicializarEditor(data);
        setTimeout(function() {
            if(typeof poblarSelectComparacion === 'function') poblarSelectComparacion(data.nota);
        }, 500);
    }).fail(function(xhr) {
        $('#pdfLoadingOverlay').remove();
        var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error desconocido';
        notificacion('error', 'Error al cargar el PDF: ' + msg);
        $('#modalEditorAnotaciones').modal('hide');
    });
}
function inicializarEditor(data) {
    comentariosDB = data.comentarios || [];

    // Calcular siguiente número de referencia
    nextComRef = 1;
    comentariosDB.forEach(function(c) {
        if(c.numero_ref && c.numero_ref >= nextComRef) {
            nextComRef = c.numero_ref + 1;
        }
    });

    // Actualizar header
    $('#editorTitulo').html(
        '<i class="fa fa-file-pdf-o"></i> ' +
        'Nota #' + currentNroNota + ' - ' + (data.nota.titulo || 'Sin título') +
        '<small class="text-muted"> | ' + getCurrentTipoNombre() + '</small>'
    );

    // Poblar selector de versiones
    var versiones = data.versiones || [];
    if(versiones.length >= 1) {
        var versionHtml = '';
        versiones.forEach(function(v, idx) {
            var esCurrent = currentVersionId ? (v.id == currentVersionId) : (idx === 0);
            if(esCurrent) currentVersionLabel = 'v' + v.version;
            var label = 'v' + v.version + ' — ' + v.nombre_original +
                        (v.created_at ? ' (' + v.created_at + ')' : '') +
                        (idx === 0 ? ' ★' : '');
            versionHtml += '<option value="' + v.id + '"' + (esCurrent ? ' selected' : '') + '>' + label + '</option>';
        });
        $('#selectVersion').html(versionHtml);
        $('#contenedorVersiones').css('display', 'flex');
    } else {
        $('#contenedorVersiones').hide();
    }

    // Cargar PDF
    var loadingTask = pdfjsLib.getDocument(data.url);
    
    loadingTask.promise.then(function(pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        currentPage = 1;
        
        $('#pageInfo').text(`Página ${currentPage} de ${totalPages}`);
        
        renderPage(pdf, currentPage, data.anotaciones, comentariosDB);
        mostrarComentarios(comentariosDB);
        
    }).catch(function(error) {
        console.error('Error loading PDF:', error);
        notificacion('error', 'Error al cargar el PDF: ' + error.message);
    });
}

function getCurrentTipoNombre() {
    var nombres = {
        'pautas': 'Solicitud Concesionario',
        'solicitud': 'Solicitud Concesionario',
        'diseno': 'Diseño/Arte',
        'bases': 'Bases y Condiciones',
        'varios': 'Archivos Varios',
        'informe': 'Informe Técnico'
    };
    return nombres[currentTipoArchivo] || currentTipoArchivo;
}

// ============================================
// RENDERIZADO DE PÁGINA
// ============================================

function renderPage(pdf, pageNum, anotacionesData, comentariosDB) {
    pdf.getPage(pageNum).then(function(page) {
        var scale = 1.5;
        var viewport = page.getViewport({scale: scale});
        
        // Crear canvas temporal para renderizar el PDF
        var tempCanvas = document.createElement('canvas');
        var ctx = tempCanvas.getContext('2d');
        tempCanvas.width = viewport.width;
        tempCanvas.height = viewport.height;
        
        var renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };
        
        // Renderizar PDF en canvas temporal
        page.render(renderContext).promise.then(function() {
            // Crear imagen de Fabric desde el canvas temporal
            var bgImage = new fabric.Image(tempCanvas, {
                scaleX: 1,
                scaleY: 1,
                selectable: false,
                evented: false
            });
            
            // Inicializar Fabric.js
            if(canvas) {
                canvas.dispose();
            }
            
            canvas = new fabric.Canvas('pdfCanvas', {
                width: viewport.width,
                height: viewport.height,
                selection: true
            });
            
            // Establecer PDF como fondo del canvas
            canvas.setBackgroundImage(bgImage, canvas.renderAll.bind(canvas));
            
            // Adjuntar eventos del canvas
            attachCanvasEvents();
            
            // Cargar anotaciones guardadas de esta página
            if(anotacionesData) {
                var anotPage = anotacionesData.find(a => a.pagina == pageNum);
                if(anotPage && anotPage.anotaciones_json) {
                    var jsonData = JSON.parse(anotPage.anotaciones_json);
                    delete jsonData.backgroundImage; // No cargar el fondo viejo
                    canvas.loadFromJSON(jsonData, function() {
                        canvas.setBackgroundImage(bgImage, canvas.renderAll.bind(canvas));
                    });
                }
            }
            
            // Cargar comentarios de esta página
            cargarComentariosPagina(pageNum, comentariosDB);
            
            console.log('Canvas inicializado correctamente con PDF como fondo');
        });
    }).catch(function(error) {
        console.error('Error al renderizar página:', error);
    });
}

// Llamar a poblarSelectComparacion después de que se inicializa el editor
function cargarVersionesEnSelector() {
    if(typeof poblarSelectComparacion === 'function' && window.notaActual) {
        console.log('Cargando versiones para selector de comparación');
        poblarSelectComparacion(window.notaActual);
    }
}
            
            // Poblar select de comparación con versiones

function aplicarEstilosUnificados() {
    canvas.getObjects().forEach(function(obj) {
        if(obj.type === 'line') {
            obj.set({
                stroke: '#ff0000',
                strokeWidth: 4,
                selectable: true,
                evented: true
            });
        } else if(obj.type === 'rect') {
            obj.set({
                stroke: '#ff0000',
                strokeWidth: 3,
                fill: 'transparent',
                selectable: true,
                evented: true,
                lockMovementX: false,
                lockMovementY: false,
                hasControls: false,
                hasBorders: true
            });
        } else if(obj.type === 'path') {
            obj.set({
                selectable: true,
                evented: true,
                hasControls: false,
                hasBorders: false,
                perPixelTargetFind: true,
                lockMovementX: false,
                lockMovementY: false
            });
        } else if(obj.type === 'circle' && obj.commentRef) {
            obj.set({
                selectable: false,
                evented: true
            });
        }
    });
    canvas.renderAll();
}

function cargarComentariosPagina(pageNum, comentariosDB) {
    // Recopilar commentRef que ya existen en el canvas (cargados desde JSON)
    var existentes = {};
    canvas.getObjects().forEach(function(obj) {
        if (obj.commentRef) existentes[obj.commentRef] = true;
    });

    comentariosDB.forEach(function(c) {
        if(c.pagina == pageNum && c.pos_x && c.pos_y && !existentes[c.numero_ref]) {
            agregarGlobitoEnCanvas(c.pos_x, c.pos_y, c.numero_ref, c.id);
        }
    });
    canvas.renderAll();
}

// ============================================
// HERRAMIENTAS DE DIBUJO
// ============================================

var modoActual = 'select'; // select, arrow, rect, comment

$('#btnSelect').click(function() {
    modoActual = 'select';
    canvas.isDrawingMode = false;
    $('.btn-tool').removeClass('active');
    $(this).addClass('active');
});

$('#btnArrow').click(function() {
    modoActual = 'arrow';
    canvas.isDrawingMode = false;
    $('.btn-tool').removeClass('active');
    $(this).addClass('active');
});

$('#btnRect').click(function() {
    modoActual = 'rect';
    canvas.isDrawingMode = false;
    $('.btn-tool').removeClass('active');
    $(this).addClass('active');
});

$('#btnComment').click(function() {
    modoActual = 'comment';
    canvas.isDrawingMode = false;
    $('.btn-tool').removeClass('active');
    $(this).addClass('active');
});

$('#btnDeleteSelected').click(function() {
    if(!canvas) return;
    var activeObject = canvas.getActiveObject();
    if(activeObject && !activeObject.commentRef) {
        canvas.remove(activeObject);
        canvas.renderAll();
        guardarAnotacionesAutomatico();
    }
});

// Cambiar versión desde el selector dentro del editor
$(document).on('change', '#selectVersion', function() {
    var versionId = $(this).val();
    if(!versionId || !currentIdNota) return;
    currentVersionId = versionId;

    var ajaxUrl = '/notas-unificadas/pdf-anotaciones/datos/' + currentIdNota + '/' + currentTipoArchivo + '?version_id=' + versionId;
    $.get(ajaxUrl, function(data) {
        // Actualizar comentarios de la nueva versión
        comentariosDB = data.comentarios || [];
        nextComRef = 1;
        comentariosDB.forEach(function(c) {
            if(c.numero_ref && c.numero_ref >= nextComRef) nextComRef = c.numero_ref + 1;
        });
        mostrarComentarios(comentariosDB);

        var loadingTask = pdfjsLib.getDocument(data.url);
        loadingTask.promise.then(function(pdf) {
            pdfDoc = pdf;
            totalPages = pdf.numPages;
            currentPage = 1;
            $('#pageInfo').text('Página ' + currentPage + ' de ' + totalPages);
            renderPage(pdf, currentPage, data.anotaciones, comentariosDB);
        });
    }).fail(function() {
        notificacion('error', 'Error al cargar la versión seleccionada.');
    });
});

// Detectar tecla Delete/Backspace para eliminar objetos seleccionados
$(document).on('keydown', function(e) {
    if((e.keyCode === 46 || e.keyCode === 8) && canvas) {
        var activeObject = canvas.getActiveObject();
        if(activeObject && !activeObject.commentRef) {
            if($(e.target).is('textarea, input')) return;
            e.preventDefault();
            canvas.remove(activeObject);
            canvas.renderAll();
        }
    }
});

// Variables de dibujo
var isDown = false;
var origX, origY;
var currentShape = null;

// ============================================
// HELPERS DE FLECHA
// ============================================

/**
 * Crea un fabric.Path de flecha con arrowhead.
 * selectable=false para preview durante el dibujo.
 */
function _buildArrowPath(x1, y1, x2, y2, finalizado) {
    var dx = x2 - x1, dy = y2 - y1;
    var len = Math.sqrt(dx * dx + dy * dy);
    if (len < 2) len = 2;

    var angle = Math.atan2(dy, dx);
    var headLen = Math.min(22, len * 0.35);
    var headAngle = Math.PI / 6; // 30°

    var hx1 = x2 - headLen * Math.cos(angle - headAngle);
    var hy1 = y2 - headLen * Math.sin(angle - headAngle);
    var hx2 = x2 - headLen * Math.cos(angle + headAngle);
    var hy2 = y2 - headLen * Math.sin(angle + headAngle);

    var pathStr = 'M ' + x1 + ' ' + y1 +
                  ' L ' + x2 + ' ' + y2 +
                  ' M ' + x2 + ' ' + y2 +
                  ' L ' + hx1 + ' ' + hy1 +
                  ' M ' + x2 + ' ' + y2 +
                  ' L ' + hx2 + ' ' + hy2;

    return new fabric.Path(pathStr, {
        stroke: '#e53e3e',
        strokeWidth: 3,
        fill: '',
        strokeLineCap: 'round',
        strokeLineJoin: 'round',
        selectable: !!finalizado,
        evented: !!finalizado,
        hasControls: false,
        hasBorders: false,
        perPixelTargetFind: true,
        lockRotation: true,
        lockScalingX: true,
        lockScalingY: true,
        padding: 4
    });
}

/**
 * Deshabilita interactividad en todos los objetos existentes del canvas.
 * Guarda el estado previo en obj._prevSelectable para restaurar después.
 */
function _lockCanvas() {
    canvas.selection = false;
    canvas.getObjects().forEach(function(obj) {
        obj._prevSelectable = obj.selectable;
        obj._prevEvented    = obj.evented;
        obj.selectable = false;
        obj.evented    = false;
    });
}

/**
 * Restaura la interactividad de los objetos del canvas.
 */
function _unlockCanvas() {
    canvas.selection = true;
    canvas.getObjects().forEach(function(obj) {
        if (obj._prevSelectable !== undefined) {
            obj.selectable = obj._prevSelectable;
            obj.evented    = obj._prevEvented;
            delete obj._prevSelectable;
            delete obj._prevEvented;
        }
    });
}

// Definir eventos del canvas (se adjuntarán después de que canvas se inicialice)
function attachCanvasEvents() {
    if (!canvas) {
        console.warn('Canvas no inicializado, no se pueden adjuntar eventos');
        return;
    }

    canvas.on('mouse:down', function(o) {
        if(modoActual === 'select') return;
        if(window.modoComparacion) return; // No dibujar durante comparación

        // Bloquear todo mientras se dibuja
        _lockCanvas();

        isDown = true;
        var pointer = canvas.getPointer(o.e);
        origX = pointer.x;
        origY = pointer.y;

        if(modoActual === 'arrow') {
            currentShape = _buildArrowPath(origX, origY, origX + 1, origY + 1, false);
            canvas.add(currentShape);

        } else if(modoActual === 'rect') {
            currentShape = new fabric.Rect({
                left: origX,
                top: origY,
                width: 0,
                height: 0,
                stroke: '#e53e3e',
                strokeWidth: 3,
                fill: 'rgba(229, 62, 62, 0.05)',
                selectable: false,
                evented: false,
                hasControls: false,
                hasBorders: false
            });
            canvas.add(currentShape);

        } else if(modoActual === 'comment') {
            _unlockCanvas();
            agregarComentario(origX, origY);
            isDown = false;
        }
    });

    canvas.on('mouse:move', function(o) {
        if(!isDown || modoActual === 'select' || modoActual === 'comment') return;

        var pointer = canvas.getPointer(o.e);

        if(modoActual === 'arrow' && currentShape) {
            // Recrear el path con arrowhead en vivo
            canvas.remove(currentShape);
            currentShape = _buildArrowPath(origX, origY, pointer.x, pointer.y, false);
            canvas.add(currentShape);
            canvas.renderAll();

        } else if(modoActual === 'rect' && currentShape) {
            var width = pointer.x - origX;
            var height = pointer.y - origY;
            currentShape.set({width: Math.abs(width), height: Math.abs(height)});

            if(width < 0) currentShape.set({left: pointer.x});
            if(height < 0) currentShape.set({top: pointer.y});

            canvas.renderAll();
        }
    });

    canvas.on('mouse:up', function(o) {
        isDown = false;
        _unlockCanvas();

        if(modoActual === 'arrow' && currentShape) {
            var pointer = canvas.getPointer(o.e);
            var dx = pointer.x - origX;
            var dy = pointer.y - origY;

            // Descartar clicks accidentales (movimiento < 5px)
            if(Math.abs(dx) < 5 && Math.abs(dy) < 5) {
                canvas.remove(currentShape);
                currentShape = null;
                return;
            }

            // Reemplazar el preview por la flecha final (seleccionable)
            canvas.remove(currentShape);
            var finalArrow = _buildArrowPath(origX, origY, pointer.x, pointer.y, true);
            canvas.add(finalArrow);
            canvas.setActiveObject(finalArrow);
            
            // Guardar automáticamente
            guardarAnotacionesAutomatico();
        }
        
        // Finalizar rectángulo: hacerlo seleccionable y guardar
        if(modoActual === 'rect' && currentShape) {
            // El rect fue creado con selectable:false (durante el dibujo), hay que activarlo
            currentShape.set({
                selectable: true,
                evented: true,
                hasControls: false,
                hasBorders: true,
                borderColor: '#3498db',
                borderScaleFactor: 2,
                lockRotation: true,
                lockScalingX: true,
                lockScalingY: true,
                padding: 6
            });
            canvas.setActiveObject(currentShape);
            canvas.renderAll();
            guardarAnotacionesAutomatico();
        }
        
        currentShape = null;
        
        // Volver a modo select después de dibujar
        if(modoActual !== 'select') {
            modoActual = 'select';
            canvas.isDrawingMode = false;
            $('.btn-tool').removeClass('active');
            $('#btnSelect').addClass('active');
        }
    });
}

// ============================================
// COMENTARIOS
// ============================================

function agregarComentario(x, y) {
    var numeroRef = nextComRef++;

    // NO agregar globito aún — se agrega solo si se confirma
    // Mostrar modal para el mensaje
    $('#comentarioNumero').text(numeroRef);
    $('#comentarioMensaje').val('');
    $('#modalNuevoComentario').modal('show');
    $('#modalNuevoComentario').one('shown.bs.modal', function() {
        $('#comentarioMensaje').focus();
    });

    // Guardar posición temporal
    window.tempComentario = {x: x, y: y, numero_ref: numeroRef};

    // Si cancela el modal, revertir el contador
    $('#modalNuevoComentario').one('hidden.bs.modal', function() {
        if (window.tempComentario && window.tempComentario.numero_ref === numeroRef) {
            // No se guardó — revertir
            nextComRef = numeroRef;
            window.tempComentario = null;
        }
    });
}

function agregarGlobitoEnCanvas(x, y, numeroRef, comentarioId) {
    var r = 14;
    var circle = new fabric.Circle({
        radius: r,
        fill: '#e53e3e',
        stroke: '#9b2c2c',
        strokeWidth: 2,
        originX: 'center',
        originY: 'center',
        left: 0,
        top: 0
    });

    var fontSize = numeroRef.toString().length === 1 ? 15 : 12;
    var text = new fabric.Text(numeroRef.toString(), {
        fontSize: fontSize,
        fill: 'white',
        fontWeight: 'bold',
        fontFamily: 'Arial',
        originX: 'center',
        originY: 'center',
        left: 0,
        top: 0
    });

    var group = new fabric.Group([circle, text], {
        left: x,
        top: y,
        originX: 'center',
        originY: 'center',
        selectable: false,
        evented: true,
        hoverCursor: 'pointer',
        hasControls: false,
        hasBorders: false,
        commentRef: numeroRef,
        commentId: comentarioId || null
    });

    canvas.add(group);
    canvas.renderAll();

    group.on('mousedown', function() {
        mostrarDetalleComentario(numeroRef);
    });
}

// Enter para guardar comentario
$('#comentarioMensaje').on('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        $('#btnGuardarComentario').click();
    }
});

$('#btnGuardarComentario').click(function() {
    var mensaje = $('#comentarioMensaje').val().trim();
    
    if(!mensaje) {
        notificacion('warning', 'El mensaje no puede estar vacío.');
        return;
    }
    
    var temp = window.tempComentario;
    
    console.log('Guardando comentario:', {
        id_nota_ingreso: currentIdNota,
        tipo_archivo: currentTipoArchivo,
        pagina: currentPage,
        pos_x: temp.x,
        pos_y: temp.y,
        numero_ref: temp.numero_ref,
        mensaje: mensaje
    });
    
    $.ajax({
        url: '/notas-unificadas/pdf-anotaciones/guardar-comentario',
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: {
            id_nota_ingreso: currentIdNota,
            tipo_archivo: currentTipoArchivo,
            version_id: currentVersionId || '',
            pagina: currentPage,
            pos_x: temp.x,
            pos_y: temp.y,
            numero_ref: temp.numero_ref,
            mensaje: mensaje,
            padre_id: null
        },
        success: function(comentario) {
            console.log('Comentario guardado exitosamente:', comentario);
            comentariosDB.push(comentario);
            mostrarComentarios(comentariosDB);

            // Agregar globito ahora que se confirmó el guardado
            agregarGlobitoEnCanvas(temp.x, temp.y, temp.numero_ref, comentario.id);

            // Limpiar tempComentario ANTES de cerrar el modal para que
            // el handler hidden.bs.modal no revierta nextComRef
            window.tempComentario = null;

            $('#modalNuevoComentario').modal('hide');
        },
        error: function(xhr, status, error) {
            console.error('Error al guardar comentario:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            notificacion('error', 'Error al guardar el comentario: ' + (xhr.responseJSON?.error || error));
        }
    });
});

function _avatarHtml(userImagen, size) {
    size = size || 32;
    if (userImagen) {
        return '<img src="data:image/jpeg;base64,' + userImagen + '" style="width:' + size + 'px; height:' + size + 'px; border-radius:50%; object-fit:cover; flex-shrink:0;">';
    }
    return '<div style="width:' + size + 'px; height:' + size + 'px; border-radius:50%; background:#bdc3c7; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fa fa-user" style="color:white; font-size:' + (size * 0.45) + 'px;"></i></div>';
}

function mostrarComentarios(comentarios) {
    var html = '';

    comentarios.forEach(function(c) {
        if(c.padre_id) return;

        var resueltoStyle = c.resuelto ? 'opacity:0.5;' : '';
        var resueltoText = c.resuelto ? 'text-decoration:line-through;' : '';

        html += '<div class="comentario-item" data-id="' + c.id + '" style="margin-bottom:12px; padding:10px; background:#f8f9fa; border-radius:8px; border-left:3px solid #e53e3e; ' + resueltoStyle + '">';

        // Header con avatar + badge + nombre
        html += '<div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">';
        html += '<span class="badge" style="background:#e53e3e; font-size:11px; min-width:22px;">' + c.numero_ref + '</span>';
        html += _avatarHtml(c.user_imagen, 28);
        html += '<div style="flex:1; min-width:0;">';
        html += '<strong style="font-size:12px;">' + c.usuario + '</strong> ';
        html += '<small class="text-muted">Pág. ' + c.pagina + '</small>';
        if(currentVersionLabel) html += ' <span style="background:#3b82f6; color:#fff; font-size:9px; padding:1px 5px; border-radius:3px;">' + currentVersionLabel + '</span>';
        html += '</div>';
        html += '</div>';

        // Mensaje
        html += '<div style="margin-left:60px; font-size:13px; ' + resueltoText + '">' + c.mensaje + '</div>';

        // Acciones (autor propio o admin puede eliminar)
        if (window.PUEDE_ELIMINAR || c.id_usuario == window.CURRENT_USER_ID) {
            html += '<div style="margin-left:60px; margin-top:6px; display:flex; gap:6px; align-items:center;">';
            html += '<button class="btn btn-xs btn-danger btn-eliminar" data-id="' + c.id + '" title="Eliminar"><i class="fa fa-trash"></i></button>';
            html += '</div>';
        }

        // Respuestas existentes + campo de reply (siempre visible)
        html += '<div style="margin-left:40px; margin-top:8px; border-left:2px solid #e2e8f0; padding-left:10px;">';
        if(c.respuestas && c.respuestas.length > 0) {
            c.respuestas.forEach(function(r) {
                html += '<div style="display:flex; align-items:flex-start; gap:8px; margin-bottom:6px;">';
                html += _avatarHtml(r.user_imagen, 24);
                html += '<div style="flex:1; background:#fff; padding:6px 10px; border-radius:8px; border:1px solid #e5e7eb;">';
                html += '<strong style="font-size:11px;">' + r.usuario + '</strong>';
                html += '<div style="font-size:12px; color:#374151;">' + r.mensaje + '</div>';
                html += '</div></div>';
            });
        }
        // Input de reply siempre visible
        html += '<div style="display:flex; align-items:center; gap:6px; margin-top:4px;">';
        html += '<input type="text" class="form-control input-sm input-reply-inline" data-id="' + c.id + '" data-ref="' + c.numero_ref + '" placeholder="Responder..." style="flex:1; border-radius:20px; font-size:12px; height:30px;">';
        html += '<button class="btn btn-xs btn-default btn-enviar-reply" data-id="' + c.id + '" data-ref="' + c.numero_ref + '" style="border:1px solid #ccc; border-radius:4px; padding:2px 8px;"><i class="fa fa-paper-plane" style="color:#e53e3e;"></i></button>';
        html += '</div>';
        html += '</div>';

        html += '</div>';
    });

    $('#listaComentarios').html(html || '<p class="text-muted text-center">No hay comentarios aún</p>');
    if(window._comentariosComparacion && window._comentariosComparacion.length > 0) {
        _appendComentariosComparacion(window._comentariosComparacion);
    }
}

// Muestra los comentarios de la versión comparada (solo lectura) al final del panel
window.mostrarComentariosComparacion = function(comentarios, versionLabel) {
    window._comentariosComparacion = comentarios || [];
    window._comparacionVersionLabel = versionLabel || '';
    // Quitar sección previa de comparación
    $('#seccionComentariosComparacion').remove();
    if(!comentarios || comentarios.length === 0) return;
    _appendComentariosComparacion(comentarios, versionLabel);
};

function _appendComentariosComparacion(comentarios, versionLabel) {
    $('#seccionComentariosComparacion').remove();
    if(!comentarios || comentarios.length === 0) return;
    versionLabel = versionLabel || window._comparacionVersionLabel || '';
    var html = '<div id="seccionComentariosComparacion">';
    html += '<hr style="margin:8px 0;"><p class="text-muted"><small><i class="fa fa-eye"></i> Versión comparada' + (versionLabel ? ' (' + versionLabel + ')' : '') + ' (solo lectura)</small></p>';
    comentarios.forEach(function(c) {
        if(c.padre_id) return;
        var resuelto = c.resuelto ? 'style="opacity:0.6; text-decoration:line-through;"' : '';
        html += '<div class="comentario-item" ' + resuelto + '>';
        html += '<div class="comentario-header"><span class="badge">' + c.numero_ref + '</span> <strong>' + c.usuario + '</strong> <small class="text-muted">Pág. ' + c.pagina + '</small>';
        if(versionLabel) html += ' <span style="background:#f59e0b; color:#fff; font-size:9px; padding:1px 5px; border-radius:3px;">' + versionLabel + '</span>';
        html += '</div>';
        html += '<div class="comentario-body">' + c.mensaje + '</div>';
        if(c.respuestas && c.respuestas.length > 0) {
            html += '<div class="comentario-respuestas" style="margin-left:15px; margin-top:5px;">';
            c.respuestas.forEach(function(r) {
                html += '<div class="well well-sm" style="margin-bottom:4px; padding:5px;"><strong>' + r.usuario + ':</strong> ' + r.mensaje + '</div>';
            });
            html += '</div>';
        }
        html += '</div>';
    });
    html += '</div>';
    $('#listaComentarios').append(html);
}

$(document).on('click', '.btn-resolver', function() {
    var id = $(this).data('id');
    var comentario = comentariosDB.find(c => c.id == id);
    var nuevoEstado = comentario.resuelto ? 0 : 1;
    
    $.ajax({
        url: '/notas-unificadas/pdf-anotaciones/resolver-comentario',
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: {id: id, resuelto: nuevoEstado},
        success: function() {
            comentario.resuelto = nuevoEstado;
            mostrarComentarios(comentariosDB);
        }
    });
});

// Eliminar comentario
$(document).on('click', '.btn-eliminar', function() {
    var id = $(this).data('id');
    
    if(!confirm('¿Eliminar este comentario?')) return;
    
    $.ajax({
        url: '/notas-unificadas/pdf-anotaciones/eliminar-comentario/' + id,
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function() {
            comentariosDB = comentariosDB.filter(c => c.id != id);
            mostrarComentarios(comentariosDB);
            
            // Recolectar primero, luego eliminar (no modificar array durante iteración)
            var toRemove = canvas.getObjects().filter(function(obj) {
                return obj.commentId == id;
            });
            toRemove.forEach(function(obj) { canvas.remove(obj); });
            canvas.renderAll();
        },
        error: function() {
            notificacion('error', 'Error al eliminar el comentario.');
        }
    });
});

// Enviar reply con click en botón
$(document).on('click', '.btn-enviar-reply', function() {
    var padreId = $(this).data('id');
    var numeroRef = $(this).data('ref');
    var $input = $(this).siblings('.input-reply-inline');
    _enviarReply(padreId, numeroRef, $input);
});

// Enviar reply con Enter
$(document).on('keydown', '.input-reply-inline', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        var padreId = $(this).data('id');
        var numeroRef = $(this).data('ref');
        _enviarReply(padreId, numeroRef, $(this));
    }
});

function _enviarReply(padreId, numeroRef, $input) {
    var mensaje = $input.val().trim();
    if (!mensaje) return;
    $input.prop('disabled', true);

    $.ajax({
        url: '/notas-unificadas/pdf-anotaciones/guardar-comentario',
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: {
            id_nota_ingreso: currentIdNota,
            tipo_archivo: currentTipoArchivo,
            version_id: currentVersionId || '',
            pagina: currentPage,
            numero_ref: numeroRef,
            mensaje: mensaje,
            padre_id: padreId,
            pos_x: 0,
            pos_y: 0
        },
        success: function(respuesta) {
            comentariosDB.forEach(function(c) {
                if(c.id == padreId) {
                    c.respuestas = c.respuestas || [];
                    c.respuestas.push(respuesta);
                }
            });
            mostrarComentarios(comentariosDB);
        },
        error: function() {
            notificacion('error', 'Error al guardar la respuesta.');
            $input.prop('disabled', false);
        }
    });
}

function mostrarDetalleComentario(numeroRef) {
    var comentario = comentariosDB.find(c => c.numero_ref == numeroRef);
    if(comentario) {
        // Scroll al comentario en la lista
        var elemento = $(`.comentario-item[data-id="${comentario.id}"]`);
        if(elemento.length) {
            $('#listaComentarios').animate({
                scrollTop: elemento.offset().top - $('#listaComentarios').offset().top + $('#listaComentarios').scrollTop()
            }, 500);
            elemento.effect('highlight', {}, 1000);
        }
    }
}

// ============================================
// NAVEGACIÓN Y GUARDADO
// ============================================

$('#btnPrevPage').click(function() {
    if(currentPage > 1) {
        guardarAnotacionesPagina(currentPage);
        currentPage--;
        renderPage(pdfDoc, currentPage, [], comentariosDB);
        $('#pageInfo').text('Página ' + currentPage + ' de ' + totalPages);
        if(typeof window.actualizarOnionPagina === 'function') window.actualizarOnionPagina(currentPage);
    }
});

$('#btnNextPage').click(function() {
    if(currentPage < totalPages) {
        guardarAnotacionesPagina(currentPage);
        currentPage++;
        renderPage(pdfDoc, currentPage, [], comentariosDB);
        $('#pageInfo').text('Página ' + currentPage + ' de ' + totalPages);
        if(typeof window.actualizarOnionPagina === 'function') window.actualizarOnionPagina(currentPage);
    }
});

var saveTimeout = null;

function guardarAnotacionesAutomatico() {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(function() {
        guardarAnotacionesPagina(currentPage);
    }, 1000);
}

function guardarAnotacionesPagina(pageNum, generarLog = false) {
    if(!canvas) return;
    
    var json = JSON.stringify(canvas.toJSON(['commentRef', 'commentId']));
    
    $.ajax({
        url: '/notas-unificadas/pdf-anotaciones/guardar-anotaciones',
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: {
            id_nota_ingreso: currentIdNota,
            tipo_archivo: currentTipoArchivo,
            version_id: currentVersionId || '',
            pagina: pageNum,
            anotaciones: json,
            generar_log: generarLog ? 1 : 0
        },
        success: function() {
            if(generarLog) console.log('Anotaciones guardadas con LOG');
            else console.log('Anotaciones autoguardadas');
        }
    });
}

$('#btnGuardarTodo').click(function() {
    guardarAnotacionesPagina(currentPage, true); // Log Manual
    notificacion('success', 'Cambios guardados correctamente.');
    $('#modalEditorAnotaciones').modal('hide');
    if(canvas) { canvas.dispose(); canvas = null; }
    pdfDoc = null; currentIdNota = null; currentTipoArchivo = null; currentVersionId = null;
});

$('#btnCerrarEditor').click(function() {
    guardarAnotacionesPagina(currentPage, true); // Log al cerrar
    $('#modalEditorAnotaciones').modal('hide');
    
    // Limpiar
    if(canvas) {
        canvas.dispose();
        canvas = null;
    }
    pdfDoc = null;
    currentIdNota = null;
    currentTipoArchivo = null;
    currentVersionId = null;
});

// Borrar elemento seleccionado
$(document).keydown(function(e) {
    if((e.key === 'Delete' || e.key === 'Backspace') && canvas) {
        var activeObject = canvas.getActiveObject();
        if(activeObject && !activeObject.commentRef) {
            canvas.remove(activeObject);
            guardarAnotacionesAutomatico();
        }
    }
});

// ============================================
// SUBIR NUEVA VERSIÓN DESDE EL EDITOR
// ============================================

$('#btnSubirNuevaVersion').on('click', function() {
    $('#inputNuevaVersion').val('').trigger('click');
});

$('#inputNuevaVersion').on('change', function() {
    var file = this.files[0];
    if (!file) return;
    if (!currentIdNota || !currentTipoArchivo) {
        notificacion('error', 'Error: no hay nota/tipo activo.');
        return;
    }

    var formData = new FormData();
    formData.append('archivo', file);
    formData.append('id_nota', currentIdNota);
    formData.append('tipo_archivo', currentTipoArchivo);
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

    var $btn = $('#btnSubirNuevaVersion');
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Subiendo...');

    $.ajax({
        url: '/notas-unificadas/pdf-anotaciones/subir-version',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.success) {
                // Recargar el editor con la nueva versión
                abrirEditorPdf(currentIdNota, currentTipoArchivo, res.version_id);
                // Notificar (usar la función del wizard si está disponible, sino alert)
                notificacion('success', res.msg || 'Nueva versión subida');
            } else {
                notificacion('error', res.msg || 'Error al subir');
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Error al subir archivo';
            notificacion('error', msg);
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Nueva versión');
            $('#inputNuevaVersion').val('');
        }
    });
});
