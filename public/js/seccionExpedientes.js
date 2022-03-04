//Resaltar la sección en el menú del costado
$(document).ready(function() {
  $('#barraExpedientes').attr('aria-expanded','true');
  $('#expedientes').removeClass();
  $('#expedientes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Gestionar expedientes');
  $('#opcGestionarExpedientes').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcGestionarExpedientes').addClass('opcionesSeleccionado');

  $('#B_dtpFechaInicio').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 3,
  });

  $('#btn-buscar').trigger('click');
});

/* PESTAÑAS */
$('.tab').click(function(e){
  e.preventDefault();
  $('.seccion').hide();
  $($(this).attr('data-tab')).show();
  $('.tab').removeClass('navModalActivo');
  $(this).addClass('navModalActivo');
});

/////////////////////////////////// NOTAS ////////////////////////////////////

function obtenerCasinosSeleccionados(){
  return $('.casinosExp:checked').map(function(){return $(this).attr('id');}).toArray();
}

//Detectar casino de/seleccionado.
$(document).on('change','.casinosExp', function() {
  $('#notasMov').empty();  //Eliminar todas las notas de fila (menos el molde)
  $('#cantidad_movimientos').val(0);            //Resetear la cantidad de movimientos disponibles
  $('#btn-notaMov').parent().show();           //Mostrar el botón de agregar notas
  const casinos_seleccionados = obtenerCasinosSeleccionados();
  if (casinos_seleccionados.length == 0) {// Si hay 0 casinos seleccionados: limpiar las secciones de notas y mostrar mensajes.
    //limpiarSeccionNotas
    $('#notas').empty(); //Eliminar las filas de notas
    $('.mensajeNotas').show();
    $('.formularioNotas').hide();
  } else if (casinos_seleccionados.length == 1) {//Si hay un SOLO UN CASINO seleccionado: habilitar las dos pestañas
    //habilitarSeccionNotasMovimientos
    $('.mensajeNotas').hide();
    $('.formularioNotas').show();
    movimientosSinExpediente(casinos_seleccionados[0]);
    $('.mensajeNotas').hide();
    $('.formularioNotas').show();
  } else {//Si hay más casinos seleccionados: SOLO habilitar las notas nuevas
    //habilitarNotasNuevas
    $('#secNotas .mensajeNotas').hide();
    $('#secNotas .formularioNotas').show();
    $('#secMov .mensajeNotas').show();
    $('#secMov .formularioNotas').hide();
  }
});

function movimientosSinExpediente(id_casino) {
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: "GET",
    url: 'expedientes/movimientosSinExpediente/'+id_casino,
    success: function (data) {
      $('#cantidad_movimientos').val(data.logs.length);
      $('#movimientosDisponibles').find('option').remove();
      $('#movimientosDisponibles').append( $('<option>').val(0).text("Seleccione un movimiento"));
      data.logs.forEach(function(l){
        $('#movimientosDisponibles').append(
          $('<option>').val(l.id_log_movimiento).text(`${l.nombre} - ${l.descripcion} - ${l.fecha}`).attr('data-casino',l.id_casino)
        );
      });
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
}

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  const minimizar = $(this).data("minimizar");
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar",!minimizar);
});

//Quitar eventos de la tecla Enter y guardar
$('#collapseFiltros').on('keypress',function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
});

//Quitar eventos de la tecla Enter y guardar
$(document).on('keypress',function(e){
  if(e.which == 13 && $('#modalExpediente').is(':visible')) {
    e.preventDefault();
    $('#btn-guardar').click();
  }
});

//DATETIMEPICKER de las fechas
function habilitarDTP() {
  $('#dtpFechaPase,#dtpFechaInicio').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
  });
  $('#dtpFechaPase').data('datetimepicker').reset();
  $('#dtpFechaInicio').data('datetimepicker').reset();
}

//Agregar nueva disposicion en el modal
$('#btn-agregarDisposicion').click(function(){
  const disposicion = $('#moldeDisposicion').clone().removeAttr('id').show();
  datetimepicker(disposicion.find('.dtpFechaDisposicion'),disposicion.find('.fecha_disposicion'));
  $('#columnaDisposicion').append(disposicion);
});

