$(document).ready(function() {
  $('#barraApuestas').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Relevamientos de Valores Mínimos de Apuestas');
  $('#barraApuestas').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#barraApuestas').addClass('opcionesSeleccionado');

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
          $( "#dtpFecha" ).datetimepicker( "option", "dateFormat",'yyyy-mm-dd');

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

    if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
      var sort_by =  {columna: 'fecha',orden: 'desc'} ;

      //$('#tablaInicial th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
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
  $('#turnoRelevadoBUp').generarDataList("apuestas/buscarTurnos" ,'turnos','id_turno','nro_turno' ,1,true);
  $('#turnoRelevadoBUp').setearElementoSeleccionado(0,'');

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
          $('#mensajeErrorBuscarBUp').hide();
          $('#btn-guardar-backUp').show();

        var id_relevamiento=data.relevamiento.id_relevamiento_apuestas;
        var id_casino=data.relevamiento.id_casino;

        $('#fiscalizadorBUp').generarDataList("apuestas/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
        $('#hora_prop_BUp').val(data.relevamiento.hora_propuesta).prop('readonly',true);

        var aux_nro_fila = 0;

         for (var i = 0; i < data.mesasporjuego.length; i++) {
           for (var j = 0; j < data.mesasporjuego[i].mesas.length; j++){

             var fila= generarFilaCargaBUp(data.mesasporjuego[i].mesas[j],aux_nro_fila,data.estados);


             $('#tablaCargaBUp tbody').append(fila);
             aux_nro_fila++;
           }
         }

        },

        error: function (data) {

            var response= data.responseJSON.errors;

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

  var fiscalizadores=[];
  var fis= $('#fiscalizadoresPartBUp tbody > tr');

  $.each(fis, function(index, value){
    if($(this) != 'undefined'){
        fiscalizadores.push($(this).attr('id'));
    }
  })
  //recorro tabla para enviar datos de relevamiento
  $.each(f, function(index, value){

    if($(this) != 'undefined'){
      var min =$(this).find('.min_up').val();
      var max = $(this).find('.max_up').val();
      if($(this).find('.moneda_up').find('#monedacargaBUp').length > 0){
        var mon=$(this).find('input.monedaApuestaBUp:checked').val();
      }
      else{
        var mon= $(this).attr('data-moneda');
      }
      var d={
        id_detalle: $(this).attr('id'),
        minimo: min,
        maximo:max,
        id_estado_mesa:$(this).find('.estado_up').val(),
        id_moneda: mon
      }

        detalles.push(d);
    }
      })

      var formData= {
        hora:$('#hora_ejec_BUp').val(),
        detalles:detalles,
        fiscalizadores:fiscalizadores,
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
            ocultarErrorValidacion($('#hora_ejec_BUp'));
            ocultarErrorValidacion($('#fiscalizadorBUp'));
            //data ==0 => se ha guardado correctamente pero no hay mesas abiertas
            //data ==1 => se ha guardado correctamente

            if(data==0){
              deshabilitarBup();

              $('#btn-guardar-backUp').hide();
              $('#btn-salir-bup').show();
              $('#alertaMesasCerradasBUP').show();
              $('#modalCargaBackUp').animate({scrollTop:$('#alertaMesasCerradasBUP').offset().top},"slow");

            }
            else{
              $('#modalCargaBackUp').modal('hide');
              $('#mensajeExito h3').text('ÉXITO');
              $('#mensajeExito p').text('Relevamiento GUARDADO. ');
              $('#mensajeExito').show();
              $('#btn-buscar-apuestas').trigger('click',[1,10,'fecha','desc']);
            }

          },
          error: function (reject) {
            console.log(reject);
            if(reject.status != 422) return;
            const errors = reject.responseJSON.errors;
            
            if(typeof errors.hora !== 'undefined'){
              mostrarErrorValidacion( $('#hora_ejec_BUp'),errors.hora.join(', '),false);
              $('#modalCargaBackUp').animate({scrollTop:$('#hora_ejec_BUp').offset().top},"slow");
              $('#mensajeErrorCargaBUp').show();
            }
            if(typeof errors.fiscalizadores !== 'undefined'){
              mostrarErrorValidacion( $('#fiscalizadorBUp'),errors.fiscalizadores.join(', '),false);
              $('#modalCargaBackUp').animate({scrollTop:$('#fiscalizadorBUp').offset().top},"slow");
              $('#mensajeErrorCargaBUp').show();
            }
            
            let last_error = null;
            $('#tablaCargaBUp .filaClone').each(function(idx,fila){
              const monn = `detalles.${idx}.id_moneda`;
              const minn = `detalles.${idx}.minimo`;
              const maxx = `detalles.${idx}.maximo`;
              if(typeof errors[monn] !== 'undefined'){
                mostrarErrorValidacion( $(fila).find('.estado_up'),errors[monn].join(', '),false);
                last_error = fila;
              }
              if(typeof errors[minn] !== 'undefined'){
                mostrarErrorValidacion( $(fila).find('.min_up'),errors[minn].join(', '),false);
                last_error = fila;
              }
              if(typeof errors[maxx] !== 'undefined'){
                mostrarErrorValidacion( $(fila).find('.max_up'),errors[maxx].join(', '),false);
                last_error = fila;
              }
            });
            if(last_error !== null && $('#mensajeErrorCargaBUp').is(':hidden')){
              $('#mensajeErrorCargaBUp').show();
              $('#modalCargaBackUp').animate({scrollTop:$(last_error).offset().top},"slow");
            }
          }
      })

});

