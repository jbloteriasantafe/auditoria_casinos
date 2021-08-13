$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Relevamientos');
  $('#opcRelevamientos').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcImportaciones').addClass('opcionesSeleccionado');

  $('#modalMaquinasPorRelevamiento #detalles').hide();

  $('#dtpFecha').datetimepicker({
    todayBtn:  1,
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'HH:ii',
    pickerPosition: "bottom-left",
    startView: 1,
    minView: 0,
    ignoreReadonly: true,
    minuteStep: 5,
  });

  $('#dtpBuscadorFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#fechaGeneracion,#fechaRelSinSistema').datetimepicker({
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

  $.get('obtenerFechaActual', function (data) {
    $('#modalMaquinasPorRelevamiento #dtpFechaDesde,#dtpFechaHasta').datetimepicker({
      todayBtn:  1,
      language:  'es',
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd MM yyyy',
      pickerPosition: "bottom-left",
      startView: 2,
      minView: 2,
      ignoreReadonly: true,
      minuteStep: 5,
      startDate: data.fechaDate,
    });
  });

  $('#btn-buscar').trigger('click',[1,10,'relevamiento.fecha','desc']);
});

$('#fecha').on('change', function (e) {
  $(this).trigger('focusin');
  habilitarBotonGuardar();
})

//Opacidad del modal al minimizar
$('.minimizar').click(function(){
  const minimizar = $(this).data("minimizar")==true;
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar",!minimizar);
});

$(".pop").hover(function(){
  $(this).popover('show');
});

$(".pop").mouseleave(function(){
  $(this).popover('hide');
});

$('.modal').on('hidden.bs.modal', function() {
  ocultarErrorValidacion($('.form-control'));
})

$(document).on('click','.pop',function(e){
  e.preventDefault();
});

//ABRIR MODAL DE NUEVO RELEVAMIENTO
$('#btn-nuevoRelevamiento').click(function(e){
  e.preventDefault();
  $('#frmRelevamiento').trigger('reset');
  $('#sector option').remove();
  $('#maquinas_pedido').hide();
  $('#modalRelevamiento').modal('show');

  $('#modalRelevamiento').find('.modal-footer').children().show();
  $('#modalRelevamiento').find('.modal-body').children().show();
  $('#modalRelevamiento').find('.modal-body').children('#iconoCarga').hide();

  $.get("obtenerFechaActual", function(data){
    //Mayuscula pŕimer letra
    var fecha = data.fecha.charAt(0).toUpperCase() + data.fecha.slice(1);
    $('#fechaActual').val(fecha);
    $('#fechaDate').val(data.fechaDate);
  });
});

$('#modalRelevamiento #sector').on('change',function(){
  maquinasAPedido();
  //Acá se pregunta si para el sector y la fecha actual ya se genero un relevamiento.
  existeRelevamiento();
});

//GENERAR RELEVAMIENTO SOBRE SECTOR CON RELEVAMIENTO EXISTENTE
$('#btn-generarIgual').click(function(){
  $('#btn-generar').trigger('click');
  $('#confirmacionGenerarRelevamiento').modal('hide');
  $('#modalRelevamiento').modal('show');
});

$('#btn-cancelarConfirmacion').click(function(){
  $('#modalRelevamiento #existeRelevamiento').val(1);
  $('#modalRelevamiento').modal('show');
});

