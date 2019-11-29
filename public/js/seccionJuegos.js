var letras="abcdefghyjklmnñopqrstuvwxyz";

$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionarMTM').removeClass();
  $('#gestionarMTM').addClass('subMenu2 collapse in');

  $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Juegos');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcJuegos').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcJuegos').addClass('opcionesSeleccionado');

  //click forzado
  $('#btn-buscar').trigger('click');

  $('#maquina_mod').hide(); //maquina modelo, se clona
})

//enter en buscador
$('#modalJuego input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-guardar').click();
  }
})

//enter en modal
$('#contenedorFiltros input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
})

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

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| JUEGOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo Juego
$('#btn-nuevo').click(function(e){

  e.preventDefault();
  $('#mensajeExito').hide();

  $('.modal-title').text(' | NUEVO JUEGO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

  $('#btn-guardar').removeClass('btn-warningModificar');
  $('#btn-guardar').addClass('btn-successAceptar');
  $('#btn-guardar').text('ACEPTAR');

  $('#columna > .alertaSpan').remove();
  $('#btn-guardar').val("nuevo");
  $('#frmJuego').trigger('reset');
  $('#columna > #unaTablaDePago').remove();
  $('#alertaNombre').hide();
  $('#alertaCodigo').hide();
  $('#alertaNiveles').hide();
  $('#alertaTablas').hide();
  $('#alertaTabla').remove();
  $('#modalJuego').modal('show');
  $('#boton-salir').text('CANCELAR');

  //Agregar el boton para guardar
  $('#btn-guardar').css('display','inline-block');
  $('#btn-agregarMaquina').show();
  $('#tablaPagosEncabezado').hide();
  $('#btn-agregarTablaDePago').show();

  $('#nombre_juego').prop('readonly',false);
  $('#cod_identificacion').prop('readonly',false);
  $('#nro_niv_progresivos').prop('readonly',false);
  $('#tabla_pago').prop('readonly',false);
  $('#inputProgresivo').prop('readonly',false);

  $('#nombre_juego').removeClass('alerta');
  $('#cod_identificacion').removeClass('alerta');
  $('#nro_niv_progresivos').removeClass('alerta');

  $('#agregarProgresivo').hide();
  $('#cancelarProgresivo').hide();
});

//Muestra el modal con todos los datos del JUEGO
$(document).on('click','.detalle', function(){

  $('.modal-title').text('| VER MÁS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #4FC3F7; color: #FFF');
  $('#boton-cancelar').hide();
  $('#boton-salir').show();
  $('#columna > .alertaSpan').remove();
  $('#columna > #unaTablaDePago').remove();
  $('#btn-agregarTablaDePago').hide();
  $('#btn-agregarMaquina').hide();
  $('.borrarFila').hide();
  $('#agregarProgresivo').hide();

  $('#boton-salir').text('SALIR');

  var id_juego = $(this).val();

  $.get("juegos/obtenerJuego/" + id_juego, function(data){
      console.log(data);
      $('#id_juego').val(data.juego.id_juego);
      $('#inputJuego').val(data.juego.nombre_juego).prop('readonly',true);
      $('#inputCodigo').val(data.juego.cod_identificacion);
      $('#inputCodigoJuego').val(data.juego.cod_juego).prop('readonly',true);
      $('#nro_niv_progresivos').val(data.juego.nro_niv_progresivos);

      $('#btn-guardar').val("modificar");


      $('#nombre_juego').prop('readonly',true);
      $('#cod_identificacion').prop('readonly',true);
      $('#nro_niv_progresivos').prop('readonly',true);
      $('#tabla_pago').prop('readonly',true);
      $('#inputProgresivo').prop('readonly',true);

      $('#nombre_juego').removeClass('alerta');
      $('#cod_identificacion').removeClass('alerta');
      $('#nro_niv_progresivos').removeClass('alerta');

      for (var i = 0; i < data.tablasDePago.length; i++) {
        $('#btn-agregarTablaDePago').trigger('click');
        $('#tablas_pago input:last').val(data.tablasDePago[i].codigo).attr('data-id' , data.tablasDePago[i].id_tabla_pago).prop('disabled',true);
        
      }
      $('.borrarTablaPago').hide();
      for (var i = 0; i < data.maquinas.length; i++) {
        var div = agregarRenglonMaquina();
        div.attr('data-id' ,data.maquinas[i].id_maquina);
        div.find('.selectCasinos').val(data.maquinas[i].id_casino).prop('disabled',true).trigger('change');
        div.find('.nro_admin').val(data.maquinas[i].nro_admin).prop('readonly',true);
        div.find('.denominacion').val(data.maquinas[i].denominacion).prop('readonly',true);
        div.find('.porcentaje').val(data.maquinas[i].porcentaje_devolucion).prop('readonly',true);
      }

      let listaSoft = $('#listaSoft');
      listaSoft.find('.copia').remove();
      const filaCert = $('#soft_mod');
      for(var i = 0; i < data.certificadoSoft.length; i++){
        const c = data.certificadoSoft[i];
        let nueva_fila = filaCert.clone().show().addClass('copia');
        nueva_fila.attr('data-id',c.certificado.id_gli_soft);
        nueva_fila.find('.codigo').text(c.certificado.nro_archivo);
        if(c.archivo === null){
          nueva_fila.find('.link').text('SIN ARCHIVO');
          nueva_fila.find('.link').removeAttr('href').css('color','rgb(183,51,122)');
        }
        else{
          nueva_fila.find('.link').text(c.archivo);
          nueva_fila.find('.link').attr('href','glisofts/pdf/'+c.certificado.id_gli_soft);
        }
        listaSoft.append(nueva_fila);
      }

      $('#modalJuego').modal('show');

  });

    $('#alertaNombre').hide();
    $('#alertaCodigo').hide();
    $('#alertaNiveles').hide();
    $('#cancelarProgresivo').hide();

    //Remover el boton para guardar
    $('#btn-guardar').css('display','none');

});

$('.modal').on('hidden.bs.modal', function() {
  ocultarErrorValidacion($('#inputCodigo'));
  ocultarErrorValidacion($('inputJuego'));
  $('#btn-guardar').val('');
  $('#id_juego').val(0);
  $('#inputCodigo').val('');
  $('#inputJuego').val('');
  $('#inputCodigoJuego').val('');
  $('.copia').remove();
  $('#tablas_pago').empty();
})

//Mostrar modal con los datos del Juego cargado
$(document).on('click','.modificar',function(){

    ocultarErrorValidacion($('#inputJuego'));
    ocultarErrorValidacion($('#inputCodigo'));
    ocultarErrorValidacion($('#inputCodigoJuego'));
    ocultarErrorValidacion($('#tablas_pago'));
    //se restrablece los botones despues de salir del ver detalle
    $('#btn-agregarTablaDePago').show();
    $('#btn-agregarMaquina').show();
    $('.borrarFila').show();
    $('#agregarProgresivo').show();

    var id_juego = $(this).val();
    //Modificar los colores del modal
    $('#modalJuego .modal-title').text('MODIFICAR JUEGO');
    $('#modalJuego .modal-header').attr('style','background: #ff9d2d');
    $('#btn-guardar').val('modificar').show();
    $('#id_juego').val(id_juego);
    $.get("juegos/obtenerJuego/" + id_juego, function(data){
      console.log(data);
      mostrarJuego(data.juego, data.tablasDePago , data.maquinas,data.certificadoSoft);

    });

});

$('#btn-agregarMaquina').click(function(){
  agregarRenglonMaquina();
})

function agregarRenglonMaquina(){
  var modelo = $('#maquina_mod');
  var renglon = modelo.clone();
  renglon.addClass('copia').removeAttr('id').show();
  $('#listaMaquinas').append(renglon);
  renglon.trigger('change');
  return renglon;
};

$(document).on('click' , '.borrarJuego' , function(){
    $(this).parent().parent().remove();
})
$(document).on('change','.selectCasinos',function(){
    const t  = $(this);
    const id_casino = t.val();
    const fila = t.parent();
    fila.find('.nro_admin').attr('list','datalistMaquinas'+id_casino);
})

//agregar Tabla DE Pago
$('#btn-agregarTablaDePago').click(function(){
    $('#tablas_pago')
        .append($('<div>').addClass('row')
                          .addClass('col-md-12')
                          .css('padding-top','2px')
                          .css('padding-bottom','2px')
                          .append($('<div>')
                            .addClass('col-xs-10')
                            .append($('<input>').attr('data-id' , 0).addClass('form-control'))
                          )
                          .append($('<div>')
                              .addClass('col-xs-2')
                              .append($('<button>')
                                  .addClass('btn').addClass('btn-danger').addClass('borrarFila').addClass('borrarTablaPago').css('display','block')
                                  .append($('<i>')
                                      .addClass('fa fa-fw fa-trash')
                                  )
                              )
                          )

                )
});

//borrar Tabla de Pago
$(document).on('click' , '.borrarTablaPago' , function(){
  var fila = $(this).parent().parent();
  fila.remove();
});

$(document).on('click' , '.borrarCertificado' , function(){
  var fila = $(this).parent().parent();
  fila.remove();
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
    url: 'juegos/buscar',
    data: formData,
    dataType: 'json',
    success: function (resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      $('#cuerpoTabla tr').remove();
      for (var i = 0; i < resultados.data.length; i++) {
        $('#cuerpoTabla').append(crearFilaJuego(resultados.data[i]));
      }
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

//borrar una tabla de pago
$(document).on('click','.borrarTablaDeJuego',function(){
  $(this).parent().parent().remove();
  var cant_filas=0;
  $('#columna #unaTablaDePago').each(function(){
      cant_filas++;
      // console.log('Cantidad de filas: ' + cant_filas);
  });
  if(cant_filas == 0){
    // console.log('Entró al if de filas');
    $('#tablaPagosEncabezado').hide();
  }
});

//Borrar Juego y remover de la tabla
$(document).on('click','.eliminar',function(){
    $('.modal-title').removeAttr('style');
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    var id_juego = $(this).val();
    $('#btn-eliminarModal').val(id_juego);
    $('#modalEliminar').modal('show');
    $('#mensajeEliminar').text('¿Seguro que desea eliminar el juego "' + $(this).parent().parent().find('td:first').text()+'"?');
});

$('#btn-eliminarModal').click(function (e) {
    var id_juego = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "juegos/eliminarJuego/" + id_juego,
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

//Crear nuevo Juego / actualizar si existe
$('#btn-guardar').click(function (e) {
  $('#mensajeExito').hide();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var maquinas = [];
   
    $('#listaMaquinas .copia').each(function (){
      var id_m = $(this).attr('data-id') == undefined ? 0 : $(this).attr('data-id') ;
      var maquina = {
        id_maquina: id_m,
        id_casino: $('.selectCasinos',$(this)).val(),
        nro_admin: $('.nro_admin',$(this)).val() ,
        denominacion: $('.denominacion',$(this)).val(),
        porcentaje: $('.porcentaje',$(this)).val(),
      }
      maquinas.push(maquina);
    })

    var tablas = [];
    $('#tablas_de_pago input').each(function(){
      var id_t = $(this).attr('data-id') == undefined ? 0 : $(this).attr('data-id') ;
      var tabla = {
        id_tabla_pago: id_t,
        codigo:  $(this).val()
      }
      tablas.push(tabla)
    })

    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = 'juegos/guardarJuego';
    var id_juego = $('#id_juego').val();

    var formData = {
      nombre_juego: $('#inputJuego').val(),
      cod_identificacion: $('#inputCodigo').val(),
      cod_juego:$('#inputCodigoJuego').val(),
      tabla_pago: tablas,
      maquinas: maquinas,
    }

    if (state == "modificar") {
      url = 'juegos/modificarJuego';
      formData.id_juego =  $('#id_juego').val();
    }

    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            $('#btn-buscar').trigger('click');
            $('#modalJuego').modal('hide');
            $('#mensajeExito h3').text('ÉXITO');
            $('#mensajeExito p').text(' ');
            $('#mensajeExito').show();

        },
        error: function (data) {
            var response = JSON.parse(data.responseText);

            if(typeof response.nombre_juego !== 'undefined'){
              mostrarErrorValidacion($('#inputJuego'),response.nombre_juego,false);
            }

            if(typeof response.cod_identificacion !== 'undefined'){
              mostrarErrorValidacion($('#inputCodigo'),response.cod_identificacion,false);
            }

            var i=0;
            var error=' ';
            $('#columna #unaTablaDePago').each(function(){
              $(this).find('#codigo').removeClass('alerta');
            });

            $('#columna #unaTablaDePago').each(function(){
              if(typeof response['tablasDePago.'+ i +'.codigo'] !== 'undefined'){
                error=response['tablasDePago.'+ i +'.codigo'];
                $(this).find('#codigo').addClass('alerta');
              }
              i++;
            })

        }
    });
});

/** EVENTOS PARA VALIDAR LOS CAMPOS ***/
$('#nombre_juego').focusout(function(){
  if ($(this).val() == ''){
      $(this).addClass('alerta');
      $('#alertaNombre').text('Este campo no puede estar en blanco.');
      $('#alertaNombre').show();
  }
});

$('#nombre_juego').focusin(function(){
  $(this).removeClass('alerta');
  $('#alertaNombre').hide();
});

$('#nro_niv_progresivos').focusout(function(){
  if ($(this).val() == ''){
      $(this).addClass('alerta');
      $('#alertaNiveles').text('El campo Niveles de Progresivo debe ser un entero.');
      $('#alertaNiveles').show();
  }
});

$('#nro_niv_progresivos').focusin(function(){
  $(this).removeClass('alerta');
  $('#alertaNiveles').hide();
});

$(document).on('focusout','.inputTabla', function(){
  if ($(this).val() == '' || tiene_letras($(this).val())){
      $(this).addClass('alerta');
      var alerta='<span id="alertaTabla" class="alertaSpan">Tabla de pago no debe estar vacía; y los campos Base, %Dev.min y %Dev.max deben ser números</span>';
      $('#columna').append(alerta);
  }
});

$(document).on('focusin','.inputTabla', function(){
  $(this).removeClass('alerta');
  $('#alertaTabla').remove();
});

$(document).on('focusout','.inputCodigo', function(){
  if ($(this).val() == '' ){
      $(this).addClass('alerta');
      var alerta='<span id="alertaTabla" class="alertaSpan">Tabla de pago no debe estar vacía; y los campos Base, %Dev.min y %Dev.max deben ser números</span>';
      $('#columna').append(alerta);
  }
});

$(document).on('focusin','.inputCodigo', function(){
  $(this).removeClass('alerta');
  $('#alertaTabla').remove();
});

//Evento de tipeo en el input
$('#inputProgresivo').bind('input', function() {
    datalist = $('#datalistProgresivos');
    //Lo escrito en el input
    var inputProgresivo = $(this).val();

    if (inputProgresivo.length > 0){
      $('#cancelarProgresivo').hide();

    }
    else {
      $('#crearProgresivo').css('display' , 'none');
      $('#cancelarProgresivo').hide();

    }

    if(inputProgresivo.length <= 1) {
      datalist.empty();
    }

    if(inputProgresivo.length == 2) {
      buscarProgresivo(inputProgresivo);
    }

    if(inputProgresivo.length >= 2) {
      revisarDatalistProgresivo(inputProgresivo);
    }

    if(inputProgresivo.length==0){
      $('#agregarProgresivo').hide();
      $('#crearProgresivo').hide();
      $('#cancelarProgresivo').hide();
    }
});

//Agregar Progresivo ya existente
$('#agregarProgresivo').click(function(){
      $('#muestraProgresivos p').text($('#inputProgresivo').val());
      //Crear un item de la lista
      $('#buscadorProgresivos div').hide();
      $('#muestraProgresivos').hide();
});

$('#cancelarProgresivo').click(function(){
    $(this).hide();
    $('#inputProgresivo').val('');
    $('#agregarProgresivo').hide();
});

$('#borrarProgresivo').click(function(){
    $('#buscadorProgresivos div').show();
    $('#muestraProgresivos').hide();
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
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
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

/***********FUNCIONES****************/
function buscarProgresivo(input){
  datalist = $('#datalistProgresivos');
  //Si el string del input es más largo que 2 caracteres busca en la BD
  $.get("progresivos/buscarProgresivoPorNombreYTipo/" + input, function(data){

      //Recorre el arreglo de los progresivos que vienen de la BASE
      $.each(data.resultados, function(index, progresivo) {
                          datalist.append($('<option>')
                    .text(progresivo.nombre_progresivo + " (" + progresivo.tipo_progresivo + ")")
                    .attr('id',progresivo.id_progresivo)
          );
      });
  });


}

function revisarDatalistProgresivo(inputProgresivo){
  //me fijo si lo que escribio existe o seleccionó
  $('#datalistProgresivos option').each(function(){
    console.log($(this));
    console.log(inputProgresivo);
       if($(this).val() === inputProgresivo){
          // datalist.empty();
         $('#inputProgresivo').attr('data-progresivo',$(this).attr('id'));
        //  $('#inputHard').prop('readonly', true);
         $('#agregarProgresivo').hide();
         $('#cancelarProgresivo').hide();
      }
      else{
        $('#agregarProgresivo').hide();
        $('#cancelarProgresivo').hide();

        $('#inputHard').val(inputProgresivo);
        $('#inputHard').attr('data-hard','');
      }
  });
}

function crearFilaJuego(juego){
  var fila = $(document.createElement('tr'));

  var progresivos;
  var codigo;
  juego.nro_niv_progresivos == null ? progresivos = '-' : progresivos= juego.nro_niv_progresivos;
  juego.certificados == null ?  codigo = '-' :   codigo= juego.certificados;
  juego.cod_juego == null ?  codigojuego = '-' :   codigojuego= juego.cod_juego;

  fila.attr('id',juego.id_juego)
  .append($('<td>')
      .addClass('col-xs-3')
      .text(juego.nombre_juego)
  )
  .append($('<td>')
      .addClass('col-xs-3')
      .text(codigojuego)
  )
  .append($('<td>')
      .addClass('col-xs-3')
      .text(codigo)
  )
  .append($('<td>')
      .addClass('col-xs-3')
      .append($('<button>')
          .append($('<i>')
              .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
          )
          .append($('<span>').text(' VER MÁS'))
          .addClass('btn').addClass('btn-info').addClass('detalle')
          .val(juego.id_juego)
      )
      .append($('<span>').text(' '))
      .append($('<button>')
          .append($('<i>')
              .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
          )
          .append($('<span>').text(' MODIFICAR'))
          .addClass('btn').addClass('btn-warning').addClass('modificar')
          .val(juego.id_juego)
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
          .val(juego.id_juego)
      )
  )
  return fila;
}

function tiene_letras(texto){
   texto = texto.toLowerCase();
   for(i=0; i<texto.length; i++){
      if (letras.indexOf(texto.charAt(i),0)!=-1){
         return 1;
      }
   }
   return 0;
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

function habilitarControles(valor){

}


function mostrarJuego(juego, tablas, maquinas,certificados){
  $('#modalJuego').modal('show');
  $('#inputJuego').val(juego.nombre_juego).prop('readonly',false);;
  $('#inputCodigo').val(juego.cod_identificacion);
  $('#inputCodigoJuego').val(juego.cod_juego).prop('readonly',false);;

  for (var i = 0; i < tablas.length; i++) {
    $('#btn-agregarTablaDePago').trigger('click');
    $('#tablas_pago input:last').val(tablas[i].codigo).attr('data-id' , tablas[i].id_tabla_pago);

    console.log('dd',tablas);
    console.log($('#tablas_pago'));

  }
  for (var i = 0; i < maquinas.length; i++) {
    var div = agregarRenglonMaquina();
    div.attr('data-id' ,maquinas[i].id_maquina);
    div.find('.selectCasinos').val(maquinas[i].id_casino).trigger('change');
    div.find('.nro_admin').val(maquinas[i].nro_admin);
    div.find('.denominacion').val(maquinas[i].denominacion);
    div.find('.porcentaje').val(maquinas[i].porcentaje_devolucion);
  } 
  for (var i = 0; i < certificados.length; i++){
    let fila = agregarRenglonCertificado();
    const cert = certificados[i].certificado;
    fila.find('.codigo').val(cert.nro_archivo)
    .attr('data-id',cert.id_gli_soft);
  }
}

function agregarRenglonCertificado(){
  let fila =  $('#soft_input_mod').clone().show()
  .css('padding-top','2px')
  .css('padding-bottom','2px')
  .addClass('copia');
  $('#listaSoft').append(fila);
  return fila;
}

$('#btn-agregarCertificado').click(function(){
  agregarRenglonCertificado();
});