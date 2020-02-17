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
        <div class="row">
            <div class="col-lg-12" id="tomaProgresivo" style="overflow: scroll;max-height: 250px;">
                <h6>PROGRESIVOS</h6>
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
        <div class="row">
            <div class="col-lg-12">
                <h6>OBSERVACIONES</h6>
                <textarea id="observacionesToma" value="" class="form-control" style="resize:vertical;"></textarea>
            </div>
        </div> <!-- FIN ULTIMO row -->
    </form>
</div> <!-- fin detalle -->


<script src="js/utils.js" type="text/javascript"></script>
<script type="text/javascript">
//DATOS DE LA MAQUINA

function limpiarDatosMaquina(){
    $('#macCargar').val("");
    $('#islaRelevadaCargar').val("");
    $('#sectorRelevadoCargar').val("");
}
function ocultarErroresDatosMaquina(){
    ocultarErrorValidacion($('#macCargar'));
}
function mostrarErrorDatosMaquinaMac(err){
    mostrarErrorValidacion($('#macCargar'),err);
}
function setearDatosMaquina(maquina){
    $('#nro_islaMov').val(maquina.nro_isla);
    $('#nro_adminMov').val(maquina.nro_admin);
    $('#nro_serieMov').val(limpiarNullUndef(maquina.nro_serie,''));
    $('#marcaMov').val(maquina.marca);
    $('#modeloMov').val(limpiarNullUndef(maquina.modelo,''));
}
function setearDatosMaquinaToma(toma){
    if(toma != null){
        $('#macCargar').val(toma.mac);
        $('#sectorRelevadoCargar').val(toma.descripcion_sector_relevado);
        $('#islaRelevadaCargar').val(toma.nro_isla_relevada);
    }
}
function obtenerDatosMaquinaToma(){
    let mac = $('#macCargar').val();
    let islaRelevadaCargar = $('#islaRelevadaCargar').val();
    let sectorRelevadoCargar = $('#sectorRelevadoCargar').val();
    return {mac:mac,isla: islaRelevadaCargar,sector:sectorRelevadoCargar};
}

//CONTADORES

function agregarContadores(maquina,toma){
    $('#tablaCargarContadores tbody').empty();
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
function obtenerDatosContadores(){
    let tabla = $('#tablaCargarContadores tbody > tr');
    let contadores=[];
    $.each(tabla, function(index, value){
        const cont={
            nombre: $(this).attr('data-contador'),
            valor: $(this).find('.valorModif').val()
        }
        contadores.push(cont);
    });
    return contadores;
}

// TOMA

function agregarJuegosToma(nombre_juego,juegos){
    if(nombre_juego==null){
        $('#juegoRel')
        .append($('<option>')
            .val(0)
            .text('Seleccione')
        );
        juegos.forEach(j => {
            $('#juegoRel').append($('<option>')
                .val(j.id_juego)
                .text(j.nombre_juego)
            );
        });
    }
    else{
        $('#juegoRel')
        .append($('<option>')
        .val(juegos[0].id_juego)
        .text(nombre_juego));
    }
}
function setearDatosToma(toma){
    if(toma != null){
        $('#juegoRel option:selected').val(toma.juego);
        $('#apuesta').val(toma.apuesta_max);
        $('#cant_lineas').val(toma.cant_lineas);
        $('#devolucion').val(toma.porcentaje_devolucion);
        $('#denominacion').val(toma.denominacion);
        $('#creditos').val(toma.cant_creditos);
        $('#observacionesToma').val(toma.observaciones);
    }
}
function obtenerDatosToma(){
    return {
        juego: $('#juegoRel').val(),
        apuesta: $('#apuesta').val(),
        lineas: $('#cant_lineas').val(),
        devolucion: $('#devolucion').val(),
        denominacion: $('#denominacion').val(), 
        creditos: $('#creditos').val()
    };
}
function limpiarDatosToma(){
    ocultarErrorValidacion($('#juegoRel'));
    ocultarErrorValidacion($('#apuesta'));
    ocultarErrorValidacion($('#cant_lineas'));
    ocultarErrorValidacion($('#creditos'));
    ocultarErrorValidacion($('#denominacion'));
    ocultarErrorValidacion($('#devolucion'));
    $('#juegoRel option').remove();
}
function habilitarDatosToma(hab){
  const not = !hab;  
  $('#apuesta').prop('disabled',not);
  $('#devolucion').prop('disabled',not);
  $('#denominacion').prop('disabled',not);
  $('#creditos').prop('disabled',not);
  $('#cant_lineas').prop('disabled',not);
  $('#juegoRel').prop('disabled',not);
}

// PROGRESIVOS

function agregarProgresivos(progresivos){
  $('#tomaProgresivo tbody').empty();
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

function obtenerDatosProgresivos(){
  progresivos = [];
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
  return progresivos;
}
</script>
