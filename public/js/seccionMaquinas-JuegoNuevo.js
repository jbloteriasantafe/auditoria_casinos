var juego_seleccionado;

$(document).ready(function() {
  $('#inputJuego').generarDataList("http://" + window.location.host + "/juego/buscarJuegos" ,'resultados','id_juego','nombre_juego', 2, false);
  $('#inputJuego').setearElementoSeleccionado(0,"");
  $('#tablaJuegosActivos').hide();

})

// AGREGAR JUEGO A LA MÁQUINA
$('#btn-agregarJuegoLista').click(function(){
  //Crear un item de la lista
  var id = $('#inputJuego').obtenerElementoSeleccionado();

  $.get('http://' + window.location.host +'/juegos/obtenerJuego/'+ id, function(data) {

        agregarRenglonListaJuego(data.juego.id_juego , data.juego.nombre_juego , $('#den_sala').val() , $('#porcentaje_devolucion_juego').val() , data.tablasDePago, false, $('#inputPack option:selected').text(),$('#inputPack').select().val());

        limpiarCamposJuego();

        $('#inputJuego').borrarDataList();
        $('#inputJuego').generarDataList("http://" + window.location.host + "/juego/buscarJuegos" ,'resultados','id_juego','nombre_juego', 2, false);
        $('#inputJuego').setearElementoSeleccionado(0,"");
        // $('#inputJuego').parent().find('.contenedor-data-list').hide();// lo rompe

    });

    //Se agrega a la lista de juegos
    $('#tablaJuegosActivos').show();

    $('#tablas_pago').empty();

    $('#listaJuegosMaquina').find('p').hide();
});

$('#inputJuego').on('seleccionado',function(){
    var id_juego = $(this).obtenerElementoSeleccionado();

    $.get('juegos/obtenerJuego/' + id_juego, function(data) {
      $('#inputPack').empty();
      $('#inputPack').append($('<option>').text("-").val("-1"));
        if(data.pack!=""){
            for (var i = 0; i < data.pack.length; i++) {
              
              $('#inputPack').append($('<option>').text(data.pack[i].identificador).val(data.pack[i].id_pack));
            }
        }
       
        $('#inputCodigo').val(data.juego.cod_juego).prop('readonly',true);
        $('#niveles_progresivos').val(data.juego.id_progresivo).prop('readonly',true); //Acá tiene que ir el nivel de progresivo, no el id

        //Si no tiene tablas de pagos ocultar esa zona
        if (data.tablasDePago.length == 0) {
            $('#tablas_de_pago').hide();
        } else {
            //Mostrarlas
            $('#tablas_de_pago').show();
            $('#tablas_pago').empty();
            for (var i = 0; i < data.tablasDePago.length; i++) {
              $('#tablas_pago')
                  .append($('<div>').addClass('row')
                                    .css('margin-bottom','15px')
                                    .append($('<div>')
                                      .addClass('col-xs-10')
                                      .append($('<input>').attr('data-id' , data.tablasDePago[i].id_tabla_pago).val(data.tablasDePago[i].codigo).addClass('form-control'))
                                    )
                                    .append($('<div>')
                                        .addClass('col-xs-2')
                                        .append($('<button>')
                                            .addClass('btn').addClass('btn-danger').addClass('borrarFila').addClass('borrarTablaPago')
                                            .append($('<i>')
                                                .addClass('fa fa-fw fa-trash')
                                            )
                                        )
                                    )

                          ).append($('<br>'))
            }
        }
    });

    //Mostrar los botones correspondientes
    //$('#btn-cancelarJuego').show(); se comenta porque no tiene funcionalidad
    $('#btn-agregarJuegoLista').show();
    $('#btn-crearJuego').hide();

});

// $('#inputJuego').on('input',function(){
//   //se ejecuta despues de que cambia el valor del input
//   $();
//   $();
// })

$('#inputJuego').on('deseleccionado',function(){
      console.log('deseleccionado');
      if($('#inputJuego').val() != ''){

            //se dispuso que no se pueda crear los juegos desde esta pantalla, por lo que se elimina la opcion de crear juego
            /*
            $('#btn-crearJuego').show();
            $('#btn-agregarJuegoLista').hide();
            $('#btn-cancelarJuego').show();
            */
      }else{
            $('#btn-agregarJuegoLista').hide();
            $('#btn-crearJuego').hide();
            $('#btn-cancelarJuego').hide();
      }
      $('#tablas_de_pago').show();
});

