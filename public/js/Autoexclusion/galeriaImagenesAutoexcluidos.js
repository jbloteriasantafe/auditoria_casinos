$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Galería de imágenes');
  $('#wrapper').hide();
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
            $('#wrapper').toggle(resultados.length > 0);
            for (var i = 0; i < resultados.length; i++) {
                const id = resultados[i].id_ae;
                const img = $('<img>')
                .addClass('fotoMiniatura')
                .attr('id',id)
                .attr('src',resultados[i].path);

                const div = $('<div>')
                .addClass('thumbnail')
                .append(img);

                div.click(function(){
                  clickImagenGaleria(id);
                });

                $('#gallery').append(div);
            }
        },
        error: function(data) {
            console.log('Error:', data);
        }
    });
    $('#gallery').gallery({
      'rows': 1,
      'cols': 5
    });
});

function clickImagenGaleria(id){
  $.ajax({
    type: 'GET',
    url: 'http://' + window.location.host + '/galeriaImagenesAutoexcluidos/getDatosUnAutoexcluido/' + id,
    async:false,
    success: function(resultado) {
        let res = resultado[0];
        document.getElementById("apellido").innerHTML = res.apellido;
        document.getElementById("nombres").innerHTML = res.nombres;
        document.getElementById("dni").innerHTML = res.nro_dni;
        document.getElementById("casino").innerHTML = res.casino;
        document.getElementById("estado").innerHTML = res.estado;
        document.getElementById("fecha_ae").innerHTML = res.fecha_ae;
        document.getElementById("vencimiento").innerHTML = res.fecha_vencimiento;
        document.getElementById("fecha_revocacion").innerHTML = res.fecha_revocacion_ae;
        document.getElementById("fecha_cierre").innerHTML = res.fecha_cierre_ae;
    },
    error: function(data) {
        console.log('Error:', data);
    }
  });
}
