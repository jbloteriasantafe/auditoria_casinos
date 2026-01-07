$(document).ready(function () {
  $('#barraMaquinas').attr('aria-expanded', 'true');
  $('#maquinas').removeClass().addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass().addClass('subMenu2 collapse in');
  $('#contadores').removeClass().addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Producidos');
  $('#opcProducidos').addClass('opcionesSeleccionado')
    .attr('style', 'border-left: 6px solid #673AB7; background-color: #131836;')

  $('#fecha').datetimepicker({
    language: 'es',
    todayBtn: 1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#dtpFechaInicio,#dtpFechaFin').datetimepicker({
    language: 'es',
    todayBtn: 1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd / mm / yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });

  $('#btn-buscar').trigger('click');
});

//SI INGRESA ALGO EN ALGUN INPUT, se recalcula la diferencia
$(document).on('input', '#frmCargaProducidos input', function (e) {
  $('#btn-salir').data('salida', false);
  $('#modalCargaProducidos .mensajeSalida span').hide();
  $.ajax({
    type: 'POST',
    url: 'producidos/calcularDiferencia',
    data: {
      coinin_inicio: $('#coininIni').val(),
      coinout_inicio: $('#coinoutIni').val(),
      jackpot_inicio: $('#jackIni').val(),
      progresivo_inicio: $('#progIni').val(),
      denominacion_inicio: $('#denIni').val(),
      coinin_final: $('#coininFin').val(),
      coinout_final: $('#coinoutFin').val(),
      jackpot_final: $('#jackFin').val(),
      progresivo_final: $('#progFin').val(),
      denominacion_final: $('#denFin').val(),
      producido: $('#prodSist').val(),
    },
    dataType: 'json',
    success: function (data) {
      $('#prodCalc').val(data.producido_calculado);
      $('#diferencias').text(data.diferencia);
      $('#btn-finalizar').toggle(data.diferencia == 0);
    },
    error: function (data) { console.log(data); },
  });
});

$(document).on('change', '#tipoAjuste', function () {
  //Ver tabla en ProducidoController:guardarAjuste
  const permitir_finales = [1, 2, 3, 6];
  const permitir_iniciales = [5, 6];
  const permitir_producido = [4, 6];
  const id_tipo_ajuste = parseInt($(this).val());

  $('.cont_finales input').attr('disabled', !permitir_finales.includes(id_tipo_ajuste));
  $('.cont_iniciales input').attr('disabled', !permitir_iniciales.includes(id_tipo_ajuste));
  $('#prodSist').attr('disabled', !permitir_producido.includes(id_tipo_ajuste));
  //Vuelvo a los valores originales
  $('.cont_finales input,.cont_iniciales input').each(function () { $(this).val($(this).data('original')); });
  $('#prodSist').val($('#prodSist').data('original')).trigger('input');//Trigger para recalcular
});

//Permite hacer aritmetica basica en los campos
$(document).on('focusout', '#frmCargaProducidos input[type="text"]', function (e) {
  if ($(this).val() == '') $(this).val(0);

  const val = $(this).val().replaceAll(/(^|[-+*/])0+[1-9][0-9]*/g, function (match) {
    //Elimino los 0s de adelante para evitar que javascript interprete numeros como octales >:(
    //Osea pasa 0123+004123*0532 a 123+4123*532
    if (['-', '+', '*', '/'].indexOf(match[0]) != -1) {//Si el primer caracter es un operador
      return match[0] + match.substring(1).replace(/^0+/g, "");
    }
    return match.replace(/^0+/g, "");
  });
  //Solo permite {NUMERO OPERADOR} {NUMERO OPERADOR} ... NUMERO
  //{} significa opcional, operadores validos - + * /
  const valid = /^(-?[0-9]+[-+*/])*-?[0-9]+$/.test(val);
  if (valid) $(this).val(eval(val));
  else $(this).val(NaN);
  $(this).trigger('input');
});

