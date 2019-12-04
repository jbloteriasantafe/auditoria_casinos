$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Juegos');
  $('#opcJuegos').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcJuegos').addClass('opcionesSeleccionado');
  //click forzado
  $('#btn-buscar').trigger('click');
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
  ocultarErrorValidacion($('#inputJuego'));
  ocultarErrorValidacion($('#inputCodigoJuego'));
  $('#mensajeExito').hide();
  $('.modal-title').text(' | NUEVO JUEGO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
  $('#btn-guardar').removeClass('btn-warningModificar');
  $('#btn-guardar').addClass('btn-successAceptar');
  $('#btn-guardar').text('ACEPTAR');
  $('#btn-guardar').val("nuevo");
  $('#btn-guardar').css('display','inline-block');
  $('#boton-salir').text('CANCELAR');

  const juego = {nombre_juego: "", cod_juego: ""};
  const tablas = [];
  const maquinas = [];
  const certificados = [];
  let casinos = [];
  $('#maquina_mod .selectCasinos option').each(function(){
    const t = $(this);
    casinos.push({id_casino: t.val(), nombre: t.text()});
  });

  mostrarJuego(juego,tablas,maquinas,certificados,casinos);
  habilitarControles(true);

  $('#modalJuego').modal('show');
});

//Muestra el modal con todos los datos del JUEGO
$(document).on('click','.detalle', function(){
  ocultarErrorValidacion($('#inputJuego'));
  ocultarErrorValidacion($('#inputCodigoJuego'));
  $('.modal-title').text('| VER MÁS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #4FC3F7; color: #FFF');
  $('#boton-cancelar').hide();
  $('#boton-salir').show();
  $('#boton-salir').text('SALIR');
  //Remover el boton para guardar
  $('#btn-guardar').css('display','none');

  var id_juego = $(this).val();

  $.get("juegos/obtenerJuego/" + id_juego, function(data){
      console.log(data);
      mostrarJuego(data.juego, data.tablasDePago , data.maquinas,data.certificadoSoft,data.casinosJuego);
      $('#id_juego').val(data.juego.id_juego);
      habilitarControles(false);
      $('#modalJuego').modal('show');
  });
});

$('.modal').on('hidden.bs.modal', function() {
  ocultarErrorValidacion($('#inputJuego'));
  ocultarErrorValidacion($('#inputCodigoJuego'));
  $('#btn-guardar').val('');
  $('#id_juego').val(0);
  $('#inputJuego').val('');
  $('#inputCodigoJuego').val('');
  $('.copia').remove();
  $('#tablas_pago').empty();
})
$('#inputJuego').mouseleave(function(){
  ocultarErrorValidacion($('#inputJuego'));
});
$('#inputCodigoJuego').mouseleave(function(){
  ocultarErrorValidacion($('#inputJuego'));
});

