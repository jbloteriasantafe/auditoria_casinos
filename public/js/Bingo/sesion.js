$(document).ready(function(){

  $('#barraMenu').attr('aria-expanded','true');
  // $('#maquinas').removeClass();
  // $('#maquinas').addClass('subMenu1 collapse in');
  $('#bingoMenu').removeClass();
  $('#bingoMenu').addClass('subMenu2 collapse in');

  $('#bingoMenu').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Sesiones Bingo');
  // $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcBingo').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcBingo').addClass('opcionesSeleccionado');

  $('#btn-buscar').trigger('click');

});

//Generar planilla sesion
$(document).on('click','#btn-planilla-sesion',function(){

        window.open('bingo/generarPlanillaSesion');
});
//Generar planilla relevamiento
$(document).on('click','#btn-planilla-relevamiento',function(){

        window.open('bingo/generarPlanillaRelevamiento');
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
$('#btn-minimizarMaquinas').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//si presiono enter y el modal esta abierto se manda el formulario
$(document).on("keypress" , function(e){
  if(e.which == 13 && $('#modalFormula').is(':visible')) {
    e.preventDefault();
    $('#btn-guardar').click();
  }
})
//enter en buscador
$('contenedorFiltros input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#').click();
  }
})

$('#columna input').on('focusout' , function(){
  if ($(this).val() == ''){
    mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
  }
});

$('#columna input').focusin(function(){
  $(this).removeClass('alerta');

});

//Agregar nueva fila -> valor carton - serie inicial - carton incial
$('#btn-agregarTermino').click(function(){

  $('#columna')
      .append($('<div>')
          .addClass('row')
          .addClass('terminoFormula')
          .css('margin-bottom','15px')
          .attr('id','terminoFormula')
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','valor_carton')
                  .attr('type','text')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','serie_inicial')
                  .attr('type','text')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','carton_inicial')
                  .attr('type','text')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-xs-3')
              .css('padding-right','0px')
              .append($('<button>')
                  .addClass('borrarTermino')
                  .addClass('borrarFila')
                  .addClass('btn')
                  .addClass('btn-danger')
                  .css('margin-top','6px')
                  .attr('type','button')
                  .append($('<i>')
                      .addClass('fa')
                      .addClass('fa-trash')
                  )
              )
          )


      )

});

//Agregar nueva fila -> valor carton - serie final - carton final
$('#btn-agregarTerminoFinal').click(function(){
  $('#columna2')
      .append($('<div>')
          .addClass('row')
          .addClass('terminoCierreSesion')
          .css('margin-bottom','15px')
          .attr('id','terminoCierreSesion')
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','valor_carton_f')
                  .attr('type','text')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','serie_final')
                  .attr('type','text')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','carton_final')
                  .attr('type','text')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-xs-3')
              .css('padding-right','0px')
              .append($('<button>')
                  .addClass('borrarTerminoFinal')
                  .addClass('borrarFila')
                  .addClass('btn')
                  .addClass('btn-danger')
                  .css('margin-top','6px')
                  .attr('type','button')
                  .append($('<i>')
                      .addClass('fa')
                      .addClass('fa-trash')
                  )
              )
          )


      )

});

//Agregar nueva fila -> nombre del premio - nro carton ganador
$('#btn-agregarTerminoRelevamiento').click(function(){
  $('#columnaRelevamiento')
      .append($('<div>')
          .addClass('row')
          .addClass('terminoRelevamiento')
          .css('margin-bottom','15px')
          .attr('id','terminoRelevamiento')
          .append($('<div>')
              .addClass('col-lg-4')
              .append($('<select>')
                .addClass('form-control')
                  .attr('id','nombre_premio')
                  .append($('<option>')
                      .attr('value','')
                      .attr('selected','')
                      .append('Seleccione Valor')
                  )
                  .append($('<option>')
                      .attr('value','1')
                      .append('Línea')
                  )
                  .append($('<option>')
                      .attr('value','1')
                      .append('Bingo')
                  )
                  .append($('<option>')
                      .attr('value','1')
                      .append('Línea Acumulada')
                  )
                  .append($('<option>')
                      .attr('value','1')
                      .append('Pozo Acumulado')
                  )
                  .append($('<option>')
                      .attr('value','1')
                      .append('Bingo Especial')
                  )
                  .append($('<option>')
                      .attr('value','1')
                      .append('Bingo sale o sale')
                  )
              )
          )
          .append($('<div>')
              .addClass('col-lg-4')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','carton_ganador')
                  .attr('type','text')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-xs-4')
              .css('padding-right','0px')
              .append($('<button>')
                  .addClass('borrarTerminoRelevamiento')
                  .addClass('borrarFila')
                  .addClass('btn')
                  .addClass('btn-danger')
                  .css('margin-top','6px')
                  .attr('type','button')
                  .append($('<i>')
                      .addClass('fa')
                      .addClass('fa-trash')
                  )
              )
          )


      )

});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| FÓRMULAS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para iniciar una nueva sesion
$('#btn-nuevo').click(function(e){
  $('#mensajeExito').hide();
  $('#btn-agregarTermino').show();
  e.preventDefault();
  $('#casino_nueva').removeAttr('disabled','disabled');
  $('#btn-guardar').val("nuevo");
  $('#frmFormula').trigger('reset');
  $('.terminoFormula').remove();
  $('#btn-guardar').removeClass();
  $('#btn-guardar').addClass('btn btn-successAceptar');
  $('.modal-title').text('| NUEVA SESIÓN');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

  document.querySelector("#fechaInicioNueva").valueAsDate = new Date();
  let h =new Date();
  let hora = h.getHours() + ":" + h.getMinutes() + ":" + h.getSeconds();
  document.querySelector("#horaInicioNueva").value = hora;
  //Calulo la fecha actual
  var fecha_actual = fechaHoy();
  var fecha_str = $('#cuerpoTabla tr').last().children().text();
  $('#modalFormula').modal('show');

});

