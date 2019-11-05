//Cuando se sube el archivo se identifican los datos posibles
var id_casino;
var id_tipo_moneda;
var fecha_date;

//Tamaños de los diferentes archivos CSV
var COL_PROD_ROS = 2;
var COL_PROD_SFE = 32;
var COL_BEN_ROS = 8;

//Opacidad del modal al minimizar
$('#btn-minimizarProducidos').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarBeneficios').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Importaciones');
  $('#opcImportaciones').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcImportaciones').addClass('opcionesSeleccionado');

  //Habilitar o no la fecha según el casino
  // habilitarFechayMoneda();
  $('#mensajeInformacion').hide();

  //Fecha para el casino de Rosario
  $('#modalImportacionContadores #fecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    // pickerPosition: "bottom-left",
    pickerPosition: "top-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#fecha_busqueda').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
    ignoreReadonly: true,
  });

  if($('#casino_busqueda option').length == 2 ){
    $('#casino_busqueda option:eq(1)').prop('selected', true);
  }

  setearValueFecha();
  //Paginar
    $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);
  // $('#btn-buscarImportaciones').trigger('click');

  // var id_casino = $('#casinoInfoImportacion').val();
  id_casino = 1;
  id_tipo_moneda = 1;

  $('#casinoInfoImportacion').val(id_casino);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  $('#casinoInfoImportacion').change();
  // if (id_casino != 3) {
  //   cargarTablasImportaciones(id_casino, '1'); $('#monedaInfoImportacion').hide();
  // }
  // else  $('#monedaInfoImportacion').change();

});


$('#casinoInfoImportacion').change(function() {
    var id_casino = $(this).val();
    var id_moneda = $('#monedaInfoImportacion').val();

    //Si el casino elegido no es Rosario, entonces ocultar el select de monedas
      //Para Santa Fe y Melincué mandar moneda PESOS por defecto
      //Para Rosario mirar que moneda está seleccionada
    if (id_casino != '3') {
        $('#monedaInfoImportacion').hide();
        cargarTablasImportaciones(id_casino, '1'); //El 1 es PESOS
    }else {
        $('#monedaInfoImportacion').show();
        console.log("Casino: ", id_casino);
        console.log("Moneda: ", id_moneda);
        $('#monedaInfoImportacion').change();
        // cargarTablasImportaciones(id_casino, id_moneda);
    }
});

$('#monedaInfoImportacion').change(function() {
    var id_moneda = $(this).val();

    if (id_moneda == 1) $('.tablaBody').removeClass('dolares').addClass('pesos');
    else $('.tablaBody').removeClass('pesos').addClass('dolares');

    //Esto pasa siempre en Rosario, el único casino que tiene dolar
    cargarTablasImportaciones('3', id_moneda);
});

function limpiarBodysImportaciones() {
    $('.tablaBody tr').not('#moldeFilaImportacion').remove();
    $('.tablaBody').hide();
}

function cargarTablasImportaciones(casino, moneda) {


    $.get('importaciones/' + casino, function(data) {
        var tablaBody;

        console.log("Casino: ", casino);

        limpiarBodysImportaciones();

        switch (casino) {
          case '1':
            tablaBody = $('#bodyMelincue');
            break;
          case '2':
            tablaBody = $('#bodySantaFe');
            break;
          case '3':
            tablaBody = $('#bodyRosario');
            break;
        }

        for (var i = 0; i < data.arreglo.length; i++) {

          var moldeFilaImportacion = $('#moldeFilaImportacion').clone();
          moldeFilaImportacion.removeAttr('id');
          moldeFilaImportacion.find('.fecha').text(convertirDate(data.arreglo[i].fecha));

          var filaContador = moldeFilaImportacion.find('.contador');
          var filaProducido = moldeFilaImportacion.find('.producido');
          var filaBeneficio = moldeFilaImportacion.find('.beneficio');

          if (moneda == '1') {
            console.log('PESOS');
            data.arreglo[i].contador.pesos == true ? filaContador.addClass('true') : filaContador.addClass('false');
            data.arreglo[i].producido.pesos == true ? filaProducido.addClass('true') : filaProducido.addClass('false');
            data.arreglo[i].beneficio.pesos == true ? filaBeneficio.addClass('true') : filaBeneficio.addClass('false');
          }
          else {
            console.log('DOLAR');
            data.arreglo[i].contador.dolares == true ? filaContador.addClass('true') : filaContador.addClass('false');
            data.arreglo[i].producido.dolares == true ? filaProducido.addClass('true') : filaProducido.addClass('false');
            data.arreglo[i].beneficio.dolares == true ? filaBeneficio.addClass('true') : filaBeneficio.addClass('false');
          }

          tablaBody.append(moldeFilaImportacion);
          moldeFilaImportacion.show();
        }

        tablaBody.show();
    });

    $('#moldeFilaImportacion').hide();
}


function setearValueFecha() {
  var tipo_archivo = $('#tipo_archivo').val();

  switch (tipo_archivo) {
    case '1':
      $('#tablaImportaciones #tipo_fecha').attr('value',"contador_horario.fecha");
      break;
    case '2':
      $('#tablaImportaciones #tipo_fecha').attr('value',"producido.fecha");
      break
    case '3':
      $('#tablaImportaciones #tipo_fecha').attr('value',"beneficio.fecha");
      break;
  }
}

