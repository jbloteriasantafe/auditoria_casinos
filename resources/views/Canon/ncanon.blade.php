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
<style>
  .tabs {
    width: 100%;
    display: flex;
    margin-bottom: 10px;
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
    border: 1px solid transparent;
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
    cursor: pointer;
  }
  .tabs a.active {
    color: #555;
    cursor: default;
    border-color: rgb(221, 221, 221);
  }
</style>
@endsection
@section('contenidoVista')

<div class="row">
  <div class="tabs" data-js-tabs="">
    <div>
      <a data-js-tab="#pant_total">Total</a>
    </div>
    <div>
      <a data-js-tab="#pant_maquinas">Maquinas</a>
    </div>
    <div>
      <a data-js-tab="#pant_bingo">Bingo</a>
    </div>
    <div>
      <a data-js-tab="#pant_jol">JOL</a>
    </div>
    <div>
      <a data-js-tab="#pant_mesas">Mesas</a>
    </div>
    <div>
      <a data-js-tab="#pant_mesas_adicionales">Mesas Adicionales</a>
    </div>
    <div>
      <a data-js-tab="#pant_canon_antiguo">Canon Antiguo</a>
    </div>
    <div>
      <a data-js-tab="#pant_defecto">Valores por Defecto</a>
    </div>
  </div>
</div>