// Agregar resolucion
$('#btn-agregarResolucion').on("click",function(e){
  const nro_res  = $('#nro_resolucion').val();
  const anio_res = $('#nro_resolucion_anio').val();
  if(nro_res == "" || anio_res == "") return;
  $('#nro_resolucion').val("");
  $('#nro_resolucion_anio').val("");
  const fila = $('#moldeResolucion').clone().removeAttr('id');
  fila.find('.nro_res').text(nro_res);
  fila.find('.anio_res').text(anio_res);
  $('#tablaResolucion').append(fila);
});

$(document).on('click','.borrarFila',function(){
  $(this).parent().parent().remove();
});

$(document).on('click','.borrarNota', function(){
  $(this).closest('.nota').remove();
});

$(document).on('click','.borrarNotaMov',function(){
  $(this).closest('.notaMov').remove();
  $('#cantidad_movimientos').val(parseInt($('#cantidad_movimientos').val()) + 1);
  $('#secMov .agregarNota').show(); //Mostrar el botón para agregar notas
  $(`#movimientosDisponibles option[value="${$(this).val()}"]`).show();//Mostrar el movimiento borrado nuevamente en el selector
});

$('#btn-notaNueva').click(function(e){
  e.preventDefault();
  const clonNota = $('#moldeNotaNueva').clone().removeAttr('id');
  datetimepicker(clonNota.find('.dtpFechaNota'),clonNota.find('.fecha_notaNueva'));
  $('#notas').append(clonNota);
});

let id_unico = 0;//Contador global que sirve para generar el id de linkfield (hack, si o si es por ID el linkfield en la libreria...)
function datetimepicker(dtp,linkfield){//Auxiliar que permite generar datetimepickers con link fields
  const id = id_unico + '_fecha';
  linkfield.attr('id', id);
  dtp.datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    linkField: id,
    linkFormat: "yyyy-mm-dd",
  });
  id_unico++;
}

$('#btn-notaMov').click(function(e){
  e.preventDefault();

  const cantidadMovimientos = $('#cantidad_movimientos').val();                   //Cantidad de movimientos disponibles para crear notas
  const id_movimiento = $('#movimientosDisponibles').val();                       //Se obtiene el id del movimiento

  if(cantidadMovimientos == 0 || id_movimiento == 0) return;

  $(`#movimientosDisponibles option[value="${id_movimiento}"]`).hide();  //Ocultar la opción del movimiento que se va a agregar
  $('#movimientosDisponibles').val(0);                                        //Cambiar el selector a la opción por defecto

  $.get('expedientes/obtenerMovimiento/' + id_movimiento, function(data) {    //Se trae toda la información del movimiento seleccionado
    const clonNota = $('#moldeNotaMov').clone().removeAttr('id');
    //Generar un ID (id_movimiento_fecha) para linkear el DTP con el input oculto que guarda el 'date' elegido
    datetimepicker(clonNota.find('.dtpFechaMov'),clonNota.find('.fecha_notaMov'));

    const fecha = convertirDate(data.movimiento.fecha);
    clonNota.find('.descripcionTipoMovimiento').val(`${fecha} - ${data.tipo} - ${data.casino.nombre}`).attr('id', id_movimiento);
    clonNota.find('.borrarNotaMov').val(id_movimiento);
    $('#notasMov').append(clonNota);                                    //Agregar la nota con el movimiento existente para editarla
    $('#cantidad_movimientos').val(cantidadMovimientos - 1);            //Disminuir en 1 el contador de cantidad de movimientos
    $('#btn-notaMov').parent().toggle(cantidadMovimientos > 1);        //Si no quedan más movimientos ocultar el botón de agregar
  });
});

