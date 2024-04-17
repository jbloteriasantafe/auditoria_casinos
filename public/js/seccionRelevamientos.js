var nombreMeses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
var truncadas=0;

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Relevamientos');

  const dtp_ops = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    pickerPosition: "bottom-left",
    ignoreReadonly: true,
  };
  
  $('#dtpFecha').datetimepicker({
    ...dtp_ops,
    format: 'HH:ii',
    startView: 1,
    minView: 0,
    minuteStep: 5,
  });

  $('#dtpBuscadorFecha').datetimepicker({
    ...dtp_ops,
    format: 'dd MM yyyy',
    startView: 2,
    minView: 2,
  });

  $('#btn-buscar').trigger('click',[1,10,'relevamiento.fecha','desc']);
});

$('#fecha').on('change', function (e) {
  $(this).trigger('focusin');
  habilitarBotonGuardar();
})

$('[data-toggle][data-minimizar]').click(function(){
  const minimizar = !!$(this).data('minimizar');
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data('minimizar',!minimizar);
});

$(".pop").hover(function(){
  $(this).popover('show');
});

$(".pop").mouseleave(function(){
  $(this).popover('hide');
});

$('.modal').on('hidden.bs.modal', function() {
  ocultarErrorValidacion($('.form-control'));
  $(document).find('.sector').empty();
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
  $('#modalRelevamiento').find('#iconoCarga').hide();

  $.get("obtenerFechaActual", function(data){
    //Mayuscula pŕimer letra
    var fecha = data.fecha.charAt(0).toUpperCase() + data.fecha.slice(1);
    $('#fechaActual').val(fecha);
    $('#fechaDate').val(data.fechaDate);
  });
});

function agregarSectores(id_casino,obj,after = function(sectores){}){
  $.get("relevamientos/obtenerSectoresPorCasino/" + id_casino, function(data){
    data.sectores.forEach(function(s){
      obj.append($('<option>').val(s.id_sector).text(s.descripcion));
    });
    after(data.sectores);
  });
}

$('#casinoSinSistema').on('change', function(){
  const id_casino = $('#casinoSinSistema option:selected').attr('id');//WTF? usar value
  ocultarErrorValidacion($('#sectorSinSistema').empty());
  agregarSectores(id_casino,$('#sectorSinSistema'));
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#modalRelevamiento #casino').on('change',function(){
  const id_casino = $('#modalRelevamiento #casino option:selected').attr('id');//WTF? usar value
  $('#modalRelevamiento #sector').removeClass('alerta').empty();
  agregarSectores(id_casino,$('#modalRelevamiento #sector'),function(){
    maquinasAPedido();
    existeRelevamiento();
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
  if(existeRelevamiento == '1' || existeRelevamiento == '2'){
    $('#modalRelevamiento').modal('hide');
    $('#modalRelevamiento #existeRelevamiento').val(0);
    $('#confirmacionGenerarRelevamiento').modal('show');
    return;
  }
  if(existeRelevamiento != '0') throw 'Unexpected value '+existeRelevamiento;
  
  const formData = {
    id_sector: $('#modalRelevamiento #sector').val() ?? 0,
    cantidad_maquinas: $('#cantidad_maquinas').val(),
    cantidad_fiscalizadores: $('#cantidad_fiscalizadores').val(),
  };
  //Solo los superusers tienen el input para seedear
  if($('#seed').length > 0) formData.seed = $('#seed').val();

  if ($('#modalRelevamiento #casino').val() == "") return;
    
  $('#modalRelevamiento').find('.modal-footer').children().hide();
  $('#modalRelevamiento').find('.modal-body').children().hide();
  $('#modalRelevamiento').find('#iconoCarga').show();
          
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'relevamientos/crearRelevamiento',
    data: formData,
    dataType: 'json',
    success: function (data) {      
      $('#btn-buscar').click();
      $('#modalRelevamiento').modal('hide');

      let iframe = $('#download-container');
      if (iframe.length == 0){
          iframe = $('<iframe>').attr('id','download-container').css('visibility','hidden');
          $('body').append(iframe);
      }
      iframe.attr('src',data.url_zip);
    },
    error: function (data) {
      const response = data.responseJSON;
      if(typeof response.id_sector !== 'undefined'){
          mostrarErrorValidacion($('#modalRelevamiento #sector'),response.id_sector[0],false);
          mostrarErrorValidacion($('#modalRelevamiento #casino'),response.id_sector[0],false);
      }
    },
    complete: function(jqXHR,textStatus){
      $('#modalRelevamiento').find('.modal-footer').children().show();
      $('#modalRelevamiento').find('.modal-body').children().show();
      $('#modalRelevamiento').find('#iconoCarga').hide();
    }
  });
});

$('#btn-volver').click(function(){
  $('#modalRelevamiento #existeRelevamiento').val(2);
  $('#modalRelevamiento').modal('show');
});

$('#modalRelSinSistema').on('hidden.bs.modal', function(){
  $('#casinoSinSistema').val("");
  $('#sectorSinSistema option').remove();
  $('#fechaRelSinSistema').datetimepicker('remove');
  $('#fechaGeneracion').datetimepicker('remove');
  $('#fechaRelSinSistema input,#fechaRelSinSistema_date,\
     #fechaGeneracion input,#fechaGeneracion_date').val('');
});

$('#modalCargaRelevamiento').on('hidden.bs.modal', function(){
  //limpiar modal
  $('#modalCargaRelevamiento #frmCargaRelevamiento').trigger('reset');
  $('#modalCargaRelevamiento #inputFisca').prop('readonly',false);
});

$('#modalMaquinasPorRelevamiento').on('hidden.bs.modal', function(){
  //resetearModal
  toggleDatosMaquinasPorRelevamiento(true);
});


let guardado = true;
let salida = 0; //cantidad de veces que se apreta salir

