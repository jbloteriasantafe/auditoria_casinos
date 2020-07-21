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
                const id_autoexcluido = resultados[i].id_autoexcluido;
                const id_importacion = resultados[i].id_importacion;
                const tipo_archivo = resultados[i].tipo_archivo;
                const link = 'autoexclusion/mostrarArchivo/' + id_importacion + '/' + tipo_archivo;
                const img = $('<embed>')
                .addClass('fotoMiniatura')
                .attr('id-autoexcluido',id_autoexcluido)
                .attr('data-id-importacion',id_importacion)
                .attr('data-tipo-archivo',tipo_archivo)
                .attr('src',link +'#toolbar=0');

                const a  = $('<a>').attr('href',link).text('LINK').attr('target','_blank')
                .css('background-color','white');
                const div = $('<div>')
                .addClass('thumbnail')
                .append(img)
                .append($('<center>').append(a).css('padding-top','5px'));

                div.click(function(){
                  const big_img = img.clone().removeClass('fotoMiniatura').removeAttr('style');
                  clickImagenGaleria(id_autoexcluido,big_img);
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
    //Reemplazo el IMG que pone por un embed...
    $('#gallery div:first').click();
});

function clickImagenGaleria(id,embed){
  const max_time = function(src){
    //Tiempo del request, para aproximar cuanto tarda en cargar la imagen...
    //Esto es heuristico pero deberia funcionar en la mayoria de los casos.
    //Ademas que en teoria el browser deberia cachear... por lo que estariamos sobrados
    const resourceList = window.performance.getEntriesByType("resource");
    let max_time = -1;
    resourceList.forEach(r => {
      if(r.duration > max_time && r.initiatorType == "embed" && r.name==src) max_time = r.duration;
    });
    return max_time < 0? 500 : max_time;
  }
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

        //Le meto un minimo de 500ms por las moscas...
        const time = Math.max(max_time(embed.attr('src'))*1.25,500);
        setTimeout(function(){//Reemplazo la imagen por un embed, necesitamos mostrar PDFs tambien...
          const height = $('#gallery-viewer').height();
          embed.css('max-height',height+'px');
          $('#gallery-viewer').empty().append(embed);
          setTimeout(function(){
            const embed_height = embed.height();
            //Si no ocupo todo la altura, es un PDF y hay que setearle la altura/ancho a mano
            //A las imagenes no les seteamos height/width 100% para no destruir el aspect ratio
            if(embed_height != height){
              embed.css('height','100%').css('width','100%');
            }
          },time);
        },time);
    },
    error: function(data) {
        console.log('Error:', data);
    }
  });
}
