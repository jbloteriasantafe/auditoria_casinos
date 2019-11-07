$(document).ready(function(){
  // $('#noexiste_isla').show();
  // $('#headListaSoft').hide();
  limpiarModalGliSoft();
});

/* FUNCIONES COMUNES EN TODAS LAS SECCIONES */
function limpiarModalGliSoft(){
    $('#tablaSoftActivo tr').not('#datosGLISoft').remove();
}

function ocultarAlertasGliSoft(){
  $('#alerta_codigo_soft').hide();
  $('#alerta_observaciones').hide();
  $('#alerta_archivoSoft').hide();
}

function habilitarControlesGliSoft(valor){
  return;
}

function mostrarGliSofts(gli_softs){
  for(let i = 0;i<gli_softs.length;i++){
    mostrarGliSoft(gli_softs[i]);
  }
  if($('#tablaSoftActivo tr').not('#datosGLISoft').length == 0){
    $('#tablaSoftActivo').hide();
    $('#noexiste_soft').show();
  }
}

function mostrarGliSoft(gli_soft){
  if(gli_soft.id != 0){
    $('#tablaSoftActivo').show();
    let fila = $('#datosGLISoft').hide().clone().show().attr('id','');

    fila.attr('data-id',gli_soft.id);
    fila.attr('data-codigo',gli_soft.nro_archivo);
    fila.attr('data-observaciones',gli_soft.observaciones);

    fila.find('.nro_certificado_activo').text(gli_soft.nro_archivo);
    fila.find('.nombre_archivo_activo').text(gli_soft.nombre_archivo);
    const link = 'http://' + window.location.host + "/glisofts/pdf/" + gli_soft.id;
    fila.find('.nombre_archivo_activo').attr('href',link);
    fila.find('.nombre_juego_gli').text(gli_soft.juego? gli_soft.juego : '-');
    if(gli_soft.activo) fila.css('background-color','rgb(245,245,255)');

    $('#tablaSoftActivo tbody').append(fila);

    $('#listaSoftMaquina').attr('data-agregado','true');

    //Quitar mensaje
    $('#listaSoftMaquina p').hide();
  }
}
