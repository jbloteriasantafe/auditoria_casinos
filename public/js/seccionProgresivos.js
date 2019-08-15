/****************EVENTOS DEL DOM***********/


$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionarMTM').removeClass();
  $('#gestionarMTM').addClass('subMenu2 collapse in');

  $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Progresivos');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcProgresivos').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcProgresivos').addClass('opcionesSeleccionado');

  $('#btn-buscar').trigger('click');
});


//Busqueda
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

  var formData = {
    nombre_progresivo: $('#B_nombre_progresivo').val(),
    id_casino: $('#busqueda_casino').val(),
    page: page_number,
    sort_by: 'nombre',
    page_size: page_size,
  };

  $.ajax({
      type: 'POST',
      url: 'progresivos/buscarProgresivos',
      data: formData,
      dataType: 'json',
      success: function (resultados) {
          console.log(resultados);
          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
          $('#cuerpoTabla tr').remove();
          for (var i = 0; i < resultados.data.length; i++){
              var filaProgresivo = generarFilaTabla(resultados.data[i]);
              $('#cuerpoTabla').append(filaProgresivo);
          }
          $('#herramientasPaginacion').generarIndices(
            page_number,
            page_size,
            resultados.total,
            clickIndice);

      },
      error: function (data) {
          console.log('Error:', data);
      }
    });

  function callbackMaquinas(resultados){
    let maquinas_lista = $('#maquinas_lista');
    maquinas_lista.empty();
    let option = $('<option></option>');
    for (var i=0; i < resultados.length; i++){
      let fila = option.clone().attr('value',resultados[i].nombre)
      .attr('data-id',resultados[i].id)
      .attr('data-isla',resultados[i].isla)
      .attr('data-sector',resultados[i].sector)
      .attr('data-nro_admin',resultados[i].nro_admin)
      .attr('data-marca_juego',resultados[i].marca_juego);
      maquinas_lista.append(fila);
    };
  }

  let ajaxData = {
    type: 'GET',
    url: 'progresivos/buscarMaquinas/'+$('#busqueda_casino').val()
  };

  $.when($.ajax(ajaxData))
  .then(callbackMaquinas, function(err){console.log(err)});
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| PROGRESIVOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');
});

//Mostrar modal para agregar nuevo Progresivo
$('#btn-nuevo').click(function(e){
    e.preventDefault();
    $('#btn-cancelar').text('CANCELAR');
    $('#btn-guardar').val("nuevo");
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('.modal-title').text('| NUEVO PROGRESIVO');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
    $('#modalProgresivo').modal('show');
    mostrarProgresivo({id_progresivo: -1,nombre: '',porc_recup: 0},[],[],true);
});

