var aEliminar=0;
$(document).ready(function(){

  var t= $('#tablaResultadosEv tbody > tr .fechaEventualidad');

  $.each(t, function(index, value){
    //console.log($(this));
  $(this).text(convertirDate($(this).text()));
  });

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#movimientos').removeClass();
  $('#movimientos').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Intervenciones Técnicas');
  $('#opcIntervencionesTecnicas').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcIntervencionesTecnicas').addClass('opcionesSeleccionado');

  $('#agregarMaqEv').click(clickAgregarEv);
  $('#agregarSecEv').click(clickAgregarEv);
  $('#agregarIsEv').click(clickAgregarEv);

  $('#B_CasinoEv')[0].selectedIndex = 0;
  $('#B_fecha_ev').val("");

  $('#cargaInforme').on('fileerror', function(event, data, msg) {
    // get message
    alert(msg);
  });

  $('#alertaArchivo').hide();

  $('#dtpFechaEv').datetimepicker({
    todayBtn:  1,
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 0,
    ignoreReadonly: true,
    container:$('main section'),
  });

  $('#evFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
    container:$('main section'),


  });
  //$('#btn-buscarEventualidad').click();
  clickIndice(null, 
    $('#herramientasPaginacion').getCurrentPage(), 
    $('#herramientasPaginacion').getPageSize());
  $('#B_CasinoEv').change();
});
$('#fechaEv').on('change', function (e) {
  $(this).trigger('focusin');
})
//botón para cargar informe dentro del modal de carga de eventualidades
$('#cargaArchivo').click(function(){
  $('#alertaArchivo').hide();
});

$('#B_fecha_ev').on('change', function (e) {
  $(this).trigger('focusin');
})

//BOTON NUEVA EVENTUALIDAD
$('#btn-nueva-eventualidad').off().click(function(){
  let casinostr = $('#B_CasinoEv').val();
  //Si es 'Todos los casinos', seteamos el proximo que viene.
  if(isNaN(parseInt(casinostr))) casinostr = $('#B_CasinoEv option').eq(1).val();
  
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'GET',
    url: 'eventualidades/crearEventualidad/'+casinostr,
    success: function (id_ev) {
      $('#btn-buscarEventualidad').click();
      window.open('eventualidades/verPlanillaVacia/' + id_ev,'_blank');
    },
    error: function (data) {
      console.log(data);
    }
  });
})

// BOTÓN IMPRIMIR
$(document).on('click','#btn_imprimirEv',function(e){

  var id_ev=$(this).val();

  //abre una pestaña con planilla de eventualidad vacía
  window.open('eventualidades/verPlanillaVacia/' + id_ev,'_blank');
});

//CIERRA MODAL
$('#modalCargarEventualidad').on('hidden.bs.modal', function() {
    ocultarErrorValidacion($('.form-control'));
  $("#modalCargarEventualidad #cargaInforme").fileinput('destroy');
  $('#select_event').prop('disabled', false);

})


//BOTÓN CARGAR Eventualidad
$(document).on('click','#btn_cargarEv',function(e){

  $('#btn-aceptar-carga').show();
  $('#btn-aceptar-visado').hide();
  $('#btn-aceptar-carga').val(1);
  $('#modalCargarEventualidad #myModalLabel').text('CARGAR INTERVENCIÓN TÉCNICA');
  $('#mensajeExito').hide();
  $('#modalCargarEventualidad #select_event').val(3);
  $('#tablaCargaCompleta').hide();

  //Dependiendo lo que seleccione va a aparecer luego uno u otro
  $('#inputMaquinaEv').hide();
  $('#inputSectorEv').hide();
  $('#inputIslaEv').hide();

  //variables completadas con valores del botón
  var casino=$(this).attr('data-casino');
  var id_eventualidad=$(this).val();

  $('#modalCargarEventualidad').find('#id_event').val(id_eventualidad);
  $('#modalCargarEventualidad').find('#id_casino').val(casino);
  $('#modalCargarEventualidad').find('#btn-aceptar-carga').prop('disabled', true);
  $('#tablaCargaEvent tbody tr').remove();
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#modalCargarEventualidad').modal('show');
  $('#fechaRel').val('');
  $('#fiscaToma').generarDataList("eventualidades/buscarUsuariosPorNombreYCasino/" + casino,'usuarios' ,'id_usuario','nombre',1,false);
  $('#fiscaToma').setearElementoSeleccionado(0,"");
  $('#modalCargarEventualidad').find('#fechaEv').val(" ");
  $('#modalCargarEventualidad #tipoEventualidad').val(" ");
  $('#modalCargarEventualidad #observacionesEv').val(" ")

  //Inicializa el fileinput para cargar los CSV
  //$('#modalCargarEventualidad #cargaInforme')[0].files[0] = null;
  $('#modalCargarEventualidad #cargaInforme').attr('data-borrado','false');
  $("#modalCargarEventualidad #cargaInforme").fileinput('destroy').fileinput({
        language: 'es',
        //       showPreview: false,
        // allowedFileExtensions: ["csv", "txt"],
        //       elErrorContainer: "#alertaArchivo"
        language: 'es',
        showRemove: false,
        showUpload: false,
        showCaption: false,
        showZoom: false,
        browseClass: "btn btn-primary",
        previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
        overwriteInitial: false,
        initialPreviewAsData: true,
        dropZoneEnabled: false,
        preferIconicPreview: true,
        previewFileIconSettings: {
          'csv': '<i class="fa fa-file-text-o fa-6" aria-hidden="true"></i>',
          'txt': '<i class="fa fa-file-text-o fa-6" aria-hidden="true"></i>'
        },
        allowedFileExtensions: ['pdf'],
  });
});

  $('#modalCargarEventualidad #cargaInforme').on('fileclear', function(event) {

  $('#modalCargarEventualidad #cargaInforme').attr('data-borrado','true');
  $('#modalCargarEventualidad #cargaInforme')[0].files[0] = null;

});

