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
        nombres: $('#buscadorNombres').val(),
        apellido: $('#buscadorApellido').val(),
        dni: $('#buscadorDni').val(),
        casino: $('#buscadorCasino').val() > 0? $('#buscadorCasino').val() : "",
        plataforma: $('#buscadorCasino').val() < 0? -$('#buscadorCasino').val() : "",
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
                const img = crearElementoArchivo(link,nombre,tipo_archivo)
                .addClass('fotoMiniatura')
                .attr('id-autoexcluido',id_autoexcluido)
                .attr('nro-dni',nro_dni)
                .attr('data-id-importacion',id_importacion)
                .attr('data-tipo-archivo',tipo_archivo)
                .attr('data-nombre',nombre);

                const div = $('<div>')
                .addClass('thumbnail')
                .append(img);

                div.click(function(){
                  clickImagenGaleria(nro_dni,link,nombre,tipo_archivo);
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

//Las imágenes van en un <img> y no en un <embed>: como la URL de mostrarArchivo no
//tiene extensión, el navegador no le conoce el tamaño natural al <embed> y lo deja en
//su tamaño por defecto (300x150), recortando la foto en una ventanita chica.
function esImagen(nombre,tipo_archivo){
  if(tipo_archivo == 'sin_foto') return true;//El placeholder es un jpg
  const indice_ext = nombre.lastIndexOf('.');
  if(indice_ext == -1) return false;//Sin extension
  const ext = nombre.toLowerCase().substr(indice_ext+1);
  return ext == 'png' || ext == 'jpg' || ext == 'jpeg' || ext == 'gif' || ext == 'bmp' || ext == 'webp';
}

function crearElementoArchivo(link,nombre,tipo_archivo){
  if(esImagen(nombre,tipo_archivo)) return $('<img>').attr('src',link);
  return $('<embed>').attr('src',link + '#toolbar=0');//PDF o DOC o algun otro
}

//Los estados de ae_nombre_estado son fijos, así que el cartel se resuelve por id y no
//por la descripción que venga de la BD (esa ya se renombró alguna vez). El texto se
//muestra en mayúsculas por CSS, no hace falta escribirlo así acá.
const ESTADOS_AE = {
  1: 'Vigente',
  2: 'Renovado',
  3: 'Pendiente de val.',
  4: 'Fin. por AE',
  5: 'Vencido',
  6: 'RES.983 - Pendiente',
  7: 'RES.983 - Verificado'
};

function clickImagenGaleria(nro_dni,link,nombre,tipo_archivo){
  $.ajax({
    type: 'GET',
    url: '/galeriaImagenesAutoexcluidos/getDatosUnAutoexcluido/' + nro_dni,
    async:false,
    success: function(resultado) {
        let res = resultado[0];
        document.getElementById("apellido").innerHTML = res.apellido;
        document.getElementById("nombres").innerHTML = res.nombres;
        document.getElementById("dni").innerHTML = res.nro_dni;
        document.getElementById("casino_plataforma").innerHTML = res.casino_plataforma;
        //Si apareciera un id nuevo, caigo en la descripción de la BD antes que dejarlo vacío
        document.getElementById("estado").innerHTML = ESTADOS_AE[res.id_nombre_estado] || res.estado;
        document.getElementById("fecha_ae").innerHTML = res.fecha_ae;
        document.getElementById("vencimiento").innerHTML = res.fecha_vencimiento;
        document.getElementById("fecha_revocacion").innerHTML = res.fecha_revocacion_ae;
        document.getElementById("fecha_cierre").innerHTML = res.fecha_cierre_ae;

        //Si ya tuvo autoexclusiones anteriores, estas dos fechas no son de este período
        $('#fila_vencimiento').toggle(!!res.es_primer_ae);
        $('#fila_revocacion').toggle(!!res.es_primer_ae);

        const visor = crearElementoArchivo(link,nombre,tipo_archivo);
        if(esImagen(nombre,tipo_archivo)){
          visor.css('max-width','100%').css('max-height','100%');
        }
        else{
          visor.css('height','100%').css('width','100%');
        }
        $('#viewer').empty().append(visor);
    },
    error: function(data) {
        console.log('Error:', data);
    }
  });
}
