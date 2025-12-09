let divRM = null;
function divRelMovInit(){
    // Si alguna vez se necesitan multiples divRelMovs habra que hacer que se pase como argumento a todas las funciones
    // No tan complicado...
    divRM = $('#divRelMov');
    divRM.find('.relFecha').datetimepicker({
        todayBtn:  1,
        language:  'es',
        autoclose: 1,
        todayHighlight: 1,
        pickerPosition: "bottom-left",
        startView: 1,
        minView: 0,
        minuteStep: 5,
        ignoreReadonly: true,
        maxDate: 0
    });
}
function divRelMovObtenerDatos(){
    let contadores= [];
    divRM.find('.tablaCont tbody tr').each(function(){
        const cont={
            nombre: $(this).attr('data-contador'),
            valor: $(this).find('.valorModif').val()
        }
        contadores.push(cont);
    });

    let progresivos = [];
    divRM.find('.tablaProg tbody tr').each(function(){
        let fila = $(this);
        let obj = {
            id_pozo : fila.find('.nombreProgresivo').attr('data-id-pozo'),
            niveles : [],
            id_tipo_causa_no_toma_progresivo: fila.find('.causaNoToma').val()
        };
        $(this).find('input.habilitado').each(function(){
            obj.niveles.push({
                id_nivel_progresivo: $(this).attr('data-id-nivel'),
                val : $(this).val()
            });
        });
        progresivos.push(obj);
    });

    return {
        estado_relevamiento: divRM.find('.estado').val(),
        id_estado_relevamiento: divRM.find('.estado').attr('data-id'),
        nro_admin: divRM.find('.nro_admin').val(),
        isla_maq: divRM.find('.nro_isla').val(),
        nro_isla_importado: divRM.find('.nro_isla_importado').val(),
        nro_serie: divRM.find('.nro_serie').val(),
        marca: divRM.find('.marca').val(),
        modelo: divRM.find('.modelo').val(),
        //Usuarios
        usuario_carga: {nombre: divRM.find('.fiscaCarga').val(), id_usuario: divRM.find('.fiscaCarga').attr('data-id')},
        usuario_toma:  {nombre: divRM.find('.fiscaToma').val() , id_usuario: divRM.find('.fiscaToma').obtenerElementoSeleccionado()},
        //Valores relevados
        fecha_ejecucion: divRM.find('.fechaRel').val(),
        mac: divRM.find('.mac').val(),
        isla_rel: divRM.find('.isla_rel').val(),
        sector_rel: divRM.find('.sector_rel').val(),
        contadores: contadores,
        juego: divRM.find('.juego').val(),
        apuesta: divRM.find('.apuesta').val(),
        lineas: divRM.find('.cant_lineas').val(),
        devolucion: divRM.find('.devolucion').val(),
        denominacion: divRM.find('.denominacion').val(),
        creditos: divRM.find('.creditos').val(),
        progresivos: progresivos,
        observaciones: divRM.find('.observaciones').val(),
        adjunto: divRM.find('.adjunto')?.[0].files?.[0],
        link_adjunto: divRM.find('.imagen_adjunto').attr('src')?.slice(window.location.pathname.length+1) ?? '',
        observacionesAdm: divRM.find('.observacionesAdm').val(),
        nro_exp_org: divRM.find('.exp_org').val(),
        nro_exp_interno: divRM.find('.exp_interno').val(),
        nro_exp_control: divRM.find('.exp_control').val(),
        nro_disposicion: divRM.find('.disposicion').val(),
        nro_disposicion_anio: divRM.find('.disposicion_anio').val(),
    };
}
function divRelMovLimpiarErrores(){
    divRM.find('.alerta').each(function(){
        ocultarErrorValidacion($(this));
    });
}
function divRelMovLimpiar(){
    divRelMovLimpiarErrores();
    divRM.find('input').not('.tipoMov,.sentidoMov,.exp_org,.exp_interno,.exp_control,.disposicion,.disposicion_anio').val('');
    divRM.find('.tablaCont tbody').empty();
    divRM.find('.juego').empty();
    divRM.find('.tablaProg tbody').empty();
    divRM.find('.relFecha').datetimepicker('update','');
    divRM.find('textarea').val('');
}
function divRelMovAgregarContadores(maquina,toma,ultimo = null){
  const vcont = [null,
    ultimo  ? ultimo.vcont1 : '',
    ultimo  ? ultimo.vcont2 : '',
    ultimo  ? ultimo.vcont3 : '',
    ultimo  ? ultimo.vcont4 : '',
    ultimo  ? ultimo.vcont5 : '',
    ultimo  ? ultimo.vcont6 : '',
    ultimo  ? ultimo.vcont7 : '',
    ultimo  ? ultimo.vcont8 : ''];
    for (let i = 1; i < 7; i++){
        let fila = divRM.find('.filaEjCont').clone().removeClass('filaEjCont');
        let nombre_cont = maquina["cont" + i];
        if(nombre_cont === null) continue;
        let val_cont = null;
        if(toma !=null){
          val_cont = toma["vcont"+i];
        }
        fila.find('.cont').text(nombre_cont).attr('data-contador',nombre_cont);
        (ultimo != null) ? fila.find('.vcont').val(vcont[i] != null ? vcont[i] : '') : fila.find('.vcont').val(val_cont != null? val_cont : '');
        divRM.find('.tablaCont tbody').append(fila);
    }
}
function divRelMovAgregarProgresivos(progresivos, aux = null, sentido = null){
    // Limpieza inicial
    divRM.find('.sinProg').show();
    divRM.find('.tablaProg').hide();
    divRM.find('.tablaProg tbody').empty(); // Importante limpiar antes

    if(progresivos === null || progresivos.length == 0){
        return;
    }

    divRM.find('.sinProg').hide();
    divRM.find('.tablaProg').show();

    progresivos.forEach(function(prog, index){
        let fila = divRM.find('.filaEjProg').clone().removeClass('filaEjProg');
        let nombre = prog.nombre;
        if(prog.pozo && !prog.pozo.es_unico){ nombre += '(' + prog.pozo.descripcion + ')';}
        if(prog.es_individual) nombre = 'INDIVIDUAL';

        fila.find('.nombreProgresivo')
            .text(nombre)
            .attr('title', nombre)
            .attr('data-id-pozo', prog.pozo.id_pozo);

        // Obtenemos los datos YA guardados en este relevamiento (temporal o final)
        const det_guardado = prog.pozo.det_rel_prog;

        prog.pozo.niveles.forEach(function(niv){
            const key = 'nivel' + niv.nro_nivel;
            const $nivel = fila.find('.' + key);

            $nivel
                .attr('placeholder', niv.nombre_nivel)
                .addClass('habilitado')
                .attr('data-id-nivel', niv.id_nivel_progresivo);

            // --- LÓGICA DE VISUALIZACIÓN CORREGIDA ---

            // 1. Intentamos mostrar lo que está guardado en base de datos (prioridad absoluta)
            if (det_guardado && det_guardado[key] != null) {
                 $nivel.val(det_guardado[key]);
            }
            // 2. Si no hay nada guardado y es un reingreso, intentamos mostrar el dato histórico (aux)
            else if (aux && aux[index] && aux[index][key] != null && aux[index][key] !== '') {
                if(sentido) {
                    $nivel.val(aux[index][key]);
                }
            }
        });

        // Recuperar causa no toma guardada o auxiliar
        let causaNoToma = null;
        if(det_guardado && det_guardado.id_tipo_causa_no_toma_progresivo != null){
             causaNoToma = det_guardado.id_tipo_causa_no_toma_progresivo;
        } else if (aux && aux[index]) {
             // Si quieres arrastrar la causa del egreso, descomenta esto:
             // causaNoToma = aux[index].id_tipo_causa_no_toma_progresivo;
        }

        if(causaNoToma != null) fila.find('.causaNoToma').val(causaNoToma);

        divRM.find('.tablaProg tbody').append(fila);
        divRM.find('.tablaProg tbody input').not('.habilitado').attr('disabled', true);
    });
}

