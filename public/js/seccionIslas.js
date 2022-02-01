$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionarMTM').removeClass();
  $('#gestionarMTM').addClass('subMenu2 collapse in');
  $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');
  $('.tituloSeccionPantalla').text('Islas');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcIslas').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcIslas').addClass('opcionesSeleccionado');
  $('.modal').trigger('hidden.bs.modal');//Limpio los modales
  $('#btn-buscar').trigger('click');
});

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$('.modal').on('hidden.bs.modal', function(){//se ejecuta cuando se oculta modal con clase .modal
  ocultarErrorValidacion($('#modalIsla input,#modalIsla select'));
  $('#frmIsla').trigger('reset');
  $('#id_isla').val(0);
  $('#listaMaquinas li').remove();
  $('#nro_isla').removeClass('alerta');
  $('#cant_maquinas').removeClass('alerta');
  $('#sector').empty().append($('<option>').val(0).text('-Sectores del casino-'));
  $('#columnaMovimientos').empty();
});

//Quitar eventos de la tecla Enter
$("#contenedorFiltros input").on('keypress',function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
});

//Quitar eventos de la tecla Enter
$("#modalIsla input").on('keypress',function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-guardar').click();
  }
});

$('#casino').on('change' , function(e,id_sector){
  const id_casino = $(this).val();
  if(id_casino == 0){
    $('#buscadorMaquina').borrarDataList();
    return;
  }
  $('#buscadorMaquina').generarDataList('http://' +window.location.host + '/maquinas/buscarMaquinaPorNumeroMarcaYModelo/' + id_casino, "resultados","id_maquina" ,"nro_admin" , 2, true);
  $('#buscadorMaquina').setearElementoSeleccionado(0 , "");
  $('#sector option').remove();
  $.get("/sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
    for (let i = 0; i < data.sectores.length; i++) {
      $('#sector').append($('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion))
    }
    $('#sector').val(id_sector);
  });
})

/**************************************************
  TODOS LOS EVENTOS DEL INPUT MÁQUINA
**************************************************
 Evento para pasarle el ID de la isla en el datalist al input
    Cada vez que se hace el input se controla si un option del datalist fue seleccionado.
    Si fue seleccionado se compara con el input para sacarle el id.
*/

function generarHistorialMov(id_isla){
  const estadosMov = $('<select>').addClass('form-control estadosMovimientos');
  $('.columnaMovimientos').children().remove();
  $.get("logIsla/obtenerHistorial/" + id_isla, function(data){
    for(let i = 0;i<data.estados.length;i++){
      estadosMov.append($('<option>').val(data.estados[i].id_estado_relevamiento).text(data.estados[i].descripcion));
    }

    for(let i = 0;i<data.historial.length;i++){
      $('.columnaMovimientos')
      .append($('<div>').addClass('unMovimiento')
      .append($('<div>').addClass('col-md-4').css('padding-bottom','15px')
        .append($('<span>').attr('value',data.historial[i].id_log_isla).text(data.historial[i].fecha)))
      .append($('<div>').addClass('col-md-8').css('padding-bottom','15px')
        .append(estadosMov.clone().val(data.historial[i].id_estado_relevamiento))))
      .append($('<br>'));
    }
  });
}

//Agregar Máquina
$('#btn-agregarMaquina').click(function(){
  const nro_admin = $('#buscadorMaquina').val();
  const existeEnLista = $('#listaMaquinas li .nro_admin').filter(function(){return $(this).text() == nro_admin;}).length > 0;
  if(existeEnLista){
    $('#buscadorMaquina').setearElementoSeleccionado(0 , "");//se limpia
    return;
  }
  $.get("/islas/obtenerMTMReducido/" + $('#buscadorMaquina').obtenerElementoSeleccionado(), function(data) {
    agregarMaquina(data.id_maquina,data.nro_admin,data.marca,data.modelo);
    $('#buscadorMaquina').setearElementoSeleccionado(0 , "");//se limpia buscador
  });
});

$(document).on('click','.borrarMaquina',function(){
  $(this).parent().parent().remove();
});

$('#buscadorCasino').on('change' , function (){
  if($(this).val() == 0) return;
  $.get("/sectores/obtenerSectoresPorCasino/" + $(this).val(), function(data){
    $('#buscadorSector option').remove();
    $('#buscadorSector').append($('<option value="0">-Todos los sectores-</option>'));
    for (let i = 0; i < data.sectores.length; i++) {
      $('#buscadorSector').append($('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion));
    }
  });
 })

//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  let size = 10;
  //Fix error cuando librería saca los selectores
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;

  $.ajax({
    type: 'POST',
    url: 'http://' + window.location.host + '/islas/buscarIslas',
    data: {
      nro_isla: $('#buscadorNroIsla').val(),
      cantidad_maquinas: $('#buscadorCantMaquinas').val(),
      casino: $('#buscadorCasino').val(),
      sector: $('#buscadorSector').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    },
    dataType: 'json',
    success: function (resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      $('#cuerpoTabla tr').remove();

      for (var i = 0; i < resultados.data.length; i++){
        $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i],resultados.data[i].sector));
      }

      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function (data) {
      const response = JSON.parse(data.responseText);
      if(typeof response.cantidad_maquinas !== 'undefined'){
        mostrarErrorValidacion($('#buscadorCantMaquinas') , response.cantidad_maquinas[0] , true);
      }
    }
  });
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
	$('#modalAyuda').modal('show');
});

