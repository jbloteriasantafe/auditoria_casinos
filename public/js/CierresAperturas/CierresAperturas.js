$(document).ready(function() {
  $('#barraMesas').attr('aria-expanded','true');
  $('#mesasPanio').removeClass();
  $('#mesasPanio').addClass('subMenu1 collapse in');
  $('.tituloSeccionPantalla').text('Gestionar Cierres y Aperturas');
  $('#opcAperturas').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcAperturas').addClass('opcionesSeleccionado');
    $('[data-toggle="popover"]').popover();
    $(function () {
  $('[data-toggle="popover"]').popover()
})

    $('.tituloSeccionPantalla').hide();
    //$('.tituloSeccionPantalla').text('Gestionar Juegos');
    var fila= $('#filaFichasClon').clone();

    limpiarFiltrosApertura();
    limpiarFiltrosCierre();
    $('#cierreApertura').show();
    $('#cierreApertura').css('display','inline-block');

    $('#casinoApertura').val('0');
    $('#B_fecha_cie').val('');
    $('#B_fecha_apert').val('');

    $('#mensajeExito').hide();
    $('#mensajeError').hide();


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
      $('#dtpfechaCierreModif').datetimepicker({
            language:  'es',
            todayBtn:  1,
            autoclose: 1,
            todayHighlight: 1,
            format: 'yyyy-mm-dd',
            pickerPosition: "bottom-left",
            startView: 4,
            minView: 2,
            container:$('#modalModificarCierre'),
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
        $('#dtpFechaCierreFiltro').datetimepicker({
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


    //pestañas
    $(".tab_content").hide(); //Hide all content
  	$("ul.cierreApertura li:first").addClass("active").show(); //Activate first tab
  	$(".tab_content:first").show(); //Show first tab content

    $('#modalCargaApertura #agregarMesa').click(clickAgregarMesa);
    $('#modalCargaCierre #agregarMesaCierre').click(clickAgregarMesaCierre);
    $('#btn-buscarCyA').trigger('click',[1,10,'apertura_mesa.fecha','desc']);

}); //fin document ready


//PESTAÑAS
$("ul.cierreApertura li").click(function() {

    $("ul.cierreApertura li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content
                console.log(activeTab);
    if(activeTab == '#pant_cierres'){
      limpiarFiltrosCierre();
      $('#btn-buscar-cierre').trigger('click',[1,10,'cierre_mesa.fecha','desc']);
    }
    if(activeTab == '#pant_aperturas'){
      limpiarFiltrosApertura();
      $('#btn-buscar-cierre').trigger('click',[1,10,'cierre_mesa.fecha','desc']);
    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});

//BUSCAR cierres
$('#btn-buscar-cierre').on('click', function(e,pagina,page_size,columna,orden){
  e.preventDefault();
  var mesa=$('#filtroMesaCierre').val();

  if(mesa.length > 11){
    mostrarErrorValidacion($('#filtroMesaCierre'), 'La cantidad máxima permitida de Carateres es de 11');
  }
  else{
    ocultarErrorValidacion($('#filtroMesaCierre'));
    $('#cuerpoTablaCierre tr').remove();

      //Fix error cuando librería saca los selectores
      if(isNaN($('#herramientasPaginacion2').getPageSize())){
        var size = 10; // por defecto
      }else {
        var size = $('#herramientasPaginacion2').getPageSize();
      }

      var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
      var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion2').getCurrentPage();
      var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultadosCierres .activa').attr('cierre'),orden: $('#tablaResultadosCierres .activa').attr('estado')} ;

      if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
        var sort_by =  {columna: 'cierre_mesa.fecha',orden: 'desc'} ;

        //$('#tablaInicial th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
      }
          var formData= {
            fecha: $('#B_fecha_filtro_cierre').val(),
            nro_mesa: $('#filtroMesaCierre').val(),
            id_juego:$('#selectJuegoCierre').val(),
            id_casino: $('#selectCasCierre').val(),
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
              type: 'POST',
              url: 'cierres/filtrosCierres',
              data: formData,
              dataType: 'json',

              success: function (data){
                $('#herramientasPaginacion2').generarTitulo(page_number,page_size,data.cierre.total,clickIndiceCierres);
                $('#tablaResultadosCierres tbody tr').remove();

                for (var i = 0; i < data.cierre.data.length; i++) {

                    var fila=  generarFilaCierres(data.cierre.data[i]);
                    $('#cuerpoTablaCierre').append(fila);
                }
                $('#herramientasPaginacion2').generarIndices(page_number,page_size,data.cierre.total,clickIndiceCierres);
              },
              error: function(data){
              },
          })
        }
});

$('#btn-buscarCyA').on('click', function(e,pagina,page_size,columna,orden){

  e.preventDefault();
  var nro=$('#filtroMesa').val();

  if(nro.length > 11){
    mostrarErrorValidacion($('#filtroMesa'), 'La cantidad máxima permitida de Carateres es de 11');
  }
  else{
    ocultarErrorValidacion($('#filtroMesa'));

    $('#cuerpoTablaCyA tr').remove();

        if(isNaN($('#herramientasPaginacion').getPageSize())){
          var size = 10; // por defecto
        }else {
          var size = $('#herramientasPaginacion').getPageSize();
        }

        var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
        // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
        var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
        var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('apertura'),orden: $('#tablaResultados .activa').attr('estado')} ;

        if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
          var sort_by =  {columna: 'apertura_mesa.fecha',orden: 'desc'} ;

          //$('#tablaInicial th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
        }

        $('#tablaInicial').text('APERTURAS');

          var formData = {
            fecha: $('#B_fecha_filtro').val(),
            nro_mesa: $('#filtroMesa').val(),
            id_juego:$('#selectJuego').val(),
            id_casino: $('#selectCas').val(),
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
              type: 'POST',
              url: 'aperturas/filtrosAperturas',
              data: formData,
              dataType: 'json',

              success: function (data){
                $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.apertura.total,clickIndice);

                $('#tablaResultados tbody tr').remove();

                for (var i = 0; i < data.apertura.data.length; i++) {
                    var fila=generarFilaAperturas(data.apertura.data[i]);
                    $('#cuerpoTablaCyA').append(fila);
                }

                $('#herramientasPaginacion').generarIndices(page_number,page_size,data.apertura.total,clickIndice);
              },
              error: function(data){
              },
          })
      }
});

//detectar si se cierra un modal de carga, presiona buscar
$("#modalCargaCierre").on('hidden.bs.modal', function () {
    $('#btn-buscar-cierre').trigger('click');
  });

$("#modalCargaApertura").on('hidden.bs.modal', function () {
  $('#btn-buscarCyA').trigger('click',[1,10,'apertura_mesa.fecha','desc']);
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
         $('#modalRelevamiento').find('.modal-body').html('<div class="loading"><img src="/img/ajax-loader(1).gif" alt="loading" /><br>Un momento, por favor...</div>').css('text-align','center');
         $('#modalRelevamiento').modal('show');
         //$('#modalRelevamiento').find('.modal-body').children('#floatingCirclesG').show();

         //Si están cargados los datos para generar oculta el formulario y muestra el icono de carga
      //   if ($('#modalRelevamiento #casino option:selected').val() != "") {
      //       $('#modalRelevamiento').find('.modal-footer').children().hide();
      //       $('#modalRelevamiento').find('.modal-body').children().hide();
      //       $('#modalRelevamiento').find('.modal-body').children('#iconoCarga').show();
      //   }
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

        $('#modalErrorRelevamiento').modal('show');


      } //error
  }); //$.ajax

});

