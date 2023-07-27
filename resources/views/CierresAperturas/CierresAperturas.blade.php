<?php
use App\Http\Controllers\AuthenticationController;
?>
@extends('includes.dashboard')

@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link href="css/bootstrap-datetimepicker.min.css" rel="stylesheet"/>
<link href="css/bootstrap.min.css" rel="stylesheet"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/js/jquery-ui-1.12.1.custom/jquery-ui.css">
<link rel="stylesheet" href="/css/paginacion.css">
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
        'Generar Plantilla' => '[data-js-generar-plantilla]',
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
          'data-js-mostrar="[data-js-ver-apertura]"' => ['fa-search-plus',null,'data-estados="1,2,3,4"'],
          'data-js-mostrar="[data-js-desvincular]"' => ['fa-unlink','m_validar_aperturas','data-estados="2,3,4"'],
          'data-js-mostrar="[data-js-modificar-apertura]"' => ['fa-pencil-alt',null,'data-estados="1"'],
          'data-js-mostrar="[data-js-validar-apertura]"' => ['fa-check','m_validar_aperturas','data-estados="1"'],
          'data-js-mostrar="[data-js-eliminar-apertura]"' => ['fa-trash','m_eliminar_cierres_y_aperturas','data-estados="1"'],
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
          'data-js-mostrar="[data-js-ver-cierre]"' => ['fa-search-plus',null,'data-estados="1,2,3,4"'],
          'data-js-mostrar="[data-js-modificar-cierre]"' => ['fa-pencil-alt',null,'data-estados="1"'],
          'data-js-mostrar="[data-js-validar-cierre]"' => ['fa-check','m_validar_cierres','data-estados="1"'],
          'data-js-mostrar="[data-js-eliminar-cierre]"' => ['fa-trash','m_eliminar_cierres_y_aperturas','data-estados="1,3"'],
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
  .tablaResultados tbody tr .estado i.rojo{
    color: rgb(211, 47, 47);
  }
  .tablaResultados tbody tr .estado i.azul{
    color: rgb(30, 30, 227);
  }
  .tablaResultados tbody tr .estado i.verde{
    color: rgb(76, 175, 80);
  }
  .tablaResultados tbody tr .estado i.naranja{
    color: rgb(189, 133, 1);
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
      <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading" data-toggle="collapse" href="#pant_{{$tab}} .filtro-busqueda-collapse" style="cursor: pointer">
              <h4>Filtros de búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
            </div>
            <div class="filtro-busqueda-collapse panel-collapse collapse">
              <div class="panel-body">
                <div class="row">
                  <div class="col-xs-4">
                    <h5>Fecha</h5>
                    <div class="form-group">
                      <div class='input-group date' data-js-fecha data-date-format="MM yyyy">
                        <input name="fecha" type='text' class="form-control" placeholder="aaaa-mm-dd" />
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                      </div>
                    </div>
                  </div>
                  <div class="col-xs-4">
                    <h5>Mesa</h5>
                    <div class="input-group">
                      <input name="nro_mesa" class="form-control filtroMesa" type="text" autocomplete="off">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-xs-4">
                    <h5>Casino</h5>
                    <select name="id_casino" class="form-control filtroCas">
                      <option value="" selected>- Seleccione un Casino -</option>
                      @foreach ($casinos as $cas)
                      <option value="{{$cas->id_casino}}">{{$cas->nombre}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-xs-4">
                    <h5>JUEGO</h5>
                    <select name="id_juego" class="form-control filtroJuego">
                      <option value="" selected>- Seleccione un Juego -</option>
                      @foreach ($juegos as $j)
                      <option value="{{$j->id_juego_mesa}}">{{$j->nombre_juego}} - {{$j->casino->codigo}}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4" style="padding-top:50px;">
                    <button data-target="{{$tdata['buscar']}}" data-js-buscar class="btn btn-infoBuscar" type="button" style="margin-top:30px">
                      <i class="fa fa-fw fa-search"></i> BUSCAR
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4>{{$tab}}</h4>
            </div>
            <div class="panel-body">
              <div class="table-responsibe">
                <table class="table tablesorter tablaResultados">
                  <thead>
                    <tr align="center">
                      @foreach($tdata['resultados'] as $class => $key)
                      @php $txt = str_replace('_',' ',strtoupper($class)); @endphp
                      @if(!is_array($key))
                        <th data-js-sortable="{{$key}}">{{$txt}}</th>
                      @else
                        <th>{{$txt}}</th>
                      @endif
                      @endforeach
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
              <table hidden>
                <tr class="filaResultado moldeFilaResultados">
                  @foreach($tdata['resultados'] as $class => $key)
                  @if(!is_array($key))
                  <td class="{{$class}}">{{$class}}</td>
                  @else
                  <td>
                    @foreach($key as $boton => $icono_perm)
                    @if($tiene_permiso($icono_perm[1]))
                    <button type="button" class="btn" {!! $boton !!} {!! $icono_perm[2] !!}>
                      <i class="fa fa-fw {{$icono_perm[0]}}"></i>
                    </button>
                    @endif                    
                    @endforeach
                  </td>
                  @endif
                  @endforeach
                </tr>
              </table>
            </div>
            <div class="row zonaPaginacion herramientasPaginacion"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endforeach

@component('CierresAperturas/generar',compact('juegos'))
@endcomponent

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


<!-- Comienza modal de ayuda -->
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
<!-- Termina modal de ayuda -->

@section('scripts')

  <!-- JavaScript personalizado -->
  <script src="js/CierresAperturas/CierresAperturas.js?7" type="module" charset="utf-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
  <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

  <script src="js/inputSpinner.js" type="text/javascript"></script>
  <script src="/js/lista-datos.js" type="text/javascript"></script>

  <script src="/js/fileinput.min.js" type="text/javascript"></script>
  <script src="/js/locales/es.js" type="text/javascript"></script>
  <script src="/themes/explorer/theme.js" type="text/javascript"></script>
  <script src="/js/jquery-ui.js" type="text/javascript"></script>

  <script src="js/math.min.js" type="text/javascript"></script>

  <!-- JS paginacion -->
  <script src="/js/paginacion.js" charset="utf-8"></script>
@endsection
