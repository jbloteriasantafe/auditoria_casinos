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

// AJUSTE AUTOMÁTICO - Aplica fórmula COINOUT_INI -= DIFERENCIAS/DEN_INICIAL a todas las máquinas
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
    $('#data-producido').val(data.producidos_con_diferencia[0].id_detalle_producido);
    $('#data-detalle-inicial').val(data.producidos_con_diferencia[0].id_detalle_contador_inicial);
    $('#data-detalle-final').val(data.producidos_con_diferencia[0].id_detalle_contador_final);

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

    // Mostrar fecha y casino en el título del modal
    let fechaTitulo = 'Todas las Diferencias';
    if (data.producido && data.producido.fecha) {
      fechaTitulo = data.producido.fecha + ' - ' + data.producido.casino;
    }
    $('#titulo-fecha-producido').text(fechaTitulo);

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

// Variable global para almacenar datos Excel
window.datosExcelRaw = null;

// --- SISTEMA DE UNDO (Ctrl+Z) ---
window.undoHistory = []; // Stack: [{rowId, field, oldValue}, ...]

function guardarParaUndo(input) {
  const tr = $(input).closest('tr');
  const rowId = tr.data('id');
  if (!rowId) return;

  // Identificar qué campo es por su clase específica
  let field = '';
  const classes = ['ip-den-ini', 'ip-coinin-ini', 'ip-coinout-ini', 'ip-jack-ini', 'ip-prog-ini',
    'ip-den-fin', 'ip-coinin-fin', 'ip-coinout-fin', 'ip-jack-fin', 'ip-prog-fin'];
  for (let c of classes) {
    if ($(input).hasClass(c)) {
      field = c;
      break;
    }
  }
  if (!field) return;

  const oldValue = $(input).val();

  // Evitar duplicados consecutivos
  const last = window.undoHistory[window.undoHistory.length - 1];
  if (last && last.rowId == rowId && last.field == field && last.oldValue == oldValue) return;

  window.undoHistory.push({
    rowId: rowId,
    field: field,
    oldValue: oldValue
  });

  // Limitar historial a 50 acciones
  if (window.undoHistory.length > 50) window.undoHistory.shift();
}

// Capturar valor ANTES de cambiar (focus)
$(document).on('focus', '#tbody-tabla-diferencias .ip-calc', function () {
  guardarParaUndo(this);
});

// Ctrl+Z para deshacer
$(document).on('keydown', '#modalTablaCompleta', function (e) {
  if (e.ctrlKey && e.key === 'z') {
    e.preventDefault();
    if (window.undoHistory.length === 0) return;

    const last = window.undoHistory.pop();
    const tr = $('#tbody-tabla-diferencias').find('tr[data-id="' + last.rowId + '"]');
    if (tr.length > 0) {
      const input = tr.find('.' + last.field);
      if (input.length > 0) {
        input.val(last.oldValue);
        calcularDiferenciaFila(tr); // Recalcular diferencia

        // Feedback visual
        input.css('background-color', '#FFF9C4');
        setTimeout(() => input.css('background-color', ''), 300);
      }
    }
  }
});

// Botón Importar Excel en Modal
$(document).on('click', '#btn-importar-excel-modal', function () {
  $('#input-excel').click();
});

// Input Excel Change
$(document).on('change', '#input-excel', function () {
  if ($(this).val() == '') return;

  const formData = new FormData();
  formData.append('archivo_excel', $(this)[0].files[0]);
  formData.append('_token', $('meta[name="_token"]').attr('content'));

  const id_producido = $('#modalCargaProducidos #id_producido').val();

  $('#excel-status-msg').html('<i class="fa fa-spinner fa-spin"></i> Procesando...');

  $.ajax({
    url: 'producidos/importarExcelConcesionario/' + id_producido,
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function (resp) {
      if (resp.error) {
        alert(resp.error);
        $('#excel-status-msg').html('<i class="fa fa-times" style="color:red;"></i> Error');
      } else {
        console.log('Excel Debug:', resp.debug); // Debug para ver qué está parseando
        console.log('Datos Excel:', resp.datos_excel);

        window.datosExcelRaw = resp.datos_excel;
        $('#excel-status-msg').html('<i class="fa fa-check" style="color:green;"></i> Cargado (' + resp.total_excel + ' registros)');

        // Recargar tabla visualmente si ya hay datos
        const id_producido_act = $('#modalCargaProducidos #id_producido').val();
        cargarTablaDiferencias(id_producido_act);
      }
    },
    error: function () {
      alert('Error de conexión');
      $('#excel-status-msg').html('<i class="fa fa-times" style="color:red;"></i> Error');
    }
  });
});


