<h6>TOMA</h6>
<div class="row"> <!-- PRIMER ROW DE TOMA -->
  <div class="col-lg-4">
    <h5>JUEGO</h5>
    <select id="juegoRel" class="form-control" name="">
      <option value=""></option>
    </select>
    <br>
  </div>
  <div class="col-lg-4">
    <h5>APUESTA MÁX</h5>
    <input id="apuesta" type="text" value="" class="form-control">
  </div>
  <div class="col-lg-4">
    <h5>CANT LÍNEAS</h5>
    <input id="cant_lineas" type="text" value="" class="form-control">
  </div>
</div> <!-- FIN PRIMER ROW DE TOMA -->
<div class="row"> <!-- SEGUNDO ROW DE TOMA -->
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
</div> <!-- FIN SEGUNDO ROW DE TOMA -->

<script type="text/javascript">
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
</script>