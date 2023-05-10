var fechas = [];
var datos = [];
var estado;
$(document).ready(function() {
    //Resetear componentes
    $('.tituloSeccionPantalla').text('Estadisticas de no toma');
    const ultimo_valor_url = window.location.pathname.split('/').slice(-1);
    if(isNaN(ultimo_valor_url)){
      habilitarBusquedaMTM(false);
      $('#btn-buscarMTM').prop('disabled', true);
    }
    else{
      setTimeout(function(){
        eventoModal(ultimo_valor_url);
        habilitarBusquedaMTM(true);
      },1000);
    }
});

function llenarTablaNoToma(motivos) {
    $('#tablaNoToma').empty();
    for (var i = 0; i < motivos.length; i++) {
        var fila = $('<tr>');

        fila.append($('<td>').text(motivos[i].fecha));
        fila.append($('<td>').text(motivos[i].descripcion));
        $('#tablaNoToma').append(fila);
    }

}

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
        $('#inputMaquina').generarDataList("/estadisticas_no_toma/obtenerMTMEnCasino/" + id_casino, 'maquinas', 'id_maquina', 'nro_admin', 1);
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

$('#btn-buscarMTM').click(function(e) {
    var id_maquina = $('#inputMaquina').obtenerElementoSeleccionado();
    eventoModal(id_maquina);
});

function eventoModal(id_maquina) {
    $('.detalleEstados').hide();
    $('#modalMaquinaContable').modal('show');


    $.get("/estadisticas_no_toma/obtenerEstadisticasNoToma/" + id_maquina, function(data) {

        $('#nro_admin').text(data.maquina.nro_admin);
        $('#casino').text(data.maquina.casino);
        $('#marca').text(data.maquina.marca);
        $('#sector').text(data.maquina.sector);

        var isla = data.maquina.nro_isla;

        if (data.maquina.codigo != null) {
            isla += ' - ';
            isla += data.maquina.codigo;
        }
        $('#isla').text(isla);
        $('#juego').text(data.maquina.juego);
        $('#denominacion').text(data.maquina.denominacion);
        $('#devolucion').text(data.maquina.porcentaje_devolucion);
        $('#modalMaquinaContable').modal('show');
        llenarTablaNoToma(data.resultados);
        generarTablaRelevamientos(data.maquina.id_casino, data.maquina.nro_admin, 5)
    })




}

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

};

function generarTablaRelevamientos(id_casino, nro_admin, cant_rel) {

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

    var formData = {
        id_casino: id_casino,
        nro_admin: nro_admin,
        cantidad_relevamientos: cant_rel,
    }

    $.ajax({
        type: 'POST',
        url: '/estadisticas_no_toma/obtenerUltimosRelevamientosPorMaquinaNroAdmin',
        data: formData,
        dataType: 'json',
        success: function(data) {

            $('#tablaRelevamientosDesdeNoToma tbody tr').remove();

            for (var i = 0; i < data.detalles.length; i++) {

                var producidoCalculado = data.detalles[i].tipos_causa_no_toma == null ?
                    data.detalles[i].producido_calculado_relevado : data.detalles[i].tipos_causa_no_toma;

                var fila = $('<tr>');

                fila.append($('<td>').text(data.detalles[i].fecha))

                data.detalles[i].cont1 != null ? fila.append($('<td>').text(data.detalles[i].cont1)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont2 != null ? fila.append($('<td>').text(data.detalles[i].cont2)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont3 != null ? fila.append($('<td>').text(data.detalles[i].cont3)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont4 != null ? fila.append($('<td>').text(data.detalles[i].cont4)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont5 != null ? fila.append($('<td>').text(data.detalles[i].cont5)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont6 != null ? fila.append($('<td>').text(data.detalles[i].cont6)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont7 != null ? fila.append($('<td>').text(data.detalles[i].cont7)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont8 != null ? fila.append($('<td>').text(data.detalles[i].cont8)) : fila.append($('<td>').text('-'));

                data.detalles[i].coinin != null ? fila.append($('<td>').text(data.detalles[i].coinin)) : fila.append($('<td>').text('-'));
                data.detalles[i].coinout != null ? fila.append($('<td>').text(data.detalles[i].coinout)) : fila.append($('<td>').text('-'));
                data.detalles[i].jackpot != null ? fila.append($('<td>').text(data.detalles[i].jackpot)) : fila.append($('<td>').text('-'));
                data.detalles[i].progresivo != null ? fila.append($('<td>').text(data.detalles[i].progresivo)) : fila.append($('<td>').text('-'));

                fila.append($('<td>').text(producidoCalculado))

                data.detalles[i].producido_importado != null ? fila.append($('<td>').text(data.detalles[i].producido_importado)) : fila.append($('<td>').text('-'));

                data.detalles[i].diferencia != null ? fila.append($('<td>').text(data.detalles[i].diferencia)) : fila.append($('<td>').text('-'));


                $('#tablaRelevamientosDesdeNoToma tbody').append(fila);

            }
        },
    });

};
