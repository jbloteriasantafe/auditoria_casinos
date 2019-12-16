//$('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN');
//$('#mensajeExito p').text(data.cantidad_registros + ' registro(s) del PRODUCIDO fueron importados');
//$('#mensajeExito').show();
//Resaltar la sección en el menú del costado
$(document).ready(function() {
  $('#gestionExpediente').addClass('active');
  $('#liExpedientes ul').removeClass('collapse').removeAttr('id');
  $('#liExpedientes a').removeAttr('data-toggle').removeAttr('data-target');
  //Se esconde el botón de agregar GLI Soft
  $('#agregarGLISoft').css('display','none'); //Se esconde el botón de agregar
  $('#agregarGLIHard').css('display','none'); //Se esconde el botón de agregar
  //Se ocultan todos los alertas
  $('#alerta-nroExpediente').hide();
  $('#alerta-fechaPase').hide();
  $('#alerta-fechaInicio').hide();
  $('#alerta-destino').hide();
  $('#alerta-ubicacion').hide();
  $('#alerta-iniciador').hide();
  $('#alerta-remitente').hide();
  $('#alerta-concepto').hide();
  $('#alerta-tema').hide();
  $('#alerta-nroCuerpos').hide();
  $('#alerta-nroFolios').hide();
  $('#alerta-anexo').hide();
  $('#alerta-resolucion').hide();

});

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

//Quitar eventos de la tecla Enter y guardar
$(document).on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-guardar').click();
    }
});

/**************************************************
  TODOS LOS EVENTOS DE GLI SOFT
**************************************************/
function onInputSoft() {
  console.log('Se hizo el onInput');
    var val = document.getElementById("inputSoft").value;
    var opts = document.getElementById('soft').childNodes;
    console.log('El valor de inputSoft: ' + val);
    for (var i = 0; i < opts.length; i++) {
      if(opts[i].text === val){
        // console.log('El id del option: ' + opts[i].id);
        $('#inputSoft').attr('data-soft',opts[i].id);
      }
    }
}

//Evento de GLI soft seleccionado de la lista
$('#inputSoft').bind('select', function(e) {
  if (e.timeStamp != 0) { //Si se selecciona de la lista (timeStamp == 0 es un dobleclick)
    $('#inputSoft').prop("readonly", true); //Se bloquea el input
    $('#agregarGLISoft').css('display','inline'); //Se muestra el botón de agregar
  }
});

//Evento de tipeo en el input
$('#inputSoft').bind('input', function() {
    datalist = $('#soft');

    $('#soft').empty();

    //Lo escrito en el input
    var inputGli = $(this).val();

    if(inputGli.length > 2) {
        console.log('Hay más de 3 caracteres en el input');

        //Si el string del input es más largo que 2 caracteres busca en la BD
        $.get("certificadoSoft/buscarGliSoftsPorNroArchivo/" + inputGli, function(data){
            console.log('Hizo el get');
            console.log(data);

            $('#soft').empty();

            //Recorre el arreglo de los GLI que vienen de la BASE
            $.each(data.gli_softs, function(index, gli_soft) {
                var seleccionado = false;

                //Recorre la lista de agregados
                $('#listaGLISoft li').each(function(){
                    if($(this).val() == gli_soft.id_gli_soft){
                      seleccionado = true;
                    }
                });

                //Si ya está en la lista de agregados entonces no lo muestra en el datalist
                if(!seleccionado){
                  $('#soft').append($('<option>').text(gli_soft.nro_archivo).attr('id',gli_soft.id_gli_soft));
                  console.log('Se agregó ' + gli_soft.nro_archivo + ' a la lista!');
                  //Importante! Se quita el foco y se lo saca para que recargue la lista
                  // $(this).blur();
                  // $(this).focus();
                }
            });
        });
    }else {
      $('#soft').empty();
    }

});

//Botón Cancelar input GLI Soft
$('#cancelarGLISoft').click(function(){
    $('#inputSoft').prop("readonly", false); //Se habilita el input
    $('#inputSoft').val(''); //Se limpia el input
    $('#agregarGLISoft').css('display','none'); //Se esconde el botón de agregar

    //Vaciar datasets
    // $('#soft > option').remove();
});

