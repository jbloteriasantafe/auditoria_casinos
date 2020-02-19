$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#movimientos').removeClass();
  $('#movimientos').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Intervenciones MTM');
  $('#opcIntervencionesMTM').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcIntervencionesMTM').addClass('opcionesSeleccionado');

  $('#relFecha').datetimepicker({
    todayBtn:  1,
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy, HH:ii',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 0,
    ignoreReadonly: true,
    minuteStep: 5,
    container: $('#modalCargarRelMov'),
  });

  $('#dtpFechaEv').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
    ignoreReadonly: true,
    container:$('main section'),
  });

   $('#btn-buscarEventualidadMTM').trigger('click');
});

$('#cantidad').on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#aceptarCantEv').click();
    }
});

$('#fechaRel').on('change', function (e) {
  $(this).trigger('focusin');
})

//botón grande para generar la nueva eventualidad de máquina
$(document).on('click','#btn-nueva-evmaquina',function(e){
  e.preventDefault();
  $('#tablaMTM tbody tr').remove();
  $.get('eventualidadesMTM/tiposMovIntervMTM', function(data){
    $('#tipoMov option').remove();
    data.tipos_movimientos.forEach(tm => {
      $('#modalNuevaEvMTM #tipoMov').append($('<option>').val(tm.id_tipo_movimiento).text(tm.descripcion));
    });
    $('#modalNuevaEvMTM').modal('show');
  });

  const casino = 0; //@TODO: Permitir elegir el casino.
  $('#inputMTM').generarDataList("maquinas/obtenerMTMEnCasino/" + casino, 'maquinas','id_maquina','nro_admin',1,true);
  $('#modalNuevaEvMTM').find('#btn-impr').prop('disabled',true);
});

$('#agregarMTMEv').click(function(e) {
  const id_maq = $('#inputMTM').attr('data-elemento-seleccionado');
  if (id_maq != 0) {
    $.get('http://' + window.location.host +"/maquinas/obtenerMTM/" + id_maq, function(data) {
      agregarMTMEv(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.marca, data.maquina.modelo, 1);
      $('#inputMTM').setearElementoSeleccionado(0 , "");
    });
  }
});

function agregarMTMEv(id_maquina, nro_admin) {
  let fila = $('<tr>').attr('id', id_maquina);
  let accion = $('<button>').addClass('btn btn-danger borrarMTMCargada')
                            .append($('<i>').addClass('fa fa-fw fa-trash'));

  fila.append($('<td>').text(nro_admin));
  fila.append($('<td>').append(accion));

  $('#tablaMTM tbody').append(fila);
  $('#modalNuevaEvMTM').find('#btn-impr').prop('disabled',false);
};

//botón imprimir dentro del modal
$(document).on('click','#btn-impr',function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  let mtmEv = [];
  $('#tablaMTM tbody > tr').each(function(){
    const maquina={
      id_maquina : $(this).attr('id')
    }
    mtmEv.push(maquina);
  });
  const formData = {
    id_tipo_movimiento: $('#modalNuevaEvMTM').find('#tipoMov').val(),
    maquinas: mtmEv,
    sentido: $('#sentidoMov').val()
  };

  $.ajax({
    type: 'POST',
    url: 'eventualidadesMTM/nuevaEventualidadMTM',
    data: formData,
    dataType: 'json',
    success: function (data) {
      mensajeExito({titulo: 'CARGA EXITOSA', mensajes : ['La Intervención fue creada EXITOSAMENTE']});
      $("#modalNuevaEvMTM").modal('hide');
      $('#btn-buscarEventualidadMTM').trigger('click');
      //1 si la planilla es generada desde el modal de carga,
      //y va a ser 0 si se genera desde el boton imprimir de la pag ppal
      window.open('eventualidadesMTM/imprimirEventualidadMTM/' + data + '/' + 1,'_blank');
    },
    error: function (data) {
      console.log('Error:',data);
      var response = data.responseJSON;
      let err = false;
      if(typeof response.tipo_movimiento !== 'undefined'){
        mostrarErrorValidacion($('#tipomov'),response.tipo_movimiento[0]);
        err = true;
      }
      if(typeof response.maquinas !== 'undefined'){
        mensajeError(['Debe asignar máquinas a la intervención.']);
        err = true;
      }
      if(err) $("#modalNuevaEvMTM").animate({ scrollTop: 0 }, "slow");
    }
  });
});

