$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#layout').removeClass();
  $('#layout').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Layout Total');
  $('#opcLayoutTotal').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcLayoutTotal').addClass('opcionesSeleccionado');

  $('#fechaControlSinSistema,#dtpBuscadorFecha,#fechaGeneracion').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    ignoreReadonly: true,
  });

  limpiarModales();
  $('#btn-buscar').trigger('click',[1,10,'layout_total.fecha','desc']);
});

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
  window.open('layouts/generarPlanillaLayoutTotales/' + $(this).val(),'_blank');
});

//Opacidad del modal al minimizar
$('.minimizar').click(function(){
  $('.modal-backdrop').css('opacity',$(this).data("minimizar")? '0.1' : '0.5');
  $(this).data("minimizar",!$(this).data("minimizar"));
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
	$('#modalAyuda').modal('show');
});

//ABRIR MODAL DE NUEVO LAYOUT
$('#btn-nuevoLayoutTotal').click(function(e){
  e.preventDefault();
  $('#modalNuevoLayoutTotal').modal('show');
  $.get("obtenerFechaActual", function(data){
    $('#fechaActual').val(data.fecha);
    $('#fechaDate').val(data.fechaDate);
  });
});

$('#btn-finalizarValidacion').click(function(e){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host +'/layouts/validarLayoutTotal',
      data: {
        id_layout_total: $('#id_layout_total').val(),
        observacion_validacion: $('#observacion_validar').val(),
      },
      dataType: 'json',
      success: function (data) {
        $('#mensajeExito h3').text('ÉXITO DE VALIDACIÓN');
        $('#mensajeExito .cabeceraMensaje').removeClass('modificar');
        $('#mensajeExito p').text("Se ha validado correctamente el control de Layout Total.");
        $('#mensajeExito').show();
        $('#btn-buscar').trigger('click');
        $('#modalValidarControl').modal('hide');
      },
      error: function (data) {
        if(typeof data.responseJSON.observacion_validacion !== 'undefined'){
          mostrarErrorValidacion($('#observacion_validacion'),data.responseJSON.observacion_validacion[0] ,true );
        }
      }
  });
})

$("#btn-layoutSinSistema").click(function(e){
  e.preventDefault();
  $('#modalLayoutSinSistema').modal('show');
})

$("#btn-backup").click(function(){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  $.ajax({
    type: "POST",
    url: 'http://' + window.location.host +'/layouts/usarLayoutTotalBackup',
    data: {
      fecha: $('#fechaLayoutSinSistema').val(),
      fecha_generacion: $('#fechaGeneracionSinSistema').val(),
      id_casino: $('#casinoSinSistema option:selected').val(),
    },
    dataType: 'json',
    success: function (data) {
      const pageNumber = $('#herramientasPaginacion').getCurrentPage();
      const tam = $('#tituloTabla').getPageSize();
      const columna = $('#tablaLayouts .activa').attr('value');
      const orden = $('#tablaLayouts .activa').attr('estado');
      $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
      $('frmLayoutSinSistema').trigger('reset');
      $('#modalLayoutSinSistema').modal('hide');
    },
    error: function (data) {
      const response = data.responseJSON;
      if(typeof response.fecha !== 'undefined'){
        mostrarErrorValidacion($('#fecha_backup'),'Valor inválido',true);
      }
      if(typeof response.fecha_generacion !== 'undefined'){
        mostrarErrorValidacion($('#fecha_generacion_backup'),'Valor inválido',true);
      }
      if(typeof response.id_casino !== 'undefined'){
        mostrarErrorValidacion($('#casinoSinSistema'),'Valor inválido',true);
      }
    }
  });
})