//Agregar GLI Soft
$('#agregarGLISoft').click(function(){
      //Crear un item de la lista
      $('#listaGLISoft')
        .append($('<li>')
             .text($('#inputSoft').val())
             .val($('#inputSoft').attr('data-soft'))
             .append($('<button>')
                  .addClass('btn').addClass('btn-danger').addClass('btn-xs').addClass('borrarGliSoft')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-times')
                  )
             )
        );

      $('#inputSoft').prop("readonly", false); //Se habilita el input
      $('#inputSoft').val(''); //Se limpia el input
      $('#agregarGLISoft').css('display','none'); //Se esconde el botón de agregar
});

$(document).on('click','.borrarGliSoft',function(){
  $(this).parent().remove();

});

/**************************************************
  TODOS LOS EVENTOS DE GLI HARD
**************************************************/
function onInputHard() {
    var val = document.getElementById("inputHard").value;
    var opts = document.getElementById('hard').childNodes;
    console.log(opts);
    for (var i = 0; i < opts.length; i++) {
      if(opts[i].text === val){
        console.log('El id del option: ' + opts[i].id);
        $('#inputHard').attr('data-hard',opts[i].id);
      }
    }
}

//Evento de GLI hard seleccionado de la lista
$('#inputHard').bind('select', function(e) {
  if (e.timeStamp != 0) { //Si se selecciona de la lista (timeStamp == 0 es un dobleclick)
    $('#inputHard').prop("readonly", true); //Se bloquea el input
    $('#agregarGLIHard').css('display','inline'); //Se muestra el botón de agregar
  }
});

//Evento de tipeo en el input
$('#inputHard').bind('input', function() {
    datalist = $('#hard');
    console.log(datalist);
    //Lo escrito en el input
    var inputGli = $(this).val();

    if(inputGli.length > 2) {
        console.log('Entró al if');

        //Si el string del input es más largo que 2 caracteres busca en la BD
        $.get("glihards/buscarGliHardsPorNroArchivo/" + inputGli, function(data){

            $('#hard').empty();

            $.each(data.gli_hards, function(index, gli_hard) {
                var seleccionado = false;

                $('#listaGLIHard li').each(function(index){
                  console.log("De la lista de GLIHard: " + $(this));

                    if($(this).val() == gli_hard.id_gli_hard){
                      seleccionado = true;
                    }
                });

                if(!seleccionado) $('#hard').append($('<option>').text(gli_hard.nro_archivo).attr('id',gli_hard.id_gli_hard));
            });

        });
    }else {
      $('#hard').empty();
    }

    //Importante! Se quita el foco y se lo saca para que recargue la lista
    $(this).blur();
    $(this).focus();

});

//Botón Cancelar input GLI Hard
$('#cancelarGLIHard').click(function(){
    $('#inputHard').prop("readonly", false); //Se habilita el input
    $('#inputHard').val(''); //Se limpia el input
    $('#agregarGLIHard').css('display','none'); //Se esconde el botón de agregar
});

//Agregar GLI Hard
$('#agregarGLIHard').click(function(){
      //Crear un item de la lista
      $('#listaGLIHard')
        .append($('<li>')
             .text($('#inputHard').val())
             .val($('#inputHard').attr('data-hard'))
             .append($('<button>')
                  .addClass('btn').addClass('btn-danger').addClass('btn-xs').addClass('borrarGliHard')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-times')
                  )
             )
        );

      $('#inputHard').prop("readonly", false); //Se habilita el input
      $('#inputHard').val(''); //Se limpia el input
      $('#agregarGLIHard').css('display','none'); //Se esconde el botón de agregar
});

$(document).on('click','.borrarGliHard',function(){
  $(this).parent().remove();
});


//DATETIMEPICKER de las fechas
$(function () {
    $('#busqueda_fecha').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd MM yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2
    });

    $('#dtpFechaPase').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd / mm / yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2
    });

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
});

//Quitar el foco de los input cuando se cierra el modal
$('#modalExpediente').on('hidden.bs.modal', function () {
     $('#inputSoft').blur();
     $('#inputHard').blur();
})

//Agregar nueva disposicion en el modal
$('#btn-agregarDisposicion').click(function(){
  agregarDisposicion(null,true);
});

$(document).on('click','.borrarDisposicion',function(){
  $(this).parent().parent().remove();
});