function renderizarTablaDiferencias(datos) {
  $('#tbody-tabla-diferencias').empty();

  if (datos.length === 0) {
    $('#tbody-tabla-diferencias').append('<tr><td colspan="12" style="text-align:center;">No hay diferencias</td></tr>');
    return;
  }

  datos.forEach(function (item) {
    // Reglas de Tipo de Ajuste por Defecto (Solicitud Usuario)
    // Si CoinIn INI CoinOut INI vienen en cero y CoinIn FIN CoinOut FIN vienen con contadores -> Cambio Iniciales (5)
    // Si CoinIn INI CoinOut INI vienen con contadores y CoinIn FIN CoinOut FIN vienen en cero -> Cambio Finales (3)
    // Si todos cero -> Multiples Ajustes (6)

    let tipoDefecto = 5; // Default general

    const iniCero = (parseFloat(item.coinin_inicio) == 0 && parseFloat(item.coinout_inicio) == 0);
    const finCero = (parseFloat(item.coinin_final) == 0 && parseFloat(item.coinout_final) == 0);

    if (iniCero && !finCero) tipoDefecto = 5;
    else if (!iniCero && finCero) tipoDefecto = 3;
    else if (iniCero && finCero) tipoDefecto = 6;
    else if (item.es_reset) tipoDefecto = 2; // Mantener detección de reset si no son ceros

    let options = '';
    TIPOS_AJUSTE.forEach(t => {
      options += '<option value="' + t.id + '" ' + (t.id == tipoDefecto ? 'selected' : '') + '>' + t.descripcion + '</option>';
    });

    const inputStyle = 'width:90px; padding:2px; height:24px; font-size:11px; text-align:right;';
    const inputStyleDen = 'width:50px; padding:2px; height:24px; font-size:11px; text-align:right;';

    // Tooltips para cada campo
    const tooltips = {
      'den-ini': 'Denominación Inicial: Factor de conversión créditos → pesos al inicio del día',
      'coinin-ini': 'CoinIn Inicial: Total de créditos apostados al inicio del día',
      'coinout-ini': 'CoinOut Inicial: Total de créditos pagados al inicio del día',
      'jack-ini': 'Jackpot Inicial: Acumulado de jackpots al inicio del día',
      'prog-ini': 'Progresivo Inicial: Acumulado de progresivos al inicio del día',
      'den-fin': 'Denominación Final: Factor de conversión créditos → pesos al final del día',
      'coinin-fin': 'CoinIn Final: Total de créditos apostados al final del día',
      'coinout-fin': 'CoinOut Final: Total de créditos pagados al final del día',
      'jack-fin': 'Jackpot Final: Acumulado de jackpots al final del día',
      'prog-fin': 'Progresivo Final: Acumulado de progresivos al final del día'
    };

    // Use type="text" to allow math expressions
    const mkInput = (val, name, style = inputStyle) => {
      const tip = tooltips[name] || name;
      return '<input type="text" class="form-control ip-calc ip-' + name + '" value="' + (val || 0) + '" style="' + style + '" title="' + tip + '">';
    };

    // Fila SISTEMA
    const magicBtnStyle = 'padding:0; width:22px; height:22px; line-height:20px; font-size:10px; border-radius:50%; margin-left:3px; background-color:#FAFAFA; color:#555; border:1px solid #CCC;';

    // Tooltip para la diferencia
    const difTooltip = item.diferencia != 0
      ? 'Diferencia: ' + item.diferencia + ' pesos. Debe ser 0 para guardar.'
      : 'Sin diferencia - Listo para guardar';

    // Calcular sugerencia de denominación (usando Delta Créditos)
    const deltaCredits = (parseFloat(item.coinin_final) - parseFloat(item.coinin_inicio)) - (parseFloat(item.coinout_final) - parseFloat(item.coinout_inicio)) - (parseFloat(item.jackpot_final) - parseFloat(item.jackpot_inicio)) - (parseFloat(item.progresivo_final) - parseFloat(item.progresivo_inicio));
    const sugerencia = obtenerSugerenciaDenominacion(deltaCredits, parseFloat(item.producido), parseFloat(item.denominacion_inicio));
    let btnSugerencia = '';
    if (sugerencia) {
      btnSugerencia = '<button type="button" class="btn btn-xs btn-info btn-sugerencia-den" data-val="' + sugerencia + '" style="margin-left:5px; padding:1px 4px; font-size:10px;" title="Sugerencia: Cambiar denominación a ' + sugerencia + ' para corregir la diferencia"><i class="fa fa-lightbulb-o"></i> ' + sugerencia + '?</button>';
    }

    $('#tbody-tabla-diferencias').append(
      '<tr class="fila-sistema" data-nro="' + item.nro_admin + '" data-id="' + item.id_maquina + '" data-iddetpro="' + item.id_detalle_producido + '" data-iddetini="' + item.id_detalle_contador_inicial + '" data-iddetfin="' + item.id_detalle_contador_final + '" data-prod-importado="' + item.producido + '" data-es-reset="' + (item.es_reset ? 1 : 0) + '">' +
      '<td style="text-align:center;"><input type="checkbox" class="check-fila" title="Seleccionar esta fila"></td>' +
      '<td title="Número Administrativo de la máquina">' + item.nro_admin + '</td>' +
      '<td class="celda-diferencia" style="font-weight:bold; color:' + (item.diferencia != 0 ? 'red' : 'green') + '" title="' + difTooltip + '">' + item.diferencia + '</td>' +
      '<td style="white-space:nowrap;">' + mkInput(item.denominacion_inicio, 'den-ini', inputStyleDen) + btnSugerencia + '</td>' +
      '<td>' + mkInput(item.coinin_inicio, 'coinin-ini') + '</td>' +
      '<td><div style="display:flex; align-items:center;">' + mkInput(item.coinout_inicio, 'coinout-ini') +
      '<div class="btn-group" style="margin-left:2px;">' +
      '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" title="Operaciones matemáticas" style="' + magicBtnStyle + '">' +
      '<i class="fa fa-calculator"></i></button>' +
      '<ul class="dropdown-menu" style="font-size:12px; min-width:200px;">' +
      '<li><a href="javascript:void(0)" class="op-ajuste-auto" style="padding:8px 15px;"><i class="fa fa-magic"></i> Ajuste Automático</a></li>' +
      '<li><a href="javascript:void(0)" class="op-reset" style="padding:8px 15px;"><i class="fa fa-refresh"></i> Reset (INI + FIN)</a></li>' +
      '</ul></div></div></td>' +
      '<td>' + mkInput(item.jackpot_inicio, 'jack-ini') + '</td>' +
      '<td>' + mkInput(item.progresivo_inicio, 'prog-ini') + '</td>' +
      '<td style="white-space:nowrap;">' + mkInput(item.denominacion_final, 'den-fin', inputStyleDen) + '</td>' +
      '<td>' + mkInput(item.coinin_final, 'coinin-fin') + '</td>' +
      '<td>' + mkInput(item.coinout_final, 'coinout-fin') + '</td>' +
      '<td>' + mkInput(item.jackpot_final, 'jack-fin') + '</td>' +
      '<td>' + mkInput(item.progresivo_final, 'prog-fin') + '</td>' +
      '<td><select class="form-control ip-tipo-ajuste" style="width:150px; padding:2px; height:24px; font-size:11px;" title="Tipo de Ajuste: Define qué valores usar para el cálculo. Múltiples Ajustes (6) usa todos los valores del formulario.">' + options + '</select></td>' +
      '<td><button class="btn btn-xs btn-primary btn-guardar-fila" title="Guardar ajuste: Solo funciona si la diferencia es 0"><i class="fa fa-save"></i></button></td>' +
      '</tr>'
    );

    // Fila EXCEL (si existe)
    if (window.datosExcelRaw && window.datosExcelRaw[item.nro_admin]) {
      const exc = window.datosExcelRaw[item.nro_admin];
      // Estilo readonly
      const tdStyle = 'padding:4px; font-size:11px; color:#5D4037;';
      const valStyle = 'text-align:right; font-family:monospace;';

      const mkVal = (v) => '<div style="' + valStyle + '">' + (v || 0) + '</div>';

      // Botón Copiar
      const btnCopiar = '<button class="btn btn-xs btn-warning btn-copiar-excel" title="Usar datos de Excel"><i class="fa fa-level-up"></i> Usar Excel</button>';

      $('#tbody-tabla-diferencias').append(
        '<tr class="fila-excel" style="background-color:#FFF3E0; border-bottom:2px solid #ddd;">' +
        '<td style="text-align:right; font-style:italic;">Excel <i class="fa fa-file-text-o"></i></td>' +
        '<td style="text-align:center;">' + btnCopiar + '</td>' + // En columna diferencia/status? O mejor acciones? Col 2 es Diferencia. Poner botón ahí es raro pero visible. O en Acción.
        // Pongamos el botón en columna 'Acción' (última) y aquí un label 'Importado'
        // Col DenIni (Excel no trae den explicitamente por fila normalmente, asumimos sistema o null)
        '<td style="' + tdStyle + ' text-align:center;">-</td>' +
        '<td style="' + tdStyle + '" data-val="' + exc.coinin_inicio + '">' + mkVal(exc.coinin_inicio) + '</td>' +
        '<td style="' + tdStyle + '" data-val="' + exc.coinout_inicio + '">' + mkVal(exc.coinout_inicio) + '</td>' +
        '<td style="' + tdStyle + '" data-val="' + exc.jackpot_inicio + '">' + mkVal(exc.jackpot_inicio) + '</td>' +
        '<td style="' + tdStyle + '" data-val="' + exc.progresivo_inicio + '">' + mkVal(exc.progresivo_inicio) + '</td>' +
        '<td style="' + tdStyle + ' text-align:center;">-</td>' +
        '<td style="' + tdStyle + '" data-val="' + exc.coinin_final + '">' + mkVal(exc.coinin_final) + '</td>' +
        '<td style="' + tdStyle + '" data-val="' + exc.coinout_final + '">' + mkVal(exc.coinout_final) + '</td>' +
        '<td style="' + tdStyle + '" data-val="' + exc.jackpot_final + '">' + mkVal(exc.jackpot_final) + '</td>' +
        '<td style="' + tdStyle + '" data-val="' + exc.progresivo_final + '">' + mkVal(exc.progresivo_final) + '</td>' +
        '<td style="' + tdStyle + '" colspan="2" style="text-align:center;">' + exc.beneficio + ' (Beneficio)</td>' +
        '</tr>'
      );
    }
  });
}

