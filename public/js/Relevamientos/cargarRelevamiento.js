import '/js/Components/inputFecha.js';
import '/js/Components/modal.js';
import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-modal-cargar-relevamiento]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  const dname_f = function(s){return `[data-js-detalle-asignar-name="${s}"]`;};
  let modo = null;
    
  const calcularEstadoDetalleRelevamiento = function(idrs){
    const detalles = $M('[data-js-tabla-relevamiento] tbody tr').map(function(idx,obj){
      const fila = $(obj);
      const idr = fila.find(dname_f('id_detalle_relevamiento')).val();
      
      if(!idrs.includes(idr)) return;
      
      const fd = {
        id_detalle_relevamiento: idr,
      };
      fila.find('[data-js-cambio-contador]').each(function(idx,cont){
        const name = $(cont).attr('data-js-detalle-asignar-name');
        fd[name] = $(cont).val();
      });
      return fd;
    }).toArray();
    
    AUX.POST('relevamientos/calcularEstadoDetalleRelevamiento',{detalles: detalles},function(estados){
      for(const idr in estados){
        const e = estados[idr];
        const fila = $M(`[data-js-tabla-relevamiento] tbody tr[data-id_detalle_relevamiento="${idr}"]`);
        const hay_contadores = fila.find('[data-js-cambio-contador]').filter(function(idx,obj){
          return $(obj).val().length > 0;
        }).length > 0;
        fila.find(dname_f('producido')).val(e.importado);
        fila.find(dname_f('producido_calculado_relevado')).val(e.relevado);
        fila.find(dname_f('diferencia')).val(e.diferencia);
        fila.find('[data-js-icono-estado]').hide().filter(`[data-js-icono-estado="${e.estado}"]`).show();
        fila.attr('data-css-colorear',e.estado);
        
        if(modo == 'Validar'){
          if(!(e.estado == 'DIFERENCIA' || e.estado == 'NO_TOMA')){
            fila.find('[data-js-boton-medida],[data-js-estadisticas-no-toma]').replaceWith('&nbsp;');
          }
          fila.find('input').each(function(idx,obj){$(obj).attr('title',$(obj).val());});
          if (e.estado == 'NO_TOMA'){
            fila.find(dname_f('id_tipo_causa_no_toma')).css('border','2px solid #1E90FF').css('color','#1E90FF');
          }
        }
        else if(modo == 'Ver'){
          fila.find('input').each(function(idx,obj){
            const val = $(obj).val().length == 0? '--' : $(obj).val();
            $(obj).replaceWith(val);
          });
          fila.find('select').each(function(idx,obj){
            const val = $(obj).find('option:selected').text().length == 0? '--' : $(obj).find('option:selected').text();
            $(obj).replaceWith(val);
          });
          fila.find('td').each(function(idx,obj){
            if($(obj).children().length > 0) return;
            $(obj).attr('title',$(obj).text().trim());
          });
        }
      }
    });
  }
  
  function cargarTablaRelevamientos(data, tabla){
    data.detalles.forEach(function(d,didx){
      const fila = $M('[ data-js-molde-tabla-relevamiento]').clone().removeAttr('data-js-molde-tabla-relevamiento')
      .attr('data-medida', d.unidad_medida.id_unidad_medida)//Unidad de medida: 1-Crédito, 2-Pesos 
      .attr('data-denominacion', d.denominacion)//Denominación: para créditos
      .attr('data-id_detalle_relevamiento',d.detalle.id_detalle_relevamiento);
      
      fila.find('[data-js-detalle-asignar-name]').each(function(idx,obj){
        const name = $(obj).attr('data-js-detalle-asignar-name');
        $(obj).attr('name',`detalles[${didx}][${name}]`);
      });
      
      fila.find(dname_f('id_detalle_relevamiento')).val(d.detalle.id_detalle_relevamiento);
      fila.find(dname_f('id_unidad_medida')).val(d.unidad_medida.id_unidad_medida);
      fila.find(dname_f('denominacion')).val(d.denominacion);
      fila.find(dname_f('maquina')).text(d.maquina);
      
      for(let c=1;c<=CONTADORES;c++){
        const cont_s = 'cont'+c;
        const cont = fila.find(`[data-js-cambio-contador="${c}"]`).val(d?.detalle?.[cont_s] ?? '');
        const f = d?.formula?.[cont_s] ?? '';
        const o = d?.formula?.['operador'+c] ?? '';
        cont.prop('readonly',f == '');
        cont.attr('data-formula',f ?? '');
        cont.attr('data-operador',o ?? '');
      }
      
      fila.find(dname_f('id_tipo_causa_no_toma')).val(d.tipo_causa_no_toma ?? '');
      fila.find(dname_f('denominacion')).val(d.unidad_medida.id_unidad_medida == 1? d.denominacion : 1);
            
      fila.find('[data-js-boton-medida]').filter(`[data-js-boton-medida!="${d.unidad_medida.id_unidad_medida}"]`).remove();
      fila.find('[data-js-estadisticas-no-toma]').attr('href',fila.find('[data-js-estadisticas-no-toma]').attr('href')+'/'+d.detalle.id_maquina);
            
      tabla.append(fila);
    });
    
    tabla.find('[data-js-boton-medida]').popover({
      html:true
    })
    .click(function(e){
      e.preventDefault();
      tabla.find('[data-js-boton-medida]').not(this).popover('hide');
      $(this).popover('show');
    });
    
    tabla.find('[data-js-icono-estado]').popover({
      html:true
    });
    
    calcularEstadoDetalleRelevamiento(tabla.find('tr').map(function(idx,obj){
      return $(obj).attr('data-id_detalle_relevamiento');
    }).toArray());
  }
  
  M.on('mostrar',function(e,modo_arg,id_relevamiento){
    modo = modo_arg;
    const checkCSV = function(attr){
      return function(idx,obj){
        return $(obj).attr(attr).split(',').includes(modo);
      };
    };
    $M('[name]').val('');
    $M('[data-js-salir]').attr('data-guardado',1);
    ocultarErrorValidacion($M('[name]'));
    $M('[name="id_relevamiento"]').val(id_relevamiento);
    $M('[data-js-mensaje-salida],[data-js-finalizar-carga]').hide();
        
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
        
    const tbody = $M('[data-js-tabla-relevamiento] tbody').empty();
    
    AUX.GET('relevamientos/obtenerRelevamiento/'+id_relevamiento,{},function(data){
      $M('[name="fecha"]').val(data.relevamiento.fecha);
      $M('[name="fecha_generacion"]').val(data.relevamiento.fecha_generacion);
      $M('[name="casino"]').val(data.casino);
      $M('[name="sector"]').val(data.sector);
      //@TODO: Poner subrelevamiento ???
      $M('[name="hora_ejecucion"]').val(data.relevamiento.fecha_ejecucion);
      $M('[name="tecnico"]').val(data.relevamiento.tecnico);
      // si el relevamiento no tiene usuario fizcalizador se le asigna el actual
      $M('[name="usuario_cargador"]').val(data?.usuario_cargador?.nombre ?? data.usuario_actual.nombre);
      
      $M('[data-js-input-usuario-fiscalizador]').generarDataList('relevamientos/buscarUsuariosPorNombreYCasino/'+ data.id_casino,'usuarios','id_usuario','nombre',2);
      $M('[data-js-input-usuario-fiscalizador]').setearElementoSeleccionado(data?.usuario_fiscalizador?.id_usuario ?? 0,data?.usuario_fiscalizador?.nombre ?? "");
      $M('[data-js-input-usuario-fiscalizador]').change();
      
      $M('[name="observacion_carga"]').val(data?.relevamiento?.observacion_carga ?? '');
      $M('[name="observacion_validacion"]').val(data?.relevamiento?.observacion_validacion ?? '');
    
      cargarTablaRelevamientos(data,tbody);
      
      $M('[data-js-salir]').attr('data-guardado',1);
      
      M.modal('show');
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

  $('[data-js-guardar]').click(function(e){
    cargarRelevamiento(2,function(){
      M.trigger('guardo');
      $M('[data-js-salir]').attr('data-guardado',1);
      $M('[data-js-mensaje-salida]').hide();
      AUX.mensajeExito('Relevamiento guardado');
    });
  });
  
  $M('[data-js-finalizar-carga]').click(function(e){
    cargarRelevamiento(3,function() {
      M.trigger('finalizo');
      M.modal('hide');
    });
  });
  
  function cargarRelevamiento(estado,success) {
    const formData = AUX.form_entries($M('form')[0]);
    formData.estado = estado;
    
    AUX.POST('relevamientos/cargarRelevamiento',formData,
      success,
      function (data) {
        const response = data.responseJSON;
        AUX.mostrarErroresNames(M,response ?? {});
        
        let filaError = null;
        $M('[data-js-tabla-relevamiento] tbody tr').each(function(obj,idx){
          for(let c=1;c<=CONTADORES;c++){
            const err = response['detalles.'+ idx +'.cont'+c];
            if(typeof err !== 'undefined'){
              const cont = $(this).find(`[data-js-cambio-contador="${c}"]`);
              mostrarErrorValidacion(cont,err.join(', '),false);
              filaError = $(this);
            }
          }
        });

        if(filaError !== null){
          M.animate({ scrollTop: filaError.offset().top }, "slow");
        }
        else if(Object.keys(response).length > 0){
          M.animate({ scrollTop: 0 }, "slow");
        }
      }
    );
  }
  
  function habilitarBotonFinalizar(){
    const no_finalizable = !!$M('[data-js-finalizar-carga]').attr('data-no-mostrar');
    if(no_finalizable) return;
    
    let puedeFinalizar = true;
    const cantidadMaquinas = $M('[data-js-tabla-relevamiento] tbody tr').each(function(idx,fila){ 
      let inputLleno = false;
      
      //La fila tiene algun campo lleno
      $(fila).find('[data-js-cambio-contador]').not('[readonly]').each(function (idx,c){
        inputLleno = inputLleno || ($(c).val().length > 0);
        if(inputLleno) return false;//break
      });

      //Seleccionó un tipo de no toma
      const noToma = $(fila).find('[data-js-cambio-tipo-causa-no-toma]').val() !== '';
      
      puedeFinalizar = puedeFinalizar && (inputLleno || noToma);
      if(!puedeFinalizar) return false;//break
    });
    
    $M('[data-js-finalizar-carga]').toggle(puedeFinalizar);
  }
  
  //CAMBIOS EN TABLAS RELEVAMIENTOS / MOSTRAR BOTÓN GUARDAR
  //@TODO: bindear directamente para que sea mas rapido
  M.on('change','input,select,textarea,.form-control',function(e){
    $M('[data-js-salir]').attr('data-guardado',0);
    $M('[data-js-mensaje-salida]').hide();
  });
  
  //@TODO: asignar directamente el event handler
  M.on('change','[data-js-tabla-relevamiento] [data-js-cambio-tipo-causa-no-toma]',function(){   
    habilitarBotonFinalizar();
    
    const fila = $(this).closest('tr');
    //Si se elige algun tipo de no toma se vacian las cargas de contadores
    fila.find('[data-js-icono-estado]').hide();
    if($(this).val() != ''){//Se cambia el icono de diferencia
      fila.find('[data-js-cambio-contador]').val('');
      fila.find('[data-js-icono-estado="icono_no_toma"]').show();
    }
    else{
      fila.find('[data-js-cambio-contador]').eq(0).trigger('input');//Calcular diferencia
    }
  });
  
  //@TODO: asignar directamente el event handler
  M.on('input', "[data-js-tabla-relevamiento] [data-js-cambio-contador]", function(){
    habilitarBotonFinalizar();
    const fila = $(this).closest('tr');
    //Fijarse si se habilita o deshabilita el tipo no toma
    if($(this).val() != '') fila.find('[data-js-cambio-tipo-causa-no-toma]').val('');
    calcularEstadoDetalleRelevamiento([fila.attr('data-id_detalle_relevamiento')]);
  });
  
  M.on('click','[data-js-cancelar-ajuste]',function(e){
    M.find('[data-js-boton-medida]').popover('hide');
  });
  
  M.on('click','[data-js-ajustar]',function(e){
    const id_unidad_medida = $(this).siblings('input:checked').val();
    const fila   = $(this).closest('tr');
    
    let deno = fila.attr('data-denominacion');
    if(id_unidad_medida != 1){//@TODO: rechequear esta logica??? si esta en pesos reasigna la denominacion?
      deno = (deno ?? '') == ''? 0.01 : deno;
    }
    
    AUX.POST('relevamientos/modificarDenominacionYUnidad',
      {
        id_detalle_relevamiento: fila.find(dname_f('id_detalle_relevamiento')).val(),
        id_unidad_medida: id_unidad_medida,
        denominacion: deno,
      },
      function(data){
        M.find('[data-js-boton-medida]').popover('hide');
        M.trigger('mostrar',['Validar',$M('[name="id_relevamiento"]').val()]);
      },
      function(error){
        console.log('Error de cambio denominacion: ', error);
        AUX.mensajeError('');
      },
    );
  });
  
  $M('[data-js-finalizar-validacion]').click(function(e){
    const formData = AUX.form_entries($M('form')[0]);
    formData.truncadas = $M('[data-js-tabla-relevamiento]').find('[data-js-icono-estado="icono_truncado"]').length;
    
    AUX.POST('relevamientos/validarRelevamiento',formData,
      function(data){
        M.trigger('valido');
      },
      function (data){
        AUX.mensajeError('');
      }
    );
  });
})});
