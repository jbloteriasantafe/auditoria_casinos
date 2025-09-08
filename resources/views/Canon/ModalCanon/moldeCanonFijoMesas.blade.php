<?php
  $molde_str = '$'.uniqid();
  
  foreach([
    'tipo','id_canon_fijo_mesas',
    'dias_valor','factor_dias_valor','valor_dolar_diario','valor_euro_diario',
    'bruto',
    'dias_lunes_jueves','mesas_lunes_jueves',
    'dias_viernes_sabados','mesas_viernes_sabados',
    'dias_domingos','mesas_domingos',
    'dias_todos','mesas_todos',
    'dias_fijos','mesas_fijos',
    'mesas_dias','factor_ajuste_diario_fijas',
    'mesas_habilitadas_acumuladas',
    'devengar',
    'devengado_valor_dolar_cotizado','devengado_valor_euro_cotizado',
    'devengado_valor_dolar_diario_cotizado','devengado_valor_euro_diario_cotizado',
    'devengado_valor','devengado_valor_diario',
    'devengado_total_dolar_cotizado','devengado_total_euro_cotizado',
    'devengado_total','devengado_deduccion','devengado',
    'determinado_valor_dolar_cotizado','determinado_valor_euro_cotizado',
    'determinado_valor_dolar_diario_cotizado','determinado_valor_euro_diario_cotizado',
    'determinado_valor','determinado_valor_diario',
    'determinado_total_dolar_cotizado','determinado_total_euro_cotizado',
    'determinado_total','determinado_ajuste','determinado'
  ] as $varname){
    $$varname =  "canon_fijo_mesas[$molde_str][$varname]";
  }
  
  $molde_str_diario = '$'.uniqid();
  
  foreach([
    'dia','bruto_ARS','bruto_USD','bruto','dia_semana',
    'mesas_habilitadas','mesas_habilitadas_acumuladas',
    'valor_diario','devengado_determinado_acumulado'
  ] as $varname){
    $varname_php = 'd_'.$varname;
    $$varname_php = "canon_fijo_mesas[$molde_str][diario][$molde_str_diario][$varname]";
  }
