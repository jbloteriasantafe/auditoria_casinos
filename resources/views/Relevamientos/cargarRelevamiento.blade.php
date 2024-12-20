@component('Components/include_guard',['nombre' => 'modalCargarRelevamiento'])
<style>
  .modalCargarRelevamiento .tablaRelevamiento thead tr th {
    text-align: center;
  }
</style>
<script>
  const CONTADORES   = {{$CONTADORES}};
</script>
@endcomponent
  
@component('Components/modal',[
  'clases_modal' => 'modalCargarRelevamiento',
  'attrs_modal' => 'data-js-modal-cargar-relevamiento',
  'estilo_cabecera' => 'background-color: #FF6E40;',
  'grande' => 90,
  'salir' => false
])

@slot('titulo')
<span data-js-modo="Cargar">| CARGAR RELEVAMIENTO</span>
<span data-js-modo="Validar">| VISAR RELEVAMIENTO</span>
<span data-js-modo="Ver">| VER RELEVAMIENTO</span>
@endslot

@slot('cuerpo')
<form class="form-horizontal" novalidate="">
  <input type="hidden" name="id_relevamiento" value="">
  <div class="row">
    <div class="col-lg-2 col-xl-offset-1">
      <h5>FECHA DE RELEVAMIENTO</h5>
      <input name="fecha" type='text' class="form-control" data-js-enabled="">
    </div>
    <div class="col-lg-2">
      <h5>FECHA DE GENERACIÓN</h5>
      <input name="fecha_generacion" type='text' class="form-control" data-js-enabled="">
    </div>
    <div class="col-lg-2">
      <h5>CASINO</h5>
      <input name="casino" type='text' class="form-control"  data-js-enabled="">
    </div>
    <div class="col-lg-2">
      <h5>SECTOR</h5>
      <input name="sector" type='text' class="form-control" data-js-enabled="">
    </div>
    <div class="col-lg-2">
      <h5>SUB RELEVAMIENTO</h5>
      <input name="subrelevamiento" type='text' class="form-control"  data-js-enabled="">
    </div>
  </div>
  <div class="row">
    <div class="col-md-2 col-xl-offset-1">
      <h5>FISCALIZADOR CARGA</h5>
      <input name="usuario_cargador" type="text"class="form-control" data-js-enabled="">
    </div>
    <div class="col-md-2">
      <h5>FISCALIZADOR TOMA</h5>
      <input data-js-input-usuario-fiscalizador="[data-js-hidden-usuario-fiscalizador]" class="form-control" type="text" autocomplete="off" data-js-enabled="Cargar">
      <input data-js-hidden-usuario-fiscalizador name="id_usuario_fiscalizador" hidden>
    </div>
    <div class="col-md-2">
      <h5>TÉCNICO</h5>
      <input name="tecnico" type="text" class="form-control" data-js-enabled="Cargar" data-js-modo="Ver,Cargar,Validar">
    </div>
    <div class="col-md-3">
      <h5>HORA EJECUCIÓN</h5>
      @component('Components/inputFecha',[
        'placeholder' => 'hh:mm',
        'attrs' => 'name="hora_ejecucion"',
        'attrs_dtp' => 'data-js-enabled="Cargar" data-date-format="HH:ii" data-start-view="day" data-min-view="hour"'])
      @endcomponent
    </div>
  </div>
  <br>
  <br>
  <div class="col-md-12">
    <table class="tablaRelevamiento table" style="margin-bottom: 0;">
      <thead>
        <tr style="display: flex;">
          <th data-js-modo="Ver,Cargar,Validar" style="flex: 0.5;">MTM</th>
          @for($c=1;$c<=$CONTADORES;$c++)
          <th data-js-modo="{{$c<=$CONTADORES_VISIBLES? 'Ver,Cargar,Validar' : ''}}" style="flex: 1;";>CONTADOR {{$c}}</th>
          @endfor
          <th data-js-modo="Ver,Validar" style="flex: 1;";>P. CALCULADO ($)</th>
          <th data-js-modo="Ver,Validar" style="flex: 1;";>P. IMPORTADO ($)</th>
          <th data-js-modo="Ver,Validar" style="flex: 1;overflow-wrap: break-word;word-break: break-all;";>DIFERENCIA</th>
          <th data-js-modo="Cargar,Validar" style="flex: 0.5";>&nbsp;</th>{{-- Estado --}}
          <th data-js-modo="Ver,Cargar,Validar" style="flex: 1;";>CAUSA NO TOMA</th>
          <th data-js-modo="Ver,Validar" style="flex: 0.5;";>DEN</th>
          <th data-js-modo="Validar" style="flex: 0.5;">A PEDIDO</th>
          <th data-js-modo="Validar" style="flex: 0.5;overflow-wrap: break-word;word-break: break-all;">ESTADÍSTICAS</th>
        </tr>
      </thead>
    </table>
    <div data-div-tabla-scrollable-errores style="height: 60vh;overflow-y: scroll;overflow-x: clip;width: 100%;">
      <table data-js-tabla-relevamiento class="tablaRelevamiento table">
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <br>
  <div class="row" data-js-modo="Cargar,Ver,Validar">
    <div class="col-md-8 col-md-offset-2">
      <h5>OBSERVACIONES</h5>
      <textarea name="observacion_carga" class="form-control" style="resize:vertical;" data-js-enabled="Cargar"></textarea>
    </div>
  </div>
  <div class="row" data-js-modo="Ver,Validar">
    <div class="col-md-8 col-md-offset-2">
      <h5>OBSERVACIONES VALIDACIÓN</h5>
      <textarea name="observacion_validacion" class="form-control" style="resize:vertical;" data-js-enabled="Validar"></textarea>
    </div>
  </div>
