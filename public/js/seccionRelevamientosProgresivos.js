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
    format: 'yyyy-mm-dd HH:ii:ss',
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

function filaEjemploCarga(){
    return $('#modalRelevamientoProgresivos .filaEjemplo').not('.validacion')
    .clone().removeClass('filaEjemplo').show().css('display','');
}
function filaEjemploValidacion(){
    return $('#modalRelevamientoProgresivos .filaEjemplo.validacion')
    .clone().removeClass('filaEjemplo').removeClass('validacion').show().css('display','');
}

$('#modalRelevamientoProgresivos').on('hidden.bs.modal', function() {
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

function obtenerMensajesError(response){
  json = response.responseJSON;
  mensajes = [];
  keys = Object.keys(json);
  for(let i=0;i<keys.length;i++){
    let k = keys[i];
    let msgs = json[k];
    for(let j=0;j<msgs.length;j++){
      mensajes.push(msgs[j]);
    }
  }

  return mensajes;
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
    let planilla = fila.find('.planilla').attr({'data-toggle':'tooltip','data-placement':'top','title':'VER PLANILLA','data-delay':'{"show":"300", "hide":"100"}'});
    let carga = fila.find('.carga').attr({'data-toggle':'tooltip','data-placement':'top','title':'CARGAR RELEVAMIENTO','data-delay':'{"show":"300", "hide":"100"}'});
    let validacion = fila.find('.validar').attr({'data-toggle':'tooltip','data-placement':'top','title':'VISAR RELEVAMIENTO','data-delay':'{"show":"300", "hide":"100"}'});
    let imprimir = fila.find('.imprimir').attr({'data-toggle':'tooltip','data-placement':'top','title':'IMPRIMIR PLANILLA','data-delay':'{"show":"300", "hide":"100"}'});

    let planillaCallback = function (){
      window.open('relevamientosProgresivo/generarPlanilla/' + $(this).val(),'_blank');
    };

    let cargaCallback = function (e){
      e.preventDefault();

      $('#modalRelevamientoProgresivos .mensajeSalida').hide();
      var id_relevamiento = $(this).val();
      $('#id_relevamiento').val(id_relevamiento);

      $('#btn-guardar').show().off();
      $('#btn-finalizar').show().text("CARGAR").off();

      $('#modalRelevamientoProgresivos')
      .find('.modal-header').attr("font-family:'Roboto-Black';color:white;background-color:#FF6E40;");
      $('#modalRelevamientoProgresivos').
      find('.modal-title').text('| CARGAR RELEVAMIENTO DE PROGRESIVOS');

      $('#inputFisca').attr('disabled',false);
      $('#usuario_fiscalizador').attr('disabled',false);
      $('#fecha').attr('disabled',false);

      $.get('relevamientosProgresivo/obtenerRelevamiento/' + id_relevamiento, function(data){
        setearRelevamiento(data,obtenerFila);

        $('#btn-finalizar').click(function(){
          let err = validarFormulario(data.casino.id_casino);
          if(err.errores){
            console.log(err.mensajes);
            mensajeError(err.mensajes);
            return;
          }

          enviarFormularioCarga(
            data.casino.id_casino,
            data.relevamiento.id_relevamiento_progresivo,
            data.relevamiento.subrelevamiento,
            function (data) {
              console.log(data);
              $('#modalCargaRelevamientoProgresivos').modal('hide');
            },
            function (x) {
              console.log(x);
              let msgs = obtenerMensajesError(x);
              mensajeError(msgs);
            }
          );
        });

        $('#btn-guardar').click(function(){
          enviarFormularioCarga(
            data.casino.id_casino,
            data.relevamiento.id_relevamiento_progresivo,
            data.relevamiento.subrelevamiento,
            function(x){
              console.log(x)
            },
            function(x){
              console.log(x);
            },
            "relevamientosProgresivo/guardarRelevamiento"
          );
        });

      });

      $('#observacion_carga').removeAttr('disabled');
      $('#observacion_validacion').parent().hide();
      $('#modalRelevamientoProgresivos').modal('show');
    };

    let validacionCallback = function (e){
        e.preventDefault();

        var id_relevamiento = $(this).val();
        $('#id_relevamiento').val(id_relevamiento);

        $('#modalRelevamientoProgresivos .mensajeSalida').hide();
        var id_relevamiento = $(this).val();
        $('#id_relevamiento').val(id_relevamiento);
        $('#btn-guardar').hide();
        $('#btn-finalizar').show().text("VISAR").off();

        $('#modalRelevamientoProgresivos')
        .find('.modal-header').attr('style',"font-family:'Roboto-Black';color:white;background-color:#69F0AE;");
        $('#modalRelevamientoProgresivos').
        find('.modal-title').text('| VALIDAR RELEVAMIENTO DE PROGRESIVOS');

        $('#inputFisca').attr('disabled',true);
        $('#usuario_fiscalizador').attr('disabled',true);
        $('#fecha').attr('disabled',true);

        $.get('relevamientosProgresivo/obtenerRelevamiento/' + id_relevamiento, function(data){
          setearRelevamiento(data,obtenerFilaValidacion);

          $('#btn-finalizar').click(function(){
            enviarFormularioValidacion(
                        data.casino.id_casino,
                        data.relevamiento.id_relevamiento_progresivo,
                        data.relevamiento.subrelevamiento);
          });
        });

        $('#observacion_carga').attr('disabled',true);
        $('#observacion_validacion').parent().show();
        $('#modalRelevamientoProgresivos').modal('show');
    };

    let imprimirCallback = function(){
      window.open('relevamientosProgresivo/generarPlanilla/' + $(this).val(),'_blank');
    };

    //Se setea el display como table-row por algun motivo :/
    //Lo saco a pata.
    fila.css('display','');
    //Qué ESTADO e ICONOS mostrar
    switch (relevamiento.estado) {
      case 'Generado':
          fila.find('.fa-dot-circle').addClass('faGenerado');
          carga.click(cargaCallback);
          validacion.remove();
          imprimir.remove();
          break;
      case 'Cargando':
          fila.find('.fa-dot-circle').addClass('faCargando');
          carga.click(cargaCallback);
          validacion.remove();
          imprimir.remove();
          break;
      case 'Finalizado':
          fila.find('.fa-dot-circle').addClass('faFinalizado');
          validacion.click(validacionCallback);
          carga.remove();
          planilla.remove();
          break;
      case 'Visado':
          fila.find('.fa-dot-circle').addClass('faValidado');
          planilla.remove();
          carga.remove();
          validacion.remove();
          break;
    }

    planilla.click(planillaCallback);
    imprimir.click(imprimirCallback);

    return fila;
}

$('#btn-salir').click(function(){
  $('#modalRelevamientoProgresivos').modal('hide');
});

function obtenerFila(detalle){
  let fila = filaEjemploCarga();
  fila.find('.nombreProgresivo').text(detalle.nombre_progresivo);
  fila.find('.nombrePozo').text(detalle.nombre_pozo);
  fila.find('.isla').text(detalle.nro_isla);
  fila.attr('data-id',detalle.id_detalle_relevamiento_progresivo);

  fila.find('.causaNoToma').on('change',function(){
    causaNoTomaCallback(this);
  });

  if(detalle.id_tipo_causa_no_toma_progresivo != null){
      fila.find('.causaNoToma').val(detalle.id_tipo_causa_no_toma_progresivo);
      fila.find('.causaNoToma').change();
  }

  for(let n=0;n<detalle.niveles.length;n++){
    let nivel = detalle.niveles[n];
    if(nivel.nombre_nivel != null)
      fila.find('.nivel'+nivel.nro_nivel).attr('placeholder',nivel.nombre_nivel);

    fila.find('.nivel'+nivel.nro_nivel)
    .val(nivel.valor)
    .attr('data-id',nivel.id_nivel_progresivo);
  }


  fila.find('input:not([data-id])').attr('disabled',true);

  return fila;
}

function causaNoTomaCallback(x){
  let fila = $(x).parent().parent();
  if($(x).val() != -1){
    fila.find('input').attr('disabled',true)
    fila.find('input').css('color','#fff');
  }
  else{
    fila.find('input').attr('disabled',false);
    fila.find('input').css('color','');
    fila.find('input:not([data-id])').attr('disabled',true);
  }
}

function obtenerFilaValidacion(detalle){
  let fila = filaEjemploValidacion();
  fila.find('.nombreProgresivo').text(detalle.nombre_progresivo);
  fila.find('.nombrePozo').text(detalle.nombre_pozo);
  fila.find('.isla').text(detalle.nro_isla);
  fila.attr('data-id',detalle.id_detalle_relevamiento_progresivo);

  for(let n=0;n<detalle.niveles.length;n++){
    let nivel = detalle.niveles[n];
    if(nivel.nombre_nivel != null)
      fila.find('.nivel'+nivel.nro_nivel).attr('placeholder',nivel.nombre_nivel);

    fila.find('.nivel'+nivel.nro_nivel)
    .val(nivel.valor)
    .attr('data-id',nivel.id_nivel_progresivo);
  }

  fila.find('input:not([data-id])').attr('disabled',true);

  if(detalle.id_tipo_causa_no_toma_progresivo != null){
      fila.find('.causaNoToma').val(detalle.id_tipo_causa_no_toma_progresivo);
  }


  fila.find('.causaNoToma').on('change',function(){
    if($(this).val() != -1){
      fila.find('input').attr('disabled',true)
      fila.find('input').css('color','#fff');
    }
    else{
      fila.find('input').attr('disabled',false);
      fila.find('input').css('color','');
      fila.find('input:not([data-id])').attr('disabled',true);
    }
  });

  return fila;
}

function setearRelevamiento(data,filaCallback){
  //Limpio los campos
  $('#modalRelevamientoProgresivos input').val('');
  $('#modalRelevamientoProgresivos select').val(-1);
  $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr').not('.filaEjemplo').remove();

  $('#usuario_fiscalizador').attr('list','datalist'+data.casino.id_casino);

  $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
  $('#cargaCasino').val(data.casino.nombre);
  $('#cargaSector').val(data.sector.descripcion);
  $('#fiscaCarga').val(data.relevamiento.id_usuario_fiscalizador);
  $('#fecha').val(data.relevamiento.fecha_ejecucion);

  if(data.usuario_cargador != null)
    $('#usuario_cargador').val(data.usuario_cargador.nombre);
  if(data.usuario_fiscalizador != null)
    $('#usuario_fiscalizador').val(data.usuario_fiscalizador.nombre);

  if(data.relevamiento.subrelevamiento != null){
    $('#cargaSubrelevamiento').val(data.relevamiento.subrelevamiento);
  }

  $('#observacion_carga').val('');
  if(data.relevamiento.observacion_carga != null){
    $('#observacion_carga').val(data.relevamiento.observacion_carga);
  }

  $('#observacion_validacion').val('');
  if(data.relevamiento.observacion_validacion != null){
    $('#observacion_validacion').val(data.relevamiento.observacion_validacion);
  }

  let tabla = $('#modalRelevamientoProgresivos .cuerpoTablaPozos');
  for (let i = 0; i < data.detalles.length; i++) {
    tabla.append(filaCallback(data.detalles[i]));
  }
}

function mensajeError(errores){
  $('#mensajeError .textoMensaje').empty();
  for(let i=0;i<errores.length;i++){
    $('#mensajeError .textoMensaje').append($('<h4></h4>').text(errores[i]));
  }
  $('#mensajeError').hide();
  setTimeout(function(){
      $('#mensajeError').show();
  },250);
}

function obtenerIdFiscalizador(id_casino,str){
  let f = $('#datalist'+id_casino).find('option:contains("'+str+'")');
  if(f.length == 0) return null;
  else return f.attr('data-id');
}

function enviarFormularioCarga(
  id_casino,
  id_relevamiento,
  subrelevamiento,
  succ = function(data){console.log(data);},
  err = function(data){console.log(data);},
  url="relevamientosProgresivo/cargarRelevamiento"){

  let formData = {
    id_casino : id_casino,
    id_relevamiento_progresivo : id_relevamiento,
    subrelevamiento : subrelevamiento,
    fecha_ejecucion : $('#fecha').val(),
    id_usuario_fiscalizador : obtenerIdFiscalizador(id_casino,$('#usuario_fiscalizador').val()),
    observaciones : $('#observacion_carga').val(),
    detalles : []
  };

  let filas = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr').not('.filaEjemplo');

  for(let i = 0;i<filas.length;i++){
    let fila = $(filas[i]);
    let id_detalle_relevamiento_progresivo = fila.attr('data-id');
    let causaNoToma = fila.find('.causaNoToma').val();
    let niveles = [];

    if(causaNoToma == -1){
      causaNoToma = null;
      fila.find('input:not([disabled])')
        .each(function(idx,c){
          let valor = $(c).val();
          let nro = $(c).attr('title');
          let id_nivel = $(c).attr('data-id');
          niveles.push({
            valor : valor,
            numero : nro,
            id_nivel : id_nivel
          });
        });
    }


    formData.detalles.push({
      id_detalle_relevamiento_progresivo: id_detalle_relevamiento_progresivo,
      niveles: niveles,
      id_tipo_causa_no_toma: causaNoToma
    });

  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: "POST",
      url: url,
      data: formData,
      dataType: 'json',
      success: succ,
      error: err
  });

}

function enviarFormularioValidacion(
  id_casino,
  id_relevamiento,
  subrelevamiento){

  let url = "relevamientosProgresivo/validarRelevamiento";

  let formData = {
    id_casino : id_casino,
    id_relevamiento_progresivo : id_relevamiento,
    subrelevamiento : subrelevamiento,
    observacion_validacion : $('#observacion_validacion').val()
  };

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: "POST",
      url: url,
      data: formData,
      dataType: 'json',
      success: function (data) {
                                console.log(data);
                                $('#modalCargaRelevamientoProgresivos').modal('hide');},
      error: function (data){console.log(data);}
  });

}

