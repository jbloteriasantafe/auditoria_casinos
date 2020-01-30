var maq_seleccionadas=[];
var maq_selecc_denom=[];
var casinos=[];
var cantidad_maquinas = []; //variable global, determina
var cant_validadas=0;
var ultimo_boton_carga = null;

$(document).ready(function(){
  $('#collapseFiltros #B_nro_exp_org').val("");
  $('#collapseFiltros #B_nro_exp_interno').val("");
  $('#collapseFiltros #B_nro_exp_control').val("");
  $('#collapseFiltros #B_TipoMovimiento').val("0");
  $('#collapseFiltros #dtpFechaMov').val("");
  $('#collapseFiltros #dtpCasinoMov').val("0");
  $('#busqueda_maquina').val("");

  if(window.location.pathname == '/movimientos'){
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
    $('#btn-buscarMovimiento').trigger('click');
  }

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

  limpiarModal();
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

/* 
 NUEVO MOVIMIENTO
 ###########################
 ####       #######    #####
 ####    #   ######    #####
 ####    ##   #####    #####
 ####    ###   ####    #####
 ####    ####   ###    #####
 ####    #####   ##    #####
 ####    ######   #    #####
 ####    #######       #####
 ###########################
*/

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

  $.get('movimientos/casinosYMovimientosIngresosEgresos', function(data){
    //carga el select de los casinos del modal
    for (let i = 0; i < data.casinos.length; i++) {
      $('#modalCas #selectCasinoIngreso')
      .append($('<option>')
      .prop('disabled',false)
      .val(data.casinos[i].id_casino)
      .text(data.casinos[i].nombre_casino))
    }
    //carga el select de los tipos de movimientos del modal
    for (let i = 0; i < data.tipos_movimientos.length; i++) {
      $('#modalCas #tipo_movimiento_nuevo')
      .append($('<option>')
      .prop('disabled',false)
      .val(data.tipos_movimientos[i].id_tipo_movimiento)
      .text(data.tipos_movimientos[i].descripcion))
    };
  });

  $('#modalCas .alerta').each(function(){
    eliminarErrorValidacion($(this));
    $(this).removeClass('alerta');
  });

  //ABRE MODAL QUE ME PERMITE ELEGIR EL CASINO AL QUE PERTENECE EL NUEVO MOV.
  $('#modalCas').modal('show');
});

//ACEPTA EL MODAL DE CASINO
$(document).on('click', '#aceptarCasinoIng', function(e) {
  $('#mensajeExito').hide();
  const id_mov = $('#modalCas #tipo_movimiento_nuevo').val();
  const id_cas = $('#modalCas #selectCasinoIngreso').val();
  const formData = {
    id_tipo_movimiento : id_mov,
    id_casino : id_cas
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

      //recargo la pág para que aparezca el nuevo movimientos en la tabla de movimientos
      $('#btn-buscarMovimiento').trigger('click');

      //ME PERMITE QUE SE EJECUTE EL COD. QUE MUESTRA LOS NOMBRES DE LOS BOT.
      $('[data-toggle="tooltip"]').tooltip();
      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text('El Movimiento fue creado correctamente');
      $('#modalCas').modal('hide');
      $('#mensajeExito').show();
    },
    error: function(response){
      console.log(response);
      const errorjson = response.responseJSON;
      if(typeof errorjson.id_casino != 'undefined'){
        mostrarErrorValidacion($('#selectCasinoIngreso'),parseError(errorjson.id_casino[0]),true);
      }
      if(typeof errorjson.id_tipo_movimiento != 'undefined'){
        mostrarErrorValidacion($('#tipo_movimiento_nuevo'),parseError(errorjson.id_tipo_movimiento[0]),true);
      }
    }
  })
});

/* 
 INGRESO
 ###########################
 ########            #######
 ########            #######
 ###########      ##########
 ###########      ##########
 ###########      ##########
 ###########      ##########
 ########            #######
 ########            #######
 ###########################
*/

//MOSTRAR MODAL PARA INGRESO: BTN NUEVO INGRESO
$(document).on('click', '.nuevoIngreso', function() {
  const id_movimiento= $(this).parent().parent().attr('id');
  $('#modalLogMovimiento .modal-title').text('SELECCIÓN DE TIPO DE CARGA');
  $('input[name="carga"]').attr('checked', false);
  $('#btn-aceptar-ingreso').prop('disabled',true);
  $('#modalLogMovimiento #cantMaqCargar').hide();
  $('#modalLogMovimiento').find("#id_log_movimiento").val(id_movimiento);
  //estilo de modal, y lo muestra
  $('#modalLogMovimiento .modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#tipoManual').prop('checked',true).click();
  $('#modalLogMovimiento').modal('show');


  $.get('movimientos/obtenerDatos/'+ id_movimiento, function(data){
    $('#conceptoExpediente').text(data.expediente.concepto);
    if(data.movimiento.tipo_carga!=null){
      $('#modalLogMovimiento #cantMaqCargar').show();
      if(data.movimiento.tipo_carga==1){
        $('#tipoManual').prop('checked',true).prop('disabled',true);
        $('#tipoCargaSel').prop('disabled',true);
      }
      if(data.movimiento.tipo_carga==2){
        $('#tipoCargaSel').prop('checked',true).prop('disabled',true);
        $('#tipoManual').prop('disabled',true);
      }
      $("#cant_maq").val(data.movimiento.cantidad).prop('disabled',true);
      $('#btn-aceptar-ingreso').prop('disabled',false);
    }
    else{
      $('#tipoManual').prop('disabled',false);
      $('#tipoCargaSel').prop('disabled',false);
      $('#cant_maq').val(1).prop('disabled',false);
    }
  })
}); //FIN DE EL NUEVO INGRESO

//DETECTAR SI EL TIPO DE CARGA SELECCIONADO ES MANUAL
$('#tipoManual').click(function(){
  const s = $('#modalLogMovimiento #tipoManual').val();
  if(s==1){ //TIPO DE CARGA: MANUAL
    $('#modalLogMovimiento #cantMaqCargar').show();
    $('#btn-aceptar-ingreso').prop('disabled',false);
  }
})
//DETECTAR EL TIPO DE CARGA SELECCIONADO ES MASIVA
$('#tipoCargaSel').click(function(){
  const s = $('#modalLogMovimiento #tipoCargaSel').val();
  if(s==2){ //TIPO DE CARGA: MASIVA
    $('#modalLogMovimiento #cantMaqCargar').hide();
  }
  $('#btn-aceptar-ingreso').prop('disabled',false);
});

//minimiza modal SELECCION INDIVIDUAL/MASIVO PARA INGRESOS
$('#btn-minimizar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }
  else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//BOTÓN ACEPTAR dentro del modal ingreso
$("#btn-aceptar-ingreso").click(function(e){
  const id = $("#id_log_movimiento").val();
  const cant_maq = $("#cant_maq").val();
  const t_carga = $('input:radio[name=carga]:checked').val();

  if (typeof cant_maq == "undefined" ) {
    $('#mensajeErrorCarga').text('Debe especificar la cantidad de máquinas que va a cargar');
    $('#mensajeErrorCarga').show();
  }

  else {
    const formData = {
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
        //Busco la fila que contiene el id del movimiento indicado
        let fila = $("#tablaResultados tbody").find('#' + id);
        //seteo en el btn de carga el tipo de carga
        fila.attr("data-carga",data.tipo_carga);

        $('#modalLogMovimiento').modal('hide');
        fila.find('.boton_cargar').show();
        $('#' + id).find('.nuevoIngreso').attr('style', 'display:none');;
      },
      error: function(data){
        mensajeError(sacarErrores(data));
      }
    })
  } //fin del else
}); //FIN DEL BTN ACEPTAR

