var juego_seleccionado;

$(document).ready(function() {
  $('#tablaJuegosActivos').hide();
})

// AGREGAR JUEGO A LA MÁQUINA
$('#btn-agregarJuegoLista').click(function(){
  //Crear un item de la lista
  var id = $('#inputJuego').obtenerElementoSeleccionado();
  const id_casino = $(this).attr('data-casino');

  $.get('http://' + window.location.host +'/juegos/obtenerJuego/'+ id, function(data) {
        agregarRenglonListaJuego(data.juego.id_juego , data.juego.nombre_juego , $('#den_sala').val() , $('#porcentaje_devolucion_juego').val() , data.tablasDePago, false);

        limpiarCamposJuego();

        $('#inputJuego').borrarDataList();
        $('#inputJuego').generarDataList("http://" + window.location.host + "/juegos/buscarJuegos/" + id_casino ,'resultados','id_juego','nombre_juego', 2, false);
        $('#inputJuego').setearElementoSeleccionado(0,"");
    });

    //Se agrega a la lista de juegos
    $('#tablaJuegosActivos').show();

    $('#tablas_pago').empty();

    $('#listaJuegosMaquina').find('p').hide();
});

$('#inputJuego').on('seleccionado',function(){
  const id_juego = $(this).obtenerElementoSeleccionado();
  $.get('juegos/obtenerJuego/' + id_juego, function(data) {
    $('#inputCodigo').val(data.juego.cod_juego).prop('readonly',true);
    $('#tablas_pago').empty();
    if (data.tablasDePago.length == 0) {
      return $('#tablas_de_pago').hide();
    }
    $('#tablas_de_pago').show();
    const tp_ejemplo = $('<div>').addClass('row').css('margin-bottom','15px').append(
      $('<div>').addClass('col-xs-8').append($('<input>').attr('disabled',true).addClass('form-control codigo'))
    ).append(
      $('<div>').addClass('col-xs-4').append($('<input>').attr('disabled',true).addClass('form-control porcentaje'))
    );
    for(const i in data.tablasDePago) {
      const tp = tp_ejemplo.clone();
      tp.find('input').attr('data-id',data.tablasDePago[i].id_tabla_pago);
      tp.find('.codigo').val(data.tablasDePago[i].codigo);
      tp.find('.porcentaje').val(data.tablasDePago[i].porcentaje_devolucion);
      $('#tablas_pago').append(tp);
    }
    //Mostrar los botones correspondientes
    $('#btn-agregarJuegoLista').show();
    $('#btn-crearJuego').hide();
  });
});

$('#inputJuego').on('deseleccionado',function(){
      console.log('deseleccionado');
      if($('#inputJuego').val() == ''){
        $('#btn-agregarJuegoLista').hide();
        $('#btn-crearJuego').hide();
        $('#btn-cancelarJuego').hide();
      }
      $('#tablas_de_pago').show();
});

function mostrarJuegos(id_casino,juegos,juego_activo){
  $('#inputJuego').generarDataList("http://" + window.location.host + "/juegos/buscarJuegos/"+id_casino ,'resultados','id_juego','nombre_juego', 2, false);
  $('#inputJuego').setearElementoSeleccionado(0,"");
  $('#btn-agregarJuegoLista').attr('data-casino',id_casino);
  //Cargar juego activo
  if(juego_activo != null){
    agregarRenglonListaJuego(juego_activo.id_juego, juego_activo.nombre_juego, juego_activo.denominacion,juego_activo.porcentaje_devolucion , juego_activo.tablasPago, true);
  }
  for (var i = 0; i < juegos.length; i++) {
    agregarRenglonListaJuego(juegos[i].id_juego, juegos[i].nombre_juego , juegos[i].denominacion , juegos[i].porcentaje_devolucion, juegos[i].tablasPago, false);
  }
  //Mensaje no hay juegos
  if($('#tablaJuegosActivos tbody tr').length == 0) $('#listaJuegosMaquina').find('p').show();
  else $('#listaJuegosMaquina').find('p').hide();
}

