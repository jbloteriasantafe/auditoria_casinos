var maquinas_seleccionadas = [];
var modificado = 0; //Indica si la isla está activa
var modificando = false; //En true para entrar a modificar una máquina

/*** OYENTES de eventos ***/
$('.modal').on('hidden.bs.modal', cierreModal);

$('#selectCasino').on('change', function(e,id_sector=0){
  var id_casino = $(this).val();
  //Borrar máquinas
  console.log(id_sector);
  $('#tablaMaquinasDeIsla tbody > tr').not('.actual').remove();
  $('#infoCambioSector').hide();

  if(id_casino == 0){
    //Deshabilitar componentes
      deshabilitarComponentesIsla();
  }else{
      $('#sector option').remove();
      $('#nro_isla').prop('readonly' , false);
      $('#sub_isla').prop('readonly', false);
      $('#inputMaquina').prop('readonly' , false);
      //generar datalist en campo de isla
      $('#nro_isla').generarDataList("islas/buscarIslaPorCasinoYNro/" + id_casino  ,'islas','id_isla','nro_isla',1);
      $('#inputMaquina').generarDataList("maquinas/obtenerMTMEnCasino/" + id_casino  ,'maquinas','id_maquina','nro_admin',2);

      // $('#maquinasEnIsla').show();
      // $('#nro_isla').setearElementoSeleccionado(0,"");
      // $('#inputMaquina').setearElementoSeleccionado(0,"");
      //busca sectores en servidor
      $.get('http://' + window.location.host +"/sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
        $('#sector').append($('<option>')
                          .val(0)
                          .text("-Seleccione un sector-")
                      )
        for (var i = 0; i < data.sectores.length; i++) {
          $('#sector').append($('<option>')
                            .val(data.sectores[i].id_sector)
                            .text(data.sectores[i].descripcion)
                        )
         $('#sector').val(id_sector)
        }
      });
  }
});
//Botones de acciones

$('#sector').on('change',function(){
    if($(this).val() !=  0 ){
      validarUbicacionIsla();
    }else {
      $('#infoCambioSector').hide();
    }
})

$('#btn-cancelarIsla').click(clickLimpiarCampos);

$('#btn-agregarIsla').click(clickAgregarIsla);

$('#btn-crearIsla').click(clickCrearIsla);
//Acciones input máquina
$('#agregarMaquina').click(clickAgregarMaquina);

$('#cancelarMaquina').click(clickCancelarMaquina);

//Acciones isla activa
$('#editarIslaActiva').click(clickEditarIsla);

$('#borrarIslaActiva').click(clickBorrarIsla);

$('#nro_isla').change(changeIsla);

$(document).on('keyup', '#nro_isla', keyupIsla);

// $(document).on('change', '#nro_isla', changeIsla);
$(document).on('click', '.borrarMaquinaIsla', borrarMaquinaIsla);

/*** FUNCIONES ***/
function mostrarIsla(casino, isla, sectores, sector){

  modificando = true;
  modificado = 0;

  if(sector == null){
    id_sector = 0 ;
    descripcion_sector= '';
  }else {
    id_sector = sector.id_sector;
    descripcion_sector = sector.descripcion;
  }
  //Llenar la tabla de isla activa
  $('#activa_datos').attr('data-isla', isla.id_isla).attr('data-casino', casino.id_casino).attr('data-sector', id_sector);
  $('#activa_nro_isla span').text(isla.nro_isla);
  var codigo = isla.codigo == null ? '' : isla.codigo;
  $('#activa_sub_isla').text(codigo);
  $('#activa_cantidad_maquinas').text(isla.cantidad_maquinas);
  $('#activa_casino').text(casino.nombre);
  $('#activa_zona').text(descripcion_sector);

  //Ocultar el plegado
  $('#tablaIslaActiva').show();
  $('#noexiste_isla').hide();
  $('#agregarIsla').hide();
  $('#islaPlegado').hide();

  //Acomodar plegado
  $('#agregarIsla').removeClass().attr('aria-expanded','true');
  $('#islaPlegado').addClass('in').attr('aria-expanded','true').css('height','auto');


  $('#selectCasino').val(casino_global).trigger('change');
}

function obtenerDatosIsla(){
  var isla = {
    id_isla: $('#activa_datos').attr('data-isla'),
    id_casino: $('#activa_datos').attr('data-casino'),
    nro_isla: $('#activa_nro_isla span').text(),
    codigo: $('#activa_sub_isla').text(),
    id_sector: $('#activa_datos').attr('data-sector'),
    modificado: modificado,
    maquinas: maquinas_seleccionadas,
  }

  return isla;
}

