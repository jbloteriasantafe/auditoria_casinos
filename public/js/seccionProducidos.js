$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Producidos');
  $('#opcProducidos').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcProducidos').addClass('opcionesSeleccionado');

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
  $('#columnaDetalle').hide();
});

$(function () {
    $('#dtpFechaInicio').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd / mm / yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2
    });

    $('#dtpFechaFin').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd / mm / yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2
    });

    $('#selectCasinos').val("0");
    $('#fecha_inicio').val(" ");
    $('#fecha_fin').val(" ");
    $('#validado').val("-");

});

var guardado = true;

$('#btn-buscar').on('click' , function () {
  var busqueda = {
    id_casino : $('#selectCasinos').val(),
    fecha_inicio : $('#fecha_inicio').val(),
    fecha_fin : $('#fecha_fin').val() ,
    validado : $('#selectValidado').val()
  }

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
      type: 'GET',
      url: 'producidos/buscarProducidos',
      data: busqueda,
      dataType: 'json',
      success: function (data) {
        $('#tablaImportacionesProducidos tbody').empty();
        for (var i = 0; i < data.producidos.length; i++) {
          agregarFilaTabla(data.producidos[i]);
        }
      },
      error: function (data) {
        console.log('ERROR');
        console.log(data);

      },
  });

})

//SI INGRESA ALGO EN ALGUN INPUT, se recalcula la diferencia
$(document).on('input', '#frmCargaProducidos input' , function(e){
  /*calculo si se opera en el input*/
  var input=$(this).val();
  guardado = false;
  salida=0;
  $('#modalCargaProducidos .mensajeSalida span').hide();
  $('#btn-guardar').show(); //guardado temporal, saca las diferencias 0
  //actualizo la diferencia
  //calcularDiferencia($(this));
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

  $('#prodCalc').text(producido_calculado);
  $('#diferencias').text(diferencia);
  if(diferencia == 0){
    $('#btn-guardar').hide();
    $('#btn-finalizar').show();
  }


})

$(document).on('change','#frmCargaProducidos observacionesAjuste',function(){
  $(this).removeClass('alerta');
})

//sale del campo y deja vacio cambia por 0
$(document).on('focusout' ,'#frmCargaProducidos input' , function(e){

  // $("#frmCargaProducidos").find(':input').each(function() {
    var input=$(this);
    if($(this).val() == ''){
      $(this).val(0)
    }
  // });

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
  
  $('#modalCargaProducidos #id_producido').val(id_producidos);
  //ME TRAE LAS MÁQUINAS RELACIONADAS CON ESE PRODUCIDO, PRIMER TABLA DEL MODAL
  $.get('producidos/maquinasProducidos/' + id_producidos, function(data){

    //pruebas
    console.log("esto esta por evaluar");
    console.log("esto esta por evaluar",data);


    // fin pruebas
    if(data.validado.estaValidado == 0){
      for (var i = 0; i < data.producidos_con_diferencia.length; i++) {
        var fila = generarFilaMaquina(data.producidos_con_diferencia[i].nro_admin,data.producidos_con_diferencia[i].id_maquina)//agregar otros datos para guardar en inputs ocultos

          $('#cuerpoTabla').append(fila);

        }
    }else {
      $('#cuerpoTabla')
          .append($('<div>')
              .addClass('row')
              .append($('<div>')
                .addClass('col-xs-6')
                .append($('<h3>')
                  .text('El producido ahora está validado. No se encontraron diferencias')
                )
              )

      )
      $('#textoExito').hide();
      cerrarContadoresYValidar($('#id_producido').val() ,data.validado.producido_fin);//como no hubo diferencias producido validado y contadores finales cerrados
    }

  });
  $('#frmCargaProducidos').attr('data-tipoMoneda' ,tr_html.find('.tipo_moneda').attr('data-tipo'));
  $('#modalCargaProducidos').modal('show');
  $('#').modal('hide');

});

