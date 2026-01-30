@extends('includes.dashboard')
@section('headerLogo')
<span class="etiquetaLogoMaquinas">@svg('maquinas','iconoMaquinas')</span>
@endsection

@section('estilos')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<style>
    .editor-container {
        margin-top: 20px;
    }
</style>
@endsection

@section('contenidoVista')
<div class="row">
    <div class="col-md-12">
        <a href="/notas-unificadas/{{ $expediente->id_nota_ingreso }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Volver a la Nota</a>
        <h3>Redacción de Disposición <small>Expediente: {{ $expediente->tipo_rama }}</small></h3>
        
        <div class="panel panel-default">
            <div class="panel-body">
                <form id="frmDisposicion" action="/notas-unificadas/disposicion/guardar" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="id_expediente" value="{{ $expediente->id }}">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <label>Nro Disposición (Manual)</label>
                            <input type="text" class="form-control" name="nro_disposicion" value="{{ $disposicion->nro_disposicion_manual ?? '' }}" placeholder="Ej: 1234/26">
                        </div>
                    </div>
                    <br>
                    
                    <textarea id="editor" name="cuerpo_considerandos">
                        @if($disposicion)
                            {!! $disposicion->cuerpo_considerandos !!}
                        @else
                            <p style="text-align: center;"><strong>DISPOSICIÓN N° _______</strong></p>
                            <p><strong>VISTO:</strong></p>
                            <p>La Nota N° {{ $expediente->nota->nro_nota }}-{{ $expediente->nota->anio }} presentada por...</p>
                            <p>&nbsp;</p>
                            <p><strong>CONSIDERANDO:</strong></p>
                            <p>Que la solicitud se encuadra en...</p>
                            <p>&nbsp;</p>
                            <p><strong>EL DIRECTOR DE CASINOS RESUELVE:</strong></p>
                            <p><strong>ARTÍCULO 1°:</strong> Autorizar...</p>
                            <p>&nbsp;</p>
                            <p>&nbsp;</p>
                            <p style="text-align: right;">___________________________<br>Firma</p>
                        @endif
                    </textarea>

                    <br>
                    <button type="submit" class="btn btn-success pull-right btn-lg">
                        <i class="fa fa-save"></i> Guardar Borrador
                    </button>
                    <!-- <button type="button" class="btn btn-primary pull-right btn-lg" style="margin-right: 10px;">
                        <i class="fa fa-file-pdf-o"></i> Previsualizar PDF
                    </button> -->
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    tinymce.init({
        selector: '#editor',
        height: 600,
        menubar: true,
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'undo redo | formatselect | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
    });
</script>
@endsection