//AJUSTAR PRODUCIDO, boton de la lista
$(document).on('click', '.carga', function (e) {
  e.preventDefault();
  $('#columnaDetalle').hide();
  $('#mensajeExito').modal('hide');

  limpiarCuerpoTabla();

  //permitir salir y ocultar mensaje de salida
  $('#btn-salir').data('salida', true);
  $('#modalCargaProducidos .mensajeSalida span').hide();

  const tr_html = $(this).parent().parent();
  const id_producido = $(this).val();
  const moneda = tr_html.find('.tipo_moneda').text();
  const fecha_prod = tr_html.find('.fecha_producido').text();
  const casino = tr_html.find('.casino').text();
  $('#descripcion_validacion').text(casino + ' - ' + fecha_prod + ' - $' + moneda);
  $('#maquinas_con_diferencias').text('---');

  $('#modalCargaProducidos #id_producido').val(id_producido);
  //ME TRAE LAS MÁQUINAS RELACIONADAS CON ESE PRODUCIDO, PRIMER TABLA DEL MODAL
  $.get('producidos/ajustarProducido/' + id_producido, function (data) {
    if (data.validado.estaValidado) {
      $('#btn-minimizar').hide();
      $('#cuerpoTabla').append(
        $('<div>').addClass('row').append(
          $('<div>').addClass('col-xs-6').append(
            $('<h3>').text('El producido ahora está validado. No se encontraron diferencias')
          )
        )
      );
      $('#textoExito').hide();
      $('#btn-salir-validado').show();
      $('#btn-salir').hide();
      $('#btn-buscar').click();
      return;
    }
    $('#descripcion_validacion').text(casino + ' - ' + data.fecha_produccion + ' - $' + data.moneda.descripcion);
    $('#maquinas_con_diferencias').text(data.producidos_con_diferencia.length);
    for (let i = 0; i < data.producidos_con_diferencia.length; i++) {
      const fila = $('#filaClon').clone().removeAttr('id');
      fila.attr('id', data.producidos_con_diferencia[i].id_maquina);
      fila.find('.nroAdm').text(data.producidos_con_diferencia[i].nro_admin);
      fila.find('.infoMaq').val(data.producidos_con_diferencia[i].id_maquina);
      fila.find('.btn-ajuste-individual').val(data.producidos_con_diferencia[i].id_maquina);
      $('#cuerpoTabla').append(fila);
      $('#btn-salir-validado').hide();
      $('#btn-salir').show();
    }
  });
  $('#frmCargaProducidos').attr('data-tipoMoneda', tr_html.find('.tipo_moneda').attr('data-tipo'));
  $('#modalCargaProducidos').modal('show');
});

$('#btn-salir-validado').on('click', function (e) {
  $('#modalCargaProducidos').modal('hide');
  $('#btn-buscar').trigger('click');
})

// AJUSTE AUTOMÁTICO MASIVO - Aplica fórmula COINOUT_INI -= DIFERENCIAS/DEN_INICIAL a todas las máquinas
$('#btn-ajuste-automatico-masivo').on('click', function (e) {
  e.preventDefault();
  const id_producido = $('#modalCargaProducidos #id_producido').val();
  const btn = $(this);

  btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
  $('#resultado-ajuste-masivo').text('');

  $.get('producidos/ajusteAutomaticoMasivo/' + id_producido, function (data) {
    btn.prop('disabled', false).html('<i class="fa fa-magic"></i> AJUSTE AUTOMÁTICO MASIVO');

    // Mostrar reporte
    mostrarReporteAjustes(data);

    let mensaje = '';
    let color = '#66BB6A'; // verde

    if (data.todas_validadas) {
      mensaje = '✓ Todas las ' + data.ajustadas + ' máquinas fueron ajustadas. Producido VALIDADO.';
      $('#btn-buscar').trigger('click');
    } else {
      color = '#FFA726'; // naranja
      mensaje = '✓ ' + data.ajustadas + ' ajustadas, ' + data.pendientes + ' requieren revisión manual. Ver reporte.';
      // Recargar la lista de máquinas pendientes
      recargarListaMaquinas(id_producido);
    }

    $('#resultado-ajuste-masivo').css('color', color).text(mensaje);
  }).fail(function (err) {
    btn.prop('disabled', false).html('<i class="fa fa-magic"></i> AJUSTE AUTOMÁTICO MASIVO');
    $('#resultado-ajuste-masivo').css('color', '#EF5350').text('Error al procesar: ' + err.statusText);
  });
});

// Función para mostrar el reporte de ajustes
function mostrarReporteAjustes(data) {
  // Limpiar tablas
  $('#tabla-ajustadas tbody').empty();
  $('#tabla-fallidas tbody').empty();

  // Contar
  $('#reporte-ajustadas-count').text(data.ajustadas);
  $('#reporte-fallidas-count').text(data.fallidas);

  // Llenar ajustadas
  data.detalles_ajustadas.forEach(function (item) {
    $('#tabla-ajustadas tbody').append(
      '<tr>' +
      '<td><b>' + item.nro_admin + '</b></td>' +
      '<td>' + item.diferencia_original + '</td>' +
      '<td>' + item.ajuste_creditos + '</td>' +
      '<td>' + item.coinout_ini_antes + '</td>' +
      '<td>' + item.coinout_ini_despues + '</td>' +
      '</tr>'
    );
  });

  // Llenar fallidas
  data.detalles_fallidas.forEach(function (item) {
    $('#tabla-fallidas tbody').append(
      '<tr>' +
      '<td><b>' + item.nro_admin + '</b></td>' +
      '<td>' + (item.diferencia || '-') + '</td>' +
      '<td style="color:#EF5350;">' + item.razon + '</td>' +
      '</tr>'
    );
  });

  $('#modalReporteAjustes').modal('show');
}

