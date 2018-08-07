$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionarMTM').removeClass();
  $('#gestionarMTM').addClass('subMenu2 collapse in');

  $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('GLI Hardware');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcGliHard').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcGliHard').addClass('opcionesSeleccionado');

  $('#certificados').show();


  // $('#alertaArchivo').hide();
  // $('#agregarExpediente').hide();
  // $('#cancelarExpediente').hide();

  // $("#tablaGliHard").tablesorter({
  //     headers: {
  //       3: {sorter:false}
  //     }
  // });

  $('#buscarCertificado').trigger('click');

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

/***************
TODOS LOS EVENTOS DEL BUSCADOR DE EXPEDIENTE
****************/
function agregarFilaExpediente(expediente) {
  var fila = $('<tr>').attr('id', expediente.id_expediente);

  fila.append($('<td>').addClass('col-xs-3')
                       .text(expediente.nro_exp_org + '-' + expediente.nro_exp_interno + '-' + expediente.nro_exp_control)
             );
  fila.append($('<td>').addClass('col-xs-3')
                       .append($('<button>').addClass('btn btn-danger borrarExpediente')
                                            .append($('<i>').addClass('fa fa-fw fa-trash-alt'))
                              )
             );

  $('#tablaExpedientesHard tbody').append(fila);
}

$('#btn-agregarExpediente').click(function(e){
    var id_expediente = $('#inputExpediente').obtenerElementoSeleccionado();

    if (id_expediente != 0) {
      $.get('expedientes/obtenerExpediente/' + id_expediente , function(data){
        //Agregar la fila a la tabla
        agregarFilaExpediente(data.expediente);
        //Limpiar el input para seguir buscando expedientes
        $('#inputExpediente').setearElementoSeleccionado(0, '');
      });
    }
});

//Borrar expediente de la tabla
$(document).on('click','.borrarExpediente',function(){
  $(this).parent().parent().remove();
});

function limpiarModalGLIHard() {
  $('#nroCertificado').val('');
  // $('#inputExpediente').borrarDataList();
  $('#tablaExpedientesHard tbody tr').remove();
}

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| GLI HARDWARE');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo GLI Soft
$('#btn-nuevo').click(function(e){
    e.preventDefault();

    limpiarModalGLIHard();

    //Modificar los colores del modal
      $('.modal-title').text('| NUEVO GLI HARDWARE');
      $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
      $('#btn-guardar').removeClass();
      $('#btn-guardar').addClass('btn').addClass('btn-successAceptar');
      $('#btn-guardar').text('ACEPTAR');
      $('#btn-guardar').show();
      // $('.btn-default').text('CANCELAR');

    $('#inputExpediente').prop('readonly',false);
    $('#nroCertificado').prop('readonly',false)

    $('#btn-guardar').val("nuevo");
    $('#frmGLI').trigger('reset');
    $('#listaExpedientes').empty();
    //$('#alertaNombre').hide(); Esconder los alertas!


    $('#inputExpediente').generarDataList("expedientes/buscarExpedientePorNumero",'resultados','id_expediente','concatenacion',2,true);
    $('#inputExpediente').setearElementoSeleccionado(0,"");

    //Inicializa el fileinput para cargar los PDF
    $("#cargaArchivo").fileinput('destroy').fileinput({
        language: 'es',
        showRemove: false,
        showUpload: false,
        showCaption: false,
        showZoom: false,
        browseClass: "btn btn-primary",
        previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
        overwriteInitial: false,
        initialPreviewAsData: true,
        dropZoneEnabled: true,
        allowedFileExtensions: ['pdf']
    });

    $('#modalGLI').modal('show');
});

//Borrar el archivo cargado
$('#cargaArchivo').on('fileclear', function(event) {
    $('#cargaArchivo').attr('data-borrado','true');
    $('#cargaArchivo')[0].files[0] = null;
});

$('#cargaArchivo').on('fileselect', function(event) {
  $('#cargaArchivo').attr('data-borrado','false');
});

