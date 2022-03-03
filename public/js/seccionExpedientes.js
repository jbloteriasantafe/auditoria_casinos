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
$('.tab').click(function(e){
  e.preventDefault();
  $('.seccion').hide();
  $($(this).attr('data-tab')).show();
  $('.tab').removeClass('navModalActivo');
  $(this).addClass('navModalActivo');
});

/////////////////////////////////// NOTAS ////////////////////////////////////

function obtenerCasinosSeleccionados(){
  return $('.casinosExp:checked').map(function(){return $(this).attr('id');}).toArray();
}

//Detectar casino de/seleccionado.
$(document).on('change','.casinosExp', function() {
    $('#notasMov').empty();  //Eliminar todas las notas de fila (menos el molde)
    $('#cantidad_movimientos').val(0);            //Resetear la cantidad de movimientos disponibles
    $('#btn-notaMov').parent().show();           //Mostrar el botón de agregar notas
    const casinos_seleccionados = obtenerCasinosSeleccionados();
    if (casinos_seleccionados.length == 0) {// Si hay 0 casinos seleccionados: limpiar las secciones de notas y mostrar mensajes.
      //limpiarSeccionNotas
      $('#notas').empty(); //Eliminar las filas de notas
      $('.mensajeNotas').show();
      $('.formularioNotas').hide();
    } else if (casinos_seleccionados.length == 1) {//Si hay un SOLO UN CASINO seleccionado: habilitar las dos pestañas
      //habilitarSeccionNotasMovimientos
      $('.mensajeNotas').hide();
      $('.formularioNotas').show();
      movimientosSinExpediente(casinos_seleccionados[0]);
      $('.mensajeNotas').hide();
      $('.formularioNotas').show();
    } else {//Si hay más casinos seleccionados: SOLO habilitar las notas nuevas
      //habilitarNotasNuevas
      $('#secNotas .mensajeNotas').hide();
      $('#secNotas .formularioNotas').show();
      $('#secMov .mensajeNotas').show();
      $('#secMov .formularioNotas').hide();
    }
});

function movimientosSinExpediente(id_casino) {
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: "GET",
    url: 'expedientes/movimientosSinExpediente/'+id_casino,
    success: function (data) {
      $('#cantidad_movimientos').val(data.logs.length);
      $('#movimientosDisponibles').find('option').remove();
      $('#movimientosDisponibles').append( $('<option>').val(0).text("Seleccione un movimiento"));
      data.logs.forEach(function(l){
        $('#movimientosDisponibles').append(
          $('<option>').val(l.id_log_movimiento).text(`${l.nombre} - ${l.descripcion} - ${l.fecha}`).attr('data-casino',l.id_casino)
        );
      });
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
}

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  const minimizar = $(this).data("minimizar");
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar",!minimizar);
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
  const moldeDisposicion = $('#moldeDisposicion').clone().removeAttr('id').show();
  moldeDisposicion.find('#tiposMovimientosDisp').prop("disabled", false);
  moldeDisposicion.find('.dtpFechaDisposicion').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 3,
  });
  $('#columnaDisposicion').append(moldeDisposicion);
});

// Agregar resolucion
$('#btn-agregarResolucion').on("click",function(e){
  const nro_res  = $('#nro_resolucion').val();
  const anio_res = $('#nro_resolucion_anio').val();
  if(nro_res == "" || anio_res == "") return;
  $('#nro_resolucion').val("");
  $('#nro_resolucion_anio').val("");
  const fila = $('#moldeResolucion').clone().removeAttr('id');
  fila.find('.nro_res').text(nro_res);
  fila.find('.anio_res').text(anio_res);
  $('#tablaResolucion').append(fila);
});

$(document).on('click','.borrarFila',function(){
  $(this).parent().parent().remove();
});

$(document).on('click','.borrarNota', function(){
  $(this).closest('.nota').remove();
});

