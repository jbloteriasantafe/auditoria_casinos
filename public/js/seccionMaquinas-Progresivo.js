var progresivo_global;
var seleccionado_progresivo = 0;


$(document).ready(function(){
    //setear buscador de progresivo
    $('#nombre_progresivo').generarDataList("http://" + window.location.host+  "/progresivos/buscarProgresivoPorNombreYTipo",'resultados','id_progresivo','nombre_progresivo',2);
    $('#nombre_progresivo').setearElementoSeleccionado(0,"");
})

$('.modal').on('hidden.bs.modal', function() {//cuando se cierra el modal
  limpiarCollapseProgresivo(true);
  limpiarProgresivoSeleccionado();
  ocultarProgresivo();

  $('.pozo').each(function(index){
      $(this).find('.cAgregarProgresivo').attr('aria-expanded', false);
      $(this).find('.collapse').removeClass('in');
  })

})

//Agregar nuevo nivel progresivo en el modal
$('#btn-agregarNivelProgresivo').click(function(){
    $('#tablaNivelesProgresivoEncabezado').show();
    agregarNivelProgresivo(null,true);
    $('#cancelarProgresivo').show();
  });

$('#btn-agregarProgresivo').click(function(){
  $('#tablaProgresivoSeleccionado').show();
  $('#noexiste_progresivo').hide();

  $('#progresivoSeleccionado').text($('#nombre_progresivo').val()).attr('data-id' , $('#nombre_progresivo').obtenerElementoSeleccionado());
  $('#tipoSeleccionado').text($('#selectTipoProgresivos option:selected').val()).attr('data-id' ,$('#selectTipoProgresivos option:selected').val());
  $('#maximoSeleccionado').text($('#maximo').val());
  $('#porc_recuperacionSeleccionado').text($('#porcentaje_recuperacion').val());
  $('#borrarProgresivoSeleccionado').show();

  progresivo = obtenerDatosProgresivo();

  ocultarProgresivo();
  setBloqueadoProgresivo(true);

});

//crear Progresivo
$('#btn-crearProgresivo').click(function(){

    if(checkearCuerpoProgresivo()){// si devuelve true esta todo bien, si no, devuelve false y ya setea mensajes de errores
      var texto = $('#nombre_progresivo').val();
      var tipo = $('#selectTipoProgresivos option:selected').text();
      $('#tablaProgresivoSeleccionado').show();
      $('#noexiste_progresivo').hide();
      $('#progresivoSeleccionado').text(texto);
      $('#progresivoSeleccionado').attr('data-id', 0);
      $('#tipoSeleccionado').attr('data-id', $('#selectTipoProgresivos option:selected').val());
      $('#tipoSeleccionado').text(tipo);
      $('#maximoSeleccionado').text($('#maximo').val());
      $('#porc_recuperacionSeleccionado').text($('#porcentaje_recuperacion').val());
      $('#borrarProgresivoSeleccionado').show();
      progresivo = obtenerDatosProgresivo();
      //limpia tabla de niveles
      ocultarProgresivo();
      setBloqueadoProgresivo(true);
    }
})

//Agregar Máquina
$(document).on("click",  ".agregarMaquina" , function(){

      //Crear un item de la lista
      var input = $(this).parent().parent().find('input');
      var id = input.obtenerElementoSeleccionado();
      var listaMaquinas = $(this).parent().parent().parent().parent().parent().find('.listaMaquinas');
      if(id != 0){
        $.get('http://' + window.location.host +"/maquinas/obtenerConfiguracionMaquina/" + id, function(data){
          agregarMaquina( data.maquina.id_maquina,data.maquina.nro_admin,data.maquina.marca,data.maquina.modelo,listaMaquinas);
        });
        input.setearElementoSeleccionado(0,"");
      }
      console.log(id);

});

//Agregar Isla
$(document).on("click", ".agregarIsla" ,function(){
      var listaMaquinas =  $(this).parent().parent().parent().parent().parent().find('.listaMaquinas');
      var input = $(this).parent().parent().find('input');
      var id = input.obtenerElementoSeleccionado();
      if(id != 0){
        agregarIsla(id,listaMaquinas);
        input.setearElementoSeleccionado(0,"")
      }
});

$(document).on('click','.borrarMaquina',function(e){
  e.preventDefault();
  $(this).parent().parent().remove();
});

