$(document).ready(function(){
  // $('#noexiste_isla').show();
  // $('#headListaSoft').hide();
  limpiarCamposSoft();
});

//Andan
$('#inputSoft').on('input', function(){
  //Si no escribe nada esconder todos los botones
  if ($(this).val().length == 0) {
    $('#btn-agregarSoftLista').hide();
    $('#btn-cancelarSoft').hide();
    $('#btn-crearSoft').hide();
  }else {
    $('#btn-agregarSoftLista').hide();
    $('#btn-cancelarSoft').show();
    $('#btn-crearSoft').show();
  }
})

function obtenerDatosGliSoft(){
  var agregado = $('#listaSoftMaquina').attr('data-agregado');
  var id_gli_soft = $('#datosGLISoft').attr('data-id');

  // Si hay GLI agregado manda el id.
  if (agregado == 'true') {

      // Si es CREADO manda un 0.
      if (id_gli_soft == 0) {
          var file = '';

          if($('#cargaArchivoSoft').attr('data-borrado') == 'false' && $('#cargaArchivoSoft')[0].files[0] != null){
              file = $('#cargaArchivoSoft')[0].files[0];
          }

          var gli_soft = {
              id_gli_soft: 0,
              nro_certificado: $('#inputSoft').val(),
              observaciones: $('#observaciones').val(),
              file: file,
          }

      // Si es AGREGADO manda el id existente
      }else{
          var gli_soft = {
              id_gli_soft:  $('#datosGLISoft').attr('data-id'),
          }
      }

  // Si no hay GLI agegado
  }else{
      var gli_soft = {
          id_gli_soft: '',
      };
  }

  return gli_soft;
}

