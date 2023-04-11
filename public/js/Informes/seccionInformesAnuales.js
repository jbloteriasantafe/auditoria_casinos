chart = null;
$(document).ready(function() {
  $('#barraInformesMesas').attr('aria-expanded','true');
  $('#informes2').removeClass();
  $('#informes2').addClass('subMenu1 collapse in');
  $('.tituloSeccionPantalla').text('Mesas - Informes Anuales Contables');
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
    id_casino2: c2,
    id_moneda:  m1,
    id_moneda2: m2,
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
      const datas = [data.casino1];
      const names = [leyenda1];
      if(c2 != "" && m2 != ""){
        datas.push(data.casino2);
        names.push(leyenda2);
      }
      grarGraficoCasinos(datas,names);
    },

    error: function (data) {
      const response = data.responseJSON.errors;
      const keys = Object.keys(response);
      const errors = [];
      for(const kidx in keys){
        const k  = keys[kidx];
        for(const erridx in response[k]){
          errors.push(response[k][erridx]);
        }
      }
      $('#mensajeF').empty().append(errors.join('<br>'));
      $('#mensajeErrorFiltros').show();      
    }
   });
})

function grarGraficoCasinos(datas,names){
  let i = 0;
  for(;i < Math.min(datas.length,chart.series.length);i++){
    const name = names[i]? names[i] : 'N/A';
    const data = [0,0,0,0,0,0,0,0,0,0,0,0];
    datas[i].forEach(function(x){
      data[x.mes-1]  = parseFloat(x.total_utilidad_mensual);
    });
    chart.series[i].update({name: name,data: data,showInLegend: true},false);
    chart.series[i].show();
  }
  for(;i<chart.series.length;i++){
    chart.series[i].update({name:'N/A',data: [],showInLegend: false},false);
    chart.series[i].hide();
  }
  chart.redraw();
}
