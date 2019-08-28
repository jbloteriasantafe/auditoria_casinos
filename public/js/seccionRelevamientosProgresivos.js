$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Relevamiento de progresivos');
  $('#opcRelevamientosProgresivos').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcImportaciones').addClass('opcionesSeleccionado');
  $('#iconoCarga').hide();

  $('#fechaControlSinSistema').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#dtpBuscadorFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#fechaGeneracion').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#dtpFecha').datetimepicker({
    todayBtn:  1,
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy - HH:ii',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 0,
    ignoreReadonly: true,
    minuteStep: 5,
  });

  //trigger buscar, carga de tabla, fecha desc
  $('#btn-buscar').trigger('click');

});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| RELEVAMIENTO DE PROGRESIVOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//ABRIR MODAL DE NUEVO RELEVAMIENTO
$('#btn-nuevo').click(function(e){
  e.preventDefault();
  $('.modal-title').text('| NUEVO RELEVAMIENTO PROGRESIVOS');
  $('#modalImportacionBeneficios .modalNuevo').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#modalRelevamiento').modal('show');

  $.get("obtenerFechaActual", function(data){
    //Mayuscula pŕimer letra
    var fecha = data.fecha.charAt(0).toUpperCase() + data.fecha.slice(1);
    $('#fechaActual').val(fecha);
    $('#fechaDate').val(data.fechaDate);
  });
});

//SALIR DEL RELEVAMIENTO
$('#btn-salir').click(function(){
  //Si está guardado deja cerrar el modal
  if (guardado) $('#modal').modal('hide');
  //Si no está guardado
  else{
    if (salida == 0) {
      $('#modalCargaRelevamientoProgresivos .mensajeSalida').show();
      salida = 1;
    }else {
      $('#modalCargaRelevamientoProgresivos').modal('hide');
    }
  }
});

//VALIDAR EL RELEVAMIENTO
$(document).on('click','.validar',function(e){

  e.preventDefault();

  var id_relevamiento = $(this).val();

  $('#id_relevamiento').val(id_relevamiento);

  $.get('relevamientosProgresivo/obtenerRelevamiento/' + id_relevamiento, function(data){

      $('#modalValidarRelevamientoProgresivos').modal('show');

      $('#validacionFechaActual').val(data.relevamiento.fecha);
      $('#validacionCasino').val(data.casino.nombre);
      $('#validacionSector').val(data.sector);
      $('#validacionTecnico').val(data.relevamiento.tecnico);
      $('#validacionFechaEjecucion').val(data.relevamiento.fecha_ejecucion);
      $('#validacionInputFisca').val(data.usuario_fiscalizador.nombre);
      $('#validacionFiscaCarga').val(data.usuario_cargador.nombre);
      $('#validacionFechaGeneracion').val(data.relevamiento.fecha_generacion);

      for (var i = 0; i < data.detalles.length; i++) {
        agregarRenglon(data.detalles[i],$('#validacion_contenedor_progresivos'));
      }


  });

})

function agregarRenglon(detalle, contenedor){
    console.log(detalle.nombre_nivel ,detalle.base);
    var clonado = $('#clonar').clone();
    clonado.show();
    clonado.attr('id' , detalle.id_detalle_relevamiento_progresivo);
    clonado.addClass('clonado');
    clonado.find('.nro_isla').val(detalle.nro_isla);
    clonado.find('.nombre_nivel').val(detalle.nombre_nivel);
    clonado.find('.nombre_progresivo').val(detalle.nombre_progresivo);
    clonado.find('.base').val(detalle.base);
    if (detalle.valor != null){
      clonado.find('.actual').val(detalle.valor);
      clonado.find('.actual').prop('readonly',true);
    }
    contenedor.append(clonado);
    contenedor.append($('<br>'));
}

