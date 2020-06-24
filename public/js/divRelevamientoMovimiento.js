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
        observacionesAdm: divRM.find('.observacionesAdm').val(),
        nro_exp_org: divRM.find('.exp_org').val(),
        nro_exp_interno: divRM.find('.exp_interno').val(),
        nro_exp_control: divRM.find('.exp_control').val(),
    };
}
function divRelMovLimpiarErrores(){
    divRM.find('.alerta').each(function(){
        ocultarErrorValidacion($(this));
    });
}
function divRelMovLimpiar(){
    divRelMovLimpiarErrores();
    divRM.find('input').not('.tipoMov,.sentidoMov,.exp_org,.exp_interno,.exp_control').val('');
    divRM.find('.tablaCont tbody').empty();
    divRM.find('.juego').empty();
    divRM.find('.tablaProg tbody').empty();
    divRM.find('.relFecha').datetimepicker('update','');
    divRM.find('textarea').val('');
}
function divRelMovAgregarContadores(maquina,toma){
    for (let i = 1; i < 7; i++){
        let fila = divRM.find('.filaEjCont').clone().removeClass('filaEjCont');
        let nombre_cont = maquina["cont" + i];
        if(nombre_cont === null) continue;
        let val_cont = null;
        if(toma != null){
            val_cont = toma["vcont" + i];
        }
        fila.find('.cont').text(nombre_cont).attr('data-contador',nombre_cont);
        fila.find('.vcont').val(val_cont != null? val_cont : '');
        divRM.find('.tablaCont tbody').append(fila);
    }
}
function divRelMovAgregarProgresivos(progresivos){
  if(progresivos === null || progresivos.length == 0){
    divRM.find('.sinProg').show();
    divRM.find('.tablaProg').hide();
    return;
  }
  divRM.find('.sinProg').hide();
  divRM.find('.tablaProg').show();
  progresivos.forEach( prog => {
    let fila = divRM.find('.filaEjProg').clone().removeClass('filaEjProg');
    let nombre = prog.nombre;
    if(!prog.pozo.es_unico){ nombre += '(' + prog.pozo.descripcion + ')';}
    if(prog.es_individual) nombre = 'INDIVIDUAL';
    fila.find('.nombreProgresivo').text(nombre).attr('title',nombre).attr('data-id-pozo',prog.pozo.id_pozo);
    prog.pozo.niveles.forEach( niv => {
      let nivel = fila.find('.nivel'+ niv.nro_nivel);
      nivel.attr('placeholder',niv.nombre_nivel).addClass('habilitado');
      nivel.attr('data-id-nivel',niv.id_nivel_progresivo)
    });
    const rel_prog = prog.pozo.det_rel_prog;
    const causaNoToma = rel_prog.id_tipo_causa_no_toma_progresivo;
    fila.find('.causaNoToma').val(causaNoToma);
    for(let i = 1;i <= divRelMovMaxLVLProg && causaNoToma === null;i++){
        fila.find('.nivel'+i).val(rel_prog['nivel'+i]);
    }
    divRM.find('.tablaProg tbody').append(fila);
    divRM.find('.tablaProg tbody input').not('.habilitado').attr('disabled',true);
  });
}
function divRelMovSetear(data){
    divRelMovLimpiar();
    //siempre vienen estos datos
    divRM.find('.estado').val(data.estado.descripcion)
    .attr('data-id',data.estado.id_estado_relevamiento);
    divRM.find('.nro_isla').val(data.maquina.nro_isla);
    divRM.find('.nro_admin').val(data.maquina.nro_admin);
    divRM.find('.nro_serie').val(limpiarNullUndef(data.maquina.nro_serie,''));
    divRM.find('.marca').val(data.maquina.marca);
    divRM.find('.modelo').val(limpiarNullUndef(data.maquina.modelo,''));
    divRelMovAgregarContadores(data.maquina,data.toma);
    divRM.find('.juego').append($('<option>').val(0).text('Seleccione'));
    data.juegos.forEach(j => {
        divRM.find('.juego').append($('<option>').val(j.id_juego).text(j.nombre_juego));
    });
    if(data.toma != null){
        divRM.find('.juego').val(data.toma.juego? data.toma.juego : 0);
        divRM.find('.apuesta').val(data.toma.apuesta_max);
        divRM.find('.cant_lineas').val(data.toma.cant_lineas);
        divRM.find('.devolucion').val(data.toma.porcentaje_devolucion);
        divRM.find('.denominacion').val(data.toma.denominacion);
        divRM.find('.creditos').val(data.toma.cant_creditos);
        divRM.find('.observaciones').val(data.toma.observaciones);
        divRM.find('.mac').val(data.toma.mac);
        divRM.find('.sector_rel').val(data.toma.descripcion_sector_relevado);
        divRM.find('.isla_rel').val(data.toma.nro_isla_relevada);
        divRM.find('.observaciones').val(data.toma.observaciones);
    }
    divRelMovAgregarProgresivos(data.progresivos);
    if(data.fecha != null){
        divRM.find('.relFecha').datetimepicker('setDate',new Date(data.fecha));
    }
    if(data.cargador != null) { 
        divRM.find('.fiscaCarga').val(data.cargador.nombre).attr('data-id',data.cargador.id_usuario);
    }
    if(data.fiscalizador != null){
        divRM.find('.fiscaToma').setearElementoSeleccionado(data.fiscalizador.id_usuario,data.fiscalizador.nombre);
    }
}
function divRelMovMostrarErrores(response){
    const errores = { 
        'apuesta_max' : divRM.find('.apuesta'),'cant_lineas' : divRM.find('.cant_lineas'), 'cant_creditos' : divRM.find('.creditos'),
        'porcentaje_devolucion' : divRM.find('.devolucion'),'juego' : divRM.find('.juego'), 'denominacion' : divRM.find('.denominacion'),
        'sector_relevado' : divRM.find('.sector_rel'), 'isla_relevada' :  divRM.find('.isla_rel'), 'mac' : divRM.find('.mac'),
        'id_fiscalizador' : divRM.find('.fiscaToma'),'fecha_sala' : divRM.find('.fechaRel')
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
function divRelMovCargarRelevamientos(relevamientos,dibujos = {},estado_listo = -1){
    const agregarToma = function(fila,id_maquina,id_relevamiento,dibujo,nro_toma,estado_rel){
        fila.append($('<td>')
            .addClass('col-xs-3')
            .append($('<button>')
            .append($('<i>')
            .addClass('fa').addClass('fa-fw').addClass(dibujo))
            .attr('type','button')
            .addClass('btn btn-info cargarMaq')
            .attr('data-maq', id_maquina)
            .attr('data-rel', id_relevamiento)
            .attr('toma',nro_toma)
            )
        );
        fila.append($('<td>')
            .addClass('col-xs-3 listo')
            .attr('data-maq', id_maquina)
            .attr('data-rel', id_relevamiento)
            .append($('<i>').addClass('fa fa-fw fa-check faFinalizado'))
        );
    };
    divRM.find('.tablaMTM tbody').empty();
    relevamientos.forEach(r => {
      let fila = $('<tr>');
      let dibujo = 'fa-upload';
      const id_estado = r.estado.id_estado_relevamiento;
      if(!isUndef(dibujos[id_estado])) dibujo = dibujos[id_estado];
      fila.append($('<td>')
          .addClass('col-xs-5')
          .text(r.nro_admin)
      );
      let i = 0;//Multiples tomas estan deprecadas pero esto está por compatibilidad para atras
      for(;i<r.tomas;i++){
          agregarToma(fila,r.id_maquina,r.id_relevamiento,dibujo,i+1,r.estado.id_estado_relevamiento);
      }
      //El relevamiento no tiene tomas, se va a crear una cuando le mande guardar.
      if(i == 0){
          agregarToma(fila,r.id_maquina,r.id_relevamiento,dibujo,0,1);
      }

      fila.find('.listo').toggle(r.id_estado_relevamiento == estado_listo);
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
    divRM.find('.fiscaToma').generarDataList("/usuarios/buscarUsuariosPorNombreYCasino/" + casino.id_casino,'usuarios' ,'id_usuario','nombre',1,false);
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
function divRelMovSetearExp(org,interno,control){
    divRM.find('.exp_org').val(org);
    divRM.find('.exp_interno').val(interno);
    divRM.find('.exp_control').val(control);
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
        divRM.find('.exp_org,.exp_interno,.exp_control').attr('disabled',true);
        divRM.find('.relFecha .input-group-addon').hide();
        divRM.find('.validacion').hide();
    }
    else if(modo == "CARGAR"){
        divRM.find('.editable').removeAttr('disabled');
        divRM.find('.exp_org,.exp_interno,.exp_control').attr('disabled',true);
        divRM.find('.relFecha .input-group-addon').show();
        divRM.find('.validacion').hide();
    }
    else if(modo == "VALIDAR"){
        divRM.find('.editable').attr('disabled',true);
        divRM.find('.exp_org,.exp_interno,.exp_control').removeAttr('disabled');
        divRM.find('.relFecha .input-group-addon').hide();
        divRM.find('.validacion').show();
    }
}