//si presiona el ojo de alguna de las máquinas listadas
$(document).on('click','.idMaqTabla',function(e){

  $('#observacionesAjuste option').not('.default1').remove();
  $('#cuerpoTabla tr').css('background-color','#FFFFFF');
  $(this).parent().css('background-color', '#FFCC80');
  $('#modalCargaProducidos .mensajeFin').hide();

  // .css('style', 'background: #ccc')#FFCC80;
  e.preventDefault();
  var id_maq=$(this).val();
  var id_prod= $('#modalCargaProducidos #id_producido').val();


  //ME TRAE TODOS LOS DATOS DE UNA MÁQUINA DETERMINADA, AL PŔESIONAR EL OJO
    $.get('producidos/ajustarProducido/' + id_maq + '/' + id_prod, function(data){

                    $('#btn-guardar').attr('data-id-maq', id_maq);
                    $('#btn-finalizar').attr('data-id',id_maq);

                    $('#columnaDetalle').show();
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
                    //inputs ocultos en el form
                    $('#data-denominacion').val(data.producidos_con_diferencia[0].denominacion);
                    $('#data-detalle-final').val(data.producidos_con_diferencia[0].id_detalle_contador_final);
                    $('#data-detalle-inicial').val(data.producidos_con_diferencia[0].id_detalle_contador_inicial);
                    $('#data-contador-final').val(data.id_contador_final);
                    $('#data-contador-inicial').val(data.id_contador_inicial);
                    $('#data-producido').val(data.producidos_con_diferencia[0].id_detalle_producido);

      })
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

  var fila=$('#filaClon').clone();

  fila.removeAttr('id');
  fila.attr('id',  id_maquina_final);

  fila.find('.nroAdm').text(nro_admin);
  fila.find('.idMaqTabla').val(id_maquina_final);

  fila.css('display', 'block');

      return  fila;
}

function guardarFilaDiferenciaCero(estado, id){ //POST CON DATOS CARGADOS

  $('#mensajeExito').hide();
  //estado -> generado, carga parcial, finalizado
  var detalles_sin_diferencia = [];
  var errores = 0 ;
   // $('#tablaMaquinas tbody tr').each(function(index){
   //if($('#modalCargaProducidos #diferencias').text()== 0){


       var id_detalle_contador_final = $('#data-detalle-final').val() != undefined ?  $('#data-detalle-final').val() : null;
       var id_detalle_contador_inicial = $('#data-detalle-inicial').val() != undefined ?  $('#data-detalle-inicial').val() : null;

       // if($('#observacionesAjuste').val() = 0){

          var producido={
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
          }

      // }else{
      //   $('#observacionesAjuste').addClass('alerta');
      //   errores++;
      // }
      detalles_sin_diferencia.push(producido);

      //}
  // })

  //si apreta guardar con todos arreglados
   if(($('#diferencias').text()=='0') && ($('#observacionesAjuste').val() != 0)){
     estado = 3 ;
  }

  if(errores==0){
    formData= {
      producidos_ajustados : detalles_sin_diferencia,
      estado : estado ,
      id_contador_final :  $('#data-contador-final').val(),
      id_contador_inicial: $('#data-contador-inicial').val(),
      id_tipo_moneda : $('#frmCargaProducidos').attr('data-tipoMoneda'),
      id_producido: $('#id_producido').val()
    }

    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
    $.ajax({
        type: 'POST',
        url: 'producidos/guardarAjusteProducidos',
        data: formData,
        dataType: 'json',
        success: function (data) {

          switch (data.estado) {
              case 1: //Ha finalizado el ajuste de UNA máquina
              $('#columnaDetalle').hide();

              $('#cuerpoTabla').find(id).remove();
              $('#btn-finalizar').hide();
              $('#modalCargaProducidos .mensajeFin').show();

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
                $('#mensajeExito').modal('show');

                $('#tablaImportacionesProducidos #' + $('#id_producido').val()).find('td').eq(3).children()
                    .replaceWith('<i class="fa fa-fw fa-check" style="color:#66BB6A;">');
            break;
            default:

          }

          guardado = true;
          $('#btn-guardar').hide();

        },
        error: function (data) {
          console.log('ERROR');
          console.log(data);

        },
    });
  }else {
    //mostrar errores
    }

};

function limpiarCuerpoTabla(){ //LIMPIA LOS DATOS DEL FORM DE DETALLE
  $('#modalCargaProducidos').find('#data-contador-final').val("");
  $('#modalCargaProducidos').find('#data-contador-inicial').val("");
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

}

function cerrarContadoresYValidar(id_producido , id_producido_final){

  var tds_inicio = $('#tablaImportacionesProducidos #' + id_producido).find('td');
  var tds_fin= $('#tablaImportacionesProducidos #' + id_producido_final).find('td');

  tds_inicio.eq(3).children().replaceWith('<i class="fa fa-fw fa-check" style="color:#66BB6A;">');
  tds_inicio.eq(6).find('.carga').remove();
  if(id_producido_final != 0 ){
    tds_fin.eq(4).children()
    .replaceWith('<i class="fa fa-fw fa-check" style="color:#66BB6A;">');
      checkEstado(id_producido_final);
  }

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

    // $("#cargaArchivo").fileinput('destroy').fileinput({
    //   language: 'es',
    //   showRemove: false,
    //   showUpload: false,
    //   showCaption: false,
    //   showZoom: false,
    //   browseClass: "btn btn-primary",
    //   previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
    //   overwriteInitial: true,
    //   initialPreviewAsData: true,
    //   initialPreview: [
    //     "/producidos/generarPlanilla/" + $(this).val(),
    //   ],
    //   initialPreviewConfig: [
    //     {type:'pdf', caption: '', size: 1, width: "1000px", url: "{$url}", key: 1},
    //   ],
    //   allowedFileExtensions: ['pdf'],
    // });
    //
    // $('#modalPlanilla').modal('show');
});