// Botón Usar Excel (Copiar valores)
$(document).on('click', '.btn-copiar-excel', function () {
  const trExcel = $(this).closest('tr');
  const trSistema = trExcel.prev(); // Asumimos estructura sistema -> excel

  // Copiar valores solo si existen y son diferentes? Copiar TC
  const map = [
    { src: 3, dest: '.ip-coinin-ini' }, // Index 3 es coinini (si no me equivoque indices, mejor buscar por data-val si pudiera, pero use tds)
    // Usar .find('td:eq(index)') es fragil.
    // Mejor añadir data attributes a los TD del excel
  ];

  // Mejor lógica:
  const coinin_ini = trExcel.find('td[data-val]:eq(0)').data('val');
  const coinout_ini = trExcel.find('td[data-val]:eq(1)').data('val');
  const jack_ini = trExcel.find('td[data-val]:eq(2)').data('val');
  const prog_ini = trExcel.find('td[data-val]:eq(3)').data('val');

  const coinin_fin = trExcel.find('td[data-val]:eq(4)').data('val');
  const coinout_fin = trExcel.find('td[data-val]:eq(5)').data('val');
  const jack_fin = trExcel.find('td[data-val]:eq(6)').data('val');
  const prog_fin = trExcel.find('td[data-val]:eq(7)').data('val');

  trSistema.find('.ip-coinin-ini').val(coinin_ini);
  trSistema.find('.ip-coinout-ini').val(coinout_ini);
  trSistema.find('.ip-jack-ini').val(jack_ini);
  trSistema.find('.ip-prog-ini').val(prog_ini);

  trSistema.find('.ip-coinin-fin').val(coinin_fin);
  trSistema.find('.ip-coinout-fin').val(coinout_fin);
  trSistema.find('.ip-jack-fin').val(jack_fin);
  trSistema.find('.ip-prog-fin').val(prog_fin);

  // Disparar recálculo
  trSistema.find('.ip-calc').first().trigger('change');

  // Animación feedback
  trSistema.css('background-color', '#FFF8E1');
  setTimeout(() => trSistema.css('background-color', ''), 500);
});

// Evaluar expresiones matemáticas en inputs al cambiar
$(document).on('change', '.ip-calc', function () {
  let val = $(this).val();
  // Permitir caracteres de operación y números
  if (/^[\d\.\+\-\*\/\(\)\s]+$/.test(val)) {
    try {
      // Reemplazar comas por puntos si las hubiera (aunque el usuario debería usar puntos en inputs de texto si no está localizado)
      // Pero js eval usa puntos.
      val = val.replace(/,/g, '.');
      const res = eval(val);
      if (!isNaN(res) && isFinite(res)) {
        $(this).val(Math.round(res)); // Asumimos enteros para contadores? O decimales? Contadores suelen ser enteros. 
        // Mejor round(2) si es plata? Contadores son enteros o decimales?
        // Denominacion es decimal. Contadores son enteros.
        // Si es denominacion, no redondear.
        if ($(this).hasClass('ip-den-ini') || $(this).hasClass('ip-den-fin')) {
          $(this).val(res);
        } else {
          $(this).val(Math.round(res));
        }
      }
    } catch (e) { }
  }
});

// Botón Mágico Individual en Tabla
$(document).on('click', '.btn-magic-calc', function () {
  const tr = $(this).closest('tr');

  // Obtener valores actuales
  const denIni = parseFloat(tr.find('.ip-den-ini').val()) || 1;
  if (denIni === 0) return;

  // Calcular Diferencia Actual
  calcularDiferenciaFila(tr); // Asegurar actualizado
  const diferencia = parseFloat(tr.find('.celda-diferencia').text());

  if (diferencia === 0) return;

  // *** GUARDAR PARA UNDO antes del cambio ***
  const coinOutInput = tr.find('.ip-coinout-ini');
  guardarParaUndo(coinOutInput[0]);

  // Ajuste = D / den
  // Si Dif > 0, CoinOut debe disminuir. 
  // Formulas backend: CoinOut_New = CoinOut_Old - (Dif / Den).

  const ajuste = Math.round(diferencia / denIni);
  const coinOutActual = parseFloat(coinOutInput.val()) || 0;

  coinOutInput.val(coinOutActual - ajuste).trigger('change');
});

// Recalculo dinámico
$(document).on('change keyup', '.ip-calc', function () {
  const tr = $(this).closest('tr');
  calcularDiferenciaFila(tr);
});

