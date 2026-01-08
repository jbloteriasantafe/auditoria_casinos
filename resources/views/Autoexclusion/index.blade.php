@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas', 'iconoMaquinas')</span>
@endsection

<?php
$permitir_manejar_papel = false;//Deshabilitado por ahora $usuario->es_superusuario || $usuario->es_administrador;
?>

@section('estilos')
<link rel="stylesheet" href="/css/paginacion.css">
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
</style>
@endsection

@section('contenidoVista')
<div class="col-xl-2">
  <div class="row">
    @if($usuario->tienePermiso('agregar_ae') || $usuario->tienePermiso('aym_ae_plataformas'))
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
    @if($usuario->tienePermiso('descargar_aes'))
    <div class="col-xl-12 col-md-4">
      <a href="" id="btn-descargar-ae" style="text-decoration: none;">
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
    </div>
    @endif
    
    @if($usuario->tienePermiso('agregar_ae') || $usuario->tienePermiso('aym_ae_plataformas'))
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
      <h5>Nombres</h5>
      <input name="nombres" class="form-control" id="buscadorNombres" value=""/>
    </div>
    <div class="col-md-2">
      <h5>Apellido</h5>
      <input name="apellido" class="form-control" id="buscadorApellido" value=""/>
    </div>
    <div class="col-md-2">
      <h5>DNI</h5>
      <input name="dni" class="form-control" id="buscadorDni" value="{{$dni}}"/>
    </div>
    <div class="col-md-2">
      <h5>Correo</h5>
      <input name="correo" class="form-control" id="buscadorCorreo" value=""/>
    </div>
    <div class="col-md-2">
      <h5>Estado</h5>
      <select name="estado" id="buscadorEstado" class="form-control selectEstado">
        <option selected="" value="">- Todos los estados -</option>
        @foreach ($estados_autoexclusion as $estado)
          <option value="{{$estado->id_nombre_estado}}">{{$estado->descripcion}}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <h5>Casino</h5>
      <select name="casino" id="buscadorCasino" class="form-control selectCasinos" data-js-setear-plataforma="#selectEscondidoPlataforma">
        <option value="">-Todos los Casinos-</option>
        @foreach ($casinos as $casino)
        <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
        @endforeach
        @foreach ($plataformas as $p)
        <option value="" data-id_plataforma="{{$p->id_plataforma}}">{{$p->nombre}}</option>
        @endforeach
      </select>
      <select name="plataforma" id="selectEscondidoPlataforma" class="form-control" style="display: none;">
        <option value=""></option>
        @foreach ($plataformas as $p)
        <option value="{{$p->id_plataforma}}">{{$p->nombre}}</option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="row">
    <h5>Desde</h5>
    <div class="col-md-3">
      <h5>Fecha autoexclusión</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_autoexclusion_d"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha renovación</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_renovacion_d"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha vencimiento</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_vencimiento_d"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha cierre definitivo</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_cierre_definitivo_d"'])
      @endcomponent
    </div>
  </div>
  <div class="row">
    <h5>Hasta</h5>
    <div class="col-md-3">
      <h5>Fecha autoexclusión</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_autoexclusion_h"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha renovación</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_renovacion_h"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha vencimiento</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_vencimiento_h"'])
      @endcomponent
    </div>
    <div class="col-md-3">
      <h5>Fecha cierre definitivo</h5>
      @component('Components/inputFecha',['attrs' => 'name="fecha_cierre_definitivo_h"'])
      @endcomponent
    </div>
  </div>
  @if($permitir_manejar_papel)
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
    <th value="casino_plataforma" estado="">CASINO<i class="fa fa-sort"></i></th>
    <th value="ae_datos.nro_dni" estado="">DNI<i class="fa fa-sort"></i></th>
    <th value="ae_datos.apellido" estado="">APELLIDO<i class="fa fa-sort"></i></th>
    <th value="ae_datos.nombres" estado="">NOMBRES<i class="fa fa-sort"></i></th>
    <th class="smalltext" value="ae_nombre_estado.descripcion" estado="">ESTADO<i class="fa fa-sort"></i></th>
    <th class="smalltext" value="ae_estado.fecha_ae" estado="">F. AE<i class="fa fa-sort"></i></th>
    <th class="smalltext " value="ae_estado.fecha_renovacion" estado="">F. RENOV<i class="fa fa-sort"></i></th>
    <th class="smalltext" value="ae_estado.fecha_vencimiento" estado="">F. VENC<i class="fa fa-sort"></i></th>
    <th class="smalltext" value="ae_estado.fecha_cierre_ae" estado="">F. CIERRE<i class="fa fa-sort"></i></th>
    <th style="flex: 2;">ACCIONES</th>
    @if($permitir_manejar_papel)
    <th>PAPEL</th>
    @endif
  </tr>
  @endslot
  
  @slot('molde')
  <tr class="filaBusqueda">
    <td class="casino_plataforma"></td>
    <td class="dni"></td>
    <td class="apellido"></td>
    <td class="nombres"></td>
    <td class="estado smalltext"></td>
    <td class="fecha_ae smalltext"></td>
    <td class="fecha_renovacion smalltext"></td>
    <td class="fecha_vencimiento smalltext"></td>
    <td class="fecha_cierre_ae smalltext"></td>
    <td class="acciones" style="flex: 2;">
      <button id="btnVerMas" class="btn btn-info info" type="button" value="" title="VER MÁS" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-search-plus"></i>
      </button>
      @if($usuario->tienePermiso('modificar_ae'))
      <button id="btnEditar" class="btn btn-info info" type="button" value="" title="EDITAR" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-pencil-alt"></i>
      </button>
      <button id="btnCambiarEstado" class="btn btn-info info" type="button" value="" title="" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw"></i>
      </button>
      @endif
      <a tabindex="0" id="btnSubirArchivos" class="btn btn-info info" role="button" value="" title="SUBIR ARCHIVOS" data-toggle="popover" data-html="true" data-trigger="focus" 
         data-content="">
        <i class="fa fa-fw fa-folder-open"></i>
      </a>
      <button id="btnGenerarSolicitudAutoexclusion" class="btn btn-info info" type="button" value="" title="GENERAR SOLICITUD AE" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="far fa-fw fa-file-alt"></i>
      </button>
      <button id="btnGenerarConstanciaReingreso" class="btn btn-info imprimir" type="button" value="" title="GENERAR CONSTANCIA DE REINGRESO" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-print"></i>
      </button>
      <button id="btnGenerarSolicitudFinalizacion" class="btn btn-info imprimir" type="button" value="" title="GENERAR SOLICITUD DE FINALIZACION" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-print"></i>
      </button>
      @if($usuario->tienePermiso('borrar_ae'))
      <button id="btnEliminar" class="btn btn-info info" type="button" value="" title="ELIMINAR" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
        <i class="fa fa-fw fa-trash"></i>
      </button>
      @endif
    </td>
    @if($permitir_manejar_papel)
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

