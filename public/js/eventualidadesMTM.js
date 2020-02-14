var eventoAEliminar;
var mtmEv=[];
$(document).ready(function(){
  $('#herramientasPaginacion').generarTitulo(1,10,40,function(){});
  $('#herramientasPaginacion').generarTitulo(1,10,40,function(){});
  var t= $('#tablaResultadosEvMTM tbody > tr .fecha_eventualidad');

    $.each(t, function(index, value){
      console.log($(this));
    $(this).text(convertirDate($(this).text()));
    });
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#movimientos').removeClass();
  $('#movimientos').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Intervenciones MTM');
  $('#opcIntervencionesMTM').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcIntervencionesMTM').addClass('opcionesSeleccionado');

  $('#mensajeExito').hide();
  $('#mensajeError').hide();

  $('#B_TipoMovEventualidad').val("");
  $('#B_fecha_ev').val("");
  $('#B_CasinoEv').val("");

  $('#agregarMTMEv').click(clickAgregarMTMEv);


  $('#evFecha').datetimepicker({
    todayBtn:  1,
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy, HH:ii',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 0,
    ignoreReadonly: true,
    minuteStep: 5,
    container: $('main section'),
  });

  //agregar para que permita seleccionar fecha hasta hoy inclusive, FILTRO
   $(function(){
       $('#dtpFechaEv').datetimepicker({
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
   });


   $('#btn-buscarEventualidadMTM').trigger('click');
});
$('#cantidad').on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#aceptarCantEv').click();
    }
});

$('#fechaEv').on('change', function (e) {
  $(this).trigger('focusin');
})