function recargarDatosIsla() {
      var nro_admin = $('#nro_admin').val();
      var marca = $('#marca').val();
      var modelo = $('#modelo').val();

      nro_admin = nro_admin != '' ? nro_admin : '-';
      marca = marca != '' ? marca : '-';
      modelo = modelo != '' ? modelo : '-';

      $('#tablaMaquinasDeIsla tbody tr:first td:eq(1)').text(nro_admin);
      $('#tablaMaquinasDeIsla tbody tr:first td:eq(2)').text(marca);
      $('#tablaMaquinasDeIsla tbody tr:first td:eq(3)').text(modelo);
}

//El tipo de máquina puede ser: ACTUAL, CARGADA, AGREGADA
function agregarMaquinaIsla(id_maquina, nro_admin, marca, modelo, tipo) {
  var fila = $('<tr>').attr('id', id_maquina);
  var icono = '';
  var descripcion = '';
  var accion = $('<button>').addClass('btn btn-danger borrarMaquinaIsla')
                            .append($('<i>').addClass('fa fa-fw fa-trash'));

  //Se setea el icono correcto según el tipo
  if (tipo == 'actual') {
      var icono = $('<i>').addClass('fa fa-star').css('color','#FB8C00');
      var accion = $('<span>').text('');
  }else if (tipo == 'agregada') {
      descripcion = ' Agregada';
      var icono = $('<i>').addClass('fa fa-plus').css('color','#00C853');
      fila.addClass('agregada');
  }

  //Se agregan todas las columnas para la fila
  fila.append($('<td>').append(icono).append($('<span>').css({'margin-left':'10px','color':'#aaa'}).text(descripcion)));
  nro_admin != '' ? fila.append($('<td>').text(nro_admin)) : fila.append($('<td>').text('-'));
  marca != '' ? fila.append($('<td>').text(marca)) : fila.append($('<td>').text('-'));
  modelo != '' ? fila.append($('<td>').text(modelo)) : fila.append($('<td>').text('-'));
  fila.append($('<td>').append(accion))

  //Agregar fila a la tabla
  if (tipo == 'agregada') {
    fila.insertAfter($('#tablaMaquinasDeIsla tbody #0'));
    // $('#tablaMaquinasDeIsla tbody').find('#0').after(fila);
  }
  $('#tablaMaquinasDeIsla tbody').append(fila);
}

function reiniciarSector(){//borra sectores
  $('#sector option').remove();
  $('#sector').append($('<option>').val(0).text('- Sectores del casino -'));
}

function deshabilitarComponentesIsla() {
  //Deshabilitar componentes
    $('#nro_isla').prop('readonly' , true).val('');
    $('#nro_isla').borrarDataList();
    $('#sub_isla').prop('readonly', true).val('');
    $('#inputMaquina').prop('readonly' , true).val('');
    $('#inputMaquina').borrarDataList();
    reiniciarSector();
}

/*** Manejadoras de eventos ***/
function borrarMaquinaIsla(e) {
  //Borrar máquina de la isla
  $(this).parent().parent().remove();
}

function validarUbicacionIsla(){
  var inputIsla = $('#nro_isla').val();
  var id_sector_isla = $('#nro_isla').attr('data-sector');
  var id_sector = $('#sector').val();
  if(id_sector != id_sector_isla && inputIsla != ''){
    alert("Esta cambiando la isla de sector");
    $('#infoCambioSector').show();
    //mostrar advertencia
    //mostrarMensajeAdvertencia($('#nro_isla') , "Esta isla esta asiganda a otro sector. Si continua, la isla será asignada al nuevo sector" , true);
  }else {
    $('#infoCambioSector').hide();
  }
}

function changeIsla(e) {
  var seleccionado = $('#nro_isla').obtenerElementoSeleccionado(); //id de la isla seleccionada
  var input = $('#nro_isla').val(); //Números del input

  //Vaciar tabla de máquinas menos la máquina actual y las agregadas
  $('#tablaMaquinasDeIsla tbody > tr').not('.actual').not('.agregada').remove();

  //Si se selecciona una isla existente
  if (seleccionado) {
      //Buscar las máquinas de la isla
      $.get('http://' + window.location.host +"/islas/obtenerIsla/" + seleccionado, function(data){

        $('#nro_isla').attr('data-sector' , data.sector.id_sector);

        validarUbicacionIsla();

        //Agregar las máquinas en la tabla
        for (var i = 0; i < data.maquinas.length; i++){
            agregarMaquinaIsla(data.maquinas[i].id_maquina,
                               data.maquinas[i].nro_admin,
                               data.maquinas[i].marca,
                               data.maquinas[i].modelo, 'cargada');
        }

        $('#btn-crearIsla').hide();
        $('#btn-agregarIsla').show();
        $('#btn-cancelarIsla').show();
      });
  }
  //Si se indica una isla inexistente
  else {
      $('#infoCambioSector').hide();
      $('#nro_isla').attr('data-sector' , "");

      //Si el campo está vacío esconder los botones
      if (input == '') {
        // $('#btn-crearIsla').hide();
        // $('#btn-agregarIsla').hide();
        // $('#btn-cancelarIsla').hide();
      }
      //Si se crea una nueva isla mostrar los botones
      else {
        $('#btn-crearIsla').show();
        $('#btn-agregarIsla').hide();
        $('#btn-cancelarIsla').show();
      }

  }



}

