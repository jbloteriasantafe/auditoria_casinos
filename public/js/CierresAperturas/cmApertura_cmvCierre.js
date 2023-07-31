import {AUX} from "./AUX.js";
import "/js/lista-datos.js";
import "./modal.js";
import "./inputFecha.js";

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
      $M('[name="id_cargador"]').attr('data-elemento-seleccionado',null);
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
        hora: O?.datos?.hora? AUX.hhmm(O.datos.hora) : '',
        hora_inicio: O?.datos?.hora_inicio? AUX.hhmm(O.datos.hora_inicio) : '',
        hora_fin: O?.datos?.hora_fin? AUX.hhmm(O.datos.hora_fin) : '',
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
        fila.find('[data-js-cargar]:visible,[data-js-ver]:visible').click();
      },300);
    }

    M.modal('show');
  }
  
  $M('[data-js-cargar-apertura]').on('mostrar',function(e,params){
    modalCargarCierreApertura( 
      'CARGAR APERTURAS',TipoModal.apertura,ModoModal.cargar,
      {}
    );
  });
  
  $M('[data-js-cargar-cierre]').on('mostrar',function(e,params){
    M.attr('data-entry-div','[data-js-cargar-cierre]');
    modalCargarCierreApertura(
      'CARGAR CIERRES',TipoModal.cierre,ModoModal.cargar,
      {}
    );
  });

  $M('[data-js-modificar-apertura]').on('mostrar',function(e,params) {
    M.attr('data-entry-div','[data-js-modificar-apertura]');
    AUX.GET('aperturas/getApertura/'+params.id,{},function(data){
      modalCargarCierreApertura(
        'MODIFICAR APERTURA',TipoModal.apertura,ModoModal.modificar,
        data?.apertura ?? {}
      );
    });
  });

  $M('[data-js-modificar-cierre]').on('mostrar',function(e,params) {
    M.attr('data-entry-div','[data-js-modificar-cierre]');
    AUX.GET('cierres/getCierre/'+params.id,{},function(data){
      data = data ?? {};
      data.cierre = data?.cierre ?? {};
      data.cierre.cargador = data?.cierre?.fiscalizador;
      modalCargarCierreApertura(
        'MODIFICAR CIERRE',TipoModal.cierre,ModoModal.modificar,
        data.cierre
      );
    });
  });

  $M('[data-js-validar-cierre]').on('mostrar',function(e,params) {
    M.attr('data-entry-div','[data-js-validar-cierre]');
    AUX.GET('cierres/getCierre/'+params.id,{},function(data){
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
    AUX.GET(`${PATH}/detalleMesa/${id_mesa_de_panio}`,{}, function(data) {
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
        total_anticipos_c: 0,
        id_cargador: $M('.quienSoy').attr('data-elemento-seleccionado'),
        nombre_cargador: $M('.quienSoy').val()
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
    mesa.data('valores',{...valores,...AUX.extraerFormData(M)});
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
    const data = AUX.extraerFormData(M);
    
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
    AUX.POST(`${M.data('path')}/guardar`,formData,
      function(data){
        const mesa = $M('.mesa_seleccionada');
        mesa.find('[data-js-ver]').show().click();
        mesa.find('[data-js-cargar],[data-js-borrar]').remove();
        M.data('cargados',M.data('cargados')+1);
        $(M.attr('data-entry-div')).trigger('success');
        if(M.data('salir_al_completar'))
          $M('.btn-salir').click();
      },
      function(response){
        console.log(response);
        const json = response.responseJSON ?? {};
        AUX.mostrarErroresNames(M,json);
        $M('.tablaFichas tbody tr').each(function(idx,o){
          const err_cantidad = json[`fichas.${idx}.cantidad_ficha`] ?? null;
          const err_monto    = json[`fichas.${idx}.monto_ficha`] ?? null;
          if(err_cantidad)
            mostrarErrorValidacion($(o).find('.cantidad_ficha'),err_cantidad.join(', '),false);
          if(err_monto)
            mostrarErrorValidacion($(o).find('.monto_ficha'),err_monto.join(', '),false);
        });
        AUX.mensajeError();
      }
    );
  });

  $M('[data-js-validar]').click(function(e){
    const formData = obtenerDatosModalCargarCierreApertura();
    AUX.POST('cierres/validar',formData,
      function(data){
        AUX.mensajeExito('Cierre validado');
        M.data('cargados',M.data('cargados')+1);
        $(M.attr('data-entry-div')).trigger('success');
        if(M.data('salir_al_completar'))
          $M('.btn-salir').click();
      },
      function(response){
        console.log(response);
        AUX.mostrarErroresNames(M,response.responseJSON ?? {});
        AUX.mensajeError();
      }
    );
  });

  $M('[data-js-salir]').on('click', function(e){
    e.preventDefault();
    M.modal('hide');
    if(M.data('cargados')){
      AUX.mensajeExito();
    }
  });
});
