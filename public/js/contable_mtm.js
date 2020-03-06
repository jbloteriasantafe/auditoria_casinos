var fechas = [];
var datos = [];
var estado;
$(document).ready(function() {
    //Resetear componentes
    $('#selectCasino').val(0);
    habilitarBusquedaMTM(false);
    $('#btn-buscarMTM').prop('disabled', true);

    $('#barraMaquinas').attr('aria-expanded', 'true');
    $('#maquinas').removeClass();
    $('#maquinas').addClass('subMenu1 collapse in');
    $('#informesMTM').removeClass();
    $('#informesMTM').addClass('subMenu2 collapse in');

    $('.tituloSeccionPantalla').text('Informe de MTM');
    $('#gestionarMaquinas').attr('style', 'border-left: 6px solid #3F51B5;');
    $('#opcInformesContableMTM').attr('style', 'border-left: 6px solid #25306b; background-color: #131836;');
    $('#opcInformesContableMTM').addClass('opcionesSeleccionado');
});

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function() {
    if ($(this).data("minimizar") == true) {
        $('.modal-backdrop').css('opacity', '0.1');
        $(this).data("minimizar", false);
    } else {
        $('.modal-backdrop').css('opacity', '0.5');
        $(this).data("minimizar", true);
    }
});

$('#selectCasino').change(function(e) {
    var id_casino = $(this).val();

    //Si se selecciona un casino habilitar la búsqueda de máquinas
    if (id_casino != 0) {
        habilitarBusquedaMTM(true, id_casino);
    } else {
        habilitarBusquedaMTM(false);
        $('#btn-buscarMTM').prop('disabled', true);
    }
});

/* CONTROLAR SELECCIÓN DE MÁQUINA */
$('#inputMaquina').on('seleccionado', function() {
    habilitarBotonDetalle(true);
});

$('#inputMaquina').on('deseleccionado', function() {
    habilitarBotonDetalle(false);
});

function habilitarBusquedaMTM(valor, id_casino) {
    console.log(valor);

    if (valor) {
        $('#inputMaquina').generarDataList("http://" + window.location.host + "/maquinas/obtenerMTMEnCasino/" + id_casino, 'maquinas', 'id_maquina', 'nro_admin', 1);
        $('#inputMaquina').setearElementoSeleccionado(0, '');
    } else {
        //$('#inputMaquina').borrarDataList();
        $('#inputMaquina').val('');
    }

    $('#inputMaquina').prop('disabled', !valor);
}

function habilitarBotonDetalle(valor) {
    $('#btn-buscarMTM').prop('disabled', !valor);
}

function limpiarNull(str, c = '-') {
    return str === null ? c : str;
}

function buscarTipoCausa(tipo_causas,id){
    for(let i=0;i<tipo_causas.length;i++){
        if(tipo_causas[i].id_tipo_causa_no_toma == id){
            return tipo_causas[i];
        }
    }
    return null;
}

