var casino_global;//id casino de la maquina

$(document).ready(function(){
  //seteo al inicio el buscador de marca en el modal
  $('#marca').generarDataList("maquinas/buscarMarcas",'marcas','id_marca','marca',1,false);
  $('#marca').setearElementoSeleccionado(0,"");

  $('#buscadorExpediente').generarDataList("expedientes/buscarExpedientePorNumero",'resultados','id_expediente','concatenacion',2,true);
  $('#buscadorExpediente').setearElementoSeleccionado(0,"");

  $('#error_nav_juego').hide();
  $('#error_nav_progresivo').hide();
  $('#error_nav_isla').hide();
  $('#error_nav_soft').hide();
  $('#error_nav_maquina').hide();
  $('#error_nav_formula').hide();
  $('#error_nav_hard').hide();
})

//Detectar el click en el nav para cambiar los colores
$('.navModal a').click(function(e){
    $('.navModal a').removeClass();
    e.preventDefault();
    $(this).addClass('navModalActivo');
});
//Detecta los clicks para cambiar de secciones!
$('#navMaquina').click(function(){
  $('.seccion').hide();
  $('#secMaquina').show();
});

$('#navJuego').click(function(){
  $('.seccion').hide();
  $('#secJuego').show();
});

$('#navProgresivo').click(function(){
  recargarDatosProgresivo();
  $('.seccion').hide();
  $('#secProgresivo').show();
});

$('#navSoft').click(function(){
  $('.seccion').hide();
  $('#secSoft').show();
});

$('#navIsla').click(function(){
  recargarDatosIsla();
  $('.seccion').hide();
  $('#secIsla').show();
});

$('#navHard').click(function(){
  $('.seccion').hide();
  $('#secHard').show();
});

