$(document).ready(function() {
  $('#barraImportaciones').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').hide();
  $('#barraImportaciones').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#barraImportaciones').addClass('opcionesSeleccionado');

    $('#filtroCas').val('0');
    $('#B_fecha_filtro').val('');
    $('#filtroMon').val('0');

    $('#mensajeExito').hide();
    $('#mensajeError').hide();

    $(function(){
        $('#dtpFechaImp').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'yyyy-mm-dd',
          pickerPosition: "bottom-left",
          startView: 4,
          minView: 2,
          container:$('#modalImportacionDiaria'),
        });
    });
    $(function(){
        $('#dtpFecha').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'yyyy-mm-dd',
          pickerPosition: "bottom-left",
          startView: 4,
          minView: 2
        });
    });
    $('.tituloSeccionPantalla').hide();

    $('#pestImportaciones').show();
    $('#pestImportaciones').css('display','inline-block');

    //pestañas
      $(".tab_content").hide(); //Hide all content
      	$("ul.pestImportaciones li:first").addClass("active").show(); //Activate first tab
      	$(".tab_content:first").show(); //Show first tab content

    $('#buscar-importacionesDiarias').trigger('click',[1,10,'fecha','desc']);

});

//PESTAÑAS
$("ul.pestImportaciones li").click(function() {

    $("ul.pestImportaciones li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content
                console.log(activeTab);
    if(activeTab == '#pest_mensual'){
      $('#buscar-impMensuales').trigger('click',[1,5,'fecha_mes','desc']);
    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});

document.getElementById('archivo').addEventListener('change', handleFileSelect, false);

//boton grande de importar
$('#btn-importar').on('click', function(e){

    e.preventDefault();
    $('#cotizacion_diaria').prop('readonly',true);
    $('#mensajeErrorJuegos').hide();
    // $('.modalNuevo h3').text('| IMPORTAR CONTADORES');
    $('#modalImportacionDiaria .modalNuevo').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');

    ocultarErrorValidacion($('#B_fecha_imp'));
    ocultarErrorValidacion($('#monedaSel'));
    ocultarErrorValidacion($('#casinoSel'));
    $('#B_fecha_imp').val("");
    $('#casinoSel').val('0');
    $('#monedaSel').val('0');
    $('#cotizacion_diaria').val("");
    $('#modalImportacionDiaria').find('.modal-footer').children().show();
    $('#modalImportacionDiaria').find('.modal-body').children().show();
    $('#iconoCarga').hide();

    //Mostrar: rowArchivo
    $('#modalImportacionDiaria #rowArchivo').show();
    $('#modalImportacionDiaria').modal('show');

    //Ocultar: rowFecha, mensajes, iconoCarga
    $('#modalImportacionDiaria #rowFecha').hide();
    $('#modalImportacionDiaria #mensajeError').hide();
    $('#modalImportacionDiaria #mensajeInvalido').hide();
    $('#modalImportacionDiaria #mensajeInformacion').hide();

    habilitarInputDiario();

    $('#mensajeExito').hide();

    //Ocultar botón SUBIR
    $('#iconoCarga').hide();
    $('#btn-guardarDiario').hide();
});

$(document).on('change','#monedaSel',function(){

  if($(this).val() == 2){

    $('#cotizacion_diaria').val('');
    $('#cotizacion_diaria').prop('readonly',false);
  }

  if($(this).val() == 1 || $(this).val() == 0){

    $('#cotizacion_diaria').val('');
    $('#cotizacion_diaria').prop('readonly',true);
  }
});

$(document).on('click', '#archivo', function(){

  $('#modalImportacionDiaria #mensajeInvalido').hide();

})

//presiona subir en el modal de importación
$('#btn-guardarDiario').on('click', function(e){

    $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });
        e.preventDefault();
      var url = 'importacionDiaria/importar';

      var formData = new FormData;

      formData.append('name', $('#modalImportacionDiaria #archivo')[0].files[0].name);
      formData.append('fecha', $('#B_fecha_imp').val());
      formData.append('id_moneda', $('#monedaSel').val());
      formData.append('id_casino', $('#casinoSel').val());
      formData.append('cotizacion_diaria', $('#cotizacion_diaria').val());

      //Si subió archivo lo guarda
      if($('#modalImportacionDiaria #archivo').attr('data-borrado') == 'false' && $('#modalImportacionDiaria #archivo')[0].files[0] != null){
        formData.append('archivo' , $('#modalImportacionDiaria #archivo')[0].files[0]);
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
            $('#modalImportacionDiaria').find('.modal-footer').children().hide();
            $('#modalImportacionDiaria').find('.modal-body').children().hide();

            $('#mensajeErrorJuegos').hide();

            $('#iconoCarga').show();
          },
          complete: function(data){
            console.log('Terminó');
          },
          success: function (data) {

              $('#modalImportacionDiaria').modal('hide');
              $('#mensajeExito h3').text('ÉXITO');
              $('#mensajeExito p').text('El archivo fue importado');
              $('#mensajeExito').show();
              $('#buscar-importacionesDiarias').trigger('click',[1,10,'fecha','desc']);

              //$('#modalImportacionDiaria #rowArchivo').find('.zona-file').append($('<input>').attr('data-borrado','false').css('type','file').attr('id','archivo'));
          },
          error: function (data) {
            ///debería mostrar el mensaje y nada más.
            console.log('error',data);
            var response = data.responseJSON;

            $('#modalImportacionDiaria').find('.modal-footer').children().show();
            $('#frmImportacion').show();
            $('#rowArchivo').show();

            $('#iconoCarga').hide();

               if(typeof response.fecha !== 'undefined'){
                 mostrarErrorValidacion($('#B_fecha_imp'),response.fecha[0],false);}

               if(typeof response.id_casino !== 'undefined'){
                 mostrarErrorValidacion($('#casinoSel'),response.id_casino[0],false);}

               if(typeof response.id_moneda !== 'undefined'){
                 mostrarErrorValidacion($('#monedaSel'),response.id_moneda[0],false);}

               if(typeof response.error !== 'undefined'){

                  if(response.error.length > 0){

                        $('#mensajeErrorJuegos #span').text(response.error[0]);

                        $('#mensajeErrorJuegos').show();

                    }
               }

          }
      });
  });