$(document).on('click','.borrarMTMCargada',function(e){
  $(this).parent().parent().remove();
});

//botón para cargar máquina
$(document).on('click', '.btn_cargarEvmtm', function(){
  $('#fechaRel').val("");
  $('#guardarRel').prop('disabled', true);
  $('#detallesMTM').hide();

  //BORRO LOS ERRORES
  ocultarErrorValidacion($('#fiscaToma'));
  ocultarErrorValidacion($('#fechaRel'));

  const id_log_mov = $(this).val();
  $('#guardarRel').attr('data-mov',id_log_mov);

  $.get('eventualidadesMTM/relevamientosEvMTM/' + id_log_mov, function(data){
    console.log('88',data);
    //completo el ficalizador de carga con datos que me trae el data
    if(data.fiscalizador_carga != null){
      $('#fiscaCarga').val(data.fiscalizador_carga.nombre);
      $('#fiscaCarga').prop('disabled',true);
      $('#fiscaCarga').attr('data-id',data.fiscalizador_carga.id_usuario);
    }
    else {
      $('#fiscaCarga').val('');
      $('#fiscaCarga').removeAttr('data-id');
    }
    //genero la lista para seleccionar un fiscalizador en el input correspondiente
    $('#fiscaToma').generarDataList("usuarios/buscarUsuariosPorNombreYCasino/" + data.casino.id_casino,'usuarios' ,'id_usuario','nombre',1,false);
    $('#fiscaToma').setearElementoSeleccionado(0,"");
    $('#inputTipoMov').val(data.tipo_movimiento);
    $('#inputSentido').val(data.sentido);
    cargarRelevamientos(data.maquinas,{3 : 'fa-pencil-alt'},-1,-1);
    $('#modalCargarRelMov').modal('show');
  })
});

//boton que cierra el modal, para que cierre los detalles de las mtm
$('#btn-closeCargar').click(function(e){
  $('#detallesMTM').hide();
  $('#btn-buscarEventualidadMTM').trigger('click');
});

//presiona el ojo de una máquina para cargar los detalles
$(document).on('click','.cargarMaq',function(){
  $('#fechaRel').val("");
  $('#guardarRel').prop('disabled', true);
  $('#tablaCargarContadores tbody tr').remove();

  //HABILITO LOS INPUTS
  $('#observacionesToma').prop('disabled',false);

  //BORRO LOS ERRORES
  ocultarErrorValidacion($('#fiscaToma'));
  ocultarErrorValidacion($('#fechaRel'));

  $('#detallesMTM').show();
  const id_maq = $(this).attr('id');
  console.log('id_maquina', id_maq);

  $('#guardarRel').attr('data-maq',id_maq);

  const id_rel = $(this).attr('data-rel');
  $.get('eventualidadesMTM/obtenerMTMEv/' + id_rel, function(data){
    if(data.fecha != null){ 
      $('#fechaRel').val(data.fecha);
    }
    if(data.cargador != null) { 
      $('#fiscaCarga').val(data.cargador.nombre).prop('disabled',true);
    }
    if(data.fiscalizador!=null){
      $('#fiscaToma').setearElementoSeleccionado(data.fiscalizador.id_usuario,data.fiscalizador.nombre);
    }
    $('#guardarRel').prop('disabled', false);
    setearDivRelevamiento(data);
  })
});

