//Mostrar modal para agregar nuevo Pack
$('#btn-nuevo-pack').click(function(e){

    e.preventDefault();
     
    //limpio modal
    $('#mensajeExito').hide();
    $('#frmPack').trigger('reset');
    $('#alertaNombrePack').hide();
    $('#modalNuevoPack').modal('show');
  
  });


  //Mostrar modal para agregar nuevo Pack
$('#btn-asociar-pack-juego').click(function(e){
    e.preventDefault();
    //limpio modal
    $('#btn-agregarJuegoListaPack').hide();
    //genero la lista para juegos
    $('#inputJuegoPack').generarDataList("http://" + window.location.host + "/juego/buscarJuegos" ,'resultados','id_juego','nombre_juego', 1, false);
    $('#inputJuegoPack').setearElementoSeleccionado(0,"");
    //genero la lista para los pack
     $('#inputNombrePack').generarDataList("http://" + window.location.host + "/packJuego/buscarPackJuegos" ,'resultados','id_pack','identificador', 1, false);
    $('#inputNombrePack').setearElementoSeleccionado(0,"");
   

    $('#modalAsociarPack').modal('show');
  
  });


// Seleccion del juuego para el pack
$('#inputJuegoPack').on('seleccionado',function(){
    var id_juego = $(this).obtenerElementoSeleccionado();

    $.get('juegos/obtenerJuego/' + id_juego, function(data) {
        $('#inputCodigoJuegoPack').val(data.juego.cod_juego).prop('readonly',true);
    });
    if($('#inputNombrePack').val() != ''){
        $('#btn-agregarJuegoListaPack').show();
    }
    
});


$('#inputJuegoPack').on('deseleccionado',function(){
      if($('#inputJuegoPack').val() == ''){
        $('#btn-agregarJuegoListaPack').hide();
        $('#inputCodigoJuegoPack').val('');
      }
});


// Seleccion de pack
$('#inputNombrePack').on('seleccionado',function(){
    var id_juego = $(this).obtenerElementoSeleccionado();
});


$('#inputNombrePack').on('deseleccionado',function(){
      if($('#inputNombrePack').val() == ''){
        $('#btn-agregarJuegoListaPack').hide();
      }
});


// llenar tabla

// AGREGAR JUEGO A LA MÁQUINA
$('#btn-agregarJuegoListaPack').click(function(){
    //Crear un item de la lista
    var id = $('#inputJuegoPack').obtenerElementoSeleccionado();
  
    $.get('http://' + window.location.host +'/juegos/obtenerJuego/'+ id, function(data) {
  
          agregarRenglonListaJuegoPack(data.juego.id_juego , data.juego.nombre_juego);
  
          limpiarCamposJuego();
  
          $('#inputJuegoPack').borrarDataList();
          $('#inputJuegoPack').generarDataList("http://" + window.location.host + "/juego/buscarJuegos" ,'resultados','id_juego','nombre_juego', 2, false);
          $('#inputJuegoPack').setearElementoSeleccionado(0,"");
  
      });

      $('#listaJuegosPack').find('p').hide();
  });


  function agregarRenglonListaJuegoPack(id_juego, nombre_juego ){

    var fila = $('<tr>').attr('id',id_juego);
    fila.append($('<td>').append($('<span>').addClass('badge')
                                            .css({'background-color':'#6dc7be','font-family':'Roboto-Regular','font-size':'18px','margin-top':'-3px'})
                                            .text(nombre_juego)
                                )
               );       
    var boton = $('<button>').addClass('btn btn-danger borrarJuegoaActivo')
                             .css('margin-left','10px')
                             .append($('<i>').addClass('fa fa-fw fa-trash'));
    fila.append($('<td>').append(boton));
  
    $('#tablaJuegosPack').append(fila);

  }