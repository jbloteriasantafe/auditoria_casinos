var fechas = [];
var datos = [];
var estado ;
$(document).ready(function(){
    //Resetear componentes
    $('#selectCasino').val(0);
    habilitarBusquedaMTM(false);
    $('#btn-buscarMTM').prop('disabled',true);

    $('#barraMaquinas').attr('aria-expanded','true');
    $('#maquinas').removeClass();
    $('#maquinas').addClass('subMenu1 collapse in');
    $('#informesMTM').removeClass();
    $('#informesMTM').addClass('subMenu2 collapse in');

    $('.tituloSeccionPantalla').text('Informe de MTM');
    $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
    $('#opcInformesContableMTM').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
    $('#opcInformesContableMTM').addClass('opcionesSeleccionado');
});

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
  $('.detalleEstados').hide();
  $('#modalMaquinaContable').modal('show');
  var id_maquina = $('#inputMaquina').obtenerElementoSeleccionado();

  $.get('http://' + window.location.host +"/obtenerInformeContableDeMaquina/" + id_maquina, function(data){
    console.log(data);
    $('#nro_admin').text(data.nro_admin);
    $('#casino').text(data.casino);
    $('#marca').text(data.marca);
    $('#sector').text(data.sector);
    var isla = data.isla.nro_isla;

    if(data.isla.codigo != null){
      isla += ' - ';
     isla += data.isla.codigo;
    }

    $('#isla').text(isla);
    $('#juego').text(data.juego);
    $('#denominacion').text(data.denominacion);
    $('#devolucion').text(data.porcentaje_devolucion);
    $('#producido').text(addCommas(data.producido));
    for (var i = 0; i < data.datos.length; i++) {
       fechas.push(data.datos[i].fecha);
       datos.push(data.datos[i].valor);
    }

    estado = data.arreglo;

    $('#listaMovimientos .clonado').remove();

    //Si hay movimientos
    if(data.movimientos.length){
      $('#mensajeMovimiento').hide();

      $('#mov .fecha').text(data.movimientos[0].fecha);
      $('#mov .razon').text(data.movimientos[0].razon);

      for (var i = 1; i < data.movimientos.length; i++) {
        var movimiento = $('#mov').clone();
        movimiento.addClass('clonado');
        movimiento.show();
        $('.fecha' , movimiento).text(data.movimientos[i].fecha);
        $('.razon' , movimiento).text(data.movimientos[i].razon);
        $('#listaMovimientos').append(movimiento);
      }
    }
    else {
      $('#mensajeMovimiento').show();
    }

    // for (var i = 0; i < data.relevamientos; i++) {
    //
    //   var fila= $(document.createElement('tr'));
    //
    //   fila.append($('<td>').css('align','center')
    //       .text(data.relevamientos[i].fecha))
    //       .append($('<td>').css('align','center')
    //       .text(data.relevamientos[i].nro_admin))
    //       .append($('<td>').css('align','center')
    //       .text(data.relevamientos[i].cont1))
    //       .append($('<td>').css('align','center')
    //       .text(data.relevamientos[i].cont2))
    //       .append($('<td>').css('align','center')
    //       .text(data.relevamientos[i].cont3))
    //       .append($('<td>').css('align','center')
    //       .text(data.relevamientos[i].cont4))
    //       .append($('<td>').css('align','center')
    //       .text(data.relevamientos[i].cont5))
    //       .append($('<td>').css('align','center')
    //       .text(data.relevamientos[i].cont6))
    //
    // }
  })


});

$('#modalMaquinaContable').on('shown.bs.modal', function(){
    generarGraficoMTM(fechas, datos);
});

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
function mostrarEstado(posicion){
  console.log(estado[posicion]);

  var estado_contadores = estado[posicion].estado_contadores;
  var estado_relevamiento = estado[posicion].estado_relevamiento;
  var estado_producido = estado[posicion].estado_producido;

  //CONTADORES
  if (estado_contadores.cerrado) {
    $('.contador_cerrado.fa-check').show();
    $('.contador_cerrado.fa-times').hide();
  }else {
    $('.contador_cerrado.fa-check').hide();
    $('.contador_cerrado.fa-times').show();
  }

  if (estado_contadores.importado) {
    $('.contador_importado.fa-check').show();
    $('.contador_importado.fa-times').hide();
  }else {
    $('.contador_importado.fa-check').hide();
    $('.contador_importado.fa-times').show();
  }


  //RELEVAMIENTO
  if (estado_relevamiento.relevado) {
    $('.relevamiento_relevado.fa-check').show();
    $('.relevamiento_relevado.fa-times').hide();
  }else {
    $('.relevamiento_relevado.fa-check').hide();
    $('.relevamiento_relevado.fa-times').show();
  }

  if (estado_relevamiento.validado) {
    $('.relevamiento_validado.fa-check').show();
    $('.relevamiento_validado.fa-times').hide();
  }else {
    $('.relevamiento_validado.fa-check').hide();
    $('.relevamiento_validado.fa-times').show();
  }


  //PRODUCIDO
  if (estado_producido.importado) {
    $('.producido_importado.fa-check').show();
    $('.producido_importado.fa-times').hide();
  }else {
    $('.producido_importado.fa-check').hide();
    $('.producido_importado.fa-times').show();
  }

  if (estado_producido.validado) {
    $('.producido_validado.fa-check').show();
    $('.producido_validado.fa-times').hide();
  }else {
    $('.producido_validado.fa-check').hide();
    $('.producido_validado.fa-times').show();
  }

  $('.detalleEstados').show();
}
