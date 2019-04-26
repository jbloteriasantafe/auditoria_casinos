$(document).ready(function() {
    $('#filtroCasino').val('0');
    $('#filtroFecha').val('');
    $('#filtroMoneda').val('0');

    $('#mensajeExito').hide();
    $('#mensajeErrorMes').hide();

    $(function(){
        $('#dtpFechaFiltro').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'yyyy-MM',
          pickerPosition: "bottom-left",
          startView: 3,
          minView: 3,
          ignoreReadonly: true,

        });
    });
    $(function(){
        $('#dtpFechaImpMes').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'yyyy-MM',
          pickerPosition: "bottom-left",
          startView: 3,
          minView: 3,
          ignoreReadonly: true,
          container: $('#modalImportacionMensual')
        });
    });
  //  $('#pest_mensual').show();
    $('#buscar-impMensuales').trigger('click',[1,5,'fecha_mes','desc']);
    $('.opcionPaginacion #size').val(5);
});

//buscar, filtros
$('#buscar-impMensuales').click(function(e,pagina,page_size,columna,orden){

    e.preventDefault();
    //Fix error cuando librería saca los selectores
    if(isNaN($('#herramientasPaginacionMensual').getPageSize())){
      var size = 5; // por defecto
    }
    else {
      var size = $('#herramientasPaginacionMensual').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacionMensual').getCurrentPage();
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultadosMes .activa').attr('value'),orden: $('#tablaResultadosMes .activa').attr('estado')} ;

    if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
      var sort_by =  {columna: 'fecha_mes',orden: 'desc'} ;

      //$('#tablaInicial th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

    var formData= {
      fecha: $('#filtroFecha').val(),
      id_moneda:$('#filtroMoneda').val(),
      casino: $('#filtroCasino').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: 'POST',
        url: 'importacionMensual/filtros',
        data: formData,
        dataType: 'json',

        success: function (data){
          $('#tablaResultadosMes tbody tr').remove();

          $('#herramientasPaginacionMensual').generarTitulo(page_number,page_size,data.importaciones.total,clickIndiceMes);


          for (var i = 0; i < data.importaciones.data.length; i++) {

              var fila=  generarFilaTablaInicial(data.importaciones.data[i]);
              $('#cuerpoTablaImpM').append(fila);
          }
          $('#herramientasPaginacionMensual').generarIndices(page_number,page_size,data.importaciones.total,clickIndiceMes);


        },
        error: function(data){
        },
    })

});
//paginacion
$(document).on('click','#tablaResultadosMes thead tr th[value]',function(e){

  $('#tablaResultadosMes th').removeClass('activa');

  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    console.log('1');
    $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{

    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
        $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaResultadosMes th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndiceMes(e,$('#herramientasPaginacionMensual').getCurrentPage(),$('#herramientasPaginacionMensual').getPageSize());
});


function clickIndiceMes(e,pageNumber,tam){

  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacionMensual').getPageSize();
  var columna = $('#tablaResultadosMes .activa').attr('value');
  var orden = $('#tablaResultadosMes .activa').attr('estado');
  $('#buscar-impMensuales').trigger('click',[pageNumber,tam,columna,orden]);
}
//fin paginacion

//importación
document.getElementById('archivoMes').addEventListener('change', handleFileSelect, false);

//boton grande de importar
$('#btn-importar-mes').on('click', function(e){

    e.preventDefault();

    $('#mensajeErrorGral').hide();

    ocultarErrorValidacion($('#B_fecha_imp_mes'));
    ocultarErrorValidacion($('#monedaSelMes'));
    ocultarErrorValidacion($('#casinoSelMes'));
    $('#B_fecha_imp_mes').val("");
    $('#casinoSelMes').val('0');
    $('#monedaSelMes').val('0');
    $('#modalImportacionMensual').find('.modal-footer').children().show();
    $('#modalImportacionMensual').find('.modal-body').children().show();

    //Mostrar: rowarchivoMes
    $('#modalImportacionMensual #rowarchivoMes').show();
    $('#modalImportacionMensual').modal('show');

    $('#modalImportacionMensual #iconoCargaMes').hide();

    //Ocultar: rowFecha, mensajes, iconoCarga
    $('#modalImportacionMensual #rowFecha').hide();
    $('#modalImportacionMensual #mensajeErrorMes').hide();
    $('#modalImportacionMensual #mensajeInvalido').hide();
    $('#modalImportacionMensual #mensajeInformacion').hide();

    habilitarInputMensual();

    $('#mensajeExito').hide();

    $('#btn-guardarMensual').hide();
});

$('#modalImportacionMensual #archivoMes').on('fileerror', function(event, data, msg) {
     $('#modalImportacionMensual #mensajeInformacion').hide();
     $('#modalImportacionMensual #mensajeInvalido').show();
     $('#modalImportacionMensual #mensajeInvalido #span').text(msg);
     //Ocultar botón SUBIR
    // $('#guardar-observacion').hide();

  });

$('#modalImportacionMensual #archivoMes').on('fileselect', function(event) {
      $('#modalImportacionMensual #archivoMes').attr('data-borrado','false');

      // Se lee el archivo guardado en el input de tipo 'file'.

      var reader = new FileReader();
      reader.readAsText($('#modalImportacionMensual #archivoMes')[0].files[0]);
      //reader.onload = procesarDatosBeneficios;
  });


//presiona subir en el modal de importación
$('#btn-guardarMensual').on('click', function(e){

    $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });
        e.preventDefault();
      var url = 'importacionMensual/importar';

      //determina cuantos dias tiene el mes q se importa
      var str = $('#B_fecha_imp_mes').val();
      var res = str.split("-");
      var nroMes;

      switch (res[1]) {
        case 'Enero':
          nroMes='01';
          break;
        case 'Febrero':
          nroMes='02';
            break;
        case 'Marzo':
          nroMes='03';
            break;
        case 'Abril':
          nroMes='04';
            break;
        case 'Mayo':
          nroMes='05';
            break;
        case 'Junio':
          nroMes='06';
            break;
        case 'Julio':
          nroMes='07';
            break;
        case 'Agosto':
          nroMes='08';
            break;
        case 'Septiembre':
          nroMes='09';
            break;
        case 'Octubre':
          nroMes='10';
            break;
        case 'Noviembre':
          nroMes='11';
            break;
        default:
          nroMes='12';

        }
        var fechaFin = res[0] + '-' + nroMes;
        var d=  diasEnUnMes(res[0],nroMes);

        console.log('d',d);
      var formData = new FormData;

      formData.append('name', $('#modalImportacionMensual #archivoMes')[0].files[0].name);
      formData.append('fecha',fechaFin );
      formData.append('id_moneda', $('#monedaSelMes').val());
      formData.append('id_casino', $('#casinoSelMes').val());
      formData.append('diasDelmes', d);

      //Si subió archivoMes lo guarda
      if($('#modalImportacionMensual #archivoMes').attr('data-borrado') == 'false' && $('#modalImportacionMensual #archivoMes')[0].files[0] != null){
        formData.append('archivo' , $('#modalImportacionMensual #archivoMes')[0].files[0]);
      }

      $.ajax({
          type: "POST",
          url: url,
          data: formData,
          processData: false,
          contentType:false,
          cache:false,
          beforeSend: function(data){
            console.log('Empezó');
            $('#modalImportacionMensual').find('.modal-footer').children().hide();
            $('#modalImportacionMensual').find('.modal-body').children().hide();
            $('#mensajeErrorGral').hide();

            $('#iconoCargaMes').show();
          },
          complete: function(data){
            console.log('Terminó');
          },
          success: function (data) {

              $('#modalImportacionMensual').modal('hide');
              $('#mensajeExito h3').text('ÉXITO');
              $('#mensajeExito p').text('El archivo fue importado');
              $('#mensajeExito').show();
              $('#buscar-impMensuales').trigger('click');

              //$('#modalImportacionMensual #rowarchivoMes').find('.zona-file').append($('<input>').attr('data-borrado','false').css('type','file').attr('id','archivoMes'));
          },
          error: function (data) {

            var response = data.responseJSON.errors;

            $('#modalImportacionMensual').find('.modal-footer').children().show();
            $('#modalImportacionMensual #frmImportacion').show();
            $('#rowArchivoMes').show();

            $('#iconoCargaMes').hide();

           if(typeof response.fecha !== 'undefined'){
             mostrarErrorValidacion($('#B_fecha_imp_mes'),response.fecha[0],false);}

           if(typeof response.id_casino !== 'undefined'){
             mostrarErrorValidacion($('#casinoSelMes'),response.id_casino[0],false);}

           if(typeof response.id_moneda !== 'undefined'){
             mostrarErrorValidacion($('#monedaSelMes'),response.id_moneda[0],false);}

           if(typeof response.error !== 'undefined'){
                if(response.error.length > 0){
                  $('#mensajeErrorGral #span2').text(response.error[0]);
                  $('#mensajeErrorGral').show();
                }

              }
           }
      });
  });