</form>

<style>
  tr[data-css-colorear] [data-js-detalle-asignar-name="diferencia"] {
    border-width: 2px;
    border-style: solid;
  }
  
  tr[data-css-colorear="DIFERENCIA"] [data-js-detalle-asignar-name="diferencia"],
  tr[data-css-colorear="NO_TOMA"] [data-js-detalle-asignar-name="diferencia"],
  tr[data-css-colorear="SIN_IMPORTAR"] [data-js-detalle-asignar-name="diferencia"] {
    border-color: #EF5350;
    color: #EF5350;
  }
  
  tr[data-css-colorear="CORRECTO"] [data-js-detalle-asignar-name="diferencia"] {
    border-color: #66BB6A;
    color: #66BB6A;
  }
  tr[data-css-colorear="TRUNCAMIENTO"] [data-js-detalle-asignar-name="diferencia"] {
    border-color: #FFA726;
    color: #FFA726;
  }
  
  tr[data-css-colorear="NO_TOMA"] [data-js-detalle-asignar-name="id_tipo_causa_no_toma"] {
    border: 2px solid #1E90FF;
    color: #1E90FF;
  }
  
  tr[data-css-colorear="DIFERENCIA"] [data-js-boton-medida],
  tr[data-css-colorear="DIFERENCIA"] [data-js-estadisticas-no-toma],
  tr[data-css-colorear="NO_TOMA"] [data-js-estadisticas-no-toma] {
    visibility: visible !important;
  }
  
  @foreach(['DIFERENCIA','NO_TOMA','SIN_IMPORTAR','CORRECTO','TRUNCAMIENTO'] as $e)
  tr[data-css-colorear="{{$e}}"] [data-js-icono-estado="{{$e}}"] {
    display: block !important;
  }
  @endforeach
  
  tr[data-css-colorear="DIFERENCIA"][data-id_unidad_medida="1"] [data-js-boton-medida="1"] {
    display: block !important;
  }
  tr[data-css-colorear="DIFERENCIA"][data-id_unidad_medida="2"] [data-js-boton-medida="2"] {
    display: block !important;
  }
  tr[data-css-colorear!="DIFERENCIA"][data-id_unidad_medida] [data-js-boton-medida="vacio"] {
    display: block !important;
  }
  
  tr [data-contador]::placeholder {
    font-size: 90%;
  }