//botón grande para generar la nueva eventualidad de máquina
$(document).on('click','#btn-nueva-evmaquina',function(e){

  e.preventDefault();

  var casino=0
  $('#tablaMTM tbody tr').remove();
  $('#tipoMov option').remove();

  //get para obtener los tipos de mov y llenar el select:
  $.get('eventualidadesMTM/tiposMovIntervMTM', function(data){

    $('#modalNuevaEvMTM .modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
    $('#modalNuevaEvMTM').modal('show');


    for (var i = 0; i < data.tipos_movimientos.length; i++) {
      $('#modalNuevaEvMTM #tipoMov')
          .append($('<option>')
          .val(data.tipos_movimientos[i].id_tipo_movimiento)
          .text(data.tipos_movimientos[i].descripcion)
        )};

  })

  $('#inputMTM').generarDataList("maquinas/obtenerMTMEnCasino/" + casino, 'maquinas','id_maquina','nro_admin',1,true);
  $('#modalNuevaEvMTM').find('#btn-impr').prop('disabled',true);

});

function clickAgregarMTMEv(e) {

  var id_maq = $('#inputMTM').attr('data-elemento-seleccionado');

  if (id_maq != 0) {
    $.get('http://' + window.location.host +"/maquinas/obtenerMTM/" + id_maq, function(data) {
      agregarMTMEv(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.marca, data.maquina.modelo, 1);
      $('#inputMTM').setearElementoSeleccionado(0 , "");
    });
  }
}

function agregarMTMEv(id_maquina, nro_admin) {

  var fila = $('<tr>').attr('id', id_maquina);
  var accion = $('<button>').addClass('btn btn-danger borrarMTMCargada')
                            .append($('<i>').addClass('fa fa-fw fa-trash'));


  fila.append($('<td>').text(nro_admin));
  fila.append($('<td>').append(accion));

  $('#tablaMTM tbody').append(fila);
  $('#modalNuevaEvMTM').find('#btn-impr').prop('disabled',false);
};

//botón imprimir dentro del modal
$(document).on('click','#btn-impr',function(e){

  mtmEv=[];
  $('#mensajeExito').hide();
  $('#mensajeCargarMTM').hide();

  var id_tipo_mov= $('#modalNuevaEvMTM').find('#tipoMov').val();
  //guardo en la variable todas las máq de la tabla
  var maquinas = $('#tablaMTM tbody > tr');

  //recorro el array de máquinas, recupero su id, lo guardo en la var global mtmEv
  $.each(maquinas, function(index, value){
    var maquina={
      id_maquina:$(this).attr('id')
    }
      mtmEv.push(maquina);
  });

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var formData = {
    id_tipo_movimiento: id_tipo_mov,
    maquinas: mtmEv,
    sentido: $('#sentidoMov').val()
  };

  $.ajax({
        type: 'POST',
        url: 'eventualidadesMTM/nuevaEventualidadMTM',
        data: formData,
        dataType: 'json',
        success: function (data) {

            $('#mensajeCargarMTM').hide();
            $('#mensajeExito h3').text('CARGA EXITOSA');
            $("#modalNuevaEvMTM").modal('hide');
            $('#mensajeExito h3').text('ÉXITO');
            $('#mensajeExito p').text('La Intervención fue creada EXITOSAMENTE');
            $('#mensajeExito').show();
            $('#btn-buscarEventualidadMTM').trigger('click');

            //1 si la planilla es generada desde el modal de carga,
            //y va a ser 0 si se genera desde el boton imprimir de la pag ppal
            window.open('eventualidadesMTM/imprimirEventualidadMTM/' + data + '/' + 1,'_blank');

          },
        error: function (data) {
            console.log('Error:',data);
            var response = JSON.parse(data.responseText);


            if(typeof response.tipo_movimiento !== 'undefined'
                || typeof response.maquinas !== 'undefined')
            {
              $("#modalNuevaEvMTM").animate({ scrollTop: 0 }, "slow");
            }

            if(typeof response.tipo_movimiento !== 'undefined'){
              mostrarErrorValidacion($('#tipomov'),response.tipo_movimiento[0]);
            }
            if(typeof response.maquinas !== 'undefined'){
              $('#mensajeCargarMTM').show();
            }
       }

    });
});

$(document).on('click','.borrarMTMCargada',function(e){
  $(this).parent().parent().remove();
});

//botón para cargar máquina
$(document).on('click', '.btn_cargarEvmtm', function(){
  $('#fechaEv').val("");
  $('#guardarEv').prop('disabled', true);
  $('#modalCargarMaqEv .modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#modalCargarMaqEv').modal('show');
  $('#juegoEv option').remove();
  $('#mensajeExitoCarga').hide();
  $('#mensajeCargarMTMarga').hide();
  $('#tablaCargarMTM tbody tr').remove();
  $('#tablaCargarContadores tbody tr').remove();
  $('#modalCargarMaqEv #myModalLabel').text('CARGAR MTMs')
  $('#select_tevent').prop('disabled',false);
  $('#fechaEv').prop('disabled',false);
  $('#macEv').prop('disabled',false);
  $('#sectorRelevadoEv').prop('disabled',false);
  $('#islaRelevadaEv').prop('disabled',false);

  //BORRO LOS ERRORES
  ocultarErrorValidacion($('#apuestaEv'));
  ocultarErrorValidacion($('#creditosEv'));
  ocultarErrorValidacion($('#denominacionEv'));
  ocultarErrorValidacion($('#devolucionEv'));
  ocultarErrorValidacion($('#apuestaEv'));
  ocultarErrorValidacion($('#fiscalizadorEv'));
  ocultarErrorValidacion($('#cant_lineasEv'));
  ocultarErrorValidacion($('#select_tevent'));
  ocultarErrorValidacion($('#fechaEv'));
  ocultarErrorValidacion($('#macEv'));


  var id_log_mov= $(this).val();
  $('#modalCargarMaqEv #id_mov').val(id_log_mov);
  $('#modalCargarMaqEv #select_tevent option').remove();

  $.get('eventualidadesMTM/relevamientosEvMTM/' + id_log_mov, function(data){
    console.log('88',data);
        var tablaCarga=$('#tablaCargarMTM tbody');
        //hay máq cargadas
        for (var i = 0; i < data.maquinas.length; i++) {
          var fila= $('<tr>');
          var maq= data.maquinas[i].nro_admin;
          var dibujo = 'fa-upload';
          if(data.maquinas[i].estado.id_estado_relevamiento != 1){
            dibujo = 'fa-pencil-alt';
          }
          fila.append($('<td>')
              .addClass('col-xs-8')
              .text(maq)
          );
          fila.append($('<td>')
              .addClass('col-xs-4')
              .append($('<button>')
              .append($('<i>')
              .addClass('fa').addClass('fa-fw').addClass(dibujo))
              .attr('type','button')
              .addClass('btn btn-info detalleMTM')
              .attr('id', data.maquinas[i].id_maquina)
              .attr('data-relevamiento',data.maquinas[i].id_relevamiento))
          );
          tablaCarga.append(fila);
        }

        $('#inputTipoMov').val(data.tipo_movimiento);
        $('#inputSentido').val(data.sentido);

        //completo el ficalizador de carga con datos que me trae el data
        if(data.fiscalizador_carga != null){
          $('#fiscaCargaEv').val(data.fiscalizador_carga.nombre).prop('disabled',true);
          $('#modalCargarMaqEv').find('#id_fiscaliz_carga').val(data.fiscalizador_carga.id_usuario)
        }
        else {
          $('#fiscaCargaEv').val('');
        }
        //genero la lista para seleccionar un fiscalizador en el input correspondiente
        $('#fiscalizadorEv').generarDataList("usuarios/buscarUsuariosPorNombreYCasino/" + data.casino.id_casino,'usuarios' ,'id_usuario','nombre',1,false);
        $('#fiscalizadorEv').setearElementoSeleccionado(0,"");
    })
});

//boton que cierra el modal, para que cierre los detalles de las mtm
$('#btn-closeCargar').click(function(e){
  $('#detallesMTM').hide();
  $('#btn-buscarEventualidadMTM').trigger('click');
});

//presiona el ojo de una máquina para cargar los detalles
$(document).on('click','.detalleMTM',function(){
  $('#fechaEv').val("");
  $('#macEv').val("");
  $('#islaRelevadaEv').val("");
  $('#sectorRelevadoEv').val("");
  $('#guardarEv').prop('disabled', true);
  $('#juegoEv option').remove();
  $('#mensajeErrorCargaEv').hide();
  $('#mensajeExitoCarga').hide();
  $('#modalCargarMaqEv #form1').trigger("reset");
  $('#tablaCargarContadores tbody tr').remove();

  //HABILITO LOS INPUTS
  $('#fechaEv').prop('disabled',false);
  $('#apuestaEv').prop('disabled',false);
  $('#devolucionEv').prop('disabled',false);
  $('#denominacionEv').prop('disabled',false);
  $('#creditosEv').prop('disabled',false);
  $('#cant_lineasEv').prop('disabled',false);
  $('#select_tevent').prop('disabled',false);
  $('#observacionesTomaEv').prop('disabled',false);
  $('#juegoEv').prop('disabled',false);
  $('#inputAdmin').prop('disabled',false);
  $('#macEv').prop('disabled',false);
  $('#islaRelevadaEv').prop('disabled',false);
  $('#sectorRelevadoEv').prop('disabled',false);

  //BORRO LOS ERRORES
  ocultarErrorValidacion($('#apuestaEv'));
  ocultarErrorValidacion($('#creditosEv'));
  ocultarErrorValidacion($('#denominacionEv'));
  ocultarErrorValidacion($('#devolucionEv'));
  ocultarErrorValidacion($('#apuestaEv'));
  ocultarErrorValidacion($('#fiscalizadorEv'));
  ocultarErrorValidacion($('#cant_lineasEv'));
  ocultarErrorValidacion($('#select_tevent'));
  ocultarErrorValidacion($('#fechaEv'));
  ocultarErrorValidacion($('#fiscalizadorEv'));
  ocultarErrorValidacion($('#macEv'));

  $('#modalCargarMaqEv #detallesMTM').show();
  var id_maq=$(this).attr('id');
  console.log('id_maquina', id_maq);

  $('#modalCargarMaqEv #id_maq').val(id_maq);

  var id_rel=$(this).attr('data-relevamiento');
  $.get('eventualidadesMTM/obtenerMTMEv/' + id_rel, function(data){
    cargarDatos(data);
  })
});


//funcion para cargar los datos de la maquina, donde c indica si ya viene alguna máq cargada o no(false)
function cargarDatos (data){
  $('#mensajeExitoCarga').hide();
  $('#juegoEv option').remove();
  $('#tablaCargarContadores tbody tr').remove();
  //siempre vienen estos datos
  $('#nro_islaEv').val(data.maquina.nro_isla);
  $('#inputAdmin').val(data.maquina.nro_admin);
  $('#nro_serieEv').val(data.maquina.nro_serie);
  $('#marcaEv').val(data.maquina.marca);
  $('#modeloEv').val(data.maquina.modelo);

  //desde aqui genero la tabla de contadores, que son de cant variable.
  var cont = "cont";
  var vcont="vcont"
  var fila2 = $('<tr>');

  for (var i = 1; i < 7; i++){
    var fila = fila2.clone();
    var p = data.maquina[cont + i];
    if(data.toma != null){
      var v = data.toma[vcont + i];
    }

    if (v!=null && p!= null) {
      fila.append($('<td>')
        .addClass('col-xs-6')
        .text(p)
      )
      .attr('data-contador',p)
      .append($('<td>')
        .addClass('col-xs-6')
        .append($('<input>')
          .prop('disabled',false)
          .addClass('valorModif form-control')
          .val(v).text(v)
        )
      );
      $('#tablaCargarContadores tbody').append(fila);
    }

    if (v==null && p!= null) {
      fila.append($('<td>')
        .addClass('col-xs-6')
        .text(p)
      )
      .attr('data-contador',p)
      .append($('<td>')
        .addClass('col-xs-6')
        .append($('<input>')
          .addClass('valorModif form-control')
          .val("").text(' ')
        )
      );
      $('#tablaCargarContadores tbody').append(fila);
    }
  }//fin del for de contadores

  for (var i = 0; i < data.juegos.length; i++) {
    $('#modalCargarMaqEv #juegoEv')
    .append($('<option>')
    .prop('disabled',false)
    .val(data.juegos[i].id_juego)
    .text(data.juegos[i].nombre_juego)
  )};

  if(data.fecha != null){ 
    $('#fechaEv').val(data.fecha);
  }
  if(data.cargador != null) { 
    $('#fiscaCargaEv').val(data.cargador.nombre).prop('disabled',true);
  }

  if(data.toma != null) {
    $('#apuestaEv').val(data.toma.apuesta_max);
    $('#devolucionEv').val(data.toma.porcentaje_devolucion);
    $('#denominacionEv').val(data.toma.denominacion);
    $('#creditosEv').val(data.toma.cant_creditos);
    $('#cant_lineasEv').val(data.toma.cant_creditos);
    $('#observacionesTomaEv').val(data.toma.observaciones);
    $('#macEv').val(data.toma.mac);
    $('#sectorRelevadoEv').val(data.toma.descripcion_sector_relevado);
    $('#islaRelevadaEv').val(data.toma.nro_isla_relevada);
  }

  $('#inputAdmin').prop('disabled',true);

  if(data.tipo_movimiento!=null){
    $('#select_tevent').val(data.tipo_movimiento.id_tipo_movimiento);
  }
  
  if(data.fiscalizador!=null){
    $('#fiscalizadorEv').setearElementoSeleccionado(data.fiscalizador.id_usuario,data.fiscalizador.nombre);
  }

  agregarProgresivos(data.progresivos);/*movRelProgresivos.blade.php*/
  $('#guardarEv').prop('disabled', false);
};

//BOTÓN GUARDAR dentro del modal cargar eventualidad
$(document).on('click','#guardarEv',function(){

  $('#mensajeErrorCarga').hide();

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var  id_log_movimiento = $('#modalCargarMaqEv').find('#id_mov').val();
  var id_maq = $('#modalCargarMaqEv').find('#id_maq').val();
  var  id_cargador= $('#modalCargarMaqEv').find('#id_fiscaliz_carga').val();
  var  id_fiscalizador= $('#fiscalizadorEv').obtenerElementoSeleccionado();
  var  id_maquina= $('#modalCargarMaqEv').find('#id_maq').val();
  var  contadores=[];
  var  tipo_movimiento= $('#modalCargarMaqEv #select_tevent option:selected').val();
  var  juego= $('#juegoEv').val();
  var  apuesta_max= $('#apuestaEv').val();
  var  cant_lineas= $('#cant_lineasEv').val();
  var  dev= $('#devolucionEv').val();
  var  denominacion= $('#denominacionEv').val();
  var  cant_creditos= $('#creditosEv').val();
  var  fecha_sala= $('#modalCargarMaqEv').find('#fecha_ejecucionEv').val();
  var  observaciones= $('#observacionesTomaEv').val();
  var  tabla = $('#tablaCargarContadores tbody > tr');
  var  mac = $('#macEv').val();
  var  islaRelevadaEv = $('#islaRelevadaEv').val();
  var  sectorRelevadoEv = $('#sectorRelevadoEv').val();

  $.each(tabla, function(index, value){

    var cont={
      nombre: $(this).attr('data-contador'),
      valor: $(this).find('.valorModif').val()
    }
    contadores.push(cont);
  });

  var formData={
    id_log_movimiento: id_log_movimiento,
    id_maquina: id_maq,
    id_cargador: id_cargador,
    id_fiscalizador: id_fiscalizador,
    id_maquina: id_maquina,
    contadores: contadores,
    juego: juego,
    apuesta_max: apuesta_max,
    cant_lineas: cant_lineas,
    porcentaje_devolucion: dev,
    denominacion: denominacion,
    cant_creditos: cant_creditos,
    fecha_sala: fecha_sala,
    tipo_movimiento: tipo_movimiento,
    observaciones: observaciones,
    mac: mac,
    islaRelevadaEv: islaRelevadaEv,
    sectorRelevadoEv: sectorRelevadoEv,
    progresivos: obtenerDatosProgresivos() /*movRelProgresivos.blade.php*/
  }


  $.ajax({
    type: 'POST',
    url: 'eventualidadesMTM/cargarEventualidadMTM',
    data: formData,
    dataType: 'json',
    success: function (data) {

      var nro_Admin=$('#inputAdmin').val();

      $('#mensajeErrorCarga').hide();
      $('#modalCargarMaqEv #detallesMTM').hide();
      $('#modalCargarMaqEv #fechaEv').val(' ');
      $('#modalCargarMaqEv #fiscalizadorEv').val(' ');
      $('#modalCargarMaqEv #select_tevent').val('');
      $('#modalCargarMaqEv #macEv').val('');
      $('#modalCargarMaqEv #islaRelevadaEv').val('');
      $('#modalCargarMaqEv #sectorRelevadoEv').val('');
      // $('#tablaMaquinasFiscalizacion').find('.listo[value="'+maq+'"]').show();
      $('#mensajeExito h3').text('ÉXITO DE CARGA');
      $('#mensajeExito p').text(' ');
      $('#mensajeExitoCarga').show();
      $('#'+id_log_movimiento).find('.btn_borrarEvmtm').remove();
      $('#'+id_log_movimiento).find('.btn_validarEvmtm').show();
      $('#'+id_log_movimiento).find('.btn_imprimirEvmtm').show();

      // $('#modalCargarRelMov .cargarMaq').prop('disabled', false);
      $('#guardarEv').prop('disabled', true);

      $('#modalCargarMaqEv #tablaCargarMTM').find('.detalleMTM').attr('id',data.id_relevamiento);
      //BORRO LOS ERRORES
      ocultarErrorValidacion($('#apuestaEv'));
      ocultarErrorValidacion($('#creditosEv'));
      ocultarErrorValidacion($('#denominacionEv'));
      ocultarErrorValidacion($('#devolucionEv'));
      ocultarErrorValidacion($('#apuestaEv'));
      ocultarErrorValidacion($('#fiscalizadorEv'));
      ocultarErrorValidacion($('#cant_lineasEv'));
      ocultarErrorValidacion($('#select_tevent'));
      ocultarErrorValidacion($('#fechaEv'));
      ocultarErrorValidacion($('#macEv'));

//      var maq=$('#modalCargarMaqEv').find('#id_maq').val();

//      $('#modalCargarMaqEv').find('#id_maq').val();

      var boton = $('#modalCargarMaqEv')
      .find('.detalleMTM[id='+id_maq+']')[0];
      $(boton).empty();
      $(boton).append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt'));

      var cantbotones = $('#modalCargarMaqEv')
      .find('.detalleMTM').size();

      var cantlapices = $('#modalCargarMaqEv')
      .find('.detalleMTM').find('.fa-pencil-alt').size();

      //Actualizo el boton de la pantalla principal
      //Todos fueron cargados.
      if(cantbotones == cantlapices){
        var btn_menu = $('.btn_cargarEvmtm[value='+id_log_movimiento+']');
        btn_menu.empty();
        btn_menu.append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt'));
      }


    },

    error: function (data)
    {
      console.log('ERROR');
      console.log(data);

      var response = JSON.parse(data.responseText);


      if(    typeof response.apuesta_max !== 'undefined'
      || typeof response.cant_lineas !== 'undefined'
      || typeof response.cant_creditos !== 'undefined'
      || typeof response.devolucion !== 'undefined'
      || typeof response.denominacion!== 'undefined'
      || typeof response.juego !== 'undefined'
      || typeof response.id_fiscalizador !== 'undefined'
      || typeof response.fecha_sala !== 'undefined'
      || typeof response.tipo_movimiento !== 'undefined'
      || typeof response.mac !== 'undefined')
      {
        $("#modalCargarMaqEv").animate({ scrollTop: 0 }, "slow");
      }

      if(typeof response.apuesta_max !== 'undefined'){
        mostrarErrorValidacion($('#apuestaEv'),response.apuesta_max[0]);
      }
      if(typeof response.cant_lineas !== 'undefined'){
        mostrarErrorValidacion($('#cant_lineasEv'),response.cant_lineas[0]);
      }
      if(typeof response.cant_creditos !== 'undefined'){
        mostrarErrorValidacion($('#creditosEv'),response.cant_creditos[0]);
        // $('#fecha').popover('show');
        // $('.popover').addClass('popAlerta');
      }
      if(typeof response.porcentaje_devolucion !== 'undefined'){
        mostrarErrorValidacion($('#devolucionEv'),response.porcentaje_devolucion[0]);
      }
      if(typeof response.denominacion !== 'undefined'){
        mostrarErrorValidacion($('#denominacionEv'),response.denominacion[0]);
      }
      if(typeof response.juego !== 'undefined'){
        mostrarErrorValidacion($('#juegoEv'),response.juego[0]);
      }
      if(typeof response.id_fiscalizador !== 'undefined'){
        mostrarErrorValidacion($('#fiscalizadorEv'),response.id_fiscalizador[0]);
      }
      if(typeof response.fecha_sala !== 'undefined'){
        mostrarErrorValidacion($('#fechaEv'),response.fecha_sala[0]);
      }
      if(typeof response.tipo_movimiento !== 'undefined'){
        mostrarErrorValidacion($('#select_tevent'),response.tipo_movimiento[0]);
      }
      if(typeof response.mac !== 'undefined'){
        mostrarErrorValidacion($('#macEv'),response.mac[0]);
      }
      if(typeof response.contadores !== 'undefined'){
        $('#mensajeErrorCargaEv').show();

      }


      var i = 0;
      var filaError = 0;
      $('#tablaCargarContadores tbody tr').each(function(){

        var error=' ';

        if(typeof response['contadores.'+ i +'.valor'] !== 'undefined'){
          filaError = i;
          mostrarErrorValidacion($(this).find('.valorModif'),response['contadores.'+ i +'.valor'][0]);
        }

        i++;
      }); //fin del each

    }

  })

});

//BOTÓN DE VALIDAR EN CADA FILA
$(document).on('click','.btn_validarEvmtm',function(){

  //oculto msjs
  $('#mensajeExito').hide();
  $('#mensajeErrorVal').hide();
  $('#mensajeExitoValidacion').hide();

  //Modificar los colores del modal
  $('#modalValidacionEventualidadMTM .modal-title').text('VALIDAR MÁQUINAS');
  $('#modalValidacionEventualidadMTM .modal-header').attr('style','background: #4FC3F7');
  $('#modalValidacionEventualidadMTM').modal('show');

  //ocultar y limpiar tabla
  $('#tablaMaquinasFiscalizacion tbody tr').remove();
  $('.detalleMaqVal').hide();
  $('#toma2').hide();
  $('.validarEv').prop('disabled', true);
  $('.errorEv').prop('disabled',true);
  $('#observacionesToma').hide();
  $('#observacionesAdmin').val('');
  //oculto botones de error y validacion porque voy a visar de a una
  $('#enviarValidarEv').hide();
  $('#errorValidacionEv').hide();

  // var id_fiscalizacion = $(this).attr('data-id-fiscalizacion');

  var id_log_mov=$(this).val();

  $.get('eventualidadesMTM/maquinasACargar/' + id_log_mov, function(data){

    var tablaMaquinasFiscalizacion=$('#tablaMaquinasFiscalizacion tbody');

    for (var i = 0; i < data.relevamientos.length; i++) {

        var fila= $('<tr>');

        fila.append($('<td>')
            .addClass('col-xs-8')
            .text(data.relevamientos[i].nro_admin)
            )
        if(data.relevamientos[i].id_estado_relevamiento == 4){

          fila.append($('<td>')
              .addClass('col-xs-2')
              .append($('<button>')
                  .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                  )
                  .attr('type','button')
                  .addClass('btn btn-info verMaquinaEv')
                  .attr('data-numadmin',data.relevamientos[i].nro_admin)
                  .attr('data-maquina',data.relevamientos[i].id_maquina)
                  .attr('data-relevamiento', data.relevamientos[i].id_relev_mov)
                  .attr('data-estado', data.relevamientos[i].id_estado_relevamiento))
              )  .append($('<td>').addClass('col-xs-2').append($('<i>')
                    .addClass('fa').addClass('fa-fw').addClass('fa-check').css('color','#4CAF50'))
                  );
          }
          else{
            fila.append($('<td>')
                .addClass('col-xs-2')
                .append($('<button>')
                    .append($('<i>')
                    .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                    )
                    .attr('type','button')
                    .addClass('btn btn-info verMaquinaEv')
                    .attr('data-numadmin',data.relevamientos[i].nro_admin)
                    .attr('data-maquina',data.relevamientos[i].id_maquina)
                    .attr('data-relevamiento', data.relevamientos[i].id_relev_mov)
                    .attr('data-estado', data.relevamientos[i].id_estado_relevamiento)

                ))

          }

        tablaMaquinasFiscalizacion.append(fila);
   }

  //guardo el id del movimiento en el input del modal
  $('#modalValidacionEventualidadMTM').find('#id_log_movimiento').val(id_log_mov);

  });

});

