var salida; //cantidad de veces que se apreta salir
var guardado;
var sectores;
var confirmacion = 0 ;


$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#layout').removeClass();
  $('#layout').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Layout Total');
  $('#opcLayoutTotal').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcLayoutTotal').addClass('opcionesSeleccionado');

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
    startView: 4,
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
    format: 'dd MM yyyy HH:ii',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 0,
    ignoreReadonly: true,
    minuteStep: 5,
  });

  limpiarModal();
  $('.mensajeConfirmacion').hide();
  $('#iconoCarga').hide();
  $('#btn-buscar').trigger('click',[1,10,'layout_total.fecha','desc']);

});

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();

    window.open('layouts/generarPlanillaLayoutTotales/' + $(this).val(),'_blank');

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
$('#btn-minimizarSinSistema').click(function(){
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
$('#btn-minimizarValidar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| LAYOUT TOTAL');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//ABRIR MODAL DE NUEVO LAYOUT
$('#btn-nuevoLayoutTotal').click(function(e){
  e.preventDefault();
  limpiarModal();
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#modalLayoutTotal').modal('show');

  $.get("obtenerFechaActual", function(data){
    $('#fechaActual').val(data.fecha);
    $('#fechaDate').val(data.fechaDate);
  });
});

$('#btn-finalizarValidacion').click(function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  formData = {
    id_layout_total: $('#id_layout_total').val(),
    observacion_validacion: $('#observacion_validar').val(),
  }

  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host +'/layouts/validarLayoutTotal',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('#mensajeExito h3').text('ÉXITO DE VALIDACIÓN');
        $('#mensajeExito .cabeceraMensaje').removeClass('modificar');
        $('#mensajeExito p').text("Se ha validado correctamente el control de Layout Total.");
        $('#mensajeExito').show();
        //Una vez validido disparo evento buscar con fecha descendentemente
        $('#btn-buscar').trigger('click');
        $('#modalValidarControl').modal('hide');
      },
      error: function (data) {
        var response = JSON.parse(data.responseText);

        // if(response.maquinas.length){
        //   for (var i = 0; i < response.maquinas.length; i++) {
        //     typeof response.maquinas[i].no_existe !== ?
        //   }
        // }

        if(typeof response.observacion_validacion !== 'undefined'){
          mostrarErrorValidacion($('#observacion_validacion'),response.observacion_validacion[0] ,true );
        }



      }
  });
})

$("#btn-layoutSinSistema").click(function(e){
  e.preventDefault();
  limpiarModal();
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#modalLayoutSinSistema').modal('show');
})

$("#btn-backup").click(function(){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var formData = {
    fecha: $('#fechaLayoutSinSistema').val(),
    fecha_generacion: $('#fechaGeneracionSinSistema').val(),
    id_casino: $('#casinoSinSistema option:selected').val(),
  }

  console.log(formData);

  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host +'/layouts/usarLayoutTotalBackup',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);


        var pageNumber = $('#herramientasPaginacion').getCurrentPage();
        var tam = $('#tituloTabla').getPageSize();
        var columna = $('#tablaLayouts .activa').attr('value');
        var orden = $('#tablaLayouts .activa').attr('estado');
        $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
        $('frmLayoutSinSistema').trigger('reset');
        $('#modalLayoutSinSistema').modal('hide');

      },
      error: function (data) {
        var response = JSON.parse(data.responseText);

        if(typeof response.fecha !== 'undefined'){
              mostrarErrorValidacion($('#fecha_backup'),response.fecha[0],true);
        }
        if(typeof response.fecha_generacion !== 'undefined'){
              mostrarErrorValidacion($('#fecha_generacion_backup'),response.fecha_generacion[0],true);
        }
        if(typeof response.id_casino !== 'undefined'){
              mostrarErrorValidacion($('#casinoSinSistema'),response.id_casino[0],true);
        }
      }
  });

})

