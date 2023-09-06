<?php
use App\Http\Controllers\AuthenticationController;
?>
@extends('includes.dashboard')

@section('headerLogo')

@endsection
@section('estilos')
<link href="css/bootstrap.min.css" rel="stylesheet"/>
<style>
  table.tablaResultados thead tr th {
    font-size:14px;
    text-align:center !important;
  }
  tr.filaResultado td {
    text-align:center !important;
  }
  section {
    padding: 0 !important;
  }
  .contenedor > nav {
    display: none;
  }
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

<?php   
  $tiene_permiso = function($p) use ($usuario){
    if(empty($p)) return true;
    return AuthenticationController::getInstancia()->usuarioTienePermiso($usuario->id_usuario,$p);
  };
  
  $tabs = [
    'aperturas' => [
      'botones' => [
        'Cargar Apertura' => '[data-js-cargar-apertura]',
        'Apertura A Pedido' => '[data-js-apertura-a-pedido]'
      ],
      'botones_solo_adm' => ['Apertura A Pedido'],
      'buscar' => 'aperturas/filtrosAperturas',
      'resultados' => [
        'fecha' => 'fecha',
        'nro_mesa' => 'nro_mesa',
        'juego' => 'juego_mesa.siglas',
        'hora' => 'hora',
        'moneda' => 'moneda.siglas',
        'casino' => 'casino.nombre',
        'estado' => 'apertura_mesa.id_estado_cierre',
        'acciones' =>  [//attr => icono, permiso, html extra
          '[data-js-ver-apertura]' => ['fa-search-plus',null,'data-estados="1,2,3,4" data-importado="0,1"'],
          '[data-js-desvincular]' => ['fa-unlink','m_validar_aperturas','data-estados="2,3,4" data-importado="0,1"'],
          '[data-js-modificar-apertura]' => ['fa-pencil-alt',null,'data-estados="1" data-importado="0,1"'],
          '[data-js-validar-apertura]' => ['fa-check','m_validar_aperturas','data-estados="1" data-importado="0,1"'],
          '[data-js-eliminar-apertura]' => ['fa-trash','m_eliminar_cierres_y_aperturas','data-estados="1" data-importado="0,1"'],
        ],
      ],
    ],
    'cierres' => [
      'botones' => [
        'Cargar Cierre' => '[data-js-cargar-cierre]'
      ],
      'botones_solo_adm' => [],
      'buscar' => 'cierres/filtrosCierres',
      'resultados' => [
        'fecha' => 'fecha',
        'nro_mesa' => 'nro_mesa',
        'juego' => 'juego_mesa.siglas',
        'hora' => 'hora_inicio',
        'moneda' => 'moneda.siglas',
        'casino' => 'casino.nombre',
        'estado' => 'cierre_mesa.id_estado_cierre',
        'acciones' =>  [
          '[data-js-ver-cierre]' => ['fa-search-plus',null,'data-estados="1,2,3,4" data-importado="0,1"'],
          '[data-js-modificar-cierre]' => ['fa-pencil-alt',null,'data-estados="1"  data-importado="0"'],
          '[data-js-validar-cierre]' => ['fa-check','m_validar_cierres','data-estados="1"  data-importado="0"'],
          '[data-js-eliminar-cierre]' => ['fa-trash','m_eliminar_cierres_y_aperturas','data-estados="1,3"  data-importado="0"'],
        ],
      ],
    ],
  ];
?>
<div class="row">
  <div class="tabs" data-js-tabs>
    @foreach($tabs as $tab => $_)
    <div>
      <a data-js-tab data-tab-target="#pant_{{$tab}}">{{ucwords($tab)}}</a>
    </div>
    @endforeach
  </div>
</div>

<style>
  .filtro_tabla tbody tr .estado i.rojo{
    color: rgb(211, 47, 47);
  }
  .filtro_tabla tbody tr .estado i.azul{
    color: rgb(30, 30, 227);
  }
  .filtro_tabla tbody tr .estado i.verde{
    color: rgb(76, 175, 80);
  }
  .filtro_tabla tbody tr .estado i.naranja{
    color: rgb(189, 133, 1);
  }
  .filtro_tabla .txt-center{
    text-align: center !important;
  }
</style>