function obtenerFechaString(dateFecha, conDia) {
    var arrayFecha = dateFecha.split('/');
    console.log(arrayFecha);
    var meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

    if (conDia) {
      return arrayFecha[0] + ' ' +  meses[arrayFecha[1] - 1] + ' ' + arrayFecha[2];
    }
    else return meses[arrayFecha[1] - 1] + ' ' + arrayFecha[2];
}

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$(document).on('click','.planilla', function(){
  var tipo_importacion = $('#tablaImportaciones').attr('data-tipo');

  //Mostrar el título correspondiente
  switch (tipo_importacion) {
    case '1':
      $('#modalPlanilla h3.modal-title').text('VISTA PREVIA CONTADOR');
      break;
    case '2':
      $('#modalPlanilla h3.modal-title').text('VISTA PREVIA PRODUCIDO');
      break;
    case '3':
      $('#modalPlanilla h3.modal-title').text('VISTA PREVIA BENEFICIO');
      break;
  }

  var head = $('#tablaVistaPrevia thead tr');

  //Limpiar el modal
  $('#modalPlanilla #fecha').val('');
  $('#modalPlanilla #casino').val('');
  $('#modalPlanilla #tipo_moneda').val('');
  head.children().remove();
  $('#tablaVistaPrevia tbody tr').remove();

  //Comprobar el tipo de importacion. BENEFICIO tiene una ruta diferente a CONTADOR y PRODUCIDO
  if (tipo_importacion == 3) {
      //el request contiene mes anio id_tipo_moneda id_casino
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      var formData = {
          mes: $(this).attr('data-mes'),
          anio: $(this).attr('data-anio'),
          id_tipo_moneda: $(this).attr('data-moneda'),
          id_casino: $(this).attr('data-casino'),
      }

      $.ajax({
          type: 'POST',
          url: 'importaciones/previewBeneficios',
          data: formData,
          dataType: 'json',
          success: function (data) {
            console.log(data);

            $('#modalPlanilla #fecha').val(convertirDate(data.beneficios[0].fecha).substring(3,11));
            $('#modalPlanilla #casino').val(data.casino.nombre);
            $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);

            // head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('MTM')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('FECHA')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('COININ')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('COINOUT')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('VALOR')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('% DEVOLUCION')));
            head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('PROMEDIO')));

            for (var i = 0; i < data.beneficios.length; i++) {
                agregarFilaDetalleBeneficio(data.beneficios[i]);
            }
          },
          error: function (data) {
            console.log(data);
          }
      });
  }else {
      var id_importacion = $(this).val();

      $.get('importaciones/obtenerVistaPrevia/' + tipo_importacion + '/' + id_importacion, function(data){
          //Armar la tabla según el tipo de importacion
          switch (tipo_importacion) {
            case '1':
              $('#modalPlanilla #fecha').val(convertirDate(data.contador.fecha));
              $('#modalPlanilla #casino').val(data.casino.nombre);
              $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);

              head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('MTM')));
              head.append($('<th>').addClass('col-xs-3').append($('<h5>').text('COININ')));
              head.append($('<th>').addClass('col-xs-3').append($('<h5>').text('COINOUT')));
              head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('JACKPOT')));
              head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('PROGRESIVO')));

              for (var i = 0; i < data.detalles_contador.length; i++) {
                  agregarFilaDetalleContador(data.detalles_contador[i]);
              }
              break;
            case '2':
              $('#modalPlanilla #fecha').val(convertirDate(data.producido.fecha));
              $('#modalPlanilla #casino').val(data.casino.nombre);
              $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);

              head.append($('<th>').addClass('col-xs-5').append($('<h5>').text('MTM')));
              head.append($('<th>').addClass('col-xs-7').append($('<h5>').text('VALOR')));
              // head.append($('<th>').addClass('col-xs-3').append($('<h5>').text('COINOUT')));
              // head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('JACKPOT')));
              // head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('PROGRESIVO')));

              for (var i = 0; i < data.detalles_producido.length; i++) {
                  agregarFilaDetalleProducido(data.detalles_producido[i]);
              }
              break;
          }
      });
  }

  //Mostrar el modal de la vista previa
  $('#modalPlanilla').modal('show');
});

$(document).on('click','.borrar',function(){

  $('.modal-title').removeAttr('style');
  $('.modal-title').text('ADVERTENCIA');
  $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');


  var id_importacion = $(this).val();
  //Mirar en la tabla los tipos de archivos listados (1:contadores;2:producidos;3:beneficios).
  var tipo_archivo = $('#tablaImportaciones').attr('data-tipo');
  var nombre_tipo_archivo;

  switch (tipo_archivo) {
    case '1':
      nombre_tipo_archivo = 'CONTADOR';
      break;
    case '2':
      nombre_tipo_archivo = 'PRODUCIDO';
      break;
    case '3':
      nombre_tipo_archivo = 'BENEFICIO';
      break;
  }

  //Se muestra el modal de confirmación de eliminación
  //Se le pasa el tipo de archivo y el id del archivo
  $('#btn-eliminarModal').val(id_importacion).attr('data-tipo',tipo_archivo);
  $('#titulo-modal-eliminar').text('¿Seguro desea eliminar el '+ nombre_tipo_archivo + '?');
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
  var id_importacion = $(this).val();
  var tipo_archivo = $(this).attr('data-tipo');
  console.log('Borrar ' + tipo_archivo + ': ' + id_importacion);

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })

  var url;

  switch(tipo_archivo){
    case '1':
      url = "contadores/eliminarContador/" + id_importacion;
      break;
    case '2':
      url = "producidos/eliminarProducido/" + id_importacion;
      break;
    case '3':
      url = "beneficios/eliminarBeneficio/" + id_importacion;
      break;
  }

  $.ajax({
      type: "DELETE",
      url: url,
      success: function (data) {
        //Remueve de la tabla
        console.log();
        // $('#' + tipo_archivo + id_importacion).remove();
        $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

        $('#modalEliminar').modal('hide');

      },
      error: function (data) {
        console.log('Error: ', data);
      }
  });
});

/*********************** CONTADORES **********************************/
function agregarFilaDetalleContador(contador) {
  var fila = $('<tr>');

  fila.append($('<td>').addClass('col-xs-2').text(contador.nro_admin));
  fila.append($('<td>').addClass('col-xs-3').text(addCommas(contador.coinin)));
  fila.append($('<td>').addClass('col-xs-3').text(addCommas(contador.coinout)));
  fila.append($('<td>').addClass('col-xs-2').text(addCommas(contador.jackpot)));
  fila.append($('<td>').addClass('col-xs-2').text(addCommas(contador.progresivo)));

  $('#tablaVistaPrevia tbody').append(fila);
}

$('#btn-importarContadores').click(function(e){
    e.preventDefault();
    // $('.modalNuevo h3').text('| IMPORTAR CONTADORES');
    $('.modal-title').text('| IMPORTADOR CONTADOR');
    $('#modalImportacionContadores .modalNuevo').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
    // $('#btn-guardar').text('ACEPTAR');
    // $('#tipoImportacion').val('contador');


    //Mostrar: rowArchivo
    $('#modalImportacionContadores #rowArchivo').show();
    //Ocultar: rowFecha, mensajes, iconoCarga
    $('#modalImportacionContadores #rowFecha').hide();
    $('#modalImportacionContadores #mensajeError').hide();
    $('#modalImportacionContadores #mensajeInvalido').hide();
    $('#modalImportacionContadores #mensajeInformacion').hide();
    $('#modalImportacionContadores #iconoCarga').hide();

    habilitarInputContador();
    $('#modalImportacionContadores').find('.modal-footer').children().show();

    $('#mensajeExito').hide();
    $('#modalImportacionContadores').modal('show');

    //Ocultar botón SUBIR
    $('#btn-guardarContador').hide();
});

