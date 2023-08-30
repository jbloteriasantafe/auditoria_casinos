$(document).ready(function(){
  $('#collapseFiltros #B_nro_exp_org').val("");
  $('#collapseFiltros #B_nro_exp_interno').val("");
  $('#collapseFiltros #B_nro_exp_control').val("");
  $('#collapseFiltros #B_TipoMovimiento').val("0");
  $('#collapseFiltros #dtpFechaMov').val("");
  $('#collapseFiltros #dtpCasinoMov').val("0");

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#movimientos').removeClass();
  $('#movimientos').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Asignación de movimientos a relevar');
  $('#opcAsignacion').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcAsignacion').addClass('opcionesSeleccionado');

  //PAGINACION
  $('#btn-buscarMovimiento').trigger('click');
 //agregar para que permita seleccionar fecha hasta hoy inclusive
  $(function(){
    $('#dtpFechaMov').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd / mm / yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2,
      container:$('main section'),
    });
  });
  $(function(){
    $('#dtpFechaMDenom').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'yyyy-mm-dd',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2,
      container:$('#modalDenominacion'),
    });
  });
  $(function(){
    $('#dtpFechaIngreso').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'yyyy-mm-dd',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2,
      container:$('#modalEnviarFiscalizar'),
    });
  });

  limpiarModal();
  divRelMovInit();
}); //FIN DEL DOCUMENT READY

/* 
 NUEVO MOVIMIENTO
 ###########################
 ####       #######    #####
 ####    #   ######    #####
 ####    ##   #####    #####
 ####    ###   ####    #####
 ####    ####   ###    #####
 ####    #####   ##    #####
 ####    ######   #    #####
 ####    #######       #####
 ###########################
*/

//BOTON GRANDE DE NUEVO INGRESO
$(document).on('click', '#btn-nuevo-movimiento', function (e) {
  e.preventDefault();
  //limpio las opciones del select
  $('#selectCasinoIngreso option').not('.default1').remove();
  $('#tipo_movimiento_nuevo option').not('.default2').remove();

  //SETEO EN 0 EL SELECT DE CASINO
  $('#selectCasinoIngreso').val(3);
  $('#tipo_movimiento_nuevo').val(7);

  $('#mensajeExito').hide();
  $('#mensajeError').hide();

  $.get('movimientos/casinosYMovimientosIngresosEgresos', function (data) {
    //carga el select de los casinos del modal
    data.casinos.forEach(c => {
      $('#modalCas #selectCasinoIngreso')
        .append($('<option>')
          .prop('disabled', false)
          .val(c.id_casino)
          .text(c.nombre_casino));
    });
    //carga el select de los tipos de movimientos del modal
    data.tipos_movimientos.forEach(t => {
      $('#modalCas #tipo_movimiento_nuevo')
        .append($('<option>').prop('disabled', false)
          .val(t.id_tipo_movimiento).text(t.descripcion));
    });
  });

  $('#modalCas .alerta').each(function () {
    eliminarErrorValidacion($(this));
    $(this).removeClass('alerta');
  });

  //ABRE MODAL QUE ME PERMITE ELEGIR EL CASINO AL QUE PERTENECE EL NUEVO MOV.
  $('#modalCas').modal('show');
});

