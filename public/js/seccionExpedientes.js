var id_casinos_seleccionados = [];

//Resaltar la sección en el menú del costado
$(document).ready(function() {

  $('#barraExpedientes').attr('aria-expanded','true');
  $('#expedientes').removeClass();
  $('#expedientes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Gestionar expedientes');
  $('#opcGestionarExpedientes').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcGestionarExpedientes').addClass('opcionesSeleccionado');

  $('#btn-buscar').trigger('click');

  limpiarModal();

  $('#navConfig').click();
  $('#error_nav_config').hide();
  $('#error_nav_notas').hide();
  $('#error_nav_mov').hide();

  //DTP filtros
  $('#B_dtpFechaInicio span:first').click();

  $('#B_dtpFechaInicio').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 3,
    container: $('main section'),
  });
});


/* PESTAÑAS */

// Motrar la pestaña activa (se agrega el subrayado).
$('.navModal a').click(function(e){
    e.preventDefault();
    $('.navModal a').removeClass();
    $(this).addClass('navModalActivo');
});

//Cambiar a la sección de Configuración.
$('#navConfig').click(function(){
  $('.seccion').hide();
  $('#secConfig').show();
});

//Cambiar a la sección de Notas nuevas.
$('#navNotas').click(function(){
  $('.seccion').hide();
  $('#secNotas').show();
});

//Cambiar a la sección de Notas con movimientos.
$('#navMov').click(function(){
  $('.seccion').hide();
  $('#secMov').show();

  // if (id_casinos_seleccionados.length > 0) {
  //   //Cargar sección notas
  //   movimientosSinExpediente();
  // }
});


/////////////////////////////////// NOTAS ////////////////////////////////////

//Detectar casino de/seleccionado.
$(document).on('change','.casinosExp', function() {
    //Revisar todos los casinos para ver si hay uno seleccionado
    var casinos_seleccionados = $('.casinosExp:checked');
    id_casinos_seleccionados = [];

    limpiarNotasMovimientos();                                                  //Limpiar la sección de notas con movimientos existentes

    for (var i = 0; i < casinos_seleccionados.length; i++) {
      id_casinos_seleccionados.push(parseInt(casinos_seleccionados[i].id));
    }

    // Si hay 0 casinos seleccionados: limpiar las secciones de notas y mostrar mensajes.
    if (casinos_seleccionados.length == 0) {
        limpiarSeccionNotas();
        $('.mensajeNotas').show();
        $('.formularioNotas').hide();

    //Si hay un SOLO UN CASINO seleccionado: habilitar las dos pestañas
    } else if (casinos_seleccionados.length == 1) {
        habilitarSeccionNotasMovimientos();
        $('.mensajeNotas').hide();
        $('.formularioNotas').show();
    //Si hay más casinos seleccionados: SOLO habilitar las notas nuevas
    } else {
        habilitarNotasNuevas();
    }
});

function limpiarSeccionNotas() {
  $('#moldeNotaNueva .tiposMovimientos option').remove(); //Eliminar los tipos de movimientos
  $('.notaNueva').not('#moldeNotaNueva').remove(); //Eliminar las filas de notas
}

function limpiarNotasMovimientos() {
    $('.notaMov').not('#moldeNotaMov').remove();                              //Eliminar todas las notas de fila (menos el molde)
    $('#cantidad_movimientos').val(0);                                          //Resetear la cantidad de movimientos disponibles
    $('#btn-notaMov').parent().show();                                          //Mostrar el botón de agregar notas
}

function habilitarSeccionNotasMovimientos() {
  $('.mensajeNotas').hide();
  $('.formularioNotas').show();
  movimientosSinExpediente();
}

function movimientosSinExpediente() {
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var formData = {
      id_casino: id_casinos_seleccionados,
  }

  $.ajax({
      type: "POST",
      url: 'movimientos/movimientosSinExpediente',
      data: formData,
      success: function (data) {
        console.log('Mov sin expedientes: ', data);
        var cantidadMovimientos = data.logs.length;
        //
        $('#cantidad_movimientos').val(cantidadMovimientos);
        // mostrarMovimientosDisponibles(cantidadMovimientos);
        var select = $('#movimientosDisponibles');
        var optionDefecto = $('<option>').val(0).text("Seleccione un movimiento");

        select.find('option').remove();
        select.append(optionDefecto);

        for (var i = 0; i < data.logs.length; i++) {
            var option = $('<option>').val(data.logs[i].id_log_movimiento)
                                      .text(data.logs[i].nombre + ' -  ' +data.logs[i].descripcion)
                                      .attr('data-casino',data.logs[i].id_casino);
            select.append(option);
        }


      },
      error: function (data) {
        console.log('Error: ', data);
      }
  });

}


function habilitarNotasNuevas() {
  $('#secNotas .mensajeNotas').hide();
  $('#secNotas .formularioNotas').show();
  $('#secMov .mensajeNotas').show();
  $('#secMov .formularioNotas').hide();
}


















function obtenerTiposMovimientos() {
    var id_expediente = $('#id_expediente').val();

    $.get('expedientes/tiposMovimientos/' + id_expediente, function(data) {
          var optionDefecto = $('<option>').val(0).text("- Tipo de movimiento -");
          $('#moldeNotaNueva .tiposMovimientos').append(optionDefecto);
          for (var i = 0; i < data.length; i++) {
            var option = $('<option>').val(data[i].id_tipo_movimiento).text(data[i].descripcion);
            $('#moldeNotaNueva .tiposMovimientos').append(option);
          }
    });
}





function mostrarMovimientosDisponibles(cantidadMovimientos) {
  if (cantidadMovimientos == 1) $('#cantidadMovimientos').text('1 Movimiento disponible');
  else $('#cantidadMovimientos').text(cantidadMovimientos + ' Movimientos disponibles');
}






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

//Quitar eventos de la tecla Enter y guardar
$('#collapseFiltros').on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-buscar').click();
    }
});

