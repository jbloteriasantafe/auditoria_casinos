$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass().addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass().addClass('subMenu2 collapse in');
  $('#contadores').removeClass().addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Producidos');
  $('#opcProducidos').addClass('opcionesSeleccionado')
  .attr('style','border-left: 6px solid #673AB7; background-color: #131836;')

  $('#fecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    ignoreReadonly: true,
  });
  
  $('#dtpFechaInicio,#dtpFechaFin').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd / mm / yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });

  $('#btn-buscar').trigger('click');
});

//SI INGRESA ALGO EN ALGUN INPUT, se recalcula la diferencia
//Lo mas logico seria recalcular en el backend pero no hace tan responsive el input
$(document).on('input', '#frmCargaProducidos input' , function(e){
  $('#btn-salir').data('salida',false);
  $('#modalCargaProducidos .mensajeSalida span').hide();

  //actualizo la diferencia
  const denominacion       = parseFloat($('#data-denominacion').val());
  const coinin_inicial     = parseInt($('#coininIni').val())  * denominacion;
  const coinout_inicial    = parseInt($('#coinoutIni').val()) * denominacion;
  const jackpot_inicial    = parseInt($('#jackIni').val())    * denominacion;
  const progresivo_inicial = parseInt($('#progIni').val())    * denominacion;
  const coinin_final       = parseInt($('#coininFin').val())  * denominacion;
  const coinout_final      = parseInt($('#coinoutFin').val()) * denominacion;
  const jackpot_final      = parseInt($('#jackFin').val())    * denominacion;
  const progresivo_final   = parseInt($('#progFin').val())    * denominacion;
  const producido_sistema  = parseFloat($('#prodSist').val());
  const valor_final        = coinin_final   - coinout_final   - jackpot_final   - progresivo_final;
  const valor_inicio       = coinin_inicial - coinout_inicial - jackpot_inicial - progresivo_inicial;

  //Aca esta redondeando a 2 digitos
  const producido_calculado = Math.round((valor_final - valor_inicio)*100)/100;
  const diferencia          = Math.round((producido_calculado - producido_sistema)*100)/100;
  $('#prodCalc').val(producido_calculado);
  $('#diferencias').text(diferencia);
  if(diferencia == 0){
    $('#btn-finalizar').show();
  }
})

$(document).on('change','#tipoAjuste',function(){
  //Ver tabla en ProducidoController:guardarAjuste
  const permitir_finales   = [1,2,3,6];
  const permitir_iniciales = [5,6];
  const permitir_producido = [4,6];
  const id_tipo_ajuste     = parseInt($(this).val());

  $('.cont_finales input').attr('disabled',!permitir_finales.includes(id_tipo_ajuste));
  $('.cont_iniciales input').attr('disabled',!permitir_iniciales.includes(id_tipo_ajuste));
  $('#prodSist').attr('disabled',!permitir_producido.includes(id_tipo_ajuste));
  //Vuelvo a los valores originales
  $('.cont_finales input,.cont_iniciales input').each(function(){$(this).val($(this).data('original'));});
  $('#prodSist').val($('#prodSist').data('original')).trigger('input');//Trigger para recalcular
  const permitir_ajuste_automatico = [0,3,5];
  $('#ajustarProducido').attr('disabled',!permitir_ajuste_automatico.includes(id_tipo_ajuste));
});