$('#btn-cargar-apertura').on('click', function(e){

  e.preventDefault();

  $('#mensajeExito').hide();

  limpiarCargaApertura();
    $('#cargador').val('');
  $('#tablaMesasApert tbody tr').remove();

  $('#B_fecha_apert').val("").prop('disabled',false);

  ocultarErrorValidacion($('#B_fecha_apert'));
  ocultarErrorValidacion($('#horarioAp'));
  ocultarErrorValidacion($('#casinoApertura'));

  $('#mensajeErrorCargaAp').hide();
  $('#casinoApertura').val("0");
  $('#mensajeExitoCargaAp').hide();
  $('.detallesCargaAp').hide();

  $('#btn-finalizar-apertura').hide();
  $('#modalCargaApertura').modal('show');

})

$(document).on('change','#casinoApertura',function(){

  limpiarCargaApertura();
  $('#cargador').val('');
  $('#tablaMesasApert tbody tr').remove();

  $('#columnaDetalle').hide();
  var fecha=$('#B_fecha_apert').val();
  var id_casino=$('#casinoApertura').val();
  $('#inputMesaApertura').generarDataList("mesas/obtenerMesasApertura/"  + id_casino ,'mesas','id_mesa_de_panio','nro_mesa',1,true);
  $('#fiscalizApertura').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
});

//presiona el botón dentro del modal de carga que confirma el casino
$('#confirmar').on('click',function(e){

  e.preventDefault();

    $('#btn-guardar-apertura').hide();
    if($('#casinoApertura').val() != 0 && $('#B_fecha_apert').val().length != 0){

      $('.detallesCargaAp').show();

      var fecha = $('#B_fecha_apert').val();
      var id_casino=$('#casinoApertura').val();

      $('#inputMesaApertura').generarDataList("mesas/obtenerMesasApertura/"  + id_casino ,'mesas','id_mesa_de_panio','nro_mesa',1,true);

      $('#fiscalizApertura').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#B_fecha_apert').prop('disabled', true);

      $.get('usuarios/quienSoy',function(data){
        console.log('quiensoy',data);
        $('#cargador').val(data.usuario.nombre);
        $('#cargador').attr('data-cargador',data.usuario.id_usuario);
      })
    }
    else{
      if($('#casinoCierre').val() == 0 ){
        mostrarErrorValidacion($('#casinoApertura'),'Campo Obligatorio',false);
      }
      console.log('ppp',$('#B_fecha_apert').val().length);

      if($('#B_fecha_apert').val().length == 0  ){
        mostrarErrorValidacion($('#B_fecha_apert'),'Campo Obligatorio',false);
      }
    }

})

$(document).on('change','.inputApe',function(){

  var num= Numeros($(this).val());

  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

        if(num != '' && num != 0)
        {   var cantidad=num;
            $(this).attr('data-ingresado',cantidad);
            var valor=$(this).attr('data-valor');

            var subtotal=0;
            subtotal = Number($('#totalApertura').val());
            subtotal += Number(valor * cantidad);

            $('#totalApertura').val(subtotal);
        }

        if (num ==''|| num ==0) {
          var cantidad=0;
          var subtotal=0;

          $(this).attr('data-ingresado',cantidad);

          subtotal = Number($('#totalApertura').val());
          subtotal -= Number(($(this).attr('data-ingresado')) * ($(this).attr('data-valor')));

          $('#totalApertura').val(subtotal);
        }
  }
  else{
        if(num !='' && num !=0)
        {
            var cantidad=num;
            var valor=$(this).attr('data-valor');
            var subtotal=0;

            subtotal = Number($('#totalApertura').val());
            subtotal -= Number(valor * $(this).attr('data-ingresado'));//resto antes de perderlo
            $('#totalApertura').val(subtotal);

            var total=0;
            total = Number($('#totalApertura').val());
            total += Number(valor * cantidad);//valor nuevo

            $('#totalApertura').val(total);

            $(this).attr('data-ingresado',cantidad);
        }
        if (num ==''|| num ==0) {
          var cantidad=0;
          var valor=$(this).attr('data-valor');
          var subtotal=0;

          subtotal = Number($('#totalApertura').val());
          subtotal -= Number($(this).attr('data-ingresado') * valor );

          $('#totalApertura').val(subtotal);
          $(this).attr('data-ingresado',cantidad);

        }
    }
});

//desvincular una apertura y un cierre. Cuando se valido mal la apertura
$(document).on('click', '.desvincular', function(e){
  e.preventDefault();
  $('#mensajeExito').hide();
  $('#mensajeError').hide();
  $('#modalDesvinculacion').modal('show');
  $('#btn-desvincular').val($(this).val());

});

$(document).on('click', '#btn-desvincular', function(e){

  var id=$(this).val();

  $.get('aperturas/desvincularApertura/' + id, function(data){

    if(data==1){
      $('#modalDesvinculacion').modal('hide');
      $('#mensajeExito p').text('Se ha desvinculado el cierre de esta Apertura.');
      $('#mensajeExito').show();
      $('#btn-buscarCyA').trigger('click',[1,10,'apertura_mesa.fecha','desc']);
    }
    else{
      $('#modalDesvinculacion').modal('hide');
      $('#mensajeError p').text('No es posible realizar esta acción, ya ha cerrado el periodo de producción correspondiente.');
      $('#mensajeError').show();
    }
  })
})

$(document).on('click', '.btn_ver_mesa', function(e){
  e.preventDefault();

  $('#mensajeExitoCargaAp').hide();
  $('#mensajeErrorCargaAp').hide();

  limpiarCargaApertura();
  $("input[name='monedaApertura'][value='1']").prop('checked', true);

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

      for (var i = 0; i < data.fichas.length; i++) {

        var fila= $('#filaFichasClon').clone();
        fila.removeAttr('id');
        fila.attr('id', data.fichas[i].id_ficha);
        fila.find('.fichaVal').val(data.fichas[i].valor_ficha).attr('id',data.fichas[i].id_ficha);
        fila.find('.inputApe').attr('data-valor',data.fichas[i].valor_ficha).attr('data-ingresado', 0);
        fila.find('.inputApe').addClass('fichas'+i+'cantidad_ficha');
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
  limpiarCargaCierre();
  $('#columnaDetalle').hide();
  $('#columnaDetalleCie').hide();

  //si queda vacia la tabla, la oculta.
  if (tbody.children().length == 0) {

    console.log('andaaa');
    $('.listMes').hide();
  }

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
          if(valor.cantidad_ficha != "" && valor.cantidad_ficha != "0" ){
            fichas.push(valor);
          }else{
            valor={
              id_ficha: $(this).find('.fichaVal').attr('id'),
              cantidad_ficha: 0
            }
              fichas.push(valor);
          }

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
          $('#btn-guardar-apertura').hide();
          $('#btn-finalizar-apertura').show();

        },
        error: function (reject) {
              if( reject.status === 422 ) {
                  var errors = $.parseJSON(reject.responseText);
                  $.each(errors, function (key, val) {
                    if(key == 'fecha'){
                      mostrarErrorValidacion($('#B_fecha_apert'),val[0],false);
                    }
                    if(key == 'hora'){
                      mostrarErrorValidacion($('#horarioAp'),val[0],false);
                    }
                    if(key == 'id_fiscalizador'){
                      mostrarErrorValidacion($('#fiscalizApertura'),val[0],false);
                    }
                    if(key== 'total_pesos_fichas_a'){
                      $('#mensajeErrorCargaAp').show();
                    }
                    if(key == 'id_moneda'){
                      $('#mensajeErrorCargaAp').show();
                    }
                    $('#btn-guardar-apertura').prop('disabled',false);

                    if(key != 'hora' && key != 'fecha' &&
                       key != 'id_fiscalizador' && key != 'id_moneda' &&
                       key != 'total_pesos_fichas_a'
                      ){
                        var splitt = key.split('.');
                        mostrarErrorValidacion( $('.' + splitt[0]+splitt[1]+splitt[2] ),val[0],false);
                    }
                  });
              }else{
                $('#mensajeErrorCargaAp').show();
              }
          }
    })

});

