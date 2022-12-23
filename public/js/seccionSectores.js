$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionarMTM').removeClass();
  $('#gestionarMTM').addClass('subMenu2 collapse in');

  $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Sectores');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcSectores').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcSectores').addClass('opcionesSeleccionado');

  $("#tablaSectores").tablesorter({
      headers: {
        3: {sorter:false}
      }
  });

  limpiarModal();
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

function limpiarModal(){
  $('#frmSector').trigger('reset');
  $('#id_sector').val(0);
  $('#nombre').prop('readonly' , false);
  $('#cantidad_maquinas').prop('readonly',true);
  $('#casino').prop("disabled" , false);
  $('#inputIsla').prop('readonly' , false);
  $('#inputIsla').attr("data-maquina" , "");
  $('#listaIslas li').remove();
  limpiarAlertas();
}

function limpiarAlertas(){
  $('#nombre').removeClass('alerta');
  $('#casino').removeClass('alerta');
  $('#alertaNombre').text('').hide();
  $('#alertaCasino').text('').hide();
}

//Quitar eventos de la tecla Enter
$("#modalSector input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-guardar').click();
    }
});

/**************************************************
  TODOS LOS EVENTOS DEL INPUT ISLA
**************************************************
 Evento para pasarle el ID del sector en el datalist al input
    Cada vez que se hace el input se controla si un option del datalist fue seleccionado.
    Si fue seleccionado se compara con el input para sacarle el id.
*/
$('#casino').on('change' , function() {
     var id_casino = $(this).val();
    $('.buscadorIsla').generarDataList("sectores/buscarIslaPorCasinoYNro/" + id_casino,'islas','id_isla','nro_isla',2,true);
})

