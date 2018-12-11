var salida; //cantidad de veces que se apreta salir

$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#layout').removeClass();
  $('#layout').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Layout Parcial');
  $('#opcLayoutParcial').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcLayoutParcial').addClass('opcionesSeleccionado');
  $('#iconoCarga').hide();

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
    startView: 2,
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
    format: 'dd MM yyyy - HH:ii',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 0,
    ignoreReadonly: true,
    minuteStep: 5,
  });

  //trigger buscar, carga de tabla, fecha desc
  $('#btn-buscar').trigger('click',[1,10,'layout_parcial.fecha' ,'desc']);

});

/*****   Modal de validacion  *****/
$(document).on('click','.validar',function(e){
    e.preventDefault();
    $('#mensajeExito').hide();
    var modal = $('#modalValidarControl'); // formato en html
    var id_layout_parcial = $(this).val();
    modal.find('#id_layout_parcial').val(id_layout_parcial);

    //Setear el id del layout en el modal
    var id_layout_parcial = $(this).val();
    $('#id_layout_parcial').val(id_layout_parcial);

    $.get( 'http://' + window.location.host + '/layouts/obtenerLayoutParcialValidar/' + id_layout_parcial, function(data) {
        console.log(data);
        $('#validarFechaActual').val(data.layout_parcial.fecha);
        $('#validarFechaEjecucion').val(data.layout_parcial.fecha_ejecucion);
        $('#validarCasino').val(data.casino);
        $('#validarSector').val(data.sector);

        if (data.usuario_cargador != null) {
            $('#validarFiscaCarga').val(data.usuario_cargador.nombre);
        }
        if (data.usuario_fiscalizador){
          $('#validarFiscaToma').val(data.usuario_fiscalizador.nombre);
        }

        $('#validarTecnico').val(data.layout_parcial.tecnico);

        //Limpiar la lista de máquinas del layout
        modal.find('#tablaMaquinasLayouts > tbody tr').remove();

        //Agregar las máquinas en la lista
        for (var i = 0; i < data.detalles.length; i++) {
          // agregarFilaMaquina(data.detalles[i], modal, "Carga");
          agregarFilaTablaMaquinasLayout(data.detalles[i],modal,"Validar");
        }

    });

    //Mostrar modal
    $('#modalValidarControl').modal('show');
});

/*****   Modal de carga | nueva manera   *****/
$(document).on('click','.carga',function(e){
  e.preventDefault();
  guardado = true;
  $('#mensajeExito').hide();

  var modal = $('#modalCargaControlLayout');
  var id_layout_parcial = $(this).val();
  modal.find('#id_layout_parcial').val(id_layout_parcial);


  $.get('http://' + window.location.host + '/layouts/obtenerLayoutParcial/' + id_layout_parcial, function(data){
      $('#cargaFechaActual').val(data.layout_parcial.fecha);
      $('#cargaFechaGeneracion').val(data.layout_parcial.fecha_generacion);
      $('#cargaCasino').val(data.casino);
      $('#cargaSector').val(data.sector);
      var subrelevamiento = data.layout_parcial.sub_control != null ? data.layout_parcial.sub_control : "";
      $('#cargaSubrelevamiento').val(subrelevamiento);
      $('#fecha').val(data.layout_parcial.fecha_ejecucion);
      $('#fecha_ejecucion').val(data.layout_parcial.fecha_ejecucion);

      if (data.usuario_cargador != null) {
          $('#fiscaCarga').val(data.usuario_cargador.nombre);
      }

      $('#inputFisca').generarDataList('usuarios/buscarUsuariosPorNombreYCasino/'+ data.id_casino,'usuarios','id_usuario','nombre',2);
      $('#inputFisca').setearElementoSeleccionado(0,"");
      if (data.usuario_fiscalizador){
        $('#inputFisca').setearElementoSeleccionado(data.usuario_fiscalizador.id_usuario,data.usuario_fiscalizador.nombre);
      }

      $('#tecnico').val(data.layout_parcial.tecnico);


      //Limpiar la lista de máquinas del layout
      modal.find('#tablaMaquinasLayouts > tbody tr').remove();
      // modal.find('#contenedorMaquinas div').remove();

      // $('#contenedorMaquinas div').remove();
      //Agregar las máquinas en la lista
      for (var i = 0; i < data.detalles.length; i++) {
        // agregarFilaMaquina(data.detalles[i], modal, "Carga");
        agregarFilaTablaMaquinasLayout(data.detalles[i],modal,"Carga");
      }

      // habilitarBotonFinalizar();
  });

  $('#modalCargaControlLayout').modal('show');
});

//Escuchar los checkboxs que cambian su estado
$('#contenedorMaquinas').on('change','.inputConCheck input:checkbox',function(){
    var input = $(this).parent().parent().find(':text');

    //Si se checkea, se deshabilita el input y se muestra contenido original
    if (this.checked) {
        input.attr('readonly',true);
        input.val(input.attr('data-original'));
    //Si se descheckea se habilita el input para introducir texto correcto
    }else {
        input.attr('readonly',false);
    }
});

