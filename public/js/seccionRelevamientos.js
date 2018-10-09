var nombreMeses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
var truncadas=0;
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

  $('#btn-buscar').trigger('click',[1,10,'relevamiento.fecha','desc']);

});

$('#fecha').on('change', function (e) {
    $(this).trigger('focusin');
    habilitarBotonGuardar();
})

//Opacidad del modal al minimizar
$('#btn-minimizarNuevo').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarSinSistema').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarMRelevamientos').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarCargar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarValidar').click(function(){
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

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| RELEVAMIENTOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');
	$('#modalAyuda').modal('show');
});

//ABRIR MODAL DE NUEVO RELEVAMIENTO
$('#btn-nuevoRelevamiento').click(function(e){
  e.preventDefault();
  $('.modal-title').text('| NUEVO RELEVAMIENTO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
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

  // $('#sectorSinSistema').removeClass('alerta');
  ocultarErrorValidacion($('#sectorSinSistema'));
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#modalRelevamiento #casino').on('change',function(){
  var id_casino = $('#modalRelevamiento #casino option:selected').attr('id');

  $('#modalRelevamiento #sector option').remove();
  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data){

    for (var i = 0; i < data.sectores.length; i++) {
      $('#modalRelevamiento #sector')
          .append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
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
  console.log('Entra');

  switch ($('#modalRelevamiento #existeRelevamiento').val()) {
    case '0':
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

        var id_sector = $('#modalRelevamiento #sector option:selected').val();

        if (typeof id_sector == 'undefined') id_sector = 0;

        var formData = {
          id_sector: id_sector,
          cantidad_maquinas: $('#cantidad_maquinas').val(),
          cantidad_fiscalizadores: $('#cantidad_fiscalizadores').val(),
        }

        console.log(formData);

        $.ajax({
            type: "POST",
            url: 'relevamientos/crearRelevamiento',
            data: formData,
            dataType: 'json',
            // processData: false,
            // contentType:false,
            // cache:false,
            beforeSend: function(data){
              //Si están cargados los datos para generar oculta el formulario y muestra el icono de carga
              if ($('#modalRelevamiento #casino option:selected').val() != "") {
                  $('#modalRelevamiento').find('.modal-footer').children().hide();
                  $('#modalRelevamiento').find('.modal-body').children().hide();
                  $('#modalRelevamiento').find('.modal-body').children('#iconoCarga').show();
              }
            },
            success: function (data) {

                $('#btn-buscar').click();
                $('#modalRelevamiento').modal('hide');

                var iframe;
                iframe = document.getElementById("download-container");
                if (iframe === null){
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
                  // $('#modalRelevamiento #sector').addClass('alerta');
                  // $('#modalRelevamiento #casino').addClass('alerta');
              }

            } //error
        }); //$.ajax

        break;
    case '1':
        $('#modalRelevamiento').modal('hide');
        $('#modalRelevamiento #existeRelevamiento').val(0);
        $('#confirmacionGenerarRelevamiento').modal('show');
        break;
    case '2':
        $('#modalRelevamiento').modal('hide');
        $('#modalRelevamiento #existeRelevamiento').val(0);
        $('#imposibleGenerarRelevamiento').modal('show');
        break;
  }
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
        var diferencia = Number(sumaxdenom.toFixed(2)) - Number(producidoxcien.toFixed(2));
      }
      console.log('acac');

      if (diferencia == 0) {
          renglon_actual.find('i.fa-question').hide();
          renglon_actual.find('i.fa-times').hide();
          renglon_actual.find('i.fa-check').show();
          renglon_actual.find('i.fa-exclamation').hide();
        } else if(Math.abs(diferencia) > 1 && diferencia%1000000 == 0) { //El caso de que no haya diferencia ignorando la unidad del millon (en pesos)
          renglon_actual.find('i.fa-question').hide();
          renglon_actual.find('i.fa-times').hide();
          renglon_actual.find('i.fa-check').hide();
          renglon_actual.find('i.fa-exclamation').show();
        } else {
          renglon_actual.find('i.fa-question').hide();
          renglon_actual.find('i.fa-times').show();
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
    $(this).parent().parent().find('td').find('i.fa-times').show();
    $(this).parent().parent().find('td').find('i.fa-check').hide();
    $(this).parent().parent().find('td').find('i.fa-exclamation').hide();

    habilitarBotonGuardar();
    habilitarBotonFinalizar();
});

//SALIR DEL RELEVAMIENTO
var salida; //cantidad de veces que se apreta salir
$('#btn-salir').click(function(){

  //Si está guardado deja cerrar el modal
  if (guardado) $('#modalCargaRelevamiento').modal('hide');
  //Si no está guardado
  else{
    if (salida == 0) {
      $('#modalCargaRelevamiento .mensajeSalida').show();
      $("#modalCargaRelevamiento").animate({ scrollTop: $('.mensajeSalida').offset().top }, "slow");
      salida = 1;
    }else {
      $('#modalCargaRelevamiento').modal('hide');
    }
  }
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
    // $('#alertaArchivo').hide();

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
  var id_relevamiento = $(this).val();
  console.log(id_relevamiento);
  $('#modalValidarRelevamiento #id_relevamiento').val(id_relevamiento);

  $('#mensajeValidacion').hide();

  $.get('relevamientos/obtenerRelevamiento/' + id_relevamiento, function(data){

      $('#validarFechaActual').val(convertirDate(data.relevamiento.fecha));
      $('#validarCasino').val(data.casino);
      $('#validarSector').val(data.sector);
      $('#validarFiscaToma').val(data.usuario_fiscalizador.nombre);
      $('#validarFiscaCarga').val(data.usuario_cargador.nombre );
      $('#validarTecnico').val(data.relevamiento.tecnico);
      $('#observacion_validacion').val('');
      $('#tablaValidarRelevamiento tbody tr').remove();
      //$('#observacion_fisca_validacion')-val(data.relevamiento.observacion_carga);

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
    var id_relevamiento = $('#modalValidarRelevamiento #id_relevamiento').val();
    var maquinas_a_pedido=[];
    var data=[];

    $('#tablaValidarRelevamiento tbody tr').each(function(){
      var datos={
        id_detalle_relevamiento: $(this).attr('id'),
        denominacion: $(this).attr('data-denominacion'),
        diferencia: $(this).find('.diferencia').val(),
        importado: $(this).find('.producido').val()
      }
      console.log('da',datos);
      data.push(datos)

      if($(this).find('.a_pedido').length){
        if($(this).find('.a_pedido').val() != 0){
          var maquina = {
            id: $(this).find('.a_pedido').attr('data-maquina'),
            en_dias: $(this).find('.a_pedido').val(),
          }
          maquinas_a_pedido.push(maquina);
        }
      }
    })

    var formData = {
      id_relevamiento: id_relevamiento,
      observacion_validacion: $('#observacion_validacion').val(),
      maquinas_a_pedido: maquinas_a_pedido,
      data
    }

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
          console.log('MAL');
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
              .text(data.detalles[i].detalle.cont1))}
            else{
              fila.append($('<td>')
              .text(' - ')).css('align','center')
            }
            if(data.detalles[i].detalle.cont2 != null){
              fila.append($('<td>')
              .text(data.detalles[i].detalle.cont2).css('text-align','center'))}
            else{
              fila.append($('<td>').css('text-align','center')
              .text(' - '))
            }
            if(data.detalles[i].detalle.cont3!= null){
              fila.append($('<td>')
              .text(data.detalles[i].detalle.cont3).css('text-align','center'))}
            else{
              fila.append($('<td>').css('text-align','center')
              .text(' - '))
            }

            if(data.detalles[i].detalle.cont4 != null){
              fila.append($('<td>')
              .text(data.detalles[i].detalle.cont4).css('text-align','center'))}
            else{
              fila.append($('<td>').css('text-align','center')
              .text(' - '))
            }
            if(data.detalles[i].detalle.cont5 != null){
              fila.append($('<td>')
              .text(data.detalles[i].detalle.cont5).css('text-align','center'))}
            else{
              fila.append($('<td>').css('text-align','center')
              .text(' - '))
            }
            if(data.detalles[i].detalle.cont6 != null){
              fila.append($('<td>')
              .text(data.detalles[i].detalle.cont6).css('text-align','center'))}
            else{
              fila.append($('<td>').css('text-align','center')
              .text(' - '))
            }
            if(data.detalles[i].detalle.cont7 != null){
              fila.append($('<td>')
              .text(data.detalles[i].detalle.cont7).css('text-align','center'))}
            if(data.detalles[i].detalle.cont8 != null){
                fila.append($('<td>').css('text-align','center')
                .text(data.detalles[i].detalle.cont8).css('text-align','center'))}


                if(data.detalles[i].detalle.producido_calculado_relevado != null){
                  fila.append($('<td>').css('text-align','center')
                  .text(data.detalles[i].detalle.producido_calculado_relevado))}
                else{
                  fila.append($('<td>').css('text-align','center')
                  .text(' - '))
                }

                if(data.detalles[i].detalle.producido_importado != null){
                  fila.append($('<td>')
                  .text(data.detalles[i].detalle.producido_importado))}
                else{
                  fila.append($('<td>').css('text-align','center')
                  .text(' - '))
                }

                if(data.detalles[i].detalle.diferencia != null){
                  fila.append($('<td>')
                  .text(data.detalles[i].detalle.diferencia))}
                else{
                  fila.append($('<td>').css('text-align','center')
                  .text(' - '))
                }




            fila.append($('<td>')
            .text(' '))

            if(data.detalles[i].tipo_no_toma != null){
            fila.append($('<td>')
            .text(data.detalles[i].tipo_no_toma).prop('disabled', true))}
            else{
              fila.append($('<td>')
              .text(' - ').prop('disabled', true))
            }
            fila.append($('<td>')
            .text(data.detalles[i].denominacion).prop('disabled', true))
            if(data.detalles[i].mtm_pedido != null){
            fila.append($('<td>')
            .text(data.detalles[i].mtm_pedido.fecha).prop('disabled', true))}
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
  $('.modal-title').text('| RELEVAMIENTO SIN SISTEMA');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');

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

  $('.modal-title').text('| MÁQUINAS POR RELEVAMIENTOS');
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

  habilitarDTPmaquinasPorRelevamiento();
});

//Generar el relevamiento de backup
$('#btn-backup').click(function(e){

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var formData = {
    fecha: $('#fechaRelSinSistema_date').val(),
    fecha_generacion: $('#fechaGeneracion_date').val(),
    id_sector: $('#sectorSinSistema').val(),
  }

  console.log(formData);

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
  var id_casino = $('#modalMaquinasPorRelevamiento #casino option:selected').attr('id');

  $('#modalMaquinasPorRelevamiento #sector option').remove();
  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data){

    for (var i = 0; i < data.sectores.length; i++) {
      $('#modalMaquinasPorRelevamiento #sector')
          .append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }

    maquinasPorRelevamiento();
  });
});

