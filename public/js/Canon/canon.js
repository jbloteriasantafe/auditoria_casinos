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
        $('#dtpFechaAnioInicio').datetimepicker({
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
  const id = $(this).val();
  $('#periodo option').remove();
  if(id == 0){
    $('#periodo').prop('disabled',true);
    return;
  }

  $('#periodo').prop('disabled',false);
  $.get('canon/obtenerAnios/'+ id, function(data){
    for (let i = 0; i < data.anios.length; i++) {
      const a = data.anios[i];
      $('#mensajeErrorInforme').hide();
      $('#periodo').append($('<option>').val(a.anio_inicio).text(a.anio_inicio+'-'+a.anio_final));
    }
    if(data.anios.length == 0){
      $('.datosReg').show();
      $('#mensajeErrorInforme').find('.msjtext').text('No hay años para filtrar, puede que no se hayan cargado pagos de Canon, durante un año completo.');
      $('#mensajeErrorInforme').show();
    }
  });
})

//BUSCAR DE DICHA PESTAÑA
$('#buscarActualizar').on('click',function(e){
  e.preventDefault();
  $('.desplegarActualizar').hide();
  $('.datosActualizacion').hide();

  $('#anio1 tbody tr').not('.default1').remove();
  $('#anio2 tbody tr').not('.default2').remove();
  $('#tablaActualizacion tbody tr').remove();
  const formData = {
    id_casino: $('#selectActualizacion').val(),
    anio_inicio:$('#periodo').val(),
  }

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')} });

  $.ajax({
    type: 'POST',
    url: 'canon/verInforme',
    data: formData,
    dataType: 'json',

    success: function (data){
      const result = Object.keys(data.detalles).map(function(key) {
        return [Number(key), data.detalles[key]];
      });

      if(result.length == 0){
        $('.datosReg').show();
        $('#actualizarCanon').hide();
        $('#mensajeErrorInforme').show();
        return;
      }

      $('.casinoInformeFinal').text('EURO').css('text-align','center').css('color','#000');
      $('.casinoInformeFinal2').text('DÓLAR').css('text-align','center').css('color','#000');

      const e = data.informe.anio_inicio;
      const f = e - 1;
      const d = data.informe.anio_final;
      $('.rdo1').text('Rdo.Bruto ' + f + '/' + e);
      $('.rdo2').text('Rdo.Bruto ' + e + '/' + d);
      $('.cotizacion1').text('Cotización ' + f + '/' + e );
      $('.cotizacion2').text('Cotización ' + e + '/' + d );
      $('.valor1').text('Monto ' + f + '/' + e );
      $('.valor2').text('Monto ' + e + '/' + d );

      for (let i = 0; i < result.length; i++) {
        const fila = cargarTablaInforme(result[i][1],1);
        $('#anio1').append(fila);
      }

      for (let i = 0; i < result.length; i++) {
        const fila2 = cargarTablaInforme(result[i][1],2);
        $('#anio2').append(fila2);
      }

      $('.desplegarActualizar').show();
      $('.datosReg').show();
      $('#actualizarCanon').show();
      $('#actualizarCanon').val( $('#selectActualizacion').val());
      $('#mensajeErrorInforme').hide();
    },

    error: function (x) {console.log(x);}
  });
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

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  let size = 10;
  //Fix error cuando librería saca los selectores
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  let sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaInicial .activa').attr('value'),orden: $('#tablaInicial .activa').attr('estado')} ;

  if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
    sort_by =  {columna: 'DIFM.fecha_cobro',orden: 'desc'} ;
  }

  const mesSeleccionado = $('#mesFiltro option:selected');
  const formData = {
    fecha: $('#B_fecha_filtro').val(),
    mes: mesSeleccionado.data('mes'),
    dia_inicio: mesSeleccionado.data('dia_inicio'),
    dia_fin: mesSeleccionado.data('dia_fin'),
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
        for (let i = 0; i < data.pagos.data.length; i++) {
          $('#tablaInicial tbody').append(generarFila(data.pagos.data[i]));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,data.pagos.total,clickIndice);
    },
    error: function (data) {
        console.log('Error:', data);
    }
  });
});

