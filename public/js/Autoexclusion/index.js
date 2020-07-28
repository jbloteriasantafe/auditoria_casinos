$(document).ready(function(){
  $('#barraMenu').attr('aria-expanded','true');
  $('.tituloSeccionPantalla').text('Alta de autoexcluidos');
  const input_fecha_iso = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  }
  $('#dtpFechaNacimiento').datetimepicker(input_fecha_iso);
  $('#dtpFechaAutoexclusionEstado').datetimepicker(input_fecha_iso);
  $('#dtpFechaAutoexclusion').datetimepicker(input_fecha_iso);
  $('#dtpFechaVencimiento').datetimepicker(input_fecha_iso);
  $('#dtpFechaFinalizacion').datetimepicker(input_fecha_iso);
  $('#dtpFechaCierreDefinitivo').datetimepicker(input_fecha_iso);
  $('#btn-buscar').trigger('click');
});

//PAGINACION
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden) {
    e.preventDefault();
    const deflt_size = isNaN($('#herramientasPaginacion').getPageSize())? 10 : $('#herramientasPaginacion').getPageSize();
    const sort_by = (columna != null) ? { columna: columna, orden: orden } 
    :{ 
      columna: $('#tablaAutoexcluidos .activa').attr('value'), 
      orden:   $('#tablaAutoexcluidos .activa').attr('estado')
    };
    const formData = {
        apellido: $('#buscadorApellido').val(),
        dni: $('#buscadorDni').val(),
        estado: $('#buscadorEstado').val(),
        casino: $('#buscadorCasino').val(),
        fecha_autoexclusion: $('#buscadorFechaAutoexclusion').val(),
        fecha_vencimiento: $('#buscadorFechaVencimiento').val(),
        fecha_finalizacion: $('#buscadorFechaFinalizacion').val(),
        fecha_cierre_definitivo: $('#buscadorFechaCierreDefinitivo').val(),
        page: (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage(),
        sort_by: sort_by,
        page_size: (page_size == null || isNaN(page_size)) ? deflt_size : page_size,
    }

    $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });
    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host + '/autoexclusion/buscarAutoexcluidos',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            $('#herramientasPaginacion')
                .generarTitulo(formData.page, formData.page_size, resultados.total, clickIndice);
            $('#herramientasPaginacion')
                .generarIndices(formData.page, formData.page_size, resultados.total, clickIndice);

            $('#cuerpoTabla tr').not('.filaTabla').remove();
            for (var i = 0; i < resultados.data.length; i++) {
                $('#tablaAutoexcluidos tbody').append(generarFilaTabla(resultados.data[i]));
            }
        },
        error: function(data) {
            console.log('Error:', data);
        }
    });
});

//Paginacion
$(document).on('click', '#tablaAutoexcluidos thead tr th[value]', function(e) {
    $('#tablaAutoexcluidos th').removeClass('activa');
    if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
        $(e.currentTarget).children('i')
            .removeClass('fa-sort').addClass('fa fa-sort-desc')
            .parent().addClass('activa').attr('estado', 'desc');
    } else {
        if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
            $(e.currentTarget).children('i')
                .removeClass('fa-sort-desc').addClass('fa fa-sort-asc')
                .parent().addClass('activa').attr('estado', 'asc');
        } else {
            $(e.currentTarget).children('i')
                .removeClass('fa-sort-asc').addClass('fa fa-sort')
                .parent().attr('estado', '');
        }
    }
    $('#tablaAutoexcluidos th:not(.activa) i')
        .removeClass().addClass('fa fa-sort')
        .parent().attr('estado', '');
    clickIndice(e,
        $('#herramientasPaginacion').getCurrentPage(),
        $('#herramientasPaginacion').getPageSize());
});