$('#btn-nuevo').click(function(e){
  $('#mensajeExito').hide();
  e.preventDefault();
  habilitarControles(true);
  $('#btn-guardar').val("nuevo");
  $('#btn-guardar').show();
  $('.movimientos').hide();
  $('#modalIsla .modal-title').text('| NUEVA ISLA');
  $('#modalIsla .modal-header').attr('style','background-color: #6dc7be; color: #fff');
  $('#btn-guardar').removeClass();
  $('#btn-guardar').addClass('btn btn-successAceptar');
  $('#modalIsla').modal('show');
});

/* DIVIDIR ISLAS */
$(document).on('click','.dividir',function(){
    const nro_isla = $(this).attr('data-isla');
    const id_casino = $(this).attr('data-casino');
    const id_isla = $(this).val();

    //Setear datos al boton de enviar
    $('#btn-aceptarDividir').val(id_isla);
    $('#btn-aceptarDividir').attr('data-casino', id_casino);
    $('#btn-aceptarDividir').attr('data-isla', nro_isla);

    //Limpiar el modal
    $('.subisla').not('#moldeSubisla').remove();
    $('#selectSubisla option').remove();

    //Buscador de máquinas
    $('#inputMaquina').generarDataList('/maquinas/buscarMaquinaPorNumeroMarcaYModelo/' + id_casino, "resultados","id_maquina" ,"nro_admin" , 2, true);

    $('#moldeSubisla .selectSector option').remove();

    //Setear sectores
    $.get('sectores/obtenerSectoresPorCasino/' + id_casino, function(data){
      for (let i = 0; i < data.sectores.length; i++) {
        const option = $('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion);
        $('#moldeSubisla .selectSector').append(option);
      }
    });

    $.get('islas/obtenerIsla/'+ id_isla, function(data) {
      //Setear datos de la isla
      $('#d_nro_isla').val(data.isla.nro_isla);
      $('#d_casino').val(data.casino.nombre);
      $('#d_sector').val(data.sector.descripcion);
    });

    $.get('islas/listarMaquinasPorNroIsla/' + nro_isla + '/' + id_casino, function(data) {
      //Setear la nueva cantidad de subislas
      $('#d_maquinas').val(data.cantidad_maquinas);

      //Generar las subislas existentes en el modal
      for (let i = 0; i < data.islas.length; i++) {
        const subisla = generarSubisla(i, data.islas[i]);
        $('#subislas').append(subisla);
        $('#selectSubisla').append($('<option>').val(i).text(i));
      }

      //Generar la nueva subisla en el modal
      const subisla = generarSubisla(data.islas.length);
      subisla.find('.contenedorSI').css('border-color','#FF9100');
      $('#subislas').append(subisla);
      $('#selectSubisla').append($('<option>').val(data.islas.length).text(data.islas.length));
    });
    $('#modalDividirIsla').modal('show');
});

$('#modalDividirIsla').on('hidden.bs.modal',function(){
  $('#inputMaquina').setearElementoSeleccionado(0 , "");
});

$('#btn-agregarMaquinaSI').click(function(e){
  const indice = $('#selectSubisla').val();
  const id_maquina = $('#inputMaquina').obtenerElementoSeleccionado();
  const existeMaquina = $(`#subislas tr.maquinaSI[data-maquina="${id_maquina}"]`).not('#moldeMaquinaSI').length > 0;
  if(existeMaquina){
    mostrarErrorValidacion($('#inputMaquina') , 'Ya existe la maquina elegida.' , true);
    return;
  }
  $.get('maquinas/obtenerMTM/'+ id_maquina , function(data){
    $('#inputMaquina').setearElementoSeleccionado(0 , "");
    $(`.subisla[data-sub="${indice}"] table tbody`).append(generarMaquinaSI(indice, data.maquina));
    $('#d_maquinas').val(parseInt($('#d_maquinas').val())+1);
  });
});

function generarSubisla(indice, subisla) {
  const moldeSubisla = $('#moldeSubisla').clone().show().removeAttr('id').attr('data-sub', indice);

  moldeSubisla.find('span').text(indice);

  if (typeof subisla === "undefined") {
    moldeSubisla.attr('id', 0);
    return moldeSubisla;
  }

  moldeSubisla.attr('id',subisla.id_isla);
  moldeSubisla.find('input.codigo_subisla').val(subisla.codigo);
  moldeSubisla.find('.selectSector').val(subisla.id_sector);
  
  for (let i = 0; i < subisla.maquinas.length; i++) {
    const maquina = generarMaquinaSI(indice, subisla.maquinas[i]);
    moldeSubisla.find('table tbody').append(maquina);
  }
  
  return moldeSubisla;
}

function mod(x,m){//https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Remainder
  return ((x % m) + m) % m;
}

function generarMaquinaSI(indice, maquina) {
  const moldeMaquinaSI = $('#moldeMaquinaSI').clone().show().removeAttr('id');
  moldeMaquinaSI.attr('data-maquina', maquina.id_maquina);
  moldeMaquinaSI.find('.nro_admin').text(maquina.nro_admin);
  moldeMaquinaSI.find('.marca_juego').text(maquina.marca_juego);
  moldeMaquinaSI.find('button').val(indice);
  return moldeMaquinaSI;
}

function moverFila(fila,mover){
  const nro_subisla_actual = parseInt(fila.find('button').eq(0).val());
  const cantidad_subislas =  $('#subislas .subisla').length;
  const nuevapos = mod(nro_subisla_actual + mover,cantidad_subislas);
  fila.find('button').val(nuevapos);
  fila.remove();
  $('#subislas').find(`div[data-sub="${nuevapos}"]`).find('table tbody').append(fila);
}

$(document).on('click','.mover_izquierda', function() {
  moverFila($(this).closest('tr'),-1);
});

$(document).on('click','.mover_derecha', function() {
  moverFila($(this).closest('tr'),+1);
});

$(document).on('click','.borrarMaquinaSI', function() {
  $(this).parent().parent().remove();
  $('#d_maquinas').val(parseInt($('#d_maquinas').val())-1);
});

$('#btn-aceptarDividir').click(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

    const subislas = $('.subisla').not('#moldeSubisla').map(function(i){
        return {
          id_sector: $(this).find('.selectSector').val() ,
          id_isla: $(this).attr('id'),
          codigo: $(this).find('input.codigo_subisla').val(),
          maquinas: $(this).find('.maquinaSI').not('#moldeMaquinaSI').map(function(){
            return {id_maquina: $(this).attr('data-maquina')};
          }).toArray(),
        };
    }).toArray();

    $.ajax({
        type: 'POST',
        url: 'islas/dividirIsla',
        data: {
          nro_isla: $(this).attr('data-isla'),
          id_casino: $(this).attr('data-casino'),
          subislas: subislas,
        },
        dataType: 'json',
        success: function (data) {
          $('#modalDividirIsla').modal('hide');
          mensajeExito("modificar","Se ha dividido correctamente la isla.");
          $('#btn-buscar').trigger('click');
        },
        error: function (data) {
          console.log('Error: ', data);
          const response = JSON.parse(data.responseText);
          const subislas = $('.subisla').not('#moldeSubisla');
          subislas.each(function(idx){
            const codigo = response[`subislas.${idx}.codigo`];
            if(typeof codigo !== 'undefined'){
              mostrarErrorValidacion($(this).find('input.codigo_subisla'),codigo.join(', '),true);
            }
          });
        }
    });
});

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){
  $('#modalIsla .modal-title').text('| VER MÁS');
  $('#modalIsla .modal-header').attr('style','background: #4FC3F7');
  $('.movimientos').show();
  $.get( "/islas/obtenerIsla/" + $(this).val(), function(data){
    mostrarIsla(data.isla,data.sector,data.maquinas);
    habilitarControles(false);
    $('#btn-guardar').hide();
    $('#modalIsla').modal('show');
  });
});

