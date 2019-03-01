var maq_seleccionadas=[];
var maq_selecc_denom=[];
var mtmParaBaja=[];
var casinos=[];
var cantidad_maquinas = []; //variable global, determina
var cant_validadas=0;

$(document).ready(function(){

  $('#collapseFiltros #B_nro_exp_org').val("");
  $('#collapseFiltros #B_nro_exp_interno').val("");
  $('#collapseFiltros #B_nro_exp_control').val("");
  $('#collapseFiltros #B_TipoMovimiento').val("0");
  $('#collapseFiltros #dtpFechaMov').val("");
  $('#collapseFiltros #dtpCasinoMov').val("0");
  $('#busqueda_maquina').val("");


  var prueba = window.location.pathname;

  if(prueba == '/movimientos'){

      $('#barraMaquinas').attr('aria-expanded','true');
      $('#maquinas').removeClass();
      $('#maquinas').addClass('subMenu1 collapse in');
      $('#procedimientos').removeClass();
      $('#procedimientos').addClass('subMenu2 collapse in');
      $('#movimientos').removeClass();
      $('#movimientos').addClass('subMenu3 collapse in');

      $('.tituloSeccionPantalla').text('Asignación de movimientos a relevar');
      $('#opcAsignacion').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
      $('#opcAsignacion').addClass('opcionesSeleccionado');

    $('#mensajeExito').hide();
    $('#mensajeError').hide();

    //PAGINACION
    $('#btn-buscarMovimiento').trigger('click',[1,10,'log_movimiento.fecha','desc']);

  }
  //Para agregar una máquina cuando la busco en un input
  $('#agregarMaq').click(clickAgregarMaq);
  $('#agregarMaq2').click(clickAgregarMaqDenominacion);
  $('#agregarMaqBaja').click(clickAgregarMaqBaja);
  $('#agregarIslaDen').click(clickAgregarIslaDen);
  $('#agregarSectorDen').click(clickAgregarSectorDen);

 //agregar para que permita seleccionar fecha hasta hoy inclusive
  $(function(){
      $('#dtpFechaMov').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'dd / mm / yyyy',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
        container:$('main section'),
      });
  });
  $(function(){
      $('#dtpFechaMDenom').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
        container:$('#modalDenominacion'),
      });
  });
  $(function(){
      $('#dtpFechaEgreso').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
        container:$('#modalLogMovimiento2'),
      });
  });
  $(function(){
      $('#dtpFechaIngreso').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2,
        container:$('#modalEnviarFiscalizarIngreso'),
      });
  });


}); //FIN DEL DOCUMENT READY

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

//BOTON GRANDE DE NUEVO INGRESO
$(document).on('click', '#btn-nuevo-movimiento', function(e){

  e.preventDefault();
  //limpio las opciones del select
  $('#selectCasinoIngreso option').not('.default1').remove();
  $('#tipo_movimiento_nuevo option').not('.default2').remove();

  //SETEO EN 0 EL SELECT DE CASINO
  $('#selectCasinoIngreso').val(3);
  $('#tipo_movimiento_nuevo').val(7);


  $('#mensajeExito').hide();
  $('#mensajeError').hide();

  $.get('movimientos/casinosYMovimientos', function(data){

      //carga el select de los casinos del modal
      for (var i = 0; i < data.casinos.length; i++) {

        $('#modalCas #selectCasinoIngreso')
        .append($('<option>')
        .prop('disabled',false)
        .val(data.casinos[i].id_casino)
        .text(data.casinos[i].nombre_casino))
      }
      //carga el select de los tipos de movimientos del modal
      for (var i = 0; i < data.tipos_movimientos.length; i++) {
        $('#modalCas #tipo_movimiento_nuevo')
        .append($('<option>')
        .prop('disabled',false)
        .val(data.tipos_movimientos[i].id_tipo_movimiento)
        .text(data.tipos_movimientos[i].descripcion))
      };

  });

    //ABRE MODAL QUE ME PERMITE ELEGIR EL CASINO AL QUE PERTENECE EL NUEVO MOV.
    $('#modalCas').modal('show');

});

//ACEPTA EL MODAL DE CASINO
$(document).on('click', '#aceptarCasinoIng', function(e) {

  $('#mensajeExito').hide();

    id_mov=$('#modalCas #tipo_movimiento_nuevo').val();
    id_cas=$('#modalCas #selectCasinoIngreso').val();

    var formData = {
      id_tipo_movimiento: id_mov,
      casino:id_cas
    }

    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });

    $.ajax({
      type: 'POST',
      url: 'movimientos/nuevoLogMovimiento',
      data: formData,
      dataType: 'json',

      success: function (data){

        //CREO LA NUEVA FILA DE MOVIMIENTO
        var movimiento = generarFilaTabla(data);
        $('#cuerpoTabla').append(movimiento);

        var t= $('#herramientasPaginacion').getPageSize();

        //recargo la pág para que aparezca el nuevo movimientos en la tabla de movimientos
        $('#btn-buscarMovimiento').trigger('click',[1,t,'log_movimiento.fecha','desc']);

        //ME PERMITE QUE SE EJECUTE EL COD. QUE MUESTRA LOS NOMBRES DE LOS BOT.
        $('[data-toggle="tooltip"]').tooltip();

        $('#mensajeExito h3').text('ÉXITO');
        $('#mensajeExito p').text('El Movimiento fue creado correctamente');

        $('#modalCas').modal('hide');
        $('#mensajeExito').show();


      },
      error: function(data){

          $('#mensajeError p').text('Debe seleccionar un casino');
          $('#mensajeError').show();

      }
    })

});

//MOSTRAR MODAL PARA INGRESO: BTN NUEVO INGRESO
$(document).on('click', '.nuevoIngreso', function() {

  var id_movimiento=$(this).closest('tr').attr('id');

  $('.modal-title').text('SELECCIÓN DE TIPO DE CARGA');

  $('input[name="carga"]').attr('checked', false);

  limpiarModal();
  habilitarControles(true);

  $('#btn-aceptar-ingreso').prop('disabled',true);
  $('#modalLogMovimiento #cantMaqCargar').hide();
  $('#modalLogMovimiento').find("#id_log_movimiento").val(id_movimiento);
  //estilo de modal, y lo muestra
  $('#modalLogMovimiento .modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#modalLogMovimiento').modal('show');

  $.get('movimientos/obtenerDatos/'+ id_movimiento, function(data){
      $('#conceptoExpediente').text(data.expediente.concepto);
      if(data.tipo!=null){
        $('#modalLogMovimiento #cantMaqCargar').show();

          if(data.tipo==1){
            $('#tipoManual').prop('checked',true).prop('disabled',true);
            $('#tipoCargaSel').prop('disabled',true);
          }

          if(data.tipo==2){
            $('#tipoCargaSel').prop('checked',true).prop('disabled',true);
            $('#tipoManual').prop('disabled',true);
          }

          $("#cant_maq").val(data.cantidad).prop('disabled',true);
          $('#btn-aceptar-ingreso').prop('disabled',false);
    }
    else{
          $('#tipoManual').prop('disabled',false);
          $('#tipoCargaSel').prop('disabled',false);
          $('#cant_maq').val(1).prop('disabled',false);
        }
  })

}); //FIN DE EL NUEVO INGRESO

//DETECTAR EL TIPO DE CARGA SELECCIONADO ES MASIVA
$('#tipoCargaSel').click(function(){

  var s=$('#modalLogMovimiento #tipoCargaSel').val();

  if(s==2){ //TIPO DE CARGA: MASIVA
    $('#modalLogMovimiento #cantMaqCargar').hide();}
    $('#btn-aceptar-ingreso').prop('disabled',false);
  });

//DETECTAR SI EL TIPO DE CARGA SELECCIONADO ES MANUAL
$('#tipoManual').click(function(){
  var s=$('#modalLogMovimiento #tipoManual').val();

  if(s==1){ //TIPO DE CARGA: MANUAL
    $('#modalLogMovimiento #cantMaqCargar').show();
    $('#btn-aceptar-ingreso').prop('disabled',false);}
  })

//BOTÓN ACEPTAR dentro del modal ingreso
$("#btn-aceptar-ingreso").click(function(e){

  var id=$("#id_log_movimiento").val();
  var cant_maq=$("#cant_maq").val();
  var t_carga=$('input:radio[name=carga]:checked').val();

  if (typeof cant_maq=="undefined" ) {

    $('#mensajeErrorCarga').text('Debe especificar la cantidad de máquinas que va a cargar');
    $('#mensajeErrorCarga').show();
  }

  else {
    var formData= {
      id_log_movimiento: id,
      cantMaq: cant_maq,
      tipoCarga: t_carga,

    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });

    $.ajax({
        type: 'POST',
        url: 'movimientos/guardarTipoCargaYCantMaq',
        data: formData,
        dataType: 'json',

          success: function (data){

            var tipo_carga=data.tipo_carga;
            //Busco la fila que contiene el id del movimiento indicado
            var fila= $("#tablaResultados tbody").find('#' + id);
            //seteo en el btn de carga el tipo de carga
            fila.find('.boton_cargar').attr("data-carga",tipo_carga);

            $('#modalLogMovimiento').modal('hide');
            fila.find('.boton_cargar').show();
            $('#' + id).find('.nuevoIngreso').attr('style', 'display:none');;

          },

          error: function(data){
            var response = data.responseJSON.errors;
            
          }
    })
  } //fin del else
}); //FIN DEL BTN ACEPTAR

