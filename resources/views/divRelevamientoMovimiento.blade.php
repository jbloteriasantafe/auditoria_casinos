<div class="row"> <!-- row inicial -->
    <div class="col-md-3">
        <h5>Máquinas</h5>
        <table id="tablaCargarMTM" class="table">
        <thead>
            <tr>
            <th> </th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        </table>
    </div> <!-- maquinas -->
    <div  id="detallesMTM" class="col-md-9">
        <h6>DETALLES MTM</h6>
        <form id="form1" class="" action="index.html" method="post">
        <div class="row" >
                <div class="col-lg-4">
                    <h5>Nro Admin.</h5>
                    <input id="nro_adminMov" type="text"   class="form-control" readonly="readonly">
                </div>
                <div class="col-lg-4">
                    <h5>N° Isla</h5>
                    <input id="nro_islaMov" type="text" class="form-control" readonly="readonly">
                </div>
                <div class="col-lg-4">
                    <h5>N° Serie</h5>
                    <input id="nro_serieMov" type="text" class="form-control" readonly="readonly">
                </div>
            </div> 
            <div class="row"> 
                <div class="col-lg-6">
                    <h5>Marca</h5>
                    <input id="marcaMov" type="text" class="form-control" readonly="readonly">
                </div>
                <div class="col-lg-6">
                    <h5>Modelo</h5>
                    <input id="modeloMov" type="text" class="form-control" readonly="readonly">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <h5>MAC</h5>
                    <input id="macCargar" type="text" value="" class="form-control">
                </div>
                <div class="col-lg-4">
                    <h5>SECTOR</h5>
                    <input id="sectorRelevadoCargar" type="text" value="" class="form-control">
                </div>
                <div class="col-lg-4">
                    <h5>ISLA</h5>
                    <input id="islaRelevadaCargar" type="text" value="" class="form-control">
                </div>
            </div>
            <div class="row">
                <div id="" class="table-editable">
                    <table id="tablaCargarContadores" class="table">
                        <thead>
                            <tr>
                                <th class="col-xs-6"><h6><b>CONTADORES</b></h6></th>
                                <th class="col-xs-6"><h6><b>TOMA</b></h6></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div> 
            </div>
            <h6>TOMA</h6>
            <div class="row"> 
                <div class="col-lg-4">
                    <h5>JUEGO</h5>
                    <select id="juegoRel" class="form-control" name="">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-lg-4">
                    <h5>APUESTA MÁX</h5>
                    <input id="apuesta" type="text" value="" class="form-control">
                </div>
                <div class="col-lg-4">
                    <h5>CANT LÍNEAS</h5>
                    <input id="cant_lineas" type="text" value="" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <h5>% DEVOLUCIÓN</h5>
                    <input id="devolucion" type="text" value="" class="form-control">
                </div>
                <div class="col-lg-4">
                    <h5>DENOMINACIÓN</h5>
                    <input id="denominacion" type="text" value="" class="form-control">
                </div>
                <div class="col-lg-4">
                    <h5>CANT CRÉDITOS</h5>
                    <input id="creditos" type="text" value="" class="form-control">
                </div>
            </div>
            <h6>PROGRESIVOS</h6>
            <div class="row">
                <div class="col-lg-12" id="tomaProgresivo" style="overflow: scroll;max-height: 250px;">
                    <h5 id="sinProgresivos" hidden>La maquina no posee progresivos asignados</h5>
                    <table class="table table-fixed" id="tablaProgresivos">
                        <thead>
                            <tr>
                                <th width="17%">PROGRESIVO</th>
                                @for($i=6;$i>0;$i--)
                                <th width="11%">NIVEL{{$i}}</th>
                                @endfor
                                <th width="17%">CAUSA NO TOMA</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <table hidden>
                <tr id="filaEjemploProgresivo">
                    <td class="nombreProgresivo" width="17%">PROGRESIVO99</td>
                    @for ($i=6;$i>0;$i--)
                    <td width="11%">
                    <input class="nivel{{$i}} form-control" min="0" data-toggle="tooltip" data-placement="down" title="nivel{{$i}}"></input>
                    </td>
                    @endfor
                    <td width="17%">
                    <select class="causaNoToma form-control">
                        <option value="-1"></option>
                        @foreach($causasNoTomaProgresivo as $causa)
                        <option value="{{$causa->id_tipo_causa_no_toma_progresivo}}">{{$causa->descripcion}}</option>
                        @endforeach
                    </select>
                    </td>
                </tr>
            </table>
            <h6>OBSERVACIONES</h6>
            <div class="row">
                <div class="col-lg-12">
                    <textarea id="observacionesToma" value="" class="form-control" style="resize:vertical;"></textarea>
                </div>
            </div> <!-- FIN ULTIMO row -->
        </form>
    </div> <!-- fin detalle -->
