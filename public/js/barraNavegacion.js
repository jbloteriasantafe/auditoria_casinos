$(document).ready(function(){
  //Para mostrar los mensajes de las acciones de los botones
// animacionLogo();
(function(){
	$('.btn').dblclick(function(){
  		alert("Has hecho doble click");
	});	});

});

//Mostrar modal para agregar nuevo Casino
$('#btn-ayuda').click(function(e){
  e.preventDefault();
	$('#modalAyuda').modal('show');

});


//Var globales para la animación del logo
var canvas, stage, exportRoot, anim_container, dom_overlay_container, fnStartAnimation;

// $('[data-toggle="tooltip"]').tooltip();
// $(document).on('click' ,'[data-toggle="tooltip"]' ,  function () {
//     trigger : 'hover'
// })
//
// $(document).on('click' ,'[data-toggle="tooltip"]' ,  function () {
// 		$(this).tooltip('hide')
// })


$(document).ajaxError(function(event, jqxhr, settings, thrownError){
  if(jqxhr.status == 351){
    var responseText = jQuery.parseJSON(jqxhr.responseText);
    alert(responseText.mensaje);
    window.location.href=responseText.url;
  }
});


$('.etiquetaLogoSalida').click(function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  $.ajax({
      type: 'POST',
      url: '/logout',
      success: function (data) {
        window.location.href = "login";
      },
      error: function (data) {
        console.log("Error: ", data);
      },
  });

});

/*
 input: selector al que se agrega el popover,
 mensaje: mensaje a mostrar en el popover,
 mostrar: true -> show popover al crear | false -> hide popover (se muestra en el hover)
*/
function mostrarErrorValidacion(input, mensaje, mostrar) {
  console.log('agrega');
  //Se quita el evento si la función se había aplicado antes
  input.off('focusin');
  //Agregar un borde rojo de alerta al input
  input.addClass('alerta');
  //Agregar un popover al input con las opciones detalladas
  input.popover(
    {
      content: mensaje,
      placement: "top",
      html:true,
      trigger: "hover",
    }
  );


  var id_pop = input.attr('aria-describedby');
  $("#" + id_pop).addClass('popAlerta');

  if (mostrar) input.popover('show');
  else input.popover('show').popover('hide');

  //Agregar estilos al popover
  var id_pop = input.attr('aria-describedby');
  $("#" + id_pop).addClass('popAlerta');

  //Cuando se entra al input que tenía error se borran los alertas
  input.one('focusin', function() {
    ocultarErrorValidacion(input);
  });

  input.on('change', function() {
    ocultarErrorValidacion(input);
  });

  return 1;
}

function mostrarMensajeAdvertencia(input, mensaje, mostrar){
  //Se quita el evento si la función se había aplicado antes
  input.off('focusin');
  //Agregar un borde rojo de alerta al input

  //Agregar un popover al input con las opciones detalladas
  input.popover(
    {
      content: mensaje,
      placement: "top",
      html:true,
      trigger: "hover",
    }
  );


  var id_pop = input.attr('aria-describedby');
  $("#" + id_pop).addClass('popAdvertencia');

  if (mostrar) input.popover('show');
  else input.popover('show').popover('hide');

  //Agregar estilos al popover
  var id_pop = input.attr('aria-describedby');
  $("#" + id_pop).addClass('popAdvertencia');

  //Cuando se entra al input que tenía error se borran los alertas
  input.one('focusin', function() {
    ocultarAdvertencia(input);
  });

  input.on('change', function() {
    ocultarAdvertencia(input);
  });

  return 1;
}

function ocultarErrorValidacion(input) {
  if (input.hasClass('alerta')) {
    // input.off('focusin');
    input.removeClass('alerta');
    input.popover("destroy");
  }
}

function ocultarAdvertencia(input) {
  if (input.hasClass('advertencia')) {
    // input.off('focusin');
    input.removeClass('advertencia');
    input.popover("destroy");
  }
}

