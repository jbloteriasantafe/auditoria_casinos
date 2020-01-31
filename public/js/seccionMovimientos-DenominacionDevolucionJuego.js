$(document).on('click', '.modificarDenominacion', function () {
    const fila = $(this).parent().parent();
    const casino = fila.attr('data-casino');
    const mov = fila.attr('id');
    const tmov = fila.attr('data-tipo');
    $('#denom_comun').val(' ');
    $('#devol_comun').val(' ');
    $('#unidad_comun').val(' ');
    ocultarErrorValidacion($('#B_fecha_denom'));
    $('#B_fecha_denom').val(' ');

    $('#modalDenominacion').find('#id_t_mov').val(tmov);
    $('#modalDenominacion').find('#id_mov_denominacion').val(mov);

    $('#inputMaq2').generarDataList("maquinas/obtenerMTMEnCasinoMovimientos/" + casino + '/' + mov, 'maquinas', 'id_maquina', 'nro_admin', 1, true);
    $('#inputIslaDen').generarDataList("eventualidades/obtenerIslaEnCasino/" + casino, 'islas', 'id_isla', 'nro_isla', 1, true);
    $('#inputSectorDen').generarDataList("eventualidades/obtenerSectorEnCasino/" + casino, 'sectores', 'id_sector', 'descripcion', 1, true);

    switch (tmov) {
        case '5'://denominación
            $('#modalDenominacion .modal-title').text('ASIGNACIÓN: CAMBIO DE DENOMINACIÓN DE JUEGO');
            $('#segunda_columna').show().text('DENOMINACIÓN');
            $('#tercer_columna').show().text('');
            $('#cuarta_columna').show().text('');
            $('#denom_comun').show();
            $('#unidad_comun').show();
            $('#devol_comun').hide();
            $('#todosDen').show();
            $('#aplicar').show();
            $('#aplicar1').hide();
            $('#todosDev').hide();
            $('#nuevaDen').show();
            $('#nuevaDev').hide();
            $('#nuevaUni').show();
            $('#busqSector').show();
            $('#busqIsla').show();
            $('#B_fecha_denom').show();
            break;
        case '6': //devolución
            $('#modalDenominacion .modal-title').text('ASIGNACIÓN: CAMBIO DE %DEV DE JUEGO');
            $('#segunda_columna').show().text('% DEVOLUCIÓN');
            $('#tercer_columna').show().text('');
            $('#cuarta_columna').show().text('');
            $('#denom_comun').hide();
            $('#unidad_comun').hide();
            $('#devol_comun').show();
            $('#todosDen').hide();
            $('#todosDev').show();
            $('#aplicar').hide();
            $('#aplicar1').show();
            $('#nuevaDen').hide();
            $('#nuevaDev').show();
            $('#nuevaUni').hide();
            $('#busqSector').show();
            $('#busqIsla').show();
            $('#B_fecha_denom').show();
            break;
        case '7': //juego
            $('#modalDenominacion .modal-title').text('ASIGNACIÓN: CAMBIO DE JUEGO');
            $('#segunda_columna').show().text('JUEGO');
            $('#tercer_columna').show().text('DENOMINACIÓN');
            $('#cuarta_columna').show().text('% DEVOLUCIÓN');
            $('#denom_comun').hide();
            $('#unidad_comun').hide();
            $('#devol_comun').hide();
            $('#todosDen').hide();
            $('#todosDev').hide();
            $('#nuevaDen').hide();
            $('#nuevaDev').hide();
            $('#nuevaUni').hide();
            $('#busqSector').hide();
            $('#busqIsla').hide();
            $('#aplicar').hide();
            $('#aplicar1').hide();
            $('#dtpFechaMDenom').show();
            break;
        default:
            $('#modalDenominacion .modal-title').text('SELECCIÓN DE MTMs PARA ENVÍO A FISCALIZAR');
            break;
    }
    $('#tablaDenominacion tbody tr').remove();
    $.get('movimientos/buscarMaquinasMovimiento/' + mov, function (data) {
        if (data.maquinas.length != 0) {
            console.log('77', data);
            data.maquinas.forEach(m => {
                agregarMaqDenominacion(
                    m.maquina.id_maquina, m.maquina.nro_admin,
                    m.maquina.denominacion, m.juegos,
                    m.juego_seleccionado.id_juego, m.juego_seleccionado.nombre_juego,
                    m.maquina.porcentaje_devolucion, m.maquina.id_unidad_medida,
                    data.unidades, 2);
            });
        }
        else {
            $('#tablaDenominacion tbody tr').remove();
            $('#btn-enviar-denom').prop('disabled', true);
            $('#btn-pausar-denom').prop('disabled', true);
        }
    });

    $('#modalDenominacion').modal('show');
    $('#mensajeFiscalizacionError2').hide();
    $('#btn-enviar-denom').val(mov);
});

