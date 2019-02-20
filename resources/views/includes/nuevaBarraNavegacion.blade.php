<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
$id_usuario = $usuario['usuario']->id_usuario;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="_token" content="{!! csrf_token() !!}"/>
    <!-- <meta http-equiv="Cache-Control" content="public"> -->

    <link rel="icon" type="image/png" sizes="32x32" href="/img/logos/favicon.png">
    <title>CAS - Lotería de Santa Fe</title>

    <!-- Bootstrap Core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/bootstrap-col-xl.css" rel="stylesheet">

    <!-- Custom CSS -->
    <!-- <link href="css/sb-admin.css" rel="stylesheet"> -->
    <link href="/css/estilosBarraNavegacion.css" rel="stylesheet">
    <link href="/css/estilosBotones.css" rel="stylesheet">
    <link href="/css/estilosModal.css" rel="stylesheet">
    <link href="/css/estilosFileInput.css" rel="stylesheet">
    <link href="/css/estilosPopUp.css" rel="stylesheet">
    <link href="/css/table-fixed.css" rel="stylesheet">
    <link href="/css/importacionFuentes.css" rel="stylesheet">
    <link href="/css/banners.css" rel="stylesheet">
    <link href="/css/tarjetasMenues.css" rel="stylesheet">
    <link href="/css/flaticon.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="/css/style.css">

    <link rel="stylesheet" type="text/css" href="/css/component.css" />

    <!-- Custom Fonts -->
    <link rel="stylesheet" type="text/css" href="/font-awesome/css/font-awesome.min.css" >

    <!-- Animaciones de los LINKS en MENU -->
    <link rel="stylesheet" href="/css/animacionesMenu.css">

    <!-- Animaciones de alta -->
    <link rel="stylesheet" href="/css/animacionesAlta.css">

    <!-- Estilos de la barra de menú -->
    <link rel="stylesheet" href="/css/barraMenu.css">

    <!-- Animación de carga de datos -->
    <link rel="stylesheet" href="/css/loadingAnimation.css">

    <!-- Mesaje de notificación -->
    <link rel="stylesheet" href="/css/mensajeExito.css">
    <link rel="stylesheet" href="/css/mensajeError.css">


    <link rel="stylesheet" href="/css/estilosSVG.css">


    @section('estilos')
    @show

</head>

