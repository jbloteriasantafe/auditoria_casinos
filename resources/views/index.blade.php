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

    <link rel="icon" type="image/png" sizes="32x32" href="img/logos/favicon.ico">
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
                      <center><img src="img/logos/logo_nuevo2.png" width="90%"></center> <!-- VER -->
                    </div>
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
                    <!-- /.social-auth-links -->
                    <legend></legend>
                    <div class="alert alert-danger" hidden role="alert" id="alertaLogin"><span></span></div>
                    <center><a href="" style="color: #337ab7;">+   Olvidé mi Contraseña</a><br></center>


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
              <div class="col-lg-4 col-md-6 col-sm-6">
                  <a href="http://www.santafe.gov.ar/index.php/web">
                      <img src="/img/logos/logo-provincia.png" width="58px" style="float:right; margin:20px;">
                  </a>
                  <a href="http://www.loteriasantafe.gov.ar/">
                      <img src="/img/logos/logo_nuevo2_bn.png" width="135px" style="float:right; margin: 13px 5px 0px 5px;">
                  </a>
                  <a href="https://www.facebook.com/loteriadesantafe" style="color:white !important;">
                      <i class="fa fa-facebook-square fa-2x" style="float:right; margin:35px 30px 0px 10px;"></i>
                  </a>
                  <a href="https://twitter.com/loteriasantafe" style="color:white !important;">
                      <i class="fa fa-twitter fa-2x" style="float:right; margin:35px 20px 0px 10px;"></i>
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
      <script src="js/index.js?1" charset="utf-8"></script>

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
