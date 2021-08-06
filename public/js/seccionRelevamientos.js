$(document).ready(function(){
  var truncadas=0;
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
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
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
  $('#modalRelevamiento .modal-title').text('| NUEVO RELEVAMIENTO');
  $('#modalRelevamiento .modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
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

$('#casinoSinSistema').on('change', function(){
  var id_casino = $('#casinoSinSistema option:selected').attr('id');

  $('#sectorSinSistema option').remove();

  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
    console.log(data);
    for (var i = 0; i < data.sectores.length; i++) {
      $('#sectorSinSistema')
          .append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }
  });

  ocultarErrorValidacion($('#sectorSinSistema'));
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#modalRelevamiento #casino').on('change',function(){
  const id_casino = $('#modalRelevamiento #casino').val();

  $('#modalRelevamiento #sector option').remove();
  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
    for (let i = 0; i < data.sectores.length; i++) {
      const op = $('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion);
      $('#modalRelevamiento #sector').append(op)
    }
    maquinasAPedido();
    existeRelevamiento();
  });

  $('#modalRelevamiento #sector').removeClass('alerta');
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

$('#modalRelSinSistema').on('hidden.bs.modal', function(){
  $('#casinoSinSistema').val("");
  $('#sectorSinSistema option').remove();

  $('#fechaRelSinSistema').datetimepicker('remove');
  $('#fechaGeneracion').datetimepicker('remove');

  $('#fechaRelSinSistema input').val('');
  $('#fechaRelSinSistema_date').val('');

  $('#fechaGeneracion input').val('');
  $('#fechaGeneracion_date').val('');

});

$('#modalCargaRelevamiento').on('hidden.bs.modal', function(){
  //limpiar modal
  $('#modalCargaRelevamiento #frmCargaRelevamiento').trigger('reset');
  $('#modalCargaRelevamiento #inputFisca').prop('readonly',false);
});

$('#modalMaquinasPorRelevamiento').on('hidden.bs.modal', function(){
  //resetearModal
  desbloquearDatosMaquinasPorRelevamiento();
});

$(document).on('click','.carga',function(e){
  truncadas=0;
  e.preventDefault();

  //ocultar mensaje de salida
  salida = 0;
  guardado = true;
  $('#modalCargaRelevamiento .mensajeSalida').hide();
  $("#modalCargaRelevamiento").animate({ scrollTop: 0 }, "slow");

  var id_relevamiento = $(this).val();
  $('#id_relevamiento').val(id_relevamiento);

  //SI ESTÁ GUARDADO NO MUESTRA EL BOTÓN PARA GUARDAR
  $('#btn-guardar').hide();
  $('#btn-finalizar').hide();

  $.get('relevamientos/obtenerRelevamiento/' + id_relevamiento, function(data){
      console.log('DAT::',data);
      $('#cargaFechaActual').val(data.relevamiento.fecha);
      $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
      $('#cargaCasino').val(data.casino);
      $('#cargaSector').val(data.sector);

      $('#fecha').val(data.relevamiento.fecha_ejecucion);
      $('#fecha_ejecucion').val(data.relevamiento.fecha_ejecucion);

      $('#tecnico').val(data.relevamiento.tecnico);

      if (data.usuario_cargador != null) {
          $('#fiscaCarga').val(data.usuario_cargador.nombre);
      }else {

          $('#fiscaCarga').val(data.usuario_actual.usuario.nombre);// si el relevamiento no tiene usuario fizcalizador se le asigna el actual
      }

      $('#inputFisca').generarDataList('usuarios/buscarUsuariosPorNombreYCasino/'+ data.id_casino,'usuarios','id_usuario','nombre',2);
      $('#inputFisca').setearElementoSeleccionado(0,"");
      if (data.usuario_fiscalizador){
        $('#inputFisca').setearElementoSeleccionado(data.usuario_fiscalizador.id_usuario,data.usuario_fiscalizador.nombre);
      }

      $('#tablaCargaRelevamiento tbody tr').remove();

      var tablaCargaRelevamiento = $('#tablaCargaRelevamiento tbody');

      cargarTablaRelevamientos(data, tablaCargaRelevamiento, 'Carga');

      calculoDiferencia(tablaCargaRelevamiento);

      habilitarBotonFinalizar();
  });

  $('#modalCargaRelevamiento').modal('show');
});

var guardado;

//CAMBIOS EN TABLAS RELEVAMIENTOS / MOSTRAR BOTÓN GUARDAR
$('#modalCargaRelevamiento').on('input', "#tablaCargaRelevamiento input:not(:radio):not('.denominacion')", function(){
    habilitarBotonGuardar();

    //Fijarse si se habilita o deshabilita el tipo no toma
    if($(this).val() != '') $(this).parent().parent().find('td').children('.tipo_causa_no_toma').val('');

    habilitarBotonFinalizar();

    var id_detalle_relevamiento = $(this).parent().parent().attr('id');

    if ($(this).parent().parent().find('td').children('.producido').val() == '') {
      console.log('No hay producido');
        //Mostrar signo de pregunta
        $(this).parent().parent().find('td').children('i.fa-question').show();
        $(this).parent().parent().find('td').children('i.fa-times').hide();
        $(this).parent().parent().find('td').children('i.fa-check').hide();
        $(this).parent().parent().find('td').children('i.fa-exclamation').hide();
        $(this).parent().parent().find('td').children('i.fa-ban').hide();
    }else{

      producido = parseFloat($(this).parent().parent().find('td').children('.producido').val());

      formulaCont1 = $(this).parent().parent().children('.formulaCont1').val();
      formulaCont2 = $(this).parent().parent().children('.formulaCont2').val();
      formulaCont3 = $(this).parent().parent().children('.formulaCont3').val();
      formulaCont4 = $(this).parent().parent().children('.formulaCont4').val();
      formulaCont5 = $(this).parent().parent().children('.formulaCont5').val();
      formulaCont6 = $(this).parent().parent().children('.formulaCont6').val();
      formulaCont7 = $(this).parent().parent().children('.formulaCont7').val();
      formulaCont8 = $(this).parent().parent().children('.formulaCont8').val();

      operador1 = $(this).parent().parent().children('.formulaOper1').val();
      operador2 = $(this).parent().parent().children('.formulaOper2').val();
      operador3 = $(this).parent().parent().children('.formulaOper3').val();
      operador4 = $(this).parent().parent().children('.formulaOper4').val();
      operador5 = $(this).parent().parent().children('.formulaOper5').val();
      operador6 = $(this).parent().parent().children('.formulaOper6').val();
      operador7 = $(this).parent().parent().children('.formulaOper7').val();
      operador8 = $(this).parent().parent().children('.formulaOper8').val();

      contador1 = $(this).parent().parent().find('td').children('.cont1').val() != '' ? parseFloat($(this).parent().parent().find('td').children('.cont1').val().replace(/,/g,".")) : 0;
      contador2 = $(this).parent().parent().find('td').children('.cont2').val() != '' ? parseFloat($(this).parent().parent().find('td').children('.cont2').val().replace(/,/g,".")) : 0;
      contador3 = $(this).parent().parent().find('td').children('.cont3').val() != '' ? parseFloat($(this).parent().parent().find('td').children('.cont3').val().replace(/,/g,".")) : 0;
      contador4 = $(this).parent().parent().find('td').children('.cont4').val() != '' ? parseFloat($(this).parent().parent().find('td').children('.cont4').val().replace(/,/g,".")) : 0;
      contador5 = $(this).parent().parent().find('td').children('.cont5').val() != '' ? parseFloat($(this).parent().parent().find('td').children('.cont5').val().replace(/,/g,".")) : 0;
      contador6 = $(this).parent().parent().find('td').children('.cont6').val() != '' ? parseFloat($(this).parent().parent().find('td').children('.cont6').val().replace(/,/g,".")) : 0;
      contador7 = $(this).parent().parent().find('td').children('.cont7').val() != '' ? parseFloat($(this).parent().parent().find('td').children('.cont7').val().replace(/,/g,".")) : 0;
      contador8 = $(this).parent().parent().find('td').children('.cont8').val() != '' ? parseFloat($(this).parent().parent().find('td').children('.cont8').val().replace(/,/g,".")) : 0;

      var suma = 0;

      input1 = $(this).parent().parent().find('td').children('.cont1').val() != '' ? true : false;
      input2 = $(this).parent().parent().find('td').children('.cont2').val() != '' ? true : false;
      input3 = $(this).parent().parent().find('td').children('.cont3').val() != '' ? true : false;
      input4 = $(this).parent().parent().find('td').children('.cont4').val() != '' ? true : false;
      input5 = $(this).parent().parent().find('td').children('.cont5').val() != '' ? true : false;
      input6 = $(this).parent().parent().find('td').children('.cont6').val() != '' ? true : false;
      input7 = $(this).parent().parent().find('td').children('.cont7').val() != '' ? true : false;
      input8 = $(this).parent().parent().find('td').children('.cont8').val() != '' ? true : false;

      if(input1 || input2 || input3 || input4 || input5 || input6 || input7 || input8){
          inputValido=true;
      }else{
          inputValido=false;
      }

      //FALTA VALIDAR QUE EL INPUT ESTÉ LLENO
      if (formulaCont1 != '') {
        suma = contador1;
      }
      if (formulaCont2 != '') {
        if (operador1 == '+') suma += contador2;
        else suma -= contador2;
      }
      if (formulaCont3 != '') {
        if (operador2 == '+') suma += contador3;
        else suma -= contador3;
      }
      if (formulaCont4 != '') {
        if (operador3 == '+') suma += contador4;
        else suma -= contador4;
      }
      if (formulaCont5 != '') {
        if (operador4 == '+') suma += contador5;
        else suma -= contador5;
      }
      if (formulaCont6 != '') {
        if (operador5 == '+') suma += contador6;
        else suma -= contador6;
      }
      if (formulaCont7 != '') {
        if (operador6 == '+') suma += contador7;
        else suma -= contador7;
      }
      if (formulaCont8 != '') {
        if (operador7 == '+') suma += contador8;
        else suma -= contador8;
      }

      var renglon_actual = $(this).parent().parent();

      if(renglon_actual.attr('data-medida') == 1){//si trabjo en credito
        var denominacion = renglon_actual.attr('data-denominacion');
        var sumaxdenom = Number((suma * denominacion) );
        var producidoxcien = Number(producido);
        var diferencia = Number(sumaxdenom.toFixed(2)) - Number(producidoxcien.toFixed(2));

      }else{
        var sumaxdenom = Number(suma);
        var producidoxcien = Number(producido);
        //se contempla la posibilidad de que los contadores den negativo
        var diferencia = Number(sumaxdenom.toFixed(2)) - Number(producidoxcien.toFixed(2));
      }
      //luego de operar , en ciertos casos quedaba con mas digitos despues de la coma, por lo que se lo fuerza a dos luego de operar
      diferencia= Number(diferencia.toFixed(2));
      console.log('acac');

      if (diferencia == 0 && inputValido) {
          renglon_actual.find('i.fa-question').hide();
          renglon_actual.find('i.fa-times').hide();
          renglon_actual.find('i.fa-ban').hide();
          renglon_actual.find('i.fa-check').show();
          renglon_actual.find('i.fa-exclamation').hide();
        } else if(Math.abs(diferencia) > 1 && diferencia%1000000 == 0 && inputValido) { //El caso de que no haya diferencia ignorando la unidad del millon (en pesos)
          renglon_actual.find('i.fa-question').hide();
          renglon_actual.find('i.fa-times').hide();
          renglon_actual.find('i.fa-ban').hide();
          renglon_actual.find('i.fa-check').hide();
          renglon_actual.find('i.fa-exclamation').show();
        } else {
          renglon_actual.find('i.fa-question').hide();
          renglon_actual.find('i.fa-times').show();
          renglon_actual.find('i.fa-ban').hide();
          renglon_actual.find('i.fa-check').hide();
          renglon_actual.find('i.fa-exclamation').hide();
        }


      console.log("La suma es: " + (Math.round(suma * 100) / 100) * denominacion);
      console.log("Producido: " + producido);
      console.log("Diferencia: " + diferencia);
  }

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
var salida; //cantidad de veces que se apreta salir
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
    $('#validarFechaActual').val(convertirDate(data.relevamiento.fecha));
    $('#validarCasino').val(data.casino);
    $('#validarSector').val(data.sector);
    $('#validarFiscaToma').val(data.usuario_fiscalizador.nombre);
    $('#validarFiscaCarga').val(data.usuario_cargador.nombre );
    $('#validarTecnico').val(data.relevamiento.tecnico);
    $('#observacion_validacion').val('');
    $('#tablaValidarRelevamiento tbody tr').remove();

    var tablaValidarRelevamiento = $('#tablaValidarRelevamiento tbody');

    cargarTablaRelevamientos(data, tablaValidarRelevamiento, 'Validar');
    calculoDiferenciaValidar(tablaValidarRelevamiento, data);
  });

  $('#modalValidarRelevamiento').modal('show');
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

  var id_rel=$(this).val();

  $.get('relevamientos/verRelevamientoVisado/' + id_rel, function(data){
    console.log('rv', data);
    $('#modalValidarRelevamiento .modal-title').text('DETALLES RELEVAMIENTO VISADO');

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

    for (var i = 0; i < data.detalles.length; i++) {

      var fila= $(document.createElement('tr'));

      fila.attr('id', data.detalles[i].id_detalle_relevamiento)
      .append($('<td>').css('align','center')
      .text(data.detalles[i].nro_admin))

      if(data.detalles[i].detalle.cont1 != null){
        fila.append($('<td>').css('align','center')
        .text(data.detalles[i].detalle.cont1))
      }
      else{
        fila.append($('<td>')
        .text(' - ')).css('align','center')
      }
      if(data.detalles[i].detalle.cont2 != null){
        fila.append($('<td>')
        .text(data.detalles[i].detalle.cont2).css('text-align','center'))
      }
      else{
        fila.append($('<td>').css('text-align','center')
        .text(' - '))
      }
      if(data.detalles[i].detalle.cont3!= null){
        fila.append($('<td>')
        .text(data.detalles[i].detalle.cont3).css('text-align','center'))
      }
      else{
        fila.append($('<td>').css('text-align','center')
        .text(' - '))
      }

      if(data.detalles[i].detalle.cont4 != null){
        fila.append($('<td>')
        .text(data.detalles[i].detalle.cont4).css('text-align','center'))
      }
      else{
        fila.append($('<td>').css('text-align','center')
        .text(' - '))
      }
      if(data.detalles[i].detalle.cont5 != null){
        fila.append($('<td>')
        .text(data.detalles[i].detalle.cont5).css('text-align','center'))
      }
      else{
        fila.append($('<td>').css('text-align','center')
        .text(' - '))
      }
      if(data.detalles[i].detalle.cont6 != null){
        fila.append($('<td>')
        .text(data.detalles[i].detalle.cont6).css('text-align','center'))
      }
      else{
        fila.append($('<td>').css('text-align','center')
        .text(' - '))
      }
      if(data.detalles[i].detalle.cont7 != null){
        fila.append($('<td>')
        .text(data.detalles[i].detalle.cont7).css('text-align','center'))
      }

      if(data.detalles[i].detalle.cont8 != null){
        fila.append($('<td>').css('text-align','center')
        .text(data.detalles[i].detalle.cont8).css('text-align','center'))
      }


      if(data.detalles[i].detalle.producido_calculado_relevado != null){
        fila.append($('<td>').css('text-align','center')
        .text(data.detalles[i].detalle.producido_calculado_relevado))
      }
      else{
        fila.append($('<td>').css('text-align','center')
        .text(' - '))
      }

      if(data.detalles[i].detalle.producido_importado != null){
        fila.append($('<td>')
        .text(data.detalles[i].detalle.producido_importado))
      }
      else{
        fila.append($('<td>').css('text-align','center')
        .text(' - '))
      }

      if(data.detalles[i].detalle.diferencia != null){
        fila.append($('<td>')
        .text(data.detalles[i].detalle.diferencia))
      }
      else{
        fila.append($('<td>').css('text-align','center')
        .text(' - '))
      }

      fila.append($('<td>')
      .text(' '))

      if(data.detalles[i].tipo_no_toma != null){
        fila.append($('<td>')
        .text(data.detalles[i].tipo_no_toma).prop('disabled', true))
      }
      else{
        fila.append($('<td>')
        .text(' - ').prop('disabled', true))
      }
      fila.append($('<td>')
      .text(data.detalles[i].denominacion).prop('disabled', true))
      if(data.detalles[i].mtm_pedido != null){
        fila.append($('<td>')
        .text(data.detalles[i].mtm_pedido.fecha).prop('disabled', true))
      }
      else{
        fila.append($('<td>')
        .text(' ').prop('disabled', true))
      }

      $('#tablaValidarRelevamiento tbody').append(fila);
    }

    $('#modalValidarRelevamiento').modal('show');
  })


});