function divRelMovSetearAdjunto(url,generado){
  const adjunto = divRM.find('.adjunto').off('change');
  const imagen_adjunto = divRM.find('.imagen_adjunto').off('click')
  .removeAttr('src')
  .css({'cursor':''});
  const eliminar_adjunto = divRM.find('.eliminar_adjunto').off('click')
  .attr('disabled',true)
  .css({'pointer-events':'none'});

  const sin_url = !url;
  const con_url = !sin_url;

  if(con_url){
    imagen_adjunto.attr('src',url)
    .css({'cursor':'pointer'})
    .click(function(e){
      window.open(url,'_blank');
    });

    eliminar_adjunto.removeAttr('disabled')
    .css({'pointer-events':''})
    .click(function(e){
      divRM.find('.adjunto').val('');
      divRelMovSetearAdjunto(null,generado);
    });
  }

  if(generado){
    adjunto.change(function(e){
      const tgt = $(e.currentTarget);
      if(tgt?.[0]?.files?.[0]){
        divRelMovSetearAdjunto(URL.createObjectURL(tgt[0].files[0]),generado);
      }
      else{
        divRelMovSetearAdjunto(null,generado);
      }
    }).show();
    imagen_adjunto.toggle(con_url);
    eliminar_adjunto.toggle(con_url);
  }
  else{//Visualizando un visado por ejemplo
    adjunto.toggle(sin_url);//Si hay imagen no lo muestro
    imagen_adjunto.toggle(con_url);
    eliminar_adjunto.hide();
  }
}
function divRelMovSetear(data){
    divRelMovLimpiar();

    //Helper para prioridad: Toma > Egreso > Vacio
    const esReingreso = (data.maquina.sentido == "REINGRESO" && data.maquina.id_casino == 2);
    const egreso = esReingreso ? data.datos_egreso : null;
    const toma   = data.toma;

    // El helper recibe el campo y el valor por defecto (de la maquina)
    const val = function(campo, defecto = ""){
        if(toma && toma[campo] != null && toma[campo] !== "") return toma[campo];
        if(egreso && egreso[campo] != null && egreso[campo] !== "") return egreso[campo];
        if(defecto != null && defecto !== "") return defecto;
        return "";
    };

    //siempre vienen estos datos
    divRM.find('.estado').val(data.estado.descripcion)
    .attr('data-id',data.estado.id_estado_relevamiento);
    divRM.find('.nro_isla').val(data.maquina.nro_isla);
    divRM.find('.nro_isla_importado').val((data?.ultima_isla?.fecha && data?.ultima_isla?.isla)? ('('+data.ultima_isla.fecha+') '+data.ultima_isla.isla) : '');
    divRM.find('.nro_admin').val(data.maquina.nro_admin);
    divRM.find('.nro_serie').val(limpiarNullUndef(data.maquina.nro_serie,''));
    divRM.find('.marca').val(data.maquina.marca);

    // ELIMINADO: divRM.find('.mac').val(data.maquina.mac); -> Se maneja abajo con val()

    divRM.find('.modelo').val(limpiarNullUndef(data.maquina.modelo,''));

    //Contadores: Toma > Egreso
    let fuenteContadores = toma;
    if( (!toma || toma.vcont1 == null) && egreso ){ fuenteContadores = egreso; }
    divRelMovAgregarContadores(data.maquina, fuenteContadores, null);

    divRM.find('.juego').append($('<option>').val('').text('Seleccione'));
    data.juegos.forEach(j => {
        divRM.find('.juego').append($('<option>').val(j.id_juego).text(j.nombre_juego));
    });

    //Juego: Toma > Egreso > Maquina
    let id_juego = data.maquina.id_juego;
    if(egreso && egreso.juego) id_juego = egreso.juego;
    if(toma && toma.juego) id_juego = toma.juego;
    divRM.find('.juego').val(id_juego);

    const link_adjunto = data?.toma?.link_adjunto? (window.location.pathname+'/'+data?.toma?.link_adjunto) : null;
    divRelMovSetearAdjunto(link_adjunto,data.estado.descripcion == 'Generado' || data.estado.descripcion == 'Cargando');

    //Seteo de campos usando el helper de prioridad
    divRM.find('.apuesta').val(val('apuesta_max'));
    divRM.find('.cant_lineas').val(val('cant_lineas'));
    divRM.find('.devolucion').val(val('porcentaje_devolucion', data.maquina.porcentaje_devolucion));
    divRM.find('.denominacion').val(val('denominacion', data.maquina.denominacion));
    divRM.find('.creditos').val(val('cant_creditos'));

    divRM.find('.mac').val(val('mac', data.maquina.mac));
    divRM.find('.isla_rel').val(val('nro_isla_relevada', data.maquina.nro_isla));

    divRM.find('.sector_rel').val(val('descripcion_sector_relevado'));
    divRM.find('.observaciones').val(val('observaciones'));

    divRelMovAgregarProgresivos(data.progresivos,data.progresivos_aux, esReingreso);

    if(data.fecha != null){
        divRM.find('.relFecha').datetimepicker('setDate',new Date(data.fecha));
    } else {
        divRM.find('.relFecha').datetimepicker('setDate',new Date());
    }

    if(data.cargador != null) {
        divRM.find('.fiscaCarga').val(data.cargador.nombre).attr('data-id',data.cargador.id_usuario);
    } else if(data.usuario_actual) {
        divRM.find('.fiscaCarga').val(data.usuario_actual.nombre).attr('data-id',data.usuario_actual.id_usuario);
    }

    if(data.usuario_actual){
        divRM.find('.fiscaToma').setearElementoSeleccionado(data.usuario_actual.id_usuario,data.usuario_actual.nombre);
    }
}