$('#btn-finalizar-apertura').on('click', function(){

    $('#modalCargaApertura').modal('hide');
    $('#mensajeExito h3').text('ÉXITO');
    $('#mensajeExito p').text('Las Aperturas cargadas han sido guardadas correctamente');
    $('#mensajeExito').show();
})

//CIERRES CIERRES CIERRES CIERRES Cierres

$('#btn-cargar-cierre').on('click', function(e){

  e.preventDefault();

  $('#mensajeExito').hide();
  limpiarCargaCierre();
  ocultarErrorValidacion($('#horario_ini_c'));
  ocultarErrorValidacion($('#juegoCierre'));
  ocultarErrorValidacion($('#horarioCie'));
  ocultarErrorValidacion($('#B_fecha_cie'));
  ocultarErrorValidacion($('#totalAnticipoCierre'));
  ocultarErrorValidacion($('#casinoCierre'));

  $('#B_fecha_cie').val('');

  $('#mensajeCargaConError').hide();
  $('#mensajeFichasError2').hide();
  $('#mensajeErrorMoneda').hide();

  $('#casinoCierre').val("0");
  $('.desplegable').hide();

  $('#btn-guardar-cierre').hide();
  $('#btn-finalizar-cierre').hide();

  $('#modalCargaCierre').modal('show');

})

//por si luego de confirmar cambia de nuevo el casino
$(document).on('change','#casinoCierre',function(){

  var id_casino=$('#casinoCierre').val();
  $('#inputMesaCierre').generarDataList("mesas/obtenerMesasCierre/" + id_casino,'mesas' ,'id_mesa_de_panio','nro_mesa',1);
  $('#juegoCierre').generarDataList("mesas-juegos/obtenerJuegoPorCasino/" + id_casino,'juegos' ,'id_juego_mesa','nombre_juego',1);
  $('#fiscalizadorCierre').val('');
  $('#tablaCargaCierreF tbody tr').remove();
  $('#horario_ini_c').val("");
  $('#horarioCie').val("");
  $('#totalCierre').val("");
  $('#total_anticipos_c').val("");
  $('columnaDetalleCie').hide();
});

$('#confirmarCierre').on('click',function(e){

  e.preventDefault();

    if($('#casinoCierre').val() != 0  && $('#B_fecha_cie').val().length != 0 ){

      $('.desplegable').show(); //agregar mesa + fiscalizador
      var id_casino=$('#casinoCierre').val();


      $('.listMes').hide();
      $('#listaMesasCierres tbody tr').remove();
      $('#columnaDetalleCie').hide();
      $('#mensajeExitoCargaCie').hide();

      $.get('usuarios/quienSoy',function(data){
        console.log('quiensoy',data);
        $('#fiscalizadorCierre').val(data.usuario.nombre);
        $('#fiscalizadorCierre').attr('data-cargador',data.usuario.id_usuario);
      })
    }
  else{
    if($('#casinoCierre').val() == 0 ){
      mostrarErrorValidacion($('#casinoCierre'),'Campo Obligatorio',false);
    }
    if($('#B_fecha_cie').val().length == 0  ){
      mostrarErrorValidacion($('#B_fecha_cie'),'Campo Obligatorio',false);
    }
  }

})

$(document).on('click', '.cargarDatos', function(e){
  e.preventDefault();

  var id_casino=$('#casinoCierre').val();

  $('#mensajeExitoCargaAp').hide();
  $('#mensajeErrorCargaAp').hide();
  $('#mensajeExitoCargaCie').hide();

  limpiarCargaCierre();
  $('#inputMesaCierre').generarDataList("mesas/obtenerMesasCierre/" + id_casino,'mesas' ,'id_mesa_de_panio','nro_mesa',1);
  $('#juegoCierre').generarDataList("mesas-juegos/obtenerJuegoPorCasino/" + id_casino,'juegos' ,'id_juego_mesa','nombre_juego',1);

  $('#btn-guardar-cierre').show();
  $("input[name='moneda'][value='1']").prop('checked', true);

  $('#modalCargaCierre #id_mesa_panio').val($(this).attr('data-id'));


  //$('#hor_cierre').datepicker('1-2-3');
  //$('#horario_ini_c').datepicker('1-2-3');

  $('#listaMesasCierres tbody tr').css('background-color','#FFFFFF');
  $(this).parent().parent().css('background-color', '#E0E0E0');

  if($(this).attr('data-cargado') == true){

    $('#btn-guardar-cierre').hide();
  }
  else{
  //$('#listaMesasCierres tbody tr').remove();
  $('#btn-guardar-cierre').show();
  $('#btn-guardar-cierre').prop('disabled',false);
  }
  $('#columnaDetalleCie').show();

  var id_mesa=$(this).attr('data-id');
  $('#id_mesa_ap').val(id_mesa);

  $.get('mesas/detalleMesa/' + id_mesa, function(data){

    //$('#moneda').val(data.moneda.descripcion);
    for (var i = 0; i < data.fichas.length; i++) {


      var fila= $('#clonCierre').clone();
      fila.removeAttr('id');
      fila.attr('id', data.fichas[i].id_ficha);
      fila.find('.fichaValCC').val(data.fichas[i].valor_ficha).attr('id',data.fichas[i].id_ficha);
      fila.find('.inputCie').attr('data-valor',data.fichas[i].valor_ficha).attr('data-ingresado', 0);
      fila.find('.inputCie').addClass('fichas'+i+'monto_ficha');
      fila.css('display', 'block');
      $('#bodyFichasCierre').append(fila);
     }

  })

});

$(document).on('change','.inputCie',function(){

  var num= Numeros($(this).val());

  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

    if(num !== '' && num !== 0) //si se ingreso un valor diferente de 0
    {   var cantidad=num;
        $(this).attr('data-ingresado',cantidad);
        var valor=$(this).attr('data-valor');

        var subtotal=0;
        subtotal = Number($('#totalCierre').val());
        subtotal += Number(cantidad);
        $('#totalCierre').val(subtotal);}

    if (num == '' || num == 0) { //si se ingresa el 0 o nada
      var cantidad=0;
      var subtotal=0;
      subtotal = Number($('#totalCierre').val());
      subtotal -= Number($(this).attr('data-ingresado') );
      $('#totalCierre').val(subtotal);
      $(this).attr('data-ingresado',cantidad);

    }
  }
  else{
    if(num !== '' && num !== 0){ //si se ingreso un valor diferente de 0
        var cantidad=num;
        var subtotal=0;
        //tomo el data ingresado anteriormente y lo resto al total antes de perderlo
        subtotal = Number($('#totalCierre').val());
          if(($(this).attr('data-ingresado')) !== 0){
            subtotal-=Number($(this).attr('data-ingresado'));}
        $('#totalCierre').val(subtotal);

        $(this).attr('data-ingresado',cantidad);//cambio el data ingresado
      //  var valor=$(this).attr('data-valor');

        var total=0;

        total = Number($('#totalCierre').val());
        total += Number(cantidad);

        $('#totalCierre').val(total);}

    if (num=='' || num==0) { //si se ingresa el 0 o nada
          var cantidad=0;
          var subtotal=0;
          subtotal = Number($('#totalCierre').val());
          subtotal -= Number($(this).attr('data-ingresado') );
          $('#totalCierre').val(subtotal);

          $(this).attr('data-ingresado',cantidad);

    }
  }
})


