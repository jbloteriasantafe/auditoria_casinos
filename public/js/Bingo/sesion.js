$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Sesiones Bingo');
  
  const common_dtp = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    pickerPosition: "bottom-left",
    ignoreReadonly: true,
    endDate: '+0d',
    startView: 2,
    minView: 0,
  };
  const yyyy_mm_dd = {
    ...common_dtp,
    format: 'yyyy-mm-dd',
    minView: 2,
  };
  const hh_ii = {
    ...common_dtp,
    format: 'HH:ii',
    startView: 0,
  };
  const yyyy_mm_dd_hh_ii = {
    ...common_dtp,
    format: 'yyyy-mm-dd HH:ii',
    minuteStep: 5,
  };
  
  $('#dtpBuscadorFecha').datetimepicker(yyyy_mm_dd);
  $('#dtpFechaSesion').datetimepicker(yyyy_mm_dd);
  $('#dtpFechaCierreSesion').datetimepicker(yyyy_mm_dd);
  
  $('#dtpHoraSesion').datetimepicker(hh_ii);
  $('#dtpHoraCierreSesion').datetimepicker(hh_ii);
  $('#dtpHoraJugada').datetimepicker(hh_ii);
  
  $('#dtpFecha').datetimepicker(yyyy_mm_dd_hh_ii);
  $('#dtpFecha span.nousables').off();
  $('#fechaRelevamientoDiv').datetimepicker(yyyy_mm_dd_hh_ii);

  $('#btn-buscar').trigger('click');
  $('#modalFormula').trigger('hidden.bs.modal');
});

$('#modalFormula').on('hidden.bs.modal',function(){
  ocultarErrorValidacion($(this).find('.form-control'));
  $(this).find('.terminoFormula').remove();
  $('#frmFormula').trigger('reset');
});

$('.btn-planilla').click(function(e){
  e.preventDefault();
  window.open($(this).attr('data-url'),'_blank');
});

//Opacidad del modal al minimizar
$('.btn-minimizar').click(function(){
  const activo = $(this).data("minimizar")==true;
  $('.modal-backdrop').css('opacity',activo? '0.1' : '0.5');
  $(this).data("minimizar",!activo);
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
    $('#btn-buscar').click();
  }
})

$('#columna input').on('focusout' , function(){
  if ($(this).val() == ''){
    mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
  }
});

$(document).on('focusin','.alerta',function(){
  $(this).removeClass('alerta');
});

function modalNuevoModificar(id_sesion){
  const nuevo = id_sesion === null;//Si es nulo es nuevo, sino modifica
  $('#btn-agregarTermino').data('nuevo',nuevo);
  $('#modalFormula .modal-title').text(nuevo? '| NUEVA SESIÓN' : '| MODIFICAR SESIÓN');
  $('#modalFormula .modal-header').css('background-color',nuevo? '#6dc7be' : '#ff9d2d');
  $('#btn-guardar').removeClass().addClass('btn').addClass(nuevo? 'btn-successAceptar' : 'btn-warningModificar')
  .val(nuevo? "nuevo" : "modificar");
  if(nuevo){
    $('#casino_nueva').removeAttr('disabled','disabled');
    $('#fechaInicioNueva').removeAttr('disabled','disabled');
  } 
  else{
    $('#casino_nueva').attr('disabled','disabled');
    $('#fechaInicioNueva').attr('disabled','disabled');
  }
  $('#input-calendar').toggle(nuevo);
  $('#input-times').toggle(nuevo);
  if(nuevo){
    $('#id_sesion').val('');
    agregarTerminoNuevo(false);
    return $('#modalFormula').modal('show');
  }
  $.get("bingo/obtenerSesion/" + id_sesion, function(data){
    console.log(data);
    $('#id_sesion').val(id_sesion);//campo oculto
    $('#fechaInicioNueva').val(data.sesion.fecha_inicio);
    $('#horaInicioNueva').val(data.sesion.hora_inicio);
    $('#casino_nueva').val(data.sesion.id_casino);
    $('#pozo_dotacion_inicial').val(data.sesion.pozo_dotacion_inicial);
    $('#pozo_extra_inicial').val(data.sesion.pozo_extra_inicial);
    for (const i in data.detalles){
      agregarTerminoModificar(data.detalles[i],i != 0);//No permito borrar todas las filas porque es un parametro requerido
    }
    $('#modalFormula').modal('show');
  });
}

