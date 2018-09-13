(function($){
  $.fn.borrarDataList = function() {
    var element = $(this); //Obtener el input

    //Comprobar si tiene seteado el datalist
    if (element.hasClass('input-data-list')) {
      element.next().next().remove(); //Se borra el contenedor
      element.next().remove(); //Se borra el ícono

      //Se borran todos los atributos agregados al input por la librería
      element.removeClass('input-data-list');
      element.removeAttr('data-elemento-seleccionado');
      element.removeAttr('ultimo-input');
      element.off('keyup');

      element.siblings('span').children().removeClass('activo');
    }

  }

  $.fn.obtenerElementoSeleccionado = function(){
    var id = this.attr('data-elemento-seleccionado');
    if(id !== undefined){
      return parseInt(id);
    }
    else{
      return 0;
    }
  }

  $.fn.setearElementoSeleccionado = function(id,descripcion){
    this.attr('data-elemento-seleccionado',id);
    this.val(descripcion);
    if (descripcion != "") {
        this.next().removeClass('fa-search').addClass('fa-check');
    }else{// Se cambia el icono por uno de busqueda (antes solo se escondía)
      // this.next().hide();
        this.next().removeClass('fa-check').addClass('fa-search');
    }

    this.next().next().hide();
  }
 // GUARDAR ULTIMO INPUT PARA COMPARAR CON EL NUEVO Y VER SI BUSCAR
  $.fn.generarDataList = function(url, nombre_elementos, nombre_id, nombre_descripcion, char_min, group){
    return this.each(function(){

        //Se crea busca el input y se lo limpia.
        var element = $(this); // este es el input
        element.attr('data-elemento-seleccionado',0);
        element.val("");
        element.addClass('input-data-list');
        element.attr('ultimo-input',"");
        element.off('keyup');

        //Si ya habia sido creado el datalist, borro los anteriores elementos por seguridad.
        if(element.next().hasClass('check-data-list')){ // ya habia sido creado el datalist y adelante tengo el icono, dp el container y dp la lista
          if(element.next().next().hasClass('contenedor-data-list')){
             element.next().next().remove();
          }
          element.next().remove();
        }

        //Se crea el icono (check) y se agrega al input. Luego se muestra.
        var icono = $(document.createElement('i'));
        icono.insertAfter(element);
        icono.addClass('fas fa-search check-data-list');
        icono.show();

        //Se crea el contenedor de la lista. Luego se oculta.
        var contenedor = $(document.createElement('div'));
        contenedor.addClass('contenedor-data-list').hide();

        //Hacer un datalist para grupo. Contiene el botón '+'.
        if (group) {
          contenedor.addClass('grupo');
          //Se le agrega la clase activa al botón para acomodar la posición
          var boton = element.siblings('span').children();
          boton.addClass('activo');
        }

        //Se crea la lista contenedora de resultados.
        var lista = $(document.createElement('div')); // esta seria la lista
        lista.addClass('lista-data-list');
        contenedor.append(lista);
        contenedor.insertAfter(icono); // lo posicionamos seguido del icono

        //Controlador del evento al presionar tecla.
        element.on("keyup", function(e){

          //Si es diferente de 'ENTER'.
          if(e.which != 13){
            var input = element.val(); //Lo que se escribió en el input
            //element.next().hide(); //oculto icono
            element.next().removeClass('fa-check').addClass('fa-search');

            //Si la cantidad de letras de lo que se escribió es igual o más largo que lo que se pide:
            if(input.length >= parseInt(char_min)){

              //Si la lista no tiene resultados, se busca en el servidor.
              if(lista.find('div').length == 0){
                $.get(url + "/" + input, function(data){
                  element.attr('ultimo-input',input);
                  lista.empty();
                  $.each(data[''+nombre_elementos+''],function(index, elemento){
                        lista.append($('<div>').text(elemento[''+nombre_descripcion+'']).attr('id',elemento[''+nombre_id+'']).addClass('elemento-data-list'));
                        //para comparar tranformo en mayuscula, pero si es un 'number' no funciona
                        var compara;
                        typeof elemento[''+nombre_descripcion+''] == 'string' ? compara = elemento[''+nombre_descripcion+''].toUpperCase() : compara = elemento[''+nombre_descripcion+''];

                        if(compara === input.toUpperCase()){
                          //element.val(elemento[''+nombre_descripcion+'']);
                          element.attr('data-elemento-seleccionado',elemento[''+nombre_id+'']);
                          //Disparar evento de elemento seleccionado
                          element.trigger('seleccionado');
                          // element.next().show(); // muestro icono
                          element.next().removeClass('fa-search').addClass('fa-check');
                          element.next().next().show(); // muestro lista
                        }
                  });

                  //Si se encontraron coincidencias se muestra la lista.
                  if(lista.find('div').length > 0){
                    lista.parent().show();
                  }else{
                    lista.parent().hide();
                  }
                });
              }else{ // filtrar sobre los que ya tengo

                //ACÁ ESTABA EL ERROR DEL DATALIST. Para cuando se clicke sobre una cadena con coincidencia "LIKE" intermedio.
                //console.log(input.toLowerCase().indexOf(element.attr('ultimo-input').toLowerCase()));

                //Se compara lo que se escribe con lo último que se escribió para ver si está contenido.
                //Si está contenido, se filtran los resultados que contiene la lista:
                if(input.toLowerCase().indexOf(element.attr('ultimo-input').toLowerCase()) != -1) {

                  lista.parent().show();
                  element.attr('data-elemento-seleccionado',0);
                  element.trigger('deseleccionado');

                  //Se repasa la lista para ocultar los que no coinciden.
                  lista.find(".elemento-data-list").each(function(index){
                    if($(this).text().toLowerCase().indexOf(input.toLowerCase()) < 0){
                      $(this).hide();
                    }else{
                      $(this).show();
                      if($(this).text().toLowerCase() === input.toLowerCase()){
                        //element.val($(this).text());
                        element.attr('data-elemento-seleccionado',$(this).attr('id'));
                        element.trigger('seleccionado');
                        // element.next().show();
                        element.next().removeClass('fa-search').addClass('fa-check');
                      }
                    }
                  });
                }
                //Si no está contenido, se vulve a buscar en la base:
                else{
                  //console.log('El index no es 0', input.toLowerCase().indexOf(element.attr('ultimo-input').toLowerCase()));
                  $.get(url + "/" + input, function(data){
                    element.attr('ultimo-input',input);
                    lista.empty();
                    $.each(data[''+nombre_elementos+''],function(index, elemento){
                          lista.append($('<div>').text(elemento[''+nombre_descripcion+'']).attr('id',elemento[''+nombre_id+'']).addClass('elemento-data-list'));
                          // console.log(elemento[''+nombre_descripcion+'']); elemento agregado

                          var compara;
                          typeof elemento[''+nombre_descripcion+''] == 'string' ? compara = elemento[''+nombre_descripcion+''].toUpperCase() : compara = elemento[''+nombre_descripcion+''];

                          if(compara === input.toUpperCase()){
                            element.val(elemento[''+nombre_descripcion+'']);
                            element.attr('data-elemento-seleccionado',elemento[''+nombre_id+'']);
                            element.trigger('seleccionado');
                            // element.next().show(); // muestro icono
                            element.next().removeClass('fa-search').addClass('fa-check');
                            element.next().next().show(); // muestro lista
                          }
                    });
                    if(lista.find('div').length > 0){
                      lista.parent().show();
                    }else{
                      lista.parent().hide();
                    }
                  });
                }
              }
            }else{
              console.log('Entra al else');
              element.attr('ultimo-input',"");
              lista.empty();
              element.attr('data-elemento-seleccionado',0);
              element.trigger('deseleccionado');
              contenedor.hide();
            }
          }
        });

      });
   }

}(jQuery));

