@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas', 'iconoMaquinas')</span>
@endsection

<?php
$puede_manejar_papel = $usuario->es_superusuario;
$puede_agregar   = $usuario->es_superusuario || $usuario->tienePermiso('agregar_ae') || $usuario->tienePermiso('aym_ae_plataformas');
$puede_modificar = $usuario->es_superusuario || $usuario->tienePermiso('modificar_ae') || $usuario->tienePermiso('aym_ae_plataformas');
$puede_ver_formularios = $puede_agregar || $puede_modificar;
$puede_descargar = $usuario->es_superusuario || $usuario->tienePermiso('descargar_aes');
$puede_borrar    = $usuario->es_superusuario || $usuario->tienePermiso('borrar_ae');
$puede_ver_logs_descargas = $usuario->es_superusuario || $usuario->tienePermiso('logs_descargar_aes');
$puede_subir_archivos = $puede_agregar;
//Las plataformas de apuestas deportivas no cuelgan de ningún casino, así que no se pueden filtrar
//por plataforma_tiene_casino como las online: se muestran todas o ninguna según el permiso.
$plats_deportivas_alta = $usuario->tienePermiso('aym_ae_plataformas')? $plataformas_deportivas : [];
$NOMBRE_NUEVA_RESOLUCION = env('NOMBRE_NUEVA_RESOLUCION','494');
$FECHA_FIN_R983 = env('FECHA_FIN_R983','2026-09-09')
?>

@section('estilos')
<link rel="stylesheet" href="/css/paginacion.css?v=2.0">
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/animacionCarga.css">

<style>
.page {
    display: none;
}
.active {
    display: inherit;
}
.easy-autocomplete{
  width:initial!important
}

/* Make circles that indicate the steps of the form: */
.step {
  height: 15px;
  width: 15px;
  margin: 0 2px;
  background-color: #bbbbbb;
  border: none;
  border-radius: 50%;
  display: inline-block;
  opacity: 0.5;
}

/* Mark the active step: */
.step.actived {
  opacity: 1;
}

/* Mark the steps that are finished and valid: */
.step.finish {
  background-color: #4CAF50;
}

.smalltext {
  font-size: 97%;
}
input[required], select[required]{
  background: #f0f6ff
}
.hover-pointer:hover{
  cursor: pointer;
}

