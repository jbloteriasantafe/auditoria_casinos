$(document).ready(function() {
    $('#barraMesas').attr('aria-expanded','true');
    $('#mesasPanio').removeClass();
    $('#mesasPanio').addClass('subMenu1 collapse in');
    $('.tituloSeccionPantalla').text('Gestionar Cierres y Aperturas');
    $('#opcAperturas').attr('style','border-left: 6px solid #185891; background-color: #131836;');
    $('#opcAperturas').addClass('opcionesSeleccionado');

    $('#tipoArchivo').val('1');
    $('#selectCas').val('0');
    $('#casinoApertura').val('0');
    $('#selectJuego').val('0');
    $('#filtroMesa').val('');
    $('#B_fecha_filtro').val('');
    $('#B_fecha_cie').val('');
    $('#B_fecha_apert').val('');

    $('#mensajeExito').hide();
    $('#mensajeError').hide();

    $("#tablaCyA").tablesorter({
        headers: {
          3: {sorter:false}
        }
    });

    //$('#filtroMesa').generarDataList("usuarios/buscarUsuariosPorNombreYCasino/" + cas,'usuarios' ,'id_usuario','nombre',1,false);

  $('#hora_apertura').datetimepicker({
      language:  'es',
      autoclose: 1,
      todayHighlight: 1,
      format: 'HH:ii',
      pickerPosition: "bottom-left",
      startView: 1,
      minView: 0,
      maxView: 1,
      container: $('#modalCargaApertura')
    });

  $('#hora_cierre').datetimepicker({
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'HH:ii',
    pickerPosition: "bottom-left",
    startView: 1,
    minView: 0,
    maxView: 1,
    container: $('#modalCargaCierre')
  });

  $('#hora_CC').datetimepicker({
    language: 'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'HH:ii',
    pickerPosition: "bottom-left",
    startView: 1,
    minView: 0,
    maxView: 1,
    container: $('#modalCargaCierre')
  });
  //hora para modificar apertura
  $('#hora_apertura_modif').datetimepicker({
      language:  'es',
      autoclose: 1,
      todayHighlight: 1,
      format: 'HH:ii',
      pickerPosition: "bottom-left",
      startView: 1,
      minView: 0,
      maxView: 1,
      container: $('#modalModificarApertura')
    });

  $('#hora_In_cierre_modif').datetimepicker({
        language:  'es',
        autoclose: 1,
        todayHighlight: 1,
        format: 'HH:ii',
        pickerPosition: "bottom-left",
        startView: 1,
        minView: 0,
        maxView: 1,
        container: $('#modalModificarCierre')
  });

  $('#hora_cierre_modif').datetimepicker({
          language:  'es',
          autoclose: 1,
          todayHighlight: 1,
          format: 'HH:ii',
          pickerPosition: "bottom-left",
          startView: 1,
          minView: 0,
          maxView: 1,
          container: $('#modalModificarCierre')
        });

  $(function(){
    $('#dtpFechaApert').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
        container:$('#modalCargaApertura'),
      });
  });

  $(function(){
    $('#dtpfechaCierre').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
        container:$('#modalCargaCierre'),
      });
  });
  $(function(){
    $('#dtpFecha').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
      });
  });

  $('#modalCargaApertura #agregarMesa').click(clickAgregarMesa);

}); //fin document ready

//BUSCAR BUSCAR BUSCA buscar