//BOTÓN GUARDAR dentro del modal cargar eventualidad
$(document).on('click','#guardarRel',function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const datos = obtenerDatosDivRelevamiento();
  const formData = {
    id_log_movimiento: $('#guardarRel').attr('data-mov'),
    id_maquina: $('#guardarRel').attr('data-maq'),
    id_cargador: $('#fiscaCarga').attr('data-id'),
    id_fiscalizador: $('#fiscaToma').obtenerElementoSeleccionado(),
    contadores: datos.contadores,
    juego: datos.juego,
    apuesta_max: datos.apuesta,
    cant_lineas: datos.cant_lineas,
    porcentaje_devolucion: datos.devolucion,
    denominacion: datos.denominacion,
    cant_creditos: datos.creditos,
    fecha_sala: $('#fecha_ejecucionRel').val(),
    observaciones: datos.observaciones,
    mac: datos.mac,
    islaRelevadaEv: datos.isla_rel,
    sectorRelevadoEv: datos.sector_rel,
    progresivos: datos.progresivos
  };

  $.ajax({
    type: 'POST',
    url: 'eventualidadesMTM/cargarEventualidadMTM',
    data: formData,
    dataType: 'json',
    success: function (data){
      $('##detallesMTM').hide();
      $('#fechaRel').val(' ');
      $('#fiscaToma').val(' ');
      mensajeExito({titulo:'ÉXITO DE CARGA'});
      $('#'+formData.id_log_movimiento).find('.btn_borrarEvmtm').remove();
      $('#'+formData.id_log_movimiento).find('.btn_validarEvmtm').show();
      $('#'+formData.id_log_movimiento).find('.btn_imprimirEvmtm').show();

      $('#guardarRel').prop('disabled', true);

      $('#tablaCargarMTM').find('.cargarMaq').attr('id',data.id_relevamiento);
      //BORRO LOS ERRORES
      ocultarErrorValidacion($('#fiscaToma'));
      ocultarErrorValidacion($('#fechaRel'));

      var boton = $('#modalCargarRelMov')
      .find('.cargarMaq[id='+formData.id_maquina+']')[0];
      $(boton).empty();
      $(boton).append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt'));

      var cantbotones = $('#modalCargarRelMov')
      .find('.cargarMaq').size();

      var cantlapices = $('#modalCargarRelMov')
      .find('.cargarMaq').find('.fa-pencil-alt').size();

      //Actualizo el boton de la pantalla principal
      //Todos fueron cargados.
      if(cantbotones == cantlapices){
        var btn_menu = $('.btn_cargarEvmtm[value='+formData.id_log_movimiento+']');
        btn_menu.empty();
        btn_menu.append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt'));
      }
    },
    error: function (data){
      console.log('ERROR');
      console.log(data);
      const response = data.responseJSON;
      const err1 = mostrarErroresDiv(response);
      const errores = { 
        'id_fiscalizador' : $('#fiscaToma'),'fecha_sala' : $('#fechaRel')
      };
      let err2 = false;
      for(const key in errores){
        if(!isUndef(response[key])){
          mostrarErrorValidacion(errores[key],parseError(response[key][0]));
          err2 = true;
        }
      }
      if(err1 || err2) $("#modalCargarRelMov").animate({ scrollTop: 0 }, "slow");
    }
  })
});

//BOTÓN DE VALIDAR EN CADA FILA
$(document).on('click','.btn_validarEvmtm',function(){
  //Modificar los colores del modal
  $('#modalValidacionEventualidadMTM .modal-title').text('VALIDAR MÁQUINAS');
  $('#modalValidacionEventualidadMTM .modal-header').attr('style','background: #4FC3F7');
  $('#modalValidacionEventualidadMTM').modal('show');

  //ocultar y limpiar tabla
  $('#tablaMaquinasFiscalizacion tbody tr').remove();
  $('.detalleMaqVal').hide();
  $('#toma2').hide();
  $('.validarEv').prop('disabled', true);
  $('.errorEv').prop('disabled',true);
  $('#observacionesAdmin').val('');
  //oculto botones de error y validacion porque voy a visar de a una
  $('#enviarValidarEv').hide();
  $('#errorValidacionEv').hide();

  const id_log_mov = $(this).val();
  $.get('eventualidadesMTM/maquinasACargar/' + id_log_mov, function(data){
    let tablaMaquinasFiscalizacion = $('#tablaMaquinasFiscalizacion tbody');
    data.relevamientos.forEach(r => {
      let fila= $('<tr>');
      fila.append($('<td>')
        .addClass('col-xs-8')
        .text(r.nro_admin)
      );

      fila.append($('<td>')
        .addClass('col-xs-2')
        .append($('<button>')
          .append($('<i>')
            .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
          )
          .attr('type','button')
          .addClass('btn btn-info verMaquinaEv')
          .attr('data-numadmin',r.nro_admin)
          .attr('data-maquina',r.id_maquina)
          .attr('data-rel', r.id_relev_mov)
          .attr('data-estado',r.id_estado_relevamiento))
      );

      if(r.id_estado_relevamiento == 4){
        fila.append($('<td>').addClass('col-xs-2').append($('<i>')
          .addClass('fa').addClass('fa-fw').addClass('fa-check').css('color','#4CAF50'))
        );
      }

      tablaMaquinasFiscalizacion.append(fila);
    });

  //guardo el id del movimiento en el input del modal
  $('#modalValidacionEventualidadMTM').find('#id_log_movimiento').val(id_log_mov);
  });
});