$('#btn-guardarContador').on('click', function(e){

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var url = 'importaciones/importarContador';

  var formData = new FormData();

  formData.append('id_casino', id_casino);

  //Si el contador es de ROSARIO manda la fecha y el tipo de moneda
  if(id_casino == '3') {
    formData.append('fecha', $('#fecha_hidden').val());
    formData.append('id_tipo_moneda', id_tipo_moneda);
  }
  else {
    formData.append('fecha', '');
    formData.append('id_tipo_moneda', '');
  }

  //Si subió archivo lo guarda
  if($('#modalImportacionContadores #archivo').attr('data-borrado') == 'false' && $('#modalImportacionContadores #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionContadores #archivo')[0].files[0]);
  }


  $.ajax({
      type: "POST",
      url: url,
      data: formData,
      processData: false,
      contentType:false,
      cache:false,
      beforeSend: function(data){
        console.log('Empezó');
        $('#modalImportacionContadores').find('.modal-footer').children().hide();
        $('#modalImportacionContadores').find('.modal-body').children().hide();

        $('#modalImportacionContadores').find('.modal-body').children('#iconoCarga').show();
      },
      complete: function(data){
        console.log('Terminó');
      },
      success: function (data) {

        //existe para el casino y la fecha relevamientos visados, por lo que no se puede importar

        if(data.resultado!='existeRel'){

          $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN CONTADOR');
          $('#mensajeExito p').text(data.cantidad_registros + ' registro(s) del CONTADOR fueron importados');


        $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

        $('#modalImportacionContadores').modal('hide');

        limpiarBodysImportaciones();

        var casino = id_casino.toString();
        var moneda = '1';

        console.log('En guardar contador, casino: ', casino);
        console.log('En guardar contador, moneda: ', moneda);

        if (casino == '3') moneda = id_tipo_moneda.toString();

        // cargarTablasImportaciones(casino, moneda);
        $('#casinoInfoImportacion').val(id_casino);
        $('#monedaInfoImportacion').val(id_tipo_moneda);
        $('#casinoInfoImportacion').change();

        $('#mensajeExito').show();

        }
        if(data.resultado == 'existeRel'){
          //se debe cambiar el modal para que sea homogeneo con el resto
          $('#modalImportacionContadores').modal('hide');
          $('#modalErrorVisado').modal(true);

        }
      },
      error: function (data) {
        //Mostrar: mensajeError
        $('#modalImportacionContadores #mensajeError').show();
        //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
        $('#modalImportacionContadores #rowArchivo').hide();
        $('#modalImportacionContadores #rowFecha').hide();
        $('#modalImportacionContadores #mensajeInvalido').hide();
        $('#modalImportacionContadores #mensajeInformacion').hide();
        $('#modalImportacionContadores #iconoCarga').hide();
        console.log('ERROR!');
        console.log(data);
      }
  });
});

function habilitarInputContador(){
  // $('#modalImportacionContadores').find('.modal-body').children().show();
  // $('#modalImportacionContadores').find('.modal-body').children('#iconoCarga').hide();

  // $('#modalImportacionContadores #mensajeInformacion').hide();
  // $('#modalImportacionContadores #rowFecha').hide();

  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionContadores #archivo')[0].files[0] = null;
  $('#modalImportacionContadores #archivo').attr('data-borrado','false');
  $("#modalImportacionContadores #archivo").fileinput('destroy').fileinput({
      language: 'es',
    //       showPreview: false,
          // allowedFileExtensions: ["csv", "txt"],
    //       elErrorContainer: "#alertaArchivo"
      language: 'es',
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: false,
      preferIconicPreview: true,
      previewFileIconSettings: {
        'csv': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>',
        'txt': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>'
      },
      allowedFileExtensions: ['csv','txt'],
  });
}

