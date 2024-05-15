import '/js/Components/inputFecha.js';
import '/js/Components/modal.js';
import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-modal-cargar-relevamiento]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  const dname_f = function(s){return `[data-js-detalle-asignar-name="${s}"]`;};
  
  function cargarTablaRelevamientos(data, tabla, estadoRelevamiento){
    data.detalles.forEach(function(d,didx){
      const fila = $M('[ data-js-molde-tabla-relevamiento]').clone().removeAttr('data-js-molde-tabla-relevamiento')
      .attr('data-medida', d.unidad_medida.id_unidad_medida)//Unidad de medida: 1-Crédito, 2-Pesos 
      .attr('data-denominacion', d.denominacion)//Denominación: para créditos
      .attr('data-id-maquina',d.detalle.id_maquina)
      .attr('data-id-detalle-relevamiento',d.detalle.id_detalle_relevamiento);
      
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
      
      fila.find(dname_f('producido_calculado')).val(d.detalle.producido_calculado_relevado ?? '');
      fila.find(dname_f('producido')).val(d.producido ?? '');
      fila.find(dname_f('id_tipo_causa_no_toma')).val(d.tipo_causa_no_toma ?? '');
      fila.find(dname_f('denominacion')).val(d.unidad_medida.id_unidad_medida == 1? d.denominacion : 1);
            
      fila.find('[data-js-boton-medida]')
      .filter(`[data-medida!=${d.unidad_medida.id_unidad_medida}]`).remove();
      
      fila.find('.estadisticas_no_toma').attr('data-maquina',d.detalle.id_maquina);//@TODO
      
      if(estadoRelevamiento == 'Cargar'){
      }
      else if(estadoRelevamiento == 'Validar'){
        const tipo_causa_no_toma = d.tipo_causa_no_toma;
        const diferencia = d.detalle.diferencia;
        const mostrar_botones = tipo_causa_no_toma != null || diferencia == null || (diferencia != 0 && ( diferencia%1000000 != 0));
        
        if(!mostrar_botones){
          fila.find('[data-js-boton-medida],[data-js-estadisticas-no-toma]').replaceWith('&nbsp;');
        }
      
        fila.find('input').each(function(){$(this).attr('title',$(this).val());});
          
        if (tipo_causa_no_toma != null) {
          fila.find(dname_f('id_tipo_causa_no_toma')).css('border','2px solid #1E90FF').css('color','#1E90FF');
        }
        
        //@TODO: modularizar
        const diff = Math.abs(Number(d.detalle.producido_calculado_relevado - d.producido).toFixed(2));
        fila.find(dname_f('diferencia')).val(diff);
        fila.find('[data-js-icono-estado]').hide();
        if(tipo_causa_no_toma != null){
          fila.find('[data-js-icono-estado="icono_no_toma"]').show();
          fila.find(dname_f('diferencia')).css('border',' 2px solid #EF5350').css('color','#EF5350');
        }
        else if(d.detalle.producido_calculado_relevado != null && (diff >= 1000000) && ((diff % 1000000) == 0)){
          fila.find('[data-js-icono-estado="icono_truncado"]').show()
          fila.find(dname_f('diferencia')).css('border','2px solid #FFA726').css('color','#FFA726');
        }
        else if(d.detalle.producido_calculado_relevado == null || diff != 0){
          fila.find('[data-js-icono-estado="icono_incorrecto"]').show();
          fila.find(dname_f('diferencia')).css('border',' 2px solid #EF5350').css('color','#EF5350');
        }
        else {
          fila.find('[data-js-icono-estado="icono_correcto"]').show();
          fila.find(dname_f('diferencia')).css('border','2px solid #66BB6A').css('color','#66BB6A');
        }
        
      }
      else if(estadoRelevamiento == 'Ver'){
        fila.find('input').each(function(idx,obj){
          const val = $(obj).val().length == 0? '--' : $(obj).val();
          $(obj).replaceWith(val);
        });
        fila.find('select').each(function(idx,obj){
          const val = $(obj).find('option:selected').text().length == 0? '--' : $(obj).find('option:selected').text();
          $(obj).replaceWith(val);
        });
      }
      
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
    
    tabla.find('[data-js-estadisticas-no-toma]').click(function (){
      window.open('/relevamientos/estadisticas_no_toma/'+$(this).closest('tr').attr('data-id-maquina'),'_blank');
    });
    
    tabla.find('tr').each(function(idx,obj){
      $(obj).find('[data-js-cambio-tipo-causa-no-toma]').trigger('change');
      $(obj).find('[data-js-cambio-contador]').eq(0).trigger('input');
    });
  }
  
  M.on('mostrar',function(e,estadoRelevamiento,id_relevamiento){
    const checkCSV = function(attr){
      return function(idx,obj){
        return $(obj).attr(attr).split(',').includes(estadoRelevamiento);
      };
    };
    $M('[name]').val('').change();
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
      $M('[name="usuario_cargador"]').val(data?.usuario_cargador?.nombre ?? data.usuario_actual.usuario.nombre);
      
      $M('[data-js-input-usuario-fiscalizador]').generarDataList('relevamientos/buscarUsuariosPorNombreYCasino/'+ data.id_casino,'usuarios','id_usuario','nombre',2);
      $M('[data-js-input-usuario-fiscalizador]').setearElementoSeleccionado(data?.usuario_fiscalizador?.id_usuario ?? 0,data?.usuario_fiscalizador?.nombre ?? "");
      $M('[data-js-input-usuario-fiscalizador]').change();
      
      $M('[name="observacion_carga"]').val(data?.relevamiento?.observacion_carga ?? '');
      $M('[name="observacion_validacion"]').val(data?.relevamiento?.observacion_validacion ?? '');
    
      cargarTablaRelevamientos(data,tbody,estadoRelevamiento);
      
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
  
  function calcularProducido(fila){
    let suma = 0;
    let inputValido = false;
    
    for(let c=1;c<=CONTADORES;c++){
      const cont_anterior = fila.find(dname_f('cont'+(c-1)));
      const cont = fila.find(dname_f('cont'+c));
      
      const operador = cont_anterior.attr('data-operador');
      const formula = cont.attr('data-formula');
      const cont_val = cont.val();
      const contador = cont_val.length > 0? parseFloat(cont_val.replace(/,/g,".")) : 0;
      
      inputValido = inputValido || (cont_val.length > 0);
      
      if(formula != ''){
        if(c == 1){//El primer contador no tiene operador (el signo esta bakeado en el numero)
          suma = contador;
        }
        else{
          if(operador == '+') suma += contador;
          else                suma -= contador;
        }
      }
    }
    
    const denominacion = fila.attr('data-medida') == 1? fila.attr('data-denominacion') : 1;
    return [Number((suma * denominacion)),inputValido];
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
    
    const [producido_calc,inputValido] = calcularProducido(fila);
    fila.find(dname_f('producido_calculado')).val(producido_calc);
    
    fila.find('[data-js-icono-estado]').hide();
    let producido = fila.find(dname_f('producido')).val();
    if (producido == ''){
      return fila.find('[data-js-icono-estado="icono_no_importado"]').show();
    }

    producido = parseFloat(producido);
    const diferencia = Number(producido_calc.toFixed(2)) - Number(Number(producido).toFixed(2));
    const diferencia_redondeada = Number(diferencia.toFixed(2));

    if (diferencia_redondeada == 0 && inputValido) {
      fila.find('[data-js-icono-estado="icono_correcto"]').show();
    }
    else if(Math.abs(diferencia_redondeada) > 1 && diferencia_redondeada%1000000 == 0 && inputValido) { //El caso de que no haya diferencia ignorando la unidad del millon (en pesos)
      fila.find('[data-js-icono-estado="icono_truncado"]').show();
    } 
    else {
      fila.find('[data-js-icono-estado="icono_incorrecto"]').show();
    }
    
  });
  
  M.on('click','[data-js-cancelar-ajuste]',function(e){
    M.find('[data-js-boton-medida]').popover('hide');
  });
  
  M.on('click','[data-js-ajustar]',function(e){
    const medida_es_credito = $(this).siblings('input:checked').val() == 'credito';
    const fila   = $(this).closest('tr');
    
    let deno = fila.attr('data-denominacion');
    if(!medida_es_credito){
      deno = (deno ?? '') == ''? 0.01 : deno;
    }
    
    AUX.POST('relevamientos/modificarDenominacionYUnidad',
      {
        id_detalle_relevamiento: fila.attr('data-id-detalle-relevamiento'),
        id_unidad_medida:  medida_es_credito? 1 : 2,
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