function keyupIsla(e) {
  if ($(this).val() == '') {
    $('#btn-crearIsla').hide();
    $('#btn-agregarIsla').hide();
    $('#btn-cancelarIsla').hide();
  }
}

function clickAgregarMaquina(e) {
  var id_maquina = $('#inputMaquina').attr('data-elemento-seleccionado');

  if (id_maquina != 0) {
    $.get('http://' + window.location.host +"/maquinas/obtenerMTM/" + id_maquina, function(data) {
      agregarMaquinaIsla(data.maquina.id_maquina, data.maquina.nro_admin , data.maquina.marca , data.maquina.modelo, 'agregada');
      $('#inputMaquina').setearElementoSeleccionado(0 , "");
    });
  }
}

function clickCancelarMaquina(e) {
  $('#inputMaquina').setearElementoSeleccionado(0 , "");
}

function clickLimpiarCampos(e) {
  deshabilitarComponentesIsla();
  $('#selectCasino').val(0);
  $('#tablaMaquinasDeIsla tbody > tr').not('.actual').remove();

  //Ocultar botones
  $('#btn-crearIsla').hide();
  $('#btn-agregarIsla').hide();
  $('#btn-cancelarIsla').hide();
}

function clickLimpiarCamposModalIsla(e) {
  deshabilitarComponentesIsla();
  $('#selectCasino').val(0);
  $('#tablaMaquinasDeIsla tbody > tr').not('.actual').remove();
  $('#activa_datos').attr('data-isla',''),
  $('#activa_datos').attr('data-casino',''),
  $('#activa_nro_isla span').text(''),
  $('#activa_sub_isla').text(''),
  $('#activa_datos').attr('data-sector',''),
  //Ocultar botones
  $('#btn-crearIsla').hide();
  $('#btn-agregarIsla').hide();
  $('#btn-cancelarIsla').hide();
}

function clickAgregarIsla(e) {
  modificado = 1;

  var cantidad_maquinas = $('#tablaMaquinasDeIsla tbody > tr').length;
  var id_isla = $('#nro_isla').obtenerElementoSeleccionado();
  var id_casino = $('#selectCasino').val();
  var id_sector = $('#sector').val();

  //Llenar la tabla de isla activa
  $('#activa_datos').attr('data-isla', id_isla).attr('data-casino', id_casino).attr('data-sector', id_sector);
  $('#activa_nro_isla span').text($('#nro_isla').val());
  $('#activa_sub_isla').text($('#sub_isla').val());
  $('#activa_cantidad_maquinas').text(cantidad_maquinas);
  $('#activa_casino').text($('#selectCasino option:selected').text());
  $('#activa_zona').text($('#sector option:selected').text());

  //Ocultar el plegado
  $('#tablaIslaActiva').show();
  $('#noexiste_isla').hide();
  $('#agregarIsla').hide();
  $('#islaPlegado').hide();

  //Llenar el arreglo de máquinas
  var maquinas = $('#tablaMaquinasDeIsla tbody > tr').not('.actual');

  $.each(maquinas, function(index, value){
      maquinas_seleccionadas.push($(this).attr('id'));
  });
}

function clickCrearIsla(e){
  modificado = 0;

  var cantidad_maquinas = $('#tablaMaquinasDeIsla tbody > tr').length;
  var id_isla = 0;
  var id_casino = $('#selectCasino').val();
  var id_sector = $('#sector').val();

  //Llenar la tabla de isla activa
  $('#activa_datos').attr('data-isla', id_isla).attr('data-casino', id_casino).attr('data-sector', id_sector);
  $('#activa_nro_isla span').text($('#nro_isla').val());
  $('#activa_sub_isla').text($('#sub_isla').val());
  $('#activa_cantidad_maquinas').text(cantidad_maquinas);
  $('#activa_casino').text($('#selectCasino option:selected').text());
  $('#activa_zona').text($('#sector option:selected').text());

  //Ocultar el plegado
  $('#tablaIslaActiva').show();
  $('#noexiste_isla').hide();
  $('#agregarIsla').hide();
  $('#islaPlegado').hide();

  //Llenar el arreglo de máquinas
  var maquinas = $('#tablaMaquinasDeIsla tbody > tr').not('.actual');

  $.each(maquinas, function(index, value){
      maquinas_seleccionadas.push($(this).attr('id'));
  });
}

