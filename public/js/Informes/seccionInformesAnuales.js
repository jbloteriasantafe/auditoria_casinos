$(document).ready(function() {

  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');
  $('.tituloSeccionPantalla').text('Informes Anuales Mesas de Paño');
  $('#opcInfoInteranuales').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInfoInteranuales').addClass('opcionesSeleccionado');



  $(function(){
    $('#dtpFecha').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'YYYY',
          pickerPosition: "bottom-left",
          startView: 4,
          minView: 2
        });
      
  });

  setearDatosIniciales();


  $('#mensajeErrorFiltros').hide();
  $('#buscar-informes-anuales').trigger('click');


});


$('#buscar-informes-anuales').on('click', function(e){

  e.preventDefault();

  var f=$('#B_fecha_filtro').val();
  console.log('es',f);

  if(f!= null){
    var fecha=f;
  }else{
    var a = new Date();
    var fecha = a.getFullYear();
  }

  var cas2=$('#CasComparar').val();
  if(cas2==0){
    var c2='';
  }else{
    c2=cas2;
  }
  var mon2=$('#MonComparar').val();
  if(mon2==0){
    var m2='';
  }else{
    m2=mon2;
  }

  var formData= {
    anio: fecha,
    id_casino: $('#CasInformeA').val(),
    id_casino2: c2,
    id_moneda: $('#MonInformeA').val(),
    id_moneda2: m2,
  }
    console.log('mon',mon2);
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: '/informeAnual/obtenerDatos',
      data: formData,
      dataType: 'json',

      success: function (data){
        $('#mensajeErrorFiltros').hide();

        $('#B_fecha_filtro').val(fecha);


           if(cas2 != 0 ){
             grarGraficoCasinos(data.casino1,data.casino2);
             $('#speedChart').show();
           }
           if(mon2 != 0){
             grarGraficoMoneda(data.casino1,data.casino2);
             $('#speedChart').show();
           }
           if(cas2 == 0 && mon2 == 0){
             grarGraficoCasino1(data.casino1);
             $('#speedChart').show();
           }


      },

      error: function (data) {

        $('#mensajeErrorFiltros').show();

        }

   })

})


$(document).on('change','#CasComparar',function(){

  if($(this).val() != 0){
    $('#MonComparar').prop('disabled',true);
    $('#MonComparar').val(0);

    $('#buscar-informes-anuales').trigger('click');
  }
  else{
    $('#MonComparar').prop('disabled',false);
  }
})

$(document).on('change','#MonComparar',function(){

  if($(this).val() != 0){
    $('#CasComparar').prop('disabled',true);
    $('#CasComparar').val(0);

    $('#buscar-informes-anuales').trigger('click');
  }
  else{
    $('#CasComparar').prop('disabled',false);
  }
})



// function generarGrafico(data){
//
//   var datosObtenidos1 = [];
//
//   for (var i = 0; i < data.casino1[i].length; i++) {
//
//     //var v=[];
//     datosObtenidos1.push(data.casino1[i].total_utilidad_mensual);
//
//   }
//
//   var datosObtenidos2 = [];
//   if(data.casino2[0] != 'null'){
//     for (var i = 0; i < data.casino2[1].length; i++) {
//
//       //var v=[];
//       datosObtenidos2.push(data.casino2[i].total_utilidad_mensual);
//
//     }
//   }
//
//   var  chart = new Highcharts.Chart({
//   			chart: {
//   				renderTo: '#speedChart', 	// Le doy el nombre a la gráfica
//   				defaultSeriesType: 'line'	// Pongo que tipo de gráfica es
//   			},
//   			title: {
//   				text: 'UTILIDADES AÑO:'	// Titulo (Opcional)
//   			},
//   			subtitle: {
//   				text: 'Jarroba.com'		// Subtitulo (Opcional)
//   			},
//   			// Pongo los datos en el eje de las 'X'
//   			xAxis: {
//   				categories: ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE',
//           'NOVIEMBRE','DICIEMBRE'],
//   				// Pongo el título para el eje de las 'X'
//   				title: {
//   					text: 'Meses'
//   				}
//   			},
//   			yAxis: {
//   				// Pongo el título para el eje de las 'Y'
//   				title: {
//   					text: 'Utilidad'
//   				}
//   			},
//   			// Doy formato al la "cajita" que sale al pasar el ratón por encima de la gráfica
//   			tooltip: {
//   				enabled: true,
//   				formatter: function() {
//   					return '<b>'+ this.series.name +'</b><br/>'+
//   						this.x +': '+ this.y +' '+this.series.name;
//   				}
//   			},
//   			// Doy opciones a la gráfica
//   			plotOptions: {
//   				line: {
//   					dataLabels: {
//   						enabled: true
//   					},
//   					enableMouseTracking: true
//   				}
//   			},
//   			// Doy los datos de la gráfica para dibujarlas
//   			series: [
//
//   		            {
//   		                name: data.casino1[0].id_casino,
//   		                data: datosObtenidos1
//   		            },
//   		            {
//   		                name: data.casino2[0].casino,
//   		                data: datosObtenidos1
//   		            }],
//   		});
//
//
//   	}

