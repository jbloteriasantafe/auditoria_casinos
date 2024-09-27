import "/js/Components/FiltroTabla.js";
import "/js/Components/inputFecha.js";
import "/js/Components/modal.js";
import {AUX} from "/js/Components/AUX.js";

$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Canon');
  
  $('[data-js-modal-ver-cargar-canon]').each(function(_,m_obj){
    const M = $(m_obj);
            
    const fillValue = function(keys,val){
      let compound_k = keys[0] ?? '';
      for(const kidx in keys){
        if(kidx == 0) continue;
        compound_k += '['+keys[kidx]+']';
      }
      M.find(`[name="${compound_k}"]`).val(val);
    }
    
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
        
    const setReadonly = function(){
      const modo = M.attr('data-modo');
      const es_antiguo = M.find('[name="es_antiguo"]').val();
      
      M.find('[data-readonly]:not([data-js-fecha])').each(function(_,r_obj){
        const r = $(r_obj);
        const f = r.children('[data-js-fecha]');
        if(f.length){
          f[0].readonly(false);  
        }
        else{
          r.removeAttr('readonly');
        }
      }).filter(function(_,r_obj){
        let json_rdata = null;
        try{
          json_rdata = JSON.parse($(r_obj).attr('data-readonly'));
        }
        catch(error){
          console.log(r_obj,json_rdata);
          throw error;
        }
        
        if(!Array.isArray(json_rdata)){
          console.log(r_obj,json_rdata);
          throw 'Valor inesperado de "'+$(r_obj).attr('data-readonly')+'" se esperaba un arreglo de objetos';
        }
        for(const obj of json_rdata){
          if(typeof obj !== 'object'){
            console.log(r_obj,obj);
            throw 'Valor inesperado de "'+$(r_obj).attr('data-readonly')+'" se esperaba un arreglo de objetos';
          }
          const obj_modo = obj.modo ?? '*';
          const obj_es_antiguo = obj.es_antiguo ?? '*';
          if(obj_modo == '*' && obj_es_antiguo == '*') return true;
          if(obj_modo == '*' && obj_es_antiguo == es_antiguo) return true;
          if(obj_modo == modo && obj_es_antiguo == '*') return true;
          if(obj_modo == modo && obj_es_antiguo == es_antiguo) return true;
        }
        return false;
      }).each(function(_,r_obj){
        const r = $(r_obj);
        const f = r.children('[data-js-fecha]');
        if(f.length){
          f[0].readonly(true); 
        }
        else{
          r.attr('readonly',true);
        }
      });
    }
    
    const mostrarSegunModo = function(){
      const modo = M.attr('data-modo');
      M.find('[data-modo-mostrar]').hide().filter(function(_,mobj){
        return $(mobj).attr('data-modo-mostrar').toUpperCase().split(',').includes(modo);
      }).show();
    }
    
    const render = function(canon,mantener_historial = false){
      const form = M.find('form[data-js-recalcular]');
      const rerender = M.attr('data-render');
      
      if((rerender ?? 1) == 0){
        fill(null,canon);
        setReadonly();
        return;
      }
      
      const llenarPestaña = function(pestaña,tipos_obj){
        pestaña.find('[data-js-contenedor]').empty();
        let lleno = false;
        for(const tipo in tipos_obj){
          lleno = true;
          const div = pestaña.find('[data-js-molde]').clone();
          const replace_str_tipo = div.attr('data-js-molde');
          div.removeAttr('data-js-molde').show();
          
          div.find('[data-titulo]').text(tipo.toUpperCase());
          
          div.find('[data-name]').each(function(_,nobj){
            const n = $(nobj);
            n.attr('name',n.attr('data-name').replaceAll(replace_str_tipo,tipo));
          });
          
          div.find('[data-depende]').each(function(_,nobj){
            const n = $(nobj);
            n.attr('data-depende',n.attr('data-depende').replaceAll(replace_str_tipo,tipo));
          });
          
          pestaña.find('[data-js-contenedor]').append(div);
        }
        
        //@HACK: no mostrar la pestaña si no tiene nada
        M.find('[data-js-tabs] [data-js-tab]').filter(function(_,tab_obj){
          return $($(tab_obj).attr('data-js-tab'))?.[0] == pestaña[0];
        }).toggle(lleno);
        pestaña.toggle(lleno);
      }
      
      llenarPestaña(form.find('[data-canon-variable]'),canon?.canon_variable ?? {});
      llenarPestaña(form.find('[data-canon-fijo-mesas]'),canon?.canon_fijo_mesas ?? {});
      llenarPestaña(form.find('[data-canon-fijo-mesas-adicionales]'),canon?.canon_fijo_mesas_adicionales ?? {});
            
      form.find('[data-js-fecha]').trigger('init.fecha');
      
      M.attr('data-render',0);
      fill(null,canon);
      setReadonly();
      
      (mantener_historial?
         M.find('[data-js-select-historial]')
       : M.find('[data-js-select-historial]').empty())
       .append(
        (canon?.historial ?? []).map(function(h,hidx){
          const o = $('<option>');
          o.val(h.id_canon);
          o.text(h.usuario + ' - '+h.created_at);
          o.data('canon',h);
          return o;
        })
      );
      
      M.find('[data-js-tabs] [data-js-tab]').filter(function(_,t_obj){
        return $(t_obj).css('display') != 'none';
      }).eq(0).click();
    };
    
    
    M.find('[data-js-select-historial]').change(function(e){
      const tgt = $(e.currentTarget);
      M.attr('data-render',1);
      render(tgt.find('option:selected').data('canon'),true);
    });
    
    M.on('mostrar.modal',function(e,url,id_canon,modo){      
      M.attr('data-modo',modo.toUpperCase());
      mostrarSegunModo();
      const form = M.find('form[data-js-recalcular]');
      form.find('[name]').val('');
      AUX.GET(url,{id_canon: id_canon},function(canon){       
        if(canon !== null){
          M.attr('data-render',1);
          render(canon);
        }
        
        if(M.is(':hidden')){
          M.modal('show');
        }
      });
    });
    
    //Importa el orden con 'recalcular', se llama antes
    M.find('form[data-js-recalcular] [data-js-empty-si-cambio]').change(function(e){
      const tgt = $(e.currentTarget);
      const contenedores = $(tgt.attr('data-js-empty-si-cambio'));
      contenedores.empty();
      M.attr('data-render',1);
    });
    
    M.find('form[data-js-recalcular]').on('recalcular',function(e){
      const form = $(e.currentTarget);
      AUX.POST(form.attr('data-js-recalcular'),AUX.form_entries(form[0]),function(data){
        render(data);
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
    
    M.find('form[data-js-recalcular]').on('focus','select[readonly],.data-css-deshabilitar-cursor',function(e){
      const tgt = $(e.currentTarget);
      const form  = tgt.closest('form[data-js-recalcular]');
      let focusidx = null;
      const focusables = form.find('[name]:visible,[tabindex]:visible');
      focusables.each(function(fidx,f){
        if(f == e.currentTarget){
          focusidx = fidx;
          return false;//Break
        }
      });
      if(focusidx !== null && focusables.length > 1){
        focusables.eq((focusidx+1)%focusables.length).focus();
      }
      else {
        tgt.blur();
      }
    });
    
    M.find('form[data-js-recalcular]').on('keypress','a:focus',function(e){
      if(e.which == 13){//Enter
        $(e.currentTarget).click();
      }
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
    
    M.find('[data-js-enviar]').click(function(e){
      const tgt = $(e.currentTarget);
      const url = tgt.attr('data-js-enviar');
      const fd = AUX.form_entries(M.find('form[data-js-recalcular]')[0]);
      AUX.POST(url,fd,
        function(data){
          AUX.mensajeExito('Guardado');
          M.modal('hide');
          $('#pant_canon').find('[data-js-filtro-tabla]').trigger('buscar');
        },
        function(data){
          AUX.mensajeError('');
        }
      );
    });
    
  });
    
  $('[data-js-tabs]').each(function(_,tab_group_obj){
    const tab_group = $(tab_group_obj);
    tab_group.find('[data-js-tab]').each(function(__,tobj){
      const tab = $(tobj);
      const target = $(tab.attr('data-js-tab'));
      tab.on('mostrar.tab',function(e,mostrar){
        tab.toggleClass('active',mostrar);
        target.toggle(mostrar);  
      }).on('click',function(e){
        tab_group.find('[data-js-tab]').trigger('mostrar.tab',false);
        tab.trigger('mostrar.tab',true);
      });
    }).eq(0).click();
  });
    
  $('#pant_canon,#pant_defecto').each(function(_,pant_obj){
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
      tbody.find('[data-js-trigger-evento-al-insertar]').each(function(_,obj){
        const o = $(obj);
        o.trigger(o.attr('data-js-trigger-evento-al-insertar'));
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
     
  $('#pant_canon [data-js-nuevo-canon]').click(function(e){
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-nuevo-canon'),null,'NUEVO']);
  });
  
  $('#pant_canon').on('click','[data-js-editar]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-editar'),tgt.val(),'EDITAR']);
  });
  
  $('#pant_canon').on('click','[data-js-ver]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-ver'),tgt.val(),'VER']);
  });
  
  $('#pant_defecto').on('jsoneditor.crear','[data-js-jsoneditor]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    const json = e.currentTarget.innerHTML;
    tgt.empty();
    const jsoneditor = new JSONEditor(e.currentTarget, {mode: 'code',modes: ['tree','view','form','code','text','preview']});
    jsoneditor.set(JSON.parse(json));
    tgt.data('jsoneditor',jsoneditor);
  });
  
  $('#pant_defecto').on('click','[data-js-guardar]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    const url = tgt.attr('data-js-guardar');
    const fila = tgt.closest('tr');
    AUX.POST(
      url,
      {
        campo: fila.find('.campo')[0].innerHTML,
        valor: fila.find('.valor').data('jsoneditor').getText(),
      },
      function(data){
        AUX.mensajeExito('Guardado');
        tgt.closest('[data-js-filtro-tabla]').trigger('buscar');
      },
      function(data){
        AUX.mensajeError(JSON.stringify(data?.responseJSON ?? '{}'));
      } 
    );
  });
  
  $('[data-js-filtro-tabla]').trigger('buscar');
});
