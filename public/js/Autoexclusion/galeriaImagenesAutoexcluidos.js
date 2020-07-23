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

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Galería de imágenes');
  $('#wrapper').hide();

  //Si en la url viene con un dni, en el view se le carga un valor por defecto. Lo buscamos
  if($('#buscadorDni').val().length > 0) $('#btn-buscar').click();
});

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
        url: '/galeriaImagenesAutoexcluidos/getPathsFotosAutoexcluidos',
        data: formData,
        dataType: 'json',
        async:false,
        success: function(resultados) {
            $('#wrapper').toggle(resultados.length > 0);
            for (var i = 0; i < resultados.length; i++) {
                const id_autoexcluido = resultados[i].id_autoexcluido;
                const id_importacion = resultados[i].id_importacion;
                const tipo_archivo = resultados[i].tipo_archivo;
                const nombre = resultados[i].nombre;
                const link = '/autoexclusion/mostrarArchivo/' + id_importacion + '/' + tipo_archivo;
                const img = $('<embed>')
                .addClass('fotoMiniatura')
                .attr('id-autoexcluido',id_autoexcluido)
                .attr('data-id-importacion',id_importacion)
                .attr('data-tipo-archivo',tipo_archivo)
                .attr('data-nombre',nombre)
                .attr('src',link +'#toolbar=0');

                const a  = $('<a>').attr('href',link).text('LINK').attr('target','_blank')
                .css('background-color','white');
                const div = $('<div>')
                .addClass('thumbnail')
                .append(img)
                .append($('<center>').append(a).css('padding-top','5px'));

                div.click(function(){
                  const big_img = img.clone().removeClass('fotoMiniatura').removeAttr('style');
                  clickImagenGaleria(id_autoexcluido,big_img,nombre);
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

function clickImagenGaleria(id,embed,nombre){
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
    url: '/galeriaImagenesAutoexcluidos/getDatosUnAutoexcluido/' + id,
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

        const indice_ext = nombre.lastIndexOf('.');
        if(indice_ext != -1){
          const ext = nombre.toLowerCase().substr(indice_ext+1);
          if(ext == 'png' || ext == 'jpg' || ext == 'jpeg'){
            embed.css('max-height','100%');
          }
          else{//PDF o DOC o algun otro
            embed.css('height','100%').css('width','100%');
          }
        }
        else{//Sin extension
          embed.css('height','100%').css('width','100%');
        }
        //Le meto un minimo de 500ms por las moscas...
        const time = Math.max(max_time(embed.attr('src'))*1.25,500);
        setTimeout(function(){
          $('#gallery-viewer').empty().append(embed);
        },time);
    },
    error: function(data) {
        console.log('Error:', data);
    }
  });
}
