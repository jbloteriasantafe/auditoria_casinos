import "/js/Components/FiltroTabla.js";
import "/js/Components/inputFecha.js";
import "/js/Components/modal.js";
import {AUX} from "/js/Components/AUX.js";
import "/js/Components/modalEliminar.js";

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
}

function deformatter(n){
  const caracteres_validos = /^([0-9]|-|,|\.)$/;
  n = n.split('').filter(c => caracteres_validos.test(c)).join('');
  
  return n.replaceAll('.','').replaceAll(',','.');
}
 
function encodeQueryData(data){
  const ret = [];
  for (let d in data)
    ret.push(encodeURIComponent(d) + '=' + encodeURIComponent(data[d]));
  return ret.join('&');
}

function fill(div,prefix,obj){//@HACK @TODO: mover a AUX
  const subscript = function(s){
    return prefix === null? s : (prefix+'['+s+']');
  };
  for(const k in obj){
    const val = obj[k];
    if(val !== null && typeof val == 'object'){
      fill(div,subscript(k),val);
    }
    else{
      const name = subscript(k);
      div.find(`[name="${name}"]`).val(val);
    }
  }
}

function fillError(div,obj){//@HACK @TODO: mover a AUX
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

function filterFunction(M,attr){
  const check_params = {
    modo: M.attr('data-modo'),
    es_antiguo: M.find('[name="es_antiguo"]').val(),
    estado: M.find('[name="estado"]').val().toUpperCase()
  };
        
  return function(_,r_obj){
    let json_rdata = null;
    try{
      json_rdata = JSON.parse($(r_obj).attr(attr));
    }
    catch(error){
      console.log(r_obj,json_rdata);
      throw error;
    }
    
    if(!Array.isArray(json_rdata)){
      console.log(r_obj,json_rdata);
      throw 'Valor inesperado de "'+$(r_obj).attr(attr)+'" se esperaba un arreglo de objetos';
    }
    for(const obj of json_rdata){
      if(typeof obj !== 'object'){
        console.log(r_obj,obj);
        throw 'Valor inesperado de "'+$(r_obj).attr(attr)+'" se esperaba un arreglo de objetos';
      }
      for(const param in check_params){
        const check_val = check_params[param];
        const obj_val = obj[param] ?? undefined;
        if(obj_val == '*' || obj_val === check_val) return true;
      }
    }
    return false;
  };
}
        
function setReadonly(M){
  const setReadOnlyObj = function(state){
    return function(_,r_obj){
      const r = $(r_obj);
      const f = r.children('[data-js-fecha]');
      if(f.length){
        f[0].readonly(state);  
      }
      else{
        if(state){ r.attr('readonly',true);  }
        else{      r.removeAttr('readonly'); }
      }
    };
  }
  
  M.find('[data-readonly]:not([data-js-fecha])')
  .each(setReadOnlyObj(false))
  .filter(filterFunction(M,'data-readonly'))
  .each(setReadOnlyObj(true));
}

$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Canon');
  
  $('[data-js-modal-ver-cargar-canon]').each(function(_,m_obj){
    const M = $(m_obj);
    
    
    const setVisible = function(){
      M.find('[data-modo-mostrar]').hide().filter(filterFunction(M,'data-modo-mostrar')).show();
    }
    
    const agregarDetallePestaña = function(pestaña,titulo,replace_idx,dias){
      const div = pestaña.find('[data-js-molde]').clone();
      const replace_str_tipo = div.attr('data-js-molde');
      div.removeAttr('data-js-molde');
      
      div.find('[data-titulo]').text(titulo);
      
      div.find('[data-name]').each(function(_,nobj){
        const n = $(nobj);
        n.attr('name',n.attr('data-name').replaceAll(replace_str_tipo,replace_idx));
      });
      
      div.find('[data-depende]').each(function(_,nobj){
        const n = $(nobj);
        n.attr('data-depende',n.attr('data-depende').replaceAll(replace_str_tipo,replace_idx));
      });
      
      div.find('[data-div-devengado="diario"],[data-div-determinado="diario"]').each(function(_,divdetdevobj){          
        const tabla = $(divdetdevobj).find('[data-tabla-diario]');
        const molde = $(divdetdevobj).find('[data-molde-diario]');
        molde.find('[data-name][name]').removeAttr('name');//@HACK Le saco el name al molde puesto anteriormente
        const replace_str_diario = molde.attr('data-molde-diario');
        for(let dia=1;dia<=dias;dia++){
          const fila = molde.clone().removeAttr('data-molde-diario');
          fila.find('[data-name]').each(function(_,nobj){
            const n = $(nobj);
            n.attr(
              'name',
              n.attr('data-name')
              .replaceAll(replace_str_tipo,replace_idx)
              .replaceAll(replace_str_diario,dia)
            );
          });
          tabla.append(fila);
        };
      });
      
      div.attr('data-subcanon-tipo',replace_idx);
      
      pestaña.find('[data-js-contenedor]').append(div);
      
      div.find('[data-js-fecha]').trigger('init.fecha');
      
      return div;
    }
    
    let inputs_a_formatear = null;
    const agregarInputsFormatear = function(inpts){
      inputs_a_formatear = inputs_a_formatear ?? {};
      inpts.each(function(_,iobj){
        inputs_a_formatear[iobj.getAttribute('name')] = inputs_a_formatear[iobj.getAttribute('name')] ?? [];
        inputs_a_formatear[iobj.getAttribute('name')].push(iobj);
      });
    };
    const limpiarInputsFormatear = function(){
      inputs_a_formatear = null;
    }
    const borrarInputsFormatear = function(inpts){
      if(inputs_a_formatear === null) return;
      inpts.each(function(name,_){
        if(name in inputs_a_formatear){
          delete inputs_a_formatear[name];
        }
      });
    };
    
    const formatearCampos = function(inpts = null){//Saca los 0 de sobra a la derecha
      //Para verlos en debug usar algo tipo .css('color','red');
      for(const name in inputs_a_formatear){
        for(const i of inputs_a_formatear[name]){
          const $i = $(i);
          if($i.is('[data-js-formatear-año-mes]')){
            $i.val($i.val().substr(0,'YYYY-MM'.length));
          }
          else{
            $i.val(formatter($i.val()));
          }
        }
      }
    }
    
    const deformatearVal = function(obj,val){
      if(obj.is('[data-js-formatear-año-mes]')){
        return val+'-01';
      }
      else{
        return deformatter(val);
      }
    };
    
    const deformatearFormData = function(obj){
      const ret = {};
      for(const name in obj){
        if(name in inputs_a_formatear){
          for(const i of inputs_a_formatear[name]){
            ret[name] = deformatearVal($(i),obj[name]);
          }
        }
        else{
          ret[name] = obj[name];
        }
      }
      return ret;
    }
    
    const render = function(canon,mantener_historial = false){
      const form = M.find('form[data-js-recalcular]');
      const rerender = M.attr('data-render');
      
      ocultarErrorValidacion(form.find('[name]'));
      
      if((rerender ?? 1) == 0){
        fill(M,null,canon);
        setReadonly(M);
        formatearCampos();
        return;
      }
      
      M.find('[name="estado"]').val(canon?.estado ?? 'Nuevo');
      setVisible();
      
      const llenarPestaña = function(pestaña,tipos_obj,dias,mostrar_de_todos_modos = false){
        pestaña.find('[data-js-contenedor]').empty();
        let lleno = false;
        for(const tipo in tipos_obj){
          lleno = true;
          agregarDetallePestaña(pestaña,tipo.toUpperCase(),tipo,dias);
        }
        
        //@HACK: no mostrar la pestaña si no tiene nada
        M.find('[data-js-tabs] [data-js-tab]').filter(function(_,tab_obj){
          return $($(tab_obj).attr('data-js-tab'))?.[0] == pestaña[0];
        }).toggle(lleno || mostrar_de_todos_modos);
        pestaña.toggle(lleno || mostrar_de_todos_modos);
        
        //Muestro el primer subcanon por defecto
        pestaña.find('[data-subcanon-toggle-estado]')
        .filter(function(_,o){return $(o).css('display') !== 'none';})
        .eq(0)
        .attr('data-subcanon-toggle-estado','mostrar_subcanon');
      }
      
      const dias = (function(isoDateString){
        const date = new Date(isoDateString+'T00:00');
        const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        return lastDay.getDate();
      })(canon.año_mes);
      
      llenarPestaña(form.find('[data-cotizaciones]'),canon?.canon_cotizacion_diaria ?? {},dias,true);
      llenarPestaña(form.find('[data-canon-variable]'),canon?.canon_variable ?? {},dias);
      llenarPestaña(form.find('[data-canon-fijo-mesas]'),canon?.canon_fijo_mesas ?? {},dias);
      llenarPestaña(form.find('[data-canon-fijo-mesas-adicionales]'),canon?.canon_fijo_mesas_adicionales ?? {},dias);
      llenarPestaña(form.find('[data-canon-archivo]'),canon?.canon_archivo ?? {},dias,true);
      llenarPestaña(form.find('[data-canon-pago]'),canon?.canon_pago ?? {},dias,true);
      
      let con_diario = true;
      //early break lo hace bastante feo al codigo para poca o nula optimizacion
      for(const sc of ['canon_variable','canon_fijo_mesas','canon_fijo_mesas_adicionales'])
      for(const tipo in (canon?.[sc] ?? {}))
        con_diario = con_diario && (Object.keys(canon?.[sc]?.[tipo]?.diario ?? {}).length > 0);
      
      M.attr('data-con-diario',con_diario+0);
      M.attr('data-render',0);
      fill(M,null,canon);
      setReadonly(M);
      
      limpiarInputsFormatear();
      agregarInputsFormatear(M.find('form[data-js-recalcular] input[name]:not([data-js-texto-no-formatear-numero])'));
      formatearCampos();
      
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
      
      M.find('[data-js-devengar]').trigger('setearDevengar');
    };
    
    M.find('[data-js-select-historial]').change(function(e){
      const tgt = $(e.currentTarget);
      M.attr('data-render',1);
      render(tgt.find('option:selected').data('canon'),true);
    });
    
    M.on('mostrar.modal',function(e,url,id_canon,modo){      
      M.attr('data-modo',modo.toUpperCase());
      const form = M.find('form[data-js-recalcular]');
      form.find('[name],[data-descripcion],[data-archivo]').val('');
      AUX.GET(url,{id_canon: id_canon},function(canon){       
        M.attr('data-render',1);
        render(canon);
        
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
      $('.loading_screen').show();
      AUX.POST(form.attr('data-js-recalcular'),deformatearFormData(AUX.form_entries(form[0])),
        function(data){
          $('.loading_screen').hide();
          render(data);
        },
        function(data){
          $('.loading_screen').hide();
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
    
    M.find('form[data-js-recalcular]').on('click','[data-js-borrar-archivo]',function(e){
      const tgt = $(e.currentTarget);
      tgt.closest('[data-subcanon-tipo]').remove();
    });
    
    const agregarArchivo = function(resolve=()=>{},reject=()=>{}){
      const tgt = M.find('form[data-js-recalcular]').find('[data-js-agregar-archivo]');
      const pestaña = tgt.closest('[data-canon-archivo]');
      
      let max_idx = -1;
      pestaña.find('[data-js-contenedor] [data-subcanon-tipo]:visible').each(function(_,adj){
        const idx = parseInt($(adj).attr('data-subcanon-tipo'));
        if(isNaN(idx) || idx < 0){
          throw `Error el indice "${idx}" tiene que ser un numero entero positivo o 0`;
        }
        max_idx = Math.max(max_idx,idx);
      });
      
      const idx = max_idx+1;//Si no hay max_idx=-1 -> idx=0
      const parent = tgt.closest('[data-subcanon]');
      
      const descripcion_obj = parent.find('[data-descripcion]');
      const descripcion = parent.find('[data-descripcion]').val();
      
      const archivo_obj = parent.find('[data-archivo]');
      const archivo = archivo_obj?.[0]?.files?.[0];
      
      if(!archivo) return resolve();
      
      const fileReader = new FileReader();
      fileReader.onloadend = function (e) {
        const div = agregarDetallePestaña(pestaña,idx,idx);        
        div.data('archivo',archivo);
        
        const file = new Blob([e.target.result], { type: archivo.type });
        const fileURL = window.URL.createObjectURL(file);
        
        fill(
          div,
          'canon_archivo['+idx+']',
          {
            descripcion: descripcion,
            nombre_archivo: archivo.name,
            id_archivo: null,
            link: fileURL
          }
        );
        
        descripcion_obj.val('');
        archivo_obj.val('');
        
        div.attr('data-subcanon-tipo',idx);
        div.attr('data-nuevo-archivo',true);
        
        resolve();
      };
      
      fileReader.onerror = reject;
      
      fileReader.readAsArrayBuffer(archivo);
    };
    
    M.find('form[data-js-recalcular]').find('[data-js-agregar-archivo]').click(function(e){
      agregarArchivo();
    });
    
    M.find('[data-js-enviar]').click(async function(e){
      await new Promise(agregarArchivo);//Agrego archivo si lo dejo seleccionado
      
      const tgt = $(e.currentTarget);
      const url = tgt.attr('data-js-enviar');
      const form = M.find('form[data-js-recalcular]');
      const entries = deformatearFormData(AUX.form_entries(form?.[0]));
      
      M.find('[data-canon-archivo] [data-js-contenedor] [data-subcanon-tipo]:visible').each(function(_,adj_obj){
        const adj = $(adj_obj);
        const idx = adj.attr('data-subcanon-tipo');
        if(adj.data('archivo')){
          entries[`canon_archivo[${idx}][file]`] = adj.data('archivo')
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
    
    M.find('[data-js-agregar-pago]').click(function(e){
      let max_idx = null;
      M.find('[data-canon-pago] [data-js-contenedor] [data-subcanon-tipo]').each(function(_,p_obj){
        max_idx = Math.max(parseInt($(p_obj).attr('data-subcanon-tipo')),max_idx);
      });
      
      const idx = max_idx === null? 0 : (max_idx+1);
      const pago = agregarDetallePestaña(M.find('[data-canon-pago]'),null,idx);
      agregarInputsFormatear(pago.find('input[name]:not([data-js-texto-no-formatear-numero])'));
      
      M.find('form[data-js-recalcular]').trigger('recalcular');
    });
    
    M.find('form[data-js-recalcular]').on('click','[data-js-borrar-pago]',function(e){
      const tgt = $(e.currentTarget);
      const pago = tgt.closest('[data-subcanon-tipo]');
      borrarInputsFormatear(pago.find('input[name]:not([data-js-texto-no-formatear-numero])'));
      pago.remove();
      M.find('form[data-js-recalcular]').trigger('recalcular');
    });
    
    M.find('form[data-js-recalcular]').on('change setearDevengar','[data-js-devengar]',function(e){
      $(e.currentTarget)
      .closest('[data-subcanon-tipo]').find('[data-css-devengar]')
      .attr('data-css-devengar',parseInt(e.currentTarget.value));
    });
    
    M.find('form[data-js-recalcular]').on('click','[data-js-click-toggle]',function(e){
      const tgt = $(e.currentTarget);
      const params = JSON.parse(tgt.attr('data-js-click-toggle'));
      tgt.closest(params.parentSelector)
      .attr(params.parentAttr,params.nuevoEstado);
      tgt.trigger(params.nuevoEstado);
    });
    
    M.find('form[data-js-recalcular]').on('mostrar_subcanon','[data-js-click-toggle][data-js-subcanon-mostrar-esconder-siblings]',function(e){
      const tgt = $(e.currentTarget);
      const params = JSON.parse(tgt.attr('data-js-click-toggle'));
      tgt.closest(params.parentSelector)
      .siblings('[data-subcanon-toggle-estado]')
      .attr('data-subcanon-toggle-estado','esconder_subcanon');
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
    jsoneditor.set(JSON.parse(valor ?? '{}'));
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
        
        const id_k = fila.attr('data-table-id');
        const id = obj[id_k];
        
        fila.find('button')
        .val(id)
        .filter(obj.deleted_at? ':not([data-mostrar-borrado])' : '[data-mostrar-borrado]')
        .remove();
        
        fila.find('a[href]')
        .each(function(_,obj){
          const data = {};
          data[id_k] = id;
          $(obj).attr('href',$(obj).attr('href')+'?'+encodeQueryData(data));
        });
        
        tbody.append(fila);
        
        if(pant.is('#pant_defecto')){
          reemplazarPorJsonEditor(fila.find('[data-js-jsoneditor]'),obj?.valor ?? '');
        }
        else if(pant.is('#pant_canon')){
          fila.attr('data-css-tiene_diarios',obj.tiene_diarios);
          fila.find('[data-estado-visible]').filter(function(_,ev_obj){
            return !$(ev_obj)?.attr('data-estado-visible')?.toUpperCase()?.split(',').includes(obj.estado.toUpperCase());
          }).remove();
          fila.find('[data-formatear-numero]').each(function(_,fn_obj){
            const $fn_obj = $(fn_obj);
            $fn_obj.text(formatter($fn_obj.text()));
          });
        }
        
        const popover_html = fila.find('[data-molde-popover]').clone().removeAttr('data-molde-popover')[0]?.outerHTML;
        fila.find('[data-toggle="popover"]').attr('data-content',popover_html);
      });
      tbody.find('[data-js-borrar]').click(function(e){
        const tgt = $(e.currentTarget);
        const fd = {};
        fd[tgt.closest('[data-table-id]').attr('data-table-id')] = tgt.val();
        
        $('[data-js-modal-eliminar]').trigger('mostrar.modal',[{
          url: tgt.attr('data-js-borrar'),
          url_params: fd,
          mensaje: 'Esta seguro que desea eliminarlo',
          success: function(){pant.find('[data-js-filtro-tabla]').trigger('buscar');},
        }]);
      });
      tbody.find('[data-toggle="popover"]').popover();
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
  
  $('#pant_canon [data-js-descargar]').click(function(e){
    const url = $(e.currentTarget).attr('data-js-descargar');
    const descargando = $(this).find('[data-js-descargando]').show();
    AUX.POST(
      url,
      $('#pant_canon [data-js-filtro-tabla]:visible')[0].form_data(),
      function(data){
        descargando.hide();
        //https://stackoverflow.com/questions/14964035/how-to-export-javascript-array-info-to-csv-on-client-side
        const blob = new Blob([data], { type: 'text/csv' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href = url;
        
        const timestamp = (new Date())
        .toISOString()
        .split('.')[0]
        .replaceAll('-','')
        .replaceAll('T','-')
        .replaceAll(':','');
        
        a.setAttribute('download', 'canon-generado-'+timestamp+'.csv');
        
        a.click();
      },
      function(data){
        descargando.hide();
        console.log(data);
        AUX.mensajeError();
      }
    );
  });
     
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
  
  $('#pant_canon').on('click','[data-js-cambiar-estado]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-cambiar-estado]').trigger('mostrar.modal',[{
      url: tgt.attr('data-js-cambiar-estado'),
      url_params: {id_canon: tgt.val()},
      mensaje: tgt.attr('data-mensaje-cambiar-estado') ?? '¿Desea cambiar el estado?',
      success: function(data){
        AUX.mensajeExito(data?.mensaje ?? '');
        $('#pant_canon').find('[data-js-filtro-tabla]').trigger('buscar');
      },
      error: function(data){
        AUX.mensajeError(data?.mensaje ?? '');
      }
    }]);
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

$(function(e){ $('[data-js-modal-cambiar-estado]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  let url = undefined;
  let success = null;
  let error = null;
  let url_params = {};
  
  M.on('mostrar.modal',function(e,params){
    url = params.url;
    if(typeof url == 'undefined') throw 'No se recibio una URL';
    
    success = params.success ?? function(data){};
    error = params.error ?? function(data){console.log(data);};
    url_params = params.url_params ?? {};
    
    $M('.mensaje').text(params.mensaje ?? '');
    M.modal('show');
  });

  $M('[data-js-click-cambiar-estado]').click(function(){
    AUX.GET(url,url_params,function(data){
      M.modal('hide');
      success(data);
    },function(data){
      error(data);
    });
  });
  
})});
