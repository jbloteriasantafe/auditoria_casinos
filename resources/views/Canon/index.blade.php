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
  
  [data-content-popover][data-molde-popover] {
    display: none;
  }
  [data-content-popover]:not([data-molde-popover]) {
    display: flex;
  }
 
</style>

<style>
  #pant_canon table tr.canon-table-row {
    display: table-row !important;
  }
  #pant_canon table tr.canon-table-row td {
    display: table-cell !important;
  }
  #pant_canon table tr.canon-table-row th {
    display: table-cell !important;
  }
  #pant_canon table tr.canon-table-row-cuenta {
    background: #f2f2f2;
  }
  #pant_canon table tr.canon-table-row-cuenta[data-cuenta-display="none"] {
    display: none !important;
  }
  #pant_canon [data-js-filtro-tabla] th, 
  #pant_canon [data-js-filtro-tabla] td {
    width: 11.11%;/* @HACK: poner algun atributo a la tabla para que haya columnas fijas? */
  }
  
  #pant_canon [data-js-filtro-tabla] td button {
    padding: 0.3rem;/* @HACK: achivo los botones asi entran que son tantos -___- */
  }
  
  #pant_canon [data-js-filtro-tabla] tr td.saldo_posterior.saldo-balanceado {
    color: #0c0;
  }
  #pant_canon [data-js-filtro-tabla] tr td.saldo_posterior.saldo-desbalanceado {
    color: #ff7800;
  }
</style>

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

<style>
  .VerCargarOperador input[type="color"][readonly] {
    pointer-events: none;
  }
</style>

@endsection
@section('contenidoVista')

<div class="row">
  <div class="tabs" data-js-tabs="">
    <div>
      <a data-js-tab="#pant_canon">Canon</a>
    </div>
    @if($permisos('canon_operador_cargar') || $permisos('canon_operador_ver'))
    <div>
      <a data-js-tab="#pant_operadores">Operadores</a>
    </div>
    @endif
    @if($permisos('canon_agrupamiento_cargar') || $permisos('canon_agrupamiento_ver'))
    <div>
      <a data-js-tab="#pant_agrupamientos_todos">Agrupamientos</a>
    </div>
    <div>
      <a data-js-tab="#pant_defecto">Valores por Defecto</a>
    </div>
    @endif
    @if($permisos('canon_permiso_ver') || $permisos('canon_permiso_cargar'))
    <div>
      <a data-js-tab="#pant_permisos">Permisos</a>
    </div>
    @endif
  </div>
</div>

<style>
  #pant_canon tbody tr td.tright {
    text-align: right;
  }
