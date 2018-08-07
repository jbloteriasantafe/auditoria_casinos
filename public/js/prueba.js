$('body').on('click', function(e) {
})
//Mostrar modal para agregar nuevo Casino
$('#boton').on('click' , function () {
  $.get('/pruebas/generarPlanillaPruebaDeJuego/6' , function(data) {
    var pdfAsDataUri = "data:application/pdf;base64,"+data;
    window.open(pdfAsDataUri);
  })
})

$('#mandarArchivo').click(function(e){
  e.preventDefault();

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })

  var formData=new FormData();
  formData.append('file',$('#cargaMasiva')[0].files[0]);
  formData.append('id_casino' , $('#contenedorCargaMasiva').val());

  $.ajax({
    type: "POST",
    url: 'prueba/actualizarMaestroRosario',
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    success: function (resultados) { //que pasa si no hubo error
      console.log(resultados);
    },
    error: function (data) { //que pasa si ocurrio error
      console.log('Error:', data);
    }
  });
});
