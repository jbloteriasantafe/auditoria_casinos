@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
<link rel="stylesheet" href="/css/hyper_modal.css?v={{ time() }}">
<!-- CSS Inlined for reliability -->
<style>
    /* --- HYPER-MODERN INLINE STYLES (DENSE MODE) --- */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    .modal-content {
        border-radius: 15px; /* Reduced from 20px */
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        font-family: 'Inter', sans-serif;
    }
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        padding: 10px 20px; /* Reduced padding */
        border-radius: 15px 15px 0 0;
    }
    .modal-title { font-weight: 700; letter-spacing: 0.5px; font-size: 20px; color:white !important; } /* Smaller title */
    .close { color: white !important; opacity: 0.8; text-shadow: none; font-size: 24px; font-weight: 300; margin-top: -2px; }
    
    .modal-body { padding: 15px 25px; background-color: #f8fafc; } /* Reduced padding */
    .modal-footer { border-top: 1px solid #e2e8f0; padding: 10px 20px; background-color: #f8fafc; border-radius: 0 0 15px 15px; }

    /* Stepper (Restored Structure + Dense Mode) */
    .stepper-wrapper { display: flex; justify-content: space-between; margin-bottom: 25px; position: relative; }
    .stepper-wrapper::before { content: ''; position: absolute; top: 14px; left: 0; width: 100%; height: 2px; background: #e2e8f0; z-index: 0; }
    
    .stepper-item { position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; z-index: 1; }
    
    .step-counter { 
        width: 30px; height: 30px; /* Dense but circular */
        border-radius: 50%; 
        background: white; 
        border: 2px solid #e2e8f0; 
        color: #94a3b8; 
        display: flex; justify-content: center; align-items: center; 
        font-weight: 600; 
        font-size: 13px;
        margin-bottom: 5px; 
        transition: all 0.3s ease; 
    }
    .step-name { font-size: 12px; color: #94a3b8; font-weight: 500; }
    
    .stepper-item.active .step-counter { 
        border-color: #764ba2; background: #764ba2; color: white; 
        box-shadow: 0 0 0 4px rgba(118, 75, 162, 0.2); 
    }
    .stepper-item.active .step-name { color: #764ba2; font-weight: 700; }
    .stepper-item.completed .step-counter { border-color: #10b981; background: #10b981; color: white; }

    /* Inputs Compact */
    .form-group { margin-bottom: 12px; }
    .form-control { 
        border-radius: 8px; border: 1px solid #e2e8f0; padding: 6px 12px; height: 36px; font-size: 13px; box-shadow: none; 
    }
    label { color: #475569; font-weight: 600; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .row { margin-bottom: 0px; }

    /* Buttons Compact */
    .btn { border-radius: 8px; padding: 8px 20px; font-size: 13px; }
    .btn-success { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; 
        box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3); 
        color: white !important; 
        border: none !important;
    }
    .btn-primary { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
        box-shadow: 0 4px 6px -1px rgba(118, 75, 162, 0.3); 
        color: white !important;
        border: none !important;
    }

    /* Chat Bubbles */
    .burbuja-chat { border-radius: 15px; padding: 10px 15px; margin-bottom: 10px; max-width: 80%; }
    .burbuja-propia { background-color: #dcf8c6; float: right; clear: both; }
    .burbuja-ajena { background-color: #f1f0f0; float: left; clear: both; }

    /* Modern Dropzones */
    .dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        background-color: #f8fafc;
        transition: all 0.3s ease;
        position: relative;
        cursor: pointer;
    }
    .dropzone:hover {
        border-color: #764ba2;
        background-color: #f3f4f6;
        transform: translateY(-2px);
    }
    .dropzone label {
        display: block;
        margin-bottom: 10px;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .dropzone .input-group {
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        border-radius: 8px;
        overflow: hidden;
    }
    .dropzone .form-control {
        background: white;
        border: 1px solid #e2e8f0;
        height: 40px;
    }
    .dropzone .btn {
        padding: 8px 15px;
        border-radius: 0 8px 8px 0;
    }
</style>
<script>
    // Global function called by Card Clicks
    function selectTaskType(type) {
        // Specify mode to backend/hidden input
        $('#selTipoTarea').val(type);
        
        // Highlight selected card
        $('.card-type').css('border-color', 'transparent').css('background', 'white');
        $('[onclick="selectTaskType(\''+type+'\')"] .card-type').css('border-color', '#3b82f6').css('background', '#eff6ff');

        // Toggle Sections (Simple Show/Hide)
        if(type === 'MARKETING') {
            $('.section-marketing').show();
            $('.section-fiscalizacion').hide();
            
            // Required Logic (Disable hidden inputs so HTML5 validation ignores them)
            $('.section-fiscalizacion select').prop('disabled', true);
            $('.section-marketing select').prop('disabled', false);
            
        } else {
            $('.section-marketing').hide();
            $('.section-fiscalizacion').show();

            // Required Logic
            $('.section-marketing select').prop('disabled', true);
            $('.section-fiscalizacion select').prop('disabled', false);
        }

        // Show Wizard Step 1
        $('#step1Content').fadeIn();
    }
</script>

<style>
/* === MODAL DETALLE TRAMITE === */
#modalDetalleTramite .modal-body {
    padding: 0 !important;
    background: #f1f5f9;
}
#modalDetalleTramite .tab-content {
    padding: 20px;
}
/* Panel styles with visible borders */
#modalDetalleTramite .panel {
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
    margin-bottom: 15px;
    border-radius: 8px;
    overflow: hidden;
}
#modalDetalleTramite .panel-heading {
    padding: 12px 15px;
    font-weight: 600;
    font-size: 13px;
    border-bottom: 1px solid rgba(255,255,255,0.2);
    margin: 0;
}
#modalDetalleTramite .panel-body {
    padding: 15px;
    background: white;
    overflow: hidden;
}
/* Table in panels */
#modalDetalleTramite .table {
    margin-bottom: 0;
}
#modalDetalleTramite .table td {
    padding: 8px 10px;
    border-color: #f1f5f9;
    word-break: break-word;
}
#modalDetalleTramite .table td:first-child {
    color: #64748b;
    font-size: 12px;
    white-space: nowrap;
}
/* Fix 2-column layout */
#modalDetalleTramite .tab-pane > .row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}
#modalDetalleTramite .col-md-7 {
    flex: 0 0 58.333%;
    max-width: 58.333%;
    padding: 0 10px;
}
#modalDetalleTramite .col-md-5 {
    flex: 0 0 41.667%;
    max-width: 41.667%;
    padding: 0 10px;
}
/* Adjuntos list styling */
#modalDetalleTramite .adjuntos-lista > div {
    transition: all 0.2s ease;
    border-radius: 6px;
    margin-bottom: 8px;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    overflow: hidden;
}
#modalDetalleTramite .adjuntos-lista > div:hover {
    transform: translateX(3px);
    border-color: #cbd5e1;
}
/* Nombre de archivo más legible */
#modalDetalleTramite .adjuntos-lista .nombre-archivo {
    max-width: 280px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: inline-block;
    vertical-align: middle;
    font-size: 12px;
}
/* Comment area */
#modalDetalleTramite .comentarios-lista {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px;
    background: #fafafa;
    min-height: 80px;
}
#modalDetalleTramite .input-comentario {
    border-radius: 20px !important;
    padding-left: 15px;
    border: 1px solid #e2e8f0;
}
#modalDetalleTramite .btn-enviar-comentario {
    border-radius: 0 20px 20px 0 !important;
}
/* Timeline styling */
#modalDetalleTramite .timeline-movimientos {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px;
    background: #fafafa;
}
#modalDetalleTramite .timeline-movimientos > div {
    font-size: 12px;
    padding: 8px 0;
    border-bottom: 1px dashed #e2e8f0;
}
#modalDetalleTramite .timeline-movimientos > div:last-child {
    border-bottom: none;
}
</style>
@endsection

