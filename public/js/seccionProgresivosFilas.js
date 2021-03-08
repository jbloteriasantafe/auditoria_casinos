//Obtiene el valor de una celda, independientemente si es un input o si solo es texto
//El parametro numeric se usa para validar que sea un numero.
function objVal(obj, newval = undefined, numeric = false) {
    const input = obj.find('input').length > 0;
    if ((typeof newval !== 'undefined')) { //SET
        newval = (newval === null) ? '' : newval;
        const dotnewval = numeric? getDotFloat(newval) : newval;//ver float.js, si pone un numero con coma lo pasa a punto
        if(input) obj.find('input').val(dotnewval).val();
        else      obj.text(dotnewval).text();
    }
    return input? obj.find('input').val() : obj.text();
}

function arregloNivel(fila) {
    return {
        id_nivel_progresivo: fila.attr('data-id'),
        nro_nivel:    objVal(fila.find('.cuerpoTablaPozoNumero')),
        nombre_nivel: objVal(fila.find('.cuerpoTablaPozoNombre')),
        base:         objVal(fila.find('.cuerpoTablaPozoBase')),
        maximo:       objVal(fila.find('.cuerpoTablaPozoMaximo')),
        porc_visible: objVal(fila.find('.cuerpoTablaPorcVisible')),
        porc_oculto:  objVal(fila.find('.cuerpoTablaPorcOculto')),
    };
}

function setearValoresFilaNivel(fila, nivel) {
    fila.attr('data-id', nivel.id_nivel_progresivo)
    objVal(fila.find('.cuerpoTablaPozoNumero') , nivel.nro_nivel);
    objVal(fila.find('.cuerpoTablaPozoNombre') , nivel.nombre_nivel);
    objVal(fila.find('.cuerpoTablaPozoBase')   , nivel.base        , true);
    objVal(fila.find('.cuerpoTablaPozoMaximo') , nivel.maximo      , true);
    objVal(fila.find('.cuerpoTablaPorcVisible'), nivel.porc_visible, true);
    objVal(fila.find('.cuerpoTablaPorcOculto') , nivel.porc_oculto , true);
}

//INDIVIDUALES
function arregloProgresivoIndividual(fila) {
    return {
        id_maquina: fila.attr('data-id'),
        nro_admin:    objVal(fila.find('.cuerpoTablaNroAdmin')),
        sector:       objVal(fila.find('.cuerpoTablaSector')),
        isla:         objVal(fila.find('.cuerpoTablaIsla')),
        marca_juego:  objVal(fila.find('.cuerpoTablaMarcaJuego')),
        porc_recup:   objVal(fila.find('.cuerpoPorcRecup')),
        maximo:       objVal(fila.find('.cuerpoMaximo')),
        base:         objVal(fila.find('.cuerpoBase')),
        porc_visible: objVal(fila.find('.cuerpoPorcVisible')),
        porc_oculto:  objVal(fila.find('.cuerpoPorcOculto'))
    };
}

function setearFilaProgresivoIndividual(fila, data) {
    fila.attr('data-id', data.id_maquina);
    objVal(fila.find('.cuerpoTablaNroAdmin')  , data.nro_admin);
    objVal(fila.find('.cuerpoTablaSector')    , data.sector);
    objVal(fila.find('.cuerpoTablaIsla')      , data.isla);
    objVal(fila.find('.cuerpoTablaMarcaJuego'), data.marca_juego);
    objVal(fila.find('.cuerpoPorcRecup')      , data.porc_recup  , true);
    objVal(fila.find('.cuerpoMaximo')         , data.maximo      , true);
    objVal(fila.find('.cuerpoBase')           , data.base        , true);
    objVal(fila.find('.cuerpoPorcVisible')    , data.porc_visible, true);
    objVal(fila.find('.cuerpoPorcOculto')     , data.porc_oculto , true);
}

//Verifica si los caracteres
//son todos espacios/tabs/etc o es null
function stringVacio(s) {
    if (s === null) return true;
    s = s.toString();
    if (s.length == 0) return true;
    const regexp = /\s+/;
    const match = regexp.exec(s);
    if (match === null || match.length == 0) return false;
    return match[0].length == s.length;
}