//Quitar eventos de la tecla Enter y guardar
$(document).on('keypress',function(e){
    if(e.which == 13 && $('#modalExpediente').is(':visible')) {
      e.preventDefault();
      $('#btn-guardar').click();
    }
});


//DATETIMEPICKER de las fechas
function habilitarDTP() {
  //Resetear DTP (Click en la cruz)
  $('#dtpFechaInicio span:first').click();
  $('#dtpFechaPase span:first').click();

  $('#dtpFechaPase').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    container: $('#modalExpediente'),
  });

  $('#dtpFechaInicio').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    container: $('#modalExpediente'),
  });
}

//Agregar nueva disposicion en el modal
$('#btn-agregarDisposicion').click(function(){
  agregarDisposicion(null,true);
});

//Agregar nuevo movimiento en el modal
$('#btn-agregarMovimientos').click(function(){
  agregarMovimientos(null,true);
});

$(document).on('click','.borrarDisposicion',function(){
  $(this).parent().parent().remove();
});

$(document).on('click','.borrarMovimiento',function(){
  $(this).parent().parent().remove();
});

// $(document).on('change','#selectCasinos',function(){
//   var cas = $(this).val();
//   generarListaAsocMovimientos(cas);
//
//   console.log(cas);
// });

function generarListaMovimientos(expediente){

  $.get("expedientes/tiposMovimientos/" + id_expediente, function(data){
      console.log(data);
        selectMov.children().remove();
        for(i=0 ; i<data.length ; i++){
          selectMov.append($('<option>').val(data[i].id_tipo_movimiento).text(data[i].descripcion));
        }
  });

}

function generarListaAsocMovimientos(casino){

  var espacio = ' | ';

  $.get("movimientos/movimientosSinExpediente/" + casino, function(data){
    console.log(data);
      asocMov.children().remove();
      console.log(data.length);
      for(i=0; i<data.logs.length; i++){
        asocMov.append($('<input>').attr('type','checkbox').addClass('asociaMovimiento').val(data.logs[i].id_log_movimiento))
        .append($('<span>').text(data.logs[i].descripcion))
        .append($('<span>').text(espacio))
        .append($('<span>').text(data.logs[i].fecha))
        .append($('<br>'));
      }
  });
}

//variable global para selectMovimientos
var asocMov = $('#columnaAsociar').append($('<div>'));
var selectMov = $('<select>').addClass('form-control').attr('id','selectMovimientos');

//Contador global que sirve para generar el id de cada DTP de notas nuevas
var nro_nota = 0;

$('#btn-notaNueva').click(function(e){
    nro_nota = nro_nota + 1;                                                    //Se incrementa en 1, para que cada DTP tenga un ID diferente

    e.preventDefault();
    var clonNota = $('#moldeNotaNueva').clone();
    clonNota.removeAttr('id');
    clonNota.show();

    clonNota.find('.dtpFechaNota').attr('data-link-field', nro_nota + '_fecha');
    clonNota.find('.fecha_notaNueva').attr('id', nro_nota + '_fecha');

    clonNota.find('.dtpFechaNota').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd MM yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2,
    });

    $('#moldeNotaNueva').before(clonNota);
});

$('#modalExpediente').on('click','.borrarNota', function(){
    $(this).parent().parent().remove();
});

$('#modalExpediente').on('click','.borrarNotaMov', function(){
    var id_movimiento = $(this).attr('id');

    var cantidadMovimientos = $('#cantidad_movimientos').val();                 //Cantidad de movimientos disponibles
    $(this).parent().parent().remove();                                         //Se borra la fila
    cantidad_movimientos = parseInt(cantidadMovimientos) + 1;                   //Se aumenta la cantidad de movimientos disponibles

    $('#cantidad_movimientos').val(cantidad_movimientos);                       //Se setea la nueva cantidad de movimientos
    $('#secMov .agregarNota').show();                                           //Mostrar el botón para agregar notas
    $('#movimientosDisponibles option[value="'+ id_movimiento +'"]').show();    //Mostrar el movimiento borrado nuevamente en el selector
});




$('#btn-notaMov').click(function(e){
  e.preventDefault();

  var cantidadMovimientos = $('#cantidad_movimientos').val();                   //Cantidad de movimientos disponibles para crear notas
  var id_movimiento = $('#movimientosDisponibles').val();                       //Se obtiene el id del movimiento

  if (cantidadMovimientos > 0 && id_movimiento != 0) {                          //Si se seleccionó algún movimiento...

    $('#movimientosDisponibles option[value="' + id_movimiento + '"]').hide();  //Ocultar la opción del movimiento que se va a agregar
    $('#movimientosDisponibles').val(0);                                        //Cambiar el selector a la opción por defecto

    var clonNota = $('#moldeNotaMov').clone();

    $.get('movimientos/obtenerMovimiento/' + id_movimiento, function(data) {    //Se trae toda la información del movimiento seleccionado

        clonNota.removeAttr('id');
        clonNota.show();

        //Generar un ID (id_movimiento_fecha) para linkear el DTP con el input oculto que guarda el 'date' elegido
        clonNota.find('.dtpFechaMov').attr('data-link-field', id_movimiento + '_fecha');
        clonNota.find('.fecha_notaMov').attr('id',id_movimiento + '_fecha');
        //Aplicar la librería a cada datetimepicker
        clonNota.find('.dtpFechaMov').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'dd MM yyyy',
          pickerPosition: "bottom-left",
          startView: 4,
          minView: 2,
        });

        var fecha = convertirDate(data.movimiento.fecha);
        var descripcion = fecha + " - " + data.tipo.descripcion + " - " + data.casino.nombre;
        clonNota.find('.descripcionTipoMovimiento').val(descripcion).attr('id', id_movimiento);
        clonNota.find('.borrarNotaMov').attr('id', id_movimiento);
        $('#moldeNotaMov').before(clonNota);                                    //Agregar la nota con el movimiento existente para editarla

        cantidadMovimientos = cantidadMovimientos - 1;
        $('#cantidad_movimientos').val(cantidadMovimientos);                    //Disminuir en 1 el contador de cantidad de movimientos

        if (cantidadMovimientos == 0) {                                         //Si no quedan más movimientos ocultar el botón de agregar
          $('#btn-notaMov').parent().hide();
        }
    });

  }

});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| GESTIONAR EXPEDIENTES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});