//Mostrar modal para iniciar una nueva sesion
$('#btn-nuevo').click(function(e){
  e.preventDefault();
  modalNuevoModificar(null);
});

//Mostrar modal con los datos de las sesion cargados
$(document).on('click','.modificar',function(){
  modalNuevoModificar($(this).val());
});

$('.operador').keydown(function(e){
  if((e.which!=107 && e.which!=109 && e.which!=8)
   ||($(this).val().length > 0 && e.which!=8)){
    e.preventDefault();
  }
})

$(document).on('click','.borrarTermino',function(){
  $(this).closest('.terminoFormula').remove();
});
$(document).on('click','.borrarTerminoRelevamiento',function(){
  $(this).closest('.terminoRelevamiento').remove();
});

//Modal de eliminar una sesión
$(document).on('click','.eliminar',function(){
  const id = $(this).val();
  cantidadPartidas(id); //cargo la cantidad de partidas y luego si es necesario, muestra el mensaje
  $('#btn-eliminarSesion').val(id);
  $('#modalEliminar').modal('show');
  $('#mensajeEliminar').text('¿Seguro que desea eliminar la sesión del día "' + $(this).parent().parent().find('td:first').text()+'"?');
});

//Modal de eliminar una partida
$(document).on('click','.borrarPartida',function(){
  $('#btn-eliminarPartida').val($(this).val());
  $('#modalEliminarPartida').modal('show');
  $('#mensajeEliminarPartida').text('¿Seguro que desea eliminar la partida número "' + $(this).parent().parent().find('td:first').text()+'"?');
});

//Elimina una sesión
$('#btn-eliminarSesion').click(function (e) {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "DELETE",
    url: "bingo/eliminarSesion/" + $(this).val(),
    success: function (data) {
      $('#btn-buscar').click();
      $('#modalEliminar').modal('hide');
    },
    error: function (data) {
      console.log(data);
      console.log('Error: ', data);
    }
  });
});

//Elimina una partida
$('#btn-eliminarPartida').click(function (e) {
  const id = $(this).val();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } })
  $.ajax({
    type: "DELETE",
    url: "bingo/eliminarPartida/" + id,
    success: function (data) {
      //Remueve de la tabla
      $('#cuerpoTablaRel #'+ id).remove();
      $("#tablaResultadosRel").trigger("update");
      $('#modalEliminarPartida').modal('hide');
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
});

//envio de datos a servidor (guardar / modificar)
$('#btn-guardar').click(function (e) {
  const detalles = $('#columna .terminoFormula').map(function(){
    return {
      valor_carton: $(this).find('#valor_carton').val(),
      serie_inicial: $(this).find('#serie_inicial').val(),
      carton_inicial: $(this).find('#carton_inicial').val(),
    };
  }).toArray();

  const formData = {
    pozo_dotacion_inicial: $('#pozo_dotacion_inicial').val(),
    pozo_extra_inicial: $('#pozo_extra_inicial').val(),
    fecha_inicio: $('#fechaInicioNueva').val(),
    hora_inicio: $('#horaInicioNueva').val(),
    casino: $('#casino_nueva').val(),
    detalles:detalles,
  }

  const state = $('#btn-guardar').val();    
  if(state != 'nuevo'){//se agrega id_sesion si se esta modificando
    formData.id_sesion = $('#id_sesion').val();
  }

  console.log(formData);
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: state == 'nuevo'? 'bingo/guardarSesion' : 'bingo/modificarSesion',
    data: formData,
    dataType: 'json',
    success: function (data) {
      console.log(data);
      $('#btn-buscar').click();
      if (state == "nuevo"){//si se esta creando guarda en tabla
        $('#mensajeExito P').text('La sesión fue ABIERTA correctamente.');
        $('#mensajeExito div').css('background-color','#4DB6AC');
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
      const response = data.responseJSON.errors;
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
      $('#columna .terminoFormula').each(function(index,value){
        if(typeof response[`detalles.${index}.valor_carton`] !== 'undefined'){
          mostrarErrorValidacion($(this).find('#valor_carton'),'El campo no puede estar en blanco.' ,true);
        }
        if(typeof response[`detalles.${index}.serie_inicial`] !== 'undefined'){
          mostrarErrorValidacion($(this).find('#serie_inicial'),'El campo no puede estar en blanco.' ,true);
        }
        if(typeof response[`detalles.${index}.carton_inicial`] !== 'undefined'){
          mostrarErrorValidacion($(this).find('#carton_inicial'),'El campo no puede estar en blanco.' ,true);
        }
      });
      if(response.sesion_cargada != null){
        modalCorrecta('ADVERTENCIA: YA SE HA ABIERTO UNA SESIÓN EL DÍA DE HOY.','Ya se ha realizado la apertura de una sesión para el día de hoy.');
      }
    }
  });
});