//crea tabla

$('#agregarMaq2').click(function (e) {
    const id_maq = $('#inputMaq2').attr('data-elemento-seleccionado');
    if (id_maq != 0) {
        $.get('http://' + window.location.host + "/movimientos/obtenerMTM/" + id_maq, function (data) {
            agregarMDenominacion(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.denominacion,
                data.maquina.porcentaje_devolucion, data.maquina.id_unidad_medida, data.unidades, 1, data.juego_activo);
            $('#inputMaq2').setearElementoSeleccionado(0, "");
        });
    }
});

function agregarMaqDenominacion(id_maquina, nro_admin, denom, juegos, id_juego, nombre_juego, dev, unidad_seleccionada, unidades, p) {
    let fila = $('<tr>').attr('id', id_maquina);
    const accion = $('<button>').addClass('btn btn-danger borrarMaq')
        .append($('<i>').addClass('fa fa-fw fa-trash'));
    const t_mov = $('#modalDenominacion').find('#id_t_mov').val();

    //Se agregan todas las columnas para la fila
    fila.append($('<td>').text(nro_admin))
    //TIPO DE MOVIMIENTO ES DENOMINACION:
    if (t_mov == 5) {
        fila.append($('<td>')
            .append($('<input>')
                .addClass('denominacion_modificada form-control')
                .val(denom)));

        let select = $('<select>').addClass('unidad_denominacion form-control');

        unidades.forEach(u =>{
            const tipo = u.descripcion;
            const id = u.id_unidad_medida;
            select.append($('<option>').text(tipo).val(id));
        });
        select.val(unidad_seleccionada);
        fila.append($('<td>').append(select));
    };

    //TIPO DE MOVIMIENTO ES %DEVOLUCION:
    if (t_mov == 6) {
        fila.append($('<td>')
            .append($('<input>')
                .addClass('devolucion_modificada form-control')
                .val(dev)));
    };
    //TIPO DE MOVIMIENTO ES JUEGO:
    if (t_mov == 7) {
        //select de juego
        var input = $('<input>').addClass('juego_modif form-control').attr('placeholder', "Nombre Juego");
        fila.append($('<td>').append(input)); //falta el denom y el devol
        input.generarDataList("movimientos/buscarJuegoMovimientos", 'juegos', 'id_juego', 'nombre_juego', 1);
        input.setearElementoSeleccionado(id_juego, nombre_juego);
    };
    //"p" indica si ya viene cargada la tabla o no, para agregar o no el boton de borrar
    if (p == 1) {
        fila.append($('<td>').append(accion));
    }
    //Agregar fila a la tabla
    $('#tablaDenominacion tbody').append(fila);
    //Habilitar botones
    $('#btn-enviar-denom').prop('disabled', false);
    $('#btn-pausar-denom').prop('disabled', false);
};

$('#agregarIslaDen').click(function (e) {
    const id_isla = $('#inputIslaDen').attr('data-elemento-seleccionado');
    if (id_isla != 0) {
        $.get('movimientos/obtenerMaquinasIsla/' + id_isla, function (data) {
            console.log('ff', data);
            data.maquinas.forEach(m => {
                agregarMDenominacion(m.id_maquina, m.nro_admin, m.denominacion, m.porcentaje_devolucion, 
                                     m.id_unidad_medida, data.unidades, 1, m.juego_obj);
            });
            $('#inputIslaDen').setearElementoSeleccionado(0, "");
        });
    }
});

