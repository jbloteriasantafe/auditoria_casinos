chart = null;
$(document).ready(function() {
  $('#barraInformesMesas').attr('aria-expanded','true');
  $('#informes2').removeClass();
  $('#informes2').addClass('subMenu1 collapse in');
  $('.tituloSeccionPantalla').text('Informes Anuales Contables');
  $('#opcInfoInteranuales').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInfoInteranuales').addClass('opcionesSeleccionado');

  $('#dtpFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    viewSelect:'decade',
    minView: 4,
    maxView:4,
    ignoreReadonly: true,
  });

  $('#dtpFecha').data('datetimepicker').setDate(new Date());
  $('#mensajeErrorFiltros').hide();
  chart = Highcharts.chart('speedChart', {
    chart: { type: 'line' },
    title: { text: 'UTILIDAD MENSUAL' },
    xAxis: { categories: ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'] },
    yAxis: [{
        title: { text: '$' },
        labels: { format: '{value}' }
    }],
    plotOptions: {
      line: {
        dataLabels: { enabled: true },
        enableMouseTracking: false
      }
    },
    series: [{name: "N/A", data: [],visible: false,showInLegend: false},{name: "N/A", data: [],visible: false,showInLegend: false}]
  });
  $('#buscar-informes-anuales').click();
});

$('#buscar-informes-anuales').on('click', function(e){
  e.preventDefault();
  const fecha = $('#B_fecha_filtro').val();
  const c1 = $('#CasInformeA').val();
  const c2 = $('#CasComparar').val();
  const m1 = $('#MonInformeA').val();
  const m2 = $('#MonComparar').val();

  var formData = {
    anio: fecha == null? (new Date()).getFullYear() : fecha,
    id_casino:  c1,
    id_casino2: c2 == 0? '' : c2,
    id_moneda:  m1,
    id_moneda2: m2 == 0? '' : m2,
  }

  const leyenda1 = $('#CasInformeA option:selected').text() + ' - ' +$('#MonInformeA option:selected').text();
  const leyenda2 = $('#CasComparar option:selected').text() + ' - ' +$('#MonComparar option:selected').text();

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  $.ajax({
    type: 'POST',
    url: '/informeAnual/obtenerDatos',
    data: formData,
    dataType: 'json',

    success: function (data){
      $('#mensajeErrorFiltros').hide();
      $('#B_fecha_filtro').val(fecha);
      if(c1 == 0 && c2 == 0) return;

      if(c2 != 0){
        if(c1 != 0){
          grarGraficoCasinos([data.casino1,data.casino2],[leyenda1,leyenda2]);
        }
        else grarGraficoCasinos([data.casino2],[leyenda2]);
      }
      else grarGraficoCasinos([data.casino1],[leyenda1]);
    },

    error: function (data) {
      const response = data.responseJSON;
      if(typeof response.id_casino !== 'undefined'){
        $('#mensajeF').text(response.id_casino[0]);
      }
      if(typeof response.id_moneda !== 'undefined'){
        $('#mensajeF').text(response.id_moneda[0]);
      }
      if(typeof response.id_casino2 !== 'undefined'){
        $('#mensajeF').text(response.id_casino2[0]);
      }
      if(typeof response.id_moneda2 !== 'undefined'){
        $('#mensajeF').text(response.id_moneda2[0]);
      }
      $('#mensajeErrorFiltros').show();      
    }
   });
})

function grarGraficoCasinos(datas,names){
  let i = 0;
  console.log(datas);
  console.log(names);
  for(i = 0;i < Math.min(datas.length,chart.series.length);i++){
    let name = names[i]? names[i] : 'N/A';
    let data = [NaN,NaN,NaN,NaN,NaN,NaN,NaN,NaN,NaN,NaN,NaN,NaN];
    datas[i].forEach(function(x){
      if(isNaN(data[x.mes-1])) data[x.mes-1]  = x.total_utilidad_mensual;
      else                     data[x.mes-1] += x.total_utilidad_mensual;
    });
    chart.series[i].update({name: name,data: data,showInLegend: true},true);
    chart.series[i].show();
  }
  for(;i<chart.series.length;i++){
    chart.series[i].update({name:'N/A',data: [],showInLegend: false},true);
    chart.series[i].hide();
  }
}