//ABRIR MODAL DE NUEVA MÁQUINA
$(document).on('click', '.boton_cargar', function(e){
  let boton = $(this);
  e.preventDefault();
  boton.tooltip('hide');

  const mov = boton.parent().parent().attr('id');
  $('#modalMaquina').find('#id_movimiento').val(mov);

  //Ver que tipo de carga de máqunas se hace.
  //MANUAL
  if(boton.parent().parent().attr('data-carga') == 1){
    //muestra tab de maquinas y oculto el resto
    $.get('movimientos/obtenerDatos/'+ mov, function(data){
      ultimo_boton_carga = boton;
      eventoNuevo(data.movimiento, data.expediente);
    })
  }
  //MASIVA
  else {
    $('#modalCargaMasiva .modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
    $('#modalCargaMasiva').modal('show');
  }
});

function eventoNuevo(movimiento, expediente){
  //Modificar los colores del modal
  $('#modalMaquina .modal-title').text('NUEVA MÁQUINA TRAGAMONEDAS');
  $('#modalMaquina .modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
  $('#btn-guardar').removeClass('btn-warning');
  $('#btn-guardar').addClass('btn-success');
  $('#btn-guardar').text('CREAR MTM');
  $('#btn-guardar').val("nuevo");
  $('#btn-guardar').prop('disabled',false).show();
  $('#btn-guardar').css('display','inline-block');
  $('#marca_juego_check').prop('checked',true).trigger('change');
  //como estoy creando id = 0
  $('#id_maquina').val(0);
  const option_casino = $('#dtpCasinoMov option[value="'+movimiento.id_casino+'"]').clone();
  $('#selectCasino').empty().append(option_casino).prop('disabled',true)
  .val(movimiento.id_casino).trigger('change');
  mostrarJuegos(movimiento.id_casino,[],null);

  $('#modalMaquina  .seccion').hide();
  $('#modalMaquina  .navModal a').removeClass();
  $('#navMaquina').addClass('navModalActivo');
  $('#secMaquina').show();

  //Setear el expediente
  $('#M_expediente').val(expediente.id_expediente);
  $('#M_nro_exp_org').val(expediente.nro_exp_org).prop('readonly',true);
  $('#M_nro_exp_interno').val(expediente.nro_exp_interno).prop('readonly',true);
  $('#M_nro_exp_control').val(expediente.nro_exp_control).prop('readonly',true);

  //Setear la cantidad de máquinas pendientes
  if (movimiento.cantidad == 1) {
    $('#maquinas_pendientes').text(' ' + movimiento.cant_maquinas+ ' MÁQUINA PENDIENTE A CARGAR');
  }else {
    $('#maquinas_pendientes').text(' ' + movimiento.cant_maquinas + ' MÁQUINAS PENDIENTES A CARGAR');
  }

  $('#modalMaquina').modal('show');
}

$('#modalMaquina #nro_admin').on("keyup", function(e){
  const text = "NUEVA MÁQUINA TRAGAMONEDAS N°: " + $(this).val();
  $('#modalMaquina .modal-title').text(text);
});

//ABRIR MODAL DE CARGA MASIVA
$('.cargar2').click(function(e){
  e.preventDefault();
  //Modificar los colores del modal
  $('#modalCargaMasiva .modal-title').text('| NUEVA CARGA MASIVA');
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
  let formData=new FormData();
  formData.append('file',$('#cargaMasiva')[0].files[0]);
  formData.append('id_casino' , $('#contenedorCargaMasiva').val());

  for(const pair of formData.entries()) {
    console.log(pair[0]+ ', '+ pair[1]);
  }

  $.ajax({
    type: 'POST',
    url: '/movimientos/cargaMasiva',
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    success: function (data){
      $('#frmCargaMasiva').trigger('reset');
      $('#modalCargaMasiva').modal('hide');
    },
    error: function(data){
        alert('error');
    },
  });
}); //FIN DEL POST PARA ENVIAR ARCHIVO DE C. MASIVA

//Enviar a fiscalizar las de ingreso **************************
$(document).on('click','.enviarIngreso',function(e){
  const id_log_movimiento = $(this).parent().parent().attr('id');
  $('#modalEnviarFiscalizarIngreso .modal-title').text('SELECCIÓN DE MTMs PARA ENVÍO A FISCALIZAR');
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
  const id = $("#modalEnviarFiscalizarIngreso #id_log_movimiento").val();
  let maquinas_seleccionadas = [];
  const fecha = $('#B_fecha_ingreso').val();

  $('#tablaMaquinas tbody tr').each(function(){
    const check=$(this).find('td input[type=checkbox]');
    console.log(check);

    if (check.prop('checked')) {
        maquinas_seleccionadas.push($(this).attr('id'));
    }
  });

  const formData= {
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
      $('#modalEnviarFiscalizarIngreso').modal('hide');
      mensajeExitoMovimientos({
        titulo: 'ENVÍO EXITOSO',
        mensajes: ['Las máquinas fueron enviadas correctamente']
      });
    },
    error: function(data){
      console.log(data);
      mensajeError(sacarErrores(data));
    }
  })
})
//FIN ENVIAR A FISCALIZAR INGRESO****************************************

/* 
 VOLVER A RELEVAR
 ###########################
 ####    #############    ##
 #####    ###########    ###
 ######    #########    ####
 #######    #######    #####
 ########    #####    ######
 #########    ###    #######
 ##########    #    ########
 ############     ##########
 ###########################
*/

