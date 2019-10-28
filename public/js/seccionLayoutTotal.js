var salida; //cantidad de veces que se apreta salir
var guardado;
var sectores;
var confirmacion = 0 ;


$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#layout').removeClass();
  $('#layout').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Layout Total');
  $('#opcLayoutTotal').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcLayoutTotal').addClass('opcionesSeleccionado');

  $('#fechaControlSinSistema').datetimepicker({
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

  $('#dtpBuscadorFecha').datetimepicker({
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

  $('#fechaGeneracion').datetimepicker({
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

  $('#dtpFecha').datetimepicker({
    todayBtn:  1,
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy HH:ii',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 0,
    ignoreReadonly: true,
    minuteStep: 5,
  });

  limpiarModal();
  $('.mensajeConfirmacion').hide();
  $('#iconoCarga').hide();
  $('#btn-buscar').trigger('click',[1,10,'layout_total.fecha','desc']);
});

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();

    window.open('layouts/generarPlanillaLayoutTotales/' + $(this).val(),'_blank');

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

//Opacidad del modal al minimizar
$('#btn-minimizarSinSistema').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarCargar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});
//Opacidad del modal al minimizar
$('#btn-minimizarValidar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| LAYOUT TOTAL');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//ABRIR MODAL DE NUEVO LAYOUT
$('#btn-nuevoLayoutTotal').click(function(e){
  e.preventDefault();
  limpiarModal();
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#modalLayoutTotal').modal('show');

  $.get("obtenerFechaActual", function(data){
    $('#fechaActual').val(data.fecha);
    $('#fechaDate').val(data.fechaDate);
  });
});

$('#btn-finalizarValidacion').click(function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  formData = {
    id_layout_total: $('#id_layout_total').val(),
    observacion_validacion: $('#observacion_validar').val(),
  }

  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host +'/layouts/validarLayoutTotal',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('#mensajeExito h3').text('ÉXITO DE VALIDACIÓN');
        $('#mensajeExito .cabeceraMensaje').removeClass('modificar');
        $('#mensajeExito p').text("Se ha validado correctamente el control de Layout Total.");
        $('#mensajeExito').show();
        //Una vez validido disparo evento buscar con fecha descendentemente
        $('#btn-buscar').trigger('click');
        $('#modalValidarControl').modal('hide');
      },
      error: function (data) {
        var response = JSON.parse(data.responseText);

        // if(response.maquinas.length){
        //   for (var i = 0; i < response.maquinas.length; i++) {
        //     typeof response.maquinas[i].no_existe !== ?
        //   }
        // }

        if(typeof response.observacion_validacion !== 'undefined'){
          mostrarErrorValidacion($('#observacion_validacion'),response.observacion_validacion[0] ,true );
        }



      }
  });
})

$("#btn-layoutSinSistema").click(function(e){
  e.preventDefault();
  limpiarModal();
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#modalLayoutSinSistema').modal('show');
})

$("#btn-backup").click(function(){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var formData = {
    fecha: $('#fechaLayoutSinSistema').val(),
    fecha_generacion: $('#fechaGeneracionSinSistema').val(),
    id_casino: $('#casinoSinSistema option:selected').val(),
  }

  console.log(formData);

  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host +'/layouts/usarLayoutTotalBackup',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);


        var pageNumber = $('#herramientasPaginacion').getCurrentPage();
        var tam = $('#tituloTabla').getPageSize();
        var columna = $('#tablaLayouts .activa').attr('value');
        var orden = $('#tablaLayouts .activa').attr('estado');
        $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
        $('frmLayoutSinSistema').trigger('reset');
        $('#modalLayoutSinSistema').modal('hide');

      },
      error: function (data) {
        var response = JSON.parse(data.responseText);

        if(typeof response.fecha !== 'undefined'){
              mostrarErrorValidacion($('#fecha_backup'),response.fecha[0],true);
        }
        if(typeof response.fecha_generacion !== 'undefined'){
              mostrarErrorValidacion($('#fecha_generacion_backup'),response.fecha_generacion[0],true);
        }
        if(typeof response.id_casino !== 'undefined'){
              mostrarErrorValidacion($('#casinoSinSistema'),response.id_casino[0],true);
        }
      }
  });

})

