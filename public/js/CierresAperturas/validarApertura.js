import {AUX} from "./AUX.js";

$(function(e){
  const  M = $('[data-js-validar-apertura]');
  const $M = M.find.bind(M);
  
  M.on('mostrar',function(e,params) {
    const id_apertura_mesa = params.id;
    
    $M('.form-control,.observacion').val('');
    $M('.datosA,.datosC').find('h6 span').text('');
    $M('[name="id_cierre_mesa"] option[value!=""]').remove();
    $M('.datosC').hide();
    $M('.tablaFichas tbody tr').remove();
    $M('[data-js-validar-apertura-validar]').val(id_apertura_mesa).hide();

    AUX.GET('aperturas/obtenerApValidar/' + id_apertura_mesa,{},function(A){
      $M('.nro_mesa').text(A?.mesa?.nro_mesa);
      $M('.fecha_apertura').text(A?.apertura?.fecha);
      $M('.juego').text(A?.juego?.nombre_juego);
      $M('.casino').text(A?.casino?.nombre);
      $M('.hora').text(A?.apertura?.hora_format);
      $M('.fiscalizador').text(A?.fiscalizador?.nombre);
      $M('.cargador').text(A?.cargador?.nombre);
      $M('.tipo_mesa').text(A?.tipo_mesa?.descripcion);
      $M('.moneda').text(A?.moneda?.descripcion).val(A?.moneda?.id_moneda);
      $M('.total_pesos_fichas_a').val(A?.apertura?.total_pesos_fichas_a);
      $M('[name="id_cierre_mesa"]').append((A?.fechas_cierres ?? []).map(function(c) {   
        return $('<option>').val(c.id_cierre_mesa)
          .text(`${c.fecha} │ ${AUX.hhmm(c.hora_inicio)} a ${AUX.hhmm(c.hora_fin)} │ ${c.siglas}`);
      }));
      
      M.modal('show');
    });
  });
  
  $M('[data-js-validar-apertura-cambio-fecha]').change(function(e) {
    $M('.datosC,[data-js-validar-apertura-validar]').hide();
    $M('.tablaFichas tbody tr').remove();
    $M('.total_anticipos_c,.total_pesos_fichas_c').val('-');
    
    const id_cierre_mesa   = $(this).val();
    if(id_cierre_mesa.length == 0) return;
    const id_apertura_mesa = $M('[data-js-validar-apertura-validar]').val();
    
    AUX.GET(`aperturas/compararCierre/${id_apertura_mesa}/${id_cierre_mesa}`,{},function(data){
      $M('.hora_inicio').text(data?.cierre?.hora_inicio_format ?? '-');
      $M('.hora_fin').text(data?.cierre?.hora_fin_format ?? '-');
      $M('.fecha_cierre').text(data?.cierre?.fecha ?? '-');
      $M('.total_anticipos_c').val(data?.cierre?.total_anticipos_c ?? '-');
      $M('.total_pesos_fichas_c').val(data?.cierre?.total_pesos_fichas_c ?? '-');    
      
      let diferencia = 0;
      (data.fichas ?? []).forEach(function(f){
        const c = (data.detalles_cierre   ?? []).find(c => c.id_ficha == f.id_ficha)?.cantidad_ficha ?? 0;
        const a = (data.detalles_apertura ?? []).find(a => a.id_ficha == f.id_ficha)?.cantidad_ficha ?? 0;
        
        const fila = $M('[data-js-validar-apertura-molde-ficha]')
          .clone().removeAttr('data-js-validar-apertura-molde-ficha');
        fila.attr('data-id-ficha',f.id_ficha);
        fila.find('.valor_ficha').text(f.valor_ficha ?? '-');
        fila.find('.cierre_cantidad_ficha').text(c);
        fila.find('.apertura_cantidad_ficha').text(a);
        const hay_diferencia = (a != c)+0;
        fila.find(`.diferencia i[data-diferencia="${hay_diferencia}"]`).show();
        
        diferencia = diferencia || hay_diferencia;
        $M('.tablaFichas tbody').append(fila);
      });
      
      $M(`.datosC,[data-js-validar-apertura-validar][data-diferencia="${diferencia}"]`).show();
    })
  });
  
  $M('[data-js-validar-apertura-validar]').click(function(e) {
    AUX.POST('aperturas/validarApertura',
      {
        ...AUX.extraerFormData(M),
        id_apertura_mesa: $(this).val(),
        diferencia:  $(this).attr('data-diferencia')
      },
      function (data){
        M.modal('hide');
        AUX.mensajeExito('Apertura Validada correctamente.');
        M.trigger('success');
      },
      function(data){
        console.log(data);
        AUX.mostrarErroresNames(M,data.responseJSON ?? {});
        AUX.mensajeError();
      }
    );
  });
});