//Mostrar modal para agregar nuevo Expediente
$('#btn-nuevo').click(function(){
  //Resetear formulario para llevar los datos
    $('#frmExpediente').trigger('reset');
    $('#columna > #disposicion').remove();
    $('#listaGLISoft > li').remove();
    $('#listaGLIHard > li').remove();
    $('#inputSoft').prop("readonly", false); //Se desbloquea el input

    //Se ocultan todos los alertas
    $('#alerta-nroExpediente').hide();
    $('#alerta-fechaPase').hide();
    $('#alerta-fechaInicio').hide();
    $('#alerta-destino').hide();
    $('#alerta-ubicacion').hide();
    $('#alerta-iniciador').hide();
    $('#alerta-remitente').hide();
    $('#alerta-concepto').hide();
    $('#alerta-tema').hide();
    $('#alerta-nroCuerpos').hide();
    $('#alerta-nroFolios').hide();
    $('#alerta-anexo').hide();
    $('#alerta-resolucion').hide();

  //Modificar los colores del modal
    $('.modal-title').text('NUEVO EXPEDIENTE');
    $('.modal-header').attr('style','background: #5cb85c');
    $('#btn-guardar').removeClass('btn-warning');
    $('#btn-guardar').addClass('btn-success');
    $('#btn-guardar').text('Crear EXPEDIENTE');

  $('#btn-guardar').val("nuevo");
  $('#frmExpediente').trigger('reset');
  //$('#alertaNombre').hide(); Esconcer los alertas!
  $('#modalExpediente').modal('show');
});

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){

    $('.modal-title').text('VER DETALLE EXPEDIENTE');
    $('.modal-header').attr('style','background: #ff9d2d');

      $('#btn-guardar').removeClass('btn-success');
      $('#btn-guardar').addClass('btn-warning');
      $('#btn-guardar').text('ACEPTAR');
    //Resetear formulario para llevar los datos
      $('#frmExpediente').trigger('reset');
      $('#columna > #disposicion').remove();
      $('#listaGLISoft > li').remove();
      $('#listaGLIHard > li').remove();
      $('#inputSoft').prop("readonly", false); //Se desbloquea el input
      //Se ocultan todos los alertas
      $('#alerta-nroExpediente').hide();
      $('#alerta-fechaPase').hide();
      $('#alerta-fechaInicio').hide();
      $('#alerta-destino').hide();
      $('#alerta-ubicacion').hide();
      $('#alerta-iniciador').hide();
      $('#alerta-remitente').hide();
      $('#alerta-concepto').hide();
      $('#alerta-tema').hide();
      $('#alerta-nroCuerpos').hide();
      $('#alerta-nroFolios').hide();
      $('#alerta-anexo').hide();
      $('#alerta-resolucion').hide();

    var id_expediente = $(this).val();

    $.get("expedientes/obtenerExpediente/" + id_expediente, function(data){
        console.log(data);
        $('#modalExpediente').modal('show');
    });
});

