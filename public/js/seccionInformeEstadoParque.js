import {AUX} from "/js/Components/AUX.js";
import "/js/Components/inputFecha.js";

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Informe de Casino');
  
  $('#btn-buscar').click(function(e){
    const id_casino = $('#buscadorCasino').val();
    if(id_casino == 0) return;
    
    const formData = AUX.form_entries($('#formDatosInforme')[0]);
    const fecha_hoy = new Date().toISOString().split('T')[0];
    $('[data-js-mostrar-sin-fecha-informe]').toggle(formData.fecha_informe.length == 0 || formData.fecha_informe == fecha_hoy);
    
    AUX.GET('informeEstadoParque/obtenerEstadoParqueDeCasino',
      formData,
      function(data){
        $('.logoCasino').hide();
        $(`.logoCasino[data-id_casino="${id_casino}"]`).show();
        
        $('#total_habilitadas').text(data.totales.total_habilitadas);
        $('#total_deshabilitadas').text(data.totales.total_deshabilitadas);
        $('#islas_asignadas').text(data.totales.islas_no_asignadas);
        $('#maquinas_asignadas').text(data.totales.total_no_asignadas);

        $('#sectores').find('.filaSector').not('#filaModelo').remove();
        for(const s of data.sectores){
          const fila = $('#filaModelo').clone().show().removeAttr('id');
          fila.find('.nombreSector').text(s.descripcion);
          fila.find('.cantMaquinasSector').text(s.cantidad);
          $('#sectores').append(fila);
        }
        
        $('#modalDetallesParque').modal('show');
      }
    );
  });
  
  $('#modalDetallesParque').on('shown.bs.modal',generarGraficoTortaHabilitadas);
  
  //Opacidad del modal al minimizar
  $('#btn-minimizar').click(function(){
    const minimizar = $(this).data("minimizar")==true;
    $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
    $(this).data("minimizar",!minimizar);
  });
  
  if($('#buscadorCasino option').length == 2){ //opcion default y un casino
    $('#buscadorCasino').val($('#buscadorCasino option').eq(1).val());
    $('#btn-buscar').trigger('click');
  }
});

function generarGraficoTortaHabilitadas(){
  Highcharts.chart('tortaHabilitadas', {
    chart: {
      spacingBottom: 0,
      marginBottom: 0,
      spacingTop: 0,
      marginTop: 0,
      height: 350,
      backgroundColor: "#fff",
      type: 'pie',
      options3d: {
        enabled: true,
        alpha: 45,
        beta: 0
      },
    },
    title: { text: ' '},
    legend: {
      labelFormatter: function () {
        return this.name + " " + this.percentage.toFixed(2) + " %";
      },
      layout: 'horizontal',
      align: 'center',
      verticalAlign: 'bottom',
      y: 0,
      padding: 0,
      itemMarginTop: 0,
      itemMarginBottom: 0,
    },
    tooltip: { pointFormat: '{point.percentage:.1f}%'},
    plotOptions: {
      pie: {
        colors: ['#00E676','#FF1744'],
        allowPointSelect: true,
        cursor: 'pointer',
        depth: 35,
        dataLabels: {
          enabled: false,
          format: '{point.name}'
        },
        showInLegend: true
      }
    },
    series: [{
      type: 'pie',
      name: 'Porcentaje',
      data: [
        ['Habilitadas', parseInt($('#total_habilitadas').text())],
        ['Deshabilitadas', parseInt($('#total_deshabilitadas').text())]
      ]
    }]
  });
}