//GENERAR RELEVAMIENTO
$('#btn-generar').click(function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var formData = {
    id_casino: $('#casino').val(),
    turno: $('#turno').val(),
  }

  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host +'/layouts/crearLayoutTotal',
      data: formData,
      dataType: 'json',
      beforeSend: function(data){
        console.log('Empezó');
        $('#modalLayoutTotal').find('.modal-footer').children().hide();
        $('#modalLayoutTotal').find('.modal-body').children().hide();

        $('#iconoCarga').show();
      },
      success: function (data) {

        $('#modalLayoutTotal').modal('hide');
        $('#frmLayoutTotal').trigger('reset');
        var pageNumber = $('#herramientasPaginacion').getCurrentPage();
        var tam = $('#tituloTabla').getPageSize();
        var columna = $('#tablaLayouts .activa').attr('value');
        var orden = $('#tablaLayouts .activa').attr('estado');
        $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);

        var iframe;
        iframe = document.getElementById("download-container");
        if (iframe === null){
            iframe = document.createElement('iframe');
            iframe.id = "download-container";
            iframe.style.visibility = 'hidden';
            document.body.appendChild(iframe);
        }
        iframe.src = data.url_zip;


      },
      error: function (data) {

        $('#iconoCarga').hide();
        $('#modalLayoutTotal').find('.modal-footer').children().show();
        $('#modalLayoutTotal').find('.modal-body').children().show();

        var response = JSON.parse(data.responseText);

        if(typeof response.id_casino !== 'undefined'){
              mostrarErrorValidacion($('#casino'), response.id_casino[0] ,true);
        }
        if(typeof response.turno !== 'undefined'){
              mostrarErrorValidacion($('#turno'), "Valor de turno incorrecto.",true);
        }

      }
  });


});

$(document).on('input' , '#frmCargaControlLayout input' ,function(){
  habilitarBotonGuardar();
})

$(document).on('click','.carga',function(e){
  e.preventDefault();
  limpiarModal();
  //ocultar mensaje de salida
  salida = 0;
  guardado = true;
  $('#modalCargaControlLayout .mensajeSalida').hide();
  $('#mensajeExito').hide();

  var id_layout_total = $(this).val();
  $('#id_layout_total').val(id_layout_total);

  //SI ESTÁ GUARDADO NO MUESTRA EL BOTÓN PARA GUARDAR
  $('#btn-guardar').hide();
  $('#btn-finalizar').hide();


  $.get('http://' + window.location.host +'/layouts/obtenerLayoutTotal/' + id_layout_total, function(data){
      $('#cargaFechaActual').val(data.layout_total.fecha);
      $('#cargaFechaGeneracion').val(data.layout_total.fecha_generacion);
      $('#cargaCasino').val(data.casino.nombre);
      $('#cargaTurno').val(data.layout_total.turno);

      $('#fecha').val(data.layout_total.fecha_ejecucion);
      $('#fecha_ejecucion').val(data.layout_total.fecha_ejecucion);

      if (data.usuario_cargador != null) {
          $('#fiscaCarga').val(data.usuario_cargador.nombre);
      }

      if (data.usuario_fiscalizador != null) {
        $('#inputFisca').val(data.usuario_fiscalizador.nombre)
                        .attr('data-fisca',data.usuario_fiscalizador.id_usuario)
                        .prop('readonly',true);
      }

      sectores = data.sectores;

      $('#inputFisca').generarDataList('usuarios/buscarUsuariosPorNombreYCasino/'+ data.casino.id_casino,'usuarios','id_usuario','nombre',2);
      $('#inputFisca').setearElementoSeleccionado(0,"");
      if (data.usuario_fiscalizador){
        $('#inputFisca').setearElementoSeleccionado(data.usuario_fiscalizador.id_usuario,data.usuario_fiscalizador.nombre);
      }


      $('#tablaCargaControlLayout tbody tr').remove();

      var tablaCargaRelevamiento = $('#tablaCargaControlLayout tbody');

  });

  $('#modalCargaControlLayout').modal('show');
});