//función para generar el listado inicial
function agregarFilaTabla(producido, casino){
  var color_rojo = '#EF5350';
  var color_verde = '#66BB6A';
  var icono_validado, icono_cont_ini , icono_cont_fin;
  var color_validado, color_cont_ini, color_cont_fin;
  //si ya esta validado
  if(producido.producido.validado == 1) {
     icono_validado = 'fa fa-fw fa-check';
     color_validado = color_verde;
  }else {
      icono_validado= 'fas fa-fw fa-times';
      color_validado = color_rojo;
  }
  if(producido.cerrado.length == 0) {
    icono_cont_ini= 'fa fa-fw fa-check';
    color_cont_ini = color_verde;
  }else {
    icono_cont_ini= 'fas fa-fw fa-times';
    color_cont_ini = color_rojo;
  }

  if(producido.validado.length == 0){
    icono_cont_fin= 'fa fa-fw fa-check';
    color_cont_fin = color_verde;
  }else {
    icono_cont_fin= 'fas fa-fw fa-times';
    color_cont_fin = color_rojo;
  }
   if(producido.cerrado.length == 0 && producido.validado.length == 0 && producido.producido.validado == 0){
    var tr = $('<tr>').append($('<td>').addClass('col-xs-2').text(producido.casino.nombre))
                      .append($('<td>').addClass('col-xs-2').text(producido.producido.fecha))
                      .append($('<td>').addClass('col-xs-2 tipo_moneda').text(producido.tipo_moneda.descripcion))
                      .append($('<td>').addClass('col-xs-1').append($('<i>').addClass(icono_validado).css('color', color_validado)))
                      .append($('<td>').addClass('col-xs-1').append($('<i>').addClass(icono_cont_ini).css('color', color_cont_ini)))
                      .append($('<td>').addClass('col-xs-1').append($('<i>').addClass(icono_cont_fin).css('color', color_cont_fin)))
                      .append($('<td>').addClass('col-xs-1').append($('<button>')
                          .append($('<i>')
                              .addClass('fa').addClass('fa-fw').addClass('fa fa-fw fa-upload')
                          )
                          .addClass('btn').addClass('btn-warning').addClass('carga').addClass('popInfo')
                          .attr('value',producido.producido.id_producido)
                      ))
                      .append($('<td>').addClass('col-xs-1').append($('<button>')
                          .append($('<i>')
                              .addClass('fa').addClass('fa-fw').addClass('fa fa-fw fa-print')
                          )
                          .addClass('btn').addClass('btn-info').addClass('planilla')
                          .attr('value',producido.producido.id_producido)
                      ));
  }

  else{
  var tr = $('<tr>').append($('<td>').addClass('col-xs-2').text(producido.casino.nombre))
                    .append($('<td>').addClass('col-xs-2').text(producido.producido.fecha))
                    .append($('<td>').addClass('col-xs-2 tipo_moneda').text(producido.tipo_moneda.descripcion))
                    .append($('<td>').addClass('col-xs-1').append($('<i>').addClass(icono_validado).css('color', color_validado)))
                    .append($('<td>').addClass('col-xs-1').append($('<i>').addClass(icono_cont_ini).css('color', color_cont_ini)))
                    .append($('<td>').addClass('col-xs-1').append($('<i>').addClass(icono_cont_fin).css('color', color_cont_fin)))
                    .append($('<td>').addClass('col-xs-2').append($('<button>')
                        .append($('<i>')
                            .addClass('fa').addClass('fa-fw').addClass('fa fa-fw fa-print')
                        )
                        .addClass('btn').addClass('btn-info').addClass('planilla')
                        .attr('value',producido.producido.id_producido)
                    ));
} //GENERA TABLA DE LISTADO PRINCIPAL
 $('#tablaImportacionesProducidos tbody').append(tr);
}
