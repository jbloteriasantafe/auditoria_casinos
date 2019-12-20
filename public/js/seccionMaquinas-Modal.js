var casino_global;//id casino de la maquina


$(document).ready(function(){
  //seteo al inicio el buscador de marca en el modal
  $('#marca').generarDataList("maquinas/buscarMarcas",'marcas','id_marca','marca',1,false);
  $('#marca').setearElementoSeleccionado(0,"");
  $('#error_nav_juego').hide();
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

$('#navPaqueteJuegos').click(function(){
  $('.seccion').hide();
  $('#secPaqueteJuego').show();
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
  for(let i=0;i<expedientes.length;i++){
    formData.append('expedientes['+i+'][id_expediente]',expedientes[i]);
  }

  formData.append('id_isla', isla['id_isla']);
  formData.append('id_casino', isla['id_casino']);
  formData.append('nro_isla', isla['nro_isla']);
  formData.append('codigo', isla['codigo']);
  formData.append('cantidad_maquinas' , isla['cantidad_maquinas']);
  formData.append('id_sector', isla['id_sector']);
  for (var i = 0; i < isla['maquinas'].length; i++) {
    formData.append('maquinas['+i+']', isla['maquinas'][i]);
  }

  //DATOS DE SECCION JUEGOS
  for(var i=0;i<juegos.length;i++){
    formData.append('juegos['+i+'][id_juego]', juegos[i]['id_juego']);
    formData.append('juegos['+i+'][nombre_juego]', juegos[i]['nombre_juego']);
    formData.append('juegos['+i+'][activo]', juegos[i]['activo']);
    formData.append('juegos['+i+'][denominacion]', juegos[i]['denominacion']);
    formData.append('juegos['+i+'][porcentaje_devolucion]', juegos[i]['porcentaje_devolucion']);
    formData.append('juegos['+i+'][id_pack]', juegos[i]['id_pack']);

    if(juegos[i]['tablas'].length){
      for(var j=0;j<juegos[i]['tablas'].length;j++){
        formData.append('juegos['+i+'][tabla]['+j+'][id_tabla]', juegos[i]['tablas'][j]['id_tabla']);
        formData.append('juegos['+i+'][tabla]['+j+'][nombre_tabla]', juegos[i]['tablas'][j]['nombre_tabla']);
      }
    }else{
      formData.append('juegos['+i+'][tabla]', []);
    }
  }

  //DATOS SECCION GLI HARD
  formData.append('gli_hard[id_gli_hard]', gli_hard['id_gli_hard']);
  formData.append('gli_hard[nro_certificado]', gli_hard['nro_certificado']);
  formData.append('gli_hard[file]', gli_hard['file']);
  formData.append('gli_hard[nombre_archivo]', gli_hard['nombre_archivo']);

  //DATOS SECCION FORMULA
  formData.append('formula[id_formula]', formula['id_formula']);
  formData.append('formula[cuerpoFormula]', formula['cuerpoFormula']);

  formData.append('id_log_movimiento', $('#modalMaquina #id_movimiento').val());

  console.log('informacion enviada en el data',formData);
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
            const id=$('#modalMaquina').find('#id_movimiento').val();
            $('#'+ id).find('.boton_cargar').remove();
          }
          $('#btn-buscar').trigger('click');

          //Si estuvo bien:
          // 1. Cerrar el modal de máquina.
          // 2. Mostrar el modal de éxtio de carga de máquina.
          $('#modalMaquina').modal('hide');

          if(state == 'nuevo'){
              if(data.cantidad != 0){
                if(data.cantidad == 1){
                  const mensaje = 'La máquina se dio de alta correctamente. Queda '
                  +'<span id="cantidad_maquinas_restantes" class="badge" style="background-color:#1DE9B6;Roboto-Regular;font-size:18px;margin-top:-3px;">1</span> '
                  +'máquina pendiente para cargar.';
                  mensajeExito('ÉXITO DE CARGA',mensaje,true);
                }
                else{
                  const mensaje = 'La máquina se dio de alta correctamente. Quedan '
                  +'<span id="cantidad_maquinas_restantes" class="badge" style="background-color:#1DE9B6;Roboto-Regular;font-size:18px;margin-top:-3px;">'
                  + data.cantidad 
                  +'</span> '
                  +'máquinas pendientes para cargar. Los datos de DETALLE MTM serán los de la MTM anterior, para facilitar la carga. Deberá modificar los que corresponda.'
                  mensajeExito('ÉXITO DE CARGA',mensaje,true);
                }
              }
              else{
                mensajeExito('ÉXITO DE CARGA','Se cargaron todas las máquinas con éxito.',false)
              }
        }else{
          mensajeExito('ÉXITO DE CARGA','Se ha modificado correctamente la máquina.',false);
        }

        $('#mensajeExito').show();

      },
      error: function(data){
          console.log('Error:', data);
          $('.navModal > div > i').hide();

          const conversion = {
            'nro_admin'            : { obj: '#nro_admin'                 , show: '#error_nav_maquina'},
            'nro_serie'            : { obj: '#nro_serie'                 , show: '#error_nav_maquina'},
            'marca'                : { obj: '#marca'                     , show: '#error_nav_maquina'},
            'modelo'               : { obj: '#modelo'                    , show: '#error_nav_maquina'},
            'desc_marca'           : { obj: '#desc_marca'                , show: '#error_nav_maquina'},
            'unidad_medida'        : { obj: '#unidad_medida'             , show: '#error_nav_maquina'},
            'mac'                  : { obj: '#mac'                       , show: '#error_nav_maquina'},
            'id_tipo_gabinete'     : { obj: '#tipo_gabinete'             , show: '#error_nav_maquina'},
            'id_tipo_maquina'      : { obj: '#tipo_maquina'              , show: '#error_nav_maquina'},
            'id_tipo_moneda'       : { obj: '#tipo_moneda'               , show: '#error_nav_maquina'},
            'juega_progresivo'     : { obj: '#juega_progresivo'          , show: '#error_nav_maquina'},
            'denominacion'         : { obj: '#modalMaquina #denominacion', show: '#error_nav_maquina'}, 
            'id_estado_maquina'    : { obj: '#estado'                    , show: '#error_nav_maquina'},
            'juegos'               : { obj: ''                           , show: '#error_nav_juego'  },
            'id_isla'              : { obj: ''                           , show: '#error_nav_isla'   },
            'gli_soft.id_gli_soft' : { obj: ''                           , show: '#error_nav_soft'   },
            'formula.id_formula'   : { obj: ''                           , show: '#error_nav_formula'},
            'id_juego'             : { obj: '#tablaJuegosActivos tbody'  , show: '#error_nav_juego'  },
            'id_expediente'        : { obj: '#listaExpedientes'          , show: '#error_nav_maquina'},
            'id_unidad_medida'     : { obj: '#unidad_medida'             , show: '#error_nav_maquina'}
          };

          const response = JSON.parse(data.responseText);
          const keys = Object.keys(response);
          keys.forEach(key => {
            const val = response[key][0];
            const errorResponse = conversion[key];
            if(tieneValor(val) && tieneValor(errorResponse)){
              mostrarErrorValidacion($(errorResponse.obj),parseError(val),true);
              $(errorResponse.show).show();
            }
          });
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
    );
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
      $('select').each(function(){
        $(this).removeClass('alerta');
    });
}

function limpiarModalMaquina(){
  $('#inputPack').val('-');
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
  $('#juega_progresivo_m').prop('disabled',true);
  $('#denominacion').prop('readonly',!valor);
  $('#estado').prop('disabled',!valor);
  $('#buscadorExpediente').prop('readonly',!valor);
  $('#marca_juego').prop('readonly',!valor);
  $('#tipo_moneda').prop('disabled',!valor);
  $('#unidad_medida').prop('disabled',!valor);
}

function ocultarAlertas(){
  ocultarAlertasMaquina();
  ocultarAlertasGliSoft();
  ocultarAlertasGliHard();
  $('#modalMaquina input').each(function(){
    ocultarErrorValidacion($(this));
  });
  $('#modalMaquina select').each(function(){
    ocultarErrorValidacion($(this));
  });
  $('#modalMaquina .alerta').each(function(){
    ocultarErrorValidacion($(this));
  });
}

function limpiarModal(){
  $('.navModal > div > i').hide();
  limpiarModalMaquina();
  limpiarModalJuego();
  limpiarModalGliSoft();
  limpiarModalGliHard();
  limpiarModalFormula();
  limpiarModaPaqueteJuegos();
  ocultarAlertas();
}

function habilitarControles(valor){
  habilitarControlesMaquina(valor);
  habilitarControlesJuegos(valor);
  habilitarControlesGliSoft(valor);
  habilitarControlesGliHard(valor);
  habilitarControlesFormula(valor);
}

// funcion que setea datos de la maquina de todos los tabs . Accion puede ser modificar o detalle
function mostrarMaquina(data, accion){
  if(data.maquina.id_pack==null){
    $('#navPaqueteJuegos').attr('hidden',true);
    $('#navJuego').attr('hidden',false);
  }else{
    // gestiona paquete de juegos
    $('#tablaMtmJuegoPack tbody').empty();

            for (i = 0; i < data.juego_pack_mtm.juegos.length; i++) {
                if (i==0){
                  pack=data.juego_pack_mtm.juegos[0];
                  $('#inputPackActual').val(pack.identificador);
                  $('#inputPackActual').attr("data-idPack", pack.id_pack);
                }else{
                    agregarJuegosPackMtm(data.juego_pack_mtm.juegos[i]);
                }

              }

    $('#navPaqueteJuegos').attr('hidden',false);
    $('#navJuego').attr('hidden',true);
  }
  casino_global = data.casino.id_casino;
  $('#buscadorExpediente').generarDataList("expedientes/buscarExpedientePorCasinoYNumero/"+casino_global,'resultados','id_expediente','concatenacion',2,true);
  $('#buscadorExpediente').setearElementoSeleccionado(0,"");
  if (data.maquina.juega_progresivo==0){
    $('#juega_progresivo_m').val("NO");
  }else{
    $('#juega_progresivo_m').val("SI");
  }

  $('#nro_admin').val(data.maquina.nro_admin);
  $('#marca').val(data.maquina.marca);
  $('#modelo').val(data.maquina.modelo);
  $('#unidad_medida').val(data.maquina.id_unidad_medida);
  $('#nro_serie').val(data.maquina.nro_serie);
  $('#mac').val(data.maquina.mac);
  $('#marca_juego').val(data.maquina.marca_juego);
  data.tipo_gabinete != null ? $('#tipo_gabinete').val(data.tipo_gabinete.id_tipo_gabinete) : $('#tipo_gabinete').val("") ;
  data.tipo_maquina != null ? $('#tipo_maquina').val(data.tipo_maquina.id_tipo_maquina) : $('#tipo_maquina').val("");
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

  $('#tipo_moneda').val(data.moneda != null? data.moneda.id_tipo_moneda: null);

  var text=$('#modalMaquina .modal-title').text();

  //Datos pesataña isla
  console.log(data.isla);
  if(data.isla != null){//si no tiene isla asociada, puede pasar al modifcar isla
    mostrarIsla(data.casino, data.isla ,data.sectores, data.sector);
    //seteo datos pensataña maquina
    text= text +" N°: " + data.maquina.nro_admin + " ISLA: "+data.isla.nro_isla ;
    $('#modalMaquina .modal-title').text(text);
  }else{
    text= text +" N°: " + data.maquina.nro_admin + " ISLA: SIN ASIGNAR ";
   $('#modalMaquina .modal-title').text(text);
  }

  mostrarJuegos(data.maquina.id_casino,data.juegos,data.juego_activo);

  data.gli_soft != null ? mostrarGliSofts(data.gli_soft) : null;
  data.gli_hard != null ? mostrarGliHard(data.gli_hard) : null;
  data.formula != null ? mostrarFormula(data.formula) : null;
}

function agregarJuegosPackMtm(juego){
  den =juego.denominacion!=null ? juego.denominacion : "-" ;
  dev =juego.porcentaje_devolucion!=null ? juego.porcentaje_devolucion : "-" ;
  var fila = $('<tr>').attr('id',juego.id_juego);

  fila.append($('<td>').append($('<input>')
                  .attr('type','checkbox')
                  .attr('disabled',true)

                  .prop('checked', juego.habilitado)));


  fila.append($('<td>').append($('<span>').addClass('badge')
                                          .css({'background-color':'#6dc7be','font-family':'Roboto-Regular','font-size':'18px','margin-top':'-3px'})
                                          .text(juego.nombre_juego)
                              )
             );

  fila.append($('<td>').text(den));

  fila.append($('<td>').text(dev));

  $('#tablaMtmJuegoPack').append(fila);
};

function limpiarModaPaqueteJuegos(){
    $('#inputPackActual').val("");
    $('#inputPackActual').attr("data-idPack", -1);
    $('#tablaMtmJuegoPack tbody').empty();
}

function parseError(response){
  if(response == 'validation.unique'){
    return 'El valor tiene que ser único y ya existe el mismo.';
  }
  else if(response == 'validation.required'){
    return 'El campo es obligatorio.'
  }
  else if(response == 'validation.max.string'){
    return 'El valor es muy largo.'
  }
  else if(response == 'validation.exists'){
    return 'El valor no es valido';
  }
  else{
    return response;
  }
}

function tieneValor(val){
  return typeof val !== 'undefined';
}

function mensajeExito(titulo,parrafo,mostrar_botones){
  if(mostrar_botones){
    $('#mensajeExito').addClass('fijarMensaje mostrarBotones');
  }
  else{
    $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');
  }
  $('#mensajeExito h3').text(titulo);
  $('#mensajeExito p').text(parrafo);
}