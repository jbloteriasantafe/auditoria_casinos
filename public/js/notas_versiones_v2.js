// SCRIPT PARA MOSTRAR VERSIONES EN COMPARACIÓN
console.log('Script de versiones V2 cargado (con onion skin)');

// Variables globales para onion skin
var onionCanvas = null;
var onionPdf = null;
var currentOnionPage = 1;

window.poblarSelectComparacion = function(nota) {
    var select = $('#select-comparar');
    select.empty();
    select.append('<option value="">Seleccionar versión anterior...</option>');
    
    // INTENTAR OBTENER ID DE MULTIPLES FUENTES
    var idNota = null;
    if (nota && nota.id) idNota = nota.id;
    else if (nota && nota.id_nota_ingreso) idNota = nota.id_nota_ingreso;
    else if (typeof currentIdNota !== 'undefined' && currentIdNota) idNota = currentIdNota;
    
    var tipo = null;
    if (typeof currentTipoArchivo !== 'undefined' && currentTipoArchivo) tipo = currentTipoArchivo;
    
    console.log('DEBUG VERSIONES => Objeto nota:', nota, 'ID final:', idNota, 'Tipo:', tipo);
    
    if(!idNota || !tipo) {
        console.error('ERROR: No se pudo determinar ID de nota o tipo de archivo para versiones');
        return;
    }
    
    $.ajax({
        url: `/notas-unificadas/historial-versiones/${idNota}/${tipo}`,
        method: 'GET',
        success: function(response) {
            console.log('Versiones recibidas:', response);
            
            if(response.success && response.versiones && response.versiones.length > 0) {
                var versionesDisponibles = response.versiones;
                
                if(versionesDisponibles.length <= 1) {
                    select.append('<option disabled>No hay versiones anteriores disponibles</option>');
                } else {
                    versionesDisponibles.forEach(function(v, index) {
                        // Omitir la versión más reciente (index 0)
                        if(index === 0) return; 
                        
                        var label = `Versión ${v.version} - ${v.nombre_original} (${v.created_at})`;
                        select.append(`<option value="version-${v.id}" data-id="${v.id}">Versión ${v.version} (${v.created_at})</option>`);
                    });
                }
            } else {
                select.append('<option disabled>No hay historial de versiones</option>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error cargando versiones:', error);
            select.append('<option disabled>Error al cargar versiones</option>');
        }
    });

    // EVENTO DE CAMBIO EN EL SELECT (ONION SKIN)
    select.off('change').on('change', function() {
        var valor = $(this).val();
        if(!valor) {
            limpiarOnionSkin();
            return;
        }

        // Si es una versión
        if(valor.startsWith('version-')) {
            var idVersion = $(this).find(':selected').data('id'); 
            // Como el value es "version-ID", podemos extraer el ID también del value
            if(!idVersion) idVersion = valor.replace('version-', '');
            
            activarOnionSkin(idVersion);
        }
    });

    // Evento Checkbox Comparar
    $('#check-comparar').off('change').on('change', function() {
        if($(this).is(':checked')) {
            $('#controles-comparacion').show();
        } else {
            $('#controles-comparacion').hide();
            $('#select-comparar').val(''); // Reset select
            limpiarOnionSkin(); // Clean overlay
        }
    });

    // Resetear estado inicial al abrir (ocultar si no está chequeado)
    if(!$('#check-comparar').is(':checked')) {
        $('#controles-comparacion').hide();
    }

    // Evento Slider Opacidad
    $('#slider-opacidad').off('input change').on('input change', function() {
        var opacidad = $(this).val();
        $('#onionCanvas').css('opacity', opacidad);
    });
};

function activarOnionSkin(idVersion) {
    console.log("Activando Onion Skin para versión:", idVersion);
    var url = `/notas-unificadas/visualizar-version/${idVersion}`;
    console.log("URL de la versión:", url);
    cargarPdfOnion(url);
}
function cargarPdfOnion(url) {
    pdfjsLib.getDocument(url).promise.then(function(pdf) {
        onionPdf = pdf;
        currentOnionPage = currentPage || 1; // Usar página actual del editor
        renderOnionLayer(currentOnionPage);
    });
}

function renderOnionLayer(pageNum) {
    if(!onionPdf) return;
    
    onionPdf.getPage(pageNum).then(function(page) {
        var scale = 1.5; // Debe coincidir con el scale del editor principal
        var viewport = page.getViewport({scale: scale});
        
        // Usar contenedor principal directo
        var container = $('#canvasContainer');
        var onionCanvasEl = $('#onionCanvas');
        
        // Asegurar que el contenedor tenga position relative
        if(container.css('position') === 'static') {
            container.css('position', 'relative');
        }

        if(onionCanvasEl.length === 0) {
            // Crear canvas
            container.append('<canvas id="onionCanvas" style="position: absolute; top: 0; left: 0; z-index: 10000; pointer-events: none; opacity: 0.5; border: 4px solid green;"></canvas>');
            onionCanvasEl = $('#onionCanvas');
        }
        
        var canvas = onionCanvasEl[0];
        var ctx = canvas.getContext('2d');
        
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        
        // Importante: dimensiones CSS deben coincidir
        $(canvas).css({
            width: viewport.width + 'px',
            height: viewport.height + 'px'
        });
        
        var renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };
        
        console.log('Iniciando renderizado Onion...');
        
        page.render(renderContext).promise.then(function() {
            console.log('Capa Onion renderizada');
            onionCanvasEl.show();
            // Asegurar que el contenedor padre (si existe, ej: .onion-layer) también sea visible
            onionCanvasEl.parents('.onion-layer').show();
        });
    });
}

function limpiarOnionSkin() {
    $('#onionCanvas').remove();
    onionPdf = null;
}

// Hook para actualizar onion skin cuando cambia la página en el editor principal
// Esto requiere que el editor principal llame a un evento o variable global, 
// pero por ahora lo dejamos simple.