//envio de datos a servidor cierre sesion
$('#btn-guardar-cierre').click(function (e) {
  e.preventDefault();

  const detalles = $('#columna2 .terminoCierreSesion').map(function(){
    return  {
      valor_carton_f: $(this).find('#valor_carton_f').val(),
      serie_final:    $(this).find('#serie_final').val(),
      carton_final:   $(this).find('#carton_final').val(),
    }
  }).toArray();

  const formData = {
    id_sesion: $('#id_sesion').val(),
    pozo_dotacion_final: $('#pozo_dotacion_final').val(),
    pozo_extra_final: $('#pozo_extra_final').val(),
    fecha_fin: $('#fechaCierreSesion').val(),
    hora_fin: $('#horaCierreSesion').val(),
    detalles: detalles,
  }
  
  const state = $('#btn-guardar-cierre').val();
    //si la cantidad de detalles a enviar es igual a la que tiene, envia los datos
  if($('#cantidad_detalles').val() != detalles.length){
    return modalCorrecta('ERROR: CANTIDAD DE DETALLES INVALIDA','La cantidad de detalles que contiene el inicio de sesión no coinciden con los de cierre.');
  }
  
  console.log(formData);
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: state == 'crear'? 'bingo/guardarCierreSesion' : 'bingo/modificarCierreSesion',
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
      $('#btn-buscar').click();
      $('#frmCierreSesion').trigger("reset");
      $('#modalCierreSesion').modal('hide');
      //abre planilla cierre sesión
      window.open('bingo/generarPlanillaCierreSesion','_blank');
      //Mostrar éxito
      $('#mensajeExito').show();
    },
    error: function (data) {
      const response = data.responseJSON.errors;
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
      $('#columna2 .terminoCierreSesion').each(function(index,value){
        if(typeof response[`detalles.${index}.valor_carton_f`] !== 'undefined'){
          mostrarErrorValidacion($(this).find('#valor_carton_f'),'El campo no puede estar en blanco.' ,true);
        }
        if(typeof response[`detalles.${index}.serie_final`] !== 'undefined'){
          mostrarErrorValidacion($(this).find('#serie_final'),'El campo no puede estar en blanco.' ,true);
        }
        if(typeof response[`detalles.${index}.carton_final`] !== 'undefined'){
          mostrarErrorValidacion($(this).find('#carton_final'),'El campo no puede estar en blanco.' ,true);
        }
      });
    }
  });
});

