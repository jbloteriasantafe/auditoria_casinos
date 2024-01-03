<?php
use App\Rol;
use App\Permiso;
use App\Http\Controllers\permisoController;

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="_token" content="{!! csrf_token() !!}"/>

    <link rel="icon" type="image/png" sizes="32x32" href="img/logos/favico.ico">
    <title>Gestión Roles y Permisos</title>

    <head>

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" type="image/png" sizes="32x32" href="img/logos/faviconFisico.ico">
        <title>CAS - Lotería de Santa Fe</title>

        <!-- Bootstrap Core CSS -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Custom CSS -->
        <link href="css/sb-admin.css" rel="stylesheet">

        <!-- Custom Fonts -->
        <link href="font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    </head>


<body>

  <div id="wrapper">

          <!-- Navigation -->
          <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
              <!-- Brand and toggle get grouped for better mobile display -->
              <div class="navbar-header">
                  <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
                      <span class="sr-only">Toggle navigation</span>
                      <span class="icon-bar"></span>
                      <span class="icon-bar"></span>
                      <span class="icon-bar"></span>
                  </button>
                  <a class="navbar-brand" href="index.html">SB Admin</a>
              </div>
              <!-- Top Menu Items -->
              <ul class="nav navbar-right top-nav">
                  <li class="dropdown">
                      <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-envelope"></i> <b class="caret"></b></a>
                      <ul class="dropdown-menu message-dropdown">
                          <li class="message-preview">
                              <a href="#">
                                  <div class="media">
                                      <span class="pull-left">
                                          <img class="media-object" src="http://placehold.it/50x50" alt="">
                                      </span>
                                      <div class="media-body">
                                          <h5 class="media-heading"><strong>John Smith</strong>
                                          </h5>
                                          <p class="small text-muted"><i class="fa fa-clock-o"></i> Yesterday at 4:32 PM</p>
                                          <p>Lorem ipsum dolor sit amet, consectetur...</p>
                                      </div>
                                  </div>
                              </a>
                          </li>
                          <li class="message-preview">
                              <a href="#">
                                  <div class="media">
                                      <span class="pull-left">
                                          <img class="media-object" src="http://placehold.it/50x50" alt="">
                                      </span>
                                      <div class="media-body">
                                          <h5 class="media-heading"><strong>John Smith</strong>
                                          </h5>
                                          <p class="small text-muted"><i class="fa fa-clock-o"></i> Yesterday at 4:32 PM</p>
                                          <p>Lorem ipsum dolor sit amet, consectetur...</p>
                                      </div>
                                  </div>
                              </a>
                          </li>
                          <li class="message-preview">
                              <a href="#">
                                  <div class="media">
                                      <span class="pull-left">
                                          <img class="media-object" src="http://placehold.it/50x50" alt="">
                                      </span>
                                      <div class="media-body">
                                          <h5 class="media-heading"><strong>John Smith</strong>
                                          </h5>
                                          <p class="small text-muted"><i class="fa fa-clock-o"></i> Yesterday at 4:32 PM</p>
                                          <p>Lorem ipsum dolor sit amet, consectetur...</p>
                                      </div>
                                  </div>
                              </a>
                          </li>
                          <li class="message-footer">
                              <a href="#">Read All New Messages</a>
                          </li>
                      </ul>
                  </li>
                  <li class="dropdown">
                      <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-bell"></i> <b class="caret"></b></a>
                      <ul class="dropdown-menu alert-dropdown">
                          <li>
                              <a href="#">Alert Name <span class="label label-default">Alert Badge</span></a>
                          </li>
                          <li>
                              <a href="#">Alert Name <span class="label label-primary">Alert Badge</span></a>
                          </li>
                          <li>
                              <a href="#">Alert Name <span class="label label-success">Alert Badge</span></a>
                          </li>
                          <li>
                              <a href="#">Alert Name <span class="label label-info">Alert Badge</span></a>
                          </li>
                          <li>
                              <a href="#">Alert Name <span class="label label-warning">Alert Badge</span></a>
                          </li>
                          <li>
                              <a href="#">Alert Name <span class="label label-danger">Alert Badge</span></a>
                          </li>
                          <li class="divider"></li>
                          <li>
                              <a href="#">View All</a>
                          </li>
                      </ul>
                  </li>
                  <li class="dropdown">
                      <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-user"></i> John Smith <b class="caret"></b></a>
                      <ul class="dropdown-menu">
                          <li>
                              <a href="#"><i class="fa fa-fw fa-user"></i> Profile</a>
                          </li>
                          <li>
                              <a href="#"><i class="fa fa-fw fa-envelope"></i> Inbox</a>
                          </li>
                          <li>
                              <a href="#"><i class="fa fa-fw fa-gear"></i> Settings</a>
                          </li>
                          <li class="divider"></li>
                          <li>
                              <a href="#"><i class="fa fa-fw fa-power-off"></i> Log Out</a>
                          </li>
                      </ul>
                  </li>
              </ul>
              <!-- MENÚ DE IZQUIERDA FIJA -->
              <div class="collapse navbar-collapse navbar-ex1-collapse">
                  <ul class="nav navbar-nav side-nav">
                      <li>
                          <a href="index.html"><i class="fa fa-fw fa-dashboard"></i> Dashboard</a>
                      </li>
                      <li>
                          <a href="casinos"><i class="fa fa-fw fa-copyright"></i> Administrar casinos</a>
                      </li>
                      <li>
                          <a href="javascript:;" data-toggle="collapse" data-target="#demo"><i class="fa fa-fw fa-user"></i> Usuarios <i class="fa fa-fw fa-caret-down"></i></a>
                          <ul id="demo" class="collapse in">
                              <li>
                                  <a href="usuarios">Gestionar Usuarios</a>
                              </li>
                              <li class="active">
                                  <a href="roles">Roles y Permisos</a>
                              </li>
                          </ul>
                      </li>
                      <li>
                          <a href="expedientes"><i class="fa fa-fw fa-briefcase"></i> Expedientes</a>
                      </li>
                      <li>
                          <a href="disposiciones"><i class="fa fa-fw fa-file-text-o"></i> Disposiciones</a>
                      </li>
                      <li>
                          <a href="bootstrap-grid.html"><i class="fa fa-fw fa-wrench"></i> Bootstrap Grid</a>
                      </li>
                      <li>
                          <a href="javascript:;" data-toggle="collapse" data-target="#demo"><i class="fa fa-fw fa-user"></i> Dropdown <i class="fa fa-fw fa-caret-down"></i></a>
                          <ul id="demo" class="collapse">
                              <li>
                                  <a href="#">Dropdown Item</a>
                              </li>
                              <li>
                                  <a href="#">Dropdown Item</a>
                              </li>
                          </ul>
                      </li>
                      <li>
                          <a href="blank-page.html"><i class="fa fa-fw fa-file"></i> Blank Page</a>
                      </li>
                      <li>
                          <a href="index-rtl.html"><i class="fa fa-fw fa-dashboard"></i> RTL Dashboard</a>
                      </li>
                  </ul>
              </div>
              <!-- /.navbar-collapse -->
          </nav>


    <div id="page-wrapper">
     <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">
            <h2>Roles y Permisos</h2>
            <legend></legend>
        </div>
      </div>

      <br>

      <div class="row"><!-- DEFINIR ROW (SIEMPRE EL COL TIENE QUE REFERENCIAR A 12 O SUMAR 12 ENTRE LAS TARJETAS) -->
        <div class="col-md-6">
          <div class="panel panel-default"> <!-- TARJETA ADAPTABLE -->
            <div class="panel-heading" style="background: #5cb85c; color: #fff;"> <!-- TÍTULO DE LA TARJETA -->
              <center> <!-- CONTENIDO DE LA TARJETA -->
                <h2>Roles</h2>
              </center>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="panel panel-default"> <!-- TARJETA ADAPTABLE -->
            <div class="panel-heading" style="background: #337ab7; color: #fff;"> <!-- TÍTULO DE LA TARJETA -->
              <center> <!-- CONTENIDO DE LA TARJETA -->
                <h2>Permisos</h2>
              </center>
            </div>
          </div>
       </div>
     </div>

     <div class="row">
       <div class="col-md-6">
         <button id="btn-add" type="button" class="btn btn-success btn-lg"><h4>+ NUEVO ROL</h4></button>
       </div>
       <div class="col-md-6">
         <button id="btn-add2" type="button" class="btn btn-primary btn-lg"><h4>+ NUEVO PERMISO</h4></button>
       </div>
     </div><br>
       <div class="row">
         <div class="col-md-6"> <!-- columna TABLA CASINOS -->
           <div class="panel panel-default">
             <div class="panel-heading">
                 <h4>Roles</h4>
             </div>
             <div class="panel-body">
               <table class="table">
                 <thead>
                   <tr>
                     <th>ROL</th>
                     <th>ACCIÓN</th>
                   </tr>
                 </thead>
                 <tbody id="cuerpoTablaRoles">
                   @foreach($roles as $rol)
                   <tr id="{{$rol->id_rol}}">
                     <td> {{$rol->descripcion}}<a id="popoverData" class="pop btn" href="#" data-content="@foreach($rol->permisos as $permiso) · {{$permiso->descripcion}} <br> @endforeach" data-placement="bottom" data-original-title="Permisos asociados" > <span class="badge"><i class="fa fa-user"></i></span></a></td>
                     <td><!-- ENLACE DE BOTON MODAL background: #ff9d2d;-->
                         <button type="button" class="btn btn-warning modificarRol" value="{{$rol->id_rol}}"><i class="fa fa-edit"></i> Modificar</button>
                         <button type="button" class="btn btn-danger openEliminar" data-tipo="rol" value="{{$rol->id_rol}}"><i class="fa fa-trash"></i> Eliminar</button>
                     </td>
                   </tr>
                   @endforeach
                 </tbody>
               </table>
             </div>
           </div>
         </div> <!--/columna TABLA -->

         <div class="col-md-6"> <!-- columna TABLA CASINOS -->
           <div class="panel panel-default">
             <div class="panel-heading">
                 <h4>Permisos</h4>
             </div>
             <div class="panel-body">
               <table class="table">
                 <thead>
                   <tr>
                     <th>PERMISO</th>
                     <th>ACCIÓN</th>
                   </tr>
                 </thead>
                 <tbody id="cuerpoTablaPermisos">
                   @foreach($permisos as $permiso)
                   <tr id="{{$permiso->id_permiso}}">
                     <td>{{$permiso->descripcion}}</td>
                     <td>
                       <button class="btn btn-warning modificarPermiso" value="{{$permiso->id_permiso}}"><i class="fa fa-edit"></i> Modificar</button>
                       <button class="btn btn-danger openEliminar" data-tipo="permiso" value="{{$permiso->id_permiso}}"><i class="fa fa-trash"></i> Eliminar</button>
                     </td>
                   </tr>
                   @endforeach
                 </tbody>
               </table>
             </div>
           </div>
         </div> <!--/columna TABLA -->

       </div>
       <br><br>
     </div><br><br><br><br><br><br>
     <!-- style="background: #5cb85c;" -->
     <legend></legend>
   </div>
 </div>


 <div class="modal fade" id="myModalRol" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">

          <div class="modal-header" style="background: #5cb85c;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            <h2 class="modal-title" id="myModalLabel" style="color: #fff;">Nuevo Rol</h2>

         </div>

         <div class="modal-body">
           <p id="errores"></p>
             <form id="frmRol" name="frmRol" class="form-horizontal" novalidate="">

                 <!-- Si no anda falta el <fieldset> -->

                 <div class="row">
                   <div class="col-md-8 col-md-offset-2">
                     <div class="form-group">
                      <h4><label for="comment">Descripción:</label></h4>
                      <textarea class="form-control" rows="5" id="comment"></textarea>
                      <br>
                       <div class="alert alert-danger" hidden role="alert" id="alertaDescripcion"><span></span></div>
                     </div>

                     <div class="row">
                         <div id="conteinerPermisos" class="col-md-6">
                             <h4>Permisos</h4>
                             @foreach($permisos as $permiso)
                              <div class="checkbox"> <label><input id="rol{{$permiso->id_permiso}}" type="checkbox" value="{{$permiso->id_permiso}}">{{$permiso->descripcion}}</label></div>
                             @endforeach


                         </div>
                     </div>

                   </div>
                 </div>
                 <br>

           </form>



         </div>

         <div class="modal-footer">
           <button type="button" class="btn btn-success" id="btn-save-rol" value="add">Crear Rol</button>
           <input type="hidden" id="id_rol" name="id_rol" value="0">
           <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
         </div>
     </div>
   </div>
 </div>

 <div class="modal fade" id="myModalPermisos" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">

          <div class="modal-header" style="background: #337ab7;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            <h2 class="modal-title" id="myModalLabel" style="color: #fff"; >Nuevo Permiso</h2>
         </div>

         <div class="modal-body">
           <p id="errores"></p>
             <form id="frmPermiso" name="frmPermiso" class="form-horizontal" novalidate="">

                 <!-- Si no anda falta el <fieldset> -->

                 <div class="row">
                   <div class="col-md-8 col-md-offset-2">


                     <div class="form-group">
                      <h4><label for="comment">Descripción:</label></h4>
                      <textarea class="form-control" rows="5" id="commentPermiso"></textarea>
                       <br>

                     <div class="alert alert-danger" hidden role="alert" id="alertaDescripcion"><span></span></div>
                     </div>

                     <div class="row">
                         <div id="conteinerRoles" class="col-md-6">
                             <h4>Roles Asociados</h4>
                             @foreach($roles as $rol)
                             <div class="checkbox"> <label><input id="permiso{{$rol->id_rol}}" type="checkbox" value="{{$rol->id_rol}}">{{$rol->descripcion}}</label></div>
                             @endforeach
                         </div>
                     </div>

                   </div>
                 </div>
                 <br>

           </form>



         </div>

         <div class="modal-footer">
           <button type="button" class="btn btn-primary" id="btn-save-permiso" value="add">Crear Permiso</button>
           <input type="hidden" id="id_permiso" name="id_permiso" value="0">
           <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
         </div>
     </div>
   </div>
 </div>

 <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">

          <div class="modal-header" style="background: #d9534f; color: #fff;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            <h2 class="modal-title" id="myModalLabel">Eliminar</h2>
         </div>

         <div class="modal-body">
           <p id="errores"></p>
             <form id="frmEliminar" name="frmEliminar" class="form-horizontal" novalidate="">

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

           </form>
         </div>

         <div class="modal-footer">
           <button type="button" class="btn btn-danger" id="btn-eliminar" value="">Eliminar</button>
           <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
         </div>
     </div>
   </div>
 </div>


 </body>

 <!-- jQuery -->
 <script src="js/jquery.js"></script>

 <!-- Bootstrap Core JavaScript -->
 <script src="js/bootstrap.min.js"></script>

 <!-- JavaScript personalizado -->
 <!-- <script src="js/seccionCasinos.js"></script> -->

<script src="js/ajaxRoles-Permisos.js"></script>

<script type="text/javascript">

  $('.pop').popover({
    html:true
  });

  $('#alertaCasino').hide();

  $('#btn-add').click(function(){
    $('#myModalRol #myModalLabel').text('Nuevo Rol');
    $('#btn-save-rol').val("add");
    $('#myModalRol .modal-header').attr('style','background: #449d44');
    $('#btn-save-rol').text('Crear Rol');
    $('#btn-save-rol').attr('class','btn btn-success');

    $('#frmRol').trigger("reset");
    $('#myModalRol').modal('show');
  });

  $('#btn-add2').click(function(){
    $('#myModalPermisos #myModalLabel').text('Nuevo Permiso');
    $('#btn-save-permiso').val("add");
    $('#myModalPermisos .modal-header').attr('style','background: #286090');
    $('#btn-save-permiso').text('Crear Permiso');
    $('#btn-save-permiso').attr('class','btn btn-primary');

    $('#frmPermiso').trigger("reset");
    $('#myModalPermisos').modal('show');
  });
</script>


</body>
</html>