function aux_modalExpediente(modo,data){
  limpiarModal();
  habilitarDTP();
  $('#navConfig').click(); //Empezar por la sección de configuración
  if(data != null){
    setearExpediente(data.expediente,data.casinos,data.resolucion,data.disposiciones,data.notas,data.notasConMovimientos);
  }

  const modificar_o_nuevo = modo == "modificar" || modo == "nuevo";
  habilitarControles(modificar_o_nuevo);
  $('#navMov').parent().toggle(modificar_o_nuevo);
  $('#notasNuevas').toggle(modificar_o_nuevo);
  $('.mensajeNotas').toggle(modificar_o_nuevo);
  
  const modificar_o_ver   = modo == "modificar" || modo == "ver";
  $('#notasCreadas').toggle(modificar_o_ver);
  $('.casinosExp').prop('disabled',modificar_o_ver);
  
  if(modificar_o_nuevo) $('.casinosExp').change();
  $('#modalExpediente').modal('show');
}

function modalExpediente(modo,id_expediente){
  if(modo == "nuevo"){
    $('#modalExpediente .modal-title').text('NUEVO EXPEDIENTE');
    $('#modalExpediente .modal-header').css('background-color','#6dc7be');
    $('#btn-guardar').removeClass().addClass('btn btn-successAceptar').val("nuevo");
    $('#btn-cancelar').text('CANCELAR');
  }
  if(modo == "modificar"){
    $('#modalExpediente .modal-title').text('MODIFICAR EXPEDIENTE');
    $('#modalExpediente .modal-header').css('background-color','#FFB74D');
    $('#btn-guardar').removeClass().addClass('btn btn-warningModificar').val("modificar");
    $('#btn-cancelar').text('CANCELAR');
  }
  else if(modo == "ver"){
    $('#modalExpediente .modal-title').text('VER EXPEDIENTE');
    $('#modalExpediente .modal-header').css('background-color','#4FC3F7');
    $('#btn-guardar').hide();
    $('#btn-cancelar').text('SALIR');
  }
  $('#btn-guardar').data('id_expediente',id_expediente);
  if(modo == "nuevo"){
    aux_modalExpediente(modo,null);
  }
  else{
    $.get("expedientes/obtenerExpediente/" + id_expediente, function(data){
      aux_modalExpediente(modo,data);
    });
  }
}

//Mostrar modal para agregar nuevo Expediente
$('#btn-nuevo').click(function(e){
  e.preventDefault();
  modalExpediente("nuevo",null);
});

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(e){
  e.preventDefault();
  modalExpediente("ver",$(this).val());
});

//Mostrar modal con los datos del Casino cargados
//"modificarExp" en vez de "modificar" porque al mensaje de exito se le agrega .modificar para ponerlo amarillo... y tira errores
$(document).on('click','.modificarExp',function(e){
  e.preventDefault();
  modalExpediente("modificar",$(this).val());  
});

