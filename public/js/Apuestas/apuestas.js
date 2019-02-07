$(document).ready(function() {

    $('#barraMesas').attr('aria-expanded','true');
    $('#mesasPanio').removeClass();
    $('#mesasPanio').addClass('subMenu1 collapse in');
    $('.tituloSeccionPantalla').text('Relevamientos de Valores Mínimos de Apuestas');
    $('#opcApuestas').attr('style','border-left: 6px solid #185891; background-color: #131836;');
    $('#opcApuestas').addClass('opcionesSeleccionado');

    $('#filtroTurno').val('0');
    $('#B_fecha_filtro').val('');
    $('#filtroCasino').val('0');


    $(function(){
      $('#dtpFecha').datetimepicker({
            language:  'es',
            todayBtn:  1,
            autoclose: 1,
            todayHighlight: 1,
            format: 'yyyy-mm-dd',
            pickerPosition: "bottom-left",
            startView: 4,
            minView: 2
          });
    });
    $(function(){
      $('#dtpFechaCarga').datetimepicker({
            language:  'es',
            todayBtn:  1,
            autoclose: 1,
            todayHighlight: 1,
            format: 'yyyy-mm-dd',
            pickerPosition: "bottom-left",
            startView: 4,
            minView: 2,
            container:$('#modalCarga')
          });
    });
    $(function(){
      $('#dtpFechaBUp').datetimepicker({
            language:  'es',
            todayBtn:  1,
            autoclose: 1,
            todayHighlight: 1,
            format: 'yyyy-mm-dd',
            pickerPosition: "bottom-left",
            startView: 4,
            minView: 2,
            container:$('#modalCargaBackUp')
          });
    });
    $(function(){
      $('#dtpFechaBUpEjecucion').datetimepicker({
            language:  'es',
            todayBtn:  1,
            autoclose: 1,
            todayHighlight: 1,
            format: 'yyyy-mm-dd',
            pickerPosition: "bottom-left",
            startView: 4,
            minView: 2,
            container:$('#modalCargaBackUp')
          });
    });
    $('#modalCarga #agregarFisca').click(clickAgregarFisca);
    $('#modalCargaBackUp #agregarFiscaBUp').click(clickAgregarFisca);

    $('#modalModificar #agregarFiscaMod').click(clickAgregarFiscaMod);

    $('#btn-buscar-apuestas').trigger('click',[1,10,'fecha','desc']);

});