//Mostrar modal con los datos de las sesion cargados
$(document).on('click','.modificar',function(){
    $('#mensajeExito').hide();
    $('#btn-agregarTermino').hide();
    $('#frmFormula').trigger('reset');
    $('.terminoFormula').remove();
    $('.modal-title').text('| MODIFICAR SESIÓN');
    $('.modal-header').attr('style','font-family: Roboto-Black; background: #ff9d2d; color: #fff;');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-warningModificar');
    $('#casino_nueva').attr('disabled','disabled');
    var id_sesion = $(this).val();

    $.get("bingo/obtenerSesion/" + id_sesion, function(data){
        console.log(data);

        $('#id_sesion').val(id_sesion);//campo oculto
        $('#btn-guardar').val("modificar");
        $('#id_sesion').val(id_sesion);
        $('#modalFormula').modal('show');
        $('#fechaInicioNueva').val(data.sesion.fecha_inicio);
        $('#horaInicioNueva').val(data.sesion.hora_inicio);
        $('#casino_nueva').val(data.sesion.id_casino);
        $('#pozo_dotacion_inicial').val(data.sesion.pozo_dotacion_inicial);
        $('#pozo_extra_inicial').val(data.sesion.pozo_extra_inicial);

        $('#valor_carton').val(data.detalles[0].valor_carton);
        $('#serie_inicial').val(data.detalles[0].serie_inicio);
        $('#carton_inicial').val(data.detalles[0].carton_inicio);

        var cantidad = data.detalles.length - 1; //cantidad de detalles -1 que ya se utilizo
         for (var i = 0; i < cantidad; i++){
           cargarDetallesInicioSesion(data.detalles[i+1]);
         }

      });
      $('.terminoFormula').remove();
});

$('.operador').keydown(function(e){
  console.log($(this).val().length);
  if(e.which!=107 && e.which!=109 && e.which!=8)
    e.preventDefault();
  else if($(this).val().length > 0 && e.which!=8){
      e.preventDefault();
  }
})

//borrar fila -> valor carton - serie inicial - carton incial
$(document).on('click','.borrarTermino',function(){

  $(this).parent().parent().remove();

  var i=$('#columna #terminoFormula').length;

  $('#columna #terminoFormula').last().find('#valor_carton').val('');
  $('#columna #terminoFormula').last().find('#serie_inicial').val('');
  $('#columna #terminoFormula').last().find('#carton_inicial').val('');

});

//borrar fila -> valor carton_f - serie inicial - carton incial
$(document).on('click','.borrarTerminoFinal',function(){

  $(this).parent().parent().remove();

  var i=$('#columna2 #terminoCierreSesion').length;

  $('#columna2 #terminoCierreSesion').last().find('#valor_carton_f').val('');
  $('#columna2 #terminoCierreSesion').last().find('#serie_inicial').val('');
  $('#columna2 #terminoCierreSesion').last().find('#carton_inicial').val('');

});

//borrar fila -> valor carton - serie final - carton final
$(document).on('click','.borrarTerminoRelevamiento',function(){

  $(this).parent().parent().remove();

  var i=$('#columnaRelevamiento #terminoRelevamiento').length;

  $('#columnaRelevamiento #terminoRelevamiento').last().find('#nombre_premio').val('');
  $('#columnaRelevamiento #terminoRelevamiento').last().find('#carton_ganador').val('');

});

//Modal de eliminar una sesión
$(document).on('click','.eliminar',function(){
    var id = $(this).val();
    console.log(id);
    cantidadPartidas(id); //cargo la cantidad de partidas y luego si es necesario, muestra el mensaje
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
    $('#btn-eliminarSesion').val(id);
    $('#modalEliminar').modal('show');
    $('#mensajeEliminar').text('¿Seguro que desea eliminar la sesión del día "' + $(this).parent().parent().find('td:first').text()+'"?');

});