//GENERAR RELEVAMIENTO
$('#btn-generar').click(function(e){
  const existeRelevamiento = $('#modalRelevamiento #existeRelevamiento').val();
  if(existeRelevamiento == "1" || existeRelevamiento == "2"){
    $('#modalRelevamiento').modal('hide');
    $('#modalRelevamiento #existeRelevamiento').val(0);
    $('#confirmacionGenerarRelevamiento').modal('show');
    return;
  }
  if(existeRelevamiento != "0"){
    console.log('UNREACHABLE');
    return;
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  const id_sector = $('#modalRelevamiento #sector').val();

  $.ajax({
    type: "POST",
    url: 'relevamientos/crearRelevamiento',
    data: {
      id_sector: id_sector == null? 0 : id_sector,
      cantidad_maquinas: $('#cantidad_maquinas').val(),
      cantidad_fiscalizadores: $('#cantidad_fiscalizadores').val(),
    },
    dataType: 'json',
    beforeSend: function(data){
      //Si están cargados los datos para generar oculta el formulario y muestra el icono de carga
      if ($('#modalRelevamiento #casino').val() != "") {
        $('#modalRelevamiento').find('.modal-footer').children().hide();
        $('#modalRelevamiento').find('.modal-body').children().hide();
        $('#modalRelevamiento').find('.modal-body').children('#iconoCarga').show();
      }
    },
    success: function (data) {
        $('#btn-buscar').click();
        $('#modalRelevamiento').modal('hide');
        let iframe = document.getElementById("download-container");
        if (iframe === null){//Crea una ventana de descarga del zip
          iframe = document.createElement('iframe');
          iframe.id = "download-container";
          iframe.style.visibility = 'hidden';
          document.body.appendChild(iframe);
        }
        iframe.src = data.url_zip;
    },
    error: function (data) {
      $('#modalRelevamiento').find('.modal-footer').children().show();
      $('#modalRelevamiento').find('.modal-body').children().show();
      $('#modalRelevamiento').find('.modal-body').children('#iconoCarga').hide();
      var response = JSON.parse(data.responseText);
      //mostrar error
      if(typeof response.id_sector !== 'undefined'){
        mostrarErrorValidacion($('#modalRelevamiento #sector'),response.id_sector[0],false);
        mostrarErrorValidacion($('#modalRelevamiento #casino'),response.id_sector[0],false);
      }
    } //error
  }); //$.ajax
});

$('#btn-volver').click(function(){
    $('#modalRelevamiento #existeRelevamiento').val(2);
    $('#modalRelevamiento').modal('show');
});

$('#modalCargaRelevamiento').on('hidden.bs.modal', function(){
  //limpiar modal
  $('#modalCargaRelevamiento #frmCargaRelevamiento').trigger('reset');
  $('#modalCargaRelevamiento #inputFisca').prop('readonly',false);
});

$('#modalMaquinasPorRelevamiento').on('hidden.bs.modal', function(){
  //resetearModal
  bloquearDatosMaquinasPorRelevamiento(false);
});

let truncadas = 0;
let guardado = true;
let salida = 0; //cantidad de veces que se apreta salir

$(document).on('click','.carga',function(e){
  e.preventDefault();

  truncadas = 0;
  salida = 0;//ocultar mensaje de salida
  guardado = true;
  $('#modalCargaRelevamiento .mensajeSalida').hide();
  $("#modalCargaRelevamiento").animate({ scrollTop: 0 }, "slow");

  const id_relevamiento = $(this).val();
  $('#id_relevamiento').val(id_relevamiento);

  //SI ESTÁ GUARDADO NO MUESTRA EL BOTÓN PARA GUARDAR
  $('#btn-guardar').hide();
  $('#btn-finalizar').hide();

  $.get('relevamientos/obtenerRelevamiento/' + id_relevamiento, function(data){
      $('#cargaFechaActual').val(data.relevamiento.fecha);
      $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
      $('#cargaCasino').val(data.casino);
      $('#cargaSector').val(data.sector);
      $('#fecha').val(data.relevamiento.fecha_ejecucion);
      $('#fecha_ejecucion').val(data.relevamiento.fecha_ejecucion);
      $('#tecnico').val(data.relevamiento.tecnico);
      $('#fiscaCarga').val(data.usuario_actual.usuario.nombre);// si el relevamiento no tiene usuario fizcalizador se le asigna el actual
      if (data.usuario_cargador != null) {
        $('#fiscaCarga').val(data.usuario_cargador.nombre);
      }

      $('#inputFisca').generarDataList('usuarios/buscarUsuariosPorNombreYCasino/'+ data.id_casino,'usuarios','id_usuario','nombre',2);
      $('#inputFisca').setearElementoSeleccionado(0,"");
      if (data.usuario_fiscalizador){
        $('#inputFisca').setearElementoSeleccionado(data.usuario_fiscalizador.id_usuario,data.usuario_fiscalizador.nombre);
      }

      $('#tablaCargaRelevamiento tbody tr').remove();
      const tablaCargaRelevamiento = $('#tablaCargaRelevamiento tbody');
      cargarTablaRelevamientos(data, tablaCargaRelevamiento, 'Carga');
      tablaCargaRelevamiento.find('input').trigger('input');
      habilitarBotonFinalizar();
      $('#modalCargaRelevamiento').modal('show');
  });
});

//CAMBIOS EN TABLAS RELEVAMIENTOS / MOSTRAR BOTÓN GUARDAR
$('#modalCargaRelevamiento').on('input', "#tablaCargaRelevamiento input:not(:radio):not('.denominacion')", function(){
  habilitarBotonGuardar();

  const fila = $(this).parent().parent();
  //Fijarse si se habilita o deshabilita el tipo no toma
  if($(this).val() != '') fila.children('.tipo_causa_no_toma').val('');

  habilitarBotonFinalizar();

  const producido = parseFloat(fila.find('input.producido').val());
  fila.find('.icono-estado i').hide();
  if (isNaN(producido)) {//No hay producido
    //Mostrar signo de pregunta
    fila.find('.icono-estado i.fa-question').show();
    return;
  }

  const aux = producidoCalculadoRelevado(fila);
  const suma = aux.producido_calculado;
  const inputValido = aux.inputValido;
  const suma_redondeada = Number(suma.toFixed(2));
  const producido_redondeado = Number(Number(producido).toFixed(2));
  //luego de operar , en ciertos casos quedaba con mas digitos despues de la coma, por lo que se lo fuerza a dos luego de operar
  const diferencia = Number((suma_redondeada-producido_redondeado).toFixed(2));
  
  if (diferencia == 0 && inputValido) {
    fila.find('.icono-estado i.fa-check').show();
  } else if(Math.abs(diferencia) > 1 && diferencia%1000000 == 0 && inputValido) { //El caso de que no haya diferencia ignorando la unidad del millon (en pesos)
    fila.find('.icono-estado i.fa-exclamation').show();
  } else {
    fila.find('.icono-estado i.fa-times').show();
  }

  console.log("La suma es: " + (Math.round(suma * 100) / 100) * fila.attr('data-denominacion'));
  console.log("Producido: " + producido);
  console.log("Diferencia: " + diferencia);
});

$('#modalCargaRelevamiento').on('input', "input", habilitarBotonGuardar);

$(document).on('change','.tipo_causa_no_toma',function(){
  const fila = $(this).parent().parent();
  fila.find('.icono-estado i').hide();
  fila.find('.icono-estado i.fa-ban').show();
  //Si se elige algun tipo de no toma se vacian las cargas de contadores
  fila.find('.contador').val('');
  habilitarBotonGuardar();
  habilitarBotonFinalizar();
});

//SALIR DEL RELEVAMIENTO
$('#btn-salir').click(function(){
  //Si está guardado deja cerrar el modal
  if (guardado || salida != 0) $('#modalCargaRelevamiento').modal('hide');
  //Si no está guardado
  if (salida == 0) {
    $('#modalCargaRelevamiento .mensajeSalida').show();
    $("#modalCargaRelevamiento").animate({ scrollTop: $('.mensajeSalida').offset().top }, "slow");
    salida = 1;
  }
});

//FINALIZAR EL RELEVAMIENTO
$('#btn-finalizar').click(function(e){
  e.preventDefault();
  //Se envía el relevamiento para guardar con estado 3 = 'Finalizado'
  enviarRelevamiento(3);
  $('#modalValidarRelevamiento').modal('hide');
});

//GUARDAR TEMPORALMENTE EL RELEVAMIENTO
$('#btn-guardar').click(function(e){
  e.preventDefault();
  //Se envía el relevamiento para guardar con estado 2 = 'Carga parcial'
  enviarRelevamiento(2);
  $('#modalCargaRelevamiento .mensajeSalida').hide();
  $('#modalValidarRelevamiento').modal('hide');
});

$('select').focusin(function(e){
  $(this).removeClass('alerta');
});

$(document).on('click','.validado',function(){
  window.open('relevamientos/generarPlanillaValidado/' + $(this).val(),'_blank');
})

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.imprimir',function(){
  window.open('relevamientos/generarPlanilla/' + $(this).val(),'_blank');
});

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
  $('#alertaArchivo').hide();
  window.open('relevamientos/generarPlanilla/' + $(this).val(),'_blank');
});