//btn BUSCAR APUESTAS, con paginación
$('#btn-buscar-apuestas').click(function(e,pagina,page_size,columna,orden){

  e.preventDefault();

    $('#tablaResultadosApuestas tbody tr').remove();

    //Fix error cuando librería saca los selectores
    if(isNaN($('#herramientasPaginacion').getPageSize())){
      var size = 10; // por defecto
    }
    else {
      var size = $('#herramientasPaginacion').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultadosApuestas .activa').attr('value'),orden: $('#tablaResultadosApuestas .activa').attr('estado')} ;

    if(sort_by == null){ // limpio las columnas
      $('#tablaResultadosApuestas th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

        var formData= {
          fecha: $('#B_fecha_filtro').val(),
          id_turno:$('#filtroTurno').val(),
          id_casino: $('#filtroCasino').val(),
          page: page_number,
          sort_by: sort_by,
          page_size: page_size,
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.ajax({
            type: 'POST',
            url: '/apuestas/buscarRelevamientosApuestas',
            data: formData,
            dataType: 'json',

            success: function (data){

                  $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.apuestas.total,clickIndice);

                    for (var i = 0; i < data.apuestas.data.length; i++) {

                        var fila=  generarFila(data.apuestas.data[i]);

                        $('#tablaResultadosApuestas tbody').append(fila);
                    }

              $('#herramientasPaginacion').generarIndices(page_number,page_size,data.apuestas.total,clickIndice);

            },
            error: function(data){
            },
        })


})

$('#btn-backUp').on('click',function(e){

  e.preventDefault();

  $('#B_fecha_bup').val('').prop('readonly',false);
  $('#B_fecha_bupEj').val('').prop('readonly',false);
  $('#turnoRelevadoBUp').val('').prop('readonly',false);
  $('#turnoRelevadoBUp').generarDataList("turnos/buscarTurnos" ,'turnos','id_turno','nro_turno' ,1,true);
  $('#turnoRelevadoBUp').setearElementoSeleccionado('',0);

  $('.desplegarCarga').hide();
  $('#mensajeErrorBuscarBUp').hide();
  $('#mensajeErrorCargaBUp').hide();

  $('#btn-guardar-backUp').hide();


  $('#modalCargaBackUp').modal('show');

})

$('#buscarBackUp').on('click', function(e){

  e.preventDefault();

  limpiarCargaBUp();

  var fechaCreacion = $('#B_fecha_bup').val();
  var fechaEjecucion = $('#B_fecha_bupEj').val();
  var turno = $('#turnoRelevadoBUp').obtenerElementoSeleccionado();


  if(fechaCreacion !='' && turno !=''  && fechaEjecucion !=''){

    $('#B_fecha_bup').prop('readonly',true);
    $('#turnoRelevadoBUp').prop('readonly',true);
    $('#B_fecha_bupEj').prop('readonly',true);

    var formData= {
      fecha: fechaEjecucion,
      nro_turno: turno,
      created_at: fechaCreacion,

    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: 'POST',
        url: 'apuestas/obtenerRelevamientoBackUp',
        data: formData,
        dataType: 'json',

        success: function (data){

          $('#mensajeErrorCargaBUp').hide();

          $('.desplegarCarga').show();
          $('#btn-guardar-backUp').show();

        var id_relevamiento=data.relevamiento.id_relevamiento_apuestas;
        var id_casino=data.relevamiento.id_casino;

        $('#fiscalizadorBUp').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
        $('#hora_prop_BUp').val(data.relevamiento.hora_propuesta).prop('readonly',true);

        var aux_nro_fila = 0;

         for (var i = 0; i < data.mesasporjuego.length; i++) {
           for (var j = 0; j < data.mesasporjuego[i].mesas.length; j++){

             var fila= generarFilaCargaBUp(data.mesasporjuego[i].mesas[j],aux_nro_fila,data.estados);

             fila.find('.juego_up').text(data.mesasporjuego[i].juego);

             $('#tablaCargaBUp tbody').append(fila);
             aux_nro_fila++;
           }
         }

        },

        error: function (data) {

            var response= data.responseJSON;

              if(typeof response != 'undefined'){
                $('#mensajeErrorBuscarBUp').show();
              }
              $('#B_fecha_bup').prop('readonly',false);
              $('#turnoRelevadoBUp').prop('readonly',false);
              $('#B_fecha_bupEj').prop('readonly',false);
          }

     })
   }
   else {
     $('#B_fecha_bup').prop('readonly',false);
     $('#turnoRelevadoBUp').prop('readonly',false);
     $('#B_fecha_bupEj').prop('readonly',false);
     $('#mensajeErrorCargaBUp').show();
   }
});

//guardar backUp dentro del modal
$('#btn-guardar-backUp').on('click',function(e){

  e.preventDefault();

  var detalles=[];

  var f= $('#tablaCargaBUp tbody > tr');

  //recorro tabla para enviar datos de relevamiento
  $.each(f, function(index, value){

    if($(this) != 'undefined'){
      var d={
        id_detalle: $(this).attr('id'),
        minimo: $(this).find('.min_up').val(),
        maximo:$(this).find('.max_up').val(),
        id_estado_mesa:$(this).find('.estado_up').val(),
      }
        detalles.push(d);
    }
      })


      var formData= {
        hora:$('#hora_ejec_BUp').val(),
        detalles:detalles,
        id_fiscalizador:$('#fiscalizadorBUp').obtenerElementoSeleccionado(),
        observaciones:$('#obsBUp').val(),
      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'apuestas/cargarRelevamiento',
          data: formData,
          dataType: 'json',

          success: function (data){

            $('#modalCargaBackUp').modal('hide');
            $('#mensajeExito h3').text('ÉXITO');
            $('#mensajeExito p').text('Relevamiento GUARDADO. ');
            $('#mensajeExito').show();
            $('#btn-buscar-apuestas').trigger('click',[1,10,'fecha','desc']);
          },
          error: function (reject) {
                if( reject.status === 422 ) {
                    var errors = $.parseJSON(reject.responseText);
                    $.each(errors, function (key, val) {
                      if(key == 'detalles'){

                        $('#mensajeErrorCargaBUp').show();
                      }
                      if(key == 'hora'){
                        mostrarErrorValidacion( $('#hora_ejec_BUp'),val[0],false);
                      }
                      if(key != 'hora' && key != 'detalles' && key != 'fiscalizadores' ){
                          var splitt = key.split('.');
                        mostrarErrorValidacion( $("#" + splitt[0]+splitt[1]+splitt[2] ),val[0],false);
                      }
                      if(key == 'fiscalizadores' ){
                        $('#mensajeErrorCargaBUp').show();
                      }

                    });
                }
            }
      })

});


//btn generar planillas
$('#btn-generar').on('click', function(e){

  e.preventDefault();

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });


  var formData = {

  }

  $.ajax({
      type: "POST",
      url: 'apuestas/generarRelevamientoApuestas',
      data: formData,
      dataType: 'json',

       beforeSend: function(data){
         $('#modalRelevamiento').modal('show');
         $('#modalRelevamiento').find('.modal-body').children('#iconoCarga').show();

      },
      success: function (data) {

          // $('#btn-buscar').click();
           $('#modalRelevamiento').modal('hide');

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
        console.log('error',data);

         $('#modalRelevamiento').modal('hide');

      }
  });



})