/* ---- Resultado de la carga masiva: un color por categoría ---- */
.bloque-resultado{
  border-left: 5px solid;
  border-radius: 4px;
  padding: 8px 12px;
  margin-bottom: 8px;
}
.bloque-resultado h5{
  margin: 0 0 6px 0;
  font-weight: bold;
  font-size: 14px;
}
.bloque-resultado ul{
  font-size: 12px;
  margin-bottom: 0;
  padding-left: 20px;
  max-height: 140px;
  overflow-y: auto;
}
.bloque-verde   { border-color:#2e7d32; background:#e8f5e9; color:#1b5e20; }
.bloque-azul    { border-color:#0288d1; background:#e1f5fe; color:#01579b; }
.bloque-amarillo{ border-color:#f9a825; background:#fff8e1; color:#8a6100; }
.bloque-rojo    { border-color:#c62828; background:#ffebee; color:#b71c1c; }
.bloque-verde .badge   { background-color:#2e7d32; }
.bloque-azul .badge    { background-color:#0288d1; }
.bloque-amarillo .badge{ background-color:#f9a825; }
.bloque-rojo .badge    { background-color:#c62828; }
</style>
@endsection

@section('contenidoVista')
<div class="col-xl-2">
  <div class="row">
    @if($puede_agregar)
    <div class="col-xl-12 col-md-4">
      <a href="" id="btn-agregar-ae" style="text-decoration: none;">
        <div class="panel panel-default panelBotonNuevo">
          <center>
            <img class="imgNuevo" src="/img/logos/logo_autoexclusion.png">
          </center>
          <div class="backgroundNuevo"></div>
          <div class="row">
            <div class="col-xs-12">
              <center>
                <h5 class="txtLogo">+</h5>
                <h4 class="txtNuevo">Agregar Autoexcluido</h4>
              </center>
            </div>
          </div>
        </div>
      </a>
    </div>
    @endif
    @if($puede_ver_formularios)
    <div class="col-xl-12 col-md-4">
      <a href="" id="btn-ver-formularios-ae" style="text-decoration: none;">
        <div class="panel panel-default panelBotonNuevo">
            <center>
              <img class="imgNuevo" src="/img/logos/relevamientos_white.png">
            </center>
            <div class="backgroundNuevo"></div>
            <div class="row">
              <div class="col-xs-12">
                <center>
                  <h5 class="txtLogo">+</h5>
                  <h4 class="txtNuevo">Ver formularios de autoexclusión</h4>
                </center>
              </div>
            </div>
        </div>
      </a>
    </div>
    @endif
    @if($puede_descargar)
    <div class="col-xl-12 col-md-4">
      <a href="" id="btn-descargar-ae" style="text-decoration: none;width: 100%;">
        <div class="panel panel-default panelBotonNuevo">
          <center>
            <img class="imgNuevo" src="/img/logos/CSV_white.png">
          </center>
          <div class="backgroundNuevo"></div>
          <div class="row">
            <div class="col-xs-12">
              <center>
                <h5 class="txtLogo" style="font-size: 165px !important;">↓</h5>
                <h4 class="txtNuevo"><br>DESCARGAR AEs</h4>
              </center>
            </div>
          </div>
        </div>
      </a>
      @if($puede_ver_logs_descargas)
      <a href="/autoexclusion/logsDescarga" target="_blank"rel="noopener noreferrer" style="text-decoration: none;">
        <div class="panel panel-default" style="text-align: center;">
          <span style="width: 100%;">
            <i class="fa fa-fw fa-search"></i> VER LOGS DE DESCARGA
          </span>
        </div>
      </a>
      @endif
    </div>
    @endif
    
    @if($puede_agregar)
    <div class="col-xl-12 col-md-4">
      <a href="" id="btn-importar-masivo" style="text-decoration: none;">
        <div class="panel panel-default panelBotonNuevo">
          <center>
            <img class="imgNuevo" src="/img/logos/CSV_white.png">
          </center>
          <div class="backgroundNuevo"></div>
          <div class="row">
            <div class="col-xs-12">
              <center>
                <h5 class="txtLogo">+</h5>
                <h4 class="txtNuevo">Carga Masiva</h4>
              </center>
            </div>
          </div>
        </div>
      </a>
      @if($usuario->es_superusuario)
      <a href="/autoexclusion/logsCarga" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
        <div class="panel panel-default" style="text-align: center;">
          <span style="width: 100%;">
            <i class="fa fa-fw fa-search"></i> VER LOGS DE CARGA
          </span>
        </div>
      </a>
      @endif
    </div>
    @endif
  </div>
</div>

<div class="col-xl-10">
  @component('Components/FiltroTabla')
  
  @slot('titulo')
  AUTOEXCLUIDOS
  @endslot
  
  @slot('target_buscar')
  /autoexclusion/buscarAutoexcluidos
  @endslot
  
  @slot('filtros')
  <div class="row">
    <div class="col-md-2">
      <h5>Resolucion</h5>
      <select name="estado_resolucion" class="form-control" value="">
        @foreach($catalog['estado_resolucion'] as $text => $val)
        <option {{$loop->first? 'selected' : ''}} value="{{$val}}">{{$text}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <h5>Alcance Interjuris.</h5>
      <select name="estado_alcance_interjurisdiccional" class="form-control" value="">
        @foreach($catalog['estado_alcance_interjurisdiccional'] as $text => $val)
        <option {{$loop->first? 'selected' : ''}} value="{{$val}}">{{$text}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <h5>Eliminada</h5>
      <select name="deleted" class="form-control">
        @foreach($catalog['deleted'] as $text => $val)
        <option {{$loop->first? 'selected' : ''}} value="{{$val}}">{{$text}}</option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="row">
    <div class="col-md-2">
      <h5>Nombres</h5>
      <input name="nombres" class="form-control" value=""/>
    </div>
    <div class="col-md-2">
      <h5>Apellidos</h5>
      <input name="apellidos" class="form-control" value=""/>
    </div>
    <div class="col-md-2">
      <h5>DNI</h5>
      <input name="nro_dni" class="form-control" value="{{$dni}}"/>
    </div>
    <div class="col-md-2">
      <h5>Correo</h5>
      <input name="email" class="form-control" value=""/>
    </div>
    <div class="col-md-2">
      <h5>Estado</h5>
      <select name="estado" class="form-control selectEstado">
        @foreach($catalog['estado'] as $text => $val)
        <option  {{$loop->first? 'selected' : ''}} value="{{$val}}">{{$text}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <h5>Origen</h5>
      <select name="origen" id="buscadorOrigen" class="form-control selectCasinos" data-js-setear-plataforma="#selectEscondidoPlataforma">
        @foreach($catalog['origen'] as $text => $val)
        <option  {{$loop->first? 'selected' : ''}} value="{{$val}}">{{$text}}</option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="row">
    <h5>Desde</h5>
    <div class="col-md-3">
      <h5>Fecha autoexclusión</h5>
      @component('Components/inputFecha',['attrs' => 'name="estado_fecha_desde"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha finalizable (5 meses)</h5>
      @component('Components/inputFecha',['attrs' => 'name="estado_fecha_finalizacion_desde"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha vencimiento (6 meses)</h5>
      @component('Components/inputFecha',['attrs' => 'name="estado_fecha_finalizacion_vencimiento_desde"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha Vencimiento</h5>
      @component('Components/inputFecha',['attrs' => 'name="estado_fecha_vencimiento_desde"'])
      @endcomponent
    </div>
  </div>
  <div class="row">
    <h5>Hasta</h5>
    <div class="col-md-3">
      <h5>&nbsp;</h5>
      @component('Components/inputFecha',['attrs' => 'name="estado_fecha_hasta"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>&nbsp;</h5>
      @component('Components/inputFecha',['attrs' => 'name="estado_fecha_finalizacion_hasta"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>&nbsp;</h5>
      @component('Components/inputFecha',['attrs' => 'name="estado_fecha_finalizacion_vencimiento_hasta"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>&nbsp;</h5>
      @component('Components/inputFecha',['attrs' => 'name="estado_fecha_vencimiento_hasta"'])
      @endcomponent
    </div>
  </div>
  @if($puede_manejar_papel)
  <div class="row">
    <div class="col-md-3">
      <h5>PAPEL DESTRUIDO</h5>
      <select name="papel_destruido" class="form-control">
        <option value="">- TODOS -</option>
        <option value="SI">SI</option>
        <option value="NO">NO</option>
      </select>
    </div>
  </div>
  @endif
  <div class="col-md-5">&nbsp;</div>{{-- OFFSET PARA QUE EL BUSCAR QUEDE EN EL MEDIO --}}
  @endslot
  
  @slot('cabecera')
  <tr>
    <th data-js-sortable="id_autoexclusion">AE</th>
    <th data-js-sortable="origen">ORIGEN</th>
    <th data-js-sortable="nro_dni">DNI</th>
    <th data-js-sortable="apellidos">APELLIDOS</th>
    <th data-js-sortable="nombres">NOMBRES</th>
    <th class="smalltext" data-js-sortable="estado_resolucion">RES.</th>
    <th class="smalltext" data-js-sortable="estado">ESTADO</th>
    <th class="smalltext" data-js-sortable="estado_fecha" data-js-state="desc">F. AE</th>
    <th class="smalltext">F. FIN</th>
    <th class="smalltext">F. FIN. VENC.</th>
    <th class="smalltext">F. VENC.</th>
    <th style="flex: 2;">ACCIONES</th>
    @if($puede_manejar_papel)
    <th>PAPEL</th>
    @endif
  </tr>
  @endslot
  
  @slot('molde')
  <tr class="filaBusqueda">
    <td class="id_autoexclusion"></td>
    <td class="origen"></td>
    <td class="nro_dni"></td>
    <td class="apellidos"></td>
    <td class="nombres"></td>
    <td class="estado_resolucion smalltext"></td>
    <td class="estado smalltext"></td>
    <td class="estado_fecha smalltext"></td>
    <td class="estado_fecha_finalizable	smalltext"></td>
    <td class="estado_fecha_finalizable_vencimiento smalltext"></td>
    <td class="estado_fecha_vencimiento	smalltext"></td>
    <td class="acciones" style="flex: 2;">
      <button data-js-click-ver-ae="/autoexclusion/obtenerConHistorial" class="btn btn-info info" type="button" value="" title="VER MÁS" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-search-plus"></i>
      </button>
      @if($puede_modificar)
      <button id="btnCambiarEstado" class="btn btn-info info" type="button" value="" title="" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw"></i>
      </button>

      @if($usuario->es_superusuario)
      <button data-js-click-editar-ae="/autoexclusion/obtenerConHistorial" class="btn btn-info info" type="button" value="" title="EDITAR" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-pencil-alt"></i>
      </button>
      @elseif($puede_modificar)
      <button data-js-click-editar-ae="/autoexclusion/obtenerConHistorial" data-solo-pendientes class="btn btn-info info" type="button" value="" title="EDITAR" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-pencil-alt"></i>
      </button>
      @endif

      @endif
      @if($puede_subir_archivos)
      <a tabindex="0" data-js-click-mostrar-popover-adjuntar class="btn btn-info info" role="button" value="" title="SUBIR ARCHIVOS" data-toggle="popover" data-html="true" data-trigger="focus" data-placement="left"
         data-content="">
        <i class="fa fa-fw fa-folder-open"></i>
      </a>
      @endif
      @if($puede_agregar)
      <button id="btnGenerarSolicitudAutoexclusion" class="btn btn-info info" type="button" value="" title="GENERAR SOLICITUD AE" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="far fa-fw fa-file-alt"></i>
      </button>
      <button id="btnGenerarConstanciaReingreso" class="btn btn-info imprimir" type="button" value="" title="GENERAR CONSTANCIA DE REINGRESO" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-print"></i>
      </button>
      <button id="btnGenerarSolicitudFinalizacion" class="btn btn-info imprimir" type="button" value="" title="GENERAR SOLICITUD DE FINALIZACION" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-print"></i>
      </button>
      @endif      
      @if($puede_borrar)
      <button id="btnEliminar" class="btn btn-info info" type="button" value="" title="ELIMINAR" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-trash"></i>
      </button>
      @endif
    </td>
    @if($puede_manejar_papel)
    <td style="flex: 0.8;">
      <button class="btn btn-info btnDestruirPapel" title="DESTRUIR PAPEL" data-papel-destruido="0">
        <i class="fa fa-indent" style="transform: rotate(67deg);"></i>
      </button>
      <a data-papel-destruido="-1" class="pop" data-content="ERROR -1" rel="popover" data-trigger="hover" data-placement="left">
        <i class="fa fa-ban hover-pointer" style="color: orange;"></i>
      </a>
      <a data-papel-destruido="0" class="pop" data-content="ERROR 0" rel="popover" data-trigger="hover" data-placement="left">
        <i class="fa fa-times hover-pointer" style="color: #FF1744;"></i>
      </a>
      <a data-papel-destruido="1" class="pop" data-content="ERROR 1" rel="popover" data-trigger="hover" data-placement="left">
        <i class="fa fa-check hover-pointer" style="color: #00C853;"></i>
      </a>
    </td>
    @endif
  </tr>
  @endslot
  
  @endcomponent
</div> <!-- col-xs-10 -->

@if($puede_manejar_papel)
<div hidden>
  <div data-js-molde-popover-papel data-papel-destruido="-1">
    <p>AE ONLINE // SIN PAPELES</p>
    <p>Ultima modificación: <span class="modificado">YYYY-MM-DD HH:mm:SS USUARIO</span></p>
  </div>
  <div data-js-molde-popover-papel data-papel-destruido="0">
    <p>PAPELES SIN DESTRUIR</p>
    <p>Ultima modificación: <span class="modificado">YYYY-MM-DD HH:mm:SS USUARIO</span></p>
  </div>
  <div data-js-molde-popover-papel data-papel-destruido="1">
    <p>PAPELES DESTRUIDOS</p>
    <p>Ultima modificación: <span class="modificado">YYYY-MM-DD HH:mm:SS USUARIO</span></p>
    <p>Destruido: <span class="destruido">YYYY-MM-DD HH:mm:SS USUARIO</span></p>
  </div>
</div>
@endif

<style>
  .tabs {
    --fondo: white;
    --gradiente: rgb(235,235,235);
    --gradiente-fondo-inicio: rgba(180,180,180,1);
    --gradiente-fondo-fin: rgba(180,180,180,0);
    --borde-tab: rgb(221, 221, 221);
    --borde-tab-seleccionado: orange;
    --texto-tab-seleccionado: #555;
    width: 100%;
    display: flex;
    margin-bottom: 10px;
    background: linear-gradient(0deg, var(--gradiente-fondo-inicio) 0%, var(--gradiente-fondo-fin) 100%);
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
    cursor: pointer;
    border: 1px solid var(--borde-tab);
    border-top-left-radius: 2em;
    border-top-right-radius: 2em;
  }
  .tabs a.active {
    color: var(--texto-tab-seleccionado);
    cursor: default;
    border-color: var(--borde-tab-seleccionado);
    border-bottom: none;
  }
  .tabs a:not(.active):not(:hover) {
    background-image:  linear-gradient(135deg, var(--gradiente) 25%, transparent 25%), linear-gradient(225deg, var(--gradiente) 25%, transparent 25%), linear-gradient(45deg, var(--gradiente) 25%, transparent 25%), linear-gradient(315deg, var(--gradiente) 25%, #ffffff 25%);
    background-position:  3px 0, 3px 0, 0 0, 0 0;
    background-size: 3px 3px;
    background-repeat: repeat;
    background-color: var(--fondo);
  }

  .VerCargarAE select[readonly],
  .VerCargarAE input[type="file"][readonly] {
    pointer-events: none;
  }
</style>
@component('Components/modal',[
  'clases_modal' => 'VerCargarAE',
  'attrs_modal' => 'data-js-modal-ver-cargar-ae',
  'estilo_cabecera' => 'background-color: #6dc7be;',
  'grande' => 80,
])
  @slot('titulo')
  <span data-modo-mostrar='[{"modo": "VER"}]'>VER AUTOEXCLUIDO</span>
  <span data-modo-mostrar='[{"modo": "NUEVO"}]'>NUEVO AUTOEXCLUIDO</span>
  <span data-modo-mostrar='[{"modo": "EDITAR"}]'>MODIFICAR AUTOEXCLUIDO</span>
  <span data-modo-mostrar='[{"modo": "ADJUNTAR"}]'>ADJUNTAR A AUTOEXCLUIDO</span>
  @endslot
  @slot('cuerpo')
  <div style="width: 100%;display: flex;align-items: center;justify-content: flex-end;" data-modo-mostrar='[{"modo": "VER"}]'>
    <h5 style="width: 15rem;">Version</h5>
    <select class="form-control" data-js-select-historial style="width: 15rem;">
    </select>
  </div>
  <form style="display: flex;flex-direction: column;">
    <div class="tabs" data-js-tabs>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-ae] [data-datos]" tabindex="0">Datos</a>
      </div>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-ae] [data-estado]" tabindex="0">Estado</a>
      </div>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-ae] [data-importacion]" tabindex="0">Adjuntos</a>
      </div>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-ae] [data-contacto]" tabindex="0">Contacto</a>
      </div>
      <div>
        <a data-js-tab="[data-js-modal-ver-cargar-ae] [data-encuesta]" tabindex="0">Encuesta</a>
      </div>
    </div>
    <div class="pestaña" data-datos>
      <div class="col-md-12">
        <div class="col-md-4">
          <h5>DNI</h5>
          <input class="form-control" name="nro_dni" data-readonly='[{"modo": "VER"},{"modo": "EDITAR"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>Origen</h5>
          <select class="form-control" name="origen"  data-readonly='[{"modo": "*"}]'>
            @foreach($catalog['origen'] as $text => $val)
            @continue(empty($val))
            <option value="{{$val}}">{{$text}}</option>
            @endforeach
          </select>
        </div>
        <div hidden>
          <input name="id_autoexclusion" class="form-control" data-readonly='[{"modo":"*"}]'>
          <input name="id_autoexcluido" class="form-control" data-readonly='[{"modo":"*"}]'>
        </div>
      </div>
      <div class="col-md-12" style="border-top: 1px solid lightgray;margin-top: 1em;">
        <div class="col-md-4">
          <h5>Nombres</h5>
          <input class="form-control" name="nombres" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>Apellidos</h5>
          <input class="form-control" name="apellidos" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>Fecha Nacimiento</h5>
          @component('Components/inputFecha',[
            'attrs' => "name='fecha_nacimiento'",
            'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;margin: 0 !important;"'
          ])
          @endcomponent
        </div>
        <div class="col-md-4">
          <h5>Sexo</h5>
          <select class="form-control" name="sexo" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
            @foreach($catalog['sexo'] as $text => $val)
            <option value="{{$val}}" {{empty($val)? 'default selected' : ''}}>{{$text}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <h5>Telefono</h5>
          <input class="form-control" name="telefono" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>Correo</h5>
          <input class="form-control" name="email" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
      </div>
      <div class="col-md-12" style="border-top: 1px solid lightgray;margin-top: 1em;">
        <div class="col-md-4">
          <h5>Provincia</h5>
          <input class="form-control" name="nombre_provincia" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>Localidad</h5>
          <input class="form-control" name="nombre_localidad" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>Cod. Postal</h5>
          <input class="form-control" name="codigo_postal" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>Domicilio</h5>
          <input class="form-control" name="domicilio" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>N° Dom</h5>
          <input class="form-control" name="nro_domicilio" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>Piso</h5>
          <input class="form-control" name="piso" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
        <div class="col-md-4">
          <h5>Departamento</h5>
          <input class="form-control" name="dpto" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
      </div>
      <div class="col-md-12" style="border-top: 1px solid lightgray;margin-top: 1em;">
        <div class="col-md-4">
          <h5>Ocupacion</h5>
          <select class="form-control" name="ocupacion" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
            @foreach($catalog['ocupacion'] as $text => $val)
            <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <h5>Estado Civil</h5>
          <select class="form-control" name="estado_civil" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
            @foreach($catalog['estado_civil'] as $text => $val)
            <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <h5>Capacitacion</h5>
          <select class="form-control" name="capacitacion" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
            @foreach($catalog['capacitacion'] as $text => $val)
            <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="pestaña" data-estado>
      <div class="col-md-4">
        <h5>Resolución</h5>
        <select data-js-change-display-resolucion class="form-control" name="estado_resolucion" data-readonly='[{"modo": "*"}]' style="text-align: center;">
          @foreach($catalog['estado_resolucion'] as $text => $val)
          @continue(empty($val))
          <option value="{{$val}}">{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Fecha</h5>
        @component('Components/inputFecha',[
          'attrs' => "name='estado_fecha' data-js-change-calcular-fechas='autoexclusion/calcularFechas'",
          'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;margin: 0 !important;"'
        ])
        @endcomponent
      </div>
      <div class="col-md-4">
        <h5>Estado</h5>
        <select class="form-control" name="estado" data-readonly='[{"modo": "*"}]' style="text-align: center;">
          @foreach($catalog['nombre_estado'] as $text => $val)
          <option value="{{$val}}">{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div data-display-resolucion="983" class="col-md-12" style="border-top: 1px solid lightgray;margin-top: 1em;">
        <div class="col-md-12">
          <h5>Res. 983</h5>
        </div>
        <div class="col-md-4">
          <h5>Primer AE</h5>
          <select class="form-control" name="es_primer_ae" data-readonly='[{"modo": "*"}]'>
            @foreach($catalog['es_primer_ae'] as $text => $val)
            <option value="{{$val}}">{{$text}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <h5>Fecha Finalizable</h5>
          <input class="form-control" name="estado_fecha_finalizable" data-readonly='[{"modo": "*"}]'>
        </div>
        <div class="col-md-4">
          <h5>Fecha Finalizable Vencimiento</h5>
          <input class="form-control" name="estado_fecha_finalizable_vencimiento" data-readonly='[{"modo": "*"}]'>
        </div>
        <div class="col-md-4">
          <h5>Fecha Finalizado</h5>
          @component('Components/inputFecha',[
            'attrs' => "name='estado_fecha_finalizado'",
            'form_group_attrs' => 'data-readonly=\'[{"modo": "VER"},{"modo": "ADJUNTAR"}]\' style="padding: 0 !important;margin: 0 !important;"'
          ])
          @endcomponent
        </div>
      </div>
      <div data-display-resolucion="494" class="col-md-12" style="border-top: 1px solid lightgray;margin-top: 1em;">
        <div class="col-md-12">
          <h5>Res. 494</h5>
        </div>
        <div class="col-md-4">
          <h5>Alcance Interjurisdiccional</h5>
          <select class="form-control" name="estado_alcance_interjurisdiccional" style="text-align: center;" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            @foreach($catalog['estado_alcance_interjurisdiccional'] as $text => $val)
            @continue($val === '')
            <option value="{{$val}}" {{($text=='NO')? 'default selected' : ''}}>{{$text}}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <h5>Fecha Vencimiento (1 año)</h5>
          <input class="form-control" name="estado_fecha_vencimiento_minima" data-readonly='[{"modo": "*"}]'>
        </div>
        <div class="col-md-4">
          <h5>Años adicionales</h5>
          <input class="form-control" name="estado_años_adicionales" data-js-change-calcular-fechas="autoexclusion/calcularFechas" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
        </div>
      </div>
      <div class="col-md-12" style="border-top: 1px solid lightgray;margin-top: 1em;">
        <div class="col-md-6">
          <h5>Fecha Vencimiento</h5>
          <input class="form-control" name="estado_fecha_vencimiento" data-readonly='[{"modo": "*"}]'>
        </div>
      </div>
    </div>
    <div class="pestaña" data-importacion>
      <?php $i_idx = 0; ?>
      @foreach($catalog['importaciones'] as $text => $val)
      <?php 
        $begin_parent_div = (($i_idx)%3) == 0; 
        $end_parent_div = (($i_idx)%3) == 2; 
      ?>
      @if($begin_parent_div)
      <div class="col-md-12">
      @endif
        <div class="col-md-4">
          <h5>{{$text}} </h5>
          <div display: flex;>
            <input class="form-control" name="{{$val}}" type="file" accept=".pdf, image/jpeg, image/png, image/webp" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
            <a href="/autoexclusion/importacion" target="_blank" data-file-external-link="{{$val}}">FILENAME.EXT</a>
            <a href="TOFILL" target="_blank" data-file-internal-link="{{$val}}">FILENAME.EXT</a>
            <button class="btn btn-link" type="button" title="BORRAR" data-js-click-file-clear="{{$val}}"><i class="fa fa-fw fa-times" style="color: red !important;"></i></button>
          </div>
        </div>
      @if($end_parent_div)
      </div>
      @endif
      <?php $i_idx++;?>
      @endforeach
    </div>
    <div class="pestaña" data-contacto>
      <div class="col-md-4">
        <h5>Vinculo</h5>
        <input class="form-control" name="contacto_vinculo" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
      </div>
      <div class="col-md-4">
        <h5>Nombre y Apellido</h5>
        <input class="form-control" name="contacto_nombre_apellido" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
      </div>
      <div class="col-md-4">
        <h5>Provincia</h5>
        <input class="form-control" name="contacto_nombre_provincia" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
      </div>
      <div class="col-md-4">
        <h5>Localidad</h5>
        <input class="form-control" name="contacto_nombre_localidad" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
      </div>
      <div class="col-md-4">
        <h5>Domicilio</h5>
        <input class="form-control" name="contacto_domicilio" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
      </div>
      <div class="col-md-4">
        <h5>Telefono</h5>
        <input class="form-control" name="contacto_telefono" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'>
      </div>
    </div>
    <div class="pestaña" data-encuesta>
      <div class="col-md-4">
        <h5>Encuesta</h5>
        <select class="form-control" name="encuesta" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['encuesta'] as $text => $val)
          <option value="{{$val}}">{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Frecuencia de Asistencia</h5>
        <select class="form-control" name="encuesta_frecuencia_asistencia" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['frecuencia_asistencia'] as $text => $val)
          <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Juego Preferido</h5>
        <select class="form-control" name="encuesta_juego_preferido" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['juego_preferido'] as $text => $val)
          <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Club Jugadores</h5>
        <select class="form-control" name="encuesta_club_jugadores" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['encuesta_club_jugadores'] as $text => $val)
          <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Conoce Plataformas</h5>
        <select class="form-control" name="encuesta_conoce_plataformas" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['encuesta_conoce_plataformas'] as $text => $val)
          <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Utiliza Plataformas</h5>
        <select class="form-control" name="encuesta_utiliza_plataformas" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['encuesta_utiliza_plataformas'] as $text => $val)
          <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Por problemas de autocontrol</h5>
        <select class="form-control" name="encuesta_autocontrol_juego" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['encuesta_autocontrol_juego'] as $text => $val)
          <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Conoce sobre Juego Responsable</h5>
        <select class="form-control" name="encuesta_juego_responsable" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['encuesta_juego_responsable'] as $text => $val)
          <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>Recibir mas información</h5>
        <select class="form-control" name="encuesta_recibir_informacion" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['encuesta_recibir_informacion'] as $text => $val)
          <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <h5>¿Como asiste?</h5>
        <select class="form-control" name="encuesta_como_asiste" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]' style="text-align: center;">
          @foreach($catalog['encuesta_como_asiste'] as $text => $val)
          <option value="{{$val}}" {{($val=='NC' || $val == 'No Contesta')? 'default selected' : ''}}>{{$text}}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-12">
        <h5>Observación</h5>
        <textarea class="form-control" name="encuesta_observacion" style="width: 100%;margin: 0;resize: vertical;" data-readonly='[{"modo": "VER"},{"modo": "ADJUNTAR"}]'></textarea>
      </div>
    </div>
  </form>
  @endslot
  @slot('pie')
  @if($puede_agregar || $puede_modificar)
  <button class="btn btn-successAceptar" type="button" data-js-click-enviar="/autoexclusion/adjuntar" data-modo-mostrar='[{"modo": "ADJUNTAR"}]'>ADJUNTAR</button>
  <button class="btn btn-successAceptar" type="button" data-js-click-enviar="/autoexclusion/guardar" data-modo-mostrar='[{"modo": "NUEVO"},{"modo": "EDITAR"}]'>GUARDAR</button>
  @endif
  @endslot
@endcomponent

@if($puede_agregar)
<!-- MODAL AGREGAR AE-->
<div class="modal fade" id="modalAgregarAE" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #00695c; color: #fff">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" id="myModalLabel">| AGREGAR AUTOEXCLUIDO</h3>
      </div>
      <div id="colapsado" class="collapse in">
        <div class="modal-body modal-Cuerpo">
          <form id="frmAgregarAE"  class="form-horizontal" novalidate=" method="post"" enctype="multipart/form-data">
            <div class="form-group error">
              <div class="col-lg-12">
                <div id="columna" class="row">
                  <div class="page step1">
                    <div class="col-lg-6">
                      <h5>NÚMERO DE DOCUMENTO</h5>
                      <input id="nro_dni" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                  </div>
                  <div class="page">
                    <div class="col-lg-12">
                      <h6>Datos Personales</h6>
                    </div>
                    <div class="step2">
                      <div class="col-lg-6">
                        <h5>APELLIDO</h5>
                        <input id="apellido" type="text" class="form-control"  placeholder="" value="" required alpha data-size="100">
                      </div>
                      <div class="col-lg-6">
                        <h5>NOMBRES</h5>
                        <input id="nombres" type="text" class="form-control"  placeholder="" value="" required alpha data-size="100">
                      </div>
                      <div class="col-lg-6">
                        <h5>FECHA DE NACIMIENTO</h5>
                        <div class="input-group date" id="dtpFechaNacimiento" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                          <input type="text" class="form-control" placeholder="Fecha de nacimiento" id="fecha_nacimiento" autocomplete="off" data-original-title="" title="" required>
                          <span id="input-times-nacimiento" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                          <span id="input-calendar-nacimiento" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                      </div>
                      <div class="col-lg-3">
                        <h5>SEXO</h5>
                        <select id="id_sexo" class="form-control"  required>
                          <option selected="" value="">Seleccionar Valor</option>
                          <option value="0">Masculino</option>
                          <option value="1">Femenino</option>
                          <option value="-1">Otro</option>
                        </select>
                      </div>
                      <div class="col-lg-3">
                        <h5>ESTADO CIVIL</h5>
                        <select id="id_estado_civil" class="form-control selectEstadoCivil" name="id_estado_civil" required disabled>
                          @foreach ($estados_civiles as $estado_civil)
                            <option id="{{$estado_civil->id_estado_civil}}" value="{{$estado_civil->id_estado_civil}}">{{$estado_civil->descripcion}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-lg-6">
                        <h5>DOMICILIO</h5>
                        <input id="domicilio" type="text" class="form-control"  placeholder="" value="">
                      </div>
                      <div class="col-lg-6">
                        <h5>NRO. DOMICILIO</h5>
                        <input id="nro_domicilio" type="text" class="form-control"  placeholder="" value="" numeric data-size="100">
                      </div>
                      <div class="col-lg-6">
                        <h5>PISO</h5>
                        <input id="piso" type="text" class="form-control"  placeholder="" value="" data-size="5">
                      </div>
                      <div class="col-lg-6">
                        <h5>DEPARTAMENTO</h5>
                        <input id="dpto" type="text" class="form-control"  placeholder="" value="" data-size="5">
                      </div>
                      <div class="col-lg-6">
                        <h5>PROVINCIA</h5>
                        <input id="nombre_provincia" type="text" class="form-control"  placeholder="" value="" required data-size="200">
                      </div>
                      <div class="col-lg-6">
                        <h5>LOCALIDAD</h5>
                        <input id="nombre_localidad" class="form-control"  type="text" class="form-control"  placeholder="" value="" required data-size="200">
                      </div>
                      <div class="col-lg-6">
                        <h5>CÓDIGO POSTAL</h5>
                        <input id="codigo_postal" type="text" class="form-control"  placeholder="" value="" data-size="10">
                      </div>
                      <div class="col-lg-6">
                        <h5>TELEFONO</h5>
                        <input id="telefono" type="text" class="form-control" placeholder="" value="" required numeric data-size="100">
                      </div>
                      <div class="col-lg-6">
                        <h5>EMAIL</h5>
                        <input id="correo" type="text" class="form-control"  placeholder="" value="" email data-size="100">
                      </div>
                      <div class="col-lg-6">
                        <h5>OCUPACIÓN</h5>
                        <select id="id_ocupacion" class="form-control" required disabled>
                            @foreach($ocupaciones as $ocupacion)
                            <option value="{{$ocupacion->id_ocupacion}}">{{$ocupacion->nombre}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="col-lg-6">
                        <h5>CAPACITACIÓN</h5>
                        <select id="id_capacitacion" class="form-control" required disabled>
                          @foreach ($capacitaciones as $capacitacion)
                            <option id="{{$capacitacion->id_capacitacion}}" value="{{$capacitacion->id_capacitacion}}">{{$capacitacion->descripcion}}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="col-lg-12">
                        <h6>Datos Persona de Contacto</h6>
                    </div>
                    <div class="col-lg-6">
                      <h5>NOMBRE Y APELLIDO</h5>
                      <input id="nombre_apellido" type="text" class="form-control"  placeholder="" value="" data-size="200">
                    </div>
                    <div class="col-lg-6">
                      <h5>DOMICILIO</h5>
                      <input id="domicilio_vinculo" type="text" class="form-control"  placeholder="" value="" data-size="200">
                    </div>
                    <div class="col-lg-6">
                      <h5>PROVINCIA</h5>
                      <input id="nombre_provincia_vinculo" type="text" class="form-control"  placeholder="" value="" data-size="200">
                    </div>
                    <div class="col-lg-6">
                      <h5>LOCALIDAD</h5>
                      <input id="nombre_localidad_vinculo" type="text" class="form-control"  placeholder="" value="" data-size="200">
                    </div>
                    <div class="col-lg-6">
                      <h5>TELEFONO</h5>
                      <input id="telefono_vinculo"  type="text" class="form-control"  placeholder="" value="" data-size="200">
                    </div>
                    <div class="col-lg-6">
                      <h5>VINCULO</h5>
                      <input id="vinculo" type="text" class="form-control"  placeholder="" value="" data-size="200">
                    </div>
                  </div>
                  <div class="page">
                    <div class="step3">
                      <div class="col-lg-12">
                        <div class="col-lg-6">
                          <h5>Resolucion</h5>
                          <input id="resolucion" type="text" class="form-control"  placeholder="" value="" required readonly>
                        </div>
                        <div class="col-lg-6">
                          <h5>Primera Autoexclusión</h5>
                          <select id="primer_ae" class="form-control" required readonly>
                            <option value="1">SI</option>
                            <option value="0">NO</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-lg-6">
                        <h5>CASINO</h5>
                        <select id="id_casino" class="form-control" required>
                          <option selected="" value="">- Seleccione un casino -</option>
                          @foreach($casinos_usuario as $casino)
                          <option data-tipo="casino" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                          @endforeach
                          @foreach ($plataformas_usuario as $p)
                          <option data-tipo="plataforma" id="-{{$p->id_plataforma}}" value="-{{$p->id_plataforma}}">{{$p->nombre}}</option>
                          @endforeach
                          @foreach ($plats_deportivas_alta as $p)
                          <option data-tipo="plataforma_aadd"  id="-{{$p->id_plataforma}}" value="-{{$p->id_plataforma}}">{{$p->nombre}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-lg-6">
                        <h5>ESTADO</h5>
                        <select id="id_estado" class="form-control" required>
                          @if(count($estados_elegibles) > 1)
                            <option selected="" value="" required>- Seleccione un estado -</option>
                            @foreach ($estados_elegibles as $estado)
                            <option id="{{$estado->id_nombre_estado}}" value="{{$estado->id_nombre_estado}}">{{$estado->descripcion}}</option>
                            @endforeach
                          @elseif(count($estados_elegibles) == 1)
                            @foreach ($estados_elegibles as $estado)
                            <option selected id="{{$estado->id_nombre_estado}}" value="{{$estado->id_nombre_estado}}">{{$estado->descripcion}}</option>
                            @endforeach
                          @else
                            <option selected="" value="" required>- Seleccione un estado -</option>
                          @endif
                        </select>
                      </div>
                      <div class="col-lg-12">
                        <h5>FECHA AUTOEXCLUSIÓN</h5>
                        <div class="input-group date" id="dtpFechaAutoexclusionEstado" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                          <input style="text-align: center;" type="text" class="form-control" placeholder="Fecha de autoexclusion" id="fecha_autoexlusion" autocomplete="off" data-original-title="" title="" required>
                          <span id="input-times" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                          <span id="input-calendar" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                      </div>
                      <div class="col-lg-6">
                        <h5>FECHA CIERRE MINIMA</h5>
                        <input id="fecha_cierre_minima" type="text" class="form-control"  placeholder="" value="" disabled="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>AÑOS ADICIONALES</h5>
                        <input id="años_adicionales" type="number" class="form-control"  placeholder="" value="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>VENCIMIENTO 1° PERIODO</h5>
                        <input id="fecha_vencimiento_periodo"type="text" class="form-control"  placeholder="" value="" disabled="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>PERMITIR RENOVACIÓN DESDE</h5>
                        <input id="fecha_renovacion" type="text" class="form-control" placeholder="" value="" disabled="" required>
                      </div>
                      <div class="col-lg-12">
                        <h5>FECHA CIERRE DEFINITIVO</h5>
                        <input style="text-align: center;" id="fecha_cierre_definitivo" type="text" class="form-control"  placeholder="" value="" disabled="" required>
                      </div>
                      <div class="col-lg-12">
                        <h5>ALCANCE INTERJURISDICCIONAL</h5>
                        <select style="text-align: center;" id="alcance_interjurisdiccional" type="text" class="form-control"  placeholder="" value="" required>
                          <option value="">- SELECCIONE -</option>
                          <option value="1">SI CONSIENTO</option>
                          <option value="0">NO CONSIENTO</option>
                        </select>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-lg-6">
                        <h5>FOTO #1</h5>
                        <div>
                          <a href="" target="_blank">FOTO1.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="foto1" type="file">
                      </div>
                      <div class="col-lg-6">
                        <h5>FOTO #2</h5>
                        <div>
                          <a href="" target="_blank">FOTO2.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="foto2" data-borrado="false" type="file">
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-lg-6">
                        <h5>SCAN DNI</h5>
                        <div>
                          <a href="" target="_blank">DNI.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="scan_dni" data-borrado="false" type="file">
                      </div>
                      <div class="col-lg-6">
                        <h5>SOLICITUD AUTOEXCLUSIÓN</h5>
                        <div>
                          <a href="" target="_blank">SOLAE.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="solicitud_autoexclusion" data-borrado="false" type="file">
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-lg-6">
                        <h5>SOLICITUD DE FINALIZACIÓN</h5>
                        <div>
                          <a href="" target="_blank">SOLFIN.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="solicitud_revocacion" data-borrado="false" type="file">
                      </div>
                      <div class="col-lg-6">
                        <h5>CARATULA</h5>
                        <div>
                          <a href="" target="_blank">CARATULA.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="caratula" data-borrado="false" type="file">
                      </div>
                    </div>
                  </div>

                  <div class="page">
                    <div class="col-lg-12 no_esconder">
                      <h6>Encuesta <span style="font-size: 12px">(OPCIONAL)</span></h6>
                      <div {{($usuario->es_superusuario || $usuario->es_administrador)? '' : 'hidden'}}>
                        Hace encuesta
                        <input type="checkbox" class="form-check-input" id="hace_encuesta" checked >
                      </div>
                    </div>
                    <div class="col-lg-3">
                      <h5>JUEGO PREFERIDO</h5>
                      <select id="juego_preferido" class="form-control">
                        <option value="">- Seleccione un juego -</option>
                        @foreach($juegos as $juego)
                        <option value="{{$juego->id_juego_preferido}}">{{$juego->nombre}}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-lg-3">
                      <h5>F. DE ASISTENCIA</h5>
                      <select id="id_frecuencia_asistencia" class="form-control">
                        <option selected="" value="">- Seleccione un valor -</option>
                        @foreach($frecuencias as $frecuencia)
                        <option value="{{$frecuencia->id_frecuencia}}">{{$frecuencia->nombre}}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-lg-3">
                      <h5>VECES</h5>
                      <input id="veces" type="text" class="form-control"  placeholder="" value="">
                    </div>
                    <div class="col-lg-3">
                      <h5>TIEMPO JUGANDO (HS)</h5>
                      <input id="tiempo_jugado" type="text" class="form-control"  placeholder="" value="">
                    </div>
                    <div class="col-lg-6">
                      <h5>¿ES SOCIO DEL CLUB DE JUGADORES?</h5>
                        <select id="socio_club_jugadores" class="form-control">
                          <option selected="" value="">- Seleccione una opción -</option>
                          <option value="SI">SI</option>
                          <option value="NO">NO</option>
                        </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿CONOCE LAS PLATAFORMAS DE JUEGO ONLINE?</h5>
                        <select id="conoce_plataformas" class="form-control">
                          <option selected="" value="">- Seleccione una opción -</option>
                          <option value="SI">SI</option>
                          <option value="NO">NO</option>
                        </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿UTILIZA LAS PLATAFORMAS DE JUEGO ONLINE?</h5>
                        <select id="utiliza_plataformas" class="form-control">
                          <option selected="" value="">- Seleccione una opción -</option>
                          <option value="SI">SI</option>
                          <option value="NO">NO</option>
                        </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿CONOCE EL PROGRAMA JUEGO RESPONSABLE?</h5>
                      <select id="juego_responsable" class="form-control">
                        <option selected="" value="">- Seleccione una opción -</option>
                        <option value="SI">SI</option>
                        <option value="NO">NO</option>
                      </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿DECISIÓN POR PROBLEMAS DE AUTOCONTROL?</h5>
                      <select id="autocontrol_juego" class="form-control">
                        <option selected="" value="">- Seleccione una opción -</option>
                        <option value="SI">SI</option>
                        <option value="NO">NO</option>
                      </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿CÓMO ASISTE?</h5>
                      <select id="como_asiste" class="form-control">
                        <option selected="" value="">- Seleccione una opción -</option>
                        <option value="0">SOLO</option>
                        <option value="1">ACOMPAÑADO</option>
                      </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿DESEA RECIBIR INFORMACIÓN SOBRE JR?</h5>
                      <select id="recibir_informacion" class="form-control">
                        <option selected="" value="">- Seleccione una opción -</option>
                        <option value="SI">SI</option>
                        <option value="NO">NO</option>
                      </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿MEDIO DE RECEPCIÓN?</h5>
                      <input id="medio_recepcion" type="text" class="form-control"  placeholder="" value="" data-size="100">
                    </div>
                    <div class="col-lg-12">
                      <h5>OBSERVACIONES</h5>
                      <textarea id="observaciones" class="form-control" placeholder="" value="" data-size="1000"></textarea>
                    </div>
                  </div>
                </div>
                <span id="alerta_sesion" class="alertaSpan"></span>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <div style="text-align:center;margin-top:20px;">
            <span id="one" class="step"></span>
            <span class="step"></span>
            <span class="step"></span>
            <span class="step"></span>
          </div>
          <button type="button" class="btn btn-successAceptar" id="btn-prev">ANTERIOR</button>
          <button type="button" class="btn btn-successAceptar" id="btn-next">SIGUIENTE</button>
          <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ENVIAR</button>
          <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
          <input type="hidden" id="id_sesion" value="0">
          <input type="hidden" id="es_superusuario" value="{{$usuario->es_superusuario ? 1 : 0}}">
          <input type="hidden" id="nombre_usuario" value="{{$usuario->nombre}}">
          <input type="hidden" id="estado_original_id" value="">
          <input type="hidden" id="estado_original_texto" value="">
        </div>
      </div>
    </div>
  </div>
</div>
@endif

<div class="modal fade" id="modalVerMas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modalVerMas" style="font-family: Roboto-Black; background-color: #4FC3F7; color: #fff">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title">| VER MÁS</h3>
      </div>
      <div id="colapsado" class="collapse in">
        <div class="modal-body">
          <form id="frmAutoExcluido" class="form-horizontal" novalidate="">
            <div class="infoDatosPersonales">
              <h6>Datos personales</h6>
              <div class="row">
                <div class="col-lg-6">
                  <h5>APELLIDO</h5>
                  <input id="infoApellido" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>NOMBRES</h5>
                  <input id="infoNombres" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>FECHA DE NACIMIENTO</h5>
                  <input id="infoFechaNacimiento" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>DNI</h5>
                  <input id="infoDni" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>SEXO</h5>
                  <select id="infoSexo" class="form-control" disabled>
                    <option selected="" value="">No ingresado</option>
                    <option value="0">Masculino</option>
                    <option value="1">Femenino</option>
                    <option value="-1">Otro</option>
                  </select>
                </div>
                <div class="col-lg-6">
                  <h5>ESTADO CIVIL</h5>
                  <select id="infoEstadoCivil" class="form-control" disabled>
                    <option selected="" value="">No ingresado</option>
                    @foreach ($estados_civiles as $estado_civil)
                    <option id="{{$estado_civil->id_estado_civil}}" value="{{$estado_civil->id_estado_civil}}">{{$estado_civil->descripcion}}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>DOMICILIO</h5>
                  <input id="infoDomicilio" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>NRO. DOMICILIO</h5>
                  <input id="infoNroDomicilio" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>PROVINCIA</h5>
                  <input id="infoProvincia" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>LOCALIDAD</h5>
                  <input id="infoLocalidad" class="form-control" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>TELEFONO</h5>
                  <input id="infoTelefono" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>EMAIL</h5>
                  <input id="infoEmail" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>OCUPACIÓN</h5>
                  <select id="infoOcupacion" class="form-control" disabled>
                    <option selected="" value="">No ingresado</option>
                    @foreach($ocupaciones as $ocupacion)
                    <option value="{{$ocupacion->id_ocupacion}}">{{$ocupacion->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-6">
                  <h5>CAPACITACIÓN</h5>
                  <select id="infoCapacitacion" class="form-control" disabled>
                    <option selected="" value="">No ingresado</option>
                    @foreach ($capacitaciones as $capacitacion)
                    <option id="{{$capacitacion->id_capacitacion}}" value="{{$capacitacion->id_capacitacion}}">{{$capacitacion->descripcion}}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>
            <br>
            <div class="infoDatosContacto">
              <h6>Datos persona de contacto</h6>
              <div class="row">
                <div class="col-lg-6">
                  <h5>NOMBRE Y APELLIDO</h5>
                  <input id="infoNombreApellidoVinculo" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>DOMICILIO</h5>
                  <input id="infoDomiclioVinculo" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>PROVINCIA</h5>
                  <input id="infoProvinciaVinculo" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>LOCALIDAD</h5>
                  <input id="infoLocalidadVinculo" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>TELÉFONO</h5>
                  <input id="infoTelefonoVinculo" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>VÍNCULO</h5>
                  <input id="infoVinculo" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
            </div>
            <br>
            <div class="infoEstadoAutoexclusion">
              <h6>Información de estado de la autoexclusión</h6>
              <div class="row">
                <div class="col-lg-6">
                  <h5>CASINO</h5>
                  <select id="infoCasino" class="form-control selectCasinos" disabled>
                    <option value="0">Todos los Casinos</option>
                    @foreach ($casinos as $casino)
                    <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                    @endforeach
                    @foreach ($plataformas as $p)
                    <option id="-{{$p->id_plataforma}}" value="-{{$p->id_plataforma}}">{{$p->nombre}}</option>
                    @endforeach
                    @foreach ($plataformas_deportivas as $p)
                    <option id="-{{$p->id_plataforma}}" value="-{{$p->id_plataforma}}">{{$p->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-6">
                  <h5>ESTADO</h5>
                  <select id="infoEstado" class="form-control selectEstado" disabled>
                    <option selected="" value="">No ingresado</option>
                    @foreach ($estados_autoexclusion as $estado)
                    <option id="{{$estado->id_nombre_estado}}" value="{{$estado->id_nombre_estado}}">{{$estado->descripcion}}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>RESOLUCIÓN</h5>
                  <input id="infoResolucion" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>PRIMERA AUTOEXCLUSIÓN</h5>
                  <input id="infoPrimerAE" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>FECHA AUTOEXCLUSIÓN</h5>
                  <input id="infoFechaAutoexclusion" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>VENCIMIENTO 1° PERÍODO</h5>
                  <input id="infoFechaVencimiento" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-4">
                  <h5>FECHA CIERRE MÍNIMA</h5>
                  <input id="infoFechaCierreMinima" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-4">
                  <h5>AÑOS ADICIONALES</h5>
                  <input id="infoAñosAdicionales" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-4">
                  <h5>ALCANCE INTERJURISDICCIONAL</h5>
                  <select id="infoAlcanceInterjurisdiccional" class="form-control" placeholder="" value="" required disabled>
                    <option value="">-</option>
                    <option value="1">Sí</option>
                    <option value="0">NO</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>PERMITIR RENOVACIÓN DESDE</h5>
                  <input id="infoFechaRenovacion" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>FECHA CIERRE DEFINITIVO</h5>
                  <input id="infoFechaCierreDefinitivo" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
              </div>
            </div>
            <br>
            <div class="archivosImportados">
              <h6>Archivos importados</h6>
              <div class="row">
                <div class="col-lg-6">
                  <h5>FOTO #1</h5>
                  <button type="button" data-tipo="foto1" class="btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente a Foto #1</button>
                </div>
                <div class="col-lg-6">
                  <h5>FOTO #2</h5>
                  <button type="button" data-tipo="foto2" class="btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente a Foto #2</button>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>SCAN DNI</h5>
                  <button type="button" data-tipo="scandni" class="btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente al scan DNI</button>
                </div>
                <div class="col-lg-6">
                  <h5>SOLICITUD AUTOEXCLUSIÓN</h5>
                  <button type="button" data-tipo="solicitud_ae" class="btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente a la SAE</button>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>SOLICITUD DE FINALIZACIÓN</h5>
                  <button type="button" data-tipo="solicitud_revocacion" class="btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente a la SAE</button>
                </div>
                <div class="col-lg-6">
                  <h5>CARATULA</h5>
                  <button type="button" data-tipo="caratula" class="btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente a la SAE</button>
                </div>
              </div>
            </div>
            <br>
            <div class="infoEncuesta">
              <h6>Encuesta</h6>
              <div class="row">
                <div class="col-lg-3">
                  <h5>JUEGO PREFERIDO</h5>
                  <select id="infoJuegoPreferido" class="form-control" disabled>
                    <option value="">No ingresado</option>
                    @foreach($juegos as $juego)
                    <option value="{{$juego->id_juego_preferido}}">{{$juego->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-3">
                  <h5>F. DE ASISTENCIA</h5>
                  <select id="infoFrecuenciaAsistencia" class="form-control" disabled>
                    <option selected="" value="">No ingresado</option>
                    @foreach($frecuencias as $frecuencia)
                    <option value="{{$frecuencia->id_frecuencia}}">{{$frecuencia->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-3">
                  <h5>VECES</h5>
                  <input id="infoVeces" type="text" class="form-control" placeholder="" value="" disabled>
                </div>
                <div class="col-lg-3">
                  <h5>TIEMPO JUGANDO (HS)</h5>
                  <input id="infoTiempoJugado" type="text" class="form-control" placeholder="" value="" disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>¿ES SOCIO DEL CLUB DE JUGADORES?</h5>
                  <input id="infoSocioClubJugadores" type="text" class="form-control" placeholder="" value="" disabled>
                </div>
                <div class="col-lg-6">
                  <h5>¿CONOCE EL PROGRAMA JUEGO RESPONSABLE?</h5>
                  <input id="infoJuegoResponsable" type="text" class="form-control" placeholder="" value="" disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>¿CONOCE LAS PLATAFORMAS DE JUEGO ONLINE?</h5>
                  <input id="infoConocePlataformas" type="text" class="form-control" placeholder="" value="" disabled>
                </div>
                <div class="col-lg-6">
                  <h5>¿UTILIZA LAS PLATAFORMAS DE JUEGO ONLINE?</h5>
                  <input id="infoUtilizaPlataformas" type="text" class="form-control" placeholder="" value="" disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>¿DECISIÓN POR PROBLEMAS DE AUTOCONTROL?</h5>
                  <input id="infoAutocontrol" type="text" class="form-control" placeholder="" value="" disabled>
                </div>
                <div class="col-lg-6">
                  <h5>¿CÓMO ASISTE?</h5>
                  <select id="infoComoAsiste" class="form-control" disabled>
                    <option selected="" value="">No ingresado</option>
                    <option value="0">SOLO</option>
                    <option value="1">ACOMPAÑADO</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>¿DESEA RECIBIR INFORMACIÓN SOBRE JR?</h5>
                  <input id="infoRecibirInformacion" type="text" class="form-control" placeholder="" value="" disabled>
                </div>
                <div class="col-lg-6">
                  <h5>¿MEDIO DE RECEPCIÓN?</h5>
                  <input id="infoMedioRecepcion" type="text" class="form-control" placeholder="" value="" disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-12">
                  <h5>OBSERVACIONES</h5>
                  <input id="infoObservaciones" class="form-control" placeholder="" value="" disabled>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button id="btn-salir" type="button" class="btn btn-default" >SALIR</button>
        </div>
      </div>
    </div>
  </div>
</div>

@component('Components/modal',[
  'clases_modal' => 'SubirArchivo',
  'attrs_modal' => 'data-js-modal-subir-archivo',
  'estilo_cabecera' => 'background-color: #00695c;',
  'grande' => 30,
])
  @slot('titulo')
  SUBIR ARCHIVO
  @endslot
  @slot('cuerpo')
  <form class="row">
    <div class="col-md-12">
      <h5>NÚMERO DE DOCUMENTO</h5>
      <input type="text" class="form-control" name="nro_dni" placeholder="" value="" required readonly>
    </div>
    <div class="col-md-12" hidden>
      <input type="text" class="form-control" name="tipo_archivo" placeholder="" value="" required readonly>
    </div>
    <div class="col-md-12" hidden>
      <input type="text" class="form-control" name="id_autoexclusion" placeholder="" value="" required readonly>
    </div>
    <div class="col-md-12">
      <br>
    </div>
    <div class="col-md-12">
      <?php $idArchivoSubir = uniqid(); ?>
      <label for="{{$idArchivoSubir}}" class="dropzone" data-js-dropzone style="border: 3px dashed #00695c; border-radius: 12px; padding: 30px; text-align: center; background-color: #f8fafc; min-height: 220px; transition: all 0.3s ease; display: flex; flex-direction: column; justify-content: center; align-items: center; cursor: pointer; width: 100%; margin: 0;">
        <i class="fa fa-cloud-upload" style="font-size: 3em; color: #00695c; margin-bottom: 10px;"></i>
        <span class="tipo_archivo" style="font-weight: 600; color: #00695c; font-size: 1.3em;">ARCHIVO</span>
        <span id="filenameDisplay" style="color:#64748b; font-size: 15px; margin-top: 10px;">Arrastre el archivo aquí o haga click en Seleccionar Archivo</span>
        <input id="{{$idArchivoSubir}}" type="file" accept=".pdf, image/jpeg, image/png, image/webp" name="archivo" class="archivo" style="display: none;">
      </label>
    </div>
  </form>
  @endslot
  @slot('pie')
  <button type="button" class="btn btn-successAceptar" data-js-click-enviar-archivo="autoexclusion/subirArchivo">SUBIR ARCHIVO</button>
  @endslot
@endcomponent

@if($puede_ver_formularios)
<!--MODAL VER FORMULARIOS AE -->
<div class="modal fade" id="modalFormulariosAE" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #00695c; color: #fff">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" id="myModalLabel">| VER FORMULARIOS DE AUTOEXCLUSIÓN</h3>
      </div>
      <div id="colapsado" class="collapse in">
        <div class="modal-body">
          <hr>
          <?php 
            $hasta = $FECHA_FIN_R983;
            $desde = \DateTimeImmutable::createFromFormat('Y-m-d',$hasta)->modify('+1 day')->format('Y-m-d'); 
          ?>
          <h6>DESDE {{$desde}}</h6>
          <div class="row">
            <div class="col-lg-6">
              <h5>RVE N° {{$NOMBRE_NUEVA_RESOLUCION}}</h5>
              <button id="7" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver la RVE N° {{$NOMBRE_NUEVA_RESOLUCION}}</button>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-6">
              <h5>Formulario AU</h5>
              <button id="8" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el Formulario AU</button>
            </div>
          </div>
          <hr>
          <h6>HASTA {{$hasta}}</h6>
          <div class="row">
            <div class="col-lg-6">
              <h5>Carátula AU 1°</h5>
              <button id="1" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #888d8f !important; color: white; font-weight: bold;" >Click aquí para ver la Carátula AU 1°</button>
            </div>
            <div class="col-lg-6">
              <h5>Carátula AU 2°</h5>
              <button id="2" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #888d8f !important; color: white; font-weight: bold;" >Click aquí para ver la Carátula AU 2°</button>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-6">
              <h5>Formulario AU 1°</h5>
              <button id="3" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #888d8f !important; color: white; font-weight: bold;" >Click aquí para ver el Formulario AU 1°</button>
            </div>
            <div class="col-lg-6">
              <h5>Formulario AU 2°</h5>
              <button id="4" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #888d8f  !important; color: white; font-weight: bold;" >Click aquí para ver el Formulario AU 2°</button>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-6">
                <h5>Formulario Finalización AU</h5>
                <button id="5" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #888d8f  !important; color: white; font-weight: bold;" >Click aquí para ver el Formulario Finalización AU</button>
            </div>
            <div class="col-lg-6">
              <h5>RVE N°983</h5>
              <button id="6" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #888d8f  !important; color: white; font-weight: bold;" >Click aquí para ver la RVE N°983</button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" id="btn-salir" data-dismiss="modal" aria-label="Close">SALIR</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

@if($puede_agregar)
<!-- MODAL IMPORTAR MASIVO -->
<div class="modal fade" id="modalImportarMasivo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #00695c; color: #fff">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-importar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoImportar" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" id="myModalLabel">| CARGA MASIVA</h3>
      </div>
      <div id="colapsadoImportar" class="collapse in">
        <div class="modal-body modal-Cuerpo">
            <div class="row">
                <div class="col-md-12">
                    <h5>CASINO / PLATAFORMA</h5>
                    <select id="selectCasinoImportacion" class="form-control">
                        <option value="">- Seleccione -</option>
                           <?php
                            $cas_creacion = $usuario->es_superusuario? $casinos : $usuario->casinos;
                            $plats_creacion = $usuario->tienePermiso('aym_ae_plataformas')? $plataformas : [];
                          ?>
                          @foreach($cas_creacion as $casino)
                          <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                          @endforeach
                          @foreach ($plats_creacion as $p)
                          <option value="-{{$p->id_plataforma}}">{{$p->nombre}}</option>
                          @endforeach
                          @foreach ($plats_deportivas_alta as $p)
                          <option value="-{{$p->id_plataforma}}">{{$p->nombre}}</option>
                          @endforeach
                    </select>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-12">
                    <h5>ARCHIVO (CSV / EXCEL)</h5>
                    <div class="input-group">
                        <input id="archivoImportacionText" type="text" class="form-control" placeholder="Seleccione un archivo..." readonly onclick="$('#archivoImportacion').click();">
                        <span class="input-group-btn">
                            <label class="btn btn-info fileinput-button" style="background-color: #007bff; border-color: #007bff; color: white;">
                                <span>Examinar</span>
                                <input id="archivoImportacion" type="file" name="archivoImportacion" style="display: none;" onchange="$('#archivoImportacionText').val($(this).val().replace(/C:\\fakepath\\/i, ''))" accept=".csv, .xlsx, .xls">
                            </label>
                        </span>
                    </div>
                </div>
            </div>
            <br>
            <div id="mensajeExitoImportacion" hidden>
                <div style="max-height: 400px; overflow-y: auto;">
                    <p style="font-weight: bold; font-size: 16px;">Importación finalizada.</p>
                    <!-- VERDE: entraron y son personas nuevas -->
                    <div id="detalleNuevas" class="bloque-resultado bloque-verde" hidden>
                        <h5>Nuevas autoexclusiones (<span class="badge">0</span>)</h5>
                        <ul></ul>
                    </div>
                    <!-- AZUL: entraron, pero la persona ya tenía una AE previa -->
                    <div id="detallePrevias" class="bloque-resultado bloque-azul" hidden>
                        <h5>Con autoexclusión previa (<span class="badge">0</span>)</h5>
                        <ul></ul>
                    </div>
                    <!-- AMARILLO: no ingresaron (el rojo queda reservado para errores) -->
                    <div id="detalleVigentes" class="bloque-resultado bloque-amarillo" hidden>
                        <h5>NO ingresaron: ya tienen autoexclusión vigente (<span class="badge">0</span>)</h5>
                        <ul></ul>
                    </div>
                    <div id="detalleCubiertas" class="bloque-resultado bloque-amarillo" hidden>
                        <h5>NO ingresaron: la persona ya estaba cubierta por una AE previa (<span class="badge">0</span>)</h5>
                        <ul></ul>
                    </div>
                    <div id="detalleRepetidas" class="bloque-resultado bloque-amarillo" hidden>
                        <h5>NO ingresaron: fecha repetida (<span class="badge">0</span>)</h5>
                        <ul></ul>
                    </div>
                </div>
            </div>
             <div id="mensajeErrorImportacion" hidden>
                <div class="alert alert-danger">
                    <p></p>
                     <ul id="listaErroresImportacion" style="max-height: 100px; overflow-y: auto;"></ul>
                </div>
            </div>
             <div id="loadingImportacion" hidden style="text-align: center;">
                <i class="fa fa-spinner fa-spin" style="font-size: 40px; color: #6dc7be;"></i>
                <p>Procesando...</p>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-importar">IMPORTAR</button>
          <button type="button" class="btn btn-default" id="btn-cerrar-importar" data-dismiss="modal">CANCELAR</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

@if($puede_modificar)
<!-- MODAL CONFIRMACION ESTADO -->
<div class="modal fade" id="modalConfirmacionEstado" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #d9534f; color: #fff">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar-alerta" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoAlerta" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" id="myModalLabel">| ADVERTENCIA</h3>
      </div>
      <div id="colapsadoAlerta" class="collapse in">
        <div class="modal-body modal-Cuerpo">
          <div class="row">
            <div class="col-md-12">
              <h5 style="color:#d9534f; font-size: 16px;">¿Seguro que desea continuar?</h5>
              <p style="font-size: 14px;">Este cambio de estado se está realizando fuera de término.</p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" id="btn-confirmar-estado">ACEPTAR</button>
          <button type="button" class="btn btn-default" id="btn-cancelar-estado" data-dismiss="modal">CANCELAR</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

@component('Components/modalEliminar')
@endcomponent

<meta name="_token" content="{!! csrf_token() !!}" />
@endsection

@section('tituloDeAyuda')
<h3 class="modal-title2" style="color: #fff;">| SESIONES</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Sesiones</h5>
  <p>
    Agregar nuevos autoexluidos, revocar autoexclusiones, ver listado y estados.
</div>
@endsection

@section('scripts')
<!-- JavaScript paginacion -->
<script src="/js/paginacion.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->

<script language="javascript">
var noPrint=true;
var noCopy=false;
var noScreenshot=true;
var autoBlur=true;
var noSelect=false;
const FECHA_FIN_R983 = "{!! env('FECHA_FIN_R983','2026-09-09') !!}";
const NOMBRE_NUEVA_RESOLUCION = "{!! env('NOMBRE_NUEVA_RESOLUCION','494') !!}";
const IDENTITY = "{!! $identity !!}";
<?php 
  $tiene_rosario = $casinos_usuario->first(function($c){
    return $c->nombre == 'Rosario';
  });
  $nombres_casinos_usuario = $casinos_usuario->pluck('nombre')->toArray();
  if($tiene_rosario){
    $nombres_casinos_usuario[] = 'CityCenter';//Fuck up de migración
  }
  $nombres_plataformas_usuario = $plataformas_usuario->pluck('nombre')->toArray();
?>
const CASINOS_USUARIO = {!! json_encode($nombres_casinos_usuario) !!};
const PLATAFORMAS_USUARIO = {!! json_encode($nombres_plataformas_usuario) !!};
const IMPORTACIONES = {!! json_encode($catalog['importaciones'] ?? ((object)[])) !!};
const ESTADOS ={!! json_encode(array_keys($catalog['nombre_estado'] ?? [])) !!};
</script>
<script type="text/javascript" src="/js/noprint.js?1"></script> 

<script src="/js/Autoexclusion/index.js?14" type="module"  charset="utf-8"></script>
<!-- JS file -->
<script src="/js/Autoexclusion/EasyAutocomplete/jquery.easy-autocomplete.min.js"></script>
<!-- CSS file -->
<link rel="stylesheet" href="/js/Autoexclusion/EasyAutocomplete/easy-autocomplete.min.css">
<!-- Custom input Bootstrap -->
<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>
<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
@endsection