function divRelMovMostrarErrores(response){
    const errores = {
        'apuesta_max' : divRM.find('.apuesta'),'cant_lineas' : divRM.find('.cant_lineas'), 'cant_creditos' : divRM.find('.creditos'),
        'porcentaje_devolucion' : divRM.find('.devolucion'),'juego' : divRM.find('.juego'), 'denominacion' : divRM.find('.denominacion'),
        'sector_relevado' : divRM.find('.sector_rel'), 'isla_relevada' :  divRM.find('.isla_rel'), 'mac' : divRM.find('.mac'),
        'id_fiscalizador' : divRM.find('.fiscaToma'),'fecha_sala' : divRM.find('.fechaRel'),
        'adjunto': divRM.find('.adjunto')
    };
    let err = false;
    for(const key in errores){
        if(!isUndef(response[key])){
            mostrarErrorValidacion(errores[key],parseError(response[key][0]));
            err = true;
        }
    }
    divRM.find('.tablaCont tbody tr').each(function(index){
        const res = response['contadores.'+ index +'.valor'];
        if(!isUndef(res)){
            mostrarErrorValidacion($(this).find('.valorModif'),parseError(res[0]));
            err = true;
        }
    });
    divRM.find('.tablaProg tbody tr').each(function(index){
        const progresivo = 'progresivos.'+ index;
        for(let i = 1;i <= divRelMovMaxLVLProg;i++){
            const res = response[progresivo + '.niveles.' + (i-1) + '.val'];
            if(!isUndef(res)){
                const msg = parseError(res[0]);
                mostrarErrorValidacion($(this).find('.nivel'+i),msg);
                err = true;
            }
        }
    });
    return err;
}
function divRelMovCargarRelevamientos(relevamientos, dibujos = {}, estado_listo = -1){
    // Limpiamos la tabla antes de empezar
    divRM.find('.tablaMTM tbody').empty();

    if (!relevamientos || !Array.isArray(relevamientos) || relevamientos.length === 0) {
        return;
    }

    // --- 1. Funciones Helper para manejar la Isla con seguridad ---

    // Devuelve un valor numérico para ORDENAR (las nulas al fondo)
    const getIslaSort = (r) => {
        if (r.nro_isla == null || r.nro_isla === '') return 99999999;
        const n = parseInt(r.nro_isla);
        return isNaN(n) ? 99999999 : n;
    };

    // Devuelve el TEXTO para mostrar en el encabezado
    const getIslaTexto = (r) => {
        if (r.nro_isla == null || r.nro_isla === '') return 'SIN ISLA';
        return 'ISLA ' + r.nro_isla;
    };

    // --- 2. Ordenar el array (Isla -> Nro Admin) ---
    relevamientos.sort(function(a, b) {
        const islaA = getIslaSort(a);
        const islaB = getIslaSort(b);

        // Primero por Isla
        if (islaA !== islaB) return islaA - islaB;

        // Si la isla es igual, por Nro Admin (orden natural: 2 antes que 10)
        const adminA = a.nro_admin ? String(a.nro_admin) : '';
        const adminB = b.nro_admin ? String(b.nro_admin) : '';
        return adminA.localeCompare(adminB, undefined, {numeric: true});
    });

    // --- 3. Función para crear los botones de acción ---
    const agregarToma = function(fila, id_maquina, id_relevamiento, dibujo, nro_toma){
        fila.append($('<td>')
            .addClass('col-xs-3')
            .append($('<button>')
                .append($('<i>').addClass('fa fa-fw').addClass(dibujo))
                .attr('type','button')
                .addClass('btn btn-info cargarMaq')
                .attr('data-maq', id_maquina)
                .attr('data-rel', id_relevamiento)
                .attr('toma', nro_toma)
            )
        );
        fila.append($('<td>')
            .addClass('col-xs-3 listo')
            .attr('data-maq', id_maquina)
            .attr('data-rel', id_relevamiento)
            .append($('<i>').addClass('fa fa-fw fa-check faFinalizado'))
        );
    };

    let ultima_isla_texto = '###_INICIO_###';

    // --- 4. Renderizar filas ---
    relevamientos.forEach(r => {
        // A. Cabecera de Grupo (Isla)
        const textoActual = getIslaTexto(r);

        if (textoActual !== ultima_isla_texto) {
            const filaHeader = $('<tr>').css('background-color', '#eeeeee');
            filaHeader.append($('<td>')
                .attr('colspan', 3)
                .css({
                    'font-weight': 'bold',
                    'text-align': 'center',
                    'color': '#333',
                    'border-top': '2px solid #ccc' // Borde para separar mejor
                })
                .text(textoActual)
            );
            divRM.find('.tablaMTM tbody').append(filaHeader);
            ultima_isla_texto = textoActual;
        }

        // B. Fila de Máquina
        let fila = $('<tr>');
        let dibujo = 'fa-upload';
        const id_estado = r.estado ? r.estado.id_estado_relevamiento : 1;

        // CORRECCIÓN: Usamos 'dibujos' (argumento), no 'drawings'
        if(typeof dibujos[id_estado] !== 'undefined') {
            dibujo = dibujos[id_estado];
        }

        fila.append($('<td>')
            .addClass('col-xs-5')
            .text(r.nro_admin || 'S/N')
            .css('padding-left', '20px') // Sangría para que se note jerarquía
        );

        // C. Botones de Toma
        let i = 0;
        const cant_tomas = r.tomas || 0;
        for(; i < cant_tomas; i++){
            agregarToma(fila, r.id_maquina, r.id_relevamiento, dibujo, i+1);
        }
        // Si no hay tomas creadas aun, mostramos boton para la toma 1 (0 en indice)
        if(i == 0){
            agregarToma(fila, r.id_maquina, r.id_relevamiento, dibujo, 0);
        }

        // D. Icono de Listo (Check verde)
        fila.find('.listo').toggle(id_estado == estado_listo);

        divRM.find('.tablaMTM tbody').append(fila);
    });
}

