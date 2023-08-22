import {AUX} from "../CierresAperturas/AUX.js";

$(function(e){
  const  M = $('[data-js-modificar-minimo]');
  const $M = M.find.bind(M);
  
  M.on('mostrar',function(e){
    ocultarErrorValidacion(
      $M('[name]').val('')
    );
    $M('[name="id_casino"]').val($M('[name="id_casino"] option:first').val());
    $M('[name="id_moneda"]').val($M('[name="id_moneda"] option:first').val());
    $M('[data-js-cambio-casino]').change();
    M.modal('show');
  });
  
  $M('[data-js-cambio-casino],[data-js-cambio-moneda]').change(function(e,id_juego_mesa = null){
    const fd = AUX.extraerFormData(M);
    $M('[name="id_juego_mesa"]').empty().data('apuestas',[]);
    $M('.valores').hide();
    AUX.GET('apuestas/obtenerRequerimientos/' + fd.id_casino + '/' + fd.id_moneda,{}, function(data){
      $M('[name="id_juego_mesa"]').empty().data('apuestas',data?.apuestas ?? [])
      .append(
        data?.juegos?.map(j => {
          return $('<option>').val(j.id_juego_mesa).text(j.nombre_juego);
        }) ?? []
      )
      .val(id_juego_mesa ?? $M('[name="id_juego_mesa"] option:first').val());
      $M('[data-js-cambio-juego]').change();
    });
  });
  
  $M('[data-js-cambio-juego]').change(function(e){
    const id_juego_mesa = $M('[name="id_juego_mesa"]').val();
    const apuestas = $M('[name="id_juego_mesa"]').data('apuestas') ?? [];
    const ap = apuestas.find(a => a.id_juego_mesa == id_juego_mesa);
    $M('[name="apuesta_minima"]').val(ap?.apuesta_minima ?? '');
    $M('[name="cantidad_requerida"]').val(ap?.cantidad_requerida ?? '');
    $M('.valores').show();
  });

  $M('[data-js-guardar]').click(function(e){
    $M('.valores').show();
    const formData = AUX.extraerFormData(M);
    AUX.POST('apuestas/modificarRequerimiento',formData,
      function (data){
        AUX.mensajeExito('Cambios GUARDADOS.');
        $M('[data-js-cambio-casino]').trigger('change',[formData.id_juego_mesa]);
      },
      function(data){
        AUX.mostrarErroresNames(M,data.responseJSON ?? {});
      }
    );
  });
});
