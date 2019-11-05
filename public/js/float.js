function isFloatComma(f) {
    //Un mas o menos no obligatorio
    //Al menos un numero
    //Una coma con al menos un numero no obligatorio
    const floatCommaRegExp = /(\+|-)?\d+(,\d+)?/;
    f = f.toString();
    const match = floatCommaRegExp.exec(f);
    if (match === null || match.length == 0) return false;
    return match[0].length == f.length;
}

function isFloatDot(f) {
    const floatDotRegExp = /(\+|-)?\d+(\.\d+)?/;
    f = f.toString();
    const match = floatDotRegExp.exec(f);
    if (match === null || match.length == 0) return false;
    return match[0].length == f.length;
}

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