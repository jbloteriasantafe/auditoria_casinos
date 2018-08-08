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
                                      <h5>Número de Resolución</h5>

                                      <div class="input-group triple-input">
                                          <input id="nro_resolucion" style="width:70%; border-right:none;" type="text" placeholder="-----" maxlength="5" class="form-control" />
                                          <input id="nro_resolucion_anio" style="width:30%;" type="text" placeholder="--" maxlength="2" class="form-control" />
                                      </div>

                                    </div>
                                    <div class="col-md-3">
                                      <h5 style="color:#f5f5f5;">Búsqueda</h5>
                                      <button id="buscarResolucion" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
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
                                  <h4 id="tituloResoluciones">Resoluciones</h4>
                              </div>
                              <div class="panel-body">
                                <table id="tablaResoluciones" class="table table-fixed tablesorter">
                                  <thead>
                                    <tr>
                                      <th class="col-xs-4">NÚMERO DE EXPEDIENTE  <i class="fa fa-sort"></i> </th>
                                      <th class="col-xs-4">CASINO  <i class="fa fa-sort"></i></th>
                                      <th class="col-xs-4">NÚMERO DE RESOLUCIÓN  <i class="fa fa-sort"></i></th>
                                    </tr>
                                  </thead>
                                  <tbody style="height: 400px;">
                                    @foreach($resoluciones as $resolucion)
                                    <tr id="{{$resolucion->id_resolucion}}">
                                      <td class="col-xs-4">{{$resolucion->nro_exp_org}}-{{$resolucion->nro_exp_interno}}-{{$resolucion->nro_exp_control}}</td>
                                      <td class="col-xs-4">{{$resolucion->nombre}}</td>
                                      <td class="col-xs-4">{{$resolucion->nro_resolucion}}-{{$resolucion->nro_resolucion_anio}}</td>
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
            <br>
            <p>Número de Expediente</p>
            XXX-XXXXX-X<br><br>
            <p>Casino</p>
            Santa Fé<br><br>
            <p>Número de Resolución</p>
            <input type="text" name="" value=""><br><br><br>
          </div>
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
            <p>Mensaje de Eliminar Disposición / Resolución </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Eliminar</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
          </div>
        </div>
      </div>
    </div><!-- FIN CUERPO MODAL -->





@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA RESOLUCIONES</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Resoluciones</h5>
  <p>
    Instrumentos legales de rango superior que avalan pedidos realizados por los concesionarios. Solo están detalladas aquellas resoluciones que pudieron ser cargadas en expedientes.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->


@section('scripts')

    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    <script src="js/seccionResoluciones.js"></script>
@endsection