function movimientoAString(mov) {
    retstr = "";
    retstr += "<br style=''>Denominacion de juego: " + limpiarNull(mov.denominacion) + "</br>";
    retstr += "<br style=''>Numero de isla: " + limpiarNull(mov.nro_isla) + "</br>";
    retstr += "<br style=''>Sector: " + limpiarNull(mov.sector) + "</br>";
    retstr += "<br style=''>Juego: " + limpiarNull(mov.nombre_juego) + "</br>";
    retstr += "<br style=''>Porcentaje devolucion: " + limpiarNull(mov.porcentaje_devolucion) + "</br>";
    retstr += "<br style=''>Tipo movimiento: " + limpiarNull(mov.descripcion) + "</br>";
    return retstr;
}
$('#btn-buscarMTM').click(function(e) {
    $('#tablaContadoresTomados tbody tr').remove();
    $('.detalleEstados').hide();
    $('#modalMaquinaContable').modal('show');
    var id_maquina = $('#inputMaquina').obtenerElementoSeleccionado();

    $.get('http://' + window.location.host + "/obtenerInformeContableDeMaquina/" + id_maquina, function(data) {
        console.log(data);
        $('#nro_admin').text(limpiarNull(data.nro_admin));
        $('#casino').text(limpiarNull(data.casino));
        $('#marca').text(limpiarNull(data.marca));
        $('#sector').text(limpiarNull(data.sector));
        var isla = data.isla.nro_isla;

        if (data.isla.codigo != null) {
            isla += ' - ';
            isla += data.isla.codigo;
        }

        $('#isla').text(limpiarNull(isla));
        $('#juego').text(limpiarNull(data.juego));
        $('#denominacion').text(limpiarNull(data.denominacion_juego));
        $('#devolucion').text(limpiarNull(data.porcentaje_devolucion));
        const moneda = data.moneda != null? limpiarNull(data.moneda.descripcion,'') : '';
        $('#monedaProducido').text('$' + moneda);
        $('#producido').text(addCommas(data.producido));
        for (var i = 0; i < data.datos.length; i++) {
            fechas.push(data.datos[i].fecha);
            datos.push(data.datos[i].valor);
        }

        estado = data.arreglo;

        $('#listaMovimientos .clonado').remove();

        //Si hay movimientos
        if (data.movimientos.length) {
            $('#mensajeMovimiento').hide();
            data.movimientos.forEach(m =>{
                let movimiento = $('#mov').clone();
                movimiento.addClass('clonado');
                movimiento.show();
                $('.fecha', movimiento).text(limpiarNull(m.fecha));
                $('.boton', movimiento).attr('title', movimientoAString(m));
                let razon = limpiarNull(m.razon);
                $('.razon', movimiento).empty().append(razon.split('\n').join('<br />'));
                $('#listaMovimientos').append(movimiento);
            });
            $('[data-toggle="tooltip"]').tooltip();
        } else {
            $('#mensajeMovimiento').show();
        }

        for (var i = 0; i < data.relevamientos.length; i++) {

            var fila = $(document.createElement('tr'));
            const rel = data.relevamientos[i];

            fila.append($('<td>').css('align', 'center')
                    .text(rel.fecha_carga))
                .append($('<td>').css('align', 'center')
                    .text(rel.nro_admin));

            fila.append($('<td>').css('align', 'center')
                .text(limpiarNull(rel.cont1)));
            fila.append($('<td>').css('align', 'center')
                .text(limpiarNull(rel.cont2)));
            fila.append($('<td>').css('align', 'center')
                .text(limpiarNull(rel.cont3)));
            fila.append($('<td>').css('align', 'center')
                .text(limpiarNull(rel.cont4)));
            fila.append($('<td>').css('align', 'center')
                .text(limpiarNull(rel.cont5)));
            fila.append($('<td>').css('align', 'center')
                .text(limpiarNull(rel.cont6)));
            fila.append($('<td>').css('align', 'center')
            .text(limpiarNull(rel.producido_calculado_relevado)));
            fila.append($('<td>').css('align', 'center')
            .text(limpiarNull(rel.producido_importado)));
            fila.append($('<td>').css('align', 'center')
            .text(limpiarNull(rel.diferencia)));
            const causa = buscarTipoCausa(
                data.tipos_causa_no_toma,
                rel.id_tipo_causa_no_toma);
            let descripcion = '-';
            if(causa != null) descripcion = causa.descripcion;
            fila.append($('<td>').css('align', 'center')
            .text(descripcion));
            $('#tablaContadoresTomados tbody').append(fila);
        }
        setTimeout(function(){
            generarGraficoMTM(fechas, datos);
        },200);
    })


});

$('#modalMaquinaContable').on('shown.bs.modal', function() {
    //Antes se hacia aca pero el evento no se estaba ejecutando a veces
    //Por lo que lo hago directamente cuando se carga todo.
    //generarGraficoMTM(fechas, datos);
});

