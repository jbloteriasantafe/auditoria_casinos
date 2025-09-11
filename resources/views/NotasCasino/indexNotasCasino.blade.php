@extends('includes.dashboard')
@section('headerLogo')
    <span class="etiquetaLogoInformes">@svg('informes', 'iconoInformes')</span>
@endsection

@section('estilos')
    <link rel="stylesheet" href="/css/paginacion.css">
    <link rel="stylesheet" href="/css/lista-datos.css">
    <link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
    <link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="/css/animacionCarga.css">
    <style>
        #observaciones {
            width: 100%;
            /* ocupa todo el ancho disponible */
            max-width: 100%;
            /* no se pasa del contenedor */
            box-sizing: border-box;
            /* respeta padding y bordes dentro del ancho */
        }
    </style>
@endsection

@section('contenidoVista')
    <div class="row">
        <div class="col-xl-12 col-md-12">
            <a href="" id="btn-agregar-nota" style="text-decoration: none;">
                <div class="panel panel-default panelBotonNuevo">
                    <center>
                        <img class="imgNuevo" src="/img/logos/noticia_white.png">
                    </center>
                    <div class="backgroundNuevo"></div>
                    <div class="row">
                        <div class="col-xs-12">
                            <center>
                                <h5 class="txtLogo">+</h5>
                                <h4 class="txtNuevo">Agregar una nota</h4>
                            </center>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div id="contenedorFiltros" class="panel panel-default">
                <div class="panel-heading" data-toggle="collapse" href="#collapseFiltros" style="cursor: pointer">
                    <h4>Filtros de Búsqueda <i class="fa fa-fw fa-angle-down"></i></h4>
                </div>
                <div id="collapseFiltros" class="panel-collapse collapse">
                    <div class="panel-body">
                        {{-- TODO: DEFINIR PARAMETROS DE BUSQUEDA Y MODIFICARS --}}
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
                        <div class="row">
                            <h5>Publicado entre</h5>
                            <div class="col-md-3">
                                <h5>Fecha Inicio</h5>
                                <div class="input-group date" id="rangoinicio">
                                    <input type="text" class="form-control" placeholder="Fecha de Inicio"
                                        id="fecha_noticia_inicio" autocomplete="off"
                                        style="background-color: rgb(255,255,255);" data-original-title="" title="">
                                    <span id="input-times-autoexclusion" class="input-group-addon"
                                        style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                    <span id="input-calendar-autoexclusion" class="input-group-addon"
                                        style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h5>Fecha Fin</h5>
                                <div class="input-group date" id="rangofin">
                                    <input type="text" class="form-control" placeholder="Fecha de Fin"
                                        id="fecha_noticia_fin" autocomplete="off"
                                        style="background-color: rgb(255,255,255);" data-original-title="" title="">
                                    <span id="input-times-renovacion" class="input-group-addon"
                                        style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>
                                    <span id="input-calendar-renovacion" class="input-group-addon"
                                        style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                                </div>
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
                    <h4>LISTADO DE NOTAS</h4>
                </div>
                <div class="panel-body">
                    <table id="tablaNoticias" class="table">
                        <thead>
                            {{-- TODO: DEFINIR INFORMACION IMPORTANTE PARA MOSTRAR Y AGREGAR --}}
                            <tr>
                                <!-- <i class="fa fa-sort"></i> -->
                                <th class="col-sm-3" value="titulo_noticias" estado="">TITULO</th>
                                <th class="col-sm-3" value="abstract_noticias" estado="">ABSTRACT</th>
                                <th class="col-sm-2" value="foto_noticias" estado="">FOTO</th>
                                <th class="col-sm-2" value="pdf_noticias" estado="">PDF</th>
                                <th class="col-sm-2">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
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

    <div class="modal fade" id="modalSubirNota" tabindex="-1" role="dialog" aria-hidden="true">
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
                    <h3 class="modal-title" id="myModalLabel">| SUBIR NOTAS </h3>
                </div>
                <div id="colapsado" class="collapse in">
                    <div class="modal-body">
                        <div class="row">
                            {{-- ! NRO DE NOTA --}}
                            <div class="col-lg-12">
                                <h5>Nro de nota</h5>
                                <input id="nroNota" class="form-control" required />
                            </div>
                            {{-- ! NOMBRE DEL EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Nombrw del evento</h5>
                                <input id="nombreEvento" class="form-control" required />
                            </div>
                            {{-- ! TIPO EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Tipo evento</h5>
                                <input id="tipoEvento" class="form-control" required />
                            </div>
                            {{-- ! CATEGORIA --}}
                            <div class="col-lg-12">
                                <h5>Categoría</h5>
                                <input id="categoria" class="form-control" required />
                            </div>
                            {{-- ! ADJUNTO PAUTAS --}}
                            <div class="col-lg-12">
                                <h5>Adjunto pautas</h5>
                                <div class="zona-file">
                                    <input id="adjuntoPautas" data-borrado="false" type="file" />
                                </div>

                            </div>
                            {{-- ! ADJUNTO DISEÑO --}}
                            <div class="col-lg-12">
                                <h5>Adjunto DISEÑO</h5>
                                <div class="zona-file">
                                    <input id="temaEvento" type="file" />
                                </div>
                            </div>
                            {{-- ! ADJUNTO BASES Y COND. --}}
                            <div class="col-lg-12">
                                <h5>Adjunto bases y condiciones</h5>
                                <div class="zona-file">
                                    <input id="basesyCondiciones" type="file" />
                                </div>
                            </div>
                            {{-- !ADJUNTO INF. TECNICO --}}
                            <div class="col-lg-12">
                                <h5>Adjunto inf. técnico</h5>
                                <div class="zona-file">
                                    <input id="adjuntoInfTecnico" type="file" />
                                </div>
                            </div>
                            {{-- ! FECHA INICIO EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Fecha inicio evento</h5>
                                <input id="fechaInicio" class="form-control" type="date" required />
                            </div>
                            {{-- !FECHA FINALIZACION EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Fecha finalización evento</h5>
                                <input id="fechaFinalizacion" class="form-control" type="date" required />
                            </div>
                            {{-- ! FECHA REFERENCIA EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Fecha referencia evento</h5>
                                <input id="fechaReferencia" class="form-control" required />
                            </div>
                            {{-- ! MES REFERENCIA EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Mes referencia evento</h5>
                                <input id="mesReferencia" class="form-control" required />
                            </div>
                            {{-- !AÑO --}}
                            <div class="col-lg-12">
                                <h5>Año referencia evento</h5>
                                <input id="anioReferencia" class="form-control" required />
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding-top: 7px;">
                    <button id="btn-guardar-nota" type="button" value="add"></button>
                    <button id="btn-cancelar-nota" type="button" class="btn btn-default" id="btn-salir"
                        data-dismiss="modal" aria-label="Close">CANCELAR</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('tituloDeAyuda')
    <h3 class="modal-title2" style="color: #fff;">| NOTAS CASINOS</h3>
@endsection

@section('contenidoAyuda')
    <div class="col-md-12">
        <h5> Notas casinos</h5>
        <p>
            Agregar, editar y eliminar notas relacionadas con los casinos.
        </p>
    </div>
@endsection

@section('scripts')
    <script src="/js/NotasCasino/indexNotasCasino.js"></script>
    <script src="/js/paginacion.js" charset="utf-8"></script>
    <script src="/js/lista-datos.js" type="text/javascript"></script>
    <!-- Custom input Bootstrap -->
    <script src="/js/fileinput.min.js" type="text/javascript"></script>
    <script src="/js/locales/es.js" type="text/javascript"></script>
    <script src="/themes/explorer/theme.js" type="text/javascript"></script>
    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="/js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>
@endsection
