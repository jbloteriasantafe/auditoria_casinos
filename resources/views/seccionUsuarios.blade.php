@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoUsuarios">@svg('usuario','iconoUsuarios')</span>
@endsection
@section('contenidoVista')

        <!-- <div id="page-wrapper">  -->

            <!-- <div class="container-fluid"> -->

                <div class="row">

                  <div class="col-lg-12 col-xl-9"> <!-- columna de FILTROS y TABLA -->

                    <div class="row"> <!-- fila de FILTROS -->
                        <div class="col-md-12">
                          <div id="contenedorFiltros" class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltros" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">
                                  <div class="col-md-3">
                                    <h5>Usuario</h5>
                                    <input id="buscadorUsuario" class="form-control" placeholder="Usuario">
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Nombre</h5>
                                    <input id="buscadorNombre" class="form-control" placeholder="Nombre">
                                  </div>
                                  <div class="col-md-3">
                                    <h5>Email</h5>
                                    <input id="buscadorEmail" class="form-control" placeholder="Email">
                                  </div>
                                  <div class="col-md-3">
                                    <h5 style="color:#F5F5F5;">boton buscar</h5>
                                    <button id="buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div>
                                <br>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div> <!-- Fin de la fila de FILTROS -->

                    <div class="row">
                      <div class="col-md-12">
                        <div class="panel panel-default">
                          <div class="panel-heading">
                              <h4 id="tituloBusqueda">Usuarios cargados en el Sistema</h4>
                          </div>
                          <div class="panel-body">
                            <table id="tablaUsuarios" class="table table-fixed tablesorter">
                              <thead>
                                <tr>
                                  <th class="col-xs-3">USUARIO <i class="fa fa-sort"></i></th>
                                  <th class="col-xs-3">NOMBRE <i class="fa fa-sort"></i></th>
                                  <th class="col-xs-3">EMAIL <i class="fa fa-sort"></i></th>
                                  <th class="col-xs-3">ACCIONES</th>
                                </tr>
                              </thead>
                              <tbody id="cuerpoTabla" style="height: 450px;">
                                @foreach($usuarios as $usuario)
                                <tr id="usuario{{$usuario->id_usuario}}">
                                  <td class="col-xs-3">{{$usuario->user_name}}</td>
                                  <td class="col-xs-3">{{$usuario->nombre}}</td>
                                  <td class="col-xs-3">{{$usuario->email}}</td>
                                  <td class="col-xs-3">
                                                                <!-- ENLACE DE BOTON MODAL-->
                                  <button type="button" class="btn btn-info info" value="{{$usuario->id_usuario}}">
                                    <i class="fa fa-fw fa-search-plus"></i>
                                  </button>
                                  <button type="button" class="btn btn-warning modificar" value="{{$usuario->id_usuario}}">
                                    <i class="fa fa-fw fa-pencil-alt"></i>
                                  </button>
                                  <button type="button" class="btn btn-danger openEliminar" value="{{$usuario->id_usuario}}">
                                    <i class="fa fa-fw fa-trash-alt"></i>
                                  </button>

                                  </td>

                                </tr>
                                <!-- <tr id="var_{{$usuario->id_usuario}}" class="collapse" style="background-color:white;">
                                  <td colspan="4" class="col-xs-12"><h3>ASD</h3></td>

                                </tr> -->
                                @endforeach
                              </tbody>

                            </table>
                          </div>
                        </div>
                      </div>
                    </div>  <!--/fila TABLA -->

                  </div> <!-- Fin de la columna FILTROS y TABLA -->


                  <div class="col-lg-3">
                   <a href="" id="btn-nuevo" style="text-decoration: none;">
                    <div class="panel panel-default panelBotonNuevo">
                        <center><img class="imgNuevo" src="/img/logos/gestion_usuarios_white.png"><center>
                        <div class="backgroundNuevo"></div>
                        <div class="row">
                            <div class="col-xs-12">
                              <center>
                                  <h5 class="txtLogo">+</h5>
                                  <h4 class="txtNuevo">NUEVO USUARIO</h4>
                              </center>
                            </div>
                        </div>
                    </div>
                   </a>
                  </div>

                </div>


      <!-- MODAL NUEVO USUARIO -->
        <div class="modal fade" id="modalCrear" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                 <div class="modal-content">
                    <div class="modal-header modalNuevo">
                      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                      <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                      <h3 class="modal-title">| NUEVO USUARIO</h3>
                    </div>

              <div id="colapsado" class="collapse in">
                    <div class="modal-body">
                      <form id="frmUsuario" name="frmUsuario" class="form-horizontal" novalidate="">
                          <div class="row">
                              <div class="col-md-6">
                                  <h5>Nombre de Usuario</h5>
                                  <input type="text" class="form-control" id="usuario" placeholder="Nombre de Usuario">
                                  <br>
                                  <div class="alertaSpan" hidden role="alert" id="alertaUsuario"><span></span></div>
                              </div>
                              <div class="col-md-6">
                                  <h5>Nombre Completo</h5>
                                  <input type="text" class="form-control" id="nombre" name="cod_identificacion" placeholder="Nombre Completo" value="">
                                  <br>
                                  <div class="alertaSpan" hidden role="alert" id="alertaNombre"><span></span></div>
                              </div>
                            </div>
                            <div class="row">
                              <div class="col-md-6">
                                  <h5>Email</h5>
                                  <input type="text" class="form-control" id="email" name="nro_niv_progresivos" placeholder="Email" value="">
                                  <br>
                                  <div class="alertaSpan" hidden role="alert" id="alertaEmail"><span></span></div>
                              </div>
                              <div class="col-md-6">
                                  <h5>Contraseña</h5>
                                  <input type="text" class="form-control" id="password" name="nro_niv_progresivos" placeholder="Contraseña" value="">
                                  <br>
                                  <div class="alertaSpan" hidden role="alert" id="alertaPassword"><span></span></div>
                              </div>
                              </div>
                          <br>
                          <div style="border-bottom: 1px solid #eee;"></div><br>

                          <!-- nuevas secciones -->
                          <div class="row">
                              <div class="col-md-12" id="contenedorCasino">
                                <h5>Casino</h5>
                                @foreach($casinos as $casino)
                                <input style="margin-left:40px;" id="casino{{$casino->id_casino}}" type="checkbox" name="" value="{{$casino->id_casino}}"> {{$casino->nombre}}
                                @endforeach
                              </div>
                          </div>

                          <br>

                          <div class="row">
                              <div class="col-md-12" id="contenedorRoles">
                                <h5>Roles</h5>
                                @foreach($roles as $rol)
                                <input style="margin-left:40px;" id="rol{{$rol->id_rol}}" type="checkbox" name="" value="{{$rol->id_rol}}"> {{$rol->descripcion}}
                                @endforeach
                              </div>
                          </div>

                          <br>

                          <div class="row">
                              <div class="col-md-12">
                                  <h5>PERMISOS</h5>
                                  <div id="contenedorPermisos" style="text-align:center;">

                                  </div>
                              </div>
                          </div>
                          <!-- /nuevas secciones -->

                      </form>
                    </div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
                      <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                      <button type="button" class="btn btn-default" data-dismiss="modal" style="display: none;">SALIR</button>
                      <!-- <input type="hidden" id="id_juego" name="id_juego" value="0"> -->
                    </div>
                  </div>
                </div>
              </div>
        </div>

        <!-- FIN MODAL GUIA -->

        <div class="modal fade" id="modalVer" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                 <div class="modal-content">
                    <div class="modal-header modalVerMas">
                      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                      <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                      <h3 class="modal-title">| VER MÁS</h3>
                    </div>

              <div id="colapsado" class="collapse in">
                    <div class="modal-body">
                      <form id="frmUsuario" name="frmUsuario" class="form-horizontal" novalidate="">
                          <div class="row">
                              <div class="col-md-6">
                                  <h5>Nombre de Usuario</h5>
                                  <input type="text" class="form-control" id="infoUsuario" placeholder="Nombre de Usuario" readonly>
                                  <br>
                              </div>
                              <div class="col-md-6">
                                  <h5>Nombre Completo</h5>
                                  <input type="text" class="form-control" id="infoNombre" name="cod_identificacion" placeholder="Nombre Completo" value="" readonly>
                                  <br>
                              </div>
                            </div>
                            <div class="row">
                              <div class="col-md-6">
                                  <h5>Email</h5>
                                  <input type="text" class="form-control" id="infoEmail" name="nro_niv_progresivos" placeholder="Email" value="" readonly>
                                  <br>
                              </div>
                              <div class="col-md-6">
                                  <h5>Contraseña</h5>
                                  <input type="text" class="form-control" id="infoPass" name="nro_niv_progresivos" placeholder="Contraseña" value="" readonly>
                                  <br>
                              </div>
                              </div>
                          <br>
                          <div style="border-bottom: 1px solid #eee;"></div><br>

                          <!-- nuevas secciones -->
                          <div class="row">
                              <div class="col-md-12" id="contenedorCasino">
                                <h5>Casino</h5>
                                @foreach($casinos as $casino)
                                <input style="margin-left:40px;" id="casino{{$casino->id_casino}}" type="checkbox" name="" value="{{$casino->id_casino}}" disabled> {{$casino->nombre}}
                                @endforeach
                              </div>
                          </div>

                          <br>

                          <div class="row">
                              <div class="col-md-12" id="contenedorRoles">
                                <h5>Roles</h5>
                                @foreach($roles as $rol)
                                <input style="margin-left:40px;" id="rol{{$rol->id_rol}}" type="checkbox" name="" value="{{$rol->id_rol}}" disabled> {{$rol->descripcion}}
                                @endforeach
                              </div>
                          </div>

                          <br>

                          <div class="row">
                              <div class="col-md-12">
                                  <h5>PERMISOS</h5>
                                  <div id="contenedorPermisos" style="text-align:center;">

                                  </div>
                              </div>
                          </div>
                          <!-- /nuevas secciones -->

                      </form>
                    </div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
                    </div>
                  </div>
                </div>
              </div>
        </div>

        <!-- Final Modal Ver Más -->

    <!-- Modal Modificar-->

    <div class="modal fade" id="modalModificar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header modalModificar">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-title">| MODIFICAR USUARIO</h3>
                </div>

          <div id="colapsado" class="collapse in">
                <div class="modal-body">
                      <div class="row">
                          <div class="col-md-6">
                              <h5>Nombre de Usuario</h5>
                              <input type="text" class="form-control" id="modUsuario" placeholder="Nombre de Usuario">
                              <br>
                              <span id="modAlertaUsuario" class="alertaSpan"></span>
                          </div>
                          <div class="col-md-6">
                              <h5>Nombre Completo</h5>
                              <input type="text" class="form-control" id="modNombre" name="cod_identificacion" placeholder="Nombre Completo" value="">
                              <br>
                              <span id="modAlertaNombre" class="alertaSpan"></span>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                              <h5>Email</h5>
                              <input type="text" class="form-control" id="modEmail" name="nro_niv_progresivos" placeholder="Email" value="">
                              <br>
                              <span id="modAlertaEmail" class="alertaSpan"></span>
                          </div>
                          <div class="col-md-6">
                              <h5>Contraseña</h5>
                              <button id="btn-resetearPass" class="btn btn-infoBuscar" type="button" name="button">RESETEAR CONTRASEÑA  [DNI]</button>
                              <!-- <input type="text" class="form-control" id="modPassword" name="nro_niv_progresivos" placeholder="Contraseña" value="">
                              <br>
                              <span id="modAlertaPassword" class="alertaSpan"></span> -->
                          </div>
                          </div>
                      <br>
                      <div style="border-bottom: 1px solid #eee;"></div><br>

                      <div class="row">
                          <div class="col-md-12" id="contenedorCasino">
                            <h5>Casino</h5>
                            @foreach($casinos as $casino)
                            <input style="margin-left:40px;" id="casino{{$casino->id_casino}}" type="checkbox" name="" value="{{$casino->id_casino}}"> {{$casino->nombre}}
                            @endforeach
                          </div>
                      </div>

                      <br>

                      <div class="row">
                          <div class="col-md-12" id="contenedorRoles">
                            <h5>Roles</h5>
                            @foreach($roles as $rol)
                            <input style="margin-left:40px;" id="rol{{$rol->id_rol}}" type="checkbox" name="" value="{{$rol->id_rol}}"> {{$rol->descripcion}}
                            @endforeach
                          </div>
                      </div>

                      <br>

                      <div class="row">
                          <div class="col-md-12">
                              <h5>PERMISOS</h5>
                              <div id="contenedorPermisos" style="text-align:center;">

                              </div>
                          </div>
                      </div>



                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-warningModificar" id="btn-modificiar" value="nuevo">ACEPTAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
              </div>
            </div>
          </div>
    </div>

    <!-- Final Modal Modificar -->

    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                  <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
                </div>

               <div  id="colapsado" class="collapse in">
                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                  <form id="frmEliminar" name="frmJuego" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div id="mensajeEliminar" class="col-xs-12">
                            <strong>¿Está seguro que desea eliminar este usuario y sus relaciones ?</strong>
                          </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-dangerEliminar" id="btn-eliminar" value="0">ELIMINAR</button>
                  <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
                </div>
              </div>
            </div>
          </div>
    </div>

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA GESTIONAR USUARIOS</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjetas de gestionar usuarios</h5>
  <p>
    Gestiona el ingreso o baja de usuarios existentes en el sistema. Permite asociarlos a diferentes casinos y roles que derivan en sus tareas asignadas.
  </p>
</div>

@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/ajaxUsuarios.js"></script>
@endsection
