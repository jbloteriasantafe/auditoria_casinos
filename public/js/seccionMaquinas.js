$(document).ready(function() {
  //Muestra el menu desplegado
  var prueba = window.location.pathname;

  if(prueba == '/maquinas'){
    $('#barraMaquinas').attr('aria-expanded','true');
    $('#maquinas').removeClass();
    $('#maquinas').addClass('subMenu1 collapse in');
    $('#gestionarMTM').removeClass();
    $('#gestionarMTM').addClass('subMenu2 collapse in');

    $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');

    $('.tituloSeccionPantalla').text('Gestionar máquinas');
    $('#gestionarMaquinas').attr('style','border-left: 6px solid #25306b;');
    $('#opcGestionarMaquinas').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
    $('#opcGestionarMaquinas').addClass('opcionesSeleccionado');
  }

  // eventoBusqueda(10,1,null,null,null);
  var pathname = window.location.pathname; // ej: /maquinas , /maquinas/5

  var arreglo = pathname.split("/");

  switch (arreglo.length) {
    case 3:
      if(arreglo[2] !=0){
          eventoModificar(arreglo[2]);
          console.log('carga pagina');
        }else{
          eventoNuevo();
      }
      break;
    default:

  }

  $('[data-toggle="popover"]').popover();

  $('#btn-buscar').trigger('click', [1, 10 , 'maquina.nro_admin' , 'asc']);

});

$("#contenedorFiltros").on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| GESTIONAR MÁQUINAS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//abrir modal nuevo
$('#btn-nuevo').click(function(e){
    e.preventDefault();
    eventoNuevo();
});

//minimiza modal
$('#btn-minimizar').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
      $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//abrir modal nuevo
$('#btn-cargaMasiva').click(function(e){
    e.preventDefault();

    limpiarModal();
    habilitarControles(true);

    //Modificar los colores del modal
    $('.modal-title').text('| CARGA MASIVA');
    $('.modal-header').attr('style','background: #6DC7BE','color: #000;');
    $('#btn-guardar').removeClass('btn-warning');
    $('#btn-guardar').addClass('btn-success');
    $('#modalCargaMasiva').modal('show');
});

//boton Borrar Maquina: Mostror modal
$(document).on('click','.eliminar',function(){
    //Cambiar colores modal
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').removeAttr('style');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    var id_maquina = $(this).val();
    $('#btn-eliminarModal').val(id_maquina);
    $('#modalEliminar').modal('show');
});
//borrar maquina
$('#btn-eliminarModal').click(function (e) {
    var id_maquina = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "maquinas/eliminarMaquina/" + id_maquina,
        success: function (data) {
          //Remueve de la tabla
          console.log(data);
          $('#' + id_maquina).remove();
          $("#tablaMaquinas").trigger("update");
          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});
//Busqueda
$('#btn-buscar').click(function(e, pagina,page_size,columna,orden){
  e.preventDefault();
  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }
  var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaMaquinas .activa').attr('value'),orden: $('#tablaMaquinas .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaMaquinas th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  eventoBusqueda(page_size, page_number, sort_by, columna,orden);

});