$('#navFormula').click(function(){
  $('.seccion').hide();
  $('#secFormula').show();
});
/***************
TODOS LOS EVENTOS DEL BUSCADOR DE EXPEDIENTE
****************/
$(document).on('click','.borrarExpediente',function(){
  $(this).closest('li').remove();
});
// Agregar expediente
$('.agregarExpediente').click(function(){
  var id = $('#buscadorExpediente').obtenerElementoSeleccionado();

  $.get('http://' + window.location.host + '/movimientos/obtenerExpediente/' + id , function(data){
    $('#listaExpedientes')
          .append($('<li>')
                .val(data.expediente.id_expediente)
                .addClass('row')
                .css('list-style','none').css('padding','5px 0px')
                .append($('<div>')
                      .addClass('col-xs-7')
                      .text(data.expediente.nro_exp_org+'-'+data.expediente.nro_exp_interno + '-' + data.expediente.nro_exp_control)
                      )
                .append($('<div>')
                      .addClass('col-xs-5')
                      .append($('<button>')
                            .addClass('btn').addClass('btn-danger').addClass('borrarFila').addClass('borrarExpediente')
                            .append($('<i>')
                                  .addClass('fa').addClass('fa-trash')
                                  )
                            )
                      )
                )

    $('#buscadorExpediente').setearElementoSeleccionado(0,""); //Se limpia el input
  });

});
// CREAR MÁQUINA
$('#btn-guardar').click(function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var juegos = obtenerDatosJuego();
  var progresivo =  progresivo_global ;
  var gli_soft = obtenerDatosGliSoft();
  var gli_hard = obtenerDatosGliHard();
  var formula = obtenerDatosFormula();

  console.log('form',formula);

  var isla = obtenerDatosIsla();

  var state = $('#btn-guardar').val();

  //Mandar los expedientes según el estado (NUEVO o MODIFICAR)
  var expedientes = [];

  if (state == 'nuevo') {
      var expediente = $('#M_expediente').val();
      console.log(expediente);
      expedientes.push(expediente);
  }else {
    $('#listaExpedientes li').each(function(){
      expedientes.push($(this).val());
    });
  }

  var url = (state == 'nuevo') ? 'http://' + window.location.host + '/maquinas/guardarMaquina' : '/maquinas/modificarMaquina';

  var formData = new FormData();
  //DATOS DE SECCION MAQUINA
  formData.append('id_maquina', $('#id_maquina').val());
  formData.append('nro_admin', $('#nro_admin').val());
  formData.append('marca', $('#marca').val());
  formData.append('modelo', $('#modelo').val());
  formData.append('mac', $('#mac').val());
  formData.append('id_unidad_medida', $('#unidad_medida').val());
  formData.append('id_tipo_moneda', $('#tipo_moneda').val());
  formData.append('nro_serie', $('#nro_serie').val());
  formData.append('marca_juego', $('#marca_juego').val());
  formData.append('juega_progresivo', $('#juega_progresivo').val());
  formData.append('id_tipo_gabinete', $('#tipo_gabinete').val());
  formData.append('id_tipo_maquina', $('#tipo_maquina').val());
  formData.append('denominacion', $('#modalMaquina #denominacion').val());
  formData.append('porcentaje_devolucion', $('#porcentaje_devolucion').val());
  formData.append('id_estado_maquina', $('#estado').val());
  formData.append('expedientes', expedientes);

  formData.append('id_isla', isla['id_isla']);
  formData.append('id_casino', isla['id_casino']);
  formData.append('nro_isla', isla['nro_isla']);
  formData.append('codigo', isla['codigo']);
  formData.append('modificado' , isla['modificado']);
  formData.append('cantidad_maquinas' , isla['cantidad_maquinas']);
  formData.append('id_sector', isla['id_sector']);
  for (var i = 0; i < isla['maquinas'].length; i++) {
    formData.append('maquinas['+i+']', isla['maquinas'][i]);
  }

  //DATOS DE SECCION JUEGOS
  for(var i=0;i<juegos.length;i++){
    formData.append('juego['+i+'][id_juego]', juegos[i]['id_juego']);
    formData.append('juego['+i+'][nombre_juego]', juegos[i]['nombre_juego']);
    formData.append('juego['+i+'][activo]', juegos[i]['activo']);
    formData.append('juego['+i+'][denominacion]', juegos[i]['denominacion']);
    formData.append('juego['+i+'][porcentaje_devolucion]', juegos[i]['porcentaje_devolucion']);
    // formData.append('juego['+i+'][cod_identificacion]', juegos[i]['cod_identificacion']);

    if(juegos[i]['tablas'].length){
      for(var j=0;j<juegos[i]['tablas'].length;j++){
        formData.append('juego['+i+'][tabla]['+j+'][id_tabla]', juegos[i]['tablas'][j]['id_tabla']);
        formData.append('juego['+i+'][tabla]['+j+'][nombre_tabla]', juegos[i]['tablas'][j]['nombre_tabla']);
      }
    }else{
      formData.append('juego['+i+'][tabla]', []);

    }
  }

  if(typeof(progresivo) != 'undefined'){
    //DATOS DE SECCION PROGRESIVOS
    formData.append('progresivo[id_progresivo]' , progresivo['id_progresivo']);
    formData.append('progresivo[nombre_progresivo]' , progresivo['nombre_progresivo']);
    formData.append('progresivo[id_tipo]' , progresivo['id_tipo_progresivo']);
    formData.append('progresivo[maximo]', progresivo['maximo']);
    formData.append('progresivo[porcentaje_recuperacion]', progresivo['porcentaje_recuperacion']);

    for(var j=0;j<progresivo['pozos'].length;j++){// POR CADA POZO
      for (var i = 0; i < progresivo['pozos'][j]['niveles'].length; i++) { // POR CADA NIVEL DEL POZO
        formData.append('progresivo[pozos]['+j+'][niveles]['+i+'][id_nivel]', progresivo['pozos'][j]['niveles'][i]['id_nivel']);
        formData.append('progresivo[pozos]['+j+'][niveles]['+i+'][nombre_nivel]', progresivo['pozos'][j]['niveles'][i]['nombre_nivel']);
        formData.append('progresivo[pozos]['+j+'][niveles]['+i+'][nro_nivel]', progresivo['pozos'][j]['niveles'][i]['nro_nivel']);
        formData.append('progresivo[pozos]['+j+'][niveles]['+i+'][base]', progresivo['pozos'][j]['niveles'][i]['base']);
        formData.append('progresivo[pozos]['+j+'][niveles]['+i+'][porc_visible]', progresivo['pozos'][j]['niveles'][i]['visible']);
        formData.append('progresivo[pozos]['+j+'][niveles]['+i+'][porc_oculto]', progresivo['pozos'][j]['niveles'][i]['oculto']);
      }
      for (var i = 0; i < progresivo['pozos'][j]['maquinas'].length; i++) {// POR CADA DEL POZO
        formData.append('progresivo[pozos]['+j+'][maquinas]['+i+'][id_maquina]', progresivo['pozos'][j]['maquinas'][i]);
      }
    }
  }else{
    formData.append('progresivo[id_progresivo]', -1);
  }
  //DATOS DE SECCION GLI SOFT
  formData.append('gli_soft[id_gli_soft]', gli_soft['id_gli_soft']);
  formData.append('gli_soft[nro_certificado]', gli_soft['nro_certificado']);
  formData.append('gli_soft[observaciones]', gli_soft['observaciones']);
  formData.append('gli_soft[file]', gli_soft['file']);

  //DATOS SECCION GLI HARD
  formData.append('gli_hard[id_gli_hard]', gli_hard['id_gli_hard']);
  formData.append('gli_hard[nro_certificado]', gli_hard['nro_certificado']);
  formData.append('gli_hard[file]', gli_hard['file']);

  //DATOS SECCION FORMULA
  formData.append('formula[id_formula]', formula['id_formula']);
  formData.append('formula[cuerpoFormula]', formula['cuerpoFormula']);

  formData.append('id_log_movimiento', $('#modalMaquina #id_movimiento').val());
  //FIN DATOS
  // for (var pair of formData.entries()) {
  //   console.log(pair[0]+ ', ' + pair[1]);
  // }

  $.ajax({
      type: 'POST',
      url: url,
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      success: function(data){
          console.log(data);
          if(data.cantidad==0){

            var id=  $('#modalMaquina').find('#id_movimiento').val();

            $('#'+ id).find('.boton_cargar').remove();

          }
          // $('#btn-buscar').trigger('click');

          //Si estuvo bien:
              // 1. Cerrar el modal de máquina.
              // 2. Mostrar el modal de éxtio de carga de máquina.
          $('#modalMaquina').modal('hide');

          if(state == 'nuevo'){

              $('#mensajeExito h3').text('ÉXITO DE CARGA');

              var p;

              if (data.cantidad != 0) {
                  //Mostrar los botones en el mensaje de éxito
                  $('#mensajeExito').addClass('fijarMensaje mostrarBotones');

                  if (data.cantidad == 1) {
                    p = '<p>La máquina se dio de alta correctamente. Queda '
                            +'<span id="cantidad_maquinas_restantes" class="badge" style="background-color:#1DE9B6;Roboto-Regular;font-size:18px;margin-top:-3px;">1</span> '
                            +'máquina pendiente para cargar.'
                  }else {
                    p = '<p>La máquina se dio de alta correctamente. Quedan '
                            +'<span id="cantidad_maquinas_restantes" class="badge" style="background-color:#1DE9B6;Roboto-Regular;font-size:18px;margin-top:-3px;">'+ data.cantidad +'</span> '
                            +'máquinas pendientes para cargar.'
                  }
              }
              else {
                  //Ocultar los botones en el mensaje de éxito
                  $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');

                  p = '<p>Se cargaron todas las máquinas con éxito.</p>'
              }

              $('#mensajeExito p').replaceWith(p);
        }else{
          //Ocultar los botones en el mensaje de éxito
          $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');

          $('#mensajeExito h3').text('ÉXITO DE CARGA');
          $('#mensajeExito p').text("Se ha modificado correctamente la máquina.");
        }

        $('#mensajeExito').show();

      },
      error: function(data){
          console.log('Error:', data);
          $('.navModal > div > i').hide();

          var response = JSON.parse(data.responseText);

          if(typeof response.nro_admin !== 'undefined'){
            mostrarErrorValidacion($('#nro_admin'),response.nro_admin[0],true);
            $('#error_nav_maquina').show();
          }
          if(typeof response.nro_serie !== 'undefined'){
            mostrarErrorValidacion($('#nro_serie'),response.nro_serie[0],true);
            $('#error_nav_maquina').show();
          }
          if(typeof response.marca !== 'undefined'){
            mostrarErrorValidacion($('#marca'),response.marca[0],true);
            $('#error_nav_maquina').show();
          }
          if(typeof response.modelo !== 'undefined'){
            mostrarErrorValidacion($('#modelo'),response.modelo[0],true);
            $('#error_nav_maquina').show();
          }
          if(typeof response.desc_marca !== 'undefined'){
            mostrarErrorValidacion($('#desc_marca'),response.desc_marca[0],true);
            $('#error_nav_maquina').show();
          }
          if(typeof response.unidad_medida !== 'undefined'){
            mostrarErrorValidacion($('#unidad_medida'),response.unidad_medida[0],true);
            $('#error_nav_maquina').show();
          }
          if(typeof response.mac !== 'undefined'){
            mostrarErrorValidacion($('#mac'),response.mac[0],true);
            $('#error_nav_maquina').show();
          }
          if(typeof response.id_tipo_gabinete !== 'undefined'){
            mostrarErrorValidacion($('#tipo_gabinete'),response.id_tipo_gabinete[0],true);
            $('#error_nav_maquina').show();
          }
          if(typeof response.id_tipo_maquina !== 'undefined'){
            mostrarErrorValidacion($('#tipo_maquina'),response.id_tipo_maquina[0],true);
            $('#error_nav_maquina').show();
          }
          // if(typeof response.id_casino !== 'undefined'){
          //   mostrarErrorValidacion($('#tipo_maquina'),response.id_casino[0],true);
          //   $('#alerta_casinos').text(response.id_casino[0]).show();
          // }

          if(typeof response.juego !== 'undefined'){
            $('#error_nav_juego').show();
          }

          if(typeof response.id_casino !== 'undefined' || typeof response.id_isla !== 'undefined'){
            $('#error_nav_isla').show();
          }

          if(typeof response.juega_progresivo !== 'undefined'){
            mostrarErrorValidacion($('#juega_progresivo'),response.juega_progresivo[0],true);
            $('#error_nav_maquina').show();
          }

          if(typeof response.denominacion !== 'undefined'){
            mostrarErrorValidacion($('#modalMaquina #denominacion'),response.denominacion[0],true);
            $('#error_nav_maquina').show();
          }

          if(typeof response.porcentaje_devolucion !== 'undefined'){
            mostrarErrorValidacion($('#modalMaquina #porcentaje_devolucion'),response.porcentaje_devolucion,true);
            $('#error_nav_maquina').show();
          }



          if(typeof response.estado !== 'undefined'){
            $('#estado').addClass('alerta');
            $('#alerta_estado').text(response.estado[0]).show();
          }

          if(typeof response.id_casino !== 'undefined' || typeof response.id_isla !== 'undefined'){
            $('#error_nav_isla').show();
          }

          if(typeof response['gli_soft.id_gli_soft'] !== 'undefined'){
            $('#error_nav_soft').show();
          }
          if(typeof response['formula.id_formula'] !== 'undefined'){
            $('#error_nav_formula').show();
          }

      }
    });
});

