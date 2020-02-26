<?php
use App\Http\Controllers\UsuarioController;
$divRelMov_ucontrol = UsuarioController::getInstancia(); 
$divRelMov_user = $divRelMov_ucontrol->quienSoy()['usuario'];
?>

<div id="divRelevamientoMovimiento">
<div class="row"> 
    <div class="col-md-4 col-md-offset-2">
        <h5>Tipo Movimiento</h5>
        <input id="inputTipoMov" class="form-control" type="text" autocomplete="off" readonly="">
    </div>
    <div class="col-md-4">
        <h5>Sentido</h5>
        <input id="inputSentido" class="form-control" type="text" autocomplete="off" readonly="" placeholder="Reingreso - Egreso temporal">
    </div>
</div>
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
        <div class="row">
            <div class="col-md-4">
                <h5>Fiscalizador Carga: </h5>
                <input id="fiscaCarga" type="text" class="form-control" disabled="true">
            </div>
            <div class="col-md-4">
                <h5>Fiscalizador Toma: </h5>
                <input id="fiscaToma" class="form-control editable" type="text" autocomplete="off">
            </div>
            <div class="col-md-4">
                <h5>Fecha Ejecución: </h5>
                <div class='input-group date' id='relFecha' data-date-format="yyyy-mm-dd HH:ii:ss">
                    <input type='text' class="form-control editable" placeholder="Fecha de ejecución del relevamiento" id="fechaRel" data-trigger="manual" data-toggle="popover" data-placement="top" />
                    <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                    <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
            </div>
        </div>
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
                    <input id="macCargar" type="text" class="form-control editable">
                </div>
                <div class="col-lg-4">
                    <h5>SECTOR</h5>
                    <input id="sectorRelevadoCargar" type="text" class="form-control editable">
                </div>
                <div class="col-lg-4">
                    <h5>ISLA</h5>
                    <input id="islaRelevadaCargar" type="text" class="form-control editable">
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
                    <select id="juegoRel" class="form-control editable">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-lg-4">
                    <h5>APUESTA MÁX</h5>
                    <input id="apuesta" type="text" class="form-control editable">
                </div>
                <div class="col-lg-4">
                    <h5>CANT LÍNEAS</h5>
                    <input id="cant_lineas" type="text" class="form-control editable">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <h5>% DEVOLUCIÓN</h5>
                    <input id="devolucion" type="text" class="form-control editable">
                </div>
                <div class="col-lg-4">
                    <h5>DENOMINACIÓN</h5>
                    <input id="denominacion" type="text" class="form-control editable">
                </div>
                <div class="col-lg-4">
                    <h5>CANT CRÉDITOS</h5>
                    <input id="creditos" type="text" class="form-control editable">
                </div>
            </div>
            <h6>PROGRESIVOS</h6>
            <div class="row">
                <div class="col-lg-12" id="tomaProgresivo" style="overflow: scroll;max-height: 250px;">
                    <h5 id="sinProgresivos" hidden>La toma no posee progresivos asignados</h5>
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
                    <input class="nivel{{$i}} form-control editable" min="0" data-toggle="tooltip" data-placement="down" title="nivel{{$i}}"></input>
                    </td>
                    @endfor
                    <td width="17%">
                    <select class="causaNoToma form-control editable">
                        <option value="-1"></option>
                        @foreach($causasNoTomaProgresivo as $causa)
                        <option value="{{$causa->id_tipo_causa_no_toma_progresivo}}">{{$causa->descripcion}}</option>
                        @endforeach
                    </select>
                    </td>
                </tr>
            </table>
            <table hidden>
                <tr id="filaEjemploContador">
                    <td class="col-xs-6 cont" data-contador=""></td>
                    <td class="col-xs-6">
                        <input class="form-control editable vcont valorModif">
                    </td>
                </tr>
            </table>
            <h6>OBSERVACIONES</h6>
            <div class="row">
                <div class="col-lg-12">
                    <textarea id="observacionesToma" class="form-control editable" style="resize:vertical;"></textarea>
                </div>
            </div> <!-- FIN ULTIMO row -->
            <div class="validacion">
            @if($divRelMov_user->es_controlador)
                <h6>OBSERVACIONES ADMIN:</h6>
                <div class="row">
                    <div class="col-lg-12">
                        <textarea id="observacionesAdmin" class="form-control"  maxlength="200" style="resize:vertical;"></textarea>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-lg-1 col-lg-offset-11">
                        <button type="button" class="btn btn-success validar"><b>VISAR</b></button>
                    </div>
                </div>
            @endif
            </div>
        </form>
    </div> <!-- fin detalle -->
</div>
</div>


