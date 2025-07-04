<?php
  $molde_str = '$'.uniqid();
  $molde_str_diario = '$'.uniqid();
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
  
  $nd = function($s,$mstr = null,$mstrd = null) use (&$molde_str,&$molde_str_diario){
    $mstr = $mstr ?? $molde_str;
    $mstrd = $mstrd ?? $molde_str_diario;
    return "canon_fijo_mesas[$mstr][diario][$mstrd][$s]";
  };
?>
<div class="bloque_interno" style="width: 100%;" data-js-molde="{{$molde_str}}" data-subcanon-tipo>
  <input data-tipo data-js-texto-no-formatear-numero data-name="{{$n('tipo')}}" hidden>
  <input data-name="{{$n('id_canon_fijo_mesas')}}" hidden>
  <div class="bloque_interno"  style="width: 100%;display: flex;align-items: center;">
    @include('Canon.ModalCanon.toggleMensualDiario')
  </div>
  <div class="bloque_interno">
    <div style="display: flex;width: 100%;">
      <div class="valor_intermedio">
        <h5>MESAS×DIAS</h5>
        <input class="form-control" data-name="{{$mesas_dias}}" data-depende="{{$dias_lunes_jueves}},{{$mesas_lunes_jueves}},{{$dias_viernes_sabados}},{{$mesas_viernes_sabados}},{{$dias_domingos}},{{$mesas_domingos}},{{$dias_todos}},{{$mesas_todos}},{{$dias_fijos}},{{$mesas_fijos}}" data-readonly='[{"modo":"*"}]'>
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
        <input class="form-control" data-name="{{$factor_dias_valor}}" data-depende="{{$dias_valor}}" data-readonly='[{"modo":"*"}]'>
      </div>
    </div>
    <br>
    <div style="display: flex;width: 100%;">
      <div style="display: flex;width: 100%;" data-mensual-diario="mensual">
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
          <input class="form-control" style="visibility: hidden;">
        </div>
        <div>
          <h5>BRUTO</h5>
          <input class="form-control" data-name="{{$bruto}}" data-depende="id_casino,año_mes" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
      </div>
    </div>
    <div style="display: flex;width: 100%;">
      <div style="width: 100%;" data-mensual-diario="diario">
        <div data-div-devengado="header" data-div-determinado="header" class="col-md-12">
          <table class="table table-bordered" style="margin-bottom: 0;">
            @section('colgroupCF')
            <colgroup>
              <col style="width: 5%;">
              <col style="width: 6.25%;">
              <col style="width: 6.25%;">
              <col style="width: 20%;">
              <col style="width: 6.25%;">
              <col style="width: 20%;">
              <col style="width: 10%;">
              <col style="width: 5%;">
              <col style="width: 20%;">
            </colgroup>
            @endsection
            @yield('colgroupCF')
            <thead>
              <tr>
                <th style="text-align: center;">Día</th>
                <th style="text-align: center;">Mesas Habilitadas</th>
                <th style="text-align: center;">Mesas Usadas ARS</th>
                <th style="text-align: center;">Bruto ARS</th>
                <th style="text-align: center;">Mesas Usadas USD</th>
                <th style="text-align: center;">Bruto USD</th>
                <th style="text-align: center;">Cotización</th>
                <th style="text-align: center;">Mesas Usadas</th>
                <th style="text-align: center;">Bruto</th>
              </tr>
            </thead>
          </table>
        </div>
        <div data-div-devengado="diario" data-div-determinado="diario" class="col-md-12" style="max-height: 25vh;overflow-y: scroll;">
          <table data-tabla-diario class="sacar-borde-primer-tr table table-bordered" style="margin-bottom: 0;">
            @yield('colgroupCF')
            <tbody>
            </tbody>
          </table>
          <table hidden>
            <tr data-molde-diario>
              <td><input class="form-control" value="dia" readonly></td>
              <td><input class="form-control" value="mesas_habilitadas" readonly></td>
              <td><input class="form-control" value="mesas_usadas_ars"></td>
              <td><input class="form-control" value="bruto_ars"></td>
              <td><input class="form-control" value="mesas_usadas_usd"></td>
              <td><input class="form-control" value="bruto_usd"></td>
              <td><input class="form-control" value="cotizacion" readonly></td>
              <td><input class="form-control" value="mesas_usadas" readonly></td>
              <td><input class="form-control" value="bruto" readonly></td>
            </tr>
          </table>
        </div>
        <div data-div-devengado="mensual" data-div-determinado="mensual" class="col-md-12">
          <table data-tabla-mensual class="sacar-borde-primer-tr table table-bordered">
            @yield('colgroupCF')
            <tbody>
              <tr class="fila-mensual">
                <td>&nbsp;</td>
                <td><input class="form-control" value="mesas_habilitadas" readonly></td>
                <td><input class="form-control" value="mesas_usadas_ars" readonly></td>
                <td><input class="form-control" value="bruto_ars" readonly></td>
                <td><input class="form-control" value="mesas_usadas_usd" readonly></td>
                <td><input class="form-control" value="bruto_usd" readonly></td>
                <td><input class="form-control" value="cotizacion" readonly></td>
                <td><input class="form-control" value="mesas_usadas" readonly></td>
                <td><input class="form-control" value="bruto" readonly></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div style="display: flex;width: 100%;">
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
          <input class="form-control" data-name="{{$devengado_deduccion}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
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
          <input class="form-control" data-name="{{$determinado_ajuste}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
      </div>
      <div style="display: flex;">
        <div>
          <h5>DETERMINADO</h5>
          <input class="form-control" data-name="{{$determinado}}" data-depende="{{$determinado_total}},{{$determinado_ajuste}}" data-readonly='[{"modo":"*"}]'>
        </div>
      </div>
    </div>
  </div>
</div>
