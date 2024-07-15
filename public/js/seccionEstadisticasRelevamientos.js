$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Estadísticas de relevamientos');
  $('#b_fecha_desde,#b_fecha_hasta').datetimepicker({
    language: 'es',
    todayBtn: 1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd / mm / yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });
  habilitarDTPPedido();
  $('#btn-buscarMaquina').popover({ html: true });
  $('#fecha_desde').popover({ html: true });
  $('#B_fecha_inicio_m').popover({ html: true });
  $('#b_casinoMaquina').change();
  $('#b_casino').change();
});

//Filtra datalist segun lo que tipea
//Se hace asi pq sino matchea en el medio del string
//Y no le gustaba al usuario 
//i.e input='asd' -> recomendaba ('pepeasd','asd1','holaasd')
//Ahora seria input='asd' -> recomendar ('asd1')
function actualizarBusqueda(str) {
  $('#b_adminMaquina').attr('list', '');
  $('#maquinas_lista_sub').empty();
  const valores = $('#maquinas_lista').find('option');
  for (let i = 0; i < valores.length; i++) {
    const opt = $(valores[i]);
    if (opt.val().substr(0, str.length) === str) {
      $('#maquinas_lista_sub').append(opt.clone());
    }
  }
  $('#b_adminMaquina').attr('list', 'maquinas_lista_sub');
  $('#b_adminMaquina').focus();
}
$('#b_adminMaquina').on('input', function() {
  actualizarBusqueda($('#b_adminMaquina').val());
})

$('#b_casinoMaquina').change(function() {
  let id_casino = $('#b_casinoMaquina').val();
  if (id_casino.length == 0) id_casino = 0;

  function callbackMaquinas(resultados) {
    let maquinas_lista = $('#maquinas_lista');
    maquinas_lista.empty();
    let option = $('<option></option>');
    for (var i = 0; i < resultados.length; i++) {
      let fila = option.clone();
      if (id_casino == 0) {
        fila.val(resultados[i].nro_admin + resultados[i].codigo);
      } else {
        fila.val(resultados[i].nro_admin);
      }

      fila.attr('data-id', resultados[i].id_maquina);
      fila.attr('data-casino', resultados[i].id_casino);
      fila.attr('data-nro_admin', resultados[i].nro_admin);
      maquinas_lista.append(fila);
    };
    actualizarBusqueda($('#b_adminMaquina').val());
  }

  $.ajax({
    type: 'GET',
    url: 'estadisticas_relevamientos/buscarMaquinas/' + id_casino,
    success: callbackMaquinas,
    error: function(data) {
      console.log(data)
    }
  });
});

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function() {
  if ($(this).data("minimizar") == true) {
    $('.modal-backdrop').css('opacity', '0.1');
    $(this).data("minimizar", false);
  } else {
    $('.modal-backdrop').css('opacity', '0.5');
    $(this).data("minimizar", true);
  }
});
//Opacidad del modal al minimizar
$('#btn-minimizarDetalle').click(function() {
  if ($(this).data("minimizar") == true) {
    $('.modal-backdrop').css('opacity', '0.1');
    $(this).data("minimizar", false);
  } else {
    $('.modal-backdrop').css('opacity', '0.5');
    $(this).data("minimizar", true);
  }
});

$('#seccionBusquedaPorMaquina input ,#seccionBusquedaPorMaquina select').on('focusin', function() {
  $('#btn-buscarMaquina').popover('hide');
})

$('input , button').on('focusin', function() {
  $(this).popover('hide');
})

$('#seccionBusquedaPorMaquina input').on('focusin', function() {
  $('#btn-buscarMaquina').popover('hide');
})

$('#b_casino').on('change', function() {
  const id_casino = $('#b_casino').val();
  
  const select = $('#busqueda_sector');
  select.empty();
  select.append($('<option>').val('').text('Todos los sectores'));
  
  if(id_casino.length == 0) return;
  
  $.get("estadisticas_relevamientos/obtenerSectoresPorCasino/" + id_casino, function(data) {
    data.sectores.forEach(function(s){
      select.append($('<option>').val(s.id_sector).text(s.descripcion));
    });
  });
});

