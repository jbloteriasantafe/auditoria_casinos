$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Reportes de Diferencia');
  $('#dtpBuscadorFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
    endDate: '+0d'
  });
  $('#btn-buscar').trigger('click');
});

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  const minimizar = $(this).data("minimizar")==true;
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar",!minimizar);  
});

//enter en buscador
$('#collapseFiltros .form-control').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
})

//búsqueda de reportes
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  e.preventDefault();

  //Fix error cuando librería saca los selectores
  let size = 10;
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size))? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultadosPremio .activa').attr('value'),orden: $('#tablaResultadosPremio .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultadosPremios th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'GET',
    url: 'buscarReportesDiferencia',
    data: {
      fecha: $('#buscadorFecha').val(),
      casino: $('#buscadorCasino').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    },
    dataType: 'json',
    success: function(resultados){
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      $('#cuerpoTabla tr').remove();
      for (const i in resultados.data){
        $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i]));
      }
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function(data){
      console.log('Error:', data);
    }
  });
});

//envia los datos para validar (obsercaciones+cambio de estado)
$('#btn-finalizarValidacion').click(function(e){
  e.preventDefault();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'guardarReporteDiferencia',
    data: {
      id_importacion: $('#id_importacion').val(),
      observacion: $('#observacion_validacion').val()
    },
    dataType: 'json',
    success: function (data) {
      console.log(data);
      $('#btn-buscar').click();
      $('#modalDetalles').modal('hide');
      $('#mensajeExito').show();
    },
    error: function (data) {
      console.log(data);
    }
  });
});
//Mostral modal con detalles y relevamientos de la sesión
$(document).on('click' , '.visar,.ver' , function() {
  const id_importacion = $(this).val();
  $('#id_importacion').val(id_importacion);
  $('#frmDetalles').trigger("reset");
  $('#cuerpoTablaDetalles').empty();
  $('#terminoDatos2').empty();
  //si la sesión tiene archivo importado, muestra el modal con los datos
  //sino, muestra mensaje de error
  if(id_importacion == 'no_importado'){
    return $('#modalError').modal('show');
  }
  $.get("obtenerDiferencia/" + id_importacion, function(data){
    console.log(data);
    $('#modalDetalles .modal-title').text('| DETALLES DIFERENCIA ' + data.importacion[0].fecha);
    //detalles sesion
    const sesion = data.sesion? data.sesion.sesion : null;
    const detalles_sesion = data.sesion? data.sesion.detalles : [];
    cargarDatosSesion(data.importacion,sesion ?? null, data.pozoDotInicial);
    cargarDetallesSesion(data.importacion, detalles_sesion);
    //genera la tabla con las partidas importadas
    const partidas = data.sesion? (data.sesion.partidas? data.sesion.partidas : []) : [];
    data.importacion.forEach(function(imp,idx){
      const par = partidas.find(function(p){
        return p[0].num_partida === (parseInt(idx)+1);
      });
      $('#cuerpoTablaDetalles').append(generarFilaPartidaImportada(imp, par? par[0] : undefined));
    });

    //mostrar pops
    $('.pop-sinrelevar').popover({html:true});
    $('.pop-relevado').popover({html:true});
    $('.pop-exclamation').popover({html:true});
    $('.pop-check').popover({html:true});
    $('.pop-times').popover({html:true});
    
    $('#btn-finalizarValidacion').toggle(!data.reporte.visado);
    $('#observacion_validacion').val(data.reporte.observaciones_visado).attr('disabled',!!data.reporte.visado);
    $('#modalDetalles').modal('show');
  });
})

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
  const icon = $(this).children('i');
  const sin_ordenar = icon.hasClass('fa-sort');
  const ordenado_abajo = icon.hasClass('fa-sort-down');
  icon.removeClass('fa-sort fa-sort-down fa-sort-up');
  if(sin_ordenar){
    icon.addClass('fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else if(ordenado_abajo){
    icon.addClass('fa-sort-up').parent().addClass('activa').attr('estado','asc');
  }
  else{
    icon.addClass('fa-sort').parent().attr('estado','');
  }
  $('#tablaResultados th:not(.activa) i').removeClass('fa-sort-down fa-sort-up').addClass('fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaResultados .activa').attr('value');
  const orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}
