 <?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;

$usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
$id_usuario = $usuario['usuario']->id_usuario;
$cas = $usuario['usuario']->casinos;

//@HACK temporal @TODO remover cuand se pase a todos los casinos
$tiene_santafe = UsuarioController::getInstancia()
->usuarioTieneCasinoCorrespondiente($id_usuario,2);
$ver_prueba_progresivo = $usuario['usuario']->es_superusuario;
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="_token" content="{!! csrf_token() !!}"/>

    <link rel="icon" type="image/png" sizes="32x32" href="/img/logos/favicon.png">
    <title>CAS - Lotería de Santa Fe</title>

    <!-- Bootstrap Core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/bootstrap-col-xl.css" rel="stylesheet">

    <link href="/css/estilosBotones.css" rel="stylesheet">
    <link href="/css/estilosModal.css" rel="stylesheet">
    <link href="/css/estilosFileInput.css" rel="stylesheet">
    <link href="/css/estilosPopUp.css" rel="stylesheet">
    <link href="/css/table-fixed.css" rel="stylesheet">
    <link href="/css/importacionFuentes.css" rel="stylesheet">
    <link href="/css/tarjetasMenues.css" rel="stylesheet">
    <link href="/css/flaticon.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="/css/style.css">

    <link rel="stylesheet" type="text/css" href="/css/component.css" />

    <!-- Custom Fonts -->
    <!-- <link rel="stylesheet" type="text/css" href="/font-awesome/css/font-awesome.min.css" > -->

    <!-- Animaciones de los LINKS en MENU -->
    <link rel="stylesheet" href="/css/animacionesMenu.css">

    <!-- Animaciones de alta -->
    <link rel="stylesheet" href="/css/animacionesAlta.css">

    <!-- Animación de carga de datos -->
    <link rel="stylesheet" href="/css/loadingAnimation.css">

    <!-- Mesaje de notificación -->
    <link rel="stylesheet" href="/css/mensajeExito.css">
    <link rel="stylesheet" href="/css/mensajeError.css">

    <!-- Estilos de imagenes en SVG -->
    <link rel="stylesheet" href="/css/estilosSVG.css">
    <link rel="stylesheet" href="/css/estiloDashboard.css">
    <link rel="stylesheet" href="/css/estiloDashboard_xs.css">

    <!-- Custom Fonts -->
    <!-- <link rel="stylesheet" type="text/css" href="/font-awesome/css/font-awesome.min.css"> -->
    <link rel="stylesheet" href="/web-fonts-with-css/css/fontawesome-all.css">

    <!-- Mesaje de notificación -->
    <link rel="stylesheet" href="/css/mensajeExito.css">
    <link rel="stylesheet" href="/css/mensajeError.css">

    <link rel="stylesheet" href="/css/perfect-scrollbar.css">



    @section('estilos')
    @show

  </head>
  <body>

    <!-- Contenedor de toda la página -->
    <div class="contenedor">

        <!-- Barra superior  -->
        <header>
            <nav>@section('headerLogo')
                 @show
              <h2 class="tituloSeccionPantalla"></h2>

              <ul class="nav nav-tabs nav-justified juegosSec" id="juegosSec" style=" width:70%;" hidden="true">
                <li id="b_juego" ><a href="#pant_juegos"  style="font-family:Roboto-condensed;font-size:20px; ">Juegos</a></li>
                <li id="b_sector"><a href="#pant_sectores"  style="font-family:Roboto-condensed;font-size:20px;">Sectores</a></li>
              </ul>
              <ul class="nav nav-tabs nav-justified cierreApertura" id="cierreApertura" style=" width:70%;" hidden="true">
                <li id="b_apertura" ><a href="#pant_aperturas"  style="font-family:Roboto-condensed;font-size:20px; ">Aperturas</a></li>
                <li id="b_cierre"><a href="#pant_cierres"  style="font-family:Roboto-condensed;font-size:20px;">Cierres</a></li>
             </ul>
              <ul class="nav nav-tabs nav-justified informesMes" id="informesMes" style=" width:70%;" hidden="true">
                <li id="gestInformes" ><a href="#gestionInfoMes"  style="font-family:Roboto-condensed;font-size:20px; ">Informes Mensuales</a></li>
                <li id="graficos"><a href="#graficosMes"  style="font-family:Roboto-condensed;font-size:20px;">Gráficos Mensuales</a></li>
              </ul>

              <ul class="nav nav-tabs nav-justified pestCanon" id="pestCanon" style=" width:70%;" hidden="true">
                <li id="canon1" ><a href="#pant_canon_pagos"  style="font-family:Roboto-condensed;font-size:20px; ">Detalles Canon y Pagos</a></li>
                <li id="canon2"><a href="#pant_canon_valores"  style="font-family:Roboto-condensed;font-size:20px;">Actualización Valores</a></li>
              </ul>

             <ul class="nav nav-tabs nav-justified pestImportaciones" id="pestImportaciones" style=" width:70%;" hidden="true">
               <li id="imp_diaria" ><a href="#pest_diaria"  style="font-family:Roboto-condensed;font-size:20px; ">Importaciones Diarias</a></li>
               <li id="imp_mensual"><a href="#pest_mensual"  style="font-family:Roboto-condensed;font-size:20px;">Importaciones Mensuales</a></li>
            </ul>

              <a href="#" id="btn-ayuda"><i class="iconoAyuda glyphicon glyphicon-question-sign" style="padding-top: 12px; padding-left: 10px; !important"></i></a>
              <ul class="opcionesBarraSuperior" style=" width:20%;float:right;">

                  <?php
                    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'));
                  ?>
                  <li class="dropdown" id="marcaLeido" onclick="markNotificationAsRead('{{count($usuario['usuario']->unreadNotifications)}}')" style="right:1%;">
                    <!-- <a href="#" class="iconoBarraSuperior"><i class="fa fa-times"></i></a> -->
                    <!--Icono de notificaciones -->

                  <a href="#" id="notificaciones" style="text-decoration:none;position:relative;top:1px;" class="dropdown-toggle" data-toggle="dropdown" type="button">
                    <i class="far fa-bell fa-2x" style="margin-right:5px;color:#333;"></i>
                    <span class="badge" style="font-size:20px; background:#333333;height:30px;padding-top:5px;position:relative;top:-5px;">{{count($usuario['usuario']->unreadNotifications)}}</span>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-right" style="max-height: 300px; overflow-y:auto; width:350px;">
                    @forelse ($usuario['usuario']->unreadNotifications as $notif)
                    <div style="background: #E6E6E6;">
                        @include('includes.notifications.'.snake_case(class_basename($notif->type)))
                    </div>

                    @empty
                      @forelse($usuario['usuario']->lastNotifications() as $notif)
                        @include('includes.notifications.'.snake_case(class_basename($notif->type)))
                      @empty
                        <a href="#" style="font-size:20px;">No hay nuevas Notificaciones</a>
                      @endforelse
                    @endforelse
                  </ul>

                  </li>
                  <li>
                    <a id="calendario" class="iconoBarraSuperior" onclick="window.location = window.location.protocol + '//' + window.location.host + '/calendario_eventos'" href="#"><i class="far fa-fw fa-calendar-alt fa-2x" style="margin-right:6px; margin-top: 1px; color: black;"></i></a>
                  </li>
                  <li>
                    <a href="#" class="etiquetaLogoSalida"><img src="/img/logos/salida_negrita.png" style="margin-top:4px; margin-right: 32px; width: 17px;"></a>
                  </li>
              </ul>
            </nav>
        </header>

        <!-- Menú lateral -->
        <aside>
            <div class="contenedorLogo">
                <a onclick="window.location = window.location.protocol + '//' + window.location.host + '/inicio'"  href="#">
                  <img src="/img/logos/logo_brand_blanco.png" alt="" width="48%">
                </a>
            </div>
            <!-- <div class="scrollMenu"> -->


              <div class="contenedorMenu">
                <div class="contenedorUsuario">
                  <?php
                    $casinos = $usuario['usuario']->casinos;
                    if(count($casinos)!=0){
                      $cas = $casinos[0]->id_casino;
                      if($cas == 1){
                        echo '<div class="fondoMEL"></div>';
                      }
                      else if($cas == 2){
                        echo '<div class="fondoSFE"></div>';
                      }
                      else if($cas == 3){
                        echo '<div class="fondoROS"></div>';
                      }
                    }
                  ?>
                    <div class="infoUsuario">
                      <a onclick="window.location = window.location.protocol + '//' + window.location.host + '/configCuenta'" href="#">
                        <?php
                          $tieneImagen = UsuarioController::getInstancia()->tieneImagen();
                          if($tieneImagen) {
                            echo '<img id="img_perfilBarra" src="/usuarios/imagen" class="img-circle">';
                          }
                          else {
                            echo '<img id="img_perfilBarra" src="/img/img_user.jpg" class="img-circle">';
                          }
                        ?>
                        <i id="iconConfig" class="fa fa-cog"></i>
                      </a>
                        <h3>{{$usuario['usuario']->nombre}}</h3>
                        <div class="nombreUsuario"><h4>{{'@'.$usuario['usuario']->user_name}}</h4></div>

                    </div>
                </div>

                <div class="opcionesMenu">

                    <!-- PRIMER NIVEL -->
                    <ul>
                        <div class="separadoresMenu">MENÚ</div>
                        <li>
                            <div id="opcInicio" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/inicio'" style="cursor: pointer;">
                                <span class="icono" style="padding-bottom: 50px;">
                                  @svg('home','iconoHome')
                                </span>
                                <span>Inicio</span>
                            </div>
                        </li>
                        <!-- CASINOS -->
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_casinos'))
                        <li>
                            <div id="opcCasino" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/casinos'" href="#" style="cursor: pointer;">
                                <span class="icono" style="padding-bottom: 56px;">
                                  @svg('casinos','iconoCasinos')
                                </span>
                                <span>Casinos</span>
                            </div>
                        </li>
                        @endif

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_usuarios','ver_seccion_roles_permisos','ver_seccion_casinos']))
                        <div class="separadoresMenu">GESTIÓN</div>
                        <li>
                            <div id="barraUsuarios" class="opcionesHover" data-target="#usuarios" data-toggle="collapse">
                                <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 50px;">
                                  @svg('usuario','iconoUsuarios')
                                </span>
                                <span>Usuarios</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="usuarios">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_usuarios'))
                              <li>
                                <div id="opcGestionarUsuarios" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/usuarios'" href="#" style="cursor: pointer;">
                                  <span>Gestionar usuarios</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_roles_permisos'))
                              <li>
                                <div id="opcRolesPermisos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/roles'" href="#" style="cursor: pointer;">
                                  <span>Roles y permisos</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_logs_actividades'))
                              <li>
                                <div id="opcLogActividades" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/logActividades'" href="#" style="cursor: pointer;">
                                  <span>Log de actividades</span>
                                </div>
                              </li>
                              @endif
                            </ul>


                        </li>
                        @endif
                        <!-- EXPEDIENTES -->
                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_expedientes','ver_seccion_resoluciones','ver_seccion_disposiciones']))
                        <li>
                            <div id="barraExpedientes" class="opcionesHover" data-target="#expedientes" data-toggle="collapse" href="#">
                                <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 54px;">
                                  @svg('expedientes','iconoExpedientes')
                                </span>
                                <span>Expedientes</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="expedientes">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_expedientes'))
                              <li>
                                <div id="opcGestionarExpedientes" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/expedientes'" href="#" style="cursor: pointer;">
                                  <span>Gestionar expedientes</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_resoluciones'))
                              <li>
                                <div id="opcResoluciones" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/resoluciones'" href="#" style="cursor: pointer;">
                                  <span>Resoluciones</span>
                                </div>
                              </li>
                              <li>
                                <div id="opcNotas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/notas'" href="#" style="cursor: pointer;">
                                  <span>Notas</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_disposiciones'))
                              <li>
                                <div id="opcDisposiciones" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/disposiciones'" href="#" style="cursor: pointer;">
                                  <span>Disposiciones</span>
                                </div>
                              </li>
                              @endif
                            </ul>
                        </li>
                        @endif

                        <!-- GESTIÓN MAQUINAS -->
                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_maquinas','ver_seccion_progresivos','ver_seccion_islas',
                                                                                                            'ver_seccion_formulas','ver_seccion_juegos','ver_seccion_glisoft',
                                                                                                            'ver_seccion_glihard','ver_seccion_sectores']))
                        <li>
                            <div class="opcionesHover" data-target="#gestionarMTM" data-toggle="collapse">
                              <span class="flechita">
                                <i class="fa fa-angle-right"></i>
                              </span>
                              <span class="icono" style="padding-bottom: 56px;">
                                @svg('maquinas','iconoMaquinas')
                              </span>
                                <span>Maquinas</span>
                            </div>
                              <!-- CUARTO NIVEL -->
                              <ul class="subMenu2 collapse" id="gestionarMTM">
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_maquinas'))
                                  <li>
                                    <div id="opcGestionarMaquinas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/maquinas'" href="#" style="cursor: pointer;">
                                      <span>Máquinas</span>
                                    </div>
                                  </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_progresivos'))
                                  <li>
                                    <div id="opcProgresivos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/progresivos'" href="#" style="cursor: pointer;">
                                      <span>Progresivos</span>
                                    </div>
                                  </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_islas'))
                                  <li>
                                    <div id="opcIslas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/islas'" href="#" style="cursor: pointer;">
                                      <span>Islas</span>
                                    </div>
                                  </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_formulas'))
                                  <li>
                                    <div id="opcFormulas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/formulas'" href="#" style="cursor: pointer;">
                                      <span>Fórmulas</span>
                                    </div>
                                  </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_juegos'))
                                  <li>
                                    <div id="opcJuegos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/juegos'" href="#" style="cursor: pointer;">
                                      <span>Juegos</span>
                                    </div>
                                  </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_juegos'))
                                  <li>
                                    <div id="opcPackJuegos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/packJuegos'" href="#" style="cursor: pointer;">
                                      <span>Paquete-Juegos</span>
                                    </div>
                                  </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_glisoft'))
                                  <li>
                                    <div id="opcGliSoft" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/certificadoSoft'" href="#" style="cursor: pointer;">
                                      <span>GLI Software</span>
                                    </div>
                                  </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_glihard'))
                                  <li>
                                    <div id="opcGliHard" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/certificadoHard'" href="#" style="cursor: pointer;">
                                      <span>GLI Hardware</span>
                                    </div>
                                  </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_sectores'))
                                  <li>
                                    <div id="opcSectores" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/sectores'" href="#" style="cursor: pointer;">
                                      <span>Sectores</span>
                                    </div>
                                  </li>
                                  @endif
                              </ul>
                        </li>
                        @endif

                        <!-- FIN GESTIÓN MAQUINAS -->

                        <!-- GESTIÓN BINGO -->
                        @if ($tiene_santafe)
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'bingo_ver_gestion'))
                        <!-- <li>
                            <div id="barraGestionBingo" class="opcionesHover" data-target="#gestionBingo" data-toggle="collapse">
                                <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 50px;"> -->
                                <!--  (falta arroba ) svg('bingos','iconoTableroControl') -->
                                <!-- </span>
                                <span>Bingo</span>
                            </div> -->

                            <!-- SEGUNDO NIVEL -->
                            <!-- <ul class="subMenu1 collapse" id="gestionBingo">

                              <li>
                                <div id="opcGestionarBingo" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/bingo/gestionBingo'" href="#" style="cursor: pointer;">
                                  <span>Gestion premios y canon</span>
                                </div>
                              </li>

                            </ul>
                        </li> -->
                        @endif
                        @endif
                        <!-- FIN GESTIÓN BINGO -->

                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_maquinas','ver_seccion_progresivos','ver_seccion_islas',
                                                                                                            'ver_seccion_formulas','ver_seccion_juegos','ver_seccion_glisoft',
                                                                                                            'ver_seccion_glihard','ver_seccion_sectores','ver_seccion_importaciones',
                                                                                                            'ver_seccion_relevamientos','ver_seccion_mtm_a_pedido','ver_seccion_producidos',
                                                                                                            'ver_seccion_beneficios','ver_planilla_layout_total','ver_planilla_layout_parcial',
                                                                                                            'ver_seccion_gestionar_movimientos','ver_seccion_relevamientos_movimientos',
                                                                                                            'ver_seccion_eventualidades','ver_seccion_eventualidades_MTM',
                                                                                                            'ver_seccion_estestadoparque','ver_seccion_estestadorelevamientos',
                                                                                                            'ver_seccion_informecontable']))
                        <div class="separadoresMenu">AUDITORÍA</div>
                        <li>
                            <div id="barraMaquinas" class="opcionesHover" data-target="#maquinas" data-toggle="collapse" href="#">
                                <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 56px;">
                                  @svg('maquinas','iconoMaquinas')
                                </span>
                                <span>Máquinas</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="maquinas">
                              @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_estestadoparque','ver_seccion_estestadorelevamientos',
                              'ver_seccion_informecontable']))
                              <li>
                                <div class="opcionesHover" data-target="#informesMTM" data-toggle="collapse" href="#">
                                  <span class="flechita">
                                    <i class="fa fa-angle-right"></i>
                                  </span>
                                  <span>Informes MTM</span>
                                </div>

                                <!-- TERCER NIVEL -->
                                <ul class="subMenu2 collapse" id="informesMTM">
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_estestadoparque'))
                                    <li>
                                      <div id="opcInformeEstadoParque" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeEstadoParque'" href="#" style="cursor: pointer;">
                                        <span>Casino</span>
                                      </div>
                                    </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_estestadorelevamientos'))
                                    <li>
                                      <div id="opcEstadisticas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/estadisticas_relevamientos'" href="#" style="cursor: pointer;">                                        <span>Relevamiento</span>
                                      </div>
                                    </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_informecontable'))
                                    <li>
                                      <div id="opcInformesContableMTM" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeContableMTM'" href="#" style="cursor: pointer;">
                                        <span>MTM</span>
                                      </div>
                                    </li>
                                  @endif
                                  @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_informecontable'))
                                    <li>
                                      <div id="opcInformesNoToma" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/estadisticas_no_toma'" href="#" style="cursor: pointer;">
                                        <span>No toma</span>
                                      </div>
                                    </li>
                                  @endif
                                </ul>
                              </li>
                              @endif



                              <!-- Procedimientos -->
                              @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_importaciones','ver_seccion_relevamientos',
                                                                                                                  'ver_seccion_relevamientos_progresivos','ver_seccion_mtm_a_pedido','ver_seccion_producidos',
                                                                                                                  'ver_seccion_beneficios','ver_planilla_layout_total',
                                                                                                                  'ver_planilla_layout_parcial','ver_seccion_prueba_juegos',
                                                                                                                  'ver_seccion_prueba_progresivos','ver_seccion_gestionar_movimientos',
                                                                                                                  'ver_seccion_relevamientos_movimientos','ver_seccion_eventualidades',
                                                                                                                  'ver_seccion_eventualidades_MTM']))
                              <li>
                                <div id="gestionarProcedimientos" class="opcionesHover" data-target="#procedimientos" data-toggle="collapse" href="#">
                                  <span class="flechita">
                                    <i class="fa fa-angle-right"></i>
                                  </span>
                                  <span>Procedimientos</span>
                                </div>
                                <!-- TERCER NIVEL -->
                                @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_importaciones','ver_seccion_relevamientos','ver_seccion_relevamientos_progresivos',
                                                                                                                    'ver_seccion_mtm_a_pedido','ver_seccion_producidos',
                                                                                                                    'ver_seccion_beneficios']))
                               <ul class="subMenu2 collapse" id="procedimientos">
                                   <li>
                                     <div id="gestionarContadores" class="opcionesHover" data-target="#contadores" data-toggle="collapse" href="#">
                                       <span class="flechita">
                                         <i class="fa fa-angle-right"></i>
                                       </span>
                                       <span>Contadores</span>
                                     </div>

                                     <!-- CUARTO NIVEL -->
                                     <ul class="subMenu3 collapse" id="contadores">
                                       @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_importaciones'))
                                         <li>
                                           <div id="opcImportaciones" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/importaciones'" href="#" style="cursor: pointer;">
                                             <span>Importaciones</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_relevamientos'))
                                         <li>
                                           <div id="opcRelevamientos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/relevamientos'" href="#" style="cursor: pointer;">
                                             <span>Relevamientos</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_relevamientos_progresivos') && $tiene_santafe)
                                         <li>
                                           <div id="opcRelevamientosProgresivos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/relevamientosProgresivo'" href="#" style="cursor: pointer;">
                                             <span>Relev. Progresivos</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_mtm_a_pedido'))
                                         <li>
                                           <div id="opcMTMaPedido" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/mtm_a_pedido'" href="#" style="cursor: pointer;">
                                             <span>MTM a pedido</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_producidos'))
                                         <li>
                                           <div id="opcProducidos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/producidos'" href="#" style="cursor: pointer;">
                                             <span>Producidos</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_beneficios'))
                                         <li>
                                           <div id="opcBeneficios" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/beneficios'" href="#" style="cursor: pointer;">
                                             <span>Beneficios</span>
                                           </div>
                                         </li>
                                         @endif
                                     </ul>
                                     @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_planilla_layout_total','ver_planilla_layout_parcial']))
                                     <div id="gestionarLayout" class="opcionesHover" data-target="#layout" data-toggle="collapse" href="#">
                                       <span class="flechita">
                                         <i class="fa fa-angle-right"></i>
                                       </span>
                                       <span>Layout</span>
                                     </div>

                                     <!-- CUARTO NIVEL -->
                                     <ul class="subMenu3 collapse" id="layout">
                                       @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_planilla_layout_total'))
                                         <li>
                                           <div id="opcLayoutTotal" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/layout_total'" href="#" style="cursor: pointer;">
                                             <span>Total</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_planilla_layout_parcial'))
                                         <li>
                                           <div id="opcLayoutParcial" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/layout_parcial'" href="#" style="cursor: pointer;">
                                             <span>Parcial</span>
                                           </div>
                                         </li>
                                         @endif
                                     </ul>
                                     @endif
                                     @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_prueba_juegos','ver_seccion_prueba_progresivos']) && $ver_prueba_progresivo)
                                     <div id="gestionarPruebas" class="opcionesHover" data-target="#prueba" data-toggle="collapse" href="#">
                                       <span class="flechita">
                                         <i class="fa fa-angle-right"></i>
                                       </span>
                                       <span>Pruebas</span>
                                     </div>
                                     <!-- CUARTO NIVEL -->
                                     <ul class="subMenu3 collapse" id="prueba">
                                       @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_prueba_juegos'))
                                         <li>
                                           <div id="opcPruebaJuego" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/prueba_juegos'" href="#" style="cursor: pointer;">
                                             <span>Juegos</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_prueba_progresivos'))
                                         <li>
                                           <div id="opcPruebaProgresivo" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/prueba_progresivos'" href="#" style="cursor: pointer;">
                                             <span>Progresivos</span>
                                           </div>
                                         </li>
                                         @endif
                                     </ul>
                                     @endif
                                     @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['ver_seccion_gestionar_movimientos','ver_seccion_relevamientos_movimientos',
                                                                                                                         'ver_seccion_eventualidades','ver_seccion_eventualidades_MTM']))
                                     <!-- MOVIMIENTOS -->
                                     <div id="gestionarMovimientos" class="opcionesHover" data-target="#movimientos" data-toggle="collapse" href="#">
                                       <span class="flechita">
                                         <i class="fa fa-angle-right"></i>
                                       </span>
                                       <span>Movimientos</span>
                                     </div>

                                     <!-- CUARTO NIVEL -->
                                     <ul class="subMenu3 collapse" id="movimientos">
                                       @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_gestionar_movimientos'))
                                         <li>
                                           <div id="opcAsignacion" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/movimientos'" href="#" style="cursor: pointer;">
                                             <span>Asignación</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_relevamientos_movimientos'))
                                         <li>
                                           <div id="opcRelevamientosMovimientos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/relevamientos_movimientos'" href="#" style="cursor: pointer;">
                                             <span>Relevamientos</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_eventualidades'))
                                         <li>
                                           <div id="opcIntervencionesTecnicas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/eventualidades'" href="#" style="cursor: pointer;">
                                             <span>Intervenciones Téc.</span>
                                           </div>
                                         </li>
                                         @endif
                                         @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_eventualidades_MTM'))
                                         <li>
                                           <div id="opcIntervencionesMTM" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/eventualidadesMTM'" href="#" style="cursor: pointer;">
                                             <span>Intervenciones MTM</span>
                                           </div>
                                         </li>
                                         @endif
                                     </ul>
                                     @endif
                                   </li>
                               </ul>
                               @endif
                              </li>
                              @endif
                            </ul>
                        </li>
                        @endif

                        <li>
                            <div id="barraMesas" class="opcionesHover" data-target="#mesasPanio" data-toggle="collapse" href="#">
                                <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 54px;">
                                  @svg('mesa','iconoMesa')
                                </span>
                                <span>Mesas de paño</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="mesasPanio">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_gestionar_juegos_mesas'))
                              <li>
                                <div id="opcJuegos" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/juegosMesa'" href="#" style="cursor: pointer;">
                                  <span>Gestionar Juegos y Sectores</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_gestionar_mesas'))
                              <li>
                                <div id="opcGestionarMesas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/mesas'" href="#" style="cursor: pointer;">
                                  <span>Gestionar Mesas</span>
                                </div>
                              </li>
                              @endif
                              <li>
                                <div id="opcAperturas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/aperturas'" href="#" style="cursor: pointer;">
                                  <span>Cierres y Aperturas</span>
                                </div>
                              </li>
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_ver_seccion_apuestas'))
                              <li>
                                <div id="opcApuestas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/apuestas'" href="#" style="cursor: pointer;">
                                  <span>Apuestas Mínimas</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_ver_seccion_informe_fiscalizadores'))
                              <li>
                                <div id="opcInformesFisca" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeDiarioBasico'" href="#" style="cursor: pointer;">
                                  <span>Informes Diarios </span>
                                </div>
                              </li>
                              @endif

                            </ul>
                        </li>