function divRelMovEsconderDetalleRelevamiento(){
    divRM.find('.relFecha').parent().hide();
    divRM.find('.fiscaToma').parent().hide();
    divRM.find('.detalleRel').hide();
}
function divRelMovMostrarDetalleRelevamiento(){
    divRM.find('.relFecha').parent().show();
    divRM.find('.fiscaToma').parent().show();
    divRM.find('.detalleRel').show();
}
function divRelMovSetearUsuarios(casino,cargador,fiscalizador){
    divRM.find('.fiscaToma').generarDataList(window.location.href+"/buscarUsuariosPorNombreYCasino/" + casino.id_casino,'usuarios' ,'id_usuario','nombre',1,false);
    divRM.find('.fiscaToma').setearElementoSeleccionado(0,"");
    divRM.find('.fiscaCarga').val('');
    divRM.find('.fiscaCarga').removeAttr('data-id');

    if(cargador){
        divRM.find('.fiscaCarga').attr('data-id',cargador.id_usuario);
        divRM.find('.fiscaCarga').val(cargador.nombre);
    }
    if(fiscalizador){
      divRM.find('.fiscaToma').setearElementoSeleccionado(fiscalizador.id_usuario,fiscalizador.nombre);
    }
}
function divRelMovSetearTipo(tipo_movimiento,sentido){
    divRM.find('.tipoMov').val(tipo_movimiento);
    divRM.find('.sentidoMov').val(sentido);
}
function divRelMovSetearExp(org,interno,control,dispo,dispo_anio){
    divRM.find('.exp_org').val(org);
    divRM.find('.exp_interno').val(interno);
    divRM.find('.exp_control').val(control);
    divRM.find('.disposicion').val(dispo);
    divRM.find('.disposicion_anio').val(dispo_anio);
}
function divRelMovMarcarListaMaq(id_maquina,estado = true){
    divRM.find('.tablaMTM').find('.listo[data-maq="'+id_maquina+'"]').toggle(estado);
}
function divRelMovMarcarListoRel(id_relev,estado = true){
    divRM.find('.tablaMTM').find('.listo[data-rel="'+id_relev+'"]').toggle(estado);
    divRM.find('.tablaMTM').find('.cargarMaq[data-rel="'+id_relev+'"]').parent().toggle(!estado);
}
function divRelMovCambiarDibujoMaq(id_maquina,dibujo){
    let boton = divRM.find('.cargarMaq[data-maq='+id_maquina+']')[0];
    $(boton).empty();
    $(boton).append($('<i>').addClass(dibujo));
}
function divRelMovSetearModo(modo){
    if(modo == "VER"){
        divRM.find('.editable').attr('disabled',true);
        divRM.find('.exp_org,.exp_interno,.exp_control,.disposicion,.disposicion_anio').attr('disabled',true);
        divRM.find('.relFecha .input-group-addon').hide();
        divRM.find('.validacion').hide();
    }
    else if(modo == "CARGAR"){
        divRM.find('.editable').removeAttr('disabled');
        divRM.find('.exp_org,.exp_interno,.exp_control,.disposicion,.disposicion_anio').attr('disabled',true);
        divRM.find('.relFecha .input-group-addon').show();
        divRM.find('.validacion').hide();
    }
    else if(modo == "VALIDAR"){
        divRM.find('.editable').attr('disabled',true);
        divRM.find('.exp_org,.exp_interno,.exp_control,.disposicion,.disposicion_anio').removeAttr('disabled');
        divRM.find('.relFecha .input-group-addon').hide();
        divRM.find('.validacion').show();
    }
}