<div id="iconosEstados" hidden>{{-- El manejo de estados es bastante raro... por eso todos estos casos --}}
  <i data-linkeado="0" data-estado="1" class="rojo fas fa-fw fa-times" title="CARGADO"></i>
  <i data-linkeado="0" data-estado="2" class="azul fa fa-fw fa-check" title="VISADO"></i>
  <i data-linkeado="0" data-estado="3" class="azul fa fa-fw fa-check" title="VISADO"></i>
  <i data-linkeado="0" data-estado="4" class="azul fa fa-fw fa-check" title="VISADO"></i>
  <i data-linkeado="0" data-estado=""  class="naranja fas fa-fw fa-question" title="ERROR"></i>
  <i data-linkeado="1" data-estado="1" class="verde fas fa-fw fa-check" title="VALIDADO"></i>
  <i data-linkeado="1" data-estado="2" class="verde fa fa-fw fa-check" title="VALIDADO C/ DIFERENCIAS"></i>
  <i data-linkeado="1" data-estado="3" class="verde fa fa-fw fa-check" title="VALIDADO"></i>
  <i data-linkeado="1" data-estado="4" class="verde fa fa-fw fa-check" title="VALIDADO"></i>
  <i data-linkeado="1" data-estado=""  class="naranja fas fa-fw fa-question" title="ERROR"></i>
</div>
@foreach($tabs as $tab => $tdata)
<div class="col-lg-12 tab_content" id="pant_{{$tab}}" hidden="true">
  <div class="row">
    <div class="col-md-3">  
      @if($tab == 'aperturas')
      <div class="row">
        @component('CierresAperturas/sorteador',compact('casinos'))
        @endcomponent
      </div>
      @endif
      @foreach($tdata['botones'] as $btn_text => $modal_selector)
      @if(!in_array($btn_text,$tdata['botones_solo_adm']) || $usuario->es_administrador || $usuario->es_superusuario)
      <div class="row">
        <div class="col-md-12">
          <a href="" class="btn-grande" data-js-mostrar="{{$modal_selector}}" dusk="btn-nuevo" style="text-decoration: none;">
            <div class="panel panel-default panelBotonNuevo">
              <center>
                <img class="imgNuevo" src="/img/logos/informes_white.png">
              <center>
              <div class="backgroundNuevo"></div>
              <div class="row">
                <div class="col-xs-12">
                  <center>
                    <h5 class="txtLogo">+</h5>
                    <h4 class="txtNuevo">{{$btn_text}}</h4>
                  </center>
                </div>
              </div>
            </div>
          </a>
        </div>
      </div>
      @endif
      @endforeach
    </div>
    <div class="col-md-9">
      @component('CierresAperturas/FiltroTabla')
        @slot('titulo')
        {{$tab}}
        @endslot
        
        @slot('target_buscar')
        {{$tdata['buscar']}}
        @endslot
        
        @slot('filtros')
        <div class="row">
          <div class="col-md-4">
            <h5>Fecha</h5>
            @component('CierresAperturas/inputFecha',['attrs' => 'name="fecha"'])
            @endcomponent
          </div>
          <div class="col-md-4">
            <h5>Mesa</h5>
            <div class="input-group">
              <input name="nro_mesa" class="form-control" type="text" autocomplete="off">
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <h5>Casino</h5>
          <select name="id_casino" class="form-control">
            <option value="" selected>- Seleccione un Casino -</option>
            @foreach ($casinos as $cas)
            <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <h5>JUEGO</h5>
          <select name="id_juego" class="form-control">
            <option value="" selected>- Seleccione un Juego -</option>
            @foreach ($juegos as $j)
            <option value="{{$j->id_juego_mesa}}">{{$j->nombre_juego}} - {{$j->casino->codigo}}</option>
            @endforeach
          </select>
        </div>
        @endslot
        
        @slot('cabecera')
        <tr>
          @foreach($tdata['resultados'] as $class => $key)
          @php $txt = str_replace('_',' ',strtoupper($class)); @endphp
          @if(!is_array($key))
            <th class="txt-center" data-js-sortable="{{$key}}">{{$txt}}</th>
          @else
            <th class="txt-center">{{$txt}}</th>
          @endif
          @endforeach
        </tr>
        @endslot
        
        @slot('molde')
        <tr>
          @foreach($tdata['resultados'] as $class => $key)
          @if(!is_array($key))
          <td class="{{$class}} txt-center">{{$class}}</td>
          @else
          <td>
            @foreach($key as $selector => $icono_perm)
            @if($tiene_permiso($icono_perm[1]))
            <button type="button" class="btn" data-js-mostrar="{{$selector}}" {!! $icono_perm[2] !!}>
              <i class="fa fa-fw {{$icono_perm[0]}}"></i>
            </button>
            @endif                    
            @endforeach
          </td>
          @endif
          @endforeach
        </tr>
        @endslot
        
      @endcomponent
    </div>
  </div>
