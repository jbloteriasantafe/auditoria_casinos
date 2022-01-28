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

  limpiarModal();
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
  limpiarModal();
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
    }else{
      $('#buscadorMaquina').generarDataList('http://' +window.location.host + '/maquinas/buscarMaquinaPorNumeroMarcaYModelo/' + id_casino, "resultados","id_maquina" ,"nro_admin" , 2, true);
      $('#buscadorMaquina').setearElementoSeleccionado(0 , "");
      $('#sector option').remove();
      $.get('http://' + window.location.host + "/sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
        for (let i = 0; i < data.sectores.length; i++) {
          $('#sector').append($('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion))
        }
        $('#sector').val(id_sector);
      })
    }
})

/**************************************************
  TODOS LOS EVENTOS DEL INPUT MÁQUINA
**************************************************
 Evento para pasarle el ID de la isla en el datalist al input
    Cada vez que se hace el input se controla si un option del datalist fue seleccionado.
    Si fue seleccionado se compara con el input para sacarle el id.
*/

//Botón Cancelar input Islas
$('#cancelarMaquina').click(function(){
    $('#buscadorMaquina').prop("readonly", false); //Se habilita el input
    $('#buscadorMaquina').setearElementoSeleccionado(0 , "");
});

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
$('.agregarMaquina').click(function(){
  if(existeEnDataList($('#buscadorMaquina').val())){
    $('#buscadorMaquina').setearElementoSeleccionado(0 , "");//se limpia
    return;
  }
  $.get("/islas/obtenerMTMReducido/" + $('#buscadorMaquina').obtenerElementoSeleccionado(), function(data) {
    agregarMaquina(data.id_maquina,data.nro_admin,data.marca,data.modelo);
    $('#buscadorMaquina').setearElementoSeleccionado(0 , "");//se limpia buscador
  });
});

function existeEnDataList(nro_admin){
  const maqs = $('#listaMaquinas li');
  for(let i = 0;i<maqs.length;i++){
    if(maqs.eq(i).find('.nro_admin').text() == nro_admin) return true;
  }
  return false;
}

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
  const formData = {
    nro_isla: $('#buscadorNroIsla').val(),
    cantidad_maquinas: $('#buscadorCantMaquinas').val(),
    casino: $('#buscadorCasino').val(),
    sector: $('#buscadorSector').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
    type: 'POST',
    url: 'http://' + window.location.host + '/islas/buscarIslas',
    data: formData,
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
    $('#inputMaquina').generarDataList('http://' + window.location.host + '/maquinas/buscarMaquinaPorNumeroMarcaYModelo/' + id_casino, "resultados","id_maquina" ,"nro_admin" , 2, true);

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
        cantidad_subislas = parseInt(data.islas.length) + 1;

        $('#d_maquinas').val(data.cantidad_maquinas);

        //Generar las subislas existentes en el modal
        for (let i = 1; i < cantidad_subislas; i++) {
            const subisla = generarSubisla(i, data.islas[i-1]);
            $('#subislas').append(subisla);
        }

        //Generar la nueva subisla en el modal
        const subisla = generarSubisla(cantidad_subislas);
        subisla.find('.contenedorSI').css('border-color','#FF9100');
        $('#subislas').append(subisla);

        $('#selectSubisla').append($('<option>').val(cantidad_subislas).text(cantidad_subislas));
    });
    $('#modalDividirIsla').modal('show');
});

$('#modalDividirIsla').on('hidden.bs.modal',function(){
  $('#inputMaquina').setearElementoSeleccionado(0 , "");
});