$('#ajustarProducido').click(function(){
  switch($('#tipoAjuste').val()){
    case '0':
    {//No selecciono nada
      const ini = $('#coininIni,#coinoutIni,#jackIni,#progIni')
      .map(function(idx,obj){return $(obj).val();}).toArray().join('|');
      const fin  = $('#coininFin,#coinoutFin,#jackFin,#progFin')
      .map(function(idx,obj){return $(obj).val();}).toArray().join('|');
      const ceros = '0|0|0|0';
      const dif = parseFloat($('#diferencias').text());

      let ajuste = null;
      if     (ini == ceros && fin != ceros) ajuste = "5";
      else if(ini != ceros && fin == ceros) ajuste = "3";
      //No estoy seguro si esto agarra 100% de los casos
      else if((dif%1000000) == 0 && dif != 0){//No deberia haber dif = 0 pero bueno por las dudas chequeo
        const conts = ['coinin','coinout','jack','prog'];
        let contadorMenor = false;
        //Chequeo si algun contador final es menor al inicial
        for(const idx in conts){
          const c = conts[cidx];
          const ini = parseInt($('#'+c+'Ini').val());
          const fin = parseInt($('#'+c+'Fin').val());
          if(fin < ini){
            contadorMenor = true;
            break;
          }
        }
        if(contadorMenor) ajuste = '1';
      }

      if(ajuste == null) return;

      $('#tipoAjuste').val(ajuste).change();
      $('#ajustarProducido').click();
    }
    case '5'://Cambio contadores iniciales
    {
      $('#coininIni').val($('#coininFin').val());
      $('#coinoutIni').val($('#coinoutFin').val());
      $('#jackIni').val($('#jackFin').val());
      $('#progIni').val($('#progFin').val()).focusout();
    }break;
    case '3'://Cambio contadores finales
    {
      $('#coininFin').val($('#coininIni').val());
      $('#coinoutFin').val($('#coinoutIni').val());
      $('#jackFin').val($('#jackIni').val());
      $('#progFin').val($('#progIni').val()).focusout();
    }break;
    default:{
    }break;
  }
});

//Permite hacer aritmetica basica en los campos
$(document).on('focusout' ,'#frmCargaProducidos input' , function(e){
  if($(this).val() == '') $(this).val(0);

const val = $(this).val().replaceAll(/(^|[-+*/])0+[1-9][0-9]*/g,function(match){
    //Elimino los 0s de adelante para evitar que javascript interprete numeros como octales >:(
    //Osea pasa 0123+004123*0532 a 123+4123*532
    if(['-','+','*','/'].indexOf(match[0]) != -1){//Si el primer caracter es un operador
      return match[0]+match.substring(1).replace(/^0+/g,"");
    }
    return match.replace(/^0+/g,"");
  });
  //Solo permite {NUMERO OPERADOR} {NUMERO OPERADOR} ... NUMERO
  //{} significa opcional, operadores validos - + * /
  const valid = /^(-?[0-9]+[-+*/])*-?[0-9]+$/.test(val);
  if(valid) $(this).val(eval(val));
  else $(this).val(NaN);
  $(this).trigger('input');
});

//AJUSTAR PRODUCIDO, boton de la lista
$(document).on('click','.carga',function(e){
  e.preventDefault();
  $('#columnaDetalle').hide();
  $('#mensajeExito').modal('hide');

  limpiarCuerpoTabla();

  //permitir salir y ocultar mensaje de salida
  $('#btn-salir').data('salida',true);
  $('#modalCargaProducidos .mensajeSalida span').hide();

  const tr_html = $(this).parent().parent();
  const id_producido = $(this).val();
  const moneda = tr_html.find('.tipo_moneda').text();
  const fecha_prod = tr_html.find('.fecha_producido').text();
  const casino = tr_html.find('.casino').text();
  $('#descripcion_validacion').text(casino+' - '+fecha_prod+' - $'+moneda);
  $('#maquinas_con_diferencias').text('---');

  $('#modalCargaProducidos #id_producido').val(id_producido);
  //ME TRAE LAS MÁQUINAS RELACIONADAS CON ESE PRODUCIDO, PRIMER TABLA DEL MODAL
  $.get('producidos/ajustarProducido/' + id_producido, function(data){
    if(data.validado.estaValidado){
      $('#btn-minimizar').hide();
      $('#cuerpoTabla').append(
        $('<div>').addClass('row').append(
          $('<div>').addClass('col-xs-6').append(
            $('<h3>').text('El producido ahora está validado. No se encontraron diferencias')
          )
        )
      );
      $('#textoExito').hide();
      $('#btn-salir-validado').show();
      $('#btn-salir').hide();
      $('#btn-buscar').click();
      return;
    }
    $('#descripcion_validacion').text(casino+' - '+data.fecha_produccion+' - $'+data.moneda.descripcion);
    $('#maquinas_con_diferencias').text(data.producidos_con_diferencia.length);
    for (let i = 0; i < data.producidos_con_diferencia.length; i++) {
      const fila = $('#filaClon').clone().removeAttr('id');
      fila.attr('id',  data.producidos_con_diferencia[i].id_maquina);
      fila.find('.nroAdm').text(data.producidos_con_diferencia[i].nro_admin);
      fila.find('.infoMaq').val(data.producidos_con_diferencia[i].id_maquina);
      $('#cuerpoTabla').append(fila);
      $('#btn-salir-validado').hide();
      $('#btn-salir').show();
    }
  });
  $('#frmCargaProducidos').attr('data-tipoMoneda' ,tr_html.find('.tipo_moneda').attr('data-tipo'));
  $('#modalCargaProducidos').modal('show');
});

