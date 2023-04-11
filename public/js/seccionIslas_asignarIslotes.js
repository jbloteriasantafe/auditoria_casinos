
function mensajeError(msg){
  $('#mensajeError .textoMensaje').empty();
  $('#mensajeError .textoMensaje').append($('<h4>'+msg+'</h4>'));
  $('#mensajeError').hide();
  setTimeout(function() {
    $('#mensajeError').show();
  }, 250);
}
  
$('#btn-islotes').click(function(e){
  e.preventDefault();
  $('#casinoIslotes').val($('#casinoIslotes option').eq(0).val()).change()
  .attr('disabled',$('#casinoIslotes option').length == 1);//Si tiene 1 solo deshabilito seleccionar 
  $('#escondido_pre_insertar').empty();
  $('.seleccionado').removeClass('seleccionado');
  $('.sombreado').removeClass('sombreado');
  $('#modalAsignarIslotes').modal('show');
});
  
function crearIslote(nro_islote,islas){
  const islote = $('#moldeIslote').clone().removeAttr('id');
  islote.find('.nro_islote').empty().append(nro_islote == 'SIN_NRO_ISLOTE'? '&nbsp;' : nro_islote)
  .css('background',nro_islote == 'SIN_NRO_ISLOTE'? '#fcc' : '');
  for(const nro_isla_idx in islas){
    const nro_isla = islas[nro_isla_idx];
    const isla = $('#moldeIslaIslote').clone().removeAttr('id');
    isla.find('.nro_isla').text(nro_isla);
    islote.find('.islas').append(isla);
  }
  return islote;
}
  
$('#casinoIslotes').change(function(e){
  e.preventDefault();
  $('#sectores').empty();
  $.get('/islas/buscarIslotes/'+$(this).val(),function(sectores){
    for(const sectores_idx in sectores){
      const sector = $('#moldeSector').clone().removeAttr('id');
      const sector_obj = sectores[sectores_idx];
      sector.find('.nombre_sector').text(sector_obj['descripcion']);
      sector.data('id_sector',sector_obj['id_sector'] == 'SIN_SECTOR'? '' : sector_obj['id_sector'] );
      const islotes = sector_obj['islotes'];
      for(const islotes_idx in islotes){
        const islotes_obj = islotes[islotes_idx];
        sector.find('.islotes').append(crearIslote(islotes_obj['nro_islote'],islotes_obj['islas']));
      }
      $('#sectores').append(sector);
    }
  });
});
  
$(document).on('mousedown','.asignar_isla',function(e){
  if($('.seleccionado').length == 0 && e.which == 1){
    e.preventDefault();//evitar que seleccione texto
    $(this).addClass('seleccionado').closest('.asignar_islote').addClass('sombreado');
  }
});
  
$(document).on('mousedown','.asignar_islote',function(e){
  //No permito seleccionar el "SIN_NRO_ISLOTE" ya que es solo para mostrar islas sin asignar
  if($('.seleccionado').length == 0 && e.which == 1 && $(this).find('.nro_islote').text().trim().length > 0){
    e.preventDefault();//evitar que seleccione texto
    $(this).addClass('seleccionado').closest('.asignar_sector').addClass('sombreado');
    $('#modalAsignarIslotes .asignar_borrar_islote').addClass('sombreado');
  }
});

$(document).on('mousedown','.asignar_sector h3',function(e){
  if($('.seleccionado').length == 0 && e.which == 1){
    e.preventDefault();//evitar que seleccione texto
    $(this).closest('.asignar_sector').addClass('seleccionado');
  }
});
  
$(document).on('mouseenter','#modalAsignarIslotes div',function(){
  if($('.seleccionado').length == 0) return;
  let div = $();
  if($('.seleccionado').hasClass('asignar_isla')){
    div = $(this).filter(".asignar_islote");
  }
  if($('.seleccionado').hasClass('asignar_islote')){
    div = $(this).filter('.asignar_sector');
  }
  div.addClass('sombreado');
});
  
$(document).on('mouseleave','.sombreado',function(){    
  if(!$(this).hasClass('asignar_borrar_islote')){
    $(this).removeClass('sombreado');
  }
});
  
