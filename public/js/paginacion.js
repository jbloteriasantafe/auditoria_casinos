(function($){
$.fn.getCurrentPage = function(){
  var indice = this.find('.current');
  if(indice.length > 0){
    return parseInt(indice.text());
  }else{
    return 1;
  }
}
$.fn.getPageSize = function(){
  if (!isNaN(parseInt(this.find('#size').val()))) {
    return parseInt(this.find('#size').val());
  }else {
    return 10;
  }
}

$.fn.generarTitulo = function(current_page,page_size,total_results,funcion){
  return this.each(function(){
      var total_pages = Math.ceil(total_results/page_size);
      var element = $(this);
      // element.removeClass().find('div').remove().text('');
      var limSup = ((current_page*page_size) < total_results) ? current_page*page_size : total_results;
      var titulo = (total_results > 0 ) ? "Mostrando del " + ((current_page - 1)*page_size + 1) + " al " + limSup
                   + " de " + total_results + " resultados" : "No se encontraron resultados";


      //Si existen, remover resultados anteriores
      element.find('.col-md-3').remove();
      element.find('.col-xs-12').remove();
      // element.addClass('row').css('margin-bottom','20px')
      element.append($('<div>')
                    .css('margin-bottom','20px')
                    .addClass('col-xs-12')
                    .append($('<h4>').text(titulo))
                    )
    if (total_results > 0){
      element.append($('<div>')
                    .addClass('col-md-3 col-xs-12')
                    .append($('<span>').text('PÁGINA NÚMERO').addClass('opcionPaginacion'))
                    .append($('<div>')
                                .addClass('input-group number-spinner-paginacion')
                                .append($('<span>').addClass('input-group-btn')
                                            .append($('<button>')
                                                          .addClass('btn btn-default')
                                                          .attr('data-dir','dwn').text('-')
                                                          .css('border','1px solid #ccc')
                                            )
                                )
                                .append($('<input>').attr('id','pag').attr('type','text')
                                                    .addClass('form-control text-center').val(current_page)
                                )
                                .append($('<span>').addClass('input-group-btn')
                                            .append($('<button>')
                                                          .addClass('btn btn-default')
                                                          .attr('data-dir','up').text('+')
                                                          .css('border','1px solid #ccc')
                                                    )
                                )
                    )
              )
      element.append($('<div>')
                    .addClass('col-md-3 col-xs-12')
                    .append($('<span>').text('TAMAÑO DE PÁGINA').addClass('opcionPaginacion'))
                    // .append($('<span>').text(' de ' + total_pages + ' con tamaño de página de: '))
                    .append($('<select id="size">')
                                // .css('display','inline')
                                // .css('width','120px')
                                // .css('margin-left','10px')
                                .addClass('form-control')
                                .append($('<option>').val(5).text(5))
                                .append($('<option>').val(10).text(10))
                                .append($('<option>').val(20).text(20))
                                .append($('<option>').val(50).text(50))
                                .val(page_size)
                                .change(function(e){
                                    if($('#pag').val()<1 || $('#pag').val()>total_pages) {
                                        $('#pag').css('border','1px solid red');
                                    }else{
                                        var size = $(e.currentTarget).val();
                                        var page = (size*(element.find('#pag').val() - 1) < total_results) ? parseInt(element.find('#pag').val()) : 1;
                                        funcion(e,page,size);
                                    }
                                })
                      )
                );

            //Evento para cambiar la página
            $('.number-spinner-paginacion button').on('click', function (e) {
                e.preventDefault();
                $('#pag').css('border','1px solid #bbb');

                var btn = $(this);
                var oldValue = btn.closest('.number-spinner-paginacion').find('input').val().trim();
                var	newVal = 0;

                var size = $('#size').val();

                if (btn.attr('data-dir') == 'up') {
                  if (oldValue < total_pages) {
                    newVal = parseInt(oldValue) + 1;
                    funcion(e,newVal,size)
                  } else {
                    newVal = total_pages;
                  }
                } else {
                  if (oldValue > 1) {
                    newVal = parseInt(oldValue) - 1;
                    funcion(e,newVal,size)
                  } else {
                    newVal = 1;
                  }
                }


                btn.closest('.number-spinner-paginacion').find('input').val(newVal);
            });

            $('#pag').on("keypress" , function(e){
                if(e.which == 13 && ($(this).val()<1 || $(this).val()>total_pages) ) {
                  e.preventDefault();
                  $('#pag').css('border','1px solid red');
                }
            });
    }

  });
}
$.fn.generarIndices = function(current_page,page_size,total_results,funcion){
  return this.each(function(){
      var element = $(this);
      element.find('.col-md-6').remove();

      element.append($('<div>').addClass('col-md-6'));
      var columna = $(this).find('.col-md-6');

      var total_pages = Math.ceil(total_results/page_size);
      if(total_pages > 1){ // si no hay más de 1 página no armamos los indices
        if(total_pages <= 7){ // 1 2 3 4 5 6 7
          for(var i=1; i <= total_pages; i++){
            if(i == current_page){
              columna.append($('<a>').text(i).addClass('indicePaginacion').addClass('current').addClass('bloqueado'));
             }else{
               columna.append($('<a>').text(i).addClass('indicePaginacion'));
             }
           }
      }
      if(total_pages > 7){
        columna.append($('<a>').addClass('indicePaginacion').addClass('fa fa-angle-left'));
        if(current_page <= 3){ // 1 2 3 4 5 ... 10
          for(var i=1; i <= 5; i++){
            if(i == current_page){
              columna.append($('<a>').text(i).addClass('indicePaginacion').addClass('current').addClass('bloqueado'));
            }else{
              columna.append($('<a>').text(i).addClass('indicePaginacion'));
            }
          }
          columna.append($('<a>').html("&hellip;").addClass('indicePaginacion').addClass('bloqueado'));
          columna.append($('<a>').text(total_pages).addClass('indicePaginacion'));
        }
        else{
          columna.append($('<a>').text(1).addClass('indicePaginacion'));
          columna.append($('<a>').html("&hellip;").addClass('indicePaginacion').addClass('bloqueado'));
          if(total_pages - current_page <=2){ // 1 ... 6 7 8 9 10
            for(var i=total_pages - 4; i <= total_pages; i++){
              if(i == current_page){
                columna.append($('<a>').text(i).addClass('indicePaginacion').addClass('current').addClass('bloqueado'));
              }else{
                columna.append($('<a>').text(i).addClass('indicePaginacion'));
              }
            }
          }
          else{ //1 ... 4 5 6 ... 10
            columna.append($('<a>').text(current_page - 1).addClass('indicePaginacion'));
            columna.append($('<a>').text(current_page).addClass('indicePaginacion').addClass('current').addClass('bloqueado'));
            columna.append($('<a>').text(current_page + 1).addClass('indicePaginacion'));
            columna.append($('<a>').html("&hellip;").addClass('indicePaginacion').addClass('bloqueado'));
            columna.append($('<a>').text(total_pages).addClass('indicePaginacion'));
          }
        }
        columna.append($('<a>').addClass('indicePaginacion').addClass('fa fa-angle-right'));
        }
        if(current_page == 1){
          columna.find('.fa-angle-left').addClass('bloqueado');
        }
        if(current_page == total_pages){
          columna.find('.fa-angle-right').addClass('bloqueado');
        }
      }
      columna.find('a:not(.bloqueado)').click(function(e){
        var index = $(this);
        var page;
        if(index.hasClass('fa-angle-right')){
          page = parseInt(index.parent().find('.current').text()) + 1;
        }
        if(index.hasClass('fa-angle-left')){
          page = parseInt(index.parent().find('.current').text()) - 1;
        }
        if(!(index.hasClass('fa-angle-right') || index.hasClass('fa-angle-left'))){
          page = parseInt(index.text());
        }
        funcion(e,page);
      });
    });
  }
}(jQuery));
