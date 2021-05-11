$(document).ready(function() {
  $('#barraInformesMesas').attr('aria-expanded','true');
  $('#informes2').removeClass();
  $('#informes2').addClass('subMenu1 collapse in');
  $('#opcInfoMensual').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInfoMensual').addClass('opcionesSeleccionado');
  $('.tituloSeccionPantalla').text('Mesas - Informe Mensual');

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
  }).data('datetimepicker').setDate(new Date());
  $('#casinoFMes').val('1');
  $('#generarGraficos').click();
});

$('#generarGraficos').on('click', function(e){
  e.preventDefault();

  const formData = {
    fecha: $('#B_MyA_filtro').val(),
    id_casino: $('#casinoFMes').val(),
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  $('#graficoPesos').hide();
  $('#graficoDolares').hide();
  $('.mensajeErrorGrafico').text('CARGANDO').show();

  let progress = 0;
  const loading = setInterval(function(){
      const message = ['CARGANDO ―','CARGANDO /','CARGANDO |','CARGANDO \\'];
      $('.mensajeErrorGrafico').text(message[progress]);
      progress = (progress + 1)%4;
  },100);

  $.ajax({
    type: 'POST',
    url: 'informeMensual/obtenerDatos',
    data: formData,
    dataType: 'json',
    success: function (data){
      clearInterval(loading);
      if(data.por_moneda.length != 0){
        $('.mensajeErrorGrafico').hide();
        graficar('RESULTADO MENSUAL POR JUEGO (PESOS)',data.por_moneda[0],$('#graficoPesos'));
        $('#graficoPesos').show();
        if(data.por_moneda.length>1){
          graficar('RESULTADO MENSUAL POR JUEGO (DÓLAR)',data.por_moneda[1],$('#graficoDolares'));
          $('#graficoDolares').show();
        }
      }
      else{
        $('#graficoPesos').hide();
        $('#graficoDolares').hide();
        $('.mensajeErrorGrafico').text('No se han encontrado datos para el mes filtrado.');
        $('.mensajeErrorGrafico').show();
      }
    },
    error: function (data) {
      clearInterval(loading);
      $('.mensajeErrorGrafico').text('Error al procesar el pedido.');
      $('.mensajeErrorGrafico').show();
      console.log('error',data);
    }
  });
});

function graficar(titulo,datos,jqobject){
  const datosObtenidos = [];
  for (const siglas in datos.utilidad) {
    const u = datos.utilidad[siglas];
    if(u == 0) continue;
    datosObtenidos.push([siglas,u]);
  }

  jqobject.highcharts({
    chart: {
      type: 'pie',
      options3d: {
        enabled: true,
        alpha: 45,
        beta: 0,
        defaultFontSize:10
      }
    },
    title: { text: titulo },
    tooltip: { pointFormat: '{point.y} ({point.percentage:.1f}%)' },
    plotOptions:  {
      pie: {
        allowPointSelect: true,
        cursor: 'pointer',
        depth: 35,
        dataLabels: { enabled: false },
        showInLegend: true
      },
      legend: {
        itemStyle: { fontSize: '10px' }
      },
    },
    series: [{
      type: 'pie',
      name: 'Representa',
      colorByPoint: true,
      data: datosObtenidos,
      sliced: true,
      selected: true,
    }]
  });
}