$('#modalImportacionDiaria #archivo').on('fileerror', function(event, data, msg) {
     //$('#modalImportacionDiaria #rowMoneda').hide();
     $('#modalImportacionDiaria #mensajeInformacion').hide();
     $('#modalImportacionDiaria #mensajeInvalido').show();
     $('#modalImportacionDiaria #mensajeInvalido #span').text(msg);
     //Ocultar botón SUBIR
    // $('#guardar-observacion').hide();

  });

$('#modalImportacionDiaria #archivo').on('fileselect', function(event) {
      $('#modalImportacionDiaria #archivo').attr('data-borrado','false');

      // Se lee el archivo guardado en el input de tipo 'file'.

      var reader = new FileReader();
      reader.readAsText($('#modalImportacionDiaria #archivo')[0].files[0]);
      //reader.onload = procesarDatosBeneficios;
  });

  //btn buscar ed filtros

$('#buscar-importacionesDiarias').click(function(e,pagina,page_size,columna,orden){

    e.preventDefault();

    //Fix error cuando librería saca los selectores
    if(isNaN($('#herramientasPaginacion').getPageSize())){
      var size = 10; // por defecto
    }
    else {
      var size = $('#herramientasPaginacion').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;

    if(sort_by == null){ // limpio las columnas
      $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

    var formData= {
      fecha: $('#B_fecha_filtro').val(),
      id_moneda:$('#filtroMon').val(),
      casino: $('#filtroCas').val(),
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
        url: 'importacionDiaria/filtros',
        data: formData,
        dataType: 'json',

        success: function (data){
          $('#tablaResultados tbody tr').remove();

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.importaciones.total,clickIndice);


          for (var i = 0; i < data.importaciones.data.length; i++) {

              var fila=  generarFilaImportaciones(data.importaciones.data[i]);
              $('#cuerpoTablaImpD').append(fila);
          }
          $('#herramientasPaginacion').generarIndices(page_number,page_size,data.importaciones.total,clickIndice);


        },
        error: function(data){
        },
    })

});