//VALIDAR EL RELEVAMIENTO
$(document).on('click','.validar',function(e){
  e.preventDefault();
  truncadas=0;
  const id_relevamiento = $(this).val();
  $('#modalValidarRelevamiento #id_relevamiento').val(id_relevamiento);

  $('#mensajeValidacion').hide();
  $('#btn-finalizarValidacion').show();

  $.get('relevamientos/obtenerRelevamiento/' + id_relevamiento, function(data){
    $('#modalValidarRelevamiento .modal-title').text('| VISAR RELEVAMIENTO');
    $('#validarFechaActual').val(convertirDate(data.relevamiento.fecha));
    $('#validarCasino').val(data.casino);
    $('#validarSector').val(data.sector);
    $('#validarFiscaToma').val(data.usuario_fiscalizador.nombre);
    $('#validarFiscaCarga').val(data.usuario_cargador.nombre );
    $('#validarTecnico').val(data.relevamiento.tecnico);
    $('#observacion_validacion').val('');
    $('#tablaValidarRelevamiento tbody tr').remove();
    cargarTablaRelevamientos(data, $('#tablaValidarRelevamiento tbody'), 'Validar');
    calculoDiferenciaValidar(data);
    $('#modalValidarRelevamiento').modal('show');
  });
})

//validar
$('#btn-finalizarValidacion').click(function(e){
  $('#modalValidarRelevamiento').modal('hide');

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  let maquinas_a_pedido=[];
  let data=[];
  $('#tablaValidarRelevamiento tbody tr').each(function(){
    const datos = {
      id_detalle_relevamiento: $(this).attr('id'),
      denominacion: $(this).attr('data-denominacion'),
      diferencia: $(this).find('.diferencia').val(),
      importado: $(this).find('.producido').val()
    }
    data.push(datos)

    if($(this).find('.a_pedido').length){
      if($(this).find('.a_pedido').val() != 0){
        const maquina = {
          id: $(this).find('.a_pedido').attr('data-maquina'),
          en_dias: $(this).find('.a_pedido').val(),
        }
        maquinas_a_pedido.push(maquina);
      }
    }
  })

  $.ajax({
    type: 'POST',
    url: 'relevamientos/validarRelevamiento',
    data: {
      id_relevamiento: $('#modalValidarRelevamiento #id_relevamiento').val(),
      observacion_validacion: $('#observacion_validacion').val(),
      maquinas_a_pedido: maquinas_a_pedido,
      truncadas:truncadas,
      data
    },
    dataType: 'json',
    success: function (data) {
      $('#btn-buscar').trigger('click');
      $('#modalValidarRelevamiento').modal('hide');
    },
    error: function (data) {
      console.log(data);
      $('#mensajeValidacion').show();
      $("#modalValidarRelevamiento").animate({ scrollTop: $('#mensajeValidacion').offset().top }, "slow");
    },
  });
});


$(document).on('click','.verDetalle',function(e){
  e.preventDefault();

  $.get('relevamientos/verRelevamientoVisado/' + $(this).val(), function(data){
    $('#modalValidarRelevamiento .modal-title').text('| DETALLES RELEVAMIENTO VISADO');

    $('#frmValidarRelevamiento').trigger('reset');
    $('#validarFechaActual').val(convertirDate(data.relevamiento.fecha_generacion));
    $('#validarCasino').val(data.casino);
    $('#validarSector').val(data.sector);
    $('#validarFiscaToma').val(data.fiscalizador);
    $('#validarFiscaCarga').val(data.cargador );
    $('#validarTecnico').val(data.relevamiento.tecnico);
    $('#observacion_validacion').val(data.relevamiento.observacion_validacion).prop('disabled',true);
    $('#observacion_fisca_validacion').val(data.relevamiento.observacion_carga);
    $('#tablaValidarRelevamiento tbody tr').remove();
    $('#btn-finalizarValidacion').hide();

    for (let i = 0; i < data.detalles.length; i++) {
      const d = data.detalles[i];
      //el removeAttr es importante, por algun motivo si lo modifico derecho mantiene la referencia al molde
      const fila = $('#moldeVerRelevamiento').clone().removeAttr('id').attr('id',d.id_detalle_relevamiento);
      fila.find('.nro_admin').text(d.nro_admin);
      for(let c = 1;c<=8;c++){
        const cont = d.detalle['cont'+c];
        //Si es 7,8 solo se muestra cuando no es nulo (ver el molde)
        if(cont != null) fila.find('.cont'+c).text(cont).show();
      }
      if(d.detalle.producido_calculado_relevado != null){
        fila.find('.producido_calculado_relevado').text(d.detalle.producido_calculado_relevado);
      }
      if(d.detalle.producido_importado != null){
        fila.find('.producido_importado').text(d.detalle.producido_importado);
      }
      if(d.detalle.diferencia != null){
        fila.find('.diferencia').text(d.detalle.diferencia);
      }
      if(d.tipo_no_toma != null){
        fila.find('.tipo_no_toma').text(d.tipo_no_toma);
      }
      fila.find('.denominacion').text(d.denominacion);
      if(d.mtm_pedido != null){
        fila.find('.mtm_pedido').text(d.mtm_pedido.fecha);
      }
      $('#tablaValidarRelevamiento tbody').append(fila);
    }
    $('#modalValidarRelevamiento').modal('show');
  })
});

$('#btn-relevamientoSinSistema').click(function(e) {
  e.preventDefault();
  $('#fechaGeneracion').data('datetimepicker').reset();
  $('#fechaRelSinSistema').data('datetimepicker').reset();
  $('#casinoSinSistema').val("");
  $('#sectorSinSistema option').remove();
  $('#modalRelSinSistema').modal('show');
});

//ABRIR MODAL DE CARGAR MAQUINAS POR RELEVAMIENTO
$('#btn-maquinasPorRelevamiento').click(function(e) {
  e.preventDefault();
  //Ocultar y mostrar botones necesarios
  $('#btn-generarMaquinasPorRelevamiento').show();
  $('#btn-generarDeTodasFormas').hide();
  $('#mensajeTemporal').hide();
  $('#btn-cancelarTemporal').hide();
  $('#frmMaquinasPorRelevamiento').trigger('reset');
  $('#modalMaquinasPorRelevamiento #casino').change();
  $('#modalMaquinasPorRelevamiento #tipo_cantidad').change();
  $('#modalMaquinasPorRelevamiento').modal('show');
  $('#modalMaquinasPorRelevamiento').find('.modal-body').children('#iconoCarga').hide();
  $('#modalMaquinasPorRelevamiento #detalles').hide();
});