@section('contenidoVista')

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Gestión Unificada de Notas y Solicitudes</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <button id="btn-nueva-nota" class="btn btn-success" type="button" data-toggle="modal" data-target="#modalNuevaNota">
                            <i class="fa fa-plus"></i> Nueva Nota
                        </button>
                    </div>
                </div>
                <br>
                <!-- TOOLBAR (Search & Filters) -->
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="inpBusqueda" placeholder="Buscar por Nota, Título...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="selFiltroCasino">
                            <option value="">- Todos los Casinos -</option>
                            @foreach($casinos as $c)
                                <option value="{{ $c->id_casino }}">{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="selFiltroTipo">
                            <option value="">- Todos los Tipos -</option>
                            <option value="EVENTO">Evento</option>
                            <option value="PUBLICIDAD">Publicidad</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-right">
                         <!-- View Toggles -->
                         <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-default active" id="btnViewTable">
                                <input type="radio" name="options" autocomplete="off" checked> <i class="fa fa-list"></i>
                            </label>
                            {{-- Kanban y Calendario deshabilitados por ahora --}}
                            {{-- <label class="btn btn-default" id="btnViewKanban">
                                <input type="radio" name="options" autocomplete="off"> <i class="fa fa-columns"></i>
                            </label>
                            <label class="btn btn-default" id="btnViewCalendar">
                                <input type="radio" name="options" autocomplete="off"> <i class="fa fa-calendar"></i>
                            </label> --}}
                        </div>
                    </div>
                </div>

                <!-- Saved Filters (Quick Views) -->
                <div class="row" style="margin-bottom: 10px;">
                    <div class="col-md-12">
                        <span class="text-muted" style="margin-right: 10px;"><i class="fa fa-filter"></i> Vistas Rápidas:</span>
                        <button class="btn btn-xs btn-default btn-quick-filter" data-filter="pendientes">Mis Pendientes</button>
                        <button class="btn btn-xs btn-default btn-quick-filter" data-filter="hoy">Ingresadas Hoy</button>
                        <button class="btn btn-xs btn-default btn-quick-filter" data-filter="eventos_activos">Eventos Activos</button>
                        <button class="btn btn-xs btn-link btn-quick-filter" data-filter="reset">Limpiar Filtros</button>
                    </div>
                </div>

                <!-- TABLE CONTAINER (Partial View) -->
                <div id="divTablaNotas">
                    @include('Unified.tabla_notas')
                </div>
                
                <!-- CALENDAR CONTAINER -->
                <div id="divCalendarioNotas" style="display:none; background:white; padding:20px; border:1px solid #ddd;"></div>

                <!-- DRAWER (Off-Canvas Details) -->
                <div id="drawer-right" class="drawer-right" style="position:fixed; top:0; right:-500px; width:500px; height:100%; background:white; z-index:9999; box-shadow:-2px 0 5px rgba(0,0,0,0.2); transition:right 0.3s; padding:20px; overflow-y:auto; display: block;">
                    <button type="button" class="close" id="btnCloseDrawer" style="font-size: 30px;">&times;</button>
                    <div id="drawer-content">
                        <!-- Content loaded via AJAX -->
                        <h4 class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando...</h4>
                    </div>
                </div>
                <div id="drawer-backdrop" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); z-index:9998;"></div>

                <!-- Custom Context Menu -->
                <ul id="custom-context-menu" class="dropdown-menu" role="menu" style="display:none; position:fixed; z-index:10000; width: 200px; box-shadow: 2px 2px 10px rgba(0,0,0,0.2);">
                    <li><a href="#" class="ctx-action" data-action="ver"><i class="fa fa-eye"></i> Ver Detalle</a></li>
                    <li><a href="#" class="ctx-action" data-action="descargar-todo"><i class="fa fa-download"></i> Descargar Todo</a></li>
                    <li class="divider"></li> 
                    <li><a href="#" class="ctx-action text-danger" data-action="eliminar"><i class="fa fa-trash"></i> Eliminar</a></li>
                </ul>
                
                <!-- Bulk Actions Toolbar (Floating) -->
                <div id="bulkToolbar" style="display:none; position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:white; padding:10px 20px; border-radius:30px; box-shadow:0 10px 25px rgba(0,0,0,0.2); z-index:9990; align-items:center; gap:15px; border:1px solid #e2e8f0;">
                    <span style="font-weight:600; color:#475569; font-size:13px;"><span id="bulkCount">0</span> Seleccionados</span>
                    <div style="height:20px; width:1px; background:#e2e8f0;"></div>
                    <button class="btn btn-danger btn-sm" id="btnBulkDelete" style="border-radius:20px; padding:5px 15px;"><i class="fa fa-trash"></i> Eliminar</button>
                    <button class="btn btn-default btn-sm" id="btnBulkCancel" style="border-radius:20px; border:none; color:#94a3b8;"><i class="fa fa-times"></i></button>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaNota" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document" style="width: 60%;">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0; padding: 20px 30px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:0.8;"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title" id="myModalLabel" style="font-weight: 600;">Iniciar Nuevo Trámite</h3>
            </div>
            <div class="modal-body" style="padding: 40px 50px; background-color: #fcfcfc;">
                
                <!-- CIRCULAR PROGRESS (Hyper-Modern) -->
                <div class="circular-progress-container">
                    <div class="stepper-line">
                        <div class="stepper-progress-fill" id="progressFill"></div>
                    </div>
                    
                    <!-- Steps Circles -->
                    <div class="stepper-circle active" id="stepIndicator0" style="left: 0%;">
                        <i class="fa fa-mouse-pointer"></i>
                    </div>
                    <div class="stepper-circle" id="stepIndicator1" style="left: 25%;">
                        1
                    </div>
                    <div class="stepper-circle" id="stepIndicator2" style="left: 50%;">
                        2
                    </div>
                    <div class="stepper-circle" id="stepIndicator3" style="left: 75%;">
                        <i class="fa fa-paperclip"></i>
                    </div>
                    <div class="stepper-circle" id="stepIndicator4" style="left: 100%;">
                        <i class="fa fa-flag-checkered"></i>
                    </div>
                </div>
                
                <form id="frmNuevaNota" style="padding-bottom: 20px; padding-left: 30px; padding-right: 30px;">
                    {{ csrf_field() }}
                    <input type="hidden" name="id_grupo_existente" id="idGrupoExistente" value="">

                    <!-- STEP 0: TYPE SELECTION (CARDS) -->
                    <div id="step0Content">
                        <h4 class="text-center" style="margin-bottom:30px; font-weight:300; color:#64748b;">Seleccione el tipo de trámite que desea inscribir</h4>
                        <div class="row" style="display:flex; justify-content:center; gap:20px;">
                            
                            <!-- Card Marketing -->
                            <div class="col-md-5" style="cursor:pointer;" onclick="selectTaskType('MARKETING')">
                                <div class="panel panel-default card-type" style="border-radius:15px; border:2px solid transparent; transition:all 0.3s; text-align:center; padding:30px;">
                                    <div style="background:#eff6ff; width:80px; height:80px; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center;">
                                        <i class="fa fa-bullhorn fa-3x" style="color:#3b82f6;"></i>
                                    </div>
                                    <h4 style="font-weight:700; color:#1e293b;">Marketing / Publicidad</h4>
                                    <p class="text-muted">Gestión de eventos, promociones y pautas publicitarias.</p>
                                </div>
                            </div>

                            <!-- Card Fiscalización -->
                            <div class="col-md-5" style="cursor:pointer;" onclick="selectTaskType('FISCALIZACION')">
                                <div class="panel panel-default card-type" style="border-radius:15px; border:2px solid transparent; transition:all 0.3s; text-align:center; padding:30px;">
                                    <div style="background:#f0fdf4; width:80px; height:80px; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center;">
                                        <i class="fa fa-cogs fa-3x" style="color:#10b981;"></i>
                                    </div>
                                    <h4 style="font-weight:700; color:#1e293b;">Fiscalización / Técnico</h4>
                                    <p class="text-muted">Movimientos de máquinas, cambios de layout y técnica.</p>
                                </div>
                            </div>

                        </div>
                        <input type="hidden" name="tipo_tarea" id="selTipoTarea">
                    </div>

                    <!-- STEP 1: GENERAL DATA -->
                    <div id="step1Content" style="display:none; max-width: 850px; margin: 0 auto;">
                        <h4 class="step-title">Datos Administrativos</h4>
                        <div class="row">
                            <!-- Compressed Row 1: Nota / Año / Casino -->
                            <div class="col-md-4">
                                <label>Nro. Nota * <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Número que figura en la carátula física del expediente o nota."></i></label>
                                <input type="text" class="form-control" name="nro_nota" id="inpNroNota" required placeholder="Ej: 1540">
                            </div>
                            <div class="col-md-3">
                                <label>Año * <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Año de emisión de la nota."></i></label>
                                <input type="number" class="form-control" name="anio" id="inpAnio" value="{{ date('Y') }}" required>
                            </div>
                            <div class="col-md-5">
                                <label>Casino * <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Casino al que afecta este trámite."></i></label>
                                <select class="form-control" name="id_casino" id="selCasino" required>
                                    @foreach($casinos as $c)
                                    <option value="{{ $c->id_casino }}" data-nombre="{{ $c->nombre }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                         </div>
                    
                        </div>
                    </div>
                
                    <!-- STEP 2: CLASSIFICATIONS & DATES -->
                    <div id="step2Content" style="display:none; max-width: 850px; margin: 0 auto;">
                        <h4 class="step-title">Clasificación y Fechas</h4>

                        <!-- DYNAMIC FIELDS: MARKETING -->
                        <div class="row section-marketing">
                             <div class="col-md-6">
                                 <label>Tipo Solicitud * <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Define si es una pauta publicitaria o un evento presencial."></i></label>
                                 <select class="form-control" name="tipo_solicitud" id="selTipoSolicitud">
                                     <option value="PUBLICIDAD">Publicidad</option>
                                     <option value="EVENTO">Evento / Promo</option>
                                 </select>
                            </div>
                        </div>
    
                        <!-- DYNAMIC FIELDS: MARKETING -->
                        <div class="row section-marketing">
                             <div class="col-md-6">
                                 <label>Tipo Evento (MKT) *</label>
                                 <select class="form-control" name="id_tipo_evento_mkt" id="selTipoEventoMKT">
                                     <option value="">-- Seleccione --</option>
                                     @foreach($tipos_evento as $t)
                                        @if(($t->tipo_tarea ?? 'MKT') == 'MKT')
                                            <option value="{{ $t->idtipoevento }}">{{ $t->tipo_nombre }}</option>
                                        @endif
                                     @endforeach
                                 </select>
                            </div>
                             <div class="col-md-6">
                                 <label>Categoría (MKT) *</label>
                                 <select class="form-control" name="id_categoria_mkt" id="selCategoriaMKT">
                                     <option value="">-- Seleccione --</option>
                                     @foreach($categorias as $c)
                                        @if(($c->tipo_tarea ?? 'MKT') == 'MKT')
                                            <option value="{{ $c->idcategoria }}">{{ $c->categoria }}</option>
                                        @endif
                                     @endforeach
                                 </select>
                            </div>
                        </div>

                        <!-- DYNAMIC FIELDS: FISCALIZACION -->
                        <div class="row section-fiscalizacion" style="display:none;">
                             <div class="col-md-6">
                                 <label>Tipo Evento (Técnico) *</label>
                                 <select class="form-control" name="id_tipo_evento_fisc" id="selTipoEventoFISC">
                                     <option value="">-- Seleccione --</option>
                                     @foreach($tipos_evento as $t)
                                        @if(($t->tipo_tarea ?? 'MKT') == 'FISC')
                                            <option value="{{ $t->idtipoevento }}">{{ $t->tipo_nombre }}</option>
                                        @endif
                                     @endforeach
                                 </select>
                            </div>
                             <div class="col-md-6">
                                 <label>Categoría (Técnico) *</label>
                                 <select class="form-control" name="id_categoria_fisc" id="selCategoriaFISC">
                                     <option value="">-- Seleccione --</option>
                                     @foreach($categorias as $c)
                                        @if(($c->tipo_tarea ?? 'MKT') == 'FISC')
                                            <option value="{{ $c->idcategoria }}">{{ $c->categoria }}</option>
                                        @endif
                                     @endforeach
                                 </select>
                            </div>
                        </div>

                        <div class="row" style="margin-top:20px;">
                            <div class="col-md-12">
                                <label>Título / Asunto * <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Breve descripción identificatoria del trámite."></i></label>
                                <input type="text" class="form-control" name="titulo" id="inpTitulo" required maxlength="255" placeholder="Descripción breve...">
                            </div>
                        </div>

                        <!-- DATE FIELDS -->
                        <div class="row" style="margin-top:15px; margin-bottom: 30px;">
                            <div class="col-md-6">
                                <label>Fecha Inicio <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Cuándo comienza la vigencia."></i></label>
                                <input type="date" class="form-control" name="fecha_inicio_evento" id="inpFechaInicio">
                            </div>
                            <div class="col-md-6">
                                <label>Fecha Fin <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Cuándo finaliza la vigencia (opcional)."></i></label>
                                <input type="date" class="form-control" name="fecha_fin_evento" id="inpFechaFin">
                            </div>
                        </div>
                        
                        <div class="row section-fiscalizacion" style="margin-top:10px; display:none;">
                            <div class="col-md-12">
                                <label>Fecha Referencia <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Fecha histórica o de referencia externa."></i></label>
                                <input type="text" class="form-control" name="fecha_referencia" placeholder="Texto libre o fecha">
                            </div>
                        </div>

                        <!-- ASSETS (Previously Step 1) -->
                        <div id="secActivosAsociados" style="margin-top:30px; display:none; padding-bottom: 20px;">
                            <hr>
                            <h5 style="color:#64748b; margin-bottom: 15px;">Activos Asociados <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Seleccione máquinas, islas o mesas relacionadas."></i></h5>
                             <div class="row" style="background:#f8fafc; padding:20px; border-radius:10px; border: 1px solid #e2e8f0;">
                                 <div class="col-md-4">
                                     <select class="form-control" id="selTipoActivo">
                                         <!-- JS Fill -->
                                     </select>
                                 </div>
                                 <div class="col-md-6" style="position: relative;">
                                     <input type="text" id="inpIdActivo" class="form-control" placeholder="Buscar..." autocomplete="off">
                                     <input type="hidden" id="hidIdActivo"> 
                                     <div id="resultadosBusqueda" class="list-group" style="position: absolute; top: 100%; left: 15px; right: 15px; z-index: 10000; max-height: 200px; overflow-y: auto; display: none; box-shadow: 0px 10px 20px rgba(0,0,0,0.15);"></div>
                                 </div>
                                 <div class="col-md-2">
                                     <button type="button" class="btn btn-primary btn-block" id="btnAgregarActivo" style="padding:6px;">Agregar</button>
                                 </div>
                            </div>
                            <table class="table table-condensed table-striped" id="tablaActivos" style="margin-top:5px; font-size:12px;">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>ID</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Spacer explicitly for bottom margin -->
                        <div style="height: 50px; width: 100%; clear: both;"></div>

                    </div>
                    
                    <!-- STEP 3: ADJUNTOS (Nueva Estructura) -->
                    <div id="step3Content" style="display:none; max-width: 900px; margin: 0 auto; padding-bottom: 30px;">
                        <input type="hidden" id="hidIdNotaFisc" name="id_nota_fisc">
                        <input type="hidden" id="hidIdNotaMkt" name="id_nota_mkt">

                        <!-- =====================================================
                             MKT UPLOADS (Marketing)
                        ====================================================== -->
                        <div class="section-marketing" style="margin-bottom: 30px;">
                            <h5 style="color: #3b82f6; border-bottom: 2px solid #3b82f6; padding-bottom: 8px; margin-bottom: 20px;">
                                <i class="fa fa-bullhorn"></i> Adjuntos Marketing (MKT)
                            </h5>
                            
                            <div class="row">
                                <!-- Solicitud Concesionario MKT -->
                                <div class="col-md-4">
                                    <div class="dropzone" style="border: 2px dashed #3b82f6; border-radius: 10px; padding: 15px; text-align: center; min-height: 120px;">
                                        <label style="font-weight: 600; color: #1e40af;"><i class="fa fa-file-pdf-o"></i> Solicitud Concesionario</label>
                                        <input id="adjuntoSolicitud" name="adjuntoSolicitud" type="file" class="form-control" accept=".pdf,.zip" style="margin-top:10px;">
                                        <small class="text-muted">PDF o ZIP</small>
                                    </div>
                                </div>
                                <!-- Diseño MKT -->
                                <div class="col-md-4">
                                    <div class="dropzone" style="border: 2px dashed #3b82f6; border-radius: 10px; padding: 15px; text-align: center; min-height: 120px;">
                                        <label style="font-weight: 600; color: #1e40af;"><i class="fa fa-image"></i> Adjunto Diseño</label>
                                        <input id="adjuntoDisenio" name="adjuntoDisenio" type="file" class="form-control" accept=".pdf,.zip,.jpg,.png" style="margin-top:10px;">
                                        <small class="text-muted">PDF, ZIP, JPG, PNG</small>
                                    </div>
                                </div>
                                <!-- Bases y Condiciones MKT -->
                                <div class="col-md-4">
                                    <div class="dropzone" style="border: 2px dashed #3b82f6; border-radius: 10px; padding: 15px; text-align: center; min-height: 120px;">
                                        <label style="font-weight: 600; color: #1e40af;"><i class="fa fa-file-text-o"></i> Bases y Condiciones</label>
                                        <input id="adjuntoBases" name="adjuntoBases" type="file" class="form-control" accept=".pdf,.doc,.docx,.zip" style="margin-top:10px;">
                                        <small class="text-muted">PDF, DOC, DOCX, ZIP</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- =====================================================
                             FISC UPLOADS (Fiscalización)
                        ====================================================== -->
                        <div class="section-fiscalizacion" style="display:none; margin-bottom: 30px;">
                            <h5 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 8px; margin-bottom: 20px;">
                                <i class="fa fa-gavel"></i> Adjuntos Fiscalización (FISC)
                            </h5>
                            
                            <div class="row">
                                <!-- Solicitud Concesionario FISC -->
                                <div class="col-md-6">
                                    <div class="dropzone" style="border: 2px dashed #10b981; border-radius: 10px; padding: 15px; text-align: center; min-height: 120px;">
                                        <label style="font-weight: 600; color: #047857;"><i class="fa fa-file-pdf-o"></i> Solicitud Concesionario</label>
                                        <input id="adjuntoSolicitudFisc" name="adjuntoSolicitudFisc" type="file" class="form-control" accept=".pdf,.zip" style="margin-top:10px;">
                                        <small class="text-muted">PDF o ZIP</small>
                                    </div>
                                </div>
                                <!-- Archivos Varios FISC -->
                                <div class="col-md-6">
                                    <div class="dropzone" style="border: 2px dashed #10b981; border-radius: 10px; padding: 15px; text-align: center; min-height: 120px;">
                                        <label style="font-weight: 600; color: #047857;"><i class="fa fa-archive"></i> Archivos Varios</label>
                                        <input id="adjuntoVarios" name="adjuntoVarios" type="file" class="form-control" accept=".zip,.rar,.pdf,.doc,.docx,.xlsx" style="margin-top:10px;">
                                        <small class="text-muted">ZIP con todos los archivos adjuntos</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- =====================================================
                             INFORME TÉCNICO (Común - opcional, instancia posterior)
                        ====================================================== -->
                        <div class="section-informe" style="display:none; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #cbd5e1;">
                            <h5 style="color: #f59e0b; border-bottom: 2px solid #f59e0b; padding-bottom: 8px; margin-bottom: 20px;">
                                <i class="fa fa-clipboard"></i> Informe Técnico (Opcional)
                            </h5>
                            <div class="row">
                                <div class="col-md-6 section-marketing">
                                    <div class="dropzone" style="border: 2px dashed #f59e0b; border-radius: 10px; padding: 15px; text-align: center;">
                                        <label style="font-weight: 600; color: #d97706;"><i class="fa fa-file-text"></i> Informe Técnico (MKT)</label>
                                        <input id="adjuntoInformeMkt" name="adjuntoInformeMkt" type="file" class="form-control" accept=".pdf,.doc,.docx" style="margin-top:10px;">
                                    </div>
                                </div>
                                <div class="col-md-6 section-fiscalizacion" style="display:none;">
                                    <div class="dropzone" style="border: 2px dashed #f59e0b; border-radius: 10px; padding: 15px; text-align: center;">
                                        <label style="font-weight: 600; color: #d97706;"><i class="fa fa-file-text"></i> Informe Técnico (FISC)</label>
                                        <input id="adjuntoInformeFisc" name="adjuntoInformeFisc" type="file" class="form-control" accept=".pdf,.doc,.docx" style="margin-top:10px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: SUMMARY (Merged) -->
                    <div id="step4Content" style="display:none; max-width: 850px; margin: 0 auto;">
                         <h4 class="text-center" style="margin-bottom:20px; font-weight:700; color:#475569;">Resumen de la Solicitud</h4>
                        <div class="alert alert-success text-center">
                            <i class="fa fa-info-circle"></i> Verifique que todos los datos sean correctos antes de confirmar.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar / Finalizar</button>
                <div style="float: right;">
                    <button type="button" class="btn btn-default btn-wizard-prev" style="display:none;" onclick="wizardPrev()">Atrás</button>
                    
                    <!-- NEW COLLAB BUTTON -->
                    <button type="button" class="btn btn-collab" id="btnSolicitarMkt" style="display:none; margin-right: 10px;" onclick="solicitarMarketing()">
                        <i class="fa fa-paper-plane"></i> Solicitar a MKT
                    </button>

                    <button type="button" class="btn btn-success btn-wizard-next" onclick="wizardNext()">Siguiente</button>
                    <button type="button" class="btn btn-success btn-wizard-finish" onclick="wizardFinish()" style="display:none;">Confirmar <i class="fa fa-check"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: AGREGAR ADJUNTOS A NOTA EXISTENTE -->
