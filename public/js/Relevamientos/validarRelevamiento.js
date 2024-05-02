function cargarTablaRelevamientos(data, tabla, estadoRelevamiento){
  //se cargan las observaciones del fiscalizador si es que hay
  $('#observacion_fisca_validacion').val(data.relevamiento.observacion_carga);
  
  const td = function(arg){return $('<td>').append(arg);}

  data.detalles.forEach(function(d){
    //  Unidad de medida: 1-Crédito, 2-Pesos      |    Denominación: para créditos
    const fila = $('.moldeCarga').clone().removeClass('moldeCarga')
    .attr('data-medida', d.unidad_medida.id_unidad_medida)
    .attr('data-denominacion', d.denominacion)
    .attr('data-id-maquina',d.detalle.id_maquina)
    .attr('data-id-detalle-relevamiento',d.detalle.id_detalle_relevamiento);
    
    fila.find('.maquina').text(d.maquina);
    
    for(let c=1;c<=CONTADORES;c++){
      const cont = fila.find('.cont'+c).val(d.detalle['cont'+c]);
      
      if(estadoRelevamiento == 'Carga' && d.formula != null){
        cont.prop('readonly',d.formula['cont'+c] == null);
      }
      else if (estadoRelevamiento == 'Validar'){
        cont.prop('readonly',true);
      }
      
      fila.find('.formulaCont'+c).val(d?.formula?.['cont'+c] ?? null);
      fila.find('.formulaOper'+c).val(d?.formula?.['operador'+c] ?? null);
    }
    
    fila.find('.producidoCalculado').val(d.detalle.producido_calculado_relevado ?? '');
    fila.find('.producido').val(d.producido ?? '');
    fila.find('.diferencia').val('');
    fila.find('.tipo_causa_no_toma').val(d.tipo_causa_no_toma ?? '');
    
    fila.find(`.medida[data-medida!=${d.unidad_medida.id_unidad_medida}]`).remove();
    
    if(estadoRelevamiento == 'Validar'){
      const tipo_causa_no_toma = d.detalle.tipo_causa_no_toma;
      const diff = d.detalle.diferencia;
      const mostrar_botones = tipo_causa_no_toma != null || diff == null || (diff != 0 && ( diff %1000000 != 0));
      fila.find('.estadisticas_no_toma').attr('data-maquina',d.detalle.id_maquina);
      fila.find('.medida,.acciones_validacion').closest('td').toggle(mostrar_botones);
    
      fila.find('input').each(function(){$(this).attr('title',$(this).val());});
      fila.find('.producido').prop('readonly',true).show().parent().show();
      fila.find('.producidoCalculado').prop('readonly',true).show().parent().show();
      fila.find('.diferencia').prop('readonly',true).show().parent().show();
      fila.find('.tipo_causa_no_toma').attr('disabled',true);
        
      if (d.id_tipo_causa_no_toma != null) {
        fila.find('.tipo_causa_no_toma').css('border','2px solid #1E90FF').css('color','#1E90FF');
      }

      fila.find('.medida').parent().show();
    }
    
    tabla.append(fila);
  });

  $('.pop').popover({
    html:true
  });
}