//botón para ver los detalles de una máquina en particular
$(document).on('click','.verMaquinaEv',function(){

  $('.detalleMaqVal').show();
  //marco el seleccionado
  $('#tablaMaquinasFiscalizacion tbody tr').css('background-color','#FFFFFF');
  $(this).parent().parent().css('background-color', '#E0E0E0');

  if($(this).attr('data-estado') == 4){
    $('#enviarValidarEv').hide();
    $('#errorValidacionEv').hide();
  }
  else{
  $('#enviarValidarEv').show();
  $('#errorValidacionEv').show();}

  var numadmin = $(this).attr('data-numadmin');
  var id_maquina = $(this).attr('data-maquina');
  var tablaContadores = $('#tablaValidarContadores tbody');
  var id_relevamiento = $(this).attr('data-relevamiento');
  $('#enviarValidarEv').val(id_relevamiento);

  //guardo el id_maquina en el input maquina del modal
  $('#modalValidacionEventualidadMTM').find('#maquina').val(id_maquina);
  $('#modalValidacionEventualidadMTM').find('#maquina').attr('numadmin',numadmin);
  $('#modalValidacionEventualidadMTM').find('#relevamiento').val(id_relevamiento);
  $('#mensajeExitoValidacion').hide();
  $('#sectorRelevadoVal').val('');
  $('#islaRelevadaVal').val('');

  // $('#mensajeExitoValidacion').hide();

  $('#tablaValidarContadores tbody tr').remove();
  $.get('eventualidadesMTM/obtenerMTMEv/' + id_relevamiento, function(data){
        console.log('aqui:',data);

      if (true) {
        //CARGA CAMPOS INPUT

        $('#f_cargaVal').val(data.cargador == null? '' : data.cargador.nombre);
        $('#f_tomaVal').val(data.fiscalizador == null? '' : data.fiscalizador.nombre);
        $('#tipo_movVal').val(data.tipo_movimiento.descripcion);
        $('#nro_adminVal').val(data.maquina.nro_admin);
        $('#nro_islaVal').val(data.maquina.nro_isla);
        $('#nro_serieVal').val(data.maquina.nro_serie);
        $('#marcaVal').val(data.maquina.marca);
        $('#modeloVal').val(data.maquina.modelo);
        $('#macVal').val(data.toma.mac);
        $('#fecha_Val').val(data.fecha);
        $('#sectorRelevadoVal').val(data.toma.descripcion_sector_relevado);
        $('#islaRelevadaVal').val(data.toma.nro_isla_relevada);

        //CARGAR LA TABLA DE CONTADORES, HASTA 6
        var cont = "cont";
        var vcont = "vcont";
        var fila1 = $('<tr>');

          for (var i = 1; i < 7; i++) {
                var fila = fila1.clone();
                var p = data.maquina[cont + i];
                var v = data.toma[vcont + i];
                if(v==null){
                  v="-"
                }

                if(p != null ){
                    fila.append($('<td>')
                        .addClass('col-xs-6')
                        .text(p))
                        .append($('<td>')
                        .addClass('col-xs-3')
                        .text(v)
                        );
                        $('#tercer_col').hide();
                        tablaContadores.append(fila);
                }
        }

        if(data.toma!=null){

        $('#juego').val(data.nombre_juego);
        $('#apuesta').val(data.toma.apuesta_max);
        $('#cant_lineas').val(data.toma.cant_lineas);
        $('#devolucion').val(data.toma.porcentaje_devolucion);
        $('#denominacion').val(data.toma.denominacion);
        $('#creditos').val(data.toma.cant_creditos);

        }

      $('#observacionesToma').show();
        if(data.toma.observaciones!=null){
            $('#observacionesToma').text(data.toma.observaciones);}
        else{
          $('#observacionesToma').text(' ');
        }
        //guardo el id_fiscalizacion en el boton enviarValidar
        // $('#modalValidacionEventualidadMTM').find('#enviarValidar').val(id_fiscalizacion);

        $('.detalleMaq').show();
        $('.validar').prop('disabled', false);
        $('.error').prop('disabled',false);

      }
      else {
        console.log('No hay datos de la Máquina:', data.maquina.nro_admin);
        $('#msj').text('No hay datos de la Máquina:', data.maquina.nro_admin);
        $("#modalValidacionEventualidadMTM").animate({ scrollTop: $('#mensajeErrorVal').offset().top }, "slow");
      }
  });

   $('#enviarValidarEv').prop('disabled',false);
   $('#errorValidacionEv').prop('disabled',false);

});

