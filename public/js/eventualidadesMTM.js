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
   divRelMovInit();
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

function initModalNuevaEvMTM(){
  $('#tablaMTM tbody tr').remove();
  $.get('eventualidadesMTM/tiposMovIntervMTM', function(data){
    $('#tipoMov option').remove();
    data.tipos_movimientos.forEach(tm => {
      $('#modalNuevaEvMTM #tipoMov').append($('<option>').val(tm.id_tipo_movimiento).text(tm.descripcion));
    });
    $('#modalNuevaEvMTM').modal('show');
  });

  const casino = $('#casinoNuevaEvMTM').val();
  $('#inputMTM').generarDataList("maquinas/obtenerMTMEnCasino/" + casino, 'maquinas','id_maquina','nro_admin',1,true);
  $('#modalNuevaEvMTM').find('#btn-impr').prop('disabled',true);
}
//botón grande para generar la nueva eventualidad de máquina
$(document).on('click','#btn-nueva-evmaquina',function(e){
  e.preventDefault();
  initModalNuevaEvMTM();
});

$('#casinoNuevaEvMTM').change(function(){
  initModalNuevaEvMTM();
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
    sentido: $('#sentidoMov').val(),
    id_casino: $('#casinoNuevaEvMTM').val()
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
      window.open('eventualidadesMTM/imprimirEventualidadMTM/' + data,'_blank');
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
      if(typeof response.id_casino !== 'undefined'){
        mostrarErrorValidacion($('#casinoNuevaEvMTM'),parseError(response.id_casino[0]));
        err = true;
      }
      if(err) $("#modalNuevaEvMTM").animate({ scrollTop: 0 }, "slow");
    }
  });
});

$(document).on('click','.borrarMTMCargada',function(e){
  $(this).parent().parent().remove();
});

function mostrarFiscalizacion(id_mov,modo){
  $('#guardarRel').prop('disabled', true);
  $('#guardarRel').toggle(modo == "CARGAR");
  divRelMovEsconderDetalleRelevamiento();
  $.get('eventualidadesMTM/relevamientosEvMTM/' + id_mov, function(data){
    console.log('88',data);
    divRelMovSetearUsuarios(data.casino,data.fiscalizador_carga,null);
    divRelMovSetearTipo(data.tipo_movimiento,data.sentido);
    let dibujos = {3 : 'fa-search-plus', 4 : 'fa-search-plus'};
    if(modo == "CARGAR") dibujos = {3 : 'fa-pencil-alt'};
    divRelMovCargarRelevamientos(data.relevamientos,dibujos,-1);
    divRelMovSetearModo(modo);
    $('#modalCargarRelMov').modal('show');
  })
}
//botón para cargar máquina
$(document).on('click', '.btn_cargarEvmtm', function(){
  $('#modalCargarRelMov .modal-title').text('CARGAR MAQUINAS');
  $('#modalCargarRelMov .modal-header').attr('style','background: #6dc7be');
  mostrarFiscalizacion($(this).val(),"CARGAR");
});
$(document).on('click','.btn_verEvmtm',function(){
  $('#modalCargarRelMov .modal-title').text('VER MÁQUINAS');
  $('#modalCargarRelMov .modal-header').attr('style','background: #4FC3F7');
  mostrarFiscalizacion($(this).val(),"VER");
});

//boton que cierra el modal, para que cierre los detalles de las mtm
$('#btn-closeCargar').click(function(e){
  $('#detallesMTM').hide();
  $('#btn-buscarEventualidadMTM').trigger('click');
});

//presiona el ojo de una máquina para cargar los detalles
$(document).on('click','.cargarMaq',function(){
  const id_rel = $(this).attr('data-rel');
  const toma = $(this).attr('toma');
  $('#guardarRel').attr('data-rel', id_rel);
  $('#guardarRel').attr('toma', toma);
  $.get('eventualidadesMTM/obtenerRelevamientoToma/' + id_rel, function(data){
    $('#guardarRel').prop('disabled', false);
    divRelMovSetear(data);
    divRelMovMostrarDetalleRelevamiento();
  })
});

//BOTÓN GUARDAR dentro del modal cargar eventualidad
$(document).on('click','#guardarRel',function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const datos = divRelMovObtenerDatos();
  const formData = {
    id_relev_mov:          $('#guardarRel').attr('data-rel'),
    toma:                  $('#guardarRel').attr('toma'),
    id_cargador:           datos.usuario_carga.id_usuario,
    id_fiscalizador:       datos.usuario_toma.id_usuario,
    contadores:            datos.contadores,
    juego:                 datos.juego,
    apuesta_max:           datos.apuesta,
    cant_lineas:           datos.lineas,
    porcentaje_devolucion: datos.devolucion,
    denominacion:          datos.denominacion,
    cant_creditos:         datos.creditos,
    fecha_sala:            datos.fecha_ejecucion,
    observaciones:         datos.observaciones,
    mac:                   datos.mac,
    isla_relevada:         datos.isla_rel,
    sector_relevado:       datos.sector_rel,
    progresivos:           datos.progresivos
  };

  divRelMovLimpiarErrores();
  $.ajax({
    type: 'POST',
    url: 'eventualidadesMTM/cargarEventualidadMTM',
    data: formData,
    dataType: 'json',
    success: function (data){
      $('#guardarRel').prop('disabled', true);
      divRelMovEsconderDetalleRelevamiento();
      divRelMovLimpiar();
      mensajeExito({titulo:'ÉXITO DE CARGA'});
      //BORRO LOS ERRORES
      divRelMovCambiarDibujoMaq(formData.id_maquina,'fa fa-fw fa-pencil-alt');
      $('#btn-buscarEventualidadMTM').click();
    },
    error: function (data){
      console.log('ERROR');
      console.log(data);
      divRelMovMostrarErrores(data.responseJSON);
    }
  })
});

//BOTÓN DE VALIDAR EN CADA FILA
$(document).on('click','.btn_validarEvmtm',function(){
  $('#modalCargarRelMov .modal-title').text('VALIDAR MÁQUINAS');
  $('#modalCargarRelMov .modal-header').attr('style','background: #69F0AE');
  mostrarFiscalizacion($(this).val(),"VALIDAR");
});

$(document).on('click','#divRelevamientoMovimiento .validar',function(){
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  const formData = {
    id_relev_mov: $('#guardarRel').attr('data-rel'),
    observacion: divRelMovObtenerDatos().observacionesAdm,
  }

  $.ajax({
    type: 'POST',
    url: 'eventualidadesMTM/visarConObservacion',
    data: formData,
    dataType: 'json',
    success: function (data) {
      if(data.id_estado_relevamiento == 4){
        divRelMovLimpiar();
        mensajeExito({titulo:'ÉXITO DE VALIDACIÓN'});
        divRelMovMarcarListoRel(formData.id_relev_mov);
      };
    },
    error: function (data) {
      console.log('Error:', data);
      mensajeError(['Error al validar la intervención.']);
    }
  });
});

//botón impŕimir de la tab la principal
$(document).on('click', '.btn_imprimirEvmtm', function(){
  const id_mov = $(this).val();
  //le envío 0 para que identifique que es la planilla completa
  window.open('eventualidadesMTM/imprimirEventualidadMTM/' + id_mov,'_blank');
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

  fila.find('.btn_validarEvmtm').toggle((estado == 3 || event.id_estado_relevamiento == 3) && (superusuario || controlador));
  fila.find('.btn_cargarEvmtm').toggle(estado == 1 || estado == 6 || estado == 8);
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