$(document).on('click', '.cargarApuesta', function(e){

  e.preventDefault();

    var id_relevamiento=$(this).val();
    limpiarCarga();

  $.get('apuestas/obtenerDatos/' + id_relevamiento, function(data){

      var id_casino=data.relevamiento.id_casino;

      $('#fiscalizadorCarga').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#B_fecha_carga').val(data.fecha).prop('readonly',true);
      $('#hora_prop_carga').val(data.relevamiento.hora_propuesta).prop('readonly',true);
      $('#turnoRelevado').val(data.turno.nro_turno).prop('readonly', true);

      var aux_nro_fila = 0;

       for (var i = 0; i < data.mesasporjuego.length; i++) {
         for (var j = 0; j < data.mesasporjuego[i].mesas.length; j++) {

           var fila= generarFilaCarga(data.mesasporjuego[i].mesas[j],aux_nro_fila,data.estados);

           fila.find('.juego_carga').text(data.mesasporjuego[i].juego);

           $('#tablaCarga tbody').append(fila);
           aux_nro_fila++;
         }
         $('#tablaCarga').css('display','');

       }

 })
  $('#modalCarga').modal('show');

})

//guardar carga de apuestas
$('#btn-guardar').on('click',function(e){

  e.preventDefault();

  var detalles=[];
  var fiscalizadores=[];

  var d= $('#fiscalizadoresPart tbody > tr');

  $.each(d, function(index, value){

    if($(this) != 'undefined'){

        fiscalizadores.push($(this).attr('id'));
    }
      })

  var f= $('#tablaCarga tbody > tr');

  //recorro tabla para enviar datos de relevamiento
  $.each(f, function(index, value){

    if($(this) != 'undefined'){
      var d={
        id_detalle: $(this).attr('id'),
        minimo: $(this).find('.min_carga').val(),
        maximo:$(this).find('.max_carga').val(),
        id_estado_mesa:$(this).find('.estado_carga').val(),
      }
        detalles.push(d);
    }
      })

      var formData= {
        hora:$('#hora_ejec_carga').val(),
        detalles:detalles,
        fiscalizadores:fiscalizadores,
        observaciones:$('#obsCarga').val(),
      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'apuestas/cargarRelevamiento',
          data: formData,
          dataType: 'json',

          success: function (data){

            $('#modalCarga').modal('hide');
            $('#mensajeExito h3').text('ÉXITO');
            $('#mensajeExito p').text('Relevamiento GUARDADO. ');
            $('#mensajeExito').show();
            $('#btn-buscar-apuestas').trigger('click',[1,10,'fecha','desc']);
          },
          error: function (reject) {
                if( reject.status === 422 ) {
                    var errors = $.parseJSON(reject.responseText);
                    console.log('ee',errors);
                    $.each(errors, function (key, val) {
                      if(key == 'detalles'){

                        $('#mensajeErrorCarga').show();
                      }
                      if(key == 'hora'){
                        mostrarErrorValidacion( $('#hora_ejec_carga'),val[0],false);
                      }
                      if(key != 'hora' && key != 'detalles' && key != 'fiscalizadores'){
                          var splitt = key.split('.');
                        mostrarErrorValidacion( $("#" + splitt[0]+splitt[1]+splitt[2] ),val[0],false);
                      }
                      if(key == 'fiscalizadores' ){
                        $('#mensajeErrorCarga').show();
                                        }
                    });
                }
            }
      })

})

//modificar relevamiento cargado
$(document).on('click', '.modificarApuesta', function(e){

  e.preventDefault();

  limpiarModificar();

  var id_relevamiento=$(this).val();

   $.get('apuestas/relevamientoCargado/' + id_relevamiento, function(data){

       var id_casino=data.relevamiento_apuestas.id_casino;

       $('#fiscalizadorMod').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
       console.log('dataaaa',data);
       for (var i = 0; i < data.fiscalizadores.length; i++) {
         var fila= generarTablaFisca(data.fiscalizadores[i]);
         $('#fiscalizadoresPartModif tbody').append(fila);
       }

       $('#B_fecha_modificar').val(data.relevamiento_apuestas.fecha).prop('readonly',true);
       $('#turnoRelevadoMod').val(data.turno.id_turno).prop('readonly', true);
       $('#obsModificacion').val(data.relevamiento_apuestas.observaciones);

       if(data.relevamiento_apuestas.hora_ejecucion != null){
         var p = data.relevamiento_apuestas.hora_ejecucion.split(':')
         var hs, mm;

         if(p.length === 3) {
           hs = p[0];
           mm = p[1];
         }
       }else{
         hs = '-';
         mm = '-';
       }

       $('#hora_ejec_mod').val(hs + ':' + mm);
       $('#hora_prop_mod').val(data.relevamiento_apuestas.hora_propuesta).prop('readonly',true);

       //sirve para crear indices sobre las filas de detalles
        var aux_nro_fila = 0;
        for (var i = 0; i < data.detalles.length; i++) {

            var fila= generarFilaModificar(data.detalles[i].detalle,aux_nro_fila,data.estados);

            $('#tablaModificar tbody').append(fila);

          aux_nro_fila++;
        }
  })
   $('#modalModificar').modal('show');

})

