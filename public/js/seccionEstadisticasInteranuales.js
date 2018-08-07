meses=["Ene", "Feb" , "Mar", "Abr" , "May" , "Jun" , "Jul", "Ago" ,"Sep", "Oct" , "Nov" , "Dic"];

$(document).ready(function(){

  $('#barraEstadisticas').attr('aria-expanded','true');
  $('#tablero').removeClass();
  $('#tablero').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Estadisticas interanuales');
  $('#opcEstadisticasInteranuales').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcEstadisticasInteranuales').addClass('opcionesSeleccionado');

    $('#dtpFechaDesde').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      showClear: true,
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 4
    });

    $('#dtpFechaHasta').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 4
    });

    $('.informacionEstadistica').hide();
});

function addCommas(nStr) {
    if(nStr == '-'){
      return '-';
    }
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? ',' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + '.' + '$2');
    }
    return x1 + x2;
}

function generarSeguimientoBrutoYcanon(porcentajesBruto, porcentajesCanon){

  Highcharts.chart('seguimientoCanon', {
      chart: {
          backgroundColor: "#f5f5f5",
      },
      title: {
          text: ''
      },
      subtitle: {
          text: ''
      },
      xAxis: {
          categories: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
              'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
      },
      yAxis: {
          title: {
              text: ' '
            },
            labels: { formatter: function() { return this.value + ' %'; }
            },
          plotLines: [{
              value: 0,
              width: 1,
              color: '#808080'
          }]
      },
      tooltip: {
          crosshairs: [true]
      },
      series: [{
        name: 'Canon',
        data: porcentajesCanon,
        threshold: 0,
        negativeColor: 'red',
        color: 'green',
        tooltip: {
            valueDecimals: 2
        }
      }]
  });
  Highcharts.chart('seguimientoBruto', {
        chart: {
            backgroundColor: "#f5f5f5",
        },
        title: {
            text: ''
        },
        subtitle: {
            text: ''
        },
        xAxis: {
            categories: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
        },
        yAxis: {
            title: {
                text: ' ',
              },
              labels: { formatter: function() { return this.value + ' %'; }
              },
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        tooltip: {
            crosshairs: [true]
        },
        series: [{
          name: 'Bruto',
          data: porcentajesBruto,
          threshold: 0,
          negativeColor: 'red',
          color: 'green',
          tooltip: {
              valueDecimals: 2
          }
        }]
    });
}

function generarSeguimientoPorJuego(porcentajesMTM, porcentajesMesas, porcentajesBingo){

  Highcharts.chart('seguimientoCanonJuego', {
     title: {
         text: ''
     },
     subtitle: {
         text: ''
     },
     xAxis: {
         categories: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
             'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
     },


     yAxis: {
         title: {
             text: ' '
         },
         labels: { formatter: function() { return this.value + ' %'; }
         },
         plotLines: [{
             value: 0,
             width: 1,
             color: '#808080'
         }]
     },
     tooltip: {
         crosshairs: [true]
     },
     series: [{
         name: 'MTM',
         data: porcentajesMTM,
         threshold: 0,
         color: '#FF7043',
         negativeColor: '#FFCCBC',
         tooltip: {
             valueDecimals: 2
         }
       },{
         name: 'MESAS',
         data: porcentajesMesas,
         visible: false,
         threshold: 0,
         color: '#7E57C2',
         negativeColor: '#D1C4E9',
         tooltip: {
             valueDecimals: 2
         }
       },{
         name: 'BINGO',
         data: porcentajesBingo,
         threshold: 0,
         visible: false,
         color: '#66BB6A',
         negativeColor: '#C8E6C9',
         tooltip: {
             valueDecimals: 2
         }
       }
     ]
  });
}

function generarTablas(resultadosX , resultadosY , resultadosMTM, resultadosMesas, resultadosBingo){

  $('#cuerpoTablaXY').empty();

  $('.cabeceraX').text('AÑO ' + $('#fecha_desde').val());
  $('.cabeceraY').text('AÑO ' + $('#fecha_hasta').val());

  var casino = $('input[name=opcion2]:checked').parent().find('h5').text();

  switch (casino) {
    case 'Santa Fe':
      $('.cabeceraCasino').text(casino).css('background-color','#ef3e42');
      break;
    case 'Rosario':
      $('.cabeceraCasino').text(casino).css('background-color','#f58426');
      break;
    case 'Melincué':
      $('.cabeceraCasino').text(casino).css('background-color','#00b259');
      break;
  }



  var meses_c=["Enero", "Febrero" , "Marzo", "Abril" , "Mayo" , "Junio" , "Julio", "Agosto" ,"Septiembre", "Octubre" , "Noviembre" , "Diciembre"];

  for (var i = 0; i < resultadosX.meses.length; i++) {

      var fecha=resultadosX.meses[i].fecha.split("-");

      $('#cuerpoTablaXY').append($('<tr>')
          .append($('<td>')
                .addClass('col-xs-2  col-xs-offset-1')
                .css('font-weight', 'bold')
                .text(meses_c[(fecha[1].replace(/^0+/, '') ) - 1 ])
          )

          .append($('<td>')
                .addClass('col-xs-2')
                .text('$' +  addCommas(resultadosX.meses[i].bruto))
          )

          .append($('<td>')
                .addClass('col-xs-2')
                .text('$' +  addCommas(resultadosX.meses[i].canon))
          )

          .append($('<td>')
                .addClass('col-xs-2')
                .text('$' +  addCommas(resultadosY.meses[i].bruto))
          )
          .append($('<td>')
                .addClass('col-xs-2')
                .text('$' +  addCommas(resultadosY.meses[i].canon ))
          )
    )


    $('#cuerpoTablaX').append($('<tr>')
        .append($('<td>')
              .addClass('col-xs-2 col-xs-offset-1 ')
              .css('font-weight', 'bold')
              .text(meses_c[(fecha[1].replace(/^0+/, '') ) - 1 ])
        )

        .append($('<td>')
              .addClass('col-xs-2')
              .text('$' +  addCommas(resultadosMTM.año_x.meses[i].bruto))
        )

        .append($('<td>')
              .addClass('col-xs-2')
              .text('$' +  addCommas(resultadosMesas.año_x.meses[i].canon))
        )

        .append($('<td>')
              .addClass('col-xs-3')
              .text('$' +  addCommas(resultadosBingo.año_x.meses[i].bruto))
        )

  )

  $('#cuerpoTablaY').append($('<tr>')
      .append($('<td>')
            .addClass('col-xs-2  col-xs-offset-1')
            .css('font-weight', 'bold')
            .text(meses_c[(fecha[1].replace(/^0+/, '') ) - 1 ])
      )

      .append($('<td>')
            .addClass('col-xs-2')
            .text('$' +  addCommas(resultadosMTM.año_y.meses[i].bruto))
      )

      .append($('<td>')
            .addClass('col-xs-2')
            .text('$' +  addCommas(resultadosMesas.año_y.meses[i].canon))
      )

      .append($('<td>')
            .addClass('col-xs-3')
            .text('$' +  addCommas(resultadosBingo.año_y.meses[i].bruto))
      )

  )


  }
  $('#pieTablaXY').append($('<tr>')
      .css('font-weight', 'bold')
      .append($('<td>')
          .addClass('col-xs-2  col-xs-offset-1')
          .text('Total')
      )
      .append($('<td>')
          .addClass('col-xs-2')
          .text('$' +  addCommas(resultadosX.bruto))
      )

      .append($('<td>')
          .addClass('col-xs-2')
          .text('$' +  addCommas(resultadosX.canon))

      )

      .append($('<td>')
          .addClass('col-xs-2')
          .text('$' +  addCommas(resultadosY.bruto))

      )

      .append($('<td>')
          .addClass('col-xs-2')
          .text('$' +  addCommas(resultadosY.canon))

      )
  )
  $('#pieTablaX').append($('<tr>')
      .css('font-weight', 'bold')
      .append($('<td>')
          .addClass('col-xs-2  col-xs-offset-1')
          .text('Total')
      )
      .append($('<td>')
          .addClass('col-xs-2')
          .text('$' +  addCommas(resultadosMTM.año_x.bruto))
      )

      .append($('<td>')
          .addClass('col-xs-2')
          .text('$' +  addCommas(resultadosMesas.año_x.canon))

      )

      .append($('<td>')
          .addClass('col-xs-3')
          .text('$' +  addCommas(resultadosBingo.año_x.bruto))

      )
  )

  $('#pieTablaY').append($('<tr>')
      .css('font-weight', 'bold')
      .append($('<td>')
          .addClass('col-xs-2  col-xs-offset-1')
          .text('Total')
      )
      .append($('<td>')
          .addClass('col-xs-2')
          .text('$' +  addCommas(resultadosMTM.año_y.bruto))
      )

      .append($('<td>')
          .addClass('col-xs-2')
          .text('$' +  addCommas(resultadosMesas.año_y.canon))

      )

      .append($('<td>')
          .addClass('col-xs-3')
          .text('$' +  addCommas(resultadosBingo.año_y.bruto))

      )
  )

}

$('#btn-generarGraficos').click(function(){


  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var formData = {
    fecha_desde: $('#fecha_desde').val(),
    fecha_hasta: $('#fecha_hasta').val(),
    id_casino: $('input[name=opcion2]:checked').val(),

  }
  console.log(formData);
  $.ajax({
      type: "POST",
      url: 'interanuales',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('.informacionEstadistica').show();

        generarTablas(data.resultadosX, data.resultadosY, data.resultadosMTM ,data.resultadosMesas ,data.resultadosBingo);
        generarSeguimientoBrutoYcanon(data.porcentajesBruto ,data.porcentajesCanon);
        generarSeguimientoPorJuego(data.porcentajesMTM ,data.porcentajesMesas , data.porcentajesBingo);

      },
      error: function (data) {
        // $('.informacionEstadistica').hide();
        // var chart = ($('#recBrutoJuego').highcharts());
        // chart.destroy();
      },
    });
});