//Armar la tabla agregando una fila por cada máquina para layout
function agregarFilaTablaMaquinasLayout(fila,modal,estado){
  var popGenerico = $('<a>').addClass('pop')
                    .attr("title","VALOR DEL SISTEMA")
                    .attr("data-placement" , "top")
                    .attr("data-trigger" , "hover");

  var inputGenerico = $('<input>').addClass('form-control inputLayout modificable').attr({'type':'text','readonly':true});

  //Datas originales
  var data_maquina = (fila.nro_admin.valor == null) ? '' : fila.nro_admin.valor;
  var data_isla = (fila.nro_isla.valor == null) ? '' : fila.nro_isla.valor;
  var data_fabricante = (fila.marca.valor == null) ? '' : fila.marca.valor;
  var data_juego = (fila.juego.valor == null) ? '' : fila.juego.valor;
  var data_serie = (fila.nro_serie.valor == null) ? '' : fila.nro_serie.valor;

  var maquina = popGenerico.clone().attr('data-content',fila.nro_admin.valor_antiguo)
                           .append(inputGenerico.clone().addClass('nro_admin').attr('data-original',data_maquina).val(fila.nro_admin.valor));
  var isla = popGenerico.clone().attr('data-content',fila.nro_isla.valor_antiguo)
                        .append(inputGenerico.clone().addClass('nro_isla').attr('data-original',data_isla).val(fila.nro_isla.valor));
  var fabricante = popGenerico.clone().attr('data-content',fila.marca.valor_antiguo)
                              .append(inputGenerico.clone().addClass('marca').attr('data-original',data_fabricante).val(fila.marca.valor));
  var juego = popGenerico.clone().attr('data-content',fila.juego.valor_antiguo)
                         .append(inputGenerico.clone().addClass('juego').attr('data-original',data_juego).val(fila.juego.valor));
  var nro_serie = popGenerico.clone().attr('data-content',fila.nro_serie.valor_antiguo)
                             .append(inputGenerico.clone().addClass('nro_serie').attr('data-original',data_serie).val(fila.nro_serie.valor));
 
 var juegosPack=  '<div align="left">'
                      +   '<input type="radio" class="seleccionJuego" value="juego 1">'

                      +               '<span style="position:relative;top:-3px;"> juego 1</span><br>'
                      +   '<input type="radio" class="seleccionJuego" value="juego 2">'
                                     
                      +               '<span style="position:relative;top:-3px;"> juego 2</span> <br><br>'
                      +   '<input type="radio" class="seleccionJuego" value="juego 3">'
                                     
                      +               '<span style="position:relative;top:-3px;"> juego 3</span> <br><br>'
                      +   '<input type="radio" class="seleccionJuego" value="juego 4">'
                                     
                      +               '<span style="position:relative;top:-3px;"> juego 4</span> <br><br>'
                      +   '<input type="radio" class="seleccionJuego" value="juego 5">'
                                     
                      +               '<span style="position:relative;top:-3px;"> juego 5</span> <br><br>'
                      +   '<input type="radio" class="seleccionJuego" value="juego 6">'
                                     
                      +               '<span style="position:relative;top:-3px;"> juego 6</span> <br><br>'
                      +   '<input type="radio" class="seleccionJuego" value="juego 7">'
                                     
                      +               '<span style="position:relative;top:-3px;"> juego 7</span> <br><br>'
                      +   '<input type="radio" class="seleccionJuego" value="juego 8">'
                                     
                      +               '<span style="position:relative;top:-3px;"> juego 8</span> <br><br>'
                      + '</div>';

  var botonMultiJuego = $('<button>')
                                      .attr('data-trigger','manual')
                                      .attr('data-toggle','popover')
                                      .attr('data-placement','left')
                                      .attr('data-html','true')
                                      .attr('title','JUEGOS')
                                      .attr('data-content',juegosPack)
                                      .attr('type','button')
                                      .addClass('btn btn-warning pop medida')
                                      
                                      .append($('<i>').addClass('fas fa-exchange-alt'));
 var bandera = false; //Si tuvo algun error

  //Para validar habilitar el POP
  if (fila.nro_admin.valor_antiguo != "") {maquina.addClass('modificado').find('input').css('border','2px solid red');bandera=true;}
  if (fila.nro_isla.valor_antiguo != "") {isla.addClass('modificado').find('input').css('border','2px solid red'); bandera=true;}
  if (fila.marca.valor_antiguo != "") {fabricante.addClass('modificado').find('input').css('border','2px solid red');bandera=true;}
  if (fila.juego.valor_antiguo != "") {juego.addClass('modificado').find('input').css('border','2px solid red');bandera=true;}
  if (fila.nro_serie.valor_antiguo != "") {nro_serie.addClass('modificado').find('input').css('border','2px solid red');bandera=true;}

  var no_toma = $('<input>').attr('type','checkbox').addClass('checkboxLayout check_notoma').prop('checked', fila.no_toma).prop('disabled',false);
  var denominacion = $('<input>').addClass('form-control den_sala').attr({'data-original':fila.denominacion,'type':'text'}).val(fila.denominacion);
  var porcentaje_dev = $('<input>').addClass('form-control porc_dev').attr({'data-original':fila.porcentaje_dev,'type':'text'}).val(fila.porcentaje_dev);

  var botonProgresivo = $('<button>').addClass('btn btn-default progresivo').attr('type','button')
                                     .attr('data-toggle','collapse').attr('data-target','#progresivo'+fila.id_maquina).hide()
                                     .append($('<i>').addClass('fa fa-fw fa-angle-down'));
  var boton_gestionar = $('<a>').addClass('btn btn-success pop gestion_maquina')
                                        .attr('type' , 'button')
                                       .attr('href' , 'http://' + window.location.host + '/maquinas/' + fila.id_maquina)
                                       .attr('target' , '_blank')
                                       .attr("data-placement" , "top")
                                       .attr('data-trigger','hover')
                                       .attr('title','GESTIONAR MÁQUINA')
                                       .attr('data-content','Ir a sección máquina')
                                       .append($('<i>').addClass('fa fa-fw fa-wrench'))
                                       .hide();

  var filaMaquinaLayout = $('<tr>').attr('id',fila.id_maquina);

  filaMaquinaLayout.append($('<td>').append(maquina));
  filaMaquinaLayout.append($('<td>').append(isla));
  filaMaquinaLayout.append($('<td>').append(fabricante));
  filaMaquinaLayout.append($('<td>').append(botonMultiJuego));
  filaMaquinaLayout.append($('<td>').append(juego));
  filaMaquinaLayout.append($('<td>').append(nro_serie));
  filaMaquinaLayout.append($('<td>').append(no_toma));
  filaMaquinaLayout.append($('<td>').append(denominacion));
  filaMaquinaLayout.append($('<td>').append(porcentaje_dev));
  filaMaquinaLayout.append($('<td>').append(botonProgresivo).append(boton_gestionar));

  modal.find('#tablaMaquinasLayouts > tbody').append(filaMaquinaLayout);
  //Todo lo de PROGRESIVO
  if (fila.progresivo != null){

      botonProgresivo.show();

      var rowProgresivo = modal.find('.rowProgresivo').first().clone().show();

      var data_nombre = (fila.progresivo.nombre_progresivo.valor == null) ? '' : fila.progresivo.nombre_progresivo.valor;
      var data_maximo = (fila.progresivo.maximo.valor == null) ? '' : fila.progresivo.maximo.valor;
      var data_recuperacion = (fila.progresivo.porc_recuperacion.valor == null) ? '' : fila.progresivo.porc_recuperacion.valor;

      rowProgresivo.find('.nombre_progresivo').addClass('modificable').attr('data-original',data_nombre).val(fila.progresivo.nombre_progresivo.valor);
      if (fila.progresivo.individual.valor)
           rowProgresivo.find('.tipo_progresivo').addClass('modificable').attr('data-original','LINKEADO').val('LINKEADO');
      else rowProgresivo.find('.tipo_progresivo').addClass('modificable').attr('data-original','INDIVIDUAL').val('INDIVIDUAL');
      rowProgresivo.find('.maximo_progresivo').addClass('modificable').attr('data-original',data_maximo).val(fila.progresivo.maximo.valor);
      rowProgresivo.find('.recuperacion_progresivo').addClass('modificable').attr('data-original',data_recuperacion).val(fila.progresivo.porc_recuperacion.valor);

      var filaProgresivo = $('<tr>').attr('id','progresivo' + fila.id_maquina).attr('data-progresivo',fila.progresivo.id_progresivo).addClass('collapse out')
                                    .append($('<td>').css('border-top','none').attr('colspan','9')
                                                     .append(rowProgresivo));

       //Todo lo de NIVELES
       if (fila.niveles != null) {
           var rowNivelProgresivo = modal.find('.rowNivelProgresivo').first().clone().show();
           var filaNivel = rowNivelProgresivo.find('.tablaNivelProgresivo .filaNivel').first();

          //Limpiar tabla de niveles
          rowNivelProgresivo.find('.tablaNivelProgresivo .filaNivel').remove();

           for (var i = 0; i < fila.niveles.length; i++) {
               var filaNivelNueva = filaNivel.clone().show();

               var data_nro = (fila.niveles[i].nro_nivel.valor == null) ? '' : fila.niveles[i].nro_nivel.valor;
               var data_nombre = (fila.niveles[i].nombre_nivel.valor == null) ? '' : fila.niveles[i].nombre_nivel.valor;
               var data_base = (fila.niveles[i].base.valor == null) ? '' : fila.niveles[i].base.valor;
               var data_oculto = (fila.niveles[i].porc_oculto.valor == null) ? '' : fila.niveles[i].porc_oculto.valor;
               var data_visible = (fila.niveles[i].porc_visible.valor == null) ? '' : fila.niveles[i].porc_visible.valor;

               filaNivelNueva.find('.nro_nivel').addClass('modificable').attr('data-original',data_nro).val(fila.niveles[i].nro_nivel.valor);
               filaNivelNueva.find('.nombre_nivel').addClass('modificable').attr('data-original',data_nombre).val(fila.niveles[i].nombre_nivel.valor);
               filaNivelNueva.find('.base_nivel').addClass('modificable').attr('data-original',data_base).val(fila.niveles[i].base.valor);
               filaNivelNueva.find('.porc_oculto').addClass('modificable').attr('data-original',data_oculto).val(fila.niveles[i].porc_oculto.valor);
               filaNivelNueva.find('.porc_visible').addClass('modificable').attr('data-original',data_visible).val(fila.niveles[i].porc_visible.valor);

               rowNivelProgresivo.find('.tablaNivelProgresivo tbody').append(filaNivelNueva);
           }

           filaProgresivo.children().append(rowNivelProgresivo);
       }

      modal.find('#tablaMaquinasLayouts > tbody').append(filaProgresivo);
  }

  //Esto hay que modificarlo SOLO para el que esté mal
  if (estado == "Validar") {
      // popGenerico.addClass('modificado');

      $('.inputLayout').removeClass('modificable');

      $('.check_notoma').attr('disabled',true);
      $('.den_sala').attr('readonly',true);
      $('.porc_dev').attr('readonly',true);
  }

  //muestra pop solo aqullos campos que fueron modificado
  $('.pop.modificado').popover({
    html:true
  });

  //muestro redireccion a maquina si existe algun cambio
  // if(bandera){
  //   filaMaquinaLayout.find('.gestion_maquina').popover({
  //     html:true,
  //   })
    filaMaquinaLayout.find('.gestion_maquina').show();
  //}
}