<!-- MODAL AGREGAR AE-->
<div class="modal fade" id="modalAgregarAE" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
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
                        <input id="domicilio" type="text" class="form-control"  placeholder="" value="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>NRO. DOMICILIO</h5>
                        <input id="nro_domicilio" type="text" class="form-control"  placeholder="" value="" required numeric data-size="100">
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
                      <div class="col-lg-6">
                        <h5>CASINO</h5>
                        <select id="id_casino" class="form-control" required>
                          <option selected="" value="">- Seleccione un casino -</option>
                          <?php 
                            $cas_creacion = $usuario->es_superusuario? $casinos : $usuario->casinos;
                            $plats_creacion = $usuario->tienePermiso('aym_ae_plataformas')? $plataformas : [];
                          ?>
                          @foreach($cas_creacion as $casino)
                          <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                          @endforeach
                          @foreach ($plats_creacion as $p)
                          <option id="-{{$p->id_plataforma}}" value="-{{$p->id_plataforma}}">{{$p->nombre}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-lg-6">
                        <h5>ESTADO</h5>
                        <select id="id_estado" class="form-control" required>
                          <option selected="" value="" required>- Seleccione un estado -</option>
                          @foreach ($estados_elegibles as $estado)
                            <option id="{{$estado->id_nombre_estado}}" value="{{$estado->id_nombre_estado}}">{{$estado->descripcion}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-lg-6">
                        <h5>FECHA AUTOEXCLUSIÓN</h5>
                        <div class="input-group date" id="dtpFechaAutoexclusionEstado" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                          <input type="text" class="form-control" placeholder="Fecha de autoexclusion" id="fecha_autoexlusion" autocomplete="off" data-original-title="" title="" required>
                          <span id="input-times" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                          <span id="input-calendar" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                      </div>
                      <div class="col-lg-6">
                        <h5>VENCIMIENTO 1° PERIODO</h5>
                        <input id="fecha_vencimiento_periodo"type="text" class="form-control"  placeholder="" value="" disabled="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>PERMITIR RENOVACIÓN DESDE</h5>
                        <input id="fecha_renovacion" type="text" class="form-control" placeholder="" value="" disabled="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>FECHA CIERRE DEFINITIVO</h5>
                        <input id="fecha_cierre_definitivo" type="text" class="form-control"  placeholder="" value="" disabled="" required>
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
                      <textarea id="observaciones" class="form-control" placeholder="" value="" data-size="200"></textarea>
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
        </div>
      </div>
    </div>
  </div>
</div>

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
                  <h5>FECHA AUTOEXCLUSIÓN</h5>
                  <input id="infoFechaAutoexclusion" type="text" class="form-control" placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>VENCIMIENTO 1° PERÍODO</h5>
                  <input id="infoFechaVencimiento" type="text" class="form-control" placeholder="" value="" required disabled>
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

<!--MODAL SUBIR SOLICITUD AE -->
<div class="modal fade" id="modalSubirArchivo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 37%">
    <div class="modal-content">
      <div class="modal-header modalNuevo" style="background-color: #6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizarCrear" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoCrear" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" id="myModalLabel">| SUBIR ARCHIVO</h3>
      </div>
      <div id="colapsadoCrear" class="collapse in">
        <div class="modal-body modalCuerpo">
          <div class="row">
            <div class="col-md-6">
              <h5>NÚMERO DE DOCUMENTO</h5>
              <input type="text" class="form-control nro_dni" placeholder="" value="" required disabled>
              <br>
            </div>
            <div class="col-md-6">
              <h5 class="tipo_archivo">ARCHIVO</h5>
              <input type="file" class="archivo">
              <br>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-successAceptar" id="btn-subir-archivo">SUBIR ARCHIVO</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!--MODAL VER FORMULARIOS AE -->
<div class="modal fade" id="modalFormulariosAE" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title" id="myModalLabel">| VER FORMULARIOS DE AUTOEXCLUSIÓN</h3>
      </div>
      <div id="colapsado" class="collapse in">
        <div class="modal-body">
          <h6>Formularios AE - Resolución 983</h6>
          <div class="row">
            <div class="col-lg-6">
              <h5>Carátula AU 1°</h5>
              <button id="1" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver la Carátula AU 1°</button>
            </div>
            <div class="col-lg-6">
              <h5>Carátula AU 2°</h5>
              <button id="2" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver la Carátula AU 2°</button>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-6">
              <h5>Formulario AU 1°</h5>
              <button id="3" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el Formulario AU 1°</button>
            </div>
            <div class="col-lg-6">
              <h5>Formulario AU 2°</h5>
              <button id="4" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el Formulario AU 2°</button>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-6">
                <h5>Formulario Finalización AU</h5>
                <button id="5" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el Formulario Finalización AU</button>
            </div>
            <div class="col-lg-6">
              <h5>RVE N°983</h5>
              <button id="6" type="button" class="btn btn-default btn-ver-formulario" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver la RVE N°983</button>
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

<!-- MODAL IMPORTAR MASIVO -->
<div class="modal fade" id="modalImportarMasivo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
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
                                <input id="archivoImportacion" type="file" name="archivoImportacion" style="display: none;" onchange="$('#archivoImportacionText').val($(this).val());">
                            </label>
                        </span>
                    </div>
                </div>
            </div>
            <br>
            <div id="mensajeExitoImportacion" hidden>
                <div class="alert alert-success">
                    <p></p>
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
          <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
        </div>
      </div>
    </div>
  </div>
</div>

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
</script>
<script type="text/javascript" src="/js/noprint.js?1"></script> 

<script src="/js/Autoexclusion/index.js?4" type="module"  charset="utf-8"></script>
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
