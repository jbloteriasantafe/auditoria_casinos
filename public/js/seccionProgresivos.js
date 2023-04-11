$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Progresivos');
  $('#btn-buscar').trigger('click');
});

$('#contenedorFiltros').keypress(function(e){
  if(e.charCode == 13){//Enter
    $('#btn-buscar').click();
  }
});

//Busqueda
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden) {
  e.preventDefault();
  let size = 10;
  //Fix error cuando librería saca los selectores
  if (!isNaN($('#herramientasPaginacion').getPageSize())) {
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size   = (page_size == null || isNaN(page_size)) ? size : page_size;
  page_number = (pagina != null) ? pagina  : $('#herramientasPaginacion').getCurrentPage();
  columna     = (columna != null)? columna : $('#tablaResultados .activa').attr('value');
  orden       = (orden  != null) ? orden   : $('#tablaResultados .activa').attr('estado');
  const formData = {
    page: page_number,
    sort_by: {columna: columna, orden: orden},
    page_size: page_size,
  };
  $('#contenedorFiltros').find('[form-key]').each(function(){
    formData[$(this).attr('form-key')]=$(this).val();
  });
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: 'POST',
    url: '/progresivos/buscarProgresivos',
    data: formData,
    dataType: 'json',
    success: function(resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number, page_size, resultados.total, clickIndice);
      $('#cuerpoTabla tr').not('.filaEjemplo').remove();
      for (let i = 0; i < resultados.data.length; i++) {
        const p = resultados.data[i];
        const fila = $('#cuerpoTabla .filaEjemplo').clone().removeClass('filaEjemplo').css('display', '');
        ["casino","moneda","nombre","maquinas","islas","sectores"].forEach(function(attr){
          fila.find('.'+attr).text(p[attr]).attr('title',p[attr]);
        });
        fila.find(`[es_individual="${p.es_individual}"]`).show();
        fila.find('button').val(p.id_progresivo);
        $('#cuerpoTabla').append(fila);
      }
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function(data) {
      console.log('Error:', data);
    }
  });
});

$('#btn-ayuda').click(function(e) {
  e.preventDefault();
  $('#modalAyuda').modal('show');
});

//Mostrar modal para agregar nuevo Progresivo
$('#btn-nuevo').click(function(e) {
    e.preventDefault();
    $('#btn-cancelar').text('CANCELAR');
    $('#btn-guardar').val("nuevo");
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('#modalProgresivo .modal-title').text('| NUEVO PROGRESIVO');
    $('#modalProgresivo .modal-header').css('background-color', '#6dc7be');
    $('#modalProgresivo').modal('show');
    mostrarProgresivo({ id_progresivo: null, id_tipo_moneda: 1,nombre: '', porc_recup: 0, es_individual: 0}, [], [], true);
});

// Modal crear nuevo progresivo individual
$('#btn-nuevo-ind').click(function(e) {
  e.preventDefault();
  $('#modalProgresivoIndividual .modal-title').text('| NUEVOS PROGRESIVOS INDIVIDUALES');
  $('#modalProgresivoIndividual .modal-header').css('background-color', '#6dc7be');
  $('.erroneo').removeClass('erroneo');
  $('#contenedorMaquinasIndividual').empty();
  $('#inputPorcRecupIndividual').val(0);
  $('#inputMaximoIndividual').val(0);
  $('#inputBaseIndividual').val(0);
  $('#inputPorcVisibleIndividual').val(0);
  $('#inputPorcOcultoIndividual').val(0);
  let maq_html = $('.tablaMaquinasDivIndividual').clone().removeClass('ejemplo').show();
  $('#contenedorMaquinasIndividual').append(maq_html);
  let cuerpo_tabla = maq_html.find('.cuerpoTabla').empty();
  $('#modalProgresivoIndividual_casino').trigger('change');
  $('#modalProgresivoIndividual').modal('show');
});

$(document).on('click','#contenedorMaquinasIndividual .cuerpoTabla tr .individual.eliminar', function(){
    $(this).closest('tr').remove();
});
$(document).on('click','#contenedorMaquinasIndividual .cuerpoTabla tr .individual.editar', function(){
    const fila = $(this).closest('tr');
    const data = arregloProgresivoIndividual(fila);
    const filaEditable = filaEditableIndividualParcial(data);
    fila.replaceWith(filaEditable);
});
$(document).on('click','#contenedorMaquinasIndividual .cuerpoTabla tr .individual.cancelar', function(){
    const fila = $(this).closest('tr');
    const data = arregloProgresivoIndividual(fila);
    const filaNoEditable = filaEjemploIndividual();
    setearFilaProgresivoIndividual(filaNoEditable, data);
    fila.replaceWith(filaNoEditable);
});

