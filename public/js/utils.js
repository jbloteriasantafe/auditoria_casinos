function denominacionToFloat(den) {
    if (den=="" || den==null) {
      return parseFloat(0.01)
    }
    denf=den.replace(",",".")
    return parseFloat(denf)
  }

//Convierte los errores standard de laravel a lenguaje normal.
function parseError(response){
    errors = {
        'validation.unique'       :'El valor tiene que ser único y ya existe el mismo.',
        'validation.required'     :'El campo es obligatorio.',
        'validation.max.string'   :'El valor es muy largo.',
        'validation.exists'       :'El valor no es valido.',
        'validation.min.numeric'  :'El valor no es valido.',
        'validation.integer'      :'El valor tiene que ser un número entero.',
        'validation.regex'        :'El valor no es valido.',
        'validation.required_if'  :'El valor es requerido.',
        'validation.required_with':'El valor es requerido.',
        'validation.before'       :'El valor supera el limite.',
        'validation.after'        :'El valor precede el limite.',
        'validation.max.numeric'  :'El valor supera el limite.',
    };
    if(response in errors) return errors[response];
    return response;
}
  
//Saca los errores custom de un response y los retorna en una lista.
function sacarErrores(errorResponse){
    const errorjson = errorResponse.responseJSON;
    const keys  = Object.keys(errorjson);
    let msjs = [];
    keys.forEach(function(k){
        const list_msjs = errorjson[k];
        list_msjs.forEach(function(str){
        msjs.push(parseError(str));
        });
    });
    return msjs;
}
  
//Toma una lista de strings y los muestra linea tras linea en el modal de errores.
function mensajeError(errores = []) {
    $('#mensajeError .textoMensaje').empty();
    for (let i = 0; i < errores.length; i++) {
        $('#mensajeError .textoMensaje').append($('<h4></h4>').append(errores[i]));
    }
    $('#mensajeError').hide();
    $('#mensajeError').trigger('show');
    setTimeout(function() {
        $('#mensajeError').show();
    }, 250);
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
function modalEliminar(
    confirmar = function(){},
    cancelar = function(){},
    mensaje = "¿Seguro desea eliminar el MOVIMIENTO?"
    )
    {
    $('#modalEliminar #mensajeEliminar').empty();
    $('#modalEliminar #mensajeEliminar').append($('<strong>').append(mensaje));
    $('#modalEliminar .confirmar').off().click(function(){
        confirmar();
        setTimeout(function(){
        $('#modalEliminar').modal('hide');
        },250);
    });
    $('#modalEliminar .cancelar').off().click(function(){
        cancelar();
        setTimeout(function(){
        $('#modalEliminar').modal('hide');
        },250);
    });
    $('#modalEliminar').modal('show');
}
  
//Recibe un objeto como deftl.
function mensajeExito(args) {
    const deflt = {
        titulo : 'ÉXITO',
        mensajes : [],
        mostrarBotones : false,
        fijarMensaje : false
    };

    const noargs = isUndef(args);
    const titulo = noargs || isUndef(args.titulo)? deflt.titulo : args.titulo;
    const mensajes = noargs ||isUndef(args.mensajes)? deflt.mensajes : args.mensajes;
    const mostrarBotones = noargs || isUndef(args.mostrarBotones)? deflt.mostrarBotones : args.mostrarBotones;
    const fijarMensaje = noargs || isUndef(args.fijarMensaje)? deflt.fijarMensaje : args.fijarMensaje;

    $('#mensajeExito .textoMensaje').empty();
    $('#mensajeExito .textoMensaje').append($('<h3>').append(titulo));
    mensajes.forEach(function(m){
        $('#mensajeExito .textoMensaje').append($('<h4>').append(m));
    });
    $('#mensajeExito').toggleClass('mostrarBotones',mostrarBotones == true);//Conversion a boolean por si pasa cualquiera.
    $('#mensajeExito').toggleClass('fijarMensaje',fijarMensaje == true);
    $('#mensajeExito').hide();
    $('#mensajeExito').trigger('show');
    setTimeout(function() {
        $('#mensajeExito').show();
    }, 250);
}
  
function isUndef(x){
    return typeof x == 'undefined';
}

function tieneValor(val){
    return typeof val !== 'undefined';
}

function limpiarNull(x,c = '-'){
    return x === null? c : x;
}

function limpiarUndef(x,c='-'){
    return isUndef(x)? c : x;
}

function limpiarNullUndef(x,c='-'){
    return x === null || isUndef(x)? c : x;
}