$(document).on('click','.carga',function(e){
  e.preventDefault();
  truncadas = 0;//@TODO: remover globales
  salida = 0;//ocultar mensaje de salida
  guardado = true;
  $('#modalCargaRelevamiento .mensajeSalida').hide();
  $("#modalCargaRelevamiento").animate({ scrollTop: 0 }, "slow");
  $('#btn-guardar').hide();//SI ESTÁ GUARDADO NO MUESTRA EL BOTÓN PARA GUARDAR
  $('#btn-finalizar').hide();

  const id_relevamiento = $(this).val();
  $('#id_relevamiento').val(id_relevamiento);

  $.get('relevamientos/obtenerRelevamiento/' + id_relevamiento, function(data){
    $('#cargaFechaActual').val(data.relevamiento.fecha);
    $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
    $('#cargaCasino').val(data.casino);
    $('#cargaSector').val(data.sector);
    $('#fecha').val(data.relevamiento.fecha_ejecucion);
    $('#fecha_ejecucion').val(data.relevamiento.fecha_ejecucion);
    $('#tecnico').val(data.relevamiento.tecnico);
    // si el relevamiento no tiene usuario fizcalizador se le asigna el actual
    $('#fiscaCarga').val(data?.usuario_cargador?.nombre ?? data.usuario_actual.usuario.nombre);
    $('#inputFisca').generarDataList('relevamientos/buscarUsuariosPorNombreYCasino/'+ data.id_casino,'usuarios','id_usuario','nombre',2);
    $('#inputFisca').setearElementoSeleccionado(data?.usuario_fiscalizador?.id_usuario ?? 0,data?.usuario_fiscalizador?.nombre ?? "");
    
    const tbody = $('#tablaCargaRelevamiento tbody');
    tbody.find('tr').remove();
    cargarTablaRelevamientos(data, tbody, 'Carga');
    calculoDiferencia(tbody);
    
    habilitarBotonFinalizar();
    $('#modalCargaRelevamiento').modal('show');
  });
});


$('#modalCargaRelevamiento').on('input', "input", function(){
  habilitarBotonGuardar();
});

$(document).on('change','.tipo_causa_no_toma',function(){
  //Si se elige algun tipo de no toma se vacian las cargas de contadores
  $(this).parent().parent().find('td').children('.contador').val('');
  //Se cambia el icono de diferencia
  $(this).parent().parent().find('td').find('i.fa-question').hide();
  $(this).parent().parent().find('td').find('i.fa-times').hide();
  $(this).parent().parent().find('td').find('i.fa-ban').show();//para no toma
  $(this).parent().parent().find('td').find('i.fa-check').hide();
  $(this).parent().parent().find('td').find('i.fa-exclamation').hide();

  habilitarBotonGuardar();
  habilitarBotonFinalizar();
});

//SALIR DEL RELEVAMIENTO
$('#btn-salir').click(function(){

  //Si está guardado deja cerrar el modal
  if (guardado || salida != 0) return $('#modalCargaRelevamiento').modal('hide');
  //Si no está guardado
  $('#modalCargaRelevamiento .mensajeSalida').show();
  $("#modalCargaRelevamiento").animate({ scrollTop: $('.mensajeSalida').offset().top }, "slow");
  salida = 1;
});

//FINALIZAR EL RELEVAMIENTO
$('#btn-finalizar').click(function(e){
  e.preventDefault();
  //Se evnía el relevamiento para guardar con estado 3 = 'Finalizado'
  enviarRelevamiento(3);
  $('#modalValidarRelevamiento').modal('hide');

});

//GUARDAR TEMPORALMENTE EL RELEVAMIENTO
$('#btn-guardar').click(function(e){
  e.preventDefault();
  //Se evnía el relevamiento para guardar con estado 2 = 'Carga parcial'
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
    $('#validarFechaActual').val(convertirDate(data.relevamiento.fecha));
    $('#validarCasino').val(data.casino);
    $('#validarSector').val(data.sector);
    $('#validarFiscaToma').val(data.usuario_fiscalizador.nombre);
    $('#validarFiscaCarga').val(data.usuario_cargador.nombre );
    $('#validarTecnico').val(data.relevamiento.tecnico);
    $('#observacion_validacion').val('');
    $('#tablaValidarRelevamiento tbody tr').remove();

    const tbody = $('#tablaValidarRelevamiento tbody');
    cargarTablaRelevamientos(data, tbody, 'Validar');
    calculoDiferenciaValidar(tbody, data);
    $('#modalValidarRelevamiento').modal('show');
  });
})