$(document).on('click','.borrarIsla',function(){
  var cant = $('#cantidad_maquinas').val() - $(this).parent().parent().find(':nth-child(3)').text();
  $('#cantidad_maquinas').val(cant);

  $(this).parent().parent().remove();
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| SECTORES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo Sector
$('#btn-nuevo').click(function(e){
  e.preventDefault();
  limpiarModal();
  $('#btn-guardar').val("nuevo");
  $('#btn-guardar').show();
  $('.modal-title').text('| NUEVO SECTOR');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
  $('#btn-guardar').removeClass();
  $('#btn-guardar').addClass('btn btn-successAceptar');
  $('#modalSector').modal('show');
});

//Mostrar modal con los datos del Sector
$(document).on('click','.detalle',function(){
    limpiarModal();
    $('.modal-title').text('| VER MÁS');
    $('.modal-header').attr('style','font-family: Roboto-Black; background: #4FC3F7');

    $('#nombre').prop("readonly" , true);
    $('#cantidad_maquinas').prop("readonly" , true);
    $('#casino').prop("disabled" , true);

    $('#inputIsla').prop("readonly" , true);
    $('.btn-default').text('SALIR');
    var id_sector = $(this).val();

    $.get("sectores/obtenerSector/" + id_sector, function(data){
        console.log(data);

        $('#nombre').val(data.sector.descripcion);
        $('#cantidad_maquinas').val(data.sector.cantidad_maquinas);
        $('#casino').val(data.casino.id_casino);
        for(var i = 0; i<data.islas.length;i++){
          agregarIsla(data.islas[i],false);
        }
        $('#btn-guardar').hide();
        $('#modalSector').modal('show');
    });
});

//Modal para modificar una SECTOR
$(document).on('click','.modificar',function(){

    limpiarModal();
    $('.modal-title').text('| MODIFICAR SECTOR');
    $('.modal-header').attr('style','font-family: Roboto-Black; background: #ff9d2d');
    $('#btn-guardar').addClass('btn btn-warningModificar');
    $('#btn-guardar').show();

    var id_sector = $(this).val();

    $.get("sectores/obtenerSector/" + id_sector, function(data){
        console.log(data);
        $('#id_sector').val(data.sector.id_sector);
        $('#nombre').val(data.sector.descripcion);
        $('#cantidad_maquinas').val(data.sector.cantidad_maquinas);
        $('#casino').val(data.casino.id_casino).trigger('change');
        for(var i = 0; i<data.islas.length;i++){
          agregarIsla(data.islas[i],true);
        }
        $('#btn-guardar').val("modificar");
        $('#modalSector').modal('show');
    });
});

//Borrar Sector y remover de la tabla
$(document).on('click','.eliminar',function(){
    //Cambiar colores modal
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').removeAttr('style');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    var id_sector = $(this).val();
    $('#btn-eliminarModal').val(id_sector);
    $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function(e){
    var id_sector = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "sectores/eliminarSector/" + id_sector,
        success: function (data) {
          console.log(data);
          $('#sector' + id_sector).remove();
          $("#tablaSectores").trigger("update");

          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

//Crear nueva Sector / actualizar si existe
$('#btn-guardar').click(function(e){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var islas = [];
    $('#listaIslas li').each(function(){
      var isla={
        id_isla: $(this).val(),
      };
      islas.push(isla);
    });

    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = ((state == "modificar") ? 'sectores/modificarSector':'sectores/guardarSector');

    var formData = {
      id_sector: $('#id_sector').val(),
      descripcion: $('#nombre').val(),
      id_casino: $('#casino').val(),
      islas: islas,
    }

    console.log(formData);

    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            console.log(data);

            var sector = generarFilaTabla(data.sector,data.casino);

            if(state == "nuevo"){ //Si está agregando
                console.log('NUEVO');
                $('#tablaSectores tbody').append(sector);
                $('#tituloTabla').text("Se agregó un nuevo Sector");
            }else{ //Si está modificando
                console.log('MODIFICA');
                $('#sector' + data.sector.id_sector).replaceWith(sector);
                $('#tituloTabla').text("Se modificó un Sector");
            }
            $('#frmSector').trigger("reset");
            $('#modalSector').modal('hide');
            $("#tablaSectores").trigger("update");
            $("#tablaSectores th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');

        },
        error: function (data) {
            console.log('Error:', data);

            var response = JSON.parse(data.responseText);
            limpiarAlertas();

            if(typeof response.descripcion !== 'undefined'){
              $('#nombre').addClass('alerta');
              $('#alertaNombre').text(response.descripcion[0]);
              $('#alertaNombre').show();
            }

        }
    });
});

function generarFilaTabla(sector,casino){
  var fila = $(document.createElement('tr'));
  fila.attr('id','sector' + sector.id_sector)
      .append($('<td>')
          .addClass('col-xs-2')
          .text(casino.nombre)
      )
      .append($('<td>')
          .addClass('col-xs-4')
          .text(sector.descripcion)
      )
      .append($('<td>')
          .addClass('col-xs-3')
          .text(sector.cantidad_maquinas)
      )
      .append($('<td>')
          .addClass('col-xs-3')
          .append($('<button>')
              .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
              )
              .append($('<span>').text(' VER MÁS'))
              .addClass('btn').addClass('btn-info').addClass('detalle')
              .attr('value', sector.id_sector)
          )
          .append($('<span>').text(' '))
          .append($('<button>')
              .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
              )
              .append($('<span>').text(' MODIFICAR'))
              .addClass('btn').addClass('btn-warning').addClass('modificar')
              .attr('value', sector.id_sector)
          )
          .append($('<span>').text(' '))
          .append($('<button>')
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
              )
              .append($('<span>').text(' ELIMINAR'))
              .addClass('btn').addClass('btn-danger').addClass('eliminar')
              .attr('value', sector.id_sector)
          )
      )
    return fila;
}

function agregarIsla(isla,editable){
  var codigo;
  isla.codigo == null ? codigo = '-' : codigo = isla.codigo;
  $('#listaIslas')
     .append($('<li>')
        //Se agrega el id de la isla del input
        .val(isla.id_isla)
        .addClass('row')
        .css('list-style','none').css('padding','5px 0px')
        //Columna de NUMERO ISLA
        .append($('<div>')
            .addClass('col-xs-3 col-xs-offset-1')
            .text(isla.nro_isla)
        )
        //Columna de CODIGO ISLA
        .append($('<div>')
            .addClass('col-xs-2')
            .text(codigo)
        )
        //Columna de CANT MAQUINAS
        .append($('<div>')
            .addClass('col-xs-2')
            .text(isla.cantidad_maquinas)
        )
    );

  if(editable){
    $('#listaIslas li:last')
            //Columna BOTON QUITAR
            .append($('<div>')
                .addClass('col-xs-3')
                .append($('<button>')
                    .addClass('btn').addClass('btn-danger').addClass('borrarFila').addClass('borrarIsla')
                    .append($('<i>')
                        .addClass('fa').addClass('fa-trash-alt')
                    )
                )
            )
  }

}

//Agregar Isla
$(document).on("click", ".agregarIsla" ,function(){
  var id = $('.buscadorIsla').obtenerElementoSeleccionado();
  if(!existeEnDataList(id)){
    if(id != 0){
      $.get('http://' + window.location.host +"/islas/obtenerIsla/" + id, function(data){
        agregarIsla(data.isla ,true);
      });
      $('.buscadorIsla').setearElementoSeleccionado(0,"")
    }
  }else {
    $('.buscadorIsla').setearElementoSeleccionado(0,"")

  }
});


function existeEnDataList(id){
  var bandera = false;
  $('#listaIslas li').each(function(){
      if (parseInt($(this).val()) ==  parseInt(id))
        bandera = true;
  });

  return bandera;
}