function procesarDatosContador(e) {
    var csv = e.target.result;
    // procesarDatosContador(csv);

    // var allTextLines = csv.split(/\r\n|\n/);
    var allTextLines = csv.split('\n'); //Se obtienen todas las filas del archivo

    //Comprobar que el tamaño del archivo corresponda al de un contador
    if (allTextLines.length > 5) {
        // Se obtienen todas las columnas de la fila 6.
        // En esta fila los contadores de todos los casinos tienen los datos importantes para identificarlo.
        var data = allTextLines[6].split(';');

        var tarr = [];

        for (var j=0; j<data.length; j++) {
              tarr.push(data[j]);
        }

        console.log(tarr);

        //Comprobar que el archivo subido no sea un producido o beneficio
        if (tarr.length == 5 || tarr.length == COL_PROD_SFE || tarr.length == COL_BEN_ROS) {
            console.log("Archivo incorrecto");


            $('#modalImportacionContadores #rowFecha').hide();
            $('#modalImportacionContadores #mensajeError').hide();
            $('#modalImportacionContadores #mensajeInformacion').hide();
            $('#modalImportacionContadores #iconoCarga').hide();
            //Ocultar botón de subida
            $('#modalImportacionContadores #btn-guardarContador').hide();


            $('#modalImportacionContadores #mensajeInvalido').show();
            $('#modalImportacionContadores #mensajeInvalido p').text('El archivo no contiene contadores de ningún casino');

        } else {
            //El archivo es de contadores. Se oculta el mensaje invalido.
            $('#modalImportacionContadores #mensajeInvalido').hide();

            //Analizar de qué CASINO es el archivo
            if (tarr.length == 16) {
                  console.log('ROSARIO');
                  id_casino = 3;

                  //Habilitar el ingreso de fecha
                  $('#modalImportacionContadores #rowFecha').show();
                  //Ocultar mensaje hasta que no elija una fecha
                  $('#modalImportacionContadores #mensajeInformacion').hide();

                  $('#modalImportacionContadores #informacionCasino').text('CASINO ROSARIO');

                  var nro_admin = tarr[1].slice(0,4);

                  //Consultar tipo de moneda
                  $.get('maquinas/getMoneda/' + nro_admin , function(data) {
                      // var tipo_moneda = 'DOLAR';
                      // id_tipo_moneda = 2;
                      console.log('Data tipo moneda: ' , data);

                      if (data.tipo != null) {
                          id_tipo_moneda = data.tipo.id_tipo_moneda;
                          tipo_moneda = data.tipo.descripcion;
                      }

                      $('#modalImportacionContadores #informacionMoneda').text(tipo_moneda);
                      $('#modalImportacionContadores #iconoMoneda').show();
                      $('#modalImportacionContadores #informacionMoneda').show();
                  });

                  $('#modalImportacionContadores #fecha input').val('');

            } else {
                  //Ocultar el input de fecha
                  $('#modalImportacionContadores #rowFecha').hide();
                  //Mostrar el mensaje de información
                  $('#modalImportacionContadores #mensajeInformacion').show();
                  //Mostrar botón SUBIR
                  $('#btn-guardarContador').show();

                  var fecha = tarr[16];
                  $('#modalImportacionContadores #informacionFecha').text(obtenerFechaString(fecha,true));

                  $('#modalImportacionContadores #iconoMoneda').hide();
                  $('#modalImportacionContadores #informacionMoneda').hide();

                  if (tarr[3] < 2000) {
                      console.log('MELINCUÉ');
                      id_casino = 1;
                      //Setear la información del mensaje
                      $('#modalImportacionContadores #informacionCasino').text('CASINO MELINCUÉ');
                  }else {
                      console.log('SANTA FE');
                      id_casino = 2;
                      $('#modalImportacionContadores #informacionCasino').text('CASINO SANTA FE');
                  }
            } // 'else' del tipo de casino

        } // 'else' de comprobar tipo de archivo

    }
    //El archivo no tiene el tamaño correcto
    else {
      console.log("ARCHIVO MUY CHICO!");

      $('#modalImportacionContadores #rowFecha').hide();
      $('#modalImportacionContadores #mensajeError').hide();
      $('#modalImportacionContadores #mensajeInformacion').hide();
      $('#modalImportacionContadores #iconoCarga').hide();
      //Ocultar botón de subida
      $('#modalImportacionContadores #btn-guardarContador').hide();


      $('#modalImportacionContadores #mensajeInvalido').show();
      $('#modalImportacionContadores #mensajeInvalido p').text('El archivo no contiene contadores de ningún casino');
    }



}

$('#modalImportacionContadores #fecha > input').on('change', function(){
  //Si hay una fecha mostrar el mensaje de información
  if ($(this).val() != '') {
    $('#mensajeInformacion').show();
    $('#informacionFecha').text($(this).val().toUpperCase());

    //Mostrar botón SUBIR
    $('#btn-guardarContador').show();
  } else {
    $('#mensajeInformacion').hide();
    //Ocultar botón SUBIR
    $('#btn-guardarContador').hide();
  }
});

//Eventos de la librería del input
$('#modalImportacionContadores #archivo').on('fileerror', function(event, data, msg) {
   // console.log(data.id);
   // console.log(data.index);
   // console.log(data.file);
   // console.log(data.reader);
   // console.log(data.files);
   // get message
   // alert(msg);

   $('#modalImportacionContadores #rowFecha').hide();
   $('#modalImportacionContadores #mensajeInformacion').hide();
   $('#modalImportacionContadores #mensajeInvalido').show();
   $('#modalImportacionContadores #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardarContador').hide();

});

$('#modalImportacionContadores #archivo').on('fileclear', function(event) {
    $('#modalImportacionContadores #archivo').attr('data-borrado','true');
    $('#modalImportacionContadores #archivo')[0].files[0] = null;
    $('#modalImportacionContadores #mensajeInformacion').hide();
    $('#modalImportacionContadores #mensajeInvalido').hide();
    $('#modalImportacionContadores #rowFecha').hide();
    //Ocultar botón SUBIR
    $('#btn-guardarContador').hide();
});

$('#modalImportacionContadores #archivo').on('fileselect', function(event) {
    $('#modalImportacionContadores #archivo').attr('data-borrado','false');

    // Se lee el archivo guardado en el input de tipo 'file'.
    // Luego se lo maneja para saber a qué casino pertenece
    // y así, tener una forma para validar la importación.
    var reader = new FileReader();
    reader.readAsText($('#modalImportacionContadores #archivo')[0].files[0]);
    reader.onload = procesarDatosContador;
});

$('#btn-reintentarContador').click(function(e) {
  //Mostrar: rowArchivo
  $('#modalImportacionContadores #rowArchivo').show();
  //Ocultar: rowFecha, mensajes, iconoCarga
  $('#modalImportacionContadores #rowFecha').hide();
  $('#modalImportacionContadores #mensajeError').hide();
  $('#modalImportacionContadores #mensajeInvalido').hide();
  $('#modalImportacionContadores #mensajeInformacion').hide();
  $('#modalImportacionContadores #iconoCarga').hide();

  habilitarInputContador();
  $('#modalImportacionContadores').find('.modal-footer').children().show();
});

/*********************** PRODUCIDOS *********************************/
function agregarFilaDetalleProducido(producido) {
  var fila = $('<tr>');

  fila.append($('<td>').addClass('col-xs-5').text(producido.nro_admin));
  fila.append($('<td>').addClass('col-xs-7').text(producido.valor));
  // fila.append($('<td>').addClass('col-xs-3').text(producido.coinout));
  // fila.append($('<td>').addClass('col-xs-2').text(producido.jackpot));
  // fila.append($('<td>').addClass('col-xs-2').text(producido.progresivo));

  $('#tablaVistaPrevia tbody').append(fila);
}

$('#btn-importarProducidos').click(function(e){
  e.preventDefault();
  $('.modal-title').text('| IMPORTAR PRODUCIDOS');
  $('#modalImportacionProducidos .modalNuevo').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');

  //Mostrar: rowArchivo
  $('#modalImportacionProducidos #rowArchivo').show();

  //Ocultar: rowFecha, mensajes, iconoCarga
  // $('#modalImportacionProducidos #rowMoneda').hide();
  $('#modalImportacionProducidos #mensajeError').hide();
  $('#modalImportacionProducidos #mensajeInvalido').hide();
  $('#modalImportacionProducidos #mensajeInformacion').hide();
  $('#modalImportacionProducidos #iconoCarga').hide();

  habilitarInputProducido();
  $('#modalImportacionProducidos').find('.modal-footer').children().show();

  $('#mensajeExito').hide();
  $('#modalImportacionProducidos').modal('show');

  //Ocultar botón SUBIR
  $('#btn-guardarProducido').hide();
});

