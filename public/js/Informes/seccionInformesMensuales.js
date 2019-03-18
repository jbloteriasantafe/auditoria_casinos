$(document).ready(function() {

  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');
  $('#opcInfoMensuales').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInfoMensuales').addClass('opcionesSeleccionado');
  $('.tituloSeccionPantalla').hide();
  $('#btn-ayuda').hide();

  $('#informesMes').show();
  $('#informesMes').css('display','inline-block');
  $(".tab_content").hide(); //Hide all content
  	$("ul.informesMes li:first").addClass("active").show(); //Activate first tab
  	$(".tab_content:first").show(); //Show first tab content

  $(function(){
    $('#dtpFecha').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'yyyy-MM',
          pickerPosition: "bottom-left",
          startView: 3,
          minView: 3,
          ignoreReadonly: true,

        });
  });
  $(function(){
    $('#dtpFechaMyA').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'yyyy-MM',
      pickerPosition: "bottom-left",
      startView: 3,
      minView: 3,
      ignoreReadonly: true,
        });
  });
  $('#CasInforme').val('');
  $('#fechaInformeMensual').val('');

  $('#buscar-informes-mensuales').trigger('click',[1,10,'fecha_mes','desc']);

});

//PESTAÑAS
$("ul.informesMes li").click(function() {

    $("ul.informesMes li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content
                console.log(activeTab);
    if(activeTab == '#graficosMes'){
      $('#casinoFMes').val('1')
       $('#B_MyA_filtro').val('0-0');
      $('#generarGraficos').trigger('click');
    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});


$('#generarGraficos').on('click', function(e){
  e.preventDefault();

  var formData= {
    fecha:$('#B_MyA_filtro').val(),
    id_casino:$('#casinoFMes').val(),
  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'informeMensual/obtenerDatos',
      data: formData,
      dataType: 'json',

      success: function (data){
        console.log('dd',data.por_moneda.length);
        if(data.por_moneda.length != 0){
          $('.mensajeErrorGrafico').hide();

            if(data.por_moneda.length>1){

              crearGraficoPesos(data.por_moneda[0]);
              crearGraficoDolar(data.por_moneda[1]);
              $('#graficoPesos').show();
              $('#graficoDolares').show();
            }
            else{
              crearGraficoPesos(data.por_moneda[0]);
              $('#graficoPesos').show();

            }
        }
        else{

        $('#graficoPesos').hide();
        $('#graficoDolares').hide();

        $('.mensajeErrorGrafico').text('No se han encontrado informes para el mes filtrado.');
        $('.mensajeErrorGrafico').show();
      }
        //setear fecha
        $('#B_MyA_filtro').val(data.fecha[0] + '-' + data.nombre_mes);

      },
      error: function (data) {
          console.log('error',data);
        }
      });

})

$('#buscar-informes-mensuales').on('click',function(e,pagina,page_size,columna,orden){
  e.preventDefault();

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });
  $('#tablaInformesMensuales tbody tr').remove();

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }
  else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaInformesMensuales .activa').attr('value'),orden: $('#tablaInformesMensuales .activa').attr('estado')} ;

  if(sort_by == null){ // limpio las columnas
    $('#tablaInformesMensuales th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

      var formData= {
        fecha: $('#fechaInformeMensual').val(),
        id_casino: $('#CasInforme').val(),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
      }

      $.ajax({
          type: 'POST',
          url: 'informeMensual/buscar',
          data: formData,
          dataType: 'json',

          success: function (data){

              $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.mensuales.total,clickIndice);
                  for (var i = 0; i < data.mensuales.data.length; i++) {

                      var fila=  generarFila(data.mensuales.data[i]);

                      $('#tablaInformesMensuales').append(fila);
                  }

              $('#herramientasPaginacion').generarIndices(page_number,page_size,data.mensuales.total,clickIndice);

          },
          error: function(data){

          },
      })

  });


$(document).on('click','.imprimirMensual', function(e){

  e.preventDefault();
   var fecha=$(this).val();
   var id_casino=$(this).attr('idcasino');
window.open('informeMensual/imprimir/'+fecha+'/'+id_casino,'_blank');
  // $.get('informeMensual/obtenerDatos/', function(data){
  //   console.log('da',data);
});


/***********funciones grales*****************/

function generarFila(data){

  var fila = $(document.createElement('tr'));

  var mes=data.fecha_mes.split('-');
  var nombreMes;

  switch (mes[1]) {
    case '01':
      nombreMes='Enero';
      break;
    case '02':
      nombreMes='Febrero';
        break;
    case '03':
      nombreMes='Marzo';
        break;
    case '04':
      nombreMes='Abril';
        break;
    case '05':
      nombreMes='Mayo';
        break;
    case '06':
      nombreMes='Junio';
        break;
    case '07':
      nombreMes='Julio';
        break;
    case '08':
      nombreMes='Agosto';
        break;
    case '09':
      nombreMes='Septiembre';
        break;
    case '10':
      nombreMes='Octubre';
        break;
    case '11':
      nombreMes='Noviembre';
        break;
    default:
      nombreMes='Diciembre';

  }

  fila.attr('id',data.id_importacion_mensual_mesas)
      .append($('<td>').addClass('.m_fecha').addClass('col-xs-4').text(nombreMes + ' ' + mes[0]).css('text-align','center'))
      .append($('<td>').addClass('.m_casino').addClass('col-xs-4').text(data.nombre).css('text-align','center'))
      .append($('<td>').css('text-align','center').addClass('col-xs-4').append($('<button>').addClass('btn btn-info imprimirMensual')
                          .val(data.fecha_mes).attr('idcasino',data.id_casino).append($('<i>').addClass('fa fa-fw fa-print').append($('</i>').append($('</button>'))))))



  return fila;
};

