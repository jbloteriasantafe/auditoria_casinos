$(document).ajaxError(function(event, jqxhr, settings, thrownError){
  if(jqxhr.status == 351){
    var responseText = jQuery.parseJSON(jqxhr.responseText);
    alert(responseText.mensaje);
    window.location.href=responseText.url;
  }
});
/*
funcion que agrega un punto cada mil y coma a los centavos
*/
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

function tipoUndefinedCanon(objeto){
  if(typeof objeto === 'undefined'){
    return '-';
  }
  else{
    return objeto.canon;
  }
}
/*
seccion interanual
*/
$('#btn-grafico-3').click(function (e) {
  var formData = {
      fecha_inicio: $('#fecha_inicio3').val(),
      fecha_fin: $('#fecha_fin3').val(),
      id_casino: $('input[name=opcion3]:checked').val(),
  };
var meses=["Ene", "Feb" , "Mar", "Abr" , "May" , "Jun" , "Jul", "Ago" ,"Sep", "Oct" , "Nov" , "Dic"];

$.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  }) ;

console.log(formData);
$.ajax({
      type: "POST",
      url: 'interanuales',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('#grafico3').show();

        $(function () {
          Highcharts.chart('interanualCasinoCanon', {
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
                data: data.porcentajesCanon,
                threshold: 0,
                negativeColor: 'red',
                color: 'green',
                tooltip: {
                    valueDecimals: 2
                }
              }]
          });
        });

        $(function () {
          Highcharts.chart('interanualCasinoBruto', {
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
                data: data.porcentajesBruto,
                threshold: 0,
                negativeColor: 'red',
                color: 'green',
                tooltip: {
                    valueDecimals: 2
                }
              }]
          });
        });

      /*
      carga tabla x y
      */
      var meses=["Enero", "Febrero" , "Marzo", "Abril" , "Mayo" , "Junio" , "Julio", "Agosto" ,"Septiembre", "Octubre" , "Noviembre" , "Diciembre"];
      $('#cuerpoTablaXY').empty();
      $('#pieTablaXY').empty();
      $('#cabeceraTablaXY').empty();
      var resultados=' ';

      for (var i = 0; i < 12 ; i++) {
        if(typeof data.sumaBrutoX[i] === 'undefined'){data.sumaBrutoX[i] ='-';}
        if(typeof data.sumaCanonX[i] === 'undefined'){data.sumaCanonX[i] = '-';}
        if(typeof  data.sumaBrutoY[i] === 'undefined'){ data.sumaBrutoY[i]= '-';}
        if(typeof  data.sumaCanonY[i] === 'undefined'){data.sumaCanonY[i] = '-';}


          resultados += '<tr > <td class="col-xs-2 col-xs-offset-1" style="font-weight: bold">' + meses[i]  + ' </td> <td class="col-xs-2">$' + addCommas(data.sumaBrutoX[i]) + '</td><td class="col-xs-2">$' +addCommas( data.sumaCanonX[i] )+ '</td><td class="col-xs-2">$' +addCommas( data.sumaBrutoY[i]) +  '</td><td class="col-xs-2">$' + addCommas(data.sumaCanonY[i] ) + '</td></tr>';
      };

      $('#cuerpoTablaXY').append(resultados);
      var pie = '<tr> <td class="col-xs-2 col-xs-offset-1" style="font-weight: bold">' + 'Total' + '</td><td class="col-xs-2" style="font-weight: bold"> $'+ addCommas(data.total_casino_X[0].total_bruto ) + ' </td> <td class="col-xs-2" style="font-weight: bold"> $' + addCommas(data.total_casino_X[0].total_canon ) + ' </td> <td class="col-xs-2" style="font-weight: bold"> $' +addCommas( data.total_casino_Y[0].total_bruto  )+ ' </td> <td class="col-xs-2" style="font-weight: bold"> $' + addCommas(data.total_casino_Y[0].total_canon) +  '</td></tr>';
      var cabecera= '<tr class="row"> <th class="col-xs-2 col-xs-offset-1">'+ $('input[name=opcion3]:checked').data('nombre') +'</th> <th class="col-xs-4">AÑO '+   $('#fecha_inicio3').val() + '</th> <th class="col-xs-4">AÑO ' + $('#fecha_fin3').val() + '</th></tr>  <tr class="row"> <th class="col-xs-2 col-xs-offset-1" style="font-weight: bold">MES</th> <th class="col-xs-2" style="background: #FFAB91; color: #FFF">BRUTO</th>  <th class="col-xs-2" style="background: #B39DDB; color: #FFF">CANON</th>  <th class="col-xs-2" style="background: #A5D6A7; color: #FFF">BRUTO</th><th class="col-xs-2" style="background: #A5D6A7; color: #FFF">CANON</th></tr>'
      $('#pieTablaXY').append(pie);
      $('#cabeceraTablaXY').append(cabecera);

      $(function () {
      Highcharts.chart('interanualJuego', {
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
             data: data.porcentajesMTM,
             threshold: 0,
             color: '#FF7043',
             negativeColor: '#FFCCBC',
             tooltip: {
                 valueDecimals: 2
             }
           },{
             name: 'MESAS',
             data: data.porcentajeMesas,
             visible: false,
             threshold: 0,
             color: '#7E57C2',
             negativeColor: '#D1C4E9',
             tooltip: {
                 valueDecimals: 2
             }
           },{
             name: 'BINGO',
             data: data.porcentajeBingos,
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
      });

             $('#cuerpoTablaInteranualPorJuegoX').empty();
             $('#cuerpoTablaInteranualPorJuegoY').empty();
             $('#cabeceraTablaX').empty();
             $('#cabeceraTablaY').empty();
             $('#pieTablaX').empty();
             $('#pieTablaY').empty();
            var resultadoX=' ';
            var resultadoY=' ';
            var cabeceraX=' ';
            var cabeceraY=' ';
            var meses=["Enero", "Febrero" , "Marzo", "Abril" , "Mayo" , "Junio" , "Julio", "Agosto" ,"Septiembre", "Octubre" , "Noviembre" , "Diciembre"];

            for (var i = 0; i < 12 ; i++) {
            /*  if(typeof data.resultadosMTMX[i] === 'undefined'){ data.resultadosMTMX[i]='-';}
              if(typeof data.resultadosMesasX[i] === 'undefined'){ data.resultadosMesasX[i].canon= '-';}
              if(typeof data.resultadosBingosX[i]  === 'undefined'){ data.resultadosBingosX[i].canon= '-';}
              if(typeof data.resultadosMTMY[i] === 'undefined'){ data.resultadosMTMY[i]='-';}
              if(typeof data.resultadosMesasY[i] === 'undefined'){ data.resultadosMesasY[i]= '-';}
              if(typeof data.resultadosBingosY[i]  === 'undefined'){ data.resultadosBingosY[i] = '-';} */



                var fechaX=data.resultadosMTMX[0].anioMes.split("-");
                var fechaY=data.resultadosMTMY[0].anioMes.split("-");

                resultadoX += '<tr > <td class="col-xs-3 ">' +meses[i]+ '<td class="col-xs-3 ">$' +  addCommas(tipoUndefinedCanon(data.resultadosMTMX[i])) + ' </td> <td class="col-xs-3">$' +  addCommas(tipoUndefinedCanon(data.resultadosMesasX[i]))+ '</td><td class="col-xs-3">$' + addCommas(tipoUndefinedCanon(data.resultadosBingosX[i]))  + '</td></tr>';
                resultadoY += '<tr > <td class="col-xs-3 ">' +meses[i]+ '<td class="col-xs-3 ">$' +  addCommas(tipoUndefinedCanon(data.resultadosMTMY[i])) + ' </td> <td class="col-xs-3">$' +  addCommas(tipoUndefinedCanon(data.resultadosMesasY[i]))+ '</td><td class="col-xs-3">$' + addCommas(tipoUndefinedCanon(data.resultadosBingosY[i])) + '</td></tr>';
                //resultados += '<tr > <td class="col-xs-2 ">' +   data.resultadosMTMX[i].canon + ' </td> <td class="col-xs-2">$' +  data.resultadosMesasX[i].canon + '</td><td class="col-xs-2">$' + data.resultadosBingosX[i].canon  + '</td><td class="col-xs-2">$'   +   data.resultadosMTMY[i].canon + ' </td> <td class="col-xs-2">$' +  data.resultadosMesasY[i].canon + '</td><td class="col-xs-2">$' + data.resultadosBingosY[i].canon + '</td></tr>';
            };
            var pieX= '<tr> <td class="col-xs-3" style="font-weight: bold">' + 'Total' + '</td><td class="col-xs-3" style="font-weight: bold"> $'+ addCommas(data.resultado_anual_MTMX[0].total_canon ) + ' </td> <td class="col-xs-3" style="font-weight: bold"> $' + addCommas(data.resultado_anual_MesasX[0].total_canon ) + ' </td> <td class="col-xs-3" style="font-weight: bold"> $' +addCommas( data.resultado_anual_BingosX[0].total_bruto  )+ ' </td></tr>';
            var pieY= '<tr> <td class="col-xs-3" style="font-weight: bold">' + 'Total' + '</td><td class="col-xs-3" style="font-weight: bold"> $'+ addCommas(data.resultado_anual_MTMY[0].total_canon ) + ' </td> <td class="col-xs-3" style="font-weight: bold"> $' + addCommas(data.resultado_anual_MesasY[0].total_canon ) + ' </td> <td class="col-xs-3" style="font-weight: bold"> $' +addCommas( data.resultado_anual_BingosY[0].total_bruto  )+ ' </td></tr>';
            cabeceraX +=  '<tr > <th class="col-xs-12"> AÑO ' + fechaX[0] + '</th></tr>' + '<tr> <th class="col-xs-3" style="font-weight: bold">MES</th> <th class="col-xs-3" style="background: #FFAB91; color: #FFF">MTM</th> <th class="col-xs-3" style="background: #B39DDB; color: #FFF">MESAS</th> <th class="col-xs-3" style="background: #A5D6A7; color: #FFF">BINGO</th> </tr>';
            cabeceraY +=  '<tr > <th class="col-xs-12"> AÑO ' + fechaY[0] + '</th></tr>' + '<tr> <th class="col-xs-3" style="font-weight: bold">MES</th> <th class="col-xs-3" style="background: #FFAB91; color: #FFF">MTM</th> <th class="col-xs-3" style="background: #B39DDB; color: #FFF">MESAS</th> <th class="col-xs-3" style="background: #A5D6A7; color: #FFF">BINGO</th> </tr>';

            $('#cuerpoTablaInteranualPorJuegoX').append(resultadoX);
            $('#cuerpoTablaInteranualPorJuegoY').append(resultadoY);
            $('#cabeceraTablaX').append(cabeceraX);
            $('#cabeceraTablaY').append(cabeceraY);

            $('#pieTablaX').append(pieX);
            $('#pieTablaY').append(pieY);

      },
      error: function (data) {
        var response = JSON.parse(data.responseText);
        $('#alertaFechaInicio3').hide();
        $('#alertaFechaFin3').hide();
        $('#alertaCasino2').hide();


        if(typeof response.fecha_inicio !== 'undefined'){
          $('#alertaFechaInicio3 span').text(response.fecha_inicio[0]);
          $('#alertaFechaInicio3').show();

        }

        if(typeof response.fecha_fin !== 'undefined'){
          $('#alertaFechaFin3 span').text(response.fecha_fin[0]);
          $('#alertaFechaFin3').show();

        }
        if(typeof response.id_casino !== 'undefined'){
          $('#alertaCasino2 span').text(response.id_casino[0]);
          $('#alertaCasino2').show();

        }

          console.log(data);
      }
    });
});

