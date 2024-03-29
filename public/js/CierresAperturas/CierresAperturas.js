import "/js/Components/FiltroTabla.js";
import "/js/Components/inputFecha.js";
import "./sorteador.js";
import "./eliminar.js";
import "./aperturasAPedido.js";
import "./verCierreApertura.js";
import "./validarApertura.js";
import "./desvincular.js";
import "./cmApertura_cmvCierre.js";
import "./sortearOusarBackup.js";

$(function() {
  $('.tituloSeccionPantalla').text('Cierres y Aperturas');

  $(document).on('click','[data-js-mostrar]',function(e){
    e.preventDefault();
    $($(this).attr('data-js-mostrar')).trigger(
      'mostrar',[JSON.parse($(this).attr('data-js-mostrar-params') ?? '{}')]
    );
  });
  
  $('[data-js-mostrar]').each(function(i,o){
    const modal = $($(o).attr('data-js-mostrar'));
    modal.on('success',function(e){
      $('.tab_content:visible [data-js-filtro-tabla]').trigger('buscar');
    });
  });
  
  $('[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
    ret.data.forEach(function(obj){
      const fila = molde.clone();
      Object.keys(obj).forEach(function(k){
        fila.find('.'+k).text(obj[k]);
      });
      fila.find('button').val(obj.id).attr('data-js-mostrar-params',JSON.stringify({
        id: obj.id
      })).filter(function(idx,o){
        const no_accesible_por_estado = !$(o).attr('data-estados').split(',').includes(obj.estado+'');
        const no_accesible_por_import = !$(o).attr('data-importado').split(',').includes(obj.importado+'');
        return no_accesible_por_estado || no_accesible_por_import;
      }).remove();
      fila.find('.estado').empty().append(
        $(`#iconosEstados i[data-linkeado=${obj.linkeado}][data-estado=${obj.estado}]`).clone()
      );
      tbody.append(fila);
    });
    $('[data-js-aperturas-sorteadas]').trigger('buscar');
  });
  
  $('[data-js-tabs]').each(function(_,tab_group){
    $(tab_group).find('[data-js-tab]').click(function(e){
      $(tab_group).find('[data-js-tab]').removeClass("active");
      $(this).addClass('active');
      const tab = $($(this).attr('data-tab-target')); //Find the href attribute value to
      tab.find('.filtro_tabla_filtro [name]').val('');//Limpio los filtros
      $('.tab_content').hide();
      tab.show().find('[data-js-filtro-tabla]').trigger('buscar');
    }).eq(0).click();
  });
});