//botón para ver los detalles de una máquina en particular
$(document).on('click','.verMaquinaEv',function(){
  $('.detalleMaqVal').show();
  //marco el seleccionado
  $('#tablaMaquinasFiscalizacion tbody tr').css('background-color','#FFFFFF');
  $(this).parent().parent().css('background-color', '#E0E0E0');

  if($(this).attr('data-estado') == 4){
    $('#enviarValidarEv').hide();
    $('#errorValidacionEv').hide();
  }
  else{
    $('#enviarValidarEv').show();
    $('#errorValidacionEv').show();
  }

  const numadmin = $(this).attr('data-numadmin');
  const id_maquina = $(this).attr('data-maquina');
  const tablaContadores = $('#tablaValidarContadores tbody');
  const id_relevamiento = $(this).attr('data-rel');
  $('#enviarValidarEv').val(id_relevamiento);

  //guardo el id_maquina en el input maquina del modal
  $('#modalValidacionEventualidadMTM').find('#maquina').val(id_maquina);
  $('#modalValidacionEventualidadMTM').find('#maquina').attr('numadmin',numadmin);
  $('#modalValidacionEventualidadMTM').find('#relevamiento').val(id_relevamiento);
  $('#sectorRelevadoVal').val('');
  $('#islaRelevadaVal').val('');

  $('#tablaValidarContadores tbody tr').remove();
  $.get('eventualidadesMTM/obtenerMTMEv/' + id_relevamiento, function(data){
    console.log('aqui:',data);
    //CARGA CAMPOS INPUT

    $('#f_cargaVal').val(data.cargador == null? '' : data.cargador.nombre);
    $('#f_tomaVal').val(data.fiscalizador == null? '' : data.fiscalizador.nombre);
    $('#tipo_movVal').val(data.tipo_movimiento.descripcion);
    $('#nro_adminVal').val(data.maquina.nro_admin);
    $('#nro_islaVal').val(data.maquina.nro_isla);
    $('#nro_serieVal').val(data.maquina.nro_serie);
    $('#marcaVal').val(data.maquina.marca);
    $('#modeloVal').val(data.maquina.modelo);
    $('#macVal').val(data.toma.mac);
    $('#fecha_Val').val(data.fecha);
    $('#sectorRelevadoVal').val(data.toma.descripcion_sector_relevado);
    $('#islaRelevadaVal').val(data.toma.nro_isla_relevada);

    //CARGAR LA TABLA DE CONTADORES, HASTA 6
    for (let i = 1; i < 7; i++) {
      let fila = $('<tr>');
      const p = data.maquina["cont" + i];
      let v = data.toma["vcont" + i];
      if(v == null){
        v="-"
      }

      if(p != null){
          fila.append($('<td>')
            .addClass('col-xs-6')
            .text(p)
          )
          .append($('<td>')
            .addClass('col-xs-3')
            .text(v)
          );
          $('#tercer_col').hide();
          tablaContadores.append(fila);
      }
    }

    if(data.toma!=null){
      $('#juego').val(data.nombre_juego);
      $('#apuesta').val(data.toma.apuesta_max);
      $('#cant_lineas').val(data.toma.cant_lineas);
      $('#devolucion').val(data.toma.porcentaje_devolucion);
      $('#denominacion').val(data.toma.denominacion);
      $('#creditos').val(data.toma.cant_creditos);
    }

    $('#observacionesToma').show();
    if(data.toma.observaciones!=null){
      $('#observacionesToma').text(data.toma.observaciones);}
    else{
      $('#observacionesToma').text(' ');
    }

    $('.detalleMaq').show();
    $('.validar').prop('disabled', false);
    $('.error').prop('disabled',false);
  });
  $('#enviarValidarEv').prop('disabled',false);
  $('#errorValidacionEv').prop('disabled',false);
});