//Modal para modificar una ISLA
$(document).on('click','.modificar',function(){
  $('#modalIsla .modal-title').text('| MODIFICAR ISLA');
  $('#modalIsla .modal-header').attr('style','background: #ff9d2d');
  $('#mensajeExito').hide();
  $('#btn-guardar').addClass('btn btn-warningModificar');
  $('#btn-guardar').show();
  $('.movimientos').show();

  const id_isla = $(this).val();
  generarHistorialMov(id_isla);

  $.get("/islas/obtenerIsla/" + id_isla, function(data){
    mostrarIsla(data.isla,data.sector,data.maquinas);
    habilitarControles(true);
    $('#casino').attr('disabled',true);//Deshabilito cambiar casino al modificar... para no vincular maquinas de un casino con otro
    $('#btn-guardar').val("modificar");
    $('#modalIsla').modal('show');
  });
});

//Borrar Isla y remover de la tabla
$(document).on('click','.eliminar',function(){
  $('#btn-eliminarModal').val($(this).val());
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  const id_isla = $(this).val();
  $.ajax({
    type: "DELETE",
    url: "islas/eliminarIsla/" + id_isla,
    success: function (data) {
      $('#btn-buscar').click();
      $('#modalEliminar').modal('hide');
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
});

//Crear nueva Isla / actualizar si existe
$('#btn-guardar').click(function (e) {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

    const maquinas = $('#listaMaquinas li').map(function(){ return $(this).val(); }).toArray();
    const historial = $('.columnaMovimientos .unMovimiento').map(function(){
      return {
        id_log_isla: $(this).find('span').attr('value'),
        id_estado_relevamiento: $(this).find('.estadosMovimientos option:selected').val(),
      };
    }).toArray();

    const state = $('#btn-guardar').val();
    $.ajax({
      type: 'POST',
      url: state == "modificar"? '/islas/modificarIsla': '/islas/guardarIsla',
      data: {
        id_isla: $('#id_isla').val(),
        nro_isla: $('#nro_isla').val(),
        casino: $('#casino').val(),
        sector: $('#sector').val(),
        codigo: $('#ncodigo').val(),
        maquinas: maquinas,
        historial: historial,
      },
      dataType: 'json',
      success: function (data) {
        if (state == "nuevo"){
          mensajeExito("nuevo","Se ha creado correctamente la isla.");
        }else if(state == "modificar"){
          mensajeExito("modificar","Se ha modificado la isla correctamente.");
        }
        $('#modalIsla').modal('hide');
        $('#btn-buscar').click();
      },
      error: function (data) {
        const response = JSON.parse(data.responseText);
        if(typeof response.nro_isla !== 'undefined'){
          mostrarErrorValidacion($('#nro_isla') , response.nro_isla[0] , true);
        }
        if(typeof response.casino !== 'undefined'){
          mostrarErrorValidacion($('#casino') , response.casino[0] , true);
        }
        if(typeof response.sector !== 'undefined'){
          mostrarErrorValidacion($('#sector') , response.sector[0] , true);
        }
        if(typeof response.codigo !== 'undefined'){
          mostrarErrorValidacion($('#ncodigo') , response.codigo[0] , true);
        }
      }
    });
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function generarFilaTabla(isla,sector){
  const fila = $('#moldeFilaTabla').clone().removeAttr('id');
  const codigo = isla.codigo == null ? '-' :  isla.codigo;
  fila.attr('id','isla' + isla.id_isla);
  fila.find('.nro_isla').text(isla.nro_isla);
  fila.find('.codigo').text(isla.id_casino != 3? codigo : ' - ');
  fila.find('.casino').text(isla.casino);
  fila.find('.sector').text(sector);
  fila.find('.cantidad_maquinas').text(isla.cantidad_maquinas);
  fila.find('button').val(isla.id_isla).attr('data-isla',isla.nro_isla).attr('data-casino',isla.id_casino);
  if(isla.id_casino == 3){
    fila.find('.dividir').remove();
  }
  return fila;
}

function mostrarIsla(isla,sector,maquinas){
  $('#id_isla').val(isla.id_isla);
  $('#nro_isla').val(isla.nro_isla);
  $('#cant_maquinas').val(isla.cantidad_maquinas);
  $('#casino').val(sector.id_casino).trigger('change', [sector.id_sector]);
  $('#ncodigo').val(isla.codigo);
  $('#orden').val(isla.orden).closest('div').toggle(isla.id_casino == 3);
  $('#nro_islote').val(isla.nro_islote).closest('div').toggle(isla.id_casino == 3);

  for (var i = 0; i < maquinas.length; i++) {
    agregarMaquina(maquinas[i].id_maquina, maquinas[i].nro_admin, maquinas[i].marca, maquinas[i].modelo);
  }
}

function agregarMaquina(id_maquina, nro_admin,nombre,modelo){
  const fila = $('#moldeMaquina').clone().removeAttr('id');
  fila.find('.nro_admin').text(nro_admin);
  fila.find('.nombre').text(nombre);
  fila.find('.modelo').text(modelo);
  fila.val(id_maquina);
  $('#listaMaquinas').append(fila);
}

function habilitarControles(valor){
  $('#modalIsla .modal-body').find('input,select,button').prop('readonly',!valor).prop('disabled',!valor);
}

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaResultados .activa').attr('value');
  const orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function mensajeExito(modo,mensaje){
  $('#mensajeExito h3').text('ÉXITO');
  $('#mensajeExito p').text(mensaje);
  $('#mensajeExito .cabeceraMensaje').toggleClass('modificar',modo=="modificar");
  $('#mensajeExito').show();
}