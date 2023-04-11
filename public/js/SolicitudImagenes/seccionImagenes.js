$(document).ready(function() {

  $('#barraImagenes').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Gestión Imágenes de Búnker');
  $('#barraImagenes').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#barraImagenes').addClass('opcionesSeleccionado');

  $('#filtroCasinoImag').val('0');
  $('#mes_filtro').val('');
  $('#identificacion').val('');

  $(function(){
    $('#dtpFecha').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'yyyy-MM',
      pickerPosition: "bottom-left",
      startView: 3,
      minView: 3,
      ignoreReadonly: true,


        });
  });
  $('#btn-buscar-imagenes').trigger('click',[1,10,'img.created_at','desc']);

});

$('#btn-buscar-imagenes').click(function(e,pagina,page_size,columna,orden){

  e.preventDefault();
  var ident=$('#identificacion').val();

  if(ident.length > 20){

    mostrarErrorValidacion($('#identificacion'),'La cantidad máxima de caracteres permitidos es 20',false);
  }
  else{

    ocultarErrorValidacion($('#identificacion'));
    $('#tablaSorteos tbody tr').remove();
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
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaSorteos .activa').attr('value'),orden: $('#tablaSorteos .activa').attr('estado')} ;

    if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
      var sort_by =  {columna: 'img.created_at',orden: 'desc'} ;

      //$('#tablaInicial th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

    var fecha=formatear($('#mes_filtro').val());
    console.log('fe',fecha.length);
    if(fecha.length != 0){
      var mes=fecha[0] + '-' + fecha[2];
    }else{
      var mes='';
    }
    console.log('tt',typeof mes);
  var formData= {
    mes:mes,
    id_casino: $('#filtroCasinoImag').val(),
    identificacion: $('#identificacion').val(),
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
      url: 'solicitudImagenes/buscar',
      data: formData,
      dataType: 'json',

      success: function (data){
          $('#herramientasPaginacion').generarTitulo(1,page_size,data.datos.data.length,clickIndice);

          for (var i = 0; i < data.datos.data.length; i++) {
              var fila= generarFila(data.datos.data[i]);
              $('#tablaSorteos').append(fila);
          }
            $('#herramientasPaginacion').generarIndices(1,page_size,data.datos.data.length,clickIndice);
      },

      error: function (data) {
        console.log('error',data);

        }
   })
 }//fin else
});

$('#btn-sortear-fechas').on('click',function(e){

  e.preventDefault();
  $('#mensajeError').hide();
  $('#modalSorteo').find('.loading').show();
  $('#modalSorteo').find('.detallesFechas').hide();

  $('#tablaFechas tbody tr').remove();

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  var formData = {
  }

  $.ajax({
      type: "POST",
      url: 'solicitudImagenes/sorteoFechasMesas',
      data: formData,
      dataType: 'json',

        beforeSend: function(data){
          $('#modalSorteo').modal('show');

       },
      success: function (data) {

        $('#modalSorteo').find('.loading').hide();
        $('#modalSorteo').find('.detallesFechas').show();

         for (var i = 0; i < data.sorteo.length; i++) {
          var nuevo=$('#modalSorteo').find('#datosPorCasinos').clone();
          nuevo.removeAttr('id');
          if(data.sorteo[i].bunker != ''){
            nuevo.find('.casinoNombre').text(data.sorteo[i].casino);
            nuevo.find('.fechasVer').text('FECHAS: ' + data.sorteo[i].bunker.fechas);
            nuevo.find('.mesAnio').text('DEL MES: ' + data.sorteo[i].bunker.mes_anio);

            var m=data.sorteo[i].bunker.mesas;
            var mesas=m.split(';');
            var arrayMesas='';

            for (var j = 0; j < mesas.length; j++) {
              if (j== (mesas.length - 1)) {
                arrayMesas= arrayMesas + mesas[j];
              }else{
              arrayMesas= arrayMesas + mesas[j] + ' - ';}

            }
            nuevo.find('.mesasVer').text('MESAS: ' + arrayMesas);
          }
          else{
            nuevo.find('.casinoNombre').text(data.sorteo[i].casino);
            nuevo.find('.fechasVer').text('No es posible generar el sorteo para el mes anterior. Puede que este casino no tenga cierres cargados o mesas asociadas.');
          }

          nuevo.css('display','');
          $('#modalSorteo').find('.detallesFechas').append(nuevo);
         }
      },
      error: function (data) {

         $('#modalSorteo').modal('hide');
         $('#mensajerError p').text('El sorteo para el mes anterior ya ha sido generado.');
         $('#mensajeError').show();

      }
  });
});

//detectar cierre del modal de generar sorteos
$("#modalSorteo").on('hidden.bs.modal', function () {
  $('#btn-buscar-imagenes').trigger('click',[1,10,'img.created_at','desc']);
});

//cargar datos de imag de bunker obtenidas
$(document).on('click','.cargarSorteo', function(e){
  e.preventDefault();

  var id=$(this).val();
  $('#tablaDatosCds').hide();
  $('#mensajeExito').hide();

  $('.continuarCarga').val(id);
  $('.continuarCarga').hide();
  $('.cant_cds').val('');
  $('#obsSorteo').val('');
  $('#desplazarTMEsas').hide();
  $('.verObs').hide();
  $('#tablaMesasSorteadas tbody tr').remove();
  $('#tablaMesasSorteadas2 tbody tr').remove();
  $('#tablaMesasSorteadas3 tbody tr').remove();

  $('#btn-guardar').hide();
  $('#btn-guardar').val(id);

  $('.fechaSorteada').val($(this).attr('data-fecha')).prop('readonly',true);
  $('.casinoCarga').val($(this).attr('data-casino')).prop('readonly',true);


  $('#modalCargarDatos').modal('show');
})