function deshabilitarBup(){
  $('#hora_ejec_BUp').prop('disabled',true);
  $('#fiscalizadorBUp').prop('disabled',true);
  $('#obsBUp').prop('disabled',true);
  $('#modalCargaBackUp #btn_borrar_fisca').prop('disabled',true);

  var d= $('#tablaCargaBUp tbody > tr');

  $.each(d, function(index, value){
    $(this).find('.estado_up').prop('disabled',true);
    $(this).find('.min_up').prop('disabled',true);
    $(this).find('.max_up').prop('disabled',true);
  });
}

//btn generar planillas
$('#btn-generar').on('click', function(e){

  e.preventDefault();

  $.get('apuestas/consultarMinimo',function(data){

    if(data.apuestas.length == 0){
      $('#modalPreGenerar').modal('show');
    }
    else{

      $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
      var formData = {
      }

      $.ajax({
          type: "POST",
          url: 'apuestas/generarRelevamientoApuestas',
          data: formData,
          dataType: 'json',

           beforeSend: function(data){
             $('#modalRelevamiento').find('.modal-body').html('<div class="loading"><img src="/img/ajax-loader(1).gif" alt="loading" /><br>Un momento, por favor...</div>').css('text-align','center');
             $('#modalRelevamiento').modal('show');

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
              iframe.src = 'apuestas/descargarZipApuestas/'+data.nombre_zip;

          },
          error: function (data) {
            $('#modalRelevamiento').modal('hide');

            $('#modalErrorRelevamientoA').modal('show');

          }
      });

    }
  })
})

