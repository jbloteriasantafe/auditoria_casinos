import {AUX} from "/js/Components/AUX.js";
import "/js/Components/modal.js";
import "/js/Components/inputFecha.js";

$(function(e){
  const _M = '[data-js-apertura-a-pedido]';
  const  M = $('[data-js-apertura-a-pedido]');
  const $M = M.find.bind(M);
  
  $(document).on('click',`${_M} [data-js-eliminar-aap]`,function(e){
    const fila = $(this).closest('tr');
    AUX.DELETE('/aperturas/borrarAperturaAPedido/'+$(this).val(),{},
      function(){
        fila.remove();
      },
      function(data){
        AUX.mensajeError();
        console.log(data.responseJSON);
      }
    );
  });

  const buscarAperturasAPedido = function(success = function(){}){
    const tabla = $M('[data-js-tabla] tbody').empty();
    const molde = $M('[data-js-molde]');
    tabla.find('tbody').empty();
    AUX.GET('/aperturas/buscarAperturasAPedido',{},
      function(aaps){
        aaps.forEach(function(aap){
          const fila = molde.clone().removeAttr('data-js-molde');
          Object.keys(aap).forEach(function(k){
            fila.find('.'+k).text(aap[k]);
          });
          fila.find('button').val(aap.id_apertura_a_pedido);
          tabla.append(fila);
        });
        success();
      },
      function(data){
        AUX.mensajeError();
        console.log(data.responseJSON);
      }
    );
  }
  
  M.on('mostrar',function(e,params){
    $M('[data-js-juego] option').removeAttr('selected').eq(0).attr('selected','selected').change();
    $M('[data-js-fecha]').each(function(){
      $(this).data('datetimepicker').reset();
    });
    ocultarErrorValidacion($M('input,select'));
    buscarAperturasAPedido(() => M.modal('show'));
  });

  $M('[data-js-juego]').change(function(e){
    $M('[data-js-mesa]').generarDataList("/aperturas/obtenerMesasPorJuego/" + $(this).val(), 'mesas', 'id_mesa_de_panio', 'nro_mesa', 1);
  });

  $M('[data-js-agregar]').click(function(e){
    AUX.POST('/aperturas/agregarAperturaAPedido',
      AUX.extraerFormData(M),
      function(data){
        $M('[data-js-juego]').change();//Limpia el input de nro de mesa
        buscarAperturasAPedido();
      },
      function(data){
        const response = data.responseJSON;
        console.log(response);
        AUX.mostrarErroresNames(M,data.responseJSON);
      }
    );
  });
});
