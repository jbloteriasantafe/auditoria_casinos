<?php
use App\Http\Controllers\UsuarioController;
?>
@if(UsuarioController::getInstancia()->quienSoy()['usuario']->tienePermiso('cotizar_dolar_peso')) 
<div class="modal fade" id="modal-cotizacion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width: 70%;">
    <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
      <h3 class="modal-title">| COTIZACIÓN DÓLAR->PESO</h3>
    </div>
    <div class="modal-body" style="background-color: white;">
      <div class="row" style="padding-bottom: 15px;display: flex;">
        <div style="width: 70%;">
          <div id="calendarioCotizacion"></div>
        </div>
        <div style="width: 30%;height: 50vh;font-size: 0.9em !important;">
          <div style="width: 100%;border: 1px solid darkblue;max-height: 20%;">
            <table style="width: 100%;">
              <colgroup>
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
              </colgroup>
              <thead>
                <tr>
                  <th colspan="4" style="text-align: center;background: darkblue;color: white !important;font-weight: bolder;">DATOS BNA</th>
                </tr>
                <tr>
                  <th style="text-align: center;">FECHA</th>
                  <th style="text-align: center;">MONEDA</th>
                  <th style="text-align: center;">COMPRA</th>
                  <th style="text-align: center;">VENTA</th>
                </tr>
              </thead>
            </table>
          </div>
          <div data-cotizacion-bna style="width: 100%;border: 1px solid darkblue;max-height: 80%;overflow-y: scroll;">
            <table style="width: 100%;">
              <colgroup>
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
              </colgroup>
              <tbody data-cotizacion-bna-tbody>
              </tbody>
            </table>
            <table hidden>
              <tr data-cotizacion-bna-fila data-cotizacion-bna-molde="datos" style="border-top: 1px solid grey;">
                <td data-cotizacion-bna-col="fecha" rowspan="2" style="text-align: left;border-right: 1px solid grey;">YYYY-MM-DD</td>
                <td style="text-align: center;background: #89E1A1;">Dólar</td>
                <td data-cotizacion-bna-col="dolar-compra" style="text-align: right;background: #89E1A1;">1111</td>
                <td data-cotizacion-bna-col="dolar-venta" style="text-align: right;background: #89E1A1;">2222</td>
              </tr>
              <tr data-cotizacion-bna-fila data-cotizacion-bna-molde="datos" style="border-bottom: 1px solid grey;">
                <td style="text-align: center;background: #8EB8FF;">Euro</td>
                <td data-cotizacion-bna-col="euro-compra" style="text-align: right;background: #8EB8FF;">3333</td>
                <td data-cotizacion-bna-col="euro-venta" style="text-align: right;background: #8EB8FF;">4444</td>
              </tr>
              <tr data-cotizacion-bna-fila data-cotizacion-bna-molde="celda" style="border-bottom: 1px solid grey;">
                <td data-cotizacion-bna-col="celda" colspan="4">&nbsp;</td>
              </tr>
            </table>
          </div>
        </div>
        
      </div>
      <div class="modal-footer">
        <label id="labelCotizacion" for="number"> </label>
        <input id="valorCotizacion" placeholder="xx,xxx">
        <button type="button" class="btn btn-successAceptar" id="guardarCotizacion">GUARDAR</button>
      </div> 
    </div>
  </div>
</div>

