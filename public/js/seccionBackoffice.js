import {AUX} from "/js/Components/AUX.js";
import "/js/Components/FiltroTabla.js";
import "/js/Components/inputFecha.js";

function formatter(n){//Mismo que en Canon/index.js
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

$(function(){
  $('.tituloSeccionPantalla').text('Backoffice');
  
  $('[data-js-cambio-vista]').change(function(e){
    $('[data-vista]').hide();
    $(`[data-vista="${$(this).val()}"]`).show()
    .find('[data-js-filtro-tabla]').trigger('buscar');
  }).change();
  

  
  $('[data-js-filtro-tabla]').each(function(_,div){
    $(div).on('buscar',function(e){
      $(div).find('[data-js-cargando]').show();
    });
    $(div).on('error_busqueda',function(e){
      $(div).find('[data-js-cargando]').hide();
    });
    
    function val_format(tipo,val){
      if(val === null || val === undefined || val == '' || (typeof val == 'number' && isNaN(val)))
        return '';
      switch(tipo){
        case 'integer':
          return parseInt(val).toLocaleString();
        case 'numeric':
          return formatter(val);
        case 'numeric3d':
          return formatter(val);
      }
      return val;
    }
    
    $(div).on('busqueda',function(e,ret,tbody,molde){
      $(div).find('[data-js-cargando]').hide();
      ret.data.forEach(function(obj){
        const fila = molde.clone();
        Object.keys(obj).forEach(function(k){
          const td = fila.find('.'+k);
          const texto = (obj[k] ?? '')+'';
          const visible = texto.substring(0,100);
          
          td.text(visible);
          if(visible.length < texto.length){
            td.addClass('hover_borde_naranja')
            .append('...').attr('data-js-ver-mas', texto);
          }
        });
        tbody.append(fila);
      });
      tbody.find('[data-js-ver-mas]').on('mouseenter mouseleave',function(e){
        const t = $(this);
        const a_insertar = t.attr('data-js-ver-mas');
        const a_guardar = t.text();
        t.attr('data-js-ver-mas',a_guardar);
        t.text(a_insertar);
      });
    });
  });
  
  $('[data-js-descargar]').click(function(e){
    const descargando = $(this).find('[data-js-descargando]');
    descargando.show();
    const formData = {
      ...$('[data-js-filtro-tabla]:visible')[0].form_data(),
      completo: $(this).attr('data-descargar-completo')
    };
    AUX.POST(
      '/backoffice/descargar',
      formData,
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
        
        a.setAttribute('download', formData.vista+'-'+timestamp+'.csv');
        
        a.click();
      },
      function(data){
        descargando.hide();
        console.log(data);
        AUX.mensajeError();
      }
    );
  });
  
  $('[data-js-poner-default-al-vacio]').each(function(_,o){
    const val_al_cargar_html = $(o).val();
    $(o).change(function(e){
      if($(this).val().length == 0){
        $(this).val(val_al_cargar_html).change();
      }
    });
  });
});
