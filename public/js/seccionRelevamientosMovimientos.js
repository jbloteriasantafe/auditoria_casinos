$(document).ready(function(){
  var t= $('#tablaRelevamientosMovimientos tbody > tr .fechaRelMov');

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

    $('.tituloSeccionPantalla').text('Relevamientos');
    $('#opcRelevamientosMovimientos').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
    $('#opcRelevamientosMovimientos').addClass('opcionesSeleccionado');

      $('#relFecha').datetimepicker({
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
        container:$('#modalCargarRelMov'),
      });

      $('#dtpFechaRM').datetimepicker({
        language:  'es',
        todayBtn:  1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'MM yyyy',
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,
        container:$('main section'),

      });


$('#B_TipoMovimientoRel').val("");
$('#busqueda_maquina').val("");
  });



  $('#fechaRel').on('change', function (e) {
    $(this).trigger('focusin');
  })

  $('#fechaRelMov').on('change', function (e) {
    $(this).trigger('focusin');
  })



//filtros
// function buscarFiscalizaciones() {
//
//   $.ajaxSetup({headers:{ 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
//
//   var page_size = (page_size != null) ? page_size : 10;
//   var page_number = (pagina != null) ? pagina : 1;
//   var sort_by = (columna != null) ? {columna,orden} : null;
//   if(sort_by == null) { // limpio las columnas
//     $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
//   }
//   e.preventDefault();
//
//   var formData = {
//     //Casino
//     page: page_number,
//     sort_by: sort_by,
//     page_size: page_size,
//   }
//
//   $.ajax({
//       type: 'POST',
//       url: 'movimientos/obtenerFiscalizaciones',
//       data: formData,
//       dataType: 'json',
//       success: function (resultados) {
//
//           $('#tituloTabla').generarTitulo(page_number,page_size,resultados.total,clickIndice);
//           $('.btn-imprimirRelMov').prop('disabled', true);
//           $('#indicesPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
//       },
//       error: function (data) {
//           console.log('Error:', data);
//       }
//     });
// }

//SELECCIONA EL BOTÓN QUE ABRE EL MODAL DE CARGA
$('.btn-generarRelMov').click(function(e){
  var id_fiscalizacion= $(this).val();

  window.open('movimientos/generarPlanillasRelevamientoMovimiento/' + id_fiscalizacion,'_blank');

});

$('.btn-imprimirRelMov').click(function(e){
  var id_fiscalizacion= $(this).val();

  window.open('movimientos/generarPlanillasRelevamientoMovimiento/' + id_fiscalizacion,'_blank');

});

$('.btn-cargarRelMov').click(function(e){

  var id_fiscalizacion = $(this).val();

  $('#tablaCargarRelevamiento tbody tr').remove();
  $('#tablaMaquinasFiscalizacion tbody tr').remove();
  $('.modal-title').text('CARGAR RELEVAMIENTOS');
  $('.modal-header').attr('style','background: #4FC3F7');
  $('#form1').trigger("reset");
  $('#fechaRel').val('');
  $('#juegoRel option').remove();
  $('#guardarRel').prop('disabled', true);
  $('#modalCargarRelMov #detalless').hide();
  $('#mensajeExitoCarga').hide();
  $('#mensajeErrorCarga').hide();
  $('#modalCargarRelMov').modal('show');

  $.get('movimientos/obtenerRelevamientosFiscalizacion/' + id_fiscalizacion, function(data)
  {
    $('#modalCargarRelMov').find('#casinoId').val(data.casino);
    $('#fiscaCarga').attr('data-id',data.cargador.id_usuario);
    $('#fiscaCarga').val(data.cargador.nombre);
    var fila1=$('<tr>');

      for (var i = 0; i < data.relevamientos.length; i++)
      {
          var fila = fila1.clone();
          fila.append($('<td>')
              .addClass('col-xs-5')
              .text(data.relevamientos[i].nro_admin))
          fila.append($('<td>')
                  .addClass('col-xs-3')
                  .append($('<button>')
                      .append($('<i>')
                          .addClass('fa').addClass('fa-fw').addClass('fa-upload')
                      ).attr('type','button')
                      .addClass('btn btn-info cargarMaq')
                      .attr('value', data.relevamientos[i].id_maquina)
                      .attr('data-fisc', id_fiscalizacion))
                    )
              fila.append($('<td>')
                  .addClass('col-xs-3')
                  .append($('<i>').addClass('fa fa-fw fa-check faFinalizado').addClass('listo')
                        .attr('value', data.relevamientos[i].id_maquina)))

          if(data.relevamientos[i].id_estado_relevamiento == 3)
          {
            fila.find('.listo').show();
         }else{
           fila.find('.listo').hide();

         }

        $('#tablaMaquinasFiscalizacion tbody').append(fila);
      }

      $('#fiscaToma').generarDataList("usuarios/buscarUsuariosPorNombreYCasino/" + data.casino,'usuarios' ,'id_usuario','nombre',1,false);
      $('#fiscaToma').setearElementoSeleccionado(0,"");
      if (data.usuario_fiscalizador)
      {
        $('#fiscaToma').setearElementoSeleccionado(data.usuario_fiscalizador.id_usuario,data.usuario_fiscalizador.nombre);
      }
  })

});

