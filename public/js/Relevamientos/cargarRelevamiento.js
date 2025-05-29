import '/js/Components/inputFecha.js';
import '/js/Components/modal.js';
import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-modal-cargar-relevamiento]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  const dname_f = function(s){return `[data-js-detalle-asignar-name="${s}"]`;};
  let modo = null;
  
  const getFormData = function(obj){
    return Object.fromEntries(
      obj.find('[name]').map(function(_,nobj){
        return [[$(nobj).attr('name'),$(nobj).val()]];
      }).toArray()
    );
  };
  
  const llenarFila = function(fila,d){
    fila.find(dname_f('[detalle][id_detalle_relevamiento]')).val(d.detalle.id_detalle_relevamiento);
    fila.find(dname_f('[maquina][id_maquina]')).val(d.maquina.id_maquina);
    fila.find(dname_f('[isla][id_isla]')).val(d.isla.id_isla ?? '');
    fila.find(dname_f('[formula][id_formula]')).val(d.formula.id_formula ?? '');
    fila.find(dname_f('[detalle][id_unidad_medida]')).val(d.detalle.id_unidad_medida);
    
    fila.find(dname_f('[maquina][nro_admin]')).text(d.maquina.nro_admin);
    fila.find(dname_f('[maquina][marca_juego]')).text(d.maquina.marca_juego ?? '');
    fila.find(dname_f('[isla][nro_isla]')).text(d.isla.nro_isla ?? '');
    
    for(let c=1;c<=CONTADORES;c++){
      const cont_s = 'cont'+c;
      const cval = d?.detalle?.[cont_s] ?? '';
      const fval = d?.formula?.[cont_s] ?? '';
      const visible = fval.length > 0;
      fila.find(dname_f('[detalle]['+cont_s+']')).val(cval).toggle(visible);
      fila.find(dname_f('[formula]['+cont_s+']')).text(fval).toggle(visible);
    }
    
    fila.find(dname_f('[detalle][id_tipo_causa_no_toma]')).val(d.detalle.id_tipo_causa_no_toma ?? '');
    fila.find('[data-js-estadisticas-no-toma]').attr('href',fila.find('[data-js-estadisticas-no-toma]').attr('href')+'/'+d.detalle.id_maquina);
      
    fila.attr('data-css-colorear',d.detalle.estado);
    fila.attr('data-id_unidad_medida',d.detalle.id_unidad_medida);
    
    fila.find(dname_f('[detalle][id_unidad_medida]')).val(d.detalle.id_unidad_medida);
    fila.find(dname_f('[detalle][denominacion]')).val(d.detalle.denominacion);
    fila.find(dname_f('[maquina][id_unidad_medida]')).val(d.maquina.id_unidad_medida ?? '');
    fila.find(dname_f('[maquina][denominacion]')).val(d.maquina.denominacion ?? '');
    fila.find(dname_f('[detalle][producido_calculado_relevado]')).val(d.detalle.producido_calculado_relevado);
    fila.find(dname_f('[detalle][producido_importado]')).val(d.detalle.producido_importado);
    fila.find(dname_f('[detalle][diferencia]')).val(d.detalle.diferencia);

    if(fila.find(dname_f('[detalle][id_tipo_causa_no_toma]')).val() != ''){
      fila.find('[data-js-cambio-contador]').val('');
      fila.find('[data-contador]').not('[readonly]').attr('disabled',true);
    }
    else{
      fila.find('[data-contador]').not('[readonly]').removeAttr('disabled');
    }
    
    fila.find('[data-js-cambio-contador]').attr('data-procesado','true')
  };
  
  const POST_async = async function(url,data,ext_params={}){
    return new Promise((resolve, reject) => {
      AUX.POST(url, data, resolve, reject,ext_params);
    });
  };
  
  const cambiarDenominacion = function(e){
    const tgt = $(e.target);
    const url = tgt.attr('data-js-cambio-cambiar-denominacion');
    const fila = tgt.closest('[data-fila-tabla]');
    const formData = getFormData(fila);
    AUX.POST(url,formData,
      function(estados){
        for(const idr in estados){
          const e    = estados[idr];
          const fila = $M(`[data-fila-tabla="${idr}"]`);
          llenarFila(fila,e);
        }
      },
      function(data){
        console.log(data);
        AUX.mostrarErroresNamesJSONResponse(M,data?.responseJSON ?? {},true);
      },
    );
  };
  
  let calculo_encolado = null;
  const forzarRecalculo = async function(){
    clearTimeout(calculo_encolado);
    await _cambioContador();
  };
      
  const _cambioContador = async function(){
    const filas = $M('[data-js-tabla-relevamiento] [data-procesado="false"]').closest('[data-fila-tabla]');
    
    //Guardo los numeros al a izquierda y derecha de la selección para restaurarla cuando reemple los valores
    const selectionMarker = {};
    filas.each(function(fidx,f){
      const $f = $(f);
      const id_detalle_relevamiento = $f.attr('data-fila-tabla');
      selectionMarker[id_detalle_relevamiento] = {};
      
      for(let c=1;c<=CONTADORES;c++){
        const val = $f.find(`[data-js-cambio-contador="${c}"]`).val();
        selectionMarker[id_detalle_relevamiento][c] = {
          obj: f,
          izq: val.substring(0,f.selectionStart).replaceAll('.',''),
          der: val.substring(f.selectionStart).replaceAll('.',''),
        };
      }
    });
    
    ocultarErrorValidacion(filas.find('.alerta'));
    calculo_encolado = null;
    try {
      const formData = getFormData(filas);
      const dets = await POST_async('relevamientos/calcularEstadoDetalleRelevamiento',formData);
      for(const idr in dets){
        const d    = dets[idr];
        const fila = $M(`[data-fila-tabla="${idr}"]`);
        llenarFila(fila,d);
        
        const selection = selectionMarker?.[d.detalle.id_detalle_relevamiento+''] ?? null;
        if(selection === null) continue;
        
        for(const cont in selection){
          const sel = selection[cont];
          const val = d.detalle[cont] ?? '';
          for(let idx=0;idx<val.length;idx++){
            const val_izq = val.substring(0,idx).replaceAll('.','');
            const val_der = val.substring(idx).replaceAll('.','');
            if(sel.izq == val_izq && sel.der == val_der){
              sel.obj.selectionStart = idx;
              sel.obj.selectionEnd   = idx;
              break;
            }
          }
          $(sel.obj).attr('data-procesado','true');
        }
      }
    }
    catch(data){
      console.log(data);
      AUX.mostrarErroresNamesJSONResponse(M,data?.responseJSON ?? {},true);
    }
  };
  
  //El calcular le pongo un delay por si teclea muchas teclas... hace mas suave el tipeo porque no tenes javascript seteandote el valor del input
  const CALCULAR_DELAY_MS = 3500;
  async function cambioContador(e){
    const code = e.which || e.keyCode;
    if((code >= 35 && code <= 40)//Inicio, Fin, Flechas
    || (code >= 16 && code <= 18)){//Ctrl shift alt
      return;
    }
    
    $(e.target).attr('data-procesado','false');
    if(calculo_encolado !== null){
      clearTimeout(calculo_encolado);
    }
    calculo_encolado = setTimeout(function(){
      _cambioContador();
    },CALCULAR_DELAY_MS);
  };
  
  function cargarTablaRelevamientos(data, tabla){
    const filtrarNumeros = function(e){
      const code = e.which || e.keyCode;
      const c = String.fromCharCode(code);
      const tgt = $(e.target);
      const val = tgt.val();
      
      if((c == ',' || c == '.') && val.includes(',')){
        e.preventDefault();
        return;
      }
      if(c == '-' && val.includes('-')){
        e.preventDefault();
        return;
      }
      
      if(c == '.'){
        e.preventDefault();
        const pos = this.selectionStart;
        tgt.val(
          val.substring(0,pos)+','+val.substring(pos)
        );
        this.selectionStart = pos+1;
        this.selectionEnd = pos+1;
        return;
      }
      if(c == '-' && this.selectionStart == 0){
        e.preventDefault();
        tgt.val('-'+val.substring(this.selectionEnd));
        this.selectionStart = 1;
        this.selectionEnd = 1;
        return;
      }
      if(!c.match(/^(,|[0-9])$/)){
        e.preventDefault();
      }
    };
    
    const sacarErroresContadores = function(e){
      ocultarErrorValidacion($(e.target).closest('[data-fila-tabla]').find('[data-js-cambio-contador].alerta'));
    };
    
    tabla.empty();
    for(const didx in data.detalles){
      const d = data.detalles[didx];
      const fila = $M('[data-js-molde-tabla-relevamiento]').clone().removeAttr('data-js-molde-tabla-relevamiento')
      .attr('data-fila-tabla',d.detalle.id_detalle_relevamiento);
      
      fila.find('[data-js-detalle-asignar-name]').each(function(idx,obj){
        const name = $(obj).attr('data-js-detalle-asignar-name');
        $(obj).attr('name',`detalles[${didx}]${name}`);
      });
      
      llenarFila(fila,d);
            
      tabla.append(fila);
      
      if(modo == 'Validar'){
        fila.find('[data-js-cambio-contador]').each(function(idx,obj){$(obj).attr('title',$(obj).val());})
        .attr('readonly',true);
      }
      else if(modo == 'Ver'){
        fila.find('input').each(function(idx,obj){
          const val = $(obj).val().length == 0? '--' : $(obj).val();
          const span = $('<span>').text(val.trim()).addClass('celda-ver');
          $(obj).replaceWith(span);
        });
        fila.find('select').each(function(idx,obj){
          const val = $(obj).find('option:selected').text().length == 0? '--' : $(obj).find('option:selected').text();
          const span = $('<span>').text(val.trim()).addClass('celda-ver');
          $(obj).replaceWith(span);
        });
      }
      
      fila.find('span').each(function(idx,obj){
        $(obj).attr('title',$(obj).text());
      });
      fila.find('input').each(function(idx,obj){
        $(obj).attr('title',$(obj).val());
      });
      fila.find('select').each(function(idx,obj){
        $(obj).attr('title',$(obj).find('option:selected').text());
      });
      
      fila.find('[data-js-cambio-tipo-causa-no-toma]').on('change',async function(e){
        fila.find('[data-js-cambio-contador]').attr('data-procesado','false');
        await forzarRecalculo();
      });
      fila.find('[data-js-cambio-contador]').on('keypress',filtrarNumeros);
      fila.find('[data-js-cambio-contador]').on('keyup',cambioContador);
      fila.find('[data-js-cambio-cambiar-denominacion]').on('change',cambiarDenominacion);
      fila.find('[data-js-cambio-contador]:not([readonly],[disabled]),[data-js-cambio-tipo-causa-no-toma]').on('focus',sacarErroresContadores);
    }
    
    tabla.find('[data-js-icono-estado]').popover({
      html:true
    });
    
    M.modal('show');
  }
  
  M.on('mostrar',function(e,modo_arg,id_relevamiento){
    modo = modo_arg;
    M.attr('data-modo',modo);
    const checkCSV = function(attr){
      return function(idx,obj){
        return $(obj).attr(attr).split(',').includes(modo);
      };
    };
    $M('[name]').val('');
    $M('[data-js-salir]').attr('data-guardado',1);
    ocultarErrorValidacion($M('[name]'));
    $M('[name="id_relevamiento"]').val(id_relevamiento);
            
    $M('[data-js-modo]')
    .hide().attr('data-no-mostrar',true)
    .filter(checkCSV('data-js-modo'))
    .show().removeAttr('data-no-mostrar');
    
    $M('[data-js-enabled]:not([data-js-fecha])')
    .attr('disabled',true).attr('data-no-habilitar',true)
    .filter(checkCSV('data-js-enabled'))
    .removeAttr('disabled').removeAttr('data-no-habilitar');
    
    $M('[data-js-enabled][data-js-fecha]')//Habilitar/deshabilitar DTPs
    .each(function(idx,obj){
      obj.disabled(true);
    })
    .filter(checkCSV('data-js-enabled'))
    .each(function(idx,obj){
      obj.disabled(false);
    });
    
    $M('[data-js-readonly]')
    .removeAttr('readonly')
    .filter(checkCSV('data-js-readonly'))
    .attr('readonly',true);
        
    AUX.GET('relevamientos/obtenerRelevamiento/'+id_relevamiento,{},function(data){
      $M('[name="fecha"]').val(data.relevamiento.fecha);
      $M('[name="fecha_generacion"]').val(data.relevamiento.fecha_generacion);
      $M('[name="casino"]').val(data.casino);
      $M('[name="sector"]').val(data.sector);
      $M('[name="subrelevamiento"]').val(data.relevamiento.subrelevamiento ?? '');
      $M('[name="hora_ejecucion"]').val(data.relevamiento.fecha_ejecucion);
      $M('[name="tecnico"]').val(data.relevamiento.tecnico);
      // si el relevamiento no tiene usuario fizcalizador se le asigna el actual
      $M('[name="usuario_cargador"]').val(data?.usuario_cargador?.nombre ?? data.usuario_actual.nombre);
      
      $M('[data-js-input-usuario-fiscalizador]').generarDataList('relevamientos/buscarUsuariosPorNombreYCasino/'+ data.id_casino,'usuarios','id_usuario','nombre',2);
      $M('[data-js-input-usuario-fiscalizador]').setearElementoSeleccionado(data?.usuario_fiscalizador?.id_usuario ?? 0,data?.usuario_fiscalizador?.nombre ?? "");
      $M('[data-js-input-usuario-fiscalizador]').change();
      
      $M('[name="observacion_carga"]').val(data?.relevamiento?.observacion_carga ?? '');
      $M('[name="observacion_validacion"]').val(data?.relevamiento?.observacion_validacion ?? '');
    
      $M('[data-js-salir]').attr('data-guardado',1);
      
      cargarTablaRelevamientos(data,$M('[data-js-tabla-relevamiento]'));
    });
  });
  
  $M('[data-js-input-usuario-fiscalizador]').change(function(e){
    const obj = $M($(this).attr('data-js-input-usuario-fiscalizador'));
    const elemento_seleccionado = $(this).attr('data-elemento-seleccionado');
    obj.val(elemento_seleccionado == 0? '' : elemento_seleccionado);
  });
  
  $M('[data-js-salir]').click(function(){
    const guardado = $(this).attr('data-guardado') == 1;
    const vio_mensaje_salida = $M('[data-js-mensaje-salida]:visible').length > 0;
    const mensaje_salida_no_mostrable = !!$M('[data-js-mensaje-salida]').attr('data-no-mostrar');
    if(guardado || vio_mensaje_salida || mensaje_salida_no_mostrable) return M.modal('hide');
    //Muestro el mensaje
    M.animate({ scrollTop: $M('[data-js-mensaje-salida]').show().offset().top }, 'slow');
  });

  $('[data-js-guardar]').click(async function(e){
    try {
      await cargarRelevamiento(2);
      M.trigger('guardo');
      $M('[data-js-salir]').attr('data-guardado',1);
      $M('[data-js-mensaje-salida]').hide();
      AUX.mensajeExito('Relevamiento guardado');
    } catch(_){}
  });
  
  $M('[data-js-finalizar-carga]').click(async function(e){
    try {
      await cargarRelevamiento(3);
      AUX.mensajeExito('Relevamiento finalizado');
      M.trigger('finalizo');
      M.modal('hide');
    } catch(_){}
  });
  
  async function cargarRelevamiento(estado) {     
    try {
      await forzarRecalculo();
      const formData = getFormData($M('form'));
      formData.estado = estado;
      return await POST_async('relevamientos/cargarRelevamiento',formData);
    }
    catch(data){
      console.log(data);
      const response = data.responseJSON ?? {};
      
      if(response.id_usuario_fiscalizador !== undefined){
        mostrarErrorValidacion($M('[data-js-input-usuario-fiscalizador]'),response.id_usuario_fiscalizador.join(', '),true);
      }
      
      AUX.mostrarErroresNamesJSONResponse(M,response,true);
      
      const filaError = $M('[data-js-tabla-relevamiento] .popAlerta:first').closest('[data-fila-tabla]');
      if(filaError.length){
        const div_scrollable = $M('[data-div-tabla-scrollable-errores]');
        div_scrollable.animate({ scrollTop: filaError[0].offsetTop }, "slow");
        M.animate({scrollTop: div_scrollable}, "slow");
      }
      else if(Object.keys(response).length > 0){
        M.animate({ scrollTop: 0 }, "slow");
      }
      
      throw data;
    }
  }
  
  //CAMBIOS EN TABLAS RELEVAMIENTOS / MOSTRAR BOTÓN GUARDAR
  //@TODO: bindear directamente para que sea mas rapido
  M.on('change','input,select,textarea,.form-control',function(e){
    $M('[data-js-salir]').attr('data-guardado',0);
    $M('[data-js-mensaje-salida]').hide();
  });
    
  M.on('click','[data-js-cancelar-ajuste]',function(e){
    M.find('[data-js-boton-medida]').popover('hide');
  });
  
  $M('[data-js-finalizar-validacion]').click(async function(e){
    try {
      await forzarRecalculo();
      const formData = getFormData($M('form'));
      formData.truncadas = $M('[data-js-tabla-relevamiento]').find('[data-js-icono-estado="icono_truncado"]').length;
      await POST_async('relevamientos/validarRelevamiento',formData);
      M.trigger('valido');
    }
    catch(data){
      console.log(data);
      AUX.mensajeError(data?.responseJSON?.faltan_contadores?.join(', ') ?? '');
    }
  });
})});
