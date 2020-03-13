$(document).on('click', '.nuevoEgreso', function () {
    const fila = $(this).parent().parent();
    const id_casino = fila.attr('data-casino');
    const id_mov = fila.attr('id');
    const t_mov = fila.attr('data-tipo');

    $('#tablaMaquinasSeleccionadas tbody tr').remove();
    $('#btn-enviar-egreso').attr('data-tipo-mov',t_mov);
    $('#btn-enviar-egreso').val(id_mov);
    maq_seleccionadas = [];
    $('#inputMaq').generarDataList("movimientos/obtenerMTMEnCasino/" + id_casino, 'maquinas', 'id_maquina', 'nro_admin', 1, true);  
    $('#modalEgresoElegirMaquinas').modal('show');
});

//click mas para agregar máquinas
$('#agregarMaq').click(function (e) {
    const id_maquina = $('#inputMaq').attr('data-elemento-seleccionado');
    if (id_maquina != 0) {
        $.get('http://' + window.location.host + "/movimientos/obtenerMTM/" + id_maquina, function (data) {
            agregarMaq(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.marca,
                data.maquina.modelo, data.isla.nro_isla, data.juego_activo.nombre_juego,
                data.maquina.nro_serie);
            $('#inputMaq').setearElementoSeleccionado(0, "");
            console.log('555:', data);
        });
    }
});

function agregarMaq(id_maquina, nro_admin, marca, modelo, isla, nombre_juego, nro_serie) {
    const tipo = $('#modalEgresoElegirMaquinas').find('#tipo_movi').val();
    let fila = $('<tr>').attr('id', id_maquina);
    const accion = $('<button>').addClass('btn btn-danger borrarMaq')
        .append($('<i>').addClass('fa fa-fw fa-trash'));

    fila.append($('<td>').text(nro_admin));
    fila.append($('<td>').text(marca));
    fila.append($('<td>').text(limpiarNull(modelo)));
    fila.append($('<td>').text(isla));
    fila.append($('<td>').text(nombre_juego));
    fila.append($('<td>').text(limpiarNull(nro_serie)));
    fila.append($('<td>').append(accion));

    //Agregar fila a la tabla
    $('#tablaMaquinasSeleccionadas tbody').append(fila);
};

//Envía a fiscalizar, finaliza carga
$(document).on('click', '#btn-enviar-egreso', function (e) {
    const tipo = $(this).attr('data-tipo-mov');
    const id_log_movimiento = $(this).val();
    const maquinas = $('#tablaMaquinasSeleccionadas tbody > tr');
    let maq_seleccionadas=[];
    $.each(maquinas, function (index, value) {
        maq_seleccionadas.push({
            id_maquina: $(this).attr('id')
        });
    });
    enviarFiscalizar(id_log_movimiento, maq_seleccionadas);
});

//POST
function enviarFiscalizar(id_mov, maq) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    const formData = {
        id_log_movimiento: id_mov,
        maquinas: Array.from(new Set(maq)), //Sacar repetidos
    };

    $.ajax({
        type: 'POST',
        url: 'movimientos/cargarMaquinasMovimiento',
        data: formData,
        dataType: 'json',
        success: function (data) {
            mensajeExito({titulo: 'ÉXITO', mensajes: ['Las máquinas han sido cargadas.']});
            $("#modalEgresoElegirMaquinas").modal('hide');
        },
        error: function (data) {
            mensajeError(['Error al cargar el movimiento.']);
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