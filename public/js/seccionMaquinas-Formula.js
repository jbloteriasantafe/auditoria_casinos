/****************
TODOS LOS EVENTOS DE FORMULA
*************/

$(document).ready(function(){

  $('#inputFormula').generarDataList('http://' + window.location.host +'/formulas/buscarFormulaPorCampos','formulas','id_formula','formula',2,true);
  $('#inputFormula').setearElementoSeleccionado(0,"");
  $('#borrarFormulaSeleccionada').hide();
})

function limpiarModalFormula(){

  //Oculta todos los botones
  $('#agregarFormula').hide();
  $('#cancelarFormula').hide();
  $('#crearFormula').hide();
  $('#borrarFormulaSeleccionada').hide();
  $('#formulaSeleccionada').text('No existe formula seleccionada.');
  $('#formulaSeleccionada').attr('data-id' , '');

}


function obtenerDatosFormula(){
    var formula= {
        id_formula: $('#formulaSeleccionada').attr('data-id'),
        cuerpoFormula: $('#formulaSeleccionada').text(),
    };
    return formula;
  }

function concatenarFormula(formula){
      if(formula===null){return '-';}
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

function habilitarControlesFormula(valor){
  if(valor){
    $('#seccionAgregarFormula').show();
    $('#borrarFormulaSeleccionada').css('display','inline');
  }else {
    $('#seccionAgregarFormula').hide();
    $('#borrarFormulaSeleccionada').css('display','none');
  }
}

function mostrarFormula(formula){
  var concatenada = concatenarFormula(formula);
  $('#formulaSeleccionada').text(concatenada);
  $('#formulaSeleccionada').attr('data-id', formula.id_formula); //id del seleccionado
  if(formula!=null){
    $('#borrarFormulaSeleccionada').show();
  }
}

// Al presionar enter en el campo buscador, reviso el input y las options del datalist
$('#inputFormula').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
  }
});

$('.agregarFormula').click(function(){
    var inputFormula = $('#inputFormula').val() ;
    console.log('inp',inputFormula);
    //se valida a partir del id valido, 0 corresponde a vacio o algo no recuparo desde la base de datos
    if($('#inputFormula').attr('data-elemento-seleccionado')!='0'){
      $('#formulaSeleccionada').text($('#inputFormula').val());
    $('#formulaSeleccionada').attr('data-id', $('#inputFormula').obtenerElementoSeleccionado());
    $('#borrarFormulaSeleccionada').css('display','inline');
    $('#inputFormula').setearElementoSeleccionado(0,""); //limpia input
    }else{
      mostrarErrorValidacion($('#inputFormula'), 'Formato Incorrecto.' , true);
    }
});

//Botón para borrar la formula seleccionada
$('#borrarFormulaSeleccionada').click(function(){
    var formula=$('#formulaSeleccionada').text();
    $('#formulaSeleccionada').text('No existe formula seleccionada.');
    $('#formulaSeleccionada').attr('data-id' , '');
    $('#borrarFormulaSeleccionada').css('display','none');
    $('#cancelarFormula').css('display','inline'); //Se muestra el botón de agregar
});