//validar
$('#btn-finalizarValidacion').click(function(e){
  $('#modalValidarRelevamiento').modal('hide');//???

  const id_relevamiento = $('#modalValidarRelevamiento #id_relevamiento').val();
  const maquinas_a_pedido = [];
  const data = [];

  $('#tablaValidarRelevamiento tbody tr').each(function(){   
    data.push({
      id_detalle_relevamiento: $(this).attr('id'),
      denominacion: $(this).attr('data-denominacion'),
      diferencia: $(this).find('.diferencia').val(),
      importado: $(this).find('.producido').val()
    });

    if($(this).find('.a_pedido').length && $(this).find('.a_pedido').val() != 0){
      maquinas_a_pedido.push({
        id: $(this).find('.a_pedido').attr('data-maquina'),
        en_dias: $(this).find('.a_pedido').val(),
      });
    }
  });

  const formData = {
    id_relevamiento: id_relevamiento,
    observacion_validacion: $('#observacion_validacion').val(),
    maquinas_a_pedido: maquinas_a_pedido,
    truncadas: truncadas,
    data
  };

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
      type: 'POST',
      url: 'relevamientos/validarRelevamiento',
      data: formData,
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

  const id_rel = $(this).val();

  $.get('relevamientos/verRelevamientoVisado/' + id_rel, function(data){
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

    data.detalles.forEach(function(d){
      const fila = $('<tr>');
      fila.attr('id', d.id_detalle_relevamiento)
      .append($('<td>').css('align','center').text(d.nro_admin));
      
      for(let c=1;c<=8;c++){
        const val = d.detalle?.['cont'+c];
        if(c<=6 || val != null)
          fila.append($('<td>').css('align','center').text(d.detalle?.['cont'+c] ?? ' - '));
      }

      fila.append($('<td>').css('text-align','center').text(d.detalle?.producido_calculado_relevado ?? ' - '));
      fila.append($('<td>').css('text-align','center').text(d.detalle?.producido_importado ?? ' - '));
      fila.append($('<td>').css('text-align','center').text(d.detalle?.diferencia ?? ' - '));
      fila.append($('<td>').text(' '));
      fila.append($('<td>').text(d.tipo_no_toma ?? ' - ').prop('disabled', true));
      fila.append($('<td>').text(d.denominacion ?? ' - ').prop('disabled', true));
      fila.append($('<td>').text(d?.mtm_pedido?.fecha ?? ' ').prop('disabled', true));

      $('#tablaValidarRelevamiento tbody').append(fila);
    });

    $('#modalValidarRelevamiento').modal('show');
  });
});

$('#btn-relevamientoSinSistema').click(function(e) {
  e.preventDefault();
  
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
  $('#sector option').remove();
  $('#modalMaquinasPorRelevamiento').modal('show');
  $('#modalMaquinasPorRelevamiento').find('.modal-body').children('#iconoCarga').hide();

  $('#modalMaquinasPorRelevamiento #detalles').hide();

  toggleDatosMaquinasPorRelevamiento(true);
});

//Generar el relevamiento de backup
$('#btn-backup').click(function(e){

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

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
      console.log(data);

      const response = data.responseJSON;

      if(typeof response.id_sector !== 'undefined'){
        mostrarErrorValidacion($('#casinoSinSistema'), response.id_sector[0],false);
        mostrarErrorValidacion($('#sectorSinSistema'), response.id_sector[0],false);
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
//Obtener las máquinas para cada relevamiento según el sector
$('#modalMaquinasPorRelevamiento #casino').on('change',function(){
  const id_casino = $('#modalMaquinasPorRelevamiento #casino option:selected').attr('id');
  $('#modalMaquinasPorRelevamiento #sector option').remove();
  
  agregarSectores(id_casino,$('#modalMaquinasPorRelevamiento #sector'),function(sectores){
    maquinasPorRelevamiento();
  });
});

$('#modalMaquinasPorRelevamiento #sector').on('change',function(){
  maquinasPorRelevamiento();
});

//Según el tipo de tipo se bloquea la fecha o no
$('#modalMaquinasPorRelevamiento #tipo_cantidad').change(function() {
  const tipo_cantidad_1 = $(this).find("option:selected").attr('id') == 1;
  toggleDatosMaquinasPorRelevamiento(!tipo_cantidad_1);
});

$('#btn-generarDeTodasFormas').click(function(){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: "POST",
    url: 'relevamientos/crearCantidadMaquinasPorRelevamiento',
    data: {
      id_sector: $('#modalMaquinasPorRelevamiento #sector option:selected').val(),
      id_tipo_cantidad_maquinas_por_relevamiento: $('#modalMaquinasPorRelevamiento #tipo_cantidad option:selected').attr('id'),
      fecha_desde: $('#modalMaquinasPorRelevamiento #fecha_desde').val(),
      fecha_hasta: $('#modalMaquinasPorRelevamiento #fecha_hasta').val(),
      cantidad_maquinas: $('#modalMaquinasPorRelevamiento #cantidad_maquinas_por_relevamiento').val(),
    },
    dataType: 'json',
    success: function (data) {
      console.log(data);
      //Habilitar botón originales y sacar los temporales
      $('#btn-generarMaquinasPorRelevamiento').show();
      $('#btn-generarDeTodasFormas').hide();
      $('#mensajeTemporal').hide();
      $('#btn-cancelarTemporal').hide();

      toggleDatosMaquinasPorRelevamiento(true);

      //Modificar defecto y/o agregar temporal
      setCantidadMaquinas(data);

      //Mostrar mensaje de éxito
    },
    error: function (data) {
      console.log(data);
    }
  });
});

//Si se cancela la generación temporal se ocultan los boton y se muestran los originales
$('#btn-cancelarTemporal').click(function(){
  $('#btn-generarMaquinasPorRelevamiento').show();
  $('#btn-generarDeTodasFormas').hide();
  $('#mensajeTemporal').hide();
  $('#btn-cancelarTemporal').hide();
  toggleDatosMaquinasPorRelevamiento(true);
});

$('#btn-generarMaquinasPorRelevamiento').click(function(){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  
  const formData = {
    id_sector: $('#modalMaquinasPorRelevamiento #sector option:selected').val(),
    id_tipo_cantidad_maquinas_por_relevamiento: $('#modalMaquinasPorRelevamiento #tipo_cantidad option:selected').attr('id'),
    fecha_desde: $('#modalMaquinasPorRelevamiento #fecha_desde').val(),
    fecha_hasta: $('#modalMaquinasPorRelevamiento #fecha_hasta').val(),
    cantidad_maquinas: $('#modalMaquinasPorRelevamiento #cantidad_maquinas_por_relevamiento').val(),
  };
  
  const crearCantidadMaquinasPorRelevamiento = function(){
    $.ajax({
      type: "POST",
      url: 'relevamientos/crearCantidadMaquinasPorRelevamiento',
      data: formData,
      dataType: 'json',
      success: function (data) {
          console.log(data);
          //Modificar defecto y/o agregar temporal
          setCantidadMaquinas(data);
          //Mostrar mensaje de éxito
      },
      error: function (data) {
        console.log(data);
      }
    });
  };
  
  if($('#modalMaquinasPorRelevamiento #tipo_cantidad option:selected').val() != 'Temporal'){
    return crearCantidadMaquinasPorRelevamiento();
  }
  //Preguntar si las fechas para cantidad TEMPORAL se pisan
  //SOLO PARA UN MENSAJE DE ALERTA AL USUARIO
  $.get('relevamientos/existeCantidadTemporalMaquinas/' + formData.id_sector + "/" + formData.fecha_desde + "/" + formData.fecha_hasta, function(data){
    console.log(data);
    //Si el intervalo de fechas se pisa con uno definido anteriormente
    if (data.existe) {
      //Mostrar mensaje y habilitar boton para generar de todas formas con las fechas elegidas
      $('#btn-generarMaquinasPorRelevamiento').hide();
      $('#mensajeTemporal').show();
      $('#btn-generarDeTodasFormas').show();
      $('#btn-cancelarTemporal').show();
      //Deshabilitar todos los inputs
      toggleDatosMaquinasPorRelevamiento(false);
    }
    else {
      crearCantidadMaquinasPorRelevamiento();
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
    success: function (data) {
      maquinasPorRelevamiento();
    },
    error: function (error) {
      console.log('Error: ', error);
    }
  });
});

$(document).on('focusin' , 'input' , function(e){
  $(this).removeClass('alerta');
})

function enviarRelevamiento(estado) {
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const detalles = $('#tablaCargaRelevamiento tbody tr').map(function(){
    let calculado = '';
    //Si se envía para finalizar se guarda el producido calculado
    if (estado == 3 && $(this).children().children('.tipo_causa_no_toma').val() == '') {
      calculado = calcularProducido($(this))[0]; //calculado relevado siempre se trabaja en dinero, no en creditos
      calculado = Math.round(calculado*100)/100;//Redondea a 2 digitos
    }
    
    return {
      id_unidad_medida: $(this).attr('data-medida'),
      denominacion: $(this).attr('data-denominacion'),
      id_detalle_relevamiento: $(this).attr('id'),
      ...Object.fromEntries(Array.from(Array(8).keys()).map((c) => c+1).map(function(c){
        const cont = 'cont'+c;
        return [cont,$(this).find('.'+cont).val().replace(/,/g,".")];
      })),
      id_tipo_causa_no_toma: $(this).children().children('.tipo_causa_no_toma').val(),
      producido_calculado_relevado: calculado,
    };
  }).toArray();
    
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
      detalles: detalles.length? detalles : 0,//@TODO: no entiendo esto del 0?
      truncadas: truncadas
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

      if(    typeof response.tecnico !== 'undefined'
          || typeof response.fecha_ejecucion !== 'undefined'
          || typeof response.id_usuario_fiscalizador !== 'undefined')
      {
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
      $('#tablaCargaRelevamiento tbody tr').each(function(obj,idx){
        var error=' ';
        for(let c=1;c<=8;c++){
          if(typeof response['detalles.'+ i +'.cont'+c] !== 'undefined'){
            filaError = $(this);
            mostrarErrorValidacion($(this).find('.cont'+c),response['detalles.'+ i +'.cont'+c][0],false);
          }
        }
      });

      if(filaError !== null){
        $("#modalCargaRelevamiento").animate({ scrollTop: filaError.offset().top }, "slow");
      }
    },
  });
}

