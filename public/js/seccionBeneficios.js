$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Beneficios');
  $('#opcBeneficios').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcBeneficios').addClass('opcionesSeleccionado');

  $('#mensajeExito').hide();

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
      // url: 'beneficios/validarBeneficios',
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
  var fila = $(document.createElement('tr'));

  var boolProducido= false;

  if(!beneficio.id_producido){
    boolProducido=true
  }
  
  console.log(beneficio);

  var diferencia = beneficio.diferencia;

  /*********     CON LOS 4 CAMPOS!   ***************/
  //Si la diferencia es MENOR A 0 se agrega al campo coinin
  // var coinin = '<input class="form-control coinin" type="text" value="'+ diferencia * -1 +'" placeholder="COIN IN"><br>';
  // var coinout = '<input class="form-control coinout" type="text" value="" placeholder="COIN OUT"><br>';
  //Si es menor
  // if (diferencia > 0) {
  //   coinin = '<input class="form-control coinin" type="text" value="" placeholder="COIN IN"><br>';
  //   coinout = '<input class="form-control coinout" type="text" value="'+ diferencia +'" placeholder="COIN OUT"><br>';
  // }

  // var formulario =  '<div align="right">'
  //                 +   coinin
  //                 +   coinout
  //                 +   '<input class="form-control jackpot" type="text" value="" placeholder="JACKPOT"><br>'
  //                 +   '<input class="form-control progresivo" type="text" value="" placeholder="PROGRESIVO"><br>'
  //                 +   '<button id="'+ beneficio.id_beneficio +'" class="btn btn-successAceptar ajustar" type="button" style="margin-right:8px;">AJUSTAR</button>'
  //                 +   '<button class="btn btn-default cancelarAjuste" type="button">CANCELAR</button>'
  //                 + '</div>'

  var formulario =  '<div align="right">'
                  +   '<input class="form-control valorAjuste" type="text" value="' + diferencia * -1 + '" placeholder="JACKPOT"><br>'
                  +   '<button id="'+ beneficio.id_beneficio +'" class="btn btn-successAceptar ajustar" type="button" style="margin-right:8px;">AJUSTAR</button>'
                  +   '<button class="btn btn-default cancelarAjuste" type="button">CANCELAR</button>'
                  + '</div>';


  fila.attr('id','id'+beneficio.id_beneficio)
      .append($('<td>')
          // .addClass('col-xs-1')
          .text(beneficio.fecha)
      )
      .append($('<td>')
          // .addClass('col-xs-2')
          .text(beneficio.beneficio_calculado)
      )
      .append($('<td>')
          // .addClass('col-xs-2')
          .text(beneficio.beneficio)
      )
      .append($('<td>')
          // .addClass('col-xs-2')
          .text(diferencia)
      )
      .append($('<td>')
          // .addClass('col-xs-1')
          .append($('<button>')
              .addClass('btn btn-success pop')
              .attr('tabindex', 0)
              .attr('data-trigger','manual')
              .attr('data-toggle','popover')
              .attr('data-html','true')
              .attr('title','AJUSTE')
              .attr('data-content',formulario)
              .attr('disabled',(diferencia == 0))
              .append($('<i>').addClass('fa fa-fw fa-wrench'))
          )
      )
      .append($('<td>')
          // .addClass('col-xs-4')
          .append($('<textarea>').addClass('form-control').css('resize','vertical'))
      )

      .append($('<td>')
      .append($('<button>')
                          .append($('<i>')
                              .addClass('fa').addClass('fa-fw').addClass('fa fa-fw fa-search')
                          )
                          .addClass('btn').addClass('btn-info').addClass('ver-producido')
                          .attr('data-idProducido',beneficio.id_producido)
                          .attr('disabled',boolProducido)
                      ));

    return fila;
}

$(document).on('click','.ver-producido',function(){
  
  id_producido=$(this).attr('data-idProducido')
  console.log(id_producido);
  window.open('producidos/generarPlanilla/' + id_producido,'_blank');
});