$(function(){
  let truncadas = 0;
  
  $(document).on('click','.cancelarAjuste',function(e){
    $('.pop').popover('hide');
  });

  $(document).on('click','.ajustar',function(e){
    const medida_es_credito = $(this).siblings('input:checked').val() == 'credito';
    const fila   = $(this).closest('tr');
    
    fila.attr('data-medida', medida_es_credito? 1 : 2);
    
    if(!medida_es_credito){
      const den = fila.attr('data-denominacion') ?? '';
      fila.attr('data-denominacion',den == ''? 0.01 : den);
    }
    
    $(this).closest('.popover').siblings('.pop').find('i')
    .toggleClass('fa-life-ring',medida_es_credito)
    .toggleClass('fa-usd-circle',!medida_es_credito);
    
    enviarCambioDenominacion(fila.attr('id'),fila.attr('data-medida'), fila.attr('data-denominacion'));
  });

  $(document).on('click' , '.estadisticas_no_toma' , function (){
    const win = window.open('http://' + window.location.host + "/relevamientos/estadisticas_no_toma/" + $(this).val(), '_blank');

    if (win) {
      //Browser has allowed it to be opened
      win.focus();
    } else {
      //Browser has blocked it
      alert('Please allow popups for this website');
    }
  });

  function calculoDiferenciaValidar(tablaValidarRelevamiento, data){
    //debido a que el metodo se llama en ultima instancia para validar, ahi empieza el contador desde cero
    truncadas=0;
    data.detalles.forEach(function(d){
      const id_detalle = d.detalle.id_detalle_relevamiento;
      const fila = tablaValidarRelevamiento.find(`tr[data-id-detalle-relevamiento="${id_detalle}"]`);
      
      const iconoPregunta   = fila.find('.icono_no_importado').hide();
      const iconoCruz       = fila.find('.icono_incorrecto').hide();
      const iconoNoToma     = fila.find('.icono_no_toma').hide();
      const iconoCheck      = fila.find('.icono_correcto').hide();
      const iconoAdmiracion = fila.find('.icono_truncado').hide();
      
      const diferencia      = fila.find('td input.diferencia');
      
      if(d.tipo_causa_no_toma != null) {
        iconoNoToma.show();
        return;
      }
      
      if(d.producido == null) {//no se importaron contadores muestra = ?
        diferencia.css('border',' 2px solid #EF5350').css('color','#EF5350');
        iconoPregunta.show();
        return;
      }
      
      //calcular la diferencia entre lo calculado y lo importado
      const diff = Math.abs(Number(d.detalle.producido_calculado_relevado - d.producido).toFixed(2));
      diferencia.val(diff);
      
      if(d.detalle.producido_calculado_relevado != null && (diff >= 1000000) && ((diff % 1000000) == 0)){
        truncadas++;
        iconoAdmiracion.show();
        diferencia.css('border','2px solid #FFA726').css('color','#FFA726');
        return;
      }
          
      if(d.detalle.producido_calculado_relevado == null || diff != 0){
        diferencia.css('border',' 2px solid #EF5350').css('color','#EF5350');
        iconoCruz.show();
        return;
      }
      
      iconoCheck.show();
      diferencia.css('border','2px solid #66BB6A').css('color','#66BB6A');
    });
  }
  
  function enviarCambioDenominacion(id_maquina, medida, denominacion) {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
    $.ajax({
      type: "POST",
      url: 'relevamientos/modificarDenominacionYUnidad',
      data: {
        id_detalle_relevamiento: id_maquina,
        id_unidad_medida: medida,
        denominacion: denominacion,
      },
      dataType: 'json',
      success: function(data){
        $('.pop').popover('hide');
        //Volver a cargar la tabla y ver la diferencia
        $.get('relevamientos/obtenerRelevamiento/' + $('#modalValidarRelevamiento #id_relevamiento').val(), function(data){
          const tbody = $('#tablaValidarRelevamiento tbody');
          tbody.find('tr').remove();
          cargarTablaRelevamientos(data, tbody, 'Validar');
          calculoDiferenciaValidar(tbody, data);
        });
      },
      error: function(error){
        console.log('Error de cambio denominacion: ', error);
      },
    });
  }

  //VALIDAR EL RELEVAMIENTO
  $(document).on('click','.validar',function(e){
    e.preventDefault();
    truncadas=0;
    const id_relevamiento = $(this).val();
    $('#modalValidarRelevamiento #id_relevamiento').val(id_relevamiento);
    $('#mensajeValidacion').hide();
    $('#btn-finalizarValidacion').show();

    $.get('relevamientos/obtenerRelevamiento/' + id_relevamiento, function(data){
      $('#validarFechaActual').val(convertirDate(data.relevamiento.fecha));
      $('#validarCasino').val(data.casino);
      $('#validarSector').val(data.sector);
      $('#validarFiscaToma').val(data.usuario_fiscalizador.nombre);
      $('#validarFiscaCarga').val(data.usuario_cargador.nombre );
      $('#validarTecnico').val(data.relevamiento.tecnico);
      $('#observacion_validacion').val('');
      $('#tablaValidarRelevamiento tbody tr').remove();

      const tbody = $('#tablaValidarRelevamiento tbody');
      cargarTablaRelevamientos(data, tbody, 'Validar');
      calculoDiferenciaValidar(tbody, data);
      $('#modalValidarRelevamiento').modal('show');
    });
  })

  //validar
  $('#btn-finalizarValidacion').click(function(e){
    $('#modalValidarRelevamiento').modal('hide');//???

    const id_relevamiento = $('#modalValidarRelevamiento #id_relevamiento').val();
    const maquinas_a_pedido = [];
    const data = [];

    $('#tablaValidarRelevamiento tbody tr').each(function(){   
      data.push({
        id_detalle_relevamiento: $(this).attr('id'),
        denominacion: $(this).attr('data-denominacion'),
        diferencia: $(this).find('.diferencia').val(),
        importado: $(this).find('.producido').val()
      });

      if($(this).find('.a_pedido').length && $(this).find('.a_pedido').val() != 0){
        maquinas_a_pedido.push({
          id: $(this).find('.a_pedido').attr('data-maquina'),
          en_dias: $(this).find('.a_pedido').val(),
        });
      }
    });

    const formData = {
      id_relevamiento: id_relevamiento,
      observacion_validacion: $('#observacion_validacion').val(),
      maquinas_a_pedido: maquinas_a_pedido,
      truncadas: truncadas,
      data
    };

    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
    $.ajax({
        type: 'POST',
        url: 'relevamientos/validarRelevamiento',
        data: formData,
        dataType: 'json',
        success: function (data) {
          $('#btn-buscar').trigger('click');
          $('#modalValidarRelevamiento').modal('hide');
        },
        error: function (data) {
          console.log(data);
          $('#mensajeValidacion').show();
          $("#modalValidarRelevamiento").animate({ scrollTop: $('#mensajeValidacion').offset().top }, "slow");
        },
    });
  });

  $(document).on('click','.verDetalle',function(e){
    e.preventDefault();

    const id_rel = $(this).val();

    $.get('relevamientos/verRelevamientoVisado/' + id_rel, function(data){
      $('#frmValidarRelevamiento').trigger('reset');
      $('#validarFechaActual').val(convertirDate(data.relevamiento.fecha_generacion));
      $('#validarCasino').val(data.casino);
      $('#validarSector').val(data.sector);
      $('#validarFiscaToma').val(data.fiscalizador);
      $('#validarFiscaCarga').val(data.cargador );
      $('#validarTecnico').val(data.relevamiento.tecnico);
      $('#observacion_validacion').val(data.relevamiento.observacion_validacion).prop('disabled',true);
      $('#observacion_fisca_validacion').val(data.relevamiento.observacion_carga);
      $('#tablaValidarRelevamiento tbody tr').remove();
      $('#btn-finalizarValidacion').hide();

      data.detalles.forEach(function(d){
        const fila = $('.moldeVer').clone().removeClass('moldeVer');
        fila.attr('data-id-detalle-relevamiento',d.id_detalle_relevamiento);
        fila.find('.nro_admin').text(d.nro_admin);
        
        for(let c=1;c<=CONTADORES;c++){
          const val = d.detalle?.['cont'+c];
          fila.find('.cont'+c).text(val ?? ' - ');
        }
        
        fila.find('.producido_calculado_relevado').text(d.detalle?.producido_calculado_relevado ?? ' - ');
        fila.find('.producido_importado').text(d.detalle?.producido_importado ?? ' - ');
        fila.find('.diferencia').text(d.detalle?.diferencia ?? ' - ');
        fila.find('.tipo_no_toma').text(d.tipo_no_toma ?? ' - ');
        fila.find('.denominacion').text(d.denominacion ?? ' - ');
        fila.find('.tipo_no_toma').text(d.tipo_no_toma ?? ' - ');
        fila.find('.fecha').text(d?.mtm_a_pedido?.fecha ?? ' - ');
        
        $('#tablaValidarRelevamiento tbody').append(fila);
      });

      $('#modalValidarRelevamiento').modal('show');
    });
  });
});
