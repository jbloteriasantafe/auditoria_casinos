@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection
@section('estilos')
  <link rel="stylesheet" href="/css/lista-datos.css">
@endsection
<?php
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
?>
@section('contenidoVista')

          <div class="row">
                <div class="col-lg-12 col-xl-9">

                    <div class="row">
                  <div class="col-md-12"> <!-- columna TABLA SECTORES -->
                    <div class="panel panel-default">
                      <div class="panel-heading">
                          <h4 id="tituloTabla" class"nombreTabla">Sectores cargados en el sistema</h4>
                      </div>
                      <div class="panel-body">
                        <table id="tablaSectores" class="table table-fixed tablesorter">
                          <thead>
                            <tr>
                              <th class="col-xs-2">CASINO <i class="fa fa-sort"></i></th>
                              <th class="col-xs-4">NOMBRE DEL SECTOR <i class="fa fa-sort"></i></th>
                              <th class="col-xs-3">CANT MÁQUINAS <i class="fa fa-sort"></i></th>
                              <th class="col-xs-3">ACCIÓN</th>
                            </tr>
                          </thead>
                          <tbody style="height:560px;">

                             @foreach ($sectores as $sector)
                             <tr id="sector{{$sector->id_sector}}">
                               <td class="col-xs-2">{{$sector->casino->nombre}}</td>
                               <td class="col-xs-4">{{$sector->descripcion}}</td>
                               <td class="col-xs-3">{{$sector->cantidad_maquinas}}</td>
                               <td class="col-xs-3">
                                 <button class="btn btn-info detalle" value="{{$sector->id_sector}}"><i class="fa fa-fw fa-search-plus"></i></button>
                                 <button class="btn btn-warning btn-detalle modificar" value="{{$sector->id_sector}}"><i class="fa fa-fw fa-pencil-alt"></i></button>
                                 <button class="btn btn-danger btn-borrar eliminar" value="{{$sector->id_sector}}"><i class="fa fa-fw fa-trash-alt"></i></button>
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
            <!-- /. col-lg-12 col-xl-9 -->
                <div class="col-lg-12 col-xl-3">
                  <div class="row">
                    <div class="col-lg-12">
                      <a href="" id="btn-nuevo" style="text-decoration: none;">
                        <div class="panel panel-default panelBotonNuevo">
                          <center><img class="imgNuevo" src="/img/logos/sectores_white.png"><center>
                            <div class="backgroundNuevo"></div>
                            <div class="row">
                              <div class="col-xs-12">
                                <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">NUEVO SECTOR</h4>
                                </center>
                              </div>
                            </div>
                          </div>
                        </a>
                      </div>
                  </div>
                </div>

          </div>
          <!-- row -->


    <!-- Modal Sector -->
    <div class="modal fade" id="modalSector" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo">
                 <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                 <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                 <h3 class="modal-title">| NUEVO SECTOR</h3>
                </div>

                <div  id="colapsado" class="collapse in">

                <div class="modal-body modalCuerpo">

                  <form id="frmSector" name="frmSector" class="form-horizontal" novalidate="">

                          <div class="row">
                            <div class="col-md-4">
                              <h5>NOMBRE</h5>
                              <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del sector" value="">
                              <br><span id="alertaNombre" class="alertaSpan"></span>
                            </div>
                            <div class="col-md-4">
                              <h5>CASINO</h5>
                              <select id="casino" class="form-control" name="">
                                  <?php $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario')) ?>
                                   @foreach ($usuario['usuario']->casinos as $casino)
                                   <option id="{{$casino->id_casino}}" value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                   @endforeach
                              </select>
                              <br> <span id="alertaCasino" class="alertaSpan"></span>
                            </div>
                            <div class="col-md-4">
                              <h5>CANT MÁQUINAS</h5>
                              <input type="text" class="form-control" id="cantidad_maquinas" name="cantidad_maquinas" value="0">
                              <br><span id="alertaCantidad" class="alertaSpan"></span>
                            </div>
                          </div>
                          <div class="row">
                              <div class="col-md-12">
                                  <h5>Buscador Islas <i class="fa fa-fw fa-search"></i></h5>
                                  <div class="row">
                                    <div class="input-group lista-datos-group">
                                                    <input class="form-control buscadorIsla" type="text" value="" autocomplete="off" >
                                                    <span class="input-group-btn">
                                                      <button class="btn btn-default btn-lista-datos agregarIsla" type="button"><i class="fa fa-plus"></i></button>
                                                    </span>
                                    </div>
                                  </div>

                                  <br>

                                  <!-- Lista de islas agregadas -->
                                  <h5>Islas agregadas en el sector</h5>
                                  <div class="row">
                                      <div class="col-xs-3 col-xs-offset-1">
                                          <h5>NÚMERO</h5>
                                      </div>
                                      <div class="col-xs-2">
                                          <h5>CÓDIGO</h5>
                                      </div>
                                      <div class="col-xs-2">
                                          <h5>CANT MÁQUINAS</h5>
                                      </div>
                                      <div class="col-xs-3">
                                      </div>
                                      <ul class="col-xs-12" id="listaIslas">
                                      </ul>
                                  </div>
                                </div>
                            </div>

                  </form>

                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                  <input type="hidden" id="id_sector" name="id_sector" value="0">
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
                  <form id="frmEliminar" name="frmSector" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                              <h5>¿Seguro desea eliminar el <strong>SECTOR</strong>? Podría ocasionar errores serios en el sistema. Los relevamientos asociados a este sector serán tambien eliminados.
                                    Las islas permanecerán en el sistema, sin sector.</h5>
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
                  <h3 class="modal-title">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="background-color: #FFB74D; color: white;">
                  <form id="frmPreModificar" name="" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Seguro desea modificar el SECTOR? Podría ocasionar errores serios en el sistema.</strong>
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
    <h3 class="modal-title" style="color: #fff;">| SECTORES</h3>
    @endsection
    @section('contenidoAyuda')
    <div class="col-md-12">
      <h5>Tarjeta de Sectores</h5>
      <p>
        Creación y modificación de sectores, respecto al casino asignado a esta acción. Luego, podrá unificar criterios
        con respecto a islas, que ya vendrán asociadas a las máquinas ingresadas.
      </p>
    </div>
    @endsection
    <!-- Termina modal de ayuda -->

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionSectores.js" charset="utf-8"></script>
    <script src="/js/lista-datos.js" charset="utf-8"></script>

    @endsection