//GENERAR RELEVAMIENTO
$('#btn-generar').click(function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var formData = {
    id_casino: $('#casino').val(),
    turno: $('#turno').val(),
  }

  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host +'/layouts/crearLayoutTotal',
      data: formData,
      dataType: 'json',
      beforeSend: function(data){
        console.log('Empezó');
        $('#modalLayoutTotal').find('.modal-footer').children().hide();
        $('#modalLayoutTotal').find('.modal-body').children().hide();

        $('#iconoCarga').show();
      },
      success: function (data) {

        $('#modalLayoutTotal').modal('hide');
        $('#frmLayoutTotal').trigger('reset');
        var pageNumber = $('#herramientasPaginacion').getCurrentPage();
        var tam = $('#tituloTabla').getPageSize();
        var columna = $('#tablaLayouts .activa').attr('value');
        var orden = $('#tablaLayouts .activa').attr('estado');
        $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);

        var iframe;
        iframe = document.getElementById("download-container");
        if (iframe === null){
            iframe = document.createElement('iframe');
            iframe.id = "download-container";
            iframe.style.visibility = 'hidden';
            document.body.appendChild(iframe);
        }
        iframe.src = data.url_zip;


      },
      error: function (data) {

        $('#iconoCarga').hide();
        $('#modalLayoutTotal').find('.modal-footer').children().show();
        $('#modalLayoutTotal').find('.modal-body').children().show();

        var response = JSON.parse(data.responseText);

        if(typeof response.id_casino !== 'undefined'){
              mostrarErrorValidacion($('#casino'), response.id_casino[0] ,true);
        }
        if(typeof response.turno !== 'undefined'){
              mostrarErrorValidacion($('#turno'), "Valor de turno incorrecto.",true);
        }

      }
  });


});


function limpiarNull(s,defecto = ''){
  return s === null? defecto : s;
}

function cargarDivActivas(id_layout_total,done = function (x){return;}){
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  $.ajax({
    type: "GET",
    url: 'layouts/islasLayoutTotal/'+id_layout_total,
    dataType: 'json',
    success: function(data){
      const sectorEjemplo = $('#sectorEjemplo').clone().attr('id','').show();
      const filaEjemplo = $('#filaEjemploActivas').clone().attr('id','').show();
      const islaEjemplo = $('#islaEjemplo').clone().attr('id','').show();
      const activas_x_fila = filaEjemplo.attr('activas_por_fila');
      for(let z = 0;z<data.length;z++){
        const sector = data[z];
        let sector_html = sectorEjemplo.clone();
        sector_html.find('.nombre').text(sector.descripcion);
        sector_html.attr('data-id-sector',sector.id_sector);
        let tr = null;
        for(let i=0;i<sector.islas.length;i++){
          const isla = sector.islas[i];
          if(i%activas_x_fila == 0){
            tr = filaEjemplo.clone();
            sector_html.find('.cuerpoTabla').append(tr);
          }
          let isla_html = islaEjemplo.clone();
          isla_html.find('.textoIsla').text(isla.nro_isla);
          isla_html.find('.inputIsla').val(isla.maquinas_observadas);
          isla_html.attr('data-id-isla',isla.id_isla);
          tr.append(isla_html);
        }

        $('#modalCargaControlLayout .activas').append(sector_html);
      }
      done();
    },
    error: function(data){
      console.log(data);
    }
  });

 
}

function cargarDivInactivas(id_layout_total,done = function (x){return;}){
  $.get('http://' + window.location.host +'/layouts/obtenerLayoutTotal/' + id_layout_total, function(data){
      $('#cargaFechaActual').val(data.layout_total.fecha);
      $('#cargaFechaGeneracion').val(data.layout_total.fecha_generacion);
      $('#cargaCasino').val(data.casino.nombre);
      $('#cargaTurno').val(data.layout_total.turno);
      $('#fecha').val(data.layout_total.fecha_ejecucion);
      $('#fecha_ejecucion').val(data.layout_total.fecha_ejecucion);
      $('#observacion_carga').val(data.layout_total.observacion_fiscalizacion);

      if (data.usuario_cargador != null) {
          $('#fiscaCarga').val(data.usuario_cargador.nombre);
      }

      if (data.usuario_fiscalizador != null) {
        $('#inputFisca').val(data.usuario_fiscalizador.nombre)
                        .attr('data-fisca',data.usuario_fiscalizador.id_usuario)
                        .prop('readonly',true);
      }

      sectores = data.sectores;

      $('#inputFisca').generarDataList('usuarios/buscarUsuariosPorNombreYCasino/'+ data.casino.id_casino,'usuarios','id_usuario','nombre',2);
      $('#inputFisca').setearElementoSeleccionado(0,"");
      if (data.usuario_fiscalizador){
        $('#inputFisca').setearElementoSeleccionado(data.usuario_fiscalizador.id_usuario,data.usuario_fiscalizador.nombre);
      }
      $('#tablaCargaControlLayout tbody tr').remove();

      if('detalles' in data){
        for (var i = 0; i < data.detalles.length; i++) {
          agregarNivel(data.detalles[i] , $('#controlLayout') ,'carga');
        }
      }
      done();
  });
}