//GENERAR RELEVAMIENTO
$('#btn-generar').click(function(e){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host +'/layouts/crearLayoutTotal',
      data: {
        id_casino: $('#casino').val(),
        turno: $('#turno').val(),
      },
      dataType: 'json',
      beforeSend: function(data){
        $('#modalNuevoLayoutTotal').find('.modal-footer').children().hide();
        $('#modalNuevoLayoutTotal').find('.modal-body').children().hide();
        $('#iconoCarga').show();
      },
      success: function (data) {
        $('#modalNuevoLayoutTotal').modal('hide');
        $('#frmLayoutTotal').trigger('reset');
        const pageNumber = $('#herramientasPaginacion').getCurrentPage();
        const tam = $('#tituloTabla').getPageSize();
        const columna = $('#tablaLayouts .activa').attr('value');
        const orden = $('#tablaLayouts .activa').attr('estado');
        $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);

        let iframe = document.getElementById("download-container");
        if (iframe === null){
            iframe = document.createElement('iframe');
            iframe.id = "download-container";
            iframe.style.visibility = 'hidden';
            document.body.appendChild(iframe);
        }
        iframe.src = data.url_zip;
      },
      error: function (data) {
        $('#iconoCarga').hide();
        $('#modalNuevoLayoutTotal').find('.modal-footer').children().show();
        $('#modalNuevoLayoutTotal').find('.modal-body').children().show();

        const response = JSON.parse(data.responseText);
        if(typeof response.id_casino !== 'undefined'){
          mostrarErrorValidacion($('#casino'), response.id_casino[0] ,true);
        }
        if(typeof response.turno !== 'undefined'){
          mostrarErrorValidacion($('#turno'), "Valor de turno incorrecto.",true);
        }
      }
  });
});


function cargarDivInactivas(id_layout_total,modo,done = function (x){return;}){
  $.get('http://' + window.location.host +'/layouts/obtenerLayoutTotal/' + id_layout_total, function(data){
    $('#fecha_layout').val(data.layout_total.fecha);
    $('#fecha_generacion_layout').val(data.layout_total.fecha_generacion);
    $('#casino_layout').val(data.casino.nombre);
    $('#turno_layout').val(data.layout_total.turno);
    const dtp = $('#dtpFechaEjecucionLayout').data('datetimepicker');
    if(dtp && data.layout_total.fecha_ejecucion){
      dtp.setDate(new Date(data.layout_total.fecha_ejecucion));
    }
    else{
      $('#fecha_ejecucion_layout').val(data.layout_total.fecha_ejecucion);
    }
    $('#observaciones_fisca_layout').val(data.layout_total?.observacion_fiscalizacion);

    $('#fiscalizador_carga_layout').val(data.usuario_cargador?.nombre);
    $('#fiscalizador_toma_layout').generarDataList('usuarios/buscarUsuariosPorNombreYCasino/'+ data.casino.id_casino,'usuarios','id_usuario','nombre',2);
    $('#fiscalizador_toma_layout').setearElementoSeleccionado(0,"");
    if (data.usuario_fiscalizador){
      $('#fiscalizador_toma_layout').setearElementoSeleccionado(data.usuario_fiscalizador.id_usuario,data.usuario_fiscalizador.nombre);
    }

    $('#observaciones_adm_layout').val(data.layout_total?.observacion_validacion);

    $('#modalLayoutTotal').data('sectores',data.sectores);
    if('detalles' in data){
      for (let i = 0; i < data.detalles.length; i++) {
        agregarNivel(data.sectores, data.detalles[i],modo);
      }
    }
    done();
  });
}