function cargarDatosSesion(importaciones,sesion, pozoDotInicial){  
  $('.pop-relevado').popover('destroy');
  $('.pop-sinrelevar').popover('destroy');
  if(sesion == null || sesion.id_estado != 2){//si no existen datos de sesión, cargo los datos desde importación y pinto de naranja
    const ult_imp = importaciones[importaciones.length - 1];
    marcarSinRelevar($('#pozo_dotacion_inicial_d'),pozoDotInicial);
    marcarSinRelevar($('#pozo_extra_inicial_d'),importaciones[0].pozo_extra);
    marcarSinRelevar($('#pozo_dotacion_final_d'),ult_imp.pozo_dot);
    marcarSinRelevar($('#pozo_extra_final_d'),ult_imp.pozo_extra);
    return;
  }

  //si hay datos de sesión, comparo esos datos con los de importación y si son distintos, pinto de rojo o de naranja si no existe comparación
  //comparo datos iniciales de pozo dot y pozo extra, si llego hasta acá, existen. Sólo comparo y pinto de rojo si son != o dejo sin pintar si son ==
  marcarDiferencia($('#pozo_dotacion_inicial_d'),pozoDotInicial,sesion.pozo_dotacion_inicial);
  marcarDiferencia($('#pozo_extra_inicial_d'),importaciones[0].pozo_extra,sesion.pozo_extra_inicial);
  const ult_imp = importaciones[importaciones.length - 1];
  //Si no esta cerrado (estado = 2), NO le marco diferencia sino que le pongo color naranja
  marcarDiferencia($('#pozo_dotacion_final_d'),ult_imp.pozo_dot,sesion.pozo_dotacion_final);
  marcarDiferencia($('#pozo_extra_final_d'),ult_imp.pozo_extra,sesion.pozo_extra_final);
}
//funcion auxiliar para cargar los detalles de la sesion a partir de importación
function cargarDetallesSesion(importaciones,detalles_sesion){
  const cartones_ini = {};//Se queda con la primer ocurrencia de cada carton
  const cartones_fin = {};//Se queda con la ultima ocurrencia de cada carton
  (importaciones ?? []).map(function(imp){
    if(!(imp.valor_carton in cartones_ini)){
      cartones_ini[imp.valor_carton] = {
        valor_carton: imp.valor_carton,
        serie_inicio: imp.serieA,
        carton_inicio: imp.carton_inicio_A,
      };
    }
    return imp;//Chain
  }).forEach(function(_,idx,arr){
    const imp = arr[arr.length-1-idx];//Recorre atras para adelante
    if(!(imp.valor_carton in cartones_fin)){
      cartones_fin[imp.valor_carton] = {//si serieB es !=0 quiere decir que existe segunda serie
        serie_fin:  imp.serieB != 0? imp.serieB       : imp.serieA,
        carton_fin: imp.serieB != 0? imp.carton_fin_B : imp.carton_fin_A,
      };
    }
  });
  
  const cant_detalles_importados = Object.keys(cartones_ini).filter(function(valor_carton){
    const carton = {...cartones_ini[valor_carton],...cartones_fin[valor_carton]};//Los uno
    $('#terminoDatos2').append(generarFilaDetallesSesion(carton,detalles_sesion));
    return true;
  }).length;
  
  if(cant_detalles_importados != detalles_sesion.length){
    $('#terminoDatos2').append(//mensaje de aviso si no coincide la cantidad de detalles
      $('<p>').css('color' ,'red').text('*La cantidad de detalles relevados no coincide con los detalles de importados.')
    );
  }
}

