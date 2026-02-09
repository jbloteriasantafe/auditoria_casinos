// Helper para getCurrentTipoNombre
function getCurrentTipoNombre() {
    var nombres = {
        'solicitud': 'Solicitud',
        'diseno': 'Diseño',
        'bases': 'Bases',
        'varios': 'Varios',
        'informe': 'Informe'
    };
    return nombres[currentTipoArchivo] || currentTipoArchivo;
}

// Actualizar función poblarSelectComparacion
var poblarSelectComparacion = function(nota) {
    var select = $('#select-comparar');
    select.empty();
    select.append('<option value="">Seleccionar PDF...</option>');
    
    // Lista de tipos de archivo posibles
    var tiposArchivo = [
        {campo: 'path_solicitud', tipo: 'solicitud', nombre: 'Solicitud'},
        {campo: 'path_diseno', tipo: 'diseno', nombre: 'Diseño'},
        {campo: 'path_bases', tipo: 'bases', nombre: 'Bases y Condiciones'},
        {campo: 'path_varios', tipo: 'varios', nombre: 'Archivos Varios'},
        {campo: 'path_informe', tipo: '

informe', nombre: 'Informe Técnico'}
    ];
    
    tiposArchivo.forEach(function(archivo) {
        // Solo agregar si existe y no es el archivo actual
        if(nota[archivo.campo] && archivo.tipo !== currentTipoArchivo) {
            select.append(`<option value="${archivo.tipo}" data-id="${nota.id}">${archivo.nombre}</option>`);
        }
    });
    
    // Cargar versiones del archivo actual
    $.get(`/notas-unificadas/versiones/${nota.id}/${currentTipoArchivo}`, function(response) {
        if(response.success && response.versiones.length > 1) {
            select.append('<optgroup label="───────────────"></optgroup>');
            select.append('<optgroup label="Versiones Anteriores"></optgroup>');
            response.versiones.forEach(function(v, index) {
                // Saltar la versión actual (la primera/más reciente)
                if(index > 0) {
                    select.append(`<option value="version-${v.id}">${getCurrentTipoNombre()} - v${v.version} (${v.created_at})</option>`);
                }
            });
        }
    }).fail(function() {
        console.log('No se pudieron cargar versiones');
    });
};