</style>
<div id="pant_canon" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    CANON
    @if($permisos('canon_cargar'))
    <button class="btn" type="button" data-js-nuevo="/canon/obtener">NUEVO</button>
    @endif
    @if($permisos('canon_ver'))
    <button data-js-descargar="/canon/descargar" class="btn btn-sucess" type="button" style="font-size: 0.9rem;"><i class="fa fa-arrow-circle-down"></i>DESCARGAR<i class="fa fa-spinner fa-spin" data-js-descargando style="display: none;"></i></button> 
    <a href="/canon/descargarPlanillas" target="_blank" class="btn btn-sucess" role="button" style="font-size: 0.9rem;"><i class="fa fa-arrow-circle-down"></i>PLANILLAS</a>
    @endif
    @endslot
    
    @slot('target_buscar')
    /canon/buscar
    @endslot
    
    @slot('filtros')
    <div class="col-md-4">
      <h5>Operador</h5>
      <select class="form-control" name="id_operador">
        <option value='' selected>- TODOS -</option>
        @foreach($operadores as $o)
        <option value='{{$o->id_operador}}'>{{$o->nombre}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <h5>PERÍODO</h5>
      <div style="display: flex;">
        @component('Components/inputFecha',[
          'attrs' => 'name="año_mes[0]"',
          'attrs_dtp' => 'data-date-format="yyyy-mm" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
          'placeholder' => 'DESDE'
        ])
        @endcomponent
        @component('Components/inputFecha',[
          'attrs' => 'name="año_mes[1]"',
          'attrs_dtp' => 'data-date-format="yyyy-mm" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;flex: 1;"',
          'placeholder' => 'HASTA'
        ])
        @endcomponent
      </div>
    </div>
    @if($permisos('canon_deseliminar'))
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
    <tr class="canon-table-row">
      <th style="text-align: center;" data-js-sortable="año_mes">AÑO MES</th>
      <th style="text-align: center;">OPERADOR</th>
      <th style="text-align: center;">ESTADO</th>
      <th style="text-align: center;">DEVENGADO</th>
      <th style="text-align: center;">DETERMINADO</th>
      <th style="text-align: center;">INTERESES Y CARGOS</th>
      <th style="text-align: center;">PAGO</th>
      <th style="text-align: center;">SALDO</th>
      <th style="text-align: center;">ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <?php $rowspan=count($cuentas)+1; ?>
    <tr class="canon-table-row" data-table-id="id_canon" data-canon>
      <!-- Bootstrap usa data-rowspan -->
      <td data-cuentas-rowspan="{{$rowspan}}" class="año_mes" style="text-align: center;">AÑO MES</td>
      <td data-cuentas-rowspan="{{$rowspan}}" class="operador"  style="text-align: center;">OPERADOR</td>
      <td data-cuentas-rowspan="{{$rowspan}}" class="cambios-estados" style="text-align: center;">
        @if($permisos('canon_ver') || $permisos('canon_cargar'))
        <div data-content-popover data-molde-popover="acciones-canon" style="flex-direction: row;justify-content: center;"><!-- Esto esta aca porque tiene que estar en el <tr></tr> nomas... no tiene otro sentido -->
          <div style="display: flex;flex-direction: column;justify-content: center;padding-right: 1em;">
            @if($permisos('canon_ver'))
            <button class="btn" type="button" data-js-ver="/canon/obtenerConHistorial" title="VER/HISTORIAL">VER CANON<i class="fa fa-fw fa-search-plus"></i></button>
            @endif
            @if($permisos('canon_cargar'))
            <button class="btn" type="button" data-js-adjuntar="/canon/obtener" data-estado-visible="GENERADO,PAGADO" title="ADJUNTAR">ADJUNTAR <i class="fa fa-fw fa-paperclip"></i></button>
            <button class="btn" type="button" data-js-editar="/canon/obtener" data-estado-visible="GENERADO"  title="EDITAR">EDITAR CANON<i class="fas fa-fw fa-pencil-alt"></i></button>
            @endif
          </div>
        </div>
        @endif
        <div data-content-popover data-molde-popover="planillas" style="flex-direction: column;justify-content: center;"><!-- Esto esta aca porque tiene que estar en el <tr></tr> nomas... no tiene otro sentido -->
          <a href="/canon/planillaPDF" target="_blank" title="REPORTE">Valores</a>
          <a href="/canon/planillaDevengado" target="_blank" title="IMPRIMIR DEVENGADO">Devengado</a>
          <a href="/canon/planillaDeterminado"  target="_blank" title="IMPRIMIR DETERMINADO">Determinado</a>
        </div>
        <span style="color: blue;font-weight: bold;font-size: 0.8em;padding-right: 0.1em;"><sup class="antiguo">XXX</sup></span>
        <span class="estado">ESTADO</span>
        @if($permisos('canon_cargar'))
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
      <td class="tright devengado" data-formatear-numero>DEVENGADO</td>
      <td class="tright determinado" data-formatear-numero>DETERMINADO</td>
      <td class="tright intereses_y_cargos" data-formatear-numero>INTERESES Y CARGOS</td>
      <td class="tright">
        <button data-js-click-toggle-cuentas class="btn" type="button" title="VER PAGOS">
          <i class="fa fa-sort-down"></i>
        </button>
        <span class="pago" data-formatear-numero>PAGO</span>
      </td>
      <td class="saldo_posterior" style="text-align: center;font-weight: bolder;font-size: 200%;">SALDO</td>
      <td style="text-align: center;">
        @if($permisos('canon_ver') || $permisos('canon_cargar'))
        <a tabindex="0" class="btn btn-info info" data-toggle-popover="acciones-canon" data-content="COMPLETAR!" data-html="true" data-trigger="focus" data-placement="top">
          <i class="fa fa-window-restore" aria-hidden="true"></i>
        </a>
        <a tabindex="0" class="btn btn-info info" data-toggle-popover="planillas" data-content="COMPLETAR!" data-html="true" data-trigger="focus" data-placement="top">
          <i class="fa fa-print"></i>
        </a>
        <a tabindex="0" href="/canon/planillaInformeCanon" target="_blank" class="btn btn-info info" title="Informe de Canon">
          <i class="fa fa-list-ul"></i>
        </a>
        @endif
        @if($permisos('canon_deseliminar'))
        <button data-mostrar-borrado class="btn" type="button" data-js-ver="/canon/obtenerConHistorial" title="VER/HISTORIAL"><i class="fa fa-fw fa-search-plus"></i></button>
        <button data-mostrar-borrado class="btn" type="button" data-js-cambiar-estado="/canon/desborrar" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "BORRADO" a "ACTIVO"?' title="DESBORRAR">
          <i class="fa fa-backward"></i>
        </button>
        @endif
        @if($permisos('canon_cargar') || $permisos('canon_deseliminar'))
        <button class="btn" type="button" data-js-borrar="/canon/borrar" title="BORRAR" 
          @if($permisos('canon_deseliminar'))
          data-estado-visible="GENERADO,PAGADO"
          @endif
        >
          <i class="fa fa-fw fa-trash-alt"></i>
        </button>
        @endif
      </td>
    </tr>
    @foreach($cuentas as $cidx => $c)
    <tr class="canon-table-row canon-table-row-cuenta" data-table-id="id_canon_cuenta" data-cuenta-idx="{{$cidx}}" data-cuenta="{{$c}}" data-cuenta-display="none">
      <td>
        <div style="display: flex;width: 100%;">
          <span style="text-align: left;">Cuenta: </span>
          <span style="flex: 1;font-weight: bold;text-align: right;">
            {{empty($c)? '-' : $cuentas[$cidx-1]}}
          </span>
        </div>
        @if($permisos('canon_ver') || $permisos('canon_cargar'))
        <div data-content-popover data-molde-popover="acciones-cuenta" style="flex-direction: row;justify-content: center;"><!-- Esto esta aca porque tiene que estar en el <tr></tr> nomas... no tiene otro sentido -->
          <div style="display: flex;flex-direction: column;justify-content: center;padding-left: 1em;">
            @if($permisos('canon_cuenta_ver'))
            <button class="btn" type="button" data-js-ver-pagos="/canon/pagos/obtenerConHistorial" title="VER PAGO">VER PAGOS <i class="fa fa-book"></i></button>
            @endif
            @if($permisos('canon_cuenta_cargar'))
            <button class="btn" type="button" data-js-editar-pagos="/canon/pagos/obtener" title="CARGAR PAGO">EDITAR PAGOS <i class="fa fas fa-hand-holding-usd"></i></button>
            @endif
          </div>
        </div>
        @endif
      </td>
      <td class="tright" data-name="canon_cuenta[{{$cidx}}][determinado]" data-formatear-numero>-</td>
      <td class="tright" data-name="canon_cuenta[{{$cidx}}][intereses_y_cargos]" data-formatear-numero>-</td>
      <td class="tright" data-name="canon_cuenta[{{$cidx}}][pago]" data-formatear-numero>-</td>
      <td class="tright" data-name="canon_cuenta[{{$cidx}}][saldo_posterior]" data-formatear-numero>-</td>
      <td style="text-align: center;">
        @if($permisos('canon_cuenta_ver') || $permisos('canon_cuenta_cargar'))
        <a tabindex="0" class="btn btn-info info" data-toggle-popover="acciones-cuenta" data-content="COMPLETAR!" data-html="true" data-trigger="focus" data-placement="top">
          <i class="fa fa-window-restore" aria-hidden="true"></i>
        </a>
        @endif
      </td>
    </tr>
    @endforeach
    
    @endslot
  @endcomponent
</div>

@if($permisos('canon_operador_ver') || $permisos('canon_operador_cargar'))
<div id="pant_operadores" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    Operadores
    @if($permisos('canon_operador_cargar'))
    <button class="btn" type="button" data-js-nuevo="/canon/operador/obtener">NUEVO</button>
    @endif
    @endslot
    
    @slot('target_buscar')
    /canon/operador/buscar
    @endslot
    
    @slot('filtros')
    @if($permisos('canon_operador_deseliminar'))
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
      <th>ID</th>
      <th>NOMBRE</th>
      <th>ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr data-table-id="id_operador">
      <td class="id_operador">ID</td>
      <td class="nombre">NOMBRE</td>
      <td>
        @if($permisos('canon_operador_ver') || $permisos('canon_operador_cargar'))
        <div data-content-popover data-molde-popover="acciones" style="flex-direction: row;justify-content: center;"><!-- Esto esta aca porque tiene que estar en el <tr></tr> nomas... no tiene otro sentido -->
          <div style="display: flex;flex-direction: column;justify-content: center;padding-right: 1em;">
            @if($permisos('canon_operador_ver'))
            <button class="btn" type="button" data-js-ver="/canon/operador/obtenerConHistorial" title="VER/HISTORIAL">VER OPERARIO<i class="fa fa-fw fa-search-plus"></i></button>
            @endif
            @if($permisos('canon_operador_cargar'))
            <button class="btn" type="button" data-js-editar="/canon/operador/obtener" title="EDITAR">EDITAR OPERARIO<i class="fas fa-fw fa-pencil-alt"></i></button>
            @endif
          </div>
        </div>
        @endif
        @if($permisos('canon_operador_ver'))
        <a tabindex="0" class="btn btn-info info" data-toggle-popover="acciones" data-content="COMPLETAR!" data-html="true" data-trigger="focus" data-placement="top">
          <i class="fa fa-window-restore" aria-hidden="true"></i>
        </a>
        @endif
        @if($permisos('canon_operador_deseliminar'))
        <button data-mostrar-borrado class="btn" type="button" data-js-desborrar="/canon/operador/desborrar" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "BORRADO" a "ACTIVO"?' title="DESBORRAR">
          <i class="fa fa-backward"></i>
        </button>
        @endif
        @if($permisos('canon_operador_cargar') || $permisos('canon_operador_deseliminar'))
        <button class="btn" type="button" data-js-borrar="/canon/operador/borrar" title="BORRAR">
          <i class="fa fa-fw fa-trash-alt"></i>
        </button>
        @endif
      </td>
    </tr>
    @endslot
  @endcomponent
</div>
@endif

@if($permisos('canon_agrupamiento_cargar') || $permisos('canon_agrupamiento_ver'))
<div id="pant_agrupamientos_todos">
  <div class="col-md-6" id="pant_agrupamientos">
    @component('Canon.bAgrupamientos',compact('permisos'))
    @endcomponent
  </div>
  
  <div class="col-md-6" id="pant_grupos_operadores">
    @component('Components/FiltroTabla')
      @slot('titulo')
      Grupos Operadores
      @if($permisos('canon_agrupamiento_cargar'))
      <button class="btn" type="button" data-js-nuevo="/canon/grupo_operador/obtener">NUEVO</button>
      @endif
      @endslot
      
      @slot('target_buscar')
      /canon/grupo_operador/buscar
      @endslot
      
      @slot('filtros')
      @if($permisos('canon_agrupamiento_deseliminar'))
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
        <th>ID</th>
        <th>NOMBRE</th>
        <th>OPERADORES</th>
        <th>ACCION</th>
      </tr>
      @endslot
      
      @slot('molde')
      <tr data-table-id="id_grupo_operador">
        <td class="id_grupo_operador">ID</td>
        <td class="nombre">NOMBRE</td>
        <td class="operadores">OPERADORES</td>
        <td>
          @if($permisos('canon_agrupamiento_ver'))
          <button class="btn" type="button" data-es_individual="|0|1|" data-js-ver="/canon/grupo_operador/obtenerConHistorial" title="VER/HISTORIAL"><i class="fa fa-fw fa-search-plus"></i></button>
          @endif
          @if($permisos('canon_agrupamiento_cargar'))
          <button class="btn" type="button" data-es_individual="|0|" data-js-editar="/canon/grupo_operador/obtener" title="EDITAR"><i class="fas fa-fw fa-pencil-alt"></i></button>
          @endif
          @if($permisos('canon_agrupamiento_deseliminar'))
          <button data-mostrar-borrado data-es_individual="|0|" class="btn" type="button" data-js-desborrar="/canon/grupo_operador/desborrar" data-mensaje-cambiar-estado='¿Esta seguro que quiere cambiar el estado de "BORRADO" a "ACTIVO"?' title="DESBORRAR">
            <i class="fa fa-backward"></i>
          </button>
          @endif
          @if($permisos('canon_agrupamiento_ver') || $permisos('canon_agrupamiento_cargar'))
          <button class="btn" type="button" data-es_individual="|0|" data-js-borrar="/canon/grupo_operador/borrar" title="BORRAR">
            <i class="fa fa-fw fa-trash-alt"></i>
          </button>
          @endif
        </td>
      </tr>
      @endslot
    @endcomponent
  </div>

</div>

<div id="pant_defecto" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    <div>VALORES POR DEFECTO</div>
    <form style="display: flex;">
      <input class="form-control" name="campo" placeholder="Campo" style="flex: 1;">
      <div data-js-nuevo style="flex: 2;"></div>
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

@if($permisos('canon_permiso_ver'))
<div id="pant_permisos" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    <div>Permisos</div>
    @if($permisos('canon_permiso_cargar'))
    <form id="pant_permisos_form" action="/canon/permiso/ingresar" method="POST" style="display: flex;">
      <input class="form-control" name="permiso" placeholder="nombre_permiso" style="flex: 1;">
      <input class="form-control" name="user_name" placeholder="user_name" style="flex: 1;">
      <input class="form-control" name="id_operador" placeholder="id_operador" style="flex: 1;">
      <div data-js-nuevo style="flex: 2;"></div>
      <div style="flex: 1;">
        <button class="btn" type="button" data-js-click-submit-form="#pant_permisos_form">GUARDAR</button>
      </div>
    </form>
    @endif
    @endslot
    
    @slot('target_buscar')
    /canon/permiso/buscar
    @endslot
    
    @slot('filtros')
    <div class="col-md-4">
      <h5>Permiso</h5>
      <input class="form-control" name="permiso">
    </div>
    <div class="col-md-4">
      <h5>Usuario</h5>
      <input class="form-control" name="user_name">
    </div>
    <div class="col-md-4">
      <h5>ID Operador</h5>
      <input class="form-control" name="id_operador">
    </div>
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>PERMISO</th>
      <th>USUARIO</th>
      <th>ID OPERADOR</th>
      <th>ACCIÓN</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr data-table-id="id_canon_permiso_usuario">
      <td class="permiso">-PERMISO-</td>
      <td class="user_name">-user_name-</td>
      <td class="id_operador">-id_operador-</td>
      <td>
        @if($permisos('canon_permiso_eliminar'))
        <button class="btn" type="button" data-js-borrar="/canon/permiso/borrar">
          <i class="fa fa-fw fa-trash-alt"></i>
        </button>
        @endif
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

@endif

@if($permisos('canon_ver') || $permisos('canon_cargar'))

@component('Components/modal',[
  'clases_modal' => 'VerCargarCanon',
  'attrs_modal' => 'data-js-modal-canon-comportamiento-comun data-js-modal-ver-cargar-canon',
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
  <form style="display: flex;flex-direction: column;" data-js-recalcular="/canon/recalcular">
    <div style="width: 100%;display: flex;">
      <div>
        <h5>AÑO MES</h5>
        @component('Components/inputFecha',[
          'attrs' => 'data-js-formatear-año-mes name="año_mes" placeholder="AÑO MES" data-js-empty-si-cambio="[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"',
          'attrs_dtp' => 'data-date-format="yyyy-mm" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"',
          'placeholder' => 'aaaa-mm'
        ])
        @endcomponent
      </div>
      <div>
        <h5>Operador</h5>
        <select class="form-control" name="id_operador"
          data-js-empty-si-cambio="[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"
          data-readonly='[{"modo": "VER"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]'>
          <option value="" selected>- SELECCIONE -</option>
          @foreach($operadores as $o)
          <option value="{{$o->id_operador}}">{{$o->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div>
        <h5>Estado</h5>
        <input data-js-texto-no-formatear-numero class="form-control" name="estado" data-readonly='[{"modo": "*"}]'>
      </div>
      <div>
        <h5>ANTIGUO</h5>
        <select class="form-control" name="es_antiguo" data-check-param
          data-js-empty-si-cambio="[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"
          data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          <option value="0" selected>NO</option>
          <option value="1">SI</option>
        </select>      
      </div>
      <div hidden>
        <input name="id_canon" class="form-control" data-js-texto-no-formatear-numero data-readonly='[{"modo":"*"}]'>
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
        <a data-js-tab="[data-js-modal-ver-cargar-canon] [data-canon-archivo]" tabindex="0">Adjuntos</a>
      </div>
    </div>
    <div class="datos_numericos" style="height: 70vh;overflow-y: scroll;">
      <div class="pestaña" data-total style="display: flex;flex-wrap: wrap;justify-content: center;">
        <div class="bloque_interno" style="flex: 1;">
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
        <div class="bloque_interno" style="flex: 1;">
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
          </div>
        </div>
        <div class="bloque_interno">
          <div class="parametro_chico">
            <h5>Porcentaje Seguridad</h5>
            <input class="form-control" name="porcentaje_seguridad" data-depende="devengado,determinado" data-readonly='[{"modo":"*"}]'>
          </div>
        </div>
      </div>
      <div class="pestaña" data-canon-variable>
        <div data-js-contenedor>
        </div>
        <?php
          $molde_str = '$cv';
          $n = function($s) use (&$molde_str){
            return "canon_variable[$molde_str][$s]";
          };
          $cuenta = $n('cuenta');
          $alicuota = $n('alicuota');
          $devengar = $n('devengar');
          $devengado = $n('devengado');
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
          $determinado_ajuste = $n('determinado_ajuste');
          $determinado = $n('determinado');
        ?>
        <div class="bloque_interno" data-js-molde="{{$molde_str}}">
          <h6 data-titulo>TITULO CANON VARIABLE</h6>
          <div class="bloque_interno" style="width: 100%;display: flex;">
            <div class="parametro_chico"  style="flex: 2;">
              <h5>APLICABLE (%)</h5>
              <input class="form-control" data-name="{{$devengado_apostado_porcentaje_aplicable}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div class="parametro_chico" style="flex: 2;">
              <h5>IMPUESTO LEY (%)</h5>
              <input class="form-control" data-name="{{$devengado_apostado_porcentaje_impuesto_ley}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div class="parametro_chico" style="flex: 3;">
              <h5>ALICUOTA (%)</h5>
              <input class="form-control" data-name="{{$alicuota}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
          </div>
          <div style="width: 100%;display: flex;">
            <div class="bloque_interno" data-css-devengar style="flex: 1;">
              <h4 style="display: flex;">
                <span>DEVENGADO&nbsp;&nbsp;</span>
                <select class="form-control" data-name="{{$devengar}}" data-js-devengar style="width: unset;height: unset;padding: 0;" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <option value="1">SI</option>
                  <option value="0">NO</option>
                </select>
              </h4>
              <div style="width: 100%;display: grid; 
                grid-template-columns: 1fr 1fr 1fr 1fr 1fr; 
                grid-template-rows: 1fr 1fr 1fr; 
                gap: 0px 0px; 
                grid-template-areas: 'grid_apostado grid_base_imponible grid_vacio grid_vacio grid_vacio' 'grid_impuesto grid_bruto grid_subtotal grid_total grid_deduccion' 'grid_devengado grid_vacio2 grid_vacio2 grid_vacio2 grid_vacio2';"
              >
                <div style="grid-area: grid_apostado">
                  <h5>APOSTADO SISTEMA</h5>
                  <input class="form-control" data-name="{{$devengado_apostado_sistema}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_base_imponible" class="valor_intermedio">
                  <h5>BASE IMPONIBLE</h5>
                  <input class="form-control" data-name="{{$devengado_base_imponible}}" data-depende="{{$devengado_apostado_sistema}},{{$devengado_apostado_porcentaje_aplicable}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div style="grid-area: grid_vacio">
                </div>
                <div style="grid-area: grid_impuesto">
                  <h5>IMPUESTO</h5>
                  <input class="form-control" data-name="{{$devengado_impuesto}}" data-depende="{{$devengado_base_imponible}},{{$devengado_apostado_porcentaje_impuesto_ley}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div style="grid-area: grid_bruto">
                  <h5>BRUTO</h5>
                  <input class="form-control" data-name="{{$devengado_bruto}}" data-depende="id_operador,es_antiguo" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_subtotal" class="valor_intermedio">
                  <h5>SUBTOTAL</h5>
                  <input class="form-control" data-name="{{$devengado_subtotal}}" data-depende="{{$devengado_bruto}},{{$devengado_impuesto}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div style="grid-area: grid_total">
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$devengado_total}}" data-depende="{{$devengado_subtotal}},{{$alicuota}}" data-readonly='[{"es_antiguo": "0"},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_deduccion">
                  <h5>DEDUCCIÓN</h5>
                  <input class="form-control" data-name="{{$devengado_deduccion}}" data-depende="id_operador"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_devengado">
                  <h5>DEVENGADO</h5>
                  <input class="form-control" data-name="{{$devengado}}" data-depende="{{$devengado_total}},{{$devengado_deduccion}}" data-readonly='[{"modo": "*"}]'>
                </div>
                <div style="grid-area: grid_vacio2">
                </div>
              </div>
            </div>
            <div class="bloque_interno" style="flex: 1;">
              <h4>DETERMINADO</h4>
              <div style="width: 100%;display: grid; 
                grid-template-columns: 1fr 1fr 1fr 1fr 1fr; 
                grid-template-rows: 1fr 1fr 1fr; 
                gap: 0px 0px; 
                grid-template-areas: 'grid_vacio grid_vacio grid_vacio grid_vacio grid_vacio' 'grid_impuesto grid_bruto grid_subtotal grid_total grid_ajuste' 'grid_determinado grid_cuenta grid_vacio2 grid_vacio2 grid_vacio2';"
              >  
                <div style="grid-area: grid_vacio">
                </div>
                <div style="grid-area: grid_impuesto">
                  <h5>IMPUESTO</h5>
                  <input class="form-control" data-name="{{$determinado_impuesto}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_bruto">
                  <h5>BRUTO</h5>
                  <input class="form-control" data-name="{{$determinado_bruto}}" data-depende="id_operador,es_antiguo" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_subtotal" class="valor_intermedio">
                  <h5>SUBTOTAL</h5>
                  <input class="form-control" data-name="{{$determinado_subtotal}}" data-depende="{{$determinado_bruto}},{{$determinado_impuesto}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div style="grid-area: grid_total">
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$determinado_total}}" data-depende="{{$determinado_subtotal}},{{$alicuota}}" data-readonly='[{"es_antiguo": "0"},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_ajuste">
                  <h5>AJUSTE</h5>
                  <input class="form-control" data-name="{{$determinado_ajuste}}" data-depende="id_operador"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div style="grid-area: grid_determinado">
                  <h5>DETERMINADO</h5>
                  <input class="form-control" data-name="{{$determinado}}" data-depende="{{$determinado_total}},{{$determinado_ajuste}}" data-readonly='[{"modo": "*"}]'>
                </div>
                <div style="grid-area: grid_cuenta">
                  <h5>A CUENTA</h5>
                  <input class="form-control" data-name="{{$cuenta}}" data-depende="id_casino,año_mes" data-readonly='[{"modo": "*"}]'>
                </div>
                <div style="grid-area: grid_vacio2">
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
          $n = function($s) use (&$molde_str){
            return "canon_fijo_mesas[$molde_str][$s]";
          };
          $cuenta = $n('cuenta');
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
          $devengar = $n('devengar');
          $devengado_valor_dolar_cotizado = $n('devengado_valor_dolar_cotizado');
          $devengado_valor_euro_cotizado  = $n('devengado_valor_euro_cotizado');
          $devengado_valor_dolar_diario_cotizado = $n('devengado_valor_dolar_diario_cotizado');
          $devengado_valor_euro_diario_cotizado  = $n('devengado_valor_euro_diario_cotizado');
          $devengado_total_dolar_cotizado = $n('devengado_total_dolar_cotizado');
          $devengado_total_euro_cotizado  = $n('devengado_total_euro_cotizado');
          $devengado_total       = $n('devengado_total');
          $devengado_deduccion   = $n('devengado_deduccion');
          $devengado             = $n('devengado');
          $determinado_valor_dolar_cotizado = $n('determinado_valor_dolar_cotizado');
          $determinado_valor_euro_cotizado  = $n('determinado_valor_euro_cotizado');
          $determinado_valor_dolar_diario_cotizado = $n('determinado_valor_dolar_diario_cotizado');
          $determinado_valor_euro_diario_cotizado  = $n('determinado_valor_euro_diario_cotizado');
          $determinado_total_dolar_cotizado = $n('determinado_total_dolar_cotizado');
          $determinado_total_euro_cotizado  = $n('determinado_total_euro_cotizado');
          $determinado_total       = $n('determinado_total');
          $determinado_ajuste      = $n('determinado_ajuste');
          $determinado             = $n('determinado');
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
                  <input class="form-control" data-name="{{$dias_lunes_jueves}}" placeholder="DIAS" data-depende="id_operador,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_lunes_jueves}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div>
                <h5>Viernes-Sabados</h5>
                <div style="display: flex;flex-direction: column;">
                  <input class="form-control" data-name="{{$dias_viernes_sabados}}" placeholder="DIAS" data-depende="id_operador,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_viernes_sabados}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>               
              <div>
                <h5>Domingos</h5>
                <div style="display: flex;flex-direction: column;">
                  <input class="form-control" data-name="{{$dias_domingos}}" placeholder="DIAS" data-depende="id_operador,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_domingos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div>
                <h5>Todos los dias</h5>
                <div style="display: flex;flex-direction: column;">
                  <input class="form-control" data-name="{{$dias_todos}}" placeholder="DIAS" data-depende="id_operador,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_todos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div>
                <h5>Fijos</h5>
                <div style="display: flex;flex-direction: column;">
                  <input class="form-control" data-name="{{$dias_fijos}}" placeholder="DIAS" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input class="form-control" data-name="{{$mesas_fijos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div>
                <h5>&nbsp;</h5>
                <input class="form-control" style="visibility: hidden;">
              </div>
              <div>
                <h5>BRUTO</h5>
                <input class="form-control" data-name="{{$bruto}}" data-depende="id_operador,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
            </div>
            <div style="display: flex;">
              <div class="valor_intermedio">
                <h5>MESAS×DIAS</h5>
                <input class="form-control" data-name="{{$mesas_dias}}" data-depende="{{$dias_lunes_jueves}},{{$mesas_lunes_jueves}},{{$dias_viernes_sabados}},{{$mesas_viernes_sabados}},{{$dias_domingos}},{{$mesas_domingos}},{{$dias_todos}},{{$mesas_todos}},{{$dias_fijos}},{{$mesas_fijos}}" data-readonly='[{"modo":"*"}]'>
              </div>
              <div>
                <h5>VALOR DOLAR (USD)</h5>
                <input class="form-control" data-name="valor_dolar" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div>
                <h5>VALOR EURO (EUR)</h5>
                <input class="form-control" data-name="valor_euro" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div class="parametro_chico">
                <h5>DÍAS VALOR</h5>
                <input class="form-control" data-name="{{$dias_valor}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div class="aproximado valor_intermedio">
                <h5>FACTOR DÍAS VALOR ≈ (DÍAS VALOR)⁻¹</h5>
                <input class="form-control" data-name="{{$factor_dias_valor}}" data-depende="{{$dias_valor}}" data-readonly='[{"modo":"*"}]'>
              </div>
            </div>
          </div>
          <div style="display: flex;">
            <div class="bloque_interno" data-css-devengar style="flex: 1;">
              <h4 style="display: flex;">
                <span>DEVENGADO&nbsp;&nbsp;</span>
                <select class="form-control" data-name="{{$devengar}}" data-js-devengar style="width: unset;height: unset;padding: 0;"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <option value="1">SI</option>
                  <option value="0">NO</option>
                </select>
              </h4>
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
                  <input class="form-control" data-name="{{$devengado_valor_dolar_cotizado}}" data-depende="devengado_cotizacion_dolar,valor_dolar" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR EURO (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_valor_euro_cotizado}}" data-depende="devengado_cotizacion_euro,valor_euro" data-readonly='[{"modo":"*"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>VALOR DOLAR DIARIO (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_valor_dolar_diario_cotizado}}" data-depende="{{$devengado_valor_dolar_cotizado}},{{$factor_dias_valor}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR EURO DIARIO (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_valor_euro_diario_cotizado}}" data-depende="{{$devengado_valor_euro_cotizado}},{{$factor_dias_valor}}" data-readonly='[{"modo":"*"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>TOTAL DOLAR (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_total_dolar_cotizado}}" data-depende="{{$devengado_valor_dolar_cotizado}},{{$devengado_valor_dolar_diario_cotizado}},{{$dias_valor}},{{$mesas_dias}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>TOTAL EURO (ARS)</h5>
                  <input class="form-control" data-name="{{$devengado_total_euro_cotizado}}" data-depende="{{$devengado_valor_euro_cotizado}},{{$devengado_valor_euro_diario_cotizado}},{{$dias_valor}},{{$mesas_dias}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div>
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$devengado_total}}" data-depende="{{$devengado_total_dolar_cotizado}},{{$devengado_total_euro_cotizado}}" data-readonly='[{"es_antiguo": "0"},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div>
                  <h5>DEDUCCIÓN</h5>
                  <input class="form-control" data-name="{{$devengado_deduccion}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div>
                  <h5>DEVENGADO</h5>
                  <input class="form-control" data-name="{{$devengado}}" data-depende="{{$devengado_total}},{{$devengado_deduccion}}" data-readonly='[{"modo":"*"}]'>
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
                  <input class="form-control" data-name="{{$determinado_valor_dolar_cotizado}}" data-depende="determinado_cotizacion_dolar,valor_dolar" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR EURO (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_valor_euro_cotizado}}" data-depende="determinado_cotizacion_euro,valor_euro" data-readonly='[{"modo":"*"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>VALOR DOLAR DIARIO (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_valor_dolar_diario_cotizado}}" data-depende="{{$determinado_valor_dolar_cotizado}},{{$dias_valor}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR EURO DIARIO (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_valor_euro_diario_cotizado}}" data-depende="{{$determinado_valor_euro_cotizado}},{{$dias_valor}}" data-readonly='[{"modo":"*"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div class="valor_intermedio">
                  <h5>TOTAL DOLAR (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_total_dolar_cotizado}}" data-depende="{{$determinado_valor_dolar_cotizado}},{{$determinado_valor_dolar_diario_cotizado}},{{$dias_valor}},{{$mesas_dias}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>TOTAL EURO (ARS)</h5>
                  <input class="form-control" data-name="{{$determinado_total_euro_cotizado}}" data-depende="{{$determinado_valor_euro_cotizado}},{{$determinado_valor_euro_diario_cotizado}},{{$dias_valor}},{{$mesas_dias}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div>
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$determinado_total}}" data-depende="{{$determinado_total_dolar_cotizado}},{{$determinado_total_euro_cotizado}}" data-readonly='[{"es_antiguo": "0"},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div>
                  <h5>AJUSTE</h5>
                  <input class="form-control" data-name="{{$determinado_ajuste}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div>
                  <h5>DETERMINADO</h5>
                  <input class="form-control" data-name="{{$determinado}}" data-depende="{{$determinado_total}},{{$determinado_ajuste}}" data-readonly='[{"modo":"*"}]'>
                </div>
                <div>
                  <h5>A CUENTA</h5>
                  <input class="form-control" data-name="{{$cuenta}}" data-depende="id_casino,año_mes" data-readonly='[{"modo":"*"}]'>
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
          $n = function($s) use (&$molde_str){
            return "canon_fijo_mesas_adicionales[$molde_str][$s]";
          };
          $cuenta = $n('cuenta');
          $dias_mes = $n('dias_mes');
          $horas_dia = $n('horas_dia');
          $factor_dias_mes = $n('factor_dias_mes');
          $factor_horas_mes = $n('factor_horas_mes');
          $horas = $n('horas');
          $mesas = $n('mesas');
          $porcentaje = $n('porcentaje');
          $devengar = $n('devengar');
          $devengado_valor_mes = $n('devengado_valor_mes');
          $devengado_valor_dia = $n('devengado_valor_dia');
          $devengado_valor_hora = $n('devengado_valor_hora');
          $devengado_total = $n('devengado_total');
          $devengado_deduccion = $n('devengado_deduccion');
          $devengado = $n('devengado');
          $determinado_valor_mes = $n('determinado_valor_mes');
          $determinado_valor_dia = $n('determinado_valor_dia');
          $determinado_valor_hora = $n('determinado_valor_hora');
          $determinado_total = $n('determinado_total');
          $determinado_ajuste = $n('determinado_ajuste');
          $determinado = $n('determinado');
        ?>
        <div class="bloque_interno" data-js-molde="{{$molde_str}}">
          <h6 data-titulo>TITULO MESA ADICIONAL</h6>
          <div class="bloque_interno">
            <div style="display: flex;">
              <div class="parametro_chico">
                <h5>DIAS MES</h5>
                <input class="form-control" data-name="{{$dias_mes}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div class="parametro_chico">
                <h5>HORAS DÍA</h5>
                <input class="form-control" data-name="{{$horas_dia}}"  data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
              <div class="aproximado valor_intermedio">
                <h5>FACTOR DIAS MES ≈ (DÍAS MES)⁻¹</h5>
                <input class="form-control" data-name="{{$factor_dias_mes}}" data-depende="{{$dias_mes}}" data-readonly='[{"modo":"*"}]'>
              </div>
              <div class="aproximado valor_intermedio">
                <h5>FACTOR HORAS MES ≈ (DÍAS MES × HORAS DÍA)⁻¹</h5>
                <input class="form-control" data-name="{{$factor_horas_mes}}" data-depende="{{$dias_mes}},{{$horas_dia}}" data-readonly='[{"modo":"*"}]'>
              </div>
            </div>
            <div style="display: flex;">
              <div style="width: 14em;">
                <div style="display: flex;">
                  <h5 style="flex: 1;">HORAS</h5>
                  <h5 style="flex: 1;">MESAS</h5>
                </div>
                <div style="display: flex;">
                  <input style="flex: 1;border-bottom-right-radius: 0px;border-top-right-radius: 0px;border-right: 1px dashed gray;" class="form-control" data-name="{{$horas}}"  data-depende="{{$mesas}}"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <input style="flex: 1;border-bottom-left-radius: 0px;border-top-left-radius: 0px;border-left: 1px dashed gray;" class="form-control" data-name="{{$mesas}}" data-depende="{{$horas}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div class="parametro_chico">
                <h5>PORCENTAJE</h5>
                <input class="form-control" data-name="{{$porcentaje}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              </div>
            </div>
          </div>
          <div style="display: flex;">
            <div class="bloque_interno" data-css-devengar style="flex: 1;">
              <h4 style="display: flex;">
                <span>DEVENGADO&nbsp;&nbsp;</span>
                <select class="form-control" data-name="{{$devengar}}" data-js-devengar style="width: unset;height: unset;padding: 0;"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                  <option value="1">SI</option>
                  <option value="0">NO</option>
                </select>
              </h4>
              <div style="display: flex;">
                <div>
                  <h5>VALOR MES</h5>
                  <input class="form-control" data-name="{{$devengado_valor_mes}}" data-depende="id_operador" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR DÍA</h5>
                  <input class="form-control" data-name="{{$devengado_valor_dia}}" data-depende="{{$devengado_valor_mes}},{{$factor_dias_mes}}" data-depende="id_operador" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR HORA</h5>
                  <input class="form-control" data-name="{{$devengado_valor_hora}}" data-depende="{{$devengado_valor_mes}},{{$factor_horas_mes}}" data-readonly='[{"modo":"*"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div>
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$devengado_total}}" data-depende="{{$devengado_valor_mes}},{{$devengado_valor_dia}},{{$devengado_valor_hora}},{{$horas}},{{$porcentaje}}" data-readonly='[{"es_antiguo": "0"},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div>
                  <h5>DEDUCCIÓN</h5>
                  <input class="form-control" data-name="{{$devengado_deduccion}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div>
                  <h5>DEVENGADO</h5>
                  <input class="form-control" data-name="{{$devengado}}" data-depende="{{$devengado_total}},{{$devengado_deduccion}}" data-readonly='[{"modo": "*"}]'>
                </div>
              </div>
            </div>
            <div class="bloque_interno" style="flex: 1;">
              <h4>DETERMINADO</h4>
              <div style="display: flex;">
                <div>
                  <h5>VALOR MES</h5>
                  <input class="form-control" data-name="{{$determinado_valor_mes}}" data-depende="id_operador" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR DÍA</h5>
                  <input class="form-control" data-name="{{$determinado_valor_dia}}" data-depende="{{$determinado_valor_mes}},{{$factor_dias_mes}}" data-depende="id_operador" data-readonly='[{"modo":"*"}]'>
                </div>
                <div class="valor_intermedio">
                  <h5>VALOR HORA</h5>
                  <input class="form-control" data-name="{{$determinado_valor_hora}}" data-depende="{{$determinado_valor_mes}},{{$factor_horas_mes}}" data-readonly='[{"modo":"*"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div>
                  <h5>TOTAL</h5>
                  <input class="form-control" data-name="{{$determinado_total}}" data-depende="{{$determinado_valor_mes}},{{$determinado_valor_dia}},{{$determinado_valor_hora}},{{$horas}},{{$porcentaje}}" data-readonly='[{"es_antiguo": "0"},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
                <div>
                  <h5>AJUSTE</h5>
                  <input class="form-control" data-name="{{$determinado_ajuste}}" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
                </div>
              </div>
              <div style="display: flex;">
                <div>
                  <h5>DETERMINADO</h5>
                  <input class="form-control" data-name="{{$determinado}}" data-depende="{{$determinado_total}},{{$determinado_ajuste}}" data-readonly='[{"modo": "*"}]'>
                </div>
                <div>
                  <h5>A CUENTA</h5>
                  <input class="form-control" data-name="{{$cuenta}}" data-depende="id_casino,año_mes" data-readonly='[{"modo":"*"}]'>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="pestaña" data-canon-archivo>
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
          <div class="grid_fila_adjunto" style="width: 100%;" data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]' data-archivo>
            <div class="grid_descripcion">
              <input data-js-texto-no-formatear-numero class="form-control" placeholder="DESCRIPCIÓN" style="text-align: left;" data-descripcion>
            </div>
            <div class="grid_nombre_archivo">
              <input data-js-texto-no-formatear-numero class="form-control" type="file" style="text-align: center;" data-file>
            </div>
            <div class="grid_boton">
              <button class="btn" type="button" data-js-agregar-archivo data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]'><i class="fa fa-plus"></i></button>
            </div>
          </div>
          <?php
            $molde_str = '$adj';
            $n = function($s) use (&$molde_str){
              return "canon_archivo[$molde_str][$s]";
            };
            $descripcion = $n('descripcion');
            $nombre_archivo = $n('nombre_archivo');
            $id_archivo = $n('id_archivo');
            $archivo = $n('archivo');
            $link = $n('link');
          ?>
          <div style="width: 100%;" data-js-molde="{{$molde_str}}" data-archivo>
            <div class="grid_fila_adjunto" style="width: 100%;">
              <div class="grid_descripcion">
                <input data-js-texto-no-formatear-numero style="width: 100%;text-align: left;" class="form-control" data-name="{{$descripcion}}" data-depende="id_operador,año_mes" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div class="grid_nombre_archivo">
                <input data-js-texto-no-formatear-numero data-js-click-abrir-val-hermano="[data-es-link]" style="width: 100%;text-align: center;cursor: pointer;" class="form-control" data-name="{{$nombre_archivo}}" data-depende="id_operador,año_mes" data-readonly='[{"modo":"*"}]'>
                <input data-js-texto-no-formatear-numero data-es-link data-name="{{$link}}" hidden>
              </div>
              <div hidden>
                <input data-js-texto-no-formatear-numero style="flex: 1;" class="form-control" data-name="{{$id_archivo}}" data-depende="id_operador,año_mes" data-readonly='[{"modo":"*"}]'>
              </div>
              <div class="grid_boton">
                <button class="btn" type="button" data-js-borrar-archivo data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'><i class="fa fa-fw fa-trash-alt"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
  @if($permisos('canon_cargar'))
  <button class="btn btn-successAceptar" type="button" data-js-enviar="/canon/adjuntar" data-modo-mostrar='[{"modo": "ADJUNTAR"}]' data-modo-mostrar="ADJUNTAR">ADJUNTAR</button>
  <button class="btn btn-successAceptar" type="button" data-js-enviar="/canon/guardar" data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>GUARDAR</button>
  @endif
  @endslot