$(document).on('change','.nombreCd',function(){

  var f=$('#tablaDatosCds tbody > tr');
  var i=$('.cant_cds').val();
  var valor=$(this).val();

  $(this).parent().parent().find('.btn-borrar-cd').attr('data-id',valor);

  $.each(f, function(index, value){
    if($(this).find('.nombreCd').val() != ''){
       i=i - 1;
    }
  });
  if(i==0){
    if(compararId() == true){
      $('#errorCdId').hide();
    $('.continuarCarga').show();
    $('.continuarCarga').prop('disabled',false);
    $('#tablaDatosCds').find('.nombreCd').prop('readonly',true);
  }
  else{
    $('#errorCdId').show();
  }
  }
});

function compararId(){
  var f=$('#tablaDatosCds tbody > tr');
  var array=[];
  var rta=true;

  $.each(f, function(index, value){
    var id=$(this).find('.nombreCd').val();

    if((array.includes(id)) == false){

      array.push(id);
    }else{
      rta= false;
    }

  });
  console.log('rta',rta);
  return rta;
}

$(document).on('change','.cant_cds',function(){

    ocultarErrorValidacion($('.cant_cds'));

    $('#tablaDatosCds tbody tr').remove();
    $('.idCd option').remove();
    $('.continuarCarga').hide();
    //$('.continuarCarga').prop('readonly',false);

    $('#desplazarTMEsas').hide();
    $('.verObs').hide();

    $('#tablaMesasSorteadas tbody tr').remove();
    $('#tablaMesasSorteadas2 tbody tr').remove();
    $('#tablaMesasSorteadas3 tbody tr').remove();

});

$(document).on('change','.dropMesa',function(){

  var drop= Numeros($(this).val());
  var hayImp=0;
  var id_detalle_img_bunker=$(this).attr('data-id');
  var tabla=$(this).attr('data-tabla');
  var modificar=$(this).attr('data-modificar');

  if( modificar == "0"){
      switch (tabla) {
        case '1':
          var f=$('#tablaMesasSorteadas tbody > tr');
          break;
        case '2':
          var f=$('#tablaMesasSorteadas2 tbody > tr');
          break;
        case '3':
          var f=$('#tablaMesasSorteadas3 tbody > tr');
          break;
        default:
      }
    }
    else{
      switch (tabla) {
        case '1':
          var f=$('#tablaMesasSorteadasMod tbody > tr');
          break;
        case '2':
          var f=$('#tablaMesasSorteadas2Mod tbody > tr');
          break;
        case '3':
          var f=$('#tablaMesasSorteadas3Mod tbody > tr');
          break;
        default:
      }
    }

  if(drop != '' && drop < 1000000000){

      $.get('solicitudImagenes/hayCoincidencia/' + drop + '/' + id_detalle_img_bunker, function(data){

            if(data.diferencia == drop){
              hayImp=1;
            }

            //data retorna la diferencia, 0 o != 0
            if(data.diferencia == 0){

                $.each(f, function(index, value){
                    if($(this).attr('id') == id_detalle_img_bunker){
                      $(this).find('.coincide i').remove();
                      $(this).find('.coincide').append($('<i>').addClass('fa fa-fw fa-check').css('cssText','text-align:center !important;color:#4CAF50'));
                      $(this).find('.diferencias').val(data.diferencia).prop('disabled',true);

                    }
                });
            }
            if(data.diferencia != 0){

              $.each(f, function(index, value){

                  if($(this).attr('id') == id_detalle_img_bunker){

                    if(hayImp==0){
                      $(this).find('.coincide i').remove();
                      $(this).find('.coincide').append($('<i>').addClass('fas fa-fw fa-times').css('cssText','text-align:center !important;color:#EF5350'));
                      $(this).find('.diferencias').val(data.diferencia).prop('disabled',true);
                    }
                    else{
                      $(this).find('.coincide').text('No existen datos importados para esta mesa.');
                      $(this).find('.diferencias').val(data.diferencia).prop('disabled',true);
                    }

                  }
              });
            }

        })
    }
    else{
      if(drop > 1000000000){
        mostrarErrorValidacion($(this),'Valor superior al máximo',false);
      }

      $.each(f, function(index, value){

          if($(this).attr('id') == id_detalle_img_bunker){

            $(this).find('.coincide').text('');
            $(this).find('.diferencias').val('').prop('disabled',true);
        }
      });
    }

});

$(document).on('click', '.okCDs',function(e){



    //$('.continuarCarga').prop('disabled',true);

    var cantidad= $('#modalCargarDatos .cant_cds').val();
    if(cantidad > 10){
      mostrarErrorValidacion($('.cant_cds'),'Ingrese un número menor a 10.',false);
    }
    else {
      $('#tablaDatosCds tbody > tr').remove();
      for (var i = 0; i < cantidad; i++) {
        var fila=generarFilaCd(0,0);
        $('#tablaDatosCds').append(fila);
      }
      $('#tablaDatosCds').show();

    }

})

