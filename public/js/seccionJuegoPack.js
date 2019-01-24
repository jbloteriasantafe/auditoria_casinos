$(document).ready(function(){

    $('#barraMaquinas').attr('aria-expanded','true');
    $('#maquinas').removeClass();
    $('#maquinas').addClass('subMenu1 collapse in');
    $('#gestionarMTM').removeClass();
    $('#gestionarMTM').addClass('subMenu2 collapse in');
  
    $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');
  
    $('.tituloSeccionPantalla').text('PAQUETE - JUEGOS');
    $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
    $('#opcPackJuegos').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
    $('#opcPackJuegos').addClass('opcionesSeleccionado');
  
    // //click forzado
    $('#btn-buscar').trigger('click');
  
    // $('#maquina_mod').hide(); //maquina modelo, se clona
    
  })

//Mostrar modal para agregar nuevo Pack
$('#btn-nuevo-pack').click(function(e){
    $('#modalNuevoPack .modal-title').text('NUEVO PAQUETE-JUEGO');
    e.preventDefault();
    $('#btn-crear-pack').val('nuevo');
    //limpio modal
    $('#mensajeExito').hide();
    $('#frmPack').trigger('reset');
    $('#alertaNombrePack').hide();
    $('#modalNuevoPack').modal('show');
  
  });

  //Mostrar modal para asociar paquete con maquina
$('#btn-asociar-pack-mtm').click(function(e){
    // $('#modalNuevoPack .modal-title').text('NUEVO PAQUETE-JUEGO');
    e.preventDefault();
    // $('#btn-crear-pack').val('nuevo');
    // //limpio modal
    $('#mensajeExito').hide();
    $('#inputPackActual').val("");
    $('#inputPackActual').attr("data-idPack", -1);
    
    $('#tablaMtmJuegoPack tbody').empty();



    
    $('#inputMTM').generarDataList("maquinas/obtenerMTMEnCasino/" + 0, 'maquinas','id_maquina','nro_admin',1,true);
    $('#inputMTM').setearElementoSeleccionado(0,"");
    $('#inputPack').generarDataList("http://" + window.location.host + "/packJuego/buscarPackJuegos" ,'resultados','id_pack','identificador', 1, false);
    $('#inputPack').setearElementoSeleccionado(0,"");
    ocultarErrorValidacion($('#inputMTM'));
    $('#modalAsociarPackMtm').modal('show');
  });


  //Mostrar modal para agregar nuevo Pack
$('#btn-open-asociar-pack-juego').click(function(e){
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
  
          //limpiarCamposJuego();
  
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
    var boton = $('<button>').addClass('btn btn-danger borrarJuegoDePack')
                             .css('margin-left','10px')
                             .append($('<i>').addClass('fa fa-fw fa-trash'));
    fila.append($('<td>').append(boton));
  
    $('#tablaJuegosPack').append(fila);

  }


  //Crear nuevo PackJuego
$('#btn-crear-pack').click(function (e) {
    $('#mensajeExito').hide();
      var accion=$(this).val();
      var formData = {
        identificador: $('#identificadorPack').val(),
        prefijo: $('#prefijo').val(),
      }
      var url= 'packJuego/guardarPackJuego';
      var msj= 'El paquete de juego se creó correctamente';
      if (accion=='modificar'){
        formData.id_pack=$('#id_pack').val();
        url='packJuego/modificarPackJuego'
        msj='El paquete de juego se modificó correctamente';
      }
      
      $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
        });
        console.log(formData,url,msj);
      $.ajax({
          type: 'POST',
          url: url,
          data: formData,
          dataType: 'json',
          success: function (data) {
              $('#btn-buscar').trigger('click');
              $('#modalNuevoPack').modal('hide');
              $('#mensajeExito h3').text('ÉXITO');
              $('#mensajeExito p').text(msj);
              $('#mensajeExito').show();
  
          },
          error: function (data) {
  
            var response = JSON.parse(data.responseText);

            if(typeof response.identificador !== 'undefined'){
              mostrarErrorValidacion($('#identificadorPack'),response.identificador,false);
            }

            if(typeof response.prefijo !== 'undefined'){
              mostrarErrorValidacion($('#prefijo'),response.prefijo,false);
            }
          }
      });
  });


  //borrar Juegos