$('#modalMaquinasPorRelevamiento #sector').on('change',function(){
    maquinasPorRelevamiento();
});

//Según el tipo de tipo se bloquea la fecha o no
$('#modalMaquinasPorRelevamiento #tipo_cantidad').change(function() {

  console.log($(this).val());
  console.log($("#modalMaquinasPorRelevamiento #tipo_cantidad option:selected").attr('id'));

  if ($("#modalMaquinasPorRelevamiento #tipo_cantidad option:selected").attr('id') == 1) {
    console.log("Bloquear fechas!");

      deshabilitarDTPmaquinasPorRelevamiento();
  }
  else {
    habilitarDTPmaquinasPorRelevamiento();
  }
});

var generarMaquinasPorRelevamiento = true;

$('#btn-generarDeTodasFormas').click(function(){
    $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

    var tipo_cantidad = $('#modalMaquinasPorRelevamiento #tipo_cantidad option:selected').val();
    var id_sector = $('#modalMaquinasPorRelevamiento #sector option:selected').val();
    var fecha_desde = $('#modalMaquinasPorRelevamiento #fecha_desde').val();
    var fecha_hasta = $('#modalMaquinasPorRelevamiento #fecha_hasta').val();
    var id_tipo_cantidad_maquinas_por_relevamiento = $('#modalMaquinasPorRelevamiento #tipo_cantidad option:selected').attr('id');
    var cantidad_maquinas = $('#modalMaquinasPorRelevamiento #cantidad_maquinas_por_relevamiento').val();

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
            //Habilitar botón originales y sacar los temporales
            $('#btn-generarMaquinasPorRelevamiento').show();
            $('#btn-generarDeTodasFormas').hide();
            $('#mensajeTemporal').hide();
            $('#btn-cancelarTemporal').hide();

            desbloquearDatosMaquinasPorRelevamiento();

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

  desbloquearDatosMaquinasPorRelevamiento();
});