$(document).on('click', '.continuarCarga',function(e){

  var id=  $('.continuarCarga').val();

  $('#desplazarTMEsas').show();
  $('.verObs').show();

  $('#errorCdId').hide();
  $('.continuarCarga').prop('disabled',true);

  $.get('solicitudImagenes/obtenerMesas/' + id, function(data){
    var f = data.bunker.fechas;
    var y =data.bunker.mes_anio;
    var x = y.split('-');
    var f2= f.split(';');

    for (var i = 0; i < f2.length; i++) {
        if(f2[i] < 10){
          f2[i]='0' + f2[i];
        }
    }
      $('#b_1').find('.nombreF1').text(f2[0] + '/' + x[1] + '/' + x[0]);
      $('#b_2').find('.nombreF2').text(f2[1] + '/' + x[1] + '/' + x[0]);
      $('#b_3').find('.nombreF3').text(f2[2] + '/' + x[1] + '/' + x[0]);


    $('#pestaniasFechas').show();
    $('#pestaniasFechas').css('display','inline-block');
    $(".tab_content").hide(); //Hide all content
      $("ul.pestaniasFechas li:first").addClass("active").show(); //Activate first tab
      $(".tab_content:first").show(); //Show first tab content
      $('#fecha_1ver').show();


      //var ff1='';
      var fecha1 = x[0] + '-' + x[1] + '-' + f2[0];
      var fecha2 = x[0] + '-' + x[1] + '-' + f2[1];
      var fecha3 = x[0] + '-' + x[1] + '-' + f2[2];

    for (var i = 0; i < data.detalles.length; i++) {

      if(data.detalles[i].fecha == fecha1){
          var fila=generarFilaCarga(data.detalles[i],1,i);
          $('#tablaMesasSorteadas').append(fila);
        }

      if(data.detalles[i].fecha == fecha2){
          var fila1=generarFilaCarga(data.detalles[i],2,i);
          $('#tablaMesasSorteadas2').append(fila1);
      }

      if(data.detalles[i].fecha ==fecha3){
          var fila2=generarFilaCarga(data.detalles[i],3,i);
          $('#tablaMesasSorteadas3').append(fila2);
      }
    }
  })
  $('#tablaMesasSorteadas').show();

  $('#btn-guardar').show();
})

