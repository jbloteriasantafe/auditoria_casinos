/****************EVENTOS DEL DOM***********/
$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionarMTM').removeClass();
  $('#gestionarMTM').addClass('subMenu2 collapse in');

  $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Progresivos');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcProgresivos').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcProgresivos').addClass('opcionesSeleccionado');

  limpiarModal();

  $('#btn-buscar').trigger('click');
  //seteo buscadores para solo seccion individual
  // $('#cuerpo_individual .buscadorIsla').generarDataList("http://" + window.location.host+  "/islas/buscarIslaPorCasinoYNro/0",'islas','id_isla','nro_isla',2,true);
  // $('#cuerpo_individual .buscadorMaquina').generarDataList("http://" + window.location.host+  "/maquinas/buscarMaquinaPorNumeroMarcaYModelo/0" ,'resultados','id_maquina','nro_admin',2,true);
  // $('#cuerpo_individual .buscadorIsla').setearElementoSeleccionado(0,"");
  // $('#cuerpo_individual .buscadorMaquina').setearElementoSeleccionado(0,"");

});

//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

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
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
    if(sort_by == null){ // limpio las columnas
      $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
    }

    var formData = {
      nombre_progresivo: $('#B_nombre_progresivo').val(),
      id_tipo_progresivo: $('#B_tipo_progresivo').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    }

    $.ajax({
        type: 'POST',
        url: 'progresivos/buscarProgresivos',
        data: formData,
        dataType: 'json',
        success: function (resultados) {
            console.log(resultados);
            $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
            $('#cuerpoTabla tr').remove();
            for (var i = 0; i < resultados.data.length; i++){
              if (resultados.data[i].individual==1) {
                console.log(resultados.data[i]);
                var filaProgresivo = generarFilaTabla(resultados.data[i] , "INDIVIDUAL");
                $('#cuerpoTabla')
                    .append(filaProgresivo)
              }
              if(resultados.data[i].linkeado==1){
                console.log(resultados.data[i]);
                var filaProgresivo = generarFilaTabla(resultados.data[i] , "LINKEADO");
                $('#cuerpoTabla').append(filaProgresivo)
              }
            }
            $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

        },
        error: function (data) {
            console.log('Error:', data);
        }
      });
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| PROGRESIVOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo Progresivo
$('#btn-nuevo').click(function(e){
    $('#mensajeExito').hide();
    e.preventDefault();
    limpiarModal();
    habilitarControles(true);
    $('.btn-agregarNivelProgresivo').show();
    $('#btn-cancelar').text('CANCELAR');
    $('#btn-guardar').val("nuevo");
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('.modal-title').text('| NUEVO PROGRESIVO');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
    $('#modalProgresivo').modal('show');
});

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){
      limpiarModal();
      $('.modal-title').text('| VER MÁS');
      $('.modal-header').attr('style','font-family: Roboto-Black; background: #4FC3F7');
      $('.btn-agregarNivelProgresivo').hide();
      $('#btn-cancelar').text('SALIR');

      var id_progresivo = $(this).val();

      $.get("progresivos/obtenerProgresivo/" + id_progresivo, function(data){
          console.log(data);
          mostrarProgresivo(data.progresivo,data.individual,data.pozos,data.niveles,true);
          habilitarControles(false);
          $('#modalProgresivo').modal('show');
      });
});

//Mostrar modal con los datos del Juego cargado
$(document).on('click','.modificar',function(){
      $('#mensajeExito').hide();
      limpiarModal();
      habilitarControles(true);
      $('#btn-cancelar').text('CANCELAR');
      $('.btn-agregarNivelProgresivo').show();
      $('.modal-title').text('| MODIFICAR PROGRESIVO');
      $('.modal-header').attr('style','font-family: Roboto-Black; background: #ff9d2d');
      $('#btn-guardar').removeClass();
      $('#btn-guardar').addClass('btn btn-warningModificar');

      var id_progresivo = $(this).val();

      $.get("progresivos/obtenerProgresivo/" + id_progresivo, function(data){
          mostrarProgresivo(data.progresivo,data.individual,data.pozos , data.niveles,true);
          console.log('niveles' , data.niveles);

          // habilitarControles(true);
          $('#btn-guardar').val("modificar");
          $('#modalProgresivo').modal('show');
      });
});