function cargarDivInactivasValidar(id_layout_total,done = function (x){return;}){
  $.get('http://' + window.location.host +'/layouts/obtenerTotalParaValidar/' + id_layout_total, function(data){

    $('#validarFechaActual').val(data.layout_total.fecha);
    $('#validarFechaGeneracion').val(data.layout_total.fecha_generacion);
    $('#validarCasino').val(data.casino);
    $('#validarTurno').val(data.layout_total.turno);

    $('#fecha').val(data.layout_total.fecha_ejecucion);
    $('#validarFechaEjecucion').val(data.layout_total.fecha_ejecucion);

    if(data.usuario_cargador != null) {
        $('#validarFiscaCarga').val(data.usuario_cargador.nombre);
    }

    if(data.usuario_fiscalizador != null) {
      $('#validarInputFisca').val(data.usuario_fiscalizador.nombre)
                      .attr('data-fisca',data.usuario_fiscalizador.id_usuario)
                      .prop('readonly',true);
    }

    $('#observacion_carga_validacion').val(data.layout_total.observacion_fiscalizacion);

    sectores = data.sectores;

    $('#tablaValidarControlLayout tbody tr').remove();

    for (var i = 0; i < data.detalles.length; i++) {
      agregarNivel(data.detalles[i] , $('#validarControlLayout') ,'validar');
    }
    done();
  });
}

function cargarDivActivasValidar(id_layout_total,done = function (x){return;}){
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  $.ajax({
    type: "GET",
    url: 'layouts/islasLayoutTotal/'+id_layout_total,
    dataType: 'json',
    success: function(data){
      const sectorEjemplo = $('#sectorEjemplo').clone().attr('id','').show();
      const filaEjemplo = $('#filaEjemploActivas').clone().attr('id','').show();
      const islaEjemplo = $('#islaEjemploValidar').clone().attr('id','').show();
      const activas_x_fila = filaEjemplo.attr('activas_por_fila');
      if(data.length == 0) $('#modalValidarControl .activas').append('<h4>No hay islas asociadas a este layout total</h4>');
      for(let z = 0;z<data.length;z++){
        const sector = data[z];
        let sector_html = sectorEjemplo.clone();
        sector_html.find('.nombre').text(sector.descripcion);
        sector_html.attr('data-id-sector',sector.id_sector);
        let total_observadas = 0;
        let total_sistema = 0;
        let tr = null;
        for(let i=0;i<sector.islas.length;i++){
          const isla = sector.islas[i];
          if(i%activas_x_fila == 0){
            tr = filaEjemplo.clone();
            sector_html.find('.cuerpoTabla').append(tr);
          }
          let isla_html = islaEjemplo.clone();

          isla_html.find('.textoIsla');
          isla_html.find('.textoIsla').text(isla.nro_isla);

          const observado = isla.maquinas_observadas? isla.maquinas_observadas: '0';
          const sistema = isla.cantidad_maquinas? isla.cantidad_maquinas: '0';
          isla_html.find('.observado').text(observado);
          isla_html.find('.sistema').text(sistema);
          isla_html.attr('data-id-isla',isla.id_isla);
          tr.append(isla_html);
        }
        let suma = islaEjemplo.clone();
        suma.addClass('total');
        suma.find('.textoIsla').text('TOTAL');
        suma.find('.observado').text(sector.total_observadas);
        suma.find('.sistema').text(sector.total_sistema);
        let width = suma.css('width');
        width = parseFloat(width.substr(0,width.length-1))*2;
        suma.css('width',width+'%');
        //Si no hay mas espacio o solo hay espacio para 1 lo pongo en otra fila
        //Porque ocupa 2 espacios.
        if(sector.islas.length%activas_x_fila == 0 || (sector.islas.length+1)%activas_x_fila == 0){
          tr = filaEjemplo.clone();
          sector_html.find('.cuerpoTabla').append(tr);
        }
        tr.append(suma);
        
        $('#modalValidarControl .activas').append(sector_html);
      }
      done();
    },
    error: function(data){
      console.log(data);
    }
  });
}

$(document).on('click','.carga',function(e){
  e.preventDefault();
  limpiarModal();
  //ocultar mensaje de salida
  salida = 0;
  guardado = true;
  $('#modalCargaControlLayout .mensajeSalida').hide();
  $('#mensajeExito').hide();

  var id_layout_total = $(this).val();
  $('#id_layout_total').val(id_layout_total);

  $('#btn-guardar').show();
  $('#btn-guardarTemp').show();
  let divActivas = $('#modalCargaControlLayout .activas').hide();
  let divInactivas = $('#modalCargaControlLayout .inactivas').hide();
  let tabActivas = $('#modalCargaControlLayout .tabActivas');
  let tabInactivas = $('#modalCargaControlLayout .tabInactivas');
  tabActivas.on('click',function(){
    divInactivas.hide();
    divActivas.show();
    tabActivas.addClass('subrayado');
    tabInactivas.removeClass('subrayado');
  });
  tabInactivas.on('click',function(){
    divActivas.hide();
    divInactivas.show();
    tabInactivas.addClass('subrayado');
    tabActivas.removeClass('subrayado');
  });

  //El que termina primero setea la bandera, el segundo muestra el modal.
  let done = false;
  const donef = function(){
    if(done) $('#modalCargaControlLayout').modal('show');
    else done = true;
  };
  cargarDivActivas(id_layout_total,donef);
  cargarDivInactivas(id_layout_total,donef);
});

