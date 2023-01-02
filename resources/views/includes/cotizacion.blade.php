<?php
use App\Http\Controllers\UsuarioController;
?>
@if(UsuarioController::getInstancia()->quienSoy()['usuario']->tienePermiso('cotizar_dolar_peso'))  
<div class="modal fade" id="modal-cotizacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"  >
    <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
      <h3 class="modal-title">| COTIZACIÓN DÓLAR->PESO</h3>
    </div>
    <div class="modal-body" style="background-color: white;">
      <div class="row" style="padding-bottom: 15px;">
        <div class="col-md-12">
          <div id="calendarioCotizacion"></div>
        </div>
      </div>
      <div class="modal-footer">
        <label id="labelCotizacion" for="number"> </label>
        <input id="valorCotizacion" type="number" step="0.001" min="25" max="200" placeholder="xx,xxx">
        <button type="button" class="btn btn-successAceptar" id="guardarCotizacion">GUARDAR</button>
      </div> 
    </div>
  </div>
</div>

<script type="module" defer>  
  $(document).ready(function(){
    const cambioMes = function(s){
      $('#calendarioCotizacion').fullCalendar(s);
      $('#calendarioCotizacion').fullCalendar('refetchEvents');
    };
    $('#btn-cotizacion').on('click', function(e){
      e.preventDefault();
      //limpio modal
      $('#labelCotizacion').html("");
      $('#labelCotizacion').attr("data-fecha","");
      $('#valorCotizacion').val("");
      //inicio calendario
      $('#calendarioCotizacion').fullCalendar({  // assign calendar
        locale: 'es',
        backgroundColor: "#f00",
        eventTextColor:'yellow',
        editable: false,
        selectable: true,
        allDaySlot: false,
        selectAllow: false,
        customButtons: {
          nextCustom: {
            text: 'Siguiente',
            click: function() {
              cambioMes('next');
            }
          },
          prevCustom: {
            text: 'Anterior',
            click: function() {
              cambioMes('prev');
            }
          },
        },
        header: {
          left: 'prev,next',
          center: 'title',
          right: 'month',
        },
        events: function(start, end, timezone, callback) {
          $.ajax({
            url: 'cotizacion/obtenerCotizaciones/'+ start.format('YYYY-MM'),
            type:"GET",
            success: function(doc) {
              var events = [];
              $(doc).each(function() {
                var numero=""+$(this).attr('valor');
                events.push({
                  title:"" + numero.replace(".", ","),
                  start: $(this).attr('fecha')
                });
              });
              callback(events);
            }
          });
        },
        dayClick: function(date) {
          $('#labelCotizacion').html('Guardar cotización para el día '+ '<u>'  +date.format('DD/M/YYYY') + '</u>' );
          $('#labelCotizacion').attr("data-fecha",date.format('YYYY-MM-DD'));
          $('#valorCotizacion').val("");
          $('#valorCotizacion').focus();
        },
      });
      $('#modal-cotizacion').modal('show')
    });
    
    $('#guardarCotizacion').click(function(){
      $.ajax({
        type: 'POST',
        url: 'cotizacion/guardarCotizacion',
        data: {
          fecha: $('#labelCotizacion').attr('data-fecha'),
          valor: $('#valorCotizacion').val(),
        },
        success: function (data) {
          $('#calendarioCotizacion').fullCalendar('refetchEvents');
          $('#labelCotizacion').html("");//limpio modal
          $('#labelCotizacion').attr("data-fecha","");
          $('#valorCotizacion').val("");
        }
      });
    });
  });
</script>
@endif