//Mostrar modal con los datos del Casino cargados
$(document).on('click','.modificar',function(){
  //Modificar los colores del modal
    $('.modal-title').text('MODIFICAR EXPEDIENTE');
    $('.modal-header').attr('style','background: #ff9d2d');
    $('#btn-guardar').removeClass('btn-success');
    $('#btn-guardar').addClass('btn-warning');
    $('#btn-guardar').text('Modificar EXPEDIENTE');

  //Resetear formulario para llevar los datos
    $('#frmExpediente').trigger('reset');
    $('#columna > #disposicion').remove();
    $('#listaGLISoft > li').remove();
    $('#listaGLIHard > li').remove();
    $('#inputSoft').prop("readonly", false); //Se desbloquea el input
    //Se ocultan todos los alertas
    $('#alerta-nroExpediente').hide();
    $('#alerta-fechaPase').hide();
    $('#alerta-fechaInicio').hide();
    $('#alerta-destino').hide();
    $('#alerta-ubicacion').hide();
    $('#alerta-iniciador').hide();
    $('#alerta-remitente').hide();
    $('#alerta-concepto').hide();
    $('#alerta-tema').hide();
    $('#alerta-nroCuerpos').hide();
    $('#alerta-nroFolios').hide();
    $('#alerta-anexo').hide();
    $('#alerta-resolucion').hide();

    var id_expediente = $(this).val();

    $.get("expedientes/obtenerExpediente/" + id_expediente, function(data){
        console.log(data);
        //Setea el casino en el select
        $('#selectCasinos').val(data.casino.id_casino);

        $('#id_expediente').val(data.expediente.id_expediente);

        //Acá llenar todos los campos
        $('#nro_exp_org').val(data.expediente.nro_exp_org);
        $('#nro_exp_interno').val(data.expediente.nro_exp_interno);
        $('#nro_exp_control').val(data.expediente.nro_exp_control);

        //Acá va el datetimepicker!
        var fecha_pase = data.expediente.fecha_pase.split('-');
        $('#fecha_pase').val(fecha_pase[2] + " / " + fecha_pase[1] + " / " + fecha_pase[0]);

        if(data.expediente.fecha_iniciacion != null){
          var fecha_inicio = data.expediente.fecha_iniciacion.split('-');
          $('#fecha_inicio').val(fecha_inicio[2] + " / " + fecha_inicio[1] + " / " + fecha_inicio[0]);
        }

        $('#remitente').val(data.expediente.remitente);
        $('#concepto').val(data.expediente.concepto);
        $('#iniciador').val(data.expediente.iniciador);
        $('#concepto').val(data.expediente.concepto);
        $('#tema').val(data.expediente.tema);
        $('#ubicacion').val(data.expediente.ubicacion_fisica);
        $('#destino').val(data.expediente.destino);
        $('#nro_cuerpos').val(data.expediente.nro_cuerpos);
        $('#nro_folios').val(data.expediente.nro_folios);
        $('#anexo').val(data.expediente.anexo);

        if(data.resolucion != null){
          $('#nro_resolucion').val(data.resolucion.nro_resolucion);
          $('#nro_resolucion_anio').val(data.resolucion.nro_resolucion_anio);
        }

        for(var index=0; index<data.disposiciones.length; index++){
          agregarDisposicion(data.disposiciones[index],true);
        }

        for(var index=0; index<data.gli_softs.length; index++){
          $('#listaGLISoft')
            .append($('<li>')
                 .text(data.gli_softs[index].nro_archivo)
                 .val(data.gli_softs[index].id_gli_soft)
                 .append($('<button>')
                      .addClass('btn').addClass('btn-danger').addClass('btn-xs').addClass('borrarGliSoft')
                      .append($('<i>')
                          .addClass('fa').addClass('fa-times')
                      )
                 )
            );
        }

        for(var index=0; index<data.gli_hards.length; index++){
          $('#listaGLIHard')
            .append($('<li>')
                 .text(data.gli_hards[index].nro_archivo)
                 .val(data.gli_hards[index].id_gli_hard)
                 .append($('<button>')
                      .addClass('btn').addClass('btn-danger').addClass('btn-xs').addClass('borrarGliHard')
                      .append($('<i>')
                          .addClass('fa').addClass('fa-times')
                      )
                 )
            );
        }

        //Se asigna al botón el valor "MODIFICAR" y se muestra
        $('#btn-guardar').val("modificar");
        $('#modalExpediente').modal('show');
    });

    // Esconder todos los alertas
    //$('#alertaNombre').hide();
});

