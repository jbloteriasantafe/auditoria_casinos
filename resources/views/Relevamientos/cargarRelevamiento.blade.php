@component('Components/include_guard',['nombre' => 'modalCargarRelevamiento'])
<style>
  .modalCargarRelevamiento .tablaRelevamiento thead tr th {
    text-align: center;
  }
  
  .modalCargarRelevamiento tr[data-css-colorear] [data-js-detalle-asignar-name="diferencia"] {
    border-width: 2px;
    border-style: solid;
  }
  
  .modalCargarRelevamiento tr[data-css-colorear="DIFERENCIA"] [data-js-detalle-asignar-name="diferencia"],
  .modalCargarRelevamiento tr[data-css-colorear="NO_TOMA"] [data-js-detalle-asignar-name="diferencia"],
  .modalCargarRelevamiento tr[data-css-colorear="SIN_IMPORTAR"] [data-js-detalle-asignar-name="diferencia"] {
    border-color: #EF5350;
    color: #EF5350;
  }
  
  .modalCargarRelevamiento tr[data-css-colorear="CORRECTO"] [data-js-detalle-asignar-name="diferencia"] {
    border-color: #66BB6A;
    color: #66BB6A;
  }
  .modalCargarRelevamiento tr[data-css-colorear="TRUNCAMIENTO"] [data-js-detalle-asignar-name="diferencia"] {
    border-color: #FFA726;
    color: #FFA726;
  }
  
  .modalCargarRelevamiento tr[data-css-colorear="NO_TOMA"] [data-js-detalle-asignar-name="id_tipo_causa_no_toma"] {
    border: 2px solid #1E90FF;
    color: #1E90FF;
  }
  
  .modalCargarRelevamiento tr[data-css-colorear="DIFERENCIA"] [data-js-boton-medida],
  .modalCargarRelevamiento tr[data-css-colorear="DIFERENCIA"] [data-js-estadisticas-no-toma],
  .modalCargarRelevamiento tr[data-css-colorear="NO_TOMA"] [data-js-estadisticas-no-toma] {
    visibility: visible !important;
  }
  
  @foreach(['DIFERENCIA','NO_TOMA','SIN_IMPORTAR','CORRECTO','TRUNCAMIENTO'] as $e)
  .modalCargarRelevamiento tr[data-css-colorear="{{$e}}"] [data-js-icono-estado="{{$e}}"] {
    display: block !important;
  }
  @endforeach
  
  .modalCargarRelevamiento tr [data-contador]::placeholder {
    font-size: 90%;
  }
  
  .modalCargarRelevamiento tr .contador {
    text-align: right;
  }
  .modalCargarRelevamiento tr.sacar-hijos-borde-bottom > * {
    border-bottom: 0;
  }
  .modalCargarRelevamiento tr.sacar-hijos-borde-top > * {
    border-top: 0;
  }
  .modalCargarRelevamiento .color-gris {
    color: rgb(110, 110, 110);
  }
  
  .modalCargarRelevamiento[data-modo="Validar"] .td-contador,
  .modalCargarRelevamiento[data-modo="Validar"] .td-producido-calculado,
  .modalCargarRelevamiento[data-modo="Validar"] .td-producido-importado,
  .modalCargarRelevamiento[data-modo="Validar"] .td-diferencia,
  .modalCargarRelevamiento[data-modo="Validar"] .td-no-toma {
    width: 9.10%;
  }
  .modalCargarRelevamiento[data-modo="Validar"] .td-estado {
    width: 2%;
  } 
  .modalCargarRelevamiento[data-modo="Validar"] .td-estadisticas {
    width: 3.5%;
  }
  .modalCargarRelevamiento[data-modo="Validar"] .td-a-pedido {
    width: 4.5%;
  }
  
  .modalCargarRelevamiento[data-modo="Ver"] .td-contador,
  .modalCargarRelevamiento[data-modo="Ver"] .td-producido-calculado,
  .modalCargarRelevamiento[data-modo="Ver"] .td-producido-importado,
  .modalCargarRelevamiento[data-modo="Ver"] .td-diferencia,
  .modalCargarRelevamiento[data-modo="Ver"] .td-no-toma {
    width: 10%;
  }
  
  .modalCargarRelevamiento[data-modo="Cargar"] .td-contador,
  .modalCargarRelevamiento[data-modo="Cargar"] .td-no-toma {
    width: 13.5%;
  }
  .modalCargarRelevamiento[data-modo="Cargar"] .td-estado {
    width: 5.5%;
  }
  
  .modalCargarRelevamiento .tablaRelevamiento td.headerTabla {
    font-weight: bolder;
    text-align: left;
    background: rgb(221, 221, 221);
    text-shadow: 0px 0px 2px white;
    border-left: 1px solid rgb(236, 236, 236);
    border-right: 1px solid rgb(236, 236, 236);
  };
  
  .modalCargarRelevamiento .tablaRelevamiento td.centrado {
    display: flex;
  }
  
  .modalCargarRelevamiento .tablaRelevamiento td.centrado > div {
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  
  .modalCargarRelevamiento .tablaRelevamiento span.celda-ver {
    width: 100%;
    display: inline-block;
    background: white;
    border: 1px solid #ccc;
  }
  
  .modalCargarRelevamiento select[readonly] {
    pointer-events: none !important;
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
  'grande' => 94,
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
  <div class="row">
    <div class="col-md-12">
      <table class="tablaRelevamiento table" style="margin-bottom: 0;">
        <thead>
          <tr style="display: flex;">
            @for($c=1;$c<=$CONTADORES;$c++)
            <th data-js-modo="{{$c<=$CONTADORES_VISIBLES? 'Ver,Cargar,Validar' : ''}}" style="flex: 1;";>&nbsp;</th>
            @endfor
            <th data-js-modo="Cargar,Validar" style="flex: 0.5">&nbsp;</th>{{-- Estado --}}
            <th data-js-modo="Ver,Cargar,Validar" style="flex: 1;">&nbsp;</th>
            <th data-js-modo="Ver,Cargar,Validar" style="flex: 0.5;">&nbsp;</th>
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

<table hidden>
  <tr data-js-molde-tabla-relevamiento class="color-gris" style="display: flex;">
    <td class="headerTabla centrado" style="width: 15%;">
      <div>
        <span>MTM:&nbsp;</span>
      </div>
      <div style="flex: 1;">
        <span data-js-detalle-asignar-name="[maquina][nro_admin]"></span>
      </div>
    </td>
    <td class="headerTabla centrado" style="width: 15%;">
      <div style="display: flex;flex-direction: column;justify-content: center;">
        <span>ISLA:&nbsp;</span>
      </div>
      <div style="flex: 1;display: flex;flex-direction: column;justify-content: center;">
        <span data-js-detalle-asignar-name="[isla][nro_isla]"></span>
      </div>
    </td>
    <td class="headerTabla centrado" style="width: 26%;">
      <div>
        <span>MARCA:&nbsp;</span>
      </div>
      <div style="flex: 1;">
        <span data-js-detalle-asignar-name="[maquina][marca_juego]"></span>
      </div>
    </td>
    <td class="headerTabla centrado" style="width: 24%;">
      <div>
        <span>UNIDAD RELEVADA:&nbsp;</span>
      </div>
      <div style="flex: 1;">
        <select data-js-detalle-asignar-name="[detalle][denominacion]" class="form-control" data-js-readonly="Ver,Cargar" data-js-cambio-cambiar-denominacion="relevamientos/modificarDenominacionYUnidadDetalle" style="width: 100%;">
          <option value="1" data-id_unidad_medida="2">1 (MONEDA)</option>
          @foreach($denominaciones as $d)
          <option value="{{$d}}" data-id_unidad_medida="1">{{$d}} (CRED)</option>
          @endforeach
        </select>
      </div>
    </td>
    <td class="headerTabla centrado" style="width: 20%;">
      <div>
        <span>UNIDAD MTM:&nbsp;</span>
      </div>
      <div style="flex: 1;">
        <select data-js-detalle-asignar-name="[maquina][denominacion]" class="form-control" data-js-readonly="Ver,Cargar" data-js-cambio-cambiar-denominacion="relevamientos/modificarDenominacionYUnidadMTM" style="width: 100%;">
          <option value="1" data-id_unidad_medida="2">1 (MONEDA)</option>
          @foreach($denominaciones as $d)
          <option value="{{$d}}" data-id_unidad_medida="1">{{$d}} (CRED)</option>
          @endforeach
        </select>
      </div>
    </td>
  </tr>
  <tr data-js-molde-tabla-relevamiento class="sacar-hijos-borde-top" style="display: flex;">
    <td hidden>
      <input data-js-detalle-asignar-name="[detalle][id_detalle_relevamiento]">
      <input data-js-detalle-asignar-name="[maquina][id_maquina]">
      <input data-js-detalle-asignar-name="[isla][id_isla]">
      <input data-js-detalle-asignar-name="[formula][id_formula]">
    </td>
    @for($c=1;$c<=$CONTADORES;$c++)
    <td class="td-contador" style="text-align: right;" data-js-modo="{{$c<=$CONTADORES_VISIBLES? 'Ver,Cargar,Validar' : ''}}">
      <input data-contador data-js-enabled="Cargar" data-js-cambio-contador="{{$c}}" data-js-detalle-asignar-name="[detalle][cont{{$c}}]" data-js-focus-mostrar-formula class="contador cont{{$c}} form-control">
    </td>
    @endfor
    <td class="td-producido-calculado" style="text-align: right;" data-js-modo="Ver,Validar">
      <input data-js-readonly="Ver,Validar,Cargar" data-js-detalle-asignar-name="[detalle][producido_calculado_relevado]" class="producidoCalculado form-control" style="text-align: right; border: 2px solid rgb(109, 199, 190); color: rgb(109, 199, 190);">
    </td>
    <td class="td-producido-importado" style="text-align: right;" data-js-modo="Ver,Validar">
      <input data-js-readonly="Ver,Validar,Cargar" data-js-detalle-asignar-name="[detalle][producido_importado]" class="producido form-control" style="text-align: right; border: 2px solid rgb(109, 199, 190); color: rgb(109, 199, 190);">
    </td>
    <td class="td-diferencia" style="text-align: right;" data-js-modo="Ver,Validar">
      <input data-js-readonly="Ver,Validar,Cargar" data-js-detalle-asignar-name="[detalle][diferencia]" class="diferencia form-control" style="text-align: right;">
    </td>
    <td class="td-estado centrado" data-js-modo="Cargar,Validar" data-js-estado-diferencia style="text-align: center;" class="estado_diferencia">
      <div>
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
      </div>
    </td>
    <td class="td-no-toma" style="text-align: center;" data-js-modo="Ver,Cargar,Validar">
      <select data-js-readonly="Validar" data-js-cambio-tipo-causa-no-toma data-js-detalle-asignar-name="[detalle][id_tipo_causa_no_toma]" class="tipo_causa_no_toma form-control">
        <option value=""></option>
        @foreach($tipos_causa_no_toma as $t)
        <option value="{{$t->id_tipo_causa_no_toma}}" {{$t->deprecado? 'disabled' : ''}}>{{$t->descripcion}}</option>
        @endforeach
      </select>
    </td>
    <td class="td-a-pedido" data-js-modo="Validar">
      <select data-js-enabled="Validar" data-js-detalle-asignar-name="[a_pedido]" class="a_pedido form-control acciones_validacion">
        <option value="" selected>NO</option>
        <option value="1">1 día</option>
        <option value="5">5 días</option>
        <option value="10">10 días</option>
        <option value="15">15 días</option>
      </select>
    </td>
    <td class="td-estadisticas" data-js-modo="Validar">
      <a title="Estadisticas No Toma" href="/relevamientos/estadisticas_no_toma" target="_blank" data-js-enabled="Validar" data-js-estadisticas-no-toma class="btn btn-success acciones_validacion" type="button" style="visibility: hidden;">
        <i class="fas fa-fw fa-external-link-square-alt"></i>
      </a>
    </td>
  </tr>
  <tr data-js-molde-tabla-relevamiento class="sacar-hijos-borde-top color-gris" style="display: flex;">
    @for($c=1;$c<=$CONTADORES;$c++)
    <td class="td-contador" data-js-modo="{{$c<=$CONTADORES_VISIBLES? 'Ver,Cargar,Validar' : ''}}" style="padding-top: 0;">
      <span data-js-detalle-asignar-name="[formula][cont{{$c}}]"></span>
    </td>
    @endfor
    <td class="td-producido-calculado" data-js-modo="Ver,Validar" style="text-align: center;font-weight: bold;padding-top: 0;">P. CALCULADO ($)</td>
    <td class="td-producido-importado" data-js-modo="Ver,Validar" style="text-align: center;font-weight: bold;padding-top: 0;">P. IMPORTADO ($)</td>
    <td class="td-diferencia" data-js-modo="Ver,Validar" style="text-align: center;font-weight: bold;padding-top: 0;">DIFERENCIA</td>
    <td class="td-estado" data-js-modo="Cargar,Validar" style="padding-top: 0;">&nbsp;</td>
    <td class="td-no-toma" data-js-modo="Ver,Cargar,Validar" style="text-align: center;font-weight: bold;padding-top: 0;">NO TOMA</td>
    <td class="td-a-pedido" data-js-modo="Validar" style="text-align: center;font-weight: bold;padding-top: 0;">A PEDIDO</td>
    <td class="td-estadisticas" data-js-modo="Validar" style="text-align: center;font-weight: bold;padding-top: 0;">&nbsp;</td>
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
