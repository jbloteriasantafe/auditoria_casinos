$(function(){

$("#contenedorFormulario input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      const ingresar = $('#btnIngresar').click();
      if(ingresar.length == 0){//Si esta en un formulario de resetear la contraseña 
        $(this).closest('form').find('button[type="submit"]').click();
      }
    }
});

history.pushState(null, null, 'login');
window.addEventListener('popstate', function(event) {
  history.pushState(null, null, 'login');
});

$('#btnIngresar').click(function(e){
  e.preventDefault();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'login',
    data: {
      user_name: $('#user_name').val(),
      password: $('#password').val(),
    },
    success: function (data) {
      window.location.href = data?.redirect_to ?? 'inicio';
    },
    error: function (data) {
      console.log(data);
      const response = data.responseJSON ?? {};
      const mensaje = (response.user_name ?? response.password ?? response.existe ?? []).join(', ');
      $('#alertaLogin span').text(mensaje);
      $('#alertaLogin').toggle(!!mensaje?.length);
    }
  });
});



});