function calcularDiferenciaFila(tr) {
  const getVal = (cls) => parseFloat(tr.find('.ip-' + cls).val()) || 0;

  // Inicio
  const denIni = getVal('den-ini');
  const inIni = getVal('coinin-ini');
  const outIni = getVal('coinout-ini');
  const jackIni = getVal('jack-ini');
  const progIni = getVal('prog-ini');

  // Fin
  const denFin = getVal('den-fin');
  const inFin = getVal('coinin-fin');
  const outFin = getVal('coinout-fin');
  const jackFin = getVal('jack-fin');
  const progFin = getVal('prog-fin');

  // Calculo (todo a plata)
  const netoIni = (inIni - outIni - jackIni - progIni) * denIni;
  const netoFin = (inFin - outFin - jackFin - progFin) * denFin;

  const prodCalculado = netoFin - netoIni;
  const prodImportado = parseFloat(tr.data('prod-importado')) || 0;

  const diferencia = (prodCalculado - prodImportado).toFixed(2);

  // Actualizar sugerencia de denominación dinámicamente
  tr.find('.btn-sugerencia-den').remove();
  tr.find('.btn-sherlock').remove(); // Limpiar sugerencia anterior

  const deltaCredits = (inFin - inIni) - (outFin - outIni) - (jackFin - jackIni) - (progFin - progIni);
  const sugerenciaDen = obtenerSugerenciaDenominacion(deltaCredits, prodImportado, denIni);

  if (parseFloat(diferencia) !== 0) {
    // 1. Sugerencia Denominación
    if (sugerenciaDen) {
      const btn = '<button type="button" class="btn btn-xs btn-info btn-sugerencia-den" data-val="' + sugerenciaDen + '" style="margin-left:5px; padding:1px 4px; font-size:10px;" title="Sugerencia: Cambiar denominación a ' + sugerenciaDen + ' para corregir la diferencia"><i class="fa fa-lightbulb-o"></i> ' + sugerenciaDen + '?</button>';
      tr.find('.ip-den-ini').after(btn);
    }
    // 2. Sherlock Holmes (Heurísticas)
    else {
      const sugerenciaSherlock = investigarDiferencia({
        inIni, outIni, jackIni, progIni, denIni,
        inFin, outFin, jackFin, progFin, denFin,
        prodImportado
      });

      if (sugerenciaSherlock) {
        const btn = '<button type="button" class="btn btn-xs btn-warning btn-sherlock" data-tipo="' + sugerenciaSherlock.tipo + '" style="margin-left:5px; padding:1px 4px; font-size:10px;" title="Investigación: ' + sugerenciaSherlock.msg + '"><i class="fa fa-user-secret"></i> ' + sugerenciaSherlock.msg + '</button>';
        tr.find('.celda-diferencia').append(btn);
      }
    }
  }

  const celdaDif = tr.find('.celda-diferencia');
  celdaDif.contents().filter(function () { return this.nodeType == 3; }).first().replaceWith(diferencia);

  // Si hay status (reset), lo mantenemos? O lo quitamos si se arregla? 
  // Mejor solo mostrar el numero por ahora para que se vea claro
  if (parseFloat(diferencia) === 0) {
    celdaDif.css('color', 'green');

    // Auto-guardar inmediatamente si la diferencia es 0
    const btn = tr.find('.btn-guardar-fila');
    if (!btn.prop('disabled') && !tr.data('auto-saving')) {
      tr.data('auto-saving', true);
      celdaDif.html('0 <i class="fa fa-save" style="color:#4CAF50;" title="Guardando..."></i>');

      // Guardar inmediatamente (sin delay)
      btn.trigger('click');
    }
  } else {
    celdaDif.css('color', 'red');
    tr.data('auto-saving', false);
  }
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
    denominacion_inicio: tr.find('.ip-den-ini').val(),

    coinin_final: tr.find('.ip-coinin-fin').val(),
    coinout_final: tr.find('.ip-coinout-fin').val(),
    jackpot_final: tr.find('.ip-jack-fin').val(),
    progresivo_final: tr.find('.ip-prog-fin').val(),
    denominacion_final: tr.find('.ip-den-fin').val(),

    id_detalle_producido: tr.data('iddetpro'),
    id_detalle_contador_inicial: tr.data('iddetini'),
    id_detalle_contador_final: tr.data('iddetfin'),

    id_tipo_ajuste: tr.find('.ip-tipo-ajuste').val(),
    producido: tr.data('prod-importado'), // Fix: data-prod-importado
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
          // Detectar si el problema es que necesita "Múltiples Ajustes" (tipo 6)
          const tipoActual = tr.find('.ip-tipo-ajuste').val();
          const diferenciaGrande = Math.abs(resp.diferencia) > 1000; // Diferencia muy grande

          if (tipoActual != 6 && diferenciaGrande) {
            // Sugerir y cambiar a Múltiples Ajustes
            let msg = "⚠️ PROBLEMA DETECTADO\n\n";
            msg += "La diferencia (" + resp.diferencia + ") es muy grande.\n";
            msg += "Esto ocurre porque el Tipo Ajuste actual (" + tipoActual + ") usa valores de la BD.\n\n";
            msg += "Se cambiará automáticamente a 'Múltiples Ajustes' (6) que usa los valores del formulario.\n\n";
            msg += "Hacé clic en Guardar nuevamente para aplicar.";

            alert(msg);

            // Cambiar automáticamente a tipo 6
            tr.find('.ip-tipo-ajuste').val(6);
            tr.find('.ip-tipo-ajuste').css('background-color', '#FFF8E1');
            setTimeout(() => tr.find('.ip-tipo-ajuste').css('background-color', ''), 2000);

            btn.prop('disabled', false).html('<i class="fa fa-save"></i>');
            tr.data('auto-saving', false); // Reset flag if manual intervention needed
            return;
          }

          let msg = "Ajuste NO guardado. La diferencia persiste: " + resp.diferencia;
          if (resp.debug) {
            msg += "\n\n--- DEBUG (valores usados en backend) ---";
            msg += "\nTipo Ajuste: " + resp.debug.tipo_ajuste;
            msg += "\nProducido usado: " + resp.debug.producido_usado;
            msg += "\nProducido calculado: " + resp.debug.producido_calculado;
            msg += "\nCoinIn INI: " + resp.debug.coinin_ini + " | CoinOut INI: " + resp.debug.coinout_ini;
            msg += "\nCoinIn FIN: " + resp.debug.coinin_fin + " | CoinOut FIN: " + resp.debug.coinout_fin;
            msg += "\nDen INI: " + resp.debug.den_ini + " | Den FIN: " + resp.debug.den_fin;
            console.log('Debug guardarAjuste:', resp.debug);
          }
          alert(msg);
          tr.data('auto-saving', false); // Important: Reset flag on failure
          btn.prop('disabled', false).html('<i class="fa fa-save"></i>');
        } else {
          // Exito probable (el controller retorna objeto producido si todo OK)
          notificarExitoFila(tr);
        }
      } else {
        notificarExitoFila(tr);
      }
    },
    error: function (xhr) {
      let msg = "Error 422 - Validación fallida\n\n";
      try {
        const response = xhr.responseJSON || JSON.parse(xhr.responseText);
        if (response.errors) {
          // Laravel validation errors format
          Object.keys(response.errors).forEach(function (field) {
            msg += "• " + field + ": " + response.errors[field].join(', ') + "\n";
          });
        } else if (typeof response === 'object') {
          // Flat error format
          Object.keys(response).forEach(function (field) {
            if (Array.isArray(response[field])) {
              msg += "• " + field + ": " + response[field].join(', ') + "\n";
            } else if (typeof response[field] === 'string') {
              msg += "• " + field + ": " + response[field] + "\n";
            }
          });
        } else {
          msg += response;
        }
      } catch (e) {
        msg += xhr.responseText || "Error desconocido";
      }
      console.log('Error guardarAjuste:', xhr);
      alert(msg);
      tr.data('auto-saving', false); // Important: Reset flag on error
      btn.prop('disabled', false).html('<i class="fa fa-save"></i>');
    }
  });
});