$(document).on('click', '.cargarApuesta', function(e){

  e.preventDefault();

    var id_relevamiento=$(this).val();
    limpiarCarga();
    habilitar();


  $.get('apuestas/obtenerDatos/' + id_relevamiento, function(data){
      var id_casino=data.relevamiento.id_casino;

      $('#fiscalizadorCarga').generarDataList("apuestas/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#B_fecha_carga').val(data.fecha).prop('readonly',true);

      if(data.relevamiento.hora_propuesta != null){
         var d = data.relevamiento.hora_propuesta.split(':')
         var hh,mm;
          if(d.length === 3) {
           hs = d[0];  mm = d[1];
          }
        }
        else{hs = '-'; mm = '-';
             }
      $('#hora_prop_carga').val(hs + ':' + mm).prop('readonly',true);

      $('#turnoRelevado').val(data.turno.length > 0? data.turno[0].nro_turno : '').prop('readonly', true);

      var aux_nro_fila = 0;

       for (var i = 0; i < data.mesasporjuego.length; i++) {
         for (var j = 0; j < data.mesasporjuego[i].mesas.length; j++) {

           var fila= generarFilaCarga(data.mesasporjuego[i].mesas[j],aux_nro_fila,data.estados);

           $('#tablaCarga tbody').append(fila);
           aux_nro_fila++;
         }
         $('#tablaCarga').css('display','');
       }
 })
 $('#btn-salir').hide();
 $('#alertaMesasCerradas').hide();

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
      if($(this).find('.moneda_carga').find('#monedacarga').length > 0){
        var mon=$(this).find('input.monedaApuesta:checked').val();
      }
      else{
        var mon= $(this).attr('data-moneda');
      }
      var d={
        id_detalle: $(this).attr('id'),
        minimo: $(this).find('.min_carga').val(),
        maximo: $(this).find('.max_carga').val(),
        id_estado_mesa:$(this).find('.estado_carga').val(),
        id_moneda: mon
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
            ocultarErrorValidacion($('#hora_ejec_carga'));
            ocultarErrorValidacion($('#fiscalizadorCarga'));
            $('#mensajeErrorCargaApMesas').hide();
            $('#mensajeErrorCarga').hide();


            //data ==0 => se ha guardado correctamente pero no hay mesas abiertas
            //data ==1 => se ha guardado correctamente

            if(data==0){
              $('#btn-guardar').hide();
              $('#btn_borrar_fisca').prop('disabled',true);
              $('#btn-salir').show();
              $('#alertaMesasCerradas').show();
              $('#modalAlta').animate({scrollTop:$('#alertaMesasCerradas').offset().top},"slow");
              var d= $('#tablaCarga tbody > tr');

              $.each(d, function(index, value){
                $(this).find('.estado_carga').prop('disabled',true);
                $(this).find('.min_carga').prop('disabled',true);
                $(this).find('.max_carga').prop('disabled',true);
              });

              deshabilitar();
            }
            else{
              $('#modalCarga').modal('hide');
              $('#mensajeExito h3').text('ÉXITO');
              $('#mensajeExito p').text('Relevamiento GUARDADO. ');
              $('#mensajeExito').show();
              $('#btn-buscar-apuestas').trigger('click',[1,10,'fecha','desc']);
            }

          },
          error: function (reject) {
            console.log(reject);
            if(reject.status != 422) return;
            const errors = reject.responseJSON.errors;
            
            if(typeof errors.hora !== 'undefined'){
              mostrarErrorValidacion( $('#hora_ejec_carga'),errors.hora.join(', '),false);
              $('#modalCarga').animate({scrollTop:$('#hora_ejec_carga').offset().top},"slow");
              $('#mensajeErrorCarga').show();
            }
            if(typeof errors.fiscalizadores !== 'undefined'){
              mostrarErrorValidacion( $('#fiscalizadorCarga'),errors.fiscalizadores.join(', '),false);
              $('#modalCarga').animate({scrollTop:$('#fiscalizadorCarga').offset().top},"slow");
              $('#mensajeErrorCarga').show();
            }
            
            let last_error = null;
            $('#tablaCarga .filaClone').each(function(idx,fila){
              const monn = `detalles.${idx}.id_moneda`;
              const minn = `detalles.${idx}.minimo`;
              const maxx = `detalles.${idx}.maximo`;
              if(typeof errors[monn] !== 'undefined'){
                mostrarErrorValidacion( $(fila).find('.estado_carga'),errors[monn].join(', '),false);
                last_error = fila;
              }
              if(typeof errors[minn] !== 'undefined'){
                mostrarErrorValidacion( $(fila).find('.min_carga'),errors[minn].join(', '),false);
                last_error = fila;
              }
              if(typeof errors[maxx] !== 'undefined'){
                mostrarErrorValidacion( $(fila).find('.max_carga'),errors[maxx].join(', '),false);
                last_error = fila;
              }
            });
            if(last_error !== null && $('#mensajeErrorCarga').is(':hidden')){
              $('#mensajeErrorCarga').show();
              $('#modalCarga').animate({scrollTop:$(last_error).offset().top},"slow");
            }
          }
      });
})