//ABRIR MODAL DE NUEVA MÁQUINA
$(document).on('click', '.boton_cargar', function(e){

    e.preventDefault();
    $(this).tooltip('hide');

    var mov = $(this).val();
    $('#modalMaquina').find('#id_movimiento').val(mov);

    //Ver que tipo de carga de máqunas se hace.
    //MANUAL
    if($(this).attr('data-carga')==1){
        //muestra tab de maquinas y oculto el resto
      $.get('movimientos/obtenerDatos/'+ mov, function(data){

        eventoNuevo(data.cantidad, data.expediente);
      })

    }
    //MASIVA
    else {
      $('#modalCargaMasiva .modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
      $('#modalCargaMasiva').modal('show');
  }

});

function eventoNuevo(cantidad, expediente){

  limpiarModal();
  $('#mensajeExito').hide(); //oculto mensaje exito

  //Modificar los colores del modal
  $('.modal-title').text('NUEVA MÁQUINA TRAGAMONEDAS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
  $('#btn-guardar').removeClass('btn-warning');
  $('#btn-guardar').addClass('btn-success');
  $('#btn-guardar').text('CREAR MTM');
  $('#btn-guardar').val("nuevo");
  $('#btn-guardar').prop('disabled',false).show();
  $('#btn-guardar').css('display','inline-block');

  //como estoy creando id = 0
  $('#id_maquina').val(0);

  $('.seccion').hide();
  $('.navModal a').removeClass();
  $('#navMaquina').addClass('navModalActivo');
  $('#secMaquina').show();

  //Setear el expediente
  $('#M_expediente').val(expediente.id_expediente);
  $('#M_nro_exp_org').val(expediente.nro_exp_org).prop('readonly',true);
  $('#M_nro_exp_interno').val(expediente.nro_exp_interno).prop('readonly',true);
  $('#M_nro_exp_control').val(expediente.nro_exp_control).prop('readonly',true);

  //Setear la cantidad de máquinas pendientes
  if (cantidad == 1) {
      $('#maquinas_pendientes').text(' ' + cantidad + ' MÁQUINA PENDIENTE A CARGAR');
  }else {
      $('#maquinas_pendientes').text(' ' + cantidad + ' MÁQUINAS PENDIENTES A CARGAR');
  }

  $('#modalMaquina').modal('show');
}

//minimiza modal
$('#btn-minimizar').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
      $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});
//Fin del ingreso MANUAL

//ABRIR MODAL DE CARGA MASIVA
$('.cargar2').click(function(e){

  e.preventDefault();

    //Modificar los colores del modal
    $('.modal-title').text('| NUEVA CARGA MASIVA');
    $('#btn-guardar').removeClass('btn-warning');
    $('#btn-guardar').addClass('btn-success');
    $('#modalCargaMasiva').modal('show');
});

//MANDAR ARCHIVO PARA CARGA MASIVA.
$('#btn-carga-masiva').click(function(){

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  //tomo el archivo seleccionado para luego enviar a servidor
  var formData=new FormData();
  formData.append('file',$('#cargaMasiva')[0].files[0]);

  formData.append('id_casino' , $('#contenedorCargaMasiva').val());

  for(var pair of formData.entries()) {
   console.log(pair[0]+ ', '+ pair[1]);
  }

  $.ajax({
      type: 'POST',
      url: '/movimientos/cargaMasiva',
      data: formData,
      processData: false,
      contentType:false,
      cache:false,
      success: function (data){

          $('#frmCargaMasiva').trigger('reset');
          $('#modalCargaMasiva').modal('hide');

      },
      error: function(data){
         alert('error');},
    })
}); //FIN DEL POST PARA ENVIAR ARCHIVO DE C. MASIVA

// **************************************MODAL VOLVER A RELEVAR ********************************************************
$(document).on('click','.botonToma2',function(){
  $('#btn-enviar-egreso').hide();
  $('#btn-enviar-toma2').show();

  var id_casino=$(this).attr('data-casino');
  var id_mov=$(this).val();
  var t_mov=$(this).attr('data-tipo');
  var estado = $(this).attr('data-estado');

  $('.modal-title').text('CARGAR MÁQUINAS A RE-RELEVAR');
  $('#tablaMaquinasSeleccionadas tbody tr').remove();
  $('#modalLogMovimiento2').find('#tipo_movi').val(t_mov);
  $('#modalLogMovimiento2').find('#mov').val(id_mov);
  maq_seleccionadas=[];

  $('#inputMaq').generarDataList("maquinas/obtenerMTMMovimientos/"  + id_casino + '/' + '8' + '/' + id_mov  ,'maquinas','id_maquina','nro_admin',1,true);

  $('#tablaMaquinasSeleccionadas tbody tr').remove();
  $('#isla_layout').hide();
  $('#btn-enviar-egreso').prop('disabled',true);

  $('#btn-pausar').hide();
  $('#modalLogMovimiento2').modal('show');

  $('#mensajeExito').hide();
  $('#mensajeFiscalizacionError').hide();
  $('#btn-enviar-toma2').val(id_mov);
});

//Envía a fiscalizar, finaliza carga
$(document).on('click','#btn-enviar-toma2',function(e){

  var tipo=$('#modalLogMovimiento2').find('#tipo_movi').val();
  var id_log_movimiento = $(this).val();
  var maquinas = $('#tablaMaquinasSeleccionadas tbody > tr');

  $.each(maquinas, function(index, value){
    var maquina={
      id_maquina:$(this).attr('id')
    }
      maq_seleccionadas.push(maquina);
  });
  enviarFiscalizarToma2(id_log_movimiento,maq_seleccionadas);
});

//POST
function enviarFiscalizarToma2(id_mov,maq){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var formData = {
    id_log_movimiento: id_mov,
    maquinas: maq,
    carga_finalizada:'toma2',
    es_reingreso:'toma2'
  }

  $.ajax({
        type: 'POST',
        url: 'movimientos/guardarRelevamientosMovimientos',
        data: formData,
        dataType: 'json',
        success: function (data) {
          $('#mensajeExito h3').text('ÉXITO');
          $('#mensajeExito p').text('Las máquinas han sido enviadas correctamente');
          $("#modalLogMovimiento2").modal('hide');
          $('#mensajeExito').show();
          },
        error: function (data) {
            console.log('Error: No fue posible enviar a fiscalizar las máquinas cargadas');
            $('#mensajeFiscalizacionError').show();
            $("#modalLogMovimiento2").animate({ scrollTop: $('#mensajeFiscalizacionError').offset().top }, "slow");
        },
      });
};

// **************************************MODAL NUEVO EGRESO ********************************************************

$(document).on('click','.nuevoEgreso',function(){
  $('#btn-enviar-egreso').show();
  $('#btn-enviar-toma2').hide();
  ocultarErrorValidacion($('#B_fecha_egreso'));
  $('#B_fecha_egreso').val(' ');

  var id_casino=$(this).attr('data-casino');
  var id_mov=$(this).val();
  var t_mov=$(this).attr('data-tipo');

  $('.modal-title').text('CARGAR MÁQUINAS A EGRESAR');
  $('#tablaMaquinasSeleccionadas tbody tr').remove();
  $('#modalLogMovimiento2').find('#tipo_movi').val(t_mov);
  $('#modalLogMovimiento2').find('#mov').val(id_mov);
  maq_seleccionadas=[];

  $('#inputMaq').generarDataList("maquinas/obtenerMTMMovimientos/"  + id_casino + '/' + t_mov + '/' + id_mov  ,'maquinas','id_maquina','nro_admin',1,true);
  if(t_mov == 8){
      $('.modal-title').text('SELECCIÓN DE MTMs PARA REINGRESO');
  }
  if(t_mov!=4){
      //busca máquinas ya cargadas
      $.get('movimientos/buscarMaquinasMovimiento/' + id_mov, function(data){

        $('#tablaMaquinasSeleccionadas tbody tr').remove();

        if(data.maquinas.length != 0){
          for (var i = 0; i < data.maquinas.length; i++) {

              agregarMaq(data.maquinas[i].maquina.id_maquina, data.maquinas[i].maquina.nro_admin,
                         data.maquinas[i].maquina.marca, data.maquinas[i].maquina.modelo,
                         data.maquinas[i].maquina.nro_isla);

              $('#inputMaq').setearElementoSeleccionado(0 , "");
              $('#isla_layout').hide();
              $('#modalLogMovimiento2').modal('show')
          }//fin FOR
        }
        else{ //no hay máquinas

            $('#tablaMaquinasSeleccionadas tbody tr').remove();
            $('#isla_layout').hide();
            $('#btn-enviar-egreso').prop('disabled',true);
            if(t_mov==8){
              $('#btn-pausar').hide();}
            else{
              $('#btn-pausar').prop('disabled',true);}
              $('#modalLogMovimiento2').modal('show');
            }
      });
  }
  else{ //CAMBIO LAYOUT
      $('.modal-title').text('SELECCIÓN DE MTMs QUE CAMBIARON DE ISLA');
      $.get('movimientos/mostrarMaquinasMovimientoLogClick/' + id_mov , function(data){

        $('#tablaMaquinasSeleccionadas tbody tr').remove();

        if(data!=null){
            for (var i = 0; i < data.length; i++) {
                agregarMaq(data[i].id_maquina, data[i].nro_admin,
                           data[i].marca, data[i].modelo, data[i].nro_isla,
                           data[i].nombre_juego, data[i].nro_serie);
            }

          $('#modalLogMovimiento2').modal('show');
            // $('#inputMaq').setearElementoSeleccionado(0 , "");

        }
        else {
          $('#tablaMaquinasSeleccionadas tbody tr').remove();
          $('#modalLogMovimiento2').modal('show');
          $('#btn-enviar-egreso').prop('disabled',true);
          $('#btn-pausar').prop('disabled',true);
          // $('#inputMaq').setearElementoSeleccionado(0 , "");
        }
    });
  }

  $('#mensajeExito').hide();
  $('#mensajeFiscalizacionError').hide();
  $('#btn-enviar-egreso').val(id_mov);

});

//click mas para agregar máquinas
function clickAgregarMaq(e) {
  var id_maquina = $('#inputMaq').attr('data-elemento-seleccionado');

  if (id_maquina != 0) {
    $.get('http://' + window.location.host +"/maquinas/obtenerMTM/" + id_maquina, function(data) {
      agregarMaq(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.marca ,
                 data.maquina.modelo, data.isla.nro_isla,data.juego_activo.nombre_juego,
                 data.maquina.nro_serie);
      $('#inputMaq').setearElementoSeleccionado(0 , "");
      console.log('555:',data);

    });
  }
}

function agregarMaq(id_maquina, nro_admin, marca, modelo, isla, nombre_juego,nro_serie) {

  var tipo=$('#modalLogMovimiento2').find('#tipo_movi').val();

  var fila = $('<tr>').attr('id', id_maquina);
  var accion = $('<button>').addClass('btn btn-danger borrarMaq')
                            .append($('<i>').addClass('fa fa-fw fa-trash'));
  //tipo de movimiento 4: CAMBIO LAYOUT
  if(tipo!=4){

    //Se agregan todas las columnas para la fila
    fila.append($('<td>').text(nro_admin));
    fila.append($('<td>').text(marca));
    fila.append($('<td>').text(modelo));
    fila.append($('<td>').text(nombre_juego));
    fila.append($('<td>').text(nro_serie));
    fila.append($('<td>').append(accion));

  }else{
    fila.append($('<td>').text(nro_admin));
    fila.append($('<td>').text(marca));
    fila.append($('<td>').text(modelo));
    fila.append($('<td>').text(isla));
    fila.append($('<td>').text(nombre_juego));
    fila.append($('<td>').text(nro_serie));
    if(isla!=null){
      fila.append($('<td>').append(accion));
    }
  }
    //Agregar fila a la tabla
    $('#tablaMaquinasSeleccionadas tbody').append(fila);
    if(tipo!=8){
      $('#btn-pausar').prop('disabled',false);
    }
    $('#btn-enviar-egreso').prop('disabled',false);

};