$(document).on('click','.modificar',function(){
  limpiarModalGLIHard();
      var id = $(this).val();

  //Modificar los colores del modal
    $('.modal-title').text('| MODIFICAR GLI HARDWARE');
    $('.modal-header').attr('style','background: #ff9d2d');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn').addClass('btn-warningModificar');
    $('#btn-guardar').show();
    $('#btn-guardar').text('ACEPTAR');
    $('#id_gli').val(id);

    // $('.btn-default').text('CANCELAR');

  //Resetear formulario para llevar los datos
    $('input').prop('readonly',false);
    $('#btn-guardar').val("modificar");

    //obtenerGli


    $('#inputExpediente').generarDataList("expedientes/buscarExpedientePorNumero",'resultados','id_expediente','concatenacion',2,true);
    $('#inputExpediente').setearElementoSeleccionado(0,"");

    $('#cargaArchivo').attr('data-borrado','false');

    $.get("glihards/obtenerGliHard/" +id , function(data){
        $('#nroCertificado').val(data.glihard.nro_archivo);

        //SI NO HAY ARCHIVO EN LA BASE
        if (data.nombre_archivo == null) {
          //Inicializa el fileinput para cargar los PDF
          $("#cargaArchivo").fileinput('destroy').fileinput({
              language: 'es',
              showRemove: false,
              showUpload: false,
              showCaption: false,
              showZoom: false,
              browseClass: "btn btn-primary",
              previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
              overwriteInitial: false,
              initialPreviewAsData: true,
              dropZoneEnabled: true,
              allowedFileExtensions: ['pdf']
          });

        //SI HAY ARCHIVO EN LA BASE
        }else{
          //Carga el fileinput con el PDF cargado
          $("#cargaArchivo").fileinput('refresh', {
              language: 'es',
              showRemove: false,
              showUpload: false,
              showCaption: false,
              showZoom: false,
              browseClass: "btn btn-primary",
              previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
              overwriteInitial: true,
              initialPreviewAsData: true,
              initialPreview: [
                  "http://localhost:8000/glihards/pdf/" + id,
              ],
              initialPreviewConfig: [
                  {type:'pdf', caption: data.nombre_archivo, size: 329892, width: "120px", url: "{$url}", key: 1},
              ],
              allowedFileExtensions: ['pdf'],
          });
        }

        //Cargar los expedientes asociados
        for (var i = 0; i < data.expedientes.length; i++) {
          agregarFilaExpediente(data.expedientes[i]);

          // $('#tablaExpedientesHard')
          //   .append($('<li>')
          //        .text(data.expedientes[i].nro_exp_org+'-'+data.expedientes[i].nro_exp_interno + '-' + data.expedientes[i].nro_exp_control)
          //        .val(data.expedientes[i].id_expediente)
          //        .append($('<button>')
          //             .addClass('btn').addClass('btn-danger').addClass('btn-xs').addClass('borrarExpediente')
          //             .append($('<i>')
          //                 .addClass('fa').addClass('fa-times')
          //             )
          //        )
          //   )
        }

  });

  $('#modalGLI').modal('show');

});



$(document).on('click','.detalle',function(){
  //Modificar los colores del modal
    $('.modal-title').text('| VER MÁS');
    $('.modal-header').attr('style','background: #4FC3F7');
    // $('#btn-guardar').removeClass('btn-success');
    // $('#btn-guardar').addClass('btn-warning');
    //TIENE QUE DESAPARECER
    // $('#btn-guardar').text('Modificar GLI');
    $('#btn-guardar').hide();
    $('#id_gli').val($(this).val());

  //Resetear formulario para llevar los datos
    $('#frmGLI').trigger('reset');
    $('#modalGLI').modal('show');
    $('#btn-guardar').val("modificar");
    $('#listaExpedientes').empty();

    $('.modal-footer .btn-default').text('SALIR');

    //obtenerGli
    var id=$(this).val();
    $.get("glihards/obtenerGliHard/" +id , function(data){
      $('#nroCertificado').val(data.glihard.nro_archivo);

      $("#cargaArchivo").fileinput('refresh', {
          language: 'es',
          showRemove: false,
          showUpload: false,
          showCaption: false,
          showZoom: false,
          browseClass: "btn btn-primary",
          previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
          overwriteInitial: true,
          initialPreviewAsData: true,
          initialPreview: [
              "http://localhost:8000/glihards/pdf/" + id,
          ],
          initialPreviewConfig: [
              {type:'pdf', caption:  data.nombre_archivo, size: 329892, width: "120px", url: "{$url}", key: 1},
          ],
          allowedFileExtensions: ['pdf'],
      });

      for (var i = 0; i < data.expedientes.length; i++) {

        $('#listaExpedientes')
          .append($('<li>')
               .text(data.expedientes[i].nro_exp_org+'-'+data.expedientes[i].nro_exp_interno + '-' + data.expedientes[i].nro_exp_control)
               .val(data.expedientes[i].id_expediente)

          )
      }

  })

  $('#nroCertificado').prop('readonly' , true);
  $('#inputExpediente').prop('readonly' , true);

});





