  @extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection


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

                <!-- <div class="row">
                  <div class="col-lg-12 col-xl-9"> -->

                    <!-- <div class="row">  -->
                      <!-- fila de FILTROS -->
                        <!-- <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltros" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">

                                  <div class="col-md-3">
                                    <h5>Fecha de la sesión</h5> -->
                                    <!-- <div class="form-group"> -->
                                       <!-- <div class='input-group date' id='dtpBuscadorFecha' data-link-field="buscadorFecha" data-link-format="yyyy-mm-dd">
                                           <input type='text' class="form-control" placeholder="Fecha de relevamiento" id="B_fecharelevamiento"/>
                                           <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                           <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                       </div>
                                       <input class="form-control" type="hidden" id="buscadorFecha" value=""/> -->
                                    <!-- </div> -->
                                  <!-- </div> -->

                                  <!-- <div class="col-md-3">
                                    <h5>Fecha de Sesión</h5>
                                    <input type="date" id="buscadorFecha" class="form-control" style="padding: 0px!important;">
                                  </div> -->
                                  <!-- <div class="col-md-3">
                                    <h5>Estado de la Sesión</h5>
                                    <select id="buscadorEstado" class="form-control" name="">
                                        <option value="0">-Todos los Estados-</option>
                                        <option value="1">ABIERTA</option>
                                        <option value="2">CERRADA</option>
                                    </select>
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Casino</h5>
                                    <select id="buscadorCasino" class="form-control selectCasinos" name="">
                                        <option value="0">-Todos los Casinos-</option>

                                    </select>
                                  </div>
                                  <div class="col-md-3">
                                    <h5 style="color:#f5f5f5;">boton buscar</h5>
                                    <button id="btn-buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div><br>
                              </div>  -->
                              <!-- /.panel-body -->
                            <!-- </div> -->
                          <!-- </div>  -->
                          <!-- /.panel -->
                        <!-- </div>  -->
                        <!-- /.col-md-12 -->
                    <!-- </div>  -->
                    <!-- Fin de la fila de FILTROS -->


                      <!-- <div class="row"> RESULTADOS BÚSQUEDA -->
                        <!-- <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>TODAS LAS SESIONES</h4>
                            </div>
                            <div class="panel-body modal-cuerpo">
                              <table id="tablaResultados" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-2" value="fecha_inicio" estado="">FECHA  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" value="hora_inicio" estado="">HORA INICIO <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" value="id_casino" estado="">CASINO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="id_usuario_inicio" estado="">INICIÓ USUARIO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" value="hora_fin" estado="">HORA FINAL  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2" value="id_usuario_fin" estado="">CERRÓ USUARIO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-1" value="id_estado" estado="">ESTADO  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-2">ACCIONES</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTabla" style="height: 370px;">


                                </tbody>
                              </table>
                              <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                              </div>
                            </div>
                          </div> -->



                        <!-- </div> Fin del col de los filtros -->

                      <!-- </div> Fin del row de la tabla -->

                      <div class="col-lg-4 col-xl-3">
                        <div class="row">
                          <div class="col-md-12">
                            <a href="" id="btn-agregar-ae" style="text-decoration: none;">
                                <div class="panel panel-default panelBotonNuevo">
                                  <center><img class="imgNuevo" src="/img/logos/informes_bingo_white.png"><center>
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
                      </div>


                      <!-- <div class="col-lg-4 col-xl-3">BOTÓN GENERAR FORMULARIO SESIÓN -->
                        <!-- <div class="row">
                          <div class="col-md-12">
                            <a href="" id="btn-planilla-sesion" style="text-decoration: none;">
                                <div class="panel panel-default panelBotonNuevo">
                                  <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                                    <div class="backgroundNuevo"></div>
                                      <div class="row">
                                        <div class="col-xs-12">
                                          <center>
                                            <h5 class="txtLogo">+</h5>
                                            <h4 class="txtNuevo">Formulario Sesión</h4>
                                          </center>
                                        </div>
                                  </div>
                              </div>
                            </a>
                           </div>
                          </div>
                      </div> -->


                      <!-- <div class="col-lg-4 col-xl-3"> BOTÓN GENERAR FORMULARIO RELEVAMIENTO  -->
                        <!-- <div class="row">
                          <div class="col-md-12">
                            <a href="" id="btn-planilla-relevamiento" style="text-decoration: none;">
                                <div class="panel panel-default panelBotonNuevo">
                                  <center><img class="imgNuevo" src="/img/logos/relevamientos_white.png"><center>
                                    <div class="backgroundNuevo"></div>
                                      <div class="row">
                                        <div class="col-xs-12">
                                          <center>
                                            <h5 class="txtLogo">+</h5>
                                            <h4 class="txtNuevo">Formulario Relevamiento</h4>
                                          </center>
                                        </div>
                                  </div>
                              </div>
                            </a>
                           </div>
                          </div>
                      </div> -->


            <!-- </div> columna row -->


    <!-- Modal AGREGAR AE-->
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

                              <!-- Step #1 ativo -->
                              <div class="page step1">
                                <div class="col-lg-6">
                                  <h5>NÚMERO DE DOCUMENTO</h5>
                                  <input id="nro_dni" name="nro_dni" type="text" class="form-control"  placeholder="" value="" required>
                                </div>
                              </div>
                              <!-- fin step #1 -->

                              <!-- step #2  datos personales+contacto-->
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
                                                <option selected="" value="">Seleccione casino</option>
                                              @foreach($casinos as $casino)
                                              <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                              @endforeach
                                          </select>
                                        </div>
                                        <div class="col-lg-6">
                                          <h5>ESTADO</h5>
                                          <select id="id_estado" name="id_estado" class="form-control">
                                            <!-- <option selected="" value="">Seleccionar Valor</option> -->
                                            <option value="1">VIGENTE</option>
                                            <option value="2">RENOVADO</option>
                                            <option value="3" selected="">PV</option>
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

                                <!-- step #4 encuenta -->
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
                                    <h5>T. JUGADO</h5>
                                    <input id="tiempo_jugado" name="tiempo_jugado" type="text" class="form-control"  placeholder="" value="" required>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>ES SOCIO DEL CLUB DE JUGADORES?</h5>
                                      <select id="socio_club_jugadores" name="socio_club_jugadores" class="form-control">
                                        <option selected="" value="">Seleccionar Valor</option>
                                        <option value="SI">SI</option>
                                        <option value="NO">NO</option>
                                      </select>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>CONOCE EL PROGRAMA JUEGO RESPONSABLE?</h5>
                                    <select id="juego_responsable" name="juego_responsable" class="form-control">
                                      <option selected="" value="">Seleccionar Valor</option>
                                      <option value="SI">SI</option>
                                      <option value="NO">NO</option>
                                    </select>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>DECISIÓN POR PROBLEMAS DE AUTOCONTROL SOBRE EL JUEGO?</h5>
                                    <select id="autocontrol_juego" name="autocontrol_juego" class="form-control">
                                      <option selected="" value="">Seleccionar Valor</option>
                                      <option value="SI">SI</option>
                                      <option value="NO">NO</option>
                                    </select>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>CÓMO ASISTE?</h5>
                                    <select id="como_asiste" name="como_asiste" class="form-control">
                                      <option selected="" value="">Seleccionar Valor</option>
                                      <option value="0">SOLO</option>
                                      <option value="1">ACOMPAÑADO</option>
                                    </select>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>DESEA RECIBIR INFORMACIÓN SOBRE JR?</h5>
                                    <select id="recibir_informacion" name="recibir_informacion" class="form-control">
                                      <option selected="" value="">Seleccionar Valor</option>
                                      <option value="SI">SI</option>
                                      <option value="NO">NO</option>
                                    </select>
                                  </div>
                                  <div class="col-lg-6">
                                    <h5>MEDIO DE RECEPCIÓN?</h5>
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

    <!-- Modal CERRAR SESIÓN -->
    <!-- <div class="modal fade" id="modalCierreSesion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| CERRAR SESIÓN</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmCierreSesion" name="frmCierreSesion" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                          <div class="col-lg-12">
                            <div id="columna2" class="row">
                              <div id="terminoCierreSesion" class="row" style="margin-bottom: 15px;">

                                <div class="col-lg-4">
                                  <h5>FECHA CIERRE</h5>
                                  <div class='input-group date' id='dtpFechaCierreSesion' data-date-format="yyyy-mm-dd" data-link-format="yyyy-mm-dd">
                                      <input type='text' class="form-control" placeholder="Fecha de sesión" id="fechaCierreSesion" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                  </div> -->

                                  <!-- <input type="date" id="fechaCierreSesion" class="form-control" style="padding: 0px!important;"> -->
                                <!-- </div> -->