$('#btn-buscarCyA').on('click', function(e){

  e.preventDefault();

  $('#cuerpoTablaCyA tr').remove();

  var fila = $(document.createElement('tr'));

  if($('#tipoArchivo').val()==2){ //elige ver CIERRES

    $('#tablaInicial').text('CIERRES');
        var formData= {
          fecha: $('#B_fecha_filtro').val(),
          nro_mesa: $('#filtroMesa').val(),
          id_juego:$('#selectJuego').val(),
          id_casino: $('#selectCas').val(),
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.ajax({
            type: 'POST',
            url: 'cierres/filtrosCierres',
            data: formData,
            dataType: 'json',

            success: function (data){
              $('#tablaResultados tbody tr').remove();

              for (var i = 0; i < data.cierre.length; i++) {

                  var fila=  generarFilaCierres(data.cierre[i]);
                  $('#cuerpoTablaCyA').append(fila);
              }

            },
            error: function(data){
            },
        })
      }

    else{
      $('#tablaInicial').text('APERTURAS');

        var formData = {
          fecha: $('#B_fecha_filtro').val(),
          nro_mesa: $('#filtroMesa').val(),
          id_juego:$('#selectJuego').val(),
          id_casino: $('#selectCas').val(),
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.ajax({
            type: 'POST',
            url: 'aperturas/filtrosAperturas',
            data: formData,
            dataType: 'json',

            success: function (data){
              $('#tablaResultados tbody tr').remove();
              for (var i = 0; i < data.apertura.length; i++) {
                  var fila=generarFilaAperturas(data.apertura[i]);
                  $('#cuerpoTablaCyA').append(fila);
              }
            },
            error: function(data){
            },
        })
      }
});

//APERTURAS APERTURAS APERTURAS APERTURAS Aperturas

$('#btn-generar-rel').on('click', function(e){

  e.preventDefault();


  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  var formData = {
    // id_ape: 1,
    // cantidad_maquinas: $('#cantidad_maquinas').val(),
    // cantidad_fiscalizadores: $('#cantidad_fiscalizadores').val(),
  }

  $.ajax({
      type: "POST",
      url: 'aperturas/generarRelevamiento',
      data: formData,
      dataType: 'json',

       beforeSend: function(data){

         $('#modalRelevamiento').modal('show');
         $('#modalRelevamiento').find('.modal-body').children('#iconoCarga').show();

      },
      success: function (data) {

          // $('#btn-buscar').click();
           $('#modalRelevamiento').modal('hide');

          var iframe;
          iframe = document.getElementById("download-container");
          if (iframe === null){
              iframe = document.createElement('iframe');
              iframe.id = "download-container";
              iframe.style.visibility = 'hidden';
              document.body.appendChild(iframe);
          }

          iframe.src = data.url_zip;
          console.log('7777',iframe);
      },
      error: function (data) {

         $('#modalRelevamiento').modal('hide');

      }
  });

});

$('#btn-cargar-apertura').on('click', function(e){
  $('#mensajeExitoCargaAp').hide();
  e.preventDefault();
  limpiarCargaApertura();
  $('#tablaMesasApert tbody tr').remove();

  $('#B_fecha_apert').val("").prop('disabled',false);

  ocultarErrorValidacion($('#B_fecha_apert'));
  ocultarErrorValidacion($('#horarioAp'));
  $('#mensajeErrorCargaAp').hide();
  $('#casinoApertura').val("0");

  $('.detallesCargaAp').hide();

  $('#modalCargaApertura').modal('show');

})

$(document).on('change','#casinoApertura',function(){

  limpiarCargaApertura();
  $('#tablaMesasApert tbody tr').remove();

  $('#columnaDetalle').hide();
  var fecha=$('#B_fecha_apert').val();
  var id_casino=$('#casinoApertura').val();
  $('#inputMesaApertura').generarDataList("mesas/obtenerMesasApertura/"  + id_casino + '/' + fecha ,'mesas','id_mesa_de_panio','nro_mesa',1,true);
  $('#fiscalizApertura').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
});

//presiona el botón dentro del modal de carga que confirma el casino
$('#confirmar').on('click',function(e){

  e.preventDefault();

    $('#btn-guardar-apertura').hide();
    if($('#casinoApertura').val() != 0 && $('#casinoApertura').val() != 4 && $('#B_fecha_apert').val().length !=0){

      $('.detallesCargaAp').show();

      var fecha = $('#B_fecha_apert').val();
      var id_casino=$('#casinoApertura').val();

      $('#inputMesaApertura').generarDataList("mesas/obtenerMesasApertura/"  + id_casino + '/' + fecha ,'mesas','id_mesa_de_panio','nro_mesa',1,true);

      $('#fiscalizApertura').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#B_fecha_apert').prop('disabled', true);

      $.get('usuarios/quienSoy',function(data){
        $('#cargador').val(data.usuario.nombre);
        $('#cargador').attr('data-cargador',data.usuario.id_usuario);
      })
      }
})

$(document).on('change','.inputApe',function(){


  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

        if($(this).val()!='' && $(this).val()!=0)
        {   var cantidad=$(this).val();
            $(this).attr('data-ingresado',cantidad);
            var valor=$(this).attr('data-valor');

            var subtotal=0;
            subtotal = Number($('#totalApertura').val());
            subtotal += Number(valor * cantidad);

            $('#totalApertura').val(subtotal);
        }

        if ($(this).val()==''|| $(this).val()==0) {
          var cantidad=0;
          var subtotal=0;
          subtotal = Number($('#totalApertura').val());
          subtotal -= Number($(this).attr('data-ingresado'));

          $('#totalApertura').val(subtotal);
        }
  }
  else{
        if($(this).val()!='' && $(this).val()!=0)
        {   var cantidad=$(this).val();
            var valor=$(this).attr('data-valor');
            var subtotal=0;

            subtotal = Number($('#totalApertura').val());
            subtotal -= Number(valor * $(this).attr('data-ingresado'));//resto antes de perderlo
            $('#totalApertura').val(subtotal);

            $(this).attr('data-ingresado',cantidad);

            var total=0;
            total = Number($('#totalApertura').val());
            total += Number(valor * cantidad);//valor nuevo

            $('#totalApertura').val(total);
        }
        if ($(this).val()==''|| $(this).val()==0) {
          var cantidad=0;
          var subtotal=0;
          subtotal = Number($('#totalApertura').val());
          subtotal -= Number(($(this).attr('data-ingresado')) * ($(this).attr('data-valor')));

          $('#totalApertura').val(subtotal);
        }
    }
});

$(document).on('click', '.btn_ver_mesa', function(e){
  e.preventDefault();

  $('#mensajeExitoCargaAp').hide();
  $('#mensajeErrorCargaAp').hide();
  $('#horarioAp').val("");
  $('#fiscalizApertura').setearElementoSeleccionado(0,"");

  $('#bodyMesas tr').css('background-color','#FFFFFF');
  $(this).parent().parent().css('background-color', '#E0E0E0');

  if($(this).attr('data-cargado') == true){

    $('#btn-guardar-apertura').hide();
  }
  else{
  $('#tablaCargaApertura tbody tr').remove();
  $('#totalApertura').val('');
  $('#btn-guardar-apertura').show();
  $('#btn-guardar-apertura').prop('disabled',false);

  $('#columnaDetalle').show();
  var id_mesa=$(this).attr('data-id');
  $('#id_mesa_ap').val(id_mesa);

  $.get('mesas/detalleMesa/' + id_mesa, function(data){

    $('#moneda').val(data.moneda.descripcion);

    for (var i = 0; i < data.fichas.length; i++) {

      var fila= $('#filaFichasClon').clone();
      fila.removeAttr('id');
      fila.attr('id', data.fichas[i].id_ficha);
      fila.find('.fichaVal').val(data.fichas[i].valor_ficha).attr('id',data.fichas[i].id_ficha);
      fila.find('.inputApe').attr('data-valor',data.fichas[i].valor_ficha).attr('data-ingresado', 0);
      fila.css('display', 'block');
      $('#tablaCargaApertura #bodyCApertura').append(fila);
     }
  })
  }
})

//presiona el tachito dentro del listado de mesas, la borra de la lista
$(document).on('click', '.btn_borrar_mesa', function(e){
  e.preventDefault();

  $(this).parent().parent().remove();

  limpiarCargaApertura();
  $('#columnaDetalle').hide();

});

//dentro del modal de carga apertura, presiona el botón guardar:
$('#btn-guardar-apertura').on('click', function(e){

  e.preventDefault();

  $(this).prop('disabled','true');

  $('#mensajeError').hide();
  $('#mensajeExito').hide();
  $('#recalcularApert').trigger('click');


    var id_mesa =$('#modalCargaApertura #id_mesa_ap').val();
    var fichas=[];
    var moneda= $('input[name=monedaApertura]:checked').val();
    var f= $('#bodyCApertura > tr');
    $.each(f, function(index, value){

      var valor={
        id_ficha: $(this).find('.fichaVal').attr('id'),
        cantidad_ficha: $(this).find('.inputApe').val()
      }
      if(valor.monto_ficha != "" ){
        fichas.push(valor);}

    })

      var formData= {
        id_cargador: $('#cargador').attr('data-cargador'),
        id_casino: $('#casinoApertura').val(),
        hora: $('#horarioAp').val(),
        fecha: $('#B_fecha_apert').val(),
        id_fiscalizador:$('#fiscalizApertura').obtenerElementoSeleccionado(),
        id_mesa_de_panio:id_mesa,
        total_pesos_fichas_a: $('#totalApertura').val(),
        fichas: fichas,
        id_moneda: moneda,

      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'aperturas/guardarApertura',
          data: formData,
          dataType: 'json',

          success: function (data){
            $('#columnaDetalle').hide();
            $('#btn-guardar-apertura').hide();
            $('#bodyMesas').find('#' + id_mesa).attr('data-cargado',true);
            $('#bodyMesas').find('#' + id_mesa).find('.btn_borrar_mesa').parent().remove();
            $('#bodyMesas').find('#' + id_mesa).find('.btn_ver_mesa').prop('disabled', true);
            $('#bodyMesas').find('#' + id_mesa).append($('<td>').addClass('col-xs-2').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50')));
            $('#mensajeExitoCargaAp').show();
            //$('#modalCargaApertura').modal('hide');
            // $('#mensajeExito h3').text('ÉXITO');
            // $('#mensajeExito p').text('Apertura guardada correctamente');
            // $('#mensajeExito').show();
          },
          error: function(data){
            $('#mensajeError h3').text('ERROR');
            console.log('ddd',data);
            var response = data.responseJSON;

            if(typeof response.fecha !== 'undefined'){
              mostrarErrorValidacion($('#B_fecha_apert'),response.fecha[0],false);
            }
            if(typeof response.hora !== 'undefined'){
              mostrarErrorValidacion($('#horarioAp'),response.hora[0],false);
            }
            if(typeof response.id_fiscalizador !== 'undefined'){
              $('#mensajeErrorCargaAp').show();
            }
            if(typeof response.total_pesos_fichas_a !== 'undefined'){
              $('#mensajeErrorCargaAp').show();
            }

          },
      })

});


//CIERRES CIERRES CIERRES CIERRES Cierres

$('#btn-cargar-cierre').on('click', function(e){

  e.preventDefault();

  limpiarCargaCierre();
  ocultarErrorValidacion($('#horario_ini_c'));
  ocultarErrorValidacion($('#juegoCierre'));
  ocultarErrorValidacion($('#horarioCie'));
  ocultarErrorValidacion($('#B_fecha_cie'));
  ocultarErrorValidacion($('#totalAnticipoCierre'));
  $('#mensajeFichasError').hide();

  $('#casinoCierre').val("0");
  $('.desplegable').hide();

  $('#btn-guardar-cierre').hide();
  $('#modalCargaCierre').modal('show');

})

//por si luego de confirmar cambia de nuevo el casino
$(document).on('change','#casinoCierre',function(){

  var id_casino=$('#casinoCierre').val();
  $('#inputMesaCierre').generarDataList("mesas/obtenerMesasCierre/" + id_casino,'mesas' ,'id_mesa_de_panio','nro_mesa',1);
  $('#juegoCierre').generarDataList("mesas-juegos/obtenerJuegoPorCasino/" + id_casino,'juegos' ,'id_juego_mesa','nombre_juego',1);
  $('#fiscalizadorCierre').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
  $('#tablaCargaCierreF tbody tr').remove();
  $('#horario_ini_c').val("");
  $('#horarioCie').val("");
  $('#totalCierre').val("");
  $('#total_anticipos_c').val("");

});

$('#confirmarCierre').on('click',function(e){

  e.preventDefault();

    if($('#casinoCierre').val() != 0 && $('#casinoApertura').val() != 4 ){
      $('.desplegable').show();

      var id_casino=$('#casinoCierre').val();
      $('#inputMesaCierre').generarDataList("mesas/obtenerMesasCierre/" + id_casino,'mesas' ,'id_mesa_de_panio','nro_mesa',1);
      $('#juegoCierre').generarDataList("mesas-juegos/obtenerJuegoPorCasino/" + id_casino,'juegos' ,'id_juego_mesa','nombre_juego',1);
      $('#fiscalizadorCierre').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);


      $('#btn-guardar-cierre').show();

    }

})

