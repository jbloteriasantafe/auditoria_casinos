$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Mesas - Canon');
  //pestañas
  $('#pestCanon').show();
  $('#pestCanon').css('display','inline-block');

  $(".tab_content").hide(); //Hide all content
  $("ul.pestCanon li:first").addClass("active").show(); //Activate first tab
  $(".tab_content:first").show(); //Show first tab content

  $('#collapseFiltros').focus();
  $('#B_fecha_filtro').val('');

  const dtp_fecha_iso = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  };

  $('#dtpFechaPago').datetimepicker(dtp_fecha_iso);
  $('#dtpFechaCotizacion').datetimepicker(dtp_fecha_iso);

  const dtp_fecha_anio = {
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
  };

  $('#dtpFechaFiltro').datetimepicker(dtp_fecha_anio);
  $('#dtpFechaAnioInicio').datetimepicker(dtp_fecha_anio);
  $('#btn-buscar-meses').click();
  cargarMesesFiltro();
});

//PESTAÑAS
$("ul.pestCanon li").click(function() {
    $("ul.pestCanon li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    const activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
    if(activeTab == '#pant_canon_valores'){
      $('#periodo').prop('disabled',true);
      $('#periodo option').remove();
      $('#selectActualizacion').val(0).change();
      $('#collapseFiltros3').collapse("show")
    }
    $(activeTab).fadeIn(); //Fade in the active ID content
});

//INICIO PESTAÑA CANON 2*****//
//SELECT Q HABILITA Y PERMITE CARGAR SELECTS DE AÑOS DEPENIENDO EL CASINO
$(document).on('change','#selectActualizacion', function(e){
  e.preventDefault();
  $('#tablasMontosVB').hide();
  $('#divBaseCanon').hide();
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
      $('#divBaseCanon').show();
      $('#mensajeErrorInforme').find('.msjtext').text('No hay años para filtrar, puede que no se hayan cargado pagos de Canon, durante un año completo.');
      $('#mensajeErrorInforme').show();
    }
  });
})