// Crear/Modificar nuevo gli
$('#btn-guardar').click(function (e) {

  //estado del boton
  var estado=$(this).val();

  var expedientes = [];

  $('#tablaExpedientesHard tbody tr').each(function(){
    expedientes.push($(this).attr('id'));
  });

  if(estado=='nuevo'){
    //seteo de la ruta y del contenido del formulario
    var url="glihards/guardarGliHard";
    var formData=new FormData();
    formData.append('nro_certificado',$('#nroCertificado').val());

    if ($('#cargaArchivo')[0].files[0] != null) formData.append('file' , $('#cargaArchivo')[0].files[0]);
    // formData.append('file' , $('#cargaArchivo')[0].files[0]);
    formData.append('expedientes' , expedientes);
  }else{
    var id=$('#id_gli').val();
    var url="glihards/modificarGliHard";
    var formData=new FormData();
    formData.append('id_gli_hard' , $('#id_gli').val());
    formData.append('nro_certificado',$('#nroCertificado').val());
    // formData.append('file' , $('#cargaArchivo')[0].files[0]);

    //Si el archivo no fue borrado y hay un archivo, se manda.
    if($('#cargaArchivo').attr('data-borrado') == 'false' && $('#cargaArchivo')[0].files[0] != null){
      formData.append('file' , $('#cargaArchivo')[0].files[0]);
    }
    formData.append('borrado', $('#cargaArchivo').attr('data-borrado'));
    formData.append('expedientes' , expedientes);

  }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  $.ajax({
    type: "POST",
    url: url,
    data: formData,
    dataType: "json",
    processData: false,
    contentType:false,
    cache:false,

    success: function (data) {
      console.log(data);
      $('#buscarCertificado').trigger('click');
      $('#modalGLI').modal('hide');
    },
    error: function (data) {
      console.log('Error:', data);
      var response = JSON.parse(data.responseText);
      // $('#alertaNroCertificado').hide();
      // $('#alertaNroCertificado').text("");
      if(typeof response.nro_certificado != 'undefined'){
        mostrarErrorValidacion($('#nroCertificado'),response.nro_certificado[0] ,true);
        $('#nroCertificado').addClass('alerta');
      }
    }
  })


})

//Borrar GLI
$(document).on('click','.eliminar',function(){

    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    $('#btn-guardar').removeClass('btn-success');
    $('#btn-guardar').addClass('btn-warning');
    $('#btn-guardar').text('Eliminar');
    $('#btn-eliminar').val($(this).val());
    $('#modalEliminar').modal('show');

});

$('#btn-eliminar').click(function(){
  var id_gli = $(this).val();

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })

  $.ajax({
      type: "DELETE",
      url: "glihards/eliminarGliHard/" + id_gli ,
      success: function (data) {
        $('#cuerpoTabla #' + id_gli).remove();
        $("#tablaGliHard").trigger("update");

        $('#modalEliminar').modal('hide');
      },
      error: function (data) {
        console.log('Error: ', data);
      }
  })
});

//si apreto enter en el formulario
// $(document).on("keypress" , function(e){
//   if(e.which == 13 && $('#modalGLI').is(':visible')) {
//     e.preventDefault();
//     $('#btn-guardar').click();
//   }
// })

//si apreto enter en los filtros de busqueda
$("#contenedorFiltros input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#buscarCertificado').click();
    }
});

