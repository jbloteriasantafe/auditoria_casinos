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
</style>
<div id="pant_canon" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    CANON
    <button class="btn" type="button" data-js-nuevo-canon="/canon/obtener">NUEVO</button>
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
      <th>AÑO MES</th>
      <th>CASINO</th>
      <th>ESTADO</th>
      <th>DEVENGADO</th>
      <th>DETERMINADO</th>
      <th>PAGO</th>
      <th>DIFERENCIA</th>
      <th>SALDO</th>
      <th>ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr>
      <td class="año_mes">AÑO MES</td>
      <td class="casino">CASINO</td>
      <td>
        <span class="estado">ESTADO</span>
        @if($puede_cargar)
        <button class="btn" type="button" data-js-cambiar-estado="/canon/cambiarEstado?estado=Pagado" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "Generado" a "Pagado"?' data-estado-visible="GENERADO" title="CONFIRMAR PAGO">
          <i class="fas fa-hand-holding-usd"></i>
        </button>
        <button class="btn" type="button" data-js-cambiar-estado="/canon/cambiarEstado?estado=Cerrado" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "Pagado" a "Cerrado"?' data-estado-visible="PAGADO" title="CERRAR CANON">
          <i class="fa fa-fw fa-lock"></i>
        </button>
        @endif
        @if($es_superusuario)
        <button class="btn" type="button" data-js-cambiar-estado="/canon/cambiarEstadoSuperusuario?estado=Generado" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "Pagado" a "Generado"?' data-estado-visible="PAGADO" title="DESCONFIRMAR PAGO">
          <i class="fa fa-backward"></i>
        </button>
        <button class="btn" type="button" data-js-cambiar-estado="/canon/cambiarEstadoSuperusuario?estado=Pagado" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "Cerrado" a "Pagado"?' data-estado-visible="CERRADO" title="ABRIR CANON">
          <i class="fa fa-backward"></i>
        </button>
        @endif
      </td>
      <td class="devengado" data-formatear-numero>DEVENGADO</td>
      <td class="determinado" data-formatear-numero>DETERMINADO</td>
      <td class="pago" data-formatear-numero>PAGO</td>
      <td class="diferencia" data-formatear-numero>DIFERENCIA</td>
      <td class="saldo_posterior" data-formatear-numero>SALDO</td>
      <td>
        <button class="btn" type="button" data-js-ver="/canon/obtenerConHistorial" title="VER/HISTORIAL"><i class="fa fa-fw fa-search-plus"></i></button>
        @if($puede_cargar)
        <button class="btn" type="button" data-js-adjuntar="/canon/obtener" data-estado-visible="PAGADO" title="ADJUNTAR"><i class="fa fa-fw fa-paperclip"></i></button>
        <button class="btn" type="button" data-js-editar="/canon/obtener" data-estado-visible="GENERADO"  title="EDITAR"><i class="fas fa-fw fa-pencil-alt"></i></button>
        @endif
        <button class="btn" type="button" data-js-abrir-pestaña="/canon/planilla" data-table-id="id_canon" title="DESCARGAR XLSX">.xlsx</button>
        <button class="btn" type="button" data-js-abrir-pestaña="/canon/planillaPDF" data-table-id="id_canon" title="REPORTE"><i class="fa fa-table"></i></button>
        <button class="btn" type="button" data-js-abrir-pestaña="/canon/planillaDevengado" data-table-id="id_canon" title="IMPRIMIR DEVENGADO"><i class="far fa-fw fa-file-alt"></i></button>
        <button class="btn" type="button" data-js-abrir-pestaña="/canon/planillaDeterminado" data-table-id="id_canon" title="IMPRIMIR DETERMINADO"><i class="fa fa fa-print"></i></button>
        @if($es_superusuario)
        <button data-mostrar-borrado class="btn" type="button" data-js-ver="/canon/obtenerConHistorial" title="VER/HISTORIAL"><i class="fa fa-fw fa-search-plus"></i></button>
        <button class="btn" type="button" data-js-borrar="/canon/borrar" data-table-id="id_canon" title="BORRAR"><i class="fa fa-fw fa-trash-alt"></i></button>
        <button data-mostrar-borrado class="btn" type="button" data-js-cambiar-estado="/canon/desborrar" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "BORRADO" a "ACTIVO"?' title="DESBORRAR">
          <i class="fa fa-backward"></i>
        </button>
        @else($puede_cargar)
        <button class="btn" type="button" data-js-borrar="/canon/borrar" data-table-id="id_canon" title="BORRAR" data-estado-visible="GENERADO,PAGADO"><i class="fa fa-fw fa-trash-alt"></i></button>
        @endif
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