function clickEditarIsla(e) {
  modificado = 0;
  maquinas_seleccionadas = [];

  $('#selectCasino').prop('disabled', false);

  //PARA MODIFICAR
  if (modificando) {
    //Hacer un GET y cargar los datos
    var id_isla = $('#activa_datos').attr('data-isla');

    $.get('http://' + window.location.host +"/islas/obtenerIsla/" + id_isla, function(data){
      console.log(data.sector.id_sector);

      $('#selectCasino').val(data.sector.id_casino).trigger('change', [data.sector.id_sector]);
      // $('#sector').val(data.sector.id_sector).trigger('change');
      $('#nro_isla').setearElementoSeleccionado(data.isla.id_isla, data.isla.nro_isla);
      var codigo = data.isla.codigo == null ? '' : data.isla.codigo;
      $('#sub_isla').val(data.isla.codigo);

      for (var i = 0; i < data.maquinas.length; i++){
        if($('#id_maquina').val() != data.maquinas[i].id_maquina){
          agregarMaquinaIsla(data.maquinas[i].id_maquina, data.maquinas[i].nro_admin, data.maquinas[i].marca, data.maquinas[i].modelo, "cargada");
          // agregar(data.maquinas[i].id_maquina,data.maquinas[i].nro_admin,data.maquinas[i].marca, data.maquinas[i].modelo, true);
        }
      }

      //Mostrar datos
      $('#tablaIslaActiva').hide();
      $('#noexiste_isla').text('Modificando Isla Activa').show();
      console.log('dd',$('#tituloAgregar'));
      $('#tituloAgregar').text('EDICIÓN DE ISLA ACTIVA').append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-angle-down'));
      $('#agregarIsla').show();
      $('#islaPlegado').show();

      //Mostrar botones
      $('#btn-crearIsla').hide();
      $('#btn-agregarIsla').show();
      $('#btn-cancelarIsla').show();

      modificando = false; //Una vez que entra a modificar isla ya quedan los datos guardados
    });

  }
  //PARA NUEVO
  else {
    //Mostrar datos
    $('#tablaIslaActiva').hide();
    $('#noexiste_isla').show();
    $('#agregarIsla').show();
    $('#islaPlegado').show();
  }

}

function clickBorrarIsla(e) {
  maquinas_seleccionadas = [];
  modificado = 0;

  $('#activa_datos').attr('data-isla','');
  $('#activa_datos').attr('data-casino','');
  $('#activa_datos').attr('data-sector','');

  //Vaciar los campos
  $('#btn-cancelarIsla').trigger('click');

  $('#selectCasino').prop('disabled',false);

  //Mostrar la zona de llenado de datos
  $('#tablaIslaActiva').hide();
  $('#noexiste_isla').show();
  $('#agregarIsla').show();
  $('#islaPlegado').show();

  //Ocultar botones
  $('#btn-crearIsla').hide();
  $('#btn-agregarIsla').hide();
  $('#btn-cancelarIsla').hide();

  //Acomodar plegado
  // $('#agregarIsla').removeClass().attr('aria-expanded','true');
  // $('#islaPlegado').addClass('in').attr('aria-expanded','true');
}

function cierreModal(e) {
  //se ejecuta siempre que se cierre algun modal
  //encargado de setear todo vacio

  deshabilitarComponentesIsla();
  $('#selectCasino').val(0);

  $('#tablaIslaActiva').hide();
  $('#noexiste_isla').show();


  //Vaciar tabla de máquinas menos la máquina actual
  $('#tablaMaquinasDeIsla tbody > tr').not('.actual').remove();

  //Todo esto para resetear el collapse
  $('#agregarIsla').show();
  $('#islaPlegado').show();
  $('#agregarIsla').addClass('collapsed').attr('aria-expanded', false);
  $('#islaPlegado').css('display','').attr('aria-expanded', false);
  $('#islaPlegado').collapse("hide");


  // if($('#islaPlegado').hasClass('in')){
  //   $('#agregarIsla').attr('aria-expanded', false);
  //   $('#islaPlegado').removeClass('in');
  // }



  //Ocultar botones
  $('#btn-crearIsla').hide();
  $('#btn-agregarIsla').hide();
  $('#btn-cancelarIsla').hide();
}