//Elimina una sesión
$('#btn-eliminarSesion').click(function (e) {
    var id = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "bingo/eliminarSesion/" + id,
        success: function (data) {

          //Remueve de la tabla
          $('#cuerpoTabla #'+ id).remove();
          $("#tablaResultados").trigger("update");

          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

//envio de datos a servidor (guardar / modificar)
$('#btn-guardar').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var detalles = [];

    $('#columna #terminoFormula').each(function(){
        var termino = {
          valor_carton: $(this).find('#valor_carton').val(),
          serie_inicial: $(this).find('#serie_inicial').val(),
          carton_inicial: $(this).find('#carton_inicial').val(),
        }
        detalles.push(termino);
    });


    var formData = {
      pozo_dotacion_inicial: $('#pozo_dotacion_inicial').val(),
      pozo_extra_inicial: $('#pozo_extra_inicial').val(),
      fecha_inicio: $('#fechaInicioNueva').val(),
      hora_inicio: $('#horaInicioNueva').val(),
      casino: $('#casino_nueva').val(),
      detalles:detalles,
    }

    var state = $('#btn-guardar').val();
    var type = "POST";

    var url; //url de destino, dependiendo si se esta creando o modificando una sesión

    if(state == 'nuevo'){
      url =  'bingo/guardarSesion';
    }else{
      url = 'bingo/modificarSesion';
      //se agrega id_sesion si se esta modificando
      var formData = {
        pozo_dotacion_inicial: $('#pozo_dotacion_inicial').val(),
        pozo_extra_inicial: $('#pozo_extra_inicial').val(),
        fecha_inicio: $('#fechaInicioNueva').val(),
        hora_inicio: $('#horaInicioNueva').val(),
        casino: $('#casino_nueva').val(),
        detalles:detalles,
        id_sesion: $('#id_sesion').val(),
      }
    }

    console.log(formData);

    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            console.log(data);
            if (state == "nuevo"){//si se esta creando guarda en tabla
              $('#mensajeExito P').text('La sesión fue ABIERTA correctamente.');
              $('#mensajeExito div').css('background-color','#4DB6AC');
              $('#cuerpoTabla').append(generarFilaTabla(data.sesion,data.estado,data.casino,data.nombre_inicio,data.nombre_fin,'guardar'));
            }else{ //Si está modificando
              $('#mensajeExito p').text('La sesión fue MODIFICADA correctamente.');
              $('#mensajeExito div').css('background-color','#FFB74D');
            }
            $('#frmFormula').trigger("reset");
            $('#modalFormula').modal('hide');
            //Mostrar éxito
            $('#mensajeExito').show();
        },
        error: function (data) {
            var response = JSON.parse(data.responseText);

            $('#columna .row').each(function(index,value){

              if(typeof response.pozo_dotacion_inicial !== 'undefined'){
                  mostrarErrorValidacion($('#pozo_dotacion_inicial'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response.pozo_extra_inicial !== 'undefined'){
                    mostrarErrorValidacion($('#pozo_extra_inicial'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response.fecha_inicio !== 'undefined'){
                      mostrarErrorValidacion($('#fechaInicioNueva'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response.hora_inicio !== 'undefined'){
                        mostrarErrorValidacion($('#horaInicioNueva'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response['detalles.' + index + '.valor_carton'] !== 'undefined'){
                      mostrarErrorValidacion($('#valor_carton'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response['detalles.' + index + '.serie_inicial'] !== 'undefined'){
                        mostrarErrorValidacion($('#serie_inicial'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response['detalles.' + index + '.carton_inicial'] !== 'undefined'){
                          mostrarErrorValidacion($('#carton_inicial'),'El campo no puede estar en blanco.' ,true);
                }

            })
            if(response.sesion_cargada != null){
              avisoSesionAbierta()
            }
        }
    });
});

//envio de datos a servidor cierre sesion
$('#btn-guardar-cierre').click(function (e) {
  e.preventDefault();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var detalles = []; //array para guardar los detalles de la sesión
    var id_sesion = $('#id_sesion').val();

    //guarda los detalles de la sesión en el array por termino
    $('#columna2 #terminoCierreSesion').each(function(){
        var termino = {
          valor_carton_f: $(this).find('#valor_carton_f').val(),
          serie_final: $(this).find('#serie_final').val(),
          carton_final: $(this).find('#carton_final').val(),
        }
        detalles.push(termino);
    });

    //datos a enviar
    var formData = {
      id_sesion: id_sesion,
      pozo_dotacion_final: $('#pozo_dotacion_final').val(),
      pozo_extra_final: $('#pozo_extra_final').val(),
      fecha_fin: $('#fechaCierreSesion').val(),
      hora_fin: $('#horaCierreSesion').val(),
      detalles:detalles,
    }
    var state = $('#btn-guardar-cierre').val();
    var url;
    if(state == 'crear'){
      url = 'bingo/guardarCierreSesion';
    }else{
      url = 'bingo/modificarCierreSesion';
    }
    var cantidad_detalles = $('#cantidad_detalles').val(); //cantidad de detalles con los que cuenta la sesión abierta
    var length_detalles = detalles.length; //cantidad de detalles enviados en el form de cerrar sesión

    //si la cantidad de detalles a enviar es igual a la que tiene, envia los datos
    if (cantidad_detalles == length_detalles) {
            var type = "POST";
            console.log(formData);
            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                dataType: 'json',
                success: function (data) {
                    console.log(data);
                    if(state == 'crear'){
                      $('#mensajeExito p').text('La sesión fue CERRADA correctamente.');
                      $('#mensajeExito div').css('background-color','#4DB6AC');
                    }else{
                      $('#mensajeExito p').text('La sesión fue MODIFICADA correctamente.');
                      $('#mensajeExito div').css('background-color','#FFB74D');
                    }
                    $('#cuerpoTabla #' +data.sesion.id_sesion ).replaceWith(generarFilaTabla(data.sesion,data.estado,data.casino,data.nombre_inicio,data.nombre_fin,'guardar'))

                    $('#frmCierreSesion').trigger("reset");
                    $('#modalCierreSesion').modal('hide');
                    //Mostrar éxito
                    $('#mensajeExito').show();
                },
                error: function (data) {
                    var response = JSON.parse(data.responseText);

                    $('#columna .row').each(function(index,value){

                      if(typeof response.pozo_dotacion_final !== 'undefined'){
                          mostrarErrorValidacion($('#pozo_dotacion_final'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.pozo_extra_final !== 'undefined'){
                            mostrarErrorValidacion($('#pozo_extra_final'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.fecha_fin !== 'undefined'){
                              mostrarErrorValidacion($('#fechaCierreSesion'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.hora_fin !== 'undefined'){
                                mostrarErrorValidacion($('#horaCierreSesion'),'El campo no puede estar en blanco.' ,true);
                          }
                      if(typeof response['detalles.' + index + '.valor_carton_f'] !== 'undefined'){
                              mostrarErrorValidacion($('#valor_carton_f'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response['detalles.' + index + '.serie_final'] !== 'undefined'){
                                mostrarErrorValidacion($('#serie_final'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response['detalles.' + index + '.carton_final'] !== 'undefined'){
                                  mostrarErrorValidacion($('#carton_final'),'El campo no puede estar en blanco.' ,true);
                       }
                    })

                }
            });
          }
          else
          {
            //muestra mensaje de error por tener cantidad de detalles distintas en el inicio y cierre de sesión
            errorCantidad();
          }
});

//envio de datos a servidor relevamiento
$('#btn-guardar-relevamiento').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var detalles = []; //array para guardar los detalles del relevamiento
    var id_sesion = $('#id_sesion').val();

    //guarda los detalles de la sesión en el array por termino
    $('#columnaRelevamiento #terminoRelevamiento').each(function(){
        var termino = {
          nombre_premio: $(this).find('#nombre_premio').val(),
          carton_ganador: $(this).find('#carton_ganador').val(),
        }
        detalles.push(termino);
    });

    //datos a enviar
    var formData = {
      id_sesion: id_sesion,
      nro_partida: $('#nro_partida').val(),
      hora_jugada: $('#hora_jugada').val(),
      valor_carton: $('#valor_carton_rel').val(),
      serie_inicio: $('#serie_inicio').val(),
      carton_inicio_i: $('#carton_inicio_i').val(),
      carton_fin_i: $('#carton_fin_i').val(),
      serie_fin: $('#serie_fin').val(),
      carton_inicio_f: $('#carton_inicio_f').val(),
      carton_fin_f: $('#carton_fin_f').val(),
      cartones_vendidos: $('#cartones_vendidos').val(),
      premio_linea: $('#premio_linea').val(),
      premio_bingo: $('#premio_bingo').val(),
      maxi_linea: $('#maxi_linea').val(),
      maxi_bingo: $('#maxi_bingo').val(),
      pos_bola_linea: $('#pos_bola_linea').val(),
      pos_bola_bingo: $('#pos_bola_bingo').val(),
      detalles:detalles,
    }

            var type = "POST";
            var url = 'bingo/guardarRelevamiento';

            console.log(formData);
            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                dataType: 'json',
                success: function (data) {
                    console.log(data);

                    $('#mensajeExito p').text('El relevamiento fue CARGADO correctamente.');
                    $('#mensajeExito div').css('background-color','#4DB6AC');

                    $('#frmRelevamiento').trigger("reset");
                    $('#modalRelevamiento').modal('hide');
                    //Mostrar éxito
                    $('#mensajeExito').show();
                },
                error: function (data) {
                    var response = JSON.parse(data.responseText);

                    $('#columna .row').each(function(index,value){

                       if(typeof response.relevamiento_cerrado !== 'undefined'){
                           errorSesionCerrada();
                        }
                      if(typeof response.nro_partida !== 'undefined'){
                          mostrarErrorValidacion($('#nro_partida'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.hora_jugada !== 'undefined'){
                            mostrarErrorValidacion($('#hora_jugada'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.valor_carton !== 'undefined'){
                              mostrarErrorValidacion($('#valor_carton'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.valor_carton !== 'undefined'){
                              mostrarErrorValidacion($('#valor_carton'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.serie_inicio !== 'undefined'){
                              mostrarErrorValidacion($('#serie_inicio'),'El campo no puede estar en blanco.' ,true);
                        }
                        if(typeof response.serie_fin !== 'undefined'){
                                mostrarErrorValidacion($('#serie_fin'),'El campo no puede estar en blanco.' ,true);
                          }
                      if(typeof response.carton_inicio_i !== 'undefined'){
                              mostrarErrorValidacion($('#carton_inicio_i'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.carton_fin_i !== 'undefined'){
                                mostrarErrorValidacion($('#carton_fin_i'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.carton_inicio_f !== 'undeffned'){
                                mostrarErrorValidacion($('#carton_inicio_f'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.carton_fin_f !== 'undefined'){
                                  mostrarErrorValidacion($('#carton_fin_f'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.cartones_vendidos !== 'undefined'){
                                    mostrarErrorValidacion($('#cartones_vendidos'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.premio_linea !== 'undefined'){
                                      mostrarErrorValidacion($('#premio_linea'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.premio_bingo !== 'undefined'){
                                        mostrarErrorValidacion($('#premio_bingo'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.maxi_bingo !== 'undefined'){
                                        mostrarErrorValidacion($('#maxi_bingo'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.maxi_linea !== 'undefined'){
                                          mostrarErrorValidacion($('#maxi_linea'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.pos_bola_bingo !== 'undefined'){
                                          mostrarErrorValidacion($('#pos_bola_bingo'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response.pos_bola_linea !== 'undefined'){
                                            mostrarErrorValidacion($('#pos_bola_linea'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response['detalles.' + index + '.nombre_premio'] !== 'undefined'){
                              mostrarErrorValidacion($('#nombre_premio'),'El campo no puede estar en blanco.' ,true);
                        }
                      if(typeof response['detalles.' + index + '.carton_ganador'] !== 'undefined'){
                                mostrarErrorValidacion($('#carton_ganador'),'El campo no puede estar en blanco.' ,true);
                        }

                    })

                }
            });

});

//envio reabrir sesión
$('#btn-abrirSesion').click(function (e) {
    var id = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "POST",
        url: "bingo/reAbrirSesion/" + id,
        success: function (data) {

          $('#mensajeExito p').text('La sesión fue ABIERTA correctamente.');
          $('#mensajeExito div').css('background-color','#4DB6AC');
          $('#cuerpoTabla #' +data.sesion.id_sesion ).replaceWith(generarFilaTabla(data.sesion,data.estado,data.casino,data.nombre_inicio,data.nombre_fin,'guardar'));
          $('#modalAbrirSesion').modal('hide');
          //Mostrar éxito
          $('#mensajeExito').show();
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

//busqueda
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
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  var formData = {
    fecha: $('#buscadorFecha').val(),
    estado: $('#buscadorEstado').val(),
    casino: $('#buscadorCasino').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: 'GET',
      url: 'bingo/buscarSesion',
      data: formData,
      dataType: 'json',
      success: function(resultados){
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTabla tr').remove();
        for (var i = 0; i < resultados.data.length; i++){
          $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i],'','','','','buscar'));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function(data){
        console.log('Error:', data);
      }
    });
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
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
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

//Mostral modal para cierre de sesión y reabrir sesión
$(document).on('click' , '.cerrarSesion' , function() {
  $('#btn-agregarTerminoFinal').hide();
  $('#borrarTerminoFinal').hide();
  console.log("cerrar/abrir");
    $('#id_sesion').val($(this).val());
    var id_sesion = $('#id_sesion').val();

    var estado = $('.estado-'+ id_sesion).text(); // obtengo el estado desde el texto del td de la sesión
    //Si la sesión está cerrada, llama al modal para confirmar.
    $('#btn-guardar-cierre').removeClass();
    if( estado == 'CERRADA'){
      reAbrirSesion(id_sesion);
    }else{
      cargarDatosCierreSesion(id_sesion); // si se reabrio la sesion, cargo los datos y sino, solo el casino al que pertenece
      cantidadDetalles(id_sesion); //cargo la cantidad de detalles
      $('.terminoCierreSesion').remove();
      $('#modalCierreSesion .modal-title').text('| CERRAR SESIÓN');
      $('#modalCierreSesion .modal-header').attr('style','font-family: Roboto-Black; background-color: #46b8da; color: #fff');
      $('#frmCierreSesion').trigger('reset');
      $('#btn-guardar-cierre').removeClass();

      document.querySelector("#fechaCierreSesion").valueAsDate = new Date();
      let h =new Date();
      let hora = h.getHours() + ":" + h.getMinutes() + ":" + h.getSeconds();
      document.querySelector("#horaCierreSesion").value = hora;

      $('#btn-guardar-cierre').addClass('btn btn-informacion');
      $('#modalCierreSesion').modal('show');
    }
})

//Mostral modal para carga de relevamientos
$(document).on('click' , '.relevamientos' , function() {
    console.log("relevamientos");
    $('#id_sesion').val($(this).val());
    var id_sesion = $('#id_sesion').val();
    $('.terminoRelevamiento').remove();
    $('#modalRelevamiento .modal-title').text('| CARGAR RELEVAMIENTO');
    $('#modalRelevamiento .modal-header').attr('style','font-family: Roboto-Black; background-color: #46b8da; color: #fff');
    $('#frmRelevamiento').trigger('reset');
    $('#btn-relevamiento').removeClass();
    $('#btn-relevamiento').addClass('btn btn-informacion');
    $('#modalRelevamiento').modal('show');

})

//Mostral modal con detalles y relevamientos de la sesión
$(document).on('click' , '.detallesRel' , function() {
    console.log("detallesRel");
    $('#id_sesion').val($(this).val());
    var id_sesion = $('#id_sesion').val();


    $('#modalDetallesRel .modal-header').attr('style','font-family: Roboto-Black; background-color: #46b8da; color: #fff');

     $('#cuerpoTablaRel').remove();
     $('#cuerpoTablaHis').remove();
     $('#tablaResultadosRel').append($('<tbody>').attr('id', 'cuerpoTablaRel'));
     $('#tablaResultadosHis').append($('<tbody>').attr('id', 'cuerpoTablaHis'));

    $.get("bingo/obtenerSesion/" + id_sesion, function(data){
        console.log(data);
          $('#modalDetallesRel .modal-title').text('| DETALLES SESIÓN ' + data.sesion.fecha_inicio);
        $('#modalDetallesRel').modal('show');

        //detalles sesion
        $('#pozo_dotacion_inicial_d').val(data.sesion.pozo_dotacion_inicial).attr('readonly','readonly');
        $('#pozo_extra_inicial_d').val(data.sesion.pozo_extra_inicial).attr('readonly','readonly');

        if(data.sesion.pozo_dotacion_final == null){
          $('#pozo_dotacion_final_d').val('-').attr('readonly','readonly');
        }else{
          $('#pozo_dotacion_final_d').val(data.sesion.pozo_dotacion_final).attr('readonly','readonly');
        }

        if(data.sesion.pozo_extra_final == null){
          $('#pozo_extra_final_d').val('-').attr('readonly','readonly');
        }else{
          $('#pozo_extra_final_d').val(data.sesion.pozo_extra_final).attr('readonly','readonly');
        }
        //genera la tabla con los relevamientos cargados
        for (var i = 0; i < data.partidas.length; i++){
          $('#cuerpoTablaRel').append(generarFilaTablaRel(data.partidas[i]));
        }
        //genera la tabla con el historial de cambios
        for (var i = 0; i < data.historico.length; i++){
          $('#cuerpoTablaHis').append(generarFilaTablaHis(data.historico[i]));
        }

      });

})

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

//Genero las filas con los datos. Recibe una sesion para la busqueda y un "valor" como bandera para determinar si búsqueda o de guardado.
//Estado y casino sólo se utilizan en caso de valor='guardar'
function generarFilaTabla(sesion, estado, casino,nombre_inicio, nombre_fin, valor){

  //Variables para cargar los datos correctamente y corregir en caso de no existir el dato(sesion abierta)
    var hora_fin;
    sesion.hora_fin == null ? hora_fin = '-' : hora_fin= sesion.hora_fin;
    if(sesion.id_usuario_fin == null) hora_fin = '-';
    var pozo_dotacion_final;
    sesion.pozo_dotacion_final == null ? pozo_dotacion_final = '-' : pozo_dotacion_final = sesion.pozo_dotacion_final;
    var pozo_extra_final;
    sesion.pozo_extra_final == null ? pozo_extra_final = '-' : pozo_extra_final = sesion.pozo_extra_final;
    var estado_sesion;
    valor == 'buscar' ? estado_sesion = sesion.descripcion : estado_sesion = estado.descripcion;
    var casino;
    valor == 'buscar' ? casino = sesion.nombre : casino = casino.nombre;
    var nombre_i;
    valor == 'guardar' ? nombre_i = nombre_inicio : nombre_i = sesion.nombre_inicio;
    var nombre_f;
    valor == 'guardar' ? nombre_f = nombre_fin : nombre_f = sesion.nombre_fin;
    if (valor == 'buscar' && sesion.nombre_fin == null) nombre_f = '-';

      var fila = $(document.createElement('tr'));
          fila.attr('id', sesion.id_sesion)
            .append($('<td>')
            .addClass('col-xs-2')
                .text(sesion.fecha_inicio)
            )
            .append($('<td>')
              .addClass('col-xs-1')

              .text(sesion.hora_inicio)
            )
            .append($('<td>')
              .addClass('col-xs-1')

              .text(casino)
            )
            .append($('<td>')
              .addClass('col-xs-2')

              .text(nombre_i)
            )
            .append($('<td>')
              .addClass('col-xs-1')

              .text(hora_fin)
            )
            .append($('<td>')
              .addClass('col-xs-2')

              .text(nombre_f)
            )
            .append($('<td>')
              .addClass('col-xs-1')
              .addClass('estado-'+ sesion.id_sesion)
              .text(estado_sesion)
            )
            .append($('<td>')
              .addClass('col-xs-2')
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                    )
                    .append($('<span>').text(' MODIFICAR'))
                    .addClass('btn').addClass('btn-warning').addClass('btn-detalle').addClass('modificar')
                    .attr('value',sesion.id_sesion)
                )
                  .append($('<span>').text(' '))
                  .append($('<button>')
                      .append($('<i>')
                          .addClass('fa')
                          .addClass('fa-paperclip')
                      )
                      .addClass('btn').addClass('btn-detalle').addClass('btn-info').addClass('relevamientos')
                      .attr('value',sesion.id_sesion)
                  )
                .append($('<span>').text(' '))
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fas')
                        .addClass('fa-wrench')
                    )
                    .append($('<span>').text('CERRAR SESIÓN'))
                    .addClass('btn').addClass('btn-success').addClass('btn-cerrarSesion').addClass('cerrarSesion')
                    .attr('value',sesion.id_sesion)
                )
                .append($('<span>').text(' '))
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa').addClass('fa-fw')
                        .addClass('fa-search-plus')
                    )
                    .append($('<span>').text('DETALLES'))
                    .addClass('btn').addClass('btn-success').addClass('btn-detallesRel').addClass('detallesRel')
                    .attr('value',sesion.id_sesion)
                )
                .append($('<span>').text(' '))
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa')
                        .addClass('fa-trash-alt')
                    )
                    .append($('<span>').text(' ELIMINAR'))
                    .addClass('btn').addClass('btn-danger').addClass('btn-borrar').addClass('eliminar')
                    .attr('value',sesion.id_sesion)
                )
            )


            //ocultar botones de cargar relevamientos y modificar la sesion si la sesión se encuentra cerrada
            if( sesion.id_estado == 2){
              fila.find('.relevamientos').hide();
              fila.find('.modificar').hide();

              //ocultar boton para abrir sesión si es fizcalizador
              $.get('relevamientos/chequearRolFiscalizador', function(data){
                if(data==1){
                  fila.find('.cerrarSesion').hide();
                  fila.find('.eliminar').hide();
                }
              })
            }

      return fila;
}
//Generar fila con los datos de las partidas
function generarFilaTablaRel(partida){
  var fila = $(document.createElement('tr'));
      fila.attr('id', partida.id_partida)
        .append($('<td>')
        .addClass('col')
            .text(partida[0].num_partida)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].hora_inicio)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].serie_inicio)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].carton_inicio_i)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].carton_fin_i)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].serie_fin)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].carton_inicio_f)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].carton_fin_f)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].cartones_vendidos)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].valor_carton)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].bola_linea)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].bola_bingo)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].premio_linea)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].premio_bingo)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].pozo_dot)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[0].pozo_extra)
        )
        .append($('<td>')
          .addClass('col')
          .text(partida[1])
        )

        return fila;
}
//Generar fila con los datos de historico
function generarFilaTablaHis(historico){
  var fecha_inicio
  historico.fecha_inicio == null ? fecha_inicio = '-' : fecha_inicio = historico.fecha_inicio;
  var hora_inicio
  historico.hora_inicio == null ? hora_inicio = '-' : hora_inicio = historico.hora_inicio;
  var fecha_fin
  historico.fecha_fin == null ? fecha_fin = '-' : fecha_fin = historico.fecha_fin;

  var fila = $(document.createElement('tr'));
      fila.attr('id', historico.id_sesion_re)
        .append($('<td>')
        .addClass('col')
            .text(historico.fecha_re)
        )
        .append($('<td>')
          .addClass('col')
          .text(historico.nombre_inicio)
        )
        .append($('<td>')
          .addClass('col')
          .text(fecha_inicio)
        )
        .append($('<td>')
          .addClass('col')
          .text(hora_inicio)
        )
        .append($('<td>')
          .addClass('col')
          .text(historico.pozo_dotacion_inicial)
        )
        .append($('<td>')
          .addClass('col')
          .text(historico.pozo_extra_inicial)
        )
        .append($('<td>')
          .addClass('col')
          .text(historico.nombre_fin)
        )
        .append($('<td>')
          .addClass('col')
          .text(fecha_fin)
        )
        .append($('<td>')
          .addClass('col')
          .text(historico.hora_fin)
        )
        .append($('<td>')
          .addClass('col')
          .text(historico.pozo_dotacion_final)
        )
        .append($('<td>')
          .addClass('col')
          .text(historico.pozo_extra_final)
        )
        return fila;
}
//Carga la cantidad de detalles del inicio de sesion
function cantidadDetalles(id_sesion){
  $.ajax({
      url: "bingo/obtenerSesion/" + id_sesion,
      method: 'GET',
      type: 'JSON'
  }).done(function(data) {
      $('#cantidad_detalles').attr('value', data.detalles.length);
  });
}
//Carga la cantidad de partidas inicio de sesion
function cantidadPartidas(id_sesion){
  $.ajax({
      url: "bingo/obtenerSesion/" + id_sesion,
      method: 'GET',
      type: 'JSON'
  }).done(function(data) {
    if(data.partidas.length > 0) advertenciaEliminarSesion();
  });
}
//Mensaje de error por cantidad de detalles distintas de inicio y cierre de sesión
function errorCantidad() {
  $('.modal-title-correcta').text('ERROR: CANTIDAD DE DETALLES INVALIDA');
  $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
  $('#modalCorrecta').modal('show');
  $('#mensajeCorrecta').text('La cantidad de detalles que contiene el inicio de sesión no coinciden con los de cierre.');
}
//Mensaje de error por cantidad de detalles distintas de inicio y cierre de sesión
function advertenciaEliminarSesion() {
  $('.modal-title-correcta').text('ADVERTENCIA');
  $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
  $('#modalCorrecta').modal('show');
  $('#mensajeCorrecta').text('La sesión que está queriendo eliminar contiene relevamientos cargados.');
}
//Mensaje de error no se puede cargar relevamientos en una sesion cerrada
function errorSesionCerrada() {
  $('.modal-title-correcta').text('ERROR: LA SESIÓN SE ENCUENTRA CERRADA');
  $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
  $('#modalCorrecta').modal('show');
  $('#mensajeCorrecta').text('No es posible cargar relevamientos en una sesión cerrada.');
}
//Mensaje de aviso al querer abrir una sesión si ya se ha abierto una en el día
function avisoSesionAbierta() {
  $('.modal-title-correcta').text('ADVERTENCIA: YA SE HA ABIERTO UNA SESIÓN EL DÍA DE HOY.');
  $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
  $('#modalCorrecta').modal('show');
  $('#mensajeCorrecta').text('Ya se ha realizado la apertura de una sesión para el día de hoy.');
}
//Modal de aviso reAbrirSesion
function reAbrirSesion(id_sesion){
    $('.modal-titleAbrirSesion').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
    $('#btn-abrirSesion').val(id_sesion);
    $('#modalAbrirSesion').modal('show');
    $('#mensajeAbrirSesion').text('Esta seguro que desea reabrir la sesión?');
}
//Calculo de feecha de hoy
function fechaHoy(){
    var hoy = new Date();
        var dd = hoy.getDate();
        var mm = hoy.getMonth()+1;
        var yyyy = hoy.getFullYear();

        dd = addZero(dd);
        mm = addZero(mm);

        return yyyy+'-'+mm+'-'+dd;
}
//Función auxiliar para agregar los ceros a la fecha
function addZero(i) {
    if (i < 10) {
        i = '0' + i;
    }
    return i;
}
//Cargar los datos que contiene una sesion re abierta
function cargarDatosCierreSesion(id_sesion){
  $.get("bingo/obtenerSesion/" + id_sesion, function(data){
      if(data.sesion.pozo_dotacion_final != null){   //solo si tiene datos lleno el formulario
        $('#id_sesion').val(id_sesion);//campo oculto

        $('#pozo_dotacion_final').val(data.sesion.pozo_dotacion_final);
        $('#pozo_extra_final').val(data.sesion.pozo_extra_final);
        $('#fechaCierreSesion').val(data.sesion.fecha_fin);
        $('#horaCierreSesion').val(data.sesion.hora_fin);

        $('#valor_carton_f').val(data.detalles[0].valor_carton).attr('disabled','disabled');
        $('#serie_final').val(data.detalles[0].serie_fin);
        $('#carton_final').val(data.detalles[0].carton_fin);

        $('#btn-guardar-cierre').val("modificar");
        console.log(data);
        var cantidad = data.detalles.length - 1; //cantidad de detalles -1 que ya se utilizo
         for (var i = 0; i < cantidad; i++){
           cargarDetallesCierreSesion(data.detalles[i+1]);
         }
          } else{
          $('#btn-guardar-cierre').val("crear");
      // }
      $('#casino_cierre').val(data.sesion.id_casino);
      $('#valor_carton_f').val(data.detalles[0].valor_carton).attr('disabled','disabled');
      $('#valor_carton_f').attr('disabled','disabled');

      var cantidad = data.detalles.length - 1; //cantidad de detalles -1 que ya se utilizo
       for (var i = 0; i < cantidad; i++){
         cargarDetallesValCarton(data.detalles[i+1].valor_carton);
       }
       }
    });
}
//cargar filas detalles ciere de sesion con valores de carton
function cargarDetallesValCarton(valor_carton){
  // $('#columna2').append('<br>');
  $('#columna2')
      .append($('<div>')
          .addClass('row')
          .addClass('terminoCierreSesion')
          .css('margin-bottom','15px')
          .attr('id','terminoCierreSesion')
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','valor_carton_f')
                  .attr('disabled','disabled')
                  .attr('type','text')
                  .attr('value', valor_carton)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','serie_final')
                  .attr('type','text')
                  .attr('value', '')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','carton_final')
                  .attr('type','text')
                  .attr('value', '')
                  .addClass('form-control')
              )
          )



      )
}
//cargar filas detalles ciere de sesion al reabrir
function cargarDetallesCierreSesion(detalle){
  // $('#columna2').append('<br>');
  $('#columna2')
      .append($('<div>')
          .addClass('row')
          .addClass('terminoCierreSesion')
          .css('margin-bottom','15px')
          .attr('id','terminoCierreSesion')
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('disabled','disabled')
                  .attr('id','valor_carton_f')
                  .attr('type','text')
                  .attr('value', detalle.valor_carton)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','serie_final')
                  .attr('type','text')
                  .attr('value', detalle.serie_fin)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','carton_final')
                  .attr('type','text')
                  .attr('value', detalle.carton_fin)
                  .addClass('form-control')
              )
          )
          // .append($('<div>')
          //     .addClass('col-xs-3')
          //     .css('padding-right','0px')
          //     .append($('<button>')
          //         .addClass('borrarTerminoFinal')
          //         .addClass('borrarFila')
          //         .addClass('btn')
          //         .addClass('btn-danger')
          //         .css('margin-top','6px')
          //         .attr('type','button')
          //         .append($('<i>')
          //             .addClass('fa')
          //             .addClass('fa-trash')
          //         )
          //     )
          // )


      )
}
//cargar filas detalles inicio de sesion para editar
function cargarDetallesInicioSesion(detalle){
  $('#columna')
      .append($('<div>')
          .addClass('row')
          .addClass('terminoFormula')
          .css('margin-bottom','15px')
          .attr('id','terminoFormula')
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','valor_carton')
                  .attr('type','text')
                  .attr('value', detalle.valor_carton)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','serie_inicial')
                  .attr('type','text')
                  .attr('value', detalle.serie_inicio)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-3')
              .append($('<input>')
                  .attr('placeholder' , '')
                  .attr('id','carton_inicial')
                  .attr('type','text')
                  .attr('value', detalle.carton_inicio)
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-xs-3')
              .css('padding-right','0px')
              .append($('<button>')
                  .addClass('borrarTermino')
                  .addClass('borrarFila')
                  .addClass('btn')
                  .addClass('btn-danger')
                  .css('margin-top','6px')
                  .attr('type','button')
                  .append($('<i>')
                      .addClass('fa')
                      .addClass('fa-trash')
                  )
              )
          )


      )
}