//Envía a fiscalizar, finaliza carga
$(document).on('click','#btn-enviar-egreso',function(e){

  var tipo=$('#modalLogMovimiento2').find('#tipo_movi').val();
  var id_log_movimiento = $(this).val();
  var fecha = $('#B_fecha_egreso').val();
  var maquinas = $('#tablaMaquinasSeleccionadas tbody > tr');

  $.each(maquinas, function(index, value){
    var maquina={
      id_maquina:$(this).attr('id')
    }
      maq_seleccionadas.push(maquina);
  });
  //USA LA FC DE POST, ENVIANDO EN TRUE EL ATRIBUTO DE CARGA FINALIZADA
  //enviarFiscalizarEgreso(id_log_movimiento, maquinas, true);
  if(tipo!=8){
    enviarFiscalizar(id_log_movimiento,maq_seleccionadas, fecha, true,false);
  }else{//es reingreso
    enviarFiscalizar(id_log_movimiento,maq_seleccionadas, fecha, true,true);
  }
});

//Pausa la carga de maquinas a fiscalizar
$('#btn-pausar').click(function(e){

  var tipo=$('#modalLogMovimiento2').find('#tipo_movi').val();
  var id_log_movimiento = $('#modalLogMovimiento2').find('#mov').val();
  var maquinas = $('#tablaMaquinasSeleccionadas tbody > tr');
  var fecha = $('#B_fecha_egreso').val();


  $.each(maquinas, function(index, value){
    var maquina={
      id_maquina:$(this).attr('id')
    }
      maq_seleccionadas.push(maquina);
  });

  //USA LA FC DE POST, ENVIANDO EN FALSE EL ATRIBUTO DE CARGA FINALIZADA
  //enviarFiscalizarEgreso(id_log_movimiento, maquinas, false);
  if(tipo!=8){
    enviarFiscalizar(id_log_movimiento,maq_seleccionadas, fecha, false,false);
  }else{
    enviarFiscalizar(id_log_movimiento,maq_seleccionadas, fecha, false,true);
  }
});

//POST
function enviarFiscalizar(id_mov,maq,fecha, fin,reingreso){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var formData = {
    id_log_movimiento: id_mov,
    maquinas: maq,
    carga_finalizada: fin,
    es_reingreso:reingreso,
    fecha:fecha,
  }

  $.ajax({
        type: 'POST',
        url: 'movimientos/guardarRelevamientosMovimientos',
        data: formData,
        dataType: 'json',
        success: function (data) {
          if(fin==true){
            $('#mensajeExito h3').text('ÉXITO');
            $('#mensajeExito p').text('Las máquinas han sido enviadas');
            $("#modalLogMovimiento2").modal('hide');
            $('#mensajeExito').show();
          }
            else{
              $('#mensajeExito h3').text('CARGA PAUSADA');
              $('#mensajeExito p').text('Las máquinas han sido guardadas correctamente');
              $("#modalLogMovimiento2").modal('hide');
              $('#mensajeExito').show();
            }
          },
        error: function (data) {
          var response = data.responseJSON.errors;

          if(typeof response.fecha !== 'undefined'){
            mostrarErrorValidacion($('#B_fecha_egreso'),response.fecha[0],false);
          }
          else{
            $('#mensajeFiscalizacionError').show();
            $("#modalLogMovimiento2").animate({ scrollTop: $('#mensajeFiscalizacionError').offset().top }, "slow");
          }

        },
      });
};
//*******************************************************************************************************************************


//*************BOTÓN NUEVO DE MOVIMIENTO: DENOMINACION **************************************************

$(document).on('click','.modificarDenominacion',function(){

  var casino=$(this).attr('data-cas');
  var mov=$(this).val();
  var tmov=$(this).attr('data-tmov');
  $('#denom_comun').val(' ');
  $('#devol_comun').val(' ');
  $('#unidad_comun').val(' ');
  ocultarErrorValidacion($('#B_fecha_denom'));
  $('#B_fecha_denom').val(' ');


  $('#modalDenominacion').find('#id_t_mov').val(tmov);
  $('#modalDenominacion').find('#id_mov_denominacion').val(mov);

  $('#inputMaq2').generarDataList("maquinas/obtenerMTMEnCasinoMovimientos/" + casino + '/' + mov, 'maquinas','id_maquina','nro_admin',1,true);
  $('#inputIslaDen').generarDataList("eventualidades/obtenerIslaEnCasino/" + 0, 'islas', 'id_isla','nro_isla',1,true);
  $('#inputSectorDen').generarDataList("eventualidades/obtenerSectorEnCasino/" + 0, 'sectores','id_sector','descripcion',1,true);


  switch (tmov) {
    case '5'://denominación
          $('.modal-title').text('ASIGNACIÓN DE CAMBIO DE DENOMINACIÓN DE JUEGO');
          $('#segunda_columna').show().text('DENOMINACIÓN');
          $('#tercer_columna').show();
          $('#denom_comun').show();
          $('#unidad_comun').show();
          $('#devol_comun').hide();
          $('#todosDen').show();
          $('#aplicar').show();
          $('#aplicar1').hide();
          $('#todosDev').hide();
          $('#nuevaDen').show();
          $('#nuevaDev').hide();
          $('#nuevaUni').show();
          $('#busqSector').show();
          $('#busqIsla').show();
          $('#B_fecha_denom').show();


      break;
      case '6': //devolución
          $('.modal-title').text('ASIGNACIÓN DE CAMBIO DE %DEV DE JUEGO');
          $('#segunda_columna').show().text('% DEVOLUCIÓN');
          $('#tercer_columna').hide();
          $('#denom_comun').hide();
          $('#unidad_comun').hide();
          $('#devol_comun').show();
          $('#todosDen').hide();
          $('#todosDev').show();
          $('#aplicar').hide();
          $('#aplicar1').show();
          $('#nuevaDen').hide();
          $('#nuevaDev').show();
          $('#nuevaUni').hide();
          $('#busqSector').show();
          $('#busqIsla').show();
          $('#B_fecha_denom').show();


      break;
      case '7': //juego
          $('.modal-title').text('ASIGNACIÓN DE CAMBIO DE JUEGO');
          $('#segunda_columna').show().text('JUEGO');
          $('#tercer_columna').hide();
          $('#denom_comun').hide();
          $('#unidad_comun').hide();
          $('#devol_comun').hide();
          $('#todosDen').hide();
          $('#todosDev').hide();
          $('#nuevaDen').hide();
          $('#nuevaDev').hide();
          $('#nuevaUni').hide();
          $('#busqSector').hide();
          $('#busqIsla').hide();
          $('#aplicar').hide();
          $('#aplicar1').hide();
          $('#dtpFechaMDenom').show();

      break;
      default:
        $('.modal-title').text('SELECCIÓN DE MTMs PARA ENVÍO A FISCALIZAR');
        break;

  }
  $('#tablaDenominacion tbody tr').remove();
   $.get('movimientos/buscarMaquinasMovimiento/' + mov, function(data){

       if(data.maquinas.length != 0){
        //console.log('data:',data.unidades);
        console.log('77',data);
         for (var i = 0; i < data.maquinas.length; i++) {
           agregarMaqDenominacion(data.maquinas[i].maquina.id_maquina, data.maquinas[i].maquina.nro_admin,
                                  data.maquinas[i].maquina.denominacion, data.maquinas[i].juegos,
                                  data.maquinas[i].juego_seleccionado.id_juego,data.maquinas[i].juego_seleccionado.nombre_juego,
                                  data.maquinas[i].maquina.porcentaje_devolucion,
                                  data.maquinas[i].maquina.id_unidad_medida, data.unidades, 2);

           $('#inputMaq2').setearElementoSeleccionado(0 , "");

         }//fin FOR
       }
       else{

         $('#tablaDenominacion tbody tr').remove();
         $('#btn-enviar-denom').prop('disabled', true);
         $('#btn-pausar-denom').prop('disabled', true);
       }
    });

  $('#modalDenominacion').modal('show');
  $('#mensajeExito').hide();
  $('#mensajeFiscalizacionError2').hide();
  $('#btn-enviar-denom').val(mov);

});

//crea tabla
function clickAgregarMaqDenominacion(e) {

  var id_maq = $('#inputMaq2').attr('data-elemento-seleccionado');

  if (id_maq != 0) {
    $.get('http://' + window.location.host +"/movimientos/obtenerMTM/" + id_maq, function(data) {
      agregarMDenominacion(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.denominacion,
        data.maquina.porcentaje_devolucion,data.maquina.id_unidad_medida, data.unidades, 1);

        $('#inputMaq2').setearElementoSeleccionado(0 , "");

    });
  }

}

