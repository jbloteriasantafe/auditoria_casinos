$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionarMTM').removeClass();
  $('#gestionarMTM').addClass('subMenu2 collapse in');

  $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Fórmulas');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcFormulas').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcFormulas').addClass('opcionesSeleccionado');

  $('#btn-buscar').trigger('click');
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
$('#btn-minimizarMaquinas').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//si presiono enter y el modal esta abierto se manda el formulario
$(document).on("keypress" , function(e){
  if(e.which == 13 && $('#modalFormula').is(':visible')) {
    e.preventDefault();
    $('#btn-guardar').click();
  }
})
//enter en buscador
$('contenedorFiltros input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#').click();
  }
})

$('#columna input').on('focusout' , function(){
  if ($(this).val() == ''){
    mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
  }
});

$('#columna input').focusin(function(){
  $(this).removeClass('alerta');

});
//Agregar nuevo termino en el modal
$('#btn-agregarTermino').click(function(){
  $('#columna #operador').each(function(){
    $(this).prop('readonly', false);
  })

  $('#columna').append('<br>');
  $('#columna')
      .append($('<div>')
          .addClass('row')
          .addClass('terminoFormula')
          .css('margin-bottom','15px')
          .attr('id','terminoFormula')
          .append($('<div>')
              .addClass('col-lg-4')
              .addClass('col-lg-offset-1')
              .append($('<input>')
                  .attr('placeholder' , 'Contador')
                  .attr('id','contador')
                  .attr('type','text')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-lg-4')
              .append($('<input>')
                  .attr('placeholder' , 'Operador')
                  .attr('readonly', true)
                  .attr('id','operador')
                  .attr('type','text')
                  .addClass('form-control')
              )
          )
          .append($('<div>')
              .addClass('col-xs-3')
              .css('padding-right','0px')
              .append($('<button>')
                  .addClass('borrarTermino')
                  .addClass('borrarFila')
                  .addClass('btn')
                  .addClass('btn-danger')
                  .css('margin-top','6px')
                  .attr('type','button')
                  .append($('<i>')
                      .addClass('fa')
                      .addClass('fa-trash')
                  )
              )
          )


      )

});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| FÓRMULAS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo Juego
$('#btn-nuevo').click(function(e){
  $('#mensajeExito').hide();
  e.preventDefault();
  $('#btn-guardar').val("nuevo");
  $('#frmFormula').trigger('reset');
  $('.terminoFormula').remove();
  $('#terminoFormula #operador').prop('readonly', true);
  $('#btn-guardar').removeClass();
  $('#btn-guardar').addClass('btn btn-successAceptar');
  $('.modal-title').text('| NUEVA FÓRMULA');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

  $('#modalFormula').modal('show');
});

//Mostrar modal con los datos del Juego cargado
$(document).on('click','.modificar',function(){
    $('#mensajeExito').hide();
    $('#frmFormula').trigger('reset');
    $('.terminoFormula').remove();

    $('.modal-title').text('| MODIFICAR FÓRMULA');
    $('.modal-header').attr('style','font-family: Roboto-Black; background: #ff9d2d; color: #fff;');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-warningModificar');

    var id_formula = $(this).val();

    $.get("formulas/obtenerFormula/" + id_formula, function(data){
        $('#id_formula').val(id_formula);//campo oculto
        $('#btn-guardar').val("modificar");
        $('#id_formula').val(id_formula);
        $('#modalFormula').modal('show');
        $('#terminoFormula #operador').prop('readonly', false);
        $('#contador').val(data.formula[0].contador);
        $('#operador').val(data.formula[0].operador);
        var ultimo=false;
        for (var i = 1; i < data.formula.length; i++) {
          //me fijo si es el ulitmo termino para ponder en readonly el operador
          if(data.formula[i].operador==null){ultimo=true};

          if(data.formula[i].contador != null){
          $('#columna').append($('<br>'));
          $('#columna')
              .append($('<div>')
                  .addClass('row')
                  .addClass('terminoFormula')
                  .css('margin-bottom','15px')
                  .attr('id','terminoFormula')
                  .append($('<div>')
                      .addClass('col-lg-4')
                      .addClass('col-lg-offset-1')
                      .append($('<input>')
                          .attr('placeholder' , 'Contador')
                          .attr('id','contador')
                          .val(data.formula[i].contador)
                          .attr('type','text')
                          .addClass('form-control')
                      )
                  )
                  .append($('<div>')
                      .addClass('col-lg-4')
                      .append($('<input>')
                          .attr('placeholder' , 'Operador')
                          .attr('id','operador')
                          .attr('readonly', ultimo)
                          .attr('type','text')
                          .val(data.formula[i].operador)
                          .addClass('form-control')
                      )
                  )
                  .append($('<div>')
                      .addClass('col-xs-3')
                      .css('padding-right','0px')
                      .append($('<button>')
                          .addClass('borrarTermino')
                          .addClass('borrarFila')
                          .addClass('btn')
                          .addClass('btn-danger')
                          .css('margin-top','6px')
                          .attr('type','button')
                          .append($('<i>')
                              .addClass('fa')
                              .addClass('fa-trash')
                          )
                      )
                  )

              );

            }

        }




      });
});

$('.operador').keydown(function(e){
  console.log($(this).val().length);
  if(e.which!=107 && e.which!=109 && e.which!=8)
    e.preventDefault();
  else if($(this).val().length > 0 && e.which!=8){
      e.preventDefault();
  }
})

//borrar una tabla de pago
$(document).on('click','.borrarTermino',function(){

  $(this).parent().parent().remove();

  var i=$('#columna #terminoFormula').length;

  $('#columna #terminoFormula').last().find('#operador').val('');
  $('#columna #terminoFormula').last().find('#operador').prop('readonly', true);


});

//Borrar Juego y remover de la tabla
$(document).on('click','.eliminar',function(){

    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
    var id = $(this).val();
    $('#btn-eliminarFormula').val(id);
    $('#modalEliminar').modal('show');
    $('#mensajeEliminar').text('¿Seguro que desea eliminar "' + $(this).parent().parent().find('td:first').text()+'"?');

});

$('#btn-eliminarFormula').click(function (e) {
    var id = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "formulas/eliminarFormula/" + id,
        success: function (data) {

          //Remueve de la tabla
          $('#cuerpoTabla #'+ id).remove();
          $("#tablaResultados").trigger("update");

          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

$('#modalFormula').on('hidden.bs.modal', function(){//se ejecuta cuando se oculta modal con clase .modal
  $('#id_formula').val(0);
  $('#columna br').remove();
  $('#columna .row').each(function(index,value){
    ocultarErrorValidacion($(this).find('#operador'));
    ocultarErrorValidacion($(this).find('#contador'));
  });


})

$('#modalMaquinas').on('hidden.bs.modal', function(){//se ejecuta cuando se oculta modal con clase .modal
  $('#id_formula').val(0);
  $('.listaMaquinas').empty();
  $('#selectCasino').val(0).trigger('change');

})

//envio de datos a servidor (guardar / modificar)
$('#btn-guardar').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var formula=[];

    $('#columna #terminoFormula').each(function(){
        var termino = {
          contador: $(this).find('#contador').val(),
          operador: $(this).find('#operador').val(),
        }
        formula.push(termino);
    });


    var formData = {
      formula: formula
    }

    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = 'formulas/guardarFormula';


    if (state == "modificar") {
      var formData = {
        id_formula: $('#id_formula').val(),
        formula: formula,
      }
      url = 'formulas/modificarFormula';
    }
    console.log(formData);
    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            console.log(data);
            if (state == "nuevo"){//si se esta creando guarda en tabla

              $('#mensajeExito h4').text('La Fórmula fue CREADA correctamente.');
              $('#mensajeExito div').css('background-color','#4DB6AC');
                $('#cuerpoTabla').append(generarFilaTabla(data.formula));
            }else{ //Si está modificando

              $('#mensajeExito h4').text('La Fórmula fue MODIFICADA correctamente.');
              $('#mensajeExito div').css('background-color','#FFB74D');
              $('#cuerpoTabla #' +data.formula.id_formula ).replaceWith(generarFilaTabla(data.formula))

            }
            $('#frmFormula').trigger("reset");
            $('#modalFormula').modal('hide');
            //Mostrar éxito
            $('#mensajeExito').show();
        },
        error: function (data) {
            var response = data.responseJSON.errors;

            $('#columna .row').each(function(index,value){
              if(typeof response['formula.'+ index +'.operador'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('#operador') , response['formula.'+ index +'.operador'][0],false);
              }
              if(typeof response['formula.'+ index +'.contador'] !== 'undefined'){
                  mostrarErrorValidacion($(this).find('#contador'),response['formula.'+ index +'.contador'][0],false);
              }
            })

        }
    });
});
//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    tabla: $('#buscadorDescripcion').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  console.log(formData);
  $.ajax({
      type: 'GET',
      url: 'formulas/buscarFormulas',
      data: formData,
      dataType: 'json',
      success: function(resultados){
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTabla tr').remove();
        for (var i = 0; i < resultados.data.length; i++){
          $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i]));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function(data){
        console.log('Error:', data);
      }
    });
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