$('#divRelMov').find('.cant_lineas,.creditos,.apuesta').focusout(function(e){
    const arr =  [$('#divRelMov .cant_lineas').val(),$('#divRelMov .creditos').val(),$('#divRelMov .apuesta').val()];
    let llenados = 0;
    for(const idx in arr){
        if(arr[idx] != "") llenados++;
    }
    if(llenados != 2) return;
    //apuesta, cant_lineas son double y creditos int en la tabla...
    const vals = [parseFloat(arr[0]),parseInt(arr[1]),parseFloat(arr[2])];
    let nans = 0;
    for(const idx in vals){
        if(isNaN(vals[idx])) nans++;
    }
    if(nans > 1) return;//Siempre hay 1 NaN porque hay 1 vacio
    //Almenos 2 valores fueron ingresados
    //Apuesta/Creditos = CantLineas
    //Si no borro calculo el restante
    if($(this).val() == "") return;
    //En los formularios subidos veo que cuando ponen creditos = 0 ponen Cantlineas en 0, sigo esa convención
    const div = function(a,b){ return b == 0? 0 : a/b; };
    if($(this).hasClass('cant_lineas')){
        if(arr[1] != "") $('#divRelMov .apuesta').val(vals[0]*vals[1]);
        else             $('#divRelMov .creditos').val(div(vals[2],vals[0]));
    }
    if($(this).hasClass('creditos')){
        if(arr[0] != "") $('#divRelMov .apuesta').val(vals[0]*vals[1]);
        else             $('#divRelMov .cant_lineas').val(div(vals[2],vals[1]));
    }
    if($(this).hasClass('apuesta')){
        if(arr[0] != "") $('#divRelMov .creditos').val(div(vals[2],vals[0]));
        else             $('#divRelMov .cant_lineas').val(div(vals[2],vals[1]));
    }
});

