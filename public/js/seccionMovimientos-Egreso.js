var maq_seleccionadas=[];
$(document).on('click', '.nuevoEgreso', function () {
    console.log('ASDSAD');
    $('#btn-enviar-egreso').show();
    $('#btn-enviar-toma2').hide();
    ocultarErrorValidacion($('#B_fecha_egreso'));
    $('#B_fecha_egreso').val(' ');

    const fila = $(this).parent().parent();
    const id_casino = fila.attr('data-casino');
    const id_mov = fila.attr('id');
    const t_mov = fila.attr('data-tipo');

    $('#modalLogMovimiento2 .modal-title').text('CARGAR MÁQUINAS A EGRESAR');
    $('#tablaMaquinasSeleccionadas tbody tr').remove();
    $('#modalLogMovimiento2').find('#tipo_movi').val(t_mov);
    $('#modalLogMovimiento2').find('#mov').val(id_mov);
    
    maq_seleccionadas = [];

    $('#inputMaq').generarDataList("maquinas/obtenerMTMMovimientos/" + id_casino + '/' + t_mov + '/' + id_mov, 'maquinas', 'id_maquina', 'nro_admin', 1, true);
    $.get('movimientos/buscarMaquinasMovimiento/' + id_mov, function (data) {
        $('#tablaMaquinasSeleccionadas tbody tr').remove();

        if (data.maquinas.length != 0) {
            data.maquinas.forEach(m => {
                agregarMaq(m.maquina.id_maquina, m.maquina.nro_admin, m.maquina.marca,
                            m.maquina.modelo,m.maquina.nro_isla);

                $('#inputMaq').setearElementoSeleccionado(0, "");
                $('#isla_layout').hide();
                $('#modalLogMovimiento2').modal('show');
            });
        }
        else { //no hay máquinas
            $('#tablaMaquinasSeleccionadas tbody tr').remove();
            $('#isla_layout').hide();
            $('#btn-enviar-egreso').prop('disabled', true);
            if (t_mov == 8) {
                $('#btn-pausar').hide();
            }
            else {
                $('#btn-pausar').prop('disabled', true);
            }
            $('#modalLogMovimiento2').modal('show');
        }
    });
    $('#mensajeFiscalizacionError').hide();
    $('#btn-enviar-egreso').val(id_mov);
});

//click mas para agregar máquinas
$('#agregarMaq').click(function (e) {
    const id_maquina = $('#inputMaq').attr('data-elemento-seleccionado');
    if (id_maquina != 0) {
        $.get('http://' + window.location.host + "/maquinas/obtenerMTM/" + id_maquina, function (data) {
            agregarMaq(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.marca,
                data.maquina.modelo, data.isla.nro_isla, data.juego_activo.nombre_juego,
                data.maquina.nro_serie);
            $('#inputMaq').setearElementoSeleccionado(0, "");
            console.log('555:', data);
        });
    }
});

function agregarMaq(id_maquina, nro_admin, marca, modelo, isla, nombre_juego, nro_serie) {
    const tipo = $('#modalLogMovimiento2').find('#tipo_movi').val();
    let fila = $('<tr>').attr('id', id_maquina);
    const accion = $('<button>').addClass('btn btn-danger borrarMaq')
        .append($('<i>').addClass('fa fa-fw fa-trash'));

    fila.append($('<td>').text(nro_admin));
    fila.append($('<td>').text(marca));
    fila.append($('<td>').text(limpiarNull(modelo)));
    //tipo de movimiento 4: CAMBIO LAYOUT
    if (tipo != 4) {
        //Se agregan todas las columnas para la fila
        fila.append($('<td>').text(nombre_juego));
        fila.append($('<td>').text(limpiarNull(nro_serie)));
        fila.append($('<td>').append(accion));
    } else {
        fila.append($('<td>').text(isla));
        fila.append($('<td>').text(nombre_juego));
        fila.append($('<td>').text(nro_serie));
        if (isla != null) {
            fila.append($('<td>').append(accion));
        }
    }
    //Agregar fila a la tabla
    $('#tablaMaquinasSeleccionadas tbody').append(fila);
    if (tipo != 8) {
        $('#btn-pausar').prop('disabled', false);
    }
    $('#btn-enviar-egreso').prop('disabled', false);
};

//Envía a fiscalizar, finaliza carga
$(document).on('click', '#btn-enviar-egreso', function (e) {
    const tipo = $('#modalLogMovimiento2').find('#tipo_movi').val();
    const id_log_movimiento = $(this).val();
    const fecha = $('#B_fecha_egreso').val();
    const maquinas = $('#tablaMaquinasSeleccionadas tbody > tr');

    $.each(maquinas, function (index, value) {
        var maquina = {
            id_maquina: $(this).attr('id')
        }
        maq_seleccionadas.push(maquina);
    });
    //USA LA FC DE POST, ENVIANDO EN TRUE EL ATRIBUTO DE CARGA FINALIZADA
    if (tipo != 8) {
        enviarFiscalizar(id_log_movimiento, maq_seleccionadas, fecha, true, false);
    } else {//es reingreso
        enviarFiscalizar(id_log_movimiento, maq_seleccionadas, fecha, true, true);
    }
});

