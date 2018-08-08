@extends('includes.barraNavegacion')

@section('estilos')
  <link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
@endsection


@section('contenidoVista')

        <div class="container-fluid">
          <div class="row">
            <div class="col-md-12 bannerGeneral">

            </div>
          </div>
        </div>

        <div id="page-wrapper">

            <div class="container-fluid">

                <div class="contenedor1" style="border: 1px solid; width: 300px; height: 300px;">
                    <div class="circle" draggable="true" style="background-color:blue; cursor: move;">

                    </div>

                    <div class="circle" draggable="true" style="background-color:blue; cursor: move;">

                    </div>
                </div>

                <div class="contenedor2" style="border: 1px solid; width: 300px; height: 300px;">

                </div>



            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->


    <!-- Modal Expediente -->
    <div class="modal fade" id="modalMovimiento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
             <div class="modal-content">
                <div class="modal-header" style="background: #5cb85c;">

                  <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                  <button id="btn-minimizar" type="button" class="close" data-toggle="collapse" data-minimizar="true" data-target="#colapsado" style="position:relative; right:20px; top:5px"><i class="fa fa-minus"></i></button>

                  <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button> -->
                  <h3 class="modal-title" style="color: #fff; text-align:center">NUEVO MOVIMIENTO</h3>
                </div>

                <div class="modal-body">

                  <!-- Panel que se minimiza -->
                  <div  id="colapsado" class="collapse in">
                    <form id="frmMovimiento" name="frmMovimiento" class="form-horizontal" novalidate="">

                      <div class="row">

                      </div>

                    </form>

                  </div> <!-- /Fin panel minimizable -->
                </div> <!-- Fin modal-header -->

                <div class="modal-footer">
                  <button type="button" class="btn btn-default" id="btn-cancelar" data-dismiss="modal" aria-label="Close">Cancelar</button>
                  <button type="button" class="btn btn-success" id="btn-guardar" value="nuevo">Crear EXPEDIENTE</button>
                  <input type="hidden" id="id_expediente" value="0">
                </div>
            </div>
          </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
             <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                  <h3 class="modal-title" id="myModalLabel">ADVERTENCIA</h3>
                </div>

                <div class="modal-body" style="color:#fff; background-color:#EF5350;">
                  <form id="frmEliminar" name="frmCasino" class="form-horizontal" novalidate="">
                      <div class="form-group error ">
                          <div class="col-xs-12">
                            <strong>¿Seguro desea eliminar el CASINO?</strong>
                          </div>
                      </div>
                  </form>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-danger" id="btn-eliminarModal" value="0">ELIMINAR</button>
                </div>
            </div>
          </div>
    </div>


    <meta name="_token" content="{!! csrf_token() !!}" />

    @endsection

    @section('scripts')
    <!-- JavaScript personalizado -->
    <script src="js/seccionMovimientos.js" charset="utf-8"></script>

    <!-- DateTimePicker JavaScript -->
    <script type="text/javascript" src="js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/bootstrap-datetimepicker.es.js" charset="UTF-8"></script>

    <script type="text/javascript">
        function handleDragStart(e) {
            this.style.opacity = '0.4';  // this / e.target is the source node.
        }

        var circulos = $('.circle');


        [].forEach.call(circulos, function(circulo) {
            circulo.addEventListener('dragstart', handleDragStart, false);
        });
    </script>
    @endsection