function habilitarBotonGuardar(){
  guardado = false;
  $('#btn-guardar').show();
}

function habilitarBotonFinalizar(){
  let puedeFinalizar = true;
  const cantidadMaquinas = $('#tablaCargaRelevamiento tbody tr').each(function(i){   
    let inputLleno = false;
    
    //La fila tiene algun campo lleno
    $(this).children('td').find('.contador').each(function (j){
      inputLleno = inputLleno || ($(this).val().length > 0);
    });

    //Seleccionó un tipo de no toma
    const noToma = $(this).children('td').find('select').val() !== '';
    
    puedeFinalizar = puedeFinalizar && (inputLleno || noToma);
  });

  $('#btn-finalizar').toggle(puedeFinalizar);
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
      //Volver a cargar la tabla y ver la diferencia
      $.get('relevamientos/obtenerRelevamiento/' + $('#modalValidarRelevamiento #id_relevamiento').val(), function(data){
        const tbody = $('#tablaValidarRelevamiento tbody');
        tbody.find('tr').remove();
        cargarTablaRelevamientos(data, tbody, 'Validar');
        calculoDiferenciaValidar(tbody, data);
      });
    },
    error: function(error){
      console.log('Error de cambio denominacion: ', error);
    },
  });
}

$(document).on('click','.ajustar',function(e){
  const medida_es_credito = $(this).siblings('input:checked').val() == 'credito';
  const fila   = $(this).closest('tr');
  
  fila.attr('data-medida', medida_es_credito? 1 : 2);
  
  if(!medida_es_credito){
    const den = fila.attr('data-denominacion') ?? '';
    fila.attr('data-denominacion',den == ''? 0.01 : den);
  }
  
  $(this).closest('.popover').siblings('.pop').find('i')
  .toggleClass('fa-life-ring',medida_es_credito)
  .toggleClass('fa-usd-circle',!medida_es_credito);
  
  enviarCambioDenominacion(fila.attr('id'),fila.attr('data-medida'), fila.attr('data-denominacion'));
});

$(document).on('click' , '.estadisticas_no_toma' , function (){
  const win = window.open('http://' + window.location.host + "/relevamientos/estadisticas_no_toma/" + $(this).val(), '_blank');

  if (win) {
    //Browser has allowed it to be opened
    win.focus();
  } else {
    //Browser has blocked it
    alert('Please allow popups for this website');
  }
});

