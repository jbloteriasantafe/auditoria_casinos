function denominacionToFloat(den) {
    if (den=="" || den==null) {
      return parseFloat(0.01)
    }
    denf=den.replace(",",".")
    return parseFloat(denf)
  }

//Convierte los errores standard de laravel a lenguaje normal.
function parseError(response){
    if(response == 'validation.unique'){
        return 'El valor tiene que ser único y ya existe el mismo.';
    }
    else if(response == 'validation.required'){
        return 'El campo es obligatorio.'
    }
    else if(response == 'validation.max.string'){
        return 'El valor es muy largo.'
    }
    else if(response == 'validation.exists'){
        return 'El valor no es valido.';
    }
    else if(response == 'validation.min.numeric'){
        return 'El valor no es valido.';
    }
    else if(response == 'validation.integer'){
        return 'El valor tiene que ser un número entero.';
    }
    else if(response == 'validation.regex'){
        return 'El valor no es valido.';
    }
    else if(response == 'validation.required_if'){
        return 'El valor es requerido';
    }
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
function mensajeError(errores) {
    $('#mensajeError .textoMensaje').empty();
    for (let i = 0; i < errores.length; i++) {
        $('#mensajeError .textoMensaje').append($('<h4></h4>').text(errores[i]));
    }
    $('#mensajeError').hide();
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
    $('#modalEliminar #mensajeEliminar').append($('<strong>').text(mensaje));
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
    $('#mensajeExito .textoMensaje').append($('<h3>').text(titulo));
    mensajes.forEach(function(m){
        $('#mensajeExito .textoMensaje').append($('<h4>').text(m));
    });
    $('#mensajeExito').toggleClass('mostrarBotones',mostrarBotones == true);//Conversion a boolean por si pasa cualquiera.
    $('#mensajeExito').toggleClass('fijarMensaje',fijarMensaje == true);
    $('#mensajeExito').hide();
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