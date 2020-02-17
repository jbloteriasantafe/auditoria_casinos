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

<script src="js/utils.js" type="text/javascript"></script>
<script type="text/javascript">
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
</script>