//guardar carga de apuestas
$('#btn-guardar-modif').on('click',function(e){
  e.preventDefault();

  var detalles=[];
  var fiscalizadores=[];

  var d= $('#fiscalizadoresPartModif tbody > tr');

  $.each(d, function(index, value){

    if($(this) != 'undefined'){

        fiscalizadores.push($(this).attr('id'));
    }
      })
  var f= $('#tablaModificar tbody > tr');

  //recorro tabla para enviar datos de relevamiento
  $.each(f, function(index, value){

    if($(this) != 'undefined'){
      var d={
        id_detalle: $(this).attr('id'),
        minimo: $(this).find('.min_mod').val(),
        maximo:$(this).find('.max_mod').val(),
        id_estado_mesa:$(this).find('.estado_mod').val(),
      }
        detalles.push(d);
    }
      })


      var formData= {
        hora:$('#hora_ejec_mod').val(),
        detalles:detalles,
        fiscalizadores:fiscalizadores,
        observaciones:$('#obsModificacion').val()
      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'apuestas/cargarRelevamiento',
          data: formData,
          dataType: 'json',

          success: function (data){

            $('#modalModificar').modal('hide');
            $('#mensajeExito h3').text('ÉXITO');
            $('#mensajeExito p').text('Relevamiento MODIFICADO. ');
            $('#mensajeExito').show();
            $('#btn-buscar-apuestas').trigger('click',[1,10,'fecha','desc']);
          },
          error: function (reject) {
                if( reject.status === 422 ) {
                    var errors = $.parseJSON(reject.responseText);
                    $.each(errors, function (key, val) {
                      if(key == 'detalles'){

                        $('#mensajeErrorModificar').show();
                      }
                      if(key == 'hora'){
                        mostrarErrorValidacion( $('#hora_ejec_mod'),val[0],false);
                      }
                      if(key != 'hora' && key != 'detalles'){
                          var splitt = key.split('.');
                        mostrarErrorValidacion( $('#tablaModificar #' + splitt[0]+splitt[1]+splitt[2] ),val[0],false);
                      }
                      if(typeof errors.id_fiscalizador != 'undefined'){
                        $('#mensajeErrorModificar').show();
                      }
                    });
                }
            }
      })

})


//ELIMINNAR UN RELEVAMIENTO, SÓLO SI NO FUE VALIDADO
$(document).on('click','.eliminarApuesta',function(e){

   var id=$(this).val();


 $.get('apuestas/baja/' + id, function(data){

   $('#tablaResultadosApuestas tbody').find('#' + id).remove();

       $('#mensajeExito h3').text('ÉXITO');
       $('#mensajeExito p').text(' ');
       $('#mensajeExito').show();
     })

});

//validación
$(document).on('click', '.validarApuesta', function(e){

  e.preventDefault();

  limpiarValidar();

  var id_relevamiento=$(this).val();

   $.get('apuestas/relevamientoCargado/' + id_relevamiento, function(data){

      $('#btn-validar').val(data.relevamiento_apuestas.id_relevamiento_apuestas);
       var id_casino=data.relevamiento_apuestas.id_casino;

       for (var i = 0; i < data.fiscalizadores.length; i++) {
        var fila= generarValidarFisca(data.fiscalizadores[i]);

        $('#fiscalizadoresPartVal tbody').append(fila);
       }

       $('#B_fecha_val').val(data.relevamiento_apuestas.fecha).prop('readonly',true);
       $('#turnoRelevadoVal').val(data.turno.id_turno).prop('readonly', true);
       $('#obsFiscalizador').val(data.relevamiento_apuestas.observaciones);
       $('#obsFiscalizador').prop('readonly',true);
       if (data.cumplio_minimo == 'true') {
         $('.cumpleMin').text('CUMPLIÓ MÍNIMO REQUERIDO: ' + 'SI' );
       }
       if (data.cumplio_minimo == 'false') {
         $('.cumpleMin').text('CUMPLIÓ MÍNIMO REQUERIDO: ' + 'NO' );
       }

       if(data.relevamiento_apuestas.hora_ejecucion != null){
         var p = data.relevamiento_apuestas.hora_ejecucion.split(':')
         var d = data.relevamiento_apuestas.hora_propuesta.split(':')

         var hs, mm, hh,ii;

         if(p.length === 3) {
           hs = p[0];
           mm = p[1];
         }
         if(d.length === 3) {
           hh = p[0];
           ii = p[1];
         }
       }else{
         hs = '-';
         mm = '-';
         hh = '-';
         ii = '-';
       }

       $('#hora_ejec_val').val(hs + ':' + mm);
       $('#hora_prop_val').val(hh + ':' + ii).prop('readonly',true);

        for (var i = 0; i < data.detalles.length; i++) {

            var fila= generarFilaValidar(data.detalles[i].detalle,data.estados);

            $('#tablaValidar tbody').append(fila);

        }
        for (var i = 0; i < data.abiertas_por_juego.length; i++) {

          var fila2= generarFilaValidar2(data.abiertas_por_juego[i]);
          console.log('fffff',fila2);

          $('#mesasPorJuego').append(fila2);

        }

  })

  $('#modalValidar').modal('show');
});