// Modal crear nuevo progresivo individual
$('#btn-nuevo-ind').click(function(e){
  e.preventDefault();
  $('#btn-cancelar').text('CANCELAR');
  $('#btn-guardar').val("nuevo");
  $('#btn-guardar').removeClass();
  $('#btn-guardar').addClass('btn btn-successAceptar');
  $('.modal-title').text('| NUEVOS PROGRESIVOS INDIVIDUALES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
  nuevoProgresivoIndividual();
});

function filaEjemploIndividual(){
  return $('.tablaMaquinasDivIndividual').find('.filaEjemplo')
  .clone().removeClass('filaEjemplo');
}


function filaEditableIndividual(){
  let fila = filaEjemploIndividual();
  let input = crearEditable('text').attr('list','maquinas_lista')
  let fila_nroadmin = fila.find('.cuerpoTablaNroAdmin').empty().append(input);
  let fila_sector = fila.find('.cuerpoTablaSector').empty();
  let fila_isla = fila.find('.cuerpoTablaIsla').empty();
  let fila_marcajuego = fila.find('.cuerpoTablaMarcaJuego').empty();

  function agregarClickCallback(dom,input){
    input.addClass(dom.attr('class'));
    dom.on('click',function(){
      dom.empty().append(input);
      dom.off();
    });
  }

  //No puedo agregarle un editable de numeros con flechas
  //porque las flechas son muy grandes.

  const input_porcentaje = crearEditable('number','0').addClass('sinflechas');
  const input_numero = crearEditable('number','',0,null,'any').addClass('sinflechas');
  let fila_porcrecup = fila.find('.cuerpoPorcRecup')
  .empty().append(input_porcentaje.clone().val($('#inputPorcRecupIndividual').val()));

  let fila_maximo = fila.find('.cuerpoMaximo')
  .empty().append(input_numero.clone().val($('#inputMaximoIndividual').val()));

  let fila_base = fila.find('.cuerpoBase')
  .empty().append(input_numero.clone().val($('#inputBaseIndividual').val()));

  let fila_porcvisible = fila.find('.cuerpoPorcVisible')
  .empty().append(input_porcentaje.clone().val($('#inputPorcVisibleIndividual').val()));

  let fila_porcoculto = fila.find('.cuerpoPorcOculto')
  .empty().append(input_porcentaje.clone().val($('#inputPorcOcultoIndividual').val()));

  let botonConfirmar = crearBoton('fa-check').addClass('confirmar').on('click',function(){
    const fila_porcrecup_val = fila_porcrecup.find('.editable').val();
    const fila_maximo_val = fila_maximo.find('.editable').val();
    const fila_base_val = fila_base.find('.editable').val();
    const fila_porcoculto_val = fila_porcoculto.find('.editable').val();
    const fila_porcvisible_val = fila_porcvisible.find('.editable').val();
    if(isNaN(fila_porcrecup_val)) return;
    if(isNaN(fila_maximo_val)) return;
    if(isNaN(fila_base_val)) return;
    if(isNaN(fila_porcoculto_val)) return;
    if(isNaN(fila_porcvisible_val)) return;
    if(fila_maximo_val < 0) return;
    if(fila_base_val < 0) return;
    if(fila_porcrecup_val<0
    || fila_porcrecup_val>100) return;
    if(fila_porcoculto_val<0
    || fila_porcoculto_val>100) return;
    if(fila_porcvisible_val<0
    || fila_porcvisible_val>100) return;

    let value = input.val();
    let data =  $('#maquinas_lista')
    .find('option[value='+value+']');

    if(data.length == 0) return;

    let data_id = data.attr('data-id');
    let nro_admin = data.attr('data-nro_admin');
    let sector = data.attr('data-sector');
    let isla = data.attr('data-isla');
    let marca_juego = data.attr('data-marca_juego');

    fila.attr('data-id',data_id);
    fila_nroadmin.empty().append(nro_admin);
    fila_sector.append(sector);
    fila_isla.append(isla);
    fila_marcajuego.append(marca_juego);

    fila.find('input').each(function(index,c){
      $(c).replaceWith($(c).val());
    });

    fila.children().each(function(index,c){
      $(c).off();//Saco eventos click.
    })

    fila.find('.cuerpoTablaAcciones').empty();

    let botonBorrar = crearBoton('fa-trash').addClass('borrar');
    fila.find('.cuerpoTablaAcciones').append(botonBorrar);

    botonBorrar.on('click',function(){fila.remove();});
  });

  let botonCancelar = crearBoton('fa-times').addClass('cancelar');
  botonCancelar.on('click',function(){
    fila.remove();
  });

  fila.find('.cuerpoTablaAcciones')
  .empty().append(botonConfirmar).append(botonCancelar);
  return fila
}

function nuevoProgresivoIndividual(){
  $('#modalProgresivoIndividual').modal('show');
  $('#contenedorMaquinasIndividual').empty();
  $('#inputPorcRecupIndividual').val(0);
  $('#inputMaximoIndividual').val(0);
  $('#inputBaseIndividual').val(0);
  $('#inputPorcVisibleIndividual').val(0);
  $('#inputPorcOcultoIndividual').val(0);

  let maq_html = $('.tablaMaquinasDivIndividual').clone().removeClass('ejemplo').show();
  let cuerpo_tabla = maq_html.find('.cuerpoTabla').empty();
  $('#contenedorMaquinasIndividual').append(maq_html);
  $('#btn-agregarMaquinaIndividual').off().on('click',function(){
    cuerpo_tabla.append(filaEditableIndividual());
  });
}

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){
      $('.modal-title').text('| VER MÁS');
      $('.modal-header').attr('style','font-family: Roboto-Black; background: #4FC3F7');
      $('.btn-agregarNivelProgresivo').hide();
      $('#btn-cancelar').text('SALIR');

      var id_progresivo = $(this).val();

      $.get("progresivos/obtenerProgresivo/" + id_progresivo, function(data){
          console.log(data);
          mostrarProgresivo(data.progresivo,data.pozos,data.maquinas,false);
          $('#modalProgresivo').modal('show');
      });
});

