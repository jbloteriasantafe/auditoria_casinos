$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionarMTM').removeClass();
  $('#gestionarMTM').addClass('subMenu2 collapse in');

  $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Certificados de Software');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcGliSoft').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcGliSoft').addClass('opcionesSeleccionado');

  $('#buscarCertificado').trigger('click');
});

//Opacidad del modal al minimizar

$('#btn-minimizar').click(function(){
    if($(this).data("minimizar")==true){
      $('.modal-backdrop').css('opacity','0.1');
      $(this).data("minimizar",false);
    }else{
      $('.modal-backdrop').css('opacity','0.5');
      $(this).data("minimizar",true);
    }
});

/* TODOS LOS EVENTOS DE BUSCAR JUEGOS */
$('#btn-agregarJuego').click(function(e){
    const id_juego = obtenerIdDatalist($('#inputJuego').val(),$('#datalistJuegos'));
    if(esUndefined(id_juego)){
      $('#inputJuego').val('');
    }
    else{
      $.get('/juegos/obtenerJuego/' + id_juego , function(data){
        agregarFilaJuego(data.juego, data.tablasDePago);
        $('#inputJuego').val('');
      });
    }
});

function existeEnDataList(id){
  var bandera = false;
  $('#tablaJuegos tbody tr').each(function(){
      if (parseInt($(this).attr('id'))  == parseInt(id))
        bandera = true;
  });

  return bandera;
}

function agregarFilaJuego(juego, tablas) {
  var fila = $('<tr>').attr('id', juego.id_juego);

  var tablas_pago = '';

  if (tablas.length > 0) {
      tablas_pago = $('<select>').addClass('form-control');

      for (var i = 0; i < tablas.length; i++) {
        tablas_pago.append($('<option>').text(tablas[i].codigo));
      }
  }


  fila.append($('<td>').addClass('col-xs-3')
                       .text(juego.nombre_juego)
  );
  if(juego.cod_juego==null){
    fila.append($('<td>').addClass('col-xs-3')
                       .text('')
  );
  }else{
    fila.append($('<td>').addClass('col-xs-3')
                       .text(juego.cod_juego)
  );
  }

  fila.append($('<td>').addClass('col-xs-3')
                       .append(tablas_pago)
  );
  fila.append($('<td>').addClass('col-xs-3')
                       .append($('<button>').addClass('btn btn-danger borrarJuego')
                                            .append($('<i>').addClass('fa fa-fw fa-trash'))
                              )
  );

  $('#tablaJuegos tbody').append(fila);
}

$(document).on('click','.borrarJuego',function(){
  $(this).parent().parent().remove();
});

/* TODOS LOS EVENTOS DE BUSCAR EXPEDIENTES */
$('#btn-agregarExpediente').click(function(e){
    var id_expediente = $('#inputExpediente').obtenerElementoSeleccionado();

    if (id_expediente != 0) {
      $.get('/expedientes/obtenerExpediente/' + id_expediente , function(data){
        //Agregar la fila a la tabla
        agregarFilaExpediente(data.expediente);
        //Limpiar el input para seguir buscando expedientes
        $('#inputExpediente').setearElementoSeleccionado(0, '');
      });
    }
});

function agregarFilaExpediente(expediente) {
  var fila = $('<tr>').attr('id', expediente.id_expediente);

  fila.append($('<td>').addClass('col-xs-3')
                       .text(expediente.nro_exp_org + '-' + expediente.nro_exp_interno + '-' + expediente.nro_exp_control)
             );
  fila.append($('<td>').addClass('col-xs-3')
                       .append($('<button>').addClass('btn btn-danger borrarExpediente')
                                            .append($('<i>').addClass('fa fa-fw fa-trash'))
                              )
             );

  $('#tablaExpedientesSoft tbody').append(fila);
}

//Borrar expediente de la tabla
$(document).on('click','.borrarExpediente',function(){
  $(this).parent().parent().remove();
});

/* DETALLE, MODIFICAR, NUEVO Y BORRAR */