function cargarDivDiferenciasValidar(){
  let tabla = $('#tablaDiferenciasEjemplo').clone().attr('id','').show();
  const filaEjemplo = tabla.find('.diferenciasFilaEjemplo').clone();
  tabla.find('.diferenciasFilaEjemplo').remove();

  
  sectores = [];
  //Busco en el div de activas
  $('#modalValidarControl .activas div.sector').each(function(){
    let f = filaEjemplo.clone();
    const t = $(this);
    const observado = parseInt(t.find('.total .observado').text());
    const sistema = parseInt(t.find('.total .sistema').text());
    f.find('.diferenciasSector').text(t.find('.nombre').text());
    f.find('.diferenciasActivas').text(observado);
    f.find('.diferenciasTotalSistema').text(sistema);
    const id_sector = t.attr('data-id-sector');
    f.attr('data-id-sector',id_sector);
    //console.log("AGREGANDO "+id_sector);
    sectores[id_sector] = [];
    sectores[id_sector]['activas'] = observado;
    sectores[id_sector]['sistema'] = sistema;
    sectores[id_sector]['inactivas'] = 0;
    sectores[id_sector]['fila'] = f;
    tabla.find('.cuerpoTablaDiferencias').append(f);
  });

  //Busco en el div de inactivas
  $('#modalValidarControl .inactivas .NivelLayout').each(function(){
    const t = $(this);
    const id_sector = t.find('select').val();
    //console.log("BUSCANDO INACTIVO "+id_sector);
    sectores[id_sector]['inactivas']++;
  });

  sectores.forEach(function(val,key){
    const fila = val['fila'];
    const activas = val['activas'];
    const inactivas = val['inactivas'];
    const total_relevado = activas + inactivas;
    const sistema = val['sistema'];
    const diff = Math.abs(sistema - total_relevado);

    fila.find('.diferenciasActivas').text(activas);
    fila.find('.diferenciasInactivas').text(inactivas);
    fila.find('.diferenciasTotal').text(total_relevado);
    fila.find('.diferenciasTotalSistema').text(sistema);
    fila.find('.diferenciasDiferencia').text(diff)
    .addClass(diff? 'incorrecto' : 'correcto');
  });

  $('#modalValidarControl .diferencias').append(tabla);
}
$(document).on('click','.validar',function(e){
  e.preventDefault();
  limpiarModal();
  //ocultar mensaje de salida
  salida = 0;
  guardado = true;
  $('#modalValidarControlLayout .mensajeSalida').hide();
  $('#mensajeExito').hide();

  var id_layout_total = $(this).val();
  $('#id_layout_total').val(id_layout_total);

  //SI ESTÁ GUARDADO NO MUESTRA EL BOTÓN PARA GUARDAR
  $('#btn-guardar').hide();
  $('#btn-guardarTemp').hide();

  let divActivas = $('#modalValidarControl .activas').hide();
  let divInactivas = $('#modalValidarControl .inactivas').hide();
  let divDiferencias = $('#modalValidarControl .diferencias').hide();
  let tabActivas = $('#modalValidarControl .tabActivas');
  let tabInactivas = $('#modalValidarControl .tabInactivas');
  let tabDiferencias = $('#modalValidarControl .tabDiferencias');
  tabActivas.on('click',function(){
    divInactivas.hide();
    divDiferencias.hide();
    divActivas.show();
    tabActivas.addClass('subrayado');
    tabInactivas.removeClass('subrayado');
    tabDiferencias.removeClass('subrayado');
  });
  tabInactivas.on('click',function(){
    divActivas.hide();
    divDiferencias.hide();
    divInactivas.show();
    tabInactivas.addClass('subrayado');
    tabActivas.removeClass('subrayado');
    tabDiferencias.removeClass('subrayado');
  });
  tabDiferencias.on('click',function(){
    divActivas.hide();
    divInactivas.hide();
    divDiferencias.show();
    tabDiferencias.addClass('subrayado');
    tabActivas.removeClass('subrayado');
    tabInactivas.removeClass('subrayado');
  });
  $('#btn-agregarNivel').hide();
  //El que termina primero setea la bandera, el segundo muestra el modal.
  let done = false;
  const donef = function(){
    if(done){
      cargarDivDiferenciasValidar();
      $('#modalValidarControl').modal('show');
    }
    else done = true;
  };
  cargarDivActivasValidar(id_layout_total,donef);
  cargarDivInactivasValidar(id_layout_total,donef);
});

$('.modal').on('hidden.bs.modal', function(){//se ejecuta cuando se oculta modal con clase .modal
  $('#tecnico').popover('hide');
  $('#fecha').popover('hide');
  $('#frmLayoutTotal').trigger('reset');
  $('#frmLayoutSinSistema').trigger('reset');
  $('#inputFisca').popover('hide');
  $('#inputFisca').prop('readonly' ,false);
  $('#inputFisca').val('');
  $('#fiscaCarga').val('');

  $('#validarFechaActual').val('');
  $('#validarFechaGeneracion').val('');
  $('#validarCasino').val('');
  $('#validarTurno').val('');
  $('#validarFiscaCarga').val('');
  $('#validarInputFisca').val('');
  $('#validarFechaEjecucion').val('');
  $('#validarControlLayout').empty();

  ocultarErrorValidacion($('#casino'));
  ocultarErrorValidacion($('#turno'));
  ocultarErrorValidacion($('#fecha'));

  $('#mensajeConfirmacion').hide();
  confirmacion = 0 ;
})

