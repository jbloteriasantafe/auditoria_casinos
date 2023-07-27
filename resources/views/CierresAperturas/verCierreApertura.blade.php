<style>
  .verCierreApertura {
    font-family: Roboto;
  }
  .verCierreApertura .modal-header {
    background-color:#0D47A1;
  }
  .verCierreApertura .modal-header button {
    margin: 2px !important;
  }
  .verCierreApertura .modal-body .titulo_datos {
    margin: 0px;
    text-align: center;
  }
  .verCierreApertura .borde_arriba {
    border-top:1px solid #ccc;
  }
  .verCierreApertura .bordes_columnas > *:not(:last-child) {
    border-right:1px solid #ccc;
  }
  .verCierreApertura .div_icono_texto {
    display: flex;
    flex-wrap: wrap;
    align-content: center;
  }
  .verCierreApertura .div_icono_texto h5 {
    color: #000 !important;
    font-size: 14px;
  }
  .verCierreApertura .tablaFichas thead tr th {
    text-align: center;
    font-size: 1.1em;
  }
  .verCierreApertura .tablaFichas tbody tr td {
    text-align: right;
  }
</style>

<div class="modal fade verCierreApertura" data-js-ver-cierre-apertura tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" >
  <div data-js-ver-cierre hidden></div>
  <div data-js-ver-apertura hidden></div>
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">
          <i class="fa fa-times"></i>
        </button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target=".verCierreApertura .collapse">
          <i class="fa fa-window-minimize"></i>
        </button>
        <h3 class="modal-title">DETALLE</h3>
      </div>
      <div class="collapse in">
        <div class="modal-body">
          @foreach(['Cierre','Apertura'] as $tipo)
          <div class="row datos{{$tipo}}">
            <div class="row">
              <h3 class="titulo_datos">{{$tipo}}</h3>
            </div>
            <div class="row datos">
              <div class="col-xs-12 bordes_columnas borde_arriba">
                <div class="col-xs-4">
                  <h6>MESA</h6>
                  <div class="col-xs-12 div_icono_texto">
                    <i class="fas fa-clipboard-check fa-2x"></i>
                    <h5 class="nro_mesa">nro mesa</h5>
                  </div>
                </div>
                <div class="col-xs-4">
                  <h6>JUEGO</h6>
                  <div class="col-xs-12 div_icono_texto">
                    <i class="fas fa-dice fa-2x"></i>
                    <h5 class="nombre_juego"></h5>
                  </div>
                </div>
                <div class="col-xs-4">
                  <h6>FISCALIZADOR</h6>
                  <div class="col-xs-12 div_icono_texto">
                    <i class="far fa-user fa-2x"></i>
                    <h5 class="fiscalizador"></h5>
                  </div>
                </div>
              </div>
            </div>
            <div class="row datos">
              <div class="col-xs-12 bordes_columnas borde_arriba">
                @if($tipo == 'Cierre')
                <div class="col-xs-4">
                  <h6>HORA INICIO</h6>
                  <div class="col-xs-12 div_icono_texto">
                    <i class="far fa-clock fa-2x"></i>
                    <h5 class="hora_inicio">10:20 H</h5>
                  </div>
                </div>
                <div class="col-xs-4">
                  <h6>HORA FIN</h6>
                  <div class="col-xs-12 div_icono_texto">
                    <i class="far fa-clock fa-2x"></i>
                    <h5 class="hora_fin">10:20 H</h5>
                  </div>
                </div>
                @else
                <div class="col-xs-4">
                  <h6>FISCALIZADOR DE CARGA</h6>
                  <div class="col-xs-12 div_icono_texto">
                    <i class="far fa-user fa-2x"></i>
                    <h5 class="cargador"></h5>
                  </div>
                </div>
                <div class="col-xs-4">
                  <h6>HORA</h6>
                  <div class="col-xs-12 div_icono_texto">
                    <i class="far fa-clock fa-2x"></i>
                    <h5 class="hora">10:20 H</h5>
                  </div>
                </div>
                @endif
                <div class="col-xs-4">
                  <h6>FECHA</h6>
                  <div class="col-xs-12 div_icono_texto">
                    <i class="far fa-calendar-alt fa-2x"></i>
                    <h5 class="fecha">10-10-1990</h5>
                  </div>
                </div>
              </div>
            </div>
            <div class="row datos">
              <div class="col-xs-12 bordes_columnas borde_arriba">
                <div class="col-xs-6">
                  <h6>FICHAS</h6>
                  <table class="table table-striped tablaFichas">
                    <thead>
                      <tr class="bordes_columnas">
                        <th>Valor</th>
                        @if($tipo == 'Apertura')
                        <th>Fichas</th>
                        @endif
                        <th>Monto</th>
                      </tr>
                    </thead>
                    <tbody>
                    </tbody>
                  </table>
                  <table hidden>
                    <tr class="moldeFila">
                      <td class="valor_ficha">Valor</td>
                      @if($tipo == 'Apertura')
                      <td class="cantidad_ficha">Fichas</td>
                      @endif
                      <td class="monto_ficha">Monto</td>
                    </tr>
                  </table>
                </div>
                <div class="col-xs-6">
                  <div class="row">
                    @if($tipo == 'Cierre')
                    <div class="col-xs-12">
                      <h6>TOTAL</h6>
                      <input type="text" class="total_pesos_fichas_c" readonly="true">
                    </div>
                    <div class="col-xs-12">
                      <h6>TOTAL ANTICIPOS</h6>
                      <input type="text" class="total_anticipos_c" readonly="true">
                    </div>
                    @else
                    <div class="col-xs-12">
                      <h6>TOTAL</h6>
                      <input type="text" class="total_pesos_fichas_a" readonly="true">
                    </div>
                    @endif
                    <div class="col-xs-12">
                      <h6>Observaciones</h6>
                      <p class="observacion"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <br>
          @endforeach
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
