$(function(e){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
});
export const AUX = {
  _mensaje(modal,mensaje){
    modal.hide();
    setTimeout(function(){
      modal.find('p').text(mensaje);
      modal.show();
    },100);
  },
  mensajeExito(mensaje=''){
    this._mensaje($('#mensajeExito'),mensaje);
  },
  mensajeError(mensaje=''){
    this._mensaje($('#mensajeError'),mensaje);
  },
  
  _aux_ajax(type,url,params = {},success = function(data){},error = function(response){console.log(response);},ext_params={}){
    $.ajax({
      type: type,
      url: url,
      data: params,
      success: success,
      error: error,
      ...ext_params
    });
  },
  GET(url,params = {},success = function(data){},error = function(response){console.log(response);},ext_params={}){
    this._aux_ajax('GET',url,params,success,error,ext_params);
  },
  POST(url,params = {},success = function(data){},error = function(response){console.log(response);},ext_params={}){
    this._aux_ajax('POST',url,params,success,error,ext_params);
  },
  DELETE(url,params = {},success = function(data){},error = function(response){console.log(response);},ext_params={}){
    this._aux_ajax('DELETE',url,params,success,error,ext_params);
  },

  hhmm(hhmmss){
    if(hhmmss === null) return '--:--';
    const arr = hhmmss.split(':');
    if(arr.length != 3) throw 'Formato de hora incorrecto '+hhmmss;
    return arr.slice(0,2).join(':');
  },

  extraerFormData(jqobject){
    const data = {};
    jqobject.find('[name]').map(function(idx,o){
      const attr = $(o).attr('data-js-formdata-attr');
      const key  = $(o).attr('name');
      const val  = attr? $(o).attr(attr) : $(o).val();
      data[key] = val;
    });
    return data;
  },
  mostrarErroresNames(jqobject,json,check_visible=false){//Si hay campos escondidos con [name], check_visible=true evita que se muestre un error flotando
    Object.keys(json).forEach(function(k){
      const obj = jqobject.find(`[name="${k}"]`);
      const visible = check_visible? obj.is(':visible') : true;
      if(visible)
        mostrarErrorValidacion(obj,json[k].join(', '),true);
    });
  },
  form_entries(form){
    return Object.fromEntries(new FormData(form).entries());
  },
  mostrarErroresNamesJSONResponse(jqobject,json,check_visible=false){
    const new_json = {};
    Object.keys(json).forEach(function(k){
      const ks = k.split('.');
      let name = ks[0];
      if(ks.length > 1){
        name+='['+ks.slice(1).join('][')+']'
      }
      new_json[name] = json[k];
    });
    this.mostrarErroresNames(jqobject,new_json,check_visible);
  }
};