//Borrar Progresivo y remover de la tabla
$(document).on('click','.eliminar',function(){
      //Cambiar colores modal
      $('.modal-title').text('ADVERTENCIA');
      $('.modal-header').removeAttr('style');
      $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

      var id_progresivo = $(this).val();
      $('#btn-eliminarModal').val(id_progresivo);
      $('#modalEliminar').modal('show');
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-desc')){
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

/***********EVENTOS DEL MODAL**********/

// $(document).on("keypress" , function(e){
//   if(e.which == 13 && $('#modalProgresivo').is(':visible')) {
//     e.preventDefault();
//     $('#btn-guardar').click();
//   }
// })

$(document).on("keyup " , ".porc_visible" , function() {
  var input = $(this).val();
  var index = $(this).parent().parent().index();
  $('.columna').each(function() {
    $(this).children().eq(index).find('.porc_visible').val(input);
  })
});

$(document).on("keyup " , ".nro_nivel" , function() {
  var input = $(this).val();
  var index = $(this).parent().parent().index();
  $('.columna').each(function() {
    $(this).children().eq(index).find('.nro_nivel').val(input);
  })
});

$(document).on("keyup " , ".porc_oculto" , function() {
  var input = $(this).val();
  var index = $(this).parent().parent().index();
  $('.columna').each(function() {
    $(this).children().eq(index).find('.porc_oculto').val(input);
  })
});

$(document).on("keyup " , ".nombre_nivel" , function() {
    var input = $(this).val();
    var index = $(this).parent().parent().index();
    $('.columna').each(function() {
      $(this).children().eq(index).find('.nombre_nivel').val(input);
    })
});

$(document).on('click','.btn-agregarNivelProgresivo',function(){
    $('#tablaNivelesProgresivoEncabezado').show();
    var columna =  $(this).parent().parent().find('.columna');
    agregarNivelProgresivo(null,true,-1);//-1 significa a todos las collumnas
});

//borrar un nivel progresivo
$(document).on('click','.borrarNivelProgresivo',function(){
    var index = $(this).parent().parent().index();

    $('.columna').each(function() {
      $(this).children().eq(index).remove();
    })

});

$('#selectTipoProgresivos').on('change' , function(){
  switch ($(this).val()) {
    case 'LINKEADO':
    $('#cuerpo_linkeado').show();
    $('#cuerpo_individual').hide();
      break;
    case 'INDIVIDUAL':
    $('#cuerpo_individual').show();
    $('#cuerpo_linkeado').hide();
      break;
    case '0':
    $('#cuerpo_individual').hide();
    $('#cuerpo_linkeado').hide();
      break;
    default:
      break;

  }
})

$('#btn-agregarPozo').click(function(){
  var nro_pozo = $('#contenedorPozos').children().length + 1 ;
  var pozo = agregarPozo(nro_pozo);
  var radio_button_group = clonarRadioButton(nro_pozo);

  $('#contenedorPozos').append(pozo);
  if($('#pozo_' + (nro_pozo - 1) + ' .columna').length){
    $("#pozo_" + nro_pozo + " .columna").replaceWith($('#pozo_' + (nro_pozo - 1) + ' .columna').clone());
  }
  $('#pozo_' + nro_pozo + ' .contenedorBuscadores').prepend(radio_button_group);
})

$(document).on('change' , '.radioGroup' , function(){
  var id_casino = $('input:checked' , $(this)).val();
  console.log(id_casino);
  $('.buscadorIsla' , $(this).parent() ).generarDataList("http://" + window.location.host+  "/islas/buscarIslaPorCasinoYNro/" + id_casino,'islas','id_isla','nro_isla',2,true);
  $('.buscadorMaquina' , $(this).parent()).generarDataList("http://" + window.location.host+  "/maquinas/buscarMaquinaPorNumeroMarcaYModelo/" + id_casino ,'resultados','id_maquina','nro_admin',2,true);
  $('.buscadorIsla' ,  $(this).parent()).setearElementoSeleccionado(0,"");
  $('.buscadorMaquina' , $(this).parent()).setearElementoSeleccionado(0,"");
})

function agregarPozo(nro_pozo){
  var retorno =  '<div class="row pozo" id="pozo_'+ nro_pozo +'" data-id="0">'
  +   '<div id="seccionAgregarProgresivo'+ nro_pozo +'" style="cursor:pointer;" class="cAgregarProgresivo" data-toggle="collapse" data-target="#collapseAgregarProgresivo'+nro_pozo+'">'
  +       '<div class="row" style="border-top: 4px solid #a0968b; padding-top: 15px;">'
  +           '<div class="col-xs-10">'
  +               '<h4>POZO: <i class="fa fa-fw fa-angle-down"></i></h4>'
  +           '</div>'
  +       '</div>'
  +   '</div>'
  +   '<div id="collapseAgregarProgresivo'+nro_pozo+'" class="collapse" data-pozo="'+nro_pozo+'">'
  +     '<div class="row">'
  +       '<div  class="col-xs-6 col-md-6 col-lg-6 contenedorBuscadores">'

  +         '<h5>Buscador Islas <i class="fa fa-fw fa-search"></i></h5>'
  +         '<div class="row">'
  +            '<div class="input-group lista-datos-group">'
  +                '<input id="" class="form-control buscadorIsla" type="text" value="" autocomplete="off">'
  +                '<span class="input-group-btn">'
  +                  '<button class="btn btn-default btn-lista-datos agregarIsla" type="button"><i class="fa fa-plus"></i></button>'
  +                '</span>'
  +            '</div>'

  +         '</div>'
  +         '<br>'
  +         '<h5>Buscador Maquinas <i class="fa fa-fw fa-search"></i></h5>'
  +         '<div class="row"> <!-- Fila de progresivos -->'

  +            '<div class="input-group lista-datos-group">'
  +                '<input id="" class="form-control buscadorMaquina" type="text" value="" autocomplete="off">'
  +                '<span class="input-group-btn">'
  +                  '<button class="btn btn-default btn-lista-datos agregarMaquina" type="button"><i class="fa fa-plus"></i></button>'
  +                '</span>'
  +            '</div>'
  +         '</div>'
  +       '</div>'
  +       '<div id="" class="col-md-6 col-lg-6">'
  +         '<div class="row">'
  +           '<div class="col-md-7 col-lg-7">'
  +               '<h5>Maquinas Seleccionadas:</h5>'
  +           '</div>'
  +           '<div class="col-md-2 col-lg-2">'

  +           '</div>'
  +           '<div class="col-md-3 col-lg-3 errorVacio">'
  +           '</div>'
  +         '</div>'
  +         '<ul class="listaMaquinas">'
  +         '</ul>'
  +       '</div></div>'
  +        '<br>'
  +       '<div class="row">'
  +         '<div class="col-lg-12">'
  +             '<h5>Niveles Progresivo <button class="btn btn-success btn-agregarNivelProgresivo" type="button"><i class="fa fa-fw fa-plus"></i> Agregar</button></h5>'
  +             '<div class="columna">'
  +             '</div>'
  +         '</div>'
  +     '</div>'
  +     '<div class="row">'
  +           '<div hidden="true" class="col-lg-3">'
  +               '<button id="cancelarProgresivo" class="btn btn-danger " type="button" name="button">'
  +                 '<i class="fa fa-fw fa-times"></i> LIMPIAR CAMPOS'
  +               '</button>'
  +           '</div>'
  +     '</div>'
  +     '<br>'
  +     '<button  class="btn btn-danger borrarPozo" type="button" name="button" style="" data-pozo="'+ nro_pozo +'"> <i class="fa fa-fw fa-times" style="position:relative; left:-1px; top:-1px;"></i>BORRAR POZO</button>'
  +     '</div><br>'
  +   '</div> </div>';
  return retorno;
}

/*****FUNCIONES*****/
function agregarIsla(id_isla , listaMaquinas , tipo_progresivo){
  $.get("islas/obtenerIsla/" + id_isla , function(data){
    switch (tipo_progresivo) {
      case 'link':
          console.log('agregarIsla-link');
          for (var i = 0; i < data.maquinas.length; i++) {
            if(existeEnDataList(data.maquinas[i].id_maquina,tipo_progresivo)){
              moverAPozo(data.maquinas[i].id_maquina,listaMaquinas);
            }else {
              agregarMaquina(data.maquinas[i].id_maquina ,data.maquinas[i].nro_admin ,data.maquinas[i].marca , data.maquinas[i].modelo , listaMaquinas);
            }
          }
          break;

      case 'individual':
        console.log('agregarIsla-individual');
        for (var i = 0; i < data.maquinas.length; i++) {
          if(!existeEnDataList(data.maquinas[i].id_maquina,tipo_progresivo)){
            agregarMaquina(data.maquinas[i].id_maquina ,data.maquinas[i].nro_admin ,data.maquinas[i].marca , data.maquinas[i].modelo , listaMaquinas);
          }
        }
        break;
      default: break;

    }

  });
}

function borrarPozo(nro_pozo){
  $('#pozo_' + nro_pozo).remove();
}

$(document).on("click " , ".borrarPozo" , function() {
    var nro_pozo = $(this).attr('data-pozo');
    borrarPozo(nro_pozo);
});

function agregarMaquina(id_maquina, nro_admin,nombre,modelo, listaMaquinas){
    listaMaquinas.append($('<li>')
        //Se agrega el id del progresivo de la lista
        .val(id_maquina)
        .addClass('row')
        .css('list-style','none')
        //Columna de NUMERO ADMIN
        .append($('<div>')
            .addClass('col-xs-2').css('margin-top','6px')
            .text(nro_admin)
        )
        //Columna de NOMBRE PROGRESIVO
        .append($('<div>')
            .addClass('col-xs-4').css('margin-top','6px')
            .text(nombre)
        )
        //Columna de TIPO PROGRESIVO
        .append($('<div>')
            .addClass('col-xs-4').css('margin-top','6px')
            .text(modelo)
        )
        //Columna BOTON QUITAR
        .append($('<div>')
            .addClass('col-xs-2')
            .append($('<button>')
                .addClass('btn').addClass('btn-danger').addClass('borrarFila').addClass('borrarMaquina')
                .append($('<i>')
                    .addClass('fa fa-fw fa-trash')
                )
            )
        )
    );
}

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (isNaN(tam)) ?  $('#herramientasPaginacion').getPageSize() : tam;
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(progresivo,tipo_progresivo){
    var fila = $(document.createElement('tr'));
    fila.attr('id','progresivo' + progresivo.id_progresivo)
        .append($('<td>')
            .addClass('col-xs-4')
            .text(progresivo.nombre_progresivo)
        )
        .append($('<td>')
            .addClass('col-xs-4')
            .text(tipo_progresivo)
        )
        .append($('<td>')
            .addClass('col-xs-4')
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                )
                .append($('<span>').text(' VER MÁS'))
                .addClass('btn').addClass('btn-info').addClass('detalle')
                .attr('value',progresivo.id_progresivo)
            )
            .append($('<span>').text(' '))
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                )
                .append($('<span>').text(' MODIFICAR'))
                .addClass('btn').addClass('btn-warning').addClass('modificar')
                .attr('value',progresivo.id_progresivo)
            )
            .append($('<span>').text(' '))
            .append($('<button>')
                .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
                )
                .append($('<span>').text(' ELIMINAR'))
                .addClass('btn').addClass('btn-danger').addClass('eliminar')
                .attr('value',progresivo.id_progresivo)
            )
        )
      return fila;
}

