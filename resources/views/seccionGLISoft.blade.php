@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
<link href="css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="css/paginacion.css">
@endsection

<?php
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthenticationController;
use App\Archivo;
use Illuminate\Http\Request;

$id_usuario = session('id_usuario');
?>

@section('contenidoVista')

          <div class="row">
              <div class="col-lg-12 col-xl-9">

                <div class="row">
                  <div class="col-md-12">
                    <div id="contenedorFiltros" class="panel panel-default">
                      <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                        <h4>Filtros de Búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                      </div>
                      <div id="collapseFiltros" class="panel-collapse collapse">
                          <div class="panel-body">
                            <div class="row">
                              <div class="col-md-3">
                                <h5>Código de Certificado</h5>
                                <input id="nro_certificado" type="text" class="form-control" placeholder="Código de certificado">
                              </div>
                              <div class="col-md-2">
                                <h5>Nombre Archivo</h5>
                                <input id="nombre_archivo" type="text" class="form-control" placeholder="Nombre archivo">
                              </div>
                              <div class="col-md-3">
                                <h5>Número de expediente</h5>
                                <div class="input-group triple-input">
                                    <input id="nro_exp_org" style="width:30%; border-right:none;" type="text" placeholder="-----" maxlength="5" class="form-control" />
                                    <input id="nro_exp_interno" style="width:50%;" type="text" placeholder="-------" maxlength="7" class="form-control" />
                                    <input id="nro_exp_control" style="width:20%; border-left:none;" type="text" placeholder="-" maxlength="1" class="form-control" />
                                </div>
                              </div>
                              <div class="col-md-2">
                                <h5>Casino</h5>
                                <div class="form-group">
                                  <select class="form-control" id="sel1">
                                    <option value="0">-Casino-</option>
                                    @foreach($casinos as $casino)
                                    <option value="{{$casino->id_casino}}">{{$casino->nombre}}</option>
                                    @endforeach
                                  </select>
                                </div>
                              </div>
                            <div class="col-md-2">
                              <h5 style="color:#f5f5f5">Búsqueda</h5>
                              <button id="buscarCertificado" class="btn btn-infoBuscar" type="button" name="button"><i class="fa fa-search fa-1x"></i> BUSCAR</button>
                            </div>
                          </div>
                      </div>
                    </div>
                  </div>
                </div> <!-- fin de la columna FILTRO -->
              </div> <!-- fin de la fila de FILTRO -->

                  <div class="row">
                    <div class="col-md-12">
                      <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>Certificados de Software Registrados en el Sistema</h4>
                        </div>
                        <div class="panel-body">
                          <table id="tablaGliSofts" class="table table-fixed tablesorter">
                            <thead>
                              <tr>
                                <th class="col-xs-4" value="gli_soft.nro_archivo" estado="">CÓDIGO DE CERTIFICADO  <i class="fa fa-sort"></i></th>
                                <th class="col-xs-4" value="archivo.nombre_archivo" estado="">NOMBRE DEL ARCHIVO  <i class="fa fa-sort"></i></th>
                                <th class="col-xs-4">ACCIÓN</th>
                                <!-- <th>ACCIÓN</th> -->
                              </tr>
                            </thead>
                            <tbody id="cuerpoTabla" style="height: 380px;">

                            </tbody>
                          </table>
                          <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                        </div>
                      </div>
                    </div> <!--/columna TABLA -->
                </div>
              </div> <!-- col-lg-12 col-xl-9 -->

      <div class="col-lg-12 col-xl-3">
            <div class="row">
              <div class="col-xl-12 col-lg-4">
               <a href="" id="btn-nuevo" style="text-decoration: none;">
                <div class="panel panel-default panelBotonNuevo">
                    <center><img class="imgNuevo" src="/img/logos/software_white.png"><center>
                    <div class="backgroundNuevo"></div>
                    <div class="row">
                        <div class="col-xs-12">
                          <center>
                              <h5 class="txtLogo">+</h5>
                              <h4 class="txtNuevo">NUEVO CERTIFICADO SOFTWARE</h4>
                          </center>
                        </div>
                    </div>
                </div>
               </a>
              </div>
          @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_glihard'))
            <div class="col-xl-12 col-lg-4">
              <a href="certificadoHard" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">CERTIFICADO HARD</h2>
                    <h2 class="tituloSeccionMenor">CERTIFICADO HARDWARE</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/hardware_white.png" alt="">
                  </div>
              </a>
              <!-- <a href="certificadoHard" style="text-decoration:none;">
                  <div class="tarjetaSeccionMenor" align="center">
                    <h2 class="tituloFondoMenor">HARDWARE</h2>
                    <h2 class="tituloSeccionMenor">GLI HARDWARE</h2>
                    <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/hardware_white.png" alt="">
                  </div>
              </a> -->
            </div>
          @endif
            @if(AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,'ver_seccion_expedientes'))
              <div class="col-xl-12 col-lg-4">
                <a href="expedientes" style="text-decoration:none;">
                    <div class="tarjetaSeccionMenor" align="center">
                      <h2 class="tituloFondoMenor">EXPEDIENTES</h2>
                      <h2 class="tituloSeccionMenor">EXPEDIENTES</h2>
                      <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/expedientes_white.png" alt="">
                    </div>
                </a>
                <!-- <a href="expedientes" style="text-decoration:none;">
                    <div class="tarjetaSeccionMenor" align="center">
                      <h2 class="tituloFondoMenor">EXPEDIENTES</h2>
                      <h2 class="tituloSeccionMenor">EXPEDIENTES</h2>
                      <img height="62%" style="top:-200px;" class="imagenSeccionMenor" src="/img/logos/expedientes_white.png" alt="">
                    </div>
                </a> -->
              </div>
            </div>
            @endif
      </div>

        </div> <!-- row -->


    <!-- Modal Crear GLI Soft -->
    <div id="modalGLI" class="modal fade" role="dialog">
      <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">

          <div class="modal-header modalNuevo">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
            <h3 class="modal-title"> | NUEVO CERTIFICADO SOFTWARE</h3>
          </div>

        <div id="colapsado" class="collapse in">
          <div class="modal-body">

                <!-- PRIMERA FILA -->
                <div class="row">
                    <!-- columna de la IZQUIERDA -->
                    <div class="col-lg-6">

                        <div class="row">
                          <div class="col-md-12">
                            <h5>Código de certificado</h5>
                            <input id="nroCertificado" type="text" class="form-control" placeholder="Código de certificado">
                            <br>
                          </div>
                        </div>

                        <!-- buscar expedientes -->
                        <div class="row">
                            <div class="col-lg-12">
                              <h5>Buscar Expedientes <i class="fa fa-search"></i></h5>

                               <div class="input-group lista-datos-group">
                                    <input id="inputExpediente" class="form-control " type="text" value="" autocomplete="off" placeholder="- - - - -/ - - - - - - - / -">
                                    <span class="input-group-btn">
                                      <button id="btn-agregarExpediente" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                                    </span>
                              </div>
                            </div>
                        </div>
                        <br>
                        <!-- tabla de expedientes -->
                        <div class="row">
                            <div class="col-md-12">
                                <h6>EXPEDIENTES DEL CERTIFICADO SOFTWARE</h6>
                                <table id="tablaExpedientesSoft" class="table">
                                  <thead>
                                      <th class="col-xs-9">N° DE CERTIFICADO</th>
                                      <th class="col-xs-3">ACCIONES</th>
                                  </thead>
                                  <tbody>

                                  </tbody>
                                </table>
                            </div>
                        </div>

                    </div> <!-- /columna izquierda -->

                    <!-- columna de la DERECHA -->
                    <div class="col-lg-6">
                      <!-- archivo -->
                      <div class="row">
                        <div class="col-md-12">
                                <h5>Archivo</h5>
                                <div class="zona-file" style="border-radius:5px;">
                                    <input id="cargaArchivo" data-borrado="false" type="file" multiple>
                                </div>
                        </div>
                      </div>
                    </div>
                </div> <!-- ./row -->

                <div class="row">
                  <div class="col-lg-12">
                    <h5>Observaciones</h5>
                    <textarea id="observaciones" class="form-control" rows="10" style="resize:vertical; min-height:60px; height:65px;" placeholder="Ingresar aquí las observaciones"></textarea>
                  </div>
                </div>

                <br>

                <div class="row" style="border-top: 1px solid #fff;padding-top:20px;">
                    <div class="col-lg-6">
                      <h5>Buscar JUEGOS <i class="fa fa-search"></i></h5>

                       <div class="input-group lista-datos-group">
                            <input id="inputJuego" class="form-control " type="text" value="" autocomplete="off" placeholder="Nombre del juego">
                            <span class="input-group-btn">
                              <button id="btn-agregarJuego" class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                            </span>
                      </div>
                    </div>
                </div>
                <br>
                <div class="row">

                    <div class="col-md-12">
                        <h6>JUEGOS DEL CERTIFICADO</h6>
                        <table id="tablaJuegos" class="table">
                          <thead>
                              <th class="col-xs-3">NOMBRE</th>
                              <th class="col-xs-3">CÓDIGO JUEGO</th>
                              <th class="col-xs-3">TABLA PAGOS</th>
                              <th class="col-xs-3">ACCIÓN</th>
                          </thead>
                          <tbody>

                          </tbody>
                        </table>
                    </div>
                </div> <!-- row - tabla juegos -->


          </div>
          <div class="modal-footer">
            <button id="btn-guardar" type="button" value="add"></button>
            <input id="id_gli" type="" hidden value="" >
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
        </div> <!-- -->
        </div>
      </div>
    </div><!-- FIN CUERPO MODAL -->

    <!-- Modal -->
    <div id="modalEliminar" class="modal fade" role="dialog">
      <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header" style="font-family: Roboto-Black; color: #EF5350">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h3 class="modal-titleEliminar">ADVERTENCIA</h3>
          </div>
          <div class="modal-body franjaRojaModal">
            <strong><p>¿Seguro desea eliminar el Certificado de Software?</p></strong>
          </div>
          <div class="modal-footer">
            <button id="boton-eliminarGLI" type="button" class="btn btn-dangerEliminar">ELIMINAR</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">CANCELAR</button>
          </div>
        </div>
      </div>
    </div><!-- FIN CUERPO MODAL -->


@endsection

  <!-- Comienza modal de ayuda -->
  @section('tituloDeAyuda')
  <h3 class="modal-title" style="color: #fff;">| CERTIFICADO DE SOFTWARE</h3>
  @endsection
  @section('contenidoAyuda')
  <div class="col-md-12">
    <h5>Tarjeta de Certificado de Software</h5>
    <p>
      Admite la carga de los respectivos .pdf de certificados de software, asociados a expedientes y juegos que corresponda.
    </p>
  </div>
  @endsection
  <!-- Termina modal de ayuda -->

@section('scripts')

    <!-- token -->
    <meta name="_token" content="{!! csrf_token() !!}" />

    <script src="js/seccionGliSoft.js"></script>

    <!-- Custom input Bootstrap -->
    <script src="js/fileinput.min.js" type="text/javascript"></script>
    <script src="js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>
    <script src="js/paginacion.js" charset="utf-8"></script>

    <script type="text/javascript">



        $('#cargaArchivo').on('fileuploaderror', function(event, data, msg) {
            var form = data.form, files = data.files, extra = data.extra,
                response = data.response, reader = data.reader;
            console.log('File upload error');
           // get message
           alert(msg);
        });
    </script>
@endsection
