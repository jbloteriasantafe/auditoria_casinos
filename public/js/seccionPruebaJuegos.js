var nombreMeses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#prueba').removeClass();
  $('#prueba').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Prueba de juegos');
  $('#opcPruebaJuego').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcPruebaJuego').addClass('opcionesSeleccionado');

  $('#tecnico').popover({ html : true});
  $('#fecha').popover({ html : true});
  $('#inputFisca').popover({ html : true});

  $('#fecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#fecha2').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#iconoCarga').hide();
  $('#btn-buscar').trigger('click',[1,10,'prueba_juego.fecha','desc']);

  $('[data-toggle="popover"]').popover();
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
//Opacidad del modal al minimizar
$('#btn-minimizarCarga').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaPrueba .activa').attr('value'),orden: $('#tablaPrueba .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaPrueba th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    fecha: $('#fecha_inicio').val(),
    nro_admin: $('#nro_admin').val(),
    casino: $('#selectCasinos').val(),
    marca: $('#marca').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  console.log(formData);
  $.ajax({
      type: 'POST',
      url: 'prueba_juegos/buscarPruebasDeJuego',
      data: formData,
      dataType: 'json',
      success: function (pruebas) {
          console.log(pruebas);

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,pruebas.total,clickIndice);
          $('#cuerpoTabla tr').remove();

          for (var i = 0; i < pruebas.data.length; i++){

            var fila = generarFilaTabla(pruebas.data[i]);
            $('#cuerpoTabla')
                .append(fila);
          }

          $('#herramientasPaginacion').generarIndices(page_number,page_size,pruebas.total,clickIndice);

      },
      error: function (pruebas) {
          console.log('Error:', pruebas);
      }
    });
});

$(document).on('click','#tablaPrueba thead tr th[value]',function(e){
  $('#tablaPrueba th').removeClass('activa');
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
  $('#tablaPrueba th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaPrueba .activa').attr('value');
  var orden = $('#tablaPrueba .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(prueba){
      var fila = $(document.createElement('tr'));

      fila.attr('id','prueba' + prueba.id_prueba_juego)
          .append($('<td>')
              .addClass('col-xs-2')
              .text(convertirDate(prueba.fecha))
          )
          .append($('<td>')
               .addClass('col-xs-2')
               .text(prueba.nro_admin)
          )
          .append($('<td>')
               .addClass('col-xs-2')
               .text(prueba.marca)
          )
          .append($('<td>')
               .addClass('col-xs-2')
               .text(prueba.nombre_casino)
          )
          .append($('<td>')
               .addClass('col-xs-4')
               .append($('<button>')
                   .append($('<i>')
                       .addClass('fa').addClass('fa-fw').addClass('fa fa-fw fa-print')
                   )
                   .addClass('btn').addClass('btn-info').addClass('planilla')
                   .attr('value',prueba.id_prueba_juego)
               )
               .append($('<button>').addClass('btn btn-warning carga').attr('type','button').val(prueba.id_prueba_juego)
                   .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-upload'))
               )
          )

        return fila;
}

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();

    window.open('prueba_juegos/generarPlanillaPruebaDeJuego/' + $(this).val(),'_blank');
});

$(document).on('click' , '.carga' , function(){
  var id = $(this).val();
  $('#id_prueba_juego').val(id);
  //Inicializa el fileinput para cargar los PDF
  $.get("prueba_juegos/obtenerPruebaJuego/" + id , function(data){

      //SI NO HAY ARCHIVO EN LA BASE
      if (data.nombre_archivo == null) {
          console.log('no hay archivo');
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
          console.log('hay archivo');
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
              "http://localhost:8000/prueba_juegos/pdf/" + id,
            ],
            initialPreviewConfig: [
              {type:'pdf', caption: data.nombre_archivo, size: 329892, width: "120px", url: "{$url}", key: 1},
            ],
            allowedFileExtensions: ['pdf'],
          });
      }

      $('#modalCargaPrueba').modal('show');
  })

})

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| PRUEBA DE JUEGOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//ABRIR MODAL DE NUEVO RELEVAMIENTO
$('#btn-nuevaPrueba').click(function(e){
  e.preventDefault();
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#frmPrueba').trigger('reset');
  $('#sector option').remove();
  $('#modalPrueba').modal('show');

  $('#modalPrueba').find('.modal-footer').children().show();
  $('#modalPrueba').find('.modal-body').children().show();
  $('#modalPrueba').find('.modal-body').children('#iconoCarga').hide();

  $.get("obtenerFechaActual", function(data){
    //Mayuscula pŕimer letra
    var fecha = data.fecha.charAt(0).toUpperCase() + data.fecha.slice(1);
    $('#fechaActual').val(fecha);
    $('#fechaDate').val(data.fechaDate);
  });
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#modalPrueba #casino').on('change',function(){
  var id_casino = $('#modalPrueba #casino option:selected').val();

  $('#modalPrueba #sector option').remove();
  $.get("prueba_juegos/obtenerSectoresPorCasino/" + id_casino, function(data){

    for (var i = 0; i < data.sectores.length; i++) {
      $('#modalPrueba #sector')
          .append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }
  });
});

//GENERAR PRUEBA JUEGO
$('#btn-generar').click(function(e){

      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

      var id_sector = $('#modalPrueba #sector option:selected').val();
      var casino = $('#modalPrueba #casino option:selected').val();

      var formData = {
        id_sector: id_sector,
        id_casino: casino,
      }

      console.log(formData);

      $.ajax({
          type: "POST",
          url: 'prueba_juegos/sortearMaquinaPruebaDeJuego',
          data: formData,
          dataType: 'json',
          success: function (data) {
            console.log(data);
            $('#btn-buscar').trigger('click');
          },
          error: function (data) {
            console.log(data);
            $('#modalPrueba').find('.modal-body').children('#iconoCarga').hide();

            var response = data.responseJSON.errors;

            if(typeof response.id_sector !== 'undefined'){
                  $('#sector').addClass('alerta');
                  $('#casino').addClass('alerta');
            }

          }
      });
});

//GENERAR PRUEBA JUEGO
$('#btn-guardar').click(function(e){

      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

      var formData=new FormData();
      formData.append('id_prueba_juego' , $('#id_prueba_juego').val());
      formData.append('fecha' , $('#fecha_ejecucion').val());
      formData.append('observacion' , $('#observaciones').val());
      if($('#cargaArchivo')[0].files[0] != null) formData.append('file',$('#cargaArchivo')[0].files[0]);

      $.ajax({
          type: "POST",
          url: '/prueba_juegos/guardarPruebaJuego',
          data: formData,
          dataType: "json",
          processData: false,
          contentType:false,
          cache:false,
          success: function (data) {
            console.log(data);
            $('#modalCargaPrueba').modal('hide');
            $('#btn-buscar').trigger('click');
          },
          error: function (data) {
            console.log(data);
            $('#modalPrueba').find('.modal-body').children('#iconoCarga').hide();

            var response = data.responseJSON.errors;

            if(typeof response.archivo !== 'undefined'){
              mostrarErrorValidacion($('#cargaArchivo'),response.archivo[0] ,true);
            }
            if(typeof response.fecha !== 'undefined'){
              mostrarErrorValidacion($('#fecha2'),response.fecha[0] ,true);
            }
            if(typeof response.observacion !== 'undefined'){
              mostrarErrorValidacion($('#observacion'),response.observacion[0] ,true);
            }

          }
      });

});

$('#modalCargaPrueba').on('hidden.bs.modal', function(){
  //resetearModal

});
