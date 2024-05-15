import '/js/Components/inputFecha.js';
import '/js/Components/modal.js';
import {AUX} from "/js/Components/AUX.js";

$(function(e){ $('[data-js-modal-maquinas-por-relevamiento]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  const nombreMeses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  
  M.on('mostrar',function(e,params){
    $M('[name]').val('').change();
    $M('[data-js-fecha]').data('datetimepicker').reset();
    $M('[data-js-forzando-carga]').hide().filter('[data-js-forzando-carga="0"]').show();
    ocultarErrorValidacion($M('[name]'));
    M.modal('show');
  });
  
  const borrar_maquinas_por_relevamiento = function(id){
    AUX.POST('relevamientos/eliminarCantidadMaquinasPorRelevamiento',
      {
        id_cantidad_maquinas_por_relevamiento: id
      },
      function(data){
        $M('[data-js-cambio-sector]').change();
      }
    );
  };
  
  $M('[data-js-cambio-sector]').on('cambioSectores change',function(e){
    $M('[data-js-maquinas-por-defecto]').text('-');
    $M('[data-js-maquinas-temporales]').hide().find('tbody').empty();
    const id_sector = $(this).val();
    if(id_sector == '' || id_sector == null) return;
    
    AUX.GET('relevamientos/obtenerCantidadMaquinasPorRelevamiento/' + id_sector,{},function(data){
      data.forEach(function(valor){
        //MÁQUINAS POR DEFECTO
        if(valor.fecha_desde == null && valor.fecha_hasta == null)
          return $M('[data-js-maquinas-por-defecto]').text(valor.cantidad);
        //MÁQUINAS TEMPORALES
        let fecha_desde = valor.fecha_desde.split("-");
        fecha_desde = `${fecha_desde[2]} ${nombreMeses[fecha_desde[1] - 1]} ${fecha_desde[0]}`;
        let fecha_hasta = valor.fecha_hasta.split("-");
        fecha_hasta = `${fecha_hasta[2]} ${nombreMeses[fecha_hasta[1] - 1]} ${fecha_hasta[0]}`;

        const fila = $M('[data-js-molde-maquinas-por-relevamiento]').clone().removeAttr('data-js-molde-maquinas-por-relevamiento');
        fila.find('.fecha_desde').text(fecha_desde);
        fila.find('.fecha_hasta').text(fecha_hasta);
        fila.find('.cantidad').text(valor.cantidad);
        fila.find('[data-js-click-borrar-fila]').click(function(){
          borrar_maquinas_por_relevamiento(valor.id_cantidad_maquinas_por_relevamiento);
        });
        $M('[data-js-maquinas-temporales]').show().find('tbody').prepend(fila);//Si hay máquinas temporales MOSTRAR TABLA
      });
    });
  });
  
  $M('[data-js-cambio-tipo]').change(function(e){
    const habilitar_dtps = $(this).val() == '2';
    
    $M('[data-js-fecha]').each(function(){
      if (!habilitar_dtps) $(this).data('datetimepicker').reset();
      this.disabled(!habilitar_dtps);
    });
    
    const sin_tipo = $(this).val() == '';
    const valor_por_defecto = $M('[data-js-maquinas-por-defecto]').text();
    $M('[name="cantidad_maquinas"]').val(sin_tipo? '' : (valor_por_defecto != '-'? valor_por_defecto : 1));
    $M('[data-js-deshabilitar-sin-tipo]').attr('disabled',sin_tipo);
  });
  
  $M('[data-js-generar]').click(function(e,forzar = false){
    const formData = AUX.form_entries($M('form')[0])
    ocultarErrorValidacion($M('[name]'))
    formData.forzar = ($(this).attr('data-js-generar') == 'forzar')+0;
    AUX.POST('relevamientos/crearCantidadMaquinasPorRelevamiento',formData,function(data){
      $M('[data-js-fecha]').each(function(){$(this).data('datetimepicker').reset();});
      $M('[data-js-cambio-sector]').change();
    },function(data){
      const json = data.responseJSON;
      if(json.ya_existe){
         $M('[data-js-forzando-carga]').hide().filter('[data-js-forzando-carga="1"]').show();
      }
      AUX.mostrarErroresNames(M,json ?? {});
    });
  });
  $M('[data-js-cambio-eliminar-forzar]').change(function(e){
    $M('[data-js-forzando-carga]').hide().filter('[data-js-forzando-carga="0"]').show();
  });
  $M('[data-js-cancelar]').click(function(e){
    M.trigger('mostrar');
  });
})});
