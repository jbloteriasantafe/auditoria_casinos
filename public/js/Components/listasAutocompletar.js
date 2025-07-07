$('[data-listas-autocompletar]').each(function(lidx,lObj){
  const L = $(lObj);
  const filtro_id_casino = $(L.attr('data-listas-autocompletar-sacar-id_casino'));
  const filtro_str       = $(L.attr('data-listas-autocompletar-sacar-str'));
  const origen_todas     = L.find('[data-lista-todas]');
  const origen_cas       = L.find('[data-lista-cas]');
  const origen_str       = L.find('[data-lista-str]');
  const origen_str_id    = origen_str.attr('id');
  const destino_output_id = $(L.attr('data-listas-autocompletar-poner-id'));
    
  filtro_id_casino.change(function(e){
    const id_casino = filtro_id_casino.val();
    
    const options = origen_todas.find('option').filter(`option[data-id_casino="${id_casino}"]`);
    
    origen_cas.empty().append(options.map(function(oidx,op){
      const op2 = $(op).clone();
      op2.val(op2.attr('data-str'));
      op2.text(op2.val());
      return op2[0];
    }));
    
    filtro_str.trigger('input');
  });

  filtro_str.on('input',function(e){
    const str  = filtro_str.val();
    const list = filtro_str.attr('list');
    filtro_str.attr('list','');//Lo saco y pongo para que lo recarge
    
    origen_str.empty().append(origen_cas.find('option').filter(function(idx,op){
      return $(op).val().substr(0, str.length) === str;
    }).clone());
    
    filtro_str.attr('list', origen_str.attr('id'));
    filtro_str.focus();
    
    destino_output_id.val('');
    const first_option = origen_str.find('option').eq(0);
    let id = null;
    if(first_option.length >= 1 && first_option.val() == str){
      id = first_option.attr('data-id');
      destino_output_id.val(id);
    }
    destino_output_id.trigger('seleccionado',[str,id]);
  });
  
  filtro_id_casino.trigger('change');
});
