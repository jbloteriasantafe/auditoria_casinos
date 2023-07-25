$(function() {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $('.tituloSeccionPantalla').text('Cierres y Aperturas');
  $('[data-js-fecha]').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
  });
  
  $('[data-js-mostrar]').click(function(e){
    e.preventDefault();
    $($(this).attr('data-js-mostrar')).trigger(
      'mostrar',[JSON.parse($(this).attr('data-js-mostrar-params') ?? '{}')]
    );
  });
  
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
$(function(e){
  $('.btn-buscar').on('click', function(e,pagina,page_size,columna,orden){
    e.preventDefault();
    const clickIndice = (tab,e,pageNumber,tam) => {
      if(e == null) return;
      
      e.preventDefault();
      tam = (tam != null) ? tam : tab.find('.herramientasPaginacion').getPageSize();
      const columna = tab.find('.tablaResultados .activa').attr('value');
      const orden = tab.find('.tablaResultados .activa').attr('estado');
      
      tab.find('.btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
    }
    
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
  }).eq(0).click();
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

$(function(e){
  const div = $('[data-js-generar-plantilla]');
  const G = div.find('[data-js-generar-plantilla-modal]');
  const R = div.find('[data-js-reintente]');
  div.on('mostrar',function(e){
    G.modal('show');
    $.ajax({
      type: "POST",
      url: 'aperturas/generarRelevamiento',
      dataType: 'json',
      success: function (data) {
        G.modal('hide');
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
        G.modal('hide');
        setTimeout(function(){
          R.modal('show');
        },500);
      }
    });
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

$(function(e){
  $(document).on('click','[data-js-apertura-a-pedido] [data-js-eliminar-aap]',function(e){
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

  const modal = $('[data-js-apertura-a-pedido]');
  const buscarAperturasAPedido = function(success = function(){}){
    const tabla = modal.find('[data-js-tabla] tbody').empty();
    const molde = modal.find('[data-js-molde]');
    tabla.find('tbody').empty();
    $.ajax({
      url: '/aperturas/buscarAperturasAPedido',
      type: 'GET',
      dataType: 'json',
      success: function(aaps){
        aaps.forEach(function(aap){
          const fila = molde.clone().removeAttr('data-js-molde');
          Object.keys(aap).forEach(function(k){
            fila.find('.'+k).text(aap[k]);
          });
          fila.find('button').val(aap.id_apertura_a_pedido);
          tabla.append(fila);
        });
        success();
      },
      error: function(data){
        mensajeError();
        console.log(data.responseJSON);
      }
    });
  }
  
  modal.on('mostrar',function(){
    modal.find('[data-js-juego] option').removeAttr('selected').eq(0).attr('selected','selected').change();
    modal.find('[data-js-fecha]').each(function(){
      $(this).data('datetimepicker').reset();
    });
    ocultarErrorValidacion(modal.find('input,select'));
    buscarAperturasAPedido(() => modal.modal('show'));
  });

  modal.find('[data-js-juego]').change(function(e){
    e.preventDefault();
    modal.find('[data-js-mesa]').generarDataList("/aperturas/obtenerMesasPorJuego/" + $(this).val(), 'mesas', 'id_mesa_de_panio', 'nro_mesa', 1);
  });

  modal.find('[data-js-agregar]').click(function(e){
    e.preventDefault();
    $.ajax({
      url: '/aperturas/agregarAperturaAPedido',
      type: 'POST',
      dataType: 'json',
      data: extraerFormData(modal),
      success: function(data){
        modal.find('[data-js-juego]').change();//Limpia el input de nro de mesa
        buscarAperturasAPedido();
      },
      error: function(data){
        const response = data.responseJSON;
        console.log(response);
        Object.keys(response).forEach(function(k){
          mostrarErrorValidacion(modal.find(`[name="${k}"]`),response[k].join(', '),true);
        });
      }
    });
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

$(function(e){
  const  M = $('[data-js-ver-cierre-apertura]');
  const $M = M.find.bind(M);
  
  function mostrarCierreApertura(url,sucess = function(data){}){
    GET(url,{},function(data){
      ['Cierre','Apertura'].forEach(function(tipo){
        const CA = data[tipo.toLowerCase()] ?? null;
        const  O = $M(`.datos${tipo}`);
        if(O === null) return $O.hide();
        O.show();
        O.find('.titulo_datos').text(CA? tipo.toUpperCase() : `-SIN ${tipo.toUpperCase()}-`);
        O.find('.datos').toggle(!!CA);
        O.find('.nro_mesa').text(`${CA?.mesa?.nombre} - ${CA?.moneda?.descripcion}`);
        O.find('.nombre_juego').text(CA?.juego?.nombre_juego ?? '-');
        O.find('.fecha').text(CA?.datos?.fecha ?? ' - ');
        O.find('.fiscalizador').text(CA?.fiscalizador?.nombre ?? ' - ');
        O.find('.hora_fin').text(CA?.datos?.hora_fin ?? ' - ');
        O.find('.hora_inicio').text(CA?.datos?.hora_inicio ?? ' - ');
        O.find('.total_pesos_fichas_c').val(CA?.datos?.total_pesos_fichas_c ?? 0);
        O.find('.total_anticipos_c').val(CA?.datos?.total_anticipos_c ?? ' - ');
        O.find('.cargador').text(CA?.cargador?.nombre ?? ' - ');
        O.find('.hora').text(CA?.datos?.hora ?? ' - ');
        O.find('.total_pesos_fichas_a').val(CA?.datos?.total_pesos_fichas_a ?? 0);
        O.find('.observacion').text(CA?.datos?.observacion ?? '');
        O.find('.tablaFichas tbody').empty();
        (CA?.detalles ?? []).forEach(function(ficha){
          const fila = O.find('.moldeFila').clone().removeClass('moldeFila');
          fila.find('.valor_ficha').text(ficha.valor_ficha ?? 0);
          fila.find('.cantidad_ficha').text(ficha.cantidad_ficha ?? 0);
          fila.find('.monto_ficha').text(ficha.monto_ficha ?? 0);
          O.find('.tablaFichas tbody').append(fila);
        });
      });
      $('[data-js-ver-cierre-apertura]').modal('show');
    });
  }
  
  $(document).on('click', '[data-js-ver-apertura]', function(e) {
    mostrarCierreApertura('aperturas/getApertura/'+$(this).val());
  });
  $(document).on('click', '[data-js-ver-cierre]', function(e) {
    mostrarCierreApertura('cierres/getCierre/'+$(this).val());
  });
});

/*
########  ########  ######  ##     ## #### ##    ##  ######  ##     ## ##          ###    ########  
##     ## ##       ##    ## ##     ##  ##  ###   ## ##    ## ##     ## ##         ## ##   ##     ## 
##     ## ##       ##       ##     ##  ##  ####  ## ##       ##     ## ##        ##   ##  ##     ## 
##     ## ######    ######  ##     ##  ##  ## ## ## ##       ##     ## ##       ##     ## ########  
##     ## ##             ##  ##   ##   ##  ##  #### ##       ##     ## ##       ######### ##   ##   
##     ## ##       ##    ##   ## ##    ##  ##   ### ##    ## ##     ## ##       ##     ## ##    ##  
########  ########  ######     ###    #### ##    ##  ######   #######  ######## ##     ## ##     ## 
*/

$(function(e){
  const modal = $('[data-js-desvincular-modal]');
  $(document).on('click','[data-js-desvincular-abrir]', function(e){
    e.preventDefault();
    modal.modal('show');
    modal.find('[data-js-desvincular-boton]').val($(this).val());
  });
  modal.find('[data-js-desvincular-boton]').click(function(e){
    GET('aperturas/desvincularApertura/' + $(this).val(),{}, function(data){
      modal.modal('hide');
      if(data==1){
        mensajeExito('Se ha desvinculado el cierre de esta Apertura.');
        $('#pant_aperturas .btn-buscar').click();
      }
      else{
        mensajeError('No es posible realizar esta acción, ya ha cerrado el periodo de producción correspondiente.');
      }
    });
  });
});

/*
######## ##       #### ##     ## #### ##    ##    ###    ########  
##       ##        ##  ###   ###  ##  ###   ##   ## ##   ##     ## 
##       ##        ##  #### ####  ##  ####  ##  ##   ##  ##     ## 
######   ##        ##  ## ### ##  ##  ## ## ## ##     ## ########  
##       ##        ##  ##     ##  ##  ##  #### ######### ##   ##   
##       ##        ##  ##     ##  ##  ##   ### ##     ## ##    ##  
######## ######## #### ##     ## #### ##    ## ##     ## ##     ## 
*/

$(function(e){
  const modal = $('[data-js-alerta-baja]');
  $(document).on('click','[data-js-eliminar-apertura-abrir]',function(e){
    modal.find('.btn-eliminar').attr('data-url','aperturas/bajaApertura');
    modal.find('.btn-eliminar').val($(this).val());
    modal.find('.mensaje').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTA APERTURA?')
    modal.modal('show');
  });

  $(document).on('click','[data-js-eliminar-cierre-abrir]',function(e){
    modal.find('.btn-eliminar').attr('data-url','cierres/bajaCierre');
    modal.find('.btn-eliminar').val($(this).val());
    modal.find('.mensaje').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTE CIERRE?')
    modal.modal('show');
  });

  modal.find('[data-js-eliminar]').click(function(){
    const url = $(this).attr('data-url')+'/'+$(this).val();
    GET(url,{},function(data){
      mensajeExito('Eliminado con éxito');
      $('.tab_content:visible .btn-buscar').click();
      modal.modal('hide');
    });
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
$(function(e){
  const  M = $('[data-js-validar-apertura-modal]');
  const $M = M.find.bind(M);
  
  $(document).on('click', '[data-js-validar-apertura-abrir]', function(e) {
    const id_apertura_mesa = $(this).val();
    
    $M('.form-control,.observacion').val('');
    $M('.datosA,.datosC').find('h6 span').text('');
    $M('[name="id_cierre_mesa"] option[value!=""]').remove();
    $M('.datosC,.mensajeErrorValApertura').hide();
    $M('.tablaFichas tbody tr').remove();
    $M('[data-js-validar-apertura-validar]').val(id_apertura_mesa).hide();

    GET('aperturas/obtenerApValidar/' + id_apertura_mesa,{},function(A){
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
      $M('[name="id_cierre_mesa"]').append((A?.fechas_cierres ?? []).map(function(c) {   
        return $('<option>').val(c.id_cierre_mesa)
          .text(`${c.fecha} │ ${hhmm(c.hora_inicio)} a ${hhmm(c.hora_fin)} │ ${c.siglas}`);
      }));
      
      M.modal('show');
    });
  });
  
  $M('[data-js-validar-apertura-cambio-fecha]').change(function(e) {
    $M('.datosC,[data-js-validar-apertura-validar]').hide();
    $M('.tablaFichas tbody tr').remove();
    $M('.total_anticipos_c,.total_pesos_fichas_c').val('-');
    
    const id_cierre_mesa   = $(this).val();
    if(id_cierre_mesa.length == 0) return;
    const id_apertura_mesa = $M('[data-js-validar-apertura-validar]').val();
    
    GET(`aperturas/compararCierre/${id_apertura_mesa}/${id_cierre_mesa}`,{},function(data){
      $M('.hora_inicio').text(data?.cierre?.hora_inicio_format ?? '-');
      $M('.hora_fin').text(data?.cierre?.hora_fin_format ?? '-');
      $M('.fecha_cierre').text(data?.cierre?.fecha ?? '-');
      $M('.total_anticipos_c').val(data?.cierre?.total_anticipos_c ?? '-');
      $M('.total_pesos_fichas_c').val(data?.cierre?.total_pesos_fichas_c ?? '-');    
      
      let diferencia = 0;
      (data.fichas ?? []).forEach(function(f){
        const c = (data.detalles_cierre   ?? []).find(c => c.id_ficha == f.id_ficha)?.cantidad_ficha ?? 0;
        const a = (data.detalles_apertura ?? []).find(a => a.id_ficha == f.id_ficha)?.cantidad_ficha ?? 0;
        
        const fila = $M('[data-js-validar-apertura-molde-ficha]')
          .clone().removeAttr('data-js-validar-apertura-molde-ficha');
        fila.attr('data-id-ficha',f.id_ficha);
        fila.find('.valor_ficha').text(f.valor_ficha ?? '-');
        fila.find('.cierre_cantidad_ficha').text(c);
        fila.find('.apertura_cantidad_ficha').text(a);
        const hay_diferencia = (a != c)+0;
        fila.find(`.diferencia i[data-diferencia="${hay_diferencia}"]`).show();
        
        diferencia = diferencia || hay_diferencia;
        $M('.tablaFichas tbody').append(fila);
      });
      
      $M(`.datosC,[data-js-validar-apertura-validar][data-diferencia="${diferencia}"]`).show();
    })
  });
  
  $M('[data-js-validar-apertura-validar]').click(function(e) {
    POST('aperturas/validarApertura',
      {
        ...extraerFormData(M),
        id_apertura_mesa: $(this).val(),
        diferencia:  $(this).attr('data-diferencia')
      },
      function (data){
        M.modal('hide');
        mensajeExito('Apertura Validada correctamente.');
        $('#pant_aperturas .btn-buscar').click();
      },
      function(data){
        console.log(data);
        mostrarErroresNames(M,data.responseJSON ?? {});
        mensajeError();
      }
    );
  });
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

$(function(e){
  const TipoModal = {
    cierre: 'cierres',apertura: 'aperturas'
  };
  const ModoModal = {
    cargar: '#6dc7be',modificar: '#FFB74D',validar: '#69F0AE'
  };
  
  const _M = '[data-js-cargar-apertura-cierre]';
  const  M = $(_M);
  const $M = M.find.bind(M);

  const modalCargarCierreApertura = function(titulo,tipo_modal,modo_modal,O){
    {//Limpiar
      $M('[data-js-fecha]').data('datetimepicker').reset();
      $M('.form-control').val('').change();
      ocultarErrorValidacion($M('.form-control'));
      ocultarErrorValidacion($M('[name="observacion"]'));
      $M('[name="observacion"]').val('').change();
      $M('.tablaMesas tbody tr,.tablaFichas tbody tr').remove();
      $M('.inputMesas,.datosCierreApertura').hide();
      const quienSoy = $('#quienSoy').clone().show().removeAttr('id');
      $M('[name="id_cargador"]').replaceWith(quienSoy);
      $M('.moldeFila').find('[data-js-cargar],[data-js-ver],[data-js-borrar]').show();
      M.data('cargados',0);
      M.data('salir_al_completar',modo_modal == ModoModal.modificar || modo_modal == ModoModal.validar);
    }
    {//Armar modal
      $M('.tipo').text(titulo);
      $M(".modal-header").css('background-color',modo_modal);
      M.data('path',tipo_modal);
      $M("[data-js-campo-cierres]").toggle(tipo_modal == TipoModal.cierre);
      $M("[data-js-campo-aperturas]").toggle(tipo_modal == TipoModal.apertura);
      $M("[data-js-campo-cargar]").toggle(modo_modal == ModoModal.cargar);
      $M("[data-js-campo-cargar-modificar]").toggle(modo_modal == ModoModal.cargar || modo_modal == ModoModal.modificar);
      $M("[data-js-campo-validar]").toggle(modo_modal == ModoModal.validar);
      $M("[name='id_casino'],[name='fecha']").attr('disabled',modo_modal != ModoModal.cargar);
    }
    
    if(O?.mesa){//Setear mesa si vino una
      $M('[data-js-fecha]').data('datetimepicker').setDate(
        new Date(O.datos.fecha+'T00:00')
      );
      $M('[name="id_casino"]').val(O?.mesa?.id_casino ?? '');
      $M('[data-js-fecha],[data-js-casino]').change();
          
      const fila = $M('.moldeFila').clone().removeClass('moldeFila');
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
      $M('.tablaMesas tbody').append(fila);
      
      setTimeout(function(){
        fila.find('.cargar').click();
      },300);
    }

    M.modal('show');
  }
;
  $('[data-js-cargar-apertura]').on('mostrar',function(e){
    modalCargarCierreApertura( 
      'CARGAR APERTURAS',TipoModal.apertura,ModoModal.cargar,
      {}
    );
  });
  
  $('[data-js-cargar-cierre]').on('mostrar',function(e){
    modalCargarCierreApertura(
      'CARGAR CIERRES',TipoModal.cierre,ModoModal.cargar,
      {}
    );
  });

  $(document).on('click', '[data-js-modificar-apertura]', function(e) {
    GET('aperturas/getApertura/'+$(this).val(),{},function(data){
      modalCargarCierreApertura(
        'MODIFICAR APERTURA',TipoModal.apertura,ModoModal.modificar,
        data?.apertura ?? {}
      );
    });
  });

  $(document).on('click', '[data-js-modificar-cierre]', function(e) {
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

  $(document).on('click', '[data-js-validar-cierre]', function(e) {
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

  $M('[data-js-casino],[data-js-fecha]').change(function(e){
    const id_casino = $M('[name="id_casino"]').val();
    const fecha     = $M('[name="fecha"]').val();
    $M('.inputMesas').toggle(id_casino.length != 0 && fecha.length != 0);
  });

  $M('[data-js-casino]').change(function(e){
    const id_casino = $M('[name="id_casino"]').val();
    const PATH = M.data('path');
    $M('.tablaMesas tbody').empty()
    $M('.mesa').generarDataList(
      `${PATH}/obtenerMesas/${id_casino}`,
      'mesas' ,'id_mesa_de_panio','nro_mesa',1
    );
    $M('[name="id_fiscalizador"]').generarDataList(
      `${PATH}/buscarFiscalizadores/${id_casino}`,
      'usuarios' ,'id_usuario','nombre',1
    );
  });

  $M('[data-js-agregar-mesa]').click(function(e) {
    const id_mesa_de_panio = $M('.mesa').attr('data-elemento-seleccionado');
    $M('.mesa').setearElementoSeleccionado(null,"");
    
    const ya_existe = $M('.tablaMesas').find('button').filter(function(idx,o){
      return $(o).val() == id_mesa_de_panio;
    }).length > 0;
    if(ya_existe) return;
    
    const PATH = M.data('path');
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
      
      const fila = $M('.moldeFila').clone().removeClass('moldeFila');
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
      fila.find('[data-js-ver]').hide();
      $M('.tablaMesas tbody').append(fila);
    });
  });

  $(document).on('click', `${_M} [data-js-borrar]`, function(e){
    const fila = $(this).closest('tr');
    const prev = fila.prev();
    const next = fila.next();
    fila.remove();
    if(!fila.hasClass('mesa_seleccionada')) return;
    
    (next.length == 0? prev : next).find('.cargar').click();
    $M('.datosCierreApertura').toggle(next.length+prev.length);
  });

  const cargarMesa = function(mesa,deshabilitado){
    $M('.tablaMesas tbody tr').removeClass('mesa_seleccionada');
    mesa.addClass('mesa_seleccionada');

    const valores = mesa.data('valores');
    $M('[name="id_moneda"] option').prop('disabled',true);
    Object.keys(valores.fichas).forEach(function(id_moneda){
      $M(`[name="id_moneda"] option[value="${id_moneda}"]`).prop('disabled',false);
    });
    
    $M('[name="id_moneda"]').val(valores.id_moneda);
    $M('[name="total_pesos_fichas_a"]').val(valores.total_pesos_fichas_a);
    $M('[name="total_pesos_fichas_c"]').val(valores.total_pesos_fichas_c);
    $M('[name="total_anticipos_c"]').val(valores.total_anticipos_c);
    $M('[name="hora"]').val(valores.hora);
    $M('[name="hora_inicio"]').val(valores.hora_inicio);
    $M('[name="hora_fin"]').val(valores.hora_fin);
    
    if(valores.id_cargador){
      $M('[name="id_cargador"]').attr('data-elemento-seleccionado',valores.id_cargador)
      .val(valores.nombre_cargador ?? '')
      .attr('value',valores.nombre_cargador ?? '');
    }
    
    $M('[name="id_fiscalizador"]').setearElementoSeleccionado(
      valores.id_fiscalizador ?? null,
      valores.nombre_fiscalizador ?? ''
    );
    
    $M('.btn-guardar').toggle(!deshabilitado);
    $M('.datosCierreApertura [name]').change();
    $M('.datosCierreApertura .form-control').not('[readonly]').attr('disabled',!!deshabilitado);
    $M('.datosCierreApertura').show();
  };
  
  $(document).on('click', `${_M} [data-js-cargar]`, function(e){
    e.preventDefault();
    cargarMesa($(this).closest('tr'),false);
  });
  $(document).on('click', `${_M} [data-js-ver]`, function(e){
    e.preventDefault();
    cargarMesa($(this).closest('tr'),true);
  });

  $M('[data-js-moneda]').change(function(e){
    e.stopPropagation();
    const mesa = $M('.mesa_seleccionada');
    const valores = mesa.data('valores') ?? {};
    valores.id_moneda = $M('[name="id_moneda"]').val();
    mesa.data('valores',valores);
      
    const tabla = $M('.tablaFichas tbody').empty();
    const moldeFila = $M('.moldeFichas').clone().removeClass('moldeFichas');
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

  $M('.datosCierreApertura [name]').change(function(e){//actualizar los datos de la mesa
    const mesa = $M('.mesa_seleccionada');
    const valores = mesa.data('valores') ?? {};
    mesa.data('valores',{...valores,...extraerFormData(M)});
  });
  
  $(document).on('change',`${_M} [data-js-cambio-ficha]`,function(e){
    const mesa = $M('.mesa_seleccionada');
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
    const fichas = $M('.tablaFichas tbody tr').map(function(idx,o){
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
    
    $M('[name="total_pesos_fichas_c"],[name="total_pesos_fichas_a"]')
    .val(total).attr('value',total);
  });

  function obtenerDatosModalCargarCierreApertura(){
    const data = extraerFormData(M);
    
    data.fichas = $M('.tablaFichas tbody tr').map(function(idx,f){
      return {
        id_ficha: $(f).attr('data-id_ficha'),
        valor_ficha: $(f).find('.valor_ficha').val(),
        cantidad_ficha: $(f).find('.cantidad_ficha').val(),
        monto_ficha: $(f).find('.monto_ficha').val()
      };
    }).toArray();
    
    const mesa = $M('.mesa_seleccionada');
    data.id_mesa_de_panio = mesa.data('valores').id_mesa_de_panio;
    data.id_cierre_mesa   = mesa.data('valores').id_cierre_mesa;
    data.id_apertura_mesa = mesa.data('valores').id_apertura_mesa;
    
    return data;
  }

  $M('[data-js-guardar]').click(function(e){
    const formData = obtenerDatosModalCargarCierreApertura();
    POST(`${M.data('path')}/guardar`,formData,
      function(data){
        const mesa = $M('.mesa_seleccionada');
        mesa.find('[data-js-ver]').show();
        mesa.find('[data-js-cargar],[data-js-borrar]').remove();
        M.data('cargados',M.data('cargados')+1);
        $('.tab_content:visible .btn-buscar').click();
        if(M.data('salir_al_completar'))
          $M('.btn-salir').click();
      },
      function(response){
        console.log(response);
        const json = response.responseJSON ?? {};
        mostrarErroresNames(M,json);
        $M('.tablaFichas tbody tr').each(function(idx,o){
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

  $M('[data-js-validar]').click(function(e){
    const formData = obtenerDatosModalCargarCierreApertura();
    POST('cierres/validar',formData,
      function(data){
        mensajeExito('Cierre validado');
        M.data('cargados',M.data('cargados')+1);
        $('.tab_content:visible .btn-buscar').click();
        if(M.data('salir_al_completar'))
          $M('.btn-salir').click();
      },
      function(response){
        console.log(response);
        mostrarErroresNames(M,response.responseJSON ?? {});
        mensajeError();
      }
    );
  });

  $M('[data-js-salir]').on('click', function(e){
    e.preventDefault();
    M.modal('hide');
    if(M.data('cargados')){
      mensajeExito();
    }
  });
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

function hhmm(hhmmss){
  if(hhmmss === null) return '--:--';
  const arr = hhmmss.split(':');
  if(arr.length != 3) throw 'Formato de hora incorrecto '+hhmmss;
  return arr.slice(0,2).join(':');
}

function extraerFormData(modal){
  const data = {};
  modal.find('[name]').map(function(idx,o){
    const attr = $(o).attr('data-js-formdata-attr');
    data[$(o).attr('name')] = attr? $(o).attr(attr) : $(o).val();
  });
  return data;
}

function mostrarErroresNames(modal,json){
  Object.keys(json).forEach(function(k){
    mostrarErrorValidacion(modal.find(`[name="${k}"]`),json[k].join(', '),true);
  });
}
