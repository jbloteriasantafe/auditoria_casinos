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
                        <th class="col-xs-1">SEXO<i class="fa fa-sort"></i></th>
                        <th class="col-xs-1">EDAD<i class="fa fa-sort"></i></th>
                        <th class="col-xs-1">FECHA AE<i class="fa fa-sort"></i></th>
                        <th class="col-xs-1">FECHA VENC.<i class="fa fa-sort"></i></th>
                        <th class="col-xs-1">FECHA RENOV.<i class="fa fa-sort"></i></th>
                        <th class="col-xs-1">FECHA CIERRE DEF.<i class="fa fa-sort"></i></th>
                      </tr>
                    </thead>
                    <tbody id="cuerpoTabla" style="height: 350px;">
                      <tr class="filaEjemplo">
                        <td class="col-xs-1.5 dni">444444444</td>
                        <td class="col-xs-2 apellido">Martinez</td>
                        <td class="col-xs-2 nombres">Juan Pedro</td>
                        <td class="col-xs-2 sexo">M</td>
                        <td class="col-xs-1 edad">33</td>
                        <td class="col-xs-1 fecha_ae">12/04/2019</td>
                        <td class="col-xs-1 fecha_venc">09/10/2019</td>
                        <td class="col-xs-1 fecha_renov">-/-/-</td>
                        <td class="col-xs-1 fecha_cierre_def">09/04/2020</td>
                      </tr>
                    </tbody>
                  </table>
                  <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                </div>
            </div>
          </div>
        </div>  <!-- row tabla -->
      </div> <!-- row principal -->





    <!-- MMODAL AGREGAR AE-->
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
                                          <span id="input-times" class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                          <span id="input-calendar" class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
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
                                            <option value="1">VIGENTE</option>
                                            <option value="2">RENOVADO</option>
                                            <option value="3" selected="">PENDIENTE DE VALIDACIÓN</option>
                                            <option value="4">FINALIZADO x/AE</option>
                                            <option value="5">VENCIDO</option>
                                          </select>
                                        </div>

                                        <div class="col-lg-6">
                                          <h5>FECHA AUTOEXCLUSIÓN</h5>
                                          <div class="input-group date" id="dtpFechaAutoexclusion" data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
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
                                        <option value="">-Todos los Casinos-</option>
                                        @foreach($juegos as $juego)
                                        <option value="{{$juego->id_juego_preferido}}">{{$juego->nombre}}</option>
                                        @endforeach
                                    </select>
                                  </div>
                                  <div class="col-lg-3">
                                    <h5>F. DE ASISTENCIA</h5>
                                    <select id="id_frecuencia_asistencia" class="form-control" name="ifrecuencia_asistencia">
                                          <option selected="" value="">Seleccione casino</option>
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
                                        <option selected="" value="">- Seleccionar opción -</option>
                                        <option value="SI">SI</option>
                                        <option value="NO">NO</option>
                                      </select>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>¿CONOCE EL PROGRAMA JUEGO RESPONSABLE?</h5>
                                    <select id="juego_responsable" name="juego_responsable" class="form-control">
                                      <option selected="" value="">- Seleccionar opción -</option>
                                      <option value="SI">SI</option>
                                      <option value="NO">NO</option>
                                    </select>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>¿DECISIÓN POR PROBLEMAS DE AUTOCONTROL?</h5>
                                    <select id="autocontrol_juego" name="autocontrol_juego" class="form-control">
                                      <option selected="" value="">- Seleccionar opción -</option>
                                      <option value="SI">SI</option>
                                      <option value="NO">NO</option>
                                    </select>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>¿CÓMO ASISTE?</h5>
                                    <select id="como_asiste" name="como_asiste" class="form-control">
                                      <option selected="" value="">- Seleccionar opción -</option>
                                      <option value="0">SOLO</option>
                                      <option value="1">ACOMPAÑADO</option>
                                    </select>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>¿DESEA RECIBIR INFORMACIÓN SOBRE JR?</h5>
                                    <select id="recibir_informacion" name="recibir_informacion" class="form-control">
                                      <option selected="" value="">- Seleccionar opción -</option>
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