<script type="module" defer>
  $(document).ready(function(){
    $('[data-cotizacion-bna]').on('mostrar',function(e,año_mes){
      const M = $(e.currentTarget);
      const tbody  = M.find('[data-cotizacion-bna-tbody]').empty();
      {
        const fila = M.find('[data-cotizacion-bna-molde="celda"]').clone().removeAttr('data-cotizacion-bna-molde');
        fila.attr('data-cotizacion-bna-fila','CARGANDO');
        fila.find('[data-cotizacion-bna-col="celda"]').empty().append('<i class="fa fa-spinner fa-spin"></i>');
        tbody.append(fila);
      }
      $.ajax({
        url: '/cotizacion/cotizacionesBNA',
        type: 'GET',
        data: {año_mes: año_mes},
        success: function(data){
          tbody.empty();
          if(Object.entries(data ?? {}).length == 0){
            const fila = M.find('[data-cotizacion-bna-molde="celda"]').clone().removeAttr('data-cotizacion-bna-molde');
            fila.attr('data-cotizacion-bna-fila','SIN-DATOS');
            fila.find('[data-cotizacion-bna-col="celda"]').text('SIN DATOS');
            tbody.append(fila);
            return;
          }
          for(const fecha_cotizacion in data){
            const fila = M.find('[data-cotizacion-bna-molde="datos"]').clone().removeAttr('data-cotizacion-bna-molde');
            const d = data[fecha_cotizacion] ?? {};
            fila.attr('data-cotizacion-bna-fila',fecha_cotizacion);
            fila.find('[data-cotizacion-bna-col="fecha"]').text(fecha_cotizacion);
            fila.find('[data-cotizacion-bna-col="dolar-compra"]').text(d?.dolar?.compra ?? '-');
            fila.find('[data-cotizacion-bna-col="dolar-venta"]').text(d?.dolar?.venta ?? '-');
            fila.find('[data-cotizacion-bna-col="euro-compra"]').text(d?.euro?.compra ?? '-');
            fila.find('[data-cotizacion-bna-col="euro-venta"]').text(d?.euro?.venta ?? '-');
            tbody.append(fila);
          }
        },
        error: function(data){
          tbody.empty();
          const fila = M.find('[data-cotizacion-bna-molde="celda"]').clone().removeAttr('data-cotizacion-bna-molde');
          fila.attr('data-cotizacion-bna-fila','ERROR');
          fila.find('[data-cotizacion-bna-col="celda"]').empty()
          .append('ERROR <br>' + (data?.responseText ?? ''))
          .css('background','lightred');
          tbody.append(fila);
        }
      });
    });
  });
</script>

<script type="module" defer>  
  $(document).ready(function(){
    let cotizacionesCalendario = {};
    $('#btn-cotizacion').on('click', function(e){
      e.preventDefault();
      //limpio modal
      $('#labelCotizacion').html("");
      $('#labelCotizacion').attr("data-fecha","");
      $('#valorCotizacion').val("").hide();
      ocultarErrorValidacion($('#valorCotizacion'));
      $('#guardarCotizacion').hide();
      cotizacionesCalendario = {};
      //inicio calendario
      $('#calendarioCotizacion').fullCalendar({  // assign calendar
        locale: 'es',
        backgroundColor: "#f00",
        eventTextColor:'yellow',
        editable: false,
        selectable: true,
        allDaySlot: false,
        selectAllow: false,
        viewRender: function(view,element){
          const start = view.start;
          const end = view.end;
          const middle = new Date(start._i+(end._d - start._d)/2);
          const año_mes = middle.toISOString().substr(0,'YYYY-MM'.length);
          $('[data-cotizacion-bna]').each(function(_,cbna){
            $(cbna).trigger('mostrar',año_mes);
          });
        },
        header: {
          left: 'prev,next',
          center: 'title',
          right: 'month',
        },
        events: function(start, end, timezone, callback) {
          ocultarErrorValidacion($('#valorCotizacion'));
          $.ajax({
            url: 'cotizacion/obtenerCotizaciones/'+ start.format('YYYY-MM'),
            type:"GET",
            success: function(doc) {
              cotizacionesCalendario = {};
              var events = [];
              $(doc).each(function() {
                const fecha  = $(this).attr('fecha');
                const numero = (""+$(this).attr('valor')).replace(".", ",");
                cotizacionesCalendario[fecha] = numero;
                events.push({
                  title: numero,
                  start: fecha
                });
              });
              callback(events);
            }
          });
        },
        dayClick: function(date) {
          const fecha = date.format('YYYY-MM-DD');
          $('#labelCotizacion').html('Guardar cotización para el día '+'<u>'+ fecha+'</u>' );
          $('#labelCotizacion').attr("data-fecha",fecha);          
          $('#valorCotizacion').val(cotizacionesCalendario[fecha] ?? '');
          ocultarErrorValidacion($('#valorCotizacion'));
          $('#guardarCotizacion').show();
          $('#valorCotizacion').show().focus();
        },
      });
      $('#modal-cotizacion').modal('show')
    });
    
    $('#guardarCotizacion').click(function(){
      ocultarErrorValidacion($('#valorCotizacion'));
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
          $('#valorCotizacion').val("").hide();
          $('#guardarCotizacion').hide();
          cotizacionesCalendario[$('#labelCotizacion').attr('data-fecha')] = $('#valorCotizacion').val();
        },
        error: function(data){
          const errores = data.responseJSON;
          const error_arr = [];
          for(const campo in errores){
            error_arr.push(campo+': '+errores[campo].join(', '));
          }
          mostrarErrorValidacion($('#valorCotizacion'),error_arr.join(" | "),true);
        }
      });
    });
  });
</script>
@endif
