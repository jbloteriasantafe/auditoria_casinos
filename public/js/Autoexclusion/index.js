$(document).ready(function(){
  $('#barraMenu').attr('aria-expanded','true');
  $('.tituloSeccionPantalla').text('Autoexcluidos');
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
  
  $('#dtpFechaAutoexclusionEstado').datetimepicker(input_fecha_iso);
  $('#dtpFechaNacimiento').datetimepicker(input_fecha_iso);

  const input_fecha = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd/mm/yy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  }

  $('#dtpFechaAutoexclusionD').datetimepicker(input_fecha);
  $('#dtpFechaVencimientoD').datetimepicker(input_fecha);
  $('#dtpFechaRenovacionD').datetimepicker(input_fecha);
  $('#dtpFechaCierreDefinitivoD').datetimepicker(input_fecha);
  $('#dtpFechaAutoexclusionH').datetimepicker(input_fecha);
  $('#dtpFechaVencimientoH').datetimepicker(input_fecha);
  $('#dtpFechaRenovacionH').datetimepicker(input_fecha);
  $('#dtpFechaCierreDefinitivoH').datetimepicker(input_fecha);
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

    const iso = function(dtp){
      //getDate me retorna hoy si esta vacio, lo tengo que verificar
      if(dtp.find('input').val().length == 0) return "";
      const date = dtp.data("datetimepicker").getDate();
      const y = date.getFullYear();
      const m = date.getMonth()+1;
      const d = date.getDate();
      return y + (m<10?'-0':'-') + m + (d<10?'-0':'-') + d;
    }

    const formData = {
        apellido: $('#buscadorApellido').val(),
        dni: $('#buscadorDni').val(),
        estado: $('#buscadorEstado').val(),
        casino: $('#buscadorCasino').val(),
        fecha_autoexclusion_d:     iso($('#dtpFechaAutoexclusionD')),
        fecha_vencimiento_d:       iso($('#dtpFechaVencimientoD')),
        fecha_renovacion_d:        iso($('#dtpFechaRenovacionD')),
        fecha_cierre_definitivo_d: iso($('#dtpFechaCierreDefinitivoD')),
        fecha_autoexclusion_h:     iso($('#dtpFechaAutoexclusionH')),
        fecha_vencimiento_h:       iso($('#dtpFechaVencimientoH')),
        fecha_renovacion_h:        iso($('#dtpFechaRenovacionH')),
        fecha_cierre_definitivo_h: iso($('#dtpFechaCierreDefinitivoH')),
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

function generarFilaTabla(ae) {
    let fila = $('#cuerpoTabla .filaTabla').clone().removeClass('filaTabla').show();
    fila.attr('data-id', ae.id_autoexcluido);
    fila.find('.casino').text(ae.casino).attr('title',ae.casino);
    fila.find('.dni').text(ae.nro_dni).attr('title',ae.nro_dni);
    fila.find('.apellido').text(ae.apellido).attr('title',ae.apellido);
    fila.find('.nombres').text(ae.nombres).attr('title',ae.nombres);
    fila.find('.estado').text(ae.desc_estado).attr('title',ae.desc_estado);

    //Lo cambio a otro formato porque es mas corto que entra en pantalla mas chicas
    const convertir_fecha = function(fecha){
      yyyymmdd = fecha.split('-');
      return yyyymmdd[2] + '/' + yyyymmdd[1] + '/' + yyyymmdd[0].substring(2);
    }
    fila.find('.fecha_ae').text(convertir_fecha(ae.fecha_ae)).attr('title',ae.fecha_ae);
    fila.find('.fecha_renovacion').text(convertir_fecha(ae.fecha_renovacion)).attr('title',ae.fecha_renovacion);
    fila.find('.fecha_vencimiento').text(convertir_fecha(ae.fecha_vencimiento)).attr('title',ae.fecha_vencimiento);
    fila.find('.fecha_cierre_ae').text(convertir_fecha(ae.fecha_cierre_ae)).attr('title',ae.fecha_cierre_ae);

    fila.find('button').val(ae.id_autoexcluido);
    fila.find('button').attr('estado-nuevo',ae.estado_transicionable);

    if($('#id_casino option[value="'+ae.id_casino+'"]').length == 0){
      fila.find('#btnEditar').remove();
    }
    // 1 Vigente, 2 Renovado, 3 Pendiente Valid, 4 Fin por AE,
    // 5 Vencido, 6 RES983 Pendiente, 7 RES983 Verificado

    //si no esta vencido oculto el botón constancia de reingreso
    if (ae.id_nombre_estado != 5) {
      fila.find('#btnGenerarConstanciaReingreso').remove();
    }
    //si no esta finalizado por AE, oculto el boton
    if (ae.id_nombre_estado != 4) {
      fila.find('#btnGenerarSolicitudFinalizacion').remove();
    }

    if(!ae.es_primer_ae){
      fila.find('td').css('font-style','italic');
      fila.find('.fecha_renovacion').text('-').attr('title','-');
      fila.find('.fecha_vencimiento').text('-').attr('title','-');
    }

    if(ae.id_nombre_estado == ae.estado_transicionable){ //El estado esta ya correcto
      fila.find('#btnCambiarEstado').remove();
    }
    //Pasar a vigente o renovado, segun si es primero o ya tuvo AE
    else if((ae.id_nombre_estado == 3 || ae.id_nombre_estado == 6) && (ae.estado_transicionable == 1 || ae.estado_transicionable == 2)){
      fila.find('#btnCambiarEstado').attr('title','VALIDAR').find('i').addClass('fa-check');
    }
    else if(ae.estado_transicionable == 4){
      fila.find('#btnCambiarEstado').attr('title','FINALIZAR POR AE').find('i').addClass('fa-ban');
    }
    else if(ae.estado_transicionable == 2){
      fila.find('#btnCambiarEstado').attr('title','RENOVAR').find('i').addClass('fa-undo');
    }
    else if(ae.estado_transicionable == 5){
      fila.find('#btnCambiarEstado').attr('title','CERRAR POR VENCIMIENTO').find('i').addClass('fa-lock');
    }
    else {
      fila.find('#btnCambiarEstado').remove();
      fila.find('.estado').css('color','red');//Lo marco como inconsistente, hay algun error de logica en algun lado.
    }

    const archivos = {
      foto1: 'FOTO #1',
      foto2: 'FOTO #2',
      scandni: 'SCAN DNI',
      solicitud_ae: 'SOLICITUD AE',
      solicitud_revocacion: 'SOLICITUD FINALIZACIÓN',
      caratula: 'CARATULA'
    };
    const ul = $('<ul>').css('text-align','left');
    for(const key in archivos){//Si ya esta subido el archivo, no lo agrego
      if(ae[key] !== null) continue;
      const item = $('<a>').addClass('subirArchivo').attr('data-tipo',key).text(archivos[key]);
      ul.append($('<li>').append(item));
    }
    if(ae.id_nombre_estado != 4){//Si no esta finalizado por AE no dejo subir la solicitud de fin.
      ul.find('a[data-tipo="solicitud_revocacion"]').parent().remove();
    }
    fila.find('#btnSubirArchivos').attr('data-content',ul[0].outerHTML);
    fila.find('#btnSubirArchivos').popover();
    if(ul.find('li').length == 0){//Si ya tiene todos los archivos saco el boton
      fila.find('#btnSubirArchivos').remove();
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

function modalAgregarEditarAE(dni,id_autoexcluido = null){
  //vuelvo a step1
  $('.page').removeClass('active');
  $('.step1').addClass('active');

  $('#frmAgregarAE :input').val('');
  $('#hace_encuesta').prop('checked', true).change();
  ocultarErrorValidacion($('#frmAgregarAE :input'));
  //Limpio el texto de archivos y muestro el input
  //boton -> div (esconder) -> a (limpiar) -> div -> div -> input (mostrar)
  $('#frmAgregarAE .sacarArchivo').parent().hide().find('a').text('').attr('href','')
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

  $('#nro_dni').val(dni);
  $('#modalAgregarAE .modal-title').text('| AGREGAR AUTOEXCLUIDO');
  $('#modalAgregarAE').attr('modo','agregar');
  $('#modalAgregarAE').attr('id-autoexcluido',-1);
  if(id_autoexcluido !== null){
    $('#modalAgregarAE .modal-title').text('| EDITAR AUTOEXCLUIDO');
    $('#modalAgregarAE').attr('modo','editar');
    $('#modalAgregarAE').attr('id-autoexcluido',id_autoexcluido);
    setTimeout(function(){
      $("#btn-next").click();
    },250);
  }
  //muestra modal
  $('#modalAgregarAE').modal('show');
}
//Botón agregar nuevo AE
$('#btn-agregar-ae').click(function(e){
  e.preventDefault();
  modalAgregarEditarAE("");
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
  const iso = function(f){
    const mes = f.getMonth()+1;
    const dia = f.getDate();
    return f.getFullYear() + (mes<10?'-0':'-') + mes + (dia<10?'-0':'-') + dia;
  }
  const fecha_autoexlusion        = new Date($( "#fecha_autoexlusion" ).val());
  const fecha_renovacion          = new Date(fecha_autoexlusion.getTime());
  const fecha_vencimiento_periodo = new Date(fecha_autoexlusion.getTime());
  const fecha_cierre_definitivo   = new Date(fecha_autoexlusion.getTime());
  fecha_renovacion.setDate(fecha_autoexlusion.getDate() + 150);
  fecha_vencimiento_periodo.setDate(fecha_autoexlusion.getDate() + 180);
  fecha_cierre_definitivo.setDate(fecha_autoexlusion.getDate() + 365);
  $( "#fecha_renovacion" ).val(iso(fecha_renovacion));
  $( "#fecha_vencimiento_periodo" ).val(iso(fecha_vencimiento_periodo));
  $( "#fecha_cierre_definitivo" ).val(iso(fecha_cierre_definitivo));
});

$("#btn-prev").on("click", function(){
    //si no es el primero, tengo anterio
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
function mensajeExito(msg){
  $('#mensajeExito p').text(msg);
  $('#mensajeExito').hide();
  setTimeout(function() {
    $('#mensajeExito').show();
  }, 250);
}

function limpiarNull(val){
  return val == null? '' : val;
}

$('#nro_dni').change(function(){
  const dni = parseInt($(this).val());
  //Obtenido regresionando los datos de AE con sus año-mes de nacimiento
  const añofloat = (dni / 100000.0)*0.1439930709+1939.7556710372;
  const año = Math.floor(añofloat);
  const aux = añofloat - año;
  const mes = Math.floor(aux*11 + 1);
  const to_iso = function(m,y){
    //@HACK timezone de Argentina, supongo que esta bien porque el servidor esta en ARG
    return y+(m < 10? '-0' : '-')+m+'-01'+'T00:00:00.000-03:00';
  }
  const fecha = new Date(to_iso(mes,año));
  $('#dtpFechaNacimiento').data('datetimepicker').setDate(fecha);
})
function validarDNI(){
  if (isNaN($('#nro_dni').val()) && $('#nro_dni').val() != '') {
    mostrarErrorValidacion($('#nro_dni') , 'El número de DNI debe ser un dato de tipo numérico' , false);
    return 0;
  }
  let valid = 1;
  //si existe dni, precargo el formulario con los datos
  let url = '/autoexclusion/existeAutoexcluido/' + $('#nro_dni').val();
  if($('#modalAgregarAE').attr('modo') == 'editar'){
    url = '/autoexclusion/buscarAutoexcluido/' + $('#modalAgregarAE').attr('id-autoexcluido');
  }
  $.ajax({
    url: url,
    async: false,
    type: "GET",
    success:     function (data) {
      console.log(typeof data);
      if(typeof data == "string"){
        if(data > 0){//ID
          $('#modalAgregarAE').modal('hide');
          mensajeError('Autoexcluido ya cargado y en vigencia');
          valid = 0;
          setTimeout(function(){
            mostrarAutoexcluido(data);
          },500);
        }
        else if(data == "0"){//AE nuevo, muestro las fechas de renovacion/vencimiento
          $('#fecha_vencimiento_periodo').parent().css('opacity','');
          $('#fecha_renovacion').parent().css('opacity','');
        }
        else if(data < 0){//El AE es cargable pero ya tuvo uno, le escondo las fechas de renovacion/vencimiento
          $('#fecha_vencimiento_periodo').parent().css('opacity','0');
          $('#fecha_renovacion').parent().css('opacity','0');
        }
      }
      else if(typeof data == "object"){
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

        if(data.es_primer_ae){
          $('#fecha_vencimiento_periodo').parent().css('opacity','');
          $('#fecha_renovacion').parent().css('opacity','');
        }
        else{
          $('#fecha_vencimiento_periodo').parent().css('opacity','0');
          $('#fecha_renovacion').parent().css('opacity','0');
        }

        //precargo el step de la encuesta
        if (data.encuesta != null) {
          $('#hace_encuesta').prop('checked', true).change();
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
        else{
          $('#hace_encuesta').prop('checked',false).change();
        }

        if(data.importacion != null){
          const textoFoto1 = $('#foto1').parent().find('div');
          const textoFoto2 = $('#foto2').parent().find('div');
          const textoDNI = $('#scan_dni').parent().find('div');
          const textoAE = $('#solicitud_autoexclusion').parent().find('div');
          const textoFIN = $('#solicitud_revocacion').parent().find('div');
          const textoCAR = $('#caratula').parent().find('div');
          const link = 'autoexclusion/mostrarArchivo/'+data.importacion.id_importacion+'/';
          textoFoto1.find('a').text(limpiarNull(data.importacion.foto1)).attr('href',link+'foto1');
          textoFoto2.find('a').text(limpiarNull(data.importacion.foto2)).attr('href',link+'foto2');
          textoDNI.find('a').text(limpiarNull(data.importacion.scandni)).attr('href',link+'scandni');
          textoAE.find('a').text(limpiarNull(data.importacion.solicitud_ae)).attr('href',link+'solicitud_ae');
          textoFIN.find('a').text(limpiarNull(data.importacion.solicitud_revocacion)).attr('href',link+'solicitud_revocacion');
          textoCAR.find('a').text(limpiarNull(data.importacion.caratula)).attr('href',link+'caratula');
          textoFoto1.toggle(data.importacion.foto1 != null);
          textoFoto2.toggle(data.importacion.foto2 != null);
          textoDNI.toggle(data.importacion.scandni != null);
          textoAE.toggle(data.importacion.solicitud_ae != null);
          textoFIN.toggle(data.importacion.solicitud_revocacion != null);
          textoCAR.toggle(data.importacion.caratula != null);
          $('#foto1').toggle(data.importacion.foto1 == null);
          $('#foto2').toggle(data.importacion.foto2 == null);
          $('#scan_dni').toggle(data.importacion.scandni == null);
          $('#solicitud_autoexclusion').toggle(data.importacion.solicitud_ae == null);
          $('#solicitud_revocacion').toggle(data.importacion.solicitud_revocacion == null);
          $('#caratula').toggle(data.importacion.caratula == null);
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
  $('.step2 input[required],.step2 select[required]').each(function(){
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
    if(!$(this).val().includes('@') && $(this).val().length > 0){
      mostrarErrorValidacion($(this) , 'Correo invalido' , false);
      valid = 0;
    }
  });
  $('.step2 input[data-size]').each(function(){
    const size = parseInt($(this).attr('data-size'));
    if(isNaN(size) || $(this).val().length > size){
      mostrarErrorValidacion($(this) , 'El campo tiene un máximo de '+size+' caracteres' , false);
      valid = 0;
    }
  })
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
  if (validarExtensionArchivo('#solicitud_revocacion') == 0) {
    mostrarErrorValidacion($('#solicitud_revocacion'), 'El tipo de archivo debe ser de tipo JPG o PDF' , true);
    valid = 0;
  }
  if (validarExtensionArchivo('#caratula') == 0) {
    mostrarErrorValidacion($('#caratula'), 'El tipo de archivo debe ser de tipo JPG o PDF' , true);
    valid = 0;
  }
  return valid;
}

//botón siguiente modal agregar ae
$("#btn-next").on("click", function(){
  const step = $(".page.active").index() + 1;
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

    const id_autoexcluido = $('#modalAgregarAE').attr('id-autoexcluido');
    const ae_datos =  {
      id_autoexcluido: (id_autoexcluido<0? null : id_autoexcluido),
      nro_dni: $('#nro_dni').val(),
      apellido: $('#apellido').val(),
      nombres: $('#nombres').val(),
      fecha_nacimiento: $('#fecha_nacimiento').val(),
      id_sexo: $('#id_sexo').val(),
      id_estado_civil: $('#id_estado_civil').val(),
      domicilio: $('#domicilio').val(),
      nro_domicilio: $('#nro_domicilio').val(),
      piso: $('#piso').val(),
      dpto: $('#dpto').val(),
      codigo_postal:  $('#codigo_postal').val(),
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

    formData.append('hace_encuesta',$('#hace_encuesta').is(':checked')? 1 : 0);
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
      foto1                : $('#foto1').parent().find('a').text().length != 0,
      foto2                : $('#foto2').parent().find('a').text().length != 0,
      solicitud_ae         : $('#solicitud_autoexclusion').parent().find('a').text().length != 0,
      solicitud_revocacion : $('#solicitud_revocacion').parent().find('a').text().length != 0,
      scandni              : $('#scan_dni').parent().find('a').text().length != 0,
      caratula             : $('#caratula').parent().find('a').text().length != 0
    }
    const ae_importacion = {
      foto1                : $('#foto1')[0].files[0],
      foto2                : $('#foto2')[0].files[0],
      solicitud_ae         : $('#solicitud_autoexclusion')[0].files[0],
      solicitud_revocacion : $('#solicitud_revocacion')[0].files[0],
      scandni              : $('#scan_dni')[0].files[0],
      caratula             : $('#caratula')[0].files[0],
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
            $('#modalAgregarAE').modal('hide');
            $('#btn-buscar').trigger('click');
            mensajeExito('La autoexclusión fue '+(data.nuevo? 'GUARDADA' : 'EDITADA') + ' correctamente.');
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

    //seteo en el value de los botones de ver mas el id de la importacion, para después
    //buscar en el backend los paths a los archivos y mostrarlos oportunamente
    $('.archivosImportados button').val(data.importacion.id_importacion);
    $('.archivosImportados [data-tipo="foto1"]').prop('disabled', data.importacion.foto1 === null);
    $('.archivosImportados [data-tipo="foto2"]').prop('disabled', data.importacion.foto2 === null);
    $('.archivosImportados [data-tipo="scandni"]').prop('disabled', data.importacion.scandni === null);
    $('.archivosImportados [data-tipo="solicitud_ae"]').prop('disabled', data.importacion.solicitud_ae === null);
    $('.archivosImportados [data-tipo="solicitud_revocacion"]').prop('disabled', data.importacion.solicitud_revocacion === null);
    $('.archivosImportados [data-tipo="caratula"]').prop('disabled', data.importacion.caratula === null);

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
  modalAgregarEditarAE(dni,$(this).val());
});

$(document).on('click', '#btnCambiarEstado', function(e){
  e.preventDefault();
  const id = $(this).val();
  const estado = $(this).attr('estado-nuevo');
  $.ajax({
    type: 'GET',
    url: 'autoexclusion/cambiarEstadoAE/'+ id + '/' + estado,
    dataType: 'json',
    success: function(data) {
      mensajeExito('Cambio de estado realizado');
      $('#btn-buscar').click();
    },
    error: function(data) {
        mensajeError('Error al validar');
        console.log(data);
    }
  });
});

$(document).on('click', '#btnGenerarSolicitudAutoexclusion', function(e){
  e.preventDefault();
  window.open('autoexclusion/generarSolicitudAutoexclusion/' + $(this).val(), '_blank');
});

$(document).on('click', '#btnGenerarConstanciaReingreso', function(e){
  e.preventDefault();
  window.open('autoexclusion/generarConstanciaReingreso/' + $(this).val(), '_blank');
});

$(document).on('click', '#btnGenerarSolicitudFinalizacion', function(e){
  e.preventDefault();
  window.open('autoexclusion/generarSolicitudFinalizacionAutoexclusion/' + $(this).val(), '_blank');
});

//Salir del modal ver mas
$('#btn-salir').click(function() {
  $('#modalVerMas').modal('hide');
});

//Mostrar archivos ver mas
$('.btn-ver-mas').click(function() {
  let tipo_archivo = $(this).attr('data-tipo');
  window.open('mostrarArchivo/' + $(this).val() + '/' + tipo_archivo, '_blank');
});

//Click en boton adentro del popover
$(document).on('click', '.subirArchivo', function(){
  $('#modalSubirArchivo input').val('')
  ocultarErrorValidacion($('#modalSubirArchivo input'));
  //Esto es bastante ridiculo pero bueno...
  const tr = $(this).parent().parent().parent().parent().parent().parent();
  $('#modalSubirArchivo .nro_dni').val(tr.find('.dni').text());
  $('#modalSubirArchivo .tipo_archivo').text($(this).text());
  $('#btn-subir-archivo').attr('data-id',tr.attr('data-id'));
  $('#btn-subir-archivo').attr('data-tipo',$(this).attr('data-tipo'));
  //muestra modal
  $('#modalSubirArchivo').modal('show');
});

//botón subir archivo solicitud ae
$('#btn-subir-archivo').click(function (e) {
  //guardo el archivo en un formdata
  const formData = new FormData();
  formData.append('id_autoexcluido', $(this).attr('data-id'));
  formData.append('tipo_archivo'   , $(this).attr('data-tipo'));
  formData.append('archivo'        , $('#modalSubirArchivo .archivo')[0].files[0]);
  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host + '/autoexclusion/subirArchivo',
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      success: function (data) {
        $('#modalSubirArchivo').modal('hide');
        mensajeExito($('#modalSubirArchivo .tipo_archivo').text()+' asignada');
        $('#btn-buscar').click();
      },
      error: function (data) {
        const json = data.responseJSON;
        mensajeError('');
        if("archivo" in json){
          mostrarErrorValidacion($('#modalSubirArchivo .archivo'),"Archivo faltante o invalido",true);
        }
        console.log(data);
      }
  });
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
  div.find('a').text('');
});

$("#contenedorFiltros input").on('keypress',function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
});

$('#hace_encuesta').change(function(){
  const show = $(this).is(':checked');
  const divs = $(this).parent().parent().parent().children().not('.no_esconder');
  divs.toggle(show);
});