//Mostrar modal con los datos del Juego cargado
$(document).on('click','.modificar',function(){
    ocultarErrorValidacion($('#inputJuego'));
    ocultarErrorValidacion($('#inputCodigoJuego'));
    var id_juego = $(this).val();
    //Modificar los colores del modal
    $('#modalJuego .modal-title').text('MODIFICAR JUEGO');
    $('#modalJuego .modal-header').attr('style','background: #ff9d2d');
    $('#btn-guardar').val('modificar').show();
    $('#id_juego').val(id_juego);
    habilitarControles(true);
    $.get("juegos/obtenerJuego/" + id_juego, function(data){
      console.log(data);
      mostrarJuego(data.juego, data.tablasDePago , data.maquinas,data.certificadoSoft,data.casinosJuego);
      $('#modalJuego').modal('show');
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
  renglon.find('select').trigger('change');
  return renglon;
};

$(document).on('click' , '.borrarJuego' , function(){
  $(this).parent().parent().remove();
})
$(document).on('change','.selectCasinos',function(){
  const t  = $(this);
  const fila = t.parent().parent().parent();
  const id_casino = t.val();
  const nro_admin = fila.find('.nro_admin').attr('list','datalistMaquinas'+id_casino).val();
  const id_maquina = obtenerIdMaquina(id_casino,nro_admin);
  if(id_maquina != null) fila.attr('data-id',id_maquina);
  else fila.removeAttr('data-id');
})
$(document).on('change','.copia input.nro_admin',function(){
  const t = $(this);
  const fila = t.parent().parent().parent();
  const id_casino = fila.find('.selectCasinos').val();
  const nro_admin = t.val();
  const id_maquina = obtenerIdMaquina(id_casino,nro_admin);
  if(id_maquina != null) fila.attr('data-id',id_maquina);
  else fila.removeAttr('data-id');
});

//agregar Tabla DE Pago
$('#btn-agregarTablaDePago').click(function(){
  agregarRenglonTablaDePago();
});

function agregarRenglonTablaDePago(){
  let fila = $('<div>').addClass('row').addClass('col-md-12').addClass('copia')
  .css('padding-top','2px').css('padding-bottom','2px');

  let input = $('<input>').attr('data-id' , 0).addClass('form-control');
  let boton_borrar = $('<button>').addClass('btn').addClass('btn-danger')
  .addClass('borrarFila').addClass('borrarTablaPago').css('display','block')
  .append($('<i>').addClass('fa fa-fw fa-trash'));

  fila.append($('<div>').addClass('col-xs-10').append(input));
  fila.append($('<div>').addClass('col-xs-2').append(boton_borrar));

  $('#tablas_pago').append(fila);
  return fila;
}

//borrar Tabla de Pago
$(document).on('click' , '.borrarTablaPago' , function(){
  var fila = $(this).parent().parent();
  fila.remove();
});

$(document).on('click' , '.borrarCertificado' , function(){
  var fila = $(this).parent().parent();
  fila.remove();
});

function obtenerIdCertificado(nro_archivo){
  const found = $('#datalistCertificados option:contains("'+nro_archivo+'")');
  let cert = null;
  for(let i = 0;i<found.length;i++){
    if(found[i].textContent == nro_archivo){
      cert = found[i].getAttribute('data-id');
      break;
    }
  }
  return cert;
}
function obtenerIdMaquina(id_casino,nro_admin){
  const found = $('#datalistMaquinas'+id_casino+' option:contains('+nro_admin+')');
  let maq = null;
  for(let i = 0;i<found.length;i++){
    if(found[i].textContent == nro_admin){
      maq = found[i].getAttribute('data-id');
      break;
    }
  }
  return maq;
}

$(document).on('click', '.verCertificado', function(){
  const input = $(this).parent().parent().find('.codigo');
  const val = input.val();
  if(obtenerIdCertificado(val) != null) window.open('certificadoSoft/' + val,'_blank');
});

$(document).on('click','.verMaquina',function(){
  const fila = $(this).parent().parent();
  const id_casino = fila.find('.selectCasinos').val();
  const nro_admin = fila.find('.nro_admin').val();
  const id_maquina = obtenerIdMaquina(id_casino,nro_admin);
  if(id_maquina != null) window.open('maquinas/' + id_maquina,'_blank');
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
    $('#mensajeEliminar').text('¿Seguro que desea eliminar el juego "' + $(this).parent().parent().find('.nombre_juego').text()+'"?');
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

function parseError(response){
  if(response == 'validation.unique'){
    return 'El valor tiene que ser único y ya existe el mismo.';
  }
  else if(response == 'validation.required'){
    return 'El valor es obligatorio.'
  }
  else{
    return null;
  }
}

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

    let certificados = [];
    $('#listaSoft .copia').each(function(){
      const texto = $(this).find('.codigo').val();
      const cert = obtenerIdCertificado(texto);
      if(cert != null) certificados.push(cert);
    });

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
      certificados: certificados,
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
              mostrarErrorValidacion($('#inputJuego'),parseError(response.nombre_juego),true);
            }

            if(typeof response.cod_identificacion !== 'undefined'){
              mostrarErrorValidacion($('#inputCodigo'),parseError(response.cod_identificacion),true);
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

function crearFilaJuego(juego){
  var fila = $(document.createElement('tr'));

  var codigo;
  juego.certificados == null ?  codigo = '-' :   codigo= juego.certificados;
  juego.cod_juego == null ?  codigojuego = '-' :   codigojuego= juego.cod_juego;

  fila.attr('id',juego.id_juego)
  .append($('<td>')
      .addClass('col-xs-3')
      .addClass('nombre_juego')
      .text(juego.nombre_juego)
  )
  .append($('<td>')
      .addClass('col-xs-3')
      .addClass('codigo_juego')
      .text(codigojuego)
  )
  .append($('<td>')
      .addClass('col-xs-3')
      .addClass('codigo_certif')
      .text(codigo)
      .attr('title',codigo)
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

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function habilitarControles(habilitado){
  $('#inputJuego').prop('readonly',!habilitado);
  $('#inputCodigoJuego').prop('readonly',!habilitado);
  if(habilitado){
    $('.borrarTablaPago').show();
    $('#btn-agregarMaquina').show();
    $('#btn-agregarTablaDePago').show();
    $('.borrarFila').show();
    $('#btn-agregarCertificado').show();
  }
  else{
    $('.borrarTablaPago').hide();
    $('#btn-agregarMaquina').hide();
    $('#btn-agregarTablaDePago').hide();
    $('.borrarFila').hide();
    $('#btn-agregarCertificado').hide();
  }
  $('#modalJuego .copia input').prop('readonly',!habilitado);
  $('#modalJuego .copia select').attr('disabled',!habilitado);
}


function mostrarJuego(juego, tablas, maquinas,certificados,casinos){
  $('#inputJuego').val(juego.nombre_juego);
  $('#inputCodigoJuego').val(juego.cod_juego);

  for (var i = 0; i < tablas.length; i++) {
    let fila = agregarRenglonTablaDePago();
    fila.find('input').val(tablas[i].codigo)
    .attr('data-id' , tablas[i].id_tabla_pago);
    fila.attr('data-id' , tablas[i].id_tabla_pago);
  }

  for (var i = 0; i < maquinas.length; i++) {
    var div = agregarRenglonMaquina();
    div.attr('data-id' ,maquinas[i].id_maquina);
    div.find('.selectCasinos').val(maquinas[i].id_casino).trigger('change');
    div.find('.nro_admin').val(maquinas[i].nro_admin).trigger('change');
    div.find('.denominacion').val(maquinas[i].denominacion);
    div.find('.porcentaje').val(maquinas[i].porcentaje_devolucion);
  } 
  for (var i = 0; i < certificados.length; i++){
    let fila = agregarRenglonCertificado();
    const cert = certificados[i].certificado;
    fila.find('.codigo').val(cert.nro_archivo)
    .attr('data-id',cert.id_gli_soft);
  }

  let selectCasinosJuego = $('#selectCasinosJuego');
  selectCasinosJuego.empty();
  selectCasinosJuego.attr('size',Math.max(casinos.length,2));
  for(let i = 0;i < casinos.length; i++){
    const c = casinos[i];
    selectCasinosJuego.append($('<option disabled>').val(c.id_casino).text(c.nombre));
  }
}

function agregarRenglonCertificado(){
  let fila =  $('#soft_mod').clone().show()
  .css('padding-top','2px')
  .css('padding-bottom','2px')
  .addClass('copia')
  .removeAttr('id');
  
  $('#listaSoft').append(fila);
  return fila;
}

$('#btn-agregarCertificado').click(function(){
  agregarRenglonCertificado();
});