</div>
@endforeach

@component('CierresAperturas/aperturasAPedido',compact('juegos'))
@endcomponent

@component('CierresAperturas/verCierreApertura')
@endcomponent

@component('CierresAperturas/desvincular')
@endcomponent

@component('CierresAperturas/cmApertura_cmvCierre',compact('usuario','casinos','monedas'))
@endcomponent

@component('CierresAperturas/validarApertura')
@endcomponent

@component('CierresAperturas/alertaBaja')
@endcomponent

<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h6>GESTIÓN DE CIERRES Y APERTURAS</h6>
  <p>
    Desde esta sección se podrán visualizar los cierres y aperturas cargados, ordenados por fecha,
    y generar las planillas de Relevamiento de Aperturas.
    Los datos cargados pueden filtrarse, cargar y editar. Sólo las aperturas se validan, seleccionando
    el cierre con el que se desea realizar dicha acción, para luego poder comparar datos de cada mesa.
    <br><br>

    <h6>CIERRES</h6>
    Desde el botón "Nuevo Cierre", podrán cargarse simultaneamente los Cierres correspondientes a una fecha de producción
    especificada y a un casino especificados en la ventana de carga, de las diferentes mesas que abrieron. Para guardar
    la información cargada para cada mesa, se debe presionar el botón "Guardar", y esta aparecerá con un tilde en el listado
    de mesas a cargar. Una vez que se hayan cargado todos los datos de cierre de cada mesa, se presiona el botón "Finalizar"
    para cerrar la ventana de carga.
    Luego podrán visualizarse en el listado principal, los Cierres cargados hasta el momento, ordenados por fecha y paginados.
    Estos pueden filtrarse por mesa, fecha, juego y casino, desplazando la barra de "FILTROS".
    Además se puede acceder a los detalles de cada cierre, modificarse y eliminar, según los roles y permisos de cada usuario.
    <br><br>
    <h6>APERTURAS</h6>
    Desde el botón "Generar Planilla Apertura", se genera un archivo con cinco planillas en las que se detallan las mesas que
    han sido seleccionadas por sorteo para relevar su apertura.
    Desde el botón "Cargar Apertura, podrán cargarse simultaneamente las Aperturas correspondientes a una fecha de producción
    especificada y a un casino especificados en la ventana de carga, de las mesas relevadas. Para guardar la información
    cargada para cada mesa, se debe presionar el botón "Guardar", y esta aparecerá con un tilde en el listado de mesas a cargar.
    Una vez que se hayan cargado todos los datos de apertura de cada mesa, se presiona el botón "Finalizar" para cerrar la
    ventana de carga.
    Luego podrán visualizarse en el listado principal, las Aperturas cargadas hasta el momento, ordenadas por fecha y paginadas.
    Estas pueden filtrarse por mesa, fecha, juego y casino, desplazando la barra de "FILTROS".
    Además se puede acceder a los detalles de cada Apertura, modificarse, eliminarse y validarse, según los roles y permisos de
    cada usuario.
    Para la validación se debe seleccionar el Cierre que se corresponda con la Apertura a validar, en la selección se detalla
    la hora, la moneda y fecha del cierre.  En caso de haber diferencias, podrá validarse con Observación.  Una vez validada,
    esta apertura aparecerá en el listado principal con una tilde verde en la columna "Estado".
  </p>
</div>
@endsection

@section('scripts')
  <script src="js/CierresAperturas/CierresAperturas.js?9" type="module" charset="utf-8"></script>
@endsection
