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
  //@TODO: permitir enviar el nro_islote y orden en la creación, hasta que no tenga eso, lo escondo.
  $('#nro_islote,#orden').parent().toggle(false);//.toggle(id_casino == 3);
  $('#buscadorMaquina').generarDataList('/maquinas/buscarMaquinaPorNumeroMarcaYModelo/' + id_casino, "resultados","id_maquina" ,"nro_admin" , 2, true);
  $('#buscadorMaquina').setearElementoSeleccionado(0 , "");
  $('#sector option').remove();
  $.get("/sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
    for (let i = 0; i < data.sectores.length; i++) {
      $('#sector').append($('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion))
    }
    $('#sector').val(id_sector);
  });
})

function generarHistorialMov(estados,historial){//Esto habria que deprecarlo
  const estadosMov = $('<select>').addClass('form-control estadosMovimientos');
  $('.columnaMovimientos').children().remove();
  for(let i = 0;i<estados.length;i++){
    estadosMov.append($('<option>').val(estados[i].id_estado_relevamiento).text(estados[i].descripcion));
  }
  for(let i = 0;i<historial.length;i++){
    $('.columnaMovimientos')
    .append($('<div>').addClass('unMovimiento')
    .append($('<div>').addClass('col-md-4').css('padding-bottom','15px')
      .append($('<span>').attr('value',historial[i].id_log_isla).text(historial[i].fecha)))
    .append($('<div>').addClass('col-md-8').css('padding-bottom','15px')
      .append(estadosMov.clone().val(historial[i].id_estado_relevamiento))))
    .append($('<br>'));
  }
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
    url: '/islas/buscarIslas',
    data: {
      nro_isla: $('#buscadorNroIsla').val(),
      cantidad_maquinas: $('#buscadorCantMaquinas').val(),
      id_casino: $('#buscadorCasino').val(),
      id_sector: $('#buscadorSector').val(),
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
  if($('#casino option').length == 2){//Solo tiene el "Seleccione" y el casino 
    $('#casino').val($('#casino option').eq(1).val()).change();
  }
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
    generarHistorialMov(data.estados,data.historial);
    habilitarControles(false);
    $('#btn-guardar').hide();
    $('#modalIsla').modal('show');
  });
});