$(document).on('click','.detalle',function(){
    //Modificar los colores del modal
    $('.modal-title').text('| VER MÁS');
    $('.modal-header').attr('style','background: #4FC3F7');
    $('#btn-guardar').hide();
    //Resetear formulario para llevar los datos
    $('#frmG').trigger('reset');
    $('#listaExpedientes').empty();
    $('#listaJuegos li').each(function(){
      if($(this).attr("id")!=0)
      $(this).remove();
    })
    $('#cuerpoTablaDePago tr').remove();
    $('.modal-footer .cancelar').text('SALIR');

    //limpia la tabla de juegos
    $('#tablaJuegos tbody').empty();

    //obtenerGli
    var id=$(this).val();

    $.get("obtenerGliSoft/" + id , function(data){

      $('#nroCertificado').val(data.glisoft.nro_archivo);
      $('#observaciones').val(data.glisoft.observaciones);

      //SI NO HAY ARCHIVO EN LA BASE
      if (data.nombre_archivo == null) {
        //Inicializa el fileinput para cargar los PDF
        $("#cargaArchivo").fileinput('destroy').fileinput({
          language: 'es',
          showRemove: false,
          showUpload: false,
          showCaption: false,
          showZoom: false,
          browseClass: "btn btn-primary",
          previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
          overwriteInitial: false,
          initialPreviewAsData: true,
          dropZoneEnabled: true,
          allowedFileExtensions: ['pdf']
        });

        //SI HAY ARCHIVO EN LA BASE
      }else{
        //Carga el fileinput con el PDF cargado
        $("#cargaArchivo").fileinput('destroy').fileinput({
          language: 'es',
          showRemove: false,
          showUpload: false,
          showCaption: false,
          showZoom: false,
          browseClass: "btn btn-primary",
          previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
          overwriteInitial: true,
          initialPreviewAsData: true,
          initialPreview: [
            "http://" + window.location.host + "/certificadoSoft/pdf/" + id,
          ],
          initialPreviewConfig: [
            {type:'pdf', caption: data.nombre_archivo, size: 329892, width: "120px", url: "{$url}", key: 1},
          ],
          allowedFileExtensions: ['pdf'],
        });
      }

       for (var i = 0; i < data.juegos.length; i++) {
        console.log(data.juegos[i]);
        agregarFilaJuego(data.juegos[i].juego, data.juegos[i].tablas_de_pago);
      }
      $('.borrarJuego').prop('disabled',true);
      $('.borrarExpediente').prop('disabled',true);
      $('#modalGLI').modal('show');
  })

  $('#inputExpediente').prop('readonly' , true);
  $('#inputJuego').prop('readonly', true);
  $('#nroCertificado').prop('readonly' , true);
  $('#observaciones').prop('readonly' , true);
  $('#cargaArchivo').prop('disabled' , true);
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| CERTIFICADO DE SOFTWARE');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo GLI Soft
$('#btn-nuevo').click(function(e){
    e.preventDefault();

    $('#mensajeExito').hide();

    //Modificar los colores del modal
    $('.modal-title').text('| NUEVO CERTIFICADO DE SOFTWARE');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

    //Limpiar los inputs
    $('input').val('');
    $('#observaciones').val('');

    //Habilitar todos los inputs
    $('input').prop('readonly',false);
    $('#observaciones').prop('readonly',false);
    $('#cargaArchivo').prop('disabled', false);
    $('#inputExpediente').prop('readonly',false);
    $('#inputJuego').prop('readonly',false);

    //Preparar los botones del modal
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn').addClass('btn-successAceptar');
    $('#btn-guardar').text('ACEPTAR');
    $('#btn-guardar').show();
    $('#btn-guardar').val("nuevo");
    $('.modal-footer .cancelar').text('CANCELAR');

    //Limpiar tablas
    $('#tablaJuegos tbody').empty();
    $('#tablaExpedientesSoft tbody').empty();

    //Preparar los datalist
    $('#inputExpediente').generarDataList("http://" + window.location.host + "/expedientes/buscarExpedientePorNumero",'resultados','id_expediente','concatenacion',2,true);
    //$('#inputJuego').generarDataList("http://" + window.location.host + "/juego/buscarJuegos" ,'resultados','id_juego','nombre_juego', 2, true);

    $('#inputExpediente').setearElementoSeleccionado(0,"");
    //$('#inputJuego').setearElementoSeleccionado(0,"");

    //Inicializa el fileinput para cargar los PDF
    $("#cargaArchivo").fileinput('destroy').fileinput({
      language: 'es',
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: true,
      allowedFileExtensions: ['pdf']
    });

    //Abrir el modal
    $('#modalGLI').modal('show');

});

//Mostrar modal con los datos del Casino cargados
$(document).on('click','.modificarGLI',function(){
    $('#mensajeExito').hide();

    //Modificar los colores del modal
    $('.modal-title').text('| MODIFICAR CERTIFICADO SOFTWARE');
    $('.modal-header').attr('style','background: #ff9d2d','color: #000;');

    $('#id_gli').val($(this).val());

    //Habilitar todos los inputs
    $('input').prop('readonly',false);
    $('#observaciones').prop('readonly',false);
    $('#cargaArchivo').prop('disabled', false);
    $('#inputExpediente').prop('readonly',false);
    $('#inputJuego').prop('readonly',false);


    //Preparar botones
    $('#btn-guardar').val("modificar");
    $('#btn-guardar').removeClass('btn-successAceptar');
    $('#btn-guardar').addClass('btn').addClass('btn-warningModificar');
    $('#btn-guardar').text('ACEPTAR');
    $('#btn-guardar').show();
    $('.modal-footer .btn-default').text('CANCELAR');

    //Limpiar tablas
    $('#tablaExpedientesSoft tbody').empty();
    $('#tablaJuegos tbody').empty();

    //Preparar los datalist
    $('#inputExpediente').generarDataList("http://" + window.location.host + "/expedientes/buscarExpedientePorNumero",'resultados','id_expediente','concatenacion',2,true);
    //$('#inputJuego').generarDataList("http://" + window.location.host + "/juego/buscarJuegos" ,'resultados','id_juego','nombre_juego', 2, true);

    $('#inputExpediente').setearElementoSeleccionado(0,"");
    //$('#inputJuego').setearElementoSeleccionado(0,"");

    //obtenerGli
    var id = $(this).val();

    $('#cargaArchivo').attr('data-borrado','false');

    $.get("obtenerGliSoft/" +id , function(data){
        console.log(data);

        $('#nroCertificado').val(data.glisoft.nro_archivo);
        $('#observaciones').val(data.glisoft.observaciones);

        //SI NO HAY ARCHIVO EN LA BASE
        if (data.nombre_archivo == null) {
            //Inicializa el fileinput vacio para cargar los PDF
            $("#cargaArchivo").fileinput('destroy').fileinput({
              language: 'es',
              showRemove: false,
              showUpload: false,
              showCaption: false,
              showZoom: false,
              browseClass: "btn btn-primary",
              previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
              overwriteInitial: false,
              initialPreviewAsData: true,
              dropZoneEnabled: true,
              allowedFileExtensions: ['pdf']
            });

        //SI HAY ARCHIVO EN LA BASE
        }else{
            //Carga el fileinput con el PDF cargado
            $("#cargaArchivo").fileinput('destroy').fileinput({
              language: 'es',
              showRemove: false,
              showUpload: false,
              showCaption: false,
              showZoom: false,
              browseClass: "btn btn-primary",
              previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
              overwriteInitial: true,
              initialPreviewAsData: true,
              initialPreview: [
                "http://" + window.location.host +"/certificadoSoft/pdf/" + id,
              ],
              initialPreviewConfig: [
                {type:'pdf', caption: data.nombre_archivo, size: 329892, width: "120px", url: "{$url}", key: 1},
              ],
              allowedFileExtensions: ['pdf'],
            });
        }

        //Cargar los expedientes
        for (var i = 0; i < data.expedientes.length; i++) {
          agregarFilaExpediente(data.expedientes[i]);
        }

        //Cargar los juegos
        for (var i = 0; i < data.juegos.length; i++) {
          console.log(data.juegos[i]);
          agregarFilaJuego(data.juegos[i].juego, data.juegos[i].tablas_de_pago);
        }

        $('.borrarJuego').prop('disabled',false);
        $('.borrarExpediente').prop('disabled',false);
        $('#modalGLI').modal('show');
    });
});

//Borrar GLI
$(document).on('click','.eliminarGLI',function(){

    $('.modal-title').removeAttr('style');
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    $('#btn-guardar').removeClass('btn-success');
    $('#btn-guardar').addClass('btn-warning');
    $('#btn-guardar').text('Eliminar');

    var id_gli = $(this).val();

    $('#boton-eliminarGLI').val(id_gli);

    $('#modalEliminar').modal('show');
});

$('#boton-eliminarGLI').click(function (e) {
    var id_gli = $(this).val();

    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    })

    $.ajax({
      type: "DELETE",
      url: "eliminarGliSoft/" + id_gli ,
      success: function (data) {
        $('#cuerpoTabla #' + id_gli).remove();
        $("#tablaGliSofts").trigger("update");
        $('#modalEliminar').modal('hide');
      },
      error: function (data) {
        console.log('Error: ', data);
      }
    });
});