//SELECCIONA UNA MÁQUINA PARA VER SU DETALLE
$(document).on('click','.cargarMaq',function(){

  $('#mensajeExitoCarga').hide();
  ocultarErrorValidacion($('#juegoRel'));
  ocultarErrorValidacion($('#apuesta'));
  ocultarErrorValidacion($('#cant_lineas'));
  ocultarErrorValidacion($('#creditos'));
  ocultarErrorValidacion($('#denominacion'));
  ocultarErrorValidacion($('#devolucion'));


  var id_maquina= $(this).val();
  var id_fiscalizacion= $(this).attr('data-fisc');
  $('#modalCargarRelMov').find('#id_fiscalizac').val(id_fiscalizacion);
  $('#modalCargarRelMov').find('#maquina').val(id_maquina);

  $.get('movimientos/obtenerMTMFiscalizacion/' + id_maquina + '/' + id_fiscalizacion, function(data)
    {
      $('#modalCargarRelMov #detalless').show();
      cargarRelMov(data);
    }
  )
});

//COMPLETA INPUTS
function cargarRelMov(data){

    $('#mensajeExitoCarga').hide();
    $('#form1').trigger("reset");
    $('#juegoRel option').remove();
    $('#tablaCargarRelevamiento tbody tr').remove();
    var fisc = $('#modalCargarRelMov').find('#id_fiscalizac').val();
    var maq = $('#modalCargarRelMov').find('#maquina').val();

    if(data.fecha != null )
    {
      $('#modalCargarRelMov').find('#fechaRel').val(data.fecha);
    }else{
      var fecha= $('#fechaRel').val();
      $('#modalCargarRelMov').find('#fechaRel').val(fecha);
    }

    if(data.fiscalizador != null){
      $('#fiscaToma').val(data.fiscalizador.nombre);
      // $('#modalCargarRelMov').find('#fiscalizador').val(data.fiscalizador.id_usuario);
    }else {
      $('#fiscaToma').val();
      // $('#modalCargarRelMov').find('#fiscalizador').val();
    }

    $('#nro_adminMov').val(data.maquina.nro_admin);
    $('#nro_islaMov').val(data.maquina.nro_isla);
    $('#nro_serieMov').val(data.maquina.nro_serie);
    $('#marcaMov').val(data.maquina.marca);
    $('#modeloMov').val(data.maquina.modelo);


    var cont = "cont";
    var vcont="vcont"
    var fila2 = $('<tr>');


    for (var i = 1; i < 7; i++)
    {
            var fila = fila2.clone();
            var p = data.maquina[cont + i];

                  if( data.toma != null){
                      var v = data.toma[vcont + i];}

                            if (v!=null && p!= null) {

                              fila.append($('<td>')
                                  .addClass('col-xs-6')
                                  .text(p))
                                  .attr('data-contador',p)
                                  .append($('<td>')
                                  .addClass('col-xs-3')
                                  .append($('<input>')
                                  .addClass('valorModif form-control')
                                  .val(v).text(v)));

                                $('#tablaCargarRelevamiento tbody').append(fila);

                              }

                              if (v==null && p!= null) {

                                fila.append($('<td>')
                                    .addClass('col-xs-6')
                                    .text(p))
                                    .attr('data-contador',p)
                                    .append($('<td>')
                                    .addClass('col-xs-3')
                                    .append($('<input>')
                                    .addClass('valorModif form-control')
                                    .val("").text(' ')));



                                  $('#tablaCargarRelevamiento tbody').append(fila);

                                }

                              }

                        if(data.nombre_juego==null){
                            $('#modalCargarRelMov #juegoRel')
                              .append($('<option>')
                              .val(0)
                              .text('Seleccione'))


                              for (var i = 0; i < data.juegos.length; i++) {
                                $('#modalCargarRelMov #juegoRel')
                                    .append($('<option>')
                                    .val(data.juegos[i].id_juego)
                                    .text(data.juegos[i].nombre_juego)
                                  )};

                                }else{

                                        $('#modalCargarRelMov #juegoRel')
                                            .append($('<option>')
                                            .val(data.juegos[0].id_juego)
                                              .text(data.nombre_juego))
                                      }

        if(data.toma==null){
          var id_juego = $('#modalCargarRelMov #juegoRel option:selected').val();

        };

        if(data.toma != null){
          var id_juego = $('#modalCargarRelMov #juegoRel option:selected').val(data.toma.juego);

            $('#apuesta').val(data.toma.apuesta_max);
            $('#cant_lineas').val(data.toma.cant_lineas);
            $('#devolucion').val(data.toma.porcentaje_devolucion);
            $('#denominacion').val(data.toma.denominacion);
            $('#creditos').val(data.toma.cant_creditos);
            $('#observacionesToma').val(data.toma.observaciones);}

            $('#guardarRel').prop('disabled', false);


        };

