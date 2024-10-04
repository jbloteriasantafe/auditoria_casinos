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
    <div>
      <a data-js-tab="#pant_defecto">Valores por Defecto</a>
    </div>
  </div>
</div>

<div id="pant_canon" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    CANON
    <button class="btn" type="button" data-js-nuevo-canon="/Ncanon/obtener">NUEVO</button>
    @endslot
    
    @slot('target_buscar')
    /Ncanon/buscar
    @endslot
    
    @slot('filtros')
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>AÑO MES</th>
      <th>CASINO</th>
      <th>ESTADO</th>
      <th>A PAGAR</th>
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
      <td class="estado">ESTADO</td>
      <td class="a_pagar">A PAGAR</td>
      <td class="pago">PAGO</td>
      <td class="diferencia">DIFERENCIA</td>
      <td class="saldo_posterior">SALDO</td>
      <td>
        <button class="btn" type="button" data-js-cambiar-estado="/Ncanon/cambiarEstado?estado=Pagado" data-estado-visible="GENERADO" title="CONFIRMAR PAGO"><i class="fa fa-fw fa-check"></i></button>
        <button class="btn" type="button" data-js-adjuntar="/Ncanon/adjuntar" data-estado-visible="PAGADO" title="ADJUNTAR"><i class="fa fa-fw fa-paperclip"></i></button>
        <button class="btn" type="button" data-js-ver="/Ncanon/obtenerConHistorial" title="VER/HISTORIAL"><i class="fa fa-fw fa-search-plus"></i></button>
        <button class="btn" type="button" data-js-editar="/Ncanon/obtener" data-estado-visible="GENERADO"  title="EDITAR"><i class="fas fa-fw fa-pencil-alt"></i></button>
        <button class="btn" type="button" data-js-borrar="/Ncanon/borrar" data-table-id="id_canon" title="BORRAR"><i class="fa fa-fw fa-trash-alt"></i></button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<div id="pant_defecto" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    <div>VALORES POR DEFECTO</div>
    <form style="display: flex;">
      <input class="form-control" name="campo" placeholder="Campo" style="flex: 1;">
      <div data-js-nuevo-jsoneditor style="flex: 2;"></div>
      <div style="flex: 1;">
        <button class="btn" type="button" data-js-guardar-nuevo="/Ncanon/valoresPorDefecto/ingresar">GUARDAR</button>
      </div>
    </form>
    @endslot
    
    @slot('target_buscar')
    /Ncanon/valoresPorDefecto
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
        <button class="btn" type="button" data-js-guardar="/Ncanon/valoresPorDefecto/ingresar" title="GUARDAR"><i class="fa fa-fw fa-check"></i></button>
        <button class="btn" type="button" data-js-borrar="/Ncanon/valoresPorDefecto/borrar" data-table-id="id_canon_valor_por_defecto" title="BORRAR"><i class="fa fa-fw fa-trash-alt"></i></button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<style>
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
    width: 5rem;
    text-align: center;
    font-family: monospace, monospace;
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
</style>

