$(document).ready(function(){

  $('#barraMenu').attr('aria-expanded','true');
  // $('#maquinas').removeClass();
  // $('#maquinas').addClass('subMenu1 collapse in');
  $('#bingoMenu').removeClass();
  $('#bingoMenu').addClass('subMenu2 collapse in');

  $('#bingoMenu').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Reportes de Diferencia');
  // $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcReporteEstadoDiferenciaBingo').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcReporteEstadoDiferenciaBingo').addClass('opcionesSeleccionado');

  //datepicker fecha busqueda
  $('#dtpBuscadorFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
    endDate: '+0d'
  });
  //busca automaticamente
  $('#btn-buscar').trigger('click');

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

//Opacidad del modal al minimizar
$('#btn-minimizarMaquinas').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
    }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//si presiono enter y el modal esta abierto se manda el formulario
$(document).on("keypress" , function(e){
  if(e.which == 13 && $('#modalFormula').is(':visible')) {
    e.preventDefault();
    $('#btn-guardar').click();
  }
})
//enter en buscador
$('contenedorFiltros input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#').click();
  }
})

$('#columna input').on('focusout' , function(){
  if ($(this).val() == ''){
    mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
  }
});

$('#columna input').focusin(function(){
  $(this).removeClass('alerta');

});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| FÓRMULAS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

  $('#modalAyuda').modal('show');

});

//búsqueda de reportes
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultadosPremio .activa').attr('value'),orden: $('#tablaResultadosPremio .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultadosPremios th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  //datos para enviar
  var formData = {
    fecha: $('#buscadorFecha').val(),
    casino: $('#buscadorCasino').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: 'GET',
      url: 'buscarReportesDiferencia',
      data: formData,
      dataType: 'json',
      success: function(resultados){
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTabla tr').remove()

        for (const i  in resultados.data){
          $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i]));
        }

        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function(data){
        console.log('Error:', data);
      }
    });
});

//envia los datos para validar (obsercaciones+cambio de estado)
$('#btn-finalizarValidacion').click(function(e){
  e.preventDefault();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });
    //datos para enviar
    var formData = {
      id_importacion: $('#id_importacion').val(),
      observacion: $('#observacion_validacion').val()
    }

    $.ajax({
        type: "POST",
        url: 'guardarReporteDiferencia',
        data: formData,
        dataType: 'json',
        success: function (data) {
            console.log(data);

            $('#mensajeExito p').text('Sesión VISADA existosamente.');
            $('#mensajeExito div').css('background-color','#4DB6AC');
            $('#cuerpoTabla #' + data +' .no-visado').hide();
            $('#cuerpoTabla #' + data +' .visado').show();
            $('#modalDetalles').modal('hide');
            //Mostrar éxito
            $('#mensajeExito').show();
        },
        error: function (data) {
        }

        });


    });