function mover_seleccionado_a_div(seleccionado,div,divpadre,x,y){
  /*
  sectores
    asignar_sector (divpadre)
      hijos
        asignar_islote (div),(divpadre)
          hijos
            asignar_isla (div)
  */

  const insertar = function(div_base){
    //No hacerlo si es el mismo sino al hacer detach() no puede insertarAfter/Before (no tiene padre) y termina borrandose
    if(div_base[0] == seleccionado[0]) return;
    const rect = div_base[0].getBoundingClientRect();//Averiguo si fue a la izquierda o derecha del elemento
    const mitad = (rect.left+rect.right)/2.;
    if(x >= mitad) seleccionado.detach().insertAfter(div_base);
    else           seleccionado.detach().insertBefore(div_base); 
  }
  const slot_a_insertar = divpadre.find('.hijos').first();
  //Si solto el click adentro del div
  if(div.length == 1){
    insertar(div);
  }
  //Si solto el click en el divpadre pero por fuera de cualquier div
  else if(div.length == 0 && slot_a_insertar.children().length > 0){
    //Encuentro el div mas cercano
    let min_dist = Infinity;
    let obj = null;
    slot_a_insertar.children().each(function(){
      const obj_rect = this.getBoundingClientRect();
      const d = distancia_a_caja(obj_rect,x,y);
      if(d < min_dist){
        min_dist = d;
        obj = this;
      }
    });
    insertar($(obj));
  }
  //Si solto el click en un divpadre sin hijos
  else if(div.length == 0 && divpadre.length == 1 && slot_a_insertar.children().length == 0){
    slot_a_insertar.append(seleccionado.detach());
  }
}
  
function movidoReciente(obj){
  obj.addClass('movido_reciente');
  setTimeout(function(){
    obj.removeClass('movido_reciente');//le saco la clase para que pueda volver a hacer el efecto 
  },2000);
}

function merge_islotes(a_borrar,a_agregar){
  const ultima_isla = a_agregar.find('.asignar_isla').last();
  a_borrar.find('.asignar_isla').each(function(){
    mover_seleccionado_a_div($(this),ultima_isla,a_agregar,window.screen.width,window.screen.height);
    movidoReciente($(this));
  });
  a_borrar.remove();
}
  
$(document).on('mouseup','*',function(e){
  const seleccionado = $('.seleccionado');
  if(seleccionado.length == 0 || e.which != 1) return;
  const elementos_en_el_mouse = $(document.elementsFromPoint(e.pageX,e.pageY));
  const isla_mouse_arriba   = elementos_en_el_mouse.filter('.asignar_isla').eq(0);//solto en una isla
  const islote_mouse_arriba = elementos_en_el_mouse.filter('.asignar_islote').eq(0);//solto en un islote
  const sector_mouse_arriba = elementos_en_el_mouse.filter('.asignar_sector').eq(0);//solto en un sector
  const borrar_mouse_arriba = elementos_en_el_mouse.filter('.asignar_borrar_islote').eq(0);//solto en el cesto de borrar

  if(seleccionado.hasClass('asignar_isla') && (isla_mouse_arriba.length + islote_mouse_arriba.length) > 0){//Si encontro isla y/o islote
    mover_seleccionado_a_div(seleccionado,isla_mouse_arriba,islote_mouse_arriba,e.pageX,e.pageY);
  }
  else if(seleccionado.hasClass('asignar_islote') && (islote_mouse_arriba.length + sector_mouse_arriba.length) > 0){//Si encontro islote y/o sector
    const nro_islote_seleccionado = seleccionado.find('.nro_islote').text().trim();
    const ya_esta_islote = sector_mouse_arriba.find('.asignar_islote').filter(function(){
        return $(this).find('.nro_islote').text().trim() == nro_islote_seleccionado;
    });
    if(ya_esta_islote.length > 0 && ya_esta_islote[0] != seleccionado[0]){//Mergear islote con el que ya esta
      merge_islotes(seleccionado,ya_esta_islote);
    }
    else{//Lo muevo al sector
      mover_seleccionado_a_div(seleccionado,islote_mouse_arriba,sector_mouse_arriba,e.pageX,e.pageY);
    }
  }
  else if(seleccionado.hasClass('asignar_islote') && borrar_mouse_arriba.length > 0){
    if(seleccionado.closest('#escondido_pre_insertar').length > 0){//Esta borrando uno nuevo
      seleccionado.remove();
    }
    else{//Esta borrando uno que ya existe en un sector
      let sin_nro_islote = seleccionado.parent().find('.asignar_islote').filter(function(){return $(this).find('.nro_islote').text().trim().length == 0;})
      if(sin_nro_islote.length == 0){
        sin_nro_islote = crearIslote('SIN_NRO_ISLOTE',[]);
        seleccionado.parent().append(sin_nro_islote);
        movidoReciente(sin_nro_islote);
      }
      merge_islotes(seleccionado,sin_nro_islote);
    }
  }
  else if(seleccionado.hasClass('asignar_sector') && sector_mouse_arriba.length > 0 && seleccionado[0] != sector_mouse_arriba[0]){
    seleccionado.detach().insertAfter(sector_mouse_arriba);
  }

  movidoReciente(seleccionado);
  $('.seleccionado').removeClass('seleccionado');
  $('.sombreado').removeClass('sombreado');
})
  