//Mostrar modal con los datos del Juego cargado
$(document).on('click','.modificar',function(){
      $('#mensajeExito').hide();
      $('#btn-cancelar').text('CANCELAR');
      $('.btn-agregarNivelProgresivo').show();
      $('.modal-title').text('| MODIFICAR PROGRESIVO');
      $('.modal-header').attr('style','font-family: Roboto-Black; background: #ff9d2d');
      $('#btn-guardar').removeClass();
      $('#btn-guardar').addClass('btn btn-warningModificar');

      var id_progresivo = $(this).val();

      $.get("progresivos/obtenerProgresivo/" + id_progresivo, function(data){
          mostrarProgresivo(data.progresivo,data.pozos,data.maquinas,true);
          console.log('niveles' , data.niveles);
          $('#btn-guardar').val("modificar");
          $('#modalProgresivo').modal('show');
      });
});

//Borrar Progresivo y remover de la tabla
$(document).on('click','.eliminar',function(){
      //Cambiar colores modal
      $('.modal-title').text('ADVERTENCIA');
      $('.modal-header').removeAttr('style');
      $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

      var id_progresivo = $(this).val();
      $('#btn-eliminarModal').val(id_progresivo);
      $('#modalEliminar').modal('show');
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
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
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (isNaN(tam)) ?  $('#herramientasPaginacion').getPageSize() : tam;
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(progresivo){
    var fila = $(document.createElement('tr'));
    fila.attr('id','progresivo' + progresivo.id_progresivo)
    .append($('<td>')
            .addClass('col-xs-6')
            .addClass('nombre')
            .text(progresivo.nombre)
    )
    .append($('<td>')
          .addClass('col-xs-6')
          .append($('<button>')
              .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
              )
              .append($('<span>').text(' VER MÁS'))
              .addClass('btn').addClass('btn-info').addClass('detalle')
              .attr('value',progresivo.id_progresivo)
          )
          .append($('<span>').text(' '))
          .append($('<button>')
              .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
              )
              .append($('<span>').text(' MODIFICAR'))
              .addClass('btn').addClass('btn-warning').addClass('modificar')
              .attr('value',progresivo.id_progresivo)
          )
          .append($('<span>').text(' '))
          .append($('<button>')
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
              )
              .append($('<span>').text(' ELIMINAR'))
              .addClass('btn').addClass('btn-danger').addClass('eliminar')
              .attr('value',progresivo.id_progresivo)
          )
      )

      fila.find('.eliminar').off().on('click',function(){
        $('.modal-title').text('ADVERTENCIA');
        $('.modal-header').removeAttr('style');
        $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

        $('#btn-eliminarModal').val(id_progresivo);
        $('#modalEliminar').modal('show');
        $('#btn-eliminarModal').off().on('click',function(){
          $.ajaxSetup({
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
              }
          })
          $.ajax({
              type: "DELETE",
              url: "progresivos/eliminarProgresivo/" + progresivo.id_progresivo,
              success: function (data) {
                console.log(data);
                fila.remove();
                $("#tablaResultados").trigger("update");
                $('#modalEliminar').modal('hide');
              },
              error: function (data) {
                console.log('Error: ', data);
              }
          })
        });
      });

      return fila;
}

function crearBoton(icono){
  let btn = $('<button></button>').addClass('btn').addClass('btn-info');
  let i = $('<i></i>').addClass('fa').addClass('fa-fw').addClass(icono);
  btn.append(i);
  return btn;
}

function crearEditable(tipo,
  defecto="",
  min=0,
  max=100,
  step=0.001){
  return $('<input></input>')
  .addClass('editable')
  .addClass('form-control')
  .attr('type',tipo)
  .attr('min',min)
  .attr('max',max)
  .attr('step',step)
  .val(defecto);
}