//BUSCAR DE DICHA PESTAÑA
$('#buscarActualizar').on('click',function(e){
  e.preventDefault();
  $('#tablasMontosVB').hide();
  $('#tablaEuro,#tablaDolar').find('tbody tr').remove();

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
      const porcentaje = function(obj,viejo,nuevo){
        const p = ((nuevo/viejo - 1)*100).toFixed(2);
        obj.text(p+"%").css('color',p>=0?'green':'red');
      }
      
      $('#filaEuroBaseCanon  .base').text(parseFloat(data.actual.euro.base).toFixed(2));
      $('#filaDolarBaseCanon .base').text(parseFloat(data.actual.dolar.base).toFixed(2));
      $('#filaEuroBaseCanon  .baseNuevo').text(parseFloat(data.nuevo.euro.base).toFixed(2));
      $('#filaDolarBaseCanon .baseNuevo').text(parseFloat(data.nuevo.dolar.base).toFixed(2));
      $('#filaEuroBaseCanon  .canon').text(parseFloat(data.actual.euro.canon).toFixed(2));
      $('#filaDolarBaseCanon .canon').text(parseFloat(data.actual.dolar.canon).toFixed(2));
      $('#filaEuroBaseCanon  .canonNuevo').text(parseFloat(data.nuevo.euro.canon).toFixed(2));
      $('#filaDolarBaseCanon .canonNuevo').text(parseFloat(data.nuevo.dolar.canon).toFixed(2));

      porcentaje($('#filaEuroBaseCanon  .variacion'),data.actual.euro.base,data.nuevo.euro.base);
      porcentaje($('#filaDolarBaseCanon .variacion'),data.actual.euro.base,data.nuevo.euro.base);

      let meses = {};
      const poner_en_meses = function(d){
        const key = siglaMes(d.mes,d.dia_inicio,d.dia_fin,d.anio);
        if(!meses[key]) meses[key] = {};
        meses[key][d.id_informe_final_mesas] = {
          anio: d.anio,
          mes: d.mes,
          dia_inicio: d.dia_inicio,
          dia_fin: d.dia_fin,          
          bruto: d.bruto_peso,
          cotizacion_euro: d.cotizacion_euro_actual,
          cotizacion_dolar: d.cotizacion_dolar_actual,
          bruto_euro: parseFloat(d.medio_bruto_euro).toFixed(2),
          bruto_dolar: parseFloat(d.medio_bruto_dolar).toFixed(2)
        };
      }
      data.detalles_anterior.forEach(poner_en_meses);
      data.detalles.forEach(poner_en_meses);
      {
        const d = data.informe_anterior;
        if(d){
          if(!meses['TOTAL']) meses['TOTAL'] = {};
          meses['TOTAL'][d.id_informe_final_mesas] = {
            anio: 'TOTAL',mes: 'TOTAL',dia_inicio: 'TOTAL',dia_fin: 'TOTAL',
            bruto: d.total_peso, 
            cotizacion_euro: '', cotizacion_dolar: '',
            bruto_euro: parseFloat(d.medio_total_euro).toFixed(2),
            bruto_dolar: parseFloat(d.medio_total_dolar).toFixed(2)
          };
        }
      }
      {
        const d = data.informe;
        if(d){
          if(!meses['TOTAL']) meses['TOTAL'] = {};
          meses['TOTAL'][d.id_informe_final_mesas] = {
            anio: 'TOTAL',mes: 'TOTAL',dia_inicio: 'TOTAL',dia_fin: 'TOTAL',
            bruto: d.total_peso, 
            cotizacion_euro: '', cotizacion_dolar: '',
            bruto_euro: parseFloat(d.medio_total_euro).toFixed(2),
            bruto_dolar:parseFloat(d.medio_total_dolar).toFixed(2)
          };
        }
      }

      if(Object.keys(meses).length == 0){
        $('#divBaseCanon').show();
        $('#mensajeErrorInforme').show();
        return;
      }

      {
        const e = data.informe.anio_inicio;
        const f = e - 1;
        const d = data.informe.anio_final;
        $('.rdo1').text('Rdo.Bruto ' + f + '/' + e);
        $('.rdo2').text('Rdo.Bruto ' + e + '/' + d);
        $('.cotizacion1').text('Cotización ' + f + '/' + e );
        $('.cotizacion2').text('Cotización ' + e + '/' + d );
        $('.valor1').text('Monto ' + f + '/' + e );
        $('.valor2').text('Monto ' + e + '/' + d );
      }

      const anterior = data.informe_anterior?.id_informe_final_mesas;
      const actual = data.informe.id_informe_final_mesas;
      for(sigla in meses){
        const m_actual = meses[sigla][actual];
        const m_anterior = meses[sigla][anterior];
        
        const filaE = $('#clonarT').clone().removeAttr('id').css('display','');

        const filaD = $('#clonarT').clone().removeAttr('id').css('display','');
        filaE.find('.mesT').text(sigla);
        filaD.find('.mesT').text(sigla);

        if(m_actual){
          filaE.find('.rdo2T').text(m_actual.bruto);
          filaE.find('.cot2T').text(m_actual.cotizacion_euro);
          filaE.find('.monto2T').text(m_actual.bruto_euro);
          filaD.find('.rdo2T').text(m_actual.bruto);
          filaD.find('.cot2T').text(m_actual.cotizacion_dolar);
          filaD.find('.monto2T').text(m_actual.bruto_dolar);
        }

        if(m_anterior){
          filaE.find('.rdo1T').text(m_anterior.bruto);
          filaE.find('.cot1T').text(m_anterior.cotizacion_euro);
          filaE.find('.monto1T').text(m_anterior.bruto_euro);
          filaD.find('.rdo1T').text(m_anterior.bruto);
          filaD.find('.cot1T').text(m_anterior.cotizacion_dolar);
          filaD.find('.monto1T').text(m_anterior?.bruto_dolar);
        }

        if(m_actual && m_anterior){
          porcentaje(filaE.find('.variacionT'),m_anterior.bruto_euro,m_actual.bruto_euro);
          porcentaje(filaD.find('.variacionT'),m_anterior.bruto_dolar,m_actual.bruto_dolar);
        }

        $('#tablaEuro').append(filaE);
        $('#tablaDolar').append(filaD);
      }
      $('#tablaEuro,#tablaDolar').find('tbody tr:last td').css('border-top','2px solid #ccc')

      $('#tablasMontosVB').show();
      $('#divBaseCanon').show();
      $('#mensajeErrorInforme').hide();
    },
    error: function (x) {console.log(x);}
  });
})