$('#btn-guardarProducido').on('click',function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var url = 'importaciones/importarProducido';

  var formData = new FormData();

  formData.append('id_casino', id_casino);
  formData.append('fecha', fecha_date);
  formData.append('id_tipo_moneda',id_tipo_moneda);

  //Si subió archivo lo guarda
  if($('#modalImportacionProducidos #archivo').attr('data-borrado') == 'false' && $('#modalImportacionProducidos #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionProducidos #archivo')[0].files[0]);
  }


    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        processData: false,
        contentType:false,
        cache:false,
        beforeSend: function(data){
          console.log('Empezó');
          $('#modalImportacionProducidos').find('.modal-footer').children().hide();
          $('#modalImportacionProducidos').find('.modal-body').children().hide();

          $('#modalImportacionProducidos').find('.modal-body').children('#iconoCarga').show();
        },
        complete: function(data){
          console.log('Terminó');
        },
        success: function (data) {

          $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

          $('#modalImportacionProducidos').modal('hide');

          limpiarBodysImportaciones();

          var casino = id_casino.toString();
          var moneda = id_tipo_moneda.toString();

          // if (id_casino == 3) cargarTablasImportaciones(casino, moneda);
          // else cargarTablasImportaciones(casino, '1');

          $('#casinoInfoImportacion').val(id_casino);
          $('#monedaInfoImportacion').val(id_tipo_moneda);
          $('#casinoInfoImportacion').change();

          $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN PRODUCIDO');

          text=data.cantidad_registros + ' registro(s) del PRODUCIDO fueron importados'
          if(data.cant_mtm_forzadas){
            text=text+ '<br>' + data.cant_mtm_forzadas +' Máquinas no reportaron'
          }

          $('#mensajeExito p').html(text);

          $('#mensajeExito').show();
        },
        error: function (data) {
          //alerta de error si el archivo ya se encuentra cargado y validado.
          var response = JSON.parse(data.responseText);
          if(response.producido_validado !== 'undefined'){
            $('#mensajeError h6').text('El Producido para esa fecha ya está validado y no se puede reimportar.')
          }
          //Mostrar: mensajeError
          $('#modalImportacionProducidos #mensajeError').show();
          //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
          $('#modalImportacionProducidos #rowArchivo').hide();
          // $('#modalImportacionProducidos #rowFecha').hide();
          $('#modalImportacionProducidos #mensajeInvalido').hide();
          $('#modalImportacionProducidos #mensajeInformacion').hide();
          $('#modalImportacionProducidos #iconoCarga').hide();


          console.log('ERROR!');
          console.log(data);
        }
    });
});

function habilitarInputProducido(){
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionProducidos #archivo')[0].files[0] = null;
  // console.log($('#modalImportacionProducidos #archivo'));
  $('#modalImportacionProducidos #archivo').attr('data-borrado','false');
  $("#modalImportacionProducidos #archivo").fileinput('destroy').fileinput({
      language: 'es',
    //       showPreview: false,
          // allowedFileExtensions: ["csv", "txt"],
    //       elErrorContainer: "#alertaArchivo"
      language: 'es',
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: false,
      preferIconicPreview: true,
      previewFileIconSettings: {
        'csv': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>',
        'txt': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>'
      },
      allowedFileExtensions: ['csv','txt'],
  });
}

function procesarDatosProducidos(e) {
    var csv = e.target.result;

    // procesarDatosContador(csv);

    // var allTextLines = csv.split(/\r\n|\n/);
    var allTextLines = csv.split('\n');

    if (allTextLines.length > 2 ) {
        var data = allTextLines[2].split(';');

        var tarr = [];

        for (var j=0; j<data.length; j++) {
              tarr.push(data[j]);
        }

        console.log('allTextLines: ', allTextLines.length);
        console.log('tarr:', tarr);

        //Mirar si la cantidad de columnas pertenece a un archivo de producido
        if (tarr.length == COL_PROD_ROS || tarr.length == COL_PROD_SFE) {
          console.log('Está bien');
          //Mostrar el select de moneda (único dato que no se puede obtener desde el archivo)

          //Si es de Santa Fe o Melincué, CASINO: 1ra columna del CSV; MONEDA: Pesos; FECHA: 3ra columna del CSV;
          //Si es de Rosario, MONEDA: según cantidad de filas del archivo; FECHA: en 1ra columna del CSV;
          switch (tarr.length) {
            case COL_PROD_SFE:
              //Verificar el CASINO: 1.Melincué; 2.Santa Fe;
              if (tarr[0] == 1) {
                id_casino = 1;
                $('#modalImportacionProducidos #informacionCasino').text('CASINO MELINCUÉ');
              }else {
                id_casino = 2;
                $('#modalImportacionProducidos #informacionCasino').text('CASINO SANTA FE');
              }

              //Se saca la fecha del CSV en formato string
              var fecha = tarr[2];
              //Se arma un date con esos datos
              var dia = fecha.substring(6,8);
              var mes = fecha.substring(4,6);
              var anio = fecha.substring(0,4);

              //Se arma así (dd/MM/AAAA) para mostrarlo
              fecha_date = dia+'/'+mes+'/'+anio;
              $('#modalImportacionProducidos #informacionFecha').text(obtenerFechaString(fecha_date, true));
              //Se arma así para mandarlo a la BD
              fecha_date = anio+'/'+mes+'/'+dia;

              $('#modalImportacionProducidos #informacionMoneda').text('ARS');

              id_tipo_moneda = 1;

              break;
            case COL_PROD_ROS:
              id_casino = 3;
              //Setear el nombre
              $('#modalImportacionProducidos #informacionCasino').text('CASINO ROSARIO');
              //Se obtiene la fecha del CSV para mostrarlo
              fecha_date = tarr[0].substring(0,10);
              //Setear la fecha en el modal
              $('#modalImportacionProducidos #informacionFecha').text(obtenerFechaString(fecha_date, true));
              //Se modifica el date para guardalo en la BD
              fecha_date = tarr[0].substring(0,10).split('/');
              fecha_date = fecha_date[2] + '/' + fecha_date[1] + '/' + fecha_date[0];


              //Si hay más de 1000 lineas entonces tiene que ser en PESOS
              if (allTextLines.length > 1000) {
                id_tipo_moneda = 1;
                $('#modalImportacionProducidos #informacionMoneda').text('ARS');
              }else {
                id_tipo_moneda = 2;
                $('#modalImportacionProducidos #informacionMoneda').text('USD');
              }

              break;
          }



          $('#modalImportacionProducidos #mensajeInvalido').hide();
          $('#modalImportacionProducidos #mensajeInformacion').show();
          //Mostrar botón SUBIR
          $('#btn-guardarProducido').show();
        }
        //No pertenece a un archivo de producido
        else {
          $('#modalImportacionProducidos #mensajeInformacion').hide();

          $('#modalImportacionProducidos #mensajeInvalido p').text('El archivo no contiene producidos');
          $('#modalImportacionProducidos #mensajeInvalido').show();

          $('#modalImportacionProducidos #iconoCarga').hide();
          //Ocultar botón de subida
          $('#btn-guardarProducido').hide();
        }
    }
    else {
      $('#modalImportacionProducidos #mensajeInformacion').hide();

      $('#modalImportacionProducidos #mensajeInvalido p').text('El archivo no contiene producidos');
      $('#modalImportacionProducidos #mensajeInvalido').show();

      $('#modalImportacionProducidos #iconoCarga').hide();
      //Ocultar botón de subida
      $('#btn-guardarProducido').hide();
    }



}

