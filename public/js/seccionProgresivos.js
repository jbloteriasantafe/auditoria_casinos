$(document).ready(function() {
    $('#barraMaquinas').attr('aria-expanded', 'true');
    $('#maquinas').removeClass();
    $('#maquinas').addClass('subMenu1 collapse in');
    $('#gestionarMTM').removeClass();
    $('#gestionarMTM').addClass('subMenu2 collapse in');

    $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded', 'true');

    $('.tituloSeccionPantalla').text('Progresivos');
    $('#gestionarMaquinas').attr('style', 'border-left: 6px solid #3F51B5;');
    $('#opcProgresivos').attr('style', 'border-left: 6px solid #25306b; background-color: #131836;');
    $('#opcProgresivos').addClass('opcionesSeleccionado');

    $('#btn-buscar').trigger('click');
    cargarMaquinas();
});


function cargarMaquinas() {
    function callbackMaquinas(resultados) {
        let maquinas_lista = $('#maquinas_lista');
        maquinas_lista.empty();
        let option = $('<option></option>');
        for (var i = 0; i < resultados.length; i++) {
            let fila = option.clone().attr('value', resultados[i].nombre)
                .attr('data-id', resultados[i].id_maquina)
                .attr('data-isla', resultados[i].isla)
                .attr('data-sector', resultados[i].sector)
                .attr('data-nro_admin', resultados[i].nro_admin)
                .attr('data-marca_juego', resultados[i].marca_juego);
            maquinas_lista.append(fila);
        };
    }

    let ajaxData = {
        type: 'GET',
        url: 'progresivos/buscarMaquinas/' + $('#busqueda_casino').val()
    };

    $.when($.ajax(ajaxData))
        .then(callbackMaquinas, function(err) { console.log(err) });
}


//Busqueda
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    //Fix error cuando librería saca los selectores
    if (isNaN($('#herramientasPaginacion').getPageSize())) {
        var size = 10; // por defecto
    } else {
        var size = $('#herramientasPaginacion').getPageSize();
    }

    page_size   = (page_size == null || isNaN(page_size)) ? size : page_size;
    page_number = (pagina != null) ? pagina  : $('#herramientasPaginacion').getCurrentPage();
    columna     = (columna != null)? columna : $('#tablaResultados .activa').attr('value');
    orden       = (orden  != null) ? orden   : $('#tablaResultados .activa').attr('estado');
    var formData = {
        nombre_progresivo: $('#B_nombre_progresivo').val(),
        id_casino: $('#busqueda_casino').val(),
        islas: $('#B_islas').val(),
        sectores: $('#B_sectores').val(),
        page: page_number,
        sort_by: {columna: columna, orden: orden},
        page_size: page_size,
    };

    $.ajax({
        type: 'POST',
        url: 'progresivos/buscarProgresivos',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            console.log(resultados);
            $('#herramientasPaginacion').generarTitulo(page_number, page_size, resultados.total, clickIndice);
            $('#cuerpoTabla tr').not('.filaEjemplo').remove();
            for (var i = 0; i < resultados.data.length; i++) {
                let prog = resultados.data[i];
                let filaProgresivo = generarFilaTabla(prog);
                $('#cuerpoTabla').append(filaProgresivo);
            }
            $('#herramientasPaginacion').generarIndices(
                page_number,
                page_size,
                resultados.total,
                clickIndice);

        },
        error: function(data) {
            console.log('Error:', data);
        }
    });
});

$('#btn-buscar-individuales').on('click', function(e) {
    e.preventDefault();
    $('#btn-cancelar').text('CANCELAR');
    $('#btn-guardar').val("nuevo");
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('.modal-title').text('| MODIFICAR PROGRESIVOS INDIVIDUALES');
    $('.modal-header').attr('style', 'font-family: Roboto-Black; background-color: #ff9d2d; color: #fff');
    let data = {
        desde: $('#maquina_desde').val(),
        hasta: $('#maquina_hasta').val(),
        id_casino: $('#busqueda_casino_individuales').val()
    };

    mostrarProgresivoIndividual(data);
});

$('#btn-ayuda').click(function(e) {
    e.preventDefault();

    $('.modal-title').text('| PROGRESIVOS');
    $('.modal-header').attr('style', 'font-family: Roboto-Black; background-color: #aaa; color: #fff');

    $('#modalAyuda').modal('show');
});

