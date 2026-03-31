<div class="row style-drawer-header">
    <div class="col-md-8">
        <h4 style="margin:0;">Nota #{{ $nota->nro_nota }}-{{ $nota->anio }}</h4>
        <small class="text-muted text-uppercase">{{ $nota->tipo_solicitud }}</small>
    </div>
    <div class="col-md-4 text-right">
        @if($nota->expedientes->count() > 0)
            <span class="label label-success">{{ $nota->expedientes->first()->estado_actual }}</span>
        @else
            <span class="label label-warning">PENDIENTE</span>
        @endif
    </div>
</div>
<hr style="margin: 10px 0;">

<div class="drawer-section">
    <label class="text-muted"><i class="fa fa-info-circle"></i> Título / Tema</label>
    <p class="lead" style="font-size: 16px;">{{ $nota->titulo }}</p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="drawer-section">
            <label class="text-muted"><i class="fa fa-building"></i> Casino</label>
            <p><strong>{{ $nota->casino ? $nota->casino->nombre : '---' }}</strong></p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="drawer-section">
            <label class="text-muted"><i class="fa fa-calendar"></i> Fecha Ingreso</label>
            <p>{{ \Carbon\Carbon::parse($nota->fecha_ingreso)->format('d/m/Y') }}</p>
        </div>
    </div>
</div>

@if($nota->tipo_solicitud == 'EVENTO')
<div class="row" style="background: #f9f9f9; padding: 10px; border-radius: 5px; margin: 0 0 15px 0;">
    <div class="col-md-6">
        <label><i class="fa fa-clock-o"></i> Inicio Evento</label><br>
        {{ $nota->fecha_inicio_evento ? \Carbon\Carbon::parse($nota->fecha_inicio_evento)->format('d/m/Y') : '-' }}
    </div>
    <div class="col-md-6">
        <label><i class="fa fa-clock-o"></i> Fin Evento</label><br>
        {{ $nota->fecha_fin_evento ? \Carbon\Carbon::parse($nota->fecha_fin_evento)->format('d/m/Y') : '-' }}
    </div>
</div>
@endif

<h5 style="margin-top: 20px; border-bottom: 2px solid #ddd; padding-bottom: 5px;">Adjuntos</h5>
<div class="list-group">
    @php $pathSolicitud = $nota->path_solicitud ?? $nota->path_pautas; @endphp
    @if($pathSolicitud)
    <div class="list-group-item">
        <i class="fa fa-file-pdf-o fa-lg pull-left text-danger" style="margin-right: 10px;"></i>
        <h5 class="list-group-item-heading">Solicitud Concesionario</h5>
        <p class="list-group-item-text"><small>{{ basename($pathSolicitud) }}</small></p>
        <div class="btn-group btn-group-sm" style="margin-top: 5px;">
            <a href="/notas-unificadas/visualizar/{{ $nota->id }}/solicitud" target="_blank" class="btn btn-primary">
                <i class="fa fa-eye"></i> Ver
            </a>
            <a href="/notas-unificadas/descargar/{{ $nota->id }}/solicitud" class="btn btn-default">
                <i class="fa fa-download"></i> Descargar
            </a>
        </div>
    </div>
    @endif

    @if($nota->path_diseno)
    <div class="list-group-item">
        <i class="fa fa-file-image-o fa-lg pull-left text-primary" style="margin-right: 10px;"></i>
        <h5 class="list-group-item-heading">Diseño</h5>
        <p class="list-group-item-text"><small>{{ basename($nota->path_diseno) }}</small></p>
        <div class="btn-group btn-group-sm" style="margin-top: 5px;">
            <a href="/notas-unificadas/visualizar/{{ $nota->id }}/diseno" target="_blank" class="btn btn-primary">
                <i class="fa fa-eye"></i> Ver
            </a>
            <a href="/notas-unificadas/descargar/{{ $nota->id }}/diseno" class="btn btn-default">
                <i class="fa fa-download"></i> Descargar
            </a>
        </div>
    </div>
    @endif

    @if($nota->path_bases)
    <div class="list-group-item">
        <i class="fa fa-file-text-o fa-lg pull-left text-muted" style="margin-right: 10px;"></i>
        <h5 class="list-group-item-heading">Bases y Condiciones</h5>
        <p class="list-group-item-text"><small>{{ basename($nota->path_bases) }}</small></p>
        <div class="btn-group btn-group-sm" style="margin-top: 5px;">
            <a href="/notas-unificadas/visualizar/{{ $nota->id }}/bases" target="_blank" class="btn btn-primary">
                <i class="fa fa-eye"></i> Ver
            </a>
            <a href="/notas-unificadas/descargar/{{ $nota->id }}/bases" class="btn btn-default">
                <i class="fa fa-download"></i> Descargar
            </a>
        </div>
    </div>
    @endif

    @if($nota->path_varios)
    <div class="list-group-item">
        <i class="fa fa-file-archive-o fa-lg pull-left text-warning" style="margin-right: 10px;"></i>
        <h5 class="list-group-item-heading">Archivos Varios</h5>
        <p class="list-group-item-text"><small>{{ basename($nota->path_varios) }}</small></p>
        <div class="btn-group btn-group-sm" style="margin-top: 5px;">
            <a href="/notas-unificadas/visualizar/{{ $nota->id }}/varios" target="_blank" class="btn btn-primary">
                <i class="fa fa-eye"></i> Ver
            </a>
            <a href="/notas-unificadas/descargar/{{ $nota->id }}/varios" class="btn btn-default">
                <i class="fa fa-download"></i> Descargar
            </a>
        </div>
    </div>
    @endif

    @if($nota->path_informe)
    <div class="list-group-item">
        <i class="fa fa-file-pdf-o fa-lg pull-left text-success" style="margin-right: 10px;"></i>
        <h5 class="list-group-item-heading">Informe Técnico</h5>
        <p class="list-group-item-text"><small>{{ basename($nota->path_informe) }}</small></p>
        <div class="btn-group btn-group-sm" style="margin-top: 5px;">
            <a href="/notas-unificadas/visualizar/{{ $nota->id }}/informe" target="_blank" class="btn btn-primary">
                <i class="fa fa-eye"></i> Ver
            </a>
            <a href="/notas-unificadas/descargar/{{ $nota->id }}/informe" class="btn btn-default">
                <i class="fa fa-download"></i> Descargar
            </a>
            <button class="btn btn-info btn-ver-versiones" data-id="{{ $nota->id }}" data-tipo="informe">
                <i class="fa fa-history"></i> Versiones
            </button>
        </div>
    </div>
    @endif
    
    @if(!($nota->path_solicitud ?? $nota->path_pautas) && !$nota->path_diseno && !$nota->path_bases && !$nota->path_varios && !$nota->path_informe)
        <div class="alert alert-warning">No hay archivos adjuntos.</div>
    @endif
</div>

<div class="drawer-footer" style="margin-top: 30px;">
    <button class="btn btn-default btn-block" onclick="$('#btnCloseDrawer').click()">Cerrar</button>
</div>
@include('Unified.modal_versiones')