function agregarMaqDenominacion(id_maquina, nro_admin, denom, juegos, id_juego,nombre_juego, dev, unidad_seleccionada, unidades, p) {

  //console.log('LLEGA:',id_juego);
  var fila = $('<tr>').attr('id', id_maquina);
  var accion = $('<button>').addClass('btn btn-danger borrarMaq')
                              .append($('<i>').addClass('fa fa-fw fa-trash'));
  var t_mov = $('#modalDenominacion').find('#id_t_mov').val();

  //Se agregan todas las columnas para la fila
  fila.append($('<td>').text(nro_admin))
  //TIPO DE MOVIMIENTO ES DENOMINACION:
  if(t_mov==5){

    fila.append($('<td>')
        .append($('<input>')
        .addClass('denominacion_modificada form-control')
        .val(denom)))

  var select = $('<select>').addClass('unidad_denominacion form-control');

    for (var j = 0; j < unidades.length; j++) {
        var tipo = unidades[j].descripcion;
        var id = unidades[j].id_unidad_medida;
        select.append($('<option>').text(tipo).val(id));
    }
    select.val(unidad_seleccionada);
    fila.append($('<td>').append(select));

  };

  //TIPO DE MOVIMIENTO ES %DEVOLUCION:
  if(t_mov==6){

    fila.append($('<td>')
      .append($('<input>')
      .addClass('devolucion_modificada form-control')
          .val(dev)));
  };
  //TIPO DE MOVIMIENTO ES JUEGO:
  if(t_mov==7){
    //select de juego
  var input = $('<input>').addClass('juego_modif form-control').attr('placeholder', "Nombre Juego");

  //   //input denominacion por si es multijuego
  // var denominacion = $('<input>').addClass('denominacion_modificada form-control');
  //
  // var select2 = $('<select>').addClass('unidad_denominacion');
  //
  //   for (var j = 0; j <unidades.length; j++) {
  //       var tipo = unidades[j].descripcion;
  //       var id = unidades[j].id_unidad_medida;
  //       select2.append($('<option>').text(tipo).val(id));
  //   }
  //   select2.val(unidad_seleccionada);
  //   fila.append($('<td>').append(select));
  //
  //   //input devolución por si es multijuego
  // var devolucion = $('<input>').addClass('devolución_modificada form-control').val(dev);

  fila.append($('<td>').append(input)); //falta el denom y el devol

  input.generarDataList("movimientos/buscarJuegoMovimientos", 'juegos','id_juego','nombre_juego',1);

  input.setearElementoSeleccionado(id_juego,nombre_juego);

  //input.setearElementoSeleccionado(0,"");


  };
  //"p" indica si ya viene cargada la tabla o no, para agregar o no el boton de borrar
  if (p==1) {
    fila.append($('<td>').append(accion));
  }
    //Agregar fila a la tabla
    $('#tablaDenominacion tbody').append(fila);
    //Habilitar botones
    $('#btn-enviar-denom').prop('disabled', false);
    $('#btn-pausar-denom').prop('disabled', false);

};


function clickAgregarIslaDen(e){
  var id_isla = $('#inputIslaDen').attr('data-elemento-seleccionado');

  if (id_isla != 0) {
    $.get('movimientos/obtenerMaquinasIsla/' + id_isla, function(data) {

      console.log('ff', data);
      for (var i = 0; i < data.maquinas.length; i++) {

        agregarMDenominacion(data.maquinas[i].id_maquina, data.maquinas[i].nro_admin, data.maquinas[i].denominacion,
                                data.maquinas[i].porcentaje_devolucion,data.maquinas[i].id_unidad_medida, data.unidades, 1);
      }


        $('#inputIslaDen').setearElementoSeleccionado(0 , "");

    });
  }
}

function clickAgregarSectorDen(e){
  var id_isla = $('#inputSectorDen').attr('data-elemento-seleccionado');

  if (id_isla != 0) {
    $.get('movimientos/obtenerMaquinasSector/' + 0, function(data) {

      console.log('ff', data);
      for (var i = 0; i < data.maquinas[i].length; i++) {

        agregarMDenominacion(data.maquinas[i].id_maquina, data.maquinas[i].nro_admin, data.maquinas[i].denominacion,
                                data.maquinas[i].porcentaje_devolucion,data.maquinas[i].id_unidad_medida, data.unidades, 1);
      }
        $('#inputSectorDen').setearElementoSeleccionado(0 , "");

    });
  }
}

$('#btn-borrarTodo').on('click', function() {
    $('#tablaDenominacion tbody tr').remove();
});

function agregarMDenominacion(id_maquina, nro_admin, denom, dev, unidad_seleccionada, unidades, p) {

  //console.log('LLEGA:',id_juego);
  var fila = $('<tr>').attr('id', id_maquina);
  var accion = $('<button>').addClass('btn btn-danger borrarMaq')
                              .append($('<i>').addClass('fa fa-fw fa-trash'));
  var t_mov = $('#modalDenominacion').find('#id_t_mov').val();

  // se busca migrar la denominacion a valores validos, por lo que se la convierte a numerico
  denFloat=denominacionToFloat(denom)
  //Se agregan todas las columnas para la fila
  fila.append($('<td>').text(nro_admin))
  //TIPO DE MOVIMIENTO ES DENOMINACION:
  if(t_mov==5){

    fila.append($('<td>')
        .append($('<input>')
        .addClass('denominacion_modificada form-control')
        .attr("type","number").attr("step","0.01").attr("min","0.01")
        .val( denFloat)))

  // var select = $('<select>').addClass('unidad_denominacion form-control');

  //   for (var j = 0; j < unidades.length; j++) {
  //       var tipo = unidades[j].descripcion;
  //       var id = unidades[j].id_unidad_medida;
  //       select.append($('<option>').text(tipo).val(id));
  //   }
  //   select.val(unidad_seleccionada);
  //  fila.append($('<td>').append(""));

  };

  //TIPO DE MOVIMIENTO ES %DEVOLUCION:
  if(t_mov==6){
    fila.append($('<td>')
      .append($('<input>')
      .addClass('devolucion_modificada form-control')
      .attr("type","number").attr("step","0.01").attr("min","80").attr("max","100")
          .val(dev)));
  };

  //"p" indica si ya viene cargada la tabla o no, para agregar o no el boton de borrar
  if (p==1) {
    fila.append($('<td>').append(accion));
  }
    //Agregar fila a la tabla
    $('#tablaDenominacion tbody').append(fila);
    //Habilitar botones
    $('#btn-enviar-denom').prop('disabled', false);
    $('#btn-pausar-denom').prop('disabled', false);

};

$('#todosDen').on('click', function(){

  var den_comun=$('#denom_comun').val();
  //var unidad_comun=$('#unidad_comun').val();
  var tabla= $('#tablaDenominacion tbody > tr');
  if (den_comun !=""){
    $.each(tabla, function(index, value){
      $('.denominacion_modificada').val(den_comun);
      //$('.unidad_denominacion').val(unidad_comun);
    });
  }
  

})
$('#todosDev').on('click', function(){

  var dev_comun=$('#devol_comun').val();
  var tabla= $('#tablaDenominacion tbody > tr');
  if (dev_comun!=""){
    $.each(tabla, function(index, value){
      $('.devolucion_modificada').val(dev_comun);
    });
  }
})
//cierra modal y limpio el data list de arriba
$('#modalDenominacion').on('hidden.bs.modal', function() {

  $('.input-data-list').borrarDataList();

})

//BOTÓN ENVIAR A FISCALIZAR DE DENOMINACION, DEVOLUCION Y JUEGO
$(document).on('click','#btn-enviar-denom',function(e){

  var id_log_movim = $('#modalDenominacion').find('#id_mov_denominacion').val();
  var tipo =  $('#modalDenominacion').find('#id_t_mov').val();
  var tabla_maq = $('#tablaDenominacion tbody > tr');
  var maquinas = [];
  var fecha = $('#B_fecha_denom').val();

  $.each(tabla_maq, function(index, value){

  //Según el tipo de movimiento genera distintos json de máquinas
  switch (tipo) {
    //Tipo Movimiento: DENOMINACION
    case '5':
    var maquina={
      id_maquina: $(this).attr('id'),
      id_juego:"",
      denominacion:$(this).find('.denominacion_modificada').val(),
      porcentaje_devolucion:"",
      id_unidad_medida:$(this).find('.unidad_denominacion').val(),
    }
      break;

    //Tipo Movimiento: % DEVOLUCION
    case '6':
      var maquina={
        id_maquina: $(this).attr('id'),
        id_juego:"",
        denominacion:"",
        porcentaje_devolucion:$(this).find('.devolucion_modificada').val(),
        id_unidad_medida:"",

      }
        break;

    //Tipo Movimiento: JUEGO
    case '7':
          var maquina={
            id_maquina: $(this).attr('id'),
            id_juego:$(this).find('.juego_modif').obtenerElementoSeleccionado(),
            denominacion:"",
            porcentaje_devolucion:"",
            id_unidad_medida:""
          }
            break;
  }

      maquinas.push(maquina);
  });
  //USA LA FC DE POST, ENVIANDO EN TRUE EL ATRIBUTO DE CARGA FINALIZADA
  enviarDenominacion(id_log_movim, maquinas, fecha, true);

});

//Pausa la carga de maquinas a fiscalizar
$(document).on('click','#btn-pausar-denom',function(e){

  var id_log_movim = $('#modalDenominacion').find('#id_mov_denominacion').val();
  var tipo =  $('#modalDenominacion').find('#id_t_mov').val();
  var tabla_maq = $('#tablaDenominacion tbody > tr');
  var maquinas = [];
  var fecha = $('#B_fecha_denom').val();

  $.each(tabla_maq, function(index, value){

  //Según el tipo de movimiento genera distintos json de máquinas
  switch (tipo) {

      //Tipo Movimiento: DENOMINACION
      case '5':
      var maquina={
        id_maquina: $(this).attr('id'),
        id_juego:"",
        denominacion:$(this).find('.denominacion_modificada').val(),
        porcentaje_devolucion:"",
        id_unidad_medida:$(this).find('.unidad_denominacion').val(),
      }
        break;

      //Tipo Movimiento: % DEVOLUCIÓN
      case '6':
        var maquina={
          id_maquina: $(this).attr('id'),
          id_juego:"",
          denominacion:"",
          porcentaje_devolucion:$(this).find('.devolucion_modificada').val(),
          id_unidad_medida:""
        }

          break;

          //Tipo Movimiento: JUEGO
          case '7':
            var maquina={
              id_maquina: $(this).attr('id'),
              id_juego:$(this).find('.juego_modif').obtenerElementoSeleccionado(),
              denominacion:"",
              porcentaje_devolucion:"",
              id_unidad_medida:""
            }
          break;
    }
  maquinas.push(maquina);

  //USA LA FC DE POST, ENVIANDO EN FALSE EL ATRIBUTO DE CARGA FINALIZADA
  enviarDenominacion(id_log_movim, maquinas, fecha, false);
   })
});