function crearFilaResultadosMaquinas(data){
  var descripcion = (data.maquina.desc_marca == null) ? ' ' : data.maquina.desc_marca;
  var fila = $(document.createElement('tr'));
  fila.attr('id',data.maquina.id_maquina)
            .append($('<td>')
                .addClass('col-xs-2')
                .text(data.maquina.nro_admin)
            )
            .append($('<td>')
                .addClass('col-xs-2')
                .text(data.maquina.marca)
            )
            .append($('<td>')
                .addClass('col-xs-3')
                .text(data.maquina.modelo)
            )
            .append($('<td>')
                .addClass('col-xs-2')
                .text(descripcion)
            )
            .append($('<td>')
                .addClass('col-xs-3')
                .addClass('accionesTD')
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                    )
                    .append($('<span>').text(' VER MÁS'))
                    .addClass('btn').addClass('btn-info').addClass('detalle')
                    .val(data.maquina.id_maquina)
                )
                .append($('<span>').text(' '))
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa').addClass('fa-fw').addClass('fa-pencil')
                    )
                    .append($('<span>').text(' MODIFICAR'))
                    .addClass('btn').addClass('btn-warning').addClass('modificar')
                    .val(data.maquina.id_maquina)
                )
                .append($('<span>').text(' '))
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa')
                        .addClass('fa-fw')
                        .addClass('fa-trash')
                    )
                    .append($('<span>').text(' ELIMINAR'))
                    .addClass('btn').addClass('btn-danger').addClass('eliminar')
                    .val(data.maquina.id_maquina)
                )
            )
    return fila;
}