function crearGraficoPesos(datos){
  // Highcharts.setOptions({
  //   lang: {
  //         contextButtonTitle:'Opciones',
  //         downloadCSV:'Descargar como CSV',
  //         downloadJPEG: 'Descargar como imagen JPEG ',
  //         downloadPDF: 'Descargar como PDF ',
  //         downloadPNG: 'Descargar como imagen PNG ',
  //         downloadSVG: 'Descargar como SVG',
  //         downloadXLS: 'Descargar como XLS',
  //         printChart: 'Imprimir gráfico'
  //   }
  // });
  //configuración básica del gráfico 3D
   var chart = {
      type: 'pie',
      options3d: {
         enabled: true,
         alpha: 45,
         beta: 0,
         defaultFontSize:10
      }
   };

   //titulo del gráfico
   var title = {
     text: 'RESULTADO MENSUAL POR JUEGO (PESOS)'
   };

   //lo que se muestra escrito sobre el gráfico al pasar encima de él con mouse
   var tooltip = {
      pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
   };

   var plotOptions = {
      pie: {
          allowPointSelect: true,
          cursor: 'pointer',
          depth: 35,
          dataLabels: {
                enabled: false
            },
            showInLegend: true
      },
      legend: {
      itemStyle: {
       fontSize: '10px'
      }
     },

   };

   var datosObtenidos = [];

   for (var i = 0; i < datos.length; i++) {

     //var v=[];
     datosObtenidos.push([datos[i].name,datos[i].y]);

   }

   //datos a mostrar
   var series= [{
         type: 'pie',
            name: 'Representa',
            colorByPoint: true,
            data: datosObtenidos,
            sliced: true,
            selected: true,

   }];
   //agrupa datos del gráfico en json, para crearlo
   var json = {};
   json.chart = chart;
   json.title = title;
   json.tooltip = tooltip;
   json.plotOptions = plotOptions;
   json.series = series;

   $('#graficoPesos').highcharts(json);
   $('.highcharts-credits').hide();

   //hasta aca llega el primer gráfico
};

function crearGraficoDolar(datos){
  // Highcharts.setOptions({
  //   lang: {
  //         contextButtonTitle:'Opciones',
  //         downloadCSV:'Descargar como CSV',
  //         downloadJPEG: 'Descargar como imagen JPEG ',
  //         downloadPDF: 'Descargar como PDF ',
  //         downloadPNG: 'Descargar como imagen PNG ',
  //         downloadSVG: 'Descargar como SVG',
  //         downloadXLS: 'Descargar como XLS',
  //         printChart: 'Imprimir gráfico'
  //   }
  // });
   //configuración básica del gráfico 3D
    var chart2 = {
       type: 'pie',
       options3d: {
          enabled: true,
          alpha: 45,
          beta: 0
       }
    };

    //titulo del gráfico
    var title2 = {
       text: 'RESULTADO MENSUAL POR JUEGO (DÓLAR)'
    };

    //lo que se muestra escrito sobre el gráfico
    var tooltip2 = {
       pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
    };

    var plotOptions2 = {
       pie: {
           allowPointSelect: true,
           cursor: 'pointer',
           depth: 35,
           dataLabels: {
                 enabled: false
             },
             showInLegend: true
       },
       legend: {
       itemStyle: {
        fontSize: '10px'
       }
      },

    };
    var datosObtenidos = [];

    for (var i = 0; i < datos.length; i++) {

      datosObtenidos.push([datos[i].name,datos[i].y]);
    }


    //datos a mostrar
    var serieDolar= [{
          type: 'pie',
             name: 'Representa',
             data: datosObtenidos,
             sliced: true,
             selected: true,
    }];
    //agrupa datos del gráfico en json, para crearlo
    var json = {};
    json.chart = chart2;
    json.title = title2;
    json.tooltip = tooltip2;
    json.plotOptions = plotOptions2;
    json.series = serieDolar;

    $('#graficoDolares').highcharts(json);
    $('.highcharts-credits').hide();

};


/*****************PAGINACION******************/
$(document).on('click','#tablaInformesMensuales thead tr th[value]',function(e){

  $('#tablaInformesMensuales th').removeClass('activa');

  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{

    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
        $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaInformesMensuales th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndice(e,pageNumber,tam){

  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaInformesMensuales .activa').attr('value');
  var orden = $('#tablaInformesMensuales .activa').attr('estado');
  $('#buscar-informes-mensuales').trigger('click',[pageNumber,tam,columna,orden]);
}
