$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#movimientos').removeClass();
  $('#movimientos').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Relevamientos');
  $('#opcRelevamientosMovimientos').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcRelevamientosMovimientos').addClass('opcionesSeleccionado');

  $('#dtpFechaRM').datetimepicker({
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

  $('#btn-buscarRelMov').click();
  initDivRelevamientoMovimiento();
});

$('#fechaRel').on('change', function (e) {
  $(this).trigger('focusin');
})

$('#fechaRelMov').on('change', function (e) {
  $(this).trigger('focusin');
})

//SELECCIONA EL BOTÓN QUE ABRE EL MODAL DE CARGA
$(document).on('click','.btn-generarRelMov, .btn-imprimirRelMov',function(e){
  const id_fiscalizacion= $(this).val();
  window.open('movimientos/generarPlanillasRelevamientoMovimiento/' + id_fiscalizacion,'_blank');
});

$(document).on('click','.btn-cargarRelMov',function(e){
  mostrarFiscalizacion($(this).val(),false,true);
});
$(document).on('click','.btn-cargarT2RelMov',function(e){
  mostrarFiscalizacion($(this).val(),true,true);
});
$(document).on('click','.btn-verRelMov',function(e){
  mostrarFiscalizacion($(this).val(),false,false);
});

function mostrarFiscalizacion(id_fiscalizacion,es_segunda_toma,editable){
  $('#guardarRel').prop('disabled', true).attr('es-segunda-toma',es_segunda_toma? 1 : 0);
  $('#guardarRel').toggle(editable);
  esconderDetalleRelevamiento();
  $.get('movimientos/obtenerRelevamientosFiscalizacion2/' + id_fiscalizacion, function(data){
    setearUsuariosCargaToma(data.casino,data.cargador,data.fiscalizador);
    setearTipoMovimiento(data.tipo_movimiento,data.sentido);
    const estado_listo = es_segunda_toma? 7 : 3;
    cargarRelevamientos(data.relevamientos,{},estado_listo);
    divRelHabilitarEdicion(editable);
    $('#modalCargarRelMov').modal('show');
  });
}

//SELECCIONA UNA MÁQUINA PARA VER SU DETALLE
$(document).on('click','.cargarMaq',function(){
  const id_rel = $(this).attr('data-rel');
  const toma = $(this).attr('toma');
  $('#guardarRel').attr('data-rel', id_rel);
  $.get('movimientos/obtenerMTMFiscalizacion2/' + id_rel + '/' + toma, function(data){
    $('#guardarRel').prop('disabled', false);
    setearDivRelevamiento(data);
    mostrarDetalleRelevamiento();
  });
});

//BOTÓN GUARDAR
$(document).on('click','#guardarRel',function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const datos = obtenerDatosDivRelevamiento();
  const formData = {
    id_relev_mov:                $('#guardarRel').attr('data-rel'),
    es_cargaT2:                  $('#guardarRel').attr('es-segunda-toma'),
    estado:                      2,
    id_cargador:                 datos.usuario_carga.id_usuario,
    id_fiscalizador:             datos.usuario_toma.id_usuario,
    contadores:                  datos.contadores,
    juego:                       datos.juego,
    apuesta_max:                 datos.apuesta,
    cant_lineas:                 datos.lineas,
    porcentaje_devolucion:       datos.devolucion,
    denominacion:                datos.denominacion,
    cant_creditos:               datos.creditos,
    fecha_sala:                  datos.fecha_ejecucion,
    observaciones:               datos.observaciones,
    mac:                         datos.mac,
    isla_relevada:               datos.isla_rel,
    sector_relevado:             datos.sector_rel,
    progresivos:                 datos.progresivos
  }

  $.ajax({
    type: 'POST',
    url: 'movimientos/cargarTomaRelevamiento',
    data: formData,
    dataType: 'json',
    success: function (data) {
      console.log('BIEN');
      console.log(data);
      esconderDetalleRelevamiento();
      marcarListaMaquina(formData.id_maquina);
      mensajeExito({mensajes :['Los datos se han guardado correctamente']});
      $('#guardarRel').prop('disabled', true);
      $('#btn-buscarRelMov').click();
    },

    error: function (data){
      console.log('ERROR');
      console.log(data);
      mostrarErroresDiv(data.responseJSON);
    }
  });
});

$(document).on('click','.btn-eliminarFiscal',function(){
  const id=$(this).val();
  console.log(id);

  $.get('relevamientos_movimientos/eliminarFiscalizacion/' + id,function(data){
    if(data==1){
      $('#mensajeExito h3').text('ÉXITO DE ELIMINACIÓN');
      $('#mensajeExito p').text(' ');
      $('#mensajeExito').show();
      $('#btn-buscarRelMov').trigger('click');
    }
  });
});

