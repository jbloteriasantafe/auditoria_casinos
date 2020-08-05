  @extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use\App\http\Controllers\RelevamientoAmbientalController;
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
</style>
@endsection

@section('contenidoVista')
<div class="col-xl-2">
  <div class="row">
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
  </div>
</div>
<div class="col-xl-10">
  <!-- FILTROS DE BÚSQUEDA -->
  <div class="row">
    <div class="col-md-12">
      <div id="contenedorFiltros" class="panel panel-default">
        <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
          <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
        </div>
        <div id="collapseFiltros" class="panel-collapse collapse">
          <div class="panel-body">
            <div class="row">
              <div class="col-md-3">
                <h5>Apellido</h5>
                <input class="form-control" id="buscadorApellido" value=""/>
              </div>
              <div class="col-md-3">
                <h5>DNI</h5>
                <input class="form-control" id="buscadorDni" value=""/>
              </div>
              <div class="col-md-3">
                <h5>Estado</h5>
                <select id="buscadorEstado" class="form-control selectEstado" name="">
                  <option selected="" value="">- Todos los estados -</option>
                  @foreach ($estados_autoexclusion as $estado)
                    <option id="{{$estado->id_nombre_estado}}" value="{{$estado->id_nombre_estado}}">{{$estado->descripcion}}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <h5>Casino</h5>
                <select id="buscadorCasino" class="form-control selectCasinos" name="">
                  <option value="0">-Todos los Casinos-</option>
                  @foreach ($casinos as $casino)
                  <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <h5>Fecha autoexclusión</h5>
                <div class="input-group date" id="dtpFechaAutoexclusion" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                  <input type="text" class="form-control" placeholder="Fecha de autoexclusion" id="buscadorFechaAutoexclusion" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                  <span id="input-times-autoexclusion" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span id="input-calendar-autoexclusion" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-3">
                <h5>Fecha vencimiento</h5>
                <div class="input-group date" id="dtpFechaVencimiento" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                  <input type="text" class="form-control" placeholder="Fecha de vencimiento" id="buscadorFechaVencimiento" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                  <span id="input-times-vencimiento" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span id="input-calendar-vencimiento" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-3">
                <h5>Fecha finalización</h5>
                <div class="input-group date" id="dtpFechaFinalizacion" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                  <input type="text" class="form-control" placeholder="Fecha de finalización" id="buscadorFechaFinalizacion" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                  <span id="input-times-finalizacion" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span id="input-calendar-finalizacion" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
              <div class="col-md-3">
                <h5>Fecha cierre definitivo</h5>
                <div class="input-group date" id="dtpFechaCierreDefinitivo" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                  <input type="text" class="form-control" placeholder="Fecha de cierre def." id="buscadorFechaCierreDefinitivo" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                  <span id="input-times-cierre-definitivo" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                  <span id="input-calendar-cierre-definitivo" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                </div>
              </div>
            </div>
            <br>
            <div class="row">
              <center>
                <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
              </center>
            </div>
            <br>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4>LISTADO DE AUTOEXCLUIDOS</h4>
        </div>
        <div class="panel-body">
          <table id="tablaAutoexcluidos" class="table table-fixed tablesorter">
            <thead>
              <tr>
                <th class="col-xs-1" value="ae_estado.id_casino" estado="">CASINO<i class="fa fa-sort"></i></th>
                <th class="col-xs-1" value="ae_datos.nro_dni" estado="">DNI<i class="fa fa-sort"></i></th>
                <th class="col-xs-1" value="ae_datos.apellido" estado="">APELLIDO<i class="fa fa-sort"></i></th>
                <th class="col-xs-1" value="ae_datos.nombres" estado="">NOMBRES<i class="fa fa-sort"></i></th>
                <th class="col-xs-1 smalltext" value="ae_nombre_estado.descripcion" estado="">ESTADO<i class="fa fa-sort"></i></th>
                <th class="col-xs-1 smalltext" value="ae_estado.fecha_ae" estado="">F. AE<i class="fa fa-sort"></i></th>
                <th class="col-xs-1 smalltext " value="ae_estado.fecha_renovacion" estado="">F. RENOV<i class="fa fa-sort"></i></th>
                <th class="col-xs-1 smalltext" value="ae_estado.fecha_vencimiento" estado="">F. VENC<i class="fa fa-sort"></i></th>
                <th class="col-xs-1 smalltext" value="ae_estado.fecha_cierre_ae" estado="">F. CIERRE<i class="fa fa-sort"></i></th>
                <th class="col-xs-3">ACCIONES</th>
              </tr>
            </thead>
            <tbody id="cuerpoTabla" style="height: 350px;">
              <tr class="filaTabla" style="display: none">
                <td class="col-xs-1 casino"></td>
                <td class="col-xs-1 dni"></td>
                <td class="col-xs-1 apellido"></td>
                <td class="col-xs-1 nombres"></td>
                <td class="col-xs-1 estado smalltext"></td>
                <td class="col-xs-1 fecha_ae smalltext"></td>
                <td class="col-xs-1 fecha_renovacion smalltext"></td>
                <td class="col-xs-1 fecha_vencimiento smalltext"></td>
                <td class="col-xs-1 fecha_cierre_ae smalltext"></td>
                <td class="col-xs-3 acciones">
                  <button id="btnVerMas" class="btn btn-info info" type="button" value="" title="VER MÁS" data-toggle="tooltip" data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
                    <i class="fa fa-fw fa-search-plus"></i>
                  </button>
                  @if($usuario->es_superusuario || $usuario->es_administrador)
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
                  <span></span>
                </td>
              </tr>
            </tbody>
          </table>
          <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
        </div>
      </div>
    </div>
  </div>  <!-- row tabla -->