@if ($tiene_santafe)
                        <li>
                            <div id="barraBingo" class="opcionesHover" data-target="#bingoMenu" data-toggle="collapse" href="#">
                              <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 50px;">
                                  @svg('bingos','iconoTableroControl')
                                </span>
                                <span>Bingo</span>
                            </div>
                            <ul class="subMenu1 collapse" id="bingoMenu">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_sesion_relevamientos'))
                              <li>
                                <div id="opcBingo" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/bingo'" href="#" style="cursor: pointer;">
                                  <span>Sesiones y Relevamiento</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'importar_bingo'))
                              <li>
                                <div id="opcImportarBingo" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/bingo/importarRelevamiento'" href="#" style="cursor: pointer;">
                                  <span>Importar Relevamiento</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'reporte_estado_bingo'))
                              <li>
                                <div id="opcReporteEstadoBingo" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/bingo/reportesEstado'" href="#" style="cursor: pointer;">
                                  <span>Reportes de Estados</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'reporte_diferencia_bingo'))
                              <li>
                                <div id="opcReporteEstadoDiferenciaBingo" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/bingo/reportesDiferencia'" href="#" style="cursor: pointer;">
                                  <span>Reportes de Diferencia</span>
                                </div>
                              </li>
                              @endif
                            </ul>
                          </li>
