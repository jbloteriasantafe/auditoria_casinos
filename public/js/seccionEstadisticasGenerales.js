meses=["ENE", "FEB" , "MAR", "ABR" , "MAY" , "JUN" , "JUL", "AGO" ,"SEP", "OCT" , "NOV" , "DIC"];
var colorSantaFe = "#ef3e42";
var colorRosario = "#f58426";
var colorMelincue = "#00b259";

var colorBrutoSantaFe = "#EE6C6E";
var colorBrutoRosario = "#F5A15D";
var colorBrutoMelincue = "#50D794";

$(document).ready(function(){

  $('#barraEstadisticas').attr('aria-expanded','true');
  $('#tablero').removeClass();
  $('#tablero').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Estadisticas Generales');
  $('#opcEstadisticasGenerales').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcEstadisticasGenerales').addClass('opcionesSeleccionado');

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

function generarTortaBruto(bSantaFe,bMelincue,bRosario){
  $(function () {
      Highcharts.chart('tortaBruto', {
        chart: {
            backgroundColor: "#f5f5f5",
            type: 'pie',
            options3d: {
                enabled: true,
                alpha: 45,
                beta: 0
            },
        },
        title: { text: ' '},
        legend: { labelFormatter: function () {return this.name + " " + this.percentage.toFixed(2) + " %";}},
        tooltip: { pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'},
        plotOptions: {
            pie: {
                colors: [colorSantaFe,colorMelincue,colorRosario],
                allowPointSelect: true,
                cursor: 'pointer',
                depth: 35,
                dataLabels: {
                    enabled: true,
                    format: '{point.name}'
                },
                showInLegend: true
            }
        },
        series: [{
            type: 'pie',
            name: 'Bruto',

            data: [
                ['Santa Fe', bSantaFe],
                ['Melincué', bMelincue],
                ['Rosario',bRosario]
            ]
        }]
    });
  });
}

function generarTortaCanon(cSantaFe,cMelincue,cRosario){
  $(function () {
      Highcharts.chart('tortaCanon', {
        chart: {
            backgroundColor: "#F5F5F5",
            type: 'pie',
            options3d: {
                enabled: true,
                alpha: 45,
                beta: 0
            }
        },
        title: { text: ' '},
        legend: { labelFormatter: function () {return this.name + " " + this.percentage.toFixed(2) + " %";}},
        tooltip: { pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'},
        plotOptions: {
            pie: {
                colors: [colorSantaFe,colorMelincue,colorRosario],
                allowPointSelect: true,
                cursor: 'pointer',
                depth: 35,
                dataLabels: {
                    enabled: true,
                    format: '{point.name}'
                },
                showInLegend: true
            }
        },
        series: [{
            type: 'pie',
            name: 'Bruto',
            data: [
                ['Santa Fe', cSantaFe],
                ['Melincué', cMelincue],
                ['Rosario', cRosario]
            ]
        }]
    });
  });
}

function generarSeguimientoBruto(resultadosSF, resultadosMEL , resultadosRO){
  var fechas=[];
  var brutosSantaFe=[];
  var brutosMelincue=[];
  var brutosRosario=[];

  for (var i = 0; i < resultadosSF.length; i++) {
      var fecha=resultadosSF[i].fecha.split("-");

      fechas.push( meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + '/' +fecha[0] );
      brutosSantaFe.push(Math.round((resultadosSF[i].bruto)*100)/100);
      brutosMelincue.push(Math.round((resultadosMEL[i].bruto)*100)/100 );
      brutosRosario.push(Math.round((resultadosRO[i].bruto)*100)/100 );
  }

  Highcharts.chart('seguimientoBruto', {
      chart: {
        backgroundColor: "#f5f5f5",
          type: 'area',
          colors: [colorSantaFe,colorMelincue,colorRosario],
          style: {
            fontFamily: 'Roboto-Regular',
          }
      },
      title: {
          text: ''
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
          pointFormat: '{series.name}: <b>$ {point.y}</b>'
      },
      plotOptions: {
          series: {
              fillOpacity: 0.1
        }
      },
      series: [
        {
           name: 'CRO',
           data: brutosRosario,
           color: colorRosario,
       },
       {
          name: 'CSF',
          data: brutosSantaFe,
          color: colorSantaFe,
      }, {
          name: 'CME',
          data: brutosMelincue,
          color: colorMelincue,
      }]
  });
}

function generarBarrasSantaFe(resultadosSantaFe){

        var fechas=[];
        var brutosSantaFe=[];
        var canonsSantaFe=[];

        for (var i = 0; i < resultadosSantaFe.length; i++){
            var fecha=resultadosSantaFe[i].fecha.split("-");

            fechas.push((meses[(fecha[1].replace(/^0+/, '') ) - 1 ]) + '/' +fecha[0]);
            brutosSantaFe.push(Math.round((resultadosSantaFe[i].bruto)*100)/100 );
            canonsSantaFe.push(Math.round((resultadosSantaFe[i].canon)*100)/100 );

        }

          Highcharts.chart('recaudacionBarraSF', {
              chart: {
                backgroundColor: "#f5f5f5",
                  type: 'column',
                  options3d: {
                      enabled: true,
                      alpha: 5,
                      beta: 0,
                      viewDistance: 25,
                      depth: 40
                  }
              },

        title: {
            text: ''
        },

        xAxis: {

            categories: fechas
        },

        yAxis: {
            allowDecimals: false,
            min: 0,
            title: {
                text: ''
            }
        },

        tooltip: {
            headerFormat: '<b>{point.key}</b><br>',
            pointFormat: '<span style="color:{series.color}">\u25CF</span> {series.name}: $ {point.y}'
        },

        plotOptions: {
            column: {
                stacking: 'normal',
                depth: 40
            }
        },

        series: [{
            name: 'Canon',
            data: canonsSantaFe,
            color: colorSantaFe,
        }, {
            name: 'Bruto',
            //Modificar los valores de Bruto - Canon.
            data: brutosSantaFe,
            color: colorBrutoSantaFe,
        }]
    });

}

function generarBarrasMelincue(resultadosMelincue){

        var fechas=[];
        var brutosMelincue=[];
        var canonsMelincue=[];

        for (var i = 0; i < resultadosMelincue.length; i++){
            var fecha=resultadosMelincue[i].fecha.split("-");

            fechas.push((meses[(fecha[1].replace(/^0+/, '') ) - 1 ]) + '/' +fecha[0]);
            brutosMelincue.push(Math.round((resultadosMelincue[i].bruto)*100)/100 );
            canonsMelincue.push(Math.round((resultadosMelincue[i].canon)*100)/100 );

        }

          Highcharts.chart('recaudacionBarraM', {
              chart: {
                backgroundColor: "#f5f5f5",
                  type: 'column',
                  options3d: {
                      enabled: true,
                      alpha: 5,
                      beta: 0,
                      viewDistance: 25,
                      depth: 40
                  }
              },

        title: {
            text: ''
        },

        xAxis: {

            categories: fechas
        },

        yAxis: {
            allowDecimals: false,
            min: 0,
            title: {
                text: ''
            }
        },

        tooltip: {
            headerFormat: '<b>{point.key}</b><br>',
            pointFormat: '<span style="color:{series.color}">\u25CF</span> {series.name}: $ {point.y}'
        },

        plotOptions: {
            column: {
                stacking: 'normal',
                depth: 40
            }
        },

        series: [{
            name: 'Canon',
            data: canonsMelincue,
            color: colorMelincue,
        }, {
            name: 'Bruto',
            //Modificar los valores de Bruto - Canon.
            data: brutosMelincue,
            color: colorBrutoMelincue,
        }]
    });

}

function generarBarrasRosario(resultadosRosario){

        var fechas=[];
        var brutosRosario=[];
        var canonsRosario=[];

        for (var i = 0; i < resultadosRosario.length; i++){
            var fecha=resultadosRosario[i].fecha.split("-");

            fechas.push((meses[(fecha[1].replace(/^0+/, '') ) - 1 ]) + '/' +fecha[0]);
            brutosRosario.push(Math.round((resultadosRosario[i].bruto)*100)/100 );
            canonsRosario.push(Math.round((resultadosRosario[i].canon)*100)/100 );

        }

          Highcharts.chart('recaudacionBarraR', {
              chart: {
                  backgroundColor: "#f5f5f5",
                  type: 'column',
                  options3d: {
                      enabled: true,
                      alpha: 5,
                      beta: 0,
                      viewDistance: 25,
                      depth: 40
                  }
              },

        title: {
            text: ''
        },

        xAxis: {

            categories: fechas
        },

        yAxis: {
            allowDecimals: false,
            min: 0,
            title: {
                text: ''
            }
        },

        tooltip: {
            headerFormat: '<b>{point.key}</b><br>',
            pointFormat: '<span style="color:{series.color}">\u25CF</span> {series.name}: $ {point.y}'
        },

        plotOptions: {
            column: {
                stacking: 'normal',
                depth: 40
            }
        },

        series: [{
            name: 'Canon',
            data: canonsRosario,
            color: colorRosario,
        }, {
            name: 'Bruto',
            //Modificar los valores de Bruto - Canon.
            data: brutosRosario,
            color: colorBrutoRosario,
        }]
    });

}

function cargarTablasTorta(resultadosSantaFe, resultadosMelincue, resultadosRosario_ars){
  $('#cuerpoTablaBruto').empty();
  $('#cuerpoTablaCanon').empty();
  $('#pieTablaBruto').empty();
  $('#pieTablaCanon').empty();


  for (var i = 0; i < resultadosSantaFe.meses.length; i++) {
    var fecha=resultadosSantaFe.meses[i].fecha.split("-");

    $('#cuerpoTablaBruto').append($('<tr>')
          .append($('<td>')
                .addClass('col-xs-3')
                .css('font-weight', 'bold')
                .text(meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' / ' + fecha[0])
          )
          .append($('<td>')
                .addClass('col-xs-3')
                .text(resultadosSantaFe.meses[i].bruto)
          )
          .append($('<td>')
                .addClass('col-xs-3')
                .text(resultadosMelincue.meses[i].bruto)
          )
          .append($('<td>')
                .addClass('col-xs-3')
                .text(resultadosRosario_ars.meses[i].bruto)
          )
    )



    $('#cuerpoTablaCanon').append($('<tr>')
          .append($('<td>')
                .addClass('col-xs-3')
                .css('font-weight', 'bold')
                .text(meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' / ' + fecha[0])
          )
          .append($('<td>')
                .addClass('col-xs-3')
                .text(resultadosSantaFe.meses[i].canon)
          )
          .append($('<td>')
                .addClass('col-xs-3')
                .text(resultadosMelincue.meses[i].canon)
          )
          .append($('<td>')
                .addClass('col-xs-3')
                .text(resultadosRosario_ars.meses[i].canon)
          )
    )

  }
  $('#pieTablaBruto').append($('<tr>')
        .css('font-weight', 'bold')
        .append($('<td>')
              .addClass('col-xs-3')
              .text('Total')
        )
        .append($('<td>')
              .addClass('col-xs-3')
              .text(resultadosSantaFe.bruto)
        )
        .append($('<td>')
              .addClass('col-xs-3')
              .text(resultadosMelincue.bruto)
        )
        .append($('<td>')
              .addClass('col-xs-3')
              .text(resultadosRosario_ars.bruto)
        )
  )
  $('#pieTablaCanon').append($('<tr>')
        .css('font-weight', 'bold')
        .append($('<td>')
              .addClass('col-xs-3')
              .text('Total')
        )
        .append($('<td>')
              .addClass('col-xs-3')
              .text(resultadosSantaFe.canon)
        )
        .append($('<td>')
              .addClass('col-xs-3')
              .text(resultadosMelincue.canon)
        )
        .append($('<td>')
              .addClass('col-xs-3')
              .text(resultadosRosario_ars.canon)
        )
  )
}

$('#btn-generarGraficos').click(function(){


  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var formData = {
    fecha_desde: $('#fecha_desde').val(),
    fecha_hasta: $('#fecha_hasta').val(),
  }

  $.ajax({
      type: "POST",
      url: 'estadisticasGenerales',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);

        $('.informacionEstadistica').show();
        generarTortaBruto(data.resultadosSantaFe.bruto ,data.resultadosMelincue.bruto ,data.resultadosRosario_ars.bruto);
        generarTortaCanon(data.resultadosSantaFe.canon ,data.resultadosMelincue.canon ,data.resultadosRosario_ars.canon);
        cargarTablasTorta(data.resultadosSantaFe ,data.resultadosMelincue ,data.resultadosRosario_ars);
        generarBarrasSantaFe(data.resultadosSantaFe.meses);
        generarBarrasMelincue(data.resultadosMelincue.meses);
        generarBarrasRosario(data.resultadosRosario_ars.meses);
        generarSeguimientoBruto(data.resultadosSantaFe.meses ,data.resultadosMelincue.meses ,data.resultadosRosario_ars.meses);


      },
      error: function (data) {
        $('.informacionEstadistica').hide();
        var chart = ($('#seguimientoBruto').highcharts());
        chart.destroy();


      },
    });
});