@if($es_superusuario)
<div id="pant_defecto" hidden>
  @component('Components/FiltroTabla')
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
    <tr>
      <td class="campo">-CAMPO-</td>
      <td class="valor" data-js-jsoneditor>-VALOR-</td>
      <td>
        <button class="btn" type="button" data-js-guardar="/canon/valoresPorDefecto/ingresar" title="GUARDAR"><i class="fa fa-fw fa-check"></i></button>
        <button class="btn" type="button" data-js-borrar="/canon/valoresPorDefecto/borrar" data-table-id="id_canon_valor_por_defecto" title="BORRAR"><i class="fa fa-fw fa-trash-alt"></i></button>
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
    box-shadow: 0rem 0rem 0.05rem 0.2rem var(--color-fondo-pestaña);
    padding: 0.75rem;
  }
  /*.VerCargarCanon .pestaña:hover div.bloque_interno:not(:hover) * {
    opacity: 0.925;
  }
  .VerCargarCanon .pestaña:hover div.bloque_interno:not(:hover) div.valor_intermedio {
    opacity: 0.60;
  }*/
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
  
  .VerCargarCanon .grid_fila_adjunto {
    display: grid; 
    grid-template-columns: 20fr 20fr 1fr;
    grid-template-rows: 1fr; 
    gap: 0px 0px; 
    grid-template-areas: 
      "grid_descripcion grid_archivo grid_boton"; 
  }
  .VerCargarCanon .grid_fila_adjunto > .grid_descripcion { grid-area: grid_descripcion; }
  .VerCargarCanon .grid_fila_adjunto > .grid_archivo { grid-area: grid_archivo; }
  .VerCargarCanon .grid_fila_adjunto > .grid_boton { grid-area: grid_boton; }
  
  .VerCargarCanon [data-js-molde] {
    display: none;
  }
</style>

