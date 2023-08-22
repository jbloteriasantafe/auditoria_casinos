import {AUX} from "../CierresAperturas/AUX.js";

$(function(){
  const _M = '[data-js-cargar-modificar-validar]';
  const  M = $(_M);
  const $M = M.find.bind(M);
  
  const cargarFisca = function(id_usuario,nombre){
    const fila = $M('[data-js-molde-fiscalizador]').clone().removeAttr('data-js-molde-fiscalizador');
    fila.find('[name="id_usuario"]')
    .text(nombre).attr('data-id_usuario',id_usuario);
    $M('[data-js-fiscalizadores]').append(fila);
  }
  
  M.on('mostrar',function(e,tipo,id){
    const TIPO = tipo.toUpperCase();
    $M('[data-js-tipo]').text(TIPO);
    M.attr('data-tipo',TIPO);
    
    $M('[data-js-fiscalizador]').borrarDataList();
    $M('[data-js-fecha]').data('datetimepicker').reset();
    $M('[data-js-fiscalizadores]').empty();
    $M('[data-js-mesas] tbody').empty();
    $M('[data-js-mesas-abiertas] tbody').empty();
    ocultarErrorValidacion($M('[name]'));
    $M('[name]').val('');
    $M('[name="id_relevamiento_apuestas"]').val(id);
     
    AUX.GET('apuestas/obtenerRelevamiento/' + id,{}, function(data){
      const id_casino = data.relevamiento.id_casino;
      
      $M('[data-js-fiscalizador]').generarDataList("apuestas/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $M('[data-js-fecha]').data('datetimepicker').setDate(
        new Date(data?.relevamiento?.fecha+'T00:00')
      );
      $M('[data-js-fecha]')[0].disabled(true);
      const hora_propuesta = data?.relevamiento?.hora_propuesta?.split(':')?.slice(0,2)?.join(':');
      $M('[name="hora_propuesta"]').val(hora_propuesta ?? '').prop('readonly',true);
      const hora_ejecucion = data?.relevamiento?.hora_ejecucion?.split(':')?.slice(0,2)?.join(':');
      $M('[name="hora_ejecucion"]').val(hora_ejecucion ?? '');
      $M('[name="id_turno"]').val(data?.turno?.nro_turno ?? '').prop('readonly', true)
      .attr('data-id_turno',data?.turno?.id_turno ?? '');
      
      data?.fiscalizadores?.forEach(function(f){
        cargarFisca(f.id_usuario,f.nombre);
      });
      
      data?.detalles?.forEach(function(d){
        const fila = $M('[data-js-molde-mesa]').clone().removeAttr('data-js-molde-mesa');
        fila.find('.mesa').text(d?.codigo_mesa)
        .attr('data-id_detalle_relevamiento_apuestas',d?.id_detalle_relevamiento_apuestas);
        fila.find('.moneda').val(d?.id_moneda ?? '').attr('disabled',!!d?.id_moneda);
        fila.find('.posiciones').text(d?.posiciones);
        fila.find('.estado').val(d?.id_estado_mesa);
        fila.find('.minimo').val(d?.minimo);
        fila.find('.maximo').val(d?.maximo);
        $M('[data-js-mesas] tbody').append(fila);
      });
      
      data?.abiertas_por_juego?.forEach(function(d){
        const fila = $M('[data-js-molde-mesas-abiertas]').clone().removeAttr('data-js-molde-mesas-abiertas');
        fila.find('.juego').text(d.juego);
        fila.find('.mesas_abiertas').text(d.mesas_abiertas);
        $M('[data-js-mesas-abiertas] tbody').append(fila);
      });
      
      $M('[data-cumplio-minimo]').hide();
      $M(`[data-cumplio-minimo="${data?.cumplio_minimo}"]`).show();
      
      $M('[name="observaciones"]').val(data?.relevamiento?.observaciones);
      $M('[name="observaciones_validacion"]').val(data?.relevamiento?.observaciones_validacion);
      
      $M(`[data-js-habilitar]`).attr('disabled',true).filter(function(){
        return $(this)?.attr('data-js-habilitar')?.split(',')?.includes(TIPO) ?? false;
      }).attr('disabled',false);
      
      $M(`[data-js-mostrar]`).hide().filter(function(){
        return $(this)?.attr('data-js-mostrar')?.split(',')?.includes(TIPO) ?? false;
      }).show();
      
      M.modal('show');
    });
  });
  
  $M('[data-js-click-agregar-fiscalizador]').click(function (e) {
    const id_usuario = $M('[data-js-fiscalizador]').obtenerElementoSeleccionado();
    AUX.GET("apuestas/buscarUsuario/" + id_usuario,{},function(data) {
      cargarFisca(data.usuario.id_usuario,data.usuario.nombre);
      $M('[data-js-fiscalizador]').setearElementoSeleccionado(0 , "");
    });
  });
  
  $(document).on('click',`${_M} [data-js-fiscalizadores] [data-js-borrar-fiscalizador]`,function(e){
    $(this).closest('tr').remove();
  });
  
  $M('[data-js-guardar]').click(function(e){
    const $relevamiento   = $M('[data-js-datos-relevamiento]');
    const $fiscalizadores = $M('[data-js-fiscalizadores] tbody tr');
    const $detalles       = $M('[data-js-mesas] tbody tr');
    
    const formData = {};
    formData.relevamiento = AUX.extraerFormData($relevamiento);
    formData.fiscalizadores = $fiscalizadores.map(function(){
      return AUX.extraerFormData($(this));
    }).toArray();
    formData.detalles       = $detalles.map(function(){
      return AUX.extraerFormData($(this));  
    }).toArray();
    
    AUX.POST('apuestas/cargarRelevamiento',formData,
      function(data){
        AUX.mensajeExito('Relevamiento guardado');
        M.trigger('success');
        M.modal('hide');
      },
      function(data){
        console.log(data);
        const json = data.responseJSON ?? {};
        let err = false;
        let mensajes = [];
        //primer nivel
        if('relevamiento' in json){
          mensajes = mensajes.concat(json['relevamiento']);
          err = true;
        }
        if('fiscalizadores' in json){
          mostrarErrorValidacion($M('[data-js-fiscalizador]'),json['fiscalizadores'].join(', '));
          err = true;
        }
        if('detalles' in json){
          mensajes = mensajes.concat(json['detalles']);
          err = true;
        }
        
        $relevamiento.find('[name]').each(function(_,n){
          const name = $(n).attr('name');
          const k = `relevamiento.${name}`;
          if(k in json){
            err = true;
            mostrarErrorValidacion($(n),json[k].join(', '),true);
          }
        });
        
        //segundo nivel        
        let prefix = '';
        const mostrarError = function(idx,o){
          let errores = 0;
          $(o).find('[name]').each(function(_,n){
            const name = $(n).attr('name');
            const k = `${prefix}.${idx}.${name}`;
            if(k in json){
              err = true;
              mostrarErrorValidacion($(n),json[k].join(', '),true);
              if(errores == 0){
                n.scrollIntoView();
              }
            }
          });
        };
        
        prefix = 'fiscalizadores';
        $fiscalizadores.each(mostrarError);
        
        prefix = 'detalles';
        $detalles.each(mostrarError);
        
        if(err){
          AUX.mensajeError(mensajes.join(', '));
        }
      }
    );
    
    console.log(formData);
  });
  
  $M('[data-js-validar]').click(function(e){
    AUX.POST('apuestas/validar',
      AUX.extraerFormData($M('[data-js-datos-relevamiento]')),
      function(data){
        AUX.mensajeExito('Relevamiento validado');
        M.trigger('success');
        M.modal('hide');
      },
      function(data){
        console.log(data);
        const json = data.responseJSON ?? {};
        AUX.mostrarErroresNames($M('[data-js-datos-relevamiento]'),json);
      }
    );
  });
});
