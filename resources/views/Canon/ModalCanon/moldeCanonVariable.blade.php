<?php
  $molde_str = '$'.uniqid();
  $molde_str_diario = '$'.uniqid();
  $n = function($s) use (&$molde_str){
    return "canon_variable[$molde_str][$s]";
  };
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
  $nd = function($s,$mstr = null,$mstrd = null) use (&$molde_str,&$molde_str_diario){
    $mstr = $mstr ?? $molde_str;
    $mstrd = $mstrd ?? $molde_str_diario;
    return "canon_variable[$mstr][diario][$mstrd][$s]";
  };
?>
<div class="bloque_interno" data-js-molde="{{$molde_str}}" data-subcanon-tipo>  
  <input data-tipo data-js-texto-no-formatear-numero data-name="{{$n('tipo')}}" hidden>
  <input data-name="{{$n('id_canon_variable')}}" hidden>
  <div class="bloque_interno"  style="width: 100%;display: flex;align-items: center;">
    @include('Canon.ModalCanon.toggleMensualDiario')
  </div>
  <div style="width: 100%;">
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
      <div class="bloque_interno" data-css-devengar style="flex: 1;">
        <h4 style="display: flex;">
          <span>DEVENGADO&nbsp;&nbsp;</span>
          <select class="form-control" data-name="{{$devengar}}" data-js-devengar style="width: unset;height: unset;padding: 0;" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            <option value="1">SI</option>
            <option value="0">NO</option>
          </select>
        </h4>
        <div data-mensual-diario="mensual" style="width: 100%;display: grid; 
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
            <input class="form-control" data-name="{{$devengado_bruto}}" data-depende="id_casino,es_antiguo" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
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
            <input class="form-control" data-name="{{$devengado_deduccion}}" data-depende="id_casino"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          </div>
          <div style="grid-area: grid_devengado">
            <h5>DEVENGADO</h5>
            <input class="form-control" data-name="{{$devengado}}" data-depende="{{$devengado_total}},{{$devengado_deduccion}}" data-readonly='[{"modo": "*"}]'>
          </div>
          <div style="grid-area: grid_vacio2">
          </div>
        </div>
      </div>
      <div data-mensual-diario="mensual" class="bloque_interno" style="flex: 1;">
        <h4>DETERMINADO</h4>
        <div style="width: 100%;display: grid; 
          grid-template-columns: 1fr 1fr 1fr 1fr 1fr; 
          grid-template-rows: 1fr 1fr 1fr; 
          gap: 0px 0px; 
          grid-template-areas: 'grid_vacio grid_vacio grid_vacio grid_vacio grid_vacio' 'grid_impuesto grid_bruto grid_subtotal grid_total grid_ajuste' 'grid_determinado grid_vacio2 grid_vacio2 grid_vacio2 grid_vacio2';"
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
            <input class="form-control" data-name="{{$determinado_subtotal}}" data-depende="{{$determinado_bruto}},{{$determinado_impuesto}}" data-readonly='[{"modo":"*"}]'>
          </div>
          <div style="grid-area: grid_total">
            <h5>TOTAL</h5>
            <input class="form-control" data-name="{{$determinado_total}}" data-depende="{{$determinado_subtotal}},{{$alicuota}}" data-readonly='[{"es_antiguo": "0"},{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          </div>
          <div style="grid-area: grid_ajuste">
            <h5>AJUSTE</h5>
            <input class="form-control" data-name="{{$determinado_ajuste}}" data-depende="id_casino"  data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          </div>
          <div style="grid-area: grid_determinado">
            <h5>DETERMINADO</h5>
            <input class="form-control" data-name="{{$determinado}}" data-depende="{{$determinado_total}},{{$determinado_ajuste}}" data-readonly='[{"modo": "*"}]'>
          </div>
          <div style="grid-area: grid_vacio2">
          </div>
        </div>
      </div>
    </div>
  </div>
  <div style="width: 100%;" data-mensual-diario="diario">
    <div data-tabla="canon_variable" class="bloque_interno" style="width: 100%;">
      <div class="row">
        <div data-div-devengado="header" class="col-md-12">
          <table class="table table-bordered" style="margin-bottom: 0;">
            @section('colgroupCV')
            <colgroup>
              <col style="width: 5%;">
              <col style="width: 5%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
              <col style="width: 9%;">
            </colgroup>
            @endsection
            @yield('colgroupCV')
            <thead>
              <tr>
                <th colspan="12" style="text-align: center;">Devengado</th>
              </tr>
              <tr>
                <th style="text-align: center;">Día</th>
                <th style="text-align: center;">Cotización</th>
                <th style="text-align: center;">Apostado (ARS)</th>
                <th style="text-align: center;">Apostado (USD)</th>
                <th style="text-align: center;">Apostado</th>
                <th style="text-align: center;">Base imponible</th>
                <th style="text-align: center;">Impuesto</th>
                <th style="text-align: center;">Bruto (ARS)</th>
                <th style="text-align: center;">Bruto (USD)</th>
                <th style="text-align: center;">Bruto</th>
                <th style="text-align: center;">Subtotal</th>
                <th style="text-align: center;">Total</th>
              </tr>
            </thead>
          </table>
        </div>
        <div data-div-devengado="diario" class="col-md-12" style="max-height: 25vh;overflow-y: scroll;">
          <table data-tabla-diario class="sacar-borde-primer-tr table table-bordered" style="margin-bottom: 0;">
            @yield('colgroupCV')
            <tbody>
            </tbody>
          </table>
          <table hidden>
            <tr data-molde-diario="{{$molde_str_diario}}">
              <td><input class="form-control" data-name="{{$nd('dia')}}" readonly></td>
              <td><input class="form-control" data-name="{{$nd('devengado_cotizacion')}}"></td>
              <td><input class="form-control" data-name="{{$nd('devengado_apostado_sistema_ARS')}}"></td>
              <td><input class="form-control" data-name="{{$nd('devengado_apostado_sistema_USD')}}"></td>
              <td><input class="form-control" data-name="{{$nd('devengado_apostado_sistema')}}" readonly></td>
              <td><input class="form-control" data-name="{{$nd('devengado_base_imponible')}}" readonly></td>
              <td><input class="form-control" data-name="{{$nd('devengado_impuesto')}}" readonly></td>
              <td><input class="form-control" data-name="{{$nd('devengado_bruto_ARS')}}"></td>
              <td><input class="form-control" data-name="{{$nd('devengado_bruto_USD')}}"></td>
              <td><input class="form-control" data-name="{{$nd('devengado_bruto')}}" readonly></td>
              <td><input class="form-control" data-name="{{$nd('devengado_subtotal')}}" readonly></td>
              <td><input class="form-control" data-name="{{$nd('devengado_total')}}" readonly></td>
            </tr>
          </table>
        </div>
        <div data-div-devengado="mensual" class="col-md-12">
          <table data-tabla-mensual class="sacar-borde-primer-tr table table-bordered">
            @yield('colgroupCV')
            <tbody>
              <tr class="fila-mensual">
                <td colspan="2">&nbsp;</td>
                <td><input class="form-control" data-name="{{$nd('devengado_apostado_sistema_ARS',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('devengado_apostado_sistema_USD',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('devengado_apostado_sistema',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('devengado_base_imponible',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('devengado_impuesto',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('devengado_bruto_ARS',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('devengado_bruto_USD',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('devengado_bruto',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('devengado_subtotal',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('devengado_total',null,'mensual')}}" readonly></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="row">
        <div data-div-determinado="header" class="col-md-12">
          <table class="table table-bordered" style="margin-bottom: 0;">
            @yield('colgroupCV')
            <thead>
              <tr>
                <th colspan="12" style="text-align: center;">Determinado</th>
              </tr>
              <tr>
                <th style="text-align: center;">Día</th>
                <th style="text-align: center;">Cotización</th>
                <th class="celda_vacia" colspan="4">&nbsp;</th>
                <th style="text-align: center;">Impuesto (Proporcional)</th>
                <th style="text-align: center;">Bruto (ARS)</th>
                <th style="text-align: center;">Bruto (USD)</th>
                <th style="text-align: center;">Bruto</th>
                <th style="text-align: center;">Subtotal</th>
                <th style="text-align: center;">Total</th>
              </tr>
            </thead>
          </table>
        </div>
        <div data-div-determinado="diario" class="col-md-12" style="max-height: 25vh;overflow-y: scroll;">
          <table data-tabla-diario class="sacar-borde-primer-tr table table-bordered" style="margin-bottom: 0;">
            @yield('colgroupCV')
            <tbody>
            </tbody>
          </table>
          <table hidden>
            <tr data-molde-diario="{{$molde_str_diario}}">
              <td><input class="form-control" data-name="{{$nd('dia')}}" readonly></td>
              <td><input class="form-control" data-name="{{$nd('determinado_cotizacion')}}" readonly></td>
              <td class="celda_vacia" colspan="4">&nbsp;</td>
              <td><input class="form-control" data-name="{{$nd('determinado_impuesto')}}" readonly></td>
              <td><input class="form-control" data-name="{{$nd('determinado_bruto_ARS')}}"></td>
              <td><input class="form-control" data-name="{{$nd('determinado_bruto_USD')}}"></td>
              <td><input class="form-control" data-name="{{$nd('determinado_bruto')}}"></td>
              <td><input class="form-control" data-name="{{$nd('determinado_subtotal')}}" readonly></td>
              <td><input class="form-control" data-name="{{$nd('determinado_total')}}" readonly></td>
            </tr>
          </table>
        </div>
        <div data-div-determinado="mensual" class="col-md-12">
          <table data-tabla-mensual class="sacar-borde-primer-tr table table-bordered">
            @yield('colgroupCV')
            <tbody>
              <tr class="fila-mensual">
                <td class="celda_vacia" colspan="6">&nbsp;</td>
                <td><input class="form-control" data-name="{{$nd('determinado_impuesto',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('determinado_bruto_ARS',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('determinado_bruto_USD',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('determinado_bruto',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('determinado_subtotal',null,'mensual')}}" readonly></td>
                <td><input class="form-control" data-name="{{$nd('determinado_total',null,'mensual')}}" readonly></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
