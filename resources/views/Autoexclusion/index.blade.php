  @extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

<?php
use Illuminate\Http\Request;
use App\Http\Controllers\UsuarioController;
use\App\http\Controllers\RelevamientoAmbientalController;
$user = UsuarioController::getInstancia()->quienSoy()['usuario'];
$puede_fiscalizar = $user->es_fiscalizador || $user->es_superusuario;
$puede_validar = $user->es_administrador || $user->es_superusuario || $user->es_control;
$puede_eliminar = $user->es_administrador || $user->es_superusuario;
$puede_modificar_valores = $user->es_administrador || $user->es_superusuario;
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

</style>
@endsection

@section('contenidoVista')

    <div class="col-xl-3">
        <div class="row">
          <div class="col-xl-12 col-md-4">
                <a href="" id="btn-agregar-ae" style="text-decoration: none;">
                  <div class="panel panel-default panelBotonNuevo">
                      <center>
                        <img class="imgNuevo" src="/img/logos/informes_bingo_white.png">
                      </center>
                      <div class="backgroundNuevo"></div>
                      <div class="row">
                          <div class="col-xs-12">
                            <center>
                              <h5 class="txtLogo">+</h5>
                              <h4 class="txtNuevo">Agregar AE</h4>
                            </center>
                          </div>
                      </div>
                  </div>
                </a>
          </div>

        </div>
      </div><!-- row botones -->


      <div class="col-xl-9">

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
                        <th class="col-xs-2 activa">DNI<i class="fa fa-sort-desc"></i></th>
                        <th class="col-xs-2">APELLIDO<i class="fa fa-sort"></i></th>
                        <th class="col-xs-2">NOMBRES<i class="fa fa-sort"></i></th>
                        <th class="col-xs-2">ESTADO<i class="fa fa-sort"></i></th>
                        <th class="col-xs-2">FECHA AE<i class="fa fa-sort"></i></th>
                        <th class="col-xs-2">ACCIONES<i class="fa fa-sort"></i></th>
                      </tr>
                    </thead>
                    <tbody id="cuerpoTabla" style="height: 350px;">
                      <tr class="filaTabla" style="display: none">
                        <td class="col-sm-2 dni"></td>
                        <td class="col-xs-2 apellido"></td>
                        <td class="col-xs-2 nombres"></td>
                        <td class="col-xs-2 estado"></td>
                        <td class="col-xs-2 fecha_ae"></td>
                        <td class="col-xs-2 acciones">
                          <button id="btnVerMas" class="btn btn-info info" type="button" value="">
                            <i class="fa fa-fw fa-search-plus"></i></button>
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
      </div> <!-- row principal -->





    <!-- MODAL AGREGAR AE-->
    <div class="modal fade" id="modalAgregarAE" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| AGREGAR AE</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmAgregarAE" name="frmAgregarAE" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                          <div class="col-lg-12">
                            <div id="columna" class="row">

                              <!-- step #1 activo -->
                              <div class="page step1">
                                <div class="col-lg-6">
                                  <h5>NÚMERO DE DOCUMENTO</h5>
                                  <input id="nro_dni" name="nro_dni" type="text" class="form-control"  placeholder="" value="" required>
                                </div>
                              </div>
                              <!-- fin step #1 -->

                              <!-- step #2  datos personales + contacto -->
                              <div class="page">
                                    <div class="col-lg-12">
                                        <h6>Datos Personales</h6>
                                    </div>
                                <div class="step2">
                                    <div class="col-lg-6">
                                      <h5>APELLIDO</h5>
                                      <input id="apellido" name="apellido" type="text" class="form-control"  placeholder="" value="" required>
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>NOMBRES</h5>
                                      <input id="nombres" name="nombres" type="text" class="form-control"  placeholder="" value="" required>
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>FECHA DE NACIMIENTO</h5>
                                      <div class="input-group date" id="dtpFechaNacimiento" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                                          <input type="text" class="form-control" placeholder="Fecha de nacimiento" id="fecha_nacimiento" autocomplete="off" style="background-color: rgb(255,255,255);" data-original-title="" title="">
                                          <span id="input-times-nacimiento" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                          <span id="input-calendar-nacimiento" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                      </div>
                                    </div>
                                    <div class="col-lg-3">
                                      <h5>SEXO</h5>
                                      <select id="id_sexo" class="form-control" name="sexo">
                                        <option selected="" value="">Seleccionar Valor</option>
                                        <option value="0">Masculino</option>
                                        <option value="1">Femenino</option>
                                        <option value="-1">Otro</option>
                                      </select>
                                    </div>
                                    <div class="col-lg-3">
                                      <h5>ESTADO CIVIL</h5>
                                      <select id="id_estado_civil" name="id_estado_civil" class="form-control">
                                        <option selected="" value="">Seleccionar Valor</option>
                                        <option value="1">Soltero</option>
                                        <option value="2">Casado</option>
                                        <option value="3">Separado / Divorciado</option>
                                        <option value="4">Unido de Hecho</option>
                                        <option value="5">Viudo</option>
                                        <option value="6">No Contesta</option>
                                      </select>
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>DOMICILIO</h5>
                                      <input id="domicilio" name="domicilio" type="text" class="form-control"  placeholder="" value="" required>
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>NRO. DOMICILIO</h5>
                                      <input id="nro_domicilio" name="nro_domicilio" type="text" class="form-control"  placeholder="" value="" required>
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>PROVINCIA</h5>
                                      <input id="nombre_provincia" name="nombre_provincia" type="text" class="form-control"  placeholder="" value="" required>
                                      <!-- <input id="id_provincia" name="id_provincia" type="text" class="form-control"  placeholder="" value="" required> -->
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>LOCALIDAD</h5>
                                      <input id="nombre_localidad" name="nombre_localidad" class="form-control"  type="text" class="form-control"  placeholder="" value="" required>
                                      <!-- <input id="id_localidad" class="form-control"  type="text" class="form-control"  placeholder="" value="" required> -->
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>TELEFONO</h5>
                                      <input id="telefono" name="telefono" type="text" class="form-control"  placeholder="" value="" required>
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>EMAIL</h5>
                                      <input id="correo" name="correo" type="text" class="form-control"  placeholder="" value="" required>
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>OCUPACIÓN</h5>
                                      <select id="id_ocupacion" class="form-control" name="id_ocupacion">
                                          <option selected="" value="">Seleccionar Valor</option>
                                          @foreach($ocupaciones as $ocupacion)
                                          <option value="{{$ocupacion->id_ocupacion}}">{{$ocupacion->nombre}}</option>
                                          @endforeach
                                      </select>
                                    </div>
                                    <div class="col-lg-6">
                                      <h5>CAPACITACIÓN</h5>
                                      <select id="id_capacitacion" name="id_capacitacion" class="form-control">
                                        <option selected="" value="">Seleccionar Valor</option>
                                        <option value="1">Primaria</option>
                                        <option value="2">Secundaria</option>
                                        <option value="3">Terciaria</option>
                                        <option value="4">Universitaria</option>
                                        <option value="5">Otra</option>
                                        <option value="6">No Contesta</option>
                                      </select>
                                    </div>
                                  </div>
                                <div class="col-lg-12">
                                    <h6>Datos Persona de Contacto <span style="font-size: 12px">(OPCIONAL)</span></h6>
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
                                    <!-- <input id="id_provincia_vinculo" name="id_provincia_vinculo" type="text" class="form-control"  placeholder="" value="" required> -->
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>LOCALIDAD</h5>
                                    <input id="nombre_localidad_vinculo" name="nombre_localidad_vinculo" type="text" class="form-control"  placeholder="" value="" required>
                                    <!-- <input id="id_localidad_vinculo" name="id_localidad_vinculo" type="text" class="form-control"  placeholder="" value="" required> -->
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
                              <!-- fin step #2 -->

                              <!-- step #3 fechas e importaciones -->
                              <div class="page">
                                <div class="step3">
                                        <div class="col-lg-6">
                                          <h5>CASINO</h5>
                                          <select id="id_casino" class="form-control" name="id_casino">
                                                <option selected="" value="">- Seleccione un casino -</option>
                                              @foreach($casinos as $casino)
                                              <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                              @endforeach
                                          </select>
                                        </div>
                                        <div class="col-lg-6">
                                          <h5>ESTADO</h5>
                                          <select id="id_estado" name="id_estado" class="form-control">
                                            <option selected="" value="">- Seleccione un estado -</option>
                                            @foreach ($estados_autoexclusion as $estado)
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
                                          <input id="fecha_renovacion" name="fecha_renovacion" type="text" class="form-control" style="color: red; font-weight: 800;" placeholder="" value="" disabled="" required>
                                        </div>
                                        <div class="col-lg-6">
                                          <h5>FECHA CIERRE DEFINITIVO</h5>
                                          <input id="fecha_cierre_definitivo" name="fecha_cierre_definitivo" type="text" class="form-control"  placeholder="" value="" disabled="" required>
                                        </div>
                                      </div>
                                  <div class="col-lg-6">
                                    <h5>FOTO #1</h5>
                                    <input id="foto1" data-borrado="false" type="file" name="">
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>FOTO #2</h5>
                                    <input id="foto2" data-borrado="false" type="file" name="">
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>SCAN DNI</h5>
                                    <input id="scan_dni" data-borrado="false" type="file" name="">
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>SOLICITUD AUTOEXCLUSIÓN</h5>
                                    <input id="solicitud_autoexclusion" data-borrado="false" type="file" name="">
                                  </div>
                                </div>
                                <!-- fin step #3 -->

                                <!-- step #4 encuesta -->
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
                                <!-- fin step #4 -->

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






    <!-- MODAL VER MAS -->
    <div class="modal fade" id="modalVerMas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header modalVerMas">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title">| VER MÁS</h3>
                </div>

              <div id="colapsado" class="collapse in">
                <div class="modal-body">
                  <form id="frmAutoExcluido" name="frmAutoExcluido" class="form-horizontal" novalidate="">

                    <!-- ver mas: datos personales -->
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
                              <option selected="" value="">Seleccionar Valor</option>
                              <option value="0">Masculino</option>
                              <option value="1">Femenino</option>
                              <option value="-1">Otro</option>
                            </select>
                          </div>
                          <div class="col-lg-6">
                            <h5>ESTADO CIVIL</h5>
                            <select id="infoEstadoCivil" name="infoEstadoCivil" class="form-control" disabled>
                              <option selected="" value="">Seleccionar Valor</option>
                              <option value="1">Soltero</option>
                              <option value="2">Casado</option>
                              <option value="3">Separado / Divorciado</option>
                              <option value="4">Unido de Hecho</option>
                              <option value="5">Viudo</option>
                              <option value="6">No Contesta</option>
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
                                <option selected="" value="">Seleccionar Valor</option>
                                @foreach($ocupaciones as $ocupacion)
                                <option value="{{$ocupacion->id_ocupacion}}">{{$ocupacion->nombre}}</option>
                                @endforeach
                            </select>
                          </div>
                          <div class="col-lg-6">
                            <h5>CAPACITACIÓN</h5>
                            <select id="infoCapacitacion" name="infoCapacitacion" class="form-control" disabled>
                              <option selected="" value="">Seleccionar Valor</option>
                              <option value="1">Primaria</option>
                              <option value="2">Secundaria</option>
                              <option value="3">Terciaria</option>
                              <option value="4">Universitaria</option>
                              <option value="5">Otra</option>
                              <option value="6">No Contesta</option>
                            </select>
                          </div>
                        </div>
                    </div><br>

                    <!-- ver mas: datos de contacto -->
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
                    </div><br>

                    <!-- ver mas: información de estado de autoexclusión -->
                    <div class="infoEstadoAutoexclusion">
                      <h6>Información de estado de la autoexclusión</h6>
                      <div class="row">
                        <div class="col-lg-6">
                          <h5>CASINO</h5>
                          <select id="infoCasino" class="form-control selectCasinos" name="infoCasino" disabled>
                              <option value="0">-Todos los Casinos-</option>
                              @foreach ($casinos as $casino)
                                <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                              @endforeach
                          </select>
                        </div>
                        <div class="col-lg-6">
                          <h5>ESTADO</h5>
                          <select id="infoEstado" class="form-control selectEstado" name="infoEstado" disabled>
                            <option selected="" value="">- Todos los estados -</option>
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
                    </div><br>

                    <!-- ver mas: información de encuesta -->
                    <div class="infoEncuesta">
                        <h6>Encuesta</h6>
                        <div class="row">
                          <div class="col-lg-3">
                            <h5>JUEGO PREFERIDO</h5>
                            <select id="infoJuegoPreferido" class="form-control" name="infoJuegoPreferido" disabled>
                                <option value="">- Seleccione un juego -</option>
                                @foreach($juegos as $juego)
                                <option value="{{$juego->id_juego_preferido}}">{{$juego->nombre}}</option>
                                @endforeach
                            </select>
                          </div>
                          <div class="col-lg-3">
                            <h5>F. DE ASISTENCIA</h5>
                            <select id="infoFrecuenciaAsistencia" class="form-control" name="infoFrecuenciaAsistencia" disabled>
                                  <option selected="" value="">- Seleccione un valor -</option>
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
                              <option selected="" value="">- Seleccione una opción -</option>
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



    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    <!-- Comienza modal de ayuda -->
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
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript paginacion -->
    <script src="js/paginacion.js" charset="utf-8"></script>
    <!-- JavaScript personalizado -->
    <script src="/js/Autoexclusion/index.js" charset="utf-8"></script>
    <!-- JS file -->
    <script src="/js/Autoexclusion/EasyAutocomplete/jquery.easy-autocomplete.min.js"></script>
    <!-- CSS file -->
    <link rel="stylesheet" href="/js/Autoexclusion/EasyAutocomplete/easy-autocomplete.min.css">

    <!-- <script src="/js/Autoexclusion/EasyAutocomplete/jquery.easy-autocomplete.min.js" charset="utf-8"></script> -->

    <script src="/js/Bingo/lista-datos.js" type="text/javascript"></script>


    <!-- Custom input Bootstrap -->
    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>


    @endsection
