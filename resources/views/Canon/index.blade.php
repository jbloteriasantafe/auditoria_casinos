@extends('includes.dashboard')
@section('headerLogo')
@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/js/jquery-ui-1.12.1.custom/jquery-ui.css">
<link rel="stylesheet" href="/css/paginacion.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/10.1.0/jsoneditor.css"/>
<style>
  #mensajeExito {
    animation: salida 1.5s forwards;
  }
  #mensajeError {
    animation: salida 2s forwards;
  }
  .tabs {
    --fondo: white;
    --gradiente: rgb(235,235,235);
    --gradiente-fondo-inicio: rgba(180,180,180,1);
    --gradiente-fondo-fin: rgba(180,180,180,0);
    --borde-tab: rgb(221, 221, 221);
    --borde-tab-seleccionado: orange;
    --texto-tab-seleccionado: #555;
    width: 100%;
    display: flex;
    margin-bottom: 10px;
    background: linear-gradient(0deg, var(--gradiente-fondo-inicio) 0%, var(--gradiente-fondo-fin) 100%);
  }
  .tabs > div {
    flex: 1;
    margin: 0;
    padding: 0;
  }
  .tabs a {
    padding: 15px 10px;
    font-family:Roboto-condensed;
    font-size:20px;
    background: white;
    display: inline-block;
    width: 100%;
    height: 100%;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid var(--borde-tab);
    border-top-left-radius: 2em;
    border-top-right-radius: 2em;
  }
  .tabs a.active {
    color: var(--texto-tab-seleccionado);
    cursor: default;
    border-color: var(--borde-tab-seleccionado);
    border-bottom: none;
  }
  .tabs a:not(.active):not(:hover) {
    background-image:  linear-gradient(135deg, var(--gradiente) 25%, transparent 25%), linear-gradient(225deg, var(--gradiente) 25%, transparent 25%), linear-gradient(45deg, var(--gradiente) 25%, transparent 25%), linear-gradient(315deg, var(--gradiente) 25%, #ffffff 25%);
    background-position:  3px 0, 3px 0, 0 0, 0 0;
    background-size: 3px 3px;
    background-repeat: repeat;
    background-color: var(--fondo);
  }
  
  #pant_canon [data-js-filtro-tabla] table:not([data-js-filtro-tabla-molde]) tr[data-css-tiene_diarios="0"] {
    color: darkolivegreen;
    font-style: italic;
  }
</style>
@endsection
@section('contenidoVista')

<div class="row">
  <div class="tabs" data-js-tabs="">
    <div>
      <a data-js-tab="#pant_canon">Canon</a>
    </div>
    @if($es_superusuario)
    <div>
      <a data-js-tab="#pant_defecto">Valores por Defecto</a>
    </div>
    @endif
  </div>
</div>

<style>
  #pant_canon [data-js-filtro-tabla] th, 
  #pant_canon [data-js-filtro-tabla] td {
    width: 11.11%;/* @HACK: poner algun atributo a la tabla para que haya columnas fijas? */
  }
  
  #pant_canon [data-js-filtro-tabla] td button {
    padding: 0.3rem;/* @HACK: achivo los botones asi entran que son tantos -___- */
  }
  
  #pant_canon [data-content-popover][data-molde-popover] {
    display: none;
  }
  #pant_canon [data-content-popover]:not([data-molde-popover]) {
    display: flex;
  }