//Borrar Casino y remover de la tabla
$(document).on('click','.eliminar',function(){
  $('#btn-eliminarModal').val($(this).val());
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } })
  $.ajax({
    type: "DELETE",
    url: "expedientes/eliminarExpediente/" + $(this).val(),
    success: function (data) {
      $('#btn-buscar').click();
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
});

//Cuando aprieta guardar en el modal de Nuevo/Modificar expediente
$('#btn-guardar').click(function (e) {
    $('#mensajeExito').hide();

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

    e.preventDefault();

    const resolucion = $('#tablaResolucion tbody tr').map(function(){
      return {
        id_resolucion:$(this).attr("id-resolucion"),
        nro_resolucion:$(this).find('td:eq(0)').text(),
        nro_resolucion_anio: $(this).find('td:eq(1)').text(),
      }
    }).toArray();

    const disposiciones = $('#columnaDisposicion .disposicion').map(function(){
      return {
        nro_disposicion: $(this).find('.nro_disposicion').val(),
        nro_disposicion_anio: $(this).find('.nro_disposicion_anio').val(),
        descripcion: $(this).find('#descripcion_disposicion').val(),
        id_tipo_movimiento: $(this).find('#tiposMovimientosDisp').val(),
      }
    }).toArray();
    
    const dispo_cargadas = $('#tablaDispoCreadas tbody tr').map(function(){
      return this.id;
    }).toArray();
    
    const notas = $('#notas .nota').map(function(){
      const mov = $(this).find('.tiposMovimientos').val();
      return {
        fecha: $(this).find('.fecha_notaNueva').val(),
        identificacion: $(this).find('.identificacion').val(),
        detalle: $(this).find('.detalleNota').val(),
        id_tipo_movimiento: mov != 0? mov : null,
      };
    }).toArray();

    const notas_asociadas = $('#notasMov .notaMov').map(function(){
      return {
        fecha: $(this).find('.fecha_notaMov').val(),
        identificacion: $(this).find('.identificacion').val(),
        detalle: $(this).find('.detalleNota').val(),
        id_log_movimiento: $(this).find('.descripcionTipoMovimiento').attr('id'),
      };
    }).toArray();

    const tablaNotas = $('#tablaNotasCreadas tbody tr').map(function(){
      return this.id;
    }).toArray();

    const formData = {
      id_expediente: $('#btn-guardar').data('id_expediente'),
      nro_exp_org: $('#nro_exp_org').val(),
      nro_exp_interno: $('#nro_exp_interno').val(),
      nro_exp_control: $('#nro_exp_control').val(),
      casinos: obtenerCasinosSeleccionados(),
      fecha_pase: $('#fecha_pase').val(),
      fecha_iniciacion: $('#fecha_inicio').val(),
      remitente: $('#remitente').val(),
      concepto: $('#concepto').val(),
      iniciador: $('#iniciador').val(),
      tema: $('#tema').val(),
      ubicacion_fisica: $('#ubicacion').val(),
      destino: $('#destino').val(),
      nro_cuerpos: $('#nro_cuerpos').val(),
      nro_folios: $('#nro_folios').val(),
      anexo: $('#anexo').val(),
      resolucion: resolucion,
      disposiciones: disposiciones,
      notas: notas,
      notas_asociadas: notas_asociadas,
      tablaNotas: tablaNotas,
      dispo_cargadas: dispo_cargadas
    };
    const state = $('#btn-guardar').val();
    $.ajax({
        type: "POST",
        url: 'expedientes/' + (state == "modificar"? 'modificarExpediente': 'guardarExpediente'),
        data: formData,
        dataType: 'json',
        beforeSend: function(data){
          console.log('Empezó');
          $('#modalExpediente').find('.modal-footer').children().hide();
          $('#modalExpediente').find('.modal-body').children().hide();
          $('#modalExpediente').find('.modal-body').children('#iconoCarga').show();
        },
        success: function (data) {
          $('#btn-buscar').trigger('click');
          if (state == "nuevo"){ //Si está agregando agrega una fila con el nuevo expediente
            $('#mensajeExito h3').text('Creación Exitosa');
            $('#mensajeExito p').text('El expediente fue creado con éxito');
            $('#mensajeExito .cabeceraMensaje').removeClass('modificar');
          }else{ //Si está modificando reemplaza la fila con el expediente modificado
            $('#mensajeExito h3').text('Modificación Exitosa');
            $('#mensajeExito p').text('El expediente fue modificado con éxito');
            $('#mensajeExito .cabeceraMensaje').addClass('modificar');
          }
          $('#modalExpediente').modal('hide');
          $('#mensajeExito').show();
        },
        error: function (data) {
            console.log('Error:', data);

            $('#modalExpediente').find('.modal-footer').children().show();
            $('#modalExpediente').find('.modal-body').children().show();
            $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();

            var response = JSON.parse(data.responseText);

            //Si hay algun campo vacio en nro_exp
            var nro_exp_org_vacio = typeof response.nro_exp_org != "undefined";
            var nro_exp_interno_vacio = typeof response.nro_exp_interno != "undefined";
            var nro_exp_control_vacio = typeof response.nro_exp_control != "undefined";

            //Ocultar errores
            $('#error_nav_config').hide();
            $('#error_nav_notas').hide();
            $('#error_nav_mov').hide();


            //////////////////////////  ALERTAS DE CONFIGURACIÓN /////////////////////////

            if(typeof response.casinos !== 'undefined'){
              mostrarErrorValidacion($('#contenedorCasinos'),"Debe seleccionar al menos un casino",true);
            }

            if (nro_exp_org_vacio || nro_exp_interno_vacio || nro_exp_control_vacio) {
                if(nro_exp_org_vacio) mostrarErrorValidacion($('#nro_exp_org'),response.nro_exp_org[0],false);
                if(nro_exp_interno_vacio) mostrarErrorValidacion($('#nro_exp_interno'),response.nro_exp_interno[0],false);
                if(nro_exp_control_vacio) mostrarErrorValidacion($('#nro_exp_control'),response.nro_exp_control[0],false);
                $('#error_nav_config').show();
            }

            if (typeof response.nro_cuerpos != "undefined") {
              mostrarErrorValidacion($('#nro_cuerpos'),response.nro_cuerpos[0],false);
              $('#error_nav_config').show();
            }

            if (typeof response.fecha_iniciacion != "undefined") {
              mostrarErrorValidacion($('#dtpFechaInicio input'),response.fecha_iniciacion[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.fecha_pase != "undefined") {
              mostrarErrorValidacion($('#dtpFechaPase input'),response.fecha_pase[0],false);
              $('#error_nav_config').show();
            }

            if (typeof response.destino != "undefined") {
              mostrarErrorValidacion($('#destino'),response.destino[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.ubicacion_fisica != "undefined") {
              mostrarErrorValidacion($('#ubicacion'),response.ubicacion_fisica[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.iniciador != "undefined") {
              mostrarErrorValidacion($('#iniciador'),response.iniciador[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.remitente != "undefined") {
              mostrarErrorValidacion($('#remitente'),response.remitente[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.concepto != "undefined") {
              mostrarErrorValidacion($('#concepto'),response.concepto[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.tema != "undefined") {
              mostrarErrorValidacion($('#tema'),response.tema[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.nro_cuerpos != "undefined") {
              mostrarErrorValidacion($('#nro_cuerpos'),response.nro_cuerpos[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.nro_folios != "undefined") {
              mostrarErrorValidacion($('#nro_folios'),response.nro_folios[0],false);
              $('#error_nav_config').show();
            }
            if (typeof response.anexo != "undefined") {
              mostrarErrorValidacion($('#anexo'),response.anexo[0],false);
              $('#error_nav_config').show();
            }
            var errorRes = ' ';
            if (typeof response["resolucion.nro_resolucion"] != "undefined") {
              mostrarErrorValidacion($('#nro_resolucion'),response['resolucion.nro_resolucion'][0],false);
              $('#error_nav_config').show();
            }
            if (typeof response["resolucion.nro_resolucion_anio"] != "undefined") {
              mostrarErrorValidacion($('#nro_resolucion_anio'),response['resolucion.nro_resolucion_anio'][0],false);
              $('#error_nav_config').show();
            }

            var i=0;
            $('#columnaDisposicion .disposicion').not('#moldeDisposicion').each(function(){
              if(typeof response['disposiciones.'+ i +'.nro_disposicion'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('.nro_disposicion'),response['disposiciones.'+ i +'.nro_disposicion'][0],false);
                $('#error_nav_config').show();
              }
              if(typeof response['disposiciones.'+ i +'.nro_disposicion_anio'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('.nro_disposicion_anio'),response['disposiciones.'+ i +'.nro_disposicion_anio'][0],false);
                $('#error_nav_config').show();
              }
              if(typeof response['disposiciones.'+ i +'.descripcion'] !== 'undefined'){
                mostrarErrorValidacion($(this).find('#descripcion_disposicion'),response['disposiciones.'+ i +'.descripcion'][0],false);
                $('#error_nav_config').show();
              }

              i++;
            })

            //////////////////////////  ALERTAS DE NOTAS /////////////////////////
            var i = 0;

            $('#notas .nota').each(function(){
                if(typeof response['notas.'+ i +'.fecha'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.dtpFechaNota input'),response['notas.'+ i +'.fecha'][0],false);
                  $('#error_nav_notas').show();
                }
                if(typeof response['notas.'+ i +'.identificacion'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.identificacion'),response['notas.'+ i +'.identificacion'][0],false);
                  $('#error_nav_notas').show();
                }
                if(typeof response['notas.'+ i +'.detalle'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.detalleNota'),response['notas.'+ i +'.detalle'][0],false);
                  $('#error_nav_notas').show();
                }
                if(typeof response['notas.'+ i +'.id_tipo_movimiento'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.tiposMovimientos'),response['notas.'+ i +'.id_tipo_movimiento'][0],false);
                  $('#error_nav_notas').show();
                }

                i++;
            });

            var j = 0;

            $('#notasMov .notaMov').each(function(){
                if(typeof response['notas_asociadas.'+ j +'.fecha'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.dtpFechaMov input'),response['notas_asociadas.'+ j +'.fecha'][0],false);
                  $('#error_nav_mov').show();
                }
                if(typeof response['notas_asociadas.'+ j +'.identificacion'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.identificacion'),response['notas_asociadas.'+ j +'.identificacion'][0],false);
                  $('#error_nav_mov').show();
                }
                if(typeof response['notas_asociadas.'+ j +'.detalle'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('.detalleNota'),response['notas_asociadas.'+ j +'.detalle'][0],false);
                  $('#error_nav_mov').show();
                }

                j++;
            });
        }
    });
});

//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  let size = 10;;
  //Fix error cuando librería saca los selectores
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  const formData = {
    nro_exp_org: $('#B_nro_exp_org').val(),
    nro_exp_interno: $('#B_nro_exp_interno').val(),
    nro_exp_control: $('#B_nro_exp_control').val(),
    id_casino: $('#B_casino').val(),
    fecha_inicio: $('#fecha_inicio1').val(),
    ubicacion_fisica: $('#B_ubicacion').val(),
    remitente: $('#B_remitente').val(),
    concepto: $('#B_concepto').val(),
    tema: $('#B_tema').val(),
    destino: $('#B_destino').val(),
    nota: $('#B_nota').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  $.ajax({
    type: 'POST',
    url: 'expedientes/buscarExpedientes',
    data: formData,
    dataType: 'json',
    success: function (data) {
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.expedientes.total,clickIndice);
      $('#cuerpoTabla').empty();

      for(let i = 0; i < data.expedientes.data.length; i++) {
        generarFilaTabla(data.expedientes.data[i]);
      }

      $('#herramientasPaginacion').generarIndices(page_number,page_size,data.expedientes.total,clickIndice);
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
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
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});
function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(expediente){
  const fila = $('#moldeFilaTabla').clone().removeAttr('id');
  fila.find('.expediente').text(`${expediente.nro_exp_org}-${expediente.nro_exp_interno}-${expediente.nro_exp_control}`);
  fila.find('.fecha').text(convertirDate(expediente.fecha_iniciacion) ?? '-');
  fila.find('.casino').text(expediente.nombre);
  fila.find('button').val(expediente.id_expediente);
  $('#cuerpoTabla').append(fila);
}

function habilitarControles(valor){
  $('#modalExpediente').find('input,select,textarea,button').prop('disabled',!valor).prop('readonly',!valor);
  $('#modalExpediente .modal-header').find('button').prop('disabled',false).prop('readonly',false);
  $('#btn-cancelar').prop('disabled',false).prop('readonly',false);
  $('#btn-agregarDisposicion,#btn-agregarMovimientos,#btn-guardar').toggle(valor);
  $('#modalExpediente .agregarNota').parent().toggle(valor);
  if(!valor){
    ($('#dtpFechaInicio').data('datetimepicker') ?? $()).remove();
    ($('#dtpFechaPase').data('datetimepicker') ?? $()).remove();
  }
}

function limpiarModal(){
  $('#tablaDispoCreadas tbody').empty();
  $('#mensajeExito').hide();
  $('#modalExpediente').find('.modal-footer').children().show();
  $('#modalExpediente').find('.modal-body').children().show();
  $('#modalExpediente').find('.modal-body').children('#iconoCarga').hide();
  $('#error_nav_config').hide();
  $('#error_nav_notas').hide();
  $('#error_nav_mov').hide();
  $('#frmExpediente').trigger('reset');
  $('.casinosExp').prop('checked',false).prop('disabled',false);
  $('#modalExpediente input').val('');
  $('#concepto').val(' ');
  $('#tema').val(' ');

  $('#columnaDisposicion .disposicion').not('#moldeDisposicion').remove();
  $('.filaNota').not('#moldeFilaNota').remove(); //Eliminar todas las notas creadas
  $('#notas').empty(); //Eliminar las filas de notas nuevas
  $('#notasMov').empty(); //Eliminar las filas de notas con movimientos existentes
  //limipar tabla de resoluciones
  $('#tablaResolucion tbody').empty();

  ocultarErrorValidacion($('#modalExpediente input,textarea,select,button'));
  ocultarErrorValidacion($('#contenedorCasinos'));

  $('#columna .Disposicion').each(function(){
    $(this).find('#nro_disposicion').removeClass('alerta');
    $(this).find('#nro_disposicion_anio').removeClass('alerta');
  });
  $('.alertaTabla').remove();
}

function setearExpediente(expediente,casinos,resolucion,disposiciones,notas,notasConMovimientos){
  $('#nro_exp_org').val(expediente.nro_exp_org);
  $('#nro_exp_control').val(expediente.nro_exp_control);
  $('#nro_exp_interno').val(expediente.nro_exp_interno);

  for (let i = 0; i < casinos.length; i++) {
    $('#contenedorCasinos').find(`#${casinos[i].id_casino}`).prop('checked',true).prop('disabled',true);
  }

  if(expediente.fecha_pase != null){
    $('#dtpFechaPase').data('datetimepicker').setDate(new Date(`${expediente.fecha_pase} 00:00`));
  }
  if(expediente.fecha_iniciacion != null){
    $('#dtpFechaInicio').data('datetimepicker').setDate(new Date(`${expediente.fecha_iniciacion} 00:00`));
  }
  $('#destino').val(expediente.destino);
  $('#ubicacion').val(expediente.ubicacion_fisica);
  $('#iniciador').val(expediente.iniciador);
  $('#remitente').val(expediente.remitente);
  $('#concepto').val(expediente.concepto);
  $('#tema').val(expediente.tema);
  $('#nro_cuerpos').val(expediente.nro_cuerpos);
  $('#nro_folios').val(expediente.nro_folios);
  $('#anexo').val(expediente.anexo);

  resolucion.forEach(res => {
    const fila = $('#moldeResolucionCargada').clone().removeAttr('id').attr("id-resolucion",res.id_resolucion);
    fila.find('.nro_res').text(res.nro_resolucion);
    fila.find('.anio_res').text(res.nro_resolucion_anio);
    $('#tablaResolucion').append(fila);
  });

  disposiciones.forEach(d => {
    const fila = $('#moldeDispoCargada').clone().attr('id', d.id_disposicion);
    fila.find('.nro_dCreada').text(d.nro_disposicion);
    fila.find('.anio_dCreada').text(d.nro_disposicion_anio);
    fila.find('.fecha_dCreada').text(d.fecha ?? " -- ")
    fila.find('.desc_dCreada').text(d.descripcion  ?? "Sin Descripción");
    fila.find('.mov_dCreada').text(d.descripcion_movimiento ?? " -- ");
    fila.find('button').val(d.id_disposicion);
    $('#tablaDispoCreadas tbody').append(fila);
  });

  for (let i = 0; i < notas.length; i++) {
    agregarNota(notas[i],false);
  }

  for (let j = 0; j < notasConMovimientos.length; j++) {
    agregarNota(notasConMovimientos[j],true);
  }

  //Si hay notas mostrarlas
  $('#notasCreadas').toggle((notas.length > 0) || (notasConMovimientos.length > 0));
}

function agregarNota(nota,conMovimiento) {
  var fila = $('#moldeFilaNota').clone();

  fila.show();
  fila.removeAttr('moldeFilaNota');

  fila.attr('id',nota.id_nota);
  fila.find('.borrarNota').attr('id',nota.id_nota);

  fila.find('.identificacion').text(nota.identificacion);
  fila.find('.fecha').text(convertirDate(nota.fecha));
  fila.find('.movimiento').text(conMovimiento? nota.movimiento: '-');
  fila.find('.detalle').text(nota.detalle);

  $('#tablaNotasCreadas tbody').append(fila);
}