@endcomponent

@component('Components/modal',[
  'clases_modal' => 'VerCargarCanon',
  'attrs_modal' => 'data-js-modal-canon-comportamiento-comun data-js-modal-ver-cargar-canon-pagos',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 98,
])
  @slot('titulo')
  PAGOS
  @endslot
  @slot('cuerpo')
  <div style="width: 100%;display: flex;align-items: center;justify-content: flex-end;" data-modo-mostrar='[{"modo": "VER"}]'>
    <h5 style="width: 15rem;">Version</h5>
    <select class="form-control" data-js-select-historial style="width: 15rem;">
    </select>
  </div>
  <form style="display: flex;flex-direction: column;" action="/canon/pagos/guardar" method="POST" data-js-recalcular="/canon/pagos/recalcular">
    <div style="width: 100%;display: flex;">
      <div>
        <h5>AÑO MES</h5>
        <input name="año_mes" data-js-texto-no-formatear-numero data-readonly='[{"modo":"*"}]' class="form-control">
      </div>
      <div>
        <h5>Operador</h5>
        <select name="id_operador"  class="form-control" data-readonly='[{"modo":"*"}]'>
          @foreach($operadores as $o)
          <option value="{{$o->id_operador}}">{{$o->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div>
        <h5>Estado</h5>
        <input name="estado" data-js-texto-no-formatear-numero class="form-control" data-check-param data-readonly='[{"modo":"*"}]'>
      </div>
      <div>
        <h5>Cuenta</h5>
        <input name="cuenta" data-js-texto-no-formatear-numero class="form-control" data-check-param data-readonly='[{"modo":"*"}]'>
      </div>
      <div hidden>
        <input name="id_canon_cuenta" data-js-texto-no-formatear-numero  class="form-control" data-readonly='[{"modo":"*"}]'>
      </div>
      <div hidden>
        <input name="id_canon" data-js-texto-no-formatear-numero  class="form-control" data-readonly='[{"modo":"*"}]'>
      </div>
    </div>
    <div class="tabs" data-js-tabs>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-canon] [data-canon-pagos]" tabindex="0" style="width: 15em;">Pagos</a>
      </div>
    </div>
    <div class="datos_numericos" style="height: 70vh;overflow-y: scroll;">
      <div class="pestaña" data-canon-pagos>      
        <div class="bloque_interno">
          <h4>DETERMINADO</h4>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>Determinado</h5>
              <input name="determinado" class="form-control" data-readonly='[{"modo":"*"}]'>
            </div>
          </div>
        </div>
        <div class="bloque_interno">
          <h4>PRINCIPAL</h4>
          <div style="width: 100%;display: flex;">
            <div>
              <h5>Saldo anterior</h5>
              <input class="form-control" name="saldo_anterior" data-depende="año_mes,id_operador" data-readonly='[{"modo":"*"}]'>
            </div>
            <div data-modo-mostrar='[{"estado": "CERRADO"},{"estado": "PAGADO"}]'>
              <h5>Saldo anterior (CERRADO)</h5>
              <input class="form-control" name="saldo_anterior_cerrado" data-depende="año_mes,id_operador" data-readonly='[{"modo":"*"}]'>
            </div>
            <div>
              <h5>Intereses y Cargos</h5>
              <input class="form-control" name="intereses_y_cargos" data-depende="año_mes,id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
              <input data-js-texto-no-formatear-numero placeholder="MOTIVO" class="form-control" name="motivo_intereses_y_cargos" data-depende="" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div>
              <h5>Principal</h5>
              <input class="form-control" name="principal" data-readonly='[{"modo":"*"}]' data-depende="saldo_anterior,saldo_anterior_cerrado,intereses_y_cargos,determinado">
            </div>
          </div>
        </div>
        <div class="bloque_interno">
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
              <input class="form-control" name="interes_provincial_diario_simple" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
            <div class="parametro_chico">
              <h5>Interés Nacional Mensual Compuesto</h5>
              <input class="form-control" name="interes_nacional_mensual_compuesto" data-depende="id_operador" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
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
        <div hidden>
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
            <input data-name="{{$id_canon_pago}}" data-modo-mostrar='[{"modo": "NOMOSTRARNUNCA"}]'>
            <div class="grid_capital valor_intermedio">
              <input class="form-control" data-name="{{$capital}}" data-readonly='[{"modo":"*"}]'>
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
              <input class="form-control" data-name="{{$pago}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' data-depende="año_mes,id_operador">
            </div>
            <div class="grid_diferencia">
              <input class="form-control" data-name="{{$diferencia}}" data-depende="{{$a_pagar}},{{$pago}}" data-readonly='[{"modo":"*"}]'>
            </div>
            <div class="grid_borrar">
              <button class="btn" type="button" data-js-borrar-pago data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'><i class="fa fa-fw fa-trash-alt"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
  @if($permisos('canon_cargar'))
  <button class="btn btn-successAceptar" type="button" data-js-click-submit-form="form" data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>GUARDAR</button>
  @endif
  @endslot
@endcomponent

@endif

@if($permisos('canon_operador_ver') || $permisos('canon_operador_cargar'))

@component('Components/modal',[
  'clases_modal' => 'VerCargarCanon VerCargarOperador',
  'attrs_modal' => 'data-js-modal-canon-comportamiento-comun data-js-modal-ver-cargar-operador data-modo=\'NUEVO\' ',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 90,
])
  @slot('titulo')
  OPERADOR
  @endslot
  @slot('cuerpo')
  <div style="width: 100%;display: flex;align-items: center;justify-content: flex-end;padding-bottom: 1em;" data-modo-mostrar='[{"modo": "VER"}]'>
    <h5 style="width: 15rem;">Version</h5>
    <select class="form-control" data-js-select-historial style="width: 15rem;">
    </select>
  </div>
  <div class="tabs" data-js-tabs>
    <div>
      <a data-js-tab="[data-js-modal-ver-cargar-operador] [data-datos]" tabindex="0">Datos</a>
    </div>
    <div>
      <a data-js-tab="[data-js-modal-ver-cargar-operador] [data-datos-enlace]" tabindex="0">Datos Enlace</a>
    </div>
    <div>
      <a data-js-tab="[data-js-modal-ver-cargar-operador] [data-cuentas]" tabindex="0">Cuentas</a>
    </div>
    <div>
      <a data-js-tab="[data-js-modal-ver-cargar-operador] [data-canon-variable]" tabindex="0">Canon Variable</a>
    </div>
    <div>
      <a data-js-tab="[data-js-modal-ver-cargar-operador] [data-canon-fijo-mesas]" tabindex="0">Canon Fijo - Mesas</a>
    </div>
    <div>
      <a data-js-tab="[data-js-modal-ver-cargar-operador] [data-canon-fijo-mesas-adicionales]" tabindex="0">Canon Fijo - Mesas Adicional</a>
    </div>
  </div>
  <form  style="display: flex;flex-direction: column;" action="/canon/operador/guardar" method="POST">
    <div class="pestaña" data-datos>
      <div hidden>
        <input name="id_canon_operador" class="form-control" data-readonly='[{"modo":"*"}]'>
      </div>
      <div class="bloque_enterno" style="display:flex;height: 70vh;width: 100%;">
        <div class="bloque_interno"  style="flex: 1;height: 100%;display: flex;flex-direction: column;">
          <div style="flex: 1;">
            <h6>Datos</h6>
            <div class="bloque_interno" style="display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;gap: 10px;">
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">ID</h5>
                <!-- El id no se deberia editar una vez creado para mantener la trazabilidad/historial del operador -->
                <input name="id_operador" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"},{"modo": "EDITAR"}]'>
              </div>
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">Nombre Legal</h5>
                <input name="nombre_legal" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">Nombre</h5>
                <input name="nombre" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">Código</h5>
                <input name="codigo" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">CUIT</h5>
                <input name="cuit" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">Inicio de Actividad</h5>
                @component('Components/inputFecha',[
                  'attrs' => 'name="inicio_actividad" data-js-texto-no-formatear-numero placeholder="YYYY-MM-DD"',
                  'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"}]\' style="padding: 0 !important;"'
                ])
                @endcomponent
              </div>
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">Abreviación</h5>
                <input name="abbr" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">Color plantillas</h5>
                <input data-js-change-agregar-alpha="" name="color" data-js-texto-no-formatear-numero type="color" value="#000000FF" alpha="alpha" colorspace="limited-srgb" class="form-control" data-readonly='[{"modo": "VER"}]'>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="pestaña" data-datos-enlace>
      <div class="bloque_interno" style="display:flex;height: 70vh;width: 100%;">
        <div class="bloque_interno" style="flex: 1;height: 100%;">
          <div style="width: 100%;height: 70%;">
            <h6>Datos De Enlace</h6>
            <div class="bloque_interno" style="display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;gap: 10px;">
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">Casino</h5>
                <input name="codigo_casino" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">Plataforma</h5>
                <input name="codigo_plataforma" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="width: 33.333333333%;">
                <h5 style="padding-left: 0;">Apuestas Deportivas</h5>
                <input name="codigo_apuestas_deportivas" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"}]'>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="pestaña" data-cuentas>
      <div class="bloque_interno" style="width: 100%;height: 70vh;">
        <div class="bloque_interno" style="width: 100%;height: 100%;">     
          <div class="bloque_interno" style="width: 100%;height: 70%;overflow-y: scroll">
            <table style="width: 100%;">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Dia Venc.</th>
                  <th>¿Fin de semana?</th>
                  <th>𝒾%  Diario Simple</th>
                  <th>𝒾%  Mensual Compuesto</th>
                  <th>&nbsp;</th>
                </tr>
              </thead>
              <tbody data-contenedor-cuentas>
              </tbody>
            </table>
          </div>
          <div class="bloque_interno" style="padding-top: 1em;width: 100%;">
            <button class="btn" type="button" data-js-click-agregar-cuenta data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]' style="display: inline-block;">
              <i class="fa fa-plus"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="pestaña" data-canon-variable>
      <div class="bloque_interno" style="width: 100%;height: 70vh;">
        <div class="bloque_interno" style="width: 100%;height: 100%;">        
          <div class="bloque_interno" style="width: 100%;height: 70%;overflow-y: scroll">
            <table style="width: 100%;">
              <thead>
                <tr>
                  <th>Tipo</th>
                  <th>A Cuenta</th>
                  <th>% Pje. Aplicable Apostado</th>
                  <th>% Impuesto Apostado</th>
                  <th>% Alícuota</th>
                  <th>Devengar</th>
                  <th>Deducción</th>
                  <th>&nbsp;</th>
                </tr>
              </thead>
              <tbody data-contenedor-canon-variable>
              </tbody>
            </table>
          </div>
          <div class="bloque_interno" style="padding-top: 1em;width: 100%;">
            <button class="btn" type="button" data-js-click-agregar-canon-variable data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]' style="display: inline-block;">
              <i class="fa fa-plus"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="pestaña" data-canon-fijo-mesas>
      <div class="bloque_interno" style="width: 100%;height: 30vh;display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;gap: 10px;">
        <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;">
          <div style="width: 25%;">
            <h5 style="padding-left: 0;">Valór Dólar</h5>
            <input name="valor_dolar" class="form-control" data-readonly='[{"modo": "VER"}]'>
          </div>
          <div style="width: 25%;">
            <h5 style="padding-left: 0;">Valór Euro</h5>
            <input name="valor_euro" class="form-control" data-readonly='[{"modo": "VER"}]'>
          </div>
        </div>
        <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;gap: 10px;">
          <div style="width: 25%;">
            <h5 style="padding-left: 0;">Día Cotización (Devengado)</h5>
            <input value="1" name="devengado_cotizacion_dia" class="form-control" data-readonly='[{"modo": "VER"}]'>
          </div>
          <div style="width: 25%;">
            <h5 style="padding-left: 0;">Fin De Semana (Devengado)</h5>
            <select class="form-control" name="devengado_cotizacion_fin_de_semana" data-readonly='[{"modo": "VER"}]'>
              <option>Lunes Próximo</option>
              <option>Viernes Anterior</option>
              <option default selected>Sin Movimiento</option>
            </select>
          </div>
        </div>
        <div style="width: 100%;display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;gap: 10px;">
          <div style="width: 25%;">
            <h5 style="padding-left: 0;">Día Cotización (Determinado)</h5>
            <input value="9" name="determinado_cotizacion_dia" class="form-control" data-readonly='[{"modo": "VER"}]'>
          </div>
          <div style="width: 25%;">
            <h5 style="padding-left: 0;">Fin De Semana (Determinado)</h5>
            <select name="determinado_cotizacion_fin_de_semana" class="form-control" data-readonly='[{"modo": "VER"}]'>
              <option>Lunes Próximo</option>
              <option default selected>Viernes Anterior</option>
              <option>Sin Movimiento</option>
            </select>
          </div>
        </div>
      </div>
      <div class="bloque_interno" style="width: 100%;height: 60vh;">
        <div class="bloque_interno" style="width: 100%;height: 100%;">
          <div class="bloque_interno" style="width: 100%;height: 70%;overflow-y: scroll">
            <table style="width: 100%;">
              <thead>
                <tr>
                  <th>Tipo</th>
                  <th>A Cuenta</th>
                  <th>Días Valor (/)</th>
                  <th>Lu.-Ju.</th>
                  <th>Vi.-Sa.</th>
                  <th>Dom.</th>
                  <th>Mes</th>
                  <th>Fijos</th>
                  <th>Devengar</th>
                  <th>Deducción</th>
                  <th>&nbsp;</th>
                </tr>
              </thead>
              <tbody data-contenedor-canon-fijo-mesas>
              </tbody>
            </table>
          </div>
          <div class="bloque_interno" style="padding-top: 1em;width: 100%;">
            <button class="btn" type="button" data-js-click-agregar-canon-fijo-mesas data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]' style="display: inline-block;">
              <i class="fa fa-plus"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="pestaña" data-canon-fijo-mesas-adicionales>
      <div class="bloque_interno" style="width: 100%;height: 70vh;">
        <div class="bloque_interno" style="width: 100%;height: 100%;">
          <div class="bloque_interno" style="width: 100%;height: 70%;overflow-y: scroll">
            <table style="width: 100%;">
              <thead>
                <tr>
                  <th>Tipo</th>
                  <th>A Cuenta</th>
                  <th>Días Mes</th>
                  <th>Horas Día</th>
                  <th>Porcentaje</th>
                  <th>Devengar</th>
                  <th>Deducción</th>
                  <th>&nbsp;</th>
                </tr>
              </thead>
              <tbody data-contenedor-canon-fijo-mesas-adicionales>
              </tbody>
            </table>
          </div>
          <div class="bloque_interno" style="padding-top: 1em;width: 100%;">
            <button class="btn" type="button" data-js-click-agregar-canon-fijo-mesas-adicionales data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]' style="display: inline-block;">
              <i class="fa fa-plus"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>
  <!-- Por fuera del modal así no afecta el <form> -->
  <table hidden>
    <?php
      $obj = 'cuentas';
      $idx = '$idxcuenta';
      $N = function($name) use (&$obj,&$idx){return $obj.'['.$idx.']['.$name.']';};
      
      $nombre = $N('nombre');
      $dia_vencimiento = $N('dia_vencimiento');
      $fin_de_semana = $N('fin_de_semana');
      $interes_diario_simple = $N('interes_diario_simple');
      $interes_mensual_compuesto = $N('interes_mensual_compuesto');
    ?>
    <tr data-molde-cuenta="{{$idx}}">
      <td><input class="form-control" data-js-texto-no-formatear-numero data-name="{{$nombre}}" data-readonly='[{"modo": "VER"}]'></td>
      <td><input value="10" class="form-control" data-js-texto-no-formatear-numero data-name="{{$dia_vencimiento}}" data-readonly='[{"modo": "VER"}]'></td>
      <td>
        <select class="form-control" data-name="{{$fin_de_semana}}" data-readonly='[{"modo": "VER"}]'>
          <option default selected>Lunes Próximo</option>
          <option>Viernes Anterior</option>
          <option>Sin Movimiento</option>
        </select>
      </td>
      <td><input class="form-control" data-name="{{$interes_diario_simple}}" data-readonly='[{"modo": "VER"}]'></td>
      <td><input class="form-control" data-name="{{$interes_mensual_compuesto}}" data-readonly='[{"modo": "VER"}]'></td>
      <td>
        <button class="btn btn-link" type="button" data-js-click-borrar-tr data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>
          <i class="fa fa-fw fa-trash-alt"></i>
        </button>
      </td>
    </tr>
    <?php
      $obj = 'canon_variable';
      $idx = '$idxcv';
      
      $tipo = $N('tipo');
      $cuenta = $N('cuenta');
      $apostado_porcentaje_aplicable = $N('apostado_porcentaje_aplicable');
      $porcentaje_impuesto_ley = $N('porcentaje_impuesto_ley');
      $alicuota = $N('alicuota');
      $devengar = $N('devengar');
      $devengado_deduccion = $N('devengado_deduccion');
    ?>
    <tr data-molde-canon-variable="{{$idx}}">
      <td><input class="form-control" data-js-texto-no-formatear-numero data-name="{{$tipo}}" data-readonly='[{"modo": "VER"}]'></td>
      <td><input class="form-control" data-js-texto-no-formatear-numero data-name="{{$cuenta}}" data-readonly='[{"modo": "VER"}]'></td>
      <td><input class="form-control" data-name="{{$apostado_porcentaje_aplicable}}" data-readonly='[{"modo": "VER"}]'></td>
      <td><input class="form-control" data-name="{{$porcentaje_impuesto_ley}}" data-readonly='[{"modo": "VER"}]'></td>
      <td><input class="form-control" data-name="{{$alicuota}}" data-readonly='[{"modo": "VER"}]'></td>
      <td>
        <select class="form-control" data-name="{{$devengar}}" data-readonly='[{"modo": "VER"}]'>
          <option value="0">NO</option>
          <option value="1">SÍ</option>
        </select>
      </td>
      <td><input class="form-control" data-name="{{$devengado_deduccion}}" data-readonly='[{"modo": "VER"}]'></td>
      <td>
        <button class="btn btn-link" type="button" data-js-click-borrar-tr data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>
          <i class="fa fa-fw fa-trash-alt"></i>
        </button>
      </td>
    </tr>
    <?php
      $obj = 'canon_fijo_mesas';
      $idx = '$idxcfm';
      
      $tipo = $N('tipo');
      $cuenta = $N('cuenta');
      $dias_valor = $N('dias_valor');
      $lunes_jueves = $N('lunes_jueves');
      $viernes_sabados = $N('viernes_sabados');
      $domingos = $N('domingos');
      $mes = $N('mes');
      $fijos = $N('fijos');
      $devengar = $N('devengar');
      $devengado_deduccion = $N('devengado_deduccion');
    ?>
    <tr data-molde-canon-fijo-mesas="{{$idx}}">      
      <td><input value="Diarias" data-name="{{$tipo}}" class="form-control" data-js-texto-no-formatear-numero  data-readonly='[{"modo": "VER"}]'></td>
      <td><input value="" data-name="{{$cuenta}}" class="form-control" data-js-texto-no-formatear-numero  data-readonly='[{"modo": "VER"}]'></td>
      <td><input value="30" data-name="{{$dias_valor}}" class="form-control" data-readonly='[{"modo": "VER"}]'></td>
      <td>
        <select value="1" class="form-control" data-name="{{$lunes_jueves}}" data-readonly='[{"modo": "VER"}]'>
          <option value="0">NO</option>
          <option value="1" default selected>SÍ</option>
        </select>
      </td>
      <td>
        <select value="1" class="form-control" data-name="{{$viernes_sabados}}" data-readonly='[{"modo": "VER"}]'>
          <option value="0">NO</option>
          <option value="1" default selected>SÍ</option>
        </select>
      </td>
      <td>
        <select value="1" class="form-control" data-name="{{$domingos}}" data-readonly='[{"modo": "VER"}]'>
          <option value="0">NO</option>
          <option value="1" default selected>SÍ</option>
        </select>
      </td>
      <td>
        <select value="1" class="form-control" data-name="{{$mes}}" data-readonly='[{"modo": "VER"}]'>
          <option value="0">NO</option>
          <option value="1" default selected>SÍ</option>
        </select>
      </td>
      <td><input value="0" data-name="{{$fijos}}" class="form-control" data-readonly='[{"modo": "VER"}]'></td>
      <td>
        <select value="1" class="form-control" data-name="{{$devengar}}" data-readonly='[{"modo": "VER"}]'>
          <option value="0">NO</option>
          <option value="1">SÍ</option>
        </select>
      </td>
      <td><input value="0" data-name="{{$devengado_deduccion}}" class="form-control" data-readonly='[{"modo": "VER"}]'></td>
      <td>
        <button class="btn btn-link" type="button" data-js-click-borrar-tr data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>
          <i class="fa fa-fw fa-trash-alt"></i>
        </button>
      </td>
    </tr>
    
    <?php
      $obj = 'canon_fijo_mesas_adicionales';
      $idx = '$idxcfma';
      
      $tipo = $N('tipo');
      $cuenta = $N('cuenta');
      $dias_mes = $N('dias_mes');
      $horas_dia = $N('horas_dia');
      $porcentaje = $N('porcentaje');
      $devengar = $N('devengar');
      $devengado_deduccion = $N('devengado_deduccion');
    ?>
    <tr data-molde-canon-fijo-mesas-adicionales="{{$idx}}">
      <td><input data-name="{{$tipo}}" class="form-control" data-js-texto-no-formatear-numero  data-readonly='[{"modo": "VER"}]'></td>
      <td><input data-name="{{$cuenta}}" class="form-control" data-js-texto-no-formatear-numero  data-readonly='[{"modo": "VER"}]'></td>
      <td><input value="30" data-name="{{$dias_mes}}" class="form-control" data-readonly='[{"modo": "VER"}]'></td>
      <td><input data-name="{{$horas_dia}}" class="form-control"   data-readonly='[{"modo": "VER"}]'></td>
      <td><input value="100" data-name="{{$porcentaje}}" class="form-control"   data-readonly='[{"modo": "VER"}]'></td>
      <td>
        <select value="0" class="form-control" data-name="{{$devengar}}" data-readonly='[{"modo": "VER"}]'>
          <option value="0" default selected>NO</option>
          <option value="1">SÍ</option>
        </select>
      </td>
      <td><input value="0" data-name="{{$devengado_deduccion}}" class="form-control" data-readonly='[{"modo": "VER"}]'></td>
      <td>
        <button class="btn btn-link" type="button" data-js-click-borrar-tr data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>
          <i class="fa fa-fw fa-trash-alt"></i>
        </button>
      </td>
    </tr>
  </table>
  @endslot
  @slot('pie')
  @if($permisos('canon_operador_cargar'))
  <button class="btn btn-successAceptar" type="button" data-js-click-submit-form="form" data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>GUARDAR</button>
  @endif
  @endslot