$(document).on('change','#inputMesaCierre',function(){

  $('#tablaCargaCierreF tbody tr').remove();
  $('#totalCierre').val("");
  $('#total_anticipos_c').val("");
  var id_mesa = $('#inputMesaCierre').obtenerElementoSeleccionado();

   if(id_mesa != 0 && id_mesa != null){
      $.get('mesas/detalleMesa/' + id_mesa, function(data){

        //$('#moneda').val(data.moneda.descripcion);
        for (var i = 0; i < data.fichas.length; i++) {

          var fila=$(document.createElement('tr'));

          fila.attr('id', data.fichas[i].id_ficha)
              .append($('<td>')
              .addClass('col-md-6').addClass('fichaVal').attr('id',data.fichas[i].id_ficha)
              .append($('<input>').prop('readonly','true')
              .val(data.fichas[i].valor_ficha)))
              .append($('<td>')
              .addClass('col-md-6')
              .append($('<input>').addClass('inputCie').attr('id', 'input').val("")
              .attr('data-valor',data.fichas[i].valor_ficha).attr('data-ingresado', 0)))

          $('#bodyFichasCierre').append(fila);
         }

      })
  }
});

$(document).on('change','.inputCie',function(){

  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

    if($(this).val()!='' && $(this).val()!=0) //si se ingreso un valor diferente de 0
    {   var cantidad=$(this).val();
        $(this).attr('data-ingresado',cantidad);
        var valor=$(this).attr('data-valor');

        var subtotal=0;
        subtotal = Number($('#totalCierre').val());
        subtotal += Number(cantidad);
        $('#totalCierre').val(subtotal);}

    if ($(this).val()=='' || $(this).val()==0) { //si se ingresa el 0 o nada
      var cantidad=0;
      var subtotal=0;
      subtotal = Number($('#totalCierre').val());
      subtotal -= Number($(this).attr('data-ingresado') );
      $('#totalCierre').val(subtotal);
    }
  }
  else{
    if($(this).val()!='' && $(this).val()!=0){ //si se ingreso un valor diferente de 0
        var cantidad=$(this).val();
        var subtotal=0;
        //tomo el data ingresado anteriormente y lo resto al total antes de perderlo
        subtotal = Number($('#totalCierre').val());
        subtotal-=Number($(this).attr('data-ingresado'));
        $('#totalCierre').val(subtotal);

        $(this).attr('data-ingresado',cantidad);//cambio el data ingresado
      //  var valor=$(this).attr('data-valor');

        var total=0;

        total = Number($('#totalCierre').val());
        total += Number(cantidad);

        $('#totalCierre').val(total);}

    if ($(this).val()=='' || $(this).val()==0) { //si se ingresa el 0 o nada
          var cantidad=0;
          var subtotal=0;
          subtotal = Number($('#totalCierre').val());
          subtotal -= Number($(this).attr('data-ingresado') );
          $('#totalCierre').val(subtotal);
    }

  }

})