$('#modalMaquinaContable').on('hidden.bs.modal', function() {
    fechas = [];
    datos = [];
    estado = null;
    $('.clonado').remove();
})

/******* GRÁFICOS ********/
function generarGraficoMTM(fechas, data) {
    //Armar los arreglos
    // for (var i = 0; i < data.length; i++) {
    //   data[i]
    // }

    Highcharts.chart('graficoSeguimientoContadores', {
        chart: {
            backgroundColor: "#fff",
            type: 'area',
            events: {
                click: function(e) {
                    console.log(e.xAxis[0].value,
                        e.yAxis[0].value);

                }
            }
        },
        title: {
            text: ' '
        },
        subtitle: {
            text: ''
        },
        xAxis: {
            categories: fechas,
            tickmarkPlacement: 'on',
            title: {
                enabled: false
            }
        },
        yAxis: {
            title: {
                text: ''
            },
            // labels: {
            //     formatter: function () {
            //         return this.value / 1000000 + "M";
            //     }
            // }
        },
        tooltip: {
            split: true,
            valuePrefix: '$ ',
        },
        plotOptions: {
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function(e) {
                            //Setear fecha y dinero
                            $('#fechaEstado').text(this.category);
                            $('#producidoEstado').text('$ ' + this.y);

                            console.log(this.category, this.y, this.x);
                            mostrarEstado(this.x);

                            //Scrollear hasta la posición de la información
                            var pos = $('.detalleEstados:first').offset().top;
                            $("#modalMaquinaContable").animate({ scrollTop: pos }, "slow");
                        }
                    }
                },
                fillOpacity: 0.4
            }
        },
        series: [{
            name: 'MTM',
            data: data,
            color: '#00E676',
            // },{
            //     name: 'BINGO',
            //     data: brutoMesas,
            //     color: colorBingo,
            // },{
            //     name: 'MESAS',
            //     data: brutoBingo,
            //     color: colorMesas,
        }]
    });

}

function mostrarEstado(posicion) {
    console.log(estado[posicion]);

    var estado_contadores = estado[posicion].estado_contadores;
    var estado_relevamiento = estado[posicion].estado_relevamiento;
    var estado_producido = estado[posicion].estado_producido;

    //CONTADORES
    if (estado_contadores.cerrado) {
        $('.contador_cerrado.fa-check').show();
        $('.contador_cerrado.fa-times').hide();
    } else {
        $('.contador_cerrado.fa-check').hide();
        $('.contador_cerrado.fa-times').show();
    }

    if (estado_contadores.importado) {
        $('.contador_importado.fa-check').show();
        $('.contador_importado.fa-times').hide();
    } else {
        $('.contador_importado.fa-check').hide();
        $('.contador_importado.fa-times').show();
    }


    //RELEVAMIENTO
    if (estado_relevamiento.relevado) {
        $('.relevamiento_relevado.fa-check').show();
        $('.relevamiento_relevado.fa-times').hide();
    } else {
        $('.relevamiento_relevado.fa-check').hide();
        $('.relevamiento_relevado.fa-times').show();
    }

    if (estado_relevamiento.validado) {
        $('.relevamiento_validado.fa-check').show();
        $('.relevamiento_validado.fa-times').hide();
    } else {
        $('.relevamiento_validado.fa-check').hide();
        $('.relevamiento_validado.fa-times').show();
    }


    //PRODUCIDO
    if (estado_producido.importado) {
        $('.producido_importado.fa-check').show();
        $('.producido_importado.fa-times').hide();
    } else {
        $('.producido_importado.fa-check').hide();
        $('.producido_importado.fa-times').show();
    }

    if (estado_producido.validado) {
        $('.producido_validado.fa-check').show();
        $('.producido_validado.fa-times').hide();
    } else {
        $('.producido_validado.fa-check').hide();
        $('.producido_validado.fa-times').show();
    }

    $('.detalleEstados').show();
}