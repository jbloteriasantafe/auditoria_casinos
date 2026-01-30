<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<div class="kanban-board" style="display: flex; gap: 15px; overflow-x: auto; padding-bottom: 20px;">
    
    @php
        $columns = [
            'PENDIENTE' => 'warning', 
            'INICIO' => 'primary', 
            'NOTIFICADO' => 'info', 
            'RESP. CASINO' => 'danger', 
            'FINALIZADO' => 'success'
        ];
        // Group notes by status for Kanban (only for current page/filtered set)
        // Note: Ideally Kanban should load all or paginate differently, but for MVP we use current set
        $grouped = $notas->groupBy(function($item) {
            return $item->expedientes->count() > 0 ? $item->expedientes->first()->estado_actual : 'PENDIENTE';
        });
    @endphp

    @foreach($columns as $status => $color)
    <div class="kanban-column panel panel-{{ $color }}" style="flex: 0 0 300px; min-height: 400px; max-height: 80vh; display: flex; flex-direction: column;">
        <div class="panel-heading">
            <h3 class="panel-title text-center text-uppercase">
                {{ $status }} 
                <span class="badge pull-right">{{ isset($grouped[$status]) ? $grouped[$status]->count() : 0 }}</span>
            </h3>
        </div>
        <div class="panel-body kanban-dropzone" data-status="{{ $status }}" style="flex: 1; overflow-y: auto; background: #f0f0f0; padding: 10px;">
            
            @if(isset($grouped[$status]))
                @foreach($grouped[$status] as $n)
                <div class="kanban-card panel" data-id="{{ $n->id }}" style="margin-bottom: 10px; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.12);">
                    <div class="panel-body" style="padding: 10px;">
                        <span class="label label-default pull-right">{{ $n->nro_nota }}</span>
                        <h5 style="margin-top: 5px; margin-bottom: 5px; font-weight: bold;">{{ str_limit($n->titulo, 40) }}</h5>
                        <p class="small text-muted mb-0">
                            <i class="fa fa-building"></i> {{ $n->casino->nombre }}<br>
                            <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($n->fecha_ingreso)->format('d/m') }}
                        </p>
                        <div class="text-right" style="margin-top: 5px;">
                             <button class="btn btn-xs btn-info btn-ver-nota" data-id="{{ $n->id }}"><i class="fa fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif

        </div>
    </div>
    @endforeach
</div>

<style>
/* Estilos Kanban (podrían ir al CSS principal) */
.kanban-dropzone { scrollbar-width: thin; }
.kanban-card:hover { transform: translateY(-2px); transition: all 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.15) !important; }
</style>
