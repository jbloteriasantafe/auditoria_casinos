<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
$id_usuario = $usuario['usuario']->id_usuario;
$cas = $usuario['usuario']->casinos;
?>

 @extends('includes.dashboard')
 @section('headerLogo')
 <span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
 @endsection

@section('estilos')
<link href="css/bootstrap-datetimepicker.css" rel="stylesheet"/>
<link rel='stylesheet' href='/css/fullcalendar.min.css'/>
<link rel="stylesheet" href="css/bootstrap-datetimepicker.css">
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">

<style media="screen">

.fc-center h2 {
   font-family: Roboto-BoldCondensed;
   font-size: 25px;
}

.fc-content {
  white-space: normal !important;
}


</style>



@endsection


@section('contenidoVista')

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">

    </div>
    <div class="panel-body">
      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'editar_calendario'))
        <div class="admin" id="calendar"></div>
        @else
        <div class="fisca" id="calendar"></div>
      @endif

    </div>
    </div>
  </div>
</div>

<!--****** MODAL PARA CREAR EL EVENTO ****-->
<div class="modal fade" id="modalEvento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header" style="font-family: Roboto-Black; background-color:#4CAF50">
          <button type="button" class="close" data-dismiss="modal">×</button>
          <h3 class="modal-title">CREAR EVENTO</h3>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-xs-6">
              <h5>EVENTO</h5>
                <input class="form-control" id="title" name="title" placeholder="Escriba el título del Evento" type="text" value="">
            </div>
            <div class="col-xs-6">
              <h5>Casino</h5>
              <select class="form-control" id="casinoEv">
                <option class="default"  value="" selected>- Seleccione casino -</option>
              </select>
            </div>
          </div>

          <input type="hidden" id="startTime"/>
          <input type="hidden" id="endTime"/>

          <br>
          <div class="row">
            <div class="col-xs-12">
              <h6>CUÁNDO:</h6>
              <h5 class="controls controls-row" id="when" style="margin-top:5px; margin-left:10px"></h5>
              <br>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <h6>Desde: </h6>
              <div class='input-group date' id='horaDesde' data-link-field="horaEvento" data-date-format="dd MM YYYY HH:ii" data-link-format="dd MM YYYY HH:ii">
                <input type='text' class="form-control" placeholder="Hora de Inicio" id="horarioEvD"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" />
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
              <input type="hidden" id="horaEventoDesde" value=""/>
            </div>
            <div class="col-xs-6">
              <h6>Hasta: </h6>
              <div class='input-group date' id='horaHasta' data-link-field="horaEvento" data-date-format="HH:ii" data-link-format="HH:ii">
                <input type='text' class="form-control" placeholder="Hora de Finalización" id="horarioEvH"  data-content='Este campo es <strong>requerido</strong>' data-trigger="manual" data-toggle="popover" data-placement="top" />
                <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
              </div>
              <input type="hidden" id="horaEventoHasta" value=""/>
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-xs-12">
              <h6>DESCRIPCIÓN:</h6>
              <textarea id="descripcionEv" class="form-control" style="resize:vertical;"></textarea>
            </div>
          </div>
          <br>
          <div class="row">

            <div class="col-xs-6 rolNotif">
              <h5>Usuario/s a Notificar</h5>
            </div>

            <div class="col-xs-6 contenedorEventos">
              <!-- <input id="colorEvento" type="color" ></select> -->
                <h5>Tipo Evento</h5>
                <div id="moldeTipo" hidden="true" class="row filaTipos">
                  <div class="col-xs-12">

                    <input id="0" class="colorEvento" style="font-size:15px;" name="color" type="radio" value="#CDDC49">
                     <i class="fa fa-circle" style="font-size:25px"></i><h7 class="dTipo">Movimiento</h7>

                  </div>
                </div>

            </div>

        </div> <!-- fin row -->

        <div class="modal-footer">
          <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">CANCELAR</button>
          <button id="guardarEventoNew" class="btn btn-default" type="button" >GUARDAR</button>
        </div>
        <div id="mensajeErrorCreacion" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Debe Completar los datos solicitados</span>
        </div> <!-- mensaje -->
      </div>

    </div>
  </div>

    </div>
</div>


<style media="screen">
    .contenedorEvento {
      text-align: center;
      margin-bottom: 30px;
    }
    .iconoEvento {
      margin: 10px 0px;
      color: #304FFE;
    }
    .tituloEvento {
      font-family: Roboto-BoldCondensed;
      font-size: 24px;
      /*text-transform: uppercase;*/
    }
    .descripcionEvento {
      margin-top: 5px;
      padding: 0px 20px;
      text-align: center;
      font-family: Roboto-Regular;
      font-size: 18px;
    }

    .casinoEvento, .tipodeEvento {
      font-family: Roboto-Regular;
      font-size: 20px;
    }


    .contenedorFecha p {
      font-family: Roboto-Regular;
      font-size: 18px;
    }

    .iconoFecha {
      text-align: center;
      padding: 0px !important;
    }

    .fechaInicio .fa-calendar-alt {
      color: #E91E63;
    }
    .fechaFin .fa-calendar-alt {
      color: #FFC107;
    }