//PAGINACION
$(document).on('click','#tablaResultados thead tr th[value]',function(e){

  $('#tablaResultados th').removeClass('activa');

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
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndice(e,pageNumber,tam){

  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#buscar-importacionesDiarias').trigger('click',[pageNumber,tam,columna,orden]);
}

//fin PAGINACION

//genera filas de la pantalla principal de importaciones diarias

//boton ver imp de cada fila
$(document).on('click', '.obsImpD', function(e) {
  e.preventDefault();

  $('#modalVerImportacion').modal('show');
  $('#mensajeExito').hide();
  $('#observacionesImpD').val('');
  $('#selectMesa').val(1);

  $('#datosImpDiarios > tr').remove();


  var id_imp=$(this).val();

  $('#guardar-observacion').val(id_imp);

  $.get('importacionDiaria/verImportacion/' + id_imp + '/' + 'RULETA', function(data){

      $('#fechaImpD').val(data.importacion.fecha);
      $('#casinoImpD').val(data.casino.nombre);
      $('#monedaImpD').val(data.moneda.descripcion);

        for (var i = 0; i < data.detalles.length; i++) {

            var fila=  generarFilaVerImpValidar(data.detalles[i]);
            $('#datosImpDiarios').append(fila);
        }
  });

});

//si cambia el select dentro del modal de ver importacion
$(document).on('change','#selectMesa',function(){
  console.log('entra');
    var id_imp=$('#guardar-observacion').val();
    $('#datosImpDiarios tr').remove();

    if($(this).val() == 2){ //elige cartas, cambio los datos de la tabla
      $.get('importacionDiaria/verImportacion/' + id_imp + '/' + 'CARTAS', function(data){

        $('#fechaImpD').val(data.importacion.fecha);
        $('#casinoImpD').val(data.casino.nombre);
        $('#monedaImpD').val(data.moneda.descripcion);

            for (var i = 0; i < data.detalles.length; i++) {

                var fila=  generarFilaVerImpValidar(data.detalles[i]);
                $('#datosImpDiarios').append(fila);
            }
      });
    }

    if($(this).val() == 3){//elige dados, cambio los datos de la tabla
      $.get('importacionDiaria/verImportacion/' + id_imp + '/' + 'DADOS', function(data){

        $('#fechaImpD').val(data.importacion.fecha);
        $('#casinoImpD').val(data.casino.nombre);
        $('#monedaImpD').val(data.moneda.descripcion);

            for (var i = 0; i < data.detalles.length; i++) {

                var fila=  generarFilaVerImpValidar(data.detalles[i]);
                $('#datosImpDiarios').append(fila);
            }
      });
    }

    if($(this).val() == 1){//elige ruleta, cambio los datos de la tabla
      $.get('importacionDiaria/verImportacion/' + id_imp + '/' + 'RULETA', function(data){

        $('#fechaImpD').val(data.importacion.fecha);
        $('#casinoImpD').val(data.casino.nombre);
        $('#monedaImpD').val(data.moneda.descripcion);

            for (var i = 0; i < data.detalles.length; i++) {

                var fila=  generarFilaVerImpValidar(data.detalles[i]);
                $('#datosImpDiarios').append(fila);
            }
      });
    }
});

$('#guardar-observacion').on('click', function(e){

  e.preventDefault();

  var formData = {
    id_importacion: $(this).val(),
    observacion: $('#observacionesImpD').val(),

  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'importacionDiaria/guardar',
      data: formData,
      dataType: 'json',

      success: function (data){
        //trigger boton buscar
        $('#buscar-importacionesDiarias').trigger('click',[1,10,'fecha','desc']);

        $('#modalVerImportacion').modal('hide');

        $('#mensajeExito h3').text('VALIDADO');
        $('#mensajeExito p').text(' ');
        $('#mensajeExito').show();
      },
      error: function(data){
      },
  })

})


//ver datos de importaciones guardados
 $(document).on('click','.infoImpD',function(e){

    var id=$(this).val();
    var tipo=$('#selectMesaInfo').val();

    $.get('importacionDiaria/verImportacion/' + id + '/' + 'RULETA', function(data){

        $('#datosInfoDiarios  tr').remove();

        $('#fechaInfo').val(data.importacion.fecha).prop('readonly',true);
        $('#casinoInfo').val(data.casino.nombre).prop('readonly',true);
        $('#monedaInfo').val(data.moneda.descripcion).prop('readonly',true);
        $('#guardar-observacion-info').val(data.importacion.id_importacion_diaria_mesas);
        $('#guardar-observacion-info').hide();

        for (var i = 0; i < data.detalles.length; i++) {

            var fila=  generarFilaVerImp(data.detalles[i]);
            $('#datosInfoDiarios').append(fila);
      }

      $('#modalInfoImportacion').modal('show');

    })
 });

 $(document).on('change','#selectMesaInfo',function(){

     var id_imp=$('#guardar-observacion-info').val();
     $('#datosInfoDiarios tr').remove();

     if($(this).val() == 2){ //elige cartas, cambio los datos de la tabla
       $.get('importacionDiaria/verImportacion/' + id_imp + '/' + 'CARTAS', function(data){

         $('#fechaInfo').val(data.importacion.fecha);
         $('#casinoInfo').val(data.casino.nombre);
         $('#monedaInfo').val(data.moneda.descripcion);

             for (var i = 0; i < data.detalles.length; i++) {

                 var fila=  generarFilaVerImp(data.detalles[i]);
                 $('#datosInfoDiarios').append(fila);
             }
       });
     }

     if($(this).val() == 3){//elige dados, cambio los datos de la tabla
       $.get('importacionDiaria/verImportacion/' + id_imp + '/' + 'DADOS', function(data){

         $('#fechaInfo').val(data.importacion.fecha);
         $('#casinoInfo').val(data.casino.nombre);
         $('#monedaInfo').val(data.moneda.descripcion);

             for (var i = 0; i < data.detalles.length; i++) {

                 var fila=  generarFilaVerImp(data.detalles[i]);
                 $('#datosInfoDiarios').append(fila);
             }
       });
     }

     if($(this).val() == 1){//elige ruleta, cambio los datos de la tabla
       $.get('importacionDiaria/verImportacion/' + id_imp + '/' + 'RULETA', function(data){

         $('#fechaInfo').val(data.importacion.fecha);
         $('#casinoInfo').val(data.casino.nombre);
         $('#monedaInfo').val(data.moneda.descripcion);

             for (var i = 0; i < data.detalles.length; i++) {

                 var fila=  generarFilaVerImp(data.detalles[i]);
                 $('#datosInfoDiarios').append(fila);
             }
       });
     }
 });

 $(document).on('click','.eliminarDia',function(e){

    var id=$(this).val();

    $.get('importacionDiaria/eliminarImportacion/' + id , function(data){

      if(data==1){
        $('#mensajeExito h3').text('ARCHIVO ELIMINADO');
        $('#mensajeExito p').text(' ');
        $('#mensajeExito').show();

        $('#cuerpoTablaImpD').find('#'+ id).remove();
      }
    })
});


//evento de seleccionar el archivo a importar
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
     $('#btn-guardarDiario').show();

 }

 function generarFilaImportaciones(data){


     var fila = $('#moldeFilaImpD').clone();
       fila.removeAttr('id');
       fila.attr('id', data.id_importacion_diaria_mesas);

       fila.find('.d_fecha').text(data.fecha);
       fila.find('.d_casino').text(data.nombre);
       fila.find('.d_moneda').text(data.descripcion);
       if(data.diferencias == 0){
         fila.find('.d_dif').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50').css('text-align','center'));


       }else{
         fila.find('.d_dif').append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F').css('text-align','center'));

       }
       if(data.validado==1){
         fila.find('.d_accion').find('.infoImpD').val(data.id_importacion_diaria_mesas).show();
         fila.find('.d_accion').find('.obsImpD').hide();
         fila.find('.d_accion').find('.eliminarDia').hide();

       }
       else{
         fila.find('.d_accion').find('.obsImpD').val(data.id_importacion_diaria_mesas).show();
         fila.find('.d_accion').find('.eliminarDia').val(data.id_importacion_diaria_mesas).show();
         fila.find('.d_accion').find('.infoImpD').hide();
       }

     fila.css('display', 'block');

   return fila;

 }

 function habilitarInputDiario(){
   //Inicializa el fileinput para cargar los CSV
   $('#modalImportacionDiaria #archivo')[0].files[0] = null;
   $('#modalImportacionDiaria #archivo').attr('data-borrado','false');
   $("#modalImportacionDiaria #archivo").fileinput('destroy').fileinput({
       language: 'es',
     //       showPreview: false,
           // allowedFileExtensions: ["csv", "txt"],
     //       elErrorContainer: "#alertaArchivo"
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

 //genera las filas a la tabla dentro del modal ver
 function generarFilaVerImp(data){

     var fila = $('#moldeInfoDiarios').clone();
       fila.removeAttr('id');
       fila.attr('id', data.id_importacion_diaria_mesas);

       fila.find('.info_juego').text(data.nombre_juego);
       fila.find('.info_mesa').text(data.nro_mesa);
       fila.find('.info_drop').text(data.droop);
       fila.find('.info_reposiciones').text(data.reposiciones);
       fila.find('.info_retiros').text(data.retiros);
       fila.find('.info_utilidad').text(data.utilidad);
       fila.find('.info_hold').text(data.hold);

       // if(data.diferencia == 0){
       //   fila.find('.d_accion').find('.imprimirImpD').hide();
       //   fila.find('.d_accion').find('.infoImpD').show();

       fila.css('display', '');
       $('#mostrarTablaver').css('display','block');

     return fila;

 }
 function generarFilaVerImpValidar(data){

     var fila = $('#moldeImpDiarios').clone();
       fila.removeAttr('id');
       fila.attr('id', data.id_importacion_diaria_mesas);

       fila.find('.v_juego').text(data.nombre_juego);
       fila.find('.v_mesa').text(data.nro_mesa);
       fila.find('.v_drop').text(data.droop);
       fila.find('.v_reposiciones').text(data.reposiciones);
       fila.find('.v_retiros').text(data.retiros);
       fila.find('.v_utilidad').text(data.utilidad);
       fila.find('.v_hold').text(data.hold);

       // if(data.diferencia == 0){
       //   fila.find('.d_accion').find('.imprimirImpD').hide();
       //   fila.find('.d_accion').find('.infoImpD').show();

       fila.css('display', '');
       $('#mostrarTablaValidar').css('display','block');

     return fila;

 }
