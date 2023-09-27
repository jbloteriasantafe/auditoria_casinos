import {AUX} from "./AUX.js";
import "/js/paginacion.js";

$(function(e){  
  const invalido = n => (n == null || isNaN(n));
  const extraerEstado = (div) => {
    return {
      pagina: div.find('.herramientasPaginacion').getCurrentPage(),
      tam: div.find('.herramientasPaginacion').getPageSize(),
      columna: div.find('[data-js-filtro-tabla-resultados] [data-js-sortable][data-js-state]').attr('data-js-sortable'),
      orden: div.find('[data-js-filtro-tabla-resultados] [data-js-sortable][data-js-state]').attr('data-js-state')
    };
  };
  
  $('[data-js-filtro-tabla]').each(function(){
    this.form_entries = function(){//Usado para sacar los atributos de busqueda desde afuera
      const form = $(this).find('[data-js-filtro-form]')[0];
      return AUX.form_entries(form);
    };
    this.form_data = function(){
      const estado = extraerEstado($(this));
      const paging = {
        page: estado.pagina,
        page_size: invalido(estado.tam)? 10 : estado.tam,
        sort_by: {
          columna: estado.columna,
          orden: estado.orden
        }
      };
      return {
        ...this.form_entries(),
        ...paging
      }
    };
  });
  
  $('[data-js-sortable]').each(function(col,s){
    $(s).append('<i class="fas fa-sort">');
  });
  
  $('[data-js-filtro-tabla]').on('buscar', function(e,page){
    e.preventDefault();
    
    const div = $(this);
    const clickIndice = (e,pageNumber) => {
      if(e == null) return;
      e.preventDefault();
      div.trigger('buscar',[pageNumber]);
    };
    
    const formData = div[0].form_data();
    formData.page = page ?? formData.page;
    
    const tbody = div.find('[data-js-filtro-tabla-resultados] tbody').empty();
    const molde = div.find('[data-js-filtro-tabla-molde] tr:first').clone();
    AUX.POST(div.find('[data-js-buscar]').attr('data-target'),formData,
      function (ret){
        div.find('.herramientasPaginacion').generarTitulo(formData.page,formData.page_size,ret.total,clickIndice);
        div.trigger('busqueda',[ret,tbody,molde]);
        div.find('.herramientasPaginacion').generarIndices(formData.page,formData.page_size,ret.total,clickIndice);
      },
      function(data){
        console.log(data);
        div.trigger('error_busqueda',[data,tbody,molde]);
      },
    );
  });
  
  $('[data-js-buscar]').on('click',function(e){
    const div = $(this).closest('[data-js-filtro-tabla]');
    div.trigger('buscar');
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
    div.trigger('buscar');
  });
});