//dentro del modal de carga de cierre, presiona el botón guardar
$('#btn-guardar-cierre').on('click', function(e){

  e.preventDefault();

  $('#mensajeError').hide();
  $('#mensajeExito').hide();
  $('#recalcular').trigger('click');


    var fichas=[];
    var moneda= $('input[name=moneda]:checked').val();

    var f= $('#bodyFichasCierre > tr');
    $.each(f, function(index, value){
      var valor={
        id_ficha: $(this).find('.fichaVal').attr('id'),
        monto_ficha: $(this).find('.inputCie').val(),
        id_moneda: moneda,
      }
      if(valor.monto_ficha != "" ){
        fichas.push(valor);

      }
    })

      var formData= {
        fecha: $('#B_fecha_cie').val(),
        hora_inicio: $('#horario_ini_c').val(),
        hora_fin:$('#horarioCie').val(),
        id_fiscalizador: $('#fiscalizadorCierre').obtenerElementoSeleccionado(),
        id_casino: $('#casinoCierre').val(),
        id_juego_mesa: $('#juegoCierre').obtenerElementoSeleccionado(),
        total_pesos_fichas_c:$('#totalCierre').val(),
        total_anticipos_c:$('#totalAnticipoCierre').val(),
        id_mesa_de_panio:$('#inputMesaCierre').obtenerElementoSeleccionado(),
        fichas: fichas,
        id_moneda:moneda,

      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'cierres/guardar',
          data: formData,
          dataType: 'json',

          success: function (data){
            $('#modalCargaCierre').modal('hide');
            $('#mensajeExito h3').text('ÉXITO');
            $('#mensajeExito p').text('El Cierre se ha guardado correctamente');
            $('#mensajeExito').show();
          },
          error: function(data){
            $('#mensajeError h3').text('ERROR');

            var response = data.responseJSON;

            if(typeof response.fecha !== 'undefined'){
              mostrarErrorValidacion($('#B_fecha_cie'),response.fecha[0],false);
            }
            if(typeof response.hora_inicio !== 'undefined'){
              mostrarErrorValidacion($('#horario_ini_c'),response.hora_inicio[0],false);
            }
            if(typeof response.hora_fin !== 'undefined'){
              mostrarErrorValidacion($('#horarioCie'),response.hora_fin[0],false);
            }
            if(typeof response.id_fiscalizador !== 'undefined'){
              $('#mensajeFichasError').show();
            }
            if(typeof response.total_anticipos_c !== 'undefined'){
              mostrarErrorValidacion($('#totalAnticipoCierre'),response.total_anticipos_c[0],false);
            }
            if(typeof response.fichas !== 'undefined'){
              $('#mensajeFichasError').show();
            }
            if(typeof response.id_juego_mesa !== 'undefined'){
              $('#mensajeFichasError').show();
            }
            if(typeof response.id_mesa_de_panio !== 'undefined'){
              $('#mensajeFichasError').show();
            }

          },
      })

});


$(document).on('click', '.infoCyA', function(e) {

  e.preventDefault();

  //veo el data-tipo para ver si se trata de una apertura o de un cierres
  //y hago el get que corresponda
  var tipo=$(this).attr('data-tipo');

  if(tipo=='apertura'){

    $('#bodyFichasDetApert tr').remove();
    var id_apertura=$(this).val();

    $.get('aperturas/obtenerAperturas/' + id_apertura, function(data){
      console.log('pp',data.detalles);
      $('#modalDetalleApertura').modal('show');

      $('.mesa_det_apertura').text(data.mesa.nombre + ' - ' + data.moneda.descripcion);
      $('.fecha_det_apertura').text(data.apertura.fecha);
      $('.juego_det_apertura').text(data.mesa.nombre_juego);
      $('.hora_apertura_det').text(data.apertura.hora);
      $('.cargador_det_apertura').text(data.cargador.nombre);
      $('.fisca_det_apertura').text(data.fiscalizador.nombre);
      $('#totalAperturaDet').val(data.apertura.total_pesos_fichas_a);

      if(data.cargador!=null){
      $('.cargador_det_apertura').text(data.cargador.nombre);}

      for (var i = 0; i < data.detalles.length; i++) {

        var fila = $(document.createElement('tr'));

            fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(data.detalles[i].valor_ficha)))

              if(data.detalles[i].cantidad_ficha != null){
                fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(data.detalles[i].cantidad_ficha)));}
              else{
                fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(0)));
                }


        $('#bodyFichasDetApert').append(fila);
      }

    })
  }

  if(tipo=='cierre'){

    //limpiar porquerias
    $('#datosCierreFichas tr').remove();
    $('#datosCierreFichasApertura tr').remove();

    var id_cierre= $(this).val();

    $.get('cierres/obtenerCierres/' + id_cierre, function(data){

      $('#modalDetalleCierre').modal('show');

      $('.mesa_det_cierre').text(data.mesa.nombre + ' - ' + data.moneda.descripcion);
      $('.fecha_detalle_cierre').text(data.cierre.fecha);
      $('.juego_det_cierre').text(data.nombre_juego);
      $('.cargador_det_cierre').text(data.cargador.nombre);

      if(data.cierre.hora_fin != null){
        $('.hora_cierre_det').text(data.cierre.hora_fin);
      }
      else{
        $('.hora_cierre_det').text(' - ');

      }
      if(data.cierre.hora_inicio != null){
        $('.inicio_cierre_det').text(data.cierre.hora_inicio);
      }
      else{
        $('.inicio_cierre_det').text(' - ');

      }

      //creo la tabla de fichas de cierres
      for (var i = 0; i < data.detallesC.length; i++) {
        var fila = $(document.createElement('tr'));

            fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(data.detallesC[i].valor_ficha)))
            if(data.detallesC[i].monto_ficha != null){
                fila.append($('<td>')
                .addClass('col-xs-6')
                .append($('<h8>')
                .text(data.detallesC[i].monto_ficha)));}
            else{
              fila.append($('<td>')
              .addClass('col-xs-6')
              .append($('<h8>')
              .text(0)));
            }

        $('#datosCierreFichas').append(fila);
      }

      $('#total_detalle').val(data.cierre.total_pesos_fichas_c);
      if(data.cierre.total_anticipos_c != null){
        $('#anticipos_detalle').val(data.cierre.total_anticipos_c);
      }
      else{
        $('#anticipos_detalle').val(' - ');
      }

       for (var i = 0; i < data.detalleAP.length; i++) {
         var fila2 = $(document.createElement('tr'));

             fila2.append($('<td>')
                 .addClass('col-xs-6')
                 .append($('<h8>')
                 .text(data.detalleAP[i].valor_ficha).css('align','center')))
                 if(data.detalleAP[i].monto_ficha != null){
                 fila2.append($('<td>')
                 .addClass('col-xs-6')
                 .append($('<h8>')
                 .text(data.detalleAP[i].monto_ficha).css('align','center')));}
                 else{
                   fila2.append($('<td>')
                   .addClass('col-xs-6')
                   .append($('<h8>')
                   .text('0').css('align','center')));
                 }

         $('#datosCierreFichasApertura').append(fila2);
       }

        $('#totalA_det_cierre').val(data.apertura.total_pesos_fichas_a)
    })
  }
});