$('#btn-agregarMaquina').click(function(e){
  const indice = $('#selectSubisla').val();
  const id_maquina = $('#inputMaquina').obtenerElementoSeleccionado();
  if(existeMaquina(id_maquina)){
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

function generarMaquinaSI(indice, maquina) {
  const moldeMaquinaSI = $('#moldeMaquinaSI').clone().show().removeAttr('id');
  moldeMaquinaSI.attr('data-maquina', maquina.id_maquina);
  moldeMaquinaSI.find('.nro_admin').text(maquina.nro_admin);
  moldeMaquinaSI.find('.marca_juego').text(maquina.marca_juego);
  moldeMaquinaSI.find('button.mover_izquierda').val(indice);
  moldeMaquinaSI.find('button.mover_derecha').val(indice);
  return moldeMaquinaSI;
}

var cantidad_subislas = 3;

$(document).on('click','.mover_izquierda', function() {
  const tr = $(this).closest('tr');
  const nro_subisla_actual = parseInt($(this).val());
  const nro_nueva_subisla =  nro_subisla_actual >= 2? nro_subisla_actual - 1 : cantidad_subislas;

  $(this).val(nro_nueva_subisla);
  $(this).siblings('button').val(nro_nueva_subisla);

  tr.remove();
  $('#subislas').find(`div[data-sub="${nro_nueva_subisla}"]`).find('table tbody').append(tr);
});

$(document).on('click','.mover_derecha', function() {
  const tr = $(this).closest('tr');
  const nro_subisla_actual = parseInt($(this).val());
  const nro_nueva_subisla =  (nro_subisla_actual < cantidad_subislas)? nro_subisla_actual + 1 : 1;

  $(this).val(nro_nueva_subisla);
  $(this).siblings('button').val(nro_nueva_subisla);
  
  tr.remove();
  $('#subislas').find(`div[data-sub="${nro_nueva_subisla}"]`).find('table tbody').append(tr);
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
        url: 'islas/actualizarListaMaquinas',
        data: {
          nro_isla: $(this).attr('data-isla'),
          id_casino: $(this).attr('data-casino'),
          detalles: subislas,
        },
        dataType: 'json',
        success: function (data) {
            $('#modalDividirIsla').modal('hide');
            //exito
            $('#mensajeExito h3').text('ÉXITO DE MODIFICACIÓN');
            $('#mensajeExito .cabeceraMensaje').addClass('modificar');
            $('#mensajeExito p').text("Se ha dividido correctamente la isla.");
            $('#mensajeExito').show();
            //actualizo tabla
            $('#btn-buscar').trigger('click');
        },
        error: function (data) {
          console.log('Error: ', data);
          var response = JSON.parse(data.responseText);

          var filaError = -1;
          var i= 0;
          var subislasModal = $('.subisla').not('#moldeSubisla');

          $(subislasModal).each(function(){
            if(typeof response['codigo.'+ i] !== 'undefined'){
              filaError=i;
              mostrarErrorValidacion($(this).find('input.codigo_subisla'),response['codigo.'+ i][0],true);
            }
            if(typeof response['duplicado.'+ i] !== 'undefined'){
              filaError=i;
              mostrarErrorValidacion($(this).find('input.codigo_subisla'),response['duplicado.'+ i][0],true);
            }
            if(typeof response['sector.'+ i] !== 'undefined'){
              filaError=i;
              mostrarErrorValidacion($(this).find('.selectSector'),response['sector.'+ i][0],true);
            }

            i++;
          })
          if(filaError >= 0){
            var pos = subislasModal.eq(filaError).offset().top;
            $("#modalDividirIsla").animate({ scrollTop: pos }, "slow");
          }
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
        $('#isla' + id_isla).remove();
        $("#tablaIslas").trigger("update");
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

    const maquinas = $('#listaMaquinas li').map(function(){
      return $(this).val();
    }).toArray();

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
        cant_maquinas:  $('#listaMaquinas li').length,
        casino: $('#casino').val(),
        sector: $('#sector').val(),
        codigo: $('#ncodigo').val(),
        maquinas: maquinas,
        historial: historial,
      },
      dataType: 'json',
      success: function (data) {
        const isla = generarFilaTabla(data.isla , data.sector.descripcion);

        if (state == "nuevo"){ //Si está agregando
          $('#cuerpoTablaIsla').append(isla);
          $('#mensajeExito h3').text('ÉXITO DE CARGA');
          $('#mensajeExito p').text("Se ha creado correctamente la isla.");
          $('#mensajeExito .cabeceraMensaje').removeClass('modificar');
        }else{ //Si está modificando
          $('#isla' + data.isla.id_isla).replaceWith(isla);
          $('#mensajeExito h3').text('ÉXITO DE MODIFICACIÓN');
          $('#mensajeExito p').text("Se ha modificado la isla correctamente.");
          $('#mensajeExito .cabeceraMensaje').addClass('modificar');
        }
        $('#mensajeExito').show();
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

function existeMaquina(id_maquina){ // devuelve true si ya existe esa maquina entre las subisla
  var subislasModal = $('.subisla').not('#moldeSubisla');
  var bandera = false;
  $.each(subislasModal, function(i){
      var maquinasModal = $(this).find('table tbody .maquinaSI').not('#moldeMaquinaSI');
      $.each(maquinasModal, function(i) {//true -> ya existe la maquina en alguna sub isla
          if(parseInt($(this).attr('data-maquina')) === parseInt(id_maquina)){
            bandera = true;
          }
      });
  });
  return bandera;
}

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

function limpiarModal(){
  ocultarErrorValidacion($('#modalIsla input,#modalIsla select'));
  $('#frmIsla').trigger('reset');
  $('#id_isla').val(0);
  $('#listaMaquinas li').remove();
  $('#nro_isla').removeClass('alerta');
  $('#cant_maquinas').removeClass('alerta');
  $('#alerta-nro_isla').text('');
  $('#alerta-nro_isla').hide();
  $('#alerta-cant_maquinas').text('');
  $('#alerta-cant_maquinas').hide();
  $('#sector option').remove();
  $('#sector').append($('<option>').val(0).text('-Sectores del casino-'));
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
  $('#nro_isla').prop('readonly',!valor);
  $('#cant_maquinas').prop('readonly',!valor);
  $('#buscadorMaquina').prop('readonly',!valor);
  $('#ncodigo').prop('readonly',!valor);
  $('#casino').prop('disabled',!valor);
  $('#sector').prop('disabled',!valor);
  $('#nro_islote').prop('readonly',!valor);
  $('#orden').prop('readonly',!valor);
}

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}
