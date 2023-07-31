@component('CierresAperturas/include_guard',['nombre' => 'cmApertura_cmvCierre'])
<link rel="stylesheet" href="/css/lista-datos.css">
<style>
  .cmApertura_cmvCierre .modal-lg {
    width: 50%;
  }
  .cmApertura_cmvCierre .mesa_seleccionada {
    background-color: #E0E0E0;
  }
  .cmApertura_cmvCierre .tablaFichas {
    width: 100%;
  }
  .cmApertura_cmvCierre .tablaFichas th {
    text-align: center;
    font-size: 1.1em;
  }
  .cmApertura_cmvCierre .align-right {
    text-align: right !important;
  }
  .cmApertura_cmvCierre .tablaMesas tbody tr i {
    padding: 0.15em;
  }
  .cmApertura_cmvCierre [name="observacion"] {
    background-color: transparent;
    border: 1px solid #000000;
    height: 100%;
    width: 100%;
    scrollbar-arrow-color: #000066;
    scrollbar-base-color: #000033;
    scrollbar-dark-shadow-color: #336699;
    scrollbar-track-color: #666633;
    scrollbar-face-color: #cc9933;
    scrollbar-shadow-color: #DDDDDD;
    scrollbar-highlight-color: #CCCCCC;
    resize: vertical;
  }
</style>
@endcomponent

@component('CierresAperturas/modal',[
  'clases_modal' => 'cmApertura_cmvCierre',
  'attrs_modal' => 'data-js-cargar-apertura-cierre',
  'estilo_cabecera' => 'background-color:#6dc7be;',
  'grande' => 60,
])
  @slot('titulo')
    <span class="tipo">CARGAR XXXXX</span>
  @endslot
  @slot('cuerpo')
  <div data-js-cargar-apertura    hidden></div>
  <div data-js-modificar-apertura hidden></div>
  <div data-js-cargar-cierre      hidden></div>
  <div data-js-modificar-cierre   hidden></div>
  <div data-js-validar-cierre     hidden></div>
  <input class="quienSoy" value="{{$usuario->nombre}}" data-elemento-seleccionado="{{$usuario->id_usuario}}" readonly style="display: none;">
  <div class="row" style="border-bottom:2px solid #ccc;">
    <div class="col-md-4">
      <h6>FECHA</h6>
      <div class="form-group">
        @component('CierresAperturas/inputFecha',['attrs' => 'name="fecha"'])
        @endcomponent
      </div>
    </div>
    <div class="col-xs-4">
      <h6>CASINO</h6>
      <select class="form-control" name="id_casino" data-js-casino>
        <option value="" selected>- Seleccione un Casino -</option>
        @foreach ($casinos as $cas)
        <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="row inputMesas" hidden>
    <div class="row">
      <div class="col-md-6">
        <h6 data-js-campo-cargar>Agregar Mesa</h6>
        <div class="row" data-js-campo-cargar>
          <div class="input-group">
            <input class="form-control mesa" type="text" autocomplete="off" placeholder="Nro. de Mesa" >
            <span class="input-group-btn" style="display:block;">
              <button class="btn btn-default btn-lista-datos" data-js-agregar-mesa type="button"><i class="fa fa-plus"></i></button>
            </span>
          </div>
        </div>
      </div> 
      <div class="col-md-4">
        <h6>FISCALIZADOR DE CARGA</h6>
        <input class="form-control" name="id_cargador" data-js-formdata-attr="data-elemento-seleccionado" readonly>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-4 inputMesas" hidden>
        <h6><b>MESAS</b></h6>
        <table class="table tablaMesas">
          <thead>
            <tr>
              <th class="col-xs-4" style="border-right:2px solid #ccc;">NRO</th>
              <th class="col-xs-8"></th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
        <table hidden>
          <tr class="moldeFila">
            <td class="nro_mesa">99999999</td>
            <td>
              <button data-js-cargar data-js-campo-cargar-modificar>
                <i class="fas fa-fw fa-pencil-alt"></i>
              </button>
              <button data-js-ver data-js-campo-validar>
                <i class="fas fa-fw fa-eye"></i>
              </button>
              <button data-js-borrar data-js-campo-cargar>
                <i class="fas fa-fw fa-trash"></i>
              </button>
            </td>
          </tr>
        </table>
      </div>
      <div class="col-xs-8 datosCierreApertura" style="border-left:2px solid #ccc; border-right:2px solid #ccc;" hidden>
        <h6 style="border-bottom:1px solid #ccc"><b>DETALLES</b></h6>
        <div>
          <div class="row">
            <div class="col-md-4">
              <h6>MONEDA</h6>
              <select class="form-control" name="id_moneda" data-js-moneda>
                <option value="" selected>- Moneda -</option>
                @foreach ($monedas as $m)
                <option value="{{$m->id_moneda}}">{{$m->descripcion}}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4" data-js-campo-cierres>
              <h6>HORA DE APERTURA</h6>
              <input name="hora_inicio" type="time" class="form-control" format="hh:mm">
            </div>
            <div class="col-md-4" data-js-campo-cierres>
              <h6>HORA CIERRE</h6>
              <input name="hora_fin" type="time" class="form-control" format="hh:mm">
            </div>
            <div class="col-md-4" data-js-campo-aperturas>
              <h6>HORA</h6>
              <input name="hora" type="time" class="form-control" format="hh:mm">
            </div>
            <div class="col-md-4" data-js-campo-aperturas>
              <h6>FISCALIZADOR DE TOMA</h6>
              <input class="form-control" name="id_fiscalizador" type="text" data-js-formdata-attr="data-elemento-seleccionado">
            </div>
          </div>
        </div>
        <hr>
        <h6 align="center">FICHAS</h6>
        <div class="row">
          <div class="col-xs-6" data-js-tablas-fichas>
          </div>
          <table class="tablaFichas" data-js-molde-tabla hidden>
            <thead>
              <tr>
                <th>VALOR</th>
                <th data-js-campo-aperturas>CANTIDAD</th>
                <th data-js-campo-cierres>MONTO</th>
              </tr>
            </thead>
            <tbody>
              <tr data-js-molde-ficha>
                <td>
                  <input class="form-control align-right valor_ficha" data-js-cambio-ficha readonly>
                </td>
                <td data-js-campo-aperturas>
                  <input class="form-control align-right cantidad_ficha" data-js-cambio-ficha>
                </td>
                <td data-js-campo-cierres>
                  <input class="form-control align-right monto_ficha" data-js-cambio-ficha>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="col-xs-6">
            <h6><b>TOTAL: </b></h6>
            <input class="form-control align-right" name="total_pesos_fichas_c" readonly data-js-campo-cierres>
            <input class="form-control align-right" name="total_pesos_fichas_a" readonly data-js-campo-aperturas>
            <h6 data-js-campo-cierres><b>TOTAL ANTICIPOS ($): </b></h6>
            <input class="form-control align-right" name="total_anticipos_c" data-js-campo-cierres>
          </div>
        </div>
        <hr>
        <div class="row">
          <div class"col-md-12" data-js-campo-validar>
            <textarea name="observacion"></textarea>
          </div>
          <div class="col-md-12" data-js-campo-validar>
            <div class="col-md-offset-10">
              <button type="button" class="btn btn-success btn-validar" data-js-validar style="font-family: Roboto-Condensed;">VALIDAR</button>
            </div>
          </div>
          <div class="col-md-12" data-js-campo-cargar-modificar>
            <div class="col-md-offset-10">
              <button type="button" class="btn btn-primary btn-guardar" data-js-guardar style="font-family: Roboto-Condensed;">GUARDAR</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endslot
@endcomponent
