import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import '/js/Components/modalEliminar.js';
import {AUX} from "/js/Components/AUX.js";
import '/js/Components/cambioCasinoSelectSectores.js';
import '/js/Components/listasAutocompletar.js';

$(function(){
  
$('.tituloSeccionPantalla').text('Layout Parcial');

$('[data-js-cambio-casino-select-sectores]')
.trigger('set_url',['layout_parcial/obtenerSectoresPorCasino'])
.trigger('change');
 
const csvCheck = function(attr,str){
  return function(idx,obj){
    return $(obj).attr(attr).split(',').includes(str+'');
  };
};

$('[data-js-filtro-tabla]').each(function(idx,fObj){ $(fObj).on('busqueda',function(e,ret,tbody,molde){  
  ret.data.forEach(function(r){
    const fila = molde.clone();
    fila.find('.fecha').text(r.fecha);
    fila.find('.casino').text(r.casino);
    fila.find('.sector').text(r.sector);
    fila.find('.subrelevamiento').text(r.sub_control ?? '');
    fila.find('button').val(r.id_layout_parcial);
    fila.find('[data-id_estado_relevamiento]').hide()
    .filter(csvCheck('data-id_estado_relevamiento',r.id_estado_relevamiento))
    .show();
    tbody.append(fila);
  });
  tbody.find('[data-js-planilla]').click(function(e){
    const tgt = $(e.currentTarget);
    window.open(tgt.attr('data-js-planilla')+'/'+tgt.val(),'_blank');
  });
  tbody.find('[data-js-abrir-modal]').click(function(e){
    const boton = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-validar-layout-parcial]').trigger('mostrar',[boton.val(),boton.attr('data-js-abrir-modal')]);
  });
}).trigger('buscar'); });
  
$('#btn-nuevoLayoutParcial').click(function(e){
  e.preventDefault();
  $('[data-js-modal-layout-parcial]').trigger('mostrar');
});

$('#btn-layoutSinSistema').click(function(e){
  e.preventDefault();
  $('[data-js-modal-layout-parcial-sin-sistema]').trigger('mostrar');
});

$('[data-js-modal-layout-parcial]').each(function(idx,Mobj){
  const M = $(Mobj);
  
  M.on('mostrar',function(e){
    M.find('[data-js-icono-carga]').hide();
    ocultarErrorValidacion(M.find('[name]').each(function(idx,obj){
      $(obj).val($(obj).attr('data-default') ?? '');
    }).change());
    M.modal('show');
  });
  
  M.find('[data-js-generar]').click(function(e){
    e.preventDefault();
    
    const formData = AUX.form_entries(M.find('form')[0]);
    ocultarErrorValidacion(M.find('[name]'));
    M.find('[data-js-icono-carga]').show();
    
    AUX.POST('layout_parcial/crearLayoutParcial',formData,
      function(data){
        M.trigger('success');
        M.find('[data-js-icono-carga]').hide();
        
        if(data.nombre_zip !== undefined){
          let iframe = document.getElementById("download-container");
          if (iframe === null){
            iframe = document.createElement('iframe');
            iframe.id = "download-container";
            iframe.style.visibility = 'hidden';
            document.body.appendChild(iframe);
          }
          iframe.src = '/layout_parcial/descargarLayoutParcialZip/' + data.nombre_zip;
          AUX.mensajeExito('Layout Parcial creado');
          M.modal('hide');
        }
      },
      function(data){
        M.find('[data-js-icono-carga]').hide();
        const errores = data.responseJSON ?? {};
        AUX.mostrarErroresNames(M,errores);
        if(errores.layout_en_carga){
          AUX.mensajeError(errores.layout_en_carga.join('; '));
        }
      }
    );
  });
});

$('[data-js-modal-layout-parcial-sin-sistema]').each(function(idx,Mobj){
  const M = $(Mobj);
  
  M.on('mostrar',function(e){
    M.find('[data-js-icono-carga]').hide();
    ocultarErrorValidacion(M.find('[name]').val(''));
    M.find('[data-js-fecha]').each(function(idx,fObj){
      $(fObj).data('datetimepicker').reset();
    });
    M.modal('show');
  });
  
  M.find('[data-js-usar-relevamiento-backup]').click(function(e){
    e.preventDefault();
    
    const formData = AUX.form_entries(M.find('form')[0]);
    ocultarErrorValidacion(M.find('[name]'));
    M.find('[data-js-icono-carga]').show();
    
    AUX.POST('layout_parcial/usarLayoutBackup',formData,
      function(data){
        M.find('[data-js-icono-carga]').hide();
        M.trigger('success');
        $('[data-js-filtro-tabla]').trigger('buscar');
        AUX.mensajeExito('Layout Parcial de backup habilitado');
        M.modal('hide');
      },
      function(data){
        M.find('[data-js-icono-carga]').hide();
        AUX.mostrarErroresNames(M,data.responseJSON ?? {});
      }
    );
  });
});

$('[data-js-modal-ver-cargar-validar-layout-parcial]').each(function(mIdx,mObj){
  const M = $(mObj);  
 
  M.on('mostrar',function(e,id_layout_parcial,modo){
    M.attr('data-css-modo',modo);
    M.find('[name="id_layout_parcial"]').val(id_layout_parcial);
    ocultarErrorValidacion(M.find('[name]'));
    
    AUX.GET('layout_parcial/obtenerLayoutParcial/'+id_layout_parcial,{},function(data){
      const tbody = M.find('[data-js-tabla-relevado] tbody').empty();
      const molde = M.find('[data-js-molde-relevado]').clone().removeAttr('data-js-molde-relevado');
      
      data.detalles.forEach(function(d,didx){
        const fila = molde.clone();
        fila.find('[data-dyn-name]').each(function(nidx,nobj){
          const o = $(nobj);
          const name = o.attr('data-dyn-name');
          const dname = d[name];
          const valor = (typeof dname == 'object'?
            dname?.valor : dname
          ) ?? '';
          const valor_antiguo = (typeof dname == 'object'?
            dname?.valor_antiguo : dname
          ) ?? '';
          
          o.val(valor);
          o.attr('name',`detalles[${didx}][${name}]`);
          if(o.is('[data-js-editable-original]')){
            o.attr('data-js-editable-original',valor_antiguo.length? valor_antiguo : valor);
            
            if(o.attr('data-js-editable-original') == o.val()){
              o.attr('readonly',true);
            }
          }
        });
        
        fila.find('[data-js-cambio-asignar-val]').prop('checked',d.no_toma);
        tbody.append(fila);
      });
      
      tbody.find('[data-js-cambio-asignar-val]').change(function(e){
        const check = $(e.currentTarget);
        const input = check.closest('tr').find(check.attr('data-js-cambio-asignar-val'));
        input.val(check.prop('checked') ?? false);
      }).change();
      
      tbody.find('[data-js-cambio-limpiar]').change(function(e){
        const check = $(e.currentTarget);
        const input = check.closest('tr').find(check.attr('data-js-cambio-limpiar'));
        if(check.prop('checked')){
          input.val('');
        }
      }).change();
      
      tbody.find('[data-js-editable-original]').on('dblclick',function(e){
        const obj = $(e.currentTarget);
        if(obj.attr('readonly')){
          obj.removeAttr('readonly');
        }
        else {
          obj.attr('readonly',true);
        }
      });
      
      const habilitar = function(estado){
        return function(idx,obj){
          if($(obj).is('[data-js-fecha]')){
            obj.disabled(estado);
          }
          else{
            if(estado) $(obj).attr('disabled',true);
            else       $(obj).removeAttr('disabled');
          }
        };
      };
      
      M.find('[data-js-modo-habilitar]')
      .each(habilitar(true))
      .filter(csvCheck('data-js-modo-habilitar',modo))
      .each(habilitar(false));
      
      M.find('[data-js-modo-ver]').hide()
      .filter(csvCheck('data-js-modo-ver',modo))
      .show();
      
      M.find('[name="fecha"]').val(data?.layout_parcial?.fecha ?? '');
      M.find('[name="fecha_generacion"]').val(data?.layout_parcial?.fecha_generacion ?? '');
      M.find('[name="casino"]').val(data?.casino ?? '');
      M.find('[name="sector"]').val(data?.sector ?? '');
      M.find('[name="subrelevamiento"]').val(data?.layout_parcial?.fecha ?? '');
      M.find('[name="fiscalizador_carga_recibido"]').val(data?.usuario_cargador?.nombre ?? '');
      M.find('[name="fiscalizador_toma"]').val(data?.usuario_fiscalizador?.nombre ?? '');
      M.find('[name="tecnico"]').val(data?.layout_parcial?.tecnico ?? '');
      M.find('[name="observacion_fiscalizacion"]').val(data?.layout_parcial?.observacion_fiscalizacion ?? '');
      M.find('[name="observacion_validacion"]').val(data?.layout_parcial?.observacion_validacion ?? '');
      M.find('[name="id_casino"]').val(data?.id_casino).trigger('change');
      
      const dtp_fecha_ejecucion = M.find('[name="fecha_ejecucion"]').closest('[data-js-fecha]').data('datetimepicker');
      dtp_fecha_ejecucion.reset();
      if(data?.layout_parcial?.fecha_ejecucion){
        dtp_fecha_ejecucion.setDate(new Date(data.layout_parcial.fecha_ejecucion));
      }
      
      M.modal('show');
    });
  });
  
  M.find('[data-js-selecciono-id-fiscalizador]').on('seleccionado',function(e,val,id){
    const fiscalizador_toma = M.find($(e.currentTarget).attr('data-js-selecciono-id-fiscalizador'));
    fiscalizador_toma.attr('data-css-seleccion-correcta',(id !== null)+0);
  });
  
  M.find('[data-js-enviar-form]').click(function(e){
    AUX.POST(
      $(e.currentTarget).attr('data-js-enviar-form'),
      AUX.form_entries(M.find('form')[0]),
      function(data){
        M.trigger('success');
        M.modal('hide');
      },
      function(data){
        const errores = data.responseJSON ?? {};
        AUX.mostrarErroresNames(M.find('form'),errores,true);
        if(errores.id_fiscalizador_toma){//@HACK leacky abstraction
          mostrarErrorValidacion(M.find('[name="fiscalizador_toma"]'),errores.id_fiscalizador_toma.join(', '),true);
        }
        M.find('[data-js-tabla-relevado] tbody tr').each(function(tridx,tr){//@HACK leacky abstraction
          $(tr).find('[data-dyn-name]').each(function(nidx,named){
            const name = $(named).attr('data-dyn-name');
            const err  = errores[`detalles.${tridx}.${name}`];
            if(err){
              mostrarErrorValidacion($(named),err.join(', '),false);
            }
          });
        });
      }
    );
  });
});

//Enlazar modales con busqueda
$('[data-js-modal-ver-cargar-validar-layout-parcial],[data-js-modal-layout-parcial],[data-js-modal-layout-parcial-sin-sistema]').on('success',function(e){
  $('[data-js-filtro-tabla]').trigger('buscar');
});

});
