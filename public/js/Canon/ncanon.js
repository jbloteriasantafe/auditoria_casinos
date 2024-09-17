import "/js/Components/FiltroTabla.js";
import "/js/Components/inputFecha.js";
import "/js/Components/modal.js";
import {AUX} from "/js/Components/AUX.js";

$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Canon');
  
  $('[data-js-modal-ver-cargar-canon]').each(function(_,m_obj){
    const M = $(m_obj);
    let rerender_html = null;
    
    M.on('mostrar',function(e,año_mes = null,id_casino = null){
      const form = M.find('form[data-js-recalcular]');
      form.find('[name]:not([readonly]):not([data-depende])').val('').eq(0);
      form.find('[name="año_mes"]').val(año_mes);
      //@HACK: trigger limpiar canon variable, canon fijo mesas adicionales y recalcular
      rerender_html = true;
      form.find('[name="id_casino"]').val(id_casino).change();
      M.modal('show');
    });
    
    const fillValue = function(keys,val){
      let compound_k = keys[0] ?? '';
      for(const kidx in keys){
        if(kidx == 0) continue;
        compound_k += '['+keys[kidx]+']';
      }
      M.find(`[name="${compound_k}"]`).val(val);
    }
    
    M.find('form[data-js-recalcular] [data-js-cambio-limpiar-canon-variable]').change(function(e){
      const form = $(e.currentTarget).closest('form');
      form.find('[data-js-canon-variable]').empty();
      rerender_html = true;
    });
    
    M.find('form[data-js-recalcular] [data-js-cambio-limpiar-canon-fijo-mesas-adicionales]').change(function(e){
      const form = $(e.currentTarget).closest('form');
      form.find('[data-js-canon-fijo-mesas-adicionales]').empty();
      rerender_html = true;
    });
    
    const fill = function(prefix,obj){
      const subscript = function(s){
        return prefix === null? s : (prefix+'['+s+']');
      };
      for(const k in obj){
        const val = obj[k];
        if(typeof val == 'object'){
          fill(subscript(k),val);
        }
        else{
          const name = subscript(k);
          M.find(`[name="${name}"]`).val(val);
        }
      }
    };
    
    
    M.find('form[data-js-recalcular]').on('recalcular',function(e){
      const form = $(e.currentTarget);
      form.find('[name][readonly]').val('');
      AUX.POST(form.attr('data-js-recalcular'),AUX.form_entries(form[0]),function(data){
        if(rerender_html === true){
          form.find('[data-js-canon-variable]').empty();
          for(const tipo in (data?.canon_variable ?? {})){
            const div = form.find('[data-js-molde-canon-variable]').clone();
            const replace_str_tipo = div.attr('data-js-molde-canon-variable');
            div.removeAttr('data-js-molde-canon-variable').show();
            
            div.find('[data-titulo]').text(tipo.toUpperCase());
            
            div.find('[data-name]').each(function(_,nobj){
              const n = $(nobj);
              n.attr('name',n.attr('data-name').replaceAll(replace_str_tipo,tipo));
            });
            
            div.find('[data-depende]').each(function(_,nobj){
              const n = $(nobj);
              n.attr('data-depende',n.attr('data-depende').replaceAll(replace_str_tipo,tipo));
            });
            
            form.find('[data-js-canon-variable]').append(div);
          }
          
          form.find('[data-js-canon-fijo-mesas-adicionales]').empty();
          for(const ma in (data?.canon_fijo?.mesas_adicionales ?? {})){
            const div = form.find('[data-js-molde-canon-fijo-mesas-adicionales]').clone();
            const replace_str_ma = div.attr('data-js-molde-canon-fijo-mesas-adicionales');
            div.removeAttr('data-js-molde-canon-fijo-mesas-adicionales').show();
            
            div.find('[data-titulo]').text(ma.toUpperCase());
            
            div.find('[data-name]').each(function(_,nobj){
              const n = $(nobj);
              n.attr('name',n.attr('data-name').replaceAll(replace_str_ma,ma));
            });
            
            div.find('[data-depende]').each(function(_,nobj){
              const n = $(nobj);
              n.attr('data-depende',n.attr('data-depende').replaceAll(replace_str_ma,ma));
            });
            
            form.find('[data-js-canon-fijo-mesas-adicionales]').append(div);
          }
          rerender_html = false;
          fill(null,data);
        }
        else if(rerender_html === false){
          fill(null,data);
        }
        else{
          throw 'Inesperado valor rerender_html = '+rerender_html;
        }
      });
    });
    
    M.find('form[data-js-recalcular]').on('change','[name]',function(e){//@TODO: bindear directo
      const tgt = $(e.currentTarget);
      const form = tgt.closest('form[data-js-recalcular]');
      
      const visitados = new Set();
      
      const limpiarDependencias = function(name){
        visitados.add(name);
        form.find('[data-depende]').filter(function(_,dep_obj){
          const dep = $(dep_obj);
          const n = dep.attr('name');
          const lista_nombres = dep.attr('data-depende').split(',');
          return lista_nombres.includes(name) && !visitados.has(n);
        })
        .val('')
        .each(function(_,dep_obj){
          const dep = $(dep_obj);
          const n = dep.attr('name');
          limpiarDependencias(n);
        });
      };
      
      limpiarDependencias(tgt.attr('name'));
      
      form.trigger('recalcular');
    });
    
    M.find('form[data-js-recalcular]').on('mouseenter','[data-depende]',function(e){
      $(e.currentTarget).attr('data-depende').split(',').forEach(function(name){
        M.find('form[data-js-recalcular]').find(`[name="${name}"]`).addClass('mostrar_dependencia');
      });
    });
    M.find('form[data-js-recalcular]').on('mouseleave','[data-depende]',function(e){
      $(e.currentTarget).attr('data-depende').split(',').forEach(function(name){
        M.find('form[data-js-recalcular]').find(`[name="${name}"]`).removeClass('mostrar_dependencia');
      });
    });
  });
    
  $('[data-js-tabs]').each(function(_,tab_group_obj){
    const tab_group = $(tab_group_obj);
    tab_group.find('[data-js-tab]').each(function(__,tobj){
      const tab = $(tobj);
      const target = $(tab.attr('data-js-tab'));
      tab.on('mostrar',function(e,mostrar){
        tab.toggleClass('active',mostrar);
        target.toggle(mostrar);  
      }).on('click',function(e){
        tab_group.find('[data-js-tab]').trigger('mostrar',false);
        tab.trigger('mostrar',true);
      });
    }).eq(0).click();
  });
    
  $('#pant_totales,#pant_maquinas,#pant_bingo,#pant_jol,#pant_mesas,#pant_mesas_adicionales,#pant_defecto,#pant_canon_antiguo').each(function(_,pant_obj){
    const pant = $(pant_obj);
    pant.find('[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
      ret.data.forEach(function(obj){
        const fila = molde.clone();
        Object.keys(obj).forEach(function(k){
          fila.find('.'+k).text(obj[k]);
        });
        const id_k = fila.find('[data-table-id]').attr('data-table-id');
        fila.find('button').val(obj[id_k]);
        tbody.append(fila);
      });
      tbody.find('[data-js-borrar]').click(function(e){
        const tgt = $(e.currentTarget);
        const id = tgt.val();
        const url = tgt.attr('data-js-borrar');
        AUX.DELETE(url,{id: id},
          function(data){
            pant.find('[data-js-filtro-tabla]').trigger('buscar');
          }
        );
      });
    });
    
    pant.find('[data-js-enviar]').click(function(e){
      const tgt = $(e.currentTarget);
      const url = tgt.attr('data-js-enviar');
      AUX.POST(url,AUX.form_entries(tgt.closest('form')[0]),function(data){
        tgt.closest('[data-js-filtro-tabla]').trigger('buscar');
      });
    });
  });
  
  $('#pant_mesas').each(function(_,pant_obj){
    const pant = $(pant_obj);
    
    pant.find('[data-js-recalcular]').click(function(e){
      const tgt = $(e.currentTarget);
      const url = tgt.attr('data-js-recalcular');
      const form = pant.find('[data-js-formulario-ingreso]');
      const fd  = AUX.form_entries(form[0]);
      form.find('[name][readonly]').val('');
      AUX.POST(url,fd,function(data){
        for(const k in data){
          form.find(`[name="${k}"]`).val(data[k] ?? null);
        }
      });
    });
    
    pant.find('[name]').change(function(e){
      const name = $(e.currentTarget).attr('name');
      const dependencias = pant.find('[data-depende]').filter(function(_,d_obj){
        return $(d_obj).attr('data-depende').split(',').includes(name);
      });
      dependencias.each(function(_,d_obj){
        $(d_obj).val('');
      });
      
      pant.find('[data-js-recalcular]').click();
    });
    
    pant.find('[data-js-cambio-asignar-fecha-cotizacion]').change();
  });
  
  
  $('#pant_mesas_adicionales').each(function(_,pant_obj){
    const pant = $(pant_obj);    
    
    pant.find('[data-js-cambio-asignar-valor-hora]').change(function(e){
      const tgt = $(e.currentTarget);
      const valor_hora = tgt.find('option:selected').attr('data-valor-hora') ?? '';
      $(tgt.attr('data-js-cambio-asignar-valor-hora')).val(valor_hora);
    });
    
    pant.find('[data-js-cambio-asignar-porcentaje]').change(function(e){
      const tgt = $(e.currentTarget);
      const valor_hora = tgt.find('option:selected').attr('data-porcentaje') ?? '';
      $(tgt.attr('data-js-cambio-asignar-porcentaje')).val(valor_hora);
    });
        
    pant.find('[data-js-cambio-mostrar-tipos-mesas]').change(function(e){
      const tgt = $(e.currentTarget);
      const destinos = $(tgt.attr('data-js-cambio-mostrar-tipos-mesas'))
      .filter('[data-casino]').hide().removeAttr('name');
      
      const cas = tgt.val();
      destinos.filter(`[data-casino="${cas}"]`).show().change().attr('name','tipo');
    });
    
    pant.find('[data-js-cambio-mostrar-tipos-mesas]').trigger('change');
  });
  
  
  $('#pant_canon_antiguo').each(function(_,pant_obj){
    const pant = $(pant_obj);    
    pant.find('[data-js-cambio-asignar-valor]').change(function(e){
      const tgt = $(e.currentTarget);
      const destino = $(tgt.attr('data-js-cambio-asignar-valor'));
      destino.val(tgt.val()).change();
    });
  });
  
  $('[data-js-filtro-tabla]').trigger('buscar');
});