$('#btn-salir-validado').on('click', function(e){
  $('#modalCargaProducidos').modal('hide');
  $('#btn-buscar').trigger('click');
})
//si presiona el ojo de alguna de las máquinas listadas
$(document).on('click','.infoMaq',function(e){
  $('#tipoAjuste option').not('.default1').remove();
  $('#tipoAjuste').change();
  $('#cuerpoTabla .idMaqTabla').css('background-color','#FFFFFF');
  $(this).parent().css('background-color', '#FFCC80');
  $('#modalCargaProducidos .mensajeFin').hide();

  e.preventDefault();
  const id_maq = $(this).val();
  const id_prod = $('#modalCargaProducidos #id_producido').val();

  //ME TRAE TODOS LOS DATOS DE UNA MÁQUINA DETERMINADA, AL PŔESIONAR EL OJO
  $.get('producidos/datosAjusteMTM/' + id_maq + '/' + id_prod, function(data){
    $('#btn-finalizar').attr('data-id',id_maq);

    $('#columnaDetalle').show();
    $('#info-denominacion').html('CONTADORES EN CRÉDITOS, DENOMINACIÓN BASE "'+data.producidos_con_diferencia[0].denominacion+'" (Solo Rosario)');
    $('#coinoutIni').val(data.producidos_con_diferencia[0].coinout_inicio);
    $('#coininIni').val(data.producidos_con_diferencia[0].coinin_inicio);
    $('#jackIni').val(data.producidos_con_diferencia[0].jackpot_inicio);
    $('#progIni').val(data.producidos_con_diferencia[0].progresivo_inicio);
    $('#coininFin').val(data.producidos_con_diferencia[0].coinin_final);
    $('#coinoutFin').val(data.producidos_con_diferencia[0].coinout_final);
    $('#jackFin').val(data.producidos_con_diferencia[0].jackpot_final);
    $('#progFin').val(data.producidos_con_diferencia[0].progresivo_final);
    $('#prodCalc').val(data.producidos_con_diferencia[0].delta).prop('disabled', true);
    $('#prodSist').val(data.producidos_con_diferencia[0].producido);

    //Guardo los valores originales para researlos al cambio de TipoAjuste
    $('.cont_finales input,.cont_iniciales input').each(function(){$(this).data('original',$(this).val());})
    $('#prodSist').data('original',$('#prodSist').val());

    $('#diferencias').text(data.producidos_con_diferencia[0].diferencia).prop('disabled', true);
    for (let i = 0; i < data.tipos_ajuste.length; i++) {
      $('#tipoAjuste').append($('<option>').val(data.tipos_ajuste[i].id_tipo_ajuste).text(data.tipos_ajuste[i].descripcion));
    }
    //de momento no esta recuperando el valor del texto de observaciones por lo que se resetea manualmente
    $('#prodObservaciones').val(data.producidos_con_diferencia[0].observacion);
    //inputs ocultos en el form
    $('#data-denominacion').val(data.producidos_con_diferencia[0].denominacion);
    $('#data-detalle-final').val(data.producidos_con_diferencia[0].id_detalle_contador_final);
    $('#data-detalle-inicial').val(data.producidos_con_diferencia[0].id_detalle_contador_inicial);
    $('#data-producido').val(data.producidos_con_diferencia[0].id_detalle_producido);
  });
}); //PRESIONA UN OJITO

