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

var guardado = true;

//SI INGRESA ALGO EN ALGUN INPUT, se recalcula la diferencia
$(document).on('input', '#frmCargaProducidos input' , function(e){
  /*calculo si se opera en el input*/
  var input=$(this).val();
  guardado = false;
  salida=0;
  $('#modalCargaProducidos .mensajeSalida span').hide();
  $('#btn-guardar').show(); //guardado temporal, saca las diferencias 0
  //actualizo la diferencia
  
  var denominacion = parseFloat($('#data-denominacion').val());
  var coinin_inicial = parseInt($('#coininIni').val()) * denominacion;
  var coinout_inicial = parseInt($('#coinoutIni').val()) * denominacion;
  var jackpot_inicial = parseInt($('#jackIni').val()) * denominacion;
  var progresivo_inicial =parseInt($('#progIni').val()) * denominacion;
  var coinin_final = parseInt($('#coininFin').val()) * denominacion;
  var coinout_final = parseInt($('#coinoutFin').val()) * denominacion;
  var jackpot_final = parseInt($('#jackFin').val()) * denominacion;
  var progresivo_final = parseInt($('#progFin').val()) * denominacion;
  var producido_sistema = parseFloat($('#prodSist').val());


  var producido_calculado=Math.round(((coinin_final - coinout_final - jackpot_final - progresivo_final) - (coinin_inicial - coinout_inicial - jackpot_inicial - progresivo_inicial)) * 100) / 100;
  var diferencia =Math.round( (producido_calculado - producido_sistema) * 100) / 100;

  $('#prodCalc').val(producido_calculado);
  $('#diferencias').text(diferencia);
  if(diferencia == 0){
    $('#btn-guardar').hide();
    $('#btn-finalizar').show();
  }
})

$(document).on('input', '#frmCargaProducidos textarea' , function(e){
  $('#btn-guardar').show()
})

$(document).on('change','#frmCargaProducidos observacionesAjuste',function(){
  $(this).removeClass('alerta');
})

//sale del campo y deja vacio cambia por 0
$(document).on('focusout' ,'#frmCargaProducidos input' , function(e){
  var input=$(this);
  if($(this).val() == ''){
    $(this).val(0)
  }

  var valor_input=$(this).val();
    //opero lo que haya escrito en el campo
    if(valor_input != ''){
      var arreglo = valor_input.split(/([-+*/])/);
      if(arreglo[0] != '' && arreglo[1] != '' && arreglo[2] != ''){
          switch (arreglo[1]) {
            case "+":
                var val= parseInt(arreglo[0])+parseInt(arreglo[2]);
              break;
            case "-":
              var val= parseInt(arreglo[0])-parseInt(arreglo[2]);
              break;
            case "*":
                var val= parseInt(arreglo[0]) * parseInt(arreglo[2]);
              break;
            case "/":
                var val= parseInt(arreglo[0])/parseInt(arreglo[2]);
              break;
            default: val = valor_input; break;
          }
          input.val(val);
      }
    }
    //calculoAritmetico(input , $(this));
    $(this).trigger('input');
});

//mostrar popover
$(document).on('mouseenter','.popInfo',function(e){
    $(this).popover('show');
});

//AJUSTAR PRODUCIDO, boton de la lista
$(document).on('click','.carga',function(e){
  e.preventDefault();
  $('#columnaDetalle').hide();
  $('#mensajeExito').modal('hide');

  limpiarCuerpoTabla();
  //ocultar mensaje de salida
  salida = 0;
  $('#modalCargaProducidos .mensajeSalida span').hide();
  var tr_html =$(this).parent().parent();

  var id_producidos =$(this).val();
  const moneda = tr_html.find('.tipo_moneda').text();
  const fecha_prod = tr_html.find('.fecha_producido').text();
  const casino = tr_html.find('.casino').text();
  $('#descripcion_validacion').text(casino+' - '+fecha_prod+' - $'+moneda);
  $('#maquinas_con_diferencias').text('---');

  $('#modalCargaProducidos #id_producido').val(id_producidos);
  //ME TRAE LAS MÁQUINAS RELACIONADAS CON ESE PRODUCIDO, PRIMER TABLA DEL MODAL
  $.get('producidos/ajustarProducido/' + id_producidos, function(data){
    if(data.validado.estaValidado == 0){
      $('#descripcion_validacion').text(casino+' - '+data.fecha_produccion+' - $'+data.moneda.descripcion);
      $('#maquinas_con_diferencias').text(data.producidos_con_diferencia.length);
      for (var i = 0; i < data.producidos_con_diferencia.length; i++) {
        var fila = generarFilaMaquina(data.producidos_con_diferencia[i].nro_admin,data.producidos_con_diferencia[i].id_maquina)//agregar otros datos para guardar en inputs ocultos
        $('#cuerpoTabla').append(fila);
        $('#btn-salir-validado').hide();
        $('#btn-salir').show();
      }
    }
    else {
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
    }
  });
  $('#frmCargaProducidos').attr('data-tipoMoneda' ,tr_html.find('.tipo_moneda').attr('data-tipo'));
  $('#modalCargaProducidos').modal('show');
  $('#').modal('hide');
});