$(document).on('click','.borrarNotaMov',function(){
  $(this).closest('.notaMov').remove();
  $('#cantidad_movimientos').val(parseInt($('#cantidad_movimientos').val()) + 1);
  $('#secMov .agregarNota').show(); //Mostrar el botón para agregar notas
  $(`#movimientosDisponibles option[value="${$(this).attr('id')}"]`).show();//Mostrar el movimiento borrado nuevamente en el selector
});

//variable global para selectMovimientos
var asocMov = $('#columnaAsociar').append($('<div>'));

//Contador global que sirve para generar el id de cada DTP de notas nuevas
var nro_nota = 0;

$('#btn-notaNueva').click(function(e){
    nro_nota = nro_nota + 1;                                                    //Se incrementa en 1, para que cada DTP tenga un ID diferente

    e.preventDefault();
    const clonNota = $('#moldeNotaNueva').clone().removeAttr('id');

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

    $('#notas').append(clonNota);
});

$('#btn-notaMov').click(function(e){
  e.preventDefault();

  var cantidadMovimientos = $('#cantidad_movimientos').val();                   //Cantidad de movimientos disponibles para crear notas
  var id_movimiento = $('#movimientosDisponibles').val();                       //Se obtiene el id del movimiento

  if (cantidadMovimientos > 0 && id_movimiento != 0) {                          //Si se seleccionó algún movimiento...

    $('#movimientosDisponibles option[value="' + id_movimiento + '"]').hide();  //Ocultar la opción del movimiento que se va a agregar
    $('#movimientosDisponibles').val(0);                                        //Cambiar el selector a la opción por defecto

    var clonNota = $('#moldeNotaMov').clone().removeAttr('id');

    $.get('expedientes/obtenerMovimiento/' + id_movimiento, function(data) {    //Se trae toda la información del movimiento seleccionado
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
        var descripcion = fecha + " - " + data.tipo + " - " + data.casino.nombre;
        clonNota.find('.descripcionTipoMovimiento').val(descripcion).attr('id', id_movimiento);
        clonNota.find('.borrarNotaMov').attr('id', id_movimiento);
        $('#notasMov').append(clonNota);                                    //Agregar la nota con el movimiento existente para editarla

        cantidadMovimientos = cantidadMovimientos - 1;
        $('#cantidad_movimientos').val(cantidadMovimientos);                    //Disminuir en 1 el contador de cantidad de movimientos

        if (cantidadMovimientos == 0) {                                         //Si no quedan más movimientos ocultar el botón de agregar
          $('#btn-notaMov').parent().hide();
        }
    });
  }
});

//Mostrar modal para agregar nuevo Expediente
$('#btn-nuevo').click(function(e){
    e.preventDefault();

    $('#modalExpediente').find('.modal-footer').children().show();
    $('#modalExpediente').find('.modal-body').children().show();
    $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();
    $('#dispoCarg').hide();
    //Ocultar errores
    $('#error_nav_config').hide();
    $('#error_nav_notas').hide();
    $('#error_nav_mov').hide();

    habilitarDTP();

    nro_nota = 0; //Reiniciar el contador de notas

    $('#navMov').parent().show();
    $('#navConfig').click(); //Empezar por la sección de configuración
    $('.formularioNotas').hide(); //Ocultar los formularios de notas
    $('#notasCreadas').hide(); //Ocultar las notas creadas (es del modal modificar expediente)
    $('.casinosExp').prop('checked',false); //Deseleccionar todos los casinos
    $('.mensajeExito').show();
    $('.mensajeNotas').show();


    limpiarModal();
    $('#concepto').val(' ');
    $('#tema').val(' ');

    habilitarControles(true);
    $('#modalExpediente .modal-title').text('NUEVO EXPEDIENTE');
    $('#modalExpediente .modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('#btn-guardar').val("nuevo");
    $('#btn-cancelar').text('CANCELAR');
    $('#asociar').show();
    $('#modalExpediente').modal('show');
});

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){
  $('#mensajeExito').hide();
  $('#modalExpediente').find('.modal-footer').children().show();
  $('#modalExpediente').find('.modal-body').children().show();
  $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();

  limpiarModal();
  //Ocultar errores
  $('#error_nav_config').hide();
  $('#error_nav_notas').hide();
  $('#error_nav_mov').hide();

  $('#modalExpediente .modal-title').text('| VER EXPEDIENTE');
  $('#modalExpediente .modal-header').attr('style','background: #4FC3F7');
  $('#btn-cancelar').text('SALIR');

  $('#navConfig').click(); //Empezar por la sección de configuración

  const id_expediente = $(this).val();

  $.get("expedientes/obtenerExpediente/" + id_expediente, function(data){
    mostrarExpediente(data.expediente,data.casinos,data.resolucion,data.disposiciones,data.notas,data.notasConMovimientos,false);
    habilitarControles(false);

    //Deshabilitar sección de 'notas & movimientos'
    $('#navMov').parent().hide();
    $('#notasNuevas').hide();

    $('#modalExpediente').modal('show');
  });
});