//Filtro de busqueda
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden) {
  e.preventDefault();

  var page_size = (page_size != null) ? page_size : 10;
  var page_number = (pagina != null) ? pagina : 1;
  var sort_by = (columna != null) ? { columna, orden } : null;
  if (sort_by == null) { // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
  }

  const formData = {
    id_casino: $('#b_casino').val(),
    id_sector: $('#busqueda_sector').val(),
    nro_isla: $('#b_isla').val(),
    fecha_desde: $('#fecha_desde_date').val(),
    fecha_hasta: $('#fecha_hasta_date').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  };
  
  $('#cuerpoTabla tr').remove();
  
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'estadisticas_relevamientos/buscarMaquinasSinRelevamientos',
    data: formData,
    dataType: 'json',
    success: function(resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number, page_size, resultados.total, clickIndice);
      
      resultados.data.forEach(function(d){
        const fila = $('<tr>')
        .append($('<td>').addClass('col-xs-3').text(d.casino))
        .append($('<td>').addClass('col-xs-2').text(d.sector))
        .append($('<td>').addClass('col-xs-2').text(d.isla))
        .append($('<td>').addClass('col-xs-2').text(d.maquina))
        .append($('<td>').addClass('col-xs-3').append(
          $('<button>').addClass('btn btn-success pedido').val(d.id_maquina).append($('<i>').addClass('fa fa-tag'))
        ));
        $('#cuerpoTabla').append(fila);
      });
      
      $('#herramientasPaginacion').generarIndices(page_number, page_size, resultados.total, clickIndice);
    },
    error: function(data) {
      const response = data.responseJSON ?? {};

      if (response.id_casino !== undefined) {
        mostrarErrorValidacion($('#b_casino'),response.id_casino.join(', '),true);
      }
      if (response.fecha_desde !== undefined) {
        mostrarErrorValidacion($('#fecha_desde'),response.fecha_desde.join(', '),true);
      }
    }
  });
});

//Busqueda de máquina
$('#btn-buscarMaquina').click(function(e) {
  e.preventDefault();
  
  let codigo = $('#b_adminMaquina').val();
  let id_maquina = parseInt($('#maquinas_lista').find('option[value="' + codigo + '"]').attr('data-id'));
  let cantidad_relevamientos = parseInt($('#b_cantidad_relevamientos').val());
  if (isNaN(cantidad_relevamientos) ||
      cantidad_relevamientos <= 0 ||
      isNaN(id_maquina)) {
      //Ignorar
      return;
  }
  
  const tomado = $('#b_tomado').val() == "" ? null : $('#b_tomado').val();
  const diferencia = $('#b_diferencia').val() == "" ? null : $('#b_diferencia').val();

  const formData = {
    id_maquina: id_maquina,
    cantidad_relevamientos: cantidad_relevamientos,
  };
  if (tomado != null) formData.tomado = tomado;
  if (diferencia != null) formData.diferencia = diferencia;
  $('#tablaRelevamientos tbody tr').remove();
  
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
      type: 'POST',
      url: 'estadisticas_relevamientos/obtenerUltimosRelevamientosPorMaquina',
      data: formData,
      dataType: 'json',
      success: function(data) {
        $('#casinoDetalle').val(data.maquina.casino);
        $('#sectorDetalle').val(data.maquina.sector);
        $('#islaDetalle').val(data.maquina.isla);
        $('#adminDetalle').val(data.maquina.nro_admin);
        
        // se cambia pora no recalcular, el relevamiento ya se hizo, por lo que se traen los valores calculados en su momento, sin alteraciones 
        data.detalles.forEach(function(d){
          const fila = $('<tr>')
          .append($('<td>').text(d.fecha))
          .append($('<td>').text(d.cont1 ?? '-'))
          .append($('<td>').text(d.cont2 ?? '-'))
          .append($('<td>').text(d.cont3 ?? '-'))
          .append($('<td>').text(d.cont4 ?? '-'))
          .append($('<td>').text(d.cont5 ?? '-'))
          .append($('<td>').text(d.cont6 ?? '-'))
          .append($('<td>').text(d.cont7 ?? '-'))
          .append($('<td>').text(d.cont8 ?? '-'))
          .append($('<td>').text(d.coinin ?? '-'))
          .append($('<td>').text(d.coinout ?? '-'))
          .append($('<td>').text(d.jackpot ?? '-'))
          .append($('<td>').text(d.progresivo ?? '-'));

          fila.append($('<td>').text(d.tipos_causa_no_toma ?? d.producido_calculado_relevado ?? '-'));
          fila.toggleClass('no_tomado',d.tipos_causa_no_toma != null);

          fila.append($('<td>').text(d.producido_importado ?? '-'));
          fila.append($('<td>').text(d.diferencia ?? '-'));

          $('#tablaRelevamientos tbody').append(fila);
        });

        $('#modalDetalle').modal('show');
      },
      error: function(data) {
        const response = data.responseJSON ?? {};

        if (typeof response.id_casino !== 'undefined' || typeof response.nro_admin !== 'undefined' || typeof response.cantidad_relevamientos !== 'undefined') {
          $('#btn-buscarMaquina').popover('show');
          $('.popover').addClass('popAlerta');
        }
      }
  });
});

