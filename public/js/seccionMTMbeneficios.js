$(document).ready(function(){

  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Informes de tragamonedas');
  $('#opcInformesMTM').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInformesMTM').addClass('opcionesSeleccionado');

});

//MUESTRA LA PLANILLA
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();

    window.open('informesMTM/generarPlanilla/' + $(this).attr('data-anio') +"/"+ $(this).attr('data-mes') +"/"+ $(this).attr('data-casino') +"/"+ $(this).attr('data-moneda'),'_blank');

    // console.log($(this).attr('data-anio') +"/"+ $(this).attr('data-mes') +"/"+ $(this).attr('data-casino') +"/"+ $(this).attr('data-moneda'));
    // $("#cargaArchivo").fileinput('destroy').fileinput({
    //   language: 'es',
    //   showRemove: false,
    //   showUpload: false,
    //   showCaption: false,
    //   showZoom: false,
    //   browseClass: "btn btn-primary",
    //   previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
    //   overwriteInitial: true,
    //   initialPreviewAsData: true,
    //   initialPreview: [
    //     // "/informesMTM/generarPlanilla/2017/06/1/1",
    //     "/informesMTM/generarPlanilla/" + $(this).attr('data-anio') +"/"+ $(this).attr('data-mes') +"/"+ $(this).attr('data-casino') +"/"+ $(this).attr('data-moneda'),
    //   ],
    //   initialPreviewConfig: [
    //     {type:'pdf', caption: '', size: 1, width: "1000px", url: "{$url}", key: 1},
    //   ],
    //   allowedFileExtensions: ['pdf'],
    // });
    //
    // $('#modalPlanilla').modal('show');
});