$('#cargaArchivo').on('fileclear', function(event) {
    $('#cargaArchivo').attr('data-borrado','true');
    $('#cargaArchivo')[0].files[0] = null;
});

$('#cargaArchivo').on('fileselect', function(event) {
  $('#cargaArchivo').attr('data-borrado','false');
});

//Crear nuevo gli-modificar
$('#btn-guardar').click(function (e){
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });

    //estado del boton
    var estado=$(this).val();

    var expedientes = [];
    $('#tablaExpedientesSoft tbody tr').each(function(){
        expedientes.push($(this).attr('id'));
    });

    var juegos = [];
    $('#tablaJuegos tbody tr').each(function(){
        juegos.push($(this).attr("id"));
    });


    if(estado=='nuevo'){
      //seteo de la ruta y del contenido del formulario
      var url="guardarGliSoft";
      var formData=new FormData();
      formData.append('nro_certificado',$('#nroCertificado').val());
      formData.append('observaciones' , $('#observaciones').val());

      if ($('#cargaArchivo')[0].files[0] != null) formData.append('file' , $('#cargaArchivo')[0].files[0]);

      formData.append('expedientes' , expedientes);
      formData.append('juegos' , juegos);

    }else{
      //ver si puede ser mas de un casino, por ahora es un checkbox
      var id=$('#id_gli').val();
      var url="modificarGliSoft";
      var formData=new FormData();
      formData.append('id_gli_soft' , $('#id_gli').val());
      formData.append('nro_certificado',$('#nroCertificado').val());
      formData.append('observaciones' , $('#observaciones').val());

      if($('#cargaArchivo').attr('data-borrado') == 'false' && $('#cargaArchivo')[0].files[0] != null){
        formData.append('file' , $('#cargaArchivo')[0].files[0]);
      }
      // if ($('#cargaArchivo')[0].files[0] != null) formData.append('file' , $('#cargaArchivo')[0].files[0]);

      formData.append('expedientes' , expedientes);
      formData.append('juegos' , juegos);
      formData.append('borrado', $('#cargaArchivo').attr('data-borrado'));
    }

    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: "json",
        processData: false,
        contentType:false,
        cache:false,
        success: function (data) {
            console.log(data);

            $('#mensajeExito h3').text('ÉXITO DE CARGA');

            if (estado == 'nuevo') $('#mensajeExito p').text('El certificado fue CREADO correctamente.');
            else $('#mensajeExito p').text('El certificado fue MODIFICADO correctamente.');

            $('#modalGLI').modal('hide');
            $('#mensajeExito').show();
            // $('#buscarCertificado').trigger('click');

            var columna = $('#tablaResultados .activa').attr('value');
            var orden = $('#tablaResultados .activa').attr('estado');

            $('#buscarCertificado').trigger('click' ,[$('#herramientasPaginacion').getCurrentPage() ,$('#tituloTabla').getPageSize() ,columna , orden] );
        },
        error: function (data) {
          console.log('Error:', data);
          var response = JSON.parse(data.responseText);

          if(typeof response.nro_admin !== 'undefined'){
            mostrarErrorValidacion($('#nro_admin'),response.nro_admin[0],true);
            $('#error_nav_maquina').show();
          }

          if(typeof response.nro_certificado !== 'undefined'){
            mostrarErrorValidacion($('#nroCertificado'),response.nro_certificado[0],true);
          }


          if(typeof response.file !== 'undefined'){
            $('#mensajeError .textoMensaje').empty();
            $('#mensajeError .textoMensaje').append($('<h3></h3>').text("El archivo no es de tipo PDF."));
            $('#mensajeError').hide();
            setTimeout(function(){
                $('#mensajeError').show();
            },250);
          }

        }//fin error

    });//fin ajax

})

