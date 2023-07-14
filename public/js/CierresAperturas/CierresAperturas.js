function mensajeExito(mensaje=''){
  $('#mensajeExito p').text(mensaje);
  $('#mensajeExito').show();
}
function mensajeError(mensaje=''){
  $('#mensajeError p').text(mensaje);
  $('#mensajeError').show();
}

$(document).ready(function() {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $('.tituloSeccionPantalla').text('Cierres y Aperturas');
  $('[data-toggle="popover"]').popover();

  $('.filtroFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
  });

  $('#cierreApertura').show().find('li').eq(0).click();
  $('#modal-CargarCierreApertura').trigger('hidden.bs.modal');//limpiar el modal de carga
  $('#modalValidarApertura').trigger('hidden.bs.modal');
});

//PESTAÑAS
$("#cierreApertura li").click(function() {
  $("#cierreApertura li").removeClass("active");
  $(this).addClass("active");
  $(".tab_content").hide();

  const tab = $($(this).find("a").attr("href")); //Find the href attribute value to
  tab.find('.form-control').val('');//Limpio los filtros
  tab.find('.btn-buscar').click();
  tab.fadeIn();//La muestro
});

//BUSCAR cierres
$('.btn-buscar').on('click', function(e,pagina,page_size,columna,orden){
  e.preventDefault();
  const tab = $(this).closest('.tab_content');
  tab.find('.tablaResultados tbody tr').remove();
  
  //Fix error cuando librería saca los selectores
  let size = tab.find('.herramientasPaginacion').getPageSize();
  if(isNaN(size)){
    size = 10;
  }

  page_size = (page_size == null || isNaN(page_size))? size : page_size;
  const page_number = (pagina != null) ? pagina : tab.find('.herramientasPaginacion').getCurrentPage();
  
  let sort_by = (columna != null) ? {columna,orden} : {columna: tab.find('.tablaResultados .activa').attr('value'),orden: tab.find('.tablaResultados .activa').attr('estado')};
  if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
    sort_by =  {columna: tab.find('.tablaResultados thead tr th').first().attr('value'),orden: 'desc'} ;
  }
  
  const formData = {
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  };
  tab.find('.filtro-busqueda-collapse [name]').each(function(idx,o){
    formData[$(o).attr('name')]=$(o).val();
  });
  
  $.ajax({
    type: 'POST',
    url: $(this).attr('target'),
    data: formData,
    dataType: 'json',

    success: function (ret){      
      tab.find('.herramientasPaginacion').generarTitulo(page_number,page_size,ret.total,function(e,pageNumber,tam){
        return clickIndice(tab,e,pageNumber,tam);
      });
      tab.find('.tablaResultados tbody tr').remove();

      ret.data.forEach(function(obj){
        const fila = tab.find('.moldeFilaResultados').clone().removeClass('moldeFilaResultados');
        Object.keys(obj).forEach(function(k){
          fila.find('.'+k).text(obj[k]);
        });
        fila.find('button').val(obj.id).filter(function(idx,o){
          return !$(o).attr('data-estados').split(',').includes(obj.estado+'');
        }).remove();
        fila.find('.estado').empty().append(
          $(`#iconosEstados i[data-estado=${obj.estado}]`).clone()
        );
        tab.find('.tablaResultados tbody').append(fila);
      });
      
      tab.find('.herramientasPaginacion').generarIndices(page_number,page_size,ret.total,function(e,pageNumber,tam){
        return clickIndice(tab,e,pageNumber,tam);
      });
    },
    error: function(data){
      console.log(data);
    },
  })
});

$('.btn-grande[modal="#modal-GenerarPlantilla"]').click(function(e){
  e.preventDefault();
  const M = $($(this).attr('modal'));
  M.modal('show');
  $.ajax({
    type: "POST",
    url: 'aperturas/generarRelevamiento',
    dataType: 'json',
    success: function (data) {
      M.modal('hide');
      var iframe;
      iframe = document.getElementById("download-container");
      if (iframe === null){
        iframe = document.createElement('iframe');
        iframe.id = "download-container";
        iframe.style.visibility = 'hidden';
        document.body.appendChild(iframe);
      }
      iframe.src = 'aperturas/descargarZip/'+data.nombre_zip;
    },
    error: function (data) {
      M.modal('hide');
      setTimeout(function(){
        $('#modal-Reintente').modal('show');
      },500);
    }
  });
});

$('#modal-CargarCierreApertura').on('hidden.bs.modal',function(e){
  $('.tab_content:visible .btn-buscar').click();
  $(this).find('.filtroFecha').data('datetimepicker').reset();
  $(this).find('.form-control').val('');
  $(this).find('.tablaMesas tbody tr').remove();
  $(this).find('.tablaFichas tbody tr').remove();
  $(this).find('.inputMesas,.datosCierreApertura').hide();
  const quienSoy = $('#quienSoy').clone().show().removeAttr('id');
  $(this).find('[name="cargador"]').replaceWith(quienSoy);
  ocultarErrorValidacion($(this).find('.form-control'));
});

