var maquinas_seleccionadas = [];
var modificando = false; //En true para entrar a modificar una máquina

$('#selectCasino').on('change', function(e,id_sector=0){
  var id_casino = $(this).val();
  deshabilitarComponentesIsla();
  $('#tablaMaquinasDeIsla tbody > tr').not('.actual').remove();
  //Ocultar botones
  $('#btn-asociarIsla').hide();
  $('#btn-cancelarIsla').hide();
  $('#nro_isla').prop('readonly',true);
  $('#sub_isla').prop('readonly',true);
  if(id_casino == 0){
    $('#sector').prop('disabled',true);
  }
  else{
      //busca sectores en servidor
      $.get('http://' + window.location.host +"/sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
        $('#sector').prop('disabled',false);
        for (var i = 0; i < data.sectores.length; i++) {
          $('#sector').append($('<option>')
                            .val(data.sectores[i].id_sector)
                            .text(data.sectores[i].descripcion)
                        )
         $('#sector').val(id_sector);
        }
      });
  }
});

//Botones de acciones

$('#sector').on('change',function(){
    //generar datalist en campo de isla
    $('#nro_isla').generarDataList("movimientos/buscarIslaPorCasinoSectorYNro/" + $('#selectCasino').val() + '/' + $('#sector').val()  ,'islas','id_isla','nro_isla',1);
    $('#nro_isla').prop('readonly',false);
    $('#sub_isla').prop('readonly',false);
})

$('#btn-cancelarIsla').click(clickLimpiarCampos);

$('#btn-asociarIsla').click(clickAsociarIsla);

//Acciones isla activa
$('#editarIslaActiva').click(clickEditarIsla);

$('#borrarIslaActiva').click(clickBorrarIsla);

$('#nro_isla').change(changeIsla);


/*** FUNCIONES ***/
function mostrarIsla(casino, isla, sectores, sector){
  modificando = true;

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
  $('#asociarIsla').hide();
  $('#islaPlegado').hide();

  //Acomodar plegado
  $('#asociarIsla').removeClass().attr('aria-expanded','true');
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
      $('#selectCasino').trigger('change');
}