//SALIR DEL RELEVAMIENTO
$('#btn-salir').click(function(){
  //Si está guardado deja cerrar el modal
  if (guardado) $('#modalCargaControlLayout').modal('hide');
  //Si no está guardado
  else{
    if (salida == 0) {
      $('#modalCargaControlLayout .mensajeSalida').show();
      salida = 1;
    }else {
      $('#modalCargaControlLayout').modal('hide');
    }
  }
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('.selectCasinos').on('change',function(){
  var id_casino = $('option:selected' , this).attr('id');
  var selectCasino = $(this)
  $.get('http://' + window.location.host +"/casinos/obtenerTurno/" + id_casino, function(data){
      $('#turno').val(data.turno);
  });
});

function enviarLayout(url,succ=function(x){console.log(x);},err=function(x){console.log(x);}){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });
  let maquinas = [];
  $('#tablaCargaControlLayout tbody tr').each(function(){
    const maquina = {
      id_sector :  $(this).find('.sector').val(),
      nro_isla  : $(this).find('.nro_isla').val(),
      nro_admin : $(this).find('.nro_admin').val(),
      id_maquina : $(this).find('.nro_admin').obtenerElementoSeleccionado(),
      co : $(this).find('.co').val(),
      pb : $(this).find('.pb ').is(':checked')  == true ? 1 :  0,
    };
    maquinas.push(maquina);
  });

  let islas = [];
  $('#modalCargaControlLayout .isla').each(function(){
    const t = $(this);
    islas.push({
      id_isla: t.attr('data-id-isla'),
      maquinas_observadas: t.find('.inputIsla').val()
    });
  });

  const formData = {
    id_fiscalizador_toma :  $('#inputFisca').obtenerElementoSeleccionado(),
    id_layout_total:   $('#id_layout_total').val(),
    fecha_ejecucion: $('#fecha_ejecucion').val(),
    maquinas: maquinas,
    observacion_fiscalizacion: $('#observacion_carga').val(),
    confirmacion: confirmacion,
    islas: islas
  };
  
  console.log(formData);
  $.ajax({
      type: 'POST',
      url: url,
      data: formData,
      dataType: 'json',
      success: succ,
      error: err
  });
}

$('#btn-guardarTemp').click(function(e){
  e.preventDefault();
  enviarLayout('http://' + window.location.host +'/layouts/guardarLayoutTotal',
    function(x){
      console.log(x);
      guardado=true;
      $('#mensajeExito h3').text('ÉXITO DE CARGA');
      $('#mensajeExito .cabeceraMensaje').addClass('modificar');
      $('#mensajeExito p').text("Se ha guardado correctamente el control de Layout Total.");
      $('#mensajeExito').show();
      $('#btn-buscar').trigger('click');
    },
    function(x){
      console.log(x);
      mostrarError('Hubo un problema al guardar.');
    }
  );
});

function mostrarError(mensaje = '') {
  $('#mensajeError').hide();
  setTimeout(function() {
      $('#mensajeError').find('.textoMensaje')
          .empty()
          .append('<h2>ERROR</h2>')
          .append(mensaje);
      $('#mensajeError').show();
  }, 500);
}