</style>
<div id="pant_canon" hidden>
  @component('Components.FiltroTabla')
    @slot('titulo')
    CANON
    <button class="btn" type="button" data-js-nuevo-canon="/canon/obtener">NUEVO</button>
    <button data-js-descargar="/canon/descargar" class="btn btn-sucess" type="button" style="font-size: 0.9rem;"><i class="fa fa-arrow-circle-down"></i>DESCARGAR<i class="fa fa-spinner fa-spin" data-js-descargando style="display: none;"></i></button> 
    <a href="/canon/descargarPlanillas" target="_blank" class="btn btn-sucess" role="button" style="font-size: 0.9rem;"><i class="fa fa-arrow-circle-down"></i>PLANILLAS</a>
    @endslot
    
    @slot('target_buscar')
    /canon/buscar
    @endslot
    
    @slot('filtros')
    <div class="col-md-4">
      <h5>Casino</h5>
      <select class="form-control" name="id_casino">
        <option value='' selected>- TODOS -</option>
        @foreach($casinos as $c)
        <option value='{{$c->id_casino}}'>{{$c->nombre}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <h5>PERÍODO</h5>
      <div style="display: flex;">
        @component('Components.inputFecha',[
          'attrs' => 'name="año_mes[0]"',
          'attrs_dtp' => 'data-date-format="yyyy-mm" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
          'placeholder' => 'DESDE'
        ])
        @endcomponent
        @component('Components.inputFecha',[
          'attrs' => 'name="año_mes[1]"',
          'attrs_dtp' => 'data-date-format="yyyy-mm" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
          'placeholder' => 'HASTA'
        ])
        @endcomponent
      </div>
    </div>
    @if($es_superusuario)
    <div class="col-md-4">
      <h5>ELIMINADOS</h5>
      <select class="form-control" name="eliminados">
        <option value='0' selected>NO</option>
        <option value='1'>SI</option>
      </select>
    </div>
    @endif
    @endslot
    
    @slot('cabecera')
    <tr>
      <th data-js-sortable="año_mes">AÑO MES</th>
      <th>CASINO</th>
      <th>ESTADO</th>
      <th>DEVENGADO</th>
      <th>DETERMINADO</th>
      <th>INTERESES Y CARGOS</th>
      <th>PAGO</th>
      <th>SALDO</th>
      <th>ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr data-table-id="id_canon">
      <td class="año_mes">AÑO MES</td>
      <td class="casino">CASINO</td>
      <td>
        <div data-content-popover data-molde-popover style="flex-direction: column;"><!-- Esto esta aca porque tiene que estar en el <tr></tr> nomas... no tiene otro sentido -->
          <a href="/canon/planillaPDF" target="_blank" title="REPORTE">Valores</a>
          <a href="/canon/planillaDevengado" target="_blank" title="IMPRIMIR DEVENGADO">Devengado</a>
          <a href="/canon/planillaDeterminado"  target="_blank" title="IMPRIMIR DETERMINADO">Determinado</a>
          @if($es_superusuario)
          <a href="/canon/planilla" target="_blank" title="DESCARGAR XLSX">.xlsx</a>
          @endif
        </div>
        <span style="color: blue;font-weight: bold;font-size: 0.8em;padding-right: 0.1em;"><sup class="antiguo">XXX</sup></span>
        <span class="estado">ESTADO</span>
        @if($puede_cargar)
        <button class="btn" type="button" data-js-cambiar-estado="/canon/cambiarEstado?estado=Pagado" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "Generado" a "Pagado"?' data-estado-visible="GENERADO" title="CONFIRMAR PAGO">
          <i class="fas fa-hand-holding-usd"></i>
        </button>
        <button class="btn" type="button" data-js-cambiar-estado="/canon/cambiarEstado?estado=Cerrado" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "Pagado" a "Cerrado"?' data-estado-visible="PAGADO" title="CERRAR CANON">
          <i class="fa fa-fw fa-lock"></i>
        </button>
        <button class="btn" type="button" data-js-cambiar-estado="/canon/cambiarEstado?estado=Generado" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "Pagado" a "Generado"?' data-estado-visible="PAGADO" title="DESCONFIRMAR PAGO">
          <i class="fa fa-backward"></i>
        </button>
        <button class="btn" type="button" data-js-cambiar-estado="/canon/cambiarEstado?estado=Pagado" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "Cerrado" a "Pagado"?' data-estado-visible="CERRADO" title="ABRIR CANON">
          <i class="fa fa-backward"></i>
        </button>
        @endif
      </td>
      <td class="devengado" data-formatear-numero>DEVENGADO</td>
      <td class="determinado" data-formatear-numero>DETERMINADO</td>
      <td class="intereses_y_cargos" data-formatear-numero>INTERESES Y CARGOS</td>
      <td class="pago" data-formatear-numero>PAGO</td>
      <td class="saldo_posterior" data-formatear-numero>SALDO</td>
      <td>
        <button class="btn" type="button" data-js-ver="/canon/obtenerConHistorial" title="VER/HISTORIAL"><i class="fa fa-fw fa-search-plus"></i></button>
        @if($puede_cargar)
        <button class="btn" type="button" data-js-adjuntar="/canon/obtener" data-estado-visible="PAGADO" title="ADJUNTAR"><i class="fa fa-fw fa-paperclip"></i></button>
        <button class="btn" type="button" data-js-editar="/canon/obtener" data-estado-visible="GENERADO"  title="EDITAR"><i class="fas fa-fw fa-pencil-alt"></i></button>
        @endif
        <a tabindex="0" class="btn btn-info info" data-toggle="popover" data-content="COMPLETAR!" data-html="true" data-trigger="focus" data-placement="top">
          <i class="fa fa-print"></i>
        </a>
        @if($es_superusuario)
        <button data-mostrar-borrado class="btn" type="button" data-js-ver="/canon/obtenerConHistorial" title="VER/HISTORIAL"><i class="fa fa-fw fa-search-plus"></i></button>
        <button class="btn" type="button" data-js-borrar="/canon/borrar" title="BORRAR"><i class="fa fa-fw fa-trash-alt"></i></button>
        <button data-mostrar-borrado class="btn" type="button" data-js-cambiar-estado="/canon/desborrar" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "BORRADO" a "ACTIVO"?' title="DESBORRAR">
          <i class="fa fa-backward"></i>
        </button>
        @else($puede_cargar)
        <button class="btn" type="button" data-js-borrar="/canon/borrar" title="BORRAR" data-estado-visible="GENERADO,PAGADO"><i class="fa fa-fw fa-trash-alt"></i></button>
        @endif
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

@if($es_superusuario)
<div id="pant_defecto" hidden>
  @component('Components.FiltroTabla')
    @slot('titulo')
    <div>VALORES POR DEFECTO</div>
    <form style="display: flex;">
      <input class="form-control" name="campo" placeholder="Campo" style="flex: 1;">
      <div data-js-nuevo-jsoneditor style="flex: 2;"></div>
      <div style="flex: 1;">
        <button class="btn" type="button" data-js-guardar-nuevo="/canon/valoresPorDefecto/ingresar">GUARDAR</button>
      </div>
    </form>
    @endslot
    
    @slot('target_buscar')
    /canon/valoresPorDefecto
    @endslot
    
    @slot('filtros')
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>CAMPO</th>
      <th>VALOR</th>
      <th>ACCIÓN</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr data-table-id="id_canon_valor_por_defecto">
      <td class="campo">-CAMPO-</td>
      <td class="valor" data-js-jsoneditor>-VALOR-</td>
      <td>
        <button class="btn" type="button" data-js-guardar="/canon/valoresPorDefecto/ingresar" title="GUARDAR"><i class="fa fa-fw fa-check"></i></button>
        <button class="btn" type="button" data-js-borrar="/canon/valoresPorDefecto/borrar" title="BORRAR"><i class="fa fa-fw fa-trash-alt"></i></button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>
@endif

<style>
  .VerCargarCanon {
    --color-fondo-pestaña: #ececec;
  }
  .VerCargarCanon .tabs {
    margin-bottom: 0;
  }
  .VerCargarCanon .pestaña {
    background: var(--color-fondo-pestaña);
  }
  .VerCargarCanon h5, .VerCargarCanon select, .VerCargarCanon input {
    text-align: center;
  }
  
  .VerCargarCanon .datos_numericos {
    box-shadow: 0px 0px 1px black;
  }
  .VerCargarCanon .datos_numericos > .pestaña {
    padding: 1em;
  }
  .VerCargarCanon .datos_numericos input{
    text-align: right;
  }
  .VerCargarCanon .datos_numericos input[readonly] {
    font-family: monospace, monospace;
  }
  .VerCargarCanon div.date input {
    text-align: center;
  }
  
  .VerCargarCanon div.parametro_chico {
    display: flex;
    flex-direction: column;
    flex-wrap: nowrap;
    justify-content: flex-start;
    align-items: center;
  }
  .VerCargarCanon div.parametro_chico h5 {
    font-size: 0.95rem;
    width: 12rem;
  }
  .VerCargarCanon div.parametro_chico input {
    font-size: 0.95rem;
    border-color: black;
    height: 1.5rem;
    width: 6rem;
    text-align: center;
    font-family: monospace, monospace;
    padding: 0;
  }
  
  .VerCargarCanon .mostrar_dependencia {
    box-shadow: 0px 0px 5px green !important;
  }
  
  .VerCargarCanon select[readonly] {
    pointer-events: none;
  }
  
  .VerCargarCanon .solo_mostrar_h5_del_primero > div:nth-child(1) h5 {
    display: block;
  }
  .VerCargarCanon .solo_mostrar_h5_del_primero > div:not(:nth-child(1)) h5 {
    display: none;
  }
  
  .VerCargarCanon div.aproximado {
    font-style: italic;
  }
  .VerCargarCanon div.aproximado h5 {
    font-weight: lighter;
    font-size: 0.9rem;
  }
  .VerCargarCanon div.aproximado input {
    height: 1.5rem;
    color: gray;
    font-size: 0.9rem;
    border-color: black;
    border-style: dashed;
    text-align: center;
    font-family: monospace, monospace;
    padding: 0;
  }
  
  .VerCargarCanon div.bloque_interno {
    background: white;
    --color-borde: var(--color-fondo-pestaña);
    border: 0.2rem solid var(--color-borde);
    padding: 0.75rem;
  }
  .VerCargarCanon div.bloque_interno.bloque_principal {
    --color-borde: #797979;
  }
  .VerCargarCanon div.bloque_interno.bloque_principal:hover {
    --color-borde: orange;
  }
  
  .VerCargarCanon div[data-css-devengar="0"]{
    --fondo: white;
    --gradiente: rgb(200,200,200);
    background-color: var(--fondo);
    opacity: 0.8;
    background: repeating-linear-gradient(-45deg, var(--gradiente), var(--gradiente) 2px, var(--fondo) 2px, var(--fondo) 10px);
  }
  .VerCargarCanon div.valor_intermedio {
    opacity: 0.60;
  }
  .VerCargarCanon div.valor_intermedio:hover {
    opacity: 1.0;
  }
  
  .VerCargarCanon .grid_fila_pago {
    display: grid; 
    grid-template-columns: 0.7fr 1fr 0.7fr 0.7fr 0.7fr 0.7fr 0.7fr 0.7fr 0.1fr;
    grid-template-rows: 1fr;
    gap: 0px 0px; 
    grid-template-areas: 
      "grid_capital grid_fecha_pago grid_dias_vencidos grid_mora_provincial grid_mora_nacional grid_a_pagar grid_pago grid_diferencia grid_borrar";
  }
  .VerCargarCanon .grid_fila_pago > .grid_capital { grid-area: grid_capital; }
  .VerCargarCanon .grid_fila_pago > .grid_fecha_pago { grid-area: grid_fecha_pago; }
  .VerCargarCanon .grid_fila_pago > .grid_dias_vencidos { grid-area: grid_dias_vencidos; }
  .VerCargarCanon .grid_fila_pago > .grid_mora_provincial { grid-area: grid_mora_provincial; }
  .VerCargarCanon .grid_fila_pago > .grid_mora_nacional { grid-area: grid_mora_nacional; }
  .VerCargarCanon .grid_fila_pago > .grid_a_pagar { grid-area: grid_a_pagar; }
  .VerCargarCanon .grid_fila_pago > .grid_pago { grid-area: grid_pago; }
  .VerCargarCanon .grid_fila_pago > .grid_diferencia { grid-area: grid_diferencia; }
  .VerCargarCanon .grid_fila_pago > .grid_borrar { grid-area: grid_borrar; }
  .VerCargarCanon .grid_fila_pago > div h5 {
    padding: 0px;
  }
  .VerCargarCanon .grid_fila_pago > div input {
    padding: 1px;
  }
  .VerCargarCanon .grid_fila_pago [data-js-fecha] span {
    padding: 6px;
    font-size: 0.7em;
  }
  
  .VerCargarCanon [data-js-molde] {
    display: none;
  }
  
  .VerCargarCanon table.sacar-borde-primer-tr,
  .VerCargarCanon table.sacar-borde-primer-tr tbody tr:first-child td {
    border-top: 0;
  }
  .VerCargarCanon tr.fila-mensual td {
    background: #f2f2f2;
    border-top: 4px double #aaa !important;
  }
  .VerCargarCanon td.celda_vacia,
  .VerCargarCanon th.celda_vacia {
    background: #f2f2f2;
  }
  
  .VerCargarCanon [data-canon-variable] [data-subcanon-tipo]:first [data-tabla-diario]:first .es-cotizacion {
    background: green !important;
  }
  .VerCargarCanon [data-canon-variable] [data-subcanon-tipo]:not(:first)  [data-tabla-diario] .es-cotizacion {
    background: red !important;
  }
  .VerCargarCanon div.separar-devengado-determinado {
    border-right: 1px solid black;
  }
  .VerCargarCanon div.separar-devengado-determinado + div {
    border-left: 1px solid black;
  }
  
  .VerCargarCanon[data-con-diario="1"] .data-css-visible-sin-diario {
    display: none;
  }
  
  .loading_screen {
    z-index: 1060;
    background: rgba(0,0,0,0.2);
    width: 100vw;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
  } 
</style>

<div class="loading_screen" hidden>
  <div style="width: 100%;height: 100%;display: flex;flex-direction: row;flex-wrap: nowrap;justify-content: center;align-items: center;align-content: center;">
    <div style="width: 100%;height: 100%;display: flex;flex-direction: column;flex-wrap: nowrap;justify-content: center;align-items: center;align-content: center;">
      <i class="fa fa-spinner fa-spin" style="font-size: 10em;"></i>
    </div>
  </div>
</div>

@component('Components.modal',[
  'clases_modal' => 'VerCargarCanon',
  'attrs_modal' => 'data-js-modal-ver-cargar-canon',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 98,
])
  @slot('titulo')
  CANON
  @endslot
  @slot('cuerpo')
  <div style="width: 100%;display: flex;align-items: center;justify-content: flex-end;" data-modo-mostrar='[{"modo": "VER"}]'>
    <h5 style="width: 15rem;">Version</h5>
    <select class="form-control" data-js-select-historial style="width: 15rem;">
    </select>
  </div>
  <form style="display: flex;flex-direction: column;" data-css-id_casino="" data-js-recalcular="/canon/recalcular">
    <div style="width: 100%;display: flex;">
      <div>
        <h5>AÑO MES</h5>
        @component('Components.inputFecha',[
          'attrs' => 'data-js-formatear-año-mes name="año_mes" placeholder="AÑO MES" data-js-empty-si-cambio="[data-cotizaciones] [data-js-contenedor],[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"',
          'attrs_dtp' => 'data-date-format="yyyy-mm" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"',
          'placeholder' => 'aaaa-mm'
        ])
        @endcomponent
      </div>
      <div>
        <h5>Casino</h5>
        <select class="form-control" name="id_casino"
          data-js-empty-si-cambio="[data-cotizaciones] [data-js-contenedor],[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"
          data-readonly='[{"modo": "VER"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]'>
          <option value="" selected>- SELECCIONE -</option>
          @foreach($casinos as $c)
          <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div>
        <h5>Estado</h5>
        <input data-js-texto-no-formatear-numero class="form-control" name="estado" data-readonly='[{"modo": "*"}]'>
      </div>
      <div>
        <h5>ANTIGUO</h5>
        <select class="form-control" name="es_antiguo"
          data-js-empty-si-cambio="[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"
          data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          <option value="0" selected>NO</option>
          <option value="1">SI</option>
        </select>
      </div>
      <div>
        <h5>&nbsp;</h5>
        <button type="button" class="btn data-css-visible-sin-diario" data-js-generar-diario>
          <b>GENERAR DIARIO</b>
        </button>
      </div>
      <div hidden>
        <input name="id_canon" class="form-control" data-readonly='[{"modo":"*"}]'>
      </div>
    </div>
    <div class="tabs" data-js-tabs>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-canon] [data-total]" tabindex="0">Total</a>
      </div>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-canon] [data-cotizaciones]" tabindex="0">Cotizaciones</a>
      </div>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-canon] [data-canon-variable]" tabindex="0">Canon Variable</a>
      </div>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-canon] [data-canon-fijo-mesas]" tabindex="0">Canon Fijo - Mesas</a>
      </div>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-canon] [data-canon-fijo-mesas-adicionales]" tabindex="0">Canon Fijo - Mesas Adicionales</a>
      </div>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-canon] [data-adjuntos]" tabindex="0">Adjuntos</a>
      </div>
    </div>
    <div class="datos_numericos" style="height: 70vh;overflow-y: scroll;">
      <div class="pestaña" data-total>
        <div class="bloque_interno">
          <h4>DEVENGADO</h4>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>Bruto</h5>
              <input class="form-control" name="devengado_bruto" data-readonly='[{"modo": "*"}]'>
            </div>
            <div>
              <h5>Deducción</h5>
              <input class="form-control" name="devengado_deduccion" data-readonly='[{"modo": "*"}]'>
            </div>
            <div>
              <h5>Devengado</h5>
              <input class="form-control" name="devengado" data-depende="devengado_bruto,devengado_deduccion" data-readonly='[{"modo":"*"}]'>
            </div>
          </div>
        </div>
        <div class="bloque_interno">
          <h4>DETERMINADO</h4>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>Bruto</h5>
              <input class="form-control" name="determinado_bruto" data-readonly='[{"modo":"*"}]'>
            </div>
            <div>
              <h5>Ajuste</h5>
              <input class="form-control" name="determinado_ajuste" data-readonly='[{"modo": "*"}]'>
            </div>
            <div>
              <h5>Determinado</h5>
              <input class="form-control" name="determinado" data-depende="determinado_bruto,determinado_ajuste" data-readonly='[{"modo":"*"}]'>
            </div>
            <div class="parametro_chico">
              <h5>Porcentaje Seguridad</h5>
              <input class="form-control" name="porcentaje_seguridad" data-depende="devengado,determinado" data-readonly='[{"modo":"*"}]'>
            </div>
          </div>
        </div>
        <div class="bloque_interno">
          <h4>PRINCIPAL</h4>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>Saldo anterior</h5>
              <input class="form-control" name="saldo_anterior" data-depende="año_mes,id_casino" data-readonly='[{"modo":"*"}]'>
            </div>
            <div data-modo-mostrar='[{"estado": "CERRADO"},{"estado": "PAGADO"}]'>
              <h5>Saldo anterior (CERRADO)</h5>
              <input class="form-control" name="saldo_anterior_cerrado" data-depende="año_mes,id_casino" data-readonly='[{"modo":"*"}]'>
            </div>
            <div>
              <h5>Intereses y Cargos</h5>
              <input class="form-control" name="intereses_y_cargos" data-depende="año_mes,id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              <input data-js-texto-no-formatear-numero placeholder="MOTIVO" class="form-control" name="motivo_intereses_y_cargos" data-depende="" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div>
              <h5>Principal</h5>
              <input class="form-control" name="principal" data-readonly='[{"modo":"*"}]' data-depende="saldo_anterior,saldo_anterior_cerrado,intereses_y_cargos,determinado">
            </div>
          </div>
        </div>
        <div class="bloque_interno" data-pagos>
          <h4>PAGOS</h4>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>F. Vencimiento</h5>
              @component('Components.inputFecha',[
                'attrs' => "data-js-texto-no-formatear-numero name='fecha_vencimiento' data-depende='año_mes'",
                'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"'
              ])
              @endcomponent
            </div>
            <div class="parametro_chico">
              <h5>Interés Provincial Diario Simple</h5>
              <input class="form-control" name="interes_provincial_diario_simple" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div class="parametro_chico">
              <h5>Interés Nacional Mensual Compuesto</h5>
              <input class="form-control" name="interes_nacional_mensual_compuesto" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
          </div>
          <div class="bloque_interno">
            <div class="grid_fila_pago" style="width: 100%;">
              <div class="grid_capital">
                <h5>Capital</h5>
              </div>
              <div class="grid_fecha_pago">
                <h5>F. Pago</h5>
              </div>
              <div class="grid_dias_vencidos">
                <h5>Dias vencidos</h5>
              </div>
              <div class="grid_mora_provincial">
                <h5>Mora Provincial</h5>
              </div>
              <div class="grid_mora_nacional">
                <h5>Mora Nacional</h5>
              </div>
              <div class="grid_a_pagar">
                <h5>A PAGAR</h5>
              </div>
              <div class="grid_pago">
                <h5>PAGO</h5>
              </div>
              <div class="grid_diferencia">
                <h5>Diferencia</h5>
              </div>
              <div class="grid_borrar">
                <h5>&nbsp;</h5>
              </div>
            </div>
            <div data-js-contenedor style="width: 100%;">
            </div>
            <button class="btn" type="button" data-js-agregar-pago data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]' style="display: inline-block;">
              <i class="fa fa-plus"></i>
            </button>
            <?php
            $molde_str = '$pidx';
            $n = function($s) use (&$molde_str){
              return "canon_pago[$molde_str][$s]";
            };
            $id_canon_pago = $n('id_canon_pago');
            $capital = $n('capital');
            $fecha_pago = $n('fecha_pago');
            $dias_vencidos = $n('dias_vencidos');
            $mora_provincial = $n('mora_provincial');
            $mora_nacional = $n('mora_nacional');
            $a_pagar = $n('a_pagar');
            $pago = $n('pago');
            $diferencia = $n('diferencia');
            ?>
            <div data-pago data-js-molde="{{$molde_str}}" class="grid_fila_pago" style="width: 100%;">
              <input data-name="{{$id_canon_pago}}" data-modo-mostrar='[]'>
              <div class="grid_capital valor_intermedio">
                <input class="form-control" data-name="{{$capital}}" data-readonly='[{"modo":"*"}]'>
              </div>
              <div class="grid_fecha_pago">
                @component('Components.inputFecha',[
                  'attrs' => "data-js-texto-no-formatear-numero data-name='$fecha_pago' data-depende='año_mes'",
                  'attrs_dtp' => 'data-picker-position="top-right"',
                  'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"'
                ])
                @endcomponent
              </div>
              <div class="grid_dias_vencidos valor_intermedio">
                <input class="form-control" data-name="{{$dias_vencidos}}" data-depende="fecha_vencimiento,{{$fecha_pago}}" data-readonly='[{"modo":"*"}]'>
              </div>
              <div class="grid_mora_provincial valor_intermedio">
                <input class="form-control" data-name="{{$mora_provincial}}" data-depende="{{$dias_vencidos}},tasa_provincial_diaria_simple" data-readonly='[{"modo":"*"}]'>
              </div>
              <div class="grid_mora_nacional valor_intermedio">
                <input class="form-control" data-name="{{$mora_nacional}}" data-depende="{{$dias_vencidos}},tasa_nacional_mensual_compuesta" data-readonly='[{"modo":"*"}]'>
              </div>
              <div class="grid_a_pagar">
                <input class="form-control" data-name="{{$a_pagar}}" data-readonly='[{"modo":"*"}]' data-depende="{{$mora_provincial}},{{$mora_nacional}},{{$capital}}">
              </div>
              <div class="grid_pago">
                <input class="form-control" data-name="{{$pago}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' data-depende="año_mes,id_casino">
              </div>
              <div class="grid_diferencia">
                <input class="form-control" data-name="{{$diferencia}}" data-depende="{{$a_pagar}},{{$pago}}" data-readonly='[{"modo":"*"}]'>
              </div>
              <div class="grid_borrar">
                <button class="btn" type="button" data-js-borrar-pago data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'><i class="fa fa-fw fa-trash-alt"></i></button>
              </div>
            </div>
          </div>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>A Pagar</h5>
              <input class="form-control" name="a_pagar" data-readonly='[{"modo":"*"}]'>
            </div>
            <div>
              <h5>Pago</h5>
              <input class="form-control" name="pago" data-readonly='[{"modo":"*"}]'>
            </div>
            <div>
              <h5>Ajuste</h5>
              <input class="form-control" name="ajuste" data-depende="" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              <input data-js-texto-no-formatear-numero placeholder="MOTIVO" class="form-control" name="motivo_ajuste" data-depende="" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div>
              <h5>Diferencia</h5>
              <input class="form-control" name="diferencia" data-readonly='[{"modo":"*"}]'>
            </div>
            <div data-modo-mostrar='[{"estado": "CERRADO"},{"estado": "PAGADO"}]'>
              <h5>Saldo posterior (CERRADO)</h5>
              <input class="form-control" name="saldo_posterior_cerrado" data-depende="diferencia" data-readonly='[{"modo":"*"}]'>
            </div>
            <div>
              <h5>Saldo posterior</h5>
              <input class="form-control" name="saldo_posterior" data-depende="diferencia" data-readonly='[{"modo":"*"}]'>
            </div>
          </div>
        </div>
      </div>
      <div class="pestaña" data-cotizaciones>
        <div class="bloque_interno">
          @component('Canon.ModalCanon.moldeCanonCotizacion')
          @endcomponent
        </div>
      </div>
      <div class="pestaña" data-canon-variable>
        <div data-js-contenedor>
        </div>
        @component('Canon.ModalCanon.moldeCanonVariable')
        @endcomponent
      </div>
      <div class="pestaña" data-canon-fijo-mesas>
        <div style="width: 100%;" data-js-contenedor>
        </div>
        @component('Canon.ModalCanon.moldeCanonFijoMesas')
        @endcomponent
      </div>
      <div class="pestaña" data-canon-fijo-mesas-adicionales>
        <div style="width: 100%;" data-js-contenedor>
        </div>
        @component('Canon.ModalCanon.moldeCanonFijoMesasAdicionales')
        @endcomponent
      </div>
      <div class="pestaña" data-adjuntos>
        <div class="bloque_interno">
          @component('Canon.ModalCanon.moldeCanonArchivo')
          @endcomponent
        </div>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
  @if($puede_cargar)
  <button class="btn btn-successAceptar" type="button" data-js-enviar="/canon/adjuntar" data-modo-mostrar='[{"modo": "ADJUNTAR"}]' data-modo-mostrar="ADJUNTAR">ADJUNTAR</button>
  <button class="btn btn-successAceptar" type="button" data-js-enviar="/canon/guardar" data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>GUARDAR</button>
  @endif
  @endslot