function filaEjemplo(){
  return $('.tablaPozoDiv.ejemplo').find('.filaEjemplo').clone().removeClass('filaEjemplo').show();
}
function filaEjemploMaquina(){
  return $('.tablaMaquinasDiv.ejemplo').find('.filaEjemplo').clone().removeClass('filaEjemplo').show();
}

function setearValoresFilaNivel(fila,nivel,fila_es_editable=false){
  fila.attr('data-id',nivel.id_nivel_progresivo);

  if(!fila_es_editable){
    fila.find('.cuerpoTablaPozoNumero').text(nivel.nro_nivel);
    fila.find('.cuerpoTablaPozoNombre').text(nivel.nombre_nivel);
    fila.find('.cuerpoTablaPozoBase').text(nivel.base);
    fila.find('.cuerpoTablaPozoMaximo').text(nivel.maximo);
    fila.find('.cuerpoTablaPorcVisible').text(nivel.porc_visible);
    fila.find('.cuerpoTablaPorcOculto').text(nivel.porc_oculto);
  }
  else{
    fila.find('.cuerpoTablaPozoNumero .editable').val(nivel.nro_nivel);
    fila.find('.cuerpoTablaPozoNombre .editable').val(nivel.nombre_nivel);
    fila.find('.cuerpoTablaPozoBase .editable').val(nivel.base);
    fila.find('.cuerpoTablaPozoMaximo .editable').val(nivel.maximo);
    fila.find('.cuerpoTablaPorcVisible .editable').val(nivel.porc_visible);
    fila.find('.cuerpoTablaPorcOculto .editable').val(nivel.porc_oculto);
  }
}

function crearFilaEditableNivel(valores = { id_nivel_progresivo : -1 }){
  let fila = filaEjemplo();

  fila.find('.cuerpoTablaPozoNumero').empty().append(crearEditable('number','',0,null,'any'))
  fila.find('.cuerpoTablaPozoNombre').empty().append(crearEditable("text"));
  fila.find('.cuerpoTablaPozoBase').empty().append(crearEditable("number","0",0,null,"any"));
  fila.find('.cuerpoTablaPozoMaximo').empty().append(crearEditable("number","0",0,null,"any"));
  fila.find('.cuerpoTablaPorcVisible').empty().append(crearEditable("number","0"));
  fila.find('.cuerpoTablaPorcOculto').empty().append(crearEditable("number","0"));
  fila.find('.editar').remove();
  fila.find('.cuerpoTablaPozoAcciones').empty();
  fila.find('.cuerpoTablaPozoAcciones').append(crearBoton('fa-check').addClass('confirmar'));
  fila.find('.cuerpoTablaPozoAcciones').append(crearBoton('fa-times').addClass('cancelar'));

  setearValoresFilaNivel(fila,valores,true);

  fila.find('.confirmar').on('click',function(){
    let numero = fila.find('.cuerpoTablaPozoNumero .editable').val();
    let nombre = fila.find('.cuerpoTablaPozoNombre .editable').val();
    let base = fila.find('.cuerpoTablaPozoBase .editable').val();
    let maximo = fila.find('.cuerpoTablaPozoMaximo .editable').val();
    let porc_visible = fila.find('.cuerpoTablaPorcVisible .editable').val();
    let porc_oculto = fila.find('.cuerpoTablaPorcOculto .editable').val();
    let valido =  numero != '';
    valido = valido && (nombre != '');
    valido = valido && (base >= 0);
    valido = valido && (maximo >= 0);
    valido = valido && (porc_visible >= 0) && (porc_visible <= 100);
    valido = valido && (porc_oculto >= 0) && (porc_oculto <= 100);
    if(valido) modificarNivel(fila);
  });

  fila.find('.cancelar').on('click',function(){
    let nueva_fila = filaEjemplo();
    setearValoresFilaNivel(nueva_fila,valores);

    nueva_fila.find('.cuerpoTablaPozoAcciones .editar').on('click',function(){
      let fila_editable = crearFilaEditableNivel(valores);
      nueva_fila.replaceWith(fila_editable);
    });

    nueva_fila.find('.cuerpoTablaPozoAcciones .borrar').on('click',function(){
      nueva_fila.remove();
    });

    fila.replaceWith(nueva_fila);
  });

  return fila;
}


