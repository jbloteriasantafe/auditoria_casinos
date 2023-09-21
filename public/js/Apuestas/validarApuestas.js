import {AUX} from "/js/Components/AUX.js";

$(function(){
  const  M = $('[data-js-validar]');
  const $M = M.find.bind(M);
  
  M.on('mostrar',function(e,id_relevamiento){
    $('#tablaValidar tbody tr').remove();
    $('#hora_prop_val').val('');
    $('#hora_ejec_val').val('');
    $('#obsValidacion').val('');
    $('#obsFiscalizador').val('');
    $('#fiscalizadoresPartVal tbody tr').remove();
    $('#mesasPorJuego tbody tr').remove();
    $('#cumplio_min tbody > tr').remove();
    
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
/*
          data.detalles.forEach(function(d){
            const fila = $('#moldeValidar').clone().removeAttr('id').show();  
            fila.find('.mesa_val').text(d.codigo_mesa);//.css('font-size', '14px');
            fila.find('.pos_val').text(d.posiciones);
            fila.find('.estado_val').append(data.estados.map(function(e){
              return $('<option>').val(e.id_estado_mesa).text(e.descripcion_mesa);
            }));
            fila.find('.estado_val').val(data.estados[d.id-1].id_estado_mesa);//.prop('selected',true).prop('disabled',true);
            fila.find('.min_val').val(data.minimo);//.css('text-align','center').prop('disabled',true);
            fila.find('.max_val').val(data.maximo);//.css('text-align','center').prop('disabled',true);
            fila.find('.moneda_val').text(data.descripcion);
            $('#tablaValidar tbody').append(fila);
          });
          data.abiertas_por_juego.forEach(function(a){
            const fila = $('<tr>');
            fila.append($('<td>').addClass('col-xs-5').text(a.nombre_juego));//.css('text-align','center'));
            fila.append($('<td>').addClass('col-xs-4').text(a.cantidad_abiertas));//.css('text-align','center'))
            $('#mesasPorJuego').append(fila);
          });*/

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
  });
});