//FUNCION PARA ENVIAR EL POST AL CONTROLADOR, CON LOS CAMBIOS GENERADOS
function enviarDenominacion(id_mov,maq,fecha,fin){

  $('#mensajeExito').hide();

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });


  var formData = {
    id_log_movimiento: id_mov,
    maquinas: maq,
    carga_finalizada: fin, //INDICA SI LA CARGA FUE FINALIZADA O NO
    fecha:fecha
  }

  $.ajax({
        type: 'POST',
        url: 'movimientos/guardarRelevamientosMovimientosMaquinas',
        data: formData,
        dataType: 'json',
        success: function (data) {
            if(fin==true){

            $('#mensajeExito h3').text('ENVÍO');
            $('#mensajeExito p').text('Las máquinas han sido enviadas correctamente');
            $('#modalDenominacion').modal('hide');
            $('#mensajeExito').show();
            }

            if(fin==false){

              $('#mensajeExito h3').text('GUARDADO');
              $('#mensajeExito p').text('Las máquinas han sido guardadas en el movimiento');
              $('#modalDenominacion').modal('hide');
              $('#mensajeExito').show();
            }

        },
        error: function (data) {
          var response = data.responseJSON.errors;

          if(typeof response.fecha !== 'undefined'){
              mostrarErrorValidacion($('#B_fecha_denom'),response.fecha[0],false);
          }
          else{
              $('#mensajeFiscalizacionError2').show();
              $("#modalLogMovimiento2").animate({ scrollTop: $('#mensajeFiscalizacionError').offset().top }, "slow");
          }

        },
   });
};
//**********FIN MODAL PARA MODIFICAR JUEGO, DENOMINACION Y DEVOLUCION ******************


//MODAL BAJA MTM EN EL MOVIMIENTO EGRESO/REINGRESO

$(document).on('click','.bajaMTM', function(){
  var casino= $(this).attr('data-casino');
  var id_movimiento= $(this).val();
  var tipo_mov= $(this).attr('data-tipo-mov');

  $('.modal-title').text('CARGAR MÁQUINAS PARA EGRESO DEFINITIVO');
  $('#modalBajaMTM').find('#tipoMovBaja').val(tipo_mov);
  $('#modalBajaMTM').find('#movimId').val(id_movimiento);
  mtmParaBaja=[];

  $('#inputMaq3').generarDataList("maquinas/obtenerMTMMovimientos/"  + casino + '/' + tipo_mov + '/' + id_movimiento  ,'maquinas','id_maquina','nro_admin',1,true);

  $('#tablaBajaMTM tbody tr').remove();
  $('#btn-baja').prop('disabled', false);
  $('#mensajeExito').hide();
  $('#modalBajaMTM').modal('show');

})

//crea tabla
function clickAgregarMaqBaja(e) {

  var id_maq = $('#inputMaq3').attr('data-elemento-seleccionado');

  if (id_maq != 0) {
    $.get('http://' + window.location.host +"/maquinas/obtenerMTM/" + id_maq, function(data) {
      agregarMaqBaja(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.marca, data.maquina.modelo, 1);
      $('#inputMaq3').setearElementoSeleccionado(0 , "");
    });
  }
}

function agregarMaqBaja(id_maquina, nro_admin, marca, modelo,p) {

  var fila = $('<tr>').attr('id', id_maquina);
  var accion = $('<button>').addClass('btn btn-danger borrarMaqCargada')
                              .append($('<i>').addClass('fa fa-fw fa-trash'));
  var t_mov = $('#modalBajaMTM').find('#tipoMovBaja').val();

  //Se agregan todas las columnas para la fila
  fila.append($('<td>').text(nro_admin))
  fila.append($('<td>').text(marca))
  fila.append($('<td>').text(modelo))
  //"p" indica si ya viene cargada la tabla o no, para agregar o no el boton de borrar
  if (p==1) {
    fila.append($('<td>').append(accion));
  }
  //Agregar fila a la tabla
  $('#tablaBajaMTM tbody').append(fila);
  //Habilitar botones
  $('#btn-baja').prop('disabled', false);

};

//boton borrar en fila
$(document).on('click','.borrarMaqCargada',function(e){
  $(this).parent().parent().remove();
});

//boton borrar en fila
$(document).on('click','.borrarMaq',function(e){
  $(this).parent().parent().remove();
});

//boton ELIMINAR, EN MODAL
$(document).on('click','#btn-baja',function(e){

  var tipo=$('#modalBajaMTM').find('#tipoMovBaja').val();
  var id_log_movimiento = $('#modalBajaMTM').find('#movimId').val();

  var maquinas = $('#tablaBajaMTM tbody > tr');

  $.each(maquinas, function(index, value){
    var maquina={
      id_maquina:$(this).attr('id')
    }
      mtmParaBaja.push(maquina);
  });

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var formData = {
    maquinas: mtmParaBaja
  }

  $.ajax({
        type: 'POST',
        url: 'movimientos/bajaMTMs',
        data: formData,
        dataType: 'json',
        success: function (data) {

            $('#mensajeExito h3').text('ELIMINACIÓN EXITOSA');
            $('#mensajeExito p').text('Las máquinas han sido eliminadas');

            $("#modalBajaMTM").modal('hide');
            $('#mensajeExito').show();
          },
        error: function (data) {
            console.log('Error: No fue posible enviar a fiscalizar las máquinas cargadas');

            $('#mensajeFiscalizacionError').show();
            $("#modalLogMovimiento2").animate({ scrollTop: $('#mensajeFiscalizacionError').offset().top }, "slow");
        },
      });
});

// Mostrar modal para VALIDACIÓN de maquinas de ingreso

//BOTÓN VALIDACION, DENTRO DE LA TABLA PRINCIPÁL
$(document).on('click','.validarMovimiento',function(){
  $('#mensajeExito').hide();

  $('#tablaFechasFiscalizacion tbody tr').remove();
  $('#tablaMaquinasFiscalizacion tbody tr').remove();
  $('#mensajeErrorVal').hide();
  $('#mensajeExitoValidacion').hide();
  $('#columnaMaq').hide();
  $('#columnaDetalle').hide();

  //oculto los dos botones de guardar
  $('#enviarValidar').hide();
  $('#errorValidacion').hide();
  $('#finalizarValidar').hide();

  //Modificar los colores del modal
  $('#modalValidacion .modal-title').text('VALIDAR MÁQUINAS RELEVADAS');
  $('#modalValidacion .modal-header').attr('style','background: #4FC3F7');

  var id_log_movimiento = $(this).val();

  $.get('movimientos/ValidarMovimiento/' + id_log_movimiento, function(data){

      var tablaFiscalizacion=$('#tablaFechasFiscalizacion tbody');

      for (var i = 0; i < data.length; i++) {
          var fila= $('<tr>');

          fila.append($('<td>')
              .addClass('col-xs-6')
              .text(data[i].fecha_envio_fiscalizar)
            );
          fila.append($('<td>')
              .addClass('col-xs-3')
              .append($('<button>')
                  .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-eye')
                  )
                  .attr('type','button')
                  .addClass('btn btn-info detalleMov')
                  .attr('data-id-fiscalizacion',data[i].id_fiscalizacion_movimiento)
                  .attr('data-fecha-fisc', data[i].fecha_envio_fiscalizar)

                )
              )
              if(data[i].id_estado_fiscalizacion == 4){
                fila.append($('<td>')
                    .addClass('col-xs-3')
                    .append($('<i>').addClass('fa fa-fw fa-check finalizado').css('color','#4CAF50')));
              }
              $('#finalizarValidar').attr('data-fiscalizacion',data[i].id_fiscalizacion_movimiento);

            tablaFiscalizacion.append(fila);
      }
      var cantidad=0;
      $('#tablaFechasFiscalizacion tbody tr').each(function(){

          if ($(this).hasClass('finalizado')) {
             cantidad=cantidad + 1;
          }

      });
      if (cantidad==data.length) {
        $('#finalizarValidar').show();

      }
      $('#mensajeErrorVal').hide();
      $('.detalleMaq').hide();
      $('#toma2').hide();
      $('.error').prop('disabled',true);
      $('#observacionesToma').hide();

      //guardo el id del movimiento en el input del modal
      $('#modalValidacion').find('#id_log_movimiento').val(id_log_movimiento);

      $('#modalValidacion').modal('show');
      $('#mensajeExito').hide();
    });
  });

//BOTON PARA VER EL LISTADO DE LAS MÁQUINAS FISCALIZADAS ESA FECHA
$(document).on('click','.detalleMov',function(){

  $('#columnaMaq').show();
  $('.detalleMaq').hide();
  $('#toma2').hide();
  $('.error').prop('disabled',true);
  $('#observacionesToma').hide();
  $('#tablaFechasFiscalizacion tbody tr').css('background-color','#FFFFFF');
  $(this).parent().parent().css('background-color', '#E0E0E0');

  var id_fiscalizacion = $(this).attr('data-id-fiscalizacion');
  var fecha_fiscalizacion = $(this).attr('data-fecha-fisc');

  //guardo la fecha de fiscalizacion en el input del modal
  $('#modalValidacion').find('#fecha_fiscalizacion').val(fecha_fiscalizacion);

  $.get('movimientos/ValidarFiscalizacion/' + id_fiscalizacion, function(data){

    if(data.Maquinas.id_estado_fiscalizacion!=4){
      $('#finalizarValidar').hide();
    }

    var tablaMaquinasFiscalizacion=$('#tablaMaquinasFiscalizacion tbody');
    $('#tablaMaquinasFiscalizacion tbody tr').remove();
    var cant_maq_val=0;
    cant_validadas=data.Maquinas.length;
    for (var i = 0; i < data.Maquinas.length; i++) {
        var fila= $('<tr>');

        fila.attr('data-id',data.Maquinas[i].id_maquina)
        .append($('<td>')
        .addClass('col-xs-4')
        .text(data.Maquinas[i].nro_admin)
        )
        fila.append($('<td>')
            .addClass('col-xs-4')
            .append($('<button>')
                .append($('<i>')
                .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                )
                .attr('type','button')
                .addClass('btn btn-info verMaquina1')
                .attr('data-maquina',data.Maquinas[i].id_maquina)
                .attr('data-fiscalizacion',id_fiscalizacion)
                .attr('data-relevamiento', data.Maquinas[i].id_relev_mov)
              )
            );
          if(data.Maquinas[i].id_estado_relevamiento == 4){
            cant_validadas= cant_validadas - 1;
            cant_maq_val=cant_maq_val + 1;
            $('#enviarValidar').hide();
            fila.append($('<td>')
                .addClass('col-xs-4')
                .append($('<i>').addClass('fa fa-fw fa-check').css('color','#4CAF50')));
          }

        tablaMaquinasFiscalizacion.append(fila);
    }
    var t= $("#tablaMaquinasFiscalizacion tr").length;
    console.log('t es', t);
    console.log('cant es', cant_maq_val);

    if(cant_maq_val==(t-1)){
      $('#finalizarValidar').show();
    }

    })
});

