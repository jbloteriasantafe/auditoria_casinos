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

  const url = window.location.pathname.split("/");
  if(url.length >= 3) {
    const id_mov = url[2];
    $('#busqueda_numero_movimiento').val(id_mov);
  }
  $('#btn-buscarRelMov').click();
  divRelMovInit();
});

$('#fechaRelMov').on('change', function (e) {
  $(this).trigger('focusin');
})

//SELECCIONA EL BOTÓN QUE ABRE EL MODAL DE CARGA
$(document).on('click','.btn-imprimirRelMov',function(e){
  const id_fiscalizacion= $(this).val();
  window.open('/relevamientos_movimientos/imprimirFiscalizacion/' + id_fiscalizacion,'_blank');
});

$(document).on('click','.btn-cargarRelMov',function(e){
  $('#modalCargarRelMov .modal-title').text('CARGAR RELEVAMIENTOS');
  $('#modalCargarRelMov .modal-header').attr('style','background: #6dc7be');
  mostrarFiscalizacion($(this).val(),"CARGAR");
});

$(document).on('click','.btn-verRelMov',function(e){
  $('#modalCargarRelMov .modal-title').text('VER RELEVAMIENTOS');
  $('#modalCargarRelMov .modal-header').attr('style','background: #4FC3F7');
  mostrarFiscalizacion($(this).val(),"VER");
});

function mostrarFiscalizacion(id_fiscalizacion,modo,refrescando = false){
  $('#datosUltimoEgresoTemporal').prop('disabled',true).data('datos',null).hide();
  $('.guardarRelMov').prop('disabled', true);
  $('.guardarRelMov').attr('modo',modo).toggle(modo == "CARGAR");
  $('.guardarRelMov').attr('data-fis',id_fiscalizacion);
  divRelMovEsconderDetalleRelevamiento();
  $.get('/relevamientos_movimientos/obtenerRelevamientosFiscalizacion/' + id_fiscalizacion, function(data){
    divRelMovSetearUsuarios(data.casino,data.cargador,data.fiscalizador);
    divRelMovSetearTipo(data.tipo_movimiento,data.sentido);
    divRelMovSetearExp(data.nro_exp_org,data.nro_exp_interno,data.nro_exp_control,data.nro_disposicion,data.nro_disposicion_anio);
    let dibujos = {3 : 'fa-search-plus', 4 : 'fa-search-plus'};
    divRelMovCargarRelevamientos(data.relevamientos,dibujos,3);
    divRelMovSetearModo("VER");
    if(!refrescando) $('#modalCargarRelMov').modal('show');
  });
}

//SELECCIONA UNA MÁQUINA PARA VER SU DETALLE
$(document).on('click','#divRelMov .cargarMaq',function(){
  const id_rel = $(this).attr('data-rel');
  const toma = $(this).attr('toma');
  const modo_ventana = $('.guardarRelMov').attr('modo');
  $('.guardarRelMov').attr('data-rel', id_rel);
  $('.guardarRelMov').attr('toma', toma);
  $.get('/relevamientos_movimientos/obtenerRelevamientoToma/' + id_rel + '/' + toma, function(data){
    $('.guardarRelMov').prop('disabled', true).hide();
    $('#datosUltimoEgresoTemporal').prop('disabled',true).data('datos',null).hide();
    const estado_rel = data.relevamiento.id_estado_relevamiento;
    if (modo_ventana == "CARGAR"){
      //GENERADO || CARGANDO || SIN RELEVAR
      if(estado_rel == 1 || estado_rel == 2 || estado_rel == 5){
        divRelMovSetearModo("CARGAR");
        $('.guardarRelMov').prop('disabled', false).show();
        const puede_recargar = data.datos_ultimo_relev != null;
        $('#datosUltimoEgresoTemporal').prop('disabled',!puede_recargar).toggle(puede_recargar)
        .data('datos',data.datos_ultimo_relev);
      }
      else divRelMovSetearModo("VER");
    }
    else{ //VER por defecto
      divRelMovSetearModo("VER");
    }
    divRelMovSetear(data);
    divRelMovSetearExp(data.nro_exp_org,data.nro_exp_interno,data.nro_exp_control,data.nro_disposicion,data.nro_disposicion_anio);
    divRelMovMostrarDetalleRelevamiento();
  });
});