</div>


<script src="js/utils.js" type="text/javascript"></script>
<script type="text/javascript">
function obtenerDatosDivRelevamiento(){
    let contadores= [];
    $('#tablaCargarContadores tbody tr').each(function(){
        const cont={
            nombre: $(this).attr('data-contador'),
            valor: $(this).find('.valorModif').val()
        }
        contadores.push(cont);
    });

    let progresivos = [];
    $('#tomaProgresivo tbody tr').each(function(){
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
        nro_admin: $('#nro_adminMov').val(),
        isla_maq: $('#nro_islaMov').val(),
        nro_serie: $('#nro_serieMov').val(),
        marca: $('#marcaMov').val(),
        modelo: $('#modeloMov').val(),
        //Valores relevados
        mac: $('#macCargar').val(),
        isla_rel: $('#islaRelevadaCargar').val(),
        sector_rel: $('#sectorRelevadoCargar').val(),
        contadores: contadores,
        juego: $('#juegoRel').val(),
        apuesta: $('#apuesta').val(),
        lineas: $('#cant_lineas').val(),
        devolucion: $('#devolucion').val(),
        denominacion: $('#denominacion').val(), 
        creditos: $('#creditos').val(),
        progresivos: progresivos,
        observaciones: $('#observacionesToma').val()
    };
}
function limpiarDivRelevamiento(){
    ocultarErrorValidacion($('#juegoRel'));
    ocultarErrorValidacion($('#apuesta'));
    ocultarErrorValidacion($('#cant_lineas'));
    ocultarErrorValidacion($('#creditos'));
    ocultarErrorValidacion($('#denominacion'));
    ocultarErrorValidacion($('#devolucion'));
    $('#nro_islaMov').val('');
    $('#nro_adminMov').val('');
    $('#nro_serieMov').val('');
    $('#marcaMov').val('');
    $('#modeloMov').val('');
    $('#tablaCargarContadores tbody').empty();
    $('#juegoRel').empty();
    $('#apuesta').val('');
    $('#cant_lineas').val('');
    $('#devolucion').val('');
    $('#denominacion').val('');
    $('#creditos').val('');
    $('#observacionesToma').val('');
    $('#macCargar').val('');
    $('#sectorRelevadoCargar').val('');
    $('#islaRelevadaCargar').val('');
    $('#observacionesToma').val('');
    $('#tomaProgresivo tbody').empty();
}
function agregarContadores(maquina,toma){
    for (let i = 1; i < 7; i++){
        let fila = $('<tr>');
        let nombre_cont = maquina["cont" + i];
        if(nombre_cont === null) continue;

        let val_cont = null;
        if(toma != null){
            val_cont = toma["vcont" + i];
        }

        fila.append($('<td>').addClass('col-xs-6').text(nombre_cont));
        fila.attr('data-contador',nombre_cont);
        fila.append($('<td>').addClass('col-xs-6')
        .append($('<input>').addClass('valorModif form-control'))
        );
        if(val_cont != null){
            fila.find('input').val(val_cont);
        }

        $('#tablaCargarContadores tbody').append(fila);
    }
}
function agregarProgresivos(progresivos){
  if(progresivos === null || progresivos.length == 0){
    $('#sinProgresivos').show();
    $('#tablaProgresivos').hide();
    return;
  }
  $('#sinProgresivos').hide();
  $('#tablaProgresivos').show();
  progresivos.forEach( prog => {
    let fila = $('#filaEjemploProgresivo').clone().removeAttr('id');
    let nombre = prog.nombre;
    if(!prog.pozo.es_unico){ nombre += '(' + prog.pozo.descripcion + ')';}
    if(prog.es_individual) nombre = 'INDIVIDUAL';
    fila.find('.nombreProgresivo').text(nombre).attr('title',nombre).attr('data-id-pozo',prog.pozo.id_pozo);
    prog.pozo.niveles.forEach( niv => {
      let nivel = fila.find('.nivel'+ niv.nro_nivel);
      nivel.attr('placeholder',niv.nombre_nivel).addClass('habilitado');
      nivel.attr('data-id-nivel',niv.id_nivel_progresivo)
    });
    $('#tomaProgresivo tbody').append(fila);
    $('#tomaProgresivo tbody input').not('.habilitado').attr('disabled',true);
  });
}
function setearDivRelevamiento(data){
    limpiarDivRelevamiento();
    //siempre vienen estos datos
    $('#nro_islaMov').val(data.maquina.nro_isla);
    $('#nro_adminMov').val(data.maquina.nro_admin);
    $('#nro_serieMov').val(limpiarNullUndef(data.maquina.nro_serie,''));
    $('#marcaMov').val(data.maquina.marca);
    $('#modeloMov').val(limpiarNullUndef(data.maquina.modelo,''));
    agregarContadores(data.maquina,data.toma);
    $('#juegoRel').append($('<option>').val(0).text('Seleccione'));
    data.juegos.forEach(j => {
        $('#juegoRel').append($('<option>').val(j.id_juego).text(j.nombre_juego));
    });
    if(data.toma != null){
        $('#juegoRel').val(data.toma.juego);
        $('#apuesta').val(data.toma.apuesta_max);
        $('#cant_lineas').val(data.toma.cant_lineas);
        $('#devolucion').val(data.toma.porcentaje_devolucion);
        $('#denominacion').val(data.toma.denominacion);
        $('#creditos').val(data.toma.cant_creditos);
        $('#observacionesToma').val(data.toma.observaciones);
        $('#macCargar').val(data.toma.mac);
        $('#sectorRelevadoCargar').val(data.toma.descripcion_sector_relevado);
        $('#islaRelevadaCargar').val(data.toma.nro_isla_relevada);
        $('#observacionesToma').val(data.toma.observaciones);
    }
    agregarProgresivos(data.progresivos);
}
function mostrarErroresDiv(response){
    const errores = { 
        'apuesta_max' : $('#apuesta'),'cant_lineas' : $('#cant_lineas'), 'cant_creditos' : $('#creditos'),
        'porcentaje_devolucion' : $('#devolucion'),'juego' : $('#juegoRel'), 'denominacion' : $('#denominacion'),
        'sectorRelevadoCargar' : $('#sectorRelevadoCargar'), 'isla_relevada' :  $('#islaRelevadaCargar'), 'mac' : $('#macCargar')
    };
    let err = false;
    for(const key in errores){
        if(!isUndef(response[key])){
            mostrarErrorValidacion(errores[key],parseError(response[key][0]));
            err = true;
        }
    }
    $('#tablaCargarContadores tbody tr').each(function(index){
        const res = response['contadores.'+ index +'.valor'];
        if(!isUndef(res)){
            mostrarErrorValidacion($(this).find('.valorModif'),parseError(res[0]));
            err = true;
        }
    });
    return err;
}
function cargarRelevamientos(relevamientos,dibujos = {},id_fiscalizacion = -1,estado_listo = -1){
    $('#tablaCargarMTM tbody').empty();
    relevamientos.forEach(r => {
      let fila = $('<tr>');
      let dibujo = 'fa-upload';
      const id_estado = r.estado.id_estado_relevamiento;
      if(!isUndef(dibujos[id_estado])) dibujo = dibujos[id_estado];
      fila.append($('<td>')
          .addClass('col-xs-5')
          .text(r.nro_admin)
      );
      fila.append($('<td>')
          .addClass('col-xs-3')
          .append($('<button>')
          .append($('<i>')
          .addClass('fa').addClass('fa-fw').addClass(dibujo))
          .attr('type','button')
          .addClass('btn btn-info cargarMaq')
          .attr('id', r.id_maquina)
          .attr('data-rel', r.id_relevamiento)
          .attr('data-fisc', id_fiscalizacion)
        )
      );
      fila.append($('<td>')
        .addClass('col-xs-3')
        .append($('<i>').addClass('fa fa-fw fa-check faFinalizado').addClass('listo')
          .attr('value', r.id_maquina))
      );
      fila.find('.listo').toggle(r.id_estado_relevamiento == estado_listo);
      $('#tablaCargarMTM tbody').append(fila);
    });
}
</script>