function grarGraficoCasinos(data1,data2){
  Highcharts.setOptions({
    lang: {
          contextButtonTitle:'Opciones',
          downloadCSV:'Descargar como CSV',
          downloadJPEG: 'Descargar como imagen JPEG ',
          downloadPDF: 'Descargar como PDF ',
          downloadPNG: 'Descargar como imagen PNG ',
          downloadSVG: 'Descargar como SVG',
          downloadXLS: 'Descargar como XLS',
          printChart: 'Imprimir gráfico'
    }
  });
  var datosObtenidos1 = [];

  for (var i = 0; i < data1.length; i++) {

    datosObtenidos1.push(data1[i].total_utilidad_mensual);

  }
  if(data2.length > 0){
  var datosObtenidos2 = [];

    for (var j = 0; j < data2.length; j++) {

      datosObtenidos2.push(data2[j].total_utilidad_mensual);
    }
  }
  Highcharts.chart('speedChart', {
      chart: {
          type: 'line'
      },
      title: {
          text: 'Utilidades mensuales en ' + data1[0].descripcion
      },

      xAxis: {
          categories: ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC']
      },
      yAxis: {
          title: {
              text: '$'
          }
      },
      plotOptions: {
          line: {
              dataLabels: {
                  enabled: true
              },
              enableMouseTracking: false
          }
      },
      series: [{
          name: data1[0].nombre,
          data: datosObtenidos1
      }, {
          name: data2[0].nombre,
          data: datosObtenidos2
      }]
  });

}
function grarGraficoCasino1(data1){
  Highcharts.setOptions({
    lang: {
          contextButtonTitle:'Opciones',
          downloadCSV:'Descargar como CSV',
          downloadJPEG: 'Descargar como imagen JPEG ',
          downloadPDF: 'Descargar como PDF ',
          downloadPNG: 'Descargar como imagen PNG ',
          downloadSVG: 'Descargar como SVG',
          downloadXLS: 'Descargar como XLS',
          printChart: 'Imprimir gráfico'
    }
  });
  console.log('entro casino1');
  var datosObtenidos1 = [];

  for (var i = 0; i < data1.length; i++) {

    datosObtenidos1.push(data1[i].total_utilidad_mensual);

  }


  Highcharts.chart('speedChart', {
      chart: {
          type: 'line'
      },
      title: {
          text: 'Utilidades mensuales en ' + data1[0].descripcion
      },

      xAxis: {
          categories: ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC']
      },
      yAxis: {
          title: {
              text: '$'
          }
      },
      plotOptions: {
          line: {
              dataLabels: {
                  enabled: true
              },
              enableMouseTracking: false
          }
      },
      series: [{
          name: data1[0].nombre,
          data: datosObtenidos1
      }, ]
  });

}
function grarGraficoMoneda(data1,data2){

  Highcharts.setOptions({
    lang: {
          contextButtonTitle:'Opciones',
          downloadCSV:'Descargar como CSV',
          downloadJPEG: 'Descargar como imagen JPEG ',
          downloadPDF: 'Descargar como PDF ',
          downloadPNG: 'Descargar como imagen PNG ',
          downloadSVG: 'Descargar como SVG',
          downloadXLS: 'Descargar como XLS',
          printChart: 'Imprimir gráfico'
    }
  });
  var datosObtenidos1 = [];

  for (var i = 0; i < data1.length; i++) {

    datosObtenidos1.push(data1[i].total_utilidad_mensual);

  }

  var datosObtenidos2 = [];
  if(data2.length > 0){

    for (var j = 0; j < data2.length; j++) {

      //var v=[];
      datosObtenidos2.push(data2[j].total_utilidad_mensual);

    }
    var nombre2=data2[0].nombre;
  }
  else{
    var nombre2='';
  }
  Highcharts.chart('speedChart', {
      chart: {
          type: 'line'
      },
      title: {
          text: 'Utilidades mensuales de ' + data1[0].nombre
      },

      xAxis: {
          categories: ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC']
      },
      yAxis: {
          title: {
              text: 'UTILIDADES'
          }
      },
      plotOptions: {
          line: {
              dataLabels: {
                  enabled: true
              },
              enableMouseTracking: false
          }
      },
      series: [{
          name: data1[0].nombre,
          data: datosObtenidos1
      }, {
          name: nombre2,
          data: datosObtenidos2
      }]
  });

}




function setearDatosIniciales(){

  var fecha = new Date();
  $('#B_fecha_filtro').val(fecha.getFullYear() - 1);
  $('#CasInformeA').val('1');
  $('#MonInformeA').val('1');
  $('#speedChart').hide();
  $('#MonComparar').val(0);
  $('#CasComparar').val(0);



}