//Generar las filas para la tabla de los beneficios mensuales
function generarFilaTabla(beneficio){
      var fila = $(document.createElement('tr'));
      var meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

      fila.attr('id','beneficio' + beneficio.id_beneficio_mensual)
          .append($('<td>')
              .addClass('col-xs-2')
              .text(beneficio.casino)
          )
          .append($('<td>')
              .addClass('col-xs-2')
              .text(meses[beneficio.mes - 1])
          )
          .append($('<td>')
              .addClass('col-xs-1')
              .text(beneficio.anio)
          )
          .append($('<td>')
              .addClass('col-xs-2')
              .text(beneficio.tipo_moneda)
          )
          .append($('<td>')
              .addClass('col-xs-3')
              .text(beneficio.diferencias_mes)
          )

        var formulario =  '<div align="center">'
                          +   '<input class="form-control valorAjuste" type="text" value="" placeholder="IEA"><br>'
                          +   '<button id="'+ beneficio.id_beneficio_mensual +'" class="btn btn-successAceptar cargarImpuesto" type="button" style="margin-right:8px;">CARGAR</button>'
                          +   '<button class="btn btn-default cancelarAjuste" type="button">CANCELAR</button>'
                          + '</div>';
        // TODO cambiar , viene indefinido, no null
        if(beneficio.id_beneficio_mensual !== null){
          fila.append($('<td>')
              .append($('<button>')
                  .addClass('btn btn-success pop')
                  .val(beneficio.id_beneficio_mensual)
                  .attr('tabindex', 0)
                  .attr('data-trigger','manual')
                  .attr('data-toggle','popover')
                  .attr('data-html','true')
                  .attr('title','CARGAR IMPUESTO')
                  .attr('data-placement','top')
                  .attr('data-content',formulario)
                  .append($('<i>').addClass('fa fa-fw fa-upload'))
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .addClass('btn btn-info planilla popInfo')
                  .attr('data-casino', beneficio.id_casino)
                  .attr('data-tipo', beneficio.id_tipo_moneda)
                  .attr('data-anio', beneficio.anio)
                  .attr('data-mes',beneficio.mes)
                  .val(beneficio.id_beneficio)
                  //Descripcion del icono | Nombre de la acción
                  .attr('tabindex', 0)
                  .attr('data-trigger','hover')
                  .attr('data-toggle','popover')
                  .attr("data-placement" , "top")
                  .attr('data-content','IMPRIMIR')
                  //Icono
                  .append($('<i>').addClass('fa fa-fw fa-print'))
              )
          );
        }else{
          fila.append($('<td>')
              .append($('<button>')
                  .addClass('btn btn-success validar popInfo')
                  .attr('data-casino', beneficio.id_casino)
                  .attr('data-tipo', beneficio.id_tipo_moneda)
                  .attr('data-anio', beneficio.anio)
                  .attr('data-mes',beneficio.mes)
                  .val(beneficio.id_beneficio)
                  //Descripcion del icono | Nombre de la acción
                  .attr('tabindex', 0)
                  .attr('data-trigger','hover')
                  .attr('data-toggle','popover')
                  .attr("data-placement" , "top")
                  .attr('data-content','VALIDAR')
                  //Icono
                  .append($('<i>').addClass('fa fa-fw fa-check'))
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .addClass('btn btn-info planilla popInfo')
                  .attr('data-casino', beneficio.id_casino)
                  .attr('data-tipo', beneficio.id_tipo_moneda)
                  .attr('data-anio', beneficio.anio)
                  .attr('data-mes',beneficio.mes)
                  .val(beneficio.id_beneficio)
                  //Descripcion del icono | Nombre de la acción
                  .attr('tabindex', 0)
                  .attr('data-trigger','hover')
                  .attr('data-toggle','popover')
                  .attr("data-placement" , "top")
                  .attr('data-content','IMPRIMIR')
                  //Icono
                  .append($('<i>').addClass('fa fa-fw fa-print'))
              )
          );
        }

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
          $('#textoExito').text('');
          var texto = "";
          if(data.responseJSON.id_beneficio !== undefined){
            texto += 'Hay beneficios sin ajustar.  ';
              $('#btn-validar-si').hide();
          }
          if(data.responseJSON.id_producido !== undefined){
            texto += 'Hay producidos sin cargar. ¿Desea validarlos de todos modos?';
            $('#btn-validar-si').show();
            $('#btn-validar').hide();
          }
          if(data.responseJSON.not_found !== undefined){
            texto += 'Recargue la página.  ';
            $('#btn-validar-si').hide();
          }
          $('#textoExito').text('Errores: '+ texto);
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

$('#btn-cotizacion').on('click', function(e){
  e.preventDefault();
  //limpio modal
  $('#labelCotizacion').html("");
  $('#labelCotizacion').attr("data-fecha","");
  $('#valorCotizacion').val("");
  //inicio calendario
  $('#calendarioInicioBeneficio').fullCalendar({  // assign calendar
    locale: 'es',
    
    backgroundColor: "#f00",
    eventTextColor:'yellow',

    editable: false,
    selectable: true,
    allDaySlot: false,
    selectAllow:false,

    customButtons: {
      nextCustom: {
        text: 'Siguiente',
        click: function() {
          cambioMes('next');
        }
      },
      prevCustom: {
        text: 'Anterior',
        click: function() {
          cambioMes('prev');
        }
      },
    },
    header: {
      left: 'prev,next',
      center: 'title',
      right: 'month',
    },




    events: function(start, end, timezone, callback) {
      $.ajax({
        url: 'cotizacion/obtenerCotizaciones/'+ start.format('YYYY-MM'),
        type:"GET",
        success: function(doc) {
          var events = [];
          $(doc).each(function() {
            var numero=""+$(this).attr('valor');
            events.push({
              title:"" + numero.replace(".", ","),
              start: $(this).attr('fecha')
            });
          });
          callback(events);
        }
      });
    },

    dayClick: function(date) {
      $('#labelCotizacion').html('Guardar cotización para el día '+ '<u>'  +date.format('DD/M/YYYY') + '</u>' );
      $('#labelCotizacion').attr("data-fecha",date.format('YYYY-MM-DD'));
      $('#valorCotizacion').val("");
      $('#valorCotizacion').focus();
      
    },


  });

  $('#modal-cotizacion').modal('show')

});

// guardar nueva cotizacion y recargar calendario
$('#guardarCotizacion').on('click',function(){
  fecha=$('#labelCotizacion').attr('data-fecha');
  valor= $('#valorCotizacion').val();
  formData={
    fecha: fecha,
    valor: valor,
  }
  $.ajax({
    type: 'POST',
    url: 'cotizacion/guardarCotizacion',
    data: formData,
    success: function (data) {
     $('#calendarioInicioBeneficio').fullCalendar('refetchEvents');
      //limpio modal
      $('#labelCotizacion').html("");
      $('#labelCotizacion').attr("data-fecha","");
      $('#valorCotizacion').val("");
    }
  
  
  });

});

function cambioMes(s){
  $('#calendarioInicioBeneficio').fullCalendar(s);
  $('#calendarioInicioBeneficio').fullCalendar('refetchEvents');
};