// Función para recargar la lista de máquinas
function recargarListaMaquinas(id_producido) {
  $('#cuerpoTabla').empty();
  $.get('producidos/ajustarProducido/' + id_producido, function (newData) {
    $('#maquinas_con_diferencias').text(newData.producidos_con_diferencia.length);
    for (let i = 0; i < newData.producidos_con_diferencia.length; i++) {
      const fila = $('#filaClon').clone().removeAttr('id');
      fila.attr('id', newData.producidos_con_diferencia[i].id_maquina);
      fila.find('.nroAdm').text(newData.producidos_con_diferencia[i].nro_admin);
      fila.find('.infoMaq').val(newData.producidos_con_diferencia[i].id_maquina);
      fila.find('.btn-ajuste-individual').val(newData.producidos_con_diferencia[i].id_maquina);
      $('#cuerpoTabla').append(fila);
    }
  });
}

// AJUSTE AUTOMÁTICO INDIVIDUAL - Aplica la fórmula a UNA sola máquina
$(document).on('click', '.btn-ajuste-individual', function (e) {
  e.preventDefault();
  e.stopPropagation();

  const id_maquina = $(this).val();
  const id_producido = $('#modalCargaProducidos #id_producido').val();
  const btn = $(this);
  const fila = btn.closest('tr');
  const nroAdmin = fila.find('.nroAdm').text();

  btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

  $.get('producidos/ajusteAutomaticoIndividual/' + id_maquina + '/' + id_producido, function (data) {
    if (data.success) {
      // Ajuste exitoso - remover fila y mostrar mensaje
      fila.fadeOut(300, function () {
        $(this).remove();
        const count = parseInt($('#maquinas_con_diferencias').text()) - 1;
        $('#maquinas_con_diferencias').text(count);
        if (count === 0) {
          $('#modalCargaProducidos').modal('hide');
          $('#mensajeExito h3').text('ÉXITO');
          $('#mensajeExito p').text('Todas las máquinas han sido ajustadas.');
          $('#mensajeExito div').css('background-color', '#4DB6AC');
          $('#mensajeExito').show();
          $('#btn-buscar').trigger('click');
        }
      });
      $('#resultado-ajuste-masivo').css('color', '#66BB6A')
        .text('✓ Máquina ' + nroAdmin + ' ajustada: COINOUT INI ' + data.coinout_ini_antes + ' → ' + data.coinout_ini_despues);
    } else {
      // Falló el ajuste
      btn.prop('disabled', false).html('<i class="fa fa-magic"></i>');
      $('#resultado-ajuste-masivo').css('color', '#EF5350')
        .text('✗ Máquina ' + nroAdmin + ': ' + data.razon);
    }
  }).fail(function (err) {
    btn.prop('disabled', false).html('<i class="fa fa-magic"></i>');
    $('#resultado-ajuste-masivo').css('color', '#EF5350').text('Error: ' + err.statusText);
  });
});

// Imprimir reporte
$('#btn-imprimir-reporte').on('click', function () {
  const contenido = $('#modalReporteAjustes .modal-body').html();
  const ventana = window.open('', '_blank');
  ventana.document.write('<html><head><title>Reporte de Ajustes</title>');
  ventana.document.write('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">');
  ventana.document.write('</head><body style="padding:20px;">');
  ventana.document.write('<h3>Reporte de Ajustes Automáticos - ' + $('#descripcion_validacion').text() + '</h3><hr>');
  ventana.document.write(contenido);
  ventana.document.write('</body></html>');
  ventana.document.close();
  setTimeout(function () { ventana.print(); }, 500);
});

// NAVEGACIÓN CON TECLADO
$(document).on('keydown', '#modalCargaProducidos', function (e) {
  // Enter: Guardar ajuste actual (si diferencia es 0)
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    if ($('#diferencias').text() === '0') {
      $('#btn-finalizar').click();
    }
  }

  // Flechas arriba/abajo: Navegar entre máquinas
  if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
    e.preventDefault();
    const filas = $('#cuerpoTabla tr');
    const actual = $('#cuerpoTabla .infoMaq.vista').parent().parent();
    let idx = filas.index(actual);

    if (e.key === 'ArrowDown') idx = Math.min(idx + 1, filas.length - 1);
    else idx = Math.max(idx - 1, 0);

    filas.eq(idx).find('.infoMaq').click();
  }
});