//btn de ver datos canon
$('#buscarDatos').on('click',function(e){
  e.preventDefault();
  $('#casinoDatos').val('');
  $('#valorBase').val('');
  $('#valorPago').val('');
  $('#periodoValido').val('');

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
      $('#guardarModificacion').hide();
    })
  }
})

//DENTRO DEL MODAL VER DATOS ACTUALES
$(document).on('click','.modificarCanon',function(e){
  e.preventDefault();

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

function generarOpcionMes(mes,dia_inicio,dia_fin,anio){
  const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  //asumo que mes 1-12 por lo que no hay que hacer+1
  const ultimo_dia_mes = new Date(anio,mes,0).getDate();
  let dias_str = "";
  if(dia_inicio != 1 || dia_fin != ultimo_dia_mes) dias_str = ` ${dia_inicio} al ${dia_fin}`;
  const anio_str = isNaN(anio)? '' : (' - '+anio);
  return $('<option>').text(meses[mes-1]+dias_str+anio_str)
  .data('dia_inicio',dia_inicio).data('dia_fin',dia_fin).data('mes',mes).data('anio',anio)
}

function cargarMeses(select,id_casino,anio_inicio = null,sync = false){
  console.log(select);
  select.find('option').remove();
  if(id_casino == "" || anio_inicio == "") return;
  anio_inicio = parseInt(anio_inicio);
  $.ajax({
    type: 'GET',
    url: 'canon/getMesesCuotas/' + id_casino,
    async: !sync,
    success: function (data){
      const meses = data.meses;
      anio_inicio = parseInt(anio_inicio);
      const mes_inicial = meses[0].nro_mes;
      for (var i = 0; i < meses.length; i++) {
        const m = meses[i];
        const anio_mes = m.nro_mes < mes_inicial || m.nro_cuota == 13? anio_inicio+1 : anio_inicio;
        select.append(generarOpcionMes(m.nro_mes,m.dia_inicio,m.dia_fin,anio_mes));
      }
    }
  });
}

$(document).on('change','#filtroCasino',function(){
  const id= $(this).val();
  if(id != 0){
    $('#mesFiltro').prop('disabled',false);
    cargarMeses($('#mesFiltro'),id,null);
  }else{
    $('#mesFiltro option').remove();
    $('#mesFiltro').prop('disabled',true);
  }
  $('#mesFiltro').prepend($('<option>').text('Todos los Meses').data('mes',0));
})

$('#selectCasinoPago').change(function(){
  if($(this).val() != ""){
    $('.desplegarPago').show();
    $('#guardarPago').show();
    //Solo hago la carga dinamica de meses si esta cargando uno nuevo
    if($('#guardarPago').attr('data-modo') == 'nuevo') 
      cargarMeses($('#selectMesPago'),$(this).val(),$('#fechaAnioInicio').val());
  }
  else{
    $('.desplegarPago').hide();
    $('#guardarPago').hide();
  }
});

$('#fechaAnioInicio').change(function(){
  if($(this).val() != ""){
    $('#selectMesPago').attr('disabled',false);
    cargarMeses($('#selectMesPago'),$('#selectCasinoPago').val(),$(this).val());
  }
  else{
    $('#selectMesPago').attr('disabled',true).empty();
  }
});

function modalPago(modo,id,id_casino){
  limpiar();

  if(modo == 'modificar'){
    $('#modalPago .modal-title').text("| MODIFICAR PAGO ");
    $('#modalPago .modal-header').css('background-color','#FFA726');
    $('#guardarPago').removeClass('btn-successAceptar').addClass('btn-warningModificar').val(id).attr('data-modo',modo);
    $('#selectCasinoPago').val(id_casino).change();
    $('#selectCasinoPago').attr('disabled',true);
    $.get('canon/obtenerPago/' + id, function(data){
      $('#fechaAnioInicio').val(data.informe.anio_inicio);
      cargarMeses($('#selectMesPago'),id_casino,data.informe.anio_inicio,true);
      const d = data.detalle;
      const opcion_mes = $('#selectMesPago option').filter(function(){
        const t = $(this);
        return  t.data('dia_inicio') == d.dia_inicio && t.data('dia_fin') == d.dia_fin && 
                t.data('mes')        == d.mes        && t.data('anio')    == d.anio;
      })
      //No deberia pasar nunca de que haya muchos pero pongo el primero si ocurre
      if(opcion_mes.length >= 1){
        opcion_mes.eq(0).attr('selected','selected');
        $('#selectMesPago').attr('disabled',false);
      }
      else{
        // No hay ningun mes con esos parametros, solo deberia ocurrir si cambian 
        // la fecha de inicio del casino (que no se puede)
        // Como salvaguarda le creo una opcion y deshabilito la edición
        $('#selectMesPago option').remove();
        $('#selectMesPago').append(generarOpcionMes(d.mes,d.dia_inicio,d.dia_fin,d.anio));
        $('#dtpFecha span').hide();
        $('#fechaAnioInicio').attr('disabled',true);
      }
      $('#fechaPago').val(d.fecha_cobro);
      $('#cotEuroPago').val(d.cotizacion_euro_actual);
      $('#cotDolarPago').val(d.cotizacion_dolar_actual);
      $('#impuestosPago').val(d.impuestos);
      $('#montoPago').val(d.total_pagado);
      $('#obsPago').val(d.observaciones);
    });
  }
  else if(modo == 'nuevo'){
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
  modalPago('nuevo',null,null);
})

//MODIFICAR UN PAGO YA CARGADO
$(document).on('click','.modificarPago',function(e){
  e.preventDefault();
  modalPago('modificar',$(this).val(),$(this).attr('data-casino'));
})

//GUARDAR DENTRO DEL MODAL CARGAR NUEVO PAGO
$('#guardarPago').on('click',function(e){
  e.preventDefault();

  const mesSeleccionado = $('#selectMesPago option:selected');
  const formData = {
    id_detalle_informe_final_mesas: $('#guardarPago').val(),
    id_casino: $('#selectCasinoPago').val(),
    anio_inicio: $('#fechaAnioInicio').val(),
    anio: mesSeleccionado.data('anio'),
    mes: mesSeleccionado.data('mes'),
    dia_inicio: mesSeleccionado.data('dia_inicio'),
    dia_fin: mesSeleccionado.data('dia_fin'),
    fecha_pago: $('#fechaPago').val(),
    cotizacion_euro: $('#cotEuroPago').val(),
    cotizacion_dolar: $('#cotDolarPago').val(),
    total_pago_pesos:$('#montoPago').val(),
    impuestos: $('#impuestosPago').val() == null? 0 : $('#impuestosPago').val(),
  };

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  let url = 'canon/';
  const modo = $('#guardarPago').attr('data-modo');
  if(modo == 'nuevo') url+='guardarPago';
  else if(modo == 'modificar') url+='modificarPago';
  else return;

  $.ajax({
      type: 'POST',
      url: url,
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
          mostrarErrorValidacion($('#dtpFechaAnioInicio'), response.anio_cuota[0]);
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
  $('#dtpFechaAnioInicio').data('datetimepicker').reset();
  $('#dtpFecha span').show();
  $('#fechaAnioInicio').attr('disabled',false);
  $('#fechaPago').val('');
  $('#montoPago').val('');
  $('#cotEuroPago').val('');
  $('#cotDolarPago').val('');
  $('#impuestosPago').val('');
  $('#obsPago').val('');
  $('#fechaAnioInicio').val('');
  $('#selectMesPago option').remove();
  ocultarErrorValidacion($('#fechaPago'));
  ocultarErrorValidacion($('#montoPago'));
  ocultarErrorValidacion($('#cotEuroPago'));
  ocultarErrorValidacion($('#cotDolarPago'));
  ocultarErrorValidacion($('#impuestosPago'));
  ocultarErrorValidacion($('#selectMesPago'));
  ocultarErrorValidacion($('#fechaAnioInicio'));
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