$('#cargaInforme').on('fileclear', function(event) {
  $('#cargaInforme').attr('data-borrado','true');
  $('#cargaInforme')[0].files[0] = null;
});

//detectar el campo seleccionado: ISLAS, SECTORES, MÁQUINAS
$('#select_event').click(function(){

  var cas=$('#modalCargarEventualidad').find('#id_casino').val();
  var sel= $('#select_event').val();

      //máquinas
      if(sel==2){

        $('#inputSectorEv').hide();
        $('#inputIslaEv').hide();
        $('#inputMaquinaEv').show();

        $('#inputMaqui').generarDataList("eventualidades/obtenerMTMEnCasino/" + cas, 'maquinas','id_maquina','nro_admin',1,true);
      }
      //sectores
      if(sel==1){

        $('#inputMaquinaEv').hide();
        $('#inputIslaEv').hide();
        $('#inputSectorEv').show();

        $('#inputSec').generarDataList("eventualidades/obtenerSectorEnCasino/" + cas ,'sectores','id_sector','descripcion',1,true);
      }
      //islas
      if(sel==0){
        $('#inputMaquinaEv').hide();
        $('#inputSectorEv').hide();
        $('#inputIslaEv').show();

        $('#inputIs').generarDataList("eventualidades/obtenerIslaEnCasino/" + cas,'islas', 'id_isla','nro_isla',1,true);
      }
      if(sel==3){
        $('#inputMaquinaEv').hide();
        $('#inputSectorEv').hide();
        $('#inputIslaEv').hide();}
  });

//botón + dentro del input
function clickAgregarEv(e) {

    var id_maquina = $('#inputMaqui').attr('data-elemento-seleccionado');
    var id_sector = $('#inputSec').attr('data-elemento-seleccionado');
    var id_isla = $('#inputIs').attr('data-elemento-seleccionado');
    var select=  $('#select_event').val();
    if((id_maquina || id_sector || id_isla) != null ){ $('#modalCargarEventualidad').find('#select_event').prop('disabled',true);}

    if (select==2) {
      $.get("eventualidades/obtenerMTM/" + id_maquina, function(data) {
        agregarMaqEv(data.maquina.id_maquina, data.maquina.nro_admin);
        $('#inputMaqui').setearElementoSeleccionado(0 , "");
      });
    }

    if(select==1){
      $.get("eventualidades/obtenerSector/" + id_sector, function(data) {
        console.log('get de sector:', data);
        agregarSecEv(data.sector.id_sector, data.sector.descripcion);
        $('#inputSec').setearElementoSeleccionado(0 , "");});
      }

      if(select==0){
        $.get("eventualidades/obtenerIsla/" + id_isla, function(data) {
          console.log('get de isla:', data);
          agregarIsEv(data.isla.id_isla, data.isla.nro_isla);
          $('#inputIs').setearElementoSeleccionado(0 , "");});
        }
      }