//permite ver datos de la importación, ya validados
$(document).on('click','.infoImpM',function(e){

     var id=$(this).val();

     $.get('importacionMensual/verImportacion/' + id , function(data){

         $('#datosMensuales tr').remove();

         $('#fechaImpM').text(data.importacion.mes).prop('readonly',true);
         $('#casinoImpM').text(data.casino.nombre).prop('readonly',true);
         $('#monedaImpM').text(data.moneda.descripcion).prop('readonly',true);
         $('#observacionesImpM').val(data.importacion.observacion).prop('readonly',true);

         for (var i = 0; i < data.detalles.length; i++) {

             var fila=  generarFilaInfo(data.detalles[i]);
             $('#datosMensuales').append(fila);
       }
       var totales= generarFilaTotales(data.importacion);
       $('#datosMensuales').append(totales);

       $('#modalInfoMensual').modal('show');


     })
});

$(document).on('click', '.obsImpM', function(e) {
  e.preventDefault();

  $('#modalValidarMensual').modal('show');
  $('#mensajeExito').hide();
  $('#observacionesValidar').val('');
  $('#observacionesValidar').prop('readonly',false);

  $('#datosMensualesValidar tr').remove();

  var id_imp=$(this).val();
  $('#validarMes').val(id_imp);

  $.get('importacionMensual/verImportacion/' + id_imp , function(data){


        $('#fechaValidar').val(data.importacion.mes).prop('readonly',true);
        $('#casinoValidar').val(data.casino.nombre).prop('readonly',true);
        $('#monedaValidar').val(data.moneda.descripcion).prop('readonly',true);

        for (var i = 0; i < data.detalles.length; i++) {

          var fila=  generarFilaValidar(data.detalles[i]);
          $('#datosMensualesValidar').append(fila);

      }
    })
});