//BOTÓN GUARDAR
$(document).on('click','#guardarRel',function(){

  $('#mensajeErrorCarga').hide();
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var y= $('#fiscaToma').obtenerElementoSeleccionado();
    $('#modalCargarRelMov').find('#fiscalizador').val(y);

  var contadores=[];
  var tabla= $('#tablaCargarRelevamiento tbody > tr');
  var contadores= [];
  var f=$('#modalCargarRelMov').find('#id_fiscalizac').val();
  var mov= $('#modalCargarRelMov').find('#id_log_movimiento').val();
  var cargador=$('#modalCargarRelMov').find('#fiscaCarga').attr('data-id');
  var id_fiscalizador= $('#modalCargarRelMov').find('#fiscalizador').val();
  var fecha=$('#modalCargarRelMov').find('#fecha_ejecucionRel').val();
  var maq= $('#modalCargarRelMov').find('#maquina').val();
  var juego= $('#modalCargarRelMov').find('#juegoRel').val();
  var apuesta= $('#modalCargarRelMov').find('#apuesta').val();
  var lineas= $('#modalCargarRelMov').find('#cant_lineas').val();
  var dev= $('#modalCargarRelMov').find('#devolucion').val();
  var den= $('#modalCargarRelMov').find('#denominacion').val();
  var cred= $('#modalCargarRelMov').find('#creditos').val();
  var obs= $('#modalCargarRelMov').find('#observacionesToma').val();
  var tabla2 = $('#tablaMaquinasFiscalizacion tbody > tr');
  var mac=$('#modalCargarRelMov').find('#macCargar').val();


  $.each(tabla, function(index, value){

    var cont={
      nombre: $(this).attr('data-contador'),
      valor: $(this).find('.valorModif').val()
    }
    contadores.push(cont);
  });

    var formData={
      id_fiscalizacion_movimiento: f,
      id_cargador: cargador,
      id_fiscalizador: id_fiscalizador,
      estado: 2,
      id_maquina: maq,
      contadores: contadores,
      juego: juego,
      apuesta_max: apuesta,
      cant_lineas: lineas,
      porcentaje_devolucion: dev,
      denominacion: den,
      cant_creditos: cred,
      fecha_sala: fecha,
      observaciones: obs,
      mac:mac
    }


  $.ajax({
      type: 'POST',
      url: 'movimientos/cargarTomaRelevamiento',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log('BIEN');
        console.log(data);
          $('#modalCargarRelMov #detalless').hide();
          $('#modalCargarRelMov #fechaRel').val(' ');
          $('#modalCargarRelMov #fiscaToma').val(' ');
          $('#mensajeErrorCarga').hide();
          //se agrega una tilde en azul a la máq cargada, dentro del mismo modal
          $('#tablaMaquinasFiscalizacion').find('.listo[value="'+maq+'"]').show();
          $('#mensajeExitoCarga').show();
          $('#modalCargarRelMov .cargarMaq').prop('disabled', false);
          $('#guardarRel').prop('disabled', true);

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
             || typeof response.fecha_sala !== 'undefined')
        {
          $("#modalCargarRelMov").animate({ scrollTop: 0 }, "slow");
        }

        if(typeof response.apuesta_max !== 'undefined'){
          mostrarErrorValidacion($('#apuesta'),response.apuesta_max[0]);
        }
        if(typeof response.cant_lineas !== 'undefined'){
          mostrarErrorValidacion($('#cant_lineas'),response.cant_lineas[0]);
        }
        if(typeof response.cant_creditos !== 'undefined'){
          mostrarErrorValidacion($('#creditos'),response.cant_creditos[0]);
          // $('#fecha').popover('show');
          // $('.popover').addClass('popAlerta');
        }
        if(typeof response.porcentaje_devolucion !== 'undefined'){
          mostrarErrorValidacion($('#devolucion'),response.porcentaje_devolucion[0]);
        }
        if(typeof response.denominacion !== 'undefined'){
            mostrarErrorValidacion($('#denominacion'),response.denominacion[0]);
        }
        if(typeof response.juego !== 'undefined'){
          mostrarErrorValidacion($('#juegoRel'),response.juego[0]);
        }
        if(typeof response.id_fiscalizador !== 'undefined'){
        mostrarErrorValidacion($('#fiscaToma'),response.id_fiscalizador[0]);
        }
        if(typeof response.fecha_sala !== 'undefined'){
        mostrarErrorValidacion($('#fechaRel'),response.fecha_sala[0]);
        }
        if(typeof response.contadores !== 'undefined'){
          $('#mensajeErrorCarga').show();

        }


        var i = 0;
        var filaError = 0;
        $('#tablaCargarRelevamiento tbody tr').each(function(){

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

$('#btn-buscarRelMov').click(function(e){

    $.ajaxSetup({
      headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
    });
    e.preventDefault();

    var formData = {
      id_tipo_movimiento: $('#B_TipoMovimientoRel').val(),
      fecha: $('#fechaRelMov').val(),
      nro_admin: $('#busqueda_maquina').val()
    }

    $.ajax({
      type: 'POST',
      url: 'relevamientos_movimientos/buscarFiscalizaciones',
      data: formData,
      dataType: 'json',

      success: function (data) {
        console.log('success rel:', data);

        $('#tablaRelevamientosMovimientos #cuerpoTablaRel tr').remove();

          for (var i = 0; i < data.fiscalizaciones.length; i++) {

              var filaRelMov = generarFilaTabla(data.fiscalizaciones[i]);
              $('#cuerpoTablaRel').append(filaRelMov);
          }

      },
      error: function (data) {
        console.log('Error:', data);
      }
    });
});

//Se generan filas en la tabla principal con las eventualidades encontradas
function generarFilaTabla(rel){

console.log('generar',rel);
  var fila = $(document.createElement('tr'));
  var fecha;
  var tipo_mov;
  var casino;
  var estado;

  tipo_mov=rel.descripcion;
  fecha=rel.fecha_envio_fiscalizar;
  casino=rel.nombre;
  estado=rel.id_estado_relevamiento;

console.log('re',rel);

  fila.attr('id', rel.id_fiscalizacion_movimiento)
      .append($('<td>')
      .addClass('col-xs-2')
      .text(fecha)
      )
      .append($('<td>')
          .addClass('col-xs-3')
          .text(rel.identificacion_nota)
          )
      .append($('<td>')
      .addClass('col-xs-3')
      .text(tipo_mov)
      )
      .append($('<td>')
      .addClass('col-xs-2')
      .text(casino)
      )
        .append($('<td>')
        .addClass('col-xs-2')
        .append($('<span>').text(' '))
        .append($('<button>')
        .addClass('btn-generarRelMov')
        .append($('<i>').addClass('far').addClass('fa-file')
        )
        .append($('<span>').text('GENERAR'))
        .addClass('btn').addClass('btn-success')
        .attr('value',rel.id_fiscalizacion_movimiento)
        )
        .append($('<span>').text(' '))
        .append($('<button>')
        .addClass('btn-cargarRelMov')
        .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-upload')
        )
        .append($('<span>').text('CARGAR'))
        .addClass('btn').addClass('btn-success')
        .attr('value',rel.id_fiscalizacion_movimiento)
        )
        .append($('<span>').text(' '))
        .append($('<button>')
        .addClass('btn-imprimirRelMov')
        .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-print')
        )
        .append($('<span>').text('IMPRIMIR'))
        .addClass('btn').addClass('btn-success')
        .attr('value',rel.id_fiscalizacion_movimiento))
        )

        if(estado < 3  ){ fila.find('.btn-imprimirRelMov').hide();}
        if(estado > 2  ){ fila.find('.btn-imprimirRelMov').show();
                          fila.find('.btn-generarRelMov').hide();
                          fila.find('.btn-cargarRelMov').hide();}


    return fila;
};