function habilitarControles(valor){
    if(valor){// nuevo y modificar
      $('#nombre_progresivo').prop('readonly',false);
      $('#selectTipoProgresivos').prop('disabled',false);
      $('#porcentaje_recuperacion').prop('readonly',false);
      $('#maximo').prop('readonly',false);
      $('.buscadorIsla').prop('readonly',false);
      $('.buscadorMaquina').prop('readonly',false);
      $('#btn-agregarNivelProgresivo').show();
      $('#btn-guardar').prop('disabled',false).show();
      $('#btn-guardar').css('display','inline-block');
    }
    else{// ver detalle
      $('#modalProgresivo input').prop("readonly" , true);
      $('#nombre_progresivo').prop('readonly',true);
      $('#selectTipoProgresivos').prop('disabled',true);
      $('#btn-agregarNivelProgresivo').hide();
      $('.borrarFila').remove();
      $('#btn-guardar').prop('disabled',true).hide();
      $('#btn-guardar').css('display','none');
      $('#borrarJuego').remove();
    }
}

function limpiarModal(){
    $('#frmProgresivo').trigger('reset');
    $('#columna > .NivelProgresivo').remove();
    $('#id_progresivo').val(0);
    $('#juegosSeleccionados li').remove();
    $('#inputJuego').prop("readonly" , false);
    $('#juegoSeleccionado').text("");
    $('#juegoSeleccionado').val("");
    $('#agregarJuego').css('display' , 'none');
    $('#cancelarJuego').css('display' , 'none');
    limpiarAlertas();
}