//validar dentro del modal
$('#btn-validar').on('click', function(e){
  e.preventDefault();

      var formData= {
        id_relevamiento:$(this).val(),
        observaciones:$('#obsValidacion').val()
      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'apuestas/validar',
          data: formData,
          dataType: 'json',

          success: function (data){

            $('#modalValidar').modal('hide');
            $('#mensajeExito h3').text('ÉXITO');
            $('#mensajeExito p').text('Relevamiento VALIDADO. ');
            $('#mensajeExito').show();
            $('#btn-buscar-apuestas').trigger('click',[1,10,'fecha','desc']);
          },
          error: function (reject) {

            }
      })

})

$('#btn-minimo').on('click',function(e){

  e.preventDefault();

  $('#juegoNuevo').setearElementoSeleccionado(" ",0);
  $('#juegoNuevoDol').setearElementoSeleccionado(" ",0);

  $('#cantidadNuevaDol').val('');
  $('#cantidadNueva').val('');
  $('#apuestaNuevaDol').val('');
  $('#apuestaNuevaDol').val('');


  $.get('apuestas/obtenerRequerimientos', function(data){

    $('#juegoMinimo').text('Juego: ' + data.minimo_pesos.juego).prop('disabled',true);
    $('#apuestaMinimo').text('Apuesta mínima: ' + data.minimo_pesos.apuesta).prop('disabled',true);
    $('#cantMinimo').text('Cantidad de Mesas abiertas: ' + data.minimo_pesos.cant_mesas).prop('disabled',true);

    $('#juegoNuevo').generarDataList("mesas-juegos/obtenerJuegoPorCasino/" + data.casino.id_casino,'juegos' ,'id_juego_mesa','nombre_juego',1);

    if(data.minimo_dolares != null){
      $('#juegoMinimoDol').text('Juego: ' + data.minimo_dolares.juego).prop('disabled',true);
      $('#apuestaMinimoDol').text('Apuesta mínima: ' + data.minimo_dolares.apuesta).prop('disabled',true);
      $('#cantMinimoDol').text('Cantidad de Mesas abiertas: ' + data.minimo_dolares.cant_mesas).prop('disabled',true);

      $('#juegoNuevoDol').generarDataList("mesas-juegos/obtenerJuegoPorCasino/" + data.casino.id_casino,'juegos' ,'id_juego_mesa','nombre_juego',1);

    }

  })

  $('#modalMinimo').modal('show');
})

$('#btn-guardar-minimo').on('click',function(e){

  e.preventDefault();

  var modificaciones=[];
  var minimo_pesos={
    id_moneda:1,
    id_juego:$('#juegoNuevo').obtenerElementoSeleccionado(),
    apuesta:$('#apuestaNueva').val(),
    cantidad:$('#cantidadNueva').val(),
  };
  var minimo_dolares={
    id_moneda: 2,
    id_juego:$('#juegoNuevoDol').obtenerElementoSeleccionado(),
    apuesta:$('#apuestaNuevaDol').val(),
    cantidad:$('#cantidadNuevaDol').val(),
  };

    modificaciones.push(minimo_pesos);
    modificaciones.push(minimo_dolares)


  var formData= {
    modificaciones:modificaciones
  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'apuestas/modificarRequerimiento',
      data: formData,
      dataType: 'json',

      success: function (data){

        $('#modalMinimo').modal('hide');
        $('#mensajeExito h3').text('ÉXITO');
        $('#mensajeExito p').text('Cambios GUARDADOS. ');
        $('#mensajeExito').show();
        $('#btn-buscar-apuestas').trigger('click',[1,10,'fecha','desc']);
      },
      error: function(data){
        console.log('error',data);
      }

    });

})

$(document).on('click','.imprimirApuesta',function(){

  var id=$(this).val();

  window.open('apuestas/imprimir/' + id,'_blank');

})