//Mostrar modal para agregar nuevo Progresivo
$('#btn-nuevo').click(function(e) {
    e.preventDefault();
    $('#btn-cancelar').text('CANCELAR');
    $('#btn-guardar').val("nuevo");
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('.modal-title').text('| NUEVO PROGRESIVO LINKEADO');
    $('.modal-header').attr('style', 'font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
    $('#modalProgresivo').modal('show');
    mostrarProgresivo({ id_progresivo: -1, nombre: '', porc_recup: 0 }, [], [], true);
});

// Modal crear nuevo progresivo individual
$('#btn-nuevo-ind').click(function(e) {
    e.preventDefault();
    $('#btn-cancelar').text('CANCELAR');
    $('#btn-guardar').val("nuevo");
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('.modal-title').text('| NUEVOS PROGRESIVOS INDIVIDUALES');
    $('.modal-header').attr('style', 'font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
    mostrarProgresivoIndividual();
});

function filaEjemploIndividual() {
    let fila = $('.tablaMaquinasDivIndividual').find('.filaEjemplo').clone().removeClass('filaEjemplo');

    let botonEditar = fila.find('.editar');
    let botonBorrar = fila.find('.eliminar');

    botonBorrar.click(function() {
        fila.remove();
    });

    botonEditar.click(function() {
        let data = arregloProgresivoIndividual(fila);
        let filaEditable = filaEditableIndividualParcial(data);
        fila.replaceWith(filaEditable);
    })

    return fila;
}

function filaEditableIndividual() {
    let fila = filaEjemploIndividual();

    let input = crearEditable('text').attr('list', 'maquinas_lista')
    let fila_nroadmin = filaIndNroAdmin(fila).empty().append(input);
    let fila_sector = filaIndSector(fila).empty();
    let fila_isla = filaIndIsla(fila).empty();
    let fila_marcajuego = filaIndMarcaJuego(fila).empty();

    function existeEnTablaIndividuales(id) {
        return $('#contenedorMaquinasIndividual tbody tr[data-id=' + id + ']').length > 0;
    }

    {
        const input2 = crearEditable('text').addClass('sinflechas');

        filaIndRecup(fila).empty().append(input2.clone());
        filaIndMaximo(fila).empty().append(input2.clone());
        filaIndBase(fila).empty().append(input2.clone());
        filaIndVisible(fila).empty().append(input2.clone());
        filaIndOculto(fila).empty().append(input2.clone());

        filaIndRecupVal(fila, $('#inputPorcRecupIndividual').val());
        filaIndMaximoVal(fila, $('#inputMaximoIndividual').val());
        filaIndBaseVal(fila, $('#inputBaseIndividual').val());
        filaIndVisibleVal(fila, $('#inputPorcVisibleIndividual').val());
        filaIndOcultoVal(fila, $('#inputPorcOcultoIndividual').val());
    }

    let botonConfirmar = crearBoton('fa-check').addClass('confirmar').on('click', function() {
        fila.find('.erroneo').removeClass('erroneo');
        let fila_val = arregloProgresivoIndividual(fila);
        const validacion = validarFilaInd(fila);
        if (!validacion.porc_recup) {
            filaIndRecup(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.maximo) {
            filaIndMaximo(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.base) {
            filaIndBase(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.porc_oculto) {
            filaIndOculto(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.porc_visible) {
            filaIndVisible(fila).find('.editable').addClass('erroneo');
        }

        if (validacion.razones.length != 0) {
            console.log(validacion.razones);
            let finalstr = '';
            for (let i = 0; i < validacion.razones.length; i++) {
                finalstr += '<h5>' + validacion.razones[i] + '</h5>';
            }
            mostrarError(finalstr);
            return;
        }

        let value = input.val();
        if (value == '') {
            input.addClass('erroneo');
            return;
        }
        let data = $('#maquinas_lista')
            .find('option[value=' + value + ']');

        if (data.length == 0) {
            input.addClass('erroneo');
            return;
        }

        let data_id = data.attr('data-id');
        let nro_admin = data.attr('data-nro_admin');
        let sector = data.attr('data-sector');
        let isla = data.attr('data-isla');
        let marca_juego = data.attr('data-marca_juego');

        if (existeEnTablaIndividuales(data_id)) {
            fila.remove();
        }

        fila_val.id_maquina = data_id;
        fila_val.nro_admin = nro_admin;
        fila_val.sector = sector;
        fila_val.isla = isla;
        fila_val.marca_juego = marca_juego;

        let nueva_fila = filaEjemploIndividual();
        setearFilaProgresivoIndividual(nueva_fila, fila_val);
        fila.replaceWith(nueva_fila);

        nueva_fila.find('.cuerpoTablaAcciones').empty();

        let botonEditar = crearBoton('fa-pencil-alt').addClass('editar');
        nueva_fila.find('.cuerpoTablaAcciones').append(botonEditar);

        botonEditar.on('click', function() {
            let data = arregloProgresivoIndividual(nueva_fila);
            nueva_fila.replaceWith(filaEditableIndividualParcial(data));
        });

        let botonBorrar = crearBoton('fa-trash').addClass('borrar');
        nueva_fila.find('.cuerpoTablaAcciones').append(botonBorrar);

        botonBorrar.on('click', function() { nueva_fila.remove(); });
    });

    let botonCancelar = crearBoton('fa-times').addClass('cancelar');
    botonCancelar.on('click', function() {
        fila.remove();
    });

    fila.find('.cuerpoTablaAcciones')
        .empty().append(botonConfirmar).append(botonCancelar);
    return fila
}

function filaEditableIndividualParcial(data) {
    let fila = filaEjemploIndividual();

    //No puedo agregarle un editable de numeros con flechas
    //porque las flechas son muy grandes.

    const input = crearEditable('text');
    filaIndBase(fila).empty().append(input.clone());
    filaIndMaximo(fila).empty().append(input.clone());
    filaIndRecup(fila).empty().append(input.clone());
    filaIndVisible(fila).empty().append(input.clone());
    filaIndOculto(fila).empty().append(input.clone());

    setearFilaProgresivoIndividual(fila, data);

    let botonConfirmar = crearBoton('fa-check').addClass('confirmar').on('click', function() {
        fila.find('.erroneo').removeClass('erroneo');
        const fila_val = arregloProgresivoIndividual(fila);
        const validacion = validarFilaInd(fila);
        if (!validacion.porc_recup) {
            filaIndRecup(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.maximo) {
            filaIndMaximo(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.base) {
            filaIndBase(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.porc_oculto) {
            filaIndOculto(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.porc_visible) {
            filaIndVisible(fila).find('.editable').addClass('erroneo');
        }

        if (validacion.razones.length != 0) {
            console.log(validacion.razones);
            let finalstr = '';
            for (let i = 0; i < validacion.razones.length; i++) {
                finalstr += '<h5>' + validacion.razones[i] + '</h5>';
            }
            mostrarError(finalstr);
            return;
        }

        let nueva_fila = filaEjemploIndividual();
        setearFilaProgresivoIndividual(nueva_fila, fila_val)
        fila.replaceWith(nueva_fila);

        nueva_fila.find('.cuerpoTablaAcciones').empty();

        let botonEditar = crearBoton('fa-pencil-alt').addClass('editar');
        nueva_fila.find('.cuerpoTablaAcciones').append(botonEditar);

        botonEditar.on('click', function() {
            let data = arregloProgresivoIndividual(nueva_fila);
            nueva_fila.replaceWith(filaEditableIndividualParcial(data));
        });

        let botonBorrar = crearBoton('fa-trash').addClass('borrar');
        nueva_fila.find('.cuerpoTablaAcciones').append(botonBorrar);

        botonBorrar.on('click', function() { nueva_fila.remove(); });
    });

    let botonCancelar = crearBoton('fa-times').addClass('cancelar');
    botonCancelar.on('click', function() {
        let filaNoEditable = filaEjemploIndividual();
        setearFilaProgresivoIndividual(filaNoEditable, data);
        fila.replaceWith(filaNoEditable);
    });

    fila.find('.cuerpoTablaAcciones')
        .empty().append(botonConfirmar).append(botonCancelar);
    return fila;
}

function enviarFormularioIndividual() {
    limpiarErrores();
    let err = verificarFormularioIndividual();
    if (err.errores) {
        mostrarError(err.mensaje);
        return;
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    let mensajeExito = 'Los progresivos fueron cargados con éxito.';
    let url = 'progresivos/crearProgresivosIndividuales';

    let formData = {
        id_casino: $('#modalProgresivoIndividual_casino').val(),
        maquinas: []
    };

    let filas = $('#contenedorMaquinasIndividual tbody tr');

    filas.each(function(idx, f) {
        let fila = $(f);
        if (fila.find('input').length > 0) return;
        formData.maquinas.push(arregloProgresivoIndividual(fila));
    })

    $.ajax({
        type: 'POST',
        data: formData,
        url: url,
        success: function(data) {
            console.log(data);
            $('#mensajeExito')
                .find('.textoMensaje p')
                .replaceWith(
                    $('<p></p>')
                    .text(mensajeExito)
                );
            $('#modalProgresivoIndividual').modal('hide');
            $('#mensajeExito').show();
        },
        error: mostrarRespuestaError
    });
}


function enviarFormularioIndividualModif(desde, hasta) {
    limpiarErrores();
    let err = verificarFormularioIndividual();
    if (err.errores) {
        mostrarError(err.mensaje);
        return;
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    let mensajeExito = 'Los progresivos fueron modificados.';
    let url = 'progresivos/modificarProgresivosIndividuales';

    let formData = {
        id_casino: $('#modalProgresivoIndividual_casino').val(),
        desde: desde,
        hasta: hasta,
        maquinas: []
    };

    let filas = $('#contenedorMaquinasIndividual tbody tr');

    filas.each(function(idx, f) {
        let fila = $(f);
        if (fila.find('input').length > 0) return;
        formData.maquinas.push(arregloProgresivoIndividual(fila));
    })

    $.ajax({
        type: 'POST',
        data: formData,
        url: url,
        success: function(data) {
            console.log(data);
            $('#mensajeExito')
                .find('.textoMensaje p')
                .replaceWith(
                    $('<p></p>')
                    .text(mensajeExito)
                );
            $('#modalProgresivoIndividual').modal('hide');
            $('#mensajeExito').show();
        },
        error: mostrarRespuestaError
    });
}

function obtenerProgresivosIndividuales(data, success = function(x) { console.log(x) }, err = function(x) { console.log(x) }) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: 'POST',
        url: 'progresivos/buscarProgresivosIndividuales',
        data: data,
        dataType: 'json',
        success: success,
        error: err
    });
}

function mostrarProgresivoIndividual(data = null) {
    limpiarErrores();
    $('#modalProgresivoIndividual').modal('show');
    $('#modalProgresivoIndividual_seccionSup').show();
    $('#modalProgresivoIndividual_seccionParametros').show();
    $('#btn-agregarMaquinaIndividual').off().parent().parent().show();
    $('#contenedorMaquinasIndividual').empty();
    $('#inputPorcRecupIndividual').val(0);
    $('#inputMaximoIndividual').val(0);
    $('#inputBaseIndividual').val(0);
    $('#inputPorcVisibleIndividual').val(0);
    $('#inputPorcOcultoIndividual').val(0);

    let maq_html = $('.tablaMaquinasDivIndividual').clone().removeClass('ejemplo').show();
    let cuerpo_tabla = maq_html.find('.cuerpoTabla').empty();
    $('#contenedorMaquinasIndividual').append(maq_html);
    $('#btn-agregarMaquinaIndividual').off().on('click', function() {
        cuerpo_tabla.append(filaEditableIndividual());
    });

    $('#btn-guardarIndividual').off().on('click', enviarFormularioIndividual);

    if (data != null) {
        const callbackForm = function() { enviarFormularioIndividualModif(data.desde, data.hasta); }
        $('#btn-guardarIndividual').off().on('click', callbackForm);
        $('#btn-agregarMaquinaIndividual').off().parent().parent().hide();
        //Seteo el casino pq despues se usa para enviar el formulario.
        $('#modalProgresivoIndividual_casino').val(data.id_casino);
        $('#modalProgresivoIndividual_seccionSup').hide();
        $('#modalProgresivoIndividual_seccionParametros').hide();
        obtenerProgresivosIndividuales(data, function(resultados) {
            resultados.sort(function(r1, r2) {
                return r1.maquina.nro_admin >= r2.maquina.nro_admin;
            })
            for (let i = 0; i < resultados.length; i++) {
                const prog = resultados[i];
                const data = {
                    id_maquina: prog.maquina.id_maquina,
                    nro_admin: prog.maquina.nro_admin,
                    sector: prog.maquina.sector,
                    isla: prog.maquina.isla,
                    marca_juego: prog.maquina.marca_juego,
                    porc_recup: prog.porc_recup,
                    maximo: prog.pozo.nivel.maximo,
                    base: prog.pozo.nivel.base,
                    porc_visible: prog.pozo.nivel.porc_visible,
                    porc_oculto: prog.pozo.nivel.porc_oculto
                };

                let filaProgresivo = filaEjemploIndividual();
                setearFilaProgresivoIndividual(filaProgresivo, data);
                cuerpo_tabla.append(filaProgresivo);
            }
        });
    }
}

//Mostrar modal con los datos del Log
$(document).on('click', '#cuerpoTabla tr .detalle', function() {
    $('.modal-title').text('| VER MÁS');
    $('.modal-header').attr('style', 'font-family: Roboto-Black; background: #4FC3F7');
    $('.btn-agregarNivelProgresivo').hide();
    $('#btn-cancelar').text('SALIR');

    var id_progresivo = $(this).val();

    $.get("progresivos/obtenerProgresivo/" + id_progresivo, function(data) {
        console.log(data);
        mostrarProgresivo(data.progresivo, data.pozos, data.maquinas, false);
        $('#modalProgresivo').modal('show');
    });
});

//Mostrar modal con los datos del Juego cargado
$(document).on('click', '#cuerpoTabla tr .modificar', function() {
    $('#mensajeExito').hide();
    $('#btn-cancelar').text('CANCELAR');
    $('.btn-agregarNivelProgresivo').show();
    $('.modal-title').text('| MODIFICAR PROGRESIVO');
    $('.modal-header').attr('style', 'font-family: Roboto-Black; background: #ff9d2d');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-warningModificar');

    var id_progresivo = $(this).val();

    $.get("progresivos/obtenerProgresivo/" + id_progresivo, function(data) {
        mostrarProgresivo(data.progresivo, data.pozos, data.maquinas, true);
        $('#btn-guardar').val("modificar");
        $('#modalProgresivo').modal('show');
    });
});

$(document).on('click', '#tablaResultados thead tr th[value]', function(e) {
    $('#tablaResultados th').removeClass('activa');
    if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
        $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado', 'desc');
    } else {
        if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
            $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado', 'asc');
        } else {
            $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
        }
    }
    $('#tablaResultados th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
    clickIndice(e, $('#herramientasPaginacion').getCurrentPage(), $('#herramientasPaginacion').getPageSize());
});


function clickIndice(e, pageNumber, tam) {
    if (e != null) {
        e.preventDefault();
    }
    var tam = (isNaN(tam)) ? $('#herramientasPaginacion').getPageSize() : tam;
    var columna = $('#tablaResultados .activa').attr('value');
    var orden = $('#tablaResultados .activa').attr('estado');
    $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

function generarFilaTabla(progresivo) {
    let fila = $('#cuerpoTabla .filaEjemplo')
        .clone().removeClass('filaEjemplo').css('display', '');
    let casino = $('#busqueda_casino option[value=' + progresivo.id_casino + ']').text();

    fila.find('.nombre').text(progresivo.nombre);
    fila.find('.casino').text(casino);
    fila.find('.islas').text(progresivo.islas).attr('title',progresivo.islas);
    fila.find('.sectores').text(progresivo.sectores).attr('title',progresivo.sectores);
    fila.attr('id', 'progresivo' + progresivo.id_progresivo)
    fila.find('.modificar').val(progresivo.id_progresivo);
    fila.find('.detalle').val(progresivo.id_progresivo);
    fila.find('.eliminar').val(progresivo.id_progresivo).off()
        .on('click', function() {
            $('.modal-title').text('ADVERTENCIA');
            $('.modal-header').removeAttr('style');
            $('.modal-header').attr('style', 'font-family: Roboto-Black; color: #EF5350');

            $('#btn-eliminarModal').val(id_progresivo);
            $('#modalEliminar').modal('show');
            $('#btn-eliminarModal').off().on('click', function() {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                })
                $.ajax({
                    type: "DELETE",
                    url: "progresivos/eliminarProgresivo/" + progresivo.id_progresivo,
                    success: function(data) {
                        console.log(data);
                        fila.remove();
                        $("#tablaResultados").trigger("update");
                        $('#modalEliminar').modal('hide');
                    },
                    error: function(data) {
                        console.log('Error: ', data);
                    }
                })
            });
        });

    return fila;
}

function crearBoton(icono) {
    let btn = $('<button></button>').addClass('btn').addClass('btn-info');
    let i = $('<i></i>').addClass('fa').addClass('fa-fw').addClass(icono);
    btn.append(i);
    return btn;
}

function crearEditable(tipo,
    defecto = "",
    min = 0,
    max = 100,
    step = 0.001) {
    return $('<input></input>')
        .addClass('editable')
        .addClass('form-control')
        .attr('type', tipo)
        .attr('min', min)
        .attr('max', max)
        .attr('step', step)
        .val(defecto);
}

function filaEjemplo() {
    return $('.tablaPozoDiv.ejemplo').find('.filaEjemplo').clone().removeClass('filaEjemplo').show();
}

function filaEjemploMaquina() {
    return $('.tablaMaquinasDiv.ejemplo').find('.filaEjemplo').clone().removeClass('filaEjemplo').show();
}

function subirFila(fila) {
    let elem = fila[0];
    let parent = elem.parentNode;
    let arriba = elem.previousElementSibling;
    if (arriba === null) return;

    let elem_nivel = elem.getElementsByClassName('cuerpoTablaPozoNumero')[0];
    let arriba_nivel = arriba.getElementsByClassName('cuerpoTablaPozoNumero')[0];

    const arriba_nro = parseInt(arriba_nivel.innerText);
    const elem_nro = parseInt(elem_nivel.innerText);
    if (arriba_nro < elem_nro) {
        arriba_nivel.innerText = elem_nro.toString();
        elem_nivel.innerText = arriba_nro.toString();
        parent.insertBefore(elem, arriba);
    }
}

function bajarFila(fila) {
    let elem = fila[0];
    let parent = elem.parentNode;
    let abajo = elem.nextElementSibling;
    if (abajo === null) return;

    let elem_nivel = elem.getElementsByClassName('cuerpoTablaPozoNumero')[0];
    let abajo_nivel = abajo.getElementsByClassName('cuerpoTablaPozoNumero')[0];

    const abajo_nro = parseInt(abajo_nivel.innerText);
    const elem_nro = parseInt(elem_nivel.innerText);
    if (elem_nro < abajo_nro) {
        abajo_nivel.innerText = elem_nro.toString();
        elem_nivel.innerText = abajo_nro.toString();
        parent.insertBefore(abajo, elem);
    }
}

function borrarFila(fila) {
    let parent = fila.parent();
    fila.remove();
    let children = parent.children();
    children.each(function(i, c) {
        filaNumeroVal(c, i + 1);
    });
}

function crearFilaEditableNivel(valores = { id_nivel_progresivo: -1 }) {
    let fila = filaEjemplo();
    filaNumero(fila).empty();
    filaNombre(fila).empty().append(crearEditable("text"));
    filaBase(fila).empty().append(crearEditable("text"));
    filaMaximo(fila).empty().append(crearEditable("text"));
    filaVisible(fila).empty().append(crearEditable("text"));
    filaOculto(fila).empty().append(crearEditable("text"));
    fila.find('.editar').remove();
    fila.find('.cuerpoTablaPozoAcciones').empty();
    fila.find('.cuerpoTablaPozoAcciones').append(crearBoton('fa-check').addClass('confirmar'));
    fila.find('.cuerpoTablaPozoAcciones').append(crearBoton('fa-times').addClass('cancelar'));

    setearValoresFilaNivel(fila, valores);

    fila.find('.confirmar').on('click', function() {
        fila.find('.erroneo').removeClass('erroneo');
        const validacion = validarFila(fila);
        if (!validacion.nombre_nivel) {
            filaNombre(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.base) {
            filaBase(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.porc_visible) {
            filaVisible(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.porc_oculto) {
            filaOculto(fila).find('.editable').addClass('erroneo');
        }
        if (!validacion.maximo) {
            filaMaximo(fila).find('.editable').addClass('erroneo');
        }
        if (validacion.razones.length == 0) modificarNivel(fila);
        else {
            console.log(validacion.razones);
            let finalstr = '';
            for (let i = 0; i < validacion.razones.length; i++) {
                finalstr += '<h5>' + validacion.razones[i] + '</h5>';
            }
            mostrarError(finalstr);
            //fila.attr('title', finalstr);
            //@HACK nuestra version de bootstrap es vieja
            //y update o dispose nos tira un error
            //fila.attr('data-original-title', finalstr);
            //fila.tooltip('destroy');
            //fila.tooltip('show');
        }
    });

    fila.find('.subir').on('click', function() {
        subirFila(fila);
    });
    fila.find('.bajar').on('click', function() {
        bajarFila(fila);
    })

    fila.find('.cancelar').on('click', function() {
        let nueva_fila = filaEjemplo();
        setearValoresFilaNivel(nueva_fila, valores);

        nueva_fila.find('.cuerpoTablaPozoAcciones .editar').on('click', function() {
            let fila_editable = crearFilaEditableNivel(valores);
            nueva_fila.replaceWith(fila_editable);
        });

        nueva_fila.find('.cuerpoTablaPozoAcciones .borrar').on('click', function() {
            borrarFila(nueva_fila);
        });

        nueva_fila.find('.subir').on('click', function() {
            subirFila(nueva_fila);
        });
        nueva_fila.find('.bajar').on('click', function() {
            bajarFila(nueva_fila);
        })

        fila.replaceWith(nueva_fila);
    });

    return fila;
}


function modificarNivel(fila) {
    let vals = arregloNivel(fila);
    let nueva_fila = filaEjemplo();
    setearValoresFilaNivel(nueva_fila, vals);
    fila.replaceWith(nueva_fila);

    nueva_fila.find('.confirmar').replaceWith(crearBoton('fa-pencil-alt').addClass('editar'));
    nueva_fila.find('.cancelar').replaceWith(crearBoton('fa-trash-alt').addClass('borrar'));

    nueva_fila.find('.editar').on('click', function() {
        let valores = arregloNivel(fila);
        let fila_editable = crearFilaEditableNivel(valores);
        nueva_fila.replaceWith(fila_editable);
    });

    nueva_fila.find('.cuerpoTablaPozoAcciones .borrar').on('click', function() {
        borrarFila(nueva_fila);
    });

    nueva_fila.find('.subir').on('click', function() {
        subirFila(nueva_fila);
    });
    nueva_fila.find('.bajar').on('click', function() {
        bajarFila(nueva_fila);
    });;

    nueva_fila.parent().parent().parent().find('.agregar').attr('disabled', false);
}

function mostrarPozo(id_pozo, nombre, editable, niveles = {}) {
    let pozo_html = $('.tablaPozoDiv.ejemplo').clone().removeClass('ejemplo');
    pozo_html.find('.nombrePozo').text(nombre);
    $('#contenedorPozos').append(pozo_html);
    pozo_html.show();

    pozo_html.attr('data-id', id_pozo);

    pozo_html.find('.filaEjemplo').remove();

    let fila_ejemplo_pozo = filaEjemplo();

    for (var j = 0; j < niveles.length; j++) {
        let fila = fila_ejemplo_pozo.clone();

        const nivel = niveles[j];

        setearValoresFilaNivel(fila, nivel);

        fila.find('.cuerpoTablaPozoAcciones').children().each(
            function(index, child) {
                $(child).attr('disabled', !editable);
            }
        );

        fila.find('.cuerpoTablaPozoAcciones .editar').on('click', function() {
            let fila_editable = crearFilaEditableNivel(nivel);
            fila.replaceWith(fila_editable);
        });

        fila.find('.cuerpoTablaPozoAcciones .borrar').on('click', function() {
            borrarFila(fila);
        });

        fila.find('.subir').on('click', function() {
            subirFila(fila);
        }).attr('disabled', !editable);
        fila.find('.bajar').on('click', function() {
            bajarFila(fila);
        }).attr('disabled', !editable);

        pozo_html.find('.cuerpoTablaPozo').append(fila);
    }

    const editarPozoCallback = function() {
        let text_viejo = pozo_html.find('.nombrePozo').text();
        pozo_html.find('.nombrePozo').replaceWith(
            crearEditable('text')
            .addClass('nombrePozo')
            .val(text_viejo)
        );

        let boton = crearBoton('fa-check')
            .addClass('confirmarPozo')
            .removeClass('btn-info')
            .addClass('btn-link');
        pozo_html.find('.editarPozo').replaceWith(boton);

        const confirmarPozoCallback = function() {
            let valorModif = pozo_html.find('.nombrePozo').val();
            let text = $('<b></b>').text(valorModif);

            text.addClass('nombrePozo');
            pozo_html.find('.nombrePozo').replaceWith(text);

            let boton2 = crearBoton('fa-pencil-alt')
                .addClass('editarPozo')
                .removeClass('btn-info')
                .addClass('btn-link');

            pozo_html.find('.confirmarPozo').replaceWith(boton2);
            boton2.on('click', editarPozoCallback);
        };

        boton.on('click', confirmarPozoCallback);
    };

    pozo_html.find('.editarPozo').attr('disabled', !editable);
    pozo_html.find('.editarPozo').on('click', editarPozoCallback);
    pozo_html.find('.eliminarPozo').attr('disabled', !editable);
    pozo_html.find('.eliminarPozo').on('click', function() {
        pozo_html.remove();
    });

    pozo_html.find('.collapse').on('show.bs.collapse', function() {
        let icono = pozo_html.find('.abrirPozo i');
        let icono_nuevo = $('<i></i>').addClass('fa').addClass('fa-fw');
        icono.replaceWith(icono_nuevo.addClass('fa-angle-down'));
    });

    pozo_html.find('.collapse').on('hide.bs.collapse', function() {
        let icono = pozo_html.find('.abrirPozo i');
        let icono_nuevo = $('<i></i>').addClass('fa').addClass('fa-fw');
        icono.replaceWith(icono_nuevo.addClass('fa-angle-up'));
    });

    pozo_html.find('.abrirPozo').on('click', function() {
        let colapsable = pozo_html.find('.collapse');
        colapsable.collapse('toggle');
    });

    pozo_html.find('.agregar').attr('disabled', !editable);
    pozo_html.find('.agregar').on("click", function() {
        let fila = crearFilaEditableNivel();
        //La primera vez que se agrega una fila,
        //No se la deja cancelar la edicion sino que se la elimina.
        fila.find('.cancelar').replaceWith(
            crearBoton('fa-trash-alt').addClass('borrar')
        );
        fila.find('.borrar').on('click', function() {
            borrarFila(fila);
            pozo_html.find('.agregar').attr('disabled', false);
        })

        pozo_html.find('.cuerpoTablaPozo').append(fila);
        $(this).attr('disabled', true);

        const fila_anterior = fila.prev();
        if (fila_anterior.length > 0) {
            let nro = filaNumeroVal(fila_anterior);
            filaNumeroVal(fila, parseInt(nro) + 1);
        } else {
            filaNumeroVal(fila, '1');
        }

    });

}



function arregloPozos() {
    const pozos_html = $('.tablaPozoDiv').not('.ejemplo');

    let ret = [];

    for (i = 0; i < pozos_html.length; i++) {
        const pozo_html = $(pozos_html[i]);
        const id_pozo = pozo_html.attr('data-id');
        const descripcion = pozo_html.find('.nombrePozo').text();

        let filas = [];

        pozo_html.find('tbody tr').each(function(idx, c) {
            filas.push(arregloNivel($(c)));
        });

        const data = {
            id_pozo: id_pozo,
            descripcion: descripcion,
            niveles: filas
        };

        ret.push(data);
    }

    return ret;
}

function arregloMaquinas() {
    const maq_html = $($('.tablaMaquinasDiv').not('.ejemplo').first());
    let ret = [];

    maq_html.find('tbody tr').each(function(idx, c) {
        let fila = $(c);
        ret.push({
            id_maquina: fila.attr('data-id'),
            nro_admin: fila.find('.cuerpoTablaNroAdmin').text(),
            nro_isla: fila.find('.cuerpoTablaIsla').text(),
            sector_descripcion: fila.find('.cuerpoTablaSector').text(),
            marca_juego: fila.find('.cuerpoTablaMarcaJuego').text()
        });
    });

    return ret;
}

function mostrarProgresivo(progresivo, pozos, maquinas, editable) {
    limpiarErrores();
    $('#modalProgresivo_casino').attr('disabled', progresivo.id_progresivo != -1);
    $('#modalProgresivo_casino').val(progresivo.id_casino);
    //Si estamos creando un nuevo progresivo y solo tengo un solo casino para elegir, lo seteo
    if(progresivo.id_progresivo == -1 && $('#modalProgresivo_casino option').length == 1){
        $('#modalProgresivo_casino option')[0].selected = true;
    }
    $('#id_progresivo').val(progresivo.id_progresivo);
    $('#nombre_progresivo').val(progresivo.nombre);
    $('#nombre_progresivo').attr('disabled', !editable);
    $('#porc_recup').val(progresivo.porc_recup);
    $('#porc_recup').attr('disabled', !editable);
    $('#contenedorPozos').empty();
    $('#contenedorMaquinas').empty();
    $('#btn-agregarPozo').attr('disabled', !editable).off();
    $('#btn-agregarPozo').on('click', function() {
        mostrarPozo(-1, 'Pozo', editable);
    });

    for (var i = 0; i < pozos.length; i++) {
        mostrarPozo(pozos[i].id_pozo, pozos[i].descripcion, editable, pozos[i].niveles);
    }

    $('.abrirPozo').first().trigger('click');

    llenarTablaMaquinas(maquinas, editable);

    $('#btn-guardar').attr('disabled', !editable).off();

    $('#btn-guardar').on('click', function() {
        limpiarErrores();
        let err = verificarFormulario();

        if (err.errores) {
            mostrarError(err.mensaje);
            return;
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        })
        let mensajeExito = 'El progresivo fue modificado con éxito.';
        let url = 'progresivos/modificarProgresivo/' + progresivo.id_progresivo;


        let formData = {
            id_progresivo: progresivo.id_progresivo,
            id_casino: $('#modalProgresivo_casino').val(),
            nombre: $('#nombre_progresivo').val(),
            porc_recup: $('#porc_recup').val(),
            pozos: arregloPozos(),
            maquinas: arregloMaquinas(),
        };

        if (progresivo.id_progresivo == -1) {
            mensajeExito = 'El progresivo fue creado con éxito.';
            url = 'progresivos/crearProgresivo';
        }

        $.ajax({
            type: 'POST',
            data: formData,
            url: url,
            success: function(data) {
                console.log(data);
                let fila = $('#cuerpoTabla').find('#progresivo' + progresivo.id_progresivo);
                fila.find('.nombre').text(formData.nombre);
                $('#mensajeExito')
                    .find('.textoMensaje p')
                    .replaceWith(
                        $('<p></p>')
                        .text(mensajeExito)
                    );
                $('#modalProgresivo').modal('hide');
                $('#mensajeExito').show();
            },
            error: mostrarRespuestaError
        });
    });

}

function mostrarRespuestaError(err) {
    let respuesta = err.responseJSON;
    console.log(err);
    console.log(respuesta);
    let msj = "";
    if (respuesta != undefined) {
        let llaves = Object.keys(respuesta);
        for (let i = 0; i < llaves.length; i++) {
            let k = llaves[i];
            msj = msj + "<p>" + k + ' => ' + respuesta[k] + "</p>";
        }
    }

    mostrarError(msj);
}

function mostrarError(mensaje = '') {
    $('#mensajeError').hide();
    setTimeout(function() {
        $('#mensajeError').find('.textoMensaje')
            .empty()
            .append('<h2>ERROR</h2>')
            .append(mensaje);
        $('#mensajeError').show();
    }, 500);
}

function verificarFormulario() {
    let errores = false;
    let mensaje = "";

    let sin_completar = $('#modalProgresivo_cuerpo').find('input');
    if (sin_completar.length > 0) {
        sin_completar.each(function(idx, c) {
            $(c).addClass('erroneo');
        });
        errores = true;
        mensaje = mensaje + "<p>Tiene pozos, niveles o maquinas sin completar</p>";
    }

    let porc_recup = parseFloat($('#porc_recup').val());
    if (isNaN(porc_recup) || porc_recup > 100 || porc_recup < 0) {
        $('#porc_recup').addClass('erroneo');
        errores = true;
        mensaje = mensaje + "<p>El porcentaje de recuperacion es erroneo</p>";
    }

    let nombre_progresivo = $('#nombre_progresivo');
    if (nombre_progresivo.val() == "") {
        nombre_progresivo.addClass('erroneo');
        errores = true;
        mensaje = mensaje + "<p>Sin nombre de progresivo</p>";
    }

    let casino = $('#modalProgresivo_casino');
    if (casino.val() === null) {
        casino.addClass('erroneo');
        errores = true;
        mensaje = mensaje + "<p>Error en el casino seleccionado</p>";
    }

    return { errores: errores, mensaje: mensaje };
}

function verificarFormularioIndividual() {
    let errores = false;
    let mensaje = "";

    let sin_completar = $('#contenedorMaquinasIndividual .tablaMaquinasIndividual tr input');
    if (sin_completar.length > 0) {
        sin_completar.each(function(idx, c) {
            $(c).addClass('erroneo');
        });
        errores = true;
        mensaje = mensaje + "<p>Tiene progresivos sin completar</p>";
    }
    return { errores: errores, mensaje: mensaje };
}

function limpiarErrores() {
    $('.erroneo').removeClass('erroneo');
}

function setearFilaMaquinas(fila, id, nro_admin, sector, isla, marca_juego) {
    fila.find('.cuerpoTablaNroAdmin').text(nro_admin);
    fila.find('.cuerpoTablaSector').text(sector)
    fila.find('.cuerpoTablaIsla').text(isla);
    fila.find('.cuerpoTablaMarcaJuego').text(marca_juego);
    fila.attr('data-id', id);
    fila.find('.unlink').on('click', function() { fila.remove() });
}

function existeEnTablaMaquinas(dataid) {
    let tabla = $('.tablaMaquinasDiv').not('.ejemplo');
    return tabla.find('tbody tr[data-id="' + dataid + '"]').length != 0;
}

function filaEditableMaquina() {
    let fila = filaEjemploMaquina();
    let input = $('<input></input>')
        .addClass('form-control')
        .addClass('editable')
        .attr('list', 'maquinas_lista');

    setearFilaMaquinas(fila, '', '', '', '', '')

    fila.find('.cuerpoTablaNroAdmin').replaceWith(input);
    fila.find('.cuerpoTablaAcciones').empty();
    fila.find('.cuerpoTablaAcciones').append(crearBoton('fa-check').addClass('confirmar'));
    fila.find('.cuerpoTablaAcciones').append(crearBoton('fa-times').addClass('cancelar'));

    fila.find('.cancelar').on('click', function() {
        fila.remove();
    });

    fila.find('.confirmar').on('click', function() {
        let filaCompleta = filaEjemploMaquina();
        let value = input.val();
        let data = $('#maquinas_lista')
            .find('option[value=' + input.val() + ']');

        if (data.length == 0) return;

        let data_id = data.attr('data-id');
        let nro_admin = data.attr('data-nro_admin');
        let sector = data.attr('data-sector');
        let isla = data.attr('data-isla');
        let marca_juego = data.attr('data-marca_juego');
        if (existeEnTablaMaquinas(data_id)) {
            fila.remove();
        } else {
            setearFilaMaquinas(filaCompleta, data_id, nro_admin, sector, isla, marca_juego);
            fila.replaceWith(filaCompleta);
        }

    });

    return fila;
}

function llenarTablaMaquinas(maquinas, editable) {
    let maq_html = $('.tablaMaquinasDiv.ejemplo').clone().removeClass('ejemplo');
    $('#contenedorMaquinas').append(maq_html);
    maq_html.show();

    $('#btn-agregarMaquina').attr('disabled', !editable).off();
    $('#btn-agregarMaquina').on('click', function() {
        maq_html.find('.cuerpoTabla').append(filaEditableMaquina());
    });


    var fila_ejemplo_maq = filaEjemploMaquina();
    maq_html.find('.filaEjemplo').remove();
    for (var j = 0; j < maquinas.length; j++) {
        let fila = fila_ejemplo_maq.clone();

        setearFilaMaquinas(fila,
            maquinas[j].id_maquina, maquinas[j].nro_admin,
            maquinas[j].sector, maquinas[j].isla,
            maquinas[j].marca_juego);

        fila.find('.cuerpoTablaAcciones').children().each(
            function(index, child) {
                $(child).attr('disabled', !editable);
            });

        maq_html.find('.cuerpoTabla').append(fila);
    }
}