function eventoBusqueda(page_size, page_number, sort_by, columna,orden){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var formData = {
    nro_admin: $('#busqueda_maquina').val(),
    marca: $('#busqueda_marca').val(),
    nombre_juego: $('#busqueda_juego').val(),
    denominacion: $('#busqueda_denominacion').val(),
    id_casino: $('#busqueda_casino').val(),
    id_sector: $('#busqueda_sector').val(),
    nro_isla: $('#busqueda_isla').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  $.ajax({
      type: 'POST',
      url:'http://' + window.location.host + '/maquinas/buscarMaquinas',
      data: formData,
      dataType: 'json',
      success: function (resultados){
          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
          $('#cuerpoTabla tr').remove();
          for(var i = 0; i < resultados.data.length; i++) {
            generarFilaTablaMaquinas(resultados.data[i]);
          }
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
}

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#busqueda_casino').on('change',function(){
  var id_casino = $('option:selected' , this).val();
  var selectCasino = $(this);

  $.get('http://' + window.location.host + "/sectores/obtenerSectoresPorCasino/" + id_casino, function(data){

    var selectSector = $('#busqueda_sector');
    selectSector.empty();
    selectSector.append($('<option>')
        .val(0)
        .text('Todos los sectores')
    )

    for (var i = 0; i < data.sectores.length; i++) {
          selectSector.append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }
  });

});

function eventoModificar(id_maquina){
  $('#mensajeExito').hide();
  limpiarModal();
  habilitarControles(true);
  //en nro isla queda desabilitado porque todavia no seleccionó un casino
  $('#nro_isla').prop('readonly', true);
  $('#inputMaquina').prop('readonly', true);

  //Modificar los colores del modal
  $('.modal-title').text('MODIFICAR MÁQUINA');
  $('.modal-header').attr('style','background: #ff9d2d');
  $('.navModal').removeClass('nuevo detalle').addClass('nav_modificar');

  $('#btn-guardar').removeClass('btn-successAceptar');
  $('#btn-guardar').addClass('btn-warningModificar');
  $('#btn-guardar').text('MODIFICAR MÁQUINA');
  $('#btn-guardar').prop('disabled',false).show();
  $('#btn-guardar').css('display','inline-block');

  $('#id_maquina').val(id_maquina);

  $.get('http://' + window.location.host +  "/maquinas/obtenerMTM/" + id_maquina, function(data){
    console.log(data);
    mostrarMaquina(data , 'modificar');
    //Se asigna al botón el valor "MODIFICAR" y se muestra
    $('#btn-guardar').val("modificar");
    $('.seccion').hide();
    $('.navModal a').removeClass();
    $('#navMaquina').addClass('navModalActivo');
    $('#secMaquina').show();
    $('#modalMaquina').modal('show');
  });

}

//Mostrar modal con los datos del Casino cargados
$(document).on('click','.modificar',function(e,id_maquina){
  $(this).prop('disabled', true);

    var id_maquina = (id_maquina != null) ? id_maquina : $(this).val();
    console.log('click .modificar');
    eventoModificar(id_maquina);
  });

  $('#modalMaquina').on('shown.bs.modal', function() {
      var id_maquina = $('#modalMaquina input#id_maquina').val();

      $("button.modificar[value='"+ id_maquina +"']").prop('disabled', false);

})

//Mostrar modal con los datos del Casino cargados
$(document).on('click','.detalle',function(){
      limpiarModal();
      //Modificar los colores del modal
      $('.modal-title').text('DETALLES MÁQUINA');
      $('.modal-header').attr('style','background: #4FC3F7');
      $('.navModal').removeClass('nuevo modificar').addClass('detalle');

      $('#btn-guardar').removeClass('btn-success');
      $('#btn-guardar').addClass('btn-warning');
      $('#btn-guardar').text('Modificar MAQUINA');
      $('#btn-guardar').prop('disabled',true).hide();
      $('#btn-guardar').css('display','none');

      var id_maquina = $(this).val();

      $.get('http://' + window.location.host + "/maquinas/obtenerMTM/" + id_maquina, function(data){
        console.log(data);
        mostrarMaquina(data,'detalle');
        habilitarControles(false);

        $('.seccion').hide();
        $('.navModal a').removeClass();
        $('#navMaquina').addClass('navModalActivo');
        $('#secMaquina').show();
        $('#modalMaquina').modal('show');
      })
});

$(document).on('click','#tablaMaquinas thead tr th[value]',function(e){

  $('#tablaMaquinas th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
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
  $('#tablaMaquinas th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function generarFilaTablaMaquinas(maquina){
    if(maquina.desc_marca == null) descripcion=' ';
    else descripcion = data.maquinas[i].desc_marca;

    $('#cuerpoTabla')
        .append($('<tr>')
            .attr('id',maquina.id_maquina)
            .append($('<td>')
                .addClass('col-xs-1')
                .text(maquina.nro_admin)
            )
            .append($('<td>')
                .addClass('col-xs-1')
                .text(maquina.nro_isla)
            )
            .append($('<td>')
                .addClass('col-xs-3')
                .text(maquina.codigo + ' - ' + maquina.descripcion)
            )
            .append($('<td>')
                .addClass('col-xs-2')
                .text(maquina.marca)
            )
            .append($('<td>')
                .addClass('col-xs-2')
                .text(maquina.nombre_juego)
            )
            .append($('<td>')
                .addClass('col-xs-1')
                .text(maquina.denominacion)
            )
            .append($('<td>')
                .addClass('col-xs-2')
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                    )
                    .append($('<span>').text(' VER MÁS'))
                    .addClass('btn').addClass('btn-info').addClass('detalle')
                    .attr('value',maquina.id_maquina)
                )
                .append($('<span>').text(' '))
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                    )
                    .append($('<span>').text(' MODIFICAR'))
                    .addClass('btn').addClass('btn-warning').addClass('modificar')
                    .attr('value',maquina.id_maquina)
                )
                .append($('<span>').text(' '))
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa')
                        .addClass('fa-fw')
                        .addClass('fa-trash-alt')
                    )
                    .append($('<span>').text(' ELIMINAR'))
                    .addClass('btn').addClass('btn-danger').addClass('eliminar')
                    .attr('value',maquina.id_maquina)
                )
            )
  )

}

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaMaquinas .activa').attr('value');
  var orden = $('#tablaMaquinas .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}