$('#agregarSectorDen').click(function (e) {
    const id_isla = $('#inputSectorDen').attr('data-elemento-seleccionado');
    if (id_isla != 0) {
        $.get('movimientos/obtenerMaquinasSector/' + 0, function (data) {
            data.maquinas.forEach(m => {
                agregarMDenominacion(m.id_maquina, m.nro_admin, m.denominacion, m.porcentaje_devolucion, 
                                     m.id_unidad_medida, data.unidades, 1);
            });
            $('#inputSectorDen').setearElementoSeleccionado(0, "");
        });
    }
});

$('#btn-borrarTodo').on('click', function () {
    $('#tablaDenominacion tbody tr').remove();
});

function agregarMDenominacion(id_maquina, nro_admin, denom, dev, unidad_seleccionada, unidades, p, juego_activo) {
    let fila = $('<tr>').attr('id', id_maquina);
    fila.append($('<td>').text(nro_admin));

    // se busca migrar la denominacion a valores validos, por lo que se la convierte a numerico
    const denFloat = denominacionToFloat(denom);
    const denominacion_modificada = $('<input>').addClass('denominacion_modificada form-control').attr("type", "number")
        .attr("step", "0.01").attr("min", "0.01").val(denFloat);
    const devolucion_modificada = $('<input>').addClass('devolucion_modificada form-control').attr("type", "number")
        .attr("step", "0.01").attr("min", "80").attr("max", "100").val(dev);
    let juego_modif = $('<input>').addClass('juego_modif form-control').attr('placeholder', "Nombre Juego");

    const t_mov = $('#modalDenominacion').find('#id_t_mov').val();
    switch (t_mov) {
        case "5": {//DENOMINACION
            fila.append($('<td>').append(denominacion_modificada));
            // se agrega elementos vacios para que sea aceptable visiblemente
            fila.append($('<td>'));
            fila.append($('<td>'));
        } break;
        case "6": {//DEVOLUCION
            fila.append($('<td>').append(devolucion_modificada));
            // se agrega elementos vacios para que sea aceptable visiblemente
            fila.append($('<td>'));
            fila.append($('<td>'));
        } break;
        case "7": {//JUEGO
            fila.append($('<td>').append(juego_modif)); //falta el denom y el devol
            juego_modif.generarDataList("movimientos/buscarJuegoMovimientos", 'juegos', 'id_juego', 'nombre_juego', 1);
            // setea el valor actual en el buscador de juego
            juego_modif.setearElementoSeleccionado(juego_activo.id_juego, juego_activo.nombre_juego);
            // agrega denominacion de juego
            fila.append($('<td>').append(denominacion_modificada));
            // agrega % dev de juego
            fila.append(devolucion_modificada);
        } break;
        default: {
        } break;
    }

    //"p" indica si ya viene cargada la tabla o no, para agregar o no el boton de borrar
    if (p == 1) {
        const accion = $('<button>').addClass('btn btn-danger borrarMaq')
            .append($('<i>').addClass('fa fa-fw fa-trash'));
        fila.append($('<td>').append(accion));
    }
    //Agregar fila a la tabla
    $('#tablaDenominacion tbody').append(fila);
    //Habilitar botones
    $('#btn-enviar-denom').prop('disabled', false);
    $('#btn-pausar-denom').prop('disabled', false);
};

$('#todosDen').on('click', function () {
    const den_comun = $('#denom_comun').val();
    let tabla = $('#tablaDenominacion tbody > tr');
    if (den_comun != "") {
        tabla.find('.denominacion_modificada').val(den_comun);;
    };
})
$('#todosDev').on('click', function () {
    const dev_comun = $('#devol_comun').val();
    let tabla = $('#tablaDenominacion tbody > tr');
    if (dev_comun != "") {
        tabla.find('.devolucion_modificada').val(dev_comun);
    };
})
//cierra modal y limpio el data list de arriba
$('#modalDenominacion').on('hidden.bs.modal', function () {
    $('.input-data-list').borrarDataList();
})