$(document).on('mousedown','.elemento-data-list',function(){
  console.log('click en contenedor');
  console.log($(this));

  $(this).parent().find('elemento-data-list').removeClass('elemento-marcado');
  $(this).addClass('elemento-marcado');

  var element = $(this).parent().parent().prev().prev();
  var icono = element.next();
  var contenedor_lista = icono.next();

  console.log(contenedor_lista);

  element.attr('data-elemento-seleccionado',parseInt($(this).attr('id')));
  element.val($(this).text());
  // icono.show(); // muestro icono
  element.next().removeClass('fa-search').addClass('fa-check');
  contenedor_lista.hide(); //oculto lista
  element.trigger('keyup');
});

//se usa para la negacion entre las opciones
$(document).on('keydown','.input-data-list',function(e){
    var input = $(this);
    var lista = input.next().next().children();
    var cantidad_opciones = lista.find('div:visible').length;
    var elemento_marcado = lista.find('.elemento-marcado');

    if(cantidad_opciones > 0){
      switch(e.which){
          case 38: // up
            // si no hay nada marcado, marco el ultimo, si hay algo marcado marco el anterior
            if(cantidad_opciones == 1){
              console.log('cantidad opciones uno');
              lista.children(':visible').addClass('elemento-marcado');
            }else{
              if(elemento_marcado.length && elemento_marcado.prevAll(':visible:first') .length){
                  elemento_marcado.removeClass('elemento-marcado').prevAll(':visible:first').addClass('elemento-marcado');
              }else{
                elemento_marcado.removeClass('elemento-marcado');
                lista.children(':visible').last().addClass('elemento-marcado');
              }
            }

            elemento_marcado = lista.find('.elemento-marcado');
            if (elemento_marcado.length > 0) {
              console.log('scroll',elemento_marcado.offset().top , lista.offset().top ,  lista.scrollTop()  );
              lista.scrollTop(elemento_marcado.offset().top - lista.offset().top -1 + lista.scrollTop());
            }
          break;
        case 40: // down
          // si no hay nada marcado, marco el primero, si hay algo marcado marco el siguiente
          if(cantidad_opciones == 1){
            lista.children(':visible').addClass('elemento-marcado');
          }else{
            if(elemento_marcado.length && elemento_marcado.nextAll(':visible:first').length){
                elemento_marcado.removeClass('elemento-marcado').nextAll(':visible:first').addClass('elemento-marcado');
            }else{
              elemento_marcado.removeClass('elemento-marcado');
              lista.children(':visible').first().addClass('elemento-marcado');
            }
          }

          elemento_marcado = lista.find('.elemento-marcado');
          if (elemento_marcado.length > 0) {
            lista.scrollTop(elemento_marcado.offset().top - lista.offset().top - 1 + lista.scrollTop());
          }
          break;
          case 13: // enter
          // si no hay nada marcado, busco, si hay algo marcado lo selecciono y oculto la lista
          var el = lista.find('.elemento-marcado');
          if(el.length){ // selecciono y oculto lista
            input.val(el.text());
            input.attr('data-elemento-seleccionado',el.attr('id'));
            input.trigger('seleccionado');
            // input.next().show(); // muestro icono
            input.next().removeClass('fa-search').addClass('fa-check');
            input.next().next().hide(); // oculto lista
          }else{ // busco
            input.trigger('keyup');
          }
          break;

          default: return;
      }
    }
});

$(document).on('focusin','.input-data-list',function(){
  //console.log('focus in');
  var input = $(this);
  var contenedor = input.next().next();
  //Para saltear al popover en caso que lo tenga
  if (contenedor.hasClass('check-data-list')) {
      contenedor = contenedor.next();
  }
  var lista = contenedor.children();
  var cantidad_opciones = lista.children().filter(function(){return $(this).css("display") !== "none";}).length;
  if(cantidad_opciones > 0){
    contenedor.show();
  }else{
    contenedor.hide();
  }
});

$(document).on('focusout','.input-data-list',function(){
  //console.log('focus out');
  var input = $(this);
  var contenedor = input.next().next();
  contenedor.hide();
});