// -- Marca visual SIN agregar HTML ni mover columnas --
function divRelMovMarcarCambio($el){
  if(!$el || !$el.length) return;

  // Pintá el control de forma visible (Bootstrap 3)
  // bg-warning = fondo amarillo; text-warning = texto ámbar
  $el.addClass('bg-warning text-warning');

  // Extra: si hay form-group, agrego estado para borde/labels
  var $fg = $el.closest('.form-group');
  if($fg.length){
    $fg.addClass('has-warning');
  } else {
    // Si no hay form-group (p.ej. celdas de tabla), marco el contenedor inmediato
    $el.closest('td,th,.input-group,[class*="col-"]').addClass('has-warning');
  }

  // console.log('[marcarCambio]', $el.get(0));
}

// -- Limpia TODOS los resaltados (cuando recargás datos o cerrás) --
function divRelMovLimpiarResaltados(){
  if(!divRM) return;
  divRM.find('.cambio-flag').remove();             // por si quedó alguno de pruebas
  divRM.find('.bg-warning').removeClass('bg-warning');
  divRM.find('.text-warning').removeClass('text-warning');
  divRM.find('.has-warning').removeClass('has-warning');
}



function mostrarModalCambios(cambios){
  //  guardo el listado para usarlo al confirmar
  window.__ultimos_cambios__ = cambios;

  const lista = $('#listaCambios').empty();
  cambios.forEach(function(c){
    lista.append('<li>• ' + c + '</li>');
  });
  window.__abrirModalCambios__ ? window.__abrirModalCambios__() : $('#modalDivRelCambios').modal('show');
}