//dentro del modal de carga de cierre, presiona el botón guardar
$('#btn-guardar-cierre').on('click', function(e){

  e.preventDefault();

  $(this).prop('disabled',true);

  $('#mensajeError').hide();
  $('#mensajeExito').hide();
  $('#recalcular').trigger('click');

    var fichas=[];
    var id_mesa=$('#id_mesa_panio').val();
    var moneda= $('input[name=moneda]:checked').val();
    var f= $('#bodyFichasCierre > tr');
    $.each(f, function(index, value){

      var valor={
        id_ficha: $(this).find('.fichaValCC').attr('id'),
        monto_ficha: $(this).find('.inputCie').attr('data-ingresado')
      }
      if(valor.monto_ficha != "" ){
        fichas.push(valor);
      }
      else{
        fichas=null;
      }

    })

      var formData= {
        fecha: $('#B_fecha_cie').val(),
        hora_inicio: $('#horario_ini_c').val(),
        hora_fin:$('#horarioCie').val(),
        id_fiscalizador: $('#fiscalizadorCierre').attr('data-cargador'),
        id_casino: $('#casinoCierre').val(),
        id_juego_mesa: $('#juegoCierre').obtenerElementoSeleccionado(),
        total_pesos_fichas_c:$('#totalCierre').val(),
        total_anticipos_c:$('#totalAnticipoCierre').val(),
        id_mesa_de_panio:id_mesa,
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
          url: 'cierres/guardar',
          data: formData,
          dataType: 'json',

          success: function (data){

            limpiarCargaCierre();

            $('#listaMesasCierres tbody').find('#' + id_mesa).attr('data-cargado',true);
            $('#listaMesasCierres tbody').find('#' + id_mesa).find('.btn_borrar_mesa').parent().remove();
            $('#listaMesasCierres tbody').find('#' + id_mesa).find('.cargarDatos').prop('disabled', true);
            $('#listaMesasCierres tbody').find('#' + id_mesa).append($('<td>').addClass('col-xs-2').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50')));

            $('#columnaDetalleCie').hide();
            $('#mensajeCargaConError').hide();
            $('#mensajeFichasError2').hide();
            $('#mensajeErrorMoneda').hide();
            $('#btn-guardar-cierre').hide();
            $('#mensajeExitoCargaCie').show();
            $('#btn-finalizar-cierre').show();

          },
          error: function (reject) {
              $('#mensajeError h3').text('ERROR');
                if( reject.status === 422 ) {
                    var errors = $.parseJSON(reject.responseText);
                    $.each(errors, function (key, val) {

                      if(key == 'fecha'){
                        mostrarErrorValidacion($('#B_fecha_cie'),val[0],false);

                      }
                      if(key == 'id_moneda'){
                        $('#mensajeErrorMoneda').show();
                      }
                      if(key == 'id_juego_mesa' || key == 'id_mesa_de_panio' ){
                        $('#mensajeCargaConError').show();
                      }

                      if(key == 'total_pesos_fichas_c'){
                        $('#mensajeCargaConError').show();
                      }
                      if(key == 'hora_inicio'){
                        mostrarErrorValidacion($('#horario_ini_c'),val[0],false);
                      }
                      if(key == 'hora_fin'){
                        mostrarErrorValidacion($('#horarioCie'),val[0],false);
                      }
                      if(key == 'fichas'){
                        $('#mensajeFichasError2').show();
                      }
                      if(key != 'id_moneda' && key != 'id_fiscalizador' &&
                         key != 'total_pesos_fichas_c' && key != 'fichas' &&
                         key != 'id_mesa_de_panio' && key != 'fecha'
                        ){
                          var splitt = key.split('.');
                          mostrarErrorValidacion( $('.' + splitt[0]+splitt[1]+splitt[2] ),val[0],false);
                          $('#mensajeFichasError2').show();

                      }
                    });
                      $('#btn-guardar-cierre').prop('disabled',false);
                }else{
                    $('#errorModificarCierre').show();
                      $('#btn-guardar-cierre').prop('disabled',false);
                }
            }
      })

});

$('#btn-finalizar-cierre').on('click', function(){

  $('#modalCargaCierre').modal('hide');
  $('#mensajeExito h3').text('EXITO');
  $('#mensajeExito p').text('Los Cierres cargados han sido guardados correctamente.');
  $('#mensajeExito').show();

});

$(document).on('click', '.infoCyA', function(e) {

  e.preventDefault();

    $('#bodyFichasDetApert tr').remove();
    var id_apertura=$(this).val();

    $.get('aperturas/obtenerAperturas/' + id_apertura, function(data){
      console.log('pp',data.detalles);
      $('#modalDetalleApertura').modal('show');

      $('.mesa_det_apertura').text(data.mesa.nombre + ' - ' + data.moneda.descripcion);
      $('.fecha_det_apertura').text(data.apertura.fecha);
      $('.juego_det_apertura').text(data.juego.nombre_juego);
      $('.hora_apertura_det').text(data.apertura.hora_format);
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
            fila.css('cssText','padding:2px !important');
        $('#bodyFichasDetApert').append(fila);
      }
    })

});

$(document).on('click', '.infoCierre', function(e) {

  e.preventDefault();

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
        $('.hora_cierre_det').text(data.cierre.hora_fin_format);
      }
      else{
        $('.hora_cierre_det').text(' - ');
      }
      if(data.cierre.hora_inicio != null){
        $('.inicio_cierre_det').text(data.cierre.hora_inicio_format);
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

        $('#fichasdetallesC').append(fila);
      }

      $('#total_detalle').val(data.cierre.total_pesos_fichas_c);
      if(data.cierre.total_anticipos_c != null){
        $('#anticipos_detalle').val(data.cierre.total_anticipos_c);
      }
      else{
        $('#anticipos_detalle').val(' - ');
      }
      if(data.detalleAP != null){

        $('.aperturaVinculada').show();
        $('#datosApertCierre').text('DATOS APERTURA');

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

        $('#totalA_det_cierre').val(data.apertura.total_pesos_fichas_a);
      }
      else{
        $('.aperturaVinculada').hide();
        $('#datosApertCierre').text('AÚN NO SE HA VINCULADO NINGUNA APERTURA');
      }
    })

});

$(document).on('click', '.validarCierre', function(e) {

  e.preventDefault();

    //limpiar porquerias
    $('#datosCierreFichasValidar tr').remove();

    var id_cierre= $(this).val();
    $('#validarCierre').val(id_cierre);
    $('#obsValidacionCierre').text('');

    $.get('cierres/obtenerCierres/' + id_cierre, function(data){

      $('#modalValidarCierre').modal('show');

      $('.mesa_validar_c').text('MESA: ' + data.mesa.nombre + ' - ' + data.moneda.descripcion);
      $('.juego_validar_c').text('NOMBRE DE JUEGO: '+ data.nombre_juego);
      $('.cargador_validar_c').text('FISCALIZADOR DE CARGA: '+ data.cargador.nombre);
      $('.fecha_validar_c').text('FECHA:' + data.cierre.fecha);


      if(data.cierre.hora_fin_format != null){
        $('.hora_cierre_validar_c').text('HORA DE CIERRE: ' + data.cierre.hora_fin_format);
      }
      else{
        $('.hora_cierre_validar_c').text('HORA DE CIERRE: - ');
      }
      if(data.cierre.hora_inicio_format != null){
        $('.inicio_validar_c').text('HORA DE INICIO: ' + data.cierre.hora_inicio_format);
      }
      else{
        $('.inicio_validar_c').text('HORA DE INICIO: - ');

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

        $('#fichasdetallesCValidar').append(fila);
      }

      $('#total_validar_c').val(data.cierre.total_pesos_fichas_c);
      if(data.cierre.total_anticipos_c != null){
        $('#anticipos_detalle').val(data.cierre.total_anticipos_c);
      }
      else{
        $('#anticipos_validar_c').val(' - ');
      }


    })

});