//Modal para modificar una ISLA
//Cambio el nombre de la clase de "modificar" a "modificarIsla" porque "modificar" clashea con la clase asignada al mensajeExito
$(document).on('click','.modificarIsla',function(){
  $('#modalIsla .modal-title').text('| MODIFICAR ISLA');
  $('#modalIsla .modal-header').attr('style','background: #ff9d2d');
  $('#mensajeExito').hide();
  $('#btn-guardar').addClass('btn btn-warningModificar');
  $('#btn-guardar').show();
  $('.movimientos').show();

  const id_isla = $(this).val();

  $.get("/islas/obtenerIsla/" + id_isla, function(data){
    mostrarIsla(data.isla,data.sector,data.maquinas);
    generarHistorialMov(data.estados,data.historial);
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
        id_casino: $('#casino').val(),
        id_sector: $('#sector').val(),
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
  //@TODO: permitir modificarlo cuando se implemente bien lo de nro_islote / orden
  $('#orden').val(isla.orden).closest('div').toggle(isla.id_casino == 3).attr('disabled','true');
  $('#nro_islote').val(isla.nro_islote).closest('div').toggle(isla.id_casino == 3).attr('disabled','true');

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

$('#btn-islotes').click(function(e){
  e.preventDefault();
  $('#casinoIslotes').val($('#casinoIslotes option').eq(0).val()).change()
  .attr('disabled',$('#casinoIslotes option').length == 1);//Si tiene 1 solo deshabilito seleccionar 
  $('#modalAsignarIslotes').modal('show');
});

function crearIslote(nro_islote,islas){
  const islote = $('#moldeIslote').clone().removeAttr('id');
  islote.find('.nro_islote').empty().append(nro_islote == 'SIN_NRO_ISLOTE'? '&nbsp;' : nro_islote);
  for(const nro_isla_idx in islas){
    const nro_isla = islas[nro_isla_idx];
    const isla = $('#moldeIslaIslote').clone().removeAttr('id');
    isla.find('.nro_isla').text(nro_isla);
    islote.find('.islas').append(isla);
  }
  return islote;
}

$('#casinoIslotes').change(function(e){
  e.preventDefault();
  $('#sectores').empty();
  $.get('/islas/buscarIslotesPorCasino/'+$(this).val(),function(sectores){
    for(const id_sector in sectores){
      const sector = $('#moldeSector').clone().removeAttr('id');
      sector.find('.nombre_sector').text(sectores[id_sector]['descripcion']);
      const islotes = sectores[id_sector]['islotes'];
      for(const nro_islote in islotes){
        sector.find('.islotes').append(crearIslote(nro_islote,islotes[nro_islote]));
      }
      $('#sectores').append(sector);
    }
  });
});

$(document).on('mousedown','.asignar_isla',function(e){
  if($('.seleccionado').length == 0 && e.which == 1){
    e.preventDefault();//evitar que seleccione texto
    $(this).addClass('seleccionado').closest('.asignar_islote').addClass('sombreado');
  }
});

$(document).on('mousedown','.asignar_islote',function(e){
  //No permito seleccionar el "SIN_NRO_ISLOTE" ya que es solo para mostrar islas sin asignar
  if($('.seleccionado').length == 0 && e.which == 1 && $(this).text().trim().length > 0){
    e.preventDefault();//evitar que seleccione texto
    $(this).addClass('seleccionado').closest('.asignar_sector').addClass('sombreado');
  }
});

$(document).on('mouseenter','#sectores div',function(){
  if($('.seleccionado').length == 0) return;
  let div = $();
  if($('.seleccionado').hasClass('asignar_isla')){
    div = $(this).filter(".asignar_islote");
  }
  if($('.seleccionado').hasClass('asignar_islote')){
    div = $(this).filter('.asignar_sector');
  }
  div.addClass('sombreado');
});

$(document).on('mouseleave','.sombreado',function(){
  $(this).removeClass('sombreado');
});

function mover_seleccionado_a_div(seleccionado,div,divpadre,x,y){
  /*
  sectores
    asignar_sector (divpadre)
      hijos
        asignar_islote (div),(divpadre)
          hijos
            asignar_isla (div)
  */

  const insertar = function(div_base){
    //No hacerlo si es el mismo sino al hacer detach() no puede insertarAfter/Before (no tiene padre) y termina borrandose
    if(div_base[0] == seleccionado[0]) return;
    const rect = div_base[0].getBoundingClientRect();//Averiguo si fue a la izquierda o derecha del elemento
    const mitad = (rect.left+rect.right)/2.;
    if(x >= mitad) seleccionado.detach().insertAfter(div_base);
    else           seleccionado.detach().insertBefore(div_base); 
  }
  const slot_a_insertar = divpadre.find('.hijos').first();
  //Si solto el click adentro del div
  if(div.length == 1){
    insertar(div);
  }
  //Si solto el click en el divpadre pero por fuera de cualquier div
  else if(div.length == 0 && slot_a_insertar.children().length > 0){
    //Encuentro el div mas cercano
    let min_dist = Infinity;
    let obj = null;
    slot_a_insertar.children().each(function(){
      const obj_rect = this.getBoundingClientRect();
      const d = distancia_a_caja(obj_rect,x,y);
      if(d < min_dist){
        min_dist = d;
        obj = this;
      }
    });
    insertar($(obj));
  }
  //Si solto el click en un divpadre sin hijos
  else if(div.length == 0 && divpadre.length == 1 && slot_a_insertar.children().length == 0){
    slot_a_insertar.append(seleccionado.detach());
  }
}

function movidoReciente(obj){
  obj.addClass('movido_reciente');
  setTimeout(function(){
    obj.removeClass('movido_reciente');//le saco la clase para que pueda volver a hacer el efecto 
  },2000);
}

$(document).on('mouseup','*',function(e){
  const seleccionado = $('.seleccionado');
  if(seleccionado.length == 0 || e.which != 1) return;
  const elementos_en_el_mouse = $(document.elementsFromPoint(e.pageX,e.pageY));
  const isla_mouse_arriba = elementos_en_el_mouse.filter(function() {//solto en una isla
    return $(this).hasClass('asignar_isla');
  }).eq(0);
  const islote_mouse_arriba = elementos_en_el_mouse.filter(function() {//solto en la lista de islas de un islote
    return $(this).hasClass('asignar_islote')
  }).eq(0);
  const sector_mouse_arriba = elementos_en_el_mouse.filter(function(){//solto en un sector
    return $(this).hasClass('asignar_sector');
  }).eq(0);

  if(seleccionado.hasClass('asignar_isla') && (isla_mouse_arriba.length + islote_mouse_arriba.length) > 0){//Si encontro isla y/o islote
    mover_seleccionado_a_div(seleccionado,isla_mouse_arriba,islote_mouse_arriba,e.pageX,e.pageY);
  }
  else if(seleccionado.hasClass('asignar_islote') && (islote_mouse_arriba.length + sector_mouse_arriba.length) > 0){//Si encontro islote y/o sector
    mover_seleccionado_a_div(seleccionado,islote_mouse_arriba,sector_mouse_arriba,e.pageX,e.pageY);
  }

  movidoReciente(seleccionado);
  $('.seleccionado').removeClass('seleccionado');
  $('.sombreado').removeClass('sombreado');
})

function distancia_a_caja(rect,px,py){
  //Retorna la distancia de (px,py) a una caja. Basado en https://www.iquilezles.org/www/articles/distfunctions2d/distfunctions2d.htm
  const centerx = (rect.left+rect.right)*0.5;
  const centery = (rect.top+rect.bottom)*0.5;
  const lx = Math.abs(rect.left-centerx);
  const ly = Math.abs(rect.bottom-centery);
  const dx = Math.abs(px-centerx) - lx;
  const dy = Math.abs(py-centery) - ly;
  const length = function(x,y){ return Math.sqrt(x*x+y*y); }
  return length(Math.max(dx,0.),Math.max(dy,0.)) + Math.min(Math.max(dx,dy),0.);
}

$('#agregarIslote').keyup(function(e){
  e.preventDefault();
  if(e.which == 13){//Agrego y limpio si toco enter
    if($(this).val() == parseInt($(this).val())){
      const islote = crearIslote($(this).val(),[]);
      $('#sectores .asignar_sector').eq(0).find('.islotes').prepend(islote);
      movidoReciente(islote);
    }
    $(this).val("").change();
  }
});