//Eventos cuando cierra el modal
$('.modal').on('hidden.bs.modal', function() {

    $('#tecnico').popover('hide');
    $('#fecha').popover('hide');
    $('#frmLayoutParcial').trigger('reset');
    $('#frmLayoutSinSistema').trigger('reset');
    $('#inputFisca').popover('hide');
    $('.popover').removeClass('popAlerta');

    if($('#casino').length > 1){
      $('.selectSector' , this).empty();
    }

    $('#modalLayoutParcial').find('.modal-footer').children().show();
    $('#modalLayoutParcial').find('.modal-body').children().show();
    $('#iconoCarga').hide();

    $(this).find('#contenedorMaquinas div').remove();

    $(this).find('#tablaMaquinasLayouts > tbody tr  ').remove();

    $('#id_layout_parcial').val(0);

})

//Devuelve un array de máquinas del layout con todos los datos en el modal
function llenarMaquinas() {
  var maquinas = [];

  //Todas las filas de máquinas
  $.each($('#modalCargaControlLayout #tablaMaquinasLayouts > tbody > tr:not(.collapse)'), function(indice, fila) {
      var filaProgresivo = $('#modalCargaControlLayout #tablaMaquinasLayouts').find('#progresivo' + $(this).attr('id'));

      var progresivo = "";

      //Si tiene progresivo
       if (filaProgresivo.length) {
          var nombre_progresivo = filaProgresivo.find('.nombre_progresivo');
          var tipo = filaProgresivo.find('.tipo_progresivo');
          var maximo = filaProgresivo.find('.maximo_progresivo');
          var porc_recuperacion = filaProgresivo.find('.recuperacion_progresivo');

          progresivo = {
              id_progresivo: filaProgresivo.attr('data-progresivo'),
              nombre_progresivo: {
                valor: nombre_progresivo.val(),
                correcto: nombre_progresivo.attr('data-original') == nombre_progresivo.val(),
              },
              individual:{
                valor: tipo.val(),
                correcto: tipo.attr('data-original') == tipo.val(),
              },
              maximo: {
                valor: maximo.val(),
                correcto: maximo.attr('data-original') == maximo.val(),
              },
              porc_recuperacion: {
                valor: porc_recuperacion.val(),
                correcto: porc_recuperacion.attr('data-original') == porc_recuperacion.val(),
              }
          } // JSON progresivo
       } //if filaProgresivo.length

      var niveles = [];

       //Recorrer todos los niveles
       $.each(filaProgresivo.find('.tablaNivelProgresivo > tbody > tr'), function(i) {
          var nro_nivel = $(this).find('.nro_nivel');
          var nombre_nivel = $(this).find('.nombre_nivel');
          var base = $(this).find('.base_nivel');
          var porc_visible = $(this).find('.porc_visible');
          var porc_oculto = $(this).find('.porc_oculto');

          var nivel = {
              nro_nivel: {
                  valor: nro_nivel.val(),
                  correcto: nro_nivel.attr('data-original') == nro_nivel.val(),
              },
              nombre_nivel: {
                  valor: nombre_nivel.val(),
                  correcto: nombre_nivel.attr('data-original') == nombre_nivel.val(),
              },
              base: {
                  valor: base.val(),
                  correcto: base.attr('data-original') == base.val(),
              },
              porc_visible: {
                  valor: porc_visible.val(),
                  correcto: porc_visible.attr('data-original') == porc_visible.val(),
              },
              porc_oculto: {
                  valor: porc_oculto.val(),
                  correcto: porc_oculto.attr('data-original') == porc_oculto.val(),
              }
          }

          niveles.push(nivel);
      }); //foreach niveles

      //Datos de la máquina
      var nro_admin = $(this).find('.nro_admin');
      var nro_isla = $(this).find('.nro_isla');
      var marca = $(this).find('.marca');
      var juego = $(this).find('.juego');
      var nro_serie = $(this).find('.nro_serie');
      var check_notoma = $(this).find('.check_notoma');
      var den_sala = $(this).find('.den_sala');
      var porc_dev = $(this).find('.porc_dev');

      var maquina = {
          id_maquina: $(this).attr('id'),
          nro_admin: {
            valor: nro_admin.val(),
            correcto: nro_admin.attr('data-original') == nro_admin.val(),
          },
          nro_isla: {
            valor: nro_isla.val(),
            correcto: nro_isla.attr('data-original') == nro_isla.val(),
          },
          marca: {
            valor: marca.val(),
            correcto: marca.attr('data-original') == marca.val(),
          },
          juego: {
            valor: juego.val(),
            correcto: juego.attr('data-original') == juego.val(),
          },
          nro_serie: {
            valor: nro_serie.val(),
            correcto: nro_serie.attr('data-original') == nro_serie.val(),
          },
          no_toma: check_notoma.is(':checked') ? 1 : 0,
          denominacion: den_sala.val(),
          porcentaje_dev: porc_dev.val(),

          //Datos de progresivo
          progresivo: progresivo,

          //Niveles
          niveles: niveles,

      } //var maquina

      maquinas.push(maquina);

  }); //foreach



  return maquinas;
}