$(document).on('click', '.borrarJuegoDePack', function(){
    $(this).parent().parent().remove();

    var cantidad_juegos = $('#tablaJuegosPack tbody tr').length;
    //Si no quedan más juegos mostrar el mensaje
    if (cantidad_juegos == 0) {
      $('#listaJuegosPack').find('p').show();
      $('#tablaJuegosPack').hide();
    }

});

// asocia un pack con un conjunto de juegos
$('#btn-asociar-pack-juego').on('click',function(){
    $('#mensajeExito').hide();
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });
  
      var formData = {
        id_pack: $('#id_pack_asociacion').val(),
        juegos_ids: obtenerJuegosDePack(),
      }
  
  
      $.ajax({
          type: 'POST',
          url: 'packJuego/asociarPackJuego',
          data: formData,
          dataType: 'json',
          success: function (data) {
              $('#btn-buscar').trigger('click');
              $('#modalAsociarPack').modal('hide');
              $('#mensajeExito h3').text('ÉXITO');
              $('#mensajeExito p').text('Se asignaron los juegos correcatamente');
              $('#mensajeExito').show();
  
          },
          error: function (data) {
  
            var response = JSON.parse(data.responseText);

            if(typeof response.id_pack !== 'undefined'){
              mostrarErrorValidacion($('#inputNombrePack'),response.id_pack,false);
            }

          }
      });
});


/* busqueda de usuarios */
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    })
  
    //Fix error cuando librería saca los selectores
    if(isNaN($('#herramientasPaginacion').getPageSize())){
      var size = 10; // por defecto
    }else {
      var size = $('#herramientasPaginacion').getPageSize();
    }
  
    var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
    if(sort_by == null){ //limpio las columnas
      $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }
  
    formData={
      nombreJuego: $('#buscadorNombre').val(),
      cod_Juego: $('#buscadorCodigoJuego').val(),
      codigoId: $('#buscadorCodigo').val(),
      nombre_progresivo: $('#buscadorProgresivos').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    }
  
    $.ajax({
      type: "POST",
      url: 'packJuegos/buscar',
      data: formData,
      dataType: 'json',
      success: function (resultados) {
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTabla tr').remove();
        for (var i = 0; i < resultados.data.length; i++) {
          $('#cuerpoTabla').append(crearFilaPackJuego(resultados.data[i]));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
  
      },
      error: function (data) {
        console.log('Error:', data);
      }
    });
  });



// obitiene los juegos de la lista seleccionados para el paquete especifico
function obtenerJuegosDePack(){
    var juegos_ids=[];
    $.each($('#tablaJuegosPack tbody tr') , function(i){
        juegos_ids.push($(this).attr('id'));
    });
    return juegos_ids;
}

$(document).on('click','.modificar',function(){

    //ocultarErrorValidacion($('#identificadorPack'));
    //ocultarErrorValidacion($('#prefijo'));
    //se restrablece los botones despues de salir del ver detalle
    var id_pack = $(this).val();
    //Modificar los colores del modal
    $('#modalNuevoPack .modal-title').text('MODIFICAR PAQUETE-JUEGO');
    $('#modalNuevoPack .modal-header').attr('style','background: #ff9d2d');
    $('#id_pack').val(id_pack);
    $('#btn-crear-pack').val('modificar').show();
    $.get("packJuegos/obtenerPackJuego/" + id_pack, function(data){
      console.log(data);
     $('#identificadorPack').val(data.pack.identificador);
     $('#prefijo').val(data.pack.prefijo);
     $('#modalNuevoPack').modal('show');
    });
});


$(document).on('click','.asociar',function(){
    // Limpiar tablas

    $('#tablaJuegosPack tbody tr').remove();
    $('#listaJuegosPack').find('p').show();
    //ocultarErrorValidacion($('#inputIdentificador'));
    //ocultarErrorValidacion($('#inputPrefijo'));
    //se restrablece los botones despues de salir del ver detalle
    $('#inputJuegoPack').generarDataList("http://" + window.location.host + "/juego/buscarJuegos" ,'resultados','id_juego','nombre_juego', 1, false);
    $('#inputJuegoPack').setearElementoSeleccionado(0,"");
    var id_pack = $(this).val();
    var nombre_pack=$(this).parent().parent().children().eq(0).text();
    $('#id_pack_asociacion').val(id_pack);
    $('#inputNombrePack').val(nombre_pack)
    $('#btn-agregarJuegoListaPack').hide();
    $.get("packJuegos/obtenerJuegos/" + id_pack, function(data){
        if(data !=""){
            $('#listaJuegosPack').find('p').hide();
            data.forEach(juego => {
                agregarRenglonListaJuegoPack(juego.id_juego ,juego.nombre_juego);
            });
        }
     $('#modalAsociarPack').modal('show');
    });
});



