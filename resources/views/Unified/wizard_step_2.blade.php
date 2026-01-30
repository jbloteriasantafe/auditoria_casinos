@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
@endsection

@section('contenidoVista')

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Paso 2: Configuración y Adjuntos</h4>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <strong>Nota Iniciada:</strong> {{ $nota->nro_nota }}-{{ $nota->anio }} | <strong>Título:</strong> {{ $nota->titulo }} <br>
                    <strong>Tipo:</strong> {{ $nota->tipo_solicitud }}
                </div>

                <form id="frmWizard" enctype="multipart/form-data">
                    <input type="hidden" name="id_nota" value="{{ $nota->id }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    @if($nota->tipo_solicitud == 'EVENTO')
                    <div class="row">
                        <div class="col-md-6">
                            <label>Fecha Inicio Evento</label>
                            <input type="date" class="form-control" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-6">
                            <label>Fecha Finalización Evento</label>
                            <input type="date" class="form-control" name="fecha_fin" required>
                        </div>
                    </div>
                    <br>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <label>Adjuntar Pautas (PDF/ZIP)</label>
                            <input id="adjuntoPautas" name="adjuntoPautas" type="file" class="file-loading">
                        </div>
                        <div class="col-md-6">
                            <label>Adjuntar Diseño (PDF/ZIP)</label>
                            <input id="adjuntoDisenio" name="adjuntoDisenio" type="file" class="file-loading">
                        </div>
                    </div>
                    <br>

                    @if($nota->tipo_solicitud == 'EVENTO')
                    <div class="row">
                        <div class="col-md-12">
                            <label>Adjuntar Bases y Condiciones (PDF/DOC/ZIP)</label>
                            <input id="basesyCondiciones" name="basesyCondiciones" type="file" class="file-loading">
                        </div>
                    </div>
                    <br>
                    @endif

                    <hr>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <a href="/notas-unificadas" class="btn btn-default">Omitir / Volver</a>
                            <button type="submit" class="btn btn-success">Finalizar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="/js/fileinput.min.js" type="text/javascript"></script>
<script src="/js/locales/es.js" type="text/javascript"></script>
<script src="/themes/explorer/theme.js" type="text/javascript"></script>
<script>
    $(document).ready(function(){
        $("#adjuntoPautas").fileinput({
            language: 'es',
            showUpload: false,
            allowedFileExtensions: ['pdf', 'zip'],
            maxFileSize: 15360,
        });
        $("#adjuntoDisenio").fileinput({
            language: 'es',
            showUpload: false,
            allowedFileExtensions: ['pdf', 'zip'],
            maxFileSize: 15360,
        });
        
        @if($nota->tipo_solicitud == 'EVENTO')
        $("#basesyCondiciones").fileinput({
            language: 'es',
            showUpload: false,
            allowedFileExtensions: ['pdf', 'zip', 'doc', 'docx'],
            maxFileSize: 15360,
        });
        @endif

        $('#frmWizard').submit(function(e){
            e.preventDefault();
            var formData = new FormData(this);
            
            $.ajax({
                url: '/notas-unificadas/guardar-adjuntos',
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data){
                    alert('Configuración guardada correctamente.');
                    window.location.href = '/notas-unificadas';
                },
                error: function(data){
                    alert('Error al guardar datos.');
                    console.log(data);
                }
            });
        });
    });
</script>
@endsection