//genera la fila de  detalles
function generarFilaDetallesSesion(detalle_importado, detalles_sesion){
  function appendFilaDetalleSesion(fila, valor, id){
    fila.append($('<div>').addClass('col-lg-2').append(
      $('<input>').attr('id',id).val(valor).attr('type','text')
      .attr('disabled','disabled').addClass('form-control')
    ));
  }
  
  const fila = $('<div>').addClass('row').css('padding-top' ,'15px');
  appendFilaDetalleSesion(fila, detalle_importado.valor_carton, 'valor_carton_f');
  appendFilaDetalleSesion(fila, detalle_importado.serie_inicio, 'serie_inicial_f');
  appendFilaDetalleSesion(fila, detalle_importado.carton_inicio, 'carton_inicial_f');
  appendFilaDetalleSesion(fila, detalle_importado.serie_fin, 'serie_final_f');
  appendFilaDetalleSesion(fila, detalle_importado.carton_fin, 'carton_final_f');

  if(detalles_sesion.length == 0){//si no existe dato para comparar, pinto de naranja
    fila.find('.form-control').addClass('pintar-orange');
    return fila;
  }
  
  const ds = detalles_sesion.find(function(ds){
    return ds.valor_carton == detalle_importado.valor_carton;
  });
  if(ds !== undefined){
    marcarDiferencia(fila.find('#serie_inicial_f'),detalle_importado.serie_inicio,ds.serie_inicio);
    marcarDiferencia(fila.find('#carton_inicial_f'),detalle_importado.carton_inicio,ds.carton_inicio);
    //si la sesión se enceuntra cerrada, no tengo datos de fin, pinto de naranja
    if(ds.serie_fin == null){
      marcarSinRelevar(fila.find('#serie_final_f'),detalle_importado.serie_fin);
      marcarSinRelevar(fila.find('#carton_final_f'),detalle_importado.carton_fin);
    }else{ //tengo datos de fin, comparo y pinto de rojo si son !=
      marcarDiferencia(fila.find('#serie_final_f'),detalle_importado.serie_fin,ds.serie_fin);
      marcarDiferencia(fila.find('#carton_final_f'),detalle_importado.carton_fin,ds.carton_fin);
    }
  }
  
  return fila;
}
//Generar fila con los datos
function generarFilaTabla(data){  
  const fila = $('#filaEjemploResultados').clone().removeAttr('id');
  fila.find('.fecha_sesion').text(data.fecha_sesion);
  fila.find('.casino').text(data.casino);
  fila.find('.hora_inicio').text(data.imp_hora_inicio ?? data.ses_hora_inicio ?? '-');
  fila.find(`.importacion i[data-status="${+!!data.importacion}"]`).show();
  fila.find(`.relevamiento i[data-status="${+!!data.relevamiento}"]`).show();
  fila.find(`.sesion_cerrada i[data-status="${+!!data.sesion_cerrada}"]`).show();
  fila.find(`.visado i[data-status="${+!!data.visado}"]`).show();
  fila.find('.visar').toggle(!!!data.visado);
  fila.find('.ver').toggle(!!data.visado);
  fila.find('button').val(data.importacion ?? 'no_importado');
  return fila;
}
//Generar fila con los datos de las partidas importadas
function generarFilaPartidaImportada(importado, partida){
  const keys_imp = Object.keys(importado);
  const fila = $('#filaEjemploDetalle').clone().removeAttr('id');
  for(const kidx in keys_imp){
    const attr_imp = keys_imp[kidx];
    fila.find(`[data-attr-imp="${attr_imp}"]`).text(importado[attr_imp]);
  }
    
  if(partida == undefined){//Si no hay partida, no comparo
    fila.find('.no-relevado').show();
    return fila;
  }
  
  let correcto = marcarDiferencia(fila.find('.recaudado'),importado.recaudado,partida.cartones_vendidos*partida.valor_carton);
  
  const keys_par = Object.keys(partida);
  for(const kidx in keys_par){
    const attr_par = keys_par[kidx];
    const attr_imp = fila.find(`[data-attr-par="${attr_par}"]`).attr('data-attr-imp');
    if(attr_imp !== undefined){
      correcto = correcto && marcarDiferencia(fila.find(`[data-attr-imp="${attr_imp}"]`),importado[attr_imp] ?? 0,partida[attr_par] ?? 0);
    }
  }
  
  fila.find('.coinciden').toggle(correcto);
  fila.find('.no-coinciden').toggle(!correcto);
  return fila;
}

function marcarSinRelevar(obj,valor){
  obj.val(valor).text(valor).attr('readonly','readonly').removeClass('pintar-red').addClass('pintar-orange')
  .attr('data-container','body')
  .attr("data-content",'Datos de Importación').attr("data-placement" , "top")
  .attr("rel","popover").attr("data-trigger" , "hover")
  .attr('title','SIN RELEVAR').addClass('pop-sinrelevar');
}
  
function marcarDiferencia(obj,valor,valor_relevado){
  obj.val(valor).text(valor).removeClass('pintar-orange');
  if(valor == valor_relevado){
    obj.attr('readonly','readonly').removeClass('pintar-red');
    return true;
  }
  obj.attr('readonly','readonly').addClass('pintar-red')
  .attr('data-container','body')
  .attr("data-content", valor_relevado).attr("data-placement" , "top")
  .attr("rel","popover").attr("data-trigger" , "hover")
  .attr('title','VALOR RELEVADO').addClass('pop-relevado');
  return false;
}