//botón validar dentro del modal validar
$(document).on('click', '#enviarValidarEv', function(){
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });
    $('.detalleMaqVal').hide();

    var id=$(this).val();
    var observacion= $('#observacionesAdmin').val();
    var formData={
      id_relev_mov: id,
      observacion: observacion,
    }


    $.ajax({
      type: 'POST',
      url: 'eventualidadesMTM/visarConObservacion',
      data: formData,
      dataType: 'json',
      success: function (data) {
        if(data.id_estado_relevamiento == 4){
          $('#mensajeExitoValidacion').show();
          $('#enviarValidarEv').hide();
          $('#errorValidarEv').hide();


          $('#tablaMaquinasFiscalizacion tbody tr').each(function()
          {
              var maq=$('#modalValidacionEventualidadMTM').find('#maquina').val();
              console.log('44',maq);
              var boton=$(this).find('.verMaquinaEv');
              //Deberia ser siempre true.
              if($(boton).attr('data-maquina') == maq)
              {
                $(boton).attr('data-estado',4);
                $(this).append($('<td>')
                .addClass('col-xs-2')
                .append($('<i>').addClass('fa fa-fw fa-check').css('color','#4CAF50')));
              }
            });
          };
        }
      ,
      error: function (data) {
        console.log('Error:', data);
      }
    });
      /*
    $.get('eventualidadesMTM/visar/' + id, function(data){
      if(data.id_estado_relevamiento == 4){
        $('#mensajeExitoValidacion').show();
        $('#enviarValidarEv').hide();
        $('#errorValidarEv').hide();


        $('#tablaMaquinasFiscalizacion tbody tr').each(function(){

            var maq=$(this).parent().find('.verMaquinaEv').attr('data-relevamiento');
            console.log('44',maq);
            if (maq == id){
              var cambio = $(this).parent().find('.verMaquinaEv');
              cambio.attr('data-estado',4);
              $(this).append($('<td>')
                  .addClass('col-xs-2')
                  .append($('<i>').addClass('fa fa-fw fa-check').css('color','#4CAF50')));
            }
        });
      }
*/
});