</style>

<div id="diseñoCalendario" class="modal fade">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
          <div class="modal-header" style="background-color:#1DE9B6;">
              <h3 class="modal-title">DETALLE DEL EVENTO</h3>
          </div>

          <div class="modal-body">
              <div class="contenedorEvento">
                  <i class="fa fa-thumbtack fa-2x iconoEvento"></i>
                  <h4 class="tituloEvento">TÍTULO DEL EVENTO</h4>
                  <p class="descripcionEvento">Acá va la descripción del evento. Fruta batida. No se si sirve, pero acá está. Nada más que agregar.</p>
              </div>

              <div class="row" style="border-top: 1px solid #ccc; border-bottom:1px solid #ccc; padding-top:30px; padding-bottom:30px;">
                  <div class="col-md-6" style="text-align:center;border-right:1px solid #ccc;">
                      <h5>CASINO</h5>
                      <p class="casinoEvento">Santa Fe</p>
                  </div>
                  <div class="col-md-6" style="text-align:center;">
                      <h5>TIPO DE EVENTO</h5>
                      <p class="tipodeEvento">Mantenimiento</p>
                  </div>
              </div>

              <br>

              <div class="contenedorFecha row">
                  <div class="col-md-6" style="border-right:1px solid #ccc;">
                    <center>
                      <h5 style="padding:0px; margin-bottom:20px;">FECHA INICIO</h5>
                    </center>
                    <div class="row fechaInicio">
                        <div class="col-xs-2 col-xs-offset-1 iconoFecha">
                            <i class="far fa-calendar-alt fa-2x"></i>
                        </div>
                        <div class="col-xs-9">
                            <p class="fecha">23 Mayo 2018</p>
                        </div>
                    </div>
                    <div class="row horaInicio">
                        <div class="col-xs-2 col-xs-offset-1 iconoFecha">
                            <i class="far fa-clock fa-2x" style="position:relative; left:-2px;"></i>
                        </div>
                        <div class="col-xs-9">
                            <p>10:20 H</p>
                        </div>
                    </div>


                  </div>
                  <div class="col-md-6">

                    <center>
                      <h5 style="padding:0px; margin-bottom:20px;">FECHA FIN</h5>
                    </center>

                    <div class="row fechaFin">
                        <div class="col-xs-2 col-xs-offset-1 iconoFecha">
                            <i class="far fa-calendar-alt fa-2x"></i>
                        </div>
                        <div class="col-xs-9">
                            <p class="fecha">23 Mayo 2018</p>
                        </div>
                    </div>
                    <div class="row horaFin">
                        <div class="col-xs-2 col-xs-offset-1 iconoFecha">
                            <i class="far fa-clock fa-2x" style="position:relative; left:-2px;"></i>
                        </div>
                        <div class="col-xs-9">
                            <p>10:20 H</p>
                        </div>
                    </div>

                  </div>
              </div>
          </div>

          <div class="modal-footer">
              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'editar_calendario'))
                <button id="btn-eliminarEvento" class="btn btn-dangerEliminar" type="button" name="button" value="0">ELIMINAR</button>
              @endif
              <button class="btn btn-default" type="button" name="button" data-dismiss="modal">SALIR</button>
          </div>
      </div>
    </div>
</div>