//Generar el relevamiento de backup
$('#btn-backup').click(function(e){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  e.preventDefault();
  $.ajax({
    type: "POST",
    url: 'relevamientos/usarRelevamientoBackUp',
    data: {
      fecha: $('#fechaRelSinSistema_date').val(),
      fecha_generacion: $('#fechaGeneracion_date').val(),
      id_sector: $('#sectorSinSistema').val(),
    },
    dataType: 'json',
    success: function (data) {
      $('#btn-buscar').trigger('click');
      $('#modalRelSinSistema').modal('hide');
    },
    error: function (data) {
      const response = data.responseJSON;
      if(typeof response.id_sector !== 'undefined'){
        mostrarErrorValidacion($('#casinoSinSistema,#sectorSinSistema'), response.id_sector[0],false);
      }
      if(typeof response.fecha !== 'undefined') {
        mostrarErrorValidacion($('#fechaRelSinSistema input'), response.fecha[0],false);
      }
      if(typeof response.fecha_generacion !== 'undefined') {
        mostrarErrorValidacion($('#fechaGeneracion input'), response.fecha_generacion[0],false);
      }
    }
  });
});

$('#fechaRelSinSistema input').on('change',function(e) {
  $(this).removeClass('alerta');
});

//MODAL DE CANTIDAD DE MÁQUINAS POR RELEVAMIENTOS |  DEFAULT Y TEMPORALES
$('#modalMaquinasPorRelevamiento #sector').on('change',maquinasPorRelevamiento);

//Según el tipo de tipo se bloquea la fecha o no
$('#modalMaquinasPorRelevamiento #tipo_cantidad').change(function() {
  const habilitar = $("#modalMaquinasPorRelevamiento #tipo_cantidad").val() != 1;
  toggleDTPmaquinasPorRelevamiento(habilitar);
});

$('#btn-generarDeTodasFormas').click(function(){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const formData = {
    id_sector:         $('#modalMaquinasPorRelevamiento #sector').val(),
    id_tipo_cantidad_maquinas_por_relevamiento: 
                       $('#modalMaquinasPorRelevamiento #tipo_cantidad').val(),
    fecha_desde:       $('#modalMaquinasPorRelevamiento #fecha_desde').val(),
    fecha_hasta:       $('#modalMaquinasPorRelevamiento #fecha_hasta').val(),
    cantidad_maquinas: $('#modalMaquinasPorRelevamiento #cantidad_maquinas_por_relevamiento').val(),
  };

  $.ajax({
    type: "POST",
    url: 'relevamientos/crearCantidadMaquinasPorRelevamiento',
    data: formData,
    dataType: 'json',
    success: function (data) {
      //Habilitar botón originales y sacar los temporales
      $('#btn-generarMaquinasPorRelevamiento').show();
      $('#btn-generarDeTodasFormas').hide();
      $('#mensajeTemporal').hide();
      $('#btn-cancelarTemporal').hide();
      bloquearDatosMaquinasPorRelevamiento(false);
      //Modificar defecto y/o agregar temporal
      setCantidadMaquinas(data);
    },
    error: function (data) { console.log(data); }
  });
});

//Si se cancela la generación temporal se ocultan los boton y se muestran los originales
$('#btn-cancelarTemporal').click(function(){
  $('#btn-generarMaquinasPorRelevamiento').show();
  $('#btn-generarDeTodasFormas').hide();
  $('#mensajeTemporal').hide();
  $('#btn-cancelarTemporal').hide();
  bloquearDatosMaquinasPorRelevamiento(false);
});

$('#btn-generarMaquinasPorRelevamiento').click(function(){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const id_sector   = $('#modalMaquinasPorRelevamiento #sector').val();
  const fecha_desde = $('#modalMaquinasPorRelevamiento #fecha_desde').val();
  const fecha_hasta = $('#modalMaquinasPorRelevamiento #fecha_hasta').val();

  if($('#modalMaquinasPorRelevamiento #tipo_cantidad option:selected').text() == "Temporal"){
    //Preguntar si las fechas para cantidad TEMPORAL se pisan
    let existe = false;
    $.ajax({
      url: 'relevamientos/existeCantidadTemporalMaquinas/' + id_sector + "/" + fecha_desde + "/" + fecha_hasta,
      async: false,//REQUEST SINCRONICO!! @HACK: Habria que obtener un error en el request de abajo y manejarlo asi, mas simple...
      type: "GET",
      success: function(data){
        existe = data.existe;
      },
      error: function(error){ console.log(error); }
    });
    if(existe){
      //Mostrar mensaje y habilitar boton para generar de todas formas con las fechas elegidas
      $('#btn-generarMaquinasPorRelevamiento').hide();
      $('#mensajeTemporal').show();
      $('#btn-generarDeTodasFormas').show();
      $('#btn-cancelarTemporal').show();
      //Deshabilitar todos los inputs
      bloquearDatosMaquinasPorRelevamiento(true);
      return;
    }
  }

  $.ajax({
    type: "POST",
    url: 'relevamientos/crearCantidadMaquinasPorRelevamiento',
    data: {
      id_sector: id_sector,
      id_tipo_cantidad_maquinas_por_relevamiento: $('#modalMaquinasPorRelevamiento #tipo_cantidad').val(),
      fecha_desde: fecha_desde,
      fecha_hasta: fecha_hasta,
      cantidad_maquinas: $('#modalMaquinasPorRelevamiento #cantidad_maquinas_por_relevamiento').val(),
    },
    dataType: 'json',
    success: function (data) {
      //Modificar defecto y/o agregar temporal
      setCantidadMaquinas(data);
    },
    error: function (data) {
      console.log(data);
    }
  });
});

//Borrar una cantidad temporal de máquinas por relevamientos
$('#modalMaquinasPorRelevamiento').on('click','.borrarCantidadTemporal', function(e){
  e.preventDefault();
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
      type: "POST",
      url: 'relevamientos/eliminarCantidadMaquinasPorRelevamiento',
      data: {
        id_cantidad_maquinas_por_relevamiento: $(this).parent().parent().attr('id')
      },
      dataType: 'json',
      success: maquinasPorRelevamiento,
      error: function (error) { console.log(error); }
  });
});

