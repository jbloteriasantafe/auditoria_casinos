import {AUX} from "/js/Components/AUX.js";
import "/js/Components/modal.js";

$(function(e){
  const  M = $('[data-js-ver-cierre-apertura]');
  const $M = M.find.bind(M);
  
  function mostrarCierreApertura(url,sucess = function(data){}){
    AUX.GET(url,{},function(data){
      ['Cierre','Apertura'].forEach(function(tipo){
        const CA = data[tipo.toLowerCase()] ?? null;
        const  O = $M(`.datos${tipo}`);
        if(O === null) return $O.hide();
        O.show();
        O.find('.titulo_datos').text(CA? tipo.toUpperCase() : `-SIN ${tipo.toUpperCase()}-`);
        O.find('.datos').toggle(!!CA);
        O.find('.nro_mesa').text(`${CA?.mesa?.nombre} - ${CA?.moneda?.descripcion}`);
        O.find('.nombre_juego').text(CA?.juego?.nombre_juego ?? '-');
        O.find('.fecha').text(CA?.datos?.fecha ?? ' - ');
        O.find('.fiscalizador').text(CA?.fiscalizador?.nombre ?? ' - ');
        O.find('.hora_fin').text(CA?.datos?.hora_fin ?? ' - ');
        O.find('.hora_inicio').text(CA?.datos?.hora_inicio ?? ' - ');
        O.find('.total_pesos_fichas_c').val(CA?.datos?.total_pesos_fichas_c ?? 0);
        O.find('.total_anticipos_c').val(CA?.datos?.total_anticipos_c ?? ' - ');
        O.find('.cargador').text(CA?.cargador?.nombre ?? ' - ');
        O.find('.hora').text(CA?.datos?.hora ?? ' - ');
        O.find('.total_pesos_fichas_a').val(CA?.datos?.total_pesos_fichas_a ?? 0);
        O.find('.observacion').text(CA?.datos?.observacion ?? '');
        O.find('.tablaFichas tbody').empty();
        (CA?.detalles ?? []).forEach(function(ficha){
          const fila = O.find('.moldeFila').clone().removeClass('moldeFila');
          fila.find('.valor_ficha').text(ficha.valor_ficha ?? 0);
          fila.find('.cantidad_ficha').text(ficha.cantidad_ficha ?? 0);
          fila.find('.monto_ficha').text(ficha.monto_ficha ?? 0);
          O.find('.tablaFichas tbody').append(fila);
        });
      });
      $('[data-js-ver-cierre-apertura]').modal('show');
    });
  }
  
  $M('[data-js-ver-apertura]').on('mostrar', function(e,params){
    mostrarCierreApertura('aperturas/getApertura/'+params.id);
  });
  $M('[data-js-ver-cierre]').on('mostrar', function(e,params){
    mostrarCierreApertura('cierres/getCierre/'+params.id);
  });
});
