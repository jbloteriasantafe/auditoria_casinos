$(document).ready(function() {
    $('#barraCanon').attr('aria-expanded','true');

    $('.tituloSeccionPantalla').hide();
    $('#barraCanon').attr('style','border-left: 6px solid #185891; background-color: #131836;');
    $('#barraCanon').addClass('opcionesSeleccionado');

    //pestañas
    $('#pestCanon').show();
    $('#pestCanon').css('display','inline-block');

    $(".tab_content").hide(); //Hide all content
    $("ul.pestCanon li:first").addClass("active").show(); //Activate first tab
    $(".tab_content:first").show(); //Show first tab content

    $('#collapseFiltros').focus();
    $('#verDatosCanon').val(0);
    $('#mesFiltro option').not('.default').remove();
    $('#mesFiltro').prop('disabled',true);
    $('#B_fecha_filtro').val('');
    $('#mesFiltro').val(0);
    $('#filtroCasino').val(0);

    $(function(){
      $('#dtpFechaPago').datetimepicker({
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
      $(function(){
        $('#dtpFecha').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'yyyy',
          pickerPosition: "bottom-left",
          startView: 4,
          viewSelect:'decade',
          minView: 4,
          maxView:4,
          ignoreReadonly: true,
      });
    });

    $(function(){
        $('#dtpFechaFiltro').datetimepicker({
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

    $('#btn-buscar-pagos').trigger('click',[1,10,'DIFM.fecha_cobro','desc']);

});

//PESTAÑAS
$("ul.pestCanon li").click(function() {

    $("ul.pestCanon li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content
                console.log(activeTab);
    if(activeTab == '#pant_canon_valores'){
      $('#añoInicioAct1').prop('disabled',true);
      $('#añoInicioAct2').prop('disabled',true);
      $('#selectActualizacion').val(0);
      $('#añoInicioAct1 option').remove();
      $('#añoInicioAct2 option').remove();
      $('#mensajeError').hide();
      $('.desplegarActualizar').hide();
      $('.datosReg').hide();
      $('.datosActualizacion').hide();
      $('#collapseFiltros3').trigger('click');
      $('#actualizarCanon').hide();
      $('#mensajeErrorInforme').hide();
      ocultarErrorValidacion($('#añoInicioAct2'));

    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});

//INICIO PESTAÑA CANON 2*****//
//SELECT Q HABILITA Y PERMITE CARGAR SELECTS DE AÑOS DEPENIENDO EL CASINO
$(document).on('change','#selectActualizacion', function(e){
  e.preventDefault();
  $('.desplegarActualizar').hide();
  $('.datosActualizacion').hide();
  $('.datosReg').hide();

  var id=$(this).val();

  if(id != 0){
      $('#añoInicioAct1 option').remove();
      $('#añoInicioAct2 option').remove();

    $('#añoInicioAct1').prop('disabled',false);
    $('#añoInicioAct2').prop('disabled',false);

    $.get('canon/obtenerAnios/'+ id, function(data){

      if(data.anios.length > 0){
        for (var i = 0; i < data.anios.length; i++) {
          $('#mensajeErrorInforme').hide();
          $('#añoInicioAct1').append($('<option>').val(data.anios[i].anio_inicio).text(data.anios[i].anio_inicio).append($('</option>')))
          $('#añoInicioAct2').append($('<option>').val(data.anios[i].anio_final).text(data.anios[i].anio_final).append($('</option>')))
        }
      }
      else{
          $('.datosReg').show();
        $('#mensajeErrorInforme').find('.msjtext').text('No hay años para filtrar, puede que no se hayan cargado pagos de Canon, durante un año completo.');
        $('#mensajeErrorInforme').show();
      }
    })
  }else{
    $('#añoInicioAct1 option').remove();
    $('#añoInicioAct2 option').remove();

  $('#añoInicioAct1').prop('disabled',true);
  $('#añoInicioAct2').prop('disabled',true);
  }
})

//BUSCAR DE DICHA PESTAÑA
$('#buscarActualizar').on('click',function(e){
    e.preventDefault();
    $('.desplegarActualizar').hide();
    $('.datosActualizacion').hide();

    $('#anio1 tbody tr').not('.default1').remove();
    $('#anio2 tbody tr').not('.default2').remove();
    $('#tablaActualizacion tbody tr').remove();
   if($('#añoInicioAct1').val() != $('#añoInicioAct2').val()){
     var formData= {
       id_casino: $('#selectActualizacion').val(),
       anio_inicio:$('#añoInicioAct1').val(),
       anio_final: $('#añoInicioAct2').val(),
     }

     $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
         }
     });

     $.ajax({
         type: 'POST',
         url: 'canon/verInforme',
         data: formData,
         dataType: 'json',

         success: function (data){
           var result = Object.keys(data.detalles).map(function(key) {
             return [Number(key), data.detalles[key]];
           });

           if(result.length > 0){
             $('.casinoInformeFinal').text(' EURO').css('text-align','center').css('color','#000');
             $('.casinoInformeFinal2').text(' DÓLAR').css('text-align','center').css('color','#000');

             var f=data.informe.anio_inicio - 1;
             var d=data.informe.anio_final;
             var e=data.informe.anio_inicio;
             $('.rdo1').text('Rdo.Bruto ' + f + '/' + e);
             $('.rdo2').text('Rdo.Bruto ' + e + '/' + d);
             $('.cotizacion1').text('Cotización ' + f + '/' + e );
             $('.cotizacion2').text('Cotización ' + e + '/' + d );
             $('.valor1').text('Monto ' + f + '/' + e );
             $('.valor2').text('Monto ' + e + '/' + d );

               for (var i = 0; i < result.length; i++) {
                 //console.log('det data',data.detalles{});
                 var fila=cargarTablaInforme(result[i][1],1);
                 $('#anio1').append(fila);
               }

               for (var i = 0; i < result.length; i++) {
                 var fila2=cargarTablaInforme(result[i][1],2);
                 $('#anio2').append(fila2);
               }


                 $('.desplegarActualizar').show();
                   $('.datosReg').show();

                   $('#actualizarCanon').show();
                   $('#actualizarCanon').val( $('#selectActualizacion').val());
                   $('#mensajeErrorInforme').hide();

            }else{
              $('.datosReg').show();
              $('#actualizarCanon').hide();
              $('#mensajeErrorInforme').show();
            }


         },

         error: function (data) {
           $('.datosReg').show();
           $('#actualizarCanon').hide();
           $('#mensajeErrorInforme').show();

         }
       })
   }
   else{
     mostrarErrorValidacion($('#añoInicioAct2'),'Debe ser diferente al año inicial',false);
   }

})

//DESEA ACTUALIZAR EL CANON-BTN GRANDE
$('#actualizarCanon').on('click',function(e){
  e.preventDefault();
  $('#aceptarActualizacion').val($(this).val());
  $('#modalAlertaActualizacion').modal('show');
})

$('#aceptarActualizacion').on('click',function(e){
  e.preventDefault();

  $('#modalAlertaActualizacion').modal('hide');

  $('.datosActualizacion').show();
  var id=$(this).val();
  var anio=$('#añoInicioAct2').val();
  $("#tablaActualizacion tbody tr").each(function(){
        $(this).remove();
      });
  $.get('canon/generarTablaActualizacion1/' + id  + '/' + anio, function(data){
    if(data!=null){
      var t1 = 'Valores '+data.informeAnterior.anio_inicio+'/'+
              data.informeAnterior.anio_final;
      $('#t_valor_ant').text(t1);
      var t2 = 'Montos '+(data.informeAnterior.anio_inicio-1)+'/'+
              (data.informeAnterior.anio_final-1);
      $('#t_monto_ant').text(t2);

      var t3 = 'Montos '+data.informeAnterior.anio_inicio+'/'+
              data.informeAnterior.anio_final;
      $('#t_monto_act').text(t3);

      var t4 = 'Valores Base '+data.informeAnterior.anio_inicio+'/'+
              data.informeAnterior.anio_final;
      $('#t4_valor_base_ant').text(t4);

      var t5 = 'Valores Base '+data.informeNuevo.anio_inicio+'/'+
              data.informeNuevo.anio_final;
      $('#t_valor_base_act').text(t5);

      var t6 = 'Valores '+data.informeNuevo.anio_inicio+'/'+
              data.informeNuevo.anio_final;
      $('#t_valor_base_nuevo').text(t6);
      var euro= cargarTablaActualizacion(data,1);
      var dolar= cargarTablaActualizacion(data,2);

      $('#tablaActualizacion').show();
      $('#tablaActualizacion').append(euro);
      $('#tablaActualizacion').append(dolar);

    }
  })
})
//FIN PESTAÑA CANON 2****//

//****PESTAÑA CANON 1 ***//

//btn de filtros
$('#btn-buscar-pagos').click(function(e,pagina,page_size,columna,orden){
  e.preventDefault();

  $('#tablaInicial tbody tr').remove();

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }
  else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaInicial .activa').attr('value'),orden: $('#tablaInicial .activa').attr('estado')} ;

  if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
    var sort_by =  {columna: 'DIFM.fecha_cobro',orden: 'desc'} ;
  }

    var formData= {
      fecha: $('#B_fecha_filtro').val(),
      mes:$('#mesFiltro').val(),
      id_casino: $('#filtroCasino').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
  }

  $.ajax({
      type: 'POST',
      url: 'canon/buscarPagos',
      data: formData,
      dataType: 'json',

      success: function (data) {

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.pagos.total,clickIndice);

          for (var i = 0; i < data.pagos.data.length; i++) {
            var fila=  generarFila(data.pagos.data[i]);
            console.log('fila',fila);
            $('#tablaInicial tbody').append(fila);
          }

          $('#herramientasPaginacion').generarIndices(page_number,page_size,data.pagos.total,clickIndice);
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});

$(document).on('change','#filtroCasino',function(){
  var id= $(this).val();

  if(id != 0){
    $('#mesFiltro option').not('.default').remove();
    $('#mesFiltro').prop('disabled',false);

    $.get('canon/getMeses/' + id, function(data){
        for (var i = 0; i < data.meses.length; i++) {
          const m = data.meses[i];
          const option = $('<option>').text(m.nombre_mes)
          .data('nro_mes',m.nro_mes).data('dia_inicio',m.dia_inicio).data('dia_fin',m.dia_fin);
          $('#mesFiltro').append(option);
        }
    })
  }else{
    $('#mesFiltro option').not('.default').remove();
    $('#mesFiltro').prop('disabled',true);
    $('#mesFiltro').val(0);
  }
})

//btn de ver datos canon
$('#buscarDatos').on('click',function(e){
  e.preventDefault();
  limpiarVer();
  var id_casino=$('#verDatosCanon').val();

  if(id_casino != 0){
    $.get('canon/obtenerCanon/' + id_casino,function(data){
      var casino=(data.canon.nombre).toUpperCase();

      $('#casinoDatos').text('REQUERIMIENTOS ACTUALES EN CASINO ' + casino);
      $('#valorBaseD').text('VALOR BASE DÓLAR: ').append($('<h5>').css('cssText','color:#0D47A1 !important').text(data.canon.valor_base_dolar).css('display','inline').prop('disabled',true));
      $('#valorBaseE').text('VALOR BASE EURO: ').append($('<h5>').css('cssText','color:#0D47A1 !important').text(data.canon.valor_base_euro).css('display','inline').prop('disabled',true));
      $('#periodoValido').text('PERIODO DE VALIDEZ: ').append($('<h5>').css('cssText','color:#0D47A1 !important').text(data.canon.periodo_anio_inicio + ' - ' + data.canon.periodo_anio_fin).css('display','inline').prop('disabled',true));
      $('#guardarModificacion').val(id_casino);
      $('#modalVerYModificar').modal('show');
      $('.modificacion').hide();
      $('#baseNuevoEuro').val(data.canon.valor_base_euro);
      $('#baseNuevoDolar').val(data.canon.valor_base_dolar);

    })

  }

  $('#guardarModificacion').hide();

})

//DENTRO DEL MODAL VER DATOS ACTUALES
$(document).on('click','.modificarCanon',function(e){
  e.preventDefault();

  // limpiarModificar();
  ocultarErrorValidacion($('#baseNuevoDolar'));
  ocultarErrorValidacion($('#baseNuevoEuro'));

  $('.modificacion').show();
  $('#guardarModificacion').show();
});

//GUARDAR DENTRO DEL MODAL DE VER DATOS ACTUALES/MODIFICAR
$('#guardarModificacion').on('click',function(e){

  e.preventDefault();

  var formData= {
    id_casino: $(this).val(),
    valor_base_dolar:$('#baseNuevoDolar').val(),
    valor_base_euro:$('#baseNuevoEuro').val(),
  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'canon/modificar',
      data: formData,
      dataType: 'json',

      success: function (data){

          $('#modalVerYModificar').modal('hide');
          $('#mensajeExito h3').text('EXITO!');
          $('#mensajeExito p').text('Los datos del Canon han sido modificados.');
          $('#mensajeExito').show();
          $('#btn-buscar-pagos').trigger('click',[1,10,'DIFM.fecha_cobro','desc']);
      },

      error: function (data) {
        var response = data.responseJSON.errors;

          if(typeof response.valor_base_euro !== 'undefined'){
            mostrarErrorValidacion($('#baseNuevoDolar'), response.valor_base_euro[0]);
          }
          if(typeof response.valor_base_dolar !== 'undefined'){
            mostrarErrorValidacion($('#baseNuevoEuro'), response.valor_base_dolar[0]);
          }

      }
    })
})

function cargarMeses(id_casino){
  $('#selectMesPago option').remove();
  $.get('canon/getMeses/' + id_casino, function(data){
      for (var i = 0; i < data.meses.length; i++) {
        const m = data.meses[i];
        const option = $('<option>').text(m.nombre_mes).data('nro_mes',m.nro_mes)
        .data('dia_inicio',m.dia_inicio).data('dia_fin',m.dia_fin);
        $('#selectMesPago').append(option);
      }
  });
}

$(document).on('change','#selectCasinoPago', function(){
  if($(this).val() != ""){
    $('.desplegarPago').show();
    $('#guardarPago').show();
    //Solo hago la carga dinamica de meses si esta cargando uno nuevo
    if($('#guardarPago').attr('data-modo') == 'ver') cargarMeses($(this).val());
  }
  else{
    $('.desplegarPago').hide();
    $('#guardarPago').hide();
  }
})

function modalPago(modo,id,id_casino){
  limpiar();

  if(modo == 'modificar'){
    $('#modalPago .modal-title').text("| MODIFICAR PAGO ");
    $('#modalPago .modal-header').css('background-color','#FFA726');
    $('#guardarPago').removeClass('btn-successAceptar').addClass('btn-warningModificar').val(id).attr('data-modo',modo);
    $('#selectCasinoPago').val(id_casino).change();
    $('#selectCasinoPago').attr('disabled',true);
    cargarMeses(id_casino);
    $.get('canon/obtenerPago/' + id, function(data){
      const d = data.detalle;
      $('#cotEuroPago').val(d.cotizacion_euro_actual);
      $('#cotDolarPago').val(d.cotizacion_dolar_actual);
      $('#obsPago').val(d.observaciones);
      $('#fechaPago').val(d.fecha_cobro);
      $('#impuestosPago').val(d.impuestos);
      $('#selectMesPago').find(d.id_mes_casino).prop('selected',true);
      const primer_mes =  $('#selectMesPago option:first').data('nro_mes');
      const nro_mes    = $('#selectMesPago option:selected').data('nro_mes');
      const nro_cuota    = $('#selectMesPago option:selected').data('nro_cuota');
      const es_anio_final = nro_mes < primer_mes || nro_cuota == 13;//Dio vuelta o el nro de cuota es la 13.
      $('#fechaPagoFil').val(es_anio_final? data.informe.anio_final : data.informe.anio_inicio);      
      $('#montoPago').val(d.total_pagado);
    });
  }
  else if(modo == 'ver'){
    $('#modalPago .modal-title').text("| CARGA DE PAGO");
    $('#modalPago .modal-header').css('background-color','#6dc7be');
    $('#guardarPago').removeClass('btn-warningModificar').addClass('btn-successAceptar').val('').attr('data-modo',modo);
    $('#selectCasinoPago').val("").change().attr('disabled',false);
  }
  else return;
  
  $('#modalPago').modal('show');
}

//btn REGISTRAR PAGO (CARGA LOS CASINOS)
$('#pagoCanon').on('click',function(e){
  e.preventDefault();
  modalPago('ver',null,null);
})

//MODIFICAR UN PAGO YA CARGADO
$(document).on('click','.modificarPago',function(e){
  e.preventDefault();
  modalPago('modificar',$(this).val(),$(this).attr('data-casino'));
})

//GUARDAR DENTRO DEL MODAL CARGAR NUEVO PAGO
$('#guardarPago').on('click',function(e){
  e.preventDefault();

  var imp= $('#impuestosPago').val();
  if(imp < 0 || imp == null){
    imp=0;
  }

  const mesSeleccionado = $('#selectMesPago option:selected');
  var formData= {
    cotizacion_dolar: $('#cotDolarPago').val(),
    cotizacion_euro: $('#cotEuroPago').val(),
    impuestos: imp,
    fecha_pago: $('#fechaPago').val(),
    total_pago_pesos:$('#montoPago').val(),
    mes: mesSeleccionado.data('mes'),
    dia_inicio: mesSeleccionado.data('dia_inicio'),
    dia_fin: mesSeleccionado.data('dia_inicio'),
    anio_cuota:$('#fechaPagoFil').val(),
  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'canon/guardarPago',
      data: formData,
      dataType: 'json',

      success: function (data){
          $('#mensajeImportacionError').hide();
          $('#modalPago').modal('hide');
          $('#mensajeExito h3').text('EXITO!');
          $('#mensajeExito p').text('Los datos del Pago han sido guardados.');
          $('#mensajeExito').show();
          $('#btn-buscar-pagos').trigger('click',[1,10,'DIFM.fecha_cobro','desc']);

      },

      error: function (data) {
        var response = data.responseJSON.errors;

          if(typeof response.cotizacion_dolar !== 'undefined'){
            mostrarErrorValidacion($('#cotDolarPago'), response.cotizacion_dolar[0]);
          }
          if(typeof response.cotizacion_euro !== 'undefined'){
            mostrarErrorValidacion($('#cotEuroPago'), response.cotizacion_euro[0]);
          }
          if(typeof response.impuestos !== 'undefined'){
            mostrarErrorValidacion($('#impuestosPago'), response.impuestos[0]);
          }
          if(typeof response.fecha_pago !== 'undefined'){
            mostrarErrorValidacion($('#fechaPago'), response.fecha_pago[0]);
          }
          if(typeof response.total_pago_pesos !== 'undefined'){
            mostrarErrorValidacion($('#montoPago'), response.total_pago_pesos[0]);
          }
          if(typeof response.anio_cuota !== 'undefined'){
            mostrarErrorValidacion($('#dtpFecha'), response.anio_cuota[0]);
          }
          if(typeof response.mes !== 'undefined'){
            mostrarErrorValidacion($('#selectMesPago'), response.mes[0]);
          }
          if(typeof response.importaciones !== 'undefined'){
            $('#help').hide();
            $('#mensajeImportacionError').show();
          }
      }
    })
});


//GUARDAR DENTRO DEL  MODAL DE MODIFICAR PAGO
$('#guardarModifPago').on('click',function(e){
  e.preventDefault();

  var formData= {
    id_detalle: $(this).val(),
    cotizacion_dolar: $('#cotDolarPago').val(),
    cotizacion_euro: $('#cotEuroPago').val(),
    impuestos: $('#impuestosPago').val(),
    fecha_pago: $('#fechaPago').val(),
    total_pago_pesos:$('#montoPago').val(),
    mes:$('#selectMesPago').val(),
  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'canon/modificarPago',
      data: formData,
      dataType: 'json',

      success: function (data){

          $('#modalModificarPago').modal('hide');
          $('#mensajeExito h3').text('EXITO!');
          $('#mensajeExito p').text('Los datos del Pago han sido modificados.');
          $('#mensajeExito').show();
          $('#btn-buscar-pagos').trigger('click',[1,10,'DIFM.fecha_cobro','desc']);

      },

      error: function (data) {
        var response = data.responseJSON.errors;

          if(typeof response.cotizacion_dolar !== 'undefined'){
            mostrarErrorValidacion($('#cotDolarPago'), response.cotizacion_dolar[0]);
          }
          if(typeof response.cotizacion_euro !== 'undefined'){
            mostrarErrorValidacion($('#cotEuroPago'), response.cotizacion_euro[0]);
          }
          if(typeof response.impuestos !== 'undefined'){
            mostrarErrorValidacion($('#impuestosPago'), response.impuestos[0]);
          }
          if(typeof response.fecha_pago !== 'undefined'){
            mostrarErrorValidacion($('#fechaPago'), response.fecha_pago[0]);
          }
          if(typeof response.total_pago_pesos !== 'undefined'){
            mostrarErrorValidacion($('#montoPago'), response.total_pago_pesos[0]);
          }
          if(typeof response.mes !== 'undefined'){
            mostrarErrorValidacion($('#selectMesPago'), response.mes[0]);
          }
      }
    })
});

function limpiar(){
  $('#mensajeImportacionError').hide();
  $('#help').show();
  $('.desplegarPago').hide();
  $('#selectCasinoPago').val("").change();
  $('#dtpFechaPago').data('datetimepicker').reset();
  $('#dtpFecha').data('datetimepicker').reset();
  $('#fechaPago').val('');
  $('#montoPago').val('');
  $('#cotEuroPago').val('');
  $('#cotDolarPago').val('');
  $('#impuestosPago').val('');
  $('#obsPago').val('');
  $('#fechaPagoFil').val('');
  $('#selectMesPago option').remove();
  ocultarErrorValidacion($('#fechaPago'));
  ocultarErrorValidacion($('#montoPago'));
  ocultarErrorValidacion($('#cotEuroPago'));
  ocultarErrorValidacion($('#cotDolarPago'));
  ocultarErrorValidacion($('#impuestosPago'));
  ocultarErrorValidacion($('#selectMesPago'));
  ocultarErrorValidacion($('#fechaPagoFil'));
}

function limpiarVer(){
  $('#casinoDatos').val('');
  $('#valorBase').val('');
  $('#valorPago').val('');
  $('#periodoValido').val('');

}

function limpiarModificar(){
  $('#casinoDatos').val('');
  $('#baseNuevoEuro').val('');
  $('#baseNuevoDolar').val('');
}

function generarFila(data){
  var fila = $('#clonartinicial').clone();
  fila.removeAttr('id');
  fila.attr('id',data.id_detalle_informe_final_mesas);

  fila.find('.mesInicio').text(data.nombre_mes);
  fila.find('.fechaInicio').text(data.fecha_cobro);
  fila.find('.casinoInicio').text(data.nombre);
  fila.find('.dolarInicio').text(data.cotizacion_dolar_actual);
  fila.find('.euroInicio').text(data.cotizacion_euro_actual);
  fila.find('.impInicio').text(data.impuestos);
  fila.find('.modificarPago').val(data.id_detalle_informe_final_mesas).attr('data-casino',data.id_casino);

  fila.css('display','');
  $('#dd').css('display','block');

  return fila;
}

function cargarTablaInforme(data,t){
  if(t==1){
    var fila = $('#clonarT1').clone();
    fila.removeAttr('id');

    fila.find('.mest1').text(data.siglas_mes);
    fila.find('.cotEuroT1').text(data.cotizacion_euro_anterior);
    fila.find('.cotEuro2T1').text(data.cotizacion_euro_actual);
    fila.find('.rdo1t1').text(data.total_mes_anio_anterior);
    fila.find('.rdo2t1').text(data.total_mes_actual);
    if(data.variacion_euro < 0){
      fila.find('.variacionET1').text(data.variacion_euro).css('color','#D32F2F');
    }
    else{
      fila.find('.variacionET1').text(data.variacion_euro);
    }
    fila.find('.variacionET1').text(data.variacion_euro);
    fila.find('.euroT1').text(data.cuota_euro_anterior);
    fila.find('.euro2T1').text(data.cuota_euro_actual);
    fila.css('display','');
    $('#mostrarTabla1').css('display','block');
  }
  else {
    var fila = $('#clonarT2').clone();
    fila.removeAttr('id');

    fila.find('.mesT2').text(data.siglas_mes);
    fila.find('.cotDolar1T2').text(data.cotizacion_dolar_anterior);
    fila.find('.cotDolar2T2').text(data.cotizacion_dolar_actual);
    fila.find('.rdo1T2').text(data.total_mes_anio_anterior);
    fila.find('.rdo2T2').text(data.total_mes_actual);
    if(data.variacion_euro < 0){
      fila.find('.variacionDT2').text(data.variacion_dolar).css('color','#D32F2F');
    }
    else{
      fila.find('.variacionDT2').text(data.variacion_dolar);
    }
    fila.find('.dolar1T2').text(data.cuota_dolar_anterior);
    fila.find('.dolar2T2').text(data.cuota_dolar_actual);


    fila.css('display','');
    $('#mostrarTabla2').css('display','block');
  }


  return fila;
}

function cargarTablaActualizacion(data,t){

    var fila = $('#clonarTA').clone();
    fila.removeAttr('id');
    if(t==1){
      fila.css('background-color','#FFD54F');
      fila.find('.monedaActualizacion').text('Dólar');
      fila.find('.valoresActualizacion').text('$' + data.informeAnterior.base_cobrado_dolar);
      fila.find('.pagos1Actualizacion').text('$' + data.informeAnterior.monto_anterior_dolar);
      fila.find('.pagos2Actualizacion').text('$' + data.informeAnterior.monto_actual_dolar);
      fila.find('.variacionActualizacion').text('%' + data.informeAnterior.variacion_total_dolar);
      fila.find('.vBaseActualizacion').text('$' + data.informeNuevo.base_anterior_dolar);
      fila.find('.vBaseNuevoActualizacion').text('$' + data.informeNuevo.base_actual_dolar);
      fila.find('.vFinalesActualizacion').text('$' + data.informeNuevo.base_cobrado_dolar);
    }
    if(t==2){
      fila.css('background-color','#81C784');

      fila.find('.monedaActualizacion').text('Euro');
      fila.find('.valoresActualizacion').text('$' + data.informeAnterior.base_cobrado_euro);
      fila.find('.pagos1Actualizacion').text('$' + data.informeAnterior.monto_anterior_euro);
      fila.find('.pagos2Actualizacion').text('$' + data.informeAnterior.monto_actual_euro);
      fila.find('.variacionActualizacion').text('%' + data.informeAnterior.variacion_total_euro);
      fila.find('.vBaseActualizacion').text('$' + data.informeNuevo.base_anterior_euro);
      fila.find('.vBaseNuevoActualizacion').text('$' + data.informeNuevo.base_actual_euro);
      fila.find('.vFinalesActualizacion').text('$' + data.informeNuevo.base_cobrado_euro);
    }

    fila.css('display','');
    $('#mostrarTablaAct').css('display','block');


    return fila;
  }
/*****************PAGINACION******************/
$(document).on('click','#tablaInicial thead tr th[value]',function(e){

  $('#tablaInicial th').removeClass('activa');

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
  $('#tablaInicial th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaInicial .activa').attr('value');
  var orden = $('#tablaInicial .activa').attr('estado');
  $('#btn-buscar-pagos').trigger('click',[pageNumber,tam,columna,orden]);
}