function modificarNivel(fila){
  fila.find('.editable').each(function(index,child){
    let val = $(child).val();
    $(child).removeClass('editable');
    $(child).replaceWith(val);
  });

  fila.find('.confirmar').replaceWith(crearBoton('fa-pencil-alt').addClass('editar'));
  fila.find('.cancelar').replaceWith(crearBoton('fa-trash-alt').addClass('borrar'));

  fila.find('.editar').on('click',function(){
    let valores = arregloNivel(fila);
    let fila_editable = crearFilaEditableNivel(valores);
    fila.replaceWith(fila_editable);
  });

  fila.find('.cuerpoTablaPozoAcciones .borrar').on('click',function(){
    fila.remove();
  });

  fila.parent().parent().parent().find('.agregar').attr('disabled',false);
}

function limpiarNullsNivel(nivel){
  function limpiarNull(val){
    return val == null? '' : val;
  }
  return {
    id_nivel_progresivo : limpiarNull(nivel.id_nivel_progresivo),
    nro_nivel :  limpiarNull(nivel.nro_nivel),
    nombre_nivel :  limpiarNull(nivel.nombre_nivel),
    base :  limpiarNull(nivel.base),
    porc_oculto :  limpiarNull(nivel.porc_oculto),
    porc_visible :  limpiarNull(nivel.porc_visible),
    maximo :  limpiarNull(nivel.maximo)
  };
}

function mostrarPozo(id_pozo,nombre,editable,niveles = {}){
  let pozo_html = $('.tablaPozoDiv.ejemplo').clone().removeClass('ejemplo');
  pozo_html.find('.nombrePozo').text(nombre);
  $('#contenedorPozos').append(pozo_html);
  pozo_html.show();

  pozo_html.attr('data-id',id_pozo);

  pozo_html.find('.filaEjemplo').remove();

  let fila_ejemplo_pozo = filaEjemplo();
  for (var j = 0; j < niveles.length; j++) {
    let fila = fila_ejemplo_pozo.clone();

    const nivel = limpiarNullsNivel(niveles[j]);

    setearValoresFilaNivel(fila,nivel);

    fila.find('.cuerpoTablaPozoAcciones').children().each(
      function (index,child){
        $(child).attr('disabled',!editable);
    });

    fila.find('.cuerpoTablaPozoAcciones .editar').on('click',function(){
      let fila_editable = crearFilaEditableNivel(nivel);
      fila.replaceWith(fila_editable);
    });

    fila.find('.cuerpoTablaPozoAcciones .borrar').on('click',function(){
      fila.remove();
    });

    pozo_html.find('.cuerpoTablaPozo').append(fila);
  }

  const editarPozoCallback = function(){
    let text_viejo = pozo_html.find('.nombrePozo').text();
    pozo_html.find('.nombrePozo').replaceWith(
      crearEditable('text')
      .addClass('nombrePozo')
      .val(text_viejo)
    );

    let boton = crearBoton('fa-check')
    .addClass('confirmarPozo')
    .removeClass('btn-info')
    .addClass('btn-link');
    pozo_html.find('.editarPozo').replaceWith(boton);

    const confirmarPozoCallback = function(){
      let valorModif = pozo_html.find('.nombrePozo').val();
      let text = $('<b></b>').text(valorModif);

      text.addClass('nombrePozo');
      pozo_html.find('.nombrePozo').replaceWith(text);

      let boton2 = crearBoton('fa-pencil-alt')
      .addClass('editarPozo')
      .removeClass('btn-info')
      .addClass('btn-link');

      pozo_html.find('.confirmarPozo').replaceWith(boton2);
      boton2.on('click',editarPozoCallback);
    };

    boton.on('click',confirmarPozoCallback);
  };

  pozo_html.find('.editarPozo').attr('disabled',!editable);
  pozo_html.find('.editarPozo').on('click',editarPozoCallback);
  pozo_html.find('.eliminarPozo').attr('disabled',!editable);
  pozo_html.find('.eliminarPozo').on('click',function(){
      pozo_html.remove();
  });

  pozo_html.find('.collapse').on('show.bs.collapse',function(){
    let icono = pozo_html.find('.abrirPozo i');
    let icono_nuevo = $('<i></i>').addClass('fa').addClass('fa-fw');
    icono.replaceWith(icono_nuevo.addClass('fa-angle-down'));
  });

  pozo_html.find('.collapse').on('hide.bs.collapse',function(){
    let icono = pozo_html.find('.abrirPozo i');
    let icono_nuevo = $('<i></i>').addClass('fa').addClass('fa-fw');
    icono.replaceWith(icono_nuevo.addClass('fa-angle-up'));
  });

  pozo_html.find('.abrirPozo').on('click',function(){
      let colapsable = pozo_html.find('.collapse');
      colapsable.collapse('toggle');
  });

  pozo_html.find('.agregar').attr('disabled',!editable);
  pozo_html.find('.agregar').on("click",function(){
    let fila = crearFilaEditableNivel();
    pozo_html.find('.cuerpoTablaPozo').append(fila);
    $(this).attr('disabled',true);
  });

}