@endcomponent

@endif

@if($permisos('canon_agrupamiento_ver') || $permisos('canon_agrupamiento_cargar'))

@component('Components/modal',[
  'clases_modal' => 'VerCargarCanon VerCargarOperador',
  'attrs_modal' => 'data-js-modal-canon-comportamiento-comun data-js-modal-ver-cargar-grupo-operador data-modo=\'NUEVO\' ',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 70,
])
  @slot('titulo')
  GRUPO OPERADOR
  @endslot
  @slot('cuerpo')
  <div style="width: 100%;display: flex;align-items: center;justify-content: flex-end;padding-bottom: 1em;" data-modo-mostrar='[{"modo": "VER"}]'>
    <h5 style="width: 15rem;">Version</h5>
    <select class="form-control" data-js-select-historial style="width: 15rem;">
    </select>
  </div>
  <form class="pestaña" style="display: flex;flex-direction: column;" action="/canon/grupo_operador/guardar" method="POST">
    <div hidden>
      <input name="id_canon_grupo_operador" class="form-control" data-readonly='[{"modo":"*"}]'>
    </div>
    <div class="bloque_enterno" style="display:flex;height: 70vh;width: 100%;">
      <div class="bloque_interno"  style="flex: 1;height: 100%;display: flex;flex-direction: column;">
        <div style="flex: 3;">
          <h6>Datos</h6>
          <div class="bloque_interno" style="display: flex;flex-direction: row;flex-wrap: wrap;justify-content: center;align-items: center;align-content: stretch;gap: 10px;">
            <div style="width: 33.333333333%;">
              <h5 style="padding-left: 0;">ID</h5>
              <!-- El id no se deberia editar una vez creado para mantener la trazabilidad/historial del g operador -->
              <input name="id_grupo_operador" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"},{"modo": "EDITAR"}]'>
              <input name="es_individual" data-js-texto-no-formatear-numero class="form-control" data-check-param data-readonly='[{"modo":"*"}]' data-modo-mostrar='[{"modo": "NOMOSTRARNUNCA!"}]'>
            </div>
            <div style="width: 33.333333333%;">
              <h5 style="padding-left: 0;">Nombre</h5>
              <input name="nombre" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"},{"es_individual": "1"}]'>
            </div>
            <div style="width: 33.333333333%;">
              <h5 style="padding-left: 0;">Código</h5>
              <input name="codigo" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"},{"es_individual": "1"}]'>
            </div>
            <div style="width: 33.333333333%;">
              <h5 style="padding-left: 0;">Abreviación</h5>
              <input name="abbr" data-js-texto-no-formatear-numero class="form-control" data-readonly='[{"modo": "VER"},{"es_individual": "1"}]'>
            </div>
            <div style="width: 33.333333333%;">
              <h5 style="padding-left: 0;">Color plantillas</h5>
              <input data-js-change-agregar-alpha="" name="color" data-js-texto-no-formatear-numero type="color" value="#000000FF" alpha="alpha" colorspace="limited-srgb" class="form-control" data-readonly='[{"modo": "VER"},{"es_individual": "1"}]'>
            </div>
          </div>
        </div>
      </div>
      <div class="bloque_interno" style="flex: 1;height: 100%;">
        <h6 style="padding-bottom: 1em;width: 100%;">
          Operadores
          <button class="btn" type="button" data-js-click-agregar-operador data-modo-mostrar='[{"modo": "NUEVO","es_individual": "0"},{"modo": "EDITAR","es_individual": "0"}]' style="display: inline-block;">
            <i class="fa fa-plus"></i>
          </button>
        </h6>
        <div class="bloque_interno" style="width: 100%;height: 70%;overflow-y: scroll">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>&nbsp;</th>
              </tr>
            </thead>
            <tbody data-contenedor-operadores>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </form>
  <!-- Por fuera del modal así no afecta el <form> -->
  <table hidden>
    <tr data-molde-operador="$idxo">
      <td><input class="form-control" data-js-texto-no-formatear-numero data-name="operadores[$idxo][id_operador]" data-readonly='[{"modo": "VER"},{"es_individual": "1"}]'></td>
      <td>
        <button class="btn btn-link" type="button" data-js-click-borrar-tr data-modo-mostrar='[{"modo": "NUEVO","es_individual": "0"},{"modo": "EDITAR","es_individual": "0"}]'>
          <i class="fa fa-fw fa-trash-alt"></i>
        </button>
      </td>
    </tr>
  </table>
  @endslot
  @slot('pie')
  @if($permisos('canon_agrupamiento_cargar'))
  <button class="btn btn-successAceptar" type="button" data-js-click-submit-form="form" data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>GUARDAR</button>
  @endif
  @endslot
@endcomponent

@component('Canon.modalAgrupamientos',['subcanons' => $subcanons])
@endcomponent

@endif

@if(
     $permisos('canon_cargar') || $permisos('canon_operador_cargar') || $permisos('canon_agrupamiento_cargar')
  || $permisos('canon_deseliminar') || $permisos('canon_operador_deseliminar') || $permisos('canon_agrupamiento_deseliminar')
  || $permisos('canon_permiso_eliminar')
)

@component('Components/modalEliminar')
@endcomponent

@endif

@if($permisos('canon_cargar'))

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

@endif

<meta name="_token" content="{!! csrf_token() !!}" />

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">Canon</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <p>
  En esta sección puede cargar y calcular en la base de datos las recaudaciones mensuales de cada operador
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
  <script src="/js/Canon/index.js?5" charset="utf-8" type="module"></script>

@endsection
