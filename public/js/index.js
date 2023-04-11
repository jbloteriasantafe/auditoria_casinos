$("#contenedorFormulario input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btnIngresar').click();
    }
});

history.pushState(null, null, 'login');
window.addEventListener('popstate', function(event) {
  history.pushState(null, null, 'login');
});

  $('#btnIngresar').click(function(e){

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    var formData = {
      user_name: $('#user_name').val(),
      password: $('#password').val(),
    }

    var type = "POST";
    var url = 'login';

    $.ajax({
        type: type,
        url: url,
        data: formData,
        success: function (data) {
          if(!jQuery.isEmptyObject(data)){
            window.location.href = data.redirect_to;
          }
          else{
            window.location.href = "inicio";
          }
        },
        error: function (data) {
          console.log(data);
          var response = data.responseJSON.errors;
            $('#alertaLogin').hide();
            $('#alertaLogin span').text("");
            if(typeof response.user_name !== 'undefined'){
              $('#alertaLogin span').text(response.user_name[0]);
              $('#alertaLogin').show();
            }
            else{
              if(typeof response.password !== 'undefined'){
                $('#alertaLogin span').text(response.password[0]);
                $('#alertaLogin').show();
              }
              else{
                if(typeof response.existe !== 'undefined'){
                  $('#alertaLogin span').text(response.existe[0]);
                  $('#alertaLogin').show();
                }
              }
            }
            console.log("Error: ", data);
        },
    });

  });