$ ('#btn-grafico-2').click(function (e) {
  var formData = {
      fecha_inicio: $('#fecha_inicio2').val(),
      fecha_fin: $('#fecha_fin2').val(),
      id_casino: $('input[name=opcion2]:checked').val(),
  }
  var meses=["Ene", "Feb" , "Ma", "Abr" , "May" , "Jun" , "Jul", "Ago" ,"Sep", "Oct" , "Nov" , "Dic"]

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })

  $.ajax({
      type: "POST",
      url: 'estadisticasPorCasino',
      data: formData,
      dataType: 'json',
      success: function (data) {
          console.log(data);
          $('#alertaFechaInicio2').hide();
          $('#alertaFechaFin2').hide();
          $('#alertaCasino').hide();
          $('#grafico2').show();

          $(function () {
              Highcharts.chart('donaMTM', {
                  chart: {
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
                          colors: ["#FFAB91","#FF8A65"],
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
                          ['Bruto', data.MTMbruto],
                          ['Canon', data.MTMcanon]
                      ]
                  }]
              });
          });
          /*
          carga de tabla de MTM
          */
          $('#cuerpoTablaMTM').empty();
          var resultados=' ';
          for (var i = 0; i < data.resultadoMTM.length; i++) {
            var fecha=data.resultadoMTM[i].anioMes.split("-");
              resultados += '<tr > <td class="col-xs-4" style="font-weight: bold">' + meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' -' + fecha[0]  + ' </td> <td class="col-xs-4">$' + addCommas(Math.round((data.resultadoMTM[i].bruto)*100)/100)  + '</td><td class="col-xs-4">$' + addCommas(Math.round((data.resultadoMTM[i].canon)*100)/100)  + '</td></tr>';
          };

          $('#cuerpoTablaMTM').append(resultados);
          var pie = '<tr> <td class="col-xs-4" style="font-weight: bold">' + 'Total' + '</td><td class="col-xs-4" style="font-weight: bold"> $'+ addCommas(Math.round((data.MTMbruto)*100)/100)   + ' </td> <td class="col-xs-4" style="font-weight: bold"> $' + addCommas(Math.round((data.MTMcanon)*100)/100)  + '</td></tr>';
          $('#pieTablaMTM').empty();
          $('#pieTablaMTM').append(pie);

          /*grafico dona de MESAS */
          $(function () {
              Highcharts.chart('donaMESAS', {
                  chart: {
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
                          colors: ["#B39DDB","#9575CD"],
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
                          ['Bruto', data.Mesasbruto],
                          ['Canon', data.Mesascanon]
                      ]
                  }]
              });
          });

          /*
          carga tabla de Mesas
          */

          $('#cuerpoTablaMesas').empty();
          var resultados=' ';
          for (var i = 0; i < data.resultadoMesas.length; i++) {
              var fecha=data.resultadoMesas[i].anioMes.split("-");
              resultados += '<tr > <td class="col-xs-4" style="font-weight: bold">' +   meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' -' + fecha[0]  + ' </td> <td class="col-xs-4">$' +  addCommas(Math.round((data.resultadoMesas[i].bruto )*100)/100) + '</td><td class="col-xs-4">$' +  addCommas(Math.round((data.resultadoMesas[i].canon)*100)/100) + '</td></tr>';
          };

          $('#cuerpoTablaMesas').append(resultados);
          var pie = '<tr> <td class="col-xs-4" style="font-weight: bold">' + 'Total' + '</td><td class="col-xs-4" style="font-weight: bold"> $'+  addCommas(Math.round((data.Mesasbruto )*100)/100)  + ' </td> <td class="col-xs-4" style="font-weight: bold"> $' + addCommas(Math.round((data.Mesascanon)*100)/100)   + '</td></tr>';
          $('#pieTablaMesas').empty();
          $('#pieTablaMesas').append(pie);

          /*
          carga grafico donas Bingos
          */

          $(function () {
              Highcharts.chart('donaBINGO', {
                  chart: {
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
                          colors: ["#A5D6A7","#81C784"],
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
                          ['Bruto', data.Bingosbruto],
                          ['Canon', data.Bingoscanon]
                      ]
                  }]
              });
          });
          $('#cuerpoTablaBingos').empty();
          var resultados=' ';
          for (var i = 0; i < data.resultadoBingos.length; i++) {

            var fecha=data.resultadoBingos[i].anioMes.split("-");

              resultados += '<tr > <td class="col-xs-4" style="font-weight: bold">' + meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' -' + fecha[0] + ' </td> <td class="col-xs-4">$' +addCommas(Math.round((data.resultadoBingos[i].bruto)*100)/100)  + '</td><td class="col-xs-4">$' + addCommas(Math.round((data.resultadoBingos[i].canon )*100)/100) + '</td></tr>';
          };

          $('#cuerpoTablaBingos').append(resultados);
          var pie = '<tr> <td class="col-xs-4" style="font-weight: bold">' + 'Total' + '</td><td class="col-xs-4" style="font-weight: bold"> $'+ addCommas(Math.round((data.Bingosbruto  )*100)/100) + ' </td> <td class="col-xs-4" style="font-weight: bold"> $' + addCommas(Math.round((data.Bingoscanon )*100)/100) + '</td></tr>';
          $('#pieTablaBingos').empty();
          $('#pieTablaBingos').append(pie);

          /*grafico recaudacion Bruto*/
          $(function () {

              var fechas=[];
              var brutosMesas=[];
              var brutosBingo=[];
              var brutosMTM=[];


              for (var i = 0; i < data.resultadoMesas.length; i++) {
                var fecha=data.resultadoMesas[i].anioMes.split("-");

                  fechas.push(meses[(fecha[1].replace(/^0+/, '') ) - 1] + '-' + fecha[0]);
                  brutosMesas.push(data.resultadoMesas[i].bruto);
                  brutosMTM.push(data.resultadoMTM[i].bruto);
                  brutosBingo.push(data.resultadoBingos[i].bruto);
              }

          Highcharts.chart('recBrutoJuego', {
              chart: {
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
                      text: ' '
                  },
                  labels: {
                      formatter: function () {
                          return this.value / 1000;
                      }
                  }
              },
              tooltip: {
                  split: true,
                  valueSuffix: ' millions'
              },
              plotOptions: {
            series: {
                fillOpacity: 0.1
          }
        },
              series: [{
                  name: 'MTM',
                  data: brutosMTM,
                  color: '#FFAB91'
              },{
                  name: 'BINGO',
                  data: brutosBingo,
                  color: '#A5D6A7'
              },{
                  name: 'MESAS',
                  data: brutosMesas,
                  color: '#B39DDB'
              }]
          });
          });
          /*
          carga tabla total
          */
          $('#cuerpoTablaTotalJuegos').empty();
          var resultados=' ';
          for (var i = 0; i < data.resultadoMesas.length; i++) {
              var fecha=data.resultadoMesas[i].anioMes.split("-");
              resultados += '<tr > <td class="col-xs-2 col-xs-offset-1" style="font-weight: bold">' + meses[(fecha[1].replace(/^0+/, '') ) - 1] + '-' + fecha[0] + ' </td> <td class="col-xs-2">$' +  addCommas(Math.round((data.resultadoMTM[i].bruto )*100)/100) + '</td><td class="col-xs-2">$' +  addCommas(Math.round((data.resultadoMesas[i].bruto)*100)/100) + '</td><td class="col-xs-2">$' + addCommas(Math.round((data.resultadoBingos[i].bruto )*100)/100) + '</td><td class="col-xs-3">$' +  addCommas(Math.round(((data.resultadoMTM[i].bruto +data.resultadoMesas[i].bruto + data.resultadoBingos[i].bruto) )*100)/100) + '</td></tr>';
          };

          $('#cuerpoTablaTotalJuegos').append(resultados);
          var pie = '<tr> <td class="col-xs-2 col-xs-offset-1" style="font-weight: bold">' + 'Total' + '</td><td class="col-xs-2" style="font-weight: bold"> $'+  addCommas(Math.round((data.MTMbruto  )*100)/100) + ' </td> <td class="col-xs-2" style="font-weight: bold"> $' +  addCommas(Math.round((data.Mesasbruto )*100)/100) + '</td><td class="col-xs-2" style="font-weight: bold">$' +  addCommas(Math.round((data.Bingosbruto )*100)/100) +  '</td><td class="col-xs-3"  style="font-weight: bold">$' +  addCommas(Math.round(((data.Bingosbruto+data.Mesasbruto+data.MTMbruto)  )*100)/100) +  '</td></tr>';
          $('#pieTablaTotalJuegos').empty();
          $('#pieTablaTotalJuegos').append(pie);
              },

            error: function (data) {

              var response = JSON.parse(data.responseText);
              $('#alertaFechaInicio2').hide();
              $('#alertaFechaFin2').hide();
              $('#alertaCasino').hide();

              if(typeof response.fecha_inicio !== 'undefined'){
                $('#alertaFechaInicio2 span').text(response.fecha_inicio[0]);
                $('#alertaFechaInicio2').show();

              }

              if(typeof response.fecha_fin !== 'undefined'){
                $('#alertaFechaFin2 span').text(response.fecha_fin[0]);
                $('#alertaFechaFin2').show();

              }
              if(typeof response.id_casino !== 'undefined'){
                $('#alertaCasino span').text(response.id_casino[0]);
                $('#alertaCasino').show();

              }

                console.log(data);
            }
  });

  });

  $('#fechaI1').click(function(e){
    $('#alertaFechaInicio').hide();
  })

  $('#fechaF1').click(function(e){
    $('#alertaFechaFin').hide();
  })
  $('#fechaI2').click(function(e){
    $('#alertaFechaInicio2').hide();
  })
  $('#fechaF2').click(function(e){
    $('#alertaFechaFin2').hide();
  })

  $('#opciones2').click(function(e){
    $('#alertaCasino').hide();
  })
  $('#opciones3').click(function(e){
    $('#alertaCasino2').hide();
  })
  $('#fechaF3').click(function(e){
    $('#alertaFechaFin3').hide();
  })

  $('#fechaI3').click(function(e){
    $('#alertaFechaInicio3').hide();
  })

