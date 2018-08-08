$(document).ajaxError(function(event, jqxhr, settings, thrownError){
  if(jqxhr.status == 351){
    var responseText = jQuery.parseJSON(jqxhr.responseText);
    alert(responseText.mensaje);
    window.location.href=responseText.url;
  }
});
/*evento en boton "guardar resultado" que abre el modal*/
$('#btn-add').click(function(){
        $('#alertaFecha').hide();
        $('#alertaBruto').hide();
        $('#alertaCanon').hide();
        $('#alertaExiste').hide();
        $('#alertaCasino').hide();
        $('#alertaJuego').hide();
        $("#canon").attr('readonly', false);
        $('#muestraImpuesto').hide();
  $('#frmResultado').trigger("reset");
  $('#myModal').modal('show');
});

$('#bruto').keyup(function (e){
      $('#alertaBruto').hide();
});

$('#canon').keyup(function (e){
      $('#alertaCanon').hide();
});

$('#fecha2').click(function(e){
  $('#alertaFecha').hide();
})

$('#juego').change(function (e){
      $('#alertaJuego').hide();
      if($(this).val() == 1){//si es maquina tragamoneda
          $('#muestraImpuesto').show();
          $('#iea').val('');
      }else{
          $('#muestraImpuesto').hide();
          $('#iea').val('');
      }
});

$('#id_casino').change(function (e){
      $('#alertaCasino').hide();
      $('#bruto').keyup();
});

$('#buscar').click(function(e){
      $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
         }
      })
      formData={
      id_casino: $('#busqueda_casino').val(),
      id_juego: $('#busqueda_juego').val(),
      anio: $('#busqueda_fecha').val(),
      }
       $.ajax({
           type: "POST",
           url: 'resultados/buscar',
           data: formData,
           dataType: 'json',
           success: function (data) {
              $('#tabla_resultados').empty();
              var resultado='';
              for (var i = 0; i < data.length; i++) {
                resultado += '<tr id="'+ data[i][0].id_resultado + '"><td class="col-md-1"> ' + data[i][1] +  '</td><td class="col-md-1">' + data[i][2] + '</td><td class="col-md-2">' + data[i][4] + '</td><td class="col-md-1">' + data[i][3]   + '</td><td class="col-md-2">$ ' + data[i][0].bruto + '</td><td class="col-md-2">$' + data[i][0].canon + '</td>';
                resultado += '<td class="col-md-3"><button class="btn btn-warning open_modal" value="' + data[i][0].id_resultado + '">Modificar</button>';
                resultado += ' <button class="btn btn-danger delete" value="' + data[i][0].id_resultado + '">Eliminar</button></td></tr>';
              }
              $('#tabla_resultados').prepend(resultado);
               console.log(data);
           },
           error: function (data) {
               console.log('Error:', data);
           }


      });
});

//Borrar Casino y remover de la tabla
$(document).on('click','.delete',function(){

    $('#modalEliminar .modal-title').text('ADVERTENCIA');
    $('#modalEliminar .modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    var resultado = $(this).val();
    $('#btn-eliminarModal').val(resultado);
    $('#modalEliminar').modal('show');
});

$(document).on('click','#btn-eliminarModal',function(){
    var resultado = $(this).val();
     $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })
    $.ajax({
        type: "delete",
        url:  'resultados/eliminar/' + resultado,
        success: function (data) {
            $('#' + resultado).remove();
            $('#modalEliminar').modal('hide');


            console.log(data);
        },
        error: function (data) {
            console.log('Error:', data);
        }
    });
});

