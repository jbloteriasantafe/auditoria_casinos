$(document).ready(function() {
    $('#barraInformesMesas').attr('aria-expanded','true');
    $('#informes').removeClass();
    $('#informes').addClass('subMenu1 collapse in');
    $('.tituloSeccionPantalla').text('Informes Diarios de Control Ambiental');
    $('#opcInfoDiario').attr('style','border-left: 6px solid #185891; background-color: #131836;');
    $('#opcInfoDiario').addClass('opcionesSeleccionado');

    $('#fechaInformeDiario').val(''),
    $('#select_casino_diario').val('0'),

    $(function(){
      $('#dtpFechaInfD').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'yyyy-mm-dd',
          pickerPosition: "bottom-left",
          startView: 4,
          minView: 2
        });
    });

    $('#buscar-info-diarios').trigger('click',[1,10,'fecha','desc']);
    $('#select_casino_diario').val('0');
    $('#B_fecha_diario').val('');

});

$("#modalModificar").on('hidden.bs.modal', function () {
  $('#buscar-info-diarios').trigger('click',[1,10,'fecha','desc']);
});


$(document).on('click','.modificar',function(){
  var t=$(this).attr('data-fecha');

  var id=$(this).val();
  $('#btn-guardar-cierre').hide();
  $('#btn-guardar-importacion').hide();
  $('.desplegarModificarCierre').hide();
  $('.desplegarModificarImp').hide();
  $('#tablaCierresAModificar tbody > tr').remove();
  $('#mensajeExitoCargaCierre').hide();
  $('#mensajeExitoCargaImp').hide();


  $.get('informeDiario/getDatos/' + id, function(data){

    if(data.length == 0){
      $('#modalModificar .desplegarTablaCI').hide();
      $('#modalModificar .msjSinDiferencias').show();
    }
    else{
      $('#modalModificar .desplegarTablaCI').show();
      $('#modalModificar .msjSinDiferencias').hide();


    for (var i = 0; i < data.length; i++) {
        var fila=cargarTabla(data[i]);
        $('#tablaCierresAModificar').append(fila);
    }
  }
    $('#fecha_modificar').text('INFORME DEL DÍA: ' + t).css('text-align','center');

  })

  $('#modalModificar').modal('show');

})

$(document).on('click','#cierre_modificar',function(e){

  e.preventDefault();

  var id=$(this).val();
  $('#btn-guardar-cierre').val(id);
  $('#btn-guardar-cierre').attr('data-importacion',$(this).attr('data-importacion'));
  $('#btn-guardar-cierre').show();
  $('#btn-guardar-importacion').hide();
  $('#mensajeExitoCargaCierre').hide();
  $('#mensajeExitoCargaImp').hide();
  $('#fichasModif > tr').remove();

  $('.desplegarModificarImp').hide();
  $('.desplegarModificarCierre').show();
  $('#modalModificar').animate({scrollTop:$('.desplegarModificarCierre').offset().top},"slow");

  $.get('cierres/obtenerCierres/' + id, function(data){


    $('#totalModif').val(data.cierre.total_pesos_fichas_c	);


  for (var i = 0; i < data.detallesC.length; i++) {
    var fila = $(document.createElement('tr'));

    fila.attr('id', data.detallesC[i].id_ficha)
        .append($('<td>')
        .addClass('col-xs-4').addClass('fichaVal').attr('id',data.detallesC[i].id_ficha).css('cssText','text-align:center !important')
        .append($('<input>').prop('readonly','true')
        .val(data.detallesC[i].valor_ficha).css('text-align','center')))

        if(data.detallesC[i].monto_ficha != null){

          fila.append($('<td>')
              .addClass('col-xs-4').css('cssText','text-align:center !important')
              .append($('<input>').addClass('modCierre'+' fichas'+i+'monto_ficha').attr('id', 'input').val(data.detallesC[i].monto_ficha).css('text-align','center')
              .attr('data-valor',data.detallesC[i].valor_ficha).attr('data-ingresado', data.detallesC[i].monto_ficha)))
        }
        else{
          fila.append($('<td>')
              .addClass('col-xs-4').css('cssText','text-align:center !important')
              .append($('<input>').addClass('modCierre'+' fichas'+i+'monto_ficha').attr('id', 'input').val(0).css('text-align','center')
              .attr('data-valor',data.detallesC[i].valor_ficha).attr('data-ingresado', 0)))
        }

    $('#fichasModif').append(fila);
  }
  })
})