?>
<div class="bloque_interno bloque_principal" style="width: 100%;" data-js-molde="{{$molde_str}}" data-subcanon-tipo data-subcanon-toggle-estado="esconder_subcanon" data-subcanon-toggle-mensual-diario-estado="mensual">
  <input data-tipo data-js-texto-no-formatear-numero data-name="{{$tipo}}" hidden>
  <input data-name="{{$id_canon_fijo_mesas}}" hidden>
  <div class="bloque_interno" style="width: 100%;display: flex;align-items: center;">
    @component('Canon.ModalCanon.toggleSubcanon')
    @endcomponent
    <h6 data-titulo>TITULO TIPO SUBCANON</h6>
  </div>
  <div data-subcanon-toggle-visible="mostrar_subcanon" style="width: 100%;display: block;">
    <div class="bloque_interno">
      <div style="display: flex;width: 100%;">
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
          <input class="form-control" data-name="{{$dias_valor}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="aproximado valor_intermedio">
          <h5>FACTOR DÍAS VALOR ≈ (DÍAS VALOR)⁻¹</h5>
          <input class="form-control" data-name="{{$factor_dias_valor}}" data-depende="{{$dias_valor}}" data-readonly='[{"modo":"*"}]'>
        </div>
      </div>
      <div style="display: flex;width: 100%;">
        <div>
          <h5>VALOR DOLAR DIARIO (USD)</h5>
          <input class="form-control" data-name="{{$valor_dolar_diario}}" data-depende="{{$factor_dias_valor}},valor_dolar" data-readonly='[{"modo":"*"}]'>
        </div>
        <div>
          <h5>VALOR EURO DIARIO (EUR)</h5>
          <input class="form-control" data-name="{{$valor_euro_diario}}" data-depende="{{$factor_dias_valor}},valor_euro" data-readonly='[{"modo":"*"}]'>
        </div>
      </div>
      <br>
    </div>
    <div style="display: flex;width: 100%;">
      <div class="bloque_interno" data-css-devengar style="flex: 1;border-right: 1px solid black;">
        <h4 style="display: flex;">
          <span>DEVENGADO&nbsp;&nbsp;</span>
          <select class="form-control" data-name="{{$devengar}}" data-js-devengar style="width: unset;height: unset;padding: 0;"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            <option value="1">SI</option>
            <option value="0">NO</option>
          </select>
        </h4>
        <div style="display: flex;">
          <div class="valor_intermedio">
            <h5>VALOR DOLAR (ARS)</h5>
            <input class="form-control" data-name="{{$devengado_valor_dolar_cotizado}}" data-depende="devengado_cotizacion_dolar,valor_dolar" data-readonly='[{"modo":"*"}]'>
          </div>
          <div class="valor_intermedio">
            <h5>VALOR EURO (ARS)</h5>
            <input class="form-control" data-name="{{$devengado_valor_euro_cotizado}}" data-depende="devengado_cotizacion_euro,valor_euro" data-readonly='[{"modo":"*"}]'>
          </div>
          <div class="valor_intermedio">
            <h5>VALOR (ARS)</h5>
            <input class="form-control" data-name="{{$devengado_valor}}" data-depende="{{$devengado_valor_dolar_cotizado}},{{$devengado_valor_euro_cotizado}}" data-readonly='[{"modo":"*"}]'>
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
          <div class="valor_intermedio">
            <h5>VALOR DIARIO (ARS)</h5>
            <input class="form-control" data-name="{{$devengado_valor_diario}}" data-depende="{{$devengado_valor}},{{$factor_dias_valor}}" data-readonly='[{"modo":"*"}]'>
          </div>
        </div>
      </div>
      <div class="bloque_interno" style="flex: 1;">
        <h4>DETERMINADO</h4>
        <div style="display: flex;">
          <div class="valor_intermedio">
            <h5>VALOR DOLAR (ARS)</h5>
            <input class="form-control" data-name="{{$determinado_valor_dolar_cotizado}}" data-depende="determinado_cotizacion_dolar,valor_dolar" data-readonly='[{"modo":"*"}]'>
          </div>
          <div class="valor_intermedio">
            <h5>VALOR EURO (ARS)</h5>
            <input class="form-control" data-name="{{$determinado_valor_euro_cotizado}}" data-depende="determinado_cotizacion_euro,valor_euro" data-readonly='[{"modo":"*"}]'>
          </div>
          <div class="valor_intermedio">
            <h5>VALOR (ARS)</h5>
            <input class="form-control" data-name="{{$determinado_valor}}" data-depende="{{$determinado_valor_dolar_cotizado}},{{$determinado_valor_euro_cotizado}}" data-readonly='[{"modo":"*"}]'>
          </div>
        </div>
        <div style="display: flex;">
          <div class="valor_intermedio">
            <h5>VALOR DOLAR DIARIO (ARS)</h5>
            <input class="form-control" data-name="{{$determinado_valor_dolar_diario_cotizado}}" data-depende="{{$determinado_valor_dolar_cotizado}},{{$factor_dias_valor}}" data-readonly='[{"modo":"*"}]'>
          </div>
          <div class="valor_intermedio">
            <h5>VALOR EURO DIARIO (ARS)</h5>
            <input class="form-control" data-name="{{$determinado_valor_euro_diario_cotizado}}" data-depende="{{$determinado_valor_euro_cotizado}},{{$factor_dias_valor}}" data-readonly='[{"modo":"*"}]'>
          </div>
          <div class="valor_intermedio">
            <h5>VALOR DIARIO (ARS)</h5>
            <input class="form-control" data-name="{{$determinado_valor_diario}}" data-depende="{{$determinado_valor}},{{$factor_dias_valor}}" data-readonly='[{"modo":"*"}]'>
          </div>
        </div>
      </div>
    </div>
    <div style="display: flex;width: 100%;">
      <div class="bloque_interno" style="display: flex;width: 100%;">
        <div class="col-md-10 col-md-offset-1" style="display: flex;">
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
              <input class="form-control" data-name="{{$dias_lunes_jueves}}" placeholder="DIAS" data-depende="id_casino,año_mes" readonly>
              <input class="form-control" data-name="{{$mesas_lunes_jueves}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
          </div>
          <div>
            <h5>Viernes-Sabados</h5>
            <div style="display: flex;flex-direction: column;">
              <input class="form-control" data-name="{{$dias_viernes_sabados}}" placeholder="DIAS" data-depende="id_casino,año_mes" readonly>
              <input class="form-control" data-name="{{$mesas_viernes_sabados}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
          </div>               
          <div>
            <h5>Domingos</h5>
            <div style="display: flex;flex-direction: column;">
              <input class="form-control" data-name="{{$dias_domingos}}" placeholder="DIAS" data-depende="id_casino,año_mes" readonly>
              <input class="form-control" data-name="{{$mesas_domingos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
          </div>
          <div>
            <h5>Todos los dias</h5>
            <div style="display: flex;flex-direction: column;">
              <input class="form-control" data-name="{{$dias_todos}}" placeholder="DIAS" data-depende="id_casino,año_mes" readonly>
              <input class="form-control" data-name="{{$mesas_todos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
          </div>
          <div>
            <h5>Fijos</h5>
            <div style="display: flex;flex-direction: column;">
              <input class="form-control" data-name="{{$dias_fijos}}" placeholder="DIAS" data-depende="id_casino" readonly>
              <input class="form-control" data-name="{{$mesas_fijos}}" placeholder="MESAS" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            </div>
          </div>
          <div class="parametro_chico">
            <h5>Factor Ajuste Diario Fijas</h5>
            <div style="display: flex;flex-direction: column;">
              <input class="form-control" data-name="{{$factor_ajuste_diario_fijas}}" data-depende="año_mes" readonly>
            </div>
          </div>
          <div class="valor_intermedio">
            <h5>MESAS×DIAS</h5>
            <input class="form-control" data-name="{{$mesas_dias}}" data-depende="{{$dias_lunes_jueves}},{{$mesas_lunes_jueves}},{{$dias_viernes_sabados}},{{$mesas_viernes_sabados}},{{$dias_domingos}},{{$mesas_domingos}},{{$dias_todos}},{{$mesas_todos}},{{$dias_fijos}},{{$mesas_fijos}}" readonly="readonly">
          </div>
        </div>
      </div>
    </div>
    <div style="display: flex;width: 100%;">
      <div class="bloque_interno" style="width: 100%;align-items: center;">      
        <div style="width: 100%;display: flex;">
          @component('Canon.ModalCanon.toggleMensualDiario')
          @endcomponent
        </div>
        <div data-div-devengado="header" data-div-determinado="header" style="width: 100%;">
          <table class="table table-bordered" style="margin-bottom: 0;">
            @section('colgroupCF')
            <colgroup>
              <col style="width: 4%;">
              <col style="width: 14.5%;">
              <col style="width: 14.5%;">
              <col style="width: 14.5%;">
              <col style="width: 8.5%;">
              <col style="width: 9.5%;">
              <col style="width: 9.5%;">
              <col style="width: 10.5%;">
              <col style="width: 14.5%;">
            </colgroup>
            @endsection
            @yield('colgroupCF')
            <thead>
              <tr>
                <th style="text-align: center;">Día</th>
                <th style="text-align: center;">Bruto ARS</th>
                <th style="text-align: center;">Bruto USD</th>
                <th style="text-align: center;">Bruto</th>
                <th style="text-align: center;">Día Sem</th>
                <th style="text-align: center;">Mesas Hab.</th>
                <th style="text-align: center;">Mesas Hab. (acumulado)</th>
                <th style="text-align: center;">Valor Diario</th>
                <th style="text-align: center;">Total (acumulado)</th>
              </tr>
            </thead>
          </table>
        </div>
        <div style="width: 100%;max-height: 25vh;overflow-y: scroll;" data-mensual-diario-toggle-visible="diario">
          <!-- No hay 2 tablas para determinado y devengado... asi que le pongo un solo atributo -->
          <div data-div-determinado="diario" style="width: 100%;">
            <table data-tabla-diario class="sacar-borde-primer-tr table table-bordered" style="margin-bottom: 0;">
              @yield('colgroupCF')
              <tbody>
              </tbody>
            </table>
            <table hidden>
              <tr data-molde-diario="{{$molde_str_diario}}">
                <td><input class="form-control" data-name="{{$d_dia}}" readonly></td>
                <td><input class="form-control" data-name="{{$d_bruto_ARS}}"></td>
                <td><input class="form-control" data-name="{{$d_bruto_USD}}"></td>
                <td><input class="form-control" data-name="{{$d_bruto}}" readonly></td>
                <td><input class="form-control" data-name="{{$d_dia_semana}}" readonly></td>
                <td><input class="form-control" data-name="{{$d_mesas_habilitadas}}" readonly></td>
                <td><input class="form-control" data-name="{{$d_mesas_habilitadas_acumuladas}}" readonly></td>
                <td><input class="form-control" data-name="{{$d_valor_diario}}" readonly></td>
                <td><input class="form-control" data-name="{{$d_devengado_determinado_acumulado}}" readonly></td>
              </tr>
            </table>
          </div>
        </div>
        <div style="width: 100%;">
          <table class="sacar-borde-primer-tr table table-bordered">
            @yield('colgroupCF')
            <tbody>
              <tr class="fila-mensual">
                <td colspan="3">&nbsp;</td>
                <td><input class="form-control" data-name="{{$bruto}}" readonly></td>
                <td colspan="7">&nbsp;</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div style="display: flex;width: 100%;">
      <div style="display: flex;width: 100%;">
        <div class="bloque_interno" data-css-devengar style="flex: 1;border-right: 1px solid black;">
          <h4>DEVENGADO</h4>
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
  </div>
</div>