function cargarTablaRelevamientos(data, tabla, estadoRelevamiento){
  //se cargan las observaciones del fiscalizador si es que hay
  $('#observacion_fisca_validacion').val(data.relevamiento.observacion_carga);
  
  const td = function(arg){return $('<td>').append(arg);}

  data.detalles.forEach(function(d){
    //  Unidad de medida: 1-Crédito, 2-Pesos      |    Denominación: para créditos

    const fila = $('<tr>').attr('id', d.detalle.id_detalle_relevamiento)
                        .attr('data-medida', d.unidad_medida.id_unidad_medida)
                        .attr('data-denominacion', d.denominacion);

    fila.append($('<td>').text(d.maquina));
    for(let c=1;c<=8;c++){
      const cont = $('<input>').addClass('contador cont'+c).addClass('form-control').val(d.detalle['cont'+c]);
      
      if(estadoRelevamiento == 'Carga' && d.formula != null){
        cont.prop('readonly',d.formula['cont'+c] == null);
      }
      else if (estadoRelevamiento == 'Validar'){
        cont.prop('readonly',true);
      }
      
      fila.append(td(cont).toggle(c<=6));//Los ultimos dos no estan habilitados... por ahora
    }
    
    
    fila
    .append(td($('<input>').addClass('producidoCalculado form-control').css('text-align','right').css('border','2px solid #6DC7BE').css('color','#6DC7BE').val(d.detalle.producido_calculado_relevado ?? '')).hide())
    .append(td($('<input>').addClass('producido form-control').css('text-align','right').css('border','2px solid #6DC7BE').css('color','#6DC7BE').val(d.producido ?? '')).hide())
    .append(td($('<input>').addClass('diferencia form-control').css('text-align','right').val('')).hide())
    .append(
      $('<td>').css('text-align','center')
      .append($('<i>').addClass('fa').addClass('fa-times').css('color','#EF5350').hide())
      .append($('<i>').addClass('fa').addClass('fa-check').css('color','#66BB6A').hide())
      .append($('<i>').addClass('fa').addClass('fa-ban').css('color','#1E90FF').hide())
      .append(
          $('<a>').addClass('pop')
          .attr("data-content", 'Contadores importados truncados')
          .attr("data-placement" , "top")
          .attr("rel","popover")
          .attr("data-trigger" , "hover")
          .append($('<i>').addClass('pop').addClass('fa').addClass('fa-exclamation')
          .css('color','#FFA726'))
      )
      .append(
          $('<a>').addClass('pop')
          .attr("data-content", 'No se importaron contadores')
          .attr("data-placement" , "top")
          .attr("rel","popover")
          .attr("data-trigger" , "hover")
          .append($('<i>').addClass('pop').addClass('fa').addClass('fa-question')
          .css('color','#42A5F5'))
      )
    );
    
    for(let c=1;c<=8;c++){
      fila.append($('<input>').addClass('formulaCont'+c).val(d?.formula?.['cont'+c] ?? null).hide());
    }
    for(let c=1;c<=8;c++){
      fila.append($('<input>').addClass('formulaOper'+c).val(d?.formula?.['operador'+c] ?? null).hide());
    }
    
    {
      //Hacer un for por todo los tipos de no toma para cargar el select
      const tipoNoToma = $('<select>').addClass('tipo_causa_no_toma form-control');
      tipoNoToma.append($('<option>').text('').val(''));

      data.tipos_causa_no_toma.forEach(function(t){
        const tipo = t.descripcion;
        const id = t.id_tipo_causa_no_toma;
        tipoNoToma.append($('<option>').text(tipo).val(id).attr('disabled',t.deprecado == 1? true : false));
      });

      tipoNoToma.val(d.tipo_causa_no_toma);
      
      fila.append(td(tipoNoToma));
    }
    
    
    {/*** PARA CONTROLAR LA UNIDAD DE MEDIDA ***/
      const unidadMedida = d.unidad_medida.id_unidad_medida;
      const formulario = `<div align="left">
        <input type="radio" name="medida" value="credito" ${unidadMedida == 1? 'checked' : ''} >
        <i style="margin-left:5px;position:relative;top:-3px;" class="fa fa-fw fa-life-ring"></i>
        <span style="position:relative;top:-3px;"> Cŕedito</span><br>
        <input type="radio" name="medida" value="pesos" ${unidadMedida != 1? 'checked' : ''} >
        <i style="margin-left:5px;position:relative;top:-3px;" class="fas fa-dollar-sign"></i>
        <span style="position:relative;top:-3px;"> Pesos</span> <br><br>
        <button id="${unidadMedida}" class="btn btn-deAccion btn-successAccion ajustar" type="button" style="margin-right:8px;">AJUSTAR</button>
        <button class="btn btn-deAccion btn-defaultAccion cancelarAjuste" type="button">CANCELAR</button>
      </div>`;
      

      fila.append(td(
        $('<button>')
        .attr('data-trigger','manual')
        .attr('data-toggle','popover')
        .attr('data-placement','left')
        .attr('data-html','true')
        .attr('title','AJUSTE')
        .attr('data-content',formulario)
        .attr('type','button')
        .addClass('btn btn-warning pop medida')
        .append($('<i>').addClass(unidadMedida == 1? 'fa fa-fw fa-life-ring' : 'fas fa-dollar-sign'))
      ).hide());
   }
    
   {
     const tipo_causa_no_toma = d.detalle.tipo_causa_no_toma;
     const diff = d.detalle.diferencia;
     const mostrar_botones = estadoRelevamiento == 'Validar' && (tipo_causa_no_toma != null || diff == null || (diff != 0 && ( diff %1000000 != 0)));
     const a_pedido = $('<select>').addClass('a_pedido form-control acciones_validacion').attr('data-maquina' ,d.detalle.id_maquina)
     .append($('<option>').val(0).text('NO'))
     .append($('<option>').val(1).text('1 día'))
     .append($('<option>').val(5).text('5 días'))
     .append($('<option>').val(10).text('10 días'))
     .append($('<option>').val(15).text('15 días'))
     .toggle(mostrar_botones);
     
     const estadisticas_no_toma = $('<button>').addClass('btn btn-success estadisticas_no_toma acciones_validacion')
      .attr('type' , 'button')
      .val(d.detalle.id_maquina)
      .append($('<i>').addClass('fas fa-fw fa-external-link-square-alt'))
      .toggle(mostrar_botones);
      
      fila.append(td(a_pedido));
      fila.append(td(estadisticas_no_toma));
    }
    
    tabla.append(fila);
    
    if(estadoRelevamiento == 'Validar'){
      fila.find('input').each(function(){$(this).attr('title',$(this).val());});
      fila.find('.producido').prop('readonly',true).show().parent().show();
      fila.find('.producidoCalculado').prop('readonly',true).show().parent().show();
      fila.find('.diferencia').prop('readonly',true).show().parent().show();
        
      const causa_notoma = data.tipos_causa_no_toma?.[parseInt(d?.tipo_causa_no_toma) - 1]?.descripcion ?? '';
      const input_notoma = $('<input>').addClass('tipo_causa_no_toma form-control').val(causa_notoma);
      
      if (causa_notoma != '') {
         input_notoma.css('border','2px solid #1E90FF').css('color','#1E90FF');
      }

      fila.find('td').find('.tipo_causa_no_toma').replaceWith(input_notoma).prop('readonly',true);
      fila.find('.btn.medida').parent().show();
    }
  });

  $('.pop').popover({
    html:true
  });
}