function validarFormulario(id_casino){
  let errores = false;
  let mensajes = [];
  let fisca = $('#usuario_fiscalizador').val();
  if(fisca == ""
  || obtenerIdFiscalizador(id_casino,fisca) === null){
    errores = true;
    mensajes.push("Ingrese un fiscalizador");
  }

  let fecha = $('#fecha').val();
  if(fecha == ""){
    errores = true;
    mensajes.push("Ingrese una fecha de ejecución");
  }

  let filas = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr')
  .not('.filaEjemplo');
  let inputs = filas.find('input:not([disabled])');
  for(let i = 0;i<inputs.length;i++){
    let input = $(inputs[i]);
    if(input.val()==""){
      errores = true;
      mensajes.push("Tiene al menos un nivel sin ingresar");
      break;
    }
  }
  return {errores: errores, mensajes: mensajes};
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

$('.cabeceraTablaPozos th.sortable').click(function(){
  let sort_by = $(this).attr('data-id');
  let filas = $('.cuerpoTablaPozos tr');
  console.log(sort_by);
  if($(this).attr('sorted') === undefined){
    $(this).attr('sorted',false);
  }

  let xor = $(this).attr('sorted');

  if(xor === "true") $(this).attr('sorted',false);
  else $(this).attr('sorted',true);

  function comp(a,b){
    let aa = $(a).find('.'+sort_by)[0];
    let bb = $(b).find('.'+sort_by)[0];

    let aa_type = aa.tagName;
    let bb_type = bb.tagName;

    if(aa_type === bb_type){
      if(aa_type === "TD"){
        return aa.textContent.localeCompare(bb.textContent) != xor;
      }
      else throw "Comparison not programmed.";
    }
    else throw "Error not matching types in comparison";
  }

  let reordenadas = ordenar(filas,comp,
    function(add){
      let clonado = $(add).clone();
      //Tengo que setear todo de vuelta, el clone no clona bien -___-
      clonado.find('.causaNoToma').val($(add).find('.causaNoToma').val());
      clonado.find('.causaNoToma').change(function(){
        causaNoTomaCallback(this);
      });
      return clonado;
    }
  );

  $('.cuerpoTablaPozos').empty();
  for(let i = 0;i<reordenadas.length;i++){
    $('.cuerpoTablaPozos').append(reordenadas[i]);
  }
})



function ordenar(list,comp,onadd=function(add){return add;}){
  //Encuentra el optimo valor, con una lista negra
  function find_val(list,comp,blacklist){
    let ret = null;
    let ret_idx = null;
    for(let i=0;i<list.length;i++){
      let item = list[i];
      if(!blacklist[i] && (ret_idx === null || comp(item,ret))){
        ret = item;
        ret_idx = i;
      }
    }
    return {elem: ret, index: ret_idx};
  }

  let newlist = [];
  let used = [];

  for(let i=0;i<list.length;i++){
    used.push(false);
  }

  for(let i=0;i<list.length;i++){
    let to_add = find_val(list,comp,used);
    newlist.push(onadd(to_add.elem));
    used[to_add.index]=true;
  }

  return newlist;
}