//BTN (+) DEL INPUT DE MÁQUINAS
function agregarMaqEv(id_maquina, nro_admin) {

        //  var tipo=$('#modalLogMovimiento2').find('#tipo_movi').val();
        $('#modalCargarEventualidad').find('#filainicial').text('MÁQUINA');

        var fila = $('<tr>').attr('id', id_maquina);
        var accion = $('<button>').addClass('btn btn-danger borrarMaquina')
        .append($('<i>').addClass('fa fa-fw fa-trash'));

        //Se agregan todas las columnas para la fila
        fila.append($('<td>').text(nro_admin))
        fila.append($('<td>').append(accion));

        //Agregar fila a la tabla
        $('#tablaCargaEvent tbody').append(fila);
        $('#modalCargarEventualidad').find('#tipo').val(1);
        $('#modalCargarEventualidad').find('#btn-aceptar-carga').prop('disabled', false);

      };

//BTN (+) DEL INPUT DE SECTORES
function agregarSecEv(id_sector, nro_sector) {

        //  var tipo=$('#modalLogMovimiento2').find('#tipo_movi').val();
        $('#modalCargarEventualidad').find('#filainicial').text('SECTOR');

        var fila = $('<tr>').attr('id', id_sector);
        var accion = $('<button>').addClass('btn btn-danger borrarMaquina')
        .append($('<i>').addClass('fa fa-fw fa-trash'));

        //Se agregan todas las columnas para la fila
        fila.append($('<td>').text(nro_sector))
        fila.append($('<td>').append(accion));

        //Agregar fila a la tabla
        $('#tablaCargaEvent tbody').append(fila);
        $('#modalCargarEventualidad').find('#tipo').val(2);
        $('#modalCargarEventualidad').find('#btn-aceptar-carga').prop('disabled', false);
      };

//BTN (+) DEL INPUT DE ISLAS
function agregarIsEv(id_isla,nro_isla) {

        //  var tipo=$('#modalLogMovimiento2').find('#tipo_movi').val();
        $('#modalCargarEventualidad').find('#filainicial').text('ISLA');

        var fila = $('<tr>').attr('id', id_isla);
        var accion = $('<button>').addClass('btn btn-danger borrarMaquina')
        .append($('<i>').addClass('fa fa-fw fa-trash'));

        //Se agregan todas las columnas para la fila
        fila.append($('<td>').text(nro_isla));
        fila.append($('<td>').append(accion));

        //Agregar fila a la tabla
        $('#tablaCargaEvent tbody').append(fila);
        $('#modalCargarEventualidad').find('#tipo').val(3);
        $('#modalCargarEventualidad').find('#btn-aceptar-carga').prop('disabled', false);
      };

      //botón de eliminar que esta dentro del modal de cargar en la lista de maquinas, sectores e islas
      $(document).on('click','.borrarMaquina',function(e){

       $(this).parent().parent().remove();
      });


//boton borrar en fila
$(document).on('click','#btn_borrarEv',function(e){

    //se abre un modal de advertencia
    $('#modalEliminarEventualidad').modal('show');
    aEliminar= $(this);

});

//Si presiona el botón eliminar dentro del modal de advertencia
$('#btn-eliminarEvent').click(function (e){

  var id= aEliminar.val();

  $.get('eventualidades/eliminarEventualidad/' + id, function(data){

      if(data==1){
        aEliminar.parent().parent().remove();
        $('#modalEliminarEventualidad').modal('hide');
     }

  }) //fin del get

});
$('#btn-aceptar-visado').click(function (e){
  e.preventDefault();

  var id_ev = $('#modalCargarEventualidad').find('#id_event').val();
  console.log('id_eve',id_ev);
  $.get('eventualidades/visado/' + id_ev, function(data){

  if(data==1){
    $('#fiscaToma').prop('disabled', false);
    $('#fechaEv').prop('disabled', false);
    $('#tipoEventualidad').prop('disabled', false);
    $('#seleccion').show();
    $('#observacionesEv').prop('disabled', false);
    $('#modalCargarEventualidad').modal('hide');
    $('#mensajeExito h3').text('VISADO');
    $('#mensajeExito p').text(' ');
    $('#mensajeExito').show();
    $('#btn-buscarEventualidad').trigger('click');
  }
  })


});


