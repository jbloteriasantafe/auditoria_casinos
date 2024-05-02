import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import {AUX} from "/js/Components/AUX.js";
import './maquinasPorRelevamientos.js';
import './generarRelevamiento.js';
import './relevamientoSinSistema.js';

var truncadas=0;

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Relevamientos');
  
  $('[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
    ret.data.forEach(function(r){
      const fila = molde.clone();
      fila.find('.fecha').text(convertirDate(r.fecha));
      fila.find('.casino').text(r.casino);
      fila.find('.sector').text(r.sector);
      fila.find('.subrelevamiento').text(r.subrelevamiento ?? '');
      fila.find('[data-id-estado-relevamiento]').filter(function(){
        const list = $(this).attr('data-id-estado-relevamiento').split(',');
        return list.includes(r.id_estado_relevamiento+'');
      }).show(); 
      fila.find('button').val(r.id_relevamiento); 
      tbody.append(fila);
    });
  }).trigger('buscar');
  
  $('[data-js-cambio-casino-select-sectores]').each(function(){
    $(this).on('change',function(){
      const casino = $(this);
      
      const sectores = $(casino.attr('data-js-cambio-casino-select-sectores'));
      sectores.find('option:not([data-js-cambio-casino-mantener])').remove();
      
      if(casino.val() == '' || casino.val() == '0') return sectores.trigger('cambioSectores',[[]]);
      
      AUX.GET("relevamientos/obtenerSectoresPorCasino/"+casino.val(),{},function(data){
        data.sectores.forEach(function(s){
          sectores.append($('<option>').val(s.id_sector).text(s.descripcion));
        });
        sectores.trigger('cambioSectores',[data.sectores]);
      });
    });
  });

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
});

$('#fecha').on('change', function (e) {
  $(this).trigger('focusin');
  habilitarBotonGuardar();
})

$('[data-toggle][data-minimizar]').click(function(){
  const minimizar = !!$(this).data('minimizar');
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data('minimizar',!minimizar);
});

$(".pop").hover(function(){
  $(this).popover('show');
});

$(".pop").mouseleave(function(){
  $(this).popover('hide');
});

$('.modal').on('hidden.bs.modal', function() {
  ocultarErrorValidacion($('.form-control'));
  $(document).find('.sector').empty();
})

$(document).on('click','.pop',function(e){
    e.preventDefault();
});

//ABRIR MODAL DE NUEVO RELEVAMIENTO
$('#btn-nuevoRelevamiento').click(function(e){
  e.preventDefault();
  return $('[data-js-modal-generar-relevamiento]').trigger('mostrar');
});

$('#modalCargaRelevamiento').on('hidden.bs.modal', function(){
  //limpiar modal
  $('#modalCargaRelevamiento #frmCargaRelevamiento').trigger('reset');
  $('#modalCargaRelevamiento #inputFisca').prop('readonly',false);
});

let guardado = true;
let salida = 0; //cantidad de veces que se apreta salir

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

//FINALIZAR EL RELEVAMIENTO
$('#btn-finalizar').click(function(e){
  e.preventDefault();
  //Se evnía el relevamiento para guardar con estado 3 = 'Finalizado'
  enviarRelevamiento(3);
  $('#modalValidarRelevamiento').modal('hide');

});

//GUARDAR TEMPORALMENTE EL RELEVAMIENTO
$('#btn-guardar').click(function(e){
  e.preventDefault();
  //Se evnía el relevamiento para guardar con estado 2 = 'Carga parcial'
  enviarRelevamiento(2);
  $('#modalCargaRelevamiento .mensajeSalida').hide();
  $('#modalValidarRelevamiento').modal('hide');

});

$('select').focusin(function(e){
  $(this).removeClass('alerta');
});

$(document).on('click','.validado',function(){
  window.open('relevamientos/generarPlanillaValidado/' + $(this).val(),'_blank');
})
//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.imprimir',function(){
  window.open('relevamientos/generarPlanilla/' + $(this).val(),'_blank');
});

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
  window.open('relevamientos/generarPlanilla/' + $(this).val(),'_blank');
});

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
      const fila = $('#moldesFilas .moldeVer').clone().removeClass('moldeVer');
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

$('#btn-relevamientoSinSistema').click(function(e) {
  e.preventDefault();
  return $('[data-js-modal-relevamiento-sin-sistema]').trigger('mostrar');
});

//ABRIR MODAL DE CARGAR MAQUINAS POR RELEVAMIENTO
$('#btn-maquinasPorRelevamiento').click(function(e) {
  e.preventDefault();
  return $('[data-js-modal-maquinas-por-relevamiento]').trigger('mostrar');
});

$(document).on('focusin' , 'input' , function(e){
  $(this).removeClass('alerta');
})

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

$(document).on('click','.pop',function(e){
  e.preventDefault();
  $('.pop').not(this).popover('hide');
  $(this).popover('show');
});

$(document).on('click','.cancelarAjuste',function(e){
  $('.pop').popover('hide');
});

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

function cargarTablaRelevamientos(data, tabla, estadoRelevamiento){
  //se cargan las observaciones del fiscalizador si es que hay
  $('#observacion_fisca_validacion').val(data.relevamiento.observacion_carga);
  
  const td = function(arg){return $('<td>').append(arg);}

  data.detalles.forEach(function(d){
    //  Unidad de medida: 1-Crédito, 2-Pesos      |    Denominación: para créditos

    const fila = $('#moldesFilas .moldeCarga').clone().removeClass('moldeCarga')
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