function revisarCargaCompleta() {
  var cargaCompleta = true;

  $.each($('#modalCargaControlLayout #tablaMaquinasLayouts > tbody tr'), function(){
      //Si toma, los inputs deben estar cargados
      if (!$(this).find('.check_notoma').is(':checked')) {
        //Si los inputs están incompletos
        if ($(this).find('.den_sala').val() == '' || $(this).find('.porc_dev').val() == '' ) {
            cargaCompleta = false;
        }
      }
      //Para salir del each cuando encuentre campos incompletos
      return cargaCompleta;
  });


  return cargaCompleta;
}

//Finalizar la carga de layout parcial
$('#modalCargaControlLayout #btn-finalizar').click(function(){
  //Si la carga está completa MANDAR LOS DATOS

  if (revisarCargaCompleta()) {

      $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

      var maquinas = llenarMaquinas();
      var id_layout_parcial = $('#modalCargaControlLayout #id_layout_parcial').val();

      var formData = {
        id_layout_parcial: id_layout_parcial,
        fiscalizador_toma: $('#modalCargaControlLayout #inputFisca').obtenerElementoSeleccionado(),

        tecnico: $('#modalCargaControlLayout #tecnico').val(),
        fecha_ejecucion: $('#modalCargaControlLayout #fecha_ejecucion').val(),
        maquinas: maquinas,
        observacion: $('#modalCargaControlLayout #observacion_carga').val(),
      }

      $.ajax({
          type: "POST",
          url: 'http://' + window.location.host +'/layouts/cargarLayoutParcial',
          data: formData,
          dataType: 'json',
          success: function (data) {
              $('#mensajeExito h3').text('ÉXITO DE CARGA');
              $('#mensajeExito .cabeceraMensaje').addClass('modificar');
              $('#mensajeExito p').text("Se ha cargado correctamente el control de Layout Parcial.");

              $('#modalCargaControlLayout').modal('hide');

              $('#mensajeExito').show();

              var pageNumber = $('#herramientasPaginacion').getCurrentPage();
              var tam = $('#tituloTabla').getPageSize();
              var columna = $('#tablaLayouts .activa').attr('value');
              var orden = $('#tablaLayouts .activa').attr('estado');
              $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);

          },
          error: function (error) {
              var response = JSON.parse(error.responseText);

              if(typeof response.fiscalizador_toma !== 'undefined'){
                mostrarErrorValidacion($('#inputFisca'),response.fiscalizador_toma[0] ,true );
              }
              if(typeof response.tecnico !== 'undefined'){
                mostrarErrorValidacion($('#tecnico') ,response.tecnico[0],true);
              }
              if(typeof response.fecha_ejecucion !== 'undefined'){
                mostrarErrorValidacion($('#fecha') ,response.fecha_ejecucion[0]  ,true );
              }

              var i = 0;
              $('#tablaMaquinasLayouts tbody tr').each(function() {
                if(typeof response['maquinas.'+ i +'.marca.valor'] !== 'undefined'){
                  filaError = i;
                  mostrarErrorValidacion($(this).find('.marca') ,response['maquinas.'+ i +'.marca.valor'][0] ,false);
                }
                if(typeof response['maquinas.'+ i +'.nro_isla.valor'] !== 'undefined'){
                  filaError = i;
                  mostrarErrorValidacion($(this).find('.nro_isla') , response['maquinas.'+ i +'.nro_isla.valor'][0],false);
                }
                if(typeof response['maquinas.'+ i +'.juego.valor'] !== 'undefined'){
                  filaError = i;
                  mostrarErrorValidacion($(this).find('.juego'), response['maquinas.'+ i +'.juego.valor'][0],false);
                }
                if(typeof response['maquinas.'+ i +'.porcentaje_dev.valor'] !== 'undefined'){
                  filaError = i;
                  mostrarErrorValidacion($(this).find('.porc_dev'), response['maquinas.'+ i +'.porcentaje_dev.valor'][0],false);
                }
                if(typeof response['maquinas.'+ i +'.denominacion.valor'] !== 'undefined'){
                  filaError = i;
                  mostrarErrorValidacion($(this).find('.den_sala'), response['maquinas.'+ i +'.denominacion.valor'][0],false);
                }

                i++;
              })

          },
      }); // $.ajax
  }
  //Si no, mostrar mensajes!
  else {

        $.each($('#modalCargaControlLayout #tablaMaquinasLayouts tbody tr'), function(indice, fila) {
          //Si toma, los inputs deben estar cargados
          if (!$(this).find('.check_notoma').is(':checked')) {
              //Si los inputs están incompletos dar ALERTA
              if ($(this).find('.den_sala').val() == '') $(this).find('.den_sala').addClass('alerta');
              if ($(this).find('.porc_dev').val() == '') $(this).find('.porc_dev').addClass('alerta');
          }
        });

  }



});