function cargarDivActivas(id_layout_total,modo,done = function (x){return;}){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "GET",
    url: 'layouts/islasLayoutTotal/'+id_layout_total,
    dataType: 'json',
    success: function(data){
      const sectorEjemplo = $('#sectorEjemplo').clone().attr('id','').show();
      const filaEjemplo = $('#filaEjemploActivas').clone().attr('id','').show();
      
      let islaEjemplo = null;
      if(modo == "validar" || modo == "ver") islaEjemplo = $('#islaEjemploValidar').clone();
      else if(modo == "cargar") islaEjemplo = $('#islaEjemplo').clone();
      islaEjemplo.attr('id','').show();

      const activas_x_fila = filaEjemplo.attr('activas_por_fila');
      if(data.length == 0) $('#activas_layout').append('<h4>No hay islas asociadas a este layout total</h4>');
      for(let z = 0;z<data.length;z++){
        const sector = data[z];
        let sector_html = sectorEjemplo.clone();
        sector_html.find('.nombre').text(sector.descripcion);
        sector_html.attr('data-id-sector',sector.id_sector);
        let fila = null;
        for(let i=0;i<sector.islas.length;i++){
          const isla = sector.islas[i];
          if(i%activas_x_fila == 0){
            fila = filaEjemplo.clone();
            sector_html.find('.cuerpoTabla').append(fila);
          }

          let isla_html = islaEjemplo.clone();
          isla_html.attr('data-id-isla',isla.id_isla);
          isla_html.find('.textoIsla').text(isla.nro_isla);
          if(modo == "cargar"){
            isla_html.find('.inputIsla').val(isla.maquinas_observadas);
          }
          else {//validar y ver
            const observado = isla.maquinas_observadas | 0;//castea a int, undefined/null -> 0
            const sistema = isla.cantidad_maquinas | 0;
            isla_html.find('.observado').text(observado);
            isla_html.find('.sistema').text(sistema);
            isla_html.find('.textoIsla').addClass(observado == sistema? 'correcto' : 'incorrecto');
          }
          fila.append(isla_html);
        }

        if(modo == "validar" || modo == "ver"){//Total por sector
          let suma = islaEjemplo.clone();
          suma.addClass('total');
          suma.find('.textoIsla').text('TOTAL');
          suma.find('.observado').text(sector.total_observadas);
          suma.find('.sistema').text(sector.total_sistema);
          suma.find('.textoIsla').addClass(sector.total_observadas == sector.total_sistema? 'correcto': 'incorrecto');
          let width = suma.css('width');
          width = parseFloat(width.substr(0,width.length-1))*2;
          suma.css('width',width+'%');
          //Si no hay mas espacio o solo hay espacio para 1 lo pongo en otra fila
          //Porque ocupa 2 espacios.
          if(sector.islas.length%activas_x_fila == 0 || (sector.islas.length+1)%activas_x_fila == 0){
            fila = filaEjemplo.clone();
            sector_html.find('.cuerpoTabla').append(fila);
          }
          fila.append(suma);
        }

        $('#activas_layout').append(sector_html);
      }
      if(modo == "carga"){
        $('#activas_layout input').eq(0).change();//Trigger change para que se actualize el total.
      }
      done();
    },
    error: function(data){
      console.log(data);
    }
  });
}

$(document).on('change','#activas_layout .sector input',function(e){
  e.preventDefault();
  let total = 0;
  const inputs = $('#activas_layout .sector input');
  inputs.each(function(i,input){
    const val = parseInt($(input).val());
    total += (isNaN(val)? 0 : val);
  });
  $('#total_activas_layout input').val(total);
})