//FINALIZAR CARGA RELEVAMIENTO
$('#btn-guardar').click(function(e){
  e.preventDefault();
  const success = function (resultados) {
    $('#mensajeExito h3').text('ÉXITO DE CARGA');
    $('#mensajeExito .cabeceraMensaje').addClass('modificar');
    $('#mensajeExito p').text("Se ha cargado correctamente el control de Layout Total.");
    $('#mensajeExito').show();
    $('#btn-buscar').trigger('click');
    $('#modalCargaControlLayout').modal('hide');
  };

  const error = function (data) {
    var response = JSON.parse(data.responseText);
    var bandera_error_no_aceptable= false;//bandera true ocurrio un error que no necesite ser corregido
    var bandera_error_aceptable = false;//bandera true si necesito pedir confirmacion
    if(typeof response.id_fiscalizador_toma !== 'undefined'){
          mostrarErrorValidacion($('#inputFisca'),response.id_fiscalizador_toma[0] ,true);
          bandera_error_no_aceptable = true;
    }

    if(typeof response.fecha_ejecucion !== 'undefined'){
          mostrarErrorValidacion($('#fecha'),response.fecha_ejecucion[0] ,true);
          bandera_error_no_aceptable = true;
    }
    var i = 0;

    $('#controlLayout tr').each(function(){
      if(typeof response['maquinas.'+ i +'.id_sector'] !== 'undefined'){
        filaError = i;
        mostrarErrorValidacion($(this).find('.sector') ,response['maquinas.'+ i +'.id_sector'][0] ,false);
        bandera_error_no_aceptable = true;
      }
      if(typeof response['maquinas.'+ i +'.nro_isla'] !== 'undefined'){
        filaError = i;
        mostrarErrorValidacion($(this).find('.nro_isla') , response['maquinas.'+ i +'.nro_isla'][0],false);
        bandera_error_no_aceptable = true;
      }
      if(typeof response['maquinas.'+ i +'.nro_admin'] !== 'undefined'){
        filaError = i;
        mostrarErrorValidacion($(this).find('.nro_admin'), response['maquinas.'+ i +'.nro_admin'][0],false);
        bandera_error_no_aceptable = true;
      }
      if(typeof response['maquinas.'+ i +'.no_existe'] !== 'undefined'){
        filaError = i;
        mostrarErrorValidacion($(this).find('.nro_isla') , response['maquinas.'+ i +'.no_existe'][0],false);
        bandera_error_aceptable = true;
      }
      i++;
    })
    if(bandera_error_aceptable && !bandera_error_no_aceptable){
      pedirValidacion();
    }else{
      $('.mensajeConfirmacion').hide();
    }
  };

  enviarLayout('http://' + window.location.host +'/layouts/cargarLayoutTotal',success,error);
});

function pedirValidacion(){
  confirmacion = 1;
  $('.mensajeConfirmacion').show();
}

function agregarMaquinaConDiferencia(renglon ,estado){
  if(estado == true){
    var id = renglon.attr('id')
    if($('#maquina'+id).length==0){
          $('#encabezado_diferencia').show();

          $('#maquinas_con_diferencia')
              .append($('<div>')
                  .addClass('row')
                  .css('margin-bottom','15px')
                  .attr('id','maquina' + renglon.attr('id'))
                  .append($('<div>')
                        .addClass('col-xs-1 col-xs-offset-1')
                        .css('padding-right','0px')
                        .append($('<span>')
                            .text(renglon.find('.col_nro_admin').text())
                        )
                  )
                  .append($('<div>')
                        .addClass('col-xs-1 nro_isla_dif')
                        .css('padding-right','0px')
                        .append($('<input>')
                            .attr('id','nro_isla')
                            .attr('type','text')
                            .attr('placeholder','Nro Isla')
                            .addClass('form-control')

                        )

                  )
                  .append($('<div>')
                        .addClass('col-xs-1 marca_dif')
                        .css('padding-right','0px')
                        .append($('<input>')
                            .attr('id','marca')
                            .attr('type','text')
                            .attr('placeholder','Fabricante')
                            .addClass('form-control')
                        )
                  )
                  .append($('<div>')
                        .addClass('col-xs-2 juego_dif')
                        .css('padding-right','0px')
                        .append($('<input>')
                            .attr('id','porc_visible')
                            .attr('type','text')
                            .attr('placeholder','Nombre Juego')
                            .addClass('form-control')
                        )
                  )
                    .append($('<div>')
                        .addClass('col-xs-1 nro_serie_dif')
                        .css('padding-right','0px')
                        .append($('<input>')
                            .attr('id','nro_serie')
                            .attr('type','text')
                            .attr('placeholder','Nro Serie')
                            .addClass('form-control')
                        )
                  )

                  .append($('<div>')
                          .addClass('col-xs-1 den_dif')
                          .css('padding-right','0px')
                          .append($('<input>')
                              .attr('type','text')
                              .attr('placeholder','Denominación')
                              .addClass('form-control')
                          )
                  )
                  .append($('<div>')
                          .addClass('col-xs-1 porc_dif')
                          .css('padding-right','0px')
                          .append($('<input>')
                              .attr('type','text')
                              .attr('placeholder','% Devolución')
                              .addClass('form-control')
                          )
                  )

                 )

    }
    actualizarDiferencia(renglon);
  }else {
    if(estaSinDiferencia(renglon)){
      $('#maquina' + renglon.attr('id')).remove();
    }else{
      actualizarDiferencia(renglon);
    }

    if( $('#maquinas_con_diferencia').children().length == 0 ) {
      $('#encabezado_diferencia').hide();
    }
  }

}