$(document).on('click' , '.asociarMaq' , function() {
    $('#id_formula').val($(this).val());
    $('#modalMaquinas .modal-title').text('| ASOCIAR MÁQUINAS');
    $('#modalMaquinas .modal-header').attr('style','font-family: Roboto-Black; background-color: #46b8da; color: #fff');
    $('#btn-asociar').removeClass();
    $('#btn-asociar').addClass('btn btn-informacion');

    $('#modalMaquinas').modal('show');
    $('#modal')
})

$(document).on('change','#selectCasino',function () {
  var casino =  $('#selectCasino option:selected').val();
  $('.listaMaquinas').empty();

  if($('#selectCasino option:selected').val() != 0){
    $('#buscadores').show();
    $('.buscadorMaquina').generarDataList('formulas/buscarMaquinaPorNumeroMarcaYModelo/' + casino, "resultados","id_maquina" ,"nro_admin" , 2, true);
    $('.buscadorMaquina').setearElementoSeleccionado(0 , "");
    $('.buscadorIsla').generarDataList("formulas/buscarIslaPorCasinoYNro/" + casino,'islas','id_isla','nro_isla',1,true);;
    $('.buscadorIsla').setearElementoSeleccionado(0,"");
  }else{
    $('#buscadores').hide();
  }

})