$('#modalCargaRelevamientoProgresivos').on('hidden.bs.modal', function() {
  ocultarErrorValidacion($('.form-control'));//oculto todos los errores
  $('#contenedor_progresivos').empty();
  $('#cargaFechaActual').val('');
  $('#cargaCasino').val('');
  $('#cargaSector').val('');
  $('#tecnico').val('');
  $('#validacionFechaEjecucion').val('');
  $('#validacionInputFisca').val('');
  $('#validacionFiscaCarga').val('');
})

$('#modalValidarRelevamientoProgresivos').on('hidden.bs.modal', function() {
  ocultarErrorValidacion($('.form-control'));//oculto todos los errores
  $('#validacion_contenedor_progresivos').empty();
  $('#validacionFechaActual').val('');
  $('#validacionCasino').val('');
  $('#validacionSector').val('');
  $('#validacionTecnico').val('');
  $('#validacionFechaEjecucion').val('');
  $('#validacionInputFisca').val('');
  $('#validacionFiscaCarga').val('');
  $('#validacion_contenedor_progresivos').empty();
})

//ABRIR MODAL DE NUEVO RELEVAMIENTO
$('#btn-nuevoRelevamiento').click(function(e){
  e.preventDefault();
  $('#frmRelevamiento').trigger('reset');
  $('#sector option').remove();
  $('#maquinas_pedido').hide();
  $('#modalRelevamiento').modal('show');

  $.get("obtenerFechaActual", function(data){
    $('#fechaActual').val(data.fecha);
    $('#fechaDate').val(data.fechaDate);
  });
});

$('#casinoSinSistema').on('change', function(){
  var id_casino = $('#casinoSinSistema option:selected').attr('id');

  $('#sectorSinSistema option').remove();

  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
    console.log(data);
    for (var i = 0; i < data.sectores.length; i++) {
      $('#sectorSinSistema')
          .append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }
  });
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#casino').on('change',function(){
  var id_casino = $('#casino option:selected').attr('id');

  $('#sector option').remove();
  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data){

    for (var i = 0; i < data.sectores.length; i++) {
      $('#sector')
          .append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }

  });
});

$('#buscadorCasino').on('change',function(){
  console.log('change');
  var id_casino = $('#buscadorCasino option:selected').attr('id');

  $('#buscadorSector option').remove();

  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
    $('#buscadorSector')
        .append($('<option>').val(0).text('-Todos los sectores-'))
    for (var i = 0; i < data.sectores.length; i++) {
      $('#buscadorSector')
          .append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }

  });
});

//GENERAR RELEVAMIENTO
$('#btn-generar').click(function(e){

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var formData = {
    id_sector: $('#sector').val(),
    //cantidad_maquinas: $('#cantidad_maquinas').val(),
    //cantidad_fiscalizadores: $('#cantidad_fiscalizadores').val(),
  }

  $.ajax({
      type: "POST",
      url: 'relevamientosProgresivo/crearRelevamiento',
      data: formData,
      dataType: 'json',
      success: function (data) {
            $('#btn-buscar').trigger('click');
            $('#modalRelevamiento').modal('hide');
            // var iframe;
            // iframe = document.getElementById("download-container");
            // if (iframe === null){
            //     iframe = document.createElement('iframe');
            //     iframe.id = "download-container";
            //     iframe.style.visibility = 'hidden';
            //     document.body.appendChild(iframe);
            // }
            // iframe.src = data.url_zip;
      },
      error: function (data) {
        var response = JSON.parse(data.responseText);

        if(typeof response.id_sector !== 'undefined'){
              $('#sector').addClass('alerta');
              $('#casino').addClass('alerta');
        }
      }
  });

});

var guardado;

$('#modalCargaRelevamientoProgresivos').on('change', "input", function(){
  habilitarBotonGuardar();
});