// Todo busqueda Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    var page_size = (page_size == null || isNaN(page_size) ) ? size : page_size;
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaLayouts .activa').attr('value'),orden: $('#tablaLayouts .activa').attr('estado')} ;
    if(sort_by == null){ // limpio las columnas
      $('#tablaLayouts th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

    //Fix error cuando librería saca los selectores
    if(isNaN($('#herramientasPaginacion').getPageSize())){
      var size = 10; // por defecto
    }else {
      var size = $('#herramientasPaginacion').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaLayouts .activa').attr('value'),orden: $('#tablaLayouts .activa').attr('estado')} ;
    if(sort_by == null){ // limpio las columnas
      $('#tablaLayouts th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

    var formData = {
      fecha: $('#buscadorFecha').val(),
      casino: $('#buscadorCasino').val(),
      turno: $('#buscadorTurno').val(),
      estadoRelevamiento: $('#buscadorEstado').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    }

    $.ajax({
        type: 'POST',
        url: 'http://' + window.location.host +'/layouts/buscarLayoutsTotales',
        data: formData,
        dataType: 'json',
        success: function (resultados) {
            console.log(resultados);

            $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
            $('#cuerpoTabla tr').remove();
            for (var i = 0; i < resultados.data.length; i++){

              var fila = generarFilaTabla(resultados.data[i]);
              $('#cuerpoTabla')
                  .append(fila);
            }

            mostrarIconosPorPermisos();


            $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

        },
        error: function (data) {
            console.log('Error:', data);
        }
      });
});

//Se usa para mostrar los iconos según los permisos del usuario
function mostrarIconosPorPermisos(){
    var formData = {
        permisos : ["ver_planilla_layout_total","carga_layout_total","validar_layout_total"],
    }

    $.ajax({
      type: 'GET',
      url: 'usuarios/usuarioTienePermisos',
      data: formData,
      dataType: 'json',
      success: function(data) {
        //Para los iconos que no hay permisos: OCULTARLOS!
        $('#cuerpoTabla tr').each(function(i,c){
          let fila = $(c);
          const estado = fila.find('.estado').text();
          setearEstado(fila,estado);
          if(!data.carga_layout_total){
            $('#cuerpoTabla .carga').hide();
          }
          if(!data.validar_layout_total){
            $('#cuerpoTabla .validar').hide();
            $('#cuerpoTabla .imprimir').hide();
          }else{
            $('#cuerpoTabla .imprimir').show();
          }
          fila.css('display','');//Lo muestro.
        });
      },
      error: function(error) {
          console.log(error);
      },
    });
}

$(document).on('click','#tablaLayouts thead tr th[value]',function(e){
  $('#tablaLayouts th').removeClass('activa');
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
  $('#tablaLayouts th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaLayouts .activa').attr('value');
  var orden = $('#tablaLayouts .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function setearEstado(fila,estado){
  let icono_estado = fila.find('.icono_estado');
  let icono_planilla = fila.find('.planilla');
  let icono_carga = fila.find('.carga');
  let icono_validacion = fila.find('.validar');
  let icono_imprimir = fila.find('.imprimir');
  //Limpio las clases de estado, seteandole lo mismo que la de ejemplo
  icono_estado.attr('class',$('#filaEjemplo').find('.icono_estado').attr('class'));
  fila.find('.estado').text(estado);
  //Siempre muestro el de la planilla (ademas porque la tabla se hace percha sin un icono)
  icono_planilla.show();
  icono_imprimir.hide();
  icono_carga.hide();
  icono_validacion.hide();
  switch (estado) {
    case 'Generado':
      icono_estado.addClass('faGenerado');
      icono_carga.show();
      break;
    case 'Cargando':
      icono_estado.addClass('faCargando');
      icono_carga.show();
      break;
    case 'Finalizado':
      icono_estado.addClass('faFinalizado');
      icono_validacion.show();
      break;
    case 'Visado':
      icono_estado.addClass('faValidado');
      break;
    default:
      break;
  }
}

//La genera PERO NO LA MUESTRA por que lo muestro despues cuando le pongo bien los iconos en base a los permisos...
function generarFilaTabla(layout_total){
      let fila = $('#filaEjemplo').clone();
      fila.attr('id',layout_total.id_layout_total);
      fila.find('.fecha').text(layout_total.fecha);
      fila.find('.casino').text(layout_total.casino);
      fila.find('.turno').text(layout_total.turno);
      fila.find('.planilla').val(layout_total.id_layout_total);
      fila.find('.carga').val(layout_total.id_layout_total);
      fila.find('.validar').val(layout_total.id_layout_total);
      fila.find('.imprimir').val(layout_total.id_layout_total);
      setearEstado(fila,layout_total.estado);
      return fila;
}

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
// $(document).on('click','.planilla',function(){
//     $('#alertaArchivo').hide();
//
//     window.open('layouts/generarPlanillaLayoutTotales/' + $(this).val(),'_blank');
//
// });
//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.imprimir',function(){
    $('#alertaArchivo').hide();
    window.open('layouts/generarPlanillaLayoutTotalesCargado/' + $(this).val(),'_blank');
});

//Agrega nivel de layout
$(document).on('click','.btn-agregarNivel',function(){
    $('#controlLayout').show();
    agregarNivel(null,$('#controlLayout'),'carga');
});

//borrar un nivel de layout
$(document).on('click','.borrarNivelLayout',function(){
    $(this).parent().parent().remove();
});

$(document).on('input' , '#modalCargaControlLayout input' ,function(){
  guardado = false;
});
  
$(document).on('input' , '#modalCargaControlLayout textarea' ,function(){
  guardado = false;
});

$(document).on('change' , '#modalCargaControlLayout select' ,function(){
  guardado = false;
});

function agregarNivel(nivel,tabla,funcion){
  console.log(nivel);
  const id_nivel_layout = (nivel != null) ? nivel.id_nivel_layout: "";
  const sector = (nivel != null) ? nivel.descripcion_sector: "";
  const nIsla = (nivel != null) ? nivel.nro_isla: null;
  const nAdmin = (nivel != null) ? nivel.nro_admin: null;
  const id_maquina = (nivel != null) ? nivel.id_maquina : 0; 
  const co = (nivel != null) ? nivel.co: null;
  const pBloq = (nivel != null) ? nivel.pb : null;  
  const editable = funcion == 'carga';

  let fila = $('<tr>')
  .addClass('NivelLayout')
  .attr('id_nivel_layout',id_nivel_layout);

  fila.append($('<td>'));
  fila.append(
    $('<td>').append(
      $('<select>').attr('type','text').addClass('form-control sector').attr('disabled' , !editable)
    )
  );
  fila.append($('<td>')
    .append($('<input>')
          .attr('type','text')
          .attr('placeholder','Isla')
          .addClass('form-control nro_isla')
          .val(nIsla)
          .attr('readonly' , !editable)
    )
  );
  fila.append($('<td>')
    .append($('<input>')
        .attr('type','text')
        .attr('placeholder','N° ADMIN')
        .addClass('form-control nro_admin')
        .val(nAdmin)
        .attr('readonly' , !editable)
    )
  );
  fila.append($('<td>')
    .append($('<input>')
        .attr('type','text')
        .attr('placeholder','C.0')
        .addClass('form-control co')
        .val(co)
        .attr('readonly' , !editable)
    )
  );
  fila.append($('<td>')
    .css('text-align' , 'center')
    .append($('<input>')
    .attr('type','checkbox')
    .addClass('pb')
    .prop('checked' , pBloq)
    .prop('disabled' , !editable)
    )
  );
  tabla.append(fila);

  if( funcion == 'carga' ){//agrego buscador y boton borrar (renglon)
    fila.find('.nro_admin').generarDataList("http://" + window.location.host + "/maquinas/obtenerMTMEnCasino/" + sectores[0].id_casino  ,'maquinas','id_maquina','nro_admin',1,false);
    fila.find('.nro_admin').setearElementoSeleccionado(id_maquina,nAdmin);
    $(fila).append(
      $('<td>')
        .append($('<button>')
          .addClass('borrarNivelLayout')
          .addClass('btn')
          .addClass('btn-danger')
          .addClass('borrarFila')
          .attr('type','button')
          .append($('<i>')
            .addClass('fa fa-fw fa-trash')
          )
      )
    );
  }else if(funcion == 'validar'){
  var boton_gestionar = $('<a>').addClass('btn btn-success pop gestion_maquina')
                                .attr('type' , 'button')
                                .attr('href' , 'http://' + window.location.host + '/maquinas/' + nivel.id_maquina )
                                .attr('target' , '_blank')
                                .attr("data-placement" , "top")
                                .attr('data-trigger','hover')
                                .attr('title','GESTIONAR MÁQUINA')
                                .attr('data-content','Ir a sección máquina')
                                .append($('<i>').addClass('fa fa-fw fa-wrench'));
  
  $('.NivelLayout:last()',tabla).append($('<td>').append(boton_gestionar));
  $('.gestion_maquina').popover({html:true});
}


cargarSectores(sectores ,sector , tabla);
}

function cargarSectores(sectores, seleccionado , tabla){
  var select = $('.NivelLayout:last()',tabla).find('.sector')

  for (var i = 0; i < sectores.length; i++) {
      select.append($('<option>')
          .val(sectores[i].id_sector)
          .text(sectores[i].descripcion)
    )
    if(seleccionado == sectores[i].descripcion){
      var id_sector = sectores[i].id_sector;
    }
  }
  $('.NivelLayout:last()',tabla).trigger('change');
  $('.NivelLayout:last()',tabla).find('.sector').val(id_sector);//carga validacion
}

function limpiarModal(){
    $('#iconoCarga').hide();
    $('#modalLayoutTotal').find('.modal-footer').children().show();
    $('#modalLayoutTotal').find('.modal-body').children().show()
    $('#controlLayout .NivelLayout').remove();
    $('#cargaFechaActual').val('');
    $('#cargaFechaGeneracion').val('');
    $('#cargaCasino').val('');
    $('#cargaTurno').val('');
    $('#fiscaCarga').val('');
    $('#inputFisca').val('');
    $('#fecha').val('');
    $('#observacion_carga').val('');
    $('#observacion_validar').val('');
    $('#observacion_carga_validacion').val('');
    $('.activas').empty();
    $('.diferencias').empty();
    $('#modalCargaControlLayout .subrayado').removeClass('subrayado');
    $('#modalValidarControl .subrayado').removeClass('subrayado');
}