//Borrar Casino y remover de la tabla
$(document).on('click','.eliminar',function(){
    //Cambiar colores modal
    $('.modal-title').text('ATENCIÓN');
    $('.modal-header').removeAttr('style');

    var id_expediente = $(this).val();
    $('#btn-eliminarModal').val(id_expediente);
    $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
    var id_expediente = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "expedientes/eliminarExpediente/" + id_expediente,
        success: function (data) {
          //Remueve de la tabla
          $('#expediente' + id_expediente).remove();
          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

//Cuando aprieta guardar en el modal de Nuevo/Modificar expediente
$('#btn-guardar').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    var fecha_pase = $('#fecha_pase').val().split(" / ");
    var fecha_iniciacion;
    var fecha_inicio;

    if($('#fecha_inicio').val() == ''){
      fecha_iniciacion = '';
    }else {
      fecha_inicio = $('#fecha_inicio').val().split(" / ");
      fecha_iniciacion = fecha_inicio[2]+"-"+fecha_inicio[1]+"-"+fecha_inicio[0];
    }

    var resolucion = null;
    if($('#nro_resolucion').val() != '' || $('#nro_resolucion_anio').val() != ''){
      var resolucion = {
        nro_resolucion: $('#nro_resolucion').val(),
        nro_resolucion_anio: $('#nro_resolucion_anio').val(),
      }
    }

    var disposiciones = [];
    $('#columna #disposicion').each(function(){
        var disposicion = {
          nro_disposicion: $(this).find('#nro_disposicion').val(),
          nro_disposicion_anio: $(this).find('#nro_disposicion_anio').val(),
        }
        disposiciones.push(disposicion);
    });

    var gliSofts = [];
    $('#listaGLISoft li').each(function(){
      var gliSoft = {
        id_gli_soft: $(this).val(),
      }
      gliSofts.push(gliSoft);
    });

    var gliHards = [];
    $('#listaGLIHard li').each(function(){
      var gliHard = {
        id_gli_hard: $(this).val(),
      }
      gliHards.push(gliHard);
    });

    var formData = {
      //Crear objeto JSON para llevar a BASE
      nro_exp_org: $('#nro_exp_org').val(),
      nro_exp_interno: $('#nro_exp_interno').val(),
      nro_exp_control: $('#nro_exp_control').val(),
      id_casino: $('#selectCasinos').val(),

      fecha_pase: fecha_pase[2]+"-"+fecha_pase[1]+"-"+fecha_pase[0],
      fecha_iniciacion: fecha_iniciacion,

      remitente: $('#remitente').val(),
      concepto: $('#concepto').val(),
      iniciador: $('#iniciador').val(),
      tema: $('#tema').val(),
      ubicacion_fisica: $('#ubicacion').val(),
      destino: $('#destino').val(),
      nro_cuerpos: $('#nro_cuerpos').val(),
      nro_folios: $('#nro_folios').val(),
      anexo: $('#anexo').val(),

      resolucion: resolucion,
      disposiciones: disposiciones,

      gli_softs: gliSofts,
      gli_hards: gliHards,
    }



    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = 'expedientes/guardarExpediente';
    var id_expediente = $('#id_expediente').val();

    if (state == "modificar"){
      var formData = {
        id_expediente: $('#id_expediente').val(),

        //Crear objeto JSON para llevar a BASE
        nro_exp_org: $('#nro_exp_org').val(),
        nro_exp_interno: $('#nro_exp_interno').val(),
        nro_exp_control: $('#nro_exp_control').val(),
        id_casino: $('#selectCasinos').val(),

        fecha_pase: fecha_pase[2]+"-"+fecha_pase[1]+"-"+fecha_pase[0],
        fecha_iniciacion: fecha_iniciacion,

        remitente: $('#remitente').val(),
        concepto: $('#concepto').val(),
        iniciador: $('#iniciador').val(),
        tema: $('#tema').val(),
        ubicacion_fisica: $('#ubicacion').val(),
        destino: $('#destino').val(),
        nro_cuerpos: $('#nro_cuerpos').val(),
        nro_folios: $('#nro_folios').val(),
        anexo: $('#anexo').val(),

        resolucion: resolucion,
        disposiciones: disposiciones,

        gli_softs: gliSofts,
        gli_hards: gliHards,
      }
      url = 'expedientes/modificarExpediente';
    }

    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            console.log(data);
            var ubicacion;
            var fecha_iniciacion;

            if (data.expediente.ubicacion_fisica == null) ubicacion = '';
            else ubicacion = data.expediente.ubicacion_fisica;

            if(data.expediente.fecha_iniciacion == null) fecha_iniciacion = '';
            else fecha_iniciacion = data.expediente.fecha_iniciacion;

            if (state == "nuevo"){ //Si está agregando agrega una fila con el nuevo expediente
              $('#tablaExpedientes tbody')
                  .append($('<tr>')
                      .attr('id','expediente' + data.expediente.id_expediente)
                      .append($('<td>')
                          .addClass('col-xs-2')
                          .text(data.expediente.nro_exp_org + '-' + data.expediente.nro_exp_interno + '-' + data.expediente.nro_exp_control)
                      )
                      .append($('<td>')
                          .addClass('col-xs-2')
                          .text(fecha_iniciacion)
                      )
                      .append($('<td>')
                          .addClass('col-xs-3')
                          .text(ubicacion)
                      )
                      .append($('<td>')
                          .addClass('col-xs-2')
                          .text(data.casino.nombre)
                      )
                      .append($('<td>')
                          .addClass('col-xs-3')
                          .addClass('accionesTD')
                          .append($('<button>')
                              .append($('<i>')
                                  .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                              )
                              .append($('<span>').text(' VER MÁS'))
                              .addClass('btn').addClass('btn-info').addClass('detalle')
                              .attr('value',data.expediente.id_expediente)
                          )
                          .append($('<span>').text(' '))
                          .append($('<button>')
                              .append($('<i>')
                                  .addClass('fa').addClass('fa-fw').addClass('fa-pencil')
                              )
                              .append($('<span>').text(' MODIFICAR'))
                              .addClass('btn').addClass('btn-warning').addClass('modificar')
                              .attr('value',data.expediente.id_expediente)
                          )
                          .append($('<span>').text(' '))
                          .append($('<button>')
                              .append($('<i>')
                                  .addClass('fa')
                                  .addClass('fa-fw')
                                  .addClass('fa-trash')
                              )
                              .append($('<span>').text(' ELIMINAR'))
                              .addClass('btn').addClass('btn-danger').addClass('eliminar')
                              .attr('value',data.expediente.id_expediente)
                          )
                      )
                  )
            }else{ //Si está modificando reemplaza la fila con el expediente modificado
                $('#expediente' + id_expediente + ' > td').remove()
                $('#expediente' + id_expediente)
                    .append($('<td>')
                        .addClass('col-xs-2')
                        .text(data.expediente.nro_exp_org + '-' + data.expediente.nro_exp_interno + '-' + data.expediente.nro_exp_control)
                    )
                    .append($('<td>')
                        .addClass('col-xs-2')
                        .text(fecha_iniciacion)
                    )
                    .append($('<td>')
                        .addClass('col-xs-3')
                        .text(ubicacion)
                    )
                    .append($('<td>')
                        .addClass('col-xs-2')
                        .text(data.casino.nombre)
                    )
                    .append($('<td>')
                        .addClass('col-xs-3')
                        .addClass('accionesTD')
                        .append($('<button>')
                            .append($('<i>')
                                .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                            )
                            .append($('<span>').text(' VER MÁS'))
                            .addClass('btn').addClass('btn-info').addClass('detalle')
                            .attr('value',data.expediente.id_expediente)
                        )
                        .append($('<span>').text(' '))
                        .append($('<button>')
                            .append($('<i>')
                                .addClass('fa').addClass('fa-fw').addClass('fa-pencil')
                            )
                            .append($('<span>').text(' MODIFICAR'))
                            .addClass('btn').addClass('btn-warning').addClass('modificar')
                            .attr('value',data.expediente.id_expediente)
                        )
                        .append($('<span>').text(' '))
                        .append($('<button>')
                            .append($('<i>')
                                .addClass('fa')
                                .addClass('fa-fw')
                                .addClass('fa-trash')
                            )
                            .append($('<span>').text(' ELIMINAR'))
                            .addClass('btn').addClass('btn-danger').addClass('eliminar')
                            .attr('value',data.expediente.id_expediente)
                        )
                    )
            }

            $('#frmExpediente').trigger("reset");
            $('#modalExpediente').modal('hide')
        },
        error: function (data) {
            var response = JSON.parse(data.responseText);
            // $('#alertaNombre').hide();
            // $('#alertaNombre span').text("");
            //
            // $('#alertaNombre span').text(response.nombre[0]);
            // $('#alertaNombre').show();
            console.log('Error:', data);

            //Ocultar todos los alertas
            $('#alerta-nroExpediente').hide();
            $('#alerta-fechaPase').hide();
            $('#alerta-fechaInicio').hide();
            $('#alerta-destino').hide();
            $('#alerta-ubicacion').hide();
            $('#alerta-iniciador').hide();
            $('#alerta-remitente').hide();
            $('#alerta-concepto').hide();
            $('#alerta-tema').hide();
            $('#alerta-nroCuerpos').hide();
            $('#alerta-nroFolios').hide();
            $('#alerta-anexo').hide();
            $('#alerta-resolucion').hide();

            //Si hay algun campo vacio en nro_exp
            var nro_exp_org_vacio = typeof response.nro_exp_org != "undefined";
            var nro_exp_interno_vacio = typeof response.nro_exp_interno != "undefined";
            var nro_exp_control_vacio = typeof response.nro_exp_control != "undefined";

            if (nro_exp_org_vacio || nro_exp_interno_vacio || nro_exp_control_vacio) {
                $('#alerta-nroExpediente span').text('El número de expediente tiene campos vacios');
                $('#alerta-nroExpediente').show();
            }
            if (typeof response.fecha_pase != "undefined") {
                $('#alerta-fechaPase span').text(response.fecha_pase[0]);
                $('#alerta-fechaPase').show();
            }
            if (typeof response.fecha_inicio != "undefined") {
                $('#alerta-fechaInicio span').text(response.fecha_inicio[0]);
                $('#alerta-fechaInicio').show();
            }
            if (typeof response.destino != "undefined") {
                $('#alerta-destino span').text(response.destino[0]);
                $('#alerta-destino').show();
            }
            if (typeof response.ubicacion_fisica != "undefined") {
                $('#alerta-ubicacion span').text(response.ubicacion_fisica[0]);
                $('#alerta-ubicacion').show();
            }
            if (typeof response.iniciador != "undefined") {
                $('#alerta-iniciador span').text(response.iniciador[0]);
                $('#alerta-iniciador').show();
            }
            if (typeof response.remitente != "undefined") {
                $('#alerta-remitente span').text(response.remitente[0]);
                $('#alerta-remitente').show();
            }
            if (typeof response.concepto != "undefined") {
                $('#alerta-concepto span').text(response.concepto[0]);
                $('#alerta-concepto').show();
            }
            if (typeof response.tema != "undefined") {
                $('#alerta-tema span').text(response.tema[0]);
                $('#alerta-tema').show();
            }
            if (typeof response.nro_cuerpos != "undefined") {
                $('#alerta-nroCuerpos span').text(response.nro_cuerpos[0]);
                $('#alerta-nroCuerpos').show();
            }
            if (typeof response.nro_folios != "undefined") {
                $('#alerta-nroFolios span').text(response.nro_folios[0]);
                $('#alerta-nroFolios').show();
            }
            if (typeof response.anexo != "undefined") {
                $('#alerta-anexo span').text(response.anexo[0]);
                $('#alerta-anexo').show();
            }
            if (typeof response["resolucion.nro_resolucion"] != "undefined") {
                $('#alerta-resolucion span').text(response["resolucion.nro_resolucion"][0]);
                $('#alerta-resolucion').show();
            }
            if (typeof response["resolucion.nro_resolucion_anio"] != "undefined") {
                $('#alerta-resolucion span').text(response["resolucion.nro_resolucion_anio"][0]);
                $('#alerta-resolucion').show();
            }

        }
    });
});