$('#btn-salir').on('click',function(){
  $('#modalCarga').modal('hide');
  $('#mensajeExito h3').text('ÉXITO');
  $('#mensajeExito p').text('Relevamiento GUARDADO. ');
  $('#mensajeExito').show();
  $('#btn-buscar-apuestas').trigger('click',[1,10,'fecha','desc']);
});

//modificar relevamiento cargado
$(document).on('click', '.modificarApuesta', function(e){

  e.preventDefault();

  limpiarModificar();

  var id_relevamiento=$(this).val();

   $.get('apuestas/relevamientoCargado/' + id_relevamiento, function(data){

       var id_casino=data.relevamiento_apuestas.id_casino;

       $('#fiscalizadorMod').generarDataList("apuestas/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);

       for (var i = 0; i < data.fiscalizadores.length; i++) {
         var fila= generarTablaFisca(data.fiscalizadores[i]);
         $('#fiscalizadoresPartModif tbody').append(fila);
       }

       $('#B_fecha_modificar').val(data.relevamiento_apuestas.fecha).prop('readonly',true);
       $('#turnoRelevadoMod').val(data.turno.length > 0? data.turno[0].nro_turno : '').prop('readonly', true);
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
      if($(this).find('.moneda_mod').find('#monedamodificar').length > 0){
        var mon=$(this).find('input.monedaApuestaMod:checked').val();
      }
      else{
        var mon= $(this).attr('data-moneda');
      }
      var d={
        id_detalle: $(this).attr('id'),
        minimo: $(this).find('.min_mod').val(),
        maximo:$(this).find('.max_mod').val(),
        id_estado_mesa:$(this).find('.estado_mod').val(),
        id_moneda: mon
      }
        detalles.push(d);}
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
            console.log(reject);
            if(reject.status != 422) return;
            const errors = reject.responseJSON.errors;
            
            if(typeof errors.hora !== 'undefined'){
              mostrarErrorValidacion( $('#hora_ejec_mod'),errors.hora.join(', '),false);
              $('#modalModificar').animate({scrollTop:$('#hora_ejec_mod').offset().top},"slow");
              $('#mensajeErrorModificar').show();
            }
            if(typeof errors.fiscalizadores !== 'undefined'){
              mostrarErrorValidacion( $('#fiscalizadorMod'),errors.fiscalizadores.join(', '),false);
              $('#modalModificar').animate({scrollTop:$('#fiscalizadorMod').offset().top},"slow");
              $('#mensajeErrorModificar').show();
            }
            
            let last_error = null;
            $('#tablaModificar .filaClone').each(function(idx,fila){
              const monn = `detalles.${idx}.id_moneda`;
              const minn = `detalles.${idx}.minimo`;
              const maxx = `detalles.${idx}.maximo`;
              if(typeof errors[monn] !== 'undefined'){
                mostrarErrorValidacion( $(fila).find('.estado_mod'),errors[monn].join(', '),false);
                last_error = fila;
              }
              if(typeof errors[minn] !== 'undefined'){
                mostrarErrorValidacion( $(fila).find('.min_mod'),errors[minn].join(', '),false);
                last_error = fila;
              }
              if(typeof errors[maxx] !== 'undefined'){
                mostrarErrorValidacion( $(fila).find('.max_mod'),errors[maxx].join(', '),false);
                last_error = fila;
              }
            });
            if(last_error !== null && $('#mensajeErrorModificar').is(':hidden')){
              $('#mensajeErrorModificar').show();
              $('#modalModificar').animate({scrollTop:$(last_error).offset().top},"slow");
            }
          }
      })

})

//ELIMINNAR UN RELEVAMIENTO, SÓLO SI NO FUE VALIDADO
$(document).on('click','.eliminarApuesta',function(e){

   var id=$(this).val();
  $('#btn-eliminar-apuesta').val(id);

  $('#modalAlertaEliminar').modal('show');

});