//botón validar dentro del modal validar
$(document).on('click', '#enviarValidarEv', function(){
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });
    $('.detalleMaqVal').hide();

    const id = $(this).val();
    const observacion = $('#observacionesAdmin').val();
    const formData = {
      id_relev_mov: id,
      observacion: observacion,
    }

    $.ajax({
      type: 'POST',
      url: 'eventualidadesMTM/visarConObservacion',
      data: formData,
      dataType: 'json',
      success: function (data) {
        if(data.id_estado_relevamiento == 4){
          mensajeExito({mensajes: 'Se valido la intervención.'});
          $('#enviarValidarEv').hide();
          $('#errorValidarEv').hide();
          $('#tablaMaquinasFiscalizacion tbody tr').each(function(){
              let maq = $('#modalValidacionEventualidadMTM').find('#maquina').val();
              console.log('44',maq);
              let boton = $(this).find('.verMaquinaEv');
              //Deberia ser siempre true.
              if($(boton).attr('data-maquina') == maq){
                $(boton).attr('data-estado',4);
                $(this).append($('<td>')
                  .addClass('col-xs-2')
                  .append($('<i>').addClass('fa fa-fw fa-check').css('color','#4CAF50')));
              }
          });
        };
      },
      error: function (data) {
        console.log('Error:', data);
        mensajeError(['Error al validar la intervención.']);
      }
    });
});

$('#modalValidacionEventualidadMTM').on('hidden.bs.modal', function() {
  $('#btn-buscarEventualidadMTM').trigger('click');
});

//botón ERROR dentro del modal validar
$(document).on('click', '#errorValidarEv', function(){
  $('.detalleMaqVal').hide();
});

//botón impŕimir de la tab la principal
$(document).on('click', '.btn_imprimirEvmtm', function(){
  const id_mov = $(this).val();
  //le envío 0 para que identifique que es la planilla completa
  window.open('eventualidadesMTM/imprimirEventualidadMTM/' + id_mov + '/' + 0,'_blank');
});

