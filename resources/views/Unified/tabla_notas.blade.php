<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered" style="font-size: 13px;">
        <thead>
            <tr>
                <th width="3%" class="text-center">
                    <input type="checkbox" id="checkAll">
                </th>
                <th width="3%" class="text-center">
                    {{-- Expand/Collapse --}}
                </th>
                <th width="8%" class="sortable" data-sort="created_at">Fecha <i class="fa fa-sort"></i></th>
                <th width="10%" class="sortable" data-sort="nro_nota">Nro Nota <i class="fa fa-sort"></i></th>
                <th width="12%" class="sortable" data-sort="id_casino">Casino <i class="fa fa-sort"></i></th>
                <th width="20%">Título / Tema</th>
                <th width="10%">Ramas</th>
                <th width="8%">Estado</th>
                <th width="12%">Acciones</th>
            </tr>
        </thead>
        <tbody>
            {{-- GRUPOS DE TRÁMITE --}}
            @forelse($grupos as $grupo)
            <tr class="grupo-row" data-grupo-id="{{ $grupo->id }}" style="background: #f8fafc; cursor: pointer;">
                <td class="text-center">
                    <input type="checkbox" class="check-grupo" value="{{ $grupo->id }}">
                </td>
                <td class="text-center toggle-grupo">
                    <i class="fa fa-chevron-right toggle-icon"></i>
                </td>
                <td>{{ \Carbon\Carbon::parse($grupo->created_at)->format('d/m/Y') }}</td>
                <td><b>{{ $grupo->nro_nota }}-{{ $grupo->anio }}</b></td>
                <td>
                    @if($grupo->id_casino == 101) City Center Online (CCOL)
                    @elseif($grupo->id_casino == 102) Bplay (BPLAY)
                    @else {{ $grupo->casino ? $grupo->casino->nombre : '---' }}
                    @endif
                </td>
                <td>{{ $grupo->titulo }}</td>
                <td>
                    {{-- Badges para cada rama --}}
                    @foreach($grupo->notas as $nota)
                        @if($nota->tipo_rama == 'MKT')
                            <span class="label label-primary" style="margin-right:3px;">MKT</span>
                        @else
                            <span class="label label-success" style="margin-right:3px;">FISC</span>
                        @endif
                    @endforeach
                </td>
                <td>
                    @php
                        $estados = $grupo->notas->map(function($n) {
                            return $n->expedientes->first() ? $n->expedientes->first()->estado_actual : 'PENDIENTE';
                        })->unique();
                    @endphp
                    @foreach($estados as $est)
                        <span class="label label-info" style="margin-right:2px;">{{ $est }}</span>
                    @endforeach
                </td>
                <td>
                    {{-- Botones de Acción --}}
                    <button class="btn btn-info btn-xs btn-ver-detalle-grupo" data-grupo-id="{{ $grupo->id }}" title="Ver Detalle del Grupo">
                        <i class="fa fa-folder-open"></i>
                    </button>
                    
                    @php
                        $ramas = $grupo->notas->pluck('tipo_rama')->toArray();
                        $faltaMkt = !in_array('MKT', $ramas);
                        $faltaFisc = !in_array('FISC', $ramas);
                    @endphp

                    @if($faltaMkt)
                        <button class="btn btn-primary btn-xs btn-complementar-grupo" data-grupo-id="{{ $grupo->id }}" data-rama="MKT" title="Agregar Nota Marketing">
                            <i class="fa fa-plus"></i> MKT
                        </button>
                    @endif
                    
                    @if($faltaFisc)
                        <button class="btn btn-success btn-xs btn-complementar-grupo" data-grupo-id="{{ $grupo->id }}" data-rama="FISC" title="Agregar Nota Fiscalización">
                            <i class="fa fa-plus"></i> FISC
                        </button>
                    @endif

                    <button class="btn btn-danger btn-xs btn-borrar-grupo" data-id="{{ $grupo->id }}" title="Eliminar Grupo">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            
            {{-- NOTAS HIJAS (Colapsadas por defecto) --}}
            @foreach($grupo->notas as $n)
            <tr class="nota-hija" data-parent-grupo="{{ $grupo->id }}" style="display: none; background: #fff;">
                <td class="text-center">
                    <input type="checkbox" class="check-item" value="{{ $n->id }}">
                </td>
                <td class="text-center" style="border-left: 3px solid {{ $n->tipo_rama == 'MKT' ? '#3b82f6' : '#10b981' }};">
                    <small class="text-muted">↳</small>
                </td>
                <td><small class="text-muted">{{ \Carbon\Carbon::parse($n->fecha_ingreso)->format('d/m/Y') }}</small></td>
                <td>
                    @if($n->tipo_rama == 'MKT')
                        <span class="label label-primary">MKT</span>
                    @else
                        <span class="label label-success">FISC</span>
                    @endif
                </td>
                <td colspan="2">
                    <small><b>{{ $n->tipo_solicitud }}</b></small>
                    @if($n->id_tipo_evento)
                        <br><small class="text-muted">Evt: {{ $n->id_tipo_evento }}</small>
                    @endif
                </td>
                <td>
                    @if($n->expedientes->count() > 0)
                        <span class="label label-success estado-badge" 
                              data-toggle="popover" 
                              data-trigger="hover" 
                              data-id="{{ $n->id }}">
                              {{ $n->expedientes->first()->estado_actual }}
                        </span>
                    @else
                        <span class="label label-warning">PENDIENTE</span>
                    @endif
                </td>
                <td>
                    <button class="btn btn-info btn-xs btn-ver-detalle-nota" 
                            data-nota-id="{{ $n->id }}" 
                            data-tipo-rama="{{ $n->tipo_rama }}" 
                            title="Ver Detalle">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button class="btn btn-warning btn-xs btn-agregar-adjuntos" 
                            data-id="{{ $n->id }}" 
                            data-tipo-rama="{{ $n->tipo_rama }}"
                            title="Agregar/Ver Adjuntos">
                        <i class="fa fa-paperclip"></i>
                    </button>
                    <button class="btn btn-danger btn-xs btn-borrar-nota" data-id="{{ $n->id }}" title="Eliminar">
                        <i class="fa fa-trash"></i>
                    </button>
                    {{-- Indicadores de adjuntos --}}
                    <div style="margin-top: 5px;">
                        @if($n->tipo_rama == 'MKT')
                            <span class="label {{ $n->path_solicitud ? 'label-success' : 'label-default' }}" style="font-size: 9px;" title="Solicitud">SOL</span>
                            <span class="label {{ $n->path_diseno ? 'label-success' : 'label-default' }}" style="font-size: 9px;" title="Diseño">DIS</span>
                            <span class="label {{ $n->path_bases ? 'label-success' : 'label-default' }}" style="font-size: 9px;" title="Bases">BAS</span>
                        @else
                            <span class="label {{ $n->path_solicitud ? 'label-success' : 'label-default' }}" style="font-size: 9px;" title="Solicitud">SOL</span>
                            <span class="label {{ $n->path_varios ? 'label-success' : 'label-default' }}" style="font-size: 9px;" title="Archivos Varios">VAR</span>
                        @endif
                        @if($n->path_informe)
                            <span class="label label-warning" style="font-size: 9px;" title="Informe Técnico">INF</span>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
            @empty
            {{-- No hay grupos, mostrar mensaje --}}
            @endforelse
            
            {{-- NOTAS SUELTAS (Legacy - sin grupo) --}}
            @if(isset($notasSueltas) && $notasSueltas->count() > 0)
                @foreach($notasSueltas as $n)
                <tr data-id="{{ $n->id }}" style="background: #fffbeb;">
                    <td class="text-center">
                        <input type="checkbox" class="check-item" value="{{ $n->id }}">
                    </td>
                    <td class="text-center">
                        <small class="text-warning"><i class="fa fa-exclamation-triangle" title="Sin grupo"></i></small>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($n->fecha_ingreso)->format('d/m/Y') }}</td>
                    <td><b>{{ $n->nro_nota }}-{{ $n->anio }}</b></td>
                    <td>{{ $n->casino ? $n->casino->nombre : '---' }}</td>
                    <td>{{ $n->titulo }}</td>
                    <td>{{ $n->tipo_solicitud }}</td>
                    <td>
                        @if($n->expedientes->count() > 0)
                            <span class="label label-success">{{ $n->expedientes->first()->estado_actual }}</span>
                        @else
                            <span class="label label-warning">PENDIENTE</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-info btn-xs btn-ver-nota" data-id="{{ $n->id }}" title="Ver"><i class="fa fa-eye"></i></button>
                        <button class="btn btn-danger btn-xs btn-borrar-nota" data-id="{{ $n->id }}" title="Eliminar"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                @endforeach
            @endif
            
            @if($grupos->count() == 0 && (!isset($notasSueltas) || $notasSueltas->count() == 0))
            <tr>
                <td colspan="9" class="text-center">
                    <h4><i class="fa fa-search"></i> No se encontraron resultados.</h4>
                </td>
            </tr>
            @endif
        </tbody>
    </table>
    
    {{-- Paginación --}}
    <div class="pull-right">
        {{ $grupos->links() }}
    </div>
</div>

<style>
.grupo-row:hover {
    background: #f1f5f9 !important;
}
.nota-hija {
    transition: all 0.2s ease;
}
.toggle-icon {
    transition: transform 0.2s ease;
}
.grupo-row.expanded .toggle-icon {
    transform: rotate(90deg);
}
</style>
