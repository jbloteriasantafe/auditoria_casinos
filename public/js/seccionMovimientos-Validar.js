var cant_validadas = 0;
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
    $.get('movimientos/obtenerRelevamientosFiscalizacion2/' + id_fiscalizacion, function(data){
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
    /*$.get('movimientos/ValidarFiscalizacion/' + id_fiscalizacion, function (data) {

        var tablaMaquinasFiscalizacion = $('#tablaMaquinasFiscalizacion tbody');
        $('#tablaMaquinasFiscalizacion tbody tr').remove();
        let cant_maq_val = 0;
        cant_validadas = data.Maquinas.length;
        data.Maquinas.forEach(m => {
            let fila = $('<tr>');
            fila.attr('data-id', m.id_maquina).append(
                $('<td>').addClass('col-xs-4').text(m.nro_admin)
            );
            fila.append(
                $('<td>')
                    .addClass('col-xs-4')
                    .append(
                        $('<button>')
                            .append(
                                $('<i>').addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                            )
                            .attr('type', 'button')
                            .addClass('btn btn-info verMaquina1')
                            .attr('data-maquina', m.id_maquina)
                            .attr('data-fiscalizacion', id_fiscalizacion)
                            .attr('data-relevamiento', m.id_relev_mov)
                    )
            );

            if (m.id_estado_relevamiento == 4) {
                cant_validadas = cant_validadas - 1;
                cant_maq_val = cant_maq_val + 1;
                $('#enviarValidar').hide();
                fila.append(
                    $('<td>')
                        .addClass('col-xs-4')
                        .append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50'))
                );
            }
            tablaMaquinasFiscalizacion.append(fila);
        });
        const t = $("#tablaMaquinasFiscalizacion tr").length;
        console.log('t es', t);
        console.log('cant es', cant_maq_val);
        if (cant_maq_val == (t - 1)) {
            $('#finalizarValidar').show();
        }
    })*/
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

/*
//BOTÓN PARA VER EL DETALLE DE  UNA DE LAS MÁQUINAS FISCALIZADAS
$(document).on('click', '.verMaquina1', function () {
    $('#columnaDetalle').show();
    $('.detalleMaq').show();
    const id_maquina = $(this).attr('data-maquina');
    const id_fiscalizacion = $(this).attr('data-fiscalizacion');
    let tablaContadores = $('#tablaValidarIngreso tbody');
    const id_relevamiento = $(this).attr('data-relevamiento');
    $('#tablaMaquinasFiscalizacion tbody tr').css('background-color', '#FAFAFA');
    $(this).parent().parent().css('background-color', '#E0E0E0');

    //guardo el id_maquina en el input maquina del modal
    $('#modalValidacion').find('#maquina').val(id_maquina);
    $('#modalValidacion').find('#relevamiento').val(id_relevamiento);
    $('#mensajeExitoValidacion').hide();

    $('#tablaValidarIngreso tbody tr').remove();

    $.get('movimientos/ValidarMaquinaFiscalizacion/' + id_relevamiento, function (data) {
        if (data.toma.id_estado_relevamiento == 4) {
            $('#enviarValidar').hide();
        }
        else {
            $('#enviarValidar').show();
            $('#errorValidacion').show();
        }
        //CARGA CAMPOS INPUT
        if (data.cargador != null) { $('#f_cargaMov').val(data.cargador.nombre); }

        $('#f_tomaMov').val(data.fiscalizador.nombre);
        $('#nro_adminMov').val(data.toma.nro_admin);
        $('#nro_islaMov').val(data.toma.nro_isla);
        $('#nro_serieMov').val(data.toma.nro_serie);
        $('#marcaMov').val(data.toma.marca);
        $('#modeloMov').val(data.toma.modelo);
        $('#macMov').val(data.toma.mac);
        $('#islaRelevadaMov').val(data.toma.nro_isla_relevada);
        $('#sectorRelevadoMov').val(data.toma.descripcion_sector_relevado);

        //CARGAR LA TABLA DE CONTADORES, HASTA 6
        const cont = "cont";
        const vcont = "vcont";
        for (let i = 1; i < 7; i++) {
            let fila = $('<tr>');
            const p = data.toma[cont + i];
            const v = data.toma[vcont + i];
            if (data.toma1 == null) {//si toma anterior es null:
                if (p != null) { //si toma actual es != null
                    fila.append($('<td>')
                        .addClass('col-xs-6')
                        .text(p))
                        .append($('<td>')
                            .addClass('col-xs-3')
                            .text(v)
                        );
                    $('#toma_actual').show();
                    $('#toma_anterior').hide();
                    $('#toma_check').hide();

                    tablaContadores.append(fila);
                }
            }
            else { //si toma anterior es != null
                let m = data.toma1[vcont + i];
                if (p != null) { //si toma nueva es != null
                    fila.append($('<td>')
                        .addClass('col-xs-6')
                        .text(p))
                        .append($('<td>')
                            .addClass('col-xs-3')
                            .text(m) //valor de la toma anterior
                        )
                        .append($('<td>')
                            .addClass('col-xs-3')
                            .text(v) //valor de la toma nueva
                        );
                    if (m == v) {
                        fila.append($('<td align="center">')
                            .addClass('col-xs-2')
                            .append($('<span>').text(' '))
                            .addClass('boton_check_toma')
                            .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-check-circle-o').css('color', '#1DE9B6'))
                            .attr('style', 'font-size:20px')
                            .attr('data-toggle', "tooltip")
                            .attr('data-placement', "top")
                            .attr('title', "OK")
                            .attr('data-delay', { "show": "300", "hide": "100" })
                        );
                        fila.find('.boton_check_toma').hide();
                    }
                    else {
                        fila.append($('<td align="center">')
                            .addClass('col-xs-2')
                            .append($('<span>').text(' '))
                            .addClass('boton_x_toma')
                            .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-times-circle-o').css('color', '#D50000'))
                            .attr('style', 'font-size:20px')
                            .attr('data-toggle', "tooltip")
                            .attr('data-placement', "top")
                            .attr('title', "ERROR")
                            .attr('data-delay', { "show": "300", "hide": "100" })
                        );
                        fila.find('.boton_x_toma').hide();
                    }
                    $('#toma_anterior').show();
                    $('#toma_actual').show();
                    $('#toma_check').hide();
                    tablaContadores.append(fila);
                }
            }
        }
        if (data.toma1 == null) { //TOMA ANTERIOR ES NULL:
            //MUESTRO LA TOMA NUEVA
            $('#juego').val(data.toma.nombre_juego);
            $('#apuesta').val(data.toma.apuesta_max);
            $('#cant_lineas').val(data.toma.cant_lineas);
            $('#devolucion').val(data.toma.porcentaje_devolucion);
            $('#denominacion').val(data.toma.denominacion);
            $('#creditos').val(data.toma.cant_creditos);
        }
        else { //SI TIENE TOMA ANTERIOR:
            $('#toma2').show(); //MUESTRO TOMA ANTERIOR
            //COMPLETO TOMA NUEVA QUE SIEMPRE TIENE
            $('#juego').val(data.toma.nombre_juego);
            $('#apuesta').val(data.toma.apuesta_max);
            $('#cant_lineas').val(data.toma.cant_lineas);
            $('#devolucion').val(data.toma.porcentaje_devolucion);
            $('#denominacion').val(data.toma.denominacion);
            $('#creditos').val(data.toma.cant_creditos);

            //Y COMPLETO TOMA ANTERIOR
            $('#juego1').val(data.toma1.nombre_juego);
            $('#apuesta1').val(data.toma1.apuesta_max);
            $('#cant_lineas1').val(data.toma1.cant_lineas);
            $('#devolucion1').val(data.toma1.porcentaje_devolucion);
            $('#denominacion1').val(data.toma1.denominacion);
            $('#creditos1').val(data.toma1.cant_creditos);
        }

        if (!data.coinciden_juego) {
            mostrarErrorValidacion($('#juego'), data.n_juego, false);
        }
        if (!data.coinciden_denominacion) {
            mostrarErrorValidacion($('#denominacion'), data.n_denominacion, false);
        }
        if (!data.coinciden_devolucion) {
            mostrarErrorValidacion($('#devolucion'), data.n_devolucion, false);
        }

        $('#observacionesToma').show();
        if (data.toma.observaciones != null) {
            $('#observacionesToma').text(data.toma.observaciones);
        }
        else {
            $('#observacionesToma').text(' ');
        }
        //guardo el id_fiscalizacion en el boton enviarValidar
        $('#modalValidacion').find('#enviarValidar').val(id_fiscalizacion);

        $('.detalleMaq').show();
        $('.validar').prop('disabled', false);
        $('.error').prop('disabled', false);
    });
});*/

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
$(document).on('click', '#divRelMov .validar', function () {
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
        validado: val,
    }

    $.ajax({
        type: 'POST',
        url: 'movimientos/visarConObservacion',
        data: formData,
        dataType: 'json',
        success: function (data) {
            //Deshabilito los botones error y validar
            $('#enviarValidar').hide();
            $('.error').prop('disabled', true);
            $('.detalleMaq').hide();
            cant_validadas = cant_validadas - 1;

            $('#tablaMaquinasFiscalizacion tbody tr').each(function () {
                console.log($(this).attr('data-id'));
                const maq = $(this).attr('data-id');
                console.log('maquina', maq);

                if (maq == id_maquina) {
                    console.log('encontrada', $(this));
                    $(this).append($('<td>').addClass('col-xs-4')
                        .append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50')));
                }
            });
            console.log('cant_validadas', cant_validadas);
            //si se validaron todas las máquinas de la fecha
            if (cant_validadas == 0) {
                $('#finalizarValidar').show();
            }
        },
        error: function (data) {
            $('#mensajeErrorVal').show();
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