//envio de datos a servidor relevamiento
$('#btn-guardar-relevamiento').click(function (e) {
    //guarda los detalles de la sesión en el array por termino
    const detalles = $('#columnaRelevamiento #terminoRelevamiento').map(function(){
      return {
        nombre_premio: $(this).find('#nombre_premio').val(),
        carton_ganador: $(this).find('#carton_ganador').val(),
      }
    }).toArray();

    //datos a enviar
    const formData = {
      id_sesion:         $('#id_sesion').val(),
      nro_partida:       $('#nro_partida').val(),
      hora_jugada:       $('#hora_jugada').val(),
      valor_carton:      $('#valor_carton_rel').val(),
      serie_inicio:      $('#serie_inicio').val(),
      carton_inicio_i:   $('#carton_inicio_i').val(),
      carton_fin_i:      $('#carton_fin_i').val(),
      serie_fin:         $('#serie_fin').val(),
      carton_inicio_f:   $('#carton_inicio_f').val(),
      carton_fin_f:      $('#carton_fin_f').val(),
      cartones_vendidos: $('#cartones_vendidos').val(),
      premio_linea:      $('#premio_linea').val(),
      premio_bingo:      $('#premio_bingo').val(),
      maxi_linea:        $('#maxi_linea').val(),
      maxi_bingo:        $('#maxi_bingo').val(),
      pos_bola_linea:    $('#pos_bola_linea').val(),
      pos_bola_bingo:    $('#pos_bola_bingo').val(),
      detalles: detalles,
    }

    console.log(formData);
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
    $.ajax({
      type: "POST",
      url: 'bingo/guardarRelevamiento',
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
        const response = data.responseJSON.errors;
        console.log(response);
        if(typeof response.relevamiento_cerrado !== 'undefined'){
          modalCorrecta('ERROR: LA SESIÓN SE ENCUENTRA CERRADA','No es posible cargar relevamientos en una sesión cerrada.');
        }
        if(typeof response.partida_cargada !== 'undefined'){
          mostrarErrorValidacion($('#nro_partida'),response.partida_cargada[0] ,true);
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
        if(typeof response.carton_inicio_f !== 'undefined'){
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
        $('#columnaRelevamiento #terminoRelevamiento').each(function(index,value){
          if(typeof response[`detalles.${index}.nombre_premio`] !== 'undefined'){
             mostrarErrorValidacion($(this).find('#nombre_premio'),'El campo no puede estar en blanco.' ,true);
          }
          if(typeof response[`detalles.${index}.carton_ganador`] !== 'undefined'){
            mostrarErrorValidacion($(this).find('#carton_ganador'),'El campo no puede estar en blanco.' ,true);
          }
        });
      }
    });

});

//envio reabrir sesión
$('#btn-abrirSesion').click(function (e) {
    var id = $(this).val();
    var formData = {
      motivo: $('#motivo-reapertura').val(),
    }
    console.log(formData);
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
    $.ajax({
        type: "POST",
        data: formData,
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
          var response = data.responseJSON.errors;
          if(typeof response.no_tiene_permiso !== 'undefined'){
            modalCorrecta('ERROR: NO TIENE PERMISOS','Su usuario no tiene los permisos necesarios para realizar esta acción.');
          }
          if(typeof response.motivo !== 'undefined'){
            mostrarErrorValidacion($('#motivo-reapertura'),'El campo no puede estar en blanco.' ,true);
          }
          console.log('Error: ', data);
        }
    });
});

//busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  e.preventDefault();

  //Fix error cuando librería saca los selectores
  let size = 10;
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  const formData = {
    fecha: $('#buscadorFecha').val(),
    estado: $('#buscadorEstado').val(),
    casino: $('#buscadorCasino').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  };
  
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.get('bingo/chequearRolFiscalizador', function(rolfisca){
    puede_eliminar = rolfisca != 1;//@HACK: eliminar las globales pasandolo a la vista
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
  $('#borrarTerminoFinal').hide();
  console.log("cerrar/abrir");
  const id_sesion = $(this).val();
  $('#id_sesion').val(id_sesion);
  const estado = $(`.estado-${id_sesion}`).text(); // obtengo el estado desde el texto del td de la sesión
  //Si la sesión está cerrada, llama al modal para confirmar.
  $('#btn-guardar-cierre').removeClass();
  if(estado == 'CERRADA'){
    return reAbrirSesion(id_sesion);
  }
  cargarDatosCierreSesion(id_sesion); // si se reabrio la sesion, cargo los datos y sino, solo el casino al que pertenece
  cantidadDetalles(id_sesion); //cargo la cantidad de detalles
  $('.terminoCierreSesion').remove();
  $('#frmCierreSesion').trigger('reset');
  $('#btn-guardar-cierre').removeClass();
  $('#btn-guardar-cierre').addClass('btn btn-informacion');
  $('#modalCierreSesion').modal('show');
})