//ACEPTA EL MODAL DE CASINO
$(document).on('click', '#aceptarCasinoIng', function (e) {
  $('#mensajeExito').hide();
  const id_mov = $('#modalCas #tipo_movimiento_nuevo').val();
  const id_cas = $('#modalCas #selectCasinoIngreso').val();
  const formData = {
    id_tipo_movimiento: id_mov,
    id_casino: id_cas
  }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'POST',
    url: 'movimientos/nuevoLogMovimiento',
    data: formData,
    dataType: 'json',
    success: function (data) {
      //CREO LA NUEVA FILA DE MOVIMIENTO
      var movimiento = generarFilaTabla(data);
      $('#cuerpoTabla').append(movimiento);

      //recargo la pág para que aparezca el nuevo movimientos en la tabla de movimientos
      $('#btn-buscarMovimiento').trigger('click');

      //ME PERMITE QUE SE EJECUTE EL COD. QUE MUESTRA LOS NOMBRES DE LOS BOT.
      $('[data-toggle="tooltip"]').tooltip();
      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text('El Movimiento fue creado correctamente');
      $('#modalCas').modal('hide');
      $('#mensajeExito').show();
    },
    error: function (response) {
      console.log(response);
      const errorjson = response.responseJSON;
      if (typeof errorjson.id_casino != 'undefined') {
        mostrarErrorValidacion($('#selectCasinoIngreso'), parseError(errorjson.id_casino[0]), true);
      }
      if (typeof errorjson.id_tipo_movimiento != 'undefined') {
        mostrarErrorValidacion($('#tipo_movimiento_nuevo'), parseError(errorjson.id_tipo_movimiento[0]), true);
      }
    }
  })
});

/* 
 OTROS (cosas que no van en las otras secciones)
 ###########################
 ########           ########
 ######               ######
 #####      #####      #####
 #####     #######     #####
 #####     #######     #####
 #####      #####      #####
 ######               ######
 ########           ########
 ###########################
*/
//-------------------------------------------------------------------------
//Opacidad del modal al minimizar
$('#btn-minimizar').click(function () {
  if ($(this).data("minimizar") == true) {
    $('.modal-backdrop').css('opacity', '0.1');
    $(this).data("minimizar", false);
  } else {
    $('.modal-backdrop').css('opacity', '0.5');
    $(this).data("minimizar", true);
  }
});

$(document).on('click', '.print_mov', function (e) {
  const id = $(this).parent().parent().attr('id');
  window.open('movimientos/imprimirMovimiento/' + id, '_blank');
});

$(document).on('click', '.baja_mov', function (e) {
  $('#mensajeExito').hide();
  $('#mensajeError').hide();
  const id_mov = $(this).parent().parent().attr('id');

  const formData = {
    id_log_movimiento: id_mov
  }

  modalEliminar(function () {
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });

    $.ajax({
      type: 'POST',
      url: 'movimientos/eliminarMovimiento',
      data: formData,
      dataType: 'json',
      success: function (response) {
        mensajeExito({ titulo: 'ELIMINACIÓN EXITOSA', mensajes: ['El movimiento fue eliminado correctamente'] });
      },
      error: function (response) {
        console.log(response);
        mensajeError(sacarErrores(response));
      }
    });
  });
});

$(document).on('click', '.ver_fiscalizaciones', function(){
  const id_mov = $(this).parent().parent().attr('id');
  window.open('relevamientos_movimientos/' + id_mov, '_blank');
})

/* Detecta la confirmación para seguir cargando máquinas en movimientos */
$('#mensajeExito .confirmar').click(function (e) {
  $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');
  $('#mensajeExito').hide();
  const id_mov = $(this).attr('data-ultimo-mov');
  const ultimo_boton_carga = $('#'+id_mov).find('.boton_cargar');
  setTimeout(function () {
    if (ultimo_boton_carga.length > 0) ultimo_boton_carga.click();
  }, 400);
});

/* Detecta la negativa para seguir cargando máquinas en movimientos */
$('#mensajeExito .salir').click(function (e) {
  limpiarModal();//seccionMaquinas-Modal.js
  $('#mensajeExito').hide();
});

$('#mensajeExito,#mensajeError').on('show',function(){
  $('#btn-buscarMovimiento').click();;
})

/* 
 TABLA BUSQUEDA PRINCIPAL
 ###########################
 ##                       ##
 ##                       ##
 ##########     ############
 ##########     ############
 ##########     ############
 ##########     ############
 ##########     ############
 ##########     ############
 ###########################
*/

