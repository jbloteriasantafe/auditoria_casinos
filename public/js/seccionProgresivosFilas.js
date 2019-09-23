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

function filaIndRecupVal(f, newval = undefined) {
    const val = objVal(filaIndRecup(f), newval, true);
    return getDotFloat(val);
}

function filaIndMaximoVal(f, newval = undefined) {
    const val = objVal(filaIndMaximo(f), newval, true);
    return getDotFloat(val);
}

function filaIndBaseVal(f, newval = undefined) {
    const val = objVal(filaIndBase(f), newval, true);
    return getDotFloat(val);
}

function filaIndVisibleVal(f, newval = undefined) {
    const val = objVal(filaIndVisible(f), newval, true);
    return getDotFloat(val);
}

function filaIndOcultoVal(f, newval = undefined) {
    const val = objVal(filaIndOculto(f), newval, true);
    return getDotFloat(val);
}

function setearFilaProgresivoIndividual(fila, data) {
    filaIndIdVal(fila, data.id_maquina);
    filaIndNroAdminVal(fila, data.nro_admin);
    filaIndSectorVal(fila, data.sector);
    filaIndIslaVal(fila, data.isla);
    filaIndMarcaJuegoVal(fila, data.marca_juego);
    filaIndRecupVal(fila, data.porc_recup);
    filaIndMaximoVal(fila, data.maximo);
    filaIndBaseVal(fila, data.base);
    filaIndVisibleVal(fila, data.porc_visible);
    filaIndOcultoVal(fila, data.porc_oculto);
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