@component('Components/modal',[
  'clases_modal' => 'VerCargarCanon',
  'attrs_modal' => 'data-js-modal-ver-cargar-canon',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 90,
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
  <form style="display: flex;flex-direction: column;" data-css-id_casino="" data-js-recalcular="/Ncanon/recalcular">
    <div style="width: 100%;display: flex;">
      <div>
        <h5>AÑO MES</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="año_mes" placeholder="AÑO MES" data-js-empty-si-cambio="[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"',
          'attrs_dtp' => 'data-date-format="yyyy-mm-01" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "EDITAR"}]\' style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      <div>
        <h5>Casino</h5>
        <select class="form-control" name="id_casino"
          data-js-empty-si-cambio="[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"
          data-readonly='[{"modo": "VER"},{"modo": "EDITAR"}]'>
          <option value="" selected>- SELECCIONE -</option>
          @foreach($casinos as $c)
          <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div>
        <h5>Estado</h5>
        <input class="form-control" name="estado" data-readonly='[{}]'>
      </div>
      <div>
        <h5>ANTIGUO</h5>
        <select class="form-control" name="es_antiguo"
          data-js-empty-si-cambio="[data-canon-variable] [data-js-contenedor],[data-canon-fijo-mesas] [data-js-contenedor],[data-canon-fijo-mesas-adicionales] [data-js-contenedor]"
          data-readonly='[{"modo": "VER"}]'>
          <option value="0" selected>NO</option>
          <option value="1">SI</option>
        </select>      
      </div>
      <div data-modo-mostrar="">
        <input name="id_canon" class="form-control" data-readonly='[{}]'>
      </div>
    </div>
    <div class="datos_numericos" style="height: 70vh;overflow-y: scroll;">
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
      <div class="pestaña" data-total>
        <div style="width: 100%;display: flex;">
          <div>
            <h5>Bruto (DEVENGADO)</h5>
            <input class="form-control" name="bruto_devengado" data-readonly='[{"modo": "VER"},{"es_antiguo": 0}]'>
          </div>
          <div>
            <h5>Deducción</h5>
            <input class="form-control" name="deduccion" data-readonly='[{"modo": "VER"}]'>
          </div>
          <div>
            <h5>Devengado</h5>
            <input class="form-control" name="devengado" data-depende="bruto_devengado,deduccion" data-readonly="[{}]">
          </div>
          <div class="parametro_chico">
            <h5>Porcentaje Seguridad</h5>
            <input class="form-control" name="porcentaje_seguridad" data-depende="deduccion,bruto_devengado" data-readonly="[{}]">
          </div>
        </div>
        <div style="width: 100%;display: flex;">
          <div>
            <h5>F. Vencimiento</h5>
            @component('Components/inputFecha',[
              'attrs' => "name='fecha_vencimiento' data-depende='año_mes'",
              'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"}]\' style="padding: 0 !important;"'
            ])
            @endcomponent
          </div>
          <div>
            <h5>F. Pago</h5>
            @component('Components/inputFecha',[
              'attrs' => "name='fecha_pago' data-depende='año_mes'",
              'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"}]\' style="padding: 0 !important;"'
            ])
            @endcomponent
          </div>
        </div>
        <div style="width: 100%;display: flex;">
          <div>
            <h5>Bruto (A PAGAR)</h5>
            <input class="form-control" name="bruto_pagar" data-readonly='[{"modo": "VER"},{"es_antiguo": 0}]'>
          </div>
          <div>
            <h5>Interes Mora</h5>
            <input class="form-control" name="interes_mora" data-depende="a_pagar,mora,fecha_pago,fecha_vencimiento" data-readonly='[{"modo": "VER"}]'>
          </div>
          <div>
            <h5>Mora</h5>
            <input class="form-control" name="mora" data-depende="interes_mora,a_pagar,fecha_pago,fecha_vencimiento" data-readonly='[{"modo": "VER"}]'>
          </div>
          <div>
            <h5>A PAGAR</h5>
            <input class="form-control" name="a_pagar" data-depende="interes_mora,mora,fecha_pago,fecha_vencimiento" data-readonly='[{"modo": "VER"}]'>
          </div>
        </div>
        <div style="width: 100%;display: flex;">
          <div>
            <h5>PAGO</h5>
            <input class="form-control" name="pago" data-readonly='[{"modo": "VER"}]'>
          </div>
          <div>
            <h5>Diferencia</h5>
            <input class="form-control" name="diferencia" data-depende="bruto_pagar,pago" data-readonly='[{}]'>
          </div>
          <div>
            <h5>Saldo anterior</h5>
            <input class="form-control" name="saldo_anterior" data-readonly='[{}]'>
          </div>
          <div>
            <h5>Saldo posterior</h5>
            <input class="form-control" name="saldo_posterior" data-depende="diferencia,saldo_anterior" data-readonly='[{}]'>
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
          $apostado_sistema = $n('apostado_sistema');
          $apostado_informado = $n('apostado_informado');
          $apostado_porcentaje_aplicable = $n('apostado_porcentaje_aplicable');
          $base_imponible_devengado = $n('base_imponible_devengado');
          $base_imponible_pagar = $n('base_imponible_pagar');
          $apostado_porcentaje_impuesto_ley = $n('apostado_porcentaje_impuesto_ley');
          $impuesto_devengado = $n('impuesto_devengado');
          $impuesto_pagar = $n('impuesto_pagar');
          $bruto = $n('bruto');
          $subtotal_devengado = $n('subtotal_devengado');
          $subtotal_pagar = $n('subtotal_pagar');
          $alicuota = $n('alicuota');
          $total_devengado = $n('total_devengado');
          $total_pagar = $n('total_pagar');
        ?>
        <div data-js-molde="{{$molde_str}}" hidden>
          <div style="width: 50%;" >
            <h6 data-titulo>TITULO CANON VARIABLE<h6>
            <div style="display: flex;">
              <div style="flex: 1;">
                <h5>&nbsp;</h5>
                <input class="form-control" style="opacity: 0;">
              </div>
              <div style="flex: 1;">
                <h5>APOSTADO SISTEMA</h5>
                <input class="form-control" data-name="{{$apostado_sistema}}" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="flex: 1;">
                <h5>APOSTADO INFORMADO</h5>
                <input class="form-control" data-name="{{$apostado_informado}}" data-readonly='[{"modo": "VER"}]'>
              </div>
            </div>
            <div style="display: flex;">
              <div class="parametro_chico"  style="flex: 1;">
                <h5>APLICABLE (%)</h5>
                <input class="form-control" data-name="{{$apostado_porcentaje_aplicable}}" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="flex: 1;">
                <h5>BASE IMPONIBLE (DEVENGADO)</h5>
                <input class="form-control" data-name="{{$base_imponible_devengado}}" data-depende="{{$apostado_sistema}},{{$apostado_porcentaje_aplicable}}" data-readonly='[{}]'>
              </div>
              <div style="flex: 1;">
                <h5>BASE IMPONIBLE (A PAGAR)</h5>
                <input class="form-control" data-name="{{$base_imponible_pagar}}" data-depende="{{$apostado_informado}},{{$apostado_porcentaje_aplicable}}" data-readonly='[{}]'>
              </div>
            </div>
            <div style="display: flex;">
              <div class="parametro_chico" style="flex: 1;">
                <h5>IMPUESTO LEY (%)</h5>
                <input class="form-control" data-name="{{$apostado_porcentaje_impuesto_ley}}" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="flex: 1;">
                <h5>IMPUESTO (DEVENGADO)</h5>
                <input class="form-control" data-name="{{$impuesto_devengado}}" data-depende="{{$base_imponible_devengado}},{{$apostado_porcentaje_impuesto_ley}}" data-readonly='[{}]'>
              </div>
              <div style="flex: 1;">
                <h5>IMPUESTO (A PAGAR)</h5>
                <input class="form-control" data-name="{{$impuesto_pagar}}" data-depende="{{$base_imponible_pagar}},{{$apostado_porcentaje_impuesto_ley}}" data-readonly='[{}]'>
              </div>
            </div>
            <div style="display: flex;">
              <div style="flex: 1;">
                <h5>BRUTO</h5>
                <input class="form-control" data-name="{{$bruto}}" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="flex: 1;">
                <h5>SUBTOTAL (DEVENGADO)</h5>
                <input class="form-control" data-name="{{$subtotal_devengado}}" data-depende="{{$bruto}},{{$impuesto_devengado}}" data-readonly='[{}]'>
              </div>
              <div style="flex: 1;">
                <h5>SUBTOTAL (A PAGAR)</h5>
                <input class="form-control" data-name="{{$subtotal_pagar}}" data-depende="{{$bruto}},{{$impuesto_pagar}}" data-readonly='[{}]'>
              </div>
            </div>
            <div style="display: flex;">
              <div class="parametro_chico" style="flex: 1;">
                <h5>ALICUOTA (%)</h5>
                <input class="form-control" data-name="{{$alicuota}}" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
              </div>
              <div style="flex: 1;">
                <h5>TOTAL (DEVENGADO)</h5>
                <input class="form-control" data-name="{{$total_devengado}}" data-depende="{{$subtotal_devengado}},{{$alicuota}}" data-readonly='[{}]'>
              </div>
              <div style="flex: 1;">
                <h5>TOTAL (A PAGAR)</h5>
                <input class="form-control" data-name="{{$total_pagar}}" data-depende="{{$subtotal_pagar}},{{$alicuota}}" data-readonly='[{}]'>
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
          $fecha_cotizacion = $n('fecha_cotizacion');
          $cotizacion_dolar = $n('cotizacion_dolar');
          $cotizacion_euro  = $n('cotizacion_euro');
          $valor_dolar = $n('valor_dolar');
          $valor_euro  = $n('valor_euro');
          $dias_valor = $n('dias_valor');
          $valor_diario_dolar = $n('valor_diario_dolar');
          $valor_diario_euro  = $n('valor_diario_euro');
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
          $total_dolar     = $n('total_dolar');
          $total_euro      = $n('total_euro');
          $total_devengado = $n('total_devengado');
          $total_pagar     = $n('total_pagar');
        ?>
        <div style="width: 100%;" data-js-molde="{{$molde_str}}" hidden>
          <h6 data-titulo>TITULO MESAS</h6>
          <div style="display: flex;">
            <div>
              <h5>F. COTIZACIÓN</h5>
              @component('Components/inputFecha',[
                'attrs' => "data-name='$fecha_cotizacion' data-depende='año_mes'",
                'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"}]\' style="padding: 0 !important;"'
              ])
              @endcomponent
            </div>
            <div>
              <h5>COTIZACIÓN DOLAR</h5>
              <input class="form-control" data-name="{{$cotizacion_dolar}}" data-depende="{{$fecha_cotizacion}}" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div>
              <h5>COTIZACIÓN EURO</h5>
              <input class="form-control" data-name="{{$cotizacion_euro}}" data-depende="{{$fecha_cotizacion}}" data-readonly='[{"modo": "VER"}]'>
            </div>
          </div>
          <div style="display: flex;">
            <div>
              <h5>VALOR DOLAR</h5>
              <input class="form-control" data-name="{{$valor_dolar}}" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div>
              <h5>VALOR EURO</h5>
              <input class="form-control" data-name="{{$valor_euro}}" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div class="parametro_chico">
              <h5>DIAS VALOR</h5>
              <input class="form-control" data-name="{{$dias_valor}}" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div>
              <h5>VALOR DIARIO DOLAR</h5>
              <input class="form-control" data-name="{{$valor_diario_dolar}}" data-depende="{{$cotizacion_dolar}},{{$valor_dolar}},{{$dias_valor}}" data-readonly='[{}]'>
            </div>
            <div>
              <h5>VALOR DIARIO EURO</h5>
              <input class="form-control" data-name="{{$valor_diario_euro}}" data-depende="{{$cotizacion_euro}},{{$valor_euro}},{{$dias_valor}}" data-readonly='[{}]'>
            </div>
          </div>
          <div style="display: flex;">
            <div>
              <h5>DIAS-MESAS L-J</h5>
              <div style="display: flex;flex-direction: column;border: 1px solid grey;">
                <input class="form-control" data-name="{{$dias_lunes_jueves}}" placeholder="DIAS" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"}]'>
                <input class="form-control" data-name="{{$mesas_lunes_jueves}}" placeholder="MESAS" data-readonly='[{"modo": "VER"}]'>
              </div>
            </div>
            <div>
              <h5>DIAS-MESAS V-S</h5>
              <div style="display: flex;flex-direction: column;border: 1px solid grey;">
                <input class="form-control" data-name="{{$dias_viernes_sabados}}" placeholder="DIAS" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"}]'>
                <input class="form-control" data-name="{{$mesas_viernes_sabados}}" placeholder="MESAS" data-readonly='[{"modo": "VER"}]'>
              </div>
            </div>               
            <div>
              <h5>DIAS-MESAS Dom</h5>
              <div style="display: flex;flex-direction: column;border: 1px solid grey;">
                <input class="form-control" data-name="{{$dias_domingos}}" placeholder="DIAS" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"}]'>
                <input class="form-control" data-name="{{$mesas_domingos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"}]'>
              </div>
            </div>
            <div>
              <h5>DIAS-MESAS Todos</h5>
              <div style="display: flex;flex-direction: column;border: 1px solid grey;">
                <input class="form-control" data-name="{{$dias_todos}}" placeholder="DIAS" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"}]'>
                <input class="form-control" data-name="{{$mesas_todos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"}]'>
              </div>
            </div>
            <div>
              <h5>DIAS-MESAS Fijos</h5>
              <div style="display: flex;flex-direction: column;border: 1px solid grey;">
                <input class="form-control" data-name="{{$dias_fijos}}" placeholder="DIAS" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
                <input class="form-control" data-name="{{$mesas_fijos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"}]'>
              </div>
            </div>
          </div>
          <div style="display: flex;">
            <div>
              <h5>TOTAL DOLAR</h5>
              <input class="form-control" data-name="{{$total_dolar}}" data-depende="{{$valor_diario_dolar}},{{$dias_lunes_jueves}},{{$mesas_lunes_jueves}},{{$dias_viernes_sabados}},{{$mesas_viernes_sabados}},{{$dias_domingos}},{{$mesas_domingos}},{{$dias_todos}},{{$mesas_todos}},{{$dias_fijos}},{{$mesas_fijos}}" data-readonly='[{}]'>
            </div>
            <div>
              <h5>TOTAL EURO</h5>
              <input class="form-control" data-name="{{$total_euro}}" data-depende="{{$valor_diario_euro}},{{$dias_lunes_jueves}},{{$mesas_lunes_jueves}},{{$dias_viernes_sabados}},{{$mesas_viernes_sabados}},{{$dias_domingos}},{{$mesas_domingos}},{{$dias_todos}},{{$mesas_todos}},{{$dias_fijos}},{{$mesas_fijos}}" data-readonly='[{}]'>
            </div>
            <div>
              <h5>TOTAL (DEVENGADO)</h5>
              <input class="form-control" data-name="{{$total_devengado}}" data-depende="{{$total_dolar}},{{$total_euro}}" data-readonly='[{}]'>
            </div>
            <div>
              <h5>TOTAL (A PAGAR)</h5>
              <input class="form-control" data-name="{{$total_pagar}}" data-depende="{{$total_dolar}},{{$total_euro}}" data-readonly='[{}]'>
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
          $valor_mensual = $n('valor_mensual');
          $dias_mes = $n('dias_mes');
          $valor_diario = $n('valor_diario');
          $horas_dia = $n('horas_dia');
          $valor_hora = $n('valor_hora');
          $horas = $n('horas');
          $mesas = $n('mesas');
          $porcentaje = $n('porcentaje');
          $total_devengado = $n('total_devengado');
          $total_pagar = $n('total_pagar');
        ?>
        <div data-js-molde="{{$molde_str}}" hidden>
          <h4 data-titulo>TITULO MESA ADICIONAL</h4>
          <div style="display: flex;">
            <div>
              <h5>VALOR MENSUAL</h5>
              <input class="form-control" data-name="{{$valor_mensual}}" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div class="parametro_chico">
              <h5>DIAS MES</h5>
              <input class="form-control" data-name="{{$dias_mes}}" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div>
              <h5>VALOR DIARIO</h5>
              <input class="form-control" data-name="{{$valor_diario}}" data-depende="{{$valor_mensual}},{{$dias_mes}}" data-readonly='[{}]'>
            </div>
            <div class="parametro_chico">
              <h5>HORAS DÍAS</h5>
              <input class="form-control" data-name="{{$horas_dia}}"  data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div>
              <h5>VALOR HORA</h5>
              <input class="form-control" data-name="{{$valor_hora}}" data-depende="{{$valor_diario}},{{$horas_dia}}" data-readonly='[{}]'>
            </div>
          </div>
          <div style="display: flex;">
            <div>
              <h5>HORAS</h5>
              <input class="form-control" data-name="{{$horas}}" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div>
              <h5>MESAS</h5>
              <input class="form-control" data-name="{{$mesas}}" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div class="parametro_chico">
              <h5>PORCENTAJE</h5>
              <input class="form-control" data-name="{{$porcentaje}}" data-depende="id_casino" data-readonly='[{"modo": "VER"}]'>
            </div>
          </div>
          <div style="display: flex;">
            <div>
              <h5>TOTAL (DEVENGADO)</h5>
              <input class="form-control" data-name="{{$total_devengado}}" data-depende="{{$horas}},{{$mesas}},{{$porcentaje}}" data-readonly='[{}]'>
            </div>
            <div>
              <h5>TOTAL (PAGAR)</h5>
              <input class="form-control" data-name="{{$total_pagar}}" data-depende="{{$horas}},{{$mesas}},{{$porcentaje}}" data-readonly='[{}]'>
            </div>
          </div>
        </div>
      </div>
      <div class="pestaña" data-adjuntos>
        <div class="solo_mostrar_h5_del_primero" style="width: 100%;" data-js-contenedor>
        </div>
        <hr>
        <div style="width: 100%;display: flex;" data-modo-mostrar="NUEVO,EDITAR" data-adjunto>
          <input class="form-control" placeholder="DESCRIPCIÓN" style="flex: 1;text-align: left;" data-descripcion>
          <input class="form-control" type="file" style="flex: 1;text-align: center;" data-archivo>
          <button class="btn" type="button" data-js-agregar-adjunto><i class="fa fa-plus"></i></button>
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
        <div data-js-molde="{{$molde_str}}" data-adjunto hidden>
          <div style="display: flex;">
            <div style="flex: 1;">
              <h5>DESCRIPCIÓN</h5>
              <input style="width: 100%;text-align: left;" class="form-control" data-name="{{$descripcion}}" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"}]'>
            </div>
            <div style="flex: 1;" data-modo-mostrar="VER,NUEVO,EDITAR">
              <h5>NOMBRE ARCHIVO</h5>
              <input data-js-click-abrir-val-hermano="[data-es-link]" style="width: 100%;text-align: center;cursor: pointer;" class="form-control" data-name="{{$nombre_archivo}}" data-depende="id_casino,año_mes" data-readonly='[{}]'>
              <input data-es-link data-name="{{$link}}" data-modo-mostrar="">
            </div>
            <div data-modo-mostrar="">
              <h5>&nbsp;</h5>
              <input style="flex: 1;" class="form-control" data-name="{{$id_archivo}}" data-depende="id_casino,año_mes" data-readonly='[{}]'>
            </div>
            <div data-modo-mostrar="NUEVO,EDITAR">
              <h5>&nbsp;</h5>
              <button class="btn" type="button" data-js-borrar-adjunto><i class="fa fa-fw fa-trash-alt"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
  <button class="btn btn-successAceptar" type="button" data-js-enviar="/Ncanon/guardar" data-modo-mostrar="NUEVO,EDITAR">GUARDAR</button>
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
  <script src="/js/Canon/ncanon.js" charset="utf-8" type="module"></script>

@endsection