<script src="js/utils.js" type="text/javascript"></script>
<script type="text/javascript">
function initDivRelevamientoMovimiento(){
    $('#relFecha').datetimepicker({
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
        //Usuarios
        usuario_carga: {nombre: $('#fiscaCarga').val(), id_usuario: $('#fiscaCarga').attr('data-id')},
        usuario_toma:  {nombre: $('#fiscaToma').val() , id_usuario: $('#fiscaToma').obtenerElementoSeleccionado()},
        //Valores relevados
        fecha_ejecucion: $('#fechaRel').val(),
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
        observaciones: $('#observacionesToma').val(),
        observacionesAdm: $('#observacionesAdmin').val()
    };
}
function limpiarDivRelevamiento(){
    $('#modalCargarRelMov .alerta').each(function(){
        ocultarErrorValidacion($(this));
    });
    $('#modalCargarRelMov input').not('#inputTipoMov,#inputSentido').val('');
    $('#tablaCargarContadores tbody').empty();
    $('#juegoRel').empty();
    $('#tomaProgresivo tbody').empty();
    $('#relFecha').datetimepicker('update','');
}
function agregarContadores(maquina,toma){
    for (let i = 1; i < 7; i++){
        let fila = $('#filaEjemploContador').clone().removeAttr('id');
        let nombre_cont = maquina["cont" + i];
        if(nombre_cont === null) continue;
        let val_cont = null;
        if(toma != null){
            val_cont = toma["vcont" + i];
        }
        fila.find('.cont').text(nombre_cont).attr('data-contador',nombre_cont);
        fila.find('.vcont').val(val_cont != null? val_cont : '');
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
    if(data.fecha != null){
        $('#relFecha').datetimepicker('setDate',new Date(data.fecha));
    }
    if(data.cargador != null) { 
        $('#fiscaCarga').val(data.cargador.nombre).attr('data-id',data.cargador.id_usuario);
    }
    if(data.fiscalizador != null){
        $('#fiscaToma').setearElementoSeleccionado(data.fiscalizador.id_usuario,data.fiscalizador.nombre);
    }
}
function mostrarErroresDiv(response){
    const errores = { 
        'apuesta_max' : $('#apuesta'),'cant_lineas' : $('#cant_lineas'), 'cant_creditos' : $('#creditos'),
        'porcentaje_devolucion' : $('#devolucion'),'juego' : $('#juegoRel'), 'denominacion' : $('#denominacion'),
        'sector_relevado' : $('#sectorRelevadoCargar'), 'isla_relevada' :  $('#islaRelevadaCargar'), 'mac' : $('#macCargar'),
        'id_fiscalizador' : $('#fiscaToma'),'fecha_sala' : $('#fechaRel')
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
    if(err) $("#modalCargarRelMov").animate({ scrollTop: 0 }, "slow");
    return err;
}
function cargarRelevamientos(relevamientos,dibujos = {},estado_listo = -1){
    const agregarToma = function(fila,id_maquina,id_relevamiento,dibujo,toma){
        fila.append($('<td>')
            .addClass('col-xs-3')
            .append($('<button>')
            .append($('<i>')
            .addClass('fa').addClass('fa-fw').addClass(dibujo))
            .attr('type','button')
            .addClass('btn btn-info cargarMaq')
            .attr('id', id_maquina)
            .attr('data-rel', id_relevamiento)
            .attr('toma',toma)
            )
        );
        fila.append($('<td>')
            .addClass('col-xs-3 listo')
            .attr('data-maq', id_maquina)
            .attr('data-rel', id_relevamiento)
            .append($('<i>').addClass('fa fa-fw fa-check faFinalizado'))
        );
    };
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
      let i = 0;//Multiples tomas estan deprecadas pero esto está por compatibilidad para atras
      for(;i<r.tomas;i++){
          agregarToma(fila,r.id_maquina,r.id_relevamiento,dibujo,i+1);
      }
      //El relevamiento no tiene tomas, se va a crear una cuando le mande guardar.
      if(i == 0){
          agregarToma(fila,r.id_maquina,r.id_relevamiento,dibujo,0);
      }

      fila.find('.listo').toggle(r.id_estado_relevamiento == estado_listo);
      $('#tablaCargarMTM tbody').append(fila);
    });
}
function esconderDetalleRelevamiento(){
    $('#relFecha').parent().hide();
    $('#fiscaToma').parent().hide();
    $('#detallesMTM').hide();
}
function mostrarDetalleRelevamiento(){
    $('#relFecha').parent().show();
    $('#fiscaToma').parent().show();
    $('#detallesMTM').show();
}
function setearUsuariosCargaToma(casino,cargador,fiscalizador){
    $('#fiscaToma').generarDataList("usuarios/buscarUsuariosPorNombreYCasino/" + casino.id_casino,'usuarios' ,'id_usuario','nombre',1,false);
    $('#fiscaToma').setearElementoSeleccionado(0,"");
    $('#fiscaCarga').val('');
    $('#fiscaCarga').removeAttr('data-id');

    if(cargador){
        $('#fiscaCarga').attr('data-id',cargador.id_usuario);
        $('#fiscaCarga').val(cargador.nombre);
    }
    if(fiscalizador){
      $('#fiscaToma').setearElementoSeleccionado(fiscalizador.id_usuario,fiscalizador.nombre);
    }
}
function setearTipoMovimiento(tipo_movimiento,sentido){
    $('#inputTipoMov').val(tipo_movimiento);
    $('#inputSentido').val(sentido);
}
function marcarListaMaquina(id_maquina,estado = true){
    $('#tablaCargarMTM').find('.listo[data-maq="'+id_maquina+'"]').toggle(estado);
}
function marcarListaMaquinaPorIdRel(id_relev,estado = true){
    $('#tablaCargarMTM').find('.listo[data-rel="'+id_relev+'"]').toggle(estado);
}
function cambiarDibujoMaquina(id_maquina,dibujo){
    let boton = $('#modalCargarRelMov')
    .find('.cargarMaq[id='+id_maquina+']')[0];
    $(boton).empty();
    $(boton).append($('<i>').addClass(dibujo));
}
function divRelSetearModo(modo){
    if(modo == "VER"){
        $('#divRelevamientoMovimiento .editable').attr('disabled',true);
        $('#relFecha .input-group-addon').hide();
        $('#divRelevamientoMovimiento .validacion').hide();
    }
    else if(modo == "CARGAR"){
        $('#divRelevamientoMovimiento .editable').removeAttr('disabled');
        $('#relFecha .input-group-addon').show();
        $('#divRelevamientoMovimiento .validacion').hide();
    }
    else if(modo == "VALIDAR"){
        $('#divRelevamientoMovimiento .editable').attr('disabled',true);
        $('#relFecha .input-group-addon').hide();
        $('#divRelevamientoMovimiento .validacion').show();
    }
}
</script>