function crearFilaPackJuego(pj){

    var fila = $(document.createElement('tr'));
                      fila.attr('id',pj.id_pack)
                      .append($('<td>')
                          .addClass('col-xs-5')
                          .text(pj.identificador)
                      )
                      .append($('<td>')
                          .addClass('col-xs-2')
                          .text(pj.prefijo)
                      )
                      .append($('<td>')
                          .addClass('col-xs-2')
                          .text(pj.cant_juegos)
                      )
  
                      .append($('<td>')
                          .addClass('col-xs-3 text-right')
                          .append($('<button>')
                              .append($('<i>')
                                  .addClass('fa').addClass('fa-fw').addClass('fa-sync-alt')
                              )
                              .append($('<span>').text(' VER MÁS'))
                              .addClass('btn').addClass('btn-info').addClass('asociar')
                              .val(pj.id_pack)
                          )
                          .append($('<span>').text(' '))
                          .append($('<button>')
                              .append($('<i>')
                                  .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                              )
                              .append($('<span>').text(' MODIFICAR'))
                              .addClass('btn').addClass('btn-warning').addClass('modificar')
                              .val(pj.id_pack)
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
                              .val(pj.id_pack)
                          )
                      )
          return fila;
  };

  function clickIndice(e,pageNumber,tam){
    if(e != null){
      e.preventDefault();
    }
    var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
    var columna = $('#tablaResultados .activa').attr('value');
    var orden = $('#tablaResultados .activa').attr('estado');
    $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
  };


  //Borrar Pack y remover de la tabla
$(document).on('click','.eliminar',function(){
    $('.modal-title').removeAttr('style');
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    var id_pack = $(this).val();
    $('#btn-eliminarModal').val(id_pack);
    $('#modalEliminar').modal('show');
    $('#mensajeEliminar').text('¿Seguro que desea eliminar el paquete-juego "' + $(this).parent().parent().find('td:first').text()+'" ?' 
                                +"\r\n"
                                +'Esto quitará la asociación con los juegos, pero los mismos seguirán existiendo');
});