function clickIndice(e, pageNumber, tam) {
    if (e != null) {
        e.preventDefault();
    }
    var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
    var columna = $('#tablaAutoexcluidos .activa').attr('value');
    var orden = $('#tablaAutoexcluidos .activa').attr('estado');
    $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

function generarFilaTabla(unAutoexcluido) {
    let fila = $('#cuerpoTabla .filaTabla').clone().removeClass('filaTabla').show();
    fila.attr('data-id', unAutoexcluido.id_autoexcluido);
    fila.find('.casino').text(unAutoexcluido.casino);
    fila.find('.dni').text(unAutoexcluido.nro_dni);
    fila.find('.apellido').text(unAutoexcluido.apellido);
    fila.find('.nombres').text(unAutoexcluido.nombres);
    fila.find('.estado').text(unAutoexcluido.descripcion);
    fila.find('.fecha_ae').text(unAutoexcluido.fecha_ae);
    fila.find('button').val(unAutoexcluido.id_autoexcluido);
    if($('#id_casino option[value="'+unAutoexcluido.id_casino+'"]').length == 0){
      fila.find('#btnEditar').remove();
    }

    //si el estado del autoexcluido es distinto de 5 (vencido),
    //oculto el botón para generar la constancia de reingreso
    if (unAutoexcluido.id_nombre_estado != 5) {
      fila.find('#btnGenerarConstanciaReingreso').remove();
    }

    fila.css('display', 'flow-root');
    return fila;
}

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

$('#columna input').focusin(function(){
  $(this).removeClass('alerta');
});

//Botón agregar nuevo AE
$('#btn-agregar-ae').click(function(e){
  e.preventDefault();

  //vuelvo a step1
  $('.page').removeClass('active');
  $('.step1').addClass('active');

  //limpio el form
  $('#frmAgregarAE :input').val('');
  //Limpio el texto de archivos y muestro el input
  //boton -> div (esconder) -> span (limpiar) -> div -> div -> input (mostrar)
  $('#frmAgregarAE .sacarArchivo').parent().hide().find('span').text('')
  .parent().parent().find('input').show();

  //oculto el botón anterior
  $("#btn-prev").hide();

  //oculto botón guardar y muestro botón siguiente en caso que se encuentre oculto
  $("#btn-guardar").hide();
  $("#btn-next").show();

  //cargar select provincia
  cargarProvincias();

  //Restablezco los valores ininiciales de step
  $('.step').removeClass('finish');
  $('.step').removeClass('actived');
  $('#one').addClass('actived');

  //muestra modal
  $('#modalAgregarAE').modal('show');
});


//Botón subir solicitud AE
$('#btn-subir-solicitud-ae').click(function(e){
  e.preventDefault();
  //limpio el form
  $('#nroDniSubirSolicitudAE').val('');
  $('#solicitudAE').val('')
  //muestra modal
  $('#modalSubirSolicitudAE').modal('show');
});

//Botón ver formularios AE
$('#btn-ver-formularios-ae').click(function(e){
  e.preventDefault();
  //muestra modal
  $('#modalFormulariosAE').modal('show');
});


//función para autocompletar el input de provincia
function cargarProvincias(){
  const options = {
    url: "/js/Autoexclusion/provincias.json",
    listLocation: "provincias",
    getValue: "nombre",
    list: {
    	match: {
    		enabled: true
    	},
      onChooseEvent: function() {
			  cargarLocalidades();
        cargarLocalidadesVinculo();
		  }
    }
  };

  $("#nombre_provincia").easyAutocomplete(options);
  $("#nombre_provincia_vinculo").easyAutocomplete(options);
};

//función para autocompletar el input de localidad de los datos personales
function cargarLocalidades(){
  const nombre_provincia =  $("#nombre_provincia").val();
  const localidades = [];

  $.getJSON('/js/Autoexclusion/localidades.json', function(data) {
    $.each(data.localidades, function(key, value) {
      //si pertenece a la provincia seleccionada, la agrego
      if(value.provincia.nombre === nombre_provincia){
        var localidad = {
          id: value.id,
          nombre: value.nombre,
        }
        localidades.push(localidad);
      }
    }); // close each()
  }); // close getJSON()

  const options = {
    data: localidades,
  	getValue: "nombre",
    list: {
      match: {
        enabled: true
      }
    }
  };

  $("#nombre_localidad").easyAutocomplete(options);
};

//función para autocompletar el input de localidad del contacto
function cargarLocalidadesVinculo(){
  const nombre_provincia =  $("#nombre_provincia_vinculo").val();
  const localidades = [];

  $.getJSON('/js/Autoexclusion/localidades.json', function(data) {
    $.each(data.localidades, function(key, value) {
      //si pertenece a la provincia seleccionada, la agrego
      if(value.provincia.nombre === nombre_provincia){
        var localidad = {
          id: value.id,
          nombre: value.nombre,
        }
        localidades.push(localidad);
      }
    }); // close each()
  }); // close getJSON()

  const options = {
    data: localidades,
  	getValue: "nombre",
    list: {
      match: {
        enabled: true
      }
    }
  };

  $("#nombre_localidad_vinculo").easyAutocomplete(options);
};

//función para actualizar fechas
$( "#fecha_autoexlusion" ).change(function() {
  const fecha_autoexlusion = new Date($( "#fecha_autoexlusion" ).val());
  const convertir_fecha = function(f){
    const mes = ((f.getMonth() + 1) < 10 ? '0' : '') + (fecha_autoexlusion.getMonth() + 1);
    const dia =  (f.getDate() < 10 ? '0' : '') + fecha_autoexlusion.getDate();
    return f.getFullYear() + '-' + mes  + '-' + dia;
  }

  fecha_autoexlusion.setDate(fecha_autoexlusion.getDate() + 361);
  $( "#fecha_cierre_definitivo" ).val(convertir_fecha(fecha_autoexlusion));
  fecha_autoexlusion.setDate(fecha_autoexlusion.getDate() - 180);
  $( "#fecha_vencimiento_periodo" ).val(convertir_fecha(fecha_autoexlusion));
  fecha_autoexlusion.setDate(fecha_autoexlusion.getDate() - 30);
  $( "#fecha_renovacion" ).val(convertir_fecha(fecha_autoexlusion));
});

$("#btn-prev").on("click", function(){
    //si no es el primero, tengo anterior
    if($(".page.active").index() > 0){
        //cambio form active
        $(".page.active").removeClass("active").prev().addClass("active");
        //cambio step active
        $(".step.actived").removeClass("actived").removeClass("finish").prev().addClass("actived");
        //oculto botón anterior
        $("#btn-prev").hide();
      }

    //si le dio al boton de anterior y la pagina activa (despues de darle al boton)
    //es del step #2 (index1) o step #3 (index2), muestro los botones de ANTERIOR
    //y SIGUIENTE, y oculto el de ENVIAR
    if($(".page.active").index() == 1 || $(".page.active").index() == 2) {
        $("#btn-prev").show();
        $("#btn-next").show();
        $("#btn-guardar").hide();
    }
    setTimeout(function(){//@HACK: Elimino el delay que heredamos de barraNavegacion.js
      $('#btn-prev').prop('disabled',false);
    },250);
});
function mensajeError(msg){
  $('#mensajeError .textoMensaje').empty();
  $('#mensajeError .textoMensaje').append($('<h4>'+msg+'</h4>'));
  $('#mensajeError').hide();
  setTimeout(function() {
    $('#mensajeError').show();
  }, 250);
}

function limpiarNull(val){
  return val == null? '' : val;
}

function validarDNI(){
  if (isNaN($('#nro_dni').val()) && $('#nro_dni').val() != '') {
    mostrarErrorValidacion($('#nro_dni') , 'El número de DNI debe ser un dato de tipo numérico' , false);
    return 0;
  }
  let valid = 1;
  //si existe dni, precargo el formulario con los datos
  $.ajax({
    url: '/autoexclusion/existeAutoexcluido/' + $('#nro_dni').val(),
    async: false,
    type: "GET",
    success:     function (data) {
      if(data < 0){//Si existe, recibo el ID (negativo)
        $('#modalAgregarAE').modal('hide');
        mensajeError('Autoexcluido ya cargado en otro casino o no editable por el usuario');
        valid = 0;
        setTimeout(function(){
          mostrarAutoexcluido(-data);
        },500);
      }
      else if(data != 0){//Si es == 0, es uno nuevo por lo que no se hace nada
        //precargo el step de datos personales y de contacto
        $('#apellido').val(data.autoexcluido.apellido);
        $('#nombres').val(data.autoexcluido.nombres);
        $('#fecha_nacimiento').val(data.autoexcluido.fecha_nacimiento);
        $('#id_sexo').val(data.autoexcluido.id_sexo);
        $('#id_estado_civil').val(data.autoexcluido.id_estado_civil);
        $('#domicilio').val(data.autoexcluido.domicilio);
        $('#nro_domicilio').val(data.autoexcluido.nro_domicilio);
        $('#nombre_provincia').val(data.autoexcluido.nombre_provincia);
        $('#nombre_localidad').val(data.autoexcluido.nombre_localidad);
        $('#telefono').val(data.autoexcluido.telefono);
        $('#correo').val(data.autoexcluido.correo);
        $('#id_ocupacion').val(data.autoexcluido.id_ocupacion);
        $('#id_capacitacion').val(data.autoexcluido.id_capacitacion);

        if (data.datos_contacto != null) {
          $('#nombre_apellido').val(data.datos_contacto.nombre_apellido);
          $('#domicilio_vinculo').val(data.datos_contacto.domicilio);
          $('#nombre_provincia_vinculo').val(data.datos_contacto.nombre_provincia);
          $('#nombre_localidad_vinculo').val(data.datos_contacto.nombre_localidad);
          $('#telefono_vinculo').val(data.datos_contacto.telefono);
          $('#vinculo').val(data.datos_contacto.vinculo);
        }

        //precargo el step del estado
        $('#id_casino').val(data.estado.id_casino);
        $('#id_estado').val(data.estado.id_nombre_estado);
        $('#fecha_autoexlusion').val(data.estado.fecha_ae);
        $('#fecha_vencimiento_periodo').val(data.estado.fecha_vencimiento);
        $('#fecha_renovacion').val(data.estado.fecha_renovacion);
        $('#fecha_cierre_definitivo').val(data.estado.fecha_cierre_ae);

        //precargo el step de la encuesta
        if (data.encuesta != null) {
          $('#juego_preferido').val(data.encuesta.id_juego_preferido);
          $('#id_frecuencia_asistencia').val(data.encuesta.id_frecuencia_asistencia);
          $('#veces').val(data.encuesta.veces);
          $('#tiempo_jugado').val(data.encuesta.tiempo_jugado);
          $('#socio_club_jugadores').val(data.encuesta.club_jugadores);
          $('#juego_responsable').val(data.encuesta.juego_responsable);
          $('#autocontrol_juego').val(data.encuesta.autocontrol_juego);
          $('#como_asiste').val(data.encuesta.como_asiste);
          $('#recibir_informacion').val(data.encuesta.recibir_informacion);
          $('#medio_recepcion').val(data.encuesta.medio_recibir_informacion);
          $('#observaciones').val(data.encuesta.observacion);
        }

        if(data.importacion != null){
          const textoFoto1 = $('#foto1').parent().find('div');
          const textoFoto2 = $('#foto2').parent().find('div');
          const textoDNI = $('#scan_dni').parent().find('div');
          const textoAE = $('#solicitud_autoexclusion').parent().find('div');
          textoFoto1.find('span').text(limpiarNull(data.importacion.foto1));
          textoFoto2.find('span').text(limpiarNull(data.importacion.foto2));
          textoDNI.find('span').text(limpiarNull(data.importacion.scandni));
          textoAE.find('span').text(limpiarNull(data.importacion.solicitud_ae));
          textoFoto1.toggle(data.importacion.foto1 != null);
          textoFoto2.toggle(data.importacion.foto2 != null);
          textoDNI.toggle(data.importacion.scandni != null);
          textoAE.toggle(data.importacion.solicitud_ae != null);
          $('#foto1').toggle(data.importacion.foto1 == null);
          $('#foto2').toggle(data.importacion.foto2 == null);
          $('#scan_dni').toggle(data.importacion.scandni == null);
          $('#solicitud_autoexclusion').toggle(data.importacion.solicitud_ae == null);
        }
      }
    },
    error: function(data){
      console.log(data);
      mensajeError('Error al solicitar el DNI');
      valid = 0;
    }
  });

  return valid;
}

function validarDatosPersonales(){
  let valid = 1;
  $('.step2 input[required], select[required]').each(function(){
    if($(this).val() == ''){
      mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
      valid = 0;
    }
  });
  $('.step2 input[alpha]').each(function(){
    if(!/^[a-zA-Z\s]*$/.test($(this).val())){
      mostrarErrorValidacion($(this) , 'El campo no puede contener números' , false);
      valid = 0;
    }
  });
  $('.step2 input[numeric]').each(function(){
    if(!/^[0-9\s]*$/.test($(this).val())){
      mostrarErrorValidacion($(this) , 'El campo es numérico' , false);
      valid = 0;
    }
  });
  $('.step2 input[email]').each(function(){
    if(!$(this).val().includes('@')){
      mostrarErrorValidacion($(this) , 'Correo invalido' , false);
      valid = 0;
    }
  });
  return valid;
}

function validarExtensionArchivo (id) {
  if ($(id).val()) {
    let extension = $(id)[0].files[0].type;
    //@TODO: despues de hacer pruebas, quitar lo de PNG
    if (extension != 'image/jpeg' && extension != 'image/png' && extension != 'application/pdf') {
      return 0;
    }
  }
  return 1;
}

function validarFechaImagenes(){
  let valid = 1;
  $(".step3 :input").each(function(){
    if( $(this).val() == ''){
        mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
        valid = 0;
    }
  });
  if (validarExtensionArchivo('#foto1') == 0) {
    mostrarErrorValidacion($('#foto1'), 'El tipo de archivo debe ser de tipo JPG o PDF' , true);
    valid = 0;
  }
  if (validarExtensionArchivo('#foto2') == 0) {
    mostrarErrorValidacion($('#foto2'), 'El tipo de archivo debe ser de tipo JPG o PDF' , true);
    valid = 0;
  }
  if (validarExtensionArchivo('#solicitud_autoexclusion') == 0) {
    mostrarErrorValidacion($('#solicitud_autoexclusion'), 'El tipo de archivo debe ser de tipo JPG o PDF' , true);
    valid = 0;
  }
  if (validarExtensionArchivo('#scan_dni') == 0) {
    mostrarErrorValidacion($('#scan_dni'), 'El tipo de archivo debe ser de tipo JPG o PDF' , true);
    valid = 0;
  }
  return valid;
}

//botón siguiente modal agregar ae
$("#btn-next").on("click", function(){
  let step = $(".page.active").index() + 1;
  let valid = 1;

  switch(step){
    case 1:
      valid = validarDNI();
      break;
    case 2:
      valid = validarDatosPersonales()
      break;
    case 3:
      valid = validarFechaImagenes();
      break;
    default:
      return;
  }

  //verifico que el step sea valido para avanzar y, si no es el último step, tengo siguiente:
  if(valid==1 && step<4 && $(".page.active").index() < $(".page").length-1){
    //cambio form active
    $(".page.active").removeClass("active").next().addClass("active");
    //cambio step active
    $(".step.actived").removeClass("actived").addClass("finish").next().addClass("actived");
    //muestro botón anterior
    $("#btn-prev").show();
  }

  //si llegue al final, muestro botón de guardar y oculto el de siguiente
  if( $(".page.active").index() == $(".page").length-1 ){
    $("#btn-guardar").show();
    $("#btn-next").hide();
  }
  setTimeout(function(){//@HACK: Elimino el delay que heredamos de barraNavegacion.js
    $('#btn-next').prop('disabled',false);
  },250);
});

function strRequest(objectName,keyname){
  return objectName + '[' + keyname + ']';
}
function clearNullUndef(val){
  return (typeof(val) !== "undefined" && val !== null)? val : '';
}
//botón guardar ae
$('#btn-guardar').click(function (e) {
    // Esto esta hecho de esta forma "a pata" porque al enviar datos ademas de archivos,
    // No puede "JSONear" los archivos y da error, se necesita mandarlo como request comun con Content-type = text/html
    // Pero en esta modalidad no hace el pasaje automatico de objeto -> dato y manda un [Object object]
    // Se hacen dos funciones auxiliares para hacer esto. -Octavio 17 Julio 2020
    const formData = new FormData();

    const ae_datos =  {
      nro_dni: $('#nro_dni').val(),
      apellido: $('#apellido').val(),
      nombres: $('#nombres').val(),
      fecha_nacimiento: $('#fecha_nacimiento').val(),
      id_sexo: $('#id_sexo').val(),
      id_estado_civil: $('#id_estado_civil').val(),
      domicilio: $('#domicilio').val(),
      nro_domicilio: $('#nro_domicilio').val(),
      nombre_localidad: $('#nombre_localidad').val(),
      nombre_provincia: $('#nombre_provincia').val(),
      telefono: $('#telefono').val(),
      correo: $('#correo').val(),
      id_ocupacion: $('#id_ocupacion').val(),
      id_capacitacion: $('#id_capacitacion').val(),
    }

    for(const key in ae_datos){
      formData.append(strRequest('ae_datos',key),clearNullUndef(ae_datos[key]));
    }

    const ae_datos_contacto =  {
      nombre_apellido: $('#nombre_apellido').val(),
      domicilio: $('#domicilio_vinculo').val(),
      nombre_localidad: $('#nombre_localidad_vinculo').val(),
      nombre_provincia: $('#nombre_provincia_vinculo').val(),
      telefono: $('#telefono_vinculo').val(),
      vinculo: $('#vinculo').val(),
    }
    for(const key in ae_datos_contacto){
      formData.append(strRequest('ae_datos_contacto',key),clearNullUndef(ae_datos_contacto[key]));
    }

    const ae_estado = {
      id_casino: $('#id_casino').val(),
      id_nombre_estado: $('#id_estado').val(),
      fecha_ae: $('#fecha_autoexlusion').val(),
      fecha_vencimiento: $('#fecha_vencimiento_periodo').val(),
      fecha_renovacion: $('#fecha_renovacion').val(),
      fecha_cierre_ae: $('#fecha_cierre_definitivo').val(),
    }
    for(const key in ae_estado){
      formData.append(strRequest('ae_estado',key),ae_estado[key]);
    }

    const ae_encuesta = {
      id_juego_preferido: $('#juego_preferido').val(),
      id_frecuencia_asistencia: $('#id_frecuencia_asistencia').val(),
      veces: $('#veces').val(),
      tiempo_jugado: $('#tiempo_jugado').val(),
      club_jugadores: $('#socio_club_jugadores').val(),
      juego_responsable: $('#juego_responsable').val(),
      autocontrol_juego: $('#autocontrol_juego').val(),
      recibir_informacion: $('#recibir_informacion').val(),
      medio_recibir_informacion: $('#medio_recepcion').val(),
      como_asiste: $('#como_asiste').val(),
      observacion: $('#observaciones').val(),
    }
    for(const key in ae_encuesta){
      formData.append(strRequest('ae_encuesta',key),clearNullUndef(ae_encuesta[key]));
    }

    const ae_importacion_cargado = {
      foto1                : $('#foto1').parent().find('span').text().length != 0,
      foto2                : $('#foto2').parent().find('span').text().length != 0,
      solicitud_ae         : $('#solicitud_autoexclusion').parent().find('span').text().length != 0,
      solicitud_revoacion  : false,
      scandni              : $('#scan_dni').parent().find('span').text().length != 0,
    }
    const ae_importacion = {
      foto1                : $('#foto1')[0].files[0],
      foto2                : $('#foto2')[0].files[0],
      solicitud_ae         : $('#solicitud_autoexclusion')[0].files[0],
      solicitud_revocacion : null,
      scandni              : $('#scan_dni')[0].files[0]
    }
    for(const key in ae_importacion){
      const cargado = ae_importacion_cargado[key];
      const file_val = clearNullUndef(ae_importacion[key]);
      if(cargado){//Si ya tiene cargado un archivo, mando vacio
        formData.append(strRequest('ae_importacion',key),'');
      }
      else if(file_val != ''){//Si mando un archivo nuevo,
        formData.append(strRequest('ae_importacion',key),file_val);
      }
    }

    //url de destino, dependiendo si se esta creando o modificando una sesión
    //dependiendo el valor del botón guarda o edita
    $.ajax({
        type: "POST",
        url: 'autoexclusion/agregarAE',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        success: function (data) {
            $('#mensajeExito p').text('La autoexclusión fue '+(data.nuevo? 'GUARDADA' : 'EDITADA') + ' correctamente.');
            $('#modalAgregarAE').modal('hide');
            $('#btn-buscar').trigger('click'); //hago un trigger al botón buscar asi actualiza la tabla sin recargar la pagina
            $('#mensajeExito').show(); //mostrar éxito
        },
        error: function (data) {
          console.log(data);
        }
    });
});

//botón subir archivo solicitud ae
$('#btn-subir-archivo').click(function (e) {
    //guardo el archivo en un formdata
    const formData = new FormData();
    formData.append('solicitudAE', $('#solicitudAE')[0].files[0]);
    formData.append('nro_dni', $('#nroDniSubirSolicitudAE').val());

    $.ajax({
        type: "POST",
        url: 'http://' + window.location.host + '/autoexclusion/subirSolicitudAE',
        data: formData,
        dataType: 'json',
        contentType: false,
        processData: false,
        cache: false,
        success: function (data) {
          $('#mensajeExito P').text('La solicitud de autoexclusión fue subida correctamente.');
          $('#mensajeExito div').css('background-color','#4DB6AC');
          $('#modalSubirSolicitudAE').modal('hide');
          $('#mensajeExito').show(); //mostrar éxito
        },
        error: function (data) {
          console.log(data);
        }
    });

});

function mostrarAutoexcluido(id_autoexcluido){
  $('#modalVerMas input:checked').prop('checked' ,false);
  $('#modalVerMas input').val('');
  $('#modalVerMas select').val('');
  $.get('/autoexclusion/buscarAutoexcluido/' + id_autoexcluido, function (data) {
    $('#infoApellido').val(data.autoexcluido.apellido);
    $('#infoNombres').val(data.autoexcluido.nombres);
    $('#infoFechaNacimiento').val(data.autoexcluido.fecha_nacimiento);
    $('#infoDni').val(data.autoexcluido.nro_dni);
    $('#infoSexo').val(data.autoexcluido.id_sexo);
    $('#infoEstadoCivil').val(data.autoexcluido.id_estado_civil);
    $('#infoDomicilio').val(data.autoexcluido.domicilio);
    $('#infoNroDomicilio').val(data.autoexcluido.nro_domicilio);
    $('#infoProvincia').val(data.autoexcluido.nombre_provincia);
    $('#infoLocalidad').val(data.autoexcluido.nombre_localidad);
    $('#infoTelefono').val(data.autoexcluido.telefono);
    $('#infoEmail').val(data.autoexcluido.correo);
    $('#infoOcupacion').val(data.autoexcluido.id_ocupacion);
    $('#infoCapacitacion').val(data.autoexcluido.id_capacitacion);

    if(data.datos_contacto != null){
      $('#infoNombreApellidoVinculo').val(data.datos_contacto.nombre_apellido);
      $('#infoDomiclioVinculo').val(data.datos_contacto.domicilio);
      $('#infoProvinciaVinculo').val(data.datos_contacto.nombre_provincia);
      $('#infoLocalidadVinculo').val(data.datos_contacto.nombre_localidad);
      $('#infoTelefonoVinculo').val(data.datos_contacto.telefono);
      $('#infoVinculo').val(data.datos_contacto.vinculo);
    }

    $('#infoCasino').val(data.estado.id_casino);
    $('#infoEstado').val(data.estado.id_nombre_estado);
    $('#infoFechaAutoexclusion').val(data.estado.fecha_ae);
    $('#infoFechaVencimiento').val(data.estado.fecha_vencimiento);
    $('#infoFechaRenovacion').val(data.estado.fecha_renovacion);
    $('#infoFechaCierreDefinitivo').val(data.estado.fecha_cierre_ae);

    if (data.encuesta != null) {
      $('#infoJuegoPreferido').val(data.encuesta.id_juego_preferido);
      $('#infoFrecuenciaAsistencia').val(data.encuesta.id_frecuencia_asistencia);
      $('#infoVeces').val(data.encuesta.veces);
      $('#infoTiempoJugado').val(data.encuesta.tiempo_jugado);
      $('#infoSocioClubJugadores').val(data.encuesta.club_jugadores);
      $('#infoJuegoResponsable').val(data.encuesta.juego_responsable);
      $('#infoAutocontrol').val(data.encuesta.autocontrol_juego);
      $('#infoComoAsiste').val(data.encuesta.como_asiste);
      $('#infoRecibirInformacion').val(data.encuesta.recibir_informacion);
      $('#infoMedioRecepcion').val(data.encuesta.medio_recibir_informacion);
      $('#infoObservaciones').val(data.encuesta.observacion);
    }
    else {
      $('#infoJuegoPreferido').append($('<option>')
          .attr('id','-1')
          .attr('value','-1')
          .val(-1)
          .text('Info. no ingresada')
      );

      $('#infoFrecuenciaAsistencia').append($('<option>')
          .attr('id','-1')
          .attr('value','-1')
          .val(-1)
          .text('Info. no ingresada')
      );

      $('#infoComoAsiste').append($('<option>')
          .attr('id','-1')
          .attr('value','-1')
          .val(-1)
          .text('Información no ingresada')
      );

      $('#infoJuegoPreferido').val(-1);
      $('#infoFrecuenciaAsistencia').val(-1);
      $('#infoVeces').val('Info. no ingresada');
      $('#infoTiempoJugado').val('Info. no ingresada');
      $('#infoSocioClubJugadores').val('Información no ingresada');
      $('#infoJuegoResponsable').val('Información no ingresada');
      $('#infoAutocontrol').val('Información no ingresada');
      $('#infoComoAsiste').val(-1);
      $('#infoRecibirInformacion').val('Información no ingresada');
      $('#infoMedioRecepcion').val('Información no ingresada');
      $('#infoObservaciones').val('Información no ingresada');
    }

    //seteo en el value de los botones de ver mas el id de la importacion, para después
    //buscar en el backend los paths a los archivos y mostrarlos oportunamente
    $('.archivosImportados button').each(function(idx, c) { $(c).val(data.importacion.id_importacion); });
    $('.archivosImportados .foto1').prop('disabled', data.importacion.foto1 === null);
    $('.archivosImportados .foto2').prop('disabled', data.importacion.foto2 === null);
    $('.archivosImportados .scandni').prop('disabled', data.importacion.scandni === null);
    $('.archivosImportados .solicitud_ae').prop('disabled', data.importacion.solicitud_ae === null);

    $('#modalVerMas').modal('show');
  });
}

$(document).on('click', '#btnVerMas', function(e){
  e.preventDefault();
  mostrarAutoexcluido($(this).val());
});

$(document).on('click', '#btnEditar', function(e){
  e.preventDefault();
  const dni = $(this).parent().parent().find('.dni').text();
  $('#btn-agregar-ae').click();
  setTimeout(function(){
    $('#nro_dni').val(dni);
    $('#btn-next').click();
  },500);
});

$(document).on('click', '#btnGenerarSolicitudAutoexclusion', function(e){
  e.preventDefault();
  window.open('autoexclusion/generarSolicitudAutoexclusion/' + $(this).val(), '_blank');
});

$(document).on('click', '#btnGenerarConstanciaReingreso', function(e){
  e.preventDefault();
  window.open('autoexclusion/generarConstanciaReingreso/' + $(this).val(), '_blank');
});

//Salir del modal ver mas
$('#btn-salir').click(function() {
  $('#modalVerMas').modal('hide');
});

//Mostrar archivos ver mas
$('.btn-ver-mas').click(function() {
  let tipo_archivo = null;
  if($(this).hasClass('foto1')) tipo_archivo = 'foto1';
  else if($(this).hasClass('foto2')) tipo_archivo = 'foto2';
  else if($(this).hasClass('scandni')) tipo_archivo = 'scandni';
  else if($(this).hasClass('solicitud_ae')) tipo_archivo = 'solicitud_ae';
  if(tipo_archivo === null) return;
  
  window.open('autoexclusion/mostrarArchivo/' + $(this).val() + '/' + tipo_archivo, '_blank');
});

//Mostrar formularios
$('.btn-ver-formulario').click(function() {
  window.open('autoexclusion/mostrarFormulario/' + $(this).attr('id'), '_blank');
});

$('.sacarArchivo').click(function(){
  const div = $(this).parent();
  const input = div.parent().find('input');
  div.hide();
  input.show();
  div.find('span').text('');
});

$("#contenedorFiltros input").on('keypress',function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
});