@component('Components/modal',[
  'clases_modal' => 'VerCargarCanon',
  'attrs_modal' => 'data-js-modal-ver-cargar-canon',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 98,
])
  @slot('titulo')
  CANON
  @endslot
  @slot('cuerpo')
  <div style="width: 100%;display: flex;align-items: center;justify-content: flex-end;" data-modo-mostrar="VER">
    <h5 style="width: 15rem;">Version</h5>
    <select class="form-control" data-js-select-historial style="width: 15rem;">
    </select>
  </div>
  <form style="display: flex;flex-direction: column;" data-css-id_casino="" data-js-recalcular="/canon/recalcular">
    <div style="width: 100%;display: flex;">
      <div>
        <h5>AÑO MES</h5>
        @component('Components/inputFecha',[
          'attrs' => 'data-js-texto-no-formatear-numero name="año_mes" placeholder="AÑO MES" data-js-empty-si-cambio="[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"',
          'attrs_dtp' => 'data-date-format="yyyy-mm-01" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      <div>
        <h5>Casino</h5>
        <select class="form-control" name="id_casino"
          data-js-empty-si-cambio="[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"
          data-readonly='[{"modo": "VER"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]'>
          <option value="" selected>- SELECCIONE -</option>
          @foreach($casinos as $c)
          <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div>
        <h5>Estado</h5>
        <input data-js-texto-no-formatear-numero class="form-control" name="estado" data-readonly='[{}]'>
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
      <div data-modo-mostrar="">
        <input name="id_canon" class="form-control" data-readonly='[{}]'>
      </div>
    </div>
    <div class="tabs" data-js-tabs>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-canon] [data-total]" tabindex="0">Total</a>
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
              <input class="form-control" name="devengado_bruto" data-readonly='[{}]'>
            </div>
            <div>
              <h5>Deducción</h5>
              <input class="form-control" name="devengado_deduccion" data-readonly='[{}]'>
            </div>
            <div>
              <h5>Devengado</h5>
              <input class="form-control" name="devengado" data-depende="devengado_bruto,devengado_deduccion" data-readonly="[{}]">
            </div>
          </div>
        </div>
        <div class="bloque_interno">
          <h4>DETERMINADO</h4>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>Bruto</h5>
              <input class="form-control" name="determinado_bruto" data-readonly='[{}]'>
            </div>
            <div>
              <h5>Determinado</h5>
              <input class="form-control" name="determinado" data-depende="determinado_bruto,interes_mora,mora,fecha_pago,fecha_vencimiento" data-readonly='[{}]'>
            </div>
            <div class="parametro_chico">
              <h5>Porcentaje Seguridad</h5>
              <input class="form-control" name="porcentaje_seguridad" data-depende="devengado,determinado" data-readonly="[{}]">
            </div>
          </div>
        </div>
        <div class="bloque_interno">
          <h4>PRINCIPAL</h4>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>Saldo anterior</h5>
              <input class="form-control" name="saldo_anterior" data-depende="año_mes,id_casino" data-readonly='[{}]'>
            </div>
            <div>
              <h5>Cargos adicionales</h5>
              <input class="form-control" name="cargos_adicionales" data-depend="año_mes,id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div>
              <h5>Principal</h5>
              <input class="form-control" name="principal" data-readonly='[{}]' data-depende="saldo_anterior,cargos_adicionales,determinado">
            </div>
          </div>
        </div>
        <div class="bloque_interno" data-pagos>
          <h4>PAGOS</h4>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>F. Vencimiento</h5>
              @component('Components/inputFecha',[
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
            <button class="btn" type="button" data-js-agregar-pago data-modo-mostrar="NUEVO,EDITAR" style="display: inline-block;">
              <i class="fa fa-plus"></i>
            </button>
            <?php
            $molde_str = '$pidx';
            $n = function($s) use (&$molde_str){
              return "canon_pago[$molde_str][$s]";
            };
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
              <div class="grid_capital valor_intermedio">
                <input class="form-control" data-name="{{$capital}}" data-readonly='[{}]'>
              </div>
              <div class="grid_fecha_pago">
                @component('Components/inputFecha',[
                  'attrs' => "data-js-texto-no-formatear-numero data-name='$fecha_pago' data-depende='año_mes'",
                  'attrs_dtp' => 'data-picker-position="top-right"',
                  'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"'
                ])
                @endcomponent
              </div>
              <div class="grid_dias_vencidos valor_intermedio">
                <input class="form-control" data-name="{{$dias_vencidos}}" data-depende="fecha_vencimiento,{{$fecha_pago}}" data-readonly='[{}]'>
              </div>
              <div class="grid_mora_provincial valor_intermedio">
                <input class="form-control" data-name="{{$mora_provincial}}" data-depende="{{$dias_vencidos}},tasa_provincial_diaria_simple" data-readonly='[{}]'>
              </div>
              <div class="grid_mora_nacional valor_intermedio">
                <input class="form-control" data-name="{{$mora_nacional}}" data-depende="{{$dias_vencidos}},tasa_nacional_mensual_compuesta" data-readonly='[{}]'>
              </div>
              <div class="grid_a_pagar">
                <input class="form-control" data-name="{{$a_pagar}}" data-readonly='[{}]' data-depende="{{$mora_provincial}},{{$mora_nacional}},{{$capital}}">
              </div>
              <div class="grid_pago">
                <input class="form-control" data-name="{{$pago}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' data-depende="año_mes,id_casino">
              </div>
              <div class="grid_diferencia">
                <input class="form-control" data-name="{{$diferencia}}" data-depende="{{$a_pagar}},{{$pago}}" data-readonly='[{}]'>
              </div>
              <div class="grid_borrar">
                <button class="btn" type="button" data-js-borrar-pago data-modo-mostrar="NUEVO,EDITAR"><i class="fa fa-fw fa-trash-alt"></i></button>
              </div>
            </div>
          </div>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>A Pagar</h5>
              <input class="form-control" name="a_pagar" data-readonly='[{}]'>
            </div>
            <div>
              <h5>Pago</h5>
              <input class="form-control" name="pago" data-readonly='[{}]'>
            </div>
            <div>
              <h5>Ajuste</h5>
              <input class="form-control" name="ajuste" data-depende="" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              <input data-js-texto-no-formatear-numero placeholder="MOTIVO" class="form-control" name="motivo_ajuste" data-depende="" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div>
              <h5>Diferencia</h5>
              <input class="form-control" name="diferencia" data-readonly='[{}]'>
            </div>
            <div>
              <h5>Saldo posterior</h5>
              <input class="form-control" name="saldo_posterior" data-depende="diferencia,saldo_anterior" data-readonly='[{}]'>
            </div>
          </div>
        </div>
      </div>
      <div class="pestaña" data-canon-variable>
        <div data-js-contenedor>
        </div>
        <?php
          $molde_str = '$cv';
          $n = function($s) use (&$id_casino,&$t,&$molde_str){
            return "canon_variable[$molde_str][$s]";
          };
          $alicuota = $n('alicuota');
          $devengado_bruto = $n('devengado_bruto');
          $devengado_apostado_sistema = $n('devengado_apostado_sistema');
          $devengado_apostado_porcentaje_aplicable = $n('devengado_apostado_porcentaje_aplicable');
          $devengado_apostado_porcentaje_impuesto_ley = $n('devengado_apostado_porcentaje_impuesto_ley');
          $devengado_base_imponible = $n('devengado_base_imponible');
          $devengado_impuesto = $n('devengado_impuesto');
          $devengado_subtotal = $n('devengado_subtotal');
          $devengado_total = $n('devengado_total');
          $devengado_deduccion = $n('devengado_deduccion');
          $determinado_bruto = $n('determinado_bruto');
          $determinado_base_imponible = $n('determinado_base_imponible');
          $determinado_impuesto = $n('determinado_impuesto');
          $determinado_subtotal = $n('determinado_subtotal');
          $determinado_total = $n('determinado_total');
        ?>
        <div class="bloque_interno" data-js-molde="{{$molde_str}}">
          <h6 data-titulo>TITULO CANON VARIABLE</h6>
          <div class="bloque_interno" style="width: 100%;display: flex;">
            <div class="parametro_chico"  style="flex: 2;">
              <h5>APLICABLE (%)</h5>
              <input class="form-control" data-name="{{$devengado_apostado_porcentaje_aplicable}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div class="parametro_chico" style="flex: 2;">
              <h5>IMPUESTO LEY (%)</h5>
              <input class="form-control" data-name="{{$devengado_apostado_porcentaje_impuesto_ley}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div class="parametro_chico" style="flex: 3;">
              <h5>ALICUOTA (%)</h5>
              <input class="form-control" data-name="{{$alicuota}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
          </div>
          <div style="width: 100%;display: flex;">
            <div class="bloque_interno" style="flex: 1;">
              <h4>DEVENGADO</h4>
              <div style="width: 100%;display: grid; 
                grid-template-columns: 1fr 1fr 1fr 1fr 1fr; 
                grid-template-rows: 1fr 1fr; 
                gap: 0px 0px; 
                grid-template-areas: 'grid_apostado grid_base_imponible grid_vacio grid_vacio grid_vacio' 'grid_impuesto grid_bruto grid_subtotal grid_total grid_deduccion';"
              >
                <div style="grid-area: grid_apostado">
                  <h5>APOSTADO SISTEMA</h5>
                  <input class="form-control" data-name="{{$devengado_apostado_sistema}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_base_imponible" class="valor_intermedio">
                  <h5>BASE IMPONIBLE</h5>
                  <input class="form-control" data-name="{{$devengado_base_imponible}}" data-depende="{{$devengado_apostado_sistema}},{{$devengado_apostado_porcentaje_aplicable}}" data-readonly='[{}]'>
                </div>
                <div style="grid-area: grid_vacio">
                </div>
                <div style="grid-area: grid_impuesto">
                  <h5>IMPUESTO</h5>
                  <input class="form-control" data-name="{{$devengado_impuesto}}" data-depende="{{$devengado_base_imponible}},{{$devengado_apostado_porcentaje_impuesto_ley}}" data-readonly='[{}]'>
                </div>
                <div style="grid-area: grid_bruto">
                  <h5>BRUTO</h5>
                  <input class="form-control" data-name="{{$devengado_bruto}}" data-depende="id_casino,es_antiguo" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_subtotal" class="valor_intermedio">
                  <h5>SUBTOTAL</h5>
                  <input class="form-control" data-name="{{$devengado_subtotal}}" data-depende="{{$devengado_bruto}},{{$devengado_impuesto}}" data-readonly='[{}]'>
                </div>
                <div style="grid-area: grid_total">
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$devengado_total}}" data-depende="{{$devengado_subtotal}},{{$alicuota}}" data-readonly='[{"es_antiguo": 0},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_deduccion">
                  <h5>DEDUCCIÓN</h5>
                  <input class="form-control" data-name="{{$devengado_deduccion}}" data-depende="id_casino"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
            </div>
            <div class="bloque_interno" style="flex: 1;">
              <h4>DETERMINADO</h4>
              <div style="width: 100%;display: grid; 
                grid-template-columns: 1fr 1fr 1fr 1fr 1fr; 
                grid-template-rows: 1fr 1fr; 
                gap: 0px 0px; 
                grid-template-areas: 'grid_vacio grid_vacio grid_vacio grid_vacio grid_vacio' 'grid_impuesto grid_bruto grid_subtotal grid_total grid_deduccion';"
              >  
                <div style="grid-area: grid_vacio">
                </div>
                <div style="grid-area: grid_impuesto">
                  <h5>IMPUESTO</h5>
                  <input class="form-control" data-name="{{$determinado_impuesto}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_bruto">
                  <h5>BRUTO</h5>
                  <input class="form-control" data-name="{{$determinado_bruto}}" data-depende="id_casino,es_antiguo" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_subtotal" class="valor_intermedio">
                  <h5>SUBTOTAL</h5>
                  <input class="form-control" data-name="{{$determinado_subtotal}}" data-depende="{{$determinado_bruto}},{{$determinado_impuesto}}" data-readonly='[{}]'>
                </div>
                <div style="grid-area: grid_total">
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$determinado_total}}" data-depende="{{$determinado_subtotal}},{{$alicuota}}" data-readonly='[{"es_antiguo": 0},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_deduccion">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="pestaña" data-canon-fijo-mesas>
        <div style="width: 100%;" data-js-contenedor>
        </div>
        <?php
          $molde_str = '$m';
          $n = function($s) use (&$id_casino,&$t,&$molde_str){
            return "canon_fijo_mesas[$molde_str][$s]";
          };
          $dias_valor = $n('dias_valor');
          $bruto = $n('bruto');
          $factor_dias_valor = $n('factor_dias_valor');
          $dias_lunes_jueves  = $n('dias_lunes_jueves');
          $mesas_lunes_jueves = $n('mesas_lunes_jueves');
          $dias_viernes_sabados  = $n('dias_viernes_sabados');
          $mesas_viernes_sabados = $n('mesas_viernes_sabados');
          $dias_domingos  = $n('dias_domingos');
          $mesas_domingos = $n('mesas_domingos');
          $dias_todos  = $n('dias_todos');
          $mesas_todos = $n('mesas_todos');
          $dias_fijos  = $n('dias_fijos');
          $mesas_fijos = $n('mesas_fijos');
          $mesas_dias  = $n('mesas_dias');
          $devengado_valor_dolar_cotizado = $n('devengado_valor_dolar_cotizado');
          $devengado_valor_euro_cotizado  = $n('devengado_valor_euro_cotizado');
          $devengado_valor_dolar_diario_cotizado = $n('devengado_valor_dolar_diario_cotizado');
          $devengado_valor_euro_diario_cotizado  = $n('devengado_valor_euro_diario_cotizado');
          $devengado_total_dolar_cotizado = $n('devengado_total_dolar_cotizado');
          $devengado_total_euro_cotizado  = $n('devengado_total_euro_cotizado');
          $devengado_total       = $n('devengado_total');
          $devengado_deduccion   = $n('devengado_deduccion');
          $determinado_valor_dolar_cotizado = $n('determinado_valor_dolar_cotizado');
          $determinado_valor_euro_cotizado  = $n('determinado_valor_euro_cotizado');
          $determinado_valor_dolar_diario_cotizado = $n('determinado_valor_dolar_diario_cotizado');
          $determinado_valor_euro_diario_cotizado  = $n('determinado_valor_euro_diario_cotizado');
          $determinado_total_dolar_cotizado = $n('determinado_total_dolar_cotizado');
          $determinado_total_euro_cotizado  = $n('determinado_total_euro_cotizado');
          $determinado_total       = $n('determinado_total');
        ?>
        <div class="bloque_interno" style="width: 100%;" data-js-molde="{{$molde_str}}">
          <h6 data-titulo>TITULO MESAS</h6>
          <div class="bloque_interno">
            <div style="display: flex;">
              <div>
                <h5>&nbsp;</h5>
                <div style="display: flex;flex-direction: column;width: 6em;">
                  <h5>DIAS</h5>
                  <h5>MESAS</h5>
                </div>
              </div>
              <div>
                <h5>Lunes-Jueves</h5>
                <div style="display: flex;flex-direction: column;">
                  <input class="form-control" data-name="{{$dias_lunes_jueves}}" placeholder="DIAS" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_lunes_jueves}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div>
                <h5>Viernes-Sabados</h5>
                <div style="display: flex;flex-direction: column;">
                  <input class="form-control" data-name="{{$dias_viernes_sabados}}" placeholder="DIAS" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_viernes_sabados}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>               
              <div>
                <h5>Domingos</h5>
                <div style="display: flex;flex-direction: column;">
                  <input class="form-control" data-name="{{$dias_domingos}}" placeholder="DIAS" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_domingos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div>
                <h5>Todos los dias</h5>
                <div style="display: flex;flex-direction: column;">
                  <input class="form-control" data-name="{{$dias_todos}}" placeholder="DIAS" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_todos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div>
                <h5>Fijos</h5>
                <div style="display: flex;flex-direction: column;">
                  <input class="form-control" data-name="{{$dias_fijos}}" placeholder="DIAS" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_fijos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div>
                <h5>&nbsp;</h5>
                <input class="form-control" style="opacity: 0;">
              </div>
              <div>
                <h5>BRUTO</h5>
                <input class="form-control" data-name="{{$bruto}}" data-depende="id_casino,año_mes" data-readonly='[{"es_antiguo": 0},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
            </div>
            <div style="display: flex;">
              <div class="valor_intermedio">
                <h5>MESAS×DIAS</h5>
                <input class="form-control" data-name="{{$mesas_dias}}" data-depende="{{$dias_lunes_jueves}},{{$mesas_lunes_jueves}},{{$dias_viernes_sabados}},{{$mesas_viernes_sabados}},{{$dias_domingos}},{{$mesas_domingos}},{{$dias_todos}},{{$mesas_todos}},{{$dias_fijos}},{{$mesas_fijos}}" data-readonly='[{}]'>
              </div>
              <div>
                <h5>VALOR DOLAR (USD)</h5>
                <input class="form-control" data-name="valor_dolar" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div>
                <h5>VALOR EURO (EUR)</h5>
                <input class="form-control" data-name="valor_euro" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div class="parametro_chico">
                <h5>DÍAS VALOR</h5>
                <input class="form-control" data-name="{{$dias_valor}}" data-depende="{{$id_casino}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div class="aproximado valor_intermedio">
                <h5>FACTOR DÍAS VALOR ≈ (DÍAS VALOR)⁻¹</h5>
                <input class="form-control" data-name="{{$factor_dias_valor}}" data-depende="{{$dias_valor}}" data-readonly='[{}]'>
              </div>
            </div>
          </div>
          <div style="display: flex;">
            <div class="bloque_interno" style="flex: 1;">
              <h4>DEVENGADO</h4>
              <div style="display: flex;">
                <div>
                  <h5>F. COTIZACIÓN</h5>
                  @component('Components/inputFecha',[
                    'attrs' => "data-js-texto-no-formatear-numero data-name='devengado_fecha_cotizacion' data-depende='año_mes'",
                    'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"'
                  ])
                  @endcomponent
                </div>
                <div>
                  <h5>COTIZACIÓN DOLAR</h5>
                  <input class="form-control" data-name="devengado_cotizacion_dolar" data-depende="devengado_fecha_cotizacion" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div>
                  <h5>COTIZACIÓN EURO</h5>
                  <input class="form-control" data-name="devengado_cotizacion_euro" data-depende="devengado_fecha_cotizacion" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>VALOR DOLAR (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_valor_dolar_cotizado}}" data-depende="devengado_cotizacion_dolar,valor_dolar" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR EURO (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_valor_euro_cotizado}}" data-depende="devengado_cotizacion_euro,valor_euro" data-readonly='[{}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>VALOR DOLAR DIARIO (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_valor_dolar_diario_cotizado}}" data-depende="{{$devengado_valor_dolar_cotizado}},{{$factor_dias_valor}}" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR EURO DIARIO (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_valor_euro_diario_cotizado}}" data-depende="{{$devengado_valor_euro_cotizado}},{{$factor_dias_valor}}" data-readonly='[{}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>TOTAL DOLAR (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_total_dolar_cotizado}}" data-depende="{{$devengado_valor_dolar_cotizado}},{{$devengado_valor_dolar_diario_cotizado}},{{$dias_valor}},{{$mesas_dias}}" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>TOTAL EURO (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_total_euro_cotizado}}" data-depende="{{$devengado_valor_euro_cotizado}},{{$devengado_valor_euro_diario_cotizado}},{{$dias_valor}},{{$mesas_dias}}" data-readonly='[{}]'>
                </div>
                <div>
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$devengado_total}}" data-depende="{{$devengado_total_dolar_cotizado}},{{$devengado_total_euro_cotizado}}" data-readonly='[{"es_antiguo": 0},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div>
                  <h5>DEDUCCIÓN</h5>
                  <input class="form-control" data-name="{{$devengado_deduccion}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
            </div>
            <div class="bloque_interno" style="flex: 1;">
              <h4>DETERMINADO</h4>
              <div style="display: flex;">
                <div>
                  <h5>F. COTIZACIÓN</h5>
                  @component('Components/inputFecha',[
                    'attrs' => "data-js-texto-no-formatear-numero data-name='determinado_fecha_cotizacion' data-depende='año_mes'",
                    'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"'
                  ])
                  @endcomponent
                </div>
                <div>
                  <h5>COTIZACIÓN DOLAR</h5>
                  <input class="form-control" data-name="determinado_cotizacion_dolar" data-depende="determinado_fecha_cotizacion" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div>
                  <h5>COTIZACIÓN EURO</h5>
                  <input class="form-control" data-name="determinado_cotizacion_euro" data-depende="determinado_fecha_cotizacion" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>VALOR DOLAR (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_valor_dolar_cotizado}}" data-depende="determinado_cotizacion_dolar,valor_dolar" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR EURO (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_valor_euro_cotizado}}" data-depende="determinado_cotizacion_euro,valor_euro" data-readonly='[{}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>VALOR DOLAR DIARIO (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_valor_dolar_diario_cotizado}}" data-depende="{{$determinado_valor_dolar_cotizado}},{{$dias_valor}}" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR EURO DIARIO (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_valor_euro_diario_cotizado}}" data-depende="{{$determinado_valor_euro_cotizado}},{{$dias_valor}}" data-readonly='[{}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>TOTAL DOLAR (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_total_dolar_cotizado}}" data-depende="{{$determinado_valor_dolar_cotizado}},{{$determinado_valor_dolar_diario_cotizado}},{{$dias_valor}},{{$mesas_dias}}" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>TOTAL EURO (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_total_euro_cotizado}}" data-depende="{{$determinado_valor_euro_cotizado}},{{$determinado_valor_euro_diario_cotizado}},{{$dias_valor}},{{$mesas_dias}}" data-readonly='[{}]'>
                </div>
                <div>
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$determinado_total}}" data-depende="{{$determinado_total_dolar_cotizado}},{{$determinado_total_euro_cotizado}}" data-readonly='[{"es_antiguo": 0},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="pestaña" data-canon-fijo-mesas-adicionales>
        <div style="width: 100%;" data-js-contenedor>
        </div>
        <?php
          $molde_str = '$ma';
          $n = function($s) use (&$id_casino,&$t,&$molde_str){
            return "canon_fijo_mesas_adicionales[$molde_str][$s]";
          };
          $dias_mes = $n('dias_mes');
          $horas_dia = $n('horas_dia');
          $factor_dias_mes = $n('factor_dias_mes');
          $factor_horas_mes = $n('factor_horas_mes');
          $horas = $n('horas');
          $porcentaje = $n('porcentaje');
          $devengado_valor_mes = $n('devengado_valor_mes');
          $devengado_valor_dia = $n('devengado_valor_dia');
          $devengado_valor_hora = $n('devengado_valor_hora');
          $devengado_total = $n('devengado_total');
          $devengado_deduccion = $n('devengado_deduccion');
          $determinado_valor_mes = $n('determinado_valor_mes');
          $determinado_valor_dia = $n('determinado_valor_dia');
          $determinado_valor_hora = $n('determinado_valor_hora');
          $determinado_total = $n('determinado_total');
        ?>
        <div class="bloque_interno" data-js-molde="{{$molde_str}}">
          <h6 data-titulo>TITULO MESA ADICIONAL</h6>
          <div class="bloque_interno">
            <div style="display: flex;">
              <div class="parametro_chico">
                <h5>DIAS MES</h5>
                <input class="form-control" data-name="{{$dias_mes}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div class="parametro_chico">
                <h5>HORAS DÍA</h5>
                <input class="form-control" data-name="{{$horas_dia}}"  data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div class="aproximado valor_intermedio">
                <h5>FACTOR DIAS MES ≈ (DÍAS MES)⁻¹</h5>
                <input class="form-control" data-name="{{$factor_dias_mes}}" data-depende="{{$dias_mes}}" data-readonly='[{}]'>
              </div>
              <div class="aproximado valor_intermedio">
                <h5>FACTOR HORAS MES ≈ (DÍAS MES × HORAS DÍA)⁻¹</h5>
                <input class="form-control" data-name="{{$factor_horas_mes}}" data-depende="{{$dias_mes}},{{$horas_dia}}" data-readonly='[{}]'>
              </div>
            </div>
            <div style="display: flex;">
              <div>
                <h5>HORAS</h5>
                <input class="form-control" data-name="{{$horas}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div class="parametro_chico">
                <h5>PORCENTAJE</h5>
                <input class="form-control" data-name="{{$porcentaje}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
            </div>
          </div>
          <div style="display: flex;">
            <div class="bloque_interno" style="flex: 1;">
              <h4>DEVENGADO</h4>
              <div style="display: flex;">
                <div>
                  <h5>VALOR MES</h5>
                  <input class="form-control" data-name="{{$devengado_valor_mes}}" data-depende="id_casino" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR DÍA</h5>
                  <input class="form-control" data-name="{{$devengado_valor_dia}}" data-depende="{{$devengado_valor_mes}},{{$factor_dias_mes}}" data-depende="id_casino" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR HORA</h5>
                  <input class="form-control" data-name="{{$devengado_valor_hora}}" data-depende="{{$devengado_valor_mes}},{{$factor_horas_mes}}" data-readonly='[{}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div>
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$devengado_total}}" data-depende="{{$devengado_valor_mes}},{{$devengado_valor_dia}},{{$devengado_valor_hora}},{{$horas}},{{$porcentaje}}" data-readonly='[{"es_antiguo": 0},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div>
                  <h5>DEDUCCIÓN</h5>
                  <input class="form-control" data-name="{{$devengado_deduccion}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
            </div>
            <div class="bloque_interno" style="flex: 1;">
              <h4>DETERMINADO</h4>
              <div style="display: flex;">
                <div>
                  <h5>VALOR MES</h5>
                  <input class="form-control" data-name="{{$determinado_valor_mes}}" data-depende="id_casino" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR DÍA</h5>
                  <input class="form-control" data-name="{{$determinado_valor_dia}}" data-depende="{{$determinado_valor_mes}},{{$factor_dias_mes}}" data-depende="id_casino" data-readonly='[{}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR HORA</h5>
                  <input class="form-control" data-name="{{$determinado_valor_hora}}" data-depende="{{$determinado_valor_mes}},{{$factor_horas_mes}}" data-readonly='[{}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div>
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$determinado_total}}" data-depende="{{$determinado_valor_mes}},{{$determinado_valor_dia}},{{$determinado_valor_hora}},{{$horas}},{{$porcentaje}}" data-readonly='[{"es_antiguo": 0},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="pestaña" data-adjuntos>
        <div class="bloque_interno">
          <div class="grid_fila_adjunto" style="width: 100%;">
            <div class="grid_descripcion">
              <h5>DESCRIPCIÓN</h5>
            </div>
            <div class="grid_nombre_archivo">
              <h5>NOMBRE ARCHIVO</h5>
            </div>
            <div class="grid_boton">
              <h5>&nbsp;</h5>
            </div>
          </div>
          <div style="width: 100%;" data-js-contenedor>
          </div>
          <div class="grid_fila_adjunto" style="width: 100%;" data-modo-mostrar="NUEVO,EDITAR,ADJUNTAR" data-adjunto>
            <div class="grid_descripcion">
              <input data-js-texto-no-formatear-numero class="form-control" placeholder="DESCRIPCIÓN" style="text-align: left;" data-descripcion>
            </div>
            <div class="grid_nombre_archivo">
              <input data-js-texto-no-formatear-numero class="form-control" type="file" style="text-align: center;" data-archivo>
            </div>
            <div class="grid_boton">
              <button class="btn" type="button" data-js-agregar-adjunto data-modo-mostrar="NUEVO,EDITAR,ADJUNTAR"><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <?php
            $molde_str = '$adj';
            $n = function($s) use (&$id_casino,&$t,&$molde_str){
              return "adjuntos[$molde_str][$s]";
            };
            $descripcion = $n('descripcion');
            $nombre_archivo = $n('nombre_archivo');
            $id_archivo = $n('id_archivo');
            $archivo = $n('archivo');
            $link = $n('link');
          ?>
          <div style="width: 100%;" data-js-molde="{{$molde_str}}" data-adjunto>
            <div class="grid_fila_adjunto" style="width: 100%;">
              <div class="grid_descripcion">
                <input data-js-texto-no-formatear-numero style="width: 100%;text-align: left;" class="form-control" data-name="{{$descripcion}}" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div class="grid_nombre_archivo">
                <input data-js-texto-no-formatear-numero data-js-click-abrir-val-hermano="[data-es-link]" style="width: 100%;text-align: center;cursor: pointer;" class="form-control" data-name="{{$nombre_archivo}}" data-depende="id_casino,año_mes" data-readonly='[{}]'>
                <input data-js-texto-no-formatear-numero data-es-link data-name="{{$link}}" data-modo-mostrar="">
              </div>
              <div data-modo-mostrar="">
                <input data-js-texto-no-formatear-numero style="flex: 1;" class="form-control" data-name="{{$id_archivo}}" data-depende="id_casino,año_mes" data-readonly='[{}]'>
              </div>
              <div class="grid_boton">
                <button class="btn" type="button" data-js-borrar-adjunto data-modo-mostrar="NUEVO,EDITAR"><i class="fa fa-fw fa-trash-alt"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
  @if($puede_cargar)
  <button class="btn btn-successAceptar" type="button" data-js-enviar="/canon/adjuntar" data-modo-mostrar="ADJUNTAR">ADJUNTAR</button>
  <button class="btn btn-successAceptar" type="button" data-js-enviar="/canon/guardar" data-modo-mostrar="NUEVO,EDITAR">GUARDAR</button>
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
  <script src="/js/Canon/index.js" charset="utf-8" type="module"></script>

@endsection