$('#btn-asociar').on('click' , function () {
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })
  var maquinas = [];
  $('.listaMaquinas li').each(function() {
    maquinas.push($(this).val());
  })

  var formData= {
    id_formula: $('#id_formula').val(),
    maquinas: maquinas,
  }

  console.log(formData);

  $.ajax({
      type: "post",
      data:formData,
      dataType:'json',
      url: "formulas/asociarMaquinas",
      success: function (data) {

      },
      error: function (data) {
        console.log('Error: ', data);
      }
  });
})

function concatenarFormula(formula){
  var formula_datalist= formula.cont1;
  formula.operador1 != null ? formula_datalist = formula_datalist + formula.operador1:null;
  formula.cont2 != null ? formula_datalist = formula_datalist + formula.cont2:null;
  formula.operador2 != null ? formula_datalist = formula_datalist + formula.operador2:null;
  formula.cont3 != null ? formula_datalist = formula_datalist + formula.cont3:null;
  formula.operador3 != null ? formula_datalist = formula_datalist + formula.operador3:null;
  formula.cont4 != null ? formula_datalist = formula_datalist +  formula.cont4:null;
  formula.operador4 != null ? formula_datalist = formula_datalist +  formula.operador4:null;
  formula.cont5 != null ? formula_datalist = formula_datalist +  formula.cont5:null;
  formula.operador5 != null ? formula_datalist = formula_datalist +  formula.operador5:null;
  formula.cont6 != null ? formula_datalist = formula_datalist +  formula.cont6:null;
  formula.operador6 != null ? formula_datalist = formula_datalist +  formula.operador6:null;
  formula.cont7 != null ? formula_datalist = formula_datalist +  formula.cont7 : null;
  formula.operador7 != null ? formula_datalist = formula_datalist +  formula.operador7: null;
  formula.cont8 != null ? formula_datalist = formula_datalist +  formula.cont8 : null;

  return formula_datalist;
}

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTabla(formula){

      // var a = formula.id_formula == 18 ? true : false;
      // .prop('disabled' , a)
      var fila = $(document.createElement('tr'));
            fila.attr('id', formula.id_formula)
            .append($('<td>')
            .addClass('col-xs-2')
                .text("Formula " + formula.id_formula)
            )
            .append($('<td>')
              .addClass('col-xs-8')

              .text(concatenarFormula(formula))
            )
            .append($('<td>')
              .addClass('col-xs-2')
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                    )
                    .append($('<span>').text(' MODIFICAR'))
                    .addClass('btn').addClass('btn-warning').addClass('btn-detalle').addClass('modificar')
                    .attr('value',formula.id_formula)
                )
                .append($('<span>').text(' '))
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa')
                        .addClass('fa-link')
                    )
                    .addClass('btn').addClass('btn-detalle').addClass('btn-info').addClass('asociarMaq')
                    .attr('value',formula.id_formula)
                )
                .append($('<span>').text(' '))
                .append($('<button>')
                    .append($('<i>')
                        .addClass('fa')
                        .addClass('fa-trash-alt')
                    )
                    .append($('<span>').text(' ELIMINAR'))
                    .addClass('btn').addClass('btn-danger').addClass('btn-borrar').addClass('eliminar')
                    .attr('value',formula.id_formula)
                )
            )

      return fila;
}
//Agregar Máquina
$(document).on("click",  ".agregarMaquina" , function(){

      //Crear un item de la lista
      var input = $(this).parent().parent().find('input');
      var id = input.obtenerElementoSeleccionado();
      var listaMaquinas = $('.listaMaquinas');
      if(id != 0){
        if(!existeEnDataList(id)){
          $.get("/formulas/obtenerConfiguracionMaquina/" + id, function(data){
            agregarMaquina( data.maquina.id_maquina,data.maquina.nro_admin,data.maquina.marca,data.maquina.modelo,listaMaquinas);
          });
          input.setearElementoSeleccionado(0,"");
        }else{
          input.setearElementoSeleccionado(0,"");
        }
      }
      console.log(id);
});
//Agregar Isla
$(document).on("click", ".agregarIsla" ,function(){
      var listaMaquinas =  $('.listaMaquinas');
      var input = $(this).parent().parent().find('input');
      var id = input.obtenerElementoSeleccionado();
      if(id != 0){
        agregarIsla(id,listaMaquinas);
        input.setearElementoSeleccionado(0,"")
      }
});