//si apreto enter en los campos de busqueda
$("#contenedorFiltros input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#buscarCertificado').click();
    }
});


//Ordenar tabla
$(document).on('click','#tablaGliSofts thead tr th[value]',function(e){
  $('#tablaGliSofts th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-desc')){
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaGliSofts th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function obtenerIdDatalist(value,datalist){
  let id = null;
  if(value != null && value.length != 0){
    id = datalist.find('option[value="'+value+'"]').attr('data-id');
  } 
  return id;
}
function esUndefined(value){
  return typeof value === 'undefined';
}

/* BÚSQUEDA */
function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaGliSofts .activa').attr('value');
  var orden = $('#tablaGliSofts .activa').attr('estado');
  $('#buscarCertificado').trigger('click',[pageNumber,tam,columna,orden]);
}

$('#buscarCertificado').click(function(e,
  pagina=1,
  page_size=$('#herramientasPaginacion').getPageSize(),
  columna=$('#tablaGliSofts .activa').attr('value'),
  orden=$('#tablaGliSofts .activa').attr('estado')){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  //Fix error cuando librería saca los selectores
  if(page_size != null){

  }
  else if(!isNaN($('#herramientasPaginacion').getPageSize())){
    page_size = $('#herramientasPaginacion').getPageSize();
  }
  else{
    page_size = 10;
  }

  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaGliHard .activa').attr('value'),orden: $('#tablaGliHard .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaGliHard th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  const id_juego = obtenerIdDatalist($('#inputJuegoBuscador').val(),$('#datalistJuegos'));

  var formData = {
    nro_exp_org:$('#nro_exp_org').val(),
    nro_exp_interno:$('#nro_exp_interno').val(),
    nro_exp_control:$('#nro_exp_control').val(),
    casino:$('#sel1').val(),
    certificado: $('#nro_certificado').val(),
    nombre_archivo: $('#nombre_archivo').val(),
    //Si es undefined es pq escribio cualquier fruta
    //Le mando -1 para que me no me devuelva nada
    id_juego: esUndefined(id_juego)? -1 : id_juego,
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

    $.ajax({
      type: "post",
      url: 'buscarGliSoft',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);

        $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.resultados.total,clickIndice);
        $('#herramientasPaginacion').generarIndices(page_number,page_size,data.resultados.total,clickIndice);

        $('#tablaGliSofts tbody tr').remove();

        for (var i = 0; i < data.resultados.data.length; i++) {
          var filaCertificado = generarFilaTabla(data.resultados.data[i]);
          $('#cuerpoTabla').append(filaCertificado);
        }
      },
      error: function (data) {
        console.log('Error:', data);
      },
    });
});

function generarFilaTabla(certificado){
      var fila = $('<tr>').attr('id',certificado.id_gli_soft);

      var nombre_archivo = certificado.nombre_archivo;

      if (nombre_archivo == null) {
          nombre_archivo = '-'
      }

      fila.append($('<td>')
              .addClass('col-xs-4')
              .text(certificado.nro_archivo)
          )
          .append($('<td>')
              .addClass('col-xs-5')
              .text(nombre_archivo)
          )
          .append($('<td>')
              .addClass('col-xs-3')
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                  )
                  .append($('<span>').text(' VER MÁS'))
                  .addClass('btn').addClass('btn-info').addClass('detalle')
                  .attr('value',certificado.id_gli_soft)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                  )
                  .append($('<span>').text(' MODIFICAR'))
                  .addClass('btn').addClass('btn-warning').addClass('modificarGLI')
                  .attr('value',certificado.id_gli_soft)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
                  )
                  .append($('<span>').text(' ELIMINAR'))
                  .addClass('btn').addClass('btn-danger').addClass('eliminarGLI')
                  .attr('value',certificado.id_gli_soft)
              )
          )
        return fila;
}