@endif




                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_ver_seccion_importaciones'))
                        <div class="separadoresMenu" style="font-size:11px !important">GESTIÓN CONTABLE MESAS</div>
                          <li>
                              <div id="barraImportaciones" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/importacionDiaria'" href="#" style="cursor: pointer;">

                                  <span class="icono" style="padding-bottom: 56px;">
                                    @svg('expedientes','iconoExpedientes')
                                  </span>
                                  <span>Importaciones</span>

                              </div>
                          </li>
                        @endif
                        @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_ver_seccion_informes'))

                          <li>
                              <div id="barraInformesMesas" class="opcionesHover" data-target="#informes2" data-toggle="collapse" href="#">
                                <span class="flechita">
                                    <i class="fa fa-angle-right"></i>
                                  </span>
                                  <span class="icono" style="padding-bottom: 54px;">
                                    @svg('informes','iconoInformes')
                                  </span>
                                  <span>Informes </span>
                              </div>

                              <!-- SEGUNDO NIVEL -->
                              <ul class="subMenu1 collapse" id="informes2">
                                <li>
                                  <div id="opcInfoDiario" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeDiario'" href="#" style="cursor: pointer;">
                                    <span>Diario</span>
                                  </div>
                                </li>
                                <li>
                                  <div id="opcInfoMensual" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeMensual'" href="#" style="cursor: pointer;">
                                    <span>Mensual</span>
                                  </div>
                                </li>

                                <li>
                                  <div id="opcInfoInteranuales" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informeAnual'" href="#" style="cursor: pointer;">
                                    <span>Anuales</span>
                                  </div>
                                </li>
                              </ul>
                          </li>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_ver_seccion_canon'))
                          <li>
                            <div id="barraCanon" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/canon'" href="#" style="cursor: pointer;">

                                <span class="icono" style="padding-bottom: 56px;">
                                  @svg('bolsa_pesos','iconoCanon')
                                </span>
                                <span>Canon</span>

                            </div>
                          </li>
                          @endif
                          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'m_ver_seccion_imagenes_bunker'))
                          <li>
                            <div id="barraImagenes" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/solicitudImagenes'" href="#" style="cursor: pointer; padding-left:15px; ">
                              <span class="icono" >
                                @svg('camara','iconoCamara')
                              </span>
                              <span>Imágenes Bunker</span>
                            </div>
                          </li>
                          @endif

                      <div class="separadoresMenu">CONTROL AMBIENTAL</div>
                      <li>
                        <div id="barraRelevamientosAmbiental" class="opcionesHover" data-target="#relevamientoAmbiental" data-toggle="collapse" href="#">
                          <span class="flechita">
                              <i class="fa fa-angle-right"></i>
                            </span>
                            <span class="icono" style="padding-bottom: 50px;">
                              @svg('tablero_control','iconoTableroControl')
                            </span>
                            <span>Relevamientos</span>
                        </div>

                        <!-- SEGUNDO NIVEL -->
                        <ul class="subMenu1 collapse" id="relevamientoAmbiental">
                          <li>
                            <div id="opcAmbientalmaquinas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/relevamientosControlAmbiental'" style="cursor: pointer;">
                              <span>Máquinas</span>
                            </div>
                          </li>

                          <li>
                            <div id="opcAmbientalMesas" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/relevamientosControlAmbientalMesas'" href="#" style="cursor: pointer;">
                              <span>Mesas de paño</span>
                            </div>
                          </li>
                        </ul>

                      </li>


                      <li>
                        <div id="barraInformesAmbiental" class="opcionesHover" data-target="#informeAmbiental" data-toggle="collapse" href="#">
                          <span class="flechita">
                              <i class="fa fa-angle-right"></i>
                            </span>
                            <span class="icono" style="padding-bottom: 50px;">
                              @svg('tablero_control','iconoTableroControl')
                            </span>
                            <span>Informes</span>
                        </div>

                        <!-- SEGUNDO NIVEL -->
                        <ul class="subMenu1 collapse" id="informeAmbiental">
                          <li>
                            <div id="opcInformeDiarioAmbiental" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/relevamientosControlAmbiental'" style="cursor: pointer;">
                              <span>Diarios</span>
                            </div>
                          </li>

                          <li>
                            <div id="opcInformeMensualAmbiental" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/relevamientosControlAmbiental'" href="#" style="cursor: pointer;">
                              <span>Mensuales</span>
                            </div>
                          </li>
                        </ul>

                      </li>


                        <div class="separadoresMenu">AUTOEXCLUSIÓN</div>
                        <li>
                          <div id="" class="opcionesHover"  href="">
                            <a href="http://10.1.120.9/AE/login.php" target="_blank">
                            <span class="flechita">
                                <i class="fa fa-angle-right"></i>
                              </span>
                              <span class="icono" style="padding-bottom: 50px;">
                                @svg('usuario','iconoUsuarios')
                              </span>
                              <span>AUTOEXCLUSIÓN</span>
                            </a>
                          </div>
                        </li>



                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['estadisticas_generales','estadisticas_por_casino','estadisticas_interanuales',
                                                                                                            'informes_mtm','informes_bingos','informes_mesas']))
                        <div class="separadoresMenu">ESTADÍSTICAS MTM</div>
                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['estadisticas_generales','estadisticas_por_casino','estadisticas_interanuales']))
                        <li>
                            <div id="barraEstadisticas" class="opcionesHover" data-target="#tablero" data-toggle="collapse" href="#">
                              <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 50px;">
                                  @svg('tablero_control','iconoTableroControl')
                                </span>
                                <span>Tablero</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="tablero">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'estadisticas_generales'))
                              <li>
                                <div id="opcEstadisticasGenerales" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/estadisticasGenerales'" href="#" style="cursor: pointer;">
                                  <span>Generales</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'estadisticas_por_casino'))
                              <li>
                                <div id="opcEstadisticasPorCasino" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/estadisticasPorCasino'" href="#" style="cursor: pointer;">
                                  <span>Por casino</span>
                                </div>
                              </li>
                              @endif
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'estadisticas_interanuales'))
                              <li>
                                <div id="opcEstadisticasInteranuales" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/interanuales'" href="#" style="cursor: pointer;">
                                  <span>Interanuales</span>
                                </div>
                              </li>
                              @endif
                            </ul>

                        </li>
                        @endif
                        @if(AuthenticationController::getInstancia()->usuarioTieneAlgunPermiso($id_usuario,['informes_mtm','informes_bingos','informes_mesas']))
                        <li>
                            <div id="barraInformes" class="opcionesHover" data-target="#informes" data-toggle="collapse" href="#">
                              <span class="flechita">
                                  <i class="fa fa-angle-right"></i>
                                </span>
                                <span class="icono" style="padding-bottom: 50px;">
                                  @svg('informes','iconoInformes')
                                </span>
                                <span>Informes</span>
                            </div>

                            <!-- SEGUNDO NIVEL -->
                            <ul class="subMenu1 collapse" id="informes">
                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'informes_mtm'))
                              <li>
                                <div id="opcInformesMTM" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/informesMTM'" href="#" style="cursor: pointer;">
                                  <span>MTM</span>
                                </div>
                              </li>
                              @endif

                              @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'informes_bingos'))
                              <li>
                                <div id="opcInformeBingo" class="opcionesHover" onclick="window.location = window.location.protocol + '//' + window.location.host + '/bingo/informe'" href="#" style="cursor: pointer;">
                                  <span>BINGO</span>
                                </div>
                              </li>
                              @endif

                            </ul>

                        </li>
                        @endif
                        @endif
                    </ul>

                </div>
                <div class="bottomMenu"></div>
              </div> <!-- contenedorMenu -->
          <!--  </div>  scrollMenu -->
        </aside>

        <!-- Vista de secciones -->
        <main class="contenedorVistaPrincipal">

          <section>
              <div class="container-fluid">
                @section('contenidoVista')
                @show

              </div>

          </section>
        </main>
              <!-- DESDE ACA -->

        <!-- NOTIFICACIÓN DE ÉXITO -->
          <!--  (*) Para que la animación solo MUESTRE (fije) el mensaje, se agrega la clase 'fijarMensaje' a #mensajeExito-->
          <!--  (*) Para que la animación MUESTRE Y OCULTE el mensaje, se quita la clase 'fijarMensaje' a #mensajeExito-->
          <!-- (**) si se quiere mostrar los botones de ACEPTAR o SALIR, se agrega la clase 'mostrarBotones' a #mensajeExito -->
          <!-- (**) para no mostrarlos, se quita la clase 'mostrarBotones' a #mensajeExito -->

        <div id="mensajeExito" class="" hidden>
            <div class="cabeceraMensaje">
              <!-- <i class="fa fa-times" style=""></i> -->
              <button type="button" class="close" style="font-size:40px;position:relative;top:10px;right:20px;"><span aria-hidden="true">×</span></button>
            </div>
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
        <div id="mensajeError"  hidden>
            <div class="cabeceraMensaje"></div>
            <div class="iconoMensaje">
              <img src="/img/logos/error.png" alt="imagen_error" >
            </div>
            <div class="textoMensaje" >
                <h3>ERROR</h3>
                <p>No es posible realizar la acción</p>
            </div>

        </div>

        <!-- Modal ayuda -->
        <div class="modal fade" id="modalAyuda" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                 <div class="modal-content">
                   <div class="modal-header modalNuevo" style="background-color: #1976D2;">
                     <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                     <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                     <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                     @section('tituloDeAyuda')
                     @show
                    </div>
                    <div  id="colapsado" class="collapse in">
                    <div class="modal-body modalCuerpo">
                              <div class="row">
                                @section('contenidoAyuda')
                                @show
                              </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-default" data-dismiss="modal">SALIR</button>
                    </div>
                  </div>
                </div>
              </div>
        </div>

        <!-- animacion -->
        <!-- <div id="animation_container" style="background-color:rgba(255, 255, 255, 1.00); width:400px; height:300px">
          <canvas id="canvas" width="30%" height="" style="position: absolute; display: block; background-color:rgba(255, 255, 255, 1.00);"></canvas>
          <div id="dom_overlay_container" style="pointer-events:none; overflow:hidden; width:400px; height:300px; position: absolute; left: 0px; top: 0px; display: block;">
          </div>
        </div> -->

        <!-- HASTA ACA -->

    </div>


    <!-- jQuery -->
    <script src="/js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="/js/bootstrap.js"></script>

    <!-- JavaScript ajaxError -->
    <script src="/js/ajaxError.js"></script>

    <!-- JavaScript personalizado -->
    <script src="/js/barraNavegacion.js"></script>

    <!-- JavaScript de tarjetas animadas -->
    <script src="/js/anime.min.js"></script>
    <script src="/js/main.js"></script>

    <!-- TableSorter -->
    <script type="text/javascript" src="/js/jquery.tablesorter.js"></script>
    <script type="text/javascript" src="/js/iconosTableSorter.js"></script>

    <!-- Collapse JS | Controla el menú -->
    <script type="text/javascript" src="/js/collapse.js"></script>

    <!-- librerias de animate -->
    <script src="/js/createjs-2015.11.26.min.js"></script>
    <script src="/js/Animacion_logo2.js?1517927954849"></script>

    <script src="/js/perfect-scrollbar.js" charset="utf-8"></script>

    <script type="text/javascript">

        $(document).on('show.bs.collapse','.subMenu1',function(){
            $('.subMenu1').not($(this)).collapse('hide');
        });
        $(document).on('show.bs.collapse','.subMenu2',function(){
            $('.subMenu2').not($(this)).collapse('hide');
        });
        $(document).on('show.bs.collapse','.subMenu3',function(){
            $('.subMenu3').not($(this)).collapse('hide');
        });

        var ps = new PerfectScrollbar('.opcionesMenu');
    </script>

    @section('scripts')
    @show

  </body>
</html>
