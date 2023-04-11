$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Importaciones Bingo');;
  $('#dtpBuscadorFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm',
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
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

//busqueda de importaciones
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
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultadosPremio .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
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
      url: 'buscarRelevamiento',
      data: formData,
      dataType: 'json',
      success: function(resultados){
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTabla tr').remove();
        for (var i = 0; i < resultados.data.length; i++){
          $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i]));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function(data){
        console.log('Error:', data);
      }
    });
});

//Mostrar modal para importar
$('#btn-nuevo').click(function(e){
  $('#mensajeExito').hide();
  $('#modalImportacion #rowArchivo').show();
  $('#modalImportacion #mensajeError').hide();
  $('#modalImportacion #mensajeInvalido').hide();
  $('#modalImportacion #mensajeInformacion').hide();
  $('#modalImportacion #casinoSelect').hide();
  $('#modalImportacion #iconoCarga').hide();
  e.preventDefault();
  $('.modal-title').text('| IMPORTAR RELEVAMIENTO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
  habilitarInput();
  $('#modalImportacion').modal('show');
  $('#guarda_igual').attr('value', 0);
  $('#btn-guardar').hide();
});

function habilitarInput(){
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacion #archivo')[0].files[0] = null;
  $('#modalImportacion #archivo').attr('data-borrado','false');
  $("#modalImportacion #archivo").fileinput('destroy').fileinput({
      language: 'es',
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: false,
      preferIconicPreview: true,
      previewFileIconSettings: {
        'csv': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>',
        'txt': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>'
      },
      allowedFileExtensions: ['csv','txt'],
      minFileSize: null, //Permitir archivos vacios.
  });
}

function procesarDatos(e) {
  var csv = e.target.result;

  var allTextLines = csv.split('\n'); //Se obtienen todas las filas del archivo
  var data = allTextLines[0].split(';'); //se obtienen todas las columnas de la fila [0]

  $('#i_eliminar').attr('value', obtenerFecha(data));
  $('#modalImportacion #casinoSelect').show();  //selecciona el casino
}

//Evento de la librería input, en caso de error
$('#modalImportacion #archivo').on('fileerror', function(event, data, msg) {
   $('#modalImportacion #rowFecha').hide();
   $('#modalImportacion #mensajeInvalido').show();
   $('#modalImportacion #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardar').hide();
});

//Evento de la librería input, limpia si había archivo cargado
$('#modalImportacion #archivo').on('fileclear', function(event) {
    $('#modalImportacion #archivo').attr('data-borrado','true');
    $('#modalImportacion #archivo')[0].files[0] = null;
    $('#modalImportacion #mensajeInvalido').hide();
    $('#modalImportacion #rowFecha').hide();
    //Ocultar botón SUBIR
    $('#btn-guardar').hide();
});

//Evento de la librería input, si hay archivo seleccionado
$('#modalImportacion #archivo').on('fileselect', function(event) {
    var reader = new FileReader();
    reader.readAsText($('#modalImportacion #archivo')[0].files[0]);
    reader.onload = procesarDatos;

    $('#btn-guardar').show();
});

//envio de datos a servidor para guaradar importación
$('#btn-guardar').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });
    e.preventDefault();

    var id_casino = $('#id_casino').val();
    var formData = new FormData();

    formData.append('id_casino', id_casino);
    formData.append('guarda_igual', $('#guarda_igual').val());
    formData.append('motivo', $('#motivo-reimportacion').val());

    console.log($('#guarda_igual').val());

    //Si subió archivo lo guarda
    if($('#modalImportacion #archivo').attr('data-borrado') == 'false' && $('#modalImportacion #archivo')[0].files[0] != null){
      formData.append('archivo' , $('#modalImportacion #archivo')[0].files[0]);
    }

    var url = 'guardarImportacion';

    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        processData: false,
        contentType:false,
        // cache:false,
        dataType: 'json',
        beforeSend: function(data){
          console.log('Empezó');
          $('#modalImportacion').find('.modal-footer').children().hide();
          $('#modalImportacion').find('.modal-body').children().hide();
          $('#modalImportacion').find('.modal-body').children('#iconoCarga').show();
        },
        complete: function(data){
          console.log('Terminó');
        },
        success: function (data) {
          $('#modalImportacion').modal('hide');
          $('#mensajeExito P').text('El relevamiento de bingo fue IMPORTADO exitosamente.');
          $('#mensajeExito').show();
          $('#modalImportacionCargada').modal('hide');
          $('#btn-buscar').click();
        },
        error: function (data) {
          var response = data.responseJSON.errors;

          console.log(response);
          //Mostrar: mensajeError
          $('#modalImportacion #mensajeError').show();
          //Ocultar: rowArchivo, mensajes, iconoCarga
          $('#modalImportacion #rowArchivo').hide();
          $('#modalImportacion #mensajeInvalido').hide();
          $('#modalImportacion #mensajeInformacion').hide();
          $('#modalImportacion #iconoCarga').hide();
          console.log(response.motivo);

          if(typeof response.motivo !== 'undefined'){
              mostrarErrorValidacion($('#motivo-reimportacion'),'El campo no puede estar en blanco.' ,true);
            }

          if(response.archivo_valido !== 'undefined'){
            $('#msjeError').text('El archivo no corresponde al casino seleccionado.')
          }

          if(response.importacion_cargada != null){
            $('#frmMotivos').trigger('reset');
            $('#modalImportacionCargada').modal('show');
          }

          // $('#modalImportacion').modal('hide');
          console.log('ERROR!');
          console.log(data);
        }
    });
});