function filaEjemploIndividual() {
    return $('.tablaMaquinasDivIndividual').find('.filaEjemplo').clone().removeClass('filaEjemplo');
}

$(document).on('click','#contenedorMaquinasIndividual .cuerpoTabla tr .individual.confirmar', function(){
    const fila = $(this).closest('tr');
    fila.find('.erroneo').removeClass('erroneo');
    const fila_val = arregloProgresivoIndividual(fila);
    const validacion = validarFilaInd(fila);
    fila.find('.cuerpoPorcRecup').find('.editable').addClass(validacion.porc_recup? '' : 'erroneo');
    fila.find('.cuerpoMaximo').find('.editable').addClass(validacion.maximo? '' : 'erroneo');
    fila.find('.cuerpoBase').find('.editable').addClass(validacion.base? '' : 'erroneo');
    fila.find('.cuerpoPorcOculto').find('.editable').addClass(validacion.porc_oculto? '' : 'erroneo');
    fila.find('.cuerpoPorcVisible').find('.editable').addClass(validacion.porc_visible? '' : 'erroneo');

    if (validacion.razones.length != 0) {
        let finalstr = '';
        for (let i = 0; i < validacion.razones.length; i++) {
            finalstr += '<h5>' + validacion.razones[i] + '</h5>';
        }
        mostrarError(finalstr);
        return;
    }

    let nueva_fila = filaEjemploIndividual();
    setearFilaProgresivoIndividual(nueva_fila, fila_val)
    fila.replaceWith(nueva_fila);

    nueva_fila.find('.cuerpoTablaAcciones').empty();

    let botonEditar = crearBoton('fa-pencil-alt').addClass('individual editar');
    nueva_fila.find('.cuerpoTablaAcciones').append(botonEditar);
    let botonBorrar = crearBoton('fa-trash').addClass('individual borrar');
    nueva_fila.find('.cuerpoTablaAcciones').append(botonBorrar);
});

function filaEditableIndividualParcial(data) {
    let fila = filaEjemploIndividual();

    //No puedo agregarle un editable de numeros con flechas,porque son muy grandes.
    const input = crearEditable('text');
    fila.find('.cuerpoBase').empty().append(input.clone());
    fila.find('.cuerpoMaximo').empty().append(input.clone());
    fila.find('.cuerpoPorcRecup').empty().append(input.clone());
    fila.find('.cuerpoPorcVisible').empty().append(input.clone());
    fila.find('.cuerpoPorcOculto').empty().append(input.clone());

    setearFilaProgresivoIndividual(fila, data);

    const botonConfirmar = crearBoton('fa-check').addClass('individual cancelar');
    const botonCancelar = crearBoton('fa-times').addClass('individual cancelar');
    fila.find('.cuerpoTablaAcciones').empty().append(botonConfirmar).append(botonCancelar);
    return fila;
}

$('#btn-guardarIndividual').click(function(){
  $('.erroneo').removeClass('erroneo');
  const err = verificarFormularioIndividual();
  if (err.errores) {
    mostrarError(err.mensaje);
    return;
  }
  
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: 'POST',
    data: {
      id_casino: $('#modalProgresivoIndividual_casino').val(),
      id_tipo_moneda: $('#modalProgresivoIndividual_tipomoneda').val(),
      maquinas: $('#contenedorMaquinasIndividual tbody tr').map(function(idx,f){
        return arregloProgresivoIndividual($(f));
      }).toArray(),
    },
    url: '/progresivos/crearProgresivosIndividuales',
    success: function(data) {
      $('#mensajeExito').find('.textoMensaje p').text('Los progresivos fueron cargados con éxito.');
      $('#modalProgresivoIndividual').modal('hide');
      $('#mensajeExito').show();
    },
    error: mostrarRespuestaError
  });
});

