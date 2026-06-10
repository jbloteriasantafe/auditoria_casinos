@php
function colorEstado($estado) {
    // Color manejado por la columna `color` de nota_estados (no hardcodeado por nombre).
    return \App\Models\NotaEstado::estilo($estado);
}
@endphp
@php
    // Para administradores la columna de fecha muestra "Fecha propuesta de realización" (FISC)
    // en lugar de "Fecha Est. Aprob." (MKT).
    $esAdm = isset($esAdministrador) && $esAdministrador;
    $colFechaLabel = $esAdm ? 'Fecha prop. realiz.' : 'Fecha Est. Aprob.';
    $colFechaSort  = $esAdm ? 'fecha_propuesta_realizacion' : 'fecha_pretendida_aprobacion';
    // FISC oculto en la carga inicial para funcionario1 (con "ver todo" sí lo ve) y SIEMPRE
    // para juego_responsable (este rol no tiene nada que ver con FISC). funcionario2 ve FISC.
    $ocultarFisc = isset($rolVista) && in_array($rolVista, ['funcionario1', 'juego_responsable']) && !(isset($verTodo) && $verTodo);
@endphp
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered" style="font-size: 13px;">
        <thead>
            <tr>
                <th width="3%" class="text-center">
                    {{-- Expand/Collapse --}}
                </th>
                <th width="7%" class="sortable th-filterable" data-sort="created_at" data-filter="fecha">Fecha Subida <i class="fa fa-sort"></i> <i class="fa fa-filter th-filter-icon" style="font-size:9px; color:#cbd5e1; margin-left:2px;"></i></th>
                <th width="7%" class="sortable" data-sort="{{ $colFechaSort }}">{{ $colFechaLabel }} <i class="fa fa-sort"></i></th>
                <th width="8%" class="sortable" data-sort="nro_nota">Nro Nota <i class="fa fa-sort"></i></th>
                <th width="10%" class="th-filterable" data-filter="casino" style="cursor:pointer;">Casino/Plat. <i class="fa fa-filter th-filter-icon" style="font-size:9px; color:#cbd5e1; margin-left:2px;"></i></th>
                <th width="18%">Título / Tema</th>
                <th width="7%" class="th-filterable" data-filter="rama" style="cursor:pointer;">Ramas <i class="fa fa-filter th-filter-icon" style="font-size:9px; color:#cbd5e1; margin-left:2px;"></i></th>
                <th width="8%" class="th-filterable" data-filter="estado" style="cursor:pointer;">Estado <i class="fa fa-filter th-filter-icon" style="font-size:9px; color:#cbd5e1; margin-left:2px;"></i></th>
                <th width="10%">Nro Aprob.</th>
                <th width="9%">Acciones</th>
            </tr>
        </thead>
        <tbody>
            {{-- GRUPOS DE TRÁMITE --}}
            @forelse($grupos as $grupo)
            @php
                $notaMkt = $grupo->notas->where('tipo_rama', 'MKT')->first();
                $notaFisc = $grupo->notas->where('tipo_rama', 'FISC')->first();
                // Admin: fecha propuesta de realización (FISC). Resto: fecha estimada de aprobación (MKT).
                if ($esAdm) {
                    $fechaCol = ($notaFisc && $notaFisc->fecha_propuesta_realizacion) ? \Carbon\Carbon::parse($notaFisc->fecha_propuesta_realizacion) : null;
                } else {
                    $fechaCol = ($notaMkt && $notaMkt->fecha_pretendida_aprobacion) ? \Carbon\Carbon::parse($notaMkt->fecha_pretendida_aprobacion) : null;
                }
            @endphp
            <tr class="grupo-row" data-grupo-id="{{ $grupo->id }}" data-fecha-aprob="{{ $fechaCol ? $fechaCol->format('Y-m-d') : '' }}" style="background: #f8fafc; cursor: pointer;">
                <td class="text-center toggle-grupo">
                    <i class="fa fa-chevron-right toggle-icon"></i>
                </td>
                <td>{{ \Carbon\Carbon::parse($grupo->created_at)->format('d/m/Y') }}</td>
                <td>
                    @if($fechaCol)
                        {{ $fechaCol->format('d/m/Y') }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td><b>{{ $grupo->nro_nota }}-{{ $grupo->anio }}</b></td>
                <td>
                    {{ $grupo->casino ? $grupo->casino->nombre : \App\Http\Controllers\NotasUnificadasController::resolverNombreCasino($grupo->id_casino, $grupo->id_plataforma) }}
                </td>
                <td>{{ $grupo->titulo }}</td>
                <td>
                    {{-- Badges para cada rama (apilados verticalmente) --}}
                    @foreach($grupo->notas as $nota)
                        @if($ocultarFisc && $nota->tipo_rama == 'FISC')
                            @continue
                        @endif
                        <div style="margin-bottom:2px;">
                            @if($nota->tipo_rama == 'MKT')
                                <span class="label label-primary">MKT</span>
                            @else
                                <span class="label label-success">FISC</span>
                            @endif
                        </div>
                    @endforeach
                </td>
                <td class="grupo-estados" data-grupo-id="{{ $grupo->id }}">
                    @php
                        $notasFiltradas = $ocultarFisc
                            ? $grupo->notas->where('tipo_rama', 'MKT')
                            : $grupo->notas;
                        $estados = $notasFiltradas->map(function($n) {
                            return $n->expedientes->first() ? $n->expedientes->first()->estado_actual : 'PENDIENTE';
                        })->unique();
                    @endphp
                    @foreach($estados as $est)
                        <div style="margin-bottom:2px;">
                            <span class="label" style="{{ colorEstado($est) }}">{{ $est }}</span>
                        </div>
                    @endforeach
                </td>
                <td>
                    @php
                        $aprobs = isset($aprobacionesPorGrupo[$grupo->id]) ? $aprobacionesPorGrupo[$grupo->id] : [];
                        // Ordenar siguiendo el orden de ramas del grupo (igual que estados)
                        $ordenRamas = $notasFiltradas->pluck('tipo_rama')->unique()->values()->toArray();
                        usort($aprobs, function($a, $b) use ($ordenRamas) {
                            $posA = array_search($a->tipo_rama, $ordenRamas);
                            $posB = array_search($b->tipo_rama, $ordenRamas);
                            if ($posA === false) $posA = 99;
                            if ($posB === false) $posB = 99;
                            return $posA - $posB;
                        });
                    @endphp
                    @foreach($aprobs as $ap)
                        @if($ocultarFisc && $ap->tipo_rama === 'FISC')
                            @continue
                        @endif
                        @php
                            $prefijo = ($ap->tipo_documento === 'DISPOSICION') ? 'D' : 'N';
                            $nroAp = $ap->numero_documento ? $prefijo . ' ' . $ap->numero_documento . '-' . $ap->anio_documento : '';
                            $claseRama = ($ap->tipo_rama === 'MKT') ? 'label-primary' : 'label-success';
                        @endphp
                        @if($nroAp)
                            <div style="margin-bottom:2px;">
                                <span class="label {{ $claseRama }}">{{ $nroAp }}</span>
                            </div>
                        @endif
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
                        <button class="btn btn-xs btn-complementar-grupo" data-grupo-id="{{ $grupo->id }}" data-rama="MKT" title="Agregar Nota Marketing"
                            style="background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; border-radius:10px; padding:2px 8px; font-size:10px; font-weight:600; letter-spacing:0.3px;">
                            <i class="fa fa-plus" style="font-size:9px;"></i> MKT
                        </button>
                    @endif
                    @if($faltaFisc && !(isset($esFuncionario) && $esFuncionario) && !$ocultarFisc)
                        <button class="btn btn-xs btn-complementar-grupo" data-grupo-id="{{ $grupo->id }}" data-rama="FISC" title="Agregar Nota Fiscalización"
                            style="background:linear-gradient(135deg,#0ba360,#3cba92); color:#fff; border:none; border-radius:10px; padding:2px 8px; font-size:10px; font-weight:600; letter-spacing:0.3px;">
                            <i class="fa fa-plus" style="font-size:9px;"></i> FISC
                        </button>
                    @endif

                    @php
                        // Concesionario: puede borrar la nota padre (grupo) solo si TODAS sus notas
                        // (la única que haya, o ambas) están en estado CARGA INICIAL.
                        $todasCargaInicial = $grupo->notas->count() > 0;
                        foreach ($grupo->notas as $__n) {
                            $__e = $__n->expedientes->first();
                            if (!$__e || $__e->estado_actual !== \App\Models\NotaEstado::CARGA_INICIAL) {
                                $todasCargaInicial = false;
                                break;
                            }
                        }
                        $concesionarioPuedeBorrarGrupo = isset($esConcesionario) && $esConcesionario && $todasCargaInicial;
                    @endphp
                    @if($puedeEliminarNotas || $concesionarioPuedeBorrarGrupo)
                    <button class="btn btn-danger btn-xs btn-borrar-grupo" data-id="{{ $grupo->id }}" title="Eliminar Grupo">
                        <i class="fa fa-trash"></i>
                    </button>
                    @endif
                </td>
            </tr>
            
            {{-- NOTAS HIJAS (Colapsadas por defecto) --}}
            @foreach($grupo->notas as $n)
            @if($ocultarFisc && $n->tipo_rama == 'FISC')
                @continue
            @endif
            <tr class="nota-hija" data-parent-grupo="{{ $grupo->id }}" style="display: none; background: {{ $n->tipo_rama == 'MKT' ? '#eff6ff' : '#ecfdf5' }};">
                <td style="background: {{ $n->tipo_rama == 'MKT' ? '#3b82f6' : '#10b981' }};"></td>
                <td><small>{{ \Carbon\Carbon::parse($n->fecha_ingreso)->format('d/m/Y') }}</small></td>
                @php $fechaHija = $esAdm ? $n->fecha_propuesta_realizacion : $n->fecha_pretendida_aprobacion; @endphp
                <td><small>{{ $fechaHija ? \Carbon\Carbon::parse($fechaHija)->format('d/m/Y') : '—' }}</small></td>
                <td>
                    @if($n->tipo_rama == 'MKT')
                        <span class="label label-primary">Marketing</span>
                    @else
                        <span class="label label-success">Fiscalización</span>
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
                        <span class="label estado-badge" data-id="{{ $n->id }}" style="{{ colorEstado($n->expedientes->first()->estado_actual) }}">
                              {{ $n->expedientes->first()->estado_actual }}
                        </span>
                    @else
                        <span class="label label-warning">PENDIENTE</span>
                    @endif
                </td>
                {{-- Celda "borrador" (anotación rápida inline). Editable solo si $puedeEditarBorrador.
                     width fijo + word-wrap: el texto crece hacia abajo, nunca empuja las columnas vecinas. --}}
                <td class="celda-borrador" style="width:140px; max-width:140px; padding:4px 6px; vertical-align:top; word-wrap:break-word; overflow-wrap:anywhere; word-break:break-word;">
                    @php $borrador = $n->borrador ?? ''; @endphp
                    @if(isset($puedeEditarBorrador) && $puedeEditarBorrador)
                        <span class="texto-borrador"
                              data-nota-id="{{ $n->id }}"
                              data-borrador="{{ e($borrador) }}"
                              style="cursor:pointer; font-size:11px; display:block; word-wrap:break-word; overflow-wrap:anywhere; word-break:break-word;"
                              title="{{ $borrador !== '' ? $borrador : 'Doble click para agregar' }}">
                            @if($borrador !== '')
                                {{ $borrador }}
                            @else
                                <small class="text-muted" style="font-style:italic; font-size:10px;">— doble click —</small>
                            @endif
                        </span>
                    @endif
                    {{-- Sin permiso: la celda queda vacía. El borrador es privado para roles privilegiados. --}}
                </td>
                <td>
                    <button class="btn btn-info btn-xs btn-ver-detalle-nota" 
                            data-nota-id="{{ $n->id }}" 
                            data-tipo-rama="{{ $n->tipo_rama }}" 
                            title="Ver Detalle">
                        <i class="fa fa-eye"></i>
                    </button>
                    @if(!isset($esFuncionario) || !$esFuncionario)
                    <button class="btn btn-warning btn-xs btn-agregar-adjuntos"
                            data-id="{{ $n->id }}"
                            data-tipo-rama="{{ $n->tipo_rama }}"
                            title="Agregar/Ver Adjuntos">
                        <i class="fa fa-paperclip"></i>
                    </button>
                    @endif
                    <button class="btn btn-info btn-xs btn-agregar-observaciones" 
                            data-id="{{ $n->id }}" 
                            data-nro-nota="{{ $n->nro_nota }}-{{ $n->anio }}"
                            title="Anotaciones PDF">
                        <i class="fa fa-edit"></i>
                    </button>
                    @php
                        // Concesionario/casino: puede borrar SUS notas solo mientras estén en CARGA INICIAL.
                        $estadoN = $n->expedientes->count() ? $n->expedientes->first()->estado_actual : null;
                        $concesionarioPuedeGestionarN = isset($esConcesionario) && $esConcesionario && $estadoN === \App\Models\NotaEstado::CARGA_INICIAL;
                    @endphp
                    @if($puedeEliminarNotas || $concesionarioPuedeGestionarN)
                    <button class="btn btn-danger btn-xs btn-borrar-nota" data-id="{{ $n->id }}" title="Eliminar">
                        <i class="fa fa-trash"></i>
                    </button>
                    @endif
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
                            <span class="label label-success" style="font-size: 9px;" title="Informe Técnico">INF</span>
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
                        <small class="text-warning"><i class="fa fa-exclamation-triangle" title="Sin grupo"></i></small>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($n->fecha_ingreso)->format('d/m/Y') }}</td>
                    <td><span class="text-muted">—</span></td>
                    <td><b>{{ $n->nro_nota }}-{{ $n->anio }}</b></td>
                    <td>{{ $n->casino ? $n->casino->nombre : \App\Http\Controllers\NotasUnificadasController::resolverNombreCasino($n->id_casino, $n->id_plataforma) }}</td>
                    <td>{{ $n->titulo }}</td>
                    <td>{{ $n->tipo_solicitud }}</td>
                    <td>
                        @if($n->expedientes->count() > 0)
                            <span class="label" style="{{ colorEstado($n->expedientes->first()->estado_actual) }}">{{ $n->expedientes->first()->estado_actual }}</span>
                        @else
                            <span class="label label-warning">PENDIENTE</span>
                        @endif
                    </td>
                    <td></td>
                    <td>
                        <button class="btn btn-info btn-xs btn-ver-nota" data-id="{{ $n->id }}" title="Ver"><i class="fa fa-eye"></i></button>
                        @if($puedeEliminarNotas)<button class="btn btn-danger btn-xs btn-borrar-nota" data-id="{{ $n->id }}" title="Eliminar"><i class="fa fa-trash"></i></button>@endif
                    </td>
                </tr>
                @endforeach
            @endif

            @if($grupos->count() == 0 && (!isset($notasSueltas) || $notasSueltas->count() == 0))
            <tr>
                <td colspan="10" class="text-center">
                    <h4><i class="fa fa-search"></i> No se encontraron resultados.</h4>
                </td>
            </tr>
            @endif
        </tbody>
    </table>
    

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