<div id="diseñoCrearEvento" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header" style="background-color:#1DE9B6;">
            <h3 class="modal-title">CREAR EVENTO</h3>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>TÍTULO DEL EVENTO</h5>
                    <input id="tituloEvento" class="form-control" type="text" name="" value="" style="">
                </div>
                <div class="col-md-4">
                    <h5>CASINO</h5>
                    <select id="casinoEvento" class="form-control" name="">
                    </select>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-12">
                    <h5>DESCRIPCIÓN</h5>
                    <textarea id="descripcionEvento" class="form-control" name="name" style="min-height:84px;resize:vertical;"></textarea>
                </div>
            </div>

            <br>

            <div class="row">
                <div class="col-md-6" style="border-right: 1px solid #ccc;">
                    <h5>DESDE</h5>

                    <div class='input-group date' id='desdeFecha' data-link-field="desde_fecha" data-link-format="yyyy-mm-dd">
                        <input type='text' class="form-control" placeholder="Fecha de inicio" id=""/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="desde_fecha" value=""/>

                    <br>

                    <div class='input-group date' id='desdeHora' data-link-field="desde_hora" data-link-format="HH:ii">
                        <input type='text' class="form-control" placeholder="Hora de inicio" id=""/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-clock"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="desde_hora" value=""/>

                    <br>
                </div>
                <div class="col-md-6">
                    <h5>HASTA</h5>


                    <div class='input-group date' id='hastaFecha' data-link-field="hasta_fecha" data-link-format="yyyy-mm-dd">
                        <input type='text' class="form-control" placeholder="Fecha de fin" id=""/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-calendar-alt"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="hasta_fecha" value=""/>

                    <br>

                    <div class='input-group date' id='hastaHora' data-link-field="hasta_hora" data-link-format="HH:ii">
                        <input type='text' class="form-control" placeholder="Hora de fin" id=""/>
                        <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                        <span class="input-group-addon" style="cursor:pointer;"><i class="far fa-clock"></i></span>
                    </div>
                    <input class="form-control" type="hidden" id="hasta_hora" value=""/>

                    <br>
                </div>
            </div>

            <br>

            <style media="screen">
                #diseñoCrearEvento ul {
                  list-style: none;
                  padding: 0px 6px;
                }
                #diseñoCrearEvento ul li span {
                  font-family: Roboto-Regular;
                  font-size: 16px;
                  margin-left: 6px;
                }

                #tiposEvento li i {
                    margin: 0px 3px 0px 5px;
                    position: relative;
                    top: -3px;
                }
                #tiposEvento li span {
                    position: relative;
                    top: -3px;
                }
                .alertaEvento {
                  font-family: Roboto-Regular;
                  font-weight: bold;
                  font-size: 16px;
                  color: #EF5350;
                }
            </style>

            <div class="row">
                <div class="col-md-4">
                    <h5>DESTINATARIOS</h5>
                    <ul id="destinatariosEvento">
                    </ul>

                </div>
                <div class="col-md-8">
                    <h5>TIPO DE EVENTO</h5>
                    <ul id="tiposEvento">
                    </ul>
                </div>
                <div class="col-md-12" style="margin-top:20px;">
                    <p id="alertaDestinatario" class="alertaEvento">Debe seleccionar al menos un destinatario del evento.</p>
                    <p id="alertaTipoEvento" class="alertaEvento">Debe seleccionar al menos un tipo de evento.</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="btn-eventoNuevo" class="btn btn-successAceptar" type="button" name="button">ACEPTAR</button>
            <button id="btn-cancelarEvento" class="btn btn-default" type="button" name="button" data-dismiss="modal">CANCELAR</button>
        </div>
    </div>
  </div>
</div>


<!-- MODAL PARA CREAR UN NUEVO TIPO DE EVENTO -->
<div class="modal fade" id="modalTipoEv" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; background-color:#6dc7be;">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-title modalVerMas" id="myModalLabel">NUEVO TIPO DE EVENTO</h3>
      </div>

      <div class="modal-body" style="">
        <div  id="colapsado" class="collapse in">

          <div class="row"> <!-- ROW 1 -->
            <div class="col-md-12">
              <h6>DESCRIPCIÓN: </h6>

              <input id="tipoNuevo" type="text" class="form-control" placeholder="Escriba un título para identificar el tipo" value="">

            </div>
            <br>
          </div> <!-- FIN ROW 1 -->
          <br>
          <div class="row"> <!-- ROW 2 -->
            <div class="col-md-6">
              <h6>COLOR DE FONDO: </h6>
              <input id="colorFondo" class="colorF" name="colorFondo" type="color" val="" />
            </div>
            <div class="col-md-6">
              <h6>COLOR TEXTO</h6>
              <input id="opcion1" class="colorTexto" style="font-size:15px;" name="colorText" type="radio" value="#000000">
                <i class="fa fa-circle" style="color: #000000; font-size:25px"></i>
                <br>
              <input id="opcion2" class="colorTexto" style="font-size:15px;" name="colorText" type="radio" value="#FFFFFF">
                <div style="height:20px; width:20px; color: #FFFFFF; border:1px solid #000000; border-radius:50%; display:inline-block;"></div>
                <br>
            </div>
          </div> <!-- FIN ROW 2 -->

        </div> <!-- colapsado -->
      </div> <!-- modal body -->

      <div class="modal-footer">

        <button id="guardarTipo" type="button" class="btn btn-default" value="" >GUARDAR</button>
        <button id="boton-cancelar-tipo" type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>

        <div id="mensajeErrorCreacionTipo" hidden>
            <br>
            <span style="font-family:'Roboto-Black'; font-size:16px; color:#EF5350;">ERROR</span>
            <br>
            <span style="font-family:'Roboto-Regular'; font-size:16px; color:#555;">Verifique que los datos ingresados sean únicos y correctos</span>
        </div> <!-- mensaje -->

      </div> <!-- modal footer -->

    </div> <!-- modal content -->
  </div> <!-- modal dialog -->
</div> <!-- modal fade -->

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| CALENDARIO</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Calendario</h5>
  <p>
    Se muestra un calendario donde se ven los tipos de eventos y/o tareas en fechas particulares, unificadas en criterios de gestión de permisos de usuarios
    que, según cada permiso asignado a cada usuario, podrá ver estos eventos o no.
    Además de poder crear este tipo de eventos y modificarlos.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')


<script src='/js/moment.min.js'></script>
<script src='/js/fullcalendar.min.js'></script>
<script src='/js/locale-all.js'></script>
<script src="/js/gcal.min.js" charset="utf-8"></script>

<script src="js/inputSpinner.js" type="text/javascript"></script>
<script src="/js/lista-datos.js" type="text/javascript"></script>

<!-- Custom input Bootstrap -->
<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>

<!-- DateTimePicker JavaScript -->
<script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

<script src="/js/calendar.js" charset="utf-8"></script>
@endsection