function distancia_a_caja(rect,px,py){
  //Retorna la distancia de (px,py) a una caja. Basado en https://www.iquilezles.org/www/articles/distfunctions2d/distfunctions2d.htm
  const centerx = (rect.left+rect.right)*0.5;
  const centery = (rect.top+rect.bottom)*0.5;
  const lx = Math.abs(rect.left-centerx);
  const ly = Math.abs(rect.bottom-centery);
  const dx = Math.abs(px-centerx) - lx;
  const dy = Math.abs(py-centery) - ly;
  const length = function(x,y){ return Math.sqrt(x*x+y*y); }
  return length(Math.max(dx,0.),Math.max(dy,0.)) + Math.min(Math.max(dx,dy),0.);
}
  
$('#agregarIslote').keyup(function(e){
  e.preventDefault();
  if(e.which == 13){//Agrego y limpio si toco enter
    if($(this).val() == parseInt($(this).val())){
      const islote = crearIslote($(this).val(),[]);
      $('#escondido_pre_insertar').append(islote);
      islote.addClass('seleccionado');
      $('#modalAsignarIslotes .asignar_borrar_islote').addClass('sombreado');
    }
    $(this).val("").change();
  }
});
  
$('#btn-aceptarIslotes').click(function(e){
  e.preventDefault();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  const sectores = $('#sectores .asignar_sector').map(function(_,sector){
    const islotes = $(sector).find('.asignar_islote').map(function(_,islote){
      return {
        nro_islote: $(islote).find('.nro_islote').text().trim(),
        islas: $(islote).find('.asignar_isla').map(function(_,isla){
          return $(isla).text().trim();
        }).toArray(),
      };
    })
    .toArray().filter(function(islote){
      return islote.islas.length > 0;
    });//Solo devuelvo los islotes que tienen islas

    return {
      id_sector: $(sector).data('id_sector'),
      islotes: islotes,
    };
  }).toArray().filter(function(sector){
    return sector.islotes.length > 0;
  });//Solo devuelvo los sectores que tienen islotes

  $.ajax({
    type: 'POST',
    url: '/islas/asignarIslotes',
    data: {
      id_casino: $('#casinoIslotes').val(),
      sectores: sectores,
    },
    dataType: 'json',
    success: function () {
      mensajeExito("modificar","Islotes y ordenes de relevamientos asignados");
      $('#casinoIslotes').change();
    },
    error: function (response) {
      const responseJSON = data.responseJSON.errors;
      const errores = [];
      for(const idx in responseJSON){
        errores.push(idx+": "+responseJSON[idx]);//@HACK: generar mensajes de errores usables para el usuario
      }
      mensajeError(errores.join("\n"));
    }
  });
})