$(document).on('focusin' , 'input' , function(e){
  $(this).removeClass('alerta');
})

function producidoCalculadoRelevado(fila){
  let suma = 0;
  let inputValido = false;
  for(let c = 1;c<=8;c++){
    const formulaCont  = fila.find('input.formulaCont'+c).val()
    if(formulaCont == '') continue;
    const operador     = fila.find('input.formulaOper'+c).val();
    const contador_str = fila.find('input.cont'+c).val().replace(/,/g,".");
    const contador     = contador_str != ''? parseFloat(contador_str) : 0;
    inputValido = inputValido || (contador_str != '');

    if(c == 1) suma = contador;//@BUG? No tenemos en cuenta el signo en el primer contador?
    else{
      const contador_signo = operador == '+'? contador : -contador;
      suma += contador_signo;
    }
  }
  if(fila.attr('data-medida') == 1){
     suma = suma * fila.attr('data-denominacion');
  }
  return {producido_calculado: suma,inputValido: inputValido};
}

function enviarRelevamiento(estado) {
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const detalles = [];
  $('#tablaCargaRelevamiento tbody tr').each(function(){
      let calculado = '';
      //Si se envía para finalizar se guarda el producido calculado
      if (estado == 3) {
        //Si no tiene una causa de no toma se calcula el producido
        if ($(this).find('.tipo_causa_no_toma').val() == '') {
          calculado = producidoCalculadoRelevado($(this)).producido_calculado; //calculado relevado siempre se trabaja en dinero, no en creditos
          calculado = Math.round(calculado*100)/100;
        }
      }

      const detalle = {
        id_unidad_medida: $(this).attr('data-medida'),
        denominacion: $(this).attr('data-denominacion'),
        id_detalle_relevamiento: $(this).attr('id'),
        id_tipo_causa_no_toma: $(this).children().children('.tipo_causa_no_toma').val(),
        producido_calculado_relevado: calculado,
      };
      for(let c=1;c<=8;c++) detalle['cont'+c] = $(this).find('.cont'+c).val().replace(/,/g,".")

      detalles.push(detalle);
  });

  $.ajax({
    type: 'POST',
    url: 'relevamientos/cargarRelevamiento',
    dataType: 'JSON',
    data: {
      id_relevamiento: $('#modalCargaRelevamiento #id_relevamiento').val(),
      id_usuario_fiscalizador: $('#inputFisca').obtenerElementoSeleccionado(),
      observacion_carga: $('#observacion_carga').val(),
      tecnico: $('#tecnico').val(),
      hora_ejecucion: $('#fecha_ejecucion').val(),
      estado: estado,
      detalles: detalles.length == 0? 0 : detalles,
      truncadas:truncadas
    },
    success: function (data) {
      $('#btn-buscar').trigger('click');
      guardado = true;
      $('#btn-guardar').hide();
      if (estado == 3) {
        $('#modalCargaRelevamiento').modal('hide');
      }
    },
    error: function (data) {
      const response = data.responseJSON;

      if(typeof response.tecnico                 !== 'undefined'
      || typeof response.fecha_ejecucion         !== 'undefined'
      || typeof response.id_usuario_fiscalizador !== 'undefined'){
        $("#modalCargaRelevamiento").animate({ scrollTop: 0 }, "slow");
      }

      if(typeof response.tecnico !== 'undefined'){
        mostrarErrorValidacion($('#tecnico'),response.tecnico[0]);
      }
      if(typeof response.fecha_ejecucion !== 'undefined'){
        mostrarErrorValidacion($('#fecha'),response.fecha_ejecucion[0]);

      }
      if(typeof response.id_usuario_fiscalizador !== 'undefined'){
        mostrarErrorValidacion($('#inputFisca'),response.id_usuario_fiscalizador[0]);
      }

      let filaError = null;
      $('#tablaCargaRelevamiento tbody tr').each(function(filaidx,fila){
        for(let c = 1;c<=8;c++){
          if(typeof response['detalles.'+ filaidx +'.cont' + c] !== 'undefined'){
            filaError = fila;
            mostrarErrorValidacion($(this).find('.cont'+c),response['detalles.'+ filaidx +'.cont'+c][0],false);
          }
        }
      });

      if(filaError != null){
        $("#modalCargaRelevamiento").animate({ scrollTop: filaError.offset().top }, "slow");
      }
    }
  });
}

function habilitarBotonGuardar(){
  guardado = false;
  $('#btn-guardar').show();
}

function habilitarBotonFinalizar(){
  var cantidadMaquinas = 0;
  var maquinasRelevadas = 0;

  $('#tablaCargaRelevamiento tbody tr').each(function(i){
    cantidadMaquinas++;
    var inputLleno = false;
    var noToma = false;

    //Mirar si la fila tiene algun campo lleno
    $(this).children('td').find('.contador').each(function (j){
        if($(this).val().length > 0) inputLleno = true;
    });

    //Mirar si seleccionó un tipo de no toma
    if($(this).children('td').find('select').val() !== '') noToma = true;

    //Si se lleno algun campo o se tifico la no toma, entonces la maquina está relevada
    if (inputLleno || noToma) {
        maquinasRelevadas++;
    }
  });

  if(cantidadMaquinas == maquinasRelevadas) $('#btn-finalizar').show();
  else $('#btn-finalizar').hide();
}

$(document).on('click','.pop',function(e){
  e.preventDefault();
  $('.pop').not(this).popover('hide');
  $(this).popover('show');
});

$(document).on('click','.cancelarAjuste',function(e){
  $('.pop').popover('hide');
});

function enviarCambioDenominacion(id_maquina, medida, denominacion) {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'relevamientos/modificarDenominacionYUnidad',
    data: {
      id_detalle_relevamiento: id_maquina,
      id_unidad_medida: medida,
      denominacion: denominacion,
    },
    dataType: 'json',
    success: function(data){
      $('.pop').popover('hide');
      const id_relevamiento = $('#modalValidarRelevamiento #id_relevamiento').val();
      //Volver a cargar la tabla y ver la diferencia
      $.get('relevamientos/obtenerRelevamiento/' + id_relevamiento, function(dataRelevamiento){
          $('#tablaValidarRelevamiento tbody tr').remove();
          cargarTablaRelevamientos(dataRelevamiento, $('#tablaValidarRelevamiento tbody'), 'Validar');
          calculoDiferenciaValidar(dataRelevamiento);
      });
    },
    error: function(error){
        console.log('Error de cambio denominacion: ', error);
    },
  });
}