$(document).on('click','.borrarMaquina',function(e){
  e.preventDefault();
  $(this).parent().parent().remove();
});

function existeEnDataList(id){
  var bandera = false;
  $('.listaMaquinas li').each(function(){
      if (parseInt($(this).val()) ==  parseInt(id)){
        bandera = true;
      }
  });

  return bandera;
}

function agregarIsla(id_isla , listaMaquinas){
  $.get("formulas/obtenerIsla/" + id_isla , function(data){
    for (var i = 0; i < data.maquinas.length; i++) {
      if(!existeEnDataList(data.maquinas[i].id_maquina)){
        agregarMaquina(data.maquinas[i].id_maquina ,data.maquinas[i].nro_admin ,data.maquinas[i].marca , data.maquinas[i].modelo , listaMaquinas)
      }
    }
  });
}

function agregarMaquina(id_maquina,nro_admin,nombre,modelo,listaMaquinas){

    listaMaquinas.append($('<li>')
        //Se agrega el id del progresivo de la lista
        .val(id_maquina)
        .addClass('row')
        .css('list-style','none')
        //Columna de NUMERO ADMIN
        .append($('<div>')
            .addClass('col-xs-2').css('margin-top','6px')
            .text(nro_admin)
        )
        //Columna de NOMBRE PROGRESIVO
        .append($('<div>')
            .addClass('col-xs-4').css('margin-top','6px')
            .text(nombre)
        )
        //Columna de TIPO PROGRESIVO
        .append($('<div>')
            .addClass('col-xs-4').css('margin-top','6px')
            .text(modelo)
        )
        //Columna BOTON QUITAR
        .append($('<div>')
            .addClass('col-xs-2')
            .append($('<button>')
                .addClass('btn').addClass('btn-danger').addClass('borrarFila').addClass('borrarMaquina')
                .append($('<i>')
                    .addClass('fa fa-fw fa-trash')
                )
            )
        )
    );
}
