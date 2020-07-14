$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Galería de imágenes');

  $('#btn-buscar').trigger('click');

});

// some extra interface stuff... pay no attention to the man behind the curtain
var gallery = $('#gallery-wrapper'), setup = $('#setup-wrapper');
setup.hide();
$('#show-setup').click(function(){
  gallery.fadeOut(400,function(){
    setup.fadeIn();
  });
  return false;
});
$('#show-gallery').click(function(){
  setup.fadeOut(400,function(){
    gallery.fadeIn();
  });
  return false;
});
$('#nav-wrapper').jfollow('#followbox', 20);


$('#btn-buscar').click(function(e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    //elimino todos los divs de los thumbnails (por si si se hizo una búsqueda anterior)
    $('#gallery').find('div').each(function(index) {
       $(this).remove();
     });

    var formData = {
        apellido: $('#buscadorApellido').val(),
        dni: $('#buscadorDni').val(),
        casino: $('#buscadorCasino').val(),
    }

    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host + '/galeriaImagenesAutoexcluidos/getPathsFotosAutoexcluidos',
        data: formData,
        dataType: 'json',
        async:false,
        success: function(resultados) {
            for (var i = 0; i < resultados.length; i++) {
                $('#gallery')
                  .append($('<div>')
                  .addClass('thumbnail')
                    .append($('<img>')
                    .addClass('fotoMiniatura')
                    .attr('id',resultados[i].id_ae)
                    .attr('src',resultados[i].path)
                          )
                        )
            }
        },
        error: function(data) {
            console.log('Error:', data);
        }
    });

    $(document).ready(function(){
      // set up the gallery
      $('#gallery').gallery({
        'rows': 1,
        'cols': 5
      });

    });
});