$('#validarCierre').on('click',function(e){
  e.preventDefault();

  var id = $(this).val();

    var formData= {
      id_cierre:id,
      observaciones: $('#obsValidacionCierre').val(),
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: 'POST',
        url: 'cierres/validar',
        data: formData,
        dataType: 'json',

        success: function (data){

          $('#modalValidarCierre').modal('hide');
          $('#mensajeExito h3').text('ÉXITO');
          $('#mensajeExito p').text('Cierre Validado correctamente. ');
          $('#mensajeExito').show();
          $('#btn-buscar-cierre').trigger('click',[1,10,'cierre_mesa.fecha','desc']);
        },
        error: function(data){
          console.log('error');
          //  var response = data.responseJSON.errors;
          //
          //  if(typeof response.id_cierre !== 'undefined'){
          //    $('#mensajeErrorValApertura').show();
          //  }
          // // if(typeof response.hora_fin !== 'undefined'){
          // //   mostrarErrorValidacion($('#hs_cierre_cierre'),response.hora_fin[0],false);
          // // }
        },
    })

})


$(document).on('click', '.modificarCyA', function(e) {

  e.preventDefault();


  $('#modificar_apertura').hide();

  //APERTURA

    ocultarErrorValidacion($('#hs_apertura'));
    ocultarErrorValidacion($('#car_apertura'));
    ocultarErrorValidacion($('#fis_apertura'));
    $('#modificarFichasAp tr').remove();
    $('#errorModificar2').hide();


    var id_apertura=$(this).val();
    //guardo el id para hacer el guardar despues
    $('#modalModificarApertura #id_apertura').val(id_apertura);

    $.get('aperturas/obtenerAperturas/' + id_apertura, function(data){

      var id_casino = data.casino.id_casino;
      $('.f_apertura').text(data.apertura.fecha);
      $('#fis_apertura').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#fis_apertura').setearElementoSeleccionado(data.fiscalizador.id_usuario, data.fiscalizador.nombre);
      $('.car_apertura').text(data.cargador.nombre);
      $('.cas_apertura').text( data.casino.nombre);
      $("input[name='monedaModApe'][value='"+data.moneda.id_moneda+"']").prop('checked', true);


      //$('.mon_apertura').val(data.moneda.descripcion);
      $('#hs_apertura').val(data.apertura.hora_format);
      //$('.j_apertura').val();
      $('.nro_apertura').text(data.mesa.nro_mesa + '-' + data.juego.nombre_juego);

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
                  .append($('<input>').addClass('modApertura'+' fichas'+i+'cantidad_ficha').attr('id', 'input').val(data.detalles[i].cantidad_ficha).css('text-align','center')
                  .attr('data-valor',data.detalles[i].valor_ficha).attr('data-ingresado', data.detalles[i].cantidad_ficha)))
            }
            else{
              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modApertura'+' fichas'+i+'cantidad_ficha').attr('id', 'input').val(0).css('text-align','center')
                  .attr('data-valor',data.detalles[i].valor_ficha).attr('data-ingresado', 0)))
            }

        $('#modificarFichasAp').append(fila);
      }

      var total = 0;

      $('#modificarFichasAp tr').each(function(){
        var valor = $(this).find('.modApertura').attr('data-valor');
        var ingresado = $(this).find('.modApertura').attr('data-ingresado');

        total += Number(valor * ingresado);

        $('#totalModifApe').val(total);
      })
      $('#modificar_apertura').show();

      $('#modalModificarApertura').modal('show');

    })

});

$(document).on('click', '.modificarCierre', function(e) {

  e.preventDefault();
  //CIERRE

    $.get('usuarios/quienSoy',function(usuario){
    });

    ocultarErrorValidacion($('#hs_cierre_cierre'));
    ocultarErrorValidacion($('#hs_inicio_cierre'));
    ocultarErrorValidacion($('#totalAnticipoModif'));
    ocultarErrorValidacion($('#fis_cierre'));
    $('#errorModificarCierre').hide();
    $('#errorModificarCierre2').hide();

    $('#modificarFichasCie tr').remove();
    var id_cierre= $(this).val();
    $('#modalModificarCierre #id_cierre').val(id_cierre);


    $.get('cierres/obtenerCierres/' + id_cierre, function(data){

      var id_casino = data.casino.id_casino;
      $("input[name='monedaModCie'][value='"+data.moneda.id_moneda+"']").prop('checked', true);

      $('#fis_cierre').generarDataList("usuarios/buscarFiscalizadores/" + id_casino,'usuarios' ,'id_usuario','nombre',1);
      $('#fis_cierre').setearElementoSeleccionado(data.cargador.id_usuario, data.cargador.nombre);
      $('.cas_cierre').text( data.casino.nombre);
      $('#hs_cierre_cierre').val(data.cierre.hora_fin_format);
      $('#hs_inicio_cierre').val(data.cierre.hora_inicio_format);
      $('.j_cierre').text(data.nombre_juego);
      $('#f_cierre').text(data.cierre.fecha);
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
                  .append($('<input>').addClass('modCierre'+' fichas'+i+'monto_ficha').attr('id', 'input').val(data.detallesC[i].monto_ficha).css('text-align','center')
                  .attr('data-valor',data.detallesC[i].valor_ficha).attr('data-ingresado', data.detallesC[i].monto_ficha)))
            }
            else{
              fila.append($('<td>')
                  .addClass('col-md-3')
                  .append($('<input>').addClass('modCierre'+' fichas'+i+'monto_ficha').attr('id', 'input').val(0).css('text-align','center')
                  .attr('data-valor',data.detallesC[i].valor_ficha).attr('data-ingresado', 0)))
            }

        $('#modificarFichasCie').append(fila);
      }

      var total = 0;

      $('#modificarFichasCie tr').each(function(){

        var ingresado = $(this).find('.modCierre').attr('data-ingresado');
        total += Number(ingresado);

        $('#totalModifCie').val(total);
        $('#totalModifCie').attr('total',total);
      })

      $('#modalModificarCierre').modal('show');
      })
});

//detecta modificaciones en los inputs de modificacion de apertura
$(document).on('change','.modApertura',function(){

  var num= Numeros($(this).val());

    if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado
      if(num !='' && num!=0)
      {   var cantidad=num;
          $(this).attr('data-ingresado',cantidad);
          var valor=$(this).attr('data-valor');

          var subtotal=0;
          subtotal = Number($('#totalModifApe').val());
          subtotal += Number(valor * cantidad);

          $('#totalModifApe').val(subtotal);
      }

      if (num==''|| num==0) {
        var cantidad=0;
        var subtotal=0;
        subtotal = Number($('#totalModifApe').val());
        subtotal -= Number($(this).attr('data-ingresado'));

        $('#totalModifApe').val(subtotal);
        $(this).attr('data-ingresado',cantidad);

      }
    }
    else{
      if(num!='' && num!=0)
      {   var cantidad=num;
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
      if (num=='' || num==0) {
        var cantidad=0;
        var subtotal=0;
        subtotal = Number($('#totalModifApe').val());
        subtotal -= Number($(this).attr('data-ingresado')*($(this).attr('data-valor')));

        $('#totalModifApe').val(subtotal);
        $(this).attr('data-ingresado',cantidad);

      }
    }
})