//Botón aceptar para guardar los datos cargados de eventualidad
$('#btn-aceptar-carga').click(function (e) {
  if ($(this).val() != 1) return;
  const tabla = $('#tablaCargaEvent tbody > tr');
  const t = $('#modalCargarEventualidad').find('#tipo').val();
  const id_ev = $('#modalCargarEventualidad').find('#id_event').val();
  let maquinas = [];
  let sectores = [];
  let islas = [];

  $.each(tabla, function (index, value) {
    var id = $(this).attr('id');
    if (t == 1) {
      maquinas.push(id);
    }
    if (t == 2) {
      sectores.push(id);
    }
    if (t == 3) {
      islas.push(id);
    }
  });

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  let formData = new FormData();
  formData.append('id_eventualidad', $('#modalCargarEventualidad').find('#id_event').val());
  formData.append('id_fiscalizador', $('#fiscaToma').obtenerElementoSeleccionado());
  formData.append('observaciones', $('#modalCargarEventualidad').find('#observacionesEv').val());
  formData.append('fecha_toma', $('#modalCargarEventualidad').find('#fecha_ejecucionEv').val());
  formData.append('sectores', sectores);
  formData.append('islas', islas);
  formData.append('maquinas', maquinas);
  if ($('#cargaInforme')[0].files[0] != null) formData.append('file', $('#cargaInforme')[0].files[0]);
  formData.append('id_tipo_eventualidad', $('#modalCargarEventualidad').find('#tipoEventualidad').val());
  formData.append('seleccion', $('#select_event').val());


  $.ajax({
    type: "POST",
    url: 'eventualidades/CargarYGuardarEventualidad',
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    cache: false,

    success: function (data) {
      console.log(data);
      $('#modalCargarEventualidad').modal('hide');
      $('#select_event').prop('disabled', false);
      $('#' + id_ev).find('#btn_cargarEv').hide();
      $('#' + id_ev).find('#btn_borrarEv').hide();
    },
    error: function (data) {
      console.log("error: ", data);

      let response = JSON.parse(data.responseText);

      if (typeof response.observaciones !== 'undefined') {
        mostrarErrorValidacion($('#observacionesEv'), response.observaciones[0]);
      }
      if (typeof response.sectores !== 'undefined') {
        mostrarErrorValidacion($('#inputSec'), response.sectores[0]);
      }
      if (typeof response.maquinas !== 'undefined') {
        mostrarErrorValidacion($('#inputMaqui'), response.maquinas[0]);
      }
      if (typeof response.islas !== 'undefined') {
        mostrarErrorValidacion($('#inputIs'), response.islas[0]);
      }
      if (typeof response.id_fiscalizador !== 'undefined') {
        mostrarErrorValidacion($('#fiscaToma'), response.id_fiscalizador[0]);
      }
      if (typeof response.fecha_toma !== 'undefined') {
        mostrarErrorValidacion($('#fechaEv'), response.fecha_toma[0]);
      }
      if (typeof response.id_tipo_eventualidad !== 'undefined') {
        mostrarErrorValidacion($('#tipoEventualidad'), response.id_tipo_eventualidad[0]);
      }
    }
  }); //fin de ajax

});

//BOTÓN VALIDAR DE CADA FILA
$(document).on('click', '#btn_validarEv', function (e) {
  $('#mensajeExito').hide();
  $('#btn-aceptar-carga').hide();
  $('#btn-aceptar-visado').show();
  $('#tablaCargaCompleta tbody tr').remove();

  //Cambio el título del modal
  $('#modalCargarEventualidad #myModalLabel').text('VISAR INTERVENCIÓN');

  var id_eventualidad = $(this).val();
  $('#modalCargarEventualidad').find('#id_event').val(id_eventualidad);

  $.get('eventualidades/visualizarEventualidadID/' + id_eventualidad, function (data) {

    $('.modal-header').attr('style', 'font-family: Roboto-Black; background-color: #6dc7be;');
    $('#modalCargarEventualidad').modal('show');
    $('#tablaCargaEvent').hide();
    $('#inputIslaEv').hide();
    $('#inputMaquinaEv').hide();
    $('#inputSectorEv').hide();
    $('#seleccion').hide();

    //Completo los campos del modal con info del data
    $('#fiscaToma').val(data.fiscalizador.nombre).prop('disabled', true);
    $('#fechaEv').val(data.eventualidad.fecha_generacion).prop('disabled', true);
    $('#tipoEventualidad').val(data.eventualidad.id_tipo_eventualidad).prop('disabled', true);


    var fila = $(document.createElement('tr'));

    for (var i = 0; i < data.maquinas.length; i++) {

      fila.attr('id', data.maquinas[i].id_maquina)
        .append($('<td>')
          .addClass('col-xs-4')
          .text(data.maquinas[i].nro_admin)
        )
        .append($('<td>')
          .addClass('col-xs-5')
          .text(data.maquinas[i].descripcion)
        )
        .append($('<td>')
          .addClass('col-xs-3')
          .text(data.maquinas[i].nro_isla)
        )
      $('#tablaCargaCompleta tbody').append(fila);
    }


    $('#observacionesEv').val(data.eventualidad.observaciones).prop('disabled', true);
    $('#cargaInforme').attr('style', 'display:none');

    //  mostrar el pdf que se recibe en el data
    $("#cargaInforme").fileinput('destroy').fileinput({
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
        "http://" + window.location.host + "/eventualidades/leerArchivoEventualidad/" + id_eventualidad,
      ],
      initialPreviewConfig: [
        { type: 'pdf', caption: 'Test', size: 1, width: "1000px", url: "{$url}", key: 1 },
      ],
      allowedFileExtensions: ['pdf'],
    });
  });


  $('#modalCargarEventualidad').modal('hide');

});