function mostrarJuegos(juegos,juego_activo){
  //Ocultar mensaje de inexistencia de juegos
  $('#listaJuegosMaquina').find('p').hide();
    //Cargar juego activo
    agregarRenglonListaJuego(juego_activo.id_juego, juego_activo.nombre_juego, juego_activo.denominacion,juego_activo.porcentaje_devolucion , juego_activo.tablasPago, true,juego_activo.pack.identificador,juego_activo.pack.id_pack);
    for (var i = 0; i < juegos.length; i++) {
      agregarRenglonListaJuego(juegos[i].id_juego, juegos[i].nombre_juego , juegos[i].denominacion , juegos[i].porcentaje_devolucion, juegos[i].tablasPago, false,juegos[i].pack.identificador,juegos[i].pack.id_pack);
    }
}

function agregarRenglonListaJuego(id_juego, nombre_juego,denominacion,porcentaje_devolucion ,tablas, activo,nombre_pack_sel,id_pack){
  denominacion = denominacion != null ? denominacion : "-"; // si denomacion vacio hardcodeo guion medio
  porcentaje_devolucion = porcentaje_devolucion != null ? porcentaje_devolucion : "-"; // si denomacion vacio hardcodeo guion medio
  if ( nombre_pack_sel !== "" ) {
    nombre_pack=nombre_pack_sel;
    
}else{
  nombre_pack="-"
}
  
  
  var fila = $('<tr>').attr('id',id_juego);

  //Mirar si solo hay un juego cuando se agrega manuealmente, setearlo como activo
  if (!activo && $('#tablaJuegosActivos tbody tr').length == 0) {
    fila.append($('<td>').append($('<input>').attr('name','juego_seleccionado').attr('type','radio').css('margin-left','10px').prop('checked', true)));
  }else {
    fila.append($('<td>').append($('<input>').attr('name','juego_seleccionado').attr('type','radio').css('margin-left','10px').prop('checked', activo)));
  }

  fila.append($('<td>').append($('<span>').addClass('badge')
                                          .css({'background-color':'#6dc7be','font-family':'Roboto-Regular','font-size':'18px','margin-top':'-3px'})
                                          .text(nombre_juego)
                              )
             );
             
  fila.append($('<td>').attr('data-idPack',id_pack)
                                          .append($('<span>').addClass('badge')
                                         .css({'background-color':'#6dc7be','font-family':'Roboto-Regular','font-size':'18px','margin-top':'-3px'})
                                         .text(nombre_pack)
                             ));
    fila.append($('<td>').append($('<span>').addClass('badge')
                                         .css({'background-color':'#6dc7be','font-family':'Roboto-Regular','font-size':'18px','margin-top':'-3px'})
                                         .text(denominacion)
                             ));                           
  fila.append($('<td>').append($('<span>').addClass('badge')
                                         .css({'background-color':'#6dc7be','font-family':'Roboto-Regular','font-size':'18px','margin-top':'-3px'})
                                         .text(porcentaje_devolucion)
                             ));

  var tablasPago = $('<select>').addClass('form-control');
  if(typeof(tablas) != 'undefined' ){
    for (var i = 0; i < tablas.length; i++) {
      var tabla = $('<option>').val(tablas[i].id_tabla_pago).text(tablas[i].codigo);
      tablasPago.append(tabla);
    }
  }

  var boton = $('<button>').addClass('btn btn-danger borrarJuegoaActivo')
                           .css('margin-left','10px')
                           .append($('<i>').addClass('fa fa-fw fa-trash'));
  fila.append($('<td>').append(tablasPago));
  fila.append($('<td>').append(boton));

  $('#tablaJuegosActivos').append(fila);
  $('#tablaJuegosActivos').show();
}

//agregar Tabla DE Pago
$('#btn-agregarTablaDePago').click(function(){
    $('#tablas_pago')
        .append($('<div>').addClass('row')
                          .css('margin-bottom','15px')
                          .append($('<div>')
                            .addClass('col-xs-10')
                            .append($('<input>').attr('data-id' , 0).addClass('form-control'))
                          )
                          .append($('<div>')
                              .addClass('col-xs-2')
                              .append($('<button>')
                                  .addClass('btn').addClass('btn-danger').addClass('borrarFila').addClass('borrarTablaPago')
                                  .append($('<i>')
                                      .addClass('fa fa-fw fa-trash')
                                  )
                              )
                          )

                ).append($('<br>'))
});

//crear juego nuevo
$('#btn-crearJuego').click(function(){
  //Crear un item de la lista

    // var tablas = $('<select>');
    var tablas = [];
    $('#tablas_pago>div').each(function(){
        var tabla = {
          id_tabla_pago: 0,
          codigo:$(this).find('input').val(),
        }
        tablas.push(tabla);
    })

    agregarRenglonListaJuego(0,$('#inputJuego').val(),$('#den_sala').val() ,$('#porcentaje_devolucion_juego').val(),tablas,false,$('#inputPack option:selected').text(),$('#inputPack').select().val());

    $('#inputJuego').val('');

    limpiarCamposJuego();

    $('#tablas_pago').empty();
    $('#listaJuegosMaquina').find('p').hide();
})

