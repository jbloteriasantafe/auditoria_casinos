$(document).ajaxError(function(event, jqxhr, settings, thrownError){
  if(jqxhr.status == 351){
    var responseText = jQuery.parseJSON(jqxhr.responseText);
    alert(responseText.mensaje);
    window.location.href=responseText.url;
  }
});
