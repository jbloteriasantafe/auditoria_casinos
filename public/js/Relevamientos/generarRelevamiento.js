import '/js/Components/inputFecha.js';
import '/js/Components/modal.js';
import {AUX} from "/js/Components/AUX.js";
import './cambioCasinoSelectSectores.js';

$(function(e){ $('[data-js-modal-generar-relevamiento]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  let estado = null;
  let paso = null;
  
  const setear_estado_paso = function(){
    $M('[data-estado]').hide().filter(function(idx,obj){
      const estado_visible = $(obj).attr('data-estado').split(',').includes(estado+'');
      const paso_visible = $(obj).attr('data-paso').split(',').includes(paso+'');
      return estado_visible && paso_visible;
    })
    .each(function(idx,obj){
      $(obj).css('display',$(obj).attr('data-display') ?? 'block');
    });
  }
    
  M.on('mostrar',function(e){
    $M('[name]').val('').change();
    $M('[name="cantidad_fiscalizadores"]').val(1);
    ocultarErrorValidacion($M('[name]'));
    $M('[data-js-maquinas-a-pedido]').hide();
    $M('[data-js-cambio-resetear-estado]').change();
    M.modal('show');
  });
  
  $M('[data-js-cambio-resetear-estado]').change(function(e){
    estado = 'SIN_CHECKEAR';
    paso   =  0;
    setear_estado_paso();
  });
    
  $M('[data-js-cambio-sector]').on('change cambioSectores',function(e,sectores){
    $M('[name="cantidad_maquinas"]').val('');
    $M('[data-js-maquinas-a-pedido]').hide();
    
    const id_sector = $(this).val();
    const fecha = $M('[data-js-fecha-hoy]').val();
    if(id_sector == null || fecha == null || id_sector == '' || fecha == '')
      return;
    
    //@TODO: Unir en una sola API call? usar await fetch?
    AUX.GET("relevamientos/obtenerCantidadMaquinasRelevamientoHoy/" + id_sector,{}, function(cantidad){
      $M('[name="cantidad_maquinas"]').val(cantidad);
      
      AUX.GET("relevamientos/obtenerMtmAPedido/" + fecha + "/" + id_sector,{}, function(cmtm){
        $M('[data-js-maquinas-a-pedido]').toggle(cmtm > 0)
        .find('[data-js-maquinas-a-pedido-cantidad]')
        .text(`Este sector tiene ${cmtm} máquina${cmtm>1? 's' : ''} a pedido.`);
        
        AUX.GET('relevamientos/existeRelevamiento/' + id_sector,{}, function(est){
          estado = est;
          setear_estado_paso();
        });
      });
    });
  });
  
  $M('[data-js-pasar-paso]').click(function(e){
    paso += 1;
    setear_estado_paso();
  });
  
  $M('[data-js-cancelar]').click(function(e){
    paso = 0;
    setear_estado_paso();
  });
  
  const generar_descargar_zip = function(url){
    const formData = AUX.form_entries($M('form')[0]);
    
    ocultarErrorValidacion($M('[name]'));
    paso += 1;
    setear_estado_paso();
                
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
    $.ajax({
      type: "POST",
      url: 'relevamientos/'+url,
      data: formData,
      dataType: 'json',
      success: function (data) {
        M.trigger('creado',[data.url_zip]);
      },
      error: function (data) {
        const response = data.responseJSON;
        AUX.mostrarErroresNames(M,response ?? {});
      },
      complete: function(jqXHR,textStatus){
        paso = 0;
        setear_estado_paso();
      }
    });
  };
  
  $M('[data-js-generar-posta-descargar]').click(function(e){
    generar_descargar_zip($(this).attr('data-js-generar-posta-descargar'));
  });
})});
