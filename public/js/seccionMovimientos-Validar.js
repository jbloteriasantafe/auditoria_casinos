//BOTÓN VALIDACION, DENTRO DE LA TABLA PRINCIPÁL
$(document).on('click', '.validarMovimiento', function () {
    $('#mensajeExito').hide();
    $('#tablaFechasFiscalizacion tbody tr').remove();
    $('#tablaMaquinasFiscalizacion tbody tr').remove();
    $('#mensajeErrorVal').hide();
    $('#mensajeExitoValidacion').hide();
    $('#columnaMaq').hide();
    $('#columnaDetalle').hide();

    //oculto los dos botones de guardar
    $('#enviarValidar').hide();
    $('#errorValidacion').hide();
    $('#finalizarValidar').hide();

    //Modificar los colores del modal
    $('#modalValidacion .modal-title').text('VALIDAR MÁQUINAS RELEVADAS');
    $('#modalValidacion .modal-header').attr('style', 'background: #4FC3F7');

    const id_log_movimiento = $(this).parent().parent().attr('id');
    $.get('movimientos/obtenerFiscalizacionesMovimiento/' + id_log_movimiento, function (data) {
        let tablaFiscalizacion = $('#tablaFechasFiscalizacion tbody');
        data.forEach(f => {
            $('#finalizarValidar').attr('data-fiscalizacion', f.id_fiscalizacion_movimiento);
            let fila = generarFilaFechaFiscalizacion(f.id_fiscalizacion_movimiento,f.id_estado_fiscalizacion,f.fecha_envio_fiscalizar);
            tablaFiscalizacion.append(fila);
        });
        let cantidad = 0;
        $('#tablaFechasFiscalizacion tbody tr').each(function () {
            if ($(this).hasClass('finalizado')) {
                cantidad = cantidad + 1;
            }
        });
        if (cantidad == data.length) {
            $('#finalizarValidar').show();
        }
        $('#mensajeErrorVal').hide();
        $('.detalleMaq').hide();
        $('#toma2').hide();
        $('.error').prop('disabled', true);
        $('#observacionesToma').hide();

        //guardo el id del movimiento en el input del modal
        $('#modalValidacion').find('#id_log_movimiento').val(id_log_movimiento);
        $('#modalValidacion').modal('show');
        $('#mensajeExito').hide();
    });
});

function validarFiscalizacion(id_fiscalizacion){
    //$('#guardarRel').prop('disabled', true);
    //$('#guardarRel').toggle(modo == "CARGAR");
    $('#finalizarValidar').attr('data-fiscalizacion',id_fiscalizacion);
    divRelMovEsconderDetalleRelevamiento();
    $.get('movimientos/obtenerRelevamientosFiscalizacion/' + id_fiscalizacion, function(data){
      divRelMovSetearUsuarios(data.casino,data.cargador,data.fiscalizador);
      divRelMovSetearTipo(data.tipo_movimiento,data.sentido);
      let dibujos = {3 : 'fa-check', 4 : 'fa-search-plus'};
      divRelMovCargarRelevamientos(data.relevamientos,dibujos,4);
      divRelMovSetearModo('VER');
      $('#modalValidacion').modal('show');
    });
}

//BOTON PARA VER EL LISTADO DE LAS MÁQUINAS FISCALIZADAS ESA FECHA
$(document).on('click', '.detalleMov', function () {
    validarFiscalizacion($(this).attr('data-id-fiscalizacion'));
});

$(document).on('click','#divRelMov .cargarMaq',function(){
    const id_rel = $(this).attr('data-rel');
    const toma = $(this).attr('toma');
    const estado = $(this).attr('data-estado-rel');
    divRelMovSetearModo(estado == 4? 'VER' : 'VALIDAR');
    //$('#guardarRel').attr('data-rel', id_rel);
    //$('#guardarRel').attr('toma', toma);
    $.get('movimientos/obtenerRelevamientoToma/' + id_rel + '/' + toma, function(data){
      //$('#guardarRel').prop('disabled', false);
      divRelMovSetear(data);
      divRelMovMostrarDetalleRelevamiento();
    });
});

//BOTÓN VALIDAR DENTRO DEL MODAL VALIDAR
$(document).on('click', '#divRelMov .validar', function () {
    $('#errorValidacion').hide();
    const id_relevamiento = $('#modalValidacion').find('#relevamiento').val();
    validar(id_relevamiento, 'valido');
});

//cuando cierra el modal de validación, actualizo el listado
$("#modalValidacion").on('hidden.bs.modal', function () {
    $('#btn-buscarMovimiento').trigger('click');
})

//BOTÓN ERROR
$(document).on('click', '#divRelMov .error', function () {
    const id_relevamiento = $('#modalValidacion').find('#relevamiento').val();
    validar(id_relevamiento, 'error');
});

//BOTÓN FINALIZAR VALIDACIÓN
$(document).on('click', '#finalizarValidar', function () {
    const id_fiscalizacion = $(this).attr('data-fiscalizacion');
    $.get('movimientos/finalizarValidacion/' + id_fiscalizacion, function (data) {
        if (data == 1) { // Log todo validado
            $('#modalValidacion').modal('hide');
            mensajeExito({ mensajes: ['Se ha VALIDADO correctamente el movimiento.'] })
        }
        else if (data == 0){ // Fiscalizacion validada
            agregarValidadoFiscalizacion(id_fiscalizacion);
        }
        else{ // Error
            mensajeError(['Hay maquinas sin validar o relevar.']);
        }
    })
});

//POST PARA VALIDAR
function validar(id_rel, val) {
    const formData = {
        id_relev_mov: id_rel,
        observacion: divRelMovObtenerDatos().observacionesAdm,
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