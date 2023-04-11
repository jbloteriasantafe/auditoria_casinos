@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoCasinos">@svg('usuario','iconoUsuarios')</span>
@endsection
@section('estilos')
<link rel="Stylesheet" type="text/css" href="css/croppie.css" />

<style media="screen">

.btn-fileInput {
    /*margin-left: 15px;*/
    /*height: 34px;*/
    position: relative;
    /*top: 35px;*/
    /*left: 15px;*/
    overflow: hidden;
}
.btn-fileInput input[type=file] {
    position: absolute;
    top: 0;
    right: 0;
    /*width: 100px;*/
    /*min-width: 100%;
    min-height: 100%;*/
    /*font-size: 100px;*/
    /*text-align: right;*/
    /*filter: alpha(opacity=0);*/
    opacity: 0;
    /*outline: none;*/
    /*background: white;*/
    cursor: inherit;
    display: block;
}

</style>
@endsection

@section('contenidoVista')

    <!-- DISEÑO -->
    <style media="screen">
        /* USUARIO */
        #C_imagen {
            padding: 10px;
            border: 4px solid #444;
            width: 200px;
        }

        #C_nombre {
            font-family: Roboto-BoldCondensed;
            font-size: 34px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        #C_username {
            font-family: Roboto-Light;
            font-size: 30px;
            margin-top: 0px;
        }

        /* ROLES Y CASINOS */
        .circuloIcono {
            border: 3px solid #333;
            height: 42px;width: 42px;
            border-radius: 50%;
            text-align: center;
            display: inline-block;
        }
        .circuloIcono i {
          transform: scale(1.4);
          position: relative;
          top: 6px;
        }
        .C_tituloInfo {
          display: inline-block;
          position: relative;
          top: 5px;
          margin-left: 15px;
        }
        #C_roles li, #C_casinos {
            list-style: none;
        }
        #C_roles li h3, #C_casinos li h3 {
            font-family: Roboto-BoldCondensed;
            font-size: 22px;
            letter-spacing: 1px;
            margin: 5px 5px 5px 15px;
            text-transform: uppercase;
            /*font-style: italic;*/
        }



        /* BOTONES DE ACCIONES */
        .C_button {
          margin: 20px 0px;
          background-color: #eee;
          border: 3px solid #fff;
          width: 100%;
        }
        .C_button:hover {
          border: 3px solid #333;
        }
        .C_icono {
          width: 40px;
        }
        .C_button span {
          font-family: Roboto-Regular;
          font-size: 18px;
          margin: 0px 8px 0px 15px;
          position: relative;
          top: 2px;
        }


        /* CONTRASEÑA */
        input.form-control.password {
          letter-spacing: 4px;
          font-size: 24px;
        }
    </style>


    <div class="row">
        <div class="col-md-12">
          <div class="panel panel-default">
              <div class="panel-heading">

              </div>
              <div class="panel-body">
                  <div class="row">
                      <div class="col-md-7" style="border-right: 1px solid #ccc;">
                          <center>
                              <img id="C_imagen" src="/configCuenta/imagen" class="img-circle">
                              <h2 id="C_nombre">{{$usuario['usuario']->nombre}}</h2>
                              <h3 id="C_username">{{'@'.$usuario['usuario']->user_name}}</h3>
                          </center>
                      </div>
                      <div class="col-md-5" style="padding-left: 30px !important;">
                          <div class="row">
                              <div class="col-md-12">
                                  <div class="circuloIcono circle">
                                    <i class="fas fa-sitemap"></i>
                                  </div>
                                  <h5 class="C_tituloInfo">ROL(ES)</h5>
                                  <ul id="C_roles">
                                    @foreach ($usuario['usuario']->roles as $rol)
                                      <li>
                                        <h3>{{$rol->descripcion}}</h3>
                                      </li>
                                    @endforeach
                                    <!-- <li>
                                      <h3>ADMINISTRADOR</h3>
                                    </li>
                                    <li>
                                      <h3>CONTROL</h3>
                                    </li> -->
                                  </ul>
                              </div>
                          </div>
                          <br>
                          <div class="row">
                              <div class="col-md-12">
                                  <div class="circuloIcono circle">
                                    <i class="fas fa-map-marker"></i>
                                  </div>
                                  <h5 class="C_tituloInfo">CASINO(S)</h5>
                                  <ul id="C_casinos">
                                    @foreach ($usuario['usuario']->casinos as $casino)
                                      <li>
                                        <h3>{{$casino->nombre}}</h3>
                                      </li>
                                    @endforeach
                                    <!-- <li>
                                      <h3>MELINCUÉ</h3>
                                    </li>
                                    <li>
                                      <h3>SANTA FE</h3>
                                    </li>
                                    <li>
                                      <h3>ROSARIO</h3>
                                    </li> -->
                                  </ul>
                              </div>
                          </div>
                      </div>
                  </div>
                  <br>
                  <div class="row" style="border-top: 1px solid #ccc;">
                      <div class="col-md-4">
                          <button id="btn-editarDatos" class="btn C_button" type="button" name="button" value="{{$usuario['usuario']->id_usuario}}">
                              <img class="C_icono" src="/img/logos/icono_user.png" alt="">
                              <span>EDITAR DATOS DE USUARIO</span>
                          </button>
                      </div>
                      <div class="col-md-4">
                          <button id="btn-cambiarPass" class="btn C_button" type="button" name="button" value="{{$usuario['usuario']->id_usuario}}">
                              <img class="C_icono" src="/img/logos/icono_key.png" alt="">
                              <span>CAMBIAR CONTRASEÑA</span>
                          </button>
                      </div>
                      <div class="col-md-4">
                          <button id="btn-cambiarImagen" class="btn C_button" type="button" name="button" value="{{$usuario['usuario']->id_usuario}}">
                              <img class="C_icono" src="/img/logos/icono_img.png" alt="">
                              <span>CAMBIAR IMAGEN</span>
                          </button>
                      </div>
                  </div>
              </div>
          </div>
        </div>
    </div>



    <!-- Modal para configurar los datos de usuario -->
    <div id="modalEditarDatos" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
                    <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                    <h3 class="modal-title">EDITAR DATOS PERSONALES</h3>
               </div>
               <div class="modal-body">
                   <div class="row">
                     <div class="col-md-8 col-md-offset-2">
                         <h5>Nombre de usuario</h5>
                         <input id="user_name" class="form-control" type="text" value="{{$usuario['usuario']->user_name}}" data-user="{{$usuario['usuario']->user_name}}">
                         <br>
                         <h5>Email</h5>
                         <input id="email" class="form-control" type="text" value="{{$usuario['usuario']->email}}" data-email="{{$usuario['usuario']->email}}">
                         <br>
                      </div>
                   </div>
               </div>
               <div class="modal-footer">
                    <button class="btn btn-default cancelar" type="button" name="button" data-dismiss="modal">CANCELAR</button>
                    <button id="btn-guardarDatos" class="btn btn-successAceptar" type="button" name="button" value="{{$usuario['usuario']->id_usuario}}">ACEPTAR</button>
               </div>
             </div>
          </div>
    </div>

    <!-- Modal para cambiar la contraseña -->
    <div id="modalCambiarPass" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
                    <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                    <h3 class="modal-title">CAMBIAR CONTRASEÑA</h3>
               </div>
               <div class="modal-body">
                  <div class="row">
                      <div class="col-md-8 col-md-offset-2">
                          <h5>CONTRASEÑA ACTUAL</h5>
                          <input id="pass_actual" class="form-control password" type="password" name="" value="">
                          <br>
                          <h5>CONTRASEÑA NUEVA</h5>
                          <input id="pass_nueva" class="form-control password" type="password" name="" value="">
                          <br>
                          <h5>REPETIR CONTRASEÑA NUEVA</h5>
                          <input id="pass_repetida" class="form-control password" type="password" name="" value="">
                          <br>
                      </div>
                  </div>
               </div>
               <div class="modal-footer">
                    <button class="btn btn-default cancelar" type="button" name="button" data-dismiss="modal">CANCELAR</button>
                    <button id="btn-guardarNuevoPass" class="btn btn-successAceptar" type="button" name="button" value="{{$usuario['usuario']->id_usuario}}">ACEPTAR</button>
               </div>
             </div>
          </div>
    </div>

    <!-- Modal para cambiar la imagen de perfil -->
    <div id="modalCambiarImagen" class="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
               <div class="modal-header modalNuevo" style="font-family: Roboto-Black; background-color: #6dc7be;">
                   <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                   <h3 class="modal-title">CAMBIAR IMAGEN</h3>
               </div>
               <div class="modal-body">
                 <div class="row">
                     <div class="col-md-12">
                         <div id="imagenPerfil"></div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col-md-12">
                       <span class="btn btn-default btn-fileInput">
                         SUBIR IMAGEN <input type="file" name="upload" id="upload">
                       </span>
                       <!-- <button id="btn-guardarImagen" class="btn btn-primary" type="button" value="{{$usuario['usuario']->id_usuario}}">GUARDAR CAMBIOS</button> -->
                     </div>
                 </div>
               </div>
               <div class="modal-footer">
                    <button class="btn btn-default cancelar" type="button" name="button" data-dismiss="modal">CANCELAR</button>
                    <button id="btn-guardarImagen" class="btn btn-successAceptar" type="button" name="button" value="{{$usuario['usuario']->id_usuario}}" >ACEPTAR</button>
               </div>
             </div>
          </div>
    </div>



@endsection

<!-- Comienza modal de ayuda -->
@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">| CONFIGURACIÓN DE CUENTA</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
  <h5>Tarjeta de Configuración de Cuenta</h5>
  <p>
    Sección donde se especifíca información particular de la cuenta de acceso a Usuarios. Se podrán modificar el usuario,
    nombre y apellido, y email. Además de poder cambiar la contraseña, y crear una nueva imagen de perfil.
  </p>
</div>
@endsection
<!-- Termina modal de ayuda -->

@section('scripts')
<!-- JavaScript personalizado -->
<script src="js/seccionConfigCuenta.js?2" charset="utf-8"></script>

<!-- Croppie -->
<script src="js/croppie.js" charset="utf-8"></script>

<script type="text/javascript">


</script>

@endsection
