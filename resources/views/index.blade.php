<?php
/* Secuencia Random de Imagen Index*/
$ruta = 'imgIndex';
$varImg= rand(1,3);
$rutaImagen = $ruta.$varImg;

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
                  <!-- /.login-logo -->
                    <div class="login-logo">
                      <center><img src="img/logos/logo_2024_loteria.png" width="90%"></center> <!-- VER -->
                      <br>
                    </div>
                    
                    @if($error !== null || ($error === null && $usuarios === null))
                      <center><p class="login-box-msg">Ingresá los datos de Usuario y Contraseña</p></center>
                      <!-- <form action="" method="post"> -->
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
                          <!-- /.col -->
                          <div class="col-xs-4">
                            <button id="btnIngresar" type="submit" class="btn btn-primary btn-block">Entrar</button>
                          </div>
                          <!-- /.col -->
                        </div>
                      <!-- </form> -->
                      <br>
                      <legend></legend>
                      <div class="alert alert-danger" {{ empty($error)? 'hidden' : '' }} role="alert" id="alertaLogin"><span>{{$error ?? ''}}</span></div>
                      <center><a href="" style="color: #337ab7;">+   Olvidé mi Contraseña</a><br></center>
                      <br>
                        <!-- /.social-auth-links -->
                      <?php $CAS_ENDPOINT = env('CAS_ENDPOINT') ?? request()->CAS_ENDPOINT_TOREMOVE ?? null;//para poder probar en producción ?>
                      @if(!empty($CAS_ENDPOINT))
                      <div class="row">
                        <div class="col-xs-12">
                          <a role="button" href="{{$CAS_ENDPOINT}}/login?service={{urlencode(url('login'))}}&renew" class="btn btn-block" style="background: #FD7400;color: white;border-color: border-color: #a42e2e;">Ingresar con UID</a>
                        </div>
                      </div>
                      @endif
                    @else
                      @if(!empty($usuarios))
                      <center><p class="login-box-msg">Seleccioná un usuario</p></center>
                      @else
                      <center><p class="login-box-msg">No existe usuario asociado a su DNI o Correo</p></center>
                      @endif
                      
                      <div class="row" style="display: flex;flex-direction: column;gap: 1em;padding: 1em;">
                        @foreach(($usuarios ?? []) as $u)
                        <a role="button" href="/login?user_name={{urlencode($u->user_name)}}" class="btn" style="background: #FD7400;color: white;border-color: border-color: #a42e2e;font-weight: bolder;text-shadow: 0px 0px 2px #353535;">{{$u->user_name}}</a>
                        @endforeach
                        <a role="button" href="/login" class="btn btn-primary" style="width: 5em;">Volver</a>
                      </div>
                    @endif
    <!-- /.login-box -->
              </div> <!-- contenedorFormulario -->
            </div> <!-- boxLogo -->
          </div> <!-- row -->
        </div>  <!-- container -->
      </section>


      <div class="container-fluid" style="position:absolute; bottom:0px; height:auto; width:100%; background:#000; color:#eee;">
          <div class="row">
              <!-- <div class="col-lg-4">

              </div> -->
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
      <!-- <footer style="bottom:0px;">
        <div class="container-fluid">
          <center><h5>Lotería de Santa Fe, 2017</h5></center>

        </div>
      </footer> -->

      <meta name="_token" content="{!! csrf_token() !!}" />
      <!-- jQuery 2.2.3 -->
      <script src="js/jquery.js"></script>
      <!-- Bootstrap 3.3.6 -->
      <script src="js/bootstrap.min.js"></script>
      <!-- iCheck -->
      <script src="js/icheck.min.js"></script>
      <!-- JavaScript personalizado -->
      <script src="js/index.js" charset="utf-8"></script>

      <!-- <script>
        $(function () {
          $('input').iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square-blue',
            increaseArea: '20%' // optional
          });
        });
      </script> -->
  </body>
</html>
