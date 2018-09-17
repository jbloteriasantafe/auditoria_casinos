var fechas = [];
var datos = [];
var estado ;
$(document).ready(function(){
    //Resetear componentes

    habilitarBusquedaMTM(false);
    $('#btn-buscarMTM').prop('disabled',true);

    $('#barraMaquinas').attr('aria-expanded','true');
    $('#maquinas').removeClass();
    $('#maquinas').addClass('subMenu1 collapse in');
    $('#informesMTM').removeClass();
    $('#informesMTM').addClass('subMenu2 collapse in');

    $('.tituloSeccionPantalla').text('Estadisticas de no toma');
    $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
    $('#opcInformesNoToma').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
    $('#opcInformesNoToma').addClass('opcionesSeleccionado');

    // eventoBusqueda(10,1,null,null,null);
    var pathname = window.location.pathname; // ej: /maquinas , /maquinas/5

    var arreglo = pathname.split("/");
    console.log(arreglo);
    switch (arreglo.length) {
      case 3:
        if(arreglo[2] !=0){
            eventoModal(arreglo[2]);
            console.log('carga pagina');
          }else{
            // eventoNuevo();
        }
        break;
      default:

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
$('#btn-minimizar').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$('#selectCasino').change(function(e){
    var id_casino = $(this).val();

    //Si se selecciona un casino habilitar la búsqueda de máquinas
    if (id_casino != 0) {
        habilitarBusquedaMTM(true, id_casino);
    }
    else {
        habilitarBusquedaMTM(false);
        $('#btn-buscarMTM').prop('disabled',true);
    }
});

/* CONTROLAR SELECCIÓN DE MÁQUINA */
$('#inputMaquina').on('seleccionado', function(){
    habilitarBotonDetalle(true);
});

$('#inputMaquina').on('deseleccionado', function(){
    habilitarBotonDetalle(false);
});

function habilitarBusquedaMTM(valor, id_casino){
  console.log(valor);

  if (valor) {
    $('#inputMaquina').generarDataList("http://" + window.location.host + "/maquinas/obtenerMTMEnCasino/" + id_casino  ,'maquinas','id_maquina','nro_admin',1);
    $('#inputMaquina').setearElementoSeleccionado(0,'');
  }
  else {
    //$('#inputMaquina').borrarDataList();
    $('#inputMaquina').val('');
  }

  $('#inputMaquina').prop('disabled', !valor);
}

function habilitarBotonDetalle(valor){
  $('#btn-buscarMTM').prop('disabled', !valor);
}

$('#btn-buscarMTM').click(function(e){
  var id_maquina = $('#inputMaquina').obtenerElementoSeleccionado();
  eventoModal(id_maquina);
});

function eventoModal(id_maquina){
  $('.detalleEstados').hide();
  $('#modalMaquinaContable').modal('show');

  $.get('http://' + window.location.host +"/estadisticas_no_toma/obtenerEstadisticasNoToma/" + id_maquina, function(data){

    $('#nro_admin').text(data.nro_admin);
    $('#casino').text(data.casino);
    $('#marca').text(data.marca);
    $('#sector').text(data.sector);

    var isla = data.isla.nro_isla;

    if(data.isla.codigo != null){
      isla += ' - ';
     isla += data.maquina.codigo;
    }
    $('#isla').text(isla);
    $('#juego').text(data.juego);
    $('#denominacion').text(data.denominacion);
    $('#devolucion').text(data.porcentaje_devolucion);
    //llenarTablaNoToma(data.resultados);
  })

}

$('#modalMaquinaContable').on('hidden.bs.modal', function(){
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
                    click: function(e){
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
                  events : {
                    click: function (e){
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