//Mostral modal para carga de relevamientos
$(document).on('click' , '.relevamientos' , function() {
  console.log("relevamientos");
  const id_sesion = $(this).val();
  $('#id_sesion').val(id_sesion);
  $('.terminoRelevamiento').remove();
  $('#frmRelevamiento').trigger('reset');
  $('#btn-relevamiento').removeClass();
  $('#btn-relevamiento').addClass('btn btn-informacion');
  $('#modalRelevamiento').modal('show');
})

//Mostral modal con detalles y relevamientos de la sesión
$(document).on('click' , '.detallesRel' , function() {
  console.log("detallesRel");
  $('#terminoDatos2,#cuerpoTablaRel,#cuerpoTablaHis').remove();
  $('#terminoDetallesRel').append($('<div>').attr('id', 'terminoDatos2'));
  $('#tablaResultadosRel').append($('<tbody>').attr('id', 'cuerpoTablaRel'));
  $('#tablaResultadosHis').append($('<tbody>').attr('id', 'cuerpoTablaHis'));
  
  const id_sesion = $(this).val();
  $('#id_sesion').val(id_sesion);

  $.get("bingo/obtenerSesion/" + id_sesion, function(data){
    console.log(data);
    $('#modalDetallesRel .modal-title').text('| DETALLES SESIÓN ' + data.sesion.fecha_inicio);
    
    //detalles sesion
    $('#pozo_dotacion_inicial_d').val(data.sesion.pozo_dotacion_inicial).attr('readonly','readonly');
    $('#pozo_extra_inicial_d').val(data.sesion.pozo_extra_inicial).attr('readonly','readonly');
    function ifnull(val,dflt='-'){ return val != null? val : dflt; };//Por si tienen navegadores viejos... igual que el operador "??"
    $('#pozo_dotacion_final_d').val(ifnull(data.sesion.pozo_dotacion_final)).attr('readonly','readonly');
    $('#pozo_extra_final_d').val(ifnull(data.sesion.pozo_extra_final)).attr('readonly','readonly');
    //ocultar fila acción e icono eliminar partida si es fiscalizador
    $.get('bingo/chequearRolFiscalizador', function(data){
      $('.borrarPartida,#accionesResultadoRel').toggle(data!=1);
    })
    //genera los input con detalles de sesión
    for(const i in data.detalles){
      $('#terminoDatos2').append(generarFilaDetallesSesion(data.detalles[i]));
    }
    for(const i in data.partidas){
      $('#cuerpoTablaRel').append(generarFilaTablaRel(data.partidas[i]));
    }
    for(const i in data.historico){
      $('#cuerpoTablaHis').append(generarFilaTablaHis(data.historico[i]));
    }
    $('#modalDetallesRel').modal('show');
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

//Hacerlo estatico a la view
puede_eliminar = false;

//Genero las filas con los datos. Recibe una sesion para la busqueda y un "valor" como bandera para determinar si búsqueda o de guardado.
//Estado y casino sólo se utilizan en caso de valor='guardar'
function generarFilaTabla(sesion, estado, casino,nombre_inicio, nombre_fin, valor){
  const hora_fin = sesion.hora_fin == null  || sesion.id_usuario_fin == null? '-' : sesion.hora_fin;    
  const pozo_dotacion_final = sesion.pozo_dotacion_final == null ? '-' : sesion.pozo_dotacion_final;
  const pozo_extra_final =  sesion.pozo_extra_final == null ? '-' : sesion.pozo_extra_final;
  const estado_sesion =  valor == 'buscar' ? sesion.descripcion : estado.descripcion;
  casino = valor == 'buscar' ? sesion.nombre : casino.nombre;
  const nombre_i = valor == 'guardar' ? nombre_inicio : sesion.nombre_inicio;
  const nombre_f = valor == 'guardar' || (valor == 'buscar' && sesion.nombre_fin == null)? nombre_fin : sesion.nombre_fin;
  
  const puede_ver_relevamientos_y_modificar = sesion.id_estado != 2;

  return $('<tr>').attr('id', sesion.id_sesion)
  .append($('<td>').addClass('col-xs-2').text(sesion.fecha_inicio))
  .append($('<td>').addClass('col-xs-1').text(sesion.hora_inicio))
  .append($('<td>').addClass('col-xs-1').text(casino))
  .append($('<td>').addClass('col-xs-2').text(nombre_i))
  .append($('<td>').addClass('col-xs-1').text(hora_fin))
  .append($('<td>').addClass('col-xs-2').text(nombre_f))
  .append($('<td>').addClass('col-xs-1').addClass('estado-'+ sesion.id_sesion).text(estado_sesion))
  .append($('<td>').addClass('col-xs-2')
    .append(
      $('<button>').attr('value',sesion.id_sesion).addClass('btn btn-warning btn-detalle modificar')
      .attr('title','MODIFICAR').toggle(puede_ver_relevamientos_y_modificar)
      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt'))
    )
    .append(
      $('<button>').attr('value',sesion.id_sesion).addClass('btn btn-detalle btn-info relevamientos')
      .attr('title','RELEVAMIENTOS').toggle(puede_ver_relevamientos_y_modificar)
      .append($('<i>').addClass('fa').addClass('fa-paperclip'))
    )
    .append(
      $('<button>').attr('value',sesion.id_sesion).addClass('btn btn-success btn-cerrarSesion cerrarSesion')
      .attr('title', sesion.id_estado == 1? 'CERRAR SESIÓN' : 'ABRIR SESION')
      .toggle(sesion.id_estado == 1 || puede_eliminar)//Si esta abierto todos pueden cerrar, sino solo puede reabrir el ADM
      .append($('<i>').addClass('fas fa-wrench'))
    )
    .append(
      $('<button>').attr('value',sesion.id_sesion).addClass('btn btn-success btn-detallesRel detallesRel')
      .attr('title','DETALLES')
      .append($('<i>').addClass('fa fa-fw fa-search-plus'))
    )
    .append(
      $('<button>').attr('value',sesion.id_sesion).addClass('btn btn-danger btn-borrar eliminar')
      .attr('title','ELIMINAR').toggle(puede_eliminar)
      .append($('<i>').addClass('fa fa-trash-alt'))
    )
  );
}

//Generar fila con los datos de las partidas
function generarFilaTablaRel(partida){
  return $('<tr>').attr('id', partida[0].id_partida)
  .append($('<td>').addClass('col').text(partida[0].num_partida))
  .append($('<td>').addClass('col').text(partida[0].hora_inicio))
  .append($('<td>').addClass('col').text(partida[0].serie_inicio))
  .append($('<td>').addClass('col').text(partida[0].carton_inicio_i))
  .append($('<td>').addClass('col').text(partida[0].carton_fin_i))
  .append($('<td>').addClass('col').text(partida[0].serie_fin))
  .append($('<td>').addClass('col').text(partida[0].carton_inicio_f))
  .append($('<td>').addClass('col').text(partida[0].carton_fin_f))
  .append($('<td>').addClass('col').text(partida[0].cartones_vendidos))
  .append($('<td>').addClass('col').text(partida[0].valor_carton))
  .append($('<td>').addClass('col').text(partida[0].bola_linea))
  .append($('<td>').addClass('col').text(partida[0].bola_bingo))
  .append($('<td>').addClass('col').text(partida[0].premio_linea))
  .append($('<td>').addClass('col').text(partida[0].premio_bingo))
  .append($('<td>').addClass('col').text(partida[0].pozo_dot))
  .append($('<td>').addClass('col').text(partida[0].pozo_extra))
  .append($('<td>').addClass('col').text(partida[1]))
  .append($('<td>').addClass('col').css('padding-right','0px').append(
      $('<button>').addClass('borrarPartida btn btn-danger').css('margin-top','6px')
      .attr('type','button').attr('value',partida[0].id_partida)
      .append($('<i>').addClass('fa fa-trash'))
    )
  );
}

//Generar fila con los datos de historico
function generarFilaTablaHis(historico){
  const fecha_inicio = historico.fecha_inicio == null ? '-' : historico.fecha_inicio;
  const hora_inicio  = historico.hora_inicio == null  ? '-' : historico.hora_inicio;
  const fecha_fin    = historico.fecha_fin == null    ? '-' : historico.fecha_fin;

  return $('<tr>').attr('id', historico.id_sesion_re)
  .append($('<td>').addClass('col').text(historico.fecha_re))
  .append($('<td>').addClass('col').text(historico.nombre_inicio))
  .append($('<td>').addClass('col').text(fecha_inicio))
  .append($('<td>').addClass('col').text(hora_inicio))
  .append($('<td>').addClass('col').text(historico.pozo_dotacion_inicial))
  .append($('<td>').addClass('col').text(historico.pozo_extra_inicial))
  .append($('<td>').addClass('col').text(historico.nombre_fin))
  .append($('<td>').addClass('col').text(fecha_fin))
  .append($('<td>').addClass('col').text(historico.hora_fin))
  .append($('<td>').addClass('col').text(historico.pozo_dotacion_final))
  .append($('<td>').addClass('col').text(historico.pozo_extra_final));
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
    if(data.partidas.length > 0){
      modalCorrecta('ADVERTENCIA','La sesión que está queriendo eliminar contiene relevamientos cargados.');
    }
  });
}

function modalCorrecta(title,msj){
  $('#modalCorrecta .modal-title-correcta').text(title);
  $('#mensajeCorrecta').text(msj);
  $('#modalCorrecta').modal('show');
}
//Modal de aviso reAbrirSesion
function reAbrirSesion(id_sesion){
  $('.modal-titleAbrirSesion').text('ADVERTENCIA');
  $('#btn-abrirSesion').val(id_sesion);
  $('#modalAbrirSesion').modal('show');
  $('#frmMotivos').trigger('reset');
  $('#mensajeAbrirSesion').text('Esta seguro que desea reabrir la sesión? Por favor, ingrese el motivo.');
}

//Cargar los datos que contiene una sesion re abierta
function cargarDatosCierreSesion(id_sesion){
  $.get("bingo/obtenerSesion/" + id_sesion, function(data){
    $('#casino_cierre').val(data.sesion.id_casino);
    if(data.sesion.pozo_dotacion_final != null){   //solo si tiene datos lleno el formulario
      $('#id_sesion').val(id_sesion);//campo oculto
      $('#pozo_dotacion_final').val(data.sesion.pozo_dotacion_final);
      $('#pozo_extra_final').val(data.sesion.pozo_extra_final);
      $('#fechaCierreSesion').val(data.sesion.fecha_fin);
      $('#horaCierreSesion').val(data.sesion.hora_fin);
      $('#btn-guardar-cierre').val("modificar");
      console.log(data);
      for(const i in data.detalles){
       cargarDetallesCierreSesion(data.detalles[i]);
      }      
      $('#valor_carton_f').attr('disabled','disabled');
    }
    else{
      $('#btn-guardar-cierre').val("crear");
      for(const i in data.detalles){
        cargarDetallesCierreSesion({valor_carton: data.detalles[i].valor_carton});
      }
    }
  });
}

//genera la fila de  detalles
function generarFilaDetallesSesion(detalle){
  const div_input = function(id_input,val=''){
    return $('<div>').addClass('col-lg-2').append(
      $('<input>').attr('placeholder' , '').attr('disabled','disabled')
      .attr('id',id_input).attr('type','text')
      .addClass('form-control').val(val)
    );
  };
  return $('<div>').addClass('row').css('padding-top' ,'15px')
  .append(div_input('valor_carton_f',detalle.valor_carton))
  .append(div_input('serie_inicial_f',detalle.serie_inicio))
  .append(div_input('carton_inicial_f',detalle.carton_inicio))
  .append(div_input('serie_final_f',detalle.serie_fin))
  .append(div_input('carton_final_f',detalle.carton_fin));
}

//cargar filas detalles ciere de sesion al reabrir
function cargarDetallesCierreSesion(detalle){  
  $('#columna2').append(fila_valores(false,'terminoCierreSesion','terminoCierreSesion',detalle));
}
function agregarTerminoNuevo(boton_borrar = true){
  $('#columna').append(fila_valores(true,'terminoFormulaAgregado','terminoFormula',{},boton_borrar? 'borrarTermino' : null));
}
function agregarTerminoModificar(detalle = {},boton_borrar = true){
  $('#columna').append(fila_valores(true,'terminoFormula','terminoFormula',detalle,boton_borrar? 'borrarTermino' : null));
}
$('#btn-agregarTermino').click(function(){
  if($(this).data('nuevo')) agregarTerminoNuevo();
  else                      agregarTerminoModificar();
});

//Agregar nueva fila -> valor carton - serie inicial - carton incial
function fila_valores(inicial,id_div,clase_div,valores = {},clase_boton_borrar = null){//@TODO: Pasar a un molde estatico en la view
  const div_input = function(id_input,val=''){
    return $('<div>').addClass('col-lg-3').append(
      $('<input>').attr('placeholder' , '')
      .attr('id',id_input).attr('type','text')
      .addClass('form-control').val(val)
    );
  };

  const defecto = {valor_carton: '',serie_inicio: '',carton_inicio: '',serie_fin: '',carton_fin: ''};
  const nvalores = {...defecto,...valores};
  const div = $('<div>').addClass(`row ${clase_div}`).attr('id',id_div).css('margin-bottom','15px');
  
  if(inicial){
    div.append(div_input('valor_carton',nvalores.valor_carton))
    .append(div_input('serie_inicial',nvalores.serie_inicio))
    .append(div_input('carton_inicial',nvalores.carton_inicio));
  }
  else{
    div.append(div_input('valor_carton_f',nvalores.valor_carton))
    .append(div_input('serie_final',nvalores.serie_fin))
    .append(div_input('carton_final',nvalores.carton_fin))
    div.find('#valor_carton_f').attr('disabled','disabled');
  }
  
  if(clase_boton_borrar !== null){
    div.append(
      $('<div>').addClass('col-xs-3').css('padding-right','0px').append(
        $('<button>').addClass(`${clase_boton_borrar} borrarFila btn btn-danger`)
        .css('margin-top','6px').attr('type','button')
        .append($('<i>').addClass('fa fa-trash'))
      )
    );
  }
  return div;
}

//Agregar nueva fila -> nombre del premio - nro carton ganador
$('#btn-agregarTerminoRelevamiento').click(function(){//@TODO: Pasar a un molde estatico en la view
  const nombre_premio = $('<select>').addClass('form-control').attr('id','nombre_premio');
  const premios = ['Seleccione Valor','Línea','Bingo','Línea Acumulada','Pozo Acumulado','Bingo Especial','Bingo sale o sale'];
  for(const idx in premios){
    const op = $('<option>').append(premios[idx]);
    if(idx == 0){
      op.attr('value','').attr('selected','');
    }
    else{
      op.attr('value','1');
    }
    nombre_premio.append(op);
  }
  $('#columnaRelevamiento').append(
    $('<div>').addClass('row terminoRelevamiento').css('margin-bottom','15px')
    .attr('id','terminoRelevamiento')
    .append($('<div>').addClass('col-lg-4').append(nombre_premio))
    .append(
      $('<div>').addClass('col-lg-4').append(
        $('<input>').attr('placeholder' , '').attr('id','carton_ganador')
        .attr('type','text').addClass('form-control')
      )
    )
    .append(
      $('<div>').addClass('col-xs-4').css('padding-right','0px').append(
        $('<button>').addClass('borrarTerminoRelevamiento borrarFila btn btn-danger')
        .css('margin-top','6px').attr('type','button')
        .append($('<i>').addClass('fa fa-trash'))
      )
    )
  );
});
