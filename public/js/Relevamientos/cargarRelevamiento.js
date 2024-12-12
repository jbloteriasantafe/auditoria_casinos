import '/js/Components/inputFecha.js';
import '/js/Components/modal.js';
import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-modal-cargar-relevamiento]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  const dname_f = function(s){return `[data-js-detalle-asignar-name="${s}"]`;};
  let modo = null;
    
  const calcularEstadoDetalleRelevamiento = function(filas,after = function(){}){
    const idr_to_fila = {};
    const detalles = filas.map(function(idx,obj){
      const fila = $(obj);
      
      const idr = fila.find(dname_f('id_detalle_relevamiento')).val();
      idr_to_fila[idr] = fila;
      
      return {
        id_detalle_relevamiento: idr,
        id_tipo_causa_no_toma: fila.find('[data-js-cambio-tipo-causa-no-toma]').val(),
        ...Object.fromEntries(fila.find('[data-js-cambio-contador]').map(function(idx,cont){
          const name = $(cont).attr('data-js-detalle-asignar-name');
          return [[$(cont).attr('data-js-detalle-asignar-name'),$(cont).val()]];//Doble array porque jquery lo flattea con el toArray()
        }).toArray())
      };
    }).toArray();
    
    AUX.POST('relevamientos/calcularEstadoDetalleRelevamiento',{detalles: detalles},function(estados){
      for(const idr in estados){
        const e    = estados[idr];
        const fila = idr_to_fila[idr];
        
        fila.attr('data-css-colorear',e.estado);
        fila.attr('data-id_unidad_medida',e.id_unidad_medida);
        fila.find(dname_f('producido_importado')).val(e.producido_importado);
        fila.find(dname_f('producido_calculado_relevado')).val(e.producido_calculado_relevado);
        fila.find(dname_f('diferencia')).val(e.diferencia);
        fila.find(dname_f('denominacion')).val(e.denominacion);

        if(fila.find('[data-js-cambio-tipo-causa-no-toma]').val() != ''){
          fila.find('[data-js-cambio-contador]').val('');
          fila.find('[data-contador]').not('[readonly]').attr('disabled',true);
        }
        else{
          fila.find('[data-contador]').not('[readonly]').removeAttr('disabled');
        }
        
        if(modo == 'Validar'){
          fila.find('[data-js-cambio-contador]').each(function(idx,obj){$(obj).attr('title',$(obj).val());})
          .attr('readonly',true);
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
      after();
    });
  }
  
  function cargarTablaRelevamientos(data, tabla){
    const cambioContador = function(e){
      calcularEstadoDetalleRelevamiento($(e.target).closest('tr'));
    };
    const sacarErroresContadores = function(e){
      ocultarErrorValidacion($(e.target).closest('tr').find('[data-js-cambio-contador].alerta'));
    };
    
    data.detalles.forEach(function(d,didx){
      const fila = $M('[ data-js-molde-tabla-relevamiento]').clone().removeAttr('data-js-molde-tabla-relevamiento')
      .attr('data-id_detalle_relevamiento',d.detalle.id_detalle_relevamiento);
      
      fila.find('[data-js-detalle-asignar-name]').each(function(idx,obj){
        const name = $(obj).attr('data-js-detalle-asignar-name');
        $(obj).attr('name',`detalles[${didx}][${name}]`);
      });
      
      fila.find(dname_f('id_detalle_relevamiento')).val(d.detalle.id_detalle_relevamiento);
      fila.find(dname_f('maquina')).text(d.maquina);
      
      for(let c=1;c<=CONTADORES;c++){
        const cont_s = 'cont'+c;
        const cont = fila.find(`[data-js-cambio-contador="${c}"]`).val(d?.detalle?.[cont_s] ?? '')
        .attr('placeholder',d?.formula?.[cont_s] ?? '');
        cont.prop('readonly',!d?.formula?.[cont_s]);
      }
      
      fila.find(dname_f('id_tipo_causa_no_toma')).val(d.detalle.id_tipo_causa_no_toma ?? '');
      fila.find('[data-js-estadisticas-no-toma]').attr('href',fila.find('[data-js-estadisticas-no-toma]').attr('href')+'/'+d.detalle.id_maquina);
            
      tabla.append(fila);
      
      fila.find('[data-js-cambio-tipo-causa-no-toma]').on('change',cambioContador);
      fila.find('[data-js-cambio-contador]').on('keyup',cambioContador);
      fila.find('[data-js-cambio-contador]:not([readonly],[disabled]),[data-js-cambio-tipo-causa-no-toma]').on('focus',sacarErroresContadores);
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
    
    calcularEstadoDetalleRelevamiento(tabla.find('tr'),function(){M.modal('show');});
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
      
      cargarTablaRelevamientos(data,tbody);
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
        const response = data.responseJSON ?? {};
        AUX.mostrarErroresNames(M,response ?? {},true);
        
        if(response.id_usuario_fiscalizador !== undefined){
          mostrarErrorValidacion($M('[data-js-input-usuario-fiscalizador]'),response.id_usuario_fiscalizador.join(', '),true);
        }
        
        let filaError = null;
        $M('[data-js-tabla-relevamiento] tbody tr').each(function(idx,obj){
          for(let c=1;c<=CONTADORES;c++){
            const err = response['detalles.'+ idx +'.cont'+c];
            if(typeof err !== 'undefined'){
              const cont = $(obj).find(`[data-js-cambio-contador="${c}"]`).not('[disabled]').not('[readonly]');
              mostrarErrorValidacion(cont,err.join(', '),false);
              filaError = $(obj);
            }
          }
        });

        if(filaError !== null){
          const div_scrollable = $M('[data-div-tabla-scrollable-errores]');
          div_scrollable.animate({ scrollTop: filaError.offset().top }, "slow");
          M.animate({scrollTop: div_scrollable}, "slow");
        }
        else if(Object.keys(response).length > 0){
          M.animate({ scrollTop: 0 }, "slow");
        }
      }
    );
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
  
  M.on('click','[data-js-ajustar]',function(e){
    M.find('[data-js-boton-medida]').popover('hide');
    
    const id_unidad_medida = $(this).siblings('input:checked').val();
    const fila   = $(this).closest('tr');
    
    AUX.POST('relevamientos/modificarDenominacionYUnidad',
      {
        id_detalle_relevamiento: fila.find(dname_f('id_detalle_relevamiento')).val(),
        id_unidad_medida: id_unidad_medida,
      },
      function(data){
        calcularEstadoDetalleRelevamiento(fila);
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
        const response = data.responseJSON ?? {};
        if(response.faltan_contadores !== undefined){
          AUX.mensajeError(response.faltan_contadores.join(', '));
        }
        else{
          AUX.mensajeError('');
        }
      }
    );
  });
})});