//Mostrar modal para agregar nuevo Expediente
$('#btn-nuevo').click(function(e){
    e.preventDefault();

    $('#modalExpediente').find('.modal-footer').children().show();
    $('#modalExpediente').find('.modal-body').children().show();
    $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();

    //Ocultar errores
    $('#error_nav_config').hide();
    $('#error_nav_notas').hide();
    $('#error_nav_mov').hide();

    habilitarDTP();

    nro_nota = 0; //Reiniciar el contador de notas

    $('#navMov').parent().show();
    $('#navConfig').click(); //Empezar por la sección de configuración
    $('.formularioNotas').hide(); //Ocultar los formularios de notas
    $('.notasCreadas').hide(); //Ocultar las notas creadas (es del modal modificar expediente)
    $('.casinosExp').prop('checked',false); //Deseleccionar todos los casinos
    $('.mensajeExito').show();
    $('.mensajeNotas').show();


    limpiarModal();
    $('#concepto').val(' ');
    $('#tema').val(' ');

    habilitarControles(true);
    $('.modal-title').text('NUEVO EXPEDIENTE');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('#btn-guardar').val("nuevo");
    $('#btn-cancelar').text('CANCELAR');
    $('#asociar').show();

    // generarListaMovimientos(0);
    // generarListaAsocMovimientos();

    obtenerTiposMovimientos();

    $('#modalExpediente').modal('show');
});

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){

  $('#modalExpediente').find('.modal-footer').children().show();
  $('#modalExpediente').find('.modal-body').children().show();
  $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();

      limpiarModal();
      //Ocultar errores
      $('#error_nav_config').hide();
      $('#error_nav_notas').hide();
      $('#error_nav_mov').hide();


      $('.modal-title').text('| VER EXPEDIENTE');
      $('.modal-header').attr('style','background: #4FC3F7');
      $('#btn-cancelar').text('SALIR');

      $('#navConfig').click(); //Empezar por la sección de configuración

      var id_expediente = $(this).val();

      $.get("expedientes/obtenerExpediente/" + id_expediente, function(data){
          console.log('aqui',data);
          generarListaMovimientos(id_expediente);
          mostrarExpedienteModif(data.expediente,data.casinos,data.resolucion,data.disposiciones,data.notas,data.notasConMovimientos,false);
          habilitarControles(false);

          //Deshabilitar sección de 'notas & movimientos'
          $('#navMov').parent().hide();
          $('.notasNuevas').hide();

          $('#modalExpediente').modal('show');
      });
});

//Mostrar modal con los datos del Casino cargados
$(document).on('click','.modificar',function(){
    $('#mensajeExito').hide();

    $('#modalExpediente').find('.modal-footer').children().show();
    $('#modalExpediente').find('.modal-body').children().show();
    $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();

    $('.casinosExp').prop('checked',false).prop('disabled',false);
    limpiarModal();
    habilitarDTP();
    habilitarControles(true);
    $('.modal-title').text('| MODIFICAR EXPEDIENTE');
    $('.modal-header').attr('style','background: #FFB74D');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-warningModificar');
    $('#btn-cancelar').text('CANCELAR');
    $('#asociar').hide();

    $('#navMov').parent().show();
    $('#navConfig').click(); //Empezar por la sección de configuración

    //Ocultar errores
    $('#error_nav_config').hide();
    $('#error_nav_notas').hide();
    $('#error_nav_mov').hide();

    var id_expediente = $(this).val();
    $('#id_expediente').val(id_expediente);

    obtenerTiposMovimientos();

    $.get("expedientes/obtenerExpediente/" + id_expediente, function(data){
        console.log(data);
        generarListaMovimientos(id_expediente);
        // mostrarExpedienteModif(data.expediente,data.casinos,data.resolucion,data.disposiciones,data.log_movimientos,data.tipos_movimientos,true);
        mostrarExpedienteModif(data.expediente,data.casinos,data.resolucion,data.disposiciones,data.notas,data.notasConMovimientos,false);
        habilitarControles(true);
        $('#btn-guardar').val("modificar");
        $('#modalExpediente').modal('show');
    });

});