$(document).on('click','.ajustar',function(e){
  var medida = $(this).siblings('input:checked').val();
  var fila = $(this).closest('tr');
  var boton = $(this).closest('.popover').siblings('.pop');

  if (medida == 'credito'){
      //se cambia la denominacion por la que ya esta definida en el maestro de maquina
    fila.attr('data-medida', 1); //Cambia el tipo de medida de la fila
    boton.find('i').addClass('fa-life-ring').removeClass('fa-usd-circle'); //Cambia el icono del botón
    enviarCambioDenominacion(fila.attr('id'), 1, fila.attr('data-denominacion'));
  }
  else {
    denMaestro=fila.attr('data-denominacion');
    if (denMaestro==""){
      denMaestro=0.01
    }

    fila.attr('data-medida', 2);
    fila.attr('data-denominacion',denMaestro) //Cambia la denominacion
    boton.find('i').removeClass('fa-life-ring').addClass('fa-usd-circle');

    enviarCambioDenominacion(fila.attr('id'), 2, denMaestro);
  }
});

$(document).on('click' , '.estadisticas_no_toma' , function (){
  window.open('http://' + window.location.host + "/relevamientos/estadisticas_no_toma/" + $(this).val(), '_blank');
})

$(document).on('change','input:radio[name=medida]',function(){
  console.log('Cambió: ', $(this).val());

  if ($(this).val() == 'credito') {
      $(this).siblings('.denominacion').prop('disabled',false);
  }else {
      $(this).siblings('.denominacion').val('').removeClass('alerta').prop('disabled',true);
  }
});

function cargarTablaRelevamientos(data, tabla, estado){
  //se cargan las observaciones del fiscalizador si es que hay
  $('#observacion_fisca_validacion').val(data.relevamiento.observacion_carga);
  const validando = estado == 'Validar';
  const cargando  = estado == 'Carga';

  for (let i = 0; i < data.detalles.length; i++) {//@TODO: simplificar este FOR
    const d = data.detalles[i];
    const f = $('#moldeTablaCargaRelevamientos').clone().removeAttr('id').attr('id',d.detalle.id_detalle_relevamiento)
              .attr('data-medida', d.unidad_medida.id_unidad_medida)
              .attr('data-denominacion', d.denominacion);

    f.find('.maquina').text(d.maquina);

    for(let c = 1;c<=8;c++){
      const cont = 'cont'+c;
      const readonly = validando || (cargando && d.formula != null  && d.formula[cont] == null);
      f.find('.'+cont).val(d.detalle[cont]).prop('readonly',readonly);
    }
    
    for (let j = 0; j < data.tipos_causa_no_toma.length; j++) {
      const tipo = data.tipos_causa_no_toma[j].descripcion;
      const id   = data.tipos_causa_no_toma[j].id_tipo_causa_no_toma;
      const deprecado = data.tipos_causa_no_toma[j].deprecado == 1;
      f.find('.tipo_causa_no_toma').append($('<option>').text(tipo).val(id).attr('disabled',deprecado));
    }
    f.find('.tipo_causa_no_toma').val(d.tipo_causa_no_toma);

    const contentUnidadMedida = $('#moldeUnidadMedida').clone().removeAttr('id');
    if(d.unidad_medida.id_unidad_medida == 1){//Creditos
      contentUnidadMedida.find('.um_credito').attr('checked',true);
      f.find('.medida').find('i.fa-dollar-sign').remove();
    }
    else{//Pesos
      contentUnidadMedida.find('.um_pesos').attr('checked',true);
      f.find('.medida').find('i.fa-life-ring').remove();
    }
    contentUnidadMedida.find('.ajustar').val(d.unidad_medida.id_unidad_medida);
    f.find('.medida').attr('data-content',contentUnidadMedida[0].outerHTML).parent().toggle(validando);
    
    const dif = d.detalle.diferencia;    
    f.find('.acciones_validacion').toggle(validando && 
      (d.tipo_causa_no_toma != null || dif == null || (dif != 0 && ( dif %1000000 != 0))));

    f.find('.fcont,.foper').val(null);
    if (d.formula != null){//@TODO: Pasar a .data()
      for(let c = 1;c<=8;c++){
        f.find('.formulaCont'+c).val(d.formula['cont'+c]);
        f.find('.formulaOper'+c).val(d.formula['operador'+c]);
      }
    }

    if (d.producido != null) {
      f.find('.producido').css('border','2px solid #6DC7BE').css('color','#6DC7BE').val(d.producido);
    }
    if (d.detalle.producido_calculado_relevado != null) {
      f.find('.producidoCalculado').css('border','2px solid #6DC7BE').css('color','#6DC7BE').val(d.detalle.producido_calculado_relevado);
    }

    f.find('.producido').prop('readonly',validando).toggle(validando);
    f.find('.producidoCalculado').prop('readonly',validando).toggle(validando).parent().toggle(validando);
    f.find('.diferencia').prop('readonly',validando).toggle(validando);

    if(validando){
      let notoma = '';
      if (d.tipo_causa_no_toma != null) {
        notoma = data.tipos_causa_no_toma[parseInt(d.tipo_causa_no_toma) - 1].descripcion;
      }else {
        //AGREGAR REDIRECCION A ESTADISTICAS
      }
      const inotoma = $('<input>').addClass('tipo_causa_no_toma form-control').val(notoma).prop('readonly',true);
      if (inotoma.val() != '') {
        inotoma.css('border','2px solid #1E90FF').css('color','#1E90FF');
      }
      f.find('.tipo_causa_no_toma').replaceWith(inotoma);
    }
    tabla.append(f);
  }

  $('.pop').popover({
    html:true
  });
}

