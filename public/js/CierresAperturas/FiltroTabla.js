import {AUX} from "./AUX.js";
import "/js/paginacion.js";

$(function(e){  
  $('[data-js-sortable]').each(function(col,s){
    $(s).append('<i class="fas fa-sort">');
  });
  
  const extraerEstado = (div) => {
    return {
      pagina: div.find('.herramientasPaginacion').getCurrentPage(),
      tam: div.find('.herramientasPaginacion').getPageSize(),
      columna: div.find('[data-js-filtro-tabla-resultados] [data-js-sortable][data-js-state]').attr('data-js-sortable'),
      orden: div.find('[data-js-filtro-tabla-resultados] [data-js-sortable][data-js-state]').attr('data-js-state')
    };
  };
  const invalido = n => (n == null || isNaN(n));
  
  $('[data-js-buscar]').on('click', function(e,pagina,page_size,columna,orden){
    e.preventDefault();
    
    const div = $(this).closest('[data-js-filtro-tabla]');

    const clickIndice = (e,pageNumber,tam) => {
        if(e == null) return;
        e.preventDefault();
        const estado = extraerEstado(div);
        div.find('[data-js-buscar]').trigger('click',[
          pageNumber  ?? estado.pagina,
          tam         ?? estado.tam,
          estado.columna, estado.orden
        ]);
    };
    
    const estado = extraerEstado(div);
    const paging = {
      page: !invalido(pagina)? pagina 
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
    const tbody = div.find('[data-js-filtro-tabla-resultados] tbody').empty();
    const molde = div.find('[data-js-filtro-tabla-molde] tr:first').clone();
    AUX.POST($(this).attr('data-target'),
      {
        ...paging,
        ...AUX.extraerFormData(div.find('[data-js-filtro-tabla-filtro]'))
      },
      function (ret){
        div.find('.herramientasPaginacion').generarTitulo(paging.page,paging.page_size,ret.total,clickIndice);
        div.trigger('busqueda',[ret,tbody,molde]);
        div.find('.herramientasPaginacion').generarIndices(paging.page,paging.page_size,ret.total,clickIndice);
      },
      function(data){
        console.log(data);
        div.trigger('error_busqueda',[data,tbody,molde]);
      },
    );
  });
  
  $('[data-js-filtro-tabla]').on('buscar',function(e,pagina,page_size,columna,orden){
    $(this).find('[data-js-buscar]').trigger('click',[pagina,page_size,columna,orden]);
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
    const div = $(this).closest('[data-js-filtro-tabla]');
    div.find('[data-js-buscar]').click();
  });
});
