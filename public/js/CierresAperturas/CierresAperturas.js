import {AUX} from "./AUX.js";
import "./generar.js";
import "./eliminar.js";
import "./aperturasAPedido.js";
import "./verCierreApertura.js";
import "./validarApertura.js";
import "./desvincular.js";
import "./cmApertura_cmvCierre.js";


$(function() {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $('.tituloSeccionPantalla').text('Cierres y Aperturas');
  $('[data-js-fecha]').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
  });
  
  $(document).on('click','[data-js-mostrar]',function(e){
    e.preventDefault();
    $($(this).attr('data-js-mostrar')).trigger(
      'mostrar',[JSON.parse($(this).attr('data-js-mostrar-params') ?? '{}')]
    );
  });
  
  $('[data-minimizar]').click(function() {
    const minimizar = $(this).data('minimizar');
    $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
    $(this).data("minimizar", !minimizar);
  });

  $('.modal').on('shown.bs.modal',function(){
    const min = $(this).find('[data-minimizar]');
    if(!min.data('minimizar')){
      setTimeout(function(){
        min.click();
      },250);
    }
  });
});

/*
########  ##     ##  ######   ######     ###    ########  
##     ## ##     ## ##    ## ##    ##   ## ##   ##     ## 
##     ## ##     ## ##       ##        ##   ##  ##     ## 
########  ##     ##  ######  ##       ##     ## ########  
##     ## ##     ##       ## ##       ######### ##   ##   
##     ## ##     ## ##    ## ##    ## ##     ## ##    ##  
########   #######   ######   ######  ##     ## ##     ## 
 */
$(function(e){
  $('[data-js-sortable]').each(function(col,s){
    $(s).append('<i class="fas fa-sort">');
  });
    
  const extraerEstado = (tab) => {    
    return {
      pagina: tab.find('.herramientasPaginacion').getCurrentPage(),
      tam: tab.find('.herramientasPaginacion').getPageSize(),
      columna: tab.find('.tablaResultados [data-js-sortable][data-js-state]').attr('data-js-sortable'),
      orden: tab.find('.tablaResultados [data-js-sortable][data-js-state]').attr('data-js-state')
    };
  };
  const clickIndice = (tab,e,pageNumber,tam) => {
      if(e == null) return;
      e.preventDefault();
      const estado = extraerEstado(tab);
      tab.find('[data-js-buscar]').trigger('click',[
        pageNumber  ?? estado.pagina,
        tam         ?? estado.tam,
        estado.columna, estado.orden
      ]);
  };
  const invalido = n => (n == null || isNaN(n));
  
  $('[data-js-buscar]').on('click', function(e,pagina,page_size,columna,orden){
    e.preventDefault();

    const tab = $(this).closest('.tab_content');
    const estado = extraerEstado(tab);
    const paging = {
      page_number: !invalido(pagina)? pagina 
        : estado.pagina,
      page_size: !invalido(page_size)? page_size
        : (invalido(estado.tam)? 10 : estado.tam),
      sort_by: !invalido(columna) && !invalido(orden)? 
        {columna,orden}
        : {
          columna: estado.columna,
          orden: estado.orden
        }
    };
    
    tab.find('.tablaResultados tbody tr').remove();
    AUX.POST($(this).attr('data-target'),
      {
        ...paging,
        ...AUX.extraerFormData(tab.find('.filtro-busqueda-collapse'))
      },
      function (ret){
        const clickIndice_sin_tab = function(e,pageNumber,tam){
          return clickIndice(tab,e,pageNumber,tam);
        };
        tab.find('.herramientasPaginacion').generarTitulo(paging.page_number,paging.page_size,ret.total,clickIndice_sin_tab);
        tab.find('.tablaResultados tbody tr').remove();

        ret.data.forEach(function(obj){
          const fila = tab.find('.moldeFilaResultados').clone().removeClass('moldeFilaResultados');
          Object.keys(obj).forEach(function(k){
            fila.find('.'+k).text(obj[k]);
          });
          fila.find('button').val(obj.id).attr('data-js-mostrar-params',JSON.stringify({
            id: obj.id
          })).filter(function(idx,o){
            return !$(o).attr('data-estados').split(',').includes(obj.estado+'');
          }).remove();
          fila.find('.estado').empty().append(
            $(`#iconosEstados i[data-linkeado=${obj.linkeado}][data-estado=${obj.estado}]`).clone()
          );
          tab.find('.tablaResultados tbody').append(fila);
        });
        
        tab.find('.herramientasPaginacion').generarIndices(paging.page_number,paging.page_size,ret.total,clickIndice_sin_tab);
      },
      function(data){
        console.log(data);
      },
    );
  });

  $('[data-js-sortable]').click(function(e){
    const not_sorted  = !$(this).attr('data-js-state');
    const down_sorted = $(this).attr('data-js-state') == 'desc';
    const tabla       = $(this).closest('table');
    tabla.find('[data-js-state]').removeAttr('data-js-state')
    .find('i').removeClass().addClass('fa fa-sort');
    if(not_sorted){
      $(this).attr('data-js-state','desc').find('i').addClass('fa fa-sort-down');
    }
    else if(down_sorted){
      $(this).attr('data-js-state','asc').find('i').addClass('fa fa-sort-up');
    }
    $(this).closest('.tab_content').find('[data-js-buscar]').click();
  });
  
  $('[data-js-tabs]').each(function(_,tab_group){
    $(tab_group).find('[data-js-tab]').click(function(e){
      $(tab_group).find('[data-js-tab]').removeClass("active");
      $(this).addClass('active');
      const tab = $($(this).attr('data-tab-target')); //Find the href attribute value to
      tab.find('.filtro-busqueda-collapse [name]').val('');//Limpio los filtros
      tab.find('[data-js-buscar]').click();
      $('.tab_content').hide();
      tab.show();
      
      setTimeout(function(){//@HACK: nose porque scrollea cuando tabea...
        $(tab_group).find('[data-js-tab]').get(0).scrollIntoView();
      },50);
    }).eq(0).click();
  });
});