$(document).on('change','.tipo_causa_no_toma',function(){
    //Si se elige algun tipo de no toma se vacian las cargas de contadores
    $(this).parent().parent().find('td').children('.contador').val('');
    //Se cambia el icono de diferencia
    $(this).parent().parent().find('td').find('i.fa-question').hide();
    $(this).parent().parent().find('td').find('i.fa-times').show();
    $(this).parent().parent().find('td').find('i.fa-check').hide();
    $(this).parent().parent().find('td').find('i.fa-exclamation').hide();

    habilitarBotonGuardar();
    habilitarBotonFinalizar();
});

//SALIR DEL RELEVAMIENTO
var salida; //cantidad de veces que se apreta salir

$('#btn-salir').click(function(){

  //Si está guardado deja cerrar el modal
  if (guardado) $('#modalCargaRelevamientoProgresivos').modal('hide');
  //Si no está guardado
  else{
    if (salida == 0) {
      $('#modalCargaRelevamientoProgresivos .mensajeSalida').show();
      salida = 1;
    }else {
      $('#modalCargaRelevamientoProgresivos').modal('hide');
    }
  }
});

//FINALIZAR EL RELEVAMIENTO
$('#btn-finalizar').click(function(e){
  e.preventDefault();

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var detalles= [];
  $('#contenedor_progresivos .clonado').each(function() {
    var detalle = {
      id_detalle_relevamiento_progresivo: $(this).attr('id'),
      valor: $(this).find('.actual').val(),
    }
    detalles.push(detalle);
  })

  var formData = {
    id_relevamiento_progresivo: $('#id_relevamiento').val(),
    id_usuario_fiscalizador: $('#inputFisca').obtenerElementoSeleccionado(),
    observacion_carga: $('#observacion_carga').val(),
    tecnico: $('#tecnico').val(),
    fecha_ejecucion: $('#fecha_ejecucion').val(),
    detalles: detalles,
  }

  console.log(formData);

  $.ajax({
      type: 'POST',
      url: 'relevamientosProgresivo/cargarRelevamiento',
      data: formData,
      dataType: 'json',
      success: function (data) {
        $("#modalCargaRelevamientoProgresivos").modal('hide');
      },
      error: function (data) {
        var response = JSON.parse(data.responseText);

        if(typeof response.id_usuario_fiscalizador !== 'undefined')
          mostrarErrorValidacion($('#inputFisca'),response.id_usuario_fiscalizador[0],true);
        if(typeof response.fecha_ejecucion !== 'undefined')
          mostrarErrorValidacion($('#fecha'),response.fecha_ejecucion[0],true);
        if(typeof response.id_sector !== 'undefined')
          mostrarErrorValidacion($('observacion'), response.observacion[0],true);
        var filaError=0;
        var i=0;
        $('.clonado').each(function(){
          if(typeof response['detalles.'+ i +'.valor'] !== 'undefined'){
            filaError=i;
            mostrarErrorValidacion($(this).find('.actual'),response['detalles.'+ i +'.valor'][0],true);
          }
          i++;
        })

        if(filaError >= 0)
        {
          var id_pos = $("#modalCargaRelevamientoProgresivos .clonado:eq("+filaError+")").attr('id');
          var pos = $('#' + id_pos).offset().top;
          $("#modalCargaRelevamiento").animate({ scrollTop: pos }, "slow");
        }
      },
  });
});



//validar
$('#btn-finalizarValidacion').click(function(e){
    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

    var id_relevamiento = $('#id_relevamiento').val();

    var formData = {
      id_relevamiento: id_relevamiento,
      observacion_validacion: $('#observacion_validacion').val(),
    }

    $.ajax({
        type: 'POST',
        url: 'relevamientosProgresivo/validarRelevamiento',
        data: formData,
        dataType: 'json',
        success: function (data) {
        },
        error: function (data) {
          var response = JSON.parse(data.responseText);
          if(typeof response.observacion_validacion !== 'undefined')
            mostrarErrorValidacion( $('#observacion_validacion') , response.observacion_validacion[0] , true);

        },
    });

});