function limpiarAlertas(){
    $('#nombre_progresivo').removeClass('alerta');
    $('#alerta-nombre_progresivo').text('').hide();

    $('#columna .NivelProgresivo').each(function(){
      $(this).find('#nro_nivel').removeClass('alerta');
      $(this).find('#nombre_nivel').removeClass('alerta');
      $(this).find('#porc_oculto').removeClass('alerta');
      $(this).find('#porc_visible').removeClass('alerta');
      $(this).find('#base').removeClass('alerta');
      $(this).find('#maximo').removeClass('alerta');
    });
    $('.alertaTabla').remove();
}

function clonarRadioButton(i){
  var div_radios_clonado = $('#modelo_radio').clone();
  var id_casino = 0;
  $('input' , div_radios_clonado).each(function(){
    if($(this).is(':checked'))
      $(this).prop('checked', false);
    id_casino = $(this).val();
    $('label[for="' +  $(this).attr('id') + '"]' , div_radios_clonado).attr('for', 'link_pozo_' + i + '_' + id_casino);
    $(this).attr('id', 'link_pozo_' + i + '_' + id_casino);
    $(this).attr('name' , 'casinos_'  + i);
  })
  div_radios_clonado.removeAttr('id');
  return div_radios_clonado;
}

function mostrarProgresivo(progresivo,individual,pozos,niveles,editable){
    $('#id_progresivo').val(progresivo.id_progresivo);
    $('#nombre_progresivo').val(progresivo.nombre_progresivo);
    $('#porcentaje_recuperacion').val(progresivo.porc_recuperacion);
    $('#maximo').val(progresivo.maximo);
    var tipo = individual == 1 ? 'INDIVIDUAL' : 'LINKEADO';
    $('#selectTipoProgresivos').val(tipo);

    if(tipo == 'INDIVIDUAL'){ // LOGICA INDIVIDUAL
      $('#cuerpo_individual').show();
      $('#cuerpo_linkeado').hide();
      for (var i = 0; i < pozos.length; i++) { //SI ES INDIVIDUAL, EXISTE UN POZO POR MAQUINA
        var listaMaquinas = $('#cuerpo_individual .listaMaquinas');
        for (var j = 0; j < pozos[i].maquinas.length; j++) {
          agregarMaquina(pozos[i].maquinas[j].id_maquina , pozos[i].maquinas[j].nro_admin , pozos[i].maquinas[j].marca ,pozos[i].maquinas[j].modelo , listaMaquinas);
        }
        if(i == 0){
          for( var j =0 ; j < niveles.length ; j++){
            agregarNivelProgresivo(niveles[j],true,0);
          }
        }
      }
    }else{ //LOGICA LINKEADO
      $('#cuerpo_individual').hide();
      $('#cuerpo_linkeado').show();
      for (var i = 0; i < pozos.length; i++){
        var nro_pozo = i+1;
        var pozo_html = agregarPozo(nro_pozo);
        $('#contenedorPozos').append(pozo_html);
        $('#pozo_' + nro_pozo + ' .buscadorIsla').generarDataList("http://" + window.location.host+  "/islas/buscarIslaPorCasinoYNro/0",'islas','id_isla','nro_isla',2,true);
        $('#pozo_' + nro_pozo + ' .buscadorMaquina').generarDataList("http://" + window.location.host+  "/maquinas/buscarMaquinaPorNumeroMarcaYModelo/0" ,'resultados','id_maquina','nro_admin',2,true);
        $('#pozo_' + nro_pozo + ' .buscadorIsla').setearElementoSeleccionado(0,"");
        $('#pozo_' + nro_pozo + ' .buscadorMaquina').setearElementoSeleccionado(0,"");
        for (var j = 0; j < pozos[i].maquinas.length; j++) {
          agregarMaquina(pozos[i].maquinas[j].id_maquina ,pozos[i].maquinas[j].nro_admin ,  pozos[i].maquinas[j].marca , pozos[i].maquinas[j].modelo  ,$('#pozo_' + nro_pozo + ' .listaMaquinas'));
        }

        for (var j = 0; j < pozos[i].niveles.length; j++) {
          agregarNivelProgresivo(pozos[i].niveles[j],true,i+1);
        }

      }
    }
}