//Guardar El modificar apertura
$('#modificar_apertura').on('click', function(e){
  e.preventDefault();

  $('#mensajeError').hide();
  $('#mensajeExito').hide();

    var fichas=[];
    var moneda= $('input[name=monedaModApe]:checked').val();
    var f= $('#modificarFichasAp > tr');

    $.each(f, function(index, value){

      var valor={
        id_ficha: $(this).find('.fichaVal').attr('id'),
        cantidad_ficha: $(this).find('.modApertura').val()
      }
      if(valor.cantidad_ficha != "" && valor.cantidad_ficha != "0" ){
        fichas.push(valor);
      }else{
        valor={
          id_ficha: $(this).find('.fichaVal').attr('id'),
          cantidad_ficha: 0
        }
          fichas.push(valor);
      }

    })

      var formData= {
        id_apertura:$('#modalModificarApertura #id_apertura').val(),
        hora: $('#hs_apertura').val(),
        id_fiscalizador:$('#fis_apertura').obtenerElementoSeleccionado(),
        total_pesos_fichas_a: $('#totalModifApe').val(),
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
          url: 'aperturas/modificarApertura',
          data: formData,
          dataType: 'json',

          success: function (data){

            $('#modalModificarApertura').modal('hide');
             $('#mensajeExito h3').text('ÉXITO');
             $('#mensajeExito p').text('Apertura guardada correctamente');
             $('#mensajeExito').show();
          },
          error: function (reject) {
                if( reject.status === 422 ) {
                    var errors = $.parseJSON(reject.responseText);
                    $.each(errors, function (key, val) {
                      if(key == 'id_moneda'){
                        $('#errorModificar2').show();
                      }
                      if(key == 'id_fiscalizador'){
                        mostrarErrorValidacion($('#fis_apertura'),val[0],false);
                      }
                      if(key == 'total_pesos_fichas_a'){
                        $('#errorModificar').show();
                      }
                      if(key == 'hora'){
                        mostrarErrorValidacion($('#hs_apertura'),val[0],false);
                      }

                      if(key == 'fichas'){
                        $('#errorModificar').show();
                      }
                      if(key != 'id_moneda' && key != 'id_fiscalizador' &&
                         key != 'total_pesos_fichas_c' && key != 'fichas'
                        ){
                          var splitt = key.split('.');
                          mostrarErrorValidacion( $('.' + splitt[0]+splitt[1]+splitt[2] ),val[0],false);
                          $('#errorModificar').show();
                      }
                    });
                }else{
                  $('#errorModificar').show();
                }
            }
      })

});