function notificarExitoFila(tr) {
  tr.css('background-color', '#C8E6C9');
  tr.find('input, select, button').prop('disabled', true);
  tr.find('.btn-guardar-fila').html('<i class="fa fa-check"></i>');
  tr.find('.celda-diferencia').text('0.00').css('color', 'green');

  // Remover la fila guardada con animación
  tr.find('td').wrapInner('<div style="display:block;"/>');
  tr.find('td > div').slideUp(400, function () {
    tr.remove();

    // Actualizar el contador en el footer
    const filasRestantes = $('#tbody-tabla-diferencias .fila-sistema').length;
    let sumDif = 0;
    $('#tbody-tabla-diferencias .fila-sistema .celda-diferencia').each(function () {
      sumDif += Math.abs(parseFloat($(this).text()) || 0);
    });
    $('#tabla-total-info').text('Total máquinas: ' + filasRestantes + ' | Diferencia acumulada (abs): ' + sumDif.toFixed(2));
  });
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

// Botón FINALIZAR Y VALIDAR PRODUCIDO desde vista tabla
$(document).on('click', '#btn-validar-producido-tabla', function () {
  const btn = $(this);

  // Verificar si todas las diferencias son 0
  let hayDiferencias = false;
  let totalDif = 0;

  $('#tbody-tabla-diferencias .fila-sistema .celda-diferencia').each(function () {
    const dif = parseFloat($(this).text()) || 0;
    if (dif !== 0) {
      hayDiferencias = true;
      totalDif += Math.abs(dif);
    }
  });

  if (hayDiferencias) {
    alert('No se puede finalizar. Aún hay máquinas con diferencias (Total: ' + totalDif.toFixed(2) + ').\n\nPrimero ajustá las diferencias.');
    return;
  }

  // Obtener todas las filas que PUEDEN ser guardadas (botón no deshabilitado)
  const filasParaGuardar = $('#tbody-tabla-diferencias .fila-sistema').filter(function () {
    const guardarBtn = $(this).find('.guardar-fila');
    return guardarBtn.length > 0 && !guardarBtn.prop('disabled');
  });

  if (filasParaGuardar.length > 0) {
    if (!confirm('Hay ' + filasParaGuardar.length + ' fila(s) sin guardar.\n\n¿Deseas GUARDAR TODAS automáticamente y validar?')) {
      return;
    }

    // Guardar todas las filas pendientes
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando ' + filasParaGuardar.length + ' filas...');

    let guardadas = 0;
    let errores = 0;
    let todasAjustadas = false;
    const total = filasParaGuardar.length;
    const resumen = [];

    // Función para guardar una fila
    function guardarFila(tr, callback) {
      const nroAdmin = tr.find('td:first').text();
      const data = {
        id_maquina: tr.data('id'),
        coinin_inicio: tr.find('.ip-coinin-ini').val(),
        coinout_inicio: tr.find('.ip-coinout-ini').val(),
        jackpot_inicio: tr.find('.ip-jack-ini').val(),
        progresivo_inicio: tr.find('.ip-prog-ini').val(),
        denominacion_inicio: tr.find('.ip-den-ini').val(),
        coinin_final: tr.find('.ip-coinin-fin').val(),
        coinout_final: tr.find('.ip-coinout-fin').val(),
        jackpot_final: tr.find('.ip-jack-fin').val(),
        progresivo_final: tr.find('.ip-prog-fin').val(),
        denominacion_final: tr.find('.ip-den-fin').val(),
        id_detalle_producido: tr.data('iddetpro'),
        id_detalle_contador_inicial: tr.data('iddetini'),
        id_detalle_contador_final: tr.data('iddetfin'),
        id_tipo_ajuste: tr.find('.ip-tipo-ajuste').val(),
        producido: tr.data('prod-importado'),
        observacion: 'Ajuste desde tabla (batch)'
      };

      $.ajax({
        type: 'POST',
        url: 'producidos/guardarAjuste',
        data: data,
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
        success: function (resp) {
          if (resp.hay_diferencia) {
            errores++;
            resumen.push({ mtm: nroAdmin, estado: 'ERROR', detalle: 'Diferencia: ' + resp.diferencia });
          } else {
            guardadas++;
            tr.find('.guardar-fila').prop('disabled', true).html('<i class="fa fa-check" style="color:green;"></i>');
            resumen.push({ mtm: nroAdmin, estado: 'OK', detalle: 'Guardado' });
            // Capturar si todas fueron ajustadas
            if (resp.todas_ajustadas == 1) {
              todasAjustadas = true;
            }
          }
          callback();
        },
        error: function (xhr) {
          errores++;
          resumen.push({ mtm: nroAdmin, estado: 'ERROR', detalle: 'Error de conexión' });
          callback();
        }
      });
    }

    // Procesar filas secuencialmente
    let idx = 0;
    function procesarSiguiente() {
      if (idx >= total) {
        // Terminamos - mostrar resumen
        btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> FINALIZAR Y VALIDAR PRODUCIDO');

        // Construir mensaje de resumen
        let msg = '═══════════════════════════════════════\n';
        msg += '           RESUMEN DE VALIDACIÓN\n';
        msg += '═══════════════════════════════════════\n\n';
        msg += '✓ Máquinas guardadas: ' + guardadas + '\n';
        msg += '✗ Con errores: ' + errores + '\n\n';

        if (todasAjustadas) {
          msg += '✅ PRODUCIDO VALIDADO EXITOSAMENTE\n';
          msg += '   El producido pasó al siguiente día.\n';
        } else if (errores == 0 && guardadas > 0) {
          msg += '⚠️ GUARDADO PERO NO VALIDADO\n';
          msg += '   Puede que falten otras máquinas por ajustar\n';
          msg += '   o que no tengan registro en ajuste_producido.\n';
        } else if (errores > 0) {
          msg += '⚠️ HAY ERRORES - REVISAR\n';
          msg += '   Las siguientes máquinas fallaron:\n';
          resumen.filter(r => r.estado == 'ERROR').forEach(r => {
            msg += '   - MTM ' + r.mtm + ': ' + r.detalle + '\n';
          });
        }

        msg += '\n═══════════════════════════════════════';

        alert(msg);

        if (errores == 0) {
          $('#modalTablaCompleta').modal('hide');
          $('#modalCargaProducidos').modal('hide');
          location.reload();
        }
        return;
      }

      btn.html('<i class="fa fa-spinner fa-spin"></i> Guardando ' + (idx + 1) + '/' + total + '...');
      guardarFila($(filasParaGuardar[idx]), function () {
        idx++;
        procesarSiguiente();
      });
    }

    procesarSiguiente();

  } else {
    // Todas ya guardadas, simplemente cerrar
    let msg = '═══════════════════════════════════════\n';
    msg += '           RESUMEN DE VALIDACIÓN\n';
    msg += '═══════════════════════════════════════\n\n';
    msg += '✓ Todas las máquinas ya estaban guardadas.\n\n';
    msg += 'Si el producido no aparece como validado,\n';
    msg += 'puede que falten máquinas en la lista original\n';
    msg += 'de ajuste_producido.\n';
    msg += '\n═══════════════════════════════════════';

    alert(msg);
    $('#modalTablaCompleta').modal('hide');
    $('#modalCargaProducidos').modal('hide');
    location.reload();
  }
});

// === OPERACIONES MATEMÁTICAS PARA AJUSTES ===

// 1. Ajuste Automático: Resta la diferencia del CoinOut INI
$(document).on('click', '.op-ajuste-auto', function (e) {
  e.preventDefault();
  const tr = $(this).closest('tr');
  const celdaDif = tr.find('.celda-diferencia');
  const diferencia = parseFloat(celdaDif.text()) || 0;
  const denIni = parseFloat(tr.find('.ip-den-ini').val()) || 1;

  // Convertir la diferencia a créditos y restarla del CoinOut INI
  const difEnCreditos = diferencia / denIni;
  const coinoutIni = parseFloat(tr.find('.ip-coinout-ini').val()) || 0;
  const nuevoValor = Math.round(coinoutIni - difEnCreditos);

  tr.find('.ip-coinout-ini').val(nuevoValor).trigger('change');
  tr.find('.ip-tipo-ajuste').val(6); // Múltiples Ajustes
});

// 2. Reset (Vuelta de Contadores): Suma valores INI a los FIN
$(document).on('click', '.op-reset', function (e) {
  e.preventDefault();
  const tr = $(this).closest('tr');

  const coininIni = parseFloat(tr.find('.ip-coinin-ini').val()) || 0;
  const coinoutIni = parseFloat(tr.find('.ip-coinout-ini').val()) || 0;
  const jackIni = parseFloat(tr.find('.ip-jack-ini').val()) || 0;
  const progIni = parseFloat(tr.find('.ip-prog-ini').val()) || 0;

  const coininFin = parseFloat(tr.find('.ip-coinin-fin').val()) || 0;
  const coinoutFin = parseFloat(tr.find('.ip-coinout-fin').val()) || 0;
  const jackFin = parseFloat(tr.find('.ip-jack-fin').val()) || 0;
  const progFin = parseFloat(tr.find('.ip-prog-fin').val()) || 0;

  tr.find('.ip-coinin-fin').val(coininFin + coininIni);
  tr.find('.ip-coinout-fin').val(coinoutFin + coinoutIni);
  tr.find('.ip-jack-fin').val(jackFin + jackIni);
  tr.find('.ip-prog-fin').val(progFin + progIni);

  tr.find('.ip-tipo-ajuste').val(2); // Reset de Contadores
  tr.find('.ip-coinin-fin').trigger('change');
});

// 3. Copiar INI a FIN
$(document).on('click', '.op-copiar-ini-fin', function (e) {
  e.preventDefault();
  const tr = $(this).closest('tr');

  tr.find('.ip-coinin-fin').val(tr.find('.ip-coinin-ini').val());
  tr.find('.ip-coinout-fin').val(tr.find('.ip-coinout-ini').val());
  tr.find('.ip-jack-fin').val(tr.find('.ip-jack-ini').val());
  tr.find('.ip-prog-fin').val(tr.find('.ip-prog-ini').val());
  tr.find('.ip-den-fin').val(tr.find('.ip-den-ini').val());

  tr.find('.ip-tipo-ajuste').val(6); // Múltiples Ajustes
  tr.find('.ip-coinin-fin').trigger('change');
});

// 4. Limpiar FIN (poner en 0)
$(document).on('click', '.op-limpiar-fin', function (e) {
  e.preventDefault();
  const tr = $(this).closest('tr');

  tr.find('.ip-coinin-fin').val(0);
  tr.find('.ip-coinout-fin').val(0);
  tr.find('.ip-jack-fin').val(0);
  tr.find('.ip-prog-fin').val(0);

  tr.find('.ip-tipo-ajuste').val(3); // Cambio contadores finales
  tr.find('.ip-coinin-fin').trigger('change');
});

// 5. Limpiar INI (poner en 0)
$(document).on('click', '.op-limpiar-ini', function (e) {
  e.preventDefault();
  const tr = $(this).closest('tr');

  tr.find('.ip-coinin-ini').val(0);
  tr.find('.ip-coinout-ini').val(0);
  tr.find('.ip-jack-ini').val(0);
  tr.find('.ip-prog-ini').val(0);

  tr.find('.ip-tipo-ajuste').val(5); // Cambio contadores iniciales
  tr.find('.ip-coinin-ini').trigger('change');
});

// === EXCEL UPLOAD PARA TABLA (Casino Rosario) ===

window.excelTablaData = null; // Almacena datos del Excel

$('#input-excel-tabla').on('change', function () {
  if ($(this).val() == '') return;

  const formData = new FormData();
  formData.append('archivo_excel', $(this)[0].files[0]);
  formData.append('_token', $('meta[name="_token"]').attr('content'));

  $('#excel-tabla-status').html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
  $('#btn-aplicar-excel').hide();

  $.ajax({
    url: 'producidos/importarExcelTabla',
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function (resp) {
      if (resp.error) {
        $('#excel-tabla-status').html('<i class="fa fa-times" style="color:red;"></i> ' + resp.error);
      } else {
        window.excelTablaData = resp.datos;
        window.excelFechaRaw = resp.fecha_raw;
        let statusMsg = '<i class="fa fa-check" style="color:green;"></i> ' + resp.total + ' registros';
        if (resp.fecha_excel) {
          statusMsg += ' | Fecha: <strong>' + resp.fecha_raw + '</strong>';
        }
        $('#excel-tabla-status').html(statusMsg);
        $('#btn-aplicar-excel').show();
        console.log('Datos Excel cargados:', resp.datos);

        // Mostrar filas de comparación del CSV
        renderizarFilasComparativaCSV();
      }
    },
    error: function (xhr) {
      const msg = xhr.responseJSON?.error || 'Error de conexión';
      $('#excel-tabla-status').html('<i class="fa fa-times" style="color:red;"></i> ' + msg);
    }
  });

  // Reset input para permitir cargar mismo archivo
  $(this).val('');
});

// Aplicar datos del Excel a filas con valores en 0
$('#btn-aplicar-excel').on('click', function () {
  if (!window.excelTablaData) {
    alert('No hay datos de Excel cargados');
    return;
  }

  let aplicados = 0;
  let noEncontrados = 0;

  $('#tbody-tabla-diferencias .fila-sistema').each(function () {
    const tr = $(this);
    const nroAdmin = tr.data('nro');

    // Buscar en Excel (el MTM puede tener formato diferente)
    let excelRow = window.excelTablaData[nroAdmin];

    if (!excelRow) {
      noEncontrados++;
      return; // continue
    }

    // Verificar si los valores difieren entre sistema y Excel
    const coininIni = parseFloat(tr.find('.ip-coinin-ini').val()) || 0;
    const coinoutIni = parseFloat(tr.find('.ip-coinout-ini').val()) || 0;
    const coininFin = parseFloat(tr.find('.ip-coinin-fin').val()) || 0;
    const coinoutFin = parseFloat(tr.find('.ip-coinout-fin').val()) || 0;

    let seAplico = false;

    // Aplicar INI del Excel si son diferentes
    if (coininIni != excelRow.coinin_inicio || coinoutIni != excelRow.coinout_inicio) {
      tr.find('.ip-coinin-ini').val(excelRow.coinin_inicio);
      tr.find('.ip-coinout-ini').val(excelRow.coinout_inicio);
      tr.find('.ip-jack-ini').val(excelRow.jackpot_inicio);
      tr.find('.ip-prog-ini').val(excelRow.progresivo_inicio);
      seAplico = true;
    }

    // Aplicar FIN del Excel si son diferentes
    if (coininFin != excelRow.coinin_final || coinoutFin != excelRow.coinout_final) {
      tr.find('.ip-coinin-fin').val(excelRow.coinin_final);
      tr.find('.ip-coinout-fin').val(excelRow.coinout_final);
      tr.find('.ip-jack-fin').val(excelRow.jackpot_final);
      tr.find('.ip-prog-fin').val(excelRow.progresivo_final);
      seAplico = true;
    }

    if (seAplico) {
      aplicados++;
      tr.find('.ip-tipo-ajuste').val(6); // Múltiples Ajustes
      tr.css('background-color', '#E3F2FD');
      tr.find('.ip-coinin-ini').trigger('change');
    }
  });

  let msg = 'Excel aplicado:\n';
  msg += '✓ ' + aplicados + ' filas actualizadas\n';
  if (noEncontrados > 0) {
    msg += '⚠ ' + noEncontrados + ' MTMs no encontradas en Excel';
  }

  alert(msg);

  // Quitar filas de comparación después de aplicar
  $('.fila-csv-comparativa').remove();
});

// Función para renderizar filas de comparación del CSV debajo de cada fila del sistema
function renderizarFilasComparativaCSV() {
  // Primero quitar filas de comparación anteriores
  $('.fila-csv-comparativa').remove();

  if (!window.excelTablaData) return;

  let diferencias = 0;

  $('#tbody-tabla-diferencias .fila-sistema').each(function () {
    const trSistema = $(this);
    const nroAdmin = trSistema.data('nro');
    const excelRow = window.excelTablaData[nroAdmin];

    if (!excelRow) return; // No hay datos del CSV para esta MTM

    // Obtener valores del sistema
    const sysCoininIni = parseFloat(trSistema.find('.ip-coinin-ini').val()) || 0;
    const sysCoinoutIni = parseFloat(trSistema.find('.ip-coinout-ini').val()) || 0;
    const sysCoininFin = parseFloat(trSistema.find('.ip-coinin-fin').val()) || 0;
    const sysCoinoutFin = parseFloat(trSistema.find('.ip-coinout-fin').val()) || 0;

    // Verificar si hay diferencias
    const hayDifIni = sysCoininIni != excelRow.coinin_inicio || sysCoinoutIni != excelRow.coinout_inicio;
    const hayDifFin = sysCoininFin != excelRow.coinin_final || sysCoinoutFin != excelRow.coinout_final;

    if (!hayDifIni && !hayDifFin) return; // No hay diferencias

    diferencias++;

    // Crear fila de comparación (CSV)
    const mkCell = function (sysVal, csvVal) {
      if (sysVal == csvVal) {
        return '<td style="background:#E8F5E9; color:#666; font-size:10px; padding:1px 3px;">=' + csvVal + '</td>';
      } else {
        return '<td style="background:#FFEBEE; color:#C62828; font-size:10px; padding:1px 3px; font-weight:bold;">' + csvVal + '</td>';
      }
    };

    const filaCSV = $('<tr class="fila-csv-comparativa" style="background:#FFF3E0;">' +
      '<td></td>' + // Checkbox column (empty)
      '<td style="padding:1px 5px; font-size:10px; color:#E65100;"><i class="fa fa-file-text-o"></i> ' + nroAdmin + ' (CSV)</td>' +
      '<td style="padding:1px 3px; font-size:10px; color:#888;">' + (excelRow.beneficio || '-') + '</td>' + // Diferencia column (showing beneficio here or just diff?) actually user wants CSV values.
      '<td style="padding:1px 3px; font-size:10px; color:#888;">-</td>' + // Den INI
      mkCell(sysCoininIni, excelRow.coinin_inicio) + // CoinIn INI
      mkCell(sysCoinoutIni, excelRow.coinout_inicio) + // CoinOut INI
      '<td style="font-size:10px; padding:1px 3px;">' + (excelRow.jackpot_inicio || 0) + '</td>' + // Jack INI
      '<td style="font-size:10px; padding:1px 3px;">' + (excelRow.progresivo_inicio || 0) + '</td>' + // Prog INI
      '<td style="padding:1px 3px; font-size:10px; color:#888;">-</td>' + // Den FIN
      mkCell(sysCoininFin, excelRow.coinin_final) + // CoinIn FIN
      mkCell(sysCoinoutFin, excelRow.coinout_final) + // CoinOut FIN
      '<td style="font-size:10px; padding:1px 3px;">' + (excelRow.jackpot_final || 0) + '</td>' + // Jack FIN
      '<td style="font-size:10px; padding:1px 3px;">' + (excelRow.progresivo_final || 0) + '</td>' + // Prog FIN
      '<td colspan="2" style="font-size:10px; padding:1px 3px; color:#E65100;">Beneficio: ' + excelRow.beneficio + '</td>' +
      '</tr>');

    trSistema.after(filaCSV);
  });

  if (diferencias > 0) {
    $('#excel-tabla-status').append(' | <span style="color:#E65100;"><i class="fa fa-exchange"></i> ' + diferencias + ' diferencias</span>');
    $('#dash-csv-dif').text(diferencias);
  }

  // Validar fecha del CSV vs fecha del producido
  validarFechaCSV();
}

// ==========================================
// === DASHBOARD, FILTROS, SELECCIÓN MÚLTIPLE
// ==========================================

// Actualizar dashboard
function actualizarDashboard() {
  const filas = $('#tbody-tabla-diferencias .fila-sistema');
  const total = filas.length;
  let enCero = 0;
  let pendientes = 0;

  filas.each(function () {
    const dif = parseFloat($(this).find('.celda-diferencia').text()) || 0;
    if (dif === 0) {
      enCero++;
    } else {
      pendientes++;
    }
  });

  $('#dash-total').text(total);
  $('#dash-en-cero').text(enCero);
  $('#dash-pendientes').text(pendientes);
}

// Llamar actualizarDashboard después de renderizar tabla
$(document).on('DOMNodeInserted', '#tbody-tabla-diferencias', function () {
  setTimeout(actualizarDashboard, 100);
});

// Filtros rápidos
$('input[name="filtro"]').on('change', function () {
  const filtro = $(this).val();

  $('#tbody-tabla-diferencias .fila-sistema').each(function () {
    const tr = $(this);
    const dif = parseFloat(tr.find('.celda-diferencia').text()) || 0;
    const esReset = tr.data('es-reset') == 1;

    let mostrar = true;

    if (filtro === 'con-dif' && dif === 0) mostrar = false;
    if (filtro === 'en-cero' && dif !== 0) mostrar = false;
    if (filtro === 'reset' && !esReset) mostrar = false;

    tr.toggle(mostrar);
    // También ocultar fila CSV comparativa si existe
    tr.next('.fila-csv-comparativa').toggle(mostrar);
  });
});

// Checkbox seleccionar todas
$('#check-todas-filas').on('change', function () {
  const checked = $(this).prop('checked');
  $('#tbody-tabla-diferencias .fila-sistema:visible .check-fila').prop('checked', checked);
  actualizarContadorSeleccion();
});

$('#btn-seleccionar-todas').on('click', function () {
  $('#tbody-tabla-diferencias .fila-sistema:visible .check-fila').prop('checked', true);
  actualizarContadorSeleccion();
});

$('#btn-deseleccionar').on('click', function () {
  $('#tbody-tabla-diferencias .fila-sistema .check-fila').prop('checked', false);
  actualizarContadorSeleccion();
});

// Actualizar contador de selección
$(document).on('change', '.check-fila', function () {
  actualizarContadorSeleccion();
});

function actualizarContadorSeleccion() {
  const seleccionadas = $('#tbody-tabla-diferencias .check-fila:checked').length;
  $('#contador-seleccion').text(seleccionadas + ' selec.');

  // Mostrar/ocultar barra de operaciones en lote
  if (seleccionadas > 0) {
    $('#barra-operaciones-lote').css('display', 'flex');
  } else {
    $('#barra-operaciones-lote').hide();
  }
}

// Operaciones en lote: Ajuste Automático
$('#btn-lote-ajuste-auto').on('click', function () {
  const seleccionadas = $('#tbody-tabla-diferencias .fila-sistema:has(.check-fila:checked)');
  if (seleccionadas.length === 0) return;

  seleccionadas.each(function () {
    const tr = $(this);
    tr.find('.op-ajuste-auto').trigger('click');
  });

  alert('Ajuste Automático aplicado a ' + seleccionadas.length + ' filas');
});

// Operaciones en lote: Reset
$('#btn-lote-reset').on('click', function () {
  const seleccionadas = $('#tbody-tabla-diferencias .fila-sistema:has(.check-fila:checked)');
  if (seleccionadas.length === 0) return;

  seleccionadas.each(function () {
    const tr = $(this);
    tr.find('.op-reset').trigger('click');
  });

  alert('Reset aplicado a ' + seleccionadas.length + ' filas');
});

// Operaciones en lote: Guardar (con barra de progreso)
$('#btn-lote-guardar').on('click', function () {
  const seleccionadas = $('#tbody-tabla-diferencias .fila-sistema:has(.check-fila:checked)').filter(function () {
    const dif = parseFloat($(this).find('.celda-diferencia').text()) || 0;
    return dif === 0;
  });

  if (seleccionadas.length === 0) {
    alert('No hay filas seleccionadas con diferencia 0 para guardar');
    return;
  }

  if (!confirm('¿Guardar ' + seleccionadas.length + ' filas con diferencia 0?')) return;

  guardarFilasConProgreso(seleccionadas);
});

// Guardar filas con barra de progreso
function guardarFilasConProgreso(filas) {
  const total = filas.length;
  let completadas = 0;
  let errores = 0;

  $('#barra-progreso-container').show();
  $('#progreso-texto').text('Guardando...');
  $('#progreso-contador').text('0/' + total);
  $('#progreso-barra').css('width', '0%');

  // Procesar secuencialmente
  let index = 0;

  function procesarSiguiente() {
    if (index >= total) {
      // Completado
      $('#progreso-texto').text('Completado');
      setTimeout(function () {
        $('#barra-progreso-container').hide();
        actualizarDashboard();
        alert('Guardado completado: ' + completadas + ' exitosas, ' + errores + ' errores');
      }, 500);
      return;
    }

    const tr = $(filas[index]);
    const btn = tr.find('.btn-guardar-fila');

    // Deshabilitar auto-save mientras guaramos en lote
    tr.data('batch-saving', true);
    tr.data('auto-saving', false); // Force clear stuck state (fix bug)

    // Simular click en guardar
    btn.trigger('click');

    // Esperar un poco y pasar a la siguiente
    setTimeout(function () {
      completadas++;
      index++;
      const pct = Math.round((index / total) * 100);
      $('#progreso-contador').text(index + '/' + total);
      $('#progreso-barra').css('width', pct + '%');
      procesarSiguiente();
    }, 300);
  }

  procesarSiguiente();
}

// Validar fecha del CSV vs producido
function validarFechaCSV() {
  if (!window.excelFechaRaw) return;

  const fechaProducido = $('#titulo-fecha-producido').text();
  const fechaCSV = window.excelFechaRaw;

  // Extraer fecha del título (formato: 2026-01-03 - CityCenter)
  const matchProd = fechaProducido.match(/(\d{4})-(\d{2})-(\d{2})/);
  // Extraer fecha del CSV (formato: Jornada Casino: 3/1/2026)
  const matchCSV = fechaCSV.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);

  if (matchProd && matchCSV) {
    const fechaProdStr = matchProd[1] + '-' + matchProd[2] + '-' + matchProd[3];
    const fechaCSVStr = matchCSV[3] + '-' + matchCSV[2].padStart(2, '0') + '-' + matchCSV[1].padStart(2, '0');

    if (fechaProdStr !== fechaCSVStr) {
      $('#alerta-fecha-csv').show();
      $('#alerta-fecha-texto').html(' <strong>Fechas no coinciden:</strong> El CSV es del <strong>' + matchCSV[1] + '/' + matchCSV[2] + '/' + matchCSV[3] + '</strong> pero el producido es del <strong>' + matchProd[3] + '/' + matchProd[2] + '/' + matchProd[1] + '</strong>');
    } else {
      $('#alerta-fecha-csv').hide();
    }
  }
}