function agregarNivelProgresivo(nivel,editable,pozo){
      var id_nivel_progresivo = ((nivel != null) ? nivel.id_nivel: "");
      var nro_nivel = ((nivel != null) ? nivel.nro_nivel: null);
      var nombre_nivel = ((nivel != null) ? nivel.nombre_nivel: null);
      var porc_oculto = ((nivel != null) ? nivel.porc_oculto: null);
      var porc_visible = ((nivel != null) ? nivel.porc_visible: null);
      var base = ((nivel != null) ? nivel.base: null);
      var maximo = ((nivel != null) ? nivel.maximo: null);

      var nivel = $(document.createElement('div'));
            nivel.addClass('row')
              .addClass('NivelProgresivo')
              .attr('data-id',id_nivel_progresivo)
              .append($('<div>')
                    .addClass('col-xs-1 col-xs-offset-1')
                    .css('padding-right','0px')
                    .append($('<input>')
                        .attr('type','text')
                        .attr('placeholder','Nro')
                        .addClass('form-control nro_nivel')
                        .val(nro_nivel)
                    )
                )
                .append($('<div>')
                    .addClass('col-xs-2')
                    .css('padding-right','0px')
                    .append($('<input>')
                        .attr('type','text')
                        .attr('placeholder','Nombre Nivel')
                        .addClass('form-control nombre_nivel')
                        .val(nombre_nivel)
                    )
                )
                .append($('<div>')
                    .addClass('col-xs-2')
                    .css('padding-right','0px')
                    .append($('<input>')
                          .attr('type','text')
                          .attr('placeholder','Base')
                          .addClass('form-control base')
                          .val(base)
                    )
                )
                .append($('<div>')
                    .addClass('col-xs-2')
                    .css('padding-right','0px')
                    .append($('<input>')
                        .attr('type','text')
                        .attr('placeholder','% Visible')
                        .addClass('form-control porc_visible')
                        .val(porc_visible)
                    )
                )
                .append($('<div>')
                    .addClass('col-xs-2')
                    .css('padding-right','0px')
                    .append($('<input>')
                        .attr('type','text')
                        .attr('placeholder','% Oculto')
                        .addClass('form-control porc_oculto')
                        .val(porc_oculto)
                    )
                )

             if(editable){

                    nivel.append($('<div>')
                     .addClass('col-xs-2')
                     .append($('<button>')
                         .addClass('borrarNivelProgresivo')
                         .addClass('btn')
                         .addClass('btn-danger')
                         .addClass('borrarFila')
                         .attr('type','button')
                         .append($('<i>')
                             .addClass('fa fa-fw fa-trash')
                         )
                     )
                   )
             }
            console.log(pozo);
            switch (pozo) {
              case 0:
              $('#cuerpo_individual .columna').append(nivel);

                break;
              case -1:
                $('.columna').append(nivel);
                break;
              default:
              $('#pozo_' + pozo  + ' .columna').append(nivel);
              break;
            }


}