function buildFormData(formData, data, parentKey) {//https://stackoverflow.com/questions/22783108/convert-js-object-to-form-data
  if (data && typeof data === 'object' && !(data instanceof Date) && !(data instanceof File) && !(data instanceof Blob)) {
    Object.keys(data).forEach(key => {
      buildFormData(formData, data[key], parentKey ? `${parentKey}[${key}]` : key);
    });
  } else {
    const value = data == null ? '' : data;
    formData.append(parentKey, value);
  }
}
function objToFormData(data) {
  const formData = new FormData();
  buildFormData(formData, data);
  return formData;
}

//BOTÓN GUARDAR
$(document).on('click','.guardarRelMov',function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});


  const datos = divRelMovObtenerDatos();
  const formData = {
    id_relev_mov:                $('.guardarRelMov').attr('data-rel'),
    toma:                        $('.guardarRelMov').attr('toma'),
    temporal:                    parseInt($(this).attr('data-temporal') ?? '1'),
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
    adjunto:                     datos.adjunto ?? null,
    link_adjunto:                datos.link_adjunto,
    mac:                         datos.mac,
    isla_relevada:               datos.isla_rel,
    sector_relevado:             datos.sector_rel,
    progresivos:                 datos.progresivos
  }

  divRelMovLimpiarErrores();
  $.ajax({
    type: 'POST',
    url: '/relevamientos_movimientos/cargarTomaRelevamiento',
    data: objToFormData(formData),
    dataType: 'json',
    processData: false,
    contentType: false,
    cache: false,
    success: function (data) {
      console.log('BIEN');
      console.log(data);
      divRelMovEsconderDetalleRelevamiento();
      divRelMovLimpiar();
      divRelMovMarcarListaMaq(formData.id_maquina);
      mensajeExito({mensajes :['Los datos se han cargado correctamente']});
      $('.guardarRelMov').prop('disabled', true);
      $('#datosUltimoEgresoTemporal').prop('disabled',true).data('datos',null).hide();
      $('#btn-buscarRelMov').click();
      if(data.fisFinalizada){
        $('#modalCargarRelMov').modal('hide');
      }
      else{
        mostrarFiscalizacion($('.guardarRelMov').attr('data-fis'),$('.guardarRelMov').attr('modo'),true);
      }
    },

    error: function (data){
      console.log('ERROR');
      console.log(data);
      if(divRelMovMostrarErrores(data.responseJSON)){
        $("#modalCargarRelMov").animate({ scrollTop: 0 }, "slow");
      }
    }
  });
});

$(document).on('click','.btn-eliminarFiscal',function(){
  const id=$(this).val();
  console.log(id);

  $.get('/relevamientos_movimientos/eliminarFiscalizacion/' + id,function(data){
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
    url: '/relevamientos_movimientos/buscarFiscalizaciones',
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
  if(estado > 2){
    fila.find('.btn-cargarRelMov').hide();
  }

  fila.find('.btn-imprimirRelMov').show();
  fila.find('.btn-eliminarFiscal').show();

  let iclass = 'fa-exclamation';
  let color = 'rgb(255,255,0)';
  let icon = fila.find('.estado i');
  icon.removeClass('fa-exclamation');
  if(estado == 1) { iclass = 'fa-plus'      ; color = 'rgb(150,150,150)';} // Generado
  else if(estado == 2) { iclass = 'fa-pencil-alt'; color = 'rgb(244,160,0)'  ;} // Cargando
  else if(estado == 3)      { iclass = 'fa-check'  ; color = 'rgb(66,133,244)' ;} // Finalizado
  else if(estado == 4) { iclass = 'fa-check'     ; color = 'rgb(76,175,80)'  ;} // Validado
  else                 { iclass = 'fa-minus'     ; color = 'rgb(0,0,0)'  ;} // Cualquier otro
  fila.find('.estado').attr('title',rel.estado_descripcion);
  icon.addClass(iclass).css('color',color);

  //Si el tipo de mov tiene direccion o es deprecado lo pongo en gris
  if(rel.puede_reingreso != 0 || rel.puede_egreso_temporal != 0 || rel.deprecado == 1){
    fila.find('button').not('.btn-imprimirRelMov,.btn-eliminarFiscal,.btn-verRelMov').remove();
    fila.find('td').css('color','rgb(150,150,150)').css('font-style','italic');
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

$('#datosUltimoEgresoTemporal').click(function(e){
  divRelMovSetear($(this).data('datos'));
  $(this).data('datos',null).attr('disabled',true); 
})