$('#btn-generarMaquinasPorRelevamiento').click(function(){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var tipo_cantidad = $('#modalMaquinasPorRelevamiento #tipo_cantidad option:selected').val();
  var id_sector = $('#modalMaquinasPorRelevamiento #sector option:selected').val();
  var fecha_desde = $('#modalMaquinasPorRelevamiento #fecha_desde').val();
  var fecha_hasta = $('#modalMaquinasPorRelevamiento #fecha_hasta').val();
  var id_tipo_cantidad_maquinas_por_relevamiento = $('#modalMaquinasPorRelevamiento #tipo_cantidad option:selected').attr('id');
  var cantidad_maquinas = $('#modalMaquinasPorRelevamiento #cantidad_maquinas_por_relevamiento').val();

  //Todo para TEMPORAL
  if (tipo_cantidad == "Temporal") {

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

  console.log('CLICK');
  var id_cantidad_maquinas_por_relevamiento = $(this).parent().parent().attr('id');

  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var formData = {
    id_cantidad_maquinas_por_relevamiento: id_cantidad_maquinas_por_relevamiento
  }

  $.ajax({
      type: "POST",
      url: 'relevamientos/eliminarCantidadMaquinasPorRelevamiento',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
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

        var i = 0;
        var filaError = 0;
        $('#tablaCargaRelevamiento tbody tr').each(function(){
          var error=' ';
          if(typeof response['detalles.'+ i +'.cont1'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.cont1'),response['detalles.'+ i +'.cont1'][0],false);
          }
          if(typeof response['detalles.'+ i +'.cont2'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.cont2'),response['detalles.'+ i +'.cont2'][0],false);
          }
          if(typeof response['detalles.'+ i +'.cont3'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.cont3'),response['detalles.'+ i +'.cont3'][0],false);
          }
          if(typeof response['detalles.'+ i +'.cont4'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.cont4'),response['detalles.'+ i +'.cont4'][0],false);
          }
          if(typeof response['detalles.'+ i +'.cont5'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.cont5'),response['detalles.'+ i +'.cont5'][0],false);
          }
          if(typeof response['detalles.'+ i +'.cont6'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.cont6'),response['detalles.'+ i +'.cont6'][0],false);
          }
          if(typeof response['detalles.'+ i +'.cont7'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.cont7'),response['detalles.'+ i +'.cont7'][0],false);
          }
          if(typeof response['detalles.'+ i +'.cont8'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.cont8'),response['detalles.'+ i +'.cont8'][0],false);
          }
          i++;
        });

        if(filaError >= 0)
        {
          var id_pos = $("#modalCargaRelevamiento #tablaCargaRelevamiento tbody tr:eq("+filaError+")").attr('id');
          var pos = $('#' + id_pos).offset().top;
          $("#modalCargaRelevamiento").animate({ scrollTop: pos }, "slow");

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

  console.log(cantidadMaquinas,maquinasRelevadas);
  if(cantidadMaquinas == maquinasRelevadas) $('#btn-finalizar').show();
  else $('#btn-finalizar').hide();
}

$(document).on('click','.pop',function(e){
    e.preventDefault();
    // console.log('asd');
    var fila = $(this).parent().parent();

    //Si está en crédito pasarla a pesos
    if (fila.attr('data-medida') == 1) {
        // fila.attr('data-medida','2'); //Se pasa a pesos
        // $(this).find('i').removeClass('fa-life-ring').addClass('fa-usd'); //Se cambia el icono del botón
        // $('.pop').popover('hide');

    //Si está en PESOS pasarla a cŕedito y mostrar el pop
    }else {
        // fila.attr('data-medida','1'); //Se pasa a créditos
        // $(this).find('i').removeClass('fa-usd').addClass('fa-life-ring');

        // $('.pop').not(this).popover('hide');
        // $(this).popover('show');
    }

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
    var denominacion = $(this).siblings('input:text');
    var fila = $(this).closest('tr');

    var boton = $(this).closest('.popover').siblings('.pop');

    if (medida == 'credito'){
        //Si la denominación no está vacía
        if (denominacion.val() != '') {
            fila.attr('data-medida', 1); //Cambia el tipo de medida de la fila
            fila.attr('data-denominacion', denominacion.val()) //Cambia la denominacion
            boton.find('i').addClass('fa-life-ring').removeClass('fa-usd-circle'); //Cambia el icono del botón


            enviarCambioDenominacion(fila.attr('id'), 1, denominacion.val());
        }
        //Complete el campo denominación
        else {
            denominacion.addClass('alerta');
        }
    }
    else {
        fila.attr('data-medida', 2);
        fila.attr('data-denominacion', 0.01) //Cambia la denominacion
        boton.find('i').removeClass('fa-life-ring').addClass('fa-usd-circle');

        enviarCambioDenominacion(fila.attr('id'), 2, 0.01);
    }

});

$(document).on('click' , '.estadisticas_no_toma' , function (){
  var url = 'http://' + window.location.host + "/relevamientos/estadisticas_no_toma/" + $(this).val();

  var win = window.open(url, '_blank');

  if (win) {
      //Browser has allowed it to be opened
      win.focus();
  } else {
      //Browser has blocked it
      alert('Please allow popups for this website');
  }

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

  for (var i = 0; i < data.detalles.length; i++) {
      //Hacer un for por todo los tipos de no toma para cargar el select
      var tipoNoToma = $('<select>').addClass('tipo_causa_no_toma form-control');
      tipoNoToma.append($('<option>').text('').val(''));

      for (var j = 0; j < data.tipos_causa_no_toma.length; j++) {
          var tipo = data.tipos_causa_no_toma[j].descripcion;
          var id = data.tipos_causa_no_toma[j].id_tipo_causa_no_toma;
          tipoNoToma.append($('<option>').text(tipo).val(id));
      }

      tipoNoToma.val(data.detalles[i].tipo_causa_no_toma);

      //  Unidad de medida: 1-Crédito, 2-Pesos      |    Denominación: para créditos

      var fila = $('<tr>').attr('id', data.detalles[i].detalle.id_detalle_relevamiento)
                          .attr('data-medida', data.detalles[i].unidad_medida.id_unidad_medida)
                          .attr('data-denominacion', data.detalles[i].denominacion);


      /*** PARA CONTROLAR LA UNIDAD DE MEDIDA ***/

      var unidadMedida = data.detalles[i].unidad_medida.id_unidad_medida;

      console.log(unidadMedida);

      var formulario = "";

      //Si la unidad es CRéDITO
      if (unidadMedida == 1) {
          formulario =  '<div align="left">'
                      +   '<input type="radio" name="medida" value="credito" checked>'
                      +               '<i style="margin-left:5px;position:relative;top:-3px;" class="fa fa-fw fa-life-ring"></i>'
                      +               '<span style="position:relative;top:-3px;"> Cŕedito</span><br>'
                      +   '<input type="radio" name="medida" value="pesos">'
                      +               '<i style="margin-left:5px;position:relative;top:-3px;" class="fas fa-dollar-sign"></i>'
                      +               '<span style="position:relative;top:-3px;"> Pesos</span> <br><br>'
                      +   '<input class="form-control denominacion" type="text" value="'+data.detalles[i].denominacion+'" placeholder="Denominación"><br>'
                      +   '<button id="'+ data.detalles[i].unidad_medida.id_unidad_medida +'" class="btn btn-deAccion btn-successAccion ajustar" type="button" style="margin-right:8px;">AJUSTAR</button>'
                      +   '<button class="btn btn-deAccion btn-defaultAccion cancelarAjuste" type="button">CANCELAR</button>'
                      + '</div>';
      }
      //Si la unidad es PESOS
      else {
          formulario =  '<div align="left">'
                      +   '<input type="radio" name="medida" value="credito">'
                      +               '<i style="margin-left:5px;position:relative;top:-3px;" class="fa fa-fw fa-life-ring"></i>'
                      +               '<span style="position:relative;top:-3px;"> Cŕedito</span><br>'
                      +   '<input type="radio" name="medida" value="pesos" checked>'
                      +               '<i style="margin-left:5px;position:relative;top:-3px;" class="fas fa-dollar-sign"></i>'
                      +               '<span style="position:relative;top:-3px;"> Pesos</span> <br><br>'
                      +   '<input class="form-control denominacion" type="text" value="" placeholder="Denominación" disabled><br>'
                      +   '<button id="'+ data.detalles[i].unidad_medida.id_unidad_medida +'" class="btn btn-deAccion btn-successAccion ajustar" type="button" style="margin-right:8px;">AJUSTAR</button>'
                      +   '<button class="btn btn-deAccion btn-defaultAccion cancelarAjuste" type="button">CANCELAR</button>'
                      + '</div>';
      }


      var botonDenominacion = $('<button>')
                                      .attr('data-trigger','manual')
                                      .attr('data-toggle','popover')
                                      .attr('data-placement','left')
                                      .attr('data-html','true')
                                      .attr('title','AJUSTE')
                                      .attr('data-content',formulario)
                                      .attr('type','button')
                                      .addClass('btn btn-warning pop medida')

      //Si la unidad de medida es CRÉDITO
      if (unidadMedida == 1) botonDenominacion.append($('<i>').addClass('fa fa-fw fa-life-ring'));
      //Si la unidad de medida es PESOS
      else botonDenominacion.append($('<i>').addClass('fas fa-dollar-sign'));


      // var columna = $('<td>');
      var cont1 = $('<input>').addClass('cont1 contador').addClass('form-control').val(data.detalles[i].detalle.cont1);
      var cont2 = $('<input>').addClass('cont2 contador').addClass('form-control').val(data.detalles[i].detalle.cont2);
      var cont3 = $('<input>').addClass('cont3 contador').addClass('form-control').val(data.detalles[i].detalle.cont3);
      var cont4 = $('<input>').addClass('cont4 contador').addClass('form-control').val(data.detalles[i].detalle.cont4);
      var cont5 = $('<input>').addClass('cont5 contador').addClass('form-control').val(data.detalles[i].detalle.cont5);
      var cont6 = $('<input>').addClass('cont6 contador').addClass('form-control').val(data.detalles[i].detalle.cont6);
      var cont7 = $('<input>').addClass('cont7 contador').addClass('form-control').val(data.detalles[i].detalle.cont7);
      var cont8 = $('<input>').addClass('cont8 contador').addClass('form-control').val(data.detalles[i].detalle.cont8);

      if (data.detalles[i].formula != null){
        var formulaCont1 = $('<input>').addClass('formulaCont1').val(data.detalles[i].formula.cont1).hide();
        var formulaCont2 = $('<input>').addClass('formulaCont2').val(data.detalles[i].formula.cont2).hide();
        var formulaCont3 = $('<input>').addClass('formulaCont3').val(data.detalles[i].formula.cont3).hide();
        var formulaCont4 = $('<input>').addClass('formulaCont4').val(data.detalles[i].formula.cont4).hide();
        var formulaCont5 = $('<input>').addClass('formulaCont5').val(data.detalles[i].formula.cont5).hide();
        var formulaCont6 = $('<input>').addClass('formulaCont6').val(data.detalles[i].formula.cont6).hide();
        var formulaCont7 = $('<input>').addClass('formulaCont7').val(data.detalles[i].formula.cont7).hide();
        var formulaCont8 = $('<input>').addClass('formulaCont8').val(data.detalles[i].formula.cont8).hide();
        var formulaOper1 = $('<input>').addClass('formulaOper1').val(data.detalles[i].formula.operador1).hide();
        var formulaOper2 = $('<input>').addClass('formulaOper2').val(data.detalles[i].formula.operador2).hide();
        var formulaOper3 = $('<input>').addClass('formulaOper3').val(data.detalles[i].formula.operador3).hide();
        var formulaOper4 = $('<input>').addClass('formulaOper4').val(data.detalles[i].formula.operador4).hide();
        var formulaOper5 = $('<input>').addClass('formulaOper5').val(data.detalles[i].formula.operador5).hide();
        var formulaOper6 = $('<input>').addClass('formulaOper6').val(data.detalles[i].formula.operador6).hide();
        var formulaOper7 = $('<input>').addClass('formulaOper7').val(data.detalles[i].formula.operador7).hide();
        var formulaOper8 = $('<input>').addClass('formulaOper8').val(data.detalles[i].formula.operador8).hide();
      }else {
        var formulaCont1 = $('<input>').addClass('formulaCont1').val(null).hide();
        var formulaCont2 = $('<input>').addClass('formulaCont2').val(null).hide();
        var formulaCont3 = $('<input>').addClass('formulaCont3').val(null).hide();
        var formulaCont4 = $('<input>').addClass('formulaCont4').val(null).hide();
        var formulaCont5 = $('<input>').addClass('formulaCont5').val(null).hide();
        var formulaCont6 = $('<input>').addClass('formulaCont6').val(null).hide();
        var formulaCont7 = $('<input>').addClass('formulaCont7').val(null).hide();
        var formulaCont8 = $('<input>').addClass('formulaCont8').val(null).hide();
        var formulaOper1 = $('<input>').addClass('formulaOper1').val(null).hide();
        var formulaOper2 = $('<input>').addClass('formulaOper2').val(null).hide();
        var formulaOper3 = $('<input>').addClass('formulaOper3').val(null).hide();
        var formulaOper4 = $('<input>').addClass('formulaOper4').val(null).hide();
        var formulaOper5 = $('<input>').addClass('formulaOper5').val(null).hide();
        var formulaOper6 = $('<input>').addClass('formulaOper6').val(null).hide();
        var formulaOper7 = $('<input>').addClass('formulaOper7').val(null).hide();
        var formulaOper8 = $('<input>').addClass('formulaOper8').val(null).hide();
      }

      console.log('estado es ',estadoRelevamiento);

      //PARTE DE PRODUCIDOS
      if (data.detalles[i].producido != null) {
          var producido = $('<input>').addClass('producido form-control').css('text-align','right').css('border','2px solid #6DC7BE').css('color','#6DC7BE').val(data.detalles[i].producido).hide();
      }else {
          var producido = $('<input>').addClass('producido form-control').css('text-align','right').val('').hide();
      }

      if (data.detalles[i].detalle.producido_calculado_relevado != null) {
          var producidoCalculado = $('<input>').addClass('producidoCalculado form-control').css('text-align','right').css('border','2px solid #6DC7BE').css('color','#6DC7BE').val(data.detalles[i].detalle.producido_calculado_relevado).hide();
      }else {
          var producidoCalculado = $('<input>').addClass('producidoCalculado form-control').css('text-align','right').val('').hide();
      }

      var diferencia = $('<input>').addClass('diferencia form-control').css('text-align','right').val('').hide();

      var a_pedido = $('<select>').addClass('a_pedido form-control acciones_validacion').attr('data-maquina' ,data.detalles[i].detalle.id_maquina)
                                                                     .append($('<option>').val(0).text('NO'))
                                                                     .append($('<option>').val(1).text('1 día'))
                                                                     .append($('<option>').val(5).text('5 días'))
                                                                     .append($('<option>').val(10).text('10 días'))
                                                                     .append($('<option>').val(15).text('15 días'));
      var a_pedido_dos = $('<button>').addClass('btn btn-success estadisticas_no_toma acciones_validacion')
                                  .attr('type' , 'button')
                                  .val(data.detalles[i].detalle.id_maquina)
                                  .append($('<i>').addClass('fas fa-fw fa-external-link-square-alt'));
      if(estadoRelevamiento == 'Validar'){
        if(data.detalles[i].tipo_causa_no_toma != null || data.detalles[i].detalle.producido_importado==null) {

          a_pedido_dos.show();
          a_pedido.show();

        }
        else{
          a_pedido_dos.hide();
          a_pedido.hide();
        }
      }else{
        a_pedido_dos.hide();
        a_pedido.hide();
      }
      //Habilita los inputs necesarios según la fórmula
      if (estadoRelevamiento == 'Carga') {
          if (data.detalles[i].formula != null) {
            data.detalles[i].formula.cont1 == null ? cont1.prop('readonly',true) : cont1.prop('readonly',false);
            data.detalles[i].formula.cont2 == null ? cont2.prop('readonly',true) : cont2.prop('readonly',false);
            data.detalles[i].formula.cont3 == null ? cont3.prop('readonly',true) : cont3.prop('readonly',false);
            data.detalles[i].formula.cont4 == null ? cont4.prop('readonly',true) : cont4.prop('readonly',false);
            data.detalles[i].formula.cont5 == null ? cont5.prop('readonly',true) : cont5.prop('readonly',false);
            data.detalles[i].formula.cont6 == null ? cont6.prop('readonly',true) : cont6.prop('readonly',false);
            data.detalles[i].formula.cont7 == null ? cont7.prop('readonly',true) : cont7.prop('readonly',false);
            data.detalles[i].formula.cont8 == null ? cont8.prop('readonly',true) : cont8.prop('readonly',false);
          }
      }

      tabla.append(fila
      .append($('<td>').text(data.detalles[i].maquina))
      .append($('<td>').append(cont1))
      .append($('<td>').append(cont2))
      .append($('<td>').append(cont3))
      .append($('<td>').append(cont4))
      .append($('<td>').append(cont5))
      .append($('<td>').append(cont6))
      .append($('<td>').append(cont7).hide())
      .append($('<td>').append(cont8).hide())
      .append($('<td>').append(producidoCalculado).hide())
      .append($('<td>').append(producido).hide())
      .append($('<td>').append(diferencia).hide())
      .append($('<td>').css('text-align','center')
              .append($('<i>').addClass('fa').addClass('fa-times').css('color','#EF5350').hide())
              .append($('<i>').addClass('fa').addClass('fa-check').css('color','#66BB6A').hide())
              .append($('<a>')
                  .addClass('pop')
                  .attr("data-content", 'Contadores importados truncados')
                  .attr("data-placement" , "top")
                  .attr("rel","popover")
                  .attr("data-trigger" , "hover")
                  .append($('<i>').addClass('pop').addClass('fa').addClass('fa-exclamation')
                                  .css('color','#FFA726'))
              )
              .append($('<a>')
                  .addClass('pop')
                  .attr("data-content", 'No se importaron contadores')
                  .attr("data-placement" , "top")
                  .attr("rel","popover")
                  .attr("data-trigger" , "hover")
                  .append($('<i>').addClass('pop').addClass('fa').addClass('fa-question')
                                  .css('color','#42A5F5'))
              )
          )

      .append(formulaCont1)
      .append(formulaCont2)
      .append(formulaCont3)
      .append(formulaCont4)
      .append(formulaCont5)
      .append(formulaCont6)
      .append(formulaCont7)
      .append(formulaCont8)
      .append(formulaOper1)
      .append(formulaOper2)
      .append(formulaOper3)
      .append(formulaOper4)
      .append(formulaOper5)
      .append(formulaOper6)
      .append(formulaOper7)
      .append(formulaOper8)
      .append($('<td>').append(tipoNoToma))
      .append($('<td>').append(botonDenominacion).hide())
      .append($('<td>').append(a_pedido))
      .append($('<td>').append(a_pedido_dos))
      );


      if(estadoRelevamiento == 'Validar'){
          cont1.prop('readonly',true);
          cont2.prop('readonly',true);
          cont3.prop('readonly',true);
          cont4.prop('readonly',true);
          cont5.prop('readonly',true);
          cont6.prop('readonly',true);
          cont7.prop('readonly',true);
          cont8.prop('readonly',true);

          $('.producido').show();
          $('.producido').parent().show();

          $('.producidoCalculado').show();
          $('.producidoCalculado').parent().show();

          $('.diferencia').show();
          $('.diferencia').parent().show();



          var causa_notoma = '';

          if (data.detalles[i].tipo_causa_no_toma != null) {
             causa_notoma = data.tipos_causa_no_toma[parseInt(data.detalles[i].tipo_causa_no_toma) - 1].descripcion;
             console.log(data.tipos_causa_no_toma[parseInt(data.detalles[i].tipo_causa_no_toma) - 1].descripcion);
          }else {
            //AGREGAR REDIRECCION A ESTADISTICAS
          }

          var input_notoma = $('<input>').addClass('tipo_causa_no_toma form-control').val(causa_notoma);

          if (input_notoma.val() != '') {
              input_notoma.css('border','2px solid #EF5350').css('color','#EF5350');
          }

          $('#tablaValidarRelevamiento #' + data.detalles[i].detalle.id_detalle_relevamiento).find('td').find('.tipo_causa_no_toma').replaceWith(input_notoma);

          producido.prop('readonly',true);
          producidoCalculado.prop('readonly',true);
          diferencia.prop('readonly',true);
          $('.tipo_causa_no_toma').prop('readonly',true);

          botonDenominacion.parent().show();
      }
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

              var renglon_actual = $(this);

              if(renglon_actual.attr('data-medida') == 1){//si trabjo en credito
                //suma es creditos
                var denominacion = renglon_actual.attr('data-denominacion');
                var sumaxdenom = Number((suma * denominacion) );
                var producidoxcien = Number(producido);
                var diferencia = Number(sumaxdenom.toFixed(2)) - Number(producidoxcien.toFixed(2));
                // var diferencia = Math.round((suma * denominacion) * 100) / 100 - (Math.round(producido * 100) / 100);//pesos - pesos
                // diferencia = Math.round(diferencia * 100) / 100;

              }else{
                var sumatrunc = Number(suma);
                var producidoxcien = Number(producido);
                var diferencia = Number(sumatrunc.toFixed(2)) - Number(producidoxcien.toFixed(2));
                // Math.round(suma * 100) / 100 - (Math.round(producido * 100) / 100);
                // diferencia = Math.round(diferencia * 100) / 100;
              }
              console.log('acac',diferencia);
              if (diferencia == 0) {
                  renglon_actual.find('i.fa-question').hide();
                  renglon_actual.find('i.fa-times').hide();
                  renglon_actual.find('i.fa-check').show();
                  renglon_actual.find('i.fa-exclamation').hide();
                } else if(Math.abs(diferencia) > 1 && diferencia%1000000 == 0) { //El caso de que no haya diferencia ignorando la unidad del millon (en pesos)
                  renglon_actual.find('i.fa-question').hide();
                  renglon_actual.find('i.fa-times').hide();
                  renglon_actual.find('i.fa-check').hide();
                  renglon_actual.find('i.fa-exclamation').show();
                } else {
                  renglon_actual.find('i.fa-question').hide();
                  renglon_actual.find('i.fa-times').show();
                  renglon_actual.find('i.fa-check').hide();
                  renglon_actual.find('i.fa-exclamation').hide();
                }

      }
  });
}

function calculoDiferenciaValidar(tablaValidarRelevamiento, data){

    for (var i = 0; i < data.detalles.length; i++) {

      var id_detalle = data.detalles[i].detalle.id_detalle_relevamiento;
      console.log('id_detalle_relevamiento: ', id_detalle);

      var iconoPregunta = tablaValidarRelevamiento.find('#' + id_detalle + ' a i.fa-question').hide();
      var iconoCruz = tablaValidarRelevamiento.find('#' + id_detalle).find('td i.fa-times').hide();
      var iconoCheck = tablaValidarRelevamiento.find('#' + id_detalle).find('td i.fa-check').show();
      var iconoAdmiracion = tablaValidarRelevamiento.find('#' + id_detalle + ' i.fa-exclamation').hide();
      var diferencia = tablaValidarRelevamiento.find('#' + id_detalle + ' td input.diferencia');

      if(data.detalles[i].detalle.producido_calculado_relevado == null){
        diferencia.val(data.detalles[i].producido).css('border',' 2px solid rgb(239, 83, 80)').css('color','rgb(239, 83, 80)');
        iconoPregunta.hide();
        iconoCruz.show();
        iconoCheck.hide();
        iconoAdmiracion.hide();
      }
      //si no se importaron contadores muestra = ?
      if(data.detalles[i].producido == null) {
        diferencia.val(data.detalles[i].detalle.producido_calculado_relevado).css('border',' 2px solid rgb(239, 83, 80)').css('color','rgb(239, 83, 80)');
        iconoPregunta.show();
        iconoCruz.hide();
        iconoCheck.hide();
        iconoAdmiracion.hide();
      }
      //Si hay causa no toma = x
      else if(data.detalles[i].tipo_causa_no_toma != null) {
        iconoPregunta.hide();
        iconoCruz.show();
        iconoCheck.hide();

        iconoAdmiracion.hide();
      }
      //Si no, calcular la diferencia entre lo calculado y lo importado
      else {
          //SI HAY DIFERENCIA
          var resta = Number(data.detalles[i].detalle.producido_calculado_relevado - data.detalles[i].producido );
          if (Number(resta.toFixed(2)) != 0) {
            var diferenciaProducido =  math.abs(Number(resta.toFixed(2))) >= 1000000;
            var moduloDiferencia = Number(resta.toFixed(2)) % 1000000;

            console.log('MODULO DIFERENCIA', moduloDiferencia);
            console.log('DIFERENCIA', diferenciaProducido);

            if(diferenciaProducido && math.abs(moduloDiferencia) == 0){
              iconoPregunta.hide();
              iconoCruz.hide();
              iconoCheck.hide();
              iconoAdmiracion.show();
              truncadas++;
              diferencia.val(resta.toFixed(2)).css('border','2px solid #FFA726').css('color','#FFA726');
            }
            else{
              iconoPregunta.hide();
              iconoCruz.show();
              iconoCheck.hide();
              iconoAdmiracion.hide();

              diferencia.val(moduloDiferencia).css('border','2px solid #EF5350').css('color','#EF5350');
            }
          }
          else {
            iconoPregunta.hide();
            iconoCruz.hide();
            iconoCheck.show();
            iconoAdmiracion.hide();

            diferencia.val(0).css('border','2px solid #66BB6A').css('color','#66BB6A');
          }
      }
    }
}

function maquinasAPedido(){
  var id_sector = $('#modalRelevamiento #sector option:selected').val();

  var fecha = $('#fechaDate').val();

  $.get("relevamientos/obtenerCantidadMaquinasRelevamientoHoy/" + id_sector, function(cantidad){
      $('#modalRelevamiento #cantidad_maquinas').val(cantidad);
  });

  $.get("mtm_a_pedido/obtenerMtmAPedido/" + fecha + "/" + id_sector, function(data){
      console.log(data);
      var cantidad = data.cantidad;

      if (cantidad == 0){
        $('#maquinas_pedido').hide();
      }else {
        if (cantidad == 1) $('#maquinas_pedido').find('span').text('Este sector tiene ' + cantidad + ' máquina a pedido.');
        else $('#maquinas_pedido').find('span').text('Este sector tiene ' + cantidad + ' máquinas a pedido.');

        $('#maquinas_pedido').show();
      }
  });
}

function existeRelevamiento(){
  var id_sector = $('#modalRelevamiento #sector option:selected').val();
  $.get('relevamientos/existeRelevamiento/' + id_sector, function(data){
      //Se guarda un valor que indica que para el SECTOR y la FECHA ACTUAL:
          // 0: No existe relevamiento generado.
          // 1: Solamente está generado y se puede volver a generar.
          // 2: El relevamiento empezó a cargarse, entonces no se puede volver a generar.
      $('#modalRelevamiento #existeRelevamiento').val(data);
  });
}

/* Funciones de MÁQUINAS POR RELEVAMIENTO */
function maquinasPorRelevamiento() {
  var id_sector = $('#modalMaquinasPorRelevamiento #sector option:selected').val();
  console.log(id_sector);

  //Si se elige correctamente un sector se muestran los detalles
  if (typeof id_sector !== 'undefined') {
      $.get('relevamientos/obtenerCantidadMaquinasPorRelevamiento/' + id_sector, function(data){
          console.log(data);
          setCantidadMaquinas(data);
          //Mostrar detalles
          $('#modalMaquinasPorRelevamiento #detalles').show();
      });
  }
  else {
      //Ocultar detalles
      $('#modalMaquinasPorRelevamiento #detalles').hide();
  }

}

function setCantidadMaquinas(data) {
  $('#maquinas_temporales tbody tr').remove();
  $('#maquinas_temporales').hide();
  $('#maquinas_defecto').text("-");

  if (data.length != 0) {
      console.log("Hay algo");

      $.each(data, function(i, valor){
          //MÁQUINAS POR DEFECTO
          if(valor.fecha_desde == null && valor.fecha_hasta == null) {
              console.log("Defecto");
              console.log(valor);

              setCantidadMaquinasDefecto(valor);
          }
          //MÁQUINAS TEMPORALES
          else {
              console.log("Temporal");
              console.log(valor);

              SetCantidadMaquinasTemporales(valor);
          }
      });

  }
}

function setCantidadMaquinasDefecto(valor) {
  $('#maquinas_defecto').text(valor.cantidad);
}

function SetCantidadMaquinasTemporales(valor) {
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

function habilitarDTPmaquinasPorRelevamiento() {
  $('#dtpFechaDesde input').prop('readonly',false);
  $('#dtpFechaHasta input').prop('readonly',false);

  var fechaActual;

  $.get('obtenerFechaActual', function (data) {
      fechaActual = data.fechaDate;
      console.log(fechaActual);

      $('#modalMaquinasPorRelevamiento #dtpFechaDesde').datetimepicker({
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
        startDate: fechaActual,
      });

      $('#modalMaquinasPorRelevamiento #dtpFechaHasta').datetimepicker({
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
        startDate: fechaActual,
      });
  });

}

function deshabilitarDTPmaquinasPorRelevamiento() {
  $('#dtpFechaDesde input').prop('readonly',true);
  $('#dtpFechaHasta input').prop('readonly',true);

  $('#dtpFechaDesde input').val('');
  $('#dtpFechaHasta input').val('');

  $('#modalMaquinasPorRelevamiento #dtpFechaDesde').datetimepicker('remove');
  $('#modalMaquinasPorRelevamiento #dtpFechaHasta').datetimepicker('remove');
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

  habilitarDTPmaquinasPorRelevamiento();
}


/*****************PAGINACION******************/
function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaRelevamientos .activa').attr('value');
  var orden = $('#tablaRelevamientos .activa').attr('estado');
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
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaRelevamientos .activa').attr('value'),orden: $('#tablaRelevamientos .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaRelevamientos th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  formData={
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
      for (var i = 0; i < resultados.data.length; i++) {
        $('#tablaRelevamientos tbody').append(crearFilaTabla(resultados.data[i]));
      }

      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      mostrarIconosPorPermisos();
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});
//fila lista principal de relevamientos
function crearFilaTabla(relevamiento){

  var subrelevamiento;
  relevamiento.subrelevamiento != null ? subrelevamiento = relevamiento.subrelevamiento : subrelevamiento = '';
  var fila = $(document.createElement('tr'));
  fila.attr('id', relevamiento.id_relevamiento)
      .append($('<td>').addClass('col-xs-2')
          .text((convertirDate(relevamiento.fecha)))
      )
      .append($('<td>').addClass('col-xs-2')
          .text(relevamiento.casino)
      )
      .append($('<td>').addClass('col-xs-2')
          .text(relevamiento.sector)
      )
      .append($('<td>').addClass('col-xs-1')
          .text(subrelevamiento)
      )
      .append($('<td>').addClass('col-xs-2')
          .append($('<i>').addClass('iconoEstadoRelevamiento fas fa-fw fa-dot-circle'))
          .append($('<span>').text(relevamiento.estado))
      )
      .append($('<td>').addClass('col-xs-3')
          .append($('<button>').addClass('btn btn-info planilla').attr('type','button').val(relevamiento.id_relevamiento)
              .attr({'data-toggle':'tooltip','data-placement':'top','title':'VER PLANILLA','data-delay':'{"show":"300", "hide":"100"}'})
              .append($('<i>').addClass('far').addClass('fa-fw').addClass('fa-file-alt'))
          )
          .append($('<span>').text(' '))
          .append($('<button>').addClass('btn btn-warning carga').attr('type','button').val(relevamiento.id_relevamiento)
              .attr({'data-toggle':'tooltip','trigger':'hover','data-placement':'top','title':'CARGAR RELEVAMIENTO'})
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-upload'))
          )
          .append($('<span>').text(' '))
          .append($('<button>').addClass('btn btn-success validar').attr('type','button').val(relevamiento.id_relevamiento)
              .attr({'data-toggle':'tooltip','data-placement':'top','title':'VISAR RELEVAMIENTO','data-delay':'{"show":"300", "hide":"100"}'})
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-check'))
          )
          .append($('<span>').text(' '))
          .append($('<button>').addClass('btn btn-success verDetalle').attr('type','button').val(relevamiento.id_relevamiento)
              .attr({'data-toggle':'tooltip','data-placement':'top','title':'VER RELEVAMIENTO','data-delay':'{"show":"300", "hide":"100"}'})
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-search-plus'))
          )
          .append($('<span>').text(' '))
          .append($('<button>').addClass('btn btn-info imprimir').attr('type','button').val(relevamiento.id_relevamiento)
              .attr({'data-toggle':'tooltip','data-placement':'top','title':'IMPRIMIR PLANILLA','data-delay':'{"show":"300", "hide":"100"}'})
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-print'))
          )
          .append($('<span>').text(' '))
          .append($('<button>').addClass('btn btn-success validado').attr('type','button').val(relevamiento.id_relevamiento)
              .attr({'data-toggle':'tooltip','data-placement':'top','title':'IMPRIMIR VISADO','data-delay':'{"show":"300", "hide":"100"}'})
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-bookmark'))
          )
      )

      var icono_planilla = fila.find('.planilla');
      var icono_carga = fila.find('.carga');
      var icono_validacion = fila.find('.validar');
      var icono_impirmir = fila.find('.imprimir');
      var icono_validado = fila.find('.validado');
      var icono_verDetalle = fila.find('.verDetalle');


    //Qué ESTADO e ICONOS mostrar
    switch (relevamiento.estado) {
      case 'Generado':
          fila.find('.iconoEstadoRelevamiento').addClass('faGenerado');
          icono_planilla.show();
          icono_carga.show();
          icono_validacion.hide();
          icono_impirmir.hide();
          icono_validado.hide();
          icono_verDetalle.hide();

          break;
      case 'Cargando':
          fila.find('.iconoEstadoRelevamiento').addClass('faCargando');
          icono_planilla.hide();
          icono_carga.show();
          icono_validacion.hide();
          icono_impirmir.show();
          icono_validado.hide();
          icono_verDetalle.hide();
          break;
      case 'Finalizado':
          fila.find('.iconoEstadoRelevamiento').addClass('faFinalizado');

          icono_validacion.show();
          icono_impirmir.show();
          icono_carga.hide();
          icono_planilla.hide();
          icono_validado.hide();
          icono_verDetalle.hide();
          break;
      case 'Visado':
          fila.find('.iconoEstadoRelevamiento').addClass('faVisado');

          icono_impirmir.show();
          icono_validacion.hide();
          icono_carga.hide();
          icono_planilla.hide();
          icono_validado.hide();
          icono_verDetalle.show();
          break;
      case 'Rel. Visado':
            fila.find('.iconoEstadoRelevamiento').addClass('faValidado');

            icono_impirmir.show();
            icono_validacion.hide();
            icono_carga.hide();
            icono_planilla.hide();
            icono_validado.show();
            icono_verDetalle.show();
            break;
    }

    return fila;
}

//Se usa para mostrar los iconos según los permisos del usuario
function mostrarIconosPorPermisos(){
    var formData = {
        permisos : ["relevamiento_cargar","relevamiento_validar"],
    }

    $.ajax({
      type: 'GET',
      url: 'usuarios/usuarioTienePermisos',
      data: formData,
      dataType: 'json',
      success: function(data) {
        console.log(data.relevamiento_cargar);
        console.log(data.relevamiento_validar);
        //Para los iconos que no hay permisos: OCULTARLOS!
        if (!data.relevamiento_cargar) $('.carga').hide();
        if (!data.relevamiento_validar) $('.validar').hide();

        // return data;
      },
      error: function(error) {
          console.log(error);
      },
    });
}

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#buscadorCasino').on('change',function(){
  var id_casino = $('option:selected' , this).val();
  $('#buscadorSector').empty();
  if(id_casino==0){
    $('#buscadorSector').append($('<option>')
        .val(0)
        .text('-Todos los sectores-')
      )
  }else{
      $.get('http://' + window.location.host + "/sectores/obtenerSectoresPorCasino/" + id_casino, function(data){

          $('#buscadorSector').append($('<option>')
            .val(0)
            .text('-Todos los sectores-')
          )

        for (var i = 0; i < data.sectores.length; i++) {
              $('#buscadorSector').append($('<option>')
                  .val(data.sectores[i].id_sector)
                  .text(data.sectores[i].descripcion)
              )
        }
      });
  }
});