function moverAPozo(id_maquina, listaMaquinas){
  var listas = $('#cuerpo_linkeado .listaMaquinas').not(listaMaquinas);
  $('li' , listas).each(function(){
     if(parseInt($(this).val()) == parseInt(id_maquina)){
        var maquina_clon = $(this).clone();
        listaMaquinas.append(maquina_clon);
        $(this).remove();
     }
  })
}

// $("#contenedorFiltros").on("keypress" , function(e){ // mandar con enter ?
//   if(e.which == 13 ) {
//     e.preventDefault();
//     $('#btn-guardar').click();
//   }
// })

/****************TODOS EVENTOS DE BUSCADORES*****************/

//Agregar Máquina
$(document).on("click",  ".agregarMaquina" , function(){
  //Crear un item de la lista
  var input = $(this).parent().parent().find('input');
  var id = input.obtenerElementoSeleccionado();
  var listaMaquinas = $(this).parent().parent().parent().parent().parent().find('.listaMaquinas');
  if(id != 0){
    if(!existeEnDataList(id,tipoProgresivo())){
      $.get('http://' + window.location.host +"/maquinas/obtenerConfiguracionMaquina/" + id, function(data){
        agregarMaquina( data.maquina.id_maquina,data.maquina.nro_admin,data.maquina.marca,data.maquina.modelo,listaMaquinas);
        input.setearElementoSeleccionado(0,"");
      });

    }else {
      if(tipoProgresivo() == 'link'){
        moverAPozo(id,listaMaquinas);
      }
      input.setearElementoSeleccionado(0,"");
    }
  }
});

//Agregar Isla
$(document).on("click", ".agregarIsla" ,function(){
  var listaMaquinas =  $(this).parent().parent().parent().parent().parent().find('.listaMaquinas');
  var input = $(this).parent().parent().find('input');
  var id = input.obtenerElementoSeleccionado();
  if(id != 0){
    console.log('agregarIsla-click');
    agregarIsla(id,listaMaquinas,tipoProgresivo());
    input.setearElementoSeleccionado(0,"")
  }
});

$(document).on('click','.borrarMaquina',function(e){
  e.preventDefault();
  $(this).parent().parent().remove();
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

//Quitar eventos de la tecla Enter
$("#contenedorFiltros input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-buscar').click();
    }
});