$('#btn-salir-validado').on('click', function(e){
  $('#modalCargaProducidos').modal('hide');
  $('#btn-buscar').trigger('click');
})
//si presiona el ojo de alguna de las máquinas listadas
$(document).on('click','.idMaqTabla',function(e){
  $('#observacionesAjuste option').not('.default1').remove();
  $('#cuerpoTabla tr').css('background-color','#FFFFFF');
  $(this).parent().css('background-color', '#FFCC80');
  $('#modalCargaProducidos .mensajeFin').hide();

  e.preventDefault();
  var id_maq=$(this).val();
  var id_prod= $('#modalCargaProducidos #id_producido').val();

  //ME TRAE TODOS LOS DATOS DE UNA MÁQUINA DETERMINADA, AL PŔESIONAR EL OJO
  $.get('producidos/datosAjusteMTM/' + id_maq + '/' + id_prod, function(data){
    $('#btn-guardar').attr('data-id-maq', id_maq);
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
    $('#prodSist').val(data.producidos_con_diferencia[0].producido_dinero);
    $('#diferencias').text(data.producidos_con_diferencia[0].diferencia).prop('disabled', true);
    for (var i = 0; i < data.tipos_ajuste.length; i++) {
        $('#observacionesAjuste').append($('<option>').val(data.tipos_ajuste[i].id_tipo_ajuste).text(data.tipos_ajuste[i].descripcion));
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

//boton guarda temporal
$("#btn-guardar").click(function(e){
  e.preventDefault();
  var id_maquina=$(this).attr('data-id-maq');
  //Se envía el relevamiento para guardar con estado 2 = 'Carga parcial'
  guardarFilaDiferenciaCero(2,id_maquina);
  $('#modalCargaProducidos .mensajeSalida span').hide();
});

$("#btn-finalizar").click(function(e){
  e.preventDefault();
  var id_maquina=$(this).attr('data-id');
  //Se evnía el relevamiento para guardar con estado 2 = 'Carga parcial'
  guardarFilaDiferenciaCero(3,id_maquina);
  $('#modalCargaProducidos .mensajeSalida span').hide();
})

$('.btn-ajustar').click(function(e){
  e.preventDefault();
  var id_producido = $(this).attr('data-producido');
  $('.carga').each(function(index){
    if(id_producido == $(this).val()){
          $(this).trigger('click');
    }
  })
})

//SALIR DEL AJUSTE
var salida; //cantidad de veces que se apreta salir
$('#btn-salir').click(function(){
  if (guardado) $('#modalCargaProducidos').modal('hide');
  else{
    if (salida == 0) {
      $('#modalCargaProducidos .mensajeSalida span').show();
      salida = 1;
    }else {
      $('#modalCargaProducidos').modal('hide');
      guardado=1;
    }
  }
});

/************   FUNCIONES   ***********/
function generarFilaMaquina(nro_admin, id_maquina_final){//CARGA LA TABLA DE MÁQUINAS SOLAMENTE, DENTRO DEL MODAL
  const fila = $('#filaClon').clone().removeAttr('id');
  fila.attr('id',  id_maquina_final);
  fila.find('.nroAdm').text(nro_admin);
  fila.find('.idMaqTabla').val(id_maquina_final);
  fila.css('display', 'block');
  return fila;
}

function guardarFilaDiferenciaCero(estado, id){ //POST CON DATOS CARGADOS

  $('#mensajeExito').hide();
  //estado -> generado, carga parcial, finalizado
  var detalles_sin_diferencia = [];
  var errores = 0 ;

  var id_detalle_contador_final = $('#data-detalle-final').val() != undefined ?  $('#data-detalle-final').val() : null;
  var id_detalle_contador_inicial = $('#data-detalle-inicial').val() != undefined ?  $('#data-detalle-inicial').val() : null;

  var producido = {
    id_maquina : id,
    id_detalle_producido :  $('#data-producido').val(),
    id_detalle_contador_final : id_detalle_contador_final,
    id_detalle_contador_inicial : id_detalle_contador_inicial,
    coinin_inicial : parseInt($('#coininIni').val()),
    coinout_inicial : parseInt($('#coinoutIni').val()),
    jackpot_inicial : $('#jackIni').val(),
    progresivo_inicial : $('#progIni').val(),
    coinin_final : parseInt($('#coininFin').val()),
    coinout_final :parseInt($('#coinoutFin').val()),
    jackpot_final : $('#jackFin').val(),
    progresivo_final :$('#progFin').val(),
    producido: $('#prodSist ').val(),
    denominacion: $('#data-denominacion').val(),
    id_tipo_ajuste: $('#observacionesAjuste').val(),
    prodObservaciones: $('#prodObservaciones').val(),
  };

  detalles_sin_diferencia.push(producido);

  //si apreta guardar con todos arreglados
   if(($('#diferencias').text()=='0') && ($('#observacionesAjuste').val() != 0)){
     estado = 3 ;
  }

  if(errores == 0){
    formData = {
      producidos_ajustados : detalles_sin_diferencia,
      estado : estado ,
      id_tipo_moneda : $('#frmCargaProducidos').attr('data-tipoMoneda'),
      id_producido: $('#id_producido').val()
    };

    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
    $.ajax({
        type: 'POST',
        url: 'producidos/guardarAjuste',
        data: formData,
        dataType: 'json',
        success: function (data) {
          switch (data.estado) {
            case 1: //Ha finalizado el ajuste de UNA máquina
              $('#columnaDetalle').hide();
              $('#cuerpoTabla').find(id).remove();
              $('#btn-finalizar').hide();
              $('#modalCargaProducidos .mensajeFin').show();
              $('#maquinas_con_diferencias').text(parseInt($('#maquinas_con_diferencias').text())-1);
            case 2: //GUARDADO TEMPORAL
              for (var i = 0; i < data.resueltas.length; i++) {
                $('#cuerpoTabla #' + data.resueltas[i]).remove();
              }
              $('#columnaDetalle').hide();
              $('#textoExito').text('Se arreglaron ' + data.resueltas.length + ' máquinas. Y ocurrieron ' + data.errores.length + ' errores.');
            break;
            case 3: //SE HAN FINALIZADO LOS AJUSTES DE TODAS LAS MÁQUINAS
              $('#columnaDetalle').hide();
              $('#btn-finalizar').hide();
              $('#btn-guardar').hide();
              $('#modalCargaProducidos').modal('hide');

              $('#mensajeExito h3').text('EXITO');
              $('#mensajeExito p').text('Se han ajustado todas las diferencias correctamente.');
              $('#mensajeExito div').css('background-color','#4DB6AC');
              $('#mensajeExito').show();
              $('#btn-buscar').trigger('click');

              $('#tablaImportacionesProducidos #' + $('#id_producido').val()).find('td').eq(3).children()
                    .replaceWith('<i class="fa fa-fw fa-check" style="color:#66BB6A;">');
            break;
            default:
            break;
          }

          guardado = true;
          $('#btn-guardar').hide();
        },
        error: function (data) {
          console.log('ERROR');
          console.log(data);
        },
    });
  }
};

function limpiarCuerpoTabla(){ //LIMPIA LOS DATOS DEL FORM DE DETALLE
  $('#btn-guardar').hide();
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
  $('#denominacion').val("");
  $('#data-detalle-final').val("");
  $('#data-detalle-inicial').val("");
  $('#observacionesAjuste option').not('.default1').remove();
  $('#observacionesAjuste').val(0);
  $('#descripcion_validacion').text('');
}

function checkEstado(id_producido){
  $.get('producidos/checkEstado/' + id_producido, function(data){
    if(data.estado == 1){
      var boton = '<button class="btn btn-warning carga popInfo" type="button" value="' + id_producido + '" data-trigger="hover" data-toggle="popover" data-placement="top" data-content="Ajustar"><i class="fa fa-fw fa-upload"></i></button>'
      $('#tablaImportacionesProducidos #' + id_producido).find('td').eq(6).prepend(boton);
      $('.btn-ajustar').each(function (index){
        if($(this).val() == data.id_casino){
          $(this).attr('data-producido' , id_producido);
        }
      })
    }else {
      $('.btn-ajustar').each(function (index){
        if($(this).val() == data.id_casino){
          $(this).remove();
        }
      })
    }
  });
}

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();
    window.open('producidos/generarPlanilla/' + $(this).val(),'_blank');
});

//función para generar el listado inicial
function agregarFilaTabla(producido){
  const fila = $('#filaEjemploProducidos').clone().removeAttr('id');
  fila.find('.casino').text(producido.casino);
  fila.find('.fecha').text(producido.fecha);
  fila.find('.moneda').text(producido.moneda);
  fila.find('button').val(producido.id_producido);
  if(producido.error_contador_ini != null || producido.producido_validado != 0){
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