//Check de no toma que habilita o deshabilita la denominacion y porcentaje_dev
$('#modalCargaControlLayout').on('change','.check_notoma', function(){
    var den_sala = $(this).parent().parent().find('.den_sala');
    var porc_dev = $(this).parent().parent().find('.porc_dev');

    den_sala.removeClass('alerta');
    porc_dev.removeClass('alerta');

    if (this.checked) {
      den_sala.val('').prop('disabled',true);
      porc_dev.val('').prop('disabled',true);
    }
    else {
      den_sala.prop('disabled',false);
      porc_dev.prop('disabled',false);
    }

});

//Sacar alerta de los inputs al darle foco
$('#modalCargaControlLayout').on('focus change','input.alerta',function(){
    $(this).removeClass('alerta');
});

//Validar el relevamiento de layout parcial
$('#btn-validarRelevamiento').click(function(){ //metodo de validar

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var formData = {
    observacion_validacion: $('#observacion_validacion').val(),
    id_layout_parcial: $('#id_layout_parcial').val(),
  }

  $.ajax({
    type: "POST",
    url: 'http://' + window.location.host +'/layouts/validarLayoutParcial',
    data: formData,
    dataType: 'json',
    success: function (data) {
        //Una vez validido disparo evento buscar con fecha descendentemente
        $('#modalValidarControl').modal('hide');

        $('#mensajeExito h3').text('ÉXITO DE VALIDACIÓN');
        $('#mensajeExito .cabeceraMensaje').removeClass('modificar');
        $('#mensajeExito p').text("Se ha validado correctamente el control de Layout Parcial.");
        $('#mensajeExito').show();

        var pageNumber = $('#herramientasPaginacion').getCurrentPage();
        var tam = $('#tituloTabla').getPageSize();
        var columna = $('#tablaLayouts .activa').attr('value');
        var orden = $('#tablaLayouts .activa').attr('estado');
        $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);    },

    error: function (error) {
      var response = JSON.parse(error.responseText);

      if(typeof response.observacion_validacion !== 'undefined'){
        mostrarErrorValidacion($('#observacion_validacion'),response.observacion_validacion[0] ,true );
      }
    },
  });

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

  $('.modal-title').text('| LAYOUT PARCIAL');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//ABRIR MODAL DE NUEVO RELEVAMIENTO
$('#btn-nuevoLayoutParcial').click(function(e){
  e.preventDefault();
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
  $('#modalLayoutParcial').modal('show');

  $.get("obtenerFechaActual", function(data){
    //Mayuscula pŕimer letra
    var fecha = data.fecha.charAt(0).toUpperCase() + data.fecha.slice(1);
    $('#fechaActual').val(fecha);
    $('#fechaDate').val(data.fechaDate);
  });
});

$("#btn-layoutSinSistema").click(function(e){
  e.preventDefault();
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
    id_sector: $('#sectorSinSistema').val(),
  }

  console.log(formData);

  $.ajax({
      type: "POST",
      url: 'http://' + window.location.host + '/layouts/usarLayoutBackup',
      data: formData,
      dataType: 'json',
      success: function (data) {
        $('#modalLayoutSinSistema').modal('hide');

        var pageNumber = $('#herramientasPaginacion').getCurrentPage();
        var tam = $('#tituloTabla').getPageSize();
        var columna = $('#tablaLayouts .activa').attr('value');
        var orden = $('#tablaLayouts .activa').attr('estado');
        $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);

      },
      error: function (data) {
        var response = JSON.parse(data.responseText);

        if(typeof response.fecha !== 'undefined'){
          mostrarErrorValidacion($('#fecha_backup'),response.fecha[0] ,true );
        }
        if(typeof response.fecha_generacion !== 'undefined'){
          mostrarErrorValidacion($('#fecha_generacion_backup'),response.fecha_generacion[0] ,true);
        }
        if(typeof response.id_sector !== 'undefined'){
          mostrarErrorValidacion($('#sectorSinSistema'),response.id_sector[0] ,true);
        }

      }
  });

});

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();
    window.open('layouts/generarPlanillaLayoutParcial/' + $(this).val(),'_blank');

});