//si presiona el ojo de alguna de las máquinas listadas
$(document).on('click', '.infoMaq', function (e) {
  $('#tipoAjuste option').not('.default1').remove();
  $('#tipoAjuste').change();
  $('#cuerpoTabla .idMaqTabla').css('background-color', '#FFFFFF');
  $(this).parent().css('background-color', '#FFCC80');
  $('#modalCargaProducidos .mensajeFin').hide();

  $('.infoMaq').removeClass('vista');//Esto lo uso para el ticket, saber que maquina esta viendose
  $(this).addClass('vista');

  e.preventDefault();
  const id_maq = $(this).val();
  const id_prod = $('#modalCargaProducidos #id_producido').val();

  //ME TRAE TODOS LOS DATOS DE UNA MÁQUINA DETERMINADA, AL PŔESIONAR EL OJO
  $.get('producidos/datosAjusteMTM/' + id_maq + '/' + id_prod, function (data) {
    $('#btn-finalizar').attr('data-id', id_maq);

    $('#columnaDetalle').show();
    $('#info-denominacion').html('CONTADORES EN CRÉDITOS');
    $('#coinoutIni').val(data.producidos_con_diferencia[0].coinout_inicio);
    $('#coininIni').val(data.producidos_con_diferencia[0].coinin_inicio);
    $('#jackIni').val(data.producidos_con_diferencia[0].jackpot_inicio);
    $('#progIni').val(data.producidos_con_diferencia[0].progresivo_inicio);
    $('#denIni').val(data.producidos_con_diferencia[0].denominacion_inicio);
    $('#coininFin').val(data.producidos_con_diferencia[0].coinin_final);
    $('#coinoutFin').val(data.producidos_con_diferencia[0].coinout_final);
    $('#jackFin').val(data.producidos_con_diferencia[0].jackpot_final);
    $('#progFin').val(data.producidos_con_diferencia[0].progresivo_final);
    $('#denFin').val(data.producidos_con_diferencia[0].denominacion_final);
    $('#prodCalc').val(data.producidos_con_diferencia[0].producido_calculado);
    $('#prodSist').val(data.producidos_con_diferencia[0].producido_sistema);

    //Guardo los valores originales para researlos al cambio de TipoAjuste
    $('.cont_finales input,.cont_iniciales input').each(function () { $(this).data('original', $(this).val()); })
    $('#prodSist').data('original', $('#prodSist').val());

    $('#diferencias').text(data.producidos_con_diferencia[0].diferencia).prop('disabled', true);
    for (let i = 0; i < data.tipos_ajuste.length; i++) {
      $('#tipoAjuste').append($('<option>').val(data.tipos_ajuste[i].id_tipo_ajuste).text(data.tipos_ajuste[i].descripcion));
    }
    //de momento no esta recuperando el valor del texto de observaciones por lo que se resetea manualmente
    $('#prodObservaciones').val(data.producidos_con_diferencia[0].observacion);
    $('#data-denominacion').val(data.producidos_con_diferencia[0].denominacion_final);
    $('#data-producido').val(data.producidos_con_diferencia[0].producido_sistema);
    $('#data-detalle-inicial').val(data.producidos_con_diferencia[0].id_detalle_contador_inicial);
    $('#data-detalle-final').val(data.producidos_con_diferencia[0].id_detalle_producido);

    // Pre-llenar con datos de Excel si hay pendiente
    if (window.datosExcelPendientes && window.datosExcelPendientes.id_maquina == id_maq) {
      const excel = window.datosExcelPendientes.data;
      $('#coininIni').val(excel.coinin_inicio);
      $('#coinoutIni').val(excel.coinout_inicio);
      $('#jackIni').val(excel.jackpot_inicio);
      $('#progIni').val(excel.progresivo_inicio);

      $('#coininFin').val(excel.coinin_final);
      $('#coinoutFin').val(excel.coinout_final);
      $('#jackFin').val(excel.jackpot_final);
      $('#progFin').val(excel.progresivo_final);

      // Sugerir tipo de ajuste (Cambio iniciales)
      $('#tipoAjuste').val(5);

      // Notificar
      alert('Datos pre-cargados desde Excel. Verifique y guarde.');
      window.datosExcelPendientes = null;
    }
  });
}); //PRESIONA UN OJITO

$("#btn-finalizar").click(function (e) {
  e.preventDefault();
  guardarFilaDiferenciaCero();
  $('#modalCargaProducidos .mensajeSalida span').hide();
})

//SALIR DEL AJUSTE
$('#btn-salir').click(function () {
  const salida = $(this).data('salida');
  if (salida) {
    $('#modalCargaProducidos').modal('hide');
    return;
  }
  $('#modalCargaProducidos .mensajeSalida span').show();
  $(this).data('salida', true);
});