$('#btn-relevamientoSinSistema').click(function(e){
    e.preventDefault();
    $('.modal-title').text('| RELEVAMIENTO SIN SISTEMA');
    $('#modalImportacionBeneficios .modalNuevo').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
    $('#modalRelevamientoSinSistema').modal('show');
});

//Generar el relevamiento de backup
$('#btn-backup').click(function(e){

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var formData = {
    fecha: $('#fechaRelSinSistema_date').val(),
    fecha_generacion: $('#fechaGeneracion_date').val(),
    id_sector: $('#sectorSinSistema').val(),
  }

  console.log(formData);

  $.ajax({
      type: "POST",
      url: 'relevamientos/usarRelevamientoBackUp',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('#btn-buscar').trigger('click');
      },
      error: function (data) {
        console.log('ERROR!');
        console.log(data);
      }
  });

});

//Botón Cancelar fiscalizador
$('#cancelarFisca').click(function(){
    guardado = false;
    $('#btn-guardar').show();

    $('#inputFisca').prop("readonly", false); //Se habilita el input
    $('#inputFisca').val(''); //Se limpia el input
    $('#inputFisca').attr('data-fisca', '');

    $('#datalistFisca').empty();
});

function habilitarBotonGuardar(){
  guardado = false;
  $('#btn-guardar').show();
}

function habilitarBotonFinalizar(){
  var cantidadMaquinas = 0;
  var maquinasRelevadas = 0;

  $('#tablaCargaRelevamiento tbody tr').each(function(i){
      cantidadMaquinas++;
      var inputLleno = false;
      var noToma = false;

      //Mirar si la fila tiene algun campo lleno
      $(this).children('td').find('.contador').each(function (j){
          if($(this).val().length > 0) inputLleno = true;
      });

      //Mirar si seleccionó un tipo de no toma
      if($(this).children('td').find('select').val() !== '') noToma = true;

      //Si se lleno algun campo o se tifico la no toma, entonces la maquina está relevada
      if (inputLleno || noToma) {
          maquinasRelevadas++;
      }
  });

  console.log(cantidadMaquinas,maquinasRelevadas);
  if(cantidadMaquinas == maquinasRelevadas) $('#btn-finalizar').show();
  else $('#btn-finalizar').hide();
}

function maquinasAPedido(){
  var id_sector = $('#sector option:selected').val();
  var fecha = $('#fechaDate').val();

  $.get("mtm_a_pedido/obtenerMtmAPedido/" + fecha + "/" + id_sector, function(data){
      console.log(data);
      var cantidad = data.cantidad;

      if (cantidad == 0){
        $('#maquinas_pedido').hide();
      }else {
        if (cantidad == 1) $('#maquinas_pedido').find('span').text('Este sector tiene ' + cantidad + ' máquina a pedido.');
        else $('#maquinas_pedido').find('span').text('Este sector tiene ' + cantidad + ' máquinas a pedido.');

        $('#maquinas_pedido').show();
      }
  });
}

//PAGINACION
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

      var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
      // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
      var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
      var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaRelevamientos .activa').attr('value'),orden: $('#tablaRelevamientos .activa').attr('estado')} ;
      if(sort_by == null){ // limpio las columnas
        $('#tablaRelevamientos th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
      }

    var formData = {
      fecha_generacion: $('#buscadorFecha').val(),
      casino: $('#buscadorCasino').val(),
      sector: $('#buscadorSector').val(),
      estadoRelevamiento: $('#buscadorEstado').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    }

    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host +'/relevamientosProgresivo/buscarRelevamientosProgresivos',
        data: formData,
        dataType: 'json',
        success: function (resultados) {
            console.log(resultados);

            $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
            $('#cuerpoTabla tr').not('.filaEjemplo').remove();

            //1ro - Se generan todas las filas con todos los iconos
            //2do - Se muestran los iconos por permiso
            //3ro - Se muestran los iconos de cada fila según el estado

            for (var i = 0; i < resultados.data.length; i++){
                var fila = generarFilaTabla(resultados.data[i]);
                $('#cuerpoTabla').append(fila);
            }

            $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

        },
        error: function (data) {
            console.log('Error:', data);
        }
      });
});