</style>
<table hidden>
  <tr data-js-molde-tabla-relevamiento style="display: flex;">
    <td hidden><input data-js-detalle-asignar-name="id_detalle_relevamiento"></td>
    <td hidden><input data-js-detalle-asignar-name="id_unidad_medida"></td>
    <td hidden><input data-js-detalle-asignar-name="denominacion"></td>
    <td data-js-modo="Ver,Cargar,Validar" data-js-detalle-asignar-name="maquina" style="flex: 0.5;">XXXX</td>
    @for($c=1;$c<=$CONTADORES;$c++)
    <td style="text-align: right;flex: 1;" data-js-modo="{{$c<=$CONTADORES_VISIBLES? 'Ver,Cargar,Validar' : ''}}">
      <input data-contador data-js-enabled="Cargar" data-js-cambio-contador="{{$c}}" data-js-detalle-asignar-name="cont{{$c}}" class="contador cont{{$c}} form-control">
    </td>
    @endfor
    <td style="text-align: right;flex: 1;" data-js-modo="Ver,Validar">
      <input data-js-readonly="Validar" data-js-detalle-asignar-name="producido_calculado_relevado" class="producidoCalculado form-control" style="text-align: right; border: 2px solid rgb(109, 199, 190); color: rgb(109, 199, 190);">
    </td>
    <td style="text-align: right;flex: 1;" data-js-modo="Ver,Validar">
      <input data-js-enabled="Validar" data-js-readonly="Validar" data-js-detalle-asignar-name="producido_importado" class="producido form-control" style="text-align: right; border: 2px solid rgb(109, 199, 190); color: rgb(109, 199, 190);">
    </td>
    <td style="text-align: right;flex: 1;" data-js-modo="Ver,Validar">
      <input data-js-enabled="Validar" data-js-readonly="Validar" data-js-detalle-asignar-name="diferencia" class="diferencia form-control" style="text-align: right;">
    </td>
    <td data-js-modo="Cargar,Validar" data-js-estado-diferencia style="text-align: center;flex: 0.5;" class="estado_diferencia">
      <a data-js-icono-estado="DIFERENCIA" class="pop" data-content="Contadores incorrectos" data-placement="top" rel="popover" data-trigger="hover" style="display: none;">
        <i class="fa fa-times" style="color: rgb(239, 83, 80); display: inline-block;"></i>
      </a>
      <a data-js-icono-estado="CORRECTO" class="pop" data-content="Contadores correctos" data-placement="top" rel="popover" data-trigger="hover" style="display: none;">
        <i class="fa fa-check" style="color: rgb(102, 187, 106); display: inline-block;"></i>
      </a>
      <a data-js-icono-estado="NO_TOMA" class="pop" data-content="Contadores no tomados" data-placement="top" rel="popover" data-trigger="hover" style="display: none;">
        <i class="fa fa-ban" style="color: rgb(30, 144, 255); display: inline-block;"></i>
      </a>
      <a data-js-icono-estado="TRUNCAMIENTO" class="pop" data-content="Contadores importados truncados" data-placement="top" rel="popover" data-trigger="hover" style="display: none;">
        <i class="pop fa fa-exclamation" style="color: rgb(255, 167, 38); display: inline-block;"></i>
      </a>
      <a data-js-icono-estado="SIN_IMPORTAR" class="pop" data-content="No se importaron contadores" data-placement="top" rel="popover" data-trigger="hover" style="display: none;">
        <i class="pop fa fa-question" style="color: rgb(66, 165, 245); display: inline-block;"></i>
      </a>
    </td>
    <td style="text-align: center;flex: 1;" data-js-modo="Ver,Cargar,Validar">
      <select data-js-enabled="Cargar" data-js-cambio-tipo-causa-no-toma data-js-detalle-asignar-name="id_tipo_causa_no_toma" class="tipo_causa_no_toma form-control">
        <option value=""></option>
        @foreach($tipos_causa_no_toma as $t)
        <option value="{{$t->id_tipo_causa_no_toma}}" {{$t->deprecado? 'disabled' : ''}}>{{$t->descripcion}}</option>
        @endforeach
      </select>
    </td>
    <td style="text-align: center;flex: 0.5;" data-js-modo="Ver">
      <input class="form-control" disabled data-js-detalle-asignar-name="denominacion">
    </td>
    <td data-js-modo="Validar" style="flex: 0.5;">
      @php
      $popup = function($select){
        $checked1 = $select == 1? 'checked' : '';
        $checked2 = $select == 2? 'checked' : '';
        return
        '<div align="left">
          <input type="radio" name="medida" value="1" '.$checked1.'>
          <i style="margin-left:5px;position:relative;top:-3px;" class="fa fa-fw fa-life-ring"></i>
          <span style="position:relative;top:-3px;"> Crédito</span><br>
          <input type="radio" name="medida" value="2" '.$checked2.'>
          <i style="margin-left:5px;position:relative;top:-3px;" class="fas fa-dollar-sign"></i>
          <span style="position:relative;top:-3px;"> Pesos</span> <br><br>
          <button data-js-ajustar class="btn btn-deAccion btn-successAccion" type="button" style="margin-right:8px;">AJUSTAR</button>
          <button data-js-cancelar-ajuste class="btn btn-deAccion btn-defaultAccion" type="button">CANCELAR</button>
        </div>';
      };
      @endphp
      <button data-js-enabled="Validar" data-js-boton-medida="1" class="btn btn-warning pop medida" title="AJUSTE" data-trigger="manual" data-toggle="popover" data-placement="left" data-html="true" type="button" class="btn btn-warning pop medida"
       data-content="{{$popup(1)}}" style="display: none;">
        <i class="fa fa-fw fa-life-ring"></i>
      </button>
      <button data-js-enabled="Validar" data-js-boton-medida="2" class="btn btn-warning pop medida" title="AJUSTE" data-trigger="manual" data-toggle="popover" data-placement="left" data-html="true" type="button" class="btn btn-warning pop medida"
       data-content="{{$popup(2)}}" style="display: none;">
        <i class="fas fa-dollar-sign"></i>
      </button>
      <span data-js-boton-medida="vacio" style="display: none;">&nbsp;</span>
    </td>
    <td data-js-modo="Validar" style="flex: 0.5;">
      <select data-js-enabled="Validar" data-js-detalle-asignar-name="a_pedido" class="a_pedido form-control acciones_validacion">
        <option value="" selected>NO</option>
        <option value="1">1 día</option>
        <option value="5">5 días</option>
        <option value="10">10 días</option>
        <option value="15">15 días</option>
      </select>
    </td>
    <td data-js-modo="Validar" style="flex: 0.5;">
      <a href="/relevamientos/estadisticas_no_toma" target="_blank" data-js-enabled="Validar" data-js-estadisticas-no-toma class="btn btn-success acciones_validacion" type="button" style="visibility: hidden;">
        <i class="fas fa-fw fa-external-link-square-alt"></i>
      </a>
    </td>
  </tr>
</table>
@endslot

@slot('pie')
<button data-js-finalizar-validacion data-js-modo="Validar" data-js-enabled="Validar" type="button" class="btn btn-successAceptar" style="position:absolute;left:20px;">VISAR RELEVAMIENTO</button>
<button data-js-finalizar-carga      data-js-modo="Cargar"  data-js-enabled="Cargar"  type="button" class="btn btn-warningModificar" style="position:absolute;left:20px;">FINALIZAR RELEVAMIENTO</button>
<button data-js-guardar              data-js-modo="Cargar"  data-js-enabled="Cargar"  type="button" class="btn btn-successAceptar">GUARDAR TEMPORALMENTE</button>
<button data-js-salir type="button" class="btn btn-default">SALIR</button>
<div data-js-modo="Cargar" data-js-enabled="Cargar" data-js-mensaje-salida hidden>
  <br>
  <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">CAMBIOS SIN GUARDAR</span>
  <br>
  <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Presione SALIR nuevamente para salir sin guardar cambios.</span>
  <br>
  <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Presione GUARDAR TEMPORALMENTE para guardar los cambios.</span>
</div>
@endslot

@endcomponent
