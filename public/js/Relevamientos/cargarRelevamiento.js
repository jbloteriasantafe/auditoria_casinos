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
  $('#dtpFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    pickerPosition: "bottom-left",
    ignoreReadonly: true,
    format: 'HH:ii',
    startView: 1,
    minView: 0,
    minuteStep: 5,
  });
  
  $('#fecha').on('change', function (e) {
    $(this).trigger('focusin');
    habilitarBotonGuardar();
  });
  
  $('#modalCargaRelevamiento').on('hidden.bs.modal', function(){
    //limpiar modal
    $('#modalCargaRelevamiento #frmCargaRelevamiento').trigger('reset');
    $('#modalCargaRelevamiento #inputFisca').prop('readonly',false);
  });

  let guardado = true;
  let salida = 0; //cantidad de veces que se apreta salir
  let truncadas = 0;
  
  $(document).on('click','.carga',function(e){
    e.preventDefault();
    truncadas = 0;//@TODO: remover globales
    salida = 0;//ocultar mensaje de salida
    guardado = true;
    $('#modalCargaRelevamiento .mensajeSalida').hide();
    $("#modalCargaRelevamiento").animate({ scrollTop: 0 }, "slow");
    $('#btn-guardar').hide();//SI ESTÁ GUARDADO NO MUESTRA EL BOTÓN PARA GUARDAR
    $('#btn-finalizar').hide();

    const id_relevamiento = $(this).val();
    $('#id_relevamiento').val(id_relevamiento);

    $.get('relevamientos/obtenerRelevamiento/' + id_relevamiento, function(data){
      $('#cargaFechaActual').val(data.relevamiento.fecha);
      $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
      $('#cargaCasino').val(data.casino);
      $('#cargaSector').val(data.sector);
      $('#fecha').val(data.relevamiento.fecha_ejecucion);
      $('#fecha_ejecucion').val(data.relevamiento.fecha_ejecucion);
      $('#tecnico').val(data.relevamiento.tecnico);
      // si el relevamiento no tiene usuario fizcalizador se le asigna el actual
      $('#fiscaCarga').val(data?.usuario_cargador?.nombre ?? data.usuario_actual.usuario.nombre);
      $('#inputFisca').generarDataList('relevamientos/buscarUsuariosPorNombreYCasino/'+ data.id_casino,'usuarios','id_usuario','nombre',2);
      $('#inputFisca').setearElementoSeleccionado(data?.usuario_fiscalizador?.id_usuario ?? 0,data?.usuario_fiscalizador?.nombre ?? "");
      
      const tbody = $('#tablaCargaRelevamiento tbody');
      tbody.find('tr').remove();
      cargarTablaRelevamientos(data, tbody, 'Carga');
      tbody.find('tr .tipo_causa_no_toma').change();
      
      habilitarBotonFinalizar();
      $('#modalCargaRelevamiento').modal('show');
    });
  });


  $('#modalCargaRelevamiento').on('input', "input", function(){
    habilitarBotonGuardar();
  });

  $(document).on('change','.tipo_causa_no_toma',function(){
    const fila = $(this).closest('tr');
    //Si se elige algun tipo de no toma se vacian las cargas de contadores
    fila.find('.icono_estado').hide();
    if($(this).val() != ''){//Se cambia el icono de diferencia
      fila.find('.contador').val('');
      fila.find('.icono_no_toma').show();
    }
    else{
      fila.find('.cont1').trigger('input');
    }
  });

  //SALIR DEL RELEVAMIENTO
  $('#btn-salir').click(function(){

    //Si está guardado deja cerrar el modal
    if (guardado || salida != 0) return $('#modalCargaRelevamiento').modal('hide');
    //Si no está guardado
    $('#modalCargaRelevamiento .mensajeSalida').show();
    $("#modalCargaRelevamiento").animate({ scrollTop: $('.mensajeSalida').offset().top }, "slow");
    salida = 1;
  });


  //GUARDAR TEMPORALMENTE EL RELEVAMIENTO
  $('#btn-guardar').click(function(e){
    e.preventDefault();
    //Se evnía el relevamiento para guardar con estado 2 = 'Carga parcial'
    enviarRelevamiento(2);
    $('#modalCargaRelevamiento .mensajeSalida').hide();
    $('#modalValidarRelevamiento').modal('hide');
  });
  
  //FINALIZAR EL RELEVAMIENTO
  $('#btn-finalizar').click(function(e){
    e.preventDefault();
    //Se evnía el relevamiento para guardar con estado 3 = 'Finalizado'
    enviarRelevamiento(3);
    $('#modalValidarRelevamiento').modal('hide');
  });
  
  function enviarRelevamiento(estado) {
    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

    const detalles = $('#tablaCargaRelevamiento tbody tr').map(function(){
      let calculado = '';
      //Si se envía para finalizar se guarda el producido calculado
      if (estado == 3 && $(this).children().children('.tipo_causa_no_toma').val() == '') {
        calculado = calcularProducido($(this))[0]; //calculado relevado siempre se trabaja en dinero, no en creditos
        calculado = Math.round(calculado*100)/100;//Redondea a 2 digitos
      }
      const fila = $(this);
      return {
        id_unidad_medida: fila.attr('data-medida'),
        denominacion: fila.attr('data-denominacion'),
        id_detalle_relevamiento: fila.attr('data-id-detalle-relevamiento'),
        ...Object.fromEntries(Array.from(Array(8).keys()).map((c) => c+1).map(function(c){
          const cont = 'cont'+c;
          return [cont,fila.find('.'+cont).val().replace(/,/g,".")];
        })),
        id_tipo_causa_no_toma: fila.find('.tipo_causa_no_toma').val(),
        producido_calculado_relevado: calculado,
      };
    }).toArray();
      
    $.ajax({
      type: 'POST',
      url: 'relevamientos/cargarRelevamiento',
      dataType: 'JSON',
      data: {
        id_relevamiento: $('#modalCargaRelevamiento #id_relevamiento').val(),
        id_usuario_fiscalizador: $('#inputFisca').obtenerElementoSeleccionado(),
        observacion_carga: $('#observacion_carga').val(),
        tecnico: $('#tecnico').val(),
        hora_ejecucion: $('#fecha_ejecucion').val(),
        estado: estado,
        detalles: detalles.length? detalles : 0,//@TODO: no entiendo esto del 0?
        truncadas: truncadas
      },
      success: function (data) {
        $('#btn-buscar').trigger('click');

        guardado = true;
        $('#btn-guardar').hide();

        if (estado == 3) {
          $('#modalCargaRelevamiento').modal('hide');
        }
      },
      error: function (data) {
        const response = data.responseJSON;

        if(    typeof response.tecnico !== 'undefined'
            || typeof response.fecha_ejecucion !== 'undefined'
            || typeof response.id_usuario_fiscalizador !== 'undefined')
        {
          $("#modalCargaRelevamiento").animate({ scrollTop: 0 }, "slow");
        }

        if(typeof response.tecnico !== 'undefined'){
          mostrarErrorValidacion($('#tecnico'),response.tecnico[0]);
        }
        if(typeof response.fecha_ejecucion !== 'undefined'){
          mostrarErrorValidacion($('#fecha'),response.fecha_ejecucion[0]);

        }
        if(typeof response.id_usuario_fiscalizador !== 'undefined'){
          mostrarErrorValidacion($('#inputFisca'),response.id_usuario_fiscalizador[0]);
        }

        let filaError = null;
        $('#tablaCargaRelevamiento tbody tr').each(function(obj,idx){
          var error=' ';
          for(let c=1;c<=CONTADORES;c++){
            if(typeof response['detalles.'+ idx +'.cont'+c] !== 'undefined'){
              filaError = $(this);
              mostrarErrorValidacion($(this).find('.cont'+c),response['detalles.'+ idx +'.cont'+c][0],false);
            }
          }
        });

        if(filaError !== null){
          $("#modalCargaRelevamiento").animate({ scrollTop: filaError.offset().top }, "slow");
        }
      },
    });
  }

  function habilitarBotonGuardar(){
    guardado = false;
    $('#btn-guardar').show();
  }

  function habilitarBotonFinalizar(){
    let puedeFinalizar = true;
    const cantidadMaquinas = $('#tablaCargaRelevamiento tbody tr').each(function(i){   
      let inputLleno = false;
      
      //La fila tiene algun campo lleno
      $(this).children('td').find('.contador').each(function (j){
        inputLleno = inputLleno || ($(this).val().length > 0);
      });

      //Seleccionó un tipo de no toma
      const noToma = $(this).children('td').find('select').val() !== '';
      
      puedeFinalizar = puedeFinalizar && (inputLleno || noToma);
    });

    $('#btn-finalizar').toggle(puedeFinalizar);
  }
  
  function calcularProducido(fila){
    let suma = 0;
    let inputValido = false;
    
    for(let c=1;c<=CONTADORES;c++){
      const formulaCont = fila.find('.formulaCont'+c).val();
      const operador = fila.find('.formulaOper'+(c-1)).val();//Los operadores estan entre los contadores
      const contador_s = fila.find('td').children('.cont'+c).val();
      const contador = contador_s? parseFloat(contador_s.replace(/,/g,".")) : 0;
      
      inputValido = inputValido || (contador_s != '');
      
      if(formulaCont != ''){
        if(c == 1){//El primer contador no tiene operador (el signo esta bakeado en el numero)
          suma = contador;
        }
        else{
          if(operador == '+') suma += contador;
          else                suma -= contador;
        }
      }
    }
    
    const denominacion = fila.attr('data-medida') == 1? fila.attr('data-denominacion') : 1;
    return [Number((suma * denominacion)),inputValido];
  }

  //CAMBIOS EN TABLAS RELEVAMIENTOS / MOSTRAR BOTÓN GUARDAR
  $('#modalCargaRelevamiento').on('input', "#tablaCargaRelevamiento input:not(:radio):not('.denominacion')", function(){
    habilitarBotonGuardar();
    const fila = $(this).closest('tr');
    
    //Fijarse si se habilita o deshabilita el tipo no toma
    if($(this).val() != '') fila.find('.tipo_causa_no_toma').val('');

    habilitarBotonFinalizar();
    
    fila.find('.estado_diferencia .icono_estado').hide();
    if (fila.find('.producido').val() == ''){
      return fila.find('.estado_diferencia .icono_no_importado').show();
    }

    producido = parseFloat(fila.find('.producido').val());
    
    const [producido_calc,inputValido] = calcularProducido(fila);
    const diferencia = Number(producido_calc.toFixed(2)) - Number(Number(producido).toFixed(2));
    const diferencia_redondeada = Number(diferencia.toFixed(2));

    if (diferencia_redondeada == 0 && inputValido) {
      fila.find('.icono_correcto').show();
    }
    else if(Math.abs(diferencia_redondeada) > 1 && diferencia_redondeada%1000000 == 0 && inputValido) { //El caso de que no haya diferencia ignorando la unidad del millon (en pesos)
      fila.find('.icono_truncado').show();
    } 
    else {
      fila.find('.icono_incorrecto').show();
    }
  });

  function calculoDiferencia(tablaRelevamientos){
    //Calcular las diferencias
    tablaRelevamientos.find('tr').each(function(){
      $(this).find('input.cont1').eq(0).trigger('input');
    });
  }
});