<div class="modal fade" id="modalAgregarAdjuntos" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 12px 12px 0 0;">
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-paperclip"></i> Agregar Adjuntos - <span id="labelTipoRama"></span></h4>
            </div>
            <div class="modal-body">
                <form id="frmAgregarAdjuntos" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" id="adjNotaId" name="id_nota">
                    <input type="hidden" id="adjTipoRama" name="tipo_rama">
                    
                    <!-- Estado actual de archivos -->
                    <div id="adjuntosActuales" style="background: #f8fafc; border-radius: 8px; padding: 12px; margin-bottom: 20px;">
                        <span class="text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando estado de archivos...</span>
                    </div>
                    
                    <!-- Campos MKT -->
                    <div id="adjCamposMkt" style="display:none;">
                        <h5 style="color: #3b82f6; margin-bottom: 15px;"><i class="fa fa-bullhorn"></i> Adjuntos Marketing</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fa fa-file-pdf-o"></i> Solicitud Concesionario</label>
                                    <input type="file" name="adjuntoSolicitud" class="form-control" accept=".pdf,.zip">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fa fa-image"></i> Diseño</label>
                                    <input type="file" name="adjuntoDisenio" class="form-control" accept=".pdf,.zip,.jpg,.png">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fa fa-file-text-o"></i> Bases y Condiciones</label>
                                    <input type="file" name="adjuntoBases" class="form-control" accept=".pdf,.doc,.docx,.zip">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campos FISC -->
                    <div id="adjCamposFisc" style="display:none;">
                        <h5 style="color: #10b981; margin-bottom: 15px;"><i class="fa fa-gavel"></i> Adjuntos Fiscalización</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fa fa-file-pdf-o"></i> Solicitud Concesionario</label>
                                    <input type="file" name="adjuntoSolicitud" class="form-control" accept=".pdf,.zip">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fa fa-archive"></i> Archivos Varios (ZIP)</label>
                                    <input type="file" name="adjuntoVarios" class="form-control" accept=".zip,.rar,.pdf,.doc,.docx,.xlsx">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informe Técnico (común) -->
                    <div class="row" style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ccc;">
                        <div class="col-md-12">
                            <h5 style="color: #f59e0b;"><i class="fa fa-clipboard"></i> Informe Técnico (Opcional)</h5>
                            <input type="file" name="adjuntoInforme" class="form-control" accept=".pdf,.doc,.docx">
                        </div>
                    </div>
                </form>
                
                <!-- Timeline de Movimientos -->
                <div style="margin-top: 25px; padding-top: 15px; border-top: 2px solid #eee;">
                    <h5><i class="fa fa-history"></i> Historial de Adjuntos</h5>
                    <div id="timelineAdjuntos" style="max-height: 200px; overflow-y: auto;">
                        <p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando historial...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnGuardarAdjuntos">
                    <i class="fa fa-upload"></i> Subir Adjuntos
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: DETALLE/EDITAR TRÁMITE
     ===================================================== -->