$(document).on('click', '.modificarCyA', function(e) {

  e.preventDefault();
  //verifico que tipo de archivo es: cierre o aperturas
  //en base a eso hago diferentes gets y uso diferentes modales.
  var tipo= $(this).attr('data-tipo');
  $('#modificar_apertura').hide();

  //APERTURA
  if(tipo =='apertura'){

    ocultarErrorValidacion($('#hs_apertura'));
    ocultarErrorValidacion($('#car_apertura'));
    ocultarErrorValidacion($('#fis_apertura'));
    $('#modificarFichasAp tr').remove();

    var id_apertura=$(this).val();
    //guardo el id para hacer el guardar despues
    $('#modalModificarApertura #id_apertura').val(id_apertura);

    $.get('aperturas/obtenerAperturas/' + id_apertura, function(data){

      var id_casino = data.casino.id_casino;
      $('.f_apertura').val(data.apertura.fecha);
      $('#fis_apertura').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#fis_apertura').setearElementoSeleccionado(data.fiscalizador.id_usuario, data.fiscalizador.nombre);
      $('.car_apertura').val(data.cargador.nombre);
      $('.cas_apertura').val( data.casino.nombre);
      $('#hs_apertura').val(data.apertura.hora);
      $('.j_apertura').val(data.juego.nombre_juego);
      $('.nro_apertura').text(data.mesa.nro_mesa);
      $("input[name='monedaModApe'][value='"+data.moneda.id_moneda+"']").prop('checked', true);


      for (var i = 0; i < data.detalles.length; i++) {
        var fila = $(document.createElement('tr'));

        fila.attr('id', data.detalles[i].id_ficha)
            .append($('<td>')
            .addClass('col-md-3').addClass('fichaVal').attr('id',data.detalles[i].id_ficha)
            .append($('<input>').prop('readonly','true')
            .val(data.detalles[i].valor_ficha).css('text-align','center')))

            if(data.detalles[i].cantidad_ficha != null){

              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modApertura').attr('id', 'input').val(data.detalles[i].cantidad_ficha).css('text-align','center')
                  .attr('data-valor',data.detalles[i].valor_ficha).attr('data-ingresado', data.detalles[i].cantidad_ficha)))
            }
            else{
              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modApertura').attr('id', 'input').val(0).css('text-align','center')
                  .attr('data-valor',data.detalles[i].valor_ficha).attr('data-ingresado', 0)))
            }


        $('#modificarFichasAp').append(fila);
      }
      var total = 0;

      $.each($('#modificarFichasAp tr'), function(index, value){

        var valor = $(this).find('.modApertura').attr('data-valor');
        var ingresado = $(this).find('.modApertura').attr('data-ingresado');

        total += Number(valor * ingresado);

        console.log('si', total);

        $('#totalModifApe').val(total);
      })

      $('#modalModificarApertura').modal('show');

    })
  }

  //CIERRE
  if(tipo=='cierre'){

    ocultarErrorValidacion($('#hs_cierre_cierre'));
    ocultarErrorValidacion($('#hs_inicio_cierre'));
    ocultarErrorValidacion($('#totalAnticipoModif'));
    ocultarErrorValidacion($('#fis_cierre'));

    $('#modificarFichasCie tr').remove();
    var id_cierre= $(this).val();
    $('#modalModificarCierre #id_cierre').val(id_cierre);


    $.get('cierres/obtenerCierres/' + id_cierre, function(data){

      var id_casino = data.casino.id_casino;

      $("input[name='monedaModCie'][value='"+data.moneda.id_moneda+"']").prop('checked', true);

      $('.f_cierre').text(data.cierre.fecha);
      $('#fis_cierre').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#fis_cierre').setearElementoSeleccionado(data.cargador.id_usuario, data.cargador.nombre);
      $('.cas_cierre').text( data.casino.nombre);
      $('#hs_cierre_cierre').val(data.cierre.hora_fin);
      $('#hs_inicio_cierre').val(data.cierre.hora_inicio);
      $('.j_cierre').text(data.nombre_juego);
      $('.nro_cierre').text(data.mesa.nro_mesa);
      $('#totalAnticipoModif').val(data.cierre.total_anticipos_c);
      $('#totalModifCie').val(data.cierre.total_pesos_fichas_c);

      for (var i = 0; i < data.detallesC.length; i++) {
        var fila = $(document.createElement('tr'));

        fila.attr('id', data.detallesC[i].id_ficha)
            .append($('<td>')
            .addClass('col-md-3').addClass('fichaVal').attr('id',data.detallesC[i].id_ficha)
            .append($('<input>').prop('readonly','true')
            .val(data.detallesC[i].valor_ficha).css('text-align','center')))

            if(data.detallesC[i].monto_ficha != null){

              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modCierre').attr('id', 'input').val(data.detallesC[i].monto_ficha).css('text-align','center')
                  .attr('data-valor',data.detallesC[i].valor_ficha).attr('data-ingresado', data.detallesC[i].monto_ficha)))
            }
            else{
              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modCierre').attr('id', 'input').val(0).css('text-align','center')
                  .attr('data-valor',data.detallesC[i].valor_ficha).attr('data-ingresado', 0)))
            }


        $('#modificarFichasCie').append(fila);
      }
      var total = 0;
      $('#modificarFichasCie tr').each(function(){

        var ingresado = $(this).find('.modCierre').attr('data-ingresado');

        total += Number(ingresado);

        $('#totalModifCie').val(total);
      })

      $('#modalModificarCierre').modal('show');

      })

  }
});

//detecta modificaciones en los inputs de modificacion de apertura
$(document).on('change','.modApertura',function(){

  $('#modificar_apertura').show();

    if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado
      if($(this).val()!=null && $(this).val()!=0)
      {   var cantidad=$(this).val();
          $(this).attr('data-ingresado',cantidad);
          var valor=$(this).attr('data-valor');

          var subtotal=0;
          subtotal = Number($('#totalModifApe').val());
          subtotal += Number(valor * cantidad);

          $('#totalModifApe').val(subtotal);
      }

      if ($(this).val()==null|| $(this).val()==0) {
        var cantidad=0;
        var subtotal=0;
        subtotal = Number($('#totalModifApe').val());
        subtotal -= Number($(this).attr('data-ingresado'));

        $('#totalModifApe').val(subtotal);
      }
    }
    else{
      if($(this).val()!=null && $(this).val()!=0)
      {   var cantidad=$(this).val();
          var valor=$(this).attr('data-valor');
          var ingresado=$(this).attr('data-ingresado');
          var subtotal = 0;

          subtotal = Number($('#totalModifApe').val());
          subtotal -= Number(valor * ingresado);//resto antes de perderlo
          $('#totalModifApe').val(subtotal);

          $(this).attr('data-ingresado',cantidad);

          var total=0;
          total = Number($('#totalModifApe').val());
          total += Number(valor * cantidad);//valor nuevo

          $('#totalModifApe').val(total);
      }
      if ($(this).val()=='' || $(this).val()==0) {
        var cantidad=0;
        var subtotal=0;
        subtotal = Number($('#totalModifApe').val());
        subtotal -= Number($(this).attr('data-ingresado'));

        $('#totalModifApe').val(subtotal);
      }
    }
})