//Eventos de la librería del input
$('#modalImportacionProducidos #archivo').on('fileerror', function(event, data, msg) {
   // $('#modalImportacionProducidos #rowMoneda').hide();
   $('#modalImportacionProducidos #mensajeInformacion').hide();
   $('#modalImportacionProducidos #mensajeInvalido').show();
   $('#modalImportacionProducidos #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardarProducido').hide();

});

$('#modalImportacionProducidos #archivo').on('fileclear', function(event) {
    id_tipo_moneda = 0;
    $('#modalImportacionProducidos #archivo').attr('data-borrado','true');
    $('#modalImportacionProducidos #archivo')[0].files[0] = null;
    $('#modalImportacionProducidos #mensajeInformacion').hide();
    $('#modalImportacionProducidos #mensajeInvalido').hide();
    // $('#modalImportacionProducidos #rowMoneda').hide();
    //Ocultar botón SUBIR
    $('#btn-guardarProducido').hide();
});

$('#modalImportacionProducidos #archivo').on('fileselect', function(event) {
    $('#modalImportacionProducidos #archivo').attr('data-borrado','false');

    // Se lee el archivo guardado en el input de tipo 'file'.
    // Luego se lo maneja para saber a qué casino pertenece
    // y así, tener una forma para validar la importación.
    var reader = new FileReader();
    reader.readAsText($('#modalImportacionProducidos #archivo')[0].files[0]);
    reader.onload = procesarDatosProducidos;
});

$('#btn-reintentarProducido').click(function(e) {
  //Mostrar: rowArchivo
  $('#modalImportacionProducidos #rowArchivo').show();
  //Ocultar: rowFecha, mensajes, iconoCarga
  // $('#modalImportacionContadores #rowFecha').hide();
  $('#modalImportacionProducidos #mensajeError').hide();
  $('#modalImportacionProducidos #mensajeInvalido').hide();
  $('#modalImportacionProducidos #mensajeInformacion').hide();
  $('#modalImportacionProducidos #iconoCarga').hide();

  habilitarInputProducido();
  $('#modalImportacionProducidos').find('.modal-footer').children().show();
});

/*********************** BENEFICIOS *********************************/
function agregarFilaDetalleBeneficio(beneficio){
  var fila = $('<tr>');

  fila.append($('<td>').addClass('col-xs-2').text(convertirDate(beneficio.fecha)));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.coinin));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.coinout));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.valor));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.porcentaje_devolucion));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.promedio_por_maquina));

  $('#tablaVistaPrevia tbody').append(fila);
}

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| IMPORTACIONES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

$('#btn-importarBeneficios').click(function(e){
  e.preventDefault();
  $('.modal-title').text('| IMPORTAR BENEFICIOS');
  $('#modalImportacionBeneficios .modalNuevo').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');

  //Mostrar: rowArchivo
  $('#modalImportacionBeneficios #rowArchivo').show();
  //Ocultar: rowFecha, mensajes, iconoCarga
  $('#modalImportacionBeneficios #rowMoneda').hide();
  $('#modalImportacionBeneficios #mensajeError').hide();
  $('#modalImportacionBeneficios #mensajeInvalido').hide();
  $('#modalImportacionBeneficios #mensajeInformacion').hide();
  $('#modalImportacionBeneficios #iconoCarga').hide();

  habilitarInputBeneficio();
  $('#modalImportacionBeneficios').find('.modal-footer').children().show();

  $('#mensajeExito').hide();
  $('#modalImportacionBeneficios').modal('show');

  //Ocultar botón SUBIR
  $('#btn-guardarBeneficio').hide();
});

$('#btn-guardarBeneficio').on('click', function(e){

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var url = 'importaciones/importarBeneficio';

  var formData = new FormData();


  formData.append('id_casino', 3);
  formData.append('fecha', fecha_date);
  formData.append('id_tipo_moneda',id_tipo_moneda);

  //Si subió archivo lo guarda
  if($('#modalImportacionBeneficios #archivo').attr('data-borrado') == 'false' && $('#modalImportacionBeneficios #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionBeneficios #archivo')[0].files[0]);
  }


  $.ajax({
      type: "POST",
      url: url,
      data: formData,
      processData: false,
      contentType:false,
      cache:false,
      beforeSend: function(data){
        console.log('Empezó');
        $('#modalImportacionBeneficios').find('.modal-footer').children().hide();
        $('#modalImportacionBeneficios').find('.modal-body').children().hide();

        $('#modalImportacionBeneficios').find('.modal-body').children('#iconoCarga').show();
      },
      complete: function(data){
        console.log('Terminó');
      },
      success: function (data) {

        $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

        $('#modalImportacionBeneficios').modal('hide');

        limpiarBodysImportaciones();

        var moneda = id_tipo_moneda.toString();

        // cargarTablasImportaciones('3', moneda);
        // $('#casinoInfoImportacion').val(3).trigger('change');
        $('#casinoInfoImportacion').val(id_casino);
        $('#monedaInfoImportacion').val(id_tipo_moneda);
        $('#casinoInfoImportacion').change();


        $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN BENEFICIO');
        $('#mensajeExito p').text(data.cantidad_registros + ' registro(s) del BENEFICIO fueron importados');

        $('#mensajeExito').show();
      },
      error: function (data) {
        //Mostrar: mensajeError
        $('#modalImportacionBeneficios #mensajeError').show();
        //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
        $('#modalImportacionBeneficios #rowArchivo').hide();
        $('#modalImportacionBeneficios #rowFecha').hide();
        $('#modalImportacionBeneficios #mensajeInvalido').hide();
        $('#modalImportacionBeneficios #mensajeInformacion').hide();
        $('#modalImportacionBeneficios #iconoCarga').hide();
        console.log('ERROR!');
        console.log(data);
      }
  });
});

