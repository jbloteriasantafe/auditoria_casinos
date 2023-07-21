$(document).ready(function() {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $('.tituloSeccionPantalla').text('Cierres y Aperturas');
  $('[data-toggle="popover"]').popover();

  $('.dtpFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
  });

  $('#tabs a:first').click();
});

/*
########  ##     ##  ######   ######     ###    ########  
##     ## ##     ## ##    ## ##    ##   ## ##   ##     ## 
##     ## ##     ## ##       ##        ##   ##  ##     ## 
########  ##     ##  ######  ##       ##     ## ########  
##     ## ##     ##       ## ##       ######### ##   ##   
##     ## ##     ## ##    ## ##    ## ##     ## ##    ##  
########   #######   ######   ######  ##     ## ##     ## 
 */
 
$('.btn-buscar').on('click', function(e,pagina,page_size,columna,orden){
  e.preventDefault();
  const tab = $(this).closest('.tab_content');
  tab.find('.tablaResultados tbody tr').remove();
  
  //Fix error cuando librería saca los selectores
  let size = tab.find('.herramientasPaginacion').getPageSize();
  if(isNaN(size)){
    size = 10;
  }

  page_size = (page_size == null || isNaN(page_size))? size : page_size;
  const page_number = (pagina != null) ? pagina : tab.find('.herramientasPaginacion').getCurrentPage();
  
  let sort_by = (columna != null) ? {columna,orden} : {columna: tab.find('.tablaResultados .activa').attr('value'),orden: tab.find('.tablaResultados .activa').attr('estado')};
  if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
    sort_by =  {columna: tab.find('.tablaResultados thead tr th').first().attr('value'),orden: 'desc'} ;
  }
  
  const formData = {
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  };
  tab.find('.filtro-busqueda-collapse [name]').each(function(idx,o){
    formData[$(o).attr('name')]=$(o).val();
  });
  
  $.ajax({
    type: 'POST',
    url: $(this).attr('target'),
    data: formData,
    dataType: 'json',

    success: function (ret){      
      tab.find('.herramientasPaginacion').generarTitulo(page_number,page_size,ret.total,function(e,pageNumber,tam){
        return clickIndice(tab,e,pageNumber,tam);
      });
      tab.find('.tablaResultados tbody tr').remove();

      ret.data.forEach(function(obj){
        const fila = tab.find('.moldeFilaResultados').clone().removeClass('moldeFilaResultados');
        Object.keys(obj).forEach(function(k){
          fila.find('.'+k).text(obj[k]);
        });
        fila.find('button').val(obj.id).filter(function(idx,o){
          return !$(o).attr('data-estados').split(',').includes(obj.estado+'');
        }).remove();
        fila.find('.estado').empty().append(
          $(`#iconosEstados i[data-linkeado=${obj.linkeado}][data-estado=${obj.estado}]`).clone()
        );
        tab.find('.tablaResultados tbody').append(fila);
      });
      
      tab.find('.herramientasPaginacion').generarIndices(page_number,page_size,ret.total,function(e,pageNumber,tam){
        return clickIndice(tab,e,pageNumber,tam);
      });
    },
    error: function(data){
      console.log(data);
    },
  })
});