$('#btn-relevamientoSinSistema').click(function(e) {
  e.preventDefault();
  $('#fechaGeneracion').datetimepicker({
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

  $('#fechaRelSinSistema').datetimepicker({
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

  var formData = {
    fecha: $('#fechaRelSinSistema_date').val(),
    fecha_generacion: $('#fechaGeneracion_date').val(),
    id_sector: $('#sectorSinSistema').val(),
  }

  $.ajax({
      type: "POST",
      url: 'relevamientos/usarRelevamientoBackUp',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
            $('#btn-buscar').trigger('click');
            $('#modalRelSinSistema').modal('hide');

      },
      error: function (data) {
        console.log('ERROR!');
        console.log(data);

        var response = JSON.parse(data.responseText);

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
  $('#modalMaquinasPorRelevamiento #sector option').remove();
  $.get("sectores/obtenerSectoresPorCasino/" + $('#modalMaquinasPorRelevamiento #casino').val(), function(data){
    for (let i = 0; i < data.sectores.length; i++) {
      const op = $('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion);
      $('#modalMaquinasPorRelevamiento #sector').append(op);
    }
    maquinasPorRelevamiento();
  });
});

$('#modalMaquinasPorRelevamiento #sector').on('change',maquinasPorRelevamiento);

//Según el tipo de tipo se bloquea la fecha o no
$('#modalMaquinasPorRelevamiento #tipo_cantidad').change(function() {
  const habilitar = $("#modalMaquinasPorRelevamiento #tipo_cantidad").val() != 1;
  toggleDTPmaquinasPorRelevamiento(habilitar);
});

var generarMaquinasPorRelevamiento = true;

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
      desbloquearDatosMaquinasPorRelevamiento();
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

  desbloquearDatosMaquinasPorRelevamiento();
});

$('#btn-generarMaquinasPorRelevamiento').click(function(){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var id_sector = $('#modalMaquinasPorRelevamiento #sector option:selected').val();
  var fecha_desde = $('#modalMaquinasPorRelevamiento #fecha_desde').val();
  var fecha_hasta = $('#modalMaquinasPorRelevamiento #fecha_hasta').val();
  var id_tipo_cantidad_maquinas_por_relevamiento = $('#modalMaquinasPorRelevamiento #tipo_cantidad').val();
  var cantidad_maquinas = $('#modalMaquinasPorRelevamiento #cantidad_maquinas_por_relevamiento').val();

  //Todo para TEMPORAL
  if ($('#modalMaquinasPorRelevamiento #tipo_cantidad option:selected').text() == "Temporal") {
    //Preguntar si las fechas para cantidad TEMPORAL se pisan
    //SOLO PARA UN MENSAJE DE ALERTA AL USUARIO
    $.get('relevamientos/existeCantidadTemporalMaquinas/' + id_sector + "/" + fecha_desde + "/" + fecha_hasta, function(data){
          console.log(data);

          //Si el intervalo de fechas se pisa con uno definido anteriormente
          if (data.existe) {
              //Mostrar mensaje y habilitar boton para generar de todas formas con las fechas elegidas
              $('#btn-generarMaquinasPorRelevamiento').hide();

              $('#mensajeTemporal').show();
              $('#btn-generarDeTodasFormas').show();
              $('#btn-cancelarTemporal').show();

              //Deshabilitar todos los inputs
              bloquearDatosMaquinasPorRelevamiento();
          }
          else {
              var formData = {
                id_sector: id_sector,
                id_tipo_cantidad_maquinas_por_relevamiento: id_tipo_cantidad_maquinas_por_relevamiento,
                fecha_desde: fecha_desde,
                fecha_hasta: fecha_hasta,
                cantidad_maquinas: cantidad_maquinas,
              }

              console.log(formData);

              $.ajax({
                  type: "POST",
                  url: 'relevamientos/crearCantidadMaquinasPorRelevamiento',
                  data: formData,
                  dataType: 'json',
                  success: function (data) {
                      //Modificar defecto y/o agregar temporal
                      setCantidadMaquinas(data);
                  },
                  error: function (data) {
                    console.log(data);
                  }
              });
          }
    });
  }

  //Todo para DEFAULT
  else {
    var formData = {
      id_sector: id_sector,
      id_tipo_cantidad_maquinas_por_relevamiento: id_tipo_cantidad_maquinas_por_relevamiento,
      fecha_desde: fecha_desde,
      fecha_hasta: fecha_hasta,
      cantidad_maquinas: cantidad_maquinas,
    }

    console.log(formData);

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
  }


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

function producidoCalculadoRelevado(fila){ //funcion que calcula el producido
  var suma = 0;
  var unidad_medida = fila.attr('data-medida');
  var denominacion =  fila.attr('data-denominacion');
  formulaCont1 = fila.find('.formulaCont1').val();
  formulaCont2 = fila.find('.formulaCont2').val();
  formulaCont3 = fila.find('.formulaCont3').val();
  formulaCont4 = fila.find('.formulaCont4').val();
  formulaCont5 = fila.find('.formulaCont5').val();
  formulaCont6 = fila.find('.formulaCont6').val();
  formulaCont7 = fila.find('.formulaCont7').val();
  formulaCont8 = fila.find('.formulaCont8').val();

  operador1 = fila.find('.formulaOper1').val();
  operador2 = fila.find('.formulaOper2').val();
  operador3 = fila.find('.formulaOper3').val();
  operador4 = fila.find('.formulaOper4').val();
  operador5 = fila.find('.formulaOper5').val();
  operador6 = fila.find('.formulaOper6').val();
  operador7 = fila.find('.formulaOper7').val();
  operador8 = fila.find('.formulaOper8').val();

  producido = parseFloat(fila.find('td').find('.producido').val());

  contador1 = fila.find('td').find('.cont1').val() != '' ? parseFloat(fila.find('td').find('.cont1').val().replace(/,/g,".")) : 0;
  contador2 = fila.find('td').find('.cont2').val() != '' ? parseFloat(fila.find('td').find('.cont2').val().replace(/,/g,".")) : 0;
  contador3 = fila.find('td').find('.cont3').val() != '' ? parseFloat(fila.find('td').find('.cont3').val().replace(/,/g,".")) : 0;
  contador4 = fila.find('td').find('.cont4').val() != '' ? parseFloat(fila.find('td').find('.cont4').val().replace(/,/g,".")) : 0;
  contador5 = fila.find('td').find('.cont5').val() != '' ? parseFloat(fila.find('td').find('.cont5').val().replace(/,/g,".")) : 0;
  contador6 = fila.find('td').find('.cont6').val() != '' ? parseFloat(fila.find('td').find('.cont6').val().replace(/,/g,".")) : 0;
  contador7 = fila.find('td').find('.cont7').val() != '' ? parseFloat(fila.find('td').find('.cont7').val().replace(/,/g,".")) : 0;
  contador8 = fila.find('td').find('.cont8').val() != '' ? parseFloat(fila.find('td').find('.cont8').val().replace(/,/g,".")) : 0;

  //FALTA VALIDAR QUE EL INPUT ESTÉ LLENO
  if (formulaCont1 != '') {
    suma = contador1;
  }
  if (formulaCont2 != '') {
    if (operador1 == '+') suma += contador2;
    else suma -= contador2;
  }
  if (formulaCont3 != '') {
    if (operador2 == '+') suma += contador3;
    else suma -= contador3;
  }
  if (formulaCont4 != '') {
    if (operador3 == '+') suma += contador4;
    else suma -= contador4;
  }
  if (formulaCont5 != '') {
    if (operador4 == '+') suma += contador5;
    else suma -= contador5;
  }
  if (formulaCont6 != '') {
    if (operador5 == '+') suma += contador6;
    else suma -= contador6;
  }
  if (formulaCont7 != '') {
    if (operador6 == '+') suma += contador7;
    else suma -= contador7;
  }
  if (formulaCont8 != '') {
    if (operador7 == '+') suma += contador8;
    else suma -= contador8;
  }

  if(unidad_medida == 1){
     suma = suma * denominacion;
  }

  return suma;
}

function enviarRelevamiento(estado) {
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var detalles = [];

  $('#tablaCargaRelevamiento tbody tr').each(function(){
      var calculado = '';
      //Si se envía para finalizar se guarda el producido calculado
      if (estado == 3) {
        //Si no tiene una causa de no toma se calcula el producido
        if ($(this).children().children('.tipo_causa_no_toma').val() == '') {
            calculado = producidoCalculadoRelevado($(this)); //calculado relevado siempre se trabaja en dinero, no en creditos
            calculado = Math.round(calculado*100)/100;
        }
      }

      var detalle = {
        id_unidad_medida: $(this).attr('data-medida'),
        denominacion: $(this).attr('data-denominacion'),
        id_detalle_relevamiento: $(this).attr('id'),
        cont1: $(this).children().children('.cont1').val().replace(/,/g,"."),
        cont2: $(this).children().children('.cont2').val().replace(/,/g,"."),
        cont3: $(this).children().children('.cont3').val().replace(/,/g,"."),
        cont4: $(this).children().children('.cont4').val().replace(/,/g,"."),
        cont5: $(this).children().children('.cont5').val().replace(/,/g,"."),
        cont6: $(this).children().children('.cont6').val().replace(/,/g,"."),
        cont7: $(this).children().children('.cont7').val().replace(/,/g,"."),
        cont8: $(this).children().children('.cont8').val().replace(/,/g,"."),

        id_tipo_causa_no_toma: $(this).children().children('.tipo_causa_no_toma').val(),
        producido_calculado_relevado: calculado,
      };

      detalles.push(detalle);
  });
  if (detalles.length == 0) {
       detalles = 0;
  }
  //EL ESTADO EN 2 (SE GUARDÓ PARCIALMENTE)
  var formData = {
    id_relevamiento: $('#modalCargaRelevamiento #id_relevamiento').val(),
    id_usuario_fiscalizador: $('#inputFisca').obtenerElementoSeleccionado(),
    observacion_carga: $('#observacion_carga').val(),
    tecnico: $('#tecnico').val(),
    hora_ejecucion: $('#fecha_ejecucion').val(),
    estado: estado,
    detalles: detalles,
    truncadas:truncadas
  }


  $.ajax({
      type: 'POST',
      url: 'relevamientos/cargarRelevamiento',
      dataType: 'JSON',
      data: formData,
      success: function (data) {

        $('#btn-buscar').trigger('click');

        guardado = true;
        $('#btn-guardar').hide();

        if (estado == 3) {
          $('#modalCargaRelevamiento').modal('hide');
        }
      },
      error: function (data) {

        var response = JSON.parse(data.responseText);

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
        $('#tablaCargaRelevamiento tbody tr').each(function(filaidx,fila){
          var error=' ';
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
      },
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

    // console.log(fila);
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

    var formData = {
      id_detalle_relevamiento: id_maquina,
      id_unidad_medida: medida,
      denominacion: denominacion,
    }

    $.ajax({
      type: "POST",
      url: 'relevamientos/modificarDenominacionYUnidad',
      data: formData,
      dataType: 'json',
      success: function(data){
          $('.pop').popover('hide');

          var id_relevamiento = $('#modalValidarRelevamiento #id_relevamiento').val();

          //Volver a cargar la tabla y ver la diferencia
          $.get('relevamientos/obtenerRelevamiento/' + id_relevamiento, function(dataRelevamiento){

              $('#tablaValidarRelevamiento tbody tr').remove();

              var tablaValidarRelevamiento = $('#tablaValidarRelevamiento tbody');

              cargarTablaRelevamientos(dataRelevamiento, tablaValidarRelevamiento, 'Validar');
              calculoDiferenciaValidar(tablaValidarRelevamiento, dataRelevamiento);
          });

          console.log(data);
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

function cargarTablaRelevamientos(dataRelevamiento, tablaRelevamientos, estadoRelevamiento){
  var data = dataRelevamiento;
  var tabla = tablaRelevamientos;

  //se cargan las observaciones del fiscalizador si es que hay
  $('#observacion_fisca_validacion').val(data.relevamiento.observacion_carga);
  const validando = estadoRelevamiento == 'Validar';
  const cargando  = estadoRelevamiento == 'Carga';

  for (let i = 0; i < data.detalles.length; i++) {//@TODO: simplificar este FOR
    const d = data.detalles[i];
    const f = $('#moldeTablaCargaRelevamientos').clone().attr('id',d.detalle.id_detalle_relevamiento)
              .attr('data-medida', d.unidad_medida.id_unidad_medida)
              .attr('data-denominacion', d.denominacion);

    f.find('.maquina').text(d.maquina);

    for(let c=1;c<=8;c++){
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
      for(let c=1;c<=8;c++){
        f.find('.formulaCont'+c).val(d.formula['cont'+c]);
        f.find('.formulaOper'+c).val(d.formula['operador'+c]);
      }
    }

    if (d.producido != null) {
      f.find('.producido').css('border','2px solid #6DC7BE').css('color','#6DC7BE').val(d.producido)
      .prop('readonly',validando).toggle(validando);
    }

    if (d.producido_calculado_relevado != null) {
      f.find('.producidoCalculado').css('border','2px solid #6DC7BE').css('color','#6DC7BE').val(d.producido_calculado_relevado)
      .toggle(validando);
    }

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

function calculoDiferencia(tablaRelevamientos){
  //Calcular las diferencias
  tablaRelevamientos.find('tr').each(function(){
  var renglon_actual = $(this);
  // $('#tablaCargaRelevamiento tbody tr').each(function () {
      if ($(this).find('td').find('.producido').val() == '') {
          $(this).find('i.fa-question').show();
          $(this).find('i.fa-times').hide();
          $(this).find('i.fa-ban').hide();
          $(this).find('i.fa-check').hide();
          $(this).find('i.fa-exclamation').hide();
      }else{
              formulaCont1 = $(this).find('.formulaCont1').val();
              formulaCont2 = $(this).find('.formulaCont2').val();
              formulaCont3 = $(this).find('.formulaCont3').val();
              formulaCont4 = $(this).find('.formulaCont4').val();
              formulaCont5 = $(this).find('.formulaCont5').val();
              formulaCont6 = $(this).find('.formulaCont6').val();
              formulaCont7 = $(this).find('.formulaCont7').val();
              formulaCont8 = $(this).find('.formulaCont8').val();

              operador1 = $(this).find('.formulaOper1').val();
              operador2 = $(this).find('.formulaOper2').val();
              operador3 = $(this).find('.formulaOper3').val();
              operador4 = $(this).find('.formulaOper4').val();
              operador5 = $(this).find('.formulaOper5').val();
              operador6 = $(this).find('.formulaOper6').val();
              operador7 = $(this).find('.formulaOper7').val();
              operador8 = $(this).find('.formulaOper8').val();

              producido = parseFloat($(this).find('td').find('.producido').val());

              contador1 = $(this).find('.cont1').val() != '' ? parseFloat($(this).find('.cont1').val().replace(/,/g,".")) : 0;
              contador2 = $(this).find('.cont2').val() != '' ? parseFloat($(this).find('.cont2').val().replace(/,/g,".")) : 0;
              contador3 = $(this).find('.cont3').val() != '' ? parseFloat($(this).find('.cont3').val().replace(/,/g,".")) : 0;
              contador4 = $(this).find('.cont4').val() != '' ? parseFloat($(this).find('.cont4').val().replace(/,/g,".")) : 0;
              contador5 = $(this).find('.cont5').val() != '' ? parseFloat($(this).find('.cont5').val().replace(/,/g,".")) : 0;
              contador6 = $(this).find('.cont6').val() != '' ? parseFloat($(this).find('.cont6').val().replace(/,/g,".")) : 0;
              contador7 = $(this).find('.cont7').val() != '' ? parseFloat($(this).find('.cont7').val().replace(/,/g,".")) : 0;
              contador8 = $(this).find('.cont8').val() != '' ? parseFloat($(this).find('.cont8').val().replace(/,/g,".")) : 0;

              var suma = 0;

              var i = 1;

              // Se valida que almenos un input este lleno
              input1 = $(this).find('.cont1').val() != '' ? true : false;
              input2 = $(this).find('.cont2').val() != '' ? true : false;
              input3 = $(this).find('.cont3').val() != '' ? true : false;
              input4 = $(this).find('.cont4').val() != '' ? true : false;
              input5 = $(this).find('.cont5').val() != '' ? true : false;
              input6 = $(this).find('.cont6').val() != '' ? true : false;
              input7 = $(this).find('.cont7').val() != '' ? true : false;
              input8 = $(this).find('.cont8').val() != '' ? true : false;

              if(input1 || input2 || input3 || input4 || input5 || input6 || input7 || input8){
                inputValido=true;
              }else{
                inputValido=false;
              }
              console.log("valor del input 1", $(this).find('.cont1').val() )
              if (formulaCont1 != '') {
                suma = contador1;
              }
              if (formulaCont2 != '') {
                if (operador1 == '+') suma += contador2;
                else suma -= contador2;
              }
              if (formulaCont3 != '') {
                if (operador2 == '+') suma += contador3;
                else suma -= contador3;
              }
              if (formulaCont4 != '') {
                if (operador3 == '+') suma += contador4;
                else suma -= contador4;
              }
              if (formulaCont5 != '') {
                if (operador4 == '+') suma += contador5;
                else suma -= contador5;
              }
              if (formulaCont6 != '') {
                if (operador5 == '+') suma += contador6;
                else suma -= contador6;
              }
              if (formulaCont7 != '') {
                if (operador6 == '+') suma += contador7;
                else suma -= contador7;
              }
              if (formulaCont8 != '') {
                if (operador7 == '+') suma += contador8;
                else suma -= contador8;
              }

              var renglon_actual = $(this);

              if(renglon_actual.attr('data-medida') == 1){//si trabjo en credito
                //suma es creditos
                var denominacion = renglon_actual.attr('data-denominacion');
                var sumaxdenom = Number((suma * denominacion) );
                var producidoxcien = Number(producido);
                var diferencia = Number(sumaxdenom.toFixed(2)) - Number(producidoxcien.toFixed(2));

              }else{
                var sumatrunc = Number(suma);
                var producidoxcien = Number(producido);
                var diferencia = Number(sumatrunc.toFixed(2)) - Number(producidoxcien.toFixed(2));
              }
              console.log('acac',diferencia);
              if (diferencia == 0 && inputValido) {
                  renglon_actual.find('i.fa-question').hide();
                  renglon_actual.find('i.fa-times').hide();
                  renglon_actual.find('i.fa-check').show();
                  renglon_actual.find('i.fa-exclamation').hide();
                  renglon_actual.find('i.fa-ban').hide();
                } else if(Math.abs(diferencia) > 1 && diferencia%1000000 == 0 && inputValido) { //El caso de que no haya diferencia ignorando la unidad del millon (en pesos)
                  renglon_actual.find('i.fa-question').hide();
                  renglon_actual.find('i.fa-times').hide();
                  renglon_actual.find('i.fa-check').hide();
                  renglon_actual.find('i.fa-exclamation').show();
                  renglon_actual.find('i.fa-ban').hide();
                } else {
                  renglon_actual.find('i.fa-question').hide();
                  renglon_actual.find('i.fa-times').show();
                  renglon_actual.find('i.fa-check').hide();
                  renglon_actual.find('i.fa-exclamation').hide();
                  renglon_actual.find('i.fa-ban').hide();
                }

      }
  });
}

function calculoDiferenciaValidar(tablaValidarRelevamiento, data){
    //debido a que el metodo se llama en ultima instancia para validar, ahi empieza el contador desde cero
    truncadas=0;
    for (var i = 0; i < data.detalles.length; i++) {

      var id_detalle = data.detalles[i].detalle.id_detalle_relevamiento;
      console.log('id_detalle_relevamiento: ', id_detalle);

      var iconoPregunta = tablaValidarRelevamiento.find('#' + id_detalle + ' a i.fa-question').hide();
      var iconoCruz = tablaValidarRelevamiento.find('#' + id_detalle).find('td i.fa-times').hide();
      var iconoNoToma = tablaValidarRelevamiento.find('#' + id_detalle).find('td i.fa-ban').hide();
      var iconoCheck = tablaValidarRelevamiento.find('#' + id_detalle).find('td i.fa-check').show();
      var iconoAdmiracion = tablaValidarRelevamiento.find('#' + id_detalle + ' i.fa-exclamation').hide();
      var diferencia = tablaValidarRelevamiento.find('#' + id_detalle + ' td input.diferencia');

      if(data.detalles[i].detalle.producido_calculado_relevado == null){
        diferencia.val( math.abs(Number(data.detalles[i].producido))).css('border',' 2px solid rgb(239, 83, 80)').css('color','rgb(239, 83, 80)');
        iconoPregunta.hide();
        iconoCruz.show();
        iconoCheck.hide();
        iconoAdmiracion.hide();
        iconoNoToma.hide();
      }
      //si no se importaron contadores muestra = ?
      if(data.detalles[i].producido == null) {
        diferencia.val(data.detalles[i].detalle.producido_calculado_relevado).css('border',' 2px solid rgb(239, 83, 80)').css('color','rgb(239, 83, 80)');
        iconoPregunta.show();
        iconoCruz.hide();
        iconoCheck.hide();
        iconoAdmiracion.hide();
        iconoNoToma.hide();
      }
      //Si hay causa no toma = x
      else if(data.detalles[i].tipo_causa_no_toma != null) {
        iconoPregunta.hide();
        iconoCruz.hide();
        iconoCheck.hide();
        iconoNoToma.show();
        iconoAdmiracion.hide();
      }
      //Si no, calcular la diferencia entre lo calculado y lo importado
      else {
          //SI HAY DIFERENCIA
        //se cambio para considerar los contadores negativos
          var resta = Number(data.detalles[i].detalle.producido_calculado_relevado - data.detalles[i].producido );
          if (Number(resta.toFixed(2)) != 0) {
            var diferenciaProducido =  math.abs(Number(resta.toFixed(2))) >= 1000000;
            var moduloDiferencia = Number(resta.toFixed(2));
            moduloDiferencia= math.abs(Number(moduloDiferencia.toFixed(2))) % 1000000;

            console.log(math.abs(data.detalles[i].detalle.producido_calculado_relevado),"-",math.abs(data.detalles[i].producido));
            console.log('MODULO DIFERENCIA', moduloDiferencia);
            console.log('DIFERENCIA', diferenciaProducido);

            if(diferenciaProducido && math.abs(moduloDiferencia) == 0){
              iconoPregunta.hide();
              iconoCruz.hide();
              iconoCheck.hide();
              iconoAdmiracion.show();
              iconoNoToma.hide();
              truncadas++;
              diferencia.val(math.abs(resta.toFixed(2))).css('border','2px solid #FFA726').css('color','#FFA726');
            }
            else{
              iconoPregunta.hide();
              iconoCruz.show();
              iconoNoToma.hide();
              iconoCheck.hide();
              iconoAdmiracion.hide();

              diferencia.val(math.abs(resta.toFixed(2))).css('border','2px solid #EF5350').css('color','#EF5350');
            }
          }
          else {
            iconoPregunta.hide();
            iconoCruz.hide();
            iconoCheck.show();
            iconoNoToma.hide();
            iconoAdmiracion.hide();

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
      setCantidadMaquinasDefecto(valor);
      return;
    }
    //MÁQUINAS TEMPORALES
    setCantidadMaquinasTemporales(valor);
  });
}

function setCantidadMaquinasDefecto(valor) {
  $('#maquinas_defecto').text(valor.cantidad);
}

function setCantidadMaquinasTemporales(valor) {
  const nombreMeses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
  var fecha_desde = valor.fecha_desde.split("-");
  fecha_desde = fecha_desde[2] + " " + nombreMeses[fecha_desde[1] - 1] + " " + fecha_desde[0];
  var fecha_hasta = valor.fecha_hasta.split("-");
  fecha_hasta = fecha_hasta[2] + " " + nombreMeses[fecha_hasta[1] - 1] + " " + fecha_hasta[0];

  var cantidad = $('<span>').addClass('badge').text(valor.cantidad)
                            .css('background-color','#6dc7be')
                            .css('font-family','Roboto-Regular')
                            .css('font-size','18px')



  $('#maquinas_temporales tbody').append(
      $('<tr>').attr('id', valor.id_cantidad_maquinas_por_relevamiento)
          .append($('<td>').text(fecha_desde)) //fecha_desde
          .append($('<td>').text(fecha_hasta)) //fecha_hasta
          .append($('<td>').append(cantidad)) //cantidad_maquinas
          .append($('<td>')
              .append($('<button>')
                  .attr('type','button')
                  .addClass('btn btn-danger borrarCantidadTemporal')
                  .append(
                      $('<i>').addClass('fa fa-fw fa-trash')
                  )
              )
            ) //icono para borrar
  )

  //Si hay máquinas temporales MOSTRAR TABLA
  $('#maquinas_temporales').show();
}

function toggleDTPmaquinasPorRelevamiento(habilitado) {
  $('#dtpFechaDesde').data('datetimepicker').reset();
  $('#dtpFechaHasta').data('datetimepicker').reset();
  $('#dtpFechaDesde input,#dtpFechaHasta input').prop('readonly',!habilitado).toggle(habilitado);
  $('#dtpFechaDesde span,#dtpFechaHasta span').toggle(habilitado);
  $('#dtpFechaDesde,#dtpFechaHasta').siblings('h5').toggle(habilitado);
}

function bloquearDatosMaquinasPorRelevamiento() {
  $('#cantidad_maquinas_por_relevamiento').prop('readonly','true');
  $('#cantidad_maquinas_por_relevamiento').parent().find('button').attr('disabled',true);
  $('#modalMaquinasPorRelevamiento #casino').attr('disabled',true);
  $('#modalMaquinasPorRelevamiento #sector').attr('disabled',true);
  $('#modalMaquinasPorRelevamiento #tipo_cantidad').attr('disabled',true);
  $('#dtpFechaDesde input').prop('readonly',true);
  $('#dtpFechaHasta input').prop('readonly',true);
  $('#modalMaquinasPorRelevamiento #dtpFechaDesde').datetimepicker('remove');
  $('#modalMaquinasPorRelevamiento #dtpFechaHasta').datetimepicker('remove');
}

function desbloquearDatosMaquinasPorRelevamiento() {
  $('#cantidad_maquinas_por_relevamiento').prop('readonly',false);
  $('#cantidad_maquinas_por_relevamiento').parent().find('button').attr('disabled',false);
  $('#modalMaquinasPorRelevamiento #casino').attr('disabled',false);
  $('#modalMaquinasPorRelevamiento #sector').attr('disabled',false);
  $('#modalMaquinasPorRelevamiento #tipo_cantidad').attr('disabled',false);
  toggleDTPmaquinasPorRelevamiento(true);
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
$('#buscadorCasino').on('change',function(){
  const id_casino = $(this).find('option:selected').val();
  $('#buscadorSector').empty().append($('<option>').val(0).text('-Todos los sectores-'));
  if(id_casino!=0){ 
    $.get('http://' + window.location.host + "/sectores/obtenerSectoresPorCasino/" + id_casino,
      function(data){
        for (let i = 0; i < data.sectores.length; i++) 
          $('#buscadorSector').append($('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion))
      }
    );
  }
});
