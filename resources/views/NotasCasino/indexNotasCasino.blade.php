@extends('includes.dashboard')
@section('headerLogo')
    <span class="etiquetaLogoMaquinas">@svg('maquinas', 'iconoMaquinas')</span>
@endsection

@section('estilos')
@endsection

@section('contenidoVista')
    <h1>Notas de Casino</h1>
    <p>Bienvenido a la sección de notas de casino.</p>
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
@endsection