//PESTAÑAS
$("ul.pestaniasFechas li").click(function() {

    $("ul.pestaniasFechas li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content

    if(activeTab == '#fecha_2'){
      $('#fecha_2').show();
      $('#tablaMesasSorteadas2').show();
      $('#tablaMesasSorteadas').hide();
      $('#tablaMesasSorteadas3').hide();

    }
    if(activeTab == '#fecha_3'){

      $('#fecha_3').show();
      $('#tablaMesasSorteadas3').show();
      $('#tablaMesasSorteadas').hide();
      $('#tablaMesasSorteadas2').hide();

    }
    if(activeTab == '#fecha_1'){
      $('#fecha_1').show();
      $('#tablaMesasSorteadas').show();
      $('#tablaMesasSorteadas3').hide();
      $('#tablaMesasSorteadas2').hide();

    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});
//PESTAÑAS2
$("ul.pestaniasFechasVer li").click(function() {

    $("ul.pestaniasFechasVer li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content

    if(activeTab == '#fecha_2ver'){
      $('#fecha_2ver').show();
      $('#tablaMesasSorteadas2ver').show();
      $('#tablaMesasSorteadasver').hide();
      $('#tablaMesasSorteadas3ver').hide();

    }
    if(activeTab == '#fecha_3ver'){
      $('#fecha_3ver').show();
      $('#tablaMesasSorteadas3ver').show();
      $('#tablaMesasSorteadasver').hide();
      $('#tablaMesasSorteadas2ver').hide();

    }
    if(activeTab == '#fecha_1ver'){
      $('#fecha_1ver').show();
      $('#tablaMesasSorteadasver').show();
      $('#tablaMesasSorteadas3ver').hide();
      $('#tablaMesasSorteadas2ver').hide();

    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});

//PESTAÑAS3
$("ul.pestaniasFechasMod li").click(function() {

    $("ul.pestaniasFechasMod li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content

    if(activeTab == '#fecha_2mod'){
      $('#fecha_2mod').show();
      $('#tablaMesasSorteadas2Mod').show();
      $('#tablaMesasSorteadasMod').hide();
      $('#tablaMesasSorteadas3Mod').hide();

    }
    if(activeTab == '#fecha_3mod'){
      $('#fecha_3mod').show();
      $('#tablaMesasSorteadas3Mod').show();
      $('#tablaMesasSorteadasMod').hide();
      $('#tablaMesasSorteadas2Mod').hide();

    }
    if(activeTab == '#fecha_1mod'){

      $('#fecha_1mod').show();
      $('#tablaMesasSorteadasMod').show();
      $('#tablaMesasSorteadas3Mod').hide();
      $('#tablaMesasSorteadas2Mod').hide();

    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});

// //guardar carga de datos del sorteo
$('#btn-guardar').on('click',function(e){
  e.preventDefault();

    var cd=[];
    var f=$('#tablaDatosCds tbody > tr');

    $.each(f, function(index, value){

      var datos={
        nombre_cd:$(this).find('.nombreCd').val(),
        duracion_cd: $(this).find('.duracionCd').val(),
      }
      cd.push(datos);

    });
    var d=$('#tablaMesasSorteadas tbody > tr');
    var d2=$('#tablaMesasSorteadas2 tbody > tr');
    var d3=$('#tablaMesasSorteadas3 tbody > tr');

    var mesas=[];

    $.each(d, function(index, value){

      var datosMesas={
        //guardar el id_detalle_img_bunker en algun lado ..
        id_detalle_img_bunker:$(this).find('.mesaNro').attr('data-detalle'),
        identificacion:$(this).find('.idCd').val(),
        drop_visto: $(this).find('.dropMesa').val(),
        minutos_video: $(this).find('.minVideo').val(),
        diferencias:$(this).find('.diferencias').val(),
      }
      mesas.push(datosMesas);

    });
    $.each(d2, function(index, value){

      var datosMesas2={
        //guardar el id_detalle_img_bunker en algun lado ..
        id_detalle_img_bunker:$(this).find('.mesaNro').attr('data-detalle'),
        identificacion:$(this).find('.idCd').val(),
        drop_visto: $(this).find('.dropMesa').val(),
        minutos_video: $(this).find('.minVideo').val(),
        diferencias:$(this).find('.diferencias').val(),
      }
      mesas.push(datosMesas2);

    });
    $.each(d3, function(index, value){

      var datosMesas3={
        //guardar el id_detalle_img_bunker en algun lado ..
        id_detalle_img_bunker:$(this).find('.mesaNro').attr('data-detalle'),
        identificacion:$(this).find('.idCd').val(),
        drop_visto: $(this).find('.dropMesa').val(),
        minutos_video: $(this).find('.minVideo').val(),
        diferencias:$(this).find('.diferencias').val(),
      }
      mesas.push(datosMesas3);
    });


  var formData= {
    id_imagenes_bunker: $(this).val(),
    datoscd: cd,
    detalles: mesas,
    observaciones: $('#obsSorteo').val(),
  }


  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'solicitudImagenes/guardar',
      data: formData,
      dataType: 'json',

      success: function (data){

          $('#modalCargarDatos').modal('hide');
          $('#mensajeExito h3').text('EXITO!');
          $('#mensajeExito p').text('Los datos del Sorteo han sido guardados.');
          $('#mensajeExito').show();
          $('#btn-buscar-imagenes').trigger('click',[1,10,'img.created_at','desc']);

      },

      error: function (reject) {
        if( reject.status === 422 ) {
            var errors = reject.responseJSON.errors;
            $.each(errors, function (key, val) {
              var k=key.split('.');

              if(k[0] == 'datoscd'){
                console.log('entra');
                var tabl=$('#tablaDatosCds tbody > tr');

                $.each(tabl, function(index, value){
                  var dur=$(this).find('.duracionCd').val();

                  if(dur.length == 0){
                    mostrarErrorValidacion($(this).find('.duracionCd'),val[0],false);
                  }
                });
              }
              //
              if(k[0] == 'detalles'){
                var t1=cantidadFilas($('#tablaMesasSorteadas tbody > tr'));//8
                var t2=cantidadFilas($('#tablaMesasSorteadas2 tbody > tr'));//2
                var t3=cantidadFilas($('#tablaMesasSorteadas3 tbody > tr'));//8

                var fila=k[1];

              if(fila < t1){ //(10>8)
                var tabl=$('#tablaMesasSorteadas tbody > tr');
                var f=0;
              }
              if(fila >= t1 && fila < (t2+t1)){
                var tabl=$('#tablaMesasSorteadas2 tbody > tr');
                var f=t1;
              }
              if(fila >= (t2+t1) && fila < (t3+t2+t1)){
                var tabl=$('#tablaMesasSorteadas3 tbody > tr');

                var f=t2+t1;
              }

                $.each(tabl, function(index, value){

                  if(f == fila){

                    if(k[2] == 'drop_visto'){
                      mostrarErrorValidacion($(this).find('.dropMesa'),'Formato Incorrecto',false);
                    }
                    else{
                      mostrarErrorValidacion($(this).find('.minVideo'),'Campo obligatorio',false);
                    }
                  }
                    f=f+1;

                });
              }
      })

   }
 }
})
});

function cantidadFilas(tabla) {
  var count = 0;

  $.each(tabla, function(index, value){
    count=count+1;
  });
  // count=count-1;
   return count;
 };

function limpiarValidaciones(){
  //tabla cds
  var tabl=$('#tablaDatosCds tbody > tr');

  $.each(tabl, function(index, value){
    ocultarErrorValidacion($(this).find('.duracionCd'));
  });

  //tablas
  var t1=$('#tablaMesasSorteadas tbody > tr');
  var t2=$('#tablaMesasSorteadas2 tbody > tr');
  var t3=$('#tablaMesasSorteadas3 tbody > tr');

  $.each(t1, function(index, value){
    ocultarErrorValidacion($(this).find('.dropMesa'));
    ocultarErrorValidacion($(this).find('.minVideo'));

  });
  $.each(t2, function(index, value){
    ocultarErrorValidacion($(this).find('.dropMesa'));
    ocultarErrorValidacion($(this).find('.minVideo'));
  });
  $.each(t3, function(index, value){
    ocultarErrorValidacion($(this).find('.dropMesa'));
    ocultarErrorValidacion($(this).find('.minVideo'));
  });
}

$(document).on('click','.modificarSorteo', function(e){
  e.preventDefault();

  $('#tablaDatosCdsMod tbody > tr').remove();
  $('#tablaMesasSorteadasMod tbody > tr').remove();
  $('#tablaMesasSorteadas2Mod tbody > tr').remove();
  $('#tablaMesasSorteadas3Mod tbody > tr').remove();

  $('.continuarModificar').hide()
  $('.fechaSorteadaMod').val($(this).attr('data-fecha'));
  $('.casinoMod').val($(this).attr('data-casino'));
  var id=$(this).val();
  $('#btn-guardar-modificar').val(id);

  $.get('solicitudImagenes/obtenerMesas/' + id, function(data){

    for (var i = 0; i < data.cds.length; i++) {
      var fila=generarFilaCd(data.cds[i],1);
      $('#tablaDatosCdsMod').append(fila);
    }
    $('#obsSorteoMod').val(data.bunker.observaciones);

    //$('#modalModificarDatos').find('.cant_cds_mod').val(data.cds.length);

    //TABLA DE Mesas
    var f = data.bunker.fechas;
    var y =data.bunker.mes_anio;
    var x = y.split('-');
    var f2= f.split(';');

    for (var i = 0; i < f2.length; i++) {
        if(f2[i] < 10){
          f2[i]='0' + f2[i];
        }
    }
      $('#b_1_mod').find('.nombreF1').text(f2[0] + '/' + x[1] + '/' + x[0]);
      $('#b_2_mod').find('.nombreF2').text(f2[1] + '/' + x[1] + '/' + x[0]);
      $('#b_3_mod').find('.nombreF3').text(f2[2] + '/' + x[1] + '/' + x[0]);


      $('#pestaniasFechasMod').show();
      $('#pestaniasFechasMod').css('display','inline-block');
      $(".tab_content").hide(); //Hide all content
        $("ul.pestaniasFechasMod li:first").addClass("active").show(); //Activate first tab
        $(".tab_content:first").show(); //Show first tab content
        $('#fecha_1mod').show();

      //var ff1='';
      var fecha1 = x[0] + '-' + x[1] + '-' + f2[0];
      var fecha2 = x[0] + '-' + x[1] + '-' + f2[1];
      var fecha3 = x[0] + '-' + x[1] + '-' + f2[2];

    for (var i = 0; i < data.detalles.length; i++) {

      if(data.detalles[i].fecha == fecha1){
          var fila=generarFilaModificar(data.detalles[i],1,i);
          $('#tablaMesasSorteadasMod').append(fila);
        }

      if(data.detalles[i].fecha == fecha2){
          var fila1=generarFilaModificar(data.detalles[i],2,i);
          $('#tablaMesasSorteadas2Mod').append(fila1);

      }

      if(data.detalles[i].fecha ==fecha3){
          var fila2=generarFilaModificar(data.detalles[i],3,i);
          $('#tablaMesasSorteadas3Mod').append(fila2);

      }

    }
  })
  $('#tablaMesasSorteadasMod').show();
  $('#btn-guardar-modificar').show();
  $('#mensajeDatosCds').hide();
  $('#modalModificarDatos').modal('show');
})

$(document).on('click', '.agregarCd',function(e){

  var fila=generarFilaCd(0,1);
  $('#tablaDatosCdsMod').append(fila);
  $('#continuarModificar').show();
});


$(document).on('click','.btn-borrar-cd', function(e){

  var id=$(this).attr('data-id');
  var cant=0;
  console.log('dd',id);
  if(id!=undefined){
    if($(this).val() == 'carga'){

      var p=$('.cant_cds').val();
      $('.cant_cds').val(p-1);

      var f=$('#tablaDatosCds tbody > tr');
        $.each(f, function(index, value){
          console.log('entro',$(this).find('.btn-borrar-cd').attr('data-id'));
          if(($(this).find('.btn-borrar-cd').attr('data-id'))==id){
            $(this).remove();
            eliminarSelectCarga(id);
          }
        });
        var m=$('#tablaDatosCds tbody > tr');
        $.each(m, function(index, value){
            if($(this).find('.nombreCd').val() != undefined){
            cant=cant+1;
          }
        });
        console.log('cant',cant);
        if(cant == 0){
          $('.continuarCarga').hide();
          //$('.continuarCarga').prop('readonly',false);

          $('#desplazarTMEsas').hide();
          $('.verObs').hide();
          $('#btn-guardar').hide();

          $('#tablaMesasSorteadas tbody tr').remove();
          $('#tablaMesasSorteadas2 tbody tr').remove();
          $('#tablaMesasSorteadas3 tbody tr').remove();
        }


    }else{
      console.log('no entro', $(this).val());

      var f=$('#tablaDatosCdsMod tbody > tr');
        $.each(f, function(index, value){

          if(($(this).find('.btn-borrar-cd').attr('data-id'))==id){
            $(this).remove();
            eliminarSelect(id);
          }
        });
    }

  }
  else{
    $(this).parent().parent().remove();
  }
});

$(document).on('change','#identCd', function(e){
  e.preventDefault();

  $(this).prop('readonly',true);
  var f=$('#tablaDatosCdsMod tbody > tr');
    $.each(f, function(index, value){
      var t=$(this).find('.nombreCd');
      var select=$(this).find('.nombreCd').val();
      $(this).find('.btn-borrar-cd').attr('data-id',select);

      if(select != undefined && t.attr('data-agregado')==0){
        $('#tablaMesasSorteadasMod').find('.idCdMod').append($('<option>').text(select).append($('</option>')));
        $('#tablaMesasSorteadas2Mod').find('.idCdMod').append($('<option>').text(select).append($('</option>')));
        $('#tablaMesasSorteadas3Mod').find('.idCdMod').append($('<option>').text(select).append($('</option>')));
        t.attr('data-agregado',1);
      }

  });

})

$('#btn-guardar-modificar').on('click',function(e){

  e.preventDefault();

    var cd=[];

    var f=$('#tablaDatosCdsMod tbody > tr');

    $.each(f, function(index, value){

      var datos={
        nombre_cd:$(this).find('.nombreCd').val(),
        duracion_cd: $(this).find('.duracionCd').val(),
      }
      cd.push(datos);

    });
    var d=$('#tablaMesasSorteadasMod tbody > tr');
    var d2=$('#tablaMesasSorteadas2Mod tbody > tr');
    var d3=$('#tablaMesasSorteadas3Mod tbody > tr');

    var mesas=[];

    $.each(d, function(index, value){
      console.log('det',$(this).find('.mesaNro').attr('data-detalle'));

      var datosMesas={
        //guardar el id_detalle_img_bunker en algun lado ..
        id_detalle_img_bunker:$(this).find('.mesaNro').attr('data-detalle'),
        identificacion:$(this).find('.idCdMod').val(),
        drop_visto: $(this).find('.dropMesa').val(),
        minutos_video: $(this).find('.minVideo').val(),
        diferencias:$(this).find('.diferencias').val(),
      }
      mesas.push(datosMesas);

    });
    $.each(d2, function(index, value){

      var datosMesas2={
        //guardar el id_detalle_img_bunker en algun lado ..
        id_detalle_img_bunker:$(this).find('.mesaNro').attr('data-detalle'),
        identificacion:$(this).find('.idCdMod').val(),
        drop_visto: $(this).find('.dropMesa').val(),
        minutos_video: $(this).find('.minVideo').val(),
        diferencias:$(this).find('.diferencias').val(),
      }
      mesas.push(datosMesas2);

    });
    $.each(d3, function(index, value){

      var datosMesas3={
        //guardar el id_detalle_img_bunker en algun lado ..
        id_detalle_img_bunker:$(this).find('.mesaNro').attr('data-detalle'),
        identificacion:$(this).find('.idCdMod').val(),
        drop_visto: $(this).find('.dropMesa').val(),
        minutos_video: $(this).find('.minVideo').val(),
        diferencias:$(this).find('.diferencias').val(),
      }
      mesas.push(datosMesas3);
    });


  var formData= {
    id_imagenes_bunker: $(this).val(),
    datoscd: cd,
    detalles: mesas,
    observaciones: $('#obsSorteoMod').val(),
  }


  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'solicitudImagenes/guardar',
      data: formData,
      dataType: 'json',

      success: function (data){
          $('#mensajeDatosCds').hide();

          $('#modalModificarDatos').modal('hide');
          $('#mensajeExito h3').text('EXITO!');
          $('#mensajeExito p').text('Los datos del Sorteo han sido guardados.');
          $('#mensajeExito').show();
          $('#btn-buscar-imagenes').trigger('click',[1,10,'img.created_at','desc']);

      },

      error: function (data) {
        var response = data.responseJSON.errors;

          if(typeof response.datoscd !== 'undefined'){
            $('#mensajeDatosCds').show();          }

        }
   })
})


//btn ver detalles de un sorteo ya cargado
$(document).on('click','.verSorteo', function(e){
  $('#tablaMesasSorteadasver tbody > tr').remove();
  $('#tablaMesasSorteadas2ver tbody > tr').remove();
  $('#tablaMesasSorteadas3ver tbody > tr').remove();


  var id= $(this).val();

  $.get('solicitudImagenes/obtenerMesas/' + id, function(data){

    $('#fechaSorteadaVer').text(data.bunker.mes_anio);
    $('#casinoVer').text(data.bunker.nombre);

    var f = data.bunker.fechas;
    var y =data.bunker.mes_anio;
    var x = y.split('-');
    var f2= f.split(';');

    for (var i = 0; i < f2.length; i++) {
        if(f2[i] < 10){
          f2[i]='0' + f2[i];
        }
    }
      $('#b_1_ver').find('.nombreF1').text(f2[0] + '/' + x[1] + '/' + x[0]);
      $('#b_2_ver').find('.nombreF2').text(f2[1] + '/' + x[1] + '/' + x[0]);
      $('#b_3_ver').find('.nombreF3').text(f2[2] + '/' + x[1] + '/' + x[0]);


    $('#pestaniasFechasVer').show();
    $('#pestaniasFechasVer').css('display','inline-block');
    $(".tab_content").hide(); //Hide all content
    $("ul.pestaniasFechasVer li").removeClass("active");
      $("ul.pestaniasFechasVer li:first").addClass("active").show(); //Activate first tab
      $(".tab_content:first").show(); //Show first tab content
      $('#fecha_1ver').show();

      //var ff1='';
      var fecha1 = x[0] + '-' + x[1] + '-' + f2[0];
      var fecha2 = x[0] + '-' + x[1] + '-' + f2[1];
      var fecha3 = x[0] + '-' + x[1] + '-' + f2[2];

    for (var i = 0; i < data.detalles.length; i++) {

      if(data.detalles[i].fecha == fecha1){
          var fila=generarFilaVer(data.detalles[i]);
          $('#tablaMesasSorteadasver').append(fila);
        }

      if(data.detalles[i].fecha == fecha2){
          var fila1=generarFilaVer(data.detalles[i]);
          $('#tablaMesasSorteadas2ver').append(fila1);
      }

      if(data.detalles[i].fecha ==fecha3){
          var fila2=generarFilaVer(data.detalles[i]);
          $('#tablaMesasSorteadas3ver').append(fila2);
      }

      $('#obsSorteoVer').val(data.bunker.observaciones);
      $('#obsSorteoVer').prop('disabled',true);
    }
  })
  $('#tablaMesasSorteadasver').show();
  $('#modalVerDatos').modal('show');

})



function eliminarSelect(id){

  var f=$('#tablaMesasSorteadasMod tbody > tr');
  var f2=$('#tablaMesasSorteadas2Mod tbody > tr');
  var f3=$('#tablaMesasSorteadas3Mod tbody > tr');

    $.each(f, function(index, value){
        $(this).find('.idCdMod option').each(function(){
          if($(this).val()==id){
            $(this).remove();
          }
        });
      });
      //borro en la tabla2
      $.each(f2, function(index, value){
          $(this).find('.idCdMod option').each(function(){
            if($(this).val()==id){
              $(this).remove();
            }
          });
        });
        //borro en la tabla 3
        $.each(f3, function(index, value){
            $(this).find('.idCdMod option').each(function(){
              if($(this).val()==id){
                $(this).remove();
              }
            });
          });
}
function eliminarSelectCarga(id){

  var f=$('#tablaMesasSorteadas tbody > tr');
  var f2=$('#tablaMesasSorteadas2 tbody > tr');
  var f3=$('#tablaMesasSorteadas3 tbody > tr');

    $.each(f, function(index, value){
        $(this).find('.idCd option').each(function(){
          if($(this).val()==id){
            $(this).remove();
          }
        });
      });
      //borro en la tabla2
      $.each(f2, function(index, value){
          $(this).find('.idCd option').each(function(){
            if($(this).val()==id){
              $(this).remove();
            }
          });
        });
        //borro en la tabla 3
        $.each(f3, function(index, value){
            $(this).find('.idCd option').each(function(){
              if($(this).val()==id){
                $(this).remove();
              }
            });
          });
}
function generarFilaCd(data,m){
  var fila = $(document.createElement('tr'));
  if(data==0){

    fila.append($('<td>').addClass('col-xs-2').append($('<h6>').text('IDENTIFICACIÓN').css('cssText','font-size:14px')))
        .append($('<td>').addClass('col-xs-3').append($('<input>').addClass('nombreCd').addClass('form-control').css('text-align','center')))
        .append($('<td>').addClass('col-xs-2').append($('<h6>').text('DURACIÓN').css('cssText','font-size:14px')))
        .append($('<td>').addClass('col-xs-3').append($('<input>').addClass('duracion_cd duracionCd').attr('type','time').addClass('form-control')));
        if(m==0){
          fila.append($('<td>').addClass('col-xs-2').append($('<button>').addClass('btn btn-warning btn-borrar-cd').val('carga')
              .append($('<i>').addClass('fa fa-fw fa-trash'))))
        }
        else{//modificar
          fila.find('.nombreCd').attr('id','identCd').attr('data-agregado',0);
          fila.append($('<td>').addClass('col-xs-2').append($('<button>').addClass('btn btn-warning btn-borrar-cd').val('modificar')
              .append($('<i>').addClass('fa fa-fw fa-trash'))))
        }
  }
  else{
    var d = data.duracion_cd.split(':')

    var hh,mm;
     if(d.length === 3) {
      hh = d[0];
      mm = d[1];
     }
   else{
     hh = '-';
     mm = '-';
   }

    fila.append($('<td>').addClass('col-xs-2').append($('<h6>').text('IDENTIFICACIÓN').css('cssText','font-size:14px')))
        .append($('<td>').addClass('col-xs-3').append($('<input>').addClass('nombreCd').val(data.nombre_cd).addClass('form-control').css('text-align','center').prop('readonly',true)))
        .append($('<td>').addClass('col-xs-2').append($('<h6>').text('DURACIÓN').css('cssText','font-size:14px')))
        .append($('<td>').addClass('col-xs-3').append($('<input>').addClass('duracionCd').val(hh + ':' + mm).attr('type','time').addClass('form-control').prop('readonly',true)))
        if(m==0){
          fila.append($('<td>').addClass('col-xs-2').append($('<button>').addClass('btn btn-warning btn-borrar-cd')
              .attr('data-id',data.nombre_cd).append($('<i>').addClass('fa fa-fw fa-trash').val('carga'))))
        }
        else{

          fila.append($('<td>').addClass('col-xs-2').append($('<button>').addClass('btn btn-warning btn-borrar-cd')
              .attr('data-id',data.nombre_cd).append($('<i>').addClass('fa fa-fw fa-trash').val('modificar'))))
        }
  }
return fila;
}

function generarFila(data){
  var fila = $('#moldeImag').clone();
  fila.removeAttr('id');
  fila.attr('id',data.id_imagenes_bunker);
  var mes=data.mes_anio.split('-');
  var nombreMes;

  switch (mes[1]) {
    case '01':
      nombreMes='Enero';
      break;
    case '02':
      nombreMes='Febrero';
        break;
    case '03':
      nombreMes='Marzo';
        break;
    case '04':
      nombreMes='Abril';
        break;
    case '05':
      nombreMes='Mayo';
        break;
    case '06':
      nombreMes='Junio';
        break;
    case '07':
      nombreMes='Julio';
        break;
    case '08':
      nombreMes='Agosto';
        break;
    case '09':
      nombreMes='Septiembre';
        break;
    case '10':
      nombreMes='Octubre';
        break;
    case '11':
      nombreMes='Noviembre';
        break;
    default:
      nombreMes='Diciembre';

  }


  fila.find('.cloneMes').text(nombreMes + ' ' + mes[0]);

  fila.find('.cloneCasino').text(data.nombre);
  fila.find('.cloneDias').text(data.fechas);

  // <span> <i class="fas fa-fw fa-file" style="color:#0D47A1"></i></span>
  // <span> <i class="fas fa-fw fa-tasks" style="color:#FFC107" ></i></span>
  // <span> <i class="fa fa-fw fa-check" style="color:#4CAF50"></i></span

  fila.find('.cargarSorteo').val(data.id_imagenes_bunker).attr('data-fecha',data.mes_anio).attr('data-casino',data.nombre);
  fila.find('.verSorteo').val(data.id_imagenes_bunker).attr('data-fecha',data.mes_anio).attr('data-casino',data.nombre);
  fila.find('.modificarSorteo').val(data.id_imagenes_bunker).attr('data-fecha',data.mes_anio).attr('data-casino',data.nombre);

   //1:generado; 2:cargando; 3:finalizado
  switch (data.id_estado_relevamiento) {

      case 1:
         fila.find('.cloneEstado').append($('<span>').append($('<i>').addClass('fas fa-fw fa-file').append($('</i>')).append($('</h6>'))))
         fila.find('.cargarSorteo').show();
         fila.find('.verSorteo').hide();
         fila.find('.modificarSorteo').hide();

        break;

      case 2:
        fila.find('.cloneEstado').append($('<span>').append($('<i>').addClass('fas fa-fw fa-tasks').append($('</i>')).append($('</h6>'))))
        fila.find('.cargarSorteo').hide();
        fila.find('.verSorteo').show();
        fila.find('.modificarSorteo').show();

        break;

      case 3:
        fila.find('.cloneEstado').append($('<span>').append($('<i>').addClass('fa fa-fw fa-check').append($('</i>')).append($('</h6>'))))

        fila.find('.cargarSorteo').hide();
        fila.find('.verSorteo').show();
        fila.find('.modificarSorteo').hide();

        break;

      default:
        fila.find('.cargarSorteo').hide();
        fila.find('.verSorteo').show();
        fila.find('.modificarSorteo').hide();
     }

  fila.css('display','');
  $('#mostrarFila').css('display','block');

  return fila;
}

function generarFilaCarga(data,t,nro_fila){

  var fila = $(document.createElement('tr'));

  fila.attr('id',data.id_detalle_img_bunker)
      .append($('<td>').append($('<input>').addClass('col-md-2 mesaNro detalles'+nro_fila+'id_detalle_img_bunker').addClass('form-control').css('text-align','center').val(data.codigo_mesa).attr('data-detalle',data.id_detalle_img_bunker).prop('readonly',true)))
      .append($('<td>').append($('<select>').addClass('col-md-2 idCd ').addClass('form-control')))
      .append($('<td>').append($('<input>').addClass('col-md-2 dropMesa detalles'+nro_fila+'drop_visto').attr('data-tabla',t).attr('data-modificar',0).attr('data-id',data.id_detalle_img_bunker).addClass('form-control')))
      .append($('<td>').append($('<input>').addClass('col-md-2 minVideo detalles'+nro_fila+'minutos_video').attr('type','time').addClass('form-control')))
      .append($('<td>').addClass('col-md-2 coincide').addClass('form-control'))
      .append($('<td>').append($('<input>').addClass('col-md-2 diferencias').addClass('form-control').prop('readonly',true)))

    var f=$('#tablaDatosCds tbody > tr');
      $.each(f, function(index, value){

        fila.find('.idCd').append($('<option>').text($(this).find('.nombreCd').val()).append($('</option>')));

    });
  return fila;
}

function generarFilaVer(data){

  var fila = $(document.createElement('tr'));

  fila.append($('<td>').append($('<input>').addClass('col-md-2').addClass('form-control').css('text-align','center').val(data.codigo_mesa).css('text-align','center').prop('readonly',true)))
      .append($('<td>').append($('<input>').addClass('col-md-2').addClass('form-control').val(data.nombre_cd).css('text-align','center').prop('readonly',true)))
      .append($('<td>').append($('<input>').addClass('col-md-2').addClass('form-control').val(data.drop_visto).css('text-align','center').prop('readonly',true)))
      .append($('<td>').append($('<input>').addClass('col-md-3').attr('type','time').val(data.minutos_captura).addClass('form-control').css('text-align','center').prop('readonly',true)))
      .append($('<td>').append($('<input>').addClass('col-md-3').addClass('form-control').val(data.diferencias).css('text-align','center').prop('readonly',true)))

  return fila;
}

function generarFilaModificar(data,t,nro_fila){
  var hh,mm;
  if(data.minutos_captura != null){
    var d = data.minutos_captura.split(':')

     if(d.length === 3) {
      hh = d[0];
      mm = d[1];
     }
   else{
     hh = '-';
     mm = '-';
   }
  }
  else{

  }

  var fila = $(document.createElement('tr'));
  fila.attr('id',data.id_detalle_img_bunker)
  fila.append($('<td>').append($('<input>').addClass('col-md-2').addClass('form-control mesaNro').css('text-align','center').attr('data-detalle',data.id_detalle_img_bunker).prop('readonly',true).val(data.codigo_mesa)))
      .append($('<td>').append($('<select>').addClass('col-md-2 idCdMod').addClass('form-control')))
      .append($('<td>').append($('<input>').addClass('col-md-2 dropMesa detalles'+nro_fila+'drop_visto').addClass('form-control').val(data.drop_visto).attr('data-tabla',t).attr('data-id',data.id_detalle_img_bunker).attr('data-modificar',1)))
      .append($('<td>').append($('<input>').addClass('col-md-2 minVideo detalles'+nro_fila+'minutos_video').attr('type','time').val(hh + ':' + mm).addClass('form-control')))
      .append($('<td>').addClass('col-md-2 coincide').addClass('form-control').css('cssText','text-align:center !important'))
      .append($('<td>').append($('<input>').addClass('col-md-2 diferencias').addClass('form-control').val(data.diferencias).prop('disabled',true)))

    var f=$('#tablaDatosCdsMod tbody > tr');
        $.each(f, function(index, value){
          if($(this).find('.nombreCd').val()==data.nombre_cd){
            fila.find('.idCdMod').append($('<option>').text($(this).find('.nombreCd').val()).append($('</option>')).prop('selected',true));
          }
          else{
          fila.find('.idCdMod').append($('<option>').text($(this).find('.nombreCd').val()).append($('</option>')));
        }
      });

      fila.find('.idCdMod').val(data.nombre_cd).prop('selected',true);
      if(data.diferencias !=0 && data.diferencias != data.drop_visto){
        fila.find('.coincide').append($('<i>').addClass('fas fa-fw fa-times').css('cssText','text-align:center !important;color:#EF5350'));
      }
      if(data.diferencias==0){
        fila.find('.coincide').append($('<i>').addClass('fa fa-fw fa-check').css('cssText','text-align:center !important;color:#4CAF50'));
      }
      if(data.diferencias !=0 && data.diferencias == data.drop_visto){
        fila.find('.coincide').text('No hay importaciones o no se han cargado datos');
      }

  return fila;
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

/*****************PAGINACION******************/
$(document).on('click','#tablaSorteos thead tr th[value]',function(e){

  $('#tablaSorteos th').removeClass('activa');

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
  $('#tablaSorteos th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){

  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaSorteos .activa').attr('value');
  var orden = $('#tablaSorteos .activa').attr('estado');
  $('#btn-buscar-imagenes').trigger('click',[pageNumber,tam,columna,orden]);
}


function formatear(f){
  if(f != ''){
    var t=f.split('-');
    console.log(f);
    console.log('t',t);
    switch (t[1]) {
      case 'Enero':
          t.push('01');
        break;
      case 'Febrero':
          t.push('02');
          break;
      case 'Marzo':
          t.push('03');
        break;
      case 'Abril':
          t.push('04');
        break;
      case 'Mayo':
          t.push('05');
        break;
      case 'Junio':
          t.push('06');
        break;
      case 'Julio':
          t.push('07');
        break;
      case 'Agosto':
          t.push('08');
        break;
      case 'Septiembre':
          t.push('09');
        break;
      case 'Octubre':
          t.push('10');
        break;
      case 'Noviembre':
          t.push('11');
        break;
      case 'Diciembre':
          t.push('12');
        break;
      default:
        return 0;
  }
    return t;
  }
    else{
      return f;
  }

}
