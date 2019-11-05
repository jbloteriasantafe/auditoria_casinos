@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoExpedientes">@svg('expedientes','iconoExpedientes')</span>
@endsection
@section('contenidoVista')
<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$id_usuario = session('id_usuario');
?>

                <div class="row">
                    <div class="col-lg-12 col-xl-9">
                        <div class="row"> <!-- Fila de FILTROS -->
                          <div class="col-md-12">
                            <div class="panel panel-default">
                              <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                                  <h4>Filtros de Búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                              </div>
                              <div id="collapseFiltros" class="panel-collapse collapse">
                                <div class="panel-body">
                                  <div class="row">
                                    <div class="col-md-3">
                                      <h5>Número de Expediente</h5>
                                      <div class="input-group triple-input">
                                          <input id="nro_exp_org" style="width:30%; border-right:none;" type="text" placeholder="-----" maxlength="5" class="form-control" />
                                          <input id="nro_exp_interno" style="width:50%;" type="text" placeholder="-------" maxlength="7" class="form-control" />
                                          <input id="nro_exp_control" style="width:20%; border-left:none;" type="text" placeholder="-" maxlength="1" class="form-control" />
                                      </div>
                                    </div>
                                    <div class="col-md-3">
                                      <h5>Casino</h5>
                                      <div class="form-group">
                                        <select class="form-control" id="sel1">
                                          <option value="0">Todos los casinos</option>
                                          @foreach($casinos as $casino)
                                          <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                          @endforeach
                                        </select>
                                      </div>
                                    </div>
                                    <div class="col-md-3">
                                      <h5>Identificación de la Nota</h5>

                                      <input id="B_nota" type="text" class="form-control" maxlength="45" placeholder="Identificación">

                                    </div>
                                    <div class="col-md-3">
                                      <h5 style="color:#f5f5f5;">Búsqueda</h5>
                                      <button id="buscarNota" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              </div>
                            </div>
                        </div>

                        <div class="row"> <!-- Fila de TABLA -->
                          <div class="col-md-12"> <!-- columna TABLA CASINOS -->
                            <div class="panel panel-default">
                              <div class="panel-heading">
                                  <h4 id="tituloNotas">Notas</h4>
                              </div>
                              <div class="panel-body">
                                <table id="tablaNotas" class="table ">
                                  <thead>
                                    <tr>
                                      <th class="col-xs-2">IDENTIFICACIÓN  <i class="fa fa-sort"></i> </th>
                                      <th class="col-xs-2">EXPEDIENTE  <i class="fa fa-sort"></i></th>
                                      <th class="col-xs-2">TIPO MOVIMIENTO<i class="fa fa-sort"></i></th>
                                      <th class="col-xs-2">CASINO  <i class="fa fa-sort"></i></th>
                                      <th class="col-xs-2">ACCIONES  <i class="fa fa-sort"></i></th>
                                    </tr>
                                  </thead>
                                  <tbody style="height: 400px;">
                                    @foreach($notas as $nota)
                                    <tr id="{{$nota->id_nota}}">
                                      <td class="col-xs-2">{{$nota->identificacion}}</td>
                                      <td class="col-xs-2">{{$nota->nro_exp_org}}-{{$nota->nro_exp_interno}}-{{$nota->nro_exp_control}}</td>
                                      <td class="col-xs-2">{{$nota->descripcion}}</td>
                                      <td class="col-xs-2">{{$nota->nombre}}</td>
                                      <td class="col-xs-2">
                                        <!--
                                        <button type="button" class="btn btn-warning modificar" value="{{$nota->id_nota}}">
                                          <i class="fas fa-fw fa-pencil-alt"></i>
                                        </button>
                                       -->

                                        <button type="button" class="btn btn-danger eliminar" value="{{$nota->id_nota}}">
                                          <i class="fa fa-fw fa-trash"></i>
                                        </button>
                                      </td>
                                    </tr>
                                    @endforeach
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          </div> <!--/columna TABLA -->
                        </div>
                    </div>

                    <div class="col-lg-12 col-xl-3">
                      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_expedientes'))
                      <div class="row">
                        <div class="col-xl-12 col-md-6">
                          <!-- <a href="expedientes" style="text-decoration:none;">
                              <div class="tarjetaSeccionMenor" align="center">
                                <h2 class="tituloFondoMenor">EXPEDIENTES</h2>
                                <h2 class="tituloSeccionMenor">GESTIÓN EXPEDIENTES</h2>
                                <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/expedientes_white.png" alt="">
                              </div>
                          </a> -->
                          <a href="expedientes" style="text-decoration:none;">
                              <div class="tarjetaSeccionMenor" align="center">
                                <h2 class="tituloFondoMenor">EXPEDIENTES</h2>
                                <h2 class="tituloSeccionMenor">EXPEDIENTES</h2>
                                <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/expedientes_white.png" alt="">
                              </div>
                          </a>
                        </div>


                      @endif
                      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_disposiciones'))

                          <div class="col-xl-12 col-md-6">
                            <!-- <a href="disposiciones" style="text-decoration:none;">
                                <div class="tarjetaSeccionMenor" align="center">
                                  <h2 class="tituloFondoMenor">DISPOSICIONES</h2>
                                  <h2 class="tituloSeccionMenor">DISPOSICIONES</h2>
                                  <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/disposiciones_white.png" alt="">
                                </div>
                            </a> -->
                            <a href="disposiciones" style="text-decoration:none;">
                                <div class="tarjetaSeccionMenor" align="center">
                                  <h2 class="tituloFondoMenor">DISPOSICIONES</h2>
                                  <h2 class="tituloSeccionMenor">DISPOSICIONES</h2>
                                  <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/disposiciones_white.png" alt="">
                                </div>
                            </a>
                          </div>
                        </div>
                        @endif
                    </div>
                </div>


    <!-- Modal modificar-->
    <div id="modalModificar" class="modal fade" role="dialog">
      <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header" style="background: #ff9d2d;">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title" style="color: #fff;">Modificar campos</h4>
          </div>
          <div class="modal-body">
            <div class="row" style="border-bottom: 1px solid #eee;">
              <div class="col-md-4 col-lg-4">
                <h5>Identificación Nota</h5>
                <div class="input-group triple-input">
                  <input id="identif_nota" type="text" class="form-control" maxlength="45" placeholder="Identificación">
                </div>
                <br>
                <span id="alerta-nroExpediente" class="alertaSpan"></span>
              </div>

            </div>
            <div class="row" style="border-bottom: 1px solid #eee;">
              <div class="col-md-4 col-lg-4">
                <h5>Número de expediente</h5>
                <div class="input-group triple-input">
                    <input id="nro_exp_org" style="width:30%; border-right:none;" type="text" placeholder="-----" maxlength="5" class="form-control" />
                    <input id="nro_exp_interno" style="width:50%;" type="text" placeholder="-------" maxlength="7" class="form-control" />
                    <input id="nro_exp_control" style="width:20%; border-left:none;" type="text" placeholder="-" maxlength="1" class="form-control" />
                </div>
                <br>
                <span id="alerta-nroExpediente" class="alertaSpan"></span>
              </div>

              <style media="screen">
                  div#contenedorCasinos.alerta {
                      border:3px solid #EF5350; padding:5px; border-radius:4px;
                  }
              </style>

              <div class="col-md-8 col-lg-8" style="text-align:center;">
                <h5 style="padding-left:0px;">Casinos</h5>
                <div id="contenedorCasinos">
                    @foreach ($casinos as $casino)
                    <input type="checkbox" id="{{$casino->id_casino}}" value="" class="casinosExp" style="margin:3px">
                    <span style="font-family:Roboto-Light; font-size:18px; margin-left:2px; margin-right:40px">{{$casino->nombre}}</span>
                    @endforeach
                </div>

              </div>

            </div>

          </div> <!-- fin body -->
          <div class="modal-footer">
            <button type="button" class="btn btn-warning" data-dismiss="modal">Modificar</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          </div>
        </div>
      </div>
    </div><!-- FIN CUERPO MODAL -->

    <!-- Modal -->
    <div id="modalEliminar" class="modal fade" role="dialog">
      <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header" style="background: #c9302c;">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title" style="color: #fff;">Eliminar</h4>
          </div>
          <div class="modal-body">
            <br>
            <p>ATENCIÓN</p>
            <div id="alerta_de_movimiento" hidden>
              <p>La nota que desea eliminar posee un movimiento en ejecución.</p>
            </div>
            <div id="alerta_de_eliminacion" hidden>
              <p>Se eliminará la nota seleccionada.</p>
            </div>
            <div>
              <h5>IDENTIFICACIÓN</h5>
              <input class="form-control identificacion_nota_eliminar" id="identificacion_nota_eliminar" type="text" name="" value="">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" id="btn-eliminarModal" value="0" class="btn btn-danger" data-dismiss="modal">Eliminar</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          </div>
        </div>
      </div>
    </div><!-- FIN CUERPO MODAL -->





@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
  <h3 class="modal-title" style="color: #fff;">| AYUDA NOTAS</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Sección Notas</h5>
  <p>
    Aquí se detallan las notas que estan asociadas a los expedientes, para poder eliminar o modificar las que el usuario desee.
    Acción modificar -> EN CONSTRUCCIÓN
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->


@section('scripts')

    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    <script src="js/seccionNotasExpediente.js"></script>
@endsection