//SI SE MODIFICA ALGUNO DE LOS CAMPOS DE FISCALIZADOR O HORA, SE MUESTRA EL BTN
$(document).on('change','#fis_apertura',function(){
  $('#modificar_apertura').show();
});

$(document).on('change','#hs_apertura',function(){
  $('#modificar_apertura').show();
});

//Guardar El modificarC
$('#modificar_apertura').on('click', function(e){
  e.preventDefault();

  $('#mensajeError').hide();
  $('#mensajeExito').hide();

    var fichas=[];
    var f= $('#modificarFichasAp > tr');
    var moneda= $('input[name=monedaModApe]:checked').val();


    $.each(f, function(index, value){

      var valor={
        id_ficha: $(this).find('.fichaVal').attr('id'),
        cantidad_ficha: $(this).find('.modApertura').val()
      }
      if(valor.cantidad_ficha != "" ){
        fichas.push(valor);}

    })

      var formData= {
        id_apertura:$('#modalModificarApertura #id_apertura').val(),
        hora: $('#hs_apertura').val(),
        id_fiscalizador:$('#fis_apertura').obtenerElementoSeleccionado(),
        total_pesos_fichas_a: $('#totalModifApe').val(),
        fichas: fichas,
        id_moneda: moneda,

      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'aperturas/modificarApertura',
          data: formData,
          dataType: 'json',

          success: function (data){

            $('#modalModificarApertura').modal('hide');
             $('#mensajeExito h3').text('ÉXITO');
             $('#mensajeExito p').text('Apertura guardada correctamente');
             $('#mensajeExito').show();
          },
          error: function(data){

            var response = data.responseJSON.errors;

            if(typeof response.hora !== 'undefined'){
              mostrarErrorValidacion($('#hs_apertura'),response.hora[0],false);
            }
            if(typeof response.id_fiscalizador !== 'undefined'){
              mostrarErrorValidacion($('#fis_apertura'),response.id_fiscalizador[0],false);
            }
            if(typeof response.total_pesos_fichas_a !== 'undefined'){
              $('#errorModificar').show();
            }
            if(typeof response.fichas !== 'undefined'){
              $('#errorModificar').show();
            }

          },
      })

});

//MODIFICAR CIERRE
//modifica el monto de alguna ficha
$(document).on('change','.modCierre',function(){

  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

    if($(this).val()!=null && $(this).val()!=0) //si se ingreso un valor diferente de 0
    {   var cantidad=$(this).val();
        $(this).attr('data-ingresado',cantidad);
        //var valor=$(this).attr('data-valor');

        var subtotal=0;
        subtotal = Number($('#totalCierre').val());
        subtotal += Number(cantidad);
        $('#totalModifCie').val(subtotal);}

    if ($(this).val()==null || $(this).val()==0) { //si se ingresa el 0 o nada
      var cantidad=0;
      var subtotal=0;
      subtotal = Number($('#totalModifCie').val());
      subtotal -= Number($(this).attr('data-ingresado') );
      $('#totalModifCie').val(subtotal);
    }
  }
  else{
    if($(this).val()!=null && $(this).val()!=0){ //si se ingreso un valor diferente de 0
        var cantidad=$(this).val();
        var subtotal=0;
        //tomo el data ingresado anteriormente y lo resto al total antes de perderlo
        subtotal = Number($('#totalModifCie').val());
        subtotal-=Number($(this).attr('data-ingresado'));
        $('#totalModifCie').val(subtotal);

        $(this).attr('data-ingresado',cantidad);//cambio el data ingresado
      //  var valor=$(this).attr('data-valor');

        var total=0;

        total = Number($('#totalModifCie').val());
        total += Number(cantidad);

        $('#totalModifCie').val(total);}

    if ($(this).val()=='' || $(this).val()==0) { //si se ingresa el 0 o nada
          var cantidad=0;
          var subtotal=0;
          subtotal = Number($('#totalModifCie').val());
          subtotal -= Number($(this).attr('data-ingresado') );
          $('#totalModifCie').val(subtotal);
    }

  }

})

//SI SE MODIFICA ALGUNO DE LOS CAMPOS DE FISCALIZADOR O HORA, SE MUESTRA EL BTN
$(document).on('change','#fis_cierre',function(){
  $('#modificar_cierre').show();
});

$(document).on('change','#hs_cierre_cierre',function(){
  $('#modificar_cierre').show();
});
$(document).on('change','#hs_inicio_cierre',function(){
  $('#modificar_cierre').show();
});
$(document).on('change','#totalAnticipoModif',function(){
  $('#modificar_cierre').show();
});

//Guardar El modificarC
$('#modificar_cierre').on('click', function(e){
  e.preventDefault();

  $('#mensajeError').hide();
  $('#mensajeExito').hide();

    var fichas=[];
    var moneda= $('input[name=monedaModCie]:checked').val();
    var f= $('#modificarFichasCie > tr');

    $.each(f, function(index, value){

      var valor={
        id_ficha: $(this).find('.fichaVal').attr('id'),
        monto_ficha: $(this).find('.modCierre').val()
      }
      if(valor.monto_ficha != "" ){
        fichas.push(valor);}

    })

      var formData= {
        id_cierre_mesa:$('#modalModificarCierre #id_cierre').val(),
        hora_inicio: $('#hs_inicio_cierre').val(),
        hora_fin: $('#hs_cierre_cierre').val(),
        id_fiscalizador:$('#fis_cierre').obtenerElementoSeleccionado(),
        total_pesos_fichas_a: $('#totalModifCie').val(),
        total_anticipos_c: $('#totalAnticipoModif').val(),
        fichas: fichas,
        id_moneda: moneda,

      }

      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      $.ajax({
          type: 'POST',
          url: 'cierres/modificarCierre',
          data: formData,
          dataType: 'json',

          success: function (data){

            $('#modalModificarCierre').modal('hide');
             $('#mensajeExito h3').text('ÉXITO');
             $('#mensajeExito p').text('Cierre guardado correctamente');
             $('#mensajeExito').show();
          },
          error: function(data){

            var response = data.responseJSON.errors;

            if(typeof response.hora_inicio !== 'undefined'){
              mostrarErrorValidacion($('#hs_inicio_cierre'),response.hora_inicio[0],false);
            }
            if(typeof response.hora_fin !== 'undefined'){
              mostrarErrorValidacion($('#hs_cierre_cierre'),response.hora_fin[0],false);
            }
            if(typeof response.id_fiscalizador !== 'undefined'){
              mostrarErrorValidacion($('#fis_cierre'),response.id_fiscalizador[0],false);
            }
            if(typeof response.total_pesos_fichas_a !== 'undefined'){
              $('#errorModificarCierre').show();
            }
            if(typeof response.total_anticipos_c !== 'undefined'){
              $('#errorModificarCierre').show();
            }
            if(typeof response.fichas !== 'undefined'){
              $('#errorModificarCierre').show();
            }

          },
      })

});


