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
var currentNroNota = '';
var comentariosDB = [];
var nextComRef = 1;

// ============================================
// INICIALIZACIÓN
// ============================================

$(document).on('click', '.btn-agregar-observaciones', function() {
    var idNota = $(this).data('id');
    var nroNota = $(this).data('nro-nota');
    
    currentIdNota = idNota;
    currentNroNota = nroNota;
    
    // Cargar lista de PDFs disponibles
    $.get(`/notas-unificadas/pdf-anotaciones/listar/${idNota}`, function(pdfs) {
        if(pdfs.length === 0) {
            alert('Esta nota no tiene PDFs adjuntos para anotar.');
            return;
        }
        
        mostrarSelectorPdfs(pdfs);
    }).fail(function() {
        alert('Error al cargar los PDFs de esta nota.');
    });
});

function mostrarSelectorPdfs(pdfs) {
    var html = '<div class="list-group" style="max-height: 400px; overflow-y: auto;">';
    
    pdfs.forEach(function(pdf) {
        var icon = pdf.nombre.includes('Solicitud') ? 'file-pdf-o' : 
                   pdf.nombre.includes('Diseño') ? 'image' : 
                   pdf.nombre.includes('Bases') ? 'file-text-o' : 
                   pdf.nombre.includes('Varios') ? 'archive' : 
                   'file-pdf-o';
        
        html += `
            <a href="javascript:void(0)" class="list-group-item pdf-selector-item" 
               data-tipo="${pdf.tipo}" 
               style="cursor: pointer; transition: all 0.2s;">
                <i class="fa fa-${icon} fa-2x pull-left" style="margin-right: 15px; color: #e74c3c;"></i>
                <h4 class="list-group-item-heading">${pdf.nombre}</h4>
                <p class="list-group-item-text text-muted">${pdf.archivo}</p>
            </a>
        `;
    });
    
    html += '</div>';
    
    $('#listaPdfsDisponibles').html(html);
    $('#modalSelectorPdfs').modal('show');
}

$(document).on('click', '.pdf-selector-item', function() {
    var tipo = $(this).data('tipo');
    $('#modalSelectorPdfs').modal('hide');
    abrirEditorPdf(currentIdNota, tipo);
});