//Mostral modal con detalles y relevamientos de la sesión
$(document).on('click' , '.validar' , function() {

     $('#id_importacion').val($(this).val());
     var id_importacion = $('#id_importacion').val();
     $('#frmDetalles').trigger("reset");
     $('#msje-sesion-no-cerrada').text('');
     //si la sesión tiene archivo importado, muestra el modal con los datos
     //sino, muestra mensaje de error
     if(id_importacion != 'no_importado'){
       $('#modalDetalles .modal-header').attr('style','font-family: Roboto-Black; background-color: #46b8da; color: #fff');

       $('#cuerpoTablaDetalles').remove();
       $('#tablaResultadosDetalles').append($('<tbody>').attr('id', 'cuerpoTablaDetalles'));

       $('#terminoDatos2').remove();
       $('#columnaDetalles').append($('<div>').attr('id', 'terminoDatos2'));
       $('#modalDetalles').modal('show');

      $.get("obtenerDiferencia/" + id_importacion, function(data){
        $('#modalDetalles .modal-title').text('| DETALLES DIFERENCIA ' + data.importacion[0].fecha);
        //detalles sesion
        cargarDatosSesion(data.importacion, data.sesion.sesion, data.pozoDotInicial);
        cargarDetallesSesion(data.importacion, data.sesion.detalles);
         // console.log(data);
        //genera la tabla con las partidas importadas
        for (var i = 0; i < data.importacion.length; i++){
            if(data.sesion !== -1){
              var partida = buscarPartida(data.sesion.partidas, i+1);
            }
            $('#cuerpoTablaDetalles').append(generarFilaPartidaImportada(data.importacion[i], partida));
        }

        //mostrar pops iconos
        $('.pop-exclamation').popover({
          html:true
        });
        $('.pop-check').popover({
          html:true
        });
        $('.pop-times').popover({
          html:true
        });
        //mostrar pops diferencias
        $('.pop-diferencia').popover({
          html:true
        });

        if(data.reporte.visado == 1){
          $('#observacion_validacion').val(data.reporte.observaciones_visado).attr('disabled','disabled');
          $('#btn-finalizarValidacion').hide();
        }
        else{
          $('#observacion_validacion').removeAttr('disabled');
          $('#btn-finalizarValidacion').show();
        }

      });
    }else{
      mensajeSesionNoImportada();
    }

})

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
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
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}
//función auxiliar cargar datos de la sesión a partir de importación
function cargarDatosSesion(importaciones,sesion, pozoDotInicial){
  //si no existen datos de sesión, cargo los datos desde importación y pinto de naranja
  if(sesion == undefined){
    $('#pozo_dotacion_inicial_d').val(pozoDotInicial).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
    $('#pozo_extra_inicial_d').val(importaciones[0].pozo_extra).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
    var i = importaciones.length - 1;
    $('#pozo_dotacion_final_d').val(importaciones[i].pozo_dot).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
    $('#pozo_extra_final_d').val(importaciones[i].pozo_extra).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
  }
  //si hay datos de sesión, comparo esos datos con los de importación y si son distintos, pinto de rojo o de naranja si no existe comparación
  else{

    //comparo datos iniciales de pozo dot y pozo extra, si llego hasta acá, existen. Sólo comparo y pinto de rojo si son != o dejo sin pintar si son ==
    if (pozoDotInicial != sesion.pozo_dotacion_inicial){
      $('#pozo_dotacion_inicial_d').val(pozoDotInicial).attr('readonly','readonly').addClass('pintar-red').removeClass('pintar-orange')
        .attr("data-content", sesion.pozo_dotacion_inicial)
        .attr("data-placement" , "top")
        .attr("rel","popover")
        .attr("data-trigger" , "hover")
        .attr('title','VALOR RELEVADO')
        .addClass('pop-pozo-dot-inicial-d');

        //popover con datos de diferencia
        $('.pop-pozo-dot-inicial-d').popover({
          html:true
        });

    }else{
        $('#pozo_dotacion_inicial_d').val(pozoDotInicial).attr('readonly','readonly').removeClass('pintar-red').removeClass('pintar-orange');
        $('.pop-pozo-dot-inicial-d').popover('disable');
    }
    if (importaciones[0].pozo_extra != sesion.pozo_extra_inicial){
      $('#pozo_extra_inicial_d').val(importaciones[0].pozo_extra).attr('readonly','readonly').addClass('pintar-red').removeClass('pintar-orange')
      .attr("data-content", sesion.pozo_extra_inicial)
      .attr("data-placement" , "top")
      .attr("rel","popover")
      .attr("data-trigger" , "hover")
      .attr('title','VALOR RELEVADO')
      .addClass('pop-pozo-extra-inicial-d');
      //popover con datos de diferencia
      $('.pop-pozo-extra-inicial-d').popover({
        html:true
      });
    }else{
      $('#pozo_extra_inicial_d').val(importaciones[0].pozo_extra).attr('readonly','readonly').removeClass('pintar-red').removeClass('pintar-orange');
          $('.pop-pozo-extra-inicial-d').popover('disable');
    }

    //me posiciono en la última ocurrencia de importaciones
    var t = importaciones.length - 1;

    //si la sesión está cerrada, tengo datos para comparar. Pinto de rojo si son != o dejo sin pintar si son ==
    if(sesion.id_estado == 2){
      if (importaciones[t].pozo_dot != sesion.pozo_dotacion_final){
        $('#pozo_dotacion_final_d').val(importaciones[t].pozo_dot).attr('readonly','readonly').addClass('pintar-red').removeClass('pintar-orange')
        .attr("data-content", sesion.pozo_dotacion_final)
        .attr("data-placement" , "top")
        .attr("rel","popover")
        .attr("data-trigger" , "hover")
        .attr('title','VALOR RELEVADO')
        .addClass('pop-pozo-dot-final-d');

        //popover con datos de diferencia
        $('.pop-pozo-dot-final-d').popover({
          html:true
        });
      }else{
        $('#pozo_dotacion_final_d').val(importaciones[t].pozo_dot).attr('readonly','readonly').removeClass('pintar-red').removeClass('pintar-orange');
            $('.pop-pozo-dot-final-d').popover('disable');
      }

      if (importaciones[t].pozo_extra != sesion.pozo_extra_final){
        $('#pozo_extra_final_d').val(importaciones[t].pozo_extra).attr('readonly','readonly').addClass('pintar-red').removeClass('pintar-orange')
        .attr("data-content", sesion.pozo_extra_final)
        .attr("data-placement" , "top")
        .attr("rel","popover")
        .attr("data-trigger" , "hover")
        .attr('title','VALOR RELEVADO')
        .addClass('pop-pozo-dot-extra-final-d');

        //popover con datos de diferencia
        $('.pop-pozo-dot-extra-final-d').popover({
          html:true
        });
      }else{
        $('#pozo_extra_final_d').val(importaciones[t].pozo_extra).attr('readonly','readonly').removeClass('pintar-red').removeClass('pintar-orange');
            $('.pop-pozo-dot-extra-final-d').popover('disable');
      }
    }else{  //si la sesión no está cerrada, no tengo datos para comparar.Pinto de naranja
      $('#pozo_dotacion_final_d').val(importaciones[0].pozo_dot).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
      $('#pozo_extra_final_d').val(importaciones[0].pozo_extra).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
      $('.pop-pozo-dot-final-d').popover('disable');
      $('.pop-pozo-dot-extra-final-d').popover('disable');
    }


  }


}
//funcion auxiliar para cargar los detalles de la sesion a partir de importación
function cargarDetallesSesion(importaciones,detalle){
  //busco pcurrencias con distinto vaor de cartón, generando un nuevo detalle
  var detalles = [];  //variable para guardar los detalles con distinto valor de cartón
  importaciones.forEach(function(importacion){ //recorro las importaciones
      //si ya tengo cargado detalles, entro al if
      if(detalles.length != 0){
          var existe = 0; //variable auxiliar para saber si existe un valor de cartón con el mismo monto
          //recorro los detalles cargados, si existe un valor de carton con igual monto, asigno 1 a existe
          detalles.forEach(function(det){
            if(det.valor_carton === importacion.valor_carton) existe = 1;
          });
          //si no existe un detalle con el mismo valor de carton, entro al if
          if(existe === 0){
            //guardo los datos en una variable auxiliar
            var datos = {
              valor_carton: importacion.valor_carton,
              serie_inicio: importacion.serieA,
              carton_inicio: importacion.carton_inicio_A,
              serie_fin: null,
              carton_fin: null,
            }
            //meto los datos en el arreglo de detalles
            detalles.push(datos);
          }
      }else if(detalles.length == 0){ //si todavía no tengo detalles agregados, entro al if
        //guardo los datos en una variable auxiliar
        var datos = {
          valor_carton: importacion.valor_carton,
          serie_inicio: importacion.serieA,
          carton_inicio: importacion.carton_inicio_A,
          serie_fin: null,
          carton_fin: null,
        }
        //meto los datos en el arreglo de detalles
        detalles.push(datos);
      }
    // }
  });

  var t = importaciones.length - 1; //cantidad de importaciones para recorrer;

  if( detalles.length != 0){ //si comenzó a armar un nuevo detalle, busco los valores finales
        detalles.forEach(function(linea){ //por cada linea de detalles
          var c = 0; //varaible de bandera para cargar sólo la primer ocurrencia
          for (var i = t; i > 0; i--) { //recorro las importaciones desde el final
            if(importaciones[i].valor_carton == linea.valor_carton && c == 0){ //si encuentro una importación con el mismo valor de carton y es la primera, ingreso al if
              if(importaciones[i].serieB != 0){ //si serieB es !=0 quiere decir que existe segunda serie, entro al if, guardo
                linea.serie_fin = importaciones[i].serieB;
                linea.carton_fin = importaciones[i].carton_fin_B;
              }else{ //no tengo segunda serie, guardo los datos de la primera
                linea.serie_fin = importaciones[i].serieA;
                linea.carton_fin = importaciones[i].carton_fin_A;
              }
              c++;
            }
          }
        });
      }

  //llamo a la función para generar la nueva fila.
  for (var i = 0; i < detalles.length; i++){
    $('#terminoDatos2').append(generarFilaDetallesSesion(detalles[i],detalle));
  }
  //mensaje de aviso si no coincide la cantidad de detalles
  if(detalle == undefined ||  detalles.length != detalle.length){
    $('#terminoDatos2').append($('<p>').css('color' ,'red')
        .text('*La cantidad de detalles relevados no coincide con los detalles de importados.')
    );}
}