function clickIndice(tab,e,pageNumber,tam){
  if(e == null) return;
  
  e.preventDefault();
  tam = (tam != null) ? tam : tab.find('.herramientasPaginacion').getPageSize();
  const columna = tab.find('.tablaResultados .activa').attr('value');
  const orden = tab.find('.tablaResultados .activa').attr('estado');
  
  tab.find('.btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

$('.tablaResultados thead tr th').click(function(e){
  const icon = $(this).find('i');
  const not_sorted = icon.hasClass('fa-sort');
  const down_sorted = icon.hasClass('fa-sort-down');
  const tabla = $(this).closest('.tablaResultados');
  tabla.find('.activa').removeClass('activa');
  tabla.find('thead tr th i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  if(not_sorted){
    icon.removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else if(down_sorted){
    icon.removeClass().addClass('fa fa-sort-up').parent().addClass('activa').attr('estado','asc');
  }
  $(this).closest('.tab_content').find('.btn-buscar').click();
});

/*
 ######   ######## ##    ## ######## ########     ###    ########  
##    ##  ##       ###   ## ##       ##     ##   ## ##   ##     ## 
##        ##       ####  ## ##       ##     ##  ##   ##  ##     ## 
##   #### ######   ## ## ## ######   ########  ##     ## ########  
##    ##  ##       ##  #### ##       ##   ##   ######### ##   ##   
##    ##  ##       ##   ### ##       ##    ##  ##     ## ##    ##  
 ######   ######## ##    ## ######## ##     ## ##     ## ##     ## 
*/

$('.btn-grande[modal="#modal-GenerarPlantilla"]').click(function(e){
  e.preventDefault();
  const M = $($(this).attr('modal'));
  M.modal('show');
  $.ajax({
    type: "POST",
    url: 'aperturas/generarRelevamiento',
    dataType: 'json',
    success: function (data) {
      M.modal('hide');
      var iframe;
      iframe = document.getElementById("download-container");
      if (iframe === null){
        iframe = document.createElement('iframe');
        iframe.id = "download-container";
        iframe.style.visibility = 'hidden';
        document.body.appendChild(iframe);
      }
      iframe.src = 'aperturas/descargarZip/'+data.nombre_zip;
    },
    error: function (data) {
      M.modal('hide');
      setTimeout(function(){
        $('#modal-Reintente').modal('show');
      },500);
    }
  });
});

/*
   ###    ########     ###    ########  ######## ########  #### ########   #######  
  ## ##   ##     ##   ## ##   ##     ## ##       ##     ##  ##  ##     ## ##     ## 
 ##   ##  ##     ##  ##   ##  ##     ## ##       ##     ##  ##  ##     ## ##     ## 
##     ## ########  ##     ## ########  ######   ##     ##  ##  ##     ## ##     ## 
######### ##        ######### ##        ##       ##     ##  ##  ##     ## ##     ## 
##     ## ##        ##     ## ##        ##       ##     ##  ##  ##     ## ##     ## 
##     ## ##        ##     ## ##        ######## ########  #### ########   #######  
*/

function buscarAperturasAPedido(){
  $('#tablaAaP tbody').empty();
  $.ajax({
    url: '/aperturas/buscarAperturasAPedido',
    type: 'GET',
    dataType: 'json',
    success: function(aaps){
      aaps.forEach(function(aap){
        const fila = $('#moldeAaP').clone().removeAttr('id');
        Object.keys(aap).forEach(function(k){
          fila.find('.'+k).text(aap[k]);
        });
        fila.find('button').val(aap.id_apertura_a_pedido);
        $('#tablaAaP tbody').append(fila);
      });
    },
    error: function(data){
      mensajeError();
      console.log(data.responseJSON);
    }
  });
}

$('.btn-grande[modal="#modal-AperturaAPedido"]').click(function(e){
  e.preventDefault();
  $('#juegoAaP option').removeAttr('selected').eq(0).attr('selected','selected').change();
  $(this).find('.dtpFecha').each(function(){
    $(this).data('datetimepicker').reset();
  });
  ocultarErrorValidacion($(this).find('input,select'));
  buscarAperturasAPedido();
  $($(this).attr('modal')).modal('show');
});

$('#juegoAaP').change(function(e){
  e.preventDefault();
  $('#mesaAaP').generarDataList("/aperturas/obtenerMesasPorJuego/" + $('#juegoAaP').val(), 'mesas', 'id_mesa_de_panio', 'nro_mesa', 1);
})

$('#agregarAaP').click(function(e){
  e.preventDefault();
  $.ajax({
    url: '/aperturas/agregarAperturaAPedido',
    type: 'POST',
    dataType: 'json',
    data: {
      id_mesa_de_panio : $('#mesaAaP').obtenerElementoSeleccionado(),
      fecha_inicio : $('#fechaInicioAaP').val(),
      fecha_fin : $('#fechaFinAaP').val(),
    },
    success: function(data){
      $('#juegoAaP').change();//Limpia el input de nro de mesa
      buscarAperturasAPedido();
    },
    error: function(data){
      const response = data.responseJSON;
      console.log(response);
      if(response.id_mesa_de_panio){
        mostrarErrorValidacion($('#mesaAaP'),'Valor incorrecto',true);
      }
      if(response.fecha_inicio){
        mostrarErrorValidacion($('#fechaInicioAaP'),'Valor incorrecto',true);
      }
      if(response.fecha_fin){
        mostrarErrorValidacion($('#fechaFinAaP'),'Valor incorrecto',true);
      }
    }
  })
})

$(document).on('click','#modal-AperturaAPedido .eliminarAaP',function(e){
  e.preventDefault();
  const fila = $(this).closest('tr');
  $.ajax({
    url: '/aperturas/borrarAperturaAPedido/'+$(this).val(),
    type: 'DELETE',
    dataType: 'json',
    success: function(){
      fila.remove();
    },
    error: function(data){
      mensajeError();
      console.log(data.responseJSON);
    }
  });
});

/*
##     ## ######## ########  
##     ## ##       ##     ## 
##     ## ##       ##     ## 
##     ## ######   ########  
 ##   ##  ##       ##   ##   
  ## ##   ##       ##    ##  
   ###    ######## ##     ## 
*/

$(document).on('click', '#pant_aperturas .ver', function(e) {
  mostrarCierreApertura('aperturas/getApertura/'+$(this).val());
});
$(document).on('click', '#pant_cierres .ver', function(e) {
  mostrarCierreApertura('cierres/getCierre/'+$(this).val());
});

function mostrarCierreApertura(url,sucess = function(data){}){
  GET(url,{},function(data){
    ['Cierre','Apertura'].forEach(function(tipo){
      const O = data[tipo.toLowerCase()] ?? null;
      const $O = $(`#modalVerCierreApertura .datos${tipo}`);
      if(O === null) return $O.hide();
      $O.show();
      $O.find('.titulo_datos').text(O? tipo.toUpperCase() : `-SIN ${tipo.toUpperCase()}-`);
      $O.find('.datos').toggle(!!O);
      $O.find('.nro_mesa').text(`${O?.mesa?.nombre} - ${O?.moneda?.descripcion}`);
      $O.find('.nombre_juego').text(O?.juego?.nombre_juego ?? '-');
      $O.find('.fecha').text(O?.datos?.fecha ?? ' - ');
      $O.find('.fiscalizador').text(O?.fiscalizador?.nombre ?? ' - ');
      $O.find('.hora_fin').text(O?.datos?.hora_fin ?? ' - ');
      $O.find('.hora_inicio').text(O?.datos?.hora_inicio ?? ' - ');
      $O.find('.total_pesos_fichas_c').val(O?.datos?.total_pesos_fichas_c ?? 0);
      $O.find('.total_anticipos_c').val(O?.datos?.total_anticipos_c ?? ' - ');
      $O.find('.cargador').text(O?.cargador?.nombre ?? ' - ');
      $O.find('.hora').text(O?.datos?.hora ?? ' - ');
      $O.find('.total_pesos_fichas_a').val(O?.datos?.total_pesos_fichas_a ?? 0);
      $O.find('.tablaFichas tbody').empty();
      (O?.detalles ?? []).forEach(function(ficha){
        const fila = $O.find('.moldeFila').clone().removeClass('moldeFila');
        fila.find('.valor_ficha').text(ficha.valor_ficha ?? 0);
        fila.find('.cantidad_ficha').text(ficha.cantidad_ficha ?? 0);
        fila.find('.monto_ficha').text(ficha.monto_ficha ?? 0);
        $O.find('.tablaFichas tbody').append(fila);
      });
    });
    $('#modalVerCierreApertura').modal('show');
  });
}

/*
########  ########  ######  ##     ## #### ##    ##  ######  ##     ## ##          ###    ########  
##     ## ##       ##    ## ##     ##  ##  ###   ## ##    ## ##     ## ##         ## ##   ##     ## 
##     ## ##       ##       ##     ##  ##  ####  ## ##       ##     ## ##        ##   ##  ##     ## 
##     ## ######    ######  ##     ##  ##  ## ## ## ##       ##     ## ##       ##     ## ########  
##     ## ##             ##  ##   ##   ##  ##  #### ##       ##     ## ##       ######### ##   ##   
##     ## ##       ##    ##   ## ##    ##  ##   ### ##    ## ##     ## ##       ##     ## ##    ##  
########  ########  ######     ###    #### ##    ##  ######   #######  ######## ##     ## ##     ## 
*/

$(document).on('click', '.desvincular', function(e){
  e.preventDefault();
  $('#modalDesvinculacion').modal('show');
  $('#btn-desvincular').val($(this).val());
});

$(document).on('click', '#btn-desvincular', function(e){
  GET('aperturas/desvincularApertura/' + $(this).val(),{}, function(data){
    $('#modalDesvinculacion').modal('hide');
    if(data==1){
      mensajeExito('Se ha desvinculado el cierre de esta Apertura.');
      $('#pant_aperturas .btn-buscar').click();
    }
    else{
      mensajeError('No es posible realizar esta acción, ya ha cerrado el periodo de producción correspondiente.');
    }
  });
})

/*
######## ##       #### ##     ## #### ##    ##    ###    ########  
##       ##        ##  ###   ###  ##  ###   ##   ## ##   ##     ## 
##       ##        ##  #### ####  ##  ####  ##  ##   ##  ##     ## 
######   ##        ##  ## ### ##  ##  ## ## ## ##     ## ########  
##       ##        ##  ##     ##  ##  ##  #### ######### ##   ##   
##       ##        ##  ##     ##  ##  ##   ### ##     ## ##    ##  
######## ######## #### ##     ## #### ##    ## ##     ## ##     ## 
*/

$(document).on('click','#pant_aperturas .eliminar',function(e){
  $('#modalAlertaBaja .btn-eliminar').attr('data-url','aperturas/bajaApertura');
  $('#modalAlertaBaja .btn-eliminar').val($(this).val());
  $('#modalAlertaBaja .mensaje').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTA APERTURA?')
  $('#modalAlertaBaja').modal('show');
});

$(document).on('click','#pant_cierres .eliminar',function(e){
  $('#modalAlertaBaja .btn-eliminar').attr('data-url','cierres/bajaCierre');
  $('#modalAlertaBaja .btn-eliminar').val($(this).val());
  $('#modalAlertaBaja .mensaje').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTE CIERRE?')
  $('#modalAlertaBaja').modal('show');
});

$('#modalAlertaBaja .btn-eliminar').click(function(){
  const url = $(this).attr('data-url')+'/'+$(this).val();
  GET(url,{},function(data){
    mensajeExito('Eliminado con éxito');
    $('.tab_content:visible .btn-buscar').click();
    $('#modalAlertaBaja').modal('hide');
  });
});

/*
##     ##            ###    ########  
##     ##           ## ##   ##     ## 
##     ##          ##   ##  ##     ## 
##     ## ####### ##     ## ########  
 ##   ##          ######### ##        
  ## ##           ##     ## ##        
   ###            ##     ## ##              
*/

$(document).on('click', '#pant_aperturas .validar', function(e) {
  e.preventDefault();
  const M = $('#modalValidarApertura');
  const $M = M.find.bind(M);
  const id_apertura = $(this).val();
  
  $M('.form-control').val('');
  $M('.datosA,.datosC').find('h6 span').text('');
  $M('.fechaCierreVal option[value!=""]').remove();
  $M('.btn-validar,.btn-validar-diferencia,.datosC,.mensajeErrorValApertura').hide();
  $M('.tablaFichas tbody tr').remove();
  $M('.btn-validar,.btn-validar-diferencia').val(id_apertura);

  GET('aperturas/obtenerApValidar/' + id_apertura,{},function(A){
    $M('.nro_mesa').text(A?.mesa?.nro_mesa);
    $M('.fecha_apertura').text(A?.apertura?.fecha);
    $M('.juego').text(A?.juego?.nombre_juego);
    $M('.casino').text(A?.casino?.nombre);
    $M('.hora').text(A?.apertura?.hora_format);
    $M('.fiscalizador').text(A?.fiscalizador?.nombre);
    $M('.cargador').text(A?.cargador?.nombre);
    $M('.tipo_mesa').text(A?.tipo_mesa?.descripcion);
    $M('.moneda').text(A?.moneda?.descripcion).val(A?.moneda?.id_moneda);
    $M('.total_pesos_fichas_a').val(A?.apertura?.total_pesos_fichas_a);

    (A?.fechas_cierres ?? []).forEach(function(c) {
      $M('.fechaCierreVal').append(      
        $('<option>').val(c.id_cierre_mesa)
        .text(`${c.fecha} -- ${c.hora_inicio_format} a ${c.hora_fin_format} -- ${c.siglas}`)
      );
    });
    
    M.modal('show');
  });
});


$(document).on('change', '#modalValidarApertura .fechaCierreVal', function(e) {
  e.preventDefault();
  
  const $M = $(this).closest('.modal').find.bind($(this).closest('.modal'));
  $M('.btn-validar,.btn-validar-diferencia,.datosC').hide();
  $M('.tablaFichas tbody tr').remove();
  $M('.total_anticipos_c,.total_pesos_fichas_c').val('-');
  
  if($(this).val().length == 0) return;
  
  const id_apertura = $M('.btn-validar').val();
  const id_cierre   = $M('.fechaCierreVal').val();
  
  GET(`aperturas/compararCierre/${id_apertura}/${id_cierre}`,{},function(data){
    $M('.hora_inicio').text(data?.cierre?.hora_inicio_format ?? '-');
    $M('.hora_fin').text(data?.cierre?.hora_fin_format ?? '-');
    $M('.fecha_cierre').text(data?.cierre?.fecha ?? '-');
    $M('.total_anticipos_c').val(data?.cierre?.total_anticipos_c ?? '-');
    $M('.total_pesos_fichas_c').val(data?.cierre?.total_pesos_fichas_c ?? '-');    
    
    let diferencias = 0;
    (data.fichas ?? []).forEach(function(f){
      const c = (data.detalles_cierre   ?? []).find(c => c.id_ficha == f.id_ficha)?.cantidad_ficha ?? 0;
      const a = (data.detalles_apertura ?? []).find(a => a.id_ficha == f.id_ficha)?.cantidad_ficha ?? 0;
      
      const fila = $M('.moldeFicha').clone().removeClass('moldeFicha').show();
      fila.attr('data-id-ficha',f.id_ficha);
      fila.find('.valor_ficha').text(f.valor_ficha ?? '-');
      fila.find('.cierre_cantidad_ficha').text(c);
      fila.find('.apertura_cantidad_ficha').text(a);
      
      const icono = [['fa-check','#66BB6A'],['fa-times','#D32F2F']];
      const hay_diferencia = (a != c)+0;
      const i = icono[hay_diferencia];
      fila.find('.diferencia').append(
        $('<i>').addClass(`fa fa-fw ${i[0]}`).css('color',i[1])
      );
      
      diferencias += hay_diferencia;
      $M('.tablaFichas tbody').append(fila);
    });
    $M('.btn-validar').toggle(diferencias == 0);
    $M('.btn-validar-diferencia').toggle(diferencias != 0);
    $M('.datosC').show();
  })
});

function validar(id_apertura,diferencia){
  $.ajax({
    type: 'POST',
    url: 'aperturas/validarApertura',
    data: {
      id_cierre: $('#modalValidarApertura .fechaCierreVal').val(),
      id_apertura: id_apertura,
      diferencia: diferencia,
      observaciones: $('#modalValidarApertura .obsValidacion').val(),
    },
    dataType: 'json',
    success: function (data){
      $('#modalValidarApertura').modal('hide');
      mensajeExito('Apertura Validada correctamente.');
      $('#pant_aperturas .btn-buscar').click();
    },
    error: function(data){
     const response = data.responseJSON;
     if(typeof response.id_cierre !== 'undefined'){
       $('#modalValidarApertura .mensajeErrorValApertura').show();
     }
    },
  });
}

$(document).on('click', '#modalValidarApertura .btn-validar', function(e) {
  e.preventDefault();
  validar($(this).val(),0);
});

$(document).on('click', '#modalValidarApertura .btn-validar-diferencia', function(e) {
  e.preventDefault();
  validar($(this).val(),1);
});

/*
 ######     ###               ###    ########          ##  ######     ###    ##     ##          ######  #### 
##    ##   ## ##             ## ##   ##     ##        ##  ##    ##   ## ##   ##     ##         ##    ##  ##  
##        ##   ##           ##   ##  ##     ##       ##   ##        ##   ##  ##     ##         ##        ##  
##       ##     ## ####### ##     ## ########       ##    ##       ##     ## ##     ## ####### ##        ##  
##       #########         ######### ##            ##     ##       #########  ##   ##          ##        ##  
##    ## ##     ##         ##     ## ##           ##      ##    ## ##     ##   ## ##           ##    ##  ##  
 ######  ##     ##         ##     ## ##          ##        ######  ##     ##    ###             ######  #### 
*/

const TipoModal = {
  cierre: 'cierres',apertura: 'aperturas'
};
const ModoModal = {
  cargar: '#6dc7be',modificar: '#FFB74D',validar: '#69F0AE'
};

$('.btn-grande[modal="#modal-CargarApertura"]').click(function(e){
  e.preventDefault();
  modalCargarCierreApertura( 
    'CARGAR APERTURAS',TipoModal.apertura,ModoModal.cargar,
    {}
  );
});

$(document).on('click', '#pant_aperturas .modificar', function(e) {
  GET('aperturas/getApertura/'+$(this).val(),{},function(data){
    modalCargarCierreApertura(
      'MODIFICAR APERTURA',TipoModal.apertura,ModoModal.modificar,
      data?.apertura ?? {}
    );
  });
});

$('.btn-grande[modal="#modal-CargarCierre"]').click(function(e){
  e.preventDefault();
  modalCargarCierreApertura(
    'CARGAR CIERRES',TipoModal.cierre,ModoModal.cargar,
    {}
  );
});

$(document).on('click', '#pant_cierres .modificar', function(e) {
  GET('cierres/getCierre/'+$(this).val(),{},function(data){
    data = data ?? {};
    data.cierre = data?.cierre ?? {};
    data.cierre.cargador = data?.cierre?.fiscalizador;
    modalCargarCierreApertura(
      'MODIFICAR CIERRE',TipoModal.cierre,ModoModal.modificar,
      data.cierre
    );
  });
});

$(document).on('click', '#pant_cierres .validar', function(e) {
  GET('cierres/getCierre/'+$(this).val(),{},function(data){
    data = data ?? {};
    data.cierre = data?.cierre ?? {};
    data.cierre.cargador = data?.cierre?.fiscalizador;
    modalCargarCierreApertura(
      'VALIDAR CIERRE',TipoModal.cierre,ModoModal.validar,
      data.cierre
    );
  });
});

const _MCCA = '#modal-CargarCierreApertura';
const  MCCA = $(_MCCA);
const $MCCA = MCCA.find.bind(MCCA);

function modalCargarCierreApertura(titulo,tipo_modal,modo_modal,O){
  {//Limpiar
    $MCCA('.dtpFecha').data('datetimepicker').reset();
    $MCCA('.form-control').val('').change();
    ocultarErrorValidacion($MCCA('.form-control'));
    ocultarErrorValidacion($MCCA('[name="observacion"]'));
    $MCCA('[name="observacion"]').val('').change();
    $MCCA('.tablaMesas tbody tr,.tablaFichas tbody tr').remove();
    $MCCA('.inputMesas,.datosCierreApertura').hide();
    const quienSoy = $('#quienSoy').clone().show().removeAttr('id');
    $MCCA('[name="id_cargador"]').replaceWith(quienSoy);
    $MCCA('.moldeFila .cargar').removeClass('cargado');
    MCCA.data('cargados',0);
    MCCA.data('salir_al_completar',modo_modal == ModoModal.modificar || modo_modal == ModoModal.validar);
  }
  {//Armar modal
    $MCCA('.tipo').text(titulo);
    $MCCA(".modal-header").css('background-color',modo_modal);
    MCCA.data('path',tipo_modal);
    $MCCA("[cierres]").toggle(tipo_modal == TipoModal.cierre);
    $MCCA("[aperturas]").toggle(tipo_modal == TipoModal.apertura);
    $MCCA("[cargar]").toggle(modo_modal == ModoModal.cargar);
    $MCCA("[cargar_modificar]").toggle(modo_modal == ModoModal.cargar || modo_modal == ModoModal.modificar);
    $MCCA("[validar]").toggle(modo_modal == ModoModal.validar);
    $MCCA("[name='id_casino'],[name='fecha']").attr('disabled',modo_modal != ModoModal.cargar);
    $MCCA('.datosCierreApertura .form-control').not('[readonly]').attr('disabled',modo_modal == ModoModal.validar);
    if(modo_modal == ModoModal.validar)
      $MCCA('.moldeFila .cargar').addClass('cargado');
  }
  
  if(O?.mesa){//Setear mesa si vino una
    $MCCA('.dtpFecha').data('datetimepicker').setDate(
      new Date(O.datos.fecha+'T00:00')
    );
    $MCCA('[name="fecha"]').change();
    $MCCA('[name="id_casino"]').val(O?.mesa?.id_casino ?? '').change();
        
    const fila = $MCCA('.moldeFila').clone().removeClass('moldeFila');
    fila.find('.nro_mesa').text(O?.mesa?.nro_mesa ?? '#ERROR#');
    fila.find('button').val(O?.mesa?.id_mesa_de_panio);
    fila.find('.borrar').remove();
    
    const fichas = {};
    fichas[O?.mesa?.id_moneda ?? O?.moneda?.id_moneda ?? ''] = O?.detalles ?? [];
    const valores = {
      id_apertura_mesa: O?.datos?.id_apertura_mesa,
      id_cierre_mesa: O?.datos?.id_cierre_mesa,
      id_mesa_de_panio: O?.mesa?.id_mesa_de_panio ?? 'ERROR',
      id_moneda: Object.keys(fichas)[0],
      fichas: fichas,
      hora: O?.datos?.hora? hhmm(O.datos.hora) : '',
      hora_inicio: O?.datos?.hora_inicio? hhmm(O.datos.hora_inicio) : '',
      hora_fin: O?.datos?.hora_fin? hhmm(O.datos.hora_fin) : '',
      total_pesos_fichas_a: O?.datos?.total_pesos_fichas_a,
      total_pesos_fichas_c: O?.datos?.total_pesos_fichas_c,
      total_anticipos_c: O?.datos?.total_anticipos_c,
      id_cargador: O?.cargador?.id_usuario,
      nombre_cargador: O?.cargador?.nombre,
      id_fiscalizador: O?.fiscalizador?.id_usuario,
      nombre_fiscalizador: O?.fiscalizador?.nombre
    };
    
    fila.data('valores',valores);
    $MCCA('.tablaMesas tbody').append(fila);
    
    setTimeout(function(){
      fila.find('.cargar').click();
    },300);
  }

  MCCA.modal('show');
}

$MCCA('[name="fecha"],[name="id_casino"]').change(function(e){
  const id_casino = $MCCA('[name="id_casino"]').val();
  const fecha     = $MCCA('[name="fecha"]').val();
  $MCCA('.inputMesas').toggle(id_casino.length != 0 && fecha.length != 0);
});

$MCCA('[name="id_casino"]').change(function(e){
  const id_casino = $(this).val();
  const PATH = MCCA.data('path');
  $MCCA('.tablaMesas tbody').empty()
  $MCCA('.mesa').generarDataList(
    `${PATH}/obtenerMesas/${id_casino}`,
    'mesas' ,'id_mesa_de_panio','nro_mesa',1
  );
  $MCCA('[name="id_fiscalizador"]').generarDataList(
    `${PATH}/buscarFiscalizadores/${id_casino}`,
    'usuarios' ,'id_usuario','nombre',1
  );
});

$MCCA('.agregarMesa').click(function(e) {
  const id_mesa_de_panio = $MCCA('.mesa').attr('data-elemento-seleccionado');
  $MCCA('.mesa').setearElementoSeleccionado(null,"");
  
  const ya_existe = $MCCA('.tablaMesas').find('button').filter(function(idx,o){
    return $(o).val() == id_mesa_de_panio;
  }).length > 0;
  if(ya_existe) return;
  
  const PATH = MCCA.data('path');
  GET(`${PATH}/detalleMesa/${id_mesa_de_panio}`,{}, function(data) {
    let id_moneda = '';//Moneda seleccionada
    const fichas = {//Fichas por moneda
      '': [], 
      ...(data.fichas ?? {})
    };
    
    if(Object.keys(fichas).length == 2){//Si tiene una sola moneda dejo esa sola
      delete fichas[''];
      id_moneda = Object.keys(fichas)[0];
    }
    
    const fila = $MCCA('.moldeFila').clone().removeClass('moldeFila');
    fila.find('.nro_mesa').text(data.mesa.nro_mesa);
    fila.data('valores',{
      id_mesa_de_panio: id_mesa_de_panio,
      id_moneda: id_moneda,
      fichas: fichas,
      hora: '',
      hora_inicio: '',
      hora_fin: '',
      total_pesos_fichas_a: 0,
      total_pesos_fichas_c: 0,
      total_anticipos_c: 0
    });
    fila.find('button').val(data.mesa.id_mesa_de_panio);
    $MCCA('.tablaMesas tbody').append(fila);
  });
});

$(document).on('click', `${_MCCA} .borrar`, function(e){
  const fila = $(this).closest('tr');
  const prev = fila.prev();
  const next = fila.next();
  fila.remove();
  if(!fila.hasClass('mesa_seleccionada')) return;
  
  (next.length == 0? prev : next).find('.cargar').click();
  $MCCA('.datosCierreApertura').toggle(next.length+prev.length);
});

$(document).on('click', `${_MCCA} .cargar`, function(e){
  e.preventDefault();
  
  const mesa = $(this).closest('tr');
  
  $MCCA('.tablaMesas tbody tr').removeClass('mesa_seleccionada');
  mesa.addClass('mesa_seleccionada');

  const valores = mesa.data('valores');
  $MCCA('[name="id_moneda"] option').prop('disabled',true);
  Object.keys(valores.fichas).forEach(function(id_moneda){
    $MCCA(`[name="id_moneda"] option[value="${id_moneda}"]`).prop('disabled',false);
  });
  
  $MCCA('[name="id_moneda"]').val(valores.id_moneda);
  $MCCA('[name="total_pesos_fichas_a"]').val(valores.total_pesos_fichas_a);
  $MCCA('[name="total_pesos_fichas_c"]').val(valores.total_pesos_fichas_c);
  $MCCA('[name="total_anticipos_c"]').val(valores.total_anticipos_c);
  $MCCA('[name="hora"]').val(valores.hora);
  $MCCA('[name="hora_inicio"]').val(valores.hora_inicio);
  $MCCA('[name="hora_fin"]').val(valores.hora_fin);
  
  if(valores.id_cargador !== null){
    $MCCA('[name="id_cargador"]').attr('data-elemento-seleccionado',valores.id_cargador)
    .val(valores.nombre_cargador ?? '')
    .attr('value',valores.nombre_cargador ?? '');
  }
  
  $MCCA('[name="id_fiscalizador"]').setearElementoSeleccionado(
    valores.id_fiscalizador ?? null,
    valores.nombre_fiscalizador ?? ''
  );
  
  const cargado = mesa.find('.cargado:visible').length > 0;
  $MCCA('.btn-guardar').toggle(!cargado);
  $MCCA('.datosCierreApertura .form-control').not('[readonly]').attr('disabled',cargado);
  $MCCA('.datosCierreApertura').show();
  $MCCA('.datosCierreApertura [name]').change();
});

$MCCA('[name="id_moneda"]').change(function(e){
  e.stopPropagation();
  const mesa = $MCCA('.mesa_seleccionada');
  const valores = mesa.data('valores') ?? {};
  valores.id_moneda = $(this).val();
  mesa.data('valores',valores);
    
  const tabla = $MCCA('.tablaFichas tbody').empty();
  const moldeFila = $MCCA('.moldeFichas').clone().removeClass('moldeFichas');
  (valores?.fichas?.[valores.id_moneda] ?? []).forEach(function(f){
    const fila = moldeFila.clone();
    fila.attr('data-id_ficha',f.id_ficha);
    const valor_ficha = parseFloat(f.valor_ficha);
    const monto_ficha = parseFloat(f.monto_ficha);
    const cantidad_ficha = parseFloat(f.cantidad_ficha);
    fila.find('.valor_ficha').val(isNaN(valor_ficha)? '' : valor_ficha);
    fila.find('.monto_ficha').val(isNaN(monto_ficha)? '' : monto_ficha);
    fila.find('.cantidad_ficha').val(isNaN(cantidad_ficha)? '' : cantidad_ficha);
    tabla.append(fila);
  });
  tabla.find('tr').eq(0).find('.valor_ficha').change();//Recalcular el total
});

$MCCA('.datosCierreApertura [name]').change(function(e){//actualizar los datos de la mesa
  const mesa = $MCCA('.mesa_seleccionada');
  const valores = mesa.data('valores') ?? {};
  valores[$(this).attr('name')] = $(this).val();
  mesa.data('valores',valores);
});

$MCCA('[name]').change(function(e){//setear value asi es mas facil de obtener todo a la hora de enviarlo
  $(this).attr('value',$(this).val());
});

function cambio_ficha(e){
  const mesa = $MCCA('.mesa_seleccionada');
  const valores = mesa.data('valores') ?? {};
  const clearNaN = x => (isNaN(x)? 0 : x);
  {//El juego monto-cantidad, cambio el que no edito el usuario
    const tgt = $(e.target);
    const fila = tgt.closest('tr');
    const valor_ficha = parseFloat(fila.find('.valor_ficha').val());
    if(tgt.hasClass('monto_ficha')){
      const monto_ficha = clearNaN(parseFloat(fila.find('.monto_ficha').val()));
      fila.find('.monto_ficha').val(monto_ficha);
      fila.find('.cantidad_ficha').val(monto_ficha/valor_ficha);
    }
    else if(tgt.hasClass('cantidad_ficha')){
      const cantidad_ficha = clearNaN(parseFloat(fila.find('.cantidad_ficha').val()));
      fila.find('.cantidad_ficha').val(cantidad_ficha);
      fila.find('.monto_ficha').val(cantidad_ficha*valor_ficha);
    }
  }
  
  let total = 0;
  const fichas = $MCCA('.tablaFichas tbody tr').map(function(idx,o){
    const monto_ficha = $(this).find('.monto_ficha').val();
    total += clearNaN(parseFloat(monto_ficha));
    return {
      valor_ficha: $(this).find('.valor_ficha').val(),
      cantidad_ficha: $(this).find('.cantidad_ficha').val(),
      monto_ficha: monto_ficha
    };
  }).toArray();
  
  valores.fichas = valores.fichas ?? {};
  valores.fichas[valores.id_moneda] = fichas;
  valores.total_pesos_fichas_c = total;
  valores.total_pesos_fichas_a = total;
  
  $MCCA('[name="total_pesos_fichas_c"],[name="total_pesos_fichas_a"]')
  .val(total).attr('value',total);
}

$(document).on(
  'change',
  `${_MCCA} .valor_ficha,${_MCCA} .cantidad_ficha,${_MCCA} .monto_ficha`,
  cambio_ficha
);

function obtenerDatosModalCargarCierreApertura(){
  const data = {};
  
  $MCCA('[name]').each(function(idx,o){
    const attr = $(o).attr('formdata-attr') ?? 'value';
    data[$(o).attr('name')] = $(o).attr(attr);
  });
  
  data.fichas = $MCCA('.tablaFichas tbody tr').map(function(idx,f){
    return {
      id_ficha: $(f).attr('data-id_ficha'),
      valor_ficha: $(f).find('.valor_ficha').val(),
      cantidad_ficha: $(f).find('.cantidad_ficha').val(),
      monto_ficha: $(f).find('.monto_ficha').val()
    };
  }).toArray();
  
  const mesa = $MCCA('.mesa_seleccionada');
  data.id_mesa_de_panio = mesa.data('valores').id_mesa_de_panio;
  data.id_cierre_mesa   = mesa.data('valores').id_cierre_mesa;
  data.id_apertura_mesa = mesa.data('valores').id_apertura_mesa;
  
  return data;
}

$MCCA('.btn-guardar').click(function(e){
  const formData = obtenerDatosModalCargarCierreApertura();
  POST(`${MCCA.data('path')}/guardar`,formData,
    function(data){
      const mesa = $MCCA('.mesa_seleccionada');
      mesa.find('.cargar').addClass('cargado');
      mesa.find('.borrar').remove();
      mesa.find('.cargar').click();
      MCCA.data('cargados',MCCA.data('cargados')+1);
      $('.tab_content:visible .btn-buscar').click();
      if(MCCA.data('salir_al_completar'))
        $MCCA('.btn-salir').click();
    },
    function(response){
      console.log(response);
      const json = response.responseJSON ?? {};
      Object.keys(json).forEach(function(k){
        mostrarErrorValidacion($MCCA(`[name="${k}"]`),json[k].join(', '),true);
      });
      $MCCA('.tablaFichas tbody tr').each(function(idx,o){
        const err_cantidad = json[`fichas.${idx}.cantidad_ficha`] ?? null;
        const err_monto    = json[`fichas.${idx}.monto_ficha`] ?? null;
        if(err_cantidad)
          mostrarErrorValidacion($(o).find('.cantidad_ficha'),err_cantidad.join(', '),false);
        if(err_monto)
          mostrarErrorValidacion($(o).find('.monto_ficha'),err_monto.join(', '),false);
      });
      mensajeError();
    }
  );
});

$MCCA('.btn-validar').click(function(e){
  const formData = obtenerDatosModalCargarCierreApertura();
  POST('cierres/validar',formData,
    function(data){
      mensajeExito('Cierre validado');
      MCCA.data('cargados',MCCA.data('cargados')+1);
      $('.tab_content:visible .btn-buscar').click();
      if(MCCA.data('salir_al_completar'))
        $MCCA('.btn-salir').click();
    },
    function(response){
      console.log(response);
      const json = response.responseJSON ?? {};
      Object.keys(json).forEach(function(k){
        mostrarErrorValidacion($MCCA(`[name="${k}"]`),json[k].join(', '),true);
      });
      mensajeError();
    }
  );
});

$MCCA('.btn-salir').on('click', function(e){
  e.preventDefault();
  MCCA.modal('hide');
  if(MCCA.data('cargados')){
    mensajeExito();
  }
});

/*
   ###    ##     ## ##     ## 
  ## ##   ##     ##  ##   ##  
 ##   ##  ##     ##   ## ##   
##     ## ##     ##    ###    
######### ##     ##   ## ##   
##     ## ##     ##  ##   ##  
##     ##  #######  ##     ## 
*/

function _mensaje(modal,mensaje){
  modal.hide();
  setTimeout(function(){
    modal.find('p').text(mensaje);
    modal.show();
  },100);
}
function mensajeExito(mensaje=''){
  _mensaje($('#mensajeExito'),mensaje);
}
function mensajeError(mensaje=''){
  _mensaje($('#mensajeError'),mensaje);
}

function _aux_ajax(type,url,params = {},success = function(data){},error = function(response){console.log(response);}){
  $.ajax({
    type: type,
    url: url,
    data: params,
    success: success,
    error: error
  });
}
function GET(url,params = {},success = function(data){},error = function(response){console.log(response);}){
  _aux_ajax('GET',url,params,success,error);
}
function POST(url,params = {},success = function(data){},error = function(response){console.log(response);}){
  _aux_ajax('POST',url,params,success,error);
}

$('[data-minimizar]').click(function() {
  const minimizar = $(this).data('minimizar');
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar", !minimizar);
});

$('.modal').on('shown.bs.modal',function(){
  const min = $(this).find('[data-minimizar]');
  if(!min.data('minimizar')){
    setTimeout(function(){
      min.click();
    },250);
  }
});

$("#tabs a").click(function() {
  $("#tabs a").removeClass("active");
  $(this).addClass("active");

  const tab = $($(this).attr("href")); //Find the href attribute value to
  tab.find('.filtro-busqueda-collapse .form-control').val('');//Limpio los filtros
  tab.find('.btn-buscar').click();
  
  $('.tab_content').hide();
  tab.show();
  
  setTimeout(function(){//@HACK: nose porque scrollea cuando tabea...
    $('#tabs').get(0).scrollIntoView();
  },50);
});

function hhmm(hhmmss){
  const arr = hhmmss.split(':');
  if(arr.length != 3) throw 'Formato de hora incorrecto '+hhmmss;
  return arr.slice(0,2).join(':');
}