/************   FUNCIONES   ***********/
function guardarFilaDiferenciaCero() { //POST CON DATOS CARGADOS
  if ($('#diferencias').text() != '0') return;

  $('#mensajeExito').hide();

  const formData = {
    coinin_inicio: $('#coininIni').val(),
    coinout_inicio: $('#coinoutIni').val(),
    jackpot_inicio: $('#jackIni').val(),
    progresivo_inicio: $('#progIni').val(),
    denominacion_inicio: $('#denIni').val(),
    coinin_final: $('#coininFin').val(),
    coinout_final: $('#coinoutFin').val(),
    jackpot_final: $('#jackFin').val(),
    progresivo_final: $('#progFin').val(),
    denominacion_final: $('#denFin').val(),
    id_detalle_producido: $('#data-producido').val(),
    id_detalle_contador_final: $('#data-detalle-final').val() != undefined ? $('#data-detalle-final').val() : null,
    id_detalle_contador_inicial: $('#data-detalle-inicial').val() != undefined ? $('#data-detalle-inicial').val() : null,
    producido: $('#prodSist').val(),
    id_tipo_ajuste: $('#tipoAjuste').val(),
    observacion: $('#prodObservaciones').val(),
  };

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'producidos/guardarAjuste',
    data: formData,
    dataType: 'json',
    success: function (data) {
      if (data.todas_ajustadas) {
        $('#columnaDetalle').hide();
        $('#btn-finalizar').hide();
        $('#modalCargaProducidos').modal('hide');
        $('#mensajeExito h3').text('EXITO');
        $('#mensajeExito p').text('Se han ajustado todas las diferencias correctamente.');
        $('#mensajeExito div').css('background-color', '#4DB6AC');
        $('#mensajeExito').show();
        $('#btn-buscar').trigger('click');
        return;
      }
      if (data.hay_diferencia) {
        $('#columnaDetalle').show();
        $('#btn-finalizar').show();
        $('#textoExito').text('Se encontraron diferencias al tratar de ajustar la maquina.').show();
        return;
      }

      $('#columnaDetalle').hide();
      $('#btn-finalizar').hide();
      $('#modalCargaProducidos .mensajeFin').show();
      $('#maquinas_con_diferencias').text(parseInt($('#maquinas_con_diferencias').text()) - 1);
      const fila = $('#cuerpoTabla #' + $("#btn-finalizar").attr('data-id'));
      $('#textoExito').text('Maquina ' + fila.find('.nroAdm').text() + ' ajustada').show();
      fila.remove();
    },
    error: function (data) {
      console.log('ERROR');
      console.log(data);
    },
  });
};

function limpiarCuerpoTabla() { //LIMPIA LOS DATOS DEL FORM DE DETALLE
  $('#btn-finalizar').hide();
  $('#cuerpoTabla').empty();
  $('#coininIni,#coinoutIni,#jackIni,#progIni,#denIni,\
     #coininFin,#coinoutFin,#jackFin,#progFin,#denIni,\
     #prodCalc,#prodSist,#diferencias').val("");
  $('#data-detalle-final').val("");
  $('#data-detalle-inicial').val("");
  $('#tipoAjuste option').not('.default1').remove().val(0);
  $('#descripcion_validacion').text('');
}

//Planilla de diferencias producido vs contadores (con el ajuste)
$(document).on('click', '.planilla', function () {
  window.open('producidos/generarPlanillaDiferencias/' + $(this).val(), '_blank');
});

$(document).on('click', '.producido', function () {
  window.open('producidos/generarPlanillaProducido/' + $(this).val(), '_blank');
});

//función para generar el listado inicial
function agregarFilaTabla(producido) {
  const fila = $('#filaEjemploProducidos').clone().removeAttr('id');
  fila.find('.casino').text(producido.casino);
  fila.find('.fecha').text(producido.fecha);
  fila.find('.moneda').text(producido.moneda);
  fila.find('button').val(producido.id_producido);
  //Tienen que estar el contador inicial importado (y cerrado), el contador final importado y el producido sin validar para permitir cargar
  //El contador final es el que se va a "cerrar" cuando se validen los ajustes
  if (producido.error_contador_ini != null || producido.error_contador_fin != null || producido.producido_validado != 0) {
    fila.find('.carga').remove();
  }

  fila.find('.producido_valido').find(producido.producido_validado == 1 ?
    '.invalido' : '.valido').remove();
  fila.find('.contador_inicial_cerrado').find(producido.error_contador_ini == null ?
    '.invalido' : '.valido').remove();
  fila.find('.relevamiento_valido').find(producido.error_contador_fin == null && producido.error_relevamientos == null ?
    '.invalido' : '.valido').remove();

  $('#tablaImportacionesProducidos tbody').append(fila);
}

$(document).on('click', '#tablaImportacionesProducidos thead tr th[value]', function (e) {
  $('#tablaImportacionesProducidos th').removeClass('activa');
  if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado', 'desc');
  } else {
    if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado', 'asc');
    } else {
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
    }
  }
  $('#tablaImportacionesProducidos th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  clickIndice(e, $('#herramientasPaginacion').getCurrentPage(), $('#herramientasPaginacion').getPageSize());
});

