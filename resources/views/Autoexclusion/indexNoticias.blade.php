  @extends('includes.dashboard')
  @section('headerLogo')
      <span class="etiquetaLogoMaquinas">@svg('maquinas', 'iconoMaquinas')</span>
  @endsection

  <?php
  use Illuminate\Http\Request;
  use App\Http\Controllers\UsuarioController;
  use\App\http\Controllers\RelevamientoAmbientalController;
  ?>

  @section('estilos')
      <link rel="stylesheet" href="/css/paginacion.css">
      <link rel="stylesheet" href="/css/lista-datos.css">
      <link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
      <link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
      <link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css" />
      <link rel="stylesheet" href="/css/animacionCarga.css">

      <style>
          .fixed-column {
              position: sticky;
              top: 0;
              background-color: #fff;
              /* Color de fondo opcional */
              z-index: 999;
              /* Ajustar si es necesario */
          }

          .page {
              display: none;
          }

          .active {
              display: inherit;
          }

          .easy-autocomplete {
              width: initial !important
          }

          /* Make circles that indicate the steps of the form: */
          .step {
              height: 15px;
              width: 15px;
              margin: 0 2px;
              background-color: #bbbbbb;
              border: none;
              border-radius: 50%;
              display: inline-block;
              opacity: 0.5;
          }

          /* Mark the active step: */
          .step.actived {
              opacity: 1;
          }

          /* Mark the steps that are finished and valid: */
          .step.finish {
              background-color: #4CAF50;
          }

          .smalltext {
              font-size: 97%;
          }

          input[required],
          select[required] {
              background: #f0f6ff
          }

          #tablaNoticias {
              width: 600px;
              /* Ancho total de la tabla */
          }

          #tablaNoticias .col-sm-3,
          #tablaNoticias .col-sm-2 {
              max-width: 200px;
              /* Ancho máximo para las columnas TITULO y ABSTRACT */
              white-space: nowrap;
              /* Evita saltos de línea */
              overflow: hidden;
              /* Oculta el contenido que sobresale */
              text-overflow: ellipsis;
              /* Agrega puntos suspensivos al final del texto truncado */
          }

          #tablaNoticias .col-sm-2 {
              /*max-width: 100px; */
              white-space: nowrap;
              overflow: hidden;
              /* Oculta el contenido que sobresale */
              text-overflow: ellipsis;
              /* Agrega puntos suspensivos al final del texto truncado */
          }
      </style>
  @endsection

  @section('contenidoVista')
      <div class="col-xl-2">
          <div class="row">
              @if ($usuario->es_superusuario)
                  <div class="col-xl-12 col-md-3">
                      <a href="" id="btn-agregar-noticia" style="text-decoration: none;">
                          <div class="panel panel-default panelBotonNuevo">
                              <center>
                                  <img class="imgNuevo" src="/img/logos/noticia_white.png">
                              </center>
                              <div class="backgroundNuevo"></div>
                              <div class="row">
                                  <div class="col-xs-12">
                                      <center>
                                          <h5 class="txtLogo">+</h5>
                                          <h4 class="txtNuevo">Noticias para Excluidos</h4>
                                      </center>
                                  </div>
                              </div>
                          </div>
                      </a>
                  </div>
              @endif
          </div>
      </div>
      <div class="col-xl-10">
          <!-- FILTROS DE BÚSQUEDA -->
          <div class="row">
              <div class="col-md-12">
                  <div id="contenedorFiltros" class="panel panel-default">
                      <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                          <h4>Filtros de Búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                      </div>
                      <div id="collapseFiltros" class="panel-collapse collapse">
                          <div class="panel-body">

                              <div class="row">
                                  <div class="col-md-4">
                                      <h5>Titulo</h5>
                                      <input class="form-control" id="buscarNoticia" value="" />
                                  </div>
                                  <div class="col-md-4">
                                      <h5>Abstract</h5>
                                      <input class="form-control" id="buscarAbstract" value="" />
                                  </div>
                              </div>
                              <br>
                              <div class="row">
                                  <center>
                                      <button id="btn-buscar" class="btn btn-infoBuscar" type="button"><i
                                              class="fa fa-fw fa-search"></i> BUSCAR</button>
                                  </center>
                              </div>
                              <br>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
          <!-- TABLA -->
          <div class="row">
              <div class="col-md-12">
                  <div class="panel panel-default">
                      <div class="panel-heading">
                          <h4>LISTADO DE NOTICIAS</h4>
                      </div>
                      <div class="panel-body">
                          <table id="tablaNoticias" class="table">
                              <thead>
                                  <tr>
                                      <!-- <i class="fa fa-sort"></i> -->
                                      <th class="col-sm-3" value="titulo_noticias" estado="">TITULO</th>
                                      <th class="col-sm-3" value="abstract_noticias" estado="">ABSTRACT</th>
                                      <th class="col-sm-2" value="foto_noticias" estado="">FOTO</th>
                                      <th class="col-sm-2" value="pdf_noticias" estado="">PDF</th>
                                      <th class="col-sm-2">ACCIONES</th>
                                  </tr>
                              </thead>
                              <tbody id="cuerpoTabla" style="height: 350px;">
                                  <tr class="filaTabla" style="display: none">
                                      <td class="col-sm-3 titulo_noticias"></td>
                                      <td class="col-sm-3 abstract_noticias"></td>
                                      <td class="col-sm-2 foto_noticias"></td>
                                      <td class="col-sm-2 pdf_noticias"></td>
                                      <td class="col-sm-2 acciones">
                                          <button id="btnVerNoticia" class="btn btn-info info" type="button" value=""
                                              title="VER MÁS" data-toggle="tooltip" data-placement="top"
                                              data-delay="{'show':'300', 'hide':'100'}">
                                              <i class="fa fa-fw fa-search-plus"></i>
                                          </button>
                                          @if ($usuario->tienePermiso('modificar_ae'))
                                              <button id="btnEditar" class="btn btn-info info" type="button"
                                                  value="" title="EDITAR" data-toggle="tooltip"
                                                  data-placement="top" data-delay="{'show':'300', 'hide':'100'}">
                                                  <i class="fa fa-fw fa-pencil-alt"></i>
                                              </button>
                                          @endif
                                          <button id="btnBorrarNoticias" class="btn btn-info info" type="button"
                                              value="" title="BORRAR" data-toggle="tooltip" data-placement="top"
                                              data-delay="{'show':'300', 'hide':'100'}">
                                              <i class="fa fa-trash" aria-hidden="true"></i>
                                          </button>
                                          <span></span>
                                      </td>
                                  </tr>
                              </tbody>
                          </table>
                          <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <!--MODAL SUBIR NOTICIAS -->
      <div class="modal fade" id="modalSubirNoticia" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-lg">
              <div class="modal-content">
                  <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
                      <button type="button" class="close" data-dismiss="modal">
                          <i class="fa fa-times"></i>
                      </button>
                      <button id="btn-minimizar" type="button" class="close" data-toggle="collapse"
                          data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px">
                          <i class="fa fa-minus"></i>
                      </button>
                      <h3 class="modal-title" id="myModalLabel">| SUBIR NOTICIAS PARA EXCLUIDOS </h3>
                  </div>
                  <div id="colapsado" class="collapse in">
                      <div class="modal-body">
                          <div class="col-lg-12">
                              <h5>TITULO</h5>
                              <input id="noticiaTitulo" class="form-control"
                                  placeholder="Ingresar aquí el titulo (max: 255 caracteres)" required />
                          </div>
                          <div class="col-lg-12">
                              <h5>ABSTRACT</h5>
                              <textarea id="noticiaAbstract" class="form-control" rows="10"
                                  style="resize:vertical; min-height:120px; height:65px;"
                                  placeholder="Ingresar aquí las una breve descripción (max: 255 caracteres)" required>
                                </textarea>
                          </div>
                          <div class="row">
                              <div class="col-lg-6">
                                  <h5>CARGAR IMAGEN</h5>
                                  <div class="zona-file" style="border-radius:5px;">
                                      <input id="cargarNoticiaIMG" accept="image/*" data-borrado="false"
                                          type="file" />
                                      <span class="no_visualizable" hidden>El archivo es muy grande para
                                          visualizarlo.</span>
                                  </div>
                              </div>
                              <div class="col-lg-6">
                                  <h5>CARGAR ARCHIVO Pdf</h5>
                                  <div class="zona-file" style="border-radius:5px;">
                                      <input id="cargarNoticiaPDF" accept=".pdf" data-borrado="false" type="file" />
                                      <span class="no_visualizable" hidden>El archivo es muy grande para
                                          visualizarlo.</span>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="modal-footer" style="padding-top: 7px;">
                      <button id="btn-guardar-noticia" type="button" value="add"></button>
                      <button id="btn-cancelar-noticia" type="button" class="btn btn-default" id="btn-salir"
                          data-dismiss="modal" aria-label="Close">CANCELAR</button>
                  </div>
              </div>
          </div>
      </div>

      <!--MODAL VER FORMULARIOS SUBIR NOTICIAS -->
      <div class="modal fade" id="modalVerNoticia" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-lg">
              <div class="modal-content">
                  <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
                      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                      <button id="btn-minimizar" type="button" class="close" data-toggle="collapse"
                          data-minimizar="true" data-target="#colapsado"
                          style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>
                      <h3 id="title-modal" class="modal-title" id="myModalLabel">| VER NOTICIA</h3>
                  </div>
                  <div id="colapsado" class="collapse in">
                      <div class="modal-body">
                          <div class="col text-center">
                              <h6 id="text-titulo" class="mb-0">Titulo</h6>
                          </div>
                          <div class="col-lg-12 ">
                              <h5 id="titleEditShow" class="no_visualizable">NUEVO TITULO</h5>
                              <input id="noticiaNuevoTitulo" class="form-control no_visualizable"
                                  placeholder="Ingresar aquí el titulo (max: 255 caracteres)" required />
                          </div>
                          <div class="row">
                              <div class="col-lg-6">
                                  <h5>Abstract</h5>
                                  <h6 id="text-abstract">Abstract</h6>
                              </div>
                          </div>
                          <div class="col-lg-12">
                              <h5 id="abstractEditShow" class="no_visualizable">NUEVO ABSTRACT</h5>
                              <textarea id="noticiaNuevoAbstract" class="form-control no_visualizable" rows="10"
                                  style="resize:vertical; min-height:120px; height:65px;"
                                  placeholder="Ingresar aquí las una breve descripción (max: 255 caracteres)" required>
                                </textarea>
                          </div>
                          <div class="row">
                              <div class="col-md-12">
                                  <h5>Archivo</h5>
                                  <div class="zona-file col-md-12" style="border-radius:5px;">
                                      <embed id="imagen" title="Img Viewer" width="100%" height="100%" />
                                      <span class="no_visualizable" hidden>El archivo es muy grande para
                                          visualizarlo.</span>
                                  </div>
                                  <a id="imagen_url" class="link_archivo col-md-12" href="/certificadoSoft/pdf/"
                                      target="_blank" rel="noopener noreferrer" style="text-align: center;">
                                      IMAGEN ACTUAL
                                  </a>
                              </div>
                              <div id="div-img" class="col-md-12 no_visualizable">
                                  <div class="zona-file col-md-12" style="border-radius:5px;">
                                      <input id="cargarNuevaNoticiaIMG" class="no_visualizable" accept="image/*"
                                          data-borrado="false" type="file" />
                                      <span class="no_visualizable" hidden>El archivo es muy grande para
                                          visualizarlo.</span>
                                  </div>
                              </div>
                          </div>
                          <div class="row">
                              <div class="col-md-12">
                                  <h5>Archivo</h5>
                                  <div class="zona-file col-md-12" style="border-radius:5px;">
                                      <embed id="pdfViewer" title="PDF Viewer" width="100%" height="100%" />
                                      <span class="no_visualizable" hidden>El archivo es muy grande para
                                          visualizarlo.</span>
                                  </div>
                                  <a id="pdf_url" class="link_archivo col-md-12" href="/certificadoSoft/pdf/"
                                      target="_blank" rel="noopener noreferrer" style="text-align: center;">
                                      PDF ACTUAL
                                  </a>
                              </div>
                              <div id="div-pdf" class="col-md-12 no_visualizable">
                                  <div class="zona-file col-md-12" style="border-radius:5px;">
                                      <input id="cargarNuevaNoticiaPDF" class="no_visualizable" accept=".pdf"
                                          data-borrado="false" type="file" />
                                      <span class="no_visualizable" hidden>El archivo es muy grande para
                                          visualizarlo.</span>
                                  </div>
                              </div>
                          </div>
                      </div>
                      <div class="modal-footer">
                          <button id="enviarNoticiasActualizacion" type="button" class="btn btn-default no_visualizable"
                              id="btn-salir" data-dismiss="modal" aria-label="Close">ENVIAR</button>
                          <button type="button" class="btn btn-default" id="btn-salir" data-dismiss="modal"
                              aria-label="Close">SALIR</button>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <meta name="_token" content="{!! csrf_token() !!}" />
  @endsection

  @section('tituloDeAyuda')
      <h3 class="modal-title2" style="color: #fff;">| SESIONES</h3>
  @endsection
  @section('contenidoAyuda')
      <div class="col-md-12">
          <h5>Tarjeta de Sesiones</h5>
          <p>
              Agregar nuevos autoexluidos, revocar autoexclusiones, ver listado y estados.
      </div>
  @endsection

  @section('scripts')
      <!-- JavaScript paginacion -->
      <script src="/js/paginacion.js" charset="utf-8"></script>
      <script src="/js/lista-datos.js" type="text/javascript"></script>
      <!-- JavaScript personalizado -->
      <script src="/js/Autoexclusion/indexNoticias.js?2" charset="utf-8"></script>
      <!-- JS file -->
      <script src="/js/Autoexclusion/EasyAutocomplete/jquery.easy-autocomplete.min.js"></script>
      <!-- CSS file -->
      <link rel="stylesheet" href="/js/Autoexclusion/EasyAutocomplete/easy-autocomplete.min.css">
      <!-- Custom input Bootstrap -->
      <script src="/js/fileinput.min.js" type="text/javascript"></script>
      <script src="/js/locales/es.js" type="text/javascript"></script>
      <script src="/themes/explorer/theme.js" type="text/javascript"></script>
      <!-- DateTimePicker JavaScript -->
      <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
      <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
  @endsection