//Borrar Casino y remover de la tabla
$(document).on('click','.eliminar',function(){
    //Cambiar colores modal
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').removeAttr('style');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    var id_expediente = $(this).val();
    $('#btn-eliminarModal').val(id_expediente);
    $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })
    var id_expediente = $(this).val();

    $.ajax({
        type: "DELETE",
        url: "expedientes/eliminarExpediente/" + id_expediente,
        success: function (data) {
          //Remueve de la tabla
          $('#expediente' + id_expediente).remove();
          $("#tablaExpedientes").trigger("update");
          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

function obtenerNotasNuevas() {
  var notas_nuevas = [];

  $.each($('.notaNueva').not('#moldeNotaNueva'), function (index, value) {
      var nota = {
        fecha: $(this).find('.fecha_notaNueva').val(),
        identificacion: $(this).find('.identificacion').val(),
        detalle: $(this).find('.detalleNota').val(),
        id_tipo_movimiento: $(this).find('.tiposMovimientos').val(),
      }

      notas_nuevas.push(nota);
  });

  return notas_nuevas;
}

function obtenerNotasMov() {
  var notas_mov = [];

  $.each($('.notaMov').not('#moldeNotaMov'), function (index, value) {
      var nota = {
        fecha: $(this).find('.fecha_notaMov').val(),
        identificacion: $(this).find('.identificacion').val(),
        detalle: $(this).find('.detalleNota').val(),
        id_log_movimiento: $(this).find('.descripcionTipoMovimiento').attr('id'),
      }

      notas_mov.push(nota);
  });

  return notas_mov;
}

//Cuando aprieta guardar en el modal de Nuevo/Modificar expediente
$('#btn-guardar').click(function (e) {
    $('#mensajeExito').hide();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    var fecha_pase = $('#fecha_pase').val();
    var fecha_iniciacion = $('#fecha_inicio').val();

    var resolucion = null;
    if($('#nro_resolucion').val() != '' || $('#nro_resolucion_anio').val() != ''){
      var resolucion = {
        nro_resolucion: $('#nro_resolucion').val(),
        nro_resolucion_anio: $('#nro_resolucion_anio').val(),
      }
    }

    var disposiciones = [];
    $('#columnaDisposicion .disposicion').not('#moldeDisposicion').each(function(){
        var disposicion = {
          nro_disposicion: $(this).find('.nro_disposicion').val(),
          nro_disposicion_anio: $(this).find('.nro_disposicion_anio').val(),
          descripcion: $(this).find('.descripcion_disposicion').val(),
        }
        disposiciones.push(disposicion);
    });

    var notas = obtenerNotasNuevas();
    var notas_asociadas = obtenerNotasMov();


    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = ((state == "modificar") ? 'expedientes/modificarExpediente':'expedientes/guardarExpediente');
    var formData = {
      id_expediente: $('#id_expediente').val(),
      nro_exp_org: $('#nro_exp_org').val(),
      nro_exp_interno: $('#nro_exp_interno').val(),
      nro_exp_control: $('#nro_exp_control').val(),
      casinos: id_casinos_seleccionados ,
      fecha_pase: fecha_pase,
      fecha_iniciacion: fecha_iniciacion,
      remitente: $('#remitente').val(),
      concepto: $('#concepto').val(),
      iniciador: $('#iniciador').val(),
      tema: $('#tema').val(),
      ubicacion_fisica: $('#ubicacion').val(),
      destino: $('#destino').val(),
      nro_cuerpos: $('#nro_cuerpos').val(),
      nro_folios: $('#nro_folios').val(),
      anexo: $('#anexo').val(),
      resolucion: resolucion,
      disposiciones: disposiciones,
      notas: notas,
      notas_asociadas: notas_asociadas,
    }
    console.log(formData);

    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        // processData: false,
        // contentType:false,
        // cache:false,
        beforeSend: function(data){
          console.log('Empezó');
          $('#modalExpediente').find('.modal-footer').children().hide();
          $('#modalExpediente').find('.modal-body').children().hide();
          $('#modalExpediente').find('.modal-body').children('#iconoCarga').show();
        },
        success: function (data) {

            $('#btn-buscar').trigger('click');

            // var expediente = generarFilaTabla(data.expediente,data.casino.nombre);

            if (state == "nuevo"){ //Si está agregando agrega una fila con el nuevo expediente
              $('#mensajeExito h3').text('Creación Exitosa');
              $('#mensajeExito p').text('El expediente fue creado con éxito');
              $('#mensajeExito .cabeceraMensaje').removeClass('modificar');
            }else{ //Si está modificando reemplaza la fila con el expediente modificado
              $('#mensajeExito h3').text('Modificación Exitosa');
              $('#mensajeExito p').text('El expediente fue modificado con éxito');
              $('#mensajeExito .cabeceraMensaje').addClass('modificar');
            }

            $('#modalExpediente').modal('hide');
            $('#mensajeExito').show();

        },
        error: function (data) {
            console.log('Error:', data);

            $('#modalExpediente').find('.modal-footer').children().show();
            $('#modalExpediente').find('.modal-body').children().show();
            $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();


            var response = JSON.parse(data.responseText);

            // limpiarAlertas(); ESTE TIENE QUE CAMBIARSE

            //Si hay algun campo vacio en nro_exp
            var nro_exp_org_vacio = typeof response.nro_exp_org != "undefined";
            var nro_exp_interno_vacio = typeof response.nro_exp_interno != "undefined";
            var nro_exp_control_vacio = typeof response.nro_exp_control != "undefined";

            //Modelo
            // if(typeof response.nro_admin !== 'undefined'){
            //   mostrarErrorValidacion($('#nro_admin'),response.nro_admin[0],true);
            //   $('#error_nav_maquina').show();
            // }

            //Ocultar errores
            $('#error_nav_config').hide();
            $('#error_nav_notas').hide();
            $('#error_nav_mov').hide();


            //////////////////////////  ALERTAS DE CONFIGURACIÓN /////////////////////////

            if(typeof response.casinos !== 'undefined'){
              mostrarErrorValidacion($('#contenedorCasinos'),"Debe seleccionar al menos un casino",true);
              // $('#error_nav_maquina').show();
            }

            if (nro_exp_org_vacio || nro_exp_interno_vacio || nro_exp_control_vacio) {
                if(nro_exp_org_vacio) mostrarErrorValidacion($('#nro_exp_org'),response.nro_exp_org[0],false);
                if(nro_exp_interno_vacio) mostrarErrorValidacion($('#nro_exp_interno'),response.nro_exp_interno[0],false);
                if(nro_exp_control_vacio) mostrarErrorValidacion($('#nro_exp_control'),response.nro_exp_control[0],false);
                $('#error_nav_config').show();
            }

            if (typeof response.nro_cuerpos != "undefined") {
              mostrarErrorValidacion($('#nro_cuerpos'),response.nro_cuerpos[0],false);
              $('#error_nav_config').show();
            }

            if (typeof response.fecha_iniciacion != "undefined") {
              mostrarErrorValidacion($('#dtpFechaInicio input'),response.fecha_iniciacion[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.fecha_pase != "undefined") {
              mostrarErrorValidacion($('#dtpFechaPase input'),response.fecha_pase[0],false);
              $('#error_nav_config').show();
            }

            if (typeof response.destino != "undefined") {
              mostrarErrorValidacion($('#destino'),response.destino[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.ubicacion_fisica != "undefined") {
              mostrarErrorValidacion($('#ubicacion'),response.ubicacion_fisica[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.iniciador != "undefined") {
              mostrarErrorValidacion($('#iniciador'),response.iniciador[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.remitente != "undefined") {
              mostrarErrorValidacion($('#remitente'),response.remitente[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.concepto != "undefined") {
              mostrarErrorValidacion($('#concepto'),response.concepto[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.tema != "undefined") {
              mostrarErrorValidacion($('#tema'),response.tema[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.nro_cuerpos != "undefined") {
              mostrarErrorValidacion($('#nro_cuerpos'),response.nro_cuerpos[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.nro_folios != "undefined") {
              mostrarErrorValidacion($('#nro_folios'),response.nro_folios[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.anexo != "undefined") {
              mostrarErrorValidacion($('#anexo'),response.anexo[0],false);
              $('#error_nav_config').show();
            }
            var errorRes = ' ';
            if (typeof response["resolucion.nro_resolucion"] != "undefined") {
              mostrarErrorValidacion($('#nro_resolucion'),response['resolucion.nro_resolucion'][0],false);
              $('#error_nav_config').show();
                // $('#nro_resolucion').addClass('alerta');
                // errorRes += response["resolucion.nro_resolucion"][0] + "\n";
            }
            if (typeof response["resolucion.nro_resolucion_anio"] != "undefined") {
              mostrarErrorValidacion($('#nro_resolucion_anio'),response['resolucion.nro_resolucion_anio'][0],false);
              $('#error_nav_config').show();
                // $('#nro_resolucion_anio').addClass('alerta');
                // errorRes += response["resolucion.nro_resolucion_anio"][0];
            }
            // if(errorRes != ' '){
            //   $('#alerta-resolucion').text(errorRes).show();
            // }

            var i=0;
            $('#columnaDisposicion .disposicion').not('#moldeDisposicion').each(function(){
              // var error=' ';
              if(typeof response['disposiciones.'+ i +'.nro_disposicion'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('.nro_disposicion'),response['disposiciones.'+ i +'.nro_disposicion'][0],false);
                $('#error_nav_config').show();
              }
              if(typeof response['disposiciones.'+ i +'.nro_disposicion_anio'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('.nro_disposicion_anio'),response['disposiciones.'+ i +'.nro_disposicion_anio'][0],false);
                $('#error_nav_config').show();
              }
              if(typeof response['disposiciones.'+ i +'.descripcion'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('.descripcion_disposicion'),response['disposiciones.'+ i +'.descripcion'][0],false);
                $('#error_nav_config').show();
              }

              i++;
            })

            // var i=0;
            // $('#columna .Movimiento').each(function(){
            //   var error=' ';
            //   if(error != ' '){
            //   var alerta='<div class="col-xs-12"><span class="alertaTabla alertaSpan">'+error+'</span></div>';
            //     $(this).append(alerta);
            //   }
            //   i++;
            // })

            //////////////////////////  ALERTAS DE NOTAS /////////////////////////
            var i = 0;

            $('.notaNueva').not('#moldeNotaNueva').each(function(){
                if(typeof response['notas.'+ i +'.fecha'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.dtpFechaNota input'),response['notas.'+ i +'.fecha'][0],false);
                  $('#error_nav_notas').show();
                }
                if(typeof response['notas.'+ i +'.identificacion'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.identificacion'),response['notas.'+ i +'.identificacion'][0],false);
                  $('#error_nav_notas').show();
                }
                if(typeof response['notas.'+ i +'.detalle'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.detalleNota'),response['notas.'+ i +'.detalle'][0],false);
                  $('#error_nav_notas').show();
                }
                if(typeof response['notas.'+ i +'.id_tipo_movimiento'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.tiposMovimientos'),response['notas.'+ i +'.id_tipo_movimiento'][0],false);
                  $('#error_nav_notas').show();
                }

                i++;
            });

            var j = 0;

            $('.notaMov').not('#moldeNotaMov').each(function(){
                if(typeof response['notas_asociadas.'+ j +'.fecha'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.dtpFechaMov input'),response['notas_asociadas.'+ j +'.fecha'][0],false);
                  $('#error_nav_mov').show();
                }
                if(typeof response['notas_asociadas.'+ j +'.identificacion'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.identificacion'),response['notas_asociadas.'+ j +'.identificacion'][0],false);
                  $('#error_nav_mov').show();
                }
                if(typeof response['notas_asociadas.'+ j +'.detalle'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.detalleNota'),response['notas_asociadas.'+ j +'.detalle'][0],false);
                  $('#error_nav_mov').show();
                }

                j++;
            });

            ///////////////////////  ALERTAS NOTAS Y MOVS ////////////////////////
            // $('#error_nav_mov').hide();
        }
    });
});

//Busqueda
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
  console.log($('#herramientasPaginacion').getPageSize());
  var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    nro_exp_org: $('#B_nro_exp_org').val(),
    nro_exp_interno: $('#B_nro_exp_interno').val(),
    nro_exp_control: $('#B_nro_exp_control').val(),
    id_casino: $('#B_casino').val(),
    fecha_inicio: $('#fecha_inicio1').val(),
    ubicacion_fisica: $('#B_ubicacion').val(),
    remitente: $('#B_remitente').val(),
    concepto: $('#B_concepto').val(),
    tema: $('#B_tema').val(),
    destino: $('#B_destino').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  console.log(formData);
  $.ajax({
      type: 'POST',
      url: 'expedientes/buscarExpedientes',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(page_number,page_size,data.total);
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.expedientes.total,clickIndice);
        $('#cuerpoTabla tr').remove();

        for(var i = 0; i < data.expedientes.data.length; i++) {
          generarFilaTabla(data.expedientes.data[i]);
        }

        $('[data-toggle="tooltip"]').tooltip();

        $('#herramientasPaginacion').generarIndices(page_number,page_size,data.expedientes.total,clickIndice);
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){

  $('#tablaResultados th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
        $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(expediente){
      var fila = $(document.createElement('tr'));
      var ubicacion = expediente.ubicacion_fisica == "" ? "-" : expediente.ubicacion_fisica;
      // var fecha = expediente.fecha_iniciacion == "" ? "-" : expediente.fecha_iniciacion;
      expediente.ubicacion_fisica != null ? ubicacion=expediente.ubicacion_fisica : ubicacion='-' ;
      expediente.fecha_iniciacion != null ? fecha= convertirDate(expediente.fecha_iniciacion) : fecha='-' ;

      fila.attr('id','expediente' + expediente.id_expediente)
          .append($('<td>')
              .addClass('col-xs-3')
              .text(expediente.nro_exp_org + '-' + expediente.nro_exp_interno + '-' + expediente.nro_exp_control)
          )
          .append($('<td>')
              .addClass('col-xs-3')
              .text(fecha)
          )

          .append($('<td>')
              .addClass('col-xs-3')
              .text(expediente.nombre)
          )

          .append($('<td>')
              .addClass('col-xs-3')
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                  )
                  .attr({'data-toggle':'tooltip','data-placement':'top','title':'VER MÁS','data-delay':'{"show":"500", "hide":"50"}'})
                  .addClass('btn').addClass('btn-info').addClass('detalle')
                  .attr('value',expediente.id_expediente)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                  )
                  .attr({'data-toggle':'tooltip','data-placement':'top','title':'MODIFICAR','data-delay':'{"show":"500", "hide":"50"}'})
                  .addClass('btn').addClass('btn-warning').addClass('modificar')
                  .attr('value',expediente.id_expediente)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
                  )
                  .attr({'data-toggle':'tooltip','data-placement':'top','title':'ELIMINAR','data-delay':'{"show":"500", "hide":"50"}'})
                  .addClass('btn').addClass('btn-danger').addClass('eliminar')
                  .attr('value',expediente.id_expediente)
              )
          )
        $('#cuerpoTabla').append(fila);
}

function habilitarControles(valor){
  $('#nro_exp_org').prop('readonly',!valor);
  $('#nro_exp_interno').prop('readonly',!valor);
  $('#nro_exp_control').prop('readonly',!valor);
  $('.casinosExp').prop('disabled',!valor);
  $('#dtpFechaPase input').prop('readonly',!valor);
  $('#dtpFechaInicio input').prop('readonly',!valor);
  $('#destino').prop('readonly',!valor);
  $('#ubicacion').prop('readonly',!valor);
  $('#iniciador').prop('readonly',!valor);
  $('#remitente').prop('readonly',!valor);
  $('#concepto').prop('readonly',!valor);
  $('#tema').prop('readonly',!valor);
  $('#nro_cuerpos').prop('readonly',!valor);
  $('#nro_folios').prop('readonly',!valor);
  $('#anexo').prop('readonly',!valor);
  $('#nro_resolucion').prop('readonly',!valor);
  $('#nro_resolucion_anio').prop('readonly',!valor);

  $('#columnaDisposicion .Disposicion').each(function(){
    $(this).find('#nro_disposicion').prop('readonly',!valor);
    $(this).find('#nro_disposicion_anio').prop('readonly',!valor);
  });

  $('#columnaMovimientos .Movimiento').each(function(){
    $(this).find('#selectMovimientos').prop('readonly',!valor);
  });

  if(valor){// nuevo y modificar
    $('#btn-agregarDisposicion').show();
    $('#btn-agregarMovimientos').show();
    $('#btn-guardar').prop('disabled',false).show();
    $('#btn-guardar').css('display','inline-block');

  }
  else{// ver detalle
    $('#btn-agregarDisposicion').hide();
    $('#btn-agregarMovimientos').hide();
    $('#btn-guardar').prop('disabled',true).hide();
    $('#btn-guardar').css('display','none');
  }
}

function limpiarModal(){
  $('#frmExpediente').trigger('reset');
  $('#modalExpediente input').val('');
  // $('div').remove(".Disposicion");
  $('#id_expediente').val(0);


  $('#columnaDisposicion .disposicion').not('#moldeDisposicion').remove();

  $('.filaNota').not('#moldeFilaNota').remove(); //Eliminar todas las notas creadas

  $('#moldeNotaNueva .tiposMovimientos option').remove(); //Eliminar los tipos de movimientos
  $('.notaNueva').not('#moldeNotaNueva').remove(); //Eliminar las filas de notas nuevas
  $('.notaMov').not('#moldeNotaMov').remove(); //Eliminar las filas de notas con movimientos existentes

  limpiarAlertas();
}

function limpiarAlertas(){

  ocultarErrorValidacion($('#nro_exp_org'));
  ocultarErrorValidacion($('#nro_exp_interno'));
  ocultarErrorValidacion($('#nro_exp_control'));

  ocultarErrorValidacion($('#contenedorCasinos'));

  ocultarErrorValidacion($('#dtpFechaPase input'));
  ocultarErrorValidacion($('#dtpFechaInicio input'));
  ocultarErrorValidacion($('#destino'));
  ocultarErrorValidacion($('#ubicacion'));
  ocultarErrorValidacion($('#iniciador'));
  ocultarErrorValidacion($('#remitente'));
  ocultarErrorValidacion($('#concepto'));
  ocultarErrorValidacion($('#tema'));
  ocultarErrorValidacion($('#nro_cuerpos'));
  ocultarErrorValidacion($('#nro_folios'));
  ocultarErrorValidacion($('#anexo'));
  ocultarErrorValidacion($('#nro_resolucion'));
  ocultarErrorValidacion($('#nro_resolucion_anio'));

  // $('#fecha_pase').removeClass('alerta');
  // $('#alerta-fechaPase').text('').hide();
  // $('#fecha_inicio').removeClass('alerta');
  // $('#alerta-fechaInicio').text('').hide();
  // $('#destino').removeClass('alerta');
  // $('#alerta-destino').text('').hide();
  // $('#ubicacion').removeClass('alerta');
  // $('#alerta-ubicacion').text('').hide();
  // $('#iniciador').removeClass('alerta');
  // $('#alerta-iniciador').text('').hide();
  // $('#remitente').removeClass('alerta');
  // $('#alerta-remitente').text('').hide();

  // $('#concepto').removeClass('alerta');
  // $('#alerta-concepto').text('').hide();
  // $('#tema').removeClass('alerta');
  // $('#alerta-tema').text('').hide();
  // $('#nro_cuerpos').removeClass('alerta');
  // $('#alerta-nroCuerpos').text('').hide();
  // $('#nro_folios').removeClass('alerta');
  // $('#alerta-nroFolios').text('').hide();
  // $('#anexo').removeClass('alerta');
  // $('#alerta-anexo').text('').hide();
  // $('#nro_resolucion').removeClass('alerta');
  // $('#nro_resolucion_anio').removeClass('alerta');
  // $('#alerta-resolucion').text('').hide();

  $('#columna .Disposicion').each(function(){
    $(this).find('#nro_disposicion').removeClass('alerta');
    $(this).find('#nro_disposicion_anio').removeClass('alerta');
  });
  $('.alertaTabla').remove();
}

function mostrarExpediente(expediente,casinos,resolucion,disposiciones,movimientos,editable){
  $('#id_expediente').val(expediente.id_expediente);
  $('#nro_exp_org').val(expediente.nro_exp_org);
  $('#nro_exp_control').val(expediente.nro_exp_control);
  $('#nro_exp_interno').val(expediente.nro_exp_interno);


  for (var i = 0; i < casinos.length; i++) {
    $('#'+casinos[i].id_casino).prop("checked",true).prop('disabled',true);
  }


  //$('#selectCasinos').val(casino.id_casino);
  if(expediente.fecha_pase != null){
    var fecha_pase = expediente.fecha_pase.split('-');
    $('#fecha_pase').val(fecha_pase[2] + " / " + fecha_pase[1] + " / " + fecha_pase[0]);
  }
  if(expediente.fecha_iniciacion != null){
    var fecha_inicio = expediente.fecha_iniciacion.split('-');
    $('#fecha_inicio').val(fecha_inicio[2] + " / " + fecha_inicio[1] + " / " + fecha_inicio[0]);
  }
  $('#destino').val(expediente.destino);
  $('#ubicacion').val(expediente.ubicacion_fisica);
  $('#iniciador').val(expediente.iniciador);
  $('#remitente').val(expediente.remitente);
  $('#concepto').val(expediente.concepto);
  $('#tema').val(expediente.tema);
  $('#nro_cuerpos').val(expediente.nro_cuerpos);
  $('#nro_folios').val(expediente.nro_folios);
  $('#anexo').val(expediente.anexo);
  if(resolucion != null){
    $('#nro_resolucion').val(resolucion.nro_resolucion);
    $('#nro_resolucion_anio').val(resolucion.nro_resolucion_anio);
  }
  if(disposiciones != null){
    for(var index=0; index<disposiciones.length; index++){
      agregarDisposicion(disposiciones[index],editable);
    }
  }
  if(movimientos != null){
    for(var index=0; index<movimientos.length; index++){
      agregarMovimientos(movimientos[index],editable);
    }
  }
}

function mostrarExpedienteModif(expediente,casinos,resolucion,disposiciones,notas,notasConMovimientos,editable){

  $('#nro_exp_org').val(expediente.nro_exp_org);
  $('#nro_exp_control').val(expediente.nro_exp_control);
  $('#nro_exp_interno').val(expediente.nro_exp_interno);
  console.log('ddddd',casinos);

  for (var i = 0; i < casinos.length; i++) {
    $('#'+ casinos[i].id_casino).prop('checked',true).prop('disabled',true);
  }

  if (casinos.length > 0) $('.casinosExp').change();

  if(expediente.fecha_pase != null){
    // var fecha_pase = expediente.fecha_pase.split('-');
    $('#dtpFechaPase input').val(convertirDate(expediente.fecha_pase));
    $('#fecha_pase').val(expediente.fecha_pase);
  }
  if(expediente.fecha_iniciacion != null){
    $('#dtpFechaInicio input').val(convertirDate(expediente.fecha_iniciacion));
    $('#fecha_inicio').val(expediente.fecha_iniciacion);
  }
  $('#destino').val(expediente.destino);
  $('#ubicacion').val(expediente.ubicacion_fisica);
  $('#iniciador').val(expediente.iniciador);
  $('#remitente').val(expediente.remitente);
  $('#concepto').val(expediente.concepto);
  $('#tema').val(expediente.tema);
  $('#nro_cuerpos').val(expediente.nro_cuerpos);
  $('#nro_folios').val(expediente.nro_folios);
  $('#anexo').val(expediente.anexo);

  if(resolucion != null){
    $('#nro_resolucion').val(resolucion.nro_resolucion);
    $('#nro_resolucion_anio').val(resolucion.nro_resolucion_anio);
  }
  if(disposiciones.length != 0){
    for(var index=0; index<disposiciones.length; index++){
      agregarDisposicion(disposiciones[index],editable);
    }
  }
  // if(log != null){
  //   for(var index=0; index<log.length; index++){
  //     agregarLogModif(log[index],editable);
  //   }
  // }


  //MOSTRAR NOTAS!!!!
  console.log(notas);
  console.log(notasConMovimientos);
  var i = 0;
  var j = 0;

  for (i; i < notas.length; i++) {
      agregarNotaSinMovimiento(notas[i]);
  }
  for (j; j < notasConMovimientos.length; j++) {
      agregarNotaConMovimiento(notasConMovimientos[j]);
  }

  console.log("Hay notas? ", i, j);

  //Si hay notas mostrarlas
  if (i || j) {
      $('.notasCreadas').show();
  }else {
      $('.notasCreadas').hide();
  }

  // if(movimientos != null){
  //   for(var index=0; index<movimientos.length; index++){
  //     agregarMovimientos(movimientos[index],editable);
  //   }
  // }
}

function agregarNotaSinMovimiento(nota) {
  var fila = $('#moldeFilaNota').clone();

  fila.show();
  fila.removeAttr('moldeFilaNota');

  fila.attr('id',nota.id_nota);

  fila.find('.identificacion').text(nota.identificacion);
  fila.find('.fecha').text(convertirDate(nota.fecha));
  fila.find('.movimiento').text('-');
  // fila.find('.detalleNota').text(nota.detalle);

  $('#tablaNotasCreadas tbody').append(fila);
}

function agregarNotaConMovimiento(nota) {
  var fila = $('#moldeFilaNota').clone();

  fila.show();
  fila.removeAttr('moldeFilaNota');

  fila.attr('id',nota.id_nota);

  fila.find('.identificacion').text(nota.identificacion);
  fila.find('.fecha').text(convertirDate(nota.fecha));
  fila.find('.movimiento').text(nota.movimiento);
  // fila.find('.detalleNota').text(nota.detalle);

  $('#tablaNotasCreadas tbody').append(fila);
}


function agregarDisposicion(disposicion, editable){
    var moldeDisposicion = $('#moldeDisposicion').clone();

    moldeDisposicion.removeAttr('id');

    //Para el modificar
     moldeDisposicion.attr('id', disposicion.id_disposicion);
     moldeDisposicion.find('.nro_resolucion').val(disposicion.nro_disposicion);
     moldeDisposicion.find('.nro_resolucion_anio').val(disposicion.nro_disposicion_anio);
     moldeDisposicion.find('.descripcion_disposicion').val(disposicion.descripcion);

    moldeDisposicion.show();

    $('#columnaDisposicion').append(moldeDisposicion);


  // var id_disposicion = ((disposicion != null) ? disposicion.id_disposicion: null);
  // var nro_disposicion = ((disposicion != null) ? disposicion.nro_disposicion: null);
  // var nro_disposicion_anio = ((disposicion != null) ? disposicion.nro_disposicion_anio: null);
  // var descripcion = ((disposicion != null) ? disposicion.descripcion: null);

  // $('#columnaDisposicion')
  //     .append($('<div>')
  //         .addClass('row')
  //         .css('margin-bottom','15px')
  //         .addClass('Disposicion')
  //         .attr('id_disposicion',id_disposicion)
  //         .append($('<div>')
  //             .addClass('col-xs-3')
  //             .css('padding-right','0px')
  //             .append($('<input>')
  //                 .attr('id','nro_disposicion')
  //                 .attr('type','text')
  //                 .css('margin-top','6px')
  //                 .addClass('form-control')
  //                 .val(nro_disposicion)
  //                 .attr('maxlength','3')
  //                 .attr('placeholder','- - -')
  //             )
  //         )
  //         .append($('<div>')
  //             .addClass('col-xs-3')
  //             .css('padding-right','0px')
  //             .append($('<input>')
  //                 .attr('id','nro_disposicion_anio')
  //                 .attr('type','text')
  //                 .css('margin-top','6px')
  //                 .addClass('form-control')
  //                 .val(nro_disposicion_anio)
  //                 .attr('maxlength','2')
  //                 .attr('placeholder','- -')
  //             )
  //         )
  //         .append($('<div>')
  //             .addClass('col-xs-5')
  //             .css('padding-right','0px')
  //             .append($('<input>')
  //                 .attr('id','nro_disposicion_anio')
  //                 .attr('type','text')
  //                 .css('margin-top','6px')
  //                 .addClass('form-control')
  //                 .val('Descripción')
  //                 .attr('placeholder','Descripción')
  //             )
  //         )
  //     )

      if(editable){
        moldeDisposicion.find('.borrarDisposicion').val(disposicion.id_disposicion);

        // $('#columnaDisposicion .Disposicion:last')
        //       .append($('<div>')
        //       .addClass('col-xs-1')
        //       .append($('<button>')
        //           .addClass('borrarDisposicion')
        //           .addClass('btn')
        //           .addClass('borrarInput')
        //           .addClass('btn-danger')
        //           .css('margin-top','6px')
        //           .attr('type','button')
        //           .append($('<i>')
        //               .addClass('fa')
        //               .addClass('fa-fw')
        //               .addClass('fa-trash')
        //           )
        //       )
        //     )
      }
}

function agregarMovimientos(movimiento, editable){

  $('#columnaMovimientos')
      .append($('<div>')
          .addClass('row')
          .append($('<div>')
          .addClass('col-xs-10')
          .addClass('Movimiento')
          .css('padding-right','0px')
          .css('margin-top','6px')
          .append(selectMov.clone())
      )
    )

      if(editable){
        $('#columnaMovimientos .row:last')
              .append($('<div>')
              .addClass('col-xs-2')
              .append($('<button>')
                  .addClass('borrarMovimiento')
                  .addClass('btn')
                  .addClass('borrarInput')
                  .addClass('btn-danger')
                  .css('margin-top','6px')
                  .css('margin-bottom','3px')
                  .attr('type','button')
                  .append($('<i>')
                      .addClass('fa')
                      .addClass('fa-fw')
                      .addClass('fa-trash-alt')
                  )
              )
            )
      }
}

function agregarLogModif(log, editable){

  var salida = log.descripcion + " | " + log.fecha;

  $('#columnaMovimientos')
      .append($('<div>')
          .addClass('row')
          .append($('<div>')
          .addClass('col-xs-10')
          .addClass('Movimiento_modif')
          .attr('id',log.id_log_movimiento)
          .css('padding-right','0px')
          .css('margin-top','12px')
          .append(salida)
      )
    )

      if(editable){
        $('#columnaMovimientos .row:last')
              .append($('<div>')
              .addClass('col-xs-2')
              .append($('<button>')
                  .addClass('borrarMovimiento')
                  .addClass('btn')
                  .addClass('borrarInput')
                  .addClass('btn-danger')
                  .css('margin-top','6px')
                  .attr('type','button')
                  .append($('<i>')
                      .addClass('fa')
                      .addClass('fa-fw')
                      .addClass('fa-trash-alt')
                  )
              )
            )
      }
}
