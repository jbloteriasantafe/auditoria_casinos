<?php
/* Secuencia Random de Imagen Index*/
$ruta = 'imgIndex';
$varImg= rand(1,3);
$rutaImagen = $ruta.$varImg;
$error = $error ?? '';
$CAS_ENDPOINT = $CAS_ENDPOINT ?? null;
$usuarios = $usuarios ?? null;
$form = $form ?? 'login';
$mensaje = $mensaje ?? null;
 ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">

    <link rel="icon" type="image/png" sizes="32x32" href="img/logos/faviconFisico.ico">
    <title>CAS - Lotería de Santa Fe</title>


    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/font-awesome/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="/css/ionicons.min.css">
    <!-- Theme style -->
    <link href="css/indexCss.css" rel="stylesheet"> <!-- VER -->
    <!-- iCheck -->
    <link href="css/blue.css" rel="stylesheet"> <!-- VER -->
    <!-- Fuentes -->
    <link href="/css/importacionFuentes.css" rel="stylesheet">

    <style media="screen">
      #user_name {
        font-size: 15px;
      }

      #password {
        letter-spacing: 3px;
        font-size: 18px;
      }

      #password::placeholder {
        letter-spacing: normal;
        font-size: 15px;
      }
    </style>
  </head>
  <body class="<?php echo $rutaImagen ?>" style="position:relative; min-height:700px; height:100%;">
      <section style="height:100vh;">
        <div class="container">
          <div class="row" style="">
            <div id="boxLog" class="col-lg-4 col-lg-offset-4 col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2 col-xs-10 col-xs-offset-1">

              <div id="contenedorFormulario" style="background-color: #fff; padding: 30px 15px; margin: 10px;">
                    <div class="login-logo">
                      <center><img src="img/logos/logo_2024_loteria.png" width="90%"></center>
                      <br>
                    </div>
                    
                    @if($form == 'login')
                      <center><p class="login-box-msg">Ingresá los datos de Usuario y Contraseña</p></center>
                      <div class="form-group has-feedback">
                        <input id="user_name" type="text" class="form-control" placeholder="Usuario">
                        <span class="glyphicon glyphicon-user form-control-feedback"></span>
                      </div>
                      <div class="form-group has-feedback">
                        <input id="password" type="password" class="form-control" placeholder="Contraseña">
                        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                      </div>
                      <div class="row">
                        <div class="col-xs-8">
                          <div class="checkbox icheck">
                            <label>
                              <input type="checkbox"> Recordar usuario
                            </label>
                          </div>
                        </div>
                        <div class="col-xs-4">
                          <button id="btnIngresar" type="submit" class="btn btn-primary btn-block">Entrar</button>
                        </div>
                      </div>
                      <br>
                      <legend></legend>
                      <div class="alert alert-danger" {{ empty($error)? 'hidden' : '' }} role="alert" id="alertaLogin"><span>{!! $error ?? '' !!}</span></div>
                      @if(!empty($mensaje))
                      <div class="alert alert-success"><span>{!! $mensaje !!}</span></div>
                      @endif
                      <center><a href="/login?accion={{urlencode('olvideMiContraseña_ingresarUser')}}" style="color: #337ab7;">+   Olvidé mi Contraseña</a><br></center>
                      <br>
                      @if($CAS_ENDPOINT)
                      <div class="row">
                        <div class="col-xs-12">
                          <a role="button" href="{{$CAS_ENDPOINT}}/login?service={{urlencode($CAS_service)}}{{$CAS_renew? '&renew' : ''}}" class="btn btn-block" style="background: #FD7400;color: white;border-color: border-color: #a42e2e;">Ingresar con UID</a>
                        </div>
                      </div>
                      @endif
                    @elseif(strpos($form,'olvideMiContraseña_') === 0)
                      <style>
                        .data-css-hover:hover {
                          background: rgba(0,0,0,0.1);
                          box-shadow: 0 0 2px black;
                          padding: 0.1em;
                        }
                        .data-css-hover:not(:hover) {
                          box-shadow: 0 0 2px white;
                          padding: 0.1em;
                        }
                      </style>
                      <?php $subform = substr($form,strlen('olvideMiContraseña_')); ?>
                      <form action="/login" method="GET">
                      @if($subform == 'ingresarUser')
                        <input name="accion" value="olvideMiContraseña_enviarCodigo" hidden readonly>
                        <div class="form-group has-feedback">
                          <input name="email" type="email" class="form-control" placeholder="Correo Electrónico">
                          <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Enviar código</button>
                      @elseif($subform == 'ingresarCodigo')
                        <input name="accion" value="olvideMiContraseña_verificarCodigo" hidden readonly>
                        <div class="form-group has-feedback">
                          <input value="{{$email}}" name="email" type="email" class="form-control" placeholder="Correo Electrónico" readonly>
                          <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                        </div>
                        <?php
                          $url_reenviar = url('login').'?'.http_build_query([
                            'accion' => 'olvideMiContraseña_enviarCodigo',
                            'email' => $email
                          ]);//Ya esta urlencoded asi que lo envio sin escapar
                        ?>
                        <a href="{!! $url_reenviar !!}" class="btn btn-warning" style="width: 100%;" role="button">Reenviar</a>
                        <div style="width: 100%;">&nbsp;</div>
                        <div class="form-group has-feedback">
                          <input name="codigo" type="text" class="form-control" placeholder="Código recibido">
                          <span class="glyphicon form-control-feedback"></span>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Verificar</button>
                      @elseif($subform == 'seleccionarUsuarios')
                        <input name="accion" value="olvideMiContraseña_verificarSeleccionUsuarios" hidden readonly>
                        @foreach($usuarios as $uidx => $u)
                        <div class="checkbox icheck data-css-hover" style="width: 100%;">
                          <label style="width: 100%;">
                            <?php $checked = !empty($u->preferencial) || (count($usuarios) == 1);?>
                            <input type="checkbox" name="usuarios[{{$uidx}}]" value="{{$u->id_usuario}}" {{$checked? 'checked' : ''}}>
                            {{$u->user_name}} ({{$u->email}}) 
                            <br>
                            {{$u->roles->pluck('descripcion')->implode(' - ')}}
                          </label>
                        </div>
                        @endforeach
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Seleccionar</button>
                      @elseif($subform == 'ingresarPassword')
                        <input name="accion" value="olvideMiContraseña_resetearPasswords" hidden readonly>
                        <div class="form-group has-feedback">
                          <input name="password" type="password" class="form-control" placeholder="Contraseña" autocomplete="off">
                          <span class="glyphicon form-control-feedback"></span>
                        </div>
                        <div class="form-group has-feedback">
                          <input name="password_confirmation" type="password" class="form-control" placeholder="Repetirla" autocomplete="off">
                          <span class="glyphicon form-control-feedback"></span>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Resetear</button>
                      @endif
                        <legend></legend>
                        <div class="alert alert-danger" {{ empty($error)? 'hidden' : '' }} role="alert" id="alertaLogin"><span>{!! $error ?? '' !!}</span></div>
                      </form>
                    @elseif($form == 'CAS_seleccionarUsuario')
                      @if(!empty($usuarios))
                      <center><p class="login-box-msg">Seleccioná un usuario</p></center>
                      @else
                      <center><p class="login-box-msg">No existe usuario asociado a su DNI o Correo</p></center>
                      @endif
                      <div class="row" style="display: flex;flex-direction: column;gap: 1em;padding: 1em;">
                        @foreach(($usuarios ?? []) as $u)
                        <?php
                          $url_logear_usuario = url('login').'?'.http_build_query([
                            'accion' => 'CAS_logearUsuario',
                            'user_name' => $u->user_name
                          ]);//Ya esta urlencoded asi que lo envio sin escapar
                        ?>
                        <a role="button" href="{!! $url_logear_usuario !!}" class="btn" style="background: #FD7400;border-color: border-color: #a42e2e">
                          <span style="color: white;font-weight: bolder;">{{$u->user_name}}</span>
                          <br>
                          <span style="color: white;font-size: 0.8em;">{{$u->roles->pluck('descripcion')->implode(' - ')}}</span>
                        </a>
                        @endforeach
                        <a role="button" href="/login" class="btn btn-primary" style="width: 5em;">Volver</a>
                      </div>
                    @endif
              </div> <!-- contenedorFormulario -->
            </div> <!-- boxLogo -->
          </div> <!-- row -->
        </div>  <!-- container -->
      </section>


      <div class="container-fluid" style="position:absolute; bottom:0px; height:auto; width:100%; background:#000; color:#eee;">
          <div class="row">
              <div id="columnaCopyright" class="col-lg-4 col-lg-offset-4 col-md-6 col-sm-6">
                  <h5 style="padding-top:10px;font-size:16px;padding-top:12px;">LOTERÍA DE SANTA FE</h5>
                  <h5>Copyright © 2018 | Todos los derechos reservados</h5>
              </div>
              <style>
                .iconos_menu_inicio {
                  display: flex;
                  flex-direction: column;
                  justify-content: center;
                }
                .iconos_menu_inicio_padding {
                  font-size: 4vmax;
                .iconos_menu_inicio_padding {
                  font-size: 4vmax;
                  margin: 0;
                  padding: 0;
                }
              </style>
              <div class="col-lg-4 col-md-6 col-sm-6" style="display: flex;justify-content: flex-end;">
                <a href="https://twitter.com/loteriasantafe" class="iconos_menu_inicio" style="color:white !important;">
                  <i class="fa fa-twitter fa-2x"></i>
                </a>
                <span class="iconos_menu_inicio_padding">&nbsp;</span>
                <a href="https://www.facebook.com/loteriadesantafe"  class="iconos_menu_inicio" style="color:white !important;">
                  <i class="fa fa-facebook-square fa-2x"></i>
                </a>
                <span class="iconos_menu_inicio_padding">&nbsp;</span>
                <a href="http://www.loteriasantafe.gov.ar/" class="iconos_menu_inicio" style="width: 125px;">
                  <img src="/img/logos/logo_2024_loteria_bn.png">
                </a>
                <span class="iconos_menu_inicio_padding">&nbsp;</span>
                <a href="http://www.santafe.gov.ar/index.php/web" class="iconos_menu_inicio"  style="width: 85px;">
                  <img src="/img/logos/logo_2024_bn.png">
                </a>
              </div>
          </div>

      </div>
      
      <meta name="_token" content="{!! csrf_token() !!}" />
      <!-- jQuery 2.2.3 -->
      <script src="js/jquery.js"></script>
      <!-- Bootstrap 3.3.6 -->
      <script src="js/bootstrap.min.js"></script>
      <!-- iCheck -->
      <script src="js/icheck.min.js"></script>
      <!-- JavaScript personalizado -->
      <script src="js/index.js?2" charset="utf-8"></script>
  </body>
</html>