//FIN PESTAÑA CANON 2****//

//****PESTAÑA CANON 1 ***//

//btn de filtros
$('#btn-buscar-meses').click(function(e,pagina,page_size,columna,orden){
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

  if(typeof sort_by['columna'] == 'undefined'){
    sort_by =  "";
  }

  const mesSeleccionado = $('#mesFiltro option:selected');
  const formData = {
    anio: $('#B_fecha_filtro').val(),
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
          const d = data.pagos.data[i];
          const fila = $('#clonartinicial').clone().attr('id',d.id_detalle_informe_final_mesas).css('display','');
          fila.find('.anioInicio').text(d.anio);
          fila.find('.mesInicio').text(siglaMes(d.mes,d.dia_inicio,d.dia_fin,d.anio));
          fila.find('.casinoInicio').text(d.nombre);
          fila.find('.montoInicio').text(d.bruto_peso);
          fila.find('.dolarInicio').text(d.cotizacion_dolar_actual);
          fila.find('.euroInicio').text(d.cotizacion_euro_actual);
          fila.find('button').val(d.id_detalle_informe_final_mesas).attr('data-casino',d.id_casino);
          $('#tablaInicial tbody').append(fila);
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,data.pagos.total,clickIndice);
    },
    error: function (data) {
      console.log(data);
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

  const id_casino = $('#verDatosCanon').val();
  if(id_casino == 0) return;
  $.get('canon/obtenerInformeBase/' + id_casino,function(data){
    if(!data){   
      $('#casinoDatos').text('NECESITA CARGAR ALGUN PAGO ANTES DE INGRESAR EL VALOR BASE');
      $('#nuevosValoresBaseCasino').hide();
      $('#guardarModificacion').hide();
      return;
    }
    $('#nuevosValoresBaseCasino').show();
    $('#guardarModificacion').show();

    $('#casinoDatos').text('VALORES BASE ORIGINALES PARA ' + $('#verDatosCanon option:selected').text().toUpperCase());

    $('#valorBaseE p').text(data.base_anterior_euro+0);//cast null to int
    $('#valorBaseD p').text(data.base_anterior_dolar+0);
    $('#periodoValido p').text((data.anio_inicio+0) + ' - ' + (data.anio_final + 1));

    $('#baseNuevoEuro').val(data.base_anterior_euro);
    $('#baseNuevoDolar').val(data.base_anterior_dolar)

    $('#guardarModificacion').val(id_casino);
    $('#modalVerYModificar').modal('show');
  });
})

