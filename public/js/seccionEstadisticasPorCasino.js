meses=["ENE", "FEB" , "MAR", "ABR" , "MAY" , "JUN" , "JUL", "AGO" ,"SEP", "OCT" , "NOV" , "DIC"];
var colorMTM = "#448AFF";
var colorBrutoMTM = "#82B1FF";

var colorBingo = "#FFC400";
var colorBrutoBingo = "#FFE57F";

var colorMesas = "#9575CD ";
var colorBrutoMesas = "#B39DDB";

$(document).ready(function(){

  $('#barraEstadisticas').attr('aria-expanded','true');
  $('#tablero').removeClass();
  $('#tablero').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Estadisticas por casino');
  $('#opcEstadisticasPorCasino').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcEstadisticasPorCasino').addClass('opcionesSeleccionado');

    $('#dtpFechaDesde').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      showClear: true,
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 3
    });

    $('#dtpFechaHasta').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 3
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

function generarDonaMTM(resultadosMTM){
    Highcharts.chart('donaMTM', {
        chart: {
            backgroundColor: "#F5F5F5",
            type: 'pie',
            height: 200,
            options3d: {
                enabled: true,
                alpha: 45,
                beta: 0
            }
        },
        title: {
            text: ' '
        },
        legend: {
            itemStyle: {
                fontFamily: 'Roboto Slab',
                fontSize: '14px'
              },
              labelFormatter: function () {
                return this.name + " " + this.percentage.toFixed(2) + " %";
              }
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                innerSize: 150,
                colors: [colorBrutoMTM,colorMTM],
                allowPointSelect: true,
                cursor: 'pointer',
                depth: 35,
                dataLabels: {
                    enabled: true,
                    format: '{point.name}',
                    style: {
                      fontFamily: 'Roboto Slab',
                      fontSize: '14px'
                    }
                },
                showInLegend: true
            }
        },
        series: [{
            type: 'pie',
            name: 'MTM',
            data: [
                ['Bruto', resultadosMTM.bruto],
                ['Canon', resultadosMTM.canon]
            ]
        }]
    });
}

function generarDonaMesas(resultadosMesas){
  Highcharts.chart('donaMesas', {
                  chart: {
                      backgroundColor: "#F5F5F5",
                      type: 'pie',
                      options3d: {
                          enabled: true,
                          alpha: 45,
                          beta: 0
                      }
                  },
                  title: {
                      text: ' '
                  },
                  legend: {
                      itemStyle: {
                          fontFamily: 'Roboto Slab',
                          fontSize: '14px'
                        },
                        labelFormatter: function () {
                          return this.name + " " + this.percentage.toFixed(2) + " %";
                        }
                  },
                  tooltip: {
                      pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                  },
                  plotOptions: {
                      pie: {
                          innerSize: 150,
                          colors: [colorBrutoMesas,colorMesas],
                          allowPointSelect: true,
                          cursor: 'pointer',
                          depth: 35,
                          dataLabels: {
                              enabled: true,
                              format: '{point.name}',
                              style: {
                                fontFamily: 'Roboto Slab',
                                fontSize: '14px'
                              }
                          },
                          showInLegend: true
                      }
                  },
                  series: [{
                      type: 'pie',
                      name: 'MTM',
                      data: [
                          ['Bruto', resultadosMesas.bruto],
                          ['Canon', resultadosMesas.canon]
                      ]
                  }]
              });
}