//BOTÓN PARA VER EL DETALLE DE  UNA DE LAS MÁQUINAS FISCALIZADAS
$(document).on('click','.verMaquina1',function(){

  $('#columnaDetalle').show();
  $('.detalleMaq').show();
  var id_maquina = $(this).attr('data-maquina');
  var id_fiscalizacion = $(this).attr('data-fiscalizacion');
  var tablaContadores = $('#tablaValidarIngreso tbody');
  var id_relevamiento = $(this).attr('data-relevamiento');
  $('#tablaMaquinasFiscalizacion tbody tr').css('background-color','#FAFAFA');
  $(this).parent().parent().css('background-color', '#E0E0E0');

  //guardo el id_maquina en el input maquina del modal
  $('#modalValidacion').find('#maquina').val(id_maquina);
  $('#modalValidacion').find('#relevamiento').val(id_relevamiento);
  $('#mensajeExitoValidacion').hide();

  // $('#mensajeExitoValidacion').hide();

  $('#tablaValidarIngreso tbody tr').remove();

  $.get('movimientos/ValidarMaquinaFiscalizacion/' + id_relevamiento, function(data){
    if(data.toma.id_estado_relevamiento==4){
      $('#enviarValidar').hide();
    }
    else{
      $('#enviarValidar').show();
      $('#errorValidacion').show();
    }
  if (true) {
    //CARGA CAMPOS INPUT
    if(data.cargador!=null){ $('#f_cargaMov').val(data.cargador.nombre); }

    $('#f_tomaMov').val(data.fiscalizador.nombre);
    $('#nro_adminMov').val(data.toma.nro_admin);
    $('#nro_islaMov').val(data.toma.nro_isla);
    $('#nro_serieMov').val(data.toma.nro_serie);
    $('#marcaMov').val(data.toma.marca);
    $('#modeloMov').val(data.toma.modelo);
    $('#macMov').val(data.toma.mac);
    $('#islaRelevadaMov').val(data.toma.nro_isla_relevada);
    $('#sectorRelevadoMov').val(data.toma.descripcion_sector_relevado);

    //CARGAR LA TABLA DE CONTADORES, HASTA 6
    var cont = "cont";
    var vcont = "vcont";
    var fila1 = $('<tr>');

    for (var i = 1; i < 7; i++) {
                    var fila = fila1.clone();
                    var p = data.toma[cont + i];
                    var v = data.toma[vcont + i];

                            if(data.toma1==null){//si toma anterior es null:

                                if(p != null ){ //si toma actual es != null
                                  fila.append($('<td>')
                                  .addClass('col-xs-6')
                                  .text(p))
                                    .append($('<td>')
                                    .addClass('col-xs-3')
                                    .text(v)
                                  );
                                  $('#toma_actual').show();
                                  $('#toma_anterior').hide();
                                  $('#toma_check').hide();

                                  tablaContadores.append(fila);
                                }
                            }

                            else{ //si toma anterior es != null
                                  var m = data.toma1[vcont + i];

                                  if(p != null ){ //si toma nueva es != null
                                        fila.append($('<td>')
                                              .addClass('col-xs-6')
                                              .text(p))
                                            .append($('<td>')
                                              .addClass('col-xs-3')
                                              .text(m) //valor de la toma anterior
                                            )
                                            .append($('<td>')
                                              .addClass('col-xs-3')
                                              .text(v) //valor de la toma nueva
                                            );

                                            if(m == v){
                                              fila.append($('<td align="center">')
                                                .addClass('col-xs-2')
                                                .append($('<span>').text(' '))
                                                  .addClass('boton_check_toma')
                                                  .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-check-circle-o').css('color','#1DE9B6'))
                                                  .attr('style', 'font-size:20px')
                                                  .attr('data-toggle',"tooltip")
                                                  .attr('data-placement',"top")
                                                  .attr('title', "OK")
                                                  .attr('data-delay',{"show":"300", "hide":"100"})

                                                );
                                                fila.find('.boton_check_toma').hide();
                                            }else{
                                              fila.append($('<td align="center">')
                                                .addClass('col-xs-2')
                                                .append($('<span>').text(' '))
                                                  .addClass('boton_x_toma')
                                                  .append($('<i>')
                                                  .addClass('fa').addClass('fa-fw').addClass('fa-times-circle-o').css('color','#D50000'))
                                                  .attr('style', 'font-size:20px')
                                                  .attr('data-toggle',"tooltip")
                                                  .attr('data-placement',"top")
                                                  .attr('title', "ERROR")
                                                  .attr('data-delay',{"show":"300", "hide":"100"})

                                              );
                                              fila.find('.boton_x_toma').hide();
                                            }

                                            $('#toma_anterior').show();
                                            $('#toma_actual').show();
                                            $('#toma_check').hide();

                                          tablaContadores.append(fila);
                                        }
                                  }
          }

    if(data.toma1==null){ //TOMA ANTERIOR ES NULL:
      //MUESTRO LA TOMA NUEVA
    $('#juego').val(data.toma.nombre_juego);
    $('#apuesta').val(data.toma.apuesta_max);
    $('#cant_lineas').val(data.toma.cant_lineas);
    $('#devolucion').val(data.toma.porcentaje_devolucion);
    $('#denominacion').val(data.toma.denominacion);
    $('#creditos').val(data.toma.cant_creditos);

  }
  else{ //SI TIENE TOMA ANTERIOR:
    $('#toma2').show(); //MUESTRO TOMA ANTERIOR
    //COMPLETO TOMA NUEVA QUE SIEMPRE TIENE
    $('#juego').val(data.toma.nombre_juego);
    $('#apuesta').val(data.toma.apuesta_max);
    $('#cant_lineas').val(data.toma.cant_lineas);
    $('#devolucion').val(data.toma.porcentaje_devolucion);
    $('#denominacion').val(data.toma.denominacion);
    $('#creditos').val(data.toma.cant_creditos);

    //Y COMPLETO TOMA ANTERIOR
    $('#juego1').val(data.toma1.nombre_juego);
    $('#apuesta1').val(data.toma1.apuesta_max);
    $('#cant_lineas1').val(data.toma1.cant_lineas);
    $('#devolucion1').val(data.toma1.porcentaje_devolucion);
    $('#denominacion1').val(data.toma1.denominacion);
    $('#creditos1').val(data.toma1.cant_creditos);

  }

  if( !data.coinciden_juego){
    mostrarErrorValidacion($('#juego'),data.n_juego,false);
  }
  if( !data.coinciden_denominacion){
    mostrarErrorValidacion($('#denominacion'),data.n_denominacion,false);
  }
  if( !data.coinciden_devolucion){
    mostrarErrorValidacion($('#devolucion'),data.n_devolucion,false);
  }

  $('#observacionesToma').show();
  if(data.toma.observaciones!=null){
  $('#observacionesToma').text(data.toma.observaciones);}
  else{
    $('#observacionesToma').text(' ');
  }
    //guardo el id_fiscalizacion en el boton enviarValidar
    $('#modalValidacion').find('#enviarValidar').val(id_fiscalizacion);

    $('.detalleMaq').show();
    $('.validar').prop('disabled', false);
    $('.error').prop('disabled',false);

  }
  else {

    console.log('No hay datos de la Máquina:', data.toma.nro_admin);
    $('#msj').text('No hay datos de la Máquina:', data.toma.nro_admin);
    $("#modalValidacion").animate({ scrollTop: $('#mensajeErrorVal').offset().top }, "slow");

  }
  });
});

//BOTÓN VALIDAR DENTRO DEL MODAL VALIDAR
$(document).on('click','#enviarValidar',function(){
  $('#errorValidacion').hide();
  var id_fiscalizacion = $(this).val();
  var id_maquina = $('#modalValidacion').find('#maquina').val();

  //BUSCO EL ID DE MOVIMIENTO EN EL MODAL, ESTA EN UN INPUT OCULTO
  var id_log_movimiento = $('#modalValidacion').find('#id_log_movimiento').val();
  var fecha_envio_fiscalizar = $('#modalValidacion').find('#fecha_fiscalizacion').val();
  var id_relevamiento= $('#modalValidacion').find('#relevamiento').val();

 validar(id_relevamiento, 1,id_maquina);


});

//cuando cierra el modal de validación, actualizo el listado
$("#modalValidacion").on('hidden.bs.modal', function () {
      $('#btn-buscarMovimiento').trigger('click',[1,10,'log_movimiento.fecha','desc']);
   })
//BOTÓN ERROR
$(document).on('click','#errorValidacion',function(){

  var id_relevamiento= $('#modalValidacion').find('#relevamiento').val();

  validar(id_relevamiento, 0);


});

//BOTÓN FINALIZAR VALIDACIÓN
$(document).on('click','#finalizarValidar',function(){

  var id_fiscalizacion=$(this).attr('data-fiscalizacion');
  $.get('movimientos/finalizarValidacion/' + id_fiscalizacion, function(data){
    if (data==1){
      $('#modalValidacion').modal('hide');
      $('#mensajeExito h3').text('EXITO');
      $('#mensajeExito p').text('Se ha VALIDADO correctamente el movimiento.');
      $('#mensajeExito').show();
    }
  })

});

//POST PARA VALIDAR
function validar(id_rel, val, id_maquina){

  var formData = {
    id_relev_mov: id_rel,
    validado: val,
  }

  $.ajax({
      type: 'POST',
      url: 'movimientos/validarTomaRelevamiento',
      data: formData,
      dataType: 'json',

      success: function (data) {

        // //ver si funciona, cambiar el color del botón
        // $('.verMaquina1').css('background-color','#4DB6AC');
        //Deshabilito los botones error y validar
        $('#enviarValidar').hide();
        $('.error').prop('disabled', true);
        $('.detalleMaq').hide();
        cant_validadas=cant_validadas - 1;

        $('#tablaMaquinasFiscalizacion tbody tr').each(function(){
            console.log($(this).attr('data-id'));
            var maq=$(this).attr('data-id');
            console.log('maquina', maq);

            if (maq == id_maquina){
              console.log('encontrada', $(this));
              $(this).append($('<td>')
                  .addClass('col-xs-4')
                  .append($('<i>').addClass('fa fa-fw fa-check').css('color','#4CAF50')));
            }
        });
        console.log('cant_validadas',cant_validadas);
         //si se validaron todas las máquinas de la fecha
          if(cant_validadas==0){
            $('#finalizarValidar').show();
            // $('#tablaFechasFiscalizacion').hide();

          }

      },
      error: function (data) {
        $('#mensajeErrorVal').show();

      }
  })

};

