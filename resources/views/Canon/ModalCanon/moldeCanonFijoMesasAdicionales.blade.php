<?php
  $molde_str = '$'.uniqid();
  foreach([
    'tipo','id_canon_fijo_mesas_adicionales',
    'dias_mes','horas_dia',
    'horas','mesas',
    'porcentaje',
    'devengar',
    'devengado_valor_mes','devengado_valor_dia','devengado_valor_hora',
    'devengado_total','devengado_deduccion',
    'devengado',
    'determinado_valor_mes','determinado_valor_dia','determinado_valor_hora',
    'determinado_total','determinado_ajuste',
    'determinado'
  ] as $varname){
    $$varname = "canon_fijo_mesas_adicionales[$molde_str][$varname]";
  }
  
  $molde_str_diario = '$'.uniqid();
  foreach([
    'dia',
    'valor_mes','valor_dia','valor_hora',
    'horas_diarias','mesas_diarias',
    'horas','mesas',
    'total'
  ] as $varname){
    $varname_php = 'd_'.$varname;
    $$varname_php = "canon_fijo_mesas_adicionales[$molde_str][diario][$molde_str_diario][$varname]";
  }
  
  $prev_d_horas = "'canon_fijo_mesas_adicionales[{$molde_str}][diario]['+($molde_str_diario-1)+'][horas]'";
  $prev_d_mesas = "'canon_fijo_mesas_adicionales[{$molde_str}][diario]['+($molde_str_diario-1)+'][mesas]'";
  foreach([//No hay meses con mas de 31 dias... asi que puedo hacerlo asi directamente... sin hacerlo dinamicamente
    'horas_diarias','mesas_diarias',
  ] as $varname){
    $tot_varname = 'tot_'.$varname;
    $$tot_varname = '';
    for($d=1;$d<=31;$d++){
      $$tot_varname .= ($d > 1? ',' : '')."canon_fijo_mesas_adicionales[{$molde_str}][diario][$d][$varname]";
    }
  }
