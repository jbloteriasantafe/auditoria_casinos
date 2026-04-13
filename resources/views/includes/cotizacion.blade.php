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
              <tr data-cotizacion-bna-fila data-cotizacion-bna-molde style="border-top: 1px solid grey;">
                <td data-cotizacion-bna-col="fecha" rowspan="2" style="text-align: left;border-right: 1px solid grey;">YYYY-MM-DD</td>
                <td style="text-align: center;background: #89E1A1;">Dólar</td>
                <td data-cotizacion-bna-col="dolar-compra" style="text-align: right;background: #89E1A1;">1111</td>
                <td data-cotizacion-bna-col="dolar-venta" style="text-align: right;background: #89E1A1;">2222</td>
              </tr>
              <tr data-cotizacion-bna-fila data-cotizacion-bna-molde style="border-bottom: 1px solid grey;">
                <td style="text-align: center;background: #8EB8FF;">Euro</td>
                <td data-cotizacion-bna-col="euro-compra" style="text-align: right;background: #8EB8FF;">3333</td>
                <td data-cotizacion-bna-col="euro-venta" style="text-align: right;background: #8EB8FF;">4444</td>
              </tr>
            </table>
          </div>
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
    $('[data-cotizacion-bna]').on('mostrar',function(e,año_mes){
      const M = $(e.currentTarget);
      const tbody  = M.find('[data-cotizacion-bna-tbody]').empty();
      {
        const fspin = M.find('[data-cotizacion-bna-molde]').clone().removeAttr('data-cotizacion-bna-molde');
        fspin.find('td').empty().append('<i class="fa fa-spinner fa-spin"></i>');
        tbody.append(fspin);
      }
      $.ajax({
        url: '/cotizacion/cotizacionesBNA',
        type: 'GET',
        data: {año_mes: año_mes},
        success: function(data){
          tbody.empty();
          if(Object.entries(data ?? {}).length == 0){
            const fila = M.find('[data-cotizacion-bna-molde]').clone().removeAttr('data-cotizacion-bna-molde');
            fila.find('[data-cotizacion-bna-col="fecha"]').text('SIN DATOS');
            fila.find('[data-cotizacion-bna-col="dolar-compra"]').text('-');
            fila.find('[data-cotizacion-bna-col="dolar-venta"]').text('-');
            fila.find('[data-cotizacion-bna-col="euro-compra"]').text('-');
            fila.find('[data-cotizacion-bna-col="euro-venta"]').text('-');
            tbody.append(fila);
            return;
          }
          for(const fecha_cotizacion in data){
            const fila = M.find('[data-cotizacion-bna-molde]').clone().removeAttr('data-cotizacion-bna-molde');
            const d = data[fecha_cotizacion] ?? {};
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
          const fila = M.find('[data-cotizacion-bna-molde]').clone().removeAttr('data-cotizacion-bna-molde');
          fila.find('[data-cotizacion-bna-col="fecha"]').append('ERROR <br>' + (data?.responseText ?? ''));
          fila.find('[data-cotizacion-bna-col="dolar-compra"]').text('-');
          fila.find('[data-cotizacion-bna-col="dolar-venta"]').text('-');
          fila.find('[data-cotizacion-bna-col="euro-compra"]').text('-');
          fila.find('[data-cotizacion-bna-col="euro-venta"]').text('-');
          tbody.append(fila);
        }
      });
    });
  });
</script>

<script type="module" defer>  
  $(document).ready(function(){
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
        viewRender: function(view,element){
          const start = view.start;
          const end = view.end;
          const middle = new Date(start._i+(end._d - start._d)/2);
          const año_mes = middle.toISOString().substr(0,'YYYY-MM'.length);
          $('[data-cotizacion-bna]').each(function(_,cbna){
            $(cbna).trigger('mostrar',año_mes);
          });
        },
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
          const isodate = function(_d){
            return _d.toISOString().split('T')[0];
          };
          const nd = new Date(date._d);
          let valor = '';
          //Va para atras hasta encontrar el ultimo valor
          //Esto es porque los sabados, domingos y feriados no tienen mercado de cambios
          while(primer_dia !== null){
            const isond = isodate(nd);
            valor = cotizacionBNA[isond] ?? ''; 
            if(isond == primer_dia || valor !== ''){
              break;
            }
            nd.setDate(nd.getDate()-1);
          }
          $('#valorCotizacion').val(valor);
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
