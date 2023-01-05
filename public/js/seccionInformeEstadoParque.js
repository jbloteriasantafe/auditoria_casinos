var habilitadas;
var deshabilitadas;
$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#informesMTM').removeClass();
  $('#informesMTM').addClass('subMenu2 collapse in');

  $('.tituloSeccionPantalla').text('Informe de Casino');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcInformeEstadoParque').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcInformeEstadoParque').addClass('opcionesSeleccionado');


  //Muestra el menu desplegado
  $('#maquinas').show();


  if($('#buscadorCasino option').length == 2){ //opcion default y un casino
    $('#buscadorCasino').val($('#buscadorCasino option').eq(1).val());
    $('#btn-buscar').trigger('click');
  }

});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| INFORMES DE CASINO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

$('#btn-buscar').click(function(e){
  // limpiarVista();
  var id = $('#buscadorCasino').val();

  if (id != 0) {
    $.get('informeEstadoParque/obtenerEstadoParqueDeCasino/' + id , function(data){

        $('.logoCasino').hide();

        switch (data.casino.id_casino) {
          case 1:
            $('#logo_CME').show();
            break;
          case 2:
            $('#logo_CSF').show();
            break;
          case 3:
            $('#logo_CRO').show();
            break;
        }

        habilitadas = data.totales.total_habilitadas;
        deshabilitadas = data.totales.total_deshabilitadas;

        $('#total_habilitadas').text(habilitadas);
        $('#total_deshabilitadas').text(deshabilitadas);

        $('#islas_asignadas').text(data.totales.islas_no_asignadas);
        $('#maquinas_asignadas').text(data.totales.total_no_asignadas);

        $('#sectores').children().not('#filaModelo').remove();

        for (var i = 0; i < data.sectores.length; i++) {
            generarFilaSector(data.sectores[i].descripcion ,data.sectores[i].cantidad);
        }

        $('#modalDetallesParque').modal('show');
    });
  }
});

$('#modalDetallesParque').on('shown.bs.modal',function(){
  generarGraficos(habilitadas,deshabilitadas);
})

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

function limpiarVista(){
  $('#tablaSectores tbody').empty();
  $('#tablaSectores tfoot').empty();
  destruirGrafico();
}

function destruirGrafico(){
  if($('#collapseGraficoHabilitadas').attr('data-generado') == 'true'){
    var chart = ($('#tortaHabilitadas').highcharts());
    chart.destroy();
  }
  $('#collapseGraficoHabilitadas').collapse('hide');
  $('#collapseGraficoHabilitadas').attr('data-generado',false);
}

//Mostrar modal con los datos del Sector
$(document).on('click','.detalle',function(){
    limpiarModal();
    $('.modal-title').text('| VER MÁS');
    $('.modal-header').attr('style','font-family: Roboto-Black; background: #4FC3F7');

    $('#nombre').prop("readonly" , true);
    $('#cantidad_maquinas').prop("readonly" , true);
    $('#casino').prop("disabled" , true);

    $('#inputIsla').prop("readonly" , true);
    $('.btn-default').text('SALIR');
    var id_sector = $(this).val();

    $.get("informeEstadoParque/obtenerSector/" + id_sector, function(data){
        console.log(data);

        $('#nombre').val(data.sector.descripcion);
        $('#cantidad_maquinas').val(data.sector.cantidad_maquinas);
        $('#casino').val(data.casino.id_casino);
        for(var i = 0; i<data.islas.length;i++){
          agregarIsla(data.islas[i],false);
        }
        $('#btn-guardar').hide();
        $('#modalSector').modal('show');
    });
});

function generarFilaSector(sector, cantidad) {
  var fila = $('#filaModelo').clone().show();
  $('#sectores').append(fila);

  console.log(fila);

  fila.attr('id','sector' + sector.id_sector);
  fila.find('.nombreSector').text(sector);
  fila.find('.cantMaquinasSector').text(cantidad);
}

function generarFilaTabla(casino, sector ,cantidad){
  var fila = $(document.createElement('tr'));
  fila.attr('id','sector' + sector.id_sector)
      .append($('<td>')
          .addClass('col-xs-3')
          .text(casino)
      )
      .append($('<td>')
          .addClass('col-xs-5')
          .text(sector)
      )
      .append($('<td>')
          .addClass('col-xs-4')
          .text(cantidad)
      )
    $('#tablaSectores tbody').append(fila);
}

$('#collapseGraficoHabilitadas').on('shown.bs.collapse', function() {
  console.log($(this).attr('data-generado'));
  if($(this).attr('data-generado') != 'true'){
    generarGraficos(habilitadas,deshabilitadas);
  }
})

function generarGraficos(habilitadas, deshabilitadas){
    $('#collapseGraficoHabilitadas').attr('data-generado' , true);

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
      tooltip: { pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'},
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
              ['', habilitadas],
              ['', deshabilitadas]            ]
      }]
  });
}