?>
<div class="bloque_interno bloque_principal" data-js-molde="{{$molde_str}}" data-subcanon="canon_fijo_mesas_adicionales" data-subcanon-toggle-estado="esconder_subcanon" data-subcanon-toggle-mensual-diario-estado="mensual">
  <input data-tipo data-js-texto-no-formatear-numero data-name="{{$tipo}}" hidden>
  <input data-name="{{$id_canon_fijo_mesas_adicionales}}" hidden>
  <div class="bloque_interno"  style="width: 100%;display: flex;align-items: center;">
    @component('Canon.ModalCanon.toggleSubcanon')
    @endcomponent
    <h6 data-titulo>TITULO TIPO SUBCANON</h6>
  </div>
  <div data-subcanon-toggle-visible="mostrar_subcanon" style="width: 100%;display: block;">
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
        <div class="parametro_chico">
          <h5>PORCENTAJE</h5>
          <input class="form-control" data-name="{{$porcentaje}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
      </div>
    </div>
    <div style="display: flex;">
      <div class="bloque_interno" style="width: 100%;">
        <div style="width: 100%;">
          <div style="width: 100%;">
            @component('Canon.ModalCanon.toggleMensualDiario')
            @endcomponent
            <table class="table table-bordered" style="margin-bottom: 0;">
              @section('colgroupCFMA')
              <colgroup>
                <col style="width: 6%;">
                <col style="width: 14%;">
                <col style="width: 14%;">
                <col style="width: 14%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 16%;">
              </colgroup>
              @endsection
              @yield('colgroupCFMA')
              <thead>
                <tr>
                  <th class="celda-vacia" colspan="6">&nbsp;</th>
                  <th colspan="3" style="text-align: center;">Acumulado</th>
                </tr>
                <tr>
                  <th style="text-align: center;">Día</th>
                  <th style="text-align: center;">Valor Mes</th>
                  <th style="text-align: center;">Valor Día</th>
                  <th style="text-align: center;">Valor Hora</th>
                  <th style="text-align: center;">Horas</th>
                  <th style="text-align: center;">Mesas</th>
                  <th style="text-align: center;">Horas</th>
                  <th style="text-align: center;">Mesas</th>
                  <th style="text-align: center;">Total</th>
                </tr>
              </thead>
            </table>
          </div>
          <div data-mensual-diario-toggle-visible="diario">
            <div data-div-determinado="diario" style="width: 100%;max-height: 25vh;overflow-y: scroll;">
              <table data-tabla-diario class="sacar-borde-primer-tr table table-bordered" style="margin-bottom: 0;">
                @yield('colgroupCFMA')
                <tbody>
                </tbody>
              </table>
              <table hidden>
                <tr data-molde-diario="{{$molde_str_diario}}">
                  <td><input class="form-control" data-name="{{$d_dia}}" readonly></td>
                  <td><input class="form-control" data-name="{{$d_valor_mes}}" data-depende="valor_dolar,valor_euro,canon_cotizacion_diaria[{{$molde_str_diario}}][dolar],canon_cotizacion_diaria[{{$molde_str_diario}}][euro]" readonly></td>
                  <td><input class="form-control" data-name="{{$d_valor_dia}}" data-depende="{{$d_valor_mes}},{{$dias_mes}}" readonly></td>
                  <td><input class="form-control" data-name="{{$d_valor_hora}}" data-depende="{{$d_valor_mes}},{{$dias_mes}},{{$horas_dia}}"  readonly></td>
                  <td><input class="form-control" data-name="{{$d_horas_diarias}}"></td>
                  <td><input class="form-control" data-name="{{$d_mesas_diarias}}"></td>
                  <td><input class="form-control" data-name="{{$d_horas}}" data-depende="{{$d_horas_diarias}}" data-depende-dyn="{{$prev_d_horas}}" readonly></td>
                  <td><input class="form-control" data-name="{{$d_mesas}}" data-depende="{{$d_mesas_diarias}}" data-depende-dyn="{{$prev_d_mesas}}" readonly></td>
                  <td><input class="form-control" data-name="{{$d_total}}" data-depende="{{$d_horas_diarias}},{{$d_mesas_diarias}},{{$d_valor_hora}}" readonly></td>
                </tr>
              </table>
            </div>
          </div>        
          <div style="width: 100%;">
            <table class="sacar-borde-primer-tr table table-bordered">
              @yield('colgroupCFMA')
              <tbody>
                <tr class="fila-mensual">
                  <td style="text-align: right;">Devengado</td>
                  <td>
                    <input class="form-control" data-name="{{$devengado_valor_mes}}" data-depende="valor_dolar,valor_euro,devengado_cotizacion_dolar,devengado_cotizacion_euro" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$devengado_valor_dia}}" data-depende="{{$devengado_valor_mes}},{{$dias_mes}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$devengado_valor_hora}}" data-depende="{{$devengado_valor_mes}},{{$dias_mes}},{{$horas_dia}}" readonly>
                  </td>
                  <td colspan="2" rowspan="2">&nbsp;</td>
                  <td rowspan="2">
                    <input class="form-control" data-name="{{$horas}}" data-depende="{{$tot_horas_diarias}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"},{"version": "diario"}]'>
                  </td>
                  <td rowspan="2">
                    <input class="form-control" data-name="{{$mesas}}" data-depende="{{$tot_mesas_diarias}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"},{"version": "diario"}]'>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$devengado_total}}" data-depende="{{$devengado_total}}" data-depende="{{$devengado_valor_mes}},{{$devengado_valor_dia}}{{$devengado_valor_hora}},{{$horas}},{{$mesas}}"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"},{"version": "diario"},{"version": "mensual"}]'>
                  </td>
                </tr>
                <tr class="fila-mensual">
                  <td style="text-align: right;">Determinado</td>
                  <td>
                    <input class="form-control" data-name="{{$determinado_valor_mes}}" data-depende="valor_dolar,valor_euro,determinado_cotizacion_dolar,determinado_cotizacion_euro" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$determinado_valor_dia}}" data-depende="{{$determinado_valor_mes}},{{$dias_mes}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$determinado_valor_hora}}" data-depende="{{$determinado_valor_mes}},{{$dias_mes}},{{$horas_dia}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$determinado_total}}" data-depende="{{$determinado_valor_mes}},{{$determinado_valor_dia}}{{$determinado_valor_hora}},{{$horas}},{{$mesas}}"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"},{"version": "diario"},{"version": "mensual"}]'>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div style="display: flex;">
      <div class="bloque_interno separar-devengado-determinado" data-css-devengar style="flex: 1;">
        <h4 style="display: flex;">
          <span>DEVENGADO&nbsp;&nbsp;</span>
          <select class="form-control" data-name="{{$devengar}}" data-js-devengar style="width: unset;height: unset;padding: 0;"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            <option value="1">SI</option>
            <option value="0">NO</option>
          </select>
        </h4>
        <div style="display: flex;">
          <div>
            <h5>DEDUCCIÓN</h5>
            <input class="form-control" data-name="{{$devengado_deduccion}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          </div>
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
            <h5>AJUSTE</h5>
            <input class="form-control" data-name="{{$determinado_ajuste}}" data-depende="id_casino" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          </div>
          <div>
            <h5>DETERMINADO</h5>
            <input class="form-control" data-name="{{$determinado}}" data-depende="{{$determinado_total}},{{$determinado_ajuste}}" data-readonly='[{"modo": "*"}]'>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