// ==========================================
// === SUGERENCIAS INTELIGENTES Y SYNC
// ==========================================

// Sincronizar al cerrar el modal de tabla
$('#modalTablaCompleta').on('hidden.bs.modal', function () {
  const id_producido = $('#modalCargaProducidos #id_producido').val();
  // Recargar la lista de máquinas del modal individual (si está abierto)
  if ($('#modalCargaProducidos').hasClass('in')) {
    recargarListaMaquinas(id_producido);
  }
});

// Lógica de sugerencia de denominación (calculada dinámicamente)
function obtenerSugerenciaDenominacion(creditosNetos, producidoImportado, denActual) {
  if (creditosNetos == 0) return null;

  // Denominaciones comunes en casinos
  const denomsComunes = [0.01, 0.05, 0.10, 0.25, 0.50, 1.00, 2.00, 5.00, 10.00, 20.00, 50.00, 100.00];

  // Calcular la denominación necesaria para que la diferencia sea 0
  // Producido = Creditos * Denom  =>  Denom = Producido / Creditos
  const targetDenom = Math.abs(producidoImportado / creditosNetos);

  // Verificar si es una denominación válida (con tolerancia por decimales)
  const match = denomsComunes.find(d => Math.abs(d - targetDenom) < 0.0001);

  if (match && match != denActual) {
    return match;
  }
  return null;
}