$('#nombre_progresivo').on('seleccionado',function(){
  seleccionado_progresivo = 1;
  $('#btn-crearProgresivo').hide();
   var seleccionado = $(this).obtenerElementoSeleccionado();
   limpiarCollapseProgresivo(true);
   console.log('collapse');

   if(seleccionado != 0){

       $.get('http://' + window.location.host +"/progresivos/obtenerProgresivo/" + seleccionado, function(data){

         $('#porcentaje_recuperacion').val(data.progresivo.porc_recuperacion);
         $('#maximo').val(data.progresivo.maximo);
          if(data.individual){

            //si el progresivo es individual
            $('#selectTipoProgresivos').val('INDIVIDUAL').trigger('change');

            for (var i = 0; i < data.pozos.length; i++) {
                console.log(data.pozos[i].maquinas.length);
                if(data.pozos[i].maquinas.length != 0) {//progresivo individual tiene una maquina por pozo
                  agregarMaquina(data.pozos[i].maquinas[0].id_maquina,data.pozos[i].maquinas[0].nro_admin,data.pozos[i].maquinas[0].marca,data.pozos[i].maquinas[0].modelo,$('#cuerpo_individual'+ ' .listaMaquinas'))
                }
            }

            for(var i = 0; i < data.niveles.length; i++) {
              agregarNivelProgresivo(null,true);
              $('#cuerpo_individual .NivelProgresivo').eq(i).attr('data-id' , data.niveles[i].id_nivel);
              $('#cuerpo_individual .NivelProgresivo').eq(i).find('.nro_nivel').val(data.niveles[i].nro_nivel);
              $('#cuerpo_individual .NivelProgresivo').eq(i).find('.nombre_nivel').val(data.niveles[i].nombre_nivel);
              $('#cuerpo_individual .NivelProgresivo').eq(i).find('.porc_visible').val(data.niveles[i].porc_visible);
              $('#cuerpo_individual .NivelProgresivo').eq(i).find('.porc_oculto').val(data.niveles[i].porc_oculto);
              $('#cuerpo_individual .NivelProgresivo').eq(i).find('.base').val(data.niveles[i].base);
            }

          }else{//logica de linkeado
            $('#selectTipoProgresivos').val('LINKEADO').trigger('change');
            for (var i = 0; i < data.pozos.length; i++) {
              var nro_pozo = $('#contenedorPozos').children().length + 1 ;
              var pozo = agregarPozo(nro_pozo); //agrega opzo en todos
              $('#contenedorPozos').append(pozo);

              $('#pozo_'+ nro_pozo).attr('data-id',data.pozos[i].id_pozo);
              if($('#pozo_' + (nro_pozo - 1) + ' .columna').length){//si existe otro pozo copio valores de nivel
                $("#pozo_" + nro_pozo + " .columna").replaceWith($('#pozo_' + (nro_pozo - 1) + ' .columna').clone());
                $("#pozo_" + nro_pozo + " .columna .base").val("");
              }
                for (var j = 0; j < data.pozos[i].niveles.length; j++) {
                  if(i == 0){ // si es la primer iteracino agrego los niveles
                    agregarNivelProgresivo(null,true);
                  }
                  $('#pozo_'+ nro_pozo +  ' .NivelProgresivo').eq(j).attr('data-id' , data.pozos[i].niveles[j].id_nivel);
                  $('#pozo_'+ nro_pozo +  ' .nro_nivel').eq(j).val(data.pozos[i].niveles[j].nro_nivel);
                  $('#pozo_'+ nro_pozo +  ' .nombre_nivel').eq(j).val(data.pozos[i].niveles[j].nombre_nivel);
                  $('#pozo_'+ nro_pozo +  ' .porc_visible').eq(j).val(data.pozos[i].niveles[j].porc_visible);
                  $('#pozo_'+ nro_pozo +  ' .porc_oculto').eq(j).val(data.pozos[i].niveles[j].porc_oculto);
                  $('#pozo_'+ nro_pozo +  ' .base').eq(j).val(data.pozos[i].niveles[j].base);

                  if ($('#seccionAgregarProgresivo' + nro_pozo).attr('aria-expanded') != true){
                    $('#seccionAgregarProgresivo' + nro_pozo).trigger('click');
                  }
              }
              for (var j = 0; j < data.pozos[i].maquinas.length; j++) {
                agregarMaquina(data.pozos[i].maquinas[j].id_maquina,data.pozos[i].maquinas[j].nro_admin,data.pozos[i].maquinas[j].marca,data.pozos[i].maquinas[j].modelo,$('#pozo_'+ nro_pozo + ' .listaMaquinas'))
              }
            }
          }
       })

       $('#btn-agregarProgresivo').show();
       $('#btn-cancelarProgresivo').show();
       $('#btn-crearProgresivo').hide();

     }else{
       $('#btn-agregarProgresivo').hide();
       $('#btn-crearProgresivo').show();
       $('#btn-cancelarProgresivo').show();
     }
})