//botón validar dentro del listado de aperturas
$(document).on('click', '.validarCyA', function(e) {
  e.preventDefault();
  $('#mensajeErrorValApertura').hide();

  $('#mensajeExito').hide();

  limpiarModalValidar();

  var id_apertura=$(this).val();
  $('#validar').val(id_apertura);
  $('#validar').hide();
  $('#div_cierre').hide();

  $.get('aperturas/obtenerApValidar/' + id_apertura , function(data){

    $('.nro_validar').text(data.mesa.nro_mesa);
    $('.fechaAp_validar_aper').text(data.apertura.fecha);
    $('.j_validar').text(data.juego.nombre_juego);
    $('.cas_validar').text(data.casino.nombre);

    $('.hs_validar_aper').text(data.apertura.hora);
    $('.fis_validar_aper').text(data.fiscalizador.nombre);
    $('.car_validar_aper').text(data.cargador.nombre);
    $('.tipo_validar_aper').text(data.tipo_mesa.descripcion);
    $('.mon_validar_aper').text(data.moneda.descripcion);
    $('.mon_validar_aper').val(data.moneda.id_moneda);
    $('#total_aper_validar').val(data.apertura.total_pesos_fichas_a);

    for (var i = 0; i < data.fechas_cierres.length; i++) {
      $('#fechaCierreVal')
      .append($('<option>')
      .val(data.fechas_cierres[i].id_cierre_mesa)
      .text(data.fechas_cierres[i].fecha))
    }

  })

  $('#modalValidarApertura').modal('show');

});

//comparar, busca el cierre que se desea comparar
$(document).on('click','.comparar',function(){

  if($('#fechaCierreVal').val() != 0){

    $('#validar').show();
    var moneda=$('.mon_validar_aper').val();
    var apertura=$('#validar').val();
    var cierre=$('#fechaCierreVal').val();
    //{id_apertura}/{id_cierre}/{id_moneda}
    $.get('compararCierre/' + apertura + '/' + cierre + '/' + moneda, function(data){
      console.log('cierreNuevo', data);
      $('#div_cierre').show();

      // //datos cierre
      if(data.cierre == null){
        $('.hs_inicio_validar').text('-');
        $('.hs_cierre_validar').text('-');
        $('.f_validar').text('-');
        $('#anticipos_validar').val('-');
        $('#total_cierre_validar').val('-');
      }else {
        $('.hs_inicio_validar').text(data.cierre.hora_inicio);
        $('.hs_cierre_validar').text(data.cierre.hora_fin);
        $('.f_validar').text(data.cierre.fecha);
        $('#anticipos_validar').val(data.cierre.total_anticipos_c);
        $('#total_cierre_validar').val(data.cierre.total_pesos_fichas_c);
      }

      if(data.detalles_join.length > 0){

        for (var i = 0; i < data.detalles_join.length; i++) {

          var fila= $(document.createElement('tr'));

          fila.attr('id', data.detalles_join[i].id_ficha);


          //pregunto si hay detalle_cierre cargado
          if(data.detalles_join[i].id_detalle_cierre != null && data.detalles_join[i].monto_ficha!= null){
              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('v_id_ficha').addClass('cierre').text(data.detalles_join[i].valor_ficha).css('font-weight','bold'))
                  .append($('<td>')
                  .addClass('col-xs-3').addClass('v_monto_cierre').addClass('cierre').text(data.detalles_join[i].monto_ficha).css('font-weight','bold'));

            }else{
                fila.append($('<td>')
                    .addClass('col-xs-3').addClass('v_id_ficha').addClass('cierre').text(data.detalles_join[i].valor_ficha).css('font-weight','bold'))
                    .append($('<td>')
                    .addClass('col-xs-3').addClass('v_monto_cierre').addClass('cierre').text('0').css('font-weight','bold'))

              }

        //  pregunto si hay apertura cargada
            if(data.detalles_join[i].id_detalle_apertura != null && data.detalles_join[i].monto_ficha_apertura != null){

              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('v_monto_apertura').text(data.detalles_join[i].monto_ficha_apertura).css('font-weight','bold'))

            }
            else {
              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('v_monto_apertura').text('0').css('font-weight','bold').prop('readonly',true))

            }

            //agrego icono comparando valores
            var monto_apertura = fila.find('.v_monto_apertura').text();
            var monto_cierre = fila.find('.v_monto_cierre').text();
            console.log('montos',monto_apertura);
            if(monto_cierre == monto_apertura){
              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('.iconoValidacion')
                  .append($('<i>').addClass('fa fa-fw fa-check').css('color', '#66BB6A')));
            }else {
              fila.append($('<td>')
                  .addClass('col-xs-3').addClass('.iconoValidacion')
                  .append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F')));
            }

            $('#tablaValidar #validarFichas').append(fila);
          }
        }
    })
  }

});
//cuando cambia la fecha
$(document).on('change', '#fechaCierreVal', function(e) {

  e.preventDefault();

  var t=$('#fechaCierreVal').val();

  if(t==0){
    $('#validar').hide();
  }

  $('#tablaValidar tbody tr').remove();
  $('#div_cierre').hide();
  $('#anticipos_validar').val('-');
  $('#total_cierre_validar').val('-');

});

//botón validar dentro del modal
$(document).on('click', '#validar', function(e) {
  e.preventDefault();

  var id_apertura = $(this).val();

    var formData= {
      id_cierre:$('#fechaCierreVal').val(),
      id_apertura:id_apertura,
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: 'POST',
        url: 'aperturas/validarApertura',
        data: formData,
        dataType: 'json',

        success: function (data){

          $('#modalValidarApertura').modal('hide');
          $('#mensajeExito h3').text('ÉXITO');
          $('#mensajeExito p').text('Apertura Validada correctamente. ');
          $('#mensajeExito').show();
          $('#btn-buscarCyA').trigger('click');
        },
        error: function(data){

           var response = data.responseJSON.errors;

           if(typeof response.id_cierre !== 'undefined'){
             $('#mensajeErrorValApertura').show();
           }
          // if(typeof response.hora_fin !== 'undefined'){
          //   mostrarErrorValidacion($('#hs_cierre_cierre'),response.hora_fin[0],false);
          // }
        },
    })

});