$('#btn-eliminar-apuesta').on('click', function(e){
  e.preventDefault();

  var id=$(this).val();
  $('#modalAlertaEliminar').modal('hide');

  $.get('apuestas/baja/' + id, function(data){
    $('#tablaResultadosApuestas tbody').find('#' + id).remove();
        $('#mensajeExito h3').text('ÉXITO');
        $('#mensajeExito p').text(' ');
        $('#mensajeExito').show();
      })
})

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

       $('#B_fecha_val').text(data.relevamiento_apuestas.fecha).prop('readonly',true);
       $('#turnoRelevadoVal').text(data.turno.length > 0? data.turno[0].id_turno : '').prop('readonly', true);
       $('#obsFiscalizador').val(data.relevamiento_apuestas.observaciones);
       $('#obsFiscalizador').prop('disabled',true);

       if(data.relevamiento_apuestas.hora_ejecucion != null){
         var p = data.relevamiento_apuestas.hora_ejecucion.split(':')
         var d = data.relevamiento_apuestas.hora_propuesta.split(':')
         var hs, mm, hh,ii;

         if(p.length === 3) {
           hs = p[0];
           mm = p[1];
         }
         if(d.length === 3) {
           hh = d[0];
           ii = d[1];
         }
       }else{
         hs = '-';
         mm = '-';
         hh = '-';
         ii = '-';
       }

       $('#hora_ejec_val').text(hs + ':' + mm);
       $('#hora_prop_val').text(hh + ':' + ii).prop('readonly',true);

        for (var i = 0; i < data.detalles.length; i++) {
            var fila= generarFilaValidar(data.detalles[i].detalle,data.estados);
            $('#tablaValidar tbody').append(fila);
        }
        for (var i = 0; i < data.abiertas_por_juego.length; i++) {
          var fila2= generarFilaValidar2(data.abiertas_por_juego[i]);
          $('#mesasPorJuego').append(fila2);
        }

        if(data.cumplio_minimo != 0){
          $('#cumplio_min').append($('<tr>').append($('<td>').append($('<i>').addClass('col-xs-3').addClass('fa fa-fw fa-check').css('color', '#4CAF50').css('padding-top','10px').css('margin-left','30px'))));
        }
        if(data.cumplio_minimo == 0){
          $('#cumplio_min').append($('<tr>').append($('<td>').append($('<i>').addClass('col-xs-3').addClass('fas fa-fw fa-times').css('color', '#D32F2F').css('padding-top','10px').css('margin-left','30px'))));
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

      $('#modalPreGenerar').modal('hide');

      limpiarModificarMin();

      $.get('apuestas/obtenerRequerimientos/0/1', function(data){
            cargarDatosMin(data);
            var id_juego = $('#selectJuegoNuevo').val();

            for (var i=0; i<data.apuestas.length; i++) {
              if (id_juego == data.apuestas[i].id_juego_mesa) {
                $('#apuestaNueva').val(data.apuestas[i].apuesta_minima);
                $('#cantidadNueva').val(data.apuestas[i].cantidad_requerida);
              }
            }
      })
      $('#modalMinimo').modal('show');


})

$(document).on('change','#selectCasinoMin', function(data){
    limpiarModificarMin();
    var id_casino=$(this).val();
    var id_moneda = $('#selectMonedaMin').val();

    $.get('apuestas/obtenerRequerimientos/' + id_casino + '/' + id_moneda, function(data){
      cargarDatosMin(data);

      $.get('apuestas/obtenerRequerimientos/' + id_casino +'/'+id_moneda, function(data){
          var id_juego = $('#selectJuegoNuevo').val();
          for (var i=0; i<data.apuestas.length; i++) {
            if (id_juego == data.apuestas[i].id_juego_mesa) {
              $('#apuestaNueva').val(data.apuestas[i].apuesta_minima);
              $('#cantidadNueva').val(data.apuestas[i].cantidad_requerida);
            }
          }
      })

      if(data.dolares==null){
        $('#btn-guardar-minimo').attr('data-dolares','false');
      }
      else{
        $('#btn-guardar-minimo').attr('data-dolares','true');
      }

  })
})

$(document).on('change','#selectMonedaMin', function(data){
    limpiarModificarMin();
    var id=$(this).val();
    var id_casino = $('#selectCasinoMin').val();

    $.get('apuestas/obtenerRequerimientos/' + id_casino +'/'+id, function(data){
        cargarDatosMin(data);
        if(data.dolares==null){
          $('#btn-guardar-minimo').attr('data-dolares','false');
        }
        else{
          $('#btn-guardar-minimo').attr('data-dolares','true');
        }
    })
})