$(document).on('change','.modCierre',function(){

  var num= Numeros($(this).val());

  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

    if(num!=null && num!=0) //si se ingreso un valor diferente de 0
    {   var cantidad=num;
        $(this).attr('data-ingresado',cantidad);
        //var valor=$(this).attr('data-valor');

        var subtotal=0;
        subtotal = Number($('#totalModif').val());
        subtotal += Number(cantidad);
        $('#totalModif').val(subtotal);}

    if (num==null || num==0) { //si se ingresa el 0 o nada
      var cantidad=0;
      var subtotal=0;
      subtotal = Number($('#totalModif').val());
      subtotal -= Number($(this).attr('data-ingresado') );

      $('#totalModif').val(subtotal);
      $(this).attr('data-ingresado',cantidad);

    }
  }
  else{
    if(num!=null && num!=0){ //si se ingreso un valor diferente de 0
        var cantidad=num;
        var subtotal=0;
        //tomo el data ingresado anteriormente y lo resto al total antes de perderlo
        subtotal = Number($('#totalModif').val());
        subtotal-=Number($(this).attr('data-ingresado'));
        $('#totalModif').val(subtotal);

        $(this).attr('data-ingresado',cantidad);//cambio el data ingresado
      //  var valor=$(this).attr('data-valor');

        var total=0;

        total = Number($('#totalModif').val());
        total += Number(cantidad);

        $('#totalModif').val(total);}

    if (num=='' || num==0) { //si se ingresa el 0 o nada
          var cantidad=0;
          var subtotal=0;
          subtotal = Number($('#totalModif').val());
          subtotal -= Number($(this).attr('data-ingresado') );

          $('#totalModif').val(subtotal);
          $(this).attr('data-ingresado',cantidad);
    }
  }

})

$('#btn-guardar-cierre').on('click',function(e){

  e.preventDefault();

  $('#mensajeError').hide();
  $('#mensajeExito').hide();

    var fichas=[];

    var f= $('#fichasModif > tr');

    $.each(f, function(index, value){

      var valor={
        id: $(this).find('.fichaVal').attr('id'),
        monto: $(this).find('.modCierre').val()
      }
      if(valor.monto_ficha != "" && valor.monto_ficha != "0" ){
        fichas.push(valor);
      }else{
        valor={
          id: $(this).find('.fichaVal').attr('id'),
          monto: 0
        }
          fichas.push(valor);
      }

    })
    var cierre={
      id:$(this).val(),
      id_importacion_diaria_mesas:$(this).attr('data-importacion'),
      fichas:fichas,

    }
      var formData= {
        cierre:cierre,
        importacion:'',
      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'informeDiario/almacenarDatos',
          data: formData,
          dataType: 'json',

          success: function (data){
            $('.desplegarModificarCierre').hide();
            $('#btn-guardar-importacion').hide();
            $('#btn-guardar-cierre').hide();
            $('#mensajeExitoCargaCierre').show();

          },
          error: function (reject) {
                if( reject.status === 422 ) {
                    var errors = $.parseJSON(reject.responseText).errors;
                    $.each(errors, function (key, val) {

                      if(key != 'id_moneda' && key != 'id_fiscalizador' &&
                         key != 'total_pesos_fichas_c' && key != 'fichas' &&
                         key != 'hora_inicio' && key != 'hora_fin'
                        ){
                          var splitt = key.split('.');
                          mostrarErrorValidacion( $('.modCierre .' + splitt[0]+splitt[1]+splitt[2] ),val[0],false);
                          $('#errorModificarCierre').show();
                      }
                    });
                }else{
                    $('#errorModificarCierre').show();
                }
            }
      })
});