$('#modal-CargarCierreApertura [name="fecha"],\
   #modal-CargarCierreApertura [name="id_casino"]').change(function(e){
  e.preventDefault();
  const modal = $(this).closest('.modal');
  let err = false;
  const id_casino = modal.find('[name="id_casino"]').val();
  const fecha     = modal.find('[name="fecha"]').val();
  if(id_casino.length == 0 || fecha.length == 0){
    modal.find('.inputMesas').hide();
  }
  else{
    modal.find('.inputMesas').show();
  }
})

$('#modal-CargarCierreApertura [name="id_casino"]').change(function(e){
  const modal = $(this).closest('.modal');
  modal.find('.tablaMesas tbody').empty();
  const id_casino = $(this).val();
  modal.find('.mesa').generarDataList("cierres/obtenerMesasCierre/" + id_casino,'mesas' ,'id_mesa_de_panio','nro_mesa',1);
  modal.find('[name="fiscalizador"]').generarDataList("cierres/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
});

$('#modal-CargarCierreApertura .agregarMesa').click(function(e) {
  const modal = $(this).closest('.modal');
  const id_mesa_de_panio = modal.find('.mesa').attr('data-elemento-seleccionado');
  modal.find('.mesa').setearElementoSeleccionado(null,"");
  
  const ya_existe = modal.find('.tablaMesas').find('button').filter(function(idx,o){
    return $(o).val() == id_mesa_de_panio;
  }).length > 0;
  if(ya_existe) return;

  $.get("cierres/detalleMesa/" + id_mesa_de_panio, function(data) {
    const fila = modal.find('.moldeFila').clone().removeClass('moldeFila');
    
    //Moneda seleccionada
    let id_moneda = null;
    //Fichas por moneda
    let fichas = {};
    
    //@HACK: devolver las monedas habilitadas desde el backend y listo
    switch(!!data.fichas_pesos + !!data.fichas_dolares){
      case 1:{
        fichas[data.fichas_pesos? 1 : 2] = data.fichas_pesos ?? data.fichas_dolares;
        id_moneda = data.fichas_pesos? 1 : 2;
      }break;
      case 2:{
        id_moneda = '';
        fichas[''] = [];
        fichas[1] = data.fichas_pesos;
        fichas[2] = data.fichas_dolares;
      }break;
      default:{
        id_moneda = '';
        fichas[''] = [];
      }break;
    }
    
    fila.find('.nro_mesa').text(data.mesa.nro_mesa);
    fila.data('valores',{
      id_mesa_de_panio: id_mesa_de_panio,
      id_moneda: id_moneda,
      fichas: fichas,
      hora: '',
      hora_inicio: '',
      hora_fin: '',
      total_pesos_fichas_a: 0,
      total_pesos_fichas_c: 0,
      total_anticipos_c: 0
    });
    fila.find('button').val(data.mesa.id_mesa_de_panio);
    modal.find('.tablaMesas tbody').append(fila);
  });
});

$(document).on('click', '#modal-CargarCierreApertura .borrar', function(e){
  const fila = $(this).closest('tr');
  const modal = $(this).closest('.modal');
  const prev = fila.prev();
  const next = fila.next();
  fila.remove();
  if(!fila.hasClass('mesa_seleccionada')) return;
  
  (next.length == 0? prev : next).find('.cargar').click();
  modal.find('.datosCierreApertura').toggle(next.length+prev.length);
});

$(document).on('click', '#modal-CargarCierreApertura  .cargar', function(e){
  e.preventDefault();
  
  const modal   = $(this).closest('.modal');
  const mesa    = $(this).closest('tr');
  
  modal.find('.tablaMesas tbody tr').removeClass('mesa_seleccionada');
  mesa.addClass('mesa_seleccionada');

  const valores = mesa.data('valores');
  modal.find('[name="id_moneda"] option').prop('disabled',true);
  Object.keys(valores.fichas).forEach(function(id_moneda){
    modal.find(`[name="id_moneda"] option[value="${id_moneda}"]`).prop('disabled',false);
  });
  
  modal.find('[name="id_moneda"]').val(valores.id_moneda);
  modal.find('[name="total_pesos_fichas_a"]').val(valores.total_pesos_fichas_a);
  modal.find('[name="total_pesos_fichas_c"]').val(valores.total_pesos_fichas_c);
  modal.find('[name="total_anticipos_c"]').val(valores.total_anticipos_c);
  modal.find('[name="hora"]').val(valores.hora);
  modal.find('[name="hora_inicio"]').val(valores.hora_inicio);
  modal.find('[name="hora_fin"]').val(valores.hora_fin);
  
  if(valores.id_cargador !== null){
    modal.find('[name="cargador"]').attr('data-elemento-seleccionado',valores.id_cargador)
    .val(valores.nombre_cargador ?? '')
    .attr('value',valores.nombre_cargador ?? '');
  }
  
  modal.find('[name="fiscalizador"]').setearElementoSeleccionado(
    valores.id_fiscalizador ?? null,
    valores.nombre_fiscalizador ?? ''
  );
  
  const cargado = mesa.find('.cargado').length > 0;
  modal.find('.btn-guardar').toggle(!cargado);
  modal.find('.datosCierreApertura .form-control').not('[readonly]').attr('disabled',cargado);
  modal.find('.datosCierreApertura').show();
  modal.find('.datosCierreApertura [name]').change();
});

$('#modal-CargarCierreApertura [name="id_moneda"]').change(function(e){
  e.stopPropagation();
  const modal = $(this).closest('.modal');
  const mesa = modal.find('.mesa_seleccionada');
  const valores = mesa.data('valores') ?? {};
  valores.id_moneda = $(this).val();
  mesa.data('valores',valores);
    
  const tabla = modal.find('.tablaFichas tbody').empty();
  const moldeFila = modal.find('.moldeFichas').clone().removeClass('moldeFichas');
  (valores.fichas[valores.id_moneda] ?? []).forEach(function(f){
    const fila = moldeFila.clone();
    fila.attr('data-id_ficha',f.id_ficha);
    const valor_ficha = parseFloat(f.valor_ficha);
    const monto_ficha = parseFloat(f.monto_ficha);
    const cantidad_ficha = parseFloat(f.cantidad_ficha);
    fila.find('.valor_ficha').val(isNaN(valor_ficha)? '' : valor_ficha);
    fila.find('.monto_ficha').val(isNaN(monto_ficha)? '' : monto_ficha);
    fila.find('.cantidad_ficha').val(isNaN(cantidad_ficha)? '' : cantidad_ficha);
    tabla.append(fila);
  });
  tabla.find('tr').eq(0).find('.valor_ficha').change();//Recalcular el total
});

$('#modal-CargarCierreApertura .datosCierreApertura [name]').change(function(e){
  const mesa = $(this).closest('.modal').find('.mesa_seleccionada');
  const valores = mesa.data('valores') ?? {};
  valores[$(this).attr('name')] = $(this).val();
  mesa.data('valores',valores);
});

function cambio_ficha(e){
  const modal = $(this).closest('.modal');
  const mesa = modal.find('.mesa_seleccionada');
  const valores = mesa.data('valores');
  const clearNaN = x => (isNaN(x)? 0 : x);
  {//El juego monto-cantidad, cambio el que no edito el usuario
    const tgt = $(e.target);
    const fila = tgt.closest('tr');
    const valor_ficha = parseFloat(fila.find('.valor_ficha').val());
    if(tgt.hasClass('monto_ficha')){
      const monto_ficha = clearNaN(parseFloat(fila.find('.monto_ficha').val()));
      fila.find('.monto_ficha').val(monto_ficha);
      fila.find('.cantidad_ficha').val(monto_ficha/valor_ficha);
    }
    else if(tgt.hasClass('cantidad_ficha')){
      const cantidad_ficha = clearNaN(parseFloat(fila.find('.cantidad_ficha').val()));
      fila.find('.cantidad_ficha').val(cantidad_ficha);
      fila.find('.monto_ficha').val(cantidad_ficha*valor_ficha);
    }
  }
  
  let total = 0;
  const fichas = modal.find('.tablaFichas tbody tr').map(function(idx,o){
    const monto_ficha = $(this).find('.monto_ficha').val();
    total += clearNaN(parseFloat(monto_ficha));
    return {
      valor_ficha: $(this).find('.valor_ficha').val(),
      cantidad_ficha: $(this).find('.cantidad_ficha').val(),
      monto_ficha: monto_ficha
    };
  }).toArray();
  
  valores.fichas[valores.id_moneda] = fichas;
  valores.total_pesos_fichas_c = total;
  valores.total_pesos_fichas_a = total;
  
  modal.find('[name="total_pesos_fichas_c"],[name="total_pesos_fichas_a"]')
  .val(total).attr('value',total);
}

$(document).on('change','#modal-CargarCierreApertura .valor_ficha,\
                         #modal-CargarCierreApertura .cantidad_ficha,\
                         #modal-CargarCierreApertura .monto_ficha',cambio_ficha);

$('#modal-CargarCierreApertura [name]').change(function(e){
  $(this).attr('value',$(this).val());
});

function obtenerDatosModalCargarCierreApertura(){
  const modal = $('#modal-CargarCierreApertura');
  const data = {};
  
  modal.find('[name]').each(function(idx,o){
    const attr = $(o).attr('formdata-attr') ?? 'value';
    data[$(o).attr('name')] = $(o).attr(attr);
  });
  
  data.fichas = modal.find('.tablaFichas tbody tr').map(function(idx,f){
    return {
      id_ficha: $(f).attr('data-id_ficha'),
      valor_ficha: $(f).find('.valor_ficha').val(),
      cantidad_ficha: $(f).find('.cantidad_ficha').val(),
      monto_ficha: $(f).find('.monto_ficha').val()
    };
  }).toArray();
  
  const mesa = modal.find('.mesa_seleccionada');
  
  data.id_mesa_de_panio = mesa.data('valores').id_mesa_de_panio;
  data.id_cierre_mesa   = mesa.data('valores').id_cierre_mesa;
  data.id_apertura_mesa = mesa.data('valores').id_apertura_mesa;
  
  return data;
}

$('#modal-CargarCierreApertura .btn-guardar').click(function(e){
  const formData = obtenerDatosModalCargarCierreApertura();
  console.log(formData);//@TODO
  const mesa = $(this).closest('.modal').find('.mesa_seleccionada');
  mesa.find('.cargar').addClass('cargado');
  mesa.find('.borrar').remove();
  mesa.find('.cargar').click();
});

$('#modal-CargarCierreApertura .btn-validar').click(function(e){
  const formData = obtenerDatosModalCargarCierreApertura();
  console.log(formData);
  mensajeExito('Cierre validado');
  $(this).closest('.modal').modal('hide');
});

$('#modal-CargarCierreApertura .btn-salir').on('click', function(){
  const modal = $(this).closest('.modal');
  modal.modal('hide');
  if(modal.find('.cargado').length > 0){
    mensajeExito();
  }
});

//desvincular una apertura y un cierre. Cuando se valido mal la apertura
$(document).on('click', '.desvincular', function(e){
  e.preventDefault();
  $('#modalDesvinculacion').modal('show');
  $('#btn-desvincular').val($(this).val());
});

$(document).on('click', '#btn-desvincular', function(e){
  $.get('aperturas/desvincularApertura/' + $(this).val(), function(data){
    $('#modalDesvinculacion').modal('hide');
    if(data==1){
      mensajeExito('Se ha desvinculado el cierre de esta Apertura.');
      $('#pant_aperturas .btn-buscar').click();
    }
    else{
      mensajeError('No es posible realizar esta acción, ya ha cerrado el periodo de producción correspondiente.');
    }
  });
})

$(document).on('click', '#pant_aperturas .ver', function(e) {
  e.preventDefault();
  mostrarCierreApertura(null,$(this).val());
});
$(document).on('click', '#pant_cierres .ver', function(e) {
  e.preventDefault();
  mostrarCierreApertura($(this).val(),null);
});

function mostrarCierreApertura(id_cierre_mesa,id_apertura_mesa,sucess = function(data){}){
  obtenerCierreApertura(id_cierre_mesa,id_apertura_mesa, function(data){
    ['Cierre','Apertura'].forEach(function(tipo){
      const O = data[tipo.toLowerCase()] ?? null;
      const $O = $(`#modalVerCierreApertura .datos${tipo}`);
      if(O === null) return $O.hide();
      $O.show();
      $O.find('.titulo_datos').text(O? tipo.toUpperCase() : `-SIN ${tipo.toUpperCase()}-`);
      $O.find('.datos').toggle(!!O);
      $O.find('.nro_mesa').text(`${O?.mesa?.nombre} - ${O?.moneda?.descripcion}`);
      $O.find('.nombre_juego').text(O?.juego?.nombre_juego ?? '-');
      $O.find('.fecha').text(O?.datos?.fecha ?? ' - ');
      $O.find('.fiscalizador').text(O?.fiscalizador?.nombre ?? ' - ');
      $O.find('.hora_fin').text(O?.datos?.hora_fin ?? ' - ');
      $O.find('.hora_inicio').text(O?.datos?.hora_inicio ?? ' - ');
      $O.find('.total_pesos_fichas_c').val(O?.datos?.total_pesos_fichas_c ?? 0);
      $O.find('.total_anticipos_c').val(O?.datos?.total_anticipos_c ?? ' - ');
      $O.find('.cargador').text(O?.cargador?.nombre ?? ' - ');
      $O.find('.hora').text(O?.datos?.hora ?? ' - ');
      $O.find('.total_pesos_fichas_a').val(O?.datos?.total_pesos_fichas_a ?? 0);
      $O.find('.tablaFichas tbody').empty();
      (O?.detalles ?? []).forEach(function(ficha){
        const fila = $O.find('.moldeFila').clone().removeClass('moldeFila');
        fila.find('.valor_ficha').text(ficha.valor_ficha ?? 0);
        fila.find('.cantidad_ficha').text(ficha.cantidad_ficha ?? 0);
        fila.find('.monto_ficha').text(ficha.monto_ficha ?? 0);
        $O.find('.tablaFichas tbody').append(fila);
      });
    });
        
    $('#modalVerCierreApertura').modal('show');
  });
}

function obtenerCierreApertura(id_cierre_mesa,id_apertura_mesa,success = function(data){}){
  let url = '';
  if(id_cierre_mesa !== null)
    url = 'cierres/getCierre';
  else if(id_apertura_mesa !== null)
    url = 'aperturas/getApertura';
  else 
    throw 'Error de parametros';
  
  $.ajax({
    type: 'GET',
    url: url,
    data: {
      id_cierre_mesa: id_cierre_mesa,
      id_apertura_mesa: id_apertura_mesa
    },
    success: success,
    error: function (response) {
      console.log(response);
    }
  });
}

function setupCargarCierreApertura(modo){
  const modal = $('#modal-CargarCierreApertura');
  modal.find('.tipo').text(modo);
  switch(modo){
    case 'CARGAR CIERRES':{
      modal.find("[cierres]").show();
      modal.find("[aperturas]").hide();
      modal.find("[cargar]").show();
      modal.find("[cargar_modificar]").show();
      modal.find("[validar]").hide();
      modal.find("[name='id_casino'],[name='fecha']").removeAttr('disabled');
      modal.find(".modal-header").css('background-color','#6dc7be');
    }break;
    case 'MODIFICAR CIERRE':{
      modal.find("[cierres]").show();
      modal.find("[aperturas]").hide();
      modal.find("[cargar]").hide();
      modal.find("[cargar_modificar]").show();
      modal.find("[validar]").hide();
      modal.find("[name='id_casino'],[name='fecha']").attr('disabled',true);
      modal.find(".modal-header").css('background-color','#FFB74D');
    }break;
    case 'VALIDAR CIERRE':{
      modal.find("[cierres]").show();
      modal.find("[aperturas]").hide();
      modal.find("[cargar]").hide();
      modal.find("[cargar_modificar]").hide();
      modal.find("[validar]").show();
      modal.find("[name='id_casino'],[name='fecha']").attr('disabled',true);
      modal.find(".modal-header").css('background-color','#6dc7be');
    }break;
    case 'CARGAR APERTURAS':{
      modal.find("[cierres]").hide();
      modal.find("[aperturas]").show();
      modal.find("[cargar]").show();
      modal.find("[cargar_modificar]").show();
      modal.find("[validar]").hide();
      modal.find("[name='id_casino'],[name='fecha']").removeAttr('disabled');
      modal.find(".modal-header").css('background-color','#6dc7be');
    }break;
    case 'MODIFICAR APERTURA':{
      modal.find("[cierres]").hide();
      modal.find("[aperturas]").show();
      modal.find("[cargar]").hide();
      modal.find("[cargar_modificar]").show();
      modal.find("[validar]").hide();
      modal.find("[name='id_casino'],[name='fecha']").attr('disabled',true);
      modal.find(".modal-header").css('background-color','#FFB74D');
    }break;
    default:{
      throw `modo "${modo}" inesperado`;
      modal.modal('hide');
    }break;
  }
}

function cargarMesaModificarAperturaModificarValidarCierre(O,titulo){
  const modal = $('#modal-CargarCierreApertura');
  setupCargarCierreApertura(titulo);
  modal.find('.filtroFecha').data('datetimepicker').setDate(
    new Date(O?.datos?.fecha+'T00:00')
  );
  modal.find('[name="fecha"]').change();
  modal.find('[name="id_casino"]').val(O?.mesa?.id_casino).change();
  
  const fila = modal.find('.moldeFila').clone().removeClass('moldeFila');
  fila.find('.nro_mesa').text(O?.mesa?.nro_mesa ?? '#ERROR#');
  fila.find('button').val(O?.mesa?.id_mesa_de_panio);
  fila.find('.borrar').remove();
  
  const fichas = {};
  fichas[O?.mesa?.id_moneda] = O?.detalles ?? [];
  const valores = {
    id_apertura_mesa: O?.datos?.id_apertura_mesa,
    id_cierre_mesa: O?.datos?.id_cierre_mesa,
    id_mesa_de_panio: O?.mesa?.id_mesa_de_panio ?? 'ERROR',
    id_moneda: O?.mesa?.id_moneda ?? '',
    fichas: fichas,
    hora: O?.datos?.hora ?? '',
    hora_inicio: O?.datos?.hora_inicio ?? '',
    hora_fin: O?.datos?.hora_fin ?? '',
    total_pesos_fichas_a: O?.datos?.total_pesos_fichas_a,
    total_pesos_fichas_c: O?.datos?.total_pesos_fichas_c,
    total_anticipos_c: O?.datos?.total_anticipos_c,
  };
  
  if(titulo == 'MODIFICAR APERTURA'){
    valores.id_cargador = A?.cargador?.id_usuario;
    valores.nombre_cargador = A?.cargador?.nombre;
    valores.id_fiscalizador = A?.fiscalizador?.id_usuario;
    valores.nombre_fiscalizador = A?.fiscalizador?.nombre;
    modal.find('[name="fiscalizador"]').generarDataList("aperturas/buscarFiscalizadores/" + A?.mesa?.id_casino,'usuarios' ,'id_usuario','nombre',1);
  }
  else if(titulo == 'MODIFICAR CIERRE' || titulo == 'VALIDAR CIERRE'){
    valores.id_cargador = O?.fiscalizador?.id_usuario;
    valores.nombre_cargador = O?.fiscalizador?.nombre;
    modal.find('[name="fiscalizador"]').generarDataList("cierres/buscarFiscalizadores/" + O?.mesa?.id_casino,'usuarios' ,'id_usuario','nombre',1);
    if(titulo == 'VALIDAR CIERRE'){
      fila.find('.cargar').addClass('cargado');
      modal.find('.datosCierreApertura .form-control').not('[readonly]').attr('disabled',true);
    }
  }
  else{ return console.log('titulo '+titulo+' sin manejar'); }
  
  fila.data('valores',valores);
  modal.find('.tablaMesas tbody').append(fila);
  modal.modal('show');
  
  setTimeout(function(){
    fila.find('.cargar').click();
  },250);
}

$('.btn-grande[modal="#modal-CargarApertura"]').click(function(e){
  e.preventDefault();
  setupCargarCierreApertura('CARGAR APERTURAS');
  $('#modal-CargarCierreApertura').modal('show');
});

$('.btn-grande[modal="#modal-CargarCierre"]').click(function(e){
  e.preventDefault();
  setupCargarCierreApertura('CARGAR CIERRES');
  $('#modal-CargarCierreApertura').modal('show');
});

$(document).on('click', '#pant_aperturas .modificar', function(e) {
  e.preventDefault();
  obtenerCierreApertura(null,$(this).val(), function(data){
    cargarMesaModificarAperturaModificarValidarCierre(data?.apertura ?? {},'MODIFICAR CIERRE');
  });
});

$(document).on('click', '#pant_cierres .modificar', function(e) {
  e.preventDefault();
  obtenerCierreApertura($(this).val(), null, function(data){
    cargarMesaModificarAperturaModificarValidarCierre(data?.cierre ?? {},'MODIFICAR CIERRE');
  });
});

$(document).on('click', '#pant_cierres .validar', function(e) {
  e.preventDefault();
  obtenerCierreApertura($(this).val(), null, function(data){
    cargarMesaModificarAperturaModificarValidarCierre(data?.cierre ?? {},'VALIDAR CIERRE');
  });
});

//si es superusuario puede eliminarCyA
$(document).on('click','#pant_aperturas .eliminar',function(e){
  $('#modalAlertaBaja .btn-eliminar').attr('data-tipo','apertura');
  $('#modalAlertaBaja .btn-eliminar').val($(this).val());
  $('#modalAlertaBaja .mensaje').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTA APERTURA?')
  $('#modalAlertaBaja').modal('show');
});
//si es superusuario puede eliminarCyA
$(document).on('click','#pant_cierres .eliminar',function(e){
  $('#modalAlertaBaja .btn-eliminar').attr('data-tipo','cierre');
  $('#modalAlertaBaja .btn-eliminar').val($(this).val());
  $('#modalAlertaBaja .mensaje').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTE CIERRE?')
  $('#modalAlertaBaja').modal('show');
});

$('#modalAlertaBaja .btn-eliminar').click(function(){
  const id = $(this).val();
  let url = '';
  switch($(this).attr('data-tipo')){
    case 'apertura':{
      url = 'aperturas/bajaApertura/'+id;
    }break;
    case 'cierre':{
      url = 'cierres/bajaCierre/'+id;
    }break;
    default:{
      return;
    }break;
  }
  
  $.get(url, function(data){
    mensajeExito();
    $('.tab_content:visible .btn-buscar').click();
    $('#modalAlertaBaja').modal('hide');
  });
})

$('#modalValidarApertura').on('hidden.bs.modal',function(e){
  $(this).find('.form-control').val('');
  $(this).find('.datosA,.datosC').find('h6 span').text('');
  $(this).find('.fechaCierreVal option[value!=""]').remove();
  $(this).find('.btn-validar,.btn-validar-diferencia,.datosC,.mensajeErrorValApertura').hide();
  $(this).find('.tablaFichas tbody tr').remove();
});

$(document).on('click', '#pant_aperturas .validar', function(e) {
  e.preventDefault();
  const M = $('#modalValidarApertura');
  const $M = M.find.bind(M);
  const id_apertura = $(this).val();
  $M('.btn-validar,.btn-validar-diferencia').val(id_apertura);

  $.get('aperturas/obtenerApValidar/' + id_apertura, function(A){
    $M('.nro_mesa').text(A?.mesa?.nro_mesa);
    $M('.fecha_apertura').text(A?.apertura?.fecha);
    $M('.juego').text(A?.juego?.nombre_juego);
    $M('.casino').text(A?.casino?.nombre);
    $M('.hora').text(A?.apertura?.hora_format);
    $M('.fiscalizador').text(A?.fiscalizador?.nombre);
    $M('.cargador').text(A?.cargador?.nombre);
    $M('.tipo_mesa').text(A?.tipo_mesa?.descripcion);
    $M('.moneda').text(A?.moneda?.descripcion).val(A?.moneda?.id_moneda);
    $M('.total_pesos_fichas_a').val(A?.apertura?.total_pesos_fichas_a);

    (A?.fechas_cierres ?? []).forEach(function(c) {
      $M('.fechaCierreVal').append(      
        $('<option>').val(c.id_cierre_mesa)
        .text(`${c.fecha} -- ${c.hora_inicio_format} a ${c.hora_fin_format} -- ${c.siglas}`)
      );
    });
    
    M.modal('show');
  });
});


$(document).on('change', '#modalValidarApertura .fechaCierreVal', function(e) {
  e.preventDefault();
  
  const $M = $(this).closest('.modal').find.bind($(this).closest('.modal'));
  $M('.btn-validar,.btn-validar-diferencia,.datosC').hide();
  $M('.tablaFichas tbody tr').remove();
  $M('.total_anticipos_c,.total_pesos_fichas_c').val('-');
  
  if($(this).val().length == 0) return;
  
  const id_apertura = $M('.btn-validar').val();
  const id_cierre   = $M('.fechaCierreVal').val();
  
  $.get(`aperturas/compararCierre/${id_apertura}/${id_cierre}`, function(data){
    $M('.hora_inicio').text(data?.cierre?.hora_inicio_format ?? '-');
    $M('.hora_fin').text(data?.cierre?.hora_fin_format ?? '-');
    $M('.fecha_cierre').text(data?.cierre?.fecha ?? '-');
    $M('.total_anticipos_c').val(data?.cierre?.total_anticipos_c ?? '-');
    $M('.total_pesos_fichas_c').val(data?.cierre?.total_pesos_fichas_c ?? '-');    
    
    let diferencias = 0;
    (data.fichas ?? []).forEach(function(f){
      const c = (data.detalles_cierre   ?? []).find(c => c.id_ficha == f.id_ficha)?.cantidad_ficha ?? 0;
      const a = (data.detalles_apertura ?? []).find(a => a.id_ficha == f.id_ficha)?.cantidad_ficha ?? 0;
      
      const fila = $M('.moldeFicha').clone().removeClass('moldeFicha').show();
      fila.attr('data-id-ficha',f.id_ficha);
      fila.find('.valor_ficha').text(f.valor_ficha ?? '-');
      fila.find('.cierre_cantidad_ficha').text(c);
      fila.find('.apertura_cantidad_ficha').text(a);
      
      const icono = [['fa-check','#66BB6A'],['fa-times','#D32F2F']];
      const hay_diferencia = (a != c)+0;
      const i = icono[hay_diferencia];
      fila.find('.diferencia').append(
        $('<i>').addClass(`fa fa-fw ${i[0]}`).css('color',i[1])
      );
      
      diferencias += hay_diferencia;
      $M('.tablaFichas tbody').append(fila);
    });
    $M('.btn-validar').toggle(diferencias == 0);
    $M('.btn-validar-diferencia').toggle(diferencias != 0);
    $M('.datosC').show();
  })
});

function validar(id_apertura,diferencia){
  $.ajax({
    type: 'POST',
    url: 'aperturas/validarApertura',
    data: {
      id_cierre: $('#modalValidarApertura .fechaCierreVal').val(),
      id_apertura: id_apertura,
      diferencia: diferencia,
      observaciones: $('#modalValidarApertura .obsValidacion').val(),
    },
    dataType: 'json',
    success: function (data){
      $('#modalValidarApertura').modal('hide');
      mensajeExito('Apertura Validada correctamente.');
      $('#pant_aperturas .btn-buscar').click();
    },
    error: function(data){
     const response = data.responseJSON;
     if(typeof response.id_cierre !== 'undefined'){
       $('#modalValidarApertura .mensajeErrorValApertura').show();
     }
    },
  });
}

$(document).on('click', '#modalValidarApertura .btn-validar', function(e) {
  e.preventDefault();
  validar($(this).val(),0);
});

$(document).on('click', '#modalValidarApertura .btn-validar-diferencia', function(e) {
  e.preventDefault();
  validar($(this).val(),1);
});

/*****************PAGINACION******************/
function clickIndice(tab,e,pageNumber,tam){
  if(e == null) return;
  
  e.preventDefault();
  tam = (tam != null) ? tam : tab.find('.herramientasPaginacion').getPageSize();
  const columna = tab.find('.tablaResultados .activa').attr('value');
  const orden = tab.find('.tablaResultados .activa').attr('estado');
  
  tab.find('.btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

$('.tablaResultados thead tr th').click(function(e){
  const icon = $(this).find('i');
  const not_sorted = icon.hasClass('fa-sort');
  const down_sorted = icon.hasClass('fa-sort-down');
  const tabla = $(this).closest('.tablaResultados');
  tabla.find('.activa').removeClass('activa');
  tabla.find('thead tr th i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  if(not_sorted){
    icon.removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else if(down_sorted){
    icon.removeClass().addClass('fa fa-sort-up').parent().addClass('activa').attr('estado','asc');
  }
  $(this).closest('.tab_content').find('.btn-buscar').click();
});

/*
  ######             #######
 ##    ##     ####   ##    ## 
 ##    ##    ##  ##  ##    ##
 ########         #  ####### 
 ##    ##   #######  ##
 ##    ##  ##    ##  ##
 ##    ##  ##    ##  ##
 ##    ##   ##### #  ##

 APERTURAS A PEDIDO
*/

function buscarAperturasAPedido(){
  $('#tablaAaP tbody').empty();
  $.ajax({
    url: '/aperturas/buscarAperturasAPedido',
    type: 'GET',
    dataType: 'json',
    success: function(aaps){
      aaps.forEach(function(aap){
        const fila = $('#moldeAaP').clone().removeAttr('id');
        Object.keys(aap).forEach(function(k){
          fila.find('.'+k).text(aap[k]);
        });
        fila.find('button').val(aap.id_apertura_a_pedido);
        $('#tablaAaP tbody').append(fila);
      });
    },
    error: function(data){
      mensajeError();
      console.log(data.responseJSON);
    }
  });
}

$('.btn-grande[modal="#modal-AperturaAPedido"]').click(function(e){
  e.preventDefault();
  $('#juegoAaP option').removeAttr('selected').eq(0).attr('selected','selected').change();
  $(this).find('.filtroFecha').each(function(){
    $(this).data('datetimepicker').reset();
  });
  ocultarErrorValidacion($(this).find('input,select'));
  buscarAperturasAPedido();
  $($(this).attr('modal')).modal('show');
});

$('#juegoAaP').change(function(e){
  e.preventDefault();
  $('#mesaAaP').generarDataList("/aperturas/obtenerMesasPorJuego/" + $('#juegoAaP').val(), 'mesas', 'id_mesa_de_panio', 'nro_mesa', 1);
})

$('#agregarAaP').click(function(e){
  e.preventDefault();
  $.ajax({
    url: '/aperturas/agregarAperturaAPedido',
    type: 'POST',
    dataType: 'json',
    data: {
      id_mesa_de_panio : $('#mesaAaP').obtenerElementoSeleccionado(),
      fecha_inicio : $('#fechaInicioAaP').val(),
      fecha_fin : $('#fechaFinAaP').val(),
    },
    success: function(data){
      $('#juegoAaP').change();//Limpia el input de nro de mesa
      buscarAperturasAPedido();
    },
    error: function(data){
      const response = data.responseJSON;
      console.log(response);
      if(response.id_mesa_de_panio){
        mostrarErrorValidacion($('#mesaAaP'),'Valor incorrecto',true);
      }
      if(response.fecha_inicio){
        mostrarErrorValidacion($('#fechaInicioAaP'),'Valor incorrecto',true);
      }
      if(response.fecha_fin){
        mostrarErrorValidacion($('#fechaFinAaP'),'Valor incorrecto',true);
      }
    }
  })
})

$(document).on('click','#modal-AperturaAPedido .eliminarAaP',function(e){
  e.preventDefault();
  const fila = $(this).closest('tr');
  $.ajax({
    url: '/aperturas/borrarAperturaAPedido/'+$(this).val(),
    type: 'DELETE',
    dataType: 'json',
    success: function(){
      fila.remove();
    },
    error: function(data){
      mensajeError();
      console.log(data.responseJSON);
    }
  });
})