//genera la fila de  detalles
function generarFilaDetallesSesion(detalle, detalles_relevado){
  var fila =
   $(document.createElement('div'))
          .addClass('row')
          .css('padding-top' ,'15px');
          //carga de fila de detalles
          appendFilaDetalleSesion(fila, detalle.valor_carton, 'valor_carton_f');
          appendFilaDetalleSesion(fila, detalle.serie_inicio, 'serie_inicial_f');
          appendFilaDetalleSesion(fila, detalle.carton_inicio, 'carton_inicial_f');
          appendFilaDetalleSesion(fila, detalle.serie_fin, 'serie_final_f');
          appendFilaDetalleSesion(fila, detalle.carton_fin, 'carton_final_f');

          //si no existe dato para comparar, pinto de naranja
          if(detalles_relevado == undefined){
            fila.find('#valor_carton_f').removeClass('pintar-red').addClass('pintar-orange');
            fila.find('#serie_inicial_f').removeClass('pintar-red').addClass('pintar-orange');
            fila.find('#carton_inicial_f').removeClass('pintar-red').addClass('pintar-orange');
            fila.find('#serie_final_f').removeClass('pintar-red').addClass('pintar-orange');
            fila.find('#carton_final_f').removeClass('pintar-red').addClass('pintar-orange');
          }else{
          //si existen datos para comparar, comparo
          //busco el detalle con el mismo valor de cartón para comparar, suponiendo que agregan más de los que existen y en distinto orden.
            detalles_relevado.forEach(function(detalle_relevado){ //por cada detalle
                //si tienen el mismo valor de cartón, comparo los datos, si son != pinto de rojo
              if(detalle_relevado.valor_carton == detalle.valor_carton){
                  if(detalle_relevado.serie_inicio != detalle.serie_inicio){
                    attrComparacion(fila, '#serie_inicial_f', detalle_relevado.serie_inicio);
                  }
                  if(detalle_relevado.carton_inicio != detalle.carton_inicio){
                    attrComparacion(fila, '#carton_inicial_f', detalle_relevado.carton_inicio);
                  }
                  //si la sesión se enceuntra cerrada, no tengo datos de fin, pinto de naranja
                  if(detalle_relevado.serie_fin == null){
                    fila.find('#serie_final_f').removeClass('pintar-red').addClass('pintar-orange');
                    fila.find('#carton_final_f').removeClass('pintar-red').addClass('pintar-orange');
                  }else{ //tengo datos de fin, comparo y pinto de rojo si son !=
                    if(detalle_relevado.serie_fin != detalle.serie_fin){
                      attrComparacion(fila, '#serie_final_f', detalle_relevado.serie_fin);
                    }
                    if(detalle_relevado.carton_fin != detalle.carton_fin){
                      attrComparacion(fila, '#carton_final_f', detalle_relevado.carton_fin);
                    }
                  }
              }
            });
          }

      return fila;
}
//Generar fila con los datos
function generarFilaTabla(data){
  const fecha = data.fecha_sesion;
  const casino = data.casino;
  const hora  = data.importacion? data.imp_hora_inicio : data.ses_hora_inicio;
  const importado = data.importacion? 'SI' : 'NO';
  const relevamiento = data.relevamiento? 'SI' : 'NO';
  const cerrada = data.sesion_cerrada? 'SI': 'NO';
  const visado = data.visado? 'SI' : 'NO';
  
  return $('<tr>').attr('id',data.importacion ?? 'no_importado')
  .append($('<td>').addClass('col').text(fecha))
  .append($('<td>').addClass('col').text(casino))
  .append($('<td>').addClass('col').text(hora ?? '-'))
  .append($('<td>').addClass('col').text(importado))
  .append($('<td>').addClass('col').text(relevamiento))
  .append($('<td>').addClass('col').text(cerrada))
  .append($('<td>').addClass('col').text(visado))
  .append($('<td>').addClass('col')
    .append(
      $('<button>').addClass('btn validar no-visado')
      .addClass(data.importacion? 'btn-success' : 'btn-danger')
      .toggle(!!data.visado)
      .append($('<i>').addClass('fa fa-fw fa-check'))
      .attr('title','VISAR DIFERENCIA').val(data.importacion ?? 'no_importado')
    )
    .append(
      $('<button>').addClass('btn validar visado btn-success')
      .toggle(!data.visado)
      .append($('<i>').addClass('fa fa-fw fa-search-plus'))
      .attr('title','VER VISADO').val(data.importacion ?? 'no_importado')
    )
  );
}
//Generar fila con los datos de las partidas importadas
function generarFilaPartidaImportada(importado, partida = -1){
  var fila = $(document.createElement('tr'));
    fila.attr('id', importado.num_partida);
    appendFila(fila, 'num_partida', importado.num_partida);
    appendFila(fila, 'hora', importado.hora_inicio);
    appendFila(fila, 'serieA', importado.serieA);
    appendFila(fila, 'carton_inicio_A', importado.carton_inicio_A);
    appendFila(fila, 'carton_fin_A', importado.carton_fin_A);
    appendFila(fila, 'serieB', importado.serieB);
    appendFila(fila, 'carton_inicio_B', importado.carton_inicio_B);
    appendFila(fila, 'carton_fin_B', importado.carton_fin_B);
    appendFila(fila, 'cartones_vendidos', importado.cartones_vendidos);
    appendFila(fila, 'valor_carton', importado.valor_carton);
    appendFila(fila, 'cant_bola', importado.cant_bola);
    appendFila(fila, 'recaudado', importado.recaudado);
    appendFila(fila, 'premio_linea', importado.premio_linea);
    appendFila(fila, 'premio_bingo', importado.premio_bingo);
    appendFila(fila, 'pozo_dot', importado.pozo_dot);
    appendFila(fila, 'pozo_extra', importado.pozo_extra);

        fila.append($('<td>').css('text-align','center')
          .append($('<a>')
              .addClass('pop-exclamation')
              .attr("data-content", 'Partida no relevada.')
              .attr("data-placement" , "top")
              .attr("rel","popover")
              .attr("data-trigger" , "hover")
              .append($('<i>').addClass('pop').addClass('fa').addClass('fa-exclamation')
                              .css('color','#FFA726'))
          )
          .append($('<a>')
              .addClass('pop-check')
              .attr("data-content", 'Coinciden datos relevados con importados.')
              .attr("data-placement" , "top")
              .attr("rel","popover")
              .attr("data-trigger" , "hover")
              .append($('<i>').addClass('pop').addClass('fa').addClass('fa-check')
                              .css('color','rgb(102, 187, 106)'))
          )
          .append($('<a>')
              .addClass('pop-times')
              .attr("data-content", 'No coinciden datos relevados con importados.')
              .attr("data-placement" , "top")
              .attr("rel","popover")
              .attr("data-trigger" , "hover")
              .append($('<i>').addClass('pop').addClass('fa').addClass('fa-times')
                              .css('color','rgb(239, 83, 80)'))
          )
        )

          //si existe partida para comparar con la importacion, comparo
          if(partida !== -1){
            var bandera = 0;
            var recaudado = (partida.cartones_vendidos*partida.valor_carton);

            if(partida.hora_inicio != importado.hora_inicio) {
              attrComparacion(fila, '#hora', partida.hora_inicio)
              bandera++;};
            if(partida.serie_inicio != importado.serieA){
              attrComparacion(fila, '#serieA', partida.serie_inicio)
              bandera++;};
            if(partida.carton_inicio_i != importado.carton_inicio_A) {
              attrComparacion(fila, '#carton_inicio_A', partida.carton_inicio_i)
              bandera++;};
            if(partida.carton_fin_i != importado.carton_fin_A) {
              attrComparacion(fila, '#carton_fin_A', partida.carton_fin_i)
              bandera++;};
            if(partida.serie_fin != importado.serieB) {
              attrComparacion(fila, '#serieB', partida.serie_fin)
              bandera++;};
            if(partida.carton_inicio_f != importado.carton_inicio_B) {
              attrComparacion(fila, '#carton_inicio_B', partida.carton_inicio_f)
              bandera++;};
            if(partida.carton_fin_f != importado.carton_fin_B) {
              attrComparacion(fila, '#carton_fin_B', partida.carton_fin_f)
              bandera++;};
            if(partida.cartones_vendidos != importado.cartones_vendidos) {
              attrComparacion(fila, '#cartones_vendidos', partida.cartones_vendidos)
              bandera++;};
            if(partida.valor_carton != importado.valor_carton) {
              attrComparacion(fila, '#valor_carton', partida.valor_carton)
              bandera++;};
            if(partida.bola_bingo != importado.cant_bola) {
              attrComparacion(fila, '#cant_bola', partida.bola_bingo)
              bandera++;};
            if(recaudado != importado.recaudado) {
              attrComparacion(fila, '#recaudado', recaudado)
              bandera++;};
            if(partida.premio_linea != importado.premio_linea) {
              attrComparacion(fila, '#premio_linea', partida.premio_linea)
              bandera++;};
            if(partida.premio_bingo != importado.premio_bingo) {
              attrComparacion(fila, '#premio_bingo', partida.premio_bingo)
              bandera++;};
            if(partida.pozo_dot != importado.pozo_dot) {
              attrComparacion(fila, '#pozo_dot', partida.pozo_dot)
              bandera++;};
            if(partida.pozo_extra != importado.pozo_extra) {
              attrComparacion(fila, '#pozo_extra', partida.pozo_extra)
              bandera++;};

            fila.find('.pop-exclamation').hide();

            if(bandera == 0){
              fila.find('.pop-times').hide();
            }else{
              fila.find('.pop-check').hide();
            }

          }else{
              fila.find('.pop-check').hide();
              fila.find('.pop-times').hide();
          }

        return fila;
}
//busca si existe partida relevada para comparar con importada
function buscarPartida(partidas, num_partida){
  var r;
  partidas.forEach(function(partida){
    if(partida[0].num_partida === num_partida){
      r = partida[0];
      return;
    }
  });
  return r;
}
//Mensaje de error cuando la sesión no se encuentra importada
function mensajeSesionNoImportada(){
  $('.modal-title-error').text('ERROR: SESIÓN NO IMPORTADA');
  $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
  $('#modalError').modal('show');
 $('#errorNoImportada').text('No se puede visar ésta sesión por no encontrarse importada.');
}
//attr atributos de comparación
function attrComparacion(fila, lugar, valor){
  fila.find(lugar).addClass('pintar-red')
  .attr("data-content", valor)
  .attr("data-placement" , "top")
  .attr("rel","popover")
  .attr('title','VALOR RELEVADO')
  .attr("data-trigger" , "hover")
  .addClass('pop-diferencia');
}
//append y atrr atributos fila importaciones
function appendFila(fila, nombre_id, valor){
  fila.append($('<td>')
    .append($('<p>')
  .attr('id', nombre_id)
  .addClass('col')
  .removeClass('pintar-red')
      .text(valor)
  ))
}
//append fila detalle sesión
function appendFilaDetalleSesion(fila, valor, id){
  fila.append($('<div>')
      .addClass('col-lg-2')
      .append($('<input>')
          .attr('placeholder' , '')
          .attr('id',id)
          .attr('type','text')
          .attr('disabled','disabled')
          .attr('value', valor)
          .addClass('form-control')
      )
  )
}