//Busqueda de eventos
$('#btn-buscarEventualidad').click(function(e, pagina, page_size){
    $.ajaxSetup({
      headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });

    e.preventDefault();

    //Fix error cuando librería saca los selectores
    if (isNaN($('#herramientasPaginacion').getPageSize())) {
      var size = 10; // por defecto
    } else {
      var size = $('#herramientasPaginacion').getPageSize();
    }
    page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    pagina = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();

    let turno = $('#B_TurnoEventualidad').val();
    turno = turno == ""? 0 : turno;

    var formData = {
      id_tipo_eventualidad: $('#B_TipoEventualidad').val(),
      fecha: $('#B_fecha_ev').val(),
      id_casino: $('#B_CasinoEv').val(),
      nro_turno: turno,
      id_sector: $('#B_Sector').val(),
      id_isla: $('#B_Isla').val(),
      nro_admin: $('#B_Numero').val(),
      page: pagina,
      sort_by: 'fecha',
      page_size: page_size
    };

    $.ajax({
      type: 'POST',
      url: 'eventualidades/buscarPorTipoFechaCasinoTurno',
      data: formData,
      dataType: 'json',

      success: function (res) {
        console.log('success', res);
        $('#tablaResultadosEv #cuerpoTablaEv tr').remove();
        $('#herramientasPaginacion').generarTitulo(
          res.eventualidades.current_page,
          res.eventualidades.per_page, 
          res.eventualidades.total, 
          clickIndice);
          for (var i = 0; i < res.eventualidades.data.length; i++) {
              let filaEventualidad = generarFilaTabla(
                res.eventualidades.data[i],
                res.esControlador
              );
              $('#cuerpoTablaEv').append(filaEventualidad);
          }
        $('#herramientasPaginacion').generarIndices(
          res.eventualidades.current_page,
          res.eventualidades.per_page,
          res.eventualidades.total,
          clickIndice);

      },
      error: function (data) {
        console.log('Error:', data);
      }
    });
});

function clickIndice(e, pageNumber, tam=undefined,total=null) {
  if (e != null) {
      e.preventDefault();
  }
  console.log(pageNumber,tam,total);
  var tam = (isNaN(tam)) ? 
  $('#herramientasPaginacion').getPageSize() 
  : tam;
  var columna = $('#tablaResultadosEv .activa').attr('value');
  var orden = $('#tablaResultadosEv .activa').attr('estado');
  $('#btn-buscarEventualidad').trigger('click', [pageNumber, tam, columna, orden]);
}

function limpiarNull(s){
  return s === null? '-' : s;
}

