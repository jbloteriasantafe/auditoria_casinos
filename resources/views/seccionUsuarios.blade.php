@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoUsuarios">@svg('usuario','iconoUsuarios')</span>
@endsection
@section('contenidoVista')
<?php 
  use App\Http\Controllers\UsuarioController;
  $usuario = UsuarioController::getInstancia()->quienSoy()['usuario'];
?>

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
                  <h5>Casino</h5>
                  <select id="buscadorCasino" class="form-control">
                    @if($usuario->es_superusuario)
                    <option value="">- Todos los casinos -</option>
                    @endif
                    @foreach($casinos as $c)
                    <option value="{{$c->id_casino}}">{{$c->codigo}}</option>
                    @endforeach
                  </select>
                </div>
                <div class="row" style="padding-top: 8% !important;">
                  <div class="col-md-1 col-md-offset-5">
                    <button id="buscar" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-fw fa-search"></i> BUSCAR</button>
                  </div>
                </div>
              </div>
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

<div class="modal fade" id="modalUsuario" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header modalNuevo" style="font-family: Roboto-Black;">
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
                </div>
                <div class="col-md-6">
                  <h5>Nombre Completo</h5>
                  <input type="text" class="form-control" id="nombre" name="cod_identificacion" placeholder="Nombre Completo" value="">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <h5>Email</h5>
                  <input type="text" class="form-control" id="email" name="nro_niv_progresivos" placeholder="Email" value="">
                </div>
                <div class="col-md-6">
                  <h5>Contraseña</h5>
                  <input type="text" class="form-control" id="password" name="nro_niv_progresivos" placeholder="Contraseña" value="">
                  <button id="btn-resetearPass" class="btn btn-infoBuscar" type="button" name="button">RESETEAR CONTRASEÑA  [DNI]</button>
                </div>
              </div>
              <hr style="margin: 1%;">
              @if($usuario->es_superusuario)
              <div class="row">
                <div class="col-md-12" id="contenedorCasino">
                  <h5>Casino</h5>
                  @foreach($casinos as $c)
                  <input style="margin-left:40px;" id="casino{{$c->id_casino}}" type="checkbox" name="" value="{{$c->id_casino}}"> {{$c->nombre}}
                  @endforeach
              </div>
              <br>
              @endif
              </div>
              <div class="row">
                <div class="col-md-12" id="contenedorRoles">
                  <h5>Roles</h5>
                  @foreach($roles as $rol)
                  @if($rol != 'SUPERUSUARIO' || ($rol == 'SUPERUSUARIO' && $usuario->es_superusuario))
                  <input style="margin-left:40px;" id="rol{{$rol->id_rol}}" type="checkbox" name="" value="{{$rol->id_rol}}"> {{$rol->descripcion}}
                  @endif
                  @endforeach
                </div>
              </div>
              <br>
              <div class="row">
                <div class="col-md-12">
                  <h5>PERMISOS</h5>
                  <div id="contenedorPermisos" style="text-align:center;"></div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-successAceptar" id="btn-guardar" value="nuevo">ACEPTAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="font-family: Roboto-Black; color: #EF5350">
        <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
        <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
        <h3 class="modal-titleEliminar" id="myModalLabel">ADVERTENCIA</h3>
      </div>
      <div id="colapsado" class="collapse in">
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

<table hidden>
  <tr id="filaEjemploUsuario">
    <td class="col-xl-3 col-md-3 user_name">user</td>
    <td class="col-xl-3 col-md-3 nombre">nombre/td>
    <td class="col-xl-3 col-md-3 email">email</td>
    <td class="col-xl-3 col-md-3">
      <button class="btn btn-info info" title="VER"><i class="fa fa-fw fa-search-plus"></i></button>
      <button class="btn btn-warning modificar" title="MODIFICAR"><i class="fa fa-fw fa-pencil-alt"></i></button>
      <button class="btn btn-danger eliminar" title="ELIMINAR"><i class="fa fa-fw fa-trash-alt"></i></button>
    </td>
  </tr>
</table>

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
<script src="js/seccionUsuarios.js?2"></script>
@endsection