//BOTÓN ENVIAR A FISCALIZAR DE DENOMINACION, DEVOLUCION Y JUEGO
$(document).on('click', '#btn-enviar-denom', function (e) {
    const id_log_movim = $('#modalDenominacion').find('#id_mov_denominacion').val();
    const tipo = $('#modalDenominacion').find('#id_t_mov').val();
    const tabla_maq = $('#tablaDenominacion tbody > tr');
    let maquinas = [];
    const fecha = $('#B_fecha_denom').val();

    $.each(tabla_maq, function (index, value) {
        let maquina = {
            id_maquina: $(this).attr('id'),
            id_juego: "",
            denominacion: "",
            porcentaje_devolucion: "",
            id_unidad_medida: ""
        };
        //Según el tipo de movimiento genera distintos json de máquinas
        switch (tipo) {
            //Tipo Movimiento: DENOMINACION
            case '5': {
                maquina.denominacion = $(this).find('.denominacion_modificada').val();
                maquina.id_unidad_medida = $(this).find('.unidad_denominacion').val();
            } break;
            //Tipo Movimiento: % DEVOLUCION
            case '6': {
                maquina.porcentaje_devolucion = $(this).find('.devolucion_modificada').val();
            } break;
            //Tipo Movimiento: JUEGO
            case '7': {
                maquina.id_juego = $(this).find('.juego_modif').obtenerElementoSeleccionado();
                maquina.denominacion = $(this).find('.denominacion_modificada').val();
                maquina.porcentaje_devolucion = $(this).find('.devolucion_modificada').val();
            } break;
        }
        maquinas.push(maquina);
    });
    //USA LA FC DE POST, ENVIANDO EN TRUE EL ATRIBUTO DE CARGA FINALIZADA
    enviarDenominacion(id_log_movim, maquinas, fecha, true);
});

//Pausa la carga de maquinas a fiscalizar
$(document).on('click', '#btn-pausar-denom', function (e) {
    const id_log_movim = $('#modalDenominacion').find('#id_mov_denominacion').val();
    const tipo = $('#modalDenominacion').find('#id_t_mov').val();
    const tabla_maq = $('#tablaDenominacion tbody > tr');
    let maquinas = [];
    const fecha = $('#B_fecha_denom').val();

    $.each(tabla_maq, function (index, value) {
        let maquina = {
            id_maquina: $(this).attr('id'),
            id_juego: "",
            denominacion: "",
            porcentaje_devolucion: "",
            id_unidad_medida: ""
        };
        //Según el tipo de movimiento genera distintos json de máquinas
        switch (tipo) {
            //Tipo Movimiento: DENOMINACION
            case '5': {
                maquina.denominacion = $(this).find('.denominacion_modificada').val();
                maquina.id_unidad_medida = $(this).find('.unidad_denominacion').val();
            } break;
            //Tipo Movimiento: % DEVOLUCIÓN
            case '6': {
                maquina.porcentaje_devolucion = $(this).find('.devolucion_modificada').val();
            } break;
            //Tipo Movimiento: JUEGO
            case '7': {
                maquina.id_juego = $(this).find('.juego_modif').obtenerElementoSeleccionado();
            } break;
        }
        maquinas.push(maquina);
    });
    //USA LA FC DE POST, ENVIANDO EN FALSE EL ATRIBUTO DE CARGA FINALIZADA
    enviarDenominacion(id_log_movim, maquinas, fecha, false);
});

//FUNCION PARA ENVIAR EL POST AL CONTROLADOR, CON LOS CAMBIOS GENERADOS
function enviarDenominacion(id_mov, maq, fecha, fin) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    const formData = {
        id_log_movimiento: id_mov,
        maquinas: maq,
        carga_finalizada: fin, //INDICA SI LA CARGA FUE FINALIZADA O NO
        fecha: fecha
    }

    $.ajax({
        type: 'POST',
        url: 'movimientos/guardarRelevamientosMovimientosMaquinas',
        data: formData,
        dataType: 'json',
        success: function (data) {
            if (fin) mensajeExito({ titulo: 'ENVÍO', mensajes: ['Las máquinas han sido enviadas correctamente.'] });
            else mensajeExito({ titulo: 'GUARDADO', mensajes: ['Las máquinas han sido guardadas en el movimiento.'] });
            $('#modalDenominacion').modal('hide');
        },
        error: function (data) {
            mensajeError(sacarErrores(data));
        },
    });
};