//Si la sesión ya existe, guardo igual
$('#btn-guardarIgual').click(function (e) {
  $('#guarda_igual').attr('value', 1);
  $('#btn-guardar').trigger('click');
});

//si falla la conexión, botón para reintentar
$('#btn-reintentar').click(function(e) {
  //Mostrar: rowArchivo
  $('#modalImportacion #rowArchivo').show();
  //Ocultar: mensajes, iconoCarga
  $('#modalImportacion #mensajeError').hide();
  $('#modalImportacion #mensajeInvalido').hide();
  $('#modalImportacion #iconoCarga').hide();
  habilitarInput();

  $('#modalImportacion').find('.modal-footer').children().show();
});

//Modal de eliminar una importacion completa
$(document).on('click','.eliminar-importacion',function(){
    var id = $(this).val();
    console.log(id);
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
    $('#btn-eliminar').val(id);
    $('#modalEliminar').modal('show');
    $('#mensajeEliminar').text('¿Seguro que desea eliminar la importación del día "' + $(this).parent().parent().find('td:first').text()+'"?');

});

//Mostral modal con detalles de importados
$(document).on('click' , '.detalles-importacion' , function() {
      var id = $(this).val();
    $('#modalDetallesRel .modal-header').attr('style','font-family: Roboto-Black; background-color: #46b8da; color: #fff');

     $('#cuerpoTablaRel').remove();
     $('#tablaResultadosRel').append($('<tbody>').attr('id', 'cuerpoTablaRel'));

    $.get("obtenerImportacionCompleta/" + id, function(data){
        console.log(data);
        $('#modalDetallesRel .modal-title').text('| DETALLES IMPORTACIÓN ' + data[0].fecha);
        $('#modalDetallesRel').modal('show');

        //genera la tabla con las jugadas importadas
        for (var i = 0; i < data.length; i++){
          $('#cuerpoTablaRel').append(generarFilaTablaDetalles(data[i]));
        }

      });

})

//Elimina una importacion
$('#btn-eliminar').click(function (e) {
    var id = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "eliminarImportacion/" + id,
        success: function (data) {

          //Remueve de la tabla
          $('#cuerpoTabla #'+ id).remove();
          $("#tablaResultados").trigger("update");

          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

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

//Generar fila con los datos de las importaciones
function generarFilaTabla(importacion){
  var fila = $(document.createElement('tr'));
      fila.attr('id', importacion.id_importacion)
        .append($('<td>')
        .addClass('col')
            .text(importacion.fecha)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.codigo)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.nombre)
        )
        .append($('<td>')
          .addClass('col')
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                )
                .append($('<span>').text(' MODIFICAR'))
                .addClass('btn').addClass('btn-warning').addClass('btn-detalle').addClass('detalles-importacion')
                .attr('value',importacion.id_importacion)
            )
            .append($('<span>').text(' '))
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa')
                    .addClass('fa-trash-alt')
                )
                .append($('<span>').text(' ELIMINAR'))
                .addClass('btn').addClass('btn-danger').addClass('btn-borrar').addClass('eliminar-importacion')
                .attr('value',importacion.id_importacion)
            )
        )

        return fila;
}
//Generar fila con los datos de los relevamientos
function generarFilaTablaDetalles(importacion){
  var fila = $(document.createElement('tr'));
      fila.attr('id', importacion.id_importacion)
        // .append($('<td>')
        // .addClass('col')
        //     .text(importacion.fecha)
        // )
        .append($('<td>')
          .addClass('col')
          .text(importacion.num_partida)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.hora_inicio)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.serieA)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.carton_inicio_A)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.carton_fin_A)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.serieB)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.carton_inicio_B)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.carton_fin_B)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.cartones_vendidos)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.valor_carton)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.cant_bola)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.recaudado)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.premio_linea)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.premio_bingo)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.pozo_dot)
        )
        .append($('<td>')
          .addClass('col')
          .text(importacion.pozo_extra)
        )
        return fila;
}
//obtener la fecha del archivo
function obtenerFecha(data){
  var fecha;
  data.forEach(function (fila){
    var i = fila.indexOf('/');
    if(i !== -1 ){
      fecha = fila.substring(i-2,11);
      fecha = fecha.replace(new RegExp('/', 'g'), '');
    }
  });
 return fecha;
}
