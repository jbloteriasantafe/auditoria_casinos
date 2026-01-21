$(function(){

$("#contenedorFormulario input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btnIngresar,#btnOlvideMiContraseñaVerificarCorreo').click();
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

$('[data-js-restaurar]').click(function(e){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  e.preventDefault();
  $.ajax({
    type: "POST",
    url: 'login',
    data: {
      restaurar: $(this).attr('data-js-restaurar'),
      email: $('#email').val(),
      code: $('#code').val(),
    },
    success: function (data) {
      const mensaje = data?.mensaje?.join(', ') ?? '';
      $('#alertaLogin span').text(mensaje);
      $('#alertaLogin').toggle(!!mensaje?.length);
    },
    error: function (data) {
      console.log(data);
      const resp = data?.responseJSON ?? {};
      const mensaje = (resp.restaurar ?? resp.email ?? resp.code ?? [])?.join(', ') ?? ''; 
      $('#alertaLogin span').text(mensaje);
      $('#alertaLogin').toggle(!!mensaje?.length);
    }
  });
});

});