//Se generan filas en la tabla principal con las eventualidades encontradas
function generarFilaTabla(event, controlador) {
  const fila = $(document.createElement('tr'));
  const fecha = limpiarNull(event.fecha);
  const tipo_ev = limpiarNull(event.descripcion);
  const turno = limpiarNull(event.turno);
  const casino = limpiarNull(event.nombre);
  const hora = limpiarNull(event.hora);
  const estado = event.id_estado_eventualidad;
  const archivo = event.id_archivo;
  console.log(event);

  fila.attr('id', event.id_eventualidad)
    .append($('<td>')
      .addClass('col-xs-2')
      .text(convertirDate(fecha))
    )
    .append($('<td>')
      .addClass('col-xs-1')
      .text(hora)
    )
    .append($('<td>')
      .addClass('col-xs-2')
      .text(tipo_ev)
    )
  if (estado == 4) {
    fila.append($('<td>')
      .addClass('col-xs-1')
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-check').css('color', '#4CAF50').css('align', 'center')))
  }
  else {
    fila.append($('<td>')
      .addClass('col-xs-1')
      .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-times').css('color', '#EF5350').css('align', 'center')))
  }

  fila.append($('<td>')
    .addClass('col-xs-2')
    .addClass('text-align="center"')
    .text(turno)
  )
  .append($('<td>')
    .addClass('col-xs-2')
    .text(casino)
  );

  let td = $('<td>').addClass('col-xs-2').append($('<span>').text(' '))
  .append($('<button>')
    .addClass('boton_imprimirEv')
    .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-print'))
    .append($('<span>').text('IMPRIMIR'))
    .addClass('btn').addClass('btn-success')
    .attr('value', event.id_eventualidad).attr('id', 'btn_imprimirEv')
  );

  if (controlador == 0 && estado == 6) {
    td
    .append($('<button>')
      .addClass('boton_cargarEv')
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-upload')
      )
      .append($('<span>').text('CARGAR'))
      .addClass('btn').addClass('btn-success')
      .attr('value', event.id_eventualidad)
      .attr('data-casino', event.id_casino).attr('id', 'btn_cargarEv'))

    .append($('<button>')
      .addClass('btn btn-danger borrarEventualidad')
      .append($('<i>').addClass('fa fa-fw fa-trash')
      )
      .append($('<span>').text('BORRAR'))
      .addClass('btn').addClass('btn-success')
      .attr('value', event.id_eventualidad).attr('id', 'btn_borrarEv'));
  }

  if (controlador == 1 && estado == 1) {
    td
    .append($('<button>')
      .addClass('btn-validarEventualidad')
      .append($('<i>').addClass('fa fa-fw fa-check')
      )
      .append($('<span>').text('VALIDAR'))
      .addClass('btn').addClass('btn-success')
      .attr('value', event.id_eventualidad).attr('id', 'btn_validarEv'));
  }
  if (controlador == 1 && estado == 6) {
    td.append(
    $('<button>')
      .addClass('btn btn-danger borrarEventualidad')
      .append($('<i>').addClass('fa fa-fw fa-trash'))
      .append($('<span>').text('BORRAR'))
      .addClass('btn').addClass('btn-success')
      .attr('value', event.id_eventualidad).attr('id', 'btn_borrarEv'));
  }

  if (estado != 6) {
    const deshab = archivo === null;
    const icono = deshab? "far fa-edit" : "fas fa-edit";
    let boton = $('<button>')
    .addClass('btn-verPDF')
    .append($('<i>').addClass(icono))
    .append($('<span>').text('VER PDF'))
    .addClass('btn').addClass('btn-success')
    .attr('value',event.id_eventualidad)
    .prop('disabled',deshab);
    td.append(boton);
    if(!deshab){
      boton.click(function(){
        window.open('eventualidades/leerArchivoEventualidad/' + boton.val(),'_blank');
      });
    }
  }

  fila.append(td);

  return fila;
};

$('#B_CasinoEv').change(function(){
  const t = $(this);
  const id_casino = t.val();
  let sector = $("#B_Sector");
  let isla = $("#B_Isla");
  let numero = $("#B_Numero");
  isla.empty();
  isla.prop('disabled',true);
  if(id_casino.length == 0){
    sector.prop('disabled',true);
    numero.prop('disabled',true);
    sector.empty();
    numero.val("");
    return;
  }
  sector.prop('disabled',false);
  numero.prop('disabled',false);

  const sectores = $('#sectores').find('option[data-id-casino="'+id_casino+'"]');
  const todos = $('<option>').val('').text('Todos los sectores');
  sector.empty();

  sector.append(todos); 
  sectores.each(function(idx,obj){
    let o = $(obj).clone();
    o.val(o.attr('data-id-sector'));
    sector.append(o);
  });
});

$('#B_Sector').change(function(){
  const t = $(this);
  const id_sector = t.val();
  let isla = $("#B_Isla");
  let numero = $("#B_numero");

  numero.prop('disabled',false);
  if(id_sector.length == 0){
    isla.prop('disabled',true);
    isla.empty();
    return;
  }
  isla.prop('disabled',false);
  const islas = $('#islas').find('option[data-id-sector="'+id_sector+'"]');
  const todos = $('<option>').val('').text('Todas las islas');
  isla.empty();
  isla.append(todos);
  islas.each(function(idx,obj){
    let o = $(obj).clone();
    o.val(o.attr('data-id-isla'));
    isla.append(o);
  });
});
