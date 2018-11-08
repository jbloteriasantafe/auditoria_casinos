@extends('layouts.dashboard')
@section('headerLogo')

@endsection
@section('estilos')
<link href="/css/fileinput.css" media="all" rel="stylesheet" type="text/css"/>
<link href="/themes/explorer/theme.css" media="all" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" href="/css/lista-datos.css">
@endsection
@section('contenidoVista')
 <b></b>
 <h3 class="modal-titleEliminar" style="color:#000000;">{{ $exception->getMessage() }}</h3>
 @endsection

 <!-- Comienza modal de ayuda -->
 @section('tituloDeAyuda')
 <h3 class="modal-title" style="color: #fff;">| AYUDA ERROR</h3>
 @endsection
 @section('contenidoAyuda')
 <div class="col-md-12">
   <h5>ERROR</h5>
   <p>
   Consulte a los desarrolladores.
   </p>
 </div>

 @endsection
 @section('scripts')
<!-- Custom input Bootstrap -->
 <script src="/js/fileinput.min.js" type="text/javascript"></script>
 <script src="/themes/explorer/theme.js" type="text/javascript"></script>
 @endsection
