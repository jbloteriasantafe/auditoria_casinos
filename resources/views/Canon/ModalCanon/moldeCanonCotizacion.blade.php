<div class="row">
  <div class="col-md-7 col-md-offset-3">
    <table class="table table-bordered" style="margin-bottom: 0;">
      @section('colgroupCOT')
      <colgroup>
        <col style="width: 10%;">
        <col style="width: 30%;">
        <col style="width: 30%;">
        <col style="width: 30%;">
      </colgroup>
      @endsection
      @yield('colgroupCOT')
      <thead>
        <tr>
          <th style="text-align: center;" colspan="2">Día</th>
          <th style="text-align: center;">USD</th>
          <th style="text-align: center;">EUR</th>
        </tr>
      </thead>
    </table>
  </div>
  <div class="col-md-7 col-md-offset-3" style="max-height: 20em;overflow-y: scroll;">
    <table class="table table-bordered" style="margin-bottom: 0;">
      @yield('colgroupCOT')
      <tbody data-js-contenedor>
      </tbody>
    </table>
  </div>
  <div class="col-md-7 col-md-offset-3">
    <table class="table table-bordered" style="margin-bottom: 0;">
      @yield('colgroupCOT')
      <tbody>
        <tr class="fila-mensual">
          <td style="text-align: center;">
            Devengado
          </td>
          <td>
            @component('Components.inputFecha',[
              'attrs' => "data-js-texto-no-formatear-numero name='devengado_fecha_cotizacion' data-depende='año_mes'",
              'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"'
            ])
            @endcomponent
          </td>
          <td>
            <input class="form-control" name="devengado_cotizacion_dolar" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          </td>
          <td>
            <input class="form-control" name="devengado_cotizacion_euro" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          </td>
        </tr>
        <tr class="fila-mensual">
          <td style="text-align: center;">
            Determinado
          </td>
          <td>
            @component('Components.inputFecha',[
              'attrs' => "data-js-texto-no-formatear-numero name='determinado_fecha_cotizacion' data-depende='año_mes'",
              'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;"'
            ])
            @endcomponent
          </td>
          <td>
            <input class="form-control" name="determinado_cotizacion_dolar" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          </td>
          <td>
            <input class="form-control" name="determinado_cotizacion_euro" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <?php
    $molde_str = '$'.uniqid();
    foreach(['dia','dolar','euro'] as $varname){
      $$varname =  "canon_cotizacion_diaria[$molde_str][$varname]";
    }
  ?>
  <table hidden>
    <tr data-subcanon="canon_cotizacion_diaria" data-js-molde="{{$molde_str}}">
      <td colspan="2"><input class="form-control" data-name="{{$dia}}" style="text-align: center;" readonly></td>
      <td><input class="form-control" data-name="{{$dolar}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'></td>
      <td><input class="form-control" data-name="{{$euro}}" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'></td>
    </tr>
  </table>
</div>