function calcularProducido(fila){
  let suma = 0;
  let inputValido = false;
  
  for(let c=1;c<=8;c++){
    const formulaCont = fila.children('.formulaCont'+c).val();
    const operador = fila.children('.formulaOper'+c).val();
    const contador_s = fila.find('td').children('.cont'+c).val();
    const contador = contador_s? parseFloat(contador_s.replace(/,/g,".")) : 0;
    
    inputValido = inputValido || (contador_s != '');
    
    if(formulaCont != ''){
      if(c == 1){
        suma = contador;
      }
      else{
        if(operador == '+') suma += contador;
        else                suma -= contador;
      }
    }
  }
  
  const denominacion = fila.attr('data-medida') == 1? fila.attr('data-denominacion') : 1;
  return [Number((suma * denominacion)),inputValido];
}

//CAMBIOS EN TABLAS RELEVAMIENTOS / MOSTRAR BOTÓN GUARDAR
$('#modalCargaRelevamiento').on('input', "#tablaCargaRelevamiento input:not(:radio):not('.denominacion')", function(){
  habilitarBotonGuardar();
  const renglon_actual = $(this).parent().parent();
  
  //Fijarse si se habilita o deshabilita el tipo no toma
  if($(this).val() != '') renglon_actual.find('td').children('.tipo_causa_no_toma').val('');

  habilitarBotonFinalizar();

  renglon_actual.find('i.fa-question,i.fa-times,i.fa-ban,i.fa-check,i.fa-exclamation').hide();
  if (renglon_actual.find('td').children('.producido').val() == '') {
    console.log('No hay producido');
    return renglon_actual.find('i.fa-question').show();
  }

  producido = parseFloat(renglon_actual.find('td').children('.producido').val());
  
  const [producido_calc,inputValido] = calcularProducido(renglon_actual);
  const diferencia = Number(producido_calc.toFixed(2)) - Number(Number(producido).toFixed(2));
  const diferencia_redondeada = Number(diferencia.toFixed(2));

  if (diferencia_redondeada == 0 && inputValido) {
    renglon_actual.find('i.fa-check').show();
  }
  else if(Math.abs(diferencia_redondeada) > 1 && diferencia_redondeada%1000000 == 0 && inputValido) { //El caso de que no haya diferencia ignorando la unidad del millon (en pesos)
    renglon_actual.find('i.fa-exclamation').show();
  } 
  else {
    renglon_actual.find('i.fa-times').show();
  }
});

function calculoDiferencia(tablaRelevamientos){
  //Calcular las diferencias
  tablaRelevamientos.find('tr').each(function(){
    $(this).find('input.cont1').eq(0).trigger('input');
  });
}

function calculoDiferenciaValidar(tablaValidarRelevamiento, data){
  //debido a que el metodo se llama en ultima instancia para validar, ahi empieza el contador desde cero
  truncadas=0;
  data.detalles.forEach(function(d){
    const id_detalle = d.detalle.id_detalle_relevamiento;
    const fila = tablaValidarRelevamiento.find('#' + id_detalle);
    
    const iconoPregunta   = fila.find(' a i.fa-question').hide();
    const iconoCruz       = fila.find('td i.fa-times').hide();
    const iconoNoToma     = fila.find('td i.fa-ban').hide();
    const iconoCheck      = fila.find('td i.fa-check').hide();
    const iconoAdmiracion = fila.find('td i.fa-exclamation').hide();
    
    const diferencia      = fila.find('td input.diferencia');
    
    //calcular la diferencia entre lo calculado y lo importado
    const diff = Math.abs(Number(d.detalle.producido_calculado_relevado - d.producido).toFixed(2));
    diferencia.val(diff);
    
    if(d.tipo_causa_no_toma != null && diff == 0) {
      iconoNoToma.show();
      return;
    }
        
    if(d.detalle.producido_calculado_relevado == null || diff != 0){
      diferencia.css('border',' 2px solid #EF5350').css('color','#EF5350');
      iconoCruz.show();
      return;
    }
    
    //si no se importaron contadores muestra = ?
    if(d.producido == null) {
      diferencia.css('border',' 2px solid #EF5350').css('color','#EF5350');
      iconoPregunta.show();
      return;
    }
    
    if((diff >= 1000000) && ((diff % 1000000) == 0)){
      truncadas++;
      iconoAdmiracion.show();
      diferencia.css('border','2px solid #FFA726').css('color','#FFA726');
      return;
    }
    
    iconoCheck.show();
    diferencia.css('border','2px solid #66BB6A').css('color','#66BB6A');
  });
}

function maquinasAPedido(){
  const id_sector = $('#modalRelevamiento #sector').val();
  const fecha = $('#fechaDate').val();

  $.get("relevamientos/obtenerCantidadMaquinasRelevamientoHoy/" + id_sector, function(cantidad){
    $('#modalRelevamiento #cantidad_maquinas').val(cantidad);
  });

  $.get("relevamientos/obtenerMtmAPedido/" + fecha + "/" + id_sector, function(data){
    const c = data.cantidad;
    $('#maquinas_pedido').toggle(c > 0)
    .find('span').text(`Este sector tiene ${c} máquina${c>1? 's' : ''} a pedido.`);
  });
}

function existeRelevamiento(){
  $.get('relevamientos/existeRelevamiento/' + $('#modalRelevamiento #sector').val(), function(data){
      //Se guarda un valor que indica que para el SECTOR y la FECHA ACTUAL:
          // 0: No existe relevamiento generado.
          // 1: Solamente está generado y se puede volver a generar.
          // 2: El relevamiento empezó a cargarse, entonces no se puede volver a generar.
      $('#modalRelevamiento #existeRelevamiento').val(data);
  });
}

/* Funciones de MÁQUINAS POR RELEVAMIENTO */
function maquinasPorRelevamiento() {
  const id_sector = $('#modalMaquinasPorRelevamiento #sector').val();
  //Si se elige correctamente un sector se muestran los detalles
  if (typeof id_sector == 'undefined'){
    //Ocultar detalles
    return $('#modalMaquinasPorRelevamiento #detalles').hide();
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
  
  data.forEach(function(valor){
    //MÁQUINAS POR DEFECTO
    if(valor.fecha_desde == null && valor.fecha_hasta == null) {
      $('#maquinas_defecto').text(valor.cantidad);
      return;
    }
    //MÁQUINAS TEMPORALES
    let fecha_desde = valor.fecha_desde.split("-");
    fecha_desde = `${fecha_desde[2]} ${nombreMeses[fecha_desde[1] - 1]} ${fecha_desde[0]}`;
    let fecha_hasta = valor.fecha_hasta.split("-");
    fecha_hasta = `${fecha_hasta[2]} ${nombreMeses[fecha_hasta[1] - 1]} ${fecha_hasta[0]}`;

    const cantidad = $('<span>').addClass('badge').text(valor.cantidad)
    .css({
      'background-color': '#6dc7be','font-family':'Roboto-Regular','font-size':'18px'
    });

    $('#maquinas_temporales tbody').prepend(
      $('<tr>').attr('id', valor.id_cantidad_maquinas_por_relevamiento)
      .append($('<td>').text(fecha_desde)) //fecha_desde
      .append($('<td>').text(fecha_hasta)) //fecha_hasta
      .append($('<td>').append(cantidad)) //cantidad_maquinas
      .append($('<td>').append(
          $('<button>').attr('type','button').addClass('btn btn-danger borrarCantidadTemporal')
          .append($('<i>').addClass('fa fa-fw fa-trash'))
        )
      ) //icono para borrar
    );

    //Si hay máquinas temporales MOSTRAR TABLA
    $('#maquinas_temporales').show();
  });
}

function habilitarDTPmaquinasPorRelevamiento() {
  $.get('obtenerFechaActual', function (data) {
    $('#modalMaquinasPorRelevamiento').find('#dtpFechaDesde,#dtpFechaHasta').each(function(){
      $(this).find('input').prop('readonly',false);
      $(this).datetimepicker({
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
  });
}

function deshabilitarDTPmaquinasPorRelevamiento(val = '') {
  $('#modalMaquinasPorRelevamiento').find('#dtpFechaDesde,#dtpFechaHasta').each(function(){
    $(this).find('input').prop('readonly',true).val(...(val === undefined? [] : [val]));
    $(this).datetimepicker('remove');
  });
}

function toggleDatosMaquinasPorRelevamiento(habilitado){
  $('#cantidad_maquinas_por_relevamiento').prop('readonly',!habilitado);
  $('#cantidad_maquinas_por_relevamiento').parent().find('button').attr('disabled',!habilitado);
  $('#modalMaquinasPorRelevamiento').find('#casino,#sector,#tipo_cantidad').attr('disabled',!habilitado);
  if(habilitado) habilitarDTPmaquinasPorRelevamiento();
  else           deshabilitarDTPmaquinasPorRelevamiento(undefined);
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
  const thead = $(this).closest('thead');
  thead.find('th').removeClass('activa');
  const i  = $(this).children('i');
  const sin = i.hasClass('fa-sort');
  const abajo = i.hasClass('fa-sort-down');
  i.removeClass();
  if(sin){
    i.addClass('fas fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else if(abajo){
    i.addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
  }
  else{
    i.addClass('fas fa-sort').parent().attr('estado','');
  }
  thead.find('th i').not(i).removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  //Fix error cuando librería saca los selectores
  const size = isNaN($('#herramientasPaginacion').getPageSize())? 10 : $('#herramientasPaginacion').getPageSize();
  page_size = (page_size == null || isNaN(page_size))? size : page_size;
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaRelevamientos .activa').attr('value'),orden: $('#tablaRelevamientos .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaRelevamientos th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  const formData = {
    fecha: $('#buscadorFecha').val(),
    casino: $('#buscadorCasino').val(),
    sector: $('#buscadorSector').val(),
    estadoRelevamiento: $('#buscadorEstado').val(),
    page: (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage(),
    sort_by: sort_by,
    page_size: page_size,
  };
  
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'relevamientos/buscarRelevamientos',
    data: formData,
    dataType: 'json',
    success: function (resultados){
      $('#herramientasPaginacion').generarTitulo(formData.page,formData.page_size,resultados.total,clickIndice);
      $('#tablaRelevamientos tbody tr').remove();
      resultados.data.forEach(function(d){
        $('#tablaRelevamientos tbody').append(crearFilaTabla(d));
      });
      $('#herramientasPaginacion').generarIndices(formData.page,formData.page_size,resultados.total,clickIndice);
      
      $.ajax({//@TODO: pasar a molde estatico
        type: 'GET',
        url: 'relevamientos/usuarioTienePermisos',
        data: {
          permisos : ["relevamiento_cargar","relevamiento_validar"],
        },
        dataType: 'json',
        success: function(permisos) {
          //Para los iconos que no hay permisos: OCULTARLOS!
          if (!permisos.relevamiento_cargar) $('.carga').hide();
          if (!permisos.relevamiento_validar) $('.validar').hide();
        },
        error: function(error) {
          console.log(error);
        },
      });
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

function crearFilaTabla(relevamiento){
  const fila = $('<tr>');
  fila.attr('id', relevamiento.id_relevamiento)
  .append($('<td>').addClass('col-xs-2').text((convertirDate(relevamiento.fecha))))
  .append($('<td>').addClass('col-xs-2').text(relevamiento.casino))
  .append($('<td>').addClass('col-xs-2').text(relevamiento.sector))
  .append($('<td>').addClass('col-xs-1').text(relevamiento.subrelevamiento ?? ''))
  .append($('<td>').addClass('col-xs-2').append(
    $('<i>').addClass('iconoEstadoRelevamiento fas fa-fw fa-dot-circle')).append($('<span>').text(relevamiento.estado))
  )
  .append($('<td>').addClass('col-xs-3')
    .append($('<button>').addClass('btn btn-info planilla').attr('type','button').val(relevamiento.id_relevamiento)
      .attr({'data-toggle':'tooltip','data-placement':'top','title':'VER PLANILLA','data-delay':'{"show":"300", "hide":"100"}'})
      .append($('<i>').addClass('far').addClass('fa-fw').addClass('fa-file-alt'))
    )
    .append($('<button>').addClass('btn btn-warning carga').attr('type','button').val(relevamiento.id_relevamiento)
      .attr({'data-toggle':'tooltip','trigger':'hover','data-placement':'top','title':'CARGAR RELEVAMIENTO'})
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-upload'))
    )
    .append($('<button>').addClass('btn btn-success validar').attr('type','button').val(relevamiento.id_relevamiento)
      .attr({'data-toggle':'tooltip','data-placement':'top','title':'VISAR RELEVAMIENTO','data-delay':'{"show":"300", "hide":"100"}'})
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-check'))
    )
    .append($('<button>').addClass('btn btn-success verDetalle').attr('type','button').val(relevamiento.id_relevamiento)
      .attr({'data-toggle':'tooltip','data-placement':'top','title':'VER RELEVAMIENTO','data-delay':'{"show":"300", "hide":"100"}'})
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-search-plus'))
    )
    .append($('<button>').addClass('btn btn-info imprimir').attr('type','button').val(relevamiento.id_relevamiento)
      .attr({'data-toggle':'tooltip','data-placement':'top','title':'IMPRIMIR PLANILLA','data-delay':'{"show":"300", "hide":"100"}'})
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-print'))
    )
    .append($('<button>').addClass('btn btn-success validado').attr('type','button').val(relevamiento.id_relevamiento)
      .attr({'data-toggle':'tooltip','data-placement':'top','title':'IMPRIMIR VISADO','data-delay':'{"show":"300", "hide":"100"}'})
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-bookmark'))
    )
  );

  const icono_planilla   = fila.find('.planilla').hide();
  const icono_carga      = fila.find('.carga').hide();
  const icono_validacion = fila.find('.validar').hide();
  const icono_imprimir   = fila.find('.imprimir').hide();
  const icono_validado   = fila.find('.validado').hide();
  const icono_verDetalle = fila.find('.verDetalle').hide();

  //Qué ESTADO e ICONOS mostrar
  switch (relevamiento.estado) {
    case 'Generado':
      fila.find('.iconoEstadoRelevamiento').addClass('faGenerado');
      icono_planilla.show();
      icono_carga.show();
      break;
    case 'Cargando':
      fila.find('.iconoEstadoRelevamiento').addClass('faCargando');
      icono_carga.show();
      icono_imprimir.show();
      break;
    case 'Finalizado':
      fila.find('.iconoEstadoRelevamiento').addClass('faFinalizado');
      icono_validacion.show();
      icono_imprimir.show();
      break;
    case 'Visado':
      fila.find('.iconoEstadoRelevamiento').addClass('faVisado');
      icono_imprimir.show();
      $.get('relevamientos/chequearRolFiscalizador', function(data){
        if(data!=1){
          icono_verDetalle.show();
        }
      });
      break;
    case 'Rel. Visado':
      fila.find('.iconoEstadoRelevamiento').addClass('faValidado');
      icono_imprimir.show();
      icono_validado.show();
      $.get('relevamientos/chequearRolFiscalizador', function(data){
        if(data!=1){
          icono_verDetalle.show();
        }
      });
      break;
  }
  
  return fila;
}

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#buscadorCasino').on('change',function(){
  $('#buscadorSector').empty();
  $('#buscadorSector').append($('<option>').val(0).text('-Todos los sectores-'));
  
  const id_casino = $(this).val();
  if(id_casino!=0)
    agregarSectores(id_casino,$('#buscadorSector'));
});