function habilitarInputBeneficio(){
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionBeneficios #archivo')[0].files[0] = null;
  $('#modalImportacionBeneficios #archivo').attr('data-borrado','false');
  $("#modalImportacionBeneficios #archivo").fileinput('destroy').fileinput({
      language: 'es',
    //       showPreview: false,
          // allowedFileExtensions: ["csv", "txt"],
    //       elErrorContainer: "#alertaArchivo"
      language: 'es',
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: false,
      preferIconicPreview: true,
      previewFileIconSettings: {
        'csv': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>',
        'txt': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>'
      },
      allowedFileExtensions: ['csv','txt'],
  });
}

function procesarDatosBeneficios(e) {
    var csv = e.target.result;
    // procesarDatosContador(csv);

    // var allTextLines = csv.split(/\r\n|\n/);
    var allTextLines = csv.split('\n');

    console.log(allTextLines.length);

    if (allTextLines.length > 4) {
        console.log('ASD');
        var data = allTextLines[4].split(';');

        var tarr = [];

        for (var j=0; j<data.length; j++) {
              tarr.push(data[j]);
        }

        console.log(tarr);
        if (tarr.length == COL_BEN_ROS) {
            console.log('Está bien');
            id_casino = 3;
            //Mostrar el select de moneda (único dato que no se puede obtener desde el archivo)
            $('#modalImportacionBeneficios #rowMoneda').show();
            $('#modalImportacionBeneficios #rowMoneda select').val(0);
            $('#modalImportacionBeneficios #mensajeInvalido').hide();

            //Info casino
            $('#modalImportacionBeneficios #informacionCasino').text('CASINO ROSARIO');
            //Info fecha
            fecha_date = tarr[0];

            $('#modalImportacionBeneficios #informacionFecha').text(obtenerFechaString(fecha_date, false));
        }
        else {
            $('#modalImportacionBeneficios #rowMoneda').hide();
            $('#modalImportacionBeneficios #mensajeInformacion').hide();

            $('#modalImportacionBeneficios #mensajeInvalido p').text('El archivo no contiene beneficios');
            $('#modalImportacionBeneficios #mensajeInvalido').show();

            $('#modalImportacionBeneficios #iconoCarga').hide();
            //Ocultar botón de subida
            $('#modalImportacionBeneficios #btn-guardarBeneficio').hide();
        }

    } else {

        $('#modalImportacionBeneficios #rowMoneda').hide();
        $('#modalImportacionBeneficios #mensajeInformacion').hide();

        $('#modalImportacionBeneficios #mensajeInvalido p').text('El archivo no contiene beneficios');
        $('#modalImportacionBeneficios #mensajeInvalido').show();

        $('#modalImportacionBeneficios #iconoCarga').hide();
        //Ocultar botón de subida
        $('#modalImportacionBeneficios #btn-guardarBeneficio').hide();
    }


}

$('#modalImportacionBeneficios #rowMoneda select').change(function(e) {
  console.log('CAMBIÓ');

  //Si se elige una moneda
  if ($(this).val() != 0) {
    id_tipo_moneda = $(this).val();

    $('#modalImportacionBeneficios #informacionMoneda').text($(this).find('option:selected').text());
    $('#modalImportacionBeneficios #iconoMoneda').show();
    $('#modalImportacionBeneficios #informacionMoneda').show();
    //Mostrar el mensaje de información
    $('#modalImportacionBeneficios #mensajeInformacion').show();
    //Mostrar botón SUBIR
    $('#btn-guardarBeneficio').show();
  } else {
    $('#modalImportacionBeneficios #mensajeInformacion').hide();
    $('#btn-guardarBeneficio').hide();
  }

});

//Eventos de la librería del input
$('#modalImportacionBeneficios #archivo').on('fileerror', function(event, data, msg) {
   $('#modalImportacionBeneficios #rowMoneda').hide();
   $('#modalImportacionBeneficios #mensajeInformacion').hide();
   $('#modalImportacionBeneficios #mensajeInvalido').show();
   $('#modalImportacionBeneficios #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardarBeneficio').hide();

});

$('#modalImportacionBeneficios #archivo').on('fileclear', function(event) {
    id_tipo_moneda = 0;
    $('#modalImportacionBeneficios #archivo').attr('data-borrado','true');
    $('#modalImportacionBeneficios #archivo')[0].files[0] = null;
    $('#modalImportacionBeneficios #mensajeInformacion').hide();
    $('#modalImportacionBeneficios #mensajeInvalido').hide();
    $('#modalImportacionBeneficios #rowMoneda').hide();
    //Ocultar botón SUBIR
    $('#btn-guardarBeneficio').hide();
});

$('#modalImportacionBeneficios #archivo').on('fileselect', function(event) {
    $('#modalImportacionBeneficios #archivo').attr('data-borrado','false');

    // Se lee el archivo guardado en el input de tipo 'file'.
    // Luego se lo maneja para saber a qué casino pertenece
    // y así, tener una forma para validar la importación.
    var reader = new FileReader();
    reader.readAsText($('#modalImportacionBeneficios #archivo')[0].files[0]);
    reader.onload = procesarDatosBeneficios;
});

/*****************PAGINACION******************/