function ocultarAlertasMaquina(){
      $('#alerta_nro_admin').text('').hide();
      $('#alerta_nro_serie').text('').hide();
      $('#alerta_marca').text('').hide();
      $('#alerta_mac').text('').hide();
      $('#alerta_modelo').text('').hide();
      $('#alerta_gabinete').text('').hide();
      $('#alerta_unidad_medida').text('').hide();
      $('#alerta_desc_marca').text('').hide();
      $('#alerta_tipo').text('').hide();
      $('#alerta_casinos').text('').hide();
      $('#alerta_nro_isla').text('').hide();
      $('#alerta_juega_progresivo').text('').hide();
      $('#alerta_denominacion').text('').hide();
      $('#alerta_estado').text('').hide();
      $('input').each(function(){
          $(this).removeClass('alerta');
      });
}

function limpiarModalMaquina(){
  $('#frmMaquina').trigger('reset');
  $('#listaExpedientes li').remove();
  ocultarAlertasMaquina();
}

function habilitarControlesMaquina(valor){
  $('#nro_admin').prop('readonly',!valor);
  $('#marca').prop('readonly',!valor);
  $('#modelo').prop('readonly',!valor);
  $('#unidad_medida').prop('readonly',!valor);
  $('#nro_serie').prop('readonly',!valor);
  $('#mac').prop('readonly',!valor);
  $('#desc_marca').prop('readonly',!valor);
  $('#tipo_gabinete').prop('disabled',!valor);
  $('#tipo_maquina').prop('disabled',!valor);
  $('#casino').prop('disabled',!valor);
  $('#nro_isla').prop('readonly',!valor);
  $('#juega_progresivo').prop('disabled',!valor);
  $('#denominacion').prop('readonly',!valor);
  $('#estado').prop('disabled',!valor);
  $('#buscadorExpediente').prop('readonly',!valor);
}