//Esta funcion hace un "post processing" de las pestañas de activas e inactivas, 
//esto esta asi porque se tuvo que adaptar codigo existente, que no tenia tiempo de cambiar.
function cargarDivDiferencias(){
  let tabla = $('#tablaDiferenciasEjemplo').clone().attr('id','').show();
  const filaEjemplo = tabla.find('.diferenciasFilaEjemplo').clone().removeClass('diferenciasFilaEjemplo');
  tabla.find('.diferenciasFilaEjemplo').remove();

  let sectores = [];
  //Busco en el div de activas cada sector y le saco la info
  $('#activas_layout div.sector').each(function(){
    const t = $(this);
    let islaTotal = t.find('.total');
    const nombre = t.find('.nombre').text();
    const observado = parseInt(islaTotal.find('.observado').text());
    const sistema = parseInt(islaTotal.find('.sistema').text());
    const id_sector = t.attr('data-id-sector');
    sectores[id_sector] = [];
    sectores[id_sector]['nombre'] = nombre;
    sectores[id_sector]['activas'] = observado;
    sectores[id_sector]['sistema'] = sistema;
    sectores[id_sector]['inactivas'] = 0;
    sectores[id_sector]['islaTotal'] = islaTotal;
  });

  islas_con_inactivas = [];
  //Busco en el div de inactivas, saco cuantas invalidas hay por sector y en que isla.
  $('#inactivas_layout .NivelLayout').each(function(){
    const t = $(this);
    const id_sector = t.find('select').val();
    const nro_isla = t.find('.nro_isla').val();
    //Si es un relevamiento viejo que no tiene asociada islas 
    //Esto no va a estar seteado por lo que se ignora.
    if(id_sector in sectores){
      sectores[id_sector]['inactivas']++;
      const init = !(nro_isla in islas_con_inactivas);
      if(init) islas_con_inactivas[nro_isla] = 1;
      else islas_con_inactivas[nro_isla]++;
    }
  });

  //Agrego una fila por cada uno y seteo la celda total en ACTIVAS
  let total_activas = 0;
  let total_inactivas = 0;
  let total_relevadas = 0;
  let total_sistema = 0;
  let total_diff = 0;
  sectores.forEach(function(val,key){
    const fila = filaEjemplo.clone();
    const nombre = val['nombre'];
    const activas = val['activas'];
    total_activas += activas;
    const inactivas = val['inactivas'];
    total_inactivas += inactivas;
    const relevado = activas + inactivas;
    total_relevadas += relevado;
    const sistema = val['sistema'];
    total_sistema += sistema;
    const diff = Math.abs(sistema - relevado);
    total_diff += diff;

    fila.attr('data-id-sector',key);
    fila.find('.diferenciasSector').text(nombre);
    fila.find('.diferenciasActivas').text(activas);
    fila.find('.diferenciasInactivas').text(inactivas);
    fila.find('.diferenciasTotal').text(relevado);
    fila.find('.diferenciasTotalSistema').text(sistema);
    fila.find('.diferenciasDiferencia').text(diff)
    .addClass(diff? 'incorrecto' : 'correcto');
    tabla.find('.cuerpoTablaDiferencias').append(fila);

    let islaTotal = val['islaTotal'];
    islaTotal.find('.textoIsla').removeClass('correcto').removeClass('incorrecto');
    islaTotal.find('.textoIsla').addClass(diff == 0? 'correcto' : 'incorrecto');
    if(inactivas != 0) islaTotal.find('.inactivas').text('+'+inactivas);
    else islaTotal.find('.inactivas').text('');
  });

  {
    const fila = filaEjemplo.clone();
    fila.attr('data-id-sector',-1);
    fila.find('.diferenciasSector').text('').addClass('borde_superior');
    fila.find('.diferenciasActivas').text(total_activas).addClass('borde_superior');
    fila.find('.diferenciasInactivas').text(total_inactivas).addClass('borde_superior');
    fila.find('.diferenciasTotal').text(total_relevadas).addClass('borde_superior');
    fila.find('.diferenciasTotalSistema').text(total_sistema).addClass('borde_superior');
    fila.find('.diferenciasDiferencia').text(total_diff)
    .addClass(total_diff? 'incorrecto' : 'correcto').addClass('borde_superior');
    tabla.find('.cuerpoTablaDiferencias').append(fila);
  }

  //Busco la isla correspondiente y le agrego las inactivas
  $('#activas_layout div.sector .isla').each(function(){
    let t = $(this);
    let textoIsla = t.find('.textoIsla');
    const nro_isla = parseInt(textoIsla.text());
    if(nro_isla in islas_con_inactivas){
      //Si la isla tiene inactivas, saco la evaluacion de correcto e incorrecto
      //y me fijo de vuelta si da con las inactivas
      textoIsla.removeClass('correcto').removeClass('incorrecto');
      const observadas = parseInt(t.find('.observado').text());
      const sistema = parseInt(t.find('.sistema').text());
      const inactivas = islas_con_inactivas[nro_isla];
      t.find('.inactivas').text('+'+inactivas);
      const correcto = sistema == (inactivas+observadas); 
      textoIsla.addClass(correcto? 'correcto' : 'incorrecto');
    }
  });

  $('#diferencias_layout').append(tabla);
}

$('.tabTitle').on('click',function(){
  const modal = $(this).closest('.modal');
  modal.find('.tabs h4').removeClass('subrayado');
  modal.find('.tabDiv').hide();
  $(this).addClass('subrayado');
  $($(this).attr('tabDiv')).show();
});

$(document).on('click','.validar',function(e){
  e.preventDefault();
  mostrarModalLayoutTotal($(this).val(),'validar');
});

$(document).on('click','.ver',function(e){
  e.preventDefault();
  mostrarModalLayoutTotal($(this).val(),'ver');
});

$(document).on('click','.carga',function(e){
  e.preventDefault();
  mostrarModalLayoutTotal($(this).val(),'cargar');
});