function noTieneValor(val){
  const es_null = val === null;
  const es_undefined = typeof val === 'undefined';
  return es_null || es_undefined;
}

//Busqueda de eventos
$('#btn-buscarRelMov').click(function(e,pagina,tam,columna,orden){
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  e.preventDefault();

  let sort_by = {
    columna: noTieneValor(columna)? $('#tablaRelevamientosMovimientos .activa').attr('value') : columna, 
    orden: noTieneValor(orden)?  $('#tablaRelevamientosMovimientos .activa').attr('estado') : orden
  };
  if(noTieneValor(sort_by.columna)){
    sort_by.columna = 'fiscalizacion_movimiento.id_fiscalizacion_movimiento';
  }
  if(noTieneValor(sort_by.orden)){
    sort_by.orden = 'desc';
  }
  console.log(sort_by);
  const page = noTieneValor(pagina)? $('#herramientasPaginacion').getCurrentPage() : pagina;
  const page_size = noTieneValor(tam)? $('#herramientasPaginacion').getPageSize() : tam;

  const formData = {
    id_tipo_movimiento: $('#B_TipoMovimientoRel').val(),
    fecha: $('#fechaRelMov').val(),
    nro_admin: $('#busqueda_maquina').val(),
    id_casino: $('#B_Casino').val(),
    id_log_movimiento: $('#busqueda_numero_movimiento').val(),
    page: noTieneValor(page)? 1 : page,
    sort_by: sort_by,
    page_size: noTieneValor(page_size)? 10 : page_size,
  }

  $.ajax({
    type: 'POST',
    url: 'relevamientos_movimientos/buscarFiscalizaciones',
    data: formData,
    dataType: 'json',

    success: function (response) {
      const fiscalizaciones = response.fiscalizaciones.data;
      console.log('success rel:', response);
      $('#herramientasPaginacion').generarTitulo(page,page_size,response.fiscalizaciones.total,clickIndice);
      $('#herramientasPaginacion').generarIndices(page,page_size,response.fiscalizaciones.total,clickIndice);
      $('#tablaRelevamientosMovimientos #cuerpoTablaRel tr').remove();
      fiscalizaciones.forEach(f => {
        $('#cuerpoTablaRel').append(generarFilaTabla(f));
      });
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
  const columna = $('#tablatablaRelevamientosMovimientosResultados .activa').attr('value');
  const orden = $('#tablaRelevamientosMovimientos .activa').attr('estado');
  $('#btn-buscarRelMov').trigger('click',[pageNumber,tam,columna,orden]);
}


//Se generan filas en la tabla principal con las fiscalizaciones encontradas
function generarFilaTabla(rel){
  console.log('generar',rel);
  const fecha = rel.fecha_envio_fiscalizar;
  const tipo_mov = rel.descripcion;
  const casino = rel.nombre;
  const nota = noTieneValor(rel.identificacion_nota)? '---' : rel.identificacion_nota;

  let fila = $('#filaEjemploRelevamiento').clone().attr('id',rel.id_fiscalizacion_movimiento);
  fila.find('.movimiento').text(rel.id_log_movimiento).attr('title',rel.id_log_movimiento);
  fila.find('.fecha').text(fecha).attr('title',fecha);
  fila.find('.nota').text(nota).attr('title',nota);
  fila.find('.tipo').text(tipo_mov).attr('title',tipo_mov);
  fila.find('.casino').text(casino).attr('title',casino);
  fila.find('.maquinas').text(rel.maquinas).attr('title',rel.maquinas);
  fila.find('button').attr('value',rel.id_fiscalizacion_movimiento);

  if(rel.es_controlador != 1){fila.find('.btn-eliminarFiscal').hide();}

  const estado = rel.id_estado_relevamiento;
  if(estado < 3){
    fila.find('.btn-imprimirRelMov').hide();
    fila.find('.btn-cargarT2RelMov').hide();
  }
  if(estado > 2){
    fila.find('.btn-imprimirRelMov').show();
    fila.find('.btn-eliminarFiscal').show();
    fila.find('.btn-generarRelMov').hide();
    fila.find('.btn-cargarRelMov').hide();
    if(estado < 7 && estado != 4 && tipo_mov != 'INGRESO' && tipo_mov != 'EGRESO/REINGRESOS'){
      fila.find('.btn-cargarT2RelMov').show();
    }else{
      fila.find('.btn-cargarT2RelMov').hide();
    }
  }

  return fila;
};


$(document).on('click','#tablaRelevamientosMovimientos thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');

  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    console.log('1');
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

$('#collapseFiltros').keypress(function(e){
  if(e.charCode == 13){//Enter
    $('#btn-buscarRelMov').click();
  }
})