</div> <!-- col-xs-10 -->

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
          <form id="frmAgregarAE" name="frmAgregarAE" class="form-horizontal" novalidate=" method="post"" enctype="multipart/form-data">
            <div class="form-group error">
              <div class="col-lg-12">
                <div id="columna" class="row">
                  <div class="page step1">
                    <div class="col-lg-6">
                      <h5>NÚMERO DE DOCUMENTO</h5>
                      <input id="nro_dni" name="nro_dni" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                  </div>
                  <div class="page">
                    <div class="col-lg-12">
                      <h6>Datos Personales</h6>
                    </div>
                    <div class="step2">
                      <div class="col-lg-6">
                        <h5>APELLIDO</h5>
                        <input id="apellido" name="apellido" type="text" class="form-control"  placeholder="" value="" required alpha>
                      </div>
                      <div class="col-lg-6">
                        <h5>NOMBRES</h5>
                        <input id="nombres" name="nombres" type="text" class="form-control"  placeholder="" value="" required alpha>
                      </div>
                      <div class="col-lg-6">
                        <h5>FECHA DE NACIMIENTO</h5>
                        <div class="input-group date" id="dtpFechaNacimiento" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                          <input type="text" class="form-control" placeholder="Fecha de nacimiento" id="fecha_nacimiento" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="" required>
                          <span id="input-times-nacimiento" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                          <span id="input-calendar-nacimiento" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                      </div>
                      <div class="col-lg-3">
                        <h5>SEXO</h5>
                        <select id="id_sexo" class="form-control" name="sexo" required>
                          <option selected="" value="">Seleccionar Valor</option>
                          <option value="0">Masculino</option>
                          <option value="1">Femenino</option>
                          <option value="-1">Otro</option>
                        </select>
                      </div>
                      <div class="col-lg-3">
                        <h5>ESTADO CIVIL</h5>
                        <select id="id_estado_civil" class="form-control selectEstadoCivil" name="id_estado_civil" required>
                          <option selected="" value="">Seleccionar Valor</option>
                          @foreach ($estados_civiles as $estado_civil)
                            <option id="{{$estado_civil->id_estado_civil}}" value="{{$estado_civil->id_estado_civil}}">{{$estado_civil->descripcion}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-lg-6">
                        <h5>DOMICILIO</h5>
                        <input id="domicilio" name="domicilio" type="text" class="form-control"  placeholder="" value="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>NRO. DOMICILIO</h5>
                        <input id="nro_domicilio" name="nro_domicilio" type="text" class="form-control"  placeholder="" value="" required numeric>
                      </div>
                      <div class="col-lg-6">
                        <h5>PROVINCIA</h5>
                        <input id="nombre_provincia" name="nombre_provincia" type="text" class="form-control"  placeholder="" value="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>LOCALIDAD</h5>
                        <input id="nombre_localidad" name="nombre_localidad" class="form-control"  type="text" class="form-control"  placeholder="" value="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>TELEFONO</h5>
                        <input id="telefono" name="telefono" type="text" class="form-control"  placeholder="" value="" required numeric>
                      </div>
                      <div class="col-lg-6">
                        <h5>EMAIL</h5>
                        <input id="correo" name="correo" type="text" class="form-control"  placeholder="" value="" required email>
                      </div>
                      <div class="col-lg-6">
                        <h5>OCUPACIÓN</h5>
                        <select id="id_ocupacion" class="form-control" name="id_ocupacion" required>
                            <option selected="" value="">Seleccionar Valor</option>
                            @foreach($ocupaciones as $ocupacion)
                            <option value="{{$ocupacion->id_ocupacion}}">{{$ocupacion->nombre}}</option>
                            @endforeach
                        </select>
                      </div>
                      <div class="col-lg-6">
                        <h5>CAPACITACIÓN</h5>
                        <select id="id_capacitacion" class="form-control" name="id_capacitacion" required>
                          <option selected="" value="">Seleccionar Valor</option>
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
                      <input id="nombre_apellido" name="nombre_apellido" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                    <div class="col-lg-6">
                      <h5>DOMICILIO</h5>
                      <input id="domicilio_vinculo" name="domicilio_vinculo" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                    <div class="col-lg-6">
                      <h5>PROVINCIA</h5>
                      <input id="nombre_provincia_vinculo" name="nombre_provincia_vinculo" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                    <div class="col-lg-6">
                      <h5>LOCALIDAD</h5>
                      <input id="nombre_localidad_vinculo" name="nombre_localidad_vinculo" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                    <div class="col-lg-6">
                      <h5>TELEFONO</h5>
                      <input id="telefono_vinculo" name="telefono" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                    <div class="col-lg-6">
                      <h5>VINCULO</h5>
                      <input id="vinculo" name="vinculo" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                  </div>
                  <div class="page">
                    <div class="step3">
                      <div class="col-lg-6">
                        <h5>CASINO</h5>
                        <select id="id_casino" class="form-control" name="id_casino">
                          <option selected="" value="">- Seleccione un casino -</option>
                          <?php $cas_creacion = $usuario->casinos; if($usuario->es_superusuario) $cas_creacion = $casinos; ?>
                          @foreach($cas_creacion as $casino)
                          <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-lg-6">
                        <h5>ESTADO</h5>
                        <select id="id_estado" name="id_estado" class="form-control">
                          <option selected="" value="">- Seleccione un estado -</option>
                          @foreach ($estados_elegibles as $estado)
                            <option id="{{$estado->id_nombre_estado}}" value="{{$estado->id_nombre_estado}}">{{$estado->descripcion}}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-lg-6">
                        <h5>FECHA AUTOEXCLUSIÓN</h5>
                        <div class="input-group date" id="dtpFechaAutoexclusionEstado" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                          <input type="text" class="form-control" placeholder="Fecha de autoexclusion" id="fecha_autoexlusion" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                          <span id="input-times" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                          <span id="input-calendar" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        </div>
                      </div>
                      <div class="col-lg-6">
                        <h5>VENCIMIENTO 1° PERIODO</h5>
                        <input id="fecha_vencimiento_periodo" name="fecha_vencimiento_periodo" type="text" class="form-control"  placeholder="" value="" disabled="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>PERMITIR RENOVACIÓN DESDE</h5>
                        <input id="fecha_renovacion" name="fecha_renovacion" type="text" class="form-control" placeholder="" value="" disabled="" required>
                      </div>
                      <div class="col-lg-6">
                        <h5>FECHA CIERRE DEFINITIVO</h5>
                        <input id="fecha_cierre_definitivo" name="fecha_cierre_definitivo" type="text" class="form-control"  placeholder="" value="" disabled="" required>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-lg-6">
                        <h5>FOTO #1</h5>
                        <div>
                          <a href="" target="_blank">FOTO1.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="foto1" type="file" name="foto1">
                      </div>
                      <div class="col-lg-6">
                        <h5>FOTO #2</h5>
                        <div>
                          <a href="" target="_blank">FOTO2.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="foto2" data-borrado="false" type="file" name="">
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-lg-6">
                        <h5>SCAN DNI</h5>
                        <div>
                          <a href="" target="_blank">DNI.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="scan_dni" data-borrado="false" type="file" name="">
                      </div>
                      <div class="col-lg-6">
                        <h5>SOLICITUD AUTOEXCLUSIÓN</h5>
                        <div>
                          <a href="" target="_blank">SOLAE.PDF</a>
                          <button type="button" class="sacarArchivo btn btn-link"><i class="fa fa-times"></i></button>
                        </div>
                        <input id="solicitud_autoexclusion" data-borrado="false" type="file" name="">
                      </div>
                    </div>
                  </div>
                  <div class="page">
                    <div class="col-lg-12">
                      <h6>Encuesta <span style="font-size: 12px">(OPCIONAL)</span></h6>
                    </div>
                    <div class="col-lg-3">
                      <h5>JUEGO PREFERIDO</h5>
                      <select id="juego_preferido" class="form-control" name="juego_preferido">
                        <option value="">- Seleccione un juego -</option>
                        @foreach($juegos as $juego)
                        <option value="{{$juego->id_juego_preferido}}">{{$juego->nombre}}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-lg-3">
                      <h5>F. DE ASISTENCIA</h5>
                      <select id="id_frecuencia_asistencia" class="form-control" name="id_frecuencia_asistencia">
                        <option selected="" value="">- Seleccione un valor -</option>
                        @foreach($frecuencias as $frecuencia)
                        <option value="{{$frecuencia->id_frecuencia}}">{{$frecuencia->nombre}}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-lg-3">
                      <h5>VECES</h5>
                      <input id="veces" name="veces" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                    <div class="col-lg-3">
                      <h5>TIEMPO JUGANDO (HS)</h5>
                      <input id="tiempo_jugado" name="tiempo_jugado" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿ES SOCIO DEL CLUB DE JUGADORES?</h5>
                        <select id="socio_club_jugadores" name="socio_club_jugadores" class="form-control">
                          <option selected="" value="">- Seleccione una opción -</option>
                          <option value="SI">SI</option>
                          <option value="NO">NO</option>
                        </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿CONOCE EL PROGRAMA JUEGO RESPONSABLE?</h5>
                      <select id="juego_responsable" name="juego_responsable" class="form-control">
                        <option selected="" value="">- Seleccione una opción -</option>
                        <option value="SI">SI</option>
                        <option value="NO">NO</option>
                      </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿DECISIÓN POR PROBLEMAS DE AUTOCONTROL?</h5>
                      <select id="autocontrol_juego" name="autocontrol_juego" class="form-control">
                        <option selected="" value="">- Seleccione una opción -</option>
                        <option value="SI">SI</option>
                        <option value="NO">NO</option>
                      </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿CÓMO ASISTE?</h5>
                      <select id="como_asiste" name="como_asiste" class="form-control">
                        <option selected="" value="">- Seleccione una opción -</option>
                        <option value="0">SOLO</option>
                        <option value="1">ACOMPAÑADO</option>
                      </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿DESEA RECIBIR INFORMACIÓN SOBRE JR?</h5>
                      <select id="recibir_informacion" name="recibir_informacion" class="form-control">
                        <option selected="" value="">- Seleccione una opción -</option>
                        <option value="SI">SI</option>
                        <option value="NO">NO</option>
                      </select>
                    </div>
                    <div class="col-lg-6">
                      <h5>¿MEDIO DE RECEPCIÓN?</h5>
                      <input id="medio_recepcion" name="medio_recepcion" type="text" class="form-control"  placeholder="" value="" required>
                    </div>
                    <div class="col-lg-12">
                      <h5>OBSERVACIONES</h5>
                        <textarea id="observaciones" name="observaciones" class="form-control"  placeholder="" value="" required></textarea>
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
          <form id="frmAutoExcluido" name="frmAutoExcluido" class="form-horizontal" novalidate="">
            <div class="infoDatosPersonales">
              <h6>Datos personales</h6>
              <div class="row">
                <div class="col-lg-6">
                  <h5>APELLIDO</h5>
                  <input id="infoApellido" name="infoApellido" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>NOMBRES</h5>
                  <input id="infoNombres" name="infoNombres" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>FECHA DE NACIMIENTO</h5>
                  <input id="infoFechaNacimiento" name="infoFechaNacimiento" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>DNI</h5>
                  <input id="infoDni" name="infoDni" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>SEXO</h5>
                  <select id="infoSexo" name="infoSexo" class="form-control" disabled>
                    <option selected="" value="">No ingresado</option>
                    <option value="0">Masculino</option>
                    <option value="1">Femenino</option>
                    <option value="-1">Otro</option>
                  </select>
                </div>
                <div class="col-lg-6">
                  <h5>ESTADO CIVIL</h5>
                  <select id="infoEstadoCivil" class="form-control" name="infoEstadoCivil" disabled>
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
                  <input id="infoDomicilio" name="infoDomicilio" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>NRO. DOMICILIO</h5>
                  <input id="infoNroDomicilio" name="infoNroDomicilio" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>PROVINCIA</h5>
                  <input id="infoProvincia" name="infoProvincia" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>LOCALIDAD</h5>
                  <input id="infoLocalidad" name="infoLocalidad" class="form-control"  type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>TELEFONO</h5>
                  <input id="infoTelefono" name="infoTelefono" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>EMAIL</h5>
                  <input id="infoEmail" name="infoEmail" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>OCUPACIÓN</h5>
                  <select id="infoOcupacion" name="infoOcupacion" class="form-control" disabled>
                    <option selected="" value="">No ingresado</option>
                    @foreach($ocupaciones as $ocupacion)
                    <option value="{{$ocupacion->id_ocupacion}}">{{$ocupacion->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-6">
                  <h5>CAPACITACIÓN</h5>
                  <select id="infoCapacitacion" class="form-control" name="infoCapacitacion" disabled>
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
                  <input id="infoNombreApellidoVinculo" name="infoNombreApellidoVinculo" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>DOMICILIO</h5>
                  <input id="infoDomiclioVinculo" name="infoDomiclioVinculo" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>PROVINCIA</h5>
                  <input id="infoProvinciaVinculo" name="infoProvinciaVinculo" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>LOCALIDAD</h5>
                  <input id="infoLocalidadVinculo" name="infoLocalidadVinculo" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>TELÉFONO</h5>
                  <input id="infoTelefonoVinculo" name="infoTelefonoVinculo" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>VÍNCULO</h5>
                  <input id="infoVinculo" name="infoVinculo" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
            </div>
            <br>
            <div class="infoEstadoAutoexclusion">
              <h6>Información de estado de la autoexclusión</h6>
              <div class="row">
                <div class="col-lg-6">
                  <h5>CASINO</h5>
                  <select id="infoCasino" class="form-control selectCasinos" name="infoCasino" disabled>
                    <option value="0">Todos los Casinos</option>
                    @foreach ($casinos as $casino)
                    <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-6">
                  <h5>ESTADO</h5>
                  <select id="infoEstado" class="form-control selectEstado" name="infoEstado" disabled>
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
                  <input id="infoFechaAutoexclusion" name="infoFechaAutoexclusion" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>VENCIMIENTO 1° PERÍODO</h5>
                  <input id="infoFechaVencimiento" name="infoFechaVencimiento" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>PERMITIR RENOVACIÓN DESDE</h5>
                  <input id="infoFechaRenovacion" name="infoFechaRenovacion" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>FECHA CIERRE DEFINITIVO</h5>
                  <input id="infoFechaCierreDefinitivo" name="infoFechaCierreDefinitivo" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
            </div>
            <br>
            <div class="archivosImportados">
              <h6>Archivos importados</h6>
              <div class="row">
                <div class="col-lg-6">
                  <h5>FOTO #1</h5>
                  <button type="button" class="foto1 btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente a Foto #1</button>
                </div>
                <div class="col-lg-6">
                  <h5>FOTO #2</h5>
                  <button type="button" class="foto2 btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente a Foto #2</button>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>SCAN DNI</h5>
                  <button type="button" class="scandni btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente al scan DNI</button>
                </div>
                <div class="col-lg-6">
                  <h5>SOLICITUD AUTOEXCLUSION</h5>
                  <button type="button" class="solicitud_ae btn btn-default btn-ver-mas" style="width:419px; background-color: #4FC3F7 !important; color: white; font-weight: bold;" >Click aquí para ver el archivo correspondiente a la SAE</button>
                </div>
              </div>
            </div>
            <br>
            <div class="infoEncuesta">
              <h6>Encuesta</h6>
              <div class="row">
                <div class="col-lg-3">
                  <h5>JUEGO PREFERIDO</h5>
                  <select id="infoJuegoPreferido" class="form-control" name="infoJuegoPreferido" disabled>
                    <option value="">No ingresado</option>
                    @foreach($juegos as $juego)
                    <option value="{{$juego->id_juego_preferido}}">{{$juego->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-3">
                  <h5>F. DE ASISTENCIA</h5>
                  <select id="infoFrecuenciaAsistencia" class="form-control" name="infoFrecuenciaAsistencia" disabled>
                    <option selected="" value="">No ingresado</option>
                    @foreach($frecuencias as $frecuencia)
                    <option value="{{$frecuencia->id_frecuencia}}">{{$frecuencia->nombre}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-lg-3">
                  <h5>VECES</h5>
                  <input id="infoVeces" name="infoVeces" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-3">
                  <h5>TIEMPO JUGANDO (HS)</h5>
                  <input id="infoTiempoJugado" name="infoTiempoJugado" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>¿ES SOCIO DEL CLUB DE JUGADORES?</h5>
                  <input id="infoSocioClubJugadores" name="infoSocioClubJugadores" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>¿CONOCE EL PROGRAMA JUEGO RESPONSABLE?</h5>
                  <input id="infoJuegoResponsable" name="infoinfoJuegoResponsableFechaRenovacion" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>¿DECISIÓN POR PROBLEMAS DE AUTOCONTROL?</h5>
                  <input id="infoAutocontrol" name="infoAutocontrol" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>¿CÓMO ASISTE?</h5>
                  <select id="infoComoAsiste" name="infoComoAsiste" class="form-control" disabled>
                    <option selected="" value="">No ingresado</option>
                    <option value="0">SOLO</option>
                    <option value="1">ACOMPAÑADO</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <h5>¿DESEA RECIBIR INFORMACIÓN SOBRE JR?</h5>
                  <input id="infoRecibirInformacion" name="infoRecibirInformacion" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
                <div class="col-lg-6">
                  <h5>¿MEDIO DE RECEPCIÓN?</h5>
                  <input id="infoMedioRecepcion" name="infoMedioRecepcion" type="text" class="form-control"  placeholder="" value="" required disabled>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-12">
                  <h5>OBSERVACIONES</h5>
                  <input id="infoObservaciones" name="infoObservaciones" class="form-control"  placeholder="" value="" required disabled>
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
      <div  id="colapsadoCrear" class="collapse in">
        <div class="modal-body modalCuerpo">
          <div class="row">
            <div class="col-md-6">
              <h5>NÚMERO DE DOCUMENTO</h5>
              <input type="text" class="form-control nro_dni"  placeholder="" value="" required disabled>
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
<script src="js/paginacion.js" charset="utf-8"></script>
<!-- JavaScript personalizado -->
<script src="/js/Autoexclusion/index.js" charset="utf-8"></script>
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