function habilitarDTPPedido() {
    $('#dtpFechaFin_m').datetimepicker({
      language: 'es',
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'yyyy-mm-dd',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2,
      minDate: 1,
      startDate: new Date()
    });

    const mañana = new Date((new Date()).getTime() + (24 * 60 * 60 * 1000));

    $('#B_fecha_inicio_m').val(dateToString(mañana));
    $('#dtpFechaFin_m').datetimepicker("setDate", mañana);
}

function clickIndice(e, pageNumber, tam) {
  if (e != null) {
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#tituloTabla').getPageSize();
  const columna = $('#tablaResultados .activa').attr('value');
  const orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

$(document).on('click', '#tablaResultados thead tr th[value]', function(e) {
    $('#tablaResultados th').removeClass('activa');
    if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado', 'desc');
    } else {
      if ($(e.currentTarget).children('i').hasClass('fa-sort-down')) {
        $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado', 'asc');
      } else {
        $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado', '');
      }
    }
    $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado', '');
    clickIndice(e, $('#herramientasPaginacion').getCurrentPage(), $('#herramientasPaginacion').getPageSize());
});

function dateToString(date) {
  const to_2dig = function(d) { return (d > 9? '' : '0')+d; };
  return date.getFullYear()+'-'+to_2dig(date.getMonth())+'-'+to_2dig(date.getDate());
}

//Modal para pedir máquina
$(document).on('click', '.pedido', function(e) {
  $('#modalPedido #frmPedido').trigger('reset');
  $('#modalPedido #id_maquina').val($(this).val());

  const id_maquina = $('#modalPedido #id_maquina').val();
  habilitarDTPPedido();

  $.get('estadisticas_relevamientos/obtenerFechasMtmAPedido/' + id_maquina, function(data) {
    $('#nro_admin_pedido').val(data.maquina.nro_admin).attr('data-maquina', data.maquina.id_maquina).prop('readonly', true);
    $('#casino_pedido').val(data.casino.nombre).attr('data-casino', data.casino.id_casino).prop('readonly', true);

    $('#modalPedido #fechasPedido tbody tr').remove();
    $('#modalPedido #fechasPedido').toggle(data.fechas.length > 0);
    data.fechas.forEach(function(f){
      $('#modalPedido #fechasPedido tbody').append($('<tr>').append($('<td>').text(f.fecha)));
    });
    
    $('#modalPedido').modal('show');
  });
});

$(document).on('click', '.detalle', function(e) {
  $('#modalDetalle').modal('show');
});

//Pedir máquina
$('#btn-pedido').click(function(e) {
  e.preventDefault();

  const formData = {
    nro_admin: $('#nro_admin_pedido').val(),
    casino: $('#casino_pedido').attr('data-casino'),
    fecha_inicio: $('#B_fecha_inicio_m').val(),
    fecha_fin: $('#B_fecha_fin_m').val(),
  };

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
      type: 'POST',
      url: 'estadisticas_relevamientos/guardarMtmAPedido',
      data: formData,
      dataType: 'json',
      success: function(data) {
          console.log('Pedido: ', data);
          let mañana = new Date();
          const hoy = new Date();
          mañana.setDate(mañana.getDate() + 1);
          mañana.setHours(0, 0, 0, 0);
          const fecha_retorno = new Date(data.fecha.split('-').join('/'));

          console.log('Hoy: ', hoy, 'Mañana: ', mañana, 'Devuelta: ', fecha_retorno);
          for (var f = mañana; f <= fecha_retorno; f.setDate(f.getDate() + 1)) {
              var fila = $('<tr>').append($('<td>').text(dateToString(f)));
              console.log('Agregando ' + f.toString());
              $('#modalPedido #fechasPedido tbody').append(fila);
              $('#modalPedido #fechasPedido').show();
          }
      },
      error: function(data) {
          var response = JSON.parse(data.responseText);

          if (typeof response.fecha_inicio !== 'undefined') {
              $('#B_fecha_inicio_m').popover('show');
              $('.popover').addClass('popAlerta');
          }

      },
  });
});