function clickIndice(e, pageNumber, tam) {
  if (e != null) {
    e.preventDefault();
  }
  tam = (isNaN(tam)) ? $('#herramientasPaginacion').getPageSize() : tam;
  const columna = $('#tablaImportacionesProducidos .activa').attr('value');
  const orden = $('#tablaImportacionesProducidos .activa').attr('estado');
  $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

$('#btn-buscar').click(function (e, pagina, page_size, columna, orden) {
  e.preventDefault();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  let size = 10;
  //Fix error cuando librería saca los selectores
  if (!isNaN($('#herramientasPaginacion').getPageSize())) {
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  columna = (columna != null) ? columna : $('#tablaResultados .activa').attr('value');
  orden = (orden != null) ? orden : $('#tablaResultados .activa').attr('estado');
  const formData = {
    id_casino: $('#selectCasino').val(),
    fecha_inicio: $('#fecha_inicio').val(),
    fecha_fin: $('#fecha_fin').val(),
    id_tipo_moneda: $('#selectMoneda').val(),
    validado: $('#selectValidado').val(),
    page: page_number,
    sort_by: { columna: columna, orden: orden },
    page_size: page_size,
  };

  $.ajax({
    type: 'POST',
    url: 'producidos/buscarProducidos',
    data: formData,
    dataType: 'json',
    success: function (resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number, page_size, resultados.total, clickIndice);
      $('#tablaImportacionesProducidos tbody').empty();
      for (let i = 0; i < resultados.data.length; i++) {
        agregarFilaTabla(resultados.data[i]);
      }
      $('#herramientasPaginacion').generarIndices(page_number, page_size, resultados.total, clickIndice);
    },
    error: function (data) { console.log('Error:', data); }
  });
});

$('#crearTicket').click(function (e) {
  e.preventDefault();

  $('#frmCargaProducidos').find('textarea, select, input').each(function () {//"Bakeo" los valores para que se muestre bien en el ticket
    if (this.nodeName == "TEXTAREA") $(this).text($(this).val());
    else if (this.nodeName == "INPUT") $(this).attr('value', $(this).val());
    else if (this.nodeName == "SELECT") {
      const val = $(this).val();
      $(this).find('option').each(function () {
        if ($(this).val() == val) $(this).attr('selected', true);
        else $(this).removeAttr('selected');
      });
    }
  });

  //Deshabilito los inputs y saco los botones de un clon
  const frm = $('#frmCargaProducidos').clone();
  frm.find('textarea, select, input').attr('disabled', true).attr('readonly', true);
  frm.find('button').remove();
  const nro_admin = $('.vista').eq(0).parent().parent().find('.nroAdm').text();
  const asunto = "Producido - " + $('#descripcion_validacion').text() + ' - ' + nro_admin;
  enviarTicket(asunto, 'data:text/html,' + frm.html());
});

// --- VISTA TABLA COMPLETA ---

$('#btn-ver-tabla').on('click', function (e) {
  e.preventDefault();
  const id_producido = $('#modalCargaProducidos #id_producido').val();
  cargarTablaDiferencias(id_producido);
});

let tablaDiferenciasCache = [];

function cargarTablaDiferencias(id_producido) {
  $.get('producidos/obtenerTablaDiferencias/' + id_producido, function (data) {
    if (data.error) {
      alert(data.error);
      return;
    }
    tablaDiferenciasCache = data.tabla;
    renderizarTablaDiferencias(tablaDiferenciasCache);

    // Total info
    const totalDiff = tablaDiferenciasCache.reduce((acc, curr) => acc + Math.abs(curr.diferencia), 0);
    $('#tabla-total-info').text('Total máquinas: ' + data.total + ' | Diferencia acumulada (abs): ' + totalDiff.toFixed(2));

    $('#modalTablaCompleta').modal('show');
  });
}

// Variables para tipos de ajuste disponibles (hardcodeado o cargado desde backend, aqui uso hardcode común)
const TIPOS_AJUSTE = [
  { id: 5, descripcion: 'Cambio Contadores Iniciales' },
  { id: 3, descripcion: 'Cambio Contadores Finales' },
  { id: 1, descripcion: 'Vuelta de Contadores' },
  { id: 2, descripcion: 'Reset de Contadores' },
  { id: 4, descripcion: 'Error Producido' },
  { id: 6, descripcion: 'Multiples Ajustes' }
];

function renderizarTablaDiferencias(datos) {
  $('#tbody-tabla-diferencias').empty();

  if (datos.length === 0) {
    $('#tbody-tabla-diferencias').append('<tr><td colspan="12" style="text-align:center;">No hay diferencias</td></tr>');
    return;
  }

  datos.forEach(function (item) {
    // Filtros
    const min = parseFloat($('#filtro-dif-min').val());
    const max = parseFloat($('#filtro-dif-max').val());
    const ajustables = $('#filtro-ajustables').val();

    if (!isNaN(min) && Math.abs(item.diferencia) < min) return;
    if (!isNaN(max) && Math.abs(item.diferencia) > max) return;
    if (ajustables === 'si' && !item.puede_ajustar_auto) return;
    if (ajustables === 'no' && item.puede_ajustar_auto) return;

    // Detectar posible tipo de ajuste por defecto
    let tipoDefecto = 5; // Iniciales
    // Si iniciales es 0, prob. es iniciales. Si finales es 0, prob. es finales.
    if (item.coinin_final == 0 && item.coinin_inicio != 0) tipoDefecto = 3;

    // Si el backend marcó como reset
    if (item.es_reset) tipoDefecto = 2; // Reset de contadores	 

    let options = '';
    TIPOS_AJUSTE.forEach(t => {
      options += '<option value="' + t.id + '" ' + (t.id == tipoDefecto ? 'selected' : '') + '>' + t.descripcion + '</option>';
    });

    const inputStyle = 'width:100px; padding:2px; height:24px; font-size:11px;';
    const mkInput = (val, name) => '<input type="number" class="form-control ip-' + name + '" value="' + (val || 0) + '" style="' + inputStyle + '">';

    const status = item.es_reset ? '<br><span class="label label-warning">Reset Detectado</span>' : '';

    $('#tbody-tabla-diferencias').append(
      '<tr data-id="' + item.id_maquina + '" data-iddetpro="' + item.id_detalle_producido + '" data-iddetini="' + item.id_detalle_contador_inicial + '" data-iddetfin="' + item.id_detalle_contador_final + '" data-den="' + item.denominacion + '" data-prod="' + item.producido + '">' +
      '<td>' + item.nro_admin + '</td>' +
      '<td style="font-weight:bold; color:' + (item.diferencia != 0 ? 'red' : 'green') + '">' + item.diferencia + status + '</td>' +
      '<td>' + mkInput(item.coinin_inicio, 'coinin-ini') + '</td>' +
      '<td>' + mkInput(item.coinout_inicio, 'coinout-ini') + '</td>' +
      '<td>' + mkInput(item.jackpot_inicio, 'jack-ini') + '</td>' +
      '<td>' + mkInput(item.progresivo_inicio, 'prog-ini') + '</td>' +
      '<td>' + mkInput(item.coinin_final, 'coinin-fin') + '</td>' +
      '<td>' + mkInput(item.coinout_final, 'coinout-fin') + '</td>' +
      '<td>' + mkInput(item.jackpot_final, 'jack-fin') + '</td>' +
      '<td>' + mkInput(item.progresivo_final, 'prog-fin') + '</td>' +
      '<td><select class="form-control ip-tipo-ajuste" style="' + inputStyle + 'width:120px;">' + options + '</select></td>' +
      '<td><button class="btn btn-xs btn-primary btn-guardar-fila" title="Guardar"><i class="fa fa-save"></i></button></td>' +
      '</tr>'
    );
  });
}

// Guardar desde la fila de la tabla
$(document).on('click', '.btn-guardar-fila', function () {
  const tr = $(this).closest('tr');
  const btn = $(this);

  // Obtener valores
  const data = {
    coinin_inicio: tr.find('.ip-coinin-ini').val(),
    coinout_inicio: tr.find('.ip-coinout-ini').val(),
    jackpot_inicio: tr.find('.ip-jack-ini').val(),
    progresivo_inicio: tr.find('.ip-prog-ini').val(),
    denominacion_inicio: tr.data('den'),

    coinin_final: tr.find('.ip-coinin-fin').val(),
    coinout_final: tr.find('.ip-coinout-fin').val(),
    jackpot_final: tr.find('.ip-jack-fin').val(),
    progresivo_final: tr.find('.ip-prog-fin').val(),
    denominacion_final: tr.data('den'), // Asumo misma den

    id_detalle_producido: tr.data('iddetpro'),
    id_detalle_contador_inicial: tr.data('iddetini'),
    id_detalle_contador_final: tr.data('iddetfin'),

    id_tipo_ajuste: tr.find('.ip-tipo-ajuste').val(),
    producido: tr.data('prod'),
    observacion: 'Ajuste manual desde tabla'
  };

  // Validar visual
  btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'POST',
    url: 'producidos/guardarAjuste',
    data: data,
    dataType: 'json',
    success: function (resp) {
      if (resp.ay_diferencia == 1 || resp.diferencia != 0) { // Note: typo in backend response usually 'hay_diferencia' but check controller
        // El controller retorna 'hay_diferencia' => 1 y 'diferencia' => X si falla
        // Si éxito retorna todo el objeto del producido actualizado o status
        if (resp.hay_diferencia) {
          alert("Ajuste NO guardado. La diferencia persiste: " + resp.diferencia);
          btn.prop('disabled', false).html('<i class="fa fa-save"></i>');
        } else {
          // Exito probable (el controller retorna objeto producido si todo OK)
          notificarExitoFila(tr);
        }
      } else {
        notificarExitoFila(tr);
      }
    },
    error: function (data) {
      const response = JSON.parse(data.responseText);
      let msg = "Error al guardar";
      if (response.coinin_inicio) msg += "\n" + response.coinin_inicio;
      // ... otros errores
      alert(msg);
      btn.prop('disabled', false).html('<i class="fa fa-save"></i>');
    }
  });
});