$(document).on('click', '#id_imp_modificar', function(){

  var id=$(this).val();

  $('#btn-guardar-importacion').val(id);
  $('#btn-guardar-cierre').hide();
  $('#btn-guardar-importacion').show();
  $('#datosImpModifPesos > tr').remove();
  $('.desplegarModificarCierre').hide();
  $('#mensajeExitoCargaCierre').hide();
  $('#mensajeExitoCargaImp').hide();

  $('.desplegarModificarImp').show();
  $('#modalModificar').animate({scrollTop:$('.desplegarModificarImp').offset().top},"slow");

  $.get('informeDiario/getDatosImportacion/' + id, function(data){
      var fila=cargarImportacion(data);
      $('#datosImpModifPesos').append(fila);
  })

})

$('#btn-guardar-importacion').on('click',function(e){

  e.preventDefault();

  $('#mensajeError').hide();
  $('#mensajeExito').hide();

      var importacion={
        id: $(this).val(),
        drop: $('#datosImpModifPesos').find('.v_drop').val(),
        fill: $('#datosImpModifPesos').find('.v_reposiciones').val(),
        credit: $('#datosImpModifPesos').find('.v_retiros').val(),
        utilidad: $('#datosImpModifPesos').find('.v_utilidad').val(),
        cotizacion: $('#datosImpModifPesos').find('.v_cotizacion').val() //if(moneda es pesos enviar en 0)
      }

      var formData= {

        cierre:'',
        importacion:importacion,

      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'informeDiario/almacenarDatos',
          data: formData,
          dataType: 'json',

          success: function (data){
            $('.desplegarModificarImp').hide();
            $('#btn-guardar-importacion').hide();
            $('#btn-guardar-cierre').hide();
            $('#mensajeExitoCargaImp').css('display','block');


          },
          error: function (reject) {
                if( reject.status === 422 ) {
                    var errors = $.parseJSON(reject.responseText).errors;
                    $.each(errors, function (key, val) {

                      if(key != 'id_moneda' && key != 'id_fiscalizador' &&
                         key != 'total_pesos_fichas_c' && key != 'fichas' &&
                         key != 'hora_inicio' && key != 'hora_fin'
                        ){
                          var splitt = key.split('.');
                          mostrarErrorValidacion( $('.modCierre .' + splitt[0]+splitt[1]+splitt[2] ),val[0],false);
                          $('#errorModificarCierre').show();

                      }
                    });
                }else{
                    $('#errorModificarCierre').show();
                }
            }

      })

});

$('#buscar-info-diarios').on('click',function(e,pagina,page_size,columna,orden){

  e.preventDefault();

    //Fix error cuando librería saca los selectores
    if(isNaN($('#herramientasPaginacion').getPageSize())){
      var size = 10; // por defecto
    }
    else {
      var size = $('#herramientasPaginacion').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaInfoDiarios .activa').attr('value'),orden: $('#tablaInfoDiarios .activa').attr('estado')} ;

    if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
      var sort_by =  {columna: 'fecha',orden: 'desc'} ;

      //$('#tablaInicial th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

    var formData= {
              fecha: $('#B_fecha_diario').val(),
              id_casino: $('#select_casino_diario').val(),
              page: page_number,
              sort_by: sort_by,
              page_size: page_size,
            }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.ajax({
            type: 'GET',
            url: 'http://' + window.location.host + '/informeControlAmbiental/buscarInformesControlAmbiental',
            data: formData,
            dataType: 'json',

            success: function (data){
                $('#tablaInfoDiarios tbody tr').remove();
                $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.diarios.total,clickIndice);

              for (var i = 0; i < data.diarios.data.length; i++) {

                  var fila=  generarFilaTablaInicial(data.diarios.data[i]);
                  $('#tablaInfoDiarios').append(fila);
              }

              $('#herramientasPaginacion').generarIndices(page_number,page_size,data.diarios.total,clickIndice);

            },
            error: function(data){
            },
        })

});


$(document).on('click','.imprimirInfoDiario', function(e){

  e.preventDefault();
  console.log($(this).val());
  window.open('informeControlAmbiental/imprimir/' + $(this).val(),'_blank');
})