function generarDonaBingo(resultadosBingo){
  Highcharts.chart('donaBingo', {
            chart: {
                backgroundColor: "#F5F5F5",
                type: 'pie',
                options3d: {
                    enabled: true,
                    alpha: 45,
                    beta: 0
                }
            },
            title: {
                text: ' '
            },
            legend: {
                itemStyle: {
                    fontFamily: 'Roboto Slab',
                    fontSize: '14px'
                  },
                  labelFormatter: function () {
                    return this.name + " " + this.percentage.toFixed(2) + " %";
                  }
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    innerSize: 150,
                    colors: [colorBrutoBingo,colorBingo],
                    allowPointSelect: true,
                    cursor: 'pointer',
                    depth: 35,
                    dataLabels: {
                        enabled: true,
                        format: '{point.name}',
                        style: {
                          fontFamily: 'Roboto Slab',
                          fontSize: '14px'
                        }
                    },
                    showInLegend: true
                }
            },
            series: [{
                type: 'pie',
                name: 'MTM',
                data: [
                    ['Bruto', resultadosBingo.bruto],
                    ['Canon', resultadosBingo.canon]
                ]
            }]
        });
}
//falta
function generarSeguimientoBrutoPorJuego(resultadosMTM, resultadosBingo , resultadosMesas){
  var fechas=[];
  var brutoMTM=[];
  var brutoMesas=[];
  var brutoBingo=[];

  for (var i = 0; i < resultadosMTM.length; i++) {
      var fecha=resultadosMTM[i].fecha.split("-");

      fechas.push( meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + '-' +fecha[0] );
      brutoMTM.push(resultadosMTM[i].bruto);
      brutoMesas.push(resultadosBingo[i].bruto);
      brutoBingo.push(resultadosMesas[i].bruto);
  }

  Highcharts.chart('recBrutoJuego', {
            chart: {
                backgroundColor: "#F5F5F5",
                type: 'area'
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
                labels: {
                    formatter: function () {
                        return this.value / 1000000 + "M";
                    }
                }
            },
            tooltip: {
                split: true,
                valuePrefix: '$ ',
            },
            plotOptions: {
          series: {
              fillOpacity: 0.1
        }
      },
            series: [{
                name: 'MTM',
                data: brutoMTM,
                color: colorMTM,
            },{
                name: 'BINGO',
                data: brutoMesas,
                color: colorBingo,
            },{
                name: 'MESAS',
                data: brutoBingo,
                color: colorMesas,
            }]
        });
}

function cargarTablasDonas(resultadosMTM, resultadosMesas, resultadosBingo){
  $('#cuerpoTablaMTM').empty();
  $('#cuerpoTablaMesas').empty();
  $('#cuerpoTablaBingo').empty();
  $('#pieTablaMTM').empty();
  $('#pieTablaMesas').empty();
  $('#pieTablaBingo').empty();

  for (var i = 0; i < resultadosMTM.meses.length; i++) {
    var fecha=resultadosMTM.meses[i].fecha.split("-");

    $('#cuerpoTablaMTM').append($('<tr>')
          .append($('<td>')
                .addClass('col-xs-4')
                .css('font-weight', 'bold')
                .text(meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' / ' + fecha[0])
          )
          .append($('<td>')
                .css('text-align','right')
                .addClass('col-xs-4')
                .text('$' +  addCommas( resultadosMTM.meses[i].bruto))
          )
          .append($('<td>')
                .css('text-align','right')
                .addClass('col-xs-4')
                .text('$' +  addCommas(resultadosMTM.meses[i].canon))
          )

    )

    $('#cuerpoTablaMesas').append($('<tr>')
          .append($('<td>')
                .addClass('col-xs-4')
                .css('font-weight', 'bold')
                .text(meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' / ' + fecha[0])
          )
          .append($('<td>')
                .css('text-align','right')
                .addClass('col-xs-4')
                .text('$' +  addCommas(resultadosMesas.meses[i].bruto))
          )
          .append($('<td>')
                .css('text-align','right')
                .addClass('col-xs-4')
                .text('$' +  addCommas(resultadosMesas.meses[i].canon))
          )
    )

    $('#cuerpoTablaBingo')
        .append($('<tr>')
            .append($('<td>')
                  .addClass('col-xs-4')
                  .css('font-weight', 'bold')
                  .text(meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' / ' + fecha[0])
            )
            .append($('<td>')
                  .css('text-align','right')
                  .addClass('col-xs-4')
                  .text('$' +  addCommas(resultadosBingo.meses[i].bruto))
            )
            .append($('<td>')
                  .css('text-align','right')
                  .addClass('col-xs-4')
                  .text('$' +  addCommas(resultadosBingo.meses[i].canon))
            )

    )

  }
  $('#pieTablaMTM').append($('<tr>')
        .css('font-weight', 'bold')
        .append($('<td>')
              .addClass('col-xs-4')
              .text('Total')
        )
        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-4')
              .text('$' +  addCommas(resultadosMTM.bruto))
        )
        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-4')
              .text('$' +  addCommas(resultadosMTM.canon))
        )

  )
  $('#pieTablaMesas').append($('<tr>')
        .css('font-weight', 'bold')
        .append($('<td>')
              .addClass('col-xs-4')
              .text('Total')
        )
        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-4')
              .text('$' +  addCommas(resultadosMesas.bruto))
        )
        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-4')
              .text('$' +  addCommas(resultadosMesas.canon))
        )

  )
  $('#pieTablaBingo').append($('<tr>')
        .css('font-weight', 'bold')
        .append($('<td>')
              .addClass('col-xs-4')
              .text('Total')
        )
        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-4')
              .text('$' +  addCommas(resultadosBingo.bruto))
        )
        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-4')
              .text('$' +  addCommas(resultadosBingo.canon))
        )

  )
}