//Busqueda
$('#btn-buscar').click(function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var formData = {
    nro_admin: $('#busqueda_nro_maquina').val(),
    id_tipo_maquina: $('#busqueda_tipo_maquina').val(),
    id_tipo_origen: $('#busqueda_tipo_origen').val(),
    id_casino: $('#busqueda_casino').val(),
    fecha_relevamiento: $('#busqueda_fecha').val(),
    tipo_movimiento: $('#busqueda_tipo_movimiento').val(),

  }

  $.ajax({
      type: 'POST',
      url: 'movimientos/buscarMovimientos',
      data: formData,
      dataType: 'json',
      success: function (data) {
          console.log(data);
          var cantidad = data.expedientes.length;
          switch(cantidad){
            case 0:
              var titulo = "No se encontró ningún Expediente";
              break;
            case 1:
              var titulo = "Se encontró 1 Expediente";
              break;
            default:
              var titulo = "Se encontraron " + cantidad + " Expedientes";
          }
          $('#tituloTabla').text(titulo);
          $('#cuerpoTabla').empty();

          for (var i = 0; i < cantidad; i++) {
            var filaExpediente = generarFilaTabla(data.expedientes[i]);
            $('#tablaExpedientes tbody').append(filaExpediente)
          }
          // $('[data-toggle="tooltip"]').tooltip({
          //     trigger : 'hover'
          // })
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});

    function generarFilaTabla(expediente){
      var fila = $(document.createElement('tr'));
      fila.attr('id','expediente' + expediente.id_expediente)
          .append($('<td>')
              .addClass('col-xs-2')
              .text(expediente.nro_exp_org + '-' + expediente.nro_exp_interno + '-' + expediente.nro_exp_control)
          )
          .append($('<td>')
              .addClass('col-xs-2')
              .text(expediente.fecha_iniciacion)
          )
          .append($('<td>')
              .addClass('col-xs-3')
              .text(expediente.ubicacion_fisica)
          )
          .append($('<td>')
              .addClass('col-xs-2')
              .text(expediente.nombre_casino)
          )
          .append($('<td>')
              .addClass('col-xs-3')
              .addClass('accionesTD')
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                  )
                  .append($('<span>').text(' VER MÁS'))
                  .addClass('btn').addClass('btn-info').addClass('detalle')
                  .attr('value',expediente.id_expediente)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa').addClass('fa-fw').addClass('fa-pencil')
                  )
                  .append($('<span>').text(' MODIFICAR'))
                  .addClass('btn').addClass('btn-warning').addClass('modificar')
                  .attr('value',expediente.id_expediente)
              )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash')
                  )
                  .append($('<span>').text(' ELIMINAR'))
                  .addClass('btn').addClass('btn-danger').addClass('eliminar')
                  .attr('value',expediente.id_expediente)
              )
          )
        return fila;
    }

    function habilitarControles(valor){
      $('#nro_exp_org').prop('readonly',!valor);
      $('#nro_exp_interno').prop('readonly',!valor);
      $('#nro_exp_control').prop('readonly',!valor);
      $('#selectCasinos').prop('disabled',!valor);
      $('#fecha_pase').prop('readonly',!valor);
      $('#fecha_inicio').prop('readonly',!valor);
      $('#destino').prop('readonly',!valor);
      $('#ubicacion').prop('readonly',!valor);
      $('#iniciador').prop('readonly',!valor);
      $('#remitente').prop('readonly',!valor);
      $('#concepto').prop('readonly',!valor);
      $('#tema').prop('readonly',!valor);
      $('#nro_cuerpos').prop('readonly',!valor);
      $('#nro_folios').prop('readonly',!valor);
      $('#anexo').prop('readonly',!valor);
      $('#nro_resolucion').prop('readonly',!valor);
      $('#nro_resolucion_anio').prop('readonly',!valor);

      if(valor){// nuevo y modificar
        $('#btn-agregarDisposicion').show();
        $('#btn-guardar').prop('disabled',false).show();
        $('#btn-guardar').css('display','inline-block');
      }
      else{// ver detalle
        $('#btn-agregarDisposicion').hide();
        $('#btn-guardar').prop('disabled',true).hide();
        $('#btn-guardar').css('display','none');
      }
    }

    function limpiarModal(){
      $('#frmExpediente').trigger('reset');
      $('#columna > #disposicion').remove();
      $('#id_expediente').val(0);
      limpiarAlertas();
    }

    function limpiarAlertas(){
      $('.alertaTabla').remove();
    }

    function agregarDisposicion(disposicion, editable){
      var id_disposicion = ((disposicion != null) ? disposicion.id_disposicion: null);
      var nro_disposicion = ((disposicion != null) ? disposicion.nro_disposicion: null);
      var nro_disposicion_anio = ((disposicion != null) ? disposicion.nro_disposicion_anio: null);

      $('#columna')
          .append($('<div>')
              .addClass('row')
              .css('margin-bottom','15px')
              .addClass('Disposicion')
              .attr('id_disposicion',id_disposicion)
              .append($('<div>')
                  .addClass('col-xs-5')
                  .css('padding-right','0px')
                  .append($('<input>')
                      .attr('id','nro_disposicion')
                      .attr('type','text')
                      .addClass('form-control')
                      .val(nro_disposicion)
                  )
              )
              .append($('<div>')
                  .addClass('col-xs-5')
                  .css('padding-right','0px')
                  .append($('<input>')
                      .attr('id','nro_disposicion_anio')
                      .attr('type','text')
                      .addClass('form-control')
                      .val(nro_disposicion_anio)
                  )
              )
          )

          if(editable){
            $('#columna .Disposicion:last')
                  .append($('<div>')
                  .addClass('col-xs-2')
                  .append($('<button>')
                      .addClass('borrarDisposicion')
                      .addClass('btn')
                      .addClass('btn-danger')
                      .addClass('btn-xs')
                      .css('margin-top','6px')
                      .attr('type','button')
                      .append($('<i>')
                          .addClass('fa')
                          .addClass('fa-times')
                      )
                  )
                )
          }
    }