function divRelMovVerificarCambios(data){
  const cambios = [];
  divRelMovLimpiarResaltados();

  let ultimo = data.datos_egreso || data.datos_ultimo_relev || null;

  // Detectar si es INGRESO (busca la palabra INGRESO en el tipo de movimiento)
  const tipoMov = (data.tipo_movimiento || '').toUpperCase();
  // "INGRESO" matchea tanto "INGRESO INICIAL" como "REINGRESO"
  // Pero si ya tenemos 'ultimo' (datos_egreso), usamos eso.
  // Si NO tenemos 'ultimo', asumimos que es un Ingreso Inicial y usamos la DB.
  const usarDB = !ultimo && tipoMov.indexOf('INGRESO') !== -1;

  // --- LÓGICA DE INGRESO: Crear objeto de comparación usando la DB ---
  if(usarDB && data.maquina){
      ultimo = {
          mac: data.maquina.mac,
          nro_isla_relevada: data.maquina.nro_isla,
          descripcion_sector_relevado: data.maquina.descripcion_sector_relevado,
          juego: data.maquina.id_juego,
          // Si estos campos no vienen de la DB, asumimos null
          apuesta_max: data.maquina.apuesta_max || null,
          cant_lineas: data.maquina.cant_lineas || null,
          porcentaje_devolucion: data.maquina.porcentaje_devolucion,
          denominacion: data.maquina.denominacion,
          cant_creditos: data.maquina.cant_creditos || null
      };
  }

  // Si no hay contra qué comparar, guardamos directo.
  if(!ultimo) return true;

  // --- CONTADORES Y PROGRESIVOS (Solo validamos si NO usamos DB, o sea, si hay historial real) ---
  if(!usarDB){
    // Contadores
    for(let i=1; i<=8; i++){
      const $tr = divRM.find('.tablaCont tbody tr').eq(i-1);
      if($tr.length === 0) break;
      const $inp = $tr.find('.vcont');
      const valActual    = ($inp.val() || '').trim();
      const valOriginal = (ultimo['vcont'+i] == null) ? '' : String(ultimo['vcont'+i]).trim();
      if(valActual !== valOriginal){
        const nombreCont = data.maquina && data.maquina['cont'+i] ? data.maquina['cont'+i] : ('#'+i);
        cambios.push('Contador ' + nombreCont + ': ' + valOriginal + ' → ' + valActual);
        divRelMovMarcarCambio($inp);
      }
    }
    // Progresivos
    (data.progresivos || []).forEach(function(prog, index){
        const fila = divRM.find('.tablaProg tbody tr').eq(index);
        const aux  = (data.progresivos_aux || [])[index];
        if(!fila.length || !aux) return;
        (prog.pozo?.niveles || []).forEach(function(niv){
          const key = 'nivel' + niv.nro_nivel;
          const $inp = fila.find('.' + key);
          const valActual    = ($inp.val() || '').trim();
          const valOriginal = (aux[key] == null) ? '' : String(aux[key]).trim();
          if(valActual !== valOriginal){
            const etiquetaProg = (prog.pozo && prog.pozo.descripcion) ? prog.pozo.descripcion : (prog.nombre || ('Pozo ' + (index+1)));
            cambios.push('Progresivo ' + etiquetaProg + ' (' + niv.nombre_nivel + '): ' + valOriginal + ' → ' + valActual);
            divRelMovMarcarCambio($inp);
          }
        });
    });
  }

  // --- CAMPOS PRINCIPALES (Aplica para TODOS) ---
  const campos = [
    {sel: '.mac',                   key: 'mac',                          label: 'MAC'},
    {sel: '.isla_rel',              key: 'nro_isla_relevada',            label: 'Isla'},
    {sel: '.sector_rel',            key: 'descripcion_sector_relevado',  label: 'Sector'},
    {sel: '.juego',                 key: 'juego',                        label: 'Juego'},
    {sel: '.apuesta',               key: 'apuesta_max',                  label: 'Apuesta máxima'},
    {sel: '.cant_lineas',           key: 'cant_lineas',                  label: 'Cant. Líneas'},
    {sel: '.devolucion',            key: 'porcentaje_devolucion',        label: '% Devolución'},
    {sel: '.denominacion',          key: 'denominacion',                 label: 'Denominación'},
    {sel: '.creditos',              key: 'cant_creditos',                label: 'Cant. Créditos'},
  ];

  // Helper para normalizar valores (trim, puntos por comas)
  const norm = (v) => String(v ?? '').trim().replace(/,/g, '.');

  campos.forEach(function(c){
    const $el = divRM.find(c.sel);
    if(!$el.length) return;

    let rawActual = $el.val();
    let rawOriginal = (ultimo[c.key] !== undefined && ultimo[c.key] !== null) ? ultimo[c.key] : '';

    // Valores normalizados para comparar
    let valActual = norm(rawActual);
    let valOriginal = norm(rawOriginal);

    // Comparación especial para JUEGO (IDs vs Strings o IDs numéricos)
    if(c.key === 'juego') {
        if(valActual == valOriginal) return;
    }
    // Comparación numérica para evitar diferencias por ceros ("20" != "20.00")
    else if(!isNaN(valActual) && !isNaN(valOriginal) && valActual !== '' && valOriginal !== '') {
        if(parseFloat(valActual) === parseFloat(valOriginal)) return;
    }

    // Si llegamos acá y son distintos, marcamos el cambio
    if(valActual !== valOriginal){
        let txtOriginal = rawOriginal;
        let txtActual = rawActual;

        // Obtener texto legible si es un Select (Juego)
        if(c.key === 'juego'){
             const $optActual = $el.find('option:selected');
             if($optActual.length) txtActual = $optActual.text();

             const $optOrig = $el.find('option[value="'+rawOriginal+'"]');
             if($optOrig.length) txtOriginal = $optOrig.text();
             else if(rawOriginal == "") txtOriginal = "Sin asignar";
        }

        if(txtOriginal === '') txtOriginal = 'Vacío';

        cambios.push(c.label + ': ' + txtOriginal + ' → ' + txtActual);
        divRelMovMarcarCambio($el);
    }
  });

  if(cambios.length > 0){
    mostrarModalCambios(cambios);
    return false; // Detiene el guardado automático
  }
  return true; // Permite guardar
}