function arregloNivel(fila){
  let nivel = {
    id_nivel_progresivo : fila.attr('data-id'),
    nro_nivel : fila.find('.cuerpoTablaPozoNumero').text(),
    nombre_nivel : fila.find('.cuerpoTablaPozoNombre').text(),
    base : fila.find('.cuerpoTablaPozoBase').text(),
    porc_oculto : fila.find('.cuerpoTablaPorcOculto').text(),
    porc_visible : fila.find('.cuerpoTablaPorcVisible').text(),
    maximo : fila.find('.cuerpoTablaPozoMaximo').text()
  };
  return nivel;
}

function arregloPozos(){
  const pozos_html = $('.tablaPozoDiv').not('.ejemplo');

  let ret = [];

  for(i = 0;i<pozos_html.length;i++){
    const pozo_html = $(pozos_html[i]);
    const id_pozo = pozo_html.attr('data-id');
    const descripcion = pozo_html.find('.nombrePozo').text();

    let filas = [];

    pozo_html.find('tbody tr').each(function(idx,c){
      filas.push(arregloNivel($(c)));
    });

    const data = {
      id_pozo : id_pozo,
      descripcion : descripcion,
      niveles : filas
    };

    ret.push(data);
  }

  return ret;
}
function arregloMaquinas(){
  const maq_html = $($('.tablaMaquinasDiv').not('.ejemplo').first());
  let ret = [];

  maq_html.find('tbody tr').each(function(idx,c){
    let fila = $(c);
    ret.push({
      id_maquina : fila.attr('data-id'),
      nro_admin : fila.find('.cuerpoTablaNroAdmin').text(),
      nro_isla :  fila.find('.cuerpoTablaIsla').text(),
      sector_descripcion : fila.find('.cuerpoTablaSector').text(),
      marca_juego : fila.find('.cuerpoTablaMarcaJuego').text()
    });
  });

  return ret;
}

function mostrarProgresivo(progresivo,pozos,maquinas,editable){
    $('#modalProgresivo_casino').attr('disabled',progresivo.id_progresivo != -1);
    $('#modalProgresivo_casino').val(progresivo.id_casino);
    $('#id_progresivo').val(progresivo.id_progresivo);
    $('#nombre_progresivo').val(progresivo.nombre);
    $('#nombre_progresivo').attr('disabled',!editable);
    $('#porc_recup').val(progresivo.porc_recup);
    $('#porc_recup').attr('disabled',!editable);
    $('#contenedorPozos').empty();
    $('#contenedorMaquinas').empty();
    $('#btn-agregarPozo').attr('disabled',!editable).off();
    $('#btn-agregarPozo').on('click',function(){
        mostrarPozo(-1,'Pozo',editable);
    });

    for (var i = 0; i < pozos.length; i++){
      mostrarPozo(pozos[i].id_pozo,pozos[i].descripcion,editable,pozos[i].niveles);
    }

    $('.abrirPozo').first().trigger('click');

    llenarTablaMaquinas(maquinas,editable);

    $('#btn-guardar').attr('disabled',!editable).off();

    $('#btn-guardar').on('click',function(){
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
          }
      })
      let mensajeExito = 'El progresivo fue modificado con éxito.';
      let url = 'progresivos/modificarProgresivo/'+progresivo.id_progresivo;


      let formData = {
        id_progresivo : progresivo.id_progresivo,
        id_casino : $('#modalProgresivo_casino').val(),
        nombre : $('#nombre_progresivo').val(),
        porc_recup :  $('#porc_recup').val(),
        pozos : arregloPozos(),
        maquinas : arregloMaquinas(),
      };

      if(progresivo.id_progresivo == -1){
        mensajeExito = 'El progresivo fue creado con éxito.';
        url = 'progresivos/crearProgresivo';
      }

      $.ajax({
          type: 'POST',
          data: formData,
          url: url,
          success: function(data){
            console.log(data);
            let fila = $('#cuerpoTabla').find('#progresivo'+progresivo.id_progresivo);
            fila.find('.nombre').text(formData.nombre);
            $('#mensajeExito')
            .find('.textoMensaje p')
            .replaceWith(
              $('<p></p>')
              .text(mensajeExito)
            );
            $('#modalProgresivo').modal('hide');
            $('#mensajeExito').show();
          },
          error: function(err){
            $('#mensajeError').find('.textoMensaje p')
            .replaceWith($('<p></p>').text('INSERTAR ERROR'));
            $('#mensajeError').show();
            console.log(err);
          }
      });
    });

}

