$(document).ready(function(){

  $('#barraMenu').attr('aria-expanded','true');
  // $('#maquinas').removeClass();
  // $('#maquinas').addClass('subMenu1 collapse in');
  $('#bingoMenu').removeClass();
  $('#bingoMenu').addClass('subMenu2 collapse in');

  $('#bingoMenu').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Reportes de Estados');
  // $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcReporteEstadoBingo').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcReporteEstadoBingo').addClass('opcionesSeleccionado');

  $('#dtpBuscadorFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
    endDate: '+0d'
  });

  $('#dtpFechaNacimiento').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
    endDate: '+0d'
  });

  $('#dtpFechaAutoexclusion').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
    endDate: '+0d'
  });


  $('#btn-buscar').trigger('click');


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

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| FÓRMULAS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//busqueda de reportes
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
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultadosPremio .activa').attr('value'),orden: $('#tablaResultadosPremio .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultadosPremios th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  var formData = {
    fecha: $('#buscadorFecha').val(),
    casino: $('#buscadorCasino').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: 'GET',
      url: 'buscarEstado',
      data: formData,
      dataType: 'json',
      success: function(resultados){

        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTabla tr').remove();
        for (var i = 0; i < resultados.data.length; i++){
          $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i]));
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

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}


//Botón agregar nuevo AE
$('#btn-agregar-ae').click(function(e){
    e.preventDefault();
  //vuelvo a step1
  $('.page').removeClass('active');
  $('.step1').addClass('active');
  //limpio el form
  $('#frmAgregarAE :input').val('');

  //oculto el botón anterior
  $("#btn-prev").hide();

  //título y color header
  $('.modal-title').text('| AGREGAR AE');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

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


//función para autocompletar el input de provincia
function cargarProvincias(){
    var options = {
    url: "/js/Autoexcluidos/provincias.json",
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
  var nombre_provincia =  $("#nombre_provincia").val();
  var localidades = [];

  $.getJSON('/js/Autoexcluidos/localidades.json', function(data) {
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

  var options = {
	// url: "/js/Autoexcluidos/localidades.json",
  data: localidades,
  // listLocation: "localidades",
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
  var nombre_provincia =  $("#nombre_provincia_vinculo").val();
  var localidades = [];

  $.getJSON('/js/Autoexcluidos/localidades.json', function(data) {
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

  var options = {
	// url: "/js/Autoexcluidos/localidades.json",
  data: localidades,
  // listLocation: "localidades",
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
  var fecha_autoexlusion = new Date( $( "#fecha_autoexlusion" ).val() );

  ((fecha_autoexlusion.getMonth() + 1) < 10 ? '0' : '') + (fecha_autoexlusion.getMonth() + 1)


  fecha_autoexlusion.setDate(fecha_autoexlusion.getDate() + 360);
  $( "#fecha_cierre_definitivo" ).val(  (fecha_autoexlusion.getDate() < 10 ? '0' : '') + fecha_autoexlusion.getDate() + '-' + (  ((fecha_autoexlusion.getMonth() + 1) < 10 ? '0' : '') + (fecha_autoexlusion.getMonth() + 1) ) + '-' +   fecha_autoexlusion.getFullYear());
  fecha_autoexlusion.setDate(fecha_autoexlusion.getDate() - 180);
  $( "#fecha_vencimiento_periodo" ).val(  (fecha_autoexlusion.getDate() < 10 ? '0' : '') + fecha_autoexlusion.getDate() + '-' + (  ((fecha_autoexlusion.getMonth() + 1) < 10 ? '0' : '') + (fecha_autoexlusion.getMonth() + 1) ) + '-' +   fecha_autoexlusion.getFullYear());
  fecha_autoexlusion.setDate(fecha_autoexlusion.getDate() - 30);
  $( "#fecha_renovacion" ).val(  (fecha_autoexlusion.getDate() < 10 ? '0' : '') + fecha_autoexlusion.getDate() + '-' + (  ((fecha_autoexlusion.getMonth() + 1) < 10 ? '0' : '') + (fecha_autoexlusion.getMonth() + 1) ) + '-' +   fecha_autoexlusion.getFullYear());

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
});

//botón siguiente modal agregar ae
$("#btn-next").on("click", function(){
  //si apreta sig y es la primera, busco si existe ae con el dni
  if( $(".page.active").index() == 1 ) {
    //si existe dni->precargo el form con los datos

  }

    //verifico que los input del step no esten en blanco.
    var step = $(".page.active").index() + 1;
    var valid = 1;
    $( ".step"+step+" :input" ).each(function(){
      if( $(this).val() == '' && step<4){
          mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
          valid = 0;
      }
    });

    //verifico que el step sea valido para avanzar
    if(valid == 1 && step<4){
      //si no es el último step, tengo siguiente
      if($(".page.active").index() < $(".page").length-1){
          //cambio form active
          $(".page.active").removeClass("active").next().addClass("active");
          //cambio step active
          $(".step.actived").removeClass("actived").addClass("finish").next().addClass("actived");
          //muestro botón anterior
          $("#btn-prev").show();
      }
    }

    //si llegue al final, muestro botón de guaradr y oculto el de siguiente
    if( $(".page.active").index() == $(".page").length-1 ){
      $("#btn-guardar").show();
      $("#btn-next").hide();
     }
});


//botón guardar ae
$('#btn-guardar').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    //guardo los datos personales
    var ae_datos =  {
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
      id_ocupacion: $('#nro_dni').val(),
      id_capacitacion: $('#id_capacitacion').val(),
    }
    //guardo los datos de contacto
    var ae_datos_contacto =  {
      nombre_apellido: $('#nombre_apellido').val(),
      domicilio_vinculo: $('#domicilio_vinculo').val(),
      nombre_localidad_vinculo: $('#nombre_localidad_vinculo').val(),
      nombre_provincia_vinculo: $('#nombre_provincia_vinculo').val(),
      telefono_vinculo: $('#telefono_vinculo').val(),
      vinculo: $('#vinculo').val(),
    }
    //guardo los datos de estado+fecha
    var ae_estado = {
      id_casino: $('#id_casino').val(),
      id_nombre_estado: $('#id_estado').val(),
      fecha_autoexlusion: $('#fecha_autoexlusion').val(),
      fecha_vencimiento_periodo: $('#fecha_vencimiento_periodo').val(),
      fecha_renovacion: $('#fecha_renovacion').val(),
      fecha_cierre_definitivo: $('#fecha_cierre_definitivo').val(),
      //faltan inportaciones
    }
    //guardo los datos de la encuesta
    var ae_encuesta = {
        juego_preferido: $('#juego_preferido').val(),
        id_frecuencia_asistencia: $('#id_frecuencia_asistencia').val(),
        veces: $('#veces').val(),
        tiempo_jugado: $('#tiempo_jugado').val(),
        socio_club_jugadores: $('#socio_club_jugadores').val(),
        juego_responsable: $('#juego_responsable').val(),
        autocontrol_juego: $('#autocontrol_juego').val(),
        recibir_informacion: $('#recibir_informacion').val(),
        medio_recepcion: $('#medio_recepcion').val(),
        observaciones: $('#observaciones').val(),
        como_asiste: $('#como_asiste').val(),
    }
    //datos para enviar
    var formData = {
      datos_personales: ae_datos,
      datos_contacto: ae_datos_contacto,
      ae_estado: ae_estado,
      ae_encuesta: ae_encuesta
    }

    //url de destino, dependiendo si se esta creando o modificando una sesión
    var url;

    //dependiendo el valor del botón guarda o edita
    var state = $('#btn-guardar').val();
    if( state == 'nuevo'){
      url =  'autoexcluido/agregarAE';
    }else{
      url = 'autoexcluido/agregarAE';
      //se agrega id_ae si se esta modificando
      var formData = {
        datos_personales: ae_datos,
        datos_contacto: ae_datos_contacto,
        ae_estado: ae_estado,
        ae_encuesta: ae_encuesta,
        // id_ae: $('#id_ae').val(),
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
              $('#mensajeExito P').text('La autoexclusión fue GUARDADA correctamente.');
              $('#mensajeExito div').css('background-color','#4DB6AC');
              // $('#cuerpoTabla').append(generarFilaTabla(data.sesion,data.estado,data.casino,data.nombre_inicio,data.nombre_fin,'guardar'));
            }else{ //Si está modificando
              $('#mensajeExito p').text('La autoexclusión fue GEDITADA correctamente.');
              $('#mensajeExito div').css('background-color','#FFB74D');
            }
            // $('#frmFormula').trigger("reset");
            $('#modalAgregarAE').modal('hide');
            //Mostrar éxito
            $('#mensajeExito').show();
        },
        error: function (data) {
            var response = JSON.parse(data.responseText);


        }
    });
});