$('#validarMes').on('click', function(e){

  e.preventDefault();

  var formData = {
    id_importacion: $(this).val(),
    observacion: $('#observacionesValidar').val(),

  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'importacionMensual/guardar',
      data: formData,
      dataType: 'json',

      success: function (data){
        //trigger boton buscar
        $('#buscar-impMensuales').trigger('click');
        $('#modalValidarMensual').modal('hide');

        $('#mensajeExito h3').text('VALIDADO');
        $('#mensajeExito p').text(' ');
        $('#mensajeExito').show();
      },
      error: function(data){
      },
  })

})

$(document).on('click','.eliminarMes',function(e){

   var id=$(this).val();

   $.get('importacionMensual/eliminarImportacion/' + id , function(data){

     if(data==1){
       $('#mensajeExito h3').text('ARCHIVO ELIMINADO');
       $('#mensajeExito p').text(' ');
       $('#mensajeExito').show();

       $('#buscar-impMensuales').trigger('click');

     }
   })
});


function handleFileSelect(evt) {
    var files = evt.target.files; // FileList object

    // files is a FileList of File objects. List some properties.
    var output = [];
    for (var i = 0, f; f = files[i]; i++) {

    var reader = new FileReader();

    // Closure to capture the file information.
    reader.onload = (function(theFile) {
      return function(e) {
        // Render thumbnail.
        // var span = document.createElement('span');
        // span.innerHTML = ['<img class="thumb" src="', e.target.result,
        //                   '" title="', escape(theFile.name), '"/>'].join('');
        // //document.getElementById('list').insertBefore(span, null);
      };
    })(f);

    // Read in the image file as a data URL.
    reader.readAsDataURL(f);}
    $('#btn-guardarMensual').show();

}

function habilitarInputMensual(){
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionMensual #archivoMes')[0].files[0] = null;
  $('#modalImportacionMensual #archivoMes').attr('data-borrado','false');
  $("#modalImportacionMensual #archivoMes").fileinput('destroy').fileinput({
      language: 'es',
    //       showPreview: false,
          // allowedFileExtensions: ["csv", "txt"],
    //       elErrorContainer: "#alertaarchivoMes"
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
  });
}