$(document).on('click','.validar',function(e){
  e.preventDefault();
  limpiarModal();
  //ocultar mensaje de salida
  salida = 0;
  guardado = true;
  $('#modalValidarControlLayout .mensajeSalida').hide();
  $('#mensajeExito').hide();

  var id_layout_total = $(this).val();
  $('#id_layout_total').val(id_layout_total);

  //SI ESTÁ GUARDADO NO MUESTRA EL BOTÓN PARA GUARDAR
  $('#btn-guardar').hide();
  $('#btn-finalizar').hide();

  $.get('http://' + window.location.host +'/layouts/obtenerTotalParaValidar/' + id_layout_total, function(data){

      $('#validarFechaActual').val(data.layout_total.fecha);
      $('#validarFechaGeneracion').val(data.layout_total.fecha_generacion);
      $('#validarCasino').val(data.casino);
      $('#validarTurno').val(data.layout_total.turno);

      $('#fecha').val(data.layout_total.fecha_ejecucion);
      $('#validarFechaEjecucion').val(data.layout_total.fecha_ejecucion);

      if(data.usuario_cargador != null) {
          $('#validarFiscaCarga').val(data.usuario_cargador.nombre);
      }

      if(data.usuario_fiscalizador != null) {
        $('#validarInputFisca').val(data.usuario_fiscalizador.nombre)
                        .attr('data-fisca',data.usuario_fiscalizador.id_usuario)
                        .prop('readonly',true);
      }

      sectores = data.sectores;

      $('#tablaValidarControlLayout tbody tr').remove();

      var tablaCargaRelevamiento = $('#tablaValidarControlLayout tbody');

      for (var i = 0; i < data.detalles.length; i++) {
        agregarNivel(data.detalles[i] , $('#validarControlLayout') ,'validar');
      }
  });

  $('#modalValidarControl').modal('show');
  $('#btn-agregarNivel').hide();
});

$('.modal').on('hidden.bs.modal', function(){//se ejecuta cuando se oculta modal con clase .modal

  $('#tecnico').popover('hide');
  $('#fecha').popover('hide');
  $('#frmLayoutTotal').trigger('reset');
  $('#frmLayoutSinSistema').trigger('reset');
  $('#inputFisca').popover('hide');
  $('#inputFisca').prop('readonly' ,false);
  $('#inputFisca').val('');
  $('#fiscaCarga').val('');

  //validar
  $('#frmValidarControlLayout').trigger('reset');
  $('#validarFechaActual').val('');
  $('#validarFechaGeneracion').val('');
  $('#validarCasino').val('');
  $('#validarTurno').val('');
  $('#validarFiscaCarga').val('');
  $('#validarInputFisca').val('');
  $('#validarFechaEjecucion').val('');
  $('#validarControlLayout').empty();

  ocultarErrorValidacion($('#casino'));
  ocultarErrorValidacion($('#turno'));
  ocultarErrorValidacion($('#fecha'));

  $('#mensajeConfirmacion').hide();
  confirmacion = 0 ;
})