<body>

  <!-- <div class="container-fluid"> -->

      <div id="contenedor">
        <div id="sub-contenedor">
            <div id="barraSuperior">
                <a onclick="window.location = window.location.protocol + '//' + window.location.host + '/inicio'" href="#"><img class="logo" src="/img/logos/logo_brand_blanco.png" alt="imagen_logo"></a>
                <button id="btnBars" data-toggle="collapse" data-target="#barraIzquierda" class="" type="button"><i class="fa fa-bars fa-2x"></i></button>
                <!-- Acá va el usuario -->
                <div id="seccionUsuarioBarra">
                  <?php
                    $tieneImagen = UsuarioController::getInstancia()->tieneImagen();

                    if($tieneImagen) {
                      echo '<img id="img_perfilBarra" src="/usuarios/imagen" class="img-circle">';
                    }
                    else {
                      echo '<img id="img_perfilBarra" src="/img/img_user.jpg" class="img-circle">';
                    }
                  ?>
                  <h3 id="nombreBarra">{{$usuario['usuario']->nombre}}</h3>
                  <h4 id="usuarioBarra">{{'@'.$usuario['usuario']->user_name}}</h4>
                </div>
                <!-- Icono configurar cuenta -->
                <a  onclick="window.location = window.location.protocol + '//' + window.location.host + '/configCuenta'" href="#"><button id="btnBarraConfigCuenta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/configCuenta'" href="#" class="" type="button"><i class="fa fa-gear fa-2x"></i></button></a>
                <!-- Icono para cerrar sesión -->
                <button id="btnBarraCerrarSesion" class="cerrarSesion" type="button"><img src="/img/logos/salida.png" style="margin-top:1px; width: 17px;"></button>
                <!-- Icono de notificaciones -->
                <!-- <button id="notificaciones" class="notificaciones" type="button"><img src="/img/logos/Bell-icon.png">Notificaciones</button> -->


            </div>
            <div id="barraIzquierda" class="collapse">
              <div id="seccionLogo">
                  <a  onclick="window.location = window.location.protocol + '//' + window.location.host + '/inicio'" href="#"><img class="logo" src="/img/logos/logo_brand_blanco.png" alt="imagen_logo"></a>
              </div>
              <div id="seccionUsuario">
                  <?php
                    $tieneImagen = UsuarioController::getInstancia()->tieneImagen();

                    if($tieneImagen) {
                      echo '<img id="img_perfil" src="/usuarios/imagen" class="img-circle">';
                    }
                    else {
                      echo '<img id="img_perfil" src="/img/img_user.jpg" class="img-circle">';
                    }
                  ?>
                  <!-- <img id="img_perfil" src="/img/sebita.jpg" alt="imagen_perfil" class="img-circle"> -->
                  <h3 id="nombreCuenta">{{$usuario['usuario']->nombre}}</h3>
                  <h4 id="usuarioCuenta">{{'@'.$usuario['usuario']->user_name}}</h4>

                  <!--Icono configurar cuenta -->
                  <a onclick="window.location = window.location.protocol + '//' + window.location.host + '/configCuenta'" href="#"><button id="btnConfigCuenta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/configCuenta'" href="#" class="" type="button"><i class="fa fa-gear fa-2x"></i></button></a>
                  <!-- Icono para cerrar sesión -->
                  <button id="btnCerrarSesion" class="cerrarSesion" type="button"><img src="/img/logos/salida_negrita.png" style="margin-top:-10px; width: 17px;"></button>
                </div>
              <div id="seccionMenu">

                  <!-- <div style="position:relative; margin-left:20px;">
                    <img style="display:inline;" width="30px" src="/img/logos/home_gris.png" alt="">
                    <h4 style="color:#424242;font-size:14px;font-weight:bolder;font-family:Roboto-Condensed;display:inline; position:relative; top:4px;">INICIO</h4>
                  </div> -->

                  <!-- INICIO -->
                  <div class="nivelMenu1">
                      <div id="menu_inicio" class="iconoDireccion1">
                          <!-- <i class="fa fa-angle-right"></i> -->
                      </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/inicio'" href="#"><div class="iconoMenu1">
                          <img id="iconoInicio" src="/img/logos/home_gris.png">
                      </div><div class="nombreMenu1">
                          <span>INICIOz</span>
                      </div></a>
                  </div>

                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_casinos'))
                  <!-- CASINOS -->
                  <div class="nivelMenu1">
                      <div id="menu_casinos" class="iconoDireccion1">
                          <!-- <i class="fa fa-angle-right"></i> -->
                      </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/casinos'" href="#"><div class="iconoMenu1">
                          <img id="iconoCasino" src="/img/logos/casinos_gris.png">
                      </div><div class="nombreMenu1">
                          <span>CASINOS</span>
                      </div></a>
                  </div>
                  @endif
                    <!-- USUARIOS -->
                    @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_usuarios','ver_seccion_roles_permisos','ver_seccion_casinos']))
                    <div class="nivelMenu1">
                        <div id="menu_usuarios" class="iconoDireccion1" data-toggle="collapse" data-target="#menu2_usuarios">
                            <i class="fa fa-angle-right"></i>
                        </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_usuarios'" href="#"><div class="iconoMenu1" style="left:-15px;">
                        </div><div class="nombreMenu1">
                            <span style="left: -40px;">@svg('usuario','iconoUsuario') USUARIOS</span>
                        </div></a>
                    </div>
                    <!-- Submenú 2 | USUARIOS -->
                    <div id="menu2_usuarios" class="collapse collapseNivel1">
                      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_usuarios'))
                        <div id="gestionUsuarios" class="nivelMenu3">
                          <div class="iconoDireccion3">
                            <!-- <i class="fa fa-circle-thin"></i> -->
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/usuarios'" href="#"><div class="nombreMenu3">
                            <span>Gestionar usuarios</span>
                          </div></a>
                        </div>
                        @endif
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_roles_permisos'))
                        <div id="rolesPermisos" class="nivelMenu3">
                          <div class="iconoDireccion3">
                            <!-- <i class="fa fa-circle-thin"></i> -->
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/roles'" href="#"><div class="nombreMenu3">
                            <span>Roles y permisos</span>
                          </div></a>
                        </div>
                        @endif
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_logs_actividades'))
                        <div id="logActividades" class="nivelMenu3">
                          <div class="iconoDireccion3">
                            <!-- <i class="fa fa-circle-thin"></i> -->
                          </div><a class="hoverRuta"  onclick="window.location = window.location.protocol + '//' + window.location.host + '/logActividades'" href="#"><div class="nombreMenu3">
                            <span>Log de actividades</span>
                          </div></a>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- EXPEDIENTES -->
                    @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_expedientes','ver_seccion_resoluciones','ver_seccion_disposiciones']))
                    <div class="nivelMenu1">
                        <div id="menu_expedientes" class="iconoDireccion1" data-toggle="collapse" data-target="#menu2_expedientes">
                            <i class="fa fa-angle-right"></i>
                        </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_expedientes'" href="#"><div class="iconoMenu1">
                            <img id="iconoExpediente" src="/img/logos/expedientes_gris.png">
                        </div><div class="nombreMenu1">
                            <span>EXPEDIENTES</span>
                        </div><div class="barraMenu1">
                        </div></a>
                    </div>

                    <!-- Submenú 2 | EXPEDIENTES -->
                    <div id="menu2_expedientes" class="collapse collapseNivel1">
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_expedientes'))
                        <div id="expedientes" class="nivelMenu3">
                          <div class="iconoDireccion3">
                            <!-- <i class="fa fa-circle-thin"></i> -->
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/expedientes'" href="#"><div class="nombreMenu3">
                            <span>Gestionar expedientes</span>
                          </div></a>
                        </div>
                        @endif
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_resoluciones'))
                        <div id="resoluciones" class="nivelMenu3">
                          <div class="iconoDireccion3">
                            <!-- <i class="fa fa-circle-thin"></i> -->
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/resoluciones'" href="#"><div class="nombreMenu3">
                            <span>Resoluciones</span>
                          </div></a>
                        </div>
                        @endif
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_disposiciones'))
                        <div id="disposiciones" class="nivelMenu3">
                          <div class="iconoDireccion3">
                            <!-- <i class="fa fa-circle-thin"></i> -->
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/disposiciones'" href="#"><div class="nombreMenu3">
                            <span>Disposiciones</span>
                          </div></a>
                        </div>
                        @endif

                    </div>
                    @endif


                  @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,
                                                     ['ver_seccion_maquinas','ver_seccion_progresivos','ver_seccion_islas','ver_seccion_formulas',
                                                      'ver_seccion_juegos','ver_seccion_glisoft','ver_seccion_glihard','ver_seccion_sectores',
                                                      'ver_seccion_importaciones','ver_seccion_relevamientos','ver_seccion_mtm_a_pedido',
                                                      'ver_seccion_producidos','ver_seccion_beneficios','ver_seccion_estadisticas_relevamientos']))
                  <!-- MÁQUINAS -->
                  <div class="nivelMenu1">
                      <div id="menu_maquinas" class="iconoDireccion1" data-toggle="collapse" data-target="#menu2_maquinas">
                          <i class="fa fa-angle-right"></i>
                      </div><a href="#" class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_maquinas'" ><div class="iconoMenu1">
                          <img id="iconoMaquina" src="/img/logos/tragaperras_gris.png">
                      </div><div class="nombreMenu1">
                          <span>MÁQUINAS</span>
                      </div></a>
                  </div>

                  <!-- Submenú 2 | MÁQUINAS -->
                  <div id="menu2_maquinas" class="collapse collapseNivel1">
                      <!-- Estadisticas MTM -->
                      @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_informes_mtm'))
                      <div class="nivelMenu2" >
                          <div id="informes_mtm" class="iconoDireccion2" data-toggle="collapse" data-target="#menu3_informes">
                            <i class="fa fa-angle-right"></i>
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_gestionarMTM'" href="#"><div class="nombreMenu2">
                            <span>INFORMES MTM</span>
                          </div></a>
                      </div>
                      <div id="menu3_informes" class="collapse collapseNivel2">
                        <div id="sub_informe_casino" class="nivelMenu3">
                          <div class="iconoDireccion3">
                            <!-- <i class="fa fa-circle-thin"></i> -->
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeEstadoParque'" href="#"><div class="nombreMenu3">
                            <span> Estado Parque</span>
                          </div></a>
                        </div>
                        <div id="sub_informe_relevamientos" class="nivelMenu3">
                          <div class="iconoDireccion3">
                            <!-- <i class="fa fa-circle-thin"></i> -->
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeContableMTM'" href="#"><div class="nombreMenu3">
                            <span>Informe Relevamiento</span>
                          </div></a>
                        </div>
                        <div id="sub_informe_mtm" class="nivelMenu3">
                          <div class="iconoDireccion3">
                            <!-- <i class="fa fa-circle-thin"></i> -->
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/juegos'" href="#"><div class="nombreMenu3">
                            <span>Informe MTM</span>
                          </div></a>
                        </div>
                      </div>
                      @endif


                      <!-- Gestionar -->
                      @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,
                                                         ['ver_seccion_maquinas','ver_seccion_progresivos','ver_seccion_islas','ver_seccion_formulas',
                                                          'ver_seccion_juegos','ver_seccion_glisoft','ver_seccion_glihard','ver_seccion_sectores']))
                      <div class="nivelMenu2">
                          <div id="menu_gestionar" class="iconoDireccion2" data-toggle="collapse" data-target="#menu3_gestionar">
                            <i class="fa fa-angle-right"></i>
                          </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_gestionarMTM'" href="#"><div class="nombreMenu2">
                            <span>GESTIONAR</span>
                          </div></a>
                      </div>

                      <!-- Submenú 3 | Gestionar -->
                      <div id="menu3_gestionar" class="collapse collapseNivel2">
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_maquinas'))
                          <div id="maquinas" class="nivelMenu3">
                            <div class="iconoDireccion3">
                              <!-- <i class="fa fa-circle-thin"></i> -->
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/maquinas'" href="#"><div class="nombreMenu3">
                              <span>Máquinas</span>
                            </div></a>
                          </div>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_progresivos'))
                          <div id="progresivos" class="nivelMenu3">
                            <div class="iconoDireccion3">
                              <!-- <i class="fa fa-circle-thin"></i> -->
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/progresivos'" href="#"><div class="nombreMenu3">
                              <span>Progresivos</span>
                            </div></a>
                          </div>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_islas'))
                          <div id="islas" class="nivelMenu3">
                            <div class="iconoDireccion3">
                              <!-- <i class="fa fa-circle-thin"></i> -->
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/islas'" href="#"><div class="nombreMenu3">
                              <span>Islas</span>
                            </div></a>
                          </div>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_formulas'))
                          <div id="formulas" class="nivelMenu3">
                            <div class="iconoDireccion3">
                              <!-- <i class="fa fa-circle-thin"></i> -->
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/formulas'" href="#"><div class="nombreMenu3">
                              <span>Fórmulas</span>
                            </div></a>
                          </div>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_juegos'))
                          <div id="juegos" class="nivelMenu3">
                            <div class="iconoDireccion3">
                              <!-- <i class="fa fa-circle-thin"></i> -->
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/juegos'" href="#"><div class="nombreMenu3">
                              <span>Juegos</span>
                            </div></a>
                          </div>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_glisoft'))
                          <div id="certificadoSoft" class="nivelMenu3">
                            <div class="iconoDireccion3">
                              <!-- <i class="fa fa-circle-thin"></i> -->
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/certificadoSoft'" href="#"><div class="nombreMenu3">
                              <span>GLI Software</span>
                            </div></a>
                          </div>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_glihard'))
                          <div id="certificadoHard" class="nivelMenu3">
                            <div class="iconoDireccion3">
                              <!-- <i class="fa fa-circle-thin"></i> -->
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/certificadoHard'" href="#"><div class="nombreMenu3">
                              <span>GLI Hardware</span>
                            </div></a>
                          </div>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_sectores'))
                          <div id="sectores" class="nivelMenu3">
                            <div class="iconoDireccion3">
                              <!-- <i class="fa fa-circle-thin"></i> -->
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/sectores'" href="#"><div class="nombreMenu3">
                              <span>Sectores</span>
                            </div></a>
                          </div>
                          @endif
                      </div>
                      @endif

                      <!-- Procedimientos -->
                      @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,
                                                         ['ver_seccion_importaciones','ver_seccion_relevamientos','ver_seccion_mtm_a_pedido',
                                                          'ver_seccion_producidos','ver_seccion_beneficios','ver_seccion_estadisticas_relevamientos']))
                      <div class="nivelMenu2">
                        <div id="menu_procedimientos" class="iconoDireccion2" data-toggle="collapse" data-target="#menu3_procedimientos">
                          <i class="fa fa-angle-right"></i>
                        </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_procedimientos'" href="#"><div class="nombreMenu2">
                          <span>PROCEDIMIENTOS</span>
                        </div></a>
                      </div>


                      <!-- Submenú 3 | Procedimientos -->
                      <div id="menu3_procedimientos" class="collapse collapseNivel2">

                          <!-- CONTADORES -->
                          @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,
                                                             ['ver_seccion_importaciones','ver_seccion_relevamientos','ver_seccion_mtm_a_pedido',
                                                              'ver_seccion_producidos','ver_seccion_beneficios','ver_seccion_estadisticas_relevamientos']))
                          <div class="nivelMenu3">
                            <div id="menu_contadores" class="iconoDireccion3" data-toggle="collapse" data-target="#menu4_contadores">
                              <i class="fa fa-angle-right"></i>
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_contadores'" href="#"><div class="nombreMenu2">
                              <span>CONTADORES</span>
                            </div></a>
                          </div>


                          <!-- Submenú 4 | Contadores -->
                          <div id="menu4_contadores" class="collapse collapseNivel3" >
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_importaciones'))
                              <div id="menu_importaciones" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/importaciones'" href="#"><div class="nombreMenu4">
                                  <span>Importaciones</span>
                                </div></a>
                              </div>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_relevamientos'))
                              <div id="menu_relevamientos" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta"  onclick="window.location = window.location.protocol + '//' + window.location.host + '/relevamientos'" href="#"><div class="nombreMenu4">
                                  <span>Relevamientos</span>
                                </div></a>
                              </div>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_mtm_a_pedido'))
                              <div id="menu_MTM_a_pedir" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/mtm_a_pedido'" href="#"><div class="nombreMenu4">
                                  <span>MTM a pedido</span>
                                </div></a>
                              </div>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_producidos'))
                              <div id="menu_producidos" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/producidos'" href="#"><div class="nombreMenu4">
                                  <span>Producidos</span>
                                </div></a>
                              </div>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_beneficios'))
                              <div id="menu_beneficios" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/beneficios'" href="#"><div class="nombreMenu4">
                                  <span>Beneficios</span>
                                </div></a>
                              </div>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_estadisticas_relevamientos'))
                              <div id="menu_estadisticas" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/estadisticas_relevamientos'" href="#"><div class="nombreMenu4">
                                  <span>Estadísticas</span>
                                </div></a>
                              </div>
                              @endif
                          </div>
                          @endif

                          <!-- LAYOUT -->
                          @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_layout_parcial' , 'ver_planilla_layout_total']))
                          <div class="nivelMenu3">
                            <div id="menu_layout" class="iconoDireccion3" data-toggle="collapse" data-target="#menu4_layout">
                              <i class="fa fa-angle-right"></i>
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_layout'" href="#"><div class="nombreMenu2">
                              <span>LAYOUT</span>
                            </div></a>
                          </div>

                          <!-- Submenú 4 | Layout -->
                          <div id="menu4_layout" class="collapse collapseNivel3" >
                            @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_planilla_layout_total'))
                              <div id="menu_layout_total" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/layout_total'" href="#"><div class="nombreMenu4">
                                  <span>Layout Total</span>
                                </div></a>
                              </div>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_planilla_layout_parcial'))
                              <div id="menu_layout_parcial" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/layout_parcial'" href="#"><div class="nombreMenu4">
                                  <span>Layout Parcial</span>
                                </div></a>
                              </div>
                              @endif
                          </div>
                          @endif

                          <!-- PRUEBAS -->
                          <div class="nivelMenu3">
                            <div id="menu_prueba" class="iconoDireccion3" data-toggle="collapse" data-target="#menu4_prueba">
                              <i class="fa fa-angle-right"></i>
                            </div><a class="hoverRuta"  href="#"><div class="nombreMenu2">
                              <span>PRUEBA</span>
                            </div></a>
                          </div>

                          <!-- Submenú 4 | Layout -->
                          <div id="menu4_prueba" class="collapse collapseNivel3" >
                              <div id="menu_prueba_juegos" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/prueba_juegos'" href="#"><div class="nombreMenu4">
                                  <span>Juegos</span>
                                </div></a>
                              </div>
                              <div id="menu_prueba_progresivos" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta"  href="prueba_progresivos"><div class="nombreMenu4">
                                  <span>Progresivos</span>
                                </div></a>
                              </div>
                          </div>

                          <!-- PROGRESIVOS -->
                          <!-- <div class="nivelMenu3">
                            <div id="menu_progresivos" class="iconoDireccion3" data-toggle="collapse" data-target="#menu4_progresivos">
                            </div><a class="hoverRuta" href="http://localhost:8000/relevamientos_progresivos"><div class="nombreMenu2">
                              <span>PROGRESIVOS</span>
                            </div></a>
                          </div> -->

                          <!-- MOVIMIENTOS -->

                          <div class="nivelMenu3">
                            <div id="menu_movimientos" class="iconoDireccion3" data-toggle="collapse" data-target="#menu4_movimientos">
                              <i class="fa fa-angle-right"></i>
                            </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_movimientos'" href="#"><div class="nombreMenu2">
                              <span>MOVIMIENTOS</span>
                            </div></a>
                          </div>

                          <!-- Submenú 4 | Movimientos -->

                          <div id="menu4_movimientos" class="collapse collapseNivel3" >
                            @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_gestionar_movimientos'))
                              <div id="gestion_movimientos" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/movimientos'" href="#"><div class="nombreMenu4">
                                  <span>Asignación de Movimientos a Relevar</span>
                                </div></a>
                              </div>
                              @endif
                            @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_relevamientos_movimientos'))
                              <div id="relevamientos_movimientos" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/relevamientos_movimientos'" href="#"><div class="nombreMenu4">
                                  <span>Relevamientos</span>
                                </div></a>
                              </div>
                              @endif
                            @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_eventualidades'))
                              <div id="eventualidades_movimientos" class="nivelMenu4">
                                <div class="iconoDireccion4">
                                  <!-- <i class="fa fa-circle-thin"></i> -->
                                </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/eventualidades'" href="#"><div class="nombreMenu4">
                                  <span>Intervenciones Técnicas</span>
                                </div></a>
                              </div>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_eventualidades_MTM'))
                                <div id="eventualidades_mtm_movimientos" class="nivelMenu4">
                                  <div class="iconoDireccion4">
                                    <!-- <i class="fa fa-circle-thin"></i> -->
                                  </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/eventualidadesMTM'" href="#"><div class="nombreMenu4">
                                    <span>Intervenciones MTM</span>
                                  </div></a>
                                </div>
                                @endif
                          </div>

                      </div>
                      @endif

                  </div>
                  @endif
                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_beneficios'))
                  <!-- TABLERO CONTROL -->

                  <div class="nivelMenu1">
                      <div id="menu_tablero" class="iconoDireccion1" data-toggle="collapse" data-target="#menu2_tablero">
                          <i class="fa fa-angle-right"></i>
                      </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_tablero'" href="#"><div class="iconoMenu1" style="left:2px;">
                          <img id="iconoTablero" src="/img/logos/tablero_gris.png">
                      </div><div class="nombreMenu1">
                          <span>TABLERO</span>
                      </div></a>
                  </div>

                  <!-- Submenú 2 | EXPEDIENTES -->
                  <div id="menu2_tablero" class="collapse collapseNivel1">
                      <div id="estadisticasGenerales" class="nivelMenu3">
                        <div class="iconoDireccion3">
                          <!-- <i class="fa fa-circle-thin"></i> -->
                        </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/estadisticasGenerales'" href="#"><div class="nombreMenu3">
                          <span>Generales</span>
                        </div></a>
                      </div>
                      <div id="estadisticasPorCasino" class="nivelMenu3">
                        <div class="iconoDireccion3">
                          <!-- <i class="fa fa-circle-thin"></i> -->
                        </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/estadisticasPorCasino'" href="#"><div class="nombreMenu3">
                          <span>Por casino</span>
                        </div></a>
                      </div>
                      <div id="interanuales" class="nivelMenu3">
                        <div class="iconoDireccion3">
                          <!-- <i class="fa fa-circle-thin"></i> -->
                        </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/interanuales'" href="#"><div class="nombreMenu3">
                          <span>Interanuales</span>
                        </div></a>
                      </div>

                  </div> <!-- /#menu2_tablero -->
                  
                  <!-- MENÚ INFORMES -->
                  <div class="nivelMenu1">
                      <div id="menu_informes" class="iconoDireccion1" data-toggle="collapse" data-target="#menu2_informes">
                          <i class="fa fa-angle-right"></i>
                      </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/menu_informes'" href="#"><div class="iconoMenu1" style="left:-1px;">
                          <img id="iconoInformes" src="/img/logos/informes_gris.png">
                      </div><div class="nombreMenu1">
                          <span>INFORMES</span>
                      </div></a>
                  </div>

                  <!-- Submenú 2 | INFORMES -->
                  <div id="menu2_informes" class="collapse collapseNivel1">
                      <div id="informesMTM" class="nivelMenu3">
                        <div class="iconoDireccion3">
                          <!-- <i class="fa fa-circle-thin"></i> -->
                        </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informesMTM'" href="#"><div class="nombreMenu3">
                          <span>MTM</span>
                        </div></a>
                      </div>
                      <div id="informesBingo" class="nivelMenu3">
                        <div class="iconoDireccion3">
                          <!-- <i class="fa fa-circle-thin"></i> -->
                        </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informesBingo'" href="#"><div class="nombreMenu3">
                          <span>Bingos</span>
                        </div></a>
                      </div>
                      <div id="informesJuegos" class="nivelMenu3">
                        <div class="iconoDireccion3">
                          <!-- <i class="fa fa-circle-thin"></i> -->
                        </div><a class="hoverRuta" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informesJuegos'" href="#"><div class="nombreMenu3">
                          <span>Juegos</span>
                        </div></a>
                      </div>

                  </div> <!-- /#menu2_informe -->
                  @endif

              </div>
            </div>

            <div id="vista">
              <!-- <div class="container"> -->
                @section('contenidoVista')
                @show
              <!-- </div> -->
            </div>

            <!-- NOTIFICACIÓN DE ÉXITO -->
              <!--  (*) Para que la animación solo MUESTRE (fije) el mensaje, se agrega la clase 'fijarMensaje' a #mensajeExito-->
              <!--  (*) Para que la animación MUESTRE Y OCULTE el mensaje, se quita la clase 'fijarMensaje' a #mensajeExito-->
              <!-- (**) si se quiere mostrar los botones de ACEPTAR o SALIR, se agrega la clase 'mostrarBotones' a #mensajeExito -->
              <!-- (**) para no mostrarlos, se quita la clase 'mostrarBotones' a #mensajeExito -->

            <div id="mensajeExito" class="" hidden>
                <div class="cabeceraMensaje"></div>
                <div class="iconoMensaje">
                  <img src="/img/logos/check.png" alt="imagen_check" >
                </div>
                <div class="textoMensaje" >
                    <h3>ÉXITO</h3>
                    <p>El CASINO fue creado con éxito.</p>
                </div>
                <div class="botonesMensaje">
                    <button class="btn btn-success confirmar" type="button" name="button">ACEPTAR</button>
                    <button class="btn btn-default salir" type="button" name="button">SALIR</button>
                </div>
            </div>


            <!-- Modal Error -->
            <div id="mensajeError" hidden>
                <div class="cabeceraMensaje"></div>
                <div class="iconoMensaje">
                  <img src="/img/logos/error.png" alt="imagen_error" >
                </div>
                <div class="textoMensaje" >
                    <h3>ERROR</h3>
                    <p>No es posible realizar la acción</p>
                </div>

            </div>

            <!-- animacion -->
            <!-- <div id="animation_container" style="background-color:rgba(255, 255, 255, 1.00); width:400px; height:300px">
              <canvas id="canvas" width="30%" height="" style="position: absolute; display: block; background-color:rgba(255, 255, 255, 1.00);"></canvas>
              <div id="dom_overlay_container" style="pointer-events:none; overflow:hidden; width:400px; height:300px; position: absolute; left: 0px; top: 0px; display: block;">
              </div>
            </div> -->

        </div> <!-- /#sub-contenedor -->
      </div> <!-- /#contenedor -->

<!--  </div>  /.container -->

  <!-- jQuery -->
  <script src="/js/jquery.js"></script>

  <!-- Bootstrap Core JavaScript -->
  <script src="/js/bootstrap.js"></script>

  <!-- JavaScript personalizado -->
  <script src="/js/barraNavegacion.js"></script>

  <!-- JavaScript ajaxError -->
  <script src="/js/ajaxError.js"></script>

  <!-- JavaScript de tarjetas animadas -->
  <script src="/js/anime.min.js"></script>
  <script src="/js/main.js"></script>

  <!-- TableSorter -->
  <script type="text/javascript" src="/js/jquery.tablesorter.js"></script>
  <script type="text/javascript" src="/js/iconosTableSorter.js"></script>

  <!-- Collapse JS | Controla el menú -->
  <script type="text/javascript" src="/js/collapse.js"></script>

  <!-- librerias de animate -->
  <script src="https://code.createjs.com/createjs-2015.11.26.min.js"></script>
  <script src="/js/Animacion_logo2.js?1517927954849"></script>

  @section('scripts')
  @show

</body>

</html>