<div class="modal fade" id="modalDetalleTramite" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="width: 90%; max-width: 1100px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <!-- Header -->
            <div class="modal-header" id="modalDetalleHeader" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
                <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1; font-size: 28px;">&times;</button>
                <div>
                    <h4 class="modal-title" style="margin: 0; font-weight: 600;">
                        <i class="fa fa-folder-open"></i> <span id="detalleHeaderTitulo">Cargando...</span>
                    </h4>
                    <div style="margin-top: 8px; font-size: 13px; opacity: 0.9;">
                        <span id="detalleHeaderMeta"></span>
                    </div>
                </div>
            </div>
            
            <div class="modal-body" style="padding: 0;">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="detalleTabs" style="background: #f8fafc; padding: 10px 20px 0; margin: 0; border-bottom: 2px solid #e5e7eb;">
                    <li class="active" id="tabGrupoLi">
                        <a href="#tabGrupo" data-toggle="tab" style="font-weight: 600;">
                            <i class="fa fa-folder"></i> Grupo
                        </a>
                    </li>
                    <li id="tabMktLi">
                        <a href="#tabMkt" data-toggle="tab" style="font-weight: 600; color: #3b82f6;">
                            <i class="fa fa-bullhorn"></i> Marketing
                        </a>
                    </li>
                    <li id="tabFiscLi">
                        <a href="#tabFisc" data-toggle="tab" style="font-weight: 600; color: #10b981;">
                            <i class="fa fa-gavel"></i> Fiscalización
                        </a>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content" style="padding: 20px; max-height: 70vh; overflow-y: auto;">
                    
                    <!-- TAB: GRUPO -->
                    <div class="tab-pane fade in active" id="tabGrupo">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="panel panel-default" style="border-radius: 8px;">
                                    <div class="panel-heading" style="background: #667eea; color: white; border-radius: 8px 8px 0 0;">
                                        <i class="fa fa-info-circle"></i> Información del Grupo
                                    </div>
                                    <div class="panel-body" id="grupoInfoPanel">
                                        <p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-default" style="border-radius: 8px;">
                                    <div class="panel-heading" style="background: #f59e0b; color: white; border-radius: 8px 8px 0 0;">
                                        <i class="fa fa-tasks"></i> Resumen de Notas
                                    </div>
                                    <div class="panel-body" id="grupoResumenPanel">
                                        <p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB: MARKETING -->
                    <div class="tab-pane fade" id="tabMkt">
                        <div id="mktContenido">
                            <p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando datos de Marketing...</p>
                        </div>
                    </div>
                    
                    <!-- TAB: FISCALIZACION -->
                    <div class="tab-pane fade" id="tabFisc">
                        <div id="fiscContenido">
                            <p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando datos de Fiscalización...</p>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <div class="modal-footer" style="background: #f8fafc; border-top: 2px solid #e5e7eb;">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarDetalle" style="display: none;">
                    <i class="fa fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template para contenido de nota (MKT o FISC) -->