// Botón de sugerencia
$(document).on('click', '.btn-sugerencia-den', function () {
  const val = $(this).data('val');
  const tr = $(this).closest('tr');

  // Aplicar a INI y FIN
  tr.find('.ip-den-ini').val(val);
  tr.find('.ip-den-fin').val(val);

  // Visual feedback
  $(this).html('<i class="fa fa-check"></i> Aplicado').removeClass('btn-info').addClass('btn-success');

  // Recalcular
  calcularDiferenciaFila(tr);
});

// ==========================================
// === MODO SHERLOCK HOLMES (Heurísticas)
// ==========================================

function investigarDiferencia(params) {
  const { inIni, outIni, jackIni, progIni, denIni, inFin, outFin, jackFin, progFin, denFin, prodImportado } = params;

  // Helper para calcular producido 
  const calc = (iI, oI, jI, pI, dI, iF, oF, jF, pF, dF) => {
    const ini = (iI - oI - jI - pI) * dI;
    const fin = (iF - oF - jF - pF) * dF;
    return (fin - ini); // Retorna producido calculado
  };

  const diffOriginal = Math.abs(calc(inIni, outIni, jackIni, progIni, denIni, inFin, outFin, jackFin, progFin, denFin) - prodImportado);

  const tolerance = 1.0; // Tolerancia de $1
  const sugerencias = [];

  // 1. Inversión CoinIn / CoinOut (Inicio)
  if (Math.abs(calc(outIni, inIni, jackIni, progIni, denIni, inFin, outFin, jackFin, progFin, denFin) - prodImportado) < tolerance) {
    sugerencias.push({ tipo: 'swap_ini', msg: 'Invertir CoinIn/Out Inicial' });
  }

  // 2. Inversión CoinIn / CoinOut (Final)
  if (Math.abs(calc(inIni, outIni, jackIni, progIni, denIni, outFin, inFin, jackFin, progFin, denFin) - prodImportado) < tolerance) {
    sugerencias.push({ tipo: 'swap_fin', msg: 'Invertir CoinIn/Out Final' });
  }

  // 3. Posible vuelta de contador no detectada (si Fin < Ini)
  // Chequeo simple si agregando 100...000 al final cierra. 
  // No implementado complejo aquí por riesgo, pero si simple.

  return sugerencias.length > 0 ? sugerencias[0] : null;
}

// Handler para botón Sherlock (se crea en calcularDiferenciaFila)
$(document).on('click', '.btn-sherlock', function () {
  const tr = $(this).closest('tr');
  const tipo = $(this).data('tipo');

  if (tipo === 'swap_ini') {
    const v1 = tr.find('.ip-coinin-ini').val();
    const v2 = tr.find('.ip-coinout-ini').val();
    tr.find('.ip-coinin-ini').val(v2);
    tr.find('.ip-coinout-ini').val(v1);
  } else if (tipo === 'swap_fin') {
    const v1 = tr.find('.ip-coinin-fin').val();
    const v2 = tr.find('.ip-coinout-fin').val();
    tr.find('.ip-coinin-fin').val(v2);
    tr.find('.ip-coinout-fin').val(v1);
  }

  // Feedback
  $(this).html('<i class="fa fa-magic"></i> Corregido').removeClass('btn-warning').addClass('btn-success');
  calcularDiferenciaFila(tr);
});


// Actualizar dashboard cuando se recalcula diferencia
$(document).on('change', '#tbody-tabla-diferencias .ip-calc', function () {
  setTimeout(actualizarDashboard, 200);
});
