@extends('includes.dashboard')
@section('estilos')

@endsection

@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('casinos','iconoCasinos')</span>
@endsection

@section('contenidoVista')

                <div class="row">

                  <div class="col-lg-9"> <!-- columna TABLA CASINOS -->
                    <div class="panel panel-default">
                      <div class="panel-heading">
                          <h4>Casinos cargados en el sistema</h4>
                      </div>
                      <div class="panel-body">
                        <table id="tablaCasinos" class="table">
                          <thead>
                            <tr>
                              <th class="col-xs-5">NOMBRE <i class="fa fa-sort"></i></th>
                              <th class="col-xs-4">CÓDIGO <i class="fa fa-sort"></i></th>
                              <th class="col-xs-3 accionesTH">ACCIÓN</th>
                            </tr>
                          </thead>
                          <tbody style="">

                             @foreach ($casinos as $casino)
                              <tr id="casino{{$casino->id_casino}}">
                                <td class="col-xs-4">{{$casino->nombre}}</td>
                                <td class="col-xs-4">{{$casino->codigo}}</td>
                                <td class="col-xs-4">
                                  <button class="btn btn-warning btn-detalle modificar" value="{{$casino->id_casino}}"><i class="fa fa-fw fa-pencil-alt"></i></button>
                                  <!-- <button class="btn btn-danger btn-borrar eliminar" value="{{$casino->id_casino}}"><i class="fa fa-fw fa-trash"></i></button> -->
                                </td>
                              </tr>
                            @endforeach

                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-3">
                   <a href="" id="btn-nuevo" style="text-decoration: none;">
                    <div class="panel panel-default panelBotonNuevo">
                        <center><img class="imgNuevo" src="/img/logos/casinos_white.png"><center>
                        <div class="backgroundNuevo"></div>
                        <div class="row">
                            <div class="col-xs-12">
                              <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">NUEVO CASINO</h4>
                              </center>
                            </div>
                        </div>
                    </div>
                   </a>
                  </div>
                </div>


    <!-- Modal Casino -->
    <div class="modal fade" id="modalCasino" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo">
                 <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| NUEVO CASINO</h3>
                </div>

                <div  id="colapsado" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmCasino" name="frmCasino" class="form-horizontal" novalidate="">

                          <div class="row">
                            <div class="col-md-6">
                              <h5>NOMBRE</h5>
                              <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del casino" value="">
                              <br><span id="alertaNombre" class="alertaSpan"></span>
                            </div>
                            <div class="col-md-6">
                              <h5>CÓDIGO</h5>
                              <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Código del casino" value="">
                              <br> <span id="alertaCodigo" class="alertaSpan"></span>
                            </div>
                          </div>

                  </form>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar btnConEspera" id="btn-guardar" value="nuevo">ACEPTAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <input type="hidden" id="id_casino" name="id_casino" value="0">
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
                </div>

                <div class="modal-body franjaRojaModal">
                  <form id="frmEliminar" name="frmCasino" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Seguro desea eliminar el CASINO? Podría ocasionar errores serios en el sistema.</strong>
                          </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminarModal" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>

    <!-- Modal preModificar -->
    <div class="modal fade" id="modalPreModificar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-titleModificar">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="background-color: #FFB74D; color: white;">
                  <form id="frmPreModificar" name="" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Seguro desea modificar el CASINO? Podría ocasionar errores serios en el sistema.</strong>
                          </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-warning" id="btn-preModificar" value="0">MODIFICAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
            </div>
          </div>
    </div>


    <meta name="_token" content="{!! csrf_token() !!}" />
    @endsection

    <!-- Comienza modal de ayuda -->
    @section('tituloDeAyuda')
    <h3 class="modal-title" style="color: #fff;">| AYUDA ADMINISTRAR CASINOS</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjetas de administrar casinos</h5>
      <p>Detalle de los casinos actuales dentro del sistema. El ingreso de un nuevo casino, generará en el sistema cambios que repercutirán en todas las implementaciones de fiscalización y toma de relevamientos.
      </p>
    </div>

    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionCasinos.js?1" charset="utf-8"></script>
    @endsection
