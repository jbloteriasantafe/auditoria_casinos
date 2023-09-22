@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('contenidoVista')

@section('estilos')
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/paginacion.css">
<style>
  .hover_borde_naranja:hover{
    border: 1px solid orange;
  }
</style>
@endsection

<div class="row">
  <div class="col-md-3">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h4>VISTA</h4>
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-md-12">
            <h5>TABLA</h5>
            <select name="vista" class="form-control" data-js-cambio-vista>
              @foreach($vistas as $v)
              <option value="{{$v['nombre']}}">{{$v['nombre_fmt']}}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-12">
    @foreach($vistas as $v)
      <div data-vista="{{$v['nombre']}}" hidden>
      @component('Components/FiltroTabla')
      
      @slot('titulo')
      <span>{{$v['nombre_fmt']}}</span>
      <i class="fa fa-spinner fa-spin" data-js-cargando></i>
      <button data-js-descargar data-descargar-completo="0" class="btn btn-sucess" type="button" style="font-size: 0.9rem;"><i class="fa fa-arrow-circle-down"></i> .csv (página) <i class="fa fa-spinner fa-spin" data-js-descargando style="display: none;"></i></button>      
      <button data-js-descargar data-descargar-completo="1" class="btn btn-sucess" type="button" style="font-size: 0.9rem;"><i class="fa fa-arrow-circle-down"></i> .csv (completo) <i class="fa fa-spinner fa-spin" data-js-descargando style="display: none;"></i></button>      
      @endslot
      
      @slot('target_buscar')
      /backoffice/buscar
      @endslot
      
      @slot('filtros')
      <div class="row">
        <input name="vista" value="{{$v['nombre']}}" hidden>
      @foreach($v['columnas'] as $cidx => $c)
        @if(($cidx%3) == 0)
        <div class="row">
        @endif
        <div class="col-md-4">
          <h5>{{$c['nombre_fmt']}}</h5>
          <div style="display: flex;">
            @for($i=0;$i<count($c['default']);$i++)
              <?php 
                $idx  = "[$i]";
                $name = "{$c['nombre']}$idx";
                $tipo = $c['tipo'];
                $dflt = $c['default'][$i];
              ?>
              @if($tipo == 'select')
                <select class="form-control" style="flex: 1;" name="{{$name}}">
                  @foreach($c['valores'] as $val_idx => $val)
                  <option value="{{$val->id}}" {{ $dflt==$val_idx? 'selected' : '' }}>{{$val->valor}}</option>
                  @endforeach
                  <option value="" {{ ($dflt==-1 || $dflt>=count($c['valores']))? 'selected' : '' }}>- TODOS/AS -</option>
                </select>
              @elseif($tipo == 'input' || $tipo == 'input_vals_list')
                <input class="form-control" style="flex: 1;" name="{{$name}}">
              @elseif(strpos($tipo,'input_date:') === 0)
                @component('Components/inputFecha',[
                  'form_group_attrs' => 'style="flex: 1;"',
                  'attrs'            => "name={$name}"
                ])
                @endcomponent
              @elseif($tipo == 'input_date_month')
                @component('Components/inputFecha',[
                  'form_group_attrs' => 'style="flex: 1;"',
                  'attrs'            => "name='{$name}' value='{$dflt}'",
                  'attrs_dtp'        => 'data-date-format="yyyy-mm" data-start-view="year" data-min-view="year" data-max-view="decade"'
                ])
                @endcomponent
              @else
                <span>Formato incorrecto</span>
              @endif
            @endfor
          </div>
        </div>
        @if(($cidx%3) == 2 || $loop->last)
        </div>
        @endif
      @endforeach
      </div>
      @endslot
      
      @slot('cabecera')
      <tr>
        @foreach($v['columnas'] as $c)
        <th class="txt-center" data-js-sortable="{{$c['nombre']}}">{{$c['nombre_fmt']}}</th>
        @endforeach
      </tr>
      @endslot
      
      @slot('molde')
      <tr>
        @foreach($v['columnas'] as $c)
        <td class="txt-center {{$c['nombre']}}">{{$c['nombre_fmt']}}</td>
        @endforeach
      </tr>
      @endslot
      
      @endcomponent
      </div>
    @endforeach
  </div>
</div>
<meta name="_token" content="{!! csrf_token() !!}" />

@endsection

@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| Backoffice</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Relevamientos</h5>
  <p>
    Generación de CSVs
  </p>
</div>
@endsection

@section('scripts')
<!-- JS paginacion -->
<script src="/js/paginacion.js" charset="utf-8"></script>
<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<!-- Custom input Bootstrap -->
<script src="js/fileinput.min.js" type="text/javascript"></script>
<script src="js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>
<script src="js/seccionBackoffice.js" type="module"></script>
@endsection