$('#btn-eliminarModal').click(function(e){
      var id_progresivo = $(this).val();

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      })

      $.ajax({
          type: "DELETE",
          url: "progresivos/eliminarProgresivo/" + id_progresivo,
          success: function (data) {
            console.log(data);
            $('#progresivo' + id_progresivo).remove();
            $("#tablaResultados").trigger("update");
            $('#modalEliminar').modal('hide');
          },
          error: function (data) {
            console.log('Error: ', data);
          }
      });
});

//Crear nuevo progresivo / actualizar si existe
$('#btn-guardar').click(function (e) {
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      var niveles = [];

      if($('#selectTipoProgresivos').val()  == 'LINKEADO'){//si es linkeado, capturo y mando informacion de casda pozo
        var pozos = [];

        $('#contenedorPozos').children().each(function(indexPozo){
          var maquinas= [];
          var niveles= [];
          $(this).find(".listaMaquinas").children().each(function(indexMaquina){
            var maquina;
            maquina = {
                id_maquina : $(this).val(),
            }
            maquinas.push(maquina);
          });

          $(this).find(".columna").children().each(function(indexNivel){
            var nivel = {
              id_nivel: $(this).attr('data-id'),
              nro_nivel : $(this).find(".nro_nivel").val(),
              nombre_nivel: $(this).find('.nombre_nivel').val(),
              porc_oculto : $(this).find(".porc_oculto").val(),
              porc_visible: $(this).find(".porc_visible").val(),
              base: $(this).find(".base").val(),
            }
            niveles.push(nivel);

          });

          var pozo = {
            maquinas: maquinas,
            niveles: niveles,
          };

          pozos.push(pozo);

        })

        var formData = {
           id_progresivo : $('#id_progresivo').val(),
           nombre:$('#nombre_progresivo').val() ,
           tipo: $('#selectTipoProgresivos').val(),
           pozos: pozos , //si es individual manda un solo pozo
           maximo: $('#maximo').val(),
           porc_recuperacion : $('#porcentaje_recuperacion').val(),
        }

      }else { //INDIVIDUAL
        var maquinas = [];
        var  pozos = [];

        $('#cuerpo_individual').find(".columna").children().each(function(indexNivel){
          var nivel = {
            id_nivel: $(this).attr('data-id'),
            nro_nivel : $(this).find(".nro_nivel").val(),
            nombre_nivel: $(this).find('.nombre_nivel').val(),
            porc_oculto : $(this).find(".porc_oculto").val(),
            porc_visible: $(this).find(".porc_visible").val(),
            base: $(this).find(".base").val(),
          }
          niveles.push(nivel);

        });

        $('#cuerpo_individual').find('.listaMaquinas').children().each(function(indexMaquina){
          var maquina;
          var maquina;
          maquina = {
              id_maquina : $(this).val(),
          };
          maquinas.push(maquina);
        })

        var pozo = {
          maquinas: maquinas,
          niveles: niveles,
        } ;

        var formData = {
           id_progresivo : $('#id_progresivo').val(),
           nombre: $('#nombre_progresivo').val() ,
           tipo: $('#selectTipoProgresivos').val(),
           pozos: pozo, //se manda un solo pozo
           maximo: $('#maximo').val(),
           porc_recuperacion : $('#porcentaje_recuperacion').val(),
        }

      }

      var state = $('#btn-guardar').val();
      var type = "POST";
      var url = ((state == "modificar") ? 'progresivos/modificarProgresivo':'progresivos/guardarProgresivo');


      console.log(formData);
      $.ajax({
          type: type,
          url: url,
          data: formData,
          dataType: 'json',
          success: function (data) {

              $('.modal').modal('hide');

              $('#mensajeExito').show();

              var pageNumber = $('#herramientasPaginacion').getCurrentPage();
              var tam = $('#herramientasPaginacion').getPageSize();
              var columna = $('#tablaLayouts .activa').attr('value');
              var orden = $('#tablaLayouts .activa').attr('estado');

              $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
          },
          error: function (data) {
      //         //console.log('Error:', data);
      //         var response = JSON.parse(data.responseText);
      //
      //         limpiarAlertas();
      //
      //         if(typeof response.nombre_progresivo !== 'undefined'){
      //           $('#nombre_progresivo').addClass('alerta');
      //           $('#alerta-nombre-progresivo').text(response.nombre_progresivo[0]);
      //           $('#alerta-nombre-progresivo').show();
      //         }
      //
      //         var i=0;
      //         $('#columna .NivelProgresivo').each(function(){
      //           var error=' ';
      //           if(typeof response['niveles.'+ i +'.nro_nivel'] !== 'undefined'){
      //             error+=response['niveles.'+ i +'.nro_nivel']+'<br>';
      //             $(this).find('#nro_nivel').addClass('alerta');
      //           }
      //           if(typeof response['niveles.'+ i +'.nombre_nivel'] !== 'undefined'){
      //             error+=response['niveles.'+ i +'.nombre_nivel']+'<br>';
      //             $(this).find('#nombre_nivel').addClass('alerta');
      //           }
      //           if(typeof response['niveles.'+ i +'.porc_oculto'] !== 'undefined'){
      //             error+=response['niveles.'+ i +'.porc_oculto']+'<br>';
      //             $(this).find('#porc_oculto').addClass('alerta');
      //           }
      //           if(typeof response['niveles.'+ i +'.porc_visible'] !== 'undefined'){
      //             error+=response['niveles.'+ i +'.porc_visible']+'<br>';
      //             $(this).find('#porc_visible').addClass('alerta');
      //           }
      //           if(typeof response['niveles.'+ i +'.base'] !== 'undefined'){
      //             error+=response['niveles.'+ i +'.base']+'<br>';
      //             $(this).find('#base').addClass('alerta');
      //           }
      //           if(typeof response['niveles.'+ i +'.maximo'] !== 'undefined'){
      //             error+=response['niveles.'+ i +'.maximo']+'<br>';
      //             $(this).find('#maximo').addClass('alerta');
      //           }
      //           if(error != ' '){
      //           var alerta='<div class="col-xs-12"><span class="alertaTabla alertaSpan">'+error+'</span></div>';
      //             $(this).append(alerta);
      //           }
      //           i++;
      //         })

          }
      });
});

