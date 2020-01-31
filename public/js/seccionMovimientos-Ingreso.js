var ultimo_boton_carga = null;
//MOSTRAR MODAL PARA INGRESO: BTN NUEVO INGRESO
$(document).on('click', '.nuevoIngreso', function () {
    const id_movimiento = $(this).parent().parent().attr('id');
    $('#modalLogMovimiento .modal-title').text('SELECCIÓN DE TIPO DE CARGA');
    $('input[name="carga"]').attr('checked', false);
    $('#btn-aceptar-ingreso').prop('disabled', true);
    $('#modalLogMovimiento #cantMaqCargar').hide();
    $('#modalLogMovimiento').find("#id_log_movimiento").val(id_movimiento);
    //estilo de modal, y lo muestra
    $('#modalLogMovimiento .modal-header').attr('style', 'font-family: Roboto-Black; background-color: #6dc7be;');
    $('#tipoManual').prop('checked', true).click();
    $('#modalLogMovimiento').modal('show');


    $.get('movimientos/obtenerDatos/' + id_movimiento, function (data) {
        $('#conceptoExpediente').text(data.expediente.concepto);
        if (data.movimiento.tipo_carga != null) {
            $('#modalLogMovimiento #cantMaqCargar').show();
            if (data.movimiento.tipo_carga == 1) {
                $('#tipoManual').prop('checked', true).prop('disabled', true);
                $('#tipoCargaSel').prop('disabled', true);
            }
            if (data.movimiento.tipo_carga == 2) {
                $('#tipoCargaSel').prop('checked', true).prop('disabled', true);
                $('#tipoManual').prop('disabled', true);
            }
            $("#cant_maq").val(data.movimiento.cantidad).prop('disabled', true);
            $('#btn-aceptar-ingreso').prop('disabled', false);
        }
        else {
            $('#tipoManual').prop('disabled', false);
            $('#tipoCargaSel').prop('disabled', false);
            $('#cant_maq').val(1).prop('disabled', false);
        }
    })
}); //FIN DE EL NUEVO INGRESO

//DETECTAR SI EL TIPO DE CARGA SELECCIONADO ES MANUAL
$('#tipoManual').click(function () {
    const s = $('#modalLogMovimiento #tipoManual').val();
    if (s == 1) { //TIPO DE CARGA: MANUAL
        $('#modalLogMovimiento #cantMaqCargar').show();
        $('#btn-aceptar-ingreso').prop('disabled', false);
    }
})
//DETECTAR EL TIPO DE CARGA SELECCIONADO ES MASIVA
$('#tipoCargaSel').click(function () {
    const s = $('#modalLogMovimiento #tipoCargaSel').val();
    if (s == 2) { //TIPO DE CARGA: MASIVA
        $('#modalLogMovimiento #cantMaqCargar').hide();
    }
    $('#btn-aceptar-ingreso').prop('disabled', false);
});

//minimiza modal SELECCION INDIVIDUAL/MASIVO PARA INGRESOS
$('#btn-minimizar').click(function () {
    if ($(this).data("minimizar") == true) {
        $('.modal-backdrop').css('opacity', '0.1');
        $(this).data("minimizar", false);
    }
    else {
        $('.modal-backdrop').css('opacity', '0.5');
        $(this).data("minimizar", true);
    }
});