function mostrarModalLayoutTotal(id_layout_total,modo){  
  $('#btn_finalizar_layout').val(id_layout_total);
  if(modo == 'validar'){
    $('#modalLayoutTotal .modal-header').css('background-color','#69F0AE');
    $('#modalLayoutTotal .modal-title').text('VALIDAR CONTROL LAYOUT');
    $('#dtpFechaEjecucionLayout span').hide();
    $('#dtpFechaEjecucionLayout input').prop('readonly',true);
    const dtp = $('#dtpFechaEjecucionLayout').data('datetimepicker');
    if(dtp) dtp.remove();
    $('#btn_finalizar_layout').text('VALIDAR').show();
    $('#btn_finalizar_layout').show();
    $('#btn_agregar_inactiva_layout').hide();
    $('#tabDiferencias').show();
    $('#fiscalizador_toma_layout').prop('readonly',true);
    $('#observaciones_fisca_layout').attr('disabled',true);
    $('#observaciones_adm_layout').attr('disabled',false).closest('div').show();
    $('#total_activas_layout').hide();
  }
  else if(modo == 'ver'){
    $('#modalLayoutTotal .modal-header').css('background-color','#69F0AE');
    $('#modalLayoutTotal .modal-title').text('VISUALIZAR CONTROL LAYOUT');
    $('#dtpFechaEjecucionLayout span').hide();
    $('#dtpFechaEjecucionLayout input').prop('readonly',true);
    const dtp = $('#dtpFechaEjecucionLayout').data('datetimepicker');
    if(dtp) dtp.remove();
    $('#btn_finalizar_layout').hide();
    $('#btn_guardartemp_layout').hide();
    $('#btn_agregar_inactiva_layout').hide();
    $('#tabDiferencias').show();
    $('#fiscalizador_toma_layout').prop('readonly',true);
    $('#observaciones_fisca_layout').attr('disabled',true);
    $('#observaciones_adm_layout').attr('disabled',true).closest('div').show();
    $('#total_activas_layout').hide();
  }
  else if(modo == 'cargar'){
    $('#modalLayoutTotal .modal-header').css('background-color','#FF6E40');
    $('#modalLayoutTotal .modal-title').text('CARGAR CONTROL LAYOUT');
    $('#dtpFechaEjecucionLayout span').show();
    $('#dtpFechaEjecucionLayout input').prop('readonly',false);
    $('#dtpFechaEjecucionLayout').datetimepicker({
      todayBtn:  1,
      language:  'es',
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd MM yyyy HH:ii',
      pickerPosition: "bottom-left",
      startView: 2,
      minView: 0,
      ignoreReadonly: true,
      minuteStep: 5,
    });
    $('#btn_finalizar_layout').show();
    $('#btn_guardartemp_layout').show();
    $('#btn_agregar_inactiva_layout').show();
    $('#tabDiferencias').hide();
    $('#fiscalizador_toma_layout').prop('readonly',false);
    $('#observaciones_fisca_layout').attr('disabled',false);
    $('#observaciones_adm_layout').attr('disabled',true).closest('div').hide();
    $('#total_activas_layout').show();
  }
  else return;

  $('#tabActivas').click();
  
  cargarDivActivas(id_layout_total,modo,function(){
    cargarDivInactivas(id_layout_total,modo,function(){
      if(modo == "ver" || modo == "validar"){
        cargarDivDiferencias();
      }
      $('#modalLayoutTotal').modal('show');
    });
  });
}