function calculoDiferenciaValidar(data){
    //debido a que el metodo se llama en ultima instancia para validar, ahi empieza el contador desde cero
    truncadas=0;
    for (let i = 0; i < data.detalles.length; i++) {
      const d = data.detalles[i];
      const fila = $('#tablaValidarRelevamiento tbody').find('#'+d.detalle.id_detalle_relevamiento);
      const diferencia = fila.find('input.diferencia');

      if(d.detalle.producido_calculado_relevado == null){
        diferencia.val(math.abs(Number(d.producido)))
        .css('border',' 2px solid rgb(239, 83, 80)').css('color','rgb(239, 83, 80)');
        fila.find('.icono-estado .icono').hide();
        fila.find('.icono-estado .cruz').show();
      }
      //si no se importaron contadores muestra = ?
      if(d.producido == null) {
        diferencia.val(d.detalle.producido_calculado_relevado)
        .css('border',' 2px solid rgb(239, 83, 80)').css('color','rgb(239, 83, 80)');
        fila.find('.icono-estado .icono').hide();
        fila.find('.icono-estado .pregunta').show();
      }
      //Si hay causa no toma = x
      else if(d.tipo_causa_no_toma != null) {
        fila.find('.icono-estado .icono').hide();
        fila.find('.icono-estado .no_toma').show();
      }
      //Si no, calcular la diferencia entre lo calculado y lo importado
      else {
        //SI HAY DIFERENCIA
        //se cambio para considerar los contadores negativos
        const resta = d.detalle.producido_calculado_relevado-d.producido;
        if (Number(resta.toFixed(2)) != 0) {
          const diferenciaProducido =  math.abs(Number(resta.toFixed(2))) >= 1000000;
          let moduloDiferencia = Number(resta.toFixed(2));
          moduloDiferencia = math.abs(Number(moduloDiferencia.toFixed(2))) % 1000000;

          console.log(math.abs(d.detalle.producido_calculado_relevado),"-",math.abs(d.producido));
          console.log('MODULO DIFERENCIA', moduloDiferencia);
          console.log('DIFERENCIA', diferenciaProducido);

          if(diferenciaProducido && math.abs(moduloDiferencia) == 0){
            fila.find('.icono-estado .icono').hide();
            fila.find('.icono-estado .admiracion').show();
            truncadas++;
            diferencia.val(math.abs(resta.toFixed(2))).css('border','2px solid #FFA726').css('color','#FFA726');
          }
          else{
            fila.find('.icono-estado .icono').hide();
            fila.find('.icono-estado .cruz').show();
            diferencia.val(math.abs(resta.toFixed(2))).css('border','2px solid #EF5350').css('color','#EF5350');
          }
        }
        else {
          fila.find('.icono-estado .icono').hide();
          fila.find('.icono-estado .check').show();
          diferencia.val(0).css('border','2px solid #66BB6A').css('color','#66BB6A');
        }
      }
    }
}

function maquinasAPedido(){
  const id_sector = $('#modalRelevamiento #sector').val();
  const fecha = $('#fechaDate').val();

  $.get("relevamientos/obtenerCantidadMaquinasRelevamientoHoy/" + id_sector, function(cantidad){
    $('#modalRelevamiento #cantidad_maquinas').val(cantidad);
  });

  $.get("mtm_a_pedido/obtenerMtmAPedido/" + fecha + "/" + id_sector, function(data){
    const c = data.cantidad;
    if (c == 0){
      $('#maquinas_pedido').hide();
      return;
    }
    $('#maquinas_pedido').find('span').text(`Este sector tiene ${c} máquina${c==1? '' : 's'} a pedido.`);
    $('#maquinas_pedido').show();
  });
}

function existeRelevamiento(){
  //Se guarda un valor que indica que para el SECTOR y la FECHA ACTUAL
  $.get('relevamientos/existeRelevamiento/' + $('#modalRelevamiento #sector').val(), function(data){
    //0: No existe 1: Está generado (se puede volver a generar) 2: Empezo a cargarse (no se puede volver a generar)
    $('#modalRelevamiento #existeRelevamiento').val(data);
  });
}

/* Funciones de MÁQUINAS POR RELEVAMIENTO */
function maquinasPorRelevamiento() {
  const id_sector = $('#modalMaquinasPorRelevamiento #sector').val();
  //Si se elige correctamente un sector se muestran los detalles
  if(id_sector == null){
    $('#modalMaquinasPorRelevamiento #detalles').hide();
    return;
  }
  $.get('relevamientos/obtenerCantidadMaquinasPorRelevamiento/' + id_sector, function(data){
    setCantidadMaquinas(data);
    //Mostrar detalles
    $('#modalMaquinasPorRelevamiento #detalles').show();
  });
}

function setCantidadMaquinas(data) {
  $('#maquinas_temporales tbody tr').remove();
  $('#maquinas_temporales').hide();
  $('#maquinas_defecto').text("-");
  if(data.length == 0) return;

  $.each(data, function(i, valor){
    //MÁQUINAS POR DEFECTO
    if(valor.fecha_desde == null && valor.fecha_hasta == null) {
      $('#maquinas_defecto').text(valor.cantidad);
      return;
    }
    //MÁQUINAS TEMPORALES
    $('#maquinas_temporales tbody').append(filaCantidadMaquinasTemporales(valor));
  });

  //Si hay máquinas temporales MOSTRAR TABLA
  $('#maquinas_temporales').toggle($('#maquinas_temporales tbody tr').length > 0);
}

function filaCantidadMaquinasTemporales(valor) {
  const nombreMeses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
  const fd = valor.fecha_desde.split("-");
  const fh = valor.fecha_hasta.split("-");
  const fila = $('#moldeMaquinasTemporales').clone().removeAttr('id').attr('id',valor.id_cantidad_maquinas_por_relevamiento);
  fila.find('.fecha_desde').text(fd[2] + " " + nombreMeses[fd[1] - 1] + " " + fd[0]);
  fila.find('.fecha_hasta').text(fh[2] + " " + nombreMeses[fh[1] - 1] + " " + fh[0]);
  fila.find('.cantidad').text(valor.cantidad);
  return fila;
}

function toggleDTPmaquinasPorRelevamiento(habilitado) {
  $('#dtpFechaDesde').data('datetimepicker').reset();
  $('#dtpFechaHasta').data('datetimepicker').reset();
  $('#dtpFechaDesde input,#dtpFechaHasta input').prop('readonly',!habilitado).toggle(habilitado);
  $('#dtpFechaDesde span,#dtpFechaHasta span').toggle(habilitado);
  $('#dtpFechaDesde,#dtpFechaHasta').siblings('h5').toggle(habilitado);
}