//Pausa la carga de maquinas a fiscalizar
$('#btn-pausar').click(function (e) {
    const tipo = $('#modalLogMovimiento2').find('#tipo_movi').val();
    const id_log_movimiento = $('#modalLogMovimiento2').find('#mov').val();
    const maquinas = $('#tablaMaquinasSeleccionadas tbody > tr');
    const fecha = $('#B_fecha_egreso').val();

    $.each(maquinas, function (index, value) {
        const maquina = {
            id_maquina: $(this).attr('id')
        }
        maq_seleccionadas.push(maquina);
    });

    //USA LA FC DE POST, ENVIANDO EN FALSE EL ATRIBUTO DE CARGA FINALIZADA
    if (tipo != 8) {
        enviarFiscalizar(id_log_movimiento, maq_seleccionadas, fecha, false, false);
    } else {
        enviarFiscalizar(id_log_movimiento, maq_seleccionadas, fecha, false, true);
    }
});

//POST
function enviarFiscalizar(id_mov, maq, fecha, fin, reingreso) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    const formData = {
        id_log_movimiento: id_mov,
        maquinas: maq,
        carga_finalizada: fin,
        es_reingreso: reingreso,
        fecha: fecha,
    }

    $.ajax({
        type: 'POST',
        url: 'movimientos/guardarRelevamientosMovimientos',
        data: formData,
        dataType: 'json',
        success: function (data) {
            if(fin){
                mensajeExito({titulo: 'ÉXITO', mensajes: ['Las máquinas han sido enviadas']});
            }
            else{
                mensajeExito({titulo: 'CARGA PAUSADA', mensajes: ['Las máquinas han sido guardadas correctamente']});
            }
            $("#modalLogMovimiento2").modal('hide');
        },
        error: function (data) {
            let response = data.responseJSON.errors;

            if (typeof response.fecha !== 'undefined') {
                mostrarErrorValidacion($('#B_fecha_egreso'), response.fecha[0], false);
            }
            else {
                $('#mensajeFiscalizacionError').show();
                $("#modalLogMovimiento2").animate({ scrollTop: $('#mensajeFiscalizacionError').offset().top }, "slow");
            }
        },
    });
};

$('#agregarMaqBaja').click(function (e) {
    const id_maq = $('#inputMaq3').attr('data-elemento-seleccionado');
    if (id_maq != 0) {
        $.get("/maquinas/obtenerMTM/" + id_maq, function (data) {
            agregarMaqBaja(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.marca, data.maquina.modelo, 1);
            $('#inputMaq3').setearElementoSeleccionado(0, "");
        });
    }
});

function agregarMaqBaja(id_maquina, nro_admin, marca, modelo, p) {
    let fila = $('<tr>').attr('id', id_maquina);
    const accion = $('<button>').addClass('btn btn-danger borrarMaqCargada')
        .append($('<i>').addClass('fa fa-fw fa-trash'));
    const t_mov = $('#modalBajaMTM').find('#tipoMovBaja').val();

    //Se agregan todas las columnas para la fila
    fila.append($('<td>').text(nro_admin))
    fila.append($('<td>').text(marca))
    fila.append($('<td>').text(modelo))
    //"p" indica si ya viene cargada la tabla o no, para agregar o no el boton de borrar
    if (p == 1) {
        fila.append($('<td>').append(accion));
    }
    //Agregar fila a la tabla
    $('#tablaBajaMTM tbody').append(fila);
    //Habilitar botones
    $('#btn-baja').prop('disabled', false);
};

//boton borrar en fila
$(document).on('click', '.borrarMaqCargada', function (e) {
    $(this).parent().parent().remove();
});

//boton borrar en fila
$(document).on('click', '.borrarMaq', function (e) {
    $(this).parent().parent().remove();
});

//boton ELIMINAR, EN MODAL
$(document).on('click', '#btn-baja', function (e) {
    const tipo = $('#modalBajaMTM').find('#tipoMovBaja').val();
    const id_log_movimiento = $('#modalBajaMTM').find('#movimId').val();
    let maquinas = $('#tablaBajaMTM tbody > tr');
    let mtmParaBaja = [];
    $.each(maquinas, function (index, value) {
        const maquina = {
            id_maquina: $(this).attr('id')
        }
        mtmParaBaja.push(maquina);
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    const formData = {
        maquinas: mtmParaBaja
    }

    $.ajax({
        type: 'POST',
        url: 'movimientos/bajaMTMs',
        data: formData,
        dataType: 'json',
        success: function (data) {
            mensajeExito({titulo: 'ELIMINACIÓN EXITOSA',mensajes: ['Las máquinas han sido eliminadas']});
            $("#modalBajaMTM").modal('hide');
        },
        error: function (data) {
            console.log('Error: No fue posible enviar a fiscalizar las máquinas cargadas');
            $('#mensajeFiscalizacionError').show();
            $("#modalLogMovimiento2").animate({ scrollTop: $('#mensajeFiscalizacionError').offset().top }, "slow");
        },
    });
});