$('#btn-eliminarModal').on('click', function() {
  $.ajaxSetup({ headers: {  'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } })
  $.ajax({
      type: "DELETE",
      url: "layouts/eliminarLayoutTotal/" + $(this).val(),
      success: function (data) {
        $('#btn-buscar').trigger('click');
      },
      error: function (data) {
        console.log('Error: ', data);
      }
  })
});

$(document).on('click','.eliminar',function(e){
  e.preventDefault();
  $('#btn-eliminarModal').val($(this).val());
  $('#modalEliminar').modal('show');
});

//SALIR DEL RELEVAMIENTO
$('#btn_salir_layout').click(function(){
  //Si está guardado deja cerrar el modal
  const estado = $(this).data('estado_cambios');
  if (estado == 'GUARDADOS') $('#modalLayoutTotal').modal('hide');
  else if (estado == 'SIN GUARDAR'){//Primera vez que toca salir, se le muestra el mensaje que tiene cambios sin guardar
    $('#mensaje_cambios_layout').show();
    $(this).data('estado_cambios','SIN GUARDAR 2');
  }
  else if (estado == 'SIN GUARDAR 2'){//Segunda vez que toca salir
    $('#modalLayoutTotal').modal('hide');
  }
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('.selectCasinos').on('change',function(){
  $.get('http://' + window.location.host +"/casinos/obtenerTurno/" + $(this).val(), function(data){
      $('#turno').val(data.turno);
  });
});

function enviarLayout(url,succ=function(x){console.log(x);},err=function(x){console.log(x);}){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  const maquinas = [];
  $('#inactivas_layout tbody tr').each(function(){
    const maquina = {
      id_sector :  $(this).find('.sector').val(),
      nro_isla  : $(this).find('.nro_isla').val(),
      nro_admin : $(this).find('.nro_admin').val(),
      id_maquina : $(this).find('.nro_admin').obtenerElementoSeleccionado(),
      co : $(this).find('.co').val(),
      pb : $(this).find('.pb ').is(':checked')  == true ? 1 :  0,
    };
    maquinas.push(maquina);
  });

  const islas = [];
  $('#activas_layout .isla').each(function(){
    const t = $(this);
    islas.push({
      id_isla: t.attr('data-id-isla'),
      maquinas_observadas: t.find('.inputIsla').val()
    });
  });

  $.ajax({
      type: 'POST',
      url: url,
      data: {
        id_fiscalizador_toma :  $('#fiscalizador_toma_layout').obtenerElementoSeleccionado(),
        id_layout_total:   $('#btn_finalizar_layout').val(),
        fecha_ejecucion: $('#fecha_ejecucion_layout_hidden').val(),
        maquinas: maquinas,
        observacion_fiscalizacion: $('#observaciones_fisca_layout').val(),
        //Si ya le mostre el mensaje de confirmacion y manda finalizar de vuelta, en el backend se ignoran algunos chequeos
        confirmacion: $('#mensaje_confirmar_layout').is(':visible'),
        islas: islas
      },
      dataType: 'json',
      success: succ,
      error: err
  });
}

$('#btn_guardartemp_layout').click(function(e){
  e.preventDefault();
  enviarLayout('http://' + window.location.host +'/layouts/guardarLayoutTotal',
    function(x){
      $('#btn-salir').data('estado_cambios','GUARDADOS');
      $('#mensajeExito h3').text('ÉXITO DE CARGA');
      $('#mensajeExito .cabeceraMensaje').addClass('modificar');
      $('#mensajeExito p').text("Se ha guardado correctamente el control de Layout Total.");
      $('#mensajeExito').show();
      $('#btn-buscar').trigger('click');
    },
    function(x){
      console.log(x);
      mostrarError('Hubo un problema al guardar.');
    }
  );
});

function mostrarError(mensaje = '') {
  $('#mensajeError').hide();
  setTimeout(function() {
      $('#mensajeError').find('.textoMensaje')
          .empty()
          .append('<h2>ERROR</h2>')
          .append(mensaje);
      $('#mensajeError').show();
  }, 500);
}

function verificarFormularioCarga(){
  let error = false;
  $('#modalLayoutTotal .isla input').each(function(){
    const t = $(this);
    const val = t.val();
    const intVal = parseInt(val);
    if((val != intVal) || (intVal < 0)){
      mostrarErrorValidacion(t,"Valor invalido",false);
      error = true;
    }
  });
  return error? "Islas incompletas o con valores invalidos" : null;
}

$('#btn_finalizar_layout').click(function(e){
  e.preventDefault();

  const mensaje = verificarFormularioCarga();
  if(mensaje != null){
    mostrarError(mensaje);
    return;
  }

  const success = function (resultados) {
    $('#mensajeExito h3').text('ÉXITO DE CARGA');
    $('#mensajeExito .cabeceraMensaje').addClass('modificar');
    $('#mensajeExito p').text("Se ha cargado correctamente el control de Layout Total.");
    $('#mensajeExito').show();
    $('#btn-buscar').trigger('click');
    $('#modalLayoutTotal').modal('hide');
  };

  const error = function (data) {
    const response = data.responseJSON;
    let error_no_aceptable = false;//true ocurrio un error que no necesite ser corregido
    let error_aceptable    = false;//true si necesito pedir confirmacion
    if(typeof response.id_fiscalizador_toma !== 'undefined'){
      mostrarErrorValidacion($('#fiscalizador_toma_layout'),response.id_fiscalizador_toma[0] ,true);
      error_no_aceptable = true;
    }

    if(typeof response.fecha_ejecucion !== 'undefined'){
      mostrarErrorValidacion($('#fecha_ejecucion_layout'),response.fecha_ejecucion[0] ,true);
      error_no_aceptable = true;
    }

    $('#controlLayout tr').each(function(i,elem){
      if(typeof response['maquinas.'+ i +'.id_sector'] !== 'undefined'){
        mostrarErrorValidacion($(this).find('.sector') ,response['maquinas.'+ i +'.id_sector'][0] ,false);
        error_no_aceptable = true;
      }
      if(typeof response['maquinas.'+ i +'.nro_isla'] !== 'undefined'){
        mostrarErrorValidacion($(this).find('.nro_isla') , response['maquinas.'+ i +'.nro_isla'][0],false);
        error_no_aceptable = true;
      }
      if(typeof response['maquinas.'+ i +'.nro_admin'] !== 'undefined'){
        mostrarErrorValidacion($(this).find('.nro_admin'), response['maquinas.'+ i +'.nro_admin'][0],false);
        error_no_aceptable = true;
      }
      if(typeof response['maquinas.'+ i +'.no_existe'] !== 'undefined'){
        mostrarErrorValidacion($(this).find('.nro_isla') , response['maquinas.'+ i +'.no_existe'][0],false);
        error_aceptable = true;
      }
    });

    //Pedir confirmacion si hay un error aceptable y ninguno no aceptable
    const confirmacion = error_aceptable && !error_no_aceptable;
    $('#mensaje_confirmar_layout').toggle(confirmacion);
  };

  enviarLayout('http://' + window.location.host +'/layouts/cargarLayoutTotal',success,error);
});


// Todo busqueda Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

    e.preventDefault();

    let size = 10;
    //Fix error cuando librería saca los selectores
    if(!isNaN($('#herramientasPaginacion').getPageSize())){
      size = $('#herramientasPaginacion').getPageSize();
    }

    page_size = (page_size == null || isNaN(page_size))? size : page_size;
    const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaLayouts .activa').attr('value'),orden: $('#tablaLayouts .activa').attr('estado')} ;
    if(sort_by == null){ // limpio las columnas
      $('#tablaLayouts th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

    $.ajax({
      type: 'POST',
      url: 'http://' + window.location.host +'/layouts/buscarLayoutsTotales',
      data: {
        fecha: $('#buscadorFecha').val(),
        casino: $('#buscadorCasino').val(),
        turno: $('#buscadorTurno').val(),
        estadoRelevamiento: $('#buscadorEstado').val(),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
      },
      dataType: 'json',
      success: function (resultados) {
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTabla tr').remove();
        for (let i = 0; i < resultados.data.length; i++){
          const fila = generarFilaTabla(resultados.data[i]);
          $('#cuerpoTabla').append(fila);
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function (data) {
        console.log('Error:', data);
      }
    });
});

$(document).on('click','#tablaLayouts thead tr th[value]',function(e){
  $('#tablaLayouts th').removeClass('activa');
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
  $('#tablaLayouts th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaLayouts .activa').attr('value');
  const orden = $('#tablaLayouts .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(layout_total){
  let fila = $('#filaEjemplo').clone();
  fila.attr('id',layout_total.id_layout_total);
  fila.find('.fecha').text(layout_total.fecha);
  fila.find('.casino').text(layout_total.casino);
  fila.find('.turno').text(layout_total.turno);
  fila.find('.acciones').find('button').val(layout_total.id_layout_total);
  fila.find('.estado').text(layout_total.estado);
  //Siempre muestro el de la planilla
  fila.find('.acciones button').hide();
  fila.find('.planilla,.imprimir,.eliminar').show();
  //Hack... la clase se llama faValidado y el estado Visado... todas las demas estan bien estructuradas
  if(layout_total.estado == 'Visado') fila.find('.icono_estado').addClass('faValidado');
  else{
    fila.find('.icono_estado').addClass(`fa${layout_total.estado}`);
  }

  const estado_a_acciones = {'Generado':['carga'],'Cargando':['carga'],'Finalizado':['ver','validar'],'Visado':['ver']};
  if(!(layout_total.estado in estado_a_acciones)) return;
  for(let idx=0;idx<estado_a_acciones[layout_total.estado].length;idx++){
    const accion = estado_a_acciones[layout_total.estado][idx];
    fila.find(`.${accion}`).show();
  }
  fila.css('display','');
  return fila;
}

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.imprimir',function(){
  window.open('layouts/generarPlanillaLayoutTotalesCargado/' + $(this).val(),'_blank');
});

$('#btn_agregar_inactiva_layout').click(function(){
  agregarNivel($('#modalLayoutTotal').data('sectores'),{},'carga');
});

//borrar un nivel de layout
$(document).on('click','.borrarNivelLayout',function(){
  $(this).parent().parent().remove();
});

$(document).on('input' , '#modalLayoutTotal input,#modalLayoutTotal textarea,#modalLayoutTotal select' ,function(){
  $('#btn_salir_layout').data('estado_cambios','SIN GUARDAR');
  $('#modalLayoutTotal .mensajeSalida').hide();
});

function agregarNivel(sectores,nivel,modo){
  const nivel_vacio = { id_nivel_layout: "", descripcion_sector: "", nro_isla: null, 
                        nro_admin: null, id_maquina: 0, co: null, pb: null };
  const n = Object.assign(nivel_vacio,nivel);
  const editable = modo == 'cargar';

  const fila = $('#filaEjemploInactivasLayout').clone().removeAttr('id');
  fila.attr('id_nivel_layout',n.id_nivel_layout);
  fila.find('select,input').attr('readonly',!editable).attr('disabled',!editable);
  
  fila.find('.nro_admin').val(n.nro_admin);
  fila.find('.co').val(n.co);
  fila.find('.pb').prop('checked',n.pb);

  if( modo == 'cargar' ){//agrego buscador y boton borrar (renglon)
    fila.find('.gestion_maquina').remove();
    fila.find('.nro_admin').generarDataList("http://" + window.location.host + "/maquinas/obtenerMTMEnCasino/" + sectores[0].id_casino  ,'maquinas','id_maquina','nro_admin',1,false);
    fila.find('.nro_admin').setearElementoSeleccionado(n.id_maquina,n.nro_admin);
  }
  else if(modo == 'validar' || modo == "ver"){
    fila.find('.borrarNivelLayout').remove();
    fila.find('.gestion_maquina').attr('href' , 'http://' + window.location.host + '/maquinas/' + nivel.id_maquina );
    fila.find('.gestion_maquina').popover({html:true});
  }

  const select = fila.find('.sector');
  let id_sector = null;
  for (let i = 0; i < sectores.length; i++) {
    select.append($('<option>').val(sectores[i].id_sector).text(sectores[i].descripcion));
    if(n.descripcion_sector == sectores[i].descripcion){
      id_sector = sectores[i].id_sector;
    }
  }
  fila.find('.nro_isla').val(n.nro_isla);
  select.val(id_sector);
  $('#inactivas_layout tbody').append(fila);
}

$(document).on('change','.NivelLayout .sector',function(e){
  e.preventDefault();
  const fila = $(this).closest('tr');
  const id_sector = $(this).val();
  if(id_sector == ""){
    fila.find('.nro_isla').borrarDataList();
  }
  else{
    const nro_isla = fila.find('.nro_isla').val();
    fila.find('.nro_isla').generarDataList("http://" + window.location.host + "/islas/buscarIslaPorSectorYNro/" + $(this).val()  ,'islas','id_isla','nro_isla',1,false);
    fila.find('.nro_isla').val(nro_isla);
  }
});

$('.modal').on('hidden.bs.modal', limpiarModales);

function limpiarModales(){
  const campos = $('#modalNuevoLayoutTotal,#modalLayoutSinSistema,#modalLayoutTotal').find('input,select,textarea');
  ocultarErrorValidacion(campos);
  campos.val('');
  
  $('#inactivas_layout tbody').empty();
  $('#activas_layout').empty();
  $('#diferencias_layout').empty();
  $('#fiscalizador_toma_layout').popover('hide');
  $('#fecha_ejecucion_layout').popover('hide');
  $('#mensaje_cambios_layout').hide();//Mensaje guardado
  $('#btn_salir_layout').data('estado_cambios','GUARDADOS');
  $('#mensaje_confirmar_layout').hide();//Mensaje confirmar carga */

  $('#frmLayoutTotal').trigger('reset');
  $('#frmLayoutSinSistema').trigger('reset');
  $('#iconoCarga').hide();
  $('#modalNuevoLayoutTotal').find('.modal-footer').children().show();
  $('#modalNuevoLayoutTotal').find('.modal-body').children().show();
}