@endcomponent

@component('Components/modalEliminar')
@endcomponent

@component('Components/modal',[
  'clases_modal' => 'modalCambiarEstado',
  'attrs_modal' => 'data-js-modal-cambiar-estado',
  'estilo_cabecera' => 'font-family: Roboto-Black; background-color: #F4B400'
])
  @slot('titulo')
    ALERTA
  @endslot
  @slot('cuerpo')
    <h6 class="mensaje" style="color:#000000; font-size: 18px !important; text-align:center !important">
    </h6>
  @endslot
  @slot('pie')
    <button type="button" class="btn" style="background-color: #F4B400 !important;color: white;" data-js-click-cambiar-estado>CAMBIAR</button>
  @endslot
@endcomponent

<meta name="_token" content="{!! csrf_token() !!}" />

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">Canon</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <p>
  En esta sección puede cargar en la base de datos las recaudaciones mensuales de cada casino, con la fecha de pago y cotización.
  Con estos datos el sistema puede calcular el Valor Base y Canon del próximo periodo.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')

  <!-- JavaScript personalizado -->
  <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <script src="js/inputSpinner.js" type="text/javascript"></script>
  <script src="/js/lista-datos.js" type="text/javascript"></script>

  <script src="js/fileinput.min.js" type="text/javascript"></script>

  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <script src="/js/paginacion.js" charset="utf-8"></script>
  <script src="/js/lib/jsoneditor.js"></script>
  <script src="/js/Canon/index.js?5" charset="utf-8" type="module"></script>

@endsection