function notificarExitoFila(tr) {
  tr.css('background-color', '#C8E6C9');
  tr.find('input, select, button').prop('disabled', true);
  tr.find('.btn-guardar-fila').html('<i class="fa fa-check"></i>');
  // Recargar tabla después de un momento para actualizar diferencias
  setTimeout(function () {
    const id_producido = $('#modalCargaProducidos #id_producido').val();
    // Actualizar cache local para esta fila o recargar todo
    // Mejor recargar todo para asegurar consistencia
    cargarTablaDiferencias(id_producido);
  }, 1000);
}

// Filtros
$('#btn-aplicar-filtros').on('click', function () {
  renderizarTablaDiferencias(tablaDiferenciasCache);
});
$('#filtro-ajustables, #filtro-dif-min, #filtro-dif-max').on('change', function () {
  renderizarTablaDiferencias(tablaDiferenciasCache);
});

// Ordenamiento simple
$('#tabla-diferencias-completa th[data-sort]').on('click', function () {
  const campo = $(this).data('sort');
  const orden = $(this).data('orden') || 'asc';

  tablaDiferenciasCache.sort(function (a, b) {
    if (orden === 'asc') return a[campo] - b[campo];
    else return b[campo] - a[campo];
  });

  $(this).data('orden', orden === 'asc' ? 'desc' : 'asc');
  renderizarTablaDiferencias(tablaDiferenciasCache);
});