$(document).on('change','#selectJuegoNuevo', function(data){

  var id_casino = $('#selectCasinoMin').val();
  var id_moneda = $('#selectMonedaMin').val();
  var id_juego = $(this).val();

  $.get('apuestas/obtenerRequerimientos/' + id_casino +'/'+id_moneda, function(data){
      let encontrado = false;
      for (var i=0; i<data.apuestas.length; i++) {
        if (id_juego == data.apuestas[i].id_juego_mesa) {
          $('#apuestaNueva').val(data.apuestas[i].apuesta_minima);
          $('#cantidadNueva').val(data.apuestas[i].cantidad_requerida);
          encontrado = true;

        }
      }

      if (!encontrado) {
        $('#apuestaNueva').val('');
        $('#cantidadNueva').val('');
      }
  })

})

$('#btn-guardar-minimo').on('click',function(e){

  e.preventDefault();

  var modificaciones={
    id_casino: $('#selectCasinoMin').val(),
    id_moneda: $('#selectMonedaMin').val(),
    id_juego: $('#selectJuegoNuevo').val(),
    apuesta_minima: $('#apuestaNueva').val(),
    cantidad_requerida: $('#cantidadNueva').val(),
  };

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
        var errors = data.responseJSON.errors;

        $.each(errors, function (key, val) {
          if( key == 'modificaciones.id_juego' ){
              mostrarErrorValidacion($('#selectJuegoNuevo'),val[0],true);
            }
          if( key == 'modificaciones.apuesta_minima' ){
              mostrarErrorValidacion($('#apuestaNueva'),val[0],true);
            }
          if( key =='modificaciones.cantidad_requerida' ){
              mostrarErrorValidacion($('#cantidadNueva'),val[0],true);
            }
        });
      }

    });

});

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
})

function cargarDatosMin(data){

  if(data.errores == 'null'){
    $('#erroresRequerimientos').hide();

    for (var i = 0; i < data.juegos.length; i++) {
      $('#selectJuegoNuevo').append($('<option>').val(data.juegos[i].id_juego_mesa).text(data.juegos[i].nombre_juego).append($('</option>')));
    }
    $('#valoresApMinima').show();

  }else{
      $('#valoresApMinima').hide();
      $('#erroresRequerimientos').show();
  }
}

function deshabilitar(){
  var d= $('#tablaCarga tbody > tr');

  $.each(d, function(index, value){
    $(this).find('.estado_carga').prop('disabled',true);
    $(this).find('.min_carga').prop('disabled',true);
    $(this).find('.max_carga').prop('disabled',true);
  });


  $('#hora_ejec_carga').prop('disabled',true);
  $('#fiscalizadorCarga').prop('disabled',true);
  $('#obsCarga').prop('disabled',true);
}

function habilitar(){
  var f= $('#tablaCarga tbody > tr');

  //recorro tabla para deshabilitar datos de relevamiento
  $.each(f, function(index, value){
    $(this).prop('disabled',false);
  });

  $('#hora_ejec_carga').prop('disabled',false);
  $('#fiscalizadorCarga').prop('disabled',false);
  $('#obsCarga').prop('disabled',false);
}