<!--
                                <div class="col-lg-4">
                                  <h5>HORA CIERRE</h5>
                                  <div class='input-group date' id='dtpHoraCierreSesion' data-date-format="HH:ii:ss" data-link-format="HH:ii">
                                      <input type='text' class="form-control" placeholder="Hora de sesión" id="horaCierreSesion" autocomplete="off" style="background-color: rgb(255,255,255);"/>
                                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                  </div> -->

                                  <!-- <input id="horaCierreSesion" name="horaInicioNueva" type="time" class="form-control"  style="padding: 0!important;" placeholder="" value="" required> -->
                                <!-- </div>

                                <div class="col-lg-4">
                                  <h5>CASINO</h5>
                                  <select id="casino_cierre" class="form-control selectCasinos" name="" disabled="">

                                  </select>
                                </div>


                                <div class="col-lg-6">
                                  <h5>POZO DOTACIÓN FINAL</h5>
                                  <input id="pozo_dotacion_final" name="pozo_dotacion_final" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-6">
                                  <h5>POZO EXTRA FINAL</h5>
                                  <input id="pozo_extra_final" name="pozo_extra_final" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>VALOR DEL CARTON</h5>
                                  <input id="valor_carton_f" name="valor_carton_f" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>SERIE FINAL</h5>
                                  <input id="serie_final" name="serie_final" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>CARTON FINAL</h5>
                                  <input id="carton_final" name="carton_final" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3 text-center">
                                 <h5>-</h5>
                                 <button id="btn-agregarTerminoFinal" class="btn btn-success btn-xs" type="button"><i class="fa fa-fw fa-plus"></i> Mas filas</button>
                               </div>

                              </div>

                          </div>
                          <span id="alerta_sesion" class="alertaSpan"></span>


                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar-cierre">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_sesion" value="0">
                  <input type="hidden" id="cantidad_detalles" value="0">
                </div>
              </div>
            </div>
          </div>
    </div> -->

    <!-- Modal CARGAR PARTIDA/RELEVAMIENTO -->
    <!-- <div class="modal fade" id="modalRelevamiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title" id="myModalLabel">| CARGAR RELEVAMIENTO</h3>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmRelevamiento" name="frmCierreSesion" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                          <div class="col-lg-12">
                            <div id="columnaRelevamiento" class="row">
                              <div id="terminoRelevamiento" class="row" style="margin-bottom: 15px;">


                                <div class="col-lg-4">
                                  <h5>NRO. DE PARTIDA</h5>
                                  <input id="nro_partida" name="nro_partida" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>HORA DE JUGADA</h5>
                                  <div class='input-group date' id='dtpHoraJugada' data-date-format="HH:ii" data-link-format="HH:ii">
                                      <input type='text' class="form-control" placeholder="Hora de partida" id="hora_jugada" autocomplete="off" style="background-color: rgb(255,255,255);" />
                                      <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                      <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                  </div> -->
                                  <!-- <input id="hora_jugada" name="hora_jugada" type="time" class="form-control"  placeholder="" value=""> -->
                                <!-- </div>

                                <div class="col-lg-4">
                                  <h5>VALOR DEL CARTON</h5>
                                  <input id="valor_carton_rel" name="valor_carton_rel" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>SERIE INICIAL</h5>
                                  <input id="serie_inicio" name="serie_inicio" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>CARTON INICIAL</h5>
                                  <input id="carton_inicio_i" name="carton_inicio_i" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>CARTON FINAL</h5>
                                  <input id="carton_fin_i" name="carton_fin_i" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>SERIE FINAL</h5>
                                  <input id="serie_fin" name="serie_fin" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>CARTON INICIAL</h5>
                                  <input id="carton_inicio_f" name="carton_inicio_f" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-4">
                                  <h5>CARTON FINAL</h5>
                                  <input id="carton_fin_f" name="carton_fin_f" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="row">
                                <div class="col-lg-4">
                                  <h5>CARTONES VENDIDOS</h5>
                                  <input id="cartones_vendidos" name="cartones_vendidos" type="text" class="form-control"  placeholder="" value="">
                                </div>
                              </div>

                                <div class="row">
                                  <div class="col-lg-4">
                                    <h5>PREMIO LINEA</h5>
                                    <input id="premio_linea" name="premio_linea" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                  <div class="col-lg-4">
                                    <h5>PREMIO BINGO</h5>
                                    <input id="premio_bingo" name="premio_bingo" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                </div>

                                <div class="row">
                                  <div class="col-lg-4">
                                    <h5>MAXI LINEA</h5>
                                    <input id="maxi_linea" name="maxi_linea" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                  <div class="col-lg-4">
                                    <h5>MAXI BINGO</h5>
                                    <input id="maxi_bingo" name="maxi_bingo" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                </div>

                                <div class="row">
                                  <div class="col-lg-4">
                                    <h5>POSICIÓN BOLA LINEA</h5>
                                    <input id="pos_bola_linea" name="pos_bola_linea" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                  <div class="col-lg-4">
                                    <h5>POSICIÓN BOLA BINGO</h5>
                                    <input id="pos_bola_bingo" name="pos_bola_bingo" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                </div>

                                <div class="row">
                                  <div class="col-lg-4">
                                    <h5>NOMBRE DEL PREMIO</h5>
                                    <select class="form-control" id="nombre_premio">
                                      <option value="" selected="">Seleccionar Valor</option>

                                    </select>
                                  </div>
                                  <div class="col-lg-4">
                                    <h5>NÚMERO CARTÓN GANADOR</h5>
                                    <input id="carton_ganador" name="carton_ganador" type="text" class="form-control"  placeholder="" value="">
                                  </div>
                                  <div class="col-lg-4 text-center">
                                   <h5>-</h5>
                                   <button id="btn-agregarTerminoRelevamiento" class="btn btn-success btn-xs" type="button"><i class="fa fa-fw fa-plus"></i> Mas filas</button>
                                 </div>

                          </div>



                              </div>

                          </div>
                          <span id="alerta_sesion" class="alertaSpan"></span>


                        </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar-relevamiento">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_sesion" value="0">
                </div>
              </div>
            </div>
          </div>
    </div> -->

    <!-- Modal DETALLES + RELEVAMIENTOS -->
    <!-- <div class="modal fade" id="modalDetallesRel" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" style="min-width:85%;">
             <div class="modal-content">
                <div class="modal-header pbzero">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title pbtitle" id="myModalLabel">| DETALLES SESIÓN</h3>

                  <ul class="nav nav-tabs bbnav">
                    <li class="active"><a data-toggle="tab" href="#detalles">DETALLES</a></li>
                    <li><a data-toggle="tab" href="#historialCambios">HISTORIAL DE CAMBIOS</a></li>
                  </ul>
                </div>

                <div  id="colapsado" class="collapse in">
                 <div class="modal-body modal-Cuerpo">
                  <form id="frmDetallesRel" name="frmDetallesRel" class="form-horizontal" novalidate="">
                      <div class="form-group error">
                        <div class="tab-content">
                          <div class="col-lg-12 tab-pane fade in active" id="detalles">
                            <div id="columnaDetallesRel" class="row">
                              <div id="terminoDetallesRel" class="row" style="margin-bottom: 15px;">
                                <div class="col-lg-12">
                                <h6>DATOS DE LA SESIÓN</h6>
                                </div>

                                <div class="col-lg-3">
                                  <h5>POZO DOTACIÓN INICIAL</h5>
                                  <input id="pozo_dotacion_inicial_d" name="pozo_dotacion_inicial_d" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>POZO EXTRA FINAL</h5>
                                  <input id="pozo_extra_inicial_d" name="pozo_extra_inicial_d" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>POZO DOTACIÓN FINAL</h5>
                                  <input id="pozo_dotacion_final_d" name="pozo_dotacion_final_d" type="text" class="form-control"  placeholder="" value="">
                                </div>

                                <div class="col-lg-3">
                                  <h5>POZO EXTRA FINAL</h5>
                                  <input id="pozo_extra_final_d" name="pozo_extra_final_d" type="text" class="form-control"  placeholder="" value="">
                                </div> -->

                                <!-- datos de detalles -->
                              <!-- <div class="row">
                                <div class="col-lg-12">
                                <h6>DETALLES DE LA SESIÓN</h6>
                                </div>
                                <div class="col-lg-2">
                                  <h5>VALOR CARTON</h5>
                                </div>
                                <div class="col-lg-2">
                                  <h5>SERIE INICIAL</h5>
                                </div>
                                <div class="col-lg-2">
                                  <h5>CARTON INICIAL</h5>
                                </div>
                                <div class="col-lg-2">
                                  <h5>SERIE FINAL</h5>
                                </div>
                                <div class="col-lg-2">
                                  <h5>CARTON FINAL</h5>
                                </div>
                              </div>

                              <div id="terminoDatos2" class="row" style="margin-bottom: 15px;">
                              </div> -->
                              <!-- fin datos de detalles -->
                                <!-- </div>
                                <div class="col-lg-12">
                                <h6>RELEVAMIENTOS CARGADOS</h6>
                              </div>
                          </div>
                          <span id="alerta_sesion" class="alertaSpan"></span>

                          <div class="panel-body modal-cuerpo">
                            <table id="tablaResultadosRel" class="table table-striped">
                              <thead>
                                <tr>
                                  <th class="col" value="nro_partida_r">Nro. PARTIDA</th>
                                  <th class="col" value="hora_sesion_r">HORA SESION</th>
                                  <th class="col" value="serie_inicial_r">SERIE INICIAL</th>
                                  <th class="col" value="carton_inicial_r">CARTON INICIAL</th>
                                  <th class="col" value="carton_final_r">CARTON FINAL</th>
                                  <th class="col" value="serie_final_r">SERIE FINAL</th>
                                  <th class="col" value="carton_inicial_rr">CARTON INICIAL</th>
                                  <th class="col" value="carton_final_rr">CARTON FINAL</th>
                                  <th class="col" value="cartones_vendidos_r">CARTONES VENDIDOS</th>
                                  <th class="col" value="valor_carton_r">VALOR CARTON</th>
                                  <th class="col" value="bola_linea_r">BOLA LÍNEA</th>
                                  <th class="col" value="bola_bingo_r">BOLA BINGO</th>
                                  <th class="col" value="premio_bingo_r">PREMIO LÍNEA</th>
                                  <th class="col" value="premio_bingo_r">PREMIO BINGO</th>
                                  <th class="col" value="pozo_dot_r">POZO DOT.</th>
                                  <th class="col" value="pozo_extra_r">POZO EXTRA</th>
                                  <th class="col" value="usuario_r">USUARIO</th>
                                  <th class="col" id="accionesResultadoRel">ACCIONES</th>
                                </tr>
                              </thead>
                              <tbody id="cuerpoTablaRel">


                              </tbody>
                            </table>
                            </div>


                          </div>
                                <div class="col-lg-12 tab-pane fade" id="historialCambios">
                                <div class="panel-body modal-cuerpo">
                                  <table id="tablaResultadosHis" class="table table-striped">
                                    <thead>
                                      <tr>
                                        <th class="col" value="fecha_h">FECHA</th>
                                        <th class="col" value="usuario_incio_h">USUARIO INICIO</th>
                                        <th class="col" value="fecha_inicio_h">FECHA INICIO</th>
                                        <th class="col" value="hora_inicio_h">HORA INICIO</th>
                                        <th class="col" value="pozo_dot_inicial_h">POZO DOT. INICIAL</th>
                                        <th class="col" value="pozo_extra_inicial_h">POZO EXTRA INICIAL</th>
                                        <th class="col" value="usuario_fin_h">USUARIO FIN</th>
                                        <th class="col" value="fecha_fin_h">FECHA FIN</th>
                                        <th class="col" value="hora_fin_h">HORA FIN</th>
                                        <th class="col" value="pozo_dot_final_h">POZO DOT. FINAL</th>
                                        <th class="col" value="pozo_extra_final_h">POZO EXTRA FINAL</th>
                                      </tr>
                                    </thead>
                                    <tbody id="cuerpoTablaHis">
                                    </tbody>
                                  </table>
                                  </div>

                                </div>
                        </div>
                      </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">

                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="id_sesion" value="0">
                </div>
              </div>
            </div>
          </div>
    </div> -->

    <!-- Modal Eliminar -->
    <!-- <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeEliminar"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarSesion" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  <input type="hidden" id="cantidad_partidas" value="0">
                </div>
            </div>
          </div>
    </div> -->

    <!-- Modal Eliminar -->
    <!-- <div class="modal fade" id="modalEliminarPartida" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleEliminarPartida" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeEliminarPartida"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarPartida" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                </div>
            </div>
          </div>
    </div> -->

    <!-- Modal reAbrirSesion -->
    <!-- <div class="modal fade" id="modalAbrirSesion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleAbrirSesion" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeAbrirSesion"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerAbrirSesion" id="btn-abrirSesion" value="0">ACEPTAR</button>
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button>
                </div>
            </div>
          </div>
    </div> -->


    <!-- Modal reabrir sesión -->
    <!-- <div class="modal fade" id="modalAbrirSesion" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">

        <div class="modal-dialog">
           <div class="modal-content">

             <div class="modal-header" style="background: #d9534f; color: #E53935;">
               <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                 <h3 class="modal-title" style="color:#000000;">ADVERTENCIA</h3>
             </div>

                  <div class="modal-body" style="color:#fff; background-color:#FFFFF;">
              <form id="frmMotivos">

                      <h6 style="color:#000000 !important; font-size:14px;"></h6>
                      <br>
                      <h6 id="mensajeAbrirSesion" style="color:#000000"></h6>
                      <div id="campo-valor">
                        <input placeholder="" id="motivo-reapertura" type="text" class="form-control">
                      </div>
              </form>
                    </div>
            <br>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" id="btn-abrirSesion">ACEPTAR</button>
              <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal">CANCELAR</button>
              <button type="button" class="btn btn-dangerAbrirSesion" id="btn-abrirSesion" value="0">ACEPTAR</button>
              <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">CANCELAR</button> -->
            <!-- </div>
        </div>
      </div>
</div> -->

    <!-- Modal ERRORES / ADVERTENCIAS -->
    <!-- <div class="modal fade" id="modalCorrecta" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-title-correcta" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                      <div class="form-group error ">
                          <div class="col-lg-12">
                            <strong id="mensajeCorrecta"></strong>
                          </div>
                      </div>

                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">ACEPTAR</button>
                </div>
            </div>
          </div>
    </div> -->


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
    <script src="/js/Autoexcluidos/index.js" charset="utf-8"></script>
    <!-- JS file -->
    <script src="/js/Autoexcluidos/EasyAutocomplete/jquery.easy-autocomplete.min.js"></script>
    <!-- CSS file -->
    <link rel="stylesheet" href="/js/Autoexcluidos/EasyAutocomplete/easy-autocomplete.min.css">

    <!-- <script src="/js/Autoexcluidos/EasyAutocomplete/jquery.easy-autocomplete.min.js" charset="utf-8"></script> -->

    <script src="/js/Bingo/lista-datos.js" type="text/javascript"></script>


    <!-- Custom input Bootstrap -->
    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>


    @endsection
