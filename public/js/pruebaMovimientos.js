$(document).ready(function(){
    console.log('Anda!');
});


$('#btn-pruebaCarga').click(function(e){
  console.log('Manda!');
  e.preventDefault();

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var formData = {
    id_log_movimiento:7,
    maquinas:[{
      id_maquina: 8555}],
    carga_finalizada: 1
}



  console.log(formData);

  $.ajax({
      type: "POST",
      url: "pruebaMovimientos/pruebasVarias",
      data: formData,
      dataType: 'json',
      success: function (data) {

      },
      error: function (error) {

      },
    });
});
