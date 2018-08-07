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
                                      <h5>Número de expediente</h5>
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
                                          <option value="0">-Casino-</option>
                                          @foreach($casinos as $casino)
                                          <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                          @endforeach
                                        </select>
                                      </div>
                                    </div>
                                    <div class="col-md-3">
                                      <h5>Número de Disposición</h5>
                                      <div class="row">
                                        <div class="col-xs-6" style="padding-right: 0px;">
                                          <input id="nro_disposicion" type="text" class="form-control" maxlength="5" placeholder="-----">
                                        </div>
                                        <div class="col-xs-6">
                                          <input id="nro_disposicion_anio" type="text" class="form-control" maxlength="2" placeholder="--">
                                        </div>
                                      </div>
                                    </div>
                                    <div class="col-md-3">
                                      <h5 style="color:#f5f5f5;">Búsqueda</h5>
                                      <button id="buscarDispocisiones" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              </div>
                            </div>
                        </div>

                        <div class="row"> <!-- Fila de TABLA -->
                            <div class="col-md-12"> <!-- columna TABLA disposiciones -->
                              <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 id="tituloDisposiciones">Disposiciones</h4>
                                </div>
                                <div class="panel-body">
                                  <table id="tablaDisposiciones" class="table table-fixed tablesorter">
                                    <thead>
                                      <tr>
                                        <th class="col-xs-4">NÚMERO DE EXPEDIENTE  <i class="fa fa-sort"></i></th>
                                        <th class="col-xs-4">CASINO  <i class="fa fa-sort"></i></th>
                                        <th class="col-xs-4">NÚMERO DE DISPOSICIÓN  <i class="fa fa-sort"></i></th>
                                      </tr>
                                    </thead>
                                    <tbody style="height: 400px;">
                                      @foreach($disposiciones as $disposicion)
                                      <tr id="{{$disposicion->id_disposicion}}">
                                        <td class="col-xs-4">{{$disposicion->nro_exp_org . '-' . $disposicion->nro_exp_interno . '-' . $disposicion->nro_exp_control }}</td>
                                        <td class="col-xs-4">{{$disposicion->nombre}}</td>
                                        <td class="col-xs-4">{{$disposicion->nro_disposicion . '-' . $disposicion->nro_disposicion_anio}}</td>
                                        <!-- <td>
                                            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modalModificar"><i class="fa fa-edit"></i> Modificar</button>
                                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalEliminar"><i class="fa fa-trash"></i> Eliminar</button>
                                        </td> -->
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
                      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_resoluciones'))

                        <div class="col-xl-12 col-md-6">
                          <!-- <a href="resoluciones" style="text-decoration:none;">
                              <div class="tarjetaSeccionMenor" align="center">
                                <h2 class="tituloFondoMenor">RESOLUCIONES</h2>
                                <h2 class="tituloSeccionMenor">RESOLUCIONES</h2>
                                <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/resoluciones_white.png" alt="">
                              </div>
                          </a> -->
                          <a href="resoluciones" style="text-decoration:none;">
                              <div class="tarjetaSeccionMenor" align="center">
                                <h2 class="tituloFondoMenor">RESOLUCIONES</h2>
                                <h2 class="tituloSeccionMenor">RESOLUCIONES</h2>
                                <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/resoluciones_white.png" alt="">
                              </div>
                          </a>
                        </div>
                      </div>
                      @endif
                    </div>
                </div>







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
            <p>Número de Disposición</p>
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
@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| DISPOSICIONES</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Disposiciones</h5>
  <p>
    Permite garantizar los distintos movimientos y/o actividades de los concesionarios a fiscalizar. Solo están detalladas aquellas disposiciones que pudieron ser cargadas en expedientes.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->


@section('scripts')

    <!-- JavaScript personalizado -->
    <script src="js/seccionDisposiciones.js"></script>
@endsection