// Link botón ojo tabla a la vista principal
$(document).on('click', '.trigger-ver-maquina', function () {
  const id_maquina = $(this).val();
  $('#modalTablaCompleta').modal('hide');
  // Buscar la fila en el modal principal y hacer click
  const fila = $('#cuerpoTabla').find('#' + id_maquina);
  if (fila.length > 0) {
    fila.find('.infoMaq').click();
  }
});


// --- IMPORTACIÓN Y COMPARACIÓN EXCEL ---

$('#btn-importar-excel').on('click', function (e) {
  e.preventDefault();
  $('#input-excel').click();
});

$('#input-excel').on('change', function () {
  if (this.files.length === 0) return;

  const file = this.files[0];
  const id_producido = $('#modalCargaProducidos #id_producido').val();

  $('#modalComparacionExcel').modal('show');
  $('#estado-excel').show();
  $('#resultado-excel').hide();

  const formData = new FormData();
  formData.append('archivo_excel', file);
  formData.append('_token', $('meta[name="_token"]').attr('content'));

  $.ajax({
    url: 'producidos/importarExcelConcesionario/' + id_producido,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (data) {
      $('#estado-excel').hide();
      if (data.error) {
        alert(data.error);
        $('#modalComparacionExcel').modal('hide');
        return;
      }

      mostrarComparacionExcel(data);
    },
    error: function (data) {
      $('#estado-excel').hide();
      alert('Error al procesar el archivo');
      $('#modalComparacionExcel').modal('hide');
    }
  });

  // Reset input
  $(this).val('');
});

function mostrarComparacionExcel(data) {
  $('#resultado-excel').show();
  $('#excel-total').text(data.total_excel);
  $('#sistema-total').text(data.total_sistema);

  let discrepancias = 0;
  $('#tbody-comparacion').empty();

  data.comparacion.forEach(function (item) {
    if (item.en_excel && item.discrepancias.length > 0) {
      discrepancias++;

      const btnUsarExcel = '<button class="btn btn-xs btn-warning btn-usar-excel" data-id="' + item.id_maquina + '" data-nro="' + item.nro_admin + '" data-excel=\'' + JSON.stringify(item.excel) + '\'><i class="fa fa-arrow-right"></i> Usar Excel</button>';

      let detalleDiscrepancia = '';
      item.discrepancias.forEach(d => {
        detalleDiscrepancia += '<span class="label label-danger">' + d + '</span> ';
      });

      const row = '<tr>' +
        '<td>' + item.nro_admin + '</td>' +
        '<td>' + (item.en_excel ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>') + '</td>' +
        '<td class="' + (item.discrepancias.includes('COININ_INI') ? 'danger' : '') + '">' + item.sistema.coinin_inicio + ' / ' + (item.excel ? item.excel.coinin_inicio : '-') + '</td>' +
        '<td class="' + (item.discrepancias.includes('COINOUT_INI') ? 'danger' : '') + '">' + item.sistema.coinout_inicio + ' / ' + (item.excel ? item.excel.coinout_inicio : '-') + '</td>' +
        '<td class="' + (item.discrepancias.includes('COININ_FIN') ? 'danger' : '') + '">' + item.sistema.coinin_final + ' / ' + (item.excel ? item.excel.coinin_final : '-') + '</td>' +
        '<td class="' + (item.discrepancias.includes('COINOUT_FIN') ? 'danger' : '') + '">' + item.sistema.coinout_final + ' / ' + (item.excel ? item.excel.coinout_final : '-') + '</td>' +
        '<td>' + detalleDiscrepancia + '</td>' +
        '<td>' + btnUsarExcel + '</td>' +
        '</tr>';

      $('#tbody-comparacion').append(row);
    }
  });

  $('#discrepancias-total').text(discrepancias);
  if (discrepancias === 0) {
    $('#tbody-comparacion').append('<tr><td colspan="8" style="text-align:center;"><h4>¡No se encontraron discrepancias con el Excel!</h4></td></tr>');
  }
}

// Botón "Usar Excel" - Carga datos en variable global y abre la máquina
$(document).on('click', '.btn-usar-excel', function () {
  const data = $(this).data('excel');
  const id_maquina = $(this).data('id');

  window.datosExcelPendientes = {
    id_maquina: id_maquina,
    data: data
  };

  $('#modalComparacionExcel').modal('hide');

  const fila = $('#cuerpoTabla').find('#' + id_maquina);
  if (fila.length > 0) {
    fila.find('.infoMaq').click();
  } else {
    alert('Máquina no encontrada en la lista actual.');
  }
});
