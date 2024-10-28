import "/js/Components/FiltroTabla.js";
import "/js/Components/inputFecha.js";
import "/js/Components/modal.js";
import {AUX} from "/js/Components/AUX.js";

function formatter(n){
  const negativo = n?.[0] == '-'? '-' : '';
  n = negativo.length? n.substr(1) : n;
  
  const partes = n.split('.');
  let entero  = partes?.[0] ?? '';
  
  entero = entero.split('').reverse().join('')//Doy vuelta el numero... 
  .match(/(.{1,3}|^$)/g).map(function(s){return s.split('').reverse().join('');})//junto los miles y los pongo en orden
  .reverse().join('.');//Lo pongo en orden correcto y lo uno
  
  //Saco los ceros de sobra, y la parte decimal si es solo .000..
  let decimal = (partes?.[1] ?? '').replaceAll(/0+$/g,'')
  if(decimal.length){
    decimal = ','+decimal;
  }
  
  return negativo+entero+decimal;
};

function deformatter(n){
  return n.replaceAll('.','').replaceAll(',','.');
};

$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Canon');
  
  $('[data-js-modal-ver-cargar-canon]').each(function(_,m_obj){
    const M = $(m_obj);
    
    const fill = function(div,prefix,obj){//@HACK @TODO: mover a AUX
      const subscript = function(s){
        return prefix === null? s : (prefix+'['+s+']');
      };
      for(const k in obj){
        const val = obj[k];
        if(typeof val == 'object'){
          fill(div,subscript(k),val);
        }
        else{
          const name = subscript(k);
          div.find(`[name="${name}"]`).val(val);
        }
      }
    };
    
    const fillError = function(div,obj){//@HACK @TODO: mover a AUX
      for(const k in obj){
        const val = obj[k];
        if(typeof val == 'object' && !Array.isArray(val)){
          console.log(k,val,'Valor inesperado');
        }
        else{
          const name_arr = k.split('.');
          let name = name_arr?.[0] ?? '';
          for(let idx=1;idx<name_arr.length;idx++){
            name+='['+name_arr[idx]+']';
          }
          mostrarErrorValidacion(div.find(`[name="${name}"]`),Array.isArray(val)? val.join(', ') : val,true);
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
    
    const agregarDetallePestaña = function(pestaña,titulo,replace_idx){
      const div = pestaña.find('[data-js-molde]').clone();
      const replace_str_tipo = div.attr('data-js-molde');
      div.removeAttr('data-js-molde').show();
      
      div.find('[data-titulo]').text(titulo);
      
      div.find('[data-name]').each(function(_,nobj){
        const n = $(nobj);
        n.attr('name',n.attr('data-name').replaceAll(replace_str_tipo,replace_idx));
      });
      
      div.find('[data-depende]').each(function(_,nobj){
        const n = $(nobj);
        n.attr('data-depende',n.attr('data-depende').replaceAll(replace_str_tipo,replace_idx));
      });
      
      div.attr('data-idx',replace_idx);
      
      pestaña.find('[data-js-contenedor]').append(div);
      
      return div;
    }
    
          
          
    let inputs_a_formatear     = null;
    let inputs_a_formatear_Set = null;
    const formatearNumeros = function(inpts = null){//Saca los 0 de sobra a la derecha
      if(inpts !== null){
        inputs_a_formatear = inpts;
        inputs_a_formatear_Set = new Set(
          inputs_a_formatear.map(function(_,i){return i.getAttribute('name');}).toArray()
        );
      }
      //Para verlos en debug usar algo tipo .css('color','red');     
      inputs_a_formatear.each(function(_,iobj){
        const i = $(iobj);
        i.val(formatter(i.val()));
      });
    }
    const deformatearFormData = function(obj){      
      const ret = {};
      for(const k in obj){
        ret[k] = inputs_a_formatear_Set.has(k)? deformatter(obj[k]) : obj[k];
      }
      return ret;
    }
    
    const render = function(canon,mantener_historial = false){
      const form = M.find('form[data-js-recalcular]');
      const rerender = M.attr('data-render');
      
      ocultarErrorValidacion(form.find('[name]'));
      
      if((rerender ?? 1) == 0){
        fill(M,null,canon);
        setReadonly();
        formatearNumeros();
        return;
      }
      
      const llenarPestaña = function(pestaña,tipos_obj,mostrar_de_todos_modos = false){
        pestaña.find('[data-js-contenedor]').empty();
        let lleno = false;
        for(const tipo in tipos_obj){
          lleno = true;
          agregarDetallePestaña(pestaña,tipo.toUpperCase(),tipo);
        }
        
        //@HACK: no mostrar la pestaña si no tiene nada
        M.find('[data-js-tabs] [data-js-tab]').filter(function(_,tab_obj){
          return $($(tab_obj).attr('data-js-tab'))?.[0] == pestaña[0];
        }).toggle(lleno || mostrar_de_todos_modos);
        pestaña.toggle(lleno || mostrar_de_todos_modos);
      }
      
      llenarPestaña(form.find('[data-canon-variable]'),canon?.canon_variable ?? {});
      llenarPestaña(form.find('[data-canon-fijo-mesas]'),canon?.canon_fijo_mesas ?? {});
      llenarPestaña(form.find('[data-canon-fijo-mesas-adicionales]'),canon?.canon_fijo_mesas_adicionales ?? {});
      llenarPestaña(form.find('[data-adjuntos]'),canon?.adjuntos ?? {},true);
            
      form.find('[data-js-fecha]').trigger('init.fecha');
      
      M.attr('data-render',0);
      fill(M,null,canon);
      setReadonly();
      formatearNumeros(M.find('form[data-js-recalcular] input:not([data-js-texto-no-formatear-numero])'));
      
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
      form.find('[name],[data-descripcion],[data-archivo]').val('');
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
    
    //No dejar poner '.' en los inputs numericos, si pone reemplazar por una ','. Esto es para homogeineizar el input
    //Si pega texto no se puede hacer mucho porque habria que adivinar el formato
    M.find('form[data-js-recalcular]').on('keydown','input:not([datajs-texto-no-formatear-numero])',function(e){
      const es_punto = e.charCode || e.keyCode || 0;
      if(es_punto == 190 || es_punto == 110){
        const $this = $(this);
        
        const val   = $this.val();
        const start = this.selectionStart;
        const end   = this.selectionEnd;
        $this.val(val.substr(0,start) + "," + val.substr(end));
        
        this.selectionStart = this.selectionEnd = start + 1;
        return false;
      }
      return true;
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
    
    M.find('form[data-js-recalcular]').on('recalcular',function(e){
      const form = $(e.currentTarget);
      AUX.POST(form.attr('data-js-recalcular'),deformatearFormData(AUX.form_entries(form[0])),
        function(data){
          render(data);
        },
        function(data){
          fillError(form,data.responseJSON ?? {});
        }
      );
    });
    
    M.find('form[data-js-recalcular]').on('focus','select[readonly]',function(e){
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
    
    M.find('form[data-js-recalcular]').on('click','[data-js-click-abrir-val-hermano]',function(e){
      const tgt = $(e.currentTarget);
      const sibling_val = tgt.siblings(tgt.attr('data-js-click-abrir-val-hermano')).val();
      window.open(sibling_val,'_blank');
    });
    
    M.find('form[data-js-recalcular]').on('click','[data-js-borrar-adjunto]',function(e){
      const tgt = $(e.currentTarget);
      tgt.closest('[data-adjunto]').remove();
    });
    
    M.find('form[data-js-recalcular]').find('[data-js-agregar-adjunto]').click(function(e){
      const tgt = $(e.currentTarget);
      const pestaña = tgt.closest('[data-adjuntos]');
      
      let max_idx = -1;
      pestaña.find('[data-js-contenedor] [data-adjunto]:visible').each(function(_,adj){
        const idx = parseInt($(adj).attr('data-idx'));
        if(isNaN(idx) || idx < 0){
          throw `Error el indice "${idx}" tiene que ser un numero entero positivo o 0`;
        }
        max_idx = Math.max(max_idx,idx);
      });
      
      const idx = max_idx+1;//Si no hay max_idx=-1 -> idx=0
      const parent = tgt.closest('[data-adjunto]');
      const descripcion_obj = parent.find('[data-descripcion]');
      const archivo_obj = parent.find('[data-archivo]');
      
      const descripcion = parent.find('[data-descripcion]').val();
      const archivo_dom_obj = parent.find('[data-archivo]')?.[0];
      const archivo = archivo_dom_obj?.files?.[0];
      
      if(!archivo) return;
      
      const fileReader = new FileReader();
      fileReader.onloadend = function (e) {
        const div = agregarDetallePestaña(pestaña,idx,idx);        
        div.data('archivo',archivo);
        
        const file = new Blob([e.target.result], { type: archivo.type });
        const fileURL = window.URL.createObjectURL(file);
        
        fill(
          div,
          'adjuntos['+idx+']',
          {
            descripcion: descripcion,
            nombre_archivo: archivo.name,
            id_archivo: null,
            link: fileURL
          }
        );
        
        descripcion_obj.val('');
        archivo_obj.val('');
        
        div.attr('data-idx',idx);
        div.attr('data-nuevo-adjunto',true);
      };
      
      fileReader.readAsArrayBuffer(archivo);
    });
    
    M.find('[data-js-enviar]').click(function(e){
      const tgt = $(e.currentTarget);
      const url = tgt.attr('data-js-enviar');
      const form = M.find('form[data-js-recalcular]');
      const entries = deformatearFormData(AUX.form_entries(form?.[0]));
      
      M.find('[data-adjuntos] [data-js-contenedor] [data-adjunto]:visible').each(function(_,adj_obj){
        const adj = $(adj_obj);
        const idx = adj.attr('data-idx');
        if(adj.data('archivo')){
          entries[`adjuntos[${idx}][file]`] = adj.data('archivo')
        }
      });
      
      //@HACK @TODO: agregar funcionalidad a AUX para convertir objetos a FD
      const newfd = new FormData();//Necesito FormData si voy a mandar sin procesar (porque mando archivos)
      for(const k in entries){
        newfd.append(k,entries[k]);
      }
      
      AUX.POST(url,newfd,
        function(data){
          AUX.mensajeExito('Guardado');
          M.modal('hide');
          $('#pant_canon').find('[data-js-filtro-tabla]').trigger('buscar');
        },
        function(data){
          AUX.mensajeError('');
          fillError(form,data.responseJSON ?? {});
        },
        {
          contentType: false,
          processData: false,
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
  
  const reemplazarPorJsonEditor = function(div,valor){
    div?.empty();
    const jsoneditor = new JSONEditor(div?.[0], {mode: 'code',modes: ['tree','view','form','code','text','preview']});
    jsoneditor.set(JSON.parse(valor ?? ''));
    div?.data('jsoneditor',jsoneditor);
  }    
  
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
        
        if(pant.is('#pant_defecto')){
          reemplazarPorJsonEditor(fila.find('[data-js-jsoneditor]'),obj?.valor ?? '');
        }
        else if(pant.is('#pant_canon')){
          fila.find('[data-estado-visible]').filter(function(_,ev_obj){
            return !$(ev_obj)?.attr('data-estado-visible')?.toUpperCase()?.split(',').includes(obj.estado.toUpperCase());
          }).remove();
          fila.find('[data-formatear-numero]').each(function(_,fn_obj){
            const $fn_obj = $(fn_obj);
            $fn_obj.text(formatter($fn_obj.text()));
          });
        }
      });
      tbody.find('[data-js-borrar]').click(function(e){
        const tgt = $(e.currentTarget);
        const id = tgt.val();
        const url = tgt.attr('data-js-borrar');
        const fd = {};
        fd[tgt.attr('data-table-id')] = id;
        AUX.DELETE(url,fd,
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
  
  if($('#pant_defecto').length){
    reemplazarPorJsonEditor($('#pant_defecto').find('[data-js-nuevo-jsoneditor]'),'{}');
  }
     
  $('#pant_canon [data-js-nuevo-canon]').click(function(e){
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-nuevo-canon'),null,'NUEVO']);
  });
  
  $('#pant_canon').on('click','[data-js-editar]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-editar'),tgt.val(),'EDITAR']);
  });
  
  $('#pant_canon').on('click','[data-js-adjuntar]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-adjuntar'),tgt.val(),'ADJUNTAR']);
  });
  
  $('#pant_canon').on('click','[data-js-ver]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-ver'),tgt.val(),'VER']);
  });
  
  function encodeQueryData(data){
    const ret = [];
    for (let d in data)
      ret.push(encodeURIComponent(d) + '=' + encodeURIComponent(data[d]));
    return ret.join('&');
  }
  
  $('#pant_canon').on('click','[data-js-abrir-pestaña]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    const data = {};
    data[tgt.attr('data-table-id')] = tgt.val();
    window.open(tgt.attr('data-js-abrir-pestaña')+'?'+encodeQueryData(data),'_blank');
  });
  
  $('#pant_canon').on('click','[data-js-cambiar-estado]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    const url = tgt.attr('data-js-cambiar-estado');
    AUX.GET(
      url,
      {id_canon: tgt.val()},
      function(data){
        AUX.mensajeExito(data?.mensaje ?? '');
        $('#pant_canon').find('[data-js-filtro-tabla]').trigger('buscar');
      },
      function(data){
        AUX.mensajeError(data?.mensaje ?? '');
      }
    );
  });
  
  const guardarValorPorDefecto = function(url,campo,valor){
    AUX.POST(
      url,
      {
        campo: campo,
        valor: valor,
      },
      function(data){
        AUX.mensajeExito('Guardado');
        $('#pant_defecto').find('[data-js-filtro-tabla]').trigger('buscar');
      },
      function(data){
        AUX.mensajeError(JSON.stringify(data?.responseJSON ?? '{}'));
      }
    );
  }
  
  $('#pant_defecto').on('click','[data-js-guardar]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    const url = tgt.attr('data-js-guardar');
    const fila = tgt.closest('tr');
    guardarValorPorDefecto(url,fila.find('.campo')[0].innerHTML,fila.find('.valor').data('jsoneditor').getText());
  });
  
  $('#pant_defecto').find('[data-js-guardar-nuevo]').click(function(e){
    const tgt = $(e.currentTarget);
    const url = tgt.attr('data-js-guardar-nuevo');
    const form = tgt.closest('form');
    const fd = AUX.form_entries(tgt.closest('form')?.[0]);
    guardarValorPorDefecto(url,fd.campo,form.find('[data-js-nuevo-jsoneditor]').data('jsoneditor').getText());
  });
  
  $('[data-js-filtro-tabla]').trigger('buscar');
});