$('#btn-cancelarProgresivo').on('click' , function(){
  limpiarCollapseProgresivo();
  $('#btn-cancelarProgresivo').hide();
  $('#btn-agregarProgresivo').hide();
  $('#btn-crearProgresivo').hide();
})

$('#nombre_progresivo').on('deseleccionado',function(){
  if(seleccionado_progresivo == 1){
    limpiarCollapseProgresivo(true);
    seleccionado_progresivo = 0;
  }
  $('#btn-crearProgresivo').show();
  $('#btn-cancelarProgresivo').show();
  $('#btn-agregarProgresivo').hide();
})

$('#borrarProgresivoSeleccionado').click(function(){
  setBloqueadoProgresivo(false);
  $('#noexiste_progresivo').show();
  $('#tablaProgresivoSeleccionado').hide();
  $('#tipoSeleccionado').text('');
  $('#progresivoSeleccionado').attr('data-id','');
  $('#tipoSeleccionado').attr('data-id','');
  $('#nivelesSeleccionados').empty();
  limpiarCollapseProgresivo();
  $('#seccionAgregarProgresivo').show();
  $('#tablaNivelesSeleccionados').hide();
  $('#borrarProgresivoSeleccionado').hide();
})

$('#editarProgresivoSeleccionado').click(function(){

  seleccionado = $('#progresivoSeleccionado').attr('data-id');
  setBloqueadoProgresivo(false);
  if(seleccionado != 0) {
    limpiarCollapseProgresivo();
    $.get('http://' + window.location.host +"/progresivos/obtenerProgresivo/" + seleccionado, function(data){
      $('#nombre_progresivo').setearElementoSeleccionado(seleccionado, data.progresivo.nombre_progresivo);
      $('#porcentaje_recuperacion').val(data.progresivo.porc_recuperacion);
      $('#maximo').val(data.progresivo.maximo);
      if(data.individual){// progresivo individual
        $('#selectTipoProgresivos').val('INDIVIDUAL').trigger('change');

        for (var i = 0; i < data.pozos.length; i++) {
          if(data.pozos[i].maquinas.length != 0) {//progresivo individual tiene una maquina por pozo
            agregarMaquina(data.pozos[i].maquinas[0].id_maquina,data.pozos[i].maquinas[0].nro_admin,data.pozos[i].maquinas[0].marca,data.pozos[i].maquinas[0].modelo,$('#cuerpo_individual'+ ' .listaMaquinas'))
          }
        }
        for (var i = 0; i < data.niveles.length; i++) {
          agregarNivelProgresivo(null,true);
          $('#cuerpo_individual .NivelProgresivo').eq(i).attr('data-id' , data.niveles[i].id_nivel);
          $('#cuerpo_individual .NivelProgresivo').eq(i).find('.nro_nivel').val(data.niveles[i].nro_nivel);
          $('#cuerpo_individual .NivelProgresivo').eq(i).find('.nombre_nivel').val(data.niveles[i].nombre_nivel);
          $('#cuerpo_individual .NivelProgresivo').eq(i).find('.porc_visible').val(data.niveles[i].porc_visible);
          $('#cuerpo_individual .NivelProgresivo').eq(i).find('.porc_oculto').val(data.niveles[i].porc_oculto);
          $('#cuerpo_individual .NivelProgresivo').eq(i).find('.base').val(data.niveles[i].base);
        }


      }else{//logica de linkeado
        $('#selectTipoProgresivos').val('LINKEADO').trigger('change');
        for (var i = 0; i < data.pozos.length; i++) {
          var nro_pozo = $('#contenedorPozos').children().length + 1 ;
          var pozo = agregarPozo(nro_pozo); //agrega opzo en todos
          $('#contenedorPozos').append(pozo);

          $('#pozo_'+ nro_pozo).attr('data-id',data.pozos[i].id_pozo);
          if($('#pozo_' + (nro_pozo - 1) + ' .columna').length){//si existe otro pozo copio valores de nivel
            $("#pozo_" + nro_pozo + " .columna").replaceWith($('#pozo_' + (nro_pozo - 1) + ' .columna').clone());
            $("#pozo_" + nro_pozo + " .columna .base").val("");
          }
          for (var j = 0; j < data.pozos[i].niveles.length; j++) {
            if(i == 0){ // si es la primer iteracino agrego los niveles
              agregarNivelProgresivo(null,true);
            }
            $('#pozo_'+ nro_pozo +  ' .NivelProgresivo').eq(j).attr('data-id' , data.pozos[i].niveles[j].id_nivel);
            $('#pozo_'+ nro_pozo +  ' .nro_nivel').eq(j).val(data.pozos[i].niveles[j].nro_nivel);
            $('#pozo_'+ nro_pozo +  ' .nombre_nivel').eq(j).val(data.pozos[i].niveles[j].nombre_nivel);
            $('#pozo_'+ nro_pozo +  ' .porc_visible').eq(j).val(data.pozos[i].niveles[j].porc_visible);
            $('#pozo_'+ nro_pozo +  ' .porc_oculto').eq(j).val(data.pozos[i].niveles[j].porc_oculto);
            $('#pozo_'+ nro_pozo +  ' .base').eq(j).val(data.pozos[i].niveles[j].base);

            if ($('#seccionAgregarProgresivo' + nro_pozo).attr('aria-expanded') != true){
              $('#seccionAgregarProgresivo' + nro_pozo).trigger('click');
            }
          }
          var id_maquina = $('#id_maquina').val();
          for (var j = 0; j < data.pozos[i].maquinas.length; j++) {
            if( data.pozos[i].maquinas[j].id_maquina == id_maquina ){
              agregarMaquinaActual($('#pozo_'+ nro_pozo + ' .listaMaquinas'));
            }else {
              agregarMaquina(data.pozos[i].maquinas[j].id_maquina,data.pozos[i].maquinas[j].nro_admin,data.pozos[i].maquinas[j].marca,data.pozos[i].maquinas[j].modelo,$('#pozo_'+ nro_pozo + ' .listaMaquinas'))
            }
          }

        }

      }
      limpiarProgresivoSeleccionado();
      expandirProgresivo();
      $('#btn-cancelarProgresivo').show();
      $('#btn-agregarProgresivo').show();
    })
    }else{//CREADO!!   logica si esta siendo creado
    //no se borra el collapse
    limpiarProgresivoSeleccionado();
    expandirProgresivo();
    $('#btn-cancelarProgresivo').show();
    $('#btn-crearProgresivo').show();
    }//fin mostrar creado

})