function abrirEditorPdf(idNota, tipo) {
    currentTipoArchivo = tipo;
    
    // Mostrar modal primero (sin reemplazar contenido)
    $('#modalEditorAnotaciones').modal('show');
    
    // Mostrar overlay de loading sin eliminar el canvas
    var loadingOverlay = $('<div id="pdfLoadingOverlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(52, 73, 94, 0.95); z-index: 10000; display: flex; align-items: center; justify-content: center;"><div class="text-center" style="color: white;"><i class="fa fa-spinner fa-spin fa-3x"></i><p style="margin-top: 20px; font-size: 16px;">Cargando PDF...</p></div></div>');
    $('#editorContent').append(loadingOverlay);
    
    $('#modalEditorAnotaciones').on('shown.bs.modal', function onModalShown() {
        $(this).off('shown.bs.modal', onModalShown);
        console.log('Modal mostrado, cargando PDF...');
        $.get(`/notas-unificadas/pdf-anotaciones/datos/${idNota}/${tipo}`, function(data) {
            console.log('Datos del PDF recibidos:', data);
            window.notaActual = data.nota;
            $('#pdfLoadingOverlay').remove();
            inicializarEditor(data);
            setTimeout(function() {
                if(typeof poblarSelectComparacion === 'function') poblarSelectComparacion(data.nota);
            }, 500);
        }).fail(function(xhr, status, error) {
            console.error('Error al cargar PDF:', status, error);
            $('#pdfLoadingOverlay').remove();
            alert('Error al cargar el PDF: ' + error);
            $('#modalEditorAnotaciones').modal('hide');
        });
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
    $('#editorTitulo').html(`
        <i class="fa fa-file-pdf-o"></i> 
        Nota #${currentNroNota} - ${data.nota.titulo || 'Sin título'}
        <small class="text-muted"> | ${getCurrentTipoNombre()}</small>
    `);
    
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
        alert('Error al cargar el PDF: ' + error.message);
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
                selectable: true
            });
        } else if(obj.type === 'rect') {
            obj.set({
                stroke: '#ff0000',
                strokeWidth: 3,
                fill: 'transparent',
                selectable: true
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
    comentariosDB.forEach(function(c) {
        if(c.pagina == pageNum && c.pos_x && c.pos_y) {
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

// Definir eventos del canvas (se adjuntarán después de que canvas se inicialice)
function attachCanvasEvents() {
    if (!canvas) {
        console.warn('Canvas no inicializado, no se pueden adjuntar eventos');
        return;
    }
    
    canvas.on('mouse:down', function(o) {
        if(modoActual === 'select') return;
        
        isDown = true;
        var pointer = canvas.getPointer(o.e);
        origX = pointer.x;
        origY = pointer.y;
        
        if(modoActual === 'arrow') {
            // Crear solo línea durante el dibujo
            var points = [origX, origY, origX, origY];
            currentShape = new fabric.Line(points, {
                strokeWidth: 4,
                fill: '#ff0000',
                stroke: '#ff0000',
                originX: 'center',
                originY: 'center',
                selectable: false,
                evented: false
            });
            
            canvas.add(currentShape);
            
        } else if(modoActual === 'rect') {
            currentShape = new fabric.Rect({
                left: origX,
                top: origY,
                width: 0,
                height: 0,
                stroke: '#ff0000',
                strokeWidth: 3,
                fill: 'transparent',
                selectable: true
            });
            canvas.add(currentShape);
            
        } else if(modoActual === 'comment') {
            agregarComentario(origX, origY);
            isDown = false;
        }
    });
    
    canvas.on('mouse:move', function(o) {
        if(!isDown || modoActual === 'select' || modoActual === 'comment') return;
        
        var pointer = canvas.getPointer(o.e);
        
        if(modoActual === 'arrow' && currentShape) {
            // Solo actualizar el endpoint de la línea
            currentShape.set({ x2: pointer.x, y2: pointer.y });
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
    
    canvas.on('mouse:up', function() {
        isDown = false;
        
        // Agrupar flecha cuando se termina de dibujar
        if(modoActual === 'arrow' && currentShape) {
            let line = currentShape;
            let dx = line.x2 - line.x1;
            let dy = line.y2 - line.y1;
            
            // Evitar clicks accidentales
            if(Math.abs(dx) < 5 && Math.abs(dy) < 5) {
                canvas.remove(line);
                if(line.arrowHead) canvas.remove(line.arrowHead);
                currentShape = null;
                return;
            }
            
            // Crear grupo con la línea y la punta
            let angle = Math.atan2(dy, dx) * 180 / Math.PI + 90;
            var head = new fabric.Triangle({
                fill: '#ff0000',
                width: 20,
                height: 20,
                left: line.x2,
                top: line.y2,
                angle: angle,
                originX: 'center',
                originY: 'center'
            });
            
            var group = new fabric.Group([line, head], {
                selectable: true
            });
            
            canvas.remove(line);
            if(line.arrowHead) canvas.remove(line.arrowHead);
            canvas.add(group);
            canvas.setActiveObject(group);
            
            // Guardar automáticamente
            guardarAnotacionesAutomatico();
        }
        
        // Guardar rectángulos también
        if(modoActual === 'rect' && currentShape) {
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
    
    // Agregar globito visual
    agregarGlobitoEnCanvas(x, y, numeroRef);
    
    // Mostrar modal para el mensaje
    $('#comentarioNumero').text(numeroRef);
    $('#comentarioMensaje').val('');
    $('#modalNuevoComentario').modal('show');
    
    // Guardar posición temporal
    window.tempComentario = {x: x, y: y, numero_ref: numeroRef};
}

function agregarGlobitoEnCanvas(x, y, numeroRef, comentarioId) {
    var circle = new fabric.Circle({
        left: x - 20,
        top: y - 20,
        radius: 20,
        fill: '#ff0000',
        stroke: '#cc0000',
        strokeWidth: 3,
        originX: 'center',
        originY: 'center'
    });
    
    var text = new fabric.Text(numeroRef.toString(), {
        left: x - 8,
        top: y - 12,
        fontSize: 20,
        fill: 'white',
        fontWeight: 'bold',
        fontFamily: 'Arial',
        originX: 'center',
        originY: 'center'
    });
    
    var group = new fabric.Group([circle, text], {
        left: x,
        top: y,
        selectable: false,  // NO se puede seleccionar ni mover
        hoverCursor: 'pointer',
        commentRef: numeroRef,
        commentId: comentarioId || null
    });
    
    canvas.add(group);
    canvas.renderAll();
    
    // Click en globito para ver comentario
    group.on('mousedown', function() {
        mostrarDetalleComentario(numeroRef);
    });
}

$('#btnGuardarComentario').click(function() {
    var mensaje = $('#comentarioMensaje').val().trim();
    
    if(!mensaje) {
        alert('El mensaje no puede estar vacío');
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
            $('#modalNuevoComentario').modal('hide');
            
            // Actualizar globito con ID real
            canvas.getObjects().forEach(function(obj) {
                if(obj.commentRef === temp.numero_ref && !obj.commentId) {
                    obj.commentId = comentario.id;
                }
            });
        },
        error: function(xhr, status, error) {
            console.error('Error al guardar comentario:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            alert('Error al guardar el comentario: ' + (xhr.responseJSON?.error || error));
        }
    });
});

function mostrarComentarios(comentarios) {
    var html = '';
    
    comentarios.forEach(function(c) {
        if(c.padre_id) return; // Solo hilos principales
        
        var resuelto = c.resuelto ? 'style="opacity: 0.6; text-decoration: line-through;"' : '';
        
        html += `
            <div class="comentario-item" data-id="${c.id}" ${resuelto}>
                <div class="comentario-header">
                    <span class="badge" style="background: #f39c12;">${c.numero_ref}</span>
                    <strong>${c.usuario}</strong>
                    <small class="text-muted">Pág. ${c.pagina}</small>
                </div>
                <div class="comentario-body">
                    ${c.mensaje}
                </div>
                <div class="comentario-actions">
                    <button class="btn btn-xs btn-resolver" data-id="${c.id}" title="${c.resuelto ? 'Reabrir' : 'Resolver'}">
                        <i class="fa fa-${c.resuelto ? 'undo' : 'check'}"></i>
                    </button>
                    <button class="btn btn-xs btn-danger btn-eliminar" data-id="${c.id}" title="Eliminar">
                        <i class="fa fa-trash"></i>
                    </button>
                    <button class="btn btn-xs btn-primary btn-responder" data-id="${c.id}" data-ref="${c.numero_ref}" title="Responder">
                        <i class="fa fa-reply"></i>
                    </button>
                </div>
        `;
        
        // Mostrar respuestas si existen
        if(c.respuestas && c.respuestas.length > 0) {
            html += '<div class="comentario-respuestas" style="margin-left: 20px; margin-top: 10px;">';
            c.respuestas.forEach(function(r) {
                html += `
                    <div class="comentario-respuesta" style="background: #f8f9fa; padding: 8px; margin-bottom: 5px; border-radius: 5px;">
                        <strong style="font-size: 11px;">${r.usuario}:</strong>
                        <p style="margin: 5px 0; font-size: 12px;">${r.mensaje}</p>
                    </div>
                `;
            });
            html += '</div>';
        }
        
        html += '</div>';
    });
    
    $('#listaComentarios').html(html || '<p class="text-muted text-center">No hay comentarios aún</p>');
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
            
            canvas.getObjects().forEach(function(obj) {
                if(obj.commentId == id) canvas.remove(obj);
            });
            canvas.renderAll();
        },
        error: function() {
            alert('Error al eliminar el comentario');
        }
    });
});

// Responder a comentario
$(document).on('click', '.btn-responder', function() {
    var padreId = $(this).data('id');
    var numeroRef = $(this).data('ref');
    
    var mensaje = prompt('Escribe tu respuesta:');
    if(!mensaje) return;
    
    $.ajax({
        url: '/notas-unificadas/pdf-anotaciones/guardar-comentario',
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: {
            id_nota_ingreso: currentIdNota,
            tipo_archivo: currentTipoArchivo,
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
            alert('Error al guardar la respuesta');
        }
    });
});

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
        $('#pageInfo').text(`Página ${currentPage} de ${totalPages}`);
    }
});

$('#btnNextPage').click(function() {
    if(currentPage < totalPages) {
        guardarAnotacionesPagina(currentPage);
        currentPage++;
        renderPage(pdfDoc, currentPage, [], comentariosDB);
        $('#pageInfo').text(`Página ${currentPage} de ${totalPages}`);
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
    
    var json = JSON.stringify(canvas.toJSON());
    
    $.ajax({
        url: '/notas-unificadas/pdf-anotaciones/guardar-anotaciones',
        method: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        data: {
            id_nota_ingreso: currentIdNota,
            tipo_archivo: currentTipoArchivo,
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
    alert('Cambios guardados correctamente');
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