function agregarRenglonListaJuego(id_juego, nombre_juego,denominacion,porcentaje_devolucion ,tablas, activo){
  porcentaje_devolucion = porcentaje_devolucion ?? '-';
  denominacion = denominacion ?? '-';

  const fila = $('<tr>').attr('id',id_juego);
  const juego_checked = activo || $('#tablaJuegosActivos tbody tr').length == 0;
  fila.append($('<td>').append($('<input>').attr('name','juego_seleccionado').attr('type','radio').css('margin-left','10px').prop('checked', juego_checked)));
  const span = $('<span>').addClass('badge').css({'background-color':'#6dc7be','font-family':'Roboto-Regular','font-size':'18px','margin-top':'-3px'});
  fila.append($('<td>').append(span.clone().text(nombre_juego)));
  fila.append($('<td>').append(span.clone().text(denominacion)));
  fila.append($('<td>').append(span.clone().addClass('pdev').text(porcentaje_devolucion)));

  const tablasPago = $('<select>').addClass('form-control select_tablas_pago');
  let id_tabla_seleccionada = null;
  for (const i in (tablas ?? [])) {
    if(id_tabla_seleccionada === null && parseFloat(tablas[i].porcentaje_devolucion) == parseFloat(porcentaje_devolucion)){
      id_tabla_seleccionada = tablas[i].id_tabla_pago;
    }
    const t = $('<option>').val(tablas[i].id_tabla_pago).text(tablas[i].codigo).attr('data-pdev',tablas[i].porcentaje_devolucion);
    tablasPago.append(t);
  }
  
  if(id_tabla_seleccionada === null){
    id_tabla_seleccionada = '-SIN-TABLA-DE-PAGO-';
    const t = $('<option>').val(id_tabla_seleccionada).text(id_tabla_seleccionada).attr('data-pdev',porcentaje_devolucion);
    tablasPago.append(t);
  }
  tablasPago.val(id_tabla_seleccionada);
  
  fila.append($('<td>').append(tablasPago));
  const boton = $('<button>').addClass('btn btn-danger borrarJuegoaActivo').css('margin-left','10px').append($('<i>').addClass('fa fa-fw fa-trash'));
  fila.append($('<td>').append(boton));
  $('#tablaJuegosActivos').append(fila);
  $('#tablaJuegosActivos').show();
}

$(document).on('change','#tablaJuegosActivos .select_tablas_pago',function(e){
  const op = $(this).find('option:selected');
  const pdev = op.attr('data-pdev'); 
  if(pdev === undefined) return;
  $(this).closest('tr').find('.pdev').text(pdev);
});

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

    var tablas = [];
    $('#tablas_pago>div').each(function(){
        var tabla = {
          id_tabla_pago: 0,
          codigo:$(this).find('input').val(),
        }
        tablas.push(tabla);
    })

    agregarRenglonListaJuego(0,$('#inputJuego').val(),$('#den_sala').val() ,$('#porcentaje_devolucion_juego').val(),tablas,false);

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
  $('#inputCodigo').val('');
  $('#den_sala').val('');
  $('#porcentaje_devolucion_juego').val('');
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
    $('.juegoSeleccionado input[type=radio]').prop('disabled' , false);
    $('#agregarJuego').show();
    $('.borrarJuego').show();
  }else{
    $('#inputJuego').prop('readonly', true);
    $('#inputCodigo').prop('readonly', true);
    $('.juegoSeleccionado input[type=radio]').prop('disabled' , true);
    $('.borrarJuego').hide();
    $('#agregarJuego').hide();
  }
}

function obtenerDatosJuego(){
  var juegos = [];
console.log($('#tablaJuegosActivos'));
  //por cada juego
  $.each($('#tablaJuegosActivos tbody tr') , function(indexMayor){
    var tablas = [];

    //por cada tabla de pago
    $.each($(this).find('td:eq(4) select option') , function(indexMenor){
      var tabla={
          id_tabla: $(this).val() ,
          nombre_tabla: $(this).text(),
          seleccionado: $(this).is(':selected')
      }
      tablas.push(tabla);
    });

    var denominacion = "";
    if ($(this).find('td:eq(2)').text() != "-") denominacion = $(this).find('td:eq(2)').text();

    var porcentaje_devolucion = "";
    if ($(this).find('td:eq(3)').text() != "-") porcentaje_devolucion = $(this).find('td:eq(3)').text();
    var juego= {
      id_juego: $(this).attr('id'),
      nombre_juego: $(this).find('td:eq(1)').text(),
      tablas: tablas,
      cod_identificacion: $('#inputCodigo').val(),
      denominacion: denominacion,
      porcentaje_devolucion: porcentaje_devolucion,
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
