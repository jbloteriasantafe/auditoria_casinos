$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Beneficios');

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

  $('#btn-buscar').trigger('click');
});

//Pop up de informacion de los iconos de acciones
$(document).on('mouseenter','.popInfo',function(e){
    $(this).popover('show');
});

//Popup para ajustara las diferencias de los beneficios
$(document).on('click','.pop',function(e){
    e.preventDefault();
    // console.log('asd');
    $('.pop').not(this).popover('hide');
    $(this).popover('show');
});

$(document).on('click','.cancelarAjuste',function(e){
  $('.pop').popover('hide');
});

//Filtro de búsqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  var page_size = (page_size != null) ? page_size : 10;
  var page_number = (pagina != null) ? pagina : 1;
  var sort_by = (columna != null) ? {columna,orden} : null;
  if(sort_by == null){ // limpio las columnas
    $('#tablaBeneficios th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaBeneficios .activa').attr('value'),orden: $('#tablaBeneficios .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaBeneficios th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
      id_casino: $('#selectCasinos').val(),
      fecha_desde: $('#fecha_desde').val(),
      fecha_hasta: $('#fecha_hasta').val(),
      id_tipo_moneda: $('#selectTipoMoneda').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
  }

  $.ajax({
      type: 'POST',
      url: 'beneficios/buscarBeneficios',
      data: formData,
      dataType: 'json',
      success: function (resultados) {
          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
          $('#cuerpoTablaResultados tr').remove();
          console.log(resultados.data);
          for (var i = 0; i < resultados.data.length; i++) {
            var filaBeneficio = generarFilaTabla(resultados.data[i]);
            $('#cuerpoTablaResultados').append(filaBeneficio);
          }
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});

//Validación
$(document).on('click','.validar',function(e){
  //id_casino | año | mes | id_tipo_moneda
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  var formData = {
      id_casino: $(this).attr('data-casino'),
      anio: $(this).attr('data-anio'),
      mes: $(this).attr('data-mes'),
      id_tipo_moneda: $(this).attr('data-tipo'),
  }

  $('#casinoModal').val($(this).parent().parent().find('td:nth-child(1)').text());
  $('#tipoMonedaModal').val($(this).parent().parent().find('td:nth-child(4)').text());
  $('#anioModal').val($(this).parent().parent().find('td:nth-child(3)').text());
  $('#mesModal').val($(this).parent().parent().find('td:nth-child(2)').text());

  $.ajax({
      type: 'POST',
      url: 'beneficios/obtenerBeneficiosParaValidar',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);

          $('#tablaModal #cuerpoTabla tr').remove();

          for (var i = 0; i < data.resultados.length; i++) {
            var filaBeneficio = generarFilaModal(data.resultados[i]);
            $('#tablaModal #cuerpoTabla').append(filaBeneficio)
          }
          $('#textoExito').text('');
          $('#modalValidarBeneficio').modal('show');
          $('#btn-validar-si').hide();
          $('#btn-validar').show();
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });

});

$(document).on('click','#tablaBeneficios thead tr th[value]',function(e){
  $('#tablaBeneficios th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-desc')){
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaBeneficios th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

/***** FUNCIONES *****/
function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaBeneficios .activa').attr('value');
  var orden = $('#tablaBeneficios .activa').attr('estado');
  console.log(pageNumber);
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

//Generar las filas de los beneficios por cada día del mes para el modal
function generarFilaModal(beneficio){
  const fila = $(document.createElement('tr'));

  const formulario =  '<div align="right">'
                  +   '<input class="form-control valorAjuste" type="text" value="' + (-beneficio.diferencia) + '" placeholder="JACKPOT"><br>'
                  +   '<button id="'+ beneficio.id_beneficio +'" class="btn btn-successAceptar ajustar" type="button" style="margin-right:8px;">AJUSTAR</button>'
                  +   '<button class="btn btn-default cancelarAjuste" type="button">CANCELAR</button>'
                  +   '</div>';

  fila.attr('id','id'+beneficio.id_beneficio)
  .append($('<td>').text(beneficio.fecha))
  .append($('<td>').text(beneficio.beneficio_calculado))
  .append($('<td>').text(beneficio.beneficio))
  .append($('<td>').text(beneficio.diferencia))
  .append($('<td>').append($('<button>')
    .addClass('btn btn-success pop')
    .attr('tabindex', 0)
    .attr('data-trigger','manual')
    .attr('data-toggle','popover')
    .attr('data-html','true')
    .attr('title','AJUSTE')
    .attr('data-content',formulario)
    .attr('disabled',(beneficio.diferencia == 0))
    .append($('<i>').addClass('fa fa-fw fa-wrench'))
    )
  )
  .append($('<td>').append($('<textarea>').addClass('form-control').css('resize','vertical')))
  .append($('<td>').append($('<button>')
    .append($('<i>')
        .addClass('fa').addClass('fa-fw').addClass('fa fa-fw fa-search')
    )
    .addClass('btn').addClass('btn-info').addClass('ver-producido')
    .attr('data-idProducido',beneficio.id_producido)
    .attr('disabled',!beneficio.id_producido)
    .attr('title','DETALLES PRODUCIDO')
  ));
  return fila;
}

$(document).on('click','.ver-producido',function(e){
  e.preventDefault();
  window.open('beneficios/generarPlanillaDiferenciasProducido/' + $(this).attr('data-idProducido'),'_blank');
});

//Generar las filas para la tabla de los beneficios mensuales
function generarFilaTabla(beneficio){
  const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

  const fila = $('<tr>').attr('id','beneficio' + beneficio.id_beneficio_mensual)
  .append($('<td>').addClass('col-xs-2').text(beneficio.casino))
  .append($('<td>').addClass('col-xs-2').text(meses[beneficio.mes - 1]))
  .append($('<td>').addClass('col-xs-1').text(beneficio.anio))
  .append($('<td>').addClass('col-xs-2').text(beneficio.tipo_moneda))
  .append($('<td>').addClass('col-xs-3').text(beneficio.diferencias_mes));

  const botones = $('<td>').append($('<button>').addClass('btn btn-info planilla popInfo')
  .attr('data-trigger','hover').attr('data-content','IMPRIMIR')
  .append($('<i>').addClass('fa fa-fw fa-print')));

  if(beneficio.id_beneficio_mensual){
    const formulario = '<div align="center">'
    + '<input class="form-control valorAjuste" type="text" value="" placeholder="IEA"><br>'
    + '<button id="'+ beneficio.id_beneficio_mensual +'" class="btn btn-successAceptar cargarImpuesto" type="button" style="margin-right:8px;">CARGAR</button>'
    + '<button class="btn btn-default cancelarAjuste" type="button">CANCELAR</button>'
    + '</div>';
    botones.prepend($('<button>').addClass('btn btn-success pop').attr('title','CARGAR IMPUESTO')
    .attr('data-trigger','manual').attr('data-content',formulario).attr('data-html','true')
    .append($('<i>').addClass('fa fa-fw fa-upload')));
  }else{
    botones.prepend($('<button>').addClass('btn btn-success validar popInfo').attr('data-content','VALIDAR')
    .attr('data-trigger','hover')
    .append($('<i>').addClass('fa fa-fw fa-check')));
  }

  botones.find('button').attr('data-casino', beneficio.id_casino)
  .attr('data-tipo', beneficio.id_tipo_moneda)
  .attr('data-anio', beneficio.anio)
  .attr('data-mes',beneficio.mes)
  .attr('data-toggle','popover')
  .attr("data-placement" , "top")
  .attr('tabindex', 0)
  .val(beneficio.id_beneficio);

  fila.append(botones);
  return fila;
}

$(document).on('click','.cargarImpuesto',function(e){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  var formData = {
    id_beneficio_mensual: $(this).attr('id'),
    impuesto: $(this).parent().find('.valorAjuste').val().replace(/,/g,"."),
  }

  $.ajax({
      type: 'POST',
      url: 'beneficios/cargarImpuesto',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('.pop').popover('hide');
        $('#btn-buscar').trigger('click');
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });


});

$(document).on('click','.ajustar',function(e){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  // console.log($(this).parent().find('.valorAjuste').val());
  var formData = {
    id_beneficio: $(this).attr('id'),
    valor: $(this).parent().find('.valorAjuste').val().replace(/,/g,"."),
  }

  $.ajax({
      type: 'POST',
      url: 'beneficios/ajustarBeneficio',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        var calculado = Math.round((parseFloat($('#id' + data.ajuste.id_beneficio).find('td:nth-child(2)').html()) + parseFloat(data.ajuste.valor))*100)/100;
        var beneficio = Math.round((parseFloat($('#id' + data.ajuste.id_beneficio).find('td:nth-child(3)').html()))*100)/100;
        var diferencia = Math.round((calculado - beneficio)*100)/100;
        console.log(calculado);
        $('#id' + data.ajuste.id_beneficio).find('td:nth-child(2)').text(calculado.toFixed(2));
        $('#id' + data.ajuste.id_beneficio).find('td:nth-child(4)').text(diferencia.toFixed(2));
        $('#id' + data.ajuste.id_beneficio).find('td:nth-child(5) button').attr('disabled',(diferencia == 0));
        $('.pop').popover('hide');
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});

$(document).on('click','#btn-validar',function(e){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  var beneficios_ajustados = [];

  $('#cuerpoTabla tr').each(function(){
    var beneficios_ajustado = {
      id_beneficio: $(this).attr('id').substring(2),
      observacion: $(this).find('td:nth-child(6) textarea').val(),
    };
    beneficios_ajustados.push(beneficios_ajustado);
  });
  $('#textoExito').text('');
  var formData = {
    beneficios_ajustados: beneficios_ajustados,
  }

  $.ajax({
      type: 'POST',
      url: 'beneficios/validarBeneficios',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('#tablaModal #cuerpoTabla tr').remove();
        $('#modalValidarBeneficio').modal('hide');

        $('#mensajeExito').hide();
        $('#mensajeExito h3').text('BENEFICIOS validados');
        $('#mensajeExito p').text('Los beneficios fueron validados correctamente');
        $('#mensajeExito div').css('background-color','#6dc7be');
        $('#mensajeExito').show();
        $('#btn-buscar').trigger('click');

      },
      error: function (data) {
          console.log('Error:', data);
          const errores = ["Errores:"];
          Object.keys(data.responseJSON).forEach(function(k,_){
            errores.push(...data.responseJSON[k]);
          });
          $('#textoExito').html("<p>"+errores.join("</p>")+"</p>");
          if(data.responseJSON.id_beneficio !== undefined){
            $('#btn-validar-si').hide();
          }
          if(data.responseJSON.id_producido !== undefined){
            $('#btn-validar-si').show();
            $('#btn-validar').hide();
          }
      }
    });
});

$(document).on('click','#btn-validar-si',function(e){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  var beneficios_ajustados = [];

  $('#cuerpoTabla tr').each(function(){
    var beneficios_ajustado = {
      id_beneficio: $(this).attr('id').substring(2),
      observacion: $(this).find('td:nth-child(6) textarea').val(),
    };
    beneficios_ajustados.push(beneficios_ajustado);
  });
  $('#textoExito').text('');
  var formData = {
    beneficios_ajustados: beneficios_ajustados,
  }

  $.ajax({
      type: 'POST',
      url: 'beneficios/validarBeneficiosSinProducidos',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('#tablaModal #cuerpoTabla tr').remove();
        $('#modalValidarBeneficio').modal('hide');

        $('#mensajeExito').hide();
        $('#mensajeExito h3').text('BENEFICIOS validados');
        $('#mensajeExito h4').text('Los beneficios fueron validados correctamente');
        $('#mensajeExito div').css('background-color','#6dc7be');
        $('#mensajeExito').show();
        $('#btn-buscar').trigger('click');


      },
      error: function (data) {
          console.log('Error:', data);
          var texto = "";
          $('#textoExito').text('');
          if(data.responseJSON.id_beneficio !== undefined){
            texto += 'Hay beneficios sin ajustar.  ';
            $('#btn-validar-si').hide();
          }
          if(data.responseJSON.not_found !== undefined){
            texto += 'Recargue la página.  ';
            $('#btn-validar-si').hide();
          }
          $('#textoExito').text('Errores: '+ texto);
      }
    });
});

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();
console.log($(this).attr('data-tipo'));
    window.open('beneficios/generarPlanilla/' + $(this).attr('data-casino') + "/" + $(this).attr('data-tipo') + "/" + $(this).attr('data-anio') +"/"+ $(this).attr('data-mes'),'_blank');

});

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