function cargarTablaSeguimiento(resultadosMTM, resultadosMesas, resultadosBingo){
  $('#cuerpoTablaTotalJuegos').empty();
  $('#pieTablaTotalJuegos').empty();
  for (var i = 0; i < resultadosMTM.meses.length; i++) {

      var fecha=resultadosMTM.meses[i].fecha.split("-");

      $('#cuerpoTablaTotalJuegos').append($('<tr>')
          .append($('<td>')
                .addClass('col-xs-3')
                .css('font-weight', 'bold')
                .text(meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' / ' + fecha[0])
          )

          .append($('<td>')
                .css('text-align','right')
                .addClass('col-xs-2')
                .text('$' +  addCommas(resultadosMTM.meses[i].bruto))
          )

          .append($('<td>')
                .css('text-align','right')
                .addClass('col-xs-2')
                .text('$' +  addCommas(resultadosMesas.meses[i].bruto))
          )

          .append($('<td>')
                .css('text-align','right')
                .addClass('col-xs-2')
                .text('$' +  addCommas(resultadosBingo.meses[i].bruto))
          )
          .append($('<td>')
                .css('text-align','right')
                .addClass('col-xs-3')
                .text('$' +  addCommas(Math.round(((resultadosMTM.meses[i].bruto +resultadosMesas.meses[i].bruto + resultadosBingo.meses[i].bruto) )*100)/100))
          )
    )
  }

  $('#pieTablaTotalJuegos').append($('<tr>')
        .attr( "style" ,"font-weight: bold")
        .append($('<td>')
              .addClass('col-xs-3')
              .css('font-weight', 'bold')
              // .text(meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' -' + fecha[0])
              .text('TOTAL')
        )

        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-2')
              .text('$' +  addCommas(resultadosMTM.bruto))
        )

        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-2')
              .text('$' +  addCommas(resultadosMesas.bruto))
        )

        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-2')
              .text('$' +  addCommas(resultadosBingo.bruto))
        )
        .append($('<td>')
              .css('text-align','right')
              .addClass('col-xs-3')
              .text('$' + addCommas(Math.round(((resultadosMTM.bruto +resultadosMesas.bruto + resultadosBingo.bruto) )*100)/100))
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
      url: 'estadisticasPorCasino',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('.informacionEstadistica').show();
        generarDonaMTM(data.resultadosMTM);
        generarDonaMesas(data.resultadosMesas);
        generarDonaBingo(data.resultadosBingo);
        cargarTablasDonas(data.resultadosMTM ,data.resultadosMesas,data.resultadosBingo);
        generarSeguimientoBrutoPorJuego(data.resultadosMTM.meses ,data.resultadosBingo.meses , data.resultadosMesas.meses);
        cargarTablaSeguimiento(data.resultadosMTM ,data.resultadosBingo , data.resultadosMesas);

      },
      error: function (data) {
        $('.informacionEstadistica').hide();
        var chart = ($('#recBrutoJuego').highcharts());
        chart.destroy();
      },
    });
});