//BOTÓN ACEPTAR dentro del modal ingreso
$("#btn-aceptar-ingreso").click(function (e) {
    const id = $("#id_log_movimiento").val();
    const cant_maq = $("#cant_maq").val();
    const t_carga = $('input:radio[name=carga]:checked').val();

    if (typeof cant_maq == "undefined") {
        $('#mensajeErrorCarga').text('Debe especificar la cantidad de máquinas que va a cargar');
        $('#mensajeErrorCarga').show();
    }

    else {
        const formData = {
            id_log_movimiento: id,
            cantMaq: cant_maq,
            tipoCarga: t_carga,
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.ajax({
            type: 'POST',
            url: 'movimientos/guardarTipoCargaYCantMaq',
            data: formData,
            dataType: 'json',
            success: function (data) {
                //Busco la fila que contiene el id del movimiento indicado
                let fila = $("#tablaResultados tbody").find('#' + id);
                //seteo en el btn de carga el tipo de carga
                fila.attr("data-carga", data.tipo_carga);

                $('#modalLogMovimiento').modal('hide');
                fila.find('.boton_cargar').show();
                $('#' + id).find('.nuevoIngreso').attr('style', 'display:none');;
            },
            error: function (data) {
                mensajeError(sacarErrores(data));
            }
        })
    } //fin del else
}); //FIN DEL BTN ACEPTAR

//ABRIR MODAL DE NUEVA MÁQUINA
$(document).on('click', '.boton_cargar', function (e) {
    let boton = $(this);
    e.preventDefault();
    boton.tooltip('hide');

    const mov = boton.parent().parent().attr('id');
    $('#modalMaquina').find('#id_movimiento').val(mov);

    //Ver que tipo de carga de máqunas se hace.
    //MANUAL
    if (boton.parent().parent().attr('data-carga') == 1) {
        //muestra tab de maquinas y oculto el resto
        $.get('movimientos/obtenerDatos/' + mov, function (data) {
            ultimo_boton_carga = boton;
            eventoNuevo(data.movimiento, data.expediente);
        })
    }
    //MASIVA
    else {
        $('#modalCargaMasiva .modal-header').attr('style', 'font-family: Roboto-Black; background-color: #6dc7be;');
        $('#modalCargaMasiva').modal('show');
    }
});

function eventoNuevo(movimiento, expediente) {
    //Modificar los colores del modal
    $('#modalMaquina .modal-title').text('NUEVA MÁQUINA TRAGAMONEDAS');
    $('#modalMaquina .modal-header').attr('style', 'font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
    $('#btn-guardar').removeClass('btn-warning');
    $('#btn-guardar').addClass('btn-success');
    $('#btn-guardar').text('CREAR MTM');
    $('#btn-guardar').val("nuevo");
    $('#btn-guardar').prop('disabled', false).show();
    $('#btn-guardar').css('display', 'inline-block');
    $('#marca_juego_check').prop('checked', true).trigger('change');
    //como estoy creando id = 0
    $('#id_maquina').val(0);
    const option_casino = $('#dtpCasinoMov option[value="' + movimiento.id_casino + '"]').clone();
    $('#selectCasino').empty().append(option_casino).prop('disabled', true)
        .val(movimiento.id_casino).trigger('change');
    mostrarJuegos(movimiento.id_casino, [], null);

    $('#modalMaquina  .seccion').hide();
    $('#modalMaquina  .navModal a').removeClass();
    $('#navMaquina').addClass('navModalActivo');
    $('#secMaquina').show();

    //Setear el expediente
    $('#M_expediente').val(expediente.id_expediente);
    $('#M_nro_exp_org').val(expediente.nro_exp_org).prop('readonly', true);
    $('#M_nro_exp_interno').val(expediente.nro_exp_interno).prop('readonly', true);
    $('#M_nro_exp_control').val(expediente.nro_exp_control).prop('readonly', true);

    //Setear la cantidad de máquinas pendientes
    if (movimiento.cantidad == 1) {
        $('#maquinas_pendientes').text(' ' + movimiento.cant_maquinas + ' MÁQUINA PENDIENTE A CARGAR');
    } else {
        $('#maquinas_pendientes').text(' ' + movimiento.cant_maquinas + ' MÁQUINAS PENDIENTES A CARGAR');
    }

    $('#modalMaquina').modal('show');
}

$('#modalMaquina #nro_admin').on("keyup", function (e) {
    const text = "NUEVA MÁQUINA TRAGAMONEDAS N°: " + $(this).val();
    $('#modalMaquina .modal-title').text(text);
});

//ABRIR MODAL DE CARGA MASIVA
$('.cargar2').click(function (e) {
    e.preventDefault();
    //Modificar los colores del modal
    $('#modalCargaMasiva .modal-title').text('| NUEVA CARGA MASIVA');
    $('#btn-guardar').removeClass('btn-warning');
    $('#btn-guardar').addClass('btn-success');
    $('#modalCargaMasiva').modal('show');
});

//MANDAR ARCHIVO PARA CARGA MASIVA.
$('#btn-carga-masiva').click(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    //tomo el archivo seleccionado para luego enviar a servidor
    let formData = new FormData();
    formData.append('file', $('#cargaMasiva')[0].files[0]);
    formData.append('id_casino', $('#contenedorCargaMasiva').val());

    for (const pair of formData.entries()) {
        console.log(pair[0] + ', ' + pair[1]);
    }

    $.ajax({
        type: 'POST',
        url: '/movimientos/cargaMasiva',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        success: function (data) {
            $('#frmCargaMasiva').trigger('reset');
            $('#modalCargaMasiva').modal('hide');
        },
        error: function (data) {
            alert('error');
        },
    });
}); //FIN DEL POST PARA ENVIAR ARCHIVO DE C. MASIVA

//Enviar a fiscalizar las de ingreso **************************
$(document).on('click', '.enviarIngreso', function (e) {
    const id_log_movimiento = $(this).parent().parent().attr('id');
    $('#modalEnviarFiscalizarIngreso .modal-title').text('SELECCIÓN DE MTMs PARA ENVÍO A FISCALIZAR');
    $('#tablaMaquinas tbody tr').remove();
    $('#modalEnviarFiscalizarIngreso #id_log_movimiento').val(id_log_movimiento);
    ocultarErrorValidacion($('#B_fecha_ingreso'));
    $('#B_fecha_ingreso').val('');
    $.get('movimientos/buscarMaquinasMovimiento/' + id_log_movimiento, function (data) {
        var tablaMaquinas = $('#tablaMaquinas tbody');

        for (var i = 0; i < data.maquinas.length; i++) {
            var fila = $(document.createElement('tr'));

            fila.attr('id', data.maquinas[i].maquina.id_maquina)
                .append($('<td>').addClass('col-xs-3').append($('<input>').attr('type', 'checkbox')))
                .append($('<td>').addClass('col-xs-9').text(data.maquinas[i].maquina.nro_admin))

            tablaMaquinas.append(fila);
        }
    });

    $('#modalEnviarFiscalizarIngreso').modal('show');
})

//dentro del modal de ingreso, presiona el boton "Enviar a Fiscalizar"
$("#btn-enviar-ingreso").click(function (e) {
    $('#mensajeError').hide();
    $('#mensajeExito').hide();
    const id = $("#modalEnviarFiscalizarIngreso #id_log_movimiento").val();
    let maquinas_seleccionadas = [];
    const fecha = $('#B_fecha_ingreso').val();

    $('#tablaMaquinas tbody tr').each(function () {
        const check = $(this).find('td input[type=checkbox]');
        console.log(check);

        if (check.prop('checked')) {
            maquinas_seleccionadas.push($(this).attr('id'));
        }
    });

    const formData = {
        id_log_movimiento: id,
        maquinas: maquinas_seleccionadas,
        fecha: fecha
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: 'POST',
        url: 'movimientos/enviarAFiscalizar',
        data: formData,
        dataType: 'json',

        success: function (data) {
            $('#modalEnviarFiscalizarIngreso').modal('hide');
            mensajeExito({
                titulo: 'ENVÍO EXITOSO',
                mensajes: ['Las máquinas fueron enviadas correctamente']
            });
        },
        error: function (data) {
            console.log(data);
            mensajeError(sacarErrores(data));
        }
    })
})