<div id="pant_total" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    Total
    @endslot
    
    @slot('target_buscar')
    /Ncanon/total
    @endslot
    
    @slot('filtros')
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>AÑO MES</th>
      <th>CASINO</th>
      <th>BRUTO</th>
      <th>% DE SEGURIDAD</th>
      <th>DEVENGADO</th>
      <th>FECHA PAGO</th>
      <th>PAGADO</th>
      <th>DEDUCCION</th>
      <th>i% MORA</th>
      <th>MORA</th>
      <th>ESTADO</th>
      <th>ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr>
      <td class="año_mes">AÑO MES</td>
      <td class="casino">CASINO</td>
      <td class="bruto">BRUTO</td>
      <td class="porcentaje_seguridad">% DE SEGURIDAD</td>
      <td class="devengado">DEVENGADO</td>
      <td class="fecha_pago">FECHA PAGO</td>
      <td class="pago">PAGO</td>
      <td class="deduccion">DEDUCCION</td>
      <td class="interes_mora">i% MORA</td>
      <td class="mora">MORA</td>
      <td class="estado">ESTADO</td>
      <td>
        <button class="btn" type="button">VER</button>
        <button class="btn" type="button">EDITAR</button>
        <button class="btn" type="button" data-js-borrar="/Ncanon/borrar" data-table-id="">BORRAR</button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<div id="pant_maquinas" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
   <div>Maquinas</div>
    <form style="display: grid;grid-template-columns: repeat(4, 1fr);grid-template-rows: repeat(2, 1fr);grid-column-gap: 0px;grid-row-gap: 0px; ">
      <div>
        <h5>AÑO-MES</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="año_mes" placeholder="AÑO MES"',
          'attrs_dtp' => 'data-date-format="yyyy-mm-01" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      
      <div>
        <h5>CASINO</h5>
        <select class="form-control" name="casino">
          <option value="">-SELECCIONE-</option>
          @foreach($casino_mesas as $c => $vals)
          <option value="{{$c}}">{{$c}}</option>
          @endforeach
        </select>
      </div>
      
      <div>
        <h5>BRUTO</h5>
        <input class="form-control" name="bruto" placeholder="BRUTO">
      </div>
      
      <div>
        <h5>ALiCUOTA</h5>
        <input class="form-control" name="alicuota" placeholder="ALICUOTA">
      </div>
            
      <div>
        <h5>&nbsp;</h5>
        <button class="btn" type="button" data-js-enviar="/Ncanon/maquinas/ingresar">INGRESAR</button>
      </div>
    </form>
    @endslot
    
    @slot('target_buscar')
    /Ncanon/maquinas
    @endslot
    
    @slot('filtros')
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>CASINO</th>
      <th>AÑO MES</th>
      <th>BRUTO</th>
      <th>ALICUOTA</th>
      <th>TOTAL</th>
      <th>ESTADO</th>
      <th>ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr>
      <td class="casino">CASINO</td>
      <td class="año_mes">AÑO MES</td>
      <td class="bruto">BRUTO</td>
      <td class="alicuota">ALICUOTA</td>
      <td class="total">TOTAL</td>
      <td class="estado">ESTADO</td>
      <td>
        <button class="btn" type="button" data-js-borrar="/Ncanon/maquinas/borrar" data-table-id="id_canon_maquinas">BORRAR</button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<div id="pant_bingo" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    <div>Bingo</div>
    <form style="display: grid;grid-template-columns: repeat(4, 1fr);grid-template-rows: repeat(2, 1fr);grid-column-gap: 0px;grid-row-gap: 0px; ">
      <div>
        <h5>AÑO-MES</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="año_mes" placeholder="AÑO MES"',
          'attrs_dtp' => 'data-date-format="yyyy-mm-01" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      
      <div>
        <h5>CASINO</h5>
        <select class="form-control" name="casino">
          <option value="">-SELECCIONE-</option>
          @foreach($casino_mesas as $c => $vals)
          <option value="{{$c}}">{{$c}}</option>
          @endforeach
        </select>
      </div>
      
      <div>
        <h5>BRUTO</h5>
        <input class="form-control" name="bruto" placeholder="BRUTO">
      </div>
      
      <div>
        <h5>ALiCUOTA</h5>
        <input class="form-control" name="alicuota" placeholder="ALICUOTA">
      </div>
            
      <div>
        <h5>&nbsp;</h5>
        <button class="btn" type="button" data-js-enviar="/Ncanon/bingo/ingresar">INGRESAR</button>
      </div>
    </form>
    @endslot
    
    @slot('target_buscar')
    /Ncanon/bingo
    @endslot
    
    @slot('filtros')
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>CASINO</th>
      <th>AÑO MES</th>
      <th>BRUTO</th>
      <th>ALICUOTA</th>
      <th>TOTAL</th>
      <th>ESTADO</th>
      <th>ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr>
      <td class="casino">PLATAFORMA</td>
      <td class="año_mes">AÑO MES</td>
      <td class="bruto">BRUTO</td>
      <td class="alicuota">ALICUOTA</td>
      <td class="total">TOTAL</td>
      <td class="estado">ESTADO</td>
      <td>
        <button class="btn" type="button" data-js-borrar="/Ncanon/bingo/borrar" data-table-id="id_canon_bingo">BORRAR</button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<div id="pant_jol" hidden>
  @component('Components/FiltroTabla')
  @slot('titulo')
   <div>Juegos Online</div>
    <form style="display: grid;grid-template-columns: repeat(4, 1fr);grid-template-rows: repeat(2, 1fr);grid-column-gap: 0px;grid-row-gap: 0px; ">
      <div>
        <h5>AÑO-MES</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="año_mes" placeholder="AÑO MES"',
          'attrs_dtp' => 'data-date-format="yyyy-mm-01" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      
      <div>
        <h5>Plataforma</h5>
        <select class="form-control" name="plataforma">
          <option value="">-SELECCIONE-</option>
          @foreach($plataformas as $pidx => $p)
          <option value="{{$p->codigo}}">{{$p->codigo}}</option>
          @endforeach
        </select>
      </div>
      
      <div>
        <h5>BRUTO</h5>
        <input class="form-control" name="bruto" placeholder="BRUTO">
      </div>
      
      <div>
        <h5>ALiCUOTA</h5>
        <input class="form-control" name="alicuota" placeholder="ALICUOTA">
      </div>
            
      <div>
        <h5>&nbsp;</h5>
        <button class="btn" type="button" data-js-enviar="/Ncanon/jol/ingresar">INGRESAR</button>
      </div>
    </form>
    @endslot
    
    @slot('target_buscar')
    /Ncanon/jol
    @endslot
    
    @slot('filtros')
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>PLATAFORMA</th>
      <th>AÑO MES</th>
      <th>BRUTO</th>
      <th>ALICUOTA</th>
      <th>TOTAL</th>
      <th>ESTADO</th>
      <th>ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr>
      <td class="plataforma">PLATAFORMA</td>
      <td class="año_mes">AÑO MES</td>
      <td class="bruto">BRUTO</td>
      <td class="alicuota">ALICUOTA</td>
      <td class="total">total</td>
      <td class="estado">ESTADO</td>
      <td>
        <button class="btn" type="button" data-js-borrar="/Ncanon/jol/borrar" data-table-id="id_canon_jol">BORRAR</button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<div id="pant_mesas" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    <div>MESAS</div>
    <form data-js-formulario-ingreso style="display: grid;grid-template-columns: repeat(8, 1fr);grid-template-rows: repeat(2, 1fr);grid-column-gap: 0px;grid-row-gap: 0px; ">
      <div>
        <h5>AÑO-MES</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="año_mes" placeholder="AÑO MES"',
          'attrs_dtp' => 'data-date-format="yyyy-mm-01" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      
      <div>
        <h5>CASINO</h5>
        <select class="form-control" name="casino">
          <option value="">-SELECCIONE-</option>
          @foreach($casino_mesas as $c => $vals)
          <option value="{{$c}}">{{$c}}</option>
          @endforeach
        </select>
      </div>
      
      <div>
        <h5>F. COT.</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="fecha_cotizacion" placeholder="F. COT." data-depende="año_mes"',
          'form_group_attrs' => 'style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      
      <div>
        <h5>DIAS VALOR</h5>
        <input class="form-control" name="dias_valor" value="30" readonly>
      </div>
      
      <div>
        <h5>COT. DOL.</h5>
        <input class="form-control" name="cotizacion_dolar" placeholder="COT. DOLAR" data-depende="año_mes,fecha_cotizacion">
      </div>
            
      <div>
        <h5>VAL. DOL.</h5>
        <input class="form-control" name="valor_dolar" placeholder="VALOR DOLAR" data-depende="casino">
      </div>
      
      <div>
        <h5>COT. EUR.</h5>
        <input class="form-control" name="cotizacion_euro" placeholder="COT. EURO" data-depende="año_mes,fecha_cotizacion">
      </div>
      
      <div>
        <h5>VAL. EUR.</h5>
        <input class="form-control" name="valor_euro" placeholder="VALOR EURO" data-depende="casino">
      </div>
      
      <div>
        <h5>VAL. DIARIO DOL.</h5>
        <input class="form-control" name="valor_diario_dolar" readonly>
      </div>
      <div>
        <h5>VAL. DIARIO EURO.</h5>
        <input class="form-control" name="valor_diario_euro" readonly>
      </div>
      
      <div>
        <h5>DIAS-MESAS L-J</h5>
        <div style="display: flex;border: 1px solid grey;">
          <input class="form-control" name="dias_lunes_jueves" placeholder="DIAS" data-depende="año_mes">
          <input class="form-control" name="mesas_lunes_jueves" placeholder="MESAS">
        </div>
      </div>
      <div>
        <h5>DIAS-MESAS V-S</h5>
        <div style="display: flex;border: 1px solid grey;">
          <input class="form-control" name="dias_viernes_sabados" placeholder="DIAS" data-depende="año_mes">
          <input class="form-control" name="mesas_viernes_sabados" placeholder="MESAS">
        </div>
      </div>
      <div>
        <h5>DIAS-MESAS Dom</h5>
        <div style="display: flex;border: 1px solid grey;">
          <input class="form-control" name="dias_domingos" placeholder="DIAS" data-depende="año_mes">
          <input class="form-control" name="mesas_domingos" placeholder="MESAS">
        </div>
      </div>  
      <div>
        <h5>TODOS LOS DIAS</h5>
        <div style="display: flex;border: 1px solid grey;">
          <input class="form-control" name="dias_todos" placeholder="DIAS" data-depende="año_mes">
          <input class="form-control" name="mesas_todos" placeholder="MESAS">
        </div>
      </div>      
      
      <div>
        <h5>TOTAL DÓLAR</h5>
        <input class="form-control" name="total_dolar" readonly>
      </div>
      <div>
        <h5>TOTAL EURO</h5>
        <input class="form-control" name="total_euro" readonly>
      </div>
      
      <div>
        <h5>TOTAL</h5>
        <input class="form-control" name="total" readonly>
      </div>
      
      <div>
        <h5>&nbsp;</h5>
        <button class="btn" type="button" data-js-recalcular="/Ncanon/mesas/recalcular" style="display: none;">RELLENAR</button>
        <button class="btn" type="button" data-js-enviar="/Ncanon/mesas/ingresar">INGRESAR</button>
      </div>
    </form>
    @endslot
    
    @slot('target_buscar')
    /Ncanon/mesas
    @endslot
    
    @slot('filtros')
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>AÑO MES</th>
      <th>CASINO</th>
      <th>ESTADO</th>
      <th>FECHA COTIZACIÓN</th>
      <th>COT. DÓLAR</th>
      <th>COT. EURO</th>
      <th>VALÓR DÓLAR</th>
      <th>VALÓR EURO</th>
      <th>D LJ</th>
      <th>M LJ</th>
      <th>D VS</th>
      <th>M VS</th>
      <th>D D</th>
      <th>M D</th>
      <th>D T</th>
      <th>M T</th>
      <th>TOTAL DÓLAR</th>
      <th>TOTAL EURO</th>
      <th>TOTAL</th>
      <th>ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr>
      <td class="año_mes">AÑO MES</td>
      <td class="casino">CASINO</td>
      <td class="estado">ESTADO</td>
      <td class="fecha_cotizacion">FECHA COTIZACIÓN</td>
      <td class="cotizacion_dolar">COT. DÓLAR</td>
      <td class="cotizacion_euro">COT. EURO</td>
      <td class="valor_dolar">VALÓR DÓLAR</td>
      <td class="valor_euro">VALÓR EURO</td>
      <td class="dias_lunes_jueves">D LJ</td>
      <td class="mesas_lunes_jueves">M LJ</td>
      <td class="dias_viernes_sabados">D VS</td>
      <td class="mesas_viernes_sabados">M VS</td>
      <td class="dias_domingos">D D</td>
      <td class="mesas_domingos">M D</td>
      <td class="dias_todos">D T</td>
      <td class="mesas_todos">M T</td>
      <td class="total_dolar">TOTAL DÓLAR</td>
      <td class="total_euro">TOTAL EURO</td>
      <td class="total">TOTAL</td>
      <td>
        <button class="btn" type="button" data-js-borrar="/Ncanon/mesas/borrar" data-table-id="id_canon_mesas_v2">BORRAR</button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<div id="pant_mesas_adicionales" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    <div>MESAS ADICIONALES</div>
    <form style="display: grid;grid-template-columns: repeat(7, 1fr);grid-template-rows: repeat(2, 1fr);grid-column-gap: 0px;grid-row-gap: 0px; ">
      @component('Components/inputFecha',[
        'attrs' => 'name="año_mes" placeholder="AÑO MES" data-js-cambio-asignar-valor="#pant_canon_antiguo [name=\'fecha_vencimiento\'],#pant_canon_antiguo [name=\'fecha_pago\']"',
        'attrs_dtp' => 'data-date-format="yyyy-mm-01" data-start-view="year" data-min-view="decade"',
        'form_group_attrs' => 'style="padding: 0 !important;"'
      ])
      @endcomponent
      <select class="form-control" name="casino" 
        data-js-cambio-mostrar-tipos-mesas="#pant_mesas_adicionales [data-js-tipos-mesas]">
        @foreach($casino_tipo_mesas_adicionales as $c => $tipos_mesas_adicionales)
        <option value="{{$c}}">{{$c}}</option>
        @endforeach
      </select>
      
      @foreach($casino_tipo_mesas_adicionales as $c => $tipos_mesas_adicionales)
      <select class="form-control" name="tipo"
        data-js-tipos-mesas
        data-casino="{{$c}}"
        data-js-cambio-asignar-valor-hora="#pant_mesas_adicionales [name='valor_hora']"
        data-js-cambio-asignar-porcentaje="#pant_mesas_adicionales [name='porcentaje']"
        hidden>
        @foreach($tipos_mesas_adicionales as $t => $tobj)
        <option value="{{$t}}" data-valor-hora="{{$tobj['valor_hora'] ?? ''}}"  data-porcentaje="{{$tobj['porcentaje'] ?? ''}}">{{$t}}</option>
        @endforeach
      </select>
      @endforeach
            
      <input class="form-control" name="valor_hora" placeholder="VALOR HORA">
      <input class="form-control" name="horas" placeholder="HORAS">
      <input class="form-control" name="mesas" placeholder="MESAS">
      <input class="form-control" name="porcentaje" placeholder="%">
      <div>
        <button class="btn" type="button" data-js-enviar="/Ncanon/mesasAdicionales/ingresar">INGRESAR</button>
      </div>
    </form>
    @endslot
    
    @slot('target_buscar')
    /Ncanon/mesasAdicionales
    @endslot
    
    @slot('filtros')
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>AÑO MES</th>
      <th>CASINO</th>
      <th>TIPO</th>
      <th>VALOR HORA</th>
      <th>HORAS</th>
      <th>MESAS</th>
      <th>PORCENTAJE</th>
      <th>TOTAL</th>
      <th>ESTADO</th>
      <th>ACCION</th>
    </tr>
    @endslot
    
    @slot('molde')
    <tr>
      <td class="año_mes">AÑO MES</td>
      <td class="casino">CASINO</td>
      <td class="tipo">TIPO</td>
      <td class="valor_hora">VALOR HORA</td>
      <td class="horas">HORAS</td>
      <td class="mesas">MESAS</td>
      <td class="porcentaje">PORCENTAJE</td>
      <td class="total">total</td>
      <td class="estado">ESTADO</td>
      <td>
        <button class="btn" type="button" data-js-borrar="/Ncanon/mesasAdicionales/borrar" data-table-id="id_canon_mesas_adicionales">BORRAR</button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<div id="pant_defecto" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    <div>VALORES POR DEFECTO</div>
    <form style="display: flex;width: 50%;">
      <input class="form-control" name="campo" placeholder="Campo">
      <button class="btn" type="button" data-js-enviar="/Ncanon/valoresPorDefecto/borrar">BORRAR</button>
      <input class="form-control" name="valor" placeholder="Valor">
      <button class="btn" type="button" data-js-enviar="/Ncanon/valoresPorDefecto/ingresar">INGRESAR</button>
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
      <td class="valor">-VALOR-</td>
      <td>
        <button class="btn" type="button" data-js-borrar="/Ncanon/valoresPorDefecto/borrar" data-table-id="id_canon_valor_por_defecto">BORRAR</button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<div id="pant_canon_antiguo" hidden>
  @component('Components/FiltroTabla')
    @slot('titulo')
    <div>Canon Antiguo</div>
    <form style="display: grid;grid-template-columns: repeat(7, 1fr);grid-template-rows: repeat(2, 1fr);grid-column-gap: 0px;grid-row-gap: 0px; ">
      <div>
        <h5>AÑO MES</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="año_mes" placeholder="AÑO MES" data-js-cambio-asignar-valor="#pant_canon_antiguo [name=\'fecha_vencimiento\'],#pant_canon_antiguo [name=\'fecha_pago\']"',
          'attrs_dtp' => 'data-date-format="yyyy-mm-01" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      <div>
        <h5>CASINO</h5>
        <select class="form-control" name="casino">
          @foreach($casinos as $c)
          <option value="{{$c->codigo}}">{{$c->codigo}}</option>
          @endforeach
          @foreach($plataformas as $p)
          <option value="{{$p->codigo}}">{{$p->codigo}}</option>
          @endforeach
        </select>
      </div>
      <div>
        <h5>BRUTO</h5>
        <input class="form-control" name="bruto" placeholder="BRUTO">
      </div>
      <div>
        <h5>DEVENGADO</h5>
        <input class="form-control" name="devengado" placeholder="DEVENGADO">
      </div>
      <div>
        <h5>F. VENC</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="fecha_vencimiento" placeholder="F. VENC."',
          'form_group_attrs' => 'style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      <div>
        <h5>F. PAGO</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="fecha_pago" placeholder="F. PAGO"',
          'form_group_attrs' => 'style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      <div>
        <h5>PAGADO</h5>
        <input class="form-control" name="pagado" placeholder="PAGADO">
      </div>
      <div>
        <button class="btn" type="button" data-js-enviar="/Ncanon/antiguo/ingresar">INGRESAR</button>
      </div>
    </form>
    @endslot
    
    @slot('target_buscar')
    /Ncanon/antiguo
    @endslot
    
    @slot('filtros')
    @endslot
    
    @slot('cabecera')
    <tr>
      <th>AÑO MES</th>
      <th>CASINO</th>
      <th>BRUTO</th>
      <th>DEVENGADO</th>
      <th>% DE SEGURIDAD</th>
      <th>FECHA VENCIMIENTO</th>
      <th>FECHA PAGO</th>
      <th>PAGADO</th>
      <th>DEDUCCION</th>
      <th>MORA</th>
      <th>i% MORA</th>
      <th>ACCIÓN</th>
    </tr>
    @endslot

    @slot('molde')
    <tr>
      <td class="año_mes">AÑO MES</td>
      <td class="casino">CASINO</td>
      <td class="bruto">BRUTO</td>
      <td class="devengado">DEVENGADO</td>
      <td class="porcentaje_seguridad">% DE SEGURIDAD</td>
      <td class="fecha_vencimiento">FECHA VENCIMIENTO</td>
      <td class="fecha_pago">FECHA PAGO</td>
      <td class="pagado">PAGADO</td>
      <td class="deduccion">DEDUCCION</td>
      <td class="mora">MORA</td>
      <td class="interes_mora">i% MORA</td>
      <td>
        <button class="btn" type="button" data-js-borrar="/Ncanon/antiguo/borrar" data-table-id="id_canon_antiguo">BORRAR</button>
      </td>
    </tr>
    @endslot
  @endcomponent
