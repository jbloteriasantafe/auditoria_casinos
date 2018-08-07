@extends('includes.nuevaBarraNavegacion')
@section('contenidoVista')

@section('estilos')
<link rel="stylesheet" href="css/lista-datos.css">
@endsection


<header>
  <img class="iconoSeccion" src="/img/logos/relevamientos_blue.png" alt="">
  <h2>LISTA DATOS</h2>
</header>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body" style="height:500px;">

                  <br><br>
                  <div class="row">
                      <div class="col-md-3 col-md-offset-3">





                          <!-- <input id="input-datos" class="form-control" type="text" value="">


                          <div class="contenedor-data-list">
                                  <div class="lista-data-list">
                                        <div class="elemento-data-list">
                                          <span id="id-dato">dato</span>
                                        </div>
                                        <div class="elemento-data-list">
                                          <span id="id-dato">dato</span>
                                        </div>
                                        <div class="elemento-data-list">
                                          <span id="id-dato">dato</span>
                                        </div>
                                        <div class="elemento-data-list">
                                          <span id="id-dato">dato</span>
                                        </div>
                                        <div class="elemento-data-list">
                                          <span id="id-dato">dato</span>
                                        </div>

                                  </div>
                          </div> -->
                          <input id="input-datos" class="form-control" type="text" value="">

                          <!-- purbea de objetos que quedan atras -->
                          <!-- <br>
                          <input class="form-control" type="text" name="" value=""> -->

                      </div> <!-- /.col -->

                      <div class="col-md-3">


                        <div class="input-group">
                            <input id="input-datos-grupo" class="form-control" type="text" value="">
                            <span class="input-group-btn">
                              <button class="btn btn-default btn-lista-datos" type="button"><i class="fa fa-plus"></i></button>
                            </span>
                        </div>


                      </div>

                  </div> <!-- /.row -->

            </div> <!-- /.panel-body -->
        </div> <!-- /.panel -->

    </div>
</div>
@endsection

@section('scripts')
<script src="js/lista-datos.js" charset="utf-8"></script>

<script type="text/javascript">
  $("#input-datos").generarDataList('usuarios/buscarUsuariosPorNombre','usuarios','id_usuario','nombre',2,false);
  $("#input-datos-grupo").generarDataList('usuarios/buscarUsuariosPorNombre','usuarios','id_usuario','nombre',2,true);
</script>

@endsection