//MODIFICAR CIERRE
//modifica el monto de alguna ficha
$(document).on('change','.modCierre',function(){

  var num= Numeros($(this).val());

  if($(this).attr('data-ingresado') == 0){ //si no hay valor en el input modificado

    if(num!=null && num!=0) //si se ingreso un valor diferente de 0
    {   var cantidad=num;
        $(this).attr('data-ingresado',cantidad);
        //var valor=$(this).attr('data-valor');

        var subtotal=0;
        subtotal = Number($('#totalModifCie').val());
        subtotal += Number(cantidad);
        $('#totalModifCie').val(subtotal);}

    if (num==null || num==0) { //si se ingresa el 0 o nada
      var cantidad=0;
      var subtotal=0;
      subtotal = Number($('#totalModifCie').val());
      subtotal -= Number($(this).attr('data-ingresado') );

      $('#totalModifCie').val(subtotal);
      $(this).attr('data-ingresado',cantidad);

    }
  }
  else{
    if(num!=null && num!=0){ //si se ingreso un valor diferente de 0
        var cantidad=num;
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

    if (num=='' || num==0) { //si se ingresa el 0 o nada
          var cantidad=0;
          var subtotal=0;
          subtotal = Number($('#totalModifCie').val());
          subtotal -= Number($(this).attr('data-ingresado') );

          $('#totalModifCie').val(subtotal);
          $(this).attr('data-ingresado',cantidad);
    }
  }

})


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
      if(valor.monto_ficha != "" && valor.monto_ficha != "0" ){
        fichas.push(valor);
      }else{
        valor={
          id_ficha: $(this).find('.fichaVal').attr('id'),
          monto_ficha: 0
        }
          fichas.push(valor);
      }

    })

      var formData= {
        id_cierre_mesa:$('#modalModificarCierre #id_cierre').val(),
        hora_inicio: $('#hs_inicio_cierre').val(),
        hora_fin: $('#hs_cierre_cierre').val(),
        id_fiscalizador:$('#fis_cierre').obtenerElementoSeleccionado(),
        total_pesos_fichas_a: $('#totalModifCie').val(),
        total_anticipos_c: $('#totalAnticipoModif').val(),
        fichas: fichas,
        //fecha:$('#f_cierre').val(),
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
          error: function (reject) {
                if( reject.status === 422 ) {
                    var errors = $.parseJSON(reject.responseText);
                    $.each(errors, function (key, val) {
                      if(key == 'id_moneda'){
                        $('#errorModificarCierre2').show();
                      }
                      if(key == 'id_fiscalizador'){
                        mostrarErrorValidacion($('#fis_cierre'),response.id_fiscalizador[0],false);
                      }
                      if(key == 'total_pesos_fichas_c'){
                        $('#errorModificarCierre').show();
                      }
                      if(key == 'hora_inicio'){
                        $('#errorModificarCierre').show();
                      }
                      if(key == 'hora_fin'){
                        $('#errorModificarCierre').show();
                      }
                      if(key == 'fichas'){
                        $('#errorModificarCierre').show();
                      }
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


//botón validar dentro del listado de aperturas
$(document).on('click', '.validarCyA', function(e) {
  e.preventDefault();
  $('#mensajeErrorValApertura').hide();

  $('#mensajeExito').hide();
  limpiarModalValidar();

  var id_apertura=$(this).val();
  $('#validar').val(id_apertura);
  $('#validar-diferencia').val(id_apertura);
  $('#validar').hide();
  $('#validar-diferencia').hide();

  $('#div_cierre').hide();

  $.get('aperturas/obtenerApValidar/' + id_apertura , function(data){

    $('.nro_validar').text('MESA: ' +data.mesa.nro_mesa);
    $('.fechaAp_validar_aper').text('FECHA APERTURA: ' + data.apertura.fecha);
    $('.j_validar').text('JUEGO: ' +data.juego.nombre_juego);
    $('.cas_validar').text('CASINO: ' + data.casino.nombre);

    $('.hs_validar_aper').text('HORA APERTURA: ' + data.apertura.hora_format);
    $('.fis_validar_aper').text('FISCALIZADOR DE TOMA: '+ data.fiscalizador.nombre);
    $('.car_validar_aper').text('FISCALIZADOR DE CARGA: '+ data.cargador.nombre);
    $('.tipo_validar_aper').text('TIPO MESA: ' + data.tipo_mesa.descripcion);
    $('.mon_validar_aper').text('MONEDA: ' +  data.moneda.descripcion);
    $('.mon_validar_aper').val(data.moneda.id_moneda);
    $('#total_aper_validar').val(data.apertura.total_pesos_fichas_a);
    $('#obsValidacion').val("");

    for (var i = 0; i < data.fechas_cierres.length; i++) {
      $('#fechaCierreVal')
      .append($('<option>')
              .val(data.fechas_cierres[i].id_cierre_mesa)
              .text(data.fechas_cierres[i].fecha + ' -- '+ data.fechas_cierres[i].hora_inicio_format
                    +' a '+ data.fechas_cierres[i].hora_fin_format
                    +' -- '+data.fechas_cierres[i].siglas
                  ))
    }

  })

  $('#obsValidacion').focus();

  $('#modalValidarApertura2').modal('show');

});

//comparar, busca el cierre que se desea comparar
$(document).on('click','.comparar',function(){

  if($('#fechaCierreVal').val() != 0){

    $('#tablaValidar tbody tr').remove();

    $('#validar').show();
    $('#validar-diferencia').show();

    var moneda=$('.mon_validar_aper').val();
    var apertura=$('#validar').val();
    var cierre=$('#fechaCierreVal').val();
    //{id_apertura}/{id_cierre}/{id_moneda}
  $.get('compararCierre/' + apertura + '/' + cierre + '/' + moneda, function(data){
      $('#div_cierre').show();

      // //datos cierre
      if(data.cierre == null){
        $('.hs_inicio_validar').text('HORA APERTURA: -');
        $('.hs_cierre_validar').text('HORA CIERRE: -');
        $('.f_validar').text('FECHA: -');
        $('#anticipos_validar').val('-');
        $('#total_cierre_validar').val('-');
      }else {
        $('.hs_inicio_validar').text('HORA APERTURA: ' + data.cierre.hora_inicio_format);
        $('.hs_cierre_validar').text('HORA CIERRE: ' + data.cierre.hora_fin_format);
        $('.f_validar').text('FECHA: ' + data.cierre.fecha);
        $('#anticipos_validar').val(data.cierre.total_anticipos_c);
        $('#total_cierre_validar').val(data.cierre.total_pesos_fichas_c);
      }

      //relleno de tabla validar: creo la tabla con solo las fichas
      //recibidas, luego recorro los detalles,la tabla y completo

        for (var i = 0; i < data.fichas.length; i++) {

          var fila= $('#clonarTFichasV').clone();
          fila.removeAttr('id');
          fila.attr('id',data.fichas[i].id_ficha);

          fila.find('.valor_validar').addClass('v_id_ficha').addClass('cierre').text(data.fichas[i].valor_ficha);
          fila.find('.cant_cierre_validar').text(0);
          fila.find('.cant_apertura_validar').text(0);

          fila.css('display','');
          $('#mostrarTablaValidar').css('display','block');

          $('#tablaValidar #validarFichas').append(fila);
        }

        for (var i = 0; i < data.detalles_cierre.length; i++) {

          var t=$('#tablaValidar tbody > tr');
          $.each(t, function(index, value){
            if($(this).attr('id') == data.detalles_cierre[i].id_ficha){
              $(this).find('.cant_cierre_validar').addClass('cierre').text(data.detalles_cierre[i].cantidad_ficha);

            }
          })
        }
        for (var i = 0; i < data.detalles_apertura.length; i++) {

          var t=$('#tablaValidar tbody > tr');
          $.each(t, function(index, value){
            if($(this).attr('id') == data.detalles_apertura[i].id_ficha){

              $(this).find('.cant_apertura_validar').text(data.detalles_apertura[i].cantidad_ficha);

            }
          })
        }

            //agrego icono comparando valores

            var t=$('#tablaValidar tbody > tr');

            $.each(t, function(index, value){

                var fichas_apertura = $(this).find('.cant_apertura_validar').text();
                var fichas_cierre = $(this).find('.cant_cierre_validar').text();

                if(fichas_cierre == fichas_apertura){
                  $(this).find('.diferencias_validar').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#66BB6A'));
                }
                else{
                  $(this).find('.diferencias_validar').append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F'))
                }
            })

  })
}
});
//cuando cambia la fecha
$(document).on('change', '#fechaCierreVal', function(e) {

  e.preventDefault();

  var t=$('#fechaCierreVal').val();

  if(t==0){
    $('#validar').hide();
    $('#validar-diferencia').hide();

  }

  $('#tablaValidar tbody tr').remove();
  $('#div_cierre').hide();
  $('#anticipos_validar').val('-');
  $('#total_cierre_validar').val('-');

});

//botón validar dentro del modal
$(document).on('click', '#validar', function(e) {
  e.preventDefault();
  $('#mensajeExito').hide();

  var id_apertura = $(this).val();

    var formData= {
      id_cierre:$('#fechaCierreVal').val(),
      id_apertura:id_apertura,
      diferencia:0,
      observaciones: $('#obsValidacion').val(),
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

          $('#modalValidarApertura2').modal('hide');
          $('#mensajeExito h3').text('ÉXITO');
          $('#mensajeExito p').text('Apertura Validada correctamente. ');
          $('#mensajeExito').show();
          $('#btn-buscarCyA').trigger('click',[1,10,'apertura_mesa.fecha','desc']);
        },
        error: function(data){

           var response = data.responseJSON;

           if(typeof response.id_cierre !== 'undefined'){
             $('#mensajeErrorValApertura').show();
           }
          // if(typeof response.hora_fin !== 'undefined'){
          //   mostrarErrorValidacion($('#hs_cierre_cierre'),response.hora_fin[0],false);
          // }
        },
    })

});

//botón validar dentro del modal
$(document).on('click', '#validar-diferencia', function(e) {
  e.preventDefault();
  $('#mensajeExito').hide();

  var id_apertura = $(this).val();

    var formData= {
      id_cierre:$('#fechaCierreVal').val(),
      id_apertura:id_apertura,
      diferencia:1,
      observaciones: $('#obsValidacion').val(),
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

          $('#modalValidarApertura2').modal('hide');
          $('#mensajeExito h3').text('ÉXITO');
          $('#mensajeExito p').text('Apertura Validada correctamente. ');
          $('#mensajeExito').show();
          $('#btn-buscarCyA').trigger('click',[1,10,'apertura_mesa.fecha','desc']);
        },
        error: function(data){

           var response = data.responseJSON;

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

  $('#modalAlertaBaja #btn-baja').attr('data-tipo','apertura');
  $('#modalAlertaBaja #btn-baja').val($(this).val());

  $('#msjAlertaBaja').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTA APERTURA?')
  $('#modalAlertaBaja').modal('show');

});

$('#btn-baja').on('click',function(){
  var id=$(this).val();

  if($(this).attr('data-tipo') == 'apertura'){
    $('#cuerpoTablaCyA').find('#' + id).remove();

       $.get('aperturas/bajaApertura/' + id, function(data){
         $('#mensajeExito h3').text('ÉXITO');
         $('#mensajeExito p').text(' ');
         $('#mensajeExito').show();
       });

       $('#modalAlertaBaja').modal('hide');
  }
  if($(this).attr('data-tipo') == 'cierre'){
    $('#cuerpoTablaCierre').find('#' + id).remove();

    $.get('cierres/bajaCierre/' + id, function(data){
         $('#mensajeExito h3').text('ÉXITO');
         $('#mensajeExito p').text(' ');
          $('#mensajeExito').show();
      })
      $('#modalAlertaBaja').modal('hide');

  }

})

//si es superusuario puede eliminarCyA
$(document).on('click','.eliminarCierre',function(e){

  $('#modalAlertaBaja #btn-baja').attr('data-tipo','cierre');
  $('#modalAlertaBaja #btn-baja').val($(this).val());

  $('#msjAlertaBaja').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTE CIERRE?')
  $('#modalAlertaBaja').modal('show');

});


//dentro del modal de cargar cierres, para agregar la mesa al listado
function clickAgregarMesaCierre(e) {
  var id_mesa_panio = $('#inputMesaCierre').attr('data-elemento-seleccionado');


     $.get('http://' + window.location.host +"/mesas/detalleMesa/" + id_mesa_panio, function(data) {

       var fila= $(document.createElement('tr'));
       fila.attr('id', data.mesa.id_mesa_de_panio)
           .append($('<td>')
           .addClass('col-xs-4')
           .text(data.mesa.nro_mesa).css('border-right','2px solid #ccc')
         )
         .append($('<td>')
         .addClass('col-xs-2')
         .append($('<span>').text(' '))
         .append($('<button>')
         .addClass('cargarDatos').attr('data-id',data.mesa.id_mesa_de_panio).attr('data-cargado',false)
             .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-eye')
           )))
           .append($('<td>')
           .addClass('col-xs-2')
           .append($('<span>').text(' '))
           .append($('<button>')
           .addClass('btn_borrar_mesa').append($('<i>')
           .addClass('fas').addClass('fa-fw').addClass('fa-trash')
             )))

      $('#inputMesaCierre').setearElementoSeleccionado(0 , "");
      $('#listaMesasCierres tbody').append(fila);
      $('.listMes').show();


    });

}

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

/*****************PAGINACION******************/
function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('apertura');


  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscarCyA').trigger('click',[pageNumber,tam,columna,orden]);
}

$(document).on('click','#tablaResultados thead tr th',function(e){
  $('#tablaResultados th').removeClass('activa');
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
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndiceCierres(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion2').getPageSize();
  var columna = $('#tablaResultadosCierres .activa').attr('cierre');


  var orden = $('#tablaResultadosCierres .activa').attr('estado');
  $('#btn-buscar-cierre').trigger('click',[pageNumber,tam,columna,orden]);
}

$(document).on('click','#tablaResultadosCierres thead tr th',function(e){
  $('#tablaResultadosCierres th').removeClass('activa');
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
  $('#tablaResultadosCierres th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndiceCierres(e,$('#herramientasPaginacion2').getCurrentPage(),$('#herramientasPaginacion2').getPageSize());
});

//fc que generan la fila del listado principal:
function generarFilaAperturas(data){

  if(data.hora != null){
    var piecesi = data.hora.split(':')
    var houri, minutei;

    if(piecesi.length === 3) {
      houri = piecesi[0];
      minutei = piecesi[1];
    }
  }else{
    houri = '-';
    minutei = '-';
  }
    var fila = $('#moldeFilaCyA').clone();
    fila.removeAttr('id');
    fila.attr('id', data.id_apertura_mesa);

    fila.find('.L_fecha').text(data.fecha);
    fila.find('.L_juego').text(data.nombre_juego);
    fila.find('.L_mesa').text(data.nro_mesa);
    fila.find('.L_hora').text( houri +':'+minutei);
    fila.find('.L_moneda').text(data.siglas_moneda);
    fila.find('.L_casino').text(data.nombre);
    if(data.id_estado_cierre == 3){
      fila.find('.L_estado').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50').css('text-align','center'));
    }
    if(data.id_estado_cierre == 1){
        fila.find('.L_estado').append($('<i>').addClass('fas fa-fw fa-times').css('color', '#D32F2F').css('text-align','center'));
    }
    if(data.id_estado_cierre == 2){
      fila.find('.L_estado').append($('<i>').addClass('fa fa-fw fa-check').css('color', '#4CAF50').css('text-align','center'));
        fila.find('.L_estado').append($('<i>').addClass('fas fa-fw fa-exclamation').css('color', '#FFC107').css('text-align','center'));
    }
    fila.find('.L_estado').show();

    fila.find('.infoCyA').val(data.id_apertura_mesa);
    fila.find('.modificarCyA').val(data.id_apertura_mesa);
    fila.find('.validarCyA').val(data.id_apertura_mesa);
    fila.find('.desvincular').val(data.id_apertura_mesa);
    fila.find('.eliminarCyA').val(data.id_apertura_mesa);

    if(data.id_estado_cierre == 3 || data.id_estado_cierre == 2){
      fila.find('.validarCyA').val(data.id_apertura_mesa).hide();
      fila.find('.eliminarCyA').val(data.id_apertura_mesa).hide();
      fila.find('.modificarCyA').val(data.id_apertura_mesa).hide();
      fila.find('.desvincular').show();
      fila.find('.infoCyA').show();
    }
    else{
      fila.find('.validarCyA').show();
      fila.find('.eliminarCyA').show();
      fila.find('.modificarCyA').show();
      fila.find('.desvincular').hide();
      fila.find('.infoCyA').hide();
    }
    fila.css('display', '');

    return fila;

}

function generarFilaCierres(data){

      if(data.hora_inicio != null){
        var piecesi = data.hora_inicio.split(':')
        var houri, minutei;

        if(piecesi.length === 3) {
          houri = piecesi[0];
          minutei = piecesi[1];
        }
      }else{
        var houri, minutei;
        houri = '-';
        minutei = '-';

      }
      if (data.hora_fin != null) {
        var piecesf= data.hora_fin.split(':')
        var hourf, minutef;

        if(piecesf.length === 3) {
          hourf = piecesf[0];
          minutef = piecesf[1];
        }

      } else {
        var hourf, minutef;
          hourf = '-';
          minutef = '-';
      }

      var fila = $('#moldeFilaCierre').clone();
      fila.removeAttr('id');
      fila.attr('id', data.id_cierre_mesa);

      fila.find('.cierre_fecha').text(data.fecha);
      fila.find('.cierre_juego').text(data.nombre_juego);
      fila.find('.cierre_mesa').text(data.nro_mesa);
      fila.find('.cierre_hora').text( houri +':'+minutei + '-'+ hourf +':'+minutef);
      fila.find('.cierre_moneda').text(data.siglas_moneda);
      fila.find('.cierre_casino').text(data.nombre);

    //cuando se presionan, ya que se usa un mismo molde

    fila.find('.infoCierre').val(data.id_cierre_mesa);
    fila.find('.modificarCierre').val(data.id_cierre_mesa);
    fila.find('.validarCierre').val(data.id_cierre_mesa);


    fila.find('.eliminarCierre').val(data.id_cierre_mesa);
    fila.css('display', '');

    switch (data.id_estado_cierre) {
      case 1://generado
        fila.find('.infoCierre').hide();
        fila.find('.eliminarCierre').show();
        fila.find('.modificarCierre').show();
        fila.find('.validarCierre').show();
        break;
      case 2://p/aperturas
        fila.find('.infoCierre').hide();
        fila.find('.eliminarCierre').show();
        fila.find('.modificarCierre').show();
        fila.find('.validarCierre').show();
        break;
      case 3://validado desde la pestaña cierres
        fila.find('.infoCierre').show();
        fila.find('.eliminarCierre').hide();
        fila.find('.modificarCierre').hide();
        fila.find('.validarCierre').hide();
        break;
      case 4://validado con apertura, desde aperturas
        fila.find('.infoCierre').show();
        fila.find('.eliminarCierre').hide();
        fila.find('.modificarCierre').hide();
        fila.find('.validarCierre').hide();
        break;
      default:

    }

  return fila;

}

function limpiarCargaCierre(){

  $('#juegoCierre').setearElementoSeleccionado('0',"");
  $('#totalCierre').val('');
  $('#totalAnticipoCierre').val('');
  $('#bodyFichasCierre tr').remove();
  $('#horarioCie').val('');
  $('#horario_ini_c').val('');
  $('#id_mesa_panio').val('');

}

function limpiarCargaApertura(){

  $('#id_mesa_ap').val('');
  $('#totalApertura').val('');
  $('#horarioAp').val('');
  $('#fiscalizApertura').setearElementoSeleccionado(0,"");
  $('#tablaCargaApertura tbody tr').remove();

}

function limpiarModalValidar(){
  //$('#validarFichas tr').not('moldeValidar').remove();
  $('.nro_validar').text(' ');
  $('#fechaCierreVal option').not('.defecto').remove();
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

    $('#tablaValidar tbody tr').remove();

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

function limpiarFiltrosApertura(){
  $('#selectCas').val('0');
  $('#selectJuego').val('0');
  $('#filtroMesa').val('');
  $('#B_fecha_filtro').val('');

}

function limpiarFiltrosCierre(){
  $('#selectJuegoCierre').val('0');
  $('#filtroMesaCierre').val('');
  $('#B_fecha_filtro_cierre').val('');
  $('#selectCasCierre').val('0');


}