function generarFilaTablaInicial(data){
  var fila = $('#moldeFilaImpM').clone();

    fila.removeAttr('id');
    fila.attr('id', data.id_importacion_mensual_mesas);

    var mes=data.fecha_mes.split('-');
    var nombreMes;

    switch (mes[1]) {
      case '01':
        nombreMes='Enero';
        break;
      case '02':
        nombreMes='Febrero';
          break;
      case '03':
        nombreMes='Marzo';
          break;
      case '04':
        nombreMes='Abril';
          break;
      case '05':
        nombreMes='Mayo';
          break;
      case '06':
        nombreMes='Junio';
          break;
      case '07':
        nombreMes='Julio';
          break;
      case '08':
        nombreMes='Agosto';
          break;
      case '09':
        nombreMes='Septiembre';
          break;
      case '10':
        nombreMes='Octubre';
          break;
      case '11':
        nombreMes='Noviembre';
          break;
      default:
        nombreMes='Diciembre';
    }

    fila.find('.m_fecha').text(nombreMes + ' ' + mes[0]);
    fila.find('.m_casino').text(data.nombre);
    fila.find('.m_moneda').text(data.descripcion);
    if(data.diferencias == 0){
      fila.find('.m_dif').append($('<i>').addClass('fas fa-check-circle').css('color', '#4CAF50').css('text-align','center'));


    }else{
      fila.find('.m_dif').append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F').css('text-align','center'));

    }
    if(data.validado==1){
      fila.find('.m_accion').find('.infoImpM').val(data.id_importacion_mensual_mesas).show();
      fila.find('.m_accion').find('.infoImpM').css('cssText','text-align:center !important');
      fila.find('.m_accion').find('.obsImpM').hide();
      fila.find('.m_accion').find('.eliminarMes').hide();
    }
    else{
      fila.find('.m_accion').find('.obsImpM').val(data.id_importacion_mensual_mesas).show();
      fila.find('.m_accion').find('.eliminarMes').val(data.id_importacion_mensual_mesas).show();
      fila.find('.m_accion').find('.infoImpM').hide();
      fila.find('.m_accion').find('.obsImpM').css('cssText','text-align:center !important');
      fila.find('.m_accion').find('.eliminarMes').css('cssText','text-align:center !important');
    }

  fila.css('display', 'block');

  return fila;

}

//genera las filas a la tabla dentro del modal ver
function generarFilaInfo(data){

    var fila = $('#moldeInfoMensual').clone();
      fila.removeAttr('id');
      fila.attr('id', data.id_importacion_mensual_mesas);

      fila.find('.ver_fecha').text(data.fecha_dia);
      fila.find('.ver_drop').text(data.total_diario);
      fila.find('.ver_utilidad').text(data.utilidad);
      fila.find('.ver_reposiciones').text(data.reposiciones_dia);
      fila.find('.ver_retiros').text(data.retiros_dia);
      fila.find('.ver_hold').text(data.hold);

      // if(data.diferencia == 0){
      //   fila.find('.d_accion').find('.imprimirImpD').hide();
      //   fila.find('.d_accion').find('.infoImpD').show();

      fila.css('display', '');
      $('#mostrarTablaVerMensual').css('display','block');

    return fila;

};

function generarFilaTotales(data){

    var fila = $('#moldeInfoMensual').clone();
      fila.removeAttr('id');
      fila.attr('id', data.id_importacion_mensual_mesas);

      fila.find('.ver_fecha').text('TOTALES').css('cssText','padding:5px !important;font-weight:bold;text-align:center');;
      fila.find('.ver_drop').text(data.total_drop_mensual).css('cssText','padding:5px !important;font-weight:bold;text-align:center');;
      fila.find('.ver_utilidad').text(data.total_utilidad_mensual).css('cssText','padding:5px !important;font-weight:bold;text-align:center');;
      fila.find('.ver_reposiciones').text(data.reposiciones_mes).css('cssText','padding:5px !important;font-weight:bold;text-align:center');;
      fila.find('.ver_retiros').text(data.retiros_mes).css('cssText','padding:5px !important;font-weight:bold;text-align:center');;
      fila.find('.ver_hold').text('-').css('cssText','padding:5px !important;font-weight:bold;text-align:center');
      fila.css('cssText','background-color:#aaa; color:black;');
      fila.css('display', '');
      $('#mostrarTablaVerMensual').css('display','block');

    return fila;
};

function generarFilaValidar(data){

    var fila = $('#moldeValidarMensual').clone();
      fila.removeAttr('id');
      fila.attr('id', data.id_importacion_mensual_mesas);

      fila.find('.validar_fecha').text(data.fecha_dia);
      fila.find('.validar_drop').text(data.total_diario);
      fila.find('.validar_utilidad').text(data.utilidad);
      fila.find('.validar_hold').text(data.hold);

      // if(data.diferencia == 0){
      //   fila.find('.d_accion').find('.imprimirImpD').hide();
      //   fila.find('.d_accion').find('.infoImpD').show();

      fila.css('display', 'block');

    return fila;

};

function diasEnUnMes(anio, mes) {
	return new Date(anio, mes, 0).getDate();
}