$(document).on('click','.btn_borrar_fisca',function(){

  var tipo= $(this).attr('data-tipo');
  var id=$(this).attr('id');


  if(tipo=='modificar'){
    $('#fiscalizadoresPartModif tbody').find('#' + id).remove();

  }
  if(tipo=='cargar'){
    $('#fiscalizadoresPart tbody').find('#' + id).remove();
  }
  if(tipo=='cargarBUp'){
    $('#fiscalizadoresPartBUp tbody').find('#' + id).remove();

  }
})

/*****************PAGINACION******************/
$(document).on('click','#tablaResultadosApuestas thead tr th[value]',function(e){

  $('#tablaResultadosApuestas th').removeClass('activa');

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
  $('#tablaResultadosApuestas th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){

  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultadosApuestas .activa').attr('value');
  var orden = $('#tablaResultadosApuestas .activa').attr('estado');
  $('#btn-buscar-apuestas').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFila(data){

  var fila = $(document.createElement('tr'));

  fila.attr('id',data.id_relevamiento_apuestas)
      .append($('<td>').addClass('.L_fecha').addClass('col-xs-2').text(data.fecha).css('text-align','center'))
      .append($('<td>').addClass('.L_turno').addClass('col-xs-2').text(data.nro_turno).css('text-align','center'))
      .append($('<td>').addClass('.L_casino').addClass('col-xs-3').text(data.nombre).css('text-align','center'))
      if(data.id_estado_relevamiento ==4){
        fila.append($('<td>').append($('<i>').addClass('col-xs-2').addClass('fa fa-fw fa-check').css('color', '#4CAF50').css('padding-top','10px').css('margin-left','30px')));
      }
      else{
        fila.append($('<td>').append($('<i>').addClass('col-xs-2').addClass('fas fa-fw fa-times').css('color', '#D32F2F').css('padding-top','10px').css('margin-left','30px')));
      }
      fila.append($('<td>').css('text-align','center').addClass('col-xs-3').append($('<button>').addClass('btn btn-successAceptar cargarApuesta').val(data.id_relevamiento_apuestas)
                          .append($('<i>').addClass('fas fa-fw fa-upload').append($('</i>')
                          .append($('</button>')))))
                          .append($('<button>').addClass('btn btn-info imprimirApuesta').val(data.id_relevamiento_apuestas)
                          .append($('<i>').addClass('fa fa-fw fa-print').append($('</i>')
                          .append($('</button>')))))
                          .append($('<button>').addClass('btn btn-warning modificarApuesta').val(data.id_relevamiento_apuestas)
                          .append($('<i>').addClass('fas fa-fw fa-pencil-alt').append($('</i>')
                          .append($('</button>')))))
                          .append($('<button>').addClass('btn btn-success validarApuesta').val(data.id_relevamiento_apuestas)
                          .append($('<i>').addClass('fa fa-fw fa-check').append($('</i>')
                          .append($('</button>')))))
                          .append($('<button>').addClass('btn btn-success eliminarApuesta').val(data.id_relevamiento_apuestas)
                                  .append($('<i>').addClass('fa fa-fw fa-trash').append($('</i>')
                                                                                        .append($('</button>'))
                                                                                        )
                                          )
                                        )
                    )

        if(data.id_estado_relevamiento == 4){
          fila.find('.cargarApuesta').hide();
          fila.find('.imprimirApuesta').show();
          fila.find('.eliminarApuesta').hide();
          fila.find('.modificarApuesta').hide();
          fila.find('.validarApuesta').hide();

        }
        else{
          if(data.id_estado_relevamiento == 1){
            fila.find('.cargarApuesta').show();
            fila.find('.eliminarApuesta').show();
            fila.find('.imprimirApuesta').hide();
            fila.find('.modificarApuesta').hide();
            fila.find('.validarApuesta').hide();
            }
            if(data.id_estado_relevamiento == 3){
              fila.find('.cargarApuesta').hide();
              fila.find('.eliminarApuesta').show();
              fila.find('.imprimirApuesta').hide();
              fila.find('.modificarApuesta').show();
              fila.find('.validarApuesta').show();
            }
          }


  return fila;
}

function generarFilaCarga(data,nro_row,e){

      var fila = $('#moldeCarga').clone();
      fila.removeAttr('id');
      fila.attr('id',data.id_detalle);

      fila.find('.nro_mesa').text(data.nro_mesa);
      fila.find('.pos_carga').text(data.posiciones);
      fila.find('.min_carga').attr('id','detalles'+nro_row+'minimo');
      fila.find('.max_carga').attr('id','detalles'+nro_row+'maximo');

      for (var i = 0; i < e.length; i++) {
        if(e[i].id_estado_mesa==2){
          fila.find('.estado_carga').append($('<option>').val(e[i].id_estado_mesa).text(e[i].siglas_mesa).prop('selected',true).append($('</option>')));
          fila.find('.estado_carga').attr('id','detalles'+nro_row+'id_estado_mesa');
        }
        else{
          fila.find('.estado_carga').append($('<option>').val(e[i].id_estado_mesa).text(e[i].siglas_mesa).append($('</option>')));
          fila.find('.estado_carga').attr('id','detalles'+nro_row+'id_estado_mesa');
        }
      }

      fila.css('display','');
      $('#ff').css('display','block');
      $('#dd').css('display','block');


  return fila;
};

function generarFilaValidar(data,e){

    var fila = $('#moldeValidar').clone();
    fila.removeAttr('id');

     var id=data.id_estado_mesa -1;

     fila.find('.juego_val').text(data.nombre_juego);
     fila.find('.nro_mesa_val').text(data.nro_mesa);
     fila.find('.pos_val').text(data.posiciones);
     for (var i = 0; i < e.length; i++) {
          fila.find('.estado_val').append($('<option>').val(e[i].id_estado_mesa).text(e[i].descripcion_mesa));
     }

     fila.find('.estado_val').val(e[id].id_estado_mesa).prop('selected',true).prop('disabled',true);
     fila.find('.min_val').val(data.minimo).css('text-align','center').prop('disabled',true);
     fila.find('.max_val').val(data.maximo).css('text-align','center').prop('disabled',true);

     fila.css('display','block');
     $('#dd').css('display','block');
     return fila;

}

function generarFilaCargaBUp(data,nro_row,e){


    var fila = $('#moldeBUp').clone();
    fila.removeAttr('id');
    fila.attr('id',data.id_detalle);

    fila.find('.nro_mesa_up').text(data.nro_mesa).css('font-size', '14px');
    fila.find('.pos_up').text(data.posiciones).css('font-size', '14px');
    fila.find('.min_up').attr('id','detalles'+nro_row+'minimo');
    fila.find('.max_up').attr('id','detalles'+nro_row+'maximo');

    for (var i = 0; i < e.length; i++) {

      if(e[i].id_estado_mesa == 2){
        fila.find('.estado_up').append($('<option>').val(e[i].id_estado_mesa).text(e[i].siglas_mesa).append($('</option>')).prop('selected',true));
        fila.find('.estado_up').attr('id','detalles'+nro_row+'id_estado_mesa');

      }
      else{

      fila.find('.estado_up').append($('<option>').val(e[i].id_estado_mesa).text(e[i].siglas_mesa).append($('</option>')));
      fila.find('.estado_up').attr('id','detalles'+nro_row+'id_estado_mesa');
    }
    }

  //  fila.find('.juego_up').text(data.nombre_juego).css('font-size', '14px');

    fila.css('display','block');
    $('#pp').css('display','block');

    return fila;
}

function generarFilaModificar(data,nro_row,e){

  var fila = $('#moldeModificacion').clone();
  fila.removeAttr('id');
  fila.attr('id',data.id_detalle_relevamiento_apuestas);

  fila.find('.nro_mesa_mod').text(data.nro_mesa).css('font-size', '14px');
  fila.find('.pos_mod').text(data.posiciones).css('font-size', '14px');
  fila.find('.min_mod').attr('id','detalles'+nro_row+'minimo');
  fila.find('.max_mod').attr('id','detalles'+nro_row+'maximo');

  for (var i = 0; i < e.length; i++) {

    fila.find('.estado_mod').append($('<option>').val(e[i].id_estado_mesa).text(e[i].siglas_mesa).append($('</option>')));
    fila.find('.estado_mod').attr('id','detalles'+nro_row+'id_estado_mesa');
  }
  fila.find('.juego_mod').text(data.nombre_juego).css('font-size', '14px');
  fila.find('.min_mod').val(data.minimo).css('font-size', '14px');
  fila.find('.max_mod').val(data.maximo);
  fila.find('.estado_mod').val(data.id_estado_mesa ).prop('selected',true);

  fila.css('display','block');
  $('#dd').css('display','block');

  return fila;
}

function generarFilaValidar2(data){
  var fila = $(document.createElement('tr'));

    fila.append($('<td>').addClass('col-xs-6').text(data.nombre_juego))
        .append($('<td>').addClass('col-xs-6').text(data.cantidad_abiertas));

  return fila;
}

function limpiarCarga(){
  $('#tablaCarga tbody tr').remove();
  $('#mensajeErrorCarga').hide();
  ocultarErrorValidacion($('#hora_ejec_carga'));
  ocultarErrorValidacion($('#fiscalizadorCarga'));
  $('#hora_ejec_carga').val('');
  $('#hora_prop_carga').val('');
  $('#B_fecha_carga').val('');
  $('#fiscalizadorCarga').setearElementoSeleccionado(0,'');
  $('#obsCarga').val('');
  $('#fiscalizadoresPart tbody tr').remove();


}

function limpiarModificar(){
  $('#tablaModificar tbody tr').remove();
  $('#mensajeErrorModificar').hide();
  ocultarErrorValidacion($('#hora_ejec_mod'));
  ocultarErrorValidacion($('#fiscalizadorMod'));
  $('#hora_prop_mod').val('');
  $('#hora_ejec_mod').val('');
  $('#obsModificacion').val('');
  $('#fiscalizadoresPartModif tbody tr').remove();


}

function limpiarValidar(){
  $('#tablaValidar tbody tr').remove();
  $('#hora_prop_val').val('');
  $('#hora_ejec_val').val('');
  $('#obsValidacion').val('');
  $('#obsFiscalizador').val('');
  $('#fiscalizadoresPartVal tbody tr').remove();

}

function limpiarCargaBUp(){

  $('#tablaCargaBUp tbody tr').remove();
  $('#mensajeErrorCargaBUp').hide();
  ocultarErrorValidacion($('#hora_ejec_BUp'));
  ocultarErrorValidacion($('#fiscalizadorBUp'));
  $('#hora_ejec_BUp').val('');
  $('#hora_prop_BUp').val('');
  $('#fiscalizadorBUp').setearElementoSeleccionado(0,'');
  $('#obsBUp').val('');
  $('#fiscalizadoresPartBUp tbody tr').remove();

}

//dentro del modal de cargar relevamiento, para agregar la mesa al listado
function clickAgregarFisca(e) {

  if($(this).attr('data-carga') == 'normal'){
    var id = $('#fiscalizadorCarga').obtenerElementoSeleccionado();

       $.get('http://' + window.location.host +"/usuarios/buscar/" + id, function(data) {

           var fila= $(document.createElement('tr'));
           fila.attr('id', data.usuario.id_usuario)
               .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
               .text(data.usuario.nombre)
             )
               .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
               .addClass('col-xs-2')
               .append($('<span>').text(' '))
               .append($('<button>')
               .addClass('btn_borrar_fisca').attr('id',data.usuario.id_usuario).attr('data-tipo','cargar')
               .append($('<i>')
               .addClass('fas').addClass('fa-fw').addClass('fa-trash')
                 )))

             $('#fiscalizadoresPart tbody').append(fila);
             $('#fiscalizadorCarga').setearElementoSeleccionado(0 , "");
      });
  }
  if($(this).attr('data-carga') == 'backup'){

    var id = $('#fiscalizadorBUp').obtenerElementoSeleccionado();

       $.get('http://' + window.location.host +"/usuarios/buscar/" + id, function(data) {

           var fila= $(document.createElement('tr'));
           fila.attr('id', data.usuario.id_usuario)
               .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
               .text(data.usuario.nombre)
             )
               .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
               .append($('<span>').text(' '))
               .append($('<button>')
               .addClass('btn_borrar_fisca').attr('id',data.usuario.id_usuario).attr('data-tipo','cargarBUp')
               .append($('<i>')
               .addClass('fas').addClass('fa-fw').addClass('fa-trash')
                 )))

             $('#fiscalizadoresPartBUp tbody').append(fila);
             $('#fiscalizadorBUp').setearElementoSeleccionado(0 , "");
      });
  }
}
function clickAgregarFiscaMod(e) {
  var id = $('#fiscalizadorMod').obtenerElementoSeleccionado();

     $.get('http://' + window.location.host +"/usuarios/buscar/" + id, function(data) {

       var fila= $(document.createElement('tr'));
       fila.attr('id', data.usuario.id_usuario)
           .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
           .text(data.usuario.nombre)
         )
           .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
           .addClass('col-xs-2')
           .append($('<span>').text(' '))
           .append($('<button>')
           .addClass('btn_borrar_fisca').attr('id',data.usuario.id_usuario).attr('data-tipo','modificar')
           .append($('<i>')
           .addClass('fas').addClass('fa-fw').addClass('fa-trash')
             )))

         $('#fiscalizadoresPartModif tbody').append(fila);
      $('#fiscalizadorMod').setearElementoSeleccionado(0 , "");


    });

}

//genera la fila dentro de la tabla participantes en el modificar
function generarTablaFisca(data){
    var fila= $(document.createElement('tr'));
    fila.attr('id', data.id_usuario)
        .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
        .text(data.nombre)
      )
        .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
        .addClass('col-xs-2')
        .append($('<span>').text(' '))
        .append($('<button>')
        .addClass('btn_borrar_fisca').attr('id',data.id_usuario).attr('data-tipo','modificar')
        .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-trash')
          )))

    return fila;

}

function generarValidarFisca(data){
    var fila= $(document.createElement('tr'));
    fila.attr('id', data.id_usuario)
        .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
        .text(data.nombre)
      )


    return fila;

}