//MUESTRA LA PLANILLA VACIA PARA RELEVAR
$(document).on('click','.imprimir',function(){
    $('#alertaArchivo').hide();
    window.open('layouts/generarPlanillaLayoutParcial/' + $(this).val(),'_blank');
});

//GENERAR RELEVAMIENTO
$('#btn-generar').click(function(e){
  /*
  //Si existe relevamiento para el sector seleccionado se muestra un modal de confirmación
  if ($('#modalLayoutParcial #existeLayoutParcial').val() == 1) {
      $('#modalLayoutParcial').modal('hide');
      $('#modalLayoutParcial #existeLayoutParcial').val(0);
      $('#modalConfirmacion').modal('show');
  }
  //Si no existe relevamiento para el sector se genera normalmente
  
  else {
    antes se evaluaba esto pero al ser lento el metodo ajax , no funcionba correctamente
    */
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    var formData = {
      id_sector: $('#sector').val(),
      cantidad_maquinas: $('#cantidad_maquinas').val(),
      cantidad_fiscalizadores: $('#cantidad_fiscalizadores').val(),
    }

    console.log(formData);

    $.ajax({
        type: "POST",
        url: 'http://' + window.location.host +'/layouts/crearLayoutParcial',
        data: formData,
        dataType: 'json',
        beforeSend: function(data){
          console.log('Empezó');
          $('#modalLayoutParcial').find('.modal-footer').children().hide();
          $('#modalLayoutParcial').find('.modal-body').children().hide();

          $('#iconoCarga').show();
        },
        success: function (data) {
          if(data.existeLayoutParcial==0){
              $('#frmLayoutParcial').trigger('reset');
              $('#modalLayoutParcial').modal('hide');
              $('#btn-buscar').trigger('click',[1,10,'layout_parcial.fecha' ,'desc']);

              var iframe;
              iframe = document.getElementById("download-container");

              if (iframe === null){
                  iframe = document.createElement('iframe');
                  iframe.id = "download-container";
                  iframe.style.visibility = 'hidden';
                  document.body.appendChild(iframe);
              }
              iframe.src = data.url_zip;
            }else{
              $('#modalLayoutParcial').modal('hide');
              $('#modalConfirmacion').modal('show');
          }
        },
        error: function (data) {
          console.log('entro al err');
          $('#modalLayoutParcial').find('.modal-footer').children().show();
          $('#modalLayoutParcial').find('.modal-body').children().show();
          $('#iconoCarga').hide();

          var response = JSON.parse(data.responseText);
          if(typeof response.id_sector !== 'undefined'){
                mostrarErrorValidacion( $('#sector'), response.id_sector[0] ,true);
          }
          if(typeof response.cantidad_maquinas !== 'undefined'){
                mostrarErrorValidacion( $('#cantidad_maquinas'), response.cantidad_maquinas[0] ,true);
          }
          if(typeof response.cantidad_fiscalizadores !== 'undefined'){
                mostrarErrorValidacion( $('#cantidad_fiscalizadores'), response.cantidad_fiscalizadores[0] ,true);
          }
        }

    });
  }
);

