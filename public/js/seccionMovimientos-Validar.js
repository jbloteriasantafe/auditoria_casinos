//BOTÓN VALIDACION, DENTRO DE LA TABLA PRINCIPÁL
$(document).on('click', '.boton_validar', function () {
    $('#tablaFechasFiscalizacion tbody tr').remove();
    $('#tablaMaquinasFiscalizacion tbody tr').remove();
    
    const id_log_movimiento = $(this).parent().parent().attr('id');
    $.get('movimientos/obtenerFiscalizacionesMovimiento/' + id_log_movimiento, function (data) {
        let tablaFiscalizacion = $('#tablaFechasFiscalizacion tbody');
        data.fiscalizaciones.forEach(f => {
            let fila = generarFilaFechaFiscalizacion(f.id_fiscalizacion_movimiento,f.id_estado_fiscalizacion,f.fecha_envio_fiscalizar);
            tablaFiscalizacion.append(fila);
        });
        divRelMovLimpiar();
        divRelMovCargarRelevamientos([],{},-1);
        divRelMovEsconderDetalleRelevamiento();
        divRelMovSetearTipo(data.tipo_mov,data.sentido);
        divRelMovSetearExp(data.nro_exp_org,data.nro_exp_interno,data.nro_exp_control);
        $('#modalValidacion').modal('show');
    });
});

function mostrarFiscalizacion(id_fiscalizacion){
    divRelMovEsconderDetalleRelevamiento();
    $.get('movimientos/obtenerRelevamientosFiscalizacion/' + id_fiscalizacion, function(data){
      divRelMovSetearUsuarios(data.casino,data.cargador,data.fiscalizador);
      divRelMovSetearTipo(data.tipo_movimiento,data.sentido);
      divRelMovSetearExp(data.nro_exp_org,data.nro_exp_interno,data.nro_exp_control);
      let dibujos = {3 : 'fa-check', 4 : 'fa-search-plus'};
      divRelMovCargarRelevamientos(data.relevamientos,dibujos,4);
      divRelMovSetearModo('VER');
      $('#modalValidacion').modal('show');
    });
}

//BOTON PARA VER EL LISTADO DE LAS MÁQUINAS FISCALIZADAS ESA FECHA
$(document).on('click', '.detalleMov', function () {
    mostrarFiscalizacion($(this).attr('data-id-fiscalizacion'));
});

$(document).on('click','#divRelMov .cargarMaq',function(){
    const id_rel = $(this).attr('data-rel');
    const toma = $(this).attr('toma');
    $('#modalValidacion').attr('data-rel', id_rel);
    $.get('movimientos/obtenerRelevamientoToma/' + id_rel + '/' + toma, function(data){
        divRelMovSetearModo(data.relevamiento.id_estado_relevamiento == 3? 'VALIDAR' : 'VER');
        divRelMovSetearExp(data.nro_exp_org,data.nro_exp_interno,data.nro_exp_control);
        divRelMovSetear(data);
        divRelMovMostrarDetalleRelevamiento();
    });
});

//BOTÓN VALIDAR DENTRO DEL MODAL VALIDAR
$(document).on('click', '#divRelMov .validar', function () {
    const id_relevamiento = $('#modalValidacion').attr('data-rel');
    validar(id_relevamiento, 'valido');
});

//BOTÓN ERROR
$(document).on('click', '#divRelMov .error', function () {
    const id_relevamiento = $('#modalValidacion').attr('data-rel');
    validar(id_relevamiento, 'error');
});

//cuando cierra el modal de validación, actualizo el listado
$("#modalValidacion").on('hidden.bs.modal', function () {
    $('#btn-buscarMovimiento').trigger('click');
})

//POST PARA VALIDAR
function validar(id_rel, val) {
    const datos = divRelMovObtenerDatos();
    const formData = {
        id_relev_mov: id_rel,
        observacion: datos.observacionesAdm,
        nro_exp_org: datos.nro_exp_org,
        nro_exp_interno: datos.nro_exp_interno,
        nro_exp_control: datos.nro_exp_control,        
        estado: val,
    }

    $.ajax({
        type: 'POST',
        url: 'movimientos/visarConObservacion',
        data: formData,
        dataType: 'json',
        success: function (data) {
            let mensaje = '';
            if(data.relError || data.relValidado){
                divRelMovLimpiar();
                if(data.relError) mensaje = 'Relevamiento marcado erroneo.';
                else mensaje = 'Relevamiento marcado valido.';
                divRelMovMarcarListoRel(formData.id_relev_mov);
                divRelMovLimpiar();
                divRelMovEsconderDetalleRelevamiento();
            };
            if(data.fisValidada){
                mensaje = 'Fiscalizacion visada.';
                divRelMovCargarRelevamientos([],{},-1);
            }
            if(data.movValidado){
                mensaje = 'Movimiento visado.';
                $('#btn-buscarMovimiento').click();
                $('#modalValidacion').modal('hide');
            }
            mensajeExito({titulo:'ÉXITO',mensajes:[mensaje]});
        },
        error: function (data) {
            mensajeError(['Error al visar el movimiento.']);
        }
    })
};

function generarFilaFechaFiscalizacion(id,estado,fecha){
    let fila = $('<tr>');
    fila.append($('<td>').addClass('col-xs-6').text(fecha));
    fila.append(
        $('<td>').addClass('col-xs-3')
        .append(
            $('<button>').append(
                $('<i>').addClass('fa').addClass('fa-fw').addClass('fa-eye')
            )
            .attr('type', 'button')
            .addClass('btn btn-info detalleMov')
            .attr('data-id-fiscalizacion', id)
            .attr('data-fecha-fisc', fecha)
        )
    )
    console.log('estado',estado);
    if (estado == 4) {
        fila.append(
            $('<td>').addClass('col-xs-3')
            .append(
                $('<i>').addClass('fa fa-fw fa-check finalizado').css('color', '#4CAF50')
            )
        );
    }
    return fila;
}

function agregarValidadoFiscalizacion(id){
    let fila = $('button[data-id-fiscalizacion="'+id+'"]').parent().parent();
    fila.append(
        $('<td>').addClass('col-xs-3')
        .append(
            $('<i>').addClass('fa fa-fw fa-check finalizado').css('color', '#4CAF50')
        )
    );
    return fila;
}