</div>

<style>
  .VerCargarCanon h5 {
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
  <form style="display: flex;flex-direction: column;" data-css-id_casino="" data-js-recalcular="/Ncanon/recalcular">
    <div style="width: 100%;display: flex;">
      <div>
        <h5>AÑO MES</h5>
        @component('Components/inputFecha',[
          'attrs' => 'name="año_mes" placeholder="AÑO MES"  data-js-cambio-limpiar-canon-variable data-js-cambio-limpiar-canon-fijo-mesas-adicionales',
          'attrs_dtp' => 'data-date-format="yyyy-mm-01" data-start-view="year" data-min-view="decade"',
          'form_group_attrs' => 'style="padding: 0 !important;"'
        ])
        @endcomponent
      </div>
      <div>
        <h5>Casino</h5>
        <select class="form-control" name="id_casino" data-js-cambio-limpiar-canon-variable data-js-cambio-limpiar-canon-fijo-mesas-adicionales>
          <option value="" selected>- SELECCIONE -</option>
          @foreach($casinos as $c)
          <option value="{{$c->id_casino}}">{{$c->nombre}}</option>
          @endforeach
        </select>
      </div>
      <div>
        <h5>Estado</h5>
        <input class="form-control" name="estado" readonly>
      </div>
    </div>
    <div style="height: 70vh;overflow-y: scroll;">
      <h6>Canon Variable</h6>
      <div data-js-canon-variable>
      </div>
      <div data-js-molde-canon-variable="$cv" hidden>
        <h6 data-titulo>TITULO CANON VARIABLE<h6>
        <div style="width: 50%;" >
          <div style="display: flex;">
            <div style="flex: 1;">
              <h5>&nbsp;</h5>
              <input class="form-control"  style="opacity: 0;">
            </div>
            <div style="flex: 1;">
              <h5>APOSTADO SISTEMA</h5>
              <input class="form-control" data-name="canon_variable[$cv][apostado_sistema]">
            </div>
            <div style="flex: 1;">
              <h5>APOSTADO INFORMADO</h5>
              <input class="form-control" data-name="canon_variable[$cv][apostado_informado]">
            </div>
          </div>
          <div style="display: flex;">
            <div class="parametro_chico"  style="flex: 1;">
              <h5>APLICABLE (%)</h5>
              <input class="form-control" data-name="canon_variable[$cv][apostado_porcentaje_aplicable]" data-depende="id_casino">
            </div>
            <div style="flex: 1;">
              <h5>BASE IMPONIBLE (DEVENGADO)</h5>
              <input class="form-control" data-name="canon_variable[$cv][base_imponible_devengado]" data-depende="canon_variable[$cv][apostado_sistema],canon_variable[$cv][apostado_porcentaje_aplicable]"  readonly>
            </div>
            <div style="flex: 1;">
              <h5>BASE IMPONIBLE (A PAGAR)</h5>
              <input class="form-control" data-name="canon_variable[$cv][base_imponible_pagar]" data-depende="canon_variable[$cv][apostado_informado],canon_variable[$cv][apostado_porcentaje_aplicable]"  readonly>
            </div>
          </div>
          <div style="display: flex;">
            <div class="parametro_chico" style="flex: 1;">
              <h5>IMPUESTO LEY (%)</h5>
              <input class="form-control" data-name="canon_variable[$cv][apostado_porcentaje_impuesto_ley]" data-depende="id_casino">
            </div>
            <div style="flex: 1;">
              <h5>IMPUESTO (DEVENGADO)</h5>
              <input class="form-control" data-name="canon_variable[$cv][impuesto_devengado]" data-depende="canon_variable[$cv][base_imponible_devengado],canon_variable[$cv][apostado_porcentaje_impuesto_ley]" readonly>
            </div>
            <div style="flex: 1;">
              <h5>IMPUESTO (A PAGAR)</h5>
              <input class="form-control" data-name="canon_variable[$cv][impuesto_pagar]" data-depende="canon_variable[$cv][base_imponible_pagar],canon_variable[$cv][apostado_porcentaje_impuesto_ley]" readonly>
            </div>
          </div>
          <div style="display: flex;">
            <div style="flex: 1;">
              <h5>BRUTO</h5>
              <input class="form-control" data-name="canon_variable[$cv][bruto]" data-depende="id_casino">
            </div>
            <div style="flex: 1;">
              <h5>SUBTOTAL (DEVENGADO)</h5>
              <input class="form-control" data-name="canon_variable[$cv][subtotal_devengado]" data-depende="canon_variable[$cv][bruto],canon_variable[$cv][impuesto_devengado]" readonly>
            </div>
            <div style="flex: 1;">
              <h5>SUBTOTAL (A PAGAR)</h5>
              <input class="form-control" data-name="canon_variable[$cv][subtotal_pagar]" data-depende="canon_variable[$cv][bruto],canon_variable[$cv][impuesto_pagar]" readonly>
            </div>
          </div>
          <div style="display: flex;">
            <div class="parametro_chico" style="flex: 1;">
              <h5>ALICUOTA (%)</h5>
              <input class="form-control" data-name="canon_variable[$cv][alicuota]" data-depende="id_casino">
            </div>
            <div style="flex: 1;">
              <h5>TOTAL (DEVENGADO)</h5>
              <input class="form-control" readonly data-name="canon_variable[$cv][total_devengado]" data-depende="canon_variable[$cv][subtotal_devengado],canon_variable[$cv][alicuota]">
            </div>
            <div style="flex: 1;">
              <h5>TOTAL (A PAGAR)</h5>
              <input class="form-control" readonly data-name="canon_variable[$cv][total_pagar]" data-depende="canon_variable[$cv][subtotal_pagar],canon_variable[$cv][alicuota]">
            </div>
          </div>
        </div>  
      </div>
      <hr>
      <h6>Canon Fijo - Mesas</h6>
      <div style="width: 100%;">
        <div style="display: flex;">
          <div>
            <h5>F. COTIZACIÓN</h5>
            @component('Components/inputFecha',[
              'attrs' => 'name="canon_fijo[mesas][fecha_cotizacion]" data-depende="año_mes"',
              'form_group_attrs' => 'style="padding: 0 !important;"'
            ])
            @endcomponent
          </div>
          <div>
            <h5>COTIZACIÓN DOLAR</h5>
            <input class="form-control" name="canon_fijo[mesas][cotizacion_dolar]" data-depende="canon_fijo[mesas][fecha_cotizacion]">
          </div>
          <div>
            <h5>COTIZACIÓN EURO</h5>
            <input class="form-control" name="canon_fijo[mesas][cotizacion_euro]" data-depende="canon_fijo[mesas][fecha_cotizacion]">
          </div>
        </div>
        <div style="display: flex;">
          <div>
            <h5>VALOR DOLAR</h5>
            <input class="form-control" name="canon_fijo[mesas][valor_dolar]" data-depende="id_casino">
          </div>
          <div>
            <h5>VALOR EURO</h5>
            <input class="form-control" name="canon_fijo[mesas][valor_euro]" data-depende="id_casino">
          </div>
          <div class="parametro_chico">
            <h5>DIAS VALOR</h5>
            <input class="form-control" name="canon_fijo[mesas][dias_valor]">
          </div>
          <div>
            <h5>VALOR DIARIO DOLAR</h5>
            <input class="form-control" name="canon_fijo[mesas][valor_diario_dolar]" data-depende="canon_fijo[mesas][valor_dolar],canon_fijo[mesas][dias_valor]" readonly>
          </div>
          <div>
            <h5>VALOR DIARIO EURO</h5>
            <input class="form-control" name="canon_fijo[mesas][valor_diario_euro]"  data-depende="canon_fijo[mesas][valor_euro],canon_fijo[mesas][dias_valor]" readonly>
          </div>
        </div>
        <div style="display: flex;">
          <div>
            <h5>DIAS-MESAS L-J</h5>
            <div style="display: flex;flex-direction: column;border: 1px solid grey;">
              <input class="form-control" name="canon_fijo[mesas][dias_lunes_jueves]" placeholder="DIAS" data-depende="año_mes">
              <input class="form-control" name="canon_fijo[mesas][mesas_lunes_jueves]" placeholder="MESAS">
            </div>
          </div>
          <div>
            <h5>DIAS-MESAS V-S</h5>
            <div style="display: flex;flex-direction: column;border: 1px solid grey;">
              <input class="form-control" name="canon_fijo[mesas][dias_viernes_sabados]" placeholder="DIAS" data-depende="año_mes">
              <input class="form-control" name="canon_fijo[mesas][mesas_viernes_sabados]" placeholder="MESAS">
            </div>
          </div>               
          <div>
            <h5>DIAS-MESAS Dom</h5>
            <div style="display: flex;flex-direction: column;border: 1px solid grey;">
              <input class="form-control" name="canon_fijo[mesas][dias_domingos]" placeholder="DIAS" data-depende="año_mes">
              <input class="form-control" name="canon_fijo[mesas][mesas_domingos]" placeholder="MESAS">
            </div>
          </div>
          <div>
            <h5>DIAS-MESAS Todos</h5>
            <div style="display: flex;flex-direction: column;border: 1px solid grey;">
              <input class="form-control" name="canon_fijo[mesas][dias_todos]" placeholder="DIAS" data-depende="año_mes">
              <input class="form-control" name="canon_fijo[mesas][mesas_todos]" placeholder="MESAS">
            </div>
          </div>
        </div>
        <div style="display: flex;">
          <div>
            <h5>TOTAL DOLAR</h5>
            <input class="form-control" name="canon_fijo[mesas][total_dolar]" data-depende="canon_fijo[mesas][valor_diario_dolar],canon_fijo[mesas][dias_lunes_jueves],canon_fijo[mesas][mesas_lunes_jueves],canon_fijo[mesas][dias_viernes_sabados],canon_fijo[mesas][mesas_viernes_sabados],canon_fijo[mesas][dias_domingos],canon_fijo[mesas][mesas_domingos],canon_fijo[mesas][dias_todos],canon_fijo[mesas][mesas_todos]" readonly>
          </div>
          <div>
            <h5>TOTAL EURO</h5>
            <input class="form-control" name="canon_fijo[mesas][total_euro]" data-depende="canon_fijo[mesas][valor_diario_euro],canon_fijo[mesas][dias_lunes_jueves],canon_fijo[mesas][mesas_lunes_jueves],canon_fijo[mesas][dias_viernes_sabados],canon_fijo[mesas][mesas_viernes_sabados],canon_fijo[mesas][dias_domingos],canon_fijo[mesas][mesas_domingos],canon_fijo[mesas][dias_todos],canon_fijo[mesas][mesas_todos]"  readonly>
          </div>
          <div>
            <h5>TOTAL (DEVENGADO)</h5>
            <input class="form-control" name="canon_fijo[mesas][total_devengado]" data-depende="canon_fijo[mesas][total_dolar],canon_fijo[mesas][total_euro]" readonly>
          </div>
          <div>
            <h5>TOTAL (A PAGAR)</h5>
            <input class="form-control" name="canon_fijo[mesas][total_pagar]" data-depende="canon_fijo[mesas][total_dolar],canon_fijo[mesas][total_euro]" readonly>
          </div>
        </div>
      </div>
      <hr>
      <h6>Canon Fijo - Mesas Adicionales</h6>
      <div style="width: 100%;" data-js-canon-fijo-mesas-adicionales>
      </div>
      <?php
        $n = function($s) use (&$id_casino,&$t){
          return "canon_fijo[mesas_adicionales][\$ma][$s]";
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
      <div data-js-molde-canon-fijo-mesas-adicionales="$ma" hidden>
        <h4 data-titulo>TITULO MESA ADICIONAL</h4>
        <div style="display: flex;">
          <div>
            <h5>VALOR MENSUAL</h5>
            <input class="form-control" data-name="{{$valor_mensual}}" data-depende="id_casino">
          </div>
          <div class="parametro_chico">
            <h5>DIAS MES</h5>
            <input class="form-control" data-name="{{$dias_mes}}" data-depende="id_casino">
          </div>
          <div>
            <h5>VALOR DIARIO</h5>
            <input class="form-control" data-name="{{$valor_diario}}" data-depende="{{$valor_mensual}},{{$dias_mes}}" readonly>
          </div>
          <div class="parametro_chico">
            <h5>HORAS DÍAS</h5>
            <input class="form-control" data-name="{{$horas_dia}}"  data-depende="id_casino">
          </div>
          <div>
            <h5>VALOR HORA</h5>
            <input class="form-control" data-name="{{$valor_hora}}" data-depende="{{$valor_diario}},{{$horas_dia}}"  readonly>
          </div>
        </div>
        <div style="display: flex;">
          <div>
            <h5>HORAS</h5>
            <input class="form-control" data-name="{{$horas}}">
          </div>
          <div>
            <h5>MESAS</h5>
            <input class="form-control" data-name="{{$mesas}}">
          </div>
          <div class="parametro_chico">
            <h5>PORCENTAJE</h5>
            <input class="form-control" data-name="{{$porcentaje}}" data-depende="id_casino">
          </div>
        </div>
        <div style="display: flex;">
          <div>
            <h5>TOTAL (DEVENGADO)</h5>
            <input class="form-control" data-name="{{$total_devengado}}" data-depende="{{$horas}},{{$mesas}},{{$porcentaje}}" readonly>
          </div>
          <div>
            <h5>TOTAL (PAGAR)</h5>
            <input class="form-control" data-name="{{$total_pagar}}" data-depende="{{$horas}},{{$mesas}},{{$porcentaje}}" readonly>
          </div>
        </div>
      </div>
      <hr>
      <h6>Total</h6>
      <div style="width: 100%;display: flex;">
        <div>
          <h5>Bruto (DEVENGADO)</h5>
          <input class="form-control" name="bruto_devengado" readonly>
        </div>
        <div>
          <h5>Deducción</h5>
          <input class="form-control" name="deduccion">
        </div>
        <div>
          <h5>Devengado</h5>
          <input class="form-control" name="devengado" readonly>
        </div>
        <div class="parametro_chico">
          <h5>Porcentaje Seguridad</h5>
          <input class="form-control" name="porcentaje_seguridad" readonly>
        </div>
      </div>
      <div style="width: 100%;display: flex;">
        <div>
          <h5>F. Vencimiento</h5>
          @component('Components/inputFecha',[
            'attrs' => 'name="fecha_vencimiento" data-depende="año_mes"',
            'form_group_attrs' => 'style="padding: 0 !important;"'
          ])
          @endcomponent
        </div>
        <div>
          <h5>F. Pago</h5>
          @component('Components/inputFecha',[
            'attrs' => 'name="fecha_pago" data-depende="año_mes"',
            'form_group_attrs' => 'style="padding: 0 !important;"'
          ])
          @endcomponent
        </div>
      </div>
      <div style="width: 100%;display: flex;">
        <div>
          <h5>Bruto (A PAGAR)</h5>
          <input class="form-control" name="bruto_pagado" readonly>
        </div>
        <div>
          <h5>Interes Mora</h5>
          <input class="form-control" name="interes_mora" data-depende="pago,mora,fecha_pago,fecha_vencimiento">
        </div>
        <div>
          <h5>Mora</h5>
          <input class="form-control" name="mora" data-depende="interes_mora,pago,fecha_pago,fecha_vencimiento">
        </div>
        <div>
          <h5>Pago</h5>
          <input class="form-control" name="pago" data-depende="interes_mora,mora,fecha_pago,fecha_vencimiento">
        </div>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
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

  <script src="js/Canon/ncanon.js" charset="utf-8" type="module"></script>

@endsection