//si es superusuario puede eliminarCyA
$(document).on('click','.eliminarCyA',function(e){

  var tipo = $(this).attr('data-tipo');
   var id=$(this).val();

  $('#cuerpoTablaCyA').find('#' + id).remove();

  if(tipo=='apertura'){
     $.get('aperturas/bajaApertura/' + id, function(data){
       $('#mensajeExito h3').text('ÉXITO');
       $('#mensajeExito p').text(' ');
       $('#mensajeExito').show();
     })
  }

  if(tipo=='cierre'){
       $.get('cierres/bajaCierre/' + id, function(data){
         $('#mensajeExito h3').text('ÉXITO');
         $('#mensajeExito p').text(' ');
          $('#mensajeExito').show();
       })
}
});



//dentro del modal de cargar aperturas, para agregar la mesa al listado
function clickAgregarMesa(e) {
  var id_mesa_panio = $('#inputMesaApertura').attr('data-elemento-seleccionado');


     $.get('http://' + window.location.host +"/mesas/detalleMesa/" + id_mesa_panio, function(data) {

       var fila= $(document.createElement('tr'));
       fila.attr('id', data.mesa.id_mesa_de_panio)
           .append($('<td>')
           .addClass('col-xs-4')
           .text(data.mesa.nro_mesa).css('border-right','2px solid #ccc')
         ).append($('<td>')
         .addClass('col-xs-4')
         .text(data.juego.nombre_juego))
         .append($('<td>')
         .addClass('col-xs-2')
         .append($('<span>').text(' '))
         .append($('<button>')
         .addClass('btn_ver_mesa').attr('data-id',data.mesa.id_mesa_de_panio).attr('data-cargado',false)
             .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-eye')
           )))
           .append($('<td>')
           .addClass('col-xs-2')
           .append($('<span>').text(' '))
           .append($('<button>')
           .addClass('btn_borrar_mesa').append($('<i>')
           .addClass('fas').addClass('fa-fw').addClass('fa-trash')
             )))

         $('#bodyMesas').append(fila);
      $('#inputMesaApertura').setearElementoSeleccionado(0 , "");


    });

}

//fc que generan la fila del listado principal:
function generarFilaAperturas(data){

    var fila = $('#moldeFilaCyA').clone();
    fila.removeAttr('id');
    fila.attr('id', data.id_apertura_mesa);

    fila.find('.L_fecha').text(data.fecha);
    fila.find('.L_juego').text(data.nombre_juego);
    fila.find('.L_mesa').text(data.nro_mesa);
    fila.find('.L_casino').text(data.nombre);
    if(data.id_estado_cierre == 3){
      fila.find('.L_estado').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50'));
    }else{
        fila.find('.L_estado').append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F'));
    }


    fila.find('.infoCyA').attr('data-tipo', 'apertura').val(data.id_apertura_mesa);
    fila.find('.modificarCyA').attr('data-tipo', 'apertura').val(data.id_apertura_mesa);
    fila.find('.validarCyA').attr('data-tipo', 'apertura').val(data.id_apertura_mesa);
    fila.find('.eliminarCyA').attr('data-tipo', 'cierre').val(data.id_apertura_mesa);
    if(data.id_estado_cierre == 3){
      fila.find('.validarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
      fila.find('.eliminarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
      fila.find('.modificarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
    }
    fila.css('display', 'block');

    return fila;

}

function generarFilaCierres(data){

    var fila = $('#moldeFilaCyA').clone();
    fila.removeAttr('id');
    fila.attr('id', data.id_cierre_mesa);

    fila.find('.L_fecha').text(data.fecha);
    fila.find('.L_juego').text(data.nombre_juego);
    fila.find('.L_mesa').text(data.nro_mesa);
    fila.find('.L_casino').text(data.nombre);
    if(data.id_estado_cierre == 3){
      fila.find('.L_estado').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50').css('text-align','center'));
    }else{
        fila.find('.L_estado').append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F').css('text-align','center'));
    }

    //attr=data-tipo sirve para luego determinar qué get o post realizar
    //cuando se presionan, ya que se usa un mismo molde

    fila.find('.infoCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa);
    fila.find('.modificarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa);

    fila.find('.validarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
    fila.find('.eliminarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa);
    fila.css('display', 'block');

    if(data.id_estado_cierre == 3){
      fila.find('.validarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
      fila.find('.eliminarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
      fila.find('.modificarCyA').attr('data-tipo', 'cierre').val(data.id_cierre_mesa).hide();
    }
  return fila;

}

function limpiarCargaCierre(){

  $('#B_fecha_cie').val("");
  $('#inputMesaCierre').setearElementoSeleccionado('0',"");
  $('#juegoCierre').setearElementoSeleccionado('0',"");
  $('#totalCierre').val('');
  $('#totalAnticipoCierre').val('');
  $('#bodyFichasCierre tr').remove();
  $('#horarioCie').val('');
  $('#horario_ini_c').val('');
  $('#fiscalizadorCierre').setearElementoSeleccionado(0,"");

}

function limpiarCargaApertura(){

  $('#id_mesa_ap').setearElementoSeleccionado('0',"");
  $('#totalApertura').val('');
  $('#horarioAp').val('');
  $('#fiscalizApertura').setearElementoSeleccionado(0,"");
  $('#cargador').val('');
  $('#tablaCargaApertura tbody tr').remove();
  $('#mensajeExitoCargaAp').hide();

}

function limpiarModalValidar(){
  //$('#validarFichas tr').not('moldeValidar').remove();
  $('.nro_validar').text(' ');
  $('.j_validar_aper').text(' ');
  $('.j_validar').text(' ');
  $('.cas_validar').text(' ');
  $('.hs_inicio_validar').text(' ');
  $('.hs_cierre_validar').text(' ');
  $('.f_validar').text(' ');
  $('.hs_validar_aper').text(' ');
  $('.fis_validar_aper').text(' ');
  $('.car_validar_aper').text(' ');
  $('.tipo_validar_aper').text(' ');
  $('.mon_validar_aper').text(' ');
  $('#total_cierre_validar').val('');
  $('#total_aper_validar').val('');
  $('#anticipos_validar').val('');
  $('#fechaCierreVal option').not('.defecto').remove();
  $('#tablaValidar tbody tr').remove();

}