function bloquearDatosMaquinasPorRelevamiento(bloquear) {
  $('#cantidad_maquinas_por_relevamiento').prop('readonly',bloquear);
  $('#cantidad_maquinas_por_relevamiento').parent().find('button').attr('disabled',bloquear);
  $('#modalMaquinasPorRelevamiento #casino').attr('disabled',bloquear);
  $('#modalMaquinasPorRelevamiento #sector').attr('disabled',bloquear);
  $('#modalMaquinasPorRelevamiento #tipo_cantidad').attr('disabled',bloquear);
  $('#dtpFechaDesde input').prop('readonly',bloquear).parent().find('span').toggle(!bloquear);
  $('#dtpFechaHasta input').prop('readonly',bloquear).parent().find('span').toggle(!bloquear);
}

/*****************PAGINACION******************/
function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaRelevamientos .activa').attr('value');
  const orden = $('#tablaRelevamientos .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

$(document).on('click','#tablaRelevamientos thead tr th[value]',function(e){
  $('#tablaRelevamientos th').removeClass('activa');
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
  $('#tablaRelevamientos th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') }});

  size = 10;
  //Fix error cuando librería saca los selectores
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaRelevamientos .activa').attr('value'),orden: $('#tablaRelevamientos .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaRelevamientos th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  const formData = {
    fecha: $('#buscadorFecha').val(),
    casino: $('#buscadorCasino').val(),
    sector: $('#buscadorSector').val(),
    estadoRelevamiento: $('#buscadorEstado').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  $.ajax({
    type: "POST",
    url: 'relevamientos/buscarRelevamientos',
    data: formData,
    dataType: 'json',
    success: function (resultados){
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      $('#tablaRelevamientos tbody tr').remove();

      let puede_ver = false;
      $.ajax({//@HACK: devolderlo en resultados... o crear un molde ya filtrado por tipo de usuario en la vista
        url: 'relevamientos/chequearRolFiscalizador',
        async: false,
        type: "GET",
        success: function(data){ puede_ver = data != 1; },//solo si NO es fiscalizador puede ver... raro
        error: function(error){ console.log(error); }
      });

      let puede_cargar = false;
      let puede_validar = false;
      $.ajax({//@HACK: devolderlo en resultados... o crear un molde ya filtrado por tipo de usuario en la vista
        type: 'GET',
        async: false,
        url: 'usuarios/usuarioTienePermisos',
        data: { permisos : ["relevamiento_cargar","relevamiento_validar"] },
        dataType: 'json',
        success: function(data) {
          puede_cargar = !!data.relevamiento_cargar;
          puede_validar = !!data.relevamiento_validar;
        },
        error: function(error) { console.log(error); },
      });

      for (let i = 0; i < resultados.data.length; i++) {
        $('#tablaRelevamientos tbody').append(crearFilaTabla(resultados.data[i],puede_ver,puede_cargar,puede_validar));
      }

      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});
//fila lista principal de relevamientos
function crearFilaTabla(relevamiento,puede_ver,puede_cargar,puede_validar){
  const subrelevamiento =  (relevamiento.subrelevamiento != null)? relevamiento.subrelevamiento : '';
  const f = $('#moldeTablaRelevamientos').clone().removeAttr('id');
  f.find('.fecha').text(convertirDate(relevamiento.fecha));
  f.find('.casino').text(relevamiento.casino);
  f.find('.sector').text(relevamiento.sector);
  f.find('.subrelevamiento').text(subrelevamiento);
  const icono = {'Generado':'faGenerado','Cargando':'faCargando','Finalizado':'faFinalizado','Visado':'faVisado','Rel. Visado':'faValidado'};
  const e = relevamiento.estado;
  f.find('.estado span').text(e);
  f.find('.estado i').not('.'+icono[e]).remove();
  
  f.find('button').val(relevamiento.id_relevamiento);
  f.find('.planilla')  .toggle(['Generado'].includes(e));
  f.find('.carga')     .toggle(puede_cargar  && ['Generado','Cargando'].includes(e));
  f.find('.validar')   .toggle(puede_validar && ['Finalizado'].includes(e));
  //No permitir ver cuando esta Generado (tira error de backend)
  f.find('.verDetalle').toggle(puede_ver     && ['Cargando','Finalizado','Visado','Rel. Visado'].includes(e));
  f.find('.imprimir')  .toggle(['Cargando','Finalizado','Visado','Rel. Visado'].includes(e));
  f.find('.validado')  .toggle(['Rel. Visado'].includes(e));
  
  return f;
}

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
function cargarSectores(id_casino,select,postAppend = function(x){}){
  select.empty();
  if(id_casino == ""){
    postAppend(false);
    return;
  }
  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
    for (let i = 0; i < data.sectores.length; i++) {
      const op = $('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion);
      select.append(op);
    }
    postAppend(true);
  });
}

$('#buscadorCasino').on('change',function(){
  cargarSectores($(this).val(),$('#buscadorSector'),function(cargo){
    if(!cargo) $('#buscadorSector').empty();
    $('#buscadorSector').prepend($('<option>').val("").text('-Todos los sectores-'));
    $('#buscadorSector').val("");
  });
});

$('#casinoSinSistema').on('change', function(){
  cargarSectores($('#casinoSinSistema').val(),$('#sectorSinSistema'))
  ocultarErrorValidacion($('#sectorSinSistema'));
});

$('#modalRelevamiento #casino').on('change',function(){
  cargarSectores($('#modalRelevamiento #casino').val(),$('#modalRelevamiento #sector'),function(cargo){
    if(!cargo) return;
    maquinasAPedido();
    existeRelevamiento();
  });
  $('#modalRelevamiento #sector').removeClass('alerta');
});

//Obtener las máquinas para cada relevamiento según el sector
$('#modalMaquinasPorRelevamiento #casino').on('change',function(){
  cargarSectores($('#modalMaquinasPorRelevamiento #casino').val(),$('#modalMaquinasPorRelevamiento #sector'),function(cargo){
    if(!cargo) return;
    maquinasPorRelevamiento();
  });
});