//GENERAR RELEVAMIENTO SOBRE SECTOR CON RELEVAMIENTO EXISTENTE
$('#btn-generarIgual').click(function(){
  $('#modalConfirmacion').modal('hide');
  $('#modalLayoutParcial').modal('show');
  //$('#btn-generar').trigger('click');
});

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

  $.get('http://' + window.location.host + "/sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
    if(selectCasino[0] == $("#casino")[0]){
      var selectSector = $('#sector');
      selectSector.empty();
    }else if (selectCasino[0] == $('#buscadorCasino')[0]){
      var selectSector = $('#buscadorSector');
      selectSector.empty();
      selectSector.append($('<option>')
          .val(0)
          .text('-Todos los sectores-')
        )
    }else {
      var selectSector = $('#sectorSinSistema');
      selectSector.empty();
    }

    for (var i = 0; i < data.sectores.length; i++) {
          selectSector.append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }
    //existeLayoutParcialGenerado();
  });

});

/*
esto se ejecutara cuando toquen el boton generar, antes se ejecutaba por cada cambio, puede llegar a afectar otra ventana, tener en cuenta
$('#modalLayoutParcial #sector').on('change',function(){
    //Acá se pregunta si para el sector ya existe una generación de relevamiento
    existeLayout();
});
*/

function existeLayoutParcial(){
  var id_sector = $('#modalLayoutParcial #sector option:selected').val();
  $.get('/layouts/existeLayoutParcial/' + id_sector, function(data){
      //Si ya existe se cambia el valor del botón GENERAR para que muestre o no un modal de confirmación
      console.log("esto me llega de la base para el sector " + id_sector);
      console.log(data);
      if (data == 1) {
          $('#modalLayoutParcial #existeLayoutParcial').val(1);
      }else {
          $('#modalLayoutParcial #existeLayoutParcial').val(0);
      }
  });
}

function existeLayoutParcialGenerado(){

  var id_sector = $('#modalLayoutParcial #sector option:selected').val();
  $.get('/layouts/existeLayoutParcialGenerado/' + id_sector, function(data){
      //Si ya existe se cambia el valor del botón GENERAR para que muestre o no un modal de confirmación
      console.log("esto me llega de la base para el sector " + id_sector);
      console.log(data);
      if (data == 1) {
          $('#modalLayoutParcial #existeLayoutParcial').val(1);
      }else {
        console.log("cambio el valor de la ventana")
          $('#modalLayoutParcial #existeLayoutParcial').val(0);
      }
      return data;
  }
  );
  
}

//Se usa Todo busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      });

      e.preventDefault();

      //Fix error cuando librería saca los selectores
      if(isNaN($('#herramientasPaginacion').getPageSize())){
        var size = 10; // por defecto
      }else {
        var size = $('#herramientasPaginacion').getPageSize();
      }

      var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
      // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
      var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
      var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaLayouts .activa').attr('value'),orden: $('#tablaLayouts .activa').attr('estado')} ;
      if(sort_by == null){ // limpio las columnas
        $('#tablaLayouts th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
      }

    var formData = {
      fecha: $('#buscadorFecha').val(),
      casino: $('#buscadorCasino').val(),
      sector: $('#buscadorSector').val(),
      estadoRelevamiento: $('#buscadorEstado').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    }

    $.ajax({
        type: 'POST',
        url: 'http://' + window.location.host +'/layouts/buscarLayoutsParciales',
        data: formData,
        dataType: 'json',
        success: function (resultados) {
            console.log(resultados);

            $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
            $('#cuerpoTabla tr').remove();

            //1ro - Se generan todas las filas con todos los iconos
            //2do - Se muestran los iconos por permiso
            //3ro - Se muestran los iconos de cada fila según el estado


            for (var i = 0; i < resultados.data.length; i++){
                var fila = generarFilaTabla(resultados.data[i]);
                $('#cuerpoTabla').append(fila);
            }

            mostrarIconosPorPermisos();

            $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

        },
        error: function (data) {
            console.log('Error:', data);
        }
      });
});