function generarFilaTablaInicial(data){
  var fila = $('#moldeInfoDia').clone();

  fila.removeAttr('id');
  fila.attr('id',data.id_informe_control_ambiental);

  fila.find('.diario_fecha').text(data.fecha).css('text-align','center');
  fila.find('.diario_casino').text(data.nombre).css('text-align','center');
  fila.find('.imprimirInfoDiario').val(data.id_informe_control_ambiental).css('text-align','center');

  /*
  var fecha=data.fecha.split('-');
  var a = new Date();
  var anio_actual = a.getFullYear();
  var mes_actual = a.getMonth();

  if(fecha[0]< anio_actual && fecha[1] < mes_actual){
    fila.find('.modificar').hide();
  }else{
    fila.find('.modificar').show();
  }
  */

  fila.css('display','');
  $('#molde2').css('display','block');

  return fila;
}

function cargarTabla(data){

  var mon;
  if(data.id_moneda==1){
    mon='PESOS';
  }
  else{
    mon='DÓLARES';
  }
  var fila=$('#moldeModif').clone();
  fila.removeAttr('id');
  fila.find('.nro_modificar').text(data.nro_mesa);
  fila.find('.juego_modificar').text(data.nombre_juego);
  fila.find('.moneda_modificar').text(mon);
  fila.find('#cierre_modificar').val(data.id_cierre_mesa).attr('data-importacion',data.id_importacion_diaria_mesas);
  fila.find('#id_imp_modificar').val(data.id_detalle_importacion_diaria_mesas);

  fila.css('display','');
  $('#dd').css('display','block');

  return fila;
}

function generarFilaVerImpValidar(data){

    var fila = $('#moldeModifImp').clone();
      fila.removeAttr('id');
      fila.attr('id', data.id_importacion_diaria_mesas);

      fila.find('.v_juego').text(data.nombre_juego);
      fila.find('.v_mesa').text(data.nro_mesa);
      fila.find('.v_drop').text(data.droop);
      fila.find('.v_reposiciones').text(data.reposiciones);
      fila.find('.v_retiros').text(data.retiros);
      fila.find('.v_utilidad').text(data.utilidad);
      fila.find('.v_hold').text(data.hold);


      fila.css('display', '');
      $('#mostrarTabla').css('display','block');

    return fila;

}

function cargarImportacion(data){

  var fila=$('#moldeModifImp').clone();
  fila.removeAttr('id');
  fila.find('.v_juego').text(data.nombre_juego);
  fila.find('.v_mesa').text(data.nro_mesa);
  fila.find('.v_drop').val(data.droop);
  fila.find('.v_utilidad').val(data.utilidad);
  fila.find('.v_retiros').val(data.retiros);
  fila.find('.v_reposiciones').val(data.reposiciones);
  fila.find('.v_hold').text(data.hold);

  if(data.id_moneda==2){
    fila.find('.v_cotizacion').val(data.cotizacion);
  }
  else {
    fila.find('.v_cotizacion').val(0).prop('disabled',true);

  }

  fila.css('display','');
  $('#mostrarTabla').css('display','block');
  return fila;
}

/*****************PAGINACION******************/
$(document).on('click','#tablaInfoDiarios thead tr th[value]',function(e){

  $('#tablaInfoDiarios th').removeClass('activa');

  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{

    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
        $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaInfoDiarios th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndice(e,pageNumber,tam){

  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaInfoDiarios .activa').attr('value');
  var orden = $('#tablaInfoDiarios .activa').attr('estado');
  $('#buscar-info-diarios').trigger('click',[pageNumber,tam,columna,orden]);
}

function Numeros(string){//Solo numeros
    var out = '';
    var filtro = '1234567890,.';//Caracteres validos

    //Recorrer el texto y verificar si el caracter se encuentra en la lista de validos
    for (var i=0; i<string.length; i++)
       if (filtro.indexOf(string.charAt(i)) != -1 )
             //Se añaden a la salida los caracteres validos
	     out += string.charAt(i);

    //Retornar valor filtrado
    return out;
}