//GUARDAR DENTRO DEL MODAL DE VER DATOS ACTUALES/MODIFICAR
$('#guardarModificacion').on('click',function(e){
  e.preventDefault();

  const formData = {
    id_casino: $(this).val(),
    valor_base_dolar:$('#baseNuevoDolar').val(),
    valor_base_euro:$('#baseNuevoEuro').val(),
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  $.ajax({
    type: 'POST',
    url: 'canon/modificarInformeBase',
    data: formData,
    dataType: 'json',
    success: function (data){
        $('#modalVerYModificar').modal('hide');
        $('#mensajeExito h3').text('EXITO!');
        $('#mensajeExito p').text('Los datos del Canon han sido modificados.');
        $('#mensajeExito').show();
        $('#btn-buscar-meses').click();
    },
    error: function (data) {
      const response = data.responseJSON;
      if(typeof response.valor_base_euro !== 'undefined'){
        mostrarErrorValidacion($('#baseNuevoDolar'), response.valor_base_euro[0]);
      }
      if(typeof response.valor_base_dolar !== 'undefined'){
        mostrarErrorValidacion($('#baseNuevoEuro'), response.valor_base_dolar[0]);
      }
    }
  });
})

function generarOpcionMes(mes,dia_inicio,dia_fin,anio){
  const anio_str = isNaN(anio)? '' : (' - '+anio);
  return $('<option>').text(siglaMes(mes,dia_inicio,dia_fin,anio)+anio_str)
  .data('dia_inicio',dia_inicio).data('dia_fin',dia_fin).data('mes',mes).data('anio',anio)
}

// "sync" Se podria sacar poniendo un callback que se llama al final de success como un argumento pero hace el codigo
// un poco mas complicado
function cargarMeses(select,id_casino,anio_inicio = undefined,sync = false){
  select.find('option').remove();
  if(id_casino == "" || anio_inicio == "") return;
  anio_inicio = parseInt(anio_inicio);
  $.ajax({
    type: 'GET',
    url: 'canon/getMesesCuotas/' + id_casino + '/' + anio_inicio,
    async: !sync,
    success: function (data){
      const meses = data.meses;
      anio_inicio = parseInt(anio_inicio);
      const mes_inicial = meses[0];
      for (let i = 0; i < meses.length; i++) {
        const m = meses[i];
        const anio_mes = m.nro_mes < mes_inicial.nro_mes || (m.nro_mes == mes_inicial.nro_mes && m != mes_inicial) ? 
        anio_inicio+1 : anio_inicio;
        select.append(generarOpcionMes(m.nro_mes,m.dia_inicio,m.dia_fin,anio_mes));
      }
    }
  });
}

function cargarMesesFiltro(){
  const id = $('#filtroCasino').val();
  const anio = $('#B_fecha_filtro').val();
  $('#mesFiltro option').remove();
  $('#mesFiltro').prepend($('<option>').text('Todos los Meses').data('mes',0));
  if(anio == "" || id == ""){
    const meses = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    $('#mesFiltrop option').remove();
    for(let midx = 0;midx < meses.length;midx++){
      $('#mesFiltro').append($('<option>').text(meses[midx]).data('mes',midx+1));
    }
    return;
  }
  $('#mesFiltro').prop('disabled',false);
  cargarMeses($('#mesFiltro'),id,anio);
}

$(document).on('change','#filtroCasino',cargarMesesFiltro);
$(document).on('change','#B_fecha_filtro',cargarMesesFiltro);

$('#selectCasino').change(function(){
  if($(this).val() != ""){
    $('.desplegarPago').show();
    $('#guardarMes').show();
    //Solo hago la carga dinamica de meses si esta cargando uno nuevo
    if($('#guardarMes').attr('data-modo') == 'nuevo') $('#fechaAnioInicio').change();
  }
  else{
    $('.desplegarPago').hide();
    $('#guardarMes').hide();
  }
});

$('#fechaAnioInicio').change(function(){
  const anio_inicio = $(this).val();
  if(anio_inicio.length == 0){
    return $('#selectMes').attr('disabled',true).empty();
  }
  $('#selectMes').attr('disabled',false);
  const id_casino = $('#selectCasino').val();

  cargarMeses($('#selectMes'),id_casino,anio_inicio,true);
  //Saco los que ya esten cargados para facilitar la carga
  $.get('canon/mesesCargados/'+id_casino+'/'+anio_inicio, function(data){
    data.forEach(function(m){
      const ops = $('#selectMes option');
      for(const opidx in ops){
        const op = ops.eq(opidx);
        if(op.data('anio') == m.anio && op.data('mes') == m.mes && op.data('dia_inicio') == m.dia_inicio){
          op.remove();
          break;
        }
      }
    });
    if($('#selectMes option').length == 0){
      $('#selectMes').append('<option>Todos los meses están cargados</option>').attr('disabled',true);
    } 
  });
});

$('#fechaPago').change(function(){
  let fecha = new Date($(this).val()+'T00:00:00.000-03:00');
  const dia = 1000*60*60*24;
  while(fecha.getDay() == 0 || fecha.getDay() == 6){
    fecha = new Date(fecha-dia);
  }
  $('#dtpFechaCotizacion').data('datetimepicker').setDate(fecha);
});

function modalMes(modo,id,id_casino){
  limpiar();

  if(modo == 'modificar'){
    $('#modalMes .modal-title').text("| MODIFICAR MES ");
    $('#modalMes .modal-header').css('background-color','#FFA726');
    $('#guardarMes').removeClass('btn-successAceptar').addClass('btn-warningModificar').val(id).attr('data-modo',modo);
    $('#selectCasino').val(id_casino).change();
    $('#selectCasino').attr('disabled',true);
    $.get('canon/obtenerPago/' + id, function(data){
      $('#fechaAnioInicio').val(data.informe.anio_inicio);
      cargarMeses($('#selectMes'),id_casino,data.informe.anio_inicio,true);
      const d = data.detalle;
      const opcion_mes = $('#selectMes option').filter(function(){
        const t = $(this);
        return  t.data('dia_inicio') == d.dia_inicio && t.data('dia_fin') == d.dia_fin && 
                t.data('mes')        == d.mes        && t.data('anio')    == d.anio;
      })
      //No deberia pasar nunca de que haya muchos pero pongo el primero si ocurre
      if(opcion_mes.length >= 1){
        opcion_mes.eq(0).attr('selected','selected');
        $('#selectMes').attr('disabled',false);
      }
      else{
        // No hay ningun mes con esos parametros, solo deberia ocurrir si cambian 
        // la fecha de inicio del casino (que no se puede)
        // Como salvaguarda le creo una opcion y deshabilito la edición
        $('#selectMes option').remove();
        $('#selectMes').append(generarOpcionMes(d.mes,d.dia_inicio,d.dia_fin,d.anio));
        $('#dtpFecha span').hide();
        $('#fechaAnioInicio').attr('disabled',true);
      }
      $('#fechaPago').val(d.fecha_pago);
      $('#fechaCotizacion').val(d.fecha_cotizacion);
      $('#cotEuroPago').val(d.cotizacion_euro_actual);
      $('#cotDolarPago').val(d.cotizacion_dolar_actual);
      $('#montoPesos').val(d.bruto_peso);
    });
  }
  else if(modo == 'nuevo'){
    $('#modalMes .modal-title').text("| CARGA DE MES");
    $('#modalMes .modal-header').css('background-color','#6dc7be');
    $('#guardarMes').removeClass('btn-warningModificar').addClass('btn-successAceptar').val('').attr('data-modo',modo);
    $('#selectCasino').val("").change().attr('disabled',false);
  }
  else return;
  
  $('#modalMes').modal('show');
}

$('#mesCanon').on('click',function(e){
  e.preventDefault();
  modalMes('nuevo',null,null);
})

$(document).on('click','.modificarMes',function(e){
  e.preventDefault();
  modalMes('modificar',$(this).val(),$(this).attr('data-casino'));
})

$(document).on('click','.eliminarMes',function(e){
  e.preventDefault();
  $.ajax({
    type: 'DELETE',
    url: 'canon/borrarPago/' + $(this).val(),
    success: function(x){$('#btn-buscar-meses').click();},
    error: function(x){console.log(x);}
  });
})

//GUARDAR DENTRO DEL MODAL CARGAR NUEVO PAGO
$('#guardarMes').on('click',function(e){
  e.preventDefault();

  const mesSeleccionado = $('#selectMes option:selected');
  const formData = {
    id_detalle_informe_final_mesas: $('#guardarMes').val(),
    id_casino: $('#selectCasino').val(),
    anio_inicio: $('#fechaAnioInicio').val(),
    anio: mesSeleccionado.data('anio'),
    mes: mesSeleccionado.data('mes'),
    dia_inicio: mesSeleccionado.data('dia_inicio'),
    dia_fin: mesSeleccionado.data('dia_fin'),
    fecha_pago: $('#fechaPago').val(),
    fecha_cotizacion: $('#fechaCotizacion').val(),
    cotizacion_euro: $('#cotEuroPago').val(),
    cotizacion_dolar: $('#cotDolarPago').val(),
    bruto_peso: $('#montoPesos').val(),
  };

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  let url = 'canon/crearOModificarPago';
  const modo = $('#guardarMes').attr('data-modo');
  if(modo != 'nuevo' && modo != 'modificar') return;

  $.ajax({
      type: 'POST',
      url: url,
      data: formData,
      dataType: 'json',

      success: function (data){
        $('#modalMes').modal('hide');
        $('#mensajeExito h3').text('EXITO!');
        $('#mensajeExito p').text('Los datos del mes han sido guardados.');
        $('#mensajeExito').show();
        $('#btn-buscar-meses').click();
      },
      error: function (data) {
        const response = data.responseJSON;

        if(typeof response.cotizacion_dolar !== 'undefined'){
          mostrarErrorValidacion($('#cotDolarPago'), parseError(response.cotizacion_dolar[0]));
        }
        if(typeof response.cotizacion_euro !== 'undefined'){
          mostrarErrorValidacion($('#cotEuroPago'), parseError(response.cotizacion_euro[0]));
        }
        if(typeof response.fecha_pago !== 'undefined'){
          mostrarErrorValidacion($('#fechaPago'), parseError(response.fecha_pago[0]));
        }
        if(typeof response.bruto_pesos !== 'undefined'){
          mostrarErrorValidacion($('#montoPesos'), parseError(response.total_pago_pesos[0]));
        }
        if(typeof response.anio_inicio !== 'undefined'){
          mostrarErrorValidacion($('#fechaAnioInicio'), parseError(response.anio_inicio[0]));
        }
        if(typeof response.anio !== 'undefined'){
          mostrarErrorValidacion($('#selectMes'), parseError(response.anio[0]));
        }
        if(typeof response.mes !== 'undefined'){
          mostrarErrorValidacion($('#selectMes'), parseError(response.mes[0]));
        }
        if(typeof response.dia_inicio !== 'undefined'){
          mostrarErrorValidacion($('#selectMes'), parseError(response.dia_inicio[0]));
        }
        if(typeof response.dia_fin !== 'undefined'){
          mostrarErrorValidacion($('#selectMes'), parseError(response.dia_fin[0]));
        }
      }
    })
});

function limpiar(){
  $('.desplegarPago').hide();
  $('#selectCasino').val("").change();
  $('#dtpFechaAnioInicio').data('datetimepicker').reset();
  $('#dtpFechaPago').data('datetimepicker').reset();
  $('#dtpFechaCotizacion').data('datetimepicker').reset();
  $('#fechaAnioInicio').attr('disabled',false);
  $('#modalMes input').val('');
  $('#selectMes option').remove();
  ocultarErrorValidacion($('#modalMes input'));
  ocultarErrorValidacion($('#selectMes'));
}

function siglaMes(nro_mes,dia_inicio,dia_fin,anio){//Necesitas el anio para saber si febrero esta completo
  const meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
  const ultimo_dia_mes = new Date(anio,nro_mes,0).getDate();
  let dia_str = "";
  if(dia_inicio != 1 || ultimo_dia_mes != parseInt(dia_fin)) dia_str = " "+dia_inicio+"-"+dia_fin;
  return meses[nro_mes-1]+dia_str;
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
  $('#btn-buscar-meses').trigger('click',[pageNumber,tam,columna,orden]);
}

function parseError(response){
  errors = {
      'validation.unique'       :'El valor tiene que ser único y ya existe el mismo.',
      'validation.required'     :'El campo es obligatorio.',
      'validation.max.string'   :'El valor es muy largo.',
      'validation.exists'       :'El valor no es valido.',
      'validation.min.numeric'  :'El valor no es valido.',
      'validation.integer'      :'El valor tiene que ser un número entero.',
      'validation.regex'        :'El valor no es valido.',
      'validation.required_if'  :'El valor es requerido.',
      'validation.required_with':'El valor es requerido.',
      'validation.before'       :'El valor supera el limite.',
      'validation.after'        :'El valor precede el limite.',
      'validation.max.numeric'  :'El valor supera el limite.',
  };
  if(response in errors) return errors[response];
  return response;
}
