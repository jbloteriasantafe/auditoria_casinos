$(document).ready(function() {

  $('.tituloSeccionPantalla').text('Administrar casinos');
  $('#opcCasino').attr('style','border-left: 6px solid #185891');
  $('#opcCasino').addClass('opcionesSeleccionado');

  $(function(){
    $('#dtpFechaIni').datetimepicker({
          language:  'es',
          todayBtn:  1,
          autoclose: 1,
          todayHighlight: 1,
          format: 'yyyy-mm-dd',
          pickerPosition: "bottom-left",
          startView: 4,
          minView: 2,
          container:$('#modalModificarCasino')
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
          container:$('#modalAlta')
        });
  });

});

//Quitar eventos de la tecla Enter y guardar
$(document).on('keypress',function(e){
    if(e.which == 13 && $('#modalCasino').is(":visible")) {
      e.preventDefault();
      $('#btn-guardar').click();
    }
});

//PESTAÑAS
$("ul.pestaniasTF li").click(function() {

    $("ul.pestaniasTF li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content

    if(activeTab == '#fpesos'){
      $('#p_pesos').show();
      $('.pestaniaPesos').show();
      $('.pestaniaTurnos').hide();
      $('.pestaniaDolares').hide();

    }
    if(activeTab == '#fdolares'){

      $('#p_dolares').show();
      $('.pestaniaDolares').show();
      $('.pestaniaTurnos').hide();
      $('.pestaniaPesos').hide();

    }
    if(activeTab == '#fturnos'){
      $('#p_turnos').show();
      $('.pestaniaTurnos').show();
      $('.pestaniaPesos').hide();
      $('.pestaniaDolares').hide();

    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});

//nuevo casino-btn
$('#btn-nuevo').on('click', function(e){
  e.preventDefault();

  limpiarModalAlta();
  setearModal(0);
  //pestañas
    $(".tab_content").hide(); //Hide all content
    	$("ul.pestaniasTF li:first").addClass("active").show(); //Activate first tab
    	$(".tab_content:first").show(); //Show first tab content
      $('.pestaniaTurnos').show();
      $('.pestaniaPesos').hide();
      $('.pestaniaDolares').hide();
      $('#p_pesos').removeClass('active');
      $('#p_dolares').removeClass('active');


  $.get('casinos/getFichas',function(data){
    for (var i = 0; i < data.fichas.length; i++) {

      var fila=cargarFichas(data.fichas[i]);

      if(fila[0]!=null){
        fila[0].find('.utilizar').prop('checked',false);
        $('#tablaFichas').append(fila[0]);
      }
      else{
        fila[1].find('.utilizarDol').prop('checked',false);
        $('#tablaFichasDol').append(fila[1]);
      }
    }
  })

  $('#modalAlta').modal('show');

});

$('#btn-guardar').on('click',function(e){
  e.preventDefault();
  $(this).prop('disabled',true);


  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var t=$('#tablaTurnos tbody tr');
  var turnos=[];

  $.each(t, function(index, value){
    var turno={
       nro:$(this).find('.nro_turno').val(),
       desde:$(this).find('.desde').val(),
       hasta:$(this).find('.hasta').val(),
       entrada:$(this).find('.hrI').val(),
       salida:$(this).find('.hrF').val()
    }

    turnos.push(turno);
  });
  var fichasNuevas=[];
  var fichasPesos=[];

  $('#tablaFichas input:checked').each(function(e){
    if($(this).val() != 0){
      fichasPesos.push({
                        id_ficha:$(this).val(),
                      });
    }
    else{
      var newF=$(this).parent().parent().find('.valorF').val();
      fichasNuevas.push({
                          valor_ficha: newF,
                          id_moneda: 1,
                        });
    }
  })

  var fichasDolares=[];

  $('#tablaFichasDol input:checked').each(function(e){
    if($(this).val() != 0){
      fichasDolares.push({
                          id_ficha:$(this).val(),
                          });
    }
    else{
      var newF=$(this).parent().parent().find('.valorDol').val();
      fichasNuevas.push({
                          valor_ficha: newF,
                          id_moneda: 2,
                        });
    }
  })

  var formData = {
    nombre: $('#nombre').val(),
    codigo: $('#codigo').val(),
    turnos: turnos,
    fichas_pesos: fichasPesos,
    fichas_dolares: fichasDolares,
    fichas_nuevas: fichasNuevas,
    porcentaje_sorteo_mesas: $('#porcentaje_sorteo_mesas').val(),
    fecha_inicio:$('#fecha_inicio').val()
  }
  //var id_casino = $('#id_casino').val();

  $.ajax({
      type: "POST",
      url: 'casinos/guardarCasino',
      data: formData,
      dataType: 'json',
      success: function (data) {

        var fila=nuevaFila(data);
        $('#tablaCasinos').append(fila);

        setearModal(1);

        $('#modalAlta #alertaCanon').show();
        $('#modalAlta').animate({scrollTop:$('#alertaCanon').offset().top},"slow");

        $('#mensajeErrorTurnosCarga').hide();

      },
      error: function(data){
        console.log(data);
        $('#btn-guardar').prop('disabled',false);

        var errors = $.parseJSON(data.responseText).errors;
        $.each(errors, function (key, val) {
          if(key == 'nombre'){
            mostrarErrorValidacion($('#nombre'),val[0],false);
          }
          if(key == 'porcentaje_sorteo_mesas'){
            mostrarErrorValidacion($('#porcentaje_sorteo_mesas'),val[0],false);
          }
          if(key == 'fecha_inicio'){
            mostrarErrorValidacion($('#fecha_inicio'),val[0],false);
          }

          if(key == 'codigo'){
            mostrarErrorValidacion($('#codigo'),val[0],false);
          }

          if(key != 'codigo' && key != 'fecha_inicio' &&
             key != 'porcentaje_sorteo_mesas' && key != 'nombre' )
             {
              $('#mensajeErrorTurnosCarga').show();
              $('#modalAlta').animate({scrollTop:$('#mensajeErrorTurnosCarga').offset().top},"slow");

          }
    })
  }
    })

});

$('#btn-continuar').on('click',function(e){
  e.preventDefault();
  $(this).prop('disabled',true);

  $('#modalAlta').modal('hide');
  $('#mensajeExito h3').text('EXITO');
  $('#mensajeExito p').text('Casino creado.');
  $('#mensajeExito').show();

  $("#tablaCasinos").trigger("update");

})

//INGRESA LA CANTIDAD DE TURNOS, SE CREA TABLA
$(document).on('click', '.okTurnos',function(e){
  e.preventDefault();

  $('#tablaTurnos tbody > tr').remove();

  var cantidad= $('.cant_turno').val();

  for (var i = 0; i < cantidad; i++) {
    var nro=i+1;
    var fila = $(document.createElement('tr'));

    fila.append($('<td>').append($('<input>').addClass('col-md-1 nro_turno').addClass('form-control').css('text-align','center')))
        .append($('<td>').append($('<select>').addClass('col-md-2 desde').addClass('form-control')
              .append($('<option>').val(7).text('Domingo').append($('</option>')))
              .append($('<option>').val(1).text('Lunes').append($('</option>')))
              .append($('<option>').val(2).text('Martes').append($('</option>')))
              .append($('<option>').val(3).text('Miércoles').append($('</option>')))
              .append($('<option>').val(4).text('Jueves').append($('</option>')))
              .append($('<option>').val(5).text('Viernes').append($('</option>')))
              .append($('<option>').val(6).text('Sábado').append($('</option>')))))
        .append($('<td>').append($('<select>').addClass('col-md-2 hasta').addClass('form-control')
              .append($('<option>').val(7).text('Domingo').append($('</option>')))
              .append($('<option>').val(1).text('Lunes').append($('</option>')))
              .append($('<option>').val(2).text('Martes').append($('</option>')))
              .append($('<option>').val(3).text('Miércoles').append($('</option>')))
              .append($('<option>').val(4).text('Jueves').append($('</option>')))
              .append($('<option>').val(5).text('Viernes').append($('</option>')))
              .append($('<option>').val(6).text('Sábado').append($('</option>')))))
        .append($('<td>').append($('<input>').addClass('col-md-2 hrI').attr('type','time').css('text-align','center').addClass('form-control')))
        .append($('<td>').append($('<input>').addClass('col-md-2 hrF').attr('type','time').addClass('form-control')))



    $('#tablaTurnos tbody').append(fila);
  }
  $('#tablaTurnos').show();

})

//PESTAÑAS MODIFICAR
$("ul.pestaniasTFM li").click(function() {

    $("ul.pestaniasTFM li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content

    if(activeTab == '#fpesosModif'){
      $('#p_pesos_modif').show();
      $('.pestaniaPesosModif').show();
      $('.pestaniaTurnosModif').hide();
      $('.pestaniaDolaresModif').hide();

    }
    if(activeTab == '#fdolaresModif'){

      $('#p_dolares_modif').show();
      $('.pestaniaDolaresModif').show();
      $('.pestaniaTurnosModif').hide();
      $('.pestaniaPesosModif').hide();

    }
    if(activeTab == '#fturnosModif'){
      $('#p_turnos_modif').show();
      $('.pestaniaTurnosModif').show();
      $('.pestaniaPesosModif').hide();
      $('.pestaniaDolaresModif').hide();

    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});


$(document).on('click','.modificarCasino',function(e){
  e.preventDefault();

  limpiarModificar();
  $('#btn-preModificar').val($(this).val());
  $('#btn-modificarCas').val($(this).val());

  $('#modalPreModificar').modal('show');
});

$('#btn-preModificar').on('click',function(e){
  e.preventDefault();
  // $('#modalPreModificar').modal('hide');

  //pestañas
    $(".tab_content").hide(); //Hide all content
    	$("ul.pestaniasTFM li:first").addClass("active").show(); //Activate first tab
    	$(".tab_content:first").show(); //Show first tab content
      $('.pestaniaTurnosModif').show();
      $('.pestaniaPesosModif').hide();
      $('.pestaniaDolaresModif').hide();
      $('#p_pesos_modif').removeClass('active');
      $('#p_dolares_modif').removeClass('active');


      var id_casino = $(this).val();
      $('#mensajeErrorTurnos').hide();

      $('#tablaTurnosModif tbody > tr').remove();

      $.get("casinos/obtenerCasino/" + id_casino, function(data){

          $('#modalModificarCasino #id_cas').val(data.casino.id_casino);
          $('#nombreModif').val(data.casino.nombre).prop('disabled', true);
          $('#codigoModif').val(data.casino.codigo);
          $('#finicioModif').val(data.casino.fecha_inicio);
          $('#finicioModif').prop('disabled',true);
          $('#porcentajeModif').val(data.casino.porcentaje_sorteo_mesas);

          var fk = data.fichas;
          Object.keys(fk).forEach(key => {
            let value = fk[key];
            var fila=cargarFichas(value);
            if(fila[0] != null){
              $('#tablaFichasModif').append(fila[0]);
            }else{
              $('#tablaFichasDolModif').append(fila[1]);
            }
          });

          var f=$('#tablaFichasModif tbody > tr');
          var t=$('#tablaFichasDolModif tbody > tr');

          for (var i = 0; i < data.fichas_casino.length; i++) {
            if(data.fichas_casino[i].id_moneda == 1){
              $.each(f, function(index, value){
                if($(this).find('.utilizar').val() == data.fichas_casino[i].id_ficha){
                  $(this).find('.utilizar').prop('checked',true);
                }
              });
            }
            else{
              $.each(t, function(index, value){
                if($(this).find('.utilizarDol').val() == data.fichas_casino[i].id_ficha){
                  $(this).find('.utilizarDol').prop('checked',true);
                }
              });
            }
          }

          for (var i = 0; i < data.turnos.length; i++) {
            var fila1 = $(document.createElement('tr'));

            fila1.attr('id',data.turnos[i].id_turno);
            fila1.append($('<td>').append($('<input>').addClass('col-md-1 nro_turno_modif').addClass('form-control').val(data.turnos[i].nro_turno).css('text-align','center')))
                .append($('<td>').append($('<select>').addClass('col-md-2 desdeModif').addClass('form-control')
                  .append($('<option>').val(7).text('Domingo').append($('</option>')))
                  .append($('<option>').val(1).text('Lunes').append($('</option>')))
                  .append($('<option>').val(2).text('Martes').append($('</option>')))
                  .append($('<option>').val(3).text('Miércoles').append($('</option>')))
                  .append($('<option>').val(4).text('Jueves').append($('</option>')))
                  .append($('<option>').val(5).text('Viernes').append($('</option>')))
                  .append($('<option>').val(6).text('Sábado').append($('</option>')))))
                .append($('<td>').append($('<select>').addClass('col-md-2 hastaModif').addClass('form-control')
                  .append($('<option>').val(7).text('Domingo').append($('</option>')))
                  .append($('<option>').val(1).text('Lunes').append($('</option>')))
                  .append($('<option>').val(2).text('Martes').append($('</option>')))
                  .append($('<option>').val(3).text('Miércoles').append($('</option>')))
                  .append($('<option>').val(4).text('Jueves').append($('</option>')))
                  .append($('<option>').val(5).text('Viernes').append($('</option>')))
                  .append($('<option>').val(6).text('Sábado').append($('</option>')))))

                  if(data.turnos[i].entrada != null){
                    var piecesi = data.turnos[i].entrada.split(':')
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
                fila1.append($('<td>').append($('<input>').addClass('col-md-2 hrImodif').attr('type','time').val(houri + ':' + minutei).css('text-align','center').addClass('form-control')))

                if(data.turnos[i].salida != null){
                  var p = data.turnos[i].salida.split(':')
                  var h, m;

                  if(piecesi.length === 3) {
                    h = p[0];
                    m = p[1];
                  }
                }else{
                  var h, m;
                  h = '-';
                  m = '-';

                }
                fila1.append($('<td>').append($('<input>').addClass('col-md-2 hrFmodif').attr('type','time').val(h + ':' + m).addClass('form-control')))
                fila1.append($('<td>').append($('<button>').addClass('btn btn-successAceptar eliminarTurno').val(data.turnos[i].id_turno).append($('<i>').addClass('fa fa-fw fa-trash'))))

                fila1.find('.desdeModif').val(data.turnos[i].dia_desde).prop('selected',true);
                fila1.find('.hastaModif').val(data.turnos[i].dia_hasta).prop('selected',true);

            $('#tablaTurnosModif tbody').append(fila1);
          }
          $('#tablaTurnosModif').show();

          $('#modalPreModificar').modal('hide');
          $('#modalModificarCasino').modal('show');

      });

      //Ocultar validaciones
      ocultarErrorValidacion($('#nombreModif'));
      ocultarErrorValidacion($('#codigoModif'));
  });

$(document).on('click','.masTurnos',function(){

  var fila1 = $(document.createElement('tr'));

  fila1.append($('<td>').append($('<input>').addClass('col-md-1 nro_turno_modif').addClass('form-control').css('text-align','center')))
      .append($('<td>').append($('<select>').addClass('col-md-2 desdeModif').addClass('form-control')
            .append($('<option>').val(7).text('Domingo').append($('</option>')))
            .append($('<option>').val(1).text('Lunes').append($('</option>')))
            .append($('<option>').val(2).text('Martes').append($('</option>')))
            .append($('<option>').val(3).text('Miércoles').append($('</option>')))
            .append($('<option>').val(4).text('Jueves').append($('</option>')))
            .append($('<option>').val(5).text('Viernes').append($('</option>')))
            .append($('<option>').val(6).text('Sábado').append($('</option>')))))
      .append($('<td>').append($('<select>').addClass('col-md-2 hastaModif').addClass('form-control')
            .append($('<option>').val(7).text('Domingo').append($('</option>')))
            .append($('<option>').val(1).text('Lunes').append($('</option>')))
            .append($('<option>').val(2).text('Martes').append($('</option>')))
            .append($('<option>').val(3).text('Miércoles').append($('</option>')))
            .append($('<option>').val(4).text('Jueves').append($('</option>')))
            .append($('<option>').val(5).text('Viernes').append($('</option>')))
            .append($('<option>').val(6).text('Sábado').append($('</option>')))))

      .append($('<td>').append($('<input>').addClass('col-md-2 hrImodif').attr('type','time').css('text-align','center').addClass('form-control')))
      .append($('<td>').append($('<input>').addClass('col-md-2 hrFmodif').attr('type','time').addClass('form-control')))
      fila1.append($('<td>').append($('<button>').addClass('btn btn-successAceptar eliminarTurnoSinID').append($('<i>').addClass('fa fa-fw fa-trash'))))

      $('#tablaTurnosModif').append(fila1);
});

$(document).on('click','.eliminarTurno',function(e){

  e.preventDefault();

  id=$(this).val();

  var f= $('#tablaTurnosModif tbody > tr');
  $.each(f, function(index, value){
        if($(this).attr('id') == id){
            $(this).remove();
        }
        })

})

$(document).on('click','.eliminarTurnoSinID',function(e){

  e.preventDefault();

  $(this).parent().remove();

})


$('#btn-modificarCas').on('click',function(e){
  e.preventDefault();
  $(this).prop('disabled',true);

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  var t=$('#tablaTurnosModif tbody tr');
  var turnos=[];

  $.each(t, function(index, value){
    var turno={
      nro:$(this).find('.nro_turno_modif').val(),
      desde:$(this).find('.desdeModif').val(),
      hasta:$(this).find('.hastaModif').val(),
      entrada:$(this).find('.hrImodif').val(),
      salida:$(this).find('.hrFmodif').val(),
    }

    turnos.push(turno);
  });

  var fichasNuevas=[];
  var fichasPesos=[];

  $('#tablaFichasModif input:checked').each(function(e){
    if($(this).val() != 0){
      fichasPesos.push({
                          id_ficha:$(this).val(),
                          });
    }
    else{
      var newF=$(this).parent().parent().find('.valorF').val();
      fichasNuevas.push({
                          valor_ficha: newF,
                          id_moneda: 1,
                        });
    }
  })

  var fichasDolares=[];

  $('#tablaFichasDolModif input:checked').each(function(e){
    if($(this).val() != 0){
      fichasDolares.push({
                          id_ficha:$(this).val(),
                          });
    }
    else{
      var newF=$(this).parent().parent().find('.valorDol').val();
      fichasNuevas.push({
                          valor_ficha: newF,
                          id_moneda: 2,
                        });
    }
  })

  var formData = {
    id_casino: $(this).val(),
    codigo: $('#codigoModif').val(),
    turnos:turnos,
    fichas_dolares:fichasDolares,
    fichas_pesos:fichasPesos,
    fichas_nuevas: fichasNuevas,
    porcentaje_sorteo_mesas: $('#porcentajeModif').val(),
  }
  //var id_casino = $('#id_casino').val();

  $.ajax({
      type: "POST",
      url: 'casinos/modificarCasino',
      data: formData,
      dataType: 'json',
      success: function (data) {

        var fila=modificarFila(data);
        $("#" + data.casino.id_casino).replaceWith(fila);
        $('#mensajeErrorTurnos').hide();

        $('#modalModificarCasino').modal('hide');
        $('#mensajeExito h3').text('EXITO');
        $('#mensajeExito p').text('Casino modificado.');
        $('#mensajeExito').show();

      },
      error: function(data){
        console.log(data);
        $('#btn-modificarCas').prop('disabled',false);

        var errors = $.parseJSON(data.responseText).errors;
        $.each(errors, function (key, val) {
          if(key == 'nombre'){
            mostrarErrorValidacion($('#nombreModif'),val[0],false);
          }
          if(key == 'porcentaje_sorteo_mesas'){
            mostrarErrorValidacion($('#porcentajeModif'),val[0],false);
          }
          if(key == 'fecha_inicio'){
            mostrarErrorValidacion($('#finicioModif'),val[0],false);
          }

          if(key == 'codigo'){
            mostrarErrorValidacion($('#codigoModif'),val[0],false);
          }

          if(key != 'codigo' && key != 'fecha_inicio' &&
             key != 'porcentaje_sorteo_mesas' && key != 'nombre' )
             {
              $('#mensajeErrorTurnos').show();

          }
    })
  }

})
});

$('#modalModificarCasino').on('show', function () {
  $(document).find('body').css('overflow', 'hidden');
});

// retiramos la propiedad de overflow para devolver el scroll
// removemos esta propiedad al cerrar el modal
$('#modalModificarCasino').on('hide', function () {
  $(document).find('body').css('overflow', 'auto');
});

//alta
$(document).on('click','.agregarFPesos',function(){
  var fila=$('#moldeFicha').clone();
  fila.removeAttr('id');
  fila.find('.valorF').append($('<input>').addClass('form-control valorFinput').css('cssText','text-align:center !important').attr('placeholder','Ingrese el Valor '));
  fila.find('.utilizar').val(0);
  fila.append($('<td>').addClass('col-xs-2').append($('<i>').addClass('fa fa-fw fa-trash').css('cssText','padding-left:10px').addClass('removeFicha').css('font-size','16px')));

  fila.css('display','');
  $('#dd').css('display','block');
  $('#tablaFichas').append(fila);

  $('#modalAlta').animate({scrollTop:$('#tablaFichas').offset().top},"slow");

})
$(document).on('click','.agregarFDolares',function(){
  var fila=$('#moldeFichaDol').clone();
  fila.removeAttr('id');
  fila.find('.valorDol').append($('<input>').addClass('form-control valorFinput').css('cssText','text-align:center !important').attr('placeholder','Ingrese el Valor '));
  fila.find('.utilizarDol').val(0);
  fila.append($('<td>').addClass('col-xs-2').append($('<i>').addClass('fa fa-fw fa-trash').css('cssText','padding-left:10px').addClass('removeFicha').css('font-size','16px')));

  fila.css('display','');
  $('#tt').css('display','block');
  $('#tablaFichasDol').append(fila);
})

//modificar
$(document).on('click','.agregarFPesosModif',function(){
  var fila=$('#moldeFicha').clone();
  fila.removeAttr('id');
  fila.find('.valorF').append($('<input>').addClass('form-control valorFinput').css('cssText','text-align:center !important').attr('placeholder','Ingrese el Valor '));
  fila.find('.utilizar').val(0);
  fila.append($('<td>').addClass('col-xs-2').append($('<i>').addClass('fa fa-fw fa-trash').css('cssText','padding-left:10px').addClass('removeFicha').css('font-size','16px')));

  fila.css('display','');
  $('#dd').css('display','block');
  $('#tablaFichasModif').append(fila);
})

$(document).on('click','.agregarFDolaresModif',function(){
  var fila=$('#moldeFichaDol').clone();
  fila.removeAttr('id');
  fila.find('.valorDol').append($('<input>').addClass('form-control valorDolinput').css('cssText','text-align:center !important').attr('placeholder','Ingrese el Valor '));
  fila.find('.utilizarDol').val(0);
  fila.append($('<td>').addClass('col-xs-2').append($('<i>').addClass('fa fa-fw fa-trash').css('cssText','padding-left:10px').addClass('removeFicha').css('font-size','16px')));

  fila.css('display','');
  $('#tt').css('display','block');
  $('#tablaFichasDolModif').append(fila);
})

//remover fila nueva de ficha
$(document).on('click','.removeFicha',function(){
  $(this).parent().parent().remove();
});



function cargarFichas(data){
  var f= [];
  if(data.id_moneda == 1){
    var fila= $('#moldeFicha').clone();

    fila.removeAttr('id');
    fila.attr('id',data.id_ficha);
    fila.find('.valorF').text(data.valor_ficha);
    fila.find('.utilizar').val(data.id_ficha);

    fila.css('display','');
    $('#dd').css('display','block');
    var filaDol=null;

  }else{
    var filaDol= $('#moldeFichaDol').clone();

    filaDol.removeAttr('id');
    filaDol.attr('id',data.id_ficha);
    filaDol.find('.valorDol').text(data.valor_ficha);
    filaDol.find('.utilizarDol').val(data.id_ficha);

    filaDol.css('display','');
    $('#tt').css('display','block');

    var fila=null;
  }

  f.push(fila);
  f.push(filaDol);

  return f;
}

function limpiarModalAlta(){
  $('.pestaniasAlta').show();
  $('#btn-guardar').prop('disabled',false);
  $('#mensajeExito').hide();
  $('#tablaTurnos').hide();
  $('#tablaTurnos tbody > tr').remove();
  $('#tablaFichas tbody > tr').remove();
  $('#tablaFichasDol tbody > tr').remove();
  $('#mensajeErrorTurnosCarga').hide();
  $('#btn-guardar').show();
  $('#btn-continuar').hide();
  $('#alertaCanon').hide();
  $('#nombre').val('');
  $('#codigo').val('');
  $('#porcentaje_sorteo_mesas').val('');
  $('#fecha_inicio').val('');
  $('.cant_turno').val('');
  //Ocultar validaciones
  ocultarErrorValidacion($('#nombre'));
  ocultarErrorValidacion($('#codigo'));
  ocultarErrorValidacion($('#porcentaje_sorteo_mesas'));
  ocultarErrorValidacion($('#fecha_inicio'));

}

function limpiarModificar(){
  $('.pestaniasModif').show();
  $('#btn-modificarCas').prop('disabled',false);
  $('#mensajeExito').hide();
  $('#tablaTurnosModif tbody > tr').remove();
  $('#tablaFichasModif tbody > tr').remove();
  $('#tablaFichasDolModif tbody > tr').remove();
  $('#mensajeErrorTurnos').hide();
  $('#codigoModif').val();
  $('#porcentajeModif').val();
  $('#finicioModif').val();
  //Ocultar validaciones
  ocultarErrorValidacion($('#codigoModif'));
  ocultarErrorValidacion($('#finicioModif'));
  ocultarErrorValidacion($('#porcentajeModif'));

}

function nuevaFila(data){
  var fila=$('#moldeFilaCasino').clone();
  fila.removeAttr('id');
  fila.attr('id',data.casino.id_casino);

  fila.find('.NCasino').text(data.casino.nombre);
  fila.find('.CodCasino').text(data.casino.codigo);
  fila.find('.fInicioCasino').text(data.casino.fecha_inicio);
  fila.find('.modificarCasino').val(data.casino.id_casino);

  fila.css('display','');
  $('#dd').css('display','block');

  return fila;
}

function modificarFila(data){

  var fila=$('#moldeFilaCasino').clone();
  fila.removeAttr('id');
  fila.attr('id',data.casino.id_casino);

  fila.find('.NCasino').text(data.casino.nombre);
  fila.find('.CodCasino').text(data.casino.codigo);
  fila.find('.fInicioCasino').text(data.casino.fecha_inicio);
  fila.find('.modificarCasino').val(data.casino.id_casino);

  fila.css('display','');
  $('#dd').css('display','block');

  return fila;
}

function setearModal(data){

  if(data==1){
  $('#btn-guardar').hide();
  $('#btn-continuar').show();
  $('#btn-continuar').prop('disabled',false);
  $('#nombre').prop('disabled',true);
  $('#codigo').prop('disabled',true);
  $('#fecha_inicio').prop('disabled',true);
  $('#porcentaje_sorteo_mesas').prop('disabled',true);
  $('.okTurnos').prop('disabled',true);
  $('#btn-cancelar-alta').hide();

  $('.pestaniasAlta').hide();

  var t=$('#tablaTurnos tbody > tr');
  $.each(t, function(index, value){
    $(this).find('.nro_turno').prop('disabled',true);
       $(this).find('.desde').prop('disabled',true);
       $(this).find('.hasta').prop('disabled',true);
       $(this).find('.hrI').prop('disabled',true);
       $(this).find('.hrF').prop('disabled',true);
    })
  }
  else{
    $('.pestaniasModif').hide();

    $('#nombre').prop('disabled',false);
    $('#codigo').prop('disabled',false);
    $('#fecha_inicio').prop('disabled',false);
    $('#porcentaje_sorteo_mesas').prop('disabled',false);
    $('.okTurnos').prop('disabled',false);
    $('#btn-cancelar-alta').show();

  }
}