/************************************/
$('.modal').on('hidden.bs.modal', function() {//cuando se cierra el modal
  limpiarCollapseProgresivo(true);
  limpiarProgresivoSeleccionado();
  $('.columna').empty();
  $('.pozo').each(function(index){
      $(this).find('.cAgregarProgresivo').attr('aria-expanded', false);
      $(this).find('.collapse').removeClass('in');
  })

  $('.radioGroup input').prop('checked' , false);

  $('.listaMaquinas').empty();
})

function tipoProgresivo(){
  var bandera = '';
  if($('#cuerpo_individual').is(':visible')) {
    bandera = 'individual';
  }else {
    bandera = 'link';
  }
  return bandera
}

function existeEnDataList( id_maquina, tipo_progresivo){
  var bandera = false;
  switch (tipo_progresivo) {
    case 'link':
      var listas = $('#cuerpo_linkeado .listaMaquinas');
      $('li' , listas).each(function(){
          if(parseInt($(this).val()) == parseInt(id_maquina)){
            bandera=true;
            console.log('existe linkeado');
         }
      })
      break;
    case 'individual':
        var listas = $('#cuerpo_individual .listaMaquinas');
        $('li' , listas).each(function(){
            if(parseInt($(this).val()) == parseInt(id_maquina)){
              bandera=true;
              console.log('existe individual');
           }
        })
      break;

  }
    return bandera;
}

function limpiarCollapseProgresivo(bandera = false){
  //si bandera viene en true mantener input del buscador
  if (bandera != true) {
    console.log('limpia');
    $('#nombre_progresivo').prop("readonly", false).val("");
    $('#nombre_progresivo').setearElementoSeleccionado(0 , "");
    seleccionado_progresivo = 0;
  }
  $('.pozo').remove();
  $('#maximo').val('');
  $('#selectTipoProgresivos').val(0).trigger('change');
  $('#porcentaje_recuperacion').val(""); //Se esconde el botón de agregar
  $('#btn-cancelarProgresivo').hide();
  $('#btn-agregarProgresivo').hide();
  $('#btn-crearProgresivo').hide();
  $('#btn-agregarNivelProgresivo').show();
  $('.columna>.NivelProgresivo').remove();//quita los niveles de progresivo individual y linkeado
}

function limpiarProgresivoSeleccionado(){
  $('#progresivoSeleccionado').text("");
  $('#tipoSeleccionado').text("");
  $('#maximoSeleccionado').text("");
  $('#porc_recuperacionSeleccionado').text("");
  $('#noexiste_progresivo').show();
  limpiarNivelesProgresivos();
  $('#tablaProgresivoSeleccionado').hide();
  $('#tablaNivelesSeleccionados').hide();
}

function limpiarNivelesProgresivos(){
  $('#columna .NivelProgresivo input').each(function(indexMayor){
    if($(this).is('[readonly]')){
      $('#columna').empty();
    }
  });
};

function mostrarBonus(bandera){
  var asdf;
}