//Mostrar modal con los datos del Log
$(document).on('click', '#cuerpoTabla tr .grupal.detalle', function() {
    $('#modalProgresivo .modal-title').text('| VER MÁS');
    $('#modalProgresivo .modal-header').css('background', '#4FC3F7');
    $('.btn-agregarNivelProgresivo').hide();
    $('#btn-cancelar').text('SALIR');

    $.get("/progresivos/obtenerProgresivo/" + $(this).val(), function(data) {
        mostrarProgresivo(data.progresivo, data.pozos, data.maquinas, false);
        $('#modalProgresivo').modal('show');
    });
});

//Mostrar modal con los datos del Juego cargado
$(document).on('click', '#cuerpoTabla tr .grupal.modificar', function() {
    $('#mensajeExito').hide();
    $('#btn-cancelar').text('CANCELAR');
    $('.btn-agregarNivelProgresivo').show();
    $('#modalProgresivo .modal-title').text('| MODIFICAR PROGRESIVO');
    $('#modalProgresivo .modal-header').css('background', '#ff9d2d');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-warningModificar');

    $.get("/progresivos/obtenerProgresivo/" + $(this).val(), function(data) {
        mostrarProgresivo(data.progresivo, data.pozos, data.maquinas, true);
        $('#btn-guardar').val("modificar");
        $('#modalProgresivo').modal('show');
    });
});