//Mostrar modal con los datos del Casino cargados
$(document).on('click','.modificarExp',function(){//"modificarExp" en vez de "modificar" porque al mensaje de exito se le agrega .modificar para ponerlo amarillo... y tira errores
    $('#mensajeExito').hide();
    $('#tablaDispoCreadas tbody tr').not('#moldeDispoCargada').remove();
    $('#modalExpediente').find('.modal-footer').children().show();
    $('#modalExpediente').find('.modal-body').children().show();
    $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();

    $('.casinosExp').prop('checked',false).prop('disabled',false);
    limpiarModal();
    habilitarDTP();
    habilitarControles(true);
    $('#modalExpediente .modal-title').text('| MODIFICAR EXPEDIENTE');
    $('#modalExpediente .modal-header').attr('style','background: #FFB74D');
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

    const id_expediente = $(this).val();
    $('#modalExpediente #id_expediente').val(id_expediente);

    $.get("expedientes/obtenerExpediente/" + id_expediente, function(data){
      mostrarExpediente(data.expediente,data.casinos,data.resolucion,data.disposiciones,data.notas,data.notasConMovimientos,true);
      habilitarControles(true);
      $('#btn-guardar').val("modificar");
      $('#modalExpediente').modal('show');
    });
});


//Borrar Casino y remover de la tabla
$(document).on('click','.eliminar',function(){
  $('#btn-eliminarModal').val($(this).val());
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } })
  $.ajax({
    type: "DELETE",
    url: "expedientes/eliminarExpediente/" + $(this).val(),
    success: function (data) {
      $('#btn-buscar').click();
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
});

function obtenerNotasNuevas() {
  var notas_nuevas = [];
  var mov = null;
  $.each($('#notas .nota'), function (index, value) {
    if($(this).find('.tiposMovimientos').val() != 0){
      mov = $(this).find('.tiposMovimientos').val();
    }else{
      mov = null;
    }
      var nota = {
        fecha: $(this).find('.fecha_notaNueva').val(),
        identificacion: $(this).find('.identificacion').val(),
        detalle: $(this).find('.detalleNota').val(),
        id_tipo_movimiento: mov,
      }

      notas_nuevas.push(nota);
  });

  return notas_nuevas;
}

function obtenerNotasMov() {
  var notas_mov = [];

  $.each($('#notasMov .notaMov'), function (index, value) {
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

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

    e.preventDefault();

    var fecha_pase = $('#fecha_pase').val();
    var fecha_iniciacion = $('#fecha_inicio').val();

    var resolucion = $('#tablaResolucion tbody tr').map(function(){
      return {
        id_resolucion:$(this).attr("id-resolucion"),
        nro_resolucion:$(this).find('td:eq(0)').text(),
        nro_resolucion_anio: $(this).find('td:eq(1)').text(),
      }
    }).toArray();


    var disposiciones = [];
    $('#columnaDisposicion .disposicion').not('#moldeDisposicion').each(function(){
        var disposicion = {
          nro_disposicion: $(this).find('.nro_disposicion').val(),
          nro_disposicion_anio: $(this).find('.nro_disposicion_anio').val(),
          descripcion: $(this).find('#descripcion_disposicion').val(),
          id_tipo_movimiento: $(this).find('#tiposMovimientosDisp').val(),
        }
        disposiciones.push(disposicion);
    });
    var dispo_cargadas = [];
    var tabla = $('#tablaDispoCreadas tbody > tr').not('#moldeDispoCargada');

    $.each(tabla, function(index, value){

        var id_disposicion= $(this).attr('id');
        console.log('dispoCar1',id_disposicion);

        dispo_cargadas.push(id_disposicion);
        console.log('dispoCar',dispo_cargadas);
    });


    var notas = obtenerNotasNuevas();
    console.log('notas',notas);
    var notas_asociadas = obtenerNotasMov();
    var tablaNotas=[];
    var tabla= $('#tablaNotasCreadas tbody > tr').not('#moldeFilaNota');

    $.each(tabla, function(index, value){
      console.log(value.id);
      tablaNotas.push(value.id);
    });

    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = ((state == "modificar") ? 'expedientes/modificarExpediente':'expedientes/guardarExpediente');
    var formData = {
      id_expediente: $('#id_expediente').val(),
      nro_exp_org: $('#nro_exp_org').val(),
      nro_exp_interno: $('#nro_exp_interno').val(),
      nro_exp_control: $('#nro_exp_control').val(),
      casinos: $('.casinosExp:checked').map(function(){return $(this).attr('id');}).toArray(),
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
      tablaNotas: tablaNotas,
      dispo_cargadas: dispo_cargadas
    }
    console.log(formData);

    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        beforeSend: function(data){
          console.log('Empezó');
          $('#modalExpediente').find('.modal-footer').children().hide();
          $('#modalExpediente').find('.modal-body').children().hide();
          $('#modalExpediente').find('.modal-body').children('#iconoCarga').show();
        },
        success: function (data) {

            $('#btn-buscar').trigger('click');

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

            //Si hay algun campo vacio en nro_exp
            var nro_exp_org_vacio = typeof response.nro_exp_org != "undefined";
            var nro_exp_interno_vacio = typeof response.nro_exp_interno != "undefined";
            var nro_exp_control_vacio = typeof response.nro_exp_control != "undefined";

            //Ocultar errores
            $('#error_nav_config').hide();
            $('#error_nav_notas').hide();
            $('#error_nav_mov').hide();


            //////////////////////////  ALERTAS DE CONFIGURACIÓN /////////////////////////

            if(typeof response.casinos !== 'undefined'){
              mostrarErrorValidacion($('#contenedorCasinos'),"Debe seleccionar al menos un casino",true);
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
            }
            if (typeof response["resolucion.nro_resolucion_anio"] != "undefined") {
              mostrarErrorValidacion($('#nro_resolucion_anio'),response['resolucion.nro_resolucion_anio'][0],false);
              $('#error_nav_config').show();
            }

            var i=0;
            $('#columnaDisposicion .disposicion').not('#moldeDisposicion').each(function(){
              if(typeof response['disposiciones.'+ i +'.nro_disposicion'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('.nro_disposicion'),response['disposiciones.'+ i +'.nro_disposicion'][0],false);
                $('#error_nav_config').show();
              }
              if(typeof response['disposiciones.'+ i +'.nro_disposicion_anio'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('.nro_disposicion_anio'),response['disposiciones.'+ i +'.nro_disposicion_anio'][0],false);
                $('#error_nav_config').show();
              }
              if(typeof response['disposiciones.'+ i +'.descripcion'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('#descripcion_disposicion'),response['disposiciones.'+ i +'.descripcion'][0],false);
                $('#error_nav_config').show();
              }

              i++;
            })

            //////////////////////////  ALERTAS DE NOTAS /////////////////////////
            var i = 0;

            $('#notas .nota').each(function(){
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

            $('#notasMov .notaMov').each(function(){
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
        }
    });
});

//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }
  console.log($('#herramientasPaginacion').getPageSize());
  var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
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
    nota: $('#B_nota').val(),
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
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.expedientes.total,clickIndice);
        $('#cuerpoTabla').empty();

        for(let i = 0; i < data.expedientes.data.length; i++) {
          generarFilaTabla(data.expedientes.data[i]);
        }

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
  const fila = $('#moldeFilaTabla').clone().removeAttr('id');
  fila.find('.expediente').text(`${expediente.nro_exp_org}-${expediente.nro_exp_interno}-${expediente.nro_exp_control}`);
  fila.find('.fecha').text(convertirDate(expediente.fecha_iniciacion) ?? '-');
  fila.find('.casino').text(expediente.nombre);
  fila.find('button').val(expediente.id_expediente);
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
  $('#id_expediente').val(0);

  $('#columnaDisposicion .disposicion').not('#moldeDisposicion').remove();
  $('.filaNota').not('#moldeFilaNota').remove(); //Eliminar todas las notas creadas
  $('#notas').empty(); //Eliminar las filas de notas nuevas
  $('#notasMov').empty(); //Eliminar las filas de notas con movimientos existentes
  //limipar tabla de resoluciones
  $('#tablaResolucion tbody').empty();

  ocultarErrorValidacion($('#modalExpediente input,textarea,select,button'));
  ocultarErrorValidacion($('#contenedorCasinos'));

  $('#columna .Disposicion').each(function(){
    $(this).find('#nro_disposicion').removeClass('alerta');
    $(this).find('#nro_disposicion_anio').removeClass('alerta');
  });
  $('.alertaTabla').remove();
}

function mostrarExpediente(expediente,casinos,resolucion,disposiciones,notas,notasConMovimientos,editable){
  $('#nro_exp_org').val(expediente.nro_exp_org);
  $('#nro_exp_control').val(expediente.nro_exp_control);
  $('#nro_exp_interno').val(expediente.nro_exp_interno);

  for (let i = 0; i < casinos.length; i++) {
    $('#'+ casinos[i].id_casino).prop('checked',true).prop('disabled',true);
  }

  $('.casinosExp').change();

  if(expediente.fecha_pase != null){
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

  resolucion.forEach(res => {
    const fila = $('#moldeResolucionCargada').clone().removeAttr('id').attr("id-resolucion",res.id_resolucion);
    fila.find('.nro_res').text(res.nro_resolucion);
    fila.find('.anio_res').text(res.nro_resolucion_anio);
    $('#tablaResolucion').append(fila);
  });

  disposiciones.forEach(d => {
    const fila = $('#moldeDispoCargada').clone().attr('id', d.id_disposicion);
    fila.find('.nro_dCreada').text(d.nro_disposicion);
    fila.find('.anio_dCreada').text(d.nro_disposicion_anio);
    fila.find('.fecha_dCreada').text(d.fecha ?? " -- ")
    fila.find('.desc_dCreada').text(d.descripcion  ?? "Sin Descripción");
    fila.find('.mov_dCreada').text(d.descripcion_movimiento ?? " -- ");
    fila.find('button').val(d.id_disposicion);
    $('#tablaDispoCreadas tbody').append(fila);
  });

  if(!editable){
    $('#tablaDispoCreadas,#tablaResolucion').find('button').remove();
  }

  for (let i = 0; i < notas.length; i++) {
    agregarNota(notas[i],false);
  }

  for (let j = 0; j < notasConMovimientos.length; j++) {
    agregarNota(notasConMovimientos[j],true);
  }

  //Si hay notas mostrarlas
  $('#notasCreadas').toggle((notas.length > 0) || (notasConMovimientos.length > 0));
}

function agregarNota(nota,conMovimiento) {
  var fila = $('#moldeFilaNota').clone();

  fila.show();
  fila.removeAttr('moldeFilaNota');

  fila.attr('id',nota.id_nota);
  fila.find('.borrarNota').attr('id',nota.id_nota);

  fila.find('.identificacion').text(nota.identificacion);
  fila.find('.fecha').text(convertirDate(nota.fecha));
  fila.find('.movimiento').text(conMovimiento? nota.movimiento: '-');
  fila.find('.detalle').text(nota.detalle);

  $('#tablaNotasCreadas tbody').append(fila);
}