function ocultarAlertas(){
  ocultarAlertasMaquina();
  //ocultarAlertasJuegos();
  //ocultarAlertasProgresivo();
  ocultarAlertasGliSoft();
  ocultarAlertasGliHard();
  //ocultarAlertasFormula();
}

function limpiarModal(){
  $('.navModal > div > i').hide();
  limpiarModalMaquina();
  limpiarModalJuego();
  limpiarModalProgresivo();
  limpiarModalGliSoft();
  limpiarModalGliHard();
  limpiarModalFormula();
}

function habilitarControles(valor){
  habilitarControlesMaquina(valor);
  habilitarControlesJuegos(valor);
  habilitarControlesProgresivo(valor);
  habilitarControlesGliSoft(valor);
  habilitarControlesGliHard(valor);
  habilitarControlesFormula(valor);
}

function mostrarMaquina(data, accion){// funcion que setea datos de la maquina de todos los tabs . Accion puede ser modificar o detalle
  casino_global = data.casino.id_casino;
  //seteo datos pensataña maquina
  $('#nro_admin').val(data.maquina.nro_admin);
  $('#marca').val(data.maquina.marca);
  $('#modelo').val(data.maquina.modelo);
  $('#unidad_medida').val(data.maquina.id_unidad_medida);
  $('#nro_serie').val(data.maquina.nro_serie);
  $('#mac').val(data.maquina.mac);
  $('#marca_juego').val(data.maquina.marca_juego);
  data.tipo_gabinete != null ? $('#tipo_gabinete').val(data.tipo_gabinete.id_tipo_gabinete) : $('#tipo_gabinete').val(0) ;
  data.tipo_maquina != null ? $('#tipo_maquina').val(data.tipo_maquina.id_tipo_maquina) : $('#tipo_maquina').val(0);
  $('#estado').val(data.maquina.id_estado_maquina);
  $('#porcentaje_devolucion').val(data.maquina.porcentaje_devolucion);
  if(data.maquina.juega_progresivo == 1){
    $('#juega_progresivo').val(0);
  }
  else {
    $('#juega_progresivo').val(1);
  }
  $('#denominacion').val(data.maquina.denominacion);
  if(data.expedientes != null){
    for(var i=0; i < data.expedientes.length; i++){
      $('#listaExpedientes').append($('<li>')
            .val(data.expedientes[i].id_expediente)
            .addClass('row')
            .css('list-style','none').css('padding','5px 0px')
            .append($('<div>')
                  .addClass('col-xs-7')
                  .text(data.expedientes[i].nro_exp_org+'-'+data.expedientes[i].nro_exp_interno + '-' + data.expedientes[i].nro_exp_control)
                  )
            .append($('<div>')
                  .addClass('col-xs-5')
                  .append($('<button>')
                        .addClass('btn').addClass('btn-danger').addClass('borrarFila').addClass('borrarExpediente')
                        .append($('<i>')
                              .addClass('fa').addClass('fa-trash')
                              )
                        )
                  )
            )
    }
  }

  //Datos pesataña isla
  console.log(data.isla);
  if(data.isla != null){//si no tiene isla asociada, puede pasar al modifcar isla
    mostrarIsla(data.casino, data.isla ,data.sectores, data.sector);
  }

  mostrarJuegos(data.juegos,data.juego_activo);

  data.progresivo != null ? mostrarProgresivo(data.progresivo, data.id_casino) : mostrarProgresivo(null,data.id_casino);
  data.gli_soft != null ? mostrarGliSoft(data.gli_soft) : null;
  data.gli_hard != null ? mostrarGliHard(data.gli_hard) : null;
  data.formula != null ? mostrarFormula(data.formula) : null;
}