function setearFilaMaquinas(fila,id,nro_admin,sector,isla,marca_juego){
  fila.find('.cuerpoTablaNroAdmin').text(nro_admin);
  fila.find('.cuerpoTablaSector').text(sector)
  fila.find('.cuerpoTablaIsla').text(isla);
  fila.find('.cuerpoTablaMarcaJuego').text(marca_juego);
  fila.attr('data-id',id);
  fila.find('.unlink').on('click',function(){fila.remove()});
}

function existeEnTablaMaquinas(dataid){
  let tabla =  $('.tablaMaquinasDiv').not('.ejemplo');
  return tabla.find('tbody tr[data-id="'+dataid+'"]').length != 0;
}

function filaEditableMaquina(){
  let fila = filaEjemploMaquina();
  let input = $('<input></input>')
  .addClass('form-control')
  .addClass('editable')
  .attr('list','maquinas_lista');

  setearFilaMaquinas(fila,'','','','','')

  fila.find('.cuerpoTablaNroAdmin').replaceWith(input);
  fila.find('.cuerpoTablaAcciones').empty();
  fila.find('.cuerpoTablaAcciones').append(crearBoton('fa-check').addClass('confirmar'));
  fila.find('.cuerpoTablaAcciones').append(crearBoton('fa-times').addClass('cancelar'));

  fila.find('.cancelar').on('click',function(){
    fila.remove();
  });

  fila.find('.confirmar').on('click',function(){
    let filaCompleta = filaEjemploMaquina();
    let value = input.val();
    let data =  $('#maquinas_lista')
    .find('option[value='+input.val()+']');

    if(data.length == 0) return;

    let data_id = data.attr('data-id');
    let nro_admin = data.attr('data-nro_admin');
    let sector = data.attr('data-sector');
    let isla = data.attr('data-isla');
    let marca_juego = data.attr('data-marca_juego');
    if(existeEnTablaMaquinas(data_id)){
      fila.remove();
    }
    else{
      setearFilaMaquinas(filaCompleta,data_id,nro_admin,sector,isla,marca_juego);
      fila.replaceWith(filaCompleta);
    }

  });

  return fila;
}

function llenarTablaMaquinas(maquinas,editable){
  let maq_html = $('.tablaMaquinasDiv.ejemplo').clone().removeClass('ejemplo');
  $('#contenedorMaquinas').append(maq_html);
  maq_html.show();

  $('#btn-agregarMaquina').attr('disabled',!editable).off();
  $('#btn-agregarMaquina').on('click', function() {
    maq_html.find('.cuerpoTabla').append(filaEditableMaquina());
  });


  var fila_ejemplo_maq = filaEjemploMaquina();
  maq_html.find('.filaEjemplo').remove();
  for (var j = 0; j < maquinas.length; j++) {
    let fila = fila_ejemplo_maq.clone();

    setearFilaMaquinas(fila,
      maquinas[j].id,maquinas[j].nro_admin,
      maquinas[j].sector,maquinas[j].isla,
      maquinas[j].marca_juego);

    fila.find('.cuerpoTablaAcciones').children().each(
      function (index,child){
        $(child).attr('disabled',!editable);
    });

    maq_html.find('.cuerpoTabla').append(fila);
  }
}