//borrar Tabla de Pago
$(document).on('click' , '.borrarTablaPago' , function(){
  var fila = $(this).parent().parent();
  fila.next().remove(); //Remueve el salto de linea
  fila.remove();
});

//borrar Juegos
$(document).on('click', '.borrarJuegoaActivo', function(){
    $(this).parent().parent().remove();

    var cantidad_juegos = $('#tablaJuegosActivos tbody tr').length;
    //Si no quedan más juegos mostrar el mensaje
    if (cantidad_juegos == 0) {
      $('#listaJuegosMaquina').find('p').show();
      $('#tablaJuegosActivos').hide();
    }else if (cantidad_juegos == 1) {
      $('#tablaJuegosActivos tbody tr td:eq(0)').find('input').prop('checked',true);
    }

    //Si se borra el activo y por lo menos hay un juego, entonces asignar un activo.
    if ($(this).parent().parent().find('input').prop('checked') && cantidad_juegos > 0) {
        $('#tablaJuegosActivos tbody tr:first td:eq(0)').find('input').prop('checked',true);
    }
});

function limpiarCamposJuego(){
  //Borra todos los inputs
  $('#inputJuego').setearElementoSeleccionado(0,"");
  $('#inputPack').val('-');
  $('#inputCodigo').val('');
  $('#den_sala').val('');
  $('#porcentaje_devolucion_juego').val('');
  $('#niveles_progresivos').val('');
  $('#tablas_pago input').each(function(){
    if($(this).is('[readonly]')){
      $('#tablas_pago').empty();
    }
  });
  habilitarControlesJuegos(true);
  //Oculta todos los botones
  $('#btn-cancelarJuego').hide();
  $('#btn-agregarJuegoLista').hide();
  $('#btn-crearJuego').hide();
  //Mostrar el botón de agregar tabla de pago
  $('#btn-agregarTablaDePago').show();

  $('#tablas_de_pago').show();
}

//limpia los campos al comenzar.
function limpiarModalJuego(){
  //Borra todos los inputs
  $('#inputJuego').setearElementoSeleccionado(0,"");
  $('#inputCodigo').val('');
  $('#niveles_progresivos').val('');
  $('#tablas_pago').empty();

  $('#tablaJuegosActivos tbody tr').remove();

  $('#listaJuegosMaquina').find('p').show();

  $('#juegoPlegado').removeClass('in');
  habilitarControlesJuegos(true);

  //Oculta todos los botones
  $('#btn-cancelarJuego').hide();
  $('#btn-agregarJuegoLista').hide();
  $('#btn-crearJuego').hide();

  //Mostrar el botón de agregar tabla de pago
  $('#btn-agregarTablaDePago').show();
}

function habilitarControlesJuegos(valor){
  if(valor){
    $('#inputJuego').prop('readonly', false);

    $('#inputCodigo').prop('readonly', false);
    $('#niveles_progresivos').prop('readonly', false);
    $('.juegoSeleccionado input[type=radio]').prop('disabled' , false);
    $('#agregarJuego').show();
    $('.borrarJuego').show();
  }else{
    $('#inputJuego').prop('readonly', true);
    $('#inputCodigo').prop('readonly', true);
    $('#niveles_progresivos').prop('readonly', true);
    $('.juegoSeleccionado input[type=radio]').prop('disabled' , true);
    $('.borrarJuego').hide();
    $('#agregarJuego').hide();
  }
}

function obtenerDatosJuego(){
  var juegos = [];

  //por cada juego
  $.each($('#tablaJuegosActivos tbody tr') , function(indexMayor){
    var tablas = [];

    //por cada tabla de pago
    $.each($(this).find('td:eq(3) select option') , function(indexMenor){
      var tabla={
          id_tabla: $(this).val() ,
          nombre_tabla: $(this).text(),
          seleccionado: $(this).is(':selected')
      }
      tablas.push(tabla);
    });

    var denominacion = "";
    if ($(this).find('td:eq(3)').text() != "-") denominacion = $(this).find('td:eq(3)').text();

    var porcentaje_devolucion = "";
    if ($(this).find('td:eq(4)').text() != "-") porcentaje_devolucion = $(this).find('td:eq(4)').text();
    var juego= {
      id_juego: $(this).attr('id'),
      nombre_juego: $(this).find('td:eq(1)').text(),
      tablas: tablas,
      cod_identificacion: $('#inputCodigo').val(),
      denominacion: denominacion,
      porcentaje_devolucion: porcentaje_devolucion,
      id_pack: $(this).find('td:eq(2)').attr("data-idPack"),
    }
    if($(this).find('td:eq(0) input').is(':checked')){
      juego.activo=1;
      
    }else{
      juego.activo=0;
    }


    juegos.push(juego);

  }); //fin each


  console.log(juegos);
  return juegos;
}