function convertirDate(date) {
  if (date != null) {
    var mesesEnteros = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    var meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];

    var fecha = date.split('-');

    return fecha[2] + ' ' + meses[fecha[1] - 1] + ' ' + fecha[0];
  }else {

    return null;
  }
}

function animacionLogo(){
  canvas = document.getElementById("canvas");
  anim_container = document.getElementById("animation_container");
  dom_overlay_container = document.getElementById("dom_overlay_container");
  var comp=AdobeAn.getComposition("C157BAF2E4B3C44A95979EE193DECCB1");
  var lib=comp.getLibrary();
  handleComplete({},comp);
}

function handleComplete(evt,comp) {
	//This function is always called, irrespective of the content. You can use the variable "stage" after it is created in token create_stage.
	var lib=comp.getLibrary();
	var ss=comp.getSpriteSheet();
	exportRoot = new lib.Animacion_logo2();
	stage = new lib.Stage(canvas);
	stage.addChild(exportRoot);
	//Registers the "tick" event listener.
	fnStartAnimation = function() {
		createjs.Ticker.setFPS(lib.properties.fps);
		createjs.Ticker.addEventListener("tick", stage);
	}
	//Code to support hidpi screens and responsive scaling.
	function makeResponsive(isResp, respDim, isScale, scaleType) {
		var lastW, lastH, lastS=1;
		window.addEventListener('resize', resizeCanvas);
		resizeCanvas();
		function resizeCanvas() {
			var w = lib.properties.width, h = lib.properties.height;
			var iw = window.innerWidth, ih=window.innerHeight;
			var pRatio = window.devicePixelRatio || 1, xRatio=iw/w, yRatio=ih/h, sRatio=1;
			if(isResp) {
				if((respDim=='width'&&lastW==iw) || (respDim=='height'&&lastH==ih)) {
					sRatio = lastS;
				}
				else if(!isScale) {
					if(iw<w || ih<h)
						sRatio = Math.min(xRatio, yRatio);
				}
				else if(scaleType==1) {
					sRatio = Math.min(xRatio, yRatio);
				}
				else if(scaleType==2) {
					sRatio = Math.max(xRatio, yRatio);
				}
			}
			canvas.width = w*pRatio*sRatio;
			canvas.height = h*pRatio*sRatio;
			canvas.style.width = dom_overlay_container.style.width = anim_container.style.width =  w*sRatio+'px';
			canvas.style.height = anim_container.style.height = dom_overlay_container.style.height = h*sRatio+'px';
			stage.scaleX = pRatio*sRatio;
			stage.scaleY = pRatio*sRatio;
			lastW = iw; lastH = ih; lastS = sRatio;
		}
	}
	makeResponsive(false,'both',false,1);
	AdobeAn.compositionLoaded(lib.properties.id);
	fnStartAnimation();
}

function addCommas(nStr) {
    if(nStr == '-'){
      return '-';
    }
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? ',' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + '.' + '$2');
    }
    return x1 + x2;
}

function markNotificationAsRead(notificationCount) {
    if(notificationCount !=='0'){
        $.get('/marcarComoLeidaNotif');
    }
}
// $('#notificaciones').on('click', function(){
//   markNotificationAsRead();
// })

$('#mensajeExito button.close').click(function() {
		$('#mensajeExito').hide();
});


$(document).on('click','div.panel table button.btn',function(){
	var boton = $(this);
  boton.prop('disabled',true);

  window.setTimeout(function() {
    boton.prop('disabled',false);
  }, 3000);
});

$(document).on('click','div.modal div.modal-footer button.btn',function(){
	var boton = $(this);
  boton.prop('disabled',true);

  window.setTimeout(function() {
    boton.prop('disabled',false);
  }, 3000);
});

// $('.btnConEspera').click(function() {
//   var boton = $(this);
//   boton.prop('disabled',true);
//
//   window.setTimeout(function() {
//     boton.prop('disabled',false);
//   }, 1000);
// });
