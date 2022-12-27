<?php
use App\Rol;
use App\Permiso;
use App\Http\Controllers\permisoController;

?>
@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoUsuarios">@svg('usuario','iconoUsuarios')</span>
@endsection
@section('contenidoVista')

    <!-- <div id="page-wrapper">
      <div class="container-fluid"> -->

           <div class="row">
                <div class="col-lg-6"> <!-- columna ROLES -->
                    <div class="row"> <!-- fila de FILTROS -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltrosRoles" style="cursor: pointer">
                              <h4>Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltrosRoles" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">
                                  <div class="col-md-6">
                                    <h5>Rol</h5>
                                    <input id="buscadorRol" class="form-control" placeholder="Rol">
                                  </div>
                                  <div class="col-md-3" style="margin-top:30px !important">

                                    <button id="buscarRol" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div>
                                <br>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>

                    <div class="row"> <!-- fila de TABLA ROLES -->
                        <div class="col-md-12"> <!-- columna TABLA ROLES -->
                          <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 id="tituloBusquedaRoles">Roles</h4>
                            </div>
                            <div class="panel-body">
                              <table id="tablaRoles" class="table table-fixed tablesorter">
                                <thead>
                                  <tr>
                                    <th class="col-xs-7">ROL  <i class="fa fa-sort"></i></th>
                                    <th class="col-xs-5">ACCIONES</th>
                                  </tr>
                                </thead>
                                <tbody id="cuerpoTablaRoles" style="height: 220px;">
                                  @foreach($roles as $rol)
                                  <tr id="{{$rol->id_rol}}">
                                    <td class="col-xs-7"> {{$rol->descripcion}}<a id="popoverData" class="pop btn" href="#" data-content="@foreach($rol->permisos as $permiso) · {{$permiso->descripcion}} <br> @endforeach" data-placement="bottom" data-original-title="Permisos asociados" > <span class="badge"><i class="fa fa-search-plus"></i></span></a></td>
                                    <td class="col-xs-5"><!-- ENLACE DE BOTON MODAL background: #ff9d2d;-->
                                        <button class="btn btn-info detalleRol" value="{{$rol->id_rol}}"><i class="fa fa-fw fa-search-plus"></i></button>
                                        <button type="button" class="btn btn-warning modificarRol" value="{{$rol->id_rol}}"><i class="fa fa-fw fa-pencil-alt"></i></button>
                                        <button type="button" class="btn btn-danger openEliminar" data-tipo="rol" value="{{$rol->id_rol}}"><i class="fa fa-fw fa-trash-alt"></i></button>
                                    </td>
                                  </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div> <!--/columna TABLA -->
                    </div> <!-- fin fila TABLA -->

                    <div class="row"> <!-- fila de AGREGAR ROL -->
                      <div class="col-lg-12">
                       <a href="" id="btn-add" style="text-decoration: none;">
                        <div class="panel panel-default panelBotonNuevo">
                            <center><img class="imgNuevo" src="/img/logos/roles_white.png"><center>
                            <div class="backgroundNuevo"></div>
                            <div class="row">
                                <div class="col-xs-12">
                                  <center>
                                      <h5 class="txtLogo">+</h5>
                                      <h4 class="txtNuevo">NUEVO ROL</h4>
                                  </center>
                                </div>
                            </div>
                        </div>
                       </a>
                      </div>
                    </div>
                </div> <!-- fin de columna ROLES -->

                <div class="col-lg-6"> <!-- columna PERMISOS -->
                    <div class="row"> <!-- fila de FILTROS -->
                        <div class="col-md-12">
                          <div class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" href="#collapseFiltrosPermisos" style="cursor: pointer">
                              <h4 id="tituloBusquedaPermisos">Filtros de Búsqueda  <i class="fa fa-fw fa-angle-down"></i></h4>
                            </div>
                            <div id="collapseFiltrosPermisos" class="panel-collapse collapse">
                              <div class="panel-body">
                                <div class="row">
                                  <div class="col-md-6">
                                    <h5>Permiso</h5>
                                    <input id="buscadorPermiso" class="form-control" placeholder="Permiso">
                                  </div>
                                  <div class="col-md-3" style="margin-top:30px !important">

                                    <button id="buscarPermiso" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </div>
                                </div>
                                <br>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>

                    <div class="row">
                      <div class="col-md-12"> <!-- columna TABLA CASINOS -->
                        <div class="panel panel-default">
                          <div class="panel-heading">
                              <h4>Permisos</h4>
                          </div>
                          <div class="panel-body">
                            <table id="tablaPermisos" class="table table-fixed tablesorter">
                              <thead>
                                <tr>
                                  <th class="col-xs-7">PERMISO <i class="fa fa-sort"></i></th>
                                  <th class="col-xs-5">ACCIONES</th>
                                </tr>
                              </thead>
                              <tbody id="cuerpoTablaPermisos" style="max-height: 220px;">
                                @foreach($permisos as $permiso)
                                <tr id="{{$permiso->id_permiso}}">
                                  <td class="col-xs-7">{{$permiso->descripcion}}</td>
                                  <td class="col-xs-5">
                                    <button class="btn btn-info detallePermiso" value="{{$permiso->id_permiso}}"><i class="fa fa-fw fa-search-plus"></i></button>
                                    <button class="btn btn-warning modificarPermiso" value="{{$permiso->id_permiso}}"><i class="fa fa-fw fa-pencil-alt"></i></button>
                                    <button class="btn btn-danger openEliminar" data-tipo="permiso" value="{{$permiso->id_permiso}}"><i class="fa fa-fw fa-trash-alt"></i></button>
                                  </td>
                                </tr>
                                @endforeach
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div> <!--/columna TABLA -->

                    </div>

                    <div class="row">
                      <div class="col-lg-12">
                       <a href="" id="btn-add2" style="text-decoration: none;">
                        <div class="panel panel-default panelBotonNuevo">
                            <center><img class="imgNuevo" src="/img/logos/permisos_white.png"><center>
                            <div class="backgroundNuevo"></div>
                            <div class="row">
                                <div class="col-xs-12">
                                  <center>
                                      <h5 class="txtLogo">+</h5>
                                      <h4 class="txtNuevo">NUEVO PERMISO</h4>
                                  </center>
                                </div>
                            </div>
                        </div>
                       </a>
                      </div>
                    </div>
                </div> <!-- fin de columna PERMISOS -->
           </div>

       <!-- </div>
    </div> -->

 <div class="modal fade" id="myModalRol" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
       <div class="modal-dialog">
          <div class="modal-content">
             <div class="modal-header modalNuevo">
               <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
               <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
               <h3 class="modal-title">| NUEVO ROL</h3>
             </div>
     <form id="frmRol" name="frmRol">
       <div id="colapsado" class="collapse in">
             <div class="modal-body">
               <p id="errores"></p>
               <!-- <form id="frmRol" name="frmRol" class="form-horizontal" novalidate=""> -->
                   <div class="row">
                       <div class="col-md-6">
                           <h5>Nombre del Rol</h5>
                           <input type="text" class="form-control" id="comment" placeholder="Nombre del Rol">
                           <div class="alertaSpan" hidden role="alert" id="alertaDescripcion"><br><span></span></div>
                       </div>
                   </div><br>
                   <div class="row">
                       <div id="conteinerPermisos" class="col-md-6">
                           <h5>Permisos</h5>
                           @foreach($permisos as $permiso)
                            <div class="checkbox"> <label><input id="rol{{$permiso->id_permiso}}" type="checkbox" value="{{$permiso->id_permiso}}">{{$permiso->descripcion}}</label></div>
                           @endforeach
                       </div>
                   </div>
               <!-- </form> -->
             </div>

             <div class="modal-footer">
               <button type="button" class="btn btn-successAceptar" id="btn-save-rol" value="add">ACEPTAR</button>
               <button id='boton-cancelar' type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
               <!-- <button id='boton-salir' type="button" class="btn btn-default" data-dismiss="modal" style="display: none;">SALIR</button> -->
               <input type="hidden" id="id_rol" name="id_rol" value="0">
             </div>
           </div>
         </form>
        </div>
       </div>
     </div>

 <div class="modal fade" id="myModalPermisos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">

          <div class="modal-header modalNuevo">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            <button id="btn-minimizarPermisos" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsadoP" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
            <h3 class="modal-title">| NUEVO PERMISO</h3>
         </div>
        <form id="frmPermisos" name="frmPermisos">
         <div id="colapsadoP" class="collapse in">
          <div class="modal-body">
           <p id="errores"></p>
                 <div class="row">
                   <div class="col-md-6">
                      <h5>Nombre del Permiso</h5>
                      <input type="text" class="form-control" id="commentPermiso" placeholder="Nombre del Permiso">

                      <div class="alertaSpan" hidden role="alert" id="alertaDescripcion"><br><span></span></div>
                    </div>
                 </div>
                     <div class="row">
                         <div id="conteinerRoles" class="col-md-6">
                             <br><h5>Roles Asociados</h5>
                             @foreach($roles as $rol)
                             <div class="checkbox"> <label><input id="permiso{{$rol->id_rol}}" type="checkbox" value="{{$rol->id_rol}}">{{$rol->descripcion}}</label></div>
                             @endforeach
                         </div>
                     </div>
                   </div>
                 </div>
               </form>
                 <br>
         <div class="modal-footer">
           <button type="button" class="btn btn-successAceptar" id="btn-save-permiso" value="add">Crear Permiso</button>
           <input type="hidden" id="id_permiso" name="id_permiso" value="0">
           <button type="button" id="cancelar_permiso" class="btn btn-default" data-dismiss="modal">Cancelar</button>
         </div>
     </div>
   </div>
 </div>

 <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">

          <div class="modal-header" style="background: #d9534f; color: #fff;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
         </div>

         <div class="modal-body" style="color:#fff; background-color:#EF5350;">
           <p id="errores"></p>
             <!-- <form id="frmEliminar" name="frmEliminar" class="form-horizontal" novalidate=""> -->

                 <!-- Si no anda falta el <fieldset> -->

               <h4 id="mensaje1"></h4>
                 <div class="row">
                   <div class="col-md-8 col-md-offset-2">

                     <div class="row">
                         <div class="col-md-6">
                             <h4 id="mensaje2"></h4>
                             <ul id="lista1">

                             </ul>

                         </div>

                         <div class="col-md-6">
                             <h4 id="mensaje3"></h4>
                             <ul id="lista2">

                             </ul>
                         </div>
                     </div>
                   </div>
                 </div>
                 <br>

           <!-- </form> -->
         </div>

         <div class="modal-footer">
           <button type="button" class="btn btn-dangerEliminar" id="btn-eliminar" value="">ELIMINAR</button>
           <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
         </div>
     </div>
   </div>
 </div>


 </body>

@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| AYUDA ROLES Y PERMISOS</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjetas de roles</h5>
  <p>
    Administra roles dentro del sistema que permitirán asociar a cada usuario, con el que determinarán sus tareas correspondientes.
    Están relacionadas a permisos con los que se definen dichos roles.
  </p>
</div>
<div class="col-md-12">
  <h5>Tarjetas de permisos</h5>
  <p>
    Cada permiso asociado a un rol es implementado para que cada vista sea filtrada para accesos a las tareas de usuarios asignados.
  </p>
</div>

@endsection
<!-- Termina modal de ayuda -->

@section('scripts')

 <!-- JavaScript personalizado -->
 <!-- <script src="js/seccionCasinos.js"></script> -->

<script src="js/ajaxRoles-Permisos.js?1"></script>

<script type="text/javascript">

  $('.pop').popover({
    html:true
  });

</script>
@endsection
