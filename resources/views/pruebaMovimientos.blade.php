@extends('includes.nuevaBarraNavegacion')


@section('contenidoVista')


<header>
  <img class="iconoSeccion" src="/img/logos/layout_blue.png" alt="">
  <h2>PLANOS</h2>
</header>


<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
          <h4>FABRICJS</h4>
      </div>
      <div class="panel-heading">
              <button id="btn-pruebaCarga" type="button" name="button">PRUEBA CARGA</button>

      </div>
    </div>
  </div>
</div>



@endsection

@section('scripts')
<script src="/js/pruebaMovimientos.js" type="text/javascript"></script>
@endsection