//El tipo de máquina puede ser: ACTUAL, CARGADA, AGREGADA
function agregarMaquinaIsla(id_maquina, nro_admin, marca, modelo, tipo) {
  var fila = $('<tr>').attr('id', id_maquina);
  var icono = '';
  var descripcion = '';
  //Se setea el icono correcto según el tipo
  if (tipo == 'actual') {
      var icono = $('<i>').addClass('fa fa-star').css('color','#FB8C00');
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

  //Agregar fila a la tabla
  if (tipo == 'agregada') {
    fila.insertAfter($('#tablaMaquinasDeIsla tbody #0'));
  }
  $('#tablaMaquinasDeIsla tbody').append(fila);
}

function reiniciarSector(){//borra sectores
  $('#sector option[value!="0"]').remove();
  $('#sector').val(0);
  $('#sector').prop('disabled',true);
}

function deshabilitarComponentesIsla() {
    //Deshabilitar componentes
    $('#nro_isla').prop('readonly' , true).val('');
    $('#nro_isla').borrarDataList();
    $('#sub_isla').prop('readonly', true).val('');
    reiniciarSector();
}

function changeIsla(e) {
  const id_casino = $('#selectCasino').val();
  const id_sector = $('#sector').val();
  const nro_isla = $('#nro_isla').val();

  //Vaciar tabla de máquinas menos la máquina actual y las agregadas
  $('#tablaMaquinasDeIsla tbody > tr').not('.actual').not('.agregada').remove();
  //Aca hacemos otro pedido a la base de datos porque el datalist te guarda el ultimo seleccionado entonces
  //Quedaba bugeado (solo en el fronttend) isla 219381987321 con id_isla correcto
  $.get('http://' + window.location.host + "/islas/obtenerIsla/" + id_casino + '/' + id_sector + '/' + nro_isla, function (data) {
    if (data.length == 0) {//Nro isla invalido
      $('#tablaMaquinasDeIsla tbody > tr').not('.actual').remove();
      $('#nro_isla').attr('data-sector', "");
      $('#btn-asociarIsla').hide();
      $('#btn-cancelarIsla').show();
    }
    else {
      $('#nro_isla').attr('data-sector', data.sector.id_sector);
      //Agregar las máquinas en la tabla
      for (var i = 0; i < data.maquinas.length; i++) {
        agregarMaquinaIsla(data.maquinas[i].id_maquina,
          data.maquinas[i].nro_admin,
          data.maquinas[i].marca,
          data.maquinas[i].modelo, 'cargada');
      }

      $('#btn-asociarIsla').show();
      $('#btn-cancelarIsla').show();
    }
  });
}

function clickLimpiarCampos(e) {
  clickBorrarIsla();
}

function limpiarModalIsla() {
  clickBorrarIsla();
}

function clickAsociarIsla(e) {
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
  $('#asociarIsla').hide();
  $('#islaPlegado').hide();

  //Llenar el arreglo de máquinas
  var maquinas = $('#tablaMaquinasDeIsla tbody > tr').not('.actual');

  $.each(maquinas, function(index, value){
      maquinas_seleccionadas.push($(this).attr('id'));
  });
}
function clickEditarIsla(e) {
  maquinas_seleccionadas = [];

  //PARA MODIFICAR
  if (modificando) {
    //Hacer un GET y cargar los datos
    var id_isla = $('#activa_datos').attr('data-isla');

    $.get('http://' + window.location.host +"/islas/obtenerIsla/" + id_isla, function(data){
      console.log(data.sector.id_sector);

      $('#selectCasino').val(data.sector.id_casino).trigger('change', [data.sector.id_sector]);
      $('#nro_isla').setearElementoSeleccionado(data.isla.id_isla, data.isla.nro_isla);
      var codigo = data.isla.codigo == null ? '' : data.isla.codigo;
      $('#sub_isla').val(data.isla.codigo);

      for (var i = 0; i < data.maquinas.length; i++){
        if($('#id_maquina').val() != data.maquinas[i].id_maquina){
          agregarMaquinaIsla(data.maquinas[i].id_maquina, data.maquinas[i].nro_admin, data.maquinas[i].marca, data.maquinas[i].modelo, "cargada");
        }
      }

      //Mostrar datos
      $('#tablaIslaActiva').hide();
      $('#noexiste_isla').text('Modificando Isla Activa').show();
      console.log('dd',$('#tituloAgregar'));
      $('#tituloAgregar').text('EDICIÓN DE ISLA ACTIVA').append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-angle-down'));
      $('#asociarIsla').show();
      $('#islaPlegado').show();

      //Mostrar botones
      $('#btn-asociarIsla').show();
      $('#btn-cancelarIsla').show();

      modificando = false; //Una vez que entra a modificar isla ya quedan los datos guardados
    });

  }
  //PARA NUEVO
  else {
    //Mostrar datos
    $('#tablaIslaActiva').hide();
    $('#noexiste_isla').show();
    $('#asociarIsla').show();
    $('#islaPlegado').show();
  }
}

function clickBorrarIsla(e) {
  maquinas_seleccionadas = [];

  $('#activa_datos').attr('data-isla','');
  $('#activa_datos').attr('data-casino','');
  $('#activa_datos').attr('data-sector','');
  $('#activa_nro_isla span').text('');
  $('#activa_sub_isla').text('');
  $('#activa_cantidad_maquinas').text('');
  $('#activa_casino').text('');
  $('#activa_zona').text('');

  //Vaciar los campos
  deshabilitarComponentesIsla();
  $('#tablaMaquinasDeIsla tbody > tr').not('.actual').remove();

  //Ocultar botones
  $('#btn-asociarIsla').hide();
  $('#btn-cancelarIsla').hide();
  $('#selectCasino').trigger('change');

  //Mostrar la zona de llenado de datos
  $('#tablaIslaActiva').hide();
  $('#noexiste_isla').show();
  $('#asociarIsla').show();
  $('#islaPlegado').show();

  //Ocultar botones
  $('#btn-asociarIsla').hide();
  $('#btn-cancelarIsla').hide();
}
