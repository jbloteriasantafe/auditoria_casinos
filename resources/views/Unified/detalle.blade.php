@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
<style>
    .chat-container {
        height: 400px;
        overflow-y: scroll;
        border: 1px solid #ddd;
        padding: 15px;
        background: #fff;
    }
    .burbuja {
        padding: 10px;
        margin: 5px;
        border-radius: 10px;
        max-width: 70%;
    }
    .propia {
        background: #dcf8c6;
        margin-left: auto;
    }
    .ajena {
        background: #f1f0f0;
        margin-right: auto;
    }
    .timeline-item {
        padding: 10px;
        border-left: 2px solid #337ab7;
        margin-left: 20px;
        position: relative;
    }
    .timeline-item::before {
        content: '';
        width: 10px;
        height: 10px;
        background: #337ab7;
        border-radius: 50%;
        position: absolute;
        left: -6px;
        top: 15px;
    }
</style>
@endsection

@section('contenidoVista')
<div class="row">
    <div class="col-md-12">
        <a href="/notas-unificadas" class="btn btn-default"><i class="fa fa-arrow-left"></i> Volver</a>
        <h3>{{ $nota->titulo }} <small>Nota: {{ $nota->nro_nota }}-{{ $nota->anio }}</small></h3>
        
        <div class="row">
            <!-- Columna Izquierda: Expedientes y Chat -->
            <div class="col-md-8">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        @foreach($nota->expedientes as $exp)
                        <li class="{{ $loop->first ? 'active' : '' }}">
                            <a href="#tab_{{ $exp->id }}" data-toggle="tab">
                                Expediente {{ $exp->tipo_rama }} ({{ $exp->estado_actual }})
                            </a>
                        </li>
                        @endforeach
                    </ul>
                    <div class="tab-content">
                        @foreach($nota->expedientes as $exp)
                        <div class="tab-pane {{ $loop->first ? 'active' : '' }}" id="tab_{{ $exp->id }}">
                            <h4>Historial y Chat ({{ $exp->tipo_rama }})</h4>
                            
                            <div class="chat-container">
                                @foreach($exp->movimientos as $mov)
                                <div class="burbuja {{ $mov->id_usuario == 1 ? 'propia' : 'ajena' }}"> <!-- TODO: Check Auth::id() -->
                                    <strong>{{ $mov->accion }}</strong> - <small>{{ $mov->fecha_movimiento }}</small>
                                    <p>{{ $mov->comentario }}</p>
                                </div>
                                @endforeach
                            </div>
                            
                            <hr>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Escribir observación...">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="button">Enviar</button>
                                </span>
                            </div>

                            @if($exp->estado_actual == 'INICIO' || $exp->estado_actual == 'DICTAMEN_FAVORABLE') 
                            <!-- Habilitado en INICIO para pruebas, luego solo en DICTAMEN -->
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4>Paso 1: Generar PDF</h4>
                                        <a href="/notas-unificadas/disposicion/crear/{{ $exp->id }}" class="btn btn-warning">
                                            <i class="fa fa-pencil"></i> Editar
                                        </a>
                                        <a href="/notas-unificadas/disposicion/descargar/{{ $exp->id }}" class="btn btn-info" target="_blank">
                                            <i class="fa fa-download"></i> Descargar PDF
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <h4>Paso 2: Subir Firmado</h4>
                                        <form action="/notas-unificadas/disposicion/subir" method="POST" enctype="multipart/form-data">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="id_expediente" value="{{ $exp->id }}">
                                            <div class="form-group">
                                                <input type="file" name="archivo_firmado" class="form-control" required>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-block">
                                                <i class="fa fa-check"></i> Subir y Finalizar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Activos y Datos -->
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">Activos Afectados</div>
                    <div class="panel-body">
                        <ul>
                            @foreach($nota->activos as $activo)
                            <li>
                                <strong>{{ $activo->tipo_activo }}</strong>: {{ $activo->id_activo }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                
                <div class="panel panel-info">
                    <div class="panel-heading">Acciones Generales</div>
                    <div class="panel-body">
                        <button class="btn btn-default btn-block">Ver Adjuntos Nota</button>
                        <button class="btn btn-danger btn-block">Rechazar de Plano</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