//Enviar a fiscalizar las de ingreso **************************
$(document).on('click','.enviarIngreso',function(e){
  // e.preventDefault();
  var id_log_movimiento = $(this).val();
  $('.modal-title').text('SELECCIÓN DE MTMs PARA ENVÍO A FISCALIZAR');
  $('#tablaMaquinas tbody tr').remove();
  $('#modalEnviarFiscalizarIngreso #id_log_movimiento').val(id_log_movimiento);
  ocultarErrorValidacion($('#B_fecha_ingreso'));
  $('#B_fecha_ingreso').val('');
  $.get('movimientos/buscarMaquinasMovimiento/' + id_log_movimiento, function(data){

      var tablaMaquinas=$('#tablaMaquinas tbody');

      for (var i = 0; i < data.maquinas.length; i++) {
          var fila = $(document.createElement('tr'));

          fila.attr('id', data.maquinas[i].maquina.id_maquina)
              .append($('<td>').addClass('col-xs-3').append($('<input>').attr('type','checkbox')))
              .append($('<td>').addClass('col-xs-9').text(data.maquinas[i].maquina.nro_admin))

          tablaMaquinas.append(fila);
      }
  });

  $('#modalEnviarFiscalizarIngreso').modal('show');


})

//dentro del modal de ingreso, presiona el boton "Enviar a Fiscalizar"
$("#btn-enviar-ingreso").click(function(e){
  $('#mensajeError').hide();
  $('#mensajeExito').hide();
  var id=$("#modalEnviarFiscalizarIngreso #id_log_movimiento").val();
  var maquinas_seleccionadas=[];
  var fecha=$('#B_fecha_ingreso').val();

  $('#tablaMaquinas tbody tr').each(function(){
      var check=$(this).find('td input[type=checkbox]');
      console.log(check);

      if (check.prop('checked')) {
         maquinas_seleccionadas.push($(this).attr('id'));
      }
  });

  var formData= {
    id_log_movimiento: id,
    maquinas: maquinas_seleccionadas,
    fecha:fecha
  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
        type: 'POST',
        url: 'movimientos/enviarAFiscalizar',
        data: formData,
        dataType: 'json',

        success: function (data){

          $('#mensajeExito h3').text('ENVÍO EXITOSO');
          $('#mensajeExito p').text('Las máquinas fueron enviadas correctamente');
          $('#modalEnviarFiscalizarIngreso').modal('hide');
          $('#mensajeExito').show();
        },
        error: function(data){
          $('#mensajeError h3').text('ERROR');
          $('#mensajeError p').text('No hay máquinas seleccionadas');
          //$('#modalEnviarFiscalizarIngreso').modal('hide');
          $('#mensajeError').show();
          var response = data.responseJSON.errors;

          if(typeof response.fecha !== 'undefined'){
            mostrarErrorValidacion($('#B_fecha_ingreso'),response.fecha[0],false);}
        },
  })
})
//FIN ENVIAR A FISCALIZAR INGRESO****************************************

//-------------------------------------------------------------------------

//redirigir cambio layout
$(document).on('click','.redirigir',function(e){

    var id_movimiento=$(this).val();

    var formData= {
      id_log_movimiento: id_movimiento
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: 'POST',
        url: 'movimientos/guardarLogClickMov',
        data: formData,
        dataType: 'json',

        success: function (data){
          console.log('Exito!!');
        },
        error: function(data){
          alert('error');

        },
    })

  window.open('islas','_blank');
});

//Busqueda de movimientos
$('#btn-buscarMovimiento').click(function(e,pagina,page_size,columna,orden){

  $('#mensajeExito').hide();
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var page_size = (page_size != null) ? page_size : 10;
  var page_number = (pagina != null) ? pagina : 1;
  var sort_by = (columna != null) ? {columna,orden} : null;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }
  else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;

  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    nro_exp_org: $('#B_nro_exp_org').val(),
    nro_exp_interno: $('#B_nro_exp_interno').val(),
    nro_exp_control: $('#B_nro_exp_control').val(),
    tipo_movimiento: $('#B_TipoMovimiento').val(),
    casino: $('#dtpCasinoMov').val(),
    fecha: $('#fecha_movimiento').val(),
    nro_admin: $('#busqueda_maquina').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: 'POST',
      url: 'movimientos/buscarLogsMovimientos',
      data: formData,
      dataType: 'json',

      success: function (data) {

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.logMovimientos.total,clickIndiceMov);
          $('#cuerpoTabla tr').remove();
          for (var i = 0; i < data.logMovimientos.data.length; i++) {
              var filaMovimiento = generarFilaTabla(data.logMovimientos.data[i]);
              $('#cuerpoTabla').append(filaMovimiento);
          }
          //Asigno valor a la variable global casinos
          casinos=data.casinos;

          //Me permite mostrar los nombres de los botones
          $('[data-toggle="tooltip"]').tooltip();


          $('#herramientasPaginacion').generarIndices(page_number,page_size,data.logMovimientos.total,clickIndiceMov);
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){

  $('#tablaResultados th').removeClass('activa');

  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    console.log('1');
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
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndiceMov(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndiceMov(e,pageNumber,tam){

  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscarMovimiento').trigger('click',[pageNumber,tam,columna,orden]);
}

//paginacion
function generarFilaTabla(movimiento){

  var fila = $(document.createElement('tr'));
  var t_mov;
  var fecha;
  var estado;
  var t_carga;
  var estado_movimiento;
  var nro_org;
  var nro_int;
  var nro_cont;
  var islas;

  estado_movimiento=movimiento.id_estado_movimiento;
  t_carga=movimiento.tipo_carga;
  estado=movimiento.id_estado_movimiento.descripcion;
  t_mov=movimiento.descripcion;
  fecha=movimiento.fecha;
  cant=movimiento.cant_maquinas;
  if(movimiento.islas != null){
    islas=movimiento.islas;
  }else{
    islas ="-";
  }

    if(movimiento.nro_exp_org != null){
        nro_org=movimiento.nro_exp_org;
        nro_int=movimiento.nro_exp_interno;
        nro_cont=movimiento.nro_exp_control;
    }

    fila.attr('id', movimiento.id_log_movimiento)
        .append($('<td>')
        .addClass('col-xs-2')
        .text(convertirDate(fecha))
        )
        .append($('<td>')
        .addClass('col-xs-2')
        .text(nro_org + '-' + nro_int + '-' + nro_cont)
        )
        .append($('<td>')
        .addClass('col-xs-2')
        .text(islas)
        )
        .append($('<td>')
        .addClass('col-xs-2')
        .text(t_mov)
        )
        if(estado_movimiento==4){
        fila.append($('<td>')
        .addClass('col-xs-1').css('text-align','center')
        .append($('<i>')
        .addClass('fa').addClass('fa-fw').addClass('fa-check').css('color','#66BB6A').css('margin-left',' auto').css('margin-right', 'auto')
            )
      )}else{
        fila.append($('<td>')
        .addClass('col-xs-1').css('text-align','center')
        .append($('<i>')
        .addClass('fas').addClass('fa-fw').addClass('fa-times').css('color','#EF5350')))
      }
        fila.append($('<td>')
            .addClass('col-xs-3')
            .append($('<span>').text(' '))
            .append($('<button>')
            .addClass('boton_redirigir')
                .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-external-link-square-alt')
                  )
                .append($('<span>').text(' REDIRIGIR'))
                  .addClass('btn').addClass('btn-warning')
                  .attr('value',movimiento.id_log_movimiento)

                  )
                  .append($('<button>')
                  .addClass('boton_nuevo')
                  .append($('<i>')
                  .addClass('far').addClass('fa-fw').addClass('fa-file-alt')
                      )
                      .append($('<span>').text('NUEVO'))
                      .attr('type','button')
                      .addClass('btn')//.addClass('btn-info')
                      .attr('value',movimiento.id_log_movimiento)
                      .attr('data-casino',movimiento.id_casino)
                      .attr('data-tipo',movimiento.id_tipo_movimiento)

                  )
                  .append($('<span>').text(' '))
                  .append($('<button>')
                  .addClass('boton_cargar').attr("data-carga", t_carga)
                  .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-plus')
                      )
                      .append($('<span>').text(' CARGAR'))
                      .addClass('btn').addClass('btn-success')
                      .attr('type','button')
                      .attr('value',movimiento.id_log_movimiento)

                  )
                  .append($('<span>').text(' '))
                  .append($('<button>')
                  .addClass('boton_fiscalizar')
                  .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-paper-plane')
                      )
                      .append($('<span>').text(' ENVIAR A FISCALIZAR'))
                      .addClass('btn').addClass('btn-success')
                      .attr('value',movimiento.id_log_movimiento)

                  )
                  .append($('<span>').text(' '))
                  .append($('<button>')
                  .addClass('boton_modificar')
                  .append($('<i>')
                  .addClass('fas').addClass('fa-fw').addClass('fa-pencil-alt')
                      )
                      .append($('<span>').text('MODIFICAR'))
                      .addClass('btn').addClass('btn-warning')
                      .attr('value',movimiento.id_log_movimiento)
                      .attr('data-tmov',movimiento.id_tipo_movimiento)
                      .attr('data-cas',movimiento.id_casino)

                  )
                  .append($('<span>').text(' '))
                  .append($('<button>')
                  .addClass('boton_validar')
                  .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-check')
                      )
                      .append($('<span>').text(' VALIDAR'))
                      .addClass('btn').addClass('btn-success')
                      .attr('value',movimiento.id_log_movimiento)

                  )
                  .append($('<span>').text(' '))
                  .append($('<button>')
                  .addClass('boton_baja')
                      .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-ban')
                      )
                      .append($('<span>').text(' BAJA MTM'))
                      .addClass('btn').addClass('btn-danger')
                      .attr('value',movimiento.id_log_movimiento)
                      .attr('data-casino', movimiento.id_casino)
                      .attr('data-tipo-mov', movimiento.id_tipo_movimiento)


                 )

                 .append($('<span>').text(' '))
                 .append($('<button>')
                 .addClass('boton_toma2')
                     .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-retweet')
                     )
                     .append($('<span>').text('VOLVER A RELEVAR'))
                     .addClass('btn')//.addClass('btn-info')
                     .attr('value',movimiento.id_log_movimiento)
                     .attr('data-casino', movimiento.id_casino)
                     .attr('data-tipo-mov', movimiento.id_tipo_movimiento)
                     .attr('data-estado',movimiento.id_estado_movimiento)

                )


               .append($('<span>').text(' '))
               .append($('<button>')
               .addClass('baja_mov')
                   .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash')
                   )
                   .append($('<span>').text(' BAJA MOV'))
                   .addClass('btn').addClass('btn-danger')
                   .attr('value',movimiento.id_log_movimiento)
                   .attr('data-casino', movimiento.id_casino)
                   .attr('data-tipo-mov', movimiento.id_tipo_movimiento)


           )
             .append($('<span>').text(' '))
             .append($('<button>')
             .addClass('print_mov')
                 .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-print')
                 )
                 .append($('<span>').text(' IMPRIMIR MOV'))
                 .addClass('btn').addClass('btn-success')
                 .attr('value',movimiento.id_log_movimiento)
                 .attr('data-casino', movimiento.id_casino)
                 .attr('data-tipo-mov', movimiento.id_tipo_movimiento)
                 // .attr('data-toggle',"tooltip")
                 // .attr('data-placement',"top")
                 // .attr('title', "IMPRIMIR MOVIMIENTO")
                 // .attr('data-delay',{"show":"300", "hide":"100"})

         ))

          if (t_mov=="INGRESO"){
            fila.find('.boton_nuevo').addClass('nuevoIngreso');
            fila.find('.boton_fiscalizar').addClass('enviarIngreso');
            fila.find('.boton_validar').addClass('validarMovimiento');
            fila.find('.boton_modificar').remove();
            fila.find('.boton_redirigir').remove();
            fila.find('.boton_cargar').hide();
            fila.find('.boton_baja').remove();
            fila.find('.baja_mov').addClass('bajaMov');
            fila.find('.boton_toma2').remove();

            if(estado_movimiento==8 || cant != 0){
               fila.find('.boton_cargar').show();
               fila.find('.nuevoIngreso').attr('style', 'display:none');
               fila.find('.enviarIngreso').show();
            }
            if(cant==0){
              fila.find('.enviarIngreso').show();
              fila.find('.boton_cargar').attr('style', 'display:none');
            } //oculto el boton +
          }
          if (t_mov=="EGRESO" ) {
            fila.find('.boton_nuevo').addClass('nuevoEgreso');
            fila.find('.boton_cargar').remove();
            fila.find('.boton_fiscalizar').remove();
            fila.find('.boton_validar').addClass('validarMovimiento');
            fila.find('.boton_modificar').remove();
            fila.find('.boton_redirigir').remove();
            fila.find('.boton_baja').addClass('bajaMTM');
            fila.find('.baja_mov').addClass('bajaMov');
            fila.find('.boton_toma2').remove();

          }
          if(t_mov=="EGRESO/REINGRESOS"){
            fila.find('.boton_nuevo').addClass('nuevoEgreso');
            fila.find('.boton_fiscalizar').remove();
            fila.find('.boton_cargar').remove();
            fila.find('.boton_validar').addClass('validarMovimiento');
            fila.find('.boton_modificar').remove();
            fila.find('.boton_redirigir').remove();
            fila.find('.boton_baja').addClass('bajaMTM');
            fila.find('.baja_mov').remove();
            fila.find('.boton_toma2').remove();

          }
          if (t_mov=="% DEVOLUCIÓN") {
            fila.find('.boton_nuevo').remove();
            fila.find('.boton_cargar').remove();
            fila.find('.boton_fiscalizar').remove();
            fila.find('.boton_modificar').addClass('modificarDenominacion');
            fila.find('.boton_validar').addClass('validarMovimiento');
            fila.find('.boton_redirigir').remove();
            fila.find('.boton_baja').remove();
            fila.find('.baja_mov').addClass('bajaMov');
            fila.find('.boton_toma2').addClass('botonToma2');

          }
          if (t_mov=="DENOMINACIÓN") {
            fila.find('.boton_nuevo').remove();
            fila.find('.boton_cargar').remove();
            fila.find('.boton_fiscalizar').remove();
            fila.find('.boton_modificar').addClass('modificarDenominacion');
            fila.find('.boton_validar').addClass('validarMovimiento');
            fila.find('.boton_redirigir').remove();
            fila.find('.boton_baja').remove();
            fila.find('.baja_mov').addClass('bajaMov');
            fila.find('.boton_toma2').addClass('botonToma2');

          }
          if (t_mov=="JUEGO") {
            fila.find('.boton_nuevo').remove();
            fila.find('.boton_cargar').remove();
            fila.find('.boton_fiscalizar').remove();
            fila.find('.boton_modificar').addClass('modificarDenominacion');
            fila.find('.boton_validar').addClass('validarMovimiento');
            fila.find('.boton_redirigir').remove();
            fila.find('.boton_baja').remove();
            fila.find('.baja_mov').addClass('bajaMov');
            fila.find('.boton_toma2').addClass('botonToma2');
          }
          if (t_mov=="CAMBIO LAYOUT" ) {
            fila.find('.boton_cargar').remove();
            fila.find('.boton_nuevo').addClass('nuevoEgreso');
            fila.find('.boton_fiscalizar').remove();
            fila.find('.boton_redirigir').addClass('redirigir');
            fila.find('.boton_validar').addClass('validarMovimiento');
            fila.find('.boton_modificar').remove();
            fila.find('.boton_baja').remove();
            fila.find('.baja_mov').addClass('bajaMov');
            fila.find('.boton_toma2').addClass('botonToma2');

          }

    //para habilitar y deshabilitar botones, según el estado del movimiento:
    if(estado_movimiento != 1){fila.find('.nuevoIngreso').attr('style', 'display:none');} else{fila.find('.nuevoIngreso').show();}
    if(estado_movimiento != 3){ fila.find('.validarMovimiento').attr('style', 'display:none');} else{ fila.find('.validarMovimiento').show(); };
    if(estado_movimiento != 8 || estado_movimiento == 1){ fila.find('.enviarIngreso').attr('style', 'display:none');} else{ fila.find('.enviarIngreso').show();};
    if (t_mov=="INGRESO" && estado_movimiento != 1){fila.find('.enviarIngreso').show();};
    if(estado_movimiento == 4 || estado_movimiento == 5){ fila.find('.bajaMTM').attr('style', 'display:none')} else{ fila.find('.bajaMTM').prop('disabled', false) };
    if(estado_movimiento > 2){ fila.find('.boton_toma2').show();}else{ fila.find('.boton_toma2').attr('style', 'display:none');}
    // if(estado_movimiento > 2){ fila.find('.print_mov').show();}else{ fila.find('.print_mov').attr('style', 'display:none');}

    return fila;
}

$(document).on('click','.print_mov',function(e){

  var id= $(this).val();

  $.get('movimientos/maquinasEnviadasAFiscalizar/' + id, function(data){

    if (data==0){

      $('#modalAlerta').modal('show');

    }
    else{

      window.open('movimientos/maquinasEnviadasAFiscalizar/' + id,'_blank');

    }
  })

});


$(document).on('click','.bajaMov',function(e){

  $('#mensajeExito').show();
  $('#mensajeError').hide();
  var id_mov=$(this).val();

  var formData= {
    id_log_movimiento: id_mov
  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'movimientos/eliminarMovimiento',
      data: formData,
      dataType: 'json',

      success: function (data){
        if(data==1){

            $('#btn-buscarMovimiento').trigger('click',[1,10,'log_movimiento.fecha','desc']);
            $('#mensajeExito h3').text('ELIMINACIÓN EXITOSA');
            $('#mensajeExito p').text('El Movimientos fue eliminado correctamente');
            $('#mensajeExito').show();
        }
        else{
            $('#mensajeError p').text('No es posible eliminar este Movimiento.');
            $('#mensajeError').show();

        }
      },

      error: function(data){
        alert('ERROR: El movimiento ya fue enviado a fiscalizar o tiene asignado un expediente.',data);
      },
    })
});

/* Detecta la confirmación para seguir cargando máquinas en movimientos */
$('#mensajeExito .confirmar').click(function(e){

    //Se quitan las clases para dejar limpio el modal de éxito
    $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');
    //Luego se lo cierra
    $('#mensajeExito').hide();

    //Como confirmó, se siguen cargando máquinas y se muestra el modal de carga de máquina LIMPIO
    //Setear la cantidad de máquinas pendiente
    var cantidad = $('#cantidad_maquinas_restantes').text();
    $('#maquinas_pendientes').text(' ' + cantidad + ' MÁQUINAS PENDIENTES');
    // //pero hay que limpiarlo .. entonces
    // $('#boton_cargar').trigger('click');

    limpiarModalJuego();
    limpiarModalProgresivo();
    limpiarModalGliSoft();
    limpiarModalGliHard();
    limpiarModalFormula();
    clickLimpiarCamposModalIsla();
    $('#modalMaquina').modal('show');

    ocultarErrorValidacion($('#tipo_maquina'));
    ocultarErrorValidacion($('#nro_admin'));
    ocultarErrorValidacion($('#nro_serie'));
    ocultarErrorValidacion($('#marca'));
    ocultarErrorValidacion($('#modelo'));
    ocultarErrorValidacion($('#desc_marca'));
    ocultarErrorValidacion($('#unidad_medida'));
    ocultarErrorValidacion($('#tipo_gabinete'));
    ocultarErrorValidacion($('#mac'));
    ocultarErrorValidacion($('#tipo_maquina'));

});

/* Detecta la negativa para seguir cargando máquinas en movimientos */
$('#mensajeExito .salir').click(function(e){
    //Se quitan las clases para dejar limpio el modal de éxito
    $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');
    //Luego se lo cierra
    $('#mensajeExito').hide();
});

/* Cada vez que se abre un modal */
$('.modal').on('shown.bs.modal', function() {
    //Limpiar el mensaje de éxito. Sacar los botones y agregar animación
    $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');
    //Luego se lo cierra
    $('#mensajeExito').hide();
});

$('#modalMaquina #nro_admin').on("keyup", function(e){
  var text="NUEVA MÁQUINA TRAGAMONEDAS N°: " + $(this).val();
  $('#modalMaquina .modal-title').text(text);

});

function denominacionToFloat(den) {
  if (den=="" || den==null) {
    return parseFloat(0.01)
  }
  denf=den.replace(",",".")
  return parseFloat(denf)
}