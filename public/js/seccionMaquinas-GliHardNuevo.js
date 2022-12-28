$(document).ready(function(){
  // $('#noexiste_isla').show();
  // $('#headListaSoft').hide();
  limpiarCamposHard();
});

$('#inputHard').on('input', function(){
  //Si no escribe nada esconder todos los botones
  if ($(this).val().length == 0) {
    $('#btn-crearHard').hide();
    $('#btn-cancelarHard').hide();
    $('#btn-agregarHardLista').hide();
  }else {
    $('#btn-crearHard').show();
    $('#btn-cancelarHard').show();
    $('#btn-agregarHardLista').hide();
  }
})

$('#inputHard').on('deseleccionado',function(){
  $('#btn-crearHard').show();
  $('#btn-cancelarHard').show();
  $('#btn-agregarHardLista').hide();

  $("#cargaArchivoHard").fileinput('destroy').fileinput({
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

  //Habilitar el input file y el evento click de la cruz para eliminar
  $('#cargaArchivoHard').fileinput('enable');
  $('#secHard .fileinput-remove').on('click',function(){
      $("#cargaArchivoHard").fileinput('refresh');
  });
});

$('#inputHard').on('seleccionado',function(){
  //Mostrar los datos del GLI
  var id_gli_hard = $(this).obtenerElementoSeleccionado();

  $.get(window.location.href+"/certificadoHard/obtenerGliHard/" + id_gli_hard, function(data){

      //SI NO HAY ARCHIVO EN LA BASE
      if (data.nombre_archivo == null) {
          //Inicializa el fileinput para cargar los PDF
          $("#cargaArchivoHard").fileinput('destroy').fileinput({
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
          $("#cargaArchivoHard").fileinput('destroy').fileinput({
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
                  window.location.href+"/certificadoHard/pdf/" + data.glihard.id_gli_hard,
              ],
              initialPreviewConfig: [
                  {type:'pdf', caption: data.nombre_archivo, size: 329892, width: "120px", url: "{$url}", key: 1},
              ],
              allowedFileExtensions: ['pdf'],
          });
      }
  });

  $('#btn-crearHard').hide();
  $('#btn-cancelarHard').show();
  $('#btn-agregarHardLista').show();
});

//CREAR HARD Y AGREGARLO A LA MÁQUINA
$('#btn-crearHard').click(function(){
    agregarFilaHard();
});

//AGREGAR UN HARD EXISTENTE A LA MÁQUINA
$('#btn-agregarHardLista').click(function(){
    agregarFilaHard();
});

//Limpiar todos los campos
$('#btn-cancelarHard').click(function(){
    limpiarCamposHard();
});

//Quitar hard de la máquina
$(document).on('click','.borrarHard',function(){

    $('#listaHardMaquina').attr('data-agregado','false');
    $('#agregarHard').show();
    // $('#softPlegado').show();

    limpiarCamposHard();

    $('#listaHardMaquina .zona-file > div').remove();
    $('#listaHardMaquina .zona-file').hide();

    //Agregar mensaje
    $('#listaHardMaquina p').show();
    $('#tablaHardActivo').hide();
});

//Creo que andan
$('#cargaArchivoHard').on('fileclear', function(event) {
  //1ro. Se setea borrado en el data.
  //2do. Se borra el archivo del input.
  $('#cargaArchivoHard').attr('data-borrado','true');
  $('#cargaArchivoHard')[0].files[0] = null;
});

function limpiarCamposHard(){
  $('#btn-crearHard').hide();
  $('#btn-cancelarHard').hide();
  $('#btn-agregarHardLista').hide();

  // $('#inputSoft').val('');
  $('#inputHard').generarDataList(window.location.href+"/certificadoHard/buscarGliHardsPorNroArchivo",'gli_hards','id_gli_hard','nro_archivo',2,false);

  // $('#observaciones').val('').prop('readonly',false);

  $("#cargaArchivoHard").fileinput('destroy').fileinput({
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

  $('#cargaArchivoHard').attr('data-borrado','true');
  $('#cargaArchivoHard')[0].files[0] = null;
  console.log('Limpiando Hard');

  //Habilitar el input file y el evento click de la cruz para eliminar
  $('#cargaArchivoHard').fileinput('enable');
  $('#secHard .fileinput-remove').on('click');
}

function agregarFilaHard(){
      $('#tablaHardActivo').show();

      $('#nro_certificado_hard_activo').text($('#inputHard').val());
      $('#nombre_archivo_hard_activo').text($('#secHard .file-footer-caption').attr('title'));
      const id_hard = $('#inputHard').attr('data-elemento-seleccionado');
      $('#nro_certificado_hard_activo').attr('data-id',id_hard);
      $('#listaHardMaquina').attr('data-agregado','true');

    //SI HAY UN ARCHIVO
    if (typeof $('#secHard .file-footer-caption').attr('title') != 'undefined') {
      var inputFile = $('#hardPlegado .zona-file').clone();
      // inputFile.attr('id','muestraArchivoSoft');
      console.log(inputFile);

      $('#listaHardMaquina .zona-file').replaceWith(inputFile);

      $('#listaHardMaquina .zona-file').css('height','340px');

      //Quitar los botones para eliminar y para cargar del input file
      $('#listaHardMaquina .fileinput-remove').remove();
      $('#listaHardMaquina .btn-file').remove();
      $('#listaHardMaquina .file-preview-frame').css('position','relative').css('top','6px').css('left','6px');

    }else{
      $('#listaHardMaquina .zona-file').hide();
    }

    //Ocultar la sección para agregar o crear Hard
    $('#agregarHard').hide();
    $('#hardPlegado').removeClass('in');

    //Quitar mensaje
    $('#listaHardMaquina p').hide();
}

/* FUNCIONES COMUNES EN TODAS LAS SECCIONES */
function limpiarModalGliHard(){
    $('#frmGliHard').trigger('reset');
    $('#hardPlegado').removeClass('in');
    $('#agregarHard').show();
    limpiarCamposHard();
    $('.borrarHard').trigger('click');
    $('#hardActivo').remove();
}

function ocultarAlertasGliHard(){
  $('#alerta_codigo_hard').hide();
  $('#alerta_archivoHard');
}

function habilitarControlesGliHard(valor){
  if (valor) {
    $('#hardPlegado').removeClass('in');
    $('#agregarHard').show();
  }else {
    $('#hardPlegado').removeClass('in');
    $('#agregarHard').hide();
  }
  $('#frmGliHard input').prop('readonly',!valor);

}

function mostrarGliHard(gli_hard){
  if(gli_hard.id != 0){
    $('#tablaHardActivo').show();

    $('#nro_certificado_hard_activo').text(gli_hard.nro_archivo);
    $('#nombre_archivo_hard_activo').text(gli_hard.nombre_archivo);

    $('#listaHardMaquina').attr('data-agregado','true');

    var archivo = $('#listaHardMaquina .zona-file');
    archivo.append($('<input>').attr('id','muestraArchivoHard').attr('type','file'));
    archivo.show();

    $('#listaHardMaquina .zona-file').css('height','340px');

    if(gli_hard.nombre_archivo!=''){
      //Carga el fileinput con el PDF cargado
      $("#muestraArchivoHard").fileinput('destroy').fileinput({
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
          window.location.href+"/certificadoHard/pdf/"+gli_hard.id,
        ],
        initialPreviewConfig: [
          {type:'pdf', caption: gli_hard.nombre_archivo, size: 329892, width: "100%", url: "{$url}", key: 1},
        ],
        previewZoomSettings: {pdf: {width: "100%", height: "100%", 'min-height': "480px"}},
        allowedFileExtensions: ['pdf'],
      });

      $('#listaHardMaquina .fileinput-remove').remove();
      $('#listaHardMaquina .btn-file').remove();
      $('#listaHardMaquina .file-preview').css('position','relative').css('top','-15px').css('left','7px');
    }


    //Ocultar la sección para agregar o crear Hard
    $('#agregarHard').hide();
    $('#hardPlegado').removeClass('in');

    //Quitar mensaje
    $('#listaHardMaquina p').hide();


  }

}

function obtenerDatosGliHard(){
    let agregado = $('#listaHardMaquina').attr('data-agregado');
    let gli_hard = {
      id_gli_hard: null,
      nro_certificado: null,
      nombre_archivo: null,
      file: null,
    }

    // Si hay GLI agregado manda el id.
    if (agregado == 'true') {

      gli_hard.nombre_archivo = $('#nombre_archivo_hard_activo').text();
      gli_hard.id_gli_hard = $('#nro_certificado_hard_activo').attr('data-id');
      if(gli_hard.id_gli_hard == 0 && $('#cargaArchivoHard')[0].files[0] != null){
          gli_hard.file = $('#cargaArchivoHard')[0].files[0];
      }
      gli_hard.nro_certificado = $('#nro_certificado_hard_activo').text();
    }

    //console.log('Datos');
    //console.log(gli_hard);
    return gli_hard;
  }