@verbatim
<script type="text/template" id="templateNotaDetalle">
<div class="row">
    <!-- Columna Izquierda: Datos y Adjuntos -->
    <div class="col-md-7">
        <!-- Datos Generales -->
        <div class="panel panel-default" style="border-radius: 8px; margin-bottom: 15px;">
            <div class="panel-heading" style="background: {{color}}; color: white; border-radius: 8px 8px 0 0;">
                <i class="fa fa-file-text"></i> Datos de la Nota
                <button class="btn btn-xs btn-default pull-right btn-editar-nota" data-id="{{id}}" style="margin-top: -3px;">
                    <i class="fa fa-pencil"></i> Editar
                </button>
            </div>
            <div class="panel-body">
                <table class="table table-condensed" style="margin-bottom: 0;">
                    <tr><td style="width: 140px;"><strong>Nro Nota:</strong></td><td><span class="editable" data-field="nro_nota_ing">{{nro_nota}}</span></td></tr>
                    <tr><td><strong>Tipo Solicitud:</strong></td><td><span class="editable" data-field="tipo_solicitud">{{tipo_solicitud}}</span></td></tr>
                    <tr><td><strong>Descripción:</strong></td><td><span class="editable" data-field="descripcion">{{descripcion}}</span></td></tr>
                    <tr><td><strong>Fecha Inicio:</strong></td><td><span class="editable" data-field="fecha_inicio">{{fecha_inicio}}</span></td></tr>
                    <tr><td><strong>Fecha Fin:</strong></td><td><span class="editable" data-field="fecha_fin">{{fecha_fin}}</span></td></tr>
                    <tr><td><strong>Estado:</strong></td><td><span class="label label-{{estadoClass}}">{{estado}}</span></td></tr>
                </table>
            </div>
        </div>
        
        <!-- Adjuntos -->
        <div class="panel panel-default" style="border-radius: 8px; margin-bottom: 15px;">
            <div class="panel-heading" style="background: #6b7280; color: white; border-radius: 8px 8px 0 0;">
                <i class="fa fa-paperclip"></i> Archivos Adjuntos
                <button class="btn btn-xs btn-success pull-right btn-agregar-adj-modal" data-id="{{id}}" data-tipo-rama="{{tipo_rama}}" style="margin-top: -3px;">
                    <i class="fa fa-plus"></i> Agregar
                </button>
            </div>
            <div class="panel-body" style="padding: 10px;">
                <div class="adjuntos-lista">{{adjuntosHtml}}</div>
            </div>
        </div>
        
        <!-- Activos -->
        <div class="panel panel-default" style="border-radius: 8px;">
            <div class="panel-heading" style="background: #8b5cf6; color: white; border-radius: 8px 8px 0 0;">
                <i class="fa fa-gamepad"></i> Activos Asociados
            </div>
            <div class="panel-body">
                {{activosHtml}}
            </div>
        </div>
    </div>
    
    <!-- Columna Derecha: Comentarios e Historial -->
    <div class="col-md-5">
        <!-- Comentarios -->
        <div class="panel panel-default" style="border-radius: 8px; margin-bottom: 15px;">
            <div class="panel-heading" style="background: #ec4899; color: white; border-radius: 8px 8px 0 0;">
                <i class="fa fa-comments"></i> Comentarios
            </div>
            <div class="panel-body" style="padding: 10px;">
                <div class="comentarios-lista" data-id="{{id}}" style="max-height: 200px; overflow-y: auto; margin-bottom: 10px;">
                    {{comentariosHtml}}
                </div>
                <div class="input-group">
                    <input type="text" class="form-control input-comentario" placeholder="Escribir comentario..." data-id="{{id}}">
                    <span class="input-group-btn">
                        <button class="btn btn-primary btn-enviar-comentario" data-id="{{id}}">
                            <i class="fa fa-send"></i>
                        </button>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Historial -->
        <div class="panel panel-default" style="border-radius: 8px;">
            <div class="panel-heading" style="background: #374151; color: white; border-radius: 8px 8px 0 0;">
                <i class="fa fa-history"></i> Historial de Movimientos
            </div>
            <div class="panel-body" style="padding: 10px; max-height: 250px; overflow-y: auto;">
                <div class="timeline-movimientos">{{historialHtml}}</div>
            </div>
        </div>
    </div>
</div>
</script>
@endverbatim

@endsection

@section('tituloDeAyuda')
<h3 class="modal-title" style="color: #fff;">Ayuda Módulo Unificado</h3>
@endsection
@section('contenidoAyuda')
<div class="col-md-12">
    <p>Gestión de notas para Fiscalización y Marketing.</p>
</div>
@endsection

@section('scripts')
<!-- JS Manual para Wizard -->
<script src="/js/unified_wizard.js?v={{ time() }}"></script>

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<!-- FullCalendar v3 (Compatible with jQuery) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/locale/es.js"></script>
@endsection