//Busqueda de eventos
$('#btn-buscarEventualidadMTM').click(function(e,pagina,tam,columna,orden){
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  e.preventDefault();

  const noTieneValor = function(val){
    const es_null = val === null;
    const es_undefined = typeof val === 'undefined';
    return es_null || es_undefined;
  }

  let sort_by = {
    columna: noTieneValor(columna)? $('#tablaResultadosEvMTM .activa').attr('value') : columna, 
    orden: noTieneValor(orden)?  $('#tablaResultadosEvMTM .activa').attr('estado') : orden
  };
  if(noTieneValor(sort_by.columna)){
    sort_by.columna = 'log_movimiento.fecha';
  }
  if(noTieneValor(sort_by.orden)){
    sort_by.orden = 'desc';
  }
  console.log(sort_by);
  const page = noTieneValor(pagina)? $('#herramientasPaginacion').getCurrentPage() : pagina;
  const page_size = noTieneValor(tam)? $('#herramientasPaginacion').getPageSize() : tam;
  const formData = {
    id_tipo_movimiento: $('#B_TipoMovEventualidad').val(),
    fecha: $('#fecha_eventualidad').val(),
    id_casino: $('#B_CasinoEv').val(),
    mtm: $('#B_mtmEv').val(),
    isla: $('#B_islaEv').val(),
    sentido: $('#B_SentidoEventualidad').val(),
    page: page,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
    type: 'POST',
    url: 'eventualidades/buscarEventualidadesMTMs',
    data: formData,
    dataType: 'json',

    success: function (response) {
      console.log('success', response);
      $('#herramientasPaginacion').generarTitulo(page,page_size,response.eventualidades.total,clickIndice);
      $('#tablaResultadosEvMTM #cuerpoTablaEvMTM tr').remove();
      const eventualidades = response.eventualidades.data;
      for (var i = 0; i < eventualidades.length; i++) {
        var filaEventualidad = generarFilaTabla(eventualidades[i], response.esControlador,response.esSuperUsuario);
        $('#cuerpoTablaEvMTM').append(filaEventualidad);
      }
      $('#herramientasPaginacion').generarIndices(page,page_size,response.eventualidades.total,clickIndice);
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }

  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaResultados .activa').attr('value');
  const orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscarEventualidadMTM').trigger('click',[pageNumber,tam,columna,orden]);
}

$("#modalValidacionEventualidadMTM").on('hidden.bs.modal', function () {
    $('#btn-buscarEventualidadMTM').trigger('click');
});

//Se generan filas en la tabla principal con las eventualidades encontradas
function generarFilaTabla(event,controlador,superusuario){
  const estado = event.id_estado_movimiento;
  let fila = $('#filaEjemploTablaEventualidades').clone().removeAttr('id');
  fila.attr('id',event.id_log_movimiento);
  fila.find('.fecha').text(convertirDate(event.fecha)).attr('title',event.fecha);
  fila.find('.tipo').text(event.descripcion).attr('title',event.descripcion);
  fila.find('.sentido').text(event.sentido).attr('title',event.sentido);
  fila.find('.estado').attr('title',event.estado_descripcion);
  let iclass = 'fa-exclamation';
  let color = 'rgb(255,255,0)';
  let icon = fila.find('.estado i');
  icon.removeClass('fa-exclamation');
  if(estado == 1)      { iclass = 'fa-envelope'  ; color = 'rgb(66,133,244)' ;} // Notificado
  else if(estado == 4) { iclass = 'fa-check'     ; color = 'rgb(76,175,80)'  ;} // Validado
  else if(estado == 6) { iclass = 'fa-plus'      ; color = 'rgb(150,150,150)';} // Creado
  else if(estado == 8) { iclass = 'fa-pencil-alt'; color = 'rgb(244,160,0)'  ;} // Cargando
  else                 { iclass = 'fa-times'     ; color = 'rgb(239,83,80)'  ;} // Cualquier otro
  icon.addClass(iclass).css('color',color);
  fila.find('.estado').attr('title',event.estado_descripcion);
  fila.find('.casino').text(event.nombre).attr('title',event.nombre);
  const islas = event.islas === null? '-' : event.islas;
  fila.find('.isla').text(islas).attr('title',islas);
  if(estado == 1) fila.find('.accion .btn_cargarEvmtm i').removeClass('fa-upload').addClass('fa-pencil-alt');
  fila.find('button').attr('data-casino',event.id_casino).val(event.id_log_movimiento);

  if(estado!=8 && estado!=6 && estado!=1){fila.find('.btn_validarEvmtm').hide(); fila.find('.btn_cargarEvmtm').hide();fila.find('.btn_borrarEvmtm').hide(); }
  if(controlador == 0 && !superusuario){fila.find('.btn_validarEvmtm').hide();fila.find('.btn_borrarEvmtm').hide();}
  if (controlador == 1 && estado==8 && !superusuario) {fila.find('.btn_validarEvmtm').hide()}
  if(controlador == 1 && estado == 6 && !superusuario){fila.find('.btn_validarEvmtm').hide(); fila.find('.btn_cargarEvmtm').hide();}
  if(controlador==1 && estado==1 && !superusuario){fila.find('.btn_cargarEvmtm').hide();}
  if(event.deprecado==1) fila.find('td').css('color','rgb(150,150,150)');

  return fila;
};

$(document).on('click','.btn_borrarEvmtm',function(e){
  const id_log_movimiento = $(this).val();
  let fila = $(this).parent().parent();
  modalEliminar(function(){
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });
   
    const formData = {
      id_log_movimiento: id_log_movimiento
    }
  
    $.ajax({
      type: 'POST',
      url: 'eventualidadesMTM/eliminarEventualidadMTM',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log('success', data);
        fila.remove();
        mensajeExito({mensajes : ['Se elimino la intervención.']});
      },
      error: function (data) {
        console.log('Error:', data);
        mensajeError(['No se ha podido eliminar la intervención.']);
      }
    });
  },
  function(){},
  "¿Seguro desea eliminar la intervención?")
});

$(document).on('click','#tablaResultadosEvMTM thead tr th[value]',function(e){
  $('#tablaResultadosEvMTM th').removeClass('activa');
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
  $('#tablaResultadosEvMTM th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});