//Paginacion
$(document).on('click','#tablaRelevamientos thead tr th[value]',function(e){
  $('#tablaRelevamientos th').removeClass('activa');
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
  $('#tablaRelevamientos th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaRelevamientos .activa').attr('value');
  var orden = $('#tablaRelevamientos .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(relevamiento){
    var subrelevamiento;
    relevamiento.sub_control != null ? subrelevamiento = relevamiento.sub_control : subrelevamiento = '';
    let fila = $('#cuerpoTabla .filaEjemplo').clone().removeClass('filaEjemplo').show();

    fila.attr('id', relevamiento.id_relevamiento_progresivo);
    fila.find('.fecha').text(relevamiento.fecha);
    fila.find('.casino').text(relevamiento.casino);
    fila.find('.sector').text(relevamiento.sector);
    fila.find('.subcontrol').text(subrelevamiento);
    fila.find('.textoEstado').text(relevamiento.estado);
    fila.find('button').each(function(idx,c){$(c).val(relevamiento.id_relevamiento_progresivo);});
    let planilla = fila.find('.planilla');
    let carga = fila.find('.carga');
    let validacion = fila.find('.validar');
    let imprimir = fila.find('.imprimir');

    //Se setea el display como table-row por algun motivo :/
    //Lo saco a pata.
    fila.css('display','');
    //Qué ESTADO e ICONOS mostrar
    switch (relevamiento.estado) {
      case 'Generado':
          fila.find('.fa-dot-circle').addClass('faGenerado');
          break;
      case 'Cargando':
          fila.find('.fa-dot-circle').addClass('faCargando');
          break;
      case 'Finalizado':
          fila.find('.fa-dot-circle').addClass('faFinalizado');
          break;
      case 'Validado':
          fila.find('.fa-dot-circle').addClass('faValidado');
          break;
    }

    planilla.click(function(){
      window.open('relevamientosProgresivo/generarPlanilla/' + $(this).val(),'_blank');
    });

    carga.click(function(e){
      e.preventDefault();

      salida = 0;//ocultar mensaje de salida
      guardado = true;

      $('#modalCargaRelevamientoProgresivos .mensajeSalida').hide();
      var id_relevamiento = $(this).val();
      $('#id_relevamiento').val(id_relevamiento);
      $('#btn-guardar').hide();
      $('#btn-finalizar').hide();

      $.get('relevamientosProgresivo/obtenerRelevamiento/' + id_relevamiento, function(data){
          //$('#inputFisca').setearElementoSeleccionado(0,"");
          $('#inputFisca').attr('list','datalist'+data.casino.id_casino);

          $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
          $('#cargaCasino').val(data.casino.nombre);
          $('#cargaSector').val(data.sector.descripcion);
          $('#cargaSubrelevamiento').val('TODO:!!!!')
          if(data.usuario_cargador != null)
            $('#fiscaCarga').val(data.usuario_cargador.nombre);
          if(data.usuario_fiscalizador != null)
            $('#inputFisca').val(data.usuario_fiscalizador.nombre);

          for (var i = 0; i < data.detalles.length; i++) {
            agregarRenglon(data.detalles[i],$('#contenedor_progresivos'));
          }

          habilitarBotonFinalizar();
      });

      $('#modalCargaRelevamientoProgresivos').modal('show');
    });



    return fila;
}

//Opacidad del modal al minimizar
$('#btn-minimizarValidar').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
      $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarCargar').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
      $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarPlanilla').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
      $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarCrear').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
      $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarSinSistema').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
      $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});