function generarFila(data){

  var fila=$('#moldeApuesta').clone();

  fila.removeAttr('id');
  fila.attr('id',data.id_relevamiento_apuestas)
  fila.find('.L_fecha').text(data.fecha).css('text-align','center');
  fila.find('.L_turno').text(data.nro_turno).css('text-align','center');
  fila.find('.L_casino').text(data.nombre).css('text-align','center');

    if(data.id_estado_relevamiento ==4){
      fila.find('.L_estado').append($('<i>').addClass('fas fa-check-circle').css('color', '#4CAF50'));
    }
    else{
      fila.find('.L_estado').append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F'));
    }
    fila.find('.L_estado').css('cssText', 'text-align:center !important');

  fila.find('.cargarApuesta').val(data.id_relevamiento_apuestas);
  fila.find('.imprimirApuesta').val(data.id_relevamiento_apuestas);
  fila.find('.eliminarApuesta').val(data.id_relevamiento_apuestas);
  fila.find('.modificarApuesta').val(data.id_relevamiento_apuestas);
  fila.find('.validarApuesta').val(data.id_relevamiento_apuestas);

  switch (data.id_estado_relevamiento) {
    case 4:
      fila.find('.cargarApuesta').hide();
      fila.find('.imprimirApuesta').show();
      fila.find('.eliminarApuesta').hide();
      fila.find('.modificarApuesta').hide();
      fila.find('.validarApuesta').hide();
      break;
    case 1:
      fila.find('.cargarApuesta').show();
      fila.find('.eliminarApuesta').show();
      fila.find('.imprimirApuesta').hide();
      fila.find('.modificarApuesta').hide();
      fila.find('.validarApuesta').hide();
      break;
    case 3:
      fila.find('.cargarApuesta').hide();
      fila.find('.eliminarApuesta').show();
      fila.find('.imprimirApuesta').hide();
      fila.find('.modificarApuesta').show();
      fila.find('.validarApuesta').show();
      break;
    default:
      fila.find('.cargarApuesta').show();
      fila.find('.eliminarApuesta').show();
      fila.find('.imprimirApuesta').hide();
      fila.find('.modificarApuesta').hide();
      fila.find('.validarApuesta').hide();
  }

  fila.css('display','');
  $('#verFilaAp').css('display','block');

  return fila;
}

function generarFilaCarga(data,nro_row,e){

      var fila = $('#moldeCarga').clone();
      fila.removeAttr('id');
      fila.attr('id',data.id_detalle);

      fila.find('.mesa_carga').text(data.codigo_mesa);
      fila.find('.pos_carga').text(data.posiciones);
      fila.find('.min_carga').attr('id','detalles'+nro_row+'minimo');
      fila.find('.max_carga').attr('id','detalles'+nro_row+'maximo');

      if(data.multimoneda == 1){
        fila.find('.moneda_carga').find('#monedacarga input').prop('disabled',false).attr('name','monedaApuesta'+data.id_detalle);
      }else{
        fila.attr('data-moneda',data.id_moneda);
          fila.find('#monedacarga').remove();
          fila.find('.moneda_carga').text(data.descripcion)
        }


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

  // var fila = $(document.createElement('tr'));
  var fila = $('#moldeValidar').clone();
  fila.removeAttr('id');

   var id=data.id_estado_mesa -1;

   fila.find('.mesa_val').text(data.codigo_mesa).css('font-size', '14px');
   fila.find('.pos_val').text(data.posiciones);
   for (var i = 0; i < e.length; i++) {
        fila.find('.estado_val').append($('<option>').val(e[i].id_estado_mesa).text(e[i].descripcion_mesa));
   }

   fila.find('.estado_val').val(e[id].id_estado_mesa).prop('selected',true).prop('disabled',true);
   fila.find('.min_val').val(data.minimo).css('text-align','center').prop('disabled',true);
   fila.find('.max_val').val(data.maximo).css('text-align','center').prop('disabled',true);
  fila.find('.moneda_val').text(data.descripcion);

      fila.css('display','block');
      $('#dd').css('display','block');
      return fila;
}

function generarFilaCargaBUp(data,nro_row,e){


    var fila = $('#moldeBUp').clone();
    fila.removeAttr('id');
    fila.attr('id',data.id_detalle);

    fila.find('.mesa_up').text(data.codigo_mesa).css('font-size', '14px');
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
    if(data.multimoneda == 1){
      fila.find('.moneda_up').find('#monedacargaBUp input').prop('disabled',false).attr('name','monedaApuestaBUp'+data.id_detalle);
    }else{
      fila.attr('data-moneda',data.id_moneda);
        fila.find('#monedacargaBUp').remove();
        fila.find('.moneda_up').text(data.descripcion)
      }

    fila.css('display','block');
    $('#pp').css('display','block');

    return fila;
}