function validarFila(fila) {
    let ret = {
        id_nivel_progresivo: true,
        nro_nivel: true,
        nombre_nivel: true,
        base: true,
        maximo: true,
        porc_visible: true,
        porc_oculto: true,
        razones: []
    }
    const nivel = arregloNivel(fila);
    if (stringVacio(nivel.nombre_nivel)) {
        ret.nombre_nivel = false;
        ret.razones.push('El nombre es obligatorio.');
    }
    const base = parseFloat(getDotFloat(nivel.base));
    if (stringVacio(nivel.base) || isNaN(base) || base < 0) {
        ret.base = false;
        ret.razones.push('La base es vacia o un valor incorrecto.');
    }
    const maximo = parseFloat(getDotFloat(nivel.maximo));
    if (!stringVacio(nivel.maximo) && (isNaN(maximo) || maximo < 0)) {
        ret.maximo = false;
        ret.razones.push('El maximo es un valor incorrecto.');
    }
    const visible = parseFloat(getDotFloat(nivel.porc_visible));
    if (stringVacio(nivel.porc_visible) ||
        isNaN(visible) || visible < 0 || visible > 100) {
        ret.porc_visible = false;
        ret.razones.push('El porcentaje visible es vacio o un valor incorrecto.');
    }
    const oculto = parseFloat(getDotFloat(nivel.porc_oculto));
    if (!stringVacio(nivel.porc_oculto) &&
        (isNaN(oculto) || oculto < 0 || oculto > 100)) {
        ret.porc_oculto = false;
        ret.razones.push('El porcentaje oculto es un valor incorrecto.');
    }
    if (ret.maximo && ret.base &&
        !stringVacio(nivel.maximo) && (maximo < base)) {
        ret.maximo = false;
        ret.base = false;
        ret.razones.push('El maximo es un valor menor a la base.');
    }
    return ret;
}

function validarFilaInd(fila) {
    let ret = {
        id_maquina: true,
        nro_admin: true,
        sector: true,
        isla: true,
        marca_juego: true,
        porc_recup: true,
        base: true,
        maximo: true,
        porc_visible: true,
        porc_oculto: true,
        razones: []
    }
    const prog = arregloProgresivoIndividual(fila);
    const base = parseFloat(getDotFloat(prog.base));
    if (stringVacio(prog.base) || isNaN(base) || base < 0) {
        ret.base = false;
        ret.razones.push('La base es vacia o un valor incorrecto.');
    }
    const maximo = parseFloat(getDotFloat(prog.maximo));
    if (!stringVacio(prog.maximo) && (isNaN(maximo) || maximo < 0)) {
        ret.maximo = false;
        ret.razones.push('El maximo es un valor incorrecto.');
    }
    const visible = parseFloat(getDotFloat(prog.porc_visible));
    if (stringVacio(prog.porc_visible) ||
        isNaN(visible) || visible < 0 || visible > 100) {
        ret.porc_visible = false;
        ret.razones.push('El porcentaje visible es vacio o un valor incorrecto.');
    }
    const oculto = parseFloat(getDotFloat(prog.porc_oculto));
    if (!stringVacio(prog.porc_oculto) &&
        (isNaN(oculto) || oculto < 0 || oculto > 100)) {
        ret.porc_oculto = false;
        ret.razones.push('El porcentaje oculto es un valor incorrecto.');
    }
    if (ret.maximo && ret.base &&
        !stringVacio(prog.maximo) && (maximo < base)) {
        ret.maximo = false;
        ret.base = false;
        ret.razones.push('El maximo es un valor menor a la base.');
    }
    const recup = parseFloat(getDotFloat(prog.porc_recup));
    if (stringVacio(prog.porc_recup) ||
        isNaN(recup) || recup < 0 || recup > 100) {
        ret.porc_recup = false;
        ret.razones.push('El porcentaje de recuperacion es vacio o un valor incorrecto.');
    }
    return ret;
}