/*busquda resultado a modificar*/
$(document).on('click','.open_modal',function(){
    var id_resultado = $(this).val();
    $("#canon").attr('readonly', false);

    $.get('resultados/buscar/' + id_resultado, function (data) {

        console.log(data);
        var fecha = data.mes + ' ' + data.anio;
        $('#fecha').val(data.resultado.anioMes);
        $('#fecha2').val(fecha);
        $('#bruto').val(data.resultado.bruto);
        $('#id_resultado').val(data.resultado.id_resultado);
        $('#canon').val(data.resultado.canon);
        $('#id_casino option:eq(' + data.resultado.id_casino +')').prop('selected', true);
        $('#juego option:eq(' + data.resultado.id_juego +')').prop('selected', true);
        $('#btn-save').val("update");
        $('#myModal').modal('show');
        switch (data.resultado.id_juego) {
          case 1:
                  $("#canon").attr('readonly', true);
                  $('#muestraImpuesto').show();
                  $('#iea').val(data.resultado.iea)
                    break;
          case 3:
                  $("#canon").attr('readonly', true);
                  $('#muestraImpuesto').hide();
                  break;
          default: $('#muestraImpuesto').hide(); break;
        }

    })
});

/*guardar-modificar resultado */
$("#btn-save").click(function (e) {
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      })

      var state = $('#btn-save').val();


      my_url='resultados/guardar';

      var formData = {
          juego: $('#juego').val(),
          fecha: $('#fecha').val(),
          bruto: $('#bruto').val(),
          canon: $('#canon').val(),
          iea:   $('#iea').val(),
          id_casino: $('#id_casino').val(),

      }

      if (state == "update"){
        formData = {
            id_resultado: $('#id_resultado').val(),
            juego: $('#juego').val(),
            fecha: $('#fecha').val(),
            bruto: $('#bruto').val(),
            iea:   $('#iea').val(),
            canon: $('#canon').val(),
            id_casino: $('#id_casino').val(),

        };
        my_url='resultados/modificar' ;
      }


      console.log(formData);
      $.ajax({
          type: "POST",
          url: my_url,
          data: formData,
          dataType: 'json',
          success: function (data) {
              console.log(data);
              $('#frmResultado').trigger("reset");
              $('#myModal').modal('hide');
              var resultado = '<tr id="'+ data.resultado.id_resultado + '"><td class="col-md-1"> '+ data.mes +  '</td><td class="col-md-1">' + data.año + '</td><td class="col-md-2">' + data.casino + '</td><td class="col-md-1">' + data.juego + '</td><td class="col-md-2">$ ' + data.resultado.bruto + '</td><td class="col-md-2">$ ' + data.resultado.canon + '</td>';
              resultado += '<td class="col-md-3"><button class="btn btn-warning open_modal" value="' + data.resultado.id_resultado + '">Modificar</button>';
              resultado += ' <button class="btn btn-danger delete" value="' + data.resultado.id_resultado + '">Eliminar</button></td></tr>';
              if (state == "add"){
                 $('#tabla_resultados').prepend(resultado);
           }else{ //if user updated an existing record
               $("#" + data.resultado.id_resultado).replaceWith(resultado);
           }


          },
          error: function (data) {

            var response = JSON.parse(data.responseText);

            $('#alertaFecha').hide();
            $('#alertaBruto').hide();
            $('#alertaCanon').hide();
            $('#alertaExiste').hide();
            $('#alertaCasino').hide();
            $('#alertaJuego').hide();

            if(typeof response.fecha !== 'undefined'){
              $('#alertaFecha span').text(response.fecha[0]);
              $('#alertaFecha').show();

            }
            if(typeof response.bruto !== 'undefined'){
              $('#alertaBruto span').text(response.bruto[0]);
              $('#alertaBruto').show();

            }
            if(typeof response.canon !== 'undefined'){
              $('#alertaCanon span').text(response.canon[0]);
              $('#alertaCanon').show();

            }
            if(typeof response.existe !== 'undefined'){
              $('#alertaExiste span').text(response.existe[0]);
              $('#alertaExiste').show();

            }
            if(typeof response.id_casino !== 'undefined'){
              $('#alertaCasino span').text(response.id_casino[0]);
              $('#alertaCasino').show();

            }
            if(typeof response.juego !== 'undefined'){
              $('#alertaJuego span').text(response.juego[0]);
              $('#alertaJuego').show();

            }

            if(typeof response.iea !== 'undefined'){
              $('#alertaImpuesto span').text(response.iea[0]);
              $('#alertaImpuesto').show();

            }
      


            console.log('Error:', data);
          }
      });
});