$(document).on('click','.borrarNivelProgresivo',function(){
  var index_borrar = $(this).parent().parent().index();

  $('.columna').each(function(index){$('.NivelProgresivo' , $(this)).each(function(index2){ if(index_borrar == index2){
    $(this).remove();
  } })  });

  // $(this).parent().parent().remove();
  if($('.columna .NivelProgresivo').length == 0){
    $('#tablaNivelesProgresivoEncabezado').hide();
  }
});

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
    var columna = $(this).parent().parent().find('.columna');
    agregarNivelProgresivo(null,true);
});

$(document).on("click " , ".borrarPozo" , function() {
    var nro_pozo = $(this).attr('data-pozo');
    borrarPozo(nro_pozo);
});

$(document).on("click" , ".agregarActual" , function(){
  agregarMaquina+Actual($(this).parent().parent().parent().find('ul'));
})

$('#selectTipoProgresivos').on('change' , function(){
  switch ($(this).val()) {
    case 'LINKEADO':
    $('#cuerpo_linkeado').show();
    $('#cuerpo_individual').hide();
      break;
    case 'INDIVIDUAL':
    agregarMaquinaActual($('#cuerpo_individual .listaMaquinas'));
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
  $('#contenedorPozos').append(pozo);
  $('#pozo_'+ nro_pozo + ' .buscadorMaquina').generarDataList("http://" + window.location.host+  "/maquinas/buscarMaquinaPorNumeroMarcaYModelo/" + casino_global,'resultados', 'id_maquina' , 'nro_admin' ,2 , true);;
  $('#pozo_'+ nro_pozo + ' .buscadorMaquina').setearElementoSeleccionado(0,"");
  $('#pozo_'+ nro_pozo + ' .buscadorIsla').generarDataList("http://" + window.location.host+  "/islas/buscarIslaPorCasinoYNro/" + casino_global,'islas','id_isla','nro_isla',2 , true);
  $('#pozo_'+ nro_pozo + ' .buscadorIsla').setearElementoSeleccionado(0,"");

  if($('#pozo_' + (nro_pozo - 1) + ' .columna').length){//si existe otro pozo
    $("#pozo_" + nro_pozo + " .columna").replaceWith($('#pozo_' + (nro_pozo - 1) + ' .columna').clone());
    $("#pozo_" + nro_pozo + " .columna .base").val("");
  }

  $('[data-toggle="tooltip"]').tooltip();
})

$('.switch').click(function(e) {
    e.preventDefault();
    e.stopPropagation(); //prevents event e from going to parent
});

function checkearCuerpoProgresivo(){// true -> pasó la validacion , false-> caso contrario

  var bandera = true;
  $('#nombre_progresivo').val() == "" ? (mostrarErrorValidacion($('#nombre_progresivo'), "Campo <strong>Requerido</strong>" , true), bandera = false) : false ;
  $('#selectTipoProgresivos option:selected').val() == 0 ? (mostrarErrorValidacion($('#selectTipoProgresivos'),"Seleccione un valor  <strong>válido</strong>", true), bandera = false): false ;
  (!esNumero($('#maximo').val())) ? (mostrarErrorValidacion($('#maximo'),"Valor <strong>Incorrecto</strong>", false), bandera = false ) : false ;
  (!esNumero($('#porcentaje_recuperacion').val())) ? (mostrarErrorValidacion($('#porcentaje_recuperacion') ,"Valor <strong>Incorrecto</strong>", false), bandera = false) : false ;
  switch ($('#selectTipoProgresivos option:selected').val()) {
    case 'LINKEADO':
    $.each($('#contenedorPozos').children() , function(index) {//por cada pozo linkeado

      $('.listaMaquinas',$(this)).each(function(){//
        if($(this).is(':empty')){

          $(this).parent().find('.errorVacio').append('<i class="fa fa-times style="font-size:48px;color:red"></i>');
          mostrarErrorValidacion($(this).parent().find('.errorVacio') , 'No se ha seleccionado ninguna maquina para este pozo' , false);
        };

      })

      $('.columna' , $(this)).children().each(function(){//por cada nivel
        $.each($('div' , $(this)), function(indexMenor){//por cada campo del nivel
          switch (indexMenor) {
            case 0:$(this).find('input').val() == "" ? (mostrarErrorValidacion($(this).find('input'),"Valor <strong>Incorrecto</strong>", false), bandera = false) : false ;break;//nro nivel
            case 1:$(this).find('input').val() == "" ? (mostrarErrorValidacion($(this).find('input'),"Valor <strong>Incorrecto</strong>", false), bandera = false) : false ;break;//nombre nivel
            case 2:($(this).find('input').val() == "" || !esNumero($(this).find('input').val())) ? (mostrarErrorValidacion($(this).find('input'),"Valor <strong>Incorrecto</strong>", false), bandera = false) : false ;break;//base
            case 3:($(this).find('input').val() == "" || !esNumero($(this).find('input').val())) ? (mostrarErrorValidacion($(this).find('input') , "Valor <strong>Incorrecto</strong>", false), bandera = false) : false;break;//visible
            case 4:!esNumero($(this).find('input').val()) ? (mostrarErrorValidacion($(this).find('input') , "Valor <strong>Incorrecto</strong>", false), bandera = false) : false ; break;//oculto
            default: return false;
          }
        })
      })
    })

      break;
    case 'INDIVIDUAL':

      break;
    default:

  }
  return bandera;
}

function agregarPozo(nro_pozo){
  var retorno =  '<div class="row pozo" id="pozo_'+ nro_pozo +'" data-id="0">'
  +   '<div id="seccionAgregarProgresivo'+ nro_pozo +'" style="cursor:pointer;" class="cAgregarProgresivo" data-toggle="collapse" data-target="#collapseAgregarProgresivo'+nro_pozo+'">'
  +       '<div class="row" style="border-top: 1px solid #eee; padding-top: 15px;">'
  +           '<div class="col-xs-10">'
  +               '<h4>POZO: <i class="fa fa-fw fa-angle-down"></i></h4>'
  +           '</div>'
  +       '</div>'
  +   '</div>'
  +   '<div id="collapseAgregarProgresivo'+nro_pozo+'" class="collapse" data-pozo="'+nro_pozo+'">'
  +     '<div class="row">'
  +       '<div class="col-xs-6 col-md-6 col-lg-6">'

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
  +                '<button style="background-color:#F5F5F5;border-color:#F5F5F5;" class="btn btn-warning agregarActual" type="button" data-toggle="tooltip" data-placement="top" title="Agregar máquina actual">'
  +                      '<i class="fa fa-fw fa-star"></i>'
  +                '</button>'
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
  +     '</div>'
  +   '</div> </div>' ;
  return retorno;
}

function borrarPozo(nro_pozo){
  $('#pozo_' + nro_pozo).remove();
}

/*****FUNCIONES*****/
function agregarIsla(id_isla , listaMaquinas){
  $.get( 'http://' + window.location.host +"/islas/obtenerIsla/" + id_isla , function(data){
    for (var i = 0; i < data.maquinas.length; i++) {
      agregarMaquina (data.maquinas[i].id_maquina ,data.maquinas[i].nro_admin ,data.maquinas[i].marca , data.maquinas[i].modelo , listaMaquinas)
    }
  });
}

function agregarMaquina(id_maquina,nro_admin,nombre,modelo,listaMaquinas){

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

function agregarNivelProgresivo(nivel,editable){
      var nro_nivel = ((nivel != null) ? nivel.nro_nivel: null);
      var nombre_nivel = ((nivel != null) ? nivel.nombre_nivel: null);
      var porc_oculto = ((nivel != null) ? nivel.porc_oculto: null);
      var porc_visible = ((nivel != null) ? nivel.porc_visible: null);
      var base = ((nivel != null) ? nivel.base: null);
      var maximo = ((nivel != null) ? nivel.maximo: null);

        $('.columna').each(function(){

          $(this).append($('<div>')
              .addClass('row')
              .addClass('NivelProgresivo')
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

             )
             if(editable){
               $( '.NivelProgresivo:last' , $(this))
                     .append($('<div>')
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

        })
}

function limpiarNivelesProgresivos(){
  $('#columna .NivelProgresivo input').each(function(indexMayor){
    if($(this).is('[readonly]')){
      $('#columna').empty();
    }
  });
};

function limpiarCollapseProgresivo(bandera = false){
  //si bandera viene en true mantener input del buscador
  console.log('limpia');
  if (bandera != true) {
    $('#nombre_progresivo').prop("readonly", false).val("");
    $('#nombre_progresivo').setearElementoSeleccionado(0, "");
    seleccionado_progresivo = 0;
  }
  setBloqueadoProgresivo(false);//se puede abrir
  $('#maximo').val('');
  $('#selectTipoProgresivos').val(0).trigger('change');
  $('#porcentaje_recuperacion').val(""); //Se esconde el botón de agregar
  $('#btn-cancelarProgresivo').hide();
  $('#btn-agregarProgresivo').hide();
  $('#btn-crearProgresivo').hide();
  $('#btn-agregarNivelProgresivo').show();
  $('.pozo').remove();
  $('.listaMaquinas').empty();
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

function limpiarModalProgresivo(){//como nuevo
    $('#btn-agregarNivelProgresivo').show();
    $('#btn-cancelarProgresivo').hide();
    $('#btn-crearProgresivo').hide();
    $('#btn-agregarProgresivo').hide();
    $('#borrarProgresivoSeleccionado').hide();
    $('#progresivoSeleccionado').text('No existe progresivo seleccionado.');
    $('#noexiste_progresivo').show();
    $('#tablaProgresivoSeleccionado').hide();

    $('#nivelesSeleccionados').empty();
    $('#tablaNivelesProgresivoEncabezado').hide();
    $('#tipoSeleccionado').text('');
    $('#progresivoSeleccionado').val('');
    $('#columna').empty().prop('readonly', false);
    $('#seccionAgregarProgresivo').show();
    $('#tablaNivelesSeleccionados').hide();
}

function habilitarControlesProgresivo(valor){
    if(valor){// nuevo y modificar
      $('#columna input').prop("readonly" , false);
      $('#selectTipoProgresivos').prop('disabled' ,false);
      $('#inputProgresivo').prop('readonly' ,false);
      $('#borrarProgresivoSeleccionado').hide();
      $('#seccionAgregarProgresivo').show();
    }
    else{// ver detalle
      $('#columna input').prop("readonly" , true);
      $('#columna #base').prop("readonly" , true);
      $('#selectTipoProgresivos').prop('disabled' ,true);
      $('#inputProgresivo').prop('readonly' ,true);
      $('#btn-agregarNivelProgresivo').hide();
      $('#seccionAgregarProgresivo').hide();

    }
};

function obtenerDatosProgresivo(){

    var pozos=[];
    if($('#tipoSeleccionado').text() == 'LINKEADO'){
    // if(1){

      $.each($('#contenedorPozos').children() , function(index) {//por cada pozo linkeado

      var maquinas = [];
       $('.listaMaquinas',$(this)).children().each(function(){
          maquinas.push($(this).val());
       })

      var pozo={
        id_pozo: $(this).attr('data-id'),
        maquinas: maquinas,
      }
      var niveles=[];
      $('.columna' , $(this)).children().each(function(){//por cada nivel - this :

        var nivel = {
          id_nivel:$(this).attr('data-id'),
        }
        $.each($('div' , $(this)), function(indexMenor){//por cada campo del nivel
          switch (indexMenor) {
            case 0:nivel.nro_nivel=$(this).find('input').val();break;
            case 1:nivel.nombre_nivel=$(this).find('input').val();break;
            case 2:nivel.base=$(this).find('input').val();break;
            case 3:nivel.visible=$(this).find('input').val();break;
            case 4:nivel.oculto=$(this).find('input').val();break;
            default: return false;
          }
        })
        niveles.push(nivel);
      })
      pozo.niveles = niveles;
      pozos.push(pozo);
    })
  }else {
      //si el progresivo es individual
      var maquinas = [];

      $('.listaMaquinas',$('#cuerpo_individual')).children().each(function(){// por cada li
         maquinas.push($(this).val());
      })

      var niveles=[];
      $('#cuerpo_individual .columna').children().each(function(){//por cada nivel

        var nivel = {
            id_nivel:$(this).attr('data-id'),
        }

        $.each($('div' , $(this)), function(indexMenor){//por cada campo del nivel
          switch (indexMenor) {
            case 0:nivel.nro_nivel = $(this).find('input').val();break;
            case 1:nivel.nombre_nivel = $(this).find('input').val();break;
            case 2:nivel.base = $(this).find('input').val();break;
            case 3:nivel.visible = $(this).find('input').val();break;
            case 4:nivel.oculto = $(this).find('input').val();break;
            default: return false;
          }
        })

        niveles.push(nivel);
      })
      var pozo = {
        maquinas : maquinas,
        niveles : niveles,
      }

      pozos.push(pozo);
  }

    var progresivo= {
      id_progresivo: $('#progresivoSeleccionado').attr('data-id'),
      nombre_progresivo:$('#progresivoSeleccionado').text(),
      id_tipo_progresivo: $('#tipoSeleccionado').text(),
      maximo: $('#maximoSeleccionado').text(),
      porcentaje_recuperacion: $('#porc_recuperacionSeleccionado').text(),
      pozos: pozos,
    }

    progresivo_global = progresivo;

    return progresivo;
}

function mostrarProgresivo(progresivo, casino){

  if(progresivo != null){
    $('#noexiste_progresivo').hide();
    $('#progresivoSeleccionado').text(progresivo.progresivo.nombre_progresivo);
    $('#progresivoSeleccionado').attr('data-id', progresivo.progresivo.id_progresivo);


    if(progresivo.progresivo.individual==1){
      tipoProgresivo='INDIVIDUAL';
    }else {
      tipoProgresivo='LINKEADO';
    }

    $('#tipoSeleccionado').attr('data-id', tipoProgresivo);
    $('#tipoSeleccionado').text(tipoProgresivo);

    $('#maximoSeleccionado').text(progresivo.progresivo.maximo);
    $('#porc_recuperacionSeleccionado').text(progresivo.progresivo.porc_recuperacion);
    var oculto = "-";
    for (var i = 0; i < progresivo.niveles.length; i++) {
      oculto = progresivo.niveles[i].porc_oculto == null ? "-" : progresivo.niveles[i].porc_oculto ;
      $('#nivelesSeleccionados').append($('<tr>')
            .attr('id' , progresivo.niveles[i].id_nivel_progresivo)
            .append($('<td>')
              .addClass('col-xs-2 col-xs-offset-1')
              .text(progresivo.niveles[i].nro_nivel)
            )
            .append($('<td>')
              .addClass('col-xs-3')
              .text(progresivo.niveles[i].nombre_nivel)
            )
            .append($('<td>')
              .addClass('col-xs-2')
              .text(progresivo.niveles[i].base)
            )
            .append($('<td>')
              .addClass('col-xs-2')
              .text(progresivo.niveles[i].porc_visible)
            )
            .append($('<td>')
              .addClass('col-xs-2')
              .text(oculto)
            )

      )
    $('#tablaNivelesSeleccionados').show();
    $('#tablaProgresivoSeleccionado').show();

    }
    $('#borrarProgresivoSeleccionado').show();
    $('#collapseAgregarProgresivo').removeClass('in');
    setBloqueadoProgresivo(true);
  }
  //(url, nombre_elementos, nombre_id, nombre_descripcion, char_min, is group)
  $('.buscadorMaquina').generarDataList('http://' +window.location.host + '/maquinas/buscarMaquinaPorNumeroMarcaYModelo/' + casino_global, "resultados","id_maquina" ,"nro_admin" , 2, true);
  $('.buscadorMaquina').setearElementoSeleccionado(0 , "");
  $('.buscadorIsla').generarDataList("http://" + window.location.host+  "/islas/buscarIslaPorCasinoYNro/" + casino_global,'islas','id_isla','nro_isla',1,true);;
  $('.buscadorIsla').setearElementoSeleccionado(0,"");
}

function ocultarProgresivo(){
  if($('#seccionAgregarProgresivo').attr('aria-expanded') == "true" ){
      $('#collapseAgregarProgresivo').collapse('hide');
  }
}

function expandirProgresivo(){
  console.log('expandir');
    $('#collapseAgregarProgresivo').collapse('show');

}

function setBloqueadoProgresivo(bandera){
  $('#seccionAgregarProgresivo').prop('disabled',bandera);
}

function esNumero(x){
  return !isNaN(Number(x));
}

function agregarMaquinaActual(listaMaquinas){
  $('.actual').remove();
  if(listaMaquinas.find('.actual').length == 0 ){
    listaMaquinas.prepend($('<li>')
      //Se agrega la maquina actual al progresivo
      .val(0)
      .addClass('row actual')
      .css('list-style','none')
      //Columna de NUMERO ADMIN
      .append($('<div>')
          .addClass('col-xs-3').css('margin-top','6px')
          .append('<i class="fa fa-star" style="color:#FB8C00;position:relative;left:-1px;"></i>'
                   + '<span style="color:#aaa; margin-left:7px;">Actual</span>')
      )
      .append($('<div>')
          .addClass('col-xs-2').css('margin-top','6px')
          .text($('#nro_admin').val())
      )
      //Columna de NOMBRE PROGRESIVO
      .append($('<div>')
          .addClass('col-xs-4').css('margin-top','6px')
          .text($('#marca').val())
      )

      //Columna de TIPO PROGRESIVO
      .append($('<div>')
          .addClass('col-xs-3').css('margin-top','6px')
          .text($('#modelo').val() )
      )

  );
  }
}

function recargarDatosProgresivo() {
      var nro_admin = $('#nro_admin').val();
      var marca = $('#marca').val();
      var modelo = $('#modelo').val();

      nro_admin = nro_admin != '' ? nro_admin : '-';
      marca = marca != '' ? marca : '-';
      modelo = modelo != '' ? modelo : '-';

      $('.listaMaquinas [value=0] div').eq(0).text(nro_admin);//maquina actual es la numero 0
      $('.listaMaquinas [value=0] div').eq(1).text(marca);
      $('.listaMaquinas [value=0] div').eq(2).text(modelo);
}
