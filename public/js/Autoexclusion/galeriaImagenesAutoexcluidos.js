$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Galería de imágenes');
  $('#wrapper').hide();

  //Si en la url viene con un dni, en el view se le carga un valor por defecto. Lo buscamos
  if($('#buscadorDni').val().length > 0) $('#btn-buscar').click();
});

$('#btn-buscar').click(function(e) {
  e.preventDefault();
  buscar(1);
});

$('#next').click(function(){
  buscar(parseInt($('#currpage').text())+1);
});
$('#prev').click(function(){
  buscar(parseInt($('#currpage').text())-1);
});

function buscar(pagina){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    const formData = {
        apellido: $('#buscadorApellido').val(),
        dni: $('#buscadorDni').val(),
        casino: $('#buscadorCasino').val(),
        page: pagina,
        size: 5,
    }

    $('#thumbs div[data-n]').empty();
    $.ajax({
        type: 'GET',
        url: '/galeriaImagenesAutoexcluidos/getPathsFotosAutoexcluidos',
        data: formData,
        dataType: 'json',
        async:false,
        success: function(resultados) {
            $('#galeria').toggle(resultados.pages > 0);
            $('#currpage').text(parseInt(resultados.page));
            $('#pages').text(resultados.pages);
            const data = resultados.data;
            for (let i = 0; i < data.length; i++) {
                const id_autoexcluido = data[i].id_autoexcluido;
                const nro_dni = data[i].nro_dni;
                const id_importacion = data[i].id_importacion;
                const tipo_archivo = data[i].tipo_archivo;
                const nombre = data[i].nombre;
                const link = '/galeriaImagenesAutoexcluidos/mostrarArchivo/' + id_importacion + '/' + tipo_archivo;
                const img = $('<embed>')
                .addClass('fotoMiniatura')
                .attr('id-autoexcluido',id_autoexcluido)
                .attr('nro-dni',nro_dni)
                .attr('data-id-importacion',id_importacion)
                .attr('data-tipo-archivo',tipo_archivo)
                .attr('data-nombre',nombre)
                .attr('src',link +'#toolbar=0');

                const div = $('<div>')
                .addClass('thumbnail')
                .append(img);

                div.click(function(){
                  const big_img = img.clone().removeClass('fotoMiniatura').removeAttr('style');
                  clickImagenGaleria(nro_dni,big_img,nombre);
                });
                $('#thumbs div[data-n="'+(i+1)+'"]').empty().append(div);
            }
        },
        error: function(data) {
            console.log('Error:', data);
        }
    });

    $('#thumbs div[data-n="1"] .thumbnail').click()
}

function clickImagenGaleria(nro_dni,embed,nombre){
  $.ajax({
    type: 'GET',
    url: '/galeriaImagenesAutoexcluidos/getDatosUnAutoexcluido/' + nro_dni,
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
        $('#viewer').empty().append(embed);
    },
    error: function(data) {
        console.log('Error:', data);
    }
  });
}