$ ('#btn-grafico-1').click(function (e) {
  var formData = {
      fecha_inicio: $('#fecha_inicio1').val(),
      fecha_fin: $('#fecha_fin1').val(),
  }
  var meses=["ENE", "FEB" , "MAR", "ABR" , "MAY" , "JUN" , "JUL", "AGO" ,"SEP", "OCT" , "NOV" , "DIC"]
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })

  $.ajax({
      type: "POST",
      url: 'estadisticasGenerales',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('#alertaFechaInicio').hide();
        $('#alertaFechaFin').hide();
        $('#alertaResultados').hide();
        $('#grafico1').show();
        /*
          generar grafico con los datos del bruto
        */
                  $(function () {
                    Highcharts.chart('torta', {
                        chart: {
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
                        labelFormatter: function () {
                              return this.name + " " + this.percentage.toFixed(2) + " %";
                          }
                      },
                      tooltip: {
                          pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                      },
                      plotOptions: {
                          pie: {
                              colors: ["#FF6384","#36A2EB","#FFCE56"],
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
                              ['Santa Fe', data.brutoSantaFe],
                              ['Melincué', data.brutoMelincue],
                              ['Rosario', data.brutoRosario]
                          ]
                      }]
                  });
                });
        /*
        carga de la tabla de brutos;
        */
        $('#cuerpoTablaBruto').empty();
        var resultados=' ';

        for (var i = 0; i < data.resultado_mes_ME.length; i++) {
            //fecha=new Date(data.resultadosSantaFe[i].anioMes);
            var fecha=data.resultado_mes_ME[i].anioMes.split("-");

            resultados += '<tr > <td class="col-xs-3" style="font-weight: bold">' + meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' -' + fecha[0]  + '</td><td class="col-xs-3"> $'+ addCommas(Math.round((data.resultado_mes_SF[i].total_bruto)*100)/100) + ' </td> <td class="col-xs-3">$' + addCommas( Math.round((data.resultado_mes_ME[i].total_bruto )*100)/100 ) + '</td><td class="col-xs-3">$' +addCommas( Math.round((data.resultado_mes_RO[i].total_bruto )*100)/100 ) + '</td></tr>';

        };

        $('#cuerpoTablaBruto').append(resultados);
        var pie = '<tr> <td class="col-xs-3" style="font-weight: bold">' + 'Total' + '</td><td class="col-xs-3" style="font-weight: bold"> $'+addCommas( data.brutoSantaFe)  + ' </td> <td class="col-xs-3" style="font-weight: bold"> $' + addCommas(data.brutoMelincue) + '</td><td class="col-xs-3" style="font-weight: bold"> $' +addCommas( data.brutoRosario )+ '</td></tr>';
        $('#pieTablaBruto').empty();
        $('#pieTablaBruto').append(pie);

        /*
        generar el grafico de los canon
        */
        $(function () {
                    Highcharts.chart('tortaCanon', {
                        chart: {
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
                        labelFormatter: function () {
                              return this.name + " " + this.percentage.toFixed(2) + " %";
                          }
                      },
                      tooltip: {
                          pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                      },
                      plotOptions: {
                          pie: {
                              colors: ["#FF6384","#36A2EB","#FFCE56"],
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
                          name: 'Canon',
                          data: [
                              ['Santa Fe', data.canonSantaFe],
                              ['Melincué', data.canonMelincue ],
                              ['Rosario',  data.canonRosario]
                          ]
                      }]
                  });
          });

          /*
          carga de la tabla de canon;
          */
          $('#cuerpoTablaCanon').empty();
          var resultados=' ';
          for (var i = 0; i < data.resultado_mes_ME.length; i++) {
              var fecha=data.resultado_mes_ME[i].anioMes.split("-");
              resultados += '<tr > <td class="col-xs-3" style="font-weight: bold">' + meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + ' -' + fecha[0] + '</td><td class="col-xs-3"> $'+addCommas( Math.round((data.resultado_mes_SF[i].total_canon)*100)/100  )+ ' </td> <td class="col-xs-3">$' + addCommas( Math.round((data.resultado_mes_ME[i].total_canon)*100)/100 ) + '</td><td class="col-xs-3">$' + addCommas(Math.round((data.resultado_mes_RO[i].total_canon)*100)/100) + '</td></tr>';
          };

          $('#cuerpoTablaCanon').append(resultados);
          var pie = '<tr> <td class="col-xs-3" style="font-weight: bold">' + 'Total' + '</td><td class="col-xs-3" style="font-weight: bold"> $'+ addCommas( data.canonSantaFe ) + ' </td> <td class="col-xs-3" style="font-weight: bold"> $' +addCommas(  data.canonMelincue )+ '</td><td class="col-xs-3" style="font-weight: bold"> $' + addCommas( data.canonRosario) + '</td></tr>';
            $('#pieTablaCanon').empty();
          $('#pieTablaCanon').append(pie);

          /*
          carga de grafico barras de Santa Fe
          */
          $(function () {
            var fechas=[];
            var brutosSantaFe=[];
            var canonsSantaFe=[];

            for (var i = 0; i < data.resultado_mes_SF.length; i++) {
                var fecha=data.resultado_mes_SF[i].anioMes.split("-");

                fechas.push( (meses[(fecha[1].replace(/^0+/, '') ) - 1 ]) + '-' +fecha[0] );
                brutosSantaFe.push(Math.round((data.resultado_mes_SF[i].total_bruto)*100)/100 );
                canonsSantaFe.push(Math.round((data.resultado_mes_SF[i].total_canon)*100)/100 );

            }

              Highcharts.chart('recaudacionBarraSF', {
                  chart: {
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
                pointFormat: '<span style="color:{series.color}">\u25CF</span> {series.name}: {point.y}'
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
                color: '#F06292'
            }, {
                name: 'Valor Bruto',
                //Modificar los valores de Bruto - Canon.
                data: brutosSantaFe,
                color: '#F48FB1'
            }]
        });
      });
      /*
      Grafico de seguimiento
      */
      $(function () {
        var fechas=[];
        var brutosSantaFe=[];
        var canonsSantaFe=[];
        var brutosMelincue=[];
        var canonsMelincue=[];
        var brutosRosario=[];
        var canonsRosario=[];
        for (var i = 0; i < data.resultado_mes_SF.length; i++) {
            var fecha=data.resultado_mes_SF[i].anioMes.split("-");

            fechas.push( meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + '-' +fecha[0] );
            brutosSantaFe.push(Math.round((data.resultado_mes_SF[i].total_bruto)*100)/100 );
            canonsSantaFe.push(Math.round((data.resultado_mes_SF[i].total_canon)*100)/100 );
            brutosMelincue.push(Math.round((data.resultado_mes_ME[i].total_bruto)*100)/100 );
            canonsMelincue.push(Math.round((data.resultado_mes_ME[i].total_canon)*100)/100 );
            brutosRosario.push(Math.round((data.resultado_mes_RO[i].total_bruto)*100)/100 );
            canonsRosario.push(Math.round((data.resultado_mes_RO[i].total_canon)*100)/100 );
        }

                  Highcharts.chart('sRecaudacionB', {
                      chart: {
                          type: 'area',
                          colors: ['#FF6384', '#36A2EB','#FFCE56']
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
                              text: 'Valor Bruto'
                          },
                          labels: {
                              formatter: function () {
                                  return this.value / 1000;
                              }
                          }
                      },
                      tooltip: {
                          split: true,
                          pointFormat: '{series.name}: <b>${point.y}</b>'
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
                           color: '#FFCE56'
                       },
                       {
                          name: 'CSF',
                          data: brutosSantaFe,
                          color: '#FF6384'
                      }, {
                          name: 'CME',
                          data: brutosMelincue,
                          color: '#36A2EB'
                      }]
                  });
                });

      /*
      grafico recuadacion de barra Melincue
      */

        $(function () {
          var fechas=[];

          var brutosMelincue=[];
          var canonsMelincue=[];

          for (var i = 0; i < data.resultado_mes_ME.length; i++) {
              var fecha=data.resultado_mes_ME[i].anioMes.split("-");

              fechas.push( meses[(fecha[1].replace(/^0+/, '') ) - 1 ]+'-'  +fecha[0] );

              brutosMelincue.push(Math.round((data.resultado_mes_ME[i].total_bruto)*100)/100 );
              canonsMelincue.push(Math.round((data.resultado_mes_ME[i].total_canon)*100)/100 );

          }
            Highcharts.chart('recaudacionBarraM', {
                chart: {
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
              pointFormat: '<span style="color:{series.color}">\u25CF</span> {series.name}: {point.y}'
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
              color: '#29B6F6'
          }, {
              name: 'Valor Bruto',
              //Modificar los valores de Bruto - Canon.
              data: brutosMelincue,
              color: '#81D4FA'
          }]
      });
    });

    /*
      generar grafico de barra Rosario
    */
    $(function () {
      var fechas=[];
      var brutosRosario=[];
      var canonsRosario=[];
      for (var i = 0; i < data.resultado_mes_RO.length; i++) {
          var fecha=data.resultado_mes_RO[i].anioMes.split("-");

          fechas.push( meses[(fecha[1].replace(/^0+/, '') ) - 1 ] + '-' + fecha[0]);
          brutosRosario.push(Math.round((data.resultado_mes_RO[i].total_bruto)*100)/100 );
          canonsRosario.push(Math.round((data.resultado_mes_RO[i].total_canon)*100)/100 );
      }
                  Highcharts.chart('recaudacionBarraR', {
                      chart: {
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
                    pointFormat: '<span style="color:{series.color}">\u25CF</span> {series.name}: {point.y}'
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
                    color: '#FFC107'
                }, {
                    name: 'Valor Bruto',
                    data: brutosRosario,
                    color: '#FFE082'
                }]
            });
          });

      },
      error: function (data) {
        console.log(data);
        var response = JSON.parse(data.responseText);
        $('#alertaFechaInicio').hide();
        $('#alertaFechaFin').hide();
        $('#alertaResultados').hide();

        if(typeof response.fecha_inicio !== 'undefined'){
          $('#alertaFechaInicio span').text(response.fecha_inicio[0]);
          $('#alertaFechaInicio').show();

        }

        if(typeof response.resultados !== 'undefined'){
          $('#alertaResultados span').text(response.resultados[0]);
          $('#alertaResultados').show();

        }

        if(typeof response.fecha_fin !== 'undefined'){
          $('#alertaFechaFin span').text(response.fecha_fin[0]);
          $('#alertaFechaFin').show();

        }

          console.log(data);
      }
   });
  })