function generarFilaModificar(data,nro_row,e){

  var fila = $('#moldeModificacion').clone();
  fila.removeAttr('id');
  fila.attr('id',data.id_detalle_relevamiento_apuestas);

  fila.find('.mesa_mod').text(data.codigo_mesa ).css('font-size', '14px');
  fila.find('.pos_mod').text(data.posiciones).css('font-size', '14px');
  fila.find('.min_mod').attr('id','detalles'+nro_row+'minimo');
  fila.find('.max_mod').attr('id','detalles'+nro_row+'maximo');

  if(data.multimoneda == 1){
    fila.find('.moneda_mod').find('#monedamodificar input').prop('disabled',false).attr('name','monedaApuestaMod'+data.id_detalle_relevamiento_apuestas);    
    fila.find('.moneda_mod').find(`input[value="${data.id_moneda}"]`).prop('checked',true);
  }else{
    fila.attr('data-moneda',data.id_moneda);
      fila.find('#monedamodificar').remove();
      fila.find('.moneda_mod').text(data.descripcion)
    }

  for (var i = 0; i < e.length; i++) {

    fila.find('.estado_mod').append($('<option>').val(e[i].id_estado_mesa).text(e[i].siglas_mesa).append($('</option>')));
    fila.find('.estado_mod').attr('id','detalles'+nro_row+'id_estado_mesa');
  }
  fila.find('.min_mod').val(data.minimo).css('font-size', '14px');
  fila.find('.max_mod').val(data.maximo);
  fila.find('.estado_mod').val(data.id_estado_mesa ).prop('selected',true);

  fila.css('display','block');
  $('#dd').css('display','block');

  return fila;
}

function generarFilaValidar2(data){
  var fila = $(document.createElement('tr'));

    fila.append($('<td>').addClass('col-xs-5').text(data.nombre_juego).css('text-align','center'))
        .append($('<td>').addClass('col-xs-4').text(data.cantidad_abiertas).css('text-align','center'))

  return fila;
}

function limpiarCarga(){
  $('#tablaCarga tbody tr').remove();
  $('#mensajeErrorCarga').hide();
  $('#mensajeErrorCargaApMesas').hide();
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
  $('#mesasPorJuego tbody tr').remove();
  $('#cumplio_min tbody > tr').remove();

}

function limpiarCargaBUp(){
  $('#tablaCargaBUp tbody tr').remove();
  $('#mensajeErrorCargaBUp').hide();
  ocultarErrorValidacion($('#hora_ejec_BUp'));
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

       $.get("apuestas/buscarUsuario/" + id, function(data) {

           var fila= $(document.createElement('tr'));
           fila.attr('id', data.usuario.id_usuario)
               .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
               .text(data.usuario.nombre)
             )
               .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
               .addClass('col-xs-2')
               .append($('<span>').text(' '))
               .append($('<button>')
               .addClass('btn_borrar_fisca').attr('id',data.usuario.id_usuario).attr('data-tipo','cargar').prop('disabled',false)
               .append($('<i>')
               .addClass('fas').addClass('fa-fw').addClass('fa-trash')
                 )))

             $('#fiscalizadoresPart tbody').append(fila);
             $('#fiscalizadorCarga').setearElementoSeleccionado(0 , "");
      });
  }
  if($(this).attr('data-carga') == 'backup'){

    var id = $('#fiscalizadorBUp').obtenerElementoSeleccionado();

       $.get("apuestas/buscarUsuario/" + id, function(data) {

           var fila= $(document.createElement('tr'));
           fila.attr('id', data.usuario.id_usuario)
               .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
               .text(data.usuario.nombre)
             )
               .append($('<td>').css('margin-top','0px').css('margin-bottom','0px')
               .append($('<span>').text(' '))
               .append($('<button>')
               .addClass('btn_borrar_fisca').attr('id',data.usuario.id_usuario).attr('data-tipo','cargar')
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

     $.get("apuestas/buscarUsuario/" + id, function(data) {

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

function limpiarModificarMin(){
  $('#selectJuegoNuevo option').remove();
  $('#cantidadNueva').val('');
  $('#apuestaNueva').val('');
  ocultarErrorValidacion($('#juegoNuevo'));
  ocultarErrorValidacion($('#apuestaNueva'));
  ocultarErrorValidacion($('#cantidadNueva'));
}

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