// confirma eliminar 
$('#btn-eliminarModal').click(function (e) {
    var id_pack = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "packJuegos/eliminarPackJuego/" + id_pack,
        success: function (data) {
          //Remueve de la tabla
          $('#btn-buscar').trigger('click');
          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

$('#quitarPack').on("click",function(e){
    $('#inputPackActual').val("");
    $('#inputPackActual').attr("data-idPack", -1);
    $('#tablaMtmJuegoPack tbody').empty();

});

$('#btn-asociar-pack-mtm-juego').on('click',function(e){
    e.preventDefault();

    var formData = {
        id_pack: $('#inputPackActual').attr('data-idPack'),
        id_mtm:$('#inputMTM').attr('data-elemento-seleccionado'),
        juegos:obtenerJuegosHabilitados(),
      }

      $.ajax({
        type: "POST",
        url: 'packJuego/asociarMtmJuegosPack',
        data: formData,
        dataType: 'json',
        success: function (resultados) {
            $('#modalAsociarPackMtm').modal('hide');
              $('#mensajeExito h3').text('ÉXITO');
              $('#mensajeExito p').text('La asociación fue exitosa');
              $('#mensajeExito').show();
        },
        error: function (data) {
            if ( data.id_mtm!=""){
                mostrarErrorValidacion($('#inputMTM'),"Campo Requerido", true);
            }
        }
      });
    


});

$('#agregarPack').on("click",function(e){
    id_pack= $('#inputPack').attr("data-elemento-seleccionado");
    nombre_pack=$('#inputPack').val();

    if(id_pack!=0){
        $('#inputPackActual').val(nombre_pack);
        $('#inputPackActual').attr("data-idPack", id_pack);
    }
    //limpio datos cargador para setear nuevo paquete

    $('#tablaMtmJuegoPack tbody').empty();

    $.get("packJuegos/obtenerJuegos/" + id_pack, function(data){
        if(data !=""){
            data.forEach(juego => {
                agregarRenglonListaJuegoPackMTM(juego.id_juego ,juego.nombre_juego);
            });
        }
    });


})

$('#inputPack').on('seleccionado',function(){

    id_pack= $('#inputPack').attr("data-elemento-seleccionado");
    nombre_pack=$('#inputPack').val();

    if(id_pack!=0){
        $('#inputPackActual').val(nombre_pack);
        $('#inputPackActual').attr("data-idPack", id_pack);
    }
    //limpio datos cargador para setear nuevo paquete

    $('#tablaMtmJuegoPack tbody').empty();

    $.get("packJuegos/obtenerJuegos/" + id_pack, function(data){
        $('#tablaMtmJuegoPack tbody').empty();
        if(data !=""){
            data.forEach(juego => {
                agregarRenglonListaJuegoPackMTM(juego.id_juego ,juego.nombre_juego);
            });
        }
    });
});

function agregarRenglonListaJuegoPackMTM(id_juego, nombre_juego ){

    var fila = $('<tr>').attr('id',id_juego);

    fila.append($('<td>').append($('<input>')
                    .attr('type','checkbox').css('margin-left','10px')
                    .prop('checked', false)));


    fila.append($('<td>').append($('<span>').addClass('badge')
                                            .css({'background-color':'#6dc7be','font-family':'Roboto-Regular','font-size':'18px','margin-top':'-3px'})
                                            .text(nombre_juego)
                                )
               );       
   
    fila.append($('<td>').append($('<input>')
        .attr('type','text')));

    fila.append($('<td>').append($('<input>')
        .attr('type','text')));
        
    
  


    $('#tablaMtmJuegoPack').append(fila);

  }

  $('#inputMTM').on('seleccionado',function(){

    id_maquina=$(this).obtenerElementoSeleccionado();
    //limpio campos

    $('#inputPackActual').val("");
    $('#inputPackActual').attr("data-idPack", -1);
    $('#tablaMtmJuegoPack tbody').empty();


    //pido y cargo
    $.get("packJuegos/obtenerJuegosMTM/" + id_maquina, function(data){
        console.log(data);
        // TODO gestionar la informacion para cargar
        if(data.juegos !=""){
            
            $('#tablaMtmJuegoPack tbody').empty();
            for (i = 0; i < data.juegos.length; i++) {
                if (i==0){
                    pack=data.juegos[0];
                    $('#inputPackActual').val(pack.identificador);
                    $('#inputPackActual').attr("data-idPack", pack.id_pack);
                }else{
                    agregarJuegosPackMtm(data.juegos[i]);
                }

              } 
            
        }

    });
  });

  function agregarJuegosPackMtm(juego){
    var fila = $('<tr>').attr('id',juego.id_juego);
    
    fila.append($('<td>').append($('<input>')
                    .attr('type','checkbox').css('margin-left','10px')
                    .prop('checked', juego.habilitado)));


    fila.append($('<td>').append($('<span>').addClass('badge')
                                            .css({'background-color':'#6dc7be','font-family':'Roboto-Regular','font-size':'18px','margin-top':'-3px'})
                                            .text(juego.nombre_juego)
                                )
               );       
   
    fila.append($('<td>').append($('<input>')
        .attr('type','text').val(juego.denominacion))
        );

    fila.append($('<td>').append($('<input>')
        .attr('type','text').val(juego.porcentaje_devolucion))
        );
    
    
  


    $('#tablaMtmJuegoPack').append(fila);


  };

  function obtenerJuegosHabilitados(){
    var juegos=[];
    $.each($('#tablaMtmJuegoPack tbody tr') , function(indexMayor){
        var res={
            id_juego:$(this).attr("id"),
            habilitado: $(this).children().eq(0).find(':input').prop('checked'),
            denominacion: $(this).children().eq(2).find(':input').val(),
            devolucion: $(this).children().eq(3).find(':input').val(),
          }
          juegos.push(res);

        });
        return juegos
  }