//Paginacion
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

//Paginacion
function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaLayouts .activa').attr('value');
  var orden = $('#tablaLayouts .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

//Se usa
function generarFilaTabla(layout_parcial){
    var subrelevamiento;
    layout_parcial.sub_control != null ? subrelevamiento = layout_parcial.sub_control : subrelevamiento = '';
    var fila = $(document.createElement('tr'));
    fila.attr('id', layout_parcial.id_layout_parcial)
        .append($('<td>').addClass('col-xs-2')
            .text((layout_parcial.fecha))
        )
        .append($('<td>').addClass('col-xs-2')
            .text(layout_parcial.casino)
        )
        .append($('<td>').addClass('col-xs-2')
            .text(layout_parcial.sector)
        )
        .append($('<td>').addClass('col-xs-1')
            .text(subrelevamiento)
        )
        .append($('<td>').addClass('col-xs-2')
            .append($('<i>').addClass('fas fa-fw fa-dot-circle'))
            .append($('<span>').text(layout_parcial.estado))
        )
        .append($('<td>').addClass('col-xs-3')
            .append($('<button>').addClass('btn btn-info planilla').attr('type','button').val(layout_parcial.id_layout_parcial)
                .append($('<i>').addClass('far').addClass('fa-fw').addClass('fa-file-alt'))
            )
            .append($('<span>').text(' '))
            .append($('<button>').addClass('btn btn-warning carga').attr('type','button').val(layout_parcial.id_layout_parcial)
                .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-upload'))
            )
            .append($('<span>').text(' '))
            .append($('<button>').addClass('btn btn-success validar').attr('type','button').val(layout_parcial.id_layout_parcial)
                .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-check'))
            )
            .append($('<span>').text(' '))
            .append($('<button>').addClass('btn btn-info imprimir').attr('type','button').val(layout_parcial.id_layout_parcial)
                .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-print'))
            )
        )

        var icono_planilla = fila.find('.planilla');
        var icono_carga = fila.find('.carga');
        var icono_validacion = fila.find('.validar');
        var icono_impirmir = fila.find('.imprimir');

      //Qué ESTADO e ICONOS mostrar
      switch (layout_parcial.estado) {
        case 'Generado':
            fila.find('.fa-dot-circle').addClass('faGenerado');

            icono_planilla.show();
            icono_carga.show();
            icono_validacion.hide();
            icono_impirmir.hide();
            break;
        case 'Cargando':
            fila.find('.fa-dot-circle').addClass('faCargando');
            break;
        case 'Finalizado':
            fila.find('.fa-dot-circle').addClass('faFinalizado');

            icono_validacion.show();
            icono_impirmir.show();
            icono_carga.hide();
            icono_planilla.hide();
            break;
        case 'Validado':
            fila.find('.fa-dot-circle').addClass('faValidado');

            icono_impirmir.show();
            icono_validacion.hide();
            icono_carga.hide();
            icono_planilla.hide();
            break;
      }

      return fila;
}

//Se usa para mostrar los iconos según los permisos del usuario
function mostrarIconosPorPermisos(){
    var formData = {
        permisos : ["ver_planilla_layout_parcial","carga_layout_parcial","validar_layout_parcial"],
    }

    $.ajax({
      type: 'GET',
      url: 'usuarios/usuarioTienePermisos',
      data: formData,
      dataType: 'json',
      success: function(data) {

        //Para los iconos que no hay permisos: OCULTARLOS!
        if (!data.ver_planilla_layout_parcial) $('.planilla').hide();
        if (!data.carga_layout_parcial) $('.carga').hide();
        if (!data.validar_layout_parcial) $('.validar').hide();

        // return data;
      },
      error: function(error) {
          console.log(error);
      },
    });
}

$(document).on('keypress','.inputLayout', function(e){
    if(e.which == 13) {
      $(this).blur();
    }
});

//Prueba doble click
$(document).on('dblclick','.inputLayout.modificable', function(e){
    if ($(this).prop('readonly')) {
        $(this).prop('readonly',false);
        $(this).css('border','2px solid orange');
    }
    else {
        $(this).blur();
        $(this).prop('readonly',true);
        $(this).css('border','1px solid #ccc');
        $(this).val($(this).attr('data-original')); //Si se arrepiente de modificación se setea el valor original
    }
    clearSelection();
});

function clearSelection(){
    if(document.selection && document.selection.empty) {
        document.selection.empty();
    } else if(window.getSelection) {
        var sel = window.getSelection();
        sel.removeAllRanges();
    }
}

$(document).on('click','.pop',function(e){
  e.preventDefault();
 //estos era util para obtener info
  var fila = $(this).parent().parent();
  $('.pop').not(this).popover('hide');
  $(this).popover('show');
});


// cambia el el nombre del juego dentro de los valores posibles del paquete
$(document).on('click','.seleccionJuego',function(e){
  e.preventDefault();
 //estos era util para obtener info
  var fila = $(this).parent().parent().parent().parent().parent();
  var nombre_juego=$(this).val();
  $('.pop').not(this).popover('hide');
  $(this).popover('show');
  fila.children().find('.juego').val(nombre_juego)
  
});