//SALIR DEL RELEVAMIENTO
$('#btn-salir').click(function(){
  //Si está guardado deja cerrar el modal
  if (guardado) $('#modalCargaControlLayout').modal('hide');
  //Si no está guardado
  else{
    if (salida == 0) {
      $('#modalCargaControlLayout .mensajeSalida').show();
      salida = 1;
    }else {
      $('#modalCargaControlLayout').modal('hide');
    }
  }
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('.selectCasinos').on('change',function(){
  var id_casino = $('option:selected' , this).attr('id');
  var selectCasino = $(this)

  $.get('http://' + window.location.host +"/casinos/obtenerTurno/" + id_casino, function(data){
      $('#turno').val(data.turno);
  });
});

//FINALIZAR CARGA RELEVAMIENTO
$('#btn-guardar').click(function(e){
  e.preventDefault();
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var maquinas = [];
  $('#tablaCargaControlLayout tbody tr').each(function(){
    var maquina = {
      id_sector :  $(this).find('.sector').val(),
      nro_isla  : $(this).find('.nro_isla').val(),
      nro_admin : $(this).find('.nro_admin').val(),
      id_maquina : $(this).find('.nro_admin').obtenerElementoSeleccionado(),
      co : $(this).find('.co').val(),
      pb : $(this).find('.pb ').is(':checked')  == true ? 1 :  0,
    };
    maquinas.push(maquina);
  })

  formData= {
    id_fiscalizador_toma :  $('#inputFisca').obtenerElementoSeleccionado(),
    id_layout_total:   $('#id_layout_total').val(),
    fecha_ejecucion: $('#fecha_ejecucion').val(),
    maquinas: maquinas,
    observacion_fiscalizacion: $('#observacion_carga').val(),
    confirmacion: confirmacion
  }

  console.log(formData);
  $.ajax({
      type: 'POST',
      url: 'http://' + window.location.host +'/layouts/cargarLayoutTotal',
      data: formData,
      dataType: 'json',
      success: function (resultados) {
        $('#mensajeExito h3').text('ÉXITO DE CARGA');
        $('#mensajeExito .cabeceraMensaje').addClass('modificar');
        $('#mensajeExito p').text("Se ha cargado correctamente el control de Layout Total.");
        $('#mensajeExito').show();

        //se puede eliminar, la misma logica lo hace el boton buscar
        var pageNumber = $('#herramientasPaginacion').getCurrentPage();
        var tam = $('#tituloTabla').getPageSize();
        var columna = $('#tablaLayouts .activa').attr('value');
        var orden = $('#tablaLayouts .activa').attr('estado');
        $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
        $('#modalCargaControlLayout').modal('hide');
      },
      error: function (data) {
        var response = JSON.parse(data.responseText);
        var bandera_error_no_aceptable= false;//bandera true ocurrio un error que no necesite ser corregido
        var bandera_error_aceptable = false;//bandera true si necesito pedir confirmacion
        if(typeof response.id_fiscalizador_toma !== 'undefined'){
              mostrarErrorValidacion($('#inputFisca'),response.id_fiscalizador_toma[0] ,true);
              bandera_error_no_aceptable = true;

        }

        if(typeof response.fecha_ejecucion !== 'undefined'){
              mostrarErrorValidacion($('#fecha'),response.fecha_ejecucion[0] ,true);
              bandera_error_no_aceptable = true;
        }
        var i = 0;

        $('#controlLayout tr').each(function(){
          if(typeof response['maquinas.'+ i +'.id_sector'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.sector') ,response['maquinas.'+ i +'.id_sector'][0] ,false);
            bandera_error_no_aceptable = true;
          }
          if(typeof response['maquinas.'+ i +'.nro_isla'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.nro_isla') , response['maquinas.'+ i +'.nro_isla'][0],false);
            bandera_error_no_aceptable = true;
          }
          if(typeof response['maquinas.'+ i +'.nro_admin'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.nro_admin'), response['maquinas.'+ i +'.nro_admin'][0],false);
            bandera_error_no_aceptable = true;
          }
          if(typeof response['maquinas.'+ i +'.no_existe'] !== 'undefined'){
            filaError = i;
            mostrarErrorValidacion($(this).find('.nro_isla') , response['maquinas.'+ i +'.no_existe'][0],false);
            bandera_error_aceptable = true;
          }
          i++;
        })
        if(bandera_error_aceptable && !bandera_error_no_aceptable){
          pedirValidacion();
        }else{
          $('.mensajeConfirmacion').hide();
        }
      }
    });

});

function pedirValidacion(){
  confirmacion = 1;
  $('.mensajeConfirmacion').show();
}

function agregarMaquinaConDiferencia(renglon ,estado){
  if(estado == true){
    var id = renglon.attr('id')
    if($('#maquina'+id).length==0){
          $('#encabezado_diferencia').show();

          $('#maquinas_con_diferencia')
              .append($('<div>')
                  .addClass('row')
                  .css('margin-bottom','15px')
                  .attr('id','maquina' + renglon.attr('id'))
                  .append($('<div>')
                        .addClass('col-xs-1 col-xs-offset-1')
                        .css('padding-right','0px')
                        .append($('<span>')
                            .text(renglon.find('.col_nro_admin').text())
                        )
                  )
                  .append($('<div>')
                        .addClass('col-xs-1 nro_isla_dif')
                        .css('padding-right','0px')
                        .append($('<input>')
                            .attr('id','nro_isla')
                            .attr('type','text')
                            .attr('placeholder','Nro Isla')
                            .addClass('form-control')

                        )

                  )
                  .append($('<div>')
                        .addClass('col-xs-1 marca_dif')
                        .css('padding-right','0px')
                        .append($('<input>')
                            .attr('id','marca')
                            .attr('type','text')
                            .attr('placeholder','Fabricante')
                            .addClass('form-control')
                        )
                  )
                  .append($('<div>')
                        .addClass('col-xs-2 juego_dif')
                        .css('padding-right','0px')
                        .append($('<input>')
                            .attr('id','porc_visible')
                            .attr('type','text')
                            .attr('placeholder','Nombre Juego')
                            .addClass('form-control')
                        )
                  )
                    .append($('<div>')
                        .addClass('col-xs-1 nro_serie_dif')
                        .css('padding-right','0px')
                        .append($('<input>')
                            .attr('id','nro_serie')
                            .attr('type','text')
                            .attr('placeholder','Nro Serie')
                            .addClass('form-control')
                        )
                  )

                  .append($('<div>')
                          .addClass('col-xs-1 den_dif')
                          .css('padding-right','0px')
                          .append($('<input>')
                              .attr('type','text')
                              .attr('placeholder','Denominación')
                              .addClass('form-control')
                          )
                  )
                  .append($('<div>')
                          .addClass('col-xs-1 porc_dif')
                          .css('padding-right','0px')
                          .append($('<input>')
                              .attr('type','text')
                              .attr('placeholder','% Devolución')
                              .addClass('form-control')
                          )
                  )

                 )

    }
    actualizarDiferencia(renglon);
  }else {
    if(estaSinDiferencia(renglon)){
      $('#maquina' + renglon.attr('id')).remove();
    }else{
      actualizarDiferencia(renglon);
    }

    if( $('#maquinas_con_diferencia').children().length == 0 ) {
      $('#encabezado_diferencia').hide();
    }
  }

}

function habilitarBotonGuardar(){
  guardado = false;
  $('#btn-guardar').show();
}

// Todo busqueda Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

      var page_size = (page_size == null || isNaN(page_size) ) ? size : page_size;
      var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
      var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaLayouts .activa').attr('value'),orden: $('#tablaLayouts .activa').attr('estado')} ;
      if(sort_by == null){ // limpio las columnas
        $('#tablaLayouts th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
      }

      //Fix error cuando librería saca los selectores
      if(isNaN($('#herramientasPaginacion').getPageSize())){
        var size = 10; // por defecto
      }else {
        var size = $('#herramientasPaginacion').getPageSize();
      }

      var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
      // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
      var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
      var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaLayouts .activa').attr('value'),orden: $('#tablaLayouts .activa').attr('estado')} ;
      if(sort_by == null){ // limpio las columnas
        $('#tablaLayouts th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
      }

    var formData = {
      fecha: $('#buscadorFecha').val(),
      casino: $('#buscadorCasino').val(),
      turno: $('#buscadorTurno').val(),
      estadoRelevamiento: $('#buscadorEstado').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    }

    $.ajax({
        type: 'POST',
        url: 'http://' + window.location.host +'/layouts/buscarLayoutsTotales',
        data: formData,
        dataType: 'json',
        success: function (resultados) {
            console.log(resultados);

            $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
            $('#cuerpoTabla tr').remove();
            for (var i = 0; i < resultados.data.length; i++){

              var fila = generarFilaTabla(resultados.data[i]);
              $('#cuerpoTabla')
                  .append(fila);
            }

            mostrarIconosPorPermisos();


            $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

        },
        error: function (data) {
            console.log('Error:', data);
        }
      });
});

//Se usa para mostrar los iconos según los permisos del usuario
function mostrarIconosPorPermisos(){
    var formData = {
        permisos : ["ver_planilla_layout_total","carga_layout_total","validar_layout_total"],
    }

    $.ajax({
      type: 'GET',
      url: 'usuarios/usuarioTienePermisos',
      data: formData,
      dataType: 'json',
      success: function(data) {

        //Para los iconos que no hay permisos: OCULTARLOS!
        if (!data.ver_planilla_layout_total) $('.planilla').hide();
        if (!data.carga_layout_total) $('.carga').hide();
        if (!data.validar_layout_total) $('.validar').hide();

      },
      error: function(error) {
          console.log(error);
      },
    });
}

$(document).on('click','#tablaLayouts thead tr th[value]',function(e){
  $('#tablaLayouts th').removeClass('activa');
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
  $('#tablaLayouts th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaLayouts .activa').attr('value');
  var orden = $('#tablaLayouts .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(layout_total){

    var fila = $(document.createElement('tr'));
    fila.attr('id', layout_total.id_layout_total)
        .append($('<td>').addClass('col-xs-2')
            .text((layout_total.fecha))
        )
        .append($('<td>').addClass('col-xs-2')
            .text(layout_total.casino)
        )
        .append($('<td>').addClass('col-xs-2') /* REEMPLAZAR EN TURNO */
            .text(layout_total.turno)
        )
        .append($('<td>').addClass('col-xs-3')
            .append($('<i>').addClass('fas fa-fw fa-dot-circle'))
            .append($('<span>').text(layout_total.estado))
        )
        .append($('<td>').addClass('col-xs-3')
            .append($('<button>').addClass('btn btn-info planilla').attr('type','button').val(layout_total.id_layout_total)
                .append($('<i>').addClass('far').addClass('fa-fw').addClass('fa-file-alt'))
            )
            .append($('<span>').text(' '))
            .append($('<button>').addClass('btn btn-warning carga').attr('type','button').val(layout_total.id_layout_total)
                .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-upload'))
            )
            .append($('<span>').text(' '))
            .append($('<button>').addClass('btn btn-success validar').attr('type','button').val(layout_total.id_layout_total)
                .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-check'))
            )
            .append($('<span>').text(' '))
            .append($('<button>').addClass('btn btn-info imprimir').attr('type','button').val(layout_total.id_layout_total)
                .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-print'))
            )
        )
      var icono_planilla = fila.find('.planilla');
      var icono_carga = fila.find('.carga');
      var icono_validacion = fila.find('.validar');
      var icono_impirmir = fila.find('.imprimir');

      switch (layout_total.estado) {
          case 'Generado':
            fila.find('.fa-dot-circle').addClass('faGenerado');

            icono_planilla.show();
            icono_carga.show();
            icono_validacion.hide();
            icono_impirmir.hide();
            break;
          case 'Cargando':
            fila.find('.fa-dot-circle').addClass('faCargando');
            break;
          case 'Finalizado':
            fila.find('.fa-dot-circle').addClass('faFinalizado');
            icono_validacion.show();
            icono_impirmir.show();
            icono_carga.hide();
            icono_planilla.hide();
            break;
          case 'Validado':
              fila.find('.fa-dot-circle').addClass('faValidado');
              icono_validacion.hide();
              icono_impirmir.show();
              icono_carga.hide();
              icono_planilla.hide();
              break;
          default:break;

      }
      return fila;
}

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
// $(document).on('click','.planilla',function(){
//     $('#alertaArchivo').hide();
//
//     window.open('layouts/generarPlanillaLayoutTotales/' + $(this).val(),'_blank');
//
// });
//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.imprimir',function(){
    $('#alertaArchivo').hide();

    window.open('layouts/generarPlanillaLayoutTotalesCargado/' + $(this).val(),'_blank');

});

//Agrega nivel de layout
$(document).on('click','.btn-agregarNivel',function(){
    $('#controlLayout').show();
    agregarNivel(null,$('#controlLayout'),'carga');
});

//borrar un nivel de layout
$(document).on('click','.borrarNivelLayout',function(){
    // var index = $('#controlLayout').parent().parent().index();
    //
    // $('#controlLayout').children().eq(index).remove();

    $(this).parent().parent().remove();

});

function agregarNivel(nivel,tabla,funcion){

  var id_nivel_layout = ((nivel != null) ? nivel.id_nivel_layout: "");
  var sector = ((nivel != null) ? nivel.descripcion_sector: "");
  var nIsla = ((nivel != null) ? nivel.nro_isla: null);
  var nAdmin = ((nivel != null) ? nivel.nro_admin: null);
  var co = ((nivel != null) ? nivel.co: null);
  var pBloq = ((nivel != null) ? nivel.pb : null);

  var editable = funcion == 'carga' ? true : false;

          tabla.append($('<tr>')
                  .addClass('NivelLayout')
                  .attr('id_nivel_layou',id_nivel_layout)
                  .append($('<td>')
                  )
                  .append($('<td>')
                    .append($('<select>')
                        .attr('type','text')
                        .addClass('form-control sector')
                        .attr('disabled' , !editable)
                    )
                  )
                .append($('<td>')
                  .append($('<input>')
                        .attr('type','text')
                        .attr('placeholder','Isla')
                        .addClass('form-control nro_isla')
                        .val(nIsla)
                        .attr('readonly' , !editable)
                    )
                  )
                .append($('<td>')
                    .append($('<input>')
                        .attr('type','text')
                        .attr('placeholder','N° ADMIN')
                        .addClass('form-control nro_admin')
                        .val(nAdmin)
                        .attr('readonly' , !editable)
                    )
                  )
                .append($('<td>')
                    .append($('<input>')
                        .attr('type','text')
                        .attr('placeholder','C.0')
                        .addClass('form-control co')
                        .val(co)
                        .attr('readonly' , !editable)
                    )
                  )

                .append($('<td>')
                    .css('text-align' , 'center')
                    .append($('<input>')
                    .attr('type','checkbox')
                    .addClass('pb')
                    .prop('checked' , pBloq)
                    .prop('disabled' , !editable)
                )
              )
            )


            if( funcion == 'carga' ){//agrego buscador y boton borrar (renglon)
              $('.nro_admin:last').generarDataList("http://" + window.location.host + "/maquinas/obtenerMTMEnCasino/" + sectores[0].id_casino  ,'maquinas','id_maquina','nro_admin',1,false);
              $('.nro_admin:last').setearElementoSeleccionado(0,"");
                $('.NivelLayout:last()',tabla)
                  .append($('<td>')
                    .append($('<button>')
                      .addClass('borrarNivelLayout')
                      .addClass('btn')
                      .addClass('btn-danger')
                      .addClass('borrarFila')
                      .attr('type','button')
                      .append($('<i>')
                        .addClass('fa fa-fw fa-trash')
                      )
                  )
                )
           }else if(funcion == 'validar'){
            var boton_gestionar = $('<a>').addClass('btn btn-success pop gestion_maquina')
                                                  .attr('type' , 'button')
                                                 .attr('href' , 'http://' + window.location.host + '/maquinas/' + nivel.id_maquina )
                                                 .attr('target' , '_blank')
                                                 .attr("data-placement" , "top")
                                                 .attr('data-trigger','hover')
                                                 .attr('title','GESTIONAR MÁQUINA')
                                                 .attr('data-content','Ir a sección máquina')
                                                 .append($('<i>').addClass('fa fa-fw fa-wrench'));
            $('.NivelLayout:last()',tabla)
                    .append($('<td>').append(boton_gestionar));
            $('.gestion_maquina').popover({
              html:true
            });
          }


          cargarSectores(sectores ,sector , tabla);
}

function cargarSectores(sectores, seleccionado , tabla){
  var select = $('.NivelLayout:last()',tabla).find('.sector')

  for (var i = 0; i < sectores.length; i++) {
      select.append($('<option>')
          .val(sectores[i].id_sector)
          .text(sectores[i].descripcion)
    )
    if(seleccionado == sectores[i].descripcion){
      var id_sector = sectores[i].id_sector;
    }
  }
  $('.NivelLayout:last()',tabla).trigger('change');
  $('.NivelLayout:last()',tabla).find('.sector').val(id_sector);//carga validacion
}

function limpiarModal(){
    $('#iconoCarga').hide();
    $('#modalLayoutTotal').find('.modal-footer').children().show();
    $('#modalLayoutTotal').find('.modal-body').children().show()
    $('#controlLayout .NivelLayout').remove();
}