$('#modalValidacionEventualidadMTM').on('hidden.bs.modal', function() {
  $('#btn-buscarEventualidadMTM').trigger('click');
});

//botón ERROR dentro del modal validar
$(document).on('click', '#errorValidarEv', function(){

    $('.detalleMaqVal').hide();

});

//botón impŕimir de la tab la principal
$(document).on('click', '.btn_imprimirEvmtm', function(){

  var id_mov=$(this).val();
  //le envío 0 para que identifique que es la planilla completa
  window.open('eventualidadesMTM/imprimirEventualidadMTM/' + id_mov + '/' + 0,'_blank');

});

//Busqueda de eventos
$('#btn-buscarEventualidadMTM').click(function(e,pagina,tam,columna,orden){

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  e.preventDefault();

  const noTieneValor = function(val){
    const es_null = val === null;
    const es_undefined = typeof val === 'undefined';
    return es_null || es_undefined;
  }

  let sort_by = {
    columna: noTieneValor(columna)? $('#tablaResultadosEvMTM .activa').attr('value') : columna, 
    orden: noTieneValor(orden)?  $('#tablaResultadosEvMTM .activa').attr('estado') : orden
  };
  if(noTieneValor(sort_by.columna)){
    sort_by.columna = 'log_movimiento.fecha';
  }
  if(noTieneValor(sort_by.orden)){
    sort_by.orden = 'desc';
  }
  console.log(sort_by);
  const page = noTieneValor(pagina)? $('#herramientasPaginacion').getCurrentPage() : pagina;
  const page_size = noTieneValor(tam)? $('#herramientasPaginacion').getPageSize() : tam;
  var formData = {
    id_tipo_movimiento: $('#B_TipoMovEventualidad').val(),
    fecha: $('#fecha_eventualidad').val(),
    id_casino: $('#B_CasinoEv').val(),
    mtm: $('#B_mtmEv').val(),
    isla: $('#B_islaEv').val(),
    sentido: $('#B_SentidoEventualidad').val(),
    page: page,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
    type: 'POST',
    url: 'eventualidades/buscarEventualidadesMTMs',
    data: formData,
    dataType: 'json',

    success: function (response) {
      console.log('success', response);
      $('#herramientasPaginacion').generarTitulo(page,page_size,response.eventualidades.total,clickIndice);
      $('#tablaResultadosEvMTM #cuerpoTablaEvMTM tr').remove();
      const eventualidades = response.eventualidades.data;
      for (var i = 0; i < eventualidades.length; i++) {
        var filaEventualidad = generarFilaTabla(eventualidades[i], response.esControlador,response.esSuperUsuario);
        $('#cuerpoTablaEvMTM').append(filaEventualidad);
      }
      $('#herramientasPaginacion').generarIndices(page,page_size,response.eventualidades.total,clickIndice);
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscarEventualidadMTM').trigger('click',[pageNumber,tam,columna,orden]);
}



$("#modalValidacionEventualidadMTM").on('hidden.bs.modal', function () {
    $('#btn-buscarEventualidadMTM').trigger('click');
});
//Se generan filas en la tabla principal con las eventualidades encontradas
function generarFilaTabla(event,controlador,superusuario){
  const estado = event.id_estado_movimiento;
  let fila = $('#filaEjemploTablaEventualidades').clone().removeAttr('id');
  fila.attr('id',event.id_log_movimiento);
  fila.find('.fecha').text(convertirDate(event.fecha)).attr('title',event.fecha);
  fila.find('.tipo').text(event.descripcion).attr('title',event.descripcion);
  fila.find('.sentido').text(event.sentido).attr('title',event.sentido);
  fila.find('.estado').attr('title',event.estado_descripcion);
  let iclass = 'fa-exclamation';
  let color = 'rgb(255,255,0)';
  let icon = fila.find('.estado i');
  icon.removeClass('fa-exclamation');
  if(estado == 1)      { iclass = 'fa-envelope'  ; color = 'rgb(66,133,244)' ;} // Notificado
  else if(estado == 4) { iclass = 'fa-check'     ; color = 'rgb(76,175,80)'  ;} // Validado
  else if(estado == 6) { iclass = 'fa-plus'      ; color = 'rgb(150,150,150)';} // Creado
  else if(estado == 8) { iclass = 'fa-pencil-alt'; color = 'rgb(244,160,0)'  ;} // Cargando
  else                 { iclass = 'fa-times'     ; color = 'rgb(239,83,80)'  ;} // Cualquier otro
  icon.addClass(iclass).css('color',color);
  fila.find('.estado').attr('title',event.estado_descripcion);
  fila.find('.casino').text(event.nombre).attr('title',event.nombre);
  const islas = event.islas === null? '-' : event.islas;
  fila.find('.isla').text(islas).attr('title',islas);
  if(estado == 1) fila.find('.accion .btn_cargarEvmtm i').removeClass('fa-upload').addClass('fa-pencil-alt');
  fila.find('button').attr('data-casino',event.id_casino).val(event.id_log_movimiento);

  if(estado!=8 && estado!=6 && estado!=1){fila.find('.btn_validarEvmtm').hide(); fila.find('.btn_cargarEvmtm').hide();fila.find('.btn_borrarEvmtm').hide(); }
  if(controlador == 0 && !superusuario){fila.find('.btn_validarEvmtm').hide();fila.find('.btn_borrarEvmtm').hide();}
  if (controlador == 1 && estado==8 && !superusuario) {fila.find('.btn_validarEvmtm').hide()}
  if(controlador == 1 && estado == 6 && !superusuario){fila.find('.btn_validarEvmtm').hide(); fila.find('.btn_cargarEvmtm').hide();}
  if(controlador==1 && estado==1 && !superusuario){fila.find('.btn_cargarEvmtm').hide();}
  if(event.deprecado==1) fila.find('td').css('color','rgb(150,150,150)');

  return fila;
};

//botón de eliminar que esta dentro del modal de cargar en la lista de maquinas, sectores e islas
$(document).on('click','.btn_borrarEvmtm',function(e){
  //se abre un modal de advertencia
  $('#modalEliminarEventualidadMTM').modal('show');
  eventoAEliminar= $(this);
});

//Si presiona el botón eliminar dentro del modal de advertencia
$('#btn-eliminarEventMTM').click(function (e){

  var id= eventoAEliminar.val();

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  e.preventDefault();

    var formData = {
      id_log_movimiento: id

    }

  $.ajax({
    type: 'POST',
    url: 'eventualidadesMTM/eliminarEventualidadMTM',
    data: formData,
    dataType: 'json',

    success: function (data) {

      console.log('success', data);

      eventoAEliminar.parent().parent().remove();
      $('#modalEliminarEventualidadMTM').modal('hide');

    },
    error: function (data) {
      console.log('Error:', data);
    }
  });

});

$(document).on('click','#tablaResultadosEvMTM thead tr th[value]',function(e){
  $('#tablaResultadosEvMTM th').removeClass('activa');
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
  $('#tablaResultadosEvMTM th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});