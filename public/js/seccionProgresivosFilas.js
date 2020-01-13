//Funcion que antes era mas compleja pero quedo asi despues de simplificar.
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

function limpiarNull(s) {
    return (s === null) ? '' : s;
}

//Obtiene el valor de una celda, independientemente si es un input o si solo es texto
//El parametro numeric se usa para validar que sea un numero.
function objVal(obj, newval = undefined, numeric = false) {
    const edit = obj.find('input').length > 0;
    if (edit) obj = obj.find('input');

    if ((typeof newval !== 'undefined')) { //SET
        newval = limpiarNull(newval);
        let dotnewval = newval;
        if (numeric) {
            dotnewval = getDotFloat(newval);//ver float.js, si pone un numero con coma lo pasa a punto
        }
        return edit ? obj.val(dotnewval).val() : obj.text(dotnewval).text();
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

function filaBaseVal(f, newval = undefined, numeric = false) {
    const val = objVal(filaBase(f), newval, numeric);
    return val;
}

function filaMaximoVal(f, newval = undefined, numeric = false) {
    const val = objVal(filaMaximo(f), newval, numeric);
    return val;
}

function filaVisibleVal(f, newval = undefined, numeric = false) {
    const val = objVal(filaVisible(f), newval, numeric);
    return val;
}

function filaOcultoVal(f, newval = undefined, numeric = false) {
    const val = objVal(filaOculto(f), newval, numeric);
    return val;
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
    filaBaseVal(fila, nivel.base, true);
    filaMaximoVal(fila, nivel.maximo, true);
    filaVisibleVal(fila, nivel.porc_visible, true);
    filaOcultoVal(fila, nivel.porc_oculto, true);
}

//INDIVIDUALES

function filaIndNroAdmin(f) {
    return filaObj(f, '.cuerpoTablaNroAdmin');
}

function filaIndSector(f) {
    return filaObj(f, '.cuerpoTablaSector');
}

function filaIndIsla(f) {
    return filaObj(f, '.cuerpoTablaIsla');
}

function filaIndMarcaJuego(f) {
    return filaObj(f, '.cuerpoTablaMarcaJuego');
}

function filaIndRecup(f) {
    return filaObj(f, '.cuerpoPorcRecup');
}

function filaIndMaximo(f) {
    return filaObj(f, '.cuerpoMaximo');
}

function filaIndBase(f) {
    return filaObj(f, '.cuerpoBase');
}

function filaIndVisible(f) {
    return filaObj(f, '.cuerpoPorcVisible');
}

function filaIndOculto(f) {
    return filaObj(f, '.cuerpoPorcOculto');
}


function filaIndIdVal(f, newval = undefined) {
    if ((typeof newval !== 'undefined')) {
        f.attr('data-id', newval);
        return newval;
    } else {
        return f.attr('data-id');
    }
}

function filaIndNroAdminVal(f, newval = undefined) {
    return objVal(filaIndNroAdmin(f), newval);
}

function filaIndSectorVal(f, newval = undefined) {
    return objVal(filaIndSector(f), newval);
}

function filaIndIslaVal(f, newval = undefined) {
    return objVal(filaIndIsla(f), newval);
}

function filaIndMarcaJuegoVal(f, newval = undefined) {
    return objVal(filaIndMarcaJuego(f), newval);
}

function filaIndRecupVal(f, newval = undefined, numeric = false) {
    const val = objVal(filaIndRecup(f), newval, numeric);
    return val;
}

function filaIndMaximoVal(f, newval = undefined, numeric = false) {
    const val = objVal(filaIndMaximo(f), newval, numeric);
    return val;
}

function filaIndBaseVal(f, newval = undefined, numeric = false) {
    const val = objVal(filaIndBase(f), newval, numeric);
    return val;
}

function filaIndVisibleVal(f, newval = undefined, numeric = false) {
    const val = objVal(filaIndVisible(f), newval, numeric);
    return val;
}

function filaIndOcultoVal(f, newval = undefined, numeric = false) {
    const val = objVal(filaIndOculto(f), newval, numeric);
    return val;
}

function setearFilaProgresivoIndividual(fila, data) {
    filaIndIdVal(fila, data.id_maquina);
    filaIndNroAdminVal(fila, data.nro_admin);
    filaIndSectorVal(fila, data.sector);
    filaIndIslaVal(fila, data.isla);
    filaIndMarcaJuegoVal(fila, data.marca_juego);
    filaIndRecupVal(fila, data.porc_recup, true);
    filaIndMaximoVal(fila, data.maximo, true);
    filaIndBaseVal(fila, data.base, true);
    filaIndVisibleVal(fila, data.porc_visible, true);
    filaIndOcultoVal(fila, data.porc_oculto, true);
}

function arregloProgresivoIndividual(fila) {
    return {
        id_maquina: filaIndIdVal(fila),
        nro_admin: filaIndNroAdminVal(fila),
        sector: filaIndSectorVal(fila),
        isla: filaIndIslaVal(fila),
        marca_juego: filaIndMarcaJuegoVal(fila),
        porc_recup: filaIndRecupVal(fila),
        maximo: filaIndMaximoVal(fila),
        base: filaIndBaseVal(fila),
        porc_visible: filaIndVisibleVal(fila),
        porc_oculto: filaIndOcultoVal(fila)
    };
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