$("#btn-finalizar").click(function(e){
  e.preventDefault();
  guardarFilaDiferenciaCero();
  $('#modalCargaProducidos .mensajeSalida span').hide();
})

//SALIR DEL AJUSTE
$('#btn-salir').click(function(){
  const salida = $(this).data('salida');
  if(salida){
    $('#modalCargaProducidos').modal('hide');
    return;
  }
  $('#modalCargaProducidos .mensajeSalida span').show();
  $(this).data('salida',true);
});

/************   FUNCIONES   ***********/
function guardarFilaDiferenciaCero(){ //POST CON DATOS CARGADOS
  if($('#diferencias').text()!='0') return;

  $('#mensajeExito').hide();

  const formData = {
    denominacion: $('#data-denominacion').val(),
    coinin_inicio:     parseInt($('#coininIni').val()),
    coinin_final:       parseInt($('#coininFin').val()),
    coinout_inicio:    parseInt($('#coinoutIni').val()),
    coinout_final:      parseInt($('#coinoutFin').val()),
    jackpot_inicio:    parseInt($('#jackIni').val()),
    jackpot_final:      parseInt($('#jackFin').val()),
    progresivo_inicio: parseInt($('#progIni').val()),
    progresivo_final:   parseInt($('#progFin').val()),
    id_detalle_producido:        $('#data-producido').val(),
    id_detalle_contador_final:   $('#data-detalle-final').val() != undefined ?  $('#data-detalle-final').val() : null,
    id_detalle_contador_inicial: $('#data-detalle-inicial').val() != undefined ?  $('#data-detalle-inicial').val() : null,
    producido:         $('#prodSist').val(),
    id_tipo_ajuste:    $('#tipoAjuste').val(),
    observacion: $('#prodObservaciones').val(),
  };

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
      type: 'POST',
      url: 'producidos/guardarAjuste',
      data: formData,
      dataType: 'json',
      success: function (data) {
        if(data.todas_ajustadas){
          $('#columnaDetalle').hide();
          $('#btn-finalizar').hide();
          $('#modalCargaProducidos').modal('hide');
          $('#mensajeExito h3').text('EXITO');
          $('#mensajeExito p').text('Se han ajustado todas las diferencias correctamente.');
          $('#mensajeExito div').css('background-color','#4DB6AC');
          $('#mensajeExito').show();
          $('#btn-buscar').trigger('click');
          return;
        }
        if(data.hay_diferencia){
          $('#columnaDetalle').show();
          $('#btn-finalizar').show();
          $('#textoExito').text('Se encontraron diferencias al tratar de ajustar la maquina.').show();
          return;
        }

        $('#columnaDetalle').hide();
        $('#btn-finalizar').hide();
        $('#modalCargaProducidos .mensajeFin').show();
        $('#maquinas_con_diferencias').text(parseInt($('#maquinas_con_diferencias').text())-1);
        const fila = $('#cuerpoTabla #' + $("#btn-finalizar").attr('data-id'));
        $('#textoExito').text('Maquina '+fila.find('.nroAdm').text()+' ajustada').show();
        fila.remove();
      },
      error: function (data) {
        console.log('ERROR');
        console.log(data);
      },
  });
};

function limpiarCuerpoTabla(){ //LIMPIA LOS DATOS DEL FORM DE DETALLE
  $('#btn-finalizar').hide();
  $('#cuerpoTabla').empty();
  $('#coinoutIni').val("");
  $('#coininIni').val("");
  $('#jackIni').val("");
  $('#progIni').val("");
  $('#coininFin').val("");
  $('#coinoutFin').val("");
  $('#jackFin').val("");
  $('#progFin').val("");
  $('#prodCalc').val("");
  $('#prodSist').val("");
  $('#diferencias').val("");
  $('#data-detalle-final').val("");
  $('#data-detalle-inicial').val("");
  $('#tipoAjuste option').not('.default1').remove().val(0);
  $('#descripcion_validacion').text('');
}

