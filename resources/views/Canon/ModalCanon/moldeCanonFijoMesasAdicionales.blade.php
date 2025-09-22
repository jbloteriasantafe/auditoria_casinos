<?php
  $molde_str = '$'.uniqid();
  $molde_str_diario = '$'.uniqid();
  $n = function($s) use (&$id_casino,&$t,&$molde_str){
    return "canon_fijo_mesas_adicionales[$molde_str][$s]";
  };
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
  
  $nd = function($s) use (&$molde_str,&$molde_str_diario){
    return "canon_fijo_mesas_adicionales[$molde_str][diario][$molde_str_diario][$s]";
  };
?>
<div class="bloque_interno bloque_principal" data-js-molde="{{$molde_str}}" data-subcanon-tipo data-subcanon-toggle-estado="esconder_subcanon" data-subcanon-toggle-mensual-diario-estado="mensual">
  <input data-tipo data-js-texto-no-formatear-numero data-name="{{$n('tipo')}}" hidden>
  <input data-name="{{$n('id_canon_fijo_mesas_adicionales')}}" hidden>
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
        <div class="aproximado valor_intermedio">
          <h5>FACTOR DIAS MES ≈ (DÍAS MES)⁻¹</h5>
          <input class="form-control" data-name="{{$factor_dias_mes}}" data-depende="{{$dias_mes}}" data-readonly='[{"modo":"*"}]'>
        </div>
        <div class="aproximado valor_intermedio">
          <h5>FACTOR HORAS MES ≈ (DÍAS MES × HORAS DÍA)⁻¹</h5>
          <input class="form-control" data-name="{{$factor_horas_mes}}" data-depende="{{$dias_mes}},{{$horas_dia}}" data-readonly='[{"modo":"*"}]'>
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
                <col style="width: 8%;">
                <col style="width: 8%;">
                <col style="width: 14%;">
                <col style="width: 14%;">
                <col style="width: 14%;">
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
                  <th style="text-align: center;">Horas</th>
                  <th style="text-align: center;">Mesas</th>
                  <th style="text-align: center;">Valor Mes</th>
                  <th style="text-align: center;">Valor Día</th>
                  <th style="text-align: center;">Valor Hora</th>
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
                  <td><input class="form-control" data-name="{{$nd('dia')}}" readonly></td>
                  <td><input class="form-control" data-name="{{$nd('horas_diarias')}}"></td>
                  <td><input class="form-control" data-name="{{$nd('mesas_diarias')}}"></td>
                  <td><input class="form-control" data-name="{{$nd('horas')}}" readonly></td>
                  <td><input class="form-control" data-name="{{$nd('mesas')}}" readonly></td>
                  <td><input class="form-control" data-name="{{$nd('valor_mes')}}" readonly></td>
                  <td><input class="form-control" data-name="{{$nd('valor_dia')}}" readonly></td>
                  <td><input class="form-control" data-name="{{$nd('valor_hora')}}" readonly></td>
                  <td><input class="form-control" data-name="{{$nd('total')}}" readonly></td>
                </tr>
              </table>
            </div>
          </div>        
          <div style="width: 100%;">
            <table class="sacar-borde-primer-tr table table-bordered">
              @yield('colgroupCFMA')
              <tbody>
                <tr class="fila-mensual">
                  <td colspan="3" style="text-align: right;">Devengado</td>
                  <td>
                    <input class="form-control" data-name="{{$horas}}"  data-depende="{{$mesas}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$mesas}}" data-depende="{{$horas}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$devengado_valor_mes}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$devengado_valor_dia}}" data-depende="{{$devengado_valor_mes}},{{$devengado_valor_dia}},{{$devengado_valor_hora}},{{$horas}},{{$porcentaje}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$devengado_valor_hora}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$devengado_total}}" data-depende="{{$devengado_valor_mes}},{{$horas}},{{$mesas}}" readonly>
                  </td>
                </tr>
                <tr class="fila-mensual">
                  <td colspan="3" style="text-align: right;">Determinado</td>
                  <td>
                    <input class="form-control" data-name="{{$horas}}"  data-depende="{{$mesas}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$mesas}}" data-depende="{{$horas}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$determinado_valor_mes}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$determinado_valor_dia}}" data-depende="{{$determinado_valor_mes}},{{$determinado_valor_dia}},{{$determinado_valor_hora}},{{$horas}},{{$porcentaje}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$determinado_valor_hora}}" readonly>
                  </td>
                  <td>
                    <input class="form-control" data-name="{{$determinado_total}}" data-depende="{{$determinado_valor_mes}},{{$horas}},{{$mesas}}" readonly>
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
