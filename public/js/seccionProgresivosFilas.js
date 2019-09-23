/* 

Si es INPUT

Al setear o getear, se hace con puntos i.e '3.147' pero se muestra
con coma correctamente
SET(VAL: fmtConPunto)
GET()->VAL: fmtConPunto

Si es TEXT

Al setear o getear, se hace literal lo que se le pasa por lo que
hay que poner una interfaz que pase de numero con punto a con coma.

Lo transforma con coma y lo setea
SET(VAL: float) 

Se fija si es un numero con coma y lo pasa a punto
Si no es valido o vacio, habria que retornar null
GET()->VAL: float

*/



function isFloatComma(f) {
    //Un mas o menos no obligatorio
    //Al menos un numero
    //Una coma con al menos un numero no obligatorio
    const floatCommaRegExp = /(\+|-)?\d+(,\d+)?/;
    f = f.toString();
    const match = floatCommaRegExp.exec(f);
    //console.log(f);
    //console.log(match);
    if (match === null || match.length == 0) return false;
    return match[0].length == f.length;
}

function isFloatDot(f) {
    const floatDotRegExp = /(\+|-)?\d+(\.\d+)?/;
    f = f.toString();
    const match = floatDotRegExp.exec(f);
    //console.log(f);
    //console.log(match);
    if (match === null || match.length == 0) return false;
    return match[0].length == f.length;
}
/*
//Pasa un float a formato español (con coma)
function float2text(f) {
    const res = f.toLocaleString('es', { 'useGrouping': false });
    return res;
}*/

//Reemplazo las comas con puntos
function comma2dot(f) {
    const res = f.replace(',', '.');
    return res;
}

function dot2comma(f) {
    const res = f.replace('.', ',');
    return res;
}

function getDotFloat(f) {
    if (f === null) return '';
    f = f.toString();
    if (isFloatComma(f)) return comma2dot(f);
    if (isFloatDot(f)) return f;
    return '';
}

function getCommaFloat(f) {
    if (f === null) return '';
    f = f.toString();
    if (isFloatComma(f)) return f;
    if (isFloatDot(f)) return dot2comma(f);
    return '';
}

function filaObj(f, str) {
    return $(f).find(str);
}

function filaNumero(f) {
    return filaObj(f, '.cuerpoTablaPozoNumero');
}

function filaNombre(f) {
    return filaObj(f, '.cuerpoTablaPozoNombre');
}

function filaBase(f) {
    return filaObj(f, '.cuerpoTablaPozoBase');
}

function filaMaximo(f) {
    return filaObj(f, '.cuerpoTablaPozoMaximo');
}

function filaVisible(f) {
    return filaObj(f, '.cuerpoTablaPorcVisible');
}

function filaOculto(f) {
    return filaObj(f, '.cuerpoTablaPorcOculto');
}

function objVal(obj, newval = undefined, numeric = false) {
    const edit = obj.find('input').length > 0;
    if (edit) obj = obj.find('input');

    if ((typeof newval !== 'undefined')) { //SET
        let commanewval = newval;
        let dotnewval = newval;
        if (numeric) {
            commanewval = getCommaFloat(newval);
            dotnewval = getDotFloat(newval);
        }
        return edit ? obj.val(dotnewval) : obj.text(commanewval);
    } else { //GET
        return edit ? obj.val() : obj.text();
    }
}

function filaNumeroVal(f, newval = undefined) {
    return objVal(filaNumero(f), newval);
}

function filaNombreVal(f, newval = undefined) {
    return objVal(filaNombre(f), newval);
}

function filaBaseVal(f, newval = undefined) {
    const val = objVal(filaBase(f), newval, true);
    return getDotFloat(val);
}

function filaMaximoVal(f, newval = undefined) {
    const val = objVal(filaMaximo(f), newval, true);
    return getDotFloat(val);
}

function filaVisibleVal(f, newval = undefined) {
    const val = objVal(filaVisible(f), newval, true);
    return getDotFloat(val);
}

function filaOcultoVal(f, newval = undefined) {
    const val = objVal(filaOculto(f), newval, true);
    return getDotFloat(val);
}

function filaIdVal(f, newval = undefined) {
    if ((typeof newval !== 'undefined')) {
        f.attr('data-id', newval);
        return newval;
    } else {
        return f.attr('data-id');
    }
}

function arregloNivel(fila) {
    let nivel = {
        id_nivel_progresivo: filaIdVal(fila),
        nro_nivel: filaNumeroVal(fila),
        nombre_nivel: filaNombreVal(fila),
        base: filaBaseVal(fila),
        porc_oculto: filaOcultoVal(fila),
        porc_visible: filaVisibleVal(fila),
        maximo: filaMaximoVal(fila)
    };
    return nivel;
}

function setearValoresFilaNivel(fila, nivel) {
    filaIdVal(fila, nivel.id_nivel_progresivo);
    filaNumeroVal(fila, nivel.nro_nivel);
    filaNombreVal(fila, nivel.nombre_nivel);
    filaBaseVal(fila, nivel.base);
    filaMaximoVal(fila, nivel.maximo);
    filaVisibleVal(fila, nivel.porc_visible);
    filaOcultoVal(fila, nivel.porc_oculto);
}