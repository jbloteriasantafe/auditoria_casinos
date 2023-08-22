@component('../CierresAperturas/include_guard',['nombre' => 'modalCargarModificar'])
<style>
  .modalCargarModificar[data-tipo="CARGAR"] .modal-header,
  .modalCargarModificar[data-tipo="VALIDAR"] .modal-header,
  .modalCargarModificar[data-tipo="VER"] .modal-header {
    background-color: #6dc7be;
  }
  .modalCargarModificar[data-tipo="MODIFICAR"] .modal-header {
    background-color: #FFA726;
  }
  .modalCargarModificar[data-tipo="CARGAR"] .btn-guardar {
    background-color: #6dc7be !important;
  }
  .modalCargarModificar[data-tipo="MODIFICAR"] .btn-guardar {
    background-color: #FFA726 !important;
  }
  
  .modalCargarModificar .tablaApuestasMinimas th,
  .modalCargarModificar .tablaApuestasMinimas td,
  .modalCargarModificar .tablaApuestasMinimas .form-control{
    text-align: center !important;
  }
  .modalCargarModificar .tablaApuestasMinimas th {
    color: #212121 !important;
    font-size: 17px;
    font-weight: bold;
  }
</style>
@endcomponent

@component('CierresAperturas/modal',[
  'clases_modal' => 'modalCargarModificar',
  'attrs_modal' => 'data-js-cargar-modificar-validar',
  'grande' => 80,
])
  @slot('titulo')
    <span data-js-tipo>XXXXX</span> RELEVAMIENTO DE VALORES DE APUESTAS
  @endslot
  @slot('cuerpo')
    <div class="row" data-js-datos-relevamiento style="border-bottom:2px solid #ccc;">
      <input name="id_relevamiento_apuestas" hidden>
      <div class="col-md-3">
        <h5 style="font-size:16px !important;">FECHA</h5>
        @component('CierresAperturas/inputFecha',['attrs' => 'name="fecha"'])
        @endcomponent
      </div>
      <div class="col-md-3">
        <h5 style="font-size:16px !important;">HORA PROPUESTA</h5>
        <input type="time" name="hora_propuesta" class="form-control" style="padding-top:0px;">
      </div>
      <div class="col-md-3">
        <h5 style="font-size:16px !important;">HORA EJECUCIÓN</h5>
        <input type="time" name="hora_ejecucion" class="form-control" style="padding-top:0px;" data-js-habilitar="CARGAR,MODIFICAR">
      </div>
      <div class="col-md-3">
        <h5 style="font-size:16px !important;">TURNO</h5>
        <input type="text" name="id_turno" class="form-control" readonly="true" data-js-formdata-attr="data-id_turno">
      </div>
    </div>
    <div class="row" style="border-bottom:2px solid #ccc;">
      @if($puede_validar)
      <div class="col-md-4" style="border-right:1px solid #ccc;" data-js-mostrar="VER,VALIDAR">
        <h5 style="font-size:16px !important;">MESAS ABIERTAS</h5>
        <div style="max-height: 20vh;overflow-y: scroll;">
          <table style="width:100%;" class="table table-responsive" data-js-mesas-abiertas>
            <thead>
              <tr>
                <th class="col-xs-5" style="text-align:center !important;border-right:2px solid #ccc;">
                  JUEGO
                </th>
                <th class="col-xs-4" style="text-align:center !important;">
                  CANTIDAD
                </th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
          <table hidden>
            <tr data-js-molde-mesas-abiertas>
              <td class="juego" style="text-align: center;">#JUEGO#</td>
              <td class="mesas_abiertas" style="text-align: center;">999999</td>
            </tr>
          </table>
        </div>
        <h5 style="font-size:15px !important;" data-js-mostrar="VER,VALIDAR">
          CUMPLIÓ MÍNIMO
          <i data-cumplio-minimo="1" class="fa fa-fw fa-check" style="color: rgb(76, 175, 80);"></i>
          <i data-cumplio-minimo="0" class="fa fa-fw fa-times" style="color: rgb(211, 47, 47);"></i>
        </h5>
      </div>
      @endif
      <div class="col-md-4" data-js-mostrar="CARGAR,MODIFICAR">
        <h5 style="font-size:16px !important;">FISCALIZADOR DE TOMA</h5>
        <div class="input-group">
          <input data-js-fiscalizador class="form-control" type="text" value="" autocomplete="off" placeholder="Nombre Fiscalizador" >
          <span class="input-group-btn" style="display:block;">
            <button data-js-click-agregar-fiscalizador class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
          </span>
        </div>
      </div>
      <div class="col-md-5" style="border-right:1px solid #ccc">
        <h5 style="font-size:16px !important;">FISCALIZADORES DE TOMA</h5>
        <div style="max-height: 20vh;overflow-y: scroll;">
          <table class="table" data-js-fiscalizadores>
            <tbody>
            </tbody>
          </table>
          <table hidden>
            <tr data-js-molde-fiscalizador>
              <td class="usuario" style="margin-top: 0px; margin-bottom: 0px;" name="id_usuario" data-js-formdata-attr="data-id_usuario">
                FISCALIZADOR
              </td>
              <td style="margin-top: 0px; margin-bottom: 0px;" class="col-xs-2">
                <button data-js-borrar-fiscalizador data-js-mostrar="CARGAR,MODIFICAR">
                  <i class="fas fa-fw fa-trash"></i>
                </button>
              </td>
            </tr>
          </table>
        </div>
      </div>
      <div class="col-md-3">
        <h5 style="font-size:15px !important">A: Abierta</h5>
        <h5 style="font-size:15px !important">C: Cerrada</h5>
        <h5 style="font-size:15px !important">T: En Torneo</h5>
      </div>
    </div>
    <br><br>
    <div class="row mesas" style="height: 60vh;overflow-y: scroll;">
      <table data-js-mesas class="tablaApuestasMinimas table table-striped">
        <thead>
          <th class="col-xs-2">MESA</th>
          <th class="col-xs-2">MONEDA</th>
          <th class="col-xs-2">POSICIONES</th>
          <th class="col-xs-2">ESTADO (A|C|T)</th>
          <th class="col-xs-2">MÍNIMA</th>
          <th class="col-xs-2">MÁXIMA</th>
        </thead>
        <tbody>
        </tbody>
      </table>
      <table hidden>
        <tr data-js-molde-mesa>
          <td class="mesa" name="id_detalle_relevamiento_apuestas" data-js-formdata-attr="data-id_detalle_relevamiento_apuestas">MESA</td>
          <td>
            <select class="form-control moneda" name="id_moneda" data-js-habilitar="CARGAR,MODIFICAR">
              <option value="">- SELECCIONE -</option>
              @foreach($monedas as $m)
              <option value="{{$m->id_moneda}}">{{$m->descripcion}}</option>
              @endforeach
            </select>
          </td>
          <td class="posiciones">POSICIONES</td>
          <td>
            <select class="form-control estado" name="id_estado_mesa" data-js-habilitar="CARGAR,MODIFICAR">
              <option value="">- SELECCIONE -</option>
              @foreach($estados_mesa as $e)
              <option value="{{$e->id_estado_mesa}}" title="{{$e->descripcion_mesa}}">{{$e->siglas_mesa}}</option>
              @endforeach
            </select>
          </td>
          <td class="col-xs-2">
            <input type="text" class="form-control minimo" name="minimo" data-js-habilitar="CARGAR,MODIFICAR">
          </td>
          <td class="col-xs-2">
            <input type="text" class="form-control maximo" name="maximo" data-js-habilitar="CARGAR,MODIFICAR">
          </td>
        </tr>
      </table>
    </div>
    <div class="row" data-js-datos-relevamiento>
      <div class="col-md-10 col-md-offset-1">
        <h6>OBSERVACIONES:</h6>
        <textarea data-js-habilitar="CARGAR,MODIFICAR" name="observaciones" style="resize: vertical;width: 100%;"></textarea>
      </div>
    </div>
    @if($puede_validar)
    <div class="row" data-js-datos-relevamiento data-js-mostrar="VER,VALIDAR">
      <div class="col-md-10 col-md-offset-1">
        <h6>OBSERVACIONES VALIDACIÓN:</h6>
        <textarea data-js-habilitar="VALIDAR" name="observaciones_validacion" style="resize: vertical;width: 100%;"></textarea>
      </div>
    </div>
    @endif
  @endslot
  @slot('pie')
    <button type="button" class="btn btn-guardar" data-js-guardar data-js-mostrar="CARGAR,MODIFICAR" style="color: white !important;">GUARDAR</button>
    @if($puede_validar)
    <button type="button" class="btn btn-validar" data-js-validar data-js-mostrar="VALIDAR" style="color: white !important;background-color: #6dc7be !important;">VALIDAR</button>
    @endif
  @endslot
@endcomponent