function agregarFilasImportaciones(data, id) {
  var fila = $('<tr>');

  var meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];

  //Si es beneficio no se muestra el dia y se agregan los 'datas'
  if (id == null) {
    fila.append($('<td>').addClass('col-xs-3').text("-"));
    fila.append($('<td>').addClass('col-xs-3').text(meses[data.mes - 1] + ' ' + data.anio));
    fila.append($('<td>').addClass('col-xs-2').text(data.casino));
    fila.append($('<td>').addClass('col-xs-2').text(data.tipo_moneda));
    fila.append($('<td>').addClass('col-xs-2')
                         .append($('<button>').addClass('btn btn-info planilla')
                                              .attr('data-mes', data.mes)
                                              .attr('data-anio', data.anio)
                                              .attr('data-casino', data.id_casino)
                                              .attr('data-moneda', data.id_tipo_moneda)
                                              .append($('<i>').addClass('far fa-fw fa-file-alt'))
                         )
                         .append($('<button>').addClass('btn btn-danger borrar').val(id)
                                              .append($('<i>').addClass('fa fa-fw fa-trash-alt'))

                         )
               )
  }
  else {
    var archivo = typeof data.fecha_archivo == "undefined" ? "-" : convertirDate(data.fecha_archivo);
    fila.append($('<td>').addClass('col-xs-3').text(archivo));
    fila.append($('<td>').addClass('col-xs-3').text(convertirDate(data.fecha)));
    fila.append($('<td>').addClass('col-xs-2').text(data.casino));
    fila.append($('<td>').addClass('col-xs-2').text(data.tipo_moneda));
    fila.append($('<td>').addClass('col-xs-2')
                         .append($('<button>').addClass('btn btn-info planilla').val(id)
                                              .append($('<i>').addClass('far fa-fw fa-file-alt'))

                         )
                         .append($('<button>').addClass('btn btn-danger borrar').val(id)
                                              .append($('<i>').addClass('fa fa-fw fa-trash-alt'))

                         )
               )
  }


  $('#tablaImportaciones tbody').append(fila);
}

//Detectar el cambio de TIPO DE ARCHIVO
$('#tipo_archivo').on('change',function(){
    setearValueFecha();

    // $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaImportaciones .activa').attr('value');
  var orden = $('#tablaImportaciones .activa').attr('estado');
  $('#btn-buscarImportaciones').trigger('click',[pageNumber,tam,columna,orden]);
}

$(document).on('click','#tablaImportaciones thead tr th[value]',function(e){
  $('#tablaImportaciones th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-desc')){
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaImportaciones th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

$('#btn-buscarImportaciones').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaImportaciones .activa').attr('value'),orden: $('#tablaImportaciones .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaImportaciones th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  // var page_size = (page_size != null) ? page_size : 5;
  // var page_number = (pagina != null) ? pagina : 1;
  // var sort_by = (columna != null) ? {columna,orden} : null;
  // if(sort_by == null){ // limpio las columnas
  //   $('#tablaImportaciones th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  // }

  var formData = {
    fecha: $('#fecha_busqueda_hidden').val(),
    casinos: $('#casino_busqueda').val(),
    tipo_moneda: $('#moneda_busqueda').val(),
    seleccion: $('#tipo_archivo').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  console.log('FormData de buscar: ', formData);

  $.ajax({
    type: "POST",
    url: 'importaciones/buscar',
    data: formData,
    dataType: 'json',
    success: function (resultados) {
      $('#tablaImportaciones tbody tr').remove();

      //Mostrar CONTADORES
      if (typeof resultados.contadores.total != 'undefined') {
          $('#tablaImportaciones').attr('data-tipo', 1);

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.contadores.total,clickIndice);
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.contadores.total,clickIndice);

          $('#tituloTabla').text('Todos los contadores');
          // $('#tituloTabla').generarTitulo(page_number,page_size,resultados.contadores.total,clickIndice);
          // $('#indicesPaginacion').generarIndices(page_number,page_size,resultados.contadores.total,clickIndice);

          // $('#tituloTabla h4').before($('<h4>').css('font-weight','bold').text('CONTADORES')).before($('<br>'));

          for (var i = 0; i < resultados.contadores.data.length; i++) {
              agregarFilasImportaciones(resultados.contadores.data[i],resultados.contadores.data[i].id_contador_horario);
          }

      }else {
        //Mostrar BENEFICIOS
        if (typeof resultados.beneficios.total != 'undefined') {
          $('#tablaImportaciones').attr('data-tipo', 3);

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.beneficios.total,clickIndice);
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.beneficios.total,clickIndice);

          $('#tituloTabla').text('Todos los Beneficios');
          // $('#tituloTabla').generarTitulo(page_number,page_size,resultados.beneficios.total,clickIndice);
          // $('#indicesPaginacion').generarIndices(page_number,page_size,resultados.beneficios.total,clickIndice);

          // $('#tituloTabla h4').before($('<h4>').css('font-weight','bold').text('BENEFICIOS')).before($('<br>'));

          for (var i = 0; i < resultados.beneficios.data.length; i++) {
              // agregarFilasImportaciones(resultados.beneficios.data[i],resultados.beneficios.data[i].id_beneficio);
              agregarFilasImportaciones(resultados.beneficios.data[i], null);
          }
        }
        //Mostrar PRODUCIDOS
        else if (typeof resultados.producidos.total != 'undefined') {
          $('#tablaImportaciones').attr('data-tipo', 2);

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.producidos.total,clickIndice);
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.producidos.total,clickIndice);

          $('#tituloTabla').text('Todos los PRODUCIDOS');

          // $('#tituloTabla').generarTitulo(page_number,page_size,resultados.producidos.total,clickIndice);
          // $('#indicesPaginacion').generarIndices(page_number,page_size,resultados.producidos.total,clickIndice);

          // $('#tituloTabla h4').before($('<h4>').css('font-weight','bold').text('PRODUCIDOS')).before($('<br>'));

          for (var i = 0; i < resultados.producidos.data.length; i++) {
              agregarFilasImportaciones(resultados.producidos.data[i],resultados.producidos.data[i].id_producido);
          }
        }

      }



      // $('#tituloTabla').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      // $('#tablaRelevamientos tbody tr').remove();
      // for (var i = 0; i < resultados.data.length; i++) {
      //   $('#tablaRelevamientos tbody').append(crearFilaTabla(resultados.data[i]));
      //   console.log(resultados.data[i]);
      // }
      //
      // $('[data-toggle="tooltip"]').tooltip();
      //
      // $('#indicesPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});