$(document).on('click','#cuerpoTabla tr .grupal.eliminar', function(){
    $('#btn-eliminarModal').val($(this).val());
    $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function(){
    $.ajaxSetup({headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') }});
    $.ajax({
        type: "DELETE",
        url: "/progresivos/eliminarProgresivo/" + $(this).val(),
        success: function(data) {
            $('#btn-buscar').click();
            $("#tablaResultados").trigger("update");
            $('#modalEliminar').modal('hide');
        },
        error: function(data) {
            console.log('Error: ', data);
        }
    });
});

$(document).on('click', '#tablaResultados thead tr th[value]', function(e) {
  const icon = $(this).find('i');
  const not_sorted = icon.hasClass('fa-sort');
  const down_sorted = icon.hasClass('fa-sort-down');
  $('#tablaResultados .activa').removeClass('activa');
  $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  if(not_sorted){
    icon.removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else if(down_sorted){
    icon.removeClass().addClass('fa fa-sort-up').parent().addClass('activa').attr('estado','asc');
  }
  clickIndice(e, $('#herramientasPaginacion').getCurrentPage(), $('#herramientasPaginacion').getPageSize());
});


function clickIndice(e, pageNumber, tam) {
  if (e != null) {
    e.preventDefault();
  }
  tam = (isNaN(tam)) ? $('#herramientasPaginacion').getPageSize() : tam;
  const columna = $('#tablaResultados .activa').attr('value');
  const orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

function crearBoton(icono) {
    const btn = $('<button>').addClass('btn').addClass('btn-info');
    const i = $('<i>').addClass('fa').addClass('fa-fw').addClass(icono);
    return btn.append(i);
}

function crearEditable( tipo, defecto = "", min = 0, max = 100, step = 0.001) {
    return $('<input>').addClass('editable form-control').attr('type', tipo).attr('min', min)
    .attr('max' , max).attr('step', step).val(defecto);
}

function filaEjemplo() {
    return $('.tablaPozoDiv.ejemplo').find('.filaEjemplo').clone().removeClass('filaEjemplo').show();
}

function filaEjemploMaquina() {
    return $('.tablaMaquinasDiv.ejemplo').find('.filaEjemplo').clone().removeClass('filaEjemplo').show();
}

function moverFila(fila,direccion){
    let elem = fila[0];
    let parent = elem.parentNode;
    let arriba = null;
    if(direccion == 'subir'){
        arriba = elem.previousElementSibling;
    }
    else if(direccion == 'bajar'){
        arriba = elem;
        elem = elem.nextElementSibling;
    }
    else return;
    let elem_nivel = elem.getElementsByClassName('cuerpoTablaPozoNumero')[0];
    let arriba_nivel = arriba.getElementsByClassName('cuerpoTablaPozoNumero')[0];
    const arriba_nro = parseInt(arriba_nivel.innerText);
    const elem_nro = parseInt(elem_nivel.innerText);
    if (arriba_nro < elem_nro) {
        arriba_nivel.innerText = elem_nro.toString();
        elem_nivel.innerText = arriba_nro.toString();
        parent.insertBefore(elem, arriba);
    }
}

$(document).on('click','#contenedorPozos .cuerpoTablaPozoAcciones .borrar',function(){
    let fila = $(this).closest('tr');
    let tbody = fila.closest('tbody');
    fila.remove();
    tbody.find('.cuerpoTablaPozoNumero').each(function(i,c){
        $(this).text(i+1);
    });
});

$(document).on('click','#contenedorPozos .cuerpoTablaPozoFlechas .subir',function(){
    moverFila($(this).closest('tr'),'subir');
});

$(document).on('click','#contenedorPozos .cuerpoTablaPozoFlechas .bajar',function(){
    moverFila($(this).closest('tr'),'bajar');
});

$(document).on('click','#contenedorPozos .cuerpoTablaPozoAcciones .cancelar',function(){
    let fila = $(this).closest('tr');
    let valores = $(this).data('valores_viejos');
    let nueva_fila = filaEjemplo();
    setearValoresFilaNivel(nueva_fila, valores);
    fila.replaceWith(nueva_fila);
});

$(document).on('click','#contenedorPozos .cuerpoTablaPozoAcciones .editar',function(){
    let fila = $(this).closest('tr');
    let valores = arregloNivel(fila);
    let fila_editable = crearFilaEditableNivel(valores);
    fila.replaceWith(fila_editable);
    fila_editable.find('.cancelar').data('valores_viejos',valores);
});

$(document).on('click','#contenedorPozos .cuerpoTablaPozoAcciones .confirmar',function(){
    let fila = $(this).closest('tr');
    fila.find('.erroneo').removeClass('erroneo');
    const validacion = validarFila(fila);
    fila.find('.cuerpoTablaPozoNombre').find('.editable').addClass(validacion.nombre_nivel? '' : 'erroneo');
    fila.find('.cuerpoTablaPozoBase').find('.editable').addClass(validacion.base? '' : 'erroneo');
    fila.find('.cuerpoTablaPozoMaximo').find('.editable').addClass(validacion.maximo? '' : 'erroneo');
    fila.find('.cuerpoTablaPorcVisible').find('.editable').addClass(validacion.porc_visible? '' : 'erroneo');
    fila.find('.cuerpoTablaPorcOculto').find('.editable').addClass(validacion.porc_oculto? '' : 'erroneo');
    if (validacion.razones.length != 0){
        mostrarError(validacion.razones.map(function(s){return '<h5>'+s+'</h5>';}).join(''));
        return;
    }

    let vals = arregloNivel(fila);
    let nueva_fila = filaEjemplo();
    setearValoresFilaNivel(nueva_fila, vals);
    fila.replaceWith(nueva_fila);

    nueva_fila.find('.confirmar').replaceWith(crearBoton('fa-pencil-alt').addClass('editar'));
    nueva_fila.find('.cancelar').replaceWith(crearBoton('fa-trash-alt').addClass('borrar'));
});

function crearFilaEditableNivel(valores = { id_nivel_progresivo: null }) {
    let fila = filaEjemplo();
    fila.find('.cuerpoTablaPozoNumero').empty();
    fila.find('.cuerpoTablaPozoNombre').empty().append(crearEditable("text"));
    fila.find('.cuerpoTablaPozoBase').empty().append(crearEditable("text"));
    fila.find('.cuerpoTablaPozoMaximo').empty().append(crearEditable("text"));
    fila.find('.cuerpoTablaPorcVisible').empty().append(crearEditable("text"));
    fila.find('.cuerpoTablaPorcOculto').empty().append(crearEditable("text"));
    fila.find('.editar').remove();
    fila.find('.cuerpoTablaPozoAcciones').empty()
    .append(crearBoton('fa-check').addClass('confirmar'))
    .append(crearBoton('fa-times').addClass('cancelar'));

    setearValoresFilaNivel(fila, valores);
    return fila;
}

$(document).on('click','#contenedorPozos .confirmarPozo',function(){
    const pozo = $(this).closest('.tablaPozoDiv');
    let valorModif = pozo.find('.nombrePozo').val();
    let text = $('<b>').text(valorModif).addClass('nombrePozo');
    pozo.find('.nombrePozo').replaceWith(text);
    pozo.find('.confirmarPozo').replaceWith(crearBoton('fa-pencil-alt').addClass('editarPozo').removeClass('btn-info').addClass('btn-link'));
});

$(document).on('click','#contenedorPozos .editarPozo',function(){
    const pozo = $(this).closest('.tablaPozoDiv');
    const text_viejo = pozo.find('.nombrePozo').text();
    pozo.find('.nombrePozo').replaceWith(crearEditable('text').addClass('nombrePozo').val(text_viejo));
    pozo.find('.editarPozo').replaceWith(crearBoton('fa-check').addClass('confirmarPozo').removeClass('btn-info').addClass('btn-link'));
});

$(document).on('click','#contenedorPozos .eliminarPozo',function(){
    $(this).closest('.tablaPozoDiv').remove();
});

$(document).on('show.bs.collapse','#contenedorPozos .collapse',function(){
    const pozo = $(this).closest('.tablaPozoDiv');
    let icono = pozo.find('.abrirPozo i');
    let icono_nuevo = $('<i>').addClass('fa').addClass('fa-fw');
    icono.replaceWith(icono_nuevo.addClass('fa-angle-down'));
});

$(document).on('hide.bs.collapse','#contenedorPozos .collapse',function(){
    const pozo = $(this).closest('.tablaPozoDiv');
    let icono = pozo.find('.abrirPozo i');
    let icono_nuevo = $('<i>').addClass('fa').addClass('fa-fw');
    icono.replaceWith(icono_nuevo.addClass('fa-angle-up'));
});

$(document).on('click','#contenedorPozos .abrirPozo',function(){
    const pozo = $(this).closest('.tablaPozoDiv');
    let colapsable = pozo.find('.collapse');
    colapsable.collapse('toggle');
});

$(document).on('click','#contenedorPozos .agregar',function(){
    const pozo = $(this).closest('.tablaPozoDiv');
    let fila = crearFilaEditableNivel();
    //La primera vez que se agrega una fila,
    //No se la deja cancelar la edicion sino que se la elimina.
    fila.find('.cancelar').replaceWith(crearBoton('fa-trash-alt').addClass('borrar'));
    pozo.find('.cuerpoTablaPozo').append(fila);

    const fila_anterior = fila.prev();
    const nro_anterior = fila_anterior.length > 0? parseInt(fila_anterior.find('.cuerpoTablaPozoNumero').text()) : 0;
    fila.find('.cuerpoTablaPozoNumero').text(nro_anterior+1);
});

function mostrarPozo(id_pozo, nombre, editable, niveles = {}) {
    let pozo_html = $('.tablaPozoDiv.ejemplo').clone().removeClass('ejemplo');
    pozo_html.find('.nombrePozo').text(nombre);
    $('#contenedorPozos').append(pozo_html);
    pozo_html.show();

    pozo_html.attr('data-id', id_pozo);

    pozo_html.find('.filaEjemplo').remove();

    let fila_ejemplo_pozo = filaEjemplo();

    for (let j = 0; j < niveles.length; j++) {
        let fila = fila_ejemplo_pozo.clone();
        const nivel = niveles[j];
        setearValoresFilaNivel(fila, nivel);
        fila.find('button').attr('disabled',!editable);
        pozo_html.find('.cuerpoTablaPozo').append(fila);
    }

    pozo_html.find('.editarPozo').attr('disabled', !editable);
    pozo_html.find('.eliminarPozo').attr('disabled', !editable);
    pozo_html.find('.agregar').attr('disabled', !editable);
}

$('#modalProgresivo_casino').change(function(){
    $('#input-isla').generarDataList("/progresivos/buscarIslaPorCasinoYNro/" + $(this).val(),'islas','id_isla','nro_isla',1,true);
    $('#input-maquina').generarDataList("/progresivos/buscarMaquinas/" + $(this).val(),'maquinas','id_maquina','nro_admin',1,true);
    //FIX visual
    $('#input-isla').parent().find('.contenedor-data-list').css('top','0px').css('position','revert');
    $('#input-maquina').parent().find('.contenedor-data-list').css('top','0px').css('position','revert');
});

$('#modalProgresivoIndividual_casino').change(function(){
    $('#input-maquina-individual').generarDataList("http://" + window.location.host+"/progresivos/buscarMaquinas/" + $(this).val(),'maquinas','id_maquina','nro_admin',1,true)
    $('#input-maquina-individual').parent().find('.contenedor-data-list').css('top','0px').css('position','revert');
});

$('#input-maquina-individual').keypress(function(e){
    if(e.charCode == 13) $('#btn-agregarMaquinaIndividual').click();
});

$('#btn-agregarMaquinaIndividual').click(function() {
    const id_casino = $('#modalProgresivoIndividual_casino').val();
    const nro_admin = $('#input-maquina-individual').val();
    if(id_casino == null || nro_admin.length == 0) return;
    $.get('/progresivos/buscarMaquinas/'+id_casino+'/'+nro_admin,function(data){
        let m = null;
        for(let i = 0;i<data.maquinas.length;i++){
            if(data.maquinas[i].nro_admin == nro_admin){
                m = data.maquinas[i];
                break;
            }
        }
        if(m === null) return;
        $('#input-maquina-individual').setearElementoSeleccionado(0, "");
        const selector_maquina = 'data-id='+m.id_maquina;
        const esta_en_la_tabla = $('#contenedorMaquinasIndividual .tablaMaquinasIndividual tr['+selector_maquina+']').length != 0;
        if(esta_en_la_tabla) return;
        const data_fila = {
            id_maquina:  m.id_maquina,
            nro_admin:   m.nro_admin,
            sector:      m.sector,
            isla:        m.isla,
            marca_juego: m.marca_juego,
            porc_recup: $('#inputPorcRecupIndividual').val(),
            maximo: $('#inputMaximoIndividual').val(),
            base: $('#inputBaseIndividual').val(),
            porc_visible: $('#inputPorcVisibleIndividual').val(),
            porc_oculto: $('#inputPorcOcultoIndividual').val()
        };
        let fila = filaEjemploIndividual();
        setearFilaProgresivoIndividual(fila, data_fila);
        $('#contenedorMaquinasIndividual .cuerpoTabla').append(fila);
    });
});

$('#input-isla').keypress(function(e){
    if(e.charCode == 13) $('#btn-agregarIsla').click();
});

$('#btn-agregarIsla').click(function(){
    const id_casino = $('#modalProgresivo_casino').val();
    const nro_isla = $('#input-isla').val();
    if(id_casino == null || nro_isla.length == 0) return;
    //Esto retorna lista de subislas (islas con mismo nro y distinto codigo). Por eso la doble iteracion
    $.get('/progresivos/listarMaquinasPorNroIsla/'+nro_isla+'/'+id_casino,function(data){
        let maquinas_filas = [];
        $('#input-isla').setearElementoSeleccionado(0, "");
        data.islas.forEach(function(subisla){
            subisla.maquinas.forEach(function(m,k){
                const selector_maquina = 'data-id='+m.id_maquina;
                const esta_en_la_tabla = $('#contenedorMaquinas .tablaMaquinas tr['+selector_maquina+']').length != 0;
                if(esta_en_la_tabla) return;
                maquinas_filas.push({
                    id_maquina:  m.id_maquina,
                    nro_admin:   m.nro_admin,
                    sector:      subisla.sector,
                    isla:        subisla.nro_isla,
                    marca_juego: m.marca_juego
                });
            })
        })
        llenarTablaMaquinas(maquinas_filas,true,true);
        $('#selectTipoProgresivo').change();
    });
});

$('#input-maquina').keypress(function(e){
    if(e.charCode == 13) $('#btn-agregarMaquina').click();
});

$('#btn-agregarMaquina').click(function() {
  const id_casino = $('#modalProgresivo_casino').val();
  const nro_admin = $('#input-maquina').val();
  if(id_casino == null || nro_admin.length == 0) return;
  $.get('/progresivos/buscarMaquinas/'+id_casino+'/'+nro_admin,function(data){
    const m = data.maquinas.find(function(m){return m.nro_admin==nro_admin;});
    if(m === undefined) return;
    $('#input-maquina').setearElementoSeleccionado(0, "");
    const esta_en_la_tabla = $(`#contenedorMaquinas .tablaMaquinas tr[data-id=${m.id_maquina}]`).length != 0;
    if(esta_en_la_tabla) return;
    llenarTablaMaquinas([m],true,true);
    $('#selectTipoProgresivo').change();
  });
});

$('#btn-agregarPozo').on('click', function() {
  mostrarPozo(null, 'Pozo', true);
  $('.tablaPozoDiv').not('.ejemplo').find('.abrirPozo').last().trigger('click');
});

function mostrarProgresivo(progresivo, pozos, maquinas, editable) {    
    $('.erroneo').removeClass('erroneo');
    $('#modalProgresivo_casino').attr('disabled', progresivo.id_progresivo !== null)
    .val(progresivo.id_casino).trigger('change');
    $('#modalProgresivo_tipomoneda').attr('disabled', !editable).val(progresivo.id_tipo_moneda);
    //Si estamos creando un nuevo progresivo y solo tengo un solo casino para elegir, lo seteo
    $('#modalProgresivo_casino option')[0].selected = progresivo.id_progresivo === null && $('#modalProgresivo_casino option').length == 1;
    $('#nombre_progresivo').val(progresivo.nombre);
    $('#nombre_progresivo').attr('disabled', !editable);
    $('#porc_recup').val(progresivo.porc_recup);
    $('#porc_recup').attr('disabled', !editable);
    $('#contenedorPozos').empty();
    $('#contenedorMaquinas').empty();
    $('#btn-agregarPozo').attr('disabled', !editable);

    for (let i = 0; i < pozos.length; i++) {
        mostrarPozo(pozos[i].id_pozo, pozos[i].descripcion, editable, pozos[i].niveles);
    }

    $('.tablaPozoDiv').not('.ejemplo').find('.abrirPozo').first().click();
    llenarTablaMaquinas(maquinas, editable);
    $('#selectTipoProgresivo').val(progresivo.es_individual).attr('disabled',!editable).change();
    $('#btn-guardar').attr('disabled', !editable).data('id_progresivo',progresivo.id_progresivo);
}

$('#btn-guardar').on('click', function() {
  $('.erroneo').removeClass('erroneo');
  {
    const err = verificarFormulario();
    if (err.errores) return mostrarError(err.mensaje);
  }
  
  const pozos = $('.tablaPozoDiv').not('.ejemplo').map(function(pidx,pozo){
    const p = $(pozo);
    return {
      id_pozo: p.attr('data-id'),
      descripcion: p.find('.nombrePozo').text(),
      niveles: p.find('tbody tr').map(function(nidx,nivel) {
        return arregloNivel($(nivel));
      }).toArray(),
    };
  }).toArray();
  
  const maquinas = $('.tablaMaquinasDiv').not('.ejemplo').find('tbody tr').map(function(idx,maq){
    const m = $(maq);
    return {
      id_maquina: m.attr('data-id'),
      nro_admin: m.find('.cuerpoTablaNroAdmin').text(),
      nro_isla: m.find('.cuerpoTablaIsla').text(),
      sector_descripcion: m.find('.cuerpoTablaSector').text(),
      marca_juego: m.find('.cuerpoTablaMarcaJuego').text()
    };
  }).toArray();
  
  const formData = {
    id_progresivo: $(this).data('id_progresivo'),
    es_individual: $('#selectTipoProgresivo').val(),
    id_casino: $('#modalProgresivo_casino').val(),
    id_tipo_moneda: $('#modalProgresivo_tipomoneda').val(),
    nombre: $('#nombre_progresivo').val(),
    porc_recup: $('#porc_recup').val(),
    pozos: pozos,
    maquinas: maquinas,
  };

  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: 'POST',
    data: formData,
    url: '/progresivos/crearModificarProgresivo',
    success: function(data) {
      const accion = formData.id_progresivo === null? 'creado' : 'modificado';
      $('#mensajeExito').find('.textoMensaje p').text(`El progresivo fue ${accion} con éxito.`);
      $('#modalProgresivo').modal('hide');
      $('#mensajeExito').show();
      $('#btn-buscar').click();
    },
    error: mostrarRespuestaError
  });
});


function mostrarRespuestaError(err) {
    let respuesta = err.responseJSON.errors;
    console.log(err);
    console.log(respuesta);
    let msj = "";
    if (respuesta != undefined) {
        let llaves = Object.keys(respuesta);
        for (let i = 0; i < llaves.length; i++) {
            let k = llaves[i];
            msj = msj + "<p>" + k + ' => ' + respuesta[k] + "</p>";
        }
    }
    mostrarError(msj);
}

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

function verificarFormulario() {
    let errores = false;
    let mensaje = "";

    let sin_completar = $('#contenedorPozos').find('input');
    if (sin_completar.length > 0) {
        sin_completar.each(function(idx, c) {
            $(c).addClass('erroneo');
        });
        errores = true;
        mensaje = mensaje + "<p>Tiene pozos o niveles sin completar</p>";
    }

    let porc_recup = parseFloat($('#porc_recup').val());
    if (isNaN(porc_recup) || porc_recup > 100 || porc_recup < 0) {
        $('#porc_recup').addClass('erroneo');
        errores = true;
        mensaje = mensaje + "<p>El porcentaje de recuperacion es erroneo</p>";
    }

    let nombre_progresivo = $('#nombre_progresivo');
    const es_link = $('#selectTipoProgresivo').val() == 0;
    if (nombre_progresivo.val() == "" && es_link) {
        nombre_progresivo.addClass('erroneo');
        errores = true;
        mensaje = mensaje + "<p>Sin nombre de progresivo</p>";
    }

    let casino = $('#modalProgresivo_casino');
    if (casino.val() === null) {
        casino.addClass('erroneo');
        errores = true;
        mensaje = mensaje + "<p>Error en el casino seleccionado</p>";
    }

    return { errores: errores, mensaje: mensaje };
}

function verificarFormularioIndividual() {
    let errores = false;
    let mensaje = "";

    let sin_completar = $('#contenedorMaquinasIndividual .tablaMaquinasIndividual tr input');
    if (sin_completar.length > 0) {
        sin_completar.addClass('erroneo');
        errores = true;
        mensaje = mensaje + "<p>Tiene progresivos sin completar</p>";
    }
    return { errores: errores, mensaje: mensaje };
}

$(document).on('click','#contenedorMaquinas .unlink',function(){
  $(this).closest('tr').remove();
  $('#selectTipoProgresivo').change();
});

function llenarTablaMaquinas(maquinas, editable, reusar_div=false) {
  let maq_html = null;
  if(reusar_div){
    maq_html = $('.tablaMaquinasDiv').not('.ejemplo')
  }
  else{
    maq_html = $('.tablaMaquinasDiv.ejemplo').clone().removeClass('ejemplo');
    $('#contenedorMaquinas').append(maq_html);
    maq_html.show();    
  }

  $('#btn-agregarMaquina').attr('disabled', !editable);
  $('#input-maquina').attr('disabled',!editable);
  $('#btn-agregarIsla').attr('disabled', !editable);
  $('#input-isla').attr('disabled',!editable);

  const fila_ejemplo_maq = filaEjemploMaquina();
  maq_html.find('.filaEjemplo').remove();
  for (let j = 0; j < maquinas.length; j++) {
    const fila = fila_ejemplo_maq.clone();
    const m = maquinas[j];
    fila.attr('data-id', m.id_maquina);
    fila.find('.cuerpoTablaNroAdmin').text(m.nro_admin).attr('title',m.nro_admin);
    fila.find('.cuerpoTablaSector').text(m.sector).attr('title',m.sector);
    fila.find('.cuerpoTablaIsla').text(m.isla).attr('title',m.isla);
    fila.find('.cuerpoTablaMarcaJuego').text(m.marca_juego).attr('title',m.marca_juego);
    fila.find('.cuerpoTablaAcciones button').attr('disabled', !editable);
    maq_html.find('.cuerpoTabla').append(fila);
  }
}

$('#selectTipoProgresivo').change(function(e){
  const cant_maquinas = $('#contenedorMaquinas tbody tr').length;
  const deshabilitar_opcion_indiv = cant_maquinas > 1;
  $(this).find('[value="1"]').attr('disabled',deshabilitar_opcion_indiv);
  //Si esta en individual pero no puede estar en individual lo pongo en LINK
  if($(this).val() == 1 && deshabilitar_opcion_indiv) $(this).val(0);
  const es_individual = $(this).val() == 1;
  $('#nombre_progresivo').parent().toggle(!es_individual);
  const deshabilitar_carga_isla     = es_individual;
  const deshabilitar_carga_maquinas = es_individual && cant_maquinas >= 1;
  $('#enlazarMaquinaDiv').toggle(!deshabilitar_carga_maquinas);
  $('#enlazarIslaDiv').toggle(!deshabilitar_carga_isla);
});