$('#inputSoft').on('deseleccionado',function(){
  $('#btn-agregarSoftLista').hide();
  $('#btn-cancelarSoft').show();
  $('#btn-crearSoft').show();
  console.log('deseleccionado');
  // $('#inputSoft').val(inputSoft);
  $('#observaciones').val('').prop('readonly',false);

  $("#cargaArchivoSoft").fileinput('destroy').fileinput({
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
  $('#cargaArchivoSoft').fileinput('enable');
  $('#secSoft .fileinput-remove').on('click',function(){
      $("#cargaArchivoSoft").fileinput('refresh');
  });
});

$('#inputSoft').on('seleccionado',function(){
  //Mostrar los datos del GLI
  var id_gli_soft = $(this).obtenerElementoSeleccionado();

  console.log('Cambió',id_gli_soft);
  $.get('http://' + window.location.host +"/glisofts/obtenerGliSoft/" + id_gli_soft, function(data){

      console.log(data);
      $('#observaciones').val(data.glisoft.observaciones).prop('readonly',true);

      //SI NO HAY ARCHIVO EN LA BASE
      if (data.nombre_archivo == null) {
          //Inicializa el fileinput para cargar los PDF
          $("#cargaArchivoSoft").fileinput('destroy').fileinput({
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
          $("#cargaArchivoSoft").fileinput('destroy').fileinput({
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
                  'http://' + window.location.host +  "/glisofts/pdf/" + data.glisoft.id_gli_soft,
              ],
              initialPreviewConfig: [
                  {type:'pdf', caption: data.nombre_archivo, size: 329892, width: "120px", url: "{$url}", key: 1},
              ],
              allowedFileExtensions: ['pdf'],
          });
      }
  });

  $('#btn-agregarSoftLista').show();
  $('#btn-cancelarSoft').show();
  $('#btn-crearSoft').hide();
  // $.get('glisofts/obtenerGliSoft/' + id_gli_soft, function(data){
  //     console.log(data);
  //     $('#observaciones').text(data.glisoft.observaciones);
  // });
});

//CREAR HARD Y AGREGARLO A LA MÁQUINA
$('#btn-crearSoft').click(function(){
    agregarFilaSoft();
});

//AGREGAR UN HARD EXISTENTE A LA MÁQUINA
$('#btn-agregarSoftLista').click(function(){
    agregarFilaSoft();
});

//Limpiar todos los campos
$('#btn-cancelarSoft').click(function(){
    limpiarCamposSoft();
});

//Quitar hard de la máquina
$(document).on('click','.borrarSoft',function(){
    // $(this).parent().parent().remove();
    // $('#muestraArchivoSoft').hide();
    $('#listaSoftMaquina').attr('data-agregado','false');
    $('#agregarSoft').show();
    // $('#softPlegado').show();

    limpiarCamposSoft();

    $('#listaSoftMaquina .zona-file > div').remove();
    $('#listaSoftMaquina .zona-file').hide();

    //Agregar mensaje
    $('#listaSoftMaquina p').show();
    $('#tablaSoftActivo').hide();
});

//Creo que andan
$('#cargaArchivoSoft').on('fileclear', function(event) {
  //1ro. Se setea borrado en el data.
  //2do. Se borra el archivo del input.
  $('#cargaArchivoSoft').attr('data-borrado','true');
  $('#cargaArchivoSoft')[0].files[0] = null;
});

$('#cargaArchivoSoft').on('fileselect', function(event) {
  $('#cargaArchivoSoft').attr('data-borrado','false');
});

function limpiarCamposSoft(){
  console.log('limpia soft');
  $('#btn-crearSoft').hide();
  $('#btn-cancelarSoft').hide();
  $('#btn-agregarSoftLista').hide();

  // $('#inputSoft').val('');
  $('#inputSoft').generarDataList("http://" + window.location.host + "/glisofts/buscarGliSoftsPorNroArchivo",'gli_softs','id_gli_soft','nro_archivo',2,false);

  $('#observaciones').val('').prop('readonly',false);

  $("#cargaArchivoSoft").fileinput('destroy').fileinput({
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

  $('#cargaArchivoSoft').attr('data-borrado','true');
  $('#cargaArchivoSoft')[0].files[0] = null;

  //Habilitar el input file y el evento click de la cruz para eliminar
  $('#cargaArchivoSoft').fileinput('enable');
  $('#secSoft .fileinput-remove').on('click');

}

function agregarFilaSoft(){
      $('#tablaSoftActivo').show();

      $('#datosGLISoft').attr('data-id',$('#inputSoft').obtenerElementoSeleccionado());
      $('#datosGLISoft').attr('data-codigo', $('#inputSoft').val());
      $('#datosGLISoft').attr('data-observaciones',$('#observaciones').val());

      $('#nro_certificado_activo').text($('#inputSoft').val());
      $('#nombre_archivo_activo').text($('#secSoft .file-footer-caption').attr('title'));

      $('#listaSoftMaquina').attr('data-agregado','true');

    //SI HAY UN ARCHIVO
    if (typeof $('#secSoft .file-footer-caption').attr('title') != 'undefined') {
      var inputFile = $('#softPlegado .zona-file').clone();
      // inputFile.attr('id','muestraArchivoSoft');
      console.log(inputFile);

      $('#listaSoftMaquina .zona-file').replaceWith(inputFile);

      $('#listaSoftMaquina .zona-file').css('height','340px');

      //Quitar los botones para eliminar y para cargar del input file
      $('#listaSoftMaquina .fileinput-remove').remove();
      $('#listaSoftMaquina .btn-file').remove();
      $('#listaSoftMaquina .file-preview-frame').css('position','relative').css('top','6px').css('left','6px');

    }else{
      $('#listaSoftMaquina .zona-file').hide();
    }

    //Ocultar la sección para agregar o crear Hard
    $('#agregarSoft').hide();
    $('#softPlegado').removeClass('in');

    //Quitar mensaje
    $('#listaSoftMaquina p').hide();
}

/* FUNCIONES COMUNES EN TODAS LAS SECCIONES */
function limpiarModalGliSoft(){
    $('#frmGliSoft').trigger('reset');
    $('#softPlegado').removeClass('in');
    $('#agregarSoft').show();
    limpiarCamposSoft();


    $('.borrarSoft').trigger('click');

    $('#softActivo').remove();
}

function ocultarAlertasGliSoft(){
  $('#alerta_codigo_soft').hide();
  $('#alerta_observaciones').hide();
  $('#alerta_archivoSoft').hide();
}

function habilitarControlesGliSoft(valor){
  if(valor){
    $('#frmGliSoft input').prop('readonly',false);
    $('#frmGliSoft textarea').prop('readonly',false);
    $('#softPlegado').removeClass('in');
    $('#agregarSoft').show();
    $('#borrarSoft').show();
  }else {
    $('#frmGliSoft input').prop('readonly',true);
    $('#frmGliSoft textarea').prop('readonly',true);
    $('#softPlegado').removeClass('in');
    $('#agregarSoft').hide();
    $('.borrarSoft').hide();
  }
}

function mostrarGliSofts(gli_softs){
  $('#tablaSoftActivo tr').not('#datosGLISoft').remove();
  $('#listaSoftMaquina .zona-file').empty();
  for(let i = 0;i<gli_softs.length;i++){
    mostrarGliSoft(gli_softs[i]);
  }
  //Ajusto el borde de la parte de archivos.
  $('#listaSoftMaquina .zona-file').css('height',(340*gli_softs.length)+'px');
}

function mostrarGliSoft(gli_soft){
  if(gli_soft.id != 0){
    $('#tablaSoftActivo').show();
    let fila = $('#datosGLISoft').hide().clone().show().attr('id','');

    fila.attr('data-id',gli_soft.id);
    fila.attr('data-codigo',gli_soft.nro_archivo);
    fila.attr('data-observaciones',gli_soft.observaciones);

    fila.find('.nro_certificado_activo').text(gli_soft.nro_archivo);
    fila.find('.nombre_archivo_activo').text(gli_soft.nombre_archivo);
    fila.find('.nombre_juego_gli').text(gli_soft.juego? gli_soft.juego : '-');
    if(gli_soft.activo) fila.css('background-color','rgb(245,245,255)');

    $('#tablaSoftActivo tbody').append(fila);

    $('#listaSoftMaquina').attr('data-agregado','true');

    var archivo = $('#listaSoftMaquina .zona-file');
    archivo.append($('<input>').attr('id','muestraArchivoSoft').attr('type','file'));
    archivo.show();

    $('#listaSoftMaquina .zona-file').css('height','340px');

    if(gli_soft.nombre_archivo!=''){
      //Carga el fileinput con el PDF cargado
      $("#muestraArchivoSoft").fileinput('destroy').fileinput({
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
          'http://' + window.location.host + "/glisofts/pdf/" + gli_soft.id,
        ],
        initialPreviewConfig: [
          {type:'pdf', caption: gli_soft.nombre_archivo, size: 329892, width: "100%", url: "{$url}", key: 1},
        ],
        previewZoomSettings: {pdf: {width: "100%", height: "100%", 'min-height': "480px"}},
        allowedFileExtensions: ['pdf'],
      });

      $('#listaSoftMaquina .fileinput-remove').remove();
      $('#listaSoftMaquina .btn-file').remove();
      $('#listaSoftMaquina .file-preview').css('position','relative').css('top','-15px').css('left','7px');
    }
    else {
      $('#listaSoftMaquina .zona-file').hide();
    }


    //Ocultar la sección para agregar o crear Hard
    $('#agregarSoft').hide();
    $('#softPlegado').removeClass('in');

    //Quitar mensaje
    $('#listaSoftMaquina p').hide();
  }
}
