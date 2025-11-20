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
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            display: none;
        }

        .input-error {
            border: 1px solid #e74c3c;
            background-color: #fdecea;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .asterisco {
            cursor: help;
        }

        .page {
            display: none;
        }

        .contenedorVistaPrincipal {
            position: relative;
            height: auto;
            /* deja que crezca según el contenido */
            overflow-y: visible;
            /* elimina el scroll del contenedor */
        }

        .tabla-scroll {
            max-height: 500px;
            /* ajusta el alto máximo según necesites */
            overflow-y: auto;
            overflow-x: auto;
        }

        /* Mantener cabecera fija si se quiere */
        #tablaNotas thead th {
            position: sticky;
            top: 0;
            background: #f8f8f8;
            z-index: 2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            text-align: center;
        }

        #tablaNotas thead th[title] {
            cursor: help;
        }

        /* Estilo general de celdas */
        #tablaNotas td,
        #tablaNotas th {
            max-width: 140px;
            /* ancho máximo de cada celda */
            max-height: 40px;
            /* alto máximo de cada celda */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            text-align: center;
        }

        /* Tooltip usando el atributo title */
        #tablaNotas td[title] {
            cursor: help;
        }
    </style>
@endsection

@section('contenidoVista')
    {{-- ! BOTON DE AGREGAR NOTAS --}}
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
    {{-- ! FILTRO DE NOTAS --}}
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
                                <h5>NRO. DE NOTA</h5>
                                <input class="form-control" id="buscarNroNota" value="" />
                            </div>
                            <div class="col-md-4">
                                <h5>NOMBRE DEL EVENTO</h5>
                                <input class="form-control" id="buscarNombreEvento" value="" />
                            </div>
                        </div>
                        <div class="row">
                            <h5>FECHA INICIO DE LA NOTA ENTRE</h5>
                            <div class="col-md-4">
                                <h5>FECHA INICIO</h5>
                                <div id="rangoinicio">
                                    <input type="date" class="form-control" placeholder="Fecha de inicio evento"
                                        id="fecha_nota_inicio" autocomplete="off"
                                        style="background-color: rgb(255,255,255);" data-original-title="" title="">
                                    <span class="error-message" id="mensajeErrorFechaInicioFiltro" style="display: none;">
                                        La fecha de inicio no puede ser posterior a la fecha de finalización.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h5>FECHA FIN</h5>
                                <div id="rangofin">
                                    <input type="date" class="form-control" placeholder="Fecha de finalizacion evento"
                                        id="fecha_nota_fin" autocomplete="off" style="background-color: rgb(255,255,255);"
                                        data-original-title="" title="">
                                    <span class="error-message" id="mensajeErrorFechaFinFiltro" style="display: none;">
                                        La fecha de finalización no puede ser anterior a la fecha de inicio.</span>
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
    {{-- ! TABLA DE NOTAS --}}
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>LISTADO DE NOTAS</h4>
                </div>
                <div class="panel-body">
                    <div class="tabla-scroll">
                        <table id="tablaNotas" class="table">
                            <thead>
                                <tr>
                                    <!-- <i class="fa fa-sort"></i> -->
                                    <th class="col-sm-1 text-center" value="numero_nota" estado=""
                                        title="Número de nota">NRO. DE NOTA</th>
                                    <th class="col-sm-1 text-center" value="nombre_evento" estado=""
                                        title="Nombre de evento">NOMBRE EVENTO
                                    </th>{{-- ! TODO LO QUE DICE NOTAS EN EL CODIGO ESTA COMO PAUTAS --}}
                                    <th class="col-sm-1 text-center" value="adjunto_pautas" estado=""
                                        title="Adjunto pautas">ADJ. NOTAS
                                    </th>
                                    <th class="col-sm-1 text-center" value="adjunto_diseño" estado=""
                                        title="Adjunto diseño">ADJ. DISEÑO
                                    </th>
                                    <th class="col-sm-1 text-center" value="adjunto_basesycond" estado=""
                                        title="Adjunto bases y condiciones">ADJ. BASES
                                        Y
                                        CONDICIONES
                                    </th>
                                    <th class="col-sm-1 text-center" value="fecha_inicio_evento"
                                        title="Fecha de inicio del evento">FECHA INICIO EVENTO</th>
                                    <th class="col-sm-1 text-center" value="fecha_finalizacion_evento"
                                        title="Fecha de finalización del evento">FECHA FINALIZACIÓN
                                        EVENTO</th>
                                    <th class="col-sm-1 text-center" value="estado" title="Estado de la nota">ESTADO</th>
                                    <th class="col-sm-1 text-center" value="notas_relacionadas"
                                        title="Notas relacionadas">NOTAS RELACIONADAS</th>
                                    <th class="col-sm-1 text-center" value="acciones" title="Acciones">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTabla">
                                <tr class="filaTabla" style="display: none">
                                    <td class="col-sm-1 text-center numero_nota"></td>
                                    <td class="col-sm-1 text-center nombre_evento"></td>
                                    <td class="col-sm-1 text-center adjunto_pautas"></td>
                                    <td class="col-sm-1 text-center adjunto_disenio"></td>
                                    <td class="col-sm-1 text-center adjunto_basesycond"></td>
                                    <td class="col-sm-1 text-center fecha_inicio_evento"></td>
                                    <td class="col-sm-1 text-center fecha_finalizacion_evento"></td>
                                    <td class="col-sm-1 text-center estado"></td>
                                    <td class="col-sm-1 text-center notas_relacionadas"></td>
                                    <td class="col-sm-1 text-center acciones"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="herramientasPaginacion" class="row zonaPaginacion"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ! MODAL DE CARGA DE NOTAS --}}
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
                        <form class="row" id="formulario">
                            {{-- ! FORMADO DE NOTA --}}
                            <div class="row">
                                {{-- ! NRO DE NOTA --}}
                                <div class="col-lg-4">
                                    <h5>Nro de nota <span class="asterisco text-muted text-danger"
                                            title="Este campo es obligatorio">*</span></h5>
                                    <input id="nroNota" class="form-control" type="number" required
                                        placeholder="Mínimo 3 dígitos (Por ejemplo: 001)" />
                                    <span class="error-message" id="mensajeErrorNroNota" style="display: none;">Este
                                        campo es obligatorio y debe
                                        tener como mínimo 3 dígitos</span>
                                </div>
                                {{-- ! TIPO DE NOTA --}}
                                <div class="col-lg-4">
                                    <h5>Tipo de nota <span class="asterisco text-muted text-danger"
                                            title="Este campo es obligatorio">*</span></h5>
                                    <select id="tipoNota" class="form-control" required>
                                        <option value="" selected disabled>-- Seleccione un tipo de nota --</option>
                                        @foreach ($tipos_nota as $tipo)
                                            <option value="{{ $tipo->id_tipo_nota }}">{{ $tipo->tipo_nombre }}</option>
                                        @endforeach
                                    </select>
                                    <span class="error-message" style="display: none;" id="mensajeErrorTipoNota">Este
                                        campo es obligatorio</span>
                                </div>
                                {{-- ! AÑO DE NOTA --}}
                                <div class="col-lg-4">
                                    <h5>Año de nota <span class="asterisco text-muted text-danger"
                                            title="Este campo es obligatorio">*</span></h5>
                                    <input id="anioNota" class="form-control" type="number"
                                        value="{{ $anio }}" disabled required />
                                    <span class="error-message" style="display: none;" id="mensajeErrorAnioNota">Este
                                        campo es obligatorio</span>
                                </div>
                            </div>
                            {{-- ! NOMBRE DEL EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Nombre del evento <span class="asterisco text-muted text-danger"
                                        title="Este campo es obligatorio">*</span></h5>
                                <input id="nombreEvento" class="form-control" required maxlength="1000"
                                    placeholder="Ingrese el nombre del evento (Máximo: 1000 caracteres)" />
                                <span class="error-message" style="display: none;" id="mensajeErrorNombreEvento">Este
                                    campo es obligatorio</span>
                            </div>
                            {{-- ! TIPO EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Tipo evento <span class="asterisco text-muted text-danger"
                                        title="Este campo es obligatorio">*</span></h5>
                                <select id="tipoEvento" class="form-control" required>
                                    <option value="" selected disabled>-- Seleccione un tipo de evento --</option>
                                    @foreach ($tipos_evento as $tipo)
                                        <option value="{{ $tipo->idtipoevento }}">{{ $tipo->tipo_nombre }}</option>
                                    @endforeach
                                </select>
                                <span class="error-message" style="display: none;" id="mensajeErrorTipoEvento">Este
                                    campo es obligatorio</span>
                            </div>
                            {{-- ! CATEGORIA --}}
                            <div class="col-lg-12">
                                <h5>Categoría <span class="asterisco text-muted text-danger"
                                        title="Este campo es obligatorio">*</span></h5>
                                <select id="categoria" name="categoria" class="form-control" required>
                                    <option value="" selected disabled>-- Seleccione una categoría --</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->idcategoria }}">{{ $categoria->categoria }}</option>
                                    @endforeach
                                </select>
                                <span class="error-message" style="display: none;" id="mensajeErrorCategoria">Este
                                    campo es obligatorio</span>
                            </div>
                            {{-- ! ADJUNTO NOTAS, TODO LO QUE DICE PAUTAS EN TODO EL CODIGO ES DE LAS NOTAS --}}
                            <div class="col-lg-12">
                                <h5>Adjunto NOTAS</h5>
                                <div class="custom-file">
                                    <input id="adjuntoPautas" name="adjuntoPautas" data-borrado="false"
                                        class="custom-file-input" type="file" accept=".pdf,.zip"
                                        style="display:none;" />
                                    <button type="button" id="adjuntoPautasBtn" class="btn btn-primary">Seleccionar
                                        archivo</button>
                                    <span id="adjuntoPautasName" class="ms-2">Ningún archivo seleccionado</span>
                                    <button id="eliminarAdjuntoPautas" type="button"
                                        class="btn btn-danger btn-sm ms-2">Eliminar</button>
                                </div>
                                <span class="error-message" style="display: none;" id="mensajeErrorAdjuntoPautas">El
                                    archivo seleccionado es demasiado grande. El tamaño máximo permitido es de 150
                                    MB.</span>
                            </div>
                            {{-- ! ADJUNTO DISEÑO --}}
                            <div class="col-lg-12">
                                <h5>Adjunto DISEÑO</h5>
                                <div>
                                    <input id="adjuntoDisenio" name="adjuntoDisenio" type="file" accept=".pdf,.zip"
                                        class="custom-file-input" style="display:none;" />
                                    <button type="button" id="adjuntoDisenioBtn" class="btn btn-primary">Seleccionar
                                        archivo</button>
                                    <span id="adjuntoDisenioName" class="ms-2">Ningún archivo seleccionado</span>
                                    <button id="eliminarAdjuntoDisenio" type="button"
                                        class="btn btn-danger btn-sm ms-2">Eliminar</button>
                                </div>
                                <span class="error-message" style="display: none;" id="mensajeErrorAdjuntoDisenio">El
                                    archivo seleccionado es demasiado grande. El tamaño máximo permitido es de 150
                                    MB.</span>
                            </div>
                            {{-- ! ADJUNTO BASES Y COND. --}}
                            <div class="col-lg-12">
                                <h5>Adjunto bases y condiciones</h5>
                                <div>
                                    <input id="basesyCondiciones" type="file" accept=".pdf,.zip,.doc,.docx"
                                        class="custom-file-input" style="display:none;" />
                                    <button type="button" id="basesyCondicionesBtn" class="btn btn-primary">Seleccionar
                                        archivo</button>
                                    <span id="basesyCondicionesName" class="ms-2">Ningún archivo seleccionado</span>
                                    <button id="eliminarBasesyCondiciones" type="button"
                                        class="btn btn-danger btn-sm ms-2">Eliminar</button>
                                </div>
                                <span class="error-message" style="display: none;" id="mensajeErrorBasesyCondiciones">El
                                    archivo seleccionado es demasiado grande. El tamaño máximo permitido es de 150
                                    MB.</span>
                            </div>
                            {{-- ! FECHA INICIO EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Fecha inicio evento <span class="asterisco text-muted text-danger"
                                        title="Este campo es obligatorio">*</span></h5>
                                <input id="fechaInicio" class="form-control" type="date" required />
                                <span class="error-message" style="display: none;" id="mensajeErrorFechaInicio">Este
                                    campo es obligatorio</span>
                            </div>
                            {{-- !FECHA FINALIZACION EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Fecha finalización evento <span class="asterisco text-muted text-danger"
                                        title="Este campo es obligatorio">*</span></h5>
                                <input id="fechaFinalizacion" class="form-control" type="date" required />
                                <span class="error-message" style="display: none;"
                                    id="mensajeErrorFechaFinalizacion">Este
                                    campo es obligatorio</span>
                            </div>
                            {{-- ! FECHA REFERENCIA EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Fecha referencia evento</h5>
                                <input id="fechaReferencia" class="form-control" maxlength="500"
                                    placeholder="Ingrese la fecha de referencia (Máximo: 500 caracteres)" />
                                <span class="error-message" style="display: none;"
                                    id="mensajeErrorFechaReferencia">Máximo: 500 caracteres</span>
                            </div>
                        </form>
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

    {{-- ! MODAL DE EDICION DE NOTAS --}}
    <div class="modal fade" id="modalEditarNota" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="font-family: Roboto-Black; background-color: #6dc7be; color: #fff">
                    <button type="button" class="close" data-dismiss="modal">
                        <i class="fa fa-times"></i>
                    </button>
                    <h3 class="modal-title" id="myModalLabel">| EDITAR NOTAS </h3>
                </div>
                <div id="colapsado" class="collapse in">
                    <div class="modal-body">
                        <form class="row" id="formularioEditarNota">
                            {{-- ! FORMADO DE NOTA --}}
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="alert alert-info"
                                        style="margin-bottom: 20px;  margin-top:20px; border-left: 4px solid #17a2b8; background-color: #d1ecf1; border-color: #bee5eb;">
                                        <i class="fa fa-info-circle" style="margin-right: 8px; color: #0c5460;"></i>
                                        <strong>Información importante:</strong> Para editar la información de las notas
                                        rellene solo los campos que desea actualizar, no es necesario rellenar todo el
                                        formulario. <br>
                                        Si quiere modificar el número de nota debe completar los campos <strong>NRO DE NOTA
                                            y TIPO
                                            DE NOTA</strong>, de otra forma el cambio no se efectuará.
                                    </div>
                                </div>
                                {{-- ! NRO DE NOTA --}}
                                <div class="col-lg-4">
                                    <h5>Nro de nota</h5>
                                    <input id="nroNotaEditar" class="form-control" type="number" required
                                        placeholder="Mínimo 3 dígitos (Por ejemplo: 001)" />
                                    <span class="error-message" id="mensajeErrorNroNotaEditar"
                                        style="display: none;">Este
                                        campo debe
                                        tener como mínimo 3 dígitos</span>
                                </div>
                                {{-- ! TIPO DE NOTA --}}
                                <div class="col-lg-4">
                                    <h5>Tipo de nota</h5>
                                    <select id="tipoNotaEditar" class="form-control" required>
                                        <option value="" selected disabled>-- Seleccione un tipo de nota --</option>
                                        @foreach ($tipos_nota as $tipo)
                                            <option value="{{ $tipo->id_tipo_nota }}">{{ $tipo->tipo_nombre }}</option>
                                        @endforeach
                                    </select>
                                    <span class="error-message" style="display: none;"
                                        id="mensajeErrorTipoNotaEditar">Este
                                        campo es obligatorio si quiere cambiar el NRO DE NOTA</span>
                                </div>
                                {{-- ! AÑO DE NOTA --}}
                                <div class="col-lg-4">
                                    <h5>Año de nota</h5>
                                    <input id="anioNotaEditar" class="form-control" type="number"
                                        value="{{ $anio }}" disabled required />
                                    <span class="error-message" style="display: none;"
                                        id="mensajeErrorAnioNotaEditar">Este
                                        campo es obligatorio</span>
                                </div>
                                <div class="col-lg-12"><span class="error-message" id="mensajeErrorModificarNroNota"
                                        style="display: none;">Si
                                        quiere modificar el
                                        número de nota debe completar los campos <strong>NRO DE NOTA
                                            y TIPO
                                            DE NOTA</strong>, de otra forma el cambio no se efectuará.</span></div>
                            </div>
                            {{-- ! NOMBRE DEL EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Nombre del evento</h5>
                                <input id="nombreEventoEditar" class="form-control" required maxlength="1000"
                                    placeholder="Ingrese el nombre del evento (Máximo: 1000 caracteres)" />
                                <span class="error-message" style="display: none;"
                                    id="mensajeErrorNombreEventoEditar">Este
                                    campo puede tener como máximo 1000 caracteres</span>
                            </div>
                            {{-- ! TIPO EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Tipo evento</h5>
                                <select id="tipoEventoEditar" class="form-control" required>
                                    <option value="" selected disabled>-- Seleccione un tipo de evento --</option>
                                    @foreach ($tipos_evento as $tipo)
                                        <option value="{{ $tipo->idtipoevento }}">{{ $tipo->tipo_nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- ! CATEGORIA --}}
                            <div class="col-lg-12">
                                <h5>Categoría</h5>
                                <select id="categoriaEditar" name="categoria" class="form-control" required>
                                    <option value="" selected disabled>-- Seleccione una categoría --</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->idcategoria }}">{{ $categoria->categoria }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- ! ADJUNTO NOTAS, TODO LO QUE DICE PAUTAS EN TODO EL CODIGO ES DE LAS NOTAS --}}
                            <div class="col-lg-12">
                                <h5>Adjunto NOTAS</h5>
                                <div class="custom-file">
                                    <input id="adjuntoPautasEditar" name="adjuntoPautas" data-borrado="false"
                                        class="custom-file-input" type="file" accept=".pdf,.zip"
                                        style="display:none;" />
                                    <button type="button" id="adjuntoPautasBtnEditar"
                                        class="btn btn-primary">Seleccionar
                                        archivo</button>
                                    <span id="adjuntoPautasNameEditar" class="ms-2">Ningún archivo seleccionado</span>
                                    <button id="eliminarAdjuntoPautasEditar" type="button"
                                        class="btn btn-danger btn-sm ms-2">Eliminar</button>
                                </div>
                                <span class="error-message" style="display: none;"
                                    id="mensajeErrorAdjuntoPautasEditar">El
                                    archivo seleccionado es demasiado grande. El tamaño máximo permitido es de 150
                                    MB.</span>
                            </div>
                            {{-- ! ADJUNTO DISEÑO --}}
                            <div class="col-lg-12">
                                <h5>Adjunto DISEÑO</h5>
                                <div>
                                    <input id="adjuntoDisenioEditar" name="adjuntoDisenio" type="file"
                                        accept=".pdf,.zip" class="custom-file-input" style="display:none;" />
                                    <button type="button" id="adjuntoDisenioBtnEditar"
                                        class="btn btn-primary">Seleccionar
                                        archivo</button>
                                    <span id="adjuntoDisenioNameEditar" class="ms-2">Ningún archivo seleccionado</span>
                                    <button id="eliminarAdjuntoDisenioEditar" type="button"
                                        class="btn btn-danger btn-sm ms-2">Eliminar</button>
                                </div>
                                <span class="error-message" style="display: none;"
                                    id="mensajeErrorAdjuntoDisenioEditar">El
                                    archivo seleccionado es demasiado grande. El tamaño máximo permitido es de 150
                                    MB.</span>
                            </div>
                            {{-- ! ADJUNTO BASES Y COND. --}}
                            <div class="col-lg-12">
                                <h5>Adjunto bases y condiciones</h5>
                                <div>
                                    <input id="basesyCondicionesEditar" type="file" accept=".pdf,.zip,.doc,.docx"
                                        class="custom-file-input" style="display:none;" />
                                    <button type="button" id="basesyCondicionesBtnEditar"
                                        class="btn btn-primary">Seleccionar
                                        archivo</button>
                                    <span id="basesyCondicionesNameEditar" class="ms-2">Ningún archivo
                                        seleccionado</span>
                                    <button id="eliminarBasesyCondicionesEditar" type="button"
                                        class="btn btn-danger btn-sm ms-2">Eliminar</button>
                                </div>
                                <span class="error-message" style="display: none;"
                                    id="mensajeErrorBasesyCondicionesEditar">El
                                    archivo seleccionado es demasiado grande. El tamaño máximo permitido es de 150
                                    MB.</span>
                            </div>
                            {{-- ! FECHA INICIO EVENTO --}}
                            <div class="col-lg-12">
                                <div class="alert alert-info"
                                    style="margin-bottom: 20px;  margin-top:20px; border-left: 4px solid #17a2b8; background-color: #d1ecf1; border-color: #bee5eb;">
                                    <i class="fa fa-info-circle" style="margin-right: 8px; color: #0c5460;"></i>
                                    <strong>Información importante:</strong> Si quiere modificar las fechas del evento debe
                                    seleccionar una nueva fecha de inicio y de fin. Si selecciona solo una, el cambio no se
                                    efectuará.
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <h5>Fecha inicio evento</h5>
                                <input id="fechaInicioEditar" class="form-control" type="date" required />
                                <span class="error-message" style="display: none;"
                                    id="mensajeErrorFechaInicioEditar">Para modificar las fechas debe seleccionar
                                    ambas</span>
                            </div>
                            {{-- !FECHA FINALIZACION EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Fecha finalización evento</h5>
                                <input id="fechaFinalizacionEditar" class="form-control" type="date" required />
                                <span class="error-message" style="display: none;"
                                    id="mensajeErrorFechaFinalizacionEditar">Para modificar las fechas debe seleccionar
                                    ambas</span>
                            </div>
                            {{-- ! FECHA REFERENCIA EVENTO --}}
                            <div class="col-lg-12">
                                <h5>Fecha referencia evento</h5>
                                <input id="fechaReferenciaEditar" class="form-control" maxlength="500"
                                    placeholder="Ingrese la fecha de referencia (Máximo: 500 caracteres)" />
                                <span class="error-message" style="display: none;"
                                    id="mensajeErrorFechaReferenciaEditar">Máximo: 500 caracteres</span>
                            </div>
                            <div class="col-lg-12">
                                <span class="error-message" style="display: none;" id="mensajeErrorFormVacio">El
                                    formulario está vacío</span>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer" style="padding-top: 7px;">
                    <button id="btn-guardar-nota-editar" type="button" value="add"></button>
                    <button id="btn-cancelar-nota-editar" type="button" class="btn btn-default" id="btn-salir"
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
