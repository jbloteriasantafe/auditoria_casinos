import "/js/Components/FiltroTabla.js";
import "/js/Components/inputFecha.js";
import "/js/Components/modal.js";
import {AUX} from "/js/Components/AUX.js";
import "/js/Components/modalEliminar.js";
import "/js/lib/jsoneditor.js";

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

const reemplazarPorJsonEditor = function(div,valor){
  div?.empty();
  const jsoneditor = new JSONEditor(div?.[0], {mode: 'code',modes: ['tree','view','form','code','text','preview']});
  jsoneditor.set(JSON.parse(valor ?? '{}'));
  div?.data('jsoneditor',jsoneditor);
}

const agregarPopOvers = function(fila){
  fila.find('[data-molde-popover]').each(function(_,molde){
    const attr_match = $(molde).attr('data-molde-popover');
    const popover_html = $(molde).clone().removeAttr('data-molde-popover')[0]?.outerHTML;
    fila.find(`[data-toggle-popover="${attr_match}"]`).attr('data-content',popover_html);
  });
  fila.find('[data-toggle-popover]').popover();
}

$(function(){
  $('.tituloSeccionPantalla').text('Canon');
  
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
          {//Arreglo el rowspan al numero de cuentas del canon
            let filas_cuentas_a_borrar = fila.filter('[data-cuenta]');
            for(const c of (obj.cuentas ?? [])){
              const fila_cuenta = fila.filter(`[data-cuenta="${c.cuenta}"]`);
              const cidx = fila_cuenta.attr('data-cuenta-idx');
              for(const kc in c){
                fila_cuenta.find(`[data-name="canon_cuenta[${cidx}][${kc}]"]`).text(c[kc]);
              }
              filas_cuentas_a_borrar = filas_cuentas_a_borrar.not(fila_cuenta);//Lo saco del listado para borrar
            }
            filas_cuentas_a_borrar.remove();
            //Arreglo el rowspan al numero de cuentas
            fila.find('[data-cuentas-rowspan]').attr('rowspan',1).attr('data-cuentas-rowspan',fila.filter('[data-cuenta]').length+1);
          }
          {//Agrego el evento de display
            const f = fila.filter('[data-canon]');
            const f_cuentas = fila.filter('[data-cuenta]');
            
            fila.find('[data-js-click-toggle-cuentas]').on('toggleCuentas',function(e,status){
              const display = status ?? (f_cuentas.attr('data-cuenta-display') == 'none');
              if(display){
                f_cuentas.attr('data-cuenta-display','');
                f.find('[rowspan]').attr('rowspan',f.find('[data-cuentas-rowspan]').attr('data-cuentas-rowspan'));
              }
              else{
                f_cuentas.attr('data-cuenta-display','none');
                f.find('[rowspan]').attr('rowspan',1);
              }
            });
            
            fila.find('[data-js-click-toggle-cuentas]').on('click',function(e,status){
              e.stopPropagation();//Saco el evento por defecto con delay
              fila.find('[data-js-click-toggle-cuentas]').trigger('toggleCuentas');
              tbody.find('[data-canon]').not(f)
              .find('[data-js-click-toggle-cuentas]').trigger('toggleCuentas',[false]);
            });
          }
          
          fila.find('[data-estado-visible]').filter(function(_,ev_obj){
            return !$(ev_obj)?.attr('data-estado-visible')?.toUpperCase()?.split(',').includes(obj.estado.toUpperCase());
          }).remove();
          fila.find('[data-formatear-numero]').each(function(_,fn_obj){
            const $fn_obj = $(fn_obj);
            $fn_obj.text(formatter($fn_obj.text()));
          });
        }
        agregarPopOvers(fila);
      });
      tbody.find('[data-js-borrar]').click(function(e){
        const tgt = $(e.currentTarget);
        const fd = {};
        fd[tgt.closest('[data-table-id]').attr('data-table-id')] = tgt.val();
        
        $('[data-js-modal-eliminar]').trigger('mostrar',[{
          url: tgt.attr('data-js-borrar'),
          url_params: fd,
          mensaje: 'Esta seguro que desea eliminarlo',
          success: function(){pant.find('[data-js-filtro-tabla]').trigger('buscar');},
        }]);
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
    reemplazarPorJsonEditor($('#pant_defecto').find('[data-js-nuevo]'),'{}');
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
     
  $('#pant_canon [data-js-nuevo]').click(function(e){
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-nuevo'),null,'NUEVO']);
  });
  
  $('#pant_canon').on('click','[data-js-editar]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-editar'),tgt.val(),'EDITAR']);
  });
  
  $('#pant_canon').on('click','[data-js-ver]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-ver'),tgt.val(),'VER']);
  });
  
  $('#pant_canon').on('click','[data-js-adjuntar]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon]').trigger('mostrar.modal',[tgt.attr('data-js-adjuntar'),tgt.val(),'ADJUNTAR']);
  });
  
  $('#pant_canon').on('click','[data-js-ver-pagos]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon-pagos]').trigger('mostrar.modal',[tgt.attr('data-js-ver-pagos'),tgt.val(),'VER']);
  });
  
  $('#pant_canon').on('click','[data-js-editar-pagos]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-canon-pagos]').trigger('mostrar.modal',[tgt.attr('data-js-editar-pagos'),tgt.val(),'EDITAR']);
  });
  
  $('#pant_canon').on('click','[data-js-cambiar-estado]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-cambiar-estado]').trigger('mostrar',[{
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
  
  $('#pant_canon').on('click','[data-js-cambiar-estado]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-cambiar-estado]').trigger('mostrar',[{
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
    guardarValorPorDefecto(url,fd.campo,form.find('[data-js-nuevo]').data('jsoneditor').getText());
  });
  
  $('[data-js-filtro-tabla]').trigger('buscar');
});

$(function(){  
  $('[data-js-modal-ver-cargar-canon]').each(function(_,m_obj){
    const M = $(m_obj);
    
    const agregarDetallePestaña = function(pestaña,titulo,replace_idx){
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
      
      div.attr('data-idx',replace_idx);
      
      pestaña.find('[data-js-contenedor]').append(div);
      
      div.find('[data-js-fecha]').trigger('init.fecha');
      
      return div;
    }
           
    const render = function(canon,mantener_historial = false){
      const form = M.find('form');
      const rerender = M.attr('data-render');
      
      ocultarErrorValidacion(form.find('[name]'));
      
      if((rerender ?? 1) == 0){
        fill(M,null,canon);
        M.trigger('setReadonly');
        M.trigger('formatearCampos');
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
      llenarPestaña(form.find('[data-canon-archivo]'),canon?.canon_archivo ?? {},true);
      
      fill(M,null,canon);
      
      //@HACK: si es un canon nuevo estos valores tienen que estar llenados para setReadonly/setVisible
      M.find('[name="estado"]').val(canon?.estado ?? 'Nuevo');
      M.find('[name="es_antiguo"]').val(canon?.es_antiguo ?? 0);
      M.find('[name="id_casino"]').val(canon?.id_casino ?? '');
      
      M.trigger('setReadonly')
      .trigger('setVisible')
      .trigger('regenerarInputsFormatear')
      .trigger('formatearCampos');
      
      (mantener_historial?
         M.find('[data-js-select-historial]')
       : M.find('[data-js-select-historial]').empty())
       .append(
        (canon?.historial ?? []).map(function(h,hidx){
          const o = $('<option>');
          o.val(h.id_canon);
          o.text(h.usuario + ' - '+h.created_at);
          o.data('instancia',h);
          return o;
        })
      );
      
      M.attr('data-render',0);
      
      M.find('[data-js-tabs] [data-js-tab]').filter(function(_,t_obj){
        return $(t_obj).css('display') != 'none';
      }).eq(0).click();
      
      M.find('[data-js-devengar]').trigger('setearDevengar');
    };
    
    M.on('render',function(e,data,mantener_historial){
      render(data,mantener_historial);
    });
    
    M.find('form[data-js-recalcular]').on('recalculado',function(e,data){
      render(data);
    });
    
    M.find('form[data-js-recalcular]').on('recalculado-error',function(e,data){
      fillError($(e.currentTarget).closest('form'),data);
    });
    
    M.on('mostrar.modal',function(e,url,id_canon,modo){      
      M.trigger('setModo',[modo]);
      const form = M.find('form');
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
    M.find('[data-js-empty-si-cambio]').change(function(e){
      const tgt = $(e.currentTarget);
      const contenedores = $(tgt.attr('data-js-empty-si-cambio'));
      contenedores.empty();
      M.attr('data-render',1);
    });
    
    M.find('form').on('click','[data-js-borrar-archivo]',function(e){
      const tgt = $(e.currentTarget);
      tgt.closest('[data-archivo]').remove();
    });
    
    const agregarAdjunto = function(resolve=()=>{},reject=()=>{}){
      const tgt = M.find('form').find('[data-js-agregar-archivo]');
      const pestaña = tgt.closest('[data-canon-archivo]');
      
      let max_idx = -1;
      pestaña.find('[data-js-contenedor] [data-archivo]:visible').each(function(_,adj){
        const idx = parseInt($(adj).attr('data-idx'));
        if(isNaN(idx) || idx < 0){
          throw `Error el indice "${idx}" tiene que ser un numero entero positivo o 0`;
        }
        max_idx = Math.max(max_idx,idx);
      });
      
      const idx = max_idx+1;//Si no hay max_idx=-1 -> idx=0
      const parent = tgt.closest('[data-archivo]');
      const descripcion_obj = parent.find('[data-descripcion]');
      const archivo_obj = parent.find('[data-file]');
      
      const descripcion = parent.find('[data-descripcion]').val();
      const archivo_dom_obj = parent.find('[data-file]')?.[0];
      const archivo = archivo_dom_obj?.files?.[0];
      
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
        
        div.attr('data-idx',idx);
        div.attr('data-nuevo-archivo',true);
        
        resolve();
      };
      
      fileReader.onerror = reject;
      
      fileReader.readAsArrayBuffer(archivo);
    };
    
    M.find('form').find('[data-js-agregar-archivo]').click(function(e){
      agregarAdjunto();
    });
    
    M.find('[data-js-enviar]').click(async function(e){
      await new Promise(agregarAdjunto);//Agrego archivo si lo dejo seleccionado
      
      const tgt = $(e.currentTarget);
      const url = tgt.attr('data-js-enviar');
      const form = M.find('form');
            
      const aux = {};
      M.trigger('deformatearFormData',[AUX.form_entries(form[0]),aux]);
      const entries = aux.response;
      
      M.find('[data-canon-archivo] [data-js-contenedor] [data-archivo]:visible').each(function(_,adj_obj){
        const adj = $(adj_obj);
        const idx = adj.attr('data-idx');
        if(adj.data('archivo')){
          entries[`canon_archivo[${idx}][file]`] = adj.data('archivo');
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
    
    M.find('form').on('change setearDevengar','[data-js-devengar]',function(e){
      $(e.currentTarget)
      .closest('[data-css-devengar]')
      .attr('data-css-devengar',parseInt(e.currentTarget.value));
    });
  });
});

$(function(){ $('[data-js-modal-cambiar-estado]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  let url = undefined;
  let success = null;
  let error = null;
  let url_params = {};
  
  M.on('mostrar',function(e,params){
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

$(function(){  
  $('[data-js-modal-ver-cargar-canon-pagos]').each(function(_,m_obj){
    const M = $(m_obj);
    
    const agregarDetallePestaña = function(pestaña,titulo,replace_idx){
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
      
      div.attr('data-idx',replace_idx);
      
      pestaña.find('[data-js-contenedor]').append(div);
      
      div.find('[data-js-fecha]').trigger('init.fecha');
      
      return div;
    }
           
    const render = function(canon,mantener_historial = false){
      const form = M.find('form');
      const rerender = M.attr('data-render');
      
      ocultarErrorValidacion(form.find('[name]'));
      
      if((rerender ?? 1) == 0){
        fill(M,null,canon);
        M.trigger('setReadonly')
        .trigger('formatearCampos');
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
      
      llenarPestaña(form.find('[data-canon-pagos]'),canon?.canon_pago ?? [],true);
      
      fill(M,null,canon);
      
      M.trigger('setReadonly')
      .trigger('setVisible')
      .trigger('regenerarInputsFormatear')
      .trigger('formatearCampos');
      
      M.attr('data-render',0);
      
      (mantener_historial?
         M.find('[data-js-select-historial]')
       : M.find('[data-js-select-historial]').empty())
       .append(
        (canon?.historial ?? []).map(function(h,hidx){
          const o = $('<option>');
          o.val(h.id_canon);
          o.text(h.usuario + ' - '+h.created_at);
          o.data('instancia',h);
          return o;
        })
      );
    };
    
    M.on('render',function(e,data,mantener_historial){
      render(data,mantener_historial);
    });
    
    M.find('form[data-js-recalcular]').on('recalculado',function(e,data){
      render(data);
    });
    
    M.find('form[data-js-recalcular]').on('recalculado-error',function(e,data){
      fillError($(e.currentTarget).closest('form'),data);
    });
    
    M.on('mostrar.modal',function(e,url,id_canon,modo){
      M.trigger('setModo',[modo]);
      const form = M.find('form');
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
    M.find('form [data-js-empty-si-cambio]').change(function(e){
      const tgt = $(e.currentTarget);
      const contenedores = $(tgt.attr('data-js-empty-si-cambio'));
      contenedores.empty();
      M.attr('data-render',1);
    });
    
    M.find('[data-js-click-submit-form]').click(function(e){
      const o = e.currentTarget;
      const select = $(o).attr('data-js-click-submit-form');
      const $form = M.find(select);
      
      const aux = {};
      M.trigger('deformatearFormData',[$form.length? AUX.form_entries($form[0]) : {},aux]);
      const formData = aux.response;
      
      const ajax_params = JSON.parse($form.attr('data-ajax-params') ?? '{}') ?? {};
      ocultarErrorValidacion(M.find('[name]'));
      $.ajax({
        type: $form.attr('method'),
        url: $form.attr('action'),
        data: formData,
        ...ajax_params,
        success: function (data) {
          $('#pant_canon [data-js-filtro-tabla]').trigger('buscar');
          AUX.mensajeExito(data?.mensaje ?? '');
          $(o).closest('.modal').modal('hide');
        },
        error: function (data) {
          const json = data.responseJSON ?? {};
          AUX.mensajeError(json?.mensaje ?? '');
          AUX.mostrarErroresNames($form,json);
          console.log(data);
        }
      });
    });
    
    M.find('[data-js-agregar-pago]').click(function(e){
      let max_idx = null;
      M.find('[data-canon-pagos] [data-js-contenedor] [data-pago]').each(function(_,p_obj){
        max_idx = Math.max(parseInt($(p_obj).attr('data-idx')),max_idx);
      });
      
      const idx = max_idx === null? 0 : (max_idx+1);
      const pago = agregarDetallePestaña(M.find('[data-canon-pagos]'),null,idx);
      M.trigger('regenerarInputsFormatear');
      M.find('form[data-js-recalcular]').trigger('recalcular');
    });
    
    M.find('form[data-js-recalcular]').on('click','[data-js-borrar-pago]',function(e){
      const tgt = $(e.currentTarget);
      const pago = tgt.closest('[data-pago]');
      pago.remove();
      M.attr('data-render',1);
      M.find('form[data-js-recalcular]').trigger('recalcular');
    });
  });
});

$(function(){ $('[data-js-modal-cambiar-estado]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  let url = undefined;
  let success = null;
  let error = null;
  let url_params = {};
  
  M.on('mostrar',function(e,params){
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

$(function(){
  $('#pant_operadores [data-js-nuevo]').click(function(e){
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-operador]').trigger('mostrar.modal',[tgt.attr('data-js-nuevo'),tgt.val(),'NUEVO']);
  });
  
  $('#pant_operadores').on('click','[data-js-ver]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-operador]').trigger('mostrar.modal',[tgt.attr('data-js-ver'),tgt.val(),'VER']);
  });
  
  $('#pant_operadores').on('click','[data-js-editar]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-operador]').trigger('mostrar.modal',[tgt.attr('data-js-editar'),tgt.val(),'EDITAR']);
  });
  
  $('#pant_grupos_operadores [data-js-nuevo]').click(function(e){
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-grupo-operador]').trigger('mostrar.modal',[tgt.attr('data-js-nuevo'),tgt.val(),'NUEVO']);
  });
  
  $('#pant_grupos_operadores').on('click','[data-js-ver]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-grupo-operador]').trigger('mostrar.modal',[tgt.attr('data-js-ver'),tgt.val(),'VER']);
  });
  
  $('#pant_grupos_operadores').on('click','[data-js-editar]',function(e){//@TODO: bindear derecho
    const tgt = $(e.currentTarget);
    $('[data-js-modal-ver-cargar-grupo-operador]').trigger('mostrar.modal',[tgt.attr('data-js-editar'),tgt.val(),'EDITAR']);
  });
  
  $('#pant_operadores,#pant_grupos_operadores').each(function(_,pant_obj){
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
        
        if(obj?.es_individual !== undefined){
          fila.attr('data-es_individual',obj.es_individual);
        }
        
        tbody.append(fila);
        agregarPopOvers(fila);
      });
      
      tbody.find('[data-js-borrar]').click(function(e){
        const tgt = $(e.currentTarget);
        const fd = {};
        fd[tgt.closest('[data-table-id]').attr('data-table-id')] = tgt.val();
        
        $('[data-js-modal-eliminar]').trigger('mostrar',[{
          url: tgt.attr('data-js-borrar'),
          url_params: fd,
          mensaje: 'Esta seguro que desea eliminarlo',
          success: function(){pant.find('[data-js-filtro-tabla]').trigger('buscar');},
        }]);
      });
      
      tbody.find('[data-js-desborrar]').click(function(e){
        const tgt = $(e.currentTarget);
        const fd = {};
        fd[tgt.closest('[data-table-id]').attr('data-table-id')] = tgt.val();
        
        $('[data-js-modal-eliminar]').trigger('mostrar',[{
          url: tgt.attr('data-js-desborrar'),
          url_params: fd,
          mensaje: 'Esta seguro que desea des-eliminarlo',
          success: function(){pant.find('[data-js-filtro-tabla]').trigger('buscar');},
        }]);
      });
      
      tbody.find('tr[data-es_individual]').each(function(_,tr){
        const es_individual = $(tr).attr('data-es_individual');
        const correctos = $(tr).find(`[data-es_individual*="|${es_individual}|"]`);
        $(tr).find('[data-es_individual]').not(correctos).remove();
      });
    });
  });
});

$(function(){
  $('[data-js-modal-ver-cargar-operador]').each(function(){
    const  M = $(this);
    const $M = M.find.bind(M);
    const Mname = function(name,val,O=M){
      return O.find(`[name="${name}"]`).val(val ?? '');
    };
    
    const obtener_fila_cuenta = (cidx,c) => {
      const replace_str = $M('[data-molde-cuenta]').attr('data-molde-cuenta');
      const fila = $M('[data-molde-cuenta]').clone().removeAttr('data-molde-cuenta');
      fila.find('[data-name]').each(function(_,nobj){
        $(nobj).attr('name',$(nobj).attr('data-name').replaceAll(replace_str,cidx));
      });
      for(const k in c){
        Mname(`cuentas[${cidx}][${k}]`,c[k],fila);
      }
      return fila;
    };
    const obtener_fila_cv = (cidx,c) => {
      const replace_str = $M('[data-molde-canon-variable]').attr('data-molde-canon-variable');
      const fila = $M('[data-molde-canon-variable]').clone().removeAttr('data-molde-canon-variable');
      fila.find('[data-name]').each(function(_,nobj){
        $(nobj).attr('name',$(nobj).attr('data-name').replaceAll(replace_str,cidx));
      });
      for(const k in c){
        Mname(`canon_variable[${cidx}][${k}]`,c[k],fila);
      }
      return fila;
    };
    const obtener_fila_cfm = (cidx,c) => {
      const replace_str = $M('[data-molde-canon-fijo-mesas]').attr('data-molde-canon-fijo-mesas');
      const fila = $M('[data-molde-canon-fijo-mesas]').clone().removeAttr('data-molde-canon-fijo-mesas');
      fila.find('[data-name]').each(function(_,nobj){
        $(nobj).attr('name',$(nobj).attr('data-name').replaceAll(replace_str,cidx));
      });
      for(const k in c){
        Mname(`canon_fijo_mesas[${cidx}][${k}]`,c[k],fila);
      }
      return fila;
    };
    const obtener_fila_cfma = (cidx,c) => {
      const replace_str = $M('[data-molde-canon-fijo-mesas-adicionales]').attr('data-molde-canon-fijo-mesas-adicionales');
      const fila = $M('[data-molde-canon-fijo-mesas-adicionales]').clone().removeAttr('data-molde-canon-fijo-mesas-adicionales');
      fila.find('[data-name]').each(function(_,nobj){
        $(nobj).attr('name',$(nobj).attr('data-name').replaceAll(replace_str,cidx));
      });
      for(const k in c){
        Mname(`canon_fijo_mesas_adicionales[${cidx}][${k}]`,c[k],fila);
      }
      return fila;
    };
    
    const render = function(operador,mantener_historial = false){
      ocultarErrorValidacion(M.find('[name]'));
      Mname('id_canon_operador',operador?.id_canon_operador);
      Mname('id_operador',operador?.id_operador);
      Mname('nombre_legal',operador?.nombre_legal);
      Mname('nombre',operador?.nombre);
      Mname('codigo',operador?.codigo);
      Mname('cuit',operador?.cuit);
      Mname('inicio_actividad',operador?.inicio_actividad ?? '').trigger('change');
      Mname('abbr',operador?.abbr);
      Mname('color',operador?.color).trigger('change');
      Mname('codigo_casino',operador?.codigo_casino);
      Mname('codigo_plataforma',operador?.codigo_plataforma);
      Mname('codigo_apuestas_deportivas',operador?.codigo_apuestas_deportivas);
      Mname('valor_dolar',operador?.valor_dolar);
      Mname('valor_euro',operador?.valor_euro);
      Mname('devengado_cotizacion_dia',operador?.devengado_cotizacion_dia);
      Mname('devengado_cotizacion_fin_de_semana',operador?.devengado_cotizacion_fin_de_semana);
      Mname('determinado_cotizacion_dia',operador?.determinado_cotizacion_dia);
      Mname('determinado_cotizacion_fin_de_semana',operador?.determinado_cotizacion_fin_de_semana);
      
      $M('[data-contenedor-cuentas]').empty();
      for(const cidx in (operador?.cuentas ?? [])){
        $M('[data-contenedor-cuentas]').append(obtener_fila_cuenta(cidx,operador.cuentas[cidx]));
      }
      $M('[data-contenedor-canon-variable]').empty();
      for(const cidx in (operador?.canon_variable ?? [])){
        $M('[data-contenedor-canon-variable]').append(obtener_fila_cv(cidx,operador.canon_variable[cidx]));
      }
      $M('[data-contenedor-canon-fijo-mesas]').empty();
      for(const cidx in (operador?.canon_fijo_mesas ?? [])){
        $M('[data-contenedor-canon-fijo-mesas]').append(obtener_fila_cfm(cidx,operador.canon_fijo_mesas[cidx]));
      }
      $M('[data-contenedor-canon-fijo-mesas-adicionales]').empty();
      for(const cidx in (operador?.canon_fijo_mesas_adicionales ?? [])){
        $M('[data-contenedor-canon-fijo-mesas-adicionales]').append(obtener_fila_cfma(cidx,operador.canon_fijo_mesas_adicionales[cidx]));
      }
      
      (mantener_historial?
         M.find('[data-js-select-historial]')
       : M.find('[data-js-select-historial]').empty())
       .append(
        (operador?.historial ?? []).map(function(h,hidx){
          const o = $('<option>');
          o.val(h.id_canon_operador);
          o.text(h.usuario + ' - '+h.created_at);
          o.data('instancia',h);
          return o;
        })
      );
      
      M.trigger('regenerarInputsFormatear')
      .trigger('formatearCampos');
    };
    
    M.on('render',function(e,data,mantener_historial){
      render(data,mantener_historial);
    });
    
    M.on('mostrar.modal',function(e,url,id_operador,modo){
      M.trigger('setModo',[modo]);
      
      AUX.GET(url,{id_operador: id_operador},function(operador){
        render(operador);
        M.trigger('setVisible');
        M.trigger('setReadonly');
        if(M.attr('data-modo') == 'NUEVO'){
          $M('[data-js-click-agregar-cuenta]').trigger('click');
        }
        M.modal('show');
      });
    });
        
    $M('[data-js-click-agregar-cuenta]').click(function(){
      const cidx = $M('[data-contenedor-cuentas] tr').length;
      $M('[data-contenedor-cuentas]').append(
        obtener_fila_cuenta(cidx,{
          nombre: '',
          dia_vencimiento: '',
          fin_de_semana: 'Lunes Próximo',
          interes_diario_simple: '',
          interes_mensual_compuesto: ''
        })
      );
      M.trigger('regenerarInputsFormatear');
    });
    
    $M('[data-js-click-agregar-canon-variable]').click(function(){
      const cidx = $M('[data-contenedor-canon-variable] tr').length;
      $M('[data-contenedor-canon-variable]').append(
        obtener_fila_cv(cidx,{})
      );
      M.trigger('regenerarInputsFormatear');
    });
    
    $M('[data-js-click-agregar-canon-fijo-mesas]').click(function(){
      const cidx = $M('[data-contenedor-canon-fijo-mesas] tr').length;
      $M('[data-contenedor-canon-fijo-mesas]').append(
        obtener_fila_cfm(cidx,{})
      );
      M.trigger('regenerarInputsFormatear');
    });
    
    $M('[data-js-click-agregar-canon-fijo-mesas-adicionales]').click(function(){
      const cidx = $M('[data-contenedor-canon-fijo-mesas-adicionales] tr').length;
      $M('[data-contenedor-canon-fijo-mesas-adicionales]').append(
        obtener_fila_cfma(cidx,{})
      );
      M.trigger('regenerarInputsFormatear');
    });
    
    M.on('click','[data-js-click-borrar-tr]',function(){
      $(this).closest('tr').remove();
      if($M('[data-contenedor-cuentas] tr').length == 0){
        $M('[data-js-click-agregar-cuenta]').trigger('click');
      }
    });
    
    $M('[data-js-click-submit-form]').click(function(e){
      const o = e.currentTarget;
      const select = $(o).attr('data-js-click-submit-form');
      const $form = $M(select);
      
      const aux = {};
      M.trigger('deformatearFormData',[$form.length? AUX.form_entries($form[0]) : {},aux]);
      const formData = aux.response;
      
      const ajax_params = JSON.parse($form.attr('data-ajax-params') ?? '{}') ?? {};
      ocultarErrorValidacion(M.find('[name]'));
      $.ajax({
        type: $form.attr('method'),
        url: $form.attr('action'),
        data: formData,
        ...ajax_params,
        success: function (data) {
          $('#pant_operadores [data-js-filtro-tabla]').trigger('buscar');
          AUX.mensajeExito(data?.mensaje ?? '');
          $(o).closest('.modal').modal('hide');
        },
        error: function (data) {
          const json = data.responseJSON ?? {};
          AUX.mensajeError(json?.mensaje ?? '');
          AUX.mostrarErroresNames($form,json);
          //@HACK: tengo que hacerlo manual porque el name de cada cuenta es cuentas[]
          for(const k in json){
            const arrk = k.split('.');
            if(arrk.length != 3) continue;
            for(const objname of ['cuentas','canon_variable','canon_fijo_mesas','canon_fijo_mesas_adicionales']){
              if(arrk[0] != objname) continue;
              const idx = arrk[1];
              const name = arrk[2];
              mostrarErrorValidacion(
                $form.find(`[name="${objname}[${idx}][${name}]"]`),
                json[k].join(', '),
                true
              );
            }
          }
          console.log(data);
        }
      });
    });
  });
});

$(function(){
  $('[data-js-modal-ver-cargar-grupo-operador]').each(function(){
    const  M = $(this);
    const $M = M.find.bind(M);
    const Mname = function(name,val,O=M){
      return O.find(`[name="${name}"]`).val(val ?? '');
    };
    
    const agregar_fila_operador = (oidx,o) => {
      const replace_str = $M('[data-molde-operador]').attr('data-molde-operador');
      const fila = $M('[data-molde-operador]').clone().removeAttr('data-molde-operador');
      fila.find('[data-name]').each(function(_,nobj){
        $(nobj).attr('name',$(nobj).attr('data-name').replaceAll(replace_str,oidx));
      });
      $M('[data-contenedor-operadores]').append(fila);
      for(const k in o){
        Mname(`operadores[${oidx}][${k}]`,o[k],fila);
      }
    };
    
    const render = function(grupo_operador,mantener_historial = false){
      ocultarErrorValidacion(M.find('[name]'));
      Mname('id_canon_grupo_operador',grupo_operador?.id_canon_grupo_operador);
      Mname('id_grupo_operador',grupo_operador?.id_grupo_operador);
      Mname('es_individual',grupo_operador?.es_individual ?? 0);
      Mname('nombre',grupo_operador?.nombre);
      Mname('codigo',grupo_operador?.codigo);
      Mname('abbr',grupo_operador?.abbr);
      Mname('color',grupo_operador?.color).trigger('change');
      
      $M('[data-contenedor-operadors]').empty();
      for(const oidx in (grupo_operador?.operadors ?? [])){
        agregar_fila_operador(oidx,grupo_operador.operadors[oidx]);
      }
      
      (mantener_historial?
         M.find('[data-js-select-historial]')
       : M.find('[data-js-select-historial]').empty())
       .append(
        (grupo_operador?.historial ?? []).map(function(h,hidx){
          const o = $('<option>');
          o.val(h.id_canon_grupo_operador);
          o.text(h.usuario + ' - '+h.created_at);
          o.data('instancia',h);
          return o;
        })
      );
      
      M.trigger('regenerarInputsFormatear')
      .trigger('formatearCampos');
    };
    
    M.on('render',function(e,data,mantener_historial){
      render(data,mantener_historial);
    });
    
    M.on('mostrar.modal',function(e,url,id_grupo_operador,modo){
      M.trigger('setModo',[modo]);
      
      AUX.GET(url,{id_grupo_operador: id_grupo_operador},function(grupo_operador){
        render(grupo_operador);
        M.trigger('setVisible');
        M.trigger('setReadonly');
        if(M.attr('data-modo') == 'NUEVO'){
          $M('[data-js-click-agregar-operador]').trigger('click');
        }
        M.modal('show');
      });
    });
        
    $M('[data-js-click-agregar-operador]').click(function(){
      const oidx = $M('[data-contenedor-operadores] tr').length;
      agregar_fila_operador(oidx,{
        id_operador: ''
      });
      M.trigger('regenerarInputsFormatear');
    });
    
    M.on('click','[data-js-click-borrar-tr]',function(){
      $(this).closest('tr').remove();
      if($M('[data-contenedor-operadores] tr').length == 0){
        $M('[data-js-click-agregar-operador]').trigger('click');
      }
    });
    
    $M('[data-js-click-submit-form]').click(function(e){
      const o = e.currentTarget;
      const select = $(o).attr('data-js-click-submit-form');
      const $form = $M(select);
      
      const aux = {};
      M.trigger('deformatearFormData',[$form.length? AUX.form_entries($form[0]) : {},aux]);
      const formData = aux.response;
      
      const ajax_params = JSON.parse($form.attr('data-ajax-params') ?? '{}') ?? {};
      ocultarErrorValidacion(M.find('[name]'));
      $.ajax({
        type: $form.attr('method'),
        url: $form.attr('action'),
        data: formData,
        ...ajax_params,
        success: function (data) {
          $('#pant_operadores [data-js-filtro-tabla]').trigger('buscar');
          AUX.mensajeExito(data?.mensaje ?? '');
          $(o).closest('.modal').modal('hide');
        },
        error: function (data) {
          const json = data.responseJSON ?? {};
          AUX.mensajeError(json?.mensaje ?? '');
          AUX.mostrarErroresNames($form,json);
          
          for(const k in json){
            if(k.substr(0,'operadores.'.length) != 'operadores.'){
              continue;
            }
            const oidx = k.match(/^operadores\.[0-9]+/gm)?.[0].substr('operadores.'.length);
            const name = k.substr('operadores.'.length+oidx.length+1);//+1 por el punto
            mostrarErrorValidacion(
              $form.find(`[name="operadores[${oidx}][${name}]"]`),
              json[k].join(', '),
              true
            );
          }
          console.log(data);
        }
      });
    });
  });
});

$(function(){
  $('[data-js-modal-canon-comportamiento-comun]').each(function(){
    const  M = $(this);
    const $M = M.find.bind(M);
    
    //Formatea correctamente los campos numericos
    M.find('form').on('keydown','input:not([data-js-texto-no-formatear-numero])',function(e){
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
    
    const filterFunction = function(attr){
      const check_params = {
        modo: M.attr('data-modo')
      };
      M.find('[data-check-param]').each(function(_,obj){
        check_params[$(obj).attr('name')] = $(obj).val()?.toUpperCase();
      });
            
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
          let result = true;
          for(const param in check_params){
            const check_val = check_params[param];
            const obj_val = obj[param] ?? undefined;
            result = result && (obj_val == '*' || obj_val === check_val || obj_val === undefined);
          }
          if(result){//Short-circuiteo al primero que sea matchee
            return result;
          }
        }
        return false;
      };
    };
        
    const setReadonly = function(){
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
      .filter(filterFunction('data-readonly'))
      .each(setReadOnlyObj(true));
    }
    
    const setVisible = function(){
      M.find('[data-modo-mostrar]').hide().filter(filterFunction('data-modo-mostrar')).show();
    }
    
    M.on('setModo',function(e,modo){
      M.attr('data-modo',modo.toUpperCase());
    });
    
    M.on('setVisible',setVisible);
    M.on('setReadonly',setReadonly);
        
    let inputs_a_formatear = {};
    //Agrega todos los inputs para des/formatear
    M.on('regenerarInputsFormatear',function(e){
      inputs_a_formatear = {};
      const inpts = M.find('input[name]:not([data-js-texto-no-formatear-numero])')
      inpts.each(function(_,iobj){
        inputs_a_formatear[iobj.getAttribute('name')] = $(iobj);
      });
    });
    M.on('formatearCampos',function(e,inpts = null){//Saca los 0 de sobra a la derecha
      //Para verlos en debug usar algo tipo .css('color','red');
      for(const name in inputs_a_formatear){
        const i = inputs_a_formatear[name];
        if(i.is('[data-js-formatear-año-mes]')){
          i.val(i.val().substr(0,'YYYY-MM'.length));
        }
        else{
          i.val(formatter(i.val()));
        }
      }
    });
    M.on('deformatearFormData',async function(e,obj,responseobj){
      const ret = {};
      for(const k in obj){
        let val = obj[k];
        if(k in inputs_a_formatear){
          const i = inputs_a_formatear[k];
          if(i.is('[data-js-formatear-año-mes]')){
            val = val+'-01';
          }
          else{
            val = deformatter(val);
          }
        }
        ret[k] = val;
      }
      responseobj.response = ret;
    });
    
    //Limpia valores dependientes ANTES de recalcular
    M.find('form').on('change','[name]',function(e){//@TODO: bindear directo
      const tgt = $(e.currentTarget);
      const form = tgt.closest('form');
      
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
    });
    
    M.find('form[data-js-recalcular]').on('change','[name]',function(e){
      $(e.currentTarget).closest('form[data-js-recalcular]').trigger('recalcular');
    });
    
    M.find('form[data-js-recalcular]').on('recalcular',function(e){
      const form = $(e.currentTarget);
      const aux = {};
      M.trigger('deformatearFormData',[AUX.form_entries(form[0]),aux]);
      const fd = aux.response;
      
      AUX.POST(form.attr('data-js-recalcular'),fd,
        function(data){
          form.trigger('recalculado',data);
        },
        function(data){
          form.trigger('recalculado-error',data?.responseJSON ?? {});
        }
      );
    });
    
    //No permite cambiar el valor de un select con Tab y flechas
    M.find('form').on('focus','select[readonly],input[type="color"]',function(e){
      const tgt = $(e.currentTarget);
      const form  = tgt.closest('form');
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
    //Enter con un link focuseado
    M.find('form').on('keypress','a:focus',function(e){
      if(e.which == 13){//Enter
        $(e.currentTarget).click();
      }
    });
    //Mostrar dependencia al hovear
    M.find('form').on('mouseenter','[data-depende]',function(e){
      $(e.currentTarget).attr('data-depende').split(',').forEach(function(name){
        M.find('form').find(`[name="${name}"]`).addClass('mostrar_dependencia');
      });
    });
    M.find('form').on('mouseleave','[data-depende]',function(e){
      $(e.currentTarget).attr('data-depende').split(',').forEach(function(name){
        M.find('form').find(`[name="${name}"]`).removeClass('mostrar_dependencia');
      });
    });
    //Abrir link de hermano
    M.find('form').on('click','[data-js-click-abrir-val-hermano]',function(e){
      const tgt = $(e.currentTarget);
      const sibling_val = tgt.siblings(tgt.attr('data-js-click-abrir-val-hermano')).val();
      window.open(sibling_val,'_blank');
    });
    
    M.find('[data-js-change-agregar-alpha]').change(function(e){
      const tgt = $(e.currentTarget);
      const ALPHA = tgt.attr('data-js-change-agregar-alpha');
      let val = tgt.val();
      {
        const fakeDiv = document.createElement('div');
        fakeDiv.style.color = val;
        document.body.append(fakeDiv);
        const rgbStr = window.getComputedStyle(fakeDiv).color.replaceAll(/( |\t|\n|\r|\f|\v)/g,'');//Le saco los espacios
        document.body.removeChild(fakeDiv);
        const numRegex = '(\\+|-)?([0-9]+\\.?[0-9]*|[0-9]*\\.?[0-9]+)';
        const fRegex = '((0|1)\\.?[0-9]*|\\.[0-9]+)';
        const rgbRegex = new RegExp('rgb\\('+numRegex+','+numRegex+','+numRegex+'\\)','gi');
        const rgbaRegex = new RegExp('rgba\\('+numRegex+','+numRegex+','+numRegex+','+numRegex+'\\)','gi');
        const rgbafRegex = new RegExp('rgba\\('+numRegex+','+numRegex+','+numRegex+','+fRegex+'\\)','gi');
        const clearTextRegex = /(r|g|b|a|\(|\))/g;
        const f255toHex = (s) => {
          return Math.min(Math.max(Math.round(parseFloat(s)),0),255).toString(16).padStart(2,'0').toUpperCase();
        };
        const fatoHex = (s) => {
          return Math.min(Math.max(Math.round(parseFloat(s)*255),0),255).toString(16).padStart(2,'0').toUpperCase();
        };
        //Mi navegador me devuelve negro si seteo un color en hex con alfa #RRGGBBAA
        //Asi que lo seteo sin esto
        const CON_ALPHA = ALPHA.length > 0;
        if(rgbStr == rgbStr.match(rgbRegex)?.[0]){
          val = '#'+(rgbStr.replaceAll(clearTextRegex,'').split(',').map(f255toHex).join(''))+ALPHA;
        }
        else if(rgbStr == rgbStr.match(rgbaRegex)?.[0]){
          val = '#'+(rgbStr.replaceAll(clearTextRegex,'').split(',').map(f255toHex).slice(0,CON_ALPHA? 4 : 3).join(''));
        }
        else if(rgbStr == rgbStr.match(rgbafRegex)?.[0]){
          val = '#'+(rgbStr.replaceAll(clearTextRegex,'').split(',').map(function(s,idx,arr){
            if(idx < (arr.length-1)){
              return f255toHex(s);
            }
            return fatoHex(s);
          }).slice(0,CON_ALPHA? 4 : 3).join(''));
        }
        else {
          val = '#000000'+ALPHA;
        }
      }
      tgt.val(val).attr('value',val);
    });
    
    M.find('[data-js-select-historial]').change(function(e){
      const tgt = $(e.currentTarget);
      M.attr('data-render',1);
      M.trigger('render',[tgt.find('option:selected').data('instancia'),true]);
    });
  });
});


$(function(){
  $('#pant_agrupamientos').each(function(_,pant_obj){
    const pant = $(pant_obj);
    
    pant.find('[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
      ret.data.forEach(function(obj){
        const fila = molde.clone();
        Object.keys(obj).forEach(function(k){
          fila.find('.'+k).text(obj[k]);
        });
        
        const id_k = fila.attr('data-table-id');
        const id = obj[id_k];
        fila.find('button').val(id);
        
        tbody.append(fila);
      });
      
      tbody.find('[data-js-click-editar-agrupamiento]').click(function(e){
        const tgt = $(e.currentTarget);
        $('[data-js-modal-editar-agrupamiento]').trigger('mostrar.modal',[tgt.attr('data-js-click-editar-agrupamiento'),tgt.val(),'EDITAR']);
      });
    });
  });
});

import "/js/lib/vis-network.js";

$(function(){
  $('[data-js-modal-editar-agrupamiento]').each(function(){
    const  M = $(this);
    const $M = M.find.bind(M);
    const Mname = function(name,val,O=M){
      return O.find(`[name="${name}"]`).val(val ?? '');
    };
  
    const container = M.find('[data-grafo-agrupamiento]')?.[0];

    // Define DAG nodes
    const nodes = new vis.DataSet([]);
    let nextNodeId = 1;

    // Define directed edges (from parent to child)
    const edges = new vis.DataSet([]);
    
    // Configure for a DAG visual layout
    const options = {
      layout: {
        hierarchical: {
          enabled: true,
          direction: 'UD',       // 'UD' (Top-to-Bottom), 'LR' (Left-to-Right)
          sortMethod: 'directed', // Organizes levels strictly by edge direction
          nodeSpacing: 150,
          levelSeparation: 120
        }
      },
      groups: {
        grupo_operador: {
          color: { background: '#e0a96d', border: '#8c592b', highlight: { background: '#f2c48d', border: '#8c592b' } },
          shape: 'box',
          font: { color: '#ffffff', face: 'arial', size: 14 }
        },
        superior: {
          color: { background: '#457b9d', border: '#1d3557', highlight: { background: '#a8dadc', border: '#1d3557' } },
          shape: 'ellipse',
          font: { color: '#ffffff', face: 'arial', size: 14 }
        },
        subcanon: {
          color: { background: '#2a9d8f', border: '#145249', highlight: { background: '#76c893', border: '#145249' } },
          shape: 'ellipse',
          font: { color: '#ffffff', face: 'arial', size: 14 }
        },
      },
      edges: {
        arrows: {
          to: { enabled: true, scaleFactor: 1 }
        },
        smooth: {
          type: 'cubicBezier',
          forceDirection: 'vertical',
          roundness: 0.4
        }
      },
      physics: false // Disabling physics keeps the DAG locked in rank order
    };

    const network = new vis.Network(container, { nodes, edges }, options);
        
    const render = function(agg,mantener_historial = false){
      ocultarErrorValidacion(M.find('[name]'));
      Mname('id_canon_subcanon_a_grupo',agg?.id_canon_subcanon_a_grupo);
      Mname('clave',agg?.clave);
      Mname('id_grupo_operador',agg?.id_grupo_operador);
      Mname('grupo_operador',agg?.grupo_operador);
      
      (mantener_historial?
         M.find('[data-js-select-historial]')
       : M.find('[data-js-select-historial]').empty())
       .append(
        (agg?.historial ?? []).map(function(h,hidx){
          const o = $('<option>');
          o.val(h.id_canon_subcanon_a_grupo);
          o.text(h.usuario + ' - '+h.created_at);
          o.data('instancia',h);
          return o;
        })
      );
      
      M.trigger('regenerarInputsFormatear')
      .trigger('formatearCampos');
    };
    
    M.on('render',function(e,data,mantener_historial){
      render(data,mantener_historial);
    });
    
    M.on('mostrar.modal',function(e,url,id_canon_subcanon_a_grupo,modo){
      M.trigger('setModo',[modo]);
      
      AUX.GET(url,{id_canon_subcanon_a_grupo: id_canon_subcanon_a_grupo},function(agrupamiento){
        render(agrupamiento);
        M.trigger('setVisible');
        M.trigger('setReadonly');
        M.modal('show');
      });
    });
    
    M.find('[data-js-click-agregar-nodo]').click(function(e){
      const tgt = $(e.currentTarget);
      const labelTarget = M.find('[data-nuevo-nodo="label"]');
      const groupTarget = M.find('[data-nuevo-nodo="group"]');
      
      const label = labelTarget?.val()?.trim();
      if(label === undefined){
        throw 'Error al obtener el nombre del nuevo nodo';
      }
      
      const group = groupTarget?.val()?.trim();
      if(group === undefined){
        throw 'Error al obtener el nombre del nuevo nodo';
      }
      
      nodes.add({
        id: nextNodeId,
        label: (label+` (${nextNodeId})`).trim(),
        group: group
      });
      
      network.fit();
      labelTarget.val('');
      nextNodeId++;
    });
    
    M.find('[data-js-click-enlazar-nodo]').click(function(e){
      const tgt = $(e.currentTarget);
      const desde_hasta = M.find(tgt.attr('data-js-click-enlazar-nodo'));
      const desde_id = parseInt(desde_hasta.filter('[data-enlazar-nodo-id="desde"]')?.val()?.trim());
      const hasta_id = parseInt(desde_hasta.filter('[data-enlazar-nodo-id="hasta"]')?.val()?.trim());
      if(!isNaN(desde_id) && !isNaN(hasta_id) && nodes.get(desde_id) && nodes.get(hasta_id) && desde_id != hasta_id){
        edges.add({
          from: desde_id,
          to: hasta_id
        });
        network.fit();
      }
      desde_hasta.val('');
    });
    
    M.find('[data-js-click-borrar-objetos]').click(function(e){
      const selectedNodes = network.getSelectedNodes();
      const selectedEdges = network.getSelectedEdges();
      if (selectedNodes.length > 0) {
        nodes.remove(selectedNodes);
      } 
      if (selectedEdges.length > 0) {
        edges.remove(selectedEdges);
      }
      network.fit();
    });
    
    /*$M('[data-js-click-submit-form]').click(function(e){
      const o = e.currentTarget;
      const select = $(o).attr('data-js-click-submit-form');
      const $form = $M(select);
      
      const aux = {};
      M.trigger('deformatearFormData',[$form.length? AUX.form_entries($form[0]) : {},aux]);
      const formData = aux.response;
      
      const ajax_params = JSON.parse($form.attr('data-ajax-params') ?? '{}') ?? {};
      ocultarErrorValidacion(M.find('[name]'));
      $.ajax({
        type: $form.attr('method'),
        url: $form.attr('action'),
        data: formData,
        ...ajax_params,
        success: function (data) {
          $('#pant_operadores [data-js-filtro-tabla]').trigger('buscar');
          AUX.mensajeExito(data?.mensaje ?? '');
          $(o).closest('.modal').modal('hide');
        },
        error: function (data) {
          const json = data.responseJSON ?? {};
          AUX.mensajeError(json?.mensaje ?? '');
          AUX.mostrarErroresNames($form,json);
          
          for(const k in json){
            if(k.substr(0,'operadores.'.length) != 'operadores.'){
              continue;
            }
            const oidx = k.match(/^operadores\.[0-9]+/gm)?.[0].substr('operadores.'.length);
            const name = k.substr('operadores.'.length+oidx.length+1);//+1 por el punto
            mostrarErrorValidacion(
              $form.find(`[name="operadores[${oidx}][${name}]"]`),
              json[k].join(', '),
              true
            );
          }
          console.log(data);
        }
      });
    });*/
  });
});



$(function(){
  $('#pant_permisos').each(function(_,pant_obj){
    const pant = $(pant_obj);
    
    pant.find('[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
      ret.data.forEach(function(obj){
        const fila = molde.clone();
        Object.keys(obj).forEach(function(k){
          fila.find('.'+k).text(obj[k]);
        });
        
        const id_k = fila.attr('data-table-id');
        const id = obj[id_k];
        fila.find('button').val(id);
        
        tbody.append(fila);
      });
      
      tbody.find('[data-js-borrar]').click(function(e){
        const tgt = $(e.currentTarget);
        const fd = {};
        fd[tgt.closest('[data-table-id]').attr('data-table-id')] = tgt.val();
        
        $('[data-js-modal-eliminar]').trigger('mostrar',[{
          url: tgt.attr('data-js-borrar'),
          url_params: fd,
          mensaje: 'Esta seguro que desea eliminarlo',
          success: function(){pant.find('[data-js-filtro-tabla]').trigger('buscar');},
        }]);
      });
    });
    
    pant.find('[data-js-click-submit-form]').click(function(e){
      const o = e.currentTarget;
      const select = $(o).attr('data-js-click-submit-form');
      const $form = $(select);
      const formData = AUX.form_entries($form[0]);
      const ajax_params = JSON.parse($form.attr('data-ajax-params') ?? '{}') ?? {};
      ocultarErrorValidacion($form.find('[name]'));
      $.ajax({
        type: $form.attr('method'),
        url: $form.attr('action'),
        data: formData,
        ...ajax_params,
        success: function (data) {
          pant.find('[data-js-filtro-tabla]').trigger('buscar');
          AUX.mensajeExito(data?.mensaje ?? '');
          $form.find('[name]').val('');
        },
        error: function (data) {
          const json = data.responseJSON ?? {};
          AUX.mensajeError(json?.mensaje ?? '');
          AUX.mostrarErroresNames($form,json);
          console.log(data);
        }
      });
    });
  });
});