//Busqueda de movimientos
$('#btn-buscarMovimiento').click(function (e, pagina = null, page_size = null, columna = null, orden = null) {
  $('#mensajeExito').hide();
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  e.preventDefault();

  page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  columna = (columna != null) ? columna : $('#tablaResultados .activa').attr('value');
  orden = (orden != null) ? orden : $('#tablaResultados .activa').attr('estado');
  const sort_by = (columna != null && orden != null) ? { columna: columna, orden: orden } : null;
  if (sort_by == null) { // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
  }

  const formData = {
    nro_exp_org: $('#B_nro_exp_org').val(),
    nro_exp_interno: $('#B_nro_exp_interno').val(),
    nro_exp_control: $('#B_nro_exp_control').val(),
    tipo_movimiento: $('#B_TipoMovimiento').val(),
    casino: $('#dtpCasinoMov').val(),
    fecha: $('#fecha_movimiento').val(),
    nro_admin: $('#busqueda_maquina').val(),
    id_log_movimiento: $('#busqueda_numero').val(),
    page: page_number != null ? page_number : 1,
    page_size: page_size != null ? page_size : 10,
    sort_by: sort_by,
  }

  $.ajax({
    type: 'POST',
    url: 'movimientos/buscarLogsMovimientos',
    data: formData,
    dataType: 'json',

    success: function (data) {
      $('#herramientasPaginacion').generarTitulo(page_number, page_size, data.logMovimientos.total, clickIndiceMov);
      $('#cuerpoTabla tr').remove();
      data.logMovimientos.data.forEach(l => {
        $('#cuerpoTabla').append(generarFilaTabla(l));
      });
      //Me permite mostrar los nombres de los botones
      $('[data-toggle="tooltip"]').tooltip();
      $('#herramientasPaginacion').generarIndices(page_number, page_size, data.logMovimientos.total, clickIndiceMov);
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

$(document).on('click', '#tablaResultados thead tr th[value]', function (e) {
  $('#tablaResultados th').removeClass('activa');

  if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
    console.log('1');
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado', 'desc');
  }
  else if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado', 'asc');
  }
  else {
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  }
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  clickIndiceMov(e, $('#herramientasPaginacion').getCurrentPage(), $('#herramientasPaginacion').getPageSize());
});


function clickIndiceMov(e, pageNumber = null, tam = null) {
  if (e != null) {
    e.preventDefault();
  }

  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaResultados .activa').attr('value');
  const orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscarMovimiento').trigger('click', [pageNumber, tam, columna, orden]);
}

function setearEstadoFila(fila, estado, tipo) {
  const color = {
    verde : 'rgb(102,187,106)',
    rojo : 'rgb(219,68,55)',
    azul : 'rgb(66,133,244)',
    gris : 'rgb(150,150,150)',
    amarillo : 'rgb(244,160,0)'
  }

  if(tipo == 'INGRESO INICIAL'){
    fila.find('.boton_nuevo').addClass('nuevoIngreso');
  }
  else if(tipo == 'EGRESO DEFINITIVO'){
    fila.find('.boton_nuevo').addClass('nuevoEgreso');
  }
  else{
    fila.find('button').not('.print_mov,.baja_mov,.ver_fiscalizaciones').remove();
    fila.find('td').css('color',color.gris).css('font-style','italic');
  }

  switch(estado){
    case 'NOTIFICADO':
    case 'CREADO':{ 
      fila.find('.boton_nuevo').show();
      fila.find('.boton_cargar').hide();
      fila.find('.boton_fiscalizar').hide();
      fila.find('.boton_validar').hide();
      const icono = $('<i>').addClass('fas').addClass('fa-plus').css('color',color.gris);
      fila.find('.icono_mov i').replaceWith(icono);
    }break;
    case 'FISCALIZANDO':{ 
      fila.find('.boton_nuevo').hide();
      fila.find('.boton_cargar').hide();
      fila.find('.boton_fiscalizar').show();
      fila.find('.boton_validar').show();
      const icono = $('<i>').addClass('fas').addClass('fa-chalkboard-teacher').css('color',color.amarillo);
      fila.find('.icono_mov i').replaceWith(icono);
    };break;
    case 'FISCALIZADO':{ 
      fila.find('.boton_nuevo').hide();
      fila.find('.boton_cargar').hide();
      fila.find('.boton_fiscalizar').hide();
      fila.find('.boton_validar').show();
      const icono = $('<i>').addClass('fas').addClass('fa-chalkboard-teacher').css('color',color.verde);
      fila.find('.icono_mov i').replaceWith(icono);
    }break;
    case 'VALIDADO':{
      fila.find('.boton_nuevo').hide();
      fila.find('.boton_cargar').hide();
      fila.find('.boton_fiscalizar').hide();
      fila.find('.boton_validar').hide();
      const icono = $('<i>').addClass('fas').addClass('fa-check').css('color',color.verde);
      fila.find('.icono_mov i').replaceWith(icono); 
    }break;
    case 'ERROR':{
      fila.find('.boton_nuevo').hide();
      fila.find('.boton_cargar').hide();
      fila.find('.boton_fiscalizar').hide();
      fila.find('.boton_validar').hide();
      const icono = $('<i>').addClass('fas').addClass('fa-times').css('color',color.rojo);
      fila.find('.icono_mov i').replaceWith(icono);
    }break;
    case 'MTM CARGADAS':{ 
      fila.find('.boton_nuevo').hide();
      fila.find('.boton_cargar').hide();
      fila.find('.boton_fiscalizar').show();
      fila.find('.boton_validar').hide();
      const icono = $('<i>').addClass('fas').addClass('fa-check').css('color',color.azul);
      fila.find('.icono_mov i').replaceWith(icono);
    }break;
    case 'CARGANDO':{ 
      fila.find('.boton_nuevo').hide();
      fila.find('.boton_cargar').show();
      fila.find('.boton_fiscalizar').hide();
      fila.find('.boton_validar').hide();
      const icono = $('<i>').addClass('fas').addClass('fa-user-edit').css('color',color.amarillo);
      fila.find('.icono_mov i').replaceWith(icono);
    }break;
    default:{
      fila.find('button').not('.print_mov,.baja_mov,.ver_fiscalizaciones').remove()
    }break;
  }
}

//paginacion
function generarFilaTabla(movimiento) {
  let fila = $('#filaEjemploMovimiento').clone().removeAttr('id', '');
  const id = movimiento.id_log_movimiento;
  const t_mov = movimiento.descripcion;
  const fecha = convertirDate(movimiento.fecha);
  const islas = (movimiento.islas != null) ? movimiento.islas : '-';

  fila.attr('id', id);
  fila.find('.casino').text(movimiento.nombre).attr('title',movimiento.nombre);
  fila.find('.nro_mov').text(id).attr('title', id);
  fila.find('.fecha_mov').text(fecha).attr('title', fecha);
  fila.find('.nro_exp_mov').text(movimiento.nro_exp).attr('title', movimiento.nro_exp);
  fila.find('.islas_mov').text(islas).attr('title', islas);
  fila.find('.tipo_mov').text(t_mov).attr('title', t_mov);
  fila.find('.icono_mov').parent().attr('title', movimiento.estado);
  fila.attr('data-casino', movimiento.id_casino);
  fila.attr('data-tipo', movimiento.id_tipo_movimiento);
  fila.attr('data-estado', movimiento.id_estado_movimiento);
  fila.attr('data-tipo-carga', movimiento.tipo_carga);
  console.log('Agregando fila',movimiento.id_estado_movimiento,movimiento.estado);
  setearEstadoFila(fila,movimiento.estado,t_mov);
  return fila;
}

$('#collapseFiltros').keypress(function (e) {
  if (e.charCode == 13) {//Enter
    $('#btn-buscarMovimiento').click();
  }
})