//Planilla de diferencias producido vs contadores (con el ajuste)
$(document).on('click','.planilla',function(){
  window.open('producidos/generarPlanillaDiferencias/' + $(this).val(),'_blank');
});

$(document).on('click','.producido',function(){
  window.open('producidos/generarPlanillaProducido/' + $(this).val(),'_blank');
});

//función para generar el listado inicial
function agregarFilaTabla(producido){
  const fila = $('#filaEjemploProducidos').clone().removeAttr('id');
  fila.find('.casino').text(producido.casino);
  fila.find('.fecha').text(producido.fecha);
  fila.find('.moneda').text(producido.moneda);
  fila.find('button').val(producido.id_producido);
  //Tienen que estar el contador inicial importado (y cerrado), el contador final importado y el producido sin validar para permitir cargar
  //El contador final es el que se va a "cerrar" cuando se validen los ajustes
  if(producido.error_contador_ini != null || producido.error_contador_fin != null || producido.producido_validado != 0){
    fila.find('.carga').remove();
  }

  fila.find('.producido_valido').find(producido.producido_validado == 1?
    '.invalido' : '.valido').remove();
  fila.find('.contador_inicial_cerrado').find(producido.error_contador_ini == null?
    '.invalido' : '.valido').remove();
  fila.find('.relevamiento_valido').find(producido.error_contador_fin == null && producido.error_relevamientos == null? 
    '.invalido' : '.valido').remove();

  $('#tablaImportacionesProducidos tbody').append(fila);
}

$(document).on('click', '#tablaImportacionesProducidos thead tr th[value]', function(e) {
  $('#tablaImportacionesProducidos th').removeClass('activa');
  if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado', 'desc');
  } else {
    if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado', 'asc');
    } else {
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
    }
  }
  $('#tablaImportacionesProducidos th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  clickIndice(e, $('#herramientasPaginacion').getCurrentPage(), $('#herramientasPaginacion').getPageSize());
});

function clickIndice(e, pageNumber, tam) {
  if (e != null) {
    e.preventDefault();
  }
  tam = (isNaN(tam)) ? $('#herramientasPaginacion').getPageSize() : tam;
  const columna = $('#tablaImportacionesProducidos .activa').attr('value');
  const orden = $('#tablaImportacionesProducidos .activa').attr('estado');
  $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

$('#btn-buscar').click(function(e, pagina, page_size, columna, orden) {
  e.preventDefault();
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  let size = 10;
  //Fix error cuando librería saca los selectores
  if (!isNaN($('#herramientasPaginacion').getPageSize())) {
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size   = (page_size == null || isNaN(page_size)) ? size : page_size;
  page_number = (pagina != null) ? pagina  : $('#herramientasPaginacion').getCurrentPage();
  columna     = (columna != null)? columna : $('#tablaResultados .activa').attr('value');
  orden       = (orden  != null) ? orden   : $('#tablaResultados .activa').attr('estado');
  const formData = {
    id_casino : $('#selectCasino').val(),
    fecha_inicio : $('#fecha_inicio').val(),
    fecha_fin : $('#fecha_fin').val(),
    id_tipo_moneda : $('#selectMoneda').val(),
    validado : $('#selectValidado').val(),
    page: page_number,
    sort_by: {columna: columna, orden: orden},
    page_size: page_size,
  };

  $.ajax({
    type: 'POST',
    url: 'producidos/buscarProducidos',
    data: formData,
    dataType: 'json',
    success: function(resultados) {
        $('#herramientasPaginacion').generarTitulo(page_number, page_size, resultados.total,clickIndice);
        $('#tablaImportacionesProducidos tbody').empty();
        for (let i = 0; i < resultados.data.length; i++) {
          agregarFilaTabla(resultados.data[i]);
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function(data) { console.log('Error:', data); }
  });
});