// **************************************MODAL VOLVER A RELEVAR ********************************************************
$(document).on('click','.botonToma2',function(){
  $('#btn-enviar-egreso').hide();
  $('#btn-enviar-toma2').show();

  const fila = $(this).parent().parent();
  const id_casino = fila.attr('data-casino');
  const id_mov = fila.attr('id');
  const t_mov = fila.attr('data-tipo');
  const estado = fila.attr('data-estado');

  $('#modalLogMovimiento2 .modal-title').text('CARGAR MÁQUINAS A RE-RELEVAR');
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
  const tipo=$('#modalLogMovimiento2').find('#tipo_movi').val();
  const id_log_movimiento = $(this).val();
  let maquinas = $('#tablaMaquinasSeleccionadas tbody > tr');

  $.each(maquinas, function(index, value){
    const maquina={
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

  const formData = {
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
    }
  });
};
/* 
 EGRESO Y CAMBIO LAYOUT (y reingreso?)
 ########################### ###########################
 ########            ####### ######                #####
 ########            ####### ######                #####
 ########     ############## ######       ##############
 ########          ######### ######       ##############
 ########          ######### ######       ##############
 ########     ############## ######       ##############
 ########            ####### ######                #####
 ########            ####### ######                #####
 ########################### ########################### 
*/
// **************************************MODAL NUEVO EGRESO ********************************************************

$(document).on('click','.nuevoEgreso',function(){
  $('#btn-enviar-egreso').show();
  $('#btn-enviar-toma2').hide();
  ocultarErrorValidacion($('#B_fecha_egreso'));
  $('#B_fecha_egreso').val(' ');

  const fila = $(this).parent().parent();
  const id_casino = fila.attr('data-casino');
  const id_mov = fila.attr('id');
  const t_mov = fila.attr('data-tipo');

  $('#modalLogMovimiento2 .modal-title').text('CARGAR MÁQUINAS A EGRESAR');
  $('#tablaMaquinasSeleccionadas tbody tr').remove();
  $('#modalLogMovimiento2').find('#tipo_movi').val(t_mov);
  $('#modalLogMovimiento2').find('#mov').val(id_mov);
  maq_seleccionadas=[];

  $('#inputMaq').generarDataList("maquinas/obtenerMTMMovimientos/"  + id_casino + '/' + t_mov + '/' + id_mov  ,'maquinas','id_maquina','nro_admin',1,true);
  if(t_mov == 8){
      $('#modalLogMovimiento2 .modal-title').text('SELECCIÓN DE MTMs PARA REINGRESO');
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
          $('#modalLogMovimiento2').modal('show');
        }//fin FOR
      }
      else{ //no hay máquinas
        $('#tablaMaquinasSeleccionadas tbody tr').remove();
        $('#isla_layout').hide();
        $('#btn-enviar-egreso').prop('disabled',true);
        if(t_mov==8){
          $('#btn-pausar').hide();
        }
        else{
          $('#btn-pausar').prop('disabled',true);
        }
        $('#modalLogMovimiento2').modal('show');
      }
    });
  }
  else{ //CAMBIO LAYOUT
    $('#modalLogMovimiento2 .modal-title').text('SELECCIÓN DE MTMs QUE CAMBIARON DE ISLA');
    $.get('movimientos/mostrarMaquinasMovimientoLogClick/' + id_mov , function(data){
      $('#tablaMaquinasSeleccionadas tbody tr').remove();

      if(data!=null){
        for (var i = 0; i < data.length; i++) {
          agregarMaq(data[i].id_maquina, data[i].nro_admin,
                      data[i].marca, data[i].modelo, data[i].nro_isla,
                      data[i].nombre_juego, data[i].nro_serie);
        }

        $('#modalLogMovimiento2').modal('show');
      }
      else {
        $('#tablaMaquinasSeleccionadas tbody tr').remove();
        $('#modalLogMovimiento2').modal('show');
        $('#btn-enviar-egreso').prop('disabled',true);
        $('#btn-pausar').prop('disabled',true);
      }
    });
  }

  $('#mensajeExito').hide();
  $('#mensajeFiscalizacionError').hide();
  $('#btn-enviar-egreso').val(id_mov);
});

//click mas para agregar máquinas
$('#agregarMaq').click(function(e){
  const id_maquina = $('#inputMaq').attr('data-elemento-seleccionado');
  if (id_maquina != 0) {
    $.get('http://' + window.location.host +"/maquinas/obtenerMTM/" + id_maquina, function(data) {
      agregarMaq(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.marca ,
                 data.maquina.modelo, data.isla.nro_isla,data.juego_activo.nombre_juego,
                 data.maquina.nro_serie);
      $('#inputMaq').setearElementoSeleccionado(0 , "");
      console.log('555:',data);
    });
  }
});

function agregarMaq(id_maquina, nro_admin, marca, modelo, isla, nombre_juego,nro_serie) {
  const tipo= $('#modalLogMovimiento2').find('#tipo_movi').val();
  let fila = $('<tr>').attr('id', id_maquina);
  const accion = $('<button>').addClass('btn btn-danger borrarMaq')
                            .append($('<i>').addClass('fa fa-fw fa-trash'));
  
  fila.append($('<td>').text(nro_admin));
  fila.append($('<td>').text(marca));
  fila.append($('<td>').text(limpiarNull(modelo)));
  //tipo de movimiento 4: CAMBIO LAYOUT
  if(tipo!=4){
    //Se agregan todas las columnas para la fila
    fila.append($('<td>').text(nombre_juego));
    fila.append($('<td>').text(limpiarNull(nro_serie)));
    fila.append($('<td>').append(accion));
  }else{
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
  const tipo= $('#modalLogMovimiento2').find('#tipo_movi').val();
  const id_log_movimiento = $(this).val();
  const fecha = $('#B_fecha_egreso').val();
  const maquinas = $('#tablaMaquinasSeleccionadas tbody > tr');

  $.each(maquinas, function(index, value){
    var maquina={
      id_maquina:$(this).attr('id')
    }
    maq_seleccionadas.push(maquina);
  });
  //USA LA FC DE POST, ENVIANDO EN TRUE EL ATRIBUTO DE CARGA FINALIZADA
  if(tipo!=8){
    enviarFiscalizar(id_log_movimiento,maq_seleccionadas, fecha, true,false);
  }else{//es reingreso
    enviarFiscalizar(id_log_movimiento,maq_seleccionadas, fecha, true,true);
  }
});

//Pausa la carga de maquinas a fiscalizar
$('#btn-pausar').click(function(e){
  const tipo = $('#modalLogMovimiento2').find('#tipo_movi').val();
  const id_log_movimiento = $('#modalLogMovimiento2').find('#mov').val();
  const maquinas = $('#tablaMaquinasSeleccionadas tbody > tr');
  const fecha = $('#B_fecha_egreso').val();

  $.each(maquinas, function(index, value){
    const maquina={
      id_maquina:$(this).attr('id')
    }
    maq_seleccionadas.push(maquina);
  });

  //USA LA FC DE POST, ENVIANDO EN FALSE EL ATRIBUTO DE CARGA FINALIZADA
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

  const formData = {
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
      let response = data.responseJSON.errors;

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


//MODAL BAJA MTM EN EL MOVIMIENTO EGRESO/REINGRESO

$(document).on('click','.bajaMTM', function(){
  const fila = $(this).parent().parent();
  const casino = fila.attr('data-casino');
  const id_movimiento= fila.attr('id');
  const tipo_mov= fila.attr('data-tipo');

  $('#modalBajaMTM .modal-title').text('CARGAR MÁQUINAS PARA EGRESO DEFINITIVO');
  $('#modalBajaMTM').find('#tipoMovBaja').val(tipo_mov);
  $('#modalBajaMTM').find('#movimId').val(id_movimiento);

  $('#inputMaq3').generarDataList("maquinas/obtenerMTMMovimientos/"  + casino + '/' + tipo_mov + '/' + id_movimiento  ,'maquinas','id_maquina','nro_admin',1,true);

  $('#tablaBajaMTM tbody tr').remove();
  $('#btn-baja').prop('disabled', false);
  $('#mensajeExito').hide();
  $('#modalBajaMTM').modal('show');
})

//crea tabla

$('#agregarMaqBaja').click(function(e) {
  const id_maq = $('#inputMaq3').attr('data-elemento-seleccionado');
  if (id_maq != 0) {
    $.get("/maquinas/obtenerMTM/" + id_maq, function(data) {
      agregarMaqBaja(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.marca, data.maquina.modelo, 1);
      $('#inputMaq3').setearElementoSeleccionado(0 , "");
    });
  }
});

function agregarMaqBaja(id_maquina, nro_admin, marca, modelo,p) {
  let fila = $('<tr>').attr('id', id_maquina);
  const accion = $('<button>').addClass('btn btn-danger borrarMaqCargada')
                              .append($('<i>').addClass('fa fa-fw fa-trash'));
  const t_mov = $('#modalBajaMTM').find('#tipoMovBaja').val();

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
  const tipo=$('#modalBajaMTM').find('#tipoMovBaja').val();
  const id_log_movimiento = $('#modalBajaMTM').find('#movimId').val();
  let maquinas = $('#tablaBajaMTM tbody > tr');
  let mtmParaBaja = [];
  $.each(maquinas, function(index, value){
    const maquina = {
      id_maquina:$(this).attr('id')
    }
    mtmParaBaja.push(maquina);
  });

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  const formData = {
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

//*******************************************************************************************************************************
/* 
 DENOMINACION DEVOLUCION JUEGO
 ########################### ###########################  ########################### 
 ######           ########## ##      ###################  ########           ########
 ######   ########   ####### ##  ###   #################  ########           ########
 ######   ##########   ##### ##  ####   ################  ###########     ###########
 ######   ##########   ##### ##  ###   ####   #####   ##  ###########     ###########
 ######   ##########   ##### ##      #######   ###   ###  ###########     ###########
 ######   ##########   ##### ################   #   ####  #####   ###     ###########
 ######   ######     ####### #################     #####  #####           ###########
 ######           ########## ###########################  #######         ###########
 ########################### ###########################  ########################### 
*/
//*************BOTÓN NUEVO DE MOVIMIENTO: DENOMINACION **************************************************

$(document).on('click','.modificarDenominacion',function(){
  const fila = $(this).parent().parent();
  const casino = fila.attr('data-casino');
  const mov = fila.attr('id');
  const tmov = fila.attr('data-tipo');
  $('#denom_comun').val(' ');
  $('#devol_comun').val(' ');
  $('#unidad_comun').val(' ');
  ocultarErrorValidacion($('#B_fecha_denom'));
  $('#B_fecha_denom').val(' ');

  $('#modalDenominacion').find('#id_t_mov').val(tmov);
  $('#modalDenominacion').find('#id_mov_denominacion').val(mov);

  $('#inputMaq2').generarDataList("maquinas/obtenerMTMEnCasinoMovimientos/" + casino + '/' + mov, 'maquinas','id_maquina','nro_admin',1,true);
  $('#inputIslaDen').generarDataList("eventualidades/obtenerIslaEnCasino/"  + casino, 'islas', 'id_isla','nro_isla',1,true);
  $('#inputSectorDen').generarDataList("eventualidades/obtenerSectorEnCasino/" + casino, 'sectores','id_sector','descripcion',1,true);

  switch (tmov) {
    case '5'://denominación
      $('#modalDenominacion .modal-title').text('ASIGNACIÓN: CAMBIO DE DENOMINACIÓN DE JUEGO');
      $('#segunda_columna').show().text('DENOMINACIÓN');
      $('#tercer_columna').show().text('');
      $('#cuarta_columna').show().text('');
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
      $('#modalDenominacion .modal-title').text('ASIGNACIÓN: CAMBIO DE %DEV DE JUEGO');
      $('#segunda_columna').show().text('% DEVOLUCIÓN');
      $('#tercer_columna').show().text('');
      $('#cuarta_columna').show().text('');
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
      $('#modalDenominacion .modal-title').text('ASIGNACIÓN: CAMBIO DE JUEGO');
      $('#segunda_columna').show().text('JUEGO');
      $('#tercer_columna').show().text('DENOMINACIÓN');
      $('#cuarta_columna').show().text('% DEVOLUCIÓN');
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
      $('#modalDenominacion .modal-title').text('SELECCIÓN DE MTMs PARA ENVÍO A FISCALIZAR');
      break;
  }
  $('#tablaDenominacion tbody tr').remove();
  $.get('movimientos/buscarMaquinasMovimiento/' + mov, function(data){
    if(data.maquinas.length != 0){
        console.log('77',data);
        data.maquinas.forEach(m => {
          agregarMaqDenominacion(
            m.maquina.id_maquina, m.maquina.nro_admin,
            m.maquina.denominacion, m.juegos,
            m.juego_seleccionado.id_juego, m.juego_seleccionado.nombre_juego,
            m.maquina.porcentaje_devolucion, m.maquina.id_unidad_medida, 
            data.unidades, 2);
        });
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

$('#agregarMaq2').click(function(e) {
  const id_maq = $('#inputMaq2').attr('data-elemento-seleccionado');
  if (id_maq != 0) {
    $.get('http://' + window.location.host +"/movimientos/obtenerMTM/" + id_maq, function(data) {
      agregarMDenominacion(data.maquina.id_maquina, data.maquina.nro_admin, data.maquina.denominacion,
        data.maquina.porcentaje_devolucion,data.maquina.id_unidad_medida, data.unidades, 1 , data.juego_activo);
      $('#inputMaq2').setearElementoSeleccionado(0 , "");
    });
  }
});

function agregarMaqDenominacion(id_maquina, nro_admin, denom, juegos, id_juego,nombre_juego, dev, unidad_seleccionada, unidades, p) {
  let fila = $('<tr>').attr('id', id_maquina);
  const accion = $('<button>').addClass('btn btn-danger borrarMaq')
                            .append($('<i>').addClass('fa fa-fw fa-trash'));
  const t_mov = $('#modalDenominacion').find('#id_t_mov').val();

  //Se agregan todas las columnas para la fila
  fila.append($('<td>').text(nro_admin))
  //TIPO DE MOVIMIENTO ES DENOMINACION:
  if(t_mov==5){
    fila.append($('<td>')
        .append($('<input>')
        .addClass('denominacion_modificada form-control')
        .val(denom)));

    let select = $('<select>').addClass('unidad_denominacion form-control');

    for (var j = 0; j < unidades.length; j++) {
      const tipo = unidades[j].descripcion;
      const id = unidades[j].id_unidad_medida;
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
    fila.append($('<td>').append(input)); //falta el denom y el devol
    input.generarDataList("movimientos/buscarJuegoMovimientos", 'juegos','id_juego','nombre_juego',1);
    input.setearElementoSeleccionado(id_juego,nombre_juego);
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

$('#agregarIslaDen').click(function(e){
  const id_isla = $('#inputIslaDen').attr('data-elemento-seleccionado');
  if (id_isla != 0) {
    $.get('movimientos/obtenerMaquinasIsla/' + id_isla, function(data) {
      console.log('ff', data);
      for (var i = 0; i < data.maquinas.length; i++) {
        agregarMDenominacion(data.maquinas[i].id_maquina, data.maquinas[i].nro_admin, data.maquinas[i].denominacion,
                             data.maquinas[i].porcentaje_devolucion,data.maquinas[i].id_unidad_medida, data.unidades, 1, data.maquinas[i].juego_obj);
      }
      $('#inputIslaDen').setearElementoSeleccionado(0 , "");
    });
  }
});

$('#agregarSectorDen').click(function(e){
  const id_isla = $('#inputSectorDen').attr('data-elemento-seleccionado');
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
});

$('#btn-borrarTodo').on('click', function() {
  $('#tablaDenominacion tbody tr').remove();
});

function agregarMDenominacion(id_maquina, nro_admin, denom, dev, unidad_seleccionada, unidades, p , juego_activo) {
  let fila = $('<tr>').attr('id', id_maquina);
  fila.append($('<td>').text(nro_admin));
  
  // se busca migrar la denominacion a valores validos, por lo que se la convierte a numerico
  const denFloat = denominacionToFloat(denom);
  const denominacion_modificada = $('<input>').addClass('denominacion_modificada form-control').attr("type","number")
  .attr("step","0.01").attr("min","0.01").val(denFloat);
  const devolucion_modificada = $('<input>').addClass('devolucion_modificada form-control').attr("type","number")
  .attr("step","0.01").attr("min","80").attr("max","100").val(dev);
  let juego_modif = $('<input>').addClass('juego_modif form-control').attr('placeholder', "Nombre Juego");

  const t_mov = $('#modalDenominacion').find('#id_t_mov').val();
  switch(t_mov){
    case "5": {//DENOMINACION
      fila.append($('<td>').append(denominacion_modificada));
      // se agrega elementos vacios para que sea aceptable visiblemente
      fila.append($('<td>'));
      fila.append($('<td>'));
    }break;
    case "6": {//DEVOLUCION
      fila.append($('<td>').append(devolucion_modificada));
      // se agrega elementos vacios para que sea aceptable visiblemente
      fila.append($('<td>'));
      fila.append($('<td>'));
    }break;
    case "7": {//JUEGO
      fila.append($('<td>').append(juego_modif)); //falta el denom y el devol
      juego_modif.generarDataList("movimientos/buscarJuegoMovimientos", 'juegos', 'id_juego', 'nombre_juego', 1);
      // setea el valor actual en el buscador de juego
      juego_modif.setearElementoSeleccionado(juego_activo.id_juego, juego_activo.nombre_juego);
      // agrega denominacion de juego
      fila.append($('<td>').append(denominacion_modificada));
      // agrega % dev de juego
      fila.append(devolucion_modificada);
    } break;
    default:{
    }break;
  }

  //"p" indica si ya viene cargada la tabla o no, para agregar o no el boton de borrar
  if (p==1) {
    const accion = $('<button>').addClass('btn btn-danger borrarMaq')
                  .append($('<i>').addClass('fa fa-fw fa-trash'));
    fila.append($('<td>').append(accion));
  }
  //Agregar fila a la tabla
  $('#tablaDenominacion tbody').append(fila);
  //Habilitar botones
  $('#btn-enviar-denom').prop('disabled', false);
  $('#btn-pausar-denom').prop('disabled', false);
};

$('#todosDen').on('click', function(){
  const den_comun = $('#denom_comun').val();
  let tabla = $('#tablaDenominacion tbody > tr');
  if (den_comun != ""){
    tabla.find('.denominacion_modificada').val(den_comun);;
  };
})
$('#todosDev').on('click', function(){
  const dev_comun = $('#devol_comun').val();
  let tabla = $('#tablaDenominacion tbody > tr');
  if (dev_comun!=""){
    tabla.find('.devolucion_modificada').val(dev_comun);
  };
})
//cierra modal y limpio el data list de arriba
$('#modalDenominacion').on('hidden.bs.modal', function() {
  $('.input-data-list').borrarDataList();
})

//BOTÓN ENVIAR A FISCALIZAR DE DENOMINACION, DEVOLUCION Y JUEGO
$(document).on('click','#btn-enviar-denom',function(e){
  const id_log_movim = $('#modalDenominacion').find('#id_mov_denominacion').val();
  const tipo =  $('#modalDenominacion').find('#id_t_mov').val();
  const tabla_maq = $('#tablaDenominacion tbody > tr');
  let maquinas = [];
  const fecha = $('#B_fecha_denom').val();

  $.each(tabla_maq, function(index, value){
    let maquina = {
      id_maquina : $(this).attr('id'),
      id_juego : "",
      denominacion : "",
      porcentaje_devolucion : "",
      id_unidad_medida : ""
    };
    //Según el tipo de movimiento genera distintos json de máquinas
    switch (tipo) {
      //Tipo Movimiento: DENOMINACION
      case '5': {
        maquina.denominacion = $(this).find('.denominacion_modificada').val();
        maquina.id_unidad_medida = $(this).find('.unidad_denominacion').val();
      }break;
      //Tipo Movimiento: % DEVOLUCION
      case '6': {
        maquina.porcentaje_devolucion = $(this).find('.devolucion_modificada').val();
      }break;
      //Tipo Movimiento: JUEGO
      case '7': {
        maquina.id_juego = $(this).find('.juego_modif').obtenerElementoSeleccionado();
        maquina.denominacion = $(this).find('.denominacion_modificada').val();
        maquina.porcentaje_devolucion = $(this).find('.devolucion_modificada').val();
      }break;
    }
    maquinas.push(maquina);
  });
  //USA LA FC DE POST, ENVIANDO EN TRUE EL ATRIBUTO DE CARGA FINALIZADA
  enviarDenominacion(id_log_movim, maquinas, fecha, true);
});

//Pausa la carga de maquinas a fiscalizar
$(document).on('click','#btn-pausar-denom',function(e){
  const id_log_movim = $('#modalDenominacion').find('#id_mov_denominacion').val();
  const tipo =  $('#modalDenominacion').find('#id_t_mov').val();
  const tabla_maq = $('#tablaDenominacion tbody > tr');
  let maquinas = [];
  const fecha = $('#B_fecha_denom').val();

  $.each(tabla_maq, function(index, value){
    let maquina = {
      id_maquina : $(this).attr('id'),
      id_juego : "",
      denominacion : "",
      porcentaje_devolucion : "",
      id_unidad_medida : ""
    };
    //Según el tipo de movimiento genera distintos json de máquinas
    switch (tipo) {
        //Tipo Movimiento: DENOMINACION
        case '5': {
          maquina.denominacion = $(this).find('.denominacion_modificada').val();
          maquina.id_unidad_medida = $(this).find('.unidad_denominacion').val();
        }break;
        //Tipo Movimiento: % DEVOLUCIÓN
        case '6': {
          maquina.porcentaje_devolucion = $(this).find('.devolucion_modificada').val();
        }break;
        //Tipo Movimiento: JUEGO
        case '7': {
          maquina.id_juego = $(this).find('.juego_modif').obtenerElementoSeleccionado();
        }break;
      }
    maquinas.push(maquina);
   });
  //USA LA FC DE POST, ENVIANDO EN FALSE EL ATRIBUTO DE CARGA FINALIZADA
  enviarDenominacion(id_log_movim, maquinas, fecha, false);
});

//FUNCION PARA ENVIAR EL POST AL CONTROLADOR, CON LOS CAMBIOS GENERADOS
function enviarDenominacion(id_mov,maq,fecha,fin){
  $('#mensajeExito').hide();

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  const formData = {
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
      if(fin) mensajeExitoMovimientos({titulo: 'ENVÍO', mensajes : ['Las máquinas han sido enviadas correctamente.']});
      else    mensajeExitoMovimientos({titulo: 'GUARDADO', mensajes : ['Las máquinas han sido guardadas en el movimiento.']});
      $('#modalDenominacion').modal('hide');
    },
    error: function (data) {
      mensajeError(sacarErrores(data));
    },
  });
};
//**********FIN MODAL PARA MODIFICAR JUEGO, DENOMINACION Y DEVOLUCION ******************
/* 
 VALIDAR
 ###########################
 ###################### ####
 ####################  #####
 ###################  ######
 #################   #######
 ################   ########
 #######  #####    #########
 ########   ##    ##########
 ##########     ############
 ###########################
*/
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

  const id_log_movimiento = $(this).parent().parent().attr('id');
  $.get('movimientos/ValidarMovimiento/' + id_log_movimiento, function(data){
      let tablaFiscalizacion = $('#tablaFechasFiscalizacion tbody');

      for (var i = 0; i < data.length; i++) {
        let fila = $('<tr>');

        fila.append(
            $('<td>').addClass('col-xs-6')
            .text(data[i].fecha_envio_fiscalizar)
        );
        fila.append(
          $('<td>')
          .addClass('col-xs-3')
          .append(
            $('<button>').append(
              $('<i>').addClass('fa').addClass('fa-fw').addClass('fa-eye')
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
      let cantidad=0;
      $('#tablaFechasFiscalizacion tbody tr').each(function(){
        if ($(this).hasClass('finalizado')) {
          cantidad = cantidad + 1;
        }
      });
      if (cantidad == data.length) {
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

  const id_fiscalizacion = $(this).attr('data-id-fiscalizacion');
  const fecha_fiscalizacion = $(this).attr('data-fecha-fisc');

  //guardo la fecha de fiscalizacion en el input del modal
  $('#modalValidacion').find('#fecha_fiscalizacion').val(fecha_fiscalizacion);

  $.get('movimientos/ValidarFiscalizacion/' + id_fiscalizacion, function(data){
    if(data.Maquinas.id_estado_fiscalizacion!=4){
      $('#finalizarValidar').hide();
    }

    var tablaMaquinasFiscalizacion=$('#tablaMaquinasFiscalizacion tbody');
    $('#tablaMaquinasFiscalizacion tbody tr').remove();
    let cant_maq_val = 0;
    cant_validadas = data.Maquinas.length;
    for (var i = 0; i < data.Maquinas.length; i++) {
        var fila= $('<tr>');

        fila.attr('data-id',data.Maquinas[i].id_maquina)
        .append(
          $('<td>').addClass('col-xs-4')
          .text(data.Maquinas[i].nro_admin)
        )
        fila.append(
          $('<td>')
          .addClass('col-xs-4')
          .append(
            $('<button>')
            .append(
              $('<i>').addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
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
          cant_maq_val = cant_maq_val + 1;
          $('#enviarValidar').hide();
          fila.append(
            $('<td>')
            .addClass('col-xs-4')
            .append($('<i>').addClass('fa fa-fw fa-check').css('color','#4CAF50'))
          );
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
  const id_maquina = $(this).attr('data-maquina');
  const id_fiscalizacion = $(this).attr('data-fiscalizacion');
  let tablaContadores = $('#tablaValidarIngreso tbody');
  const id_relevamiento = $(this).attr('data-relevamiento');
  $('#tablaMaquinasFiscalizacion tbody tr').css('background-color','#FAFAFA');
  $(this).parent().parent().css('background-color', '#E0E0E0');

  //guardo el id_maquina en el input maquina del modal
  $('#modalValidacion').find('#maquina').val(id_maquina);
  $('#modalValidacion').find('#relevamiento').val(id_relevamiento);
  $('#mensajeExitoValidacion').hide();

  $('#tablaValidarIngreso tbody tr').remove();

  $.get('movimientos/ValidarMaquinaFiscalizacion/' + id_relevamiento, function(data){
    if(data.toma.id_estado_relevamiento==4){
      $('#enviarValidar').hide();
    }
    else{
      $('#enviarValidar').show();
      $('#errorValidacion').show();
    }
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
    const cont = "cont";
    const vcont = "vcont";
    let fila1 = $('<tr>');

    for (var i = 1; i < 7; i++) {
      let fila = fila1.clone();
      const p = data.toma[cont + i];
      const v = data.toma[vcont + i];
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
        if(p != null){ //si toma nueva es != null
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
          }
          else{
            fila.append($('<td align="center">')
              .addClass('col-xs-2')
              .append($('<span>').text(' '))
              .addClass('boton_x_toma')
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-times-circle-o').css('color','#D50000'))
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
      $('#observacionesToma').text(data.toma.observaciones);
    }
    else{
      $('#observacionesToma').text(' ');
    }
    //guardo el id_fiscalizacion en el boton enviarValidar
    $('#modalValidacion').find('#enviarValidar').val(id_fiscalizacion);

    $('.detalleMaq').show();
    $('.validar').prop('disabled', false);
    $('.error').prop('disabled',false);
  });
});

//BOTÓN VALIDAR DENTRO DEL MODAL VALIDAR
$(document).on('click','#enviarValidar',function(){
  $('#errorValidacion').hide();
  const id_maquina = $('#modalValidacion').find('#maquina').val();
  const id_relevamiento = $('#modalValidacion').find('#relevamiento').val();
  validar(id_relevamiento, 1,id_maquina);
});

//cuando cierra el modal de validación, actualizo el listado
$("#modalValidacion").on('hidden.bs.modal', function () {
  $('#btn-buscarMovimiento').trigger('click');
})
//BOTÓN ERROR
$(document).on('click','#errorValidacion',function(){
  const id_relevamiento = $('#modalValidacion').find('#relevamiento').val();
  validar(id_relevamiento, 0);
});

//BOTÓN FINALIZAR VALIDACIÓN
$(document).on('click','#finalizarValidar',function(){
  const id_fiscalizacion = $(this).attr('data-fiscalizacion');
  $.get('movimientos/finalizarValidacion/' + id_fiscalizacion, function(data){
    if (data==1){
      $('#modalValidacion').modal('hide');
      mensajeExitoMovimientos({mensajes: ['Se ha VALIDADO correctamente el movimiento.']})
    }
  })
});

//POST PARA VALIDAR
function validar(id_rel, val, id_maquina){
  const formData = {
    id_relev_mov: id_rel,
    validado: val,
  }

  $.ajax({
    type: 'POST',
    url: 'movimientos/validarTomaRelevamiento',
    data: formData,
    dataType: 'json',
    success: function (data) {
      //Deshabilito los botones error y validar
      $('#enviarValidar').hide();
      $('.error').prop('disabled', true);
      $('.detalleMaq').hide();
      cant_validadas = cant_validadas - 1;

      $('#tablaMaquinasFiscalizacion tbody tr').each(function(){
        console.log($(this).attr('data-id'));
        const maq = $(this).attr('data-id');
        console.log('maquina', maq);

        if (maq == id_maquina){
          console.log('encontrada', $(this));
          $(this).append($('<td>').addClass('col-xs-4')
            .append($('<i>').addClass('fa fa-fw fa-check').css('color','#4CAF50')));
        }
      });
      console.log('cant_validadas',cant_validadas);
      //si se validaron todas las máquinas de la fecha
      if(cant_validadas==0){
        $('#finalizarValidar').show();
      }
    },
    error: function (data) {
      $('#mensajeErrorVal').show();
    }
  })
};

/* 
 OTROS (cosas que no van en las otras secciones)
 ###########################
 ########           ########
 ######               ######
 #####      #####      #####
 #####     #######     #####
 #####     #######     #####
 #####      #####      #####
 ######               ######
 ########           ########
 ###########################
*/
//-------------------------------------------------------------------------
//redirigir cambio layout
$(document).on('click','.redirigir',function(e){
  const id_movimiento=$(this).parent().parent().attr('id');

  const formData= {
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
    }
  });

  window.open('islas','_blank');
});

$(document).on('click','.print_mov',function(e){
  const id = $(this).parent().parent().attr('id');
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
  $('#mensajeExito').hide();
  $('#mensajeError').hide();
  const id_mov = $(this).parent().parent().attr('id');

  const formData = {
    id_log_movimiento: id_mov
  }

  modalEliminar(function(){
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
      success: function (response){
        mensajeExitoMovimientos({titulo:'ELIMINACIÓN EXITOSA',mensajes:['El movimiento fue eliminado correctamente']});
        $('#btn-buscarMovimiento').trigger('click');
      },
      error: function(response){
        console.log(response);
        mensajeError(sacarErrores(response));
      }
    });
  });
});

/* Detecta la confirmación para seguir cargando máquinas en movimientos */
$('#mensajeExito .confirmar').click(function(e){
  $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');
  $('#mensajeExito').hide();
  setTimeout(function(){
    if(ultimo_boton_carga != null) ultimo_boton_carga.click();
  },150);
});

/* Detecta la negativa para seguir cargando máquinas en movimientos */
$('#mensajeExito .salir').click(function(e){
  $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');
  $('#mensajeExito').hide();
  limpiarModal();
});

/* Cada vez que se abre un modal */
$('.modal').on('shown.bs.modal', function() {
  //Limpiar el mensaje de éxito. Sacar los botones y agregar animación
  $('#mensajeExito').removeClass('fijarMensaje mostrarBotones');
  //Luego se lo cierra
  $('#mensajeExito').hide();
});

/* 
 TABLA BUSQUEDA PRINCIPAL
 ###########################
 ##                       ##
 ##                       ##
 ##########     ############
 ##########     ############
 ##########     ############
 ##########     ############
 ##########     ############
 ##########     ############
 ###########################
*/

//Busqueda de movimientos
$('#btn-buscarMovimiento').click(function(e,pagina = null,page_size = null,columna = null,orden = null){
  $('#mensajeExito').hide();
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  e.preventDefault();

  page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  columna = (columna != null)? columna : $('#tablaResultados .activa').attr('value');
  orden = (orden != null)? orden : $('#tablaResultados .activa').attr('estado');
  const sort_by = (columna != null && orden != null)? { columna: columna, orden: orden } : null;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  const formData = {
    nro_exp_org: $('#B_nro_exp_org').val(),
    nro_exp_interno: $('#B_nro_exp_interno').val(),
    nro_exp_control: $('#B_nro_exp_control').val(),
    tipo_movimiento: $('#B_TipoMovimiento').val(),
    casino: $('#dtpCasinoMov').val(),
    fecha: $('#fecha_movimiento').val(),
    nro_admin: $('#busqueda_maquina').val(),
    id_log_movimiento: $('#busqueda_numero').val(),
    page: page_number != null? page_number : 1,
    page_size: page_size != null? page_size : 10,
    sort_by: sort_by,
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
  else if($(e.currentTarget).children('i').hasClass('fa-sort-desc')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado','asc');
  }
  else{
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  }
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndiceMov(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndiceMov(e,pageNumber = null,tam = null){
  if(e != null){
    e.preventDefault();
  }

  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaResultados .activa').attr('value');
  const orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscarMovimiento').trigger('click',[pageNumber,tam,columna,orden]);
}

function handleMovimientoIngreso(movimiento,fila){
  fila.find('.boton_nuevo').addClass('nuevoIngreso');
  fila.find('.boton_fiscalizar').addClass('enviarIngreso');
  fila.find('.boton_validar').addClass('validarMovimiento');
  fila.find('.boton_modificar').remove();
  fila.find('.boton_redirigir').remove();
  fila.find('.boton_cargar').hide();
  fila.find('.boton_baja').remove();
  fila.find('.baja_mov').addClass('bajaMov');
  fila.find('.boton_toma2').remove();
  const estado_movimiento = movimiento.id_estado_movimiento;
  const tiene_maquinas = movimiento.cant_maquinas !== null && movimiento.cant_maquinas != 0;
  if(estado_movimiento==8 || tiene_maquinas){
    fila.find('.boton_cargar').show();
    fila.find('.nuevoIngreso').attr('style', 'display:none');
    fila.find('.enviarIngreso').show();
    fila.attr('data-carga',1);
  }
  if(movimiento.cant_maquinas==0){
    fila.find('.enviarIngreso').show();
    fila.find('.boton_cargar').attr('style', 'display:none');
  }
  fila.find('.nuevoIngreso').toggle(estado_movimiento == 1);
  fila.find('.enviarIngreso').toggle(estado_movimiento == 8);
  fila.find('.enviarIngreso').toggle(estado_movimiento != 1);
}
function handleMovimientoEgreso(movimiento,fila){
  fila.find('.boton_nuevo').addClass('nuevoEgreso');
  fila.find('.boton_cargar').remove();
  fila.find('.boton_fiscalizar').remove();
  fila.find('.boton_validar').addClass('validarMovimiento');
  fila.find('.boton_modificar').remove();
  fila.find('.boton_redirigir').remove();
  fila.find('.boton_baja').addClass('bajaMTM');
  fila.find('.baja_mov').addClass('bajaMov');
  fila.find('.boton_toma2').remove();
  const estado_movimiento = movimiento.id_estado_movimiento;
  if(estado_movimiento == 4 || estado_movimiento == 5){
    fila.find('.bajaMTM').hide();
  }else{
    fila.find('.bajaMTM').prop('disabled', false);
  };
}
function handleMovimientoPorcDevolucion_Denominacion_Juego(movimiento,fila){
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
function handleMovimientoCambioLayout(movimiento,fila){
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

//paginacion
function generarFilaTabla(movimiento){
  let fila              = $('#filaEjemploMovimiento').clone().removeAttr('id','');
  const id = movimiento.id_log_movimiento;
  const t_mov             = movimiento.descripcion;
  const estado_movimiento = movimiento.id_estado_movimiento;
  const fecha = convertirDate(movimiento.fecha);
  const islas = (movimiento.islas != null)? movimiento.islas : '-';
  let expediente        = '-';
  if(movimiento.nro_exp_org != null){
      expediente        = movimiento.nro_exp_org + '-'
                        + movimiento.nro_exp_interno + '-'
                        + movimiento.nro_exp_control;
  }

  fila.attr('id', id);
  fila.find('.nro_mov').text(id).attr('title',id);
  fila.find('.fecha_mov').text(fecha).attr('title',fecha);
  fila.find('.nro_exp_mov').text(expediente).attr('title',expediente);
  fila.find('.islas_mov').text(islas).attr('title',islas);
  fila.find('.tipo_mov').text(t_mov).attr('title',t_mov); 

  let icono = fila.find('.icono_mov i');
  switch(estado_movimiento){
    case 1:{//NOTIFICADO
      icono = $('<i>').addClass('fas').addClass('fa-envelope')
      .css('color','rgb(66,133,244)').css('margin-left',' auto').css('margin-right', 'auto');
    }break;
    case 2:{//FISCALIZANDO
      icono = $('<i>').addClass('fas').addClass('fa-edit')
      .css('color','rgb(244,160,0)').css('margin-left',' auto').css('margin-right', 'auto');
    }break;
    case 3:{//FISCALIZADO
      icono = $('<i>').addClass('fas').addClass('fa-file-alt')
      .css('color','#66BB6A').css('margin-left',' auto').css('margin-right', 'auto');
    }break;
    case 4:{//VALIDADO
      icono = $('<i>').addClass('fa').addClass('fa-fw').addClass('fa-check')
      .css('color','#66BB6A').css('margin-left',' auto').css('margin-right', 'auto');
    }break;
    case 6:{//CREADO
      icono = $('<i>').addClass('fa').addClass('fa-fw').addClass('fa-plus')
      .css('color','rgb(150,150,150)').css('margin-left',' auto').css('margin-right', 'auto');
    }break;
    case 8:{//CARGANDO
      icono = $('<i>').addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
      .css('color','rgb(244,160,0)').css('margin-left',' auto').css('margin-right', 'auto');
    }break;
    default:{
    }break;
  }
  fila.find('.icono_mov i').replaceWith(icono);
  fila.find('.icono_mov').attr('title',movimiento.estado)
  fila.attr('data-casino',movimiento.id_casino);
  fila.attr('data-tipo',movimiento.id_tipo_movimiento);
  fila.attr('data-estado',movimiento.id_estado_movimiento);
  
  const handlers = {
    "INGRESO" : handleMovimientoIngreso, 
    "EGRESO"  : handleMovimientoEgreso,
    "EGRESO/REINGRESOS" : handleMovimientoEgreso,
    "% DEVOLUCIÓN" : handleMovimientoPorcDevolucion_Denominacion_Juego,
    "DENOMINACIÓN" : handleMovimientoPorcDevolucion_Denominacion_Juego,
    "JUEGO" : handleMovimientoPorcDevolucion_Denominacion_Juego,
    "CAMBIO LAYOUT" : handleMovimientoCambioLayout,
    "INGRESO INICIAL" : handleMovimientoIngreso,
    "EGRESO DEFINITIVO" : handleMovimientoEgreso
  };

  if(t_mov in handlers) handlers[t_mov](movimiento,fila);
  else fila.find('button,span').not('.print_mov').remove();

  fila.find('.validarMovimiento').toggle(estado_movimiento == 3);
  fila.find('.boton_toma2').toggle(estado_movimiento > 2);

  const es_intervencion_mtm = movimiento.puede_reingreso || movimiento.ṕuede_egreso_temporal;
  if(movimiento.deprecado || es_intervencion_mtm) fila.find('td').css('color','rgb(150,150,150)');
   
  return fila;
}

$('#collapseFiltros').keypress(function(e){
  if(e.charCode == 13){//Enter
    $('#btn-buscarMovimiento').click();
  }
})

/* 
 UTILIDADES
 ###########################
 ###       #######       ###
 ###       #######       ###
 ###       #######       ###
 ###       #######       ###
 ###       #######       ###
 ###       #######       ###
 ###                     ###
 ###                     ###
 ###########################
*/

function denominacionToFloat(den) {
  if (den=="" || den==null) {
    return parseFloat(0.01)
  }
  denf=den.replace(",",".")
  return parseFloat(denf)
}

//Convierte los errores standard de laravel a lenguaje normal.
function parseErrorMovimientos(response){
  if(response == 'validation.unique'){
    return 'El valor tiene que ser único y ya existe el mismo.';
  }
  else if(response == 'validation.required'){
    return 'El campo es obligatorio.'
  }
  else if(response == 'validation.max.string'){
    return 'El valor es muy largo.'
  }
  else if(response == 'validation.exists'){
    return 'El valor no es valido';
  }
  else if(response == 'validation.min.numeric'){
    return 'El valor no es valido';
  }
  return response;
}

//Saca los errores custom de un response y los retorna en una lista.
function sacarErrores(errorResponse){
  const errorjson = errorResponse.responseJSON;
  const keys  = Object.keys(errorjson);
  let msjs = [];
  keys.forEach(function(k){
    const list_msjs = errorjson[k];
    list_msjs.forEach(function(str){
      msjs.push(parseErrorMovimientos(str));
    });
  });
  return msjs;
}

//Toma una lista de strings y los muestra linea tras linea en el modal de errores.
function mensajeError(errores) {
  $('#mensajeError .textoMensaje').empty();
  for (let i = 0; i < errores.length; i++) {
      $('#mensajeError .textoMensaje').append($('<h4></h4>').text(errores[i]));
  }
  $('#mensajeError').hide();
  setTimeout(function() {
      $('#mensajeError').show();
  }, 250);
}

function modalEliminar(
  confirmar = function(){},
  cancelar = function(){},
  mensaje = "¿Seguro desea eliminar el MOVIMIENTO?"
)
{
  $('#modalEliminar #mensajeEliminar').empty();
  $('#modalEliminar #mensajeEliminar').append($('<strong>').text(mensaje));
  $('#modalEliminar .confirmar').off().click(function(){
    confirmar();
    setTimeout(function(){
      $('#modalEliminar').modal('hide');
    },250);
  });
  $('#modalEliminar .cancelar').off().click(function(){
    cancelar();
    setTimeout(function(){
      $('#modalEliminar').modal('hide');
    },250);
  });
  $('#modalEliminar').modal('show');
}

//Recibe un objeto como deftl.
function mensajeExitoMovimientos(args) {
  const deflt = {
    titulo : 'ÉXITO',
    mensajes : [],
    mostrarBotones : false,
    fijarMensaje : false
  };

  const noargs = isUndef(args);
  const titulo = noargs || isUndef(args.titulo)? deflt.titulo : args.titulo;
  const mensajes = noargs ||isUndef(args.mensajes)? deflt.mensajes : args.mensajes;
  const mostrarBotones = noargs || isUndef(args.mostrarBotones)? deflt.mostrarBotones : args.mostrarBotones;
  const fijarMensaje = noargs || isUndef(args.fijarMensaje)? deflt.fijarMensaje : args.fijarMensaje;

  $('#mensajeExito .textoMensaje').empty();
  $('#mensajeExito .textoMensaje').append($('<h3>').text(titulo));
  mensajes.forEach(function(m){
    $('#mensajeExito .textoMensaje').append($('<h4>').text(m));
  });
  $('#mensajeExito').toggleClass('mostrarBotones',mostrarBotones == true);//Conversion a boolean por si pasa cualquiera.
  $('#mensajeExito').toggleClass('fijarMensaje',fijarMensaje == true);
  $('#mensajeExito').hide();
  setTimeout(function() {
      $('#mensajeExito').show();
  }, 250);
}

function isUndef(x){
  return typeof x == 'undefined';
}

function limpiarNull(x,c = '-'){
  return x === null? c : x;
}