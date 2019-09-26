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

//busqueda de reportes
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
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.estados.total,clickIndice);
        $('#cuerpoTabla tr').remove()

        for (var i = 0; i < resultados.respuesta.relevados.length; i++){
          $('#cuerpoTabla').append(generarFilaTabla(resultados.respuesta.relevados[i],resultados.respuesta.importaciones[i],resultados.estados.data[i]));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.estados.total,clickIndice);
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
            $('#cuerpoTabla #' +data +' .no-visado').hide();
            $('#cuerpoTabla #' +data +' .visado').show();
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
     console.log(id_importacion);
     if(id_importacion != 'no_importado'){
       $('#modalDetalles .modal-header').attr('style','font-family: Roboto-Black; background-color: #46b8da; color: #fff');

       $('#cuerpoTablaDetalles').remove();
       $('#tablaResultadosDetalles').append($('<tbody>').attr('id', 'cuerpoTablaDetalles'));

       $('#terminoDatos2').remove();
       $('#modalDetalles').modal('show');

      $.get("obtenerDiferencia/" + id_importacion, function(data){
        $('#modalDetalles .modal-title').text('| DETALLES DIFERENCIA ' + data.importacion[0].fecha);
        //detalles sesion
        cargarDatosSesion(data.importacion, data.sesion.sesion);
        cargarDetallesSesion(data.importacion, data.sesion.detalles);
         // console.log(data);
        //genera la tabla con las partidas importadas
        for (var i = 0; i < data.importacion.length; i++){
            if(data.sesion !== -1){
              var partida = buscarPartida(data.sesion.partidas, i+1);
            }
            $('#cuerpoTablaDetalles').append(generarFilaPartidaImportada(data.importacion[i], partida));
        }

        if(data.reporte.visado == 1){
          $('#observacion_validacion').val(data.reporte.observaciones_visado).attr('disabled','disabled');
          $('#btn-finalizarValidacion').hide();
        }
        else{
          $('#observacion_validacion').removeAttr('disabled');
          $('#btn-finalizarValidacion').show();
        }

        // if(data.reporte.sesion_abierta === 1 && data.reporte.sesion_cerrada !== 1){
        //   $('#btn-finalizarValidacion').hide();
        //   $('#msje-sesion-no-cerrada').text('Esta sesión se ha abierto pero no cerrado.');
        // }

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
function cargarDatosSesion(importaciones,sesion){
  //si no existen datos de sesión, cargo los datos desde importación y pinto de naranja
  if(sesion == undefined){
    $('#pozo_dotacion_inicial_d').val(importaciones[0].pozo_dot).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
    $('#pozo_extra_inicial_d').val(importaciones[0].pozo_extra).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
    var i = importaciones.length - 1;
    $('#pozo_dotacion_final_d').val(importaciones[i].pozo_dot).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
    $('#pozo_extra_final_d').val(importaciones[i].pozo_extra).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange');
  }
  //si hay datos de sesión, comparo esos datos con los de importación y si son distintos, pinto de rojo
  //en el caso en que la sesión este abierta, pero no cerrada, pinto de rojo diferencias y de naranja si no existe el dato
  else{
    if (importaciones[0].pozo_dot != sesion.pozo_dotacion_inicial){
      $('#pozo_dotacion_inicial_d').val(importaciones[0].pozo_dot).attr('readonly','readonly').addClass('pintar-red').removeClass('pintar-orange');
    }
    if (importaciones[0].pozo_extra != sesion.pozo_extra_inicial){
      $('#pozo_extra_inicial_d').val(importaciones[0].pozo_extra).attr('readonly','readonly').addClass('pintar-red').removeClass('pintar-orange');
    }
    var t = importaciones.length - 1;
    if(sesion.pozo_dotacion_final == null) {
      $('#pozo_dotacion_final_d').val(importaciones[0].pozo_dot).attr('readonly','readonly').addClass('pintar-orange').removeClass('pintar-red');
    }else{
      if (importaciones[t].pozo_dot != sesion.pozo_dotacion_final){
        $('#pozo_dotacion_final_d').val(importaciones[0].pozo_dot).attr('readonly','readonly').addClass('pintar-red').removeClass('pintar-orange');
      }
    }
    if(sesion.pozo_extra_final == null) {
      $('#pozo_extra_final_d').val(importaciones[0].pozo_extra).attr('readonly','readonly').addClass('pintar-orange').removeClass('pintar-red');
    }else{
      if (importaciones[t].pozo_extra != sesion.pozo_extra_final){
        $('#pozo_extra_final_d').val(importaciones[0].pozo_extra).attr('readonly','readonly').addClass('pintar-red').removeClass('pintar-orange');
      }
    }

  }
}
//funcion auxiliar para cargar los detalles de la sesion a partir de importación
function cargarDetallesSesion(importaciones,detalle){

  $('#valor_carton').val(importaciones[0].valor_carton).attr('disabled','disabled');
  $('#serie_inicial').val(importaciones[0].serieA).attr('disabled','disabled');
  $('#carton_inicial').val(importaciones[0].carton_inicio_A).attr('disabled','disabled');

  var t = importaciones.length - 1;

  //busco la primer ocurrencia con el mismo valor de carton desde el final.
  var c = 0;
  for (var i = t; i > 0; i--) {
    if(importaciones[i].valor_carton == importaciones[0].valor_carton && c == 0){
      if(importaciones[i].serieB != 0){
        $('#serie_final').val(importaciones[i].serieB).attr('disabled','disabled');
        $('#carton_final').val(importaciones[i].carton_fin_B).attr('disabled','disabled');
      }else{
        $('#serie_final').val(importaciones[i].serieA).attr('disabled','disabled');
        $('#carton_final').val(importaciones[i].carton_fin_A).attr('disabled','disabled');
      }
      c++;
    }
  }
  //busco si existe alguna ocurrencia con otro valor de carton, generando un nuevo detalle
  var detalles = [];
  var i = 0;
  importaciones.forEach(function(importacion){
    if(importacion.valor_carton != importaciones[0].valor_carton && i == 0){
      detalles.push(importacion.valor_carton);
      detalles.push(importacion.serieA);
      detalles.push(importacion.carton_inicio_A);
      i++;
    }
  });
  //si comenzó a armar un nuevo detalle, busco los valores finales
  if( detalles.length != 0){
    var c = 0;
      for (var i = t; i > 0; i--) {
        if(importaciones[i].valor_carton == detalles[0] && c == 0){
          if(importaciones[i].serieB != 0){
            detalles.push(importaciones[i].serieB);
            detalles.push(importaciones[i].carton_fin_B);
          }else{
            detalles.push(importaciones[i].serieA);
            detalles.push(importaciones[i].carton_fin_A);
          }
          c++;
        }
      }
      //llamo a la función para generar la nueva fila.
      //el valor uno indica que viene desde cargar detalles a partir de importaciones
      generarFilaDetallesSesion(detalles,1);
    }
    pintarDetalleSesion(importaciones, detalle, detalles);
}
function pintarDetalleSesion(importacion, detalle, detalles){
  //si no existen datos de detalle,  pinto de naranja
  if(detalle == undefined){
      $('#valor_carton').removeClass('pintar-red').addClass('pintar-orange');
      $('#serie_inicial').removeClass('pintar-red').addClass('pintar-orange');
      $('#carton_inicial').removeClass('pintar-red').addClass('pintar-orange');
      $('#serie_final').removeClass('pintar-red').addClass('pintar-orange');
      $('#carton_final').removeClass('pintar-red').addClass('pintar-orange');
      if(detalles.length != 0){
        $('#valor_carton_f').removeClass('pintar-red').addClass('pintar-orange');
        $('#serie_inicial_f').removeClass('pintar-red').addClass('pintar-orange');
        $('#carton_inicial_f').removeClass('pintar-red').addClass('pintar-orange');
        $('#serie_final_f').removeClass('pintar-red').addClass('pintar-orange');
        $('#carton_final_f').removeClass('pintar-red').addClass('pintar-orange');
      }
  }
  //si hay datos de detalle, comparo esos datos con los de importación y si son distintos, pinto de rojo
  //en el caso en que la sesión este abierta, pero no cerrada, pinto de rojo diferencias y de naranja si no existe el dato
  else{
    if(detalle[0].valor_carton != importacion[0].valor_carton){
        $('#valor_carton').addClass('pintar-red').removeClass('pintar-organge');
    }
    if(detalle[0].serie_inicio != importacion[0].serieA){
        $('#serie_inicial').addClass('pintar-red').removeClass('pintar-orange');
    }
    if(detalle[0].carton_inicio != importacion[0].carton_inicio_A){
        $('#carton_inicial').addClass('pintar-red').removeClass('pintar-orange');
    }

    if(detalle[0].serie_fin == null){
        $('#serie_final').addClass('pintar-orange').removeClass('pintar-red');
    }else{
      if(detalle[0].serie_fin != importacion[0].serieB){
        $('#serie_final').addClass('pintar-red').removeClass('pintar-orange');
      }
    }
    if(detalle[0].carton_fin == null){
        $('#carton_final').addClass('pintar-orange').removeClass('pintar-red');
    }else{
      if(detalle[0].carton_fin != importacion[0].carton_fin_B){
        $('#carton_final').addClass('pintar-red').removeClass('pintar-orange');
      }
    }
    if(detalles.length != 0){
      if(detalle[1].valor_carton != detalles[0]){
        $('#valor_carton_f').addClass('pintar-red').removeClass('pintar-orange');
      }
      if(detalle[1].serie_inicio != detalles[1]){
        $('#serie_inicial_f').addClass('pintar-red').removeClass('pintar-orange');
      }
      if(detalle[1].carton_inicio != detalles[3]){
        $('#carton_inicial_f').addClass('pintar-red').removeClass('pintar-orange');
      }

      if(detalle[1].serie_fin == null){
          $('#serie_final_f').addClass('pintar-orange').removeClass('pintar-red');
      }else{
        if(detalle[1].serie_fin != detalles[4]){
          $('#serie_final_f').addClass('pintar-red').removeClass('pintar-orange');
        }
      }
      if(detalle[1].carton_fin == null){
          $('#carton_final_f').addClass('pintar-orange').removeClass('pintar-red');
      }else{
        if(detalle[1].carton_fin != detalles[5]){
          $('#carton_final_f').addClass('pintar-red').removeClass('pintar-orange');
        }
      }
    }
  }
}
//genera la fila de  detalles si tiene más de uno
function generarFilaDetallesSesion(detalle, detalle_importacion = 0){
  var valor_carton;
  var serie_inicio;
  var carton_inicio;
  var serie_fin;
  var carton_fin;
  //si detalle_importacion == 0, la sesion contiene detalles.
  //sino, detalle_importacion == 1, se crean los detalles a partir de importación
  if(detalle_importacion == 0){
    valor_carton = detalle.valor_carton;
    serie_inicio = detalle.serie_inicio;
    carton_inicio = detalle.carton_inicio;
    serie_fin = detalle.serie_fin;
    carton_fin = detalle.carton_fin;
  }else{
    valor_carton = detalle[0];
    serie_inicio = detalle[1];
    carton_inicio = detalle[2];
    serie_fin = detalle[3];
    carton_fin = detalle[4];
  }
  $('#columnaDetalles')
      .append($('<div>')
          .addClass('row')
          // .addClass('terminoCierreSesion')
          .css('padding-top','15px')
          .attr('id','terminoDatos2')
          .append($('<div>')
              .addClass('col-lg-2')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','valor_carton_f')
                  .attr('type','text')
                  .attr('disabled','disabled')
                  .attr('value', valor_carton)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-2')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','serie_inicial_f')
                  .attr('type','text')
                  .attr('disabled','disabled')
                  .attr('value', serie_inicio)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-2')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','carton_inicial_f')
                  .attr('type','text')
                  .attr('disabled','disabled')
                  .attr('value', carton_inicio)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-2')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','serie_final_f')
                  .attr('type','text')
                  .attr('disabled','disabled')
                  .attr('value', serie_fin)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-2')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','carton_final_f')
                  .attr('type','text')
                  .attr('disabled','disabled')
                  .attr('value', carton_fin)
                  .addClass('form-control')
              )
          )
      )
}
//Generar fila con los datos
function generarFilaTabla(relevados, importados, estado){
  var id_importacion;
  var importado;
  if(importados === -1) {
    id_importacion = 'no_importado';
    importado = 'NO'
  }else{
    // if(estado.sesion_abierta == 1 && estado.sesion_cerrada != 1){
    //     id_importacion = 'abierta_no_cerrada';
    // }else{
    id_importacion = importados[0].id_importacion;
      // }
    importado = 'SI';
  }

  var relevamiento;
  var cerrada;
  if(relevados === -1){
    relevamiento=cerrada='NO';
  }else{
    if(relevados.detalles.length != 0) relevamiento = 'SI';
    else relevamiento = 'NO';
    if(relevados.sesion.id_usuario_fin === null) cerrada = 'NO';
    else cerrada = 'SI';
  }

  var casino;
  var fecha;
  var hora;

  if(importados != -1 ){
    if(importados[0].id_casino == 1) casino = 'Melincue';
    if(importados[0].id_casino == 2) casino = 'Santa Fe';
    if(importados[0].id_casino == 3) casino = 'Rosario';
    fecha = importados[0].fecha;
    hora = importados[0].hora_inicio;
  }else if(relevados != -1){
    if(relevados.sesion.id_casino == 1) casino = 'Melincue';
    if(relevados.sesion.id_casino == 2) casino = 'Santa Fe';
    if(relevados.sesion.id_casino == 3) casino = 'Rosario';
    fecha = relevados.sesion.fecha_inicio;
    hora = relevados.sesion.hora_inicio;
  }
  var visado;
  if(estado.visado === 1){
    visado = 'SI';
  }else{
    visado = 'NO';
  }
    var fila = $(document.createElement('tr'));
        fila.attr('id', id_importacion)
          .append($('<td>')
          .addClass('col')
              .text(fecha)
          )
          .append($('<td>')
            .addClass('col')
            .text(casino)
          )
          .append($('<td>')
            .addClass('col')
            .text(hora)
          )
          .append($('<td>')
            .addClass('col')
            .text(importado)
          )
          .append($('<td>')
            .addClass('col')
            .text(relevamiento)
          )
          .append($('<td>')
            .addClass('col')
            .text(cerrada)
          )
          .append($('<td>')
            .addClass('col')
            .text(visado)
          )
          .append($('<td>')
            .addClass('col')
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa')
                    .addClass('fa-fw')
                    .addClass('fa-check')
                  )
                .append($('<span>').text('VISAR DIFERENCIA'))
                .addClass('btn').addClass('btn-success').addClass('validar').addClass('no-visado')
                .attr('value',id_importacion)
              )
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa')
                      .addClass('fa-fw')
                      .addClass('fa-search-plus')
                    )
                  .append($('<span>').text('VER VISADO'))
                  .addClass('btn').addClass('btn-success').addClass('validar').addClass('visado')
                  .attr('value',id_importacion)
                )
            )

            if( id_importacion === 'no_importado'){
              fila.find('.validar').removeClass('btn-success').addClass('btn-danger');
            }
            if( visado === 'SI') {
              fila.find('.visado').show();
              fila.find('.no-visado').hide();
            }else{
              fila.find('.visado').hide();
              fila.find('.no-visado').show();
            }
      return fila;
}
//Generar fila con los datos de las partidas importadas
function generarFilaPartidaImportada(importado, partida = -1){
  var fila = $(document.createElement('tr'));
      fila.attr('id', importado.num_partida)
        .append($('<td>')
        .attr('id', 'num_partida')
        .addClass('col')
        .removeClass('pintar-red')
            .text(importado.num_partida)
        )
        .append($('<td>')
          .attr('id', 'hora')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.hora_inicio)
        )
        .append($('<td>')
          .attr('id', 'serieA')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.serieA)
        )
        .append($('<td>')
          .attr('id', 'carton_inicio_A')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.carton_inicio_A)
        )
        .append($('<td>')
          .attr('id', 'carton_fin_A')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.carton_fin_A)
        )
        .append($('<td>')
          .attr('id', 'serieB')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.serieB)
        )
        .append($('<td>')
          .attr('id', 'carton_inicio_B')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.carton_inicio_B)
        )
        .append($('<td>')
          .attr('id', 'carton_fin_B')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.carton_fin_B)
        )
        .append($('<td>')
          .attr('id', 'cartones_vendidos')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.cartones_vendidos)
        )
        .append($('<td>')
          .attr('id', 'valor_carton')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.valor_carton)
        )
        .append($('<td>')
          .attr('id', 'cant_bola')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.cant_bola)
        )
        .append($('<td>')
          .attr('id', 'recaudado')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.recaudado)
        )
        .append($('<td>')
          .attr('id', 'premio_linea')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.premio_linea)
        )
        .append($('<td>')
          .attr('id', 'premio_bingo')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.premio_bingo)
        )
        .append($('<td>')
          .attr('id', 'pozo_dot')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.pozo_dot)
        )
        .append($('<td>')
          .attr('id', 'pozo_extra')
          .addClass('col')
          .removeClass('pintar-red')
          .text(importado.pozo_extra)
        )
        .append($('<td>').css('text-align','center')
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
            if(partida.hora_inicio != importado.hora_inicio) {fila.find('#hora').addClass('pintar-red'); bandera++;};
            if(partida.serie_inicio != importado.serieA){ fila.find('#serieA').addClass('pintar-red');bandera++;};
            if(partida.carton_inicio_i != importado.carton_inicio_A) {fila.find('#carton_inicio_A').addClass('pintar-red'); bandera++;};
            if(partida.carton_fin_i != importado.carton_fin_A) {fila.find('#carton_fin_A').addClass('pintar-red'); bandera++;};
            if(partida.serie_fin != importado.serieB) {fila.find('#serieB').addClass('pintar-red'); bandera++;};
            if(partida.carton_inicio_f != importado.carton_inicio_B) {fila.find('#carton_inicio_B').addClass('pintar-red'); bandera++;};
            if(partida.carton_fin_f != importado.carton_fin_B) {fila.find('#carton_fin_B').addClass('pintar-red'); bandera++;};
            if(partida.cartones_vendidos != importado.cartones_vendidos) {fila.find('#cartones_vendidos').addClass('pintar-red'); bandera++;};
            if(partida.valor_carton != importado.valor_carton) {fila.find('#valor_carton').addClass('pintar-red'); bandera++;};
            if(partida.bola_bingo != importado.cant_bola) {fila.find('#cant_bola').addClass('pintar-red'); bandera++;};
            if(recaudado != importado.recaudado) {fila.find('#recaudado').addClass('pintar-red'); bandera++;};
            if(partida.premio_linea != importado.premio_linea) {fila.find('#premio_linea').addClass('pintar-red'); bandera++;};
            if(partida.premio_bingo != importado.premio_bingo) {fila.find('#premio_bingo').addClass('pintar-red'); bandera++;};
            if(partida.pozo_dot != importado.pozo_dot) {fila.find('#pozo_dot').addClass('pintar-red'); bandera++;};
            if(partida.pozo_extra != importado.pozo_extra) {fila.find('#pozo_extra').addClass('pintar-red'); bandera++;};

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

          $('.pop-exclamation').popover({
            html:true
          });
          $('.pop-check').popover({
            html:true
          });
          $('.pop-times').popover({
            html:true
          });
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
