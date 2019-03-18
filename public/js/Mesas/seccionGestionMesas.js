$(document).ready(function() {
  $('#barraGestionMesas').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Gestionar Mesas');
  $('#barraGestionMesas').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#barraGestionMesas').addClass('opcionesSeleccionado');


  $('#collapseFiltros #F_Nro').val("");
  $('#collapseFiltros #F_Sector').val("");
  $('#collapseFiltros #F_Casino').val("0");
  $('#collapseFiltros #F_Tipo').val("0");
  $('#collapseFiltros #F_Juego').val("");

  $('#mensajeExito').hide();
  $('#mensajeError').hide();

    //PAGINACION
    $('#btn-buscarMesas').trigger('click',[1,10,'mesa_de_panio.nro_mesa','desc']);


}); //fin document ready

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

//Busqueda de MESAS
$('#btn-buscarMesas').click(function(e,pagina,page_size,columna,orden){

  $('#mensajeExito').hide();
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();


  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }
  else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaMesas .activa').attr('value'),orden: $('#tablaMesas .activa').attr('estado')} ;

  if(sort_by == null){ // limpio las columnas
    $('#tablaMesas th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    nro_mesa: $('#F_Nro').val(),
    id_juego: $('#F_Juego').val(),
    casino: $('#F_Casino').val(),
    id_tipo_mesa: $('#F_Tipo').val(),
    id_sector: $('#F_Sector').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: 'POST',
      url: 'mesas/buscarMesas',
      data: formData,
      dataType: 'json',

      success: function (data) {

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.total,clickIndice);
          $('#cuerpoTablaMesas tr').remove();
          for (var i = 0; i < data.data.length; i++) {
              var filaMesa = generarFilaTabla(data.data[i]);
              $('#cuerpoTablaMesas').append(filaMesa);
          }

          $('#herramientasPaginacion').generarIndices(page_number,page_size,data.total,clickIndice);
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});

$(document).on('click','#tablaMesas thead tr th[value]',function(e){

  $('#tablaMesas th').removeClass('activa');

  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    console.log('1');
    $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{

    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
        $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaMesas th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndice(e,pageNumber,tam){

  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaMesas .activa').attr('value');
  var orden = $('#tablaMesas .activa').attr('estado');
  $('#btn-buscarMesas').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(mesa){

  var fila=$('#moldeFilaMesa').clone();

  fila.removeAttr('id');
  fila.attr('id',  mesa.id_mesa_de_panio);

  fila.find('.nroMesa').text(mesa.nro_mesa);
  fila.find('.juegoMesa').text(mesa.nombre_juego);
  fila.find('.sectorMesa').text(mesa.nombre_sector);
    fila.find('.casinoMesa').text(mesa.nombre);
  fila.find('.infoMesa').val(mesa.id_mesa_de_panio);
  fila.find('.modificarMesa').val(mesa.id_mesa_de_panio);
  fila.find('.eliminarMesa').val(mesa.id_mesa_de_panio);
  fila.find('.eliminarMesa').attr('data-casino',mesa.id_casino);
 fila.css('display', 'block');
  //fila.show();

  return fila;

};


//PRESIONA EL BOTON DE NUEVA MESA, ABRE MODAL Y CARGA SELECTS
$('#btn-nueva-mesa').click(function(e){

  e.preventDefault(e);

  ocultarErrorValidacion($('#nombre_mesa'));
  $('#nombre_mesa').val(" ");
  ocultarErrorValidacion($('#nro_mesa'));
  $('#nro_mesa').val(" ");
  ocultarErrorValidacion($('#nro_adm_mesa'));
  $('#nro_adm_mesa').val(" ");
  ocultarErrorValidacion($('#descripcion_mesa'));
  $('#descripcion_mesa').val(" ");

  $('#sector_mesa option').not('.default1').remove();
  $('#sector_mesa').val('0').prop('disabled',true);
  $('#juego_mesa option').not('.default2').remove();
  $('#juego_mesa').val('0').prop('disabled',true);
  $('#moneda_mesa option').not('.default3').remove();
  $('#moneda_mesa').val('0').prop('disabled',true);
  $('#casino_mesa option').not('.default').remove();
  $('#casino_mesa').val('0');
  $('#mensajeExito').hide();

  $.get('mesas/cargarDatos', function(data){

    for (var i = 0; i < data.casinos.length; i++) {
      $('#casino_mesa')
      .append($('<option>')
      .val(data.casinos[i].id_casino)
      .text(data.casinos[i].nombre))
    }

    $('#mensajeErrorAlta').hide();
    $('#modalAltaMesa').modal('show');

  })
});

//dentro del modalde nueva mesa, selecciona un casino y se piden: monedas, sectores y juegos
$(document).on('change','#casino_mesa',function(){

  $('#sector_mesa option').not('.default1').remove();
  $('#sector_mesa').val('0').prop('disabled',false);
  $('#juego_mesa option').not('.default2').remove();
  $('#juego_mesa').val('0').prop('disabled',false);;
  $('#moneda_mesa option').not('.default3').remove();
  $('#moneda_mesa').val('0').prop('disabled',false);


  var casino= $('#casino_mesa').val();

  if (casino != 0){
    $.get('mesas/obtenerDatos/' + casino, function(data){

      for (var i = 0; i < data.juegos.length; i++) {
        $('#juego_mesa')
        .append($('<option>')
        .val(data.juegos[i].id_juego_mesa)
        .text(data.juegos[i].nombre_juego + ' - ' + data.juegos[i].siglas))
      }
      $('#juego_mesa').prop('readonly',false);

      for (var i = 0; i < data.moneda.length; i++) {
        $('#moneda_mesa')
        .append($('<option>')
        .val(data.moneda[i].id_moneda)
        .text(data.moneda[i].descripcion))
      }

      for (var i = 0; i < data.sectores.length; i++) {
        $('#sector_mesa')
        .append($('<option>')
        .val(data.sectores[i].id_sector_mesas)
        .text(data.sectores[i].descripcion))
      }

    })
  }


});


//PRESIONA EL BOTÓN GUARADR DENTRO DEL MODAL DE ALTA MESA
$('#btn-guardar-mesa').click(function(e){

  e.preventDefault();

  $('#mensajeExito').hide();
  var id_casino= $('#casino_mesa').val();
  if($('#moneda_mesa').val() != 0){
    var id_moneda = $('#moneda_mesa').val();
    var mmoneda = 0;
  }else{
    var id_moneda = null;
    var mmoneda = 1;
  }

  var formData = {
    nro_mesa: $('#nro_mesa').val(),
    nro_admin: $('#nro_adm_mesa').val(),
    nombre: $('#nombre_mesa').val(),
    descripcion: $('#descripcion_mesa').val(),
    id_juego_mesa: $('#juego_mesa').val(),
    id_moneda: id_moneda,
    id_sector_mesas: $('#sector_mesa').val(),
    id_casino: id_casino,
    multimoneda: mmoneda,
  }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'POST',
    url: 'mesas/nuevaMesa/' + id_casino,
    data: formData,
    dataType: 'json',

    success: function (data){

      $('#mensajeErrorAlta').hide();
      $('#modalAltaMesa').modal('hide');

      $('#mensajeExito h3').text('ÉXITO DE CREACIÓN');
      $('#mensajeExito p').text('La MESA fue creada correctamente');
      $('#mensajeExito').show();

      var t= $('#herramientasPaginacion').getPageSize();

      //recargo la pág para que aparezca el nuevo movimientos en la tabla de movimientos
      $('#btn-buscarMesas').trigger('click',[1,t,'mesa_de_panio.nro_mesa','desc']);


    },
    error:function(data){

      var response = data.responseJSON.errors;

      if(typeof response.id_casino !== 'undefined'){
        $('#mensajeErrorAlta').show();
      }
      if(typeof response.nombre !== 'undefined'){
          mostrarErrorValidacion($('#nombre_mesa'),response.nombre[0]);
      }
      if(typeof response.nro_admin !== 'undefined'){
          mostrarErrorValidacion($('#nro_adm_mesa'),response.nro_admin[0]);
      }
      if(typeof response.nro_mesa !== 'undefined'){
          mostrarErrorValidacion($('#nro_mesa'),response.nro_mesa[0]);
      }
      if(typeof response.id_moneda !== 'undefined'){
        $('#mensajeErrorAlta').show();
      }
      if(typeof response.descripcion !== 'undefined'){
          mostrarErrorValidacion($('#descripcion_mesa'),response.descripcion[0]);
      }
      if(typeof response.id_juego_mesa !== 'undefined'){
        $('#mensajeErrorAlta').show();
      }
      if(typeof response.id_sector_mesa !== 'undefined'){
        $('#mensajeErrorAlta').show();
      }
    }

  });
});

//PRESIONA EL BOTÓN DE DETALLES EN LA LISTA DE MESAS
//SE ABRE EL MODAL DE DETALLE DE MESA
$(document).on('click','.infoMesa',function(e){

  e.preventDefault();

  var id_mesa= $(this).val();

  $.get('mesas/detalleMesa/'+ id_mesa, function(data){

    $('.detalle_nro').text(data.mesa.nro_mesa);
    $('.detalle_nombre').text(data.mesa.nombre);
    $('.detalle_sector').text(data.sector.descripcion);
    $('.detalle_casino').text(data.casino.nombre);
    $('.detalle_juego').text(data.juego.nombre_juego);
    if(data.moneda == null || data.moneda == 'null' || data.moneda == 'undefined' ){
      $('.detalle_moneda').text('MULTI-MONEDA');
    }else{
      $('.detalle_moneda').text(data.moneda.descripcion);
    }

    $('.detalle_descripcion').text(data.mesa.descripcion);
    $('.detalle_tipo').text(data.tipo_mesa.descripcion);

    console.log(data);
    $('#modalDetalleMesa').modal('show');
  })


})

//PRESIONA EL BOTÓN DE MODIFICAR, EN LA LISTA DE MESAS
//SE ABRE EL MODAL DE MODIFICAR DE MESA Y SE CARGAN LOS SELECTS Y INPUTS
$(document).on('click','.modificarMesa',function(e){

  e.preventDefault();

  ocultarErrorValidacion($('#casinoM'));
  ocultarErrorValidacion($('#sectorM'));
  ocultarErrorValidacion($('#juegoM'));
  ocultarErrorValidacion($('#monedaM'));
  ocultarErrorValidacion($('#numeroM'));
  ocultarErrorValidacion($('#numeroAdmM'));
  ocultarErrorValidacion($('#descripcionM'));
  ocultarErrorValidacion($('#nombreM'));


  $('#mensajeErrorModificacion').hide();
  var id=$(this).val();

  $.get('mesas/detalleMesa/' + id, function(data){

    $('#nombreM').val(data.mesa.nombre);
    $('#casinoM').val(data.casino.id_casino);
    $('#descripcionM').val(data.mesa.descripcion);
    $('#numeroM').val(data.mesa.nro_mesa);
    $('#numeroAdmM').val(data.mesa.nro_admin);

    $('#sectorM option').not('.default1').remove();
    $('#juegoM option').not('.default2').remove();
    $('#monedaM option').not('.default3').remove();


    for (var i = 0; i < data.juegos.length; i++) {

      $('#juegoM')
        .append($('<option>')
        .val(data.juegos[i].id_juego_mesa)
        .text(data.juegos[i].nombre_juego))
    }

    for (var i = 0; i < data.monedas.length; i++) {
      $('#monedaM')
      .append($('<option>')
      .val(data.monedas[i].id_moneda)
      .text(data.monedas[i].descripcion))
    }
    $('#monedaM')
        .append($('<option>')
        .val(0)
        .text('- MULTI-MONEDA -'))

    for (var i = 0; i < data.sectores.length; i++) {
      $('#sectorM')
      .append($('<option>')
      .val(data.sectores[i].id_sector_mesas)
      .text(data.sectores[i].descripcion))
    }

    $('#sectorM option').each(function(){
      if($(this).val() == data.sector.id_sector_mesas){
        $(this).attr('selected',true);
      }
    })
    $('#juegoM option').each(function(){
      if($(this).val() == data.juego.id_juego_mesa){
        $(this).attr('selected',true);
      }
    })
    $('#monedaM option').each(function(){
      if(data.moneda !== 'undefined'){
        if($(this).val() == data.moneda.id_moneda){
          $(this).attr('selected',true);
        }
      }else{
        if($(this).val() == 0){
          $(this).attr('selected',true);
        }
      }
    })


    //$('#monedaM').val(data.moneda.id_moneda);
    //$('#juegoM').val(data.juego.id_juego_mesa);
    //$('#sectorM').val(data.sector.id_sector_mesas);

  });

  //$.get('mesas/cargarDatos', function(data){


      $('#modalModificarMesa').modal('show');
      $('#modalModificarMesa #btn-modificar-mesa').val(id);
  //});

});

//PRESIONA GUARDAR DENTRO DEL MODAL MODIFICAR
$('#btn-modificar-mesa').click(function(){

  $('#mensajeExito').hide();

  var casino=$('#casinoM').val();

  if($('#monedaM').val() != 0){
    var id_moneda = $('#monedaM').val();
    var mmoneda = 0;
  }else{
    var id_moneda = null;
    var mmoneda = 1;
  }

  var formData = {
    id_mesa_de_panio: $(this).val(),
    nro_mesa: $('#numeroM').val(),
    nro_admin: $('#numeroAdmM').val(),
    nombre: $('#nombreM').val(),
    descripcion: $('#descripcionM').val(),
    id_juego_mesa: $('#juegoM').val(),
    id_casino: casino,
    id_moneda: id_moneda,
    id_sector_mesas: $('#sectorM').val(),
    multimoneda:mmoneda,
  }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'POST',
    url: 'mesas/modificarMesa/' + casino,
    data: formData,
    dataType: 'json',

    success: function (data){
      console.log(data);
      $('#mensajeErrorModificacion').hide();
      $('#modalModificarMesa').modal('hide');
      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text('La mesa fue modificada correctamente');
      $('#mensajeExito').show();

    },
    error:function(data){
      console.log('error', data);
      var response = data.responseJSON.errors;

      if(typeof response.id_casino !== 'undefined'){
        $('#mensajeErrorModificacion').show();
      }
      if(typeof response.nombre !== 'undefined'){
          mostrarErrorValidacion($('#nombreM'),response.nombre[0]);
      }
      if(typeof response.nro_mesa !== 'undefined'){
          mostrarErrorValidacion($('#numeroM'),response.nro_mesa[0]);
      }
      if(typeof response.nro_admin !== 'undefined'){
          mostrarErrorValidacion($('#numeroAdmM'),response.nro_admin[0]);
      }
      if(typeof response.id_moneda !== 'undefined'){
        $('#mensajeErrorModificacion').show();
      }
      if(typeof response.descripcion !== 'undefined'){
          mostrarErrorValidacion($('#descripcionM'),response.descripcion[0]);
      }
      if(typeof response.id_juego_mesa !== 'undefined'){
          $('#mensajeErrorModificacion').show();
      }
      if(typeof response.id_sector_mesa !== 'undefined'){
        $('#mensajeErrorModificacion').show();
      }
    }

  })
});


//PRESIONA EL BOTÓN DE ELIMINAR EN LA LISTA DE Mesas
$(document).on('click','.eliminarMesa',function(e){
  $('#mensajeExito').hide();
  $('#mensajeError').hide();

  e.preventDefault();

  var id_mesa=$(this).val();
  var id_casino=$(this).attr('data-casino');

  $('#modalAlertaEliminar').modal('show');
  $('#modalAlertaEliminar #btn-eliminar-mesa').val(id_mesa);
  $('#modalAlertaEliminar #id_casino').val(id_casino);

});


$('#btn-eliminar-mesa').click( function(e) {
  e.preventDefault();
  var id_mesa=$(this).val();
  var id_casino= $('#modalAlertaEliminar #id_casino').val();


  $.get('mesas/eliminarMesa/' + id_casino + '/' + id_mesa , function(data){

    if(data==1){

      $('#btn-buscarMesas').trigger('click',[1,10,'mesa_de_panio.nro_mesa','desc']);

      $('#modAlertaEliminar').modal('hide');
      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text('La mesa ha sido ELIMINADA correctamente');
      $('#mensajeExito').show();
    }
    else{

      $('#modAlertaEliminar').modal('hide');
      $('#mensajeError h3').text('ÉXITO');
      $('#mensajeError p').text('No está autorizado para realizar esta accion.');
      $('#mensajeError').show();


    }
  });
});