$('#buscarCertificado').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var page_size = (page_size != null) ? page_size : 10;
  var page_number = (pagina != null) ? pagina : 1;
  var sort_by = (columna != null) ? {columna,orden} : null;
  if(sort_by == null){ // limpio las columnas
    $('#tablaGliHard th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
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
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaGliHard .activa').attr('value'),orden: $('#tablaGliHard .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaGliHard th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    nro_exp_org:$('#nro_exp_org').val(),
    nro_exp_interno:$('#nro_exp_interno').val(),
    nro_exp_control:$('#nro_exp_control').val(),
    casino:$('#sel1').val(),
    certificado: $('#nro_certificado').val(),
    nombre_archivo: $('#nombre_archivo').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: "post",
      url: 'glihards/buscarGliHard',
      data: formData,
      dataType: 'json',
      success: function (resultados) {
          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.resultados.total,clickIndice);
          $('#tablaGliHard tbody tr').remove();

          for (var i = 0; i < resultados.resultados.data.length; i++) {
            var filaCertificado = generarFilaTabla(resultados.resultados.data[i]);
            $('#cuerpoTabla').append(filaCertificado);
          }

      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaGliHard .activa').attr('value');
  var orden = $('#tablaGliHard .activa').attr('estado');
  $('#buscarCertificado').trigger('click',[pageNumber,tam,columna,orden]);
}

//Ordenar tabla
$(document).on('click','#tablaGliHard thead tr th[value]',function(e){
  $('#tablaGliHard th').removeClass('activa');
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
  $('#tablaGliHard th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function generarFilaTabla(certificado){
      var fila = $('<tr>').attr('id',certificado.id_gli_soft);

      var nombre_archivo = certificado.nombre_archivo;

      if (nombre_archivo == null) {
          nombre_archivo = '-'
      }

      fila.append($('<td>')
              .addClass('col-xs-4')
              .text(certificado.nro_archivo)
          )
          .append($('<td>')
              .addClass('col-xs-5')
              .text(nombre_archivo)
          )
          .append($('<td>')
              .addClass('col-xs-3')
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                  )
                  .append($('<span>').text(' VER MÁS'))
                  .addClass('btn').addClass('btn-info').addClass('detalle')
                  .attr('value',certificado.id_gli_hard)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                  )
                  .append($('<span>').text(' MODIFICAR'))
                  .addClass('btn').addClass('btn-warning').addClass('modificar')
                  .attr('value',certificado.id_gli_hard)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
                  )
                  .append($('<span>').text(' ELIMINAR'))
                  .addClass('btn').addClass('btn-danger').addClass('eliminar')
                  .attr('value',certificado.id_gli_hard)
              )
          )
        return fila;
}

//Vieja
// $('#buscarCertificado').click(function(e){
//   var formData={
//     nro_exp_org:$('#nro_exp_org').val(),
//     nro_exp_interno:$('#nro_exp_interno').val(),
//     nro_exp_control:$('#nro_exp_control').val(),
//     casino:$('#sel1').val(),
//     certificado: $('#nro_certificado').val(),
//     nombre_archivo: $('#nombre_archivo').val(),
//   }
//
//   $.ajaxSetup({
//       headers: {
//           'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
//       }
//   });
//
//   $.ajax({
//       type: "post",
//       url: 'glihards/buscarGliHard',
//       data: formData,
//       dataType: 'json',
//       success: function (data) {
//           console.log(data);
//           $('#cuerpoTabla').empty();
//           var resultado='';
//           for (var i = 0; i < data.resultados.length; i++) {
//             var nombre_archivo;
//             data.resultados[i].nombre_archivo != null ? nombre_archivo=data.resultados[i].nombre_archivo : nombre_archivo='-';
//
//                         $('#cuerpoTabla')
//                               .append($('<tr>')
//                               .attr("id" ,data.resultados[i].id_gli_hard )
//                               .append($('<td>')
//
//                                   .text(data.resultados[i].nro_archivo)
//                               )
//                               .append($('<td>')
//                                         .text(nombre_archivo)
//                               )
//
//                               .append($('<td>')
//                                   .append($('<button>')
//                                   .attr('type','button')
//
//                                       .append($('<i>')
//                                           .addClass('fa').addClass('fa-search-plus')
//                                       )
//                                       .append($('<span>').text(' VER MÁS'))
//                                       .addClass('btn').addClass('btn-info').addClass('detalle')
//                                       .attr('value',data.resultados[i].id_gli_hard)
//                                   )
//                                   .append($('<span>').text(' '))
//                                   .append($('<button>')
//                                   .attr('type','button')
//                                       .append($('<i>')
//                                           .addClass('fa').addClass('fa-pencil')
//                                       )
//                                       .append($('<span>').text(' MODIFICAR'))
//                                       .addClass('btn').addClass('btn-warning').addClass('modificar')
//                                       .attr('value',data.resultados[i].id_gli_hard)
//                                   )
//                                   .append($('<span>').text(' '))
//                                   .append($('<button>')
//                                   .attr('type','button')
//                                       .append($('<i>')
//                                           .addClass('fa')
//                                           .addClass('fa-trash')
//                                       )
//                                       .append($('<span>').text(' ELIMINAR'))
//                                       .addClass('btn').addClass('btn-danger').addClass('eliminar')
//                                       .attr('value',data.resultados[i].id_gli_hard)
//                                   )
//                               )
//                           )
//
//           }
//
//           $("#tablaGliHard").trigger("update");
//           $("#tablaGliHard th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');
//
//         },
//       error: function (data) {
//           var response = JSON.parse(data.responseText